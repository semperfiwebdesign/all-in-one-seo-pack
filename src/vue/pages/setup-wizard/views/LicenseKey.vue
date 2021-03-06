<template>
	<div class="aioseo-wizard-license-key">
		<wizard-header />
		<wizard-container>
			<wizard-body>
				<wizard-steps />

				<div class="header">
					{{ strings.enterYourLicenseKey }}
				</div>

				<div
					v-if="!$isPro"
					class="description"
					v-html="noLicenseNeeded"
				/>

				<div class="license-cta-box">
					<div v-html="tooltipText"></div>
					<br>
					<grid-row>
						<grid-column
							sm="6"
							v-for="(feature, index) in getSelectedUpsellFeatures"
							:key="index"
						>
							<svg-checkmark />
							{{ feature.name }}
						</grid-column>
					</grid-row>
				</div>

				<div
					v-if="!$isPro"
					v-html="alreadyPurchased"
				/>

				<form class="license-key">
					<input type="text" name="username" autocomplete="username" style="display:none;" />
					<base-input
						type="password"
						:placeholder="strings.placeholder"
						:append-icon="licenseKey ? 'circle-check' : null"
						autocomplete="new-password"
						v-model="licenseKey"
					/>
					<base-button
						type="green"
						:disabled="!licenseKey"
						:loading="loading"
						@click="processConnectOrActivate"
					>
						{{ strings.connect }}
					</base-button>
				</form>

				<core-alert
					class="license-key-error"
					v-if="error"
					type="red"
					v-html="error"
				/>

				<template #footer>
					<div class="go-back">
						<router-link :to="getPrevLink" class="no-underline">&larr;</router-link>
						&nbsp;
						<router-link :to="getPrevLink">{{ strings.goBack }}</router-link>
					</div>
					<div class="spacer"></div>
					<base-button
						type="gray"
						tag="router-link"
						:to="getNextLink"
					>{{ strings.skipThisStep }}</base-button>
				</template>
			</wizard-body>

			<wizard-close-and-exit />
		</wizard-container>
	</div>
</template>

<script>
import { popup } from '@/vue/utils/popup'
import { Wizard } from '@/vue/mixins'
import { mapActions, mapMutations, mapState } from 'vuex'
export default {
	mixins : [ Wizard ],
	data () {
		return {
			error      : null,
			loading    : false,
			stage      : 'license-key',
			licenseKey : null,
			strings    : {
				// Translators: 1 - The plugin short name ("AIOSEO").
				enterYourLicenseKey : this.$t.sprintf(this.$t.__('Enter your %1$s License Key', this.$td), process.env.VUE_APP_SHORT_NAME),
				boldText            : this.$t.sprintf('<strong>%1$s %2$s</strong>', process.env.VUE_APP_SHORT_NAME, 'Lite'),
				purchasedBoldText   : this.$t.sprintf('<strong>%1$s %2$s</strong>', process.env.VUE_APP_SHORT_NAME, 'Pro'),
				// Translators: 1 - "Pro".
				linkText            : this.$t.sprintf(this.$t.__('upgrade to %1$s', this.$td), 'Pro'),
				placeholder         : this.$t.__('Paste your license key here', this.$td),
				connect             : this.$t.__('Connect', this.$td)
			}
		}
	},
	watch : {
		licenseKey (newVal) {
			this.updateLicenseKey(newVal)
		}
	},
	computed : {
		...mapState([ 'options' ]),
		...mapState('wizard', {
			stateLicenseKey : 'licenseKey',
			presetFeatures  : 'features'
		}),
		noLicenseNeeded () {
			// Translators: 1 - The plugin name ("All in One SEO").
			return this.$t.sprintf(this.$t.__('You\'re using %1$s - no license needed. Enjoy!', this.$td) + ' 🙂', this.strings.boldText)
		},
		link () {
			return this.$t.sprintf('<strong><a href="%1$s" target="_blank">%2$s</a></strong>', this.$links.utmUrl('general-settings', 'license-box'), this.strings.linkText)
		},
		tooltipText () {
			return this.$isPro
				? this.$t.__('To unlock the selected features, please enter your license key below.', this.$td)
				// Translators: 1 - "upgrading to Pro".
				: this.$t.sprintf(this.$t.__('To unlock the selected features, please %1$s and enter your license key below.', this.$td), this.link)
		},
		alreadyPurchased () {
			// Translators: 1 - The plugin name ("All in One SEO").
			return this.$t.sprintf(this.$t.__('Already purchased? Simply enter your license key below to connect with %1$s!', this.$td), this.strings.purchasedBoldText)
		}
	},
	methods : {
		...mapActions([ 'getConnectUrl', 'processConnect', 'activate' ]),
		...mapActions('wizard', [ 'saveWizard' ]),
		...mapMutations('wizard', [ 'updateLicenseKey' ]),
		processConnectOrActivate () {
			if (this.$isPro) {
				return this.processActivateLicense()
			}

			this.processGetConnectUrl()
		},
		processActivateLicense () {
			this.error   = null
			this.loading = true
			this.$store.commit('loading', true)
			this.activate(this.licenseKey)
				.then(() => {
					this.$aioseo.internalOptions.internal.license.expired = false
					this.saveWizard('license-key')
						.then(() => {
							this.$router.push(this.getNextLink)
						})
				})
				.catch(error => {
					this.loading    = false
					this.licenseKey = null
					this.$store.commit('loading', false)
					if (!error || !error.response || !error.response.body || !error.response.body.error || !error.response.body.licenseData) {
						this.error = this.$t.__('An unknown error occurred, please try again later.', this.$tdPro)
						return
					}

					const data = error.response.body.licenseData
					if (data.invalid) {
						this.error = this.$t.__('The license key provided is invalid. Please use a different key to continue receiving automatic updates.', this.$tdPro)
					} else if (data.disabled) {
						this.error = this.$t.__('The license key provided is disabled. Please use a different key to continue receiving automatic updates.', this.$tdPro)
					} else if (data.expired) {
						this.error = this.licenseKeyExpired
					} else if (data.activationsError) {
						this.error = this.$t.__('This license key has reached the maximum number of activations. Please deactivate it from another site or purchase a new license to continue receiving automatic updates.', this.$tdPro)
					} else if (data.connectionError || data.requestError) {
						this.error = this.$t.__('There was an error connecting to the licensing API. Please try again later.', this.$tdPro)
					}
				})
		},
		processGetConnectUrl () {
			this.loading = true
			this.$store.commit('loading', true)
			this.getConnectUrl({
				key    : this.licenseKey,
				wizard : true
			})
				.then(response => {
					if (response.body.url) {
						if (!response.body.popup) {
							this.loading = false
							this.$store.commit('loading', false)
							return window.open(response.body.url)
						}

						this.openPopup(response.body.url)
					}
				})
		},
		openPopup (url) {
			popup(
				url,
				'_self',
				600,
				630,
				true,
				[ 'file', 'token' ],
				this.completedCallback,
				this.closedCallback
			)
		},
		completedCallback (payload) {
			payload.wizard = true
			return this.processConnect(payload)
		},
		closedCallback (reload) {
			if (reload) {
				return window.location.reload()
			}

			this.loading = false
			this.$store.commit('loading', false)
		}
	},
	mounted () {
		this.licenseKey = this.stateLicenseKey
	}
}
</script>

<style lang="scss">
.aioseo-wizard-license-key {
	font-size: 16px;
	color: $black;

	.header {
		font-size: 24px;
		color: $black;
		font-weight: 600;
	}

	.description {
		margin-top: 32px;
		font-size: 16px;
		color: $black2;
		margin-bottom: 20px;
	}

	.aioseo-settings-row {
		&:last-child {
			border-bottom: none;
			margin-bottom: 0;
		}

		&.feature-grid {
			.settings-name {
				.name {
					font-size: 18px;
				}
			}

			.aioseo-col {
				display: flex;
				align-items: center;
			}
		}
	}

	.go-back {
		a {
			color: $black2;
			font-size: 14px;
		}
	}

	.license-cta-box {
		border-radius: 3px;
		background-color: $inline-background;
		padding: 20px;
		max-width: 630px;
		margin: 10px 0 30px;

		a {
			color: $green;
		}

		> div:first-child {
			font-weight: 600;
			line-height: 1.4;
		}

		.aioseo-row {
			.aioseo-col {
				display: flex;
				align-items: center;

				svg {
					width: 16px;
					height: 16px;
					color: $blue;
					margin-right: 10px;
				}
			}
		}
	}

	.license-key {
		margin-top: 10px;
		display: flex;
		max-width: 560px;

		.aioseo-input {
			margin-right: 10px;
		}
	}

	.license-key-error {
		margin-top: 20px;
	}
}
</style>
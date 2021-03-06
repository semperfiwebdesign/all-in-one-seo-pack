<template>
	<core-settings-row
		:name="strings.licenseKey"
	>
		<template #description>
			{{ strings.licenseKeyDescription }}
		</template>

		<template #content>
			<div v-html="noLicenseNeeded" />

			<div class="license-cta-box">
				<div v-html="tooltipText"></div>
				<span v-html="moreToolTipText" />
			</div>

			<div v-html="alreadyPurchased" />

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
					@click="processGetConnectUrl"
				>
					{{ strings.connect }}
				</base-button>
			</form>
		</template>
	</core-settings-row>
</template>

<script>
import { popup } from '@/vue/utils/popup'
import { mapActions, mapState } from 'vuex'
export default {
	data () {
		return {
			licenseKey : null,
			strings    : {
				boldText          : this.$t.sprintf('<strong>%1$s %2$s</strong>', process.env.VUE_APP_SHORT_NAME, 'Lite'),
				purchasedBoldText : this.$t.sprintf('<strong>%1$s %2$s</strong>', process.env.VUE_APP_SHORT_NAME, 'Pro'),
				// Translators: 1 - "Pro".
				linkText          : this.$t.sprintf(this.$t.__('upgrading to %1$s', this.$td), 'Pro'),
				moreBoldText      : this.$t.sprintf(
					'<strong>%1$s</strong>',
					// Translators: This refers to a discount ("As a valued user you receive 50%, automatically applied at checkout!").
					'50% ' + this.$t.__('off', this.$td)
				),
				licenseKey            : this.$t.__('License Key', this.$td),
				licenseKeyDescription : this.$t.__('Your license key provides access to updates and addons.', this.$td),
				placeholder           : this.$t.__('Paste your license key here', this.$td),
				connect               : this.$t.__('Connect', this.$td)
			}
		}
	},
	computed : {
		...mapState([ 'loading' ]),
		link () {
			return this.$t.sprintf('<strong><a href="%1$s" target="_blank">%2$s</a></strong>', this.$links.utmUrl('general-settings', 'license-box'), this.strings.linkText)
		},
		tooltipText () {
			// Translators: 1 - "upgrading to Pro".
			return this.$t.sprintf(this.$t.__('To unlock more features, consider %1$s.', this.$td), this.link)
		},
		moreToolTipText () {
			// Translators: 1 - "50% off".
			return this.$t.sprintf(this.$t.__('As a valued user you receive %1$s, automatically applied at checkout!', this.$td), this.strings.moreBoldText)
		},
		noLicenseNeeded () {
			// Translators: 1 - The plugin name ("All in One SEO").
			return this.$t.sprintf(this.$t.__('You\'re using %1$s - no license needed. Enjoy!', this.$td) + ' 🙂', this.strings.boldText)
		},
		alreadyPurchased () {
			// Translators: 1 - The plugin name ("All in One SEO").
			return this.$t.sprintf(this.$t.__('Already purchased? Simply enter your license key below to connect with %1$s!', this.$td), this.strings.purchasedBoldText)
		}
	},
	methods : {
		...mapActions([ 'getConnectUrl', 'processConnect' ]),
		processGetConnectUrl () {
			this.$store.commit('loading', true)
			this.getConnectUrl({
				key : this.licenseKey
			})
				.then(response => {
					if (response.body.url) {
						if (!response.body.popup) {
							this.$store.commit('loading', false)
							return window.open(response.body.url, '_blank')
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
			return this.processConnect(payload)
		},
		closedCallback (reload) {
			if (reload) {
				return window.location.reload()
			}

			this.$store.commit('loading', false)
		}
	}
}
</script>

<style lang="scss">
.aioseo-general-settings {
	.more-tooltip-text strong {
		color: $green;
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

		div {
			font-weight: 600;
		}

		span {
			font-size: 14px;
			font-style: italic;
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
}
</style>
<template>
	<div class="aioseo-modal-content">
		<core-settings-row
			class="mobile-radio-buttons"
		>
			<template #content>
				<core-main-tabs
					:tabs="getTabs"
					:showSaveButton="false"
					:active="currentPost.tabs.tab_modal"
					internal
					@changed="value => processChangeTab(value)"
				/>
			</template>
		</core-settings-row>

		<div class="component-wrapper">
			<transition name="route-fade" mode="out-in">
				<component
					:is="this.activeTab"
					parentComponentContext="modal"
				/>
			</transition>
		</div>
	</div>
</template>

<script>
import { mapActions, mapState } from 'vuex'
import General from './General'
import Social from './Social'
export default {
	components : {
		General,
		Social
	},
	data () {
		return {
			activeTab : 'general',
			strings   : {
				pageName : this.$t.__('Modal Content', this.$td)
			},
			tabs : [
				{
					slug : 'general',
					icon : 'svg-settings',
					name : 'General'
				},
				{
					slug : 'social',
					icon : 'svg-share',
					name : 'Social'
				}
			]
		}
	},
	computed : {
		...mapState([ 'currentPost' ]),
		getTabs () {
			return this.tabs.filter(tab => this.$allowed(`aioseo_page_${tab.slug}_settings`))
		}
	},
	methods : {
		...mapActions([ 'changeTabSettings' ]),
		processChangeTab (newTabValue) {
			this.activeTab = newTabValue
			this.changeTabSettings({ setting: 'tab_modal', value: newTabValue })
		}
	}
}
</script>
<style lang="scss">
.aioseo-modal-content {
	.mobile-radio-buttons {
		display: block!important;
		> .col-md-3 {
			display: none;
		}
		> .col-md-9 {
			padding: 0;
			flex-basis: 100%!important;
			max-width: 100%!important;
		}
	}
	.aioseo-settings-row {
		> .col-md-3 {
			padding-bottom: 0;
		}
		> .col-md-3,
		> .col-md-9 {
			flex-basis: 100%;
			max-width: 100%;
		}
	}
	> .aioseo-settings-row {
		.aioseo-tabs {
			&.internal {
				padding-left: 40px;
			}
			.md-button-content {
				display: flex;
				align-items: center;
				svg {
					display: inline;
					width: 16px;
					height: 16px;
				}
			}
			.md-button {
				&:before {
					height: auto!important;
					border-radius: 0!important;
				}
				.label {
					display: inline!important;
					margin-left: 10px;
				}
			}
		}
	}
	> .aioseo-settings-row.mobile-radio-buttons {
		position: sticky;
		top: 60px;
		z-index: 10;
		padding-bottom: 0;
		.md-button {
			max-height: 46px;
		}
	}
	.aioseo-tab-content {
		position: relative;
		padding: 30px 40px !important;
		&.aioseo-post-social {
			padding: 22px 40px !important;
		}
	}
}
</style>
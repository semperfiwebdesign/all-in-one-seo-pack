<template>
	<core-main
		:page-name="strings.pageName"
		:show-save-button="showSaveButton"
	>
		<component :is="$route.name" />
	</core-main>
</template>

<script>
import BadBotBlocker from './BadBotBlocker'
import DatabaseTools from './DatabaseTools'
import Debug from './Debug'
import HtaccessEditor from './HtaccessEditor'
import ImportExport from './ImportExport'
import RobotsEditor from './RobotsEditor'
import SystemStatus from './SystemStatus'
export default {
	components : {
		BadBotBlocker,
		DatabaseTools,
		Debug,
		HtaccessEditor,
		ImportExport,
		RobotsEditor,
		SystemStatus
	},
	data () {
		return {
			strings : {
				pageName : this.$t.__('Tools', this.$td)
			}
		}
	},
	computed : {
		showSaveButton () {
			return 'system-status' !== this.$route.name &&
				'import-export' !== this.$route.name &&
				'database-tools' !== this.$route.name
		}
	}
}
</script>
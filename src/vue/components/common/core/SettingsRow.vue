<template>
	<grid-row
		class="aioseo-settings-row"
		:class="{ 'no-side-margin': noSideMargin }"
	>
		<slot name="header" />

		<grid-column
			:md="leftSize"
		>
			<div class="settings-name">
				<div
					class="name"
					:class="[
						{ align: align },
						{ 'align-small': alignSmall }
					]"
				>
					<slot name="name">
						{{ name }}
					</slot>
				</div>
				<div
					v-if="$slots.description"
					class="aioseo-description"
				>
					<slot name="description" />
				</div>
			</div>
		</grid-column>

		<grid-column
			:md="rightSize"
		>
			<div class="settings-content">
				<slot name="content" />
			</div>
		</grid-column>
	</grid-row>
</template>

<script>
export default {
	props : {
		align        : Boolean,
		alignSmall   : Boolean,
		name         : String,
		noSideMargin : {
			type    : Boolean,
			default : false
		},
		leftSize : {
			type : String,
			default () {
				return '3'
			}
		},
		rightSize : {
			type : String,
			default () {
				return '9'
			}
		}
	}
}
</script>

<style lang="scss">
.aioseo-settings-row {
	margin-bottom: 22px;
	padding-bottom: 16px;
	border-bottom: 1px solid $border;

	&.no-margin {
		margin-bottom: 0;
	}

	&.small-padding {
		padding-bottom: 5px;
	}

	&.medium-margin {
		margin-bottom: 15px;
	}

	&.no-border {
		border: none;
	}

	&.no-side-margin {
		margin-left: 0 !important;
		margin-right: 0 !important;
	}

	.settings-name {
		color: $black;

		.name {
			font-weight: 600;
			font-size: 16px;
			display: flex;
			align-items: center;

			&.small-margin {
				margin-bottom: 5px;
			}

			&.align {
				line-height: 40px;
			}

			&.align-small {
				line-height: 30px;
			}

			.aioseo-pro-badge {
				margin-left: 10px;
			}
		}

		.aioseo-description {
			margin-top: 20px;
		}
	}

	.settings-content {
		font-size: 16px;
	}

	p.description {
		font-size: 14px;
	}
}
</style>
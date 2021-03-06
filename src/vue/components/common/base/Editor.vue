<template>
	<div class="aioseo-editor">
		<div
			:class="[
				{ 'aioseo-editor-line-numbers': lineNumbers },
				{ 'aioseo-editor-single'      : single },
				{ 'aioseo-editor-monospace'   : monospace },
				{ 'aioseo-editor-description' : description }
			]"
			ref="quill"
		/>
		<div
			v-if="lineNumbers"
			ref="line-numbers"
			class="aioseo-line-numbers"
		/>
		<template
			v-for="(tag, index) in $tags.context(tagsContext)"
		>
			<div
				v-show="false"
				:key="index"
				ref="select-template"
			>
				<span
					class="aioseo-tag"
				>
					<span class="tag-name">{{ tag.name }}</span>
					<span
						v-if="tag.custom && tag.customValue"
						class="tag-custom"
					>
						- {{ tag.customValue }}
					</span>
					<span class="tag-toggle">
						<svg-caret />
					</span>
				</span>
			</div>
			<div
				v-show="false"
				:key="`menu-${index}`"
				ref="menu-template"
			>
				<div class="aioseo-tag-item">
					<div>
						<svg-plus />
					</div>
					<div>
						<div class="aioseo-tag-title">
							{{ tag.name }}
						</div>
						<div class="aioseo-tag-description">
							{{ tag.description }}
						</div>
					</div>
				</div>
			</div>
		</template>

		<div
			v-show="false"
			ref="tag-search"
		>
			<base-input
				size="medium"
				:placeholder="strings.searchPlaceholder"
				prependIcon="search"
			/>
		</div>

		<div
			v-show="false"
			ref="tag-custom"
		>
			<base-input
				size="small"
				:placeholder="strings.enterCustomFieldName"
			/>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import Quill from 'quill'
import '@/vue/plugins/quill/quill-line-numbers'
import '@/vue/plugins/quill/quill-mention'
import 'quill/dist/quill.snow.css'
import '@/vue/plugins/quill/quill-single-line'
import '@/vue/plugins/quill/quill-character-counter'

export default {
	props : {
		value : {
			type    : String,
			default : ''
		},
		minimumLineNumbers : {
			type : Number,
			default () {
				return 5
			}
		},
		single                 : Boolean,
		lineNumbers            : Boolean,
		allowTags              : Boolean,
		disabled               : Boolean,
		tagsContext            : String,
		forceUpdates           : Boolean,
		monospace              : Boolean,
		defaultMenuOrientation : String,
		description            : Boolean
	},
	data () {
		return {
			localTags   : [],
			quill       : null,
			html        : '',
			insertExact : false,
			strings     : {
				searchPlaceholder    : this.$t.__('Search for an item...', this.$td),
				enterCustomFieldName : this.$t.__('Enter a custom field name...', this.$td)
			}
		}
	},
	watch : {
		disabled () {
			if (this.disabled) {
				this.quill.disable()
			} else {
				this.quill.enable()
			}
		},
		value () {
			if (this.forceUpdates) {
				this.startup(true)
			}
		},
		liveTags : {
			deep : true,
			handler () {
				this.localTags       = this.getTags()
				const counter        = this.quill.getModule('counter')
				if (counter) {
					counter.options.tags = this.localTags
					this.$emit('counter', counter.calculate())
				}
			}
		},
		tags : {
			deep : true,
			handler () {
				// Only update the tags if they have changed.
				const newTags = this.getTags()
				if (JSON.stringify(this.localTags) !== JSON.stringify(newTags)) {
					this.localTags = newTags
					this.startup(true)
				}
			}
		}
	},
	computed : {
		...mapState([ 'currentPost', 'tags' ]),
		...mapState('live-tags', [ 'liveTags' ])
	},
	methods : {
		getTags () {
			const tags = this.tagsContext
				? [ ...this.$tags.context(this.tagsContext) ]
				: [ ...this.tags.tags ]
					.filter(tag => !tag.deprecated)
			return tags.map((item, index) => {
				const value = this.currentPost ? (this.liveTags[item.id] || item.value) : item.value
				return {
					...item,
					valueText : value,
					value     : this.$refs['select-template'][index] ? this.$refs['select-template'][index].innerHTML : '',
					menuHtml  : this.$refs['menu-template'][index] ? this.$refs['menu-template'][index].innerHTML : ''
				}
			})
		},
		update () {
			if (this.allowTags) {
				const counter = this.quill.getModule('counter')
				this.$emit('counter', counter.calculate())
			}

			let html = this.quill.getText() ? this.quill.root.innerHTML : ''

			const frag    = document.createRange().createContextualFragment(html)
			const fragNew = document.createRange().createContextualFragment('')
			frag.childNodes.forEach(node => {
				// quill wraps everything in <p /> tags so we are going to loop through those.
				if ('P' !== node.tagName) {
					return
				}

				node.childNodes.forEach(n => {
					if (3 === n.nodeType) {
						fragNew.appendChild(n.cloneNode(true))
						return
					}

					if ('SPAN' === n.tagName && this.allowTags) {
						const aioTag = n.querySelector('.aioseo-tag')
						if (aioTag) {
							const name = aioTag.querySelector('.tag-name')
							if (name) {
								const tag = this.localTags.find(t => t.name === name.textContent)
								if (tag.custom) {
									const custom = aioTag.querySelector('.tag-custom')
									if (custom) {
										const newNode = document.createTextNode(`#${tag.id}-${custom.innerText.replace(' - ', '')}`)
										fragNew.appendChild(newNode)
										return
									}
								}
								const newNode = document.createTextNode(`#${tag.id}`)
								fragNew.appendChild(newNode)
							}
						}
					}
				})

				const br = document.createElement('br')
				fragNew.appendChild(br)
			})

			fragNew.normalize()

			const div = document.createElement('div')
			div.appendChild(fragNew.cloneNode(true))

			html = div.innerHTML.replace(/<br\s*[/]?>/gi, this.single ? '' : '\n').trim()

			this.$emit('input', html)
		},
		insertTag (tagId) {
			const mention    = this.quill.getModule('mention')
			const textBefore = mention.getTextBeforeCursor()
			this.insertExact = true
			const tag  = tagId ? this.localTags.find(t => t.id === tagId) : null
			const text = tag ? `#${tag.id}` : '#' === textBefore.charAt(textBefore.length - 1) ? '' : '#'
			this.quill.focus()

			if (tagId) {
				mention.removeOrphanedMentionChar()
			}

			this.quill.insertText(this.quill.getSelection().index, text, Quill.sources.USER)
			this.quill.setSelection(this.quill.getSelection().index + text.length, Quill.sources.USER)
			this.insertExact = false

			if (!tagId) {
				setTimeout(() => {
					mention.mentionCharPos = this.quill.getSelection().index - 1
					mention.silentInsert   = true
					mention.showMentionList()
				}, 0)
			} else {
				mention.hideMentionList()
			}
		},
		maybeCloseMenu (event) {
			const element = event.target
			if (element.classList.contains('aioseo-tag') || element.closest('.aioseo-tag') || element.closest('.add-tags')) {
				return
			}

			if (element.classList.contains('ql-mention-list-container') || element.closest('.ql-mention-list-container')) {
				const prependIcon = element.classList.contains('prepend-icon') ? element : element.closest('.prepend-icon')
				if (prependIcon) {
					prependIcon.nextSibling.focus()
				}
				return
			}

			const mention = this.quill.getModule('mention')
			if (mention.isOpen) {
				mention.hideMentionList()
				mention.removeOrphanedMentionChar()
			}
		},
		startup (reset = false) {
			this.quill = new Quill(this.$refs.quill, {
				modules : {
					toolbar     : [],
					lineNumbers : this.lineNumbers
						? {
							container     : this.$refs['line-numbers'],
							defaultLength : this.minimumLineNumbers
						}
						: null,
					mention : this.allowTags
						? {
							defaultMenuOrientation    : this.defaultMenuOrientation || 'bottom',
							dataAttributes            : [ 'id', 'value', 'denotationChar', 'link', 'target', 'custom', 'customValue' ],
							allowedChars              : /^[A-Za-z\s_]*$/,
							mentionDenotationChars    : [ '#' ],
							spaceAfterInsert          : true,
							mentionPrependClass       : 'aioseo-tag-search',
							mentionPrependClassCustom : 'aioseo-tag-custom',
							prependMentionList        : this.$refs['tag-search'].innerHTML,
							customFieldInput          : this.$refs['tag-custom'].innerHTML,
							listItemClassNoMatch      : 'aioseo-tag-no-match',
							renderItemNoMatch () {
								return 'No matches found'
							},
							renderItem (item) {
								return `${item.menuHtml}`
							},
							source : (searchTerm, renderList, mentionChar, returnItem = false, customValue = '') => {
								const values = [ ...this.localTags ]
								for (let i = 0; i < values.length; i++) {
									if (values[i].custom) {
										values[i].customValue = customValue
									}
								}

								if (0 === searchTerm.length) {
									return renderList(values, searchTerm, returnItem, this.insertExact)
								} else {
									const matches = []
									for (let i = 0; i < values.length; i++) {
										if (
											~values[i].name.toLowerCase().indexOf(searchTerm.toLowerCase()) ||
										~values[i].id.toLowerCase().indexOf(searchTerm.toLowerCase())
										) { matches.push(values[i]) }
									}

									return renderList(matches, searchTerm, returnItem, this.insertExact)
								}
							}
						}
						: {},
					counter : this.allowTags
						? {
							tags : this.localTags
						}
						: null,
					clipboard : {
						newLines : !this.single
					},
					keyboard : {
						bindings : {
							enter : {
								key     : 13,
								handler : () => {
									return !this.single
								}
							}
						}
					}
				},
				theme   : 'snow',
				formats : [ 'mention' ]
			})

			if (reset) {
				this.quill.setText('')
			}

			// Make sure newlines are kept intact.
			let value = this.value
				? (
					this.single
						? this.value.replace('\n', ' ')
						: '<p>' + this.value
							.split('\n')
							.map(v => '' === v ? '<br>' : v)
							.join('</p><p>') + '</p>'
				)
				: this.value

			if (value && value.length) {
				value = value.trim() + '&nbsp;'
			}

			// Stop auto scrolling to the editor on paste of the HTML.
			const scrollTop = document.documentElement.scrollTop
			this.quill.clipboard.dangerouslyPasteHTML(0, value, Quill.sources.API)
			this.quill.blur()
			const mention = this.quill.getModule('mention')
			if (mention) {
				mention.removeOrphanedMentionChar()
			}
			document.documentElement.scrollTop = scrollTop

			if (this.allowTags) {
				const counter = this.quill.getModule('counter')
				this.$emit('counter', counter.calculate())
			}

			// We will add the update event here
			this.quill.on('text-change', () => this.update())
			this.quill.on('selection-change', (range, oldRange, source) => {
				if ('api' === source) {
					this.update()
				}
			})

			document.addEventListener('click', this.maybeCloseMenu)

			if (this.disabled) {
				this.quill.disable()
			}

			if (!reset) {
				this.quill.history.clear()
			}
		}
	},
	mounted () {
		this.localTags = this.getTags()
		this.startup(true)
	},
	beforeDestroy () {
		document.removeEventListener('click', this.maybeCloseMenu)
	}
}
</script>

<style lang="scss">
.aioseo-editor {
	position: relative;

	.aioseo-editor-description {
		.ql-editor {
			min-height: 100px;
		}
	}

	.aioseo-editor-line-numbers {
		.ql-editor {
			padding: 15px 15px 15px 45px;
		}
	}

	.aioseo-editor-single {
		.ql-editor {
			padding: 8px 10px;
		}

		&.aioseo-editor-line-numbers {
			.ql-editor {
				padding: 8px 10px 8px 45px;
			}
		}
	}

	.aioseo-editor-monospace {
		.ql-editor {
			font-family: monospace;
		}
	}

	.aioseo-line-numbers {
		background: #F7F6F7;
		position: absolute;
		text-align: right;
		top: 1px;
		width: 29px;
		left: 1px;
		border-radius: 3px 0 0 3px;
		padding: 15px 9px 0 0;
		display: flex;
		height: calc(100% - 2px);
		flex-direction: column;
		overflow: hidden;

		div {
			min-height: 25px;
			color: $placeholder-color;
			font-size: 12px;
			line-height: 1.9;
		}
	}

	.ql-disabled {
		pointer-events: none;
		background-color: $box-background;
	}

	.ql-editor {
		padding: 15px;
		border-radius: 3px;
		font-size: 16px;
		color: $black;
		border: 1px solid $input-border;

		&:focus {
			border: 1px solid $blue;
			box-shadow: 0 0 0 1px $blue;
		}

		.mention {
			.ql-mention-denotation-char {
				display: none;
			}

			.aioseo-tag {
				height: 25px;
				margin: 0 1px;
				color: $black2;
				font-weight: 600;
				font-size: 14px;
				padding: 3px 25px 3px 10px;
				background-color: $background;
				border-radius: 3px;
				cursor: pointer;
				position: relative;
				display: inline-flex;
				align-items: center;

				.tag-toggle {
					display: inline-flex;
					align-items: center;
					background-color: $border;
					position: absolute;
					top: 0;
					right: 0;
					bottom: 0;
					border-radius: 0px 3px 3px 0px;

					svg.aioseo-caret {
						width: 18px;
						height: 18px;
						transition: transform 0.3s;

						&.rotated {
							transform: rotate(180deg);
						}
					}
				}
			}
		}
	}

	.ql-mention-list-container {
		color: $black;
		background-color: #fff;
		max-width: 250px;
		width: 100%;
		margin-top: 3px;
		border: 1px solid $input-border;
		border-radius: 3px;
		box-shadow: 0px 3px 15px rgba(0, 0, 0, 0.1);
		z-index: 9001;

		.aioseo-tag-custom,
		.aioseo-tag-search {
			padding: 12px;
			border-bottom: 1px solid $border;
		}

		.aioseo-tag-custom {
			display: none;
		}

		.ql-mention-list {
			list-style: none;
			margin: 0;
			padding: 0;
			max-height: 210px;
			overflow: auto;

			li {
				color: $black;
				margin: 0;
				background-color: transparent;
				border-bottom: 1px solid $border;
				padding: 15px;
				cursor: pointer;
				font-size: 14px;

				&:last-child {
					border-bottom: 0;
				}

				&:hover,
				&.selected {
					color: $blue;
					background-color: $inline-background;

					.aioseo-tag-description {
						color: initial;
					}
				}

				.aioseo-tag-item {
					display: flex;

					> div:first-child {
						margin-right: 10px;
					}

					.aioseo-tag-title {
						font-weight: 600;
					}
				}

				svg.aioseo-plus {
					width: 10px;
					height: 10px;
					color: $blue;
				}

				&.aioseo-tag-no-match {
					cursor: default;
					padding: 12px;
					font-size: 16px;
					font-weight: 600;

					&:hover,
					&.highlight {
						color: initial;
						background-color: transparent;
					}
				}
			}
		}
	}

	.ql-toolbar {
		display: none;
	}

	.ql-clipboard {
		left: -100000px;
		height: 1px;
		overflow-y: hidden;
		position: absolute;
		top: 50%;
	}

	.ql-snow .ql-hidden {
		display: none;
	}

	.ql-container {
		&.ql-snow {
			border: none;
		}

		p {
			font-size: 16px;
			margin: 0;
			line-height: 25px;
		}
	}
}
</style>
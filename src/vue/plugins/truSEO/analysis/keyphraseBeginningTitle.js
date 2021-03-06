import { __ } from '@wordpress/i18n'
const td = process.env.VUE_APP_TEXTDOMAIN

function keyphraseInBeginningTitle (title, keyphrase) {
	if (!title) {
		return {}
	}

	const titleLower       = title.toLowerCase()
	const keyphraseLower   = keyphrase.toLowerCase()
	const keywordPosition  = titleLower.indexOf(keyphraseLower)
	const titleHalfLength  = Math.floor(titleLower.length / 2)
	const startWithKeyword = !!(0 <= keywordPosition && keywordPosition < titleHalfLength)

	if (startWithKeyword) {
		return {
			title       : __('Focus Keyphrase at the beginning of SEO Title', td),
			description : __('Focus Keyphrase used at the beginning of SEO title.', td),
			score       : 9,
			maxScore    : 9,
			error       : 0
		}
	}

	return {
		title       : __('Focus Keyphrase at the beginning of SEO Title', td),
		description : __('Focus Keyphrase doesn\'t appear at the beginning of SEO title.', td),
		score       : 3,
		maxScore    : 9,
		error       : 1
	}
}

export default keyphraseInBeginningTitle
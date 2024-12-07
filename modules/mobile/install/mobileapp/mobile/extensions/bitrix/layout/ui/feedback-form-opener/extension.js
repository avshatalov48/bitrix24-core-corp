/**
 * @module layout/ui/feedback-form-opener
 */
jn.define('layout/ui/feedback-form-opener', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');

	/**
	 * @public
	 * @function openFeedbackForm
	 * @params {string} formId
	 * @params {object} [openPageConfig = {}]
	 * @return void
	 */
	const openFeedbackForm = (formId, openPageConfig = {}) => {
		PageManager.openPage({
			backgroundColor: Color.bgSecondary.toHex(),
			url: `${env.siteDir}mobile/feedback?formId=${formId}`,
			backdrop: {
				mediumPositionPercent: 80,
				onlyMediumPosition: true,
				forceDismissOnSwipeDown: true,
				swipeAllowed: true,
				swipeContentAllowed: true,
				horizontalSwipeAllowed: false,
				navigationBarColor: Color.bgSecondary.toHex(),
				enableNavigationBarBorder: false,
			},
			titleParams: {
				text: Loc.getMessage('FEEDBACK_FORM_TITLE'),
			},
			enableNavigationBarBorder: false,
			modal: true,
			cache: true,
			...openPageConfig,
		});
	};

	module.exports = { openFeedbackForm };
});

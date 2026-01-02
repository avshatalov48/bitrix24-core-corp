/**
 * @module layout/ui/feedback-form-opener
 */
jn.define('layout/ui/feedback-form-opener', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const AppTheme = require('apptheme');

	class FeedbackForm
	{
		/**
		 * @param {Object} props
		 * @param {string} [props.formId]
		 * @param {string} [props.senderPage]
		 */
		constructor(props)
		{
			this.formId = AppTheme.id === 'dark' ? 'appFeedbackDark' : 'appFeedbackLight';

			if (props.formId)
			{
				this.formId = props.formId;
			}

			this.senderPage = props.senderPage ?? '';
		}

		/**
		 * @param {Object} [backdropConfig]
		 */
		openInBackdrop(backdropConfig = null)
		{
			const backdrop = backdropConfig ?? {
				mediumPositionPercent: 80,
				onlyMediumPosition: true,
				forceDismissOnSwipeDown: true,
				swipeAllowed: true,
				swipeContentAllowed: true,
				horizontalSwipeAllowed: false,
				navigationBarColor: Color.bgSecondary.toHex(),
				enableNavigationBarBorder: false,
			};

			this.open({ backdrop });
		}

		/**
		 * @param params
		 * @param {Object} [params.backdrop]
		 * @param {string} [params.title]
		 */
		open(params)
		{
			const {
				backdrop = null,
				title = Loc.getMessage('FEEDBACK_FORM_TITLE'),
			} = params;

			PageManager.openPage({
				backgroundColor: Color.bgSecondary.toHex(),
				url: this.getUrl(),
				backdrop,
				titleParams: {
					text: title,
				},
				modal: Boolean(backdrop),
				cache: true,
			});
		}

		getHiddenFields()
		{
			return encodeURIComponent(
				JSON.stringify({
					from_domain: currentDomain,
					back_version: Application.getAppVersion(),
					os_phone: Application.getPlatform(),
					app_version: Application.getApiVersion(),
					region_model: env.languageId,
					phone_model: device.model,
					os_version: device.version,
					sender_page: this.senderPage,
				}),
			);
		}

		getUrl()
		{
			return `${env.siteDir}mobile/settings?formId=${this.formId}&hiddenFields=${this.getHiddenFields()}`;
		}
	}

	/**
	 * @deprecated
	 * @public
	 * @function openFeedbackForm
	 * @params {string} formId
	 * @params {object} [openPageConfig = {}]
	 * @return void
	 */
	const openFeedbackForm = (formId, openPageConfig = {}) => {
		const hiddenFields = encodeURIComponent(
			JSON.stringify({
				from_domain: currentDomain,
				back_version: Application.getAppVersion(),
				os_phone: Application.getPlatform(),
				app_version: Application.getApiVersion(),
				region_model: env.languageId,
				phone_model: device.model,
				os_version: device.version,
			}),
		);

		PageManager.openPage({
			backgroundColor: Color.bgSecondary.toHex(),
			url: `${env.siteDir}mobile/settings?formId=${formId}&hiddenFields=${hiddenFields}`,
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

	module.exports = { FeedbackForm, openFeedbackForm };
});

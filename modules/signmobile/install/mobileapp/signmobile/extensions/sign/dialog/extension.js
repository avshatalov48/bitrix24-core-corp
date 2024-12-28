/**
 * @module sign/dialog
 */
jn.define('sign/dialog', (require, exports, module) => {
	const GO_TO_WEB_BANNER_TYPE = 'GO_TO_WEB';
	const GO_TO_WEB_EDITOR_BANNER_TYPE = 'GO_TO_WEB_EDITOR';
	const SIGNED_BANNER_TYPE = 'SIGNED';
	const REQUEST_BANNER_TYPE = 'REQUEST';
	const RESPONSE_BANNER_TYPE = 'RESPONSE';
	const REFUSED_BANNER_TYPE = 'REFUSED';
	const REFUSED_SELF_BANNER_TYPE = 'REFUSED_SELF';
	const PROCESSING_BANNER_TYPE = 'PROCESSING';
	const REVIEW_SUCCESS_BANNER_TYPE = 'REVIEW_SUCCESS';
	const ERROR_BANNER_TYPE = 'ERROR';
	const REQUEST_REVIEW_BANNER_TYPE = 'REQUEST_REVIEW';
	const SIGNED_BY_ASSIGNEE_BANNER_TYPE = 'SIGNED_BY_ASSIGNEE';
	const SIGNED_BY_EDITOR_BANNER_TYPE = 'SIGNED_BY_EDITOR';
	const PROCESSING_WAITING_BANNER_TYPE = 'PROCESSING_WAITING';
	const ERROR_ACCESS_DENIED_BANNER_TYPE = 'ERROR_ACCESS_DENIED';
	const REFUSED_BY_ASSIGNEE_BANNER_TYPE = 'REFUSED_BY_ASSIGNEE';

	/**
	 * @class SignDialog
	 */
	class SignDialog
	{
		/**
		 * @function ERROR_ACCESS_DENIED_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get ERROR_ACCESS_DENIED_BANNER_TYPE() {
			return ERROR_ACCESS_DENIED_BANNER_TYPE;
		}

		/**
		 * @function PROCESSING_WAITING_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get PROCESSING_WAITING_BANNER_TYPE() {
			return PROCESSING_WAITING_BANNER_TYPE;
		}

		/**
		 * @function REFUSED_SELF_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REFUSED_SELF_BANNER_TYPE() {
			return REFUSED_SELF_BANNER_TYPE;
		}

		/**
		 * @function SIGNED_BY_EDITOR_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get SIGNED_BY_EDITOR_BANNER_TYPE() {
			return SIGNED_BY_EDITOR_BANNER_TYPE;
		}

		/**
		 * @function REQUEST_REVIEW_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REQUEST_REVIEW_BANNER_TYPE() {
			return REQUEST_REVIEW_BANNER_TYPE;
		}

		/**
		 * @function REVIEW_SUCCESS_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REVIEW_SUCCESS_BANNER_TYPE() {
			return REVIEW_SUCCESS_BANNER_TYPE;
		}

		/**
		 * @function ERROR_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get ERROR_BANNER_TYPE() {
			return ERROR_BANNER_TYPE;
		}

		/**
		 * @function GO_TO_WEB_EDITOR_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get GO_TO_WEB_EDITOR_BANNER_TYPE() {
			return GO_TO_WEB_EDITOR_BANNER_TYPE;
		}

		/**
		 * @function REFUSED_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REFUSED_BANNER_TYPE() {
			return REFUSED_BANNER_TYPE;
		}

		/**
		 * @function PROCESSING_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get PROCESSING_BANNER_TYPE() {
			return PROCESSING_BANNER_TYPE;
		}

		/**
		 * @function RESPONSE_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get RESPONSE_BANNER_TYPE() {
			return RESPONSE_BANNER_TYPE;
		}

		/**
		 * @function REQUEST_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REQUEST_BANNER_TYPE() {
			return REQUEST_BANNER_TYPE;
		}

		/**
		 * @function GO_TO_WEB_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get GO_TO_WEB_BANNER_TYPE() {
			return GO_TO_WEB_BANNER_TYPE;
		}

		/**
		 * @function SIGNED_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get SIGNED_BANNER_TYPE() {
			return SIGNED_BANNER_TYPE;
		}

		/**
		 * @function SIGNED_BY_ASSIGNEE_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get SIGNED_BY_ASSIGNEE_BANNER_TYPE() {
			return SIGNED_BY_ASSIGNEE_BANNER_TYPE;
		}

		/**
		 * @function REFUSED_BY_ASSIGNEE_BANNER_TYPE
		 * @returns {string}
		 * @constructor
		 */
		static get REFUSED_BY_ASSIGNEE_BANNER_TYPE() {
			return REFUSED_BY_ASSIGNEE_BANNER_TYPE;
		}

		/**
		 * @function openSignConfirmationDialog
		 *
		 * @param props
		 */
		static openSignConfirmationDialog(
			props,
		)
		{
			const {
				title = '',
				memberId = 0,
				role,
				initiatedByType
			} = props;

			ComponentHelper.openLayout(
				{
					name: 'sign:sign.response.dialog',
					canOpenInDefault: true,
					widgetParams: SignDialog.getWidgetParams(),
					componentParams: {
						role,
						title,
						memberId,
						initiatedByType
					},
				},
			);
		}

		static getWidgetParams()
		{
			return {
				backdrop: {
					mediumPositionPercent: 75,
					hideNavigationBar: true,
					forceDismissOnSwipeDown: true,
					shouldResizeContent: true,
					swipeAllowed: true,
				},
			};
		}

		/**
		 * @function openRequestDialog
		 *
		 * @param props
		 */
		static openRequestDialog(
			props,
		)
		{
			const {
				title = '',
				memberId = 0,
				url = false,
				role,
				isGoskey,
				isExternal,
				initiatedByType
			} = props;

			ComponentHelper.openLayout(
				{
					name: 'sign:sign.request.dialog',
					canOpenInDefault: true,
					widgetParams: SignDialog.getWidgetParams(),
					componentParams: {
						title,
						memberId,
						role,
						url,
						isGoskey,
						isExternal,
						initiatedByType
					},
				},
			);
		}

		/**
		 * @function show
		 *
		 * @param props
		 */
		static show(props) {
			const {
				type,
				layoutWidget,
			} = props;

			const bannerConfig = {
				[SignDialog.ERROR_ACCESS_DENIED_BANNER_TYPE]: {
					path: 'dialog/banners/erroraccessdenied',
					component: 'ErrorAccessDenied',
				},
				[SignDialog.PROCESSING_WAITING_BANNER_TYPE]: {
					path: 'dialog/banners/processingwaiting',
					component: 'ProcessingWaiting',
				},
				[SignDialog.ERROR_BANNER_TYPE]: {
					path: 'dialog/banners/error',
					component: 'Error',
				},
				[SignDialog.REFUSED_SELF_BANNER_TYPE]: {
					path: 'dialog/banners/refusedjustnow',
					component: 'RefusedJustNow',
				},
				[SignDialog.GO_TO_WEB_BANNER_TYPE]: {
					path: 'dialog/banners/gotoweb',
					component: 'GoToWeb',
				},
				[SignDialog.GO_TO_WEB_EDITOR_BANNER_TYPE]: {
					path: 'dialog/banners/gotowebeditor',
					component: 'GoToWebEditor',
				},
				[SignDialog.SIGNED_BANNER_TYPE]: {
					path: 'dialog/banners/signed',
					component: 'Signed',
				},
				[SignDialog.SIGNED_BY_ASSIGNEE_BANNER_TYPE]: {
					path: 'dialog/banners/signedbyassignee',
					component: 'SignedByAssignee',
				},
				[SignDialog.SIGNED_BY_EDITOR_BANNER_TYPE]: {
					path: 'dialog/banners/signedbyeditor',
					component: 'SignedByEditor',
				},
				[SignDialog.REQUEST_BANNER_TYPE]: {
					path: 'dialog/banners/request',
					component: 'Request',
				},
				[SignDialog.REQUEST_REVIEW_BANNER_TYPE]: {
					path: 'dialog/banners/requestreview',
					component: 'RequestReview',
				},
				[SignDialog.RESPONSE_BANNER_TYPE]: {
					path: 'dialog/banners/response',
					component: 'Response',
				},
				[SignDialog.REFUSED_BANNER_TYPE]: {
					path: 'dialog/banners/refused',
					component: 'Refused',
				},
				[SignDialog.PROCESSING_BANNER_TYPE]: {
					path: 'dialog/banners/processing',
					component: 'Processing',
				},
				[SignDialog.REVIEW_SUCCESS_BANNER_TYPE]: {
					path: 'dialog/banners/reviewsuccess',
					component: 'ReviewSuccess',
				},
				[SignDialog.REFUSED_BY_ASSIGNEE_BANNER_TYPE]: {
					path: 'dialog/banners/refusedbyassignee',
					component: 'RefusedByAssignee',
				},
			};

			const config = bannerConfig[type];
			if (config && layoutWidget)
			{
				jn.import(`sign:${config.path}`)
					.then(() => {
						const {
							[config.component]: BannerComponent,
						} = require(`sign/${config.path}`);
						layout.showComponent(new BannerComponent(props));
					});
			}
		}
	}

	module.exports = { SignDialog };
});

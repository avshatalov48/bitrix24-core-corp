/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-actions/share
 */
jn.define('crm/entity-actions/share', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	/**
	 * @function getActionToShare
	 * @returns {Object}
	 */
	const getActionToShare = () => {
		const id = 'share';

		const title = Loc.getMessage('M_CRM_ENTITY_ACTION_SHARE');

		const svgIcon = '<svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.4397 11.9683C10.4397 12.2445 10.2158 12.4683 9.93967 12.4683H8.10513C7.82899 12.4683 7.60513 12.2445 7.60513 11.9683V5.03303H5.08657C4.74505 5.03303 4.5813 4.61371 4.83245 4.38227L8.75636 0.766229C8.89994 0.633913 9.12103 0.633913 9.26461 0.766229L13.1885 4.38227C13.4397 4.61371 13.2759 5.03303 12.9344 5.03303H10.4397V11.9683Z" fill="#525C69"/><path d="M0.25 11.3833C0.25 11.1071 0.473858 10.8833 0.75 10.8833H2.59546C2.8716 10.8833 3.09546 11.1071 3.09546 11.3833V14.7028C3.09546 15.3932 3.6551 15.9528 4.34546 15.9528H13.6545C14.3449 15.9528 14.9045 15.3932 14.9045 14.7028V11.3833C14.9045 11.1071 15.1284 10.8833 15.4045 10.8833H17.25C17.5261 10.8833 17.75 11.1071 17.75 11.3833V15.8745C17.75 17.4623 16.4628 18.7495 14.875 18.7495H3.125C1.53718 18.7495 0.25 17.4623 0.25 15.8745V11.3833Z" fill="#6a737f"/></svg>';

		const iconUrl = '/bitrix/mobileapp/crmmobile/extensions/crm/entity-actions/images/share.png';

		/**
		 * @param {string} url
		 * @returns {Promise}
		 */
		const onAction = (url) => new Promise(() => {
			if (!Type.isStringFilled(url))
			{
				throw new TypeError('url is not a filled string');
			}

			if (!url.startsWith('http'))
			{
				url = currentDomain + url;
			}

			dialogs.showSharingDialog({ message: url });
		});

		return { id, title, svgIcon, iconUrl, onAction };
	};

	module.exports = { getActionToShare };
});

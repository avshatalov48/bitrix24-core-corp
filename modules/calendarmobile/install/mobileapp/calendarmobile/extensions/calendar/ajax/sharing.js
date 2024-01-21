/**
 * @module calendar/ajax/sharing
 */
jn.define('calendar/ajax/sharing', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const SharingActions = {
		ENABLE: 'enable',
		DISABLE: 'disable',
		IS_ENABLED: 'isEnabled',
		GET_PUBLIC_USER_LINK: 'getPublicUserLink',
		SAVE_LINK_RULE: 'saveLinkRule',
		INIT_CRM: 'initCrm',
	};

	/**
	 * @class SharingAjax
	 */
	class SharingAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'calendarmobile.sharing';
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		isEnabled()
		{
			return this.fetch(SharingActions.IS_ENABLED);
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		enable()
		{
			return this.fetch(SharingActions.ENABLE, {});
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		disable()
		{
			return this.fetch(SharingActions.DISABLE, {});
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		getPublicUserLink()
		{
			return this.fetch(SharingActions.GET_PUBLIC_USER_LINK);
		}

		/**
		 * @param data {Object}
		 * @return {Promise<Object, void>}
		 */
		saveLinkRule(data)
		{
			return this.fetch(SharingActions.SAVE_LINK_RULE, data);
		}

		/**
		 * @param data {Object}
		 * @returns {Promise<Object, void>}
		 */
		initCrm(data)
		{
			return this.fetch(SharingActions.INIT_CRM, data);
		}
	}

	module.exports = {
		SharingAjax: new SharingAjax(),
		SharingActions,
	};
});

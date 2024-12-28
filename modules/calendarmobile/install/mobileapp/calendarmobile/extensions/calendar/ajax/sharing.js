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
		GENERATE_USER_JOINT_SHARING_LINK: 'generateUserJointSharingLink',
		GET_ALL_USER_LINK: 'getAllUserLink',
		DISABLE_USER_LINK: 'disableUserLink',
		INCREASE_FREQUENT_USE: 'increaseFrequentUse',
		SET_SORT_JOINT_LINKS_BY_FREQUENT_USE: 'setSortJointLinksByFrequentUse',
		SAVE_LINK_RULE: 'saveLinkRule',
		INIT_CRM: 'initCrm',
		GENERATE_GROUP_JOINT_SHARING_LINK: 'generateGroupJointSharingLink',
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
		 * @param data {{memberIds: number[]}}
		 * @return {Promise<Object, void>}
		 */
		createJointLink(data)
		{
			return this.fetch(SharingActions.GENERATE_USER_JOINT_SHARING_LINK, data);
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		getAllUserLinks()
		{
			return this.fetch(SharingActions.GET_ALL_USER_LINK);
		}

		deleteUserLink(data)
		{
			return this.fetch(SharingActions.DISABLE_USER_LINK, data);
		}

		increaseFrequentUse(data)
		{
			return this.fetch(SharingActions.INCREASE_FREQUENT_USE, data);
		}

		setSortJointLinksByFrequentUse(data)
		{
			return this.fetch(SharingActions.SET_SORT_JOINT_LINKS_BY_FREQUENT_USE, data);
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

		/**
		 * @param memberIds {array}
		 * @param groupId {number}
		 * @param dialogId {string}
		 * @returns {Promise<Object, void>}
		 */
		generateGroupJointSharingLink({ memberIds, groupId, dialogId = '' })
		{
			return this.fetch(SharingActions.GENERATE_GROUP_JOINT_SHARING_LINK, { memberIds, groupId, dialogId });
		}
	}

	module.exports = {
		SharingAjax: new SharingAjax(),
	};
});

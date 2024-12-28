/**
 * @module im/messenger/lib/params
 */
jn.define('im/messenger/lib/params', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ComponentCode } = require('im/messenger/const');

	/**
	 * @class MessengerParams
	 */
	class MessengerParams
	{
		constructor()
		{
			const configMessages = this.get('MESSAGES', {});

			Object.keys(configMessages).forEach((messageId) => {
				Loc.setMessage(messageId, configMessages[messageId]);
			});
		}

		get(key, defaultValue)
		{
			return BX.componentParameters.get(key, defaultValue);
		}

		set(key, value)
		{
			BX.componentParameters.set(key, value);
		}

		getSiteDir()
		{
			return this.get('SITE_DIR', '/');
		}

		getUserId()
		{
			return Number(this.get('USER_ID', 0));
		}

		getGeneralChatId()
		{
			return Number(this.get('IM_GENERAL_CHAT_ID', 0));
		}

		/**
		 * @return {string}
		 */
		getMessengerTitle()
		{
			return this.get('MESSAGES', { COMPONENT_TITLE: '' }).COMPONENT_TITLE;
		}

		/**
		 *
		 * @return {string || ''}
		 */
		getComponentCode()
		{
			return this.get('COMPONENT_CODE', '');
		}

		setGeneralChatId(id)
		{
			this.set('IM_GENERAL_CHAT_ID', id);
		}

		isOpenlinesOperator()
		{
			return this.get('OPENLINES_USER_IS_OPERATOR', false);
		}

		isBetaAvailable()
		{
			return this.get('IS_BETA_AVAILABLE', false);
		}

		isChatM1Enabled()
		{
			return this.get('IS_CHAT_M1_ENABLED', false);
		}

		isChatLocalStorageAvailable()
		{
			return this.get('IS_CHAT_LOCAL_STORAGE_AVAILABLE', false);
		}

		shouldShowChatV2UpdateHint()
		{
			return this.get('SHOULD_SHOW_CHAT_V2_UPDATE_HINT', false);
		}

		isCloud()
		{
			return this.get('IS_CLOUD', false);
		}

		hasActiveCloudStorageBucket()
		{
			return this.get('HAS_ACTIVE_CLOUD_STORAGE_BUCKET', false);
		}

		/**
		 * @return boolean
		 */
		canUseTelephony()
		{
			return this.get('CAN_USE_TELEPHONY', false);
		}

		/**
		* @return {PlanLimits}
		*/
		getPlanLimits()
		{
			return this.get('PLAN_LIMITS', {});
		}

		/**
		 * @param {PlanLimits} limits
		* @return void
		*/
		setPlanLimits(limits)
		{
			this.set('PLAN_LIMITS', limits);
		}

		/**
		 * @return {boolean}
		 */
		isFullChatHistoryAvailable()
		{
			const limits = this.getPlanLimits();
			const componentCode = this.getComponentCode();
			if (componentCode !== ComponentCode.imChannelMessenger && limits?.fullChatHistory)
			{
				return limits?.fullChatHistory?.isAvailable;
			}

			return true;
		}

		/**
		 * @return ImFeatures
		 */
		getImFeatures()
		{
			return this.get('IM_FEATURES', {
				chatDepartments: false,
				chatV2: false,
				collabAvailable: false,
				collabCreationAvailable: false,
				copilotActive: false,
				copilotAvailable: false,
				giphyAvailable: false,
				sidebarBriefs: false,
				sidebarFiles: false,
				sidebarLinks: false,
				zoomActive: false,
				zoomAvailable: false,
			});
		}

		/**
		 * @return UserInfo
		 */
		getUserInfo()
		{
			return this.get('USER_INFO', {
				id: 0,
				type: 'user',
			});
		}

		/**
		 * @return Permissions
		 */
		getPermissions()
		{
			return this.get('PERMISSIONS', {});
		}

		/**
		 * @return {number}
		 */
		getMultipleActionMessageLimit()
		{
			return this.get('MULTIPLE_ACTION_MESSAGE_LIMIT', 20);
		}
	}

	module.exports = {
		MessengerParams: new MessengerParams(),
	};
});

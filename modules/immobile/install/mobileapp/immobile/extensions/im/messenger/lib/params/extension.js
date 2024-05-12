/**
 * @module im/messenger/lib/params
 */
jn.define('im/messenger/lib/params', (require, exports, module) => {

	const { Loc } = require('loc');

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

		isCopilotAvailable()
		{
			return this.get('IS_COPILOT_AVAILABLE', false);
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

		isCopilotAddUsersEnabled()
		{
			return this.get('IS_COPILOT_ADD_USERS', false);
		}
	}

	module.exports = {
		MessengerParams: new MessengerParams(),
	};
});

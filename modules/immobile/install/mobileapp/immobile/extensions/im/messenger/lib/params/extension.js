/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable flowtype/require-return-type */

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

			Object.keys(configMessages).forEach(messageId => {
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
	}

	module.exports = {
		MessengerParams: new MessengerParams(),
	};
});

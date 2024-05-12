/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/mention
 */
jn.define('im/messenger/lib/parser/functions/mention', (require, exports, module) => {

	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const parserMention = {
		decode(text)
		{
			text = text.replace(/\[USER=([0-9]+)( REPLACE)?](.*?)\[\/USER]/ig, (whole, userId, replace, userName) => {
				userId = Number.parseInt(userId, 10);
				if (!Type.isNumber(userId) || userId === 0)
				{
					return userName;
				}

				if (replace || !userName)
				{
					const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](userId);
					if (user)
					{
						userName = user.name;
					}
				}

				if (!userName)
				{
					userName = `User ${userId}`;
				}

				return `[USER=${userId}]${userName}[/USER]`;
			});

			return text;
		},

		simplify(text)
		{
			text = text.replace(/\[USER=([0-9]+)( REPLACE)?](.*?)\[\/USER]/ig, (whole, userId, replace, userName) => {
				userId = Number.parseInt(userId, 10);

				if (!Type.isNumber(userId) || userId === 0)
				{
					return userName;
				}

				if (replace || !userName)
				{
					const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](userId);
					if (user)
					{
						userName = user.name;
					}
				}

				if (!userName)
				{
					userName = `User ${userId}`;
				}

				return userName;
			});

			text = text.replace(
				/\[CHAT=(imol\|)?(\d+)](.*?)\[\/CHAT]/gi,
				(whole, openlines, chatId, chatName) => {
					chatId = Number.parseInt(chatId, 10);

					if (!chatName)
					{
						const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById']('chat' + chatId);
						chatName = dialog? dialog.name: 'Chat ' + chatId;
					}

					return chatName;
				}
			);

			text = text.replace(
				/\[dialog=(chat\d+|\d+)(?: message=(\d+))?](.*?)\[\/dialog]/gi,
				(whole, dialogId, messageId, text) => {
					if (!text)
					{
						const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);
						text = dialog? dialog.name: 'Dialog ' + dialogId;
					}

					return text;
				}
			);

			return text;
		},
	};

	module.exports = {
		parserMention,
	};
});

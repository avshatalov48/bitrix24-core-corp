/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/cache/messages
 */
jn.define('im/messenger/cache/messages', (require, exports, module) => {

	const { clone } = require('utils/object');
	const { Cache } = require('im/messenger/cache/base');

	/**
	 * @class MessagesCache
	 */
	class MessagesCache extends Cache
	{
		constructor()
		{
			super({
				name: 'messages',
			});
		}

		save(state)
		{
			return Promise.resolve();

			const firstPageState = clone(state);
			firstPageState.saveMessageList = {};

			for (const dialogId in firstPageState.collection)
			{
				if (!firstPageState.collection.hasOwnProperty(dialogId))
				{
					continue;
				}

				const firstPageLength = 50;
				firstPageState.collection[dialogId] = firstPageState.collection[dialogId].splice(0, firstPageLength);
				firstPageState.collection[dialogId].forEach(message => {
					message.isPlaying = false;

					if (!firstPageState.saveMessageList[dialogId])
					{
						firstPageState.saveMessageList[dialogId] = {};
					}

					firstPageState.saveMessageList[dialogId][message.id] = true;
				});
			}

			return super.save(firstPageState);
		}
	}

	module.exports = {
		MessagesCache: new MessagesCache(),
	};
});

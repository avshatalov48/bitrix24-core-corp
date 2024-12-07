/**
 * @module im/messenger/lib/element/dialog/message/check-in/const/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/const/configuration', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CheckInType } = require('im/messenger/lib/element/dialog/message/check-in/const/type');

	/**
	 * @type {CheckInMetaData}
	 */
	const metaData = {
		[CheckInType.withLocation]: {
			button: {
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHECK_IN_BUTTON'),
				callback: ({ dialogId, chatTitle }) => {
					showCheckIn(dialogId, chatTitle);
				},
			},
		},
		[CheckInType.withoutLocation]: {
			button: {
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHECK_IN_BUTTON'),
				callback: ({ dialogId, chatTitle }) => {
					showCheckIn(dialogId, chatTitle);
				},
			},
		},
	};

	async function showCheckIn(dialogId, chatTitle)
	{
		const { Entry } = await requireLazy('stafftrack:entry');

		if (Entry)
		{
			void Entry.openCheckIn({
				dialogId,
				dialogName: chatTitle,
			});
		}
	}

	module.exports = {
		metaData,
	};
});

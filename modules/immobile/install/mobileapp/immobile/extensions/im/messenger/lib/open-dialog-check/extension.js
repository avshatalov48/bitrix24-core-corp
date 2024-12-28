/**
 * @module im/messenger/lib/open-dialog-check
 */
jn.define('im/messenger/lib/open-dialog-check', (require, exports, module) => {
	const { ChatDataProvider } = require('im/messenger/provider/data');
	const { DialogType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');

	async function checkIsOpenDialogSupported(dialogId)
	{
		const chatProvider = new ChatDataProvider();
		const chatDataResult = await chatProvider.get({ dialogId });
		if (!chatDataResult.hasData())
		{
			return null;
		}

		const dialog = chatDataResult.getData();
		const isUnsupportedCollabDialog = (dialog.type === DialogType.collab && !Feature.isCollabSupported);
		if (isUnsupportedCollabDialog)
		{
			return false;
		}

		return true;
	}

	module.exports = {
		checkIsOpenDialogSupported,
	};
});

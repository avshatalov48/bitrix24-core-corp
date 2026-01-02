/**
 * @module im/messenger-v2/application/lib/dialog-manager/resolver
 */
jn.define('im/messenger-v2/application/lib/dialog-manager/resolver', (require, exports, module) => {
	const { DialogType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { CopilotDialog } = require('im/messenger/controller/dialog/copilot');
	const { AiAssistantDialog } = require('im/messenger/controller/dialog/ai-assistant');

	/**
	 * @param {DialoguesModelState} dialog
	 * @return {Promise<Dialog|null>}
	 */
	async function createDialog(dialog)
	{
		const dialogId = dialog.dialogId;
		const dialogType = dialog?.type;

		if (dialogType === DialogType.copilot)
		{
			return new CopilotDialog(dialogId);
		}

		// TODO: handle case when AiAssistantBot is not in the store
		const dialogHelper = DialogHelper.createByDialogId(dialogId);
		if (dialogHelper?.isAiAssistant)
		{
			return new AiAssistantDialog(dialogId);
		}

		return new Dialog();
	}

	module.exports = {
		createDialog,
	};
});

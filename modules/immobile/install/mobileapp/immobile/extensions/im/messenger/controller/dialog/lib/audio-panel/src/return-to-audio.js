/**
 * @module im/messenger/controller/dialog/lib/audio-panel/src/return-to-audio
 */
jn.define('im/messenger/controller/dialog/lib/audio-panel/src/return-to-audio', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { DialogActionType } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');

	/**
	 * @param {ReturnAudioData} audioData
	 * @param {Dialog} dialog
	 * @param {function} openDialog
	 * @returns {Promise<void>}
	 * @constructor
	 */
	async function returnToAudioMessage({ audioData, dialog, openDialog })
	{
		if (isEmpty(audioData))
		{
			return;
		}

		const { dialogId, messageId } = audioData;

		const isVisible = await VisibilityManager
			.getInstance()
			.checkIsDialogVisible({
				dialogId,
				currentContextOnly: true,
			});

		const toMessageOptions = {
			messageId,
			targetMessagePosition: AfterScrollMessagePosition.center,
		};

		if (isVisible)
		{
			void dialog.goToMessage(toMessageOptions);
		}
		else if (dialog)
		{
			void openDialog({
				dialogId,
				actionsAfterOpen: [
					{
						type: DialogActionType.goToMessage,
						...toMessageOptions,
					},
				],
			});
		}
	}

	module.exports = {
		returnToAudioMessage,
	};
});

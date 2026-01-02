/**
 * @module im/messenger-v2/application/lib/dialog-manager/manager
 */
jn.define('im/messenger-v2/application/lib/dialog-manager/manager', (require, exports, module) => {
	const { Type } = require('type');

	const { DialogType } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { openTaskComments } = require('im/messenger/lib/integration/tasksmobile/comments/opener');

	const { DialogManagerService } = require('im/messenger-v2/application/lib/dialog-manager/service');
	const { normalizeOpenDialogOptions } = require('im/messenger-v2/application/lib/dialog-manager/normalizer');
	const { createDialog } = require('im/messenger-v2/application/lib/dialog-manager/resolver');

	/**
	 * @class DialogManager
	 */
	class DialogManager
	{
		static #instance;
		/** @type {Array<Dialog>} */
		#dialogList = [];

		/**
		 * @return {DialogManager}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		/**
		 * @return {Dialog|null}
		 */
		getFirstOpenDialog()
		{
			return this.getOpenDialogByIndex(0);
		}

		/**
		 * @return {Dialog|null}
		 */
		getLastOpenDialog()
		{
			return this.getOpenDialogByIndex(this.#dialogList.length - 1);
		}

		/**
		 * @return {Dialog|null}
		 */
		getOpenDialogByIndex(index)
		{
			return (Type.isArrayFilled(this.#dialogList) && this.#dialogList[index]) ? this.#dialogList[index] : null;
		}

		/**
		 * @param {DialogOpenOptions} options
		 * @param {PageManager} parentWidget
		 * @return {Promise<boolean>}
		 */
		async openDialog(options, parentWidget = PageManager)
		{
			const normalizedOptions = normalizeOpenDialogOptions(options);
			const dialogId = normalizedOptions.dialogId;
			if (!Type.isStringFilled(dialogId))
			{
				return false;
			}

			const isVisible = await VisibilityManager.getInstance().checkIsDialogVisible({
				dialogId,
				currentContextOnly: true,
			});

			if (isVisible)
			{
				return false;
			}

			if (normalizedOptions.makeTabActive)
			{
				PageManager.getNavigator().makeTabActive();
			}

			const dialogManagerService = DialogManagerService.getInstance();
			const dialogModel = await dialogManagerService.getDialogByDialogId(dialogId);
			if (!Type.isPlainObject(dialogModel))
			{
				return false;
			}

			// TODO: MessengerV2 generalize the processing of chat integrations
			if (
				!Type.isPlainObject(normalizedOptions.integrationSettings)
				&& dialogModel.type === DialogType.tasksTask
				&& Type.isStringFilled(dialogModel.entityId)
			)
			{
				void openTaskComments(dialogModel.chatId, Number(dialogModel.entityId));

				return true;
			}

			const dialog = await createDialog(dialogModel);
			this.#dialogList.push(dialog);

			const originalCloseHandler = normalizedOptions.onClose;
			normalizedOptions.onClose = () => {
				this.#dialogList = this.#dialogList.filter((openDialog) => openDialog !== dialog);
				originalCloseHandler();
			};

			await dialog.open(normalizedOptions, parentWidget);

			return true;
		}
	}

	module.exports = {
		DialogManager,
	};
});

/**
 * @module im/messenger/db/repository/internal/dialog
 */
jn.define('im/messenger/db/repository/internal/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const {
		DialogInternalTable,
	} = require('im/messenger/db/table');

	/**
	 * @class DialogInternalRepository
	 */
	class DialogInternalRepository
	{
		/**
		 * @return {DialogInternalRepository}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.dialogInternalTable = new DialogInternalTable();
		}

		/**
		 * @param {Array<DialogRow>} dialogListToAdd
		 */
		async saveByDialogList(dialogListToAdd)
		{
			const internalDialogListToAdd = [];
			dialogListToAdd.forEach((dialog) => {
				const internalDialog = this.validate(dialog);
				const internalDialogToAdd = this.dialogInternalTable.validate(internalDialog);

				internalDialogListToAdd.push(internalDialogToAdd);
			});

			return this.dialogInternalTable.addIfNotExist(internalDialogListToAdd);
		}

		/**
		 * @param {Partial<DialogRow>} dialog
		 * @return {Partial<DialogInternalRow>}
		 */
		validate(dialog)
		{
			const result = {};

			if (Type.isStringFilled(dialog.dialogId))
			{
				result.dialogId = dialog.dialogId;
			}

			if (Type.isNumber(dialog.chatId))
			{
				result.chatId = dialog.chatId;
			}

			return dialog;
		}

		/**
		 * @param {Array<string|number>} idList
		 */
		async deleteByIdList(idList)
		{
			return this.dialogInternalTable.deleteByIdList(idList);
		}

		/**
		 * @param {Array<string|number>} idList
		 * @param {boolean} wasCompletelySync
		 */
		async setWasCompletelySyncByIdList(idList, wasCompletelySync)
		{
			return this.dialogInternalTable.setWasCompletelySyncByIdList(idList, wasCompletelySync);
		}
	}

	module.exports = {
		DialogInternalRepository,
	};
});

/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/dialog
 */
jn.define('im/messenger/db/model-writer/vuex/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { DialogHelper } = require('im/messenger/lib/helper');

	const { Logger } = require('im/messenger/lib/logger');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class DialogWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('dialoguesModel/add', this.addRouter)
				.on('dialoguesModel/update', this.updateRouter)
				.on('dialoguesModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('dialoguesModel/add', this.addRouter)
				.off('dialoguesModel/update', this.updateRouter)
				.off('dialoguesModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload<DialoguesAddData, DialoguesAddActions>} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'set',
				'add',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			const dialogId = data.dialogId;
			if (!dialogId)
			{
				return;
			}

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				Logger.warn(`DialogWriter.addRouter: there is no dialog with dialogId "${dialogId}" in model`);

				return;
			}

			const dialogHelper = DialogHelper.createByModel(dialog);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.dialog.saveFromModel([dialog]);
		}

		/**
		 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} mutation.payload
		 */
		updateRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const updateActions = [
				'set',
				'update',
				'mute',
				'unmute',
				'updateType',
				'decreaseCounter',
				'updateUserCounter',
				'updatePermissions',
				'setLastMessageViews',
				'incrementLastMessageViews',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			const dialogId = data.dialogId;
			if (!dialogId)
			{
				return;
			}

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				Logger.warn(`DialogWriter.updateRouter: there is no dialog with dialogId "${dialogId}" in model`);

				return;
			}

			const dialogHelper = DialogHelper.createByModel(dialog);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.dialog.saveFromModel([dialog]);
		}

		/**
		 * @param {MutationPayload<DialoguesDeleteData, DialoguesDeleteActions>} mutation.payload
		 */
		deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const deleteActions = [
				'delete',
			];
			if (!deleteActions.includes(actionName))
			{
				return;
			}

			const dialogId = data.dialogId;
			if (Type.isNumber(dialogId) || Type.isStringFilled(dialogId))
			{
				this.repository.dialog.deleteById(dialogId);
			}
		}
	}

	module.exports = {
		DialogWriter,
	};
});

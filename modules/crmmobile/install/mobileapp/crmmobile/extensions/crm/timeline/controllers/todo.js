/**
 * @module crm/timeline/controllers/todo
 */
jn.define('crm/timeline/controllers/todo', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { TodoActivityConfig } = require('crm/timeline/services/file-selector-configs');
	const { FileSelector } = require('layout/ui/file/selector');
	const { NotifyManager } = require('notify-manager');
	const { ResponsibleSelector } = require('crm/timeline/services/responsible-selector');

	const SupportedActions = {
		ADD_FILE: 'Activity:ToDo:AddFile',
		CHANGE_RESPONSIBLE: 'Activity:ToDo:ChangeResponsible',
	};

	/**
	 * @class TimelineTodoController
	 */
	class TimelineTodoController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.ADD_FILE:
					return this.openFileManager(actionParams);
				case SupportedActions.CHANGE_RESPONSIBLE:
					return this.openUserSelector(actionParams);
			}
		}

		openFileManager(actionParams)
		{
			if (actionParams.files && actionParams.files !== '')
			{
				this.itemScopeEventBus.emit('Crm.Timeline.Item.OpenFileManagerRequest');
			}
			else
			{
				FileSelector.open(TodoActivityConfig({
					focused: true,
					entityTypeId: actionParams.ownerTypeId,
					entityId: actionParams.ownerId,
					activityId: actionParams.entityId,
				}));
			}
		}

		openUserSelector(actionParams)
		{
			const { responsibleId } = actionParams;

			ResponsibleSelector.show({
				onSelectedUsers: this.updateResponsibleUser.bind(this, actionParams),
				responsibleId,
				layout,
			});
		}

		/**
		 * @param {object} actionParams
		 * @param {array} selectedUsers
		 */
		updateResponsibleUser(actionParams, selectedUsers)
		{
			const selectedUserId = selectedUsers[0].id;
			if (selectedUserId === actionParams.responsibleId)
			{
				return;
			}

			actionParams.responsibleId = selectedUserId;

			BX.ajax.runAction('crm.activity.todo.updateResponsibleUser', { data: actionParams })
				.catch((response) => {
					NotifyManager.showErrors(response.errors);
				});
		}
	}

	module.exports = { TimelineTodoController };
});

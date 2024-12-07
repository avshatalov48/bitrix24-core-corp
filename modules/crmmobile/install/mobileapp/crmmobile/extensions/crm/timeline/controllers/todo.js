/**
 * @module crm/timeline/controllers/todo
 */
jn.define('crm/timeline/controllers/todo', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { TodoActivityConfig } = require('crm/timeline/services/file-selector-configs');
	const { FileSelector } = require('layout/ui/file/selector');
	const { NotifyManager } = require('notify-manager');
	const { ResponsibleSelector } = require('crm/timeline/services/responsible-selector');
	const { ProfileView } = require('user/profile');
	const { AnalyticsEvent } = require('analytics');

	const SupportedActions = {
		ADD_FILE: 'Activity:ToDo:AddFile',
		CHANGE_RESPONSIBLE: 'Activity:ToDo:ChangeResponsible',
		USER_CLICK: 'Activity:ToDo:User:Click',
		CLIENT_CLICK: 'Activity:ToDo:Client:Click',
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
		// eslint-disable-next-line consistent-return
		onItemAction({ action, actionParams = {} })
		{
			// eslint-disable-next-line default-case
			switch (action)
			{
				case SupportedActions.ADD_FILE:
					return this.openFileManager(actionParams);
				case SupportedActions.CHANGE_RESPONSIBLE:
					return this.openUserSelector(actionParams);
				case SupportedActions.USER_CLICK:
					return this.openUserProfile(actionParams);
				case SupportedActions.CLIENT_CLICK:
					return this.openCrmEntity(actionParams);
			}
		}

		async openCrmEntity(actionParams)
		{
			const { entityTypeId, entityId } = actionParams || {};

			if (!entityTypeId || !entityId)
			{
				return;
			}

			const payload = {
				entityId: Number(entityId),
				entityTypeId: Number(entityTypeId),
			};
			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

			const analytics = new AnalyticsEvent(BX.componentParameters.get('analytics', {}))
				.setSection('text_link');
			EntityDetailOpener.open({
				payload,
				analytics,
			});
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

		openUserProfile(actionParams)
		{
			const { userId } = actionParams || {};

			if (!userId)
			{
				return;
			}

			const widgetParams = {
				groupStyle: true,
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			};

			PageManager.openWidget('list', widgetParams)
				.then((list) => ProfileView.open({ userId }, list))
				.catch(console.error);
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

			const data = {
				...actionParams,
				responsibleId: selectedUserId,
			};

			BX.ajax.runAction('crm.activity.todo.updateResponsibleUser', { data })
				.catch((response) => {
					NotifyManager.showErrors(response.errors);
				});
		}
	}

	module.exports = { TimelineTodoController };
});

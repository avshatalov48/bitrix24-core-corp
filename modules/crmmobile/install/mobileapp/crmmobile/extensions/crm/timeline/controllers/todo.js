/**
 * @module crm/timeline/controllers/todo
 */
jn.define('crm/timeline/controllers/todo', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { TodoActivityConfig } = require('crm/timeline/services/file-selector-configs');
	const { FileSelector } = require('layout/ui/file/selector');
	const { NotifyManager } = require('notify-manager');
	const { ResponsibleSelector } = require('crm/timeline/services/responsible-selector');
	const { AnalyticsEvent } = require('analytics');
	const { UserProfile } = require('user-profile');

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
		 * @param {AnalyticsEvent|null} analyticsEvent
		 */
		// eslint-disable-next-line consistent-return
		onItemAction({ action, actionParams = {}, analyticsEvent = null })
		{
			// eslint-disable-next-line default-case
			switch (action)
			{
				case SupportedActions.ADD_FILE:
					return this.openFileManager(actionParams, analyticsEvent);
				case SupportedActions.CHANGE_RESPONSIBLE:
					return this.openUserSelector(actionParams, analyticsEvent);
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

			const analytics = new AnalyticsEvent(
				BX.componentParameters.get('analytics', {}),
			)
				.setSection('text_link');

			EntityDetailOpener.open({
				payload,
				analytics,
			});
		}

		openFileManager(actionParams, analyticsEvent)
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
					analyticsEvent: analyticsEvent
						?.setEvent('activity_edit')
						.setElement('edit_button'),
				}));
			}
		}

		openUserSelector(actionParams, analyticsEvent)
		{
			const { responsibleId } = actionParams;

			ResponsibleSelector.show({
				onSelectedUsers: this.updateResponsibleUser.bind(this, actionParams, analyticsEvent),
				responsibleId,
				layout,
			});
		}

		openUserProfile(actionParams)
		{
			const { userId } = actionParams || {};

			void UserProfile.open({
				ownerId: userId,
			});
		}

		/**
		 * @param {object} actionParams
		 * @param {AnalyticsEvent|null} analyticsEvent
		 * @param {array} selectedUsers
		 */
		updateResponsibleUser(actionParams, analyticsEvent, selectedUsers)
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

			BX.ajax.runAction('crm.activity.todo.updateResponsibleUser', {
				data,
				analyticsLabel: analyticsEvent
					?.setEvent('activity_edit')
					.setElement('edit_button')
					.exportToObject(),
			})
				.catch((response) => {
					NotifyManager.showErrors(response.errors);
				});
		}
	}

	module.exports = { TimelineTodoController };
});

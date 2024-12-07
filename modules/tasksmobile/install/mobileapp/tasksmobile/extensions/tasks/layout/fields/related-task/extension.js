/**
 * @module tasks/layout/fields/related-task
 */
jn.define('tasks/layout/fields/related-task', (require, exports, module) => {
	const { TaskFieldClass } = require('tasks/layout/task/fields/task');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');
	const store = require('statemanager/redux/store');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { Icon } = require('assets/icons');

	/**
	 * @class RelatedTaskField
	 */
	class RelatedTaskField extends TaskFieldClass
	{
		getAddButtonText()
		{
			return BX.message('TASKS_FIELDS_RELATED_TASK_ADD_BUTTON_TEXT');
		}

		openTaskCreateForm()
		{
			const userId = this.getUserid();
			const currentTaskId = this.getCurrentTaskId();

			if (!userId)
			{
				this.logger.warn('User id is not defined');

				return;
			}

			if (!currentTaskId)
			{
				this.logger.warn('Current task id is not defined');

				return;
			}

			const mapUser = (user) => {
				if (user)
				{
					return {
						id: user.id,
						name: user.fullName,
						image: user.avatarSize100,
						link: user.link,
						workPosition: user.workPosition,
					};
				}

				return null;
			};

			const taskCreateParameters = {
				initialTaskData: {
					responsible: mapUser(usersSelector.selectById(store.getState(), userId)),
					groupId: this.getGroupId(),
					group: this.getPreparedGroupData(),
					relatedTaskId: currentTaskId,
				},
				layoutWidget: this.getParentWidget(),
				context: 'tasks.dashboard',
				analyticsLabel: this.getAnalyticsLabel(),
			};

			openTaskCreateForm(taskCreateParameters);
		}

		getDefaultLeftIcon()
		{
			return Icon.LINK;
		}
	}

	module.exports = { RelatedTaskField };
});

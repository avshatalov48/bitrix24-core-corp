/**
 * @module tasks/layout/fields/subtask
 */
jn.define('tasks/layout/fields/subtask', (require, exports, module) => {
	const { TaskFieldClass } = require('tasks/layout/task/fields/task');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');
	const store = require('statemanager/redux/store');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { Icon } = require('assets/icons');

	/**
	 * @class SubtaskField
	 */
	class SubtaskField extends TaskFieldClass
	{
		getAddButtonText()
		{
			return BX.message('TASKS_FIELDS_SUBTASK_ADD_BUTTON_TEXT');
		}

		openTaskCreateForm()
		{
			const currentTaskId = this.getCurrentTaskId();
			const userId = this.getUserid();

			if (!currentTaskId)
			{
				this.logger.warn('Current task id is not defined');

				return;
			}

			if (!userId)
			{
				this.logger.warn('User id is not defined');

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
					parentId: currentTaskId,
					groupId: this.getGroupId(),
					group: this.getPreparedGroupData(),
				},
				layoutWidget: this.getParentWidget(),
				context: 'tasks.dashboard',
				analyticsLabel: this.getAnalyticsLabel(),
			};

			openTaskCreateForm(taskCreateParameters);
		}

		getDefaultLeftIcon()
		{
			return Icon.RELATED_TASKS;
		}
	}

	module.exports = { SubtaskField };
});

/**
 * @module crm/timeline/scheduler/providers/task
 */
jn.define('crm/timeline/scheduler/providers/task', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { Type } = require('crm/type');
	const { AnalyticsLabel } = require('analytics-label');

	let TaskCreate;

	try
	{
		TaskCreate = require('tasks/layout/task/create').TaskCreate;
	}
	catch (e)
	{
		console.warn(e);
	}

	/**
	 * @class TimelineSchedulerTaskProvider
	 */
	class TimelineSchedulerTaskProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'task';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_TASK_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_TASK_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_TASK_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M21.0404 21.3792C21.0404 21.7331 20.7503 22.0084 20.3772 22.0084H7.98426C7.61123 22.0084 7.32109 21.7331 7.32109 21.3792V9.62079C7.32109 9.26685 7.61123 8.99157 7.98426 8.99157H16.5226L18.8851 6.75H7.19675C5.99475 6.75 5 7.69382 5 8.83427V22.1657C5 23.3062 5.99475 24.25 7.19675 24.25H21.2476C22.4496 24.25 23.4444 23.3062 23.4444 22.1657V17.014L21.0819 19.2556V21.3792H21.0404ZM24.7707 9.62079L23.6931 8.59832C23.4029 8.32303 22.947 8.32303 22.6983 8.59832L15.1962 15.677L11.8389 12.4916C11.5488 12.2163 11.0929 12.2163 10.8442 12.4916L9.76653 13.514C9.47639 13.7893 9.47639 14.2219 9.76653 14.4579L14.7403 19.177C14.9061 19.3343 15.1548 19.4129 15.4035 19.3736C15.5278 19.3343 15.6522 19.2949 15.7765 19.177L24.8536 10.5646C25.0609 10.3287 25.0609 9.89607 24.7707 9.62079Z" fill="#767C87"/></svg>';
		}

		static getMenuPosition()
		{
			return 300;
		}

		static isSupported()
		{
			return Boolean(TaskCreate);
		}

		static open(data)
		{
			const { entity } = data.scheduler;

			TaskCreate.open({
				initialTaskData: {
					crm: {
						[`${entity.typeId}_${entity.id}`]: {
							id: entity.id,
							title: entity.title,
							type: Type.resolveNameById(entity.typeId).toLowerCase(),
						},
					},
				},
			});

			AnalyticsLabel.send({
				event: 'onTaskAdd',
				scenario: 'task_add',
			});
		}
	}

	module.exports = { TimelineSchedulerTaskProvider };
});

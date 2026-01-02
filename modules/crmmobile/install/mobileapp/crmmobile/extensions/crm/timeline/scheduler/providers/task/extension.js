/**
 * @module crm/timeline/scheduler/providers/task
 */
jn.define('crm/timeline/scheduler/providers/task', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { Type } = require('crm/type');
	const { AnalyticsEvent } = require('analytics');
	const { AnalyticsLabel } = require('analytics-label');
	const { get } = require('utils/object');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	let TasksEntry = null;
	let FeatureId = null;

	try
	{
		TasksEntry = require('tasks/entry').Entry;
		FeatureId = require('tasks/enum').FeatureId;

		setTimeout(() => tariffPlanRestrictionsReady(), 2000);
	}
	catch (e)
	{
		console.warn('Failed to load extensions from tasksmobile', e);
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
			if (FeatureId && getFeatureRestriction(FeatureId.CRM).isRestricted())
			{
				return Icon.LOCK;
			}

			return Icon.CIRCLE_CHECK;
		}

		static getDefaultPosition()
		{
			return 3;
		}

		static isAvailableInMenu(context = {})
		{
			if (!TasksEntry || !FeatureId)
			{
				return false;
			}

			if (!context.detailCard)
			{
				return false;
			}

			const detailCardParams = context.detailCard.getComponentParams();

			return get(detailCardParams, 'linkedUserFields.TASKS_TASK|UF_CRM_TASK', false);
		}

		static isSupported(context = {})
		{
			return true;
		}

		static async open(data)
		{
			if (!TasksEntry || !FeatureId)
			{
				return;
			}

			const { entity } = data.scheduler;
			const type = Type.resolveNameById(entity.typeId).toLowerCase();
			const analyticsEvent = new AnalyticsEvent({
				c_section: 'crm',
				c_sub_section: type,
				c_element: 'create_button',
			});

			const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.CRM);
			if (isRestricted())
			{
				showRestriction({ analyticsData: analyticsEvent });

				return;
			}

			TasksEntry.openTaskCreation({
				initialTaskData: {
					crm: [
						{
							id: entity.id,
							title: entity.title,
							type,
						},
					],
				},
				closeAfterSave: true,
				analyticsLabel: analyticsEvent.exportToObject(),
			});

			AnalyticsLabel.send({
				event: 'onTaskAdd',
				scenario: 'task_add',
			});
		}
	}

	module.exports = { TimelineSchedulerTaskProvider };
});

/**
 * @module tasks/entry
 */
jn.define('tasks/entry', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Feature } = require('feature');
	const { checkDisabledToolById } = require('settings/disabled-tools');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	/**
	 * @typedef {{id?: string|number, taskId?: string|number, title?: string, taskInfo?: object }} OpenTaskData
	 * @typedef {{
	 * 	taskObject?: object,
	 * 	userId?: number,
	 * 	parentWidget?: object,
	 * 	context?: 'tasks.dashboard'
	 * }} OpenTaskParams
	 */

	class Entry
	{
		static getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
			}

			return `${s4() + s4()}-${s4()}-${s4()}-${s4()}-${s4() + s4() + s4()}`;
		}

		static async checkToolAvailable(toolId, infoCode)
		{
			const toolDisabled = await checkDisabledToolById(toolId);
			if (toolDisabled)
			{
				const sliderUrl = await InfoHelper.getUrlByCode(infoCode);
				helpdesk.openHelp(sliderUrl);

				return false;
			}

			return true;
		}

		static async openEfficiency(data, params = {})
		{
			const efficiencyAvailable = await Entry.checkToolAvailable('effective', 'limit_tasks_off');
			if (!efficiencyAvailable)
			{
				return;
			}

			await tariffPlanRestrictionsReady();
			const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.EFFICIENCY);
			if (isRestricted())
			{
				showRestriction({ showInComponent: params.isBackground });

				return;
			}

			const { userId, groupId } = data;

			PageManager.openPage({
				url: `${env.siteDir}/mobile/tasks/snmrouter/?routePage=efficiency&USER_ID=${userId}&GROUP_ID=${groupId}`,
				titleParams: {
					text: Loc.getMessage('TASKSMOBILE_ENTRY_EFFICIENCY_TITLE'),
				},
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					mediumPositionHeight: 600,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				cache: false,
			});
		}

		/**
		 * @public
		 * @param {OpenTaskData} data
		 * @param {OpenTaskParams} params
		 * @return {void}
		 */
		static async openTask(data, params = {})
		{
			const taskAvailable = await Entry.checkToolAvailable('tasks', 'limit_tasks_off');
			if (!taskAvailable)
			{
				return;
			}

			if (Feature.isAirStyleSupported())
			{
				Entry.#openTaskDetailNew(data, params);
			}
			else
			{
				Entry.#openTaskDetailLegacy(data, params);
			}
		}

		/**
		 * @private
		 * @param {OpenTaskData} data
		 * @param {OpenTaskParams} params
		 * @return {void}
		 */
		static async #openTaskDetailNew(data, params = {})
		{
			const {
				userId = env.userId,
				parentWidget,
				context,
				analyticsLabel,
				shouldOpenComments = false,
				view,
				kanbanOwnerId,
			} = params;
			const taskId = data.id || data.taskId;
			const guid = Entry.getGuid();

			if (parentWidget)
			{
				const { TaskView } = await requireLazy('tasks:layout/task/view-new');

				TaskView.open({
					layoutWidget: parentWidget,
					userId,
					taskId,
					guid,
					context,
					analyticsLabel,
					shouldOpenComments,
					view,
					kanbanOwnerId,
				});
			}
			else
			{
				PageManager.openComponent('JSStackComponent', {
					name: 'JSStackComponent',
					componentCode: 'tasks.task.view-new',
					scriptPath: availableComponents['tasks:tasks.task.view-new'].publicUrl,
					canOpenInDefault: true,
					rootWidget: {
						name: 'layout',
						settings: {
							titleParams: {
								text: Loc.getMessage('TASKSMOBILE_ENTRY_TASK_DEFAULT_TITLE'),
								type: 'entity',
							},
							objectName: 'layout',
							swipeToClose: true,
						},
					},
					params: {
						COMPONENT_CODE: 'tasks.task.view-new',
						TASK_ID: taskId,
						USER_ID: (userId || env.userId),
						GUID: guid,
						CONTEXT: context,
						VIEW: view,
						SHOULD_OPEN_COMMENTS: shouldOpenComments,
						analyticsLabel,
						kanbanOwnerId,
					},
				});
			}
		}

		/**
		 * @private
		 * @param {OpenTaskData} data
		 * @param {OpenTaskParams} params
		 * @return {void}
		 */
		static #openTaskDetailLegacy(data, params = {})
		{
			const { taskObject, userId, parentWidget } = params;
			const taskId = data.id || data.taskId;
			const defaultTitle = Loc.getMessage('TASKSMOBILE_ENTRY_TASK_DEFAULT_TITLE');
			const guid = Entry.getGuid();

			if (Feature.isPreventBottomSheetDismissSupported())
			{
				PageManager.openComponent('JSStackComponent', {
					name: 'JSStackComponent',
					componentCode: 'tasks.task.view',
					scriptPath: availableComponents['tasks:tasks.task.view'].publicUrl,
					canOpenInDefault: true,
					rootWidget: {
						name: 'layout',
						settings: {
							objectName: 'layout',
							modal: true,
							backdrop: {
								mediumPositionPercent: 89,
								onlyMediumPosition: true,
								forceDismissOnSwipeDown: true,
								swipeAllowed: true,
								swipeContentAllowed: true,
								horizontalSwipeAllowed: false,
								hideNavigationBar: true,
								navigationBarColor: AppTheme.colors.bgSecondary,
							},
						},
					},
					params: {
						COMPONENT_CODE: 'tasks.task.view',
						TASK_ID: taskId,
						USER_ID: (userId || env.userId),
						TASK_OBJECT: taskObject,
						GUID: guid,
					},
				}, (parentWidget || null));

				return;
			}

			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'tasks.task.tabs',
				scriptPath: availableComponents['tasks:tasks.task.tabs'].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'tabs',
					settings: {
						objectName: 'tabs',
						modal: true,
						title: (taskObject?.title || data.title || data.taskInfo?.title || defaultTitle),
						grabTitle: false,
						grabButtons: true,
						grabSearch: false,
						tabs: Entry.getTaskTabs(taskId, guid, taskObject),
						leftButtons: [
							{
								svg: {
									content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
								},
								isCloseButton: true,
							},
						],
					},
				},
				params: {
					COMPONENT_CODE: 'tasks.task.tabs',
					TASK_ID: taskId,
					USER_ID: (userId || env.userId),
					TASK_OBJECT: taskObject,
					GUID: guid,
				},
			}, (parentWidget || null));
		}

		static getTaskTabs(taskId, guid, taskObject)
		{
			const tabCounterData = Entry.getTabCounterData(taskObject);

			return {
				items: [
					{
						id: 'tasks.task.view',
						title: Loc.getMessage('TASKSMOBILE_ENTRY_TASK_TABS_VIEW'),
						counter: tabCounterData.expired.value,
						label: (tabCounterData.expired.value > 0 ? String(tabCounterData.expired.value) : ''),
						style: {
							activeBadgeColor: tabCounterData.expired.color,
							inactiveBadgeColor: tabCounterData.expired.color,
						},
						widget: {
							name: 'layout',
							code: 'tasks.task.view',
							settings: {
								objectName: 'layout',
							},
						},
					},
					{
						id: 'tasks.task.comments',
						title: Loc.getMessage('TASKSMOBILE_ENTRY_TASK_TABS_COMMENTS'),
						counter: tabCounterData.newComments.value,
						label: (tabCounterData.newComments.value > 0 ? String(tabCounterData.newComments.value) : ''),
						style: {
							activeBadgeColor: tabCounterData.newComments.color,
							inactiveBadgeColor: tabCounterData.newComments.color,
						},
						widget: {
							name: 'web',
							code: 'tasks.task.comments',
							settings: {
								page: {
									url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=comments&TASK_ID=${taskId}&GUID=${guid}`,
									loading: {
										type: 'comments',
									},
									preload: true,
								},
							},
						},
					},
				],
			};
		}

		static getTabCounterData(task)
		{
			let expiredCounter = 0;
			let newCommentsCounter = 0;
			let expiredCounterColor = Task.counterColors.gray;
			let newCommentsCounterColor = Task.counterColors.gray;

			if (task)
			{
				expiredCounter = (!task.isCompletedCounts && !task.isDeferred && task.isExpired ? 1 : 0);
				newCommentsCounter = task._counter.value - expiredCounter;

				if (task.isMember && !task.isMuted)
				{
					expiredCounterColor = Task.counterColors.danger;
					newCommentsCounterColor = Task.counterColors.success;
				}
			}

			return {
				expired: {
					value: expiredCounter,
					color: expiredCounterColor,
				},
				newComments: {
					value: newCommentsCounter,
					color: newCommentsCounterColor,
				},
			};
		}

		static async openTaskList(data)
		{
			const tasksAvailable = await Entry.checkToolAvailable('tasks', 'limit_tasks_off');
			if (!tasksAvailable)
			{
				return;
			}

			const { siteId, siteDir, languageId, userId } = env;
			const extendedData = {
				...data,
				flowId: data.flowId || 0,
				flowName: data.flowName || null,
				flowEfficiency: data.flowEfficiency || null,
				canCreateTask: data.canCreateTask ?? true,
				groupId: data.groupId || 0,
				collabId: data.collabId || 0,
				ownerId: data.ownerId || userId,
				getProjectData: data.getProjectData || true,
				analyticsLabel: data.analyticsLabel || {},
			};

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks.dashboard',
				canOpenInDefault: true,
				title: (
					extendedData.collabId > 0
						? Loc.getMessage('TASKSMOBILE_ENTRY_COLLAB_TASK_LIST_TITLE')
						: (extendedData.groupName || Loc.getMessage('TASKSMOBILE_ENTRY_TASK_LIST_TITLE'))
				),
				scriptPath: availableComponents['tasks:tasks.dashboard'].publicUrl,
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						useSearch: true,
						useLargeTitleMode: true,
					},
				},
				params: {
					COMPONENT_CODE: 'tasks.dashboard',
					GROUP_ID: extendedData.groupId,
					COLLAB_ID: extendedData.collabId,
					USER_ID: extendedData.ownerId,
					FLOW_ID: extendedData.flowId,
					FLOW_NAME: extendedData.flowName,
					FLOW_EFFICIENCY: extendedData.flowEfficiency,
					CAN_CREATE_TASK: extendedData.canCreateTask,
					DATA: extendedData,
					SITE_ID: siteId,
					SITE_DIR: siteDir,
					LANGUAGE_ID: languageId,
					PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
					ANALYTICS_LABEL: extendedData.analyticsLabel,
				},
			});
		}
	}

	setTimeout(() => requireLazy('tasks:layout/task/view-new', false), 1000);

	if (typeof jnComponent?.preload === 'function')
	{
		const componentCode = Feature.isAirStyleSupported() ? 'tasks:tasks.task.view-new' : 'tasks:tasks.task.view';

		const { publicUrl } = availableComponents[componentCode] || {};

		if (publicUrl)
		{
			setTimeout(() => jnComponent.preload(publicUrl), 3000);
		}
	}

	module.exports = { Entry };
});

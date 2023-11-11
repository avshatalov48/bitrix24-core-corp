/**
 * @module tasks/entry
 */
jn.define('tasks/entry', (require, exports, module) => {
	const { Loc } = require('loc');

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

		static openEfficiency(data)
		{
			const { userId, groupId } = data;

			PageManager.openPage({
				url: `/mobile/tasks/snmrouter/?routePage=efficiency&USER_ID=${userId}&GROUP_ID=${groupId}`,
				titleParams: {
					text: Loc.getMessage('TASKSMOBILE_ENTRY_EFFICIENCY_TITLE'),
				},
				backdrop: {
					mediumPositionHeight: 600,
				},
				cache: false,
			});
		}

		static openTask(data, params = {})
		{
			const { taskObject, userId, parentWidget } = params;
			const taskId = data.id || data.taskId;
			const defaultTitle = Loc.getMessage('TASKSMOBILE_ENTRY_TASK_DEFAULT_TITLE');
			const guid = Entry.getGuid();

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
						leftButtons: [{
							svg: {
								content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
							},
							isCloseButton: true,
						}],
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

		static openTaskList(data)
		{
			const { siteId, siteDir, languageId, userId } = env;
			const extendedData = {
				...data,
				groupId: data.groupId || 0,
				ownerId: data.ownerId || userId,
				getProjectData: data.getProjectData || true,
			};

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks.list',
				scriptPath: availableComponents['tasks:tasks.list'].publicUrl,
				canOpenInDefault: true,
				title: (extendedData.groupName || ''),
				rootWidget: {
					name: 'tasks.list',
					settings: {

						objectName: 'list',
						useSearch: true,
						useLargeTitleMode: true,
						emptyListMode: true
						,
					},
				},
				params: {
					COMPONENT_CODE: 'tasks.list',
					GROUP_ID: extendedData.groupId,
					USER_ID: extendedData.ownerId,
					DATA: extendedData,
					SITE_ID: siteId,
					SITE_DIR: siteDir,
					LANGUAGE_ID: languageId,
					PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
				},
			});
		}
	}

	module.exports = { Entry };
});

(function()
{
	class TaskBackgroundAction
	{
		constructor()
		{
			BX.addCustomEvent(
				"taskbackground::task::action",
				(data, taskId, params = {}, extra = false, delay = null) => {
					TaskBackgroundAction.executeAction(data, taskId, params, extra = false, delay = null);
				}
			);
		}

		static executeAction(data, taskId, params, extra = false, delay = null)
		{
			console.info('TaskBackgroundAction.executeAction', data);

			const currentTaskId = taskId || data.taskId;
			const {groupId} = data;

			if (currentTaskId)
			{
				if (Application.getApiVersion() >= 31)
				{
					TaskBackgroundAction.openTaskComponentByTaskId(currentTaskId, data, params);
				}
				else
				{
					TaskBackgroundAction.loadTaskPageByTaskId(currentTaskId, data);
				}
			}

			if (groupId && Application.getApiVersion() >= 31)
			{
				TaskBackgroundAction.openTaskListComponentByGroupId(groupId, {});
			}
		}

		static loadTaskPageByTaskId(taskId, data)
		{
			PageManager.openPage({url: TaskBackgroundAction.makeComponentTaskUrl(taskId)});
		}

		static openTaskListComponentByGroupId(groupId, data)
		{
			console.log('openTaskListComponentByGroupId');

			const {siteDir, siteId, languageId} = env;
			const componentData = data || {};

			componentData.params = {
				COMPONENT_CODE: 'tasks.list',
				GROUP_ID: groupId,
				SITE_ID: siteId,
				LANGUAGE_ID: languageId,
				SITE_DIR: siteDir,
				PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
			};
			componentData.path = availableComponents['tasks.list']['publicUrl'];
			componentData.canOpenInDefault = true;

			TaskView.open(componentData);
		}

		static openTaskComponentByTaskId(taskId, data, params)
		{
			const guid = TaskBackgroundAction.getGuid();
			const taskData = data || {};
			const componentCode = 'tasks.view';
			const defaultTitle = BX.message('MOBILE_TASKS_TASK_CARD_TOP_BAR_DEFAULT_TITLE');
			const {taskInfo, publicUrl} = taskData;
			const {userId, getTaskInfo} = params;
			const {siteDir, languageId} = env;
			const param = {
				name: 'JSStackComponent',
				componentCode,
				canOpenInDefault: true,
				scriptPath: publicUrl || availableComponents['tasks.view'].publicUrl,
				rootWidget: {
					name: 'taskcard',
					settings: {
						objectName: 'taskcard',
						title: defaultTitle,
						taskInfo,
						page: {
							url: `${siteDir}mobile/tasks/snmrouter/?routePage=view&TASK_ID=${taskId}&NEW_CARD=Y&GUID=${guid}`,
							titleParams: {text: defaultTitle},
							autoHideLoading: false,
						},
					},
				},
				params: {
					MODE: 'view',
					COMPONENT_CODE: componentCode,
					USER_ID: userId || 0,
					TASK_ID: taskId,
					FORM_ID: 'MOBILE_TASK_VIEW',
					LANGUAGE_ID: languageId,
					GUID: guid,
					GET_TASK_INFO: getTaskInfo || false,
				},
			};

			PageManager.openComponent('JSStackComponent', param);
			console.log({title: 'PageManager.openComponent', param});
		}

		static makeComponentTaskUrl(taskId, action = 'view', tabId = 'taskTab', messageId = 0)
		{
			return `/mobile/tasks/snmrouter/?routePage=${action}&TASK_ID=${taskId}&MID=${messageId}&tabId=${tabId}`;
		}

		static getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			return `${s4() + s4()}-${s4()}-${s4()}-${s4()}-${s4() + s4() + s4()}`;
		}
	}

	this.TaskBackgroundAction = new TaskBackgroundAction();
})();
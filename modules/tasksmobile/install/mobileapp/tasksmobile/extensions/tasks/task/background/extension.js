(function()
{
	class TaskBackgroundAction
	{
		constructor()
		{
			BX.addCustomEvent(
				'taskbackground::task::action',
				(data, taskId, params = {}, extra = false, delay = null) => {
					TaskBackgroundAction.executeAction(data, taskId, params, extra, delay);
				}
			);
			BX.addCustomEvent('taskbackground::efficiency::open', (eventData) => {
				TaskBackgroundAction.openEfficiencyPage(eventData);
			});
		}

		static openEfficiencyPage(eventData)
		{
			const {userId, groupId} = eventData;

			PageManager.openPage({
				url: `/mobile/tasks/snmrouter/?routePage=efficiency&USER_ID=${userId}&GROUP_ID=${groupId}`,
				titleParams: {
					text: BX.message('MOBILE_TASKS_BACKGROUND_EFFICIENCY_PAGE_TITLE'),
				},
				backdrop: {
					mediumPositionHeight: 600,
				},
				cache: false,
			});
		}

		static executeAction(data, taskId, params, extra = false, delay = null)
		{
			console.info('TaskBackgroundAction.executeAction', data);

			const currentTaskId = (taskId || data.taskId);
			if (currentTaskId)
			{
				TaskBackgroundAction.openTask(currentTaskId, data, params);
			}
			else
			{
				TaskBackgroundAction.openTaskList(data, params);
			}
		}

		static openTask(taskId, data, params)
		{
			if (Application.getApiVersion() >= 31)
			{
				TaskBackgroundAction.openTaskComponentByTaskId(taskId, data, params);
			}
			else
			{
				TaskBackgroundAction.loadTaskPageByTaskId(taskId, data);
			}
		}

		static openTaskList(data, params)
		{
			if (Application.getApiVersion() < 31)
			{
				return;
			}

			const {groupId, ownerId} = data;
			if (groupId)
			{
				TaskBackgroundAction.openTaskListComponentByGroupId(groupId, data);
			}
			else if (ownerId)
			{
				TaskBackgroundAction.openTaskListComponentByOwnerId(ownerId, data);
			}
		}

		static loadTaskPageByTaskId(taskId, data)
		{
			PageManager.openPage({url: TaskBackgroundAction.makeComponentTaskUrl(taskId)});
		}

		static openTaskListComponentByGroupId(groupId, data)
		{
			console.log('openTaskListComponentByGroupId');

			const {siteId, siteDir, languageId, userId} = env;

			data.ownerId = (data.ownerId || userId);
			data.getProjectData = (data.getProjectData || true);

			const componentData = {
				path: availableComponents['tasks:tasks.list'].publicUrl,
				title: (data.groupName || ''),
				canOpenInDefault: true,
				params: {
					COMPONENT_CODE: 'tasks.list',
					GROUP_ID: groupId,
					USER_ID: data.ownerId,
					DATA: data,
					SITE_ID: siteId,
					SITE_DIR: siteDir,
					LANGUAGE_ID: languageId,
					PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
				},
			};

			TaskBackgroundAction.openTaskListComponent(componentData);
		}

		static openTaskListComponentByOwnerId(ownerId, data)
		{
			console.log('openTaskListComponentByOwnerId');

			const {siteDir, siteId, languageId} = env;
			const componentData = {
				path: availableComponents['tasks:tasks.list'].publicUrl,
				canOpenInDefault: true,
				params: {
					COMPONENT_CODE: 'tasks.list',
					USER_ID: ownerId,
					DATA: data,
					SITE_ID: siteId,
					LANGUAGE_ID: languageId,
					SITE_DIR: siteDir,
					PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
				},
			};

			TaskBackgroundAction.openTaskListComponent(componentData);
		}

		static getInputPanelParams(userId)
		{
			if (Application.getApiVersion() >= 40)
			{
				return {
					inputPanel: {
						action: 0,
						callback: 0,
						useImageButton: true,
						useAudioMessages: true,
						smileButton: [],
						message: {
							placeholder: BX.message('MOBILE_TASKS_BACKGROUND_INPUT_PANEL_PLACEHOLDER'),
						},
						attachButton: {
							items: [
								{
									id: 'disk',
									name: BX.message('MOBILE_TASKS_BACKGROUND_INPUT_PANEL_B24_DISK'),
									dataSource: {
										multiple: true,
										url: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${userId}`,
									},
								},
							],
						},
						attachFileSettings: {
							resize: {
								targetWidth: -1,
								targetHeight: -1,
								sourceType: 1,
								encodingType: 0,
								mediaType: 2,
								allowsEdit: false,
								saveToPhotoAlbum: true,
								cameraDirection: 0,
							},
							maxAttachedFilesCount: 100,
						},
					},
				};
			}

			return {};
		}

		static openTaskListComponent(data)
		{
			const settings = {
				...{
					objectName: 'list',
					useSearch: true,
					useLargeTitleMode: true,
					emptyListMode: true,
				},
				...TaskBackgroundAction.getInputPanelParams(data.params.DATA.ownerId),
			};

			PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: (data.canOpenInDefault || false),
				scriptPath: data.path,
				componentCode: 'tasks.list',
				params: data.params,
				title: (data.title || ''),
				rootWidget: {
					settings,
					name: 'tasks.list',
				},
			});
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
				scriptPath: publicUrl || availableComponents['tasks:tasks.view'].publicUrl,
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
					USER_ID: userId || env.userId,
					TASK_ID: taskId,
					FORM_ID: 'MOBILE_TASK_VIEW',
					LANGUAGE_ID: languageId,
					GUID: guid,
					GET_TASK_INFO: getTaskInfo || false,
					TASK_OBJECT: params.taskObject,
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
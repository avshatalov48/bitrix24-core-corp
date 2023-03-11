jn.define('tasks/entry', (require, exports, module) => {
	const {Loc} = require('loc');

	const apiVersion = Application.getApiVersion();

	class Entry
	{
		openEfficiency(data)
		{
			const {userId, groupId} = data;

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

		openTask(data, params)
		{
			if (apiVersion >= 45)
			{
				this.openTaskTabsComponent(data, params);
			}
			else if (apiVersion >= 31)
			{
				this.openTaskComponent(data, params);
			}
			else
			{
				PageManager.openPage({url: `/mobile/tasks/snmrouter/?routePage=view&TASK_ID=${data.taskId}`});
			}
		}

		openTaskTabsComponent(data, params)
		{
			const {taskId} = data;
			const {taskObject, userId, parentWidget} = params;
			const defaultTitle = Loc.getMessage('TASKSMOBILE_ENTRY_TASK_DEFAULT_TITLE');
			const guid = this.getGuid();

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
						title: (taskObject ? taskObject.title : (data.taskInfo ? data.taskInfo.title : defaultTitle)),
						grabTitle: false,
						grabButtons: true,
						grabSearch: false,
						tabs: this.getTaskTabs(taskId, guid, taskObject),
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

		getTaskTabs(taskId, guid, taskObject)
		{
			const tabCounterData = this.getTabCounterData(taskObject);

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

		getTabCounterData(task)
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

		openTaskComponent(data, params)
		{
			const {taskId, taskInfo} = data;
			const {userId, taskObject, getTaskInfo} = params;
			const defaultTitle = Loc.getMessage('TASKSMOBILE_ENTRY_TASK_DEFAULT_TITLE');
			const guid = this.getGuid();

			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'tasks.view',
				scriptPath: availableComponents['tasks:tasks.view'].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'taskcard',
					settings: {
						objectName: 'taskcard',
						title: defaultTitle,
						taskInfo,
						page: {
							url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=view&TASK_ID=${taskId}&NEW_CARD=Y&GUID=${guid}`,
							titleParams: {
								text: defaultTitle,
							},
							autoHideLoading: false,
						},
					},
				},
				params: {
					COMPONENT_CODE: 'tasks.view',
					MODE: 'view',
					TASK_ID: taskId,
					USER_ID: (userId || env.userId),
					FORM_ID: 'MOBILE_TASK_VIEW',
					LANGUAGE_ID: env.languageId,
					GUID: guid,
					GET_TASK_INFO: (getTaskInfo || false),
					TASK_OBJECT: taskObject,
				},
			});
		}

		openTaskList(data)
		{
			if (apiVersion < 31)
			{
				return;
			}

			const {siteId, siteDir, languageId, userId} = env;

			data.groupId = (data.groupId || 0);
			data.ownerId = (data.ownerId || userId);
			data.getProjectData = (data.getProjectData || true);

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks.list',
				scriptPath: availableComponents['tasks:tasks.list'].publicUrl,
				canOpenInDefault: true,
				title: (data.groupName || ''),
				rootWidget: {
					name: 'tasks.list',
					settings: {
						...{
							objectName: 'list',
							useSearch: true,
							useLargeTitleMode: true,
							emptyListMode: true,
						},
						...this.getInputPanelParams(data.ownerId),
					},
				},
				params: {
					COMPONENT_CODE: 'tasks.list',
					GROUP_ID: data.groupId,
					USER_ID: data.ownerId,
					DATA: data,
					SITE_ID: siteId,
					SITE_DIR: siteDir,
					LANGUAGE_ID: languageId,
					PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
				},
			});
		}

		getInputPanelParams(userId)
		{
			if (apiVersion >= 40)
			{
				return {
					inputPanel: {
						action: 0,
						callback: 0,
						useImageButton: true,
						useAudioMessages: true,
						smileButton: [],
						message: {
							placeholder: Loc.getMessage('TASKSMOBILE_ENTRY_TASK_LIST_INPUT_PANEL_PLACEHOLDER'),
						},
						attachButton: {
							items: [
								{
									id: 'disk',
									name: Loc.getMessage('TASKSMOBILE_ENTRY_TASK_LIST_INPUT_PANEL_B24_DISK'),
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

		getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			return `${s4() + s4()}-${s4()}-${s4()}-${s4()}-${s4() + s4() + s4()}`;
		}
	}

	module.exports = {Entry};
});
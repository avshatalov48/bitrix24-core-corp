/**
 * @module tasks/entry
 */
jn.define('tasks/entry', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { checkDisabledToolById } = require('settings/disabled-tools');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	/**
	 * @typedef {object} OpenTaskData
	 * @property {string|number} [id]
	 * @property {string|number} [taskId]
	 */

	/**
	 * @typedef {object} OpenTaskParams
	 * @property {number} [userId=env.userId]
	 * @property {PageManager} [parentWidget]
	 * @property {object} [analyticsLabel]
	 * @property {boolean} [shouldOpenComments=false]
	 * @property {string} [view]
	 * @property {number} [kanbanOwnerId]
	 */

	/**
	 * @typedef {object} TaskCreationDataGroupDto
	 * @property {number} id
	 * @property {string} name
	 * @property {string} image
	 * @property {object} additionalData
	 */

	/**
	 * @typedef {object} TaskCreationDataUserDto
	 * @property {number} id
	 * @property {string} name
	 * @property {string?} image
	 * @property {string?} link
	 * @property {string?} workPosition
	 */

	/**
	 * @typedef {object} TaskCreationDataFileDto
	 * @property {string} id
	 * @property {string} name
	 * @property {string} type
	 * @property {string} url
	 */

	/**
	 * @typedef {object} TaskCreationDataTagDto
	 * @property {string} id
	 * @property {string} name
	 */

	/**
	 * @typedef {object} TaskCreationDataCrmElementDto
	 * @property {string} id
	 * @property {string} title
	 * @property {string} subtitle
	 * @property {string} type
	 * @property {boolean} hidden
	 */

	/**
	 * @typedef {object} OpenTaskCreationData
	 * @property {object} [initialTaskData]
	 * @property {string} [initialTaskData.guid]
	 * @property {string} [initialTaskData.title]
	 * @property {string} [initialTaskData.description]
	 * @property {Date} [initialTaskData.deadline]
	 * @property {number} [initialTaskData.groupId]
	 * @property {TaskCreationDataGroupDto} [initialTaskData.group]
	 * @property {number} [initialTaskData.flowId]
	 * @property {number} [initialTaskData.priority] - one of values from {@link tasks/enum.TaskPriority}
	 * @property {number} [initialTaskData.parentId]
	 * @property {number} [initialTaskData.relatedTaskId]
	 * @property {TaskCreationDataUserDto} [initialTaskData.responsible]
	 * @property {TaskCreationDataUserDto[]} [initialTaskData.accomplices]
	 * @property {TaskCreationDataUserDto[]} [initialTaskData.auditors]
	 * @property {TaskCreationDataFileDto[]} [initialTaskData.files]
	 * @property {TaskCreationDataTagDto[]} [initialTaskData.tags]
	 * @property {TaskCreationDataCrmElementDto[]} [initialTaskData.crm]
	 * @property {boolean} [initialTaskData.allowTimeTracking]
	 * @property {number} [initialTaskData.startDatePlan]
	 * @property {number} [initialTaskData.endDatePlan]
	 * @property {number} [initialTaskData.IM_CHAT_ID]
	 * @property {number} [initialTaskData.IM_MESSAGE_ID]
	 * @property {string} [view] - one of values from {@link tasks/enum.ViewMode}
	 * @property {object} [stage]
	 * @property {number} [copyId]
	 * @property {string} [context]
	 * @property {boolean} [closeAfterSave]
	 * @property {object} [analyticsLabel]
	 * @property {PageManager} [layoutWidget]
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
				enableNavigationBarBorder: false,
				backgroundColor: Color.bgSecondary.toHex(),
				backdrop: {
					mediumPositionHeight: 530,
					navigationBarColor: Color.bgSecondary.toHex(),
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
			const isTaskToolAvailable = await Entry.checkToolAvailable('tasks', 'limit_tasks_off');
			if (!isTaskToolAvailable)
			{
				return;
			}

			const {
				userId = Number(env.userId),
				parentWidget,
				analyticsLabel,
				shouldOpenComments = false,
				view,
				kanbanOwnerId,
				projectId = null,
			} = params;
			const taskId = data.id || data.taskId;
			const guid = Entry.getGuid();
			const isFlowToolDisabled = await checkDisabledToolById('flows', false);

			if (parentWidget)
			{
				const { TaskView } = await requireLazy('tasks:layout/task/view-new');

				TaskView.open({
					layoutWidget: parentWidget,
					userId,
					taskId,
					guid,
					analyticsLabel,
					shouldOpenComments,
					view,
					kanbanOwnerId,
					projectId,
					isFlowToolDisabled,
				});
			}
			else
			{
				PageManager.openComponent('JSStackComponent', {
					name: 'JSStackComponent',
					componentCode: 'tasks.task.view-new',
					// eslint-disable-next-line no-undef
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
						VIEW: view,
						SHOULD_OPEN_COMMENTS: shouldOpenComments,
						analyticsLabel,
						kanbanOwnerId,
						IS_FLOW_TOOL_DISABLED: isFlowToolDisabled,
					},
				});
			}
		}

		/**
		 * @public
		 * @param {OpenTaskCreationData} data
		 * @return {void}
		 */
		static async openTaskCreation(data)
		{
			const isTaskToolAvailable = await Entry.checkToolAvailable('tasks', 'limit_tasks_off');
			if (!isTaskToolAvailable)
			{
				return;
			}

			const isFlowToolDisabled = await checkDisabledToolById('flows', false);

			const { CreateNew } = await requireLazy('tasks:layout/task/create-new');
			CreateNew.open({ ...data, isFlowToolDisabled });
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
				// eslint-disable-next-line no-undef
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
		// eslint-disable-next-line no-undef
		const { publicUrl } = availableComponents['tasks:tasks.task.view-new'] || {};

		if (publicUrl)
		{
			setTimeout(() => jnComponent.preload(publicUrl), 3000);
		}
	}

	module.exports = { Entry };
});

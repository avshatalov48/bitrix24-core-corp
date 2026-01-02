/**
 * @module tasks/statemanager/redux/slices/tasks/model/task
 */
jn.define('tasks/statemanager/redux/slices/tasks/model/task', (require, exports, module) => {
	const { ExpirationRegistry } = require('tasks/statemanager/redux/slices/tasks/expiration-registry');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');
	const { selectIsExpired } = require('tasks/statemanager/redux/slices/tasks/selector');
	const { TaskStatus, TaskPriority } = require('tasks/enum');
	const { Type } = require('type');

	class TaskModel
	{
		/**
		 * Method maps fields from API responses of "tasksmobile" module to redux store.
		 *
		 * @public
		 * @param {object} sourceServerTask
		 * @param {object|null} existingReduxTask
		 * @param {{ownerId: Number}} context
		 * @return {object}
		 */
		static prepareReduxTaskFromServerTask(
			sourceServerTask,
			existingReduxTask = null,
			context = {},
		)
		{
			const preparedTask = { ...TaskModel.getEmptyReduxTask(), ...existingReduxTask };
			const serverTask = FieldChangeRegistry.removeChangedFields(sourceServerTask.id, sourceServerTask);
			let taskId = Number(existingReduxTask?.id);

			if (!Type.isUndefined(serverTask.id))
			{
				taskId = Number(serverTask.id);
				preparedTask.id = taskId;
			}

			if (!Type.isUndefined(serverTask.name))
			{
				preparedTask.name = serverTask.name;
			}

			if (!Type.isUndefined(serverTask.description))
			{
				preparedTask.description = serverTask.description;
			}

			if (!Type.isUndefined(serverTask.parsedDescription))
			{
				preparedTask.parsedDescription = serverTask.parsedDescription;
			}

			if (!Type.isUndefined(serverTask.groupId))
			{
				preparedTask.groupId = Number(serverTask.groupId);
			}

			if (!Type.isUndefined(serverTask.flowId))
			{
				preparedTask.flowId = Number(serverTask.flowId);
			}

			if (!Type.isUndefined(serverTask.timeElapsed))
			{
				preparedTask.timeElapsed = Number(serverTask.timeElapsed);
			}

			if (!Type.isUndefined(serverTask.timeEstimate))
			{
				preparedTask.timeEstimate = Number(serverTask.timeEstimate);
			}

			if (!Type.isUndefined(serverTask.commentsCount))
			{
				preparedTask.commentsCount = Number(serverTask.commentsCount);
			}

			if (!Type.isUndefined(serverTask.serviceCommentsCount))
			{
				preparedTask.serviceCommentsCount = Number(serverTask.serviceCommentsCount);
			}

			if (!Type.isUndefined(serverTask.newCommentsCount))
			{
				preparedTask.newCommentsCount = Number(serverTask.newCommentsCount);
			}

			if (!Type.isUndefined(serverTask.resultsCount))
			{
				preparedTask.resultsCount = Number(serverTask.resultsCount);
			}

			if (!Type.isUndefined(serverTask.viewsCount))
			{
				preparedTask.viewsCount = Number(serverTask.viewsCount);
			}

			if (!Type.isUndefined(serverTask.parentId))
			{
				preparedTask.parentId = Number(serverTask.parentId);
			}

			if (!Type.isUndefined(serverTask.status))
			{
				preparedTask.status = Number(serverTask.status);
			}

			if (!Type.isUndefined(serverTask.subStatus))
			{
				preparedTask.subStatus = Number(serverTask.subStatus);
			}

			if (!Type.isUndefined(serverTask.priority))
			{
				preparedTask.priority = Number(serverTask.priority);
			}

			if (!Type.isUndefined(serverTask.mark))
			{
				preparedTask.mark = serverTask.mark;
			}

			if (!Type.isUndefined(serverTask.creator))
			{
				preparedTask.creator = Number(serverTask.creator);
			}

			if (!Type.isUndefined(serverTask.responsible))
			{
				preparedTask.responsible = Number(serverTask.responsible);
			}

			if (!Type.isUndefined(serverTask.accomplices))
			{
				preparedTask.accomplices = serverTask.accomplices.map((userId) => Number(userId));
			}

			if (!Type.isUndefined(serverTask.auditors))
			{
				preparedTask.auditors = serverTask.auditors.map((userId) => Number(userId));
			}

			if (!Type.isUndefined(serverTask.relatedTasks))
			{
				preparedTask.relatedTasks = (Type.isArray(serverTask.relatedTasks) ? [] : serverTask.relatedTasks);
			}

			// if (!Type.isUndefined(serverTask.subTasks))
			// {
			// 	preparedTask.subTasks = (Type.isArray(serverTask.subTasks) ? [] : serverTask.subTasks);
			// }

			if (!Type.isUndefined(serverTask.crm))
			{
				preparedTask.crm = serverTask.crm;
			}

			if (!Type.isUndefined(serverTask.tags))
			{
				preparedTask.tags = serverTask.tags;
			}

			if (!Type.isUndefined(serverTask.files))
			{
				preparedTask.files = serverTask.files;
			}

			if (!Type.isUndefined(serverTask.isMuted))
			{
				preparedTask.isMuted = serverTask.isMuted;
			}

			if (!Type.isUndefined(serverTask.isDodNecessary))
			{
				preparedTask.isDodNecessary = serverTask.isDodNecessary;
			}

			if (!Type.isUndefined(serverTask.isPinned))
			{
				const { ownerId = env.userId } = context;
				preparedTask.isPinned = {
					...preparedTask.isPinned,
					[ownerId]: serverTask.isPinned,
				};
			}

			if (!Type.isUndefined(serverTask.isPinnedInGroup))
			{
				preparedTask.isPinnedInGroup = serverTask.isPinnedInGroup;
			}

			if (!Type.isUndefined(serverTask.isInFavorites))
			{
				preparedTask.isInFavorites = serverTask.isInFavorites;
			}

			if (!Type.isUndefined(serverTask.isResultRequired))
			{
				preparedTask.isResultRequired = serverTask.isResultRequired;
			}

			if (!Type.isUndefined(serverTask.isResultExists))
			{
				preparedTask.isResultExists = serverTask.isResultExists;
			}

			if (!Type.isUndefined(serverTask.isOpenResultExists))
			{
				preparedTask.isOpenResultExists = serverTask.isOpenResultExists;
			}

			if (!Type.isUndefined(serverTask.isMatchWorkTime))
			{
				preparedTask.isMatchWorkTime = serverTask.isMatchWorkTime;
			}

			if (!Type.isUndefined(serverTask.allowChangeDeadline))
			{
				preparedTask.allowChangeDeadline = serverTask.allowChangeDeadline;
			}

			if (!Type.isUndefined(serverTask.allowTimeTracking))
			{
				preparedTask.allowTimeTracking = serverTask.allowTimeTracking;
			}

			if (!Type.isUndefined(serverTask.allowTaskControl))
			{
				preparedTask.allowTaskControl = serverTask.allowTaskControl;
			}

			if (!Type.isUndefined(serverTask.isTimerRunningForCurrentUser))
			{
				preparedTask.isTimerRunningForCurrentUser = serverTask.isTimerRunningForCurrentUser;
			}

			let isDeadlineChanged = false;
			if (!Type.isUndefined(serverTask.deadline))
			{
				const oldDeadline = preparedTask.deadline;
				const newDeadline = serverTask.deadline;

				preparedTask.deadline = newDeadline;
				isDeadlineChanged = (newDeadline !== oldDeadline);
			}

			if (!Type.isUndefined(serverTask.activityDate))
			{
				preparedTask.activityDate = serverTask.activityDate;
			}

			if (!Type.isUndefined(serverTask.updateDate))
			{
				preparedTask.updateDate = serverTask.updateDate;
			}

			if (!Type.isUndefined(serverTask.startDatePlan))
			{
				preparedTask.startDatePlan = serverTask.startDatePlan;
			}

			if (!Type.isUndefined(serverTask.endDatePlan))
			{
				preparedTask.endDatePlan = serverTask.endDatePlan;
			}

			if (!Type.isUndefined(serverTask.startDate))
			{
				preparedTask.startDate = serverTask.startDate;
			}

			if (!Type.isUndefined(serverTask.endDate))
			{
				preparedTask.endDate = serverTask.endDate;
			}

			if (!Type.isUndefined(serverTask.checklist))
			{
				preparedTask.checklist = serverTask.checklist;
			}

			if (!Type.isUndefined(serverTask.checklistDetails))
			{
				preparedTask.checklistDetails = serverTask.checklistDetails;
			}

			if (!Type.isUndefined(serverTask.dodTypes))
			{
				preparedTask.dodTypes = serverTask.dodTypes;
			}

			if (!Type.isUndefined(serverTask.activeDodTypeId))
			{
				preparedTask.activeDodTypeId = serverTask.activeDodTypeId;
			}

			if (!Type.isUndefined(serverTask.counter))
			{
				preparedTask.counter = serverTask.counter;
			}

			if (!Type.isUndefined(serverTask.shouldShowKanbanStages))
			{
				preparedTask.shouldShowKanbanStages = serverTask.shouldShowKanbanStages;
			}

			if (!Type.isUndefined(serverTask.actions))
			{
				const actions = FieldChangeRegistry.removeChangedFields(preparedTask.id, serverTask.actions);

				preparedTask.canRead = actions.canRead ?? preparedTask.canRead;
				preparedTask.canUpdate = actions.canUpdate ?? preparedTask.canUpdate;
				preparedTask.canUpdateDeadline = actions.canUpdateDeadline ?? preparedTask.canUpdateDeadline;
				preparedTask.canUpdateCreator = actions.canUpdateCreator ?? preparedTask.canUpdateCreator;
				preparedTask.canUpdateResponsible = actions.canUpdateResponsible ?? preparedTask.canUpdateResponsible;
				preparedTask.canUpdateAccomplices = actions.canUpdateAccomplices ?? preparedTask.canUpdateAccomplices;
				preparedTask.canDelegate = actions.canDelegate ?? preparedTask.canDelegate;
				preparedTask.canTake = actions.canTake ?? preparedTask.canTake;
				preparedTask.canUpdateMark = actions.canUpdateMark ?? preparedTask.canUpdateMark;
				preparedTask.canUpdateReminder = actions.canUpdateReminder ?? preparedTask.canUpdateReminder;
				preparedTask.canUpdateElapsedTime = actions.canUpdateElapsedTime ?? preparedTask.canUpdateElapsedTime;
				preparedTask.canAddChecklist = actions.canAddChecklist ?? preparedTask.canAddChecklist;
				preparedTask.canUpdateChecklist = actions.canUpdateChecklist ?? preparedTask.canUpdateChecklist;
				preparedTask.canRemove = actions.canRemove ?? preparedTask.canRemove;
				preparedTask.canUseTimer = actions.canUseTimer ?? preparedTask.canUseTimer;
				preparedTask.canStart = (actions.canStart ?? preparedTask.canStart) && !preparedTask.canUseTimer;
				preparedTask.canPause = (actions.canPause ?? preparedTask.canPause) && !preparedTask.canUseTimer;
				preparedTask.canComplete = actions.canComplete ?? preparedTask.canComplete;
				preparedTask.canRenew = actions.canRenew ?? preparedTask.canRenew;
				preparedTask.canApprove = actions.canApprove ?? preparedTask.canApprove;
				preparedTask.canDisapprove = actions.canDisapprove ?? preparedTask.canDisapprove;
				preparedTask.canDefer = actions.canDefer ?? preparedTask.canDefer;
				preparedTask.canCreate = actions.canCreate ?? preparedTask.canCreate;
			}

			const userFieldNames = serverTask.userFieldNames;

			Object.keys(preparedTask).forEach((key) => {
				if (key.startsWith('UF_AUTO_') && !userFieldNames.includes(key))
				{
					delete preparedTask[key];
				}
			});
			preparedTask.userFieldNames = userFieldNames;

			if (serverTask.areUserFieldsLoaded && serverTask.userFields)
			{
				preparedTask.areUserFieldsLoaded = true;

				serverTask.userFields.forEach((field) => {
					preparedTask[field.fieldName] = field;
				});
			}

			preparedTask.isExpired = selectIsExpired(preparedTask);
			preparedTask.isConsideredForCounterChange = false;
			preparedTask.isCreationErrorExist = false;

			const uploadedFiles = Array.isArray(preparedTask.uploadedFiles) ? preparedTask.uploadedFiles : [];

			preparedTask.uploadedFiles = uploadedFiles.filter((file) => {
				return file.isUploading || !Number.isInteger(Number(file.objectId));
			});

			if (!existingReduxTask || isDeadlineChanged)
			{
				ExpirationRegistry.handleDeadlineTimerForTask(preparedTask);
			}

			return preparedTask;
		}

		/**
		 *
		 * @returns {TaskReduxModel}
		 */
		static getEmptyReduxTask()
		{
			return {
				id: `tmp-id-${Date.now()}`,
				guid: undefined,
				name: undefined,
				description: undefined,
				parsedDescription: undefined,
				groupId: undefined,
				timeElapsed: undefined,
				timeEstimate: undefined,
				commentsCount: undefined,
				serviceCommentsCount: undefined,
				newCommentsCount: 0,
				resultsCount: 0,
				parentId: undefined,

				status: undefined,
				subStatus: undefined,
				priority: undefined,
				mark: undefined,

				creator: undefined,
				responsible: undefined,
				accomplices: undefined,
				auditors: undefined,
				relatedTasks: [],
				// subTasks: undefined,

				crm: undefined,
				tags: undefined,
				files: undefined,
				uploadedFiles: [],
				userFieldNames: undefined,

				isMuted: undefined,
				isPinned: {},
				isPinnedInGroup: undefined,
				isInFavorites: undefined,
				isResultRequired: undefined,
				isResultExists: undefined,
				isOpenResultExists: undefined,
				isMatchWorkTime: undefined,
				allowChangeDeadline: undefined,
				allowTimeTracking: undefined,
				allowTaskControl: undefined,
				isTimerRunningForCurrentUser: undefined,
				areUserFieldsLoaded: undefined,

				deadline: undefined,
				activityDate: undefined,
				updateDate: undefined,
				startDatePlan: undefined,
				endDatePlan: undefined,
				startDate: undefined,
				endDate: undefined,

				checklist: {
					completed: 0,
					uncompleted: 0,
				},
				checklistDetails: [],
				counter: {
					counters: {
						expired: 0,
						newComments: 0,
						mutedExpired: 0,
						mutedNewComments: 0,
						projectExpired: 0,
						projectNewComments: 0,
					},
					color: '',
					value: 0,
				},

				canRead: false,
				canUpdate: false,
				canUpdateDeadline: false,
				canUpdateCreator: false,
				canUpdateResponsible: false,
				canUpdateAccomplices: false,
				canDelegate: false,
				canTake: false,
				canUpdateMark: false,
				canUpdateReminder: false,
				canUpdateElapsedTime: false,
				canAddChecklist: false,
				canUpdateChecklist: false,
				canRemove: false,
				canUseTimer: false,
				canStart: false,
				canPause: false,
				canComplete: false,
				canRenew: false,
				canApprove: false,
				canDisapprove: false,
				canDefer: false,
				canCreate: false,

				isRemoved: false,
				isExpired: false,
				isConsideredForCounterChange: false,
				isCreationErrorExist: false,
			};
		}

		/**
		 * @returns {TaskReduxModel}
		 */
		static getDefaultReduxTask()
		{
			return {
				id: `tmp-id-${Date.now()}`,
				guid: null,
				name: '',
				description: '',
				parsedDescription: '',
				groupId: 0,
				timeElapsed: 0,
				timeEstimate: 0,
				commentsCount: 0,
				serviceCommentsCount: 0,
				newCommentsCount: 0,
				resultsCount: 0,
				parentId: 0,

				status: TaskStatus.PENDING,
				subStatus: TaskStatus.PENDING,
				priority: TaskPriority.NORMAL,
				mark: null,

				creator: Number(env.userId),
				responsible: Number(env.userId),
				accomplices: [],
				auditors: [],
				relatedTasks: [],
				// subTasks: [],

				crm: [],
				tags: [],
				files: [],
				uploadedFiles: [],
				userFieldNames: [],

				isMuted: false,
				isPinned: {
					[env.userId]: false,
				},
				isPinnedInGroup: false,
				isInFavorites: false,
				isResultRequired: false,
				isResultExists: false,
				isOpenResultExists: false,
				isMatchWorkTime: false,
				allowChangeDeadline: false,
				allowTimeTracking: false,
				allowTaskControl: false,
				isTimerRunningForCurrentUser: false,
				areUserFieldsLoaded: true,

				deadline: null,
				activityDate: Math.ceil(Date.now() / 1000),
				updateDate: null,
				startDatePlan: null,
				endDatePlan: null,
				startDate: null,
				endDate: null,

				checklist: {
					completed: 0,
					uncompleted: 0,
				},
				checklistDetails: [],
				counter: {
					counters: {
						expired: 0,
						newComments: 0,
						mutedExpired: 0,
						mutedNewComments: 0,
						projectExpired: 0,
						projectNewComments: 0,
					},
					color: '',
					value: 0,
				},

				canRead: true,
				canUpdate: true,
				canUpdateDeadline: true,
				canUpdateCreator: true,
				canUpdateResponsible: true,
				canUpdateAccomplices: true,
				canDelegate: false,
				canTake: false,
				canUpdateMark: true,
				canUpdateReminder: true,
				canUpdateElapsedTime: false,
				canAddChecklist: true,
				canUpdateChecklist: true,
				canRemove: true,
				canUseTimer: false,
				canStart: true,
				canPause: false,
				canComplete: true,
				canRenew: false,
				canApprove: false,
				canDisapprove: false,
				canDefer: true,
				canCreate: true,

				isRemoved: false,
				isExpired: false,
				isConsideredForCounterChange: false,
				isCreationErrorExist: false,
			};
		}
	}

	module.exports = { TaskModel };
});

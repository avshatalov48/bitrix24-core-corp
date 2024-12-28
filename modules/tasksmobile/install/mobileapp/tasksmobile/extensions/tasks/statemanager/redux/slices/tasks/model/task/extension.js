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
		 * @return {object}
		 */
		static prepareReduxTaskFromServerTask(sourceServerTask, existingReduxTask = null)
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
				preparedTask.isPinned = serverTask.isPinned;
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

			if (
				!Type.isUndefined(serverTask.actions)
				&& !Type.isUndefined(serverTask.actionsOld)
			)
			{
				preparedTask.actionsOld = serverTask.actionsOld;

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

		static prepareReduxTaskFromOldTaskModel(oldTaskModel, existingReduxTask = null)
		{
			const preparedTask = { ...TaskModel.getEmptyReduxTask(), ...existingReduxTask };

			// eslint-disable-next-line no-underscore-dangle
			if (!Type.isUndefined(oldTaskModel._id))
			{
				// eslint-disable-next-line no-underscore-dangle
				preparedTask.id = Number(oldTaskModel._id);
			}

			if (!Type.isUndefined(oldTaskModel.title))
			{
				preparedTask.name = oldTaskModel.title;
			}

			if (!Type.isUndefined(oldTaskModel.description))
			{
				preparedTask.description = oldTaskModel.description;
			}

			if (!Type.isUndefined(oldTaskModel.parsedDescription))
			{
				preparedTask.parsedDescription = oldTaskModel.parsedDescription;
			}

			if (!Type.isUndefined(oldTaskModel.groupId))
			{
				preparedTask.groupId = Number(oldTaskModel.groupId);
			}

			if (!Type.isUndefined(oldTaskModel.flowId))
			{
				preparedTask.flowId = Number(oldTaskModel.flowId);
			}

			if (!Type.isUndefined(oldTaskModel.timeElapsed))
			{
				preparedTask.timeElapsed = Number(oldTaskModel.timeElapsed);
			}

			if (!Type.isUndefined(oldTaskModel.timeEstimate))
			{
				preparedTask.timeEstimate = Number(oldTaskModel.timeEstimate);
			}

			if (!Type.isUndefined(oldTaskModel.commentsCount))
			{
				preparedTask.commentsCount = Number(oldTaskModel.commentsCount);
			}

			if (!Type.isUndefined(oldTaskModel.serviceCommentsCount))
			{
				preparedTask.serviceCommentsCount = Number(oldTaskModel.serviceCommentsCount);
			}

			// eslint-disable-next-line no-underscore-dangle
			if (!Type.isUndefined(oldTaskModel._counter))
			{
				// eslint-disable-next-line no-underscore-dangle
				const { counters } = oldTaskModel._counter;

				preparedTask.newCommentsCount = counters.newComments + counters.mutedNewComments + counters.projectNewComments;
			}

			if (!Type.isUndefined(oldTaskModel.parentId))
			{
				preparedTask.parentId = Number(oldTaskModel.parentId);
			}

			if (!Type.isUndefined(oldTaskModel.status))
			{
				preparedTask.status = Number(oldTaskModel.status);
			}

			if (!Type.isUndefined(oldTaskModel.subStatus))
			{
				preparedTask.subStatus = Number(oldTaskModel.subStatus);
			}

			if (!Type.isUndefined(oldTaskModel.priority))
			{
				preparedTask.priority = Number(oldTaskModel.priority);
			}

			if (!Type.isUndefined(oldTaskModel.mark))
			{
				preparedTask.mark = oldTaskModel.mark;
			}

			if (!Type.isUndefined(oldTaskModel.creator))
			{
				preparedTask.creator = Number(oldTaskModel.creator.id);
			}

			if (!Type.isUndefined(oldTaskModel.responsible))
			{
				preparedTask.responsible = Number(oldTaskModel.responsible.id);
			}

			if (!Type.isUndefined(oldTaskModel.accomplices))
			{
				preparedTask.accomplices = Object.keys(oldTaskModel.accomplices).map((userId) => Number(userId));
			}

			if (!Type.isUndefined(oldTaskModel.auditors))
			{
				preparedTask.auditors = Object.keys(oldTaskModel.auditors).map((userId) => Number(userId));
			}

			if (!Type.isUndefined(oldTaskModel.relatedTasks))
			{
				preparedTask.relatedTasks = (Type.isArray(oldTaskModel.relatedTasks) ? [] : oldTaskModel.relatedTasks);
			}

			// if (!Type.isUndefined(oldTaskModel.subTasks))
			// {
			// 	preparedTask.subTasks = (Type.isArray(oldTaskModel.subTasks) ? [] : oldTaskModel.subTasks);
			// }

			if (!Type.isUndefined(oldTaskModel.crm))
			{
				preparedTask.crm = Object.values(oldTaskModel.crm);
			}

			if (!Type.isUndefined(oldTaskModel.tags))
			{
				preparedTask.tags = Object.values(oldTaskModel.tags).map((tag) => ({ id: tag.id, name: tag.title }));
			}

			if (!Type.isUndefined(oldTaskModel.files))
			{
				preparedTask.files = oldTaskModel.files;
			}

			if (!Type.isUndefined(oldTaskModel.isMuted))
			{
				preparedTask.isMuted = oldTaskModel.isMuted;
			}

			if (!Type.isUndefined(oldTaskModel.isPinned))
			{
				preparedTask.isPinned = oldTaskModel.isPinned;
			}

			// eslint-disable-next-line no-underscore-dangle
			if (!Type.isUndefined(oldTaskModel._actions))
			{
				// eslint-disable-next-line no-underscore-dangle
				preparedTask.isInFavorites = oldTaskModel._actions.canRemoveFromFavorite;
			}

			if (!Type.isUndefined(oldTaskModel.isResultRequired))
			{
				preparedTask.isResultRequired = oldTaskModel.isResultRequired;
			}

			if (!Type.isUndefined(oldTaskModel.isResultExists))
			{
				preparedTask.isResultExists = oldTaskModel.isResultExists;
			}

			if (!Type.isUndefined(oldTaskModel.isOpenResultExists))
			{
				preparedTask.isOpenResultExists = oldTaskModel.isOpenResultExists;
			}

			if (!Type.isUndefined(oldTaskModel.isMatchWorkTime))
			{
				preparedTask.isMatchWorkTime = oldTaskModel.isMatchWorkTime;
			}

			if (!Type.isUndefined(oldTaskModel.allowChangeDeadline))
			{
				preparedTask.allowChangeDeadline = oldTaskModel.allowChangeDeadline;
			}

			if (!Type.isUndefined(oldTaskModel.allowTimeTracking))
			{
				preparedTask.allowTimeTracking = oldTaskModel.allowTimeTracking;
			}

			if (!Type.isUndefined(oldTaskModel.allowTaskControl))
			{
				preparedTask.allowTaskControl = oldTaskModel.allowTaskControl;
			}

			if (!Type.isUndefined(oldTaskModel.isTimerRunningForCurrentUser))
			{
				preparedTask.isTimerRunningForCurrentUser = oldTaskModel.isTimerRunningForCurrentUser;
			}

			let isDeadlineChanged = false;
			if (!Type.isUndefined(oldTaskModel.deadline))
			{
				const oldDeadline = preparedTask.deadline;
				const newDeadline = oldTaskModel.deadline / 1000;

				preparedTask.deadline = newDeadline;
				isDeadlineChanged = (newDeadline !== oldDeadline);
			}

			if (!Type.isUndefined(oldTaskModel.activityDate))
			{
				preparedTask.activityDate = oldTaskModel.activityDate / 1000;
			}

			if (!Type.isUndefined(oldTaskModel.startDatePlan))
			{
				preparedTask.startDatePlan = oldTaskModel.startDatePlan / 1000;
			}

			if (!Type.isUndefined(oldTaskModel.endDatePlan))
			{
				preparedTask.endDatePlan = oldTaskModel.endDatePlan / 1000;
			}

			// todo: startDate and endDate
			// if (!Type.isUndefined(oldTaskModel.startDate))
			// {
			// 	preparedTask.startDate = oldTaskModel.startDate;
			// }
			//
			// if (!Type.isUndefined(oldTaskModel.endDate))
			// {
			// 	preparedTask.endDate = oldTaskModel.endDate;
			// }

			if (!Type.isUndefined(oldTaskModel.checklist))
			{
				preparedTask.checklist = oldTaskModel.checklist;
			}

			if (!Type.isUndefined(oldTaskModel.checklistDetails))
			{
				preparedTask.checklistDetails = oldTaskModel.checklistDetails;
			}

			// eslint-disable-next-line no-underscore-dangle
			if (!Type.isUndefined(oldTaskModel._counter))
			{
				// eslint-disable-next-line no-underscore-dangle
				preparedTask.counter = oldTaskModel._counter;
			}

			// eslint-disable-next-line no-underscore-dangle
			if (!Type.isUndefined(oldTaskModel._actions))
			{
				// eslint-disable-next-line no-underscore-dangle
				const actions = oldTaskModel._actions;

				preparedTask.actionsOld = actions.actions;
				preparedTask.canUpdateDeadline = actions.canChangeDeadline;
				preparedTask.canDelegate = actions.canDelegate;
				preparedTask.canTake = actions.canTake;
				preparedTask.canRemove = actions.canRemove;
				preparedTask.canUseTimer = actions.canStartTimer || actions.canPauseTimer;
				preparedTask.canStart = actions.canStart;
				preparedTask.canPause = actions.canPause;
				preparedTask.canComplete = actions.canComplete;
				preparedTask.canRenew = actions.canRenew;
				preparedTask.canApprove = actions.canApprove;
				preparedTask.canDisapprove = actions.canDisapprove;
				preparedTask.canDefer = actions.canDefer;
			}

			preparedTask.isExpired = (oldTaskModel.isExpired && !oldTaskModel.isCompletedCounts && !oldTaskModel.isDeferred);

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
				isPinned: undefined,
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
				actionsOld: {},

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
				isPinned: false,
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
				actionsOld: {},

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

				isRemoved: false,
				isExpired: false,
				isConsideredForCounterChange: false,
				isCreationErrorExist: false,
			};
		}
	}

	module.exports = { TaskModel };
});

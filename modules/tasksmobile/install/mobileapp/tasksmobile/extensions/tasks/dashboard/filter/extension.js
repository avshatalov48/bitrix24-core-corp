/**
 * @module tasks/dashboard/filter
 */
jn.define('tasks/dashboard/filter', (require, exports, module) => {
	const { BaseListFilter } = require('layout/ui/list/base-filter');
	const { Type } = require('type');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');
	const { EntityReady } = require('entity-ready');
	const { StorageCache } = require('storage-cache');
	const store = require('statemanager/redux/store');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { selectTaskStageByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks-stages');
	const {
		selectIsMember,
		selectIsPureCreator,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsAuditor,
		selectIsExpiredSoon,
		selectIsExpired,
		selectIsSupposedlyCompleted,
		selectIsDeferred,
	} = require('tasks/statemanager/redux/slices/tasks/selector');

	/**
	 * @class TasksDashboardFilter
	 */
	class TasksDashboardFilter extends BaseListFilter
	{
		static get presetType()
		{
			return {
				none: 'none',
				default: 'filter_tasks_in_progress',
				originator: 'filter_tasks_role_originator',
			};
		}

		static get roleType()
		{
			return {
				all: 'view_all',
				responsible: 'view_role_responsible',
				accomplice: 'view_role_accomplice',
				originator: 'view_role_originator',
				auditor: 'view_role_auditor',
			};
		}

		static get counterType()
		{
			return {
				none: 'none',
				expired: 'expired',
				newComments: 'new_comments',
			};
		}

		static get allowedPresetField()
		{
			return {
				ID: 'ID',
				TITLE: 'TITLE',
				STATUS: 'STATUS',
				ROLEID: 'ROLEID',
				GROUP_ID: 'GROUP_ID',
				// PRIORITY: 'PRIORITY',
				// MARK: 'MARK',
				// TAG: 'TAG',
				PROBLEM: 'PROBLEM',

				ALLOW_TIME_TRACKING: 'ALLOW_TIME_TRACKING',

				// DEADLINE: 'DEADLINE',
				// CREATED_DATE: 'CREATED_DATE',
				// CLOSED_DATE: 'CLOSED_DATE',
				// DATE_START: 'DATE_START',
				// START_DATE_PLAN: 'START_DATE_PLAN',
				// END_DATE_PLAN: 'END_DATE_PLAN',
				// ACTIVITY_DATE: 'ACTIVITY_DATE',
				// ACTIVE: 'ACTIVE',

				CREATED_BY: 'CREATED_BY',
				RESPONSIBLE_ID: 'RESPONSIBLE_ID',
				ACCOMPLICE: 'ACCOMPLICE',
				AUDITOR: 'AUDITOR',
			};
		}

		/**
		 * @param currentUserId
		 * @param ownerId
		 * @param projectId
		 * @param isTabsMode
		 * @param tabsGuid
		 * @param presetId
		 * @param role
		 * @param isRootComponent
		 * @param siteId
		 */
		constructor(currentUserId, ownerId, projectId, isTabsMode, tabsGuid, presetId, role, isRootComponent, siteId)
		{
			super(presetId ?? TasksDashboardFilter.presetType.default, '', false);

			this.currentUserId = Number(currentUserId);
			this.ownerId = Number(ownerId);
			this.projectId = Number(projectId);
			this.isTabsMode = isTabsMode;
			this.tabsGuid = tabsGuid;

			this.counterType = TasksDashboardFilter.counterType.none;
			this.role = role ?? TasksDashboardFilter.roleType.all;
			this.counterValue = 0;

			setTimeout(() => this.fillPresets(this.getFillPresetParams()), 1000);

			if (isRootComponent && this.isMyList())
			{
				this.setInitialDownMenuTasksCounter(siteId);

				EntityReady.wait('chat')
					.then(() => setTimeout(() => this.setInitialDownMenuTasksCounter(siteId), 1000))
					.catch(console.error)
				;
			}
			this.clearCounters();
		}

		setInitialDownMenuTasksCounter(siteId)
		{
			const taskListCache = new StorageCache('tasksTaskList', 'filterCounters_0');
			let counterValue = 0;

			const cachedCounters = Application.sharedStorage().get('userCounters');
			if (cachedCounters)
			{
				try
				{
					const counters = JSON.parse(cachedCounters)[siteId];
					counterValue = (counters.tasks_total || 0);
					taskListCache.set({ counterValue });
				}
				catch
				{
					// do nothing
				}
			}
			else
			{
				const taskListCounter = taskListCache.get();
				if (taskListCounter)
				{
					counterValue = (taskListCounter.counterValue || 0);
				}
			}

			Application.setBadges({ tasks: counterValue });
		}

		/**
		 * @public
		 */
		getRole()
		{
			return this.role;
		}

		/**
		 * @private
		 */
		setRole(role)
		{
			this.role = role;
		}

		/**
		 * @public
		 * @return {String} presetId
		 */
		getDefaultPreset()
		{
			return TasksDashboardFilter.presetType.default;
		}

		getFillPresetParams()
		{
			return {
				action: 'tasksmobile.Filter.getTaskListPresets',
				options: { groupId: this.projectId },
			};
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDefault()
		{
			return (
				this.isSearchStringEmpty()
				&& this.isEmptyCounter()
				&& this.isRoleForAll()
				&& this.isDefaultPreset()
			);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmpty()
		{
			return (
				this.isSearchStringEmpty()
				&& this.isEmptyCounter()
				&& this.isRoleForAll()
				&& this.isEmptyPreset()
			);
		}

		/**
		 * @private
		 */
		clearCounters()
		{
			this.counters = {};

			Object.values(TasksDashboardFilter.roleType).forEach((role) => {
				this.counters[role] = {
					[TasksDashboardFilter.counterType.expired]: 0,
					[TasksDashboardFilter.counterType.newComments]: 0,
				};
			});
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		isMyList()
		{
			return (!this.isAnotherUserList() && !this.isProjectList());
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		isAnotherUserList()
		{
			return (this.currentUserId !== this.ownerId);
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		isProjectList()
		{
			return (this.projectId > 0);
		}

		/**
		 * @public
		 * @param data
		 * @returns {Promise}
		 */
		updateCountersFromPullEvent(data)
		{
			return new Promise((resolve) => {
				if (Number(this.currentUserId) !== Number(data.userId) || this.isAnotherUserList())
				{
					resolve();

					return;
				}

				this.clearCounters();
				this.counterValue = 0;

				Object.values(TasksDashboardFilter.roleType).forEach((role) => {
					const counter = data?.[this.projectId]?.[role];
					if (counter)
					{
						this.counters[role] = {
							[TasksDashboardFilter.counterType.expired]: (
								Number(counter[TasksDashboardFilter.counterType.expired])
							),
							[TasksDashboardFilter.counterType.newComments]: (
								Number(counter[TasksDashboardFilter.counterType.newComments])
							),
						};
					}
				});

				const onPullValue = Object.values(this.counters[TasksDashboardFilter.roleType.all])
					.reduce((a, b) => a + b, 0);
				const potentialValue = onPullValue + FieldChangeRegistry.getCounter();

				if (potentialValue === 0)
				{
					this.counters[TasksDashboardFilter.roleType.all] = {
						[TasksDashboardFilter.counterType.expired]: 0,
						[TasksDashboardFilter.counterType.newComments]: 0,
					};
				}
				this.updateCounterValue(potentialValue);

				resolve();
			});
		}

		/**
		 * @public
		 * @returns {Promise}
		 */
		updateCounters()
		{
			return new Promise((resolve) => {
				if (this.isAnotherUserList())
				{
					resolve();

					return;
				}

				const batchOperations = {};

				Object.values(TasksDashboardFilter.roleType).forEach((role) => {
					batchOperations[role] = [
						'tasksmobile.Task.Counter.getByRole',
						{
							role,
							userId: this.ownerId,
							groupId: this.projectId,
						},
					];
				});

				BX.rest.callBatch(batchOperations, (result) => {
					if (!result[TasksDashboardFilter.roleType.all].answer.result)
					{
						return;
					}

					this.clearCounters();
					this.counterValue = 0;

					Object.values(TasksDashboardFilter.roleType).forEach((role) => {
						const roleCounters = result[role].answer.result;
						this.counters[role] = {
							[TasksDashboardFilter.counterType.expired]: Number(roleCounters[TasksDashboardFilter.counterType.expired].counter),
							[TasksDashboardFilter.counterType.newComments]: Number(roleCounters[TasksDashboardFilter.counterType.newComments].counter),
						};
					});
					this.updateCounterValue(
						Object.values(this.counters[TasksDashboardFilter.roleType.all]).reduce((a, b) => a + b, 0),
					);

					resolve();
				});
			});
		}

		/**
		 * @public
		 * @returns {number}
		 */
		getCounterValue()
		{
			return this.counterValue;
		}

		/**
		 * @public
		 * @param value
		 */
		updateCounterValue(value)
		{
			this.counterValue = Number(value > 0 ? value : 0);

			this.setDownMenuTasksCounter();
			this.setTasksTabCounter();
		}

		/**
		 * @private
		 */
		setDownMenuTasksCounter()
		{
			if (this.isMyList())
			{
				Application.setBadges({ tasks: this.counterValue });
			}
		}

		/**
		 * @private
		 */
		setTasksTabCounter()
		{
			if (!this.isTabsMode)
			{
				return;
			}

			if (this.isMyList())
			{
				BX.postComponentEvent('tasks.list:setVisualCounter', [{ value: this.counterValue }], 'tasks.tabs');
			}
			else if (this.isProjectList())
			{
				BX.postComponentEvent('tasks.list:setVisualCounter', [
					{
						value: this.counterValue,
						guid: this.tabsGuid,
					},
				]);
			}
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getCounterType()
		{
			return this.counterType;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isEmptyCounter()
		{
			return this.counterType === TasksDashboardFilter.counterType.none;
		}

		/**
		 * @public
		 * @param type
		 */
		setCounterType(type)
		{
			if (Object.values(TasksDashboardFilter.counterType).includes(type))
			{
				this.counterType = type;
			}
		}

		/**
		 * @public
		 * @param {string|null} presetId
		 */
		setPresetId(presetId)
		{
			this.presetId = presetId;
			this.setRole(this.getRoleByPreset(presetId) || TasksDashboardFilter.roleType.all);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRoleForAll()
		{
			return this.role === TasksDashboardFilter.roleType.all;
		}

		/**
		 * @public
		 * @param {string} role
		 * @returns {*}
		 */
		getCountersByRole(role = this.getRole())
		{
			return this.counters[role];
		}

		/**
		 * @public
		 * @param presetId
		 * @returns {*|null}
		 */
		getRoleByPreset(presetId)
		{
			if (!Type.isArray(this.presets) || Object.keys(this.presets).length === 0)
			{
				return null;
			}

			const preset = this.getPresetById(presetId);
			if (
				!preset
				|| (Type.isArray(preset.fields) && preset.fields.length === 0)
			)
			{
				return null;
			}

			const has = Object.prototype.hasOwnProperty;
			if (has.call(preset.fields, TasksDashboardFilter.allowedPresetField.ROLEID))
			{
				return preset.fields[TasksDashboardFilter.allowedPresetField.ROLEID];
			}

			return null;
		}

		/**
		 * @param {TaskReduxModel} task
		 * @param {number|null} groupId
		 * @param {number|null} stageId
		 * @param {string} view
		 */
		isTaskSuitDashboard(task, groupId, stageId, view)
		{
			const isTaskSuitSearch = this.isTaskSuitSearch(task, this.searchString);
			const isTaskSuitGroup = this.isTaskSuitGroup(task, groupId);
			const isTaskSuitStage = this.isTaskSuitStage(task, stageId, view, this.ownerId);
			const isTaskSuitRoleCounter = this.isTaskSuitRoleCounter(task, this.role, this.counterType);
			const isTaskSuitPreset = this.isTaskSuitPreset(task, this.getPresetId());

			return (
				isTaskSuitSearch
				&& isTaskSuitGroup
				&& isTaskSuitStage
				&& isTaskSuitRoleCounter
				&& isTaskSuitPreset
			);
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @param {string} presetId
		 * @return {boolean}
		 */
		isTaskSuitPreset(task, presetId)
		{
			if (!this.loaded)
			{
				return false;
			}

			const preset = this.presets.find((item) => item.id === presetId);
			if (!preset)
			{
				return false;
			}

			const { fields } = preset;
			if (Type.isArray(fields) && fields.length === 0)
			{
				return true;
			}

			if (!this.isPresetContainAllowedFieldsOnly(fields))
			{
				return false;
			}

			return this.isTaskSuitPresetFields(task, fields);
		}

		/**
		 * @private
		 * @param {object} presetFields
		 */
		isPresetContainAllowedFieldsOnly(presetFields)
		{
			const fields = Object.keys(presetFields).map((field) => field.replaceAll(/_[a-z]+/g, ''));
			const allowedFields = Object.values(TasksDashboardFilter.allowedPresetField);

			return fields.every((field) => allowedFields.includes(field));
		}

		/**
		 * @private
		 * @param {TaskReduxModel} task
		 * @param {object} fields
		 * @return {boolean}
		 */
		isTaskSuitPresetFields(task, fields)
		{
			const has = Object.prototype.hasOwnProperty;
			let result = true;

			Object.values(TasksDashboardFilter.allowedPresetField).forEach((field) => {
				switch (field)
				{
					case TasksDashboardFilter.allowedPresetField.ID:
						if (has.call(fields, 'ID_from') && has.call(fields, 'ID_to'))
						{
							const from = Number(fields.ID_from);
							const to = Number(fields.ID_to);
							const taskId = Number(task.id);

							if (from && to && from === to)
							{
								result = (result && (taskId === from));
								break;
							}

							const isEqual = (has.call(fields, 'ID_numsel') && fields.ID_numsel === 'range');
							let localResult = true;

							if (from)
							{
								localResult = (localResult && (isEqual ? (taskId >= from) : (taskId > from)));
							}

							if (to)
							{
								localResult = (localResult && (isEqual ? (taskId <= to) : (taskId < to)));
							}
							result = (result && localResult);
						}
						break;

					case TasksDashboardFilter.allowedPresetField.TITLE:
						if (has.call(fields, 'TITLE'))
						{
							const title = task.name.toLowerCase().trim();
							const searchedText = fields.TITLE.toLowerCase().trim();

							result = (result && title.split(' ').some((word) => word.search(searchedText) === 0));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.STATUS:
						if (has.call(fields, 'STATUS'))
						{
							result = (result && fields.STATUS.map((status) => Number(status)).includes(task.status));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.ROLEID:
						if (has.call(fields, 'ROLEID'))
						{
							const roleMap = {
								[TasksDashboardFilter.roleType.responsible]: selectIsResponsible(task),
								[TasksDashboardFilter.roleType.accomplice]: selectIsAccomplice(task),
								[TasksDashboardFilter.roleType.originator]: selectIsPureCreator(task),
								[TasksDashboardFilter.roleType.auditor]: selectIsAuditor(task),
							};

							result = (result && roleMap[fields.ROLEID]);
						}
						break;

					case TasksDashboardFilter.allowedPresetField.GROUP_ID:
						if (has.call(fields, 'GROUP_ID'))
						{
							result = (result && fields.GROUP_ID.includes(task.groupId.toString()));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.PROBLEM:
						if (has.call(fields, 'PROBLEM'))
						{
							const problemMap = {
								1_048_576: false, // not_viewed
								5_242_880: selectIsDeferred(task), // deferred
								6_291_456: (selectIsExpired(task) && !task.isMuted), // expired
								8_388_608: selectIsSupposedlyCompleted(task), // supposedly_completed
								9_437_184: selectIsExpiredSoon(task), // expired_soon
								10_485_760: !task.deadline, // no_deadline
								12_582_912: (task.newCommentsCount > 0 && !task.isMuted), // with_new_comments
							};

							result = (result && has.call(problemMap, fields.PROBLEM) && problemMap[fields.PROBLEM]);
						}
						break;

					case TasksDashboardFilter.allowedPresetField.ALLOW_TIME_TRACKING:
						if (has.call(fields, 'ALLOW_TIME_TRACKING'))
						{
							result = (result && fields.ALLOW_TIME_TRACKING === (task.allowTimeTracking ? 'Y' : 'N'));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.CREATED_BY:
						if (has.call(fields, 'CREATED_BY'))
						{
							result = (result && fields.CREATED_BY.includes(task.creator.toString()));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.RESPONSIBLE_ID:
						if (has.call(fields, 'RESPONSIBLE_ID'))
						{
							result = (result && fields.RESPONSIBLE_ID.includes(task.responsible.toString()));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.ACCOMPLICE:
						if (has.call(fields, 'ACCOMPLICE'))
						{
							result = (result && task.accomplices.some((userId) => fields.ACCOMPLICE.includes(userId)));
						}
						break;

					case TasksDashboardFilter.allowedPresetField.AUDITOR:
						if (has.call(fields, 'AUDITOR'))
						{
							result = (result && task.auditors.some((userId) => fields.AUDITOR.includes(userId)));
						}
						break;

					default:
						break;
				}
			});

			return result;
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @param {string} role
		 * @param {string} counter
		 * @return {boolean}
		 */
		isTaskSuitRoleCounter(task, role, counter)
		{
			const isMember = selectIsMember(task);
			const isPureCreator = selectIsPureCreator(task);
			const isResponsible = selectIsResponsible(task);
			const isAccomplice = selectIsAccomplice(task);
			const isAuditor = selectIsAuditor(task);
			const isExpired = (selectIsExpired(task) && !task.isMuted);
			const isWithNewComments = (task.newCommentsCount > 0 && !task.isMuted);

			const roleCounterMap = {
				[TasksDashboardFilter.roleType.all]: {
					[TasksDashboardFilter.counterType.none]: isMember,
					[TasksDashboardFilter.counterType.expired]: (isMember && isExpired),
					[TasksDashboardFilter.counterType.newComments]: (isMember && isWithNewComments),
				},
				[TasksDashboardFilter.roleType.responsible]: {
					[TasksDashboardFilter.counterType.none]: isResponsible,
					[TasksDashboardFilter.counterType.expired]: (isResponsible && isExpired),
					[TasksDashboardFilter.counterType.newComments]: (isResponsible && isWithNewComments),
				},
				[TasksDashboardFilter.roleType.accomplice]: {
					[TasksDashboardFilter.counterType.none]: isAccomplice,
					[TasksDashboardFilter.counterType.expired]: (isAccomplice && isExpired),
					[TasksDashboardFilter.counterType.newComments]: (isAccomplice && isWithNewComments),
				},
				[TasksDashboardFilter.roleType.originator]: {
					[TasksDashboardFilter.counterType.none]: isPureCreator,
					[TasksDashboardFilter.counterType.expired]: (isPureCreator && isExpired),
					[TasksDashboardFilter.counterType.newComments]: (isPureCreator && isWithNewComments),
				},
				[TasksDashboardFilter.roleType.auditor]: {
					[TasksDashboardFilter.counterType.none]: isAuditor,
					[TasksDashboardFilter.counterType.expired]: (isAuditor && isExpired),
					[TasksDashboardFilter.counterType.newComments]: (isAuditor && isWithNewComments),
				},
			};

			return roleCounterMap[role][counter];
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @param {number} groupId
		 * @return {boolean}
		 */
		isTaskSuitGroup(task, groupId)
		{
			return (!groupId || groupId === Number(task.groupId));
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @param {number} stageId
		 * @param {string} view
		 * @param {number} ownerId
		 * @param {object} taskStage
		 */
		isTaskSuitStage(task, stageId, view, ownerId, taskStage = null)
		{
			if (!stageId)
			{
				return true;
			}

			const stage = (
				taskStage
				|| selectTaskStageByTaskIdOrGuid(store.getState(), task.id, task.guid, view, ownerId)
			);

			return (stage?.stageId === stageId);
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @param {string} searchText
		 * @return {boolean}
		 */
		isTaskSuitSearch(task, searchText)
		{
			const text = searchText.toLowerCase().trim();
			if (text === '')
			{
				return true;
			}

			return this.buildTaskSearchIndex(task).split(' ').some((word) => word.search(text) === 0);
		}

		/**
		 * @private
		 * @param {TaskReduxModel} task
		 * @return {string}
		 */
		buildTaskSearchIndex(task)
		{
			const searchIndexParts = new Set();

			if (task.id)
			{
				searchIndexParts.add(task.id);
			}

			if (task.name)
			{
				searchIndexParts.add(task.name);
			}

			if (task.description)
			{
				searchIndexParts.add(task.description);
			}

			const taskMembers = new Set([
				task.creator,
				task.responsible,
				...task.accomplices,
				...task.auditors,
			]);
			taskMembers.forEach((userId) => {
				const user = usersSelector.selectById(store.getState(), userId);
				if (user?.fullName)
				{
					searchIndexParts.add(user.fullName);
				}
			});

			if (task.groupId)
			{
				const group = selectGroupById(store.getState(), task.groupId);
				if (group?.name)
				{
					searchIndexParts.add(group.name);
				}
			}

			return [...searchIndexParts].join(' ').toLowerCase();
		}
	}

	module.exports = { TasksDashboardFilter };
});

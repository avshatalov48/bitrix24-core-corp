/**
 * @module tasks/filter/task
 */
jn.define('tasks/filter/task', (require, exports, module) => {
	const { Type } = require('type');

	class TaskFilter
	{
		static get presetType()
		{
			return {
				none: 'none',
				default: 'filter_tasks_in_progress',
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
				supposedlyCompleted: 'supposedly_completed',
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

		constructor()
		{
			this.loaded = false;
			this.presets = [];
		}

		fillPresets(groupId)
		{
			return new Promise((resolve) => {
				(new RequestExecutor('tasksmobile.Filter.getTaskListPresets', { groupId }))
					.call()
					.then((response) => {
						this.loaded = true;
						this.presets = response.result;
						resolve();
					})
					.catch(() => {})
				;
			});
		}

		getRoleByPreset(presetId)
		{
			if (Object.keys(this.presets).length === 0)
			{
				return null;
			}

			const preset = this.presets.find((item) => item.id === presetId);
			if (!preset)
			{
				return null;
			}

			const fields = TaskFilter.clearEmptyFields(preset.fields);

			if (Object.keys(fields).length === 0)
			{
				return null;
			}

			const has = Object.prototype.hasOwnProperty;
			if (has.call(fields, TaskFilter.allowedPresetField.ROLEID))
			{
				return fields[TaskFilter.allowedPresetField.ROLEID];
			}

			return null;
		}

		/**
		 * @public
		 * @param {string} presetId
		 * @return {*}
		 */
		getPresetById(presetId)
		{
			return this.presets.find((item) => item.id === presetId);
		}

		/**
		 * @public
		 * @param {string} presetId
		 * @return {string|null}
		 */
		getPresetNameById(presetId)
		{
			const preset = this.getPresetById(presetId);
			if (!preset)
			{
				return null;
			}

			return preset.name;
		}

		/**
		 * @param {string} presetId
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitPreset(presetId, task)
		{
			if (!this.loaded)
			{
				return true;
			}

			const preset = this.presets.find((item) => item.id === presetId);
			if (!preset)
			{
				return false;
			}

			const fields = TaskFilter.clearEmptyFields(preset.fields);
			if (Object.keys(fields).length === 0)
			{
				return true;
			}

			if (!TaskFilter.isPresetContainAllowedFieldsOnly(fields))
			{
				return false;
			}

			return TaskFilter.isTaskSuitPresetFields(task, fields);
		}

		/**
		 * @param {object|array} fields
		 */
		static clearEmptyFields(fields)
		{
			const result = {};

			if (Type.isArray(fields) && fields.length === 0)
			{
				return result;
			}

			Object.entries(fields).forEach(([field, values]) => {
				if (values !== 'undefined')
				{
					result[field] = values;
				}
			});

			return result;
		}

		/**
		 * @param {array} presetFields
		 */
		static isPresetContainAllowedFieldsOnly(presetFields)
		{
			const fields = Object.keys(presetFields);
			const allowedFields = Object.values(TaskFilter.allowedPresetField);

			return fields.every((item1) => allowedFields.some((item2) => item1.search(item2) === 0));
		}

		/**
		 * @param {Task} task
		 * @param {object} fields
		 * @return {boolean}
		 */
		static isTaskSuitPresetFields(task, fields)
		{
			const has = Object.prototype.hasOwnProperty;
			let result = true;

			Object.values(TaskFilter.allowedPresetField).forEach((field) => {
				switch (field)
				{
					case TaskFilter.allowedPresetField.ID:
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

					case TaskFilter.allowedPresetField.TITLE:
						if (has.call(fields, 'TITLE'))
						{
							const title = task.title.toLowerCase().trim();
							const searchedText = fields.TITLE.toLowerCase().trim();

							result = (result && title.split(' ').some((word) => word.search(searchedText) === 0));
						}
						break;

					case TaskFilter.allowedPresetField.STATUS:
						if (has.call(fields, 'STATUS'))
						{
							result = (result && fields.STATUS.includes(task.status.toString()));
						}
						break;

					case TaskFilter.allowedPresetField.ROLEID:
						if (has.call(fields, 'ROLEID'))
						{
							const roleMap = {
								[TaskFilter.roleType.responsible]: task.isResponsible(),
								[TaskFilter.roleType.accomplice]: task.isAccomplice(),
								[TaskFilter.roleType.originator]: task.isPureCreator(),
								[TaskFilter.roleType.auditor]: task.isAuditor(),
							};

							result = (result && roleMap[fields.ROLEID]);
						}
						break;

					case TaskFilter.allowedPresetField.GROUP_ID:
						if (has.call(fields, 'GROUP_ID'))
						{
							result = (result && fields.GROUP_ID.includes(task.groupId.toString()));
						}
						break;

					case TaskFilter.allowedPresetField.PROBLEM:
						if (has.call(fields, 'PROBLEM'))
						{
							const problemMap = {
								1_048_576: false, // not_viewed
								5_242_880: task.isDeferred, // deferred
								6_291_456: task.isExpired, // expired
								8_388_608: task.isSupposedlyCompletedCounts, // supposedly_completed
								9_437_184: task.isExpiredSoon, // expired_soon
								10_485_760: task.isWoDeadline, // no_deadline
								12_582_912: task.getCounterMyNewCommentsCount() > 0, // with_new_comments
							};

							result = (result && has.call(problemMap, fields.PROBLEM) && problemMap[fields.PROBLEM]);
						}
						break;

					case TaskFilter.allowedPresetField.ALLOW_TIME_TRACKING:
						if (has.call(fields, 'ALLOW_TIME_TRACKING'))
						{
							result = (result && fields.ALLOW_TIME_TRACKING === (task.allowTimeTracking ? 'Y' : 'N'));
						}
						break;

					case TaskFilter.allowedPresetField.CREATED_BY:
						if (has.call(fields, 'CREATED_BY'))
						{
							result = (result && fields.CREATED_BY.includes(task.creator.id.toString()));
						}
						break;

					case TaskFilter.allowedPresetField.RESPONSIBLE_ID:
						if (has.call(fields, 'RESPONSIBLE_ID'))
						{
							result = (result && fields.RESPONSIBLE_ID.includes(task.responsible.id.toString()));
						}
						break;

					case TaskFilter.allowedPresetField.ACCOMPLICE:
						if (has.call(fields, 'ACCOMPLICE'))
						{
							result = (result && Object.keys(task.accomplices).some((userId) => fields.ACCOMPLICE.includes(
								userId,
							)));
						}
						break;

					case TaskFilter.allowedPresetField.AUDITOR:
						if (has.call(fields, 'AUDITOR'))
						{
							result = (result && Object.keys(task.auditors).some((userId) => fields.AUDITOR.includes(
								userId,
							)));
						}
						break;

					default:
						break;
				}
			});

			return result;
		}

		/**
		 * @param {string} role
		 * @param {string} counter
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitRoleCounter(role, counter, task)
		{
			const roleCounterMap = {
				[TaskFilter.roleType.all]: {
					[TaskFilter.counterType.none]: task.isMember(),
					[TaskFilter.counterType.expired]: (task.isMember() && task.getCounterMyExpiredCount() > 0),
					[TaskFilter.counterType.newComments]: (task.isMember() && task.getCounterMyNewCommentsCount() > 0),
					[TaskFilter.counterType.supposedlyCompleted]: (task.isPureCreator() && task.isSupposedlyCompleted),
				},
				[TaskFilter.roleType.responsible]: {
					[TaskFilter.counterType.none]: task.isResponsible(),
					[TaskFilter.counterType.expired]: (task.isResponsible() && task.getCounterMyExpiredCount() > 0),
					[TaskFilter.counterType.newComments]: (task.isResponsible() && task.getCounterMyNewCommentsCount() > 0),
					[TaskFilter.counterType.supposedlyCompleted]: (task.isResponsible() && task.isSupposedlyCompleted),
				},
				[TaskFilter.roleType.accomplice]: {
					[TaskFilter.counterType.none]: task.isAccomplice(),
					[TaskFilter.counterType.expired]: (task.isAccomplice() && task.getCounterMyExpiredCount() > 0),
					[TaskFilter.counterType.newComments]: (task.isAccomplice() && task.getCounterMyNewCommentsCount() > 0),
					[TaskFilter.counterType.supposedlyCompleted]: (task.isAccomplice() && task.isSupposedlyCompleted),
				},
				[TaskFilter.roleType.originator]: {
					[TaskFilter.counterType.none]: task.isPureCreator(),
					[TaskFilter.counterType.expired]: (task.isPureCreator() && task.getCounterMyExpiredCount() > 0),
					[TaskFilter.counterType.newComments]: (task.isPureCreator() && task.getCounterMyNewCommentsCount() > 0),
					[TaskFilter.counterType.supposedlyCompleted]: (task.isPureCreator() && task.isSupposedlyCompleted),
				},
				[TaskFilter.roleType.auditor]: {
					[TaskFilter.counterType.none]: task.isAuditor(),
					[TaskFilter.counterType.expired]: (task.isAuditor() && task.getCounterMyExpiredCount() > 0),
					[TaskFilter.counterType.newComments]: (task.isAuditor() && task.getCounterMyNewCommentsCount() > 0),
					[TaskFilter.counterType.supposedlyCompleted]: (task.isAuditor() && task.isSupposedlyCompleted),
				},
			};

			return roleCounterMap[role][counter];
		}

		/**
		 * @param {integer} groupId
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitGroup(groupId, task)
		{
			return (!groupId || groupId === Number(task.groupId));
		}

		/**
		 * @param {string} searchText
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitSearch(searchText, task)
		{
			const text = searchText.toLowerCase().trim();
			if (text === '')
			{
				return true;
			}

			return TaskFilter.buildTaskSearchIndex(task).split(' ').some((word) => word.search(text) === 0);
		}

		/**
		 * @param {Task} task
		 * @return {string}
		 */
		static buildTaskSearchIndex(task)
		{
			const searchIndexParts = new Set();

			if (task.id)
			{
				searchIndexParts.add(task.id);
			}

			if (task.title)
			{
				searchIndexParts.add(task.title);
			}

			if (task.description)
			{
				searchIndexParts.add(task.description);
			}

			if (task.creator && task.creator.name)
			{
				searchIndexParts.add(task.creator.name);
			}

			if (task.responsible && task.responsible.name)
			{
				searchIndexParts.add(task.responsible.name);
			}

			if (task.accomplices)
			{
				Object.values(task.accomplices).forEach((user) => searchIndexParts.add(user.name || ''));
			}

			if (task.auditors)
			{
				Object.values(task.auditors).forEach((user) => searchIndexParts.add(user.name || ''));
			}

			if (task.group && task.group.name)
			{
				searchIndexParts.add(task.group.name);
			}

			return [...searchIndexParts].join(' ').toLowerCase();
		}

		getPresets()
		{
			return this.presets;
		}
	}

	module.exports = { TaskFilter };
});

/**
 * @module tasks/flow-list/src/filter
 */
jn.define('tasks/flow-list/src/filter', (require, exports, module) => {
	const { BaseListFilter } = require('layout/ui/list/base-filter');
	const { Type } = require('type');
	const { RunActionExecutor } = require('rest/run-action-executor');

	/**
	 * @class TasksFlowListFilter
	 */
	class TasksFlowListFilter extends BaseListFilter
	{
		static get presetType()
		{
			return {
				none: 'none',
				default: 'none',
			};
		}

		static get roleType()
		{
			return {
				all: 'view_all',
			};
		}

		static get counterType()
		{
			return {
				none: 'none',
				expired: 'expired',
				newComments: 'new_comments',
				flowTotalExpired: 'flow_total_expired',
				flowTotalComments: 'flow_total_comments',
				flowTotal: 'flow_total',
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
		 */
		constructor(currentUserId)
		{
			super(TasksFlowListFilter.presetType.default, '');
			this.currentUserId = Number(currentUserId);

			this.counterType = TasksFlowListFilter.counterType.none;
			this.role = TasksFlowListFilter.roleType.all;
			this.flowsTotalCounterValue = 0;

			this.clearCounters();
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
			return TasksFlowListFilter.presetType.default;
		}

		getFillPresetParams()
		{
			return {
				// todo replace with flows backend
				action: 'tasksmobile.Filter.getTaskListPresets',
				options: {},
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
			this.flowsTotalCounterValue = 0;
			this.counters = {};

			Object.values(TasksFlowListFilter.roleType).forEach((role) => {
				this.setCountersByRole(role, {
					[TasksFlowListFilter.counterType.expired]: 0,
					[TasksFlowListFilter.counterType.newComments]: 0,
				});
			});
		}

		setCountersByRole(role, newCounters)
		{
			if (Object.values(TasksFlowListFilter.roleType).includes(role))
			{
				this.counters[role] = {
					...this.counters[role],
					...newCounters,
				};
			}
		}

		/**
		 * @public
		 */
		getCounters()
		{
			return this.counters;
		}

		/**
		 * @public
		 * @param data
		 * @returns {Promise}
		 */
		updateCountersFromPullEvent(data)
		{
			return new Promise((resolve) => {
				this.clearCounters();

				Object.values(TasksFlowListFilter.roleType).forEach((role) => {
					const countersFromPull = data[0][role];
					this.setCountersByRole(role, {
						[TasksFlowListFilter.counterType.expired]: Number(
							countersFromPull[TasksFlowListFilter.counterType.flowTotalExpired],
						),
						[TasksFlowListFilter.counterType.newComments]: Number(
							countersFromPull[TasksFlowListFilter.counterType.flowTotalComments],
						),
					});
				});
				const totalCounterValue = data[TasksFlowListFilter.counterType.flowTotal];
				this.updateFlowTotalCounterValue(totalCounterValue);

				resolve();
			});
		}

		/**
		 * @public
		 * @returns {Promise}
		 */
		loadCountersFromServer()
		{
			return new Promise((resolve) => {
				new RunActionExecutor('tasksmobile.Flow.getCounters', {})
					.setHandler((result) => {
						if (result && result.status === 'success')
						{
							const countersFromServer = result.data;
							this.counters[this.getRole()] = {
								[TasksFlowListFilter.counterType.expired]: Number(
									countersFromServer[TasksFlowListFilter.counterType.flowTotalExpired],
								),
								[TasksFlowListFilter.counterType.newComments]: Number(
									countersFromServer[TasksFlowListFilter.counterType.flowTotalComments],
								),
							};
							this.flowsTotalCounterValue = countersFromServer[TasksFlowListFilter.counterType.flowTotal];
						}
						resolve();
					})
					.call(false);
			});
		}

		/**
		 * @public
		 * @returns {number}
		 */
		getFlowTotalCounterValue()
		{
			return this.flowsTotalCounterValue;
		}

		/**
		 * @public
		 */
		updateFlowTotalCounterValue(newValue)
		{
			this.flowsTotalCounterValue = Number(newValue > 0 ? newValue : 0);
			this.updateFlowsTabCounter();
		}

		/**
		 * @private
		 */
		updateFlowsTabCounter()
		{
			BX.postComponentEvent('flows.list:setVisualCounter', [{
				value: this.flowsTotalCounterValue,
			}], 'tasks.tabs');
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
			return this.counterType === TasksFlowListFilter.counterType.none;
		}

		/**
		 * @public
		 * @param type
		 */
		setCounterType(type)
		{
			if (Object.values(TasksFlowListFilter.counterType).includes(type))
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
			this.setRole(this.getRoleByPreset(presetId) || TasksFlowListFilter.roleType.all);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRoleForAll()
		{
			return this.role === TasksFlowListFilter.roleType.all;
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
			if (has.call(preset.fields, TasksFlowListFilter.allowedPresetField.ROLEID))
			{
				return preset.fields[TasksFlowListFilter.allowedPresetField.ROLEID];
			}

			return null;
		}
	}

	module.exports = { TasksFlowListFilter };
});

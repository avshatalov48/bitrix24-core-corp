/**
 * @module tasks/dashboard/src/filter
 */
jn.define('tasks/dashboard/src/filter', (require, exports, module) => {
	const { TaskFilter } = require('tasks/filter/task');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');

	class Filter
	{
		constructor(currentUserId, ownerId, projectId, isTabsMode, tabsGuid)
		{
			this.currentUserId = Number(currentUserId);
			this.ownerId = Number(ownerId);
			this.projectId = Number(projectId);
			this.isTabsMode = isTabsMode;
			this.tabsGuid = tabsGuid;

			this.counterType = TaskFilter.counterType.none;
			this.counterValue = 0;
			this.role = TaskFilter.roleType.all;
			this.preset = TaskFilter.presetType.default;
			this.searchString = '';

			this.taskFilter = new TaskFilter();
			void this.taskFilter.fillPresets(this.projectId);

			this.clearCounters();
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
		 * @public
		 * @return {boolean}
		 */
		isDefaultPreset()
		{
			return this.preset === TaskFilter.presetType.default;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmptyPreset()
		{
			return (!this.preset || this.preset === TaskFilter.presetType.none);
		}

		/**
		 * @private
		 */
		clearCounters()
		{
			this.counters = {};

			Object.values(TaskFilter.roleType).forEach((role) => {
				this.counters[role] = {
					[TaskFilter.counterType.expired]: 0,
					[TaskFilter.counterType.newComments]: 0,
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

				Object.values(TaskFilter.roleType).forEach((role) => {
					const counter = data[this.projectId][role];
					this.counters[role] = {
						[TaskFilter.counterType.expired]: Number(counter[TaskFilter.counterType.expired]),
						[TaskFilter.counterType.newComments]: Number(counter[TaskFilter.counterType.newComments]),
					};
				});

				const onPullValue = Object.values(this.counters[TaskFilter.roleType.all]).reduce((a, b) => a + b, 0);
				const potentialValue = onPullValue + FieldChangeRegistry.getCounter();

				if (potentialValue === 0)
				{
					this.counters[TaskFilter.roleType.all] = {
						[TaskFilter.counterType.expired]: 0,
						[TaskFilter.counterType.newComments]: 0,
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

				Object.values(TaskFilter.roleType).forEach((role) => {
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
					if (!result[TaskFilter.roleType.all].answer.result)
					{
						return;
					}

					this.clearCounters();
					this.counterValue = 0;

					Object.values(TaskFilter.roleType).forEach((role) => {
						const roleCounters = result[role].answer.result;
						this.counters[role] = {
							[TaskFilter.counterType.expired]: Number(roleCounters[TaskFilter.counterType.expired].counter),
							[TaskFilter.counterType.newComments]: Number(roleCounters[TaskFilter.counterType.newComments].counter),
						};
					});
					this.updateCounterValue(
						Object.values(this.counters[TaskFilter.roleType.all]).reduce((a, b) => a + b, 0),
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

		isEmptyCounter()
		{
			return this.counterType === TaskFilter.counterType.none;
		}

		/**
		 * @public
		 * @param type
		 */
		setCounterType(type)
		{
			if (Object.values(TaskFilter.counterType).includes(type))
			{
				this.counterType = type;
			}
		}

		/**
		 * @public
		 * @param {string|null} preset
		 */
		setPreset(preset)
		{
			this.preset = preset;
			this.setRole(this.taskFilter.getRoleByPreset(preset) || TaskFilter.roleType.all);
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getPreset()
		{
			return this.preset;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getPresetName()
		{
			return this.taskFilter.getPresetNameById(this.preset);
		}

		/**
		 * @public
		 * @param {string} str
		 */
		setSearchString(str)
		{
			this.searchString = str;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getSearchString()
		{
			return this.searchString;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isSearchStringEmpty()
		{
			return this.searchString === '';
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRoleForAll()
		{
			return this.role === TaskFilter.roleType.all;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getRole()
		{
			return this.role;
		}

		/**
		 * @private
		 * @param role
		 */
		setRole(role)
		{
			this.role = role;
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
	}

	module.exports = { Filter };
});

(() => {
	const require = (extension) => jn.require(extension);
	const { EntityReady } = require('entity-ready');
	const { Entry } = require('tasks/entry');
	const { ErrorLogger } = require('utils/logger/error-logger');
	const { StorageCache } = require('storage-cache');
	const { qrauth } = require('qrauth/utils');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { TasksNavigator } = require('tasks/navigator');

	const SITE_ID = BX.componentParameters.get('SITE_ID', 's1');

	const tasksNavigator = new TasksNavigator();
	tasksNavigator.unsubscribeFromPushNotifications();
	tasksNavigator.subscribeToPushNotifications();

	class Pull
	{
		/**
		 * @param {TasksTabs} tabs
		 * @param {number} userId
		 */
		constructor(tabs, userId)
		{
			this.tabs = tabs;
			this.userId = userId;
			this.canUpdateTasksCounterOnPull = true;

			this.queue = new Set();
			this.canExecute = true;
		}

		getEventHandlers()
		{
			return {
				user_efficiency_counter: {
					method: this.onUserEfficiencyCounter,
					context: this,
				},
				user_counter: {
					method: this.onUserCounter,
					context: this,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: (data) => this.processPullEvent(data),
			});
		}

		processPullEvent(data)
		{
			if (this.canExecute)
			{
				void this.executePullEvent(data);
			}
			else
			{
				this.queue.add(data);
			}
		}

		executePullEvent(data)
		{
			return new Promise((resolve, reject) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const { command, params } = data;
				if (has.call(eventHandlers, command))
				{
					const { method, context } = eventHandlers[command];
					if (method)
					{
						method.apply(context, [params]).then(() => resolve(), () => reject()).catch(() => reject());
					}
				}
			});
		}

		freeQueue()
		{
			const commonCommands = new Set([
				'user_efficiency_counter',
				'user_counter',
			]);
			this.queue = new Set([...this.queue].filter((event) => commonCommands.has(event.command)));

			const clearDuplicates = (accumulator, event) => {
				const result = accumulator;
				if (
					typeof accumulator[event.command] === 'undefined'
					|| event.extra.server_time_ago < accumulator[event.command].extra.server_time_ago
				)
				{
					result[event.command] = event;
				}

				return result;
			};
			this.queue = new Set(
				Object.values([...this.queue].reduce((accumulator, event) => clearDuplicates(accumulator, event), {})),
			);

			const promises = [...this.queue].map((event) => this.executePullEvent(event));

			return Promise.allSettled(promises);
		}

		clear()
		{
			this.queue.clear();
		}

		setCanExecute(canExecute)
		{
			this.canExecute = canExecute;
		}

		onUserCounter(data)
		{
			return new Promise((resolve) => {
				if (Number(data.userId) !== Number(this.userId))
				{
					resolve();

					return;
				}

				if (this.canUpdateTasksCounterOnPull)
				{
					TasksTabs.setDownMenuTasksCounter(data[0].view_all.total);
					this.tabs.updateTasksCounter(data[0].view_all.total);
				}
				this.tabs.updateProjectsCounter(data.projects_major);
				this.tabs.updateScrumCounter(data.scrum_total_comments);

				resolve();
			});
		}

		onUserEfficiencyCounter(data)
		{
			return new Promise((resolve) => {
				this.tabs.updateEfficiencyCounter(data.value);
				resolve();
			});
		}
	}

	class TasksTabs
	{
		static createGuid()
		{
			const s4 = function() {
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
			};

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		static setDownMenuTasksCounter(value = -1)
		{
			const taskListCache = new StorageCache('tasksTaskList', 'filterCounters_0');

			if (value >= 0)
			{
				Application.setBadges({ tasks: value });
				taskListCache.set({ counterValue: value });

				return;
			}

			let counterValue = 0;

			const cachedCounters = Application.sharedStorage().get('userCounters');
			if (cachedCounters)
			{
				try
				{
					const counters = JSON.parse(cachedCounters)[SITE_ID];
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

		constructor(tabs)
		{
			this.tabs = tabs;
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.showScrumList = BX.componentParameters.get('SHOW_SCRUM_LIST', false);
			this.tabCodes = BX.componentParameters.get('TAB_CODES');
			this.guid = TasksTabs.createGuid();

			this.pull = new Pull(this, this.userId);
			this.pull.subscribe();

			this.logger = new ErrorLogger();

			TasksTabs.setDownMenuTasksCounter();
			EntityReady.wait('chat')
				.then(() => setTimeout(() => TasksTabs.setDownMenuTasksCounter(), 1000))
				.catch(() => {})
			;

			tariffPlanRestrictionsReady()
				.then(() => {
					BX.onViewLoaded(() => {
						this.bindEvents();
						this.updateCounters();
					});
				})
				.catch(this.logger.error)
			;
		}

		bindEvents()
		{
			this.tabs.on('onTabSelected', (tab, changed) => this.onTabSelected(tab, changed));

			BX.addCustomEvent('onAppActiveBefore', () => this.onAppActiveBefore());
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());

			BX.addCustomEvent('tasks.dashboard:pullSubscribed', () => {
				this.pull.canUpdateTasksCounterOnPull = false;
			});

			BX.addCustomEvent('tasks.list:setVisualCounter', (data) => {
				if (data.guid === this.guid || !data.guid)
				{
					this.updateTasksCounter(data.value);
				}
			});
			BX.addCustomEvent('flows.list:setVisualCounter', (data) => this.updateFlowsCounter(data.value));
			BX.addCustomEvent('tasks.project.list:setVisualCounter', (data) => this.updateProjectsCounter(data.value));
			BX.addCustomEvent('tasks.scrum.list:setVisualCounter', (data) => this.updateScrumCounter(data.value));
		}

		onAppPaused()
		{
			BX.postComponentEvent('tasks.tabs:onAppPaused', [{ tabId: this.tabs.getCurrentItem().id }]);

			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
		}

		onAppActiveBefore()
		{
			BX.postComponentEvent('tasks.tabs:onAppActiveBefore', [{ tabId: this.tabs.getCurrentItem().id }]);
		}

		onAppActive()
		{
			BX.postComponentEvent('tasks.tabs:onAppActive', [{ tabId: this.tabs.getCurrentItem().id }]);

			this.activationTime = new Date();

			if (this.pauseTime)
			{
				const timePassed = this.activationTime.getTime() - this.pauseTime.getTime();
				const minutesPassed = Math.abs(Math.round(timePassed / 60000));

				if (minutesPassed > 29)
				{
					this.updateCounters();

					this.pull.setCanExecute(true);
					this.pull.clear();
				}
				else
				{
					this.pull.setCanExecute(true);
					void this.pull.freeQueue();
				}
			}
		}

		onTabSelected(tab, changed)
		{
			const tabId = tab.id;

			if (changed)
			{
				BX.postComponentEvent('tasks.tabs:onTabSelected', [{ tabId }]);

				return;
			}

			switch (tabId)
			{
				case this.tabCodes.PROJECTS:
				{
					const { isRestricted, showRestriction } = getFeatureRestriction('socialnetwork_projects_groups');
					if (isRestricted())
					{
						showRestriction();
					}
					break;
				}

				case this.tabCodes.SCRUM:
					qrauth.open({
						redirectUrl: `/company/personal/user/${this.userId}/tasks/scrum/`,
						showHint: true,
						title: BX.message('MOBILE_TASKS_TABS_TAB_SCRUM'),
						analyticsSection: 'tasks',
					});
					break;

				case this.tabCodes.EFFICIENCY:
					void Entry.openEfficiency({ userId: this.userId });
					break;

				default:
					// no default
					break;
			}
		}

		updateCounters()
		{
			const cachedCounters = Application.sharedStorage().get('userCounters');
			if (cachedCounters)
			{
				try
				{
					const counters = JSON.parse(cachedCounters)[SITE_ID];
					if (counters)
					{
						this.updateTasksCounter(counters.tasks_total);
						this.updateEfficiencyCounter(counters.tasks_effective);
					}
				}
				catch
				{
					// do nothing
				}
			}

			const projectListStorage = new StorageCache('tasks_project', 'filterCounters');
			const cachedProjectCounters = projectListStorage.get();
			if (cachedProjectCounters)
			{
				this.updateProjectsCounter(cachedProjectCounters.counterValue);
			}

			const scrumListStorage = new StorageCache('tasks_scrum', 'filterCounters');
			const cachedScrumCounters = scrumListStorage.get();
			if (cachedScrumCounters)
			{
				this.updateScrumCounter(cachedScrumCounters.counterValue);
			}

			const flowListStorage = new StorageCache('tasks_flow', 'filterCounters');
			const cachedFlowCounters = flowListStorage.get();
			if (cachedFlowCounters)
			{
				this.updateFlowsCounter(cachedFlowCounters.counterValue);
			}

			new RunActionExecutor('tasksmobile.Task.Counter.getByType')
				.setHandler((response) => {
					const counters = response.data;

					const flowsCounter = counters.flowTotal;
					this.updateFlowsCounter(flowsCounter);
					flowListStorage.set({ counterValue: flowsCounter });

					const projectCounter = counters.sonetTotalExpired + counters.sonetTotalComments;
					this.updateProjectsCounter(projectCounter);
					projectListStorage.set({ counterValue: projectCounter });

					if (this.showScrumList)
					{
						const scrumCounter = counters.scrumTotalComments;
						this.updateScrumCounter(scrumCounter);
						scrumListStorage.set({ counterValue: scrumCounter });
					}

					this.updateEfficiencyCounter(counters.effective);
				})
				.call(false)
			;
		}

		updateFlowsCounter(value)
		{
			this.tabs.updateItem(this.tabCodes.FLOW, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_FLOWS'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateTasksCounter(value)
		{
			this.tabs.updateItem(this.tabCodes.TASKS, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_TASKS'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateProjectsCounter(value)
		{
			const isProjectRestricted = getFeatureRestriction('socialnetwork_projects_groups').isRestricted();

			this.tabs.updateItem(this.tabCodes.PROJECTS, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_PROJECTS'),
				counter: Number(value),
				label: (!isProjectRestricted && value > 0 ? String(value) : ''),
			});
		}

		updateScrumCounter(value)
		{
			if (!this.showScrumList)
			{
				return;
			}

			this.tabs.updateItem(this.tabCodes.SCRUM, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_SCRUM'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateEfficiencyCounter(value)
		{
			const isEfficiencyRestricted = getFeatureRestriction(FeatureId.EFFICIENCY).isRestricted();

			this.tabs.updateItem(this.tabCodes.EFFICIENCY, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_EFFICIENCY'),
				label: (!isEfficiencyRestricted && value >= 0 ? `${String(value)}%` : ''),
				selectable: false,
			});
		}
	}

	return new TasksTabs(tabs);
})();

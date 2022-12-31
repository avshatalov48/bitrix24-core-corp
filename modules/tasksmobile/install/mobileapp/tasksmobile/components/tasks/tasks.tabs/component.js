(() => {
	const SITE_ID = BX.componentParameters.get('SITE_ID', 's1');

	class Pull
	{
		/**
		 * @param {TasksTabs} tabs
		 * @param {Integer} userId
		 */
		constructor(tabs, userId)
		{
			this.tabs = tabs;
			this.userId = userId;

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
				callback: data => this.processPullEvent(data),
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
				const {command, params} = data;
				if (has.call(eventHandlers, command))
				{
					const {method, context} = eventHandlers[command];
					if (method)
					{
						method.apply(context, [params]).then(() => resolve(), () => reject()).catch(() => reject());
					}
				}
			});
		}

		freeQueue()
		{
			const commonCommands = [
				'user_efficiency_counter',
				'user_counter',
			];
			this.queue = new Set([...this.queue].filter(event => commonCommands.includes(event.command)));

			const clearDuplicates = (result, event) => {
				if (
					typeof result[event.command] === 'undefined'
					|| event.extra.server_time_ago < result[event.command].extra.server_time_ago
				)
				{
					result[event.command] = event;
				}
				return result;
			};
			this.queue = new Set(Object.values([...this.queue].reduce(clearDuplicates, {})));

			const promises = [...this.queue].map(event => this.executePullEvent(event));
			return Promise.allSettled(promises);
		}

		clear()
		{
			this.queue.clear();
		}

		getCanExecute()
		{
			return this.canExecute;
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

				this.tabs.updateTasksCounter(data[0].view_all.total);
				this.tabs.updateProjectsCounter(data.projects_major);

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
		static get tabNames()
		{
			return {
				tasks: 'tasks.list',
				projects: 'tasks.project.list',
				scrum: 'tasks.scrum.list',
				efficiency: 'tasks.efficiency',
			};
		}

		constructor(tabs)
		{
			this.tabs = tabs;
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.pull = new Pull(this, this.userId);
			this.guid = this.createGuid();

			this.setDownMenuTasksCounter();

			BX.onViewLoaded(() => {
				this.bindEvents();
				this.updateCounters();
			});
		}

		createGuid()
		{
			const s4 = function() {
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			};

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		bindEvents()
		{
			this.tabs.on('onTabSelected', (tab, changed) => this.onTabSelected(tab,  changed));

			BX.addCustomEvent('onAppActiveBefore', () => this.onAppActiveBefore());
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());

			BX.addCustomEvent('tasks.list:setVisualCounter', (data) => {
				if (data.guid === this.guid || !data.guid)
				{
					this.updateTasksCounter(data.value);
				}
			});
			BX.addCustomEvent('tasks.project.list:setVisualCounter', data => this.updateProjectsCounter(data.value));

			this.pull.subscribe();
		}

		onAppPaused()
		{
			BX.postComponentEvent('tasks.tabs:onAppPaused', [], this.tabs.getCurrentItem().id);

			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
		}

		onAppActiveBefore()
		{
			BX.postComponentEvent('tasks.tabs:onAppActiveBefore', [], this.tabs.getCurrentItem().id);
		}

		onAppActive()
		{
			BX.postComponentEvent('tasks.tabs:onAppActive', [], this.tabs.getCurrentItem().id);

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
				BX.postComponentEvent('tasks.tabs:onTabSelected', [{tabId}], TasksTabs.tabNames.tasks);
				BX.postComponentEvent('tasks.tabs:onTabSelected', [{tabId}], TasksTabs.tabNames.projects);
			}
			else if (tabId === TasksTabs.tabNames.scrum)
			{
				qrauth.open({
					redirectUrl: `/company/personal/user/${this.userId}/tasks/scrum/`,
					showHint: true,
					title: BX.message('MOBILE_TASKS_TABS_TAB_SCRUM'),
				});
			}
			else if (tabId === TasksTabs.tabNames.efficiency)
			{
				BX.postComponentEvent('taskbackground::efficiency::open', [{userId: this.userId}]);
			}
		}

		setDownMenuTasksCounter()
		{
			const storage = Application.sharedStorage('tasksTaskList');
			const cache = storage.get('filterCounters_0');

			if (typeof cache === 'string')
			{
				Application.setBadges({tasks: (JSON.parse(cache).counterValue || 0)});
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
				catch (e)
				{
					//do nothing
				}
			}

			const projectCountersStorage = Application.sharedStorage('tasksProjectList');
			const cachedProjectCounters = projectCountersStorage.get('filterCounters');
			if (cachedProjectCounters)
			{
				try
				{
					const counters = JSON.parse(cachedProjectCounters);
					if (counters)
					{
						this.updateProjectsCounter(counters.counterValue);
					}
				}
				catch (e)
				{
					// do nothing
				}
			}

			(new RequestExecutor('tasks.task.counters.getProjectsTotalCounter'))
				.call()
				.then(
					(response) => {
						const counterValue = response.result;
						this.updateProjectsCounter(counterValue);
						projectCountersStorage.set('filterCounters', JSON.stringify({counterValue}));
					},
					response => console.error(response)
				)
			;
		}

		updateTasksCounter(value)
		{
			this.tabs.updateItem(TasksTabs.tabNames.tasks, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_TASKS'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateProjectsCounter(value)
		{
			this.tabs.updateItem(TasksTabs.tabNames.projects, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_PROJECTS'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateEfficiencyCounter(value)
		{
			this.tabs.updateItem(TasksTabs.tabNames.efficiency, {
				title: BX.message('MOBILE_TASKS_TABS_TAB_EFFICIENCY'),
				label: (value || value === 0 ? `${String(value)}%` : ''),
				selectable: false,
			});
		}
	}

	return new TasksTabs(tabs);
})();
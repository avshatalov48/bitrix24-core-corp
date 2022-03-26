(() => {
	const SITE_ID = BX.componentParameters.get('SITE_ID', 's1');

	class StreamTabs
	{
		static get tabNames()
		{
			return {
				stream: 'stream',
				disk: 'disk',
				bp: 'bp',
				calendar: 'calendar',
				video: 'video',
				mail: 'mail',
			};
		}

		static get counterKeys()
		{
			return {
				livefeed: '**',
				bp: 'bp_tasks',
			};
		}

		constructor(tabs)
		{
			this.tabs = tabs;
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.pull = new Pull(this, this.userId);
			this.pauseTime = null;
			this.activationTime = null;

			BX.onViewLoaded(() => {
				this.bindEvents();
				this.updateCounters();
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());
			BX.addCustomEvent('project.background::showLoadingIndicator', dialogs.showLoadingIndicator);
			BX.addCustomEvent('project.background::hideLoadingIndicator', dialogs.hideLoadingIndicator);

			this.tabs.on('onTabSelected', (tab, changed) => this.onTabSelected(tab,  changed));

			BX.addCustomEvent('onStreamTabsCalendarEventAddButtonPushed', () => {
				PageManager.openPage({
					url: '/mobile/calendar/edit_event.php',
					modal: true,
					data: {
						modal: 'Y',
					}
				});
			});

			BX.addCustomEvent('onUpdateUserCounters', (data) => this.onUpdateUserCounter(data));
			BX.addCustomEvent('onTabLoaded', this.hideProgress.bind(this));

			this.pull.subscribe();
		}

		onAppPaused()
		{
			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
		}

		onAppActive()
		{
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

			if (tabId === StreamTabs.tabNames.calendar)
			{
				PageManager.openList({
					url: `/mobile/?mobile_action=calendar&user_id=${this.userId}`,
					table_id: 'calendar_list',
					table_settings: {
						showTitle: 'YES',
						name: BX.message('MOBILE_STREAM_TABS_CALENDAR_TITLE'),
						useTagsInSearch: 'NO',
						button: {
							type: 'plus',
							eventName: 'onStreamTabsCalendarEventAddButtonPushed',
						},
					}
				});
			}
			else if (tabId === StreamTabs.tabNames.bp)
			{
				this.showProgress();
				BX.postWebEvent('stream.tabs::onBPTabSelected');
			}
			else if (tabId === StreamTabs.tabNames.video)
			{
				qrauth.open({
					redirectUrl: BX.componentParameters.get('VIDEO_WEB_PATH', `/conference/`),
					showHint: true,
					title: BX.message('MOBILE_STREAM_TABS_VIDEO_TITLE'),
				});
			}
			else if (tabId === StreamTabs.tabNames.mail)
			{
				qrauth.open({
					redirectUrl: BX.componentParameters.get('MAIL_WEB_PATH', `/mail/`),
					showHint: true,
					title: BX.message('MOBILE_STREAM_TABS_MAIL_TITLE'),
				});
			}
		}

		showProgress()
		{
			this.tabs.setTitle({
				useProgress: true,
			}, true);
		}

		hideProgress()
		{
			this.tabs.setTitle({
				useProgress: false,
			}, true);
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
						this.processCounters(counters);
					}
				}
				catch (e)
				{
					//do nothing
				}
			}
		}

		onUpdateUserCounter(data)
		{
			if (typeof data[SITE_ID] === 'undefined')
			{
				return;
			}

			this.processCounters(data[SITE_ID]);
		}

		processCounters(counters)
		{
			if (typeof counters[StreamTabs.counterKeys.livefeed] !== 'undefined')
			{
				this.updateLivefeedCounter(counters[StreamTabs.counterKeys.livefeed]);
			}
			if (typeof counters[StreamTabs.counterKeys.bp] !== 'undefined')
			{
				this.updateBPCounter(counters[StreamTabs.counterKeys.bp]);
			}
		}

		updateLivefeedCounter(value)
		{
			this.tabs.updateItem(StreamTabs.tabNames.stream, {
				title: BX.message('MOBILE_STREAM_TABS_STREAM_TITLE'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}

		updateBPCounter(value)
		{
			this.tabs.updateItem(StreamTabs.tabNames.bp, {
				title: BX.message('MOBILE_STREAM_TABS_BP_TITLE'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			});
		}
	}

	class Pull
	{
		/**
		 * @param {StreamTabs} tabs
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
				user_counter: {
					method: this.onUserCounter,
					context: this,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'main',
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
				if (typeof data[SITE_ID] === 'undefined')
				{
					resolve();
					return;
				}

				this.tabs.processCounters(data[SITE_ID]);

				resolve();
			});
		}
	}

	return new StreamTabs(tabs);
})();
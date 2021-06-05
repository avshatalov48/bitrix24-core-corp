import {VuexBuilder} from "ui.vue.vuex";
import {MonitorModel} from './model/monitor';
import {EventHandler} from './eventhandler';
import {Sender} from './sender';
import {Report} from './report/report';
import {Logger} from './lib/logger';
import {Debug} from './lib/debug';
import {DateFormatter} from "timeman.dateformatter";
import {TimeFormatter} from "timeman.timeformatter";

import {PULL as Pull} from 'pull.client';
import {CommandHandler} from './lib/commandhandler';
import {Loc} from 'main.core';

class Monitor
{
	init(options)
	{
		this.enabled = options.enabled;
		this.state = options.state;
		this.isHistorySent = options.isHistorySent;
		this.isAway = false;
		this.isAppInit = false;
		this.vuex = {};

		this.defaultStorageConfig = {
			config: {
				otherTime: options.otherTime,
				shortAbsenceTime: options.shortAbsenceTime,
			}
		};

		this.dateFormat = options.dateFormat;
		this.timeFormat = options.timeFormat;

		if (options.debugEnabled)
		{
			Debug.enable();
		}

		Debug.space();
		Debug.log('Desktop launched!');

		if (this.isEnabled() && Logger.isEnabled())
		{
			BXDesktopSystem.LogInfo = function() {};
			Logger.start();
		}

		Debug.log(`Enabled: ${this.enabled}`);

		Logger.warn('History sent status: ', this.isHistorySent);
		Debug.log(`History sent status: ${this.isHistorySent}`);

		this.removeDeprecatedStorage();

		this.initApp();

		Pull.subscribe(new CommandHandler());
	}

	initApp()
	{
		if (!this.isEnabled())
		{
			return;
		}

		return new Promise((resolve, reject) =>
		{
			this.initStorage()
				.then(builder => {
					this.vuex.store = builder.store;
					this.vuex.models = builder.models;
					this.vuex.builder = builder.builder;

					this.vuex.store.dispatch('monitor/processUnfinishedEvents').then(() => {
						this.initTracker(this.getStorage());

						this.isAppInit = true;

						resolve();
					});
				})
				.catch(() => {
					const errorMessage = "PWT: Storage initialization error";

					Logger.error(errorMessage);
					Debug.log(errorMessage);

					reject();
				});
		});
	}

	initStorage()
	{
		return new VuexBuilder()
			.addModel(
				MonitorModel
					.create()
					.setVariables(this.defaultStorageConfig)
					.useDatabase(true)
			)
			.setDatabaseConfig({
				name: 'timeman-pwt',
				type: VuexBuilder.DatabaseType.indexedDb,
				siteId: Loc.getMessage('SITE_ID'),
				userId: Loc.getMessage('USER_ID')
			})
			.build();
	}

	getStorage()
	{
		return (this.vuex.hasOwnProperty('store') ? this.vuex.store : null);
	}

	initTracker(store)
	{
		DateFormatter.init(this.dateFormat);
		TimeFormatter.init(this.timeFormat);

		EventHandler.init(store);
		Sender.init(store);

		BX.desktop.addCustomEvent(
			'BXUserAway',
			(away) => this.onAway(away)
		);

		BX.MessengerWindow.addTab({
			id: 'timeman-pwt',
			title: Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE'),
			order: 540,
			target: false,
			events: {
				open: () => this.openReport()
			}
		});

		BX.desktop.addCustomEvent(
			'BXProtocolUrl',
			command => {
				if (command === 'timemanpwt')
				{
					if (!BX.MessengerCommon.isDesktop())
					{
						return false;
					}

					BX.MessengerWindow.changeTab('timeman-pwt', true);
					BX.desktop.setActiveWindow();
					BX.desktop.windowCommand("show");
				}
			}
		);

		if (this.isEnabled())
		{
			this.afterTrackerInit();

			if (this.isWorkingDayStarted())
			{
				this.start();
			}
			else
			{
				Logger.warn('Monitor: Zzz...');
			}
		}
		else
		{
			Logger.warn('Monitor is disabled');
		}
	}

	openReport()
	{
		if (!this.isEnabled())
		{
			return;
		}

		Report.open(this.getStorage());
	}

	openReportPreview()
	{
		if (!this.isEnabled())
		{
			return;
		}

		Report.openPreview(this.getStorage());
	}

	start()
	{
		if (!this.isEnabled())
		{
			Logger.warn("Can't start, monitor is disabled!");
			Debug.log("Can't start, monitor is disabled!");

			return;
		}

		if (!this.isWorkingDayStarted())
		{
			Logger.warn("Can't start monitor, working day is stopped!");
			Debug.log("Can't start monitor, working day is stopped!");

			return;
		}

		if (!this.isAppInit)
		{
			this.initApp().then(() => this.startTracker());
		}
		else
		{
			this.startTracker();
		}
	}

	startTracker()
	{
		if (!this.isAppInit)
		{
			return;
		}

		Debug.log('Monitor started');
		Debug.space();

		if (this.isTrackerEventsApiAvailable())
		{
			Logger.log('Events started');
			BXDesktopSystem.TrackerStart();
		}

		this.afterTrackerInit();

		BX.ajax.runAction('bitrix:timeman.api.monitor.setStatusWaitingData')
			.then(() => {
				this.isHistorySent = false;
			});

		EventHandler.start();
		Sender.start();

		Logger.warn('Monitor started');
	}

	stop()
	{
		if (this.isTrackerEventsApiAvailable())
		{
			Logger.log('Events stopped');
			BXDesktopSystem.TrackerStop();
		}

		EventHandler.stop();
		Sender.stop();

		Logger.warn('Monitor stopped');
		Debug.log('Monitor stopped');
	}

	isTrackerEventsApiAvailable()
	{
		return (BX.desktop.getApiVersion() >= 55);
	}

	onAway(away)
	{
		if (!this.isEnabled() || !this.isWorkingDayStarted())
		{
			return;
		}

		if (away && !this.isAway)
		{
			this.isAway = true;

			Logger.warn('User AWAY');
			Debug.space();
			Debug.log('User AWAY');

			this.stop();

			EventHandler.catchAbsence(away);
		}
		else if (!away && this.isAway)
		{
			this.isAway = false;

			Logger.warn('User RETURNED, continue monitoring...');
			Debug.space();
			Debug.log('User RETURNED, continue monitoring...');

			this.start();
		}
	}

	send()
	{
		if (!this.vuex.hasOwnProperty('store'))
		{
			Logger.warn('Unable to send report. Store is not initialized.');
			Debug.log('Unable to send report. Store is not initialized.');

			return;
		}

		this.vuex.store.dispatch('monitor/createSentQueue').then(() => Sender.send());
	}

	isWorkingDayStarted()
	{
		return (this.getState() === this.getStateStart())
	}

	setState(state)
	{
		this.state = state;
	}

	getState()
	{
		return this.state;
	}

	isEnabled()
	{
		return (this.enabled === this.getStatusEnabled())
	}

	isInactive()
	{
		return !(
			this.isEnabled()
			|| this.getStorage() !== null
			|| this.isAppInit
		);
	}

	enable()
	{
		this.enabled = this.getStatusEnabled();
	}

	disable()
	{
		this.stop();

		BX.MessengerWindow.hideTab('timeman-pwt');
		this.isAppInit = false;
		this.vuex = {};

		this.enabled = this.getStatusDisabled();
	}

	getStatusEnabled()
	{
		return 'Y';
	}

	getStatusDisabled()
	{
		return 'N';
	}

	getStateStart()
	{
		return 'start';
	}

	getStateStop()
	{
		return 'stop';
	}

	removeDeprecatedStorage()
	{
		if (BX.desktop.getLocalConfig('bx_timeman_monitor_history'))
		{
			BX.desktop.removeLocalConfig('bx_timeman_monitor_history');

			Logger.log(`Deprecated storage has been cleared`);
			Debug.log(`Deprecated storage has been cleared`);
		}
	}

	afterTrackerInit()
	{
		let currentDateLog = new Date(MonitorModel.prototype.getDateLog());
		let reportDateLog = new Date(this.vuex.store.state.monitor.reportState.dateLog);

		if (
			currentDateLog > reportDateLog
			&& this.isHistorySent
		)
		{
			Logger.warn('The next day came. Clearing the history and changing the date of the report.');
			Debug.space();
			Debug.log('The next day came. Clearing the history and changing the date of the report.');

			this.vuex.store.dispatch('monitor/clearStorage')
				.then(() => {
					this.vuex.store.dispatch('monitor/setDateLog', MonitorModel.prototype.getDateLog());
				});
		}
	}
}

const monitor = new Monitor();

export {monitor as Monitor};
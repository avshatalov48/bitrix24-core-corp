import {VuexBuilder} from "ui.vue.vuex";
import {MonitorModel} from './model/monitor';
import {EventHandler} from './eventhandler';
import {Sender} from './sender';
import {MonitorReport} from 'timeman.monitor-report';
import {Logger} from './lib/logger';
import {Debug} from './lib/debug';
import {DateFormatter} from "timeman.dateformatter";
import {TimeFormatter} from "timeman.timeformatter";

import {PULL as Pull} from 'pull.client';
import {CommandHandler} from './lib/commandhandler';
import {Loc, Type} from 'main.core';
import {DesktopApi} from 'im.v2.lib.desktop-api';

class Monitor
{
	init(options)
	{
		this.enabled = options.enabled;
		this.playTimeout = null;
		this.isAway = false;
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

		if (this.isEnabled())
		{
			this.initApp();
		}

		Pull.subscribe(new CommandHandler());
	}

	initApp()
	{
		if (!this.isEnabled())
		{
			return;
		}

		new VuexBuilder()
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
			.build()
			.then(builder => {
				this.vuex.store = builder.store;

				this.getStorage().dispatch('monitor/processUnfinishedEvents')
					.then(() => this.initTracker(this.getStorage()));
			})
			.catch(() => {
				const errorMessage = "PWT: Storage initialization error";

				Logger.error(errorMessage);
				Debug.log(errorMessage);
			})
		;
	}

	initTracker(store)
	{
		DateFormatter.init(this.dateFormat);
		TimeFormatter.init(this.timeFormat);

		EventHandler.init(store);
		Sender.init(store);

		DesktopApi.subscribe(
			'BXUserAway',
			(away) => this.onAway(away),
		);

		if (BX.MessengerWindow && BX.MessengerWindow.addTab)
		{
			BX.MessengerWindow.addTab({
				id: 'timeman-pwt',
				title: Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE'),
				order: 540,
				target: false,
				events: {
					open: () => this.openReport(),
				},
			});
		}

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
			this.launch();
		}
		else
		{
			Logger.warn('Monitor is disabled');
		}
	}

	launch()
	{
		if (this.isAway)
		{
			Logger.log('Pause is over, but computer is in sleep mode. Waiting for the return of the user.');
			Debug.log('Pause is over, but computer is in sleep mode. Waiting for the return of the user.');

			return;
		}

		if (!this.getStorage().state.monitor.config.grantingPermissionDate)
		{
			Logger.log('History access not provided. Monitor is not started.');
			Debug.log('History access not provided. Monitor is not started.');

			if (this.shouldShowGrantingPermissionWindow())
			{
				this.openReport();
				BXDesktopWindow.ExecuteCommand('show.active');
			}

			return;
		}

		this.getStorage().dispatch('monitor/migrateHistory').then(() => {
			this.getStorage().dispatch('monitor/clearSentHistory').then(() => {
				this.getStorage().dispatch('monitor/refreshDateLog').then(() => {
					if (this.isPaused())
					{
						if (this.isPauseRelevant())
						{
							Logger.warn("Can't start, monitor is paused!");
							Debug.log("Can't start, monitor is paused!");

							this.setPlayTimeout();
							return;
						}

						this.clearPausedUntil().then(() => this.start());
						return;
					}

					this.start();
				});
			});
		});
	}

	start()
	{
		if (!this.isEnabled())
		{
			Logger.warn("Can't start, monitor is disabled!");
			Debug.log("Can't start, monitor is disabled!");

			return;
		}

		if (this.isPaused())
		{
			Logger.warn("Can't start, monitor is paused!");
			Debug.log("Can't start, monitor is paused!");

			return;
		}

		Debug.log('Monitor started');
		Debug.space();

		if (this.isTrackerEventsApiAvailable())
		{
			Logger.log('Events started');
			BXDesktopSystem.TrackerStart();
		}

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

	pause()
	{
		this.stop();
		this.setPlayTimeout();
	}

	onAway(away)
	{
		if (!this.isEnabled() || this.isPaused())
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

			this.launch();
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

		this.getStorage().dispatch('monitor/createSentQueue').then(() => Sender.send());
	}

	openReport()
	{
		if (!this.isEnabled())
		{
			return;
		}

		MonitorReport.open(this.getStorage());
	}

	openReportPreview()
	{
		if (!this.isEnabled())
		{
			return;
		}

		MonitorReport.openPreview(this.getStorage());
	}

	getPausedUntilTime()
	{
		return this.getStorage().state.monitor.config.pausedUntil;
	}

	clearPausedUntil()
	{
		return this.getStorage().dispatch('monitor/clearPausedUntil');
	}

	isPaused()
	{
		return !!this.getPausedUntilTime();
	}

	isPauseRelevant()
	{
		return this.getPausedUntilTime() - new Date() > 0;
	}

	setPlayTimeout()
	{
		Logger.warn(`Monitor will be turned on at ${this.getPausedUntilTime().toString()}`);
		Debug.log(`Monitor will be turned on at ${this.getPausedUntilTime().toString()}`);

		clearTimeout(this.playTimeout);

		this.playTimeout = setTimeout(
			() => this.clearPausedUntil().then(() => this.launch()),
			this.getPausedUntilTime() - new Date()
		);
	}

	pauseUntil(dateTime: Date)
	{
		if (
			Type.isDate(dateTime)
			&& Type.isNumber(dateTime.getTime())
			&& dateTime > new Date()
		)
		{
			this.getStorage().dispatch('monitor/setPausedUntil', dateTime).then(() => this.pause());
		}
		else
		{
			throw Error('Pause must be set as a date in the future');
		}
	}

	shouldShowGrantingPermissionWindow()
	{
		let config = this.getStorage().state.monitor.config;
		if (!config)
		{
			return false;
		}

		if (config.grantingPermissionDate !== null)
		{
			return false;
		}

		let deferredGrantingPermissionShowDate = config.deferredGrantingPermissionShowDate;
		if (deferredGrantingPermissionShowDate === null)
		{
			return true;
		}

		return new Date(MonitorModel.prototype.getDateLog()) >= new Date(deferredGrantingPermissionShowDate);
	}

	showGrantingPermissionLater()
	{
		return this.getStorage().dispatch('monitor/showGrantingPermissionLater');
	}

	play()
	{
		clearTimeout(this.playTimeout);
		this.playTimeout = null;
		this.clearPausedUntil().then(() => this.launch());
	}

	isEnabled()
	{
		return (this.enabled === this.getStatusEnabled())
	}

	enable()
	{
		this.enabled = this.getStatusEnabled();
	}

	disable()
	{
		this.stop();

		BX.MessengerWindow.hideTab('timeman-pwt');
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

	isTrackerEventsApiAvailable()
	{
		return (DesktopApi.getApiVersion() >= 55);
	}

	getStorage()
	{
		return (this.vuex.hasOwnProperty('store') ? this.vuex.store : null);
	}
}

const monitor = new Monitor();

export {monitor as Monitor};

import {Logger} from './lib/logger';
import {Debug} from "./lib/debug";
import {ActionTimer} from './lib/action-timer';
import {Entity} from './model/entity';
import {EntityType} from 'timeman.const';
import {Loc} from 'main.core';
import {DesktopApi} from 'im.v2.lib.desktop-api';

class EventHandler
{
	init(store)
	{
		this.enabled = false;
		this.store = store;

		this.preFinishInterval = null;

		this.lastCaught = {
			name: null,
			url: null
		}
	}

	catch(process, name, title, url)
	{
		if (!this.enabled || !process)
		{
			return;
		}

		ActionTimer.start('CATCH_ENTITY');

		const type = this.getEntityTypeByEvent({process, name, title, url});

		let isBitrix24Desktop = false;
		if (type === EntityType.app)
		{
			if (['Bitrix24.exe', 'Bitrix24'].includes(this.getNameByProcess(process)))
			{
				isBitrix24Desktop = true;
			}
		}

		if (type !== EntityType.absence)
		{
			if (type === EntityType.app)
			{
				switch (name)
				{
					case 'Application Frame Host':
						name = title
						break;

					case 'StartMenuExperienceHost':
					case 'Search application':
						name = Loc.getMessage('TIMEMAN_PWT_WINDOWS_START_ALIAS');
						break;

					case 'Windows Shell Experience Host':
						name = Loc.getMessage('TIMEMAN_PWT_WINDOWS_NOTIFICATIONS_ALIAS');
						break;
				}
			}

			if (name === '')
			{
				name = this.getNameByProcess(process);
			}

			if (!this.isNewEvent(name, url))
			{
				return;
			}

			this.lastCaught = {
				name,
				url
			};
		}

		this.store.dispatch('monitor/addHistory', new Entity({type, name, title, url, isBitrix24Desktop}));
	}

	catchAbsence(away)
	{
		if (away)
		{
			this.store.dispatch(
				'monitor/addHistory',
				new Entity({
					type: EntityType.absence
				})
			);

			this.lastCaught = {
				name: EntityType.absence,
				url: null
			};
		}
		else
		{
			if (this.isWorkingDayStarted() && this.isTrackerGetActiveAppAvailable())
			{
				BXDesktopSystem.TrackerGetActiveApp();
			}
		}
	}

	catchAppClose()
	{
		Logger.warn('Application shutdown recognized. The last interval is finished.');
		Debug.log('Application shutdown recognized. The last interval is finished.');

		this.store.dispatch('monitor/finishLastInterval');
	}

	getEntityTypeByEvent(event)
	{
		let type = EntityType.unknown;

		if (event.url === 'unknown')
		{
			type = EntityType.unknown;
		}
		else if (event.url === 'incognito')
		{
			type = EntityType.incognito;
		}
		else if (event.url)
		{
			type = EntityType.site
		}
		else if (event.process !== '')
		{
			type = EntityType.app
		}

		return type;
	}

	getNameByProcess(process)
	{
		const separator = (process.includes('/') ? '/' : '\\');
		const path = process.split(separator);

		return path[path.length - 1];
	}

	isNewEvent(name, url)
	{
		if (this.url === '')
		{
			if (this.lastCaught.name === name)
			{
				return false;
			}
		}
		else
		{
			if (this.lastCaught.name === name && this.lastCaught.url === url)
			{
				return false;
			}
		}

		return true;
	}

	isTrackerGetActiveAppAvailable()
	{
		return (BX.desktop.getApiVersion() >= 56);
	}

	start()
	{
		if (this.enabled)
		{
			Logger.warn('EventHandler already started');
			return;
		}

		this.enabled = true;

		DesktopApi.subscribe(
			'BXUserApp',
			(process, name, title, url) => this.catch(process, name, title, url),
		);

		DesktopApi.subscribe(
			'BXExitApplication',
			this.catchAppClose.bind(this),
		);

		if (this.isTrackerGetActiveAppAvailable())
		{
			BXDesktopSystem.TrackerGetActiveApp();
		}

		this.preFinishInterval = setInterval(
			() => this.store.dispatch('monitor/preFinishLastInterval'),
			60000
		);

		Logger.log('EventHandler started');
	}

	stop()
	{
		if (!this.enabled)
		{
			Logger.warn('EventHandler already stopped');
			return;
		}

		this.enabled = false;

		this.lastCaught = {
			name: null,
			url: null
		}

		this.preFinishInterval = null;

		this.store.dispatch('monitor/finishLastInterval');

		Logger.log('EventHandler stopped');
	}
}

const eventHandler = new EventHandler();

export {eventHandler as EventHandler};
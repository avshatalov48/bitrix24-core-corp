import { ProgramManager } from './model/programmanager';
import { Logger } from './lib/logger';

class EventHandler
{
	init()
	{
		this.enabled = false;

		this.lastApp = {
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

		if (name === '')
		{
			name = this.getNameByProcess(process);
		}

		if (!this.isNewEvent(name, url))
		{
			return;
		}

		ProgramManager.add(name, title, url);
		this.lastApp = { name, url };
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
			if (this.lastApp.name === name)
			{
				return false;
			}
		}
		else
		{
			if (this.lastApp.name === name && this.lastApp.url === url)
			{
				return false;
			}
		}

		return true;
	}

	start()
	{
		if (this.enabled)
		{
			Logger.warn('EventHandler already started');
			return;
		}

		this.enabled = true;

		BX.desktop.addCustomEvent(
			'BXUserApp',
			(process, name, title, url) => this.catch(process, name, title, url)
		);

		BXDesktopSystem.ListScreenMedia((window) =>
		{
			for (let index = 1; index < window.length; index++)
			{
				if (window[index].id.includes('screen'))
				{
					continue;
				}

				ProgramManager.add(window[index].process, '', '');
				return true;
			}
		});

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
		ProgramManager.finishLastInterval();
		BX.desktop.setLocalConfig('bx_timeman_monitor_history', ProgramManager.history);

		Logger.log('EventHandler stopped');
	}
}

const eventHandler = new EventHandler();

export {eventHandler as EventHandler};
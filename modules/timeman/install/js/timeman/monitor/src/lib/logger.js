import {Type} from 'main.core';
import {Monitor} from '../monitor';

class Logger
{
	constructor()
	{
		this.storageKey = 'bx-timeman-monitor-logger-enabled';
		this.enabled = null;
	}

	start()
	{
		if (!Monitor.isEnabled())
		{
			return;
		}

		this.enabled = true;

		if (typeof window.localStorage !== 'undefined')
		{
			try
			{
				window.localStorage.setItem(this.storageKey, 'Y');
			}
			catch(e) {}
		}

		return this.enabled;
	}

	stop()
	{
		this.enabled = false;

		if (typeof window.localStorage !== 'undefined')
		{
			try
			{
				window.localStorage.removeItem(this.storageKey);
			}
			catch(e) {}
		}

		return this.enabled;
	}

	isEnabled()
	{
		if (!Monitor.isEnabled())
		{
			return false;
		}

		if (this.enabled === null)
		{
			if (typeof window.localStorage !== 'undefined')
			{
				try
				{
					this.enabled = window.localStorage.getItem(this.storageKey) === 'Y';
				}
				catch(e) {}
			}
		}

		return this.enabled === true;
	}

	log(...params)
	{
		if (this.isEnabled())
		{
			console.log(...params);
		}
	}

	info(...params)
	{
		if (this.isEnabled())
		{
			console.info(...params);
		}
	}

	warn(...params)
	{
		if (this.isEnabled())
		{
			console.warn(...params);
		}
	}

	error(...params)
	{
		console.error(...params);
	}

	trace(...params)
	{
		console.trace(...params);
	}
}

let logger = new Logger();

export {logger as Logger};
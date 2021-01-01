import {Type} from 'main.core';
import {EventHandler} from './eventhandler';
import {ProgramManager} from './model/programmanager';
import {Sender} from './sender';
import {Logger} from './lib/logger';
import {Debug} from './lib/debug';

import {PULL as Pull} from 'pull.client';
import {CommandHandler} from './lib/commandhandler';

class Monitor
{
	init(options)
	{
		this.enabled = options.enabled;
		this.state = options.state;
		this.bounceTimeout = options.bounceTimeout;
		this.sendTimeout = options.sendTimeout;
		this.resendTimeout = options.resendTimeout;

		Debug.space();
		Debug.log('Desktop launched!');

		if (this.isEnabled() && Logger.isEnabled())
		{
			BXDesktopSystem.LogInfo = function() {};
			Logger.start();
		}

		Debug.log(`Enabled: ${this.enabled}`);

		Pull.subscribe(new CommandHandler());

		ProgramManager.init(this.bounceTimeout);
		EventHandler.init();
		Sender.init(this.sendTimeout, this.resendTimeout);

		BX.desktop.addCustomEvent(
			'BXUserAway',
			(away) => this.onAway(away)
		);

		if (this.isEnabled())
		{
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

		Debug.log('Monitor started');
		Debug.space();

		EventHandler.start();
		Sender.start();

		Logger.warn('Monitor started');
	}

	stop()
	{
		EventHandler.stop();
		Sender.stop();

		Logger.warn('Monitor stopped');
		Debug.log('Monitor stopped');
	}

	onAway(away)
	{
		if (!this.isEnabled())
		{
			return;
		}

		if (away)
		{
			Debug.space();
			Debug.log('User AWAY');
			this.stop();
		}
		else
		{
			Debug.space();

			if (this.isWorkingDayStarted())
			{
				Debug.log('User RETURNED, continue monitoring...');
				this.start();
			}
			else
			{
				Debug.log('User RETURNED, but working day is stopped');
			}
		}
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

	enable()
	{
		this.enabled = this.getStatusEnabled();
	}

	disable()
	{
		this.stop();
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
}

const monitor = new Monitor();

export {monitor as Monitor};
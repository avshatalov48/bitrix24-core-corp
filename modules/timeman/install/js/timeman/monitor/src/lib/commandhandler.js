import {Monitor} from "../monitor";
import {Logger} from './logger';
import {Debug} from './debug';

export class CommandHandler
{
	constructor(options = {})
	{
	}

	getModuleId()
	{
		return 'timeman';
	}

	handleChangeDayState(params)
	{
		Monitor.setState(params.state);

		if (!Monitor.isEnabled())
		{
			Logger.warn('Ignore day state, monitor is disabled!');
			Debug.log('Ignore day state, monitor is disabled!');

			return;
		}

		if (params.state === Monitor.getStateStart())
		{
			Monitor.start();
		}
		else if (params.state === Monitor.getStateStop())
		{
			Monitor.stop();
		}
	}

	handleChangeMonitorEnabled(params)
	{
		if (params.enabled === Monitor.getStatusEnabled())
		{
			Logger.warn('Enabled via API');
			Debug.log('Enabled via API');

			Monitor.enable();

			if (Monitor.isWorkingDayStarted())
			{
				Monitor.start();
			}
		}
		else
		{
			Monitor.stop();
			Monitor.disable();

			Logger.warn('Disabled via API');
			Debug.log('Disabled via API');
		}
	}
}
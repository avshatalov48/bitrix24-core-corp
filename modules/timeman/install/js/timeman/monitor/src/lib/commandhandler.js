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

	handleChangeMonitorEnabled(params)
	{
		if (params.enabled === Monitor.getStatusEnabled())
		{
			Logger.warn('Enabled via API');
			Debug.log('Enabled via API');

			location.reload();
		}
		else
		{
			Monitor.stop();
			Monitor.disable();

			Logger.warn('Disabled via API');
			Debug.log('Disabled via API');
		}
	}

	handleChangeMonitorDebugEnabled(params)
	{
		if (params.enabled)
		{
			Debug.enable();

			Logger.warn('Debug mode enabled via API');
			Debug.log('Debug mode enabled via API');
		}
		else
		{
			Logger.warn('Debug mode disabled via API');
			Debug.log('Debug mode disabled via API');

			Debug.disable();
		}
	}
}
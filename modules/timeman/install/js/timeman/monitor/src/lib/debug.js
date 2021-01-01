import {Type} from 'main.core';
import {Monitor} from '../monitor';

class Debug
{
	isEnabled()
	{
		return Monitor.isEnabled();
	}

	log(...params)
	{
		if (!this.isEnabled())
		{
			return;
		}

		let text = this.getLogMessage(...params);

		BX.desktop.log(BX.message('USER_ID') + '.monitor.log', text.substr(3));
	}

	space()
	{
		if (!this.isEnabled())
		{
			return;
		}

		BX.desktop.log(BX.message('USER_ID') + '.monitor.log', ' ');
	}

	getLogMessage()
	{
		if (!this.isEnabled())
		{
			return;
		}

		let text = '';

		for (let i = 0; i < arguments.length; i++)
		{
			if(arguments[i] instanceof Error)
			{
				text = arguments[i].message + "\n" + arguments[i].stack
			}
			else
			{
				try
				{
					text = text+' | '+(typeof(arguments[i]) == 'object'? JSON.stringify(arguments[i]): arguments[i]);
				}
				catch (e)
				{
					text = text+' | (circular structure)';
				}
			}
		}

		return text;
	}
}

let debug = new Debug();

export {debug as Debug};
import {Loc} from 'main.core';

class Debug
{
	constructor()
	{
		this.enabled = false;
	}

	isEnabled()
	{
		return this.enabled;
	}

	enable()
	{
		this.enabled = true;
	}

	disable()
	{
		this.enabled = false;
	}

	log(...params)
	{
		if (!this.isEnabled())
		{
			return;
		}

		let text = this.getLogMessage(...params);

		BX.desktop.log(Loc.getMessage('USER_ID') + '.monitor.log', text.substr(3));
	}

	space()
	{
		if (!this.isEnabled())
		{
			return;
		}

		BX.desktop.log(Loc.getMessage('USER_ID') + '.monitor.log', ' ');
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
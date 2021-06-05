import {md5} from 'main.md5';
import {sha1} from 'main.sha1';

export class Code
{
	static createPublic(...params)
	{
		return md5(params.join(''));
	}

	static createPrivate(...params)
	{
		return sha1(params.join(''));
	}

	static createSecret()
	{
		if (typeof BXDesktopSystem === 'undefined')
		{
			return null;
		}

		return Code.createPrivate(
			BXDesktopSystem.UserAccount(),
			BXDesktopSystem.UserOsMark(),
			+Date.now(),
		);
	}

	static getDesktopCode()
	{
		if (typeof BXDesktopSystem === 'undefined')
		{
			return null;
		}

		return Code.createPublic(
			BXDesktopSystem.UserAccount(),
			BXDesktopSystem.UserOsMark()
		);
	}
}
import { Reflection } from 'main.core';
import { BaseCommand } from './base-command';

export class OpenAboutCopilot extends BaseCommand
{
	execute()
	{
		const articleCode = '19092894';

		const Helper = Reflection.getClass('top.BX.Helper');

		if (Helper)
		{
			Helper.show(`redirect=detail&code=${articleCode}`);
		}
	}
}
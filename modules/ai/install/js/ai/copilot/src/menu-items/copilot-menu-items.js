import type { CopilotMenuItem } from '../copilot-menu';

export class CopilotMenuItems
{
	static getMenuItems(options: any): CopilotMenuItem[]
	{
		throw new Error('You must override method: getMenuItems');
	}
}

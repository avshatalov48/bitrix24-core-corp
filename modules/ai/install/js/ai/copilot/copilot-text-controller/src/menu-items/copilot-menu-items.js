import type { CopilotMenuItem } from 'ai.copilot';

export class CopilotMenuItems
{
	static getMenuItems(options: any): CopilotMenuItem[]
	{
		throw new Error('You must override method: getMenuItems');
	}
}

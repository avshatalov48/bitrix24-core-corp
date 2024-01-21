import { CreationMenu } from '../creation-menu';

export class Delimiter
{
	static create(): JSON
	{
		return {
			tabId: CreationMenu.MENU_ID,
			delimiter: true,
		};
	}
}
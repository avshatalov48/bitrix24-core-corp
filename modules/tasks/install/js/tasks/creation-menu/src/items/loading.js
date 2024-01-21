import { Loc } from 'main.core';

export class Loading
{
	static ID = 'loading';

	static create(): JSON
	{
		return {
			id: Loading.ID,
			text: Loc.getMessage('TASKS_CREATION_MENU_LOAD_TEMPLATE_LIST'),
		};
	}
}
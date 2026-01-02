import { Loc } from 'main.core';
import Item from './item';

export class ItemMainPage extends Item
{
	static code = 'main';

	canDelete(): boolean
	{
		return false;
	}

	openSettings(): void
	{
		const url = `${Loc.getMessage('mainpage_settings_path')}&analyticContext=left_menu`;
		BX.SidePanel.Instance.open(url);
	}
}

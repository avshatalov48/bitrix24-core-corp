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
		BX.SidePanel.Instance.open('/settings/configs/?analyticContext=left_menu&page=mainpage');
	}
}

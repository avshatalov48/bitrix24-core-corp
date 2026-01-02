import { MessageMenu } from 'im.v2.lib.menu';

import type { MenuItemOptions, MenuSectionOptions } from 'ui.system.menu';

const MenuSectionCode = Object.freeze({
	main: 'main',
	select: 'select',
});

export class OpenLinesMessageMenu extends MessageMenu
{
	getMenuItems(): MenuItemOptions[]
	{
		const mainGroupItems = [
			this.getReplyItem(),
			this.getCopyItem(),
			this.getMarkItem(),
			this.getForwardItem(),
			this.getFavoriteItem(),
			this.getDownloadFileItem(),
			this.getEditItem(),
			this.getDeleteItem(),
		];

		return [
			...this.groupItems(mainGroupItems, MenuSectionCode.main),
			...this.groupItems([this.getSelectItem()], MenuSectionCode.select),
		];
	}

	getMenuGroups(): MenuSectionOptions[]
	{
		return [
			{ code: MenuSectionCode.main },
			{ code: MenuSectionCode.select },
		];
	}
}

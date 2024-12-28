import { MessageMenu } from 'im.v2.component.message-list';

import type { MenuItem } from 'im.v2.lib.menu';

export class OpenLinesMessageMenu extends MessageMenu
{
	getMenuItems(): MenuItem[]
	{
		return [
			this.getReplyItem(),
			this.getCopyItem(),
			this.getDelimiter(),
			this.getDelimiter(),
			this.getDownloadFileItem(),
			this.getDelimiter(),
			this.getEditItem(),
			this.getDeleteItem(),
			this.getMarkItem(),
			this.getDelimiter(),
			this.getSelectItem(),
		];
	}
}

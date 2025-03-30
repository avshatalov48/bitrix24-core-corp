import { ButtonManager, Button } from 'ui.buttons';
import { MenuItem } from 'main.popup';
import { ExternalLink } from 'disk.external-link';
import { LegacyPopup } from 'disk.sharing-legacy-popup';
import type { BoardData } from './types';

export default class Board
{
	setupSharingButton: Button = null;
	data: BoardData = null;

	constructor(options)
	{
		this.setupSharingButton = ButtonManager.createByUniqId(options.panelButtonUniqIds.setupSharing);
		this.data = options.boardData;

		this.bindEvents();
	}

	bindEvents(): void
	{
		if (this.setupSharingButton)
		{
			const menuWindow = this.setupSharingButton.getMenuWindow();
			const extLinkOptions = menuWindow.getMenuItem('ext-link').options;
			extLinkOptions.onclick = this.handleClickSharingByExternalLink.bind(this);

			menuWindow.removeMenuItem('ext-link');
			menuWindow.addMenuItem(extLinkOptions);

			const sharingOptions = menuWindow.getMenuItem('sharing').options;
			sharingOptions.onclick = this.handleClickSharing.bind(this);

			menuWindow.removeMenuItem('sharing');
			menuWindow.addMenuItem(sharingOptions);
		}
	}

	handleClickSharingByExternalLink(event, menuItem: MenuItem): void
	{
		this.setupSharingButton.getMenuWindow().close();
		if (menuItem.dataset.shouldBlockExternalLinkFeature)
		{
			eval(menuItem.dataset.blockerExternalLinkFeature);

			return;
		}

		ExternalLink.showPopup(this.data.id);
	}

	handleClickSharing(): void
	{
		this.setupSharingButton.getMenuWindow().close();
		(new LegacyPopup()).showSharingDetailWithChangeRights({
			object: {
				id: this.data.id,
				name: this.data.name,
			},
		});
	}
}

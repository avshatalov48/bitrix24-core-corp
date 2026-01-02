import { ButtonManager, Button } from 'ui.buttons';
import { MenuItem } from 'main.popup';
import { ExternalLink, ExternalLinkForUnifiedLink } from 'disk.external-link';
import { LegacyPopup, SharingControlType } from 'disk.sharing-legacy-popup';
import type { BoardData } from './types';

export default class Board
{
	setupSharingButton: Button = null;
	data: BoardData = null;
	sharingControlType: SharingControlType;
	unifiedLinkAccessOnly: boolean = false;

	constructor(options)
	{
		this.setupSharingButton = ButtonManager.createByUniqId(options.panelButtonUniqIds.setupSharing);
		this.data = options.boardData;
		this.sharingControlType = options.sharingControlType;
		this.unifiedLinkAccessOnly = options.unifiedLinkAccessOnly;

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

		if (this.unifiedLinkAccessOnly)
		{
			ExternalLinkForUnifiedLink.showPopup(this.data.uniqueCode);
		}
		else
		{
			ExternalLink.showPopup(this.data.id);
		}
	}

	handleClickSharing(): void
	{
		this.setupSharingButton.getMenuWindow().close();
		this.showSharingRightsPopup();
	}

	showSharingRightsPopup(): void
	{
		const popup = new LegacyPopup();
		const popupOptions = {
			object: {
				id: this.data.id,
				name: this.data.name,
			},
		};

		switch (this.sharingControlType)
		{
			case SharingControlType.WITH_CHANGE_RIGHTS:
			case SharingControlType.WITH_SHARING:
				popup.showSharingDetailWithChangeRights(popupOptions);
				break;
			case SharingControlType.WITHOUT_EDIT:
				popup.showSharingDetailWithoutEdit(popupOptions);
				break;
			default:
				break;
		}
	}
}

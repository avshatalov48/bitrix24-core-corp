import BaseCommandHandler from "./base-command-handler";
import type {DocumentSavedMessage} from "./types";
import {ContentUpdatedMessage} from "./types";
import {Loc, Tag, Text} from "main.core";

export default class ServerCommandHandler extends BaseCommandHandler
{
	getMap(): Object
	{
		return {
			onlyoffice: this.filterCurrentObject(this.handleSavedDocument.bind(this)),
			contentUpdated: this.filterCurrentObject(this.handleContentUpdated.bind(this)),
		};
	}

	handleSavedDocument(data: DocumentSavedMessage): void
	{
		console.log('handleSavedDocument', data);

		if (data.documentSessionInfo.wasFinallySaved)
		{
			BX.UI.Notification.Center.notify({
				autoHide: false,
				content: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_SAVED_AFTER_IDLE'),
			});
		}
	}

	handleContentUpdated(data: ContentUpdatedMessage): void
	{
		console.log('handleContentUpdated', data);

		if (!data.object.updatedBy || this.isCurrentUser(data.object.updatedBy))
		{
			return;
		}

		if (this.onlyOffice.wasDocumentChanged())
		{
			this.userManager.getUserInfo(data.object.updatedBy, data.updatedBy.infoToken).then(userData => {
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_SAVED_WHILE_EDITING', {
						'#NAME#': Text.encode(data.object.name),
						'#USER_NAME#': Text.encode(userData.name),
					}),
				});
			}, () => {});
		}
		else if (this.onlyOffice.isViewMode())
		{
			this.userManager.getUserInfo(data.object.updatedBy, data.updatedBy.infoToken).then(userData => {
				let content = Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_VIEW_NON_ACTUAL_VERSION', {
					'#NAME#': Text.encode(data.object.name),
					'#USER_NAME#': Text.encode(userData.name),
				});
				content = Tag.render`<span>${content}</span>`;

				const refreshButton = content.querySelector('[data-refresh-btn]');
				if (refreshButton)
				{
					Tag.style(refreshButton)`
						cursor: pointer;
					`;
					refreshButton.addEventListener('click', this.#handleClickToRefreshEditor.bind(this));
				}

				BX.UI.Notification.Center.notify({
					content: content,
				});
			}, () => {});
		}
	}

	#handleClickToRefreshEditor(): void
	{
		this.onlyOffice.reloadView();
	}
}
import { ajax as Ajax, Loc, Tag, Type } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonColor, ButtonState, CancelButton, SaveButton } from 'ui.buttons';
import { FileUploader as TodoEditorFileUploader } from 'crm.activity.file-uploader';

import type { FileUploaderPopupOptions } from './file-uploader-popup-options';

import 'ui.design-tokens';
import './file-uploader-popup.css';

const SAVE_BUTTON_ID = 'save';
const CANCEL_BUTTON_ID = 'cancel';

export class FileUploaderPopup
{
	#entityTypeId: Number = null;
	#entityId: Number = null;
	#files: Array = [];
	#ownerTypeId: Number = null;
	#ownerId: Number = null;

	#popup: ?Popup = null;
	#fileUploader: ?TodoEditorFileUploader = null;

	constructor(params: FileUploaderPopupOptions)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#files = Type.isArrayFilled(params.files) ? params.files : [];
		this.#ownerTypeId = params.ownerTypeId;
		this.#ownerId = params.ownerId;
	}

	show(): void
	{
		if (!this.#popup)
		{
			const htmlStyles = getComputedStyle(document.documentElement);
			const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
			const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
			const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';

			this.#popup = new Popup({
				className: 'crm-activity__file-uploader-popup',
				closeIcon: true,
				closeByEsc: true,
				padding: popupPaddingNumberValue,
				overlay: {
					opacity: 40,
					backgroundColor: popupOverlayColor,
				},
				cacheable: false,
				content: this.#getPopupContent(),
				buttons: this.#getPopupButtons(),
				minWidth: 650,
				width: 650,
				maxHeight: 650
			});
		}

		this.#popup.show();
	}

	#updateFiles(): void
	{
		this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(ButtonState.WAITING);
		this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(ButtonState.DISABLED);

		Ajax.runAction('crm.activity.todo.updateFiles', {
			data: {
				ownerTypeId: this.#ownerTypeId,
				ownerId: this.#ownerId,
				id: this.#entityId,
				fileTokens: this.#fileUploader ? this.#fileUploader.getServerFileIds() : []
			}
		}).then((result) => {
				this.#revertButtonsState();

				if (!(result.hasOwnProperty('errors') && result.errors.length))
				{
					this.#closePopup();
				}
			}).catch(() => {
			this.#revertButtonsState();
		});
	}

	#revertButtonsState()
	{
		this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(null);
		this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(null);
	}

	#closePopup(): void
	{
		this.#popup?.close();
	}

	#getPopupContent(): HTMLElement
	{
		const uploaderContainer = Tag.render`<div></div>`;

		const content = Tag.render`<div class="crm-activity__file-uploader">
			<div class="crm-activity__file-uploader_title">${this.#getPopupTitle()}</div>
			<div class="crm-activity__file-uploader_content">
				${uploaderContainer}
			</div>
		</div>`;

		this.#fileUploader = new TodoEditorFileUploader({
			events: {
				'File:onComplete': (event) => {
					this.#revertButtonsState();
				},
				'File:onRemove': (event) => {
					this.#changeUploaderContainerSize();
					this.#revertButtonsState();
				},
				'onUploadStart': (event) => {
					this.#changeUploaderContainerSize();
					this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(ButtonState.DISABLED);
					this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(ButtonState.DISABLED);
				},
				// TODO: not implemented yet
				//		'onUploadComplete'
			},
			ownerId: this.#ownerId,
			ownerTypeId: this.#ownerTypeId,
			activityId: this.#entityId,
			files: this.#files,
		});
		this.#fileUploader.renderTo(uploaderContainer);

		return content;
	}

	#getPopupTitle(): string
	{
		return Loc.getMessage('CRM_FILE_UPLOADER_POPUP_TITLE_2');
	}

	#getPopupButtons(): Array<Button>
	{
		return [
			new SaveButton({
				id: SAVE_BUTTON_ID,
				round: true,
				state: ButtonState.DISABLED,
				events: {
					click: this.#updateFiles.bind(this),
				},
			}),
			new CancelButton({
				id: CANCEL_BUTTON_ID,
				round: true,
				events: {
					click: this.#closePopup.bind(this),
				},
				text: Loc.getMessage('CRM_FILE_UPLOADER_POPUP_CANCEL'),
				color: ButtonColor.LIGHT_BORDER,
			}),
		]
	}

	#changeUploaderContainerSize(): void
	{
		if (this.#popup)
		{
			this.#popup.adjustPosition();
		}
	}
}

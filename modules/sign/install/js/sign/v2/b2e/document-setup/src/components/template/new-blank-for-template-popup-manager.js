import { Tag, Loc } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';

import reuploadIcon from '../../images/components/reupload-files-popup/icon.svg';

type PopupTypesValue = 'upload' | 'select';
const popupTypes: Readonly<Record<string, PopupTypesValue>> = Object.freeze({
	upload: 'upload', select: 'select',
});

export class NewBlankForTemplatePopupManager extends EventEmitter
{
	static #instances: WeakMap<Object, NewBlankForTemplatePopupManager> = new WeakMap();

	events = {
		uploadPopup: {
			onConfirm: 'onUploadFilePopupConfirm',
			onCancel: 'onUploadFilePopupCancel',
		},
		selectBlankPopup: {
			onConfirm: 'onSelectBlankPopupConfirm',
			onCancel: 'onSelectBlankPopupCancel',
		},
	};

	#isUploadPopupCompleted: boolean = false;
	#isSelectBlankPopupCompleted: boolean = false;
	#cache: MemoryCache<any> = new MemoryCache();

	constructor()
	{
		super();
		this.setEventNamespace('BX.Sign.B2e.DocumentSetup.ReuploadFilesPopup');
	}

	static getOrCreateForObject(object: Object): NewBlankForTemplatePopupManager
	{
		if (!NewBlankForTemplatePopupManager.#instances.has(object))
		{
			NewBlankForTemplatePopupManager.#instances.set(object, new NewBlankForTemplatePopupManager());
		}

		return NewBlankForTemplatePopupManager.#instances.get(object);
	}

	showSelectBlankPopup(): void
	{
		this.#getSelectBlankPopup().show();
	}

	showUploadPopup(): void
	{
		this.#getUploadPopup().show();
	}

	isUploadPopupCompletedOnce(): boolean
	{
		return this.#isUploadPopupCompleted;
	}

	isSelectBlankPopupCompletedOnce(): boolean
	{
		return this.#isSelectBlankPopupCompleted;
	}

	#createPopupContent(popupType: PopupTypesValue): HTMLElement
	{
		const message = popupType === 'upload'
			? Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_UPLOAD_DESCRIPTION')
			: Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_SELECT_DESCRIPTION');

		return Tag.render`
			<div class="sign-b2e-document-setup__reupload__wrapper">
				<div class="sign-b2e-document-setup__reupload__icon-container">
					<img class="sign-b2e-document-setup__reupload__icon" src="${reuploadIcon}" alt="Icon"/>
				</div>
				<div class="sign-b2e-document-setup__reupload__text">
					<div class="sign-b2e-document-setup__reupload__title">
						${Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_TITLE')}
					</div>
					<div class="sign-b2e-document-setup__reupload__description">
						${message}
					</div>
				</div>
			</div>
		`;
	}

	#createSelectPopupButtons(): [Button, Button]
	{
		const confirmButton = new Button({
			text: Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_SELECT_CONFIRM'),
			color: Button.Color.PRIMARY,
			round: true,
			events: {
				click: () => {
					this.#getSelectBlankPopup().close();
					this.#isSelectBlankPopupCompleted = true;
					this.emit(this.events.selectBlankPopup.onConfirm);
				},
			},
		});

		const cancelButton = new Button({
			text: Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_CANCEL'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			events: {
				click: () => {
					this.#getSelectBlankPopup().close();
					this.emit(this.events.selectBlankPopup.onCancel);
				},
			},
		});

		return [confirmButton, cancelButton];
	}

	#getSelectBlankPopup(): Popup
	{
		return this.#cache.remember('selectBlankPopup', () => new Popup({
			disableScroll: true,
			className: 'sign-b2e-document-setup__reupload__popup',
			content: this.#createPopupContent(popupTypes.select),
			width: 386,
			overlay: true,
			closeByEsc: false,
			buttons: this.#createSelectPopupButtons(),
		}));
	}

	#createUploadPopupButtons(): [Button, Button]
	{
		const confirmButton = new Button({
			text: Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_UPLOAD_CONFIRM'),
			color: Button.Color.PRIMARY,
			round: true,
			closeByEsc: false,
			events: {
				click: () => {
					this.#getUploadPopup().close();
					this.#isUploadPopupCompleted = true;
					this.emit(this.events.uploadPopup.onConfirm);
				},
			},
		});

		const cancelButton = new Button({
			text: Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_CANCEL'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			events: {
				click: () => {
					this.#getUploadPopup().close();
					this.emit(this.events.uploadPopup.onCancel);
				},
			},
		});

		return [confirmButton, cancelButton];
	}

	#getUploadPopup(): Popup
	{
		return this.#cache.remember('uploadPopup', () => new Popup({
			disableScroll: true,
			className: 'sign-b2e-document-setup__reupload__popup',
			content: this.#createPopupContent(popupTypes.upload),
			width: 386,
			overlay: true,
			buttons: this.#createUploadPopupButtons(),
		}));
	}
}

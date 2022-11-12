import {Tag, Loc, Type, Dom} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import Options from './options';
import {Editor as AvatarEditor, FileType as AvatarFileType} from 'ui.avatar-editor';

export default class MaskEditor extends EventEmitter
{
	#container: HTMLElement;
	#isAvailable: boolean = false;
	#isInstalled: boolean = false;
	#avatarInfo: AvatarFileType = {scr: null, maskId: null};
	#avatarEditor: ?AvatarEditor;

	constructor(data: ?AvatarFileType)
	{
		super();
		this.setEventNamespace(Options.eventNameSpace);
		this.#isAvailable = data !== undefined;
		if (Type.isPlainObject(data))
		{
			this.#avatarInfo.src = data.src;
		}
		this.getContainer();
		if (Type.isPlainObject(data) && data.maskId)
		{
			this.installMask(data.maskId);
		}
		else
		{
			this.uninstallMask();
		}
	}

	getEditor(): ?AvatarEditor
	{
		if (this.#avatarEditor)
		{
			return this.#avatarEditor;
		}
		if (!this.#isAvailable)
		{
			return null;
		}

		this.#avatarEditor = AvatarEditor.getOrCreateInstanceById('intranet-user-profile-photo-file', {
			enableCamera: true,
			enableMask: true
		});
		this.#avatarEditor.subscribe('onApply', ({data: {maskedBlob}}: BaseEvent) => {
			if (maskedBlob)
			{
				return this.installMask(maskedBlob['maskId']);
			}
			this.uninstallMask();
		});

		this.#avatarEditor.subscribeOnFormIsReady('newPhoto', ({data: {form}}: BaseEvent) => {
			this.emit('onChangePhoto', form);
		});
		this.#avatarEditor.loadData(this.#avatarInfo);
		return this.#avatarEditor;
	}

	getContainer(): HTMLElement
	{
		if (this.#container instanceof HTMLElement)
		{
			return this.#container;
		}

		if (!this.#isAvailable)
		{
			this.#container = Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --mask"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_MASKS')}</span>
							<span class="system-auth-form__icon-help"></span>
						</div>
						<div class="system-auth-form__item-content --center --center-force">
							<div class="ui-qr-popupcomponentmaker__btn">${Loc.getMessage('INTRANET_USER_PROFILE_INSTALL')}</div>
						</div>
					</div>
					<div class="system-auth-form__item-new --soon">
						<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_SOON')}</div>
					</div>
				</div>
			`;
		}
		else
		{
			const onclick = () => {
				this.emit('onOpen');
				this.getEditor().show('mask');
			};
			const button = Tag.render`<div class="ui-qr-popupcomponentmaker__btn" onclick="${onclick}"></div>`;

			this.#container = Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --changeable --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --mask"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_MASKS')}</span>
						</div>
						<div class="system-auth-form__item-content --center --center-force">
							${button}
						</div>
					</div>
				</div>
			`;
			this.subscribe('onInstall', () => {
				button.innerHTML = Loc.getMessage('INTRANET_USER_PROFILE_CHANGE');
				Dom.addClass(this.#container, '--active');
			});
			this.subscribe('onUninstall', () => {
				button.innerHTML = Loc.getMessage('INTRANET_USER_PROFILE_INSTALL');
				Dom.removeClass(this.#container, '--active');
			});
		}

		return this.#container;
	}

	getPromise()
	{
		return new Promise((resolve) => {
			resolve(this.getContainer());
		});
	}

	installMask(maskId: String)
	{
		this.#isInstalled = true;
		this.#avatarInfo.maskId = maskId;
		this.emit('onInstall');
	}

	uninstallMask()
	{
		this.#isInstalled = false;
		this.#avatarInfo.maskId = null;
		this.emit('onUninstall');
	}
}
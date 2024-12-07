import {Loc, ajax} from 'main.core';
import {PopupWindowManager} from 'main.popup';
import {Guide} from 'ui.tour';

type KeyGridOptions = {
	bindElement: HTMLElement,
	article: string,
}

export class KeysGrid
{
	static gridId: string = 'biconnector_key_list';
	static componentName = 'bitrix:biconnector.key.list';
	#options: KeyGridOptions;
	#spotLight: ?BX.SpotLight;
	#guide: ?Guide;

	constructor(options: KeyGridOptions)
	{
		this.#options = options;
	}

	static deleteRow(id: number): void
	{
		const grid = BX.Main.gridManager.getInstanceById(KeysGrid.gridId);
		grid.confirmDialog({
			CONFIRM: true,
			CONFIRM_MESSAGE: Loc.getMessage('CC_BBKL_ACTION_MENU_DELETE_CONF'),
		}, () => {
			ajax.runComponentAction(KeysGrid.componentName, 'deleteRow', {
				mode: 'class',
				data: {
					id: id,
				}
			}).then(() => {
				grid.removeRow(id);
			});
		});
	}

	static activateKey(id: number, switcher: BX.UI.Switcher): void
	{
		ajax.runComponentAction(KeysGrid.componentName, 'activateKey', {
			mode: 'class',
			data: {
				id: id,
			}
		}).then((response) => {
			if (response.data === false)
			{
				switcher.check(false, false);
				this.#showNotifyKeySwitcherError(false);
			}
		}).catch(() => {
			switcher.check(false, false);
			this.#showNotifyKeySwitcherError(false);
		});
	}

	static deactivateKey(id: number, switcher: BX.UI.Switcher): void
	{
		ajax.runComponentAction('bitrix:biconnector.key.list', 'deactivateKey', {
			mode: 'class',
			data: {
				id: id,
			}
		}).then((response) => {
			if (response.data === false)
			{
				switcher.check(true, false);
				this.#showNotifyKeySwitcherError();
			}
		}).catch(() => {
			switcher.check(true, false);
			this.#showNotifyKeySwitcherError();
		});
	}

	static copyKey(elementWrapper: HTMLElement): void
	{
		const access_key = elementWrapper.querySelector('[data-key-id]');
		const textarea = document.createElement('textarea');
		textarea.value = access_key.value;
		textarea.setAttribute('readonly', '');
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();

		try {
			document.execCommand('copy');
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('CC_BBKL_KEY_COPIED'),
				autoHideDelay: 2000,
			});
		}
		catch(error)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('CC_BBKL_KEY_COPY_ERROR'),
				autoHideDelay: 2000,
			});
		}

		textarea.remove();

		return false;
	}

	static #showNotifyKeySwitcherError(): void
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('CC_BBKL_ACTIVATE_KEY_ERROR'),
			autoHideDelay: 2000,
		});
	}

	showOnboarding(): void
	{
		if (!(PopupWindowManager && PopupWindowManager.isAnyPopupShown()))
		{
			this.#getSpotlight().show();
			this.#getGuide().start();
			this.#getSpotlight().getTargetContainer().addEventListener('mouseover', () => {
				this.#getSpotlight().close();
			});
		}
	}

	#getSpotlight(): BX.SpotLight
	{
		if (this.#spotLight)
		{
			return this.#spotLight;
		}

		this.#spotLight = new BX.SpotLight({
			targetElement: this.#options.bindElement,
			targetVertex: 'middle-center',
			id: KeysGrid.gridId,
			lightMode: true,
		});

		return this.#spotLight;
	}

	#getGuide(): Guide
	{
		if (this.#guide)
		{
			return this.#guide;
		}

		this.#guide = new Guide({
			simpleMode: true,
			onEvents: true,
			overlay: false,
			steps: [
				{
					target: this.#options.bindElement,
					title: Loc.getMessage('CC_BBKL_KEY_ONBOARDING_TITLE'),
					text: Loc.getMessage('CC_BBKL_KEY_ONBOARDING_DESCRIPTION'),
					buttons: null,
					events: {
						onClose: () => {
							this.#getSpotlight().close();
						},
						onShow: () => {
							ajax.runComponentAction(KeysGrid.componentName, 'markShowOnboarding', {
								mode: 'class'
							});
						},
					},
					article: this.#options.article,
				},
			],
			autoHide: true,
		});
		this.#guide.getPopup().setWidth(360);
		this.#guide.getPopup().setAngle({
			offset: this.#options.bindElement.offsetWidth / 2,
		});
		this.#guide.getPopup().setAutoHide(true);

		return this.#guide;
	}
}
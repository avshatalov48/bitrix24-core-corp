import { ajax as Ajax, Reflection, Dom, Loc } from 'main.core';
import { App } from 'ui.accessrights.v2';
import { Loader } from 'main.loader';
import { ButtonColor, ButtonSize, CancelButton, SaveButton } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';

const namespace = Reflection.namespace('BX.Humanresources');

class ConfigPermsComponent
{
	accessRights: App;
	accessRightsOption: Object;
	menuId: string;
	isRendered: boolean;

	constructor(config: { accessRightsOption: Object, accessRights: App, menuId: string })
	{
		this.accessRightsOption = config.accessRightsOption;
		this.accessRights = config.accessRights;
		this.menuId = config.menuId;
	}

	init(): void
	{
		this.accessRights.draw();
		this.isRendered = true;
		this.#addWrapperSliderContent();
		this.#addWrapperLeftMenu();
	}

	#addWrapperSliderContent(): void
	{
		const sliderContent = document.getElementById('ui-page-slider-content');
		if (sliderContent)
		{
			const wrapperSliderContent = Dom.create('div', {
				props: { className: 'hr-permission-config-role-main-content' },
			});

			wrapperSliderContent.className = 'hr-permission-config-role-main-content';
			Dom.insertBefore(wrapperSliderContent, sliderContent);
			Dom.append(sliderContent, wrapperSliderContent);
		}
	}

	#addWrapperLeftMenu(): void
	{
		const leftPanel = document.getElementById('left-panel');

		if (leftPanel)
		{
			const wrapperLeftMenu = Dom.create('div', {
				props: { className: 'hr-permission-config-role-main-left-menu' },
			});

			Dom.insertBefore(wrapperLeftMenu, leftPanel);
			Dom.append(leftPanel, wrapperLeftMenu);
		}
	}

	openPermission(parameters: { category: string, menuId: string }): void
	{
		if (parameters.menuId === this.menuId)
		{
			return;
		}

		if (this.isRendered && this.accessRights.hasUnsavedChanges())
		{
			event.stopImmediatePropagation();
			this.#confirmBeforeRedraw(parameters.menuId);
		}
		else
		{
			this.redrawAccessRight(parameters);
		}
	}

	redrawAccessRight(parameters: { category: string, menuId: string }): void
	{
		if (this.isRendered)
		{
			this.accessRights.destroy();
		}

		this.menuId = parameters.menuId;
		this.isRendered = false;
		const loader = new Loader({
			target: document.getElementById('bx-hr-permission-config-role-main-container'),
		});
		loader.show();

		this.#runGetDataAjaxRequest(parameters)
			.then((data) => {
				if (data.userGroups && data.accessRights)
				{
					this.accessRightsOption.userGroups = data.userGroups;
					this.accessRightsOption.accessRights = data.accessRights;
					this.accessRightsOption.additionalSaveParams = { category: parameters.category };
					this.accessRightsOption.loadParams = { category: parameters.category };

					this.accessRights = new App(this.accessRightsOption);
					scrollTo({ top: 0 });
				}
				this.accessRights.draw();
				this.isRendered = true;
			})
			.catch(() => {
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('HUMANRESOURCES_CONFIG_PERMISSIONS_ERROR'),
					position: 'top-right',
					autoHideDelay: 3000,
				});
			})
			.finally(() => {
				loader.destroy();
			})
		;
	}

	#confirmBeforeRedraw(menuId: string): void
	{
		const messageBox = MessageBox.create({
			message: Loc.getMessage('HUMANRESOURCES_CONFIG_PERMISSIONS_SAVE_MESSAGEBOX_TEXT'),
			modal: true,
			buttons: [
				new SaveButton({
					size: ButtonSize.SMALL,
					color: ButtonColor.PRIMARY,
					onclick: (button) => {
						this.#handleSaveButtonClick(button, menuId, messageBox);
					},
				}),
				new CancelButton({
					size: ButtonSize.SMALL,
					onclick: () => {
						messageBox.close();
					},
				}),
			],
		});

		messageBox.show();
	}

	#handleSaveButtonClick(button: SaveButton, menuId: string, messageBox: MessageBox): void
	{
		button.setWaiting(true);
		this.accessRights.sendActionRequest()
			.then(() => {
				document.querySelector(`[data-menu-id="${menuId}"]`).click();
			})
			.catch(() => {})
			.finally(() => {
				messageBox.close();
			})
		;
	}

	#runGetDataAjaxRequest(parameters: { category: string, menuId: string }): Promise
	{
		return new Promise((resolve, reject) => {
			Ajax.runComponentAction(
				'bitrix:humanresources.config.permissions',
				'getData',
				{
					mode: 'class',
					data: {
						parameters,
					},
				},
			)
				.then((response) => {
					resolve(response.data);
				})
				.catch(reject)
			;
		});
	}
}

namespace.ConfigPermsComponent = ConfigPermsComponent;

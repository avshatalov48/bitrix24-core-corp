import { ajax as Ajax, Loc, Reflection } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import { ButtonSize, ButtonColor, CancelButton, SaveButton, Button } from 'ui.buttons';
import { App } from 'ui.accessrights.v2';

const namespace = Reflection.namespace('BX.Crm');

class ConfigPermsComponent
{
	AccessRights: App;
	AccessRightsOption: Object;
	hasLeftMenu: boolean;
	menuId: string;

	constructor(config)
	{
		this.AccessRightsOption = config.AccessRightsOption;
		this.AccessRights = config.AccessRights;
		this.hasLeftMenu = config.hasLeftMenu;
		this.menuId = config.menuId;
	}

	init(): void
	{
		this.AccessRights.draw();
		this.#renderHelpButton();

		if (this.hasLeftMenu)
		{
			this.#addWrapperSliderContent();
			this.#addWrapperLeftMenu();
		}
	}

	#addWrapperSliderContent()
	{
		const sliderContent = document.getElementById('ui-page-slider-content');
		if (sliderContent)
		{
			const wrapperSliderContent = document.createElement('div');
			wrapperSliderContent.className = 'crm-config-perms-v2-slider-content';
			sliderContent.parentNode.insertBefore(wrapperSliderContent, sliderContent);
			wrapperSliderContent.appendChild(sliderContent);
		}
	}

	#addWrapperLeftMenu()
	{
		const leftPanel = document.getElementById('left-panel');

		if (leftPanel)
		{
			const wrapperLeftMenu = document.createElement('div');
			wrapperLeftMenu.className = 'crm-config-perms-v2-sidebar';
			leftPanel.parentNode.insertBefore(wrapperLeftMenu, leftPanel);
			wrapperLeftMenu.appendChild(leftPanel);
		}
	}

	#renderHelpButton()
	{
		const Helper = Reflection.getClass('top.BX.Helper');

		const helpButton = new Button({
			size: ButtonSize.MEDIUM,
			color: ButtonColor.LIGHT_BORDER,
			text: Loc.getMessage('CRM_CONFIG_PERMS_HELP'),
			noCaps: true,
			onclick: () => {
				const articleCode = '23240636'; // todo replace with the real article code

				Helper?.show(`redirect=detail&code=${articleCode}`);
			},
		});

		const parentElement = document.querySelector('.crm-config-perms-v2-header');
		helpButton.renderTo(parentElement);
	}

	openPermission(controllerData): void
	{
		if (this.menuId === controllerData.menuId)
		{
			return;
		}

		if (!this.AccessRights.hasUnsavedChanges())
		{
			this.redrawAccessRight(controllerData);
		}
		else
		{
			event.stopImmediatePropagation();
			this.#confirmBeforeRedraw(controllerData);
		}
	}

	redrawAccessRight(controllerData): void
	{
		const loader = new BX.Loader({
			target: document.getElementById('bx-crm-perms-config-permissions'),
		});
		this.AccessRights.destroy();
		loader.show();

		this.#runGetDataAjaxRequest(controllerData)
			.then(({ accessRightsData, maxVisibleUserGroups, additionalSaveParams }) => {
				this.AccessRightsOption.userGroups = accessRightsData.userGroups;
				this.AccessRightsOption.accessRights = accessRightsData.accessRights;
				this.AccessRightsOption.maxVisibleUserGroups = maxVisibleUserGroups;
				this.AccessRightsOption.additionalSaveParams = additionalSaveParams;

				this.AccessRights = new App(this.AccessRightsOption);
				this.AccessRights.draw();
				scrollTo({ top: 0 });

				this.menuId = controllerData.menuId;
			})
			.catch((response: SaveAjaxResponse) => {
				console.warn('ui.accessrights.v2: error during redraw', response);

				this.#showNotification(response?.errors?.[0]?.message || 'Something went wrong');
			})
			.finally(() => {
				loader.hide();
			});
	}

	#runGetDataAjaxRequest(controllerData): Promise
	{
		return new Promise((resolve, reject) => {
			Ajax.runComponentAction(
				'bitrix:crm.config.perms.v2',
				'getData',
				{
					mode: 'class',
					data: {
						controllerData,
					},
				},
			)
				.then((response: SaveAjaxResponse) => {
					resolve(response.data);
				})
				.catch(reject)
			;
		});
	}

	#confirmBeforeRedraw(controllerData): void
	{
		const box = MessageBox.create({
			message: Loc.getMessage('CRM_CONFIG_PERMS_SAVE_POPUP_TITLE'),
			modal: true,
			buttons: [
				new SaveButton({
					size: ButtonSize.SMALL,
					color: ButtonColor.PRIMARY,
					onclick: (button) => {
						button.setWaiting(true);

						this.AccessRights.sendActionRequest()
							.then(() => {
								document.querySelector(`[data-menu-id="${controllerData.menuId}"]`).click();
							})
							.catch()
							.finally(() => {
								box.close();
							});
					},
				}),
				new CancelButton({
					text: Loc.getMessage('CRM_CONFIG_PERMS_SAVE_POPUP_CANCEL'),
					size: ButtonSize.SMALL,
					onclick: () => {
						box.close();
					},
				}),
			],
		});

		box.show();
	}

	#showNotification(title): void
	{
		BX.UI.Notification.Center.notify({
			content: title,
			position: 'top-right',
			autoHideDelay: 3000,
		});
	}
}

namespace.ConfigPermsComponent = ConfigPermsComponent;

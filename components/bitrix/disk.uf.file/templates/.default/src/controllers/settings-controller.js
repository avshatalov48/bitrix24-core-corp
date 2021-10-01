import {Loc} from 'main.core';
import {Menu, MenuItem} from 'main.popup';
import DefaultController from "./default-controller";
import Options from "../options";

export default class SettingsController extends DefaultController
{
	allowEditNode: ?Element;
	allowGridNode: ?Element;
	constructor({container, eventObject})
	{
		super({
			container: container.querySelector('[data-bx-role="setting"]'),
			eventObject: eventObject
		});
		if (this.getContainer())
		{
			this.getContainer().addEventListener('click', this.show.bind(this))
			this.allowEditNode = container.querySelector('input[data-bx-role="settings-allow-edit"]');
			this.allowGridNode = container.querySelector('input[data-bx-role="settings-allow-grid"]');
		}
	}

	show()
	{
		if (!this.popup)
		{
			this.popup = new Menu({
				bindElement: this.getContainer(),
				className: 'disk-uf-file-popup-settings',
				items: [
					this.allowEditNode ? {
						dataset: {bxRole: 'allowEdit'},
						className: this.allowEditNode.checked ? 'menu-popup-item-take' : '',
						text: Loc.getMessage('WDUF_ALLOW_EDIT'),
						onclick: function(event, item: MenuItem) {
							this.allowEditNode.checked = !this.allowEditNode.checked;
							if (this.allowEditNode.checked)
							{
								item.getContainer().classList.add('menu-popup-item-take');
								item.getContainer().classList.remove('menu-popup-no-icon');
							}
							else
							{
								item.getContainer().classList.remove('menu-popup-item-take');
								item.getContainer().classList.add('menu-popup-no-icon');
							}
						}.bind(this)
					} : null,
					{
						dataset: {bxRole: 'allowGrid'},
						className: this.allowGridNode.checked ? 'menu-popup-item-take' : '',
						text: Loc.getMessage('WDUF_ALLOW_COLLAGE'),
						onclick: function(event, item: MenuItem) {
							this.allowGridNode.checked = !this.allowGridNode.checked;
							if (this.allowGridNode.dataset.bxSave === 'Y')
							{
								BX.userOptions.save('disk', 'disk.uf.file', this.allowGridNode.dataset.bxName, (this.allowGridNode.checked ? 'grid' : '.default'));
							}
							if (this.allowGridNode.checked)
							{
								item.getContainer().classList.add('menu-popup-item-take');
								item.getContainer().classList.remove('menu-popup-no-icon');
							}
							else
							{
								item.getContainer().classList.remove('menu-popup-item-take');
								item.getContainer().classList.add('menu-popup-no-icon');
							}
						}.bind(this)
					},
					{
						text: this.buildDocumentServiceTextLabel(),
						items: this.buildSubMenuWithDocumentServices(),
					}
				],
				angle: true,
				offsetTop: -16,
				offsetLeft: 16,
				events: {
					onClose: () => {
						delete this.popup;
					}
				}
			})
		}
		this.popup.show()
	}

	buildDocumentServiceTextLabel(): string
	{
		let currentService = BX.Disk.getDocumentService();
		if (!currentService && BX.Disk.isAvailableOnlyOffice())
		{
			currentService = 'onlyoffice';
		}
		else if (!currentService)
		{
			currentService = 'l';
		}

		const name = Options.getDocumentHandler(currentService).name;

		return Loc.getMessage('DISK_UF_FILE_EDIT_SERVICE_LABEL', { '#NAME#' : name});
	}

	buildSubMenuWithDocumentServices(): Array<Object>
	{
		let items = [];

		Options.getDocumentHandlers().forEach(item => {
			items.push({
				text: item.name,
				dataset: {
					code: item.code,
				},
				onclick: (event, item: MenuItem) => {
					BX.Disk.saveDocumentService(item.dataset.code);

					item.getMenuWindow().getParentMenuItem().setText(this.buildDocumentServiceTextLabel());
					item.getMenuWindow().getPopupWindow().close();
				},
			});
		});

		return items;
	}

	hide()
	{
		if (this.popup)
		{
			this.popup.close();
 		}
	}
}
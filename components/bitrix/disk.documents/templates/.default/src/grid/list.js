import {Dom, pos} from 'main.core';
import {Options as GridOptions} from '../options';
import {BaseEvent, EventEmitter} from "main.core.events";
import Backend from "../backend";
import getMenuItem from "../gridmenu/index";

export default class List
{
	constructor()
	{
		this.addReloadGrid();
		this.addMenuActionLoader();

		this.bindEvents();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('Disk.OnlyOffice:onSaved', this.handleDocumentSaved.bind(this));
	}

	handleDocumentSaved(event: BaseEvent): void
	{
		const [object, documentSession] = event.getCompatData();
		const grid = BX.Main.gridManager.getInstanceById(GridOptions.getGridId());
		const objectNode = grid.getBody().querySelector(`span[data-object-id="${object.id}"]`)
		if (!objectNode)
		{
			return;
		}

		const row = objectNode.closest('.main-grid-row');
		if (!row || !row.dataset.id)
		{
			return;
		}

		const rowId = row.dataset.id;
		grid.updateRow(rowId, null, null, () => {
			const rowNode = grid.getRows().getById(rowId).getNode();
			if (!rowNode)
			{
				return;
			}

			Dom.addClass(rowNode, 'main-grid-row-checked');
			setInterval(() => {
				Dom.removeClass(rowNode, 'main-grid-row-checked');
			}, 8000);
		});
	}

	addReloadGrid()
	{
		BX.addCustomEvent('onPopupFileUploadClose', () => {
			BX.Main.gridManager
				.getInstanceById(GridOptions.getGridId())
				.reload();
		});
	}

	addMenuActionLoader()
	{
		EventEmitter.subscribe(
			'onPopupFirstShow',
			function({compatData: [popup]}: BaseEvent) {
				if (popup.uniquePopupId.indexOf('menu-popup-main-grid-actions-menu-') !== 0)
				{
					return;
				}

				const objectId = popup.uniquePopupId.replace(/^menu-popup-main-grid-actions-menu-/, '');

				popup.getContentContainer().classList.add('disk-documents-animate');
				popup.getContentContainer().style.height = 80 + 'px';

				Backend
					.getMenuActions(objectId)
					.then(function({data}) {
						const row =  BX.Main.gridManager
							.getInstanceById(GridOptions.getGridId())
							.getRows()
							.getById(objectId);
						const menu = row.getActionsMenu();

						row.actions = [];
						const prepareActionMenu = (item, index, ar) => {
							if (item['items'])
							{
								item['items'].forEach(prepareActionMenu)
							}
							const menuItem = getMenuItem(objectId, item);
							menuItem.subscribe('close', () => {
								row.closeActionsMenu();
							});

							if (ar === data)
							{
								menuItem.addPopupMenuItem(menu);
								row.actions.push(menuItem.getData());
							}
							else
							{
								ar[index] = menuItem.getData();
							}
						}

						setTimeout(function() {
							popup.getContentContainer().style.height = (data.length * 36) + 16 + 'px';
						});

						popup.getContentContainer().addEventListener('transitionend', () => {
							popup.getContentContainer().classList.remove('disk-documents-animate');
							popup.getContentContainer().style.height = '';
						});

						data.forEach(prepareActionMenu);
						if (menu)
						{
							menu.removeMenuItem('loader');
						}
					}.bind(this))
					.catch(function({errors})
					{
						//Hide Loader and show errors
						console.log(errors);
					}.bind(this));
			}.bind(this)
		);
	}
}

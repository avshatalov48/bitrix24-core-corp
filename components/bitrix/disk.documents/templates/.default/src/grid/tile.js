import {Tag, Loc} from 'main.core';
import {Options as GridOptions} from '../options';
import {BaseEvent, EventEmitter} from "main.core.events";
import Backend from "../backend";
import getMenuItem from "../gridmenu/index";

export default class Tile
{
	constructor()
	{
		this.addReloadGrid();
		this.addMenuActionLoader();
		this.addFilterSequence();
	}

	addFilterSequence()
	{
		EventEmitter.subscribe(
			'BX.Main.Filter:apply',
			function({compatData: [filterId, data, filter, promise, params]}: BaseEvent)
			{
				if (filterId === GridOptions.getFilterId())
				{
					promise.then(function () {
						BX.Main.tileGridManager
							.getInstanceById(GridOptions.getGridId())
							.reload();
					}.bind(this));
				}
			}
		);
	}

	addReloadGrid()
	{
		BX.addCustomEvent('onPopupFileUploadClose', () => {
			BX.Main.tileGridManager
				.getInstanceById(GridOptions.getGridId())
				.reload();
		});
	}

	addMenuActionLoader()
	{
		EventEmitter.subscribe(
			'Disk:Documents:TileGrid:MenuAction:FirstShow',
			function({compatData: [row, objectId, menuPopup]}: BaseEvent) {
				Backend
					.getMenuActions(objectId)
					.then(function({data}) {
						const menu = menuPopup;
						row.actions = [];
						const prepareActionMenu = (item, index, ar) => {
							if (item['items'])
							{
								item['items'].forEach(prepareActionMenu)
							}
							if (item['id'] === 'rename')
							{
								item['onclick'] = row.onRename.bind(row);
							}

							const menuItem = getMenuItem(objectId, item);
							menuItem.subscribe('close', () => {
								menu.close();
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

	static generateEmptyBlock()
	{
		return Tag.render`
		<div class="disk-folder-list-no-data-inner">
			<div class="disk-folder-list-no-data-inner-message">
				${Loc.getMessage('DISK_DOCUMENTS_GRID_TILE_EMPTY_BLOCK_TITLE')}
			</div>
			<div class="disk-folder-list-no-data-inner-variable">
				<div class="disk-folder-list-no-data-inner-create-file" onmouseover="BX.onCustomEvent(window, 'onDiskUploadPopupShow', [this]);">
					${Loc.getMessage('DISK_DOCUMENTS_GRID_TILE_EMPTY_BLOCK_UPLOAD')}</div>
			</div>
		</div>`;
	}
}
import {Cache, Tag, Text, Type, Dom, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Menu} from 'main.popup';
import type {ItemSavedType} from './item-type';
import Backend from '../backend';

export default class Item extends EventEmitter
{
	id: string;
	data: ItemSavedType;
	cache = new Cache.MemoryCache();
	properties = {
		pluggedIn: false
	};

	constructor(itemData: ItemSavedType)
	{
		super();
		this.setEventNamespace('Disk:UF:');
		this.id = String(itemData['ID']);

		this.setData(itemData);
		this.subscribe('onMoved', this.onMoved.bind(this))
	}

	getId(): string
	{
		return this.id;
	}

	getFileId(): string
	{
		return this.data.FILE_ID;
	}

	getData(key:?string)
	{
		if (key)
		{
			return this.data[key];
		}
		return this.data;
	}

	getAllIds()
	{
		return [this.getId(), ['n', this.data.FILE_ID].join('')];
	}

	setData(data)
	{
		this.data = data;
	}

	setPluggedIn(value: boolean = true): boolean
	{
		this.properties.pluggedIn = (value === true);
	}

	isPluggedIn()
	{
		return this.properties.pluggedIn;
	}

	getContainer(): Element
	{
		return this.cache.remember('container', () => {
			const name = Text.encode(this.data['NAME']);
			let extension = Text.encode(this.data['EXTENSION']).toLowerCase();

			switch (extension) {
				case 'pptx':
					extension = 'ppt';
					break;
			}

			return Tag.render`
		<div class="disk-file-thumb disk-file-thumb-file disk-file-thumb--${extension}" onclick="${this.onClick.bind(this)}">
			<div data-bx-role="icon" class="ui-icon ui-icon-file-${extension} disk-file-thumb-icon"><i></i></div>
			<div data-bx-role="name" class="disk-file-thumb-text">${name}</div>
			<div class="disk-file-thumb-btn-box">
				<div class="disk-file-thumb-btn-close" onclick="${this.onClickDelete.bind(this)}"></div>
				<div class="disk-file-thumb-btn-more" data-bx-role="more" onclick="${this.onClickMore.bind(this)}"></div>
			</div>
		</div>`;
		});
	}

	rename(newName: string)
	{
		this.data['NAME'] = newName;
		if (this.hasContainer())
		{
			this.getContainer().querySelector('[data-bx-role="name"]').innerHTML = Text.encode(this.data['NAME']);
		}
	}

	destroy()
	{
		const keys = this.cache.keys();
		keys.forEach((key) => {
			const node = this.cache.get(key);
			if (Type.isDomNode(node) && node.parentNode)
			{
				node.parentNode.removeChild(node);
			}
			this.cache.delete(key);
		});

		this.emit('onDestroy');
	}

	hasContainer(): boolean
	{
		return this.cache.has('container');
	}

	getMenu(): Menu
	{
		let extension = this.getData('NAME').split('.').pop();
		extension = (extension === this.getData('NAME') ? '' : ['.', extension].join(''));
		const fullName = String(this.getData('NAME'));
		const cleanName = fullName.substring(0, fullName.length - extension.length);

		return this.cache.remember('menu', () => {
			const moreButton = this.getContainer().querySelector('div[data-bx-role=\'more\']');
			const contextMenu = new Menu({
				id: `crm-tunnels-menu-${Text.getRandom().toLowerCase()}`,
				bindElement: moreButton,
				items: ([
					this.isPluggedIn() ? {
						dataset: {bxRole: 'insertIntoTheText'},
						text: Loc.getMessage('WDUF_ITEM_MENU_INSERT_INTO_THE_TEXT'),
						onclick : (event, item) => {
							contextMenu.close();
							this.onClick(event);
						}
					} : null,
					{
						dataset: {bxRole: 'deleteFile'},
						text: Loc.getMessage('WDUF_ITEM_MENU_DELETE'),
						onclick: (event) => {
							contextMenu.close();
							this.onClickDelete(event);
						}
					},
					/*TODO For the Future
					   (this.data['CAN_UPDATE'] === true && this.data['EDITABLE'] === true ? {
						text:  Loc.getMessage('WDUF_ITEM_MENU_EDIT'),
						className: 'menu-popup-item-edit',
						onclick: (event) => {
							contextMenu.close();
							this.onClickEdit(event);
						}
					} : null),*/
					(this.data['CAN_RENAME'] === true ? {
						dataset: {bxRole: 'renameFile'},
						text: Loc.getMessage('WDUF_ITEM_MENU_RENAME'),
						items: [
							{
								html:
									[
										`<textarea class="disk-file-popup-rename-file-textarea"
											name="rename" onkeydown="if(event.keyCode===13){ BX.fireEvent(event.target.parentNode.querySelector('input[name=save]'), 'click'); }"
											placeholder="${Text.encode(cleanName)}">${Text.encode(cleanName)}</textarea>`,
										`<div class="ui-btn-container ui-btn-container-center">
											<input type="button" class="ui-btn ui-btn-sm ui-btn-primary" name="save" value="${Loc.getMessage('WDUF_ITEM_MENU_RENAME_SAVE')}">
											<input type="button" class="ui-btn ui-btn-sm ui-btn-link" value="${Loc.getMessage('WDUF_ITEM_MENU_RENAME_CANCEL')}">
										</div>`
									].join(''),
								className: 'menu-popup-item-rename-form',
								onclick: (event, item) => {
									if (Type.isDomNode(event.target) && event.target.type === 'button')
									{
										if (event.target.name === 'save')
										{
											this.onRenamed([item.getContainer().querySelector('textarea').value, extension].join(''));
											this.clearMenu();
										}
										contextMenu.close();
									}
								}
							}
						]
					} : null),
					{
						delimiter : true,
						text: [
							Loc.getMessage('WDUF_ITEM_MENU_FILE'),
							Text.encode(this.data['SIZE'])
						].join(' ')
					},
					(this.data['CAN_MOVE'] === true ? {
						dataset: {bxRole: 'moveFile'},
						text: Text.encode(this.data['STORAGE']),
						className: 'menu-popup-item-storage',
						onclick: (event, item) => {
							event['counter'] = (event['counter'] ?? 1) + 1;
							contextMenu.close();
							this.onClickMoveTo(event, item);
						}
					} : {
						text: Text.encode(this.data['STORAGE']),
						className: 'menu-popup-item-storage',
					})
				].
				filter(preItem => preItem !== null)),
				angle: true,
				offsetLeft: 9
			});
			return contextMenu;
		});
	}

	clearMenu()
	{
		if (this.cache.has('menu'))
		{
			this.cache.get('menu').destroy();
			this.cache.delete('menu');
		}
	}

	onClick(event: MouseEvent)
	{
		this.emit('onClick');
	}

	onClickDelete(event: MouseEvent)
	{
		event.preventDefault();
		event.stopPropagation();

		this.emit('onDelete');
		this.destroy();
		Backend.deleteAction(this.getId());
	}

	onClickMore(event: MouseEvent)
	{
		event.preventDefault();
		event.stopPropagation();

		this.getMenu().show();
	}

	onClickEdit()
	{

	}

	onClickMoveTo(element_id, name, row)
	{
		const result = EventEmitter.emit(BX.DiskFileDialog, 'onFileNeedsToMove', [this]);
		return result.length > 0;
	}

	onMoved({data})
	{
		this.data['STORAGE'] = data;
		this.clearMenu();
	}

	onRenamed(newName)
	{
		if (this.getData('NAME') === newName)
		{
			return;
		}

		this.rename(newName);
		Backend.renameAction(this.getId(), newName);
	}

	//region HTMLEditor functions
	getHTMLForHTMLEditor(tagId: String)
	{
		return `<span contenteditable="false" data-bx-file-id="${this.data.ID}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${Text.encode(this.data.NAME)}</span>`;
	}
	//endregion

	static detect()
	{
		return true;
	}
}

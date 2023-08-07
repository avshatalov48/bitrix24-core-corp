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
		pluggedIn: false,
		insertedInText: false,
	};
	#hintPopup: null;

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

	setInsertedInText(value: boolean = true): boolean
	{
		this.properties.insertedInText = (value === true);
		Dom.addClass(this.getContainer(), '--edit-text-preview');
		this.emit('onIsInsertedInText');
	}

	isInsertedInText(): boolean
	{
		return this.properties.insertedInText;
	}

	getNameWithoutExtension(): string
	{
		let nameParts = this.data['NAME'].split('.');
		if (nameParts.length > 1)
		{
			nameParts.pop();
		}

		const nameWithoutExtension = nameParts.join('.');
		if (nameWithoutExtension.length > 50)
		{
			return nameWithoutExtension.substr(0, 39) + '...' + nameWithoutExtension.substr(-5);
		}

		return nameWithoutExtension;
	}

	#handleMouseEnter(event: Event)
	{
		if (this.#hintPopup)
		{
			return;
		}

		const targetNode = event.currentTarget;
		const targetNodeWidth = targetNode.offsetWidth;

		this.#hintPopup = new BX.PopupWindow({
			content: Loc.getMessage('WDUF_ITEM_MENU_INSERT_INTO_THE_TEXT'),
			cacheable: false,
			animation: 'fading-slide',
			bindElement: targetNode,
			offsetTop: 0,
			bindOptions: {
				position: 'top',
			},
			darkMode: true,
			events: {
				onClose: () => {
					this.#hintPopup.destroy();
					this.#hintPopup = null;
				},
				onShow: (event) => {
					const popup = event.getTarget();
					popup.getPopupContainer().style.display = 'block'; // bad hack

					const offsetLeft = (targetNodeWidth / 2) - popup.getPopupContainer().offsetWidth / 2;
					popup.setOffset({offsetLeft: offsetLeft + 40});
					popup.setAngle({offset: popup.getPopupContainer().offsetWidth / 2 - 17});
				}
			}
		});

		this.#hintPopup.show();
	}

	#handleMouseLeave(event: Event)
	{
		if (!this.#hintPopup)
		{
			return;
		}

		this.#hintPopup.close();
		this.#hintPopup = null;
	}

	getButtonBox(): HTMLElement
	{
		let insertInText = '';
		if (this.isPluggedIn())
		{
			insertInText = Tag.render`
				<div
					class="disk-file-thumb-btn-text-copy"
					onclick="${this.onClickInsertInText.bind(this)}"
					onmouseenter="${this.#handleMouseEnter.bind(this)}"
					onmouseleave="${this.#handleMouseLeave.bind(this)}"
				>
				</div>`;
		}

		return Tag.render`
			<div class="disk-file-thumb-btn-box">
				${insertInText}
				<div class="disk-file-thumb-btn-more" data-bx-role="more" onclick="${this.onClickMore.bind(this)}"></div>
			</div>
		`;
	}

	getDeleteButton(): HTMLElement
	{
		return Tag.render`
			<div class="disk-file-thumb-btn-close-box">
				<div class="disk-file-thumb-btn-close" onclick="${this.onClickDelete.bind(this)}"></div>
			</div>
		`;
	}

	getNameBox(nameWithoutExtension, extension): HTMLElement
	{
		let extensionNode = '';
		if (extension)
		{
			extensionNode = Tag.render`<span class="disk-file-thumb-file-extension">.${extension}</span>`;
		}

		return Tag.render`
			<div class="disk-file-thumb-text-box">
				<div data-bx-role="name" class="disk-file-thumb-text">
					${nameWithoutExtension}
					${extensionNode}
				</div>
			</div>
		`;
	}

	getIcon(extension): HTMLElement
	{
		return Tag.render`
			<div data-bx-role="icon" class="ui-icon ui-icon-file-${extension} disk-file-thumb-icon"><i></i></div>
		`;
	}

	getContainer(): Element
	{
		return this.cache.remember('container', () => {

			const nameWithoutExtension = Text.encode(this.getNameWithoutExtension());
			let extension = Text.encode(this.data['EXTENSION']).toLowerCase();

			switch (extension) {
				case 'pptx':
					extension = 'ppt';
					break;
			}

			return Tag.render`
		<div class="disk-file-thumb disk-file-thumb-file disk-file-thumb--${extension}">
			${this.getIcon(extension)}
			${this.getNameBox(nameWithoutExtension, extension)}
			${this.getDeleteButton()}
			${this.getButtonBox()}
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
							this.onClickInsertInText(event);
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

	onClickInsertInText(event: MouseEvent)
	{
		this.emit('onClickInsertInText');
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
		if (this.getData('TYPE_FILE') === 'player')
		{
			return `<img contenteditable="false" class="bxhtmled-player-surrogate" data-bx-file-id="${Text.encode(this.data.ID)}" id="${tagId}" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" />`;
		}
		return `<span contenteditable="false" data-bx-file-id="${Text.encode(this.data.ID)}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${Text.encode(this.data.NAME)}</span>`;
	}
	//endregion

	static detect()
	{
		return true;
	}
}

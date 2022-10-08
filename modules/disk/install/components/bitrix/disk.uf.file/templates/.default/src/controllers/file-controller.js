import {Type, Dom, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import DefaultController from './default-controller';
import ItemMoreButton from '../items/item-more';
import FileParser from './file-parser';
import type {ItemSavedType, ItemNew} from '../items/item-type';
import getItem from '../items/index';

export default class FileController extends DefaultController {
	maxVisibleCount = 10;
	parser: FileParser;
	prefixHTMLNode = 'disk-attach-';

	constructor({id, container, fieldName, multiple, eventObject})
	{
		super({
			container: container.querySelector('[data-bx-role="placeholder"]'),
			eventObject: eventObject});
		this.items = new Map();
		this.multiple = multiple !== false;
		this.fieldName = fieldName;
		if (!container.querySelector('[data-bx-role="placeholder"]'))
		{
			return;
		}
		if (this.isPluggedIn())
		{
			this.buildInHTMLEditor();
		}
	}

	buildInHTMLEditor()
	{
		if (this.getParser().hasInterface())
		{
			EventEmitter.emit(this.eventObject, 'OnParserRegister', this.getParser().getInterface());
		}
		EventEmitter.subscribe(this.eventObject, 'onReinitializeBefore', ({data: [text, fileData]}) => {
			let manualClearing = true;
			if (fileData)
			{
				Object.values(fileData).forEach((uf) => {
					if (uf &&
						uf['USER_TYPE_ID'] === "disk_file"
						&& uf['FIELD_NAME'].replace('[]', '') === this.fieldName.replace('[]', '')
					)
					{
						try {
							const items = [];
							const duplicateControlItems = {};
							if (Type.isArray(uf['VALUE']))
							{
								uf['VALUE'].forEach((id) => {
									let node = document.querySelector(['#', this.prefixHTMLNode, id].join(''));
									const stringId = String(id);
									if (!node || duplicateControlItems[stringId])
									{
										return;
									}
									duplicateControlItems[stringId] = true;

									const img = node.querySelector('img') || node.querySelector('div[data-bx-preview]');
									const infoNode = img || node;
									const name = infoNode.hasAttribute("data-title") ? infoNode.getAttribute("data-title")
										: (infoNode.hasAttribute("data-bx-title") ? infoNode.getAttribute("data-bx-title") : '');

									const itemData = {
										ID: id,
										FILE_ID: infoNode.getAttribute("bx-attach-file-id"),
										// IS_LOCKED: boolean
										// IS_MARK_DELETED: boolean
										// EDITABLE: boolean
										// FROM_EXTERNAL_SYSTEM: boolean

										CAN_RESTORE: false,
										CAN_UPDATE: false,
										CAN_RENAME: false,
										CAN_MOVE: false,

										COPY_TO_ME_URL: null,
										DELETE_URL: null,
										DOWNLOAD_URL: null,
										EDIT_URL: null,
										VIEW_URL: null,
										PREVIEW_URL: null,
										BIG_PREVIEW_URL: null,

										EXTENSION: name.split('.').pop(),
										NAME: name,
										SIZE: infoNode.getAttribute("data-bx-size"),
										SIZE_BYTES: infoNode.getAttribute("data-bx-size"),
										STORAGE: 'disk',
										TYPE_FILE: infoNode.getAttribute("bx-attach-file-type"),
										// width: node.getAttribute("data-bx-width"),
										// height: node.getAttribute("data-bx-height"),
									};
									if (img)
									{
										itemData['PREVIEW_URL'] = img.hasAttribute('data-bx-preview') && Type.isStringFilled(img.getAttribute('data-bx-preview'))
											? img.getAttribute('data-bx-preview') : (
												img.hasAttribute('data-thumb-src') && Type.isStringFilled(img.getAttribute('data-thumb-src'))
													? img.getAttribute('data-thumb-src') :
													img.src
											);
										itemData['BIG_PREVIEW_URL'] = img.hasAttribute("data-bx-src")
											? img.getAttribute("data-bx-src") :
											img.getAttribute('data-src')
									}
									items.push(itemData);
								});
							}
							this.set(items);
							manualClearing = false;
						}catch (e) {
							console.log('e: ', e);
						}
					}
				});
			}
			if (manualClearing !== false)
			{
				this.clear();
			}
		});
	}

	set(values: Array<ItemSavedType>): Promise
	{
		this.clear();

		let counter = this.maxVisibleCount;

		return new Promise((resolve) => {
			values.forEach((itemData: ItemSavedType) => {
				const item = this.addItem(itemData);
				counter--;
				if (counter > 0)
				{
					this.appendNode(item);
				}
				else if (counter === 0)
				{
					this.container.appendChild(this.getItemMore().reset().getContainer());
				}
				else
				{
					this.getItemMore().increment();
				}
			});
			resolve();
		});
	}

	clear()
	{
		this.items.forEach((item) => {
			item.destroy();
		});

		this.container.innerHTML = '';
	}

	// to add into DOM fom uploader
	add(itemData: ItemNew, itemContainer: ?Element)
	{
		this.multiple || this.clear();
		const item = this.addItem(itemData);
		this.appendNode(item, itemContainer);
		return item;
	}

	addItem(itemData: ItemSavedType)
	{
		const item = getItem(itemData);

		let input = this.getContainer()
			.querySelector(`[data-bx-role="reserve-item"][value="${Text.encode(item.getId())}"]`);
		if (!input)
		{
			input = document.createElement('INPUT');
			input.name = this.fieldName;
			input.type = 'hidden';
			input.value = item.getId();
			this.container.appendChild(input);
		}
		item.subscribe('onDelete', () => {
			if (input && input.parentNode)
			{
				input.parentNode.removeChild(input);
			}
			EventEmitter.emit(this, 'OnItemDelete', item);
		});
		item.subscribe('onDestroy', () => {
			this.items.delete(item.getId())
		});
		if (this.isPluggedIn())
		{
			EventEmitter.emit(this.eventObject, 'onShowControllers:File:Increment');
			if (this.getParser().hasInterface())
			{
				item.setPluggedIn();
				item.subscribe('onClickInsertInText', () => {
					this.getParser().insertFile(item.getId());
				});
				item.subscribe('onDelete', () => {
					this.getParser().deleteFile(item.getId());
				});
				item.subscribe('onDestroy', () => {
					EventEmitter.emit(this.eventObject, 'onShowControllers:File:Decrement');
				});
			}
		}

		this.items.set(item.getId(), item);
		return item;
	}

	getItem(id)
	{
		return this.items.get(id);
	}

	/*@
	Appends node to the container
	 */
	appendNode(item: Item, itemContainer: ?Element)
	{
		if (itemContainer)
		{
			if (itemContainer.parentNode)
			{
				itemContainer.parentNode.replaceChild(item.getContainer(), itemContainer)
			}
		}
		else
		{
			this.container.appendChild(item.getContainer());
		}

		return item;
	}

	getItemMore(): ItemMoreButton
	{
		return this.cache.remember('moreButton', () => {
			const res = new ItemMoreButton();
			EventEmitter.subscribe(res, 'onGetMore', this.showMoreItems.bind(this));
			return res;
		});
	}

	showMoreItems({data:{itemsCount}})
	{
		const timeoutCounter = itemsCount;
		const itemMoreNode = this.getItemMore().getContainer();
		itemMoreNode.style.opacity = '0';
		itemMoreNode.style.visibility = 'hidden';
		this.items.forEach((item: Item) => {
			if (itemsCount > 0)
			{
				if (!item.hasContainer())
				{
					itemsCount--;
					const node = document.createElement('DIV');
					itemMoreNode.parentNode.insertBefore(node, itemMoreNode);
					setTimeout(() => {
						Dom.style(item.getContainer(), 'opacity', '0');
						Dom.addClass(item.getContainer(), 'disk-file-thumb--animate');
						Dom.style(item.getContainer(), 'opacity', '');
						node.parentNode.replaceChild(item.getContainer(), node);
						item.getContainer().addEventListener('transitionend', () => {
							Dom.removeClass(item.getContainer(), 'disk-file-thumb--animate');
						})
					}, (timeoutCounter - itemsCount) * 100);
				}
			}
		});
		if (itemsCount <= 0)
		{
			setTimeout(() => {
				itemMoreNode.style.opacity = '';
				itemMoreNode.style.visibility = '';
			}, timeoutCounter * 100 + 100);
		}
	}

	getParser()
	{
		this.parser = this.parser || new FileParser(this);
		return this.parser;
	}
}

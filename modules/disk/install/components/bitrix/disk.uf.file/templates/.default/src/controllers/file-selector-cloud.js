import {Uri} from 'main.core';
import {EventEmitter} from 'main.core.events';
import Backend from '../backend';
import type {ItemSavedCloudType, ItemSavedType, ItemUploadedType, ItemSelectedCloudType} from "../items/item-type";
import ItemNewSelectedCloud from "../items/item-new-selected-cloud";
import DefaultController from "./default-controller";

let justCounter = 0;
export default class FileSelectorCloud extends DefaultController
{
	dialogName: string;
	services: Array<Element> = [];
	items: Map = new Map;

	constructor({container, eventObject})
	{
		super({
			container: container.querySelector('[data-bx-role="placeholder"]'),
			eventObject: eventObject
		});

		if (!this.getContainer())
		{
			return;
		}

		Array.from(
			container
				.querySelectorAll('[data-bx-role="file-external-controller"]')
		)
			.forEach((item) => {
				this.services.push(item)
			});
		Array.from(
			container
				.querySelectorAll('.diskuf-selector-link-cloud')
		)
			.forEach((item) => {
				this.services.push(item)
			});

		this.eventObject = eventObject;
		this.dialogName = ['selectFileCloud', justCounter++].join('-');
		this.services.forEach((node) => {
			node.addEventListener('click', this.onClick.bind(this));
		});
		this.openSection = this.openSection.bind(this);
		this.selectFile = this.selectFile.bind(this);
	}

	onClick(event: MouseEvent)
	{
		EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
		this.currentService = event.currentTarget.getAttribute('data-bx-doc-handler');

		Backend
			.getSelectedCloudData(this.dialogName, this.currentService)
			.then(() => {
				setTimeout(() => {
					BX.DiskFileDialog.obCallback[this.dialogName] = {saveButton: this.selectFile};
					BX.DiskFileDialog.openDialog(this.dialogName);
				}, 10)
			});
	}

	openSection({data: [link, name]})
	{
		if (name === this.dialogName)
		{
			BX.DiskFileDialog.target[name] = Uri.addParam(link, {dialog2: 'Y'});
		}
	}

	selectFile(tab, path, selected)
	{
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
		for (let id in selected)
		{
			if (selected.hasOwnProperty(id) && selected[id].type === 'file')
			{
				this.catchFile(selected[id], this.currentService);
			}
		}
		this.currentService = null;
	}

	catchFile(fileObject: ItemSelectedCloudType, service)
	{
		if (this.items.has(fileObject.id))
		{
			return;
		}

		fileObject['service'] = fileObject['provider'] || service;
		const item = new ItemNewSelectedCloud(fileObject.id, fileObject);
		this.items.set(item.id, item);
		EventEmitter.subscribe(item, 'onUploadDone', ({data: [itemData: ItemUploadedType]}) => {
			if (this.items.has(item.id))
			{
				this.items.delete(item.id);
				EventEmitter.emit(this, 'onUploadDone', {
					itemData: this.convertToItemSavedType(itemData),
					itemContainer: item.getContainer(),
				});
			}
		});
		EventEmitter.subscribe(item, 'onUploadError', () => {
			this.items.delete(item.id);
			EventEmitter.emit(this, 'onUploadError', {itemContainer: item.getContainer()});
		});
		EventEmitter.subscribe(item, 'onDelete', () => {
			this.items.delete(item.id);
			this.container.removeChild(item.getContainer());
		});
		this.container.appendChild(item.getContainer());
	}

	convertToItemSavedType(item: ItemSavedCloudType) :ItemSavedType
	{
		const extension = item.name.split('.').pop().toLowerCase();
		const itemData = {
			ID: item.ufId,
			IS_LOCKED: false,
			IS_MARK_DELETED: false,

			EDITABLE: false,
			FROM_EXTERNAL_SYSTEM: true,

			CAN_RESTORE: false,
			CAN_RENAME: false,
			CAN_UPDATE: false,
			CAN_MOVE: false,

			COPY_TO_ME_URL: null,
			DELETE_URL: null,
			DOWNLOAD_URL: null,
			EDIT_URL: null,
			VIEW_URL: null,
			PREVIEW_URL: (item.previewUrl ? item.previewUrl : ''),
			BIG_PREVIEW_URL: (item.previewUrl ? item.previewUrl.replace(/\&(width|height)=\d+/gi, '') : null),

			EXTENSION: extension === item.name ? '' : extension,
			NAME: item.name,
			SIZE: item.sizeFormatted,
			SIZE_BYTES: item.size,
			STORAGE: item.storage,
		};
		return itemData;
	}
}

import {Uri, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import DefaultController from './default-controller';
import Backend from '../backend';
import type {ItemSelectedType, ItemSavedType} from "../items/item-type";

let justCounter = 0;
export default class FileSelector extends DefaultController
{
	opened: boolean = false;
	dialogName: string;
	constructor({container, eventObject})
	{
		const node = container.querySelector('[data-bx-role="file-local-controller"]') ||
			container.querySelector('.diskuf-selector-link');

		super({
			container: node,
			eventObject: eventObject
		});

		this.dialogName = ['selectFile', justCounter++].join('-');
		if (!this.getContainer())
		{
			return;
		}
		this.getContainer().addEventListener('click', this.onClick.bind(this));
		this.openSection = this.openSection.bind(this);
		this.selectFile = this.selectFile.bind(this);
		EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
	}

	onClick()
	{
		Backend
			.getSelectedData(this.dialogName)
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
			if (selected.hasOwnProperty(id))
			{
				EventEmitter.emit(this, 'onUploadDone', {itemData: this.convertToItemSavedType(selected[id])});
			}
		}
	}

	convertToItemSavedType(item: ItemSelectedType) :ItemSavedType
	{
		return {
			ID: item.id,
			IS_LOCKED: false,
			IS_MARK_DELETED: false,

			EDITABLE: false,
			FROM_EXTERNAL_SYSTEM: false,

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

			EXTENSION: item.ext,
			NAME: item.name,
			SIZE: item.size,
			SIZE_BYTES: item.sizeInt,
			STORAGE: item['storage'] ?? Loc.getMessage('WDUF_MY_DISK'),
		};
	}
}

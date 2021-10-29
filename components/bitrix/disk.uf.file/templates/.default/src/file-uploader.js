import {EventEmitter} from 'main.core.events';
import Options from './options';
import ItemNew from './items/item-new';
import 'main.core_uploader';
import type {ItemUploadedType, ItemSavedType} from "./items/item-type";

export default class FileUploader
{
	#itemsCount: number = 0;
	container: Element;
	static #deleted = Symbol('deleted');


	constructor({id, container, dropZone, input})
	{
		this.container = container;
		this.agent = BX.Uploader.getInstance({
			id: id,
			allowUpload: 'A',
			uploadFormData: 'N',
			uploadMethod: 'immediate',
			uploadFileUrl: Options.urlUpload,
			showImage: false,
			sortItems: false,
			dropZone: dropZone,
			input: input,
			pasteFileHashInForm: false,
		});

		EventEmitter.subscribe(this.agent, 'onFileIsCreated', this.catchFile.bind(this));
		EventEmitter.subscribe(this.agent, 'onPackageIsInitialized', ({compatData: [packageFormer]}) => {
			const previewParams = {
				width: Options.previewSize.width,
				height: Options.previewSize.height,
				exact: 'N'
			};
			if (packageFormer.data)
			{
				packageFormer.data['previewParams'] = previewParams;
			}
			else
			{
				packageFormer.post.data['previewParams'] = previewParams;
			}
		});
	}

	catchFile({compatData: [fileId, fileObject]})
	{
		this.incrementItemsCount();
		const item = new ItemNew(fileId, fileObject);
		EventEmitter.subscribe(item, 'onUploadDone', ({data: [itemData: ItemUploadedType, blob]}) => {
			if (!item[this.constructor.#deleted])
			{
				this.decrementItemsCount();
				EventEmitter.emit(this, 'onUploadDone', {
					itemData: this.convertToItemSavedType(itemData),
					itemContainer: item.getContainer(),
					blob: blob
				});
			}
		});
		EventEmitter.subscribe(item, 'onUploadError', ({data: [blob]}) => {
			this.decrementItemsCount();
			item[this.constructor.#deleted] = true;
			EventEmitter.emit(this, 'onUploadError', {itemContainer: item.getContainer(), blob: blob});
		});
		EventEmitter.subscribe(item, 'onDelete', () => {
			this.decrementItemsCount();
			item[this.constructor.#deleted] = true;
			this.container.removeChild(item.getContainer());
		});
		this.container.appendChild(item.getContainer());
	}

	addTestThumb(progressPercent)
	{
		const item = new ItemNew(Math.ceil(Math.random() * 1000), {name: 'test.file.js', size: '348 Kb', deleteFile: () => {}});
		this.container.appendChild(item.getContainer());
		if (progressPercent > 0)
		{
			item.onUploadProgress({compatData: [null, progressPercent]})
		}
		EventEmitter.subscribe(item, 'onDelete', () => {
			this.container.removeChild(item.getContainer());
		});
	}

	addTestErrorThumb()
	{
		const item = new ItemNew(Math.ceil(Math.random() * 1000), {name: 'test.file.js', size: '348 Kb', deleteFile: () => {}});
		this.container.appendChild(item.getContainer());
		item.onUploadError({compatData: [{file: null}]});
		EventEmitter.subscribe(item, 'onDelete', () => {
			this.container.removeChild(item.getContainer());
		});
	}

	upload([...files])
	{
		this.agent.onAttach(files);
	}

	convertToItemSavedType(item: ItemUploadedType) :ItemSavedType
	{
		return {
			ID: item.attachId,
			IS_LOCKED: false,
			IS_MARK_DELETED: false,

			EDITABLE: false,
			FROM_EXTERNAL_SYSTEM: false,

			CAN_RESTORE: false,
			CAN_UPDATE: item.canChangeName,
			CAN_RENAME: item.canChangeName,
			CAN_MOVE:  item.canChangeName,

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
			STORAGE: item.storage,
			TYPE_FILE: item.fileType,
		};
	}
	incrementItemsCount()
	{
		if (this.#itemsCount <= 0)
		{
			EventEmitter.emit(this, 'onUploadIsStart');
		}
		this.#itemsCount++;
	}
	decrementItemsCount()
	{
		if (this.#itemsCount === 1)
		{
			EventEmitter.emit(this, 'onUploadIsDone');
		}

		this.#itemsCount = Math.max(this.#itemsCount--, 0);
	}
}

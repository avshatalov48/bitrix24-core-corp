import { Event, Runtime, Text, Type } from 'main.core';
import { Uploader, UploaderEvent, UploaderFile, UploaderFileInfo, Helpers as UploaderHelpers } from 'ui.uploader.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

type FormBriefOptions = {
	UID: string,
	controlName: string,
	hideSelectDialog: boolean,
};

type UploaderMenuOptions = {
	id: string,
	container: HTMLElement,
	eventObject: HTMLElement,
	hiddenFieldName: string,
};

const instances: Map<string, UploadMenu> = new Map();

class UploadMenu
{
	#container: HTMLElement = null;
	#eventObject: HTMLElement = null;
	#uploader: Uploader = null;
	#id: string = `dialog-${Text.getRandom(5)}`;
	#onProgressHandler: Function = null;

	constructor(options: UploaderMenuOptions)
	{
		this.#container = options.container;
		this.#eventObject = options.eventObject;

		let browseElement: HTMLElement = null;
		const input: HTMLInputElement = this.#container.querySelector('.diskuf-fileUploader');
		if (input)
		{
			if (input.tagName.toLowerCase() === 'input')
			{
				browseElement = input.parentNode;
				input.disabled = true;
			}
			else
			{
				browseElement = input;
			}
		}

		const openMyDriveLink: HTMLElement = this.#container.querySelector('.diskuf-selector-link');
		if (openMyDriveLink)
		{
			Event.bind(openMyDriveLink.parentNode, 'click', this.#handleMyDriveClick.bind(this));
		}

		const openCloudDriveLink: HTMLElement = this.#container.querySelector('.diskuf-selector-link-cloud');
		if (openCloudDriveLink)
		{
			Event.bind(openCloudDriveLink.parentNode, 'click', this.#handleCloudDriveClick.bind(this));
		}

		const eventData = { _onUploadProgress: null };
		EventEmitter.emit(this.#eventObject, 'DiskDLoadFormControllerInit', new BaseEvent({ compatData: [eventData] }));
		if (Type.isFunction(eventData._onUploadProgress))
		{
			this.#onProgressHandler = eventData._onUploadProgress;
		}

		this.#uploader = new Uploader({
			id: options.id,
			controller: 'disk.uf.integration.diskUploaderController',
			browseElement,
			multiple: true,
			maxFileSize: null,
			treatOversizeImageAsFile: true,
			ignoreUnknownImageTypes: true,
			hiddenFieldName: options.hiddenFieldName,
			hiddenFieldsContainer: this.#container,
			events: {
				[UploaderEvent.FILE_ADD]: this.#handleFileAdd.bind(this),
				[UploaderEvent.FILE_COMPLETE]: this.#handleFileComplete.bind(this),
				[UploaderEvent.FILE_ERROR]: this.#handleFileError.bind(this),
				[UploaderEvent.FILE_UPLOAD_PROGRESS]: this.#handleFileProgress.bind(this),
			},
		});
	}

	getUploader(): Uploader
	{
		return this.#uploader;
	}

	#handleFileComplete(event: BaseEvent): void
	{
		const file: UploaderFile = event.getData().file;

		EventEmitter.emit(
			this.#eventObject,
			'OnFileUploadSuccess',
			new BaseEvent({
				compatData: [
					this.#createFileResult(file),
					this,
					file.getBinary(),
					this.#createFileInfo(file)
				]
			})
		);
	}

	#handleFileAdd(event: BaseEvent): void
	{
		if (this.#onProgressHandler !== null)
		{
			const file: UploaderFile = event.getData().file;
			this.#onProgressHandler(this.#createFileInfo(file), 5);
		}
	}

	#handleFileProgress(event: BaseEvent): void
	{
		if (this.#onProgressHandler !== null)
		{
			const file: UploaderFile = event.getData().file;
			const progress: Number = event.getData().progress;

			this.#onProgressHandler(this.#createFileInfo(file), progress);
		}
	}

	#handleFileError(event: BaseEvent): void
	{
		const file: UploaderFile = event.getData().file;

		console.log('UploadMenu Error:', file.getError());

		EventEmitter.emit(
			this.#eventObject,
			'OnFileUploadFailed',
			new BaseEvent({compatData: [this, file.getBinary(), this.#createFileInfo(file)]})
		);
	}

	#handleMyDriveClick(): void
	{
		Runtime.loadExtension('disk.uploader.user-field-widget').then((exports) => {
			const { openDiskFileDialog } = exports;
			openDiskFileDialog({
				dialogId: `file-${this.#id}`,
				uploader: this.#uploader,
			});
		});
	}

	#handleCloudDriveClick(): void
	{
		Runtime.loadExtension('disk.uploader.user-field-widget').then((exports) => {
			const { openCloudFileDialog } = exports;
			openCloudFileDialog({
				dialogId: `cloud-${this.#id}`,
				uploader: this.#uploader,
			});
		});
	}

	#createFileInfo(file: UploaderFile): UploaderFileInfo
	{
		const fileInfo: UploaderFileInfo = file.getState();

		fileInfo.size = file.getSizeFormatted();
		fileInfo.sizeInt = file.getSize();
		fileInfo.ext = file.getExtension();
		fileInfo.nameWithoutExt = UploaderHelpers.getFilenameWithoutExtension(file.getName());

		return fileInfo;
	}

	#createFileResult(file: UploaderFile): {
		element_id: string,
		element_name: string,
		element_url: string,
		storage: string,
	}
	{
		return {
			element_id: file.getServerFileId(),
			element_name: file.getName(),
			element_url: file.getPreviewUrl(),
			storage: file.getCustomData('storage') || 'disk',
		};
	}
}

export const add = (options: FormBriefOptions): ?UploadMenu => {
	const container = document.getElementById(`diskuf-selectdialog-${options['UID']}`);
	if (!container)
	{
		return null;
	}

	if (instances.has(options['UID']))
	{
		return instances.get(options['UID']);
	}

	const uploadMenu: UploadMenu = new UploadMenu({
		id: `disk-uf-file-${options['UID']}`,
		container,
		eventObject: container.parentNode,
		hiddenFieldName: options.controlName,
	});

	instances.set(options['UID'], uploadMenu);

	return uploadMenu;
};

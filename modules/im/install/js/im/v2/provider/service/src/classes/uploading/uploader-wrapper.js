import { BaseEvent, EventEmitter } from 'main.core.events';
import { Uploader, UploaderEvent } from 'ui.uploader.core';

import { EventType } from 'im.v2.const';

import type { UploaderFile } from 'ui.uploader.core';
import type { MessageWithFile } from '../../uploading';

type UploaderWrapperOptions = {
	diskFolderId: number,
	uploaderId: string,
}

export class UploaderWrapper extends EventEmitter
{
	#uploaderRegistry: {[uploaderId: string]: Uploader} = {};
	#onUploadCancelHandler: Function;

	static eventNamespace = 'BX.Messenger.v2.Service.Uploading.UploaderWrapper';

	static events = {
		onFileAddStart: 'onFileAddStart',
		onFileAdd: 'onFileAdd',
		onFileUploadStart: 'onFileUploadStart',
		onFileUploadProgress: 'onFileUploadProgress',
		onFileUploadComplete: 'onFileUploadComplete',
		onFileUploadError: 'onFileUploadError',
		onFileUploadCancel: 'onFileUploadCancel',
		onMaxFileCountExceeded: 'onMaxFileCountExceeded',
	};

	constructor()
	{
		super();
		this.setEventNamespace(UploaderWrapper.eventNamespace);

		this.#onUploadCancelHandler = this.#onUploadCancel.bind(this);
		EventEmitter.subscribe(EventType.uploader.cancel, this.#onUploadCancelHandler);
	}

	createUploader(options: UploaderWrapperOptions)
	{
		const { diskFolderId, uploaderId, autoUpload } = options;

		this.#uploaderRegistry[uploaderId] = new Uploader({
			autoUpload,
			controller: 'disk.uf.integration.diskUploaderController',
			multiple: true,
			controllerOptions: {
				folderId: diskFolderId,
			},
			imageResizeWidth: 1280,
			imageResizeHeight: 1280,
			imageResizeMode: 'contain',
			imageResizeFilter: (file: UploaderFile) => !file.getCustomData('sendAsFile'),
			imageResizeMimeType: 'image/jpeg',
			imageResizeMimeTypeMode: 'force',
			imagePreviewHeight: 400,
			imagePreviewWidth: 400,
			events: {
				[UploaderEvent.FILE_ADD_START]: (event) => {
					this.emit(UploaderWrapper.events.onFileAddStart, event);
				},
				[UploaderEvent.FILE_UPLOAD_START]: (event) => {
					this.emit(UploaderWrapper.events.onFileUploadStart, event);
				},
				[UploaderEvent.FILE_ADD]: (event) => {
					this.emit(UploaderWrapper.events.onFileAdd, event);
				},
				[UploaderEvent.FILE_UPLOAD_PROGRESS]: (event) => {
					this.emit(UploaderWrapper.events.onFileUploadProgress, event);
				},
				[UploaderEvent.FILE_UPLOAD_COMPLETE]: (event) => {
					this.emit(UploaderWrapper.events.onFileUploadComplete, event);
				},
				[UploaderEvent.ERROR]: (event) => {
					this.emit(UploaderWrapper.events.onFileUploadError, event);
				},
				[UploaderEvent.FILE_ERROR]: (event) => {
					this.emit(UploaderWrapper.events.onFileUploadError, event);
				},
				[UploaderEvent.MAX_FILE_COUNT_EXCEEDED]: (event) => {
					this.emit(UploaderWrapper.events.onMaxFileCountExceeded, event);
				},
				[UploaderEvent.UPLOAD_COMPLETE]: () => {
					this.#uploaderRegistry[uploaderId].destroy({ removeFilesFromServer: false });
				},
			},
		});
	}

	start(uploaderId: string)
	{
		this.#uploaderRegistry[uploaderId].setAutoUpload(true);
		this.#uploaderRegistry[uploaderId].start();
	}

	addFiles(tasks: MessageWithFile[]): UploaderFile[]
	{
		const addedFiles = [];
		tasks.forEach((task) => {
			const file = this.#addFile(task);
			if (file)
			{
				addedFiles.push(file);
			}
		});

		return addedFiles;
	}

	getFiles(uploaderId): UploaderFile[]
	{
		return this.#uploaderRegistry[uploaderId].getFiles();
	}

	#addFile(task: MessageWithFile): ?UploaderFile
	{
		return this.#uploaderRegistry[task.uploaderId].addFile(
			task.file,
			{
				id: task.tempFileId,
				customData: {
					dialogId: task.dialogId,
					chatId: task.chatId,
					tempMessageId: task.tempMessageId,
				},
			},
		);
	}

	#onUploadCancel(event: BaseEvent)
	{
		const { tempFileId, tempMessageId } = event.getData();
		if (!tempFileId || !tempMessageId)
		{
			return;
		}

		this.#removeFileFromUploader(tempFileId);
		this.emit(UploaderWrapper.events.onFileUploadCancel, { tempMessageId, tempFileId });
	}

	#removeFileFromUploader(tempFileId: string)
	{
		const uploaderList = Object.values(this.#uploaderRegistry);
		for (const uploader of uploaderList)
		{
			if (!uploader.getFile)
			{
				continue;
			}

			const file = uploader.getFile(tempFileId);
			if (file)
			{
				file.remove();

				break;
			}
		}
	}

	destroy()
	{
		EventEmitter.unsubscribe(EventType.uploader.cancel, this.#onUploadCancelHandler);
	}
}

import { Runtime, Type, Loc, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { BaseEvent } from 'main.core.events';

import { FileStatus } from 'ui.uploader.core';
import { VueUploaderAdapter } from 'ui.uploader.vue';
import type { UploaderFileInfo } from 'ui.uploader.core';
import type { Store } from 'ui.vue3.vuex';
import { VueRefValue } from 'ui.vue3';
import { UI } from 'ui.notification';

import { UserFieldMenu, openDiskFileDialog } from 'disk.uploader.user-field-widget';
import { Model } from 'tasks.v2.const';
import { Core } from 'tasks.v2.core';
import { apiClient } from 'tasks.v2.lib.api-client';

import { mapDtoToModel } from './mappers';
import { processCheckListFileIds } from './util';
import type { FileId, FileDto } from './types';

export type BrowseParams = {
	bindElement: HTMLElement,
	onShowCallback?: Function,
	onHideCallback?: Function,
	compact: boolean,
};

export const EntityTypes = Object.freeze({
	Task: 'task',
	CheckListItem: 'checkListItem',
});

export type EntityType = $Values<typeof EntityTypes>;

type Options = {
	parentEntityId?: number | string,
}

export class FileService extends EventEmitter
{
	#entityId: number | string;
	#entityType: EntityType;
	#options: Options;
	#menu: UserFieldMenu;
	#loadedIds: Set<FileId> = new Set();
	#objectsIds: { [id: FileId]: number } = {};
	#promises: Promise[] = [];
	#adapter: VueUploaderAdapter;
	#fileBrowserClosed: boolean = false;
	#filesToAttach: UploaderFileInfo[] = [];
	#filesToDetach: UploaderFileInfo[] = [];
	#isDetachedErrorMode: boolean = false;
	#saveAttachedFilesDebounced: Function;
	#saveDetachedFilesDebounced: Function;

	constructor(
		entityId: number | string,
		entityType: EntityType = EntityTypes.Task,
		options: Options = {},
	)
	{
		super();

		this.setEventNamespace('Tasks.V2.Provider.Service.FileService');
		this.setEntityId(entityId, entityType);
		this.initAdapter(entityId, entityType);

		this.#options = options;

		this.#saveAttachedFilesDebounced = Runtime.debounce(this.#saveAttachedFiles, 3000, this);
		this.#saveDetachedFilesDebounced = Runtime.debounce(this.#saveDetachedFiles, 3000, this);
	}

	initAdapter(entityId: number | string, entityType: EntityType): void
	{
		this.#adapter = new VueUploaderAdapter({
			id: getServiceKey(entityId, entityType),
			controller: 'disk.uf.integration.diskUploaderController',
			imagePreviewHeight: 1200,
			imagePreviewWidth: 1200,
			imagePreviewQuality: 0.85,
			ignoreUnknownImageTypes: true,
			treatOversizeImageAsFile: true,
			multiple: true,
			maxFileSize: null,
		});

		this.#adapter.subscribeFromOptions({
			'Item:onAdd': (event: BaseEvent) => {
				const { item: file } = event.getData();

				this.#addLoadedIds([file.serverFileId]);

				this.emit('onFileAdd');
			},
			'Item:onComplete': (event: BaseEvent) => {
				const { item: file } = event.getData();

				this.#addLoadedObjectsIds([file]);

				const fileIds = new Set(this.#entityFileIds);
				if (!this.#getIdsByObjectId(file.customData.objectId).some((id: FileId) => fileIds.has(id)))
				{
					this.#attachFiles([...fileIds, file.serverFileId], file);
				}

				this.emit('onFileComplete', file);
			},
			'Item:onRemove': (event: BaseEvent) => {
				const { item: file } = event.getData();

				const idsToRemove = new Set(this.#getIdsByObjectId(file.customData.objectId));
				this.#removeLoadedObjectsIds(idsToRemove);

				this.#detachFiles(this.#getEntityFileIds(idsToRemove), file);

				this.emit('onFileRemove', { file });
			},
		});
	}

	setEntityId(entityId: number | string, entityType: EntityType = EntityTypes.Task): void
	{
		this.#entityId = entityId;
		this.#entityType = entityType;
	}

	getEntityId(): string
	{
		return this.#entityId;
	}

	getAdapter(): VueUploaderAdapter
	{
		return this.#adapter;
	}

	getFiles(): VueRefValue<UploaderFileInfo>
	{
		return this.#adapter.getReactiveItems();
	}

	isUploading(): boolean
	{
		return this.#adapter.getItems().some(({ status }) => [
			FileStatus.UPLOADING,
			FileStatus.LOADING,
		].includes(status));
	}

	hasUploadingError(): boolean
	{
		return this.#adapter.getItems().some(({ status }) => [
			FileStatus.UPLOAD_FAILED,
			FileStatus.LOAD_FAILED,
		].includes(status));
	}

	browse(params: BrowseParams): void
	{
		this.#menu = new UserFieldMenu({
			dialogId: 'task-card',
			uploader: this.#adapter.getUploader(),
			compact: params.compact || false,
			menuOptions: {
				minWidth: 220,
				animation: 'fading',
				closeByEsc: true,
				bindOptions: {
					forceBindPosition: true,
					forceTop: true,
					position: 'top',
				},
				events: {
					onPopupClose: () => {
						params.onHideCallback?.();
					},
					onPopupShow: () => {
						params.onShowCallback?.();
					},
				},
			},
		});

		this.#menu.show(params.bindElement);
	}

	#browseElement: HTMLElement;

	browseFiles(): void
	{
		if (!this.#browseElement)
		{
			this.#browseElement = document.createElement('div');
			this.#adapter.getUploader().assignBrowse(this.#browseElement);
		}

		this.#browseElement.click();
	}

	browseMyDrive(): void
	{
		openDiskFileDialog({
			dialogId: 'task-card',
			uploader: this.#adapter.getUploader(),
		});
	}

	setFileBrowserClosed(value: boolean): void
	{
		this.#fileBrowserClosed = value;
		this.emit('onFileBrowserClosed');
	}

	isFileBrowserClosed(): boolean
	{
		return this.#fileBrowserClosed;
	}

	resetFileBrowserClosedState(): void
	{
		this.#fileBrowserClosed = false;
	}

	destroy(): void
	{
		this.#adapter.unsubscribeAll('Item:onAdd');
		this.#adapter.unsubscribeAll('Item:onComplete');
		this.#adapter.unsubscribeAll('Item:onRemove');
		this.#adapter.getUploader().destroy();
	}

	async sync(ids: FileId[] | null): Promise<void>
	{
		if (!Type.isArrayFilled(ids))
		{
			return;
		}

		if (ids.every((id: FileId) => this.#loadedIds.has(id)))
		{
			this.#adapter.getItems().forEach((file) => {
				const uploaderIds = [file.serverFileId];
				if (this.#isNewFile(file.serverFileId))
				{
					const objectId = Number(file.serverFileId.slice(1));

					uploaderIds.push(...this.#getIdsByObjectId(objectId));
				}

				if (uploaderIds.some((id: FileId) => ids.includes(id)))
				{
					return;
				}

				this.#adapter.getUploader().removeFile(file.id);
			});
		}

		await this.list(ids);
	}

	async list(ids: FileId[]): Promise<UploaderFileInfo[]>
	{
		const unloadedIds = ids.filter((id) => !this.#isNewFile(id) && !this.#loadedIds.has(id));
		if (unloadedIds.length === 0)
		{
			await Promise.all(this.#promises);

			return this.#adapter.getItems();
		}

		const promise = new Resolvable();
		this.#promises.push(promise);
		this.#addLoadedIds(unloadedIds);

		try
		{
			let data = [];

			if (this.#entityType === EntityTypes.Task)
			{
				data = await apiClient.post('File.list', {
					taskId: this.#entityId,
					ids: unloadedIds,
				});
			}
			else if (this.#entityType === EntityTypes.CheckListItem)
			{
				data = await apiClient.post('Task.CheckList.File.list', {
					taskId: this.#options.parentEntityId,
					ids: unloadedIds,
				});
			}

			const files = data.map((fileDto: FileDto) => mapDtoToModel(fileDto));

			const objectsIds = new Set(Object.values(this.#objectsIds));
			const newFiles = files.filter(({ customData }) => !objectsIds.has(customData.objectId));
			this.#adapter.getUploader().addFiles(newFiles);
			this.#addLoadedObjectsIds(files);

			promise.resolve();

			await Promise.all(this.#promises);

			return this.#adapter.getItems();
		}
		catch (error)
		{
			console.error('FileService: list error', error);

			return [];
		}
	}

	#addLoadedIds(ids: FileId[]): void
	{
		ids.forEach((id: FileId): void => this.#loadedIds.add(id));
	}

	#addLoadedObjectsIds(files: UploaderFileInfo[]): void
	{
		files.forEach((file: UploaderFileInfo) => {
			this.#objectsIds[file.serverFileId] = file.customData.objectId;
		});
	}

	#removeLoadedObjectsIds(ids: FileId[]): void
	{
		ids.forEach((id: FileId) => {
			delete this.#objectsIds[id];
		});
	}

	#getIdsByObjectId(objectIdToFind: number): FileId[]
	{
		return Object.entries(this.#objectsIds)
			.filter(([, objectId]): boolean => objectId === objectIdToFind)
			.map(([id]): FileId => (this.#isNewFile(id) ? id : Number(id)))
		;
	}

	#attachFiles(fileIds: FileId[], attachedFile: UploaderFileInfo): void
	{
		if (this.#entityType === EntityTypes.Task)
		{
			const id = this.#entityId;

			void this.$store.dispatch(`${Model.Tasks}/update`, {
				id,
				fields: { fileIds },
			});

			if (this.#isDetachedErrorMode)
			{
				return;
			}

			if (this.#isRealId(id) && this.#isNewFile(attachedFile.serverFileId))
			{
				this.#filesToAttach.push(attachedFile);
				this.#saveAttachedFilesDebounced();
			}
		}
		else if (this.#entityType === EntityTypes.CheckListItem)
		{
			this.#processCheckListFileIds(fileIds);
		}
	}

	#detachFiles(fileIds: FileId[], detachedFile: UploaderFileInfo): void
	{
		if (this.#entityType === EntityTypes.Task)
		{
			const id = this.#entityId;

			void this.$store.dispatch(`${Model.Tasks}/update`, {
				id,
				fields: { fileIds },
			});

			if (this.#isRealId(id))
			{
				const detachedId = detachedFile.serverFileId;
				const attachedIndex = this.#filesToAttach.findIndex((file) => file.serverFileId === detachedId);
				if (attachedIndex === -1)
				{
					this.#filesToDetach.push(detachedFile);
					this.#saveDetachedFilesDebounced();
				}
				else
				{
					this.#filesToAttach.splice(attachedIndex, 1);
				}
			}
		}
		else if (this.#entityType === EntityTypes.CheckListItem)
		{
			this.#processCheckListFileIds(fileIds);
		}
	}

	#getEntityFileIds(excludedIds: Set<FileId> = new Set()): []
	{
		if (this.#entityType === EntityTypes.Task)
		{
			return this.#entityFileIds.filter((id: FileId) => !excludedIds.has(id));
		}

		if (this.#entityType === EntityTypes.CheckListItem)
		{
			return this.#entityFileIds.filter((attach) => !excludedIds.has(attach.id));
		}

		return [];
	}

	#processCheckListFileIds(fileIds: FileId[]): void
	{
		const attachments = processCheckListFileIds(fileIds);

		void this.$store.dispatch(`${Model.CheckList}/update`, {
			id: this.#entityId,
			fields: { attachments },
		});
	}

	#isRealId(id: number): boolean
	{
		return Number.isInteger(id) && id > 0;
	}

	#isNewFile(id: number | string): boolean
	{
		return String(id).startsWith('n');
	}

	async #saveAttachedFiles(): void
	{
		if (Type.isArrayFilled(this.#filesToAttach))
		{
			try
			{
				const ids = this.#filesToAttach.map((file) => file.serverFileId);
				this.#filesToAttach = [];

				void apiClient.post('File.attach', { task: { id: this.#entityId }, ids });
			}
			catch (error)
			{
				console.error('FileService: saveAttachedFiles error', error);

				this.notifyError(Loc.getMessage('TASKS_V2_NOTIFY_FILE_ATTACH_ERROR'));
			}
		}
	}

	async #saveDetachedFiles(): void
	{
		if (Type.isArrayFilled(this.#filesToDetach))
		{
			const filesBeforeDetach = this.#filesToDetach;

			try
			{
				const ids = this.#filesToDetach.map((file) => file.serverFileId);
				this.#filesToDetach = [];

				await apiClient.post('File.detach', { task: { id: this.#entityId }, ids });
			}
			catch (error)
			{
				console.error('FileService: saveDetachedFiles error', error);

				this.#isDetachedErrorMode = true;

				this.#adapter.getUploader().addFiles(filesBeforeDetach);

				this.#isDetachedErrorMode = false;

				this.notifyError(Loc.getMessage('TASKS_V2_NOTIFY_FILE_DETACH_ERROR'));
			}
		}
	}

	notifyError(content: string): void
	{
		UI.Notification.Center.notify({
			content,
			id: `file-service-error-${Text.getRandom()}`,
		});
	}

	get #entityFileIds(): []
	{
		if (this.#entityType === EntityTypes.Task)
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.#entityId).fileIds;
		}

		return this.$store.getters[`${Model.CheckList}/getById`](this.#entityId).attachments;
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

const services: { [key: string]: FileService } = {};

function getServiceKey(entityId: number, entityType: EntityType): string
{
	return `${entityType}:${entityId}`;
}

export const fileService = {
	get(
		entityId: number,
		entityType: EntityType = EntityTypes.Task,
		options: Options = {},
	): FileService
	{
		const key = getServiceKey(entityId, entityType);
		services[key] ??= new FileService(entityId, entityType, options);

		return services[key];
	},
	replace(tempId: number, entityId: number, entityType: EntityType = EntityTypes.Task): void
	{
		const oldKey = getServiceKey(tempId, entityType);
		const newKey = getServiceKey(entityId, entityType);

		services[newKey] = services[oldKey];
		services[newKey].setEntityId(entityId, entityType);

		delete services[oldKey];
	},
	delete(entityId: number, entityType: EntityType = EntityTypes.Task): void
	{
		const key = getServiceKey(entityId, entityType);

		services[key]?.destroy();

		delete services[key];
	},
};

function Resolvable(): Promise
{
	const promise = new Promise((resolve) => {
		this.resolve = resolve;
	});

	promise.resolve = this.resolve;

	return promise;
}

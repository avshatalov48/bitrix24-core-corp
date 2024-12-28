import { DialogId } from '../../../types/common';
import { FileStatus } from '../../../model/types/files';

export type DiskFolderId = number;
export type FileId = string;
export type TemporallyFileId = string;

export type DiskFolderIdRequestPromiseCollection = Record<DialogId, DiskFolderId | Promise<DiskFolderId>>
export type UploadRegistry = Record<FileId | TemporallyFileId, UploadRegistryData>
export type UploadRegistryData = {
	chatId: number,
	deviceFile: DeviceFile,
	dialogId: string,
	diskFolderId: number,
	temporaryFileId: string,
	realFileIdInt: number,
	temporaryMessageId: string,
	taskId: TemporallyFileId | string,
	status: FileStatus,
}

export type PreparedTasks = {
	taskId: string,
	controller: string,
	controllerOptions: object,
	resize: {
		height: number,
		quality: number,
		width: number,
	},
	type: string,
	mimeType: string,
	chunk: number,
	params: {
		dialogId: string,
		temporaryMessageId: string,
	},
	name: string,
	url: string,
}

export type PreparedUploadFile = {
	temporaryMessageId: string,
	temporaryFileId: string,
	deviceFile: DeviceFile,
	diskFolderId: DiskFolderId,
	dialogId: DialogId,
}

export type DeviceFile = {
	height: number,
	id: string,
	name: string,
	previewHeight: number,
	previewUrl: string,
	previewWidth: number,
	type: string,
	url: string,
	width: number,
}

export type FilesIdsCollection = Array<Record<FileId, TemporallyFileId>>

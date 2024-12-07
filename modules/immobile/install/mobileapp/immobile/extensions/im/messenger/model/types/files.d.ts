import {MessengerModel, PayloadData} from "./base";
import {MessageId, ReactionsModelState} from "./messages/reactions";

export enum FileType
{
	image = 'image',
	video = 'video',
	audio = 'audio',
	file = 'file',
}
export enum FileStatus
{
	upload = 'upload',
	wait = 'wait',
	done = 'done',
	error = 'error',
}

export type FilesModelState = {
	id: number,
	chatId: number,
	name: string,
	templateId: string,
	date: Date,
	type: FileType,
	extension: string,
	icon: string,
	size: number,
	image: boolean | object,
	status: FileStatus,
	progress: number,
	authorId: number,
	authorName: string,
	urlPreview: string,
	urlLocalPreview: string,
	urlShow: string,
	urlDownload: string,
	init: boolean,
	viewerAttrs: Object,
	localUrl?: string,

	uploadData?: {
		byteSent?: 0,
		byteTotal?: 0,
	},
};

export type FilesMessengerModel = MessengerModel<FilesModelCollection>;

declare type FilesModelCollection = {
	collection: Record<number, FilesModelState>
}

export type FilesModelActions =
	'filesModel/setState'
	| 'filesModel/set'
	| 'filesModel/setFromLocalDatabase'
	| 'filesModel/updateWithId'
	| 'filesModel/delete'
	| 'filesModel/deleteByChatId'

export type FilesModelMutation =
	'filesModel/setState'
	| 'filesModel/add'
	| 'filesModel/update'
	| 'filesModel/updateWithId'
	| 'filesModel/delete'
	| 'filesModel/deleteByChatId'


export type FilesSetStateActions = 'setState';
export interface FilesSetStateData extends PayloadData
{
	collection: Record<number, FilesModelState>;
}


export type FilesAddActions = 'set';
export interface FilesAddData extends PayloadData
{
	fileList: Array<FilesModelState>;
}


export type FilesUpdateActions = 'set';
export interface FilesUpdateData extends PayloadData
{
	fileList: Array< Partial<FilesModelState> >;
}


export type FilesUpdateWithIdActions = 'updateWithId';
export interface FilesUpdateWithIdData extends PayloadData
{
	id: number;
	fields: Partial<FilesModelState>
}


export type FilesDeleteActions = 'delete';
export interface FilesDeleteData extends PayloadData
{
	id: number;
}

export type FilesDeleteByChatIdActions = 'deleteByChatId';
export interface FilesDeleteByChatIdData extends PayloadData
{
	chatId: number;
}

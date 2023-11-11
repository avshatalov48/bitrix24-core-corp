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
	dialogId: string,
	name: string,
	templateId: number,
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
	urlShow: string,
	urlDownload: string,
	init: boolean,
	viewerAttrs: Object,
	localUrl?: string,

	uploadData: {
		byteSent?: 0,
		byteTotal?: 0,
	},
};

export type FilesModelActions =
	'filesModel/setState'
	| 'filesModel/set'
	| 'filesModel/updateWithId'
	| 'filesModel/delete'

export type FilesModelMutation =
	'filesModel/setState'
	| 'filesModel/add'
	| 'filesModel/update'
	| 'filesModel/updateWithId'
	| 'filesModel/delete'
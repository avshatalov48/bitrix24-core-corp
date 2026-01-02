export type FileId = string | number;

export type FileDto = {
	id: number,
	serverId: number,
	type: string,
	name: string,
	size: number,
	width: number,
	height: number,
	isImage: boolean,
	isVideo: boolean,
	treatImageAsFile: boolean,
	downloadUrl: string,
	serverPreviewUrl: number,
	serverPreviewWidth: number,
	serverPreviewHeight: number,
	customData: {
		objectId: number,
	},
	viewerAttrs: {
		'data-viewer': boolean,
		'data-title': string,
		'data-viewer-type-class': string,
		'data-viewer-type': string,
		'data-viewer-group-by': string,
		'data-object-id': string,
	},
};

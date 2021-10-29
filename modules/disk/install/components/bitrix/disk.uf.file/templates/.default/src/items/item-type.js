type ItemSavedType = {
	ID: string,
	FILE_ID: string,
	IS_LOCKED: boolean
	IS_MARK_DELETED: boolean
	EDITABLE: boolean
	FROM_EXTERNAL_SYSTEM: boolean

	CAN_RESTORE: boolean,
	CAN_UPDATE: boolean,
	CAN_RENAME: boolean,
	CAN_MOVE: boolean,

	COPY_TO_ME_URL: ?string,
	DELETE_URL: ?string,
	DOWNLOAD_URL: ?string,
	EDIT_URL: ?string,
	VIEW_URL: ?string,
	PREVIEW_URL: ?string,
	BIG_REVIEW_URL: ?string,

	EXTENSION: string,
	NAME: string,
	SIZE: string,
	SIZE_BYTES: string,
	STORAGE: string,
	TYPE_FILE: ?string,
};

type ItemUploadedType = {
	attachId: string,
	canChangeName: boolean,
	ext:  string,
	fileId: number,
	label: string,
	name: string,
	originalId: number,
	previewUrl: ?string,
	size: string,
	sizeInt: string,
	storage: string,
	fileType: string,
};

type ItemSelectedType = {
	id: string, //"n616"
	ext: string, //"jpg"
	modifyBy: string, //"Josefine Johnson"
	modifyDate: string, //"26.06.2021"
	modifyDateInt: number, //1624715431
	name: string, //"gettyimages-977164032-612x612 (1).jpg"
	previewUrl: string, //"/disk/showFile/616/?&ncc=1&ts=1624715431&filename=gettyimages-977164032-612x612+%281%29.jpg"
	size: string, //"49.5 Kb"
	sizeInt: number, //50689
	type: string, //"file"
};

type ItemSelectedCloudType = {
	id: string, //"L9CT0L7RgNGLLmpwZw=="
	ext: string, //"jpg"
	link: string, //"/bitrix/tools/disk/uf.php?folderId=L9CT0L7RgNGLLmpwZw%3D%3D&service=yandexdisk&action=loadItems&ncc=1"
	modifyBy: string, //""
	modifyDate: string, //"30.11.2020"
	modifyDateInt: number, //1606724454
	name: string, //"Mountains.jpg"
	provider: ?string, //"yandexdisk"
	service: ?string, //"yandexdisk"
	size: string, //"1.68 MB"
	sizeInt: number, //1762478
	type: "file"
}

type ItemSavedCloudType = {
	id: string, //"1462"
	ufId: string, //"n1462"
	name: string, //"Mountains.jpg"
	previewUrl: string, //"/disk/showFile/1462/?&ncc=1&width=69&height=69&signature=c40bc5943bdecfd3ae9a080294c540c16726d90a84a7f6fdba28ff257423015b&ts=1631176938&filename=%D0%9C%D0%B8%D1%88%D0%BA%D0%B8+%282%29.jpg"
	size: string, //"1555830"
	sizeFormatted: string, //"1.48 MB"
	folder: string, // "Yandex files"
	storage: string, // "Yandex files"
}



export {ItemSavedType, ItemUploadedType, ItemSelectedType, ItemSelectedCloudType, ItemSavedCloudType};

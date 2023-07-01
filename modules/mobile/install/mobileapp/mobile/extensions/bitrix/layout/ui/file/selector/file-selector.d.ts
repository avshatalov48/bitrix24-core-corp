type FileSelectorProps = {
	layout: object,
	title?: string | Function,
	files: FileSelectorFile[],
	required: boolean,
	focused: boolean,
	controller: FileSelectorBackendControllerOptions,
	onSave: Function,
};

type FileSelectorFile = {
	id: number,
	name: string,
	type: string,
	url: string,
	token?: string,
	height?: number,
	width?: number,
	previewUrl?: string,
	previewHeight?: number,
	previewWidth?: number,
};

type FileSelectorBackendControllerOptions = {
	endpoint: string,
	options: object,
};
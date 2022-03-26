import {OnlyOfficeItem} from "disk.viewer.onlyoffice-item";

export type CreateDocumentOptions = {
	call: {
		id: number,
	},
	dialog: {
		id: string,
	},
	delegate: {
		setMaxWidth: Function,
		onDocumentCreated: Function,
	}
};

export type CreateIframeDocumentOptions = {
	typeFile: 'docx' | 'pptx' | 'xlsx',
};

export type IframeDocumentOptions = {
	viewerItem: OnlyOfficeItem,
};

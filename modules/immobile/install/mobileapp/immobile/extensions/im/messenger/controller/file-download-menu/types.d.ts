import { DialogId } from '../../types/common';

type FileDownloadMenuProps = {
	fileId: number,
	dialogId: DialogId,
	ref?: Object,
}

type ServerDownloadMenuProps = {
	onDownload: () => void,
}

export { ServerDownloadMenuProps, FileDownloadMenuProps };

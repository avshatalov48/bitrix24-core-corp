import { UsersModelState } from '../../../model/types/users';
import { FilesModelState } from '../../../model/types/files';
import { SidebarFile } from '../../../model/types/sidebar/files';
import { DialogId } from '../../../types/common';
import { SidebarLink } from '../../../model/types/sidebar/links';

type SidebarFilesUpdateModel = {
	list: SidebarFile[],
	users: UsersModelState[],
	files: FilesModelState[],
}

type FileContextMenuProps = {
	fileId: number,
	dialogId: DialogId,
	messageId: number,
	ref: any,
}

type LinkContextMenuProps = SidebarLink & {
	dialogId: DialogId,
	ref: any,
}

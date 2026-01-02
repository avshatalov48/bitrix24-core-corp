import { MessageId } from '../../../../model/messages/src/reactions/types';
import { DialogId } from '../../../../types/common';
import { IServiceLocator } from '../../../../lib/di/service-locator/types';
import { DialogLocatorServices } from '../../types/dialog';
import { FileType } from '../../../../model/files/src/types';
import { DialoguesModelState } from '../../../../model/dialogues/src/types';
import {AttachConfig} from "../../../../model/messages/src/types/attach";

type galleryItemParams = {
	messageId: MessageId
}

type mediaMenuProps = {
	dialogId: DialogId,
	messageId: MessageId,
	forceDelete: boolean,
	onBeforeAction: () => Promise<void>,
	dialogLocator: IServiceLocator<DialogLocatorServices>
}

type menuItemParams = {
	id: string
}

type mediaListItem = {
	id: string | number,
	type: "image" | "video",
	urlShow: string,
	urlPreview: string,
	authorName: string,
	date: string,
	messageId: MessageId,
	description: string,
};

type mediaHandlerProps = {
	messageId: MessageId,
	mediaId: String,
	mediaType: FileType,
	direction: 'right' | 'left',
	localUrl: string,
	dialogLocator: IServiceLocator<DialogLocatorServices>,
	dialogModel: DialoguesModelState,
	forceDelete: string,
	onBeforeAction: () => Promise<void>,
	mediaList: Array<mediaListItem>,
}

export { mediaHandlerProps, galleryItemParams, mediaMenuProps, menuItemParams, mediaListItem };

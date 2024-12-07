import {DialogId} from "../../../types/common";
import {DialogPermissions, DialogType, LastMessageViews,} from "../../../model/types/dialogues";

declare type DialogStoredData = {
	dialogId: DialogId,
	chatId: number,
	type: DialogType,
	name: string,
	description: string,
	avatar: string,
	color: string,
	extranet: boolean,
	counter: number,
	userCounter: number,
	lastReadId: number,
	markedId: number,
	lastMessageId: number,
	lastMessageViews: LastMessageViews,
	managerList: Array<any>,
	readList: Array<any>,
	muteList: Array<any>,
	owner: number,
	entityType: string,
	entityId: string,
	dateCreate: Date | null,
	public: {
		code: string,
		link: string
	},
	role: string,
	permissions: DialogPermissions,
	aiProvider: string,
};
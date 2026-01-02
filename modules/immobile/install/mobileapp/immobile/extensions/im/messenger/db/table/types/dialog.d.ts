import {DialogId} from '../../../types/common';
import {DialogPermissions, DialogType, LastMessageViews,} from '../../../model/dialogues/src/types';

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
	entityLink: {
		type?: string,
		url?: string,
	},
	dateCreate: Date | null,
	public: {
		code: string,
		link: string
	},
	role: string,
	permissions: DialogPermissions,
	aiProvider: string,
	textFieldEnabled: boolean,
	backgroundId: string,
	containsCollaber: boolean,
};

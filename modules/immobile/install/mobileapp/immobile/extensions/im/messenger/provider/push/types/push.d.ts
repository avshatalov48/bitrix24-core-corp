import {RawChat, RawFile, RawUser} from "../../service/src/types/sync-list-result";
import {DialogId} from "../../../types/common";

declare type MessengerPushEvent = {
	command: 'message' | 'messageChat',
	extra: {
		server_time: string,
		server_time_unix: number,
	},
	params: {
		chat: Record<number, PushRawChat>,
		chatId: number,
		cmd: 'message' | 'messageChat',
		counter: number,
		dialogId: DialogId,
		files: Record<number, RawFile>,
		message: PushRawMessage,
		notify: boolean,
		userInChat: Record<number, Array<number>>,
		users: Record<number, RawUser>
	}
}

declare type PushRawChat = {
	avatar: string,
	call: string,
	call_number: string,
	color: string,
	date_create: string,
	entity_data_1: string,
	entity_data_2: string,
	entity_data_3: string,
	entity_id: string,
	entity_type: string,
	extranet: boolean,
	id: number,
	manage_list: Array<any>,
	mute_list: Array<any> | {},
	name: string,
	owner: number,
	type: string,
}

declare type PushRawMessage = {
	chatId: number,
	date: string,
	id: number,
	params: [] | object,
	prevId: number,
	push: boolean,
	recipientId: string,
	senderId: number,
	system: 'N' | 'Y',
	text: string,
	textOriginal: string,
}
import {DialogId} from "../../../types/common";

declare type MessagePushStoredData = {
	id: number,
	chatId: number,
	authorId: number,
	date: Date,
	text: string,
	params: object,
	previousId: number,
	dialogId: DialogId,
	textOriginal: string,
	system: boolean,
	push: boolean,
}
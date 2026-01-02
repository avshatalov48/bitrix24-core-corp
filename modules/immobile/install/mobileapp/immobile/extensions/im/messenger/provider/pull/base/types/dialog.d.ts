import {RawChat, RawUser} from './common';
import {DialogId} from "../../../../types/common";
import {InputActionNotifyType} from "../../../../model/dialogues/src/types";

export type ChatUpdatePullHandlerParams = {
	chat: RawChat
}

export type ChatUpdateFieldsPullHandlerParams = Partial<RawChat> & {dialogId: DialogId, chatId: number}

export type ChatMuteNotifyPullHandlerParams = {
	chatId: number,
	dialogId: string,
	muted: boolean,
	mute: boolean,
	counter: number,
	lines: boolean,
	unread: boolean,
	counterType: string
};


export type ChatRenamePullHandlerParams = {
	chatId: number,
	name: string
};

export type GeneralChatIdPullHandlerParams = {
	id: number,
};

export type ChatUserAddPullHandlerParams = {
	chatId: number,
	dialogId: string,
	chatTitle: string,
	chatOwner: number,
	chatExtranet: boolean,
	containsCollaber: boolean,
	users: {[userId: string]: RawUser},
	newUsers: number[],
	userCount: number
};

export type ChatUserLeavePullHandlerParams = {
	chatId: number,
	chatTitle: string,
	dialogId: string,
	message: string,
	userCount: number,
	userId: number,
	chatExtranet: boolean,
	containsCollaber: boolean,
};

export type ChatAvatarPullHandlerParams = {
	chatId: number,
	avatar: string
};

export type ChatChangeColorPullHandlerParams = {
	chatId: number,
	color: string
};

export type CommentSubscribePullHandlerParams = {
	dialogId: string,
	messageId: number,
	subscribe: boolean
};

export type ChatManagersPullHandlerParams = {
	chatId: number,
	dialogId: string,
	list: number[]
};

export type ChatDeletePullHandlerParams = {
	dialogId: string,
	chatId: number,
	userId: string,
	type: string,
	parentChatId: number,
};

export type ChatUnreadPullHandlerParams = {
	chatId: number,
	dialogId: string,
	active: boolean,
	muted: boolean,
	counter: number,
	markedId: number | "0",
	lines: boolean,
	counterType: string
};


export type InputActionNotifyPullHandlerParams = {
	dialogId: string,
	userId: number,
	userName: string,
	type: InputActionNotifyType,
	duration?: string | null,
};

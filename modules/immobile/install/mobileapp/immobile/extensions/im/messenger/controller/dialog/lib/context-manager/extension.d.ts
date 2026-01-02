import { DialogId } from '../../../../types/common';

declare type GoToMessageContextEvent = {
	dialogId: DialogId,
	messageId: string | number,
	context: string,
	parentMessageId?: string,
	withMessageHighlight?: boolean,
	targetMessagePosition?: string,
	showPlanLimitWidget?: boolean,
}

declare type GoToLastReadMessageContextEvent = {
	dialogId: DialogId,
}

declare type GoToBottomMessageContextEvent = {
	dialogId: DialogId,
}

declare type GoToPostMessageContextEvent = {
	postMessageId: number,
	withMessageHighlight?: boolean,
}

declare type GoToMessageContextByCommentsChatIdEvent = {
	dialogId: DialogId,
	commentChatId: number,
	withMessageHighlight?: boolean,
}

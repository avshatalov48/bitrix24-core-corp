declare type GoToMessageContextEvent = {
	dialogId: string | number,
	messageId: string | number,
	context: string,
	parentMessageId?: string,
	withMessageHighlight?: boolean,
	targetMessagePosition?: string,
	showNotificationIfUnsupported?: boolean,
	showPlanLimitWidget?: boolean,
}

declare type GoToLastReadMessageContextEvent = {
	dialogId: string | number,
}

declare type GoToBottomMessageContextEvent = {
	dialogId: string | number,
}

declare type GoToPostMessageContextEvent = {
	postMessageId: number,
	withMessageHighlight?: boolean,
}

declare type GoToMessageContextByCommentsChatIdEvent = {
	commentChatId: number,
	withMessageHighlight?: boolean,
}

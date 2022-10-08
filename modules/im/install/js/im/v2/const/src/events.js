/**
 * Bitrix Messenger
 * Event names constants
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2020 Bitrix
 */

export const EventType = Object.freeze({
	dialog:
	{
		open: 'IM.Dialog:open',
		call: 'IM.Dialog:call',
		openHistory: 'IM.Dialog:openHistory',
		clearHistory: 'IM.Dialog:clearHistory',
		hide: 'IM.Dialog:hide',
		leave: 'IM.Dialog:leave',
		newMessage: 'EventType.dialog.newMessage',

		scrollOnStart: 'IM.Dialog:scrollOnStart',
		scrollToBottom: 'IM.Dialog:scrollToBottom',
		readVisibleMessages: 'IM.Dialog.readVisibleMessages',
		requestUnread: 'IM.Dialog.requestUnread',

		readMessage: 'IM.Dialog:readMessage',
		quoteMessage: 'IM.Dialog:quoteMessage',
		clickOnCommand: 'IM.Dialog:clickOnCommand',
		clickOnMention: 'IM.Dialog:clickOnMention',
		clickOnUserName: 'IM.Dialog:clickOnUserName',
		clickOnMessageMenu: 'IM.Dialog:clickOnMessageMenu',
		clickOnMessageRetry: 'IM.Dialog:clickOnMessageRetry',
		clickOnUploadCancel: 'IM.Dialog:clickOnUploadCancel',
		clickOnReadList: 'IM.Dialog:clickOnReadList',
		setMessageReaction: 'IM.Dialog:setMessageReaction',
		openMessageReactionList: 'IM.Dialog:openMessageReactionList',
		clickOnKeyboardButton: 'IM.Dialog:clickOnKeyboardButton',
		clickOnChatTeaser: 'IM.Dialog:clickOnChatTeaser',
		clickOnDialog: 'IM.Dialog:clickOnDialog',
		quotePanelClose: 'IM.Dialog:quotePanelClose',
		beforeMobileKeyboard: 'IM.Dialog:beforeMobileKeyboard',

		messagesSet: 'IM.Dialog:messagesSet',
		settingsChange: 'IM.Dialog:settingsChange',
		closePopup: 'IM.Dialog:closePopup',

		errors: {
			accessDenied: 'IM.Dialog.errors:accessDenied',
		}
	},
	textarea:
	{
		focus: 'IM.Textarea:focus',
		setFocus: 'IM.Textarea:setFocus',
		blur: 'IM.Textarea:blur',
		setBlur: 'IM.Textarea:setBlur',
		keyUp: 'IM.Textarea:keyUp',
		edit: 'IM.Textarea:edit',
		insertText: 'IM.Textarea:insertText',
		sendMessage: 'IM.Textarea:sendMessage',
		fileSelected: 'IM.Textarea:fileSelected',
		startWriting: 'IM.Textarea:startWriting',
		stopWriting: 'IM.Textarea:stopWriting',
		appButtonClick: 'IM.Textarea:appButtonClick'
	},
	uploader:
	{
		addMessageWithFile: 'IM.Uploader:addMessageWithFile'
	},
	conference:
	{
		setPasswordFocus: 'IM.Conference:setPasswordFocus',
		hideSmiles: 'IM.Conference:hideSmiles',
		requestPermissions: 'IM.Conference:requestPermissions',
		waitForStart: 'IM.Conference:waitForStart',
		userRenameFocus: 'IM.Conference:userRenameFocus',
		userRenameBlur: 'IM.Conference:userRenameBlur',
	},
	notification:
	{
		updateState: 'IM.Notifications:restoreConnection',
	},
	mobile:
	{
		textarea: {
			setText: 'IM.Mobile.Textarea:setText',
			setFocus: 'IM.Mobile.Textarea:setFocus',
		},
		openUserList: 'IM.Mobile:openUserList'
	},
	search:
	{
		selectItem: 'IM.Search:selectItem',
		openContextMenu: 'IM.Search:openContextMenu',
		openNetworkItem: 'IM.Search:openNetworkItem',
	},
	recent:
	{
		openSearch: 'IM.Recent:openSearch',
		updateSearch: 'IM.Recent:updateSearch',
		closeSearch: 'IM.Recent:closeSearch',
		requestUser: 'IM.Recent:requestUser',
		// compatibility with old chat
		setCounter: 'IM.Recent:setCounter',
		setMessage: 'IM.Recent:setMessage',
		hideChat: 'IM.Recent:hideChat',
		leaveChat: 'IM.Recent:leaveChat',
		updateState: 'IM.Recent:updateState',
		clearLike: 'IM.Recent:clearLike',
		setDraftMessage: 'IM.Recent:setDraftMessage'
	}
});
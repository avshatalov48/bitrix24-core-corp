/**
 * @module im/messenger/const/event-type
 */
jn.define('im/messenger/const/event-type', (require, exports, module) => {

	const EventType = Object.freeze({
		/** Application events */
		app: {
			activeBefore: 'onAppActiveBefore',
			paused: 'onAppPaused',
			active: 'onAppActive',
			failRestoreConnection: 'failRestoreConnection',
		},
		view: {
			close: 'onViewRemoved',
		},
		/** Messenger component events */
		messenger: {
			openDialog: 'ImMobile.Messenger.Dialog:open',
			getOpenDialogParams: 'ImMobile.Messenger.Dialog:getOpenParams',
			openDialogParams: 'ImMobile.Messenger.Dialog:openParams',
			openLine: 'ImMobile.Messenger.Openlines:open',
			getOpenLineParams: 'ImMobile.Messenger.Openlines:getOpenParams',
			openLineParams: 'ImMobile.Messenger.Openlines:openParams',
			openNotifications: 'ImMobile.Messenger.Notifications:open',
			showSearch: 'ImMobile.Messenger.Search:open',
			createChat: 'ImMobile.Messenger.Chat:create',
			refresh: 'ImMobile.Messenger:refresh',
			afterRefreshSuccess: 'ImMobile.Messenger:afterRefreshSuccess',
			renderRecent: 'ImMobile.Messenger:renderRecent',
			closeDialog: 'ImMobile.Messenger:closeDialog',
		},
		/** Extension events */
		recent: {
			itemSelected: 'onItemSelected',
			itemAction: 'onItemAction',
			searchShow: 'onSearchShow',
			userTypeText: 'onUserTypeText',
			scopeSelected: 'onScopeSelected',
			searchItemSelected: 'onSearchItemSelected',
			scroll: 'onScroll',
			refresh: 'onRefresh',
			itemWillDisplay: 'itemWillDisplay',
			sectionButtonClick: 'sectionButtonClick',
			searchSectionButtonClick: 'searchSectionButtonClick',
			createChat: 'custom:createChat',
			readAll: 'custom:readAll',
			loadNextPage: 'custom:loadNextPage',
		},
		dialog: {
			submit: 'submit',
			attachTap: 'attachTap',
			onTopReached: 'onTopReached',
			onBottomReached: 'onBottomReached',
			like: 'like',
			whoLikes: 'whoLikes',
			resend: 'resend',
			reply: 'reply',
			quoteTap: 'quoteTap',
			readyToReply: 'readyToReply',
			cancelReply: 'cancelInputQuote',
			viewableMessagesChanged: 'viewableMessagesChanged',
			scrollToNewMessages: 'scrollToNewMessages',
			playAudioButtonTap: 'playTap',
			playbackCompleted: 'playbackCompleted',
			scrollBegin: 'onScrollBegin',
			scrollEnd: 'onScrollEnd',
			messageTap: 'messageTap',
			messageAvatarTap: 'avatarTap',
			messageDoubleTap: 'messageDoubleTap',
			messageLongTap: 'messageLongTap',
			messageQuoteTap: 'messageQuoteTap',
			messageMenuReactionTap: 'messageMenuReactionTap',
			messageMenuActionTap: 'messageMenuActionTap',
			quoteRemoveAnimationEnd: 'onQuoteRemoveAnimationEnd',
			urlTap: 'urlTap',
			mentionTap: 'mentionTap',
			loadTopPage: 'custom:loadTopPage',
			loadBottomPage: 'custom:loadBottomPage',
			messageRead: 'custom:messageRead',
		},
		/** Integration (other components events) */
		chatDialog: {
			initComplete: 'chatdialog::init::complete',
			counterChange: 'chatdialog::counter::change',
			taskStatusSuccess: 'chatbackground::task::status::success',
			accessError: 'chatdialog::access::error',
		},
		call: {
			active: 'CallEvents::active',
			inactive: 'CallEvents::inactive',
			join: 'CallEvents::joinCall',
		},
		notification: {
			open: 'onNotificationsOpen',
			reload: 'ImMobile.Messenger.Notification:reload',
		},
	});

	module.exports = { EventType };
});

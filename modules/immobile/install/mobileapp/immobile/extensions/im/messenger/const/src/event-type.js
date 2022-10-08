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
			openLine: 'ImMobile.Messenger.Openlines:open',
			getOpenLineParams: 'ImMobile.Messenger.Openlines:getOpenParams',
			openLineParams: 'ImMobile.Messenger.Openlines:openParams',
			joinCall: 'ImMobile.Messenger.Call:join',
			openNotifications: 'ImMobile.Messenger.Notifications:open',
			showSearch: 'ImMobile.Messenger.Search:open',
			createChat: 'ImMobile.Messenger.Chat:create',
			refresh: 'ImMobile.Messenger:refresh',
			afterRefreshSuccess: 'ImMobile.Messenger:afterRefreshSuccess',
			renderRecent: 'ImMobile.Messenger:renderRecent',
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
			loadMore: 'loadMore',
			refresh: 'refresh',
			like: 'like',
			whoLikes: 'whoLikes',
			resend: 'resend',
			reply: 'reply',
			cancelReply: 'cancelInputQuote',
			viewableMessagesChanged: 'viewableMessagesChanged',
			scrollToNewMessages: 'scrollToNewMessages',
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

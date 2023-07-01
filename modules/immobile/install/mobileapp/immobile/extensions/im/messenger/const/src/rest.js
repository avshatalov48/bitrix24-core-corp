/**
 * @module im/messenger/const/rest
 */
jn.define('im/messenger/const/rest', (require, exports, module) => {

	const RestMethod = Object.freeze({
		imMessageAdd: 'im.message.add',
		imMessageUpdate: 'im.message.update',
		imMessageDelete: 'im.message.delete',
		imMessageLike: 'im.message.like',
		imMessageCommand: 'im.message.command',
		imMessageShare: 'im.message.share',

		imChatAdd: 'im.chat.add',
		imChatGet: 'im.chat.get',
		imChatLeave: 'im.chat.leave',
		imChatMute: 'im.chat.mute',
		imChatUpdateTitle: 'im.chat.updateTitle',
		imChatParentJoin: 'im.chat.parent.join',
		imChatFileCollectionGet: 'im.chat.file.collection.get',
		imChatFileGet: 'im.chat.file.get',
		imChatUrlGet: 'im.chat.url.get',
		imChatUrlDelete: 'im.chat.url.delete',
		imChatTaskGet: 'im.chat.task.get',
		imChatTaskDelete: 'im.chat.task.delete',
		imChatCalendarGet: 'im.chat.calendar.get',
		imChatFavoriteDelete: 'im.chat.favorite.delete',
		imChatFavoriteGet: 'im.chat.favorite.get',
		imChatFavoriteCounterGet: 'im.chat.favorite.counter.get',
		imChatUrlCounterGet: 'im.chat.url.counter.get',
		imChatPinGet: 'im.chat.pin.get',
		imChatPinAdd: 'im.chat.pin.add',
		imChatPinDelete: 'im.chat.pin.delete',
		imChatTaskPrepare: 'im.chat.task.prepare',
		imChatCalendarPrepare: 'im.chat.calendar.prepare',
		imChatCalendarAdd: 'im.chat.calendar.add',
		imChatCalendarDelete: 'im.chat.calendar.delete',
		imChatUserDelete: 'im.chat.user.delete',

		imV2ChatRead: 'im.v2.Chat.read',
		imV2ChatReadAll: 'im.v2.Chat.readAll',
		imV2ChatUnread: 'im.v2.Chat.unread',
		imV2ChatMessageList: 'im.v2.Chat.Message.list',
		imV2ChatMessageTail: 'im.v2.Chat.Message.tail',
		imV2ChatMessageRead: 'im.v2.Chat.Message.read',
		imV2ChatMessageMark: 'im.v2.Chat.Message.mark',

		imDialogGet: 'im.dialog.get',
		imDialogMessagesGet: 'im.dialog.messages.get',
		imDialogRead: 'im.dialog.read',
		imDialogUnread: 'im.dialog.unread',
		imDialogWriting: 'im.dialog.writing',
		imDialogRestrictionsGet: 'im.dialog.restrictions.get',
		imDialogReadAll: 'im.dialog.read.all',
		imDialogContextGet: 'im.dialog.context.get',
		imDialogUsersList: 'im.dialog.users.list',

		imUserGet: 'im.user.get',
		imUserListGet: 'im.user.list.get',
		imUserStatusSet: 'im.user.status.set',

		imDiskFolderGet: 'im.disk.folder.get',
		imDiskFolderListGet: 'im.disk.folder.list.get',
		imDiskFileUpload: 'disk.folder.uploadfile',
		imDiskFileCommit: 'im.disk.file.commit',
		imDiskFileDelete: 'im.disk.file.delete',
		imDiskFileSave: 'im.disk.file.save',

		imRecentGet: 'im.recent.get',
		imRecentList: 'im.recent.list',
		imRecentPin: 'im.recent.pin',
		imRecentHide: 'im.recent.hide',
		imRecentUnread: 'im.recent.unread',

		imCallGetCallLimits: 'im.call.getCallLimits',

		imNotifyGet: 'im.notify.get',
		imNotifySchemaGet: 'im.notify.schema.get',

		imCountersGet: 'im.counters.get',

		imDesktopStatusGet: 'im.desktop.status.get',

		imPromotionGet: 'im.promotion.get',
		imPromotionRead: 'im.promotion.read',

		imRevisionGet: 'im.revision.get',

		imDepartmentColleaguesGet: 'im.department.colleagues.get',

		mobileBrowserConstGet: 'mobile.browser.const.get',

		userCounters: 'user.counters',
		serverTime: 'server.time',

		openlinesDialogGet: 'imopenlines.dialog.get',
	});

	module.exports = {
		RestMethod,
	};
});

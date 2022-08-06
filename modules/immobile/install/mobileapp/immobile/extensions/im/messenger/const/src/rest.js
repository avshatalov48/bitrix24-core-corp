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

		imChatGet: 'im.chat.get',
		imChatLeave: 'im.chat.leave',
		imChatMute: 'im.chat.mute',
		imChatParentJoin: 'im.chat.parent.join',

		imDialogGet: 'im.dialog.get',
		imDialogMessagesGet: 'im.dialog.messages.get',
		imDialogRead: 'im.dialog.read',
		imDialogUnread: 'im.dialog.unread',
		imDialogWriting: 'im.dialog.writing',

		imUserGet: 'im.user.get',
		imUserListGet: 'im.user.list.get',

		imDiskFolderGet: 'im.disk.folder.get',
		imDiskFileUpload: 'disk.folder.uploadfile',
		imDiskFileCommit: 'im.disk.file.commit',

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
	});

	module.exports = {
		RestMethod,
	};
});

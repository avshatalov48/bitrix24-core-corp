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
		imChatUserAdd: 'im.chat.user.add',

		imV2RecentChannelTail: 'im.v2.Recent.Channel.tail',
		imV2RecentCollabTail: 'im.v2.Recent.Collab.tail',

		imV2ChatGet: 'im.v2.Chat.get',
		imV2ChatExtendPullWatch: 'im.v2.Chat.extendPullWatch',

		imV2ChatDelete: 'im.v2.Chat.delete',
		imV2ChatAdd: 'im.v2.Chat.add',
		imV2ChatRead: 'im.v2.Chat.read',
		imV2ChatReadAll: 'im.v2.Chat.readAll',
		imV2ChatUnread: 'im.v2.Chat.unread',
		imV2ChatLoad: 'im.v2.Chat.load',
		imV2ChatShallowLoad: 'im.v2.Chat.shallowLoad',
		imV2ChatLoadInContext: 'im.v2.Chat.loadInContext',
		imV2ChatMessageList: 'im.v2.Chat.Message.list',
		imV2ChatMessageTail: 'im.v2.Chat.Message.tail',
		imV2ChatMessageGetContext: 'im.v2.Chat.Message.getContext',
		imV2ChatMessageRead: 'im.v2.Chat.Message.read',
		imV2ChatMessageMark: 'im.v2.Chat.Message.mark',
		imV2ChatMessageSend: 'im.v2.Chat.Message.send',
		imV2ChatMessageUpdate: 'im.v2.Chat.Message.update',
		imV2ChatMessageDelete: 'im.v2.Chat.Message.delete',
		imV2ChatMessageTailViewers: 'im.v2.Chat.Message.tailViewers',
		imV2ChatMessageDeleteRichUrl: 'im.v2.Chat.Message.deleteRichUrl',
		imV2ChatMessagePin: 'im.v2.Chat.Message.pin',
		imV2ChatMessageUnpin: 'im.v2.Chat.Message.unpin',

		imV2ChatPinTail: 'im.v2.Chat.Pin.tail',
		imV2ChatPinCount: 'im.v2.Chat.Pin.count',

		imV2ChatJoin: 'im.v2.Chat.join',
		imV2ChatAddUsers: 'im.v2.Chat.addUsers',
		imV2ChatDeleteUser: 'im.v2.Chat.deleteUser',
		imV2ChatUserList: 'im.v2.Chat.User.list',
		imV2ChatAddManagers: 'im.v2.Chat.addManagers',
		imV2ChatDeleteManagers: 'im.v2.Chat.deleteManagers',

		imV2ChatUpdate: 'im.v2.Chat.update',
		imV2ChatUpdateAvatar: 'im.v2.Chat.updateAvatar',
		imV2ChatSetTitle: 'im.v2.Chat.setTitle',
		imV2ChatSetManageUI: 'im.v2.Chat.setManageUI',

		imV2ChatMessageReactionAdd: 'im.v2.Chat.Message.Reaction.add',
		imV2ChatMessageReactionDelete: 'im.v2.Chat.Message.Reaction.delete',
		imV2ChatMessageReactionTail: 'im.v2.Chat.Message.Reaction.tail',

		imV2ChatCommentSubscribe: 'im.v2.Chat.Comment.subscribe',
		imV2ChatCommentUnsubscribe: 'im.v2.Chat.Comment.unsubscribe',
		imV2ChatCommentReadAll: 'im.v2.Chat.Comment.readAll',

		imV2ChatCopilotUpdateRole: 'im.v2.Chat.Copilot.updateRole',
		imV2ChatMemberEntitiesList: 'im.v2.Chat.MemberEntities.list',

		imDialogGet: 'im.dialog.get',
		imDialogMessagesGet: 'im.dialog.messages.get',
		imDialogRead: 'im.dialog.read',
		imDialogUnread: 'im.dialog.unread',
		imDialogWriting: 'im.dialog.writing',
		imDialogStartRecordVoice: 'im.v2.Chat.startRecordVoice',
		imDialogRestrictionsGet: 'im.dialog.restrictions.get',
		imDialogReadAll: 'im.dialog.read.all',
		imDialogContextGet: 'im.dialog.context.get',
		imDialogUsersList: 'im.dialog.users.list',

		imUserGet: 'im.user.get',
		imUserListGet: 'im.user.list.get',
		imUserStatusSet: 'im.user.status.set',
		imUserGetDepartment: 'im.v2.User.getDepartment',

		imDiskFolderGet: 'im.disk.folder.get',
		imDiskFolderListGet: 'im.disk.folder.list.get',
		imDiskFileUpload: 'disk.folder.uploadfile',
		imDiskFileCommit: 'im.disk.file.commit',
		imDiskFilePreviewUpload: 'disk.api.file.attachPreview',
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
		imV2CountersGet: 'im.v2.Counter.get',

		imDesktopStatusGet: 'im.desktop.status.get',

		imPromotionGet: 'im.promotion.get',
		imPromotionRead: 'im.promotion.read',

		imV2TariffRestrictionGet: 'im.v2.Tariff.Restriction.get',

		imRevisionGet: 'im.revision.get',

		imDepartmentColleaguesGet: 'im.department.colleagues.get',

		imV2SyncList: 'im.v2.Sync.list',

		mobileBrowserConstGet: 'mobile.browser.const.get',

		userCounters: 'user.counters',
		serverTime: 'server.time',

		openlinesDialogGet: 'imopenlines.dialog.get',

		immobileTabChatLoad: 'immobile.Tab.Chat.load',
		immobileTabCopilotLoad: 'immobile.Tab.Copilot.load',
		immobileTabChannelLoad: 'immobile.Tab.Channel.load',
		immobileTabCollabLoad: 'immobile.Tab.Collab.load',

		socialnetworkCollabMemberAdd: 'socialnetwork.collab.Member.add',
		socialnetworkCollabMemberDelete: 'socialnetwork.collab.Member.delete',
		socialnetworkCollabDelete: 'socialnetwork.collab.Collab.delete',
		socialnetworkCollabMemberLeave: 'socialnetwork.collab.Member.leave',
	});

	module.exports = {
		RestMethod,
	};
});

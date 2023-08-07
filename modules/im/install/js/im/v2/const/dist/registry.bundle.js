/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
this.BX.Messenger.v2 = this.BX.Messenger.v2 || {};
(function (exports) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * Device constants
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2020 Bitrix
	 */

	const DeviceType = Object.freeze({
	  mobile: 'mobile',
	  desktop: 'desktop'
	});
	const DeviceOrientation = Object.freeze({
	  horizontal: 'horizontal',
	  portrait: 'portrait'
	});

	const MutationType = Object.freeze({
	  none: 'none',
	  add: 'delete',
	  update: 'update',
	  delete: 'delete',
	  set: 'set',
	  setAfter: 'after',
	  setBefore: 'before'
	});
	const StorageLimit = Object.freeze({
	  dialogues: 50,
	  messages: 100
	});
	const OpenTarget = Object.freeze({
	  current: 'current',
	  auto: 'auto'
	});
	const BotType = Object.freeze({
	  bot: 'bot',
	  network: 'network',
	  support24: 'support24'
	});

	const RestMethod = Object.freeze({
	  imV2ChatLoad: 'im.v2.Chat.load',
	  imV2ChatGetDialogId: 'im.v2.Chat.getDialogId',
	  imV2ChatShallowLoad: 'im.v2.Chat.shallowLoad',
	  imV2ChatLoadInContext: 'im.v2.Chat.loadInContext',
	  imV2ChatLoadContext: 'im.v2.Chat.loadInContext',
	  imV2ChatAdd: 'im.v2.Chat.add',
	  imV2ChatRead: 'im.v2.Chat.read',
	  imV2ChatReadAll: 'im.v2.Chat.readAll',
	  imV2ChatUnread: 'im.v2.Chat.unread',
	  imV2ChatMessageGetContext: 'im.v2.Chat.Message.getContext',
	  imV2ChatMessageList: 'im.v2.Chat.Message.list',
	  imV2ChatMessageTail: 'im.v2.Chat.Message.tail',
	  imV2ChatMessageRead: 'im.v2.Chat.Message.read',
	  imV2ChatMessageMark: 'im.v2.Chat.Message.mark',
	  imV2ChatMessageDelete: 'im.v2.Chat.Message.delete',
	  imV2ChatMessageReactionAdd: 'im.v2.Chat.Message.Reaction.add',
	  imV2ChatMessageReactionDelete: 'im.v2.Chat.Message.Reaction.delete',
	  imV2ChatMessageReactionTail: 'im.v2.Chat.Message.Reaction.tail',
	  imV2ChatMessagePin: 'im.v2.Chat.Message.pin',
	  imV2ChatMessageUnpin: 'im.v2.Chat.Message.unpin',
	  imV2ChatMessageTailViewers: 'im.v2.Chat.Message.tailViewers',
	  imV2ChatPinTail: 'im.v2.Chat.Pin.tail',
	  imV2SettingsGeneralUpdate: 'im.v2.Settings.General.update',
	  imV2DesktopLogout: 'im.v2.Desktop.logout',
	  imV2BetaEnable: 'im.v2.Beta.enable',
	  imV2BetaDisable: 'im.v2.Beta.disable',
	  imCallBetaCreateRoom: 'im.call.beta.createRoom',
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
	  imChatFavoriteAdd: 'im.chat.favorite.add',
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
	  imUserStatusIdleStart: 'im.user.status.idle.start',
	  imUserStatusIdleEnd: 'im.user.status.idle.end',
	  imDiskFolderGet: 'im.disk.folder.get',
	  imDiskFolderListGet: 'im.disk.folder.list.get',
	  imDiskFileUpload: 'disk.folder.uploadfile',
	  imDiskFilePreviewUpload: 'disk.api.file.attachPreview',
	  imDiskFileCommit: 'im.disk.file.commit',
	  imDiskFileDelete: 'im.disk.file.delete',
	  imDiskFileSave: 'im.disk.file.save',
	  mobileBrowserConstGet: 'mobile.browser.const.get',
	  imRecentGet: 'im.recent.get',
	  imRecentList: 'im.recent.list',
	  imRecentPin: 'im.recent.pin',
	  imRecentUnread: 'im.recent.unread',
	  imRecentHide: 'im.recent.hide',
	  imCallGetCallLimits: 'im.call.getCallLimits',
	  imNotifyGet: 'im.notify.get',
	  imNotifyRead: 'im.notify.read',
	  imNotifySchemaGet: 'im.notify.schema.get',
	  imNotifyHistorySearch: 'im.notify.history.search',
	  imNotifyAnswer: 'im.notify.answer',
	  imCallBackgroundGet: 'im.v2.Call.Background.get',
	  imCallBackgroundCommit: 'im.v2.Call.Background.commit',
	  imCallBackgroundDelete: 'im.v2.Call.Background.delete',
	  imCallMaskGet: 'im.v2.Call.Mask.get',
	  imSmilesGet: 'smile.get',
	  imBotGiphyListPopular: 'imbot.Giphy.listPopular',
	  imBotGiphyList: 'imbot.Giphy.list'
	});
	const RestMethodHandler = Object.freeze({
	  imChatGet: 'im.chat.get',
	  imMessageAdd: 'im.message.add',
	  imDialogRead: 'im.dialog.read',
	  imDialogMessagesGet: 'im.dialog.messages.get',
	  imDialogMessagesGetInit: 'im.dialog.messages.get.init',
	  imDialogMessagesGetUnread: 'im.dialog.messages.get.unread',
	  imDiskFolderGet: 'im.disk.folder.get',
	  imDiskFileUpload: 'disk.folder.uploadfile',
	  imDiskFileCommit: 'im.disk.file.commit',
	  imUserGet: 'im.user.get',
	  imUserListGet: 'im.user.list.get',
	  mobileBrowserConstGet: 'mobile.browser.const.get',
	  imRecentGet: 'im.recent.get',
	  imRecentList: 'im.recent.list',
	  imCallGetCallLimits: 'im.call.getCallLimits',
	  imNotifyGet: 'im.notify.get',
	  imNotifySchemaGet: 'im.notify.schema.get'
	});

	const PullCommand = Object.freeze({
	  messageUpdate: 'messageUpdate',
	  messageDelete: 'messageDelete'
	});
	const PullHandlers = Object.freeze({
	  recent: 'recent',
	  notification: 'notification',
	  sidebar: 'sidebar'
	});

	const EventType = Object.freeze({
	  layout: {
	    onLayoutChange: 'IM.Layout:onLayoutChange',
	    onOpenChat: 'IM.Layout:onOpenChat',
	    onOpenNotifications: 'IM.Layout:onOpenNotifications'
	  },
	  dialog: {
	    open: 'IM.Dialog:open',
	    call: 'IM.Dialog:call',
	    openHistory: 'IM.Dialog:openHistory',
	    clearHistory: 'IM.Dialog:clearHistory',
	    hide: 'IM.Dialog:hide',
	    leave: 'IM.Dialog:leave',
	    newMessage: 'IM.Dialog:newMessage',
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
	    clickOnReadList: 'IM.Dialog:clickOnReadList',
	    setMessageReaction: 'IM.Dialog:setMessageReaction',
	    openMessageReactionList: 'IM.Dialog:openMessageReactionList',
	    clickOnKeyboardButton: 'IM.Dialog:clickOnKeyboardButton',
	    clickOnChatTeaser: 'IM.Dialog:clickOnChatTeaser',
	    clickOnDialog: 'IM.Dialog:clickOnDialog',
	    quotePanelClose: 'IM.Dialog:quotePanelClose',
	    beforeMobileKeyboard: 'IM.Dialog:beforeMobileKeyboard',
	    goToMessageContext: 'IM.Dialog:goToMessageContext',
	    messagesSet: 'IM.Dialog:messagesSet',
	    settingsChange: 'IM.Dialog:settingsChange',
	    closePopup: 'IM.Dialog:closePopup',
	    errors: {
	      accessDenied: 'IM.Dialog.errors:accessDenied'
	    },
	    onDialogInited: 'IM.Dialog:onDialogInited'
	  },
	  textarea: {
	    focus: 'IM.Textarea:focus',
	    setFocus: 'IM.Textarea:setFocus',
	    blur: 'IM.Textarea:blur',
	    setBlur: 'IM.Textarea:setBlur',
	    keyUp: 'IM.Textarea:keyUp',
	    editMessage: 'IM.Textarea:editMessage',
	    insertText: 'IM.Textarea:insertText',
	    insertMention: 'IM.Textarea:insertMention',
	    sendMessage: 'IM.Textarea:sendMessage',
	    fileSelected: 'IM.Textarea:fileSelected',
	    startWriting: 'IM.Textarea:startWriting',
	    stopWriting: 'IM.Textarea:stopWriting',
	    appButtonClick: 'IM.Textarea:appButtonClick'
	  },
	  uploader: {
	    addMessageWithFile: 'IM.Uploader:addMessageWithFile',
	    // todo: delete legacy event?
	    cancel: 'IM.Uploader:cancel'
	  },
	  call: {
	    onFold: 'CallController::onFold'
	  },
	  conference: {
	    setPasswordFocus: 'IM.Conference:setPasswordFocus',
	    hideSmiles: 'IM.Conference:hideSmiles',
	    requestPermissions: 'IM.Conference:requestPermissions',
	    waitForStart: 'IM.Conference:waitForStart',
	    userRenameFocus: 'IM.Conference:userRenameFocus',
	    userRenameBlur: 'IM.Conference:userRenameBlur'
	  },
	  notification: {
	    updateState: 'IM.Notifications:restoreConnection'
	  },
	  mobile: {
	    textarea: {
	      setText: 'IM.Mobile.Textarea:setText',
	      setFocus: 'IM.Mobile.Textarea:setFocus'
	    },
	    openUserList: 'IM.Mobile:openUserList'
	  },
	  search: {
	    close: 'IM.Search:close',
	    keyPressed: 'IM.Search:keyPressed',
	    selectItem: 'IM.Search:selectItem',
	    //deprecated
	    openNetworkItem: 'IM.Search:openNetworkItem',
	    //deprecated
	    openContextMenu: 'IM.Search:openContextMenu'
	  },
	  recent: {
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
	  },
	  sidebar: {
	    open: 'IM.Sidebar:open',
	    close: 'IM.Sidebar:close'
	  },
	  mention: {
	    openChatInfo: 'IM.Mention:openChatInfo'
	  },
	  counter: {
	    onNotificationCounterChange: 'onImUpdateCounterNotify',
	    onChatCounterChange: 'onImUpdateCounterMessage'
	  },
	  desktop: {
	    onInit: 'onDesktopInit',
	    onReload: 'onDesktopReload',
	    onUserAway: 'BXUserAway',
	    onWakeUp: 'BXWakeAction',
	    onBxLink: 'BXProtocolUrl',
	    onExit: 'BXExitApplication'
	  }
	});

	const DialogType = Object.freeze({
	  user: 'user',
	  chat: 'chat',
	  open: 'open',
	  general: 'general',
	  videoconf: 'videoconf',
	  announcement: 'announcement',
	  call: 'call',
	  support24Notifier: 'support24Notifier',
	  support24Question: 'support24Question',
	  crm: 'crm',
	  sonetGroup: 'sonetGroup',
	  calendar: 'calendar',
	  tasks: 'tasks',
	  thread: 'thread',
	  mail: 'mail',
	  lines: 'lines'
	});
	const DialogScrollThreshold = Object.freeze({
	  none: 'none',
	  nearTheBottom: 'nearTheBottom',
	  halfScreenUp: 'halfScreenUp'
	});
	const DialogCrmType = Object.freeze({
	  lead: 'lead',
	  company: 'company',
	  contact: 'contact',
	  deal: 'deal',
	  none: 'none'
	});
	const DialogReferenceClassName = Object.freeze({
	  listBody: 'bx-im-dialog-list',
	  listItem: 'bx-im-dialog-list-item-reference',
	  listItemName: 'bx-im-dialog-list-item-name-reference',
	  listItemBody: 'bx-im-dialog-list-item-content-reference',
	  listUnreadLoader: 'bx-im-dialog-list-unread-loader-reference'
	});
	const DialogTemplateType = Object.freeze({
	  message: 'message',
	  delimiter: 'delimiter',
	  group: 'group',
	  historyLoader: 'historyLoader',
	  unreadLoader: 'unreadLoader',
	  button: 'button',
	  placeholder: 'placeholder'
	});
	const DialogState = Object.freeze({
	  loading: 'loading',
	  empty: 'empty',
	  show: 'show'
	});
	const DialogBlockType = Object.freeze({
	  dateGroup: 'dateGroup',
	  authorGroup: 'authorGroup',
	  newMessages: 'newMessages',
	  markedMessages: 'markedMessages'
	});

	/**
	 * Bitrix Messenger
	 * File constants
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2020 Bitrix
	 */

	const FileStatus = Object.freeze({
	  upload: 'upload',
	  wait: 'wait',
	  progress: 'progress',
	  done: 'done',
	  error: 'error'
	});
	const FileType = Object.freeze({
	  image: 'image',
	  video: 'video',
	  audio: 'audio',
	  file: 'file'
	});
	const FileIconType = Object.freeze({
	  file: 'file',
	  image: 'image',
	  audio: 'audio',
	  video: 'video',
	  code: 'code',
	  call: 'call',
	  attach: 'attach',
	  quote: 'quote'
	});

	const MessageType = Object.freeze({
	  self: 'self',
	  opponent: 'opponent',
	  system: 'system'
	});
	const MessageComponent = Object.freeze({
	  base: 'BaseMessage',
	  chatCreation: 'ChatCreationMessage'
	});
	const MessageExtension = Object.freeze({
	  text: 'TextExtension',
	  deleted: 'DeletedExtension',
	  unsupported: 'UnsupportedExtension',
	  callInvite: 'CallInviteExtension'
	});
	const MessageMentionType = Object.freeze({
	  user: 'USER',
	  chat: 'CHAT',
	  context: 'CONTEXT'
	});
	const OwnMessageStatus = Object.freeze({
	  sending: 'sending',
	  sent: 'sent',
	  viewed: 'viewed'
	});

	/**
	 * Bitrix Messenger
	 * Conference constants
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2020 Bitrix
	 */

	const ConferenceFieldState = Object.freeze({
	  view: 'view',
	  edit: 'edit',
	  create: 'create'
	});
	const ConferenceStateType = Object.freeze({
	  preparation: 'preparation',
	  call: 'call'
	});
	const ConferenceErrorCode = Object.freeze({
	  userLimitReached: 'userLimitReached',
	  detectIntranetUser: 'detectIntranetUser',
	  bitrix24only: 'bitrix24only',
	  kickedFromCall: 'kickedFromCall',
	  unsupportedBrowser: 'unsupportedBrowser',
	  missingMicrophone: 'missingMicrophone',
	  unsafeConnection: 'unsafeConnection',
	  wrongAlias: 'wrongAlias',
	  notStarted: 'notStarted',
	  finished: 'finished',
	  userLeftCall: 'userLeftCall',
	  noSignalFromCamera: 'noSignalFromCamera'
	});
	const ConferenceRightPanelMode = Object.freeze({
	  hidden: 'hidden',
	  chat: 'chat',
	  users: 'users',
	  split: 'split'
	});

	//BX.Call.UserState sync
	const ConferenceUserState = Object.freeze({
	  Idle: 'Idle',
	  Busy: 'Busy',
	  Calling: 'Calling',
	  Unavailable: 'Unavailable',
	  Declined: 'Declined',
	  Ready: 'Ready',
	  Connecting: 'Connecting',
	  Connected: 'Connected',
	  Failed: 'Failed'
	});

	const RecentSection = {
	  general: 'general',
	  pinned: 'pinned'
	};
	const MessageStatus = {
	  received: 'received',
	  delivered: 'delivered',
	  error: 'error'
	};
	const RecentCallStatus = {
	  waiting: 'waiting',
	  joined: 'joined'
	};

	const NotificationTypesCodes = Object.freeze({
	  confirm: 1,
	  simple: 3
	});

	const ChatOption = Object.freeze({
	  avatar: 'avatar',
	  call: 'call',
	  extend: 'extend',
	  leave: 'leave',
	  leaveOwner: 'leaveOwner',
	  mute: 'mute',
	  rename: 'rename',
	  send: 'send',
	  userList: 'userList'
	});

	const Layout = Object.freeze({
	  chat: {
	    name: 'chat',
	    list: 'RecentListContainer',
	    content: 'ChatContent'
	  },
	  createChat: {
	    name: 'createChat',
	    list: 'RecentListContainer',
	    content: 'CreateChatContent'
	  },
	  notification: {
	    name: 'notification',
	    list: 'RecentListContainer',
	    content: 'NotificationContent'
	  },
	  openline: {
	    name: 'openline',
	    list: 'OpenlineListContainer',
	    content: 'OpenlineContent'
	  },
	  conference: {
	    name: 'conference',
	    list: 'RecentListContainer',
	    content: 'ChatContent'
	  },
	  call: {
	    name: 'call',
	    list: 'RecentListContainer',
	    content: 'ChatContent'
	  },
	  market: {
	    name: 'market',
	    list: '',
	    content: 'MarketContent'
	  }
	});

	const SearchEntityIdTypes = {
	  user: 'user',
	  bot: 'im-bot',
	  chat: 'im-chat',
	  chatUser: 'im-chat-user',
	  department: 'department',
	  network: 'imbot-network'
	};

	const UserStatus = {
	  offline: 'offline',
	  online: 'online',
	  mobileOnline: 'mobile-online',
	  away: 'away',
	  idle: 'idle',
	  dnd: 'dnd',
	  break: 'break'
	};
	const UserExternalType = {
	  default: 'default',
	  bot: 'bot',
	  call: 'call'
	};

	const SidebarBlock = Object.freeze({
	  main: 'main',
	  info: 'info',
	  task: 'task',
	  brief: 'brief',
	  file: 'file',
	  fileUnsorted: 'fileUnsorted',
	  sign: 'sign',
	  meeting: 'meeting',
	  market: 'market'
	});
	const SidebarDetailBlock = Object.freeze({
	  main: 'main',
	  link: 'link',
	  favorite: 'favorite',
	  task: 'task',
	  brief: 'brief',
	  media: 'media',
	  audio: 'audio',
	  document: 'document',
	  fileUnsorted: 'fileUnsorted',
	  other: 'other',
	  sign: 'sign',
	  meeting: 'meeting',
	  market: 'market'
	});
	const SidebarFileTypes = Object.freeze({
	  media: 'media',
	  audio: 'audio',
	  document: 'document',
	  other: 'other',
	  brief: 'brief',
	  fileUnsorted: 'fileUnsorted'
	});
	const SidebarFileTabTypes = Object.freeze({
	  [SidebarFileTypes.media]: SidebarFileTypes.media,
	  [SidebarFileTypes.audio]: SidebarFileTypes.audio,
	  [SidebarFileTypes.document]: SidebarFileTypes.document,
	  [SidebarFileTypes.other]: SidebarFileTypes.other
	});

	const Color = Object.freeze({
	  base: '#17a3ea',
	  transparent: 'transparent'
	});

	const AttachType = Object.freeze({
	  Delimiter: 'DELIMITER',
	  File: 'FILE',
	  Grid: 'GRID',
	  Html: 'HTML',
	  Image: 'IMAGE',
	  Link: 'LINK',
	  Message: 'MESSAGE',
	  Rich: 'RICH_LINK',
	  User: 'USER'
	});
	const AttachDescription = Object.freeze({
	  FIRST_MESSAGE: 'FIRST_MESSAGE',
	  SKIP_MESSAGE: 'SKIP_MESSAGE'
	});

	const DesktopFeature = {
	  mask: {
	    id: 'mask',
	    availableFromVersion: 72
	  }
	};
	const DesktopBxLink = {
	  chat: 'chat',
	  call: 'call',
	  notifications: 'notifications',
	  recentSearch: 'recentSearch',
	  timeManager: 'timemanpwt'
	};

	const LocalStorageKey = Object.freeze({
	  draft: 'draft',
	  smileLastUpdateTime: 'smileLastUpdateTime',
	  sidebarOpened: 'sidebarOpened',
	  textareaMarketOpened: 'textareaMarketOpened',
	  textareaHeight: 'textareaHeight'
	});

	const ApplicationName = {
	  core: 'core',
	  quickAccess: 'quickAccess',
	  messenger: 'messenger'
	};

	const PlacementType = Object.freeze({
	  contextMenu: 'IM_CONTEXT_MENU',
	  navigation: 'IM_NAVIGATION',
	  textarea: 'IM_TEXTAREA',
	  sidebar: 'IM_SIDEBAR',
	  smilesSelector: 'IM_SMILES_SELECTOR'
	});

	const PopupType = Object.freeze({
	  userProfile: 'im-user-settings-popup',
	  userStatus: 'im-user-status-popup',
	  backgroundSelect: 'im-background-select-popup',
	  recentContextMenu: 'im-recent-context-menu',
	  recentHeaderMenu: 'im-recent-header-menu',
	  createChatMenu: 'im-create-chat-menu',
	  dialogMessageMenu: 'bx-im-message-context-menu',
	  dialogAvatarMenu: 'bx-im-avatar-context-menu',
	  dialogReactionUsers: 'bx-im-message-reaction-users',
	  dialogReadUsers: 'bx-im-dialog-read-users'
	});

	const Settings = Object.freeze({
	  application: {
	    darkTheme: 'enableDarkTheme',
	    enableSound: 'enableSound'
	  },
	  dialog: {
	    bigSmiles: 'enableBigSmile',
	    background: 'backgroundImageId'
	  },
	  recent: {
	    showBirthday: 'viewBirthday',
	    showInvited: 'viewCommonUsers',
	    showLastMessage: 'viewLastMessage'
	  }
	});

	const SoundType = {
	  reminder: 'reminder',
	  newMessage1: 'newMessage1',
	  newMessage2: 'newMessage2',
	  send: 'send',
	  dialtone: 'dialtone',
	  ringtone: 'ringtone',
	  start: 'start',
	  stop: 'stop',
	  error: 'error'
	};

	exports.DeviceType = DeviceType;
	exports.DeviceOrientation = DeviceOrientation;
	exports.MutationType = MutationType;
	exports.StorageLimit = StorageLimit;
	exports.OpenTarget = OpenTarget;
	exports.BotType = BotType;
	exports.RestMethod = RestMethod;
	exports.RestMethodHandler = RestMethodHandler;
	exports.PullCommand = PullCommand;
	exports.PullHandlers = PullHandlers;
	exports.EventType = EventType;
	exports.DialogType = DialogType;
	exports.DialogCrmType = DialogCrmType;
	exports.DialogReferenceClassName = DialogReferenceClassName;
	exports.DialogTemplateType = DialogTemplateType;
	exports.DialogState = DialogState;
	exports.DialogBlockType = DialogBlockType;
	exports.DialogScrollThreshold = DialogScrollThreshold;
	exports.FileStatus = FileStatus;
	exports.FileType = FileType;
	exports.FileIconType = FileIconType;
	exports.MessageType = MessageType;
	exports.MessageComponent = MessageComponent;
	exports.MessageExtension = MessageExtension;
	exports.MessageMentionType = MessageMentionType;
	exports.OwnMessageStatus = OwnMessageStatus;
	exports.ConferenceFieldState = ConferenceFieldState;
	exports.ConferenceStateType = ConferenceStateType;
	exports.ConferenceErrorCode = ConferenceErrorCode;
	exports.ConferenceRightPanelMode = ConferenceRightPanelMode;
	exports.ConferenceUserState = ConferenceUserState;
	exports.RecentSection = RecentSection;
	exports.MessageStatus = MessageStatus;
	exports.RecentCallStatus = RecentCallStatus;
	exports.NotificationTypesCodes = NotificationTypesCodes;
	exports.ChatOption = ChatOption;
	exports.Layout = Layout;
	exports.SearchEntityIdTypes = SearchEntityIdTypes;
	exports.UserStatus = UserStatus;
	exports.UserExternalType = UserExternalType;
	exports.SidebarDetailBlock = SidebarDetailBlock;
	exports.SidebarBlock = SidebarBlock;
	exports.SidebarFileTabTypes = SidebarFileTabTypes;
	exports.SidebarFileTypes = SidebarFileTypes;
	exports.Color = Color;
	exports.AttachType = AttachType;
	exports.AttachDescription = AttachDescription;
	exports.DesktopFeature = DesktopFeature;
	exports.DesktopBxLink = DesktopBxLink;
	exports.LocalStorageKey = LocalStorageKey;
	exports.ApplicationName = ApplicationName;
	exports.PlacementType = PlacementType;
	exports.PopupType = PopupType;
	exports.Settings = Settings;
	exports.SoundType = SoundType;

}((this.BX.Messenger.v2.Const = this.BX.Messenger.v2.Const || {})));
//# sourceMappingURL=registry.bundle.js.map

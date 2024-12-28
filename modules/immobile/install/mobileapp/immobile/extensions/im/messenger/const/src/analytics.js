/**
 * @module im/messenger/const/analytics
 */
jn.define('im/messenger/const/analytics', (require, exports, module) => {
	const CopilotChatType = Object.freeze({
		private: 'chatType_private',
		multiuser: 'chatType_multiuser',
	});

	const Event = Object.freeze({
		openChat: 'open_chat',
		openExisting: 'open_existing',
		openComments: 'open_comments',
		createNewChat: 'create_new_chat',
		clickCreateNew: 'click_create_new',
		submitCreateNew: 'submit_create_new',
		audioUse: 'audio_use',
		openTab: 'open_tab',
		openMessenger: 'open_messenger',
		sendMessage: 'send_message',
		clickDelete: 'click_delete',
		cancelDelete: 'cancel_delete',
		delete: 'delete',
		view: 'view',
		click: 'click',
		clickEdit: 'click_edit',
		submitEdit: 'submit_edit',
		clickCallButton: 'click_call_button',
		saveToDisk: 'save_to_disk',
		downloadFile: 'download_file',
		clickAddUser: 'click_add_user',
		openCalendar: 'open_calendar',
		openTasks: 'open_tasks',
		openFiles: 'open_files',
		clickCreateTask: 'click_create_task',
		clickCreateEvent: 'click_create_event',
		clickAttach: 'click_attach',
		galleryLimitException: 'gallery_limit_exception',
		cancelFileUpload: 'cancel_file_upload',
	});

	const Tool = Object.freeze({
		ai: 'ai',
		im: 'im',
	});

	const Category = Object.freeze({
		chatOperations: 'chat_operations',
		messenger: 'messenger',
		channel: 'channel',
		collab: 'collab',
		chat: 'chat',
		copilot: 'copilot',
		videoconf: 'videoconf',
		message: 'message',
		chatPopup: 'chat_popup',
		limitBanner: 'limit_banner',
	});

	const Type = Object.freeze({
		ai: 'ai',

		/* region tabs type */
		notifications: 'notifications',
		openlines: 'openlines',

		/* region dialog type */
		user: 'user',
		private: 'user', // for analytics type list: private === user
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
		lines: 'lines',
		copilot: 'copilot',
		channel: 'channel',
		openChannel: 'openChannel',
		generalChannel: 'generalChannel',
		comment: 'comment',
		custom: 'custom', // case for custom dialog type
		limitOfficeChatingHistory: 'limit_office_chating_history',
		collab: 'collab',

		/* region call type */
		privateCall: 'private',
		groupCall: 'group',

		/* region file type */
		image: 'image',
		video: 'video',
		audio: 'audio',
		file: 'file',

		/* region media type */
		media: 'media',
		files: 'files',
	});

	const Section = Object.freeze({
		copilotTab: 'copilot_tab',
		chatTab: 'chat_tab',
		channelTab: 'channel_tab',
		collabTab: 'collab_tab',
		notificationTab: 'notification_tab',
		sidebar: 'sidebar',
		activeChat: 'active_chat',
		popup: 'popup',
		mention: 'mention',
		link: 'link',
		chatHistory: 'chat_history',
		chatStart: 'chat_start',
		messageLink: 'message_link',
		chatWindow: 'chat_window',
		chatSidebar: 'chat_sidebar',
		comments: 'comments',
		editor: 'editor',
		chatTextarea: 'chat_textarea',
		messageContextMenu: 'message_context_menu',
		callMessage: 'call_message',
	});

	const SubSection = Object.freeze({
		window: 'window',
		contextMenu: 'context_menu',
	});

	const Element = Object.freeze({
		push: 'push',
		main: 'main',
		videocall: 'videocall',
		audiocall: 'audiocall',
		startMessage: 'start_message',
		finishMessage: 'finish_message',
	});

	const P3 = Object.freeze({
		isMemberY: 'isMember_Y',
		isMemberN: 'isMember_N',
	});

	const P1 = Object.freeze({
		openChannel: 'chatType_channelOpen',
		channel: 'chatType_channel',
		generalChannel: 'chatType_channelGeneral',
		comment: 'chatType_comments',
		user: 'chatType_private',
		open: 'chatType_groupOpen',
		chat: 'chatType_groupClosed',
		general: 'chatType_general',
		tasks: 'chatType_tasks',
		calendar: 'chatType_calendar',
		videoconf: 'chatType_videoconf',
		call: 'chatType_call',
		crm: 'chatType_crm',
		mail: 'chatType_mail',
		sonetGroup: 'chatType_sonetGroup',
		copilot: 'chatType_copilot',
	});

	/**
	 * keys are associated with UserType
	 * @see UserType
	 */
	const P2 = Object.freeze({
		user: 'user_intranet',
		extranet: 'user_extranet',
		collaber: 'user_collaber',
	});

	const Analytics = Object.freeze({
		CopilotChatType,
		Event,
		Tool,
		Category,
		Type,
		Section,
		SubSection,
		Element,
		P1,
		P2,
		P3,
	});

	module.exports = { Analytics };
});

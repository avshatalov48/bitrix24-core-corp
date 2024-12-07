export const AnalyticsEvent = Object.freeze({
	clickScreenshare: 'click_screenshare',
	startScreenshare: 'start_screenshare',
	finishScreenshare: 'finish_screenshare',
	connect: 'connect',
	startCall: 'start_call',
	reconnect: 'reconnect',
	addUser: 'add_user',
	disconnect: 'disconnect',
	finishCall: 'finish_call',
	clickRecord: 'click_record',
	recordStart: 'record_start',
	recordStop: 'record_stop',
	clickAnswer: 'click_answer',
	clickDeny: 'click_deny',
	cameraOn: 'camera_on',
	cameraOff: 'camera_off',
	micOn: 'mic_on',
	micOff: 'mic_off',
	clickUserFrame: 'click_user_frame',
	handOn: 'hand_on',
	clickChat: 'click_chat',
	click: 'click',
	create: 'create',
	edit: 'edit',
	save: 'save',
	upload: 'upload',
	openResume: 'open_resume',
});

export const AnalyticsTool = Object.freeze({
	im: 'im',
});

export const AnalyticsCategory = Object.freeze({
	call: 'call',
	callDocs: 'call_docs',
});

export const AnalyticsType = Object.freeze({
	private: 'private',
	group: 'group',
	videoconf: 'videoconf',
	resume: 'resume',
	doc: 'doc',
	presentation: 'presentation',
	sheet: 'sheet',
});

export const AnalyticsSection = Object.freeze({
	callWindow: 'call_window',
	callPopup: 'call_popup',
	chatList: 'chat_list',
	chatWindow: 'chat_window',
});

export const AnalyticsSubSection = Object.freeze({
	finishButton: 'finish_button',
	contextMenu: 'context_menu',
	window: 'window',
});

export const AnalyticsElement = Object.freeze({
	answerButton: 'answer_button',
	joinButton: 'join_button',
	videocall: 'videocall',
	recordButton: 'record_button',
	disconnectButton: 'disconnect_button',
	finishForAllButton: 'finish_for_all_button',
	videoButton: 'video_button',
	audioButton: 'audio_button',
});

export const AnalyticsStatus = Object.freeze({
	success: 'success',
	decline: 'decline',
	busy: 'busy',
	noAnswer: 'no_answer',
	quit: 'quit',
	lastUserLeft: 'last_user_left',
	finishedForAll: 'finished_for_all',
	privateToGroup: 'private_to_group',
});

export const AnalyticsDeviceStatus = Object.freeze({
	videoOn: 'video_on',
	videoOff: 'video_off',
	micOn: 'mic_on',
	micOff: 'mic_off',
});

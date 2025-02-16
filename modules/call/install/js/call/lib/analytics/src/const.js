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
	clickCallButton: 'click_call_button',
	clickStartConf: 'click_start_conf',
	aiRecordStart: 'ai_record_start',
	aiOn: 'ai_on',
	aiOff: 'ai_off',
	openTab: 'open_tab',
	clickCreateEvent: 'click_create_event',
	clickCreateTask: 'click_create_task',
	viewPopup: 'view_popup',
	viewNotification: 'view_notification',
	clickTimeCode: 'click_timecode',
	playRecord: 'play_record',
});

export const AnalyticsTool = Object.freeze({
	im: 'im',
	ai: 'ai',
});

export const AnalyticsCategory = Object.freeze({
	call: 'call',
	callDocs: 'call_docs',
	messenger: 'messenger',
	callsOperations: 'calls_operations',
	callFollowup: 'call_followup',
});

export const AnalyticsType = Object.freeze({
	private: 'private',
	group: 'group',
	videoconf: 'videoconf',
	resume: 'resume',
	doc: 'doc',
	presentation: 'presentation',
	sheet: 'sheet',
	privateCall: 'private',
	groupCall: 'group',
	aiOn: 'ai_on',
	turnOnAi: 'turn_on_ai',
});

export const AnalyticsSection = Object.freeze({
	callWindow: 'call_window',
	callPopup: 'call_popup',
	chatList: 'chat_list',
	chatWindow: 'chat_window',
	callMessage: 'call_message',
	callFollowup: 'call_followup',
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
	audiocall: 'audiocall',
	recordButton: 'record_button',
	disconnectButton: 'disconnect_button',
	finishForAllButton: 'finish_for_all_button',
	videoButton: 'video_button',
	audioButton: 'audio_button',
	startButton: 'start_button',
	initialBanner: 'initial_banner',
	startMessage: 'start_message',
	finishMessage: 'finish_message',
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
	errorAgreement: 'error_agreement',
	errorLimitBaas: 'error_limit_baas',
	errorB24: 'error_b24',
});

export const AnalyticsDeviceStatus = Object.freeze({
	videoOn: 'video_on',
	videoOff: 'video_off',
	micOn: 'mic_on',
	micOff: 'mic_off',
});

export const AnalyticsAIStatus = Object.freeze({
	aiOn: 'ai_on',
	aiOff: 'ai_off',
});

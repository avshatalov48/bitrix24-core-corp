/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,ui_analytics) {
	'use strict';

	const AnalyticsEvent = Object.freeze({
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
	  openResume: 'open_resume'
	});
	const AnalyticsTool = Object.freeze({
	  im: 'im'
	});
	const AnalyticsCategory = Object.freeze({
	  call: 'call',
	  callDocs: 'call_docs'
	});
	const AnalyticsType = Object.freeze({
	  private: 'private',
	  group: 'group',
	  videoconf: 'videoconf',
	  resume: 'resume',
	  doc: 'doc',
	  presentation: 'presentation',
	  sheet: 'sheet'
	});
	const AnalyticsSection = Object.freeze({
	  callWindow: 'call_window',
	  callPopup: 'call_popup',
	  chatList: 'chat_list',
	  chatWindow: 'chat_window'
	});
	const AnalyticsSubSection = Object.freeze({
	  finishButton: 'finish_button',
	  contextMenu: 'context_menu',
	  window: 'window'
	});
	const AnalyticsElement = Object.freeze({
	  answerButton: 'answer_button',
	  joinButton: 'join_button',
	  videocall: 'videocall',
	  recordButton: 'record_button',
	  disconnectButton: 'disconnect_button',
	  finishForAllButton: 'finish_for_all_button',
	  videoButton: 'video_button',
	  audioButton: 'audio_button'
	});
	const AnalyticsStatus = Object.freeze({
	  success: 'success',
	  decline: 'decline',
	  busy: 'busy',
	  noAnswer: 'no_answer',
	  quit: 'quit',
	  lastUserLeft: 'last_user_left',
	  finishedForAll: 'finished_for_all',
	  privateToGroup: 'private_to_group'
	});
	const AnalyticsDeviceStatus = Object.freeze({
	  videoOn: 'video_on',
	  videoOff: 'video_off',
	  micOn: 'mic_on',
	  micOff: 'mic_off'
	});

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _screenShareStarted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("screenShareStarted");
	var _recordStarted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recordStarted");
	class Analytics {
	  constructor() {
	    Object.defineProperty(this, _screenShareStarted, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _recordStarted, {
	      writable: true,
	      value: false
	    });
	  }
	  static getInstance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	  onScreenShareBtnClick({
	    callId,
	    callType
	  }) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _screenShareStarted)[_screenShareStarted]) {
	      return;
	    }
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickScreenshare,
	      type: callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${callId}`
	    });
	  }
	  onScreenShareStarted({
	    callId,
	    callType
	  }) {
	    babelHelpers.classPrivateFieldLooseBase(this, _screenShareStarted)[_screenShareStarted] = true;
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.startScreenshare,
	      type: callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${callId}`
	    });
	  }
	  onScreenShareStopped({
	    callId,
	    callType,
	    status,
	    screenShareLength
	  }) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _screenShareStarted)[_screenShareStarted]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _screenShareStarted)[_screenShareStarted] = false;
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.finishScreenshare,
	      type: callType,
	      c_section: AnalyticsSection.callWindow,
	      status: status,
	      p1: `shareLength_${screenShareLength}`,
	      p5: `callId_${callId}`
	    });
	  }
	  onAnswerConference(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickAnswer,
	      type: AnalyticsType.videoconf,
	      c_section: AnalyticsSection.callPopup,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDeclineConference(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickDeny,
	      type: AnalyticsType.videoconf,
	      c_section: AnalyticsSection.callPopup,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onStartVideoconf(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.startCall,
	      type: AnalyticsType.videoconf,
	      c_element: params.withVideo ? AnalyticsElement.videoButton : AnalyticsElement.audioButton,
	      status: params.status,
	      p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
	      p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onJoinVideoconf(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.connect,
	      type: AnalyticsType.videoconf,
	      c_element: params.withVideo ? AnalyticsElement.videoButton : AnalyticsElement.audioButton,
	      status: params.status,
	      p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
	      p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onStartCall(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.startCall,
	      type: params.callType,
	      status: params.status,
	      p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
	      p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onJoinCall(params) {
	    const sendParams = {
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.connect,
	      type: params.callType,
	      status: params.status,
	      p5: `callId_${params.callId}`
	    };
	    if (params.section) {
	      sendParams.c_section = params.section;
	    }
	    if (params.element) {
	      sendParams.c_element = params.element;
	    }
	    if (params.mediaParams) {
	      sendParams.p1 = params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff;
	      sendParams.p2 = params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff;
	    }
	    ui_analytics.sendData(sendParams);
	  }
	  onReconnect(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.reconnect,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      status: params.status,
	      p4: `attemptNumber_${params.reconnectionEventCount}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onInviteUser(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.addUser,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      p4: `chatId_${this.normalizeChatId(params.chatId)}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDisconnectCall(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.disconnect,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      c_sub_section: params.subSection,
	      status: AnalyticsStatus.quit,
	      p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
	      p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onFinishCall(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.finishCall,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      status: params.status,
	      p1: `callLength_${params.callLength}`,
	      p3: `maxUserCount_${params.callUsersCount}`,
	      p4: `chatId_${this.normalizeChatId(params.chatId)}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onRecordBtnClick(params) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _recordStarted)[_recordStarted]) {
	      return;
	    }
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickRecord,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      c_element: AnalyticsElement.recordButton,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onRecordStart(params) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _recordStarted)[_recordStarted]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _recordStarted)[_recordStarted] = true;
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.recordStart,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      status: AnalyticsStatus.success,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onRecordStop(params) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _recordStarted)[_recordStarted]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _recordStarted)[_recordStarted] = false;
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.recordStop,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      c_sub_section: params.subSection,
	      c_element: params.element,
	      p1: `recordLength_${params == null ? void 0 : params.recordTime}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onToggleCamera(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: params.video ? AnalyticsEvent.cameraOn : AnalyticsEvent.cameraOff,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onToggleMicrophone(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: params.muted ? AnalyticsEvent.micOff : AnalyticsEvent.micOn,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onClickUser(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickUserFrame,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      c_sub_section: params.layout.toLowerCase(),
	      p5: `callId_${params.callId}`
	    });
	  }
	  onFloorRequest(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.handOn,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onShowChat(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.call,
	      event: AnalyticsEvent.clickChat,
	      type: params.callType,
	      c_section: AnalyticsSection.callWindow,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDocumentBtnClick(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.callDocs,
	      event: AnalyticsEvent.click,
	      p4: `callType_${params.callType}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDocumentCreate(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.callDocs,
	      event: AnalyticsEvent.create,
	      type: params.type,
	      p4: `callType_${params.callType}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDocumentClose(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.callDocs,
	      event: AnalyticsEvent.save,
	      type: params.type,
	      p4: `callType_${params.callType}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onDocumentUpload(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.callDocs,
	      event: AnalyticsEvent.upload,
	      type: params.type,
	      p4: `callType_${params.callType}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  onLastResumeOpen(params) {
	    ui_analytics.sendData({
	      tool: AnalyticsTool.im,
	      category: AnalyticsCategory.callDocs,
	      event: AnalyticsEvent.openResume,
	      p4: `callType_${params.callType}`,
	      p5: `callId_${params.callId}`
	    });
	  }
	  normalizeChatId(chatId) {
	    if (!chatId) {
	      return 0;
	    }
	    if (chatId.includes('chat')) {
	      chatId = chatId.replace('chat', '');
	    }
	    return chatId;
	  }
	}
	Object.defineProperty(Analytics, _instance, {
	  writable: true,
	  value: void 0
	});
	Analytics.AnalyticsType = AnalyticsType;
	Analytics.AnalyticsStatus = AnalyticsStatus;
	Analytics.AnalyticsSection = AnalyticsSection;
	Analytics.AnalyticsElement = AnalyticsElement;
	Analytics.AnalyticsSubSection = AnalyticsSubSection;

	exports.Analytics = Analytics;

}((this.BX.Call.Lib = this.BX.Call.Lib || {}),BX.UI.Analytics));
//# sourceMappingURL=analytics.bundle.js.map

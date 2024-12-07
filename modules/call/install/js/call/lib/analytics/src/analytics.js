import { sendData } from 'ui.analytics';

import {
	AnalyticsEvent,
	AnalyticsTool,
	AnalyticsCategory,
	AnalyticsType,
	AnalyticsSection,
	AnalyticsElement,
	AnalyticsStatus,
	AnalyticsDeviceStatus,
	AnalyticsSubSection,
} from './const';

export class Analytics
{
	static #instance: Analytics;
	static AnalyticsType = AnalyticsType;
	static AnalyticsStatus = AnalyticsStatus;
	static AnalyticsSection = AnalyticsSection;
	static AnalyticsElement = AnalyticsElement;
	static AnalyticsSubSection = AnalyticsSubSection;

	#screenShareStarted: boolean = false;
	#recordStarted: boolean = false;

	static getInstance(): Analytics
	{
		if (!this.#instance)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}

	onScreenShareBtnClick({callId, callType})
	{
		if (this.#screenShareStarted)
		{
			return;
		}

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickScreenshare,
			type: callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${callId}`,
		});
	}

	onScreenShareStarted({callId, callType})
	{
		this.#screenShareStarted = true;

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.startScreenshare,
			type: callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${callId}`,
		});
	}

	onScreenShareStopped({callId, callType, status, screenShareLength})
	{
		if (!this.#screenShareStarted)
		{
			return;
		}

		this.#screenShareStarted = false;

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.finishScreenshare,
			type: callType,
			c_section: AnalyticsSection.callWindow,
			status: status,
			p1: `shareLength_${screenShareLength}`,
			p5: `callId_${callId}`,
		});
	}

	onAnswerConference(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickAnswer,
			type: AnalyticsType.videoconf,
			c_section: AnalyticsSection.callPopup,
			p5: `callId_${params.callId}`,
		});
	}

	onDeclineConference(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickDeny,
			type: AnalyticsType.videoconf,
			c_section: AnalyticsSection.callPopup,
			p5: `callId_${params.callId}`,
		});
	}

	onStartVideoconf(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.startCall,
			type: AnalyticsType.videoconf,
			c_element: params.withVideo ? AnalyticsElement.videoButton : AnalyticsElement.audioButton,
			status: params.status,
			p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
			p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
			p5: `callId_${params.callId}`,
		});
	}

	onJoinVideoconf(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.connect,
			type: AnalyticsType.videoconf,
			c_element: params.withVideo ? AnalyticsElement.videoButton : AnalyticsElement.audioButton,
			status: params.status,
			p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
			p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
			p5: `callId_${params.callId}`,
		});
	}

	onStartCall(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.startCall,
			type: params.callType,
			status: params.status,
			p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
			p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
			p5: `callId_${params.callId}`,
		});
	}

	onJoinCall(params)
	{
		const sendParams = {
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.connect,
			type: params.callType,
			status: params.status,
			p5: `callId_${params.callId}`,
		};

		if (params.section)
		{
			sendParams.c_section = params.section;
		}

		if (params.element)
		{
			sendParams.c_element = params.element;
		}

		if (params.mediaParams)
		{
			sendParams.p1 = params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff;
			sendParams.p2 = params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff;
		}

		sendData(sendParams);
	}

	onReconnect(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.reconnect,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			status: params.status,
			p4: `attemptNumber_${params.reconnectionEventCount}`,
			p5: `callId_${params.callId}`,
		});
	}

	onInviteUser(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.addUser,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			p4: `chatId_${this.normalizeChatId(params.chatId)}`,
			p5: `callId_${params.callId}`,
		});
	}

	onDisconnectCall(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.disconnect,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			c_sub_section: params.subSection,
			status: AnalyticsStatus.quit,
			p1: params.mediaParams.video ? AnalyticsDeviceStatus.videoOn : AnalyticsDeviceStatus.videoOff,
			p2: params.mediaParams.audio ? AnalyticsDeviceStatus.micOn : AnalyticsDeviceStatus.micOff,
			p5: `callId_${params.callId}`,
		});
	}

	onFinishCall(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.finishCall,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			status: params.status,
			p1: `callLength_${params.callLength}`,
			p3: `maxUserCount_${params.callUsersCount}`,
			p4: `chatId_${this.normalizeChatId(params.chatId)}`,
			p5: `callId_${params.callId}`,
		});
	}

	onRecordBtnClick(params)
	{
		if (this.#recordStarted)
		{
			return;
		}

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickRecord,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			c_element: AnalyticsElement.recordButton,
			p5: `callId_${params.callId}`,
		});
	}

	onRecordStart(params)
	{
		if (this.#recordStarted)
		{
			return;
		}

		this.#recordStarted = true;

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.recordStart,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			status: AnalyticsStatus.success,
			p5: `callId_${params.callId}`,
		});
	}

	onRecordStop(params)
	{
		if (!this.#recordStarted)
		{
			return;
		}

		this.#recordStarted = false;

		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.recordStop,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			c_sub_section: params.subSection,
			c_element: params.element,
			p1: `recordLength_${params?.recordTime}`,
			p5: `callId_${params.callId}`,
		});
	}

	onToggleCamera(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: params.video ? AnalyticsEvent.cameraOn : AnalyticsEvent.cameraOff,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${params.callId}`,
		});
	}

	onToggleMicrophone(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: params.muted ? AnalyticsEvent.micOff : AnalyticsEvent.micOn,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${params.callId}`,
		});
	}

	onClickUser(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickUserFrame,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			c_sub_section: params.layout.toLowerCase(),
			p5: `callId_${params.callId}`,
		});
	}

	onFloorRequest(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.handOn,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${params.callId}`,
		});
	}

	onShowChat(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.clickChat,
			type: params.callType,
			c_section: AnalyticsSection.callWindow,
			p5: `callId_${params.callId}`,
		});
	}

	onDocumentBtnClick(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callDocs,
			event: AnalyticsEvent.click,
			p4: `callType_${params.callType}`,
			p5: `callId_${params.callId}`,
		});
	}

	onDocumentCreate(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callDocs,
			event: AnalyticsEvent.create,
			type: params.type,
			p4: `callType_${params.callType}`,
			p5: `callId_${params.callId}`,
		});
	}

	onDocumentClose(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callDocs,
			event: AnalyticsEvent.save,
			type: params.type,
			p4: `callType_${params.callType}`,
			p5: `callId_${params.callId}`,
		});
	}

	onDocumentUpload(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callDocs,
			event: AnalyticsEvent.upload,
			type: params.type,
			p4: `callType_${params.callType}`,
			p5: `callId_${params.callId}`,
		});
	}

	onLastResumeOpen(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callDocs,
			event: AnalyticsEvent.openResume,
			p4: `callType_${params.callType}`,
			p5: `callId_${params.callId}`,
		});
	}

	normalizeChatId(chatId)
	{
		if (!chatId)
		{
			return 0;
		}

		if (chatId.includes('chat'))
		{
			chatId = chatId.replace('chat', '');
		}

		return chatId;
	}
}

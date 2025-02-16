import { sendData } from 'ui.analytics';

import {
	AnalyticsCategory,
	AnalyticsEvent,
	AnalyticsSection,
	AnalyticsStatus,
	AnalyticsTool,
	AnalyticsType,
} from '../const';

export class Copilot
{
	onAIRecordStart(params)
	{
		const errorCodes = {
			AI_UNAVAILABLE_ERROR: AnalyticsStatus.errorB24,
			AI_SETTINGS_ERROR: AnalyticsStatus.errorB24,
			AI_AGREEMENT_ERROR: AnalyticsStatus.errorAgreement,
			AI_NOT_ENOUGH_BAAS_ERROR: AnalyticsStatus.errorLimitBaas,
		};

		const resultData = {
			tool: AnalyticsTool.ai,
			category: AnalyticsCategory.callsOperations,
			event: AnalyticsEvent.aiRecordStart,
			type: params.callType,
			c_section: AnalyticsSection.callFollowup,
			p5: `callId_${params.callId}`,
		};

		if (params?.userCount)
		{
			resultData.p3 = `userCount_${params.userCount}`;
		}

		resultData.status = params?.errorCode
			? errorCodes[params.errorCode]
			: AnalyticsStatus.success
		;

		sendData(resultData);
	}

	onAIRecordStatusChanged(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: params.isAIOn ? AnalyticsEvent.aiOn : AnalyticsEvent.aiOff,
			type: params.callType,
			status: params?.error ? `error_${params.error}` : AnalyticsStatus.success,
			p5: `callId_${params.callId}`,
		});
	}

	onOpenFollowUpTab(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callFollowup,
			event: AnalyticsEvent.openTab,
			type: params.tabName,
			p5: `callId_${params.callId}`,
		});
	}

	onFollowUpCreateEventClick(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callFollowup,
			event: AnalyticsEvent.clickCreateEvent,
			p5: `callId_${params.callId}`,
		});
	}

	onFollowUpCreateTaskClick(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callFollowup,
			event: AnalyticsEvent.clickCreateTask,
			p5: `callId_${params.callId}`,
		});
	}

	onAIRestrictionsPopupShow(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.viewPopup,
			type: params.popupType,
			p5: `callId_${params.callId}`,
		});
	}

	onCopilotNotifyShow(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.call,
			event: AnalyticsEvent.viewNotification,
			type: params.isCopilotActive ? AnalyticsType.aiOn : AnalyticsType.turnOnAi,
			p5: `callId_${params.callId}`,
		});
	}

	onAIRecordTimeCodeClick(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callFollowup,
			event: AnalyticsEvent.clickTimeCode,
			p5: `callId_${params.callId}`,
		});
	}

	onAIPlayRecord(params)
	{
		sendData({
			tool: AnalyticsTool.im,
			category: AnalyticsCategory.callFollowup,
			event: AnalyticsEvent.playRecord,
			p5: `callId_${params.callId}`,
		});
	}
}

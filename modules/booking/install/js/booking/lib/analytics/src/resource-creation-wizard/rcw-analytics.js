import { sendData } from 'ui.analytics';

import { Core } from 'booking.core';
import {
	AnalyticsTool,
	AnalyticsCategory,
	AnalyticsEvent,
	AnalyticsElement,
	Model,
} from 'booking.const';
import type { SlotLengthId } from 'booking.model.resource-creation-wizard';

import { getCSection, isExistingResource } from './lib';
import type {
	ClickAddResourceOptions,
	AddResourceStep1Options,
	AddResourceStep2Options,
	AddResourceFinishOptions,
	AcceptAgreementOptions,
	P1RenderType,
	P2SetSchedule,
	P3SlotLength,
	P1InfoNotification,
	P2ConfirmationNotification,
	P3ReminderNotification,
	P4DelayedNotification,
	P5FeedbackNotification,
	AcceptAgreementCSubSection,
} from './types';

export class RcwAnalytics
{
	static sendClickAddResource(): void
	{
		const options: ClickAddResourceOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: AnalyticsEvent.clickAddResource,
			c_section: getCSection(),
			c_element: AnalyticsElement.addButton,
		};

		sendData(options);
	}

	static sendAddResourceStep1(): void
	{
		const $store = Core.getStore();
		if (isExistingResource())
		{
			return;
		}

		const options: AddResourceStep1Options = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: AnalyticsEvent.addResourceStep1,
			c_section: getCSection(),
		};

		let code = $store.getters[`${Model.ResourceCreationWizard}/advertisingResourceType`]?.code || null;
		if (code === 'none')
		{
			code = 'other';
		}

		if (!code)
		{
			console.error('Booking.RCW. Code not found');

			return;
		}

		options.type = code;

		sendData(options);
	}

	static sendAddResourceStep2(): void
	{
		const $store = Core.getStore();
		if (isExistingResource())
		{
			return;
		}

		const getP1 = (): P1RenderType => {
			return $store.state[Model.ResourceCreationWizard].resource.isMain
				? 'renderType_main'
				: 'renderType_additional';
		};

		const getP2 = (): P2SetSchedule => {
			return $store.state[Model.ResourceCreationWizard].globalSchedule
				? 'setSchedule_Y'
				: 'setSchedule_N';
		};

		const getP3 = (): P3SlotLength => {
			const slotLengthId: SlotLengthId = $store.state[Model.ResourceCreationWizard].slotLengthId;
			switch (slotLengthId / 60)
			{
				case 1:
					return 'slotLength_1h';
				case 2:
					return 'slotLength_2h';
				case 24:
					return 'slotLength_24h';
				case 168:
					return 'slotLength_7d';
				default:
					return 'slotLength_custom';
			}
		};

		const options: AddResourceStep2Options = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: AnalyticsEvent.addResourceStep2,
			c_section: getCSection(),
			p1: getP1(),
			p2: getP2(),
			p3: getP3(),
		};
		sendData(options);
	}

	static sendAddResourceFinish(): void
	{
		const $store = Core.getStore();
		if (isExistingResource())
		{
			return;
		}

		const isCurrentSenderAvailable = $store.getters[`${Model.Notifications}/isCurrentSenderAvailable`];
		const resource = $store.state[Model.ResourceCreationWizard].resource;

		const getP1 = (): P1InfoNotification => {
			return isCurrentSenderAvailable && resource.isInfoNotificationOn
				? 'infoNotification_Y'
				: 'infoNotification_N';
		};

		const getP2 = (): P2ConfirmationNotification => {
			return isCurrentSenderAvailable && resource.isConfirmationNotificationOn
				? 'confirmationNotification_Y'
				: 'confirmationNotification_N';
		};

		const getP3 = (): P3ReminderNotification => {
			return isCurrentSenderAvailable && resource.isReminderNotificationOn
				? 'reminderNotification_Y'
				: 'reminderNotification_N';
		};

		const getP4 = (): P4DelayedNotification => {
			return isCurrentSenderAvailable && resource.isDelayedNotificationOn
				? 'delayedNotification_Y'
				: 'delayedNotification_N';
		};

		const getP5 = (): P5FeedbackNotification => {
			return isCurrentSenderAvailable && resource.isFeedbackNotificationOn
				? 'feedbackNotification_Y'
				: 'feedbackNotification_N';
		};

		const options: AddResourceFinishOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: AnalyticsEvent.addResourceFinish,
			c_section: getCSection(),
			p1: getP1(),
			p2: getP2(),
			p3: getP3(),
			p4: getP4(),
			p5: getP5(),
		};
		sendData(options);
	}

	static sendAcceptAgreement({ accepted }: { accepted: boolean }): void
	{
		if (isExistingResource())
		{
			return;
		}

		const getCSubSection = (): AcceptAgreementCSubSection => {
			return accepted ? 'accept' : 'deny';
		};

		const options: AcceptAgreementOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: AnalyticsEvent.acceptAgreement,
			c_section: getCSection(),
			c_sub_section: getCSubSection(),
		};
		sendData(options);
	}
}

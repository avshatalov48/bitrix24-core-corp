import { Extension, Type } from 'main.core';
import {
	PromoVideoPopup,
	PromoVideoPopupTargetOptions,
	PromoVideoPopupAngleOptions,
	PromoVideoPopupOffset,
	AnglePosition,
	PromoVideoPopupEvents,
} from 'ui.promo-video-popup';
import { Button } from 'ui.buttons';
import { Main as MainIconSet } from 'ui.icon-set.api.core';

import 'ui.icon-set.main';

import { CopilotPromoPopupPresetData } from './copilot-promo-popup-presets';

export type CopilotPromoPopupCreateOptions = {
	presetId: string;
	targetOptions: PromoVideoPopupTargetOptions;
	angleOptions: PromoVideoPopupAngleOptions;
	offset: PromoVideoPopupOffset;
}

export class CopilotPromoPopup
{
	static AnglePosition = AnglePosition;

	static Preset = Object.freeze({
		TASK: 'task',
		LIVE_FEED_EDITOR: 'liveFeedEditor',
		CHAT: 'chat',
	});

	static PromoVideoPopupEvents = PromoVideoPopupEvents;

	static getWidth(): number
	{
		return PromoVideoPopup.getWidth();
	}

	static createByPresetId(options: CopilotPromoPopupCreateOptions): PromoVideoPopup
	{
		CopilotPromoPopup.#checkPreset(options.presetId);

		const presetId = options.presetId;
		const preset = CopilotPromoPopupPresetData[presetId];

		const promoVideoPopup = new PromoVideoPopup({
			targetOptions: options.targetOptions,
			videoSrc: preset.videoSrc[CopilotPromoPopup.#getVideoLang()],
			videoContainerMinHeight: preset.videoContainerMinHeight,
			title: preset.title,
			text: preset.text,
			icon: MainIconSet.COPILOT_AI,
			angleOptions: options.angleOptions,
			offset: options.offset,
			colors: {
				title: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-secondary'),
				iconBackground: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary'),
				button: Button.Color.AI,
			},
		});

		promoVideoPopup.subscribe(PromoVideoPopupEvents.ACCEPT, () => {
			promoVideoPopup.hide();
		});

		return promoVideoPopup;
	}

	static #checkPreset(presetId: string): void
	{
		if (Type.isStringFilled(presetId) === false)
		{
			throw new Error('AI.CopilotPromoPopup: presetId is required option and must be the string');
		}

		if (CopilotPromoPopup.#isPresetExist(presetId) === false)
		{
			throw new Error(`AI.CopilotPromoPopup: preset with id '${presetId}' doesn't exist`);
		}
	}

	static #isPresetExist(presetId: string): boolean
	{
		return Boolean(CopilotPromoPopupPresetData[presetId]);
	}

	static #getVideoLang(): 'ru' | 'en'
	{
		return CopilotPromoPopup.#isWestZone() ? 'en' : 'ru';
	}

	static #isWestZone(): boolean
	{
		return Extension.getSettings('ai.copilot-promo-popup').isWestZone;
	}
}

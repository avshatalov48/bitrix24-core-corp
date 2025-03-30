import { Loc, ajax } from 'main.core';
import {
	PromoVideoPopup,
	PromoVideoPopupTargetOptions,
	PromoVideoPopupEvents,
	PromoVideoPopupButtonPosition,
} from 'ui.promo-video-popup';
import { Button } from 'ui.buttons';
import { Main as MainIconSet } from 'ui.icon-set.api.core';

import './style.css';

export type DiskPromoPopupOptions = {
	targetOptions: PromoVideoPopupTargetOptions;
	boardsUrl: string,
	componentName: string,
}

export class DiskVideoPopup
{
	#promoVideoPopup: ?PromoVideoPopup = null;

	constructor(options: DiskPromoPopupOptions): DiskVideoPopup
	{
		this.targetOptions = options.targetOptions ? options.targetOptions : window;
		this.boardsUrl = options.boardsUrl;
		this.componentName = options.componentName;
	}

	getWidth(): number
	{
		return PromoVideoPopup.getWidth();
	}

	getPopup(): PromoVideoPopup
	{
		const language = Loc.getMessage('LANGUAGE_ID');
		const sources = {
			ru: '/bitrix/js/disk/boards-promo-popup/video/ru/disk-promo-ru.webm',
			en: '/bitrix/js/disk/boards-promo-popup/video/en/disk-promo-en.webm',
		};

		if (this.#promoVideoPopup === null)
		{
			this.#promoVideoPopup = new PromoVideoPopup({
				videoSrc: language === 'ru' ? sources.ru : sources.en,
				videoContainerMinHeight: 255,
				title: Loc.getMessage('DISK_PROMO_VIDEO_POPUP_TITLE'),
				text: Loc.getMessage('DISK_PROMO_VIDEO_POPUP_TEXT'),
				targetOptions: this.targetOptions,
				icon: MainIconSet.DEMONSTRATION_GRAPHICS,
				button: {
					text: Loc.getMessage('DISK_PROMO_VIDEO_POPUP_BUTTON'),
					color: Button.Color.SUCCESS,
					size: Button.Size.LARGE,
					position: PromoVideoPopupButtonPosition.RIGHT,
				},
				offset: {
					top: 50,
					left: 50,
				},
				useOverlay: true,
				autoHide: false,
			});

			this.#promoVideoPopup.subscribe(PromoVideoPopupEvents.ACCEPT, () => {
				this.setCompleted().then(() => {
					window.location.href = this.boardsUrl;
				});
			});

			this.#promoVideoPopup.subscribe(PromoVideoPopupEvents.HIDE, () => {
				this.setViewed();
				document.body.style.overflowY = 'scroll';
			});
		}

		return this.#promoVideoPopup;
	}

	show(): void
	{
		this.getPopup().show();
		document.body.style.overflowY = 'hidden';
	}

	setViewed(): void
	{
		this.#requestToBackend('setViewed');
	}

	setCompleted(): Promise
	{
		return this.#requestToBackend('setCompleted');
	}

	#requestToBackend(action: string): Promise
	{
		return ajax.runComponentAction(this.componentName, action, {
			mode: 'class',
		});
	}
}

import { Tag, Loc, bind, Dom, Reflection } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonColor } from 'ui.buttons';
import { Icon, Main as MainIconSet, Actions as ActionsIconSet } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import { Lottie } from 'ui.lottie';

import './recognition-promo.css';

export type RecognitionPromoOptions = {
	events?: RecognitionPromoEvents;
};

type RecognitionPromoEvents = {
	onClickOnConnectButton?: Function;
	onClickOnRemindLaterButton?: Function;
	onClickOnClosePopup?: Function;
	onShow?: Function;
	onHide?: Function;
};

export class RecognitionPromo
{
	#popup: ?Popup = null;
	#events: RecognitionPromoEvents;

	constructor(options: RecognitionPromoOptions)
	{
		this.#events = options?.events ?? {};
		this.#preloadMainLottieAnimation();
	}

	show(): void
	{
		if (this.#popup === null)
		{
			this.#popup = this.#initPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	subscribe(eventName, callback):void
	{
		if (this.#popup === null)
		{
			this.#popup = this.#initPopup();
		}

		this.#popup.subscribe(eventName, callback);
	}

	shouldShowAgain(): boolean
	{
		const checkbox = document.getElementById('crm__ai-recognition-promo_checkbox_dont_show_again');
		return checkbox ? !checkbox.checked : true;
	}

	#initPopup(): Popup
	{
		return new Popup({
			content: this.#renderPopupContent(),
			padding: 0,
			width: 528,
			noAllPaddings: true,
			overlay: {
				backgroundColor: '#000',
				opacity: 40,
			},
			cacheable: false,
			borderRadius: 16,
			background: 'transparent',
			contentBackground: 'transparent',
			animation: 'fading-slide',
			events: {
				onPopupShow: () => {
					if (this.#events.onShow)
					{
						this.#events.onShow();
					}
				},
				onPopupClose: () => {
					if (this.#events.onHide)
					{
						this.#events.onHide();
					}

					this.#popup = null;
				},
			},
		});
	}

	#renderPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="crm__ai-recognition-promo">
				<header class="crm__ai-recognition-promo_header">
					<div class="crm__ai-recognition-promo_header-left">
						<div class="crm__ai-recognition-promo_header-icon">
							${this.#renderHeaderCopilotIcon()}
						</div>
						<h4 class="crm__ai-recognition-promo_header-title">
							${Loc.getMessage('RECOGNITION_PROMO_TITLE')}
						</h4>
					</div>
					<div class="crm__ai-recognition-promo_header-close-button">
						${this.#renderHidePopupButton()}
					</div>
				</header>
				<main class="crm__ai-recognition-promo_content">
					${this.#renderLottieAnimation()}
					${this.#renderContentText()}
				</main>
				<footer class="crm__ai-recognition-promo_footer">
					${this.#renderConnectTelephonyButton()}
					${this.#renderRemindLaterButton()}
				</footer>
				<div class="crm__ai-recognition-promo_checkbox_dont_show_again">
					<input type="checkbox" id="crm__ai-recognition-promo_checkbox_dont_show_again">
					<label>${Loc.getMessage('RECOGNITION_PROMO_DONT_SHOW_AGAIN')}</label>
				</div>
			</div>
		`;
	}

	#renderHeaderCopilotIcon(): HTMLElement
	{
		const icon = new Icon({
			icon: MainIconSet.COPILOT_AI,
			size: 40,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary') ?? '#8E52EC',
		});

		return icon.render();
	}

	#renderHidePopupButton(): HTMLElement
	{
		const icon = new Icon({
			icon: ActionsIconSet.CROSS_40,
			size: 24,
		});

		const button = Tag.render`
			<button class="crm__ai-recognition-promo_close-popup-button">
				${icon.render()}
			</button>
		`;

		bind(button, 'click', () => {
			if (this.#events?.onClickOnClosePopup)
			{
				this.#events.onClickOnClosePopup();
			}
			{
				this.hide();
			}
		});

		return button;
	}

	#renderLottieAnimation(): HTMLElement
	{
		const container = Tag.render`
			<div class="crm__ai-recognition-promo_video-container">
				<canvas ref="canvas"></canvas>
				<div ref="lottie" class="crm__ai-recognition-promo_content-lottie"></div>
				<div ref="confetti" class="crm__ai-recognition-promo_content-confetti"></div>
			</div>
		`;

		const mainAnimation = Lottie.loadAnimation({
			path: this.#getMainAnimationPath(),
			container: container.lottie,
			renderer: 'svg',
			loop: true,
			autoplay: true,
		});

		mainAnimation.setSpeed(0.75);

		const confettiAnimation = Lottie.loadAnimation({
			path: '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/confetti-animation.json',
			container: container.confetti,
			renderer: 'svg',
			loop: true,
			autoplay: false,
		});

		confettiAnimation.setSpeed(1.3);

		Dom.style(container.confetti, 'opacity', 0);

		bind(confettiAnimation, 'loopComplete', () => {
			confettiAnimation.pause();
			Dom.style(container.confetti, 'opacity', 0);
		});

		let confettiWereShown = false;

		bind(mainAnimation, 'loopComplete', () => {
			confettiWereShown = false;
			Dom.style(container.confetti, 'opacity', 0);
		});

		bind(mainAnimation, 'enterFrame', (e) => {
			if (confettiWereShown === false && e.currentTime > 350)
			{
				confettiAnimation.play();
				Dom.style(container.confetti, 'opacity', 1);
				confettiWereShown = true;
			}
		});

		bind(mainAnimation, 'enterFrame', (e) => {
			if (e.currentTime > 350 && confettiWereShown === false)
			{
				confettiAnimation.play();
				Dom.style(container.confetti, 'opacity', 1);
				confettiWereShown = true;
			}
		});

		return container.root;
	}

	#renderContentText(): HTMLElement
	{
		const content = Loc.getMessage('RECOGNITION_PROMO_CONTENT', {
			'[P]': '<p>',
			'[/P]': '</p>',
			'[LINK1]': '<a ref="link1">',
			'[/LINK1]': '</a>',
			'[LINK2]': '<a ref="link2">',
			'[/LINK2]': '</a>',
		});

		const container: { root: HTMLElement, link1: HTMLElement, link2: HTMLElement } = Tag.render`
			<div class="crm__ai-recognition-promo_content-description">
				${content}
			</div>
		`;

		const Helper = Reflection.getClass('top.BX.Helper');

		bind(container.link1, 'click', () => {
			const articleCode = '19092894'; // todo replace with the real article code

			Helper?.show(`redirect=detail&code=${articleCode}`);
		});

		bind(container.link2, 'click', () => {
			const articleCode = '6450911'; // todo replace with the real article code

			Helper?.show(`redirect=detail&code=${articleCode}`);
		});

		return container.root;
	}

	#renderConnectTelephonyButton(): HTMLElement
	{
		const button = new Button({
			color: ButtonColor.SUCCESS,
			text: Loc.getMessage('RECOGNITION_PROMO_CONNECT_TELEPHONY'),
			round: true,
			onclick: (btn: Button) => {
				if (this.#events?.onClickOnConnectButton)
				{
					this.#events.onClickOnConnectButton(btn);
				}
			},
		});

		return button.render();
	}

	#renderRemindLaterButton(): HTMLElement
	{
		const button = new Button({
			color: ButtonColor.LINK,
			text: Loc.getMessage('RECOGNITION_PROMO_REMIND_LATER'),
			round: true,
			onclick: (btn: Button) => {
				if (this.#events?.onClickOnRemindLaterButton)
				{
					this.#events.onClickOnRemindLaterButton(btn);
				}
			},
		});

		return button.render();
	}

	#preloadMainLottieAnimation(): void
	{
		Lottie.loadAnimation({
			path: this.#getMainAnimationPath(),
			renderer: 'svg',
		});
	}

	#getMainAnimationPath(): string
	{
		return Loc.getMessage('LANGUAGE_ID') === 'ru'
			? '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/animation-ru.json'
			: '/bitrix/js/crm/ai/whatsnew/recognition-promo/lottie/animation-en.json'
		;
	}
}

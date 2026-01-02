import { Tag, Extension, Loc, Event } from 'main.core';
import { PopupWindowManager } from 'main.popup';
import { EventEmitter } from 'main.core.events';
import { Button, CloseButton } from 'ui.buttons';
import { sendData } from 'ui.analytics';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import './style.css';
import 'ui.fonts.montserrat';

type PopupPosition = {
	minHeight: number,
	minWidth: number,
	offsetTop: number,
	offsetLeft: number,
}
export class MobilePopup extends EventEmitter
{
	#popup: ?Popup = null;
	#authLink: string = null;
	#isTablet: boolean = false;
	#continueInMobileBrowserClicked: boolean = false;
	#continueInTabletBrowserClicked: boolean = false;
	#collabId: ?int = null;
	#isCollaber: boolean = false;
	#isMobileContinueButton: boolean = false;
	#isTabletContinueButton: boolean = false;
	#position: PopupPosition;

	constructor()
	{
		super();
		this.setEventNamespace('BX.Intranet.MobilePopup');
		this.#authLink = Extension.getSettings('intranet.mobile-popup').authLink;
		this.#collabId = Extension.getSettings('intranet.mobile-popup').collabId;
		this.#isCollaber = Extension.getSettings('intranet.mobile-popup').isCollaber;
		this.#continueInMobileBrowserClicked = Extension.getSettings('intranet.mobile-popup').continueInMobileBrowserClicked;
		this.#continueInTabletBrowserClicked = Extension.getSettings('intranet.mobile-popup').continueInTabletBrowserClicked;
		this.#isMobileContinueButton = Extension.getSettings('intranet.mobile-popup').isMobileContinueButton;
		this.#isTabletContinueButton = Extension.getSettings('intranet.mobile-popup').isTabletContinueButton;
		this.#isTablet = window.screen.width >= 768;

		this.#sendAnalytics('push_mobapp_show');
	}

	#calculatePopupPosition(): void
	{
		const isAirTemplate = BX.Reflection.getClass('BX.Intranet.Bitrix24.Template') !== null;
		const minHeight = 602;
		const minWidth = this.#isTablet ? 375 : 321;
		const height = (window.screen.height - minHeight) >= 0 ? (window.screen.height - minHeight) / 2 : 0;
		const width = (window.screen.width - minWidth) >= 0 ? (window.screen.width - minWidth) / 2 : 0;
		let offsetTop = height - window.screen.height;

		if (isAirTemplate)
		{
			offsetTop = height - document.body.scrollHeight;
		}

		this.#position = {
			minHeight,
			minWidth,
			offsetTop,
			offsetLeft: width,
		};
	}

	#createPopup(onDone: function): Popup
	{
		return PopupWindowManager.create(
			'main-intranet-mobile-popup',
			document.body,
			{
				overlay: true,
				borderRadius: '24px',
				contentBorderRadius: '24px',
				closeIcon: false,
				minWidth: this.#position.minWidth,
				minHeight: this.#position.minHeight,
				content: this.#getContent(),
				//disableScroll: true,
				padding: 0,
				offsetTop: this.#position.offsetTop,
				offsetLeft: this.#position.offsetLeft,
				className: 'intranet-mobile-popup-wrapper',
				events: {
					onClose: () => {
						this.emit('onClose');
						onDone();
					},
				},
			},
		);
	}

	show(): void
	{
		if (
			(this.#isTablet && this.#continueInTabletBrowserClicked)
			|| (!this.#isTablet && this.#continueInMobileBrowserClicked)
		)
		{
			return;
		}

		BannerDispatcher.critical.toQueue((onDone) => {
			this.#calculatePopupPosition();
			this.#popup ??= this.#createPopup(onDone);
			this.#popup?.show();
			document.body.scrollIntoView();
		});
	}

	#getContent(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-mobile-popup">
				<div class="intranet-mobile-popup__content-wrapper">
					<div class="intranet-mobile-popup__main">
						<span class="intranet-mobile-popup__title">${Loc.getMessage('INTRANET_RECOGNIZE_LINKS_TITLE')}</span>
						${this.#renderImage()}
						<p class="intranet-mobile-popup__description">${Loc.getMessage('INTRANET_RECOGNIZE_LINKS_DESCRIPTION')}</p>
						${this.#renderContinueButton()}
					</div>
					${this.#renderFooter()}
				</div>
			</div>
		`;
	}

	#renderImage(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-mobile-popup__image"></div>
		`;
	}

	#renderFooter(): ?HTMLElement
	{
		if (
			(this.#isTablet && this.#isTabletContinueButton)
			|| (!this.#isTablet && this.#isMobileContinueButton)
		)
		{
			return Tag.render`
				<div class="intranet-mobile-popup__footer">
					${this.#renderHideButton()}
				</div>
			`;
		}

		return null;
	}

	#renderContinueButton(): HTMLElement
	{
		let text = Loc.getMessage('INTRANET_RECOGNIZE_LINKS_CONTINUE');
		if (
			(this.#isTablet && this.#isTabletContinueButton)
			|| (!this.#isTablet && this.#isMobileContinueButton)
		)
		{
			text = Loc.getMessage('INTRANET_RECOGNIZE_LINKS_OPEN_APP');
		}

		return new Button({
			text,
			className: 'intranet-mobile-popup__button intranet-mobile-popup__button--continue',
			size: Button.Size.LARGE,
			color: Button.Color.SUCCESS,
			round: true,
			onclick: () => {
				this.#popup?.close();
				this.#sendAnalytics('push_mobapp_click_start');
				setTimeout(() => {
					window.location.href = this.#authLink;
				});
			},
		}).render();
	}

	#renderHideButton(): HTMLElement
	{
		return new CloseButton({
			text: Loc.getMessage('INTRANET_RECOGNIZE_LINKS_CONTINUE_IN_BROWSER'),
			className: 'intranet-mobile-popup__button intranet-mobile-popup__button--hide',
			size: Button.Size.SMALL,
			onclick: () => {
				this.#popup?.close();
				this.#sendAnalytics('push_mobapp_click_stay_browser');
				const optionName = this.#isTablet ? 'tabletPopupContinueToApp' : 'mobilePopupContinueToApp';
				BX.userOptions.save('intranet', optionName, null, 'Y');
			},
		}).render();
	}

	#sendAnalytics(event: string): void
	{
		const params = {
			tool: 'intranet',
			category: 'activation',
			event,
			type: 'inside_portal',
			c_sub_section: 'push_to_mobapp',
			p2: `popupClosable_${this.#isTablet ? 'Y' : 'N'}`,
		};

		if (this.#isCollaber)
		{
			params.p5 = `collabId_${this.#collabId}`;
		}

		sendData(params);
	}
}

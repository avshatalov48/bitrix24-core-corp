import { Type, Reflection, Runtime, Dom, Browser } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { SidePanel } from 'main.sidepanel';
import { ChatMenu } from './chat-menu';
import { Composite } from './composite';
import { RightBar } from './right-bar';
import { Header } from './header/header';
import { Footer } from './footer';
import { GoTopButton } from './go-top-button';
import { CollaborationMenu } from './collaboration-menu';

const DEFAULT_SLIDER_BLUR = 'blur(6px)';
const IM_SLIDER_BLUR = 'blur(10px)';
const SIDEPANEL_BORDER_RADIUS = '18px 18px 0 0';

export class SiteTemplate
{
	#rightBar: RightBar = null;
	#header: Header = null;
	#footer: Footer = null;
	#composite: Composite = null;
	#chatMenu: ChatMenu = null;
	#goTopButton: GoTopButton = null;
	#collaborationMenu: CollaborationMenu = null;

	constructor()
	{
		this.#preventFromIframe();

		this.#makeSliderBlurry();
		this.#patchPopupMenu();
		this.#patchRestAPI();
		this.#patchJSClock();

		this.#goTopButton = new GoTopButton();
		this.#rightBar = new RightBar({
			goTopButton: this.#goTopButton,
		});
		this.#header = new Header();
		this.#footer = new Footer();
		this.#composite = new Composite();
		this.#chatMenu = new ChatMenu();
		this.#collaborationMenu = new CollaborationMenu();

		this.#applyUserAgentRules();
	}

	getRightBar(): RightBar
	{
		return this.#rightBar;
	}

	getHeader(): Header
	{
		return this.#header;
	}

	getFooter(): Footer
	{
		return this.#footer;
	}

	getComposite(): Composite
	{
		return this.#composite;
	}

	getChatMenu(): ChatMenu
	{
		return this.#chatMenu;
	}

	getCollaborationMenu(): CollaborationMenu
	{
		return this.#collaborationMenu;
	}

	canUseBlurry(): boolean
	{
		return !Dom.hasClass(document.documentElement, 'bx-integrated-gpu');
	}

	#patchPopupMenu(): void
	{
		EventEmitter.subscribe('BX.Main.Menu:onInit', (event: BaseEvent) => {
			const { params } = event.getData();
			if (params && Type.isNumber(params.maxWidth))
			{
				// We increased menu-item's font-size that's why we increase max-width
				params.maxWidth += 10;
			}
		});
	}

	#patchJSClock(): void
	{
		EventEmitter.subscribe('onJCClockInit', (config) => {
			window.JCClock.setOptions({
				centerXInline: 83,
				centerX: 83,
				centerYInline: 67,
				centerY: 79,
				minuteLength: 31,
				hourLength: 26,
				popupHeight: 229,
				inaccuracy: 15,
				cancelCheckClick: true,
			});
		});
	}

	#fixSliderBorderRadius(slider, forceBgColor = null)
	{
		if (slider.isSelfContained())
		{
			Dom.style(slider.getContainer(), 'background-color', '#eef2f4');
		}
		else
		{
			const frameDocument = slider?.getFrameWindow()?.document;
			if (frameDocument && frameDocument.body)
			{
				const bgColor = Dom.style(frameDocument.body, 'background-color');
				const bgImage = Dom.style(frameDocument.body, 'background-image');
				const foreBgColor = bgColor === 'rgba(0, 0, 0, 0)' && bgImage === 'none' ? '#eef2f4' : 'rgba(0, 0, 0, 0)';

				Dom.style(slider.getContainer(), 'background-color', forceBgColor === null ? foreBgColor : forceBgColor);
			}
			else
			{
				Dom.style(slider.getContainer(), 'background-color', forceBgColor === null ? '#eef2f4' : forceBgColor);
			}
		}
	}

	#makeSliderBlurry(): void
	{
		EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', (event: BaseEvent) => {
			const [sliderEvent] = event.getData();
			const slider = sliderEvent.getSlider();

			if (!slider.isLoaded())
			{
				this.#fixSliderBorderRadius(slider, 'rgba(0, 0, 0, 0)');
			}

			const previousSlider = SidePanel.Instance.getPreviousSlider();
			if (previousSlider)
			{
				this.#resetSliderBlur(previousSlider);
			}

			this.#setSliderBlur(slider);
		});

		EventEmitter.subscribe('SidePanel.Slider:onClosing', (event: BaseEvent) => {
			const [sliderEvent] = event.getData();
			const slider = sliderEvent.getSlider();

			const previousSlider = SidePanel.Instance.getPreviousSlider();
			if (previousSlider)
			{
				this.#setSliderBlur(previousSlider);
			}

			this.#resetSliderBlur(slider);
		});

		EventEmitter.subscribe('SidePanel.Slider:onOpening', () => {
			if (SidePanel.Instance.getOpenSlidersCount() === 1)
			{
				Dom.addClass(document.body, '--ui-reset-bg-blur');
			}
			// else
			// {
			// 	const previousSlider = SidePanel.Instance.getPreviousSlider();
			// 	const frameDocument = previousSlider?.getFrameWindow()?.document;
			// 	Dom.addClass(frameDocument?.body, '--ui-reset-bg-blur');
			// }
		});

		EventEmitter.subscribe('SidePanel.Slider:onClosing', () => {
			if (SidePanel.Instance.getOpenSlidersCount() === 1)
			{
				Dom.removeClass(document.body, '--ui-reset-bg-blur');
			}
			// else
			// {
			// 	const previousSlider = SidePanel.Instance.getPreviousSlider();
			// 	const frameDocument = previousSlider?.getFrameWindow()?.document;
			// 	Dom.removeClass(frameDocument?.body, '--ui-reset-bg-blur');
			// }
		});

		EventEmitter.subscribe('SidePanel.Slider:onLoad', (event: BaseEvent) => {
			const [sliderEvent] = event.getData();
			const slider = sliderEvent.getSlider();
			this.#fixSliderBorderRadius(slider);
			requestAnimationFrame(() => {
				this.#fixSliderBorderRadius(slider);
			});
		});
	}

	#setSliderBlur(slider): void
	{
		if (!this.canUseBlurry())
		{
			return;
		}

		const isMessenger = slider.getUrl().startsWith('im:slider');

		Dom.style(slider.getOverlay(), '-webkit-backdrop-filter', isMessenger ? IM_SLIDER_BLUR : DEFAULT_SLIDER_BLUR);
		Dom.style(slider.getOverlay(), 'backdrop-filter', isMessenger ? IM_SLIDER_BLUR : DEFAULT_SLIDER_BLUR);
		Dom.style(slider.getOverlay(), '--sidepanel-border-radius', SIDEPANEL_BORDER_RADIUS);
	}

	#resetSliderBlur(slider): void
	{
		if (!this.canUseBlurry())
		{
			return;
		}

		Dom.style(slider.getOverlay(), '-webkit-backdrop-filter', null);
		Dom.style(slider.getOverlay(), 'backdrop-filter', null);
		Dom.style(slider.getOverlay(), '--sidepanel-border-radius', null);
	}

	#preventFromIframe(): void
	{
		const iframeMode = window !== window.top;
		if (iframeMode)
		{
			window.top.location = window.location.href;
		}
	}

	#applyUserAgentRules(): void
	{
		if (!Browser.isMobile() && document.referrer !== '' && document.referrer.startsWith(location.origin) === false)
		{
			Runtime.loadExtension('intranet.recognize-links');
		}
	}

	#patchRestAPI(): void
	{
		const AppLayout = Reflection.getClass('BX.rest.AppLayout');
		if (!AppLayout)
		{
			return;
		}

		const placementInterface = AppLayout.initializePlacement('DEFAULT');
		placementInterface.prototype.showHelper = async function(params, cb)
		{
			let query = '';
			if (Type.isNumber(params))
			{
				query = `redirect=detail&code=${params}`;
			}
			else if (Type.isStringFilled(params))
			{
				query = params;
			}
			else if (Type.isPlainObject(params))
			{
				for (const param of Object.keys(params))
				{
					if (query.length > 0)
					{
						query += '&';
					}

					query += `${param}=${params[param]}`;
				}
			}

			if (query.length > 0)
			{
				await Runtime.loadExtension('helper');
				const Helper = Reflection.getClass('BX.Helper');
				Helper.show(query);
			}
		};
	}
}

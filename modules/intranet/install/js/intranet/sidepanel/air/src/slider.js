import { Reflection, Dom, Event, Type, Browser, ZIndexManager } from 'main.core';
import { Slider as BaseSlider, SidePanel, type OuterBoundary, type SliderOptions } from 'main.sidepanel';
import { ChatMenuBar } from './chat-menu-bar';
import { type ThemePicker } from 'intranet.theme-picker';

const MENU_COLLAPSED_WIDTH = 65;
const MENU_EXPANDED_WIDTH = 240;

export class Slider extends BaseSlider
{
	#onWindowResize: Function = null;
	#chatMenuBar: ChatMenuBar = null;
	static #verticalScrollWidth: number = null;

	constructor(url: string, sliderOptions: SliderOptions)
	{
		const options: SliderOptions = Type.isPlainObject(sliderOptions) ? { ...sliderOptions } : {};
		const isMessenger = url.startsWith('im:slider');

		if (isMessenger)
		{
			options.hideControls = false;
			options.autoOffset = false;
		}

		super(url, options);

		this.#chatMenuBar = isMessenger ? new ChatMenuBar(this) : null;
		this.#onWindowResize = this.#handleWindowResize.bind(this);
	}

	applyHacks(): void
	{
		Slider.#verticalScrollWidth = window.innerWidth - document.documentElement.clientWidth;

		this.adjustBackgroundSize();
		Event.bind(window, 'resize', this.#onWindowResize);

		if (this.getRightBar() && !this.isMessengerSlider())
		{
			const stack = ZIndexManager.getOrAddStack(document.body);
			stack.register(this.getRightBar());
			Dom.addClass(this.getRightBar(), '--ui-context-edge-dark --overlay-mode');
		}

		return true;
	}

	resetHacks(): void
	{
		this.resetBackgroundSize();
		Event.unbind(window, 'resize', this.#onWindowResize);

		if (this.getRightBar())
		{
			const stack = ZIndexManager.getOrAddStack(document.body);
			stack.unregister(this.getRightBar());
			Dom.removeClass(this.getRightBar(), '--ui-context-edge-dark --overlay-mode');
			Dom.style(this.getRightBar(), 'z-index', null); // ZIndexManager may not remove z-index, so we do it manually
		}
	}

	open(): boolean
	{
		const opened = super.open();
		if (this.getRightBar() && opened)
		{
			const stack = ZIndexManager.getOrAddStack(document.body);
			if (!this.isMessengerSlider() && !Slider.isMessengerOpen() && !Slider.isVideoCallOpen())
			{
				stack.bringToFront(this.getRightBar());
			}
		}

		return opened;
	}

	static isMessengerOpen(): boolean
	{
		const MessengerSlider = Reflection.getClass('BX.Messenger.v2.Lib.MessengerSlider');
		if (MessengerSlider && MessengerSlider.getInstance().isOpened())
		{
			return true;
		}

		return Slider.isMessengerEmbedded();
	}

	static isMessengerEmbedded(): boolean
	{
		const LayoutManager = Reflection.getClass('BX.Messenger.v2.Lib.LayoutManager');

		return LayoutManager && LayoutManager.getInstance().isEmbeddedMode();
	}

	static isMessengerOpenBeforeSlider(slider: Slider): boolean
	{
		if (Slider.isMessengerEmbedded())
		{
			return true;
		}

		const sliders = SidePanel.Instance.getOpenSliders();
		for (const openSlider of sliders)
		{
			if (openSlider === slider)
			{
				return false;
			}

			if (openSlider?.isMessengerSlider())
			{
				return true;
			}
		}

		return false;
	}

	isMessengerSlider(): boolean
	{
		return this.#chatMenuBar !== null;
	}

	static isVideoCallOpen(): boolean
	{
		const CallManager = Reflection.getClass('BX.Messenger.v2.Lib.CallManager');

		return CallManager && CallManager.getInstance().hasCurrentCall();
	}

	getRightBar(): HTMLElement
	{
		return document.getElementById('right-bar');
	}

	getLeftBoundary(): number
	{
		const windowWidth = Browser.isMobile() ? window.innerWidth : document.documentElement.clientWidth;
		if (windowWidth < 1260)
		{
			return this.getMinLeftBoundary();
		}

		const LeftMenu = Reflection.getClass('BX.Intranet.LeftMenu');

		return (
			LeftMenu?.isCollapsed() || this.isMessengerSlider()
				? MENU_COLLAPSED_WIDTH
				: MENU_EXPANDED_WIDTH
		);
	}

	getRightBoundary(): number
	{
		return 0;
	}

	getTopBoundary(): number
	{
		return 0;
	}

	calculateOuterBoundary(): OuterBoundary
	{
		if (this.isMessengerSlider() || Slider.isMessengerOpenBeforeSlider(this) || Slider.isVideoCallOpen())
		{
			return {
				top: this.isMessengerSlider() ? 58 : 16,
				right: 18,
			};
		}

		const rightBarWidth = this.getRightBar()?.offsetWidth || 0;
		const rightMargin = Slider.#verticalScrollWidth + rightBarWidth;

		return {
			top: 16,
			right: rightMargin,
		};
	}

	adjustBackgroundSize(): void
	{
		const themePicker: ThemePicker = Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
		if (!themePicker)
		{
			return;
		}

		const theme = themePicker.getAppliedTheme();
		if (theme && theme.resizable === true)
		{
			if (theme.video)
			{
				this.adjustVideoSize();
			}
			else if (theme.width > 0 && theme.height > 0)
			{
				this.adjustImageSize(theme.width, theme.height);
			}
		}
	}

	adjustImageSize(imgWidth: number, imgHeight: number): void
	{
		const containerWidth = document.documentElement.clientWidth;
		const containerHeight = document.documentElement.clientHeight;

		const imgRatio = imgHeight / imgWidth;
		const containerRatio = containerHeight / containerWidth;
		const width = containerRatio > imgRatio ? containerHeight / imgRatio : containerWidth;
		const height = containerRatio > imgRatio ? containerHeight : containerWidth * imgRatio;

		Dom.style(document.body, '--air-theme-bg-size', `${width}px ${height}px`);
	}

	adjustVideoSize(): void
	{
		const themePicker: ThemePicker = Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
		if (!themePicker)
		{
			return;
		}

		const videoContainer: HTMLElement = themePicker.getVideoContainer();
		if (videoContainer)
		{
			Dom.style(videoContainer, 'right', `${window.innerWidth - document.documentElement.clientWidth}px`);
		}
	}

	resetBackgroundSize(): void
	{
		Dom.style(document.body, '--air-theme-bg-size', null);

		const themePicker: ThemePicker = Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
		if (themePicker)
		{
			const videoContainer: HTMLElement = themePicker.getVideoContainer();
			if (videoContainer)
			{
				Dom.style(videoContainer, 'right', null);
			}
		}
	}

	#handleWindowResize(): void
	{
		this.adjustBackgroundSize();
	}
}

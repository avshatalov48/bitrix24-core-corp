import { Dom } from 'main.core';
import type { BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';

export class SidePanelIntegration
{
	#popup: Popup;
	#sliders: Set = new Set();
	#frozen: boolean = false;
	#frozenProps: {
		autoHide: boolean,
		closeByEsc: boolean,
	};

	constructor(popup: Popup)
	{
		this.#popup = popup;

		this.#popup.subscribe('onShow', this.handlePopupShow.bind(this));
		this.#popup.subscribe('onClose', this.handlePopupClose.bind(this));
		this.#popup.subscribe('onDestroy', this.handlePopupClose.bind(this));

		this.handleSliderOpen = this.handleSliderOpen.bind(this);
		this.handleSliderClose = this.handleSliderClose.bind(this);
		this.handleSliderDestroy = this.handleSliderDestroy.bind(this);
	}

	handlePopupShow(): void
	{
		this.bindEvents();
	}

	handlePopupClose(): void
	{
		this.#sliders.clear();
		this.unbindEvents();
		this.unfreeze();
	}

	bindEvents(): void
	{
		this.unbindEvents();

		if (top.BX)
		{
			top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onOpen', this.handleSliderOpen);
			top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose);
			top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
		}
	}

	unbindEvents(): void
	{
		if (top.BX)
		{
			top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onOpen', this.handleSliderOpen);
			top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose);
			top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
		}
	}

	handleSliderOpen(event: BaseEvent): void
	{
		const [sliderEvent] = event.getData();
		const slider = sliderEvent.getSlider();

		if (!this.isPopupInSlider(slider))
		{
			this.#sliders.add(slider);
			this.freeze();
		}
	}

	handleSliderClose(event: BaseEvent): void
	{
		const [sliderEvent] = event.getData();
		const slider = sliderEvent.getSlider();

		this.#sliders.delete(slider);
		if (this.#sliders.size === 0)
		{
			this.unfreeze();
		}
	}

	handleSliderDestroy(event: BaseEvent): void
	{
		const [sliderEvent] = event.getData();
		const slider = sliderEvent.getSlider();

		if (this.isPopupInSlider(slider))
		{
			this.unbindEvents();
			this.#popup.destroy();
		}
		else
		{
			this.#sliders.delete(slider);
			if (this.#sliders.size === 0)
			{
				this.unfreeze();
			}
		}
	}

	isPopupInSlider(slider): boolean
	{
		if (slider.getFrameWindow())
		{
			return slider.getFrameWindow().document.contains(this.#popup.getPopupContainer());
		}
		else
		{
			return slider.getContainer().contains(this.#popup.getPopupContainer());
		}
	}

	freeze(): void
	{
		if (this.#frozen)
		{
			return;
		}

		this.#frozenProps = {
			autoHide: this.#popup.autoHide,
			closeByEsc: this.#popup.closeByEsc,
		};

		this.#popup.setAutoHide(false);
		this.#popup.setClosingByEsc(false);
		Dom.style(this.#popup.getPopupContainer(), 'pointer-events', 'none');

		this.#frozen = true;
	}

	unfreeze(): void
	{
		if (!this.#frozen)
		{
			return;
		}

		this.#popup.setAutoHide(this.#frozenProps.autoHide !== false);
		this.#popup.setClosingByEsc(this.#frozenProps.closeByEsc !== false);
		Dom.style(this.#popup.getPopupContainer(), 'pointer-events', '');

		this.#frozen = false;
	}
}
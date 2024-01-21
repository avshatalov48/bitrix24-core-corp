import { Type, Loc } from 'main.core';
import './done.css';

export class Done
{
	#showSlider(Slider: function) {
		const topSlider = BX?.SidePanel?.Instance?.getTopSlider();
		const width = topSlider?.getWidth();
		const sliderWidth = width
			? Math.floor(width * 0.86)
			: Math.floor(window.screen.width * 0.86);

		const opts = {
			content: this.#renderContent,
			url: 'crm:copilot-wrapper-slider-done',
			width: sliderWidth,
		};

		const slider = new Slider(opts);
		slider.open();
	}

	#renderContent(): string {
		const message = Loc.getMessage('CRM_COPILOT_WRAPPER_ALL_IS_DONE');

		return `
			<div class="crm-copilot-wrapper-slider-done">
				<div class="crm-copilot-wrapper-slider-done__message">
					<div class="crm-copilot-wrapper-slider-done__message-icon"></div>
					<div class="crm-copilot-wrapper-slider-done__message-text">${message}</div>
				</div> 
			</div>
		`;
	}

	start() {
		if (BX.Crm && Type.isFunction(BX.Crm.AI.Slider))
		{
			this.#showSlider(BX.Crm.AI.Slider);
		}
		else
		{
			top.BX.Runtime.loadExtension('crm.ai.slider')
				.then((exports) => {
					const { Slider } = exports;
					this.#showSlider(Slider);
				})
				.catch((err) => {
					throw new Error('Cant load Crm.AI.Slider extension');
				});
		}
	}
}

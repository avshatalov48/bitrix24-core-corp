import { AnglePosition, PromoVideoPopup, PromoVideoPopupEvents } from 'ui.promo-video-popup';
import { CopilotPromoPopup } from 'ai.copilot-promo-popup';
import { ajax } from 'main.core';
import { BannerDispatcher } from 'ui.banner-dispatcher';

type Params = {
	promotionType: string,
};

export class TasksAiPromo
{
	#params: Params;

	constructor(params: Params)
	{
		this.#params = params;
	}

	show()
	{
		BannerDispatcher.normal.toQueue((onDone) => {
			const promoPopup = this.#getPopup();

			if (!promoPopup)
			{
				onDone();

				return;
			}

			promoPopup.subscribe(PromoVideoPopupEvents.HIDE, this.onCopilotPromoHide.bind(this, onDone));
			promoPopup.show();
		});
	}

	#getPopup(): ?PromoVideoPopup
	{
		const copilotButton = document.querySelector('#mpf-copilot-task-form');

		if (!copilotButton)
		{
			return null;
		}

		return CopilotPromoPopup.createByPresetId({
			presetId: CopilotPromoPopup.Preset.TASK,
			targetOptions: copilotButton,
			offset: {
				left: 85,
				top: -150,
			},
			angleOptions: {
				position: AnglePosition.LEFT,
				offset: 122,
			},
		});
	}

	onCopilotPromoHide(onDone: Function)
	{
		ajax.runAction('tasks.promotion.setViewed', { data: { promotion: this.#params.promotionType } })
			.catch((err) => {
				console.error(err);
			})
		;

		onDone();
	}
}

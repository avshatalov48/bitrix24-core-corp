import { Loc, Tag } from 'main.core';
import { TransferToIntranetPopup } from '../transfer-to-intranet-popup';
import type { TransferToIntranetPopupType } from '../transfer-to-intranet-popup';
import { Lottie } from 'ui.lottie';
import GuestAnimationData from './../lottie-animations/guest.json';
import ExtranetAnimationData from './../lottie-animations/extranet.json';

export class ProcessingStep
{
	#options: TransferToIntranetPopupType;
	#content: HTMLElement;
	#departmentIds: Array;
	#parent: TransferToIntranetPopup;

	constructor(options: TransferToIntranetPopupType, departmentIds, parent: TransferToIntranetPopup)
	{
		this.#options = options;
		this.#departmentIds = departmentIds;
		this.#parent = parent;
	}

	send(): void
	{
		const start = Date.now();

		const data = {
			departmentId: this.#departmentIds,
			isEmail: 'N',
		};

		BX.ajax.runComponentAction(this.#options.componentName, 'moveToIntranet', {
			signedParameters: this.#options.signedParameters,
			mode: 'ajax',
			data,
		}).then((response) => {
			const time = (Date.now() - start);
			let delay = 0;
			if (time <= 2500)
			{
				delay = 2500 - time;
			}

			setTimeout(() => {
				this.#parent.emit('changestate', {
					success: true,
					response,
				});
			}, delay);
		}, (response) => {
			this.#parent.emit('changestate', {
				success: false,
				response,
			});
		});
	}

	render(): HTMLElement
	{
		if (!this.#content)
		{
			this.#content = this.#renderBlock();
		}

		return this.#content;
	}

	#renderBlock(): HTMLElement
	{
		return Tag.render`
			<div class="transfer-processing">
				${this.renderLottieAnimation()}
				<div class="transfer-processing__content">
					<div class="transfer-processing__title">
						${Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_PROCESSING_TITLE')}
					</div>
					<div class="transfer-processing__description">
						${Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_PROCESSING_DESCRIPTION')}
					</div>
				</div>
			</div>
		`;
	}

	renderLottieAnimation(): HTMLElement
	{
		const container = Tag.render`<div class="transfer-processing__animate"></div>`;

		const mainAnimation = Lottie.loadAnimation({
			container,
			renderer: 'svg',
			loop: true,
			autoplay: true,
			animationData: this.#options.userType === 'collaber' ? GuestAnimationData : ExtranetAnimationData,
		});

		mainAnimation.setSpeed(0.75);

		return container;
	}
}

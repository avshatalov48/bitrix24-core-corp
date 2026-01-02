import { Browser, Loc, Tag, Dom } from 'main.core';
import { Button } from 'ui.buttons';
import { AvatarRound, AvatarRoundExtranet, AvatarRoundGuest, AvatarBase } from 'ui.avatar';
import { TransferToIntranetPopup } from '../transfer-to-intranet-popup';
import type { TransferToIntranetPopupType } from '../transfer-to-intranet-popup';
import { Confetti } from 'ui.confetti';

export class ResultStep
{
	#options: TransferToIntranetPopupType;
	#content: HTMLElement;
	#isSuccess: boolean;
	#parent: TransferToIntranetPopup;

	constructor(options: TransferToIntranetPopupType, isSuccess: boolean, parent: TransferToIntranetPopup)
	{
		this.#options = options;
		this.#isSuccess = isSuccess;
		this.#parent = parent;
	}

	render(): HTMLElement
	{
		if (!this.#content)
		{
			this.#content = this.#renderBlock();
		}
		this.#setAnimation();

		return this.#content;
	}

	#setAnimation(): void
	{
		if (!this.#isSuccess)
		{
			return;
		}

		const container = this.#content.querySelector('.transfer-result__img');
		if (container.classList.contains('transfer-result__animation'))
		{
			container.classList.remove('transfer-result__animation');
		}
		setTimeout(() => {
			container.classList.add('transfer-result__animation');
		}, 500);

		setTimeout(() => {
			this.#setConfetti();
		}, 2000);
	}

	#setConfetti(): void
	{
		if (Browser.isFirefox())
		{
			const canvas = Tag.render`
				<canvas></canvas>
			`;
			Dom.style(canvas, 'position', 'fixed');
			Dom.style(canvas, 'top', '0px');
			Dom.style(canvas, 'left', '0px');
			Dom.style(canvas, 'pointer-events', 'none');
			Dom.style(canvas, 'z-index', '11111');
			Dom.style(canvas, 'width', '100%');
			Dom.style(canvas, 'height', '100%');
			Dom.append(canvas, document.body);
			const confetti = Confetti.create(canvas, {
				resize: true,
				useWorker: true,
			});
			confetti({
				particleCount: 250,
				origin: { y: 0.5, x: 0.33 },
				spread: 100,
			});
			confetti({
				particleCount: 250,
				origin: { y: 0.5, x: 0.66 },
				spread: 100,
			});
		}
		else
		{
			Confetti.fire({
				particleCount: 250,
				spread: 100,
				origin: { y: 0.5, x: 0.33 },
				zIndex: 111_111,
			});
			Confetti.fire({
				particleCount: 250,
				spread: 100,
				origin: { y: 0.5, x: 0.66 },
				zIndex: 111_111,
			});
		}
	}

	#renderBlock(): HTMLElement
	{
		const title = this.#isSuccess
			? Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_RESULT_TITLE')
			: Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_RESULT_TITLE_ERROR');

		const description = this.#isSuccess
			? Loc.getMessage(
				'INTRANET_EXTRANET_TO_INTRANET_POPUP_RESULT_DESCRIPTION',
				{
					'[USER_NAME]': `<b>${this.#options.userName}</b>`,
				},
			)
			: Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_RESULT_DESCRIPTION_ERROR');

		return Tag.render`
			<div class="transfer-result">
				${this.#getImageBlock()}
				<div class="transfer-result__content">
					<div class="transfer-result__title">
						${title}
					</div>
					<div class="transfer-result__description">
						${description}
					</div>
				</div>
				<div class="transfer-result__action">
					${this.#getCloseButton()}
				</div>
			</div>
		`;
	}

	#getImageBlock(): HTMLElement
	{
		if (this.#isSuccess)
		{
			return Tag.render`
				<div class="transfer-result__img">
					<div class="transfer-result__img-check-sign check-sign-part1"></div>
					<div class="transfer-result__img-check-sign check-sign-part2"></div>
				</div>
			`;
		}
		else
		{
			return Tag.render`
				<div class="transfer-result__avatar-result">
					${this.#getAvatar().getContainer()}
				</div>
			`;
		}
	}

	#getAvatar(): AvatarBase
	{
		const options = {
			size: 102,
			userpicPath: this.#options.userPhoto,
		};
		let avatar = null;

		switch (this.#options.userType)
		{
			case 'extranet':
				avatar = new AvatarRoundExtranet(options);
				break;
			case 'collaber':
				avatar = new AvatarRoundGuest(options);
				break;
			default:
				avatar = new AvatarRound(options);
				break;
		}

		return avatar;
	}

	#getCloseButton(): HTMLElement
	{
		return new Button({
			className: 'transfer-result__action-close',
			text: Loc.getMessage('INTRANET_EXTRANET_TO_INTRANET_POPUP_RESULT_CLOSE'),
			size: Button.Size.LARGE,
			style: '--style-filled',
			useAirDesign: true,
			noCaps: true,
			isDependOnTheme: true,
			onclick: () => {
				this.#parent.emit('changestate');
			},
		}).render();
	}
}

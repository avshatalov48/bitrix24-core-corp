import { Dom, Tag, Type, Loc } from 'main.core';
import { Icon, Main, Actions } from 'ui.icon-set.api.core';

import './style.css';
import { Popup } from 'main.popup';
import { Guide } from 'sign.tour';

type UserPartyCountersOption = {
	userCountersLimit: ?Number,
}

export class UserPartyCounters
{
	#userCountersLimit: ?Number = null;
	#container: ?HTMLDivElement = null;
	#counterNode: ?HTMLSpanElement = null;
	#isShowLimitPopup: Boolean = false;
	#incrementTariffLinkContainer: ?HTMLAnchorElement = null;

	constructor(options: UserPartyCountersOption)
	{
		this.#userCountersLimit = options.userCountersLimit;
		this.#counterNode = Tag.render`<span class="sign-b2e-settings__user-party-counter-select">0</span>`;
		this.#container = Tag.render`
			<div class="sign-b2e-settings__user-party-counter">
				${this.#getIcon().render()}
				<div class="sign-b2e-settings__user-party-counter_limit-block">
					${this.#counterNode}
					${this.#getLimitContainer()}
				</div>
			</div>
		`;
	}

	getLayout(): HTMLElement
	{
		return this.#container;
	}

	#getIcon(): Icon
	{
		return new Icon({
			icon: Main.PERSONS_2,
			size: 18,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-60'),
		});
	}

	#getLimitContainer(): HTMLElement | null
	{
		if (!this.#userCountersLimit)
		{
			return null;
		}

		return Tag.render`<span class="sign-b2e-settings__user-party-counter-limit">/ ${Number(this.#userCountersLimit)}</span>`;
	}

	getCount(): number
	{
		return Number(this.#counterNode.textContent);
	}

	update(size: number): void
	{
		this.#counterNode.textContent = size;
		if (!Type.isNumber(this.#userCountersLimit))
		{
			return;
		}

		if (size > this.#userCountersLimit)
		{
			if (!Dom.hasClass(this.#container, '--alert'))
			{
				if (!this.#incrementTariffLinkContainer)
				{
					Dom.append(this.#getContainerForIncrementTariff(), this.#container);
				}
				Dom.addClass(this.#container, '--alert');
			}

			if (!this.#isShowLimitPopup)
			{
				const guide = new Guide({
					id: 'sign-b2e-tariff-limit-user-party-counter-tour',
					onEvents: true,
					autoSave: false,
					adjustPopupPosition: this.#adjustPopupWithCenterAngle.bind(this),
					steps: [
						{
							target: this.#container,
							title: Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_TITLE'),
							text: Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_MESSAGE'),
							condition: {
								top: true,
								bottom: false,
								color: 'primary',
							},
							link: 'javascript:void(0);',
						},
					],
					popupOptions: {
						width: 450,
						autoHide: false,
					},
				});

				guide.getLink().setAttribute('onclick', "BX.PreventDefault();top.BX.UI.InfoHelper.show('limit_office_e_signature');");
				guide.start();

				this.#isShowLimitPopup = true;
			}
		}
		else
		{
			Dom.removeClass(this.#container, '--alert');
		}
	}

	#adjustPopupWithCenterAngle(popup: Popup): void
	{
		const popupWidth = popup.getWidth();
		const { left: startX } = this.#container.getBoundingClientRect();
		const bindElementCenter = startX + this.#container.offsetWidth / 2;
		const popupCenter = startX + popup.getPopupContainer().offsetWidth / 2;
		const offsetLeft = Popup.getOption('angleLeftOffset') + bindElementCenter - popupCenter;
		popup.setOffset({ offsetLeft });
		popup.adjustPosition();
		const { angleArrowElement } = popup;
		popup.setAngle({ offset: (popupWidth - angleArrowElement.parentElement.offsetWidth) / 2 });
	}

	#getContainerForIncrementTariff(): HTMLElement
	{
		const rightChevron = new Icon({
			icon: Actions.CHEVRON_RIGHT,
			size: 18,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-link-primary-base'),
		});
		this.#incrementTariffLinkContainer = Tag.render`
			<a class="sign-b2e-settings__user-party-counter-increment-tariff" href="javascript:void(0);" onclick="BX.PreventDefault();top.BX.UI.InfoHelper.show('limit_office_e_signature');">
				 -
				 <div class="sign-b2e-settings__user-party-counter-increment-tariff_text">
					${Loc.getMessage('SIGN_V2_B2E_USER_PARTY_COUNTER_LIMIT_INCREMENT_TARIFF')}
				 </div>
				 ${rightChevron.render()}
			</a>
		`;

		return this.#incrementTariffLinkContainer;
	}
}

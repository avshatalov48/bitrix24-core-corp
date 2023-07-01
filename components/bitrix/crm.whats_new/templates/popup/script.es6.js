import { Reflection, Tag, Type } from 'main.core';
import { PopupManager } from 'main.popup';
import { Popup } from "main.popup";

const namespaceCrmWhatsNew = Reflection.namespace('BX.Crm.WhatsNew');

type ContentConfig = {
	innerTitle: string,
	innerSubTitle: string,
	innerDescription: string,
	innerInfo: string,
	innerImage: string,
};

class RichPopup
{
	#popup: Popup;
	#data: ContentConfig;
	#options: Object;
	#userOptionCategory: string;
	#userOptionName: string;

	constructor({ data, options,  userOptionCategory, userOptionName })
	{
		this.#popup = null;
		this.#data = data;
		this.#options = Type.isObject(options) ? options : {};
		this.#userOptionCategory = Type.isString(userOptionCategory) ? userOptionCategory : 'crm';
		this.#userOptionName = Type.isString(userOptionName) ? userOptionName : '';
		if (Type.isNumber(options.entityTypeId) && Type.isStringFilled(this.#userOptionName))
		{
			this.#userOptionName = this.#userOptionName + options.entityTypeId
		}
	}

	show(): void
	{
		const isAnyPopupShown = PopupManager && PopupManager.isAnyPopupShown();
		const isAnySliderShown = BX.SidePanel.Instance.getOpenSlidersCount() > 0;

		if (isAnyPopupShown || isAnySliderShown)
		{
			return;
		}

		if (!this.#popup)
		{
			const htmlStyles = getComputedStyle(document.documentElement);
			const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
			const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
			const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';

			this.#popup = new Popup({
				className: 'crm-rich-popup-wrapper',
				closeIcon: true,
				closeByEsc: true,
				cacheable: false,
				padding: popupPaddingNumberValue,
				overlay: {
					opacity: 40,
					backgroundColor: popupOverlayColor,
				},
				content: this.#getPopupContent(),
				width: 640,
				height: 400,
				events: {
					onPopupClose: () => this.save()
				}
			});

			this.#popup.show();
		}
	}

	#getPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="crm-rich-popup-slide">
				<img src="${this.#data.innerImage}" alt="">
				<div class="crm-rich-popup-slide-inner-title"> ${this.#data.innerTitle} </div>
				<div class="crm-rich-popup-slide-inner-subtitle"> ${this.#data.innerSubTitle} </div>
				<div class="crm-rich-popup-slide-inner-description">${this.#data.innerDescription}</div>
				<div class="crm-rich-popup-slide-inner-info">${this.#data.innerInfo}</div>
			</div>
		`;
	}

	save(): void
	{
		BX.userOptions.save(
			this.#userOptionCategory,
			this.#userOptionName,
			'count',
			this.#options.checkpoint
		);
	}
}

namespaceCrmWhatsNew.RichPopup = RichPopup;

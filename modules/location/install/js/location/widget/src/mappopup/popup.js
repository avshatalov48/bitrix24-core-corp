import {Popup as MainPopup} from 'main.popup';

/**
 * Popup window, which contains map
 */
export default class Popup extends MainPopup
{
	#detailsPopupPadding = 20;

	getBindElement()
	{
		return this.bindElement;
	}

	adjustPosition(bindOptions: {
		forceBindPosition?: boolean,
		forceLeft?: boolean,
		forceTop?: boolean,
		position?: 'right' | 'top' | 'bootom'
	}): void
	{
		if(this.bindOptions.position && this.bindOptions.position === 'right')
		{
			const itemRect = this.bindElement.getBoundingClientRect();

			const offsetLeft = itemRect.width;
			let offsetTop = itemRect.height / 2 + this.#detailsPopupPadding;
			let angleOffset = itemRect.height / 2;

			const popupWidth = this.getPopupContainer().offsetWidth;
			const popupHeight = this.getPopupContainer().offsetHeight;
			const popupBottom = itemRect.top + popupHeight;

			const clientWidth = document.documentElement.clientWidth;
			const clientHeight = document.documentElement.clientHeight;

			// let's try to fit a this to the browser viewport
			const exceeded = popupBottom - clientHeight;
			if(exceeded > 0)
			{
				let roundOffset = Math.ceil(exceeded / itemRect.height) * itemRect.height;
				if(roundOffset > itemRect.top)
				{
					// it cannot be higher than the browser viewport.
					roundOffset -= Math.ceil((roundOffset - itemRect.top) / itemRect.height) * itemRect.height;
				}

				if(itemRect.bottom > (popupBottom - roundOffset))
				{
					// let's sync bottom boundaries.
					roundOffset -= itemRect.bottom - (popupBottom - roundOffset) + this.#detailsPopupPadding;
				}

				offsetTop += roundOffset;
				angleOffset += roundOffset + this.#detailsPopupPadding;
			}

			if((itemRect.left + offsetLeft + popupWidth) <= clientWidth)
			{
				this.setOffset({offsetLeft: offsetLeft, offsetTop: -offsetTop});
				this.setAngle({position: 'left', offset: angleOffset});
			}
			else
			{
				this.setAngle(true);
			}
		}

		super.adjustPosition(bindOptions);
	}
}
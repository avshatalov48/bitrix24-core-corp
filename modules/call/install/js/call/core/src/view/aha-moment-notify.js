import { Dom, Loc } from 'main.core';
import { Popup } from 'main.popup';
import { PromoManager } from 'im.v2.lib.promo';

import '../css/aha-moment-notify.css';

const SPOTLIGHT_ID_PREFIX = 'bx-call-aha-moment-notify_spotlight';

export class AhaMomentNotify {
	constructor(config)
	{
		this.popup = null;
		this.id = config.id || Math.random().toString(16).slice(2);
		this.notifyText = config.notifyText || '';
		this.notifyTitle = config.notifyTitle || '';
		this.promoId = config.promoId || '';
		this.popupTemplate = null;
		this.bindElement = config.bindElement;
		this.notifyColor = '#22272B';
		this.spotlight = null;

		this.callbacks = {
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		}
	}

	getPopupTemplate()
	{
		if (this.popupTemplate)
		{
			return;
		}			
		
		this.popupTemplate = Dom.create('div', {
			props: {className: 'bx-call-aha-moment-notify__content'},
			children: [
				Dom.create("div", {
					props: {className: 'bx-call-aha-moment-line-top'},
				}),
				Dom.create("div", {
					props: {className: 'bx-call-aha-moment-notify__title'},
					text: this.notifyTitle,
				}),
				Dom.create("div", {
					props: {className: 'bx-call-aha-moment-notify__message'},
					text: this.notifyText,
				}),
				Dom.create("span", {
					props: {className: 'popup-window-close-icon'},
					events: {
						click: () => {
							if (this.promoId)
							{
								PromoManager.getInstance().markAsWatched(this.promoId);
							}
							this.close();
						},
					},
				}),
			]
		});
	}
	
	getSpotlight()
	{
		if (!this.bindElement)
		{
			return;
		}
		
		if (this.spotlight)
		{
			this.spotlight.close();
			this.spotlight = null;
		}		
		
		const id = `${SPOTLIGHT_ID_PREFIX}_${this.id}`;

		this.spotlight = new BX.SpotLight({
			id,
			targetElement: this.bindElement,
			targetVertex: 'middle-center',
			lightMode: true,
			zIndex: 1500,
		});		

		return this.spotlight;
	}

	create()
	{
		const self = this;

		this.getPopupTemplate();

		if (!this.bindElement || !this.popupTemplate || this.popup)
		{
			return;
		}

		this.popup = new Popup({
			className : 'bx-call-aha-moment-notify',
			bindElement: this.bindElement,
			targetContainer: document.body,
			content: this.popupTemplate,
			bindOptions: {
				position: 'bottom',
				forceBindPosition: true,
			},
			offsetTop: 23,
			background: this.notifyColor,
			contentBackground: this.notifyColor,
			darkMode: true,
			animation: 'fading',
			autoHide: false,
			events: {
				onPopupClose: function ()
				{
					self.callbacks.onClose();
					if (self.spotlight)
					{
						self.spotlight.close();
					}
					this.destroy();
				},
				onPopupDestroy: function ()
				{
					self.popup = null;
				},
				onShow: () => {
					const popupWidth = this.popup.getPopupContainer().offsetWidth;
					const elementWidth = this.popup.bindElement.offsetWidth;
					const offsetFix = 0;

					this.popup.setOffset({
						offsetLeft: elementWidth - offsetFix - popupWidth / 2
					});

					this.popup.setAngle({
						offset: (popupWidth / 2) - 33,
						position: 'top',
					});
					this.popup.adjustPosition();
				},
				onClose: () => {
					if (self.spotlight)
					{
						self.spotlight.close();
					}
				},
			}
		});
	}

	show()
	{
		this.create();

		if (!this.bindElement || !this.popupTemplate)
		{
			return;
		}
		
		if(!this.spotlight)
		{
			this.getSpotlight();
		}
		
		this.spotlight.show();
		this.spotlight.getTargetContainer().hidden = false;

		if (this.popup)
		{
			this.popup.show();
		}
	}

	close()
	{
		if (this.popup)
		{
			this.popup.close();
		}
		
		if (this.spotlight)
		{
			this.spotlight.close();
		}
	}
}

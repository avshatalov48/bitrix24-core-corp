import { Dom, Loc } from 'main.core';
import { Popup } from 'main.popup';
import { PromoManager } from 'im.v2.lib.promo';

import { Analytics } from 'call.lib.analytics';

import '../css/copilot-notify.css'

export const CopilotNotifyType = {
	COPILOT_ENABLED: 'COPILOT_ENABLED',
	COPILOT_DISABLED: 'COPILOT_DISABLED',
	COPILOT_RESULT: 'COPILOT_RESULT',
	AI_UNAVAILABLE_ERROR: 'AI_UNAVAILABLE_ERROR',
	AI_SETTINGS_ERROR: 'AI_SETTINGS_ERROR',
	AI_AGREEMENT_ERROR: 'AI_AGREEMENT_ERROR',
	AI_NOT_ENOUGH_BAAS_ERROR: 'AI_NOT_ENOUGH_BAAS_ERROR',
};

export class CopilotNotify {
	constructor(config)
	{
		this.callId = config.callId || 0;
		this.type = config.type || '';
		this.popup = null;
		this.notifyText = '';
		this.promoId = '';
		this.popupTemplate = null;
		this.bindElement = config.bindElement;
		this.notifyColor = '';

		this.callbacks = {
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		}
	}

	getPopupTemplate()
	{
		this.setAdditionalInformation();

		if (!this.notifyText)
		{
			this.popupTemplate = null;
		}

		this.popupTemplate = Dom.create("div", {
			props: {className: 'bx-call-copilot-notify__content'},
			children: [
				Dom.create("div", {
					props: {className: 'bx-call-copilot-notify__icon'},
				}),
				Dom.create("div", {
					props: {className: 'bx-call-copilot-notify__message'},
					text: this.notifyText,
				}),
				Dom.create("button", {
					props: {className: 'bx-call-copilot-notify__close-btn'},
					events: {
						click: () => {
							this.close();
						},
					}
				})
			]
		});
	}

	setAdditionalInformation()
	{
		switch (this.type)
		{
			case CopilotNotifyType.COPILOT_ENABLED:
				this.notifyText = BX.message('CALL_POPUP_WARNING_ENABLED');
				this.promoId = 'call:copilot-notify-warning:21112024:all';
				this.notifyColor = '#8E52EC';
				break;
			case CopilotNotifyType.COPILOT_DISABLED:
				this.notifyText = BX.message('CALL_POPUP_PROMO_ENABLED');
				this.promoId = 'call:copilot-notify-promo:21112024:all';
				this.notifyColor = '#8E52EC';
				break;
			case CopilotNotifyType.COPILOT_RESULT:
				this.notifyText = BX.message('CALL_POPUP_RESULT_WARNING');
				this.promoId = 'call:copilot-notify-result:24112024:all';
				this.notifyColor = '#8E52EC';
				break;
			case CopilotNotifyType.AI_UNAVAILABLE_ERROR:
				this.notifyText = BX.message('CALL_POPUP_AI_UNAVAILABLE_ERROR');
				this.notifyColor = '#FF5752';
				break;
			case CopilotNotifyType.AI_SETTINGS_ERROR:
				this.notifyText = BX.message('CALL_POPUP_AI_SETTINGS_ERROR');
				this.notifyColor = '#FF5752';
				break;
			case CopilotNotifyType.AI_AGREEMENT_ERROR:
				this.notifyText = BX.message('CALL_POPUP_AI_AGREEMENT_ERROR');
				this.notifyColor = '#FF5752';
				break;
			case CopilotNotifyType.AI_NOT_ENOUGH_BAAS_ERROR:
				this.notifyText = BX.message('CALL_POPUP_AI_NOT_ENOUGH_BAAS_ERROR');
				this.notifyColor = '#FF5752';
				break;
			default:
				this.notifyText = Loc.getMessage('CALL_POPUP_AI_DEFAULT_TEXT');
				this.notifyColor = '#FF5752';
				break;
		}
	}

	create()
	{
		const self = this;

		this.getPopupTemplate();

		if (!this.bindElement || !this.popupTemplate)
		{
			return;
		}

		this.popup = new Popup({
			className : "bx-call-copilot-notify",
			bindElement: this.bindElement,
			targetContainer: document.body,
			content: this.popupTemplate,
			bindOptions: {
				position: "top"
			},
			padding: 0,
			contentBorderRadius: '1024px',
			background: this.notifyColor,
			contentBackground: this.notifyColor,
			darkMode: true,
			contentNoPaddings: true,
			animation: "fading",
			autoHide: true,
			events: {
				onPopupClose: function ()
				{
					self.callbacks.onClose();
					this.destroy();
				},
				onPopupDestroy: function ()
				{
					self.popup = null;
				},
				onShow: () => {
					const popupWidth = this.popup.getPopupContainer().offsetWidth;
					const elementWidth = this.popup.bindElement.offsetWidth;
					const offsetFix = 7;

					this.popup.setOffset({
						offsetLeft: elementWidth - offsetFix - popupWidth / 2
					});

					this.popup.setAngle({
						offset: popupWidth / 2,
						position: 'bottom',
					});
					this.popup.adjustPosition();
				},
				onClose: () => {
					if (!this.promoId)
					{
						return;
					}

					PromoManager.getInstance().markAsWatched(this.promoId);
				},
			}
		});
	}

	show()
	{
		this.close();
		this.create();

		if (!this.canShowCopilotNotify())
		{
			return;
		}

		if (this.type === CopilotNotifyType.COPILOT_ENABLED || this.type === CopilotNotifyType.COPILOT_DISABLED)
		{
			Analytics.getInstance().copilot.onCopilotNotifyShow({
				isCopilotActive: this.type === CopilotNotifyType.COPILOT_ENABLED,
				callId: this.callId,
			});
		}

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
	}

	canShowCopilotNotify()
	{
		if (!this.promoId)
		{
			return true;
		}

		return PromoManager.getInstance().needToShow(this.promoId);
	}
}

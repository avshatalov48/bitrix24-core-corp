import { Dom, Loc } from 'main.core';
import { Popup } from 'main.popup';
import { Analytics } from 'call.lib.analytics';
import '../css/copilot-popup.css';
import Util from '../util';
import { CallAI } from '../call_ai';

export class CopilotPopup
{
	constructor(config)
	{
		this.popup = null;
		this.popupTemplate = null;
		this.isCopilotActive = config.isCopilotActive;
		this.isCopilotFeaturesEnabled = config.isCopilotFeaturesEnabled;
		this.callId = config.callId;

		this.callbacks = {
			updateCopilotState: BX.type.isFunction(config.updateCopilotState) ? config.updateCopilotState : BX.DoNothing,
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		}
	};

	getPopupTemplate()
	{
		const getButton = () => {
			if (this.isCopilotActive)
			{
				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_gray' },
					text: BX.message('CALL_COPILOT_POPUP_BUTTON_DISABLE'),
					events: {
						click: () => {
							this.callbacks.updateCopilotState();
							this.close();
						},
					}
				}
			}

			if (!this.isCopilotFeaturesEnabled)
			{
				this.sendAnalytics('private_coming_soon');

				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_transparent' },
					text: BX.message('CALL_COPILOT_POPUP_BUTTON_COMING_SOON'),
				}
			}

			if (!CallAI.tariffAvailable)
			{
				this.sendAnalytics('tariff_limit');

				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_green' },
					text: BX.message('CALL_COPILOT_POPUP_TARIFF_UP'),
					events: {
						click: () => {
							Util.openArticle(CallAI.helpSlider);
							this.close();
						},
					}
				}
			}

			if (!CallAI.settingsEnabled)
			{
				this.sendAnalytics('error_turnedoff');

				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_transparent' },
					text: Loc.getMessage('CALL_COPILOT_POPUP_SETTINGS_DISABLED'),
				};
			}

			if (!CallAI.agreementAccepted)
			{
				this.sendAnalytics('agreement_limit');

				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_transparent' },
					text: BX.message('CALL_COPILOT_POPUP_CONCERN_NOT_ACCEPTED'),
				}
			}

			if (!CallAI.baasAvailable)
			{
				this.sendAnalytics('baas_limit');

				return {
					props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_green' },
					text: BX.message('CALL_COPILOT_POPUP_BUTTON_BUY_BOOST'),
					events: {
						click: () => {
							Util.openArticle(CallAI.baasPromoSlider);
							this.close();
						},
					}
				}
			}

			return {
				props: { className: 'bx-call-copilot-popup__button bx-call-copilot-popup__button_green' },
				text: BX.message('CALL_COPILOT_POPUP_BUTTON_ENABLE'),
				events: {
					click: () => {
						this.callbacks.updateCopilotState();
						this.close();
					},
				}
			}
		};

		this.popupTemplate = Dom.create("div", {
			props: { className: 'bx-call-copilot-popup__wrapper' },
			children: [
				Dom.create("div", {
					props: { className: 'bx-call-copilot-popup__content' },
					children: [
						Dom.create("div", {
							props: { className: 'bx-call-copilot-popup__title' },
							children: [
								Dom.create("div", {
									props: { className: 'bx-call-copilot-popup__title-icon-background' },
									children: [
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__title-icon-image' },
										}),
									]
								}),
								Dom.create("div", {
									props: { className: 'bx-call-copilot-popup__title-text' },
									text: BX.message('CALL_COPILOT_POPUP_TITLE'),
								}),
							],
						}),
						Dom.create("div", {
							props: { className: 'bx-call-copilot-popup__description' },
							text: BX.message('CALL_COPILOT_POPUP_LIST_TITLE'),
						}),
						Dom.create("ul", {
							props: { className: 'bx-call-copilot-popup__list' },
							children: [
								Dom.create("li", {
									props: { className: 'bx-call-copilot-popup__list-item' },
									children: [
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-icon bx-call-copilot-popup__list-item-icon_ai' },
										}),
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-text' },
											text: BX.message('CALL_COPILOT_POPUP_LIST_ITEM_AGENDA'),
										}),
									]
								}),
								Dom.create("li", {
									props: { className: 'bx-call-copilot-popup__list-item' },
									children: [
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-icon bx-call-copilot-popup__list-item-icon_pen' },
										}),
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-text' },
											text: BX.message('CALL_COPILOT_POPUP_LIST_ITEM_AGREEMENTS_V2'),
										}),
									]
								}),
								Dom.create("li", {
									props: { className: 'bx-call-copilot-popup__list-item' },
									children: [
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-icon bx-call-copilot-popup__list-item-icon_magic-pen' },
										}),
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-text' },
											text: BX.message('CALL_COPILOT_POPUP_LIST_ITEM_GRADE'),
										}),
									]
								}),
								Dom.create("li", {
									props: { className: 'bx-call-copilot-popup__list-item' },
									children: [
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-icon bx-call-copilot-popup__list-item-icon_list' },
										}),
										Dom.create("div", {
											props: { className: 'bx-call-copilot-popup__list-item-text' },
											text: BX.message('CALL_COPILOT_POPUP_LIST_ITEM_TRANSCRIPT_V2'),
										}),
									]
								}),
							]
						}),
					]
				}),
				Dom.create('div', {
					props: { className: 'bx-call-copilot-popup__actions' },
					children: [
						Dom.create('button', getButton()),
					]
				}),
			]
		})
	}

	close()
	{
		if (this.popup)
		{
			this.popup.close();
		}
	}

	create()
	{
		const copilotButton = document.querySelector('.bx-messenger-videocall-panel-background-copilot');
		const self = this;

		if (!copilotButton)
		{
			return;
		}

		this.getPopupTemplate();

		this.popup = new Popup({
			className : "bx-call-copilot-popup",
			bindElement: copilotButton,
			targetContainer: document.body,
			content: this.popupTemplate,
			bindOptions: {
				position: "top"
			},
			autoHide: true,
			closeByEsc: true,
			background: '#190A37',
			contentBackground: '#190A37',
			darkMode: true,
			contentNoPaddings: true,
			animation: "fading",
			width: 364,
			padding: 0,
			angle: {
				offset: 162,
				position: 'bottom',
			},
			offsetLeft: -118,
			contentBorderRadius: '18px',
			events: {
				onPopupClose: function ()
				{
					self.callbacks.onClose();
					this.destroy();
				},
				onPopupDestroy: function ()
				{
					self.popup = null;
				}
			}
		});
	}

	show()
	{
		this.create();
		this.popup.show();
	}

	toggle()
	{
		if (!this.popup)
		{
			this.show();
			return;
		}

		this.close();
	}

	sendAnalytics(popupType)
	{
		Analytics.getInstance().copilot.onAIRestrictionsPopupShow({
			callId: this.callId,
			popupType,
		});
	}
}

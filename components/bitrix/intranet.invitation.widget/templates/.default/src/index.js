import {Reflection, Event} from 'main.core';
import {Vue} from 'ui.vue';
import {Popup} from "main.popup";
import {PopupWrapperComponent} from "./components/popup-wrapper";
import { EventEmitter } from 'main.core.events';

const namespace = Reflection.namespace('BX.Intranet');

class InvitationWidget
{
	#vue: Vue;

	constructor(params)
	{
		this.node = params.wrapper;
		this.isCrurrentUserAdmin = params.isCrurrentUserAdmin === "Y";

		this.renderButton();
	}

	renderButton()
	{
		const InvitationWidgetInstance = this;

		this.#vue = Vue.create({
			el: this.node,
			data()
			{
				return {};
			},
			computed:
			{
				localize(state)
				{
					return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
				}
			},
			methods: {
				togglePopup(e)
				{
					if (InvitationWidgetInstance.popup && InvitationWidgetInstance.popup.isShown())
					{
						return InvitationWidgetInstance.closePopup();
					}
					InvitationWidgetInstance.initPopup(e.target);
				},
			},
			template: `
				<button 
					class="ui-btn ui-btn-round license-btn license-btn-primary" 
					@click="togglePopup"
				>{{ localize.INTRANET_INVITATION_WIDGET_INVITE }}</button>
			`,
		});
	}

	initPopup(bindElement)
	{
		if (this.popup)
		{
			this.popup.destroy();
		}

		this.popup = new B24.PopupBlur({
			autoHide: true,
			autoHideHandler: (event) => {
				if (event.target === this.popup.getPopupContainer() || this.popup.getPopupContainer().contains(event.target))
				{
					return null;
				}

				let result = event;
				const hints = document.querySelectorAll('.bx-invite-hint-warning');

				hints.forEach((element) => {
					if (event.target === element || element.contains(event.target))
					{
						result = null;
					}
				});

				return result;
			},
			closeByEsc: true,
			contentPadding: 0,
			padding: 0,
			minWidth: 350,
			minHeight: 220,
			offsetLeft: -150,
			animation: {
				showClassName: "popup-with-radius-show",
				closeClassName: "popup-with-radius-close",
				closeAnimationType: "animation"
			},
			className: 'popup-with-radius',
			// contentBackground: 'rgba(0,0,0,0)',
			angle: { position: 'top', offset: 235 },
			bindElement: bindElement,
			content: this.renderPopupContent(),
			cachable: false,
			events: {
				onFirstShow: (event) => {
					EventEmitter.subscribe('BX.Main.InterfaceButtons:onMenuShow', () => {
						if (this.popup)
						{
							this.popup.close();
						}
					});
				},
			},
		});

		this.popup.show();
	}

	renderPopupContent()
	{
		const InvitationWidgetInstance = this;

		let content = Vue.create({
			el: document.createElement('div'),
			components: {PopupWrapperComponent},
			data()
			{
				return {
					isCrurrentUserAdmin: InvitationWidgetInstance.isCrurrentUserAdmin,
				};
			},
			computed: {
				localize(state)
				{
					return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
				}
			},
			template: `
				<PopupWrapperComponent
					:isCrurrentUserAdmin="isCrurrentUserAdmin"
				/>`
		});

		return content.$el;
	}

	closePopup()
	{
		if (this.popup)
		{
			this.popup.close();
		}
	}
}

namespace.InvitationWidget = InvitationWidget;

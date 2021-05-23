import {Reflection, Event} from 'main.core';
import {Vue} from 'ui.vue';
import {Popup} from "main.popup";
import {PopupWrapperComponent} from "./components/popup-wrapper";

const namespace = Reflection.namespace('BX.Intranet');

class InvitationWidget
{
	#vue: Vue;

	constructor(params)
	{
		this.node = params.wrapper;
		this.enterTimeout = null;
		this.leaveTimeout = null;
		this.popupLeaveTimeout = null;

		this.renderButton();

		Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:showInvitationSlider', (event) => {
			this.closePopup();
		});
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
				onMouseOver (e)
				{
					clearTimeout(InvitationWidgetInstance.enterTimeout);
					InvitationWidgetInstance.enterTimeout = setTimeout(() =>
						{
							InvitationWidgetInstance.enterTimeout = null;
							InvitationWidgetInstance.initPopup(e.target);
						}, 500
					);
				},
				onMouseOut()
				{
					if (InvitationWidgetInstance.enterTimeout !== null)
					{
						clearTimeout(InvitationWidgetInstance.enterTimeout);
						InvitationWidgetInstance.enterTimeout = null;
						return;
					}

					InvitationWidgetInstance.leaveTimeout = setTimeout(() =>
						{
							InvitationWidgetInstance.closePopup();
						}, 500
					);
				},
				togglePopup()
				{
					if (InvitationWidgetInstance.popup)
					{
						if (InvitationWidgetInstance.popup.isShown())
						{
							InvitationWidgetInstance.closePopup();
						}
						else
						{
							InvitationWidgetInstance.popup.show();
						}
					}
				},
			},
			template: `
				<button 
					class="ui-btn ui-btn-round license-btn license-btn-primary" 
					@mouseover="onMouseOver"
					@mouseout="onMouseOut"
					@click="togglePopup"
				>{{ localize.INTRANET_INVITATION_WIDGET_INVITE }}</button>
			`,
		});
	}

	initPopup(bindElement)
	{
		if (!this.popup)
		{
			this.popup = new Popup({
				autoHide: true,
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
				contentBackground: 'rgba(0,0,0,0)',
				angle: { position: 'top', offset: 235 },
				bindElement: bindElement,
				content: this.renderPopupContent(),
			});

			this.initEvents();
		}

		this.popup.show();
	}

	initEvents()
	{
		this.popup.getPopupContainer().addEventListener('mouseenter', () =>
		{
			clearTimeout(this.enterTimeout);
			clearTimeout(this.leaveTimeout);
			clearTimeout(this.popupLeaveTimeout);
		});

		this.popup.getPopupContainer().addEventListener('mouseleave', (event) =>
		{
			this.popupLeaveTimeout = setTimeout(() => {
				this.closePopup();
			}, 500);

		});
	}

	renderPopupContent()
	{
		let content = Vue.create({
			el: document.createElement('div'),
			components: {PopupWrapperComponent},
			computed: {
				localize(state)
				{
					return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
				}
			},
			template: `<PopupWrapperComponent/>`
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

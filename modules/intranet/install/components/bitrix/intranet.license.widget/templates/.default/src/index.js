import {Reflection, Event} from 'main.core';
import { EventEmitter } from 'main.core.events'
import {Vue} from 'ui.vue';
import {Popup} from "main.popup";
import {PopupWrapperComponent} from "./components/popup-wrapper";

const namespace = Reflection.namespace('BX.Intranet');

class LicenseWidget
{
	#vue: Vue;

	constructor(params)
	{
		this.isFreeLicense = params.isFreeLicense === "Y";
		this.isDemoLicense = params.isDemoLicense === "Y";
		this.isAutoPay = params.isAutoPay === "Y";
		this.isLicenseAlmostExpired = params.isLicenseAlmostExpired === "Y";
		this.isLicenseExpired = params.isLicenseExpired === "Y";
		this.isAlmostLocked = params.isAlmostLocked === "Y";
		this.isExperimentalTemplate = params.isExperimentalTemplate === "Y";
		this.licenseType = params.licenseType;
		this.node = params.wrapper;

		this.renderButton();
	}

	renderButton()
	{
		const LicenceWidgetInstance = this;

		this.#vue = Vue.create({
			el: this.node,
			data()
			{
				return {
					isFreeLicense: LicenceWidgetInstance.isFreeLicense,
					isAutoPay: LicenceWidgetInstance.isAutoPay,
					isDemoLicense: LicenceWidgetInstance.isDemoLicense,
					isLicenseAlmostExpired: LicenceWidgetInstance.isLicenseAlmostExpired,
					isLicenseExpired: LicenceWidgetInstance.isLicenseExpired,
					isAlmostLocked: LicenceWidgetInstance.isAlmostLocked,
					isExperimentalTemplate: LicenceWidgetInstance.isExperimentalTemplate,
				};
			},
			computed:
				{
					localize(state)
					{
						return Vue.getFilteredPhrases("INTRANET_LICENSE_WIDGET_");
					},
					buttonClass()
					{
						let className = "";

						if (this.isFreeLicense && !this.isAlmostLocked)
						{
							className = "ui-btn-icon-tariff license-btn-orange";
						}
						else
						{
							if (this.isLicenseAlmostExpired)
							{
								className = "license-btn-alert-border ui-btn-icon-low-battery";
							}
							else if (this.isLicenseExpired)
							{
								/*if (this.isAutoPay)
								{

								}
								else
								{*/
									className = "license-btn-alert-border license-btn-animate license-btn-animate-forward";
								//}
							}
							else if (this.isAlmostLocked)
							{
								className = "license-btn-alert-border ui-btn-icon-low-battery";
							}
							else
							{
								className = "ui-btn-icon-tariff license-btn-blue-border";

								if (this.isDemoLicense)
								{
									className = "ui-btn-icon-demo license-btn-blue-border";
								}
							}
						}

						return className;
					},
					buttonName()
					{
						let buttonName = BX.message("INTRANET_LICENSE_WIDGET_MY_TARIFF");

						if (this.isFreeLicense)
						{
							buttonName = (this.isExperimentalTemplate ?
								BX.message("INTRANET_LICENSE_WIDGET_START_FREE_TRIAL") :
								BX.message("INTRANET_LICENSE_WIDGET_BUY_TARIFF"));
						}
						else if (this.isDemoLicense)
						{
							buttonName = BX.message("INTRANET_LICENSE_WIDGET_DEMO");
						}

						return buttonName;
					},
				},
			methods: {
				togglePopup(e)
				{
					if (LicenceWidgetInstance.popup && LicenceWidgetInstance.popup.isShown())
					{
						return LicenceWidgetInstance.closePopup();
					}
					LicenceWidgetInstance.initPopup(e.target);
				},
			},
			template: `
				<button
					class="ui-btn ui-btn-round ui-btn-themes license-btn"
					:class="buttonClass"
					@click="togglePopup"
				>
					<span v-if="isLicenseExpired || isAlmostLocked" class="license-btn-icon-battery">
						<span class="license-btn-icon-battery-full">
							<span class="license-btn-icon-battery-inner">
								<span></span>
								<span></span>
								<span></span>
							</span>
						</span>
						<svg class="license-btn-icon-battery-cross" xmlns="http://www.w3.org/2000/svg" width="22" height="18">
							<path fill="#FFF" fill-rule="evenodd" d="M18.567.395c.42.42.42 1.1 0 1.52l-1.04 1.038.704.001a2 2 0 012 2v1.799a1.01 1.01 0 01.116-.007H21a1 1 0 011 1v2.495a1 1 0 01-1 1h-.653l-.116-.006v1.798a2 2 0 01-2 2L5.45 15.032l-2.045 2.045a1.075 1.075 0 11-1.52-1.52L17.047.395c.42-.42 1.1-.42 1.52 0zm-2.583 4.102l-8.991 8.99 10.836.002a1 1 0 00.994-.883l.006-.117v-6.99a1 1 0 00-1-1l-1.845-.002zm-5.031-1.543L9.409 4.498h-6.23a1 1 0 00-.993.884l-.006.116-.001 6.23-1.4 1.398v-.046L.777 4.954a2 2 0 012-2h8.175z"/>
						</svg>
					</span>
					{{ buttonName }}
				</button>
			`,
		});
	}

	initPopup(bindElement)
	{
		if (!this.popup)
		{
			this.popup = new B24.PopupBlur({
				autoHide: true,
				closeByEsc: true,
				contentPadding: 0,
				padding: 0,
				minWidth: 350,
				minHeight: 260,
				animation: {
					showClassName: "popup-with-radius-show",
					closeClassName: "popup-with-radius-close",
					closeAnimationType: "animation"
				},
				offsetLeft: -20,
				className: 'popup-with-radius',
				// contentBackground: 'rgba(0,0,0,0)',
				angle: { position: 'top', offset: 120 },
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
		}

		this.popup.show();
	}

	renderPopupContent()
	{
		const LicenceWidgetInstance = this;

		let content = Vue.create({
			el: document.createElement("div"),
			components: {PopupWrapperComponent},
			data()
			{
				return {
					licenseType: LicenceWidgetInstance.licenseType,
					isExperimentalTemplate: LicenceWidgetInstance.isExperimentalTemplate,
				};
			},
			computed: {
				localize(state)
				{
					return Vue.getFilteredPhrases('INTRANET_LICENSE_WIDGET_');
				}
			},
			template: `
				<PopupWrapperComponent
					:licenseType="licenseType"
					:isExperimentalTemplate="isExperimentalTemplate"
				/>`,
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

namespace.LicenseWidget = LicenseWidget;

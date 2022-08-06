import {Reflection, Loc} from 'main.core';
import { EventEmitter } from 'main.core.events'
import {Vue} from 'ui.vue';
import {BitrixVue} from 'ui.vue3';
import {PopupWrapperComponent} from "./components/popup-wrapper";
import {getExpirationLevel, ExpirationLevel} from "./expiration-options";

const namespace = Reflection.namespace('BX.Intranet');

class LicenseWidget
{
	#vue;

	constructor(params)
	{
		this.isDemo = params.isDemo === "Y";
		this.expirationLevel = params.expirationLevel;
		this.node = params.wrapper;

		this.renderButton();

		setTimeout(() => {
			this.initPopup(document.querySelector('[data-bx-id="liswdgt"]'));
		}, 100);
	}

	renderButton()
	{
		this.#vue = BitrixVue.createApp({
			name: 'LicenseWidget:Button',
			data: () => {
				return {
					isDemo: this.isDemo,
					expirationLevel: this.expirationLevel,
				};
			},
			computed: {
					buttonClass: () => {
						const classNames = [];
						if (this.expirationLevel <= ExpirationLevel.soonExpired)
						{
							classNames.push(this.isDemo ? 'ui-btn-icon-demo' : 'ui-btn-icon-tariff');
							classNames.push((this.expirationLevel & ExpirationLevel.soonExpired)
								? 'license-btn-orange' : 'license-btn-blue-border');
						}
						else
						{
							classNames.push('license-btn-alert-border');
							classNames.push(
								(this.expirationLevel & ExpirationLevel.expired) ?
									'license-btn-animate license-btn-animate-forward' : 'ui-btn-icon-low-battery'
							);
						}
						return classNames.join(' ');
					},
					buttonName()
					{
						if (this.expirationLevel > 1)
						{
							return this.isDemo ? Loc.getMessage('INTRANET_LICENSE_WIDGET_BUY')
								: Loc.getMessage('INTRANET_LICENSE_WIDGET_PROLONG');
						}
						return this.isDemo ? Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE_DEMO') :
							Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE');
					},
				},
			methods: {
				togglePopup: (e) => {
					if (this.popup && this.popup.isShown())
					{
						return this.closePopup();
					}
					this.initPopup(e.target);
				},
			},
			template: `
				<button
					data-bx-id="liswdgt"
					class="ui-btn ui-btn-round ui-btn-themes license-btn"
					:class="buttonClass"
					@click="togglePopup"
				>
					<span v-if="expirationLevel > 1" class="license-btn-icon-battery">
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
		this.#vue.mount(this.node);
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
		const node = document.createElement('div');

		(BitrixVue.createApp({
			name: 'LicenseWidget:PopupWrapper',
			components: {PopupWrapperComponent},
			data: () => {
				return {
					isDemo: this.isDemo,
					expirationLevel: this.expirationLevel,
				};
			},
			template: `<PopupWrapperComponent/>`,
		})).mount(node);
		return node;
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

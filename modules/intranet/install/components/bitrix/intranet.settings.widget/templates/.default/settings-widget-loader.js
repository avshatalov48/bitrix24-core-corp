import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';
import {Dom, Tag, ajax, Runtime} from "main.core";

export class SettingsWidgetLoader
{
	static #instance: SettingsWidgetLoader;
	#popup: PopupComponentsMaker;
	#isBitrix24: boolean = false;
	#isAdmin: boolean = false;
	#isRequisite: boolean = false;

	constructor(params)
	{
		this.#isBitrix24 = params['isBitrix24'];
		this.#isAdmin = params['isAdmin'];
		this.#isRequisite = params['isRequisite'];
	}

	showOnce(node)
	{
		const popup = this.#getWidgetPopup().getPopup();

		popup.setBindElement(node);
		popup.show();

		const popupContainer = popup.getPopupContainer();
		if (popupContainer.getBoundingClientRect().left < 30)
		{
			popupContainer.style.left = '30px';
		}

		(typeof BX.Intranet.SettingsWidget !== 'undefined' ? Promise.resolve(): this.#load())
			.then(() => {
				if (typeof BX.Intranet.SettingsWidget !== 'undefined')
				{
					BX.Intranet.SettingsWidget.bindAndShow(node);
				}
			})
		;
	}

	#getWidgetPopup(): PopupComponentsMaker
	{
		if (this.#popup)
		{
			return this.#popup;
		}

		const popup = new PopupComponentsMaker({
			width: 374,
		});

		const container = popup.getPopup().getPopupContainer();

		Dom.clean(container);
		Dom.addClass(container, 'intranet-settings-widget__container');

		if (this.#isBitrix24 && !this.#isAdmin)
		{
			Dom.append(Tag.render`<div class="intranet-settings-widget__skeleton-not-admin"></div>`, container);
		}
		else if (this.#isBitrix24 && this.#isRequisite)
		{
			Dom.append(Tag.render`<div class="intranet-settings-widget__skeleton"></div>`, container);
		}
		else if (this.#isBitrix24 && !this.#isRequisite)
		{
			Dom.append(Tag.render`<div class="intranet-settings-widget__skeleton-no-requisite"></div>`, container);
		}
		else if (!this.#isBitrix24 && this.#isRequisite)
		{
			Dom.append(Tag.render`<div class="intranet-settings-widget__skeleton-no-holding"></div>`, container);
		}
		else
		{
			Dom.append(Tag.render`<div class="intranet-settings-widget__skeleton-no-requisite-holding"></div>`, container);
		}

		this.#popup = popup;

		return popup;
	}

	#load(): Promise
	{
		return new Promise((resolve) => {
			ajax.runComponentAction(
				'bitrix:intranet.settings.widget',
				'getWidgetComponent',
				{
					mode: 'class',
				},
			).then((response) => {
				return (new Promise((resolve) => {
					const loadCss = response.data.assets ? response.data.assets.css : [];
					const loadJs = response.data.assets ? response.data.assets.js : [];
					BX.load(loadCss, () => {
						BX.loadScript(loadJs, () => {
							Runtime.html(null, response.data.html).then(resolve);
						});
					});
				}));
			}).then(() => {
				if (typeof BX.Intranet.SettingsWidget !== 'undefined')
				{
					setTimeout(() => {
						BX.Intranet.SettingsWidget.bindWidget(this.#getWidgetPopup());
						resolve();
					}, 0);
				}
			})
		});
	}

	static init(options): SettingsWidgetLoader
	{
		if (!this.#instance)
		{
			this.#instance = new this(options);
		}

		return this.#instance;
	}
}
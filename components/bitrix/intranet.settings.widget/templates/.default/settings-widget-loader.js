import { ajax, Runtime } from "main.core";
import { WidgetLoader } from 'intranet.widget-loader';

export class SettingsWidgetLoader
{
	static #instance: SettingsWidgetLoader;
	#widgetLoader: WidgetLoader;
	#isBitrix24: boolean = false;
	#isAdmin: boolean = false;
	#isRequisite: boolean = false;
	#isMainPageAvailable: boolean = false;
	#node: ?HTMLElement = false;

	constructor(params)
	{
		this.#isBitrix24 = params['isBitrix24'];
		this.#isAdmin = params['isAdmin'];
		this.#isRequisite = params['isRequisite'];
		this.#isMainPageAvailable = params['isMainPageAvailable'];
	}

	showOnce(node)
	{
		this.#node = node;
		const popup = this.#getWidgetLoader().getPopup();
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

	#getWidgetLoader(): WidgetLoader
	{
		if (this.#widgetLoader)
		{
			return this.#widgetLoader;
		}

		const widgetLoader = new WidgetLoader({
			id: "bx-settings-header-popup",
			bindElement: this.#node,
			width: 374,
		});

		widgetLoader.addHeaderSkeleton();

		if (this.#isRequisite)
		{
			widgetLoader.addItemSkeleton(22);
		}

		if (this.#isMainPageAvailable)
		{
			widgetLoader.addItemSkeleton(22);
		}

		if (this.#isAdmin)
		{
			widgetLoader.addSplitItemSkeleton(22);
		}

		if (this.#isBitrix24)
		{
			widgetLoader.addItemSkeleton(22);
		}

		widgetLoader.addItemSkeleton(22);
		widgetLoader.addFooterSkeleton();

		this.#widgetLoader = widgetLoader;

		return this.#widgetLoader;
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
						BX.Intranet.SettingsWidget.bindWidget(this.#getWidgetLoader());
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
import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';
import {Dom, Tag, ajax, Runtime} from "main.core";

export class SettingsWidgetLoader
{
	static #instance: SettingsWidgetLoader;
	#popup: PopupComponentsMaker;
	#isBitrix24: boolean = false;
	#isAdmin: boolean = false;
	#isRequisite: boolean = false;
	#isMainPageAvailable: boolean = false;

	constructor(params)
	{
		this.#isBitrix24 = params['isBitrix24'];
		this.#isAdmin = params['isAdmin'];
		this.#isRequisite = params['isRequisite'];
		this.#isMainPageAvailable = params['isMainPageAvailable'];
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
		Dom.addClass(container, 'intranet-widget-skeleton__wrap');

		Dom.append(this.getHeaderSkeleton(), container);

		if (this.#isRequisite)
		{
			Dom.append(this.getItemSkeleton(), container);
		}

		if (this.#isMainPageAvailable)
		{
			Dom.append(this.getItemSkeleton(), container);
		}

		if (this.#isAdmin)
		{
			Dom.append(this.getSplitItemSkeleton(), container);
		}

		if (this.#isBitrix24)
		{
			Dom.append(this.getItemSkeleton(), container);
		}

		Dom.append(this.getItemSkeleton(), container);
		Dom.append(this.getFooterSkeleton(), container);

		this.#popup = popup;

		return popup;
	}

	getHeaderSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__header">
				<div style="max-width: 95px; height: 8px;" class="intranet-widget-skeleton__line"></div>
			</div>
		`;
	}

	getItemSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div class="intranet-widget-skeleton__item">
					<div style="width: 26px; height: 26px; margin-right: 8px;" class="intranet-widget-skeleton__circle"></div>
					<div style="max-width: 130px;" class="intranet-widget-skeleton__line"></div>
					<div style="width: 12px; height: 12px; margin-left: auto;" class="intranet-widget-skeleton__circle"></div>
				</div>
			</div>
		`;
	}

	getSplitItemSkeleton(): HTMLElement
	{
		return  Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div class="intranet-widget-skeleton__item">
					<div style="width: 26px; height: 26px; margin-right: 8px;" class="intranet-widget-skeleton__circle"></div>
					<div style="max-width: 75px;" class="intranet-widget-skeleton__line"></div>
					<div style="width: 12px; height: 12px; margin-left: auto;" class="intranet-widget-skeleton__circle"></div>
				</div>
				<div class="intranet-widget-skeleton__item">
					<div style="width: 26px; height: 26px; margin-right: 8px;" class="intranet-widget-skeleton__circle"></div>
					<div style="max-width: 75px;" class="intranet-widget-skeleton__line"></div>
					<div style="width: 12px; height: 12px; margin-left: auto;" class="intranet-widget-skeleton__circle"></div>
				</div>
			</div>
		`;
	}

	getFooterSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__footer">
				<div style="max-width: 40px;" class="intranet-widget-skeleton__line"></div>
				<div style="max-width: 40px;" class="intranet-widget-skeleton__line"></div>
				<div style="max-width: 40px;" class="intranet-widget-skeleton__line"></div>
			</div>
		`;
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
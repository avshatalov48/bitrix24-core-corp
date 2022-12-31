import {Event, Loc, Type, Dom, Runtime, ajax, Text} from 'main.core';
import {PopupMenuWindow} from 'main.popup';

type PanelItem = {
	ID: number | string,
	NAME: string,
	IS_ACTIVE: boolean,
	COUNTER: number,
	COUNTER_CODE: string,
	URL: string,
	CREATE_URL: string,
	ENABLED: string,
};

type PanelOptions = {
	counter: HTMLSpanElement,
	button: HTMLButtonElement,
	container: HTMLDivElement,
	items: Array<PanelItem>,
	tunnelsUrl: string,
	componentParams: Object,
};

export class Panel extends Event.EventEmitter
{
	static createMenuItem(options: PanelItem)
	{
		const item = {
			id: options.ID,
			html: Text.encode(options.NAME),
			href: options.URL,
		};

		const count = Number.parseInt(options.COUNTER, 10);

		if (Type.isNumber(count) && count > 0)
		{
			const counter = `<span class="main-buttons-item-counter">${options.COUNTER}</span>`;
			item.html = `${item.html} ${counter}`;
		}

		return item;
	}

	constructor(options: PanelOptions)
	{
		super();

		this.button = options.button;
		this.counter = options.counter;
		this.container = options.container;
		this.items = options.items;
		this.tunnelsUrl = options.tunnelsUrl;
		this.componentParams = options.componentParams;
		this.onButtonClick = this.onButtonClick.bind(this);

		Event.bind(this.button, 'click', this.onButtonClick);
	}

	isDropdown()
	{
		return Dom.hasClass(this.button, 'ui-btn-dropdown');
	}

	reload()
	{
		return ajax
			.runComponentAction(
				'bitrix:crm.deal_category.panel',
				'getComponent',
				{
					data: {
						params: this.componentParams,
					},
				},
			)
			.then((response) => {
				const newContainer = Runtime.html(null, response.data.html);

				Dom.replace(this.container, newContainer);
				this.getMenu().destroy();
			});
	}

	onButtonClick(event)
	{
		event.preventDefault();

		if (this.isDropdown())
		{
			this.getMenu().show();
			return;
		}

		this.showTunnelSlider();
	}

	showTunnelSlider()
	{
		// eslint-disable-next-line
		BX.SidePanel.Instance.open(
			this.tunnelsUrl,
			{
				cacheable: false,
				customLeftBoundary: 40,
				allowChangeHistory: false,
				events: {
					onClose: () => {
						this.reload();
						if (window.top.BX.Main && window.top.BX.Main.filterManager)
						{
							const {data} = window.top.BX.Main.filterManager;
							// eslint-disable-next-line
							Object.values(data).forEach(filter => filter._onFindButtonClick());
						}
					},
				},
			},
		);
	}

	getMenu()
	{
		if (!this.menu)
		{
			const menuItems = this.items
				.map(item => Panel.createMenuItem(item));

			menuItems.push({
				delimiter: true,
			});

			menuItems.push({
				id: 'tunnels',
				text: Loc.getMessage('CRM_DEAL_CATEGORY_PANEL_TUNNELS2'),
				onclick: this.showTunnelSlider.bind(this),
			});

			this.menu = new PopupMenuWindow({
				bindElement: this.button,
				items: menuItems,
			});
		}

		return this.menu;
	}
}

import { MenuManager } from 'main.popup';
import { SidePanel } from 'main.sidepanel';
import type { MenuItemOptions } from 'main.popup';

export type Data = {
	dashboards: JSON,
	target: HTMLElement,
	flowId: number,
}

export type Dashboard = {
	id: string,
	title: string,
	url: string,
}

export class BIAnalytics
{
	#id: string;
	#flowId: number;
	#dashboards: Dashboard[];
	#target: HTMLElement;

	constructor(data: Data)
	{
		this.#dashboards = Object.values(data.dashboards);
		this.#target = data.target;
		this.#flowId = Number(data.flowId);

		this.#id = `tasks-flow-bi-analytics-menu_${this.#flowId}`;
	}

	static create(data: Data): BIAnalytics
	{
		return new BIAnalytics(data);
	}

	openMenu(): void
	{
		const popupMenu = MenuManager.create({
			id: this.#id,
			bindElement: this.#target,
			items: this.#getMenuItems(),
			cacheable: false,
		});

		popupMenu.show();
	}

	openFirstDashboard(): void
	{
		const dashboard = this.#dashboards[0];

		if (dashboard)
		{
			SidePanel.Instance.open(dashboard.url);
		}
	}

	#getMenuItems(): MenuItemOptions[]
	{
		const menuItems = [];

		this.#dashboards.forEach((dashboard: Dashboard) => {
			menuItems.push({
				tabId: dashboard.id,
				text: dashboard.title,
				onclick: () => {
					SidePanel.Instance.open(dashboard.url);
				},
			});
		});

		return menuItems;
	}
}

import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { Event, Reflection, Type, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Item, Dialog } from 'ui.entity-selector';

type Props = {
	containerId: string,
	textNodeId: string,
	dashboardId: number,
	marketCollectionUrl: string,
	isMarketInstalled: boolean,
	dashboardUrlParams: Object,
};

class SupersetDashboardSelector
{
	#dialog: Dialog;
	#selectorNode: HTMLElement;
	#textNode: HTMLElement;
	#dashboardId: number;
	#marketCollectionUrl: string;
	#isMarketInstalled: boolean;
	#dashboardUrlParams: Object;

	constructor(props: Props)
	{
		this.#selectorNode = document.getElementById(props.containerId);
		this.#textNode = document.getElementById(props.textNodeId);
		this.#dashboardId = props.dashboardId;
		this.#marketCollectionUrl = props.marketCollectionUrl;
		this.#isMarketInstalled = props.isMarketInstalled;
		this.#dashboardUrlParams = props.dashboardUrlParams;
		this.#initDialog(this.#selectorNode);

		if (this.#selectorNode)
		{
			Event.bind(this.#selectorNode, 'click', this.#handleSearchClick.bind(this));
			EventEmitter.subscribe('BIConnector.DashboardManager:onCopyDashboard', this.#handleCopyDashboard.bind(this));
		}
	}

	#initDialog(node: HTMLElement): Dialog
	{
		if (this.#dialog)
		{
			return this.#dialog;
		}

		this.#dialog = new Dialog({
			id: 'biconnector-superset-dashboard',
			multiple: false,
			targetNode: node,
			offsetTop: 14,
			context: 'biconnector-superset-dashboard',
			entities: [
				{
					id: 'biconnector-superset-dashboard',
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
			enableSearch: true,
			dropdownMode: true,
			showAvatars: true,
			compactView: false,
			dynamicLoad: true,
			clearUnavailableItems: true,
			preselectedItems: [['biconnector-superset-dashboard', this.#dashboardId]],
			events: {
				'Item:onSelect': this.#onSelectItem.bind(this),
			},
		});

		return this.#dialog;
	}

	#onSelectItem(event): Promise
	{
		EventEmitter.emit('BiConnector:DashboardSelector.onSelect');
		const item: Item = event.data.item;
		this.#setTitle(item.getTitle());

		return this.#installDashboard(item)
			.then(() => this.#getDashboardEmbeddedData(item.id))
			.then((response) => {
				if (response.data.dashboard)
				{
					this.#setTitle(response.data.dashboard.title);
					EventEmitter.emit('BiConnector:DashboardSelector.onSelectDataLoaded', {
						item,
						dashboardId: item.id,
						credentials: response.data.dashboard,
					});
				}

				return response;
			})
			.catch((response) => {
				if (response.errors && Type.isStringFilled(response.errors[0]?.message))
				{
					BX.UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
					});
				}
				throw response;
			});
	}

	#installDashboard(item: Item): Promise
	{
		return new Promise((resolve, reject) => {
			const status = item.getCustomData().get('status');
			if (status === 'N')
			{
				DashboardManager.installDashboard(item.id)
					.then((response) => resolve(response))
					.catch((error) => reject(error))
				;

				return;
			}
			resolve();
		});
	}

	#getDashboardEmbeddedData(dashboardId: number): Promise
	{
		return BX.ajax.runAction('biconnector.dashboard.getDashboardEmbeddedData', {
			data: {
				id: dashboardId,
				urlParams: this.#dashboardUrlParams,
			},
		});
	}

	#handleSearchClick()
	{
		this.#dialog.show();
	}

	#setTitle(text: string)
	{
		const title = Text.encode(text);
		this.#textNode.innerHTML = title;
		this.#textNode.title = title;
		document.title = title;
	}

	#handleCopyDashboard(event): Promise
	{
		const dashboard = event.data.dashboard;

		return new Promise((resolve) => {
			this.#setTitle(dashboard.title);
			this.#dialog = null;
			this.#dashboardId = dashboard.id;
			this.#initDialog(this.#selectorNode);
			EventEmitter.emit('BiConnector:DashboardSelector.onSelectDataLoaded', {
				item: dashboard,
				dashboardId: dashboard.id,
				credentials: dashboard,
			});
			resolve();
		});
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardSelector = SupersetDashboardSelector;

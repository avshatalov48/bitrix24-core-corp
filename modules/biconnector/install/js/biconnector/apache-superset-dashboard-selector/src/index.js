import { Event, Loc, Reflection, Tag, Type, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';

type Props = {
	containerId: string,
	textNodeId: string,
	dashboardId: number,
	marketCollectionUrl: string,
};

class SupersetDashboardSelector
{
	#dialog: Dialog;
	#selectorNode: HTMLElement;
	#textNode: HTMLElement;
	#dashboardId: number;
	#marketCollectionUrl: string;

	constructor(props: Props)
	{
		this.#selectorNode = document.getElementById(props.containerId);
		this.#textNode = document.getElementById(props.textNodeId);
		this.#dashboardId = props.dashboardId;
		this.#marketCollectionUrl = props.marketCollectionUrl;
		this.#initDialog(this.#selectorNode);

		if (this.#selectorNode)
		{
			Event.bind(this.#selectorNode, 'click', this.#handleSearchClick.bind(this));
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
			footer: [
				this.#getFooter(),
			],
			enableSearch: true,
			dropdownMode: true,
			showAvatars: true,
			compactView: false,
			dynamicLoad: true,
			preselectedItems: [['biconnector-superset-dashboard', this.#dashboardId]],
			events: {
				'Item:onSelect': this.#onSelectItem.bind(this),
			},
		});

		return this.#dialog;
	}

	#onSelectItem(event): Promise
	{
		return this.getDashboardEmbeddedData(event.data.item.id)
			.then((response) => {
				if (response.data.dashboard)
				{
					this.#setTitle(response.data.dashboard.title);
					EventEmitter.emit('BiConnector:DashboardSelector.onSelect', {
						item: event.data.item,
						dashboardId: event.data.item.id,
						credentials: response.data.dashboard,
					});
				}
			})
			.catch((response) => {
				if (response.errors && Type.isStringFilled(response.errors[0]?.message))
				{
					BX.UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
					});
				}
			});
	}

	getDashboardEmbeddedData(dashboardId: number): Promise
	{
		return BX.ajax.runAction('biconnector.dashboard.getDashboardEmbeddedData', {
			data: {
				id: dashboardId,
			},
		});
	}

	#handleSearchClick()
	{
		this.#dialog.show();
	}

	#getFooter(): HTMLElement
	{
		const footerLink = Tag.render`<span 
			class="ui-selector-footer-link ui-selector-footer-link-add">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_SELECTOR_FOOTER')}
			</span>`;

		Event.bind(footerLink, 'click', () => {
			BX.SidePanel.Instance.open(this.#marketCollectionUrl, { customLeftBoundary: 0 });
			BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('market', 'market_call', {
				c_element: 'detail_button',
			});
		});

		return footerLink;
	}

	#setTitle(text: string)
	{
		this.#textNode.innerHTML = text;
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardSelector = SupersetDashboardSelector;

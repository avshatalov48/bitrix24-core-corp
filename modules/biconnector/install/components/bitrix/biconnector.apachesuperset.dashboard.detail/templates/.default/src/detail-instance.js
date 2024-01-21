import { Dom, Event, Loc, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { MenuItemOptions } from 'main.popup';
import { Menu } from 'main.popup';
import type { DetailConfig } from './type/detail-config';
import type { DashboardEmbeddedParameters } from './type/dashboard-embedded-parameters';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { ApacheSupersetEmbeddedLoader } from 'biconnector.apache-superset-embedded-loader';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import { ApacheSupersetFeedbackForm } from 'biconnector.apache-superset-feedback-form';
import 'sidepanel';

export class DetailInstance
{
	#dashboardManager: DashboardManager;
	#dashboardNode: HTMLElement;
	#editBtn: HTMLElement;

	#embeddedParams: DashboardEmbeddedParameters;
	#embeddedLoader: ApacheSupersetEmbeddedLoader;
	#isExportEnabled: boolean;

	#moreMenu: Menu;

	constructor(config: DetailConfig)
	{
		this.#dashboardNode = document.getElementById(config.appNodeId);
		if (!Type.isDomNode(this.#dashboardNode))
		{
			const errorMsg = `Cannot init superset dashboard. Node with ID ${config.appNodeId} does not exists`;
			throw new Error(errorMsg);
		}
		this.#dashboardManager = new DashboardManager();
		this.#isExportEnabled = config.isExportEnabled === 'Y';

		const frameNode = this.#dashboardNode.querySelector('.dashboard-iframe');

		this.#initFrame(frameNode, config.dashboardEmbeddedParams);
		this.#initHeaderButtons();

		if (this.#embeddedParams.sourceDashboard)
		{
			this.#onEditButtonClick();
		}

		EventEmitter.subscribe('BiConnector:DashboardSelector.onSelect', (event) => {
			Dom.clean(frameNode);
			this.#initFrame(frameNode, event.data.credentials);
			this.#initHeaderButtons();
		});
	}

	#initFrame(frameNode: HTMLElement, embeddedParams: DashboardEmbeddedParameters)
	{
		this.#embeddedParams = embeddedParams;
		const dashboardParams = {
			id: embeddedParams.uuid, // given by the Superset embedding UI
			supersetDomain: embeddedParams.supersetDomain,
			mountPoint: frameNode, // any html element that can contain an iframe
			fetchGuestToken: embeddedParams.guestToken,
			debug: true,
			dashboardUiConfig: { // dashboard UI config: hideTitle, hideTab, ...etc.
				hideTitle: true,
				hideTab: true,
				hideChartControls: true,
				filters: {
					expanded: true,
					visible: true,
					nativeFilters: embeddedParams.nativeFilters,
				},
			},
		};

		this.#embeddedLoader = new ApacheSupersetEmbeddedLoader(dashboardParams);
		this.#embeddedLoader.embedDashboard();
	}

	#initHeaderButtons()
	{
		this.#initMoreMenu();
		if (this.#editBtn)
		{
			Event.unbindAll(this.#editBtn);
		}
		this.#editBtn = this.#dashboardNode.querySelector('.dashboard-header-buttons-edit');
		Event.bind(this.#editBtn, 'click', this.#onEditButtonClick.bind(this));
	}

	#onEditButtonClick()
	{
		this.#muteEditButton();

		const dashboardInfo = {
			id: this.#embeddedParams.id,
			editLink: this.#embeddedParams.editUrl,
			type: this.#embeddedParams.type,
			sourceDashboardInfo: this.#embeddedParams.sourceDashboard ?? null,
		};

		this.#dashboardManager.processEditDashboard(
			dashboardInfo,
			() => {
				this.#unmuteEditButton();
			},
			(popupType) => {
				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
					c_sub_section: popupType,
					c_element: 'detail_button',
					type: this.#embeddedParams.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#embeddedParams.appId),
					p2: this.#embeddedParams.id,
					status: 'success',
				});
			},
			(popupType) => {
				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
					c_sub_section: popupType,
					c_element: 'detail_button',
					type: this.#embeddedParams.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#embeddedParams.appId),
					p2: this.#embeddedParams.id,
					status: 'error',
				});
			},
		);

		const slider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (slider)
		{
			EventEmitter.subscribeOnce(slider, 'SidePanel.Slider:onClose', () => {
				if (!top.BX.Main || !top.BX.Main.gridManager)
				{
					return;
				}

				top.BX.Main.gridManager.data.forEach((grid) => {
					grid.instance.reload();
				});
			});
		}
	}

	#muteEditButton()
	{
		this.#editBtn.setAttribute('disabled', 'true');
		Dom.addClass(this.#editBtn, 'ui-btn-wait');
	}

	#unmuteEditButton()
	{
		this.#editBtn.removeAttribute('disabled');
		Dom.removeClass(this.#editBtn, 'ui-btn-wait');
	}

	#initMoreMenu()
	{
		const moreButton = this.#dashboardNode.querySelector('.dashboard-header-buttons-more');
		if (this.#moreMenu)
		{
			Event.unbindAll(moreButton);
		}

		this.#moreMenu = new Menu({
			closeByEsc: false,
			closeIcon: false,
			cacheable: true,
			angle: 'top',
			items: this.#getMoreMenuItems(),
			toFrontOnShow: true,
			autoHide: true,
			bindElement: moreButton,
			className: 'more-popup',
			events: {
				onBeforeClose: () => {
					this.#moreMenu.getMenuItems().forEach((menuItem) => {
						menuItem.closeSubMenu();
					});
				},
				onAfterShow: () => {
					const popupContainer = this.#getMoreMenu().getPopupWindow().getPopupContainer();
					const overHeight = popupContainer.getBoundingClientRect().top + popupContainer.offsetHeight;

					if (overHeight > window.innerHeight)
					{
						window.scrollTo({
							top: window.scrollY + (-window.innerHeight + overHeight),
							behavior: 'smooth',
						});
					}
				},
			},
		});

		Event.bind(moreButton, 'click', () => this.#moreMenu.show());
	}

	#getMoreMenuItems(): MenuItemOptions[]
	{
		const result = [
			{
				id: 'order_dashboard',
				text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_ORDER_DASHBOARD'),
				title: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_ORDER_DASHBOARD'),
				onclick: () => {
					ApacheSupersetFeedbackForm.requestIntegrationFormOpen();
				},
			},
			{
				id: 'feedback',
				text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_FEEDBACK'),
				title: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_FEEDBACK'),
				onclick: () => {
					ApacheSupersetFeedbackForm.feedbackFormOpen();
				},
			},
		];

		const dashboardType = this.#embeddedParams.type.toLowerCase();
		if (this.#isExportEnabled && dashboardType === 'custom')
		{
			result.push({
				id: 'export',
				text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_EXPORT'),
				title: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_MORE_MENU_EXPORT'),
				onclick: () => {
					this.#dashboardManager.exportDashboard(
						this.#embeddedParams.id,
						() => {
							ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
								c_element: 'detail_button',
								type: dashboardType,
								p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#embeddedParams.appId),
								p2: this.#embeddedParams.id,
								status: 'success',
							});
						},
						() => {
							ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
								c_element: 'detail_button',
								type: dashboardType,
								p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#embeddedParams.appId),
								p2: this.#embeddedParams.id,
								status: 'error',
							});
						},
					);
				},
			});
		}

		return result;
	}

	#getMoreMenu(): Menu
	{
		return this.#moreMenu;
	}
}

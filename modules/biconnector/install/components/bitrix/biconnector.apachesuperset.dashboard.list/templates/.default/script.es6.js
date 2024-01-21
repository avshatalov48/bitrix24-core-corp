import { Loc, Type, Tag, Reflection, Dom, Text } from 'main.core';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { EventEmitter } from 'main.core.events';
import { MessageBox } from 'ui.dialogs.messagebox';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import type { DashboardAnalyticInfo } from 'biconnector.apache-superset-analytics';

type Props = {
	gridId: ?string,
};

type LoginPopupParams = {
	dashboardId: number,
	type: string,
	editUrl: string,
	appId: string,
};

class SupersetDashboardGridManager
{
	#dashboardManager: DashboardManager = null;
	#grid: BX.Main.grid;

	constructor(props: Props)
	{
		this.#dashboardManager = new DashboardManager();

		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
		this.#subscribeToEvents();
	}

	#subscribeToEvents()
	{
		EventEmitter.subscribe('BiConnector:DashboardManager.onUserCredentialsLoaded', this.onUserCredentialsLoaded.bind(this));

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [sliderEvent] = event.getCompatData();

			if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardDetail:onDashboardBatchStatusUpdate')
			{
				const eventArgs = sliderEvent.getData();
				if (eventArgs.dashboardList)
				{
					this.onUpdatedDashboardBatchStatus(eventArgs.dashboardList);
				}
			}
		});

		EventEmitter.subscribe('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', (event) => {
			const data = event.getData();
			if (!data.dashboardList)
			{
				return;
			}

			const dashboardList = data.dashboardList;
			this.onUpdatedDashboardBatchStatus(dashboardList);
		});

		EventEmitter.subscribe('BX.Rest.Configuration.Install:onFinish', () => {
			this.#grid.reload();
		});
	}

	onUpdatedDashboardBatchStatus(dashboardList: Array)
	{
		for (const dashboard of dashboardList)
		{
			this.updateDashboardStatus(dashboard.id, dashboard.status);
		}
	}

	getGrid(): BX.Main.grid
	{
		return this.#grid;
	}

	/**
	 * @param params LoginPopupParams
	 * @param openedFrom
	 */
	showLoginPopup(params: LoginPopupParams, openedFrom: string = 'unknown')
	{
		const grid = this.getGrid();
		if (params.type === 'CUSTOM')
		{
			grid.tableFade();
		}

		this.#dashboardManager.processEditDashboard(
			{
				id: params.dashboardId,
				type: params.type,
				editLink: params.editUrl,
			},
			() => {
				grid.tableUnfade();
			},
			(popupType) => {
				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
					c_sub_section: popupType,
					c_element: openedFrom,
					type: params.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(params.appId),
					p2: params.dashboardId,
					status: 'success',
				});
			},
			(popupType) => {
				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
					c_sub_section: popupType,
					c_element: openedFrom,
					type: params.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(params.appId),
					p2: params.dashboardId,
					status: 'error',
				});
			},
		);
	}

	onUserCredentialsLoaded()
	{
		this.getGrid().tableUnfade();
	}

	restartDashboardLoad(dashboardId: number): void
	{
		const row = this.#grid.getRows().getById(dashboardId);
		if (row)
		{
			const btn = row.node.querySelector('#restart-dashboard-load-btn');
			if (Type.isDomNode(btn))
			{
				const isDisabled = btn.getAttribute('disabled');
				if (isDisabled)
				{
					return;
				}

				btn.setAttribute('disabled', 'true');
				Dom.addClass(btn, 'dashboard-status-label-error-btn__loading');
			}
		}

		this.#dashboardManager.restartDashboardImport(dashboardId).then(
			(response) => {
				const dashboardIds = response?.data?.restartedDashboardIds;
				if (!dashboardIds)
				{
					return;
				}

				for (const restartedDashboardId of dashboardIds)
				{
					this.updateDashboardStatus(restartedDashboardId, 'L');
				}
			},
		);
	}

	setDashboardStatusReady(dashboardId: number): void
	{
		const row = this.#grid.getRows().getById(dashboardId);
		if (row)
		{
			const label = row.node.getElementsByClassName('dashboard-status-label')[0];
			Dom.addClass(label, 'ui-label-success');
			Dom.removeClass(label, 'ui-label-primary');
			label.querySelector('span').innerText = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
		}
	}

	updateDashboardStatus(dashboardId: number, status: string): void
	{
		const row = this.#grid.getRows().getById(dashboardId);
		if (row)
		{
			const labelWrapper = row.node.querySelector('.dashboard-status-label-wrapper');
			const label = labelWrapper.querySelector('.dashboard-status-label');
			const reloadBtn = labelWrapper.querySelector('#restart-dashboard-load-btn');

			switch (status) {
				case DashboardManager.DASHBOARD_STATUS_READY:
					if (reloadBtn)
					{
						reloadBtn.remove();
					}
					Dom.addClass(label, 'ui-label-success');
					Dom.removeClass(label, 'ui-label-primary');
					Dom.removeClass(label, 'ui-label-danger');
					label.querySelector('span').innerText = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
					break;
				case DashboardManager.DASHBOARD_STATUS_LOAD:
					if (reloadBtn)
					{
						reloadBtn.remove();
					}
					Dom.addClass(label, 'ui-label-primary');
					Dom.removeClass(label, 'ui-label-success');
					Dom.removeClass(label, 'ui-label-danger');
					label.querySelector('span').innerText = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_LOAD');
					break;
				case DashboardManager.DASHBOARD_STATUS_FAILED:
					if (!reloadBtn)
					{
						const createdReloadBtn = this.createReloadBtn(dashboardId);
						Dom.append(createdReloadBtn, labelWrapper);
					}
					Dom.addClass(label, 'ui-label-danger');
					Dom.removeClass(label, 'ui-label-success');
					Dom.removeClass(label, 'ui-label-primary');
					label.querySelector('span').innerText = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');
					break;
			}
		}
	}

	createReloadBtn(dashboardId: number): HTMLElement
	{
		return Tag.render`
			<div id="restart-dashboard-load-btn" onclick="BX.BIConnector.SupersetDashboardGridManager.Instance.restartDashboardLoad(${dashboardId})" class="dashboard-status-label-error-btn">
				<div class="ui-icon-set --refresh-5 dashboard-status-label-error-icon"></div>
			</div>
		`;
	}

	duplicateDashboard(dashboardId: number, analyticInfo: DashboardAnalyticInfo = null): void
	{
		const grid = this.getGrid();
		grid.tableFade();

		return this.#dashboardManager.duplicateDashboard(dashboardId)
			.then((response) => {
				const gridRealtime: BX.Grid.Realtime = grid.getRealtime();
				const newDashboard = response.data.dashboard;

				gridRealtime.addRow({
					id: newDashboard.id,
					prepend: true,
					columns: newDashboard.columns,
					actions: newDashboard.actions,
				});
				grid.tableUnfade();
				const counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
				counterTotalTextContainer.textContent++;

				BX.UI.Hint.init(BX('biconnector-dashboard-grid'));
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_COPY_NOTIFICATION_ADDED'),
				});

				if (analyticInfo !== null)
				{
					ApacheSupersetAnalytics.sendAnalytics('edit', 'report_copy', {
						type: analyticInfo.type,
						p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
						p2: dashboardId,
						status: 'success',
						c_element: analyticInfo.from,
					});
				}
			})
			.catch((response) => {
				grid.tableUnfade();
				if (response.errors && Type.isStringFilled(response.errors[0]?.message))
				{
					BX.UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
					});
				}

				if (analyticInfo !== null)
				{
					ApacheSupersetAnalytics.sendAnalytics('edit', 'report_copy', {
						type: analyticInfo.type,
						p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
						p2: dashboardId,
						status: 'error',
						c_element: analyticInfo.from,
					});
				}
			});
	}

	exportDashboard(dashboardId: number, analyticInfo: DashboardAnalyticInfo = null): void
	{
		const grid = this.getGrid();
		grid.tableFade();

		return this.#dashboardManager.exportDashboard(
			dashboardId,
			() => {
				grid.tableUnfade();
				if (analyticInfo !== null)
				{
					ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
						type: analyticInfo.type,
						p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
						p2: dashboardId,
						status: 'success',
						c_element: analyticInfo.from,
					});
				}
			},
			() => {
				grid.tableUnfade();
				if (analyticInfo !== null)
				{
					ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
						type: analyticInfo.type,
						p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
						p2: dashboardId,
						status: 'error',
						c_element: analyticInfo.from,
					});
				}
			},
		);
	}

	deleteDashboard(dashboardId: number): void
	{
		MessageBox.confirm(
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_TITLE'),
			(messageBox, button) => {
				button.setWaiting();
				this.#dashboardManager.deleteDashboard(dashboardId)
					.then(() => {
						this.getGrid().reload();
						messageBox.close();
					})
					.catch((response) => {
						messageBox.close();
						if (response.errors && Type.isStringFilled(response.errors[0]?.message))
						{
							BX.UI.Notification.Center.notify({
								content: Text.encode(response.errors[0].message),
							});
						}
					});
			},
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_YES'),
			(messageBox) => messageBox.close(),
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_NO'),
		);
	}

	createEmptyDashboard()
	{
		BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('new', 'report_new', {
			type: 'custom',
			c_element: 'new_button',
		});

		const grid = this.getGrid();
		grid.tableFade();
		this.#dashboardManager.createEmptyDashboard()
			.then((response) => {
				grid.tableUnfade();
				const gridRealtime: BX.Grid.Realtime = grid.getRealtime();
				const newDashboard = response.data.dashboard;

				gridRealtime.addRow({
					id: newDashboard.id,
					prepend: true,
					columns: newDashboard.columns,
					actions: newDashboard.actions,
				});

				grid.tableUnfade();
				const counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
				counterTotalTextContainer.textContent++;
			})
			.catch((response) => {
				grid.tableUnfade();

				if (response.errors)
				{
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CREATE_EMPTY_NOTIFICATION_ERROR'),
					});
				}
			});
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

import { Loc, Type, Tag, Reflection, Dom, Text, Event } from 'main.core';
import { DateTimeFormat } from 'main.date';
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

				const editableData = grid.getParam('EDITABLE_DATA');
				if (BX.type.isPlainObject(editableData))
				{
					editableData[newDashboard.id] = { TITLE: newDashboard.title };
				}

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
				if (response.errors)
				{
					this.#notifyErrors(response.errors);
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

	#notifyErrors(errors: Array): void
	{
		if (errors[0] && errors[0].message)
		{
			BX.UI.Notification.Center.notify({
				content: Text.encode(errors[0].message),
			});
		}
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
						if (response.errors)
						{
							this.#notifyErrors(response.errors);
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

				const editableData = grid.getParam('EDITABLE_DATA');
				if (BX.type.isPlainObject(editableData))
				{
					editableData[newDashboard.id] = { TITLE: newDashboard.title };
				}

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

	#buildDashboardTitleEditor(
		id: number,
		title: string,
		onCancel: () => void,
		onSave: (innerTitle: string) => void,
	): HTMLElement
	{
		const input = Tag.render`
			<input class="main-grid-editor main-grid-editor-text" type="text">
		`;
		input.value = title;

		const saveInputValue = () => {
			const value = input.value;
			Dom.removeClass(input, 'dashboard-title-input-danger');
			if (value.trim() === '')
			{
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CHANGE_TITLE_ERROR_EMPTY'),
				});

				Dom.addClass(input, 'dashboard-title-input-danger');

				return;
			}
			Dom.style(buttons, 'display', 'none');
			Dom.attr(input, 'disabled', true);
			onSave(input.value);
		};

		Event.bind(input, 'keydown', (event) => {
			if (event.keyCode === 13)
			{
				saveInputValue();
				event.preventDefault();
			}
			else if (event.keyCode === 27)
			{
				onCancel();
				event.preventDefault();
			}
		});

		const applyButton = Tag.render`
			<a>
				<i
					class="ui-icon-set --check"
					style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);"
				></i>
			</a>
		`;

		const cancelButton = Tag.render`
			<a>
				<i
					class="ui-icon-set --cross-60"
					style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);"
				></i>
			</a>
		`;

		const buttons = Tag.render`
			<div class="dashboard-title-wrapper__buttons">
				${applyButton}
				${cancelButton}
			</div>
		`;

		Event.bind(cancelButton, 'click', () => {
			onCancel();
		});

		Event.bind(applyButton, 'click', saveInputValue);

		return Tag.render`
			<div class="dashboard-title-wrapper__item dashboard-title-edit">
				${input}
				<div class="dashboard-title-wrapper__buttons-wrapper">
					${buttons}
				</div>
			</div>
		`;
	}

	#getTitlePreview(dashboardId: number): ?HTMLElement
	{
		const grid = this.getGrid();
		const row = grid.getRows().getById(dashboardId);
		if (!row)
		{
			return null;
		}

		const wrapper = row.getCellById('TITLE')?.querySelector('.dashboard-title-wrapper');
		if (!wrapper)
		{
			return null;
		}

		const previewSection = wrapper.querySelector('.dashboard-title-preview');
		if (previewSection)
		{
			return previewSection;
		}

		return null;
	}

	renameDashboard(dashboardId: number): void
	{
		const grid = this.getGrid();
		const row = grid.getRows().getById(dashboardId);
		if (!row)
		{
			return;
		}

		const rowNode = row.getNode();
		Dom.removeClass(rowNode, 'dashboard-title-edited');

		const wrapper = row.getCellById('TITLE')?.querySelector('.dashboard-title-wrapper');
		if (!wrapper)
		{
			return;
		}

		const editor = this.#buildDashboardTitleEditor(
			dashboardId,
			row.getEditData().TITLE,
			() => {
				this.cancelRenameDashboard(dashboardId);
			},
			(innerTitle) => {
				const oldTitle = this.#getTitlePreview(dashboardId).querySelector('a').innerText;
				this.#getTitlePreview(dashboardId).querySelector('a').innerText = innerTitle;

				const rowEditData = row.getEditData();
				rowEditData.TITLE = innerTitle;
				const editableData = grid.getParam('EDITABLE_DATA');
				if (BX.type.isPlainObject(editableData))
				{
					editableData[row.getId()] = rowEditData;
				}

				Dom.addClass(rowNode, 'dashboard-title-edited');
				const msg = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CHANGE_TITLE_SUCCESS', {
					'#NEW_TITLE#': Text.encode(innerTitle),
				});

				BX.UI.Notification.Center.notify({
					content: msg,
				});

				this.cancelRenameDashboard(dashboardId);
				this.#setDateModifyNow(dashboardId);

				this.#dashboardManager.renameDashboard(dashboardId, innerTitle)
					.catch((response) => {
						if (response.errors)
						{
							this.#notifyErrors(response.errors);
						}
						this.#getTitlePreview(dashboardId).querySelector('a').innerText = oldTitle;
						rowEditData.TITLE = oldTitle;
					});
			},
		);

		const preview = wrapper.querySelector('.dashboard-title-preview');
		if (preview)
		{
			Dom.style(preview, 'display', 'none');
		}
		Dom.append(editor, wrapper);

		const editBtn = row.getCellById('EDIT_URL')?.querySelector('a');

		const actionsClickHandler = () => {
			Event.unbind(row.getActionsButton(), 'click', actionsClickHandler);
			if (editBtn)
			{
				Event.unbind(editBtn, 'click', actionsClickHandler);
			}
			this.cancelRenameDashboard(dashboardId);
		};
		Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
		if (editBtn)
		{
			Event.bind(editBtn, 'click', actionsClickHandler);
		}
	}

	cancelRenameDashboard(dashboardId): void
	{
		const row = this.getGrid().getRows().getById(dashboardId);
		if (!row)
		{
			return;
		}

		const editSection = row.getCellById('TITLE')?.querySelector('.dashboard-title-edit');
		const previewSection = row.getCellById('TITLE')?.querySelector('.dashboard-title-preview');

		if (editSection)
		{
			Dom.remove(editSection);
		}

		if (previewSection)
		{
			Dom.style(previewSection, 'display', 'flex');
		}
	}

	#setDateModifyNow(dashboardId: number)
	{
		const dateModifyCell = this.#grid.getRows().getById(dashboardId)?.getCellById('DATE_MODIFY');
		if (!dateModifyCell)
		{
			return;
		}
		const cellContent = dateModifyCell.querySelector('.main-grid-cell-content span');

		const date = DateTimeFormat.format(
			DateTimeFormat.getFormat('FORMAT_DATETIME'),
			Math.floor(Date.now() / 1000),
		);
		const readableDate = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DATE_MODIFY_NOW');
		const newCellContent = Tag.render`
			<span data-hint="${date}" data-hint-no-icon data-hint-interactivity>${readableDate}</span>
		`;

		Dom.replace(cellContent, newCellContent);
		BX.UI.Hint.init(dateModifyCell);
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

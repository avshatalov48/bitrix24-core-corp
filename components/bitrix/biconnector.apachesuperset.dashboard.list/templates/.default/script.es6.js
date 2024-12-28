import { Loc, Type, Tag, Reflection, Dom, Text, Event, Runtime } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { PopupWindowManager } from 'main.popup';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox } from 'ui.dialogs.messagebox';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import type { DashboardAnalyticInfo } from 'biconnector.apache-superset-analytics';
import { Dialog } from 'ui.entity-selector';
import { Guide } from 'ui.tour';
import 'spotlight';
import { TagFooter } from 'biconnector.entity-selector';
import 'ui.alerts';
import 'ui.forms';

type Props = {
	gridId: ?string,
	isNeedShowTopMenuGuide: boolean,
	isNeedShowDraftGuide: boolean,
};

type LoginPopupParams = {
	dashboardId: number,
	type: string,
	editUrl: string,
	appId: string,
};

/**
 * @namespace BX.BIConnector
 */
class SupersetDashboardGridManager
{
	#dashboardManager: DashboardManager = null;
	#grid: BX.Main.grid;
	#filter: BX.Main.Filter;
	#tagSelectorDialog: ?Dialog;
	#topMenuGuideSpotlight: ?BX.SpotLight;
	#lastPinnedRowId: ?number;
	#properties: Props;

	constructor(props: Props)
	{
		this.#dashboardManager = new DashboardManager();
		this.#properties = props;

		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
		this.#filter = BX.Main.filterManager.getById(props.gridId);

		this.#subscribeToEvents();

		if (
			this.#properties.isNeedShowTopMenuGuide
			&& !PopupWindowManager?.isAnyPopupShown()
			&& this.#grid.getRows().getBodyFirstChild().actionsButton
		)
		{
			this.#topMenuGuideSpotlight = new BX.SpotLight({
				targetElement: this.#grid.getRows().getBodyFirstChild().actionsButton,
				targetVertex: 'middle-center',
				events: {
					onTargetEnter: () => this.#topMenuGuideSpotlight.close(),
				},
			});
			this.#topMenuGuideSpotlight.show();
			this.#showTopMenuGuide();
		}
		this.#colorPinnedRows();
		this.#initHints();
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
			else if (
				sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagChange'
				|| sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagDelete'
			)
			{
				if (this.#tagSelectorDialog)
				{
					this.#tagSelectorDialog.destroy();
					this.#tagSelectorDialog = null;
				}

				const filterTagValues = this.getFilter().getFilterFieldsValues();
				if (Type.isUndefined(filterTagValues['TAGS.ID']) || filterTagValues['TAGS.ID'].length === 0)
				{
					this.getGrid().reload();

					return;
				}

				const { tagId, title } = sliderEvent.getData();
				const currentFilteredTags = filterTagValues['TAGS.ID'] ?? [];
				const currentFilteredTagLabels = filterTagValues['TAGS.ID_label'] ?? [];

				const index = currentFilteredTags.findIndex((id) => Text.toInteger(id) === Text.toInteger(tagId));
				if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagDelete')
				{
					currentFilteredTags.splice(index, 1);
					currentFilteredTagLabels.splice(index, 1);
				}
				else
				{
					currentFilteredTagLabels[index] = title;
				}

				const filterApi = this.getFilter().getApi();
				filterApi.extendFilter({
					'TAGS.ID': currentFilteredTags,
					'TAGS.ID_label': currentFilteredTagLabels,
				});

				filterApi.apply();
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

		BX.PULL && BX.PULL.extendWatch('superset_dashboard', true);
		EventEmitter.subscribe('onPullEvent-biconnector', (event: BaseEvent) => {
			const [eventName, eventData] = event.data;
			if (eventName !== 'onSupersetStatusUpdated' || !eventData)
			{
				return;
			}

			const status = eventData?.status;
			if (status)
			{
				this.#onSupersetStatusChange(status);
			}
		});

		EventEmitter.subscribe('BX.Rest.Configuration.Install:onFinish', () => {
			this.#grid.reload();
		});

		EventEmitter.subscribe('Grid::updated', () => {
			this.#initHints();
			this.#colorPinnedRows();
		});

		EventEmitter.subscribe('BIConnector.ExportMaster:onDashboardDataLoaded', () => {
			this.#grid.tableUnfade();
		});

		EventEmitter.subscribe('BIConnector.DashboardManager:onEmbeddedDataLoaded', () => {
			this.#grid.reload();
		});

		EventEmitter.subscribe('BX.BIConnector.Settings:onAfterSave', () => {
			this.#grid.reload();
		});

		EventEmitter.subscribe('BIConnector.CreateForm:onDashboardCreated', (event) => {
			this.#onNewDashboardCreated(event);
		});
	}

	#initHints(): void
	{
		const manager = BX.UI.Hint.createInstance({
			popupParameters: {
				autoHide: true,
			},
		});
		manager.init(this.#grid.getContainer());
	}

	#onSupersetStatusChange(status: string): void
	{
		if (status === 'READY')
		{
			this.getGrid().reload();
		}

		if (status !== 'LOAD' && status !== 'ERROR')
		{
			return;
		}

		const statusMap = {
			LOAD: DashboardManager.DASHBOARD_STATUS_LOAD,
			ERROR: DashboardManager.DASHBOARD_STATUS_COMPUTED_NOT_LOAD,
		};

		const grid = this.getGrid();
		const rows = grid.getRows().getBodyChild();
		for (const row: BX.Grid.Row of rows)
		{
			const dashboardId = row.getId();
			const dashboardStatus = statusMap[status];
			if (dashboardStatus)
			{
				this.updateDashboardStatus(dashboardId, dashboardStatus);
			}
		}
	}

	#showTopMenuGuide(): void
	{
		const guide = new Guide({
			steps: [
				{
					target: this.#grid.getRows().getBodyFirstChild().node,
					title: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TOP_MENU_GUIDE_TITLE'),
					text: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TOP_MENU_GUIDE_TEXT'),
					events: {
						onClose: () => {
							BX.userOptions.save('biconnector', 'top_menu_guide', 'is_over', true);
						},
					},
					rounded: false,
					position: 'bottom',
					areaPadding: 0,
				},
			],
			onEvents: true,
		});
		guide.start();
	}

	#showDraftGuide(node: HTMLElement): void
	{
		if (!this.#properties.isNeedShowDraftGuide)
		{
			return;
		}

		const labelNode = node.querySelector('.dashboard-status-label.ui-label-default');
		const cellNode = labelNode ? labelNode.closest('.main-grid-cell') : null
		const guide = new Guide({
			steps: [
				{
					target: cellNode ?? node,
					title: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DRAFT_GUIDE_TITLE'),
					text: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DRAFT_GUIDE_TEXT'),
					events: {
						onClose: () => {
							BX.userOptions.save('biconnector', 'draft_guide', 'is_over', true);
						},
					},
					rounded: false,
					position: 'bottom',
					areaPadding: 0,
				},
			],
			onEvents: true,
		});

		guide.start();
		this.#properties.isNeedShowDraftGuide = false;
	}

	#colorPinnedRows(): void
	{
		this.#lastPinnedRowId = 0;
		const rows = this.#grid.getRows().getBodyChild();
		for (const row: BX.Grid.Row of rows)
		{
			if (row.node.querySelector('.dashboard-unpin-icon'))
			{
				Dom.addClass(row.node, 'biconnector-dashboard-pinned');
				this.#lastPinnedRowId = row.getId();
			}
		}
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

	getFilter(): BX.Main.Filter
	{
		return this.#filter;
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

	showLockedByParamsPopup()
	{
		MessageBox.alert(
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_OPEN_DESCRIPTION'),
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_OPEN_TITLE'),
			(messageBox) => {messageBox.close()},
			Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_CLOSE_BUTTON'),
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

		this.#dashboardManager
			.restartDashboardImport(dashboardId)
			.then(
				(response) => {
					const dashboardIds = response?.data?.restartedDashboardIds;
					if (!dashboardIds)
					{
						return;
					}

					for (const restartedDashboardId of dashboardIds)
					{
						this.updateDashboardStatus(restartedDashboardId, DashboardManager.DASHBOARD_STATUS_LOAD);
					}
				},
			)
			.catch()
		;
	}

	updateDashboardStatus(dashboardId: number, status: string): void
	{
		const row = this.#grid.getRows().getById(dashboardId);
		if (row)
		{
			const labelWrapper = row.node.querySelector('.dashboard-status-label-wrapper');
			const label = labelWrapper.querySelector('.dashboard-status-label');
			const reloadBtn = labelWrapper.querySelector('#restart-dashboard-load-btn');

			let labelClass = '';
			let labelTitle = '';

			switch (status)
			{
				case DashboardManager.DASHBOARD_STATUS_READY:
					labelClass = 'ui-label-lightgreen';
					labelTitle = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
					break;
				case DashboardManager.DASHBOARD_STATUS_DRAFT:
					labelClass = 'ui-label-default';
					labelTitle = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_DRAFT');
					break;
				case DashboardManager.DASHBOARD_STATUS_LOAD:
					labelClass = 'ui-label-primary';
					labelTitle = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_LOAD');
					break;
				case DashboardManager.DASHBOARD_STATUS_FAILED:
					labelClass = 'ui-label-danger';
					labelTitle = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');
					break;
				case DashboardManager.DASHBOARD_STATUS_COMPUTED_NOT_LOAD:
					labelClass = 'ui-label-danger';
					labelTitle = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_NOT_LOAD');
					break;
			}

			if (labelClass === '')
			{
				return;
			}

			if (reloadBtn)
			{
				reloadBtn.remove();
			}

			if (status === DashboardManager.DASHBOARD_STATUS_FAILED)
			{
				const createdReloadBtn = this.createReloadBtn(dashboardId);
				Dom.append(createdReloadBtn, labelWrapper);
			}

			const labelStatuses = [
				'ui-label-lightgreen',
				'ui-label-default',
				'ui-label-primary',
				'ui-label-danger',
			];
			Dom.addClass(label, labelClass);
			labelStatuses.forEach((uiStatus: string) => {
				if (uiStatus !== labelClass)
				{
					Dom.removeClass(label, uiStatus);
				}
			});

			label.querySelector('span').innerText = labelTitle
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
					columns: newDashboard.columns,
					actions: newDashboard.actions,
					insertAfter: this.#lastPinnedRowId,
				});

				const editableData = grid.getParam('EDITABLE_DATA');
				if (BX.type.isPlainObject(editableData))
				{
					editableData[newDashboard.id] = { TITLE: newDashboard.title };
				}

				grid.tableUnfade();
				const counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
				counterTotalTextContainer.textContent++;

				this.#initHints();
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

	exportDashboard(dashboardId: number): void
	{
		const grid = this.getGrid();
		grid.tableFade();

		return this.#dashboardManager.exportDashboard(dashboardId, 'grid_menu');
	}
	publish(dashboardId: number): void
	{
		this.#dashboardManager.toggleDraft(dashboardId, true)
			.then(() => {
				this.updateDashboardStatus(dashboardId, DashboardManager.DASHBOARD_STATUS_READY);
				this.#grid.updateRow(dashboardId);
			})
			.catch(() => {
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_PUBLISH_NOTIFICATION_ERROR'),
				});
			})
		;
	}

	setDraft(dashboardId: number): void
	{
		this.#dashboardManager.toggleDraft(dashboardId, false)
			.then(() => {
				this.updateDashboardStatus(dashboardId, DashboardManager.DASHBOARD_STATUS_DRAFT);
				this.#grid.updateRow(dashboardId, null, null, (result) => {
					this.#showDraftGuide(this.#grid.getRows().getById(dashboardId).node);
				});
			})
			.catch(() => {
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_SET_DRAFT_NOTIFICATION_ERROR'),
				});
			})
		;
	}

	deleteDashboard(dashboardId: number): void
	{
		const messageBox = new MessageBox({
			message: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_TITLE'),
			buttons: [
				new BX.UI.Button({
					color: BX.UI.Button.Color.DANGER,
					text: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_YES'),
					onclick: (button) => {
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
				}),
				new BX.UI.CancelButton({
					text: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_NO'),
					onclick: (button) => messageBox.close(),
				}),
			],
		});

		messageBox.show();
	}

	openCreationSlider()
	{
		this.#dashboardManager.openCreationSlider();
	}

	#onNewDashboardCreated(event: Event)
	{
		const grid = this.getGrid();
		const newDashboard = event.data.dashboard;
		const gridRealtime: BX.Grid.Realtime = grid.getRealtime();
		gridRealtime.addRow({
			id: newDashboard.id,
			columns: newDashboard.columns,
			actions: newDashboard.actions,
			insertAfter: this.#lastPinnedRowId,
		});

		const editableData = grid.getParam('EDITABLE_DATA');
		if (BX.type.isPlainObject(editableData))
		{
			editableData[newDashboard.id] = { TITLE: newDashboard.title };
		}

		const counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
		counterTotalTextContainer.textContent++;
		this.#initHints();
		setTimeout(() => {
			this.#showDraftGuide(this.#grid.getRows().getBodyFirstChild().node);
		}, 1200);
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
		this.#initHints();
	}

	handleTagClick(tagJson: string): void
	{
		const tag = JSON.parse(tagJson);
		this.handleFilterChange({
			fieldId: 'TAGS.ID',
			...tag,
		});
	}

	handleTagAddClick(dashboardId: number, preselectedIds: [], event: BaseEvent): void
	{
		const onTagsChange = () => {
			const tags = this.#tagSelectorDialog.getSelectedItems().map((item) => item.getId());
			this.#dashboardManager
				.setDashboardTags(dashboardId, tags)
				.then(
					() => {
						this.getGrid().updateRow(dashboardId, null, null, () => {
							const anchor = this.getGrid()
								.getRows()
								.getById(dashboardId)
								?.getCellById('TAGS')
							;

							if (anchor && this.#tagSelectorDialog)
							{
								this.#tagSelectorDialog.setTargetNode(anchor);
							}
						});
						const filterTagValues = this.getFilter().getFilterFieldsValues();
						const currentFilteredTags = filterTagValues['TAGS.ID'] ?? [];
						if (currentFilteredTags.length > 0)
						{
							const filtered = tags.filter((tagId) => currentFilteredTags.includes(String(tagId)));
							if (filtered.length === 0)
							{
								this.#tagSelectorDialog.destroy();

								this.#tagSelectorDialog = null;
							}
						}
					},
				);
		};
		const entityId = 'biconnector-superset-dashboard-tag';

		const preselectedItems = [];
		JSON.parse(preselectedIds).forEach((id) => preselectedItems.push([entityId, id]));
		this.#tagSelectorDialog = new Dialog({
			id: 'biconnector-superset-tag-widget',
			targetNode: event.getData().button,
			enableSearch: true,
			width: 350,
			height: 400,
			multiple: true,
			dropdownMode: true,
			compactView: true,
			context: entityId,
			clearUnavailableItems: true,
			entities: [
				{
					id: entityId,
					options: {
						dashboardId,
					},
				},
			],
			preselectedItems,
			searchOptions: {
				allowCreateItem: false,
			},
			footer: TagFooter,
			events: {
				onSearch: (event: BaseEvent) => {
					const query = event.getData().query;

					const footer: TagFooter = this.#tagSelectorDialog.getFooter();
					const footerWrapper = this.#tagSelectorDialog.getFooterContainer();
					if (Type.isStringFilled(query.trim()) && footer.canCreateTag())
					{
						Dom.show(footerWrapper.querySelector('#tags-widget-custom-footer-add-new'));
						Dom.show(footerWrapper.querySelector('#tags-widget-custom-footer-conjunction'));

						return;
					}

					Dom.hide(footerWrapper.querySelector('#tags-widget-custom-footer-add-new'));
					Dom.hide(footerWrapper.querySelector('#tags-widget-custom-footer-conjunction'));
				},
				'Search:onItemCreateAsync': (searchEvent: BaseEvent) => {
					return new Promise((resolve, reject) => {
						const { searchQuery } = searchEvent.getData();
						const name = searchQuery.getQuery();

						this.#dashboardManager.addTag(name)
							.then((result) => {
								const newTag = result.data;
								const item = this.#tagSelectorDialog.addItem({
									id: newTag.ID,
									entityId,
									title: name,
									tabs: 'all',
								});
								if (item)
								{
									item.select();
								}

								resolve();
							})
							.catch((result) => {
								const errors = result.errors;
								errors.forEach((error) => {
									const alert = Tag.render`
										<div class="dashboard-tag-already-exists-alert">
											<div class='ui-alert ui-alert-xs ui-alert-danger'> 
												<span class='ui-alert-message'>
													${error.message}
												</span> 
											</div>
										</div>
									`;

									Dom.prepend(alert, this.#tagSelectorDialog.getFooterContainer());
									setTimeout(
										() => {
											Dom.remove(alert);
										},
										3000,
									);

									reject();
								});
							})
						;
					});
				},
				'Item:onSelect': Runtime.debounce(onTagsChange, 100, this),
				'Item:onDeselect': Runtime.debounce(onTagsChange, 100, this),
			},
		});

		this.#tagSelectorDialog.show();
	}

	handleOwnerClick(ownerData: Object)
	{
		this.handleFilterChange({
			fieldId: 'OWNER_ID',
			...ownerData,
		});
	}

	handleCreatedByClick(ownerData: Object)
	{
		this.handleFilterChange({
			fieldId: 'CREATED_BY_ID',
			...ownerData,
		});
	}

	handleFilterChange(fieldData: Object)
	{
		const filterFieldsValues = this.getFilter().getFilterFieldsValues();
		let currentFilteredField = filterFieldsValues[fieldData.fieldId] ?? [];
		let currentFilteredFieldLabel = filterFieldsValues[`${fieldData.fieldId}_label`] ?? [];

		if (fieldData.IS_FILTERED)
		{
			currentFilteredField = currentFilteredField.filter((value) => parseInt(value, 10) !== fieldData.ID);
			currentFilteredFieldLabel = currentFilteredFieldLabel.filter((value) => value !== fieldData.TITLE);
		}
		else if (!currentFilteredField.includes(fieldData.ID))
		{
			currentFilteredField.push(fieldData.ID);
			currentFilteredFieldLabel.push(fieldData.TITLE);
		}

		const filterApi = this.getFilter().getApi();
		const filterToExtend = {};
		filterToExtend[fieldData.fieldId] = currentFilteredField;
		filterToExtend[`${fieldData.fieldId}_label`] = currentFilteredFieldLabel;

		filterApi.extendFilter(filterToExtend);
		filterApi.apply();
	}

	addToTopMenu(dashboardId: number): Promise
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ADD_TO_TOP_MENU_SUCCESS'),
		});
		this.#switchTopMenuAction(dashboardId, true);

		return this.#dashboardManager.addToTopMenu(dashboardId)
			.then((response) => {})
			.catch((response) => {
				this.#grid.updateRow(dashboardId);
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ADD_TO_TOP_MENU_ERROR'),
				});
			})
		;
	}

	deleteFromTopMenu(dashboardId: number): Promise
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_FROM_TOP_MENU_SUCCESS'),
		});
		this.#switchTopMenuAction(dashboardId, false);

		return this.#dashboardManager.deleteFromTopMenu(dashboardId)
			.then((response) => {})
			.catch((response) => {
				this.#grid.updateRow(dashboardId);
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_FROM_TOP_MENU_ERROR'),
				});
			})
		;
	}

	#switchTopMenuAction(dashboardId: number, isInTopMenu: boolean)
	{
		const row = this.#grid.getRows().getById(dashboardId);
		const rowActions = row?.getActions();
		for (const [index, action] of rowActions.entries())
		{
			if (isInTopMenu && action.ACTION_ID === 'addToTopMenu')
			{
				rowActions[index].ACTION_ID = 'deleteFromTopMenu';
				rowActions[index].onclick = `BX.BIConnector.SupersetDashboardGridManager.Instance.deleteFromTopMenu(${dashboardId})`;
				rowActions[index].text = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ACTION_ITEM_DELETE_FROM_TOP_MENU');
			}
			else if (!isInTopMenu && action.ACTION_ID === 'deleteFromTopMenu')
			{
				rowActions[index].ACTION_ID = 'addToTopMenu';
				rowActions[index].onclick = `BX.BIConnector.SupersetDashboardGridManager.Instance.addToTopMenu(${dashboardId})`;
				rowActions[index].text = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ACTION_ITEM_ADD_TO_TOP_MENU');
			}
		}
		row.setActions(rowActions);

		const titleCell = row?.getCellById('TITLE');
		let dashboardTitle = '';
		if (titleCell)
		{
			const titleWrapper = titleCell.querySelector('.dashboard-title-wrapper__item');
			dashboardTitle = titleWrapper.querySelector('a').innerText;
		}

		const menu: BX.Main.interfaceButtons = BX.Main.interfaceButtonsManager.getById('biconnector_superset_menu');
		if (isInTopMenu && dashboardTitle)
		{
			menu.addMenuItem({
				id: `biconnector_superset_menu_dashboard_${dashboardId}`,
				text: dashboardTitle,
				url: `/bi/dashboard/detail/${dashboardId}/?openFrom=menu`,
				onClick: '',
			});
			const menuItem = menu.getItemById(`biconnector_superset_menu_dashboard_${dashboardId}`);
			const firstMenuItem = menu.getVisibleItems();
			Dom.insertBefore(menuItem, firstMenuItem[0]);
		}
		else
		{
			const menuItem = menu.getItemById(`biconnector_superset_menu_dashboard_${dashboardId}`);
			menu.deleteMenuItem(menuItem);
		}
	}

	pin(dashboardId: number): void
	{
		return this.#dashboardManager.pin(dashboardId)
			.then(() => {
				this.#grid.reload();
			})
			.catch(() => {})
		;
	}

	unpin(dashboardId: number): void
	{
		return this.#dashboardManager.unpin(dashboardId)
			.then(() => {
				this.#grid.reload();
			})
			.catch(() => {})
		;
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

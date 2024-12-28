import { ajax as Ajax, Loc, Tag, Text, Type, Uri } from 'main.core';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import { BaseEvent, EventEmitter } from 'main.core.events';
import 'sidepanel';
import { DashboardExportMaster } from 'biconnector.dashboard-export-master';

import './css/main.css';

type DashboardInfo = {
	id: number,
	isEditable: boolean,
	type: 'SYSTEM' | 'MARKET' | 'CUSTOM',
	appId: string, // for analytic
	editLink: string,
	title: string,
};

export class DashboardManager
{
	static DASHBOARD_STATUS_LOAD = 'L';
	static DASHBOARD_STATUS_READY = 'R';
	static DASHBOARD_STATUS_FAILED = 'F';
	static DASHBOARD_STATUS_DRAFT = 'D';

	static DASHBOARD_STATUS_COMPUTED_NOT_LOAD = 'NL';

	constructor()
	{
		this.subscribeOnEvents();
	}

	subscribeOnEvents()
	{
		BX.PULL && BX.PULL.extendWatch('superset_dashboard', true);
		EventEmitter.subscribe('onPullEvent-biconnector', (event: BaseEvent) => {
			const [eventName, eventData] = event.data;
			if (eventName !== 'onDashboardStatusUpdated' || !eventData)
			{
				return;
			}

			const dashboardList = eventData?.dashboardList;
			if (dashboardList)
			{
				EventEmitter.emit('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', {
					dashboardList: dashboardList,
				});
			}
		});
	}

	processEditDashboard(
		dashboardInfo: DashboardInfo,
		onCloseProcessing: () => void = () => {},
		onCompleteProcessing: (popupType: string) => void = () => {},
		onFailProcessing: (popupType: string) => void = () => {},
	)
	{
		if (dashboardInfo.type === 'CUSTOM')
		{
			this.processLoginDashboard(dashboardInfo, onCloseProcessing, onCompleteProcessing, onFailProcessing);
		}
		else
		{
			this.processCopyDashboard(dashboardInfo, onCloseProcessing, onCompleteProcessing, onFailProcessing);
		}
	}

	processCopyDashboard(
		dashboardInfo: DashboardInfo,
		onCloseProcessing: (popupType: string) => void = () => {},
		onCompleteProcessing: (popupType: string) => void = () => {},
		onFailProcessing: (popupType: string) => void = () => {},
	): void
	{
		const attentionText = dashboardInfo.type === 'SYSTEM'
			? Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_COPY_SYSTEM_DASHBOARD_ATTENTION')
			: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_COPY_MARKET_DASHBOARD_ATTENTION')
		;
		const confirmContent = Tag.render`
			<div class="dashboard-login-popup-copy-attention">
				${attentionText}
			</div>
		`;

		const popupType = 'popup_copy';

		const continueBtn = new Button({
			text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_CONTINUE_BTN'),
			color: Button.Color.PRIMARY,
			onclick: () => {
				continueBtn.setWaiting(true);
				this.duplicateDashboard(dashboardInfo.id)
					.then((duplicateResponse) => {
						onCompleteProcessing(popupType);
						const dashboard = duplicateResponse.data.dashboard;
						if (!dashboard)
						{
							BX.UI.Notification.Center.notify({
								content: BX.util.htmlspecialchars(
									Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_COPY_ERROR'),
								),
							});

							return null;
						}

						return this.getDashboardEmbeddedData(dashboard.id);
					})
					.then((embeddedResponse) => {
						EventEmitter.emit('BIConnector.DashboardManager:onCopyDashboard', {
							dashboard: embeddedResponse.data.dashboard,
						});
						const copiedDashboardInfo = {
							id: embeddedResponse.data.dashboard.id,
							editLink: embeddedResponse.data.dashboard.editUrl,
							type: embeddedResponse.data.dashboard.type,
						};
						this.processLoginDashboard(
							copiedDashboardInfo,
							() => {
								onCloseProcessing();
								popup.close();
								EventEmitter.emit('BIConnector.DashboardManager:onEmbeddedDataLoaded');
							},
							onCompleteProcessing,
							onFailProcessing,
						);
					})
					.catch((response) => {
						onFailProcessing(popupType);
						if (response.errors && Type.isStringFilled(response.errors[0]?.message))
						{
							BX.UI.Notification.Center.notify({
								content: Text.encode(response.errors[0].message),
							});
						}
					});
			},
		});

		const popup = new Popup({
			content: confirmContent,
			overlay: true,
			width: 620,
			className: 'dashboard-login-popup',
			closeIcon: true,
			closeByEsc: true,
			disableScroll: true,
			titleBar: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_TITLE'),
			buttons: [
				continueBtn,
				new Button({
					text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_CANCEL_BTN'),
					color: Button.Color.LIGHT,
					onclick: () => {
						popup.close();
					},
				}),
			],
			events: {
				onClose: () => {
					onCloseProcessing('popup_copy');
				},
			},
		});

		popup.show();
	}

	processLoginDashboard(
		dashboardInfo: DashboardInfo,
		onCloseProcessing: (popupType: string) => void = () => {},
		onCompleteProcessing: (popupType: string) => void = () => {},
		onFailProcessing: (popupType: string) => void = () => {},
	): void
	{
		const popupType = 'popup_login';

		this.getEditUrl(dashboardInfo)
			.then((response) => {
				onCompleteProcessing(popupType);
				if (response)
				{
					window.open(response, '_blank').focus();
				}
			})
			.catch(() => {
				onFailProcessing(popupType);
				window.open(dashboardInfo.editLink, '_blank').focus();
			})
			.finally(() => {
				onCloseProcessing();
			});
	}

	duplicateDashboard(dashboardId: number | string): Promise
	{
		return Ajax.runAction('biconnector.dashboard.copy', {
			data: {
				id: dashboardId,
			},
		});
	}

	getDashboardUrlParameters(dashboardId: number | string): Promise
	{
		return Ajax.runAction('biconnector.dashboard.getDashboardUrlParameters', {
			data: {
				id: dashboardId,
			},
		});
	}

	exportDashboard(
		dashboardId: number,
		openedFrom: string,
	): Promise
	{
		const exportMaster: DashboardExportMaster = new DashboardExportMaster({
			dashboardId,
			openedFrom,
		});

		/** @see BX.BIConnector.DashboardExportMaster.showPopup() */
		return exportMaster.showPopup();
	}

	deleteDashboard(dashboardId): Promise
	{
		return Ajax.runAction('biconnector.dashboard.delete', {
			data: {
				id: dashboardId,
			},
		});
	}

	renameDashboard(dashboardId: number, title: string): Promise
	{
		return Ajax.runAction('biconnector.dashboard.rename', {
			data: {
				id: dashboardId,
				title: title,
			},
		});
	}

	restartDashboardImport(dashboardId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboard.restartImport', {
			data: {
				id: dashboardId,
			},
		}).then(
			(response) => {
				const dashboardIds = response?.data?.restartedDashboardIds;
				if (!dashboardIds)
				{
					return;
				}

				const dashboardList = [];
				for (const restartedDashboardId of dashboardIds)
				{
					dashboardList.push({
						id: Number(restartedDashboardId),
						status: 'L',
					});
				}

				EventEmitter.emit(window, 'BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', {
					dashboardList: dashboardList,
				});
			},
		);
	}

	setDashboardTags(dashboardId: number, tags: {}): Promise
	{
		return Ajax.runAction('biconnector.dashboard.setDashboardTags', {
			data: {
				id: dashboardId,
				tags,
			},
		});
	}

	addTag(title: string): Promise
	{
		return Ajax.runAction('biconnector.dashboardTag.add', {
			data: {
				title,
			},
		});
	}

	static openSettingsSlider(dashboardId: number = null)
	{
		const componentLink = dashboardId === null
			? '/bitrix/components/bitrix/biconnector.apachesuperset.setting/slider.php'
			: '/bitrix/components/bitrix/biconnector.apachesuperset.dashboard.setting/slider.php'
		;

		const sliderLink = new Uri(componentLink);
		if (dashboardId !== null)
		{
			sliderLink.setQueryParam('DASHBOARD_ID', Text.toNumber(dashboardId));
		}

		BX.SidePanel.Instance.open(
			sliderLink.toString(),
			{
				width: 790,
				allowChangeHistory: false,
				cacheable: false,
			},
		);
	}

	static openDatasetListSlider()
	{
		BX.SidePanel.Instance.open(
			'/bi/dataset',
			{
				cacheable: false,
				allowChangeHistory: true,
				allowChangeTitle: true,
			},
		);
	}

	openCreationSlider(): void
	{
		const componentLink = '/bitrix/components/bitrix/biconnector.apachesuperset.dashboard.create/slider.php';
		const sliderLink = new Uri(componentLink);

		BX.SidePanel.Instance.open(
			sliderLink.toString(),
			{
				width: 790,
				allowChangeHistory: false,
				cacheable: false,
			},
		);
	}

	getEditUrl(dashboardInfo: DashboardInfo): Promise
	{
		return new Promise((resolve, reject) => {
			Ajax.runAction(
				'biconnector.dashboard.getEditUrl',
				{
					data: {
						id: dashboardInfo.id,
						editUrl: dashboardInfo.editLink,
					},
				},
			)
				.then((response) => {
					const data = response.data;
					if (data)
					{
						resolve(data);
					}
				})
				.catch((e) => {
					reject(e);
				});
		});
	}

	addToTopMenu(dashboardId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboard.addToTopMenu', {
			data: {
				dashboardId,
			},
		});
	}

	deleteFromTopMenu(dashboardId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboard.deleteFromTopMenu', {
			data: {
				dashboardId,
			},
		});
	}

	pin(dashboardId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboard.pin', {
			data: {
				dashboardId,
			},
		});
	}

	unpin(dashboardId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboard.unpin', {
			data: {
				dashboardId,
			},
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

	toggleDraft(dashboardId: number, publish: boolean): Promise
	{
		return BX.ajax.runAction('biconnector.dashboard.toggleDraft', {
			data: {
				id: dashboardId,
				publish: publish ? 1 : 0,
			},
		});
	}
}
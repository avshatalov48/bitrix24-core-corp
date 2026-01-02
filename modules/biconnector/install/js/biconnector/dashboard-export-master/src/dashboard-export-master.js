import { ajax, Loc, Dom, UI, Event, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Popup } from 'main.popup';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { Button } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';
import { TextCrop } from 'ui.textcrop';
import { Dialog } from 'ui.entity-selector';
import 'ui.forms';
import './css/main.css';

type DashboardMasterProps = {
	dashboardId: number,
	openedFrom: string,
}

type DashboardData = {
	title: string,
	type: string,
	appId: string,
	issetScopeNotToExport: boolean,
	group: ?DashboardSystemGroup,
}

type DashboardSystemGroup = {
	id: number,
	name: string,
}

/**
 * @namespace BX.BIConnector
 */
export class DashboardExportMaster
{
	#dashboardId: number;
	#openedFrom: string;
	#popup: Popup;
	#dashboardData: DashboardData;
	#exportButton: Button;

	constructor(props: DashboardMasterProps)
	{
		this.#dashboardId = props.dashboardId;
		this.#openedFrom = props.openedFrom;
	}

	#loadInfo(dashboardId: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('biconnector.dashboard.getExportData', {
				data: { id: dashboardId },
			})
				.then((response) => {
					EventEmitter.emit('BIConnector.ExportMaster:onDashboardDataLoaded');
					resolve(response);
				})
				.catch((response) => {
					reject(response);
				})
			;
		});
	}

	#createPopup(dashboardData: DashboardData)
	{
		this.#popup = new Popup({
			content: this.#getPopupContent(dashboardData),
			closeIcon: true,
			closeByEsc: true,
			cacheable: false,
			overlay: true,
			width: 360,
			padding: 0,
		});
		this.#popup.show();

		const dashboardTitle = new TextCrop({
			rows: 1,
			resize: true,
			target: document.querySelector('.bic-export-dashboard-title'),
		});
		dashboardTitle.init();

		this.#exportButton = new Button({
			text: Loc.getMessage('BIC_EXPORT_BUTTON'),
			size: Button.Size.SMALL,
			round: true,
			color: Button.Color.PRIMARY,
			className: 'bic-export-button',
			disabled: !dashboardData.group,
			state: dashboardData.group ? Button.State.ACTIVE : Button.State.DISABLED,
			onclick: () => this.#exportDashboard(this.#dashboardId),
		});
		this.#exportButton.renderTo(this.#popup.getContentContainer().querySelector('.bic-export-footer'));

		if (!dashboardData.group)
		{
			const buttonNode = this.#exportButton.getContainer();
			buttonNode.setAttribute('data-hint', Loc.getMessage('BIC_EXPORT_NO_GROUP_ERROR'));
			buttonNode.setAttribute('data-hint-no-icon', 'Y');
			buttonNode.setAttribute('data-hint-html', 'Y');
			BX.UI.Hint.init(this.#popup.getContentContainer());
		}

		const settingsLink = this.#popup.getContentContainer().querySelector('.bic-settings-link');
		settingsLink.onclick = this.#openSettingsSlider.bind(this, this.#dashboardId);

		const groupSelectorButton = this.#popup.getContentContainer().querySelector('#bic-group-selector-button');
		const preselectedItems = [];
		if (dashboardData.group)
		{
			preselectedItems.push(['biconnector-superset-group', dashboardData.group.id]);
		}
		const groupSelector = new Dialog({
			targetNode: groupSelectorButton,
			width: 324,
			height: 240,
			autoHide: true,
			multiple: false,
			showAvatars: true,
			dropdownMode: true,
			preselectedItems,
			events: {
				'Item:onSelect': (event) => {
					const item = event.getData().item;
					groupSelectorButton.textContent = Text.encode(item.getTitle());

					if (!this.#dashboardData.group)
					{
						const buttonNode = this.#exportButton.getContainer();
						buttonNode.removeAttribute('data-hint');
						buttonNode.removeAttribute('data-hint-no-icon');
						buttonNode.removeAttribute('data-hint-html');
						BX.UI.Hint.init(this.#popup.getContentContainer());

						this.#exportButton.setState(Button.State.ACTIVE);
						this.#exportButton.setDisabled(false);
					}

					this.#dashboardData.group = {
						id: item.getId(),
						name: item.getTitle(),
					};
				},
				'Item:onBeforeDeselect': (event) => {
					groupSelectorButton.innerHTML = this.#getPlaceholderForGroupSelector();

					const buttonNode = this.#exportButton.getContainer();
					buttonNode.setAttribute('data-hint', Loc.getMessage('BIC_EXPORT_NO_GROUP_ERROR'));
					buttonNode.setAttribute('data-hint-no-icon', 'Y');
					buttonNode.setAttribute('data-hint-html', 'Y');
					BX.UI.Hint.init(this.#popup.getContentContainer());

					this.#exportButton.setDisabled(true);
					this.#exportButton.setState(Button.State.INIT);

					this.#dashboardData.group = null;
				},
			},
			entities: [{
				id: 'biconnector-superset-group',
				dynamicLoad: true,
				options: {
					onlySystemGroups: true,
					checkAccessRights: false,
				},
			}],
		});
		Dom.addClass(groupSelector.getContainer(), 'biconnector-group-selector');

		Event.bind(groupSelectorButton, 'click', () => {
			groupSelector.show();
		});
	}

	#getPopupContent(dashboardData: DashboardData): string
	{
		return `
			<div class="bic-export-container">
				<div class="bic-export-header">
					<div class="bic-export-subtitle">${Loc.getMessage('BIC_EXPORT_SUBTITLE')}</div>
					<div class="bic-export-dashboard-title">${dashboardData.title}</div>
				</div>
				<div class="bic-export-separator"></div>
				<div class="bic-export-body">
					<div class="bic-export-hint">${Loc.getMessage('BIC_EXPORT_HINT_MSGVER_2')}</div>

					<div class="bic-setting-item bic-setting-item-group">
						<div class="bic-setting-title">
							<span>${Loc.getMessage('BIC_EXPORT_GROUP_TITLE')}</span>
						</div>
						<div class="bic-setting-value ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<button 
								class="ui-btn ui-btn-sm ui-btn-light-border bic-group-selector-button" 
								id="bic-group-selector-button"
							>
								${dashboardData.group?.name ?? this.#getPlaceholderForGroupSelector()}
							</button>
						</div>
					</div>

					<div class="bic-setting-item">
						<span class="bic-settings-link">${Loc.getMessage('BIC_EXPORT_OPEN_SETTINGS')}</span>
					</div>

				</div>
				<div class="bic-export-separator"></div>
				<div class="bic-export-footer"></div>
			</div>
		`;
	}

	showPopup(): Promise
	{
		return new Promise((resolve, reject) => {
			this.#loadInfo(this.#dashboardId)
				.then((response) => {
					this.#dashboardData = response.data;
					this.#createPopup(response.data);
					resolve(response);
				})
				.catch((response) => {
					UI.Notification.Center.notify({
						content: Loc.getMessage('BIC_EXPORT_ERROR'),
					});
					reject(response);
				})
			;
		});
	}

	async #exportDashboard(dashboardId: number): Promise
	{
		if (!this.#dashboardData.group)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('BIC_EXPORT_NO_GROUP_ERROR'),
			});

			return Promise.resolve();
		}

		this.#exportButton.setState(Button.State.WAITING);

		if (this.#dashboardData.issetScopeNotToExport)
		{
			await new Promise((resolve) => {
				MessageBox.alert(
					Loc.getMessage('BIC_EXPORT_SCOPE_HINT_MSGVER1'),
					() => {
						resolve();

						return true;
					},
				);
			});
		}

		return ajax.runAction('biconnector.dashboard.export', {
			data: {
				id: dashboardId,
				groupId: this.#dashboardData.group.id,
			},
		})
			.then((response) => {
				const filePath = response.data.filePath;
				if (filePath)
				{
					window.open(filePath, '_self');
				}
				this.#popup.close();

				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
					type: this.#dashboardData.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#dashboardData.appId),
					p2: dashboardId,
					status: 'success',
					c_element: this.#openedFrom,
				});
			})
			.catch(() => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('BIC_EXPORT_ERROR'),
				});
				this.#exportButton.setState(Button.State.ACTIVE);

				ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
					type: this.#dashboardData.type.toLowerCase(),
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#dashboardData.appId),
					p2: dashboardId,
					status: 'error',
					c_element: this.#openedFrom,
				});
			})
		;
	}

	#openSettingsSlider(dashboardId: number): void
	{
		EventEmitter.subscribe('BX.BIConnector.Settings:onAfterSave', this.#onSettingsChanged.bind(this));
		DashboardManager.openSettingsSlider(dashboardId);
	}

	#onSettingsChanged(): void
	{
		EventEmitter.unsubscribe('BX.BIConnector.Settings:onAfterSave', this.#onSettingsChanged.bind(this));

		if (this.#popup.isDestroyed())
		{
			return;
		}

		const loader = new Loader({
			target: this.#popup.getContentContainer(),
		});
		loader.show();
		this.#loadInfo(this.#dashboardId)
			.then((response) => {
				this.#dashboardData = response.data;
				this.#popup.close();
				this.#createPopup(response.data);
			})
			.catch(() => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('BIC_EXPORT_ERROR'),
				});
			})
		;
	}

	#getPlaceholderForGroupSelector(): string
	{
		return `<span class="bic-group-placeholder">${Loc.getMessage('BIC_EXPORT_GROUP_EMPTY')}</span>`;
	}
}

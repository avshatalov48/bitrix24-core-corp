import { ajax as Ajax, Loc, Tag, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import { Dialog, Item } from 'ui.entity-selector';

type Props = {
	targetNode: HTMLElement,
	onSend: Function,
	fileExtension: 'jpeg' | 'pdf',
	dashboardName: string,
	dashboardId: number,
	dashboardType: string,
	appId: number,
};

export class ChatSelector
{
	#props: Props;
	#dialog: Dialog;
	#sendInProgress: boolean = false;

	constructor(props: Props)
	{
		this.#props = props;
		this.#initDialog();
	}

	show(): void
	{
		this.#dialog.show();
	}

	#initDialog()
	{
		this.#dialog = new Dialog({
			id: 'biconnector-chat-selector',
			multiple: true,
			targetNode: this.#props.targetNode,
			offsetTop: 14,
			context: 'biconnector-chat-selector',
			popupOptions: {
				className: 'biconnector-chat-selector',
			},
			entities: this.#getSelectorEntities(),
			header: this.#getHeader(),
			footer: this.#getFooter(),
			footerOptions: {
				containerClass: 'ui-selector-footer-default biconnector-send-to-chat-footer-wrapper',
			},
			enableSearch: true,
			dropdownMode: true,
			showAvatars: true,
			compactView: true,
			dynamicLoad: true,
			events: {
				'Item:onBeforeSelect': (event: BaseEvent) => {
					const dialog: Dialog = event.getTarget();
					dialog.deselectAll();
				},
			},
		});
	}

	#handleOnFooterLinkClick()
	{
		const item = this.#dialog.selectedItems.values()?.next()?.value;
		if (!(item instanceof Item))
		{
			return;
		}

		if (!Type.isFunction(this.#props.onSend))
		{
			return;
		}

		if (this.#sendInProgress)
		{
			return;
		}

		this.#dialog.showLoader();
		const notificationStack = new BX.UI.Notification.Stack({
			id: 'send-dashboard',
			offsetX: 20,
			offsetY: 80,
		});

		this.#sendInProgress = true;
		this.#props.onSend()
			.then((response) => {
				return Ajax.runComponentAction('bitrix:biconnector.apachesuperset.dashboard.detail', 'sendDashboardToChat', {
					mode: 'ajax',
					data: {
						dialogId: item.id,
						content: response,
						dashboardName: this.#props.dashboardName,
						fileExtension: this.#props.fileExtension,
					},
				});
			})
			.then(() => {
				this.#dialog.hideLoader();
				this.#dialog.hide();
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_SHARE_SUCCESS'),
					stack: notificationStack,
					autoHideDelay: 3000,
				});
				ApacheSupersetAnalytics.sendAnalytics('share', 'dashboard_share', {
					status: 'success',
					type: this.#props.dashboardType,
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#props.appId),
					p2: this.#props.dashboardId,
					p3: `ext_${this.#props.fileExtension}`,
				});
				this.#sendInProgress = false;
			})
			.catch((response) => {
				this.#dialog.hideLoader();
				console.error(response);
				BX.UI.Notification.Center.notify({
					content: response.errors[0].message,
					stack: notificationStack,
				});
				ApacheSupersetAnalytics.sendAnalytics('share', 'dashboard_share', {
					status: 'error',
					type: this.#props.dashboardType,
					p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.#props.appId),
					p2: this.#props.dashboardId,
					p3: `ext_${this.#props.fileExtension}`,
				});
				this.#sendInProgress = false;
			})
		;
	}

	#getHeader(): HTMLElement
	{
		return Tag.render`
			<span class="biconnector-send-to-chat-header">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_SHARE_RECEIVER')}
			</span>
		`;
	}

	#getFooter(): HTMLElement[]
	{
		return [
			Tag.render`
				<div class="biconnector-send-to-chat-footer" onclick="${this.#handleOnFooterLinkClick.bind(this)}}">
					<span class="ui-icon-set --send" style="--ui-icon-set__icon-size: 32px; --ui-icon-set__icon-color: #2FC6F6; margin-right: 10px;">
					</span>
					<span class="biconnector-send-to-chat-footer-text">
						${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_SHARE_SEND')}
					</span>
				</div>
			`,
		];
	}

	#getSelectorEntities(): Array
	{
		return [
			{
				id: 'im-chat',
				options: {
					searchableChatTypes: ['C', 'L', 'O'],
					fillDialog: false,
				},
			},
			{
				id: 'user',
				options: {
					fillDialog: false,
				},
				filters: [
					{
						id: 'im.userDataFilter',
					},
				],
			},
			{
				id: 'im-recent',
				options: {
					limit: 100,
				},
			},
		];
	}
}

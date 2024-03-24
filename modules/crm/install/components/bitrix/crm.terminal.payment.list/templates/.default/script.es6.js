import { Reflection, ajax, Loc, Text } from 'main.core';
import { QrAuth } from 'crm.terminal';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox } from 'ui.dialogs.messagebox';

const namespace = Reflection.namespace('BX.Crm.Component');

type SalescenterParams = {
	paymentId: number,
	orderId: number,
};

class TerminalPaymentList
{
	grid = null;
	settingsSliderUrl = '';

	constructor(options = {})
	{
		this.gridId = options.gridId;
		if (BX.Main.gridManager)
		{
			this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
		}

		this.settingsSliderUrl = options.settingsSliderUrl;

		EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const eventId = event.getData()[0].eventId;
			if (eventId === 'salescenter.app:onterminalpaymentupdated')
			{
				this.grid.reload();
			}
		});
	}

	setPaidStatus(id: number, value: string): void
	{
		this.grid.tableFade();
		ajax.runAction('crm.order.payment.setPaid', {
			data: {
				id,
				value,
			},
		}).then((response) => {
			this.grid.reload();
		}, (response) => {
			this.grid.tableUnfade();
			response.errors.forEach((error) => {
				BX.UI.Notification.Center.notify({
					content: Text.encode(error.message),
				});
			});
		});
	}

	deletePayment(id): void
	{
		MessageBox.confirm(
			Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_CONTENT'),
			(messageBox, button) => {
				button.setWaiting();

				ajax.runAction(
					'crm.order.payment.delete',
					{
						data: {
							id,
						},
					},
				).then((response) => {
					messageBox.close();
					this.grid.reload();
				}).catch((response) => {
					if (response.errors)
					{
						BX.UI.Notification.Center.notify({
							content: Text.encode(response.errors[0].message),
						});
					}

					messageBox.close();
				});
			},
			Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CONFIRM'),
			(messageBox) => messageBox.close(),
			Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_BACK'),
		);
	}

	deletePayments(): void
	{
		const paymentIds = this.grid.getRows().getSelectedIds();
		ajax.runAction(
			'crm.order.payment.deleteList',
			{
				data: {
					ids: paymentIds,
				},
			},
		).then((response) => {
			this.grid.reload();
		}).catch((response) => {
			if (response.errors)
			{
				response.errors.forEach((error) => {
					if (error.message)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(error.message),
						});
					}
				});
			}
			this.grid.reload();
		});
	}

	openTerminalSettingsSlider(event, menuItem): void
	{
		BX.SidePanel.Instance.open(this.settingsSliderUrl, {
			width: 900,
			cacheable: false,
			allowChangeTitle: false,
			allowChangeHistory: false,
		});
		menuItem.getMenuWindow().close();
	}

	openQrAuthPopup(): void
	{
		(new QrAuth()).show();
	}

	openHelpdesk(event, menuItem): void
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show('redirect=detail&code=17603024');
			event.preventDefault();
		}
		menuItem.getMenuWindow().close();
	}

	onGridUpdatedHandler(event: BaseEvent): void
	{
		const [grid] = event.getCompatData();

		if (grid && grid.getId() === this.gridId && grid.getRows().getCountDisplayed() === 0)
		{
			ajax.runComponentAction('bitrix:crm.terminal.payment.list', 'isRowsExists', {
				mode: 'class',
				data: {},
			}).then((response) => {
				if (response.data.IS_ROWS_EXIST === false)
				{
					window.location.reload();
				}
			}, () => {
				window.location.reload();
			});
		}
	}

	openPaymentInSalescenter(params: SalescenterParams): void
	{
		const options = {
			context: 'terminal_list',
			mode: 'terminal_payment',
			analyticsLabel: 'terminal_payment_list_view_payment',
			templateMode: 'view',
			orderId: params.orderId,
			paymentId: params.paymentId,
		};

		BX.Salescenter.Manager.openApplication(options);
	}
}

namespace.TerminalPaymentList = TerminalPaymentList;

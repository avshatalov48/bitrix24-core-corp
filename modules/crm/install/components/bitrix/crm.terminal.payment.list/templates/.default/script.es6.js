import {Reflection, ajax, Loc} from 'main.core';
import {Popup} from 'main.popup';
import {Button, ButtonColor} from 'ui.buttons';
import {QrAuth} from "crm.terminal";
import {type BaseEvent, EventEmitter} from "main.core.events";

const namespace = Reflection.namespace('BX.Crm.Component');

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
	}

	deletePayment(id)
	{
		let popup = new Popup({
			id: 'crm_terminal_payment_list_delete_popup',
			titleBar: Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_TITLE'),
			content: Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE_DELETE_CONTENT'),
			buttons: [
				new Button({
					text:  Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CONTINUE'),
					color: ButtonColor.SUCCESS,
					onclick: (button, event) => {
						button.setDisabled();

						ajax.runAction(
							'crm.order.terminalpayment.delete',
							{
								data: {
									id: id
								}
							}
						).then((response) => {
							popup.destroy();
							this.grid.reload();
						}).catch((response) => {
							if (response.errors)
							{
								BX.UI.Notification.Center.notify({
									content: BX.util.htmlspecialchars(response.errors[0].message),
								});
							}

							popup.destroy();
						});
					},
				}),
				new Button({
					text: Loc.getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_BUTTON_CANCEL'),
					color: ButtonColor.DANGER,
					onclick: (button, event) => {
						popup.destroy();
					}
				}),
			],
		});
		popup.show();
	}

	deletePayments()
	{
		let paymentIds = this.grid.getRows().getSelectedIds();
		ajax.runAction(
			'crm.order.terminalpayment.deleteList',
			{
				data: {
					ids: paymentIds,
				}
			}
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

	openSmsSettingsSlider()
	{
		BX.SidePanel.Instance.open(this.settingsSliderUrl, {
			width: 700,
			cacheable: false,
			allowChangeTitle: false,
			allowChangeHistory: false,
		});
	}

	openQrAuthPopup()
	{
		(new QrAuth).show();
	}

	openHelpdesk(event)
	{
		if(top.BX.Helper)
		{
			top.BX.Helper.show("redirect=detail&code=17603024");
			event.preventDefault();
		}
	}

	onGridUpdatedHandler(event: BaseEvent)
	{
		const [grid] = event.getCompatData();

		if (grid && grid.getId() === this.gridId && grid.getRows().getCountDisplayed() === 0)
		{
			ajax.runComponentAction('bitrix:crm.terminal.payment.list', 'isRowsExists', {
				mode: 'class',
				data: {},
			}).then(function (response) {
				if (response.data.IS_ROWS_EXIST === false)
				{
					window.location.reload();
				}
			}, function () {
				window.location.reload();
			});
		}
	}
}

namespace.TerminalPaymentList = TerminalPaymentList;
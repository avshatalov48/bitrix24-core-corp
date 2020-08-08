;(function()
{
	"use strict";
	BX.namespace("BX.Crm.Report.Dashboard.Content");

	BX.Crm.Report.Dashboard.Content.RegularCustomers = {
		renderBalloon: function(graphDataItem, graph)
		{
			var data = graphDataItem.dataContext.balloon;

			return '<div class="crm-report-regular-customers-modal" style="border-color: ' + data.color + ';">' +
			'			<div class="crm-report-regular-customers-modal-title">' +
							BX.message("CRM_REPORT_REGULAR_CUSTOMERS_CLIENTS_COUNT").replace("#COUNT#", data.countClients) +
			'			</div>' +
			'            <div class="crm-report-regular-customers-group-values">' +
			'				<div class="crm-report-regular-customers-modal-line">' +
			'					<div class="crm-report-regular-customers-modal-subtitle">' +
									BX.message("CRM_REPORT_REGULAR_CUSTOMERS_CONTACT_COUNT") +
			'					</div>' +
			'					<div class="crm-report-regular-customers-modal-value">' +
									data.countContact +
			'					</div>' +
			'				</div>' +
			'				<div class="crm-report-regular-customers-modal-line">' +
			'					<div class="crm-report-regular-customers-modal-subtitle">' +
									BX.message("CRM_REPORT_REGULAR_CUSTOMERS_COMPANY_COUNT") +
			'					</div>' +
			'					<div class="crm-report-regular-customers-modal-value">' +
									data.countCompany +
			'					</div>' +
			'				</div>' +
			' 			</div>' +
			'            <div class="crm-report-regular-customers-group-values">' +
			'				<div class="crm-report-regular-customers-modal-line"> ' +
			'					<div class="crm-report-regular-customers-modal-subtitle">' +
									BX.message("CRM_REPORT_REGULAR_CUSTOMERS_AMOUNT") +
			'					</div>' +
			'					<div class="crm-report-regular-customers-modal-value">' +
									data.totalAmountFormatted +
			'					</div>' +
			'				</div>' +
			'				<div class="crm-report-regular-customers-modal-line">' +
			'					<div class="crm-report-regular-customers-modal-subtitle">' +
									BX.message("CRM_REPORT_REGULAR_CUSTOMERS_EARNINGS_PERCENT") +
			'					</div>' +
			'					<div class="crm-report-regular-customers-modal-value">' +
									data.earningsPercent + '%' +
			'					</div>' +
			'				</div>' +
			'            </div>' +
				'</div>';
		},

		onItemClick: function(e)
		{
			var data = e.item.dataContext.balloon;

			if(data.countContact > 0 && data.countCompany > 0)
			{
				var menuItems = [
					{
						id: "showContacts",
						text: BX.message("CRM_REPORT_REGULAR_CUSTOMERS_OPEN_CONTACTS"),
						onclick: function()
						{
							BX.PopupMenu.getCurrentMenu().destroy();
							BX.SidePanel.Instance.open(data.contactsUrl, {cacheable: false});
						},
					},
					{
						id: "showCompanies",
						text: BX.message("CRM_REPORT_REGULAR_CUSTOMERS_OPEN_COMPANIES"),
						onclick: function()
						{
							BX.PopupMenu.getCurrentMenu().destroy();
							BX.SidePanel.Instance.open(data.companiesUrl, {cacheable: false});
						},
					},
				];

				BX.PopupMenu.show(
					'telephony-show-configurations',
					e.event,
					menuItems,
					{
						autoHide: true,
						closeByEsc: true
					}
				);
			}
			else if (data.countContact > 0)
			{
				BX.SidePanel.Instance.open(data.contactsUrl, {cacheable: false});
			}
			else
			{
				BX.SidePanel.Instance.open(data.companiesUrl, {cacheable: false});
			}

		}
	}
})();
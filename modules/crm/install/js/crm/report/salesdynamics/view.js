;(function()
{
	"use strict";
	BX.namespace("BX.Crm.Report.Dashboard.Content");

	BX.Crm.Report.Dashboard.Content.SalesDynamics = {
		renderBalloon: function(graphDataItem, graph)
		{
			var data = graphDataItem.dataContext.balloon;

			return '<div class="crm-report-sales-dynamics-modal" style="border-color: #F7CC00;">' +
				'     <div class="crm-report-sales-dynamics-modal-title">' +
				         BX.util.htmlspecialchars(data.title) +
				'    </div>' +
				'    <div class="crm-report-sales-dynamics-modal-main">' +
				'        <div class="crm-report-sales-dynamics-modal-subtitle">' + BX.message("CRM_REPORT_SALES_DYNAMICS_INITIAL") + '</div>' +
				'        <div class="crm-report-sales-dynamics-modal-content">' +
				'            <div class="crm-report-sales-dynamics-modal-value">' +
							    data.amountInitialFormatted +
				'            </div>' +
							 this.renderPercentBlock(data.amountInitial, data.amountInitialPrev) +
				'        </div>' +
				'        <div class="crm-report-sales-dynamics-modal-subtitle">' + BX.message("CRM_REPORT_SALES_DYNAMICS_RETURN") + '</div>' +
				'        <div class="crm-report-sales-dynamics-modal-content">' +
				'            <div class="crm-report-sales-dynamics-modal-value">' +
								data.amountReturnFormatted +
				'            </div>' +
							this.renderPercentBlock(data.amountReturn, data.amountReturnPrev) +
				'        </div>' +
				'        <div class="crm-report-sales-dynamics-modal-subtitle">' + BX.message("CRM_REPORT_SALES_DYNAMICS_TOTAL_AMOUNT") + '</div>' +
				'        <div class="crm-report-sales-dynamics-modal-content">' +
				'            <div class="crm-report-sales-dynamics-modal-value">' +
								data.amountTotalFormatted +
				'            </div>' +
						  	 this.renderPercentBlock(data.amountTotal, data.amountTotalPrev) +
				'        </div>' +
				'    </div>' +
				'</div>';
		},

		renderPercentBlock: function(currentAmount, prevAmount)
		{
			var ratio;
			if((typeof prevAmount) === "undefined" || currentAmount == prevAmount || currentAmount == 0 || prevAmount == 0)
			{
				return '<div style="color:grey;">&mdash;</div>';
			}
			else
			{
				ratio = currentAmount / prevAmount - 1;
			}

			var classList = "crm-report-sales-dynamics-modal-percent-value";
			var percent = Math.round(ratio * 100);
			if (percent > 0)
			{
				classList += " green";
			}
			else
			{
				classList += " red";
			}

			percent = (percent > 0 ? "+" : "") + percent.toString();

			return  '<div class="'+ classList +'">' + percent + '%</div>';
		},

	}
})();
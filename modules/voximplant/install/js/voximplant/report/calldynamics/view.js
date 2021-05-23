;(function()
{
	"use strict";
	BX.namespace("BX.Voximplant.Report.Dashboard.Content");

	BX.Voximplant.Report.Dashboard.Content.CallDynamics = {
		renderBalloon: function(graphDataItem, graph)
		{
			var data = graphDataItem.dataContext.balloon;

			return '<div class="telephony-report-call-dynamics-modal" style="border-color:' + graph.fillColors + '">' +
						'<div class="telephony-report-call-dynamics-modal-title">' +
							graphDataItem.category +
						'</div>' +
						'<div class="telephony-report-call-dynamics-modal-main">' +
							'<div class="telephony-report-call-dynamics-modal-subtitle">' + graph.title + '</div>' +
							'<div class="telephony-report-call-dynamics-modal-content">' +
								'<div class="telephony-report-call-dynamics-modal-value">' +
									data.count[graph.valueField] +
								'</div>' +
								this.renderPercentBlock(data.compare[graph.valueField]) +
							'</div>' +
						'</div>'+
					'</div>';
		},

		renderPercentBlock: function(value)
		{
			if(value === null || value == 0)
			{
				return '<div style="color:grey;">&mdash;</div>';
			}

			var classList = "telephony-report-call-dynamics-modal-percent-value";

			if (value > 0)
			{
				classList += " green";
			}
			else
			{
				classList += " red";
			}

			if (value%1 === 0)
			{
				value = Math.round(value);
			}

			value = (value > 0 ? "+" : "") + value.toString();

			return  '<div class="'+ classList +'">' + value + '%</div>';
		},
	}
})();
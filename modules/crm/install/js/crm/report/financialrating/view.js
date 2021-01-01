;(function()
{
	"use strict";
	BX.namespace("BX.Crm.Report.Dashboard.Content");

	BX.Crm.Report.Dashboard.Content.FinancialRating = function ()
	{
		am4core.options.autoSetClassName = true;
		am4core.options.classNamePrefix = "crm-fin-rating-";

		BX.Report.VisualConstructor.Widget.Content.AmCharts4.apply(this, arguments);
		this.chartWrapper.style.paddingTop = '0';
		this.chartWrapper.style.marginTop = '-10px';
	};

	BX.Crm.Report.Dashboard.Content.FinancialRating.prototype = {

		__proto__: BX.Report.VisualConstructor.Widget.Content.AmCharts4.prototype,
		constructor: BX.Crm.Report.Dashboard.Content.FinancialRating,

		onAfterChartCreate: function()
		{
			var scrollbar = new am4charts.XYChartScrollbar();
			scrollbar.id = "scrollbar-box";
			scrollbar.height = 70;
			scrollbar.series.push(this.chart.series._values[0]);

			// remove default "desaturate" filter,
			// @see https://www.amcharts.com/docs/v4/tutorials/customizing-chart-scrollbar/ :Re-enabling colors
			scrollbar.scrollbarChart.series.getIndex(0).filters.clear();
			scrollbar.scrollbarChart.xAxes.getIndex(0).renderer.grid.template.disabled = true;
			scrollbar.scrollbarChart.xAxes.getIndex(0).renderer.labels.template.disabled = true;

			scrollbar.background.fill = "#2fc6f6";
			scrollbar.background.fillOpacity = 0.12;
			scrollbar.thumb.background.states.getKey('hover').properties.fill = am4core.color("#2fc6f6");

			if (this.chart.data.length > 6)
			{
				scrollbar.end = 6 / this.chart.data.length;
			}

			this.chart.scrollbarX = scrollbar;

			var borderBottom = scrollbar.thumb.createChild(am4core.Rectangle);
			borderBottom.width = am4core.percent(100);
			borderBottom.height = 2;
			borderBottom.dy = 3;
			borderBottom.fill = "#2fc6f6";
			borderBottom.align = 'center';
			borderBottom.valign = 'bottom';

			function customizeGrip(grip) {
				grip.background.disabled = true;

				grip.children.clear();
				var img = grip.createChild(am4core.Image);
				img.href = 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2036%2036%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20xmlns%3Axlink%3D%22http%3A//www.w3.org/1999/xlink%22%3E%3Cdefs%3E%3Cfilter%20x%3D%22-10.9%25%22%20y%3D%22-7.8%25%22%20width%3D%22121.9%25%22%20height%3D%22121.9%25%22%20filterUnits%3D%22objectBoundingBox%22%20id%3D%22a%22%3E%3CfeOffset%20dy%3D%221%22%20in%3D%22SourceAlpha%22%20result%3D%22shadowOffsetOuter1%22/%3E%3CfeGaussianBlur%20stdDeviation%3D%221%22%20in%3D%22shadowOffsetOuter1%22%20result%3D%22shadowBlurOuter1%22/%3E%3CfeColorMatrix%20values%3D%220%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200.21131993%200%22%20in%3D%22shadowBlurOuter1%22/%3E%3C/filter%3E%3Ccircle%20id%3D%22b%22%20cx%3D%2216%22%20cy%3D%2216%22%20r%3D%2216%22/%3E%3C/defs%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20transform%3D%22translate%282%201%29%22%3E%3Cuse%20fill%3D%22%23000%22%20filter%3D%22url%28%23a%29%22%20xlink%3Ahref%3D%22%23b%22/%3E%3Cuse%20fill%3D%22%23FFF%22%20xlink%3Ahref%3D%22%23b%22/%3E%3C/g%3E%3Cpath%20fill%3D%22%23C5C5C5%22%20d%3D%22M15.521%2011h1.5v12h-1.5zM19%2011h1.5v12H19z%22/%3E%3C/g%3E%3C/svg%3E';
				img.width = 32;
				img.height = 32;
				img.align = "center";
				img.valign = "middle";
			}

			customizeGrip(this.chart.scrollbarX.startGrip);
			customizeGrip(this.chart.scrollbarX.endGrip);

			var columnSeries = this.chart.series.getIndex(0);

			// star for first three columns
			var bullet = columnSeries.columns.template.createChild(am4core.Image);
			bullet.propertyFields.href = "bullet";
			bullet.height = 30;
			bullet.width = 30;
			bullet.align = "left";
			bullet.valign = "top";
			bullet.dx = 5;
			bullet.dy = 5;

			columnSeries.columns.template.events.on('sizechanged', function (ev)
			{
				//resize axis labels
				var newWidth = ev.target.width;
				if(newWidth !== 0 && newWidth != this.currentColumnWidth)
				{
					this.currentColumnWidth = newWidth;
					this.chart.xAxes.getIndex(0).renderer.labels.template.maxWidth = newWidth;

					borderBottom.width = scrollbar.thumb.width;
				}
			}.bind(this));

			columnSeries.columns.template.adapter.add('tooltipHTML', this.renderBalloon.bind(this));
		},

		renderBalloon: function(text, target)
		{
			var data = target.dataItem.dataContext.balloon;

			var arrowClass = data.wonAmount >= data.wonAmountPrev ? 'crm-report-financial-rating-value-up' : 'crm-report-financial-rating-value-down';

			return '<div class="crm-report-financial-rating-modal" style="border-color: ' + data.color + ';">' +
			'			<div class="crm-report-financial-rating-modal-title">' +
							BX.util.htmlspecialchars(data.clientTitle) +
			'			</div>' +
			'            <div class="crm-report-financial-rating-group-values">' +
			'				<div class="crm-report-financial-rating-modal-line">' +
			'					<div class="crm-report-financial-rating-modal-value">' +
									data.wonAmountFormatted +
			'					</div>' +
			'				</div>' +
			'				<div class="crm-report-financial-rating-modal-line">' +
			'					<div class="crm-report-financial-rating-modal-value ' + arrowClass + '">' +
									data.wonAmountPrevFormatted +
			'					</div>' +
			'				</div>' +
			'            </div>' +
			'            <div class="crm-report-financial-rating-group-values">' +
			'				<div class="crm-report-financial-rating-modal-line">' +
			'					<div class="crm-report-financial-rating-modal-subtitle">' +
									BX.message("CRM_REPORT_FINANCIAL_RATING_DEALS_AVG_AMOUNT") +
			'					</div>' +
			'					<div class="crm-report-financial-rating-modal-value">' +
									data.avgWonAvgAmountFormatted +
			'					</div>' +
			'				</div>' +
			'				<div class="crm-report-financial-rating-modal-line">' +
			'					<div class="crm-report-financial-rating-modal-subtitle">' +
									BX.message("CRM_REPORT_FINANCIAL_RATING_DEALS_WON_COUNT") +
			'					</div>' +
			'					<div class="crm-report-financial-rating-modal-inner">' +
			'					<div class="crm-report-financial-rating-modal-value-box">' +
									this.getDealCountBlock(data.wonCount, data.totalCount) +
			'					</div>' +
			'					<div class="crm-report-financial-rating-modal-percent">' +
									this.getPercent(data.wonCount, data.totalCount) +
			'					</div>' +
			'					</div>' +
			'				</div>' +
			' 			</div>' +
				'</div>';
		},

		getDealCountBlock: function(wonCount, totalCount)
		{
			var wonCountBlock = '<div class="crm-report-financial-rating-modal-value">' + wonCount + '</div>';
			var totalCountBlock = '<div class="crm-report-financial-rating-modal-value">' + totalCount + '</div>';

			var result = BX.message('CRM_REPORT_FINANCIAL_RATING_DEALS_WON_OF');

			result = result.replace('#WON_COUNT#', wonCountBlock);
			result = result.replace('#TOTAL_COUNT#', totalCountBlock);

			return result;
		},

		getPercent: function(wonCount, totalCount)
		{
			var ratio;
			if(totalCount == 0)
			{
				return '&mdash;';
			}
			else
			{
				ratio = wonCount / totalCount;
				return  Math.round(ratio * 100).toString() + "%";
			}
		},
	}
})();

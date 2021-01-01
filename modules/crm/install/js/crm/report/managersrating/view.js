;(function ()
{
	"use strict";
	BX.namespace("BX.Crm.Report.Dashboard.Content");

	BX.Crm.Report.Dashboard.Content.ManagersRating = function ()
	{
		BX.Report.VisualConstructor.Widget.Content.AmCharts4.apply(this, arguments);
	};

	BX.Crm.Report.Dashboard.Content.ManagersRating.prototype = {

		__proto__: BX.Report.VisualConstructor.Widget.Content.AmCharts4.prototype,
		constructor: BX.Crm.Report.Dashboard.Content.ManagersRating,

		onAfterChartCreate: function ()
		{
			var series = this.chart.series.getIndex(0);
			var columnTemplate = series.columns.template;

			series.mainContainer.mask = undefined;

			var bullet = columnTemplate.createChild(am4charts.CircleBullet);
			bullet.circle.radius = 30;
			bullet.valign = "bottom";
			bullet.align = "center";
			bullet.isMeasured = true;
			bullet.mouseEnabled = false;
			bullet.verticalCenter = "bottom";
			bullet.interactionsEnabled = false;
			bullet.dy = -40;
			bullet.visible = false;
			bullet.states.create("hover");

			var outlineCircle = bullet.createChild(am4core.Circle);
			outlineCircle.adapter.add("radius", function (radius, target)
			{
				var circleBullet = target.parent;
				return circleBullet.circle.pixelRadius + 10;
			});

			var image = bullet.createChild(am4core.Image);
			image.width = 60;
			image.height = 60;
			image.horizontalCenter = "middle";
			image.verticalCenter = "middle";
			image.propertyFields.href = "bullet";

			image.adapter.add("mask", function (mask, target)
			{
				var circleBullet = target.parent;
				return circleBullet.circle;
			});

			// showing bullets after amcharts finishes columns height calculations
			columnTemplate.events.on('transitionended', this.onColumnTransitionEnded.bind(this));
			columnTemplate.events.on('over', this.onColumnOver.bind(this));
			columnTemplate.events.on('out', this.onColumnOut.bind(this));

			// tooltip renderer
			columnTemplate.adapter.add('tooltipHTML', this.renderBalloon.bind(this));
		},

		onColumnTransitionEnded: function (ev)
		{
			var height = ev.target.maxHeight;
			var bullet = ev.target.children.getIndex(1);

			if (height === 0)
			{
				return;
			}

			if (height > 60)
			{
				bullet.visible = true;
			}
			else
			{
				bullet.visible = false;
				ev.target.column.cornerRadius(10, 10, 3, 3);
			}
		},

		onColumnOver: function (ev)
		{
			var bullet = ev.target.children.getIndex(1);

			var hs = bullet.states.getKey("hover");
			hs.properties.dy = -bullet.parent.pixelHeight + 30;
			bullet.isHover = true;
		},

		onColumnOut: function (ev)
		{
			var bullet = ev.target.children.getIndex(1);
			bullet.isHover = false;
		},

		renderBalloon: function (text, target)
		{
			var data = target.dataItem.dataContext.balloon;
			var color = target.fill.hex;

			var arrowClass = data.amountWon >= data.amountWonPrev ? 'crm-report-financial-rating-value-up' : 'crm-report-financial-rating-value-down';

			return '<div class="crm-report-financial-rating-modal" style="border-color: ' + color + ';">' +
				'			<div class="crm-report-financial-rating-modal-title">' +
				BX.util.htmlspecialchars(data.userName) +
				'			</div>' +
				'            <div class="crm-report-financial-rating-group-values">' +
				'				<div class="crm-report-financial-rating-modal-line">' +
				'					<div class="crm-report-financial-rating-modal-value">' +
				data.amountWonFormatted +
				'					</div>' +
				'				</div>' +
				'				<div class="crm-report-financial-rating-modal-line">' +
				'					<div class="crm-report-financial-rating-modal-value ' + arrowClass + '">' +
				data.amountWonPrevFormatted +
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
				this.getDealCountBlock(data.countWon, data.countTotal) +
				'					</div>' +
				'					<div class="crm-report-financial-rating-modal-percent">' +
				this.getPercent(data.countWon, data.countTotal) +
				'					</div>' +
				'					</div>' +
				'				</div>' +
				' 			</div>' +
				'</div>';
		},

		getDealCountBlock: function (wonCount, totalCount)
		{
			var wonCountBlock = '<div class="crm-report-financial-rating-modal-value">' + wonCount + '</div>';
			var totalCountBlock = '<div class="crm-report-financial-rating-modal-value">' + totalCount + '</div>';

			var result = BX.message('CRM_REPORT_FINANCIAL_RATING_DEALS_WON_OF');

			result = result.replace('#WON_COUNT#', wonCountBlock);
			result = result.replace('#TOTAL_COUNT#', totalCountBlock);

			return result;
		},

		getPercent: function (wonCount, totalCount)
		{
			var ratio;
			if (totalCount == 0)
			{
				return '&mdash;';
			}
			else
			{
				ratio = wonCount / totalCount;
				return Math.round(ratio * 100).toString() + "%";
			}
		},
	}
})();

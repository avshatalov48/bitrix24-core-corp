;(function()
{
	"use strict";
	BX.namespace("BX.Voximplant.Report.Dashboard.Content");

	BX.Voximplant.Report.Dashboard.Content.MissedReaction = function ()
	{
		BX.Report.VisualConstructor.Widget.Content.AmCharts4.apply(this, arguments);
	};

	BX.Voximplant.Report.Dashboard.Content.MissedReaction.prototype = {

		__proto__: BX.Report.VisualConstructor.Widget.Content.AmCharts4.prototype,
		constructor: BX.Voximplant.Report.Dashboard.Content.MissedReaction,

		onAfterChartCreate: function ()
		{
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

			if (this.chart.data.length > 5)
			{
				var scrollbar = new am4charts.XYChartScrollbar();
				scrollbar.id = "scrollbar-box";
				scrollbar.height = 70;

				this.chartWrapper.style.paddingTop = '0px';
				this.chartWrapper.style.marginTop = '-10px';
				scrollbar.dy = -14;

				scrollbar.series.push(this.chart.series._values[0]);

				// remove default "desaturate" filter,
				// @see https://www.amcharts.com/docs/v4/tutorials/customizing-chart-scrollbar/ :Re-enabling colors
				scrollbar.scrollbarChart.series.getIndex(0).filters.clear();

				scrollbar.scrollbarChart.xAxes.getIndex(0).renderer.grid.template.disabled = true;
				scrollbar.scrollbarChart.xAxes.getIndex(0).renderer.labels.template.disabled = true;

				scrollbar.background.fill = "#fda505";
				scrollbar.background.fillOpacity = 0.12;
				scrollbar.thumb.background.states.getKey('hover').properties.fill = am4core.color("#fda505");

				scrollbar.end = 5 / this.chart.data.length;

				this.chart.scrollbarX = scrollbar;

				var borderBottom = scrollbar.thumb.createChild(am4core.Rectangle);
				borderBottom.width = am4core.percent(100);
				borderBottom.height = 2;
				borderBottom.dy = 3;
				borderBottom.fill = "#fda505";
				borderBottom.align = 'center';
				borderBottom.valign = 'bottom';

				customizeGrip(this.chart.scrollbarX.startGrip);
				customizeGrip(this.chart.scrollbarX.endGrip);
			}

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

			if (bullet.parent.pixelHeight > 70)
			{
				hs.properties.dy = -bullet.parent.pixelHeight + 30;
				bullet.isHover = true;
			}
		},

		onColumnOut: function (ev)
		{
			var bullet = ev.target.children.getIndex(1);
			bullet.isHover = false;
		},

		renderBalloon: function(text, target)
		{
			var data = target.dataItem.dataContext.balloon;
			var count = data.count;
			var compare = data.compare;

			var ballon = '<div class="telephony-report-missed-reaction-modal" style="border-color:' + target.fill.hex +'">' +
						'<div class="telephony-report-missed-reaction-modal-title">' +
							BX.util.htmlspecialchars(target.dataItem.dataContext.groupingField) +
						'</div>' +
						'<div class="telephony-report-missed-reaction-modal-subtitle-secondary">' +
						BX.message('TELEPHONY_REPORT_MISSED_REACTION_MISSED') + '<br>' +
						'</div>' +
						'<div class="telephony-report-missed-reaction-modal-main">' +
							'<div class="telephony-report-missed-reaction-modal-content">' +
								'<div class="telephony-report-missed-reaction-modal-value">' +
									count['value_1'] +
								'</div>' +
							'</div>' +
						'</div>'+
						'<div class="telephony-report-missed-reaction-modal-subtitle-secondary">' +
							BX.message('TELEPHONY_REPORT_MISSED_REACTION_UNANSWERED') + '<br>' +
						'</div>' +
						'<div class="telephony-report-missed-reaction-modal-main">' +
							'<div class="telephony-report-missed-reaction-modal-content">' +
								'<div class="telephony-report-missed-reaction-modal-value">' +
									count['value_2'] +
								'</div>' +
							'</div>' +
						'</div>';

			if (count['value_3'])
			{
				ballon += '<div class="telephony-report-missed-reaction-modal-subtitle-secondary">' +
								BX.message('TELEPHONY_REPORT_MISSED_REACTION_AVG_RESPONSE_TIME') + '<br>' +
							'</div>' +
							'<div class="telephony-report-missed-reaction-modal-main">' +
								'<div class="telephony-report-missed-reaction-modal-content">' +
									'<div class="telephony-report-missed-reaction-modal-value">' +
										count['value_3'] +
									'</div>' +
									this.renderPercentBlock(compare['value_3'], compare['value_3_formatted']) +
								'</div>' +
							'</div>';
			}

			ballon += '</div>';

			return ballon;
		},

		renderPercentBlock: function(value, formatted)
		{
			if(value === null || value == 0)
			{
				return '<div style="color:grey;">&mdash;</div>';
			}

			var classList = "telephony-report-missed-reaction-modal-percent-value";

			if (value > 0)
			{
				classList += " red";
				formatted = '+' + formatted;
			}
			else
			{
				classList += " green";
				formatted = '-' + formatted;
			}

			return  '<div class="'+ classList +'">' + formatted +'</div>';
		},

		handleItemClick: function(event)
		{
			if(!event.target.hasOwnProperty('valueUrl') || !BX.type.isNotEmptyString(event.target.valueUrl))
			{
				return;
			}

			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(event.target.valueUrl, {
					cacheable: false,
					loader: "voximplant:grid-loader",
				});
			}
			else
			{
				window.open(event.target.valueUrl);
			}
		},
	}
})();
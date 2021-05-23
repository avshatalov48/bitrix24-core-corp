;(function()
{
	"use strict";
	BX.namespace("BX.Voximplant.Report.Dashboard.Content");

	BX.Voximplant.Report.Dashboard.Content.PeriodCompare = {
		filter: null,
		timePeriodField: null,
		previousPeriodField: null,
		init: function(filterId)
		{
			setTimeout(function()
			{
				this.filter = BX.Main.filterManager.getById(filterId);
				if(this.filter)
				{
					this.filter.getEmitter().subscribe('BX.Filter.Field:init', this.onFilterFieldInit.bind(this));
				}
				else
				{
					console.error("Filter " + filterId + " is not found");
				}

			}.bind(this), 500);
		},

		renderBalloon: function(graphDataItem, graph)
		{
			var data = graphDataItem.dataContext.balloon;

			data.count['value_1'] = data.count['value_1'] || "&mdash;";
			data.count['value_2'] = data.count['value_2'] || "&mdash;";
			graphDataItem.category = graphDataItem.category.replace('<br>', ' / ');

			return '<div class="telephony-report-period-compare-modal" style="border-color: #F7CC00">' +
						'<div class="telephony-report-period-compare-modal-title">' +
							graphDataItem.category +
						'</div>' +
						'<div class="telephony-report-period-compare-modal-main">' +
							'<div class="telephony-report-period-compare-modal-subtitle-primary">' +
								data.callType + '<br><br>' +
							'</div>' +
							'<div class="telephony-report-period-compare-modal-subtitle-secondary">' +
								BX.message("TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_CURRENT") +
							'</div>' +
							'<div class="telephony-report-period-compare-modal-content">' +
								'<div class="telephony-report-period-compare-modal-value">' +
									data.count['value_1'] +
								'</div>' +
								this.renderPercentBlock(data.compare) +
							'</div>' +
							'<div class="telephony-report-period-compare-modal-subtitle-secondary">' +
								BX.message("TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_PREVIOUS") +
							'</div>' +
							'<div class="telephony-report-period-compare-modal-content">' +
								'<div class="telephony-report-period-compare-modal-value">' +
									data.count['value_2'] +
								'</div>' +
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

			var classList = "telephony-report-period-compare-modal-percent-value";

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

		onFilterFieldInit: function(event)
		{
			var field = event.data.field;

			if (field.id === 'TIME_PERIOD')
			{
				this.timePeriodField = field;

				field.subscribe('BX.Filter.Field:change', this.onTimePeriodChange.bind(this));
			}
			else if (field.id === 'PREVIOUS_TIME_PERIOD')
			{
				this.previousPeriodField = field;
			}
		},

		onTimePeriodChange: function(event)
		{
			var previousValue = this.preparePreviousPeriodValue(event.data.value);

			this.previousPeriodField.setValue(previousValue);
		},

		preparePreviousPeriodValue: function(currentPeriodValue)
		{
			var result =  {
				_datesel: "",
				_from: "",
				_to: ""
			};
			var currentDate = new Date();
			var monthToQuarter = [1,1,1,2,2,2,3,3,3,4,4,4];
			var dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE'));

			switch (currentPeriodValue._datesel)
			{
				case 'CURRENT_WEEK':
					result._datesel = "LAST_WEEK";
					break;
				case 'CURRENT_MONTH':
					result._datesel = "LAST_MONTH";
					break;
				case 'CURRENT_QUARTER':
					result._datesel = "QUARTER";
					var currentQuarter = monthToQuarter[currentDate.getMonth()];
					if(currentQuarter == 1)
					{
						result._quarter = 4;
						result._year = currentDate.getFullYear() - 1;
					}
					else
					{
						result._quarter = 3;
						result._year = currentDate.getFullYear();
					}
					break;
				case 'QUARTER':
					result._datesel = "QUARTER";
					if(currentPeriodValue._quarter == 1)
					{
						result._quarter = 4;
						result._year = currentPeriodValue._year - 1;
					}
					else
					{
						result._quarter = currentPeriodValue._quarter - 1;
						result._year = currentPeriodValue._year;
					}
					break;
				case 'LAST_7_DAYS':
					result._datesel = "RANGE";
					this.setPreviousPeriodDates(7, result);
					break;
				case 'LAST_30_DAYS':
					result._datesel = "RANGE";
					this.setPreviousPeriodDates(30, result);
					break;
				case 'LAST_60_DAYS':
					result._datesel = "RANGE";
					this.setPreviousPeriodDates(60, result);
					break;
				case 'LAST_90_DAYS':
					result._datesel = "RANGE";
					this.setPreviousPeriodDates(90, result);
					break;
				case 'LAST_WEEK':
					result._datesel = "RANGE";
					var weekStart = new Date();
					weekStart.setDate(weekStart.getDate() - weekStart.getDay() + 1 - 14);
					var weekEnd = new Date(weekStart.valueOf());
					weekEnd.setDate(weekEnd.getDate() + 6);
					result._from = BX.date.format(dateFormat, weekStart);
					result._to = BX.date.format(dateFormat, weekEnd);
					break;
				case 'LAST_MONTH':
					result._datesel = "MONTH";
					if(currentDate.getMonth() <= 1)
					{
						result._month = 10 + currentDate.getMonth();
						result._year = currentDate.getFullYear() - 1;
					}
					else
					{
						result._month = currentDate.getMonth() - 1; // in js months are 0-11, but in filter months are 1-12
						result._year = currentDate.getFullYear();
					}
					break;
				case 'MONTH':
					result._datesel = "MONTH";
					result._month = currentDate.getMonth() === 0 ? 11 : currentDate.getMonth(); // in js months are 0-11, but in filter months are 1-12
					result._year = currentDate.getMonth() === 0 ? currentDate.getFullYear() - 1 : currentDate.getFullYear();
					break;
				case 'YEAR':
					result._datesel = "YEAR";
					result._year = currentDate.getFullYear() - 1;
					break;
				case 'RANGE':
					result._datesel = "RANGE";
					break;
			}

			for(var key in result)
			{
				if(result.hasOwnProperty(key))
				{
					result[key] = result[key].toString();
				}
			}

			return result;
		},

		setPreviousPeriodDates: function(periodLength, result)
		{
			var dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE'));
			var from = new Date();
			from.setDate(from.getDate() - periodLength * 2 + 1);
			result._from = BX.date.format(dateFormat, from);
			var to = new Date();
			to.setDate(to.getDate() - periodLength + 1);
			result._to = BX.date.format(dateFormat, to);
		}
	}
})();
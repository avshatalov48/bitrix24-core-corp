;(function ()
{
	"use strict";
	/**
	 * @namespace BX.Voximplant.Report.Widget
	 */
	BX.namespace('BX.Voximplant.Report.Widget');

	/**
	 *
	 * @param options
	 * @constructor
	 */
	BX.Voximplant.Report.Widget.Activity = function (options)
	{
		this.container = options.renderTo;
		this.options = options;
		this.items = options.items;
		this.days = options.labelY;
		this.hours = options.labelX;
		this.workingHours = options.workingHours;
		this.tooltips = options.tooltips;
		this.popup = null;
		this.item = null;
		this.scaleVertical = null;
		this.scaleHorizontal = null;
		this.handlerHybridWidget = null;
		this.handlerHoryzontalWidget = null;
		this.handlerVerticalWidget = null;
	};

	BX.Voximplant.Report.Widget.Activity.prototype =
		{
			/**
			 *
			 * @param {Object} items
			 * @returns {number}
			 */
			getMaxActivity: function (items)
			{

				var arrayItems = this.items;

				if(items !== undefined)
				{
					arrayItems = items;
				}

				var maxActivity = 0;

				for (var item in arrayItems)
				{
					if(arrayItems.hasOwnProperty(item))
					{
						arrayItems[item].active > maxActivity ? maxActivity = arrayItems[item].active : null;
					}
				}

				return maxActivity;
			},

			/**
			 *
			 * @returns {DocumentFragment|*}
			 */
			getDayScale: function ()
			{
				var daysBlock = document.createDocumentFragment();
				var className = 'reports-activity-day';
				var classDay;

				for (var day in this.days)
				{
					if(this.days.hasOwnProperty(day))
					{
						classDay = '';
						this.days[day].light ? classDay = ' reports-activity-day-light' : null;

						daysBlock.appendChild(BX.create('div',{
							attrs: {
								className: className + classDay
							},
							children: [
								BX.create('div', {
									attrs: {
										className: 'reports-activity-scale-item'
									},
									text: this.days[day].name
								})
							]
						}))
					}
				}

				return daysBlock;
			},

			/**
			 *
			 * @returns {DocumentFragment|*}
			 */
			getHourScale: function ()
			{
				var hoursBlock = document.createDocumentFragment();
				var className = 'reports-activity-hour';
				var classWorkTime = null;
				var classAnchor;

				for (var hour in this.workingHours.active)
				{
					if(this.workingHours.active.hasOwnProperty(hour))
					{
						classWorkTime = '';
						classAnchor = '';
						this.workingHours.active[hour].show ? classAnchor = ' reports-activity-hour-show' : null;

						if (this.workingHours.active[hour].firstHalf)
						{
							classWorkTime = ' reports-activity-hour-work-time-first';
						}
						else if (this.workingHours.active[hour].lastHalf)
						{
							classWorkTime = ' reports-activity-hour-work-time-last';
						}
						else if (this.workingHours.active[hour].active)
						{
							classWorkTime = ' reports-activity-hour-work-time';
						}

						hoursBlock.appendChild(BX.create('div',{
							attrs: {
								className: className + classAnchor + classWorkTime
							},
							children: [
								BX.create('div', {
									attrs: {
										className: 'reports-activity-scale-item'
									},
									text: this.workingHours.active[hour].name === 0 ? '0' : this.workingHours.active[hour].name
								})
							]
						}))
					}
				}

				return hoursBlock;
			},

			/**
			 *
			 * @returns {DocumentFragment|*}
			 */
			getActivityScale: function ()
			{
				var activityScaleBlock = document.createDocumentFragment();
				var numberParam = 0;

				for (var i = 1; i <= 3; i++)
				{
					i === 2 ? numberParam = Math.round(this.getMaxActivityArray() / 2) : null;
					i === 3 ? numberParam = this.getMaxActivityArray() : null;

					activityScaleBlock.appendChild(BX.create('div', {
						attrs: {
							className: 'reports-activity-active'
						},
						children: [
							BX.create('div', {
								attrs: {
									className: 'reports-activity-scale-item'
								},
								text: numberParam === 0 ? '0' : numberParam
							})
						]
					}))
				}

				return activityScaleBlock
			},

			/**
			 *
			 * @returns {DocumentFragment|*}
			 */
			getActivityDayScale: function (type)
			{
				var activityScaleBlock = document.createDocumentFragment();
				var numberParam = 0;

				for (var i = 1; i <= 3; i++)
				{
					i === 2 ? numberParam = Math.round(this.getMaxActivity(this.getMaxDayActivity()) / 2) : null;
					i === 3 ? numberParam = this.getMaxActivity(this.getMaxDayActivity()) : null;

					activityScaleBlock.appendChild(BX.create('div', {
						attrs: {
							className: 'reports-activity-active'
						},
						children: [
							BX.create('div', {
								attrs: {
									className: 'reports-activity-scale-item'
								},
								text: numberParam === 0 ? '0' : numberParam
							})
						]
					}))
				}

				return activityScaleBlock
			},

			/**
			 *
			 * @returns {Array}
			 */
			getMaxDayActivity: function ()
			{
				var daysActivity = [];

				for (var active in this.days)
				{
					if(this.days.hasOwnProperty(active))
					{
						daysActivity.push(
							{
								active: this.getDayTotalActivity(this.days[active])[0]
							}
						)
					}
				}

				return daysActivity
			},

			/**
			 *
			 * @returns {Array}
			 */
			getMaxHourActivity: function ()
			{
				var daysActivity = [];

				for (var active in this.hours)
				{
					if(this.days.hasOwnProperty(active))
					{
						daysActivity.push(
							{
								active: this.getHourTotalActivity(this.hours[active])[0]
							}
						)
					}
				}

				return daysActivity
			},

			/**
			 *
			 * @returns {Element}
			 */
			getTotalActivityGraph: function ()
			{
				var tableBlock = BX.create('div', {
					attrs: {
						className: 'reports-activity-table'
					}
				});

				for (var row in this.days)
				{
					if(this.days.hasOwnProperty(row))
					{
						var tr = BX.create('div', {
							attrs: {
								className: 'reports-activity-table-row'
							}
						});

						for (var col in this.hours)
						{
							if(this.hours.hasOwnProperty(col))
							{
								var td = BX.create('div', {
									attrs: {
										className: 'reports-activity-table-cell'
									},
									style: {
										animationDelay: Math.random().toFixed(2) + 's'
									}
								});

								td.appendChild(this.getTotalActivityItem(this.days[row], this.hours[col]));
								tr.appendChild(td);
							}
						}

						tableBlock.appendChild(tr)
					}
				}

				return tableBlock
			},

			/**
			 *
			 * @param {Object} day
			 * @param {Object} hour
			 * @returns {Element}
			 */
			getTotalActivityItem: function (day, hour)
			{
				var itemBlock = BX.create('div', {
					attrs: {
						className: 'reports-activity-table-item'
					}
				});

				var itemBlockBind = BX.create('div', {
					attrs: {
						className: 'reports-activity-table-item-bind'
					}
				});

				itemBlock.appendChild(itemBlockBind);

				var itemObj = {};

				for (var item in this.items)
				{
					if(this.items.hasOwnProperty(item))
					{
						if(day.id === this.items[item].labelYid && hour.id === this.items[item].labelXid)
						{
							itemObj = this.items[item];

							var priorityIndex = this.getPriorityItemIndex(item);
							BX.style(itemBlock, 'background', this.tooltips[priorityIndex].color);
							BX.style(itemBlock, 'opacity', this.getOpacity(this.items[item].tooltip[priorityIndex]));
							BX.bind(itemBlock, 'mouseenter', function ()
							{
								this.showPopup(itemBlockBind, itemObj)
							}.bind(this));
							BX.bind(itemBlock, 'mouseleave', this.destroyPopup.bind(this));

							if (priorityIndex === 1)
							{
								BX.adjust(itemBlock, { attrs: { 'data-target': this.items[item].targetUrl } });
								BX.bind(itemBlock, 'click', this.handleItemClick.bind(this));
							}
						}
					}
				}

				return itemBlock
			},

			handleItemClick: function(e)
			{
				if(BX.SidePanel)
				{
					BX.SidePanel.Instance.open(e.currentTarget.dataset.target, {
						cacheable: false,
						loader: "voximplant:grid-loader"
					});
				}
				else
				{
					window.open(e.currentTarget.dataset.target);
				}
			},

			getPriorityItemIndex: function(itemIndex)
			{
				return this.items[itemIndex].tooltip[1] > 0 ? 1 : 0;
			},

			/**
			 *
			 * @param {Element} targetNode
			 * @param {Object} param
			 */
			showPopup: function (targetNode, param)
			{
				var workTime = (param.labelXid - 1) + ':00 - ' + param.labelXid + ':00';

				if(param.labelYid)
				{
					workTime = this.days[param.labelYid - 1].name;
				}

				if (param.labelYid === 0)
				{
					workTime = this.days[6].name;
				}

				if(param.labelYid && param.labelXid)
				{
					workTime += ', ' + (param.labelXid - 1) + ':00 - ' + param.labelXid + ':00';
				}

				var tooltips = [
					BX.create('div', {
						attrs: {
							className: 'reports-activity-popup-work-time'
						},
						text: workTime
					}),
				];

				for (var index = 0; index < param.tooltip.length; index++)
				{
					tooltips.push(
						BX.create('div', {
							attrs: { className: 'reports-activity-popup-multiple-active' },
							children: [
								BX.create('span', {
									attrs: {
										className: 'reports-activity-popup-active-marker',
									},
									style: {
										background: this.tooltips[index].color,
									}
								}),
								BX.create('span', {
									attrs: { className: 'reports-activity-popup-active-value' },
									text: param.tooltip[index]
								}),
								BX.create('span', {
									attrs: {
										className: 'reports-activity-popup-active-value-text'
									},
									text: this.tooltips[index].title
								})
							]
						})
					);
				}

				var content = BX.create('div', {
					attrs: { className: 'reports-activity-popup' },
					children: tooltips
				});

				this.popup = new BX.PopupWindow('reports-activity-popup', targetNode, {
					className: 'reports-activity-popup-pointer-events',
					content: content,
					angle: {
						position: 'bottom',
						offset : 20
					},
					offsetTop: -9,
					zIndex: 9999,
					bindOptions: {
						position: 'top'
					}
				});

				this.popup.show()
			},

			destroyPopup: function ()
			{
				this.popup.destroy();
				this.popup = null;
			},

			/**
			 *
			 * @param {number} active
			 * @returns {string}
			 */
			getOpacity: function (active)
			{
				var activityIndex = Math.round((100 / this.getMaxActivity(this.items)) * active);
				var opacity = '.' + activityIndex;

				activityIndex <= 20 ? opacity = '.15' : null;
				(activityIndex > 20) && (activityIndex <= 40 ) ? opacity = '.3' : null;
				(activityIndex > 40) && (activityIndex <= 60 ) ? opacity = '.5' : null;
				(activityIndex > 60) && (activityIndex <= 80 ) ? opacity = '.7' : null;
				(activityIndex > 80) && (activityIndex <= 100 ) ? opacity = '.9' : null;
				activityIndex > 100 ? opacity = '1' : null;


				return opacity
			},

			/**
			 *
			 * @param {Object} hourObj
			 * @returns {number}
			 */
			getHourTotalActivity: function (hourObj)
			{
				var hourActivity = [];

				for (var item in this.items)
				{
					if(this.items.hasOwnProperty(item))
					{
						if (this.items[item].labelXid === hourObj.id)
						{
							for (var itemIndex = 0; itemIndex < this.items[item].tooltip.length; itemIndex++)
							{
								if (hourActivity[itemIndex])
								{
									hourActivity[itemIndex] += this.items[item].tooltip[itemIndex];
								}
								else
								{
									hourActivity[itemIndex] = this.items[item].tooltip[itemIndex];
								}
							}
						}
					}
				}

				return hourActivity
			},

			/**
			 *
			 * @param {Object} dayObj
			 * @returns {number}
			 */
			getDayTotalActivity: function (dayObj)
			{
				var dayActivity = [];

				for (var item in this.items)
				{
					if(this.items.hasOwnProperty(item))
					{
						if (this.items[item].labelYid === dayObj.id)
						{
							for (var itemIndex = 0; itemIndex < this.items[item].tooltip.length; itemIndex++)
							{
								if (dayActivity[itemIndex])
								{
									dayActivity[itemIndex] += this.items[item].tooltip[itemIndex];
								}
								else
								{
									dayActivity[itemIndex] = this.items[item].tooltip[itemIndex];
								}
							}
						}
					}
				}
				return dayActivity
			},

			/**
			 *
			 * @returns {Element}
			 */
			getHorizontalWidget: function ()
			{
				var horizontalWidget = BX.create('div', {
					attrs: { className: 'reports-activity-horizontal-widget' }
				});

				for (var col in this.hours)
				{
					if(this.hours.hasOwnProperty(col))
					{
						horizontalWidget.appendChild(this.getHorizontalWidgetItem(this.hours[col]))
					}
				}

				return horizontalWidget
			},

			getMaxActivityArray: function() {
				var maxActivity = 0;

				for (var cols in this.hours)
				{
					if(this.hours.hasOwnProperty(cols))
					{
						maxActivity < this.getHourTotalActivity(this.hours[cols])[0] ? maxActivity = this.getHourTotalActivity(this.hours[cols])[0] : null
					}
				}

				return maxActivity
			},

			/**
			 *
			 * @param {Object} colObj
			 * @returns {Element}
			 */
			getHorizontalWidgetItem: function (colObj)
			{
				var columnHeight = 0;
				var hourTotalActivity = this.getHourTotalActivity(colObj);
				if (hourTotalActivity[0])
				{
					columnHeight = (100 / this.getMaxActivityArray()) * hourTotalActivity[0];
				}

				var targetBlock = BX.create('div', {
					attrs: { className: 'reports-activity-horizontal-widget-item-bind' }
				});

				var events = {
					mouseenter: function ()
					{
						this.showPopup(
							targetBlock,
							{
								labelXid: colObj.id,
								active: hourTotalActivity[0],
								tooltip: hourTotalActivity
							}
						);
					}.bind(this),
					mouseleave: this.destroyPopup.bind(this)
				};

				return BX.create('div', {
					attrs: {
						className: (columnHeight === 0 ) ? 'reports-activity-horizontal-widget-item-empty' : 'reports-activity-horizontal-widget-item'
					},
					style: {
						maxHeight: columnHeight !== Infinity ? columnHeight + '%' : null,
						animationDelay: Math.random().toFixed(2) + 's'
					},
					children: [
						targetBlock
					],
					events: columnHeight === 0 ? null : events
				});
			},

			/**
			 *
			 * @returns {Element}
			 */
			getVerticalWidget: function ()
			{
				var verticalWidget = BX.create('div', {
					attrs: { className: 'reports-activity-vertical-widget' }
				});

				for (var row in this.days)
				{
					if(this.days.hasOwnProperty(row))
					{
						verticalWidget.appendChild(
							this.getVerticalWidgetItem(this.days[row], this.getMaxActivity(this.getMaxDayActivity()))
						)
					}
				}

				return verticalWidget
			},

			/**
			 *
			 * @param {Object} rowObj
			 * @param {number} maxActivity
			 * @returns {Element}
			 */
			getVerticalWidgetItem: function (rowObj, maxActivity)
			{
				var rowWidth = 0;
				var dayTotalActivity = this.getDayTotalActivity(rowObj);
				if (dayTotalActivity[0])
				{
					rowWidth = (100 / maxActivity) * dayTotalActivity[0];
				}

				var targetBlock = BX.create('div', {
					attrs: { className: 'reports-activity-vertical-widget-item-bind' }
				});

				var events =  {
					mouseenter: function ()
					{
						this.showPopup(
							targetBlock,
							{
								labelYid: rowObj.id,
								active: dayTotalActivity[0],
								tooltip: dayTotalActivity
							}
						);
					}.bind(this),
					mouseleave: this.destroyPopup.bind(this)
				};

				return BX.create('div', {
					attrs: {
						className: (rowWidth === 0 ) ? 'reports-activity-vertical-widget-item-empty' : 'reports-activity-vertical-widget-item'
					},
					style: {
						maxWidth: rowWidth + '%',
						animationDelay: Math.random().toFixed(2) + 's'
					},
					children: [
						targetBlock
					],
					events: rowWidth === Infinity ? null : events
				});
			},

			/**
			 *
			 * @returns {Element}
			 */
			getHandler: function ()
			{
				var handlerContainer = BX.create('div',{
					attrs: { className: 'reports-activity-handler' }
				});

				this.handlerHybridWidget = BX.create('div',{
					attrs: {
						className: 'reports-activity-handler-item reports-activity-handler-item-active'
					},
					text: BX.message('ACTIVITY_WIDGET_DAY_AND_HOUR_TITLE'),
					events: {
						click: function ()
						{
							if(this.handlerHybridWidget.classList.contains('reports-activity-handler-item-active'))
							{
								return
							}

							BX.removeClass(this.handlerVerticalWidget, 'reports-activity-handler-item-active');
							BX.removeClass(this.handlerHoryzontalWidget, 'reports-activity-handler-item-active');
							BX.addClass(this.handlerHybridWidget, 'reports-activity-handler-item-active');
							BX.cleanNode(this.widgetContainer);
							BX.cleanNode(this.scaleVertical);
							BX.cleanNode(this.scaleHorizontal);
							BX.removeClass(this.scaleVertical, 'reports-activity-widget-left-reverse');
							this.scaleHorizontal.appendChild(this.getHourScale());
							this.scaleVertical.appendChild(this.getDayScale());
							this.widgetContainer.appendChild(this.getTotalActivityGraph())
						}.bind(this)
					}
				});

				this.handlerVerticalWidget = BX.create('div',{
					attrs: {
						className: 'reports-activity-handler-item'
					},
					text: BX.message('ACTIVITY_WIDGET_HOUR_TITLE'),
					events: {
						click: function ()
						{
							if(this.handlerVerticalWidget.classList.contains('reports-activity-handler-item-active'))
							{
								return
							}

							BX.removeClass(this.handlerHybridWidget, 'reports-activity-handler-item-active');
							BX.removeClass(this.handlerHoryzontalWidget, 'reports-activity-handler-item-active');
							BX.addClass(this.handlerVerticalWidget, 'reports-activity-handler-item-active');
							BX.cleanNode(this.widgetContainer);
							BX.cleanNode(this.scaleVertical);
							BX.cleanNode(this.scaleHorizontal);
							BX.addClass(this.scaleVertical, 'reports-activity-widget-left-reverse');
							this.scaleVertical.appendChild(this.getActivityScale());
							this.scaleHorizontal.appendChild(this.getHourScale());
							this.widgetContainer.appendChild(this.getHorizontalWidget())
						}.bind(this)
					}
				});

				this.handlerHoryzontalWidget = BX.create('div',{
					attrs: {
						className: 'reports-activity-handler-item'
					},
					text: BX.message('ACTIVITY_WIDGET_DAY_TITLE'),
					events: {
						click: function ()
						{
							if(this.handlerHoryzontalWidget.classList.contains('reports-activity-handler-item-active'))
							{
								return
							}

							BX.removeClass(this.handlerHybridWidget, 'reports-activity-handler-item-active');
							BX.removeClass(this.handlerVerticalWidget, 'reports-activity-handler-item-active');
							BX.addClass(this.handlerHoryzontalWidget, 'reports-activity-handler-item-active');
							BX.cleanNode(this.widgetContainer);
							BX.cleanNode(this.scaleHorizontal);
							BX.cleanNode(this.scaleVertical);
							BX.removeClass(this.scaleVertical, 'reports-activity-widget-left-reverse');
							this.scaleHorizontal.appendChild(this.getActivityDayScale());
							this.scaleVertical.appendChild(this.getDayScale());
							this.widgetContainer.appendChild(this.getVerticalWidget())
						}.bind(this)
					}
				});

				handlerContainer.appendChild(this.handlerHybridWidget);
				handlerContainer.appendChild(this.handlerVerticalWidget);
				handlerContainer.appendChild(this.handlerHoryzontalWidget);

				return handlerContainer
			},

			/**
			 *
			 * @returns {Element}
			 */
			getWorkTimeBlock: function ()
			{
				if (this.workingHours.tooltip && this.workingHours.tooltip.length > 0)
				{
					var workTimeFrom = this.workingHours.tooltip[0] ? this.workingHours.tooltip[0].replace('.', ':') : null;
					var workTimeTo = this.workingHours.tooltip[1] ? this.workingHours.tooltip[1].replace('.', ':') : null;

					if (workTimeFrom && workTimeTo)
					{
						return BX.create('div', {
							attrs: {
								className: 'reports-activity-work-time'
							},
							html: BX.message('ACTIVITY_WIDGET_WORK_HOURS_TITLE') + workTimeFrom + ' - ' + workTimeTo
						})
					}
				}
			},

			render: function ()
			{
				this.container.appendChild(
					BX.create('div', {
						attrs: { className: 'reports-activity' },
						children: [
							this.getHandler(),
							BX.create('div',{
								attrs: { className: 'reports-activity-widget' },
								children: [
									this.scaleVertical = BX.create('div', {
										attrs: { className: 'reports-activity-widget-left' }
									}),
									BX.create('div', {
										attrs: { className: 'reports-activity-widget-right' },
										children: [
											this.widgetContainer = BX.create('div', {
												attrs: { className: 'reports-activity-widget-container' }
											}),
											this.scaleHorizontal = BX.create('div', {
												attrs: { className: 'reports-activity-widget-horizontal-scale' }
											})
										]
									})
								]
							}),
							this.getWorkTimeBlock()
						]
					})
				);

				this.scaleVertical.appendChild(this.getDayScale());
				this.scaleHorizontal.appendChild(this.getHourScale());
				this.widgetContainer.appendChild(this.getTotalActivityGraph());
			}
		};
})();

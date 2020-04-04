BX.namespace("BX.Scheduler");

BX.Scheduler.Timeline = (function() {

	/**
	 * @param settings
	 * @constructor
	 */
	var Timeline = function(settings)
	{
		this.settings = settings || {};

		//Dom layout
		this.layout = {
			root : null,
			list : null,
			tree : null,
			treeStub : null,
			gutter : null,
			timelineInner : null,
			scalePrimary : null,
			scaleSecondary : null,
			timelineData : null,
			currentDay : null,
			print: null
		};

		this.chartContainer = {
			element : BX.type.isDomNode(this.settings.renderTo) ? this.settings.renderTo : null,
			padding : 30,
			width : 0,
			pos : { left: 0, top: 0 },
			minPageX : 0,
			maxPageX : 0
		};

		this.adjustChartContainer();

		this.rowHeight = BX.type.isNumber(this.settings.rowHeight) ? this.settings.rowHeight : 32;

		this.printSettings = null;
		this.headerViewportWidth = null;

		var gutterOffset = BX.type.isNumber(this.settings.gutterOffset) ? this.settings.gutterOffset : 300;
		this.gutterOffset = this.normalizeGutterOffset(gutterOffset);

		this.createLayout();

		this.currentDatetime =
			BX.Tasks.Date.isDate(this.settings.currentDatetime) ?
			BX.Tasks.Date.convertToUTC(this.settings.currentDatetime) :
			BX.Tasks.Date.convertToUTC(new Date());

		this.currentDate = new Date(Date.UTC(
			this.currentDatetime.getUTCFullYear(),
			this.currentDatetime.getUTCMonth(),
			this.currentDatetime.getUTCDate(),
			0, 0, 0, 0
		));

		this.timelineDataOffset = null;

		this.firstWeekDay = 1;
		this.setFirstWeekDay(this.settings.firstWeekDay);
		this.calendar = new BX.Tasks.Calendar(this.settings);

		this.zoom = new BX.Scheduler.TimelineZoom(this.settings.zoomLevel);
		this.reconfigure(this.zoom.getPreset());

		if (this.settings.events)
		{
			for (var eventName in this.settings.events)
			{
				if (this.settings.events.hasOwnProperty(eventName))
				{
					BX.addCustomEvent(this, eventName, this.settings.events[eventName]);
				}
			}
		}

		BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));
	};

	/**
	 *
	 * @param {BX.Scheduler.TimelineRow[]} rows
	 */
	Timeline.prototype.appendRows = function(rows)
	{
		if (!BX.type.isArray(rows))
		{
			return;
		}

		var resources = document.createDocumentFragment();
		var events = document.createDocumentFragment();

		for (var i = 0, l = rows.length; i < l; i++)
		{
			var row = rows[i];
			resources.appendChild(row.getResourceRow());
			events.appendChild(row.getEventRow());
		}

		this.appendTreeItem(resources);
		this.appendDataItem(events);
	};

	/**
	 *
	 * @param {BX.Scheduler.TimelineRow} row
	 */
	Timeline.prototype.appendRow = function(row)
	{
		if (row instanceof BX.Scheduler.TimelineRow)
		{
			return this.appendRows([row]);
		}
	};

	Timeline.prototype.appendTreeItem = function(item)
	{
		if (BX.type.isDomNode(item))
		{
			this.layout.tree.appendChild(item);
		}
	};

	Timeline.prototype.appendDataItem = function(item)
	{
		if (BX.type.isDomNode(item))
		{
			this.layout.timelineData.appendChild(item);
		}
	};

	Timeline.prototype.clearItems = function()
	{
		BX.cleanNode(this.layout.tree);
	};

	Timeline.prototype.clearRows = function()
	{
		BX.cleanNode(this.layout.timelineData);
	};

	Timeline.prototype.adjustChartContainer = function()
	{
		if (this.chartContainer.element !== null)
		{
			var contWidth = this.chartContainer.width;

			this.chartContainer.width = this.chartContainer.element.offsetWidth;
			this.chartContainer.pos = BX.pos(this.chartContainer.element);
			this.adjustChartContainerPadding();

			if (this.layout.root !== null && contWidth !== this.chartContainer.width)
			{
				this.layout.root.style.width = this.chartContainer.width + "px";
				this.renderHeader();
			}
		}
	};

	Timeline.prototype.adjustChartContainerPadding = function()
	{
		if (this.chartContainer.element != null)
		{
			this.chartContainer.minPageX = this.chartContainer.pos.left + this.gutterOffset + this.chartContainer.padding;
			this.chartContainer.maxPageX = this.chartContainer.pos.left + this.chartContainer.width - this.chartContainer.padding;
		}
	};

	Timeline.prototype.normalizeGutterOffset = function(offset)
	{
		var minOffset = 2;
		var maxOffset = this.chartContainer.width - 100;
		return Math.min(Math.max(offset, minOffset), maxOffset > minOffset ? maxOffset : minOffset);
	};

	Timeline.prototype.setGutterOffset = function(offset)
	{
		this.gutterOffset = this.normalizeGutterOffset(offset);
		this.layout.list.style.width = this.gutterOffset + "px";
		return this.gutterOffset;
	};

	Timeline.prototype.onGutterMouseDown = function(event)
	{
		event = event || window.event;

		// if (!BX.GanttChart.isLeftClick(event))
		// 	return;

		BX.bind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
		BX.bind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

		this.gutterClientX = event.clientX;
		// this.allowRowHover = false;

		document.onmousedown = BX.False;
		document.body.onselectstart = BX.False;
		document.body.ondragstart = BX.False;
		document.body.style.MozUserSelect = "none";
		document.body.style.cursor = "ew-resize";
	};

	Timeline.prototype.onGutterMouseUp = function(event)
	{
		event = event || window.event;

		BX.unbind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
		BX.unbind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

		// this.allowRowHover = true;

		document.onmousedown = null;
		document.body.onselectstart = null;
		document.body.ondragstart = null;
		document.body.style.MozUserSelect = "";
		document.body.style.cursor = "default";

		BX.onCustomEvent(this, "onGutterResize", [this.gutterOffset]);
	};

	Timeline.prototype.onGutterMouseMove = function(event)
	{
		event = event || window.event;

		this.setGutterOffset(this.gutterOffset + (event.clientX - this.gutterClientX));
		this.adjustChartContainerPadding();
		this.gutterClientX = event.clientX;
	};

	Timeline.prototype.getTimelineDataOffset = function()
	{
		if (this.timelineDataOffset === null)
		{
			this.timelineDataOffset = this.layout.timelineData.offsetTop;
		}

		return this.timelineDataOffset;
	};

	Timeline.prototype.handleTimelineScroll = function()
	{
		this.renderHeader();
	};

	/**
	 *
	 * @param {MouseEvent} event
	 */
	Timeline.prototype.onTimelineMouseDown = function(event)
	{
		event = event || window.event;
		if (event.button !== 0)
		{
			return;
		}

		this.dragClientX = event.clientX;

		BX.Scheduler.Util.startDrag(document.body, {
			mouseup : BX.proxy(this.onTimelineMouseUp, this),
			mousemove : BX.proxy(this.onTimelineMouseMove, this)
		});

		BX.PreventDefault(event);
	};

	/**
	 *
	 * @param {MouseEvent} event
	 */
	Timeline.prototype.onTimelineMouseUp = function(event)
	{
		event = event || window.event;

		BX.Scheduler.Util.stopDrag(document.body, {
			mouseup : BX.proxy(this.onTimelineMouseUp, this),
			mousemove : BX.proxy(this.onTimelineMouseMove, this)
		});

		this.dragClientX = 0;
	};

	/**
	 *
	 * @param {MouseEvent} event
	 */
	Timeline.prototype.onTimelineMouseMove = function(event)
	{
		event = event || window.event;

		var scrollLeft = this.layout.timeline.scrollLeft + (this.dragClientX - event.clientX);
		this.layout.timeline.scrollLeft = scrollLeft < 0 ? 0 : scrollLeft;

		this.dragClientX = event.clientX;
	};

	/**
	 *
	 * @param {MouseEvent} event
	 */
	Timeline.prototype.onWindowResize = function(event)
	{
		this.adjustChartContainer();
	};

	Timeline.prototype.onPrintClick = function()
	{
		if (this.printSettings === null)
		{
			this.printSettings = new BX.Scheduler.PrintSettings(this);
		}
		this.printSettings.show();
	};

	Timeline.prototype.zoomToLevel = function(level)
	{
		if (this.layout.root === null)
		{
			return;
		}

		BX.onCustomEvent(this, "onBeforeZoomChange", [level]);

		this.setZoomLevel(level);
		this.drawScale();

		// for (var taskId in this.tasks)
		// {
		// 	var task = this.tasks[taskId];
		// 	this.timeline.autoExpandTimeline([task.getMinDate(), task.getMaxDate()]);
		// 	task.updateBars();
		// }
		//
		// this.drawDependencies();

		this.autoScroll();

		BX.onCustomEvent(this, "onZoomChange", [level]);
	};

	Timeline.prototype.zoomIn = function()
	{
		var nextLevel = this.zoom.getNextLevel();
		var currentLevel = this.zoom.getCurrentLevel();
		if (nextLevel.id !== currentLevel.id)
		{
			BX.onCustomEvent(this, "onBeforeZoomIn", [nextLevel.id]);
			this.zoomToLevel(nextLevel.id);
		}
	};

	Timeline.prototype.zoomOut = function()
	{
		var prevLevel = this.zoom.getPrevLevel();
		var currentLevel = this.zoom.getCurrentLevel();
		if (prevLevel.id !== currentLevel.id)
		{
			BX.onCustomEvent(this, "onBeforeZoomOut", [prevLevel.id]);
			this.zoomToLevel(prevLevel.id);
		}
	};

	Timeline.prototype.onZoomInClick = function(event)
	{
		event = event || window.event;
		this.zoomIn();
	};

	Timeline.prototype.onZoomOutClick = function(event)
	{
		event = event || window.event;
		this.zoomOut();
	};

	Timeline.prototype.getRowHeight = function()
	{
		return this.rowHeight;
	};

	Timeline.prototype.reconfigure = function(config)
	{
		//Scale options
		this.topUnit = config.topUnit || this.topUnit || BX.Tasks.Date.Unit.Month;
		this.topIncrement = config.topIncrement || this.topIncrement || 1;
		this.topDateFormat = config.topDateFormat || this.topDateFormat || "F Y";

		this.bottomUnit = config.bottomUnit || this.bottomUnit || BX.Tasks.Date.Unit.Day;
		this.bottomIncrement = config.bottomIncrement || this.bottomIncrement || 1;
		this.bottomDateFormat = config.bottomDateFormat || this.bottomDateFormat || "j";

		this.snapUnit = config.snapUnit || this.snapUnit || BX.Tasks.Date.Unit.Hour;
		this.snapIncrement = config.snapIncrement || this.snapIncrement || 1;
		this.snapWidth = config.snapWidth || this.snapWidth || 1;

		this.columnWidth = config.columnWidth || this.columnWidth || 24;

		//Start-End
		var currentDateMin = BX.Tasks.Date.floorDate(this.currentDatetime, this.topUnit, this.firstWeekDay);
		var currentDateMax = BX.Tasks.Date.ceilDate(this.currentDatetime, this.topUnit, this.topIncrement, this.firstWeekDay);

		var snapUnitsInViewport = Math.ceil(this.chartContainer.width / this.getUnitInPixels(this.snapUnit));
		var snapUnitsInTopHeader = BX.Tasks.Date.getDurationInUnit(currentDateMin, currentDateMax, this.snapUnit);

		var increment = Math.ceil(snapUnitsInViewport / snapUnitsInTopHeader);
		this.startDate = BX.Tasks.Date.add(currentDateMin, this.topUnit, -increment);
		this.endDate = BX.Tasks.Date.add(currentDateMax, this.topUnit, increment);
	};

	Timeline.prototype.setFirstWeekDay = function(day)
	{
		if (BX.type.isNumber(day) && day >= 0 && day <= 6)
		{
			this.firstWeekDay = day;
		}
	};

	Timeline.prototype.setZoomLevel = function(level)
	{
		this.zoom.setLevel(level);
		this.reconfigure(this.zoom.getPreset());
	};

	Timeline.prototype.createLayout = function()
	{
		if (!this.chartContainer.element || this.layout.root !== null)
		{
			return;
		}

		this.layout.root = BX.create("div", {
			props : { className: "task-gantt" },
			style : { width : this.chartContainer.width + "px" },
			children : [
				(this.layout.list = BX.create("div", {
					props : { className: "task-gantt-list"},
					style : { width : this.gutterOffset + "px" },
					children : [

						BX.create("div", { props : { className: "task-gantt-list-controls" }, children: [

							BX.Scheduler.Util.isMSBrowser() ? null : (this.layout.print = BX.create("div", { props: { className: "task-gantt-print" }, events: {
									click: BX.proxy(this.onPrintClick, this)
								}})),

							(this.layout.zoomIn = BX.create("div", {
								props: { className: "task-gantt-zoom-in" },
								events: {
									click: BX.proxy(this.onZoomInClick, this)
								}
							})),

							(this.layout.zoomOut =  BX.create("div", {
								props: { className: "task-gantt-zoom-out" },
								events: {
									click: BX.proxy(this.onZoomOutClick, this)
								}
							}))
						]}),

						BX.create("div", {
							props : { className: "task-gantt-list-title" },
							text : BX.type.isNotEmptyString(this.settings.headerText) ? this.settings.headerText : ""
						}),

						(this.layout.tree = BX.create("div", {
							props : { className: "task-gantt-items" }
						})),

						(this.layout.gutter = BX.create("div", {
							props : { className: "task-gantt-gutter" },
							events : {
								mousedown : BX.proxy(this.onGutterMouseDown, this)
							}
						})),

						(this.layout.treeStub = BX.create("div", {
							props: { className: "task-gantt-item task-gantt-item-stub" },
							attrs: { "data-project-id": "stub"}
						}))
					]
				})),

				(this.layout.timeline = BX.create("div", {

					props : { className: "task-gantt-timeline" },
					events: {
						scroll: this.handleTimelineScroll.bind(this)
					},
					children : [
						(this.layout.timelineInner =  BX.create("div", {

							props : { className: "task-gantt-timeline-inner" },

							events : {
								mousedown : BX.proxy(this.onTimelineMouseDown, this)
							},

							children : [
								BX.create("div", { props : { className: "task-gantt-timeline-head" }, children : [

									(this.layout.scalePrimary =  BX.create("div", {
										props : { className: "task-gantt-scale-primary" }
									})),

									(this.layout.scaleSecondary = BX.create("div", {
										props : { className: "task-gantt-scale-secondary" }
									}))

								]}),

								(this.layout.timelineData = BX.create("div", {
									props : { className: "task-gantt-timeline-data"}
								})),

								(this.layout.currentDay = BX.create("div", {
									props : { className: "task-gantt-current-day" }
								}))
								//,
								// this.tooltip.getLayout(),
								// this.pointer.getLayout()
							]
						}))
					]
				}))
		]});

		// this.dragger.registerProject(this.layout.treeStub);
	};

	Timeline.prototype.drawScale = function()
	{
		this.setTimelineWidth();
	};

	Timeline.prototype.draw = function()
	{
		if (this.chartContainer.element && this.layout.root !== null)
		{
			this.drawScale();
			this.chartContainer.element.appendChild(this.layout.root);
			this.adjustChartContainer();
		}
	};

	Timeline.prototype.getTimelineWidth = function()
	{
		return this.getTimespanInPixels(this.startDate, this.endDate, this.snapUnit);
	};

	Timeline.prototype.getTimespanWidth = function(startDate, endDate)
	{
		return this.getTimespanInPixels(startDate, endDate, this.snapUnit);
	};

	Timeline.prototype.getTimespanInPixels = function(startDate, endDate, unit)
	{
		var duration = BX.Tasks.Date.getDurationInUnit(startDate, endDate, unit);
		var unitSize = this.getUnitInPixels(unit);

		return duration * unitSize;
	};

	Timeline.prototype.setTimelineWidth = function()
	{
		this.layout.timelineInner.style.width = this.getTimelineWidth() + "px";
	};

	Timeline.prototype.getColumnWidth = function()
	{
		return this.gutterOffset;
	};

	Timeline.prototype.getScrollHeight = function()
	{
		return this.layout.root.scrollHeight;
	};

	/**
	 *
	 * @return {Element}
	 */
	Timeline.prototype.getRootContainer = function()
	{
		return this.layout.root;
	};

	Timeline.prototype.autoExpandTimeline = function(dates)
	{
		if (!BX.type.isArray(dates))
		{
			dates = [dates];
		}

		var snapUnitsInViewport = Math.ceil(this.chartContainer.width / this.getUnitInPixels(this.snapUnit));
		for (var i = 0; i < dates.length; i++)
		{
			var date = dates[i];

			var currentDateMin = BX.Tasks.Date.floorDate(date, this.topUnit, this.firstWeekDay);
			var currentDateMax = BX.Tasks.Date.ceilDate(date, this.topUnit, this.topIncrement, this.firstWeekDay);
			var snapUnitsInTopHeader = BX.Tasks.Date.getDurationInUnit(currentDateMin, currentDateMax, this.snapUnit);
			var increment = Math.ceil(snapUnitsInViewport / snapUnitsInTopHeader);

			var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, date, this.snapUnit);
			if (duration <= snapUnitsInViewport)
			{
				var newStartDate = BX.Tasks.Date.add(currentDateMin, this.topUnit, -increment);
				this.expandTimelineLeft(newStartDate);
				continue;
			}

			duration = BX.Tasks.Date.getDurationInUnit(date, this.endDate, this.snapUnit);
			if (duration <= snapUnitsInViewport)
			{
				var newEndDate = BX.Tasks.Date.add(currentDateMax, this.topUnit, increment);
				this.expandTimelineRight(newEndDate);
			}
		}
	};

	Timeline.prototype.expandTimelineLeft = function(date)
	{
		if (date >= this.startDate)
		{
			return;
		}

		var oldDate = new Date(this.startDate.getTime());
		this.startDate = date;
		if (this.layout.root === null)
		{
			return;
		}

		var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, oldDate, this.snapUnit);
		var unitSize = this.getUnitInPixels(this.snapUnit);
		var offset = duration * unitSize;

		var scrollLeft = this.getScrollLeft();
		this.setTimelineWidth();

		// for	(var taskId in this.chart.tasks)
		// {
		// 	this.chart.tasks[taskId].offsetBars(offset);
		// }
		//
		// this.chart.drawDependencies();
		// this.chart.pointer.offsetLine(offset);

		this.setScrollLeft(scrollLeft + offset);
	};

	Timeline.prototype.expandTimelineRight = function(date)
	{
		if (date <= this.endDate)
		{
			return;
		}

		var oldDate = new Date(this.endDate.getTime());
		this.endDate = date;
		if (this.layout.root === null)
		{
			return;
		}

		var scrollLeft = this.getScrollLeft();
		this.setTimelineWidth();
		this.setScrollLeft(scrollLeft);
	};

	Timeline.prototype.getCurrentDate = function()
	{
		return this.currentDate;
	};

	Timeline.prototype.getCurrentDatetime = function()
	{
		return this.currentDatetime;
	};

	Timeline.prototype.getStart = function()
	{
		return this.startDate;
	};

	Timeline.prototype.getEnd = function()
	{
		return this.endDate;
	};

	Timeline.prototype.getHeaderViewportWidth = function()
	{
		return this.headerViewportWidth !== null ? this.headerViewportWidth : this.chartContainer.width;
	};

	Timeline.prototype.setHeaderViewportWidth = function(width)
	{
		if (BX.type.isNumber(width) || width === null)
		{
			this.headerViewportWidth = width;
		}
	};

	Timeline.prototype.renderHeader = function()
	{
		var viewport = this.getHeaderViewportWidth();
		var scrollLeft = this.getScrollLeft();

		var startDate = this.getDateFromPixels(scrollLeft);
		var endDate = this.getDateFromPixels(scrollLeft + viewport);

		startDate = BX.Tasks.Date.floorDate(startDate, this.topUnit, this.firstWeekDay);
		endDate = BX.Tasks.Date.ceilDate(endDate, this.topUnit, this.topIncrement, this.firstWeekDay);

		var topUnitsInViewport = Math.ceil(viewport / this.getUnitInPixels(this.topUnit));
		startDate = BX.Tasks.Date.add(startDate, this.topUnit, -topUnitsInViewport);
		endDate = BX.Tasks.Date.add(endDate, this.topUnit, topUnitsInViewport);

		startDate = BX.Tasks.Date.max(startDate, this.getStart());
		endDate = BX.Tasks.Date.min(endDate, this.getEnd());

		this.layout.scalePrimary.innerHTML =
			this.createHeader(startDate, endDate, "top", this.topUnit, this.topIncrement);

		this.layout.scaleSecondary.innerHTML =
			this.createHeader(startDate, endDate, "bottom", this.bottomUnit, this.bottomIncrement);
	};

	Timeline.prototype.createHeader = function(start, end, position, unit, increment)
	{
		var startDate = this.getStart();
		var endDate = end;
		var result = "";

		var offset = 0;
		while (startDate < endDate)
		{
			var nextDate = BX.Tasks.Date.min(BX.Tasks.Date.getNext(startDate, unit, increment, this.firstWeekDay), endDate);

			if (startDate >= start)
			{
				result += position === "top" ? this.renderTopHeader(startDate, nextDate) : this.renderBottomHeader(startDate, nextDate);
			}
			else
			{
				offset += this.getTimespanInPixels(startDate, nextDate, this.snapUnit);
			}

			startDate = nextDate;
		}

		return '<div style="position: absolute; left: ' + offset + 'px">' + result + '</div>';
	};

	Timeline.prototype.renderTopHeader = function(start, end)
	{
		var duration = BX.Tasks.Date.getDurationInUnit(start, end, this.snapUnit);
		var unitSize = this.getUnitInPixels(this.snapUnit);

		return '<span class="task-gantt-top-column" ' +
			'style="width:' + (duration * unitSize) + 'px"><span class="task-gantt-scale-month-text">' +
			BX.date.format(this.topDateFormat, start, null, true) + '</span></span>';
	};

	Timeline.prototype.renderBottomHeader = function(start, end)
	{
		var duration = BX.Tasks.Date.getDurationInUnit(start, end, this.snapUnit);
		var unitSize = this.getUnitInPixels(this.snapUnit);

		var columnClass = "task-gantt-bottom-column";
		if (this.bottomUnit !== BX.Tasks.Date.Unit.Month &&
			this.bottomUnit !== BX.Tasks.Date.Unit.Quarter &&
			this.bottomUnit !== BX.Tasks.Date.Unit.Year)
		{
			if (this.isToday(start, end))
			{
				columnClass += " task-gantt-today-column";
			}

			if (this.isWeekend(start, end))
			{
				columnClass += " task-gantt-weekend-column";
			}

			if (this.isHoliday(start, end))
			{
				columnClass += " task-gantt-holiday-column";
			}
		}

		return '<span class="'+ columnClass +'" style="width:' + (duration * unitSize) + 'px">' +
			BX.date.format(this.bottomDateFormat, start, null, true) +
			'</span>';
	};

	Timeline.prototype.isToday = function(start, end)
	{
		return ( 
			this.currentDate.getUTCMonth() === start.getUTCMonth() && 
			this.currentDate.getUTCDate() === start.getUTCDate()
		);
	};

	Timeline.prototype.isHoliday = function(start, end)
	{
		return this.calendar.isHoliday(start);
	};

	Timeline.prototype.isWeekend = function(start, end)
	{
		return this.calendar.isWeekend(start);
	};

	Timeline.prototype.getPixelsFromDate = function(date)
	{
		var duration = BX.Tasks.Date.getDurationInUnit(this.startDate, date, this.snapUnit);
		return duration * this.getUnitInPixels(this.snapUnit);
	};

	Timeline.prototype.getDateFromPixels = function(pixels)
	{
		var date = BX.Tasks.Date.add(this.startDate, this.snapUnit, Math.floor(pixels / this.getUnitInPixels(this.snapUnit)));
		return this.snapDate(date);
	};

	Timeline.prototype.getUnitInPixels = function(unit)
	{
		return BX.Tasks.Date.getUnitRatio(this.bottomUnit, unit) * this.columnWidth / this.bottomIncrement;
	};

	Timeline.prototype.snapDate = function(date)
	{
		var newDate = new Date(date.getTime());
		if (this.snapUnit === BX.Tasks.Date.Unit.Day)
		{
			var days = BX.Tasks.Date.getDurationInDays(this.startDate, newDate);
			var snappedDays = Math.round(days / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Day, snappedDays);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Hour)
		{
			var hours = BX.Tasks.Date.getDurationInHours(this.startDate, newDate);
			var snappedHours = Math.round(hours / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Minute, snappedHours * 60);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Minute)
		{
			var minutes = BX.Tasks.Date.getDurationInMinutes(this.startDate, newDate);
			var snappedMinutes = Math.round(minutes / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Second, snappedMinutes * 60);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Week)
		{
			newDate.setUTCHours(0, 0, 0, 0);
			var firstWeekDayDelta = newDate.getUTCDay() - this.firstWeekDay;
			if (firstWeekDayDelta < 0)
			{
				firstWeekDayDelta = 7 + firstWeekDayDelta;
			}
			var daysToSnap = Math.round(firstWeekDayDelta / 7) === 1 ? 7 - firstWeekDayDelta : -firstWeekDayDelta;
			newDate = BX.Tasks.Date.add(newDate, BX.Tasks.Date.Unit.Day, daysToSnap);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Month)
		{
			var months = BX.Tasks.Date.getDurationInMonths(this.startDate, newDate) + (newDate.getUTCDate() / BX.Tasks.Date.getDaysInMonth(newDate));
			var snappedMonth = Math.round(months / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Month, snappedMonth);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Second)
		{
			var seconds = BX.Tasks.Date.getDurationInSeconds(this.startDate, newDate);
			var snappedSeconds = Math.round(seconds / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Milli, snappedSeconds * 1000);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Milli)
		{
			var millis = BX.Tasks.Date.getDurationInMilliseconds(this.startDate, newDate);
			var snappedMilli = Math.round(millis / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Milli, snappedMilli);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Year)
		{
			var years = BX.Tasks.Date.getDurationInYears(this.startDate, newDate);
			var snappedYears = Math.round(years / this.snapIncrement) * this.snapIncrement;
			newDate = BX.Tasks.Date.add(this.startDate, BX.Tasks.Date.Unit.Year, snappedYears);
		}
		else if (this.snapUnit === BX.Tasks.Date.Unit.Quarter)
		{
			newDate.setUTCHours(0, 0, 0, 0);
			newDate.setDate(1);
			newDate = BX.Tasks.Date.add(newDate, BX.Tasks.Date.Unit.Month, 3 - (newDate.getUTCMonth() % 3));
		}

		return newDate;
	};

	Timeline.prototype.scrollToDate = function(date)
	{
		if (!BX.Tasks.Date.isDate(date) || date < this.startDate || date > this.endDate)
		{
			return;
		}

		var maxScrollLeft = this.getMaxScrollLeft();
		var dateOffset = this.getPixelsFromDate(date);
		this.setScrollLeft(dateOffset > maxScrollLeft ? maxScrollLeft : dateOffset);
	};

	Timeline.prototype.scrollTo = function(x)
	{
		this.setScrollLeft(Math.min(Math.max(0, x), this.getMaxScrollLeft()));
		this.renderHeader();
	};

	Timeline.prototype.getScrollLeft = function()
	{
		return this.layout.timeline.scrollLeft;
	};

	Timeline.prototype.setScrollLeft = function(offset)
	{
		this.layout.timeline.scrollLeft = offset;
	};

	Timeline.prototype.getMaxScrollLeft = function()
	{
		var scrollWidth = this.getPixelsFromDate(this.endDate);
		var viewport = this.getViewportWidth();
		return scrollWidth - viewport;
	};

	Timeline.prototype.getViewportWidth = function()
	{
		return this.chartContainer.width - this.gutterOffset;
	};

	Timeline.prototype.autoScroll = function()
	{
		var viewport = this.getViewportWidth();
		var currentDateInPixels = this.getPixelsFromDate(
			BX.Tasks.Date.floorDate(this.currentDatetime, this.snapUnit, this.firstWeekDay)
		);
		this.scrollToDate(this.getDateFromPixels(currentDateInPixels - viewport / 4));
	};

	Timeline.prototype.getRelativeXY = function(event)
	{
		BX.fixEventPageXY(event);

		return {
			x: event.pageX - this.chartContainer.pos.left - this.gutterOffset + this.layout.timeline.scrollLeft,
			y: event.pageY - this.chartContainer.pos.top + this.layout.timeline.scrollTop
		};
	};

	return Timeline;

})();

BX.Scheduler.TimelineRow = (function() {

	var TimelineRow = function(config) {

		this.config = config || {};
		this.rowHeight = BX.type.isNumber(this.config.rowHeight) ? this.config.rowHeight : 32;

		/**
		 *
		 * @type {Element}
		 */
		this.resourceRow = BX.create("div", {
			props: { className: "scheduler-row-resource" },
			style: { height: this.rowHeight + "px" },
			events: {
				mouseover: BX.proxy(this.onMouseOver, this),
				mouseout: BX.proxy(this.onMouseOut, this)
			}
		});

		/**
		 *
		 * @type {Element}
		 */
		this.eventRow = BX.create("div", {
			props: { className: "scheduler-row-event" },
			style: { height: this.rowHeight + "px" },
			events: {
				mouseover: BX.proxy(this.onMouseOver, this),
				mouseout: BX.proxy(this.onMouseOut, this)
			}
		});
	};

	/**
	 *
	 * @returns {Element}
	 */
	TimelineRow.prototype.getResourceRow = function() {
		return this.resourceRow;
	};

	/**
	 *
	 * @returns {Element}
	 */
	TimelineRow.prototype.getEventRow = function() {
		return this.eventRow;
	};

	TimelineRow.prototype.setRowHeight = function(height) {
		if (BX.type.isNumber(height))
		{
			this.rowHeight = height;
			this.resourceRow.style.height = height + "px";
			this.eventRow.style.height = height + "px";
		}
	};

	TimelineRow.prototype.getRowHeight = function() {
		return this.rowHeight;
	};

	TimelineRow.prototype.onMouseOver = function() {
		BX.addClass(this.resourceRow, "scheduler-row-resource-hover");
		BX.addClass(this.eventRow, "scheduler-row-event-hover");
	};

	TimelineRow.prototype.onMouseOut = function() {
		BX.removeClass(this.resourceRow, "scheduler-row-resource-hover");
		BX.removeClass(this.eventRow, "scheduler-row-event-hover");
	};

	return TimelineRow;
})();

BX.Scheduler.TimelineZoom = (function() {

	function TimelineZoom(levelId)
	{
		this.setLevel(levelId);
	}

	TimelineZoom.prototype.presets = {
		yearquarter:
		{
			columnWidth: 200,
			topUnit: BX.Tasks.Date.Unit.Year,
			topIncrement: 1,
			topDateFormat: "Y",
			bottomUnit: BX.Tasks.Date.Unit.Quarter,
			bottomIncrement: 1,
			bottomDateFormat: "f",
			snapUnit: BX.Tasks.Date.Unit.Day,
			snapIncrement: 1,
			firstWeekDay: 1
		},

		yearmonth:
		{
			columnWidth: 100,
			topUnit: BX.Tasks.Date.Unit.Year,
			topIncrement: 1,
			topDateFormat: "Y",
			bottomUnit: BX.Tasks.Date.Unit.Month,
			bottomIncrement: 1,
			bottomDateFormat: "f",
			snapUnit: BX.Tasks.Date.Unit.Day,
			snapIncrement: 1,
			firstWeekDay: 1
		},

		monthday:
		{
			columnWidth: 24,
			topUnit: BX.Tasks.Date.Unit.Month,
			topIncrement: 1,
			topDateFormat: "f Y",
			bottomUnit: BX.Tasks.Date.Unit.Day,
			bottomIncrement: 1,
			bottomDateFormat: "j",
			snapUnit: BX.Tasks.Date.Unit.Hour,
			snapIncrement: 1
		},

		weekday:
		{
			columnWidth: 48,
			topUnit: BX.Tasks.Date.Unit.Week,
			topIncrement: 1,
			topDateFormat: "j F",
			bottomUnit: BX.Tasks.Date.Unit.Day,
			bottomIncrement: 1,
			bottomDateFormat: "D",
			snapUnit: BX.Tasks.Date.Unit.Hour,
			snapIncrement: 1,
			firstWeekDay: 1
		},

		dayhour: {
			columnWidth: 60,
			topUnit: BX.Tasks.Date.Unit.Day,
			topIncrement: 1,
			topDateFormat: "j F",
			bottomUnit: BX.Tasks.Date.Unit.Hour,
			bottomIncrement: 6,
			bottomDateFormat: "G:i",
			snapUnit: BX.Tasks.Date.Unit.Minute,
			snapIncrement: 15
		},

		hourminute: {
			columnWidth: 48,
			topUnit: BX.Tasks.Date.Unit.Hour,
			topIncrement: 1,
			topDateFormat: "j F H:i",
			bottomUnit: BX.Tasks.Date.Unit.Minute,
			bottomIncrement: 15,
			bottomDateFormat: "i:s",
			snapUnit: BX.Tasks.Date.Unit.Minute,
			snapIncrement: 1
		}
	};

	TimelineZoom.prototype.levels = [
		/*
		 levelId: {
		 preset
		 columnWidth
		 snapUnit
		 snapIncrement
		 bottomIncrement
		 }
		 */
		{
			id: "yearquarter",
			preset: "yearquarter"
		},
		{
			id: "yearmonth",
			preset: "yearmonth"
		},
		{
			id: "monthday",
			preset: "monthday"
		},
		{
			id: "monthday2x",
			preset: "monthday",
			columnWidth: 48
		},
		{
			id: "weekday",
			preset: "weekday"
		},
		{
			id: "dayhour",
			preset: "dayhour"
		}
	].concat(!BX.Scheduler.Util.isMSBrowser() ? [
		{
			id: "daysecondhour",
			preset: "dayhour",
			bottomIncrement: 2,
			snapIncrement: 15
		},
		{
			id: "dayeveryhour",
			preset: "dayhour",
			bottomIncrement: 1,
			snapIncrement: 5
		},
		{
			id: "hourminute",
			preset: "hourminute"
		}
	]: []);

	TimelineZoom.prototype.setLevel = function(levelId)
	{
		var levelIndex = this.getLevelIndex(levelId);
		if (levelIndex === null)
		{
			this.levelIndex = typeof(this.levelIndex) !== "undefined" ? this.levelIndex : this.getLevelIndex("monthday");
		}
		else
		{
			this.levelIndex = levelIndex;
		}
	};

	TimelineZoom.prototype.getLevelIndex = function(levelId)
	{
		for (var i = 0, l = this.levels.length; i < l; i++)
		{
			var level = this.levels[i];
			if (level.id === levelId)
			{
				return i;
			}
		}

		return null;
	};

	TimelineZoom.prototype.getCurrentLevel = function()
	{
		return this.levels[this.levelIndex];
	};

	TimelineZoom.prototype.getNextLevel = function()
	{
		return this.levelIndex === this.levels.length - 1 ?
			this.levels[this.levels.length - 1] :
			this.levels[this.levelIndex + 1];
	};

	TimelineZoom.prototype.getPrevLevel = function()
	{
		return this.levelIndex > 0 ? this.levels[this.levelIndex - 1] : this.levels[0];
	};

	TimelineZoom.prototype.getPreset = function()
	{
		var level = this.levels[this.levelIndex];
		var preset = BX.clone(this.presets[level.preset]);
		for (var option in level)
		{
			if (level.hasOwnProperty(option))
			{
				preset[option] = level[option];
			}
		}

		return preset;
	};

	return TimelineZoom;

})();
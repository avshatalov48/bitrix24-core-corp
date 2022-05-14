;(function(window) {
	var View = window.BXEventCalendarView;

	function MonthView()
	{
		View.apply(this, arguments);
		this.name = 'month';
		this.title = BX.message('EC_VIEW_MONTH');
		this.contClassName = 'calendar-month-view';
		this.dayCount = 7;
		this.slotHeight = 20;
		this.eventHolderTopOffset = 25;
		this.hotkey = 'M';

		this.preBuild();
	}
	MonthView.prototype = Object.create(View.prototype);
	MonthView.prototype.constructor = MonthView;

	MonthView.prototype.preBuild = function()
	{
		this.viewCont = BX.create('DIV', {props: {className: this.contClassName}, style: {display: 'none'}});
	};

	MonthView.prototype.build = function()
	{
		this.titleCont = this.viewCont.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month-row-days-week'}}));

		this.gridWrap = this.viewCont.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-wrap'}}));

		this.gridMonthContainer = this.gridWrap.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month-container'}}));

		this.grid = this.gridMonthContainer.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month calendar-grid-month-current'}}));
	};

	MonthView.prototype.show = function()
	{
		View.prototype.show.apply(this, arguments);

		this.buildDaysTitle();
		this.buildDaysGrid();

		if (this.calendar.navCalendar)
			this.calendar.navCalendar.hide();

		this.displayEntries();
		this.calendar.initialViewShow = false;
	};

	MonthView.prototype.hide = function()
	{
		View.prototype.hide.apply(this, arguments);
	};

	MonthView.prototype.increaseViewRangeDate = function()
	{
		this.changeViewRangeDate(1);

		var nextGrid = this.gridMonthContainer.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month calendar-grid-month-next' + ' ' + this.animateClass}}));
		BX.addClass(this.grid, this.animateClass);
		this.setTitle();

		this.buildDaysGrid({grid: nextGrid});

		// Prepare entries while animatin goes
		this.preloadEntries();

		setTimeout(BX.delegate(function()
		{
			// Start CSS animation
			BX.addClass(this.gridMonthContainer, "calendar-change-month-next");

			// Wait till the animation ends
			setTimeout(BX.delegate(function()
			{
				// Clear old grid, now it's hidden and use new as old one
				BX.removeClass(this.gridMonthContainer, "calendar-change-month-next");
				BX.removeClass(nextGrid, "calendar-grid-month-next");
				BX.addClass(nextGrid, "calendar-grid-month-current");
				BX.remove(this.grid);
				this.grid = nextGrid;
				BX.removeClass(this.grid, this.animateClass);

				// Display loaded entries for new view range
				this.displayEntries();
			}, this), 400);
		}, this), 0);
	};

	MonthView.prototype.decreaseViewRangeDate = function()
	{
		this.changeViewRangeDate(-1);

		var previousGrid = this.gridMonthContainer.insertBefore(BX.create('DIV', {props: {className: 'calendar-grid-month calendar-grid-month-previous' + ' ' + this.animateClass}}), this.grid);
		BX.addClass(this.grid, this.animateClass);

		this.setTitle();
		this.buildDaysGrid({grid: previousGrid});

		// Prepare entries while animatin goes
		this.preloadEntries();

		setTimeout(BX.delegate(function()
		{
			// Start CSS animation
			BX.addClass(this.gridMonthContainer, "calendar-change-month-previous");

			// Wait till the animation ends
			setTimeout(BX.delegate(function()
			{
				// Clear old grid, now it's hidden and use new as old one
				BX.removeClass(this.gridMonthContainer, "calendar-change-month-previous");
				BX.removeClass(previousGrid, "calendar-grid-month-previous");
				BX.addClass(previousGrid, "calendar-grid-month-current");
				BX.remove(this.grid);
				this.grid = previousGrid;
				BX.removeClass(this.grid, this.animateClass);

				// Display loaded entries for new view range
				this.displayEntries();
			}, this), 400);
		}, this), 0);
	};

	MonthView.prototype.getViewRange = function()
	{
		var
			viewRangeDate = this.calendar.getViewRangeDate(),
			endDate = new Date(viewRangeDate.getTime());

		endDate.setMonth(viewRangeDate.getMonth() + 1);
		return {start: viewRangeDate, end: endDate};
	};

	MonthView.prototype.changeViewRangeDate = function(value)
	{
		var
			viewRangeDate = this.calendar.getViewRangeDate(),
			newDate = new Date(viewRangeDate.getTime());

		newDate.setMonth(newDate.getMonth() + value);

		this.calendar.setViewRangeDate(newDate);
		return newDate;
	};

	MonthView.prototype.adjustViewRangeToDate = function(date)
	{
		var
			currentViewRangeDate = this.calendar.getViewRangeDate(),
			viewRangeDate = false;

		var diff = date.getMonth() - currentViewRangeDate.getMonth();
		if (diff == 1)
		{
			this.increaseViewRangeDate();
		}
		else if (diff == -1)
		{
			this.decreaseViewRangeDate();
		}
		else
		{
			if (date && date.getTime)
			{
				viewRangeDate = new Date(date.getTime());
				viewRangeDate.setDate(1);
				viewRangeDate.setHours(0, 0, 0, 0);
				this.calendar.setViewRangeDate(viewRangeDate);
			}

			this.fadeAnimation(this.getContainer(), 100, BX.delegate(function(){
				this.show();
				this.getContainer().style.opacity = 0;
				this.showAnimation(this.getContainer(), 300)
			}, this));
		}

		return viewRangeDate;
	};

	MonthView.prototype.getAdjustedDate = function(date, viewRange)
	{
		if (!date)
		{
			date = new Date();
		}

		if (date.getTime() < viewRange.start.getTime())
		{
			date = new Date(viewRange.start.getTime());
		}

		if (date.getTime() > viewRange.end.getTime())
		{
			date = new Date(viewRange.end.getTime());
		}

		var viewRangeDate = false;

		if (date && date.getTime)
		{
			viewRangeDate = new Date(date.getTime());
			viewRangeDate.setDate(1);
			viewRangeDate.setHours(0, 0, 0, 0);
		}

		return viewRangeDate;
	};

	MonthView.prototype.buildDaysTitle = function()
	{
		BX.cleanNode(this.titleCont);

		var
			i, day,
			weekDays = this.util.getWeekDays();

		for (i = 0; i < weekDays.length; i++)
		{
			day = weekDays[i];
			this.titleCont.appendChild(BX.create('DIV',
			{
				props: {
					className: 'calendar-grid-month-cell'
				},
				html: '<span class="calendar-grid-cell-inner">' +
					BX.message('EC_MONTH_WEEK_TITLE').replace('#DAY_OF_WEEK#', day[1])
				+ '</span>'
			}));
		}
	};

	MonthView.prototype.buildDaysGrid = function(params)
	{
		if (!params)
			params = {};

		var i;
		var dayOffset;
		var grid = params.grid || this.grid;
		var viewRangeDate = this.calendar.getViewRangeDate();
		var year = viewRangeDate.getFullYear();
		var month = viewRangeDate.getMonth();
		var height = this.util.getViewHeight();
		var displayedRange = BX.clone(this.getViewRange(), true);
		var date = new Date();

		BX.cleanNode(grid);
		date.setFullYear(year, month, 1);

		this.dayIndex = {};
		this.days = [];
		this.entryHolders = [];

		this.currentMonthRow = false;
		this.monthRows = [];

		if (this.util.getWeekStart() != BX.Calendar.Util.getWeekDayByInd(date.getDay()))
		{
			dayOffset = this.util.getWeekDayOffset(BX.Calendar.Util.getWeekDayByInd(date.getDay()));
			date.setDate(date.getDate() - dayOffset);

			displayedRange.start = new Date(date.getTime());
			displayedRange.start.setHours(0, 0, 0, 0);

			for (i = 0; i < dayOffset; i++)
			{
				this.buildDayCell({date: date, month: 'previous', grid: grid});
				date.setDate(date.getDate() + 1);
			}
		}

		date.setFullYear(year, month, 1);
		while(date.getMonth() == month)
		{
			this.buildDayCell({date: date, grid: grid});
			date.setDate(date.getDate() + 1);
		}

		if (this.util.getWeekStart() != BX.Calendar.Util.getWeekDayByInd(date.getDay()))
		{
			dayOffset = this.util.getWeekDayOffset(BX.Calendar.Util.getWeekDayByInd(date.getDay()));
			date.setFullYear(year, month + 1, 1);
			for (i = dayOffset; i < 7; i++)
			{
				this.buildDayCell({date: date, month: 'next', grid: grid});
				date.setDate(date.getDate() + 1);
			}

			displayedRange.end = new Date(date.getTime());
			displayedRange.end.setHours(23, 59, 59, 59);
		}

		this.calendar.setDisplayedViewRange(displayedRange);

		// Adjusting rows height to the height of the view
		if (this.monthRows.length > 0)
		{
			this.rowHeight = Math.round(height / this.monthRows.length);
			this.slotsCount = Math.floor((this.rowHeight - this.eventHolderTopOffset) / this.slotHeight);
			for (i = 0; i < this.monthRows.length; i++)
			{
				this.monthRows[i].style.height = this.rowHeight + 'px';
			}
		}
	};

	MonthView.prototype.buildDayCell = function(params)
	{
		var
			date = params.date,
			className = '',
			time = Math.round(date.getTime() / 1000) * 1000,
			day = date.getDay(),
			dayCode = this.util.getDayCode(date),
			weekDay = BX.Calendar.Util.getWeekDayByInd(day),
			weekNumber = false,
			startNewWeek = this.util.getWeekStart() == weekDay;

		if (startNewWeek)
		{
			this.currentMonthRow = params.grid.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month-row'}}));
			this.monthRows.push(this.currentMonthRow);

			if (this.util.showWeekNumber())
			{
				weekNumber = this.util.getWeekNumber(time);
			}
		}

		if (params.month === 'previous')
		{
			className += ' calendar-grid-previous-month-day';
		}
		else if (params.month === 'next')
		{
			className += ' calendar-grid-next-month-day';
		}

		if (this.util.isHoliday(date))
		{
			className += ' calendar-grid-holiday';
		}

		if (this.util.isToday(date))
		{
			className += ' calendar-grid-today';
		}

		this.days.push({
			date: new Date(date.getTime()),
			dayOffset: this.util.getWeekDayOffset(weekDay),
			rowIndex: this.monthRows.length - 1,
			holderIndex: this.entryHolders.length,
			node: this.currentMonthRow.appendChild(BX.create('DIV', {
				props: {className: BX.util.trim('calendar-grid-month-cell' + className)},
				attrs: {'data-bx-calendar-month-day': dayCode},
				html: '<span class="calendar-grid-cell-inner">' +
				'<span class="calendar-num-day" data-bx-calendar-date="' + time + '">' +
				(date.getDate() == 1 ? BX.message('EC_MONTH_SHORT')
					.replace('#MONTH#', BX.date.format('M', time / 1000))
					.replace('#DATE#', date.getDate())
					: date.getDate()) +
				'</span>' +
				(weekNumber ? '<span class="calendar-num-week"  data-bx-cal-time="' + time + '" data-bx-calendar-weeknumber="' + weekNumber + '">' + weekNumber + '</span>' : '') +
				'</span>'
			})),
			dayCode: dayCode
		});
		this.dayIndex[this.days[this.days.length - 1].dayCode] = this.days.length - 1;

		this.calendar.dragDrop.registerDay(this.days[this.days.length - 1]);

		if (this.currentMonthRow && this.util.getWeekEnd() === weekDay)
		{
			this.entryHolders.push(this.currentMonthRow.appendChild(BX.create('DIV', {props: {className: 'calendar-grid-month-events-holder'}})));
		}
	};

	MonthView.prototype.setTitle = function()
	{
		var viewRangeDate = this.calendar.getViewRangeDate();
		View.prototype.setTitle.apply(this, [BX.date.format('f', viewRangeDate.getTime() / 1000) + ', #GRAY_START#' + viewRangeDate.getFullYear() + '#GRAY_END#']);
	};

	MonthView.prototype.displayEntries = function(params)
	{
		var
			prevElement,
			element,
			i, j, entry, part, dayPos, entryPart, day, entryStarted,
			partsStorage = [],
			entryDisplayed, showHiddenLink,
			viewRange = this.calendar.getDisplayedViewRange();

		if (!params)
			params = {};

		if (params.reloadEntries !== false)
		{
			// Get list of entries
			this.entries = this.entryController.getList({
				showLoader: !!(this.entries && !this.entries.length),
				startDate: new Date(viewRange.start.getFullYear(), viewRange.start.getMonth(), 1),
				finishDate: new Date(viewRange.end.getFullYear(), viewRange.end.getMonth() + 1, 1),
				viewRange: viewRange,
				finishCallback: BX.proxy(this.displayEntries, this)
			});

			if (this.entries === false)
			{
				return;
			}
		}

		// Clean holders
		this.entryHolders.forEach(function(holder)
		{
			BX.cleanNode(holder);
		});

		// Clean days
		this.days.forEach(function(day)
		{
			day.slots = [];
			day.entries = {
				list: [],
				started: [],
				hidden: []
			};
		});

		if (this.entries === false || !this.entries || !this.entries.length)
			return;

		// Prepare for arrangement
		for (i = 0; i < this.entries.length; i++)
		{
			entry = this.entries[i];
			this.entriesIndex[entry.uid] = i;
			entry.cleanParts();
			entryStarted = false;

			for (dayPos = this.dayIndex[entry.startDayCode]; dayPos < this.days.length; dayPos++)
			{
				day = this.days[dayPos];
				if (day.dayCode === entry.startDayCode || entryStarted && day.dayOffset === 0)
				{
					entryStarted = true;

					part = entry.startPart({
						from: day,
						daysCount: 0
					});

					day.entries.started.push({
						entry: entry,
						part: part
					});
				}

				if(entryStarted)
				{
					day.entries.list.push({
						entry: entry,
						part: part
					});

					part.daysCount++;
					part.to = day;

					if (day.dayCode === entry.endDayCode || day.dayOffset === this.dayCount - 1)
					{
						// here we know where part of event starts and ends
						partsStorage.push({part: part, entry: entry});

						// Event finished
						if (day.dayCode === entry.endDayCode)
						{
							break;
						}
					}
				}
			}
		}

		// Display parts
		for (i = 0; i < partsStorage.length; i++)
		{
			this.displayEntryPiece(partsStorage[i]);
		}

		// Final arrangement on the grid
		for (dayPos = 0; dayPos < this.days.length; dayPos++)
		{
			day = this.days[dayPos];

			if (day.entries.started.length > 0)
			{
				if (day.entries.started.length > 0)
				{
					day.entries.started.sort(this.calendar.entryController.sort);
				}

				for(i = 0; i < day.entries.started.length; i++)
				{
					element = day.entries.started[i];
					if (element)
					{
						entry = element.entry;
						entryPart = element.part;
						entryDisplayed = false;
						for(j = 0; j < this.slotsCount; j++)
						{
							if (day.slots[j] !== false)
							{
								this.occupySlot({slotIndex: j, startIndex: dayPos, endIndex: dayPos + entryPart.daysCount});
								entryDisplayed = true;
								entry.getWrap(entryPart.partIndex).style.top = (j * this.slotHeight) + 'px';
								break;
							}
						}

						if (!entryDisplayed)
						{
							prevElement = day.entries.started[i - 1];
							if (prevElement)
							{
								day.entries.hidden.push(prevElement);
								prevElement.entry.getWrap(prevElement.part.partIndex).style.display = 'none';
							}
							day.entries.hidden.push(element);
							entry.getWrap(entryPart.partIndex).style.display = 'none';
						}
					}
				}
			}

			// Here we check all entries in the day and if any of it
			// was hidden, we going to show 'show all' link
			if (day.entries.list.length > 0)
			{
				showHiddenLink = false;
				for(i = 0; i < day.entries.list.length; i++)
				{
					if (day.entries.list[i].part.params.wrapNode.style.display === 'none')
					{
						showHiddenLink = true;
						break;
					}
				}

				if (showHiddenLink)
				{
					day.hiddenStorage = this.entryHolders[day.holderIndex].appendChild(BX.create('DIV', {
						props: {
							className: 'calendar-event-line-wrap calendar-event-more-btn-container'
						},
						attrs: {'data-bx-calendar-show-all-events': day.dayCode},
						style: {
							top: (this.rowHeight - 47) + 'px',
							left: 'calc((100% / ' + this.dayCount + ') * (' + (day.dayOffset + 1) + ' - 1) + 2px)',
							width: 'calc(100% / ' + this.dayCount + ' - 3px)'
						}
					}));
					day.hiddenStorageText = day.hiddenStorage.appendChild(BX.create('span', {props: {className: 'calendar-event-more-btn'}}));
					day.hiddenStorage.style.display = 'block';
					day.hiddenStorageText.innerHTML = BX.message('EC_SHOW_ALL') + ' ' + day.entries.list.length;
				}
				else if (day.hiddenStorage)
				{
					day.hiddenStorage.style.display = 'none';
				}
			}
		}

		BX.addClass(this.gridMonthContainer, 'calendar-events-holder-show');
	};

	MonthView.prototype.displayEntryPiece = function(params)
	{
		var
			res = false,
			entry = params.entry,
			from = params.part.from,
			daysCount = params.part.daysCount,
			partWrap, dotNode, innerNode, nameNode, timeNode, endTimeNode, innerContainer,
			entryClassName = 'calendar-event-line-wrap',
			deltaPartWidth = 0,
			startArrow, endArrow,
			holder = params.holder || this.entryHolders[from.holderIndex];

		if (holder)
		{
			if (entry.isFullDay())
			{
				entryClassName += ' calendar-event-line-fill';
			}
			else if (entry.isLongWithTime())
			{
				entryClassName += ' calendar-event-line-border';
			}

			if (entry.getCurrentStatus() === 'N')
			{
				entryClassName += ' calendar-event-line-refused';
			}
			// if (entry.hasEmailAttendees())
			// {
			// 	entryClassName += ' calendar-event-line-intranet';
			// }

			if (entry.isExpired())
			{
				entryClassName += ' calendar-event-line-past';
			}

			if (!params.popupMode && this.util.getDayCode(entry.from) !== this.util.getDayCode(from.date))
			{
				entryClassName += ' calendar-event-line-start-yesterday';
				deltaPartWidth += 8;
				startArrow = this.getArrow('left', entry.color, entry.isFullDay());
			}

			if (!params.popupMode && this.util.getDayCode(entry.to) !== this.util.getDayCode(params.part.to.date))
			{
				entryClassName += ' calendar-event-line-finish-tomorrow';
				endArrow = this.getArrow('right', entry.color, entry.isFullDay());
				deltaPartWidth += 12;
			}

			if (startArrow && !endArrow)
			{
				deltaPartWidth += 4;
			}

			if (deltaPartWidth == 0)
			{
				deltaPartWidth = 5;
			}

			partWrap = BX.create('DIV', {
				attrs: {'data-bx-calendar-entry': entry.uid},
				props: {className: entryClassName}, style: {
					top: 0,
					left: 'calc((100% / ' + this.dayCount + ') * (' + (from.dayOffset + 1) + ' - 1) + 2px)',
					width: 'calc(' + daysCount + ' * 100% / ' + this.dayCount + ' - ' + deltaPartWidth + 'px)'
				}
			});


			if (startArrow)
			{
				partWrap.appendChild(startArrow);
				partWrap.style.left = '9px';
			}

			if (endArrow)
			{
				partWrap.appendChild(endArrow);
			}

			innerContainer = partWrap.appendChild(BX.create('DIV', {props: {className: 'calendar-event-line-inner-container'}}));
			innerNode = innerContainer.appendChild(BX.create('DIV', {props: {className: 'calendar-event-line-inner'}}));
			dotNode = innerNode.appendChild(BX.create('DIV', {props: {className: 'calendar-event-line-dot'}}));

			if (entry.isFullDay())
			{
				innerNode.style.maxWidth = 'calc(200% / ' + daysCount + ' - 3px)';
			}
			else if (entry.isLongWithTime())
			{
				partWrap.style.borderColor = entry.color;
				innerNode.style.maxWidth = 'calc(200% / ' + daysCount + ' - 3px)';

				// first part
				if (
					parseInt(params.part.partIndex) === 0
					&& (entry.to.getDate() !== params.part.from.date.getDate())
					&& (entry.from.getDate() === params.part.from.date.getDate())
				)
				{
					if (this.util.getDayCode(entry.from) !== this.util.getDayCode(from.date))
					{
						timeNode = innerNode.appendChild(
							BX.create('SPAN', {
								props: {className: 'calendar-event-line-time'},
								text: this.calendar.util.formatTime(entry.to.getHours(), entry.to.getMinutes())
							}));
					}
					else
					{
						timeNode = innerNode.appendChild(
							BX.create('SPAN', {
								props: {className: 'calendar-event-line-time'},
								text: this.calendar.util.formatTime(entry.from.getHours(), entry.from.getMinutes())
							}));
					}
					innerNode.style.width = 'calc(100% / ' + daysCount + ' - 3px)';
				}

				// Last part
				if (
					parseInt(params.part.partIndex) === entry.parts.length - 1
					&& (entry.from.getDate() !== params.part.to.date.getDate())
					&& (entry.to.getDate() === params.part.to.date.getDate())
				)
				{
					var partsLength = entry.parts.length;
					if (entry.from.getDate() !== params.part.from.date.getDate())
					{
						partsLength++;
					}
					if (daysCount > 1 && partsLength > 1)
					{
						innerNode.style.width = 'calc(' + (daysCount - 1) + '00% / ' + daysCount + ' - 3px)';
					}

					if (!params.popupMode && daysCount)
					{
						endTimeNode = innerNode.appendChild(BX.create('SPAN', {
							props: {className: (partsLength > 1 && daysCount === 1)
									? 'calendar-event-line-time'
									: 'calendar-event-line-expired-time'},
							text: this.calendar.util.formatTime(entry.to.getHours(), entry.to.getMinutes())
						}));
					}
				}
			}
			else
			{
				timeNode = innerNode.appendChild(BX.create('SPAN', {props: {className: 'calendar-event-line-time'}, text: this.calendar.util.formatTime(entry.from.getHours(), entry.from.getMinutes())}));
			}
			nameNode = innerNode
				.appendChild(BX.create('SPAN', {props: {className: 'calendar-event-line-text'}}))
				.appendChild(BX.create('SPAN', {text:  params.entry.name}));


			if (entry.isFullDay())
			{
				innerContainer.style.backgroundColor = this.calendar.util.hexToRgba(entry.color, 0.3);
				innerContainer.style.borderColor = this.calendar.util.hexToRgba(entry.color, 0.3);
			}
			else
			{
				if (entry.isLongWithTime())
				{
					innerContainer.style.borderColor = this.calendar.util.hexToRgba(entry.color, 0.5);
				}
				dotNode.style.backgroundColor = entry.color;
			}

			holder.appendChild(partWrap);

			if (entry.opacity !== undefined)
			{
				partWrap.style.opacity = entry.opacity;
			}

			res = {
				wrapNode: partWrap,
				nameNode: nameNode,
				innerContainer: innerContainer,
				innerNode: innerNode,
				timeNode: timeNode || false,
				endTimeNode: endTimeNode || false,
				dotNode: dotNode
			};

			if (!params.popupMode)
			{
				params.entry.registerPartNode(params.part, res);
			}

			this.calendar.dragDrop.registerEntry(partWrap, params);
		}

		return res;
	};


	MonthView.prototype.refreshEventsOnWeek = function(ind)
	{
		var
			startDayInd = ind * 7,
			endDayInd = (ind + 1) * 7,
			day, i, k, arEv, j, ev, arAll, arHid,
			slots = [],
			maxEventCount = 5,
			step = 0;

		for(j = 0; j < maxEventCount; j++)
			slots[j] = 0;

		for (i = startDayInd; i < endDayInd; i++)
		{
			day = this.activeDateObjDays[i];

			if (!day)
				continue;

			day.arEvents.hidden = [];
			arEv = day.arEvents.begining;
			arHid = [];

			if (arEv.length > 0)
			{
				arEv.sort(function(a, b)
				{
					if (b.daysCount == a.daysCount && a.daysCount == 1)
						return a.oEvent.DT_FROM_TS - b.oEvent.DT_FROM_TS;
					return b.daysCount - a.daysCount;
				});

				eventloop:
					for(k = 0; k < arEv.length; k++)
					{
						ev = arEv[k];
						if (!ev)
							continue;

						if (!this.arEvents[ev.oEvent.ind])
						{
							day.arEvents.begining = arEv = BX.util.deleteFromArray(arEv, k);
							ev = arEv[k];
							if (!ev)
								continue; //break ?
						}

						for(j = 0; j < this.maxEventCount; j++)
						{
							if (slots[j] - step <= 0)
							{
								slots[j] = step + ev.daysCount;
								this.ShowEventOnLevel(ev.oEvent.oParts[ev.partInd], j, ind);
								continue eventloop;
							}
						}
						arHid[ev.oEvent.ID] = true;
						day.arEvents.hidden.push(ev);
					}
			}

			// For all events in the day
			arAll = day.arEvents.all;
			for (var x = 0; x < arAll.length; x++)
			{
				ev = arAll[x];
				if (!ev || arHid[ev.oEvent.ID])
				{
					continue;
				}

				if (!this.arEvents[ev.oEvent.ind])
				{
					day.arEvents.all = arAll = BX.util.deleteFromArray(arAll, x);
					ev = arAll[x];
					if (!ev)
					{
						continue;
					}
				}

				if (ev.oEvent.oParts && ev.partInd != undefined && ev.oEvent.oParts[ev.partInd] && ev.oEvent.oParts[ev.partInd].style.display == 'none')
				{
					day.arEvents.hidden.push(ev);
				}
			}
			//this.ShowMoreEventsSelect(day);
			step++;
		}
	};

	MonthView.prototype.handleClick = function(params)
	{
		if (this.isActive())
		{
			if (!params)
				params = {};

			var dayCode, uid;
			if (params.specialTarget && (uid = params.specialTarget.getAttribute('data-bx-calendar-entry')))
			{
				this.handleEntryClick(
					{
						uid: uid,
						specialTarget: params.specialTarget,
						target: params.target,
						e: params.e
					});
			}
			else if (params.specialTarget && (dayCode = params.specialTarget.getAttribute('data-bx-calendar-show-all-events')))
			{
				this.deselectEntry();
				if (this.dayIndex[dayCode] !== undefined && this.days[this.dayIndex[dayCode]])
				{
					this.showAllEventsInPopup({day: this.days[this.dayIndex[dayCode]]});
				}
			}
			else if (
				(this.calendar.util.type === 'location' || !this.calendar.util.readOnlyMode())
				&& this.entryController.canDo(false, 'add_event')
				&& (
					dayCode = params.specialTarget
					&& params.specialTarget.getAttribute('data-bx-calendar-month-day')
				)
			)
			{
				this.deselectEntry();
				if (this.dayIndex[dayCode] !== undefined && this.days[this.dayIndex[dayCode]])
				{
					this.showNewEntryWrap({dayFrom: this.days[this.dayIndex[dayCode]]});
				}
			}
		}
	};

	MonthView.prototype.showNewEntryWrap = function(params)
	{
		var
			entryTime, entryName,
			partWrap, innerNode, innerContainer,
			entryClassName = 'calendar-event-line-wrap',
			deltaPartWidth = 0,
			from = params.dayFrom,
			daysCount = 1,
			holder = this.entryHolders[from.holderIndex],
			sectionId = BX.Calendar.SectionManager.getNewEntrySectionId(),
			section = this.calendar.sectionManager.getSection(sectionId) || this.calendar.roomsManager.getRoom(sectionId),
			color = section.color;

		var compactForm = BX.Calendar.EntryManager.getCompactViewForm(false);
		if (compactForm && compactForm.isShown())
		{
			return;
		}

		entryTime = this.entryController.getTimeForNewEntry(from.date);
		entryName = this.entryController.getDefaultEntryName();

		partWrap = BX.create('DIV', {
			props: {className: entryClassName}, style: {
				opacity: 0,
				top: 0,
				left: 'calc((100% / ' + this.dayCount + ') * (' + (from.dayOffset + 1) + ' - 1) + 2px)',
				width: 'calc(' + daysCount + ' * 100% / ' + this.dayCount + ' - ' + deltaPartWidth + 'px)'
			}
		});

		innerContainer = partWrap.appendChild(BX.create('DIV', {props: {className: 'calendar-event-line-inner-container'}}));
		innerNode = innerContainer.appendChild(BX.create('DIV', {props: {className: 'calendar-event-line-inner'}}));
		innerNode.appendChild(BX.create('SPAN', {props: {className: 'calendar-event-line-time'}, style: {color: '#fff'}, text: this.calendar.util.formatTime(entryTime.from.getHours(), entryTime.from.getMinutes())}));
		innerNode.appendChild(BX.create('SPAN', {props: {className: 'calendar-event-line-text'}, style: {color: '#fff'}, text: entryName}));
		partWrap.style.backgroundColor = color;
		partWrap.style.borderColor = color;
		holder.appendChild(partWrap);

		var entryClone = BX.adjust(this.gridMonthContainer.appendChild(partWrap.cloneNode(true)), {
			props: {className: 'calendar-event-line-clone'},
			style: {
				width: (partWrap.offsetWidth - 3) + 'px',
				height: partWrap.offsetHeight + 'px',
				top : (holder.offsetTop + holder.parentNode.offsetTop) + 'px',
				left : (partWrap.offsetLeft)+ 'px'
			}
		});

		BX.addClass(holder, 'shifted');
		holder.style.height = (this.slotsCount - 1) * this.slotHeight + 'px';

		setTimeout(function(){
			entryClone.style.opacity = '1';
		},100);

		setTimeout(BX.delegate(function(){

			// Show simple add entry popup
			this.showCompactEditForm({
				entryTime: entryTime,
				entryName: entryName,
				nameNode: entryClone.querySelector('.calendar-event-line-text'),
				timeNode: entryClone.querySelector('.calendar-event-line-time'),
				entryNode: entryClone,
				section: section,
				closeCallback: function()
				{
					BX.cleanNode(entryClone, true);
					BX.cleanNode(partWrap, true);
					var shiftedHolder = this.gridWrap.querySelector('.calendar-grid-month-events-holder.shifted');
					if (shiftedHolder)
					{
						BX.removeClass(shiftedHolder, 'shifted');
						shiftedHolder.style.height = '1px';
					}
				}.bind(this)
			});

			BX.Event.EventEmitter.unsubscribeAll('BX.Calendar.CompactEventForm:onChange');
			BX.Event.EventEmitter.subscribe('BX.Calendar.CompactEventForm:onChange', function(event)
			{
				if (event instanceof BX.Event.BaseEvent && entryClone && entryClone.parentNode)
				{
					var data = event.getData();
					var dateTime = data.form.dateTimeControl.getValue();
					var dayCode = this.util.getDayCode(dateTime.from);

					if (dayCode && this.dayIndex[dayCode] !== undefined && this.days[this.dayIndex[dayCode]])
					{
						var dayFrom = this.days[this.dayIndex[dayCode]];
						partWrap.style.left = 'calc((100% / ' + this.dayCount + ') * (' + (dayFrom.dayOffset + 1) + ' - 1) + 2px)';

						var shiftedHolder = this.gridWrap.querySelector('.calendar-grid-month-events-holder.shifted');
						if (shiftedHolder)
						{
							BX.removeClass(shiftedHolder, 'shifted');
							shiftedHolder.style.height = '1px';
						}
						var holder = this.entryHolders[dayFrom.holderIndex];
						holder.appendChild(partWrap);
						BX.adjust(entryClone, {
							style: {
								width: (partWrap.offsetWidth - 3) + 'px',
								height: partWrap.offsetHeight + 'px',
								top : (holder.offsetTop + holder.parentNode.offsetTop) + 'px',
								left : (partWrap.offsetLeft) + 'px'
							}
						});
						BX.addClass(holder, 'shifted');
						holder.style.height = (this.slotsCount - 1) * this.slotHeight + 'px';
					}

					var color = data.form.colorSelector.getValue();
					entryClone.style.background = color;
					entryClone.style.borderColor = color;
				}
			}.bind(this));

		}, this), 200);
	};

	if (window.BXEventCalendar)
	{
		window.BXEventCalendar.CalendarMonthView = MonthView;
	}
	else
	{
		BX.addCustomEvent(window, "onBXEventCalendarInit", function()
		{
			window.BXEventCalendar.CalendarMonthView = MonthView;
		});
	}
})(window);
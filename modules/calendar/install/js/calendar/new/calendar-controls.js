;(function(window) {
	function SelectInput(params)
	{
		this.id = params.id || 'bx-select-input-' + Math.round(Math.random() * 1000000);
		this.values = params.values || false;
		this.input = params.input;
		this.defaultValue = params.defaultValue || '';
		this.openTitle = params.openTitle || '';
		this.className = params.className || '';
		this.currentValue = params.value;
		this.currentValueIndex = params.valueIndex;
		this.onChangeCallback = params.onChangeCallback || null;
		this.zIndex = params.zIndex || 1200;
		this.disabled = params.disabled;
		if (this.onChangeCallback)
		{
			BX.bind(this.input, 'change', this.onChangeCallback);
			BX.bind(this.input, 'keyup', this.onChangeCallback);
		}

		if (this.currentValueIndex !== undefined && this.values[this.currentValueIndex])
		{
			this.input.value = this.values[this.currentValueIndex].label;
		}

		this.curInd = false;

		if (this.values)
		{
			BX.bind(this.input, 'click', BX.proxy(this.onClick, this));
			BX.bind(this.input, 'focus', BX.proxy(this.onFocus, this));
			BX.bind(this.input, 'blur', BX.proxy(this.onBlur, this));
			BX.bind(this.input, 'keyup', BX.proxy(this.onKeyup, this));
		}
	}

	SelectInput.prototype = {
		showPopup: function()
		{
			if (this.shown || this.disabled)
				return;

			var
				ind = 0,
				j = 0,
				menuItems = [],
				i, _this = this;

			for (i = 0; i < this.values.length; i++)
			{
				if (this.values[i].delimiter)
				{
					menuItems.push(this.values[i]);
				}
				else
				{
					if (this.currentValue && this.values[i] && this.values[i].value == this.currentValue.value)
					{
						ind = j;
					}

					menuItems.push({
						id: this.values[i].value,
						text: this.values[i].label,
						onclick: this.values[i].callback || (function (value, label)
						{
							return function ()
							{
								_this.input.value = label;
								_this.popupMenu.close();
								_this.onChange();
							}
						})(this.values[i].value, this.values[i].labelRaw || this.values[i].label)
					});
					j++;
				}
			}

			this.popupMenu = BX.PopupMenu.create(
				this.id,
				this.input,
				menuItems,
				{
					closeByEsc : true,
					autoHide : true,
					zIndex: this.zIndex,
					offsetTop: 0,
					offsetLeft: 0
				}
			);
			this.popupMenu.popupWindow.setWidth(this.input.offsetWidth - 2);

			var menuContainer = this.popupMenu.layout.menuContainer;
			BX.addClass(this.popupMenu.layout.menuContainer, 'calendar-select-popup');
			this.popupMenu.show();

			var menuItem = this.popupMenu.menuItems[ind];
			if (menuItem && menuItem.layout)
			{
				menuContainer.scrollTop = menuItem.layout.item.offsetTop - menuItem.layout.item.offsetHeight;
			}

			BX.addCustomEvent(this.popupMenu.popupWindow, 'onPopupClose', function()
			{
				BX.PopupMenu.destroy(_this.id);
				_this.shown = false;
				_this.popupMenu = null;
			});

			this.input.select();

			this.shown = true;
		},

		closePopup: function()
		{
			BX.PopupMenu.destroy(this.id);
			this.popupMenu = null;
			this.shown = false;
		},

		onFocus: function()
		{
			setTimeout(BX.delegate(function(){
				if (!this.shown)
				{
					this.showPopup();
				}
			}, this), 200);
		},

		onClick: function()
		{
			if (this.shown)
			{
				this.closePopup();
			}
			else
			{
				this.showPopup();
			}
		},

		onBlur: function()
		{
			setTimeout(BX.delegate(this.closePopup, this), 200);
		},

		onKeyup: function()
		{
			setTimeout(BX.delegate(this.closePopup, this), 50);
		},

		onChange: function()
		{
			var val = this.input.value;
			BX.onCustomEvent(this, 'onSelectInputChanged', [this, val]);
			if (BX.type.isFunction(this.onChangeCallback))
			{
				this.onChangeCallback({value: val});
			}
		},

		destroy: function()
		{
			if (this.onChangeCallback)
			{
				BX.unbind(this.input, 'change', this.onChangeCallback);
				BX.unbind(this.input, 'keyup', this.onChangeCallback);
			}

			BX.unbind(this.input, 'click', BX.proxy(this.onClick, this));
			BX.unbind(this.input, 'focus', BX.proxy(this.onFocus, this));
			BX.unbind(this.input, 'blur', BX.proxy(this.onBlur, this));
			BX.unbind(this.input, 'keyup', BX.proxy(this.onKeyup, this));

			if (this.popupMenu)
				this.popupMenu.close();
			BX.PopupMenu.destroy(this.id);
			this.popupMenu = null;
			this.shown = false;
		}
	};

	function NavigationCalendar(calendar, params)
	{
		this.calendar = calendar;
		this.outerWrap = params.wrap;
		this.created = false;
	}

	NavigationCalendar.prototype = {
		show: function ()
		{
			if (!this.created)
			{
				this.smallCalendar = new BX.JCCalendar();
				this.smallCalendar.month_popup_classname = 'calendar-navi-month-popup';
				this.smallCalendar.year_popup_classname = 'calendar-navi-year-popup';

				this.smallCalendar.Show({
					node: this.outerWrap,
					callback_after: BX.proxy(this.changeDate, this),
					bTime: false
				});

				this.outerWrap.appendChild(this.smallCalendar.DIV);
				this.smallCalendar.popup.close();
				this.created = true;
				this.paintTodayElement();
				BX.addCustomEvent(this.calendar, 'changeViewRange', BX.proxy(this.setDate, this));
			}
			this.outerWrap.style.display = '';
		},

		hide: function ()
		{
			this.outerWrap.style.display = 'none';
		},

		changeDate: function(date)
		{
			if (date && this.calendar.getView())
			{
				this.calendar.getView().adjustViewRangeToDate(date);
			}
		},

		paintTodayElement: function()
		{
			var todayDate = new Date();
			todayDate.setHours(0, 0, 0, 0);
			var timezoneOffset = parseInt(todayDate.getTimezoneOffset());
			var timestamp = todayDate.getTime();
			if (timezoneOffset !== 0)
			{
				timestamp -= ((timezoneOffset * 60) * 1000);
			}

			var todayElement = document.querySelector('[data-date="' + timestamp + '"]');
			if (BX.Type.isDomNode(todayElement))
			{
				BX.Dom.addClass(todayElement, 'bx-calendar-today-date');
			}
		},

		setDate: function(date)
		{
			if(date && this.smallCalendar.value
				&& this.calendar.util.getDayCode(this.smallCalendar.value) != this.calendar.util.getDayCode(date))
			{
				date.setHours(12, 0);
				this.smallCalendar.SetValue(date);
			}
		}
	};

	function DragDrop(calendar)
	{
		this.calendar = calendar;
	}

	DragDrop.prototype = {
		reset: function()
		{
			jsDD.Reset();
		},

		registerDay: function(day)
		{
			var dayNode = day.node;
			jsDD.registerDest(dayNode);

			dayNode.onbxdestdragfinish = BX.delegate(function()
			{
				if (this.draggedNode)
				{
					var entry = this.currentState.entry;
					day.date.setHours(0, 0, 0, 0);
					entry.from.setFullYear(day.date.getFullYear(), day.date.getMonth(), day.date.getDate());
					entry.to = new Date(entry.from.getTime() + (entry.data.DT_LENGTH - (entry.fullDay ? 1 : 0)) * 1000);
					entry.startDayCode = entry.from;
					entry.endDayCode = entry.to;
					entry.opacity = '0';

					this.calendar.getView().displayEntries({reloadEntries: false});
					var firstPart = entry.getWrap(0);

					BX.addClass(this.draggedNode, 'animate');
					setTimeout(BX.delegate(function ()
					{
						this.draggedNode.style.top = BX.pos(firstPart).top + 'px';
						this.draggedNode.style.left = BX.pos(firstPart).left + 'px';
					}, this), 1);

					setTimeout(BX.delegate(function ()
					{
						delete entry.opacity;
						entry.parts.forEach(function (part)
						{
							part.params.wrapNode.style.opacity = '';
						});
						BX.remove(this.draggedNode);
					}, this), 300);

					this.calendar.entryController.moveEventToNewDate(this.currentState.entry, day.date);
					BX.removeClass(dayNode, 'calendar-grid-drag-select');
				}
				return true;
			}, this);
			dayNode.onbxdestdraghover = function()
			{
				BX.addClass(dayNode, 'calendar-grid-drag-select');
			};
			dayNode.onbxdestdraghout = function()
			{
				BX.removeClass(dayNode, 'calendar-grid-drag-select');
			};
		},

		registerTimelineDay: function(day)
		{
			var dayNode = day.node;
			jsDD.registerDest(dayNode);

			dayNode.onbxdestdragfinish = BX.delegate(function(currentNode)
			{
				if (this.calendar.util.type !== 'location' && currentNode.getAttribute('data-bx-entry-resizer') === 'Y' && this.resizedState)
				{
					this.calendar.entryController.moveEventToNewDate(this.resizedState.entry, this.resizedState.entry.from, this.resizedState.entry.to);
					return true;
				}
				else if (this.draggedNode)
				{
					var entry = this.currentState.entry;
					entry.from.setFullYear(day.date.getFullYear(), day.date.getMonth(), day.date.getDate());
					entry.to = new Date(entry.from.getTime() + (entry.data.DT_LENGTH - (entry.fullDay ? 1 : 0)) * 1000);
					if (this.calendar.util.getDayCode(entry.from) != this.calendar.util.getDayCode(entry.to) && entry.to.getHours() == 0 && entry.to.getMinutes() == 0)
					{
						entry.to = new Date(entry.to.getTime() - 1000 * 60);
					}

					entry.startDayCode = entry.from;
					entry.endDayCode = entry.to;
					entry.opacity = '0';

					this.calendar.getView().displayEntries({reloadEntries: false});
					var firstPart = entry.getWrap(0);

					BX.addClass(this.draggedNode, 'animate');
					setTimeout(BX.delegate(function(){
						var partPos = BX.pos(firstPart);
						this.draggedNode.style.top = partPos.top + 'px';
						this.draggedNode.style.left = partPos.left + 'px';
						this.draggedNode.style.height = partPos.height + 'px';
						this.draggedNode.style.width = partPos.width + 'px';
						this.draggedNode.style.opacity = '0.6';
					}, this),1);

					setTimeout(BX.delegate(function()
					{
						delete entry.opacity;
						entry.parts.forEach(function(part){
							part.params.wrapNode.style.opacity = '';
						});
						BX.remove(this.draggedNode);
					}, this), 250);

					this.calendar.entryController.moveEventToNewDate(this.currentState.entry, entry.from, entry.to);
					BX.removeClass(dayNode, 'calendar-timeline-drag-select');
				}

				return true;
			}, this);
			dayNode.onbxdestdraghover = BX.delegate(function()
			{
				if (this.draggedNode)
				{
					var posLeft = (BX.pos(dayNode).left + 4);
					if (Math.abs(posLeft - parseInt(this.draggedNode.style.left)) > 30)
					{
						BX.addClass(this.draggedNode, 'animate');
						setTimeout(BX.delegate(function(){
							this.draggedNode.style.left = (BX.pos(dayNode).left + 4) + 'px';
						}, this),1);

						if (this.clearAnimateTimeout)
						{
							clearTimeout(this.clearAnimateTimeout);
						}
						this.clearAnimateTimeout = setTimeout(BX.delegate(function()
						{
							BX.removeClass(this.draggedNode, 'animate');
						}, this),300);
					}
					BX.addClass(dayNode, 'calendar-timeline-drag-select');
				}
			}, this);
			dayNode.onbxdestdraghout = BX.delegate(function()
			{
				if (this.draggedNode)
				{
					BX.removeClass(dayNode, 'calendar-timeline-drag-select');
				}
			}, this);
		},

		registerEntry: function(node, params)
		{
			var dragAllowed = false;
			if (this.calendar.isExternalMode())
			{
				dragAllowed = params.entry && params.entry.data && params.entry.data.ALLOW_DRAGDROP;
			}
			else
			{
				dragAllowed = this.calendar.entryController.canDo(params.entry, 'edit') && !params.entry.isLocation();
			}

			jsDD.registerObject(node);

			node.onbxdragstart = BX.delegate(function()
			{
				if (!dragAllowed)
				{
					this.draggedNode = false;
					BX.addClass(node, 'calendar-entry-shake-mode');
					if (this.denyDragTimeout)
						clearTimeout(this.denyDragTimeout);
					this.denyDragTimeout = setTimeout(function(){BX.removeClass(node, 'calendar-entry-shake-mode');}, 1000);
					return;
				}

				this.currentState = params;
				this.draggedNode = document.body.appendChild(node.cloneNode(true));
				node.style.opacity = '0.3';
				BX.addClass(this.draggedNode, 'calendar-entry-drag-mode');
				BX.removeClass(this.draggedNode, 'calendar-event-line-start-yesterday');
				BX.removeClass(this.draggedNode, 'calendar-event-line-finish-tomorrow');

				if (this.calendar.currentViewName === 'week' || this.calendar.currentViewName === 'day')
				{
					this.draggedNode.style.left = (BX.pos(node).left + 2) + 'px';
					this.draggedNode.style.width = (this.calendar.getView().getDayWidth() - 5) + 'px';
					this.currentState.offtimeTuneBaseZeroPos = BX.pos(this.calendar.getView().timeLinesCont).top;
					this.currentState.bottomBasePos = BX.pos(this.calendar.getView().bottomOffHours).bottom - 2;
				}
				else
				{
					this.draggedNode.style.width = this.calendar.getView().getDayWidth() + 'px';
				}

				var
					entry = this.currentState.entry,
					dayLength = entry.getLengthInDays(),
					resizer = this.draggedNode.querySelector('.calendar-event-resizer'),
					innerContainer = this.draggedNode.querySelector('.calendar-event-line-inner-container'),
					innerBackground = this.draggedNode.querySelector('.calendar-event-block-background'),
					lineInner = this.draggedNode.querySelector('.calendar-event-line-inner');

				if (dayLength > 1)
				{
					var textNode = this.draggedNode.querySelector('.calendar-event-line-text');
					if (textNode)
					{
						textNode.innerHTML = '<span class="calendar-event-line-days-count">(' + BX.message('EC_DAY_LENGTH').replace('#COUNT#', dayLength) + ')</span> ' + textNode.innerHTML;
					}
				}

				if (innerContainer)
				{
					if (entry.isFullDay())
					{
						innerContainer.style.backgroundColor = this.calendar.util.hexToRgba(entry.color, 0.7);
						innerContainer.style.borderColor = this.calendar.util.hexToRgba(entry.color, 0.7);
					}
					else
					{
						if (entry.isLongWithTime())
						{
							innerContainer.style.borderColor = this.calendar.util.hexToRgba(entry.color, 0.7);
						}
					}
				}

				if (innerBackground)
				{
					innerBackground.style.opacity = '0.45';
				}

				if (lineInner)
				{
					lineInner.style.maxWidth = '';
				}

				if (this.calendar.getView().allEventsPopup)
				{
					this.calendar.getView().allEventsPopup.close()
				}
			}, this);

			node.onbxdrag = BX.delegate(function(x, y)
			{
				if (this.draggedNode)
				{
					if (this.calendar.currentViewName === 'week' || this.calendar.currentViewName === 'day')
					{
						var
							timeFrom,timeNode,
							deltaTop = 7,
							entry = this.currentState.entry,
							view = this.calendar.getView(),
							nodeHeight = this.draggedNode.offsetHeight,
							nodeTop = (y - deltaTop);

						if (nodeTop < this.currentState.offtimeTuneBaseZeroPos)
						{
							BX.addClass(this.draggedNode, 'calendar-entry-shake-mode');
							if (this.shakeTimeout)
								clearTimeout(this.shakeTimeout);
							this.shakeTimeout = setTimeout(BX.proxy(function(){BX.removeClass(this.draggedNode, 'calendar-entry-shake-mode');}, this), 400);
							nodeTop = this.currentState.offtimeTuneBaseZeroPos;
						}
						else if (nodeTop + nodeHeight > this.currentState.bottomBasePos)
						{
							BX.addClass(this.draggedNode, 'calendar-entry-shake-mode');
							if (this.shakeTimeout)
								clearTimeout(this.shakeTimeout);
							this.shakeTimeout = setTimeout(BX.proxy(function(){BX.removeClass(this.draggedNode, 'calendar-entry-shake-mode');}, this), 400);

							nodeTop = this.currentState.bottomBasePos - nodeHeight;
						}

						timeFrom = view.getTimeByPos(nodeTop - this.currentState.offtimeTuneBaseZeroPos, 5);
						timeNode = this.draggedNode.querySelector('.calendar-event-block-time');

						this.draggedNode.style.top = nodeTop + 'px';

						if (timeNode && timeFrom)
						{
							entry.from.setHours(timeFrom.h, timeFrom.m);
							entry.to = new Date(entry.from.getTime() + (entry.data.DT_LENGTH - (entry.fullDay ? 1 : 0)) * 1000);
							if (this.calendar.util.getDayCode(entry.from) != this.calendar.util.getDayCode(entry.to) && entry.to.getHours() == 0 && entry.to.getMinutes() == 0)
							{
								entry.to = new Date(entry.to.getTime() - 1000);
							}

							timeNode.innerHTML = this.calendar.util.formatTime(entry.from) + ' &ndash; ' + this.calendar.util.formatTime(entry.to);
						}
					}
					else
					{
						this.draggedNode.style.top = (y - 10) + 'px';
						this.draggedNode.style.left = (x - 20) + 'px';
					}

					//if (tab == 'week_title')
					//{
					//	// We move event from title to timeline (week, day mode)
					//	_this.CheckTimelineOverPos(x, y);
					//}
				}
			}, this);

			node.onbxdragstop = BX.delegate(function()
			{
				setTimeout(BX.delegate(function()
				{
					BX.remove(this.draggedNode);
				}, this), 400);
			}, this);


			if (params.part.params.resizerNode)
			{
				this.registerResizer(params.part.params.resizerNode, params);
			}
		},

		registerResizer: function(node, params)
		{
			node.setAttribute('data-bx-entry-resizer', 'Y');

			BX.bind(node, "mousedown", BX.delegate(function(e)
			{
				e = e || window.event;

				this.resizedState = {
					entry: params.entry,
					entryWrap: params.part.params.wrapNode,
					node: node,
					startY: e.clientY + BX.GetWindowSize().scrollTop,
					height: parseInt(params.part.params.wrapNode.offsetHeight) || 0
				};
			}, this));

			jsDD.registerObject(node);

			node.onbxdrag = BX.delegate(function(x, y)
			{
				if (this.resizedState && this.calendar.util.type !== 'location')
				{
					var
						entry = this.resizedState.entry,
						height = Math.max((this.resizedState.height + y - this.resizedState.startY + 5), 5),
						timeTo = this.calendar.getView().getTimeByPos(parseInt(this.resizedState.entryWrap.style.top) + height, 5),
						timeLabel = this.calendar.util.formatTime(entry.from) + ' &ndash; ' + this.calendar.util.formatTime(timeTo.h, timeTo.m),
						timeNode = this.resizedState.entryWrap.querySelector('.calendar-event-block-time');

					entry.to.setHours(timeTo.h, timeTo.m, 0);

					if (timeNode)
					{
						timeNode.innerHTML = timeLabel;
					}
					this.resizedState.entryWrap.style.height = height + 'px';
				}
			}, this);

			node.onbxdragstop = function()
			{
				setTimeout(BX.delegate(function()
				{
					if (this.resizedState)
					{
						this.resizedState = null;
					}
				}, this), 400);
			};
		}
	};


	function SectionSelector(params)
	{
		this.id = params.id || 'section-select-' + Math.round(Math.random() * 1000000);
		this.sectionList = params.sectionList;
		this.sectionGroupList = params.sectionGroupList;
		this.selectCallback = params.selectCallback;
		this.openPopupCallback = params.openPopupCallback;
		this.closePopupCallback = params.closePopupCallback;
		this.getCurrentSection = params.getCurrentSection;
		this.zIndex = params.zIndex || 1200;
		this.mode = params.mode;
		this.DOM = {
			outerWrap: params.outerWrap
		};

		this.init();
	}

	SectionSelector.prototype = {
		init: function()
		{
			this.DOM.select = this.DOM.outerWrap.appendChild(BX.create('DIV', {
				props: {className: 'calendar-field calendar-field-select' + (this.mode === 'compact' ? ' calendar-field-tiny' : '')},
				events: {
					click: BX.delegate(this.openPopup, this)
				}
			}));

			this.DOM.innerValue = this.DOM.select.appendChild(BX.create('DIV', {
				props: {className: 'calendar-field-select-icon'},
				style: {backgroundColor : this.getCurrentColor()}
			}));

			if (this.mode === 'full')
			{
				this.DOM.selectInnerText = this.DOM.select.appendChild(BX.create('SPAN', {text: this.getCurrentTitle()}));
			}
		},

		openPopup: function() {
			if (this.sectionMenu && this.sectionMenu.popupWindow && this.sectionMenu.popupWindow.isShown())
			{
				return this.sectionMenu.close();
			}

			var
				i, menuItems = [], icon;

			if (BX.type.isArray(this.sectionGroupList))
			{
				this.sectionGroupList.forEach(function(sectionGroup)
				{
					var filteredList = [], i;
					if (sectionGroup.belongsToView)
					{
						filteredList = this.sectionList.filter(function(section){
							return section.belongsToView();
						});
					}
					else if (sectionGroup.type === 'user')
					{
						filteredList = this.sectionList.filter(function(section){
							return section.type === 'user' && section.ownerId === sectionGroup.ownerId;
						});
					}
					else if (sectionGroup.type === 'company')
					{
						filteredList = this.sectionList.filter(function(section){
							return section.type === 'company_calendar'
								|| section.type === 'calendar_company'
								|| section.type === sectionGroup.type;
						});
					}
					else
					{
						filteredList = this.sectionList.filter(function(section){
							return section.type === sectionGroup.type;
						});
					}

					if (filteredList.length > 0)
					{
						menuItems.push(
							new BX.Main.Popup.MenuItem({
							text: sectionGroup.title,
							delimiter: true
						}));

						for (i = 0; i < filteredList.length; i++)
						{
							menuItems.push(this.getMenuItem(filteredList[i]));
						}
					}
				}, this);
			}
			else
			{
				for (i = 0; i < this.sectionList.length; i++)
				{
					menuItems.push(this.getMenuItem(this.sectionList[i]));
				}
			}

			this.sectionMenu = BX.PopupMenu.create(
				this.id,
				this.DOM.select,
				menuItems,
				{
					closeByEsc : true,
					autoHide : true,
					zIndex: this.zIndex,
					offsetTop: 0,
					offsetLeft: this.mode === 'compact' ? 40 : 0,
					angle: this.mode === 'compact'
				}
			);

			this.sectionMenu.popupWindow.contentContainer.style.overflow = "auto";
			this.sectionMenu.popupWindow.contentContainer.style.maxHeight = "400px";

			if (this.mode === 'full')
			{
				this.sectionMenu.popupWindow.setWidth(this.DOM.select.offsetWidth - 2);
				this.sectionMenu.popupWindow.contentContainer.style.overflowX = "hidden";
			}

			this.sectionMenu.show();

			// Paint round icons for section menu
			for (i = 0; i < this.sectionMenu.menuItems.length; i++)
			{
				if (this.sectionMenu.menuItems[i].layout.item)
				{
					icon = this.sectionMenu.menuItems[i].layout.item.querySelector('.menu-popup-item-icon');
					if (icon)
					{
						icon.style.backgroundColor = this.sectionMenu.menuItems[i].color;
					}
				}
			}

			BX.addClass(this.DOM.select, 'active');

			if (BX.type.isFunction(this.openPopupCallback))
			{
				this.openPopupCallback(this);
			}

			BX.addCustomEvent(this.sectionMenu.popupWindow, 'onPopupClose', BX.delegate(function()
			{
				if (BX.type.isFunction(this.openPopupCallback))
				{
					this.closePopupCallback();
				}
				BX.removeClass(this.DOM.select, 'active');
				BX.PopupMenu.destroy(this.id);
				this.sectionMenu = null;
			}, this));
		},

		getCurrentColor: function()
		{
			return (this.getCurrentSection() || {}).color || false;
		},

		getCurrentTitle: function()
		{
			return (this.getCurrentSection() || {}).name || '';
		},

		getPopup: function()
		{
			return this.sectionMenu;
		},

		getMenuItem: function(sectionItem)
		{
			var _this = this;
			return {
				text: BX.util.htmlspecialchars(sectionItem.name),
					color: sectionItem.color,
					className: 'calendar-add-popup-section-menu-item',
					onclick: (function (section)
				{
					return function ()
					{
						_this.DOM.innerValue.style.backgroundColor = section.color;
						if (_this.DOM.selectInnerText)
						{
							_this.DOM.selectInnerText.innerHTML = BX.util.htmlspecialchars(section.name);
						}

						if (BX.type.isFunction(_this.selectCallback))
						{
							_this.selectCallback(section);
						}
						_this.sectionMenu.close();
					}
				})(sectionItem)
			}
		}
	};

	if (window.BXEventCalendar)
	{
		window.BXEventCalendar.SelectInput = SelectInput;
		window.BXEventCalendar.NavigationCalendar = NavigationCalendar;
		window.BXEventCalendar.DragDrop = DragDrop;
		window.BXEventCalendar.SectionSelector = SectionSelector;
	}
	else
	{
		BX.addCustomEvent(window, "onBXEventCalendarInit", function()
		{
			window.BXEventCalendar.SelectInput = SelectInput;
			window.BXEventCalendar.NavigationCalendar = NavigationCalendar;
			window.BXEventCalendar.DragDrop = DragDrop;
			window.BXEventCalendar.SectionSelector = SectionSelector;
		});
	}
})(window);
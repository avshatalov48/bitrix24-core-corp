/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_formElements_view,tasks_dateRounder,main_date,ui_datePicker,main_core_events,main_popup,ui_buttons,main_core,tasks_intervalSelector,ui_analytics) {
	'use strict';

	var _EVENT_NAMESPACE = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("EVENT_NAMESPACE");
	var _calendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calendar");
	var _datePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("datePicker");
	var _input = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _matchWorkTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("matchWorkTime");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _getDefaultTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDefaultTime");
	var _onSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelect");
	var _onHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onHide");
	var _setInputValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setInputValue");
	var _getDateFormatByDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDateFormatByDate");
	class DeadlinePicker extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getDateFormatByDate, {
	      value: _getDateFormatByDate2
	    });
	    Object.defineProperty(this, _setInputValue, {
	      value: _setInputValue2
	    });
	    Object.defineProperty(this, _onHide, {
	      value: _onHide2
	    });
	    Object.defineProperty(this, _onSelect, {
	      value: _onSelect2
	    });
	    Object.defineProperty(this, _getDefaultTime, {
	      value: _getDefaultTime2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _EVENT_NAMESPACE, {
	      writable: true,
	      value: 'BX.Tasks.Deadline.Notification.Layout.DeadlinePicker'
	    });
	    Object.defineProperty(this, _calendar, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _datePicker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _input, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _matchWorkTime, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(babelHelpers.classPrivateFieldLooseBase(this, _EVENT_NAMESPACE)[_EVENT_NAMESPACE]);
	    this.subscribeFromOptions(_options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init](_options);
	  }
	  getSelectedDate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].getSelectedDate();
	  }
	  getToday() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].getToday();
	  }
	  formatDate(date, format) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].formatDate(date, format);
	  }
	}
	function _init2(options) {
	  const calendar = options.calendar;
	  if (calendar instanceof BX.Tasks.Calendar) {
	    babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar] = calendar;
	  }
	  const defaultTime = babelHelpers.classPrivateFieldLooseBase(this, _getDefaultTime)[_getDefaultTime]();
	  const input = options.input;
	  if (input instanceof HTMLInputElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input] = input;
	  }
	  const matchWorkTime = options.matchWorkTime;
	  babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime)[_matchWorkTime] = main_core.Type.isBoolean(matchWorkTime) ? matchWorkTime : false;
	  const addZero = unit => `0${unit}`.slice(-2);
	  const pickerOptions = {
	    defaultTime: `${addZero(defaultTime.getHours())}:${addZero(defaultTime.getMinutes())}`,
	    targetNode: babelHelpers.classPrivateFieldLooseBase(this, _input)[_input],
	    enableTime: true,
	    cutZeroTime: false,
	    events: {
	      onSelect: babelHelpers.classPrivateFieldLooseBase(this, _onSelect)[_onSelect].bind(this),
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _onHide)[_onHide].bind(this)
	    }
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker] = new ui_datePicker.DatePicker({
	    ...options,
	    ...pickerOptions
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _input)[_input], 'click', babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].show.bind(babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker]));
	}
	function _getDefaultTime2() {
	  const now = new Date();
	  const workInterval = babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar].getWorkIntervals(now).pop();
	  return new Date(workInterval.endDate.toLocaleString('en-US', {
	    timeZone: 'UTC'
	  }));
	}
	function _onSelect2(event) {
	  const {
	    date
	  } = event.getData();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime)[_matchWorkTime]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setInputValue)[_setInputValue](date);
	    this.emit('onSelect');
	    return;
	  }
	  const closestWorkDate = babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar].getClosestWorkTime(date, true);
	  if (date.getTime() !== closestWorkDate.getTime()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].selectDate(new Date(closestWorkDate.toLocaleString('en-US', {
	      timeZone: 'UTC'
	    })));
	    babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].hide();
	    this.emit('onMoveDateToWorkTime');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setInputValue)[_setInputValue](closestWorkDate);
	  this.emit('onSelect');
	}
	function _onHide2() {
	  const date = this.getSelectedDate();
	  const now = this.getToday();
	  if (main_core.Type.isDate(date) && date < now) {
	    this.emit('onLessCurrentDateSelect');
	  }
	}
	function _setInputValue2(date) {
	  const dateFormat = babelHelpers.classPrivateFieldLooseBase(this, _getDateFormatByDate)[_getDateFormatByDate](date);
	  babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].value = babelHelpers.classPrivateFieldLooseBase(this, _datePicker)[_datePicker].formatDate(date, dateFormat);
	}
	function _getDateFormatByDate2(date) {
	  const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	  const now = new Date();
	  let dateFormat = main_date.DateTimeFormat.getFormat('DAY_OF_WEEK_MONTH_FORMAT');
	  if (main_core.Type.isDate(date) && now.getFullYear() !== date.getFullYear()) {
	    dateFormat = main_date.DateTimeFormat.getFormat('FULL_DATE_FORMAT');
	  }
	  return `${dateFormat}, ${timeFormat}`;
	}

	class MovedDeadlinePopup extends main_popup.Popup {
	  constructor(options) {
	    const content = `
			<div class="tasks-deadline-notification-form__content_deadline-selector_moved-toolbar">
				${main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_MOVED_DEADLINE_TOOLTIP')}
			</div>
		`;
	    const popupOptions = {
	      id: 'deadline-notification-moved-deadline-popup',
	      offsetLeft: 30,
	      offsetTop: 5,
	      angle: true,
	      autoHide: true,
	      closeByEsc: false,
	      closeIcon: true,
	      content
	    };
	    super({
	      ...options,
	      ...popupOptions
	    });
	  }
	}

	class OverdueDeadlinePopup extends main_popup.Popup {
	  constructor(options) {
	    const content = `
			<div class="tasks-deadline-notification-form__content_deadline-selector_overdue-toolbar">
				${main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_OVERDUE_DEADLINE_TOOLTIP')}
			</div>
		`;
	    const popupOptions = {
	      id: 'deadline-notification-overdue-deadline-popup',
	      offsetLeft: 30,
	      offsetTop: 5,
	      angle: true,
	      autoHide: true,
	      closeByEsc: false,
	      closeIcon: true,
	      content
	    };
	    super({
	      ...options,
	      ...popupOptions
	    });
	  }
	}

	let _ = t => t,
	  _t,
	  _t2;
	const DAYS_IN_WEEK = 7;
	var _EVENT_NAMESPACE$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("EVENT_NAMESPACE");
	var _calendar$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calendar");
	var _deadlinePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deadlinePicker");
	var _input$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _matchWorkTime$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("matchWorkTime");
	var _movedDeadlinePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("movedDeadlinePopup");
	var _overdueDeadlinePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("overdueDeadlinePopup");
	var _init$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _initMatchWorkTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initMatchWorkTime");
	var _initCalendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCalendar");
	var _initDeadlinePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDeadlinePicker");
	var _onMoveDateToWorkTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMoveDateToWorkTime");
	var _onLessCurrentDateSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLessCurrentDateSelect");
	var _getOverdueDeadlinePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOverdueDeadlinePopup");
	var _getMovedDeadlinePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMovedDeadlinePopup");
	var _getPortalSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPortalSettings");
	class DeadlineSelector extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getPortalSettings, {
	      value: _getPortalSettings2
	    });
	    Object.defineProperty(this, _getMovedDeadlinePopup, {
	      value: _getMovedDeadlinePopup2
	    });
	    Object.defineProperty(this, _getOverdueDeadlinePopup, {
	      value: _getOverdueDeadlinePopup2
	    });
	    Object.defineProperty(this, _onLessCurrentDateSelect, {
	      value: _onLessCurrentDateSelect2
	    });
	    Object.defineProperty(this, _onMoveDateToWorkTime, {
	      value: _onMoveDateToWorkTime2
	    });
	    Object.defineProperty(this, _initDeadlinePicker, {
	      value: _initDeadlinePicker2
	    });
	    Object.defineProperty(this, _initCalendar, {
	      value: _initCalendar2
	    });
	    Object.defineProperty(this, _initMatchWorkTime, {
	      value: _initMatchWorkTime2
	    });
	    Object.defineProperty(this, _init$1, {
	      value: _init2$1
	    });
	    Object.defineProperty(this, _EVENT_NAMESPACE$1, {
	      writable: true,
	      value: 'BX.Tasks.Deadline.Notification.Layout.DeadlineSelector'
	    });
	    Object.defineProperty(this, _calendar$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _deadlinePicker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _input$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _matchWorkTime$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _movedDeadlinePopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _overdueDeadlinePopup, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(babelHelpers.classPrivateFieldLooseBase(this, _EVENT_NAMESPACE$1)[_EVENT_NAMESPACE$1]);
	    this.subscribeFromOptions(_options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _init$1)[_init$1](_options);
	  }
	  render() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-deadline-notification-form__content_deadline-selector">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-datetime ui-ctl-w75">
					<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1]);
	  }
	  getInputValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1].value;
	  }
	  getDeadline() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _deadlinePicker)[_deadlinePicker].getSelectedDate();
	  }
	  getToday() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _deadlinePicker)[_deadlinePicker].getToday();
	  }
	  getFormattedDeadline(format) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _deadlinePicker)[_deadlinePicker].formatDate(this.getDeadline(), format);
	  }
	}
	function _init2$1(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1] = main_core.Tag.render(_t2 || (_t2 = _`
			<input class="ui-ctl-element" data-id="tasks-deadline-notification-deadline" readonly>
		`));
	  const portalSettings = babelHelpers.classPrivateFieldLooseBase(this, _getPortalSettings)[_getPortalSettings]();
	  babelHelpers.classPrivateFieldLooseBase(this, _initCalendar)[_initCalendar](portalSettings);
	  babelHelpers.classPrivateFieldLooseBase(this, _initMatchWorkTime)[_initMatchWorkTime](options, portalSettings);
	  babelHelpers.classPrivateFieldLooseBase(this, _initDeadlinePicker)[_initDeadlinePicker](babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1], babelHelpers.classPrivateFieldLooseBase(this, _calendar$1)[_calendar$1], babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime$1)[_matchWorkTime$1]);
	}
	function _initMatchWorkTime2(options, portalSettings) {
	  babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime$1)[_matchWorkTime$1] = options.matchWorkTime;
	  const weekends = portalSettings.WEEKEND;
	  if (!main_core.Type.isArray(weekends)) {
	    return;
	  }
	  const countOfWeekends = weekends.length;
	  if (countOfWeekends === DAYS_IN_WEEK) {
	    babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime$1)[_matchWorkTime$1] = false;
	  }
	}
	function _initCalendar2(portalSettings) {
	  const adaptedSettings = BX.Tasks.Calendar.adaptSettings(portalSettings);
	  babelHelpers.classPrivateFieldLooseBase(this, _calendar$1)[_calendar$1] = new BX.Tasks.Calendar(adaptedSettings);
	}
	function _initDeadlinePicker2(input, calendar, matchWorkTime) {
	  babelHelpers.classPrivateFieldLooseBase(this, _deadlinePicker)[_deadlinePicker] = new DeadlinePicker({
	    events: {
	      onMoveDateToWorkTime: babelHelpers.classPrivateFieldLooseBase(this, _onMoveDateToWorkTime)[_onMoveDateToWorkTime].bind(this),
	      onLessCurrentDateSelect: babelHelpers.classPrivateFieldLooseBase(this, _onLessCurrentDateSelect)[_onLessCurrentDateSelect].bind(this),
	      onSelect: () => this.emit('onSelect')
	    },
	    input,
	    calendar,
	    matchWorkTime
	  });
	}
	function _onMoveDateToWorkTime2() {
	  const overdueDeadlinePopup = babelHelpers.classPrivateFieldLooseBase(this, _getOverdueDeadlinePopup)[_getOverdueDeadlinePopup]();
	  if (overdueDeadlinePopup.isShown()) {
	    return;
	  }
	  const movedDeadlinePopup = babelHelpers.classPrivateFieldLooseBase(this, _getMovedDeadlinePopup)[_getMovedDeadlinePopup]();
	  movedDeadlinePopup.show();
	}
	function _onLessCurrentDateSelect2() {
	  const overdueDeadlinePopup = babelHelpers.classPrivateFieldLooseBase(this, _getOverdueDeadlinePopup)[_getOverdueDeadlinePopup]();
	  overdueDeadlinePopup.show();
	}
	function _getOverdueDeadlinePopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _overdueDeadlinePopup)[_overdueDeadlinePopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _overdueDeadlinePopup)[_overdueDeadlinePopup] = new OverdueDeadlinePopup({
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1]
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _overdueDeadlinePopup)[_overdueDeadlinePopup];
	}
	function _getMovedDeadlinePopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _movedDeadlinePopup)[_movedDeadlinePopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _movedDeadlinePopup)[_movedDeadlinePopup] = new MovedDeadlinePopup({
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1]
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _movedDeadlinePopup)[_movedDeadlinePopup];
	}
	function _getPortalSettings2() {
	  const settings = main_core.Extension.getSettings('tasks.deadline.notification');
	  return settings.get('portalSettings');
	}

	var _EVENT_NAMESPACE$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("EVENT_NAMESPACE");
	var _ACCEPTED_ITEM_CLASS = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ACCEPTED_ITEM_CLASS");
	var _ITEMS_CLASSES = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ITEMS_CLASSES");
	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _period = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("period");
	var _onMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMenuItemClick");
	var _refreshMenuItemsIcons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refreshMenuItemsIcons");
	var _init$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	class SkipButtonMenu extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    super();
	    Object.defineProperty(this, _init$2, {
	      value: _init2$2
	    });
	    Object.defineProperty(this, _refreshMenuItemsIcons, {
	      value: _refreshMenuItemsIcons2
	    });
	    Object.defineProperty(this, _onMenuItemClick, {
	      value: _onMenuItemClick2
	    });
	    Object.defineProperty(this, _EVENT_NAMESPACE$2, {
	      writable: true,
	      value: 'BX.Tasks.Deadline.Notification.Layout.SkipButtonMenu'
	    });
	    Object.defineProperty(this, _ACCEPTED_ITEM_CLASS, {
	      writable: true,
	      value: 'menu-popup-item-accept'
	    });
	    Object.defineProperty(this, _ITEMS_CLASSES, {
	      writable: true,
	      value: 'menu-popup-icon menu-popup-item-none'
	    });
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _period, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(babelHelpers.classPrivateFieldLooseBase(this, _EVENT_NAMESPACE$2)[_EVENT_NAMESPACE$2]);
	    this.subscribeFromOptions(options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _init$2)[_init$2]();
	  }
	  getMenu() {
	    return {
	      closeByEsc: true,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	      minWidth: 180
	    };
	  }
	  getPeriod() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _period)[_period];
	  }
	  setButton(button) {
	    babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] = button;
	  }
	}
	function _onMenuItemClick2(item) {
	  var _babelHelpers$classPr, _item$dataset;
	  const buttonMenu = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _button)[_button]) == null ? void 0 : _babelHelpers$classPr.getMenuWindow();
	  if (!buttonMenu) {
	    return;
	  }
	  const period = item.id;
	  const text = item.text;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _period)[_period] === period) {
	    babelHelpers.classPrivateFieldLooseBase(this, _period)[_period] = '';
	    babelHelpers.classPrivateFieldLooseBase(this, _refreshMenuItemsIcons)[_refreshMenuItemsIcons](buttonMenu.getMenuItems(), babelHelpers.classPrivateFieldLooseBase(this, _period)[_period]);
	    const event = new main_core_events.BaseEvent({
	      data: {
	        buttonMenu
	      }
	    });
	    this.emit('onMenuItemDeselect', event);
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _period)[_period] = period;
	  babelHelpers.classPrivateFieldLooseBase(this, _refreshMenuItemsIcons)[_refreshMenuItemsIcons](buttonMenu.getMenuItems(), babelHelpers.classPrivateFieldLooseBase(this, _period)[_period]);
	  const event = new main_core_events.BaseEvent({
	    data: {
	      buttonMenu,
	      text
	    }
	  });
	  this.emit('onMenuItemSelect', event);
	  ui_analytics.sendData({
	    tool: 'tasks',
	    category: 'task_operations',
	    event: 'period_click_type',
	    type: 'popup',
	    c_section: 'tasks',
	    c_sub_section: 'deadline_popup',
	    c_element: (_item$dataset = item.dataset) == null ? void 0 : _item$dataset.analytics
	  });
	}
	function _refreshMenuItemsIcons2(items, period) {
	  items.forEach(item => {
	    const node = item.getLayout().item;
	    if (!node) {
	      return;
	    }
	    if (item.getId() === period) {
	      main_core.Dom.addClass(node, babelHelpers.classPrivateFieldLooseBase(this, _ACCEPTED_ITEM_CLASS)[_ACCEPTED_ITEM_CLASS]);
	      return;
	    }
	    main_core.Dom.removeClass(node, babelHelpers.classPrivateFieldLooseBase(this, _ACCEPTED_ITEM_CLASS)[_ACCEPTED_ITEM_CLASS]);
	  });
	}
	function _init2$2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = [{
	    dataset: {
	      id: 'tasks-deadline-notification-skip-day',
	      analytics: 'today_button'
	    },
	    id: 'day',
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DAY'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _ITEMS_CLASSES)[_ITEMS_CLASSES]
	  }, {
	    dataset: {
	      id: 'tasks-deadline-notification-skip-week',
	      analytics: 'current_week_button'
	    },
	    id: 'week',
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_WEEK'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _ITEMS_CLASSES)[_ITEMS_CLASSES]
	  }, {
	    dataset: {
	      id: 'tasks-deadline-notification-skip-month',
	      analytics: 'current_month_button'
	    },
	    id: 'month',
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_MONTH'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _ITEMS_CLASSES)[_ITEMS_CLASSES]
	  }, {
	    dataset: {
	      id: 'tasks-deadline-notification-skip-forever',
	      analytics: 'never_button'
	    },
	    id: 'forever',
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_FOREVER'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _ITEMS_CLASSES)[_ITEMS_CLASSES]
	  }];
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].forEach(item => {
	    item.onclick = babelHelpers.classPrivateFieldLooseBase(this, _onMenuItemClick)[_onMenuItemClick].bind(this, item);
	  });
	}

	let _$1 = t => t,
	  _t$1;
	var _EVENT_NAMESPACE$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("EVENT_NAMESPACE");
	var _submitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("submitButton");
	var _cancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelButton");
	var _skipButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipButton");
	var _skipButtonMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipButtonMenu");
	var _init$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _onSubmitButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubmitButtonClick");
	var _onCancelButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCancelButtonClick");
	var _onSkipButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipButtonClick");
	var _onSkipMenuItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemSelect");
	var _onSkipMenuItemDeselect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemDeselect");
	class Footer extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    super();
	    Object.defineProperty(this, _onSkipMenuItemDeselect, {
	      value: _onSkipMenuItemDeselect2
	    });
	    Object.defineProperty(this, _onSkipMenuItemSelect, {
	      value: _onSkipMenuItemSelect2
	    });
	    Object.defineProperty(this, _onSkipButtonClick, {
	      value: _onSkipButtonClick2
	    });
	    Object.defineProperty(this, _onCancelButtonClick, {
	      value: _onCancelButtonClick2
	    });
	    Object.defineProperty(this, _onSubmitButtonClick, {
	      value: _onSubmitButtonClick2
	    });
	    Object.defineProperty(this, _init$3, {
	      value: _init2$3
	    });
	    Object.defineProperty(this, _EVENT_NAMESPACE$3, {
	      writable: true,
	      value: 'BX.Tasks.Deadline.Notification.Layout.Footer'
	    });
	    Object.defineProperty(this, _submitButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _skipButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _skipButtonMenu, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(babelHelpers.classPrivateFieldLooseBase(this, _EVENT_NAMESPACE$3)[_EVENT_NAMESPACE$3]);
	    this.subscribeFromOptions(options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _init$3)[_init$3]();
	  }
	  render() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="tasks-deadline-notification-form__footer">
				<div class="tasks-deadline-notification-form__footer_buttons-container">
					${0}
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].render(), babelHelpers.classPrivateFieldLooseBase(this, _cancelButton)[_cancelButton].render(), babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton].render());
	  }
	  getSkipPeriod() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _skipButtonMenu)[_skipButtonMenu].getPeriod();
	  }
	  updateSkipButtonText(text) {
	    const defaultText = main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SKIP_BUTTON');
	    const skipButtonText = text === '' ? defaultText : `${defaultText}, ${text}`;
	    babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton].setText(skipButtonText);
	  }
	  enableButtons() {
	    this.enableSubmitButton();
	    this.enableCancelButton();
	    this.enableSkipButton();
	  }
	  disableButtons() {
	    this.disableSubmitButton();
	    this.disableCancelButton();
	    this.disableSkipButton();
	  }
	  enableSubmitButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setDisabled(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setColor(ui_buttons.ButtonColor.PRIMARY);
	  }
	  disableSubmitButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setDisabled();
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setColor();
	  }
	  enableCancelButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelButton)[_cancelButton].setDisabled(false);
	  }
	  disableCancelButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelButton)[_cancelButton].setDisabled();
	  }
	  enableSkipButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton].setDisabled(false);
	  }
	  disableSkipButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton].setDisabled();
	  }
	}
	function _init2$3() {
	  babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton] = new ui_buttons.Button({
	    dataset: {
	      id: 'tasks-deadline-notification-submit'
	    },
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SAVE_BUTTON'),
	    size: ui_buttons.ButtonSize.SMALL,
	    state: ui_buttons.ButtonState.DISABLED,
	    noCaps: true,
	    round: true,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSubmitButtonClick)[_onSubmitButtonClick].bind(this)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _cancelButton)[_cancelButton] = new ui_buttons.Button({
	    dataset: {
	      id: 'tasks-deadline-notification-cancel'
	    },
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_CANCEL_BUTTON_MSGVER_1'),
	    color: ui_buttons.ButtonColor.LINK,
	    size: ui_buttons.ButtonSize.SMALL,
	    state: ui_buttons.ButtonState.ACTIVE,
	    noCaps: true,
	    round: true,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onCancelButtonClick)[_onCancelButtonClick].bind(this)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _skipButtonMenu)[_skipButtonMenu] = new SkipButtonMenu({
	    events: {
	      onMenuItemSelect: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this),
	      onMenuItemDeselect: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemDeselect)[_onSkipMenuItemDeselect].bind(this)
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton] = new ui_buttons.Button({
	    dataset: {
	      id: 'tasks-deadline-notification-skip'
	    },
	    text: main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SKIP_BUTTON'),
	    className: 'tasks-deadline-notification-form__footer_skip-button',
	    color: ui_buttons.ButtonColor.LINK,
	    noCaps: true,
	    dropdown: true,
	    menu: babelHelpers.classPrivateFieldLooseBase(this, _skipButtonMenu)[_skipButtonMenu].getMenu(),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipButtonClick)[_onSkipButtonClick].bind(this)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _skipButtonMenu)[_skipButtonMenu].setButton(babelHelpers.classPrivateFieldLooseBase(this, _skipButton)[_skipButton]);
	}
	function _onSubmitButtonClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].isDisabled()) {
	    return;
	  }
	  this.emit('onSubmitButtonClick');
	  ui_analytics.sendData({
	    tool: 'tasks',
	    category: 'task_operations',
	    event: 'click_save_button',
	    type: 'popup',
	    c_section: 'tasks',
	    c_sub_section: 'deadline_field',
	    c_element: 'save_button'
	  });
	}
	function _onCancelButtonClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _cancelButton)[_cancelButton].isDisabled()) {
	    return;
	  }
	  this.emit('onCancelButtonClick');
	  ui_analytics.sendData({
	    tool: 'tasks',
	    category: 'task_operations',
	    event: 'click_no_deadline_button',
	    type: 'popup',
	    c_section: 'tasks',
	    c_sub_section: 'deadline_popup',
	    c_element: 'no_deadline_button'
	  });
	}
	function _onSkipButtonClick2() {
	  ui_analytics.sendData({
	    tool: 'tasks',
	    category: 'task_operations',
	    event: 'click_no_remind_button',
	    type: 'popup',
	    c_section: 'tasks',
	    c_sub_section: 'deadline_popup',
	    c_element: 'no_remind_button'
	  });
	}
	function _onSkipMenuItemSelect2(event) {
	  const {
	    buttonMenu,
	    text
	  } = event.getData();
	  buttonMenu.close();
	  this.updateSkipButtonText(text.toLowerCase());
	  this.emit('onSkipMenuItemSelect', event);
	}
	function _onSkipMenuItemDeselect2(event) {
	  const {
	    buttonMenu
	  } = event.getData();
	  buttonMenu.close();
	  this.updateSkipButtonText('');
	  this.emit('onSkipMenuItemDeselect', event);
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1;
	function removeNonNumeric(value) {
	  return value.replaceAll(/\D/g, '');
	}
	function clamp(value, min, max) {
	  let clampedValue = Math.max(value, min);
	  clampedValue = Math.min(clampedValue, max);
	  return clampedValue;
	}
	const MAX_INT = 2 ** 32 / 2 - 1;
	const SECONDS_IN_MONTH = 60 * 60 * 24 * 31;
	var _intervalSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("intervalSelector");
	var _input$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _onInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onInput");
	class DefaultDeadlineSelector {
	  constructor() {
	    Object.defineProperty(this, _onInput, {
	      value: _onInput2
	    });
	    Object.defineProperty(this, _intervalSelector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _input$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2] = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<input class="ui-ctl-element" placeholder="0" data-id="tasks-deadline-notification-default-deadline">
		`));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2], 'input', babelHelpers.classPrivateFieldLooseBase(this, _onInput)[_onInput].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _intervalSelector)[_intervalSelector] = new tasks_intervalSelector.IntervalSelector({
	      showDropdownIcon: true
	    });
	  }
	  render() {
	    return main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
			<div hidden>
				<div class="tasks-deadline-notification-form__content_default-deadline-container">
					<div class="ui-ctl ui-ctl-w25">
						${0}
					</div>
					<div
						class="tasks-deadline-notification-form__content_default-deadline-container_interval-selector"
						data-id="tasks-deadline-notification-interval-selector"
					>
						${0}
					</div>
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2], babelHelpers.classPrivateFieldLooseBase(this, _intervalSelector)[_intervalSelector].render());
	  }
	  isExactDeadlineTime() {
	    return ['minutes', 'hours'].includes(babelHelpers.classPrivateFieldLooseBase(this, _intervalSelector)[_intervalSelector].getInterval());
	  }
	  getDeadlineInSeconds() {
	    return Number(babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].value) * babelHelpers.classPrivateFieldLooseBase(this, _intervalSelector)[_intervalSelector].getDuration();
	  }
	  focus() {
	    babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].focus();
	  }
	  clear() {
	    babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].value = '';
	  }
	  select(deadline) {
	    babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].value = deadline;
	  }
	}
	function _onInput2() {
	  let normalizedValue = removeNonNumeric(babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].value);
	  normalizedValue = parseInt(normalizedValue, 10);
	  const minValue = 1;
	  const maxValue = Math.floor(MAX_INT / SECONDS_IN_MONTH);
	  normalizedValue = clamp(normalizedValue, minValue, maxValue);
	  babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].value = normalizedValue || '';
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$2,
	  _t3,
	  _t4,
	  _t5;
	var _EVENT_NAMESPACE$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("EVENT_NAMESPACE");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _targetForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetForm");
	var _targetInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetInput");
	var _targetButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetButton");
	var _matchWorkTime$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("matchWorkTime");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _getTitleContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitleContainer");
	var _getErrorsContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getErrorsContainer");
	var _getDeadlineContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDeadlineContainer");
	var _getDefaultDeadlineCheckerTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDefaultDeadlineCheckerTitle");
	var _saveTargetButtonStateInForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveTargetButtonStateInForm");
	var _onSubmitButtonClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubmitButtonClick");
	var _onCancelButtonClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCancelButtonClick");
	var _onSkipMenuItemSelect$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemSelect");
	var _onDefaultDeadlineCheckerChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDefaultDeadlineCheckerChange");
	var _onDeadlineSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDeadlineSelect");
	var _onPopupFirstShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPopupFirstShow");
	var _onPromiseError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPromiseError");
	var _setDefaultDeadline = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setDefaultDeadline");
	var _setSkipNotificationPeriod = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSkipNotificationPeriod");
	var _submit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("submit");
	var _setTargetInputValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTargetInputValue");
	var _init$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	class Notification extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _init$4, {
	      value: _init2$4
	    });
	    Object.defineProperty(this, _setTargetInputValue, {
	      value: _setTargetInputValue2
	    });
	    Object.defineProperty(this, _submit, {
	      value: _submit2
	    });
	    Object.defineProperty(this, _setSkipNotificationPeriod, {
	      value: _setSkipNotificationPeriod2
	    });
	    Object.defineProperty(this, _setDefaultDeadline, {
	      value: _setDefaultDeadline2
	    });
	    Object.defineProperty(this, _onPromiseError, {
	      value: _onPromiseError2
	    });
	    Object.defineProperty(this, _onPopupFirstShow, {
	      value: _onPopupFirstShow2
	    });
	    Object.defineProperty(this, _onDeadlineSelect, {
	      value: _onDeadlineSelect2
	    });
	    Object.defineProperty(this, _onDefaultDeadlineCheckerChange, {
	      value: _onDefaultDeadlineCheckerChange2
	    });
	    Object.defineProperty(this, _onSkipMenuItemSelect$1, {
	      value: _onSkipMenuItemSelect2$1
	    });
	    Object.defineProperty(this, _onCancelButtonClick$1, {
	      value: _onCancelButtonClick2$1
	    });
	    Object.defineProperty(this, _onSubmitButtonClick$1, {
	      value: _onSubmitButtonClick2$1
	    });
	    Object.defineProperty(this, _saveTargetButtonStateInForm, {
	      value: _saveTargetButtonStateInForm2
	    });
	    Object.defineProperty(this, _getDefaultDeadlineCheckerTitle, {
	      value: _getDefaultDeadlineCheckerTitle2
	    });
	    Object.defineProperty(this, _getDeadlineContainer, {
	      value: _getDeadlineContainer2
	    });
	    Object.defineProperty(this, _getErrorsContainer, {
	      value: _getErrorsContainer2
	    });
	    Object.defineProperty(this, _getTitleContainer, {
	      value: _getTitleContainer2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _EVENT_NAMESPACE$4, {
	      writable: true,
	      value: 'BX.Tasks.Deadline.Notification'
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _targetForm, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _targetInput, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _targetButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _matchWorkTime$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(babelHelpers.classPrivateFieldLooseBase(this, _EVENT_NAMESPACE$4)[_EVENT_NAMESPACE$4]);
	    babelHelpers.classPrivateFieldLooseBase(this, _init$4)[_init$4](_options);
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	    ui_analytics.sendData({
	      tool: 'tasks',
	      category: 'task_operations',
	      event: 'deadline_popup_show',
	      type: 'popup',
	      c_section: 'tasks'
	    });
	  }
	  close() {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	  }
	}
	function _getPopupContent2() {
	  const form = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<form class="tasks-deadline__notification-form">
				${0}
				${0}
				${0}
				${0}
			</form>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getTitleContainer)[_getTitleContainer](), babelHelpers.classPrivateFieldLooseBase(this, _getErrorsContainer)[_getErrorsContainer](), babelHelpers.classPrivateFieldLooseBase(this, _getDeadlineContainer)[_getDeadlineContainer](), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.render());
	  main_core.Event.bind(form, 'submit', e => {
	    e.preventDefault();
	  });
	  return form;
	}
	function _getTitleContainer2() {
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
			<div class="tasks-deadline-notification-form__title">
				<div class="tasks-deadline-notification-form__title_icon"></div>
				<div class="tasks-deadline-notification-form__title_text">
					${0}
				</div>
			</div>
			<div class="tasks-deadline-notification-form__subtitle">
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_TITLE'), main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DESCRIPTION'));
	}
	function _getErrorsContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].errorContainer = main_core.Tag.render(_t3 || (_t3 = _$3`
			<div class="tasks-deadline-notification-form__errors" hidden>
				<div class="ui-alert ui-alert-danger">
					<span class="ui-alert-message">${0}</span>
				</div>
			</div>
		`), main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_ERROR_MESSAGE'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].errorContainer;
	}
	function _getDeadlineContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineChecker = new ui_formElements_view.Checker({
	    id: 'tasks-deadline-notification-default-deadline-checker',
	    inputName: 'defaultDeadlineChecker',
	    checked: false,
	    title: babelHelpers.classPrivateFieldLooseBase(this, _getDefaultDeadlineCheckerTitle)[_getDefaultDeadlineCheckerTitle](),
	    hideSeparator: true,
	    size: 'extra-small'
	  });
	  const defaultDeadlineContainer = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.render();
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineChecker, 'change', babelHelpers.classPrivateFieldLooseBase(this, _onDefaultDeadlineCheckerChange)[_onDefaultDeadlineCheckerChange].bind(this, defaultDeadlineContainer));
	  return main_core.Tag.render(_t4 || (_t4 = _$3`
			<div class="tasks-deadline-notification-form__content">
				<div class="tasks-deadline-notification-form__content_title">
					${0}
				</div>
				${0}
				<div class="tasks-deadline-notification-form__content_default-deadline-checker">
					${0}
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEADLINE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineChecker.render(), defaultDeadlineContainer);
	}
	function _getDefaultDeadlineCheckerTitle2() {
	  return `
			<div>
				${main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEFAULT_DEADLINE_CHECKER')}
			</div>
			<span
				data-id="defaultDeadlineHint"
				class="ui-hint"
				data-hint="${main_core.Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEFAULT_DEADLINE_HINT')}"
				data-hint-no-icon
			>
				<span class="ui-hint-icon">
				</span>
			</span>
		`;
	}
	function _saveTargetButtonStateInForm2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton] || !babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton].name || !babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton].value) {
	    return;
	  }
	  const targetButtonInput = main_core.Tag.render(_t5 || (_t5 = _$3`
			<input name="${0}" value="${0}" hidden>
		`), babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton].name, babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton].value);
	  main_core.Dom.append(targetButtonInput, babelHelpers.classPrivateFieldLooseBase(this, _targetForm)[_targetForm]);
	}
	function _onSubmitButtonClick2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.disableButtons();
	  const defaultDeadline = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.getDeadlineInSeconds();
	  const isExactDeadlineTime = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.isExactDeadlineTime();
	  const skipPeriod = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.getSkipPeriod();
	  babelHelpers.classPrivateFieldLooseBase(this, _setDefaultDeadline)[_setDefaultDeadline](defaultDeadline, isExactDeadlineTime).then(() => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setSkipNotificationPeriod)[_setSkipNotificationPeriod](skipPeriod);
	  }).then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _setTargetInputValue)[_setTargetInputValue]();
	    babelHelpers.classPrivateFieldLooseBase(this, _submit)[_submit]();
	  }).catch(() => babelHelpers.classPrivateFieldLooseBase(this, _onPromiseError)[_onPromiseError]());
	}
	function _onCancelButtonClick2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.disableButtons();
	  babelHelpers.classPrivateFieldLooseBase(this, _submit)[_submit]();
	}
	function _onSkipMenuItemSelect2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getInputValue()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.disableButtons();
	  const skipPeriod = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.getSkipPeriod();
	  babelHelpers.classPrivateFieldLooseBase(this, _setSkipNotificationPeriod)[_setSkipNotificationPeriod](skipPeriod).then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _submit)[_submit]();
	  }).catch(() => babelHelpers.classPrivateFieldLooseBase(this, _onPromiseError)[_onPromiseError]());
	}
	function _onDefaultDeadlineCheckerChange2(defaultDeadlineContainer, event) {
	  const checked = event.getData();
	  if (!checked) {
	    main_core.Dom.hide(defaultDeadlineContainer);
	    return;
	  }
	  main_core.Dom.show(defaultDeadlineContainer);
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getInputValue()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.clear();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.focus();
	    return;
	  }
	  const now = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getToday();
	  const selectedDeadline = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getDeadline();
	  const offsetInSeconds = (selectedDeadline - now) / 1000;
	  const defaultDeadline = tasks_dateRounder.DateRounder.roundToDays(offsetInSeconds);
	  if (defaultDeadline <= 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.clear();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.focus();
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector.select(defaultDeadline);
	}
	function _onDeadlineSelect2() {
	  const selectedDate = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getDeadline();
	  if (selectedDate === null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.disableSubmitButton();
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.enableSubmitButton();
	}
	function _onPopupFirstShow2(event) {
	  BX.UI.Hint.init(event.getTarget().contentContainer);
	}
	function _onPromiseError2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.enableCancelButton();
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.enableSkipButton();
	  const selectedDate = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getDeadline();
	  if (!main_core.Type.isNull(selectedDate)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.enableSubmitButton();
	  }
	  main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].errorContainer);
	}
	function _setDefaultDeadline2(deadline, isExactDeadlineTime) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineChecker.isChecked()) {
	    return new Promise(resolve => {
	      resolve();
	    });
	  }
	  const deadlineData = {
	    default: deadline,
	    isExactTime: isExactDeadlineTime ? 'Y' : 'N'
	  };
	  return main_core.ajax.runAction('tasks.deadline.Deadline.setDefault', {
	    data: {
	      deadlineData
	    }
	  });
	}
	function _setSkipNotificationPeriod2(skipPeriod) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer.getSkipPeriod()) {
	    return new Promise(resolve => {
	      resolve();
	    });
	  }
	  const notificationData = {
	    skipPeriod
	  };
	  return main_core.ajax.runAction('tasks.deadline.Notification.skip', {
	    data: {
	      notificationData
	    }
	  });
	}
	function _submit2() {
	  this.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _saveTargetButtonStateInForm)[_saveTargetButtonStateInForm]();
	  this.emit('onBeforeSubmit');
	  babelHelpers.classPrivateFieldLooseBase(this, _targetForm)[_targetForm].submit();
	}
	function _setTargetInputValue2() {
	  const dateFormat = main_date.DateTimeFormat.getFormat('FORMAT_DATETIME');
	  babelHelpers.classPrivateFieldLooseBase(this, _targetInput)[_targetInput].value = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector.getFormattedDeadline(dateFormat);
	}
	function _init2$4(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _targetForm)[_targetForm] = options.targetForm;
	  babelHelpers.classPrivateFieldLooseBase(this, _targetInput)[_targetInput] = options.targetInput;
	  babelHelpers.classPrivateFieldLooseBase(this, _targetButton)[_targetButton] = options.targetButton;
	  babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime$2)[_matchWorkTime$2] = options.matchWorkTime;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].deadlineSelector = new DeadlineSelector({
	    matchWorkTime: babelHelpers.classPrivateFieldLooseBase(this, _matchWorkTime$2)[_matchWorkTime$2],
	    events: {
	      onSelect: babelHelpers.classPrivateFieldLooseBase(this, _onDeadlineSelect)[_onDeadlineSelect].bind(this)
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].defaultDeadlineSelector = new DefaultDeadlineSelector();
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].footer = new Footer({
	    events: {
	      onSubmitButtonClick: babelHelpers.classPrivateFieldLooseBase(this, _onSubmitButtonClick$1)[_onSubmitButtonClick$1].bind(this),
	      onCancelButtonClick: babelHelpers.classPrivateFieldLooseBase(this, _onCancelButtonClick$1)[_onCancelButtonClick$1].bind(this),
	      onSkipMenuItemSelect: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect$1)[_onSkipMenuItemSelect$1].bind(this)
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    id: 'deadline-notification',
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	    className: 'tasks__deadline-notification-popup',
	    overlay: {
	      opacity: 40
	    },
	    width: 600,
	    autoHide: false,
	    closeIcon: false,
	    events: {
	      onFirstShow: babelHelpers.classPrivateFieldLooseBase(this, _onPopupFirstShow)[_onPopupFirstShow].bind(this)
	    }
	  });
	}

	exports.Notification = Notification;

}((this.BX.Tasks.Deadline = this.BX.Tasks.Deadline || {}),BX.UI.FormElements,BX.Tasks,BX.Main,BX.UI.DatePicker,BX.Event,BX.Main,BX.UI,BX,BX.Tasks,BX.UI.Analytics));
//# sourceMappingURL=notification.bundle.js.map

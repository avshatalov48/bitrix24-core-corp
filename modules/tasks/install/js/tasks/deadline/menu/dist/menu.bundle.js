/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_dialogs_messagebox,main_core,main_core_events,main_popup,pull_client) {
	'use strict';

	let _ = t => t,
	  _t;
	var _item = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("item");
	var _hintManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hintManager");
	var _getHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHtml");
	var _onSubMenuShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubMenuShow");
	var _bringHintToFront = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bringHintToFront");
	class Item {
	  constructor() {
	    Object.defineProperty(this, _bringHintToFront, {
	      value: _bringHintToFront2
	    });
	    Object.defineProperty(this, _onSubMenuShow, {
	      value: _onSubMenuShow2
	    });
	    Object.defineProperty(this, _getHtml, {
	      value: _getHtml2
	    });
	    this.ACCEPTED_ITEM_CLASS = 'menu-popup-item-accept';
	    this.DISABLED_ITEM_CLASS = 'menu-popup-item-disabled';
	    Object.defineProperty(this, _item, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _hintManager, {
	      writable: true,
	      value: void 0
	    });
	  }
	  getItem() {
	    if (!main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item])) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _item)[_item];
	    }
	    if (main_core.Type.isUndefined(this.MENU_ID) || main_core.Type.isUndefined(this.HINT_ID) || main_core.Type.isUndefined(this.TITLE)) {
	      return null;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _item)[_item] = {
	      dataset: {
	        id: this.MENU_ID
	      },
	      id: this.MENU_ID,
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getHtml)[_getHtml](),
	      className: `menu-popup-item-none menu-popup-${this.TITLE}-deadline`,
	      events: {
	        onSubMenuShow: babelHelpers.classPrivateFieldLooseBase(this, _onSubMenuShow)[_onSubMenuShow].bind(this)
	      },
	      items: this.getSubItems()
	    };
	    return babelHelpers.classPrivateFieldLooseBase(this, _item)[_item];
	  }
	  onPromiseError(error) {
	    const errors = error.errors;
	    if (main_core.Type.isUndefined(errors)) {
	      console.error(error);
	      return;
	    }
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('TASKS_DEADLINE_MENU_ERROR_DESCRIPTION'), main_core.Loc.getMessage('TASKS_DEADLINE_MENU_ERROR_TITLE'));
	  }
	  getSubItems() {
	    return [];
	  }
	  refreshIcons(subItems, subMenu) {}
	}
	function _getHtml2() {
	  const node = main_core.Tag.render(_t || (_t = _`
			<div class="tasks-deadline-menu_${0}-deadline-item__text">
				${0}
			</div>
		`), this.TITLE, main_core.Loc.getMessage(`TASKS_DEADLINE_MENU_${this.TITLE.toUpperCase()}_ITEM_TITLE`));
	  babelHelpers.classPrivateFieldLooseBase(this, _hintManager)[_hintManager] = BX.UI.Hint.createInstance({
	    id: this.HINT_ID
	  });
	  const hint = babelHelpers.classPrivateFieldLooseBase(this, _hintManager)[_hintManager].createNode(main_core.Loc.getMessage(`TASKS_DEADLINE_MENU_${this.TITLE.toUpperCase()}_ITEM_HINT`));
	  main_core.Dom.append(hint, node);
	  return node;
	}
	function _onSubMenuShow2(event) {
	  const item = event.getTarget();
	  const subMenu = item.getSubMenu();
	  const subItems = subMenu.getMenuItems();
	  this.refreshIcons(subItems, subMenu);
	  babelHelpers.classPrivateFieldLooseBase(this, _bringHintToFront)[_bringHintToFront](subMenu);
	}
	function _bringHintToFront2(subMenu) {
	  const subMenuComponent = subMenu.getPopupWindow().getZIndexComponent();
	  const hintPopup = babelHelpers.classPrivateFieldLooseBase(this, _hintManager)[_hintManager].popup;
	  if (!hintPopup) {
	    return;
	  }
	  const hintComponent = hintPopup.getZIndexComponent();
	  hintComponent.setSort(subMenuComponent.getSort() + 1);
	  const stack = hintComponent.getStack();
	  stack.sort();
	}

	var _PULL_EVENT = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("PULL_EVENT");
	var _SECONDS_IN_DAY = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("SECONDS_IN_DAY");
	var _SECONDS_IN_WEEK = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("SECONDS_IN_WEEK");
	var _SUBITEMS_CLASS_NAME = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("SUBITEMS_CLASS_NAME");
	var _subMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subMenu");
	var _subItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subItems");
	var _defaultDeadline = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultDeadline");
	var _isDeadlineNotificationAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDeadlineNotificationAvailable");
	var _onMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMenuItemClick");
	var _subscribeToPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPull");
	var _onDefaultDeadlineChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDefaultDeadlineChanged");
	class DefaultItem extends Item {
	  constructor(isDeadlineNotificationAvailable = true) {
	    super();
	    Object.defineProperty(this, _onDefaultDeadlineChanged, {
	      value: _onDefaultDeadlineChanged2
	    });
	    Object.defineProperty(this, _subscribeToPull, {
	      value: _subscribeToPull2
	    });
	    Object.defineProperty(this, _onMenuItemClick, {
	      value: _onMenuItemClick2
	    });
	    this.MENU_ID = 'default-deadline-menu';
	    this.HINT_ID = 'default-deadline-hint';
	    this.TITLE = 'default';
	    Object.defineProperty(this, _PULL_EVENT, {
	      writable: true,
	      value: 'default_deadline_changed'
	    });
	    Object.defineProperty(this, _SECONDS_IN_DAY, {
	      writable: true,
	      value: 86400
	    });
	    Object.defineProperty(this, _SECONDS_IN_WEEK, {
	      writable: true,
	      value: 7 * babelHelpers.classPrivateFieldLooseBase(this, _SECONDS_IN_DAY)[_SECONDS_IN_DAY]
	    });
	    Object.defineProperty(this, _SUBITEMS_CLASS_NAME, {
	      writable: true,
	      value: 'menu-popup-default-deadline-item'
	    });
	    Object.defineProperty(this, _subMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _subItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _defaultDeadline, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isDeadlineNotificationAvailable, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable)[_isDeadlineNotificationAvailable] = isDeadlineNotificationAvailable;
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPull)[_subscribeToPull]();
	  }
	  getSubItems() {
	    const onclick = babelHelpers.classPrivateFieldLooseBase(this, _onMenuItemClick)[_onMenuItemClick].bind(this);
	    const subItems = [new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-day`,
	        analytics: 'one_day_button'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_1_DAY'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _SECONDS_IN_DAY)[_SECONDS_IN_DAY],
	      isExactTime: false,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-three-days`,
	        analytics: 'three_days_button'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_3_DAYS'),
	      value: 3 * babelHelpers.classPrivateFieldLooseBase(this, _SECONDS_IN_DAY)[_SECONDS_IN_DAY],
	      isExactTime: false,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-week`,
	        analytics: 'one_week_button'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_1_WEEK'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _SECONDS_IN_WEEK)[_SECONDS_IN_WEEK],
	      isExactTime: false,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-two-weeks`,
	        analytics: 'two_weeks_button'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_2_WEEKS'),
	      value: 2 * babelHelpers.classPrivateFieldLooseBase(this, _SECONDS_IN_WEEK)[_SECONDS_IN_WEEK],
	      isExactTime: false,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-empty`,
	        analytics: 'no_deadline'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_EMPTY'),
	      value: 0,
	      isExactTime: false,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME],
	      onclick
	    })];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable)[_isDeadlineNotificationAvailable]) {
	      subItems.push(new main_popup.MenuItem({
	        dataset: {
	          id: `${this.MENU_ID}-custom`
	        },
	        text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_CUSTOM'),
	        className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME]
	      }));
	    }
	    return subItems;
	  }
	  refreshIcons(subItems, subMenu) {
	    const subItemsFilled = main_core.Type.isArrayFilled(subItems) || !main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _subItems)[_subItems]);
	    const subMenuFilled = !main_core.Type.isUndefined(subMenu) || !main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _subMenu)[_subMenu]);
	    if (!subItemsFilled || !subMenuFilled) {
	      return;
	    }
	    if (main_core.Type.isArrayFilled(subItems)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _subItems)[_subItems] = subItems;
	    }
	    if (!main_core.Type.isUndefined(subMenu)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _subMenu)[_subMenu] = subMenu;
	    }
	    let isDefaultDeadlineFound = false;
	    let customItem = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _subItems)[_subItems].forEach(item => {
	      const node = item.getLayout().item;
	      if (!main_core.Type.isElementNode(node)) {
	        return;
	      }
	      const currentDeadline = item.value;
	      if (main_core.Type.isUndefined(currentDeadline)) {
	        customItem = item;
	      } else if (currentDeadline === this.defaultDeadline) {
	        main_core.Dom.addClass(node, this.ACCEPTED_ITEM_CLASS);
	        isDefaultDeadlineFound = true;
	        return;
	      }
	      main_core.Dom.removeClass(node, this.ACCEPTED_ITEM_CLASS);
	    });
	    if (!main_core.Type.isNull(customItem)) {
	      const customItemClass = isDefaultDeadlineFound ? this.DISABLED_ITEM_CLASS : this.ACCEPTED_ITEM_CLASS;
	      main_core.Dom.addClass(customItem.getLayout().item, customItemClass);
	      return;
	    }
	    if (isDefaultDeadlineFound) {
	      return;
	    }
	    customItem = new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-custom`
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_CUSTOM'),
	      className: `${babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME)[_SUBITEMS_CLASS_NAME]} ${this.ACCEPTED_ITEM_CLASS}`
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subMenu)[_subMenu].addMenuItem(customItem);
	  }
	  get defaultDeadline() {
	    if (!main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline])) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline];
	    }
	    const settings = main_core.Extension.getSettings('tasks.deadline.menu');
	    babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline] = Number(settings.get('defaultDeadline'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline];
	  }
	}
	function _onMenuItemClick2(event, item) {
	  var _item$dataset;
	  const deadline = item.value;
	  if (!main_core.Type.isNumber(deadline)) {
	    return;
	  }
	  if (deadline === babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline] = deadline;
	  const subMenu = item.getMenuWindow();
	  const subItems = subMenu.getMenuItems();
	  this.refreshIcons(subItems, subMenu);
	  main_core.ajax.runAction('tasks.deadline.Deadline.setDefault', {
	    data: {
	      deadlineData: {
	        default: deadline,
	        isExactTime: item.isExactTime ? 'Y' : 'N'
	      }
	    },
	    analytics: {
	      tool: 'tasks',
	      category: 'task_operations',
	      event: 'click_settings',
	      type: 'task',
	      c_section: 'tasks',
	      c_sub_section: 'default_deadline_selected',
	      c_element: (_item$dataset = item.dataset) == null ? void 0 : _item$dataset.analytics
	    }
	  }).catch(error => this.onPromiseError(error));
	}
	function _subscribeToPull2() {
	  pull_client.PULL.subscribe({
	    moduleId: 'tasks',
	    command: babelHelpers.classPrivateFieldLooseBase(this, _PULL_EVENT)[_PULL_EVENT],
	    callback: babelHelpers.classPrivateFieldLooseBase(this, _onDefaultDeadlineChanged)[_onDefaultDeadlineChanged].bind(this)
	  });
	}
	function _onDefaultDeadlineChanged2(params) {
	  const deadline = params.deadline;
	  if (!main_core.Type.isNumber(deadline) || babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline] === deadline) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _defaultDeadline)[_defaultDeadline] = deadline;
	}

	var _PULL_EVENT$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("PULL_EVENT");
	var _SUBITEMS_CLASS_NAME$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("SUBITEMS_CLASS_NAME");
	var _subItems$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subItems");
	var _skipPeriod = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipPeriod");
	var _onMenuItemClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMenuItemClick");
	var _subscribeToPull$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPull");
	var _onNotificationPeriodChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onNotificationPeriodChanged");
	class NotificationItem extends Item {
	  constructor() {
	    super();
	    Object.defineProperty(this, _onNotificationPeriodChanged, {
	      value: _onNotificationPeriodChanged2
	    });
	    Object.defineProperty(this, _subscribeToPull$1, {
	      value: _subscribeToPull2$1
	    });
	    Object.defineProperty(this, _onMenuItemClick$1, {
	      value: _onMenuItemClick2$1
	    });
	    this.MENU_ID = 'skip-deadline-notification-menu';
	    this.HINT_ID = 'skip-deadline-notification-hint';
	    this.TITLE = 'notification';
	    Object.defineProperty(this, _PULL_EVENT$1, {
	      writable: true,
	      value: 'skip_deadline_notification_period_changed'
	    });
	    Object.defineProperty(this, _SUBITEMS_CLASS_NAME$1, {
	      writable: true,
	      value: 'menu-popup-skip-deadline-item'
	    });
	    Object.defineProperty(this, _subItems$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _skipPeriod, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPull$1)[_subscribeToPull$1]();
	  }
	  getSubItems() {
	    const onclick = babelHelpers.classPrivateFieldLooseBase(this, _onMenuItemClick$1)[_onMenuItemClick$1].bind(this);
	    return [new main_popup.MenuItem({
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_DELIMITER'),
	      delimiter: true,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1]
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-always`,
	        analytics: 'daily_remind'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_DONT_SKIP'),
	      value: '',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-day`,
	        analytics: 'no_remind_today'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_DAY'),
	      value: 'day',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-week`,
	        analytics: 'no_remind_current_week'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_WEEK'),
	      value: 'week',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-month`,
	        analytics: 'no_remind_current_month'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_MONTH'),
	      value: 'month',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1],
	      onclick
	    }), new main_popup.MenuItem({
	      dataset: {
	        id: `${this.MENU_ID}-forever`,
	        analytics: 'never_remind'
	      },
	      text: main_core.Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOREVER'),
	      value: 'forever',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _SUBITEMS_CLASS_NAME$1)[_SUBITEMS_CLASS_NAME$1],
	      onclick
	    })];
	  }
	  refreshIcons(subItems, subMenu) {
	    const subItemsFilled = main_core.Type.isArrayFilled(subItems) || !main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _subItems$1)[_subItems$1]);
	    if (!subItemsFilled) {
	      return;
	    }
	    if (main_core.Type.isArrayFilled(subItems)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _subItems$1)[_subItems$1] = subItems;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _subItems$1)[_subItems$1].forEach(item => {
	      const node = item.getLayout().item;
	      if (!main_core.Type.isElementNode(node)) {
	        return;
	      }
	      const currentSkipPeriod = item.value;
	      if (currentSkipPeriod === this.skipPeriod) {
	        main_core.Dom.addClass(node, this.ACCEPTED_ITEM_CLASS);
	        return;
	      }
	      main_core.Dom.removeClass(node, this.ACCEPTED_ITEM_CLASS);
	    });
	  }
	  get skipPeriod() {
	    if (!main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod])) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod];
	    }
	    const settings = main_core.Extension.getSettings('tasks.deadline.menu');
	    babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod] = String(settings.get('skipDeadlineNotificationPeriod'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod];
	  }
	}
	function _onMenuItemClick2$1(event, item) {
	  var _item$dataset;
	  const skipPeriod = item.value;
	  if (!main_core.Type.isString(skipPeriod)) {
	    return;
	  }
	  if (skipPeriod === babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod] = skipPeriod;
	  const subMenu = item.getMenuWindow();
	  const subItems = subMenu.getMenuItems();
	  this.refreshIcons(subItems, subMenu);
	  main_core.ajax.runAction('tasks.deadline.Notification.skip', {
	    data: {
	      notificationData: {
	        skipPeriod
	      }
	    },
	    analytics: {
	      tool: 'tasks',
	      category: 'task_operations',
	      event: 'click_settings',
	      type: 'task',
	      c_section: 'tasks',
	      c_sub_section: 'deadline_management',
	      c_element: (_item$dataset = item.dataset) == null ? void 0 : _item$dataset.analytics
	    }
	  }).catch(error => this.onPromiseError(error));
	}
	function _subscribeToPull2$1() {
	  pull_client.PULL.subscribe({
	    moduleId: 'tasks',
	    command: babelHelpers.classPrivateFieldLooseBase(this, _PULL_EVENT$1)[_PULL_EVENT$1],
	    callback: babelHelpers.classPrivateFieldLooseBase(this, _onNotificationPeriodChanged)[_onNotificationPeriodChanged].bind(this)
	  });
	}
	function _onNotificationPeriodChanged2(params) {
	  const period = params.period;
	  if (!main_core.Type.isString(period) || babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod] === period) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _skipPeriod)[_skipPeriod] = period;
	}

	var _isDeadlineNotificationAvailable$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDeadlineNotificationAvailable");
	class Menu {
	  constructor() {
	    Object.defineProperty(this, _isDeadlineNotificationAvailable$1, {
	      writable: true,
	      value: void 0
	    });
	  }
	  get menuItems() {
	    const defaultMenuItem = new DefaultItem(this.isDeadlineNotificationAvailable);
	    const menuItems = [defaultMenuItem.getItem()];
	    if (this.isDeadlineNotificationAvailable) {
	      const notificationMenuItem = new NotificationItem();
	      menuItems.push(notificationMenuItem.getItem());
	    }
	    return menuItems;
	  }
	  get isDeadlineNotificationAvailable() {
	    if (!main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable$1)[_isDeadlineNotificationAvailable$1])) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable$1)[_isDeadlineNotificationAvailable$1];
	    }
	    const settings = main_core.Extension.getSettings('tasks.deadline.menu');
	    babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable$1)[_isDeadlineNotificationAvailable$1] = Boolean(settings.get('isDeadlineNotificationAvailable'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _isDeadlineNotificationAvailable$1)[_isDeadlineNotificationAvailable$1];
	  }
	}

	exports.Menu = Menu;

}((this.BX.Tasks.Deadline = this.BX.Tasks.Deadline || {}),BX.UI.Dialogs,BX,BX.Event,BX.Main,BX));
//# sourceMappingURL=menu.bundle.js.map

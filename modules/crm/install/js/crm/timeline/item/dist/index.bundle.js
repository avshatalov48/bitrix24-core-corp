/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,rest_client,ui_analytics,crm_field_colorSelector,ui_vue3_directives_hint,ui_label,ui_cnt,location_core,main_loader,crm_timeline_editors_commentEditor,ui_textEditor,ui_bbcode_formatter_htmlFormatter,ui_vue3,ui_icons_generator,crm_audioPlayer,ui_iconSet_api_vue,ui_iconSet_actions,crm_field_itemSelector,currency_currencyCore,ui_textcrop,ui_alerts,ui_avatar,crm_field_pingSelector,bizproc_types,im_public,main_date,crm_timeline_tools,ui_infoHelper,ai_engine,ui_buttons,ui_feedback_form,crm_activity_fileUploaderPopup,ui_entitySelector,ui_sidepanel,crm_entityEditor_field_paymentDocuments,pull_client,calendar_util,main_popup,calendar_sharing_interface,crm_ai_call,crm_router,ui_hint,ui_dialogs_messagebox,main_core_events,ui_notification,main_core,ui_imageStackSteps,ui_iconSet_main,ui_designTokens,crm_timeline_item) {
	'use strict';

	var crm_timeline_item__default = 'default' in crm_timeline_item ? crm_timeline_item['default'] : crm_timeline_item;

	const StreamType = {
	  history: 0,
	  scheduled: 1,
	  pinned: 2
	};

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _menuOptions = /*#__PURE__*/new WeakMap();
	var _vueComponent = /*#__PURE__*/new WeakMap();
	let Menu = /*#__PURE__*/function () {
	  function Menu(vueComponent, menuItems, menuOptions) {
	    babelHelpers.classCallCheck(this, Menu);
	    _classPrivateFieldInitSpec(this, _menuOptions, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _vueComponent, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldSet(this, _vueComponent, vueComponent);
	    babelHelpers.classPrivateFieldSet(this, _menuOptions, menuOptions || {});
	    babelHelpers.classPrivateFieldSet(this, _menuOptions, {
	      angle: false,
	      cacheable: false,
	      ...babelHelpers.classPrivateFieldGet(this, _menuOptions)
	    });
	    babelHelpers.classPrivateFieldGet(this, _menuOptions).items = [];
	    for (const item of menuItems) {
	      babelHelpers.classPrivateFieldGet(this, _menuOptions).items.push(this.createMenuItem(item));
	    }
	  }
	  babelHelpers.createClass(Menu, [{
	    key: "getMenuItems",
	    value: function getMenuItems() {
	      return babelHelpers.classPrivateFieldGet(this, _menuOptions).items;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      main_popup.MenuManager.show(babelHelpers.classPrivateFieldGet(this, _menuOptions));
	    }
	  }, {
	    key: "createMenuItem",
	    value: function createMenuItem(item) {
	      if (Object.prototype.hasOwnProperty.call(item, 'delimiter') && item.delimiter) {
	        return {
	          text: item.title || '',
	          delimiter: true
	        };
	      }
	      const result = {
	        text: item.title,
	        value: item.title
	      };
	      if (item.icon) {
	        result.className = `menu-popup-item-${item.icon}`;
	      }
	      if (item.menu) {
	        result.items = [];
	        for (const subItem of Object.values(item.menu.items || {})) {
	          result.items.push(this.createMenuItem(subItem));
	        }
	      } else if (item.action) {
	        if (item.action.type === 'redirect') {
	          result.href = item.action.value;
	        } else if (item.action.type === 'jsCode') {
	          result.onclick = item.action.value;
	        } else {
	          result.onclick = () => {
	            void this.onMenuItemClick(item);
	          };
	        }
	      }
	      return result;
	    }
	  }, {
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(item) {
	      const menu = main_popup.MenuManager.getCurrentMenu();
	      if (menu) {
	        menu.close();
	      }
	      void new Action(item.action).execute(babelHelpers.classPrivateFieldGet(this, _vueComponent));
	    }
	  }], [{
	    key: "showMenu",
	    value: function showMenu(vueComponent, menuItems, menuOptions) {
	      const menu = new Menu(vueComponent, menuItems, menuOptions);
	      menu.show();
	    }
	  }]);
	  return Menu;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const AnimationTarget = {
	  block: 'block',
	  item: 'item'
	};
	const AnimationType = {
	  disable: 'disable',
	  loader: 'loader'
	};
	const ActionType = {
	  JS_EVENT: 'jsEvent',
	  AJAX_ACTION: {
	    STARTED: 'ajaxActionStarted',
	    FINISHED: 'ajaxActionFinished',
	    FAILED: 'ajaxActionFailed'
	  },
	  isJsEvent(type) {
	    return type === this.JS_EVENT;
	  },
	  isAjaxAction(type) {
	    return type === this.AJAX_ACTION.STARTED || type === this.AJAX_ACTION.FINISHED || type === this.AJAX_ACTION.FAILED;
	  }
	};
	Object.freeze(ActionType.AJAX_ACTION);
	Object.freeze(ActionType);
	var _type = /*#__PURE__*/new WeakMap();
	var _value = /*#__PURE__*/new WeakMap();
	var _actionParams = /*#__PURE__*/new WeakMap();
	var _animation = /*#__PURE__*/new WeakMap();
	var _analytics = /*#__PURE__*/new WeakMap();
	var _prepareRunActionParams = /*#__PURE__*/new WeakSet();
	var _prepareCallBatchParams = /*#__PURE__*/new WeakSet();
	var _prepareMenuItems = /*#__PURE__*/new WeakSet();
	var _startAnimation = /*#__PURE__*/new WeakSet();
	var _stopAnimation = /*#__PURE__*/new WeakSet();
	var _isAnimationValid = /*#__PURE__*/new WeakSet();
	var _sendAnalytics = /*#__PURE__*/new WeakSet();
	let Action = /*#__PURE__*/function () {
	  function Action(_params) {
	    babelHelpers.classCallCheck(this, Action);
	    _classPrivateMethodInitSpec(this, _sendAnalytics);
	    _classPrivateMethodInitSpec(this, _isAnimationValid);
	    _classPrivateMethodInitSpec(this, _stopAnimation);
	    _classPrivateMethodInitSpec(this, _startAnimation);
	    _classPrivateMethodInitSpec(this, _prepareMenuItems);
	    _classPrivateMethodInitSpec(this, _prepareCallBatchParams);
	    _classPrivateMethodInitSpec(this, _prepareRunActionParams);
	    _classPrivateFieldInitSpec$1(this, _type, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _value, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _actionParams, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _animation, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _analytics, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _type, _params.type);
	    babelHelpers.classPrivateFieldSet(this, _value, _params.value);
	    babelHelpers.classPrivateFieldSet(this, _actionParams, _params.actionParams);
	    babelHelpers.classPrivateFieldSet(this, _animation, main_core.Type.isPlainObject(_params.animation) ? _params.animation : null);
	    babelHelpers.classPrivateFieldSet(this, _analytics, main_core.Type.isPlainObject(_params.analytics) ? _params.analytics : null);
	  }
	  babelHelpers.createClass(Action, [{
	    key: "execute",
	    value: function execute(vueComponent) {
	      return new Promise((resolve, reject) => {
	        if (this.isJsEvent()) {
	          vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	            action: babelHelpers.classPrivateFieldGet(this, _value),
	            actionType: ActionType.JS_EVENT,
	            actionData: babelHelpers.classPrivateFieldGet(this, _actionParams),
	            animationCallbacks: {
	              onStart: _classPrivateMethodGet(this, _startAnimation, _startAnimation2).bind(this, vueComponent),
	              onStop: _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).bind(this, vueComponent)
	            }
	          });
	          _classPrivateMethodGet(this, _sendAnalytics, _sendAnalytics2).call(this);
	          resolve(true);
	        } else if (this.isJsCode()) {
	          _classPrivateMethodGet(this, _startAnimation, _startAnimation2).call(this, vueComponent);
	          eval(babelHelpers.classPrivateFieldGet(this, _value));
	          _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).call(this, vueComponent);
	          _classPrivateMethodGet(this, _sendAnalytics, _sendAnalytics2).call(this);
	          resolve(true);
	        } else if (this.isAjaxAction()) {
	          _classPrivateMethodGet(this, _startAnimation, _startAnimation2).call(this, vueComponent);
	          vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	            action: babelHelpers.classPrivateFieldGet(this, _value),
	            actionType: ActionType.AJAX_ACTION.STARTED,
	            actionData: babelHelpers.classPrivateFieldGet(this, _actionParams)
	          });
	          const ajaxConfig = {
	            data: _classPrivateMethodGet(this, _prepareRunActionParams, _prepareRunActionParams2).call(this, babelHelpers.classPrivateFieldGet(this, _actionParams))
	          };
	          if (babelHelpers.classPrivateFieldGet(this, _analytics)) {
	            ajaxConfig.analytics = babelHelpers.classPrivateFieldGet(this, _analytics);
	          }
	          main_core.ajax.runAction(babelHelpers.classPrivateFieldGet(this, _value), ajaxConfig).then(response => {
	            _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).call(this, vueComponent);
	            vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	              action: babelHelpers.classPrivateFieldGet(this, _value),
	              actionType: ActionType.AJAX_ACTION.FINISHED,
	              actionData: babelHelpers.classPrivateFieldGet(this, _actionParams),
	              response
	            });
	            resolve(response);
	          }, response => {
	            _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).call(this, vueComponent, true);
	            ui_notification.UI.Notification.Center.notify({
	              content: response.errors[0].message,
	              autoHideDelay: 5000
	            });
	            vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	              action: babelHelpers.classPrivateFieldGet(this, _value),
	              actionType: ActionType.AJAX_ACTION.FAILED,
	              actionParams: babelHelpers.classPrivateFieldGet(this, _actionParams),
	              response
	            });
	            reject(response);
	          });
	        } else if (this.isCallRestBatch()) {
	          _classPrivateMethodGet(this, _startAnimation, _startAnimation2).call(this, vueComponent);
	          vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	            action: babelHelpers.classPrivateFieldGet(this, _value),
	            actionType: 'ajaxActionStarted',
	            actionData: babelHelpers.classPrivateFieldGet(this, _actionParams)
	          });
	          rest_client.rest.callBatch(_classPrivateMethodGet(this, _prepareCallBatchParams, _prepareCallBatchParams2).call(this, babelHelpers.classPrivateFieldGet(this, _actionParams)), restResult => {
	            for (const result in restResult) {
	              const response = restResult[result].answer;
	              if (response.error) {
	                _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).call(this, vueComponent);
	                ui_notification.UI.Notification.Center.notify({
	                  content: response.error.error_description,
	                  autoHideDelay: 5000
	                });
	                vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	                  action: babelHelpers.classPrivateFieldGet(this, _value),
	                  actionType: 'ajaxActionFailed',
	                  actionParams: babelHelpers.classPrivateFieldGet(this, _actionParams)
	                });
	                reject(restResult);
	                return;
	              }
	            }
	            _classPrivateMethodGet(this, _stopAnimation, _stopAnimation2).call(this, vueComponent);
	            vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	              action: babelHelpers.classPrivateFieldGet(this, _value),
	              actionType: 'ajaxActionFinished',
	              actionData: babelHelpers.classPrivateFieldGet(this, _actionParams)
	            });
	            resolve(restResult);
	          }, true);
	        } else if (this.isRedirect()) {
	          _classPrivateMethodGet(this, _startAnimation, _startAnimation2).call(this, vueComponent);
	          const linkAttrs = {
	            href: babelHelpers.classPrivateFieldGet(this, _value)
	          };
	          if (babelHelpers.classPrivateFieldGet(this, _actionParams) && babelHelpers.classPrivateFieldGet(this, _actionParams).target) {
	            linkAttrs.target = babelHelpers.classPrivateFieldGet(this, _actionParams).target;
	          }
	          // this magic allows auto opening internal links in slider if possible:
	          const link = main_core.Dom.create('a', {
	            attrs: linkAttrs,
	            text: '',
	            style: {
	              display: 'none'
	            }
	          });
	          main_core.Dom.append(link, document.body);
	          link.click();
	          setTimeout(() => main_core.Dom.remove(link), 10);
	          _classPrivateMethodGet(this, _sendAnalytics, _sendAnalytics2).call(this);
	          resolve(babelHelpers.classPrivateFieldGet(this, _value));
	        } else if (this.isShowMenu()) {
	          Menu.showMenu(vueComponent, _classPrivateMethodGet(this, _prepareMenuItems, _prepareMenuItems2).call(this, babelHelpers.classPrivateFieldGet(this, _value).items, vueComponent), {
	            id: 'actionMenu',
	            bindElement: vueComponent.$el,
	            minWidth: vueComponent.$el.offsetWidth
	          });
	          _classPrivateMethodGet(this, _sendAnalytics, _sendAnalytics2).call(this);
	          resolve(true);
	        } else if (this.isShowInfoHelper()) {
	          var _BX$UI$InfoHelper;
	          (_BX$UI$InfoHelper = BX.UI.InfoHelper) === null || _BX$UI$InfoHelper === void 0 ? void 0 : _BX$UI$InfoHelper.show(babelHelpers.classPrivateFieldGet(this, _value));
	          _classPrivateMethodGet(this, _sendAnalytics, _sendAnalytics2).call(this);
	          resolve(true);
	        } else {
	          reject(false);
	        }
	      });
	    }
	  }, {
	    key: "isJsEvent",
	    value: function isJsEvent() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'jsEvent';
	    }
	  }, {
	    key: "isJsCode",
	    value: function isJsCode() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'jsCode';
	    }
	  }, {
	    key: "isAjaxAction",
	    value: function isAjaxAction() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'runAjaxAction';
	    }
	  }, {
	    key: "isCallRestBatch",
	    value: function isCallRestBatch() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'callRestBatch';
	    }
	  }, {
	    key: "isRedirect",
	    value: function isRedirect() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'redirect';
	    }
	  }, {
	    key: "isShowInfoHelper",
	    value: function isShowInfoHelper() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'showInfoHelper';
	    }
	  }, {
	    key: "isShowMenu",
	    value: function isShowMenu() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'showMenu';
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _value);
	    }
	  }, {
	    key: "getActionParam",
	    value: function getActionParam(param) {
	      return babelHelpers.classPrivateFieldGet(this, _actionParams) && babelHelpers.classPrivateFieldGet(this, _actionParams).hasOwnProperty(param) ? babelHelpers.classPrivateFieldGet(this, _actionParams)[param] : null;
	    }
	  }]);
	  return Action;
	}();
	function _prepareRunActionParams2(params) {
	  const result = {};
	  if (main_core.Type.isUndefined(params)) {
	    return result;
	  }
	  for (const paramName in params) {
	    const paramValue = params[paramName];
	    if (main_core.Type.isDate(paramValue)) {
	      result[paramName] = main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), paramValue);
	    } else if (main_core.Type.isPlainObject(paramValue)) {
	      result[paramName] = _classPrivateMethodGet(this, _prepareRunActionParams, _prepareRunActionParams2).call(this, paramValue);
	    } else {
	      result[paramName] = paramValue;
	    }
	  }
	  return result;
	}
	function _prepareCallBatchParams2(params) {
	  const result = {};
	  if (main_core.Type.isUndefined(params)) {
	    return result;
	  }
	  for (const paramName in params) {
	    result[paramName] = {
	      method: params[paramName].method,
	      params: _classPrivateMethodGet(this, _prepareRunActionParams, _prepareRunActionParams2).call(this, params[paramName].params)
	    };
	  }
	  return result;
	}
	function _prepareMenuItems2(items, vueComponent) {
	  return Object.values(items).filter(item => item.state !== 'hidden' && item.scope !== 'mobile' && (!vueComponent.isReadOnly || !item.hideIfReadonly)).sort((a, b) => a.sort - b.sort);
	}
	function _startAnimation2(vueComponent) {
	  if (!_classPrivateMethodGet(this, _isAnimationValid, _isAnimationValid2).call(this)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _animation).target === AnimationTarget.item) {
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.disable) {
	      vueComponent.$root.setFaded(true);
	    }
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.loader) {
	      vueComponent.$root.showLoader(true);
	    }
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _animation).target === AnimationTarget.block) {
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.disable) {
	      if (main_core.Type.isFunction(vueComponent.setDisabled)) {
	        vueComponent.setDisabled(true);
	      }
	    }
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.loader) {
	      if (main_core.Type.isFunction(vueComponent.setLoading)) {
	        vueComponent.setLoading(true);
	      }
	    }
	  }
	}
	function _stopAnimation2(vueComponent, force = false) {
	  if (!_classPrivateMethodGet(this, _isAnimationValid, _isAnimationValid2).call(this)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _animation).forever && !force) {
	    return; // should not be stopped
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _animation).target === AnimationTarget.item) {
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.disable) {
	      vueComponent.$root.setFaded(false);
	    }
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.loader) {
	      vueComponent.$root.showLoader(false);
	    }
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _animation).target === AnimationTarget.block) {
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.disable) {
	      if (main_core.Type.isFunction(vueComponent.setDisabled)) {
	        vueComponent.setDisabled(false);
	      }
	    }
	    if (babelHelpers.classPrivateFieldGet(this, _animation).type === AnimationType.loader) {
	      if (main_core.Type.isFunction(vueComponent.setLoading)) {
	        vueComponent.setLoading(false);
	      }
	    }
	  }
	}
	function _isAnimationValid2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _animation)) {
	    return false;
	  }
	  if (!AnimationTarget.hasOwnProperty(babelHelpers.classPrivateFieldGet(this, _animation).target)) {
	    return false;
	  }
	  return AnimationType.hasOwnProperty(babelHelpers.classPrivateFieldGet(this, _animation).type);
	}
	function _sendAnalytics2() {
	  if (babelHelpers.classPrivateFieldGet(this, _analytics) && babelHelpers.classPrivateFieldGet(this, _analytics).hit) {
	    const clonedAnalytics = {
	      ...babelHelpers.classPrivateFieldGet(this, _analytics)
	    };
	    delete clonedAnalytics.hit;
	    ui_analytics.sendData(clonedAnalytics);
	  }
	}

	const Logo = {
	  props: {
	    type: String,
	    addIcon: String,
	    addIconType: String,
	    icon: String,
	    iconType: String,
	    backgroundUrl: String,
	    backgroundSize: Number,
	    inCircle: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    action: Object
	  },
	  data() {
	    return {
	      currentIcon: this.icon
	    };
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__card-logo', `--${this.type}`, {
	        '--clickable': this.action
	      }];
	    },
	    iconClassname() {
	      return ['crm-timeline__card-logo_icon', `--${this.currentIcon}`, {
	        '--in-circle': this.inCircle,
	        [`--type-${this.iconType}`]: !!this.iconType && !this.backgroundUrl,
	        '--custom-bg': !!this.backgroundUrl
	      }];
	    },
	    addIconClassname() {
	      return ['crm-timeline__card-logo_add-icon', `--type-${this.addIconType}`, `--icon-${this.addIcon}`];
	    },
	    iconInteriorStyle() {
	      const result = {};
	      if (this.backgroundUrl) {
	        result.backgroundImage = 'url(' + encodeURI(main_core.Text.encode(this.backgroundUrl)) + ')';
	      }
	      if (this.backgroundSize) {
	        result.backgroundSize = parseInt(this.backgroundSize) + 'px';
	      }
	      return result;
	    }
	  },
	  watch: {
	    icon(newIcon) {
	      this.currentIcon = newIcon;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }
	      const action = new Action(this.action);
	      action.execute(this);
	    },
	    setIcon(icon) {
	      this.currentIcon = icon;
	    }
	  },
	  template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<div :class="iconClassname">
					<i :style="iconInteriorStyle"></i>
				</div>
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
		</div>
	`
	};

	const CalendarIcon = {
	  props: {
	    timestamp: {
	      type: Number,
	      required: true,
	      default: 0
	    },
	    calendarEventId: {
	      type: Number,
	      required: false,
	      default: null
	    }
	  },
	  computed: {
	    date() {
	      return this.formatUserTime('d');
	    },
	    month() {
	      return this.formatUserTime('F');
	    },
	    dayWeek() {
	      return this.formatUserTime('D');
	    },
	    time() {
	      return this.getDateTimeConverter().toTimeString();
	    },
	    userTime() {
	      return this.getDateTimeConverter().getValue();
	    },
	    hasCalendarEventId() {
	      return this.calendarEventId > 0;
	    }
	  },
	  methods: {
	    getDateTimeConverter() {
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime();
	    },
	    formatUserTime(format) {
	      return main_date.DateTimeFormat.format(format, this.userTime);
	    }
	  },
	  template: `
		<div class="crm-timeline__calendar-icon-container">
			<div v-if="hasCalendarEventId" class="crm-timeline__calendar-icon_event_icon"></div>
			<div class="crm-timeline__calendar-icon">
				<header class="crm-timeline__calendar-icon_top">
					<div class="crm-timeline__calendar-icon_bullets">
						<div class="crm-timeline__calendar-icon_bullet"></div>
						<div class="crm-timeline__calendar-icon_bullet"></div>
					</div>
				</header>
				<main class="crm-timeline__calendar-icon_content">
					<div class="crm-timeline__calendar-icon_day">{{ date }}</div>
					<div class="crm-timeline__calendar-icon_month">{{ month }}</div>
					<div class="crm-timeline__calendar-icon_date">
						<span class="crm-timeline__calendar-icon_day-week">{{ dayWeek }}</span>
						<span class="crm-timeline__calendar-icon_time">{{ time }}</span>
					</div>
				</main>
			</div>
		</div>
	`
	};

	const LogoCalendar = ui_vue3.BitrixVue.cloneComponent(Logo, {
	  components: {
	    CalendarIcon
	  },
	  props: {
	    timestamp: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    addIcon: String,
	    addIconType: String,
	    calendarEventId: {
	      type: Number,
	      required: false,
	      default: null
	    },
	    backgroundColor: {
	      type: String,
	      required: false,
	      default: null
	    }
	  },
	  computed: {
	    addIconClassname() {
	      return ['crm-timeline__card-logo_add-icon', `--type-${this.addIconType}`, `--icon-${this.addIcon}`];
	    },
	    logoStyle() {
	      if (main_core.Type.isStringFilled(this.backgroundColor)) {
	        return {
	          '--crm-timeline__logo-background': main_core.Text.encode(this.backgroundColor)
	        };
	      }
	      return {};
	    }
	  },
	  template: `
		<div 
			:class="className"
			:style="logoStyle"
			@click="executeAction"
		>
			<div class="crm-timeline__card-logo_content">
				<CalendarIcon :timestamp="timestamp" :calendar-event-id="calendarEventId" />
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
		</div>
	`
	});

	const Body = {
	  components: {
	    Logo,
	    LogoCalendar
	  },
	  props: {
	    logo: Object,
	    blocks: Object
	  },
	  data() {
	    return {
	      blockRefs: {}
	    };
	  },
	  mounted() {
	    const blocks = this.$refs.blocks;
	    if (!blocks || !this.visibleBlocks) {
	      return;
	    }
	    this.visibleBlocks.forEach((block, index) => {
	      if (main_core.Type.isDomNode(blocks[index].$el)) {
	        blocks[index].$el.setAttribute('data-id', block.id);
	      } else {
	        throw new Error('Vue component "' + block.rendererName + '" was not found');
	      }
	    });
	  },
	  beforeUpdate() {
	    this.blockRefs = {};
	  },
	  computed: {
	    visibleBlocks() {
	      if (!main_core.Type.isPlainObject(this.blocks)) {
	        return [];
	      }
	      return Object.keys(this.blocks).map(id => ({
	        id,
	        ...this.blocks[id]
	      })).filter(item => item.scope !== 'mobile').sort((a, b) => {
	        let aSort = a.sort === undefined ? 0 : a.sort;
	        let bSort = b.sort === undefined ? 0 : b.sort;
	        if (aSort < bSort) {
	          return -1;
	        }
	        if (aSort > bSort) {
	          return 1;
	        }
	        return 0;
	      });
	    },
	    contentContainerClassname() {
	      return ['crm-timeline__card-container', {
	        '--without-logo': !this.logo
	      }];
	    }
	  },
	  methods: {
	    getContentBlockById(blockId) {
	      var _this$blockRefs$block;
	      return (_this$blockRefs$block = this.blockRefs[blockId]) !== null && _this$blockRefs$block !== void 0 ? _this$blockRefs$block : null;
	    },
	    getLogo() {
	      return this.$refs.logo;
	    },
	    saveRef(ref, id) {
	      this.blockRefs[id] = ref;
	    }
	  },
	  template: `
		<div class="crm-timeline__card-body">
			<div v-if="logo" class="crm-timeline__card-logo_container">
				<LogoCalendar v-if="logo.icon === 'calendar'" v-bind="logo"></LogoCalendar>
				<Logo v-else v-bind="logo" ref="logo"></Logo>
			</div>
			<div :class="contentContainerClassname">
				<div
					v-for="block in visibleBlocks"
					:key="block.id"
					class="crm-timeline__card-container_block"
				>
					<component
						:is="block.rendererName"
						v-bind="block.properties"
						:ref="(el) => this.saveRef(el, block.id)"
					/>
				</div>
			</div>
		</div>
	`
	};

	let ButtonScope = function ButtonScope() {
	  babelHelpers.classCallCheck(this, ButtonScope);
	};
	babelHelpers.defineProperty(ButtonScope, "MOBILE", 'mobile');

	let ButtonState = function ButtonState() {
	  babelHelpers.classCallCheck(this, ButtonState);
	};
	babelHelpers.defineProperty(ButtonState, "DEFAULT", '');
	babelHelpers.defineProperty(ButtonState, "LOADING", 'loading');
	babelHelpers.defineProperty(ButtonState, "DISABLED", 'disabled');
	babelHelpers.defineProperty(ButtonState, "HIDDEN", 'hidden');
	babelHelpers.defineProperty(ButtonState, "LOCKED", 'locked');
	babelHelpers.defineProperty(ButtonState, "AI_LOADING", 'ai-loading');
	babelHelpers.defineProperty(ButtonState, "AI_SUCCESS", 'ai-success');

	let ButtonType = function ButtonType() {
	  babelHelpers.classCallCheck(this, ButtonType);
	};
	babelHelpers.defineProperty(ButtonType, "ICON", 'icon');
	babelHelpers.defineProperty(ButtonType, "PRIMARY", 'primary');
	babelHelpers.defineProperty(ButtonType, "SECONDARY", 'secondary');
	babelHelpers.defineProperty(ButtonType, "LIGHT", 'light');
	babelHelpers.defineProperty(ButtonType, "AI", 'ai');

	const BaseButton = {
	  props: {
	    id: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    tooltip: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    state: {
	      type: String,
	      required: false,
	      default: ButtonState.DEFAULT
	    },
	    props: Object,
	    action: Object
	  },
	  data() {
	    return {
	      currentState: this.state
	    };
	  },
	  computed: {
	    itemStateToButtonStateDict() {
	      return {
	        [ButtonState.LOADING]: ui_buttons.Button.State.WAITING,
	        [ButtonState.DISABLED]: ui_buttons.Button.State.DISABLED,
	        [ButtonState.AI_LOADING]: ui_buttons.Button.State.AI_WAITING
	      };
	    }
	  },
	  methods: {
	    setDisabled(disabled) {
	      if (disabled) {
	        this.setButtonState(ButtonState.DISABLED);
	      } else {
	        this.setButtonState(ButtonState.DEFAULT);
	      }
	    },
	    setLoading(loading) {
	      if (loading) {
	        this.setButtonState(ButtonState.LOADING);
	      } else {
	        this.setButtonState(ButtonState.DEFAULT);
	      }
	    },
	    setButtonState(state) {
	      if (this.currentState !== state) {
	        this.currentState = state;
	      }
	    },
	    onLayoutUpdated() {
	      this.setButtonState(this.state);
	    },
	    executeAction() {
	      if (this.action && this.currentState !== ButtonState.DISABLED && this.currentState !== ButtonState.LOADING && this.currentState !== ButtonState.AI_LOADING) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }
	  },
	  created() {
	    this.$Bitrix.eventEmitter.subscribe('layout:updated', this.onLayoutUpdated);
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe('layout:updated', this.onLayoutUpdated);
	  },
	  template: `<button></button>`
	};

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _applyMenuItems = /*#__PURE__*/new WeakSet();
	let ButtonMenu = /*#__PURE__*/function (_Menu) {
	  babelHelpers.inherits(ButtonMenu, _Menu);
	  function ButtonMenu(vueComponent, menuItems, menuOptions) {
	    var _this;
	    babelHelpers.classCallCheck(this, ButtonMenu);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ButtonMenu).call(this, vueComponent, menuItems, menuOptions));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _applyMenuItems);
	    _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _applyMenuItems, _applyMenuItems2).call(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  /**
	   * @override
	   */
	  babelHelpers.createClass(ButtonMenu, [{
	    key: "createMenuItem",
	    value: function createMenuItem(item) {
	      const result = {
	        text: item.title,
	        value: item.title
	      };
	      if (main_core.Type.isStringFilled(item.state)) {
	        switch (item.state) {
	          case ButtonState.AI_LOADING:
	            result.className = 'menu-popup-item-ai-loading menu-popup-item-disabled';
	            break;
	          case ButtonState.AI_SUCCESS:
	            result.className = 'menu-popup-item-accept menu-popup-item-disabled';
	            break;
	          case ButtonState.DISABLED:
	            result.className = 'menu-popup-no-icon menu-popup-item-disabled';
	            break;
	          case ButtonState.LOCKED:
	            result.className = 'menu-popup-item-locked';
	            break;
	          default:
	            result.className = '';
	        }
	      }
	      if (main_core.Type.isObject(item.action)) {
	        if (item.action.type === 'redirect') {
	          result.href = item.action.value;
	        } else if (item.action.type === 'jsCode') {
	          result.onclick = item.action.value;
	        } else {
	          result.onclick = () => {
	            void this.onMenuItemClick(item);
	          };
	        }
	      }
	      return result;
	    }
	  }], [{
	    key: "showMenu",
	    value: function showMenu(vueComponent, menuItems, menuOptions) {
	      const menu = new ButtonMenu(vueComponent, menuItems, menuOptions);
	      menu.show();
	    }
	  }]);
	  return ButtonMenu;
	}(Menu);
	function _applyMenuItems2() {
	  const items = this.getMenuItems();
	  if (!items) {
	    return;
	  }
	  const emptyClassItems = items.filter(item => item.className === '');
	  if (emptyClassItems.length === items.length) {
	    return;
	  }
	  items.forEach(item => {
	    if (item.className === '') {
	      // eslint-disable-next-line no-param-reassign
	      item.className = 'menu-popup-empty-icon';
	    }
	  });
	}

	const Button = ui_vue3.BitrixVue.cloneComponent(BaseButton, {
	  props: {
	    type: {
	      type: String,
	      required: false,
	      default: ButtonType.SECONDARY
	    },
	    iconName: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'extra_small'
	    },
	    menuItems: {
	      type: Object,
	      required: false,
	      default: null
	    }
	  },
	  data() {
	    return {
	      popup: null,
	      uiButton: Object.freeze(null),
	      timerSecondsRemaining: 0,
	      currentState: this.state,
	      hintText: main_core.Type.isStringFilled(this.tooltip) ? main_core.Text.encode(this.tooltip) : ''
	    };
	  },
	  computed: {
	    itemTypeToButtonColorDict() {
	      return {
	        [ButtonType.PRIMARY]: ui_buttons.Button.Color.PRIMARY,
	        [ButtonType.SECONDARY]: ui_buttons.Button.Color.LIGHT_BORDER,
	        [ButtonType.LIGHT]: ui_buttons.Button.Color.LIGHT,
	        [ButtonType.ICON]: ui_buttons.Button.Color.LINK,
	        [ButtonType.AI]: ui_buttons.Button.Color.AI
	      };
	    },
	    buttonContainerRef() {
	      return this.$refs.buttonContainer;
	    }
	  },
	  methods: {
	    getButtonOptions() {
	      const upperCaseIconName = main_core.Type.isString(this.iconName) ? this.iconName.toUpperCase() : '';
	      const upperCaseButtonSize = main_core.Type.isString(this.size) ? this.size.toUpperCase() : 'extra_small';
	      const btnColor = this.itemTypeToButtonColorDict[this.type] || ui_buttons.Button.Color.LIGHT_BORDER;
	      const titleText = this.type === ButtonType.ICON ? '' : this.title;
	      return {
	        id: this.id,
	        round: true,
	        dependOnTheme: false,
	        size: ui_buttons.Button.Size[upperCaseButtonSize],
	        text: titleText,
	        color: btnColor,
	        state: this.itemStateToButtonStateDict[this.currentState],
	        icon: ui_buttons.Button.Icon[upperCaseIconName],
	        props: main_core.Type.isPlainObject(this.props) ? this.props : {}
	      };
	    },
	    getUiButton() {
	      return this.uiButton;
	    },
	    disableWithTimer(sec) {
	      this.setButtonState(ButtonState.DISABLED);
	      const btn = this.getUiButton();
	      let remainingSeconds = sec;
	      btn.setText(this.formatSeconds(remainingSeconds));
	      const timer = setInterval(() => {
	        if (remainingSeconds < 1) {
	          clearInterval(timer);
	          btn.setText(this.title);
	          this.setButtonState(ButtonState.DEFAULT);
	          return;
	        }
	        remainingSeconds--;
	        btn.setText(this.formatSeconds(remainingSeconds));
	      }, 1000);
	    },
	    formatSeconds(sec) {
	      const minutes = Math.floor(sec / 60);
	      const seconds = sec % 60;
	      const formatMinutes = this.formatNumber(minutes);
	      const formatSeconds = this.formatNumber(seconds);
	      return `${formatMinutes}:${formatSeconds}`;
	    },
	    formatNumber(num) {
	      return num < 10 ? `0${num}` : num;
	    },
	    setButtonState(state) {
	      var _this$getUiButton, _this$itemStateToButt;
	      this.parentSetButtonState(state);
	      (_this$getUiButton = this.getUiButton()) === null || _this$getUiButton === void 0 ? void 0 : _this$getUiButton.setState((_this$itemStateToButt = this.itemStateToButtonStateDict[this.currentState]) !== null && _this$itemStateToButt !== void 0 ? _this$itemStateToButt : null);
	    },
	    createSplitButton() {
	      const menuItems = Object.keys(this.menuItems).map(key => this.menuItems[key]);
	      const options = this.getButtonOptions();
	      const showMenu = () => {
	        ButtonMenu.showMenu(this, menuItems, {
	          id: `split-button-menu-${this.id}`,
	          className: 'crm-timeline__split-button-menu',
	          width: 250,
	          angle: true,
	          cacheable: false,
	          offsetLeft: 13,
	          bindElement: this.$el.querySelector('.ui-btn-menu')
	        });
	      };
	      options.menuButton = {
	        onclick: (element, event) => {
	          event.stopPropagation();
	          showMenu();
	        }
	      };
	      if (options.state === ui_buttons.ButtonState.DISABLED) {
	        options.mainButton = {
	          onclick: (element, event) => {
	            event.stopPropagation();
	            showMenu();
	          }
	        };
	      }
	      return new ui_buttons.SplitButton(options);
	    },
	    renderButton() {
	      if (!this.buttonContainerRef) {
	        return;
	      }
	      this.buttonContainerRef.innerHTML = '';
	      const button = this.menuItems ? this.createSplitButton() : new ui_buttons.Button(this.getButtonOptions());
	      button.renderTo(this.buttonContainerRef);
	      this.uiButton = button;
	    },
	    setTooltip(tooltip) {
	      this.hintText = tooltip;
	    },
	    showTooltip() {
	      if (this.hintText === '') {
	        return;
	      }
	      BX.UI.Hint.show(this.$el, this.hintText, true);
	    },
	    hideTooltip() {
	      if (this.hintText === '') {
	        return;
	      }
	      BX.UI.Hint.hide(this.$el);
	    },
	    isInViewport() {
	      const rect = this.$el.getBoundingClientRect();
	      return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
	    },
	    isPropEqual(propName, value) {
	      return this.getButtonOptions().props[propName] === value;
	    }
	  },
	  watch: {
	    state(newValue) {
	      this.setButtonState(newValue);
	    },
	    tooltip(newValue) {
	      this.hintText = main_core.Type.isStringFilled(newValue) ? main_core.Text.encode(newValue) : '';
	    }
	  },
	  mounted() {
	    this.renderButton();
	  },
	  updated() {
	    this.renderButton();
	  },
	  template: `
		<div
			:class="$attrs.class"
			ref="buttonContainer"
			@click="executeAction"
			@mouseover="showTooltip"
			@mouseleave="hideTooltip"
		>
		</div>
	`
	});

	const AdditionalButtonIcon = Object.freeze({
	  NOTE: 'note',
	  SCRIPT: 'script',
	  PRINT: 'print',
	  DOTS: 'dots'
	});
	const AdditionalButtonColor = Object.freeze({
	  DEFAULT: 'default',
	  PRIMARY: 'primary'
	});
	const AdditionalButton = ui_vue3.BitrixVue.cloneComponent(BaseButton, {
	  props: {
	    iconName: {
	      type: String,
	      required: false,
	      default: '',
	      validator(value) {
	        return Object.values(AdditionalButtonIcon).indexOf(value) > -1;
	      }
	    },
	    color: {
	      type: String,
	      required: false,
	      default: AdditionalButtonColor.DEFAULT,
	      validator(value) {
	        return Object.values(AdditionalButtonColor).indexOf(value) > -1;
	      }
	    }
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__card_add-button', {
	        [`--icon-${this.iconName}`]: this.iconName,
	        [`--color-${this.color}`]: this.color,
	        [`--state-${this.currentState}`]: this.currentState
	      }];
	    },
	    ButtonState() {
	      return ButtonState;
	    },
	    loaderHtml() {
	      const loader = new main_loader.Loader({
	        mode: 'inline',
	        size: 20
	      });
	      loader.show();
	      return loader.layout.outerHTML;
	    }
	  },
	  template: `
		<transition name="crm-timeline__card_add-button-fade" mode="out-in">
			<div
				v-if="currentState === ButtonState.LOADING"
				v-html="loaderHtml"
				class="crm-timeline__card_add-button"
			></div>
			<div
				v-else
				:title="title"
				@click="executeAction"
				:class="className">
			</div>
		</transition>
	`
	});

	const Buttons = {
	  components: {
	    Button
	  },
	  props: {
	    items: {
	      type: Array,
	      required: false,
	      default: () => []
	    }
	  },
	  methods: {
	    getButtonById(buttonId) {
	      const buttons = this.$refs.buttons;
	      return this.items.reduce((found, button, index) => {
	        if (found) {
	          return found;
	        }
	        if (button.id === buttonId) {
	          return buttons[index];
	        }
	        return null;
	      }, null);
	    }
	  },
	  template: `
			<div class="crm-timeline__card-action_buttons">
				<Button class="crm-timeline__card-action-btn" v-for="item in items" v-bind="item" ref="buttons" />
			</div>
		`
	};

	const MenuId = 'timeline-more-button-menu';
	const Menu$1 = {
	  components: {
	    AdditionalButton
	  },
	  props: {
	    buttons: Array,
	    // buttons that didn't fit into footer
	    items: Object // real menu items
	  },

	  inject: ['isReadOnly'],
	  computed: {
	    isMenuFilled() {
	      const menuItems = this.menuItems;
	      return menuItems.length > 0;
	    },
	    itemsArray() {
	      if (!this.items) {
	        return [];
	      }
	      return Object.values(this.items).filter(item => item.state !== 'hidden' && item.scope !== 'mobile' && (!this.isReadOnly || !item.hideIfReadonly)).sort((a, b) => a.sort - b.sort);
	    },
	    menuItems() {
	      let result = this.buttons;
	      if (this.buttons.length && this.itemsArray.length) {
	        result.push({
	          delimiter: true
	        });
	      }
	      result = [...result, ...this.itemsArray];
	      return result;
	    },
	    buttonProps() {
	      return {
	        color: AdditionalButtonColor.DEFAULT,
	        icon: AdditionalButtonIcon.DOTS
	      };
	    }
	  },
	  beforeUnmount() {
	    const menu = main_popup.MenuManager.getMenuById(MenuId);
	    if (menu) {
	      menu.destroy();
	    }
	  },
	  methods: {
	    showMenu() {
	      Menu.showMenu(this, this.menuItems, {
	        id: MenuId,
	        className: 'crm-timeline__card_more-menu',
	        width: 230,
	        angle: false,
	        cacheable: false,
	        bindElement: this.$el
	      });
	    }
	  },
	  // language=Vue
	  template: `
		<div v-if="isMenuFilled" class="crm-timeline__card-action_menu-item" @click="showMenu">
			<AdditionalButton iconName="dots" color="default"></AdditionalButton>
		</div>
	`
	};

	const Footer = {
	  components: {
	    Buttons,
	    Menu: Menu$1,
	    Button,
	    AdditionalButton
	  },
	  props: {
	    buttons: Object,
	    menu: Object,
	    additionalButtons: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    },
	    maxBaseButtonsCount: {
	      type: Number,
	      required: false,
	      default: 3
	    }
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    containerClassname() {
	      return ['crm-timeline__card-action', {
	        '--no-margin-top': this.baseButtons.length < 1
	      }];
	    },
	    baseButtons() {
	      return this.visibleAndSortedButtons.slice(0, this.maxBaseButtonsCount);
	    },
	    moreButtons() {
	      return this.visibleAndSortedButtons.slice(this.maxBaseButtonsCount);
	    },
	    visibleAndSortedButtons() {
	      return this.visibleButtons.sort(this.buttonsSorter);
	    },
	    visibleAndSortedAdditionalButtons() {
	      return this.visibleAdditionalButtons.sort(this.buttonsSorter);
	    },
	    visibleButtons() {
	      if (!main_core.Type.isPlainObject(this.buttons)) {
	        return [];
	      }
	      return this.buttons ? Object.keys(this.buttons).map(id => ({
	        id,
	        ...this.buttons[id]
	      })).filter(this.visibleButtonsFilter) : [];
	    },
	    visibleAdditionalButtons() {
	      return this.additionalButtonsArray ? Object.values(this.additionalButtonsArray).filter(this.visibleButtonsFilter) : [];
	    },
	    additionalButtonsArray() {
	      return Object.entries(this.additionalButtons).map(([id, button]) => {
	        return {
	          id,
	          type: ButtonType.ICON,
	          ...button
	        };
	      });
	    },
	    hasMenu() {
	      return this.moreButtons.length || main_core.Type.isPlainObject(this.menu) && Object.keys(this.menu).length;
	    }
	  },
	  methods: {
	    visibleButtonsFilter(buttonItem) {
	      return buttonItem.state !== ButtonState.HIDDEN && buttonItem.scope !== ButtonScope.MOBILE && (!this.isReadOnly || !buttonItem.hideIfReadonly);
	    },
	    buttonsSorter(buttonA, buttonB) {
	      return (buttonA === null || buttonA === void 0 ? void 0 : buttonA.sort) - (buttonB === null || buttonB === void 0 ? void 0 : buttonB.sort);
	    },
	    getButtonById(buttonId) {
	      if (this.$refs.buttons) {
	        const foundButton = this.$refs.buttons.getButtonById(buttonId);
	        if (foundButton) {
	          return foundButton;
	        }
	      }
	      if (this.$refs.additionalButtons) {
	        return this.visibleAndSortedAdditionalButtons.reduce((found, button, index) => {
	          if (found) {
	            return found;
	          }
	          if (button.id === buttonId) {
	            return buttons[index];
	          }
	          return null;
	        }, null);
	      }
	      return null;
	    },
	    getMenu() {
	      if (this.$refs.menu) {
	        return this.$refs.menu;
	      }
	      return null;
	    }
	  },
	  template: `
		<div :class="containerClassname">
			<div class="crm-timeline__card-action_menu">
				<div
					v-for="button in visibleAndSortedAdditionalButtons"
					:key="button.id"
					class="crm-timeline__card-action_menu-item"
				>
					<additional-button
						v-bind="button"
					>
					</additional-button>
				</div>
				<Menu v-if="hasMenu" :buttons="moreButtons" v-bind="menu" ref="menu"/>
			</div>
			<Buttons ref="buttons" :items="baseButtons" />
		</div>
	`
	};

	const ChangeStreamButton = {
	  props: {
	    disableIfReadonly: Boolean,
	    type: String,
	    title: String,
	    action: Object
	  },
	  data() {
	    return {
	      isReadonlyMode: false,
	      isComplete: false
	    };
	  },
	  inject: ['isReadOnly'],
	  mounted() {
	    this.isReadonlyMode = this.isReadOnly;
	  },
	  computed: {
	    isShowPinButton() {
	      return this.type === 'pin' && !this.isReadonlyMode;
	    },
	    isShowUnpinButton() {
	      return this.type === 'unpin' && !this.isReadonlyMode;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }
	      this.isComplete = true;
	      const action = new Action(this.action);
	      action.execute(this).then(() => {}).catch(() => {
	        this.isComplete = false;
	      });
	    },
	    onClick() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    },
	    setDisabled(disabled) {
	      if (!this.isReadonly && !disabled) {
	        this.isReadonlyMode = false;
	      }
	      if (disabled) {
	        this.isReadonlyMode = true;
	      }
	    },
	    markCheckboxUnchecked() {
	      this.isComplete = false;
	    }
	  },
	  template: `
		<div class="crm-timeline__card-top_controller">
			<input
				v-if="type === 'complete'"
				@click="executeAction"
				type="checkbox"
				:disabled="isReadonlyMode"
				:checked="isComplete"
				class="crm-timeline__card-top_checkbox"
			/>
			<div
				v-else-if="isShowPinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --pin"
			></div>
			<div
				v-else-if="isShowUnpinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --unpin"
			></div>
		</div>
	`
	};

	const ColorSelector = {
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    valuesList: {
	      type: Object,
	      required: true
	    },
	    selectedValueId: {
	      type: String,
	      default: 'default'
	    },
	    readOnlyMode: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  data() {
	    return {
	      currentValueId: this.selectedValueId
	    };
	  },
	  methods: {
	    getValue() {
	      return this.currentValueId;
	    },
	    setValue(value) {
	      this.currentValueId = value;
	      if (this.itemSelector) {
	        this.itemSelector.setValue(value);
	      }
	    },
	    onItemSelectorValueChange({
	      data
	    }) {
	      const valueId = data.value;
	      if (this.currentValueId !== valueId) {
	        this.currentValueId = valueId;
	        this.emitEvent('ColorSelector:Change', {
	          colorId: valueId
	        });
	      }
	    },
	    emitEvent(eventName, actionParams) {
	      const action = new Action({
	        type: 'jsEvent',
	        value: eventName,
	        actionParams
	      });
	      action.execute(this);
	    }
	  },
	  mounted() {
	    void this.$nextTick(() => {
	      this.itemSelector = new crm_field_colorSelector.ColorSelector({
	        target: this.$refs.itemSelectorRef,
	        colorList: this.valuesList,
	        selectedColorId: this.currentValueId,
	        readOnlyMode: this.readOnlyMode
	      });
	      if (!this.readOnlyMode) {
	        main_core_events.EventEmitter.subscribe(this.itemSelector, crm_field_colorSelector.ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE, this.onItemSelectorValueChange);
	      }
	    });
	  },
	  computed: {
	    hint() {
	      if (this.readOnlyMode) {
	        return null;
	      }
	      return {
	        text: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_COLOR_SELECTOR_HINT'),
	        popupOptions: {
	          angle: {
	            offset: 30,
	            position: 'top'
	          },
	          offsetTop: 2
	        }
	      };
	    }
	  },
	  template: `
		<div class="crm-activity__todo-editor-v2_color-selector">
			<div ref="itemSelectorRef" v-hint="hint"></div>
		</div>
	`
	};

	const FormatDate = {
	  name: 'FormatDate',
	  props: {
	    timestamp: {
	      type: Number,
	      required: true,
	      default: 0
	    },
	    datePlaceholder: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    useShortTimeFormat: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    class: {
	      type: [Array, Object, String],
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    formattedDate() {
	      if (!this.timestamp) {
	        return this.datePlaceholder;
	      }
	      const converter = crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime();
	      return this.useShortTimeFormat ? converter.toTimeString() : converter.toDatetimeString({
	        delimiter: ', '
	      });
	    }
	  },
	  template: `
		<div :class="$props.class">{{ formattedDate }}</div>
	`
	};

	const Hint = {
	  data() {
	    return {
	      isMouseOnHintArea: false,
	      hintPopup: null
	    };
	  },
	  props: {
	    icon: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    textBlocks: {
	      type: Array,
	      required: false,
	      default: []
	    }
	  },
	  computed: {
	    hintContentIcon() {
	      if (this.icon === '') {
	        return null;
	      }
	      const iconElement = main_core.Dom.create('i');
	      return main_core.Dom.create('div', {
	        attrs: {
	          classname: this.hintContentIconClassname
	        },
	        children: [iconElement]
	      });
	    },
	    hintContentText() {
	      return main_core.Dom.create('div', {
	        attrs: {
	          classname: 'crm-timeline__hint_popup-content-text'
	        },
	        children: this.hintContentTextBlocks
	      });
	    },
	    hintContentTextBlocks() {
	      return this.textBlocks.map(this.getContentBlockNode);
	    },
	    hintContentIconClassname() {
	      const baseClassname = 'crm-timeline__hint_popup-content-icon';
	      return `${baseClassname} --${this.icon}`;
	    },
	    hintIconClassname() {
	      return ['ui-hint', 'crm-timeline__header-hint', {
	        '--active': this.hintPopup
	      }];
	    },
	    hasContent() {
	      return this.textBlocks.length > 0;
	    }
	  },
	  methods: {
	    getHintContent() {
	      return main_core.Dom.create('div', {
	        attrs: {
	          classname: 'crm-timeline__hint_popup-content'
	        },
	        style: {
	          display: 'flex'
	        },
	        children: [this.hintContentIcon, this.hintContentText]
	      });
	    },
	    getPopupOptions() {
	      return {
	        darkMode: true,
	        autoHide: false,
	        content: this.getHintContent(),
	        maxWidth: 400,
	        bindOptions: {
	          position: 'top'
	        },
	        animation: 'fading-slide'
	      };
	    },
	    getPopupPosition() {
	      var _this$hintPopup;
	      const hintElem = this.$refs.hint;
	      const defaultAngleLeftOffset = main_popup.Popup.getOption('angleLeftOffset');
	      const {
	        width: hintWidth,
	        left: hintLeftOffset,
	        top: hintTopOffset
	      } = main_core.Dom.getPosition(hintElem);
	      const {
	        width: popupWidth
	      } = main_core.Dom.getPosition((_this$hintPopup = this.hintPopup) === null || _this$hintPopup === void 0 ? void 0 : _this$hintPopup.getPopupContainer());
	      return {
	        left: hintLeftOffset + defaultAngleLeftOffset - (popupWidth - hintWidth) / 2,
	        top: hintTopOffset + 15
	      };
	    },
	    getPopupAngleOffset(popupContainer) {
	      const angleWidth = 33;
	      const {
	        width: popupWidth
	      } = main_core.Dom.getPosition(popupContainer);
	      return (popupWidth - angleWidth) / 2;
	    },
	    onMouseEnterToPopup() {
	      this.isMouseOnHintArea = true;
	    },
	    onHintAreaMouseLeave() {
	      this.isMouseOnHintArea = false;
	      setTimeout(() => {
	        if (!this.isMouseOnHintArea) {
	          this.hideHintPopup();
	        }
	      }, 400);
	    },
	    onMouseEnterToHint() {
	      this.isMouseOnHintArea = true;
	      this.showHintPopupWithDebounce();
	    },
	    showHintPopup() {
	      if (!this.isMouseOnHintArea || this.hintPopup && this.hintPopup.isShown()) {
	        return;
	      }
	      this.hintPopup = new main_popup.Popup(this.getPopupOptions());
	      const popupContainer = this.hintPopup.getPopupContainer();
	      main_core.Event.bind(popupContainer, 'mouseenter', this.onMouseEnterToPopup);
	      main_core.Event.bind(popupContainer, 'mouseleave', this.onHintAreaMouseLeave);
	      this.hintPopup.show();
	      this.hintPopup.setBindElement(this.getPopupPosition());
	      this.hintPopup.setAngle(false);
	      this.hintPopup.setAngle({
	        offset: this.getPopupAngleOffset(popupContainer, this.$refs.hint)
	      });
	      this.hintPopup.adjustPosition();
	      this.hintPopup.show();
	    },
	    showHintPopupWithDebounce() {
	      main_core.Runtime.debounce(this.showHintPopup, 300, this)();
	    },
	    hideHintPopup() {
	      if (!this.hintPopup) {
	        return;
	      }
	      this.hintPopup.close();
	      const popupContainer = this.hintPopup.getPopupContainer();
	      main_core.Event.unbind(popupContainer, 'mouseenter', this.onMouseEnterToPopup);
	      main_core.Event.unbind(popupContainer, 'mouseleave', this.onHintAreaMouseLeave);
	      this.hintPopup.destroy();
	      this.hintPopup = null;
	    },
	    hideHintPopupWithDebounce() {
	      return main_core.Runtime.debounce(this.hideHintPopup, 300, this);
	    },
	    getContentBlockNode(contentBlock) {
	      if (contentBlock.type === 'text') {
	        return this.getTextNode(contentBlock.options);
	      } else if (contentBlock.type === 'link') {
	        return this.getLinkNode(contentBlock.options);
	      }
	      return null;
	    },
	    getTextNode(textOptions = {}) {
	      return main_core.Dom.create('span', {
	        text: textOptions.text
	      });
	    },
	    getLinkNode(linkOptions = {}) {
	      const link = main_core.Dom.create('span', {
	        text: linkOptions.text
	      });
	      main_core.Dom.addClass(link, 'crm-timeline__hint_popup-content-link');
	      link.onclick = () => {
	        this.executeAction(linkOptions.action);
	      };
	      return link;
	    },
	    executeAction(actionObj) {
	      if (actionObj) {
	        const action = new Action(actionObj);
	        action.execute(this);
	      }
	    }
	  },
	  template: `
		<span
			ref="hint"
			@click.stop.prevent
			@mouseenter="onMouseEnterToHint"
			@mouseleave="onHintAreaMouseLeave"
			v-if="hasContent"
			:class="hintIconClassname"
		>
			<span class="ui-hint-icon" />
		</span>
	`
	};

	let TagType = function TagType() {
	  babelHelpers.classCallCheck(this, TagType);
	};
	babelHelpers.defineProperty(TagType, "PRIMARY", 'primary');
	babelHelpers.defineProperty(TagType, "SECONDARY", 'secondary');
	babelHelpers.defineProperty(TagType, "SUCCESS", 'success');
	babelHelpers.defineProperty(TagType, "WARNING", 'warning');
	babelHelpers.defineProperty(TagType, "FAILURE", 'failure');
	babelHelpers.defineProperty(TagType, "LAVENDER", 'lavender');

	const Tag = {
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    hint: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    action: {
	      type: Object,
	      required: false,
	      default: null
	    },
	    type: {
	      type: String,
	      required: false,
	      default: TagType.SECONDARY
	    },
	    state: String
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card-status': true,
	        '--clickable': Boolean(this.action),
	        '--hint': Boolean(this.hint)
	      };
	    },
	    tagTypeToLabelColorDict() {
	      return {
	        [TagType.PRIMARY]: ui_label.Label.Color.LIGHT_BLUE,
	        [TagType.SECONDARY]: ui_label.Label.Color.LIGHT,
	        [TagType.LAVENDER]: ui_label.Label.Color.LAVENDER,
	        [TagType.SUCCESS]: ui_label.Label.Color.LIGHT_GREEN,
	        [TagType.WARNING]: ui_label.Label.Color.LIGHT_YELLOW,
	        [TagType.FAILURE]: ui_label.Label.Color.LIGHT_RED
	      };
	    },
	    tagContainerRef() {
	      return this.$refs.tag;
	    }
	  },
	  methods: {
	    getLabelColorFromTagType(tagType) {
	      const lowerCaseTagType = tagType ? tagType.toLowerCase() : '';
	      const labelColor = this.tagTypeToLabelColorDict[lowerCaseTagType];
	      return labelColor || ui_label.Label.Color.LIGHT;
	    },
	    // eslint-disable-next-line consistent-return
	    renderTag(tagOptions) {
	      if (!tagOptions || !this.tagContainerRef) {
	        return null;
	      }
	      const {
	        title,
	        type
	      } = tagOptions;
	      const uppercaseTitle = title && main_core.Type.isString(title) ? title.toUpperCase() : '';
	      const label = new ui_label.Label({
	        text: uppercaseTitle,
	        color: this.getLabelColorFromTagType(type),
	        fill: true
	      });
	      main_core.Dom.clean(this.tagContainerRef);
	      main_core.Dom.append(label.render(), this.tagContainerRef);
	    },
	    executeAction() {
	      if (!this.action) {
	        return;
	      }
	      const action = new Action(this.action);
	      action.execute(this);
	    },
	    showTooltip() {
	      if (this.hint === '') {
	        return;
	      }
	      main_core.Runtime.debounce(() => {
	        BX.UI.Hint.show(this.$el, this.hint, true);
	      }, 50, this)();
	    },
	    hideTooltip() {
	      if (this.hint === '') {
	        return;
	      }
	      BX.UI.Hint.hide(this.$el);
	    }
	  },
	  mounted() {
	    this.renderTag({
	      title: this.title,
	      type: this.type
	    });
	  },
	  updated() {
	    this.renderTag({
	      title: this.title,
	      type: this.type
	    });
	  },
	  template: `
		<div
			ref="tag"
			:class="className"
			@mouseover="showTooltip"
			@mouseleave="hideTooltip"
			@click="executeAction"
		></div>
	`
	};

	const Title = {
	  props: {
	    title: String,
	    action: Object
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    className() {
	      return ['crm-timeline__card-title', {
	        '--light': this.isLogMessage,
	        '--action': !!this.action
	      }];
	    },
	    href() {
	      if (!this.action) {
	        return null;
	      }
	      const action = new Action(this.action);
	      if (action.isRedirect()) {
	        return action.getValue();
	      }
	      return null;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }
	      const action = new Action(this.action);
	      action.execute(this);
	    }
	  },
	  template: `
		<a
			v-if="href"
			:href="href"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</a>
		<span
			v-else
			@click="executeAction"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</span>`
	};

	const User = {
	  props: {
	    title: String,
	    detailUrl: String,
	    imageUrl: String
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    styles() {
	      if (!this.imageUrl) {
	        return {};
	      }
	      return {
	        backgroundImage: "url('" + encodeURI(main_core.Text.encode(this.imageUrl)) + "')",
	        backgroundSize: '21px'
	      };
	    },
	    className() {
	      return ['ui-icon', 'ui-icon-common-user', 'crm-timeline__user-icon', {
	        '--muted': this.isLogMessage
	      }];
	    }
	  },
	  // language=Vue
	  template: `<a :class="className" :href="detailUrl"
				  target="_blank" :title="title"><i :style="styles"></i></a>`
	};

	const Header = {
	  components: {
	    ColorSelector,
	    ChangeStreamButton,
	    Title,
	    Tag,
	    User,
	    FormatDate,
	    Hint
	  },
	  props: {
	    title: String,
	    titleAction: Object,
	    date: Number,
	    datePlaceholder: String,
	    useShortTimeFormat: Boolean,
	    changeStreamButton: Object | null,
	    tags: Object,
	    user: Object,
	    infoHelper: Object,
	    colorSettings: {
	      type: Object,
	      required: false,
	      default: null
	    }
	  },
	  inject: ['isReadOnly', 'isLogMessage'],
	  computed: {
	    visibleTags() {
	      if (!main_core.Type.isPlainObject(this.tags)) {
	        return [];
	      }
	      return this.tags ? Object.values(this.tags).filter(element => this.isVisibleTagFilter(element)) : [];
	    },
	    visibleAndAscSortedTags() {
	      const tagsCopy = main_core.Runtime.clone(this.visibleTags);
	      return tagsCopy.sort(this.tagsAscSorter);
	    },
	    isShowDate() {
	      return this.date || this.datePlaceholder;
	    },
	    className() {
	      return ['crm-timeline__card-top', {
	        '--log-message': this.isReadOnly || this.isLogMessage
	      }];
	    }
	  },
	  methods: {
	    isVisibleTagFilter(tag) {
	      return tag.state !== 'hidden' && tag.scope !== 'mobile' && (!this.isReadOnly || !tag.hideIfReadonly);
	    },
	    tagsAscSorter(tagA, tagB) {
	      return tagA.sort - tagB.sort;
	    },
	    getChangeStreamButton() {
	      return this.$refs.changeStreamButton;
	    }
	  },
	  created() {
	    this.$watch('colorSettings', newColorSettings => {
	      this.$refs.colorSelector.setValue(newColorSettings.selectedValueId);
	    }, {
	      deep: true
	    });
	  },
	  template: `
		<div :class="className">
			<div class="crm-timeline__card-top_info">
				<div class="crm-timeline__card-top_info_left">
					<ChangeStreamButton 
						v-if="changeStreamButton" 
						v-bind="changeStreamButton" 
						ref="changeStreamButton"
					/>
					<Title :title="title" :action="titleAction"></Title>
					<Hint v-if="infoHelper" v-bind="infoHelper"></Hint>
				</div>
				<div ref="tags" class="crm-timeline__card-top_info_right">
					<Tag
						v-for="(tag, index) in visibleAndAscSortedTags"
						:key="index"
						v-bind="tag"
					/>
					<FormatDate
						v-if="isShowDate"
						:timestamp="date"
						:use-short-time-format="useShortTimeFormat"
						:date-placeholder="datePlaceholder"
						class="crm-timeline__card-time"
					/>
				</div>
			</div>
			<div class="crm-timeline__card-top_components-container">
				<ColorSelector
					v-if="colorSettings"
					ref="colorSelector"
					:valuesList="colorSettings.valuesList"
					:selectedValueId="colorSettings.selectedValueId"
					:readOnlyMode="colorSettings.readOnlyMode"
				/>
				<User v-bind="user"></User>
			</div>
		</div>
	`
	};

	let IconBackgroundColor = function IconBackgroundColor() {
	  babelHelpers.classCallCheck(this, IconBackgroundColor);
	};
	babelHelpers.defineProperty(IconBackgroundColor, "PRIMARY", 'primary');
	babelHelpers.defineProperty(IconBackgroundColor, "PRIMARY_ALT", 'primary_alt');
	babelHelpers.defineProperty(IconBackgroundColor, "FAILURE", 'failure');

	const Icon = {
	  props: {
	    code: {
	      type: String,
	      required: false,
	      default: 'none'
	    },
	    counterType: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    backgroundColorToken: {
	      type: String,
	      required: false,
	      default: IconBackgroundColor.PRIMARY
	    },
	    backgroundUri: String,
	    backgroundColor: {
	      type: String,
	      required: false,
	      default: null
	    }
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card_icon': true,
	        [`--bg-${this.backgroundColorToken}`]: Boolean(this.backgroundColorToken),
	        [`--code-${this.code}`]: Boolean(this.code) && !this.backgroundUri,
	        '--custom-bg': Boolean(this.backgroundUri),
	        '--muted': this.isLogMessage
	      };
	    },
	    counterNodeContainer() {
	      return this.$refs.counter;
	    },
	    styles() {
	      if (!this.backgroundUri) {
	        return {};
	      }
	      return {
	        backgroundImage: `url('${encodeURI(main_core.Text.encode(this.backgroundUri))}')`
	      };
	    },
	    iconStyle() {
	      if (main_core.Type.isStringFilled(this.backgroundColor)) {
	        return {
	          '--crm-timeline-card-icon-background': main_core.Text.encode(this.backgroundColor)
	        };
	      }
	      return {};
	    }
	  },
	  methods: {
	    renderCounter() {
	      if (!this.counterType) {
	        return;
	      }
	      main_core.Dom.clean(this.counterNodeContainer);
	      const counter = new ui_cnt.Counter({
	        value: 1,
	        border: true,
	        color: ui_cnt.Counter.Color[this.counterType.toUpperCase()]
	      });
	      counter.renderTo(this.counterNodeContainer);
	    }
	  },
	  mounted() {
	    this.renderCounter();
	  },
	  watch: {
	    counterType(newCounterType)
	    // update if counter state changed
	    {
	      void this.$nextTick(() => {
	        this.renderCounter();
	      });
	    }
	  },
	  template: `
		<div :class="className" :style="iconStyle">
			<i :style="styles"></i>
			<div ref="counter" v-show="!!counterType" class="crm-timeline__card_icon_counter"></div>
		</div>
	`
	};

	const MarketPanel = {
	  props: {
	    text: String,
	    detailsText: String,
	    detailsTextAction: Object
	  },
	  computed: {
	    needShowDetailsText() {
	      return main_core.Type.isStringFilled(this.detailsText);
	    },
	    href() {
	      if (!this.detailsTextAction) {
	        return null;
	      }
	      const action = new Action(this.detailsTextAction);
	      if (action.isRedirect()) {
	        return action.getValue();
	      }
	      return null;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.detailsTextAction) {
	        const action = new Action(this.detailsTextAction);
	        action.execute(this);
	      }
	    }
	  },
	  template: `
		<div class="crm-timeline__card-bottom">
		<div class="crm-timeline__card-market">
			<div class="crm-timeline__card-market_container">
				<span class="crm-timeline__card-market_logo"></span>
				<span class="crm-timeline__card-market_text">{{ text }}</span>
				<a
					v-if="href && needShowDetailsText"
					:href="href"
					class="crm-timeline__card-market_more"
				>
					{{detailsText}}
				</a>
				<span
					v-if="!href && needShowDetailsText"
					@click="executeAction"
					class="crm-timeline__card-market_more"
				>
				{{detailsText}}
				</span>
			</div>
			<div class="crm-timeline__card-market_cross"><i></i></div>
		</div>
		</div>
	`
	};

	const UserPick = {
	  template: `
		<div class="ui-icon ui-icon-common-user crm-timeline__card-top_user-icon">
			<i></i>
		</div>
	`
	};

	const Item = {
	  components: {
	    Icon,
	    Header,
	    Body,
	    Footer,
	    MarketPanel,
	    UserPick
	  },
	  props: {
	    initialLayout: Object,
	    id: String,
	    useShortTimeFormat: Boolean,
	    isLogMessage: Boolean,
	    isReadOnly: Boolean,
	    currentUser: Object | null,
	    onAction: Function,
	    initialColor: {
	      type: Object,
	      required: false,
	      default: null
	    },
	    streamType: {
	      type: Number,
	      required: false,
	      default: StreamType.history
	    }
	  },
	  data() {
	    return {
	      layout: this.initialLayout,
	      color: this.initialColor,
	      isFaded: false,
	      loader: Object.freeze(null)
	    };
	  },
	  provide() {
	    var _this$initialLayout;
	    return {
	      isLogMessage: Boolean((_this$initialLayout = this.initialLayout) === null || _this$initialLayout === void 0 ? void 0 : _this$initialLayout.isLogMessage),
	      isReadOnly: this.isReadOnly,
	      currentUser: this.currentUser
	    };
	  },
	  created() {
	    this.$Bitrix.eventEmitter.subscribe('crm:timeline:item:action', this.onActionEvent);
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe('crm:timeline:item:action', this.onActionEvent);
	  },
	  methods: {
	    onActionEvent(event) {
	      const eventData = event.getData();
	      this.onAction(main_core.Runtime.clone(eventData));
	    },
	    setLayout(newLayout) {
	      this.layout = newLayout;
	      this.isFaded = false;
	      this.$Bitrix.eventEmitter.emit('layout:updated');
	    },
	    setColor(newColor) {
	      this.color = newColor;
	    },
	    setFaded(faded) {
	      this.isFaded = faded;
	    },
	    showLoader(showLoader) {
	      if (showLoader) {
	        this.setFaded(true);
	        if (!this.loader) {
	          this.loader = new main_loader.Loader();
	        }
	        this.loader.show(this.$el.parentNode);
	      } else {
	        if (this.loader) {
	          this.loader.hide();
	        }
	        this.setFaded(false);
	      }
	    },
	    getContentBlockById(blockId) {
	      if (!this.$refs.body) {
	        return null;
	      }
	      return this.$refs.body.getContentBlockById(blockId);
	    },
	    getLogo() {
	      if (!this.$refs.body) {
	        return null;
	      }
	      return this.$refs.body.getLogo();
	    },
	    getHeaderChangeStreamButton() {
	      if (!this.$refs.header) {
	        return null;
	      }
	      return this.$refs.header.getChangeStreamButton();
	    },
	    getFooterButtonById(buttonId) {
	      if (!this.$refs.footer) {
	        return null;
	      }
	      return this.$refs.footer.getButtonById(buttonId);
	    },
	    getFooterMenu() {
	      if (!this.$refs.footer) {
	        return null;
	      }
	      return this.$refs.footer.getMenu();
	    },
	    highlightContentBlockById(blockId, isHighlighted) {
	      if (!isHighlighted) {
	        this.isFaded = false;
	      }
	      const block = this.getContentBlockById(blockId);
	      if (!block) {
	        return;
	      }
	      if (isHighlighted) {
	        this.isFaded = true;
	        main_core.Dom.addClass(block.$el, '--highlighted');
	      } else {
	        this.isFaded = false;
	        main_core.Dom.removeClass(block.$el, '--highlighted');
	      }
	    }
	  },
	  computed: {
	    timelineCardClassname() {
	      return {
	        'crm-timeline__card': true,
	        'crm-timeline__card-scope': true,
	        '--stream-type-history': this.streamType === StreamType.history,
	        '--stream-type-scheduled': this.streamType === StreamType.scheduled,
	        '--stream-type-pinned': this.streamType === StreamType.pinned,
	        '--log-message': this.isLogMessage
	      };
	    },
	    timelineCardStyle() {
	      if (main_core.Type.isPlainObject(this.color) && this.streamType === StreamType.scheduled) {
	        return {
	          '--crm-timeline__card-color-background': main_core.Text.encode(this.color.itemBackground)
	        };
	      }
	      return {};
	    }
	  },
	  template: `
	  	<div class="crm-timeline__card-wrapper">
			<div class="crm-timeline__card_icon_container">
				<Icon v-bind="layout.icon"></Icon>
			</div>
			<div 
				:data-id="id" 
				ref="timelineCard" 
				:class="timelineCardClassname"
				:style="timelineCardStyle"
			>
				<div class="crm-timeline__card_fade" v-if="isFaded"></div>
				<Header 
					v-if="layout.header"
					v-bind="layout.header"
					:use-short-time-format="useShortTimeFormat"
					ref="header"
				/>
				<Body v-if="layout.body" v-bind="layout.body" ref="body"></Body>
				<Footer v-if="layout.footer" v-bind="layout.footer" ref="footer"></Footer>
				<MarketPanel v-if="layout.marketPanel" v-bind="layout.marketPanel"></MarketPanel>
			</div>
		</div>
	`
	};

	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var _id = /*#__PURE__*/new WeakMap();
	let ControllerManager = /*#__PURE__*/function () {
	  function ControllerManager(id) {
	    babelHelpers.classCallCheck(this, ControllerManager);
	    _classPrivateFieldInitSpec$2(this, _id, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _id, id);
	  }
	  babelHelpers.createClass(ControllerManager, [{
	    key: "getItemControllers",
	    value: function getItemControllers(item) {
	      const foundControllers = [];
	      for (const controller of ControllerManager.getRegisteredControllers()) {
	        if (controller.isItemSupported(item)) {
	          const controllerInstance = new controller();
	          controllerInstance.onInitialize(item);
	          foundControllers.push(controllerInstance);
	        }
	      }
	      return foundControllers;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance(timelineId) {
	      if (!_classStaticPrivateFieldSpecGet(this, ControllerManager, _instances).hasOwnProperty(timelineId)) {
	        _classStaticPrivateFieldSpecGet(this, ControllerManager, _instances)[timelineId] = new ControllerManager(timelineId);
	      }
	      return _classStaticPrivateFieldSpecGet(this, ControllerManager, _instances)[timelineId];
	    }
	  }, {
	    key: "registerController",
	    value: function registerController(controller) {
	      _classStaticPrivateFieldSpecGet(this, ControllerManager, _availableControllers).push(controller);
	    }
	  }, {
	    key: "getRegisteredControllers",
	    value: function getRegisteredControllers() {
	      return _classStaticPrivateFieldSpecGet(this, ControllerManager, _availableControllers);
	    }
	  }]);
	  return ControllerManager;
	}();
	var _instances = {
	  writable: true,
	  value: {}
	};
	var _availableControllers = {
	  writable: true,
	  value: []
	};

	let Base = /*#__PURE__*/function () {
	  function Base() {
	    babelHelpers.classCallCheck(this, Base);
	  }
	  babelHelpers.createClass(Base, [{
	    key: "getDeleteActionMethod",
	    value: function getDeleteActionMethod() {
	      return '';
	    }
	  }, {
	    key: "getDeleteActionCfg",
	    value: function getDeleteActionCfg(recordId, ownerTypeId, ownerId) {
	      return {
	        data: {
	          recordId,
	          ownerTypeId,
	          ownerId
	        }
	      };
	    }
	  }, {
	    key: "onInitialize",
	    value: function onInitialize(item) {}
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {}
	  }, {
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(item) {
	      return {};
	    }
	  }, {
	    key: "onAfterItemRefreshLayout",
	    value: function onAfterItemRefreshLayout(item) {}
	  }, {
	    key: "onAfterItemLayout",
	    value: function onAfterItemLayout(item, options) {}
	    /**
	     * Will be executed before item node deleted from DOM
	     * @param item
	     */
	  }, {
	    key: "onBeforeItemClearLayout",
	    value: function onBeforeItemClearLayout(item) {}
	    /**
	     * Delete timeline record action
	     *
	     * @param recordId Timeline record ID
	     * @param ownerTypeId Owner type ID
	     * @param ownerId Owner type ID
	     * @param animationCallbacks
	     *
	     * @returns {Promise}
	     *
	     * @protected
	     */
	  }, {
	    key: "runDeleteAction",
	    value: function runDeleteAction(recordId, ownerTypeId, ownerId, animationCallbacks) {
	      if (animationCallbacks.onStart) {
	        animationCallbacks.onStart();
	      }
	      return main_core.ajax.runAction(this.getDeleteActionMethod(), this.getDeleteActionCfg(recordId, ownerTypeId, ownerId)).then(() => {
	        if (animationCallbacks.onStop) {
	          animationCallbacks.onStop();
	        }
	        return true;
	      }, response => {
	        ui_notification.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });
	        if (animationCallbacks.onStop) {
	          animationCallbacks.onStop();
	        }
	        return true;
	      });
	    }
	    /**
	     * Schedule TODO activity action
	     *
	     * @param activityId Activity ID
	     * @param scheduleDate Date to use in editor
	     *
	     * @protected
	     */
	  }, {
	    key: "runScheduleAction",
	    value: function runScheduleAction(activityId, scheduleDate) {
	      var _BX$Crm, _BX$Crm$Timeline, _BX$Crm$Timeline$Menu;
	      const menuBar = (_BX$Crm = BX.Crm) === null || _BX$Crm === void 0 ? void 0 : (_BX$Crm$Timeline = _BX$Crm.Timeline) === null || _BX$Crm$Timeline === void 0 ? void 0 : (_BX$Crm$Timeline$Menu = _BX$Crm$Timeline.MenuBar) === null || _BX$Crm$Timeline$Menu === void 0 ? void 0 : _BX$Crm$Timeline$Menu.getDefault();
	      if (menuBar) {
	        menuBar.setActiveItemById('todo');
	        const todoEditor = menuBar.getItemById('todo');
	        todoEditor.focus();
	        todoEditor.setParentActivityId(activityId);
	        todoEditor.setDeadLine(scheduleDate);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return false;
	    }
	  }]);
	  return Base;
	}();

	let Item$1 = /*#__PURE__*/function () {
	  function Item() {
	    babelHelpers.classCallCheck(this, Item);
	    this._id = '';
	    this._isTerminated = false;
	    this._wrapper = null;
	  }
	  babelHelpers.createClass(Item, [{
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "_setId",
	    value: function _setId(id) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      throw new Error('Item.setData() must be overridden');
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      throw new Error('Item.layout() must be overridden');
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      const anchor = this._wrapper.previousSibling;
	      this.clearLayout();
	      this.layout({
	        anchor: anchor
	      });
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      main_core.Dom.remove(this._wrapper);
	      this._wrapper = undefined;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      this.clearLayout();
	    }
	  }, {
	    key: "getWrapper",
	    value: function getWrapper() {
	      return this._wrapper;
	    }
	  }, {
	    key: "setWrapper",
	    value: function setWrapper(wrapper) {
	      this._wrapper = wrapper;
	    }
	  }, {
	    key: "addWrapperClass",
	    value: function addWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }
	      main_core.Dom.addClass(this._wrapper, className);
	      if (main_core.Type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(this.removeWrapperClass.bind(this, className), timeout);
	      }
	    }
	  }, {
	    key: "removeWrapperClass",
	    value: function removeWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }
	      main_core.Dom.removeClass(this._wrapper, className);
	      if (main_core.Type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(this.addWrapperClass.bind(this, className), timeout);
	      }
	    }
	  }, {
	    key: "isTerminated",
	    value: function isTerminated() {
	      return this._isTerminated;
	    }
	  }, {
	    key: "markAsTerminated",
	    value: function markAsTerminated(terminated) {
	      terminated = !!terminated;
	      if (this._isTerminated === terminated) {
	        return;
	      }
	      this._isTerminated = terminated;
	      if (!this._wrapper) {
	        return;
	      }
	      if (terminated) {
	        main_core.Dom.addClass(this._wrapper, 'crm-entity-stream-section-last');
	      } else {
	        main_core.Dom.removeClass(this._wrapper, 'crm-entity-stream-section-last');
	      }
	    }
	  }, {
	    key: "getAssociatedEntityTypeId",
	    value: function getAssociatedEntityTypeId() {
	      return null;
	    }
	  }, {
	    key: "getAssociatedEntityId",
	    value: function getAssociatedEntityId() {
	      return null;
	    }
	  }]);
	  return Item;
	}();

	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _layout = /*#__PURE__*/new WeakMap();
	let Layout = /*#__PURE__*/function () {
	  function Layout(layout) {
	    babelHelpers.classCallCheck(this, Layout);
	    _classPrivateFieldInitSpec$3(this, _layout, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _layout, layout);
	  }
	  babelHelpers.createClass(Layout, [{
	    key: "asPlainObject",
	    value: function asPlainObject() {
	      return main_core.Runtime.clone(babelHelpers.classPrivateFieldGet(this, _layout));
	    }
	  }, {
	    key: "getFooterMenuItemById",
	    value: function getFooterMenuItemById(id) {
	      var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	      const items = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _layout)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.footer) === null || _babelHelpers$classPr3 === void 0 ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.menu) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.items) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : {};
	      return items.hasOwnProperty(id) ? items.id : null;
	    }
	  }, {
	    key: "addFooterMenuItem",
	    value: function addFooterMenuItem(menuItem) {
	      babelHelpers.classPrivateFieldGet(this, _layout).footer = babelHelpers.classPrivateFieldGet(this, _layout).footer || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu = babelHelpers.classPrivateFieldGet(this, _layout).footer.menu || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items = babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items[menuItem.id] = menuItem;
	    }
	  }]);
	  return Layout;
	}();

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _container = /*#__PURE__*/new WeakMap();
	var _itemClassName = /*#__PURE__*/new WeakMap();
	var _type$1 = /*#__PURE__*/new WeakMap();
	var _dataPayload = /*#__PURE__*/new WeakMap();
	var _timelineId = /*#__PURE__*/new WeakMap();
	var _timestamp = /*#__PURE__*/new WeakMap();
	var _sort = /*#__PURE__*/new WeakMap();
	var _useShortTimeFormat = /*#__PURE__*/new WeakMap();
	var _isReadOnly = /*#__PURE__*/new WeakMap();
	var _currentUser = /*#__PURE__*/new WeakMap();
	var _ownerTypeId = /*#__PURE__*/new WeakMap();
	var _ownerId = /*#__PURE__*/new WeakMap();
	var _controllers = /*#__PURE__*/new WeakMap();
	var _layoutComponent = /*#__PURE__*/new WeakMap();
	var _layoutApp = /*#__PURE__*/new WeakMap();
	var _layout$1 = /*#__PURE__*/new WeakMap();
	var _streamType = /*#__PURE__*/new WeakMap();
	var _color = /*#__PURE__*/new WeakMap();
	var _useAnchorNextSibling = /*#__PURE__*/new WeakSet();
	var _initLayoutApp = /*#__PURE__*/new WeakSet();
	var _getLayoutAppProps = /*#__PURE__*/new WeakSet();
	var _onLayoutAppAction = /*#__PURE__*/new WeakSet();
	var _getContentBlockComponents = /*#__PURE__*/new WeakSet();
	let ConfigurableItem = /*#__PURE__*/function (_TimelineItem) {
	  babelHelpers.inherits(ConfigurableItem, _TimelineItem);
	  function ConfigurableItem(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, ConfigurableItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ConfigurableItem).call(this, ...args));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getContentBlockComponents);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _onLayoutAppAction);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getLayoutAppProps);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _initLayoutApp);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _useAnchorNextSibling);
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _itemClassName, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _type$1, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _dataPayload, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _timelineId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _timestamp, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _sort, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _useShortTimeFormat, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _isReadOnly, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _currentUser, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _ownerTypeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _ownerId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _controllers, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layoutComponent, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layoutApp, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layout$1, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _streamType, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _color, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(ConfigurableItem, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._setId(id);
	      settings = settings || {};
	      babelHelpers.classPrivateFieldSet(this, _timelineId, settings.timelineId || '');
	      this.setContainer(settings.container || null);
	      babelHelpers.classPrivateFieldSet(this, _itemClassName, settings.itemClassName || '');
	      if (main_core.Type.isPlainObject(settings.data)) {
	        this.setData(settings.data);
	        babelHelpers.classPrivateFieldSet(this, _useShortTimeFormat, settings.useShortTimeFormat || false);
	        babelHelpers.classPrivateFieldSet(this, _isReadOnly, settings.isReadOnly || false);
	        babelHelpers.classPrivateFieldSet(this, _currentUser, settings.currentUser || null);
	        babelHelpers.classPrivateFieldSet(this, _ownerTypeId, settings.ownerTypeId);
	        babelHelpers.classPrivateFieldSet(this, _ownerId, settings.ownerId);
	        babelHelpers.classPrivateFieldSet(this, _streamType, settings.streamType || crm_timeline_item.StreamType.history);
	      }
	      babelHelpers.classPrivateFieldSet(this, _controllers, ControllerManager.getInstance(babelHelpers.classPrivateFieldGet(this, _timelineId)).getItemControllers(this));
	    }
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      var _data$color;
	      babelHelpers.classPrivateFieldSet(this, _type$1, data.type || null);
	      babelHelpers.classPrivateFieldSet(this, _timestamp, data.timestamp || null);
	      babelHelpers.classPrivateFieldSet(this, _sort, data.sort || []);
	      babelHelpers.classPrivateFieldSet(this, _layout$1, new Layout(data.layout || {}));
	      babelHelpers.classPrivateFieldSet(this, _dataPayload, data.payload || {});
	      babelHelpers.classPrivateFieldSet(this, _color, (_data$color = data.color) !== null && _data$color !== void 0 ? _data$color : null);
	    }
	  }, {
	    key: "getColor",
	    value: function getColor() {
	      return babelHelpers.classPrivateFieldGet(this, _color);
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _layout$1);
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return babelHelpers.classPrivateFieldGet(this, _type$1);
	    }
	  }, {
	    key: "getDataPayload",
	    value: function getDataPayload() {
	      return babelHelpers.classPrivateFieldGet(this, _dataPayload);
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      this.setWrapper(main_core.Dom.create({
	        tag: 'div',
	        attrs: {
	          className: babelHelpers.classPrivateFieldGet(this, _itemClassName)
	        }
	      }));
	      this.initLayoutApp(options);
	    }
	  }, {
	    key: "initWrapper",
	    value: function initWrapper() {
	      this.setWrapper(main_core.Dom.create({
	        tag: 'div',
	        attrs: {
	          className: babelHelpers.classPrivateFieldGet(this, _itemClassName)
	        }
	      }));
	      return this._wrapper;
	    }
	  }, {
	    key: "initLayoutApp",
	    value: function initLayoutApp(options) {
	      _classPrivateMethodGet$2(this, _initLayoutApp, _initLayoutApp2).call(this);
	      if (this.needBindToContainer(options)) {
	        const bindTo = this.getBindToNode(options);
	        if (bindTo && !_classPrivateMethodGet$2(this, _useAnchorNextSibling, _useAnchorNextSibling2).call(this, options)) {
	          main_core.Dom.insertBefore(this.getWrapper(), bindTo);
	        } else if (bindTo && bindTo.nextSibling) {
	          main_core.Dom.insertBefore(this.getWrapper(), bindTo.nextSibling);
	        } else {
	          main_core.Dom.append(this.getWrapper(), babelHelpers.classPrivateFieldGet(this, _container));
	        }
	      }
	      for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	        controller.onAfterItemLayout(this, options);
	      }
	    }
	  }, {
	    key: "needBindToContainer",
	    value: function needBindToContainer(options) {
	      if (main_core.Type.isPlainObject(options)) {
	        return BX.prop.getBoolean(options, 'add', true);
	      }
	      return true;
	    }
	  }, {
	    key: "getBindToNode",
	    value: function getBindToNode(options) {
	      if (main_core.Type.isPlainObject(options)) {
	        return main_core.Type.isElementNode(options['anchor']) ? options['anchor'] : null;
	      }
	      return null;
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      // try to refresh layout via vue reactivity, if possible:
	      if (babelHelpers.classPrivateFieldGet(this, _layoutComponent)) {
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).setColor(this.getColor());
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).setLayout(this.getLayout().asPlainObject());
	        for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	          controller.onAfterItemRefreshLayout(this);
	        }
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).showLoader(false);
	      } else {
	        babelHelpers.get(babelHelpers.getPrototypeOf(ConfigurableItem.prototype), "refreshLayout", this).call(this);
	      }
	    }
	  }, {
	    key: "getLayoutComponent",
	    value: function getLayoutComponent() {
	      return babelHelpers.classPrivateFieldGet(this, _layoutComponent);
	    }
	  }, {
	    key: "forceRefreshLayout",
	    value: function forceRefreshLayout() {
	      var _this$getWrapper;
	      const bindTo = (_this$getWrapper = this.getWrapper()) === null || _this$getWrapper === void 0 ? void 0 : _this$getWrapper.nextSibling;
	      this.clearLayout();
	      this.layout({
	        anchor: bindTo,
	        useAnchorNextSibling: false
	      });
	    }
	  }, {
	    key: "getLayoutContentBlockById",
	    value: function getLayoutContentBlockById(id) {
	      var _babelHelpers$classPr;
	      return (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.getContentBlockById(id);
	    }
	  }, {
	    key: "getLogo",
	    value: function getLogo() {
	      var _babelHelpers$classPr2;
	      return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.getLogo();
	    }
	  }, {
	    key: "getLayoutFooterButtonById",
	    value: function getLayoutFooterButtonById(id) {
	      var _babelHelpers$classPr3;
	      return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.getFooterButtonById(id);
	    }
	  }, {
	    key: "getLayoutFooterMenu",
	    value: function getLayoutFooterMenu() {
	      var _babelHelpers$classPr4;
	      return (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.getFooterMenu();
	    }
	  }, {
	    key: "getLayoutHeaderChangeStreamButton",
	    value: function getLayoutHeaderChangeStreamButton() {
	      var _babelHelpers$classPr5;
	      return (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr5 === void 0 ? void 0 : _babelHelpers$classPr5.getHeaderChangeStreamButton();
	    }
	  }, {
	    key: "highlightContentBlockById",
	    value: function highlightContentBlockById(blockId, isHighlighted) {
	      var _babelHelpers$classPr6;
	      (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldGet(this, _layoutComponent)) === null || _babelHelpers$classPr6 === void 0 ? void 0 : _babelHelpers$classPr6.highlightContentBlockById(blockId, isHighlighted);
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	        controller.onBeforeItemClearLayout(this);
	      }
	      babelHelpers.classPrivateFieldGet(this, _layoutApp).unmount();
	      babelHelpers.classPrivateFieldSet(this, _layoutApp, null);
	      babelHelpers.classPrivateFieldSet(this, _layoutComponent, null);
	      babelHelpers.get(babelHelpers.getPrototypeOf(ConfigurableItem.prototype), "clearLayout", this).call(this);
	    }
	  }, {
	    key: "getCreatedDate",
	    value: function getCreatedDate() {
	      const timestamp = babelHelpers.classPrivateFieldGet(this, _timestamp) ? babelHelpers.classPrivateFieldGet(this, _timestamp) : Date.now() / 1000;
	      return BX.prop.extractDate(crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(timestamp).toUserTime().getValue());
	    }
	  }, {
	    key: "getSourceId",
	    value: function getSourceId() {
	      let id = this.getId();
	      if (!main_core.Type.isInteger(id)) {
	        // id is like ACTIVITY_12
	        id = main_core.Text.toInteger(id.replace(/^\D+/g, ''));
	      }
	      return id;
	    }
	  }, {
	    key: "setContainer",
	    value: function setContainer(container) {
	      babelHelpers.classPrivateFieldSet(this, _container, container);
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      if (!babelHelpers.classPrivateFieldGet(this, _timestamp)) {
	        return null;
	      }
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(babelHelpers.classPrivateFieldGet(this, _timestamp)).toUserTime().getValue();
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return babelHelpers.classPrivateFieldGet(this, _sort);
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return babelHelpers.classPrivateFieldGet(this, _isReadOnly);
	    }
	  }, {
	    key: "getCurrentUser",
	    value: function getCurrentUser() {
	      return babelHelpers.classPrivateFieldGet(this, _currentUser);
	    }
	  }, {
	    key: "clone",
	    value: function clone() {
	      return ConfigurableItem.create(this.getId(), {
	        timelineId: babelHelpers.classPrivateFieldGet(this, _timelineId),
	        container: this.getContainer(),
	        itemClassName: babelHelpers.classPrivateFieldGet(this, _itemClassName),
	        useShortTimeFormat: babelHelpers.classPrivateFieldGet(this, _useShortTimeFormat),
	        isReadOnly: babelHelpers.classPrivateFieldGet(this, _isReadOnly),
	        currentUser: babelHelpers.classPrivateFieldGet(this, _currentUser),
	        streamType: babelHelpers.classPrivateFieldGet(this, _streamType),
	        data: {
	          type: babelHelpers.classPrivateFieldGet(this, _type$1),
	          timestamp: babelHelpers.classPrivateFieldGet(this, _timestamp),
	          sort: babelHelpers.classPrivateFieldGet(this, _sort),
	          layout: this.getLayout().asPlainObject()
	        }
	      });
	    }
	  }, {
	    key: "reloadFromServer",
	    value: function reloadFromServer(forceRefreshLayout = false) {
	      const data = {
	        ownerTypeId: babelHelpers.classPrivateFieldGet(this, _ownerTypeId),
	        ownerId: babelHelpers.classPrivateFieldGet(this, _ownerId)
	      };
	      if (babelHelpers.classPrivateFieldGet(this, _streamType) === crm_timeline_item.StreamType.history || babelHelpers.classPrivateFieldGet(this, _streamType) === crm_timeline_item.StreamType.pinned) {
	        data.historyIds = [this.getId()];
	      } else if (babelHelpers.classPrivateFieldGet(this, _streamType) === crm_timeline_item.StreamType.scheduled) {
	        data.activityIds = [this.getId()];
	      } else {
	        throw new Error('Wrong stream type');
	      }
	      return main_core.ajax.runAction('crm.timeline.item.load', {
	        data
	      }).then(response => {
	        Object.values(response.data).forEach(item => {
	          if (item.id === this.getId()) {
	            this.setData(item);
	            if (forceRefreshLayout) {
	              this.forceRefreshLayout();
	            } else {
	              this.refreshLayout();
	            }
	          }
	        });
	        return true;
	      }).catch(err => {
	        console.error(err);
	        return true;
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ConfigurableItem();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ConfigurableItem;
	}(Item$1);
	function _useAnchorNextSibling2(options) {
	  if (main_core.Type.isPlainObject(options)) {
	    return main_core.Type.isBoolean(options['useAnchorNextSibling']) ? options['useAnchorNextSibling'] : true;
	  }
	  return true;
	}
	function _initLayoutApp2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layoutApp)) {
	    babelHelpers.classPrivateFieldSet(this, _layoutApp, ui_vue3.BitrixVue.createApp(Item, _classPrivateMethodGet$2(this, _getLayoutAppProps, _getLayoutAppProps2).call(this)));
	    const contentBlockComponents = _classPrivateMethodGet$2(this, _getContentBlockComponents, _getContentBlockComponents2).call(this);
	    for (const componentName in contentBlockComponents) {
	      babelHelpers.classPrivateFieldGet(this, _layoutApp).component(componentName, contentBlockComponents[componentName]);
	    }
	    babelHelpers.classPrivateFieldSet(this, _layoutComponent, babelHelpers.classPrivateFieldGet(this, _layoutApp).mount(this.getWrapper()));
	  }
	}
	function _getLayoutAppProps2() {
	  return {
	    initialLayout: this.getLayout().asPlainObject(),
	    initialColor: babelHelpers.classPrivateFieldGet(this, _streamType) === crm_timeline_item.StreamType.scheduled ? babelHelpers.classPrivateFieldGet(this, _color) : null,
	    id: String(this.getId()),
	    useShortTimeFormat: babelHelpers.classPrivateFieldGet(this, _useShortTimeFormat),
	    isReadOnly: this.isReadOnly(),
	    currentUser: this.getCurrentUser(),
	    streamType: babelHelpers.classPrivateFieldGet(this, _streamType),
	    onAction: _classPrivateMethodGet$2(this, _onLayoutAppAction, _onLayoutAppAction2).bind(this)
	  };
	}
	function _onLayoutAppAction2(eventData) {
	  for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	    controller.onItemAction(this, eventData);
	  }
	}
	function _getContentBlockComponents2() {
	  let components = {};
	  for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	    components = Object.assign(components, controller.getContentBlockComponents(this));
	  }
	  return components;
	}

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$6(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$5(obj, privateMap, value) { _checkPrivateRedeclaration$6(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const ALLOWED_MOVE_TO_ITEM_TYPES = ['Activity:Call', 'Activity:Email', 'Activity:OpenLine'];
	var _moveToSelectorDialog = /*#__PURE__*/new WeakMap();
	var _viewActivity = /*#__PURE__*/new WeakSet();
	var _editActivity = /*#__PURE__*/new WeakSet();
	var _showMoveToSelectorDialog = /*#__PURE__*/new WeakSet();
	var _getActivityEditor = /*#__PURE__*/new WeakSet();
	var _createSelectorDialog = /*#__PURE__*/new WeakSet();
	var _runMoveAction = /*#__PURE__*/new WeakSet();
	var _filterRelated = /*#__PURE__*/new WeakSet();
	let Activity = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Activity, _Base);
	  function Activity(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Activity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Activity).call(this, ...args));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _filterRelated);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _runMoveAction);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _createSelectorDialog);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getActivityEditor);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _showMoveToSelectorDialog);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _editActivity);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _viewActivity);
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _moveToSelectorDialog, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Activity, [{
	    key: "getDeleteActionMethod",
	    value: function getDeleteActionMethod() {
	      return 'crm.timeline.activity.delete';
	    }
	  }, {
	    key: "getMoveActionMethod",
	    value: function getMoveActionMethod() {
	      return 'crm.activity.binding.move';
	    }
	  }, {
	    key: "getDeleteTagActionMethod",
	    value: function getDeleteTagActionMethod() {
	      return 'crm.timeline.activity.deleteTag';
	    }
	  }, {
	    key: "getDeleteActionCfg",
	    value: function getDeleteActionCfg(recordId, ownerTypeId, ownerId) {
	      return {
	        data: {
	          activityId: recordId,
	          ownerTypeId,
	          ownerId
	        }
	      };
	    }
	  }, {
	    key: "runDeleteTagAction",
	    value: function runDeleteTagAction(recordId, ownerTypeId, ownerId) {
	      const deleteTagActionCfg = {
	        data: {
	          activityId: recordId,
	          ownerTypeId,
	          ownerId
	        }
	      };
	      return main_core.ajax.runAction(this.getDeleteTagActionMethod(), deleteTagActionCfg).then(() => {
	        return true;
	      }, response => {
	        ui_notification.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });
	        return true;
	      });
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Activity:Edit' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$3(this, _editActivity, _editActivity2).call(this, actionData.activityId);
	      }
	      if (action === 'Activity:MoveTo' && main_core.Type.isPlainObject(actionData)) {
	        _classPrivateMethodGet$3(this, _showMoveToSelectorDialog, _showMoveToSelectorDialog2).call(this, item, actionData);
	      }
	      if (action === 'Activity:View' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$3(this, _viewActivity, _viewActivity2).call(this, actionData.activityId);
	      }
	      if (action === 'Activity:Delete' && actionData && actionData.activityId) {
	        var _actionData$confirmat;
	        const confirmationText = (_actionData$confirmat = actionData.confirmationText) !== null && _actionData$confirmat !== void 0 ? _actionData$confirmat : '';
	        if (confirmationText) {
	          ui_dialogs_messagebox.MessageBox.show({
	            message: main_core.Text.encode(confirmationText),
	            modal: true,
	            buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	            onYes: () => {
	              return this.runDeleteAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
	            },
	            onNo: messageBox => {
	              messageBox.close();
	            }
	          });
	        } else {
	          this.runDeleteAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
	        }
	      }
	      if (action === 'Activity:DeleteTag' && actionData && actionData.activityId) {
	        var _actionData$confirmat2;
	        const confirmationText = (_actionData$confirmat2 = actionData.confirmationText) !== null && _actionData$confirmat2 !== void 0 ? _actionData$confirmat2 : '';
	        if (confirmationText) {
	          ui_dialogs_messagebox.MessageBox.show({
	            message: main_core.Text.encode(confirmationText),
	            modal: true,
	            buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	            yesCaption: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_TODO_DELETE_TAG_CONFIRM_YES_CAPTION'),
	            onYes: () => {
	              return this.runDeleteTagAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
	            },
	            onCancel: messageBox => {
	              messageBox.close();
	            }
	          });
	        } else {
	          this.runDeleteTagAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
	        }
	      }
	      if (action === 'Activity:FilterRelated' && main_core.Type.isPlainObject(actionData)) {
	        _classPrivateMethodGet$3(this, _filterRelated, _filterRelated2).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      const itemType = item.getType();
	      return itemType.indexOf('Activity:') === 0 // for items with type started from `Activity:`
	      || itemType === 'TodoCreated' // TodoCreated can contain link to activity
	      ;
	    }
	  }]);
	  return Activity;
	}(Base);
	function _viewActivity2(id) {
	  const editor = _classPrivateMethodGet$3(this, _getActivityEditor, _getActivityEditor2).call(this);
	  if (editor && id) {
	    editor.viewActivity(id);
	  }
	}
	function _editActivity2(id) {
	  const editor = _classPrivateMethodGet$3(this, _getActivityEditor, _getActivityEditor2).call(this);
	  if (editor && id) {
	    editor.editActivity(id);
	  }
	}
	function _showMoveToSelectorDialog2(itemElement, actionData) {
	  if (!ALLOWED_MOVE_TO_ITEM_TYPES.includes(itemElement.getType())) {
	    // eslint-disable-next-line no-console
	    console.warn('Move to action provided only for following item types:', ALLOWED_MOVE_TO_ITEM_TYPES);
	    return;
	  }
	  const isValidParams = main_core.Type.isNumber(actionData.activityId) && main_core.Type.isNumber(actionData.ownerId) && main_core.Type.isNumber(actionData.ownerTypeId);
	  if (!isValidParams) {
	    throw new TypeError('Invalid actionData parameters');
	  }
	  const element = itemElement.getLayoutFooterMenu().$el;
	  if (!main_core.Type.isDomNode(element)) {
	    throw new ReferenceError('Selector dialog target element must be a DOM node');
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog)) {
	    _classPrivateMethodGet$3(this, _createSelectorDialog, _createSelectorDialog2).call(this, element, actionData);
	  }
	  babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog).show();
	}
	function _getActivityEditor2() {
	  return BX.CrmActivityEditor.getDefault();
	}
	function _createSelectorDialog2(dialogTargetElement, actionData) {
	  const applyButton = new ui_buttons.ApplyButton({
	    color: ui_buttons.ButtonColor.PRIMARY,
	    size: ui_buttons.ButtonSize.SMALL,
	    round: true,
	    onclick: () => {
	      _classPrivateMethodGet$3(this, _runMoveAction, _runMoveAction2).call(this, actionData.activityId, actionData.ownerTypeId, actionData.ownerId, targetItem);
	      babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog).hide();
	    }
	  });
	  const cancelButton = new ui_buttons.CancelButton({
	    size: ui_buttons.ButtonSize.SMALL,
	    round: true,
	    onclick: () => {
	      targetItem = null;
	      babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog).deselectAll();
	      babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog).hide();
	    }
	  });
	  let targetItem = null;
	  let dialogEntityId = BX.CrmEntityType.resolveName(actionData.ownerTypeId);
	  if (BX.CrmEntityType.isDynamicTypeByTypeId(actionData.ownerTypeId)) {
	    dialogEntityId = BX.CrmEntityType.names.dynamic;
	  }
	  babelHelpers.classPrivateFieldSet(this, _moveToSelectorDialog, new ui_entitySelector.Dialog({
	    targetNode: dialogTargetElement,
	    enableSearch: true,
	    context: `CRM-TIMELINE-MOVE-ACTIVITY-ENTITY-SELECTOR-${actionData.ownerTypeId}`,
	    tagSelectorOptions: {
	      textBoxWidth: '50%'
	    },
	    entities: [{
	      id: dialogEntityId,
	      dynamicLoad: true,
	      dynamicSearch: true,
	      options: {
	        ownerId: actionData.ownerId,
	        categoryId: actionData.categoryId,
	        showEntityTypeNameInHeader: true,
	        hideClosedItems: true,
	        excludeMyCompany: true,
	        entityTypeId: actionData.ownerTypeId // for 'dynamic' types
	      }
	    }],

	    events: {
	      'Item:onSelect': event => {
	        const {
	          item
	        } = event.getData();
	        if (item) {
	          targetItem = item;
	          babelHelpers.classPrivateFieldGet(this, _moveToSelectorDialog).getSelectedItems().forEach(row => {
	            if (row.getEntityId() === targetItem.getEntityId() && main_core.Text.toInteger(row.getId()) !== main_core.Text.toInteger(targetItem.getId())) {
	              row.deselect();
	            }
	          });
	          applyButton.setDisabled(false);
	        }
	      },
	      'Item:onDeselect': () => applyButton.setDisabled(true)
	    },
	    footer: [applyButton.setDisabled(true).render(), cancelButton.render()],
	    footerOptions: {
	      containerStyles: {
	        display: 'flex',
	        'justify-content': 'center'
	      }
	    }
	  }));
	}
	function _runMoveAction2(activityId, sourceEntityTypeId, sourceEntityId, targetItem) {
	  if (!targetItem) {
	    throw new ReferenceError('Target item is not defined');
	  }
	  const targetEntityTypeId = BX.CrmEntityType.resolveId(targetItem.getEntityId());
	  const targetEntityId = targetItem.getId();
	  if (targetEntityTypeId <= 0 || targetEntityId <= 0) {
	    throw new Error('Target entity in not valid');
	  }
	  if (main_core.Text.toInteger(targetEntityTypeId) !== main_core.Text.toInteger(sourceEntityTypeId)) {
	    throw new Error('Source and target entity types are not equal');
	  }
	  const data = {
	    activityId,
	    sourceEntityTypeId,
	    sourceEntityId,
	    targetEntityTypeId,
	    targetEntityId
	  };
	  main_core.ajax.runAction(this.getMoveActionMethod(), {
	    data
	  }).catch(response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    throw response;
	  });
	}
	function _filterRelated2(actionData) {
	  if (!(main_core.Type.isNumber(actionData.activityId) && main_core.Type.isStringFilled(actionData.activityLabel) && main_core.Type.isStringFilled(actionData.filterId))) {
	    return;
	  }
	  const filterManager = BX.Main.filterManager.getById(actionData.filterId);
	  if (!filterManager) {
	    return;
	  }
	  const filterApi = filterManager.getApi();
	  const fields = {
	    ACTIVITY: actionData.activityId,
	    ACTIVITY_label: actionData.activityLabel
	  };
	  filterApi.extendFilter(fields, true);
	  BX.CrmTimelineManager.getDefault().getHistory().showFilter();
	}

	var AddressBlock = {
	  props: {
	    addressFormatted: String
	  },
	  mounted() {
	    void this.$nextTick(() => {
	      this.renderAddressWidget();
	    });
	  },
	  methods: {
	    renderAddressWidget() {
	      const settings = main_core.Extension.getSettings('crm.timeline.item');
	      if (!settings.hasLocationModule) {
	        return;
	      }
	      const widgetFactory = new BX.Location.Widget.Factory();
	      const format = new location_core.Format(JSON.parse(main_core.Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_FORMAT')));
	      const address = new location_core.Address({
	        languageId: format.languageId
	      });
	      address.setFieldValue(format.fieldForUnRecognized, this.addressFormatted);
	      const addressWidget = widgetFactory.createAddressWidget({
	        address,
	        mode: location_core.ControlMode.view
	      });
	      const addressWidgetParams = {
	        mode: location_core.ControlMode.view,
	        mapBindElement: this.$refs.mapBindElement,
	        controlWrapper: this.$refs.controlWrapper
	      };
	      addressWidget.render(addressWidgetParams);
	    }
	  },
	  template: `
		<div class="crm-timeline__text-block crm-timeline__address-block">
			<div ref="mapBindElement">
				<div ref="controlWrapper" class="crm-timeline__address-block-address-wrapper">
					<span 
						:title="addressFormatted"
						class="ui-link ui-link-dark ui-link-dotted"
					>
						{{addressFormatted}}
					</span>
				</div>
			</div>
		</div>
	`
	};

	let ClientMark = function ClientMark() {
	  babelHelpers.classCallCheck(this, ClientMark);
	};
	babelHelpers.defineProperty(ClientMark, "POSITIVE", 'positive');
	babelHelpers.defineProperty(ClientMark, "NEUTRAL", 'neutral');
	babelHelpers.defineProperty(ClientMark, "NEGATIVE", 'negative');

	const ClientMark$1 = {
	  props: {
	    mark: {
	      type: String,
	      required: false,
	      default: ClientMark.POSITIVE
	    },
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__client-mark': true,
	        '--positive-mark': this.mark === ClientMark.POSITIVE,
	        '--neutral-mark': this.mark === ClientMark.NEUTRAL,
	        '--negative-mark': this.mark === ClientMark.NEGATIVE
	      };
	    },
	    iconClassname() {
	      return {
	        'crm-timeline__client-mark_icon': true,
	        '--flipped': this.mark === ClientMark.NEGATIVE
	      };
	    }
	  },
	  template: `
		<div :class="className">
			<i class="crm-timeline__client-mark_icon-wrapper">
				<svg :class="iconClassname" width="11.35" height="11.36" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M14.8367 6.7166L11.2351 6.71731C11.1314 6.71731 11.0313 6.65735 10.9925 6.56213C10.8303 6.15654 10.8366 5.69947 11.0144 5.29671C11.3127 4.42841 11.3466 3.49029 11.1124 2.60224C10.8676 1.9914 10.8028 0.875521 9.67347 0.82403C9.32009 0.876226 9.01184 1.08854 8.8341 1.39819C8.80235 1.45321 8.78895 1.5181 8.78895 1.58088C8.78895 1.58088 8.84085 2.6644 8.78895 3.41764C8.73706 4.17087 7.29571 6.08954 6.37945 7.30769C6.32161 7.38528 6.24049 7.43607 6.14456 7.45088C5.80104 7.50232 5.14581 7.58943 4.90962 7.62062C4.85434 7.62792 4.82201 7.69735 4.82201 7.72994C4.82201 9.2527 4.82201 11.5306 4.82201 14.5636C4.82201 14.5887 4.85234 14.6435 4.90623 14.6524C5.09126 14.6829 5.55408 14.7676 6.02112 14.9178C6.60798 15.1061 7.0968 15.5519 8.07513 15.882C8.12662 15.8997 8.18375 15.9088 8.23807 15.9088H12.8321C13.4253 15.803 13.8668 15.2959 13.8972 14.6872C13.9056 14.3479 13.8379 14.0121 13.699 13.7039C13.6792 13.6594 13.7053 13.6129 13.7533 13.6037C14.3422 13.4965 15.0786 12.3785 14.1715 11.4778C14.1476 11.4545 14.1518 11.4228 14.1842 11.4143C14.6843 11.2866 15.0674 10.8874 15.1844 10.3894C15.2296 10.199 15.2134 10.0022 15.1569 9.81524C15.0906 9.59235 14.9848 9.38356 14.8445 9.19735C14.8092 9.15079 14.8289 9.09013 14.8854 9.07179C15.3834 8.90321 15.7262 8.4271 15.7226 7.88538C15.7798 7.35424 15.353 6.71731 14.8367 6.7166ZM3.412 7.00439H0.808518C0.673089 7.00439 0.570107 7.1243 0.593384 7.25479L2.10567 15.7508C2.12895 15.8813 2.24392 15.9766 2.37865 15.9766H3.34146C3.49029 15.9766 3.61091 15.8588 3.61091 15.7135L3.63207 7.22023C3.63207 7.10102 3.53403 7.00439 3.412 7.00439Z"/>
				</svg>
			</i>
			<span class="crm-timeline__client-mark_text">{{ text }}</span>
		</div>
	`
	};

	let EditableDescriptionHeight = function EditableDescriptionHeight() {
	  babelHelpers.classCallCheck(this, EditableDescriptionHeight);
	};
	babelHelpers.defineProperty(EditableDescriptionHeight, "SHORT", 'short');
	babelHelpers.defineProperty(EditableDescriptionHeight, "LONG", 'long');

	let EditableDescriptionBackgroundColor = function EditableDescriptionBackgroundColor() {
	  babelHelpers.classCallCheck(this, EditableDescriptionBackgroundColor);
	};
	babelHelpers.defineProperty(EditableDescriptionBackgroundColor, "YELLOW", 'yellow');
	babelHelpers.defineProperty(EditableDescriptionBackgroundColor, "WHITE", 'white');

	const EditableDescription = {
	  components: {
	    Button,
	    TextEditorComponent: ui_textEditor.TextEditorComponent,
	    HtmlFormatterComponent: ui_bbcode_formatter_htmlFormatter.HtmlFormatterComponent
	  },
	  props: {
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    saveAction: {
	      type: Object,
	      required: false,
	      default: null
	    },
	    editable: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    copied: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    height: {
	      type: String,
	      required: false,
	      default: EditableDescriptionHeight.SHORT
	    },
	    backgroundColor: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    copilotSettings: {
	      type: Object,
	      required: false,
	      default: []
	    }
	  },
	  beforeCreate() {
	    this.textEditor = null;
	  },
	  data() {
	    return {
	      isEdit: false,
	      isSaving: false,
	      isLongText: false,
	      isCollapsed: false,
	      bbcode: this.text,
	      isContentEmpty: main_core.Type.isString(this.text) && this.text.trim() === ''
	    };
	  },
	  inject: ['isReadOnly', 'isLogMessage'],
	  computed: {
	    className() {
	      return ['crm-timeline__editable-text', [String(this.heightClassnameModifier), String(this.bgColorClassnameModifier)], {
	        '--is-read-only': this.isLogMessage,
	        '--is-edit': this.isEdit,
	        '--is-long': this.isLongText,
	        '--is-expanded': this.isCollapsed || !this.isLongText
	      }];
	    },
	    heightClassnameModifier() {
	      switch (this.height) {
	        case EditableDescriptionHeight.LONG:
	          return '--height-long';
	        case EditableDescriptionHeight.SHORT:
	          return '--height-short';
	        default:
	          return '--height-short';
	      }
	    },
	    bgColorClassnameModifier() {
	      switch (this.backgroundColor) {
	        case EditableDescriptionBackgroundColor.YELLOW:
	          return '--bg-color-yellow';
	        case EditableDescriptionBackgroundColor.WHITE:
	          return '--bg-color-white';
	        default:
	          return '';
	      }
	    },
	    isEditable() {
	      return this.editable && this.saveAction && !this.isReadOnly;
	    },
	    isCopied() {
	      return !this.isEdit && this.copied;
	    },
	    saveTextButtonProps() {
	      return {
	        state: this.saveTextButtonState,
	        type: ButtonType.PRIMARY,
	        title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SAVE')
	      };
	    },
	    cancelEditingButtonProps() {
	      return {
	        type: ButtonType.LIGHT,
	        title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_CANCEL'),
	        state: this.isSaving ? ButtonState.DISABLED : ButtonState.DEFAULT
	      };
	    },
	    saveTextButtonState() {
	      if (this.isContentEmpty) {
	        return ButtonState.DISABLED;
	      }
	      if (this.isSaving) {
	        return ButtonState.DISABLED;
	      }
	      return ButtonState.DEFAULT;
	    },
	    expandButtonText() {
	      return this.isCollapsed ? this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_HIDE_MSGVER_1') : this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SHOW_MSGVER_1');
	    },
	    isEditButtonVisible() {
	      return !(this.isReadOnly || this.isEdit);
	    }
	  },
	  methods: {
	    startEditing() {
	      this.isEdit = true;
	      this.isCollapsed = true;
	      this.$nextTick(() => {
	        this.getTextEditor().focus(null, {
	          defaultSelection: 'rootEnd'
	        });
	      });
	      this.emitEvent('EditableDescription:StartEdit');
	    },
	    emitEvent(eventName) {
	      const action = new Action({
	        type: 'jsEvent',
	        value: eventName
	      });
	      action.execute(this);
	    },
	    adjustHeight(elem) {
	      elem.style.height = 0;
	      elem.style.height = `${elem.scrollHeight}px`;
	    },
	    saveText() {
	      if (this.saveTextButtonState === ButtonState.DISABLED || this.saveTextButtonState === ButtonState.LOADING || !this.isEdit) {
	        return;
	      }
	      const encodedTrimText = this.getTextEditor().getText().trim();
	      if (encodedTrimText === this.bbcode) {
	        this.isEdit = false;
	        this.emitEvent('EditableDescription:FinishEdit');
	        return;
	      }
	      this.isSaving = true;

	      // eslint-disable-next-line promise/catch-or-return
	      this.executeSaveAction(encodedTrimText).then(() => {
	        this.isEdit = false;
	        this.bbcode = encodedTrimText;
	        this.$nextTick(() => {
	          this.isLongText = this.checkIsLongText();
	        });
	        this.emitEvent('EditableDescription:FinishEdit');
	      }).finally(() => {
	        this.isSaving = false;
	      });
	    },
	    executeSaveAction(text) {
	      var _actionDescription$ac;
	      if (!this.saveAction) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.saveAction);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = text;
	      const action = new Action(actionDescription);

	      // eslint-disable-next-line consistent-return
	      return action.execute(this);
	    },
	    cancelEditing() {
	      if (!this.isEdit || this.isSaving) {
	        return;
	      }
	      this.isEdit = false;
	      this.emitEvent('EditableDescription:FinishEdit');
	    },
	    clearText() {
	      if (this.isSaving) {
	        return;
	      }
	      this.getTextEditor().clear();
	      this.getTextEditor().focus(null, {
	        defaultSelection: 'rootEnd'
	      });
	    },
	    copyText() {
	      const selection = window.getSelection();
	      selection.removeAllRanges();
	      const range = document.createRange();
	      const referenceNode = this.$refs.text;
	      range.selectNodeContents(referenceNode);
	      selection.addRange(range);
	      let isSuccess = false;
	      try {
	        isSuccess = document.execCommand('copy');
	      } catch (err) {
	        // just in case
	      }
	      selection.removeAllRanges();
	      if (isSuccess) {
	        new main_popup.Popup({
	          id: `copyTextHint_${main_core.Text.getRandom(8)}`,
	          content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_TEXT_IS_COPIED'),
	          bindElement: this.$refs.copyTextBtn,
	          darkMode: true,
	          autoHide: true,
	          events: {
	            onAfterPopupShow() {
	              setTimeout(() => {
	                this.close();
	              }, 2000);
	            }
	          }
	        }).show();
	      }
	    },
	    toggleIsCollapsed() {
	      this.isCollapsed = !this.isCollapsed;
	    },
	    checkIsLongText() {
	      var _this$$refs$rootEleme;
	      const textBlock = this.$refs.text;
	      if (!textBlock) {
	        return false;
	      }
	      const textBlockMaxHeightStyle = window.getComputedStyle(textBlock).getPropertyValue('--crm-timeline__editable-text_max-height');
	      const textBlockMaxHeight = parseFloat(textBlockMaxHeightStyle.slice(0, -2));
	      const parentComputedStyles = this.$refs.rootElement ? window.getComputedStyle(this.$refs.rootElement) : {};

	      // eslint-disable-next-line no-unsafe-optional-chaining
	      const parentHeight = ((_this$$refs$rootEleme = this.$refs.rootElement) === null || _this$$refs$rootEleme === void 0 ? void 0 : _this$$refs$rootEleme.offsetHeight) - parseFloat(parentComputedStyles.paddingTop) - parseFloat(parentComputedStyles.paddingBottom);
	      return parentHeight > textBlockMaxHeight;
	    },
	    isInViewport() {
	      const rect = this.$el.getBoundingClientRect();
	      return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
	    },
	    getTextEditor() {
	      if (this.textEditor !== null) {
	        return this.textEditor;
	      }
	      this.textEditor = new ui_textEditor.BasicEditor({
	        removePlugins: ['BlockToolbar'],
	        maxHeight: 600,
	        content: this.bbcode,
	        paragraphPlaceholder: main_core.Loc.getMessage(main_core.Type.isPlainObject(this.copilotSettings) ? 'CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_PLACEHOLDER_WITH_COPILOT' : null),
	        toolbar: [],
	        floatingToolbar: ['bold', 'italic', 'underline', 'strikethrough', '|', 'link', 'copilot'],
	        visualOptions: {
	          colorBackground: 'transparent',
	          borderWidth: '0px',
	          blockSpaceInline: '0px',
	          blockSpaceStack: '0px'
	        },
	        copilot: {
	          copilotOptions: main_core.Type.isPlainObject(this.copilotSettings) ? this.copilotSettings : null,
	          triggerBySpace: true
	        },
	        events: {
	          onMetaEnter: () => {
	            this.saveText();
	          },
	          onEscape: () => {
	            this.cancelEditing();
	          },
	          onEmptyContentToggle: event => {
	            this.isContentEmpty = event.getData().isEmpty;
	          }
	        }
	      });
	      return this.textEditor;
	    }
	  },
	  watch: {
	    text(newTextValue) {
	      this.bbcode = newTextValue;
	      this.$nextTick(() => {
	        this.isLongText = this.checkIsLongText();
	      });
	    },
	    isCollapsed(isCollapsed) {
	      if (isCollapsed === false && this.isInViewport() === false) {
	        requestAnimationFrame(() => {
	          this.$el.scrollIntoView({
	            behavior: 'smooth',
	            block: 'center'
	          });
	        });
	      }
	    },
	    isSaving(value) {
	      if (this.textEditor !== null)
	        // CommentContent uses this method as well
	        {
	          this.getTextEditor().setEditable(!value);
	        }
	    },
	    isEdit(value) {
	      if (value === false && this.textEditor !== null) {
	        this.textEditor.destroy();
	        this.textEditor = null;
	      }
	    }
	  },
	  mounted() {
	    this.$nextTick(() => {
	      this.isLongText = this.checkIsLongText();
	    });
	  },
	  template: `
		<div class="crm-timeline__editable-text_wrapper">
			<div ref="rootElement" :class="className">
				<button
					v-if="this.isCopied"
					ref="copyTextBtn"
					@click="copyText"
					class="crm-timeline__text_copy-btn"
				>
					<i class="crm-timeline__editable-text_fixed-icon --copy"></i>
				</button>
				<button
					v-if="isEdit && isEditable"
					:disabled="isSaving"
					@click="clearText"
					class="crm-timeline__editable-text_clear-btn"
				>
					<i class="crm-timeline__editable-text_fixed-icon --clear"></i>
				</button>
				<button
					v-if="!isEdit && isEditable && isEditButtonVisible"
					:disabled="isSaving"
					@click="startEditing"
					class="crm-timeline__editable-text_edit-btn"
				>
					<i class="crm-timeline__editable-text_edit-icon"></i>
				</button>
				<div class="crm-timeline__editable-text_inner">
					<div class="crm-timeline__editable-text_content">
						<TextEditorComponent
							v-if="isEdit"
							:editor-instance="this.getTextEditor()"
						/>
						<span
							v-else
							ref="text"
							class="crm-timeline__editable-text_text"
						>
							<HtmlFormatterComponent :bbcode="bbcode" />
						</span>
					</div>
					<div
						v-if="isEdit"
						class="crm-timeline__editable-text_actions"
					>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="saveTextButtonProps"
								@click="saveText"
							/>
						</div>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="cancelEditingButtonProps"
								@click="cancelEditing"
							/>
						</div>
					</div>
				</div>
				<button
					v-if="isLongText && !isEdit"
					@click="toggleIsCollapsed"
					class="crm-timeline__editable-text_collapse-btn"
				>
					{{ expandButtonText }}
				</button>
			</div>
		</div>
	`
	};

	const TYPE_LOAD_FILES_BLOCK = 1;
	const TYPE_LOAD_TEXT_CONTENT = 2;

	/**
	 * @extends EditableDescription
	 */
	var CommentContent = ui_vue3.BitrixVue.cloneComponent(EditableDescription, {
	  props: {
	    filesCount: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    hasInlineFiles: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    loadAction: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    }
	  },
	  data() {
	    return {
	      ...this.parentData(),
	      value: this.text,
	      oldValue: this.text,
	      isTextLoaded: false,
	      isTextChanged: false,
	      isMoving: false,
	      isFilesBlockDisplayed: this.filesCount > 0,
	      filesHtmlBlock: null,
	      loader: Object.freeze(null),
	      editor: Object.freeze(null)
	    };
	  },
	  computed: {
	    textWrapperClassName() {
	      return ['crm-timeline__editable-text_content', {
	        '--is-editor-loaded': this.isEdit
	      }];
	    }
	  },
	  methods: {
	    startEditing() {
	      this.isEdit = true;
	      this.isCollapsed = true;
	      this.$nextTick(() => {
	        this.editor.show(this.$refs.editor);
	      });
	      this.emitEvent('Comment:StartEdit');
	    },
	    cancelEditing() {
	      if (!this.isEdit || this.isSaving) {
	        return;
	      }
	      this.value = this.oldValue;
	      this.isEdit = false;
	      if (this.filesHtmlBlock) {
	        void main_core.Runtime.html(this.$refs.files, this.filesHtmlBlock).then(() => {
	          this.registerImages(this.$refs.files);
	          BX.LazyLoad.showImages();
	          this.emitEvent('Comment:FinishEdit');
	        });
	      } else {
	        this.emitEvent('Comment:FinishEdit');
	      }
	    },
	    toggleIsCollapsed() {
	      this.parentToggleIsCollapsed();
	      if (!this.isTextLoaded) {
	        this.executeLoadAction(TYPE_LOAD_TEXT_CONTENT, this.$refs.text);
	      }
	    },
	    checkIsLongText() {
	      const textBlock = this.$refs.text;
	      if (!textBlock) {
	        return false;
	      }
	      const textBlockMaxHeightStyle = window.getComputedStyle(textBlock).getPropertyValue('--crm-timeline__editable-text_max-height');
	      const textBlockMaxHeight = parseFloat(textBlockMaxHeightStyle.slice(0, -2));
	      const root = this.filesCount > 0 ? this.$refs.rootElement : this.$refs.rootWrapperElement;
	      const parentComputedStyles = window.getComputedStyle(root);
	      const parentHeight = root.offsetHeight - parseFloat(parentComputedStyles.paddingTop) - parseFloat(parentComputedStyles.paddingBottom);
	      const isLongText = parentHeight > textBlockMaxHeight;
	      return isLongText || this.hasInlineFiles;
	    },
	    saveContent() {
	      const isSaveDisabled = this.saveTextButtonState === ButtonState.LOADING || !this.isEdit || !this.saveAction;
	      if (isSaveDisabled) {
	        return;
	      }
	      const content = this.editor.getContent();
	      if (!main_core.Type.isStringFilled(content)) {
	        return;
	      }
	      const htmlContent = this.editor.getHtmlContent();
	      const attachmentList = this.editor.getAttachments();
	      const attachmentAllowEditOptions = this.editor.getAttachmentsAllowEditOptions(attachmentList);
	      this.isSaving = true;
	      void this.executeSaveAction(content, attachmentList, attachmentAllowEditOptions).then(() => {
	        this.isEdit = false;
	        if (!this.isTextChanged) {
	          this.oldValue = htmlContent;
	          this.value = htmlContent;
	        }
	        this.$nextTick(() => {
	          this.isLongText = this.checkIsLongText();
	          this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
	        });
	        this.emitEvent('Comment:FinishEdit');
	      }).finally(() => {
	        this.isSaving = false;
	      });
	    },
	    executeSaveAction(content, attachmentList, attachmentAllowEditOptions) {
	      var _actionDescription$ac;
	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.saveAction);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.id = actionDescription.actionParams.commentId;
	      actionDescription.actionParams.fields = {
	        COMMENT: content,
	        ATTACHMENTS: attachmentList
	      };
	      if (Object.keys(attachmentAllowEditOptions).length > 0) {
	        actionDescription.actionParams.CRM_TIMELINE_DISK_ATTACHED_OBJECT_ALLOW_EDIT = attachmentAllowEditOptions;
	      }
	      const action = new Action(actionDescription);
	      return action.execute(this);
	    },
	    executeLoadAction(type, node) {
	      var _actionDescription$ac2;
	      if (this.filesCount === 0) {
	        this.filesHtmlBlock = null;
	        return;
	      }
	      if (!main_core.Type.isDomNode(node) || !this.loadAction) {
	        return;
	      }
	      const actionDescription = main_core.Runtime.clone(this.loadAction);
	      (_actionDescription$ac2 = actionDescription.actionParams) !== null && _actionDescription$ac2 !== void 0 ? _actionDescription$ac2 : actionDescription.actionParams = {};
	      actionDescription.actionParams.options = type;
	      const action = new Action(actionDescription);
	      this.showLoader(true);
	      action.execute(this).then(response => {
	        if (type === TYPE_LOAD_FILES_BLOCK) {
	          this.filesHtmlBlock = response.data.html;
	        } else if (type === TYPE_LOAD_TEXT_CONTENT) {
	          this.isTextLoaded = true;
	        }
	        void main_core.Runtime.html(node, response.data.html).then(() => {
	          this.registerImages(node);
	          BX.LazyLoad.showImages();
	          this.showLoader(false);
	        });
	      }).catch(() => {
	        if (type === TYPE_LOAD_FILES_BLOCK) {
	          this.filesHtmlBlock = null;
	        } else if (type === TYPE_LOAD_TEXT_CONTENT) {
	          this.isTextLoaded = false;
	        }
	        this.showLoader(false);
	      });
	    },
	    registerImages(node) {
	      if (!main_core.Type.isDomNode(node)) {
	        return;
	      }
	      const idsList = [];
	      const commentImages = node.querySelectorAll('[data-viewer-type="image"]');
	      const commentImagesLength = commentImages.length;
	      if (commentImagesLength > 0) {
	        for (let i = 0; i < commentImagesLength; ++i) {
	          if (main_core.Type.isDomNode(commentImages[i])) {
	            commentImages[i].id += BX.util.getRandomString(4);
	            idsList.push(commentImages[i].id);
	          }
	        }
	        if (idsList.length > 0) {
	          BX.LazyLoad.registerImages(idsList, null, {
	            dataSrcName: 'thumbSrc'
	          });
	        }
	      }
	      BX.LazyLoad.registerImages(idsList, null, {
	        dataSrcName: 'thumbSrc'
	      });
	    },
	    showLoader(showLoader) {
	      if (showLoader) {
	        if (!this.loader) {
	          this.loader = new main_loader.Loader({
	            size: 20,
	            mode: 'inline'
	          });
	        }
	        this.loader.show(this.$refs.files);
	      } else if (this.loader) {
	        this.loader.hide();
	      }
	    },
	    createEditor() {
	      this.editor = new crm_timeline_editors_commentEditor.CommentEditor(this.loadAction.actionParams.commentId);
	    },
	    setIsMoving(flag = true) {
	      this.isMoving = flag;
	    },
	    setIsFilesBlockDisplayed(flag = true) {
	      this.isFilesBlockDisplayed = flag;
	      if (this.filesHtmlBlock) {
	        void main_core.Runtime.html(this.$refs.files, this.filesHtmlBlock).then(() => {
	          this.registerImages(this.$refs.files);
	          BX.LazyLoad.showImages();
	        });
	      }
	    }
	  },
	  watch: {
	    text(newValue) {
	      this.value = newValue;
	      this.oldValue = newValue;
	      this.isTextChanged = true;
	      this.$nextTick(() => {
	        this.isLongText = this.checkIsLongText();
	        this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
	      });
	    },
	    value(newValue) {
	      if (!this.isEdit) {
	        return;
	      }
	      this.value = newValue;
	      this.oldValue = newValue;
	    },
	    filesCount(newValue) {
	      if (this.isMoving) {
	        return;
	      }
	      this.isFilesBlockDisplayed = newValue > 0;
	      this.$nextTick(() => {
	        this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
	      });
	    }
	  },
	  mounted() {
	    this.createEditor();
	    this.$nextTick(() => {
	      this.isLongText = this.checkIsLongText();
	      this.executeLoadAction(TYPE_LOAD_FILES_BLOCK, this.$refs.files);
	    });
	  },
	  updated() {
	    this.createEditor();
	  },
	  template: `
		<div ref="rootWrapperElement" class="crm-timeline__editable-text_wrapper --comment">
			<div ref="rootElement" :class="className">
				<button
					v-if="isLongText && !isEdit && isEditable && isEditButtonVisible"
					:disabled="isSaving"
					@click="startEditing"
					class="crm-timeline__editable-text_edit-btn"
				>
					<i class="crm-timeline__editable-text_edit-icon"></i>
				</button>
				<div class="crm-timeline__editable-text_inner">
					<div :class="textWrapperClassName">
						<div
							v-if="isEdit"
							ref="editor"
							:disabled="!isEdit || isSaving"
							class="crm-timeline__editable-text_editor"
						></div>
						<span 
							v-else
							ref="text"
							class="crm-timeline__editable-text_text"
							v-html="value"
						>
						</span>
						<span
							v-if="!isEdit && !isLongText && isEditable && isEditButtonVisible"
							@click="startEditing"
							class="crm-timeline__editable-text_text-edit-icon"
						>
							<span class="crm-timeline__editable-text_edit-icon"></span>
						</span>
					</div>
					<div
						v-if="isEdit"
						class="crm-timeline__editable-text_actions"
					>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="saveTextButtonProps"
								@click="saveContent"
							/>
						</div>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="cancelEditingButtonProps"
								@click="cancelEditing"
							/>
						</div>
					</div>
				</div>
				<button
					v-if="isLongText && !isEdit"
					@click="toggleIsCollapsed"
					class="crm-timeline__editable-text_collapse-btn"
				>
					{{ expandButtonText }}
				</button>
			</div>
			<div
				v-if="!isEdit && isFilesBlockDisplayed"
				ref="files"
				class="crm-timeline__comment_files_wrapper"
				:class="{'--long-comment': isLongText}"
				v-html="filesHtmlBlock"
			>
			</div>
		</div>
	`
	});

	let TextColor = function TextColor() {
	  babelHelpers.classCallCheck(this, TextColor);
	};
	babelHelpers.defineProperty(TextColor, "GREEN", 'green');
	babelHelpers.defineProperty(TextColor, "PURPLE", 'purple');
	babelHelpers.defineProperty(TextColor, "BASE_50", 'base-50');
	babelHelpers.defineProperty(TextColor, "BASE_60", 'base-60');
	babelHelpers.defineProperty(TextColor, "BASE_70", 'base-70');
	babelHelpers.defineProperty(TextColor, "BASE_90", 'base-90');

	let TextWeight = function TextWeight() {
	  babelHelpers.classCallCheck(this, TextWeight);
	};
	babelHelpers.defineProperty(TextWeight, "NORMAL", 'normal');
	babelHelpers.defineProperty(TextWeight, "MEDIUM", 'medium');
	babelHelpers.defineProperty(TextWeight, "BOLD", 'bold');

	let TextSize = function TextSize() {
	  babelHelpers.classCallCheck(this, TextSize);
	};
	babelHelpers.defineProperty(TextSize, "XS", 'xs');
	babelHelpers.defineProperty(TextSize, "SM", 'sm');
	babelHelpers.defineProperty(TextSize, "MD", 'md');

	let TextDecoration = function TextDecoration() {
	  babelHelpers.classCallCheck(this, TextDecoration);
	};
	babelHelpers.defineProperty(TextDecoration, "NONE", 'none');
	babelHelpers.defineProperty(TextDecoration, "UNDERLINE", 'underline');
	babelHelpers.defineProperty(TextDecoration, "DOTTED", 'dotted');
	babelHelpers.defineProperty(TextDecoration, "DASHED", 'dashed');

	var Text = {
	  props: {
	    value: String | Number,
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    weight: {
	      type: String,
	      required: false,
	      default: 'normal'
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'md'
	    },
	    multiline: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    decoration: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__text-block', this.colorClassname, this.weightClassname, this.sizeClassname, this.decorationClassname];
	    },
	    colorClassname() {
	      var _TextColor$upperCaseC;
	      const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
	      const color = (_TextColor$upperCaseC = TextColor[upperCaseColorProp]) !== null && _TextColor$upperCaseC !== void 0 ? _TextColor$upperCaseC : '';
	      return `--color-${color}`;
	    },
	    weightClassname() {
	      var _TextWeight$upperCase;
	      const upperCaseWeightProp = this.weight ? this.weight.toUpperCase() : '';
	      const weight = (_TextWeight$upperCase = TextWeight[upperCaseWeightProp]) !== null && _TextWeight$upperCase !== void 0 ? _TextWeight$upperCase : TextWeight.NORMAL;
	      return `--weight-${weight}`;
	    },
	    sizeClassname() {
	      var _TextSize$upperCaseSi;
	      const upperCaseSizeProp = this.size ? this.size.toUpperCase() : '';
	      const size = (_TextSize$upperCaseSi = TextSize[upperCaseSizeProp]) !== null && _TextSize$upperCaseSi !== void 0 ? _TextSize$upperCaseSi : TextSize.SM;
	      return `--size-${size}`;
	    },
	    decorationClassname() {
	      var _TextDecoration$upper;
	      const upperCaseDecorationProp = this.decoration ? this.decoration.toUpperCase() : '';
	      if (!upperCaseDecorationProp) {
	        return '';
	      }
	      const decoration = (_TextDecoration$upper = TextDecoration[upperCaseDecorationProp]) !== null && _TextDecoration$upper !== void 0 ? _TextDecoration$upper : TextDecoration.NONE;
	      return `--decoration-${decoration}`;
	    },
	    encodedText() {
	      let text = main_core.Text.encode(this.value);
	      if (this.multiline) {
	        text = text.replace(/\n/g, '<br />');
	      }
	      return text;
	    }
	  },
	  template: `
		<span
			:title="title"
			:class="className"
			v-html="encodedText"
		></span>
	`
	};

	var DateBlock = {
	  props: {
	    withTime: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    format: {
	      type: String,
	      required: false,
	      default: null
	    },
	    duration: {
	      type: Number,
	      required: false,
	      default: null
	    }
	  },
	  extends: Text,
	  methods: {
	    getFormattedDate() {
	      const datetimeConverter = this.getDatetimeConverter();
	      if (this.format) {
	        return datetimeConverter.toFormatString(this.format);
	      }
	      const options = {
	        delimiter: ', ',
	        withDayOfWeek: true,
	        withFullMonth: true
	      };
	      return this.withTime ? datetimeConverter.toDatetimeString(options) : datetimeConverter.toDateString();
	    },
	    getDatetimeConverter() {
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.value).toUserTime();
	    },
	    getDatetimeConverterWithDuration() {
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.value + this.duration).toUserTime();
	    }
	  },
	  computed: {
	    encodedText() {
	      const formattedDate = this.getFormattedDate();
	      if (!main_core.Type.isNumber(this.duration)) {
	        return main_core.Text.encode(formattedDate);
	      }
	      const converterWithDuration = this.getDatetimeConverterWithDuration();
	      return main_core.Text.encode(`${formattedDate}-${converterWithDuration.toTimeString()}`);
	    }
	  },
	  template: Text.template
	};

	const DatePillColor = Object.freeze({
	  DEFAULT: 'default',
	  WARNING: 'warning',
	  NONE: 'none'
	});
	const PillStyle = Object.freeze({
	  DEFAULT: 'pill',
	  INLINE_GROUP: 'pill-inline-group'
	});
	var DatePill = {
	  props: {
	    value: Number,
	    withTime: Boolean,
	    duration: {
	      type: Number,
	      required: false,
	      default: null
	    },
	    backgroundColor: {
	      type: String,
	      required: false,
	      default: DatePillColor.DEFAULT,
	      validator(value) {
	        return Object.values(DatePillColor).includes(value);
	      }
	    },
	    action: Object | null,
	    styleValue: String
	  },
	  inject: ['isReadOnly'],
	  data() {
	    return {
	      currentTimestamp: this.value,
	      initialTimestamp: this.value
	    };
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__date-pill', `--color-${this.backgroundColor}`, {
	        '--readonly': this.isPillReadonly
	      }, {
	        '--inline-group': this.styleValue === PillStyle.INLINE_GROUP
	      }];
	    },
	    formattedDate() {
	      if (!this.currentTimestamp) {
	        return null;
	      }
	      const converter = this.getDatetimeConverter();
	      let result = converter.toDatetimeString({
	        withDayOfWeek: true,
	        withFullMonth: true,
	        delimiter: ', '
	      });
	      if (main_core.Type.isNumber(this.duration)) {
	        const converterWithDuration = this.getDatetimeConverterWithDuration();
	        result = `${result}-${converterWithDuration.toTimeString()}`;
	      }
	      return result;
	    },
	    currentDateInSiteFormat() {
	      return main_date.DateTimeFormat.format(this.withTime ? crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat() : crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), this.getDatetimeConverter().getValue());
	    },
	    calendarParams() {
	      return {
	        value: this.currentDateInSiteFormat,
	        bTime: this.withTime,
	        bHideTime: !this.withTime,
	        bSetFocus: false
	      };
	    },
	    isPillReadonly() {
	      return this.isReadOnly || !this.action;
	    }
	  },
	  watch: {
	    value(newDate)
	    // update date from push
	    {
	      this.initialTimestamp = newDate;
	      this.currentTimestamp = newDate;
	    }
	  },
	  methods: {
	    openCalendar(event) {
	      if (this.isPillReadonly) {
	        return;
	      }

	      // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	      BX.calendar({
	        node: event.target,
	        callback_after: newDate => {
	          // we assume that user selected time in his timezone
	          this.currentTimestamp = main_date.Timezone.UserTime.toUTCTimestamp(newDate);
	          this.executeAction();
	        },
	        ...this.calendarParams
	      });
	    },
	    executeAction() {
	      var _actionDescription$ac;
	      if (!this.action) {
	        return;
	      }
	      if (this.currentTimestamp === this.initialTimestamp) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentDateInSiteFormat;
	      actionDescription.actionParams.valueTs = this.currentTimestamp;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialTimestamp = this.currentTimestamp;
	      this.$emit('onChange', this.initialTimestamp);
	    },
	    getDatetimeConverter() {
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.currentTimestamp).toUserTime();
	    },
	    getDatetimeConverterWithDuration() {
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.currentTimestamp + this.duration).toUserTime();
	    }
	  },
	  template: `
		<span
			:class="className"
			@click="openCalendar"
		>
			<span>
				{{ formattedDate }}
			</span>
			<span class="crm-timeline__date-pill_caret"></span>
		</span>`
	};

	var Link = {
	  props: {
	    text: String,
	    action: Object,
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    bold: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    decoration: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    icon: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    rowLimit: {
	      type: Number,
	      required: false,
	      default: 0
	    }
	  },
	  computed: {
	    href() {
	      if (!this.action) {
	        return null;
	      }
	      const action = new Action(this.action);
	      if (action.isRedirect()) {
	        return action.getValue();
	      }
	      return null;
	    },
	    linkAttrs() {
	      if (!this.action) {
	        return {};
	      }
	      const action = new Action(this.action);
	      if (!action.isRedirect()) {
	        return {};
	      }
	      const attrs = {
	        href: action.getValue()
	      };
	      const target = action.getActionParam('target');
	      if (target) {
	        attrs.target = target;
	      }
	      return attrs;
	    },
	    className() {
	      return ['crm-timeline__card_link', this.colorClassName, this.boldClassName, this.decorationClassName, this.rowLimitClassName];
	    },
	    colorClassName() {
	      var _TextColor$upperCaseC;
	      const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
	      const color = (_TextColor$upperCaseC = TextColor[upperCaseColorProp]) !== null && _TextColor$upperCaseC !== void 0 ? _TextColor$upperCaseC : '';
	      return `--color-${color}`;
	    },
	    boldClassName() {
	      return this.bold ? '--bold' : '';
	    },
	    decorationClassName() {
	      var _TextDecoration$upper;
	      const upperCaseDecorationProp = this.decoration ? this.decoration.toUpperCase() : '';
	      if (!upperCaseDecorationProp) {
	        return '';
	      }
	      const decoration = (_TextDecoration$upper = TextDecoration[upperCaseDecorationProp]) !== null && _TextDecoration$upper !== void 0 ? _TextDecoration$upper : TextDecoration.NONE;
	      return `--decoration-${decoration}`;
	    },
	    iconClassName() {
	      if (!this.icon) {
	        return [];
	      }
	      return ['crm-timeline__card_link_icon', `--code-${this.icon}`];
	    },
	    rowLimitClassName() {
	      return this.rowLimit ? '--limit' : '';
	    },
	    rowLimitStyle() {
	      if (this.rowLimit && this.rowLimit > 0) {
	        return {
	          '-webkit-line-clamp': this.rowLimit
	        };
	      }
	      return {};
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }
	  },
	  template: `
			<a
				v-if="href"
				v-bind="linkAttrs"
				:class="className"
				:title="title"
				:style="rowLimitStyle"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
				:title="title"
				:style="rowLimitStyle"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</span>
		`
	};

	var EditableDate = {
	  components: {
	    Link
	  },
	  props: {
	    value: Number,
	    withTime: Boolean,
	    action: Object
	  },
	  data() {
	    return {
	      currentDate: this.value,
	      initialDate: this.value,
	      actionTimeoutId: null
	    };
	  },
	  computed: {
	    currentDateObject() {
	      return this.currentDate ? new Date(this.currentDate * 1000) : null;
	    },
	    currentDateInSiteFormat() {
	      if (!this.currentDateObject) {
	        return null;
	      }
	      return main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), this.currentDateObject);
	    },
	    textProps() {
	      return {
	        text: this.currentDateInSiteFormat
	      };
	    }
	  },
	  methods: {
	    openCalendar(event) {
	      this.cancelScheduledActionExecution();

	      // eslint-disable-next-line bitrix-rules/no-bx
	      BX.calendar({
	        node: event.target,
	        value: this.currentDateInSiteFormat,
	        bTime: this.withTime,
	        bHideTime: !this.withTime,
	        bSetFocus: false,
	        callback_after: newDate => {
	          this.currentDate = Math.round(newDate.getTime() / 1000);
	          this.scheduleActionExecution();
	        }
	      });
	    },
	    scheduleActionExecution() {
	      this.cancelScheduledActionExecution();
	      this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
	    },
	    cancelScheduledActionExecution() {
	      if (this.actionTimeoutId) {
	        clearTimeout(this.actionTimeoutId);
	        this.actionTimeoutId = null;
	      }
	    },
	    executeAction() {
	      var _actionDescription$ac;
	      if (!this.action) {
	        return;
	      }
	      if (this.currentDate === this.initialDate) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentDateObject;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialDate = this.currentDate;
	    }
	  },
	  template: `<Link @click="openCalendar" v-bind="textProps"></Link>`
	};

	var EditableText = ui_vue3.BitrixVue.cloneComponent(Text, {
	  components: {
	    Text
	  },
	  props: {
	    action: Object
	  },
	  data() {
	    return {
	      isEdit: false,
	      currentValue: this.value,
	      initialValue: this.value,
	      actionTimeoutId: null
	    };
	  },
	  computed: {
	    textProps() {
	      return {
	        ...this.$props,
	        value: this.currentValue
	      };
	    }
	  },
	  methods: {
	    enableEdit() {
	      this.cancelScheduledActionExecution();
	      this.isEdit = true;
	      this.$nextTick(() => {
	        this.$refs.input.focus();
	      });
	    },
	    disableEdit() {
	      this.isEdit = false;
	      this.scheduleActionExecution();
	    },
	    scheduleActionExecution() {
	      this.cancelScheduledActionExecution();
	      this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
	    },
	    cancelScheduledActionExecution() {
	      if (this.actionTimeoutId) {
	        clearTimeout(this.actionTimeoutId);
	        this.actionTimeoutId = null;
	      }
	    },
	    executeAction() {
	      var _actionDescription$ac;
	      if (!this.action || this.currentValue === this.initialValue) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentValue;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialValue = this.currentValue;
	    }
	  },
	  template: `
			<input
				v-if="isEdit"
				ref="input"
				type="text"
				v-model.trim="currentValue"
				@focusout="disableEdit"
			>
			<Text
				v-else
				v-bind="textProps"
				@click="enableEdit"
			/>
		`
	});

	let LogoType = function LogoType() {
	  babelHelpers.classCallCheck(this, LogoType);
	};
	babelHelpers.defineProperty(LogoType, "CALL_AUDIO_PLAY", 'call-play-record');
	babelHelpers.defineProperty(LogoType, "CALL_AUDIO_PAUSE", 'call-pause-record');

	const TimelineAudio = crm_audioPlayer.AudioPlayer.getComponent({
	  methods: {
	    changeLogoIcon(icon) {
	      if (!this.$parent || !this.$parent.getLogo) {
	        return;
	      }
	      const logo = this.$parent.getLogo();
	      if (!logo) {
	        return;
	      }
	      logo.setIcon(icon);
	    },
	    audioEventRouterWrapper(eventName, event) {
	      this.audioEventRouter(eventName, event);
	      if (eventName === 'play') {
	        this.changeLogoIcon(LogoType.CALL_AUDIO_PAUSE);
	      }
	      if (eventName === 'pause') {
	        this.changeLogoIcon(LogoType.CALL_AUDIO_PLAY);
	      }
	    }
	  }
	});

	var File = {
	  components: {
	    TimelineAudio
	  },
	  props: {
	    id: Number,
	    text: String,
	    href: String,
	    size: Number,
	    attributes: Object,
	    hasAudioPlayer: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    fileExtension() {
	      return this.text.split('.').slice(-1)[0] || '';
	    },
	    titleFirstPart() {
	      return this.text.slice(0, -this.titleLastPartSize);
	    },
	    titleLastPart() {
	      return this.text.slice(-this.titleLastPartSize);
	    },
	    titleLastPartSize() {
	      return 10;
	    }
	  },
	  mounted() {
	    const fileIcon = new ui_icons_generator.FileIcon({
	      name: this.fileExtension
	    });
	    fileIcon.renderTo(this.$refs.icon);
	  },
	  template: `
		<div class="crm-timeline__file">
			<div ref="icon" class="crm-timeline__file_icon"></div>
			<a
				target="_blank"
				class="crm-timeline__file_title crm-timeline__card_link"
				v-if="href"
				:title="text"
				:href="href"
				v-bind="attributes"
				ref="title"
			>
				<span>{{ titleFirstPart }}</span>
				<span>{{ titleLastPart }}</span>
			</a>
			<div class="crm-timeline__file_audio-player" v-if="this.hasAudioPlayer">
				<TimelineAudio :id="id" :mini="true" :src="href"></TimelineAudio>
			</div>
		</div>
		`
	};

	const FileList = {
	  components: {
	    File,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    numberOfFiles: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    files: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    updateParams: {
	      type: Object,
	      required: false,
	      default: {}
	    },
	    visibleFilesNumber: {
	      type: Number,
	      required: false,
	      default: 5
	    }
	  },
	  inject: ['isReadOnly'],
	  data() {
	    return {
	      visibleFilesAmount: this.visibleFilesNumber
	    };
	  },
	  computed: {
	    isEditable() {
	      return Object.keys(this.updateParams).length > 0 && !this.isReadOnly;
	    },
	    visibleFiles() {
	      return this.files.slice(0, this.visibleFilesAmount);
	    },
	    editFilesBtnClassname() {
	      return ['crm-timeline__file-list-btn', {
	        '--disabled': !this.isEditable
	      }];
	    },
	    expandFileListBtnTitle() {
	      return this.isAllFilesVisible ? this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_FILE_LIST_COLLAPSE') : this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_FILE_LIST_EXPAND');
	    },
	    editFilesBtnIcon() {
	      return ui_iconSet_api_vue.Set.PENCIL_40;
	    },
	    addVisibleFilesBtnIcon() {
	      return ui_iconSet_api_vue.Set.CHEVRON_DOWN;
	    },
	    isAllFilesVisible() {
	      return this.visibleFilesAmount === this.numberOfFiles;
	    },
	    isShowExpandFileListBtn() {
	      return this.numberOfFiles > this.visibleFilesNumber;
	    },
	    expandBtnIconClassname() {
	      return ['crm-timeline__file-list-btn-icon', {
	        '--upended': this.isAllFilesVisible
	      }];
	    }
	  },
	  methods: {
	    fileProps(file) {
	      return {
	        id: file.id,
	        text: file.name,
	        href: file.viewUrl,
	        size: file.size,
	        attributes: file.attributes,
	        hasAudioPlayer: file.hasAudioPlayer
	      };
	    },
	    showFileUploaderPopup() {
	      if (!this.isEditable) {
	        return;
	      }
	      const popup = new crm_activity_fileUploaderPopup.FileUploaderPopup(this.updateParams);
	      popup.show();
	    },
	    handleShowFilesBtnClick() {
	      if (this.isAllFilesVisible) {
	        this.collapseFileList();
	      } else {
	        this.expandFileList();
	      }
	    },
	    expandFileList() {
	      this.visibleFilesAmount = this.numberOfFiles;
	    },
	    collapseFileList() {
	      this.visibleFilesAmount = this.visibleFilesNumber;
	    }
	  },
	  template: `
			<div class="crm-timeline__file-list-wrapper">
				<div class="crm-timeline__file-list-container">
					<div
						class="crm-timeline__file-container"
						v-for="file in visibleFiles"
					>
						<File :key="file.id" v-bind="fileProps(file)"></File>
					</div>
				</div>
				<footer class="crm-timeline__file-list-footer">
					<div
						v-if="isShowExpandFileListBtn"
						class="crm-timeline__file-list-btn-container"
					>
						<button
							class="crm-timeline__file-list-btn"
							@click="handleShowFilesBtnClick"
						>
							<span class="crm-timeline__file-list-btn-text">{{expandFileListBtnTitle}}</span>
							<i :class="expandBtnIconClassname">
								<BIcon :name="addVisibleFilesBtnIcon" :size="18"></BIcon>
							</i>
						</button>
					</div>
					<div
						v-if="isEditable"
						class="crm-timeline__file-list-btn-container"
					>
						<button
							v-if="title !== '' || numberOfFiles > 0"
							@click="showFileUploaderPopup"
							:class="editFilesBtnClassname"
						>
							<span class="crm-timeline__file-list-btn-text">{{ title }}</span>
							<i class="crm-timeline__file-list-btn-icon">
								<BIcon :name="editFilesBtnIcon" :size="18"></BIcon>
							</i>
							<i ref="edit-icon" class="crm-timeline__file-list-btn-icon"></i>
					</button>
					</div>
				</footer>
			</div>
		`
	};

	const InfoGroup = {
	  props: {
	    blocks: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    }
	  },
	  template: `
		<table class="crm-timeline__info-group">
			<tbody>
				<tr
					v-for="({title, block}, id) in blocks"
					:key="id"
					class="crm-timeline__info-group_block"
				>
					<td
						:title="title"
						class="crm-timeline__info-group_block-title"
					>
						{{title}}
					</td>
					<td class="crm-timeline__info-group_block-content">
						<component
							:is="block.rendererName"
							v-bind="block.properties"
						/>
					</td>
				</tr>
			</tbody>
		</table>
	`
	};

	const SAVE_OFFSETS_REQUEST_DELAY = 1000;
	var ItemSelector = {
	  props: {
	    valuesList: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    value: {
	      type: Array,
	      default: []
	    },
	    saveAction: {
	      type: Object,
	      required: true
	    },
	    compactMode: {
	      type: Boolean,
	      default: false
	    },
	    icon: {
	      type: String,
	      default: null,
	      required: false
	    }
	  },
	  methods: {
	    onItemSelectorValueChange(event) {
	      main_core.Runtime.debounce(() => {
	        const data = event.getData();
	        if (data) {
	          this.executeSaveAction(data.value);
	        }
	      }, SAVE_OFFSETS_REQUEST_DELAY, this)();
	    },
	    executeSaveAction(items) {
	      var _actionDescription$ac;
	      if (!this.saveAction) {
	        return;
	      }
	      if (this.value.sort().toString() === items.sort().toString()) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.saveAction);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = items;
	      const action = new Action(actionDescription);
	      void action.execute(this);
	    }
	  },
	  mounted() {
	    var _this$compactMode;
	    this.itemSelector = new crm_field_itemSelector.ItemSelector({
	      target: this.$el,
	      valuesList: this.valuesList,
	      selectedValues: this.value,
	      compactMode: (_this$compactMode = this.compactMode) !== null && _this$compactMode !== void 0 ? _this$compactMode : false,
	      icon: main_core.Type.isStringFilled(this.icon) ? this.icon : null
	    });
	    main_core_events.EventEmitter.subscribe(this.itemSelector, crm_field_itemSelector.Events.EVENT_ITEMSELECTOR_VALUE_CHANGE, this.onItemSelectorValueChange);
	  },
	  beforeUnmount() {
	    main_core_events.EventEmitter.unsubscribe(this.itemSelector, crm_field_itemSelector.Events.EVENT_ITEMSELECTOR_VALUE_CHANGE, this.onItemSelectorValueChange);
	  },
	  computed: {
	    styles() {
	      if (this.compactMode) {
	        return {};
	      }
	      return {
	        width: '100%'
	      };
	    }
	  },
	  template: '<div :style="styles"></div>'
	};

	var LineOfTextBlocks = {
	  props: {
	    blocks: Object,
	    delimiter: String,
	    button: Object
	  },
	  mounted() {
	    const blocks = this.$refs.blocks;
	    this.visibleBlocks.forEach((block, index) => {
	      if (main_core.Type.isDomNode(blocks[index].$el)) {
	        blocks[index].$el.setAttribute('data-id', block.id);
	      } else {
	        throw new Error(`Vue component "${block.rendererName}" was not found`);
	      }
	    });
	  },
	  methods: {
	    isShowDelimiter(index, length) {
	      return main_core.Type.isString(this.delimiter) && !this.isLastElement(index, length);
	    },
	    isLastElement(index, length) {
	      return index === length - 1;
	    }
	  },
	  computed: {
	    visibleBlocks() {
	      if (!main_core.Type.isObject(this.blocks)) {
	        return [];
	      }
	      const blocks = Object.keys(this.blocks).map(id => ({
	        id,
	        ...this.blocks[id]
	      })).filter(item => item.scope !== 'mobile');
	      if (main_core.Type.isObject(this.button)) {
	        blocks.push({
	          id: 'button',
	          ...this.button
	        });
	      }
	      return blocks;
	    },
	    formattedDelimiter() {
	      return main_core.Text.encode(this.delimiter).replace(' ', '&nbsp;');
	    }
	  },
	  // language=Vue
	  template: `
		<span class="crm-timeline-block-line-of-texts">
			<span
				v-for="(block, index) in visibleBlocks"
				:key="block.id"
			>
				<component 
					:is="block.rendererName"
					v-bind="block.properties"
					ref="blocks"
				/>
				<span v-if="isShowDelimiter(index, visibleBlocks.length)" v-html="formattedDelimiter"></span>
				<span v-else-if="!isLastElement(index, visibleBlocks.length)">&nbsp;</span>
			</span>
		</span>
	`
	};

	var LineOfTextBlocksButton = {
	  props: {
	    action: Object,
	    icon: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    title: String
	  },
	  computed: {
	    href() {
	      if (!this.action) {
	        return null;
	      }
	      const action = new Action(this.action);
	      if (action.isRedirect()) {
	        return action.getValue();
	      }
	      return null;
	    },
	    linkAttrs() {
	      if (!this.action) {
	        return {};
	      }
	      const action = new Action(this.action);
	      if (!action.isRedirect()) {
	        return {};
	      }
	      const attrs = {
	        href: action.getValue()
	      };
	      const target = action.getActionParam('target');
	      if (target) {
	        attrs.target = target;
	      }
	      return attrs;
	    },
	    className() {
	      return ['crm-timeline__line_of_text_blocks_button'];
	    },
	    iconClassName() {
	      if (!this.icon) {
	        return [];
	      }
	      return ['crm-timeline__line_of_text_blocks_button_icon', `--code-${this.icon}`];
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    },
	    addAlignRightClass() {
	      this.$el.parentElement.classList.add('right-fixed-button');
	    }
	  },
	  mounted() {
	    this.addAlignRightClass();
	  },
	  template: `
			<a
				v-if="href"
				v-bind="linkAttrs"
				:class="className"
				:title="title"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
				:title="title"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</span>
		`
	};

	var Money = {
	  props: {
	    opportunity: Number,
	    currencyId: String
	  },
	  computed: {
	    encodedText() {
	      if (!main_core.Type.isNumber(this.opportunity) || !main_core.Type.isStringFilled(this.currencyId)) {
	        return null;
	      }
	      return currency_currencyCore.CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
	    }
	  },
	  extends: Text,
	  template: `
		<span
			v-if="encodedText"
			:class="className"
			v-html="encodedText"
		></span>`
	};

	const MoneyPill = {
	  props: {
	    opportunity: Number,
	    currencyId: String
	  },
	  computed: {
	    moneyHtml() {
	      if (!main_core.Type.isNumber(this.opportunity) || !main_core.Type.isStringFilled(this.currencyId)) {
	        return null;
	      }
	      return currency_currencyCore.CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
	    }
	  },
	  template: `
		<div class="crm-timeline-card__money-pill">
			<span class="crm-timeline-card__money-pill_amount">
				<span v-if="moneyHtml" v-html="moneyHtml"></span>
			</span>
		</div>
	`
	};

	const Note = {
	  components: {
	    User,
	    Button
	  },
	  props: {
	    id: {
	      type: Number,
	      required: false
	    },
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    deleteConfirmationText: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    saveNoteAction: {
	      type: Object
	    },
	    deleteNoteAction: {
	      type: Object
	    },
	    updatedBy: {
	      type: Object,
	      required: false
	    }
	  },
	  data() {
	    return {
	      note: this.text,
	      oldNote: this.text,
	      isEdit: false,
	      isExist: !!this.id,
	      isSaving: false,
	      isDeleting: false,
	      isCollapsed: true,
	      shortNoteLength: 113
	    };
	  },
	  inject: ['isReadOnly', 'currentUser'],
	  computed: {
	    noteText() {
	      if (this.isCollapsed) {
	        return this.shortNote;
	      }
	      return this.note;
	    },
	    shortNote() {
	      if (this.note.length > this.shortNoteLength) {
	        return `${this.note.slice(0, this.shortNoteLength)}...`;
	      } else if (this.getNoteLineBreaksCount() > 2) {
	        let currentLineBreakerCount = 0;
	        for (let letterIndex = 0; letterIndex < this.note.length; letterIndex++) {
	          const letter = this.note[letterIndex];
	          if (letter !== '\n') {
	            continue;
	          }
	          currentLineBreakerCount++;
	          if (currentLineBreakerCount === this.maxLineBreakerCount) {
	            return `${this.note.slice(0, letterIndex)}...`;
	          }
	        }
	      }
	      return this.note;
	    },
	    maxLineBreakerCount() {
	      return 3;
	    },
	    expandNoteBtnText() {
	      if (this.isCollapsed) {
	        return this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_NOTE_SHOW');
	      } else {
	        return this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_NOTE_HIDE');
	      }
	    },
	    ButtonType() {
	      return ButtonType;
	    },
	    isDeleteButtonVisible() {
	      return !this.isReadOnly;
	    },
	    isEditButtonVisible() {
	      return !(this.isReadOnly || this.isEdit);
	    },
	    saveButtonState() {
	      if (this.isSaving) {
	        return ButtonState.DISABLED;
	      }
	      if (this.note.trim().length > 0) {
	        return ButtonState.DEFAULT;
	      }
	      return ButtonState.DISABLED;
	    },
	    cancelButtonState() {
	      if (this.isSaving) {
	        return ButtonState.DISABLED;
	      }
	      return ButtonState.DEFAULT;
	    },
	    isNoteVisible() {
	      return this.isExist || this.isEdit;
	    },
	    user() {
	      if (this.updatedBy) {
	        return this.updatedBy;
	      }
	      if (this.currentUser) {
	        return this.currentUser;
	      }
	      return {
	        title: '',
	        detailUrl: '',
	        imageUrl: ''
	      };
	    },
	    isShowExpandBtn() {
	      return !this.isEdit && (this.note.length > this.shortNoteLength || this.getNoteLineBreaksCount() > 2);
	    }
	  },
	  methods: {
	    toggleNoteLength() {
	      this.isCollapsed = !this.isCollapsed;
	    },
	    startEditing() {
	      this.isEdit = true;
	      this.$nextTick(() => {
	        this.isCollapsed = false;
	        const textarea = this.$refs.noteText;
	        this.adjustHeight(textarea);
	        textarea.focus();
	      });
	      this.executeAction({
	        type: 'jsEvent',
	        value: 'Note:StartEdit'
	      });
	    },
	    adjustHeight(elem) {
	      elem.style.height = 0;
	      elem.style.height = elem.scrollHeight + "px";
	    },
	    setEditMode(editMode) {
	      const isEdit = editMode ? !this.isReadOnly : false;
	      if (isEdit !== this.isEdit) {
	        if (isEdit) {
	          this.startEditing();
	        } else {
	          this.isEdit = false;
	          this.executeAction({
	            type: 'jsEvent',
	            value: 'Note:FinishEdit'
	          });
	        }
	      }
	    },
	    onEnterHandle(event) {
	      if (event.ctrlKey === true || main_core.Browser.isMac() && (event.metaKey === true || event.altKey === true)) {
	        this.saveNote();
	      }
	    },
	    cancelEditing() {
	      this.note = this.oldNote;
	      this.isEdit = false;
	      this.executeAction({
	        type: 'jsEvent',
	        value: 'Note:FinishEdit'
	      });
	    },
	    deleteNote() {
	      if (this.isSaving) {
	        return;
	      }
	      if (!this.isExist) {
	        this.cancelEditing();
	        return;
	      }
	      if (this.deleteConfirmationText && this.deleteConfirmationText.length) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: this.deleteConfirmationText,
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	          onYes: messageBox => {
	            messageBox.close();
	            this.executeDeleteAction();
	          },
	          onNo: messageBox => {
	            messageBox.close();
	          }
	        });
	      } else {
	        this.executeDeleteAction();
	      }
	    },
	    saveNote() {
	      if (this.saveButtonState === ButtonState.DISABLED || this.isSaving || this.isDeleting) {
	        return;
	      }
	      if (this.note === this.text) {
	        this.cancelEditing();
	        return;
	      }
	      this.isSaving = true;
	      const action = main_core.Runtime.clone(this.saveNoteAction);
	      action.actionParams.text = this.note;
	      this.executeAction(action).then(({
	        status
	      }) => {
	        if (status === 'success') {
	          this.oldNote = this.$refs.noteText.value.trim();
	          this.isExist = true;
	          this.cancelEditing();
	        }
	      }).finally(() => {
	        this.isSaving = false;
	      });
	    },
	    executeDeleteAction() {
	      if (this.isSaving) {
	        return;
	      }
	      this.isDeleting = true;
	      this.executeAction(this.deleteNoteAction).then(({
	        status
	      }) => {
	        if (status === 'success') {
	          this.oldNote = '';
	          this.isExist = false;
	          this.cancelEditing();
	        }
	      }).finally(() => {
	        this.isDeleting = false;
	      });
	    },
	    executeAction(actionObject) {
	      if (!actionObject) {
	        console.error('No action object to execute');
	        return;
	      }
	      const action = new Action(actionObject);
	      return action.execute(this);
	    },
	    handleWindowResize() {
	      const windowWidth = window.innerWidth;
	      if (windowWidth > 1400) {
	        this.shortNoteLength = 250;
	      } else {
	        this.shortNoteLength = 113;
	      }
	    },
	    getNoteLineBreaksCount() {
	      return this.note.split('').reduce((counter, elem) => {
	        return counter + (elem === '\n' ? 1 : 0);
	      }, 0);
	    }
	  },
	  watch: {
	    id(id) {
	      this.isExist = !!id;
	    },
	    text(text) {
	      this.note = text;
	      this.oldNote = text;
	    },
	    note() {
	      if (!this.isEdit) {
	        return;
	      }
	      this.$nextTick(() => {
	        this.adjustHeight(this.$refs.noteText);
	      });
	    },
	    isEdit(value) {
	      if (value) {
	        this.$nextTick(() => this.$refs.noteText.focus());
	      }
	    }
	  },
	  created() {
	    this.handleWindowResize();
	    main_core.Event.bind(window, 'resize', this.handleWindowResize);
	  },
	  destroyed() {
	    main_core.Event.unbind(window, 'resize', this.handleWindowResize);
	  },
	  template: `
		<div
			v-show="isNoteVisible"
			class="crm-timeline__card-note"
		>
			<div class="crm-timeline__card-note_user">
				<User v-bind="user"></User>
			</div>
			<div class="crm-timeline__card-note_area">
				<div class="crm-timeline__card-note_value">
						<textarea
							v-if="isEdit"
							v-model="note"
							@keydown.esc.stop="cancelEditing"
							@keydown.enter="onEnterHandle"
							:disabled="!isEdit || isSaving"
							:placeholder="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_PLACEHOLDER')"
							ref="noteText"
							class="crm-timeline__card-note_text"
						></textarea>
						<span
							v-else
							ref="noteText"
							class="crm-timeline__card-note_text"
						>
							{{noteText}}
						</span>
	
					<span
						v-if="isEditButtonVisible"
						class="crm-timeline__card-note_edit"
						@click.prevent.stop="startEditing"
					>
							<i></i>
						</span>
				</div>
				<div v-if="isEdit" class="crm-timeline__card-note__controls">
					<div class="crm-timeline__card-note__control --save">
						<Button
							@click="saveNote"
							:state="saveButtonState" :type="ButtonType.PRIMARY"
							:title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_SAVE')"
						/>
					</div>
					<div class="crm-timeline__card-note__control --cancel">
						<Button @click="cancelEditing"
								:type="ButtonType.LIGHT"
								:state="cancelButtonState"
								:title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_CANCEL')"
						/>
					</div>
				</div>
			</div>
			<div v-if="isDeleteButtonVisible" class="crm-timeline__card-note_cross" @click="deleteNote">
				<i></i>
			</div>
			<div v-if="isDeleting" class="crm-timeline__card-note_dimmer"></div>
			<div
				v-show="isShowExpandBtn"
				@click="toggleNoteLength"
				class="crm-timeline__card-note_expand-note-btn"
			>
				{{ expandNoteBtnText }}
			</div>
		</div>
	`
	};

	const PlayerAlert = {
	  components: {
	    LineOfTextBlocks
	  },
	  props: {
	    blocks: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ui_alerts.AlertColor.DEFAULT
	    },
	    icon: {
	      type: String,
	      required: false,
	      default: ui_alerts.AlertIcon.NONE
	    }
	  },
	  computed: {
	    containerClassname() {
	      return ['crm-timeline__player-alert', 'ui-alert', 'ui-alert-xs', 'ui-alert-text-center', this.color, this.icon];
	    }
	  },
	  template: `
		<div :class="containerClassname">
			<div class="ui-alert-message">
				<LineOfTextBlocks :blocks="blocks"></LineOfTextBlocks>
			</div>
		</div>
	`
	};

	const RestAppLayoutBlocks = {
	  props: {
	    itemTypeId: {
	      type: Number
	    },
	    itemId: {
	      type: Number
	    },
	    restAppInfo: {
	      title: String,
	      clientId: String
	    },
	    contentBlocks: {
	      type: Object
	    }
	  },
	  computed: {
	    restAppTitle() {
	      return main_core.Text.encode(this.restAppInfo.title);
	    },
	    clientId() {
	      return main_core.Text.encode(this.restAppInfo.clientId);
	    }
	  },
	  template: `
		<div class="crm_timeline__rest_app_layout_blocks" :data-app-name="restAppTitle" :data-rest-client-id="clientId">
			<div class="crm-timeline__card-container_block" v-for="contentBlock in contentBlocks">
				<component :is="contentBlock.rendererName" v-bind="contentBlock.properties" ref="contentBlocks" />
			</div>
		</div>
	`
	};

	const SmsMessage = {
	  props: {
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    messageHtml() {
	      return BX.util.htmlspecialchars(this.text).replace(/\r\n|\r|\n/g, '<br/>');
	    }
	  },
	  template: `
		<div
			class="crm-timeline__item_sms-message">
			<span v-if="messageHtml" v-html="messageHtml"></span>
		</div>
	`
	};

	const STATE_LOADING = 'loading';
	const STATE_PROCESSED = 'processed';
	const STATE_UNPROCESSED = 'unprocessed';
	const CallScoringPill = {
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    value: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    state: {
	      type: String,
	      required: false,
	      default: STATE_UNPROCESSED
	    },
	    action: Object | null
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    className() {
	      return ['crm-timeline__call-scoring-pill', {
	        '--readonly': this.isPillReadonly
	      }];
	    },
	    renderValue() {
	      switch (this.state) {
	        case STATE_LOADING:
	          return '<span class="loader"></span>';
	        case STATE_PROCESSED:
	          return main_core.Text.encode(this.value);
	        case STATE_UNPROCESSED:
	        default:
	          return '<span class="arrow">&nbsp;</span>';
	      }
	    },
	    isPillReadonly() {
	      return this.isReadOnly || !this.action;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.isPillReadonly) {
	        return;
	      }
	      const action = new Action(this.action);
	      void action.execute(this);
	    }
	  },
	  template: `
		<div
			:class='className'
			@click='executeAction'
		>
			<div class='crm-timeline__call-scoring-pill-left'>{{ this.title }}</div>
			<div class='crm-timeline__call-scoring-pill-separator'></div>
			<div class='crm-timeline__call-scoring-pill-right' v-html='renderValue'></div>
		</div>
	`
	};

	const CallScoring = {
	  props: {
	    userName: String,
	    userAvatarUrl: String,
	    scoringData: Object | null,
	    action: Object | null
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    className() {
	      var _this$scoringData, _this$scoringData2, _this$scoringData3;
	      const assessment = main_core.Text.toInteger((_this$scoringData = this.scoringData) === null || _this$scoringData === void 0 ? void 0 : _this$scoringData.ASSESSMENT);
	      const highBorder = main_core.Text.toInteger((_this$scoringData2 = this.scoringData) === null || _this$scoringData2 === void 0 ? void 0 : _this$scoringData2.HIGH_BORDER);
	      const lowBorder = main_core.Text.toInteger((_this$scoringData3 = this.scoringData) === null || _this$scoringData3 === void 0 ? void 0 : _this$scoringData3.LOW_BORDER);
	      return {
	        'crm-timeline__call-scoring': true,
	        '--success': assessment >= highBorder,
	        '--failed': assessment <= lowBorder
	      };
	    },
	    assessmentScriptClassName() {
	      return ['crm-timeline__call-scoring-assessment-script', {
	        '--readonly': this.isContentReadonly
	      }];
	    },
	    assessmentPillClassName() {
	      return ['crm-timeline__call-scoring-assessment-pill', {
	        '--readonly': this.isContentReadonly
	      }];
	    },
	    isContentReadonly() {
	      return this.isReadOnly || !this.action;
	    },
	    renderUserAvatarElement() {
	      return new ui_avatar.AvatarRoundGuest({
	        size: 26,
	        userName: this.userName,
	        userpicPath: this.userAvatarUrl,
	        baseColor: '#7fdefc',
	        borderColor: '#9dcf00'
	      }).getContainer().outerHTML;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.isContentReadonly) {
	        return;
	      }
	      const action = new Action(this.action);
	      void action.execute(this);
	    }
	  },
	  template: `
		<div :class='className'>
			<div class='crm-timeline__call-scoring-wrapper'>
				<div class='crm-timeline__call-scoring-responsible'>
					<div class='crm-timeline__call-scoring-title'>
						{{ this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_CALL_SCORING_RESPONSIBLE_TITLE') }}
					</div>
					<div class='crm-timeline__call-scoring-responsible-content'>
						<div class='responsible-user-avatar' v-html="renderUserAvatarElement"></div>
						<div class='responsible-user-name'>{{ this.userName }}</div>
					</div>
				</div>
				<div class='crm-timeline__line-div'></div>
				<div class='crm-timeline__call-scoring-assessment'>
					<div class='crm-timeline__call-scoring-assessment-wrapper'>
						<!--
						<img 
							class='copilot-avatar' 
							src='/bitrix/js/crm/timeline/item/src/images/crm-timelime__copilot-avatar.svg' 
							alt='copilot-avatar'
						>
						-->
						<div
							:class='assessmentPillClassName'
							@click='executeAction'
						>
							<span class="value">{{ this.scoringData?.ASSESSMENT }}</span>
							<div class="percent"></div>
						</div>
						<div class='script-layout'>
							<div class='crm-timeline__call-scoring-title'>
								{{ this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_CALL_SCORING_SCRIPT_TITLE') }}
							</div>
							<div 
								:class='assessmentScriptClassName'
								@click='executeAction'
							>
								{{ this.scoringData?.TITLE }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	let DeadlineAndPingSelectorBackgroundColor = function DeadlineAndPingSelectorBackgroundColor() {
	  babelHelpers.classCallCheck(this, DeadlineAndPingSelectorBackgroundColor);
	};
	babelHelpers.defineProperty(DeadlineAndPingSelectorBackgroundColor, "ORANGE", 'orange');
	babelHelpers.defineProperty(DeadlineAndPingSelectorBackgroundColor, "GRAY", 'gray');

	var DeadlineAndPingSelector = {
	  props: {
	    isScheduled: Boolean,
	    deadlineBlock: Object,
	    pingSelectorBlock: Object,
	    deadlineBlockTitle: String,
	    backgroundToken: String,
	    backgroundColor: {
	      type: String,
	      required: false,
	      default: null
	    }
	  },
	  data() {
	    return {
	      deadlineBlockData: this.deadlineBlock,
	      pingSelectorBlockData: this.pingSelectorBlock
	    };
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card-container_info': true,
	        '--inline': true,
	        'crm-timeline-block-deadline-and-ping-selector-deadline-wrapper': true,
	        '--orange': this.backgroundToken === DeadlineAndPingSelectorBackgroundColor.ORANGE,
	        '--gray': this.backgroundToken === DeadlineAndPingSelectorBackgroundColor.GRAY
	      };
	    },
	    deadlineBlockStyle() {
	      if (this.isScheduled && main_core.Type.isStringFilled(this.backgroundColor)) {
	        return {
	          '--crm-timeline-block-deadline-and-ping-selector-deadline_bg-color': main_core.Text.encode(this.backgroundColor)
	        };
	      }
	      return {};
	    }
	  },
	  methods: {
	    onDeadlineChange(deadline) {
	      this.deadlineBlockData.properties.value = deadline;
	      this.pingSelectorBlockData.properties.deadline = deadline;
	      this.$refs.pingSelectorBlock.setDeadline(deadline);
	    }
	  },
	  created() {
	    this.$watch('deadlineBlock', deadlineBlock => {
	      this.deadlineBlockData = deadlineBlock;
	    }, {
	      deep: true
	    });
	    this.$watch('pingSelectorBlock', pingSelectorBlock => {
	      this.pingSelectorBlockData = pingSelectorBlock;
	    }, {
	      deep: true
	    });
	  },
	  // language=Vue
	  template: `
		<span class="crm-timeline-block-deadline-and-ping-selector">
			<div 
				:class="className" 
				ref="deadlineBlock" 
				v-if="deadlineBlock"
				:style="deadlineBlockStyle"
			>
				<div class="crm-timeline__card-container_info-title" v-if="deadlineBlockTitle">
					{{deadlineBlockTitle}}&nbsp;
				</div>
				<component
					:is="deadlineBlock.rendererName"
					v-bind="deadlineBlockData.properties"
					@onChange="onDeadlineChange"
				/>
			</div>
	
			<component
				v-if="pingSelectorBlock"
				:is="pingSelectorBlock.rendererName"
				v-bind="pingSelectorBlockData.properties"
				ref="pingSelectorBlock"
			/>
		</span>	
	`
	};

	const SAVE_OFFSETS_REQUEST_DELAY$1 = 1000;
	var PingSelector = {
	  props: {
	    valuesList: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    value: {
	      type: Array,
	      default: []
	    },
	    deadline: {
	      type: Number
	    },
	    saveAction: {
	      type: Object,
	      required: true
	    },
	    icon: {
	      type: String,
	      default: null,
	      required: false
	    }
	  },
	  data() {
	    return {
	      deadlineData: this.deadline
	    };
	  },
	  watch: {
	    deadline(deadline) {
	      this.deadlineData = deadline;
	    }
	  },
	  mounted() {
	    this.initPingSelector();
	  },
	  beforeUnmount() {
	    main_core_events.EventEmitter.unsubscribe(this.pingSelector, crm_field_pingSelector.PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE, this.onItemSelectorValueChange);
	  },
	  methods: {
	    onItemSelectorValueChange(event) {
	      main_core.Runtime.debounce(() => {
	        const data = event.getData();
	        if (data) {
	          this.executeSaveAction(data.value);
	        }
	      }, SAVE_OFFSETS_REQUEST_DELAY$1, this)();
	    },
	    executeSaveAction(items) {
	      var _actionDescription$ac;
	      if (!this.saveAction) {
	        return;
	      }
	      if (this.value.sort().toString() === items.sort().toString()) {
	        return;
	      }

	      // to avoid unintended props mutation
	      const actionDescription = main_core.Runtime.clone(this.saveAction);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = items;
	      const action = new Action(actionDescription);
	      void action.execute(this);
	    },
	    initPingSelector() {
	      const deadlineDate = this.createDateFromDeadline();
	      const deadlineTime = deadlineDate === null || deadlineDate === void 0 ? void 0 : deadlineDate.getTime();
	      const currentTime = Date.now();
	      const deadline = deadlineTime > currentTime ? deadlineDate : new Date();
	      this.pingSelector = new crm_field_pingSelector.PingSelector({
	        target: this.$el,
	        valuesList: this.valuesList,
	        selectedValues: this.value,
	        icon: main_core.Type.isStringFilled(this.icon) ? this.icon : null,
	        deadline
	      });
	      main_core_events.EventEmitter.subscribe(this.pingSelector, crm_field_pingSelector.PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE, this.onItemSelectorValueChange);
	    },
	    createDateFromDeadline() {
	      if (!main_core.Type.isNumber(this.deadlineData)) {
	        return null;
	      }
	      return crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.deadlineData).getValue();
	    },
	    setDeadline(deadline) {
	      const date = main_date.Timezone.UserTime.getDate(deadline);
	      this.deadlineData = date.getTime() / 1000;
	      this.pingSelector.setDeadline(date);
	    }
	  },
	  template: '<div></div>'
	};

	var WithTitle = {
	  props: {
	    title: String,
	    inline: Boolean,
	    wordWrap: Boolean,
	    fixedWidth: Boolean,
	    titleBottomPadding: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    contentBlock: Object
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card-container_info': true,
	        '--inline': this.inline,
	        '--word-wrap': this.wordWrap,
	        '--fixed-width': this.fixedWidth
	      };
	    },
	    valueClassName() {
	      return {
	        'crm-timeline__card-container_info-value': true
	      };
	    }
	  },
	  methods: {
	    isTitleCropped() {
	      const titleElem = this.$refs.title;
	      return titleElem.scrollWidth > titleElem.clientWidth;
	    }
	  },
	  mounted() {
	    void this.$nextTick(() => {
	      if (!this.$refs.title) {
	        return;
	      }
	      if (this.isTitleCropped()) {
	        main_core.Dom.attr(this.$refs.title, 'title', this.title);
	      }
	      if (this.titleBottomPadding > 0) {
	        main_core.Dom.style(this.$refs.title, 'padding-bottom', `${this.titleBottomPadding}px`);
	      }
	    });
	  },
	  template: `
		<div
			:class="className"
		>
			<div
				ref="title" 
				class="crm-timeline__card-container_info-title"
			>
				{{ title }}
			</div>
			<div 
				:class="valueClassName"
			>
				<component 
					:is="contentBlock.rendererName"
					v-bind="contentBlock.properties"
				/>
			</div>
		</div>
	`
	};

	let _ = t => t,
	  _t,
	  _t2;
	var WorkflowEfficiency = {
	  data() {
	    return {
	      formattedAverageDuration: '',
	      formattedExecutionTime: ''
	    };
	  },
	  props: {
	    averageDuration: Number,
	    efficiency: String,
	    executionTime: Number,
	    processTimeText: String,
	    workflowResult: Object,
	    author: Object
	  },
	  computed: {
	    itemClassName() {
	      return `bizproc-workflow-timeline-eff-icon --${this.efficiency}`;
	    },
	    efficiencyCaption() {
	      let notice = this.efficiency === 'fast' ? 'QUICKLY' : 'SLOWLY';
	      if (this.efficiency === 'stopped') {
	        notice = 'NO_PROGRESS';
	      }
	      return main_core.Loc.getMessage(`BIZPROC_WORKFLOW_TIMELINE_SLIDER_PERFORMED_${notice}`);
	    },
	    hasResult() {
	      return this.workflowResult !== undefined;
	    },
	    href() {
	      if (this.author && this.author.link) {
	        return this.author.link;
	      }
	      return '';
	    },
	    imageStyle() {
	      if (this.author && this.author.avatarSize100) {
	        return {
	          backgroundImage: `url('${encodeURI(this.author.avatarSize100)}')`
	        };
	      }
	      return {};
	    },
	    workflowResultHtml() {
	      var _this$workflowResult$, _this$workflowResult;
	      if (this.workflowResult && this.workflowResult.status === bizproc_types.WorkflowResultStatus.NO_RIGHTS_RESULT) {
	        this.workflowResult.text = main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_VIEW');
	      }
	      return (_this$workflowResult$ = (_this$workflowResult = this.workflowResult) === null || _this$workflowResult === void 0 ? void 0 : _this$workflowResult.text) !== null && _this$workflowResult$ !== void 0 ? _this$workflowResult$ : null;
	    },
	    averageDurationText() {
	      return main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_EFFICIENCY_AVERAGE_PROCESS_TIME');
	    },
	    resultCaption() {
	      if (!this.userResult) {
	        return main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_TITLE');
	      }
	      return '';
	    },
	    userResult() {
	      if (!this.hasResult) {
	        var _this$author;
	        const userLink = main_core.Tag.render(_t || (_t = _`<a href="${0}"></a>`), this.href);
	        userLink.textContent = (_this$author = this.author) === null || _this$author === void 0 ? void 0 : _this$author.fullName;
	        return main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_NO_RESULT', {
	          '#USER#': userLink.outerHTML
	        });
	      }
	      if (this.workflowResult && this.workflowResult.status === bizproc_types.WorkflowResultStatus.USER_RESULT) {
	        var _this$workflowResult$2;
	        return main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_NO_RESULT', {
	          '#USER#': (_this$workflowResult$2 = this.workflowResult.text) !== null && _this$workflowResult$2 !== void 0 ? _this$workflowResult$2 : ''
	        });
	      }
	      return null;
	    }
	  },
	  mounted() {
	    if (this.workflowResult && this.workflowResult.status === bizproc_types.WorkflowResultStatus.NO_RIGHTS_RESULT) {
	      this.showHint();
	    }
	    main_core.Runtime.loadExtension('bizproc.workflow.timeline').then(({
	      DurationFormatter
	    }) => {
	      this.formattedAverageDuration = DurationFormatter.formatTimeInterval(this.averageDuration);
	      this.formattedExecutionTime = DurationFormatter.formatTimeInterval(this.executionTime);
	    }).catch(e => {
	      console.error('Error loading DurationFormatter:', e);
	    });
	  },
	  methods: {
	    showHint() {
	      const resultBlock = this.$refs.resultBlock;
	      if (resultBlock) {
	        const hintAnchor = main_core.Tag.render(_t2 || (_t2 = _`<span data-hint="${0}"></span>`), main_core.Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_TOOLTIP'));
	        main_core.Dom.append(hintAnchor, resultBlock);
	        BX.UI.Hint.init(resultBlock);
	      }
	    }
	  },
	  template: `
		<div class="crm-timeline__text-block crm-timeline__workflow-efficiency-block">
			<div class="bizproc-workflow-timeline-item --result">
				<div class="">
					<div class="bizproc-workflow-timeline-content">
						<div v-if="!userResult" class="bp-result">
							<div class="bizproc-workflow-timeline-caption">{{ resultCaption }}</div>
							<div class="bizproc-workflow-timeline-result" ref="resultBlock" v-html="workflowResultHtml"></div>
						</div>
						<div v-if="userResult" class="bp-result" v-html="userResult"></div>
					</div>
				</div>
			</div>
			<div class="bizproc-workflow-timeline-item --efficiency">
				<div class="bizproc-workflow-timeline-item-wrapper">
					<div class="bizproc-workflow-timeline-content">
						<div class="bizproc-workflow-timeline-content-inner">
							<div class="bizproc-workflow-timeline-caption">{{ efficiencyCaption }}</div>
							<div class="bizproc-workflow-timeline-notice">
								<div class="bizproc-workflow-timeline-subject">{{ processTimeText }}</div>
								<span class="bizproc-workflow-timeline-text">{{ formattedExecutionTime }}</span>
							</div>
							<div class="bizproc-workflow-timeline-notice">
								<div class="bizproc-workflow-timeline-subject">{{ averageDurationText }}</div>
								<span class="bizproc-workflow-timeline-text">{{ formattedAverageDuration }}</span>
							</div>
						</div>
						<div :class="itemClassName"></div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$7(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$7(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openEntityDetailTab = /*#__PURE__*/new WeakSet();
	var _editNote = /*#__PURE__*/new WeakSet();
	var _cancelEditNote = /*#__PURE__*/new WeakSet();
	let CommonContentBlocks = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CommonContentBlocks, _Base);
	  function CommonContentBlocks(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, CommonContentBlocks);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CommonContentBlocks).call(this, ...args));
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _cancelEditNote);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _editNote);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _openEntityDetailTab);
	    return _this;
	  }
	  babelHelpers.createClass(CommonContentBlocks, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        AddressBlock,
	        TextBlock: Text,
	        LinkBlock: Link,
	        LineOfTextBlocksButton,
	        DateBlock,
	        WithTitle,
	        LineOfTextBlocks,
	        TimelineAudio,
	        ClientMark: ClientMark$1,
	        Money,
	        EditableText,
	        EditableDescription,
	        EditableDate,
	        PlayerAlert,
	        RestAppLayoutBlocks,
	        DatePill,
	        Note,
	        FileList,
	        InfoGroup,
	        MoneyPill,
	        SmsMessage,
	        CommentContent,
	        ItemSelector,
	        PingSelector,
	        DeadlineAndPingSelector,
	        WorkflowEfficiency,
	        CallScoringPill,
	        CallScoring
	      };
	    }
	    /**
	     * Process common events that aren't bound to specific item type
	     */
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Item:OpenEntityDetailTab' && main_core.Type.isStringFilled(actionData === null || actionData === void 0 ? void 0 : actionData.tabId)) {
	        _classPrivateMethodGet$4(this, _openEntityDetailTab, _openEntityDetailTab2).call(this, actionData.tabId);
	      }
	      if (action === 'Note:StartEdit') {
	        _classPrivateMethodGet$4(this, _editNote, _editNote2).call(this, item);
	      }
	      if (action === 'Note:FinishEdit') {
	        _classPrivateMethodGet$4(this, _cancelEditNote, _cancelEditNote2).call(this, item);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return true; // common blocks can be used anywhere
	    }
	  }]);
	  return CommonContentBlocks;
	}(Base);
	function _openEntityDetailTab2(tabId) {
	  // the event is handled by compatible code, it's a pain to use EventEmitter in this case
	  // eslint-disable-next-line bitrix-rules/no-bx
	  BX.onCustomEvent(window, 'OpenEntityDetailTab', [tabId]);
	}
	function _editNote2(item) {
	  var _item$getLayoutConten;
	  (_item$getLayoutConten = item.getLayoutContentBlockById('note')) === null || _item$getLayoutConten === void 0 ? void 0 : _item$getLayoutConten.setEditMode(true);
	  item.highlightContentBlockById('note', true);
	}
	function _cancelEditNote2(item) {
	  var _item$getLayoutConten2;
	  (_item$getLayoutConten2 = item.getLayoutContentBlockById('note')) === null || _item$getLayoutConten2 === void 0 ? void 0 : _item$getLayoutConten2.setEditMode(false);
	  item.highlightContentBlockById('note', false);
	}

	var ChatMessage = {
	  props: {
	    messageHtml: String,
	    isIncoming: Boolean
	  },
	  computed: {
	    className() {
	      return 'crm-entity-stream-content-detail-IM-message-' + (this.isIncoming ? 'incoming' : 'outgoing');
	    }
	  },
	  // language=Vue
	  template: `<div class="crm-entity-stream-content-detail-IM"><div :class="[className]" v-html="messageHtml"></div></div>`
	};

	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$8(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$8(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openChat = /*#__PURE__*/new WeakSet();
	var _onComplete = /*#__PURE__*/new WeakSet();
	var _runCompleteAction = /*#__PURE__*/new WeakSet();
	let OpenLines = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(OpenLines, _Base);
	  function OpenLines(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, OpenLines);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLines).call(this, ...args));
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _runCompleteAction);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _onComplete);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _openChat);
	    return _this;
	  }
	  babelHelpers.createClass(OpenLines, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ChatMessage
	      };
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Openline:OpenChat' && actionData && actionData.dialogId) {
	        _classPrivateMethodGet$5(this, _openChat, _openChat2).call(this, actionData.dialogId);
	      }
	      if (action === 'Openline:Complete' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$5(this, _onComplete, _onComplete2).call(this, item, actionData, animationCallbacks);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:OpenLine';
	    }
	  }]);
	  return OpenLines;
	}(Base);
	function _openChat2(dialogId) {
	  if (window.top['BXIM']) {
	    window.top['BXIM'].openMessengerSlider(dialogId, {
	      RECENT: 'N',
	      MENU: 'N'
	    });
	  }
	}
	function _onComplete2(item, actionData, animationCallbacks) {
	  ui_dialogs_messagebox.MessageBox.show({
	    title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF_TITLE'),
	    message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF'),
	    modal: true,
	    okCaption: BX.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF_OK_TEXT'),
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    onOk: () => {
	      return _classPrivateMethodGet$5(this, _runCompleteAction, _runCompleteAction2).call(this, actionData.activityId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
	    },
	    onCancel: messageBox => {
	      const changeStreamButton = item.getLayoutHeaderChangeStreamButton();
	      if (changeStreamButton) {
	        changeStreamButton.markCheckboxUnchecked();
	      }
	      messageBox.close();
	    }
	  });
	}
	function _runCompleteAction2(activityId, ownerTypeId, ownerId, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  return main_core.ajax.runAction('crm.timeline.activity.complete', {
	    data: {
	      activityId,
	      ownerTypeId,
	      ownerId
	    }
	  }).then(() => {
	    if (animationCallbacks.onStop) {
	      animationCallbacks.onStop();
	    }
	    return true;
	  }, response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    if (animationCallbacks.onStop) {
	      animationCallbacks.onStop();
	    }
	    return true;
	  });
	}

	var ValueChange = {
	  props: {
	    from: Object,
	    to: Object
	  },
	  // language=Vue
	  template: `<div class="crm-entity-stream-content-detail-info">
	<component :is="from.rendererName" v-if="from" v-bind="from.properties"></component>
	<span class="crm-entity-stream-content-detail-info-separator-icon" v-if="from"></span>
	<component :is="to.rendererName" v-if="to" v-bind="to.properties"></component>
	</div>`
	};

	var ValueChangeItem = {
	  props: {
	    iconCode: String,
	    text: String,
	    pillText: String
	  },
	  computed: {
	    iconClassName() {
	      return ['crm-timeline__value-change-item_icon', {
	        [`--${this.iconCode}`]: true
	      }];
	    }
	  },
	  // language=Vue
	  template: `
		<div class="crm-timeline__value-change-item">
			<span v-if="iconCode" :class="iconClassName"></span>
			<span class="crm-timeline__value-change-item_text" v-if="text">{{ text }}</span>
			<span class="crm-entity-stream-content-detain-info-status" v-if="pillText">{{ pillText }}</span>
		</div>
	`
	};

	let Modification = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Modification, _Base);
	  function Modification() {
	    babelHelpers.classCallCheck(this, Modification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Modification).apply(this, arguments));
	  }
	  babelHelpers.createClass(Modification, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ValueChange,
	        ValueChangeItem
	      };
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Modification' || item.getType() === 'TasksTaskModification' || item.getType() === 'RestartAutomation';
	    }
	  }]);
	  return Modification;
	}(Base);

	function _classPrivateMethodInitSpec$6(obj, privateSet) { _checkPrivateRedeclaration$9(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$6(obj, privateMap, value) { _checkPrivateRedeclaration$9(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$9(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	let featureResolver = null;
	let api = null;
	main_core.Runtime.loadExtension(['sign.v2.api', 'sign.feature-resolver']).then(async exports => {
	  if (exports !== null && exports !== void 0 && exports.Api && exports !== null && exports !== void 0 && exports.FeatureResolver) {
	    featureResolver = exports === null || exports === void 0 ? void 0 : exports.FeatureResolver.instance();
	    api = new exports.Api();
	  }
	}).catch(errors => {
	  ui_notification.UI.Notification.Center.notify({
	    content: errors[0].message,
	    autoHideDelay: 5000
	  });
	});
	var _isCancellationInProgress = /*#__PURE__*/new WeakMap();
	var _cancelWithConfirm = /*#__PURE__*/new WeakSet();
	var _cancelSigningProcess = /*#__PURE__*/new WeakSet();
	var _deleteEntry = /*#__PURE__*/new WeakSet();
	var _showSigningProcess = /*#__PURE__*/new WeakSet();
	var _modifyDocument = /*#__PURE__*/new WeakSet();
	var _previewDocument = /*#__PURE__*/new WeakSet();
	var _createDocumentChat = /*#__PURE__*/new WeakSet();
	var _resendDocument = /*#__PURE__*/new WeakSet();
	var _touchSigner = /*#__PURE__*/new WeakSet();
	var _download = /*#__PURE__*/new WeakSet();
	let SignB2eDocument = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(SignB2eDocument, _Base);
	  function SignB2eDocument(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, SignB2eDocument);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SignB2eDocument).call(this, ...args));
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _download);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _touchSigner);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _resendDocument);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _createDocumentChat);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _previewDocument);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _modifyDocument);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _showSigningProcess);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _deleteEntry);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _cancelSigningProcess);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _cancelWithConfirm);
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _isCancellationInProgress, {
	      writable: true,
	      value: false
	    });
	    return _this;
	  }
	  babelHelpers.createClass(SignB2eDocument, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      const documentId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.documentId);
	      const processUri = actionData === null || actionData === void 0 ? void 0 : actionData.processUri;
	      const documentHash = (actionData === null || actionData === void 0 ? void 0 : actionData.documentHash) || '';
	      if (action === 'Activity:SignB2eDocument:ShowSigningCancel') {
	        _classPrivateMethodGet$6(this, _cancelWithConfirm, _cancelWithConfirm2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.documentUid);
	      } else if ((action === 'SignB2eDocument:ShowSigningProcess' || action === 'Activity:SignB2eDocument:ShowSigningProcess') && processUri.length > 0) {
	        _classPrivateMethodGet$6(this, _showSigningProcess, _showSigningProcess2).call(this, processUri);
	      } else if ((action === 'SignB2eDocument:Preview' || action === 'Activity:SignB2eDocument:Preview') && documentId > 0) {
	        _classPrivateMethodGet$6(this, _previewDocument, _previewDocument2).call(this, actionData);
	      } else if ((action === 'SignB2eDocument:CreateDocumentChat' || action === 'Activity:SignB2eDocument:CreateDocumentChat') && documentId > 0) {
	        if (featureResolver && featureResolver.released('createDocumentChat')) {
	          _classPrivateMethodGet$6(this, _createDocumentChat, _createDocumentChat2).call(this, actionData);
	        }
	      } else if ((action === 'SignB2eDocument:Modify' || action === 'Activity:SignB2eDocument:Modify') && documentId > 0) {
	        _classPrivateMethodGet$6(this, _modifyDocument, _modifyDocument2).call(this, actionData);
	      } else if (action === 'SignB2eDocument:Resend' && documentId > 0 && actionData !== null && actionData !== void 0 && actionData.recipientHash) {
	        // eslint-disable-next-line promise/catch-or-return
	        _classPrivateMethodGet$6(this, _resendDocument, _resendDocument2).call(this, actionData, animationCallbacks).then(() => {
	          if (actionData.buttonId) {
	            const btn = item.getLayoutFooterButtonById(actionData.buttonId);
	            btn.disableWithTimer(60);
	          }
	        });
	      } else if (action === 'SignB2eDocument:TouchSigner' && documentId > 0) {
	        _classPrivateMethodGet$6(this, _touchSigner, _touchSigner2).call(this, actionData);
	      } else if (action === 'SignB2eDocument:Download' && documentHash) {
	        _classPrivateMethodGet$6(this, _download, _download2).call(this, actionData, animationCallbacks);
	      } else if (action === 'SignB2eDocumentEntry:Delete' && actionData !== null && actionData !== void 0 && actionData.entryId) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: (actionData === null || actionData === void 0 ? void 0 : actionData.confirmationText) || '',
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	          onYes: () => {
	            return _classPrivateMethodGet$6(this, _deleteEntry, _deleteEntry2).call(this, actionData.entryId);
	          },
	          onNo: messageBox => {
	            messageBox.close();
	          }
	        });
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'SignB2eDocument' || item.getType() === 'Activity:SignB2eDocument';
	    }
	  }]);
	  return SignB2eDocument;
	}(Base);
	function _cancelWithConfirm2(documentUid) {
	  if (babelHelpers.classPrivateFieldGet(this, _isCancellationInProgress)) {
	    return;
	  }
	  const signingCancelationDialog = new ui_dialogs_messagebox.MessageBox({
	    title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_TITLE'),
	    message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_TEXT'),
	    modal: true
	  });
	  signingCancelationDialog.setButtons([new BX.UI.Button({
	    text: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_YES_BUTTON_TEXT'),
	    color: BX.UI.Button.Color.DANGER,
	    onclick: () => {
	      babelHelpers.classPrivateFieldSet(this, _isCancellationInProgress, true);
	      signingCancelationDialog.close();
	      _classPrivateMethodGet$6(this, _cancelSigningProcess, _cancelSigningProcess2).call(this, documentUid).finally(() => {
	        babelHelpers.classPrivateFieldSet(this, _isCancellationInProgress, false);
	      });
	    }
	  }), new BX.UI.Button({
	    text: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_NO_BUTTON_TEXT'),
	    color: BX.UI.Button.Color.LIGHT_BORDER,
	    onclick: () => {
	      signingCancelationDialog.close();
	    }
	  })]);
	  signingCancelationDialog.show();
	}
	function _cancelSigningProcess2(documentUid) {
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('sign.api_v1.document.signing.stop', {
	      data: {
	        uid: documentUid
	      },
	      preparePost: false,
	      headers: [{
	        name: 'Content-Type',
	        value: 'application/json'
	      }]
	    }).then(response => {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_SUCCESS'),
	        autoHideDelay: 5000
	      });
	      resolve(response);
	    }, response => {
	      response.errors.forEach(error => {
	        ui_notification.UI.Notification.Center.notify({
	          content: error.message,
	          autoHideDelay: 5000
	        });
	      });
	      reject(response.errors);
	    }).catch(() => {
	      reject();
	    });
	  });
	}
	function _deleteEntry2(entryId) {
	  console.log(`delete entry${entryId}`);
	}
	function _showSigningProcess2(processUri) {
	  return crm_router.Router.openSlider(processUri);
	}
	function _modifyDocument2({
	  documentId
	}) {
	  return crm_router.Router.openSlider(`/sign/b2e/doc/0/?docId=${documentId}&stepId=changePartner&noRedirect=Y`);
	}
	function _previewDocument2({
	  documentId
	}) {
	  return crm_router.Router.openSlider(`/sign/b2e/preview/0/?docId=${documentId}&noRedirect=Y`);
	}
	async function _createDocumentChat2({
	  chatType,
	  documentId
	}) {
	  if (api && featureResolver && featureResolver.released('createDocumentChat')) {
	    const chatId = (await api.createDocumentChat(chatType, documentId, false)).chatId;
	    im_public.Messenger.openChat(`chat${chatId}`);
	  }
	}
	function _resendDocument2({
	  documentId,
	  recipientHash
	}, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('sign.internal.document.resendFile', {
	      data: {
	        memberHash: recipientHash,
	        documentId
	      }
	    }).then(() => {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGN_DOCUMENT_RESEND_SUCCESS'),
	        autoHideDelay: 5000
	      });
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	      resolve();
	    }, response => {
	      ui_notification.UI.Notification.Center.notify({
	        content: response.errors[0].message,
	        autoHideDelay: 5000
	      });
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	      reject();
	    });
	    console.log(`resend document ${documentId} for ${recipientHash}`);
	  });
	}
	function _touchSigner2({
	  documentId
	}) {
	  console.log(`touch signer document ${documentId}`);
	}
	function _download2({
	  filename,
	  downloadLink
	}, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  const link = document.createElement('a');
	  link.href = downloadLink;
	  link.setAttribute('download', filename || '');
	  main_core.Dom.document.body.appendChild(link);
	  link.click();
	  document.body.removeChild(link);
	  if (animationCallbacks.onStop) {
	    animationCallbacks.onStop();
	  }
	}

	function _classPrivateMethodInitSpec$7(obj, privateSet) { _checkPrivateRedeclaration$a(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$a(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _deleteEntry$1 = /*#__PURE__*/new WeakSet();
	var _openDocument = /*#__PURE__*/new WeakSet();
	var _modifyDocument$1 = /*#__PURE__*/new WeakSet();
	var _updateActivityDeadline = /*#__PURE__*/new WeakSet();
	var _resendDocument$1 = /*#__PURE__*/new WeakSet();
	var _touchSigner$1 = /*#__PURE__*/new WeakSet();
	var _download$1 = /*#__PURE__*/new WeakSet();
	let SignDocument = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(SignDocument, _Base);
	  function SignDocument(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, SignDocument);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SignDocument).call(this, ...args));
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _download$1);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _touchSigner$1);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _resendDocument$1);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _updateActivityDeadline);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _modifyDocument$1);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _openDocument);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _deleteEntry$1);
	    return _this;
	  }
	  babelHelpers.createClass(SignDocument, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      const documentId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.documentId);
	      const documentHash = (actionData === null || actionData === void 0 ? void 0 : actionData.documentHash) || '';
	      const activityId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.activityId);
	      if ((action === 'SignDocument:Open' || action === 'Activity:SignDocument:Open') && documentId > 0) {
	        _classPrivateMethodGet$7(this, _openDocument, _openDocument2).call(this, actionData);
	      } else if ((action === 'SignDocument:Modify' || action === 'Activity:SignDocument:Modify') && documentId > 0) {
	        _classPrivateMethodGet$7(this, _modifyDocument$1, _modifyDocument2$1).call(this, actionData);
	      } else if ((action === 'SignDocument:UpdateActivityDeadline' || action === 'Activity:SignDocument:UpdateActivityDeadline') && activityId > 0) {
	        _classPrivateMethodGet$7(this, _updateActivityDeadline, _updateActivityDeadline2).call(this, activityId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	      } else if (action === 'SignDocument:Resend' && documentId > 0 && actionData !== null && actionData !== void 0 && actionData.recipientHash) {
	        _classPrivateMethodGet$7(this, _resendDocument$1, _resendDocument2$1).call(this, actionData, animationCallbacks).then(() => {
	          if (actionData.buttonId) {
	            const btn = item.getLayoutFooterButtonById(actionData.buttonId);
	            btn.disableWithTimer(60);
	          }
	        });
	      } else if (action === 'SignDocument:TouchSigner' && documentId > 0) {
	        _classPrivateMethodGet$7(this, _touchSigner$1, _touchSigner2$1).call(this, actionData);
	      } else if (action === 'SignDocument:Download' && documentHash) {
	        _classPrivateMethodGet$7(this, _download$1, _download2$1).call(this, actionData, animationCallbacks);
	      } else if (action === 'SignDocumentEntry:Delete' && actionData !== null && actionData !== void 0 && actionData.entryId) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: (actionData === null || actionData === void 0 ? void 0 : actionData.confirmationText) || '',
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	          onYes: () => {
	            return _classPrivateMethodGet$7(this, _deleteEntry$1, _deleteEntry2$1).call(this, actionData.entryId);
	          },
	          onNo: messageBox => {
	            messageBox.close();
	          }
	        });
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'SignDocument' || item.getType() === 'Activity:SignDocument';
	    }
	  }]);
	  return SignDocument;
	}(Base);
	function _deleteEntry2$1(entryId) {
	  console.log('delete entry' + entryId);
	}
	function _openDocument2({
	  documentId,
	  memberHash
	}) {
	  return crm_router.Router.Instance.openSignDocumentSlider(documentId, memberHash);
	}
	function _modifyDocument2$1({
	  documentId
	}) {
	  return crm_router.Router.Instance.openSignDocumentModifySlider(documentId);
	}
	async function _updateActivityDeadline2(activityId, value) {
	  var _response$data$docume;
	  const valueInSiteFormat = main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), value);
	  let response;
	  try {
	    response = await main_core.ajax.runAction('crm.timeline.signdocument.updateActivityDeadline', {
	      data: {
	        activityId: activityId,
	        activityDeadline: valueInSiteFormat
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    return;
	  }
	  const newCreateDate = (_response$data$docume = response.data.document) === null || _response$data$docume === void 0 ? void 0 : _response$data$docume.activityDeadline;
	  if (valueInSiteFormat !== newCreateDate) {
	    console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
	  }
	}
	function _resendDocument2$1({
	  documentId,
	  recipientHash
	}, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('sign.internal.document.resendFile', {
	      data: {
	        memberHash: recipientHash,
	        documentId: documentId
	      }
	    }).then(() => {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGN_DOCUMENT_RESEND_SUCCESS'),
	        autoHideDelay: 5000
	      });
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	      resolve();
	    }, response => {
	      ui_notification.UI.Notification.Center.notify({
	        content: response.errors[0].message,
	        autoHideDelay: 5000
	      });
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	      reject();
	    });
	    console.log('resend document ' + documentId + ' for ' + recipientHash);
	  });
	}
	function _touchSigner2$1({
	  documentId
	}) {
	  console.log('touch signer document ' + documentId);
	}
	function _download2$1({
	  filename,
	  downloadLink
	}, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  const link = document.createElement('a');
	  /*link.href = '/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc' +
	  	'&memberHash=' + memberHash +
	  	'&documentHash=' + documentHash;*/
	  link.href = downloadLink;
	  link.setAttribute('download', filename || '');
	  document.body.appendChild(link);
	  link.click();
	  document.body.removeChild(link);
	  if (animationCallbacks.onStop) {
	    animationCallbacks.onStop();
	  }
	}

	let _$1 = t => t,
	  _t$1;
	function _classPrivateMethodInitSpec$8(obj, privateSet) { _checkPrivateRedeclaration$b(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$7(obj, privateMap, value) { _checkPrivateRedeclaration$b(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$b(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet$1(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "get"); return _classApplyDescriptorGet$1(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor$1(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess$1(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet$1(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classPrivateMethodGet$8(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const ACTION_NAMESPACE = 'Document:';
	var _popupConfirm = /*#__PURE__*/new WeakMap();
	var _onJsEvent = /*#__PURE__*/new WeakSet();
	var _openDocument$1 = /*#__PURE__*/new WeakSet();
	var _copyPublicLink = /*#__PURE__*/new WeakSet();
	var _createPublicUrl = /*#__PURE__*/new WeakSet();
	var _printDocument = /*#__PURE__*/new WeakSet();
	var _downloadPdf = /*#__PURE__*/new WeakSet();
	var _downloadDocx = /*#__PURE__*/new WeakSet();
	var _updateTitle = /*#__PURE__*/new WeakSet();
	var _updateCreateDate = /*#__PURE__*/new WeakSet();
	var _deleteDocument = /*#__PURE__*/new WeakSet();
	var _onAjaxAction = /*#__PURE__*/new WeakSet();
	var _convertDeal = /*#__PURE__*/new WeakSet();
	var _showInfoHelperSlider = /*#__PURE__*/new WeakSet();
	var _showMessage = /*#__PURE__*/new WeakSet();
	let Document = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Document, _Base);
	  function Document(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Document);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this, ...args));
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _showMessage);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _showInfoHelperSlider);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _convertDeal);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _onAjaxAction);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _deleteDocument);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _updateCreateDate);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _updateTitle);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _downloadDocx);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _downloadPdf);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _printDocument);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _createPublicUrl);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _copyPublicLink);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _openDocument$1);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _onJsEvent);
	    _classPrivateFieldInitSpec$7(babelHelpers.assertThisInitialized(_this), _popupConfirm, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Document, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        response,
	        animationCallbacks
	      } = actionParams;
	      if (ActionType.isJsEvent(actionType)) {
	        _classPrivateMethodGet$8(this, _onJsEvent, _onJsEvent2).call(this, action, actionData, animationCallbacks, item);
	      } else if (ActionType.isAjaxAction(actionType)) {
	        _classPrivateMethodGet$8(this, _onAjaxAction, _onAjaxAction2).call(this, action, actionType, actionData, response);
	      }
	    }
	  }, {
	    key: "onAfterItemRefreshLayout",
	    value: function onAfterItemRefreshLayout(item) {
	      var _item$getLayout$asPla, _item$getLayout$asPla2, _item$getLayout$asPla3, _action$actionParams;
	      const itemsToPrint = _classStaticPrivateFieldSpecGet$1(Document, Document, _toPrintAfterRefresh).filter(candidate => candidate.getId() === item.getId());
	      if (itemsToPrint.length <= 0) {
	        return;
	      }
	      const action = (_item$getLayout$asPla = item.getLayout().asPlainObject().footer) === null || _item$getLayout$asPla === void 0 ? void 0 : (_item$getLayout$asPla2 = _item$getLayout$asPla.additionalButtons) === null || _item$getLayout$asPla2 === void 0 ? void 0 : (_item$getLayout$asPla3 = _item$getLayout$asPla2.extra) === null || _item$getLayout$asPla3 === void 0 ? void 0 : _item$getLayout$asPla3.action;
	      const isPrintEvent = main_core.Type.isPlainObject(action) && ActionType.isJsEvent(action.type) && action.value === ACTION_NAMESPACE + 'Print';
	      if (!isPrintEvent) {
	        return;
	      }
	      const printUrl = (_action$actionParams = action.actionParams) === null || _action$actionParams === void 0 ? void 0 : _action$actionParams.printUrl;
	      if (!main_core.Type.isStringFilled(printUrl)) {
	        return;
	      }
	      _classPrivateMethodGet$8(this, _printDocument, _printDocument2).call(this, printUrl, null, item);
	      _classStaticPrivateFieldSpecSet(Document, Document, _toPrintAfterRefresh, _classStaticPrivateFieldSpecGet$1(Document, Document, _toPrintAfterRefresh).filter(remainingItem => !itemsToPrint.includes(remainingItem)));
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Document' || item.getType() === 'DocumentViewed' || item.getType() === 'Activity:Document';
	    }
	  }]);
	  return Document;
	}(Base);
	function _onJsEvent2(action, actionData, animationCallbacks, item) {
	  const documentId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.documentId);
	  // if (documentId <= 0)
	  // {
	  // 	return;
	  // }
	  if (action === ACTION_NAMESPACE + 'Open') {
	    _classPrivateMethodGet$8(this, _openDocument$1, _openDocument2$1).call(this, documentId);
	  } else if (action === ACTION_NAMESPACE + 'CopyPublicLink') {
	    // todo block button while loading
	    _classPrivateMethodGet$8(this, _copyPublicLink, _copyPublicLink2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.publicUrl);
	  } else if (action === ACTION_NAMESPACE + 'Print') {
	    _classPrivateMethodGet$8(this, _printDocument, _printDocument2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.printUrl, animationCallbacks, item);
	  } else if (action === ACTION_NAMESPACE + 'DownloadPdf') {
	    _classPrivateMethodGet$8(this, _downloadPdf, _downloadPdf2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.pdfUrl);
	  } else if (action === ACTION_NAMESPACE + 'DownloadDocx') {
	    _classPrivateMethodGet$8(this, _downloadDocx, _downloadDocx2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.docxUrl);
	  } else if (action === ACTION_NAMESPACE + 'UpdateTitle') {
	    _classPrivateMethodGet$8(this, _updateTitle, _updateTitle2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	  } else if (action === ACTION_NAMESPACE + 'UpdateCreateDate') {
	    _classPrivateMethodGet$8(this, _updateCreateDate, _updateCreateDate2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	  } else if (action === ACTION_NAMESPACE + 'ConvertDeal') {
	    _classPrivateMethodGet$8(this, _convertDeal, _convertDeal2).call(this, documentId, animationCallbacks);
	  } else if (action === ACTION_NAMESPACE + 'ShowInfoHelperSlider') {
	    _classPrivateMethodGet$8(this, _showInfoHelperSlider, _showInfoHelperSlider2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.infoHelperCode);
	  } else if (action === ACTION_NAMESPACE + 'Delete') {
	    var _actionData$confirmat;
	    const confirmationText = (_actionData$confirmat = actionData.confirmationText) !== null && _actionData$confirmat !== void 0 ? _actionData$confirmat : '';
	    if (confirmationText) {
	      ui_dialogs_messagebox.MessageBox.show({
	        message: confirmationText,
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	        onYes: () => {
	          return _classPrivateMethodGet$8(this, _deleteDocument, _deleteDocument2).call(this, actionData.id, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
	        },
	        onNo: messageBox => {
	          messageBox.close();
	        }
	      });
	    } else {
	      _classPrivateMethodGet$8(this, _deleteDocument, _deleteDocument2).call(this, actionData.id, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
	    }
	  } else {
	    console.info(`Unknown action ${action} in ${item.getType()}`);
	  }
	}
	function _openDocument2$1(documentId) {
	  crm_router.Router.Instance.openDocumentSlider(documentId);
	}
	async function _copyPublicLink2(documentId, publicUrl) {
	  if (!main_core.Type.isStringFilled(publicUrl)) {
	    try {
	      publicUrl = await _classPrivateMethodGet$8(this, _createPublicUrl, _createPublicUrl2).call(this, documentId);
	    } catch (error) {
	      ui_dialogs_messagebox.MessageBox.alert(error.message);
	      return;
	    }
	  }
	  const isSuccess = BX.clipboard.copy(publicUrl);
	  if (isSuccess) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_LINK_IS_COPIED'),
	      autoHideDelay: 5000
	    });
	  } else {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_COPY_PUBLIC_LINK_ERROR'));
	  }
	}
	async function _createPublicUrl2(documentId) {
	  let response;
	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.enablePublicUrl', {
	      analyticsLabel: 'enablePublicUrl',
	      data: {
	        status: 1,
	        id: documentId
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    throw new Error(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
	  }
	  const publicUrl = response.data.publicUrl;
	  if (!main_core.Type.isStringFilled(publicUrl)) {
	    throw new Error(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
	  }
	  return publicUrl;
	}
	function _printDocument2(printUrl, animationCallbacks, item) {
	  if (main_core.Type.isStringFilled(printUrl)) {
	    window.open(printUrl, '_blank');
	    return;
	  }

	  // there is no pdf yet. wait till document is transformed and update push comes in
	  _classStaticPrivateFieldSpecGet$1(Document, Document, _toPrintAfterRefresh).push(item);
	  const onStart = animationCallbacks === null || animationCallbacks === void 0 ? void 0 : animationCallbacks.onStart;
	  if (main_core.Type.isFunction(onStart)) {
	    onStart();
	  }
	}
	function _downloadPdf2(pdfUrl) {
	  if (main_core.Type.isStringFilled(pdfUrl)) {
	    window.open(pdfUrl, '_blank');
	  } else {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_PDF_NOT_READY'));
	  }
	}
	function _downloadDocx2(docxUrl) {
	  if (main_core.Type.isStringFilled(docxUrl)) {
	    window.open(docxUrl, '_blank');
	  } else {
	    console.error('Docx download url is not found. This should be an impossible case, something went wrong');
	  }
	}
	async function _updateTitle2(documentId, value) {
	  var _response$data$docume, _response$data$docume2;
	  let response;
	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.update', {
	      data: {
	        id: documentId,
	        values: {
	          DocumentTitle: value
	        }
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));
	    return;
	  }
	  const newTitle = (_response$data$docume = response.data.document) === null || _response$data$docume === void 0 ? void 0 : (_response$data$docume2 = _response$data$docume.values) === null || _response$data$docume2 === void 0 ? void 0 : _response$data$docume2.DocumentTitle;
	  if (newTitle !== value) {
	    console.error("Updated document title without errors, but for some reason title from the backend doesn't match sent value");
	  }
	}
	async function _updateCreateDate2(documentId, value) {
	  var _response$data$docume3, _response$data$docume4;
	  const valueInSiteFormat = main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), value);
	  let response;
	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.update', {
	      data: {
	        id: documentId,
	        values: {
	          DocumentCreateTime: valueInSiteFormat
	        }
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));
	    return;
	  }
	  const newCreateDate = (_response$data$docume3 = response.data.document) === null || _response$data$docume3 === void 0 ? void 0 : (_response$data$docume4 = _response$data$docume3.values) === null || _response$data$docume4 === void 0 ? void 0 : _response$data$docume4.DocumentCreateTime;
	  if (valueInSiteFormat !== newCreateDate) {
	    console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
	  }
	}
	function _deleteDocument2(id, ownerTypeId, ownerId, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  return main_core.ajax.runAction('crm.timeline.document.delete', {
	    data: {
	      id,
	      ownerTypeId,
	      ownerId
	    }
	  }).then(() => {
	    if (animationCallbacks.onStop) {
	      animationCallbacks.onStop();
	    }
	    return true;
	  }, response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    if (animationCallbacks.onStop) {
	      animationCallbacks.onStop();
	    }
	    return true;
	  });
	}
	function _onAjaxAction2(action, actionType, actionData, response) {
	  if (action === 'crm.api.integration.sign.convertDeal') {
	    var _response$data;
	    if (actionType === ActionType.AJAX_ACTION.FINISHED && !main_core.Type.isNil(response === null || response === void 0 ? void 0 : (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.SMART_DOCUMENT)) {
	      //todo extract it to router?
	      const wizardUri = new main_core.Uri('/sign/doc/0/');
	      wizardUri.setQueryParams({
	        docId: response.data.SMART_DOCUMENT,
	        stepId: 'changePartner',
	        noRedirect: 'Y'
	      });
	      BX.SidePanel.Instance.open(wizardUri.toString());
	    }
	  }
	}
	function _convertDeal2(id, animationCallbacks) {
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  const convertDealAndStartSign = usePrevious => {
	    main_core.ajax.runAction('crm.api.integration.sign.convertDeal', {
	      data: {
	        documentId: id,
	        usePrevious: !usePrevious ? 0 : 1
	      }
	    }).then(response => {
	      var _response$data2;
	      if (response !== null && response !== void 0 && (_response$data2 = response.data) !== null && _response$data2 !== void 0 && _response$data2.SMART_DOCUMENT) {
	        const wizardUri = new main_core.Uri('/sign/doc/0/');
	        wizardUri.setQueryParams({
	          docId: response.data.SMART_DOCUMENT,
	          stepId: 'changePartner',
	          noRedirect: 'Y'
	        });
	        BX.SidePanel.Instance.open(wizardUri.toString());
	      }
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	    }, response => {
	      if (response.errors[0].message) {
	        ui_notification.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });
	      }
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	    }).catch(response => {
	      if (response.errors[0].message) {
	        ui_notification.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });
	      }
	      if (animationCallbacks.onStop) {
	        animationCallbacks.onStop();
	      }
	    });
	  };
	  main_core.ajax.runAction('crm.api.integration.sign.getLinkedBlank', {
	    data: {
	      documentId: id
	    }
	  }).then(response => {
	    var _response$data3;
	    if ((response === null || response === void 0 ? void 0 : (_response$data3 = response.data) === null || _response$data3 === void 0 ? void 0 : _response$data3.ID) > 0) {
	      _classPrivateMethodGet$8(this, _showMessage, _showMessage2).call(this, main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DO_USE_PREVIOUS_MSGVER_3', {
	        '%TITLE%': '<b>' + BX.util.htmlspecialchars(response.data.TITLE || '') + '</b>',
	        '%INITIATOR%': '<b>' + BX.util.htmlspecialchars(response.data.INITIATOR || '') + '</b>'
	      }), [new BX.UI.Button({
	        text: BX.message('CRM_TIMELINE_ITEM_ACTIVITY_OLD_BUTTON_MSGVER_2'),
	        className: "ui-btn ui-btn-md ui-btn-primary",
	        events: {
	          click: () => {
	            convertDealAndStartSign(true);
	            babelHelpers.classPrivateFieldGet(this, _popupConfirm).destroy();
	          }
	        }
	      }), new BX.UI.Button({
	        text: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_NEW_BUTTON_MSGVER_3'),
	        className: "ui-btn ui-btn-md ui-btn-info",
	        events: {
	          click: () => {
	            convertDealAndStartSign(false);
	            babelHelpers.classPrivateFieldGet(this, _popupConfirm).destroy();
	          }
	        }
	      })], main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_POPUP_TITLE_MSGVER_2'));
	    } else {
	      convertDealAndStartSign(false);
	    }
	  });
	}
	function _showInfoHelperSlider2(code) {
	  BX.UI.InfoHelper.show(code);
	}
	function _showMessage2(content, buttons, title) {
	  babelHelpers.classPrivateFieldSet(this, _popupConfirm, new BX.PopupWindow('bx-popup-document-activity-popup', null, {
	    zIndex: 200,
	    autoHide: true,
	    closeByEsc: true,
	    buttons: buttons,
	    closeIcon: true,
	    overlay: true,
	    events: {
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldGet(this, _popupConfirm).destroy();
	      }
	    },
	    content: main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="bx-popup-document-activity-popup-content-text">${0}</div>`), content),
	    titleBar: title,
	    className: 'bx-popup-document-activity-popup',
	    maxWidth: 510
	  }));
	  babelHelpers.classPrivateFieldGet(this, _popupConfirm).show();
	}
	var _toPrintAfterRefresh = {
	  writable: true,
	  value: []
	};

	function _classPrivateMethodInitSpec$9(obj, privateSet) { _checkPrivateRedeclaration$c(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$8(obj, privateMap, value) { _checkPrivateRedeclaration$c(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$c(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$9(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const COPILOT_BUTTON_DISABLE_DELAY = 5000;
	const COPILOT_BUTTON_NUMBER_OF_MANUAL_STARTS_WITHOUT_BOOST_LIMIT = 2;
	const COPILOT_BUTTON_NUMBER_OF_MANUAL_STARTS_WITH_BOOST_LIMIT = 5;
	const COPILOT_HELPDESK_CODE = 18799442;
	const FULL_SCENARIO = 'full';
	const FILL_FIELDS_SCENARIO = 'fill_fields';
	const CALL_SCORING_SCENARIO = 'call_scoring';
	var _isCopilotWelcomeTourShown = /*#__PURE__*/new WeakMap();
	var _isCopilotBannerShown = /*#__PURE__*/new WeakMap();
	var _makeCall = /*#__PURE__*/new WeakSet();
	var _openTranscript = /*#__PURE__*/new WeakSet();
	var _changePlayerState = /*#__PURE__*/new WeakSet();
	var _downloadRecord = /*#__PURE__*/new WeakSet();
	var _launchCopilot = /*#__PURE__*/new WeakSet();
	var _openCallScoringResult = /*#__PURE__*/new WeakSet();
	var _showAdditionalInfo = /*#__PURE__*/new WeakSet();
	var _showCopilotWelcomeTour = /*#__PURE__*/new WeakSet();
	var _bindAdditionalCopilotActions = /*#__PURE__*/new WeakSet();
	var _showMarketMessageBox = /*#__PURE__*/new WeakSet();
	var _showFeedbackMessageBox = /*#__PURE__*/new WeakSet();
	var _showCopilotBanner = /*#__PURE__*/new WeakSet();
	var _emitTimelineCopilotTourEvents = /*#__PURE__*/new WeakSet();
	var _emitTimelineCopilotTourEvent = /*#__PURE__*/new WeakSet();
	var _isSliderCodeExist = /*#__PURE__*/new WeakSet();
	var _isAiMarketplaceAppsExist = /*#__PURE__*/new WeakSet();
	var _isValidScenario = /*#__PURE__*/new WeakSet();
	let Call = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Call, _Base);
	  function Call(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Call);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Call).call(this, ...args));
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _isValidScenario);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _isAiMarketplaceAppsExist);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _isSliderCodeExist);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _emitTimelineCopilotTourEvent);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _emitTimelineCopilotTourEvents);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _showCopilotBanner);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _showFeedbackMessageBox);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _showMarketMessageBox);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _bindAdditionalCopilotActions);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _showCopilotWelcomeTour);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _showAdditionalInfo);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _openCallScoringResult);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _launchCopilot);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _downloadRecord);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _changePlayerState);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _openTranscript);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _makeCall);
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _isCopilotWelcomeTourShown, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _isCopilotBannerShown, {
	      writable: true,
	      value: false
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Call, [{
	    key: "onInitialize",
	    value: function onInitialize(item) {
	      _classPrivateMethodGet$9(this, _showCopilotWelcomeTour, _showCopilotWelcomeTour2).call(this, item);
	      _classPrivateMethodGet$9(this, _bindAdditionalCopilotActions, _bindAdditionalCopilotActions2).call(this, item);
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Call:MakeCall' && actionData) {
	        _classPrivateMethodGet$9(this, _makeCall, _makeCall2).call(this, actionData);
	      }
	      if (action === 'Call:Schedule' && actionData) {
	        this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
	      }
	      if (action === 'Call:OpenTranscript' && actionData && actionData.callId) {
	        _classPrivateMethodGet$9(this, _openTranscript, _openTranscript2).call(this, actionData.callId);
	      }
	      if (action === 'Call:ChangePlayerState' && actionData && actionData.recordId) {
	        _classPrivateMethodGet$9(this, _changePlayerState, _changePlayerState2).call(this, item, actionData.recordId);
	      }
	      if (action === 'Call:DownloadRecord' && actionData && actionData.url) {
	        _classPrivateMethodGet$9(this, _downloadRecord, _downloadRecord2).call(this, actionData.url);
	      }
	      if (action === 'Call:LaunchCopilot' && actionData) {
	        const isCopilotAgreementNeedShow = actionData.isCopilotAgreementNeedShow || false;
	        if (isCopilotAgreementNeedShow) {
	          main_core.Runtime.loadExtension('ai.copilot-agreement').then(({
	            CopilotAgreement
	          }) => {
	            const copilotAgreementPopup = new CopilotAgreement({
	              moduleId: 'crm',
	              contextId: 'audio',
	              events: {
	                onAccept: () => _classPrivateMethodGet$9(this, _launchCopilot, _launchCopilot2).call(this, item, actionData)
	              }
	            });
	            void copilotAgreementPopup.checkAgreement()
	            // eslint-disable-next-line promise/no-nesting
	            .then(isAgreementAccepted => {
	              if (isAgreementAccepted) {
	                _classPrivateMethodGet$9(this, _launchCopilot, _launchCopilot2).call(this, item, actionData);
	              }
	            });
	          }).catch(() => console.error('Cant load "ai.copilot-agreement" extension'));
	        } else {
	          _classPrivateMethodGet$9(this, _launchCopilot, _launchCopilot2).call(this, item, actionData);
	        }
	      }
	      if (action === 'Call:OpenCallScoringResult' && actionData) {
	        _classPrivateMethodGet$9(this, _openCallScoringResult, _openCallScoringResult2).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Call';
	    }
	  }]);
	  return Call;
	}(Base);
	function _makeCall2(actionData) {
	  if (!main_core.Type.isStringFilled(actionData.phone)) {
	    return;
	  }
	  const params = {
	    ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.entityTypeId),
	    ENTITY_ID: actionData.entityId,
	    AUTO_FOLD: true
	  };
	  if (actionData.ownerTypeId !== actionData.entityTypeId || actionData.ownerId !== actionData.entityId) {
	    params.BINDINGS = {
	      OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.ownerTypeId),
	      OWNER_ID: actionData.ownerId
	    };
	  }
	  if (actionData.activityId > 0) {
	    params.SRC_ACTIVITY_ID = actionData.activityId;
	  }
	  window.top['BXIM'].phoneTo(actionData.phone, params);
	}
	function _openTranscript2(callId) {
	  if (BX.Voximplant && BX.Voximplant.Transcript) {
	    BX.Voximplant.Transcript.create({
	      callId
	    }).show();
	  }
	}
	function _changePlayerState2(item, recordId) {
	  const player = item.getLayoutContentBlockById('audio');
	  if (!player) {
	    return;
	  }
	  if (recordId !== player.id) {
	    return;
	  }
	  if (player.state === 'play') {
	    player.pause();
	  } else {
	    player.play();
	  }
	}
	function _downloadRecord2(url) {
	  location.href = url;
	}
	function _launchCopilot2(item, actionData) {
	  const isValidParams = main_core.Type.isNumber(actionData.activityId) && main_core.Type.isNumber(actionData.ownerId) && main_core.Type.isNumber(actionData.ownerTypeId) && [BX.CrmEntityType.enumeration.lead, BX.CrmEntityType.enumeration.deal].includes(parseInt(actionData.ownerTypeId, 10));
	  if (!isValidParams) {
	    throw new Error('Invalid "actionData" parameters');
	  }
	  const aiCopilotBtn = item.getLayoutFooterButtonById('aiButton');
	  if (!aiCopilotBtn) {
	    throw new Error('"CoPilot" button is not found in layout');
	  }
	  const aiCopilotBtnUI = aiCopilotBtn.getUiButton();
	  const aiCopilotBtnUIPrevState = aiCopilotBtnUI.getState();
	  if (aiCopilotBtnUI.getState() === ui_buttons.ButtonState.AI_WAITING) {
	    return;
	  }

	  // start call record transcription
	  aiCopilotBtnUI.setState(ui_buttons.ButtonState.AI_WAITING);
	  main_core.ajax.runAction('crm.timeline.ai.launchCopilot', {
	    data: {
	      activityId: actionData.activityId,
	      ownerTypeId: actionData.ownerTypeId,
	      ownerId: actionData.ownerId,
	      scenario: _classPrivateMethodGet$9(this, _isValidScenario, _isValidScenario2).call(this, actionData) ? actionData.scenario : null
	    }
	  }).then(response => {
	    if ((response === null || response === void 0 ? void 0 : response.status) === 'success') {
	      var _response$data;
	      const numberOfManualStarts = response === null || response === void 0 ? void 0 : (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.numberOfManualStarts;
	      if (numberOfManualStarts >= COPILOT_BUTTON_NUMBER_OF_MANUAL_STARTS_WITH_BOOST_LIMIT) {
	        _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvent, _emitTimelineCopilotTourEvent2).call(this, aiCopilotBtnUI.getContainer(), 'BX.Crm.Timeline.Call:onShowTourWhenManualStartTooMuch', 'copilot-in-call-automatically', 500);
	        return;
	      }
	      if (numberOfManualStarts >= COPILOT_BUTTON_NUMBER_OF_MANUAL_STARTS_WITHOUT_BOOST_LIMIT) {
	        _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvent, _emitTimelineCopilotTourEvent2).call(this, aiCopilotBtnUI.getContainer(), 'BX.Crm.Timeline.Call:onShowTourWhenNeedBuyBoost', 'copilot-in-call-buying-boost', 500);
	      }
	    }
	  }).catch(response => {
	    const customData = response.errors[0].customData;
	    if (customData) {
	      customData.isCopilotBannerNeedShow = actionData.isCopilotBannerNeedShow || false;
	      _classPrivateMethodGet$9(this, _showAdditionalInfo, _showAdditionalInfo2).call(this, customData, item, actionData);
	      aiCopilotBtnUI.setState(aiCopilotBtnUIPrevState || ui_buttons.ButtonState.ACTIVE);
	    } else {
	      aiCopilotBtnUI.setState(ui_buttons.ButtonState.DISABLED);
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Text.encode(response.errors[0].message),
	        autoHideDelay: COPILOT_BUTTON_DISABLE_DELAY
	      });
	      setTimeout(() => {
	        aiCopilotBtnUI.setState(ui_buttons.ButtonState.ACTIVE);
	      }, COPILOT_BUTTON_DISABLE_DELAY);
	    }
	    throw response;
	  });
	}
	function _openCallScoringResult2(actionData) {
	  var _actionData$activityC, _actionData$clientDet, _actionData$clientFul, _actionData$userPhoto, _actionData$jobId, _actionData$assessmen;
	  if (!main_core.Type.isInteger(actionData.activityId) || !main_core.Type.isInteger(actionData.ownerTypeId) || !main_core.Type.isInteger(actionData.ownerId)) {
	    return;
	  }
	  const callScoring = new crm_ai_call.Call.CallQuality({
	    activityId: actionData.activityId,
	    ownerTypeId: actionData.ownerTypeId,
	    ownerId: actionData.ownerId,
	    activityCreated: (_actionData$activityC = actionData.activityCreated) !== null && _actionData$activityC !== void 0 ? _actionData$activityC : null,
	    clientDetailUrl: (_actionData$clientDet = actionData.clientDetailUrl) !== null && _actionData$clientDet !== void 0 ? _actionData$clientDet : null,
	    clientFullName: (_actionData$clientFul = actionData.clientFullName) !== null && _actionData$clientFul !== void 0 ? _actionData$clientFul : null,
	    userPhotoUrl: (_actionData$userPhoto = actionData.userPhotoUrl) !== null && _actionData$userPhoto !== void 0 ? _actionData$userPhoto : null,
	    jobId: (_actionData$jobId = actionData.jobId) !== null && _actionData$jobId !== void 0 ? _actionData$jobId : null,
	    assessmentSettingsId: (_actionData$assessmen = actionData.assessmentSettingsId) !== null && _actionData$assessmen !== void 0 ? _actionData$assessmen : null
	  });
	  callScoring.open();
	}
	function _showAdditionalInfo2(data, item, actionData) {
	  if (_classPrivateMethodGet$9(this, _isSliderCodeExist, _isSliderCodeExist2).call(this, data)) {
	    if (data.sliderCode === 'limit_boost_copilot') {
	      main_core.Runtime.loadExtension('baas.store').then(({
	        ServiceWidget,
	        Analytics
	      }) => {
	        var _item$getLayoutFooter, _item$getLayoutFooter2;
	        if (!ServiceWidget) {
	          var _BX, _BX$UI;
	          (_BX = BX) === null || _BX === void 0 ? void 0 : (_BX$UI = _BX.UI) === null || _BX$UI === void 0 ? void 0 : _BX$UI.InfoHelper.show('limit_boost_copilot');
	          console.error('Cant load "baas.store" extension');
	        }
	        const serviceWidget = ServiceWidget === null || ServiceWidget === void 0 ? void 0 : ServiceWidget.getInstanceByCode('ai_copilot_token');
	        const bindElement = (_item$getLayoutFooter = item.getLayoutFooterButtonById('aiButton')) === null || _item$getLayoutFooter === void 0 ? void 0 : (_item$getLayoutFooter2 = _item$getLayoutFooter.getUiButton()) === null || _item$getLayoutFooter2 === void 0 ? void 0 : _item$getLayoutFooter2.getContainer();
	        serviceWidget.bind(bindElement, Analytics.CONTEXT_CRM);
	        serviceWidget.show(bindElement);
	        serviceWidget.getPopup().adjustPosition({
	          forceTop: true
	        });
	      }).catch(() => {
	        var _BX2, _BX2$UI;
	        (_BX2 = BX) === null || _BX2 === void 0 ? void 0 : (_BX2$UI = _BX2.UI) === null || _BX2$UI === void 0 ? void 0 : _BX2$UI.InfoHelper.show('limit_boost_copilot');
	        console.error('Cant load "baas.store" extension');
	      });
	    } else {
	      var _BX3, _BX3$UI;
	      (_BX3 = BX) === null || _BX3 === void 0 ? void 0 : (_BX3$UI = _BX3.UI) === null || _BX3$UI === void 0 ? void 0 : _BX3$UI.InfoHelper.show(data.sliderCode);
	    }
	  } else if (_classPrivateMethodGet$9(this, _isAiMarketplaceAppsExist, _isAiMarketplaceAppsExist2).call(this, data)) {
	    if (!babelHelpers.classPrivateFieldGet(this, _isCopilotBannerShown) && data.isCopilotBannerNeedShow) {
	      _classPrivateMethodGet$9(this, _showCopilotBanner, _showCopilotBanner2).call(this, item, actionData);
	    } else {
	      _classPrivateMethodGet$9(this, _showMarketMessageBox, _showMarketMessageBox2).call(this);
	    }
	  } else {
	    _classPrivateMethodGet$9(this, _showFeedbackMessageBox, _showFeedbackMessageBox2).call(this);
	  }
	}
	function _showCopilotWelcomeTour2(item) {
	  if (!item) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _isCopilotWelcomeTourShown)) {
	    return;
	  }
	  const payload = main_core.Type.isPlainObject(item.getDataPayload()) ? item.getDataPayload() : {};
	  setTimeout(() => {
	    const aiCopilotBtn = item.getLayoutFooterButtonById('aiButton');
	    const aiCopilotUIBtn = aiCopilotBtn === null || aiCopilotBtn === void 0 ? void 0 : aiCopilotBtn.getUiButton();
	    if (!aiCopilotUIBtn || aiCopilotUIBtn.getState() === ui_buttons.ButtonState.DISABLED) {
	      return;
	    }
	    if (aiCopilotBtn !== null && aiCopilotBtn !== void 0 && aiCopilotBtn.isInViewport()) {
	      _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvents, _emitTimelineCopilotTourEvents2).call(this, aiCopilotUIBtn.getContainer(), 1500, payload);
	      return;
	    }
	    const showCopilotTourOnScroll = () => {
	      if (aiCopilotBtn !== null && aiCopilotBtn !== void 0 && aiCopilotBtn.isInViewport()) {
	        _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvents, _emitTimelineCopilotTourEvents2).call(this, aiCopilotUIBtn.getContainer(), 1500, payload);
	        babelHelpers.classPrivateFieldSet(this, _isCopilotWelcomeTourShown, true);
	        main_core.Event.unbind(window, 'scroll', showCopilotTourOnScroll);
	      }
	    };
	    main_core.Event.bind(window, 'scroll', showCopilotTourOnScroll);
	  }, 50);
	}
	function _bindAdditionalCopilotActions2(item) {
	  if (!item) {
	    return;
	  }
	  setTimeout(() => {
	    const player = item === null || item === void 0 ? void 0 : item.getLayoutContentBlockById('audio');
	    if (!player) {
	      return;
	    }
	    main_core_events.EventEmitter.subscribe('ui:audioplayer:pause', event => {
	      const {
	        initiator
	      } = event.getData();
	      const aiCopilotBtn = item.getLayoutFooterButtonById('aiButton');
	      const aiCopilotUIBtn = aiCopilotBtn === null || aiCopilotBtn === void 0 ? void 0 : aiCopilotBtn.getUiButton();
	      if (!aiCopilotUIBtn || aiCopilotUIBtn.getState() === ui_buttons.ButtonState.DISABLED || !(aiCopilotBtn !== null && aiCopilotBtn !== void 0 && aiCopilotBtn.isPropEqual('data-activity-id', initiator))) {
	        return;
	      }
	      _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvents, _emitTimelineCopilotTourEvents2).call(this, aiCopilotUIBtn.getContainer(), 500);
	    });
	  }, 75);
	}
	function _showMarketMessageBox2() {
	  ui_dialogs_messagebox.MessageBox.show({
	    title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_TITLE'),
	    message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_TEXT', {
	      '[helpdesklink]': `<br><br><a href="##" onclick="top.BX.Helper.show('redirect=detail&code=${COPILOT_HELPDESK_CODE}');">`,
	      '[/helpdesklink]': '</a>'
	    }),
	    modal: true,
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    okCaption: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_OK_TEXT'),
	    onOk: () => {
	      return crm_router.Router.openSlider(main_core.Loc.getMessage('AI_APP_COLLECTION_MARKET_LINK'));
	    },
	    onCancel: messageBox => {
	      messageBox.close();
	    }
	  });
	}
	function _showFeedbackMessageBox2() {
	  ui_dialogs_messagebox.MessageBox.show({
	    title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_TITLE'),
	    message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_TEXT'),
	    modal: true,
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    okCaption: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_OK_TEXT'),
	    onOk: messageBox => {
	      messageBox.close();
	      BX.UI.Feedback.Form.open({
	        id: 'b24_ai_provider_partner_crm_feedback',
	        defaultForm: {
	          id: 682,
	          lang: 'en',
	          sec: '3sd3le'
	        },
	        forms: [{
	          zones: ['cn'],
	          id: 678,
	          lang: 'cn',
	          sec: 'wyufoe'
	        }, {
	          zones: ['vn'],
	          id: 680,
	          lang: 'vn',
	          sec: '2v97xr'
	        }]
	      });
	    },
	    onCancel: messageBox => {
	      messageBox.close();
	    }
	  });
	}
	async function _showCopilotBanner2() {
	  const {
	    AppsInstallerBanner,
	    AppsInstallerBannerEvents
	  } = await main_core.Runtime.loadExtension('ai.copilot-banner');
	  const portalZone = main_core.Loc.getMessage('PORTAL_ZONE');
	  const copilotBannerOptions = {
	    isWestZone: portalZone !== 'ru' && portalZone !== 'by' && portalZone !== 'kz'
	  };
	  const copilotBanner = new AppsInstallerBanner(copilotBannerOptions);
	  copilotBanner.show();
	  copilotBanner.subscribe(AppsInstallerBannerEvents.actionStart, () => {
	    // eslint-disable-next-line no-console
	    console.info('Install app started');
	  });
	  copilotBanner.subscribe(AppsInstallerBannerEvents.actionFinishSuccess, () => {
	    setTimeout(() => {
	      new ai_engine.Engine().setBannerLaunched().then(() => {}).catch(() => {});

	      // eslint-disable-next-line no-console
	      console.info('App installed successfully');
	      babelHelpers.classPrivateFieldSet(this, _isCopilotBannerShown, true);
	    }, 500);
	  });
	  copilotBanner.subscribe(AppsInstallerBannerEvents.actionFinishFailed, () => {
	    console.error('Install app failed. Try installing the application manually.');
	    setTimeout(() => {
	      _classPrivateMethodGet$9(this, _showMarketMessageBox, _showMarketMessageBox2).call(this);
	    }, 500);
	  });
	}
	function _emitTimelineCopilotTourEvents2(target, delay = 1500, payload = null) {
	  var _payload$isWelcomeTou, _payload$isWelcomeTou2, _payload$isWelcomeTou3;
	  const isWelcomeTourEnabled = (_payload$isWelcomeTou = payload === null || payload === void 0 ? void 0 : payload.isWelcomeTourEnabled) !== null && _payload$isWelcomeTou !== void 0 ? _payload$isWelcomeTou : true;
	  const isWelcomeTourAutomaticallyEnabled = (_payload$isWelcomeTou2 = payload === null || payload === void 0 ? void 0 : payload.isWelcomeTourAutomaticallyEnabled) !== null && _payload$isWelcomeTou2 !== void 0 ? _payload$isWelcomeTou2 : true;
	  const isWelcomeTourManuallyEnabled = (_payload$isWelcomeTou3 = payload === null || payload === void 0 ? void 0 : payload.isWelcomeTourManuallyEnabled) !== null && _payload$isWelcomeTou3 !== void 0 ? _payload$isWelcomeTou3 : true;
	  if (isWelcomeTourEnabled) {
	    _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvent, _emitTimelineCopilotTourEvent2).call(this, target, 'BX.Crm.Timeline.Call:onShowCopilotTour', 'copilot-button-in-call', delay);
	  }
	  if (isWelcomeTourAutomaticallyEnabled) {
	    _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvent, _emitTimelineCopilotTourEvent2).call(this, target, 'BX.Crm.Timeline.Call:onShowTourWhenCopilotAutomaticallyStart', 'copilot-button-in-call-automatically', delay);
	  }
	  if (isWelcomeTourManuallyEnabled) {
	    _classPrivateMethodGet$9(this, _emitTimelineCopilotTourEvent, _emitTimelineCopilotTourEvent2).call(this, target, 'BX.Crm.Timeline.Call:onShowTourWhenCopilotManuallyStart', 'copilot-button-in-call-manually', delay);
	  }
	}
	function _emitTimelineCopilotTourEvent2(target, eventName, stepId, delay = 1500) {
	  main_core_events.EventEmitter.emit(this, eventName, {
	    target,
	    stepId,
	    delay
	  });
	}
	function _isSliderCodeExist2(data) {
	  return Object.hasOwn(data, 'sliderCode') && main_core.Type.isStringFilled(data.sliderCode);
	}
	function _isAiMarketplaceAppsExist2(data) {
	  return Object.hasOwn(data, 'isAiMarketplaceAppsExist') && main_core.Type.isBoolean(data.isAiMarketplaceAppsExist) && data.isAiMarketplaceAppsExist;
	}
	function _isValidScenario2(actionData) {
	  return main_core.Type.isStringFilled(actionData.scenario) && [FULL_SCENARIO, FILL_FIELDS_SCENARIO, CALL_SCORING_SCENARIO].includes(actionData.scenario);
	}

	function _classPrivateMethodInitSpec$a(obj, privateSet) { _checkPrivateRedeclaration$d(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$9(obj, privateMap, value) { _checkPrivateRedeclaration$d(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$d(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$a(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _responsibleUserSelectorDialog = /*#__PURE__*/new WeakMap();
	var _showFileUploaderPopup = /*#__PURE__*/new WeakSet();
	var _showResponsibleUserSelector = /*#__PURE__*/new WeakSet();
	var _emitRepeatTodo = /*#__PURE__*/new WeakSet();
	var _emitUpdateTodo = /*#__PURE__*/new WeakSet();
	var _runUpdateColorAction = /*#__PURE__*/new WeakSet();
	var _showCalendar = /*#__PURE__*/new WeakSet();
	var _runResponsibleUserAction = /*#__PURE__*/new WeakSet();
	var _openClient = /*#__PURE__*/new WeakSet();
	var _openUser = /*#__PURE__*/new WeakSet();
	let ToDo = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(ToDo, _Base);
	  function ToDo(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, ToDo);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ToDo).call(this, ...args));
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _openUser);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _openClient);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _runResponsibleUserAction);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _showCalendar);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _runUpdateColorAction);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _emitUpdateTodo);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _emitRepeatTodo);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _showResponsibleUserSelector);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _showFileUploaderPopup);
	    _classPrivateFieldInitSpec$9(babelHelpers.assertThisInitialized(_this), _responsibleUserSelectorDialog, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(ToDo, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'ColorSelector:Change' && actionData) {
	        _classPrivateMethodGet$a(this, _runUpdateColorAction, _runUpdateColorAction2).call(this, item, actionData);
	      }
	      if (action === 'EditableDescription:StartEdit') {
	        item.highlightContentBlockById('description', true);
	      }
	      if (action === 'EditableDescription:FinishEdit') {
	        item.highlightContentBlockById('description', false);
	      }
	      if (action === 'Activity:ToDo:AddFile' && actionData) {
	        _classPrivateMethodGet$a(this, _showFileUploaderPopup, _showFileUploaderPopup2).call(this, item, actionData);
	      }
	      if (action === 'Activity:ToDo:ChangeResponsible' && actionData) {
	        _classPrivateMethodGet$a(this, _showResponsibleUserSelector, _showResponsibleUserSelector2).call(this, item, actionData);
	      }
	      if (action === 'Activity:ToDo:Repeat' && actionData) {
	        _classPrivateMethodGet$a(this, _emitRepeatTodo, _emitRepeatTodo2).call(this, item, actionData);
	      }
	      if (action === 'Activity:ToDo:Update' && actionData) {
	        _classPrivateMethodGet$a(this, _emitUpdateTodo, _emitUpdateTodo2).call(this, item, actionData);
	      }
	      if (action === 'Activity:ToDo:ShowCalendar' && actionData) {
	        _classPrivateMethodGet$a(this, _showCalendar, _showCalendar2).call(this, item, actionData);
	      }
	      if (action === 'Activity:ToDo:Client:Click' && actionData) {
	        _classPrivateMethodGet$a(this, _openClient, _openClient2).call(this, actionData.entityId, actionData.entityTypeId);
	      }
	      if (action === 'Activity:ToDo:User:Click' && actionData) {
	        _classPrivateMethodGet$a(this, _openUser, _openUser2).call(this, actionData.userId);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:ToDo';
	    }
	  }]);
	  return ToDo;
	}(Base);
	function _showFileUploaderPopup2(item, actionData) {
	  const isValidParams = main_core.Type.isNumber(actionData.entityId) && main_core.Type.isNumber(actionData.entityTypeId) && main_core.Type.isNumber(actionData.ownerId) && main_core.Type.isNumber(actionData.ownerTypeId);
	  if (!isValidParams) {
	    return;
	  }
	  actionData.files = actionData.files.split(',').filter(id => main_core.Type.isNumber(id));
	  const fileList = item.getLayoutContentBlockById('fileList');
	  if (fileList) {
	    fileList.showFileUploaderPopup(actionData);
	  } else {
	    const popup = new crm_activity_fileUploaderPopup.FileUploaderPopup(actionData);
	    popup.show();
	  }
	}
	function _showResponsibleUserSelector2(item, actionData) {
	  const isValidParams = main_core.Type.isNumber(actionData.id) && main_core.Type.isNumber(actionData.ownerId) && main_core.Type.isNumber(actionData.ownerTypeId) && main_core.Type.isNumber(actionData.responsibleId);
	  if (!isValidParams) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _responsibleUserSelectorDialog, new ui_entitySelector.Dialog({
	    id: 'responsible-user-selector-dialog-' + actionData.id,
	    targetNode: item.getLayoutFooterMenu().$el,
	    context: 'CRM_ACTIVITY_TODO_RESPONSIBLE_USER',
	    multiple: false,
	    dropdownMode: true,
	    showAvatars: true,
	    enableSearch: true,
	    width: 450,
	    entities: [{
	      id: 'user'
	    }],
	    preselectedItems: [['user', actionData.responsibleId]],
	    undeselectedItems: [['user', actionData.responsibleId]],
	    events: {
	      'Item:onSelect': event => {
	        const selectedItem = event.getData().item.getDialog().getSelectedItems()[0];
	        if (selectedItem) {
	          _classPrivateMethodGet$a(this, _runResponsibleUserAction, _runResponsibleUserAction2).call(this, actionData.id, actionData.ownerId, actionData.ownerTypeId, selectedItem.getId());
	        }
	      },
	      'Item:onDeselect': event => {
	        setTimeout(() => {
	          const selectedItems = babelHelpers.classPrivateFieldGet(this, _responsibleUserSelectorDialog).getSelectedItems();
	          if (selectedItems.length === 0) {
	            babelHelpers.classPrivateFieldGet(this, _responsibleUserSelectorDialog).hide();
	            _classPrivateMethodGet$a(this, _runResponsibleUserAction, _runResponsibleUserAction2).call(this, actionData.id, actionData.ownerId, actionData.ownerTypeId, actionData.responsibleId);
	          }
	        }, 100);
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _responsibleUserSelectorDialog).show();
	}
	function _emitRepeatTodo2(item, actionData) {
	  main_core_events.EventEmitter.emit('crm:timeline:todo:repeat', actionData);
	}
	function _emitUpdateTodo2(item, actionData) {
	  main_core_events.EventEmitter.emit('crm:timeline:todo:update', actionData);
	}
	function _runUpdateColorAction2(item, actionData) {
	  const {
	    id,
	    ownerTypeId,
	    ownerId
	  } = item.getDataPayload();
	  const {
	    colorId
	  } = actionData;
	  const isValidParams = main_core.Type.isNumber(id) && main_core.Type.isNumber(ownerId) && main_core.Type.isNumber(ownerTypeId) && main_core.Type.isStringFilled(colorId);
	  if (!isValidParams) {
	    return;
	  }
	  const data = {
	    ownerTypeId,
	    ownerId,
	    id,
	    colorId
	  };
	  main_core.ajax.runAction('crm.activity.todo.updateColor', {
	    data
	  }).catch(response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    throw response;
	  });
	}
	function _showCalendar2(item, actionData) {
	  const {
	    calendarEventId,
	    entryDateFrom,
	    timezoneOffset
	  } = actionData;
	  if (!window.top.BX.Calendar) {
	    // eslint-disable-next-line no-console
	    console.warn('BX.Calendar not found');
	    return;
	  }
	  new window.top.BX.Calendar.SliderLoader(calendarEventId, {
	    entryDateFrom,
	    timezoneOffset,
	    calendarContext: null
	  }).show();
	}
	function _runResponsibleUserAction2(id, ownerId, ownerTypeId, responsibleId) {
	  const data = {
	    ownerTypeId,
	    ownerId,
	    id,
	    responsibleId
	  };
	  main_core.ajax.runAction('crm.activity.todo.updateResponsibleUser', {
	    data
	  }).catch(response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    throw response;
	  });
	}
	function _openClient2(entityId, entityTypeId) {
	  if (ui_sidepanel.SidePanel.Instance) {
	    const entityTypeName = BX.CrmEntityType.resolveName(entityTypeId).toLowerCase();
	    const path = `/crm/${entityTypeName}/details/${entityId}/`;
	    ui_sidepanel.SidePanel.Instance.open(path);
	  }
	}
	function _openUser2(userId) {
	  if (ui_sidepanel.SidePanel.Instance) {
	    const path = `/company/personal/user/${userId}/`;
	    ui_sidepanel.SidePanel.Instance.open(path);
	  }
	}

	function _classPrivateMethodInitSpec$b(obj, privateSet) { _checkPrivateRedeclaration$e(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$e(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$b(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openHelpdesk = /*#__PURE__*/new WeakSet();
	let Helpdesk = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Helpdesk, _Base);
	  function Helpdesk(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Helpdesk);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Helpdesk).call(this, ...args));
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _openHelpdesk);
	    return _this;
	  }
	  babelHelpers.createClass(Helpdesk, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionData
	      } = actionParams;
	      if (action === 'Helpdesk:Open' && actionData && actionData.articleCode) {
	        _classPrivateMethodGet$b(this, _openHelpdesk, _openHelpdesk2).call(this, actionData.articleCode);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return true;
	    }
	  }]);
	  return Helpdesk;
	}(Base);
	function _openHelpdesk2(articleCode) {
	  if (top.BX && top.BX.Helper) {
	    top.BX.Helper.show(`redirect=detail&code=${articleCode}`);
	  }
	}

	function _classPrivateMethodInitSpec$c(obj, privateSet) { _checkPrivateRedeclaration$f(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$f(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$c(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openRealization = /*#__PURE__*/new WeakSet();
	let Payment = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Payment, _Base);
	  function Payment(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Payment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Payment).call(this, ...args));
	    _classPrivateMethodInitSpec$c(babelHelpers.assertThisInitialized(_this), _openRealization);
	    return _this;
	  }
	  babelHelpers.createClass(Payment, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Payment:OpenRealization' && actionData !== null && actionData !== void 0 && actionData.paymentId) {
	        _classPrivateMethodGet$c(this, _openRealization, _openRealization2).call(this, actionData.paymentId);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Payment' || item.getType() === 'Activity:Payment';
	    }
	  }]);
	  return Payment;
	}(Base);
	function _openRealization2(paymentId) {
	  const control = BX.Crm.EntityEditor.getDefault().getControlByIdRecursive('OPPORTUNITY_WITH_CURRENCY');
	  if (!control) {
	    return;
	  }
	  const paymentDocumentsControl = control.getPaymentDocumentsControl();
	  if (!paymentDocumentsControl) {
	    return;
	  }
	  paymentDocumentsControl._createRealizationSlider({
	    paymentId
	  });
	}

	var ListItemButton = {
	  props: {
	    text: {
	      type: String,
	      required: true
	    },
	    action: Object
	  },
	  methods: {
	    executeAction() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }
	  },
	  // language=Vue
	  template: `
		<div class="crm-entity-stream-advice-list-btn-box">
			<button
				@click="executeAction"
				class="crm-entity-stream-advice-list-btn"
			>
				{{text}}
			</button>
		</div>
	`
	};

	var ListItem = {
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    titleAction: Object,
	    isSelected: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    image: String,
	    showDummyImage: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    bottomBlock: Object,
	    button: Object
	  },
	  components: {
	    Text,
	    Link,
	    ListItemButton
	  },
	  computed: {
	    imageStyle() {
	      if (!this.image) {
	        return {};
	      }
	      return {
	        backgroundImage: 'url(' + this.image + ')'
	      };
	    }
	  },
	  // language=Vue
	  template: `
		<li
			:class="{'crm-entity-stream-advice-list-item--active': isSelected}"
			class="crm-entity-stream-advice-list-item"
		>
			<div class="crm-entity-stream-advice-list-content">
				<div
					v-if="image || showDummyImage"
					:style="imageStyle"
					class="crm-entity-stream-advice-list-icon"
				>
				</div>
				<div class="crm-entity-stream-advice-list-inner">
					<Link v-if="titleAction" :action="titleAction" :text="title"></Link>
					<Text v-else :value="title"></Text>
					<div v-if="bottomBlock" class="crm-entity-stream-advice-list-desc-box">
						<LineOfTextBlocks v-bind="bottomBlock.properties"></LineOfTextBlocks>
					</div>
				</div>
			</div>
			<ListItemButton v-if="button" v-bind="button.properties"></ListItemButton>
		</li>
	`
	};

	var ExpandableList = {
	  props: {
	    listItems: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    showMoreEnabled: {
	      type: Boolean,
	      required: true
	    },
	    showMoreCnt: {
	      type: Number,
	      required: false
	    },
	    showMoreText: {
	      type: String,
	      required: false
	    }
	  },
	  data() {
	    return {
	      isShortList: this.showMoreEnabled,
	      shortListItemsCnt: this.showMoreCnt
	    };
	  },
	  components: {
	    ListItem
	  },
	  methods: {
	    showMore() {
	      this.isShortList = false;
	    },
	    isItemVisible(index) {
	      return !this.isShortList || index < this.showMoreCnt;
	    }
	  },
	  computed: {
	    isShowMoreVisible() {
	      return this.isShortList && this.listItems.length > this.shortListItemsCnt;
	    }
	  },
	  // language=Vue
	  template: `
		<div>
			<div v-if="title" class="crm-entity-stream-advice-title">
				{{title}}
			</div>
			<transition-group class="crm-entity-stream-advice-list" name="list" tag="ul">
				<ListItem
					v-for="(item, index) in listItems"
					v-show="isItemVisible(index)"
					:key="item.id"
					v-bind="item.properties"
				></ListItem>
			</transition-group>
			<a
				v-if="isShowMoreVisible"
				@click.prevent="showMore"
				class="crm-entity-stream-advice-link"
				href="#"
			>
				{{showMoreText}}
			</a>
		</div>
	`
	};

	function _classPrivateMethodInitSpec$d(obj, privateSet) { _checkPrivateRedeclaration$g(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$a(obj, privateMap, value) { _checkPrivateRedeclaration$g(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$g(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$d(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _item = /*#__PURE__*/new WeakMap();
	var _productsGrid = /*#__PURE__*/new WeakMap();
	var _addProductToDeal = /*#__PURE__*/new WeakSet();
	let DealProductList = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(DealProductList, _Base);
	  function DealProductList(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, DealProductList);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DealProductList).call(this, ...args));
	    _classPrivateMethodInitSpec$d(babelHelpers.assertThisInitialized(_this), _addProductToDeal);
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _item, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _productsGrid, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(DealProductList, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ExpandableList
	      };
	    }
	  }, {
	    key: "onInitialize",
	    value: function onInitialize(item) {
	      babelHelpers.classPrivateFieldSet(this, _item, item);
	      main_core_events.EventEmitter.subscribe('onCrmEntityUpdate', () => {
	        babelHelpers.classPrivateFieldGet(this, _item).reloadFromServer();
	      });

	      /**
	       * For cases when timeline block controller initialization runs after product grid initialization
	       */
	      BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', controller => {
	        babelHelpers.classPrivateFieldSet(this, _productsGrid, controller.getProductList());
	      });

	      /**
	       * For cases when timeline block controller initialization runs before product grid initialization
	       */
	      main_core_events.EventEmitter.subscribe('EntityProductListController', event => {
	        babelHelpers.classPrivateFieldSet(this, _productsGrid, event.getData()[0]);
	      });
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'ProductList:AddToDeal') {
	        _classPrivateMethodGet$d(this, _addProductToDeal, _addProductToDeal2).call(this, actionData, animationCallbacks);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'ProductCompilation:SentToClient' || item.getType() === 'Order:EncourageBuyProducts';
	    }
	  }]);
	  return DealProductList;
	}(Base);
	function _addProductToDeal2(actionData, animationCallbacks) {
	  if (!(actionData && actionData.productId)) {
	    return;
	  }
	  if (animationCallbacks.onStart) {
	    animationCallbacks.onStart();
	  }
	  BX.onCustomEvent('onAddViewedProductToDeal', [actionData.productId]);
	  setTimeout(() => {
	    BX.onCustomEvent('OpenEntityDetailTab', ['tab_products']);
	  }, 500);
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
	    autoHideDelay: 5000
	  });
	  if (animationCallbacks.onStop) {
	    animationCallbacks.onStop();
	  }
	}

	var ContactList = {
	  props: {
	    contactBlocks: Array
	  },
	  template: `
	  	<div class="crm-timeline-block-mail-contacts-wrapper">
			<div class="crm-timeline-block-mail-contact" v-for="(block, index) in contactBlocks">
			  <component :is="block.rendererName" v-bind="block.properties"></component>
			</div>
		</div>
	`
	};

	function _classPrivateMethodInitSpec$e(obj, privateSet) { _checkPrivateRedeclaration$h(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$h(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$e(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _viewActivity$1 = /*#__PURE__*/new WeakSet();
	var _getActivityEditor$1 = /*#__PURE__*/new WeakSet();
	var _openMessage = /*#__PURE__*/new WeakSet();
	let Email = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Email, _Base);
	  function Email(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Email);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Email).call(this, ...args));
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _openMessage);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getActivityEditor$1);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _viewActivity$1);
	    return _this;
	  }
	  babelHelpers.createClass(Email, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Email::OpenMessage' && actionData) {
	        _classPrivateMethodGet$e(this, _openMessage, _openMessage2).call(this, actionData);
	      }
	      if (action === 'Email::Schedule' && actionData) {
	        this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
	      }
	    }
	  }, {
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ContactList
	      };
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      const supportedItemTypes = ['ContactList', 'Activity:Email', 'EmailActivitySuccessfullyDelivered', 'EmailActivityNonDelivered', 'EmailLogIncomingMessage'];
	      return supportedItemTypes.includes(item.getType());
	    }
	  }]);
	  return Email;
	}(Base);
	function _viewActivity2$1(id) {
	  const editor = _classPrivateMethodGet$e(this, _getActivityEditor$1, _getActivityEditor2$1).call(this);
	  if (editor && id) {
	    const emailActivity = BX.CrmActivityEmail.create({
	      ID: id
	    }, editor, {});
	    emailActivity.openDialog(BX.CrmDialogMode.view);
	  }
	}
	function _getActivityEditor2$1() {
	  return BX.CrmActivityEditor.getDefault();
	}
	function _openMessage2(actionData) {
	  if (!main_core.Type.isNumber(actionData.threadId)) {
	    return;
	  }
	  _classPrivateMethodGet$e(this, _viewActivity$1, _viewActivity2$1).call(this, actionData.threadId);
	}

	let OrderCheck = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(OrderCheck, _Base);
	  function OrderCheck() {
	    babelHelpers.classCallCheck(this, OrderCheck);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderCheck).apply(this, arguments));
	  }
	  babelHelpers.createClass(OrderCheck, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'OrderCheck:OpenCheck' && actionData && actionData.checkUrl) {
	        crm_router.Router.openSlider(actionData.checkUrl, {
	          width: 500,
	          cacheable: false
	        });
	      } else if (action === 'OrderCheck:ReprintCheck' && actionData && actionData.checkId) {
	        main_core.ajax.runAction('crm.ordercheck.reprint', {
	          data: {
	            checkId: actionData.checkId
	          }
	        }).catch(response => {
	          ui_notification.UI.Notification.Center.notify({
	            content: response.errors[0].message,
	            autoHideDelay: 5000
	          });
	        });
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'OrderCheckPrinted' || item.getType() === 'OrderCheckNotPrinted' || item.getType() === 'OrderCheckSent' || item.getType() === 'OrderCheckPrinting';
	    }
	  }]);
	  return OrderCheck;
	}(Base);

	const EcommerceDocumentsList = {
	  props: {
	    ownerId: {
	      type: Number,
	      required: true
	    },
	    ownerTypeId: {
	      type: Number,
	      required: true
	    },
	    isWithOrdersMode: {
	      type: Boolean,
	      required: true
	    },
	    summaryOptions: {
	      type: Object,
	      required: true
	    }
	  },
	  mounted() {
	    const timelineSummaryDocuments = new crm_entityEditor_field_paymentDocuments.TimelineSummaryDocuments({
	      'OWNER_ID': this.ownerId,
	      'OWNER_TYPE_ID': this.ownerTypeId,
	      'PARENT_CONTEXT': this,
	      'CONTEXT': BX.CrmEntityType.resolveName(this.ownerTypeId).toLowerCase(),
	      'IS_WITH_ORDERS_MODE': this.isWithOrdersMode
	    });
	    timelineSummaryDocuments.setOptions(this.summaryOptions);
	    this.$el.appendChild(timelineSummaryDocuments.render());
	  },
	  methods: {
	    startSalescenterApplication(orderId, options) {
	      if (options === undefined) {
	        return;
	      }
	      BX.loadExt('salescenter.manager').then(() => {
	        BX.Salescenter.Manager.openApplication(options);
	      });
	    }
	  },
	  template: `<div></div>`
	};

	let FinalSummary = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(FinalSummary, _Base);
	  function FinalSummary() {
	    babelHelpers.classCallCheck(this, FinalSummary);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FinalSummary).apply(this, arguments));
	  }
	  babelHelpers.createClass(FinalSummary, [{
	    key: "onAfterItemLayout",
	    value: function onAfterItemLayout(item, options) {
	      if (item.needBindToContainer()) {
	        main_core_events.EventEmitter.emit('BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded', [item.getWrapper()]);
	      }
	    }
	  }, {
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        EcommerceDocumentsList
	      };
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'FinalSummary';
	    }
	  }]);
	  return FinalSummary;
	}(Base);

	function _classPrivateMethodInitSpec$f(obj, privateSet) { _checkPrivateRedeclaration$i(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$i(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$f(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _startSalescenterApp = /*#__PURE__*/new WeakSet();
	let SalescenterApp = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(SalescenterApp, _Base);
	  function SalescenterApp(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, SalescenterApp);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SalescenterApp).call(this, ...args));
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _startSalescenterApp);
	    return _this;
	  }
	  babelHelpers.createClass(SalescenterApp, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'SalescenterApp:Start' && actionData) {
	        _classPrivateMethodGet$f(this, _startSalescenterApp, _startSalescenterApp2).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      const supportedItemTypes = ['Activity:Sms', 'Activity:Notification', 'Activity:Payment', 'PaymentViewed', 'PaymentNotViewed', 'PaymentSent', 'PaymentPaid', 'PaymentNotPaid', 'PaymentError', 'PaymentSentToTerminal', 'Activity:Delivery', 'CustomerSelectedPaymentMethod'];
	      return supportedItemTypes.includes(item.getType());
	    }
	  }]);
	  return SalescenterApp;
	}(Base);
	function _startSalescenterApp2(actionData) {
	  if (!(main_core.Type.isInteger(actionData.ownerTypeId) && main_core.Type.isInteger(actionData.ownerId) && main_core.Type.isInteger(actionData.orderId) && main_core.Type.isStringFilled(actionData.mode))) {
	    return;
	  }
	  BX.loadExt('salescenter.manager').then(() => {
	    const params = {
	      ownerTypeId: actionData.ownerTypeId,
	      ownerId: actionData.ownerId,
	      orderId: actionData.orderId,
	      mode: actionData.mode,
	      disableSendButton: '',
	      context: 'deal',
	      templateMode: 'view'
	    };
	    if (main_core.Type.isInteger(actionData.paymentId)) {
	      params.paymentId = actionData.paymentId;
	    }
	    if (main_core.Type.isInteger(actionData.shipmentId)) {
	      params.shipmentId = actionData.shipmentId;
	    }
	    if (main_core.Type.isStringFilled(actionData.analyticsLabel)) {
	      params.analyticsLabel = actionData.analyticsLabel;
	    }
	    BX.Salescenter.Manager.openApplication(params);
	  });
	}

	function _classPrivateMethodInitSpec$g(obj, privateSet) { _checkPrivateRedeclaration$j(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$b(obj, privateMap, value) { _checkPrivateRedeclaration$j(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$j(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$g(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _needCheckRequestStatus = /*#__PURE__*/new WeakMap();
	var _checkRequestStatusTimeout = /*#__PURE__*/new WeakMap();
	var _isPullSubscribed = /*#__PURE__*/new WeakMap();
	var _makeCall$1 = /*#__PURE__*/new WeakSet();
	var _subscribePullEvents = /*#__PURE__*/new WeakSet();
	var _subscribeShipmentEvents = /*#__PURE__*/new WeakSet();
	var _subscribeDeliveryServiceEvents = /*#__PURE__*/new WeakSet();
	var _subscribeDeliveryRequestEvents = /*#__PURE__*/new WeakSet();
	var _checkRequestStatus = /*#__PURE__*/new WeakSet();
	var _updateCheckRequestStatus = /*#__PURE__*/new WeakSet();
	var _getDeliveryRequest = /*#__PURE__*/new WeakSet();
	var _getDeliveryServiceIds = /*#__PURE__*/new WeakSet();
	var _getShipmentIds = /*#__PURE__*/new WeakSet();
	let Delivery = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Delivery, _Base);
	  function Delivery(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Delivery);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Delivery).call(this, ...args));
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _getShipmentIds);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _getDeliveryServiceIds);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _getDeliveryRequest);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _updateCheckRequestStatus);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _checkRequestStatus);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _subscribeDeliveryRequestEvents);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _subscribeDeliveryServiceEvents);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _subscribeShipmentEvents);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _subscribePullEvents);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _makeCall$1);
	    _classPrivateFieldInitSpec$b(babelHelpers.assertThisInitialized(_this), _needCheckRequestStatus, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$b(babelHelpers.assertThisInitialized(_this), _checkRequestStatusTimeout, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$b(babelHelpers.assertThisInitialized(_this), _isPullSubscribed, {
	      writable: true,
	      value: false
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Delivery, [{
	    key: "onInitialize",
	    value: function onInitialize(item) {
	      _classPrivateMethodGet$g(this, _updateCheckRequestStatus, _updateCheckRequestStatus2).call(this, item);
	      _classPrivateMethodGet$g(this, _subscribePullEvents, _subscribePullEvents2).call(this, item);
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Delivery:MakeCall' && actionData) {
	        _classPrivateMethodGet$g(this, _makeCall$1, _makeCall2$1).call(this, actionData);
	      }
	    }
	  }, {
	    key: "onAfterItemRefreshLayout",
	    value: function onAfterItemRefreshLayout(item) {
	      _classPrivateMethodGet$g(this, _updateCheckRequestStatus, _updateCheckRequestStatus2).call(this, item);
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Delivery';
	    }
	  }]);
	  return Delivery;
	}(Base);
	function _makeCall2$1(actionData) {
	  if (!main_core.Type.isStringFilled(actionData.phoneNumber) || !main_core.Type.isBoolean(actionData.canUserPerformCalls)) {
	    return;
	  }
	  if (!main_core.Type.isUndefined(window.top['BXIM']) && actionData.canUserPerformCalls !== false) {
	    window.top['BXIM'].phoneTo(actionData.phoneNumber);
	  } else {
	    window.open('tel:' + actionData.phoneNumber, '_self');
	  }
	}
	function _subscribePullEvents2(item) {
	  if (babelHelpers.classPrivateFieldGet(this, _isPullSubscribed)) {
	    return;
	  }
	  _classPrivateMethodGet$g(this, _subscribeShipmentEvents, _subscribeShipmentEvents2).call(this, item);
	  _classPrivateMethodGet$g(this, _subscribeDeliveryServiceEvents, _subscribeDeliveryServiceEvents2).call(this, item);
	  _classPrivateMethodGet$g(this, _subscribeDeliveryRequestEvents, _subscribeDeliveryRequestEvents2).call(this, item);
	  babelHelpers.classPrivateFieldSet(this, _isPullSubscribed, true);
	}
	function _subscribeShipmentEvents2(item) {
	  const shipmentIds = _classPrivateMethodGet$g(this, _getShipmentIds, _getShipmentIds2).call(this, item);
	  pull_client.PULL.subscribe({
	    moduleId: 'crm',
	    command: 'onOrderShipmentSave',
	    callback: params => {
	      if (shipmentIds.some(id => id == params.FIELDS.ID)) {
	        item.reloadFromServer();
	      }
	    }
	  });
	  pull_client.PULL.extendWatch('CRM_ENTITY_ORDER_SHIPMENT');
	}
	function _subscribeDeliveryServiceEvents2(item) {
	  const deliveryServiceIds = _classPrivateMethodGet$g(this, _getDeliveryServiceIds, _getDeliveryServiceIds2).call(this, item);
	  pull_client.PULL.subscribe({
	    moduleId: 'sale',
	    command: 'onDeliveryServiceSave',
	    callback: params => {
	      if (deliveryServiceIds.some(id => id == params.ID)) {
	        item.reloadFromServer();
	      }
	    }
	  });
	  pull_client.PULL.extendWatch('SALE_DELIVERY_SERVICE');
	}
	function _subscribeDeliveryRequestEvents2(item) {
	  const deliveryRequest = _classPrivateMethodGet$g(this, _getDeliveryRequest, _getDeliveryRequest2).call(this, item);
	  pull_client.PULL.subscribe({
	    moduleId: 'sale',
	    command: 'onDeliveryRequestUpdate',
	    callback: params => {
	      if (deliveryRequest && deliveryRequest.id == params.ID) {
	        item.reloadFromServer();
	      }
	    }
	  });
	  pull_client.PULL.subscribe({
	    moduleId: 'sale',
	    command: 'onDeliveryRequestDelete',
	    callback: params => {
	      if (deliveryRequest && deliveryRequest.id == params.ID) {
	        item.reloadFromServer();
	      }
	    }
	  });
	  pull_client.PULL.extendWatch('SALE_DELIVERY_REQUEST');
	}
	function _checkRequestStatus2() {
	  main_core.ajax.runAction('crm.timeline.deliveryactivity.checkrequeststatus');
	}
	function _updateCheckRequestStatus2(item) {
	  const deliveryRequest = _classPrivateMethodGet$g(this, _getDeliveryRequest, _getDeliveryRequest2).call(this, item);
	  const needCheckRequestStatus = deliveryRequest && deliveryRequest.isProcessed === false;
	  if (needCheckRequestStatus && !babelHelpers.classPrivateFieldGet(this, _needCheckRequestStatus)) {
	    clearTimeout(babelHelpers.classPrivateFieldGet(this, _checkRequestStatusTimeout));
	    babelHelpers.classPrivateFieldSet(this, _checkRequestStatusTimeout, setInterval(() => _classPrivateMethodGet$g(this, _checkRequestStatus, _checkRequestStatus2).call(this), 30 * 1000));
	  } else if (!needCheckRequestStatus && babelHelpers.classPrivateFieldGet(this, _needCheckRequestStatus)) {
	    clearTimeout(babelHelpers.classPrivateFieldGet(this, _checkRequestStatusTimeout));
	  }
	  babelHelpers.classPrivateFieldSet(this, _needCheckRequestStatus, needCheckRequestStatus);
	}
	function _getDeliveryRequest2(item) {
	  const dataPayload = item.getDataPayload();
	  if (!main_core.Type.isObject(dataPayload.deliveryRequest)) {
	    return null;
	  }
	  return dataPayload.deliveryRequest;
	}
	function _getDeliveryServiceIds2(item) {
	  const dataPayload = item.getDataPayload();
	  if (!main_core.Type.isArray(dataPayload.deliveryServiceIds)) {
	    return [];
	  }
	  return dataPayload.deliveryServiceIds;
	}
	function _getShipmentIds2(item) {
	  const dataPayload = item.getDataPayload();
	  if (!main_core.Type.isArray(dataPayload.shipmentIds)) {
	    return [];
	  }
	  return dataPayload.shipmentIds;
	}

	function _classPrivateMethodInitSpec$h(obj, privateSet) { _checkPrivateRedeclaration$k(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$k(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$h(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openRestAppSlider = /*#__PURE__*/new WeakSet();
	let RestApp = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(RestApp, _Base);
	  function RestApp(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, RestApp);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(RestApp).call(this, ...args));
	    _classPrivateMethodInitSpec$h(babelHelpers.assertThisInitialized(_this), _openRestAppSlider);
	    return _this;
	  }
	  babelHelpers.createClass(RestApp, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (!ActionType.isJsEvent(actionType)) {
	        return;
	      }
	      if (action === 'Activity:ConfigurableRestApp:OpenApp') {
	        _classPrivateMethodGet$h(this, _openRestAppSlider, _openRestAppSlider2).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:ConfigurableRestApp';
	    }
	  }]);
	  return RestApp;
	}(Base);
	function _openRestAppSlider2(params) {
	  const openAppParams = {
	    ...params
	  };
	  const appId = openAppParams.restAppId;
	  delete openAppParams.restAppId;
	  if (BX.rest && BX.rest.AppLayout) {
	    if (main_core.Type.isStringFilled(openAppParams.bx24_label)) {
	      openAppParams.bx24_label = JSON.parse(openAppParams.bx24_label);
	    }
	    BX.rest.AppLayout.openApplication(appId, openAppParams);
	  }
	}

	function _classPrivateMethodInitSpec$i(obj, privateSet) { _checkPrivateRedeclaration$l(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$l(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$i(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _showEditor = /*#__PURE__*/new WeakSet();
	var _onCommentDelete = /*#__PURE__*/new WeakSet();
	var _isValidParams = /*#__PURE__*/new WeakSet();
	let Comment = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Comment, _Base);
	  function Comment(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Comment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Comment).call(this, ...args));
	    _classPrivateMethodInitSpec$i(babelHelpers.assertThisInitialized(_this), _isValidParams);
	    _classPrivateMethodInitSpec$i(babelHelpers.assertThisInitialized(_this), _onCommentDelete);
	    _classPrivateMethodInitSpec$i(babelHelpers.assertThisInitialized(_this), _showEditor);
	    return _this;
	  }
	  babelHelpers.createClass(Comment, [{
	    key: "getDeleteActionMethod",
	    value: function getDeleteActionMethod() {
	      return 'crm.timeline.comment.delete';
	    }
	  }, {
	    key: "getDeleteActionCfg",
	    value: function getDeleteActionCfg(recordId, ownerTypeId, ownerId) {
	      return {
	        data: {
	          id: recordId,
	          ownerTypeId: ownerTypeId,
	          ownerId: ownerId
	        }
	      };
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Comment:Edit' || action === 'Comment:AddFile') {
	        _classPrivateMethodGet$i(this, _showEditor, _showEditor2).call(this, item);
	      }
	      if (action === 'Comment:Delete' && actionData) {
	        _classPrivateMethodGet$i(this, _onCommentDelete, _onCommentDelete2).call(this, actionData, animationCallbacks);
	      }
	      if (action === 'Comment:StartEdit') {
	        item.highlightContentBlockById('commentContentWeb', true);
	      }
	      if (action === 'Comment:FinishEdit') {
	        item.highlightContentBlockById('commentContentWeb', false);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Comment';
	    }
	  }]);
	  return Comment;
	}(Base);
	function _showEditor2(item) {
	  const commentBlock = item.getLayoutContentBlockById('commentContentWeb');
	  if (commentBlock) {
	    commentBlock.startEditing();
	  } else {
	    throw new Error('Vue component "CommentContent" was not found');
	  }
	}
	function _onCommentDelete2(actionData, animationCallbacks) {
	  if (!_classPrivateMethodGet$i(this, _isValidParams, _isValidParams2).call(this, actionData)) {
	    return;
	  }
	  const confirmationText = main_core.Type.isStringFilled(actionData.confirmationText) ? actionData.confirmationText : '';
	  if (confirmationText) {
	    ui_dialogs_messagebox.MessageBox.show({
	      message: confirmationText,
	      modal: true,
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	      onYes: () => {
	        return this.runDeleteAction(actionData.commentId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
	      },
	      onNo: messageBox => {
	        messageBox.close();
	      }
	    });
	  } else {
	    this.runDeleteAction(actionData.commentId, actionData.ownerTypeId, actionData.ownerId);
	  }
	}
	function _isValidParams2(params) {
	  return main_core.Type.isNumber(params.commentId) && main_core.Type.isNumber(params.ownerId) && main_core.Type.isNumber(params.ownerTypeId);
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1,
	  _t3;
	var SharingSlotsList = {
	  data() {
	    return {
	      popup: null,
	      moreLinkRef: null
	    };
	  },
	  props: {
	    listItems: {
	      type: Array,
	      required: true,
	      default: []
	    }
	  },
	  mounted() {
	    const moreLink = this.$el.querySelector('[data-anchor="more-link"]');
	    if (!moreLink) {
	      return;
	    }
	    this.moreLinkRef = moreLink;
	    main_core.Event.bind(this.moreLinkRef, 'click', () => this.openPopup());
	    main_core.Dom.append(main_core.Tag.render(_t$2 || (_t$2 = _$2`<i/>`)), this.moreLinkRef);
	  },
	  computed: {
	    items() {
	      return this.listItems.map(item => item.properties);
	    },
	    formattedRules() {
	      return this.items.map(item => this.createItemText(item));
	    },
	    firstFormattedRule() {
	      var _this$formattedRules$;
	      if (this.doShowMoreLink) {
	        return main_core.Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_WITH_MORE', {
	          '#RANGE#': this.formattedRules[0],
	          '#MORE_LINK_CLASS#': 'crm-timeline-calendar-sharing-slots-more',
	          '#AMOUNT#': this.items.length - 1
	        });
	      }
	      return (_this$formattedRules$ = this.formattedRules[0]) !== null && _this$formattedRules$ !== void 0 ? _this$formattedRules$ : '';
	    },
	    formattedDuration() {
	      return main_core.Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_DURATION', {
	        '#DURATION#': this.items[0].durationFormatted
	      });
	    },
	    doShowMoreLink() {
	      return this.items.length > 1;
	    }
	  },
	  methods: {
	    createItemText(item) {
	      return main_core.Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_V3', {
	        '#WEEKDAYS#': main_core.Text.encode(item.weekdaysFormatted),
	        '#FROM_TIME#': this.formatMinutes(item.rule.from),
	        '#TO_TIME#': this.formatMinutes(item.rule.to)
	      });
	    },
	    formatMinutes(minutes) {
	      const date = new Date(calendar_util.Util.parseDate('01.01.2000').getTime() + minutes * 60 * 1000);
	      return calendar_util.Util.formatTime(date);
	    },
	    openPopup() {
	      var _this$popup;
	      if (!this.moreLinkRef || (_this$popup = this.popup) !== null && _this$popup !== void 0 && _this$popup.isShown()) {
	        return;
	      }
	      this.popup = new main_popup.Popup(this.getPopupOptions());
	      this.popup.show();
	    },
	    getPopupOptions() {
	      return {
	        content: this.getPopupContent(),
	        autoHide: true,
	        cacheable: false,
	        animation: 'fading-slide',
	        bindElement: this.moreLinkRef,
	        closeByEsc: true
	      };
	    },
	    getPopupContent() {
	      const root = main_core.Tag.render(_t2$1 || (_t2$1 = _$2`<div></div>`));
	      this.formattedRules.forEach(item => {
	        main_core.Dom.append(main_core.Tag.render(_t3 || (_t3 = _$2`<div class="crm-timeline-calendar-sharing-slots-more-popup-item">${0}</div>`), item), root);
	      });
	      return root;
	    }
	  },
	  template: `
		<div class="crm-timeline-calendar-sharing-slots">
			<div class="crm-timeline-calendar-sharing-slots-block" v-html="firstFormattedRule"/>
			<div class="crm-timeline-calendar-sharing-slots-block">
				{{formattedDuration}}
			</div>
		</div>
	`
	};

	let _$3 = t => t,
	  _t$3;
	function _classPrivateMethodInitSpec$j(obj, privateSet) { _checkPrivateRedeclaration$m(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$m(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$j(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openCalendarEvent = /*#__PURE__*/new WeakSet();
	var _startVideoconference = /*#__PURE__*/new WeakSet();
	var _openMembersPopup = /*#__PURE__*/new WeakSet();
	var _renderMemberMenuItem = /*#__PURE__*/new WeakSet();
	let Sharing = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Sharing, _Base);
	  function Sharing(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Sharing);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sharing).call(this, ...args));
	    _classPrivateMethodInitSpec$j(babelHelpers.assertThisInitialized(_this), _renderMemberMenuItem);
	    _classPrivateMethodInitSpec$j(babelHelpers.assertThisInitialized(_this), _openMembersPopup);
	    _classPrivateMethodInitSpec$j(babelHelpers.assertThisInitialized(_this), _startVideoconference);
	    _classPrivateMethodInitSpec$j(babelHelpers.assertThisInitialized(_this), _openCalendarEvent);
	    return _this;
	  }
	  babelHelpers.createClass(Sharing, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'CalendarSharingInvitationSent:ShowMembers' || action === 'Activity:CalendarSharing:ShowMembers') {
	        _classPrivateMethodGet$j(this, _openMembersPopup, _openMembersPopup2).call(this, item, Object.values(actionData.members));
	      }
	      if (action === 'Activity:CalendarSharing:OpenCalendarEvent') {
	        _classPrivateMethodGet$j(this, _openCalendarEvent, _openCalendarEvent2).call(this, item, actionData);
	      }
	      if (action === 'Activity:CalendarSharing:StartVideoconference') {
	        _classPrivateMethodGet$j(this, _startVideoconference, _startVideoconference2).call(this, item, actionData);
	      }
	      if (action === 'CalendarSharingLinkCopied:OpenPublicPageInNewTab') {
	        window.open(actionData.url);
	      }
	      if (action === 'CalendarSharingInvitationSent:ShowQr') {
	        const dialogQr = new calendar_sharing_interface.DialogQr({
	          sharingUrl: actionData.url,
	          context: 'crm'
	        });
	        dialogQr.show();
	      }
	      if (action === 'Activity:CalendarSharing:CopyLink') {
	        const isSuccess = BX.clipboard.copy(actionData.url);
	        if (isSuccess) {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_LINK_IS_COPIED_SHORT'),
	            autoHideDelay: 5000
	          });
	        }
	      }
	    }
	  }, {
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        SharingSlotsList
	      };
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'CalendarSharingInvitationSent' || item.getType() === 'CalendarSharing' || item.getType() === 'Activity:CalendarSharing' || item.getType() === 'CalendarSharingLinkCopied';
	    }
	  }]);
	  return Sharing;
	}(Base);
	function _openCalendarEvent2(item, actionData) {
	  return crm_router.Router.Instance.openCalendarEventSlider(actionData.eventId, actionData.isSharing);
	}
	async function _startVideoconference2(item, actionData) {
	  let response = null;
	  try {
	    response = await main_core.ajax.runAction('crm.timeline.calendar.sharing.getConferenceChatId', {
	      data: {
	        eventId: actionData.eventId,
	        ownerId: actionData.ownerId,
	        ownerTypeId: actionData.ownerTypeId
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    return;
	  }
	  const chatId = response.data.chatId;
	  if (top.window.BXIM && chatId) {
	    top.window.BXIM.openMessenger(`chat${parseInt(chatId, 10)}`);
	  }
	}
	function _openMembersPopup2(item, members) {
	  const moreButton = item.getContainer().querySelector('[data-id="sharing_member_more_button"]');
	  if (!moreButton) {
	    return;
	  }
	  const existingPopup = main_popup.PopupManager.getPopupById(`sharing_members_popup_${item.getId()}`);
	  if (existingPopup) {
	    return;
	  }
	  const menu = main_popup.MenuManager.create({
	    id: `sharing_members_popup_${item.getId()}`,
	    bindElement: moreButton,
	    cacheable: false,
	    className: 'crm-timeline-sharing-members-popup',
	    maxHeight: 500,
	    maxWidth: 300,
	    animation: 'fading-slide',
	    closeByEsc: true,
	    items: members.map(member => ({
	      html: _classPrivateMethodGet$j(this, _renderMemberMenuItem, _renderMemberMenuItem2).call(this, member),
	      onclick: () => menu.close()
	    }))
	  });
	  menu.show();
	}
	function _renderMemberMenuItem2(member) {
	  const {
	    root,
	    icon
	  } = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<a class="crm-timeline-sharing-members-popup-item" href="${0}" target="_blank">
				<div class="ui-icon ui-icon-common-user crm-timeline-sharing-members-popup-item-image">
					<i ref="icon"></i>
				</div>
				<span class="crm-timeline-sharing-members-popup-item-title">
					${0}
				</span>
			</a>
		`), member.SHOW_URL, main_core.Text.encode(member.FORMATTED_NAME));
	  if (main_core.Type.isStringFilled(member.PHOTO_URL)) {
	    main_core.Dom.style(icon, 'background-image', `url('${encodeURI(main_core.Text.encode(member.PHOTO_URL))}')`);
	  }
	  return root;
	}

	let Task = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Task, _Base);
	  function Task() {
	    babelHelpers.classCallCheck(this, Task);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Task).apply(this, arguments));
	  }
	  babelHelpers.createClass(Task, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      var _actionData$taskId;
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (!actionData) {
	        return;
	      }
	      const taskId = (_actionData$taskId = actionData.taskId) !== null && _actionData$taskId !== void 0 ? _actionData$taskId : null;
	      if (!taskId) {
	        return;
	      }
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      switch (action) {
	        case 'Task:Ping':
	          this.ping(actionData);
	          break;
	        case 'Task:ChangeDeadline':
	          this.changeDeadline(item, actionData);
	          break;
	        case 'Task:View':
	          this.view(actionData);
	          break;
	        case 'Task:Edit':
	          this.edit(actionData);
	          break;
	        case 'Task:Delete':
	          this.delete(actionData);
	          break;
	        case 'Task:ResultView':
	          this.viewResult(actionData);
	          break;
	      }
	    }
	  }, {
	    key: "ping",
	    value: function ping(actionData) {
	      if (!actionData.taskId) {
	        return;
	      }
	      main_core.ajax.runAction('tasks.task.ping', {
	        data: {
	          taskId: actionData.taskId
	        }
	      }).then(response => {
	        if (response.status === 'success') {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_TASK_PING_SENT'),
	            autoHideDelay: 3000
	          });
	        }
	      });
	    }
	  }, {
	    key: "changeDeadline",
	    value: function changeDeadline(item, actionData) {
	      if (!actionData.taskId || !actionData.value) {
	        return;
	      }
	      main_core.ajax.runAction('tasks.task.update', {
	        data: {
	          taskId: actionData.taskId,
	          fields: {
	            DEADLINE: new Date(actionData.valueTs * 1000).toISOString()
	          },
	          params: {
	            skipTimeZoneOffset: 'DEADLINE'
	          }
	        }
	      }).catch(response => {
	        var _response$errors;
	        const errors = (_response$errors = response.errors) !== null && _response$errors !== void 0 ? _response$errors : null;
	        if (errors.length > 0) {
	          ui_notification.UI.Notification.Center.notify({
	            content: errors[0].message,
	            autoHideDelay: 3000
	          });
	          item.forceRefreshLayout();
	        }
	      });
	    }
	  }, {
	    key: "view",
	    value: function view(actionData) {
	      if (!actionData.path) {
	        return;
	      }
	      BX.SidePanel.Instance.open(actionData.path, {
	        cacheable: false
	      });
	    }
	  }, {
	    key: "edit",
	    value: function edit(actionData) {
	      if (!actionData.path) {
	        return;
	      }
	      BX.SidePanel.Instance.open(actionData.path, {
	        cacheable: false
	      });
	    }
	  }, {
	    key: "delete",
	    value: function _delete(actionData) {
	      if (!actionData.taskId) {
	        return;
	      }
	      const messageBox = new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_TASK_CONFIRM_DELETE'),
	        buttons: BX.UI.Dialogs.MessageBoxButtons.YES_NO,
	        onYes: () => {
	          main_core.ajax.runAction('tasks.task.delete', {
	            data: {
	              taskId: actionData.taskId
	            }
	          }).then(() => {
	            messageBox.close();
	          }).catch(error => {
	            var _error$errors$0$messa;
	            ui_notification.UI.Notification.Center.notify({
	              content: (_error$errors$0$messa = error.errors[0].message) !== null && _error$errors$0$messa !== void 0 ? _error$errors$0$messa : 'Error',
	              autoHideDelay: 3000
	            });
	            messageBox.close();
	          });
	        },
	        onNo: () => {
	          messageBox.close();
	        }
	      });
	      messageBox.show();
	    }
	  }, {
	    key: "viewResult",
	    value: function viewResult(actionData) {
	      if (!actionData.taskId) {
	        return;
	      }
	      if (!actionData.path) {
	        return;
	      }
	      main_core.ajax.runAction('tasks.task.result.getLast', {
	        data: {
	          taskId: actionData.taskId
	        }
	      }).then(response => {
	        if (response.status === 'success') {
	          const resultId = response.data.result;
	          BX.SidePanel.Instance.open(actionData.path + '?RID=' + resultId, {
	            cacheable: false
	          });
	        }
	      });
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:TasksTask' || item.getType() === 'TasksTaskCreation' || item.getType() === 'TasksTaskModification' || item.getType() === 'Activity:TasksTaskComment';
	    }
	  }]);
	  return Task;
	}(Base);

	function _classPrivateMethodInitSpec$k(obj, privateSet) { _checkPrivateRedeclaration$n(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$n(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$k(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _open = /*#__PURE__*/new WeakSet();
	let TranscriptResult = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(TranscriptResult, _Base);
	  function TranscriptResult(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, TranscriptResult);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TranscriptResult).call(this, ...args));
	    _classPrivateMethodInitSpec$k(babelHelpers.assertThisInitialized(_this), _open);
	    return _this;
	  }
	  babelHelpers.createClass(TranscriptResult, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'TranscriptResult:Open' && actionData) {
	        _classPrivateMethodGet$k(this, _open, _open2).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'AI:Call:TranscriptResult';
	    }
	  }]);
	  return TranscriptResult;
	}(Base);
	function _open2(actionData) {
	  if (!main_core.Type.isInteger(actionData.activityId) || !main_core.Type.isInteger(actionData.ownerTypeId) || !main_core.Type.isInteger(actionData.ownerId)) {
	    return;
	  }
	  const transcription = new crm_ai_call.Call.Transcription({
	    activityId: actionData.activityId,
	    ownerTypeId: actionData.ownerTypeId,
	    ownerId: actionData.ownerId,
	    languageTitle: actionData.languageTitle
	  });
	  transcription.open();
	}

	function _classPrivateMethodInitSpec$l(obj, privateSet) { _checkPrivateRedeclaration$o(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$o(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$l(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _open$1 = /*#__PURE__*/new WeakSet();
	let TranscriptSummaryResult = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(TranscriptSummaryResult, _Base);
	  function TranscriptSummaryResult(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, TranscriptSummaryResult);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TranscriptSummaryResult).call(this, ...args));
	    _classPrivateMethodInitSpec$l(babelHelpers.assertThisInitialized(_this), _open$1);
	    return _this;
	  }
	  babelHelpers.createClass(TranscriptSummaryResult, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'TranscriptSummaryResult:Open' && actionData) {
	        _classPrivateMethodGet$l(this, _open$1, _open2$1).call(this, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'AI:Call:TranscriptSummaryResult';
	    }
	  }]);
	  return TranscriptSummaryResult;
	}(Base);
	function _open2$1(actionData) {
	  if (!main_core.Type.isInteger(actionData.activityId) || !main_core.Type.isInteger(actionData.ownerTypeId) || !main_core.Type.isInteger(actionData.ownerId)) {
	    return;
	  }
	  const summary = new crm_ai_call.Call.Summary({
	    activityId: actionData.activityId,
	    ownerTypeId: actionData.ownerTypeId,
	    ownerId: actionData.ownerId,
	    languageTitle: actionData.languageTitle
	  });
	  summary.open();
	}

	function _classPrivateMethodInitSpec$m(obj, privateSet) { _checkPrivateRedeclaration$p(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$p(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$m(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openAiFormFillAction = /*#__PURE__*/new WeakSet();
	var _openAiFormFill = /*#__PURE__*/new WeakSet();
	var _openAiDoneSlider = /*#__PURE__*/new WeakSet();
	var _fetchOperationStatus = /*#__PURE__*/new WeakSet();
	var _openSendFeedbackPopup = /*#__PURE__*/new WeakSet();
	let EntityFieldsFillingResult = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(EntityFieldsFillingResult, _Base);
	  function EntityFieldsFillingResult(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, EntityFieldsFillingResult);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EntityFieldsFillingResult).call(this, ...args));
	    _classPrivateMethodInitSpec$m(babelHelpers.assertThisInitialized(_this), _openSendFeedbackPopup);
	    _classPrivateMethodInitSpec$m(babelHelpers.assertThisInitialized(_this), _fetchOperationStatus);
	    _classPrivateMethodInitSpec$m(babelHelpers.assertThisInitialized(_this), _openAiDoneSlider);
	    _classPrivateMethodInitSpec$m(babelHelpers.assertThisInitialized(_this), _openAiFormFill);
	    _classPrivateMethodInitSpec$m(babelHelpers.assertThisInitialized(_this), _openAiFormFillAction);
	    return _this;
	  }
	  babelHelpers.createClass(EntityFieldsFillingResult, [{
	    key: "onItemAction",
	    value: async function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData,
	        animationCallbacks
	      } = actionParams;
	      if (actionType !== 'jsEvent' || !actionData) {
	        return;
	      }
	      switch (action) {
	        case 'EntityFieldsFillingResult:OpenAiFormFill':
	          _classPrivateMethodGet$m(this, _openAiFormFillAction, _openAiFormFillAction2).call(this, actionData);
	          break;
	        case 'EntityFieldsFillingResult:OpenSendFeedbackPopup':
	          _classPrivateMethodGet$m(this, _openSendFeedbackPopup, _openSendFeedbackPopup2).call(this, actionData, animationCallbacks);
	          break;
	        default:
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'AI:Call:EntityFieldsFillingResult';
	    }
	  }]);
	  return EntityFieldsFillingResult;
	}(Base);
	async function _openAiFormFillAction2(actionData) {
	  const operationStatus = await _classPrivateMethodGet$m(this, _fetchOperationStatus, _fetchOperationStatus2).call(this, actionData.mergeUuid);
	  switch (operationStatus) {
	    case 'APPLIED':
	      _classPrivateMethodGet$m(this, _openAiDoneSlider, _openAiDoneSlider2).call(this);
	      break;
	    case 'CONFLICT':
	      _classPrivateMethodGet$m(this, _openAiFormFill, _openAiFormFill2).call(this, actionData);
	      break;
	    default:
	      throw new Error(`Invalid operation status: ${operationStatus}`);
	  }
	}
	function _openAiFormFill2(actionData) {
	  const mergeUuid = parseInt(actionData.mergeUuid, 10);
	  if (!main_core.Type.isInteger(mergeUuid) || mergeUuid <= 0) {
	    return;
	  }
	  top.BX.Runtime.loadExtension('crm.ai.form-fill').then(exports => {
	    const {
	      createAiFormFillApplicationInsideSlider
	    } = exports;
	    createAiFormFillApplicationInsideSlider({
	      ...actionData,
	      mergeUuid
	    });
	  }).catch(() => {
	    throw new Error('Cant load createAiFormFillApplicationInsideSlider extension');
	  });
	}
	function _openAiDoneSlider2() {
	  top.BX.Runtime.loadExtension('crm.ai.done').then(exports => {
	    const {
	      Done
	    } = exports;
	    new Done().start();
	  }).catch(() => {
	    throw new Error('Cant load crm.ai.done extension');
	  });
	}
	async function _fetchOperationStatus2(mergeId) {
	  var _response$data;
	  const response = await main_core.ajax.runAction('crm.timeline.ai.fieldsFillingStatus', {
	    data: {
	      mergeId
	    }
	  });
	  if (response.status !== 'success') {
	    return null;
	  }
	  return response === null || response === void 0 ? void 0 : (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.operationStatus;
	}
	function _openSendFeedbackPopup2(actionData, animationCallbacks) {
	  var _animationCallbacks$o;
	  const mergeUuid = parseInt(actionData.mergeUuid, 10);
	  if (!main_core.Type.isInteger(mergeUuid) || mergeUuid <= 0) {
	    return;
	  }
	  const activityId = main_core.Text.toInteger(actionData.activityId) > 0 ? main_core.Text.toInteger(actionData.activityId) : 0;
	  animationCallbacks === null || animationCallbacks === void 0 ? void 0 : (_animationCallbacks$o = animationCallbacks.onStart) === null || _animationCallbacks$o === void 0 ? void 0 : _animationCallbacks$o.call(animationCallbacks);
	  main_core.Runtime.loadExtension('crm.ai.feedback').then(exports => {
	    const {
	      showSendFeedbackPopup
	    } = exports;

	    /** @see BX.Crm.AI.Feedback.showSendFeedbackPopup */
	    showSendFeedbackPopup(mergeUuid, actionData.ownerTypeId, activityId, actionData.activityDirection);
	  }).catch(() => {
	    console.error('Cant load showSendFeedbackPopup extension');
	  }).finally(() => {
	    var _animationCallbacks$o2;
	    return animationCallbacks === null || animationCallbacks === void 0 ? void 0 : (_animationCallbacks$o2 = animationCallbacks.onStop) === null || _animationCallbacks$o2 === void 0 ? void 0 : _animationCallbacks$o2.call(animationCallbacks);
	  });
	}

	function _classPrivateMethodInitSpec$n(obj, privateSet) { _checkPrivateRedeclaration$q(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$q(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$n(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _open$2 = /*#__PURE__*/new WeakSet();
	var _editPrompt = /*#__PURE__*/new WeakSet();
	let CallScoringResult = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CallScoringResult, _Base);
	  function CallScoringResult(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, CallScoringResult);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CallScoringResult).call(this, ...args));
	    _classPrivateMethodInitSpec$n(babelHelpers.assertThisInitialized(_this), _editPrompt);
	    _classPrivateMethodInitSpec$n(babelHelpers.assertThisInitialized(_this), _open$2);
	    return _this;
	  }
	  babelHelpers.createClass(CallScoringResult, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'CallScoringResult:Open' && actionData) {
	        _classPrivateMethodGet$n(this, _open$2, _open2$2).call(this, actionData);
	      }
	      if (action === 'CallScoringResult:EditPrompt') {
	        _classPrivateMethodGet$n(this, _editPrompt, _editPrompt2).call(this, item, actionData);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'AI:Call:CallScoringResult';
	    }
	  }]);
	  return CallScoringResult;
	}(Base);
	function _open2$2(actionData) {
	  var _actionData$activityC, _actionData$clientDet, _actionData$clientFul, _actionData$userPhoto, _actionData$jobId;
	  if (!main_core.Type.isInteger(actionData.activityId) || !main_core.Type.isInteger(actionData.ownerTypeId) || !main_core.Type.isInteger(actionData.ownerId)) {
	    return;
	  }
	  const callQualityDlg = new crm_ai_call.Call.CallQuality({
	    activityId: actionData.activityId,
	    activityCreated: (_actionData$activityC = actionData.activityCreated) !== null && _actionData$activityC !== void 0 ? _actionData$activityC : null,
	    ownerTypeId: actionData.ownerTypeId,
	    ownerId: actionData.ownerId,
	    clientDetailUrl: (_actionData$clientDet = actionData.clientDetailUrl) !== null && _actionData$clientDet !== void 0 ? _actionData$clientDet : null,
	    clientFullName: (_actionData$clientFul = actionData.clientFullName) !== null && _actionData$clientFul !== void 0 ? _actionData$clientFul : null,
	    userPhotoUrl: (_actionData$userPhoto = actionData.userPhotoUrl) !== null && _actionData$userPhoto !== void 0 ? _actionData$userPhoto : null,
	    jobId: (_actionData$jobId = actionData.jobId) !== null && _actionData$jobId !== void 0 ? _actionData$jobId : null
	  });
	  callQualityDlg.open();
	}
	function _editPrompt2(item, actionData) {
	  if (!main_core.Type.isInteger(actionData.assessmentSettingId)) {
	    return;
	  }
	  crm_router.Router.openSlider(`/crm/copilot-call-assessment/details/${actionData.assessmentSettingId}/`, {
	    width: 700,
	    cacheable: false
	  });
	}

	function _classPrivateMethodInitSpec$o(obj, privateSet) { _checkPrivateRedeclaration$r(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$r(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$o(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _changePlayerState$1 = /*#__PURE__*/new WeakSet();
	let Visit = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Visit, _Base);
	  function Visit(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Visit);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Visit).call(this, ...args));
	    _classPrivateMethodInitSpec$o(babelHelpers.assertThisInitialized(_this), _changePlayerState$1);
	    return _this;
	  }
	  babelHelpers.createClass(Visit, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Activity:Visit:ChangePlayerState' && actionData && actionData.recordId) {
	        _classPrivateMethodGet$o(this, _changePlayerState$1, _changePlayerState2$1).call(this, item, actionData.recordId);
	      }
	      if (action === 'Activity:Visit:Schedule' && actionData) {
	        this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Visit';
	    }
	  }]);
	  return Visit;
	}(Base);
	function _changePlayerState2$1(item, recordId) {
	  const player = item.getLayoutContentBlockById('audio');
	  if (!player) {
	    return;
	  }
	  if (recordId !== player.id) {
	    return;
	  }
	  if (player.state === 'play') {
	    player.pause();
	  } else {
	    player.play();
	  }
	}

	function _classPrivateMethodInitSpec$p(obj, privateSet) { _checkPrivateRedeclaration$s(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$s(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$p(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const DOWNLOAD_DELAY = 300;
	var _copyToClipboard = /*#__PURE__*/new WeakSet();
	var _downloadAllRecords = /*#__PURE__*/new WeakSet();
	let Zoom = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Zoom, _Base);
	  function Zoom(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Zoom);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Zoom).call(this, ...args));
	    _classPrivateMethodInitSpec$p(babelHelpers.assertThisInitialized(_this), _downloadAllRecords);
	    _classPrivateMethodInitSpec$p(babelHelpers.assertThisInitialized(_this), _copyToClipboard);
	    return _this;
	  }
	  babelHelpers.createClass(Zoom, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent' || !actionData) {
	        return;
	      }
	      if (action === 'Activity:Zoom:CopyInviteUrl') {
	        _classPrivateMethodGet$p(this, _copyToClipboard, _copyToClipboard2).call(this, actionData.url);
	      }
	      if (action === 'Activity:Zoom:Schedule') {
	        this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
	      }
	      if (action === 'Activity:Zoom:CopyPassword') {
	        _classPrivateMethodGet$p(this, _copyToClipboard, _copyToClipboard2).call(this, actionData.password);
	      }
	      if (action === 'Activity:Zoom:DownloadAllRecords' && main_core.Type.isArray(actionData.urlList)) {
	        _classPrivateMethodGet$p(this, _downloadAllRecords, _downloadAllRecords2).call(this, actionData.urlList);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Zoom';
	    }
	  }]);
	  return Zoom;
	}(Base);
	function _copyToClipboard2(input) {
	  if (main_core.Type.isStringFilled(input)) {
	    const isSuccess = BX.clipboard.copy(input);
	    if (isSuccess) {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('CRM_COMMON_ACTION_COPY_TO_CLIPBOARD_SUCCESS'),
	        autoHideDelay: 2000
	      });
	    }
	  }
	}
	function _downloadAllRecords2(urlList) {
	  const download = urls => {
	    const url = urls.pop();
	    const a = document.createElement('a');
	    a.setAttribute('href', url);
	    if ('download' in a) {
	      a.setAttribute('download', `zoom_record_file_${main_core.Text.getRandom(5)}.m4a`);
	    }
	    a.setAttribute('target', '_blank');
	    a.click();
	    if (urls.length === 0) {
	      clearInterval(interval);
	    }
	  };
	  const interval = setInterval(download, DOWNLOAD_DELAY, urlList);
	}

	function _classPrivateMethodInitSpec$q(obj, privateSet) { _checkPrivateRedeclaration$t(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$t(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$q(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _resendSms = /*#__PURE__*/new WeakSet();
	let Sms = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Sms, _Base);
	  function Sms(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Sms);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sms).call(this, ...args));
	    _classPrivateMethodInitSpec$q(babelHelpers.assertThisInitialized(_this), _resendSms);
	    return _this;
	  }
	  babelHelpers.createClass(Sms, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Activity:Sms:Resend' && main_core.Type.isPlainObject(actionData.params)) {
	        _classPrivateMethodGet$q(this, _resendSms, _resendSms2).call(this, actionData.params);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Sms';
	    }
	  }]);
	  return Sms;
	}(Base);
	function _resendSms2(params) {
	  var _BX$Crm, _BX$Crm$Timeline, _BX$Crm$Timeline$Menu;
	  const menuBar = (_BX$Crm = BX.Crm) === null || _BX$Crm === void 0 ? void 0 : (_BX$Crm$Timeline = _BX$Crm.Timeline) === null || _BX$Crm$Timeline === void 0 ? void 0 : (_BX$Crm$Timeline$Menu = _BX$Crm$Timeline.MenuBar) === null || _BX$Crm$Timeline$Menu === void 0 ? void 0 : _BX$Crm$Timeline$Menu.getDefault();
	  if (!menuBar) {
	    throw new Error('"BX.Crm?.Timeline.MenuBar" component not found');
	  }
	  const smsItem = menuBar.getItemById('sms');
	  if (!smsItem) {
	    throw new Error('"BX.Crm.Timeline.MenuBar.Sms" component not found');
	  }
	  const goToEditor = () => {
	    menuBar.scrollIntoView();
	    menuBar.setActiveItemById('sms');
	    smsItem.tryToResend(params.senderId, params.from, params.client, params.text);
	  };
	  const {
	    text,
	    templateId
	  } = smsItem.getSendData();
	  if (main_core.Type.isStringFilled(text) || templateId !== null) {
	    ui_dialogs_messagebox.MessageBox.show({
	      modal: true,
	      title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_TITLE'),
	      message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_MESSAGE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_OK_BTN'),
	      onOk: messageBox => {
	        messageBox.close();
	        goToEditor();
	      },
	      onCancel: messageBox => messageBox.close()
	    });
	  } else {
	    goToEditor();
	  }
	}

	function _classPrivateMethodInitSpec$r(obj, privateSet) { _checkPrivateRedeclaration$u(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$u(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$r(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _resendWhatsApp = /*#__PURE__*/new WeakSet();
	let WhatsApp = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(WhatsApp, _Base);
	  function WhatsApp(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, WhatsApp);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WhatsApp).call(this, ...args));
	    _classPrivateMethodInitSpec$r(babelHelpers.assertThisInitialized(_this), _resendWhatsApp);
	    return _this;
	  }
	  babelHelpers.createClass(WhatsApp, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Activity:Whatsapp:Resend' && main_core.Type.isPlainObject(actionData.params)) {
	        _classPrivateMethodGet$r(this, _resendWhatsApp, _resendWhatsApp2).call(this, actionData.params);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Whatsapp';
	    }
	  }]);
	  return WhatsApp;
	}(Base);
	function _resendWhatsApp2(params) {
	  var _BX$Crm, _BX$Crm$Timeline, _BX$Crm$Timeline$Menu, _params$template, _params$template$FILL, _params$template2, _whatsAppItem$getTemp, _whatsAppItem$getTemp2, _whatsAppItem$getTemp3;
	  const menuBar = (_BX$Crm = BX.Crm) === null || _BX$Crm === void 0 ? void 0 : (_BX$Crm$Timeline = _BX$Crm.Timeline) === null || _BX$Crm$Timeline === void 0 ? void 0 : (_BX$Crm$Timeline$Menu = _BX$Crm$Timeline.MenuBar) === null || _BX$Crm$Timeline$Menu === void 0 ? void 0 : _BX$Crm$Timeline$Menu.getDefault();
	  if (!menuBar) {
	    throw new Error('"BX.Crm?.Timeline.MenuBar" component not found');
	  }
	  const whatsAppItem = menuBar.getItemById('whatsapp');
	  if (!whatsAppItem) {
	    throw new Error('"BX.Crm.Timeline.MenuBar.WhatsApp" component not found');
	  }
	  const goToEditor = () => {
	    menuBar.scrollIntoView();
	    menuBar.setActiveItemById('whatsapp');
	    whatsAppItem.tryToResend(params.template, params.from, params.client);
	  };
	  const templateId = (_params$template = params.template) === null || _params$template === void 0 ? void 0 : _params$template.ORIGINAL_ID;
	  const filledPlaceholders = (_params$template$FILL = (_params$template2 = params.template) === null || _params$template2 === void 0 ? void 0 : _params$template2.FILLED_PLACEHOLDERS) !== null && _params$template$FILL !== void 0 ? _params$template$FILL : [];
	  const currentTemplateId = (_whatsAppItem$getTemp = whatsAppItem.getTemplate()) === null || _whatsAppItem$getTemp === void 0 ? void 0 : _whatsAppItem$getTemp.ORIGINAL_ID;
	  const currentFilledPlaceholders = (_whatsAppItem$getTemp2 = (_whatsAppItem$getTemp3 = whatsAppItem.getTemplate()) === null || _whatsAppItem$getTemp3 === void 0 ? void 0 : _whatsAppItem$getTemp3.FILLED_PLACEHOLDERS) !== null && _whatsAppItem$getTemp2 !== void 0 ? _whatsAppItem$getTemp2 : [];
	  if (main_core.Type.isNumber(templateId) && templateId > 0 && main_core.Type.isNumber(currentTemplateId) && currentTemplateId > 0 && (templateId !== currentTemplateId || JSON.stringify(filledPlaceholders) !== JSON.stringify(currentFilledPlaceholders))) {
	    ui_dialogs_messagebox.MessageBox.show({
	      modal: true,
	      title: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_WHATSAPP_RESEND_CONFIRM_DIALOG_TITLE'),
	      message: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_WHATSAPP_RESEND_CONFIRM_DIALOG_MESSAGE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_OK_BTN'),
	      onOk: messageBox => {
	        messageBox.close();
	        goToEditor();
	      },
	      onCancel: messageBox => messageBox.close()
	    });
	  } else {
	    goToEditor();
	  }
	}

	const ICON_COLORS = Object.freeze({
	  lightGrey: 'var(--crm-timeline-avatars-stack-steps-icon-color-light-gray)',
	  blue: 'var(--crm-timeline-avatars-stack-steps-icon-color-blue)',
	  lightGreen: 'var(--crm-timeline-avatars-stack-steps-icon-color-light-green)'
	});
	var AvatarsStackSteps = {
	  props: {
	    steps: {
	      type: Array,
	      required: true,
	      validator: value => {
	        return main_core.Type.isArrayFilled(value);
	      }
	    },
	    styles: {
	      type: Object,
	      required: false
	    }
	  },
	  data() {
	    return {
	      stack: null
	    };
	  },
	  mounted() {
	    if (this.$refs.controlWrapper) {
	      this.stack = new ui_imageStackSteps.ImageStackSteps({
	        steps: this.convertIconColors(this.steps)
	      });
	      this.stack.renderTo(this.$refs.controlWrapper);
	    }
	  },
	  updated() {
	    if (this.stack) {
	      this.convertIconColors(this.steps).forEach(step => {
	        this.stack.updateStep(step, step.id);
	      });
	    }
	  },
	  unmounted() {
	    if (this.stack) {
	      this.stack.destroy();
	    }
	  },
	  computed: {
	    getStyles() {
	      var _this$styles;
	      const styles = {};
	      if ((_this$styles = this.styles) !== null && _this$styles !== void 0 && _this$styles.minWidth) {
	        styles['min-width'] = `${main_core.Text.toInteger(this.styles.minWidth)}px`;
	      }
	      return styles;
	    }
	  },
	  methods: {
	    convertIconColors(steps) {
	      const colors = Object.keys(ICON_COLORS);
	      steps.forEach(step => {
	        const images = step.stack.images;
	        if (main_core.Type.isArrayFilled(images)) {
	          images.forEach(image => {
	            if (image.type === ui_imageStackSteps.imageTypeEnum.ICON) {
	              var _image$data;
	              const color = (_image$data = image.data) === null || _image$data === void 0 ? void 0 : _image$data.color;
	              if (colors.includes(color)) {
	                // eslint-disable-next-line no-param-reassign
	                image.data.color = ICON_COLORS[color];
	              }
	            }
	          });
	        }
	      });
	      return steps;
	    }
	  },
	  template: `
		<div class="crm-timeline__avatars-stack-steps" ref="controlWrapper" :style="getStyles"></div>
	`
	};

	const TaskUserStatus = Object.freeze({
	  WAITING: 0,
	  YES: 1,
	  NO: 2,
	  OK: 3,
	  CANCEL: 4
	});

	function _classPrivateMethodInitSpec$s(obj, privateSet) { _checkPrivateRedeclaration$v(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$v(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$s(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _handleTaskAction = /*#__PURE__*/new WeakSet();
	var _openWorkflowLogSlider = /*#__PURE__*/new WeakSet();
	var _openWorkflowSlider = /*#__PURE__*/new WeakSet();
	var _openWorkflowTaskSlider = /*#__PURE__*/new WeakSet();
	var _openSlider = /*#__PURE__*/new WeakSet();
	var _openTimeline = /*#__PURE__*/new WeakSet();
	var _terminateWorkflow = /*#__PURE__*/new WeakSet();
	var _doTask = /*#__PURE__*/new WeakSet();
	let Bizproc = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Bizproc, _Base);
	  function Bizproc(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, Bizproc);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Bizproc).call(this, ...args));
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _doTask);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _terminateWorkflow);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _openTimeline);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _openSlider);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _openWorkflowTaskSlider);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _openWorkflowSlider);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _openWorkflowLogSlider);
	    _classPrivateMethodInitSpec$s(babelHelpers.assertThisInitialized(_this), _handleTaskAction);
	    return _this;
	  }
	  babelHelpers.createClass(Bizproc, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(item) {
	      return {
	        AvatarsStackSteps
	      };
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      const actionHandlers = {
	        'Bizproc:Task:Open': () => _classPrivateMethodGet$s(this, _openWorkflowTaskSlider, _openWorkflowTaskSlider2).call(this, actionData),
	        'Bizproc:Task:Do': () => _classPrivateMethodGet$s(this, _handleTaskAction, _handleTaskAction2).call(this, actionData, item),
	        'Bizproc:Workflow:Timeline:Open': () => _classPrivateMethodGet$s(this, _openTimeline, _openTimeline2).call(this, actionData),
	        'Bizproc:Workflow:Open': () => _classPrivateMethodGet$s(this, _openWorkflowSlider, _openWorkflowSlider2).call(this, actionData),
	        'Bizproc:Workflow:Terminate': () => _classPrivateMethodGet$s(this, _terminateWorkflow, _terminateWorkflow2).call(this, actionData),
	        'Bizproc:Workflow:Log': () => _classPrivateMethodGet$s(this, _openWorkflowLogSlider, _openWorkflowLogSlider2).call(this, actionData)
	      };
	      const handler = actionHandlers[action];
	      if (handler) {
	        handler();
	      }
	    }
	  }, {
	    key: "onAfterItemLayout",
	    value: function onAfterItemLayout(item, options) {
	      main_core_events.EventEmitter.emit('BX.Crm.Timeline.Items.Bizproc:onAfterItemLayout', {
	        target: item.getWrapper(),
	        id: item.getId(),
	        type: item.getType(),
	        options
	      });
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      const supportedItemTypes = ['BizprocWorkflowStarted', 'BizprocWorkflowCompleted', 'BizprocWorkflowTerminated', 'BizprocTaskCreation', 'BizprocTaskCompleted', 'BizprocCommentAdded', 'BizprocCommentRead', 'BizprocTaskDelegated', 'Activity:BizprocWorkflowCompleted', 'Activity:BizprocCommentAdded', 'Activity:BizprocTask'];
	      return supportedItemTypes.includes(item.getType());
	    }
	  }]);
	  return Bizproc;
	}(Base);
	function _handleTaskAction2(actionData, item) {
	  var _item$getCurrentUser;
	  const responsibleId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.responsibleId);
	  if (responsibleId > 0 && main_core.Text.toInteger((_item$getCurrentUser = item.getCurrentUser()) === null || _item$getCurrentUser === void 0 ? void 0 : _item$getCurrentUser.userId) === responsibleId) {
	    _classPrivateMethodGet$s(this, _doTask, _doTask2).call(this, actionData, item);
	  }
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Text.encode(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_BIZPROC_TASK_DO_ACTION_ACCESS_DENIED')),
	    autoHideDelay: 5000
	  });
	}
	function _openWorkflowLogSlider2(actionData) {
	  _classPrivateMethodGet$s(this, _openSlider, _openSlider2).call(this, actionData, (Router, {
	    workflowId
	  }) => {
	    if (Router && workflowId) {
	      Router.openWorkflowLog(workflowId);
	    }
	  });
	}
	function _openWorkflowSlider2(actionData) {
	  _classPrivateMethodGet$s(this, _openSlider, _openSlider2).call(this, actionData, (Router, {
	    workflowId
	  }) => {
	    if (Router && workflowId) {
	      Router.openWorkflow(workflowId);
	    }
	  });
	}
	function _openWorkflowTaskSlider2(actionData) {
	  _classPrivateMethodGet$s(this, _openSlider, _openSlider2).call(this, actionData, (Router, {
	    taskId,
	    userId
	  }) => {
	    if (Router && taskId) {
	      Router.openWorkflowTask(main_core.Text.toInteger(taskId), main_core.Text.toInteger(userId));
	    }
	  });
	}
	async function _openSlider2(actionData, callback) {
	  if (!actionData) {
	    return;
	  }
	  try {
	    const {
	      Router
	    } = await main_core.Runtime.loadExtension('bizproc.router');
	    callback(Router, actionData);
	  } catch (e) {
	    console.error(e);
	  }
	}
	function _openTimeline2(actionData) {
	  const workflowId = actionData === null || actionData === void 0 ? void 0 : actionData.workflowId;
	  if (!workflowId) {
	    return;
	  }
	  main_core.Runtime.loadExtension('bizproc.workflow.timeline').then(() => {
	    BX.Bizproc.Workflow.Timeline.open({
	      workflowId
	    });
	  }).catch(response => console.error(response.errors));
	}
	function _terminateWorkflow2(actionData) {
	  const workflowId = actionData === null || actionData === void 0 ? void 0 : actionData.workflowId;
	  if (!workflowId) {
	    return;
	  }
	  main_core.ajax.runAction('bizproc.workflow.terminate', {
	    data: {
	      workflowId
	    }
	  }).catch(response => {
	    response.errors.forEach(error => {
	      ui_notification.UI.Notification.Center.notify({
	        content: error.message,
	        autoHideDelay: 5000
	      });
	    });
	  });
	}
	function _doTask2(actionData, item) {
	  const taskId = actionData === null || actionData === void 0 ? void 0 : actionData.taskId;
	  if (!taskId) {
	    return;
	  }
	  const value = actionData === null || actionData === void 0 ? void 0 : actionData.value;
	  const name = actionData === null || actionData === void 0 ? void 0 : actionData.name;
	  if (main_core.Type.isStringFilled(name) && main_core.Type.isStringFilled(value)) {
	    const buttons = Object.values(TaskUserStatus).map(status => {
	      return item.getLayoutFooterButtonById(`status_${status}`);
	    }).filter(button => button);
	    buttons.forEach(button => {
	      button.setButtonState(ButtonState.DISABLED);
	    });
	    const data = {
	      taskId,
	      taskRequest: {
	        [name]: value
	      }
	    };
	    main_core.ajax.runAction('bizproc.task.do', {
	      data
	    }).then(() => {}) // waiting push
	    .catch(response => {
	      response.errors.forEach(error => {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Text.encode(error.message),
	          autoHideDelay: 5000
	        });
	      });
	      buttons.forEach(button => {
	        button.setButtonState(ButtonState.DEFAULT);
	      });
	    });
	  }
	}

	let Booking = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Booking, _Base);
	  function Booking() {
	    babelHelpers.classCallCheck(this, Booking);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Booking).apply(this, arguments));
	  }
	  babelHelpers.createClass(Booking, [{
	    key: "onItemAction",
	    value: function onItemAction(item, actionParams) {
	      const {
	        action,
	        actionType,
	        actionData
	      } = actionParams;
	      if (actionType !== 'jsEvent') {
	        return;
	      }
	      if (action === 'Activity:Booking:ShowBooking') {
	        const url = `/booking/?editingBookingId=${actionData.id}`;
	        BX.SidePanel.Instance.open(url, {
	          customLeftBoundary: 0
	        });
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Booking';
	    }
	  }]);
	  return Booking;
	}(Base);

	ControllerManager.registerController(Activity);
	ControllerManager.registerController(CommonContentBlocks);
	ControllerManager.registerController(OpenLines);
	ControllerManager.registerController(Modification);
	ControllerManager.registerController(SignDocument);
	ControllerManager.registerController(Document);
	ControllerManager.registerController(Call);
	ControllerManager.registerController(ToDo);
	ControllerManager.registerController(Helpdesk);
	ControllerManager.registerController(Payment);
	ControllerManager.registerController(DealProductList);
	ControllerManager.registerController(Email);
	ControllerManager.registerController(OrderCheck);
	ControllerManager.registerController(FinalSummary);
	ControllerManager.registerController(SalescenterApp);
	ControllerManager.registerController(Delivery);
	ControllerManager.registerController(RestApp);
	ControllerManager.registerController(Comment);
	ControllerManager.registerController(Sharing);
	ControllerManager.registerController(Task);
	ControllerManager.registerController(TranscriptResult);
	ControllerManager.registerController(TranscriptSummaryResult);
	ControllerManager.registerController(EntityFieldsFillingResult);
	ControllerManager.registerController(CallScoringResult);
	ControllerManager.registerController(SignB2eDocument);
	ControllerManager.registerController(Visit);
	ControllerManager.registerController(Zoom);
	ControllerManager.registerController(Sms);
	ControllerManager.registerController(WhatsApp);
	ControllerManager.registerController(Bizproc);
	ControllerManager.registerController(Booking);

	exports.Item = Item$1;
	exports.ConfigurableItem = ConfigurableItem;
	exports.StreamType = StreamType;
	exports.ControllerManager = ControllerManager;
	exports.BaseController = Base;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX,BX.UI.Analytics,BX.Crm.Field,BX.Vue3.Directives,BX.UI,BX.UI,BX.Location.Core,BX,BX.Crm.Timeline.Editors,BX.UI.TextEditor,BX.UI.BBCode.Formatter,BX.Vue3,BX.UI.Icons.Generator,BX.Crm,BX.UI.IconSet,BX,BX.Crm.Field,BX.Currency,BX.UI,BX.UI,BX.UI,BX.Crm.Field,BX.Bizproc,BX.Messenger.v2.Lib,BX.Main,BX.Crm.Timeline,BX.UI,BX.AI,BX.UI,BX.UI.Feedback,BX.Crm.Activity,BX.UI.EntitySelector,BX,BX.Crm,BX,BX.Calendar,BX.Main,BX.Calendar.Sharing,BX.Crm.AI,BX.Crm,BX,BX.UI.Dialogs,BX.Event,BX,BX,BX.UI,BX,BX,BX.Crm.Timeline));
//# sourceMappingURL=index.bundle.js.map

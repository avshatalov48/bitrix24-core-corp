/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_analytics,ui_popupcomponentsmaker,ui_cnt,main_popup,main_core_events,main_core,main_loader) {
	'use strict';

	var Analytics = /*#__PURE__*/function () {
	  function Analytics() {
	    babelHelpers.classCallCheck(this, Analytics);
	  }
	  babelHelpers.createClass(Analytics, null, [{
	    key: "send",
	    value: function send(event) {
	      ui_analytics.sendData({
	        tool: Analytics.TOOLS,
	        category: Analytics.CATEGORY_INVITATION,
	        event: event,
	        p1: Analytics.isAdmin ? 'isAdmin_Y' : 'isAdmin_N'
	      });
	    }
	  }, {
	    key: "sendCreateCollab",
	    value: function sendCreateCollab() {
	      ui_analytics.sendData({
	        tool: 'im',
	        category: 'collab',
	        event: 'click_create_new',
	        c_section: Analytics.SECTION_POPUP,
	        p2: 'user_intranet' // widget is available only for intranet users
	      });
	    }
	  }]);
	  return Analytics;
	}();
	babelHelpers.defineProperty(Analytics, "TOOLS", 'headerPopup');
	babelHelpers.defineProperty(Analytics, "TOOLS_LEGACY", 'Invitation');
	babelHelpers.defineProperty(Analytics, "CATEGORY_INVITATION", 'invitation');
	babelHelpers.defineProperty(Analytics, "CATEGORY_INVITATION_LEGACY", 'invitation');
	babelHelpers.defineProperty(Analytics, "EVENT_NAME_LEGACY", 'drawer_open');
	babelHelpers.defineProperty(Analytics, "SECTION_POPUP", 'headerPopup');
	babelHelpers.defineProperty(Analytics, "EVENT_SHOW", 'show');
	babelHelpers.defineProperty(Analytics, "EVENT_OPEN_SLIDER_INVITATION", 'drawer_open');
	babelHelpers.defineProperty(Analytics, "EVENT_OPEN_STRUCTURE", 'vis_structure_open');
	babelHelpers.defineProperty(Analytics, "EVENT_OPEN_USER_LIST", 'company_open');
	babelHelpers.defineProperty(Analytics, "EVENT_OPEN_SLIDER_EXTRANET_INVITATION", 'extranetinvitation_open');
	babelHelpers.defineProperty(Analytics, "isAdmin", false);

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Content = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Content, _EventEmitter);
	  function Content(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Content);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Content).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());
	    _this.setOptions(options);
	    _this.analytics = new Analytics();
	    return _this;
	  }
	  babelHelpers.createClass(Content, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      this.cache.set('options', _objectSpread({}, options));
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return this.cache.get('options', {});
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      throw new Error('Must be implemented in a child class');
	    }
	  }, {
	    key: "showInfoHelper",
	    value: function showInfoHelper(articleCode) {
	      BX.UI.InfoHelper.show(articleCode);
	      this.sendAnalytics(articleCode);
	    }
	  }, {
	    key: "sendAnalytics",
	    value: function sendAnalytics(code) {
	      main_core.ajax.runAction('intranet.invitationwidget.analyticsLabel', {
	        data: {},
	        analyticsLabel: {
	          helperCode: code,
	          headerPopup: 'Y'
	        }
	      });
	    }
	  }, {
	    key: "getHintPopup",
	    value: function getHintPopup(text, element, type) {
	      return this.cache.remember(type, function () {
	        return new main_popup.Popup("bx-hint-".concat(main_core.Text.getRandom()), element, {
	          content: text,
	          className: 'bx-invitation-warning',
	          zIndex: 15000,
	          angle: true,
	          offsetTop: 0,
	          offsetLeft: 40,
	          closeIcon: false,
	          autoHide: true,
	          darkMode: true,
	          overlay: false,
	          maxWidth: 300,
	          events: {
	            onShow: function onShow(event) {
	              main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:show', new main_core_events.BaseEvent({
	                data: {
	                  popup: event.target
	                }
	              }));
	              var timeout = setTimeout(function () {
	                event.target.close();
	              }, 4000);
	              main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:close', function () {
	                clearTimeout(timeout);
	              });
	            },
	            onClose: function onClose() {
	              main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:close');
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "showHintPopup",
	    value: function showHintPopup(text, element, type) {
	      this.getHintPopup(text, element, type).toggle();
	    }
	  }, {
	    key: "showInvitationPlace",
	    value: function showInvitationPlace(text, element, type) {
	      if (this.getOptions().isAdmin) {
	        this.showInvitationSlider(type);
	      } else {
	        if (this.getOptions().isInvitationAvailable) {
	          this.showInvitationSlider(type);
	        } else {
	          this.showHintPopup(text, element, 'hint-' + type);
	        }
	      }
	    }
	  }, {
	    key: "showInvitationSlider",
	    value: function showInvitationSlider(type) {
	      var link = this.getOptions().invitationLink;
	      if (type === 'extranet') {
	        link = "".concat(link, "&firstInvitationBlock=extranet");
	        Analytics.send(Analytics.EVENT_OPEN_SLIDER_EXTRANET_INVITATION);
	      } else {
	        Analytics.send(Analytics.EVENT_OPEN_SLIDER_INVITATION);
	      }
	      BX.SidePanel.Instance.open(link, {
	        cacheable: false,
	        allowChangeHistory: false,
	        width: 1100
	      });
	    }
	  }, {
	    key: "getConfig",
	    value: function getConfig() {
	      return {
	        html: this.getLayout()
	      };
	    }
	  }]);
	  return Content;
	}(main_core_events.EventEmitter);

	var _templateObject;
	var InvitationContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(InvitationContent, _Content);
	  function InvitationContent(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, InvitationContent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InvitationContent).call(this, options));
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.InvitationContent');
	    _this.setOptions(options);
	    return _this;
	  }
	  babelHelpers.createClass(InvitationContent, [{
	    key: "getConfig",
	    value: function getConfig() {
	      return {
	        html: this.getLayout(),
	        backgroundColor: '#14bfd5'
	      };
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this2 = this;
	      return this.cache.remember('layout', function () {
	        var showInvitationSlider = function showInvitationSlider(e) {
	          e.stopPropagation();
	          _this2.showInvitationPlace(main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_DISABLED_TEXT'), e.target, 'default-invitation');
	        };
	        var showInvitationHelper = function showInvitationHelper() {
	          _this2.showInfoHelper('limit_why_team_invites');
	        };
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-invitation-widget-invite\">\n\t\t\t\t\t<div class=\"intranet-invitation-widget-invite-main\">\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-inner\">\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-content\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--invite\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-content\">\n\t\t\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-name\">\n\t\t\t\t\t\t\t\t\t\t<span>\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-link\">\n\t\t\t\t\t\t\t\t\t\t<span onclick=\"", "\" class=\"intranet-invitation-widget-item-link-text\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<a onclick=\"", "\" class=\"intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--invite\"> \n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE'), showInvitationHelper, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_DESC'), showInvitationSlider, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE'));
	      });
	    }
	  }]);
	  return InvitationContent;
	}(Content);

	var _templateObject$1;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getCounterWrapper = /*#__PURE__*/new WeakSet();
	var _showCounter = /*#__PURE__*/new WeakSet();
	var _getCounter = /*#__PURE__*/new WeakSet();
	var _getCounterValue = /*#__PURE__*/new WeakSet();
	var _onFirstWatchNewStructure = /*#__PURE__*/new WeakSet();
	var StructureContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(StructureContent, _Content);
	  function StructureContent(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, StructureContent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StructureContent).call(this, options));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onFirstWatchNewStructure);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getCounterValue);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getCounter);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _showCounter);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getCounterWrapper);
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.StructureContent');
	    return _this;
	  }
	  babelHelpers.createClass(StructureContent, [{
	    key: "getConfig",
	    value: function getConfig() {
	      _classPrivateMethodGet(this, _showCounter, _showCounter2).call(this);
	      if (this.getOptions().shouldShowStructureCounter) {
	        main_core_events.EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', _classPrivateMethodGet(this, _onFirstWatchNewStructure, _onFirstWatchNewStructure2).bind(this));
	      }
	      return {
	        html: this.getLayout(),
	        flex: 3
	      };
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this2 = this;
	      return this.cache.remember('layout', function () {
	        var onclick = function onclick() {
	          Analytics.send(Analytics.EVENT_OPEN_STRUCTURE);
	        };
	        return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-invitation-widget-item intranet-invitation-widget-item--company intranet-invitation-widget-item--active\">\n\t\t\t\t\t<div class=\"intranet-invitation-widget-item-logo\"></div>\n\t\t\t\t\t<div class=\"intranet-invitation-widget-item-content\">\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-name\">\n\t\t\t\t\t\t\t<span>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<a onclick=\"", "\" href=\"", "\" class=\"intranet-invitation-widget-item-btn\"> \n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_STRUCTURE'), onclick, _this2.getOptions().link, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EDIT'));
	      });
	    }
	  }]);
	  return StructureContent;
	}(Content);
	function _getCounterWrapper2() {
	  var _this3 = this;
	  return this.cache.remember('counter-wrapper', function () {
	    return _this3.getLayout().querySelector('.intranet-invitation-widget-item-name');
	  });
	}
	function _showCounter2() {
	  if (_classPrivateMethodGet(this, _getCounterValue, _getCounterValue2).call(this) > 0) {
	    main_core.Dom.addClass(_classPrivateMethodGet(this, _getCounter, _getCounter2).call(this).getContainer(), 'invitation-structure-counter');
	    _classPrivateMethodGet(this, _getCounter, _getCounter2).call(this).renderTo(_classPrivateMethodGet(this, _getCounterWrapper, _getCounterWrapper2).call(this));
	  }
	}
	function _getCounter2() {
	  var _this4 = this;
	  return this.cache.remember('counter', function () {
	    return new ui_cnt.Counter({
	      value: _classPrivateMethodGet(_this4, _getCounterValue, _getCounterValue2).call(_this4),
	      color: ui_cnt.Counter.Color.DANGER
	    });
	  });
	}
	function _getCounterValue2() {
	  return this.getOptions().shouldShowStructureCounter ? 1 : 0;
	}
	function _onFirstWatchNewStructure2() {
	  var value = _classPrivateMethodGet(this, _getCounter, _getCounter2).call(this).value;
	  if (!main_core.Type.isNumber(value)) {
	    return;
	  }
	  if (!this.getOptions().shouldShowStructureCounter) {
	    return;
	  }
	  this.getOptions().shouldShowStructureCounter = false;
	  _classPrivateMethodGet(this, _getCounter, _getCounter2).call(this).destroy();
	  this.cache["delete"]('counter');
	}

	var _templateObject$2, _templateObject2, _templateObject3;
	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _rightType = /*#__PURE__*/new WeakMap();
	var _showCounter$1 = /*#__PURE__*/new WeakSet();
	var _onReceiveCounterValue = /*#__PURE__*/new WeakSet();
	var _getCounter$1 = /*#__PURE__*/new WeakSet();
	var _getCounterWrapper$1 = /*#__PURE__*/new WeakSet();
	var EmployeesContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(EmployeesContent, _Content);
	  function EmployeesContent(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, EmployeesContent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EmployeesContent).call(this, options));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getCounterWrapper$1);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getCounter$1);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onReceiveCounterValue);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _showCounter$1);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _rightType, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.EmployeesContent');
	    return _this;
	  }
	  babelHelpers.createClass(EmployeesContent, [{
	    key: "getConfig",
	    value: function getConfig() {
	      var _this2 = this;
	      return {
	        html: this.getOptions().awaitData.then(function (response) {
	          _this2.setOptions(_objectSpread$1(_objectSpread$1({}, response.data.users), _this2.getOptions()));
	          _classPrivateMethodGet$1(_this2, _showCounter$1, _showCounter2$1).call(_this2);
	          return _this2.getLayout();
	        }),
	        flex: 5,
	        sizeLoader: 55
	      };
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this3 = this;
	      return this.cache.remember('layout', function () {
	        return main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-invitation-widget-item intranet-invitation-widget-item--emp ", "\">\n\t\t\t\t\t<div class=\"intranet-invitation-widget-inner\">\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-content\">\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-content\">\n\t\t\t\t\t\t\t\t<div onclick=\"", "\" class=\"intranet-invitation-widget-item-progress ", "\"/>\n\t\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-employees\">\n\t\t\t\t\t\t\t\t\t<div onclick=\"", "\" class=\"intranet-invitation-widget-item-name\">\n\t\t\t\t\t\t\t\t\t\t<span style=\"margin-right: 2px;\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div onclick=\"", "\" class=\"intranet-invitation-widget-item-num\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this3.getOptions().isLimit ? 'intranet-invitation-widget-item--emp-alert' : null, _this3.showUserList(), _this3.getOptions().isLimit ? 'intranet-invitation-widget-item-progress--crit' : 'intranet-invitation-widget-item-progress--full', _this3.showUserList(), main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES'), _this3.showUserList(), _this3.getOptions().currentUserCountMessage, _this3.getDetail(), _this3.getOptions().isAdmin ? _this3.getSelectorRights() : null);
	      });
	    }
	  }, {
	    key: "getDetail",
	    value: function getDetail() {
	      var _this4 = this;
	      return this.cache.remember('detail', function () {
	        var content = '';
	        if (Number(_this4.getOptions().maxUserCount) === 0) {
	          content = main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT');
	        } else if (_this4.getOptions().isLimit) {
	          content = main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT');
	        } else {
	          content = _this4.getOptions().leftCountMessage;
	        }
	        return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div onclick=\"", "\" class=\"intranet-invitation-widget-item-detail\">\n\t\t\t\t\t<span class=\"intranet-invitation-widget-item-link-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t"])), _this4.showUserList(), content);
	      });
	    }
	  }, {
	    key: "showUserList",
	    value: function showUserList() {
	      return this.cache.remember('showUserList', function () {
	        return function () {
	          Analytics.send(Analytics.EVENT_OPEN_USER_LIST);
	          document.location.href = '/company/';
	        };
	      });
	    }
	  }, {
	    key: "getSelectorRights",
	    value: function getSelectorRights() {
	      var _this5 = this;
	      return this.cache.remember('selector-rights', function () {
	        var showMenu = function showMenu(e) {
	          e.stopPropagation();
	          _this5.getRightsMenu(e.target).toggle();
	        };
	        var button = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div onclick=\"", "\" class=\"intranet-invitation-widget-item-menu\"></div>\n\t\t\t"])), showMenu);
	        _this5.subscribe('right-selected', function (event) {
	          var menu = _this5.getRightsMenu(button);
	          menu.close();
	          menu.destroy();
	          if (event.data.type) {
	            _this5.cache["delete"]('menu-rights');
	            babelHelpers.classPrivateFieldSet(_this5, _rightType, event.data.type);
	          }
	        });
	        return button;
	      });
	    }
	  }, {
	    key: "getRightsMenu",
	    value: function getRightsMenu(element) {
	      var _this6 = this;
	      return this.cache.remember('menu-rights', function () {
	        return new main_popup.Menu("menu-rights-".concat(main_core.Text.getRandom()), element, _this6.getMenuRightsItems(), {
	          autoHide: true,
	          offsetLeft: 10,
	          offsetTop: 0,
	          angle: true,
	          className: 'license-right-popup-men',
	          events: {
	            onPopupShow: function onPopupShow(popup) {
	              main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, _this6.getEventNamespace() + ':showRightMenu', new main_core_events.BaseEvent({
	                data: {
	                  popup: popup
	                }
	              }));
	            },
	            onPopupClose: function onPopupClose(popup) {
	              main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, _this6.getEventNamespace() + ':closeRightMenu', new main_core_events.BaseEvent({
	                data: {
	                  popup: popup
	                }
	              }));
	            },
	            onPopupFirstShow: function onPopupFirstShow(popup) {
	              main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', function () {
	                popup.close();
	              });
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getMenuRightsItems",
	    value: function getMenuRightsItems() {
	      var _this7 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _rightType)) {
	        babelHelpers.classPrivateFieldSet(this, _rightType, this.getOptions().rightType);
	      }
	      return [{
	        text: main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_SETTING_ALL_INVITE'),
	        className: babelHelpers.classPrivateFieldGet(this, _rightType) === 'all' ? 'menu-popup-item-accept' : '',
	        onclick: function onclick() {
	          _this7.saveInvitationRightSetting('all').then(function () {
	            _this7.emit('right-selected', new main_core_events.BaseEvent({
	              data: {
	                type: 'all'
	              }
	            }));
	          });
	        }
	      }, {
	        text: main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_SETTING_ADMIN_INVITE'),
	        className: babelHelpers.classPrivateFieldGet(this, _rightType) === 'admin' ? 'menu-popup-item-accept' : '',
	        onclick: function onclick() {
	          _this7.saveInvitationRightSetting('admin').then(function () {
	            _this7.emit('right-selected', new main_core_events.BaseEvent({
	              data: {
	                type: 'admin'
	              }
	            }));
	          });
	        }
	      }];
	    }
	  }, {
	    key: "saveInvitationRightSetting",
	    value: function saveInvitationRightSetting(type) {
	      return main_core.ajax.runAction("intranet.invitationwidget.saveInvitationRight", {
	        data: {
	          type: type
	        }
	      });
	    }
	  }]);
	  return EmployeesContent;
	}(Content);
	function _showCounter2$1() {
	  if (this.getOptions().invitationCounter > 0) {
	    _classPrivateMethodGet$1(this, _getCounter$1, _getCounter2$1).call(this).renderTo(_classPrivateMethodGet$1(this, _getCounterWrapper$1, _getCounterWrapper2$1).call(this));
	  }
	  BX.addCustomEvent('onPullEvent-main', _classPrivateMethodGet$1(this, _onReceiveCounterValue, _onReceiveCounterValue2).bind(this));
	}
	function _onReceiveCounterValue2(command, params) {
	  if (command === 'user_counter' && params[BX.message('SITE_ID')]) {
	    var counters = BX.clone(params[BX.message('SITE_ID')]);
	    var value = counters[this.getOptions().counterId];
	    if (!main_core.Type.isNumber(value)) {
	      return;
	    }
	    _classPrivateMethodGet$1(this, _getCounter$1, _getCounter2$1).call(this).update(value);
	    this.getOptions().invitationCounter = value;
	    if (value > 0) {
	      _classPrivateMethodGet$1(this, _getCounter$1, _getCounter2$1).call(this).renderTo(_classPrivateMethodGet$1(this, _getCounterWrapper$1, _getCounterWrapper2$1).call(this));
	    } else {
	      _classPrivateMethodGet$1(this, _getCounter$1, _getCounter2$1).call(this).destroy();
	      this.cache["delete"]('counter');
	    }
	  }
	}
	function _getCounter2$1() {
	  var _this8 = this;
	  return this.cache.remember('counter', function () {
	    return new ui_cnt.Counter({
	      value: Number(_this8.getOptions().invitationCounter),
	      color: ui_cnt.Counter.Color.DANGER
	    });
	  });
	}
	function _getCounterWrapper2$1() {
	  var _this9 = this;
	  return this.cache.remember('counter-wrapper', function () {
	    return _this9.getLayout().querySelector('.intranet-invitation-widget-item-name');
	  });
	}

	var _templateObject$3, _templateObject2$1;
	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ExtranetContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(ExtranetContent, _Content);
	  function ExtranetContent(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, ExtranetContent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExtranetContent).call(this, options));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "articleCode", "6770709");
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.ExtranetContent');
	    return _this;
	  }
	  babelHelpers.createClass(ExtranetContent, [{
	    key: "getConfig",
	    value: function getConfig() {
	      var _this2 = this;
	      return {
	        html: this.getOptions().awaitData.then(function (response) {
	          _this2.setOptions(_objectSpread$2(_objectSpread$2({}, response.data.users), _this2.getOptions()));
	          return _this2.getLayout();
	        }),
	        minHeight: '55px',
	        sizeLoader: 37,
	        marginBottom: 24,
	        secondary: true
	      };
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this3 = this;
	      return this.cache.remember('layout', function () {
	        var showInvitationSlider = function showInvitationSlider(e) {
	          e.stopPropagation();
	          _this3.showInvitationPlace(main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_DISABLED_TEXT'), e.target, 'extranet');
	        };
	        var showExtranetHelper = function showExtranetHelper() {
	          BX.Helper.show("redirect=detail&code=".concat(_this3.articleCode));
	          _this3.sendAnalytics(_this3.articleCode);
	        };
	        return main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t<div class=\"intranet-invitation-widget-content\">\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--ext\"></div>\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-link\">\n\t\t\t\t\t\t\t\t<span onclick=\"", "\" class=\"intranet-invitation-widget-item-link-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button onclick=\"", "\" class=\"intranet-invitation-widget-item-btn\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t"])), _this3.getWrapperClass(), main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EXTRANET'), showExtranetHelper, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_EXTRANET_DESC'), _this3.getCountUserMessage(), showInvitationSlider, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE'));
	      });
	    }
	  }, {
	    key: "getWrapperClass",
	    value: function getWrapperClass() {
	      var _this4 = this;
	      return this.cache.remember('wrapper-class', function () {
	        var baseClass = 'intranet-invitation-widget-item intranet-invitation-widget-item--wide';
	        if (_this4.getOptions().currentExtranetUserCount > 0) {
	          return baseClass + ' intranet-invitation-widget-item--active';
	        }
	        return baseClass;
	      });
	    }
	  }, {
	    key: "getCountUserMessage",
	    value: function getCountUserMessage() {
	      var _this5 = this;
	      return this.cache.remember('count-user-message', function () {
	        if (_this5.getOptions().currentExtranetUserCount > 0) {
	          return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"intranet-invitation-widget-item-ext-users\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), _this5.getOptions().currentExtranetUserCountMessage);
	        }
	        return null;
	      });
	    }
	  }]);
	  return ExtranetContent;
	}(Content);

	var _templateObject$4;
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _openChat = /*#__PURE__*/new WeakMap();
	var CollabContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(CollabContent, _Content);
	  function CollabContent(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, CollabContent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CollabContent).call(this, options));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "articleCode", '22706764');
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _openChat, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.CollabContent');
	    return _this;
	  }
	  babelHelpers.createClass(CollabContent, [{
	    key: "getConfig",
	    value: function getConfig() {
	      var _this2 = this;
	      return {
	        html: this.getOptions().awaitData.then(function (response) {
	          var Messenger = response.Messenger,
	            CreatableChat = response.CreatableChat;
	          babelHelpers.classPrivateFieldSet(_this2, _openChat, function () {
	            Messenger.openChatCreation(CreatableChat.collab);
	            Analytics.sendCreateCollab();
	          });
	          return _this2.getLayout();
	        }),
	        minHeight: '55px',
	        sizeLoader: 37,
	        marginBottom: 24,
	        secondary: true
	      };
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this3 = this;
	      return this.cache.remember('layout', function () {
	        var showInvitationSlider = function showInvitationSlider(e) {
	          babelHelpers.classPrivateFieldGet(_this3, _openChat).call(_this3);
	          e.stopPropagation();
	        };
	        var showCollabHelper = function showCollabHelper() {
	          BX.Helper.show("redirect=detail&code=".concat(_this3.articleCode));
	          _this3.sendAnalytics(_this3.articleCode);
	        };
	        return main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t<div class=\"intranet-invitation-widget-content\">\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--collab\">\n\t\t\t\t\t\t\t<div class=\"ui-icon-set --collab\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intranet-invitation-widget-item-link\">\n\t\t\t\t\t\t\t\t<span onclick=\"", "\" class=\"intranet-invitation-widget-item-link-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button onclick=\"", "\" class=\"intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--collab\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t"])), _this3.getWrapperClass(), main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB'), showCollabHelper, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB_DESC'), showInvitationSlider, main_core.Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB_CREATE'));
	      });
	    }
	  }, {
	    key: "getWrapperClass",
	    value: function getWrapperClass() {
	      return this.cache.remember('wrapper-class', function () {
	        return 'intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--collab';
	      });
	    }
	  }]);
	  return CollabContent;
	}(Content);

	var _templateObject$5, _templateObject2$2;
	var UserOnlineContent = /*#__PURE__*/function (_Content) {
	  babelHelpers.inherits(UserOnlineContent, _Content);
	  function UserOnlineContent() {
	    babelHelpers.classCallCheck(this, UserOnlineContent);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(UserOnlineContent).apply(this, arguments));
	  }
	  babelHelpers.createClass(UserOnlineContent, [{
	    key: "getLoader",
	    value: function getLoader() {
	      return this.cache.remember('loader', function () {
	        return new main_loader.Loader({
	          size: 45
	        });
	      });
	    }
	  }, {
	    key: "getComponentContent",
	    value: function getComponentContent() {
	      var _this = this;
	      return this.cache.remember('component-content', function () {
	        var contentContainer = main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div data-role=\"invitation-widget-ustat-online\" class=\"invitation-widget-ustat-online\"/>\n\t\t\t"])));
	        main_core.Dom.style(contentContainer, 'min-height', '70px');
	        _this.getLoader().show(contentContainer);
	        main_core.ajax.runAction("intranet.invitationwidget.getUserOnlineComponent").then(function (response) {
	          _this.getLoader().hide();
	          var assets = response.data.assets;
	          BX.load([].concat(babelHelpers.toConsumableArray(assets['css']), babelHelpers.toConsumableArray(assets['js'])), function () {
	            main_core.Runtime.html(null, babelHelpers.toConsumableArray(assets['string']).join('\n'), {
	              useAdjacentHTML: true
	            }).then(function () {
	              main_core.Runtime.html(contentContainer, response.data.html).then(function () {
	                _this.getLoader().destroy();
	              });
	            });
	          });
	        });
	        return contentContainer;
	      });
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this2 = this;
	      return this.cache.remember('layout', function () {
	        return main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--no-padding\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this2.getComponentContent());
	      });
	    }
	  }]);
	  return UserOnlineContent;
	}(Content);

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache = /*#__PURE__*/new WeakMap();
	var _getAwaitData = /*#__PURE__*/new WeakSet();
	var _getContent = /*#__PURE__*/new WeakSet();
	var _getInvitationContent = /*#__PURE__*/new WeakSet();
	var _getStructureContent = /*#__PURE__*/new WeakSet();
	var _getEmployeesContent = /*#__PURE__*/new WeakSet();
	var _getExtranetContent = /*#__PURE__*/new WeakSet();
	var _getCollabContent = /*#__PURE__*/new WeakSet();
	var _getUserOnlineContent = /*#__PURE__*/new WeakSet();
	var _getPopupContainer = /*#__PURE__*/new WeakSet();
	var _setEventHandler = /*#__PURE__*/new WeakSet();
	var InvitationPopup = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(InvitationPopup, _EventEmitter);
	  function InvitationPopup(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, InvitationPopup);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InvitationPopup).call(this));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _setEventHandler);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getPopupContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getUserOnlineContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getCollabContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getExtranetContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getEmployeesContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getStructureContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getInvitationContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getContent);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getAwaitData);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _this.setEventNamespace('BX.Intranet.InvitationWidget.Popup');
	    _this.setOptions(options);
	    _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _setEventHandler, _setEventHandler2).call(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(InvitationPopup, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.classPrivateFieldGet(this, _cache).set('options', options);
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.classPrivateFieldGet(this, _cache).get('options', {});
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      this.getPopup().close();
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this2 = this;
	      return babelHelpers.classPrivateFieldGet(this, _cache).remember('popup', function () {
	        return new ui_popupcomponentsmaker.PopupComponentsMaker({
	          id: 'invitation-popup',
	          target: _this2.getOptions().target,
	          width: 350,
	          content: _classPrivateMethodGet$2(_this2, _getContent, _getContent2).call(_this2)
	        });
	      });
	    } //This is the method for popup content configuration
	  }]);
	  return InvitationPopup;
	}(main_core_events.EventEmitter);
	function _getAwaitData2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('await-data', function () {
	    return new Promise(function (resolve, reject) {
	      main_core.ajax.runAction("intranet.invitationwidget.getData", {
	        data: {},
	        analyticsLabel: {
	          headerPopup: "Y"
	        }
	      }).then(resolve)["catch"](reject);
	    });
	  });
	}
	function _getContent2() {
	  var _this3 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('content', function () {
	    return [_classPrivateMethodGet$2(_this3, _getInvitationContent, _getInvitationContent2).call(_this3).getConfig(), {
	      html: [_classPrivateMethodGet$2(_this3, _getStructureContent, _getStructureContent2).call(_this3).getConfig(), _classPrivateMethodGet$2(_this3, _getEmployeesContent, _getEmployeesContent2).call(_this3).getConfig()],
	      marginBottom: 24
	    }, _this3.getOptions().isExtranetAvailable ? _classPrivateMethodGet$2(_this3, _getExtranetContent, _getExtranetContent2).call(_this3).getConfig() : null, _this3.getOptions().isCollabAvailable ? _classPrivateMethodGet$2(_this3, _getCollabContent, _getCollabContent2).call(_this3).getConfig() : null, _classPrivateMethodGet$2(_this3, _getUserOnlineContent, _getUserOnlineContent2).call(_this3).getConfig()];
	  });
	}
	function _getInvitationContent2() {
	  var _this4 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('invitation-content', function () {
	    return new InvitationContent({
	      isAdmin: _this4.getOptions().isAdmin,
	      invitationLink: _this4.getOptions().params.invitationLink,
	      isInvitationAvailable: _this4.getOptions().isInvitationAvailable
	    });
	  });
	}
	function _getStructureContent2() {
	  var _this5 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('structure-content', function () {
	    return new StructureContent({
	      link: _this5.getOptions().params.structureLink,
	      shouldShowStructureCounter: _this5.getOptions().params.shouldShowStructureCounter
	    });
	  });
	}
	function _getEmployeesContent2() {
	  var _this6 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('employees-content', function () {
	    return new EmployeesContent({
	      isAdmin: _this6.getOptions().isAdmin,
	      awaitData: _classPrivateMethodGet$2(_this6, _getAwaitData, _getAwaitData2).call(_this6),
	      invitationCounter: _this6.getOptions().params.invitationCounter,
	      counterId: _this6.getOptions().params.counterId
	    });
	  });
	}
	function _getExtranetContent2() {
	  var _this7 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('extranet-content', function () {
	    return new ExtranetContent({
	      isAdmin: _this7.getOptions().isAdmin,
	      awaitData: _classPrivateMethodGet$2(_this7, _getAwaitData, _getAwaitData2).call(_this7),
	      invitationLink: _this7.getOptions().params.invitationLink,
	      isInvitationAvailable: _this7.getOptions().isInvitationAvailable
	    });
	  });
	}
	function _getCollabContent2() {
	  var _this8 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('collab-content', function () {
	    return new CollabContent({
	      isAdmin: _this8.getOptions().isAdmin,
	      awaitData: main_core.Runtime.loadExtension('im.public', 'im.v2.component.content.chat-forms.forms')
	    });
	  });
	}
	function _getUserOnlineContent2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('user-online-content', function () {
	    return new UserOnlineContent();
	  });
	}
	function _getPopupContainer2() {
	  var _this9 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('popup-container', function () {
	    return _this9.getPopup().getPopup().getPopupContainer();
	  });
	}
	function _setEventHandler2() {
	  var _this10 = this;
	  var autoHideHandler = function autoHideHandler(event) {
	    if (event.data.popup) {
	      setTimeout(function () {
	        main_core.Event.bind(_classPrivateMethodGet$2(_this10, _getPopupContainer, _getPopupContainer2).call(_this10), 'click', function () {
	          event.data.popup.close();
	        });
	      }, 100);
	    }
	  };
	  var close = function close() {
	    _this10.close();
	  };
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.EmployeesContent:showRightMenu', autoHideHandler);
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:show', autoHideHandler);
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UstatOnline:showPopup', autoHideHandler);
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', close);
	}

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache$1 = /*#__PURE__*/new WeakMap();
	var _showCounter$2 = /*#__PURE__*/new WeakSet();
	var _onReceiveCounterValue$1 = /*#__PURE__*/new WeakSet();
	var _getCounterWrapper$2 = /*#__PURE__*/new WeakSet();
	var _getCounter$2 = /*#__PURE__*/new WeakSet();
	var _getPopup = /*#__PURE__*/new WeakSet();
	var _getCounterValue$1 = /*#__PURE__*/new WeakSet();
	var _onFirstWatchNewStructure$1 = /*#__PURE__*/new WeakSet();
	var InvitationWidget = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(InvitationWidget, _EventEmitter);
	  function InvitationWidget(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, InvitationWidget);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InvitationWidget).call(this));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _onFirstWatchNewStructure$1);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getCounterValue$1);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getPopup);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getCounter$2);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getCounterWrapper$2);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _onReceiveCounterValue$1);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _showCounter$2);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _this.setEventNamespace('BX.Intranet.InvitationWidget');
	    _this.setOptions(options);
	    Analytics.isAdmin = _this.getOptions().isCurrentUserAdmin;
	    main_core.Event.bind(_this.getOptions().button, 'click', function () {
	      Analytics.send(Analytics.EVENT_SHOW);
	      _classPrivateMethodGet$3(babelHelpers.assertThisInitialized(_this), _getPopup, _getPopup2).call(babelHelpers.assertThisInitialized(_this)).show();
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.NotifyPanel:showInvitationWidget', function () {
	      _classPrivateMethodGet$3(babelHelpers.assertThisInitialized(_this), _getPopup, _getPopup2).call(babelHelpers.assertThisInitialized(_this)).show();
	    });
	    _classPrivateMethodGet$3(babelHelpers.assertThisInitialized(_this), _showCounter$2, _showCounter2$2).call(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(InvitationWidget, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.classPrivateFieldGet(this, _cache$1).set('options', options);
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.classPrivateFieldGet(this, _cache$1).get('options', {});
	    }
	  }]);
	  return InvitationWidget;
	}(main_core_events.EventEmitter);
	function _showCounter2$2() {
	  if (this.getOptions().invitationCounter > 0) {
	    _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).renderTo(_classPrivateMethodGet$3(this, _getCounterWrapper$2, _getCounterWrapper2$2).call(this));
	  }
	  BX.addCustomEvent('onPullEvent-main', _classPrivateMethodGet$3(this, _onReceiveCounterValue$1, _onReceiveCounterValue2$1).bind(this));
	  if (this.getOptions().shouldShowStructureCounter) {
	    main_core_events.EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', _classPrivateMethodGet$3(this, _onFirstWatchNewStructure$1, _onFirstWatchNewStructure2$1).bind(this));
	  }
	}
	function _onReceiveCounterValue2$1(command, params) {
	  if (command === 'user_counter' && params[BX.message('SITE_ID')]) {
	    var counters = BX.clone(params[BX.message('SITE_ID')]);
	    var value = counters[this.getOptions().counterId];
	    if (!main_core.Type.isNumber(value)) {
	      return;
	    }
	    if (this.getOptions().shouldShowStructureCounter) {
	      value++;
	    }
	    _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).update(value);
	    this.getOptions().invitationCounter = value;
	    if (value > 0) {
	      _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).renderTo(_classPrivateMethodGet$3(this, _getCounterWrapper$2, _getCounterWrapper2$2).call(this));
	    } else {
	      _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).destroy();
	      babelHelpers.classPrivateFieldGet(this, _cache$1)["delete"]('counter');
	    }
	  }
	}
	function _getCounterWrapper2$2() {
	  var _this2 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('counter-wrapper', function () {
	    return _this2.getOptions().button.querySelector('.invitation-widget-counter');
	  });
	}
	function _getCounter2$2() {
	  var _this3 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('counter', function () {
	    return new ui_cnt.Counter({
	      value: _classPrivateMethodGet$3(_this3, _getCounterValue$1, _getCounterValue2$1).call(_this3),
	      color: ui_cnt.Counter.Color.DANGER
	    });
	  });
	}
	function _getPopup2() {
	  var _this4 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('popup', function () {
	    return new InvitationPopup({
	      isAdmin: _this4.getOptions().isCurrentUserAdmin,
	      target: _this4.getOptions().button,
	      isExtranetAvailable: _this4.getOptions().isExtranetAvailable,
	      isCollabAvailable: _this4.getOptions().isCollabAvailable,
	      isInvitationAvailable: _this4.getOptions().isInvitationAvailable,
	      params: {
	        structureLink: _this4.getOptions().structureLink,
	        invitationLink: _this4.getOptions().invitationLink,
	        invitationCounter: _this4.getOptions().invitationCounter,
	        counterId: _this4.getOptions().counterId,
	        shouldShowStructureCounter: _this4.getOptions().shouldShowStructureCounter
	      }
	    });
	  });
	}
	function _getCounterValue2$1() {
	  var _this$getOptions$shou;
	  var counterValue = Number(this.getOptions().invitationCounter);
	  if ((_this$getOptions$shou = this.getOptions().shouldShowStructureCounter) !== null && _this$getOptions$shou !== void 0 ? _this$getOptions$shou : false) {
	    counterValue++;
	  }
	  return counterValue;
	}
	function _onFirstWatchNewStructure2$1() {
	  var value = _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).value;
	  if (!main_core.Type.isNumber(value)) {
	    return;
	  }
	  if (!this.getOptions().shouldShowStructureCounter) {
	    return;
	  }
	  value--;
	  this.getOptions().shouldShowStructureCounter = false;
	  _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).update(value);
	  this.getOptions().invitationCounter = value;
	  if (value > 0) {
	    _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).renderTo(_classPrivateMethodGet$3(this, _getCounterWrapper$2, _getCounterWrapper2$2).call(this));
	  } else {
	    _classPrivateMethodGet$3(this, _getCounter$2, _getCounter2$2).call(this).destroy();
	    babelHelpers.classPrivateFieldGet(this, _cache$1)["delete"]('counter');
	  }
	}

	exports.InvitationWidget = InvitationWidget;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI.Analytics,BX.UI,BX.UI,BX.Main,BX.Event,BX,BX));
//# sourceMappingURL=script.js.map

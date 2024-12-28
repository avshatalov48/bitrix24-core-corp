/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,ui_avatar,ui_popupcomponentsmaker,main_qrcode,main_loader,ui_hint,main_core_cache,ui_qrauthorization,main_core,main_popup,main_core_events,im_v2_lib_desktopApi) {
	'use strict';

	var Options = function Options() {
	  babelHelpers.classCallCheck(this, Options);
	};
	babelHelpers.defineProperty(Options, "eventNameSpace", 'BX.Intranet.Userprofile:');

	var _templateObject, _templateObject2;
	var StressLevel = /*#__PURE__*/function () {
	  function StressLevel() {
	    babelHelpers.classCallCheck(this, StressLevel);
	  }
	  babelHelpers.createClass(StressLevel, null, [{
	    key: "getOpenSliderFunction",
	    value: function getOpenSliderFunction(url) {
	      if (main_core.Type.isStringFilled(url)) {
	        return function () {
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + 'onNeedToHide');
	          BX.SidePanel.Instance.open(url, {
	            cacheable: false,
	            data: {},
	            width: 500
	          });
	        };
	      }
	      return function () {};
	    }
	  }, {
	    key: "showData",
	    value: function showData(data) {
	      data.value = parseInt(data.value || 0);
	      var result = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --vertical\" id=\"user-indicator-pulse\">\n\t\t\t\t<div class=\"system-auth-form__item-block --margin-bottom\">\n\t\t\t\t\t<div class=\"system-auth-form__stress-widget\">\n\t\t\t\t\t\t<div data-role=\"value-degree\" class=\"system-auth-form__stress-widget--arrow\" style=\"transform: rotate(90deg);\"></div>\n\t\t\t\t\t\t<div class=\"system-auth-form__stress-widget--content\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__stress-widget--content-title\">", "</div>\n\t\t\t\t\t\t\t<div data-role=\"value\" class=\"system-auth-form__stress-widget--content-progress ", "\">0</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --stress-widget-sp\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-block --center --width-100\">\n\t\t\t\t\t\t\t<span class=\"system-auth-form__stress-widget--status  --flex --", "\">", "</span>\n\t\t\t\t\t\t\t<span class=\"system-auth-form__icon-help\" onclick=\"", "\"></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --link-dotted\" onclick=\"", "\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-block --flex --center\">\n\t\t\t\t\t<div class=\"system-auth-form__stress-widget--message\">", "</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT'), data.value > 0 ? '' : '--empty', main_core.Text.encode(data.type), main_core.Text.encode(data.typeDescription), this.getOpenSliderFunction(data.url.result), this.getOpenSliderFunction(data.url.check), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON'), main_core.Text.encode(data.comment));
	      setTimeout(function () {
	        var intervalId = setInterval(function (value) {
	          value.current++;
	          value.node.innerHTML = value.current;
	          if (value.current >= value.end) {
	            clearInterval(intervalId);
	          }
	        }, 600 / data.value, {
	          current: 0,
	          end: data.value,
	          node: result.querySelector('[data-role="value"]')
	        });
	        result.querySelector('[data-role="value-degree"]').style.transform = 'rotate(' + 1.8 * data.value + 'deg)';
	      }, 1000);
	      return result;
	    }
	  }, {
	    key: "showEmpty",
	    value: function showEmpty(_ref) {
	      var check = _ref.url.check;
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --vertical --empty-stress --clickable\" onclick=\"", "\">\n\t\t\t\t<div class=\"system-auth-form__item-block --margin-bottom\">\n\t\t\t\t\t<div class=\"system-auth-form__stress-widget\">\n\t\t\t\t\t\t<div data-role=\"value-degree\" class=\"system-auth-form__stress-widget--arrow\" style=\"transform: rotate(90deg);\"></div>\n\t\t\t\t\t\t<div class=\"system-auth-form__stress-widget--content\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__stress-widget--content-title\">", "</div>\n\t\t\t\t\t\t\t<div data-role=\"value\" class=\"system-auth-form__stress-widget--content-progress --empty\">?</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --stress-widget-sp\">\n\t\t\t\t\t\t<div class=\"system-auth-form__stress-widget--message\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-block --flex --center\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title --link-dotted\">", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-new\">\n\t\t\t\t\t<div class=\"system-auth-form__item-new--title\">", "</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), this.getOpenSliderFunction(check), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_COME_ON'));
	    }
	  }, {
	    key: "getPromise",
	    value: function getPromise(_ref2) {
	      var _this = this;
	      var signedParameters = _ref2.signedParameters,
	        componentName = _ref2.componentName,
	        userId = _ref2.userId,
	        data = _ref2.data;
	      return new Promise(function (resolve, reject) {
	        var promise = data ? Promise.resolve({
	          data: data
	        }) : main_core.ajax.runAction('socialnetwork.api.user.stresslevel.get', {
	          signedParameters: signedParameters,
	          data: {
	            c: componentName,
	            fields: {
	              userId: userId
	            }
	          }
	        });
	        promise.then(function (_ref3) {
	          var data = _ref3.data;
	          if (data && data.id !== undefined && data.value !== undefined) {
	            return resolve(_this.showData(data));
	          }
	          var node = main_core.Loc.getMessage('USER_ID') === userId ? _this.showEmpty(data) : document.createElement('DIV');
	          return resolve(node);
	        })["catch"](function (error) {
	          resolve(_this.showData({
	            id: undefined,
	            value: undefined,
	            urls: {
	              check: undefined
	            }
	          }));
	        });
	      });
	    }
	  }]);
	  return StressLevel;
	}();

	var _templateObject$1;
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _params = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var ThemePicker = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ThemePicker, _EventEmitter);
	  function ThemePicker(data) {
	    var _this;
	    babelHelpers.classCallCheck(this, ThemePicker);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ThemePicker).call(this));
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _params, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _params, Object.assign({}, data));
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).lightning = String(data.id).indexOf('light:') === 0 ? 'light' : String(data.id).indexOf('dark:') === 0 ? 'dark' : null;
	    _this.applyTheme = _this.applyTheme.bind(babelHelpers.assertThisInitialized(_this));
	    _this.setEventNamespace(Options.eventNameSpace);
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', function (_ref) {
	      var _ref$data = _ref.data,
	        id = _ref$data.id,
	        theme = _ref$data.theme;
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).id = id;
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).lightning = String(id).indexOf('light:') === 0 ? 'light' : String(id).indexOf('dark:') === 0 ? 'dark' : null;
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).title = theme.title;
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).previewImage = theme.previewImage;
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _params).previewColor = theme.previewColor;
	      _this.applyTheme();
	    });
	    return _this;
	  }
	  babelHelpers.createClass(ThemePicker, [{
	    key: "applyTheme",
	    value: function applyTheme() {
	      var container = this.getContainer();
	      if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _params).previewImage) && babelHelpers.classPrivateFieldGet(this, _params).lightning) {
	        container.style.removeProperty('backgroundImage');
	        container.style.removeProperty('backgroundSize');
	        container.style.backgroundImage = 'url("' + babelHelpers.classPrivateFieldGet(this, _params).previewImage + '")';
	        container.style.backgroundSize = 'cover';
	      } else {
	        container.style.background = 'none';
	      }
	      if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _params).previewColor)) {
	        this.getContainer().style.backgroundColor = babelHelpers.classPrivateFieldGet(this, _params).previewColor;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _params).lightning) {
	        container.style.backgroundColor = 'rgba(255,255,255,1)';
	      }
	      main_core.Dom.removeClass(container, '--light --dark');
	      main_core.Dom.addClass(container, '--' + babelHelpers.classPrivateFieldGet(this, _params).lightning);
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        return babelHelpers.classPrivateFieldGet(this, _container);
	      }
	      var onclick = function onclick() {
	        BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog(false);
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	      };
	      babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --border ", " --padding-sm\">\n\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t<div data-role=\"preview-color\" class=\"system-auth-form__item-logo--image --theme\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-container --flex --column\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title --white-space --block\">\n\t\t\t\t\t\t<span data-role=\"title\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-content --margin-top-auto --center --center-force\">\n\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn\" onclick=\"", "\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), babelHelpers.classPrivateFieldGet(this, _params).lightning ? '--' + babelHelpers.classPrivateFieldGet(this, _params).lightning : '', main_core.Loc.getMessage('AUTH_THEME_DIALOG'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CHANGE')));
	      setTimeout(this.applyTheme, 0);
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }], [{
	    key: "getPromise",
	    value: function getPromise() {
	      var _this2 = this;
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getThemePickerData', {
	          mode: 'class'
	        }).then(function (response) {
	          var themePicker = new _this2(response.data);
	          resolve(themePicker.getContainer());
	        })["catch"](reject);
	      });
	    }
	  }]);
	  return ThemePicker;
	}(main_core_events.EventEmitter);

	var _templateObject$2, _templateObject2$1, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _renderUsers = /*#__PURE__*/new WeakSet();
	var Ustat = /*#__PURE__*/function () {
	  function Ustat(data) {
	    babelHelpers.classCallCheck(this, Ustat);
	    _classPrivateMethodInitSpec(this, _renderUsers);
	    this.data = data;
	    this.onclickHandle = this.onclickHandle.bind(this);
	  }
	  babelHelpers.createClass(Ustat, [{
	    key: "showData",
	    value: function showData() {
	      var _classPrivateMethodGe = _classPrivateMethodGet(this, _renderUsers, _renderUsers2).call(this),
	        myPosition = _classPrivateMethodGe.myPosition,
	        userList = _classPrivateMethodGe.userList,
	        range = _classPrivateMethodGe.range;
	      var div;
	      if (!this.data['ENABLED']) {
	        div = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --without-stat\">\n\t\t\t\t<div class=\"system-auth-form__item-container --flex --column\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title\">", "</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --lighter\" data-role=\"empty-info\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_DISABLED'));
	      } else if (range > 0 && myPosition > 0) {
	        div = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --clickable\" onclick=\"", "\">\n\t\t\t\t<div class=\"system-auth-form__item-container\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title --without-margin\">", "</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-title --link-light --margin-s\">\n\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t<span class=\"system-auth-form__icon-help\" data-hint=\"", "\" data-hint-no-icon></span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-title --link-light\" data-role=\"empty-info\">", "</div>\n\n\t\t\t\t\t<div class=\"system-auth-form__item-title --white-space --margin-xl\">\n\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t<span class=\"system-auth-form__ustat-icon --up\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__userlist\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.onclickHandle, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING'), main_core.Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION', {
	          '#POSITION#': myPosition,
	          '#AMONG#': range
	        }), userList);
	      } else {
	        var onclick = range > 0 ? this.onclickHandle : function () {};
	        div = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --without-stat ", "\" onclick=\"", "\">\n\t\t\t\t<div class=\"system-auth-form__item-container --flex --column\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title\">", "</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --lighter\" data-role=\"empty-info\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), range > 0 ? '--clickable' : '', onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY_BRIEF'));
	      }
	      BX.UI.Hint.init(div);
	      return div;
	    }
	  }, {
	    key: "onclickHandle",
	    value: function onclickHandle(event) {
	      main_core_events.EventEmitter.emit(Options.eventNameSpace + 'onNeedToHide');
	      if (window['openIntranetUStat']) {
	        openIntranetUStat(event);
	      }
	    }
	  }, {
	    key: "showWideData",
	    value: function showWideData() {
	      var _classPrivateMethodGe2 = _classPrivateMethodGet(this, _renderUsers, _renderUsers2).call(this),
	        myPosition = _classPrivateMethodGe2.myPosition,
	        userList = _classPrivateMethodGe2.userList,
	        range = _classPrivateMethodGe2.range;
	      var div = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --center --padding-ustat ", "\">\n\t\t\t\t<div class=\"system-auth-form__item-image\">\n\t\t\t\t\t<div class=\"system-auth-form__item-image--src --ustat\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-container --overflow\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title --xl --without-margin\">", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__item-container --block\">\n\t\t\t\t\t<div class=\"system-auth-form__item-title --link-light\" data-role=\"empty-info\">", "</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container--inline\" data-role=\"my-position\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --link-light --without-margin --margin-right\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --white-space --margin-xl\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t<span class=\"system-auth-form__ustat-icon --up\"></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__userlist\" data-role=\"user-list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"system-auth-form__icon-help --absolute-right-bottom\" data-hint=\"", "\" data-hint-no-icon></div>\n\t\t\t</div>\n\t\t"])), range > 0 ? '--clickable' : '--without-stat', main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION', {
	        '#POSITION#': myPosition,
	        '#AMONG#': range
	      }), userList, main_core.Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING'));
	      if (range > 0) {
	        div.addEventListener('click', this.onclickHandle);
	      }
	      return div;
	    }
	  }], [{
	    key: "getPromise",
	    value: function getPromise(_ref) {
	      var _this = this;
	      var userId = _ref.userId,
	        isNarrow = _ref.isNarrow,
	        data = _ref.data;
	      return new Promise(function (resolve, reject) {
	        (data ? Promise.resolve({
	          data: data
	        }) : main_core.ajax.runComponentAction('bitrix:intranet.ustat.department', 'getJson', {
	          mode: 'class',
	          data: {}
	        })).then(function (_ref2) {
	          var data = _ref2.data;
	          var ustat = new _this(data);
	          resolve(isNarrow ? ustat.showData() : ustat.showWideData());
	        })["catch"](function (errors) {
	          errors = main_core.Type.isArray(errors) ? errors : [errors];
	          var node = document.createElement('ul');
	          errors.forEach(function (_ref3) {
	            var message = _ref3.message;
	            var errorNode = document.createElement('li');
	            errorNode.innerHTML = message;
	            errorNode.className = 'ui-alert-message';
	            node.appendChild(errorNode);
	          });
	          resolve(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"ui-alert ui-alert-danger\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>"])), node));
	        });
	      });
	    }
	  }]);
	  return Ustat;
	}();
	function _renderUsers2() {
	  var _this2 = this;
	  var userList = document.createDocumentFragment();
	  var myPosition = parseInt(this.data['USERS_RATING']['position']);
	  var myActivity = 0;
	  var usersData = main_core.Type.isPlainObject(this.data['USERS_RATING']['top']) ? Object.values(this.data['USERS_RATING']['top']) : main_core.Type.isArray(this.data['USERS_RATING']['top']) && this.data['USERS_RATING']['top'].length > 0 ? babelHelpers.toConsumableArray(this.data['USERS_RATING']['top']) : [{
	    'USER_ID': main_core.Loc.getMessage('USER_ID'),
	    ACTIVITY: 0
	  }];
	  var dataResult = myPosition > 5 ? [].concat(babelHelpers.toConsumableArray(usersData.slice(0, 3)), babelHelpers.toConsumableArray(usersData.slice(-1)), [null]) : usersData;
	  dataResult.forEach(function (userRating, index) {
	    if (userRating === null) {
	      userList.appendChild(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"system-auth-form__userlist-item --list\"></div>"]))));
	      return;
	    }
	    var fullName = userRating['ACTIVITY'];
	    var avatarSrc = '';
	    if (_this2.data['USERS_INFO'][userRating['USER_ID']]) {
	      fullName = [_this2.data['USERS_INFO'][userRating['USER_ID']]['FULL_NAME'], ': ', userRating['ACTIVITY']].join('');
	      avatarSrc = String(_this2.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC']).length > 0 ? _this2.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC'] : null;
	    }
	    var isCurrentUser = String(userRating['USER_ID']) === String(main_core.Loc.getMessage('USER_ID'));
	    if (isCurrentUser) {
	      myActivity = userRating['ACTIVITY'];
	    }
	    userList.appendChild(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div title=\"", "\" class=\"system-auth-form__userlist-item ui-icon ui-icon ui-icon-common-user\">\n\t\t\t\t\t\t\t<i ", "></i>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"])), main_core.Text.encode(fullName), avatarSrc ? "style=\"background-image: url('".concat(encodeURI(avatarSrc), "');background-size: cover;\"") : ''));
	  });
	  return {
	    userList: userList,
	    myPosition: myPosition,
	    range: parseInt(this.data['USERS_RATING']['range']),
	    myActivity: myActivity
	  };
	}

	var _templateObject$3, _templateObject2$2, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _config = /*#__PURE__*/new WeakMap();
	var _widget = /*#__PURE__*/new WeakMap();
	var _isActive = /*#__PURE__*/new WeakSet();
	var _isAvailable = /*#__PURE__*/new WeakSet();
	var _isConfigured = /*#__PURE__*/new WeakSet();
	var _getMainButton = /*#__PURE__*/new WeakSet();
	var _getBottomButton = /*#__PURE__*/new WeakSet();
	var _getLockIcon = /*#__PURE__*/new WeakSet();
	var _showLogoutPopup = /*#__PURE__*/new WeakSet();
	var UserLoginHistory = /*#__PURE__*/function () {
	  function UserLoginHistory(config, widget) {
	    babelHelpers.classCallCheck(this, UserLoginHistory);
	    _classPrivateMethodInitSpec$1(this, _showLogoutPopup);
	    _classPrivateMethodInitSpec$1(this, _getLockIcon);
	    _classPrivateMethodInitSpec$1(this, _getBottomButton);
	    _classPrivateMethodInitSpec$1(this, _getMainButton);
	    _classPrivateMethodInitSpec$1(this, _isConfigured);
	    _classPrivateMethodInitSpec$1(this, _isAvailable);
	    _classPrivateMethodInitSpec$1(this, _isActive);
	    _classPrivateFieldInitSpec$1(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _widget, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _config, config);
	    babelHelpers.classPrivateFieldSet(this, _widget, widget);
	  }
	  babelHelpers.createClass(UserLoginHistory, [{
	    key: "handlerLogoutButton",
	    value: function handlerLogoutButton() {
	      if (_classPrivateMethodGet$1(this, _isActive, _isActive2).call(this)) {
	        _classPrivateMethodGet$1(this, _showLogoutPopup, _showLogoutPopup2).call(this);
	      }
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;
	      var showSliderLoginHistory = function showSliderLoginHistory() {
	        if (_classPrivateMethodGet$1(_this, _isActive, _isActive2).call(_this)) {
	          babelHelpers.classPrivateFieldGet(_this, _widget).getPopup().close();
	          BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(_this, _config).url, {
	            allowChangeHistory: false
	          });
	        } else if (!_classPrivateMethodGet$1(_this, _isAvailable, _isAvailable2).call(_this)) {
	          babelHelpers.classPrivateFieldGet(_this, _widget).getPopup().close();
	          BX.UI.InfoHelper.show("limit_office_login_history");
	        }
	      };
	      var loginHistoryWidget = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --vertical\">\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center ", "\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image ", "\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --sm ", "\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__visited\">\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$1(this, _isActive, _isActive2).call(this) ? '--border' : '', _classPrivateMethodGet$1(this, _isActive, _isActive2).call(this) ? '--history' : '--history-gray', showSliderLoginHistory, _classPrivateMethodGet$1(this, _isActive, _isActive2).call(this) ? '--link' : '', showSliderLoginHistory, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_TITLE'), _classPrivateMethodGet$1(this, _getLockIcon, _getLockIcon2).call(this), _classPrivateMethodGet$1(this, _getMainButton, _getMainButton2).call(this), _classPrivateMethodGet$1(this, _getBottomButton, _getBottomButton2).call(this, showSliderLoginHistory));
	      var container = loginHistoryWidget.querySelector('.system-auth-form__visited');
	      if (_classPrivateMethodGet$1(this, _isActive, _isActive2).call(this)) {
	        var loader = _classStaticPrivateMethodGet(UserLoginHistory, UserLoginHistory, _getLoader).call(UserLoginHistory);
	        loader.show(container);
	        main_core.ajax.runComponentAction('bitrix:intranet.user.login.history', 'getListLastLogin', {
	          mode: 'class',
	          data: {
	            limit: 1
	          }
	        }).then(function (response) {
	          loader.hide();
	          var devices = response.data;
	          var keys = Object.keys(devices);
	          keys.forEach(function (key) {
	            var description = _classStaticPrivateMethodGet(UserLoginHistory, UserLoginHistory, _prepareDescriptionLoginHistory).call(UserLoginHistory, devices[key]['DEVICE_PLATFORM'], devices[key]['GEOLOCATION'], devices[key]['BROWSER']);
	            var time = _classStaticPrivateMethodGet(UserLoginHistory, UserLoginHistory, _prepareDateTimeForLoginHistory).call(UserLoginHistory, devices[key]['LOGIN_DATE']);
	            var device = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"system-auth-form__visited-item\">\n\t\t\t\t\t\t\t<div data-hint='", "' class=\"system-auth-form__visited-icon --", "\" onclick=\"", "\" data-hint-no-icon></div>\n\t\t\t\t\t\t\t<script>\n\t\t\t\t\t\t\t\tBX.ready(() => {\n\t\t\t\t\t\t\t\t\tBX.UI.Hint.init(document.querySelector(\".system-auth-form__visited-icon --", "\"));\n\t\t\t\t\t\t\t\t})\n\t\t\t\t\t\t\t</script>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__visited-text\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__visited-time\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_THIS_DEVICE_DESCRIPTION'), devices[key]['DEVICE_TYPE'], showSliderLoginHistory, devices[key]['DEVICE_TYPE'], showSliderLoginHistory, description, showSliderLoginHistory, time);
	            main_core.Dom.append(device, container);
	          });
	        })["catch"](function () {
	          loader.hide();
	        });
	      }
	      return loginHistoryWidget;
	    }
	  }]);
	  return UserLoginHistory;
	}();
	function _isActive2() {
	  return babelHelpers.classPrivateFieldGet(this, _config).isAvailableUserLoginHistory && babelHelpers.classPrivateFieldGet(this, _config).isConfiguredUserLoginHistory;
	}
	function _isAvailable2() {
	  return babelHelpers.classPrivateFieldGet(this, _config).isAvailableUserLoginHistory;
	}
	function _isConfigured2() {
	  return babelHelpers.classPrivateFieldGet(this, _config).isConfiguredUserLoginHistory;
	}
	function _getMainButton2() {
	  var _this2 = this;
	  var handlerLogoutButton = function handlerLogoutButton() {
	    _classPrivateMethodGet$1(_this2, _showLogoutPopup, _showLogoutPopup2).call(_this2);
	  };
	  var showConfigureSlider = function showConfigureSlider() {
	    babelHelpers.classPrivateFieldGet(_this2, _widget).getPopup().close();
	    BX.Helper.show('redirect=detail&code=16615982');
	  };
	  if (_classPrivateMethodGet$1(this, _isActive, _isActive2).call(this)) {
	    return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn\" onclick=\"", "\">", "</div>\n\t\t\t"], ["\n\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn\" onclick=\\\"", "\\\">", "</div>\n\t\t\t"])), handlerLogoutButton, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE'));
	  } else if (!_classPrivateMethodGet$1(this, _isConfigured, _isConfigured2).call(this)) {
	    return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class='system-auth-form__settings' onclick=\"", "\">", "</div>\n\t\t\t"])), showConfigureSlider, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'));
	  }
	  return null;
	}
	function _getBottomButton2(handler) {
	  if (_classPrivateMethodGet$1(this, _isActive, _isActive2).call(this)) {
	    return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item-container\">\n\t\t\t\t\t<div class=\"system-auth-form__show-history\" onclick=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), handler, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_SHOW_FULL_LIST'));
	  }
	  return null;
	}
	function _getLockIcon2() {
	  var _this3 = this;
	  var showInfoSlider = function showInfoSlider() {
	    babelHelpers.classPrivateFieldGet(_this3, _widget).getPopup().close();
	    BX.UI.InfoHelper.show("limit_office_login_history");
	  };
	  if (!_classPrivateMethodGet$1(this, _isAvailable, _isAvailable2).call(this)) {
	    return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item-title-logo --lock\" onclick=\"", "\">\n\t\t\t\t\t<i></i>\n\t\t\t\t</div>\n\t\t\t"])), showInfoSlider);
	  }
	  return null;
	}
	function _showLogoutPopup2() {
	  var _this4 = this;
	  BX.UI.Dialogs.MessageBox.show({
	    message: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_MESSAGE'),
	    title: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_TITLE'),
	    buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL,
	    minWidth: 400,
	    popupOptions: {
	      contentBackground: 'transparent',
	      autoHide: true,
	      closeByEsc: true,
	      padding: 0,
	      background: '',
	      events: {
	        onShow: function onShow() {
	          babelHelpers.classPrivateFieldGet(_this4, _widget).getPopup().getPopup().setAutoHide(false);
	        },
	        onPopupClose: function onPopupClose() {
	          babelHelpers.classPrivateFieldGet(_this4, _widget).getPopup().getPopup().setAutoHide(true);
	        }
	      }
	    },
	    onYes: function onYes(messageBox) {
	      main_core.ajax.runComponentAction('bitrix:intranet.user.profile.password', 'logout', {
	        mode: 'ajax'
	      }).then(function () {
	        messageBox.close();
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_RESULT'),
	          autoHideDelay: 1800
	        });
	      })["catch"](function () {
	        messageBox.close();
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_ERROR'),
	          autoHideDelay: 3600
	        });
	      });
	    }
	  });
	}
	function _prepareDescriptionLoginHistory(deviceType, geolocation, browser) {
	  var arrayDescription = [];
	  if (browser) {
	    arrayDescription.push(browser);
	  }
	  if (geolocation) {
	    arrayDescription.push(geolocation);
	  }
	  if (deviceType) {
	    arrayDescription.push(deviceType);
	  }
	  return arrayDescription.join(', ');
	}
	function _getLoader() {
	  return new main_loader.Loader({
	    size: 20,
	    mode: 'inline'
	  });
	}
	function _prepareDateTimeForLoginHistory(dateTime) {
	  var format = [['-', 'd.m.Y H:i:s'], ['s', 'sago'], ['i', 'iago'], ['H', 'Hago'], ['d', 'dago'], ['m', 'mago']];
	  return ' - ' + BX.date.format(format, new Date(dateTime), new Date());
	}

	var _templateObject$4, _templateObject2$3;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classStaticPrivateMethodGet$1(receiver, classConstructor, method) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); return method; }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess$1(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	var HcmLinkSalaryVacation = /*#__PURE__*/function () {
	  function HcmLinkSalaryVacation() {
	    babelHelpers.classCallCheck(this, HcmLinkSalaryVacation);
	  }
	  babelHelpers.createClass(HcmLinkSalaryVacation, null, [{
	    key: "load",
	    value: function () {
	      var _load = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        var _yield$Runtime$loadEx, SalaryVacationMenu;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              _context.prev = 0;
	              _context.next = 3;
	              return main_core.Runtime.loadExtension('humanresources.hcmlink.salary-vacation-menu');
	            case 3:
	              _yield$Runtime$loadEx = _context.sent;
	              SalaryVacationMenu = _yield$Runtime$loadEx.SalaryVacationMenu;
	              _classStaticPrivateFieldSpecSet(this, HcmLinkSalaryVacation, _salaryVacationMenu, new SalaryVacationMenu());
	              _context.next = 8;
	              return _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _salaryVacationMenu).load();
	            case 8:
	              _classStaticPrivateFieldSpecSet(this, HcmLinkSalaryVacation, _hidden, _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _salaryVacationMenu).isHidden());
	              _classStaticPrivateFieldSpecSet(this, HcmLinkSalaryVacation, _disabled, _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _salaryVacationMenu).isDisabled());
	              _context.next = 14;
	              break;
	            case 12:
	              _context.prev = 12;
	              _context.t0 = _context["catch"](0);
	            case 14:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this, [[0, 12]]);
	      }));
	      function load() {
	        return _load.apply(this, arguments);
	      }
	      return load;
	    }()
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this = this;
	      if (_classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _hidden)) {
	        return null;
	      }
	      var disabled = _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _disabled);
	      return _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _cache).remember('hcmLinkSalaryVacationLayout', function () {
	        var layout = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__scope system-auth-form__hcmlink ", "\"\n\t\t\t\t\t", "\n\t\t\t\t\tdata-hint-no-icon\n\t\t\t\t\tdata-hint-html\n\t\t\t\t\tdata-hint-interactivity\n\t\t\t\t>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --flex\" style=\"flex-direction:row;\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --hcmlink\">\n\t\t\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), disabled ? '--disabled' : '', disabled ? "data-hint=\"".concat(_classStaticPrivateMethodGet$1(_this, HcmLinkSalaryVacation, _getDisabledHintHtml).call(_this), "\"") : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION'), _classStaticPrivateMethodGet$1(HcmLinkSalaryVacation, HcmLinkSalaryVacation, _getMenuButton).call(HcmLinkSalaryVacation));
	        if (!disabled) {
	          main_core.Dom.addClass(layout, '--clickable');
	          main_core.Event.bind(layout, 'click', function () {
	            var _classStaticPrivateFi;
	            (_classStaticPrivateFi = _classStaticPrivateFieldSpecGet(_this, HcmLinkSalaryVacation, _salaryVacationMenu)) === null || _classStaticPrivateFi === void 0 ? void 0 : _classStaticPrivateFi.show(_classStaticPrivateMethodGet$1(_this, HcmLinkSalaryVacation, _getMenuButton).call(_this));
	          });
	        }
	        return layout;
	      });
	    }
	  }, {
	    key: "closeWidget",
	    value: function closeWidget() {
	      var _Widget$getInstance;
	      (_Widget$getInstance = Widget.getInstance()) === null || _Widget$getInstance === void 0 ? void 0 : _Widget$getInstance.hide();
	    }
	  }]);
	  return HcmLinkSalaryVacation;
	}();
	function _getMenuButton() {
	  return _classStaticPrivateFieldSpecGet(this, HcmLinkSalaryVacation, _cache).remember('hcmLinkSalaryVacationMenuButton', function () {
	    return main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__btn--hcmlink ui-icon-set --chevron-right\"></div>\n\t\t\t"])));
	  });
	}
	function _getDisabledHintHtml() {
	  return main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION_DISABLED', {
	    '[LINK]': "\n\t\t\t\t<a target='_self'\n\t\t\t\t\tonclick='(() => {\n\t\t\t\t\t\tBX.Intranet.UserProfile.Widget.getInstance()?.hide();\n\t\t\t\t\t\tBX.Helper.show(`redirect=detail&code=23343028`);\n\t\t\t\t\t})()'\n\t\t\t\t\tstyle='cursor:pointer;'\n\t\t\t\t>\n\t\t\t",
	    '[/LINK]': '</a>'
	  });
	}
	var _hidden = {
	  writable: true,
	  value: true
	};
	var _disabled = {
	  writable: true,
	  value: false
	};
	var _cache = {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	};
	var _salaryVacationMenu = {
	  writable: true,
	  value: void 0
	};

	var _templateObject$5, _templateObject2$4;
	function _regeneratorRuntime$1() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$1 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classStaticPrivateMethodGet$2(receiver, classConstructor, method) { _classCheckPrivateStaticAccess$2(receiver, classConstructor); return method; }
	function _classStaticPrivateFieldSpecGet$1(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess$2(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "get"); return _classApplyDescriptorGet$1(receiver, descriptor); }
	function _classApplyDescriptorGet$1(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classStaticPrivateFieldSpecSet$1(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess$2(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "set"); _classApplyDescriptorSet$1(receiver, descriptor, value); return value; }
	function _classCheckPrivateStaticFieldDescriptor$1(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess$2(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorSet$1(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	var analyticsContext = {
	  category: 'documents',
	  c_section: 'ava_menu',
	  type: 'from_employee'
	};
	var SignDocument = /*#__PURE__*/function () {
	  function SignDocument() {
	    babelHelpers.classCallCheck(this, SignDocument);
	  }
	  babelHelpers.createClass(SignDocument, null, [{
	    key: "getPromise",
	    value: function () {
	      var _getPromise = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee(isLocked) {
	        var _yield$Runtime$loadEx, B2EEmployeeSignSettings;
	        return _regeneratorRuntime$1().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              _context.next = 2;
	              return main_core.Runtime.loadExtension('sign.v2.b2e.sign-settings-employee');
	            case 2:
	              _yield$Runtime$loadEx = _context.sent;
	              B2EEmployeeSignSettings = _yield$Runtime$loadEx.B2EEmployeeSignSettings;
	              _classStaticPrivateFieldSpecSet$1(SignDocument, SignDocument, _b2eEmployeeSignSettings, new B2EEmployeeSignSettings(_classStaticPrivateFieldSpecGet$1(SignDocument, SignDocument, _container$1).id, analyticsContext));
	              _context.prev = 5;
	              _context.next = 8;
	              return HcmLinkSalaryVacation.load();
	            case 8:
	              _context.next = 12;
	              break;
	            case 10:
	              _context.prev = 10;
	              _context.t0 = _context["catch"](5);
	            case 12:
	              return _context.abrupt("return", _classStaticPrivateMethodGet$2(SignDocument, SignDocument, _getLayout).call(SignDocument, isLocked));
	            case 13:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, null, [[5, 10]]);
	      }));
	      function getPromise(_x) {
	        return _getPromise.apply(this, arguments);
	      }
	      return getPromise;
	    }()
	  }]);
	  return SignDocument;
	}();
	function _getLayout(isLocked) {
	  var lockedClass = isLocked ? ' --lock' : '';
	  return _classStaticPrivateFieldSpecGet$1(this, SignDocument, _cache$1).remember('layout', function () {
	    var layout = main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<div class=\"system-auth-form__scope system-auth-form__sign\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --flex\" style=\"flex-direction:row;\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --sign\">\n\t\t\t\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title\">\n\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t<span class=\"system-auth-form__item-title --link-light --margin-s\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__btn--sign ui-popupcomponentmaker__btn --medium --border", "\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE_HINT'), lockedClass, function () {
	      return _classStaticPrivateMethodGet$2(SignDocument, SignDocument, _onCreateDocumentBtnClick).call(SignDocument, isLocked);
	    }, main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_CREATE_DOCUMENT'), HcmLinkSalaryVacation.getLayout());
	    if (BX.UI.Hint) {
	      BX.UI.Hint.init(layout);
	    }
	    return layout;
	  });
	}
	function _onCreateDocumentBtnClick(isLocked) {
	  main_core_events.EventEmitter.emit(SignDocument, SignDocument.events.onDocumentCreateBtnClick);
	  if (isLocked) {
	    top.BX.UI.InfoHelper.show('limit_office_e_signature');
	    return;
	  }
	  var container = _classStaticPrivateFieldSpecGet$1(SignDocument, SignDocument, _container$1);
	  BX.SidePanel.Instance.open('sign-b2e-settings-init-by-employee', {
	    width: 750,
	    cacheable: false,
	    contentCallback: function contentCallback() {
	      container.innerHTML = '';
	      return container;
	    },
	    events: {
	      onLoad: function onLoad() {
	        _classStaticPrivateFieldSpecGet$1(SignDocument, SignDocument, _b2eEmployeeSignSettings).clearCache();
	        _classStaticPrivateFieldSpecGet$1(SignDocument, SignDocument, _b2eEmployeeSignSettings).render();
	      }
	    }
	  });
	}
	babelHelpers.defineProperty(SignDocument, "events", {
	  onDocumentCreateBtnClick: 'onDocumentCreateBtnClick'
	});
	var _b2eEmployeeSignSettings = {
	  writable: true,
	  value: void 0
	};
	var _container$1 = {
	  writable: true,
	  value: main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["<div id=\"sign-b2e-employee-settings-container\"></div>"])))
	};
	var _cache$1 = {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	};

	var _templateObject$6, _templateObject2$5, _templateObject3$2, _templateObject4$2, _templateObject5$2, _templateObject6$2;
	var Otp = /*#__PURE__*/function () {
	  function Otp() {
	    var isSingle = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	    var config = arguments.length > 1 ? arguments[1] : undefined;
	    babelHelpers.classCallCheck(this, Otp);
	    this.isSingle = isSingle;
	    this.config = config;
	  }
	  babelHelpers.createClass(Otp, [{
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;
	      var isInstalled = this.config.IS_ACTIVE === 'Y';
	      var _onclick = function onclick() {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	        if (String(_this.config.URL).length > 0) {
	          main_core.Uri.addParam(_this.config.URL, {
	            page: 'otpConnected'
	          });
	          BX.SidePanel.Instance.open(main_core.Uri.addParam(_this.config.URL, {
	            page: 'otpConnected'
	          }), {
	            width: 1100
	          });
	        } else {
	          console.error('Otp page is not defined. Check the component params');
	        }
	      };
	      var button = isInstalled ? main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-qr-popupcomponentmaker__btn\" style=\"margin-top: auto\" onclick=\"", "\">", "</div>"])), _onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_TURNED_ON')) : main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-qr-popupcomponentmaker__btn\" style=\"margin-top: auto\" onclick=\"", "\">", "</div>"])), _onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_TURN_ON'));
	      var onclickHelp = function onclickHelp() {
	        top.BX.Helper.show('redirect=detail&code=17728602');
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	      };
	      if (this.isSingle !== true) {
	        return main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-bottom-10 ", "\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --authentication\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --flex --column --flex-start\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --without-margin --block\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<span class=\"system-auth-form__icon-help --inline\" onclick=\"", "\"></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content --margin-top-auto --center --center-force\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), isInstalled ? ' --active' : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE'), onclickHelp, isInstalled ? main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --link-dotted\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t\t"])), _onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE')) : '', button, isInstalled ? '' : "\n\t\t\t\t\t\t<div class=\"system-auth-form__item-new\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-new--title\">".concat(main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE'), "</div>\n\t\t\t\t\t\t</div>"));
	      }
	      var menuPopup = null;
	      var popupClick = function popupClick(event) {
	        event.stopPropagation();
	        var items = [{
	          text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'),
	          onclick: function onclick() {
	            menuPopup.close();
	            _onclick();
	          }
	        }];
	        menuPopup = menuPopup || new main_popup.Menu("menu-otp-".concat(main_core.Text.getRandom()), event.target, items, {
	          className: 'system-auth-form__popup',
	          angle: true,
	          offsetLeft: 10,
	          autoHide: true,
	          events: {
	            onShow: function onShow(popup) {
	              main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':showOtpMenu', new main_core_events.BaseEvent({
	                data: {
	                  popup: popup.target
	                }
	              }));
	            }
	          }
	        });
	        menuPopup.toggle();
	      };
	      return main_core.Tag.render(_templateObject5$2 || (_templateObject5$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-sm-all ", " --vertical --center\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --authentication\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --flex\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light --center --s\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t <span class=\"system-auth-form__icon-help\" onclick=\"", "\"></span> \n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"system-auth-form__item-container --flex --column --space-around\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content --flex --display-flex\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t"])), isInstalled ? ' --active' : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE'), onclickHelp, isInstalled ? main_core.Tag.render(_templateObject6$2 || (_templateObject6$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"system-auth-form__config --absolute\" onclick=\"", "\"></div>"])), popupClick) : '', button, isInstalled ? '' : "\n\t\t\t\t\t\t<div class=\"system-auth-form__item-new system-auth-form__item-new-icon --ssl\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-new--title\">".concat(main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE'), "</div>\n\t\t\t\t\t\t</div>"));
	    }
	  }]);
	  return Otp;
	}();

	var _templateObject$7, _templateObject2$6, _templateObject3$3, _templateObject4$3, _templateObject5$3, _templateObject6$3, _templateObject7$1, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var widgetMarker = Symbol('user.widget');
	var _container$2 = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _profile = /*#__PURE__*/new WeakMap();
	var _features = /*#__PURE__*/new WeakMap();
	var _cache$2 = /*#__PURE__*/new WeakMap();
	var _desktopDownloadLinks = /*#__PURE__*/new WeakMap();
	var _networkProfileUrl = /*#__PURE__*/new WeakMap();
	var _getProfileContainer = /*#__PURE__*/new WeakSet();
	var _getPopupContainer = /*#__PURE__*/new WeakSet();
	var _setEventHandlers = /*#__PURE__*/new WeakSet();
	var _getb24NetPanelContainer = /*#__PURE__*/new WeakSet();
	var _getAdminPanelContainer = /*#__PURE__*/new WeakSet();
	var _getThemeContainer = /*#__PURE__*/new WeakSet();
	var _getMaskContainer = /*#__PURE__*/new WeakSet();
	var _getCompanyPulse = /*#__PURE__*/new WeakSet();
	var _savePhoto = /*#__PURE__*/new WeakSet();
	var _getSignDocument = /*#__PURE__*/new WeakSet();
	var _getStressLevel = /*#__PURE__*/new WeakSet();
	var _getQrContainer = /*#__PURE__*/new WeakSet();
	var _getDeskTopContainer = /*#__PURE__*/new WeakSet();
	var _getOTPContainer = /*#__PURE__*/new WeakSet();
	var _getLoginHistoryContainer = /*#__PURE__*/new WeakSet();
	var _getBindings = /*#__PURE__*/new WeakSet();
	var _getNotificationContainer = /*#__PURE__*/new WeakSet();
	var _getLogoutContainer = /*#__PURE__*/new WeakSet();
	var Widget = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Widget, _EventEmitter);
	  function Widget(container, _ref) {
	    var _this;
	    var _ref$profile = _ref.profile,
	      ID = _ref$profile.ID,
	      FULL_NAME = _ref$profile.FULL_NAME,
	      PHOTO = _ref$profile.PHOTO,
	      MASK = _ref$profile.MASK,
	      STATUS = _ref$profile.STATUS,
	      STATUS_CODE = _ref$profile.STATUS_CODE,
	      URL = _ref$profile.URL,
	      WORK_POSITION = _ref$profile.WORK_POSITION,
	      _ref$component = _ref.component,
	      componentName = _ref$component.componentName,
	      signedParameters = _ref$component.signedParameters,
	      features = _ref.features,
	      desktopDownloadLinks = _ref.desktopDownloadLinks,
	      networkProfileUrl = _ref.networkProfileUrl;
	    babelHelpers.classCallCheck(this, Widget);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Widget).call(this));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getLogoutContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNotificationContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getBindings);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getLoginHistoryContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getOTPContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getDeskTopContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getQrContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getStressLevel);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getSignDocument);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _savePhoto);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getCompanyPulse);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getMaskContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getThemeContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getAdminPanelContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getb24NetPanelContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _setEventHandlers);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getPopupContainer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getProfileContainer);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _container$2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _popup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _profile, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _features, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _desktopDownloadLinks, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _networkProfileUrl, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace(Options.eventNameSpace);
	    _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _setEventHandlers, _setEventHandlers2).call(babelHelpers.assertThisInitialized(_this));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _container$2, container);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _profile, {
	      ID: ID,
	      FULL_NAME: FULL_NAME,
	      PHOTO: PHOTO,
	      MASK: MASK,
	      STATUS: STATUS,
	      STATUS_CODE: STATUS_CODE,
	      URL: URL,
	      WORK_POSITION: WORK_POSITION
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _features, features);
	    if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _features).browser)) {
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _features).browser = main_core.Browser.isLinux() ? 'Linux' : main_core.Browser.isWin() ? 'Windows' : 'MacOs';
	    }
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _desktopDownloadLinks, desktopDownloadLinks);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _networkProfileUrl, networkProfileUrl);
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _cache$2).set('componentParams', {
	      componentName: componentName,
	      signedParameters: signedParameters
	    });
	    _this.hide = _this.hide.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Widget, [{
	    key: "toggle",
	    value: function toggle() {
	      if (this.getPopup().isShown()) {
	        this.hide();
	      } else {
	        this.show();
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.getPopup().close();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _classPrivateMethodGe, _classPrivateMethodGe2;
	      if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	        return babelHelpers.classPrivateFieldGet(this, _popup);
	      }
	      this.emit('init');
	      var signDocument = main_core.Type.isNull(_classPrivateMethodGet$2(this, _getSignDocument, _getSignDocument2).call(this)) ? null : {
	        html: _classPrivateMethodGet$2(this, _getSignDocument, _getSignDocument2).call(this)
	      };
	      var content = [_classPrivateMethodGet$2(this, _getProfileContainer, _getProfileContainer2).call(this), _classPrivateMethodGet$2(this, _getAdminPanelContainer, _getAdminPanelContainer2).call(this) ? {
	        html: _classPrivateMethodGet$2(this, _getAdminPanelContainer, _getAdminPanelContainer2).call(this),
	        backgroundColor: '#fafafa'
	      } : null, signDocument, [{
	        html: _classPrivateMethodGet$2(this, _getThemeContainer, _getThemeContainer2).call(this),
	        marginBottom: 24,
	        overflow: true,
	        minHeight: '63px'
	      }, {
	        html: _classPrivateMethodGet$2(this, _getMaskContainer, _getMaskContainer2).call(this),
	        backgroundColor: '#fafafa'
	      }], _classPrivateMethodGet$2(this, _getCompanyPulse, _getCompanyPulse2).call(this, !!_classPrivateMethodGet$2(this, _getStressLevel, _getStressLevel2).call(this)) ? [{
	        html: _classPrivateMethodGet$2(this, _getCompanyPulse, _getCompanyPulse2).call(this, !!_classPrivateMethodGet$2(this, _getStressLevel, _getStressLevel2).call(this)),
	        overflow: true,
	        marginBottom: 24,
	        flex: _classPrivateMethodGet$2(this, _getStressLevel, _getStressLevel2).call(this) ? 0.5 : 1,
	        minHeight: _classPrivateMethodGet$2(this, _getStressLevel, _getStressLevel2).call(this) ? '115px' : '56px'
	      }, _classPrivateMethodGet$2(this, _getStressLevel, _getStressLevel2).call(this)] : null, _classPrivateMethodGet$2(this, _getOTPContainer, _getOTPContainer2).call(this, _classPrivateMethodGet$2(this, _getDeskTopContainer, _getDeskTopContainer2).call(this) === null) && _classPrivateMethodGet$2(this, _getDeskTopContainer, _getDeskTopContainer2).call(this) ? [{
	        flex: 0.5,
	        html: _classPrivateMethodGet$2(this, _getQrContainer, _getQrContainer2).call(this, 0.7),
	        minHeight: '190px'
	      }, [{
	        html: _classPrivateMethodGet$2(this, _getDeskTopContainer, _getDeskTopContainer2).call(this),
	        displayBlock: true
	      }, _classPrivateMethodGet$2(this, _getOTPContainer, _getOTPContainer2).call(this, false)]] : _classPrivateMethodGet$2(this, _getDeskTopContainer, _getDeskTopContainer2).call(this) || _classPrivateMethodGet$2(this, _getOTPContainer, _getOTPContainer2).call(this, true) ? [{
	        html: _classPrivateMethodGet$2(this, _getQrContainer, _getQrContainer2).call(this, 2),
	        flex: 2
	      }, (_classPrivateMethodGe = _classPrivateMethodGet$2(this, _getDeskTopContainer, _getDeskTopContainer2).call(this)) !== null && _classPrivateMethodGe !== void 0 ? _classPrivateMethodGe : _classPrivateMethodGet$2(this, _getOTPContainer, _getOTPContainer2).call(this, true)] : _classPrivateMethodGet$2(this, _getQrContainer, _getQrContainer2).call(this, 0), _classPrivateMethodGet$2(this, _getLoginHistoryContainer, _getLoginHistoryContainer2).call(this), {
	        html: _classPrivateMethodGet$2(this, _getBindings, _getBindings2).call(this),
	        backgroundColor: '#fafafa'
	      }, _classPrivateMethodGet$2(this, _getb24NetPanelContainer, _getb24NetPanelContainer2).call(this) ? {
	        html: _classPrivateMethodGet$2(this, _getb24NetPanelContainer, _getb24NetPanelContainer2).call(this),
	        marginBottom: 24,
	        backgroundColor: '#fafafa'
	      } : null, [{
	        html: (_classPrivateMethodGe2 = _classPrivateMethodGet$2(this, _getNotificationContainer, _getNotificationContainer2).call(this)) !== null && _classPrivateMethodGe2 !== void 0 ? _classPrivateMethodGe2 : null,
	        backgroundColor: '#fafafa'
	      }, {
	        html: _classPrivateMethodGet$2(this, _getLogoutContainer, _getLogoutContainer2).call(this),
	        backgroundColor: '#fafafa'
	      }]];
	      var filterFunc = function filterFunc(data) {
	        var result = [];
	        if (main_core.Type.isArray(data)) {
	          for (var i = 0; i < data.length; i++) {
	            if (main_core.Type.isArray(data[i])) {
	              var buff = filterFunc(data[i]);
	              if (buff !== null) {
	                if (main_core.Type.isArray(buff) && buff.length === 1) {
	                  result.push(buff[0]);
	                } else {
	                  result.push(buff);
	                }
	              }
	            } else if (data[i] !== null) {
	              result.push(data[i]);
	            }
	          }
	        }
	        return result.length <= 0 ? null : result.length === 1 ? result[0] : result;
	      };
	      content = filterFunc(content);
	      var prepareFunc = function prepareFunc(item, index, array) {
	        if (main_core.Type.isArray(item)) {
	          return {
	            html: item.map(prepareFunc)
	          };
	        }
	        return {
	          flex: item['flex'] || 0,
	          html: item['html'] || item,
	          backgroundColor: item['backgroundColor'] || null,
	          disabled: item['disabled'] || null,
	          overflow: item['overflow'] || null,
	          marginBottom: item['marginBottom'] || null,
	          displayBlock: item['displayBlock'] || null,
	          minHeight: item['minHeight'] || null,
	          secondary: item['secondary'] || false
	        };
	      };
	      babelHelpers.classPrivateFieldSet(this, _popup, new ui_popupcomponentsmaker.PopupComponentsMaker({
	        target: babelHelpers.classPrivateFieldGet(this, _container$2),
	        content: content.map(prepareFunc),
	        width: 400,
	        offsetTop: -14
	      }));
	      main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onMenuShow', this.hide);
	      main_core_events.EventEmitter.subscribe(Options.eventNameSpace + 'onNeedToHide', this.hide);
	      return babelHelpers.classPrivateFieldGet(this, _popup);
	    }
	  }], [{
	    key: "init",
	    value: function init(node, options) {
	      var _this2 = this;
	      if (node[widgetMarker]) {
	        return;
	      }
	      var onclick = function onclick() {
	        if (!node['popupSymbol']) {
	          node['popupSymbol'] = new _this2(node, options);
	        }
	        node['popupSymbol'].toggle();
	        _this2.instance = node['popupSymbol'];
	      };
	      node[widgetMarker] = true;
	      node.addEventListener('click', onclick);
	    }
	  }, {
	    key: "getInstance",
	    value: function getInstance() {
	      return this.instance;
	    }
	  }]);
	  return Widget;
	}(main_core_events.EventEmitter);
	function _getProfileContainer2() {
	  var _this3 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('profile', function () {
	    var onclick = function onclick(event) {
	      _this3.hide();
	      return BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(_this3, _profile).URL);
	    };
	    var avatar;
	    var avatarNode;
	    if (babelHelpers.classPrivateFieldGet(_this3, _profile).STATUS_CODE === 'collaber') {
	      avatar = new ui_avatar.AvatarRoundGuest({
	        size: 36,
	        userpicPath: encodeURI(babelHelpers.classPrivateFieldGet(_this3, _profile).PHOTO),
	        baseColor: '#19cc45'
	      });
	      avatarNode = avatar.getContainer();
	    } else {
	      avatarNode = main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"system-auth-form__profile-avatar--image\"\n\t\t\t\t\t\t", ">\n\t\t\t\t\t</span>\n\t\t\t\t"])), babelHelpers.classPrivateFieldGet(_this3, _profile).PHOTO ? "\n\t\t\t\t\t\t\tstyle=\"background-size: cover; background-image: url('".concat(encodeURI(babelHelpers.classPrivateFieldGet(_this3, _profile).PHOTO), "')\"") : '');
	    }
	    var nameNode = main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__profile-name\">", "</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(_this3, _profile).FULL_NAME);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UserProfile:Avatar:changed', function (_ref2) {
	      var _ref2$data = babelHelpers.slicedToArray(_ref2.data, 1),
	        _ref2$data$ = _ref2$data[0],
	        url = _ref2$data$.url,
	        userId = _ref2$data$.userId;
	      if (babelHelpers.classPrivateFieldGet(_this3, _profile).ID > 0 && userId && babelHelpers.classPrivateFieldGet(_this3, _profile).ID.toString() === userId.toString()) {
	        babelHelpers.classPrivateFieldGet(_this3, _profile).PHOTO = url;
	        avatar.setUserPic(url);
	      }
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UserProfile:Name:changed', function (_ref3) {
	      var _ref3$data = babelHelpers.slicedToArray(_ref3.data, 1),
	        fullName = _ref3$data[0].fullName;
	      babelHelpers.classPrivateFieldGet(_this3, _profile).FULL_NAME = fullName;
	      nameNode.innerHTML = fullName;
	      babelHelpers.classPrivateFieldGet(_this3, _container$2).querySelector('#user-name').innerHTML = fullName;
	    });
	    var workPosition = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(_this3, _profile).WORK_POSITION) ? main_core.Text.encode(babelHelpers.classPrivateFieldGet(_this3, _profile).WORK_POSITION) : '';
	    if (babelHelpers.classPrivateFieldGet(_this3, _profile).STATUS && (babelHelpers.classPrivateFieldGet(_this3, _profile).STATUS !== 'collaber' || workPosition === '') && main_core.Loc.hasMessage('INTRANET_USER_PROFILE_' + babelHelpers.classPrivateFieldGet(_this3, _profile).STATUS)) {
	      workPosition = main_core.Loc.getMessage('INTRANET_USER_PROFILE_' + babelHelpers.classPrivateFieldGet(_this3, _profile).STATUS);
	    }
	    return main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --clickable\" onclick=\"", "\">\n\t\t\t\t\t<div class=\"system-auth-form__profile\">\n\t\t\t\t\t\t<div class=\"system-auth-form__profile-avatar\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__profile-content --margin--right\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<div class=\"system-auth-form__profile-position\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__profile-controls\">\n\t\t\t\t\t\t\t<span class=\"ui-qr-popupcomponentmaker__btn --large --border\" >\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t \t\t<!-- <span class=\"ui-qr-popupcomponentmaker__btn --large --success\">any text</span> -->\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), onclick, avatarNode, nameNode, workPosition, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PROFILE'));
	  });
	}
	function _getPopupContainer2() {
	  var _this4 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('popup-container', function () {
	    return _this4.getPopup().getPopup().getPopupContainer();
	  });
	}
	function _setEventHandlers2() {
	  var _this5 = this;
	  var autoHideHandler = function autoHideHandler(event) {
	    console.log(event);
	    if (event.data.popup) {
	      setTimeout(function () {
	        main_core.Event.bind(_classPrivateMethodGet$2(_this5, _getPopupContainer, _getPopupContainer2).call(_this5), 'click', function () {
	          event.data.popup.close();
	        });
	      }, 100);
	    }
	  };
	  this.subscribe('init', function () {
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen', _this5.hide);
	    _this5.subscribe('bindings:open', autoHideHandler);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':showOtpMenu', autoHideHandler);
	  });
	}
	function _getb24NetPanelContainer2() {
	  var _this6 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('b24netPanel', function () {
	    if (babelHelpers.classPrivateFieldGet(_this6, _features)['b24netPanel'] !== 'Y') {
	      return null;
	    }
	    return main_core.Tag.render(_templateObject4$3 || (_templateObject4$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"system-auth-form__item system-auth-form__scope --center --padding-sm --clickable\" href=\"", "\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --network\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --block\">\n\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</a>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(_this6, _networkProfileUrl), main_core.Loc.getMessage('AUTH_PROFILE_B24NET_MSGVER_1'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_GOTO'));
	  });
	}
	function _getAdminPanelContainer2() {
	  var _this7 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('adminPanel', function () {
	    if (babelHelpers.classPrivateFieldGet(_this7, _features)['adminPanel'] !== 'Y') {
	      return null;
	    }
	    return main_core.Tag.render(_templateObject5$3 || (_templateObject5$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"system-auth-form__item system-auth-form__scope --center --padding-sm --clickable\" href=\"/bitrix/admin/\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --admin-panel\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --block\">\n\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</a>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_ADMIN_PANEL'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_GOTO'));
	  });
	}
	function _getThemeContainer2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('themePicker', function () {
	    return ThemePicker.getPromise();
	  });
	}
	function _getMaskContainer2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('Mask', function () {
	    return main_core.Tag.render(_templateObject6$3 || (_templateObject6$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-sm\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --mask\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t<span style=\"cursor: default\" class=\"system-auth-form__icon-help\"></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content --center --center-force\">\n\t\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn --disabled\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-new --soon\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-new--title\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MASKS'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SOON'));
	  });
	}
	function _getCompanyPulse2(isNarrow) {
	  var _this8 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getCompanyPulse', function () {
	    if (babelHelpers.classPrivateFieldGet(_this8, _features).pulse === 'Y' && babelHelpers.classPrivateFieldGet(_this8, _profile).ID > 0 && babelHelpers.classPrivateFieldGet(_this8, _profile).ID === main_core.Loc.getMessage('USER_ID')) {
	      return new Promise(function (resolve) {
	        main_core.ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getUserStatComponent', {
	          mode: 'class'
	        }).then(function (response) {
	          BX.Runtime.html(null, response.data.html).then(function () {
	            var _babelHelpers$classPr;
	            resolve(Ustat.getPromise({
	              userId: babelHelpers.classPrivateFieldGet(_this8, _profile).ID,
	              isNarrow: isNarrow,
	              data: (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this8, _features)['pulseData']) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : null
	            }));
	          });
	        });
	      });
	    }
	    return null;
	  });
	}
	function _getSignDocument2() {
	  var _this9 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _features)['signDocument']['available'] !== 'Y') {
	    return null;
	  }
	  var isLocked = babelHelpers.classPrivateFieldGet(this, _features)['signDocument']['locked'] === 'Y';
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getSignDocument', function () {
	    main_core_events.EventEmitter.subscribe(SignDocument, SignDocument.events.onDocumentCreateBtnClick, function () {
	      return _this9.hide();
	    });
	    return SignDocument.getPromise(isLocked);
	  });
	}
	function _getStressLevel2() {
	  var _this10 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _features)['stressLevel'] !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getStressLevel', function () {
	    var _babelHelpers$classPr2;
	    return StressLevel.getPromise({
	      signedParameters: babelHelpers.classPrivateFieldGet(_this10, _cache$2).get('componentParams').signedParameters,
	      componentName: babelHelpers.classPrivateFieldGet(_this10, _cache$2).get('componentParams').componentName,
	      userId: babelHelpers.classPrivateFieldGet(_this10, _profile).ID,
	      data: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(_this10, _features)['stressLevelData']) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : null
	    });
	  });
	}
	function _getQrContainer2(flex) {
	  var _this11 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getQrContainer', function () {
	    return new Promise(function (resolve, reject) {
	      BX.loadExt(['ui.qrauthorization', 'qrcode']).then(function () {
	        var onclick = function onclick() {
	          _this11.hide();
	          new ui_qrauthorization.QrAuthorization({
	            title: main_core.Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_TITLE2'),
	            content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_BODY2'),
	            intent: 'profile'
	          }).show();
	        };
	        var onclickHelp = function onclickHelp(event) {
	          top.BX.Helper.show('redirect=detail&code=14999860');
	          _this11.hide();
	          event.preventDefault();
	          event.stopPropagation();
	          return false;
	        };
	        var node;
	        if (flex !== 2 && flex !== 0) {
	          // for a small size
	          node = main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope\" style=\"padding: 10px 14px\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --center --column --center\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --center --margin-xl\">", "</div>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__qr\" style=\"margin-bottom: 12px\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn --border\" style=\"margin-top: auto\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__icon-help --absolute\" onclick=\"", "\" title=\"", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR_SMALL'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'));
	        } else if (flex === 0) {
	          //full size
	          node = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-qr-xl\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --column --flex --flex-start\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --l\">", "</div>\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --link-dotted\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn --large --border\" style=\"margin-top: auto\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --qr\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__qr --full-size\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR'));
	        } else {
	          // for flex 2. It is kind of middle
	          node = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-mid-qr\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --column --flex --flex-start\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --block\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t<span class=\"system-auth-form__icon-help --inline\" onclick=\"", "\" title=\"", "\"></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-qr-popupcomponentmaker__btn --border\" style=\"margin-top: auto\" onclick=\"", "\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --qr\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__qr --size-2\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR'));
	        }
	        return resolve(node);
	      })["catch"](reject);
	    });
	  });
	}
	function _getDeskTopContainer2() {
	  var _this12 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getDeskTopContainer', function () {
	    var isInstalled = babelHelpers.classPrivateFieldGet(_this12, _features)['appInstalled']['APP_MAC_INSTALLED'] === 'Y';
	    var cssPostfix = '--apple';
	    var title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_APPLE');
	    var linkToDistributive = babelHelpers.classPrivateFieldGet(_this12, _desktopDownloadLinks).macos;
	    var typesInstallersForLinux = {
	      'DEB': {
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_DEB'),
	        href: babelHelpers.classPrivateFieldGet(_this12, _desktopDownloadLinks).linuxDeb
	      },
	      'RPM': {
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_RPM'),
	        href: babelHelpers.classPrivateFieldGet(_this12, _desktopDownloadLinks).linuxRpm
	      }
	    };
	    if (babelHelpers.classPrivateFieldGet(_this12, _features).browser === 'Windows') {
	      isInstalled = babelHelpers.classPrivateFieldGet(_this12, _features)['appInstalled']['APP_WINDOWS_INSTALLED'] === 'Y';
	      cssPostfix = '--windows';
	      title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_WINDOWS');
	      linkToDistributive = babelHelpers.classPrivateFieldGet(_this12, _desktopDownloadLinks).windows;
	    }
	    var onclick = isInstalled ? function (event) {
	      event.preventDefault();
	      event.stopPropagation();
	      return false;
	    } : function () {
	      _this12.hide();
	      return true;
	    };
	    var menuLinux = null;
	    var showMenuLinux = function showMenuLinux(event) {
	      event.preventDefault();
	      menuLinux = menuLinux || new main_popup.Menu({
	        className: 'system-auth-form__popup',
	        bindElement: event.target,
	        items: [{
	          text: typesInstallersForLinux.DEB.text,
	          href: typesInstallersForLinux.DEB.href,
	          onclick: function onclick() {
	            menuLinux.close();
	          }
	        }, {
	          text: typesInstallersForLinux.RPM.text,
	          href: typesInstallersForLinux.RPM.href,
	          onclick: function onclick() {
	            menuLinux.close();
	          }
	        }],
	        angle: true,
	        offsetLeft: 10,
	        events: {
	          onShow: function onShow() {
	            _this12.getPopup().getPopup().setAutoHide(false);
	          },
	          onClose: function onClose() {
	            _this12.getPopup().getPopup().setAutoHide(true);
	          }
	        }
	      });
	      menuLinux.toggle();
	    };
	    if (babelHelpers.classPrivateFieldGet(_this12, _features).browser === 'Linux') {
	      isInstalled = babelHelpers.classPrivateFieldGet(_this12, _features)['appInstalled']['APP_LINUX_INSTALLED'] === 'Y';
	      cssPostfix = '--linux';
	      title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_LINUX');
	      linkToDistributive = '';
	      onclick = isInstalled ? function (event) {
	        event.preventDefault();
	        event.stopPropagation();
	        return false;
	      } : showMenuLinux;
	    }
	    if (babelHelpers.classPrivateFieldGet(_this12, _features)['otp'].IS_ENABLED !== 'Y') {
	      var menuPopup = null;
	      var menuItems = [{
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD'),
	        href: linkToDistributive,
	        onclick: function onclick() {
	          menuPopup.close();
	          _this12.hide();
	        }
	      }];
	      if (babelHelpers.classPrivateFieldGet(_this12, _features).browser === 'Linux') {
	        menuItems = [{
	          text: typesInstallersForLinux.DEB.text,
	          href: typesInstallersForLinux.DEB.href,
	          onclick: function onclick() {
	            menuPopup.close();
	          }
	        }, {
	          text: typesInstallersForLinux.RPM.text,
	          href: typesInstallersForLinux.RPM.href,
	          onclick: function onclick() {
	            menuPopup.close();
	          }
	        }];
	      }
	      var popupClick = function popupClick(event) {
	        menuPopup = menuPopup || new main_popup.Menu({
	          className: 'system-auth-form__popup',
	          bindElement: event.target,
	          items: menuItems,
	          angle: true,
	          offsetLeft: 10,
	          events: {
	            onShow: function onShow() {
	              _this12.getPopup().getPopup().setAutoHide(false);
	            },
	            onClose: function onClose() {
	              _this12.getPopup().getPopup().setAutoHide(true);
	            }
	          }
	        });
	        menuPopup.toggle();
	      };
	      return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div data-role=\"desktop-item\" class=\"system-auth-form__item system-auth-form__scope --padding-sm-all ", " --vertical --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image ", "\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"system-auth-form__item-container --flex --center --display-flex\">\n\t\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light --center --s\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content --flex --center --display-flex\">\n\t\t\t\t\t\t\t<a class=\"ui-qr-popupcomponentmaker__btn\" style=\"margin-top: auto\" href=\"", "\" target=\"_blank\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), isInstalled ? ' --active' : '', cssPostfix, isInstalled ? main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<div class=\"system-auth-form__config --absolute\" onclick=\"", "\"></div>"])), popupClick) : '', title, linkToDistributive, onclick, isInstalled ? main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'));
	    }
	    var getLinkForHiddenState = function getLinkForHiddenState() {
	      var link = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<a href=\"", "\" class=\"system-auth-form__item-title --link-dotted\">", "</a>\n\t\t\t\t"])), linkToDistributive, main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD'));
	      if (babelHelpers.classPrivateFieldGet(_this12, _features).browser === 'Linux') {
	        link.addEventListener('click', showMenuLinux);
	      }
	      return link;
	    };
	    return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-bottom-10 ", "\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image ", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title ", "\">", "</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"system-auth-form__item-content --center --center-force\">\n\t\t\t\t\t\t\t<a class=\"ui-qr-popupcomponentmaker__btn\" href=\"", "\" target=\"_blank\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), isInstalled ? ' --active' : '', cssPostfix, isInstalled ? ' --without-margin' : '--min-height', title, isInstalled ? getLinkForHiddenState() : '', linkToDistributive, onclick, isInstalled ? main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'));
	  });
	}
	function _getOTPContainer2(single) {
	  var _this13 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _features).otp.IS_ENABLED !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getOTPContainer', function () {
	    return new Otp(single, babelHelpers.classPrivateFieldGet(_this13, _features).otp).getContainer();
	  });
	}
	function _getLoginHistoryContainer2() {
	  var _this14 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _features).loginHistory.isHide) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getLoginHistoryContainer', function () {
	    var history = new UserLoginHistory(babelHelpers.classPrivateFieldGet(_this14, _features).loginHistory, _this14);
	    return {
	      html: history.getContainer(),
	      backgroundColor: '#fafafa'
	    };
	  });
	}
	function _getBindings2() {
	  var _this15 = this;
	  if (!(main_core.Type.isPlainObject(babelHelpers.classPrivateFieldGet(this, _features)['bindings']) && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _features)['bindings']['text']) && main_core.Type.isArray(babelHelpers.classPrivateFieldGet(this, _features)['bindings']['items']) && babelHelpers.classPrivateFieldGet(this, _features)['bindings']['items'].length > 0)) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getBindingsContainer', function () {
	    var div = main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item --hover system-auth-form__scope --center --padding-sm\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --binding\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div data-role=\"arrow\" class=\"system-auth-form__item-icon --arrow-right\"></div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Text.encode(babelHelpers.classPrivateFieldGet(_this15, _features)['bindings']['text']));
	    div.addEventListener('click', function () {
	      _this15.__bindingsMenu = _this15.__bindingsMenu || new main_popup.Menu({
	        className: 'system-auth-form__popup',
	        bindElement: div.querySelector('[data-role="arrow"]'),
	        items: babelHelpers.classPrivateFieldGet(_this15, _features)['bindings']['items'],
	        angle: true,
	        cachable: false,
	        offsetLeft: 10,
	        events: {
	          onShow: function onShow() {
	            _this15.emit('bindings:open');
	          }
	        }
	      });
	      _this15.__bindingsMenu.toggle();
	    });
	    return div;
	  });
	}
	function _getNotificationContainer2() {
	  var _this16 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _features)['im'] !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getNotificationContainer', function () {
	    var div = main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item --hover system-auth-form__scope --padding-sm\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --notification\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('AUTH_NOTIFICATION'));
	    div.addEventListener('click', function () {
	      _this16.hide();
	      BXIM.openSettings({
	        'onlyPanel': 'notify'
	      });
	    });
	    return div;
	  });
	}
	function _getLogoutContainer2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getLogoutContainer', function () {
	    var onclickLogout = function onclickLogout() {
	      if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        im_v2_lib_desktopApi.DesktopApi.logout();
	      } else {
	        var backUrl = new main_core.Uri(window.location.pathname);
	        backUrl.removeQueryParam(['logout', 'login', 'back_url_pub', 'user_lang']);
	        var newUrl = new main_core.Uri('/auth/?logout=yes');
	        newUrl.setQueryParam('sessid', BX.bitrix_sessid());
	        newUrl.setQueryParam('backurl', encodeURIComponent(backUrl.toString()));
	        document.location.href = newUrl;
	      }
	    };

	    //TODO
	    return main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"system-auth-form__item system-auth-form__scope --padding-sm\">\n\t\t\t\t\t<div class=\"system-auth-form__item-logo\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-logo--image --logout\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"system-auth-form__item-container --center\">\n\t\t\t\t\t\t<div class=\"system-auth-form__item-title --light\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<a onclick=\"", "\" class=\"system-auth-form__item-link-all\"></a>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('AUTH_LOGOUT'), onclickLogout);
	  });
	}
	babelHelpers.defineProperty(Widget, "instance", null);

	exports.Widget = Widget;

}((this.BX.Intranet.UserProfile = this.BX.Intranet.UserProfile || {}),BX.UI,BX.UI,BX,BX,BX,BX.Cache,BX.UI,BX,BX.Main,BX.Event,BX.Messenger.v2.Lib));
//# sourceMappingURL=script.js.map

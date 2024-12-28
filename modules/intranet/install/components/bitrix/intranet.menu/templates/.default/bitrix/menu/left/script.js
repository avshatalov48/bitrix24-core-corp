/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,main_core_events,im_v2_lib_desktopApi,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var Account = /*#__PURE__*/function () {
	  function Account(allCounters) {
	    babelHelpers.classCallCheck(this, Account);
	    babelHelpers.defineProperty(this, "accounts", []);
	    babelHelpers.defineProperty(this, "currentUser", null);
	    babelHelpers.defineProperty(this, "contextPopup", []);
	    babelHelpers.defineProperty(this, "popup", null);
	    babelHelpers.defineProperty(this, "allCounters", {});
	    babelHelpers.defineProperty(this, "wrapper", null);
	    this.wrapper = document.getElementById("history-items");
	    this.checkCounters(allCounters);
	    this.reload();
	    this.viewDesktopUser();
	    this.initPopup();
	  }
	  babelHelpers.createClass(Account, [{
	    key: "checkCounters",
	    value: function checkCounters(allCounters) {
	      for (var _i = 0, _Object$keys = Object.keys(allCounters); _i < _Object$keys.length; _i++) {
	        var counterId = _Object$keys[_i];
	        var key = counterId;
	        if (counterId === '**') {
	          key = 'live-feed';
	        }
	        this.allCounters[key] = allCounters[counterId];
	      }
	    }
	  }, {
	    key: "getSumCounters",
	    value: function getSumCounters() {
	      var sum = 0;
	      for (var _i2 = 0, _Object$keys2 = Object.keys(this.allCounters); _i2 < _Object$keys2.length; _i2++) {
	        var counterId = _Object$keys2[_i2];
	        if (counterId === 'tasks_effective' || counterId === 'invited_users') {
	          continue;
	        }
	        var val = this.allCounters[counterId] ? parseInt(this.allCounters[counterId], 10) : 0;
	        sum += val;
	      }
	      return sum;
	    }
	  }, {
	    key: "reload",
	    value: function reload() {
	      var currentUserId = main_core.Loc.getMessage('USER_ID');
	      this.accounts = 'undefined' !== typeof BXDesktopSystem ? im_v2_lib_desktopApi.DesktopApi.getAccountList() : [];
	      this.currentUser = this.accounts.find(function (account) {
	        return account.id === currentUserId;
	      });
	      this.viewPopupAccounts();
	    }
	  }, {
	    key: "initPopup",
	    value: function initPopup() {
	      var _this = this;
	      var userNode = document.querySelector('.intranet__desktop-menu_user-block');
	      this.popup = new main_popup.Popup({
	        content: document.querySelector('.intranet__desktop-menu_popup'),
	        bindElement: userNode,
	        width: 320,
	        background: '#282e39',
	        closeIcon: true,
	        closeByEsc: true
	      });
	      main_core.Event.bind(userNode, 'click', function () {
	        if (_this.popup.isShown()) {
	          _this.popup.close();
	        } else {
	          _this.popup.show();
	          _this.reload();
	        }
	      });
	    }
	  }, {
	    key: "setCounters",
	    value: function setCounters(counters) {
	      var newCounters = counters;
	      if (counters['data']) {
	        newCounters = counters.data;
	        if (newCounters[0] && babelHelpers["typeof"](newCounters[0]) === 'object') {
	          newCounters = newCounters[0];
	        }
	      }
	      for (var _i3 = 0, _Object$keys3 = Object.keys(newCounters); _i3 < _Object$keys3.length; _i3++) {
	        var counterId = _Object$keys3[_i3];
	        var cId = counterId;
	        if (counterId === '**') {
	          cId = 'live-feed';
	        }
	        this.allCounters[cId] = newCounters[counterId];
	      }
	      var sumCounters = this.getSumCounters();
	      var block = document.getElementsByClassName('intranet__desktop-menu_user-block')[0];
	      var counterNode = block.querySelector('[data-role="counter"]');
	      if (sumCounters > 0) {
	        counterNode.innerHTML = sumCounters > 99 ? '99+' : sumCounters;
	        if (!main_core.Dom.hasClass(block, 'intranet__desktop-menu_item_counters')) {
	          main_core.Dom.addClass(block, 'intranet__desktop-menu_item_counters');
	        }
	      } else {
	        counterNode.innerHTML = '';
	        main_core.Dom.addClass(block, 'intranet__desktop-menu_item_counters');
	      }
	    }
	  }, {
	    key: "removeElements",
	    value: function removeElements(className) {
	      var elements = document.getElementsByClassName(className);
	      babelHelpers.toConsumableArray(elements).forEach(function (element) {
	        element.remove();
	      });
	    }
	  }, {
	    key: "viewDesktopUser",
	    value: function viewDesktopUser() {
	      var block = document.getElementsByClassName('intranet__desktop-menu_user')[0];
	      var counters = this.getSumCounters();
	      var countersView = counters > 99 ? '99+' : counters;
	      this.removeElements('intranet__desktop-menu_user-block');
	      var userData = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet__desktop-menu_user-block ", "\">\n\t\t\t\t<span class=\"intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-desktop\">\n\t\t\t\t\t<i></i>\n\t\t\t\t\t<div class=\"intranet__desktop-menu_user-counter ui-counter ui-counter-md ui-counter-danger\">\n\t\t\t\t\t\t<div class=\"ui-counter-inner\" data-role=\"counter\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"intranet__desktop-menu_user-inner\">\n\t\t\t\t\t<span class=\"intranet__desktop-menu_user-name\">", "</span>\n\t\t\t\t\t<span class=\"intranet__desktop-menu_user-post\">", "</span>\n\t\t\t\t</span>\n\t\t\t</div>"])), counters > 0 ? 'intranet__desktop-menu_item_counters' : '', countersView, this.currentUser.portal, this.currentUser.work_position);
	      main_core.Dom.append(userData, block);
	      var avatar = document.getElementsByClassName('ui-icon-common-user-desktop')[0];
	      var previewImage = this.getAvatarUrl(this.currentUser);
	      main_core.Dom.style(avatar, '--ui-icon-service-bg-image', previewImage);
	    }
	  }, {
	    key: "getAvatarUrl",
	    value: function getAvatarUrl(account) {
	      var avatarUrl = '';
	      if (account.avatar.includes('http://') || account.avatar.includes('https://')) {
	        avatarUrl = account.avatar;
	      } else {
	        avatarUrl = account.protocol + '://' + account.portal + account.avatar;
	      }
	      return "url('".concat(main_core.Text.encode(account.avatar === Account.defaultAvatar ? Account.defaultAvatarDesctop : avatarUrl), "')");
	    }
	  }, {
	    key: "viewPopupAccounts",
	    value: function viewPopupAccounts() {
	      var menuPopup = document.getElementsByClassName('intranet__desktop-menu_popup')[0];
	      var position = '';
	      if (this.currentUser.work_position !== '') {
	        position = "<span class=\"intranet__desktop-menu_popup-post\">".concat(this.currentUser.work_position, "</span>");
	      }
	      this.removeElements('intranet__desktop-menu_popup-header');
	      var item = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet__desktop-menu_popup-header\">\n\t\t\t<span class=\"intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-popup\">\n\t\t\t\t<i></i>\n\t\t\t</span>\n\t\t\t<span class=\"intranet__desktop-menu_popup-label\">", "</span>\n\t\t\t<div class=\"intranet__desktop-menu_popup-header-user\">\n\t\t\t\t<span class=\"intranet__desktop-menu_popup-name\">", "</span>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>"])), this.currentUser.portal, this.currentUser.first_name + ' ' + this.currentUser.last_name, position);
	      main_core.Dom.insertBefore(item, menuPopup.firstElementChild);
	      var avatar = document.getElementsByClassName('ui-icon-common-user-popup')[0];
	      var previewImage = this.getAvatarUrl(this.currentUser);
	      main_core.Dom.style(avatar, '--ui-icon-service-bg-image', previewImage);
	      var block = document.getElementsByClassName('intranet__desktop-menu_popup-list')[0];
	      this.removeElements('intranet__desktop-menu_popup-item-account');
	      var index = 0;
	      var _iterator = _createForOfIteratorHelper(this.accounts),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var account = _step.value;
	          var currentUserClass = '';
	          var counters = 0;
	          if (account.id === this.currentUser.id && account.portal === this.currentUser.portal) {
	            counters = this.getSumCounters();
	            currentUserClass = '--selected';
	          }
	          var countersView = counters > 99 ? '99+' : counters;
	          var _item = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<li class=\"intranet__desktop-menu_popup-item intranet__desktop-menu_popup-item-account ", " ", "\">\n\t\t\t\t\t<span class=\"intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-", "\">\n\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t<div class=\"intranet__desktop-menu_user-counter ui-counter ui-counter-md ui-counter-danger\">\n\t\t\t\t\t\t\t<div class=\"ui-counter-inner\">", "</div>\n\t\t\t\t\t\t</div>\t\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"intranet__desktop-menu_popup-user\">\n\t\t\t\t\t\t<span class=\"intranet__desktop-menu_popup-name\">", "</span>\n\t\t\t\t\t\t<span class=\"intranet__desktop-menu_popup-post\">", "</span>\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"intranet__desktop-menu_popup-btn ui-icon-set --more\" id=\"ui-icon-set-", "\"></span>\n\t\t\t\t</li>"])), counters > 0 ? 'intranet__desktop-menu_item_counters' : '', currentUserClass, index, countersView, account.portal, account.login, index);
	          main_core.Dom.insertBefore(_item, block.children[index]);
	          this.addContextMenu(account, index);
	          var userAvatar = document.getElementsByClassName('ui-icon-common-user-' + index)[0];
	          var previewUserImage = this.getAvatarUrl(account);
	          main_core.Dom.style(userAvatar, '--ui-icon-service-bg-image', previewUserImage);
	          index++;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }, {
	    key: "addContextMenu",
	    value: function addContextMenu(account, index) {
	      var _this2 = this;
	      var button = document.getElementById("ui-icon-set-" + index);
	      if (this.contextPopup[index]) {
	        this.contextPopup[index].destroy();
	      }
	      this.contextPopup[index] = new main_popup.Menu({
	        bindElement: button,
	        className: 'intranet__desktop-menu_context',
	        items: [account.id === this.currentUser.id && account.portal === this.currentUser.portal ? {
	          text: main_core.Loc.getMessage('MENU_ACCOUNT_POPUP_DISCONNECT'),
	          onclick: function onclick(event, item) {
	            var _BXDesktopSystem;
	            var host = account.host;
	            (_BXDesktopSystem = BXDesktopSystem) === null || _BXDesktopSystem === void 0 ? void 0 : _BXDesktopSystem.AccountDisconnect(host);
	            if (this.contextPopup[index]) {
	              this.contextPopup[index].close();
	            }
	            this.popup.close();
	            window.location.reload();
	          }
	        } : {
	          text: main_core.Loc.getMessage('MENU_ACCOUNT_POPUP_CONNECT'),
	          onclick: function onclick(event, item) {
	            var _BXDesktopSystem2;
	            var host = account.host,
	              login = account.login,
	              protocol = account.protocol;
	            var userLang = navigator.language;
	            (_BXDesktopSystem2 = BXDesktopSystem) === null || _BXDesktopSystem2 === void 0 ? void 0 : _BXDesktopSystem2.AccountConnect(host, login, protocol, userLang);
	            if (this.contextPopup[index]) {
	              this.contextPopup[index].close();
	            }
	            this.popup.close();
	          }
	        }, {
	          text: main_core.Loc.getMessage('MENU_ACCOUNT_POPUP_REMOVE'),
	          onclick: function onclick(event, item) {
	            var _BXDesktopSystem3;
	            var host = account.host,
	              login = account.login;
	            (_BXDesktopSystem3 = BXDesktopSystem) === null || _BXDesktopSystem3 === void 0 ? void 0 : _BXDesktopSystem3.AccountDelete(host, login);
	            if (this.contextPopup[index]) {
	              this.contextPopup[index].close();
	            }
	            this.popup.close();
	            window.location.reload();
	          }
	        }]
	      });
	      main_core.Event.bind(button, 'click', function (event) {
	        var index = parseInt(event.target.id.replace('ui-icon-set-', ''));
	        if (_this2.contextPopup[index]) {
	          _this2.contextPopup[index].show();
	        }
	      });
	    }
	  }, {
	    key: "openLoginTab",
	    value: function openLoginTab() {
	      im_v2_lib_desktopApi.DesktopApi.openAddAccountTab();
	    }
	  }]);
	  return Account;
	}();
	babelHelpers.defineProperty(Account, "defaultAvatar", '/bitrix/js/im/images/blank.gif');
	babelHelpers.defineProperty(Account, "defaultAvatarDesctop", '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2');

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getThemePicker = /*#__PURE__*/new WeakSet();
	var _applyTheme = /*#__PURE__*/new WeakSet();
	var Theme = /*#__PURE__*/function () {
	  function Theme() {
	    babelHelpers.classCallCheck(this, Theme);
	    _classPrivateMethodInitSpec(this, _applyTheme);
	    _classPrivateMethodInitSpec(this, _getThemePicker);
	    babelHelpers.defineProperty(this, "bacgroundNode", null);
	  }
	  babelHelpers.createClass(Theme, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      if (!this.bacgroundNode) {
	        var _classPrivateMethodGe;
	        var theme = (_classPrivateMethodGe = _classPrivateMethodGet(this, _getThemePicker, _getThemePicker2).call(this)) === null || _classPrivateMethodGe === void 0 ? void 0 : _classPrivateMethodGe.getAppliedTheme();
	        this.bacgroundNode = document.getElementsByTagName("body")[0];
	        if (theme) {
	          _classPrivateMethodGet(this, _applyTheme, _applyTheme2).call(this, this.bacgroundNode, theme);
	        }
	        main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', function (event) {
	          _classPrivateMethodGet(_this, _applyTheme, _applyTheme2).call(_this, _this.bacgroundNode, event.data.theme);
	        });
	      }
	    }
	  }]);
	  return Theme;
	}();
	function _getThemePicker2() {
	  var _BX$Intranet$Bitrix, _BX$Intranet, _BX$Intranet$Bitrix2, _top$BX$Intranet, _top$BX$Intranet$Bitr;
	  return (_BX$Intranet$Bitrix = (_BX$Intranet = BX.Intranet) === null || _BX$Intranet === void 0 ? void 0 : (_BX$Intranet$Bitrix2 = _BX$Intranet.Bitrix24) === null || _BX$Intranet$Bitrix2 === void 0 ? void 0 : _BX$Intranet$Bitrix2.ThemePicker.Singleton) !== null && _BX$Intranet$Bitrix !== void 0 ? _BX$Intranet$Bitrix : (_top$BX$Intranet = top.BX.Intranet) === null || _top$BX$Intranet === void 0 ? void 0 : (_top$BX$Intranet$Bitr = _top$BX$Intranet.Bitrix24) === null || _top$BX$Intranet$Bitr === void 0 ? void 0 : _top$BX$Intranet$Bitr.ThemePicker.Singleton;
	}
	function _applyTheme2(container, theme) {
	  var previewImage = "url('".concat(main_core.Text.encode(theme.previewImage), "')");
	  main_core.Dom.style(container, 'backgroundImage', previewImage);
	  main_core.Dom.removeClass(container, 'bitrix24-theme-default bitrix24-theme-dark bitrix24-theme-light');
	  var themeClass = 'bitrix24-theme-default';
	  if (theme.id !== 'default') {
	    themeClass = String(theme.id).indexOf('dark:') === 0 ? 'bitrix24-theme-dark' : 'bitrix24-theme-light';
	  }
	  main_core.Dom.addClass(container, themeClass);
	}

	var Counters = /*#__PURE__*/function () {
	  function Counters() {
	    babelHelpers.classCallCheck(this, Counters);
	  }
	  babelHelpers.createClass(Counters, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      BX.addCustomEvent("onPullEvent-main", function (command, params) {
	        var key = 'SITE_ID';
	        var siteId = BX.message(key);
	        if (command === "user_counter" && params[siteId]) {
	          var counters = BX.clone(params[siteId]);
	          _this.updateCounters(counters, false);
	        }
	      });
	      BX.addCustomEvent("onPullEvent-tasks", function (command, params) {
	        if (command === "user_counter" && Number(params.userId) === Number(BX.Loc.getMessage('USER_ID'))) {
	          var counters = {};
	          if (!BX.Type.isUndefined(params.projects_major)) {
	            counters.projects_major = params.projects_major;
	          }
	          if (!BX.Type.isUndefined(params.scrum_total_comments)) {
	            counters.scrum_total_comments = params.scrum_total_comments;
	          }
	          _this.updateCounters(counters, false);
	        }
	      });
	      BX.addCustomEvent(window, "onImUpdateCounter", function (counters) {
	        if (!counters) return;
	        _this.updateCounters(BX.clone(counters), false);
	      });
	      BX.addCustomEvent("onImUpdateCounterMessage", function (counter) {
	        _this.updateCounters({
	          'im-message': counter
	        }, false);
	      });
	      if (BX.browser.SupportLocalStorage()) {
	        BX.addCustomEvent(window, 'onLocalStorageSet', function (params) {
	          if (params.key.substring(0, 4) === 'lmc-') {
	            var counters = {};
	            counters[params.key.substring(4)] = params.value;
	            _this.updateCounters(counters, false);
	          }
	        });
	      }
	      BX.addCustomEvent("onCounterDecrement", function (iDecrement) {
	        _this.decrementCounter(BX("menu-counter-live-feed"), iDecrement);
	      });
	    }
	  }, {
	    key: "updateCounters",
	    value: function updateCounters(counters, send) {
	      BX.ready(function () {
	        if (BX.getClass("BX.Intranet.DescktopLeftMenu")) {
	          BX.Intranet.DescktopLeftMenu.updateCounters(counters, send);
	        }
	      });
	    }
	  }, {
	    key: "decrementCounter",
	    value: function decrementCounter(node, iDecrement) {
	      BX.ready(function () {
	        if (BX.getClass("BX.Intranet.DescktopLeftMenu")) {
	          BX.Intranet.DescktopLeftMenu.decrementCounter(node, iDecrement);
	        }
	      });
	    }
	  }]);
	  return Counters;
	}();

	var Item = /*#__PURE__*/function () {
	  function Item(parentContainer, container) {
	    babelHelpers.classCallCheck(this, Item);
	    this.parentContainer = parentContainer;
	    this.container = container;
	    this.init();
	  }
	  babelHelpers.createClass(Item, [{
	    key: "init",
	    value: function init() {
	      this.makeTextIcons();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.container.dataset.id;
	    }
	  }, {
	    key: "getCode",
	    value: function getCode() {
	      return this.constructor.code;
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.container.querySelector("[data-role='item-text']").textContent;
	    }
	  }, {
	    key: "makeTextIcons",
	    value: function makeTextIcons() {
	      if (!this.container.classList.contains("menu-item-no-icon-state")) {
	        return;
	      }
	      var icon = this.container.querySelector(".menu-item-icon");
	      var text = this.container.querySelector(".menu-item-link-text");
	      if (icon && text) {
	        icon.textContent = this.getShortName(text.textContent);
	      }
	    }
	  }, {
	    key: "getCounterValue",
	    value: function getCounterValue() {
	      var node = this.container.querySelector('[data-role="counter"]');
	      if (!node) {
	        return null;
	      }
	      return parseInt(node.dataset.counterValue);
	    }
	  }, {
	    key: "updateCounter",
	    value: function updateCounter(counterValue) {
	      var node = this.container.querySelector('[data-role="counter"]');
	      if (!node) {
	        return;
	      }
	      var oldValue = parseInt(node.dataset.counterValue) || 0;
	      node.dataset.counterValue = counterValue;
	      if (counterValue > 0) {
	        node.innerHTML = counterValue > 99 ? '99+' : counterValue;
	        this.container.classList.add('intranet__desktop-menu_item_counters');
	      } else {
	        node.innerHTML = '';
	        this.container.classList.remove('menu-item-with-index');
	      }
	      return {
	        oldValue: oldValue,
	        newValue: counterValue
	      };
	    }
	  }, {
	    key: "getShortName",
	    value: function getShortName(name) {
	      if (!main_core.Type.isStringFilled(name)) {
	        return "...";
	      }
	      name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
	      if (name.length <= 0) {
	        return '...';
	      }
	      var shortName;
	      var words = name.split(/[\s,]+/);
	      if (words.length <= 1) {
	        shortName = name.substring(0, 1);
	      } else if (words.length === 2) {
	        shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
	      } else {
	        var firstWord = words[0];
	        var secondWord = words[1];
	        for (var i = 1; i < words.length; i++) {
	          if (words[i].length > 3) {
	            secondWord = words[i];
	            break;
	          }
	        }
	        shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
	      }
	      return shortName.toUpperCase();
	    }
	  }], [{
	    key: "detect",
	    value: function detect(node) {
	      return node.getAttribute("data-role") !== 'group' && node.getAttribute("data-type") === this.code;
	    }
	  }]);
	  return Item;
	}();

	var ItemAdminShared = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemAdminShared, _Item);
	  function ItemAdminShared() {
	    babelHelpers.classCallCheck(this, ItemAdminShared);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemAdminShared).apply(this, arguments));
	  }
	  return ItemAdminShared;
	}(Item);
	babelHelpers.defineProperty(ItemAdminShared, "code", 'admin');

	var ItemAdminShared$1 = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemAdminShared, _Item);
	  function ItemAdminShared() {
	    babelHelpers.classCallCheck(this, ItemAdminShared);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemAdminShared).apply(this, arguments));
	  }
	  return ItemAdminShared;
	}(Item);
	babelHelpers.defineProperty(ItemAdminShared$1, "code", 'custom');

	var ItemUserFavorites = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemUserFavorites, _Item);
	  function ItemUserFavorites() {
	    babelHelpers.classCallCheck(this, ItemUserFavorites);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemUserFavorites).apply(this, arguments));
	  }
	  return ItemUserFavorites;
	}(Item);
	babelHelpers.defineProperty(ItemUserFavorites, "code", 'standard');

	var ItemUserSelf = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemUserSelf, _Item);
	  function ItemUserSelf() {
	    babelHelpers.classCallCheck(this, ItemUserSelf);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemUserSelf).apply(this, arguments));
	  }
	  return ItemUserSelf;
	}(Item);
	babelHelpers.defineProperty(ItemUserSelf, "code", 'self');

	var ItemSystem = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemSystem, _Item);
	  function ItemSystem() {
	    babelHelpers.classCallCheck(this, ItemSystem);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemSystem).apply(this, arguments));
	  }
	  return ItemSystem;
	}(Item);
	babelHelpers.defineProperty(ItemSystem, "code", 'default');

	var itemMappings = [Item, ItemAdminShared, ItemUserFavorites, ItemAdminShared$1, ItemUserSelf, ItemSystem];
	function getItem(itemData) {
	  var itemClassName = Item;
	  itemMappings.forEach(function (itemClass) {
	    if (itemClass.detect(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return itemClassName;
	}

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _updateCountersLastValue = /*#__PURE__*/new WeakMap();
	var _getItemsByCounterId = /*#__PURE__*/new WeakSet();
	var ItemsController = /*#__PURE__*/function () {
	  function ItemsController(container) {
	    babelHelpers.classCallCheck(this, ItemsController);
	    _classPrivateMethodInitSpec$1(this, _getItemsByCounterId);
	    babelHelpers.defineProperty(this, "items", new Map());
	    _classPrivateFieldInitSpec(this, _updateCountersLastValue, {
	      writable: true,
	      value: null
	    });
	    this.parentContainer = container;
	    this.container = container.querySelector(".menu-items");
	    container.querySelectorAll('li.menu-item-block').forEach(this.registerItem.bind(this));
	  }
	  babelHelpers.createClass(ItemsController, [{
	    key: "registerItem",
	    value: function registerItem(node) {
	      var itemClass = getItem(node);
	      var item = new itemClass(this.container, node);
	      this.items.set(item.getId(), item);
	      return item;
	    }
	  }, {
	    key: "updateCounters",
	    value: function updateCounters(counters, send) {
	      var _this = this;
	      var countersDynamic = null;
	      send = send !== false;
	      babelHelpers.toConsumableArray(Object.entries(counters)).forEach(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	          counterId = _ref2[0],
	          counterValue = _ref2[1];
	        babelHelpers.toConsumableArray(_classPrivateMethodGet$1(_this, _getItemsByCounterId, _getItemsByCounterId2).call(_this, counterId)).forEach(function (item) {
	          var _item$updateCounter = item.updateCounter(counterValue),
	            oldValue = _item$updateCounter.oldValue,
	            newValue = _item$updateCounter.newValue;
	          if ((counterId.indexOf('crm_') < 0 || counterId.indexOf('crm_all') >= 0) && (counterId.indexOf('tasks_') < 0 || counterId.indexOf('tasks_total') >= 0)) {
	            countersDynamic = countersDynamic || 0;
	            countersDynamic += newValue - oldValue;
	          }
	        });
	        if (send) {
	          BX.localStorage.set('lmc-' + counterId, counterValue, 5);
	        }
	        if (typeof BXIM !== 'undefined') {
	          if (babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue) === null) {
	            babelHelpers.classPrivateFieldSet(_this, _updateCountersLastValue, 0);
	            babelHelpers.toConsumableArray(_this.items.entries()).forEach(function (_ref3) {
	              var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	                id = _ref4[0],
	                item = _ref4[1];
	              var res = item.getCounterValue();
	              if (res > 0) {
	                var _counterId = 'doesNotMatter';
	                if (id.indexOf('menu_crm') >= 0 || id.indexOf('menu_tasks') >= 0) {
	                  var counterNode = item.container.querySelector('[data-role="counter"]');
	                  if (counterNode) {
	                    _counterId = counterNode.id;
	                  }
	                }
	                if (_counterId === 'doesNotMatter' || _counterId.indexOf('crm_all') >= 0 || _counterId.indexOf('tasks_total') >= 0) {
	                  babelHelpers.classPrivateFieldSet(_this, _updateCountersLastValue, babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue) + res);
	                }
	              }
	            });
	          } else {
	            babelHelpers.classPrivateFieldSet(_this, _updateCountersLastValue, babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue) + (countersDynamic !== null ? countersDynamic : 0));
	          }
	          var visibleValue = babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue) > 99 ? '99+' : babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue) < 0 ? '0' : babelHelpers.classPrivateFieldGet(_this, _updateCountersLastValue);
	          if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	            im_v2_lib_desktopApi.DesktopApi.setBrowserIconBadge(visibleValue);
	          }
	        }
	      });
	    }
	  }, {
	    key: "decrementCounter",
	    value: function decrementCounter(counters) {
	      var _this2 = this;
	      babelHelpers.toConsumableArray(Object.entries(counters)).forEach(function (_ref5) {
	        var _ref6 = babelHelpers.slicedToArray(_ref5, 2),
	          counterId = _ref6[0],
	          counterValue = _ref6[1];
	        var item = _classPrivateMethodGet$1(_this2, _getItemsByCounterId, _getItemsByCounterId2).call(_this2, counterId).shift();
	        if (item) {
	          var value = item.getCounterValue();
	          counters[counterId] = value > counterValue ? value - counterValue : 0;
	        } else {
	          delete counters[counterId];
	        }
	      });
	      this.updateCounters(counters, false);
	    }
	  }]);
	  return ItemsController;
	}();
	function _getItemsByCounterId2(counterId) {
	  var result = [];
	  babelHelpers.toConsumableArray(this.items.values()).forEach(function (item) {
	    var node = item.container.querySelector('[data-role="counter"]');
	    if (node && node.id.indexOf(counterId) >= 0) {
	      result.push(item);
	    }
	  });
	  return result;
	}

	var _templateObject$1;
	var BrowserHistory = /*#__PURE__*/function () {
	  function BrowserHistory() {
	    babelHelpers.classCallCheck(this, BrowserHistory);
	    babelHelpers.defineProperty(this, "items", []);
	    this.wrapper = document.getElementById("history-items");
	  }
	  babelHelpers.createClass(BrowserHistory, [{
	    key: "init",
	    value: function init() {
	      var _BXDesktopSystem;
	      if ('object' != (typeof BXDesktopSystem === "undefined" ? "undefined" : babelHelpers["typeof"](BXDesktopSystem))) {
	        console.log('BXDesktopSystem is empty');
	        return;
	      }
	      this.items = (_BXDesktopSystem = BXDesktopSystem) === null || _BXDesktopSystem === void 0 ? void 0 : _BXDesktopSystem.BrowserHistory();
	      this.showHistory();
	    }
	  }, {
	    key: "showHistory",
	    value: function showHistory() {
	      var _this = this;
	      var i = 0;
	      this.items.forEach(function (item) {
	        if (i > 15) {
	          return true;
	        }
	        var icoName = '';
	        var title = '';
	        if (main_core.Type.isStringFilled(item.title)) {
	          icoName = _this.getShortName(item.title);
	          title = item.title;
	        } else {
	          if (item.url.includes('/desktop_app/')) {
	            icoName = Loc.getMessage('MENU_HISTORY_ITEM_ICON');
	            title = Loc.getMessage('MENU_HISTORY_ITEM_NAME');
	          } else {
	            return;
	          }
	        }
	        if (item.url.includes('/desktop/menu')) {
	          return;
	        }
	        var url = item.url;
	        if (item.url.includes('/online/')) {
	          url = 'bx://v2/' + location.hostname + '/chat/';
	        }
	        var li = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<li class=\"intranet__desktop-menu_item\">\n\t\t\t\t\t<a class=\"intranet__desktop-menu_item-link\" href=\"", "\">\n\t\t\t\t\t\t<span class=\"intranet__desktop-menu_item-icon --custom\">", "</span>\n\t\t\t\t\t\t<span class=\"intranet__desktop-menu_item-title\">", "</span>\n\t\t\t\t\t</a>\n\t\t\t\t</li>\n\t\t\t"])), url, icoName, title);
	        _this.wrapper.appendChild(li);
	        i++;
	      });
	    }
	  }, {
	    key: "getShortName",
	    value: function getShortName(name) {
	      if (!main_core.Type.isStringFilled(name)) {
	        return "...";
	      }
	      name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
	      if (name.length <= 0) {
	        return '...';
	      }
	      var shortName;
	      var words = name.split(/[\s,]+/);
	      if (words.length <= 1) {
	        shortName = name.substring(0, 1);
	      } else if (words.length === 2) {
	        shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
	      } else {
	        var firstWord = words[0];
	        var secondWord = words[1];
	        for (var i = 1; i < words.length; i++) {
	          if (words[i].length > 3) {
	            secondWord = words[i];
	            break;
	          }
	        }
	        shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
	      }
	      return shortName.toUpperCase();
	    }
	  }]);
	  return BrowserHistory;
	}();

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _specialLiveFeedDecrement = /*#__PURE__*/new WeakMap();
	var DesktopMenu = /*#__PURE__*/function () {
	  function DesktopMenu(allCounters) {
	    babelHelpers.classCallCheck(this, DesktopMenu);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "browserHistory", null);
	    babelHelpers.defineProperty(this, "account", null);
	    babelHelpers.defineProperty(this, "theme", null);
	    _classPrivateFieldInitSpec$1(this, _specialLiveFeedDecrement, {
	      writable: true,
	      value: 0
	    });
	    this.menuContainer = document.getElementById("menu-items-block");
	    if (!this.menuContainer) {
	      return false;
	    }
	    this.initTheme();
	    this.getItemsController();
	    this.getHistoryItems();
	    this.showAccount(allCounters);
	    this.runAPICounters();
	  }
	  babelHelpers.createClass(DesktopMenu, [{
	    key: "initTheme",
	    value: function initTheme() {
	      this.theme = new Theme();
	      this.theme.init();
	    }
	  }, {
	    key: "getItemsController",
	    value: function getItemsController() {
	      var _this = this;
	      return this.cache.remember('itemsMenuController', function () {
	        return new ItemsController(_this.menuContainer);
	      });
	    }
	  }, {
	    key: "getHistoryItems",
	    value: function getHistoryItems() {
	      this.browserHistory = new BrowserHistory();
	      this.browserHistory.init();
	    }
	  }, {
	    key: "showAccount",
	    value: function showAccount(allCounters) {
	      this.account = new Account(allCounters);
	      BX.Intranet.Account = this.account;
	    }
	  }, {
	    key: "runAPICounters",
	    value: function runAPICounters() {
	      BX.Intranet.Counters = new Counters();
	      BX.Intranet.Counters.init();
	    }
	  }, {
	    key: "decrementCounter",
	    value: function decrementCounter(node, iDecrement) {
	      if (!node || node.id !== 'menu-counter-live-feed') {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _specialLiveFeedDecrement, babelHelpers.classPrivateFieldGet(this, _specialLiveFeedDecrement) + parseInt(iDecrement));
	      this.getItemsController().decrementCounter({
	        'live-feed': parseInt(iDecrement)
	      });
	    }
	  }, {
	    key: "updateCounters",
	    value: function updateCounters(counters, send) {
	      if (!counters) {
	        return;
	      }
	      if (counters['**'] !== undefined) {
	        counters['live-feed'] = counters['**'];
	        delete counters['**'];
	      }
	      var workgroupsCounterUpdated = false;
	      if (!main_core.Type.isUndefined(counters['**SG0'])) {
	        this.workgroupsCounterData['livefeed'] = counters['**SG0'];
	        delete counters['**SG0'];
	        workgroupsCounterUpdated = true;
	      }
	      if (!main_core.Type.isUndefined(counters[main_core.Loc.getMessage('COUNTER_PROJECTS_MAJOR')])) {
	        this.workgroupsCounterData[main_core.Loc.getMessage('COUNTER_PROJECTS_MAJOR')] = counters[main_core.Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
	        delete counters[main_core.Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
	        workgroupsCounterUpdated = true;
	      }
	      if (!main_core.Type.isUndefined(counters[main_core.Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')])) {
	        this.workgroupsCounterData[main_core.Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')] = counters[main_core.Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
	        delete counters[main_core.Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
	        workgroupsCounterUpdated = true;
	      }
	      if (workgroupsCounterUpdated) {
	        counters['workgroups'] = Object.entries(this.workgroupsCounterData).reduce(function (prevValue, _ref) {
	          var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            curValue = _ref2[1];
	          return prevValue + Number(curValue);
	        }, 0);
	      }
	      if (counters['live-feed']) {
	        if (counters['live-feed'] <= 0) {
	          babelHelpers.classPrivateFieldSet(this, _specialLiveFeedDecrement, 0);
	        } else {
	          counters['live-feed'] -= babelHelpers.classPrivateFieldGet(this, _specialLiveFeedDecrement);
	        }
	      }
	      this.getItemsController().updateCounters(counters, send);
	      BX.Intranet.Account.setCounters(counters);
	    }
	  }]);
	  return DesktopMenu;
	}();

	exports.DesktopMenu = DesktopMenu;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Main,BX.Event,BX.Messenger.v2.Lib,BX));
//# sourceMappingURL=script.js.map

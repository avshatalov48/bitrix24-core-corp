this.BX = this.BX || {};
(function (exports,ui_buttons,main_core_event,main_core,main_popup,main_core_events,ui_dialogs_messagebox) {
	'use strict';

	var Options = /*#__PURE__*/function () {
	  function Options() {
	    babelHelpers.classCallCheck(this, Options);
	  }
	  babelHelpers.createClass(Options, null, [{
	    key: "eventName",
	    value: function eventName(name) {
	      return ['BX.Intranet.LeftMenu:'].concat(babelHelpers.toConsumableArray(main_core.Type.isStringFilled(name) ? [name] : name)).join(':');
	    }
	  }]);
	  return Options;
	}();
	babelHelpers.defineProperty(Options, "version", '2021.10');
	babelHelpers.defineProperty(Options, "eventNameSpace", 'BX.Intranet.LeftMenu:');
	babelHelpers.defineProperty(Options, "isExtranet", false);
	babelHelpers.defineProperty(Options, "isAdmin", false);
	babelHelpers.defineProperty(Options, "isCustomPresetRestricted", false);

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _popup = /*#__PURE__*/new WeakMap();
	var DefaultController = /*#__PURE__*/function () {
	  function DefaultController(container, _ref) {
	    var _this = this;
	    var events = _ref.events;
	    babelHelpers.classCallCheck(this, DefaultController);
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: null
	    });
	    this.container = container;
	    if (events) {
	      Array.from(Object.keys(events)).forEach(function (key) {
	        main_core_events.EventEmitter.subscribe(_this, Options.eventName(key), events[key]);
	      });
	    }
	  }
	  babelHelpers.createClass(DefaultController, [{
	    key: "getContainer",
	    value: function getContainer() {
	      return this.container;
	    }
	  }, {
	    key: "createPopup",
	    value: function createPopup() {}
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      return babelHelpers.classPrivateFieldGet(this, _popup);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _popup) === null) {
	        babelHelpers.classPrivateFieldSet(this, _popup, this.createPopup.apply(this, arguments));
	        main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldGet(this, _popup), 'onClose', function () {
	          main_core_events.EventEmitter.emit(_this2, Options.eventName('onClose'));
	        });
	        main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldGet(this, _popup), 'onShow', function () {
	          main_core_events.EventEmitter.emit(_this2, Options.eventName('onShow'));
	        });
	        main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldGet(this, _popup), 'onDestroy', function () {
	          babelHelpers.classPrivateFieldSet(_this2, _popup, null);
	        });
	      }
	      babelHelpers.classPrivateFieldGet(this, _popup).show();
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	        babelHelpers.classPrivateFieldGet(this, _popup).close();
	      }
	    }
	  }]);
	  return DefaultController;
	}();

	var _templateObject;
	var PresetCustomController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(PresetCustomController, _DefaultController);
	  function PresetCustomController() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, PresetCustomController);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(PresetCustomController)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "isReady", true);
	    return _this;
	  }
	  babelHelpers.createClass(PresetCustomController, [{
	    key: "createPopup",
	    value: function createPopup() {
	      var _this2 = this;
	      var form = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<form id=\"customPresetForm\" style=\"min-width: 350px;\">\n\t\t\t\t<div style=\"margin: 15px 0 15px 9px;\">\n\t\t\t\t\t<input type=\"radio\" name=\"userScope\" id=\"customPresetCurrentUser\" value=\"currentUser\">\n\t\t\t\t\t<label for=\"customPresetCurrentUser\">", "</label>\n\t\t\t\t</div>\n\t\t\t\t<div style=\"margin: 0 0 38px 9px;\">\n\t\t\t\t\t<input type=\"radio\" name=\"userScope\" id=\"customPresetNewUser\" value=\"newUser\" checked>\n\t\t\t\t\t<label for=\"customPresetNewUser\">", "</label>\n\t\t\t\t</div>\n\t\t\t\t<hr style=\"background-color: #edeef0; border: none; color:  #edeef0; height: 1px;\">\n\t\t\t</form>"])), main_core.Loc.getMessage("MENU_CUSTOM_PRESET_CURRENT_USER"), main_core.Loc.getMessage("MENU_CUSTOM_PRESET_NEW_USER"));
	      var button;
	      return main_popup.PopupManager.create(this.constructor.toString(), null, {
	        overlay: true,
	        contentColor: "white",
	        contentNoPaddings: true,
	        lightShadow: true,
	        draggable: {
	          restrict: true
	        },
	        closeByEsc: true,
	        titleBar: main_core.Loc.getMessage("MENU_CUSTOM_PRESET_POPUP_TITLE"),
	        offsetTop: 1,
	        offsetLeft: 20,
	        cacheable: false,
	        closeIcon: true,
	        content: form,
	        buttons: [button = new ui_buttons.SaveButton({
	          onclick: function onclick() {
	            if (_this2.isReady === false) {
	              return;
	            }
	            button.setWaiting(true);
	            _this2.isReady = false;
	            main_core_events.EventEmitter.emit(_this2, Options.eventName('onPresetIsSet'), form.elements["userScope"].value === 'newUser').forEach(function (promise) {
	              promise.then(function () {
	                button.setWaiting(false);
	                _this2.hide();
	                main_popup.PopupManager.create("menu-custom-preset-success-popup", null, {
	                  closeIcon: true,
	                  contentColor: "white",
	                  titleBar: main_core.Loc.getMessage("MENU_CUSTOM_PRESET_POPUP_TITLE"),
	                  content: main_core.Loc.getMessage("MENU_CUSTOM_PRESET_SUCCESS")
	                }).show();
	              })["catch"](function () {
	                console.log('Error!!');
	              });
	            });
	          }
	        }), new ui_buttons.CancelButton({
	          onclick: function onclick() {
	            _this2.hide();
	          }
	        })]
	      });
	    }
	  }]);
	  return PresetCustomController;
	}(DefaultController);

	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var AdminPanel = /*#__PURE__*/function () {
	  function AdminPanel() {
	    babelHelpers.classCallCheck(this, AdminPanel);
	    this.container = BX("bx-panel");
	    if (this.container) {
	      this.bindEvents();
	    }
	  }
	  babelHelpers.createClass(AdminPanel, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      BX.addCustomEvent("onTopPanelCollapse", function (isCollapsed) {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onPanelHasChanged'), this.top);
	      }.bind(this));
	      BX.addCustomEvent("onTopPanelFix", function () {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onPanelHasChanged'), this.top);
	      }.bind(this));
	    }
	  }, {
	    key: "height",
	    get: function get() {
	      return this.container ? this.container.offsetHeight : 0;
	    }
	  }, {
	    key: "fixedHeight",
	    get: function get() {
	      var adminPanelState = BX.getClass("BX.admin.panel.state");
	      if (adminPanelState && adminPanelState.fixed) {
	        return this.height;
	      }
	      return 0;
	    }
	  }, {
	    key: "top",
	    get: function get() {
	      if (this.container) {
	        var rect = this.container.getBoundingClientRect();
	        if (rect.bottom > 0) {
	          return Math.max(rect.bottom, this.fixedHeight);
	        }
	        return Math.max(0, this.fixedHeight);
	      }
	      return 0;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!_classStaticPrivateFieldSpecGet(this, AdminPanel, _instance)) {
	        _classStaticPrivateFieldSpecSet(this, AdminPanel, _instance, new AdminPanel());
	      }
	      return _classStaticPrivateFieldSpecGet(this, AdminPanel, _instance);
	    }
	  }]);
	  return AdminPanel;
	}();
	var _instance = {
	  writable: true,
	  value: null
	};

	function _classStaticPrivateFieldSpecSet$1(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "set"); _classApplyDescriptorSet$1(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet$1(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet$1(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$1(descriptor, "get"); return _classApplyDescriptorGet$1(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor$1(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess$1(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet$1(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var Utils = /*#__PURE__*/function () {
	  function Utils() {
	    babelHelpers.classCallCheck(this, Utils);
	  }
	  babelHelpers.createClass(Utils, null, [{
	    key: "getCurPage",
	    value: function getCurPage() {
	      if (_classStaticPrivateFieldSpecGet$1(this, Utils, _curPage) === null) {
	        _classStaticPrivateFieldSpecSet$1(this, Utils, _curPage, document.location.pathname + document.location.search);
	      }
	      return _classStaticPrivateFieldSpecGet$1(this, Utils, _curPage) === '' ? null : _classStaticPrivateFieldSpecGet$1(this, Utils, _curPage);
	    }
	  }, {
	    key: "getCurUri",
	    value: function getCurUri() {
	      if (_classStaticPrivateFieldSpecGet$1(this, Utils, _curUri) === null) {
	        _classStaticPrivateFieldSpecSet$1(this, Utils, _curUri, new main_core.Uri(document.location.href));
	      }
	      return _classStaticPrivateFieldSpecGet$1(this, Utils, _curUri);
	    }
	  }, {
	    key: "catchError",
	    value: function catchError(response) {
	      BX.UI.Notification.Center.notify({
	        content: [main_core.Loc.getMessage("MENU_ERROR_OCCURRED"), response.errors ? ': ' + response.errors[0].message : ''].join(' '),
	        position: 'bottom-left',
	        category: 'menu-self-item-popup',
	        autoHideDelay: 3000
	      });
	    }
	  }, {
	    key: "refineUrl",
	    value: function refineUrl(url) {
	      url = String(url).trim();
	      if (url !== '') {
	        if (!url.match(/^https?:\/\//i) && !url.match(/^\//i)) {
	          //for external links like "google.com" (without a protocol)
	          url = "http://" + url;
	        } else {
	          var link = document.createElement("a");
	          link.href = url;
	          if (document.location.host === link.host) {
	            // http://portal.com/path/ => /path/
	            url = link.pathname + link.search + link.hash;
	          }
	        }
	      }
	      return url;
	    }
	  }, {
	    key: "adminPanel",
	    get: function get() {
	      return AdminPanel.getInstance();
	    }
	  }]);
	  return Utils;
	}();
	var _curPage = {
	  writable: true,
	  value: null
	};
	var _curUri = {
	  writable: true,
	  value: null
	};

	var PresetDefaultController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(PresetDefaultController, _DefaultController);
	  function PresetDefaultController() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, PresetDefaultController);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(PresetDefaultController)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "isReady", true);
	    return _this;
	  }
	  babelHelpers.createClass(PresetDefaultController, [{
	    key: "createPopup",
	    value: function createPopup(mode) {
	      var _this2 = this;
	      var button;
	      var content = document.querySelector('#left-menu-preset-popup').cloneNode(true);
	      return main_popup.PopupManager.create(this.constructor.name.toString(), null, {
	        overlay: true,
	        contentColor: "white",
	        contentNoPaddings: true,
	        lightShadow: true,
	        draggable: {
	          restrict: true
	        },
	        closeByEsc: true,
	        offsetTop: 1,
	        offsetLeft: 20,
	        cacheable: false,
	        closeIcon: true,
	        content: content,
	        events: {
	          onFirstShow: function onFirstShow() {
	            babelHelpers.toConsumableArray(content.querySelectorAll('.js-left-menu-preset-item')).forEach(function (node) {
	              node.addEventListener('click', function () {
	                babelHelpers.toConsumableArray(content.querySelectorAll('.js-left-menu-preset-item')).forEach(function (otherNode) {
	                  otherNode.classList[otherNode === node ? 'add' : 'remove']('left-menu-popup-selected');
	                });
	              });
	            });
	          }
	        },
	        buttons: [button = new ui_buttons.CreateButton({
	          text: main_core.Loc.getMessage('MENU_CONFIRM_BUTTON'),
	          onclick: function onclick() {
	            if (button.isWaiting()) {
	              return;
	            }
	            button.setWaiting(true);
	            var currentPreset = "";
	            if (document.forms["left-menu-preset-form"]) {
	              babelHelpers.toConsumableArray(document.forms["left-menu-preset-form"].elements["presetType"]).forEach(function (node) {
	                if (node.checked) {
	                  currentPreset = node.value;
	                }
	              });
	            }
	            main_core_events.EventEmitter.emit(_this2, Options.eventName('onPresetIsSet'), {
	              presetId: currentPreset,
	              mode: mode
	            }).forEach(function (promise) {
	              promise.then(function (response) {
	                button.setWaiting(false);
	                _this2.hide();
	                if (response.data.hasOwnProperty("url")) {
	                  document.location.href = response.data.url;
	                } else {
	                  document.location.reload();
	                }
	              })["catch"](Utils.catchError);
	            });
	          }
	        }), new ui_buttons.CancelButton({
	          text: main_core.Loc.getMessage('MENU_DELAY_BUTTON'),
	          onclick: function onclick() {
	            main_core_events.EventEmitter.emit(_this2, Options.eventName('onPresetIsPostponed'), {
	              mode: mode
	            });
	            _this2.hide();
	          }
	        })]
	      });
	    }
	  }]);
	  return PresetDefaultController;
	}(DefaultController);

	var SettingsController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(SettingsController, _DefaultController);
	  function SettingsController() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, SettingsController);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(SettingsController)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "menuId", 'leftMenuSettingsPopup');
	    return _this;
	  }
	  babelHelpers.createClass(SettingsController, [{
	    key: "createPopup",
	    value: function createPopup() {
	      var menu = new main_popup.Menu({
	        bindElement: this.container,
	        items: this.getItems(),
	        angle: true,
	        offsetTop: 0,
	        offsetLeft: 50
	        // cacheable: false,
	      });

	      return menu.getPopupWindow();
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      var menuItems = [];
	      Array.from.apply(Array, babelHelpers.toConsumableArray(main_core_events.EventEmitter.emit(this, Options.eventName('onGettingSettingMenuItems')))).forEach(function (_ref) {
	        var text = _ref.text,
	          html = _ref.html,
	          _onclick = _ref.onclick,
	          className = _ref.className;
	        if (!text && !html) {
	          return;
	        }
	        menuItems.push(Object.assign(html ? {
	          html: html
	        } : {
	          text: text
	        }, {
	          className: ["menu-popup-no-icon", className !== null && className !== void 0 ? className : ''].join(' '),
	          onclick: function onclick(event, item) {
	            item.getMenuWindow().close();
	            _onclick(event, item);
	          }
	        }));
	      });
	      return menuItems;
	    }
	  }]);
	  return SettingsController;
	}(DefaultController);

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }
	  babelHelpers.createClass(Backend, null, [{
	    key: "toggleMenu",
	    value: function toggleMenu(collapse) {
	      if (main_core.Loc.getMessage('USER_ID') <= 0) {
	        return;
	      }
	      return main_core.ajax.runAction("intranet.leftmenu.".concat(collapse ? "collapseMenu" : "expandMenu"), {
	        data: {},
	        analyticsLabel: {
	          leftmenu: {
	            action: collapse ? "collapseMenu" : "expandMenu"
	          }
	        }
	      });
	    }
	  }, {
	    key: "saveSelfItemMenu",
	    value: function saveSelfItemMenu(itemData) {
	      var action = itemData.id > 0 ? "update" : "add";
	      return main_core.ajax.runAction("intranet.leftmenu.".concat(action, "SelfItem"), {
	        data: {
	          itemData: itemData
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'selfItemAddOrUpdate'
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteSelfITem",
	    value: function deleteSelfITem(id) {
	      return main_core.ajax.runAction('intranet.leftmenu.deleteSelfItem', {
	        data: {
	          menuItemId: id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'selfItemDelete'
	          }
	        }
	      });
	    }
	  }, {
	    key: "addFavoritesItemMenu",
	    value: function addFavoritesItemMenu(itemData) {
	      return main_core.ajax.runAction('intranet.leftmenu.addStandartItem', {
	        data: {
	          itemData: itemData
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'standardItemAdd'
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteFavoritesItemMenu",
	    value: function deleteFavoritesItemMenu(itemData) {
	      return main_core.ajax.runAction('intranet.leftmenu.deleteStandartItem', {
	        data: {
	          itemData: itemData
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'standardItemDelete'
	          }
	        }
	      });
	    }
	  }, {
	    key: "updateFavoritesItemMenu",
	    value: function updateFavoritesItemMenu(itemData) {
	      return main_core.ajax.runAction('intranet.leftmenu.updateStandartItem', {
	        data: {
	          itemText: itemData.text,
	          itemId: itemData.id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'standardItemUpdate'
	          }
	        }
	      });
	    }
	  }, {
	    key: "addAdminSharedItemMenu",
	    value: function addAdminSharedItemMenu(itemData) {
	      return main_core.ajax.runAction('intranet.leftmenu.addItemToAll', {
	        data: {
	          itemInfo: itemData
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'adminItemAdd'
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteAdminSharedItemMenu",
	    value: function deleteAdminSharedItemMenu(id) {
	      return main_core.ajax.runAction('intranet.leftmenu.deleteItemFromAll', {
	        data: {
	          menu_item_id: id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'adminItemDelete'
	          }
	        }
	      });
	    }
	  }, {
	    key: "saveItemsSort",
	    value: function saveItemsSort(menuItems, firstItemLink, analyticsLabel) {
	      return main_core.ajax.runAction('intranet.leftmenu.saveItemsSort', {
	        data: {
	          items: menuItems,
	          firstItemLink: firstItemLink,
	          version: Options.version
	        },
	        analyticsLabel: {
	          leftmenu: analyticsLabel
	        }
	      });
	    }
	  }, {
	    key: "setFirstPage",
	    value: function setFirstPage(firstPageLink) {
	      return main_core.ajax.runAction('intranet.leftmenu.setFirstPage', {
	        data: {
	          firstPageUrl: firstPageLink
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'mainPageIsSet'
	          }
	        }
	      });
	    }
	  }, {
	    key: "setDefaultPreset",
	    value: function setDefaultPreset() {
	      return main_core.ajax.runAction('intranet.leftmenu.setDefaultMenu', {
	        data: {},
	        analyticsLabel: {
	          leftmenu: {
	            action: 'defaultMenuIsSet'
	          }
	        }
	      });
	    }
	  }, {
	    key: "setCustomPreset",
	    value: function setCustomPreset(forNewUsersOnly, itemsSort, customItems, firstItemLink) {
	      return main_core.ajax.runAction('intranet.leftmenu.saveCustomPreset', {
	        data: {
	          userApply: forNewUsersOnly === true ? 'newUser' : 'currentUser',
	          itemsSort: itemsSort,
	          customItems: customItems,
	          firstItemLink: firstItemLink,
	          version: Options.version
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'customPresetIsSet'
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteCustomItem",
	    value: function deleteCustomItem(id) {
	      return main_core.ajax.runAction('intranet.leftmenu.deleteCustomItemFromAll', {
	        data: {
	          menu_item_id: id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'customItemDelete'
	          }
	        }
	      });
	    }
	  }, {
	    key: "setSystemPreset",
	    value: function setSystemPreset(mode, presetId) {
	      return main_core.ajax.runAction('intranet.leftmenu.setPreset', {
	        data: {
	          preset: presetId,
	          mode: mode === 'global' ? 'global' : 'personal'
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'systemPresetIsSet',
	            presetId: presetId,
	            mode: mode,
	            analyticsFirst: mode === 'global' ? 'y' : 'n'
	          }
	        }
	      });
	    }
	  }, {
	    key: "postponeSystemPreset",
	    value: function postponeSystemPreset(mode) {
	      return main_core.ajax.runAction('intranet.leftmenu.delaySetPreset', {
	        data: {},
	        analyticsLabel: {
	          leftmenu: {
	            action: 'systemPresetIsPostponed',
	            mode: mode,
	            analyticsFirst: mode === 'global' ? 'y' : 'n'
	          }
	        }
	      });
	    }
	  }, {
	    key: "clearCache",
	    value: function clearCache() {
	      return main_core.ajax.runAction('intranet.leftmenu.clearCache', {
	        data: {},
	        analyticsLabel: {
	          leftmenu: {
	            action: 'clearCache'
	          }
	        }
	      });
	    }
	  }, {
	    key: "expandGroup",
	    value: function expandGroup(id) {
	      if (main_core.Loc.getMessage('USER_ID') <= 0) {
	        return;
	      }
	      return main_core.ajax.runAction('intranet.leftmenu.expandMenuGroup', {
	        data: {
	          id: id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'expandMenuGroup'
	          }
	        }
	      });
	    }
	  }, {
	    key: "collapseGroup",
	    value: function collapseGroup(id) {
	      if (main_core.Loc.getMessage('USER_ID') <= 0) {
	        return;
	      }
	      return main_core.ajax.runAction('intranet.leftmenu.collapseMenuGroup', {
	        data: {
	          id: id
	        },
	        analyticsLabel: {
	          leftmenu: {
	            action: 'collapseMenuGroup'
	          }
	        }
	      });
	    }
	  }]);
	  return Backend;
	}();

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4;
	var Item = /*#__PURE__*/function () {
	  function Item(parentContainer, container) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Item);
	    babelHelpers.defineProperty(this, "links", []);
	    babelHelpers.defineProperty(this, "isDraggable", true);
	    babelHelpers.defineProperty(this, "storage", []);
	    this.parentContainer = parentContainer;
	    this.container = container;
	    this.init();
	    this.onDeleteAsFavorites = this.onDeleteAsFavorites.bind(this);
	    setTimeout(function () {
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), _this.onDeleteAsFavorites);
	      main_core_events.EventEmitter.incrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
	      main_core_events.EventEmitter.subscribe(_this, Options.eventName('onItemDelete'), _this.destroy.bind(_this));
	    }, 0);
	    this.showError = this.showError.bind(this);
	    this.showMessage = this.showMessage.bind(this);
	  }
	  babelHelpers.createClass(Item, [{
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
	    key: "canDelete",
	    value: function canDelete() {
	      return false;
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      // Just do it.
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      var _this2 = this;
	      this.links = [];
	      if (this.container.hasAttribute('data-link') && main_core.Type.isStringFilled(this.container.getAttribute("data-link"))) {
	        this.links.push(this.container.getAttribute("data-link"));
	      }
	      if (this.container.hasAttribute("data-all-links")) {
	        this.container.getAttribute("data-all-links").split(",").forEach(function (link) {
	          link = String(link).trim();
	          if (main_core.Type.isStringFilled(link)) {
	            _this2.links.push(link);
	          }
	        });
	      }
	      this.makeTextIcons();
	      this.storage = this.container.dataset.storage.split(',');
	    }
	  }, {
	    key: "update",
	    value: function update(_ref) {
	      var link = _ref.link,
	        openInNewPage = _ref.openInNewPage,
	        text = _ref.text;
	      openInNewPage = openInNewPage === 'Y' ? 'Y' : 'N';
	      if (this.container.hasAttribute('data-link')) {
	        this.container.setAttribute('data-link', main_core.Text.encode(link));
	        this.container.setAttribute('data-new-page', openInNewPage);
	      }
	      var linkNode = this.container.querySelector('a');
	      if (linkNode) {
	        linkNode.setAttribute('href', main_core.Text.encode(link));
	        linkNode.setAttribute('target', openInNewPage === 'Y' ? '_blank' : '_self');
	      }
	      this.container.querySelector("[data-role='item-text']").innerHTML = main_core.Text.encode(text);
	      this.init();
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core_events.EventEmitter.unsubscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
	      main_core_events.EventEmitter.decrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, 'onItemDeleteAsFavorites');
	    }
	  }, {
	    key: "getSimilarToUrl",
	    value: function getSimilarToUrl(currentUri) {
	      var result = [];
	      this.links.forEach(function (link, index) {
	        if (areUrlsEqual(link, currentUri)) {
	          result.push({
	            priority: index > 0 ? 0 : 1,
	            // main link is in higher priority
	            url: link
	          });
	        }
	      });
	      return result;
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
	        icon.textContent = getShortName(text.textContent);
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
	        this.container.classList.add('menu-item-with-index');
	      } else {
	        node.innerHTML = '';
	        this.container.classList.remove('menu-item-with-index');
	        if (counterValue < 0)
	          // TODO need to know what it means
	          {
	            var warning = BX('menu-counter-warning-' + this.getId());
	            if (warning) {
	              warning.style.display = 'inline-block';
	            }
	          }
	      }
	      return {
	        oldValue: oldValue,
	        newValue: counterValue
	      };
	    }
	  }, {
	    key: "markAsActive",
	    value: function markAsActive() {
	      console.error('This action is only for the group');
	    }
	  }, {
	    key: "showWarning",
	    value: function showWarning(title, events) {
	      this.removeWarning();
	      var link = this.container.querySelector("a.menu-item-link");
	      if (!link) {
	        return;
	      }
	      title = title ? main_core.Text.encode(title) : '';
	      var node = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<a class=\"menu-post-warn-icon\" title=\"", "\"></a>"])), title);
	      if (events) {
	        Object.keys(events).forEach(function (key) {
	          main_core.Event.bind(node, key, events[key]);
	        });
	      }
	      this.container.classList.add("menu-item-warning-state");
	      link.appendChild(node);
	    }
	  }, {
	    key: "removeWarning",
	    value: function removeWarning() {
	      if (!this.container.classList.contains('menu-item-warning-state')) {
	        return;
	      }
	      this.container.classList.remove('menu-item-warning-state');
	      var node;
	      while (node = this.container.querySelector("a.menu-post-warn-icon")) {
	        node.parentNode.removeChild(node);
	      }
	    }
	  }, {
	    key: "showMessage",
	    value: function showMessage(message) {
	      var _this3 = this;
	      if (this.showMessagePopup) {
	        this.showMessagePopup.close();
	      }
	      this.showMessagePopup = main_popup.PopupManager.create("left-menu-message", this.container, {
	        content: '<div class="left-menu-message-popup">' + message + '</div>',
	        darkMode: true,
	        offsetTop: 2,
	        offsetLeft: 0,
	        angle: true,
	        events: {
	          onClose: function onClose() {
	            _this3.showMessagePopup = null;
	          }
	        },
	        autoHide: true
	      });
	      this.showMessagePopup.show();
	      setTimeout(function () {
	        if (_this3.showMessagePopup) {
	          _this3.showMessagePopup.close();
	        }
	      }, 3000);
	    }
	  }, {
	    key: "showError",
	    value: function showError(response) {
	      var errors = [];
	      if (response.errors) {
	        errors.push(response.errors[0].message);
	      } else if (response instanceof TypeError) {
	        errors.push(response.message);
	      }
	      var message = [main_core.Loc.getMessage("MENU_ERROR_OCCURRED")].concat(errors).join(' ');
	      this.showMessage(message);
	    }
	  }, {
	    key: "getDropDownActions",
	    value: function getDropDownActions() {
	      return [];
	    }
	  }, {
	    key: "getEditFields",
	    value: function getEditFields() {
	      return {
	        id: this.getId(),
	        text: this.getName()
	      };
	    }
	  }, {
	    key: "onDeleteAsFavorites",
	    value: function onDeleteAsFavorites(_ref2) {
	      var data = _ref2.data;
	      if (String(data.id) === String(this.getId())) {
	        if (this.getCode() === 'standard' /* instanceof ItemUserFavorites*/) {
	          main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	            item: this,
	            animate: true
	          });
	        } else {
	          this.storage = babelHelpers.toConsumableArray(this.storage).filter(function (v) {
	            return v !== 'standard';
	          });
	          this.container.dataset.storage = this.storage.join(',');
	        }
	        main_core_events.EventEmitter.unsubscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
	        main_core_events.EventEmitter.decrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
	      }
	    }
	  }], [{
	    key: "detect",
	    value: function detect(node) {
	      return node.getAttribute("data-role") !== 'group' && node.getAttribute("data-type") === this.code;
	    }
	  }, {
	    key: "createNode",
	    value: function createNode(_ref3) {
	      var id = _ref3.id,
	        text = _ref3.text,
	        link = _ref3.link,
	        openInNewPage = _ref3.openInNewPage,
	        counterId = _ref3.counterId,
	        counterValue = _ref3.counterValue,
	        topMenuId = _ref3.topMenuId;
	      id = main_core.Text.encode(id);
	      text = main_core.Text.encode(text);
	      link = main_core.Text.encode(link);
	      counterId = counterId ? main_core.Text.encode(counterId) : '';
	      counterValue = counterValue ? parseInt(counterValue) : 0;
	      openInNewPage = openInNewPage === 'Y' ? 'Y' : 'N';
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<li \n\t\t\tid=\"bx_left_menu_", "\" \n\t\t\tdata-status=\"show\" \n\t\t\tdata-id=\"", "\" \n\t\t\tdata-role=\"item\"\n\t\t\tdata-storage=\"\" \n\t\t\tdata-counter-id=\"", "\" \n\t\t\tdata-link=\"", "\" \n\t\t\tdata-all-links=\"\" \n\t\t\tdata-type=\"", "\" \n\t\t\tdata-delete-perm=\"Y\" \n\t\t\t", "\n\t\t\tdata-new-page=\"", "\" \n\t\t\tclass=\"menu-item-block menu-item-no-icon-state\">\n\t\t\t\t<span class=\"menu-favorites-btn menu-favorites-draggable\">\n\t\t\t\t\t<span class=\"menu-fav-draggable-icon\"></span>\n\t\t\t\t</span>\n\t\t\t\t<a class=\"menu-item-link\" data-slider-ignore-autobinding=\"true\" href=\"", "\" target=\"", "\">\n\t\t\t\t\t<span class=\"menu-item-icon-box\">\n\t\t\t\t\t\t<span class=\"menu-item-icon\">W</span>\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"menu-item-link-text \" data-role=\"item-text\">", "</span>\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t\t<span data-role=\"item-edit-control\" class=\"menu-fav-editable-btn menu-favorites-btn\">\n\t\t\t\t\t<span class=\"menu-favorites-btn-icon\"></span>\n\t\t\t\t</span>\n\t\t\t</li>"])), id, id, counterId, link, this.code, topMenuId ? "data-top-menu-id=\"".concat(main_core.Text.encode(topMenuId), "\"") : "", openInNewPage, link, openInNewPage === 'Y' ? '_blank' : '_self', text, counterId ? "<span class=\"menu-item-index-wrap\">\n\t\t\t\t\t\t<span data-role=\"counter\"\n\t\t\t\t\t\t\tdata-counter-value=\"".concat(counterValue, "\" class=\"menu-item-index\" id=\"menu-counter-").concat(counterId, "\">").concat(counterValue, "</span>\n\t\t\t\t\t</span>") : '');
	    } //region Edition for siblings
	  }, {
	    key: "backendSaveItem",
	    value: function backendSaveItem(itemInfo) {
	      throw 'Function backendSaveItem must be replaced';
	    }
	  }, {
	    key: "showUpdate",
	    value: function showUpdate(item) {
	      var _this4 = this;
	      return new Promise(function (resolve, reject) {
	        _this4.showForm(item.container, item.getEditFields(), resolve, reject);
	      });
	    }
	  }, {
	    key: "checkForm",
	    value: function checkForm(form) {
	      if (String(form.elements["text"].value).trim().length <= 0) {
	        form.elements["text"].classList.add('menu-form-input-error');
	        form.elements["text"].focus();
	        return false;
	      }
	      if (form.elements["link"]) {
	        if (String(form.elements["link"].value).trim().length <= 0 || Utils.refineUrl(form.elements["link"].value).length <= 0) {
	          form.elements["link"].classList.add('menu-form-input-error');
	          form.elements["link"].focus();
	          return false;
	        } else {
	          form.elements["link"].value = Utils.refineUrl(form.elements["link"].value);
	        }
	      }
	      return true;
	    }
	  }, {
	    key: "showForm",
	    value: function showForm(bindElement, itemInfo, resolve, reject) {
	      var _this5 = this;
	      if (this.popup) {
	        this.popup.close();
	      }
	      var isEditMode = itemInfo.id !== '';
	      var form = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n<form name=\"menuAddToFavoriteForm\">\n\t<input type=\"hidden\" name=\"id\" value=\"", "\">\n\t<label for=\"menuPageToFavoriteName\" class=\"menu-form-label\">", "</label>\n\t<input name=\"text\" type=\"text\" id=\"menuPageToFavoriteName\" class=\"menu-form-input\" value=\"", "\" >\n\t", "\n\t", "\n</form>"])), main_core.Text.encode(itemInfo.id || ''), main_core.Loc.getMessage("MENU_ITEM_NAME"), main_core.Text.encode(itemInfo.text || ''), itemInfo['link'] !== undefined ? "<br><br>\n\t<label for=\"menuPageToFavoriteLink\" class=\"menu-form-label\">".concat(main_core.Loc.getMessage("MENU_ITEM_LINK"), "</label>\n\t<input name=\"link\" id=\"menuPageToFavoriteLink\" type=\"text\" class=\"menu-form-input\" value=\"").concat(main_core.Text.encode(itemInfo.link), "\" >") : '', itemInfo['openInNewPage'] !== undefined ? "<br><br>\n\t<input name=\"openInNewPage\" id=\"menuOpenInNewPage\" type=\"checkbox\" value=\"Y\" ".concat(itemInfo.openInNewPage === 'Y' ? 'checked' : '', " >\n\t<label for=\"menuOpenInNewPage\" class=\"menu-form-label\">").concat(main_core.Loc.getMessage("MENU_OPEN_IN_NEW_PAGE"), "</label>") : '');
	      Object.keys(itemInfo).forEach(function (key) {
	        if (['id', 'text', 'link', 'openInNewPage'].indexOf(key) < 0) {
	          var name = main_core.Text.encode(key);
	          var value = main_core.Text.encode(itemInfo[key]);
	          form.appendChild(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "\" value=\"", "\">"])), name, value));
	        }
	      });
	      this.popup = main_popup.PopupManager.create('menu-self-item-popup', bindElement, {
	        className: 'menu-self-item-popup',
	        titleBar: itemInfo['link'] === undefined ? main_core.Loc.getMessage("MENU_RENAME_ITEM") : isEditMode ? main_core.Loc.getMessage("MENU_EDIT_SELF_PAGE") : main_core.Loc.getMessage("MENU_ADD_SELF_PAGE"),
	        offsetTop: 1,
	        offsetLeft: 20,
	        cacheable: false,
	        closeIcon: true,
	        lightShadow: true,
	        draggable: {
	          restrict: true
	        },
	        closeByEsc: true,
	        content: form,
	        buttons: [new ui_buttons.SaveButton({
	          onclick: function onclick() {
	            if (_this5.checkForm(form)) {
	              var itemInfoToSave = {};
	              babelHelpers.toConsumableArray(form.elements).forEach(function (node) {
	                itemInfoToSave[node.name] = node.value;
	              });
	              if (form.elements['openInNewPage']) {
	                itemInfoToSave['openInNewPage'] = form.elements["openInNewPage"].checked ? 'Y' : 'N';
	              }
	              _this5.backendSaveItem(itemInfoToSave).then(function () {
	                resolve(itemInfoToSave);
	                _this5.popup.close();
	              })["catch"](Utils.catchError);
	            }
	          }
	        }), new ui_buttons.CancelButton({
	          onclick: function onclick() {
	            _this5.popup.close();
	          }
	        })]
	      });
	      this.popup.show();
	    } //endregion
	  }]);
	  return Item;
	}();
	babelHelpers.defineProperty(Item, "code", 'abstract');
	function areUrlsEqual(url, currentUri) {
	  var checkedUri = new main_core.Uri(url);
	  var checkedUrlBrief = checkedUri.getPath().replace('/index.php', '').replace('/index.html', '');
	  var currentUrlBrief = currentUri.getPath().replace('/index.php', '').replace('/index.html', '');
	  if (checkedUri.getHost() !== '' && checkedUri.getHost() !== currentUri.getHost()) {
	    return false;
	  }
	  if (currentUrlBrief.indexOf(checkedUrlBrief) !== 0) {
	    return false;
	  }
	  return true;
	}
	function getShortName(name) {
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

	function _classStaticPrivateFieldSpecSet$2(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess$2(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$2(descriptor, "set"); _classApplyDescriptorSet$2(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet$2(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet$2(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess$2(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor$2(descriptor, "get"); return _classApplyDescriptorGet$2(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor$2(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess$2(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet$2(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var ItemUserFavorites = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemUserFavorites, _Item);
	  function ItemUserFavorites() {
	    babelHelpers.classCallCheck(this, ItemUserFavorites);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemUserFavorites).apply(this, arguments));
	  }
	  babelHelpers.createClass(ItemUserFavorites, [{
	    key: "canDelete",
	    value: function canDelete() {
	      return true;
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this = this;
	      Backend.deleteFavoritesItemMenu({
	        id: this.getId(),
	        storage: this.storage
	      }).then(function () {
	        _this.destroy();
	        main_core_events.EventEmitter.emit(_this, Options.eventName('onItemDelete'), {
	          animate: true
	        });
	        var context = _this.getSimilarToUrl(Utils.getCurUri()).length > 0 ? window : {
	          'doesnotmatter': ''
	        };
	        BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [{
	          id: _this.getId()
	        }, _this]);
	        BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	          isActive: false,
	          context: context
	        }]);
	      });
	    }
	  }, {
	    key: "getDropDownActions",
	    value: function getDropDownActions() {
	      var _this2 = this;
	      var contextMenuItems = [];
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_RENAME_ITEM"),
	        onclick: function onclick() {
	          _this2.constructor.showUpdate(_this2).then(_this2.update.bind(_this2))["catch"](_this2.showError);
	        }
	      });
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_REMOVE_STANDARD_ITEM"),
	        onclick: function onclick() {
	          _this2["delete"]();
	        }
	      });
	      if (Options.isAdmin) {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("MENU_ADD_ITEM_TO_ALL"),
	          onclick: function onclick() {
	            var itemLinkNode = _this2.container.querySelector('a');
	            Backend.addAdminSharedItemMenu({
	              id: _this2.getId(),
	              link: _this2.links[0],
	              text: _this2.getName(),
	              counterId: _this2.container.dataset.counterId,
	              openInNewPage: itemLinkNode && itemLinkNode.getAttribute("target") === "_blank" ? "Y" : "N"
	            }).then(function () {
	              _this2.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_ADDED_TO_ALL'));
	              _this2.container.dataset.type = ItemAdminShared.code;
	              _this2.storage.push(ItemUserFavorites.code);
	              _this2.container.dataset.storage = _this2.storage.join(',');
	              main_core_events.EventEmitter.emit(_this2, Options.eventName('onItemConvert'), _this2);
	            })["catch"](_this2.showError);
	          }
	        });
	      }
	      return contextMenuItems;
	    }
	  }], [{
	    key: "backendSaveItem",
	    value: function backendSaveItem(itemInfoToSave) {
	      return Backend.updateFavoritesItemMenu(itemInfoToSave);
	    }
	  }, {
	    key: "getActiveTopMenuItem",
	    value: function getActiveTopMenuItem() {
	      if (_classStaticPrivateFieldSpecGet$2(this, ItemUserFavorites, _currentPageInTopMenu)) {
	        return _classStaticPrivateFieldSpecGet$2(this, ItemUserFavorites, _currentPageInTopMenu);
	      }
	      if (!BX.Main || !BX.Main.interfaceButtonsManager) {
	        return null;
	      }
	      var firstTopMenuInstance = Array.from(Object.values(BX.Main.interfaceButtonsManager.getObjects())).shift();
	      if (firstTopMenuInstance) {
	        var topMenuItem = firstTopMenuInstance.getActive();
	        if (topMenuItem && babelHelpers["typeof"](topMenuItem) === "object") {
	          var link = document.createElement("a");
	          link.href = topMenuItem['URL'];
	          //IE11 omits slash in the pathname
	          var path = link.pathname[0] !== "/" ? "/" + link.pathname : link.pathname;
	          _classStaticPrivateFieldSpecSet$2(this, ItemUserFavorites, _currentPageInTopMenu, {
	            ID: topMenuItem['ID'] || null,
	            NODE: topMenuItem['NODE'] || null,
	            URL: path + link.search,
	            TEXT: topMenuItem['TEXT'],
	            DATA_ID: topMenuItem['DATA_ID'],
	            COUNTER_ID: topMenuItem['COUNTER_ID'],
	            COUNTER: topMenuItem['COUNTER'],
	            SUB_LINK: topMenuItem['SUB_LINK']
	          });
	        }
	      }
	      return _classStaticPrivateFieldSpecGet$2(this, ItemUserFavorites, _currentPageInTopMenu);
	    }
	  }, {
	    key: "isCurrentPageStandard",
	    value: function isCurrentPageStandard(topMenuPoint) {
	      if (topMenuPoint && topMenuPoint['URL']) {
	        var currentFullPath = document.location.pathname + document.location.search;
	        return topMenuPoint.URL === currentFullPath && topMenuPoint.URL.indexOf('workgroups') < 0;
	      }
	      return false;
	    }
	  }, {
	    key: "saveCurrentPage",
	    value: function saveCurrentPage(_ref) {
	      var _this3 = this;
	      var pageTitle = _ref.pageTitle,
	        pageLink = _ref.pageLink;
	      var topMenuPoint = this.getActiveTopMenuItem();
	      var itemInfo, startX, startY;
	      if (topMenuPoint && topMenuPoint.NODE && this.isCurrentPageStandard(topMenuPoint) && (pageLink === Utils.getCurPage() || pageLink === topMenuPoint.URL || !pageLink)) {
	        var menuNodeCoord = topMenuPoint.NODE.getBoundingClientRect();
	        startX = menuNodeCoord.left;
	        startY = menuNodeCoord.top;
	        itemInfo = {
	          id: topMenuPoint.DATA_ID,
	          text: pageTitle || topMenuPoint.TEXT,
	          link: Utils.getCurPage() || topMenuPoint.URL,
	          counterId: topMenuPoint.COUNTER_ID,
	          counterValue: topMenuPoint.COUNTER,
	          isStandardItem: true,
	          subLink: topMenuPoint.SUB_LINK
	        };
	      } else {
	        itemInfo = {
	          text: pageTitle || document.getElementById('pagetitle').innerText,
	          link: pageLink || Utils.getCurPage(),
	          isStandardItem: pageLink === Utils.getCurPage()
	        };
	        var titleCoord = BX("pagetitle").getBoundingClientRect();
	        startX = titleCoord.left;
	        startY = titleCoord.top;
	      }
	      return Backend.addFavoritesItemMenu(itemInfo).then(function (_ref2) {
	        var itemId = _ref2.data.itemId;
	        itemInfo.id = itemId;
	        itemInfo.topMenuId = itemInfo.id;
	        return {
	          node: _this3.createNode(itemInfo),
	          animateFromPoint: {
	            startX: startX,
	            startY: startY
	          },
	          itemInfo: itemInfo
	        };
	      });
	    }
	  }, {
	    key: "deleteCurrentPage",
	    value: function deleteCurrentPage(_ref3) {
	      var pageLink = _ref3.pageLink;
	      var topPoint = this.getActiveTopMenuItem();
	      var itemInfo = {},
	        startX,
	        startY;
	      if (topPoint && this.isCurrentPageStandard(topPoint)) {
	        itemInfo['id'] = topPoint.DATA_ID;
	        var menuNodeCoord = topPoint.NODE.getBoundingClientRect();
	        startX = menuNodeCoord.left;
	        startY = menuNodeCoord.top;
	      } else {
	        itemInfo['link'] = pageLink || Utils.getCurPage();
	        var titleCoord = BX("pagetitle").getBoundingClientRect();
	        startX = titleCoord.left;
	        startY = titleCoord.top;
	      }
	      return Backend.deleteFavoritesItemMenu(itemInfo).then(function (_ref4) {
	        var data = _ref4.data;
	        if (!itemInfo.id && data && data['itemId']) {
	          itemInfo.id = data['itemId'];
	        }
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {
	          id: itemInfo.id
	        });
	        return {
	          itemInfo: itemInfo,
	          animateToPoint: {
	            startX: startX,
	            startY: startY
	          }
	        };
	      });
	    }
	  }, {
	    key: "saveStandardPage",
	    value: function saveStandardPage(_ref5) {
	      var _this4 = this;
	      var DATA_ID = _ref5.DATA_ID,
	        TEXT = _ref5.TEXT,
	        SUB_LINK = _ref5.SUB_LINK,
	        COUNTER_ID = _ref5.COUNTER_ID,
	        COUNTER = _ref5.COUNTER,
	        NODE = _ref5.NODE,
	        URL = _ref5.URL;
	      var itemInfo = {
	        id: DATA_ID,
	        text: TEXT,
	        link: URL,
	        subLink: SUB_LINK,
	        counterId: COUNTER_ID,
	        counterValue: COUNTER
	      };
	      var pos = NODE.getBoundingClientRect();
	      var startX = pos.left;
	      var startY = pos.top;
	      return Backend.addFavoritesItemMenu(itemInfo).then(function (_ref6) {
	        var itemId = _ref6.data.itemId;
	        itemInfo.id = itemId;
	        itemInfo.topMenuId = itemInfo.id;
	        var topPoint = _this4.getActiveTopMenuItem();
	        BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, _this4]);
	        BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	          isActive: true,
	          context: topPoint && topPoint.DATA_ID === DATA_ID ? window : null
	        }]);
	        return {
	          node: _this4.createNode(itemInfo),
	          animateFromPoint: {
	            startX: startX,
	            startY: startY
	          }
	        };
	      });
	    }
	  }, {
	    key: "deleteStandardPage",
	    value: function deleteStandardPage(_ref7) {
	      var _this5 = this;
	      var DATA_ID = _ref7.DATA_ID;
	      var itemInfo = {
	        id: DATA_ID
	      };
	      return Backend.deleteFavoritesItemMenu(itemInfo).then(function () {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {
	          id: itemInfo.id
	        });
	        BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [itemInfo, _this5]);
	        BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	          isActive: false
	        }]);
	        return {
	          itemInfo: itemInfo
	        };
	      });
	    }
	  }]);
	  return ItemUserFavorites;
	}(Item);
	babelHelpers.defineProperty(ItemUserFavorites, "code", 'standard');
	var _currentPageInTopMenu = {
	  writable: true,
	  value: null
	};

	var ItemUserSelf = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemUserSelf, _Item);
	  function ItemUserSelf() {
	    babelHelpers.classCallCheck(this, ItemUserSelf);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemUserSelf).apply(this, arguments));
	  }
	  babelHelpers.createClass(ItemUserSelf, [{
	    key: "canDelete",
	    value: function canDelete() {
	      return true;
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this = this;
	      return Backend.deleteSelfITem(this.getId()).then(function () {
	        if (_this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	          Backend.deleteFavoritesItemMenu({
	            id: _this.getId()
	          });
	        }
	        main_core_events.EventEmitter.emit(_this, Options.eventName('onItemDelete'), {
	          animate: true
	        });
	      })["catch"](this.showError);
	    }
	  }, {
	    key: "getDropDownActions",
	    value: function getDropDownActions() {
	      var _this2 = this;
	      var contextMenuItems = [];
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_EDIT_ITEM"),
	        onclick: function onclick() {
	          _this2.constructor.showUpdate(_this2).then(_this2.update.bind(_this2))["catch"](_this2.showError);
	        }
	      });
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM'),
	        onclick: function onclick() {
	          ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM_CONFIRM'), main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM'), function (messageBox) {
	            _this2["delete"]();
	            messageBox.close();
	          }, main_core.Loc.getMessage('MENU_DELETE'));
	        }
	      });
	      if (Options.isAdmin) {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("MENU_ADD_ITEM_TO_ALL"),
	          onclick: function onclick() {
	            var itemLinkNode = _this2.container.querySelector('a');
	            Backend.addAdminSharedItemMenu({
	              id: _this2.getId(),
	              link: _this2.links[0],
	              text: _this2.getName(),
	              counterId: _this2.container.dataset.counterId,
	              openInNewPage: itemLinkNode && itemLinkNode.getAttribute("target") === "_blank" ? "Y" : "N"
	            }).then(function () {
	              _this2.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_ADDED_TO_ALL'));
	              _this2.container.dataset.type = ItemAdminShared.code;
	              _this2.storage.push(ItemUserSelf.code);
	              _this2.container.dataset.storage = _this2.storage.join(',');
	              main_core_events.EventEmitter.emit(_this2, Options.eventName('onItemConvert'), _this2);
	            })["catch"](_this2.showError);
	          }
	        });
	      }
	      return contextMenuItems;
	    }
	  }, {
	    key: "getEditFields",
	    value: function getEditFields() {
	      return {
	        id: this.getId(),
	        text: this.getName(),
	        link: this.links[0],
	        openInNewPage: this.container.getAttribute('data-new-page')
	      };
	    }
	  }], [{
	    key: "backendSaveItem",
	    value: function backendSaveItem(itemInfo) {
	      return Backend.saveSelfItemMenu(itemInfo).then(function (_ref) {
	        var data = _ref.data;
	        if (data && data['itemId']) {
	          itemInfo.id = data['itemId'];
	        }
	        return itemInfo;
	      });
	    }
	  }, {
	    key: "showAdd",
	    value: function showAdd(bindElement) {
	      var _this3 = this;
	      return new Promise(function (resolve1, reject2) {
	        _this3.showForm(bindElement, {
	          id: 0,
	          name: '',
	          link: '',
	          openInNewPage: 'Y'
	        }, resolve1, reject2);
	      }).then(function (itemInfo) {
	        return {
	          node: _this3.createNode(itemInfo)
	        };
	      });
	    }
	  }]);
	  return ItemUserSelf;
	}(Item);
	babelHelpers.defineProperty(ItemUserSelf, "code", 'self');

	var ItemAdminShared = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemAdminShared, _Item);
	  function ItemAdminShared() {
	    babelHelpers.classCallCheck(this, ItemAdminShared);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemAdminShared).apply(this, arguments));
	  }
	  babelHelpers.createClass(ItemAdminShared, [{
	    key: "canDelete",
	    value: function canDelete() {
	      return this.container.dataset.deletePerm === 'Y';
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this = this;
	      Backend.deleteAdminSharedItemMenu(this.getId()).then(function () {
	        if (_this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	          Backend.deleteFavoritesItemMenu({
	            id: _this.getId()
	          });
	        }
	        if (_this.storage.indexOf(ItemUserSelf.code) >= 0) {
	          Backend.deleteSelfITem(_this.getId());
	        }
	        main_core_events.EventEmitter.emit(_this, Options.eventName('onItemDelete'), {
	          animate: true
	        });
	      })["catch"](this.showError);
	    }
	  }, {
	    key: "getDropDownActions",
	    value: function getDropDownActions() {
	      var _this2 = this;
	      if (!this.canDelete()) {
	        return [];
	      }
	      var contextMenuItems = [];
	      /*		contextMenuItems.push({
	      			text: Loc.getMessage("MENU_RENAME_ITEM"),
	      			onclick: () => {
	      				this.constructor
	      					.showUpdate(this)
	      					.then(this.update.bind(this))
	      					.catch(this.showError.bind(this));
	      			}
	      		});
	      */

	      if (this.storage.filter(function (value) {
	        return value === ItemUserFavorites.code || value === ItemUserSelf.code;
	      }).length > 0) {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage('MENU_REMOVE_STANDARD_ITEM'),
	          onclick: this["delete"].bind(this)
	        });
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage('MENU_DELETE_CUSTOM_ITEM_FROM_ALL'),
	          onclick: function onclick() {
	            Backend.deleteAdminSharedItemMenu(_this2.getId()).then(function () {
	              _this2.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_DELETED_FROM_ALL'));
	              var codeToConvert = _this2.storage.indexOf(ItemUserSelf.code) >= 0 ? ItemUserSelf.code : ItemUserFavorites.code;
	              _this2.container.dataset.type = codeToConvert;
	              _this2.container.dataset.storage = _this2.storage.filter(function (v) {
	                return v !== codeToConvert;
	              }).join(',');
	              main_core_events.EventEmitter.emit(_this2, Options.eventName('onItemConvert'), _this2);
	            })["catch"](_this2.showError);
	          }
	        });
	      } else {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("MENU_DELETE_CUSTOM_ITEM_FROM_ALL"),
	          onclick: this["delete"].bind(this)
	        });
	      }
	      return contextMenuItems;
	    }
	  }]);
	  return ItemAdminShared;
	}(Item);
	babelHelpers.defineProperty(ItemAdminShared, "code", 'admin');

	var ItemAdminCustom = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemAdminCustom, _Item);
	  function ItemAdminCustom() {
	    babelHelpers.classCallCheck(this, ItemAdminCustom);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemAdminCustom).apply(this, arguments));
	  }
	  babelHelpers.createClass(ItemAdminCustom, [{
	    key: "canDelete",
	    value: function canDelete() {
	      return this.container.dataset.deletePerm === 'Y';
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this = this;
	      if (this.canDelete()) {
	        Backend.deleteCustomItem(this.getId()).then(function () {
	          if (_this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	            Backend.deleteFavoritesItemMenu({
	              id: _this.getId()
	            });
	          }
	          main_core_events.EventEmitter.emit(_this, Options.eventName('onItemDelete'), {
	            animate: true
	          });
	        })["catch"](this.showError);
	      }
	    }
	  }, {
	    key: "getDropDownActions",
	    value: function getDropDownActions() {
	      var actions = [];
	      if (this.canDelete()) {
	        actions.push({
	          text: main_core.Loc.getMessage("MENU_DELETE_ITEM_FROM_ALL"),
	          onclick: this["delete"].bind(this)
	        });
	      }
	      return actions;
	    }
	  }]);
	  return ItemAdminCustom;
	}(Item);
	babelHelpers.defineProperty(ItemAdminCustom, "code", 'custom');

	var ItemSystem = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemSystem, _Item);
	  function ItemSystem() {
	    babelHelpers.classCallCheck(this, ItemSystem);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemSystem).apply(this, arguments));
	  }
	  babelHelpers.createClass(ItemSystem, [{
	    key: "canDelete",
	    value: function canDelete() {
	      return false;
	    }
	  }]);
	  return ItemSystem;
	}(Item);
	babelHelpers.defineProperty(ItemSystem, "code", 'default');

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _collapsingAnimation = /*#__PURE__*/new WeakMap();
	var ItemGroup = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemGroup, _Item);
	  function ItemGroup() {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemGroup);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemGroup).apply(this, arguments));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _collapsingAnimation, {
	      writable: true,
	      value: void 0
	    });
	    _this.container.addEventListener('click', _this.toggleAndSave.bind(babelHelpers.assertThisInitialized(_this)), true);
	    _this.container.addEventListener('mouseleave', function () {
	      main_core.Dom.removeClass(_this.container, 'menu-item-group-actioned');
	    });
	    _this.groupContainer = _this.container.parentNode.querySelector("[data-group-id=\"".concat(_this.getId(), "\"]"));
	    if (_this.container.getAttribute('data-collapse-mode') === 'collapsed') {
	      _this.groupContainer.style.display = 'none';
	    }
	    setTimeout(function () {
	      _this.updateCounter();
	    }, 0);
	    return _this;
	  }
	  babelHelpers.createClass(ItemGroup, [{
	    key: "toggleAndSave",
	    value: function toggleAndSave(event) {
	      var _this2 = this;
	      event.preventDefault();
	      event.stopPropagation();
	      if (this.container.getAttribute('data-collapse-mode') === 'collapsed') {
	        Backend.expandGroup(this.getId());
	        this.expand().then(function () {
	          _this2.container.setAttribute('data-collapse-mode', 'expanded');
	        });
	      } else {
	        Backend.collapseGroup(this.getId());
	        this.collapse().then(function () {
	          _this2.container.setAttribute('data-collapse-mode', 'collapsed');
	        });
	      }
	      return false;
	    }
	  }, {
	    key: "checkAndCorrect",
	    value: function checkAndCorrect() {
	      var _this3 = this;
	      var groupContainer = this.groupContainer;
	      if (groupContainer.parentNode === this.container) {
	        main_core.Dom.insertAfter(groupContainer, this.container);
	      }
	      babelHelpers.toConsumableArray(groupContainer.querySelectorAll(".menu-item-block")).forEach(function (node) {
	        node.setAttribute('data-status', _this3.container.getAttribute("data-status"));
	      });
	      return this;
	    }
	  }, {
	    key: "collapse",
	    value: function collapse(hideGroupContainer) {
	      var _this4 = this;
	      return new Promise(function (resolve) {
	        var groupContainer = _this4.groupContainer;
	        if (babelHelpers.classPrivateFieldGet(_this4, _collapsingAnimation)) {
	          babelHelpers.classPrivateFieldGet(_this4, _collapsingAnimation).stop();
	        }
	        groupContainer.style.overflow = 'hidden';
	        main_core.Dom.addClass(_this4.container, 'menu-item-group-collapsing');
	        main_core.Dom.addClass(_this4.container, 'menu-item-group-actioned');
	        main_core.Dom.addClass(groupContainer, 'menu-item-group-collapsing');
	        var slideParams = {
	          height: groupContainer.offsetHeight,
	          display: groupContainer.style.display
	        };
	        babelHelpers.classPrivateFieldSet(_this4, _collapsingAnimation, new BX.easing({
	          duration: 500,
	          start: {
	            height: slideParams.height,
	            opacity: 100
	          },
	          finish: {
	            height: 0,
	            opacity: 0
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            groupContainer.style.height = state.height + 'px';
	            groupContainer.style.opacity = state.opacity / 100;
	          },
	          complete: function complete() {
	            groupContainer.style.display = 'none';
	            groupContainer.style.opacity = 'auto';
	            groupContainer.style.height = 'auto';
	            if (_this4.container.getAttribute('data-contains-active-item') === 'Y') {
	              main_core.Dom.addClass(_this4.container, 'menu-item-active');
	            }
	            main_core.Dom.removeClass(_this4.container, 'menu-item-group-collapsing');
	            main_core.Dom.removeClass(groupContainer, 'menu-item-group-collapsing');
	            babelHelpers.classPrivateFieldSet(_this4, _collapsingAnimation, null);
	            if (hideGroupContainer === true) {
	              _this4.container.appendChild(groupContainer);
	            }
	            resolve();
	          }
	        }));
	        babelHelpers.classPrivateFieldGet(_this4, _collapsingAnimation).animate();
	      });
	    }
	  }, {
	    key: "expand",
	    value: function expand(checkAttribute) {
	      var _this5 = this;
	      return new Promise(function (resolve) {
	        var container = _this5.container;
	        var groupContainer = _this5.groupContainer;
	        if (checkAttribute === true && container.getAttribute('data-collapse-mode') === 'collapsed') {
	          return resolve();
	        }
	        var contentHeight = groupContainer.querySelectorAll('li').length * container.offsetHeight;
	        main_core.Dom.addClass(container, 'menu-item-group-expanding');
	        main_core.Dom.addClass(container, 'menu-item-group-actioned');
	        main_core.Dom.addClass(groupContainer, 'menu-item-group-expanding');
	        if (groupContainer.parentNode === _this5.container) {
	          main_core.Dom.insertAfter(groupContainer, _this5.container);
	        }
	        groupContainer.style.display = 'block';
	        babelHelpers.classPrivateFieldSet(_this5, _collapsingAnimation, new BX.easing({
	          duration: 500,
	          start: {
	            height: 0,
	            opacity: 0
	          },
	          finish: {
	            height: contentHeight,
	            opacity: 100
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            groupContainer.style.height = state.height + 'px';
	            groupContainer.style.opacity = state.opacity / 100;
	          },
	          complete: function complete() {
	            main_core.Dom.removeClass(container, 'menu-item-group-expanding menu-item-active');
	            main_core.Dom.removeClass(groupContainer, 'menu-item-group-expanding');
	            groupContainer.style.height = 'auto';
	            groupContainer.style.opacity = 'auto';
	            resolve();
	          }
	        }));
	        babelHelpers.classPrivateFieldGet(_this5, _collapsingAnimation).animate();
	      });
	    }
	  }, {
	    key: "canDelete",
	    value: function canDelete() {
	      return false;
	    }
	  }, {
	    key: "updateCounter",
	    value: function updateCounter() {
	      var counterValue = 0;
	      babelHelpers.toConsumableArray(this.container.parentNode.querySelector("[data-group-id=\"".concat(this.getId(), "\"]")).querySelectorAll('[data-role="counter"]')).forEach(function (node) {
	        counterValue += parseInt(node.dataset.counterValue);
	      });
	      var node = this.container.querySelector('[data-role="counter"]');
	      if (counterValue > 0) {
	        node.innerHTML = counterValue > 99 ? '99+' : counterValue;
	        this.container.classList.add('menu-item-with-index');
	      } else {
	        node.innerHTML = '';
	        this.container.classList.remove('menu-item-with-index');
	      }
	    }
	  }, {
	    key: "markAsActive",
	    value: function markAsActive() {
	      this.container.setAttribute('data-contains-active-item', 'Y');
	      if (this.container.getAttribute('data-collapse-mode') === 'collapsed') main_core.Dom.addClass(this.container, 'menu-item-active');
	    }
	  }, {
	    key: "markAsInactive",
	    value: function markAsInactive() {
	      this.container.removeAttribute('data-contains-active-item');
	      main_core.Dom.removeClass(this.container, 'menu-item-active');
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.container.getAttribute('data-contains-active-item') === 'Y';
	    }
	  }], [{
	    key: "detect",
	    value: function detect(node) {
	      return node.getAttribute("data-role") === 'group' && node.getAttribute("data-type") === this.code;
	    }
	  }]);
	  return ItemGroup;
	}(Item);
	babelHelpers.defineProperty(ItemGroup, "code", 'group');

	var ItemGroupSystem = /*#__PURE__*/function (_ItemGroup) {
	  babelHelpers.inherits(ItemGroupSystem, _ItemGroup);
	  function ItemGroupSystem() {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemGroupSystem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemGroupSystem).apply(this, arguments));
	    _this.container.querySelector('[data-role="item-edit-control"]').style.display = 'none';
	    return _this;
	  }
	  return ItemGroupSystem;
	}(ItemGroup);
	babelHelpers.defineProperty(ItemGroupSystem, "code", 'system_group');

	var itemMappings = [Item, ItemAdminShared, ItemUserFavorites, ItemAdminCustom, ItemUserSelf, ItemSystem, ItemGroup, ItemGroupSystem];
	function getItem(itemData) {
	  var itemClassName = Item;
	  itemMappings.forEach(function (itemClass) {
	    if (itemClass.detect(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return itemClassName;
	}

	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _link = /*#__PURE__*/new WeakMap();
	var _actualLink = /*#__PURE__*/new WeakMap();
	var ItemActive = /*#__PURE__*/function () {
	  function ItemActive() {
	    babelHelpers.classCallCheck(this, ItemActive);
	    _classPrivateFieldInitSpec$2(this, _link, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _actualLink, {
	      writable: true,
	      value: void 0
	    });
	    this.highlight = main_core.Runtime.debounce(this.highlight, 200, this);
	    babelHelpers.classPrivateFieldSet(this, _actualLink, new main_core.Uri(window.location.href));
	  }
	  babelHelpers.createClass(ItemActive, [{
	    key: "checkAndSet",
	    value: function checkAndSet(item, links) {
	      var _this = this;
	      /*
	      Custom items have more priority than standard items.
	      Example:
	      	Calendar (standard item)
	      		data-link="/company/personal/user/1/calendar/"
	      		data-all-links="/company/personal/user/1/calendar/,/calendar/
	      		Company Calendar (custom item)
	      		 data-link="/calendar/"
	      	We've got two items with the identical link /calendar/'.
	      */
	      if (item === this.item) {
	        return false;
	      }
	      var theMostOfTheLinks = babelHelpers.classPrivateFieldGet(this, _link);
	      links.forEach(function (link) {
	        var linkUri = new main_core.Uri(link.url);
	        var changeActiveItem = false;
	        if (!theMostOfTheLinks || theMostOfTheLinks.uri.getPath().length < linkUri.getPath().length) {
	          changeActiveItem = true;
	        } else if (theMostOfTheLinks.uri.getPath().length === linkUri.getPath().length) {
	          var actualParams = babelHelpers.classPrivateFieldGet(_this, _actualLink).getQueryParams();
	          var maxCount = Object.keys(actualParams).length;
	          var theMostOfTheLinkServiceData = {
	            params: theMostOfTheLinks.uri.getQueryParams(),
	            mismatches: maxCount
	          };
	          var comparedLinkParams = {
	            params: linkUri.getQueryParams(),
	            mismatches: maxCount
	          };
	          Array.from(Object.keys(actualParams)).forEach(function (key) {
	            if (String(actualParams[key]) === String(theMostOfTheLinkServiceData.params[key])) {
	              theMostOfTheLinkServiceData.mismatches--;
	            }
	            if (String(actualParams[key]) === String(comparedLinkParams.params[key])) {
	              comparedLinkParams.mismatches--;
	            }
	          });
	          if (link.priority > 0 && item instanceof ItemSystem) {
	            link.priority += 1;
	          }
	          if (theMostOfTheLinkServiceData.mismatches > comparedLinkParams.mismatches || theMostOfTheLinks.priority < link.priority) {
	            changeActiveItem = true;
	          }
	        }
	        if (changeActiveItem) {
	          theMostOfTheLinks = {
	            priority: link.priority,
	            url: link.url,
	            uri: linkUri
	          };
	        }
	      });
	      if (theMostOfTheLinks !== babelHelpers.classPrivateFieldGet(this, _link)) {
	        if (this.item) {
	          this.unhighlight(this.item);
	        }
	        babelHelpers.classPrivateFieldSet(this, _link, theMostOfTheLinks);
	        this.item = item;
	        this.highlight();
	        return true;
	      }
	      return false;
	    }
	  }, {
	    key: "checkAndUnset",
	    value: function checkAndUnset(item) {
	      if (item instanceof Item && item === this.item) {
	        this.unhighlight(this.item);
	        this.item = null;
	        babelHelpers.classPrivateFieldSet(this, _link, null);
	      }
	    }
	  }, {
	    key: "highlight",
	    value: function highlight() {
	      if (this.item) {
	        this.item.container.classList.add('menu-item-active');
	        var parent = this.item.container.closest('[data-role="group-content"]');
	        var parentContainer;
	        while (parent) {
	          parentContainer = parent.parentNode.querySelector("[data-id=\"".concat(parent.getAttribute('data-group-id'), "\"]"));
	          if (parentContainer) {
	            parentContainer.setAttribute('data-contains-active-item', 'Y');
	            if (parentContainer.getAttribute('data-collapse-mode') === 'collapsed') {
	              parentContainer.classList.add('menu-item-active');
	            }
	          }
	          parent = parent.closest('[data-relo="group-content"]');
	        }
	      }
	    }
	  }, {
	    key: "unhighlight",
	    value: function unhighlight(item) {
	      if (!(item instanceof Item)) {
	        item = this.item;
	      }
	      if (item instanceof Item) {
	        item.container.classList.remove('menu-item-active');
	        var parent = item.container.closest('[data-role="group-content"]');
	        var parentContainer;
	        while (parent) {
	          parentContainer = parent.parentNode.querySelector("[data-id=\"".concat(parent.getAttribute('data-group-id'), "\"]"));
	          if (parentContainer) {
	            parentContainer.removeAttribute('data-contains-active-item');
	            parentContainer.classList.remove('menu-item-active');
	          }
	          parent = parent.closest('[data-relo="group-content"]');
	        }
	        return item;
	      }
	      return null;
	    }
	  }]);
	  return ItemActive;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _activeItem = /*#__PURE__*/new WeakMap();
	var _isEditMode = /*#__PURE__*/new WeakMap();
	var _showHiddenContainer = /*#__PURE__*/new WeakSet();
	var _hideHiddenContainer = /*#__PURE__*/new WeakSet();
	var _animation = /*#__PURE__*/new WeakSet();
	var _recalculateCounters = /*#__PURE__*/new WeakSet();
	var _refreshActivity = /*#__PURE__*/new WeakSet();
	var _updateCountersLastValue = /*#__PURE__*/new WeakMap();
	var _getItemsByCounterId = /*#__PURE__*/new WeakSet();
	var _getItemsToSave = /*#__PURE__*/new WeakSet();
	var _saveItemsSort = /*#__PURE__*/new WeakSet();
	var _getParentItemFor = /*#__PURE__*/new WeakSet();
	var _canChangePaternity = /*#__PURE__*/new WeakSet();
	var _openItemMenuPopup = /*#__PURE__*/new WeakMap();
	var _animateTopItemToLeft = /*#__PURE__*/new WeakSet();
	var _animateTopItemFromLeft = /*#__PURE__*/new WeakSet();
	var _registerDND = /*#__PURE__*/new WeakSet();
	var _menuItemDragStart = /*#__PURE__*/new WeakSet();
	var _menuItemDragMove = /*#__PURE__*/new WeakSet();
	var _menuItemDragHover = /*#__PURE__*/new WeakSet();
	var _menuItemDragStop = /*#__PURE__*/new WeakSet();
	var ItemsController = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(ItemsController, _DefaultController);
	  function ItemsController(container, _ref) {
	    var _this;
	    var events = _ref.events;
	    babelHelpers.classCallCheck(this, ItemsController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemsController).call(this, container, {
	      events: events
	    }));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _menuItemDragStop);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _menuItemDragHover);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _menuItemDragMove);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _menuItemDragStart);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _registerDND);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _animateTopItemFromLeft);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _animateTopItemToLeft);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _canChangePaternity);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getParentItemFor);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _saveItemsSort);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getItemsToSave);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getItemsByCounterId);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _refreshActivity);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _recalculateCounters);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _animation);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _hideHiddenContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _showHiddenContainer);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "items", new Map());
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _activeItem, {
	      writable: true,
	      value: new ItemActive()
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _isEditMode, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _updateCountersLastValue, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _openItemMenuPopup, {
	      writable: true,
	      value: void 0
	    });
	    _this.parentContainer = container;
	    _this.container = container.querySelector(".menu-items");
	    _this.hiddenContainer = container.querySelector('#left-menu-hidden-items-block');
	    container.querySelectorAll('li.menu-item-block').forEach(_this.registerItem.bind(babelHelpers.assertThisInitialized(_this)));
	    container.querySelector('#left-menu-hidden-separator').addEventListener('click', _this.toggleHiddenContainer.bind(babelHelpers.assertThisInitialized(_this)));
	    if (_this.getActiveItem() && _this.getActiveItem().container.getAttribute('data-status') === 'hide') {
	      _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _showHiddenContainer, _showHiddenContainer2).call(babelHelpers.assertThisInitialized(_this), true);
	    }
	    return _this;
	  }
	  babelHelpers.createClass(ItemsController, [{
	    key: "registerItem",
	    value: function registerItem(node) {
	      var _this2 = this;
	      var itemClass = getItem(node);
	      var item = new itemClass(this.container, node);
	      this.items.set(item.getId(), item);
	      _classPrivateMethodGet(this, _registerDND, _registerDND2).call(this, item);
	      if (babelHelpers.classPrivateFieldGet(this, _activeItem).checkAndSet(item, item.getSimilarToUrl(Utils.getCurUri())) === true) {
	        var parentItem = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	        while (parentItem) {
	          parentItem.markAsActive();
	          parentItem = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, parentItem);
	        }
	      }
	      main_core_events.EventEmitter.subscribe(item, Options.eventName('onItemDelete'), function (_ref2) {
	        var data = _ref2.data;
	        _this2.deleteItem(item, data);
	      });
	      main_core_events.EventEmitter.subscribe(item, Options.eventName('onItemConvert'), function (_ref3) {
	        var data = _ref3.data;
	        _this2.convertItem(item, data);
	      });
	      babelHelpers.toConsumableArray(item.container.querySelectorAll('a')).forEach(function (node) {
	        node.addEventListener('click', function (event) {
	          if (babelHelpers.classPrivateFieldGet(_this2, _isEditMode) === true) {
	            event.preventDefault();
	            event.stopPropagation();
	            return false;
	          }
	        }, true);
	      });
	      item.container.querySelector('[data-role="item-edit-control"]').addEventListener('click', function (event) {
	        _this2.openItemMenu(item, event.target);
	      });
	      return item;
	    }
	  }, {
	    key: "unregisterItem",
	    value: function unregisterItem(item) {
	      if (!this.items.has(item.getId())) {
	        return;
	      }
	      this.items["delete"](item.getId());
	      babelHelpers.classPrivateFieldGet(this, _activeItem).checkAndUnset(item, item.getSimilarToUrl(Utils.getCurUri()));
	      main_core_events.EventEmitter.unsubscribeAll(item, Options.eventName('onItemDelete'));
	      main_core_events.EventEmitter.unsubscribeAll(item, Options.eventName('onItemConvert'));
	      item.container.parentNode.replaceChild(item.container.cloneNode(true), item.container);
	    }
	  }, {
	    key: "switchToEditMode",
	    value: function switchToEditMode() {
	      if (babelHelpers.classPrivateFieldGet(this, _isEditMode)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _isEditMode, true);
	      main_core_events.EventEmitter.emit(this, Options.eventName('EditMode'));
	    }
	  }, {
	    key: "switchToViewMode",
	    value: function switchToViewMode() {
	      if (!babelHelpers.classPrivateFieldGet(this, _isEditMode)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _isEditMode, false);
	      main_core_events.EventEmitter.emit(this, Options.eventName('ViewMode'));
	    }
	  }, {
	    key: "isHiddenContainerVisible",
	    value: function isHiddenContainerVisible() {
	      return this.hiddenContainer.classList.contains('menu-item-favorites-more-open');
	    }
	  }, {
	    key: "toggleHiddenContainer",
	    value: function toggleHiddenContainer(animate) {
	      if (this.hiddenContainer.classList.contains('menu-item-favorites-more-open')) {
	        _classPrivateMethodGet(this, _hideHiddenContainer, _hideHiddenContainer2).call(this, animate);
	      } else {
	        _classPrivateMethodGet(this, _showHiddenContainer, _showHiddenContainer2).call(this, animate);
	      }
	    }
	  }, {
	    key: "setItemAsAMainPage",
	    value: function setItemAsAMainPage(item) {
	      var _this3 = this;
	      var node = item.container;
	      node.setAttribute("data-status", "show");
	      var startTop = node.offsetTop;
	      var dragElement = main_core.Dom.create("div", {
	        attrs: {
	          className: "menu-draggable-wrap"
	        },
	        style: {
	          top: startTop
	        }
	      });
	      var insertBeforeElement = node.nextElementSibling;
	      if (insertBeforeElement) {
	        node.parentNode.insertBefore(dragElement, insertBeforeElement);
	      } else {
	        node.parentNode.appendChild(dragElement);
	      }
	      dragElement.appendChild(node);
	      main_core.Dom.addClass(node, "menu-item-draggable");
	      new BX.easing({
	        duration: 500,
	        start: {
	          top: startTop
	        },
	        finish: {
	          top: 0
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: function step(state) {
	          dragElement.style.top = state.top + "px";
	        },
	        complete: function complete() {
	          _this3.container.insertBefore(node, BX("left-menu-empty-item").nextSibling);
	          main_core.Dom.removeClass(node, "menu-item-draggable");
	          main_core.Dom.remove(dragElement);
	          _classPrivateMethodGet(_this3, _saveItemsSort, _saveItemsSort2).call(_this3, {
	            action: 'mainPageIsSet',
	            itemId: item.getId()
	          });
	        }
	      }).animate();
	    }
	  }, {
	    key: "showItem",
	    value: function showItem(item) {
	      var oldParent = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	      var container = this.container;
	      item.container.setAttribute('data-status', 'show');
	      if (_classPrivateMethodGet(this, _canChangePaternity, _canChangePaternity2).call(this, item)) {
	        container.appendChild(item.container);
	        _classPrivateMethodGet(this, _refreshActivity, _refreshActivity2).call(this, item, oldParent);
	      } else if (oldParent) {
	        container.appendChild(oldParent.container);
	        oldParent.container.setAttribute('data-status', 'show');
	        container.appendChild(oldParent.groupContainer);
	      }
	      if (this.hiddenContainer.querySelector('.menu-item-block') === null) {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
	        _classPrivateMethodGet(this, _hideHiddenContainer, _hideHiddenContainer2).call(this, false);
	      }
	      _classPrivateMethodGet(this, _recalculateCounters, _recalculateCounters2).call(this, item);
	      _classPrivateMethodGet(this, _saveItemsSort, _saveItemsSort2).call(this, {
	        action: 'showItem',
	        itemId: item.getId()
	      });
	    }
	  }, {
	    key: "hideItem",
	    value: function hideItem(item) {
	      var oldParent = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	      var container = this.hiddenContainer.querySelector('#left-menu-hidden-items-list');
	      var emitEvent = container.querySelector('.menu-item-block') === null;
	      item.container.setAttribute('data-status', 'hide');
	      if (_classPrivateMethodGet(this, _canChangePaternity, _canChangePaternity2).call(this, item)) {
	        container.appendChild(item.container);
	        _classPrivateMethodGet(this, _refreshActivity, _refreshActivity2).call(this, item, oldParent);
	      } else if (oldParent) {
	        container.appendChild(oldParent.container);
	        oldParent.container.setAttribute('data-status', 'hide');
	        container.appendChild(oldParent.groupContainer);
	      }
	      if (emitEvent) {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
	      }
	      _classPrivateMethodGet(this, _recalculateCounters, _recalculateCounters2).call(this, item);
	      _classPrivateMethodGet(this, _saveItemsSort, _saveItemsSort2).call(this, {
	        action: 'hideItem',
	        itemId: item.getId()
	      });
	    }
	  }, {
	    key: "updateCounters",
	    value: function updateCounters(counters, send) {
	      var _this4 = this;
	      var countersDynamic = {};
	      send = send !== false;
	      babelHelpers.toConsumableArray(Object.entries(counters)).forEach(function (_ref4) {
	        var _ref5 = babelHelpers.slicedToArray(_ref4, 2),
	          counterId = _ref5[0],
	          counterValue = _ref5[1];
	        babelHelpers.toConsumableArray(_classPrivateMethodGet(_this4, _getItemsByCounterId, _getItemsByCounterId2).call(_this4, counterId)).forEach(function (item) {
	          var _item$updateCounter = item.updateCounter(counterValue),
	            oldValue = _item$updateCounter.oldValue,
	            newValue = _item$updateCounter.newValue;
	          var state = item.container.getAttribute('data-status');
	          if ((counterId.indexOf('crm_') < 0 || counterId.indexOf('crm_all') >= 0) && (counterId.indexOf('tasks_') < 0 || counterId.indexOf('tasks_total') >= 0)) {
	            countersDynamic[state] = countersDynamic[state] || 0;
	            countersDynamic[state] += newValue - oldValue;
	          }
	          var parentItem = _classPrivateMethodGet(_this4, _getParentItemFor, _getParentItemFor2).call(_this4, item);
	          while (parentItem) {
	            parentItem.updateCounter();
	            parentItem = _classPrivateMethodGet(_this4, _getParentItemFor, _getParentItemFor2).call(_this4, parentItem);
	          }
	        });
	        if (send) {
	          BX.localStorage.set('lmc-' + counterId, counterValue, 5);
	        }
	      });
	      if (countersDynamic['hide'] !== undefined && countersDynamic['hide'] !== 0) {
	        var hiddenCounterNode = this.parentContainer.querySelector('#menu-hidden-counter');
	        hiddenCounterNode.dataset.counterValue = Math.max(0, Number(hiddenCounterNode.dataset.counterValue) + Number(countersDynamic['hide']));
	        if (hiddenCounterNode.dataset.counterValue > 0) {
	          hiddenCounterNode.classList.remove('menu-hidden-counter');
	          hiddenCounterNode.innerHTML = hiddenCounterNode.dataset.counterValue > 99 ? '99+' : hiddenCounterNode.dataset.counterValue;
	        } else {
	          hiddenCounterNode.classList.add('menu-hidden-counter');
	          hiddenCounterNode.innerHTML = '';
	        }
	      }
	      if (typeof BXIM !== 'undefined') {
	        if (babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue) === null) {
	          babelHelpers.classPrivateFieldSet(this, _updateCountersLastValue, 0);
	          babelHelpers.toConsumableArray(this.items.entries()).forEach(function (_ref6) {
	            var _ref7 = babelHelpers.slicedToArray(_ref6, 2),
	              id = _ref7[0],
	              item = _ref7[1];
	            if (item instanceof ItemGroup) {
	              return;
	            }
	            var res = item.getCounterValue();
	            if (res > 0) {
	              var counterId = 'doesNotMatter';
	              if (id.indexOf('menu_crm') >= 0 || id.indexOf('menu_tasks') >= 0) {
	                var counterNode = item.container.querySelector('[data-role="counter"]');
	                if (counterNode) {
	                  counterId = counterNode.id;
	                }
	              }
	              if (counterId === 'doesNotMatter' || counterId.indexOf('crm_all') >= 0 || counterId.indexOf('tasks_total') >= 0) {
	                babelHelpers.classPrivateFieldSet(_this4, _updateCountersLastValue, babelHelpers.classPrivateFieldGet(_this4, _updateCountersLastValue) + res);
	              }
	            }
	          });
	        } else {
	          babelHelpers.classPrivateFieldSet(this, _updateCountersLastValue, babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue) + (countersDynamic['show'] !== undefined ? countersDynamic['show'] : 0));
	          babelHelpers.classPrivateFieldSet(this, _updateCountersLastValue, babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue) + (countersDynamic['hide'] !== undefined ? countersDynamic['hide'] : 0));
	        }
	        var visibleValue = babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue) > 99 ? '99+' : babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue) < 0 ? '0' : babelHelpers.classPrivateFieldGet(this, _updateCountersLastValue);
	        var desktop = main_core.Reflection.getClass('BXIM.desktop');
	        if (desktop) {
	          desktop.setBrowserIconBadge(visibleValue);
	        }
	      }
	    }
	  }, {
	    key: "decrementCounter",
	    value: function decrementCounter(counters) {
	      var _this5 = this;
	      babelHelpers.toConsumableArray(Object.entries(counters)).forEach(function (_ref8) {
	        var _ref9 = babelHelpers.slicedToArray(_ref8, 2),
	          counterId = _ref9[0],
	          counterValue = _ref9[1];
	        var item = _classPrivateMethodGet(_this5, _getItemsByCounterId, _getItemsByCounterId2).call(_this5, counterId).shift();
	        if (item) {
	          var value = item.getCounterValue();
	          counters[counterId] = value > counterValue ? value - counterValue : 0;
	        } else {
	          delete counters[counterId];
	        }
	      });
	      this.updateCounters(counters, false);
	    }
	  }, {
	    key: "addItem",
	    value: function addItem(_ref10) {
	      var node = _ref10.node,
	        animateFromPoint = _ref10.animateFromPoint;
	      if (!(node instanceof Element)) {
	        return;
	      }
	      var styleValue = node.style.display;
	      if (animateFromPoint) {
	        node.dataset.styleDisplay = node.style.display;
	        node.style.display = 'none';
	      }
	      if (this.items.has(node.dataset.id) && node.dataset.type === ItemUserFavorites.code) {
	        var item = this.items.get(node.dataset.id);
	        item.storage.push(ItemUserFavorites.code);
	        item.container.dataset.storage = item.storage.join(',');
	        node = item.container;
	      } else {
	        this.container.appendChild(node);
	        this.registerItem(node);
	        _classPrivateMethodGet(this, _saveItemsSort, _saveItemsSort2).call(this);
	      }
	      if (animateFromPoint) {
	        _classPrivateMethodGet(this, _animateTopItemToLeft, _animateTopItemToLeft2).call(this, node, animateFromPoint).then(function () {
	          node.style.display = node.dataset.styleDisplay;
	        });
	      }
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(data) {
	      var id = data.id;
	      if (this.items.has(id)) {
	        this.items.get(id).update(data);
	      }
	    }
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(item, _ref11) {
	      var _this6 = this;
	      var animate = _ref11.animate;
	      this.items["delete"](item.getId());
	      babelHelpers.classPrivateFieldGet(this, _activeItem).checkAndUnset(item);
	      if (item instanceof ItemUserFavorites || animate) {
	        _classPrivateMethodGet(this, _animateTopItemFromLeft, _animateTopItemFromLeft2).call(this, item.container).then(function () {
	          item.container.parentNode.removeChild(item.container);
	          _classPrivateMethodGet(_this6, _saveItemsSort, _saveItemsSort2).call(_this6);
	        });
	      } else if (item.container) {
	        item.container.parentNode.removeChild(item.container);
	        _classPrivateMethodGet(this, _saveItemsSort, _saveItemsSort2).call(this);
	      }
	    }
	  }, {
	    key: "convertItem",
	    value: function convertItem(item) {
	      this.unregisterItem(item);
	      this.registerItem(this.parentContainer.querySelector("li.menu-item-block[data-id=\"".concat(item.getId(), "\"]")));
	    }
	  }, {
	    key: "getActiveItem",
	    value: function getActiveItem() {
	      return babelHelpers.classPrivateFieldGet(this, _activeItem).item;
	    }
	  }, {
	    key: "export",
	    value: function _export() {
	      return _classPrivateMethodGet(this, _getItemsToSave, _getItemsToSave2).call(this);
	    } //region DropdownActions
	  }, {
	    key: "openItemMenu",
	    value: function openItemMenu(item, target) {
	      var _this7 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _openItemMenuPopup)) {
	        babelHelpers.classPrivateFieldGet(this, _openItemMenuPopup).close();
	      }
	      var contextMenuItems = [];
	      // region hide/show item
	      if (item.container.getAttribute("data-status") === "show") {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("hide_item"),
	          onclick: function onclick() {
	            _this7.hideItem(item);
	          }
	        });
	      } else {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("show_item"),
	          onclick: function onclick(target, contextMenuItem) {
	            _this7.showItem(item);
	          }
	        });
	      }
	      //endregion

	      //region set as main page
	      if (!Options.isExtranet && !(item instanceof ItemUserSelf) && !(item instanceof ItemGroup) && this.container.querySelector('li.menu-item-block[data-role="item"]') !== item.container) {
	        contextMenuItems.push({
	          text: main_core.Loc.getMessage("MENU_SET_MAIN_PAGE"),
	          onclick: function onclick() {
	            _this7.setItemAsAMainPage(item);
	          }
	        });
	      }
	      //endregion

	      item.getDropDownActions().forEach(function (actionItem) {
	        contextMenuItems.push(actionItem);
	      });
	      contextMenuItems.push({
	        text: babelHelpers.classPrivateFieldGet(this, _isEditMode) ? main_core.Loc.getMessage("MENU_EDIT_READY_FULL") : main_core.Loc.getMessage("MENU_SETTINGS_MODE"),
	        onclick: function onclick() {
	          babelHelpers.classPrivateFieldGet(_this7, _isEditMode) ? _this7.switchToViewMode() : _this7.switchToEditMode();
	        }
	      });
	      contextMenuItems.forEach(function (item) {
	        var _item$className;
	        item['className'] = ["menu-popup-no-icon", (_item$className = item['className']) !== null && _item$className !== void 0 ? _item$className : ''].join(' ');
	        var onclick = item.onclick;
	        item['onclick'] = function (event, item) {
	          item.getMenuWindow().close();
	          onclick.call(event, item);
	        };
	      });
	      babelHelpers.classPrivateFieldSet(this, _openItemMenuPopup, new main_popup.Menu({
	        bindElement: target,
	        items: contextMenuItems,
	        offsetTop: 0,
	        offsetLeft: 12,
	        angle: true,
	        events: {
	          onClose: function onClose() {
	            main_core_events.EventEmitter.emit(_this7, Options.eventName('onClose'));
	            item.container.classList.remove('menu-item-block-hover');
	            babelHelpers.classPrivateFieldSet(_this7, _openItemMenuPopup, null);
	          },
	          onShow: function onShow() {
	            item.container.classList.add('menu-item-block-hover');
	            main_core_events.EventEmitter.emit(_this7, Options.eventName('onShow'));
	          }
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _openItemMenuPopup).show();
	    } //endregion
	    //region Visible sliding
	    /*endregion*/
	  }, {
	    key: "isEditMode",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _isEditMode);
	    }
	  }]);
	  return ItemsController;
	}(DefaultController);
	function _showHiddenContainer2(animate) {
	  main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsVisible'));
	  if (animate === false) {
	    return this.hiddenContainer.classList.add('menu-item-favorites-more-open');
	  }
	  this.hiddenContainer.style.height = "0px";
	  this.hiddenContainer.style.opacity = 0;
	  _classPrivateMethodGet(this, _animation, _animation2).call(this, true, this.hiddenContainer, this.hiddenContainer.scrollHeight);
	}
	function _hideHiddenContainer2(animate) {
	  main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsHidden'));
	  if (animate === false) {
	    return this.hiddenContainer.classList.remove('menu-item-favorites-more-open');
	  }
	  _classPrivateMethodGet(this, _animation, _animation2).call(this, false, this.hiddenContainer, this.hiddenContainer.offsetHeight);
	}
	function _animation2(opening, hiddenBlock, maxHeight) {
	  hiddenBlock.style.overflow = "hidden";
	  new BX.easing({
	    duration: 200,
	    start: {
	      opacity: opening ? 0 : 100,
	      height: opening ? 0 : maxHeight
	    },
	    finish: {
	      opacity: opening ? 100 : 0,
	      height: opening ? maxHeight : 0
	    },
	    transition: BX.easing.transitions.linear,
	    step: function step(state) {
	      hiddenBlock.style.opacity = state.opacity / 100;
	      hiddenBlock.style.height = state.height + "px";
	    },
	    complete: function complete() {
	      if (opening) {
	        hiddenBlock.classList.add('menu-item-favorites-more-open');
	      } else {
	        hiddenBlock.classList.remove('menu-item-favorites-more-open');
	      }
	      hiddenBlock.style.opacity = "";
	      hiddenBlock.style.overflow = "";
	      hiddenBlock.style.height = "";
	    }
	  }).animate();
	}
	function _recalculateCounters2(item) {
	  var counterValue = 0;
	  if (item.container.querySelector('[data-role="counter"]')) {
	    counterValue = item.container.querySelector('[data-role="counter"]').dataset.counterValue;
	  }
	  if (counterValue <= 0) {
	    return;
	  }
	  babelHelpers.toConsumableArray(this.items.entries()).forEach(function (_ref12) {
	    var _ref13 = babelHelpers.slicedToArray(_ref12, 2),
	      id = _ref13[0],
	      itemGroup = _ref13[1];
	    if (itemGroup instanceof ItemGroup) {
	      itemGroup.updateCounter();
	    }
	  });
	  var hiddenCounterValue = 0;
	  babelHelpers.toConsumableArray(this.parentContainer.querySelectorAll(".menu-item-block[data-status=\"hide\"][data-role='item']")).forEach(function (node) {
	    var counterNode = node.querySelector('[data-role="counter"]');
	    if (counterNode) {
	      hiddenCounterValue += parseInt(counterNode.dataset.counterValue);
	    }
	  });
	  var hiddenCounterNode = this.parentContainer.querySelector('#menu-hidden-counter');
	  hiddenCounterNode.dataset.counterValue = Math.max(0, hiddenCounterValue);
	  if (hiddenCounterNode.dataset.counterValue > 0) {
	    hiddenCounterNode.classList.remove('menu-hidden-counter');
	    hiddenCounterNode.innerHTML = hiddenCounterNode.dataset.counterValue > 99 ? '99+' : hiddenCounterNode.dataset.counterValue;
	  } else {
	    hiddenCounterNode.classList.add('menu-hidden-counter');
	    hiddenCounterNode.innerHTML = '';
	  }
	}
	function _refreshActivity2(item, oldParent) {
	  if (this.getActiveItem() !== item) {
	    return;
	  }
	  var newParent = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	  if (oldParent !== newParent) {
	    if (oldParent instanceof ItemGroup) {
	      oldParent.markAsInactive();
	    }
	    if (newParent instanceof ItemGroup) {
	      newParent.markAsActive();
	    }
	  }
	}
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
	function _getItemsToSave2() {
	  var _this8 = this;
	  var saveSortItems = {
	    show: [],
	    hide: []
	  };
	  var customItems = [];
	  var firstItemLink = null;
	  ['show', 'hide'].forEach(function (state) {
	    var items = saveSortItems[state];
	    var currentGroupId = null;
	    var chain = [];
	    Array.from(_this8.parentContainer.querySelectorAll(".menu-item-block[data-status=\"".concat(state, "\"]"))).forEach(function (node) {
	      if (node.dataset.role === 'group') {
	        var groupId = node.parentNode.hasAttribute('data-group-id') ? node.parentNode.getAttribute('data-group-id') : null;
	        items = saveSortItems[state];
	        var groupItem;
	        while (groupItem = chain.pop()) {
	          if (groupItem['group_id'] === groupId) {
	            chain.push(groupItem);
	            items = groupItem.items;
	            break;
	          }
	        }
	        var item = {
	          group_id: node.dataset.id,
	          items: []
	        };
	        items.push(item);
	        chain.push(item);
	        items = item.items;
	        currentGroupId = node.dataset.id;
	      } else {
	        if ([ItemAdminCustom.code, ItemUserSelf.code, ItemUserFavorites.code].indexOf(node.getAttribute('data-type')) >= 0) {
	          var _item = {
	            ID: node.getAttribute('data-id'),
	            LINK: node.getAttribute('data-link'),
	            TEXT: main_core.Text.decode(node.querySelector("[data-role='item-text']").innerHTML)
	          };
	          if (node.getAttribute("data-new-page") === "Y") {
	            _item.NEW_PAGE = "Y";
	          }
	          customItems.push(_item);
	        }
	        if (firstItemLink === null && main_core.Type.isStringFilled(node.getAttribute("data-link"))) {
	          firstItemLink = node.getAttribute("data-link");
	        }
	        if (node.closest("[data-group-id=\"".concat(currentGroupId, "\"][data-role=\"group-content\"]"))) {
	          items.push(node.dataset.id);
	        } else {
	          var _groupId = node.parentNode.hasAttribute('data-group-id') ? node.parentNode.getAttribute('data-group-id') : null;
	          items = saveSortItems[state];
	          var _groupItem;
	          while (_groupItem = chain.pop()) {
	            if (_groupItem['group_id'] === _groupId) {
	              chain.push(_groupItem);
	              items = _groupItem.items;
	              break;
	            }
	          }
	          items.push(node.dataset.id);
	        }
	      }
	    });
	  });
	  return {
	    saveSortItems: saveSortItems,
	    firstItemLink: firstItemLink,
	    customItems: customItems
	  };
	}
	function _saveItemsSort2(analyticsLabel) {
	  var _classPrivateMethodGe = _classPrivateMethodGet(this, _getItemsToSave, _getItemsToSave2).call(this),
	    saveSortItems = _classPrivateMethodGe.saveSortItems,
	    firstItemLink = _classPrivateMethodGe.firstItemLink;
	  Backend.saveItemsSort(saveSortItems, firstItemLink, analyticsLabel || {
	    action: 'sortItem'
	  });
	}
	function _getParentItemFor2(item) {
	  if (!(item instanceof Item)) {
	    return null;
	  }
	  var parentContainer = item.container.closest('[data-role="group-content"]');
	  if (parentContainer && this.items.has(parentContainer.getAttribute('data-group-id'))) {
	    return this.items.get(parentContainer.getAttribute('data-group-id'));
	  }
	  return null;
	}
	function _canChangePaternity2(item) {
	  if (item instanceof ItemGroup) {
	    return false;
	  }
	  var oldParent = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	  if (oldParent instanceof ItemGroup && item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1) {
	    return false;
	  }
	  return true;
	}
	function _animateTopItemToLeft2(node, animateFromPoint) {
	  var _this9 = this;
	  return new Promise(function (resolve) {
	    var startX = animateFromPoint.startX,
	      startY = animateFromPoint.startY;
	    var topMenuNode = document.createElement('DIV');
	    topMenuNode.style = "position: absolute; z-index: 1000; top: ".concat(startY + 25, "px;");
	    var cloneNode = node.cloneNode(true);
	    cloneNode.style.display = node.dataset.styleDisplay;
	    document.body.appendChild(topMenuNode);
	    topMenuNode.appendChild(cloneNode);
	    var finishY = _this9.hiddenContainer.getBoundingClientRect().top;
	    new BX.easing({
	      duration: 500,
	      start: {
	        left: startX
	      },
	      finish: {
	        left: 30
	      },
	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function step(state) {
	        topMenuNode.style.left = state.left + "px";
	      },
	      complete: function complete() {
	        new BX.easing({
	          duration: 500,
	          start: {
	            top: startY + 25
	          },
	          finish: {
	            top: finishY
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            topMenuNode.style.top = state.top + "px";
	          },
	          complete: function complete() {
	            main_core.Dom.remove(topMenuNode);
	            resolve();
	          }
	        }).animate();
	      }
	    }).animate();
	  });
	}
	function _animateTopItemFromLeft2(node) {
	  return new Promise(function (resolve) {
	    new BX.easing({
	      duration: 700,
	      start: {
	        left: node.offsetLeft,
	        opacity: 1
	      },
	      finish: {
	        left: 400,
	        opacity: 0
	      },
	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function step(state) {
	        node.style.paddingLeft = state.left + "px";
	        node.style.opacity = state.opacity;
	      },
	      complete: function complete() {
	        resolve();
	      }
	    }).animate();
	  });
	}
	function _registerDND2(item) {
	  var _this10 = this;
	  //drag&drop
	  jsDD.Enable();
	  item.container.onbxdragstart = _classPrivateMethodGet(this, _menuItemDragStart, _menuItemDragStart2).bind(this, item);
	  item.container.onbxdrag = function (x, y) {
	    _classPrivateMethodGet(_this10, _menuItemDragMove, _menuItemDragMove2).call(_this10, /*item,*/x, y);
	  };
	  item.container.onbxdraghover = function (dest, x, y) {
	    _classPrivateMethodGet(_this10, _menuItemDragHover, _menuItemDragHover2).call(_this10, /*item, */dest, x, y);
	  };
	  item.container.onbxdragstop = _classPrivateMethodGet(this, _menuItemDragStop, _menuItemDragStop2).bind(this, item);
	  jsDD.registerObject(item.container);
	}
	function _menuItemDragStart2(item) {
	  var _this11 = this;
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.LeftMenuClass:onDragStart');
	  if (!(item instanceof ItemGroup) && item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1 && _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item) !== null) {
	    item = _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item);
	  }
	  main_core_events.EventEmitter.emit(this, Options.eventName('onDragModeOn'));
	  this.dnd = {
	    container: this.container.parentNode,
	    itemDomBlank: main_core.Dom.create('div', {
	      style: {
	        display: 'none'
	        // border: '2px solid navy'
	      }
	    }),

	    itemMoveBlank: main_core.Dom.create('div', {
	      style: {
	        height: item.container.offsetHeight + 'px'
	        // border: '2px solid red',
	      }
	    }),

	    draggableBlock: main_core.Dom.create('div', {
	      //div to move
	      attrs: {
	        className: "menu-draggable-wrap"
	      },
	      style: {
	        top: [item.container.offsetTop - item.container.offsetHeight, 'px'].join('')
	        // border: '2px solid black'
	      }
	    }),

	    item: item,
	    oldParent: _classPrivateMethodGet(this, _getParentItemFor, _getParentItemFor2).call(this, item),
	    isHiddenContainerVisible: this.isHiddenContainerVisible()
	  };
	  _classPrivateMethodGet(this, _showHiddenContainer, _showHiddenContainer2).call(this, false);
	  var registerItems = function registerItems() {
	    babelHelpers.toConsumableArray(_this11.parentContainer.querySelectorAll('li.menu-item-block')).forEach(function (node) {
	      if (item instanceof ItemGroup && _classPrivateMethodGet(_this11, _getParentItemFor, _getParentItemFor2).call(_this11, _this11.items.get(node.getAttribute('data-id'))) !== null) {
	        return;
	      }
	      jsDD.registerDest(node, 100);
	    });
	    var firstNode = _this11.parentContainer.querySelector("#left-menu-empty-item");
	    if (item instanceof ItemUserSelf) {
	      jsDD.unregisterDest(firstNode);
	    } else {
	      jsDD.registerDest(firstNode, 100);
	    }
	    jsDD.registerDest(_this11.parentContainer.querySelector("#left-menu-hidden-empty-item"), 100);
	    jsDD.registerDest(_this11.parentContainer.querySelector("#left-menu-hidden-separator"), 100);
	  };
	  if (item instanceof ItemGroup) {
	    item.collapse(true).then(function () {
	      if (_this11.dnd) {
	        _this11.dnd.pos = BX.pos(_this11.container.parentNode);
	        registerItems();
	      }
	    });
	  } else {
	    registerItems();
	  }
	  var dragElement = item.container;
	  main_core.Dom.addClass(this.dnd.container, "menu-drag-mode");
	  main_core.Dom.addClass(dragElement, "menu-item-draggable");
	  dragElement.parentNode.insertBefore(this.dnd.itemDomBlank, dragElement); //remember original item place
	  dragElement.parentNode.insertBefore(this.dnd.itemMoveBlank, dragElement); //empty div
	  this.dnd.draggableBlock.appendChild(item.container);
	  this.dnd.container.style.position = 'relative';
	  this.dnd.container.appendChild(this.dnd.draggableBlock);
	  this.dnd.pos = BX.pos(this.container.parentNode);
	}
	function _menuItemDragMove2( /*item,*/x, y) {
	  var item = this.dnd.item;
	  var menuItemsBlockHeight = this.dnd.pos.height;
	  y = Math.max(0, y - this.dnd.pos.top);
	  this.dnd.draggableBlock.style.top = [Math.min(menuItemsBlockHeight - item.container.offsetHeight, y) - 5, 'px'].join('');
	}
	function _menuItemDragHover2( /*item, */dest, x, y) {
	  var item = this.dnd.item;
	  var dragElement = item.container;
	  if (dest === dragElement) {
	    this.dnd.itemDomBlank.parentNode.insertBefore(this.dnd.itemMoveBlank, this.dnd.itemDomBlank);
	    return;
	  }
	  if (dest.id === "left-menu-empty-item" && (dragElement.getAttribute("data-type") === "self" || dragElement.getAttribute("data-disable-first-item") === "Y")) {
	    return; // self-item cannot be moved on the first place
	  }

	  if (dest.getAttribute('data-role') === 'group') {
	    var groupHolder = dest.parentNode.querySelector("[data-group-id=\"".concat(dest.getAttribute('data-id'), "\"]"));
	    if (dest.getAttribute('data-collapse-mode') === 'collapsed') {
	      main_core.Dom.insertAfter(this.dnd.itemMoveBlank, groupHolder);
	    } else if (item instanceof ItemGroup) {
	      main_core.Dom.insertBefore(this.dnd.itemMoveBlank, dest);
	    } else {
	      main_core.Dom.prepend(this.dnd.itemMoveBlank, groupHolder.querySelector('ul'));
	    }
	  } else if (this.dnd.container.contains(dest)) {
	    var itemPlaceHolder = dest;
	    if (item instanceof ItemGroup && dest.closest('[data-role="group-content"]')) {
	      itemPlaceHolder = dest.closest('[data-role="group-content"]');
	    }
	    main_core.Dom.insertAfter(this.dnd.itemMoveBlank, itemPlaceHolder);
	  }
	}
	function _menuItemDragStop2() {
	  var item = this.dnd.item;
	  var oldParent = this.dnd.oldParent;
	  var dragElement = item.container;
	  main_core.Dom.removeClass(this.dnd.container, "menu-drag-mode");
	  main_core.Dom.removeClass(dragElement, "menu-item-draggable");
	  this.dnd.container.style.position = '';
	  var error = null;
	  var onHiddenBlockIsEmptyEmitted = false;
	  if (this.parentContainer.querySelector('.menu-item-block') === item.container) {
	    if (item instanceof ItemUserSelf) {
	      error = main_core.Loc.getMessage('MENU_SELF_ITEM_FIRST_ERROR');
	    } else if (item.container.getAttribute("data-disable-first-item") === "Y") {
	      error = main_core.Loc.getMessage("MENU_FIRST_ITEM_ERROR");
	    }
	  }
	  if (error !== null) {
	    this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	    item.showMessage(error);
	  } else if (!this.dnd.container.contains(this.dnd.itemMoveBlank)) {
	    this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	  } else {
	    try {
	      this.dnd.itemMoveBlank.parentNode.replaceChild(dragElement, this.dnd.itemMoveBlank);
	      if (this.hiddenContainer.contains(dragElement)) {
	        item.container.setAttribute("data-status", "hide");
	        if (this.dnd.itemDomBlank.closest('#left-menu-hidden-items-block') === null && this.hiddenContainer.querySelectorAll('.menu-item-block').length === 1) {
	          main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
	        }
	      } else {
	        item.container.setAttribute("data-status", "show");
	        if (this.hiddenContainer.querySelectorAll('.menu-item-block').length <= 0) {
	          onHiddenBlockIsEmptyEmitted = true;
	          main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
	        }
	      }
	      if (item instanceof ItemGroup) {
	        item.checkAndCorrect().expand(true);
	      }
	      _classPrivateMethodGet(this, _refreshActivity, _refreshActivity2).call(this, item, oldParent);
	      _classPrivateMethodGet(this, _recalculateCounters, _recalculateCounters2).call(this, item);
	      var analyticsLabel = {
	        action: 'sortItem'
	      };
	      if (this.parentContainer.querySelector('.menu-item-block') === item.container && !this.isExtranet) {
	        item.showMessage(main_core.Loc.getMessage("MENU_ITEM_MAIN_PAGE"));
	        analyticsLabel.action = 'mainPage';
	        analyticsLabel.itemId = item.getId();
	      }
	      _classPrivateMethodGet(this, _saveItemsSort, _saveItemsSort2).call(this, analyticsLabel);
	    } catch (e) {
	      this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	    }
	  }
	  main_core.Dom.remove(this.dnd.draggableBlock);
	  main_core.Dom.remove(this.dnd.itemDomBlank);
	  main_core.Dom.remove(this.dnd.itemMoveBlank);
	  jsDD.enableDest(dragElement);
	  this.container.style.position = 'static';
	  if (!this.dnd.isHiddenContainerVisible || onHiddenBlockIsEmptyEmitted === true) {
	    _classPrivateMethodGet(this, _hideHiddenContainer, _hideHiddenContainer2).call(this, false);
	  }
	  delete this.dnd;
	  babelHelpers.toConsumableArray(this.parentContainer.querySelectorAll('li.menu-item-block')).forEach(function (node) {
	    jsDD.registerDest(node);
	  });
	  var firstNode = this.parentContainer.querySelector("#left-menu-empty-item");
	  jsDD.unregisterDest(firstNode);
	  jsDD.unregisterDest(this.parentContainer.querySelector("#left-menu-hidden-empty-item"));
	  jsDD.unregisterDest(this.parentContainer.querySelector("#left-menu-hidden-separator"));
	  jsDD.refreshDestArea();
	  main_core_events.EventEmitter.emit(this, Options.eventName('onDragModeOff'));
	}

	var ItemDirector = /*#__PURE__*/function (_DefaultController) {
	  babelHelpers.inherits(ItemDirector, _DefaultController);
	  function ItemDirector(container, params) {
	    babelHelpers.classCallCheck(this, ItemDirector);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemDirector).call(this, container, params));
	  }
	  babelHelpers.createClass(ItemDirector, [{
	    key: "saveCurrentPage",
	    value: function saveCurrentPage(page) {
	      var _this = this;
	      return ItemUserFavorites.saveCurrentPage(page).then(function (data) {
	        main_core_events.EventEmitter.emit(_this, Options.eventName('onItemHasBeenAdded'), data);
	        return data;
	      })["catch"](Utils.catchError);
	    }
	  }, {
	    key: "saveStandardPage",
	    value: function saveStandardPage(topItem) {
	      var _this2 = this;
	      return ItemUserFavorites.saveStandardPage(topItem).then(function (data) {
	        main_core_events.EventEmitter.emit(_this2, Options.eventName('onItemHasBeenAdded'), data);
	        return data;
	      })["catch"](Utils.catchError);
	    }
	  }, {
	    key: "deleteCurrentPage",
	    value: function deleteCurrentPage(_ref) {
	      var _this3 = this;
	      var pageLink = _ref.pageLink;
	      return ItemUserFavorites.deleteCurrentPage({
	        pageLink: pageLink
	      }).then(function (data) {
	        main_core_events.EventEmitter.emit(_this3, Options.eventName('onItemHasBeenDeleted'), data);
	        return data;
	      })["catch"](Utils.catchError);
	    }
	  }, {
	    key: "deleteStandardPage",
	    value: function deleteStandardPage(topItem) {
	      var _this4 = this;
	      return ItemUserFavorites.deleteStandardPage(topItem).then(function (data) {
	        main_core_events.EventEmitter.emit(_this4, Options.eventName('onItemHasBeenDeleted'), data);
	        return data;
	      })["catch"](Utils.catchError);
	    }
	  }, {
	    key: "showAddToSelf",
	    value: function showAddToSelf(bindElement) {
	      var _this5 = this;
	      ItemUserSelf.showAdd(bindElement).then(function (data) {
	        main_core_events.EventEmitter.emit(_this5, Options.eventName('onItemHasBeenAdded'), data);
	      })["catch"](Utils.catchError);
	    }
	  }]);
	  return ItemDirector;
	}(DefaultController);

	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getLeftMenuItemByTopMenuItem = /*#__PURE__*/new WeakSet();
	var _isLogoMaskNeeded = /*#__PURE__*/new WeakSet();
	var _specialLiveFeedDecrement = /*#__PURE__*/new WeakMap();
	var _adjustAdminPanel = /*#__PURE__*/new WeakSet();
	var Menu = /*#__PURE__*/function () {
	  //region containers

	  //endregion

	  //

	  //

	  function Menu(params) {
	    babelHelpers.classCallCheck(this, Menu);
	    _classPrivateMethodInitSpec$1(this, _adjustAdminPanel);
	    _classPrivateMethodInitSpec$1(this, _isLogoMaskNeeded);
	    _classPrivateMethodInitSpec$1(this, _getLeftMenuItemByTopMenuItem);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "scrollModeThreshold", 20);
	    babelHelpers.defineProperty(this, "lastScrollOffset", 0);
	    babelHelpers.defineProperty(this, "slidingModeTimeoutId", 0);
	    babelHelpers.defineProperty(this, "topMenuSelectedNode", null);
	    babelHelpers.defineProperty(this, "topItemSelectedObj", null);
	    babelHelpers.defineProperty(this, "isMenuMouseEnterBlocked", false);
	    babelHelpers.defineProperty(this, "isMenuMouseLeaveBlocked", []);
	    babelHelpers.defineProperty(this, "isCollapsedMode", false);
	    babelHelpers.defineProperty(this, "workgroupsCounterData", {});
	    _classPrivateFieldInitSpec$4(this, _specialLiveFeedDecrement, {
	      writable: true,
	      value: 0
	    });
	    //TODO     html
	    this.menuContainer = document.getElementById("menu-items-block");
	    if (!this.menuContainer) {
	      return false;
	    }
	    params = babelHelpers["typeof"](params) === "object" ? params : {};
	    Options.isExtranet = params.isExtranet === 'Y';
	    Options.isAdmin = params.isAdmin;
	    Options.isCustomPresetRestricted = params.isCustomPresetAvailable !== 'Y';
	    this.isCollapsedMode = params.isCollapsedMode;
	    this.workgroupsCounterData = params.workgroupsCounterData;
	    this.initAndBindNodes();
	    this.bindEvents();
	    this.getItemsController();
	    //Emulate document scroll because init() can be invoked after page load scroll (a hard reload with script at the bottom).
	    this.handleDocumentScroll();
	  }
	  babelHelpers.createClass(Menu, [{
	    key: "initAndBindNodes",
	    value: function initAndBindNodes() {
	      var _this = this;
	      this.menuContainer.addEventListener("dblclick", this.handleMenuDoubleClick.bind(this));
	      this.menuContainer.addEventListener("mouseenter", this.handleMenuMouseEnter.bind(this));
	      this.menuContainer.addEventListener("mouseleave", this.handleMenuMouseLeave.bind(this));
	      this.menuContainer.addEventListener("transitionend", this.handleSlidingTransitionEnd.bind(this));
	      this.menuHeader = this.menuContainer.querySelector(".menu-items-header");
	      this.menuBody = this.menuContainer.querySelector(".menu-items-body");
	      this.menuItemsBlock = this.menuContainer.querySelector(".menu-items");
	      this.header = document.querySelector("#header");
	      this.headerBurger = this.header.querySelector(".menu-switcher");
	      var headerLogoBlock = this.header.querySelector(".header-logo-block");
	      this.headerSettings = this.header.querySelector(".header-logo-block-settings");
	      if (this.headerSettings) {
	        headerLogoBlock.addEventListener("mouseenter", this.handleHeaderLogoMouserEnter.bind(this));
	        headerLogoBlock.addEventListener("mouseleave", this.handleHeaderLogoMouserLeave.bind(this));
	        this.menuHeader.addEventListener("mouseenter", this.handleHeaderLogoMouserEnter.bind(this));
	        this.menuHeader.addEventListener("mouseleave", this.handleHeaderLogoMouserLeave.bind(this));
	      }
	      document.addEventListener("scroll", this.handleDocumentScroll.bind(this));
	      this.mainTable = document.querySelector(".bx-layout-table");
	      this.menuHeaderBurger = this.menuHeader.querySelector(".menu-switcher");
	      this.menuHeaderBurger.addEventListener('click', this.handleBurgerClick.bind(this));
	      this.menuHeader.querySelector(".menu-items-header-title").addEventListener('click', this.handleBurgerClick.bind(this, true));
	      this.upButton = this.menuContainer.querySelector(".menu-btn-arrow-up");
	      this.upButton.addEventListener("click", this.handleUpButtonClick.bind(this));
	      this.menuMoreButton = this.menuContainer.querySelector(".menu-favorites-more-btn");
	      this.menuMoreButton.addEventListener("click", this.handleShowHiddenClick.bind(this));
	      var helperItem = this.menuContainer.querySelector(".menu-help-btn");
	      if (helperItem) {
	        helperItem.addEventListener('click', this.handleHelperClick.bind(this));
	      }
	      var siteMapItem = this.menuContainer.querySelector(".menu-sitemap-btn");
	      if (siteMapItem) {
	        siteMapItem.addEventListener('click', this.handleSiteMapClick.bind(this));
	      }
	      var settingsSaveBtn = this.menuContainer.querySelector(".menu-settings-save-btn");
	      if (settingsSaveBtn) {
	        settingsSaveBtn.addEventListener('click', this.handleViewMode.bind(this));
	      }
	      this.menuContainer.querySelector(".menu-settings-btn").addEventListener('click', function () {
	        _this.getSettingsController().show();
	      });
	    } // region Controllers
	  }, {
	    key: "getItemsController",
	    value: function getItemsController() {
	      var _this2 = this;
	      return this.cache.remember('itemsController', function () {
	        return new ItemsController(_this2.menuContainer, {
	          events: {
	            EditMode: function EditMode() {
	              _this2.toggle(true);
	              _this2.menuContainer.classList.add('menu-items-edit-mode');
	              _this2.menuContainer.classList.remove('menu-items-view-mode');
	            },
	            ViewMode: function ViewMode() {
	              _this2.toggle(true);
	              _this2.menuContainer.classList.add('menu-items-view-mode');
	              _this2.menuContainer.classList.remove('menu-items-edit-mode');
	            },
	            onDragModeOn: function onDragModeOn(_ref) {
	              var target = _ref.target;
	              _this2.switchToSlidingMode(true);
	              _this2.isMenuMouseLeaveBlocked.push('drag');
	            },
	            onDragModeOff: function onDragModeOff(_ref2) {
	              var target = _ref2.target;
	              _this2.isMenuMouseLeaveBlocked.pop();
	            },
	            onHiddenBlockIsVisible: _this2.onHiddenBlockIsVisible.bind(_this2),
	            onHiddenBlockIsHidden: _this2.onHiddenBlockIsHidden.bind(_this2),
	            onHiddenBlockIsEmpty: _this2.onHiddenBlockIsEmpty.bind(_this2),
	            onHiddenBlockIsNotEmpty: _this2.onHiddenBlockIsNotEmpty.bind(_this2),
	            onShow: function onShow() {
	              _this2.isMenuMouseLeaveBlocked.push('items');
	            },
	            onClose: function onClose() {
	              _this2.isMenuMouseLeaveBlocked.pop();
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getItemDirector",
	    value: function getItemDirector() {
	      var _this3 = this;
	      return this.cache.remember('itemsCreator', function () {
	        return new ItemDirector(_this3.menuContainer, {
	          events: {
	            onItemHasBeenAdded: function onItemHasBeenAdded(_ref3) {
	              var data = _ref3.data;
	              _this3.getItemsController().addItem(data);
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getSettingsController",
	    value: function getSettingsController() {
	      var _this4 = this;
	      return this.cache.remember('presetController', function () {
	        return new SettingsController(_this4.menuContainer.querySelector(".menu-settings-btn"), {
	          events: {
	            onGettingSettingMenuItems: _this4.onGettingSettingMenuItems.bind(_this4),
	            onShow: function onShow() {
	              _this4.isMenuMouseLeaveBlocked.push('settings');
	            },
	            onClose: function onClose() {
	              _this4.isMenuMouseLeaveBlocked.pop();
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getCustomPresetController",
	    value: function getCustomPresetController() {
	      var _this5 = this;
	      return this.cache.remember('customPresetController', function () {
	        return new PresetCustomController(_this5.menuContainer, {
	          events: {
	            onPresetIsSet: function onPresetIsSet(_ref4) {
	              var data = _ref4.data;
	              var _this5$getItemsContro = _this5.getItemsController()["export"](),
	                saveSortItems = _this5$getItemsContro.saveSortItems,
	                firstItemLink = _this5$getItemsContro.firstItemLink,
	                customItems = _this5$getItemsContro.customItems;
	              return Backend.setCustomPreset(data, saveSortItems, customItems, firstItemLink);
	            },
	            onShow: function onShow() {
	              _this5.isMenuMouseLeaveBlocked.push('presets');
	            },
	            onClose: function onClose() {
	              _this5.isMenuMouseLeaveBlocked.pop();
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getDefaultPresetController",
	    value: function getDefaultPresetController() {
	      var _this6 = this;
	      return this.cache.remember('defaultPresetController', function () {
	        return new PresetDefaultController(_this6.menuContainer, {
	          events: {
	            onPresetIsSet: function onPresetIsSet(_ref5) {
	              var _ref5$data = _ref5.data,
	                mode = _ref5$data.mode,
	                presetId = _ref5$data.presetId;
	              return Backend.setSystemPreset(mode, presetId);
	            },
	            onPresetIsPostponed: function onPresetIsPostponed(_ref6) {
	              var mode = _ref6.data.mode;
	              var result = Backend.postponeSystemPreset(mode);
	              main_core_events.EventEmitter.emit(_this6, Options.eventName('onPresetIsPostponed'));
	              return result;
	            }
	            /*
	            						onShow: () => { this.isMenuMouseLeaveBlocked.push('presets-default'); },
	            						onClose: () => { this.isMenuMouseLeaveBlocked.pop(); },
	            */
	          }
	        });
	      });
	    } //endregion
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this7 = this;
	      //just to hold opened menu in collapsing mode when groups are shown
	      BX.addCustomEvent("BX.Bitrix24.GroupPanel:onOpen", this.handleGroupPanelOpen.bind(this));
	      BX.addCustomEvent("BX.Bitrix24.GroupPanel:onClose", this.handleGroupPanelClose.bind(this));

	      //region Top menu integration
	      BX.addCustomEvent('BX.Main.InterfaceButtons:onFirstItemChange', function (firstPageLink, firstNode) {
	        if (!firstPageLink || !main_core.Type.isDomNode(firstNode)) {
	          return;
	        }
	        var topMenuId = firstNode.getAttribute("data-top-menu-id");
	        var leftMenuNode = _this7.menuBody.querySelector("[data-top-menu-id=\"".concat(topMenuId, "\"]"));
	        if (leftMenuNode) {
	          leftMenuNode.setAttribute("data-link", firstPageLink);
	          var leftMenuLink = leftMenuNode.querySelector('a.menu-item-link');
	          if (leftMenuLink) {
	            leftMenuLink.setAttribute("href", firstPageLink);
	          }
	          if (leftMenuNode.previousElementSibling === _this7.menuContainer.querySelector('#left-menu-empty-item')) {
	            Backend.setFirstPage(firstPageLink);
	          } else {
	            Backend.clearCache();
	          }
	        }
	        _this7.showMessage(firstNode, main_core.Loc.getMessage('MENU_ITEM_MAIN_SECTION_PAGE'));
	      });
	      BX.addCustomEvent("BX.Main.InterfaceButtons:onHideLastVisibleItem", function (bindElement) {
	        _this7.showMessage(bindElement, main_core.Loc.getMessage("MENU_TOP_ITEM_LAST_HIDDEN"));
	      });
	      //when we edit top menu item
	      BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeCreateEditMenu", function (contextMenu, dataItem, topMenu) {
	        var item = _classPrivateMethodGet$1(_this7, _getLeftMenuItemByTopMenuItem, _getLeftMenuItemByTopMenuItem2).call(_this7, dataItem);
	        if (!item && dataItem && main_core.Type.isStringFilled(dataItem.URL) && !dataItem.URL.match(/javascript:/)) {
	          contextMenu.addMenuItem({
	            text: main_core.Loc.getMessage("MENU_ADD_TO_LEFT_MENU"),
	            onclick: function onclick(event, item) {
	              _this7.getItemDirector().saveStandardPage(dataItem);
	              item.getMenuWindow().close();
	            }
	          });
	        } else if (item instanceof ItemUserFavorites) {
	          contextMenu.addMenuItem({
	            text: main_core.Loc.getMessage("MENU_DELETE_FROM_LEFT_MENU"),
	            onclick: function onclick(event, item) {
	              _this7.getItemDirector().deleteStandardPage(dataItem);
	              item.getMenuWindow().close();
	            }
	          });
	        }
	      });
	      //endregion
	      //service event for UI.Toolbar
	      top.BX.addCustomEvent('UI.Toolbar:onRequestMenuItemData', function (_ref7) {
	        var currentFullPath = _ref7.currentFullPath,
	          context = _ref7.context;
	        if (main_core.Type.isStringFilled(currentFullPath)) {
	          BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onSendMenuItemData', [{
	            currentPageInMenu: _this7.menuContainer.querySelector(".menu-item-block[data-link=\"".concat(currentFullPath, "\"]")),
	            context: context
	          }]);
	        }
	      });
	      //When clicked on a start Favorites like
	      main_core_events.EventEmitter.subscribe('UI.Toolbar:onStarClick', function (_ref8) {
	        var _ref8$compatData = babelHelpers.slicedToArray(_ref8.compatData, 1),
	          params = _ref8$compatData[0];
	        if (params.isActive) {
	          _this7.getItemDirector().deleteCurrentPage({
	            context: params.context,
	            pageLink: params.pageLink
	          }).then(function (_ref9) {
	            var itemInfo = _ref9.itemInfo;
	            BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [itemInfo, _this7]);
	            BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	              isActive: false,
	              context: params.context
	            }]);
	          });
	        } else {
	          _this7.getItemDirector().saveCurrentPage({
	            pageTitle: params.pageTitle,
	            pageLink: params.pageLink
	          }).then(function (_ref10) {
	            var itemInfo = _ref10.itemInfo;
	            BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, _this7]);
	            BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	              isActive: true,
	              context: params.context
	            }]);
	          });
	        }
	      });
	      main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onBeforeResetMenu', function (_ref11) {
	        var _ref11$compatData = babelHelpers.slicedToArray(_ref11.compatData, 1),
	          promises = _ref11$compatData[0];
	        promises.push(function () {
	          var p = new BX.Promise();
	          Backend.clearCache().then(function () {
	            p.fulfill();
	          }, function (response) {
	            p.reject("Error: " + response.errors[0].message);
	          });
	          return p;
	        });
	      });
	    }
	  }, {
	    key: "isEditMode",
	    value: function isEditMode() {
	      return this.getItemsController().isEditMode;
	    }
	  }, {
	    key: "isCollapsed",
	    value: function isCollapsed() {
	      return this.isCollapsedMode;
	    }
	  }, {
	    key: "showMessage",
	    value: function showMessage(bindElement, message, position) {
	      var popup = main_popup.PopupManager.create("left-menu-message", bindElement, {
	        content: '<div class="left-menu-message-popup">' + message + '</div>',
	        darkMode: true,
	        offsetTop: position === "right" ? -45 : 2,
	        offsetLeft: position === "right" ? 215 : 0,
	        angle: position === "right" ? {
	          position: "left"
	        } : true,
	        cacheable: false,
	        autoHide: true,
	        events: {
	          onDestroy: function onDestroy() {
	            popup = null;
	          }
	        }
	      });
	      popup.show();
	      setTimeout(function () {
	        if (popup) {
	          popup.close();
	          popup = null;
	        }
	      }, 3000);
	    }
	  }, {
	    key: "showError",
	    value: function showError(bindElement) {
	      this.showMessage(bindElement, main_core.Loc.getMessage('edit_error'));
	    }
	  }, {
	    key: "showGlobalPreset",
	    value: function showGlobalPreset() {
	      this.getDefaultPresetController().show('global');
	    }
	  }, {
	    key: "handleShowHiddenClick",
	    value: function handleShowHiddenClick() {
	      this.getItemsController().toggleHiddenContainer(true);
	    }
	  }, {
	    key: "onHiddenBlockIsVisible",
	    value: function onHiddenBlockIsVisible() {
	      main_core.Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
	      this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = main_core.Loc.getMessage("more_items_hide");
	    }
	  }, {
	    key: "onHiddenBlockIsHidden",
	    value: function onHiddenBlockIsHidden() {
	      main_core.Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
	      this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = main_core.Loc.getMessage("more_items_show");
	    }
	  }, {
	    key: "onHiddenBlockIsEmpty",
	    value: function onHiddenBlockIsEmpty() {
	      main_core.Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	    }
	  }, {
	    key: "onHiddenBlockIsNotEmpty",
	    value: function onHiddenBlockIsNotEmpty() {
	      main_core.Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	    }
	  }, {
	    key: "setDefaultMenu",
	    value: function setDefaultMenu() {
	      ui_dialogs_messagebox.MessageBox.show({
	        message: main_core.Loc.getMessage('MENU_SET_DEFAULT_CONFIRM'),
	        onYes: function onYes(messageBox, button) {
	          button.setWaiting();
	          Backend.setDefaultPreset().then(function () {
	            button.setWaiting(false);
	            messageBox.close();
	            document.location.reload();
	          });
	        },
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL
	      });
	    }
	  }, {
	    key: "clearCompositeCache",
	    value: function clearCompositeCache() {
	      main_core.ajax.runAction('intranet.leftmenu.clearCache', {
	        data: {}
	      });
	    }
	  }, {
	    key: "onGettingSettingMenuItems",
	    // region Events servicing functions
	    value: function onGettingSettingMenuItems() {
	      var _this8 = this;
	      var topPoint = ItemUserFavorites.getActiveTopMenuItem();
	      var menuItemWithAddingToFavorites = null;
	      if (topPoint) {
	        var node = this.menuContainer.querySelector(".menu-item-block[data-link=\"".concat(topPoint['URL'], "\"]"));
	        if (!node) {
	          menuItemWithAddingToFavorites = {
	            text: main_core.Loc.getMessage("MENU_ADD_TO_LEFT_MENU"),
	            onclick: function onclick(event, item) {
	              _this8.getItemDirector().saveStandardPage(topPoint);
	              item.getMenuWindow().destroy();
	            }
	          };
	        } else if (node.getAttribute('data-type') === ItemUserFavorites.code) {
	          menuItemWithAddingToFavorites = {
	            text: main_core.Loc.getMessage("MENU_DELETE_FROM_LEFT_MENU"),
	            onclick: function onclick(event, item) {
	              _this8.getItemDirector().deleteStandardPage(topPoint);
	              item.getMenuWindow().destroy();
	            }
	          };
	        } else {
	          menuItemWithAddingToFavorites = {
	            text: main_core.Loc.getMessage('MENU_DELETE_PAGE_FROM_LEFT_MENU'),
	            className: 'menu-popup-disable-text',
	            onclick: function onclick() {}
	          };
	        }
	      }
	      var menuItems = [{
	        text: main_core.Loc.getMessage('SORT_ITEMS'),
	        onclick: function onclick() {
	          _this8.getItemsController().switchToEditMode();
	        }
	      }, {
	        text: this.isCollapsed() ? main_core.Loc.getMessage('MENU_EXPAND') : main_core.Loc.getMessage('MENU_COLLAPSE'),
	        onclick: function onclick(event, item) {
	          _this8.toggle();
	          item.getMenuWindow().destroy();
	        }
	      }, menuItemWithAddingToFavorites, {
	        text: main_core.Loc.getMessage('MENU_ADD_SELF_PAGE'),
	        onclick: function onclick(event, item) {
	          _this8.getItemDirector().showAddToSelf(_this8.getSettingsController().getContainer());
	        }
	      }, Options.isExtranet ? null : {
	        text: main_core.Loc.getMessage('MENU_SET_DEFAULT2'),
	        onclick: function onclick() {
	          _this8.getDefaultPresetController().show('personal');
	        }
	      }, Options.isExtranet ? null : {
	        text: main_core.Loc.getMessage('MENU_SET_DEFAULT'),
	        onclick: this.setDefaultMenu.bind(this)
	      }];
	      //custom preset
	      if (Options.isAdmin) {
	        var itemText = main_core.Loc.getMessage('MENU_SAVE_CUSTOM_PRESET');
	        if (Options.isCustomPresetRestricted) {
	          itemText += "<span class='menu-lock-icon'></span>";
	        }
	        menuItems.push({
	          html: itemText,
	          className: Options.isCustomPresetRestricted ? ' menu-popup-disable-text' : '',
	          onclick: function onclick(event, item) {
	            if (Options.isCustomPresetRestricted) {
	              BX.UI.InfoHelper.show('limit_office_menu_to_all');
	            } else {
	              _this8.getCustomPresetController().show();
	            }
	          }
	        });
	      }
	      return menuItems.filter(function (value) {
	        return value !== null;
	      });
	    } // endregion
	  }, {
	    key: "handleSiteMapClick",
	    value: function handleSiteMapClick() {
	      this.switchToSlidingMode(false);
	      BX.SidePanel.Instance.open((main_core.Loc.getMessage('SITE_DIR') || '/') + 'sitemap/', {
	        allowChangeHistory: false,
	        customLeftBoundary: 0
	      });
	    }
	  }, {
	    key: "handleHelperClick",
	    value: function handleHelperClick() {
	      this.switchToSlidingMode(false);
	      BX.Helper.show();
	    } // region Sliding functions
	  }, {
	    key: "blockSliding",
	    value: function blockSliding() {
	      this.stopSliding();
	      this.isMenuMouseEnterBlocked = true;
	    }
	  }, {
	    key: "releaseSliding",
	    value: function releaseSliding() {
	      this.isMenuMouseEnterBlocked = false;
	    }
	  }, {
	    key: "stopSliding",
	    value: function stopSliding() {
	      clearTimeout(this.slidingModeTimeoutId);
	      this.slidingModeTimeoutId = 0;
	    }
	  }, {
	    key: "startSliding",
	    value: function startSliding() {
	      this.stopSliding();
	      if (this.isMenuMouseEnterBlocked === true) {
	        return;
	      }
	      this.slidingModeTimeoutId = setTimeout(function () {
	        this.slidingModeTimeoutId = 0;
	        this.switchToSlidingMode(true);
	      }.bind(this), 400);
	    }
	  }, {
	    key: "handleBurgerClick",
	    value: function handleBurgerClick(open) {
	      this.getItemsController().switchToViewMode();
	      this.menuHeaderBurger.classList.add("menu-switcher-hover");
	      this.toggle(open, function () {
	        this.blockSliding();
	        setTimeout(function () {
	          this.menuHeaderBurger.classList.remove("menu-switcher-hover");
	          this.releaseSliding();
	        }.bind(this), 100);
	      }.bind(this));
	    }
	  }, {
	    key: "handleMenuMouseEnter",
	    value: function handleMenuMouseEnter(event) {
	      if (!this.isCollapsed()) {
	        return;
	      }
	      this.startSliding();
	    }
	  }, {
	    key: "handleMenuMouseLeave",
	    value: function handleMenuMouseLeave(event) {
	      this.stopSliding();
	      if (this.isMenuMouseLeaveBlocked.length <= 0) {
	        this.switchToSlidingMode(false);
	      }
	    }
	  }, {
	    key: "handleMenuDoubleClick",
	    value: function handleMenuDoubleClick(event) {
	      if (event.target === this.menuBody) {
	        this.toggle();
	      }
	    }
	  }, {
	    key: "handleHeaderLogoMouserEnter",
	    value: function handleHeaderLogoMouserEnter(event) {
	      BX.addClass(this.headerSettings, "header-logo-block-settings-show");
	    }
	  }, {
	    key: "handleHeaderLogoMouserLeave",
	    value: function handleHeaderLogoMouserLeave(event) {
	      if (!this.headerSettings.hasAttribute("data-rename-portal")) {
	        BX.removeClass(this.headerSettings, "header-logo-block-settings-show");
	      }
	    }
	  }, {
	    key: "handleUpButtonClick",
	    value: function handleUpButtonClick() {
	      this.blockSliding();
	      if (this.isUpButtonReversed()) {
	        window.scrollTo(0, this.lastScrollOffset);
	        this.lastScrollOffset = 0;
	        this.unreverseUpButton();
	      } else {
	        this.lastScrollOffset = window.pageYOffset;
	        window.scrollTo(0, 0);
	        this.reverseUpButton();
	      }
	      setTimeout(this.releaseSliding.bind(this), 100);
	    }
	  }, {
	    key: "handleUpButtonMouseLeave",
	    value: function handleUpButtonMouseLeave() {
	      this.releaseSliding();
	    }
	  }, {
	    key: "handleDocumentScroll",
	    value: function handleDocumentScroll() {
	      _classPrivateMethodGet$1(this, _adjustAdminPanel, _adjustAdminPanel2).call(this);
	      this.applyScrollMode();
	      if (window.pageYOffset > document.documentElement.clientHeight) {
	        this.showUpButton();
	        if (this.isUpButtonReversed()) {
	          this.unreverseUpButton();
	          this.lastScrollOffset = 0;
	        }
	      } else if (!this.isUpButtonReversed()) {
	        this.hideUpButton();
	      }
	      if (window.pageXOffset > 0) {
	        this.menuContainer.style.left = -window.pageXOffset + "px";
	        this.upButton.style.left = -window.pageXOffset + (this.isCollapsed() ? 0 : 172) + "px";
	      } else {
	        this.menuContainer.style.removeProperty("left");
	        this.upButton.style.removeProperty("left");
	      }
	    }
	  }, {
	    key: "switchToSlidingMode",
	    value: function switchToSlidingMode(enable, immediately) {
	      if (enable === false) {
	        this.stopSliding();
	        if (BX.hasClass(this.mainTable, "menu-sliding-mode")) {
	          if (immediately !== true) {
	            BX.addClass(this.mainTable, "menu-sliding-closing-mode");
	          }
	          BX.removeClass(this.mainTable, "menu-sliding-mode menu-sliding-opening-mode");
	        }
	      } else if (this.isCollapsedMode && !BX.hasClass(this.mainTable, "menu-sliding-mode")) {
	        BX.removeClass(this.mainTable, "menu-sliding-closing-mode");
	        if (immediately !== true) {
	          BX.addClass(this.mainTable, "menu-sliding-opening-mode");
	        }
	        BX.addClass(this.mainTable, "menu-sliding-mode");
	      }
	    }
	  }, {
	    key: "handleSlidingTransitionEnd",
	    value: function handleSlidingTransitionEnd(event) {
	      if (event.target === this.menuContainer) {
	        BX.removeClass(this.mainTable, "menu-sliding-opening-mode menu-sliding-closing-mode");
	      }
	    }
	  }, {
	    key: "switchToScrollMode",
	    value: function switchToScrollMode(enable) {
	      if (enable === false) {
	        this.mainTable.classList.remove('menu-scroll-mode');
	      } else if (!this.mainTable.classList.contains('menu-scroll-mode')) {
	        this.mainTable.classList.add('menu-scroll-mode');
	      }
	    } //region logo
	  }, {
	    key: "switchToLogoMaskMode",
	    value: function switchToLogoMaskMode(enable) {
	      if (!_classPrivateMethodGet$1(this, _isLogoMaskNeeded, _isLogoMaskNeeded2).call(this)) {
	        return;
	      }
	      if (enable === false) {
	        this.mainTable.classList.remove('menu-logo-mask-mode');
	      } else if (!this.mainTable.classList.contains('menu-logo-mask-mode')) {
	        this.mainTable.classList.add('menu-logo-mask-mode');
	      }
	    } //endregion
	  }, {
	    key: "toggle",
	    value: function toggle(flag, fn) {
	      var leftColumn = BX("layout-left-column");
	      if (!leftColumn) {
	        return;
	      }
	      var isOpen = !this.mainTable.classList.contains('menu-collapsed-mode');
	      if (flag === isOpen || this.mainTable.classList.contains('menu-animation-mode')) {
	        return;
	      }
	      BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", [flag, this]);
	      var logoImageContainer = this.menuHeader.querySelector(".logo-image-container");
	      if (logoImageContainer) {
	        var logoWidth = this.header.querySelector(".logo-image-container").offsetWidth;
	        if (logoWidth > 0) {
	          logoImageContainer.style.width = logoWidth + "px";
	        }
	      }
	      this.blockSliding();
	      this.switchToSlidingMode(false, true);
	      this.applyScrollMode();
	      leftColumn.style.overflow = "hidden";
	      this.mainTable.classList.add("menu-animation-mode", isOpen ? "menu-animation-closing-mode" : "menu-animation-opening-mode");
	      var menuLinks = [].slice.call(leftColumn.querySelectorAll('.menu-item-link'));
	      var menuMoreBtn = leftColumn.querySelector('.menu-collapsed-more-btn');
	      var menuMoreBtnDefault = leftColumn.querySelector('.menu-default-more-btn');
	      var menuSitemapIcon = leftColumn.querySelector('.menu-sitemap-icon-box');
	      var menuSitemapText = leftColumn.querySelector('.menu-sitemap-btn-text');
	      var menuEmployeesText = leftColumn.querySelector('.menu-invite-employees-text');
	      var menuEmployeesIcon = leftColumn.querySelector('.menu-invite-icon-box');
	      var licenseContainer = leftColumn.querySelector('.menu-license-all-container');
	      var licenseBtn = leftColumn.querySelector('.menu-license-all-default');
	      var licenseHeight = licenseBtn ? licenseBtn.offsetHeight : 0;
	      var licenseCollapsedBtn = leftColumn.querySelector('.menu-license-all-collapsed');
	      var settingsIconBox = this.menuContainer.querySelector(".menu-settings-icon-box");
	      var settingsBtnText = this.menuContainer.querySelector(".menu-settings-btn-text");
	      var helpIconBox = this.menuContainer.querySelector(".menu-help-icon-box");
	      var helpBtnText = this.menuContainer.querySelector(".menu-help-btn-text");
	      var menuTextDivider = leftColumn.querySelector('.menu-item-separator');
	      var menuMoreCounter = leftColumn.querySelector('.menu-item-index-more');
	      var pageHeader = this.mainTable.querySelector(".page-header");
	      var imBar = document.getElementById("bx-im-bar");
	      var imBarWidth = imBar ? imBar.offsetWidth : 0;
	      new BX.easing({
	        duration: 300,
	        start: {
	          translateIcon: isOpen ? -100 : 0,
	          translateText: isOpen ? 0 : -100,
	          translateMoreBtn: isOpen ? 0 : -84,
	          translateLicenseBtn: isOpen ? 0 : -100,
	          heightLicenseBtn: isOpen ? licenseHeight : 40,
	          burgerMenuWidth: isOpen ? 33 : 66,
	          sidebarWidth: isOpen ? 240 : 66,
	          /* these values are duplicated in style.css as well */
	          opacity: isOpen ? 100 : 0,
	          opacityRevert: isOpen ? 0 : 100
	        },
	        finish: {
	          translateIcon: isOpen ? 0 : -100,
	          translateText: isOpen ? -100 : -18,
	          translateMoreBtn: isOpen ? -84 : 0,
	          translateLicenseBtn: isOpen ? -100 : 0,
	          heightLicenseBtn: isOpen ? 40 : licenseHeight,
	          burgerMenuWidth: isOpen ? 66 : 33,
	          sidebarWidth: isOpen ? 66 : 240,
	          opacity: isOpen ? 0 : 100,
	          opacityRevert: isOpen ? 100 : 0
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: function (state) {
	          leftColumn.style.width = state.sidebarWidth + "px";
	          this.menuContainer.style.width = state.sidebarWidth + "px";
	          this.menuHeaderBurger.style.width = state.burgerMenuWidth + "px";
	          this.headerBurger.style.width = state.burgerMenuWidth + "px";

	          //Change this formula in template_style.css as well
	          if (pageHeader) {
	            pageHeader.style.maxWidth = "calc(100vw - " + state.sidebarWidth + "px - " + imBarWidth + "px)";
	          }
	          if (isOpen) {
	            //Closing Mode
	            if (menuSitemapIcon) {
	              menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuSitemapIcon.style.opacity = state.opacityRevert / 100;
	            }
	            if (menuSitemapText) {
	              menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
	              menuSitemapText.style.opacity = state.opacity / 100;
	            }
	            if (menuEmployeesIcon) {
	              menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
	            }
	            if (menuEmployeesText) {
	              menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
	              menuEmployeesText.style.opacity = state.opacity / 100;
	            }
	            settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            settingsIconBox.style.opacity = state.opacityRevert / 100;
	            settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            settingsBtnText.style.opacity = state.opacity / 100;
	            helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            helpIconBox.style.opacity = state.opacityRevert / 100;
	            helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            helpBtnText.style.opacity = state.opacity / 100;
	            menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuMoreBtn.style.opacity = state.opacityRevert / 100;
	            menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
	            menuMoreBtnDefault.style.opacity = state.opacity / 100;
	            if (menuMoreCounter) {
	              menuMoreCounter.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuMoreCounter.style.opacity = state.opacityRevert / 100;
	            }
	            if (licenseContainer) {
	              licenseBtn.style.transform = "translateX(" + state.translateLicenseBtn + "px)";
	              licenseBtn.style.opacity = state.opacity / 100;
	              licenseBtn.style.height = state.heightLicenseBtn + "px";
	              licenseCollapsedBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	              licenseCollapsedBtn.style.opacity = state.opacityRevert / 100;
	            }
	            menuLinks.forEach(function (item) {
	              var menuIcon = item.querySelector(".menu-item-icon-box");
	              var menuLinkText = item.querySelector(".menu-item-link-text");
	              var menuCounter = item.querySelector(".menu-item-index");
	              var menuArrow = item.querySelector('.menu-item-link-arrow');
	              menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
	              menuLinkText.style.opacity = state.opacity / 100;
	              menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuIcon.style.opacity = state.opacityRevert / 100;
	              if (menuArrow) {
	                menuArrow.style.transform = "translateX(" + state.translateText + "px)";
	                menuArrow.style.opacity = state.opacity / 100;
	              }
	              if (menuCounter) {
	                menuCounter.style.transform = "translateX(" + state.translateIcon + "px)";
	                menuCounter.style.opacity = state.opacityRevert / 100;
	              }
	            });
	          } else {
	            //Opening Mode
	            menuTextDivider.style.opacity = 0;
	            if (menuSitemapIcon) {
	              menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuSitemapIcon.style.opacity = state.opacityRevert / 100;
	            }
	            if (menuSitemapText) {
	              menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
	              menuSitemapText.style.opacity = state.opacity / 100;
	            }
	            if (menuEmployeesIcon) {
	              menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
	            }
	            if (menuEmployeesText) {
	              menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
	              menuEmployeesText.style.opacity = state.opacity / 100;
	            }
	            settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            settingsIconBox.style.opacity = state.opacityRevert / 100;
	            settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            settingsBtnText.style.opacity = state.opacity / 100;
	            helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            helpIconBox.style.opacity = state.opacityRevert / 100;
	            helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            helpBtnText.style.opacity = state.opacity / 100;
	            menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuMoreBtn.style.opacity = state.opacityRevert / 100;
	            menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
	            menuMoreBtnDefault.style.opacity = state.opacity / 100;
	            if (menuMoreCounter) {
	              menuMoreCounter.style.transform = "translateX(" + state.translateText + "px)";
	            }
	            if (licenseContainer) {
	              licenseBtn.style.transform = "translateX(" + state.translateLicenseBtn + "px)";
	              licenseBtn.style.opacity = state.opacity / 100;
	              licenseBtn.style.height = state.heightLicenseBtn + "px";
	              licenseCollapsedBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	              licenseCollapsedBtn.style.opacity = state.opacityRevert / 100;
	            }
	            menuLinks.forEach(function (item) {
	              var menuIcon = item.querySelector(".menu-item-icon-box");
	              var menuLinkText = item.querySelector(".menu-item-link-text");
	              var menuCounter = item.querySelector(".menu-item-index");
	              var menuArrow = item.querySelector('.menu-item-link-arrow');
	              menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
	              menuLinkText.style.opacity = state.opacity / 100;
	              menuLinkText.style.display = "inline-block";
	              menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuIcon.style.opacity = state.opacityRevert / 100;
	              if (menuArrow) {
	                menuArrow.style.transform = "translateX(" + state.translateText + "px)";
	                // menuArrow.style.opacity = state.opacityRevert / 100;
	              }

	              if (menuCounter) {
	                menuCounter.style.transform = "translateX(" + state.translateText + "px)";
	              }
	            });
	          }
	          var event = document.createEvent("Event");
	          event.initEvent("resize", true, true);
	          window.dispatchEvent(event);
	        }.bind(this),
	        complete: function () {
	          if (isOpen) {
	            this.isCollapsedMode = true;
	            BX.addClass(this.mainTable, "menu-collapsed-mode");
	          } else {
	            this.isCollapsedMode = false;
	            BX.removeClass(this.mainTable, "menu-collapsed-mode");
	          }
	          BX.removeClass(this.mainTable, "menu-animation-mode menu-animation-opening-mode menu-animation-closing-mode");
	          var containers = [leftColumn, menuTextDivider, this.menuHeaderBurger, this.headerBurger, settingsIconBox, settingsBtnText, helpIconBox, helpBtnText, menuMoreBtnDefault, menuMoreBtn, logoImageContainer, menuSitemapIcon, menuSitemapText, menuEmployeesIcon, menuEmployeesText, menuMoreCounter, licenseBtn, licenseCollapsedBtn, this.menuContainer, pageHeader];
	          containers.forEach(function (container) {
	            if (container) {
	              container.style.cssText = "";
	            }
	          });
	          menuLinks.forEach(function (item) {
	            var menuIcon = item.querySelector(".menu-item-icon-box");
	            var menuLinkText = item.querySelector(".menu-item-link-text");
	            var menuCounter = item.querySelector(".menu-item-index");
	            var menuArrow = item.querySelector('.menu-item-link-arrow');
	            item.style.cssText = "";
	            menuLinkText.style.cssText = "";
	            menuIcon.style.cssText = "";
	            if (menuArrow) {
	              menuArrow.style.cssText = "";
	            }
	            if (menuCounter) {
	              menuCounter.style.cssText = "";
	            }
	          });
	          this.releaseSliding();
	          _classPrivateMethodGet$1(this, _adjustAdminPanel, _adjustAdminPanel2).call(this);
	          if (BX.type.isFunction(fn)) {
	            fn();
	          }
	          Backend.toggleMenu(isOpen);
	          var event = document.createEvent("Event");
	          event.initEvent("resize", true, true);
	          window.dispatchEvent(event);
	        }.bind(this)
	      }).animate();
	    } //endregion
	  }, {
	    key: "handleViewMode",
	    value: function handleViewMode() {
	      this.getItemsController().switchToViewMode();
	    }
	  }, {
	    key: "applyScrollMode",
	    value: function applyScrollMode() {
	      this.switchToLogoMaskMode(true);
	      var threshold = this.scrollModeThreshold + Utils.adminPanel.height;
	      this.switchToScrollMode(window.pageYOffset > threshold);
	    }
	  }, {
	    key: "handleGroupPanelOpen",
	    value: function handleGroupPanelOpen() {
	      this.isMenuMouseLeaveBlocked.push('group');
	    }
	  }, {
	    key: "handleGroupPanelClose",
	    value: function handleGroupPanelClose() {
	      this.isMenuMouseLeaveBlocked.pop();
	    }
	  }, {
	    key: "showUpButton",
	    value: function showUpButton() {
	      this.menuContainer.classList.add("menu-up-button-active");
	    }
	  }, {
	    key: "hideUpButton",
	    value: function hideUpButton() {
	      this.menuContainer.classList.remove("menu-up-button-active");
	    }
	  }, {
	    key: "reverseUpButton",
	    value: function reverseUpButton() {
	      this.menuContainer.classList.add("menu-up-button-reverse");
	    }
	  }, {
	    key: "unreverseUpButton",
	    value: function unreverseUpButton() {
	      this.menuContainer.classList.remove("menu-up-button-reverse");
	    }
	  }, {
	    key: "isUpButtonReversed",
	    value: function isUpButtonReversed() {
	      return this.menuContainer.classList.contains("menu-up-button-reverse");
	    }
	  }, {
	    key: "isDefaultTheme",
	    value: function isDefaultTheme() {
	      return document.body.classList.contains("bitrix24-default-theme");
	    }
	  }, {
	    key: "getTopPadding",
	    value: function getTopPadding() {
	      return this.isDefaultTheme() ? 0 : 9;
	    } // region Public functions
	  }, {
	    key: "initPagetitleStar",
	    value: function initPagetitleStar() {
	      return ItemUserFavorites.isCurrentPageStandard(ItemUserFavorites.getActiveTopMenuItem());
	    }
	  }, {
	    key: "getStructureForHelper",
	    value: function getStructureForHelper() {
	      var _this9 = this;
	      var items = {
	        menu: {}
	      };
	      ["show", "hide"].forEach(function (state) {
	        Array.from(_this9.menuContainer.querySelectorAll("[data-status=\"".concat(state, "\"][data-type=\"").concat(ItemSystem.code, "\"]"))).forEach(function (node) {
	          items[state] = items[state] || [];
	          items[state].push(node.getAttribute("data-id"));
	        });
	      });
	      return items;
	    }
	  }, {
	    key: "showItemWarning",
	    value: function showItemWarning(_ref12) {
	      var itemId = _ref12.itemId,
	        title = _ref12.title,
	        events = _ref12.events;
	      if (this.getItemsController().items.has(itemId)) {
	        this.getItemsController().items.get(itemId).showWarning(title, events);
	      }
	    }
	  }, {
	    key: "removeItemWarning",
	    value: function removeItemWarning(itemId) {
	      if (this.getItemsController().items.has(itemId)) {
	        this.getItemsController().items.get(itemId).removeWarning();
	      }
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
	        counters['workgroups'] = Object.entries(this.workgroupsCounterData).reduce(function (prevValue, _ref13) {
	          var _ref14 = babelHelpers.slicedToArray(_ref13, 2),
	            curValue = _ref14[1];
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
	    } //endregion
	  }]);
	  return Menu;
	}();
	function _getLeftMenuItemByTopMenuItem2(_ref15) {
	  var _item;
	  var DATA_ID = _ref15.DATA_ID,
	    NODE = _ref15.NODE;
	  var item = this.getItemsController().items.get(DATA_ID);
	  if (!item) {
	    var topMenuId = NODE.getAttribute('data-top-menu-id');
	    if (NODE === NODE.parentNode.querySelector('[data-top-menu-id]')) {
	      var leftMenuNode = this.menuItemsBlock.querySelector("[data-top-menu-id=\"".concat(topMenuId, "\"]"));
	      if (leftMenuNode) {
	        item = this.getItemsController().items.get(leftMenuNode.getAttribute('data-id'));
	      }
	    }
	  }
	  return (_item = item) !== null && _item !== void 0 ? _item : null;
	}
	function _isLogoMaskNeeded2() {
	  var _this10 = this;
	  return this.cache.remember('isLogoMaskNeeded', function () {
	    var menuHeaderLogo = _this10.menuHeader.querySelector(".logo");
	    var result = false;
	    if (menuHeaderLogo && !menuHeaderLogo.querySelector(".logo-image-container")) {
	      var widthMeasure = menuHeaderLogo.offsetWidth === 0 ? _this10.header.querySelector(".logo") ? _this10.header.querySelector(".logo").offsetWidth : 0 : menuHeaderLogo.offsetWidth;
	      result = widthMeasure > 200;
	    }
	    return result;
	  });
	}
	function _adjustAdminPanel2() {
	  var _this11 = this;
	  if (!this['menuAdjustAdminPanel']) {
	    this['menuAdjustAdminPanel'] = function (_ref16) {
	      var data = _ref16.data;
	      _this11.menuContainer.style.top = [data, 'px'].join('');
	    };
	    main_core_events.EventEmitter.subscribe(Utils.adminPanel, Options.eventName('onPanelHasChanged'), this['menuAdjustAdminPanel']);
	  }
	  this.menuContainer.style.top = [Utils.adminPanel.top, 'px'].join('');
	}

	exports.Menu = Menu;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI,BX,BX,BX.Main,BX.Event,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map

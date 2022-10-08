this.BX = this.BX || {};
(function (exports,main_core,salescenter_manager,main_core_events,Tile) {
	'use strict';

	var EventTypes = {
	  AppLocalSliderClose: 'app-local:slider-on-close',
	  AppSliderSliderClose: 'app-slider:slider-on-close',
	  AppUninstalledSliderClose: 'app-uninstalled:slider-on-close'
	};

	var AppLocal = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(AppLocal, _EventEmitter);

	  function AppLocal() {
	    var _this;

	    babelHelpers.classCallCheck(this, AppLocal);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AppLocal).call(this));

	    _this.setEventNamespace('BX.Salescenter.TileSlider.AppSystem');

	    return _this;
	  }

	  babelHelpers.createClass(AppLocal, [{
	    key: "open",
	    value: function open(tile) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      this.openLink(tile.link, options, {
	        data: {
	          type: tile.getType()
	        }
	      });
	    }
	  }, {
	    key: "openLink",
	    value: function openLink(link) {
	      var _this2 = this;

	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var eventOptions = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      salescenter_manager.Manager.openSlider(link, options).then(function () {
	        return _this2.emit(EventTypes.AppLocalSliderClose, new main_core_events.BaseEvent(eventOptions));
	      });
	    }
	  }]);
	  return AppLocal;
	}(main_core_events.EventEmitter);

	var AppInstalled = /*#__PURE__*/function () {
	  function AppInstalled() {
	    babelHelpers.classCallCheck(this, AppInstalled);
	  }

	  babelHelpers.createClass(AppInstalled, [{
	    key: "open",
	    value: function open(tile) {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "getRestApp", {
	        data: {
	          code: tile.code
	        }
	      }).then(function (response) {
	        var app = response.data;

	        if (app.TYPE === "A") ; else {
	          BX.rest.AppLayout.openApplication(tile.id);
	        }
	      }.bind(this))["catch"](function (response) {
	        this.errorPopup(" ", response.errors.pop().message);
	      }.bind(this));
	    }
	  }, {
	    key: "errorPopup",
	    value: function errorPopup(title, text) {
	      var popup = new PopupWindow('rest-app-error-alert', null, {
	        closeIcon: true,
	        closeByEsc: true,
	        autoHide: false,
	        titleBar: title,
	        content: text,
	        zIndex: 16000,
	        overlay: {
	          color: 'gray',
	          opacity: 30
	        },
	        buttons: [new PopupWindowButton({
	          'id': 'close',
	          'text': main_core.Loc.getMessage('SALESCENTER_JS_POPUP_CLOSE'),
	          'events': {
	            'click': function click() {
	              popup.close();
	            }
	          }
	        })],
	        events: {
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            popup = null;
	          }
	        }
	      });
	      popup.show();
	    }
	  }]);
	  return AppInstalled;
	}();

	var URL = '/marketplace/detail/#app#/';

	var AppUninstalled = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(AppUninstalled, _EventEmitter);

	  function AppUninstalled() {
	    var _this;

	    babelHelpers.classCallCheck(this, AppUninstalled);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AppUninstalled).call(this));

	    _this.setEventNamespace('BX.Salescenter.Marketplace.TileSlider.AppUninstalled');

	    return _this;
	  }

	  babelHelpers.createClass(AppUninstalled, [{
	    key: "open",
	    value: function open(tile) {
	      var _this2 = this;

	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var url = URL.replace("#app#", encodeURIComponent(tile.code));
	      salescenter_manager.Manager.openSlider(url, options).then(function () {
	        return _this2.emit(EventTypes.AppUninstalledSliderClose);
	      });
	    }
	  }]);
	  return AppUninstalled;
	}(main_core_events.EventEmitter);

	var AppSlider = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(AppSlider, _EventEmitter);

	  function AppSlider() {
	    var _this;

	    babelHelpers.classCallCheck(this, AppSlider);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AppSlider).call(this));

	    _this.setEventNamespace('BX.Salescenter.AppSlider');

	    return _this;
	  }

	  babelHelpers.createClass(AppSlider, [{
	    key: "openAppLocal",
	    value: function openAppLocal(tile) {
	      var _this2 = this;

	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	      if (tile.hasOwnProperty('width')) {
	        options.width = Number(tile.width);
	      }

	      if (tile.getType() === Tile.Marketplace.type()) {
	        this.openApp(tile, options);
	      } else {
	        var system = new AppLocal();
	        system.open(tile, options);
	        system.subscribe(EventTypes.AppLocalSliderClose, function (e) {
	          return _this2.emit(EventTypes.AppSliderSliderClose, new main_core_events.BaseEvent({
	            data: e.data
	          }));
	        });
	      }
	    }
	  }, {
	    key: "openAppLocalLink",
	    value: function openAppLocalLink(link) {
	      var _this3 = this;

	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var system = new AppLocal();
	      system.openLink(link, options);
	      system.subscribe(EventTypes.AppLocalSliderClose, function (e) {
	        return _this3.emit(EventTypes.AppSliderSliderClose, new main_core_events.BaseEvent({
	          data: e.data
	        }));
	      });
	    }
	  }, {
	    key: "openApp",
	    value: function openApp(tile) {
	      var _this4 = this;

	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	      if (tile.isInstalled()) {
	        new AppInstalled().open(tile);
	      } else {
	        var uninstalled = new AppUninstalled();
	        uninstalled.open(tile, options);
	        uninstalled.subscribe(EventTypes.AppUninstalledSliderClose, function () {
	          return _this4.emit(EventTypes.AppSliderSliderClose, new main_core_events.BaseEvent({
	            data: {
	              type: Tile.Marketplace.type()
	            }
	          }));
	        });
	      }
	    }
	  }]);
	  return AppSlider;
	}(main_core_events.EventEmitter);

	exports.AppSlider = AppSlider;
	exports.AppLocal = AppLocal;
	exports.EventTypes = EventTypes;
	exports.AppInstalled = AppInstalled;
	exports.AppUninstalled = AppUninstalled;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX.Salescenter,BX.Event,BX.Salescenter.Tile));
//# sourceMappingURL=marketplace.bundle.js.map

/* eslint-disable */
(function (exports,main_core,main_core_events,ui_dialogs_messagebox) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _initHints = /*#__PURE__*/new WeakSet();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var UnusedElementsGridManager = /*#__PURE__*/function () {
	  function UnusedElementsGridManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, UnusedElementsGridManager);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _subscribeToEvents);
	    _classPrivateMethodInitSpec(this, _initHints);
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	  }
	  babelHelpers.createClass(UnusedElementsGridManager, [{
	    key: "deleteElement",
	    value: function deleteElement(elements) {
	      var _this = this;
	      var popupTitle = main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TITLE_SINGLE');
	      var popupText = main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TEXT_SINGLE');
	      if (elements.length > 1) {
	        popupTitle = main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TITLE');
	        popupText = main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TEXT');
	      }
	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        title: popupTitle,
	        message: popupText,
	        maxWidth: 300,
	        minWidth: 300,
	        buttons: [new BX.UI.Button({
	          color: BX.UI.Button.Color.DANGER,
	          text: main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_BUTTON_YES'),
	          onclick: function onclick(button) {
	            button.setWaiting();
	            main_core.ajax.runComponentAction('bitrix:biconnector.unusedelements', 'delete', {
	              mode: 'class',
	              data: {
	                elements: elements
	              }
	            }).then(function () {
	              babelHelpers.classPrivateFieldGet(_this, _grid).reload();
	              messageBox.close();
	            })["catch"](function (response) {
	              messageBox.close();
	              if (response.errors) {
	                _classPrivateMethodGet(_this, _notifyErrors, _notifyErrors2).call(_this, response.errors);
	              }
	            });
	          }
	        }), new BX.UI.CancelButton({
	          text: main_core.Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_BUTTON_NO'),
	          onclick: function onclick() {
	            return messageBox.close();
	          }
	        })]
	      });
	      messageBox.show();
	    } // noinspection JSUnusedGlobalSymbols
	  }, {
	    key: "openElement",
	    value: function openElement(openUrl) {
	      var _this2 = this;
	      babelHelpers.classPrivateFieldGet(this, _grid).tableFade();
	      main_core.ajax.runComponentAction('bitrix:biconnector.unusedelements', 'getOpenUrl', {
	        mode: 'class',
	        data: {
	          openUrl: openUrl
	        }
	      }).then(function (response) {
	        var link = response.data;
	        if (link) {
	          window.open(link, '_blank').focus();
	        }
	        babelHelpers.classPrivateFieldGet(_this2, _grid).tableUnfade();
	      })["catch"](function (response) {
	        if (response.errors) {
	          _classPrivateMethodGet(_this2, _notifyErrors, _notifyErrors2).call(_this2, response.errors);
	        }
	        babelHelpers.classPrivateFieldGet(_this2, _grid).tableUnfade();
	      });
	    } // noinspection JSUnusedGlobalSymbols
	  }, {
	    key: "deleteSelectedElements",
	    value: function deleteSelectedElements() {
	      var selectedElements = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getSelected();
	      var elementsToDelete = [];
	      var _iterator = _createForOfIteratorHelper(selectedElements),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var row = _step.value;
	          var rowData = row.getEditData();
	          elementsToDelete.push({
	            elementId: rowData.EXTERNAL_ID,
	            elementType: rowData.TYPE
	          });
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      this.deleteElement(elementsToDelete);
	    }
	  }]);
	  return UnusedElementsGridManager;
	}();
	function _initHints2() {
	  var manager = BX.UI.Hint.createInstance({
	    popupParameters: {
	      autoHide: true
	    }
	  });
	  manager.init(babelHelpers.classPrivateFieldGet(this, _grid).getContainer());
	}
	function _subscribeToEvents2() {
	  var _this3 = this;
	  main_core_events.EventEmitter.subscribe('Grid::updated', function () {
	    _classPrivateMethodGet(_this3, _initHints, _initHints2).call(_this3);
	  });
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').UnusedElementsGridManager = UnusedElementsGridManager;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map

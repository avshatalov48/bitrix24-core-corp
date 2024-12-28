/* eslint-disable */
(function (exports,main_core,main_core_events) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _initHints = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var ExternalSourceManager = /*#__PURE__*/function () {
	  function ExternalSourceManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, ExternalSourceManager);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _initHints);
	    _classPrivateMethodInitSpec(this, _subscribeToEvents);
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    babelHelpers.classPrivateFieldSet(this, _filter, BX.Main.filterManager.getById(props.gridId));
	    _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	  }
	  babelHelpers.createClass(ExternalSourceManager, [{
	    key: "getGrid",
	    value: function getGrid() {
	      return babelHelpers.classPrivateFieldGet(this, _grid);
	    }
	  }, {
	    key: "getFilter",
	    value: function getFilter() {
	      return babelHelpers.classPrivateFieldGet(this, _filter);
	    }
	  }, {
	    key: "handleCreatedByClick",
	    value: function handleCreatedByClick(ownerData) {
	      this.handleDatasetFilterChange(_objectSpread({
	        fieldId: 'CREATED_BY_ID'
	      }, ownerData));
	    }
	  }, {
	    key: "handleDatasetFilterChange",
	    value: function handleDatasetFilterChange(fieldData) {
	      var _filterFieldsValues$f, _filterFieldsValues;
	      var filterFieldsValues = this.getFilter().getFilterFieldsValues();
	      var currentFilteredField = (_filterFieldsValues$f = filterFieldsValues[fieldData.fieldId]) !== null && _filterFieldsValues$f !== void 0 ? _filterFieldsValues$f : [];
	      var currentFilteredFieldLabel = (_filterFieldsValues = filterFieldsValues["".concat(fieldData.fieldId, "_label")]) !== null && _filterFieldsValues !== void 0 ? _filterFieldsValues : [];
	      if (fieldData.IS_FILTERED) {
	        currentFilteredField = currentFilteredField.filter(function (value) {
	          return parseInt(value, 10) !== fieldData.ID;
	        });
	        currentFilteredFieldLabel = currentFilteredFieldLabel.filter(function (value) {
	          return value !== fieldData.TITLE;
	        });
	      } else if (!currentFilteredField.includes(fieldData.ID)) {
	        currentFilteredField.push(fieldData.ID);
	        currentFilteredFieldLabel.push(fieldData.TITLE);
	      }
	      var filterApi = this.getFilter().getApi();
	      var filterToExtend = {};
	      filterToExtend[fieldData.fieldId] = currentFilteredField;
	      filterToExtend["".concat(fieldData.fieldId, "_label")] = currentFilteredFieldLabel;
	      filterApi.extendFilter(filterToExtend);
	      filterApi.apply();
	    }
	  }, {
	    key: "openSourceDetail",
	    value: function openSourceDetail(id, moduleId) {
	      var sliderLink = '';
	      var sliderWidth = 0;
	      var isCacheable = false;
	      if (moduleId === 'BI') {
	        sliderLink = new main_core.Uri("/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=".concat(id));
	        sliderWidth = 564;
	        isCacheable = false;
	      } else if (moduleId === 'CRM') {
	        sliderLink = new main_core.Uri("/crm/tracking/source/edit/".concat(id, "/"));
	        sliderWidth = 900;
	        isCacheable = true;
	      } else {
	        return;
	      }
	      BX.SidePanel.Instance.open(sliderLink.toString(), {
	        width: sliderWidth,
	        allowChangeHistory: false,
	        cacheable: isCacheable
	      });
	    }
	  }, {
	    key: "openCreateSourceSlider",
	    value: function openCreateSourceSlider() {
	      var sliderLink = new main_core.Uri('/bitrix/components/bitrix/biconnector.apachesuperset.source.connect.list/slider.php');
	      BX.SidePanel.Instance.open(sliderLink.toString(), {
	        width: 900,
	        allowChangeHistory: false,
	        cacheable: false
	      });
	    }
	  }, {
	    key: "changeActivitySource",
	    value: function changeActivitySource(id, moduleId) {
	      var _this = this;
	      main_core.ajax.runAction('biconnector.externalsource.source.changeActivity', {
	        data: {
	          id: id,
	          moduleId: moduleId
	        }
	      }).then(function () {
	        _this.getGrid().reload();
	      })["catch"](function (response) {
	        if (response.errors) {
	          _classPrivateMethodGet(_this, _notifyErrors, _notifyErrors2).call(_this, response.errors);
	        }
	      });
	    }
	  }, {
	    key: "deleteSource",
	    value: function deleteSource(id, moduleId) {
	      var _this2 = this;
	      main_core.ajax.runAction('biconnector.externalsource.source.delete', {
	        data: {
	          id: id,
	          moduleId: moduleId
	        }
	      }).then(function () {
	        _this2.getGrid().reload();
	      })["catch"](function (response) {
	        if (response.errors) {
	          _classPrivateMethodGet(_this2, _notifyErrors, _notifyErrors2).call(_this2, response.errors);
	        }
	      });
	    }
	  }]);
	  return ExternalSourceManager;
	}();
	function _subscribeToEvents2() {
	  var _this3 = this;
	  main_core_events.EventEmitter.subscribe('Grid::updated', function () {
	    _classPrivateMethodGet(_this3, _initHints, _initHints2).call(_this3);
	  });
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getData = event.getData(),
	      _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	      messageEvent = _event$getData2[0];
	    var eventId = messageEvent.getEventId();
	    if (eventId === 'BIConnector:ExternalConnectionGrid:reload' || eventId === 'BIConnector:ExternalConnection:onConnectionCreated') {
	      babelHelpers.classPrivateFieldGet(_this3, _grid).reload();
	    }
	  });
	}
	function _initHints2() {
	  var manager = BX.UI.Hint.createInstance({
	    popupParameters: {
	      autoHide: true
	    }
	  });
	  manager.init(babelHelpers.classPrivateFieldGet(this, _grid).getContainer());
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').ExternalSourceManager = ExternalSourceManager;

}((this.window = this.window || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map

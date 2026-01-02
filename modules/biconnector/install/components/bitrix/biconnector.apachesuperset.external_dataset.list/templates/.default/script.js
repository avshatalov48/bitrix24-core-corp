/* eslint-disable */
(function (exports,biconnector_datasetImport_fileExport,main_popup,main_core,main_core_events,ui_buttons,ui_system_dialog) {
	'use strict';

	var _templateObject;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _initHints = /*#__PURE__*/new WeakSet();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _getDeleteDatasetPopup = /*#__PURE__*/new WeakSet();
	var _getDeleteDatasetPopupContent = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var ExternalDatasetManager = /*#__PURE__*/function () {
	  function ExternalDatasetManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, ExternalDatasetManager);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _getDeleteDatasetPopupContent);
	    _classPrivateMethodInitSpec(this, _getDeleteDatasetPopup);
	    _classPrivateMethodInitSpec(this, _subscribeToEvents);
	    _classPrivateMethodInitSpec(this, _initHints);
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
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	    _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	  }
	  babelHelpers.createClass(ExternalDatasetManager, [{
	    key: "handleCreatedByClick",
	    value: function handleCreatedByClick(ownerData) {
	      this.handleDatasetFilterChange(_objectSpread({
	        fieldId: 'CREATED_BY_ID'
	      }, ownerData));
	    }
	  }, {
	    key: "handleUpdatedByClick",
	    value: function handleUpdatedByClick(ownerData) {
	      this.handleDatasetFilterChange(_objectSpread({
	        fieldId: 'UPDATED_BY_ID'
	      }, ownerData));
	    }
	  }, {
	    key: "handleSourceClick",
	    value: function handleSourceClick(sourceData) {
	      this.handleDatasetFilterChange(_objectSpread({
	        fieldId: 'SOURCE.ID'
	      }, sourceData));
	    }
	  }, {
	    key: "handleDatasetFilterChange",
	    value: function handleDatasetFilterChange(fieldData) {
	      var _filterFieldsValues$f, _filterFieldsValues;
	      var filterFieldsValues = babelHelpers.classPrivateFieldGet(this, _filter).getFilterFieldsValues();
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
	      var filterApi = babelHelpers.classPrivateFieldGet(this, _filter).getApi();
	      var filterToExtend = {};
	      filterToExtend[fieldData.fieldId] = currentFilteredField;
	      filterToExtend["".concat(fieldData.fieldId, "_label")] = currentFilteredFieldLabel;
	      filterApi.extendFilter(filterToExtend);
	      filterApi.apply();
	    }
	  }, {
	    key: "exportDataset",
	    value: function exportDataset(id) {
	      var _this = this;
	      babelHelpers.classPrivateFieldGet(this, _grid).tableFade();
	      biconnector_datasetImport_fileExport.FileExport.getInstance().downloadOnce(id).then(function () {
	        babelHelpers.classPrivateFieldGet(_this, _grid).tableUnfade();
	      })["catch"](function () {
	        babelHelpers.classPrivateFieldGet(_this, _grid).tableUnfade();
	      });
	    }
	  }, {
	    key: "deleteDataset",
	    value: function deleteDataset(id) {
	      _classPrivateMethodGet(this, _getDeleteDatasetPopup, _getDeleteDatasetPopup2).call(this, id).show();
	    }
	  }, {
	    key: "deleteDatasetAjaxAction",
	    value: function deleteDatasetAjaxAction(datasetId) {
	      return main_core.ajax.runAction('biconnector.externalsource.dataset.delete', {
	        data: {
	          id: datasetId
	        }
	      });
	    }
	  }, {
	    key: "createChart",
	    value: function createChart(datasetId) {
	      var _this2 = this;
	      babelHelpers.classPrivateFieldGet(this, _grid).tableFade();
	      main_core.ajax.runAction('biconnector.externalsource.dataset.getEditUrl', {
	        data: {
	          id: datasetId
	        }
	      }).then(function (response) {
	        var link = response.data;
	        if (link) {
	          window.open(link, '_blank').focus();
	        }
	        babelHelpers.classPrivateFieldGet(_this2, _grid).tableUnfade();
	      })["catch"](function (response) {
	        babelHelpers.classPrivateFieldGet(_this2, _grid).tableUnfade();
	        if (response.errors) {
	          _classPrivateMethodGet(_this2, _notifyErrors, _notifyErrors2).call(_this2, response.errors);
	        }
	      });
	    }
	  }, {
	    key: "showSupersetError",
	    value: function showSupersetError() {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Text.encode(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_ERROR_SUPERSET'))
	      });
	    }
	  }]);
	  return ExternalDatasetManager;
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
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getData = event.getData(),
	      _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	      messageEvent = _event$getData2[0];
	    if (messageEvent.getEventId() === 'BIConnector.dataset-import:onDatasetCreated') {
	      babelHelpers.classPrivateFieldGet(_this3, _grid).reload();
	    }
	  });
	  main_core_events.EventEmitter.subscribe('Grid::updated', function () {
	    _classPrivateMethodGet(_this3, _initHints, _initHints2).call(_this3);
	  });
	}
	function _getDeleteDatasetPopup2(id) {
	  var _this4 = this;
	  var deleteDatasetPopupInstance = new ui_system_dialog.Dialog({
	    title: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_TITLE_MSGVER_1'),
	    content: _classPrivateMethodGet(this, _getDeleteDatasetPopupContent, _getDeleteDatasetPopupContent2).call(this),
	    centerButtons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_CAPTION_YES'),
	      size: ui_buttons.ButtonSize.LARGE,
	      style: ui_buttons.AirButtonStyle.FILLED,
	      useAirDesign: true,
	      onclick: function onclick(button) {
	        button.setWaiting();
	        _this4.deleteDatasetAjaxAction(id).then(function () {
	          babelHelpers.classPrivateFieldGet(_this4, _grid).reload();
	          deleteDatasetPopupInstance.hide();
	        })["catch"](function (response) {
	          deleteDatasetPopupInstance.hide();
	          if (response.errors) {
	            _classPrivateMethodGet(_this4, _notifyErrors, _notifyErrors2).call(_this4, response.errors);
	          }
	        });
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_CAPTION_NO'),
	      size: ui_buttons.ButtonSize.LARGE,
	      style: ui_buttons.AirButtonStyle.PLAIN,
	      useAirDesign: true,
	      onclick: function onclick() {
	        return deleteDatasetPopupInstance.hide();
	      }
	    })],
	    hasOverlay: true,
	    width: 400
	  });
	  return deleteDatasetPopupInstance;
	}
	function _getDeleteDatasetPopupContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-delete-dataset-popup-content\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_DESCRIPTION_MSGVER_1'));
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').ExternalDatasetManager = ExternalDatasetManager;

}((this.window = this.window || {}),BX.BIConnector.DatasetImport,BX.Main,BX,BX.Event,BX.UI,BX.UI.System));
//# sourceMappingURL=script.js.map

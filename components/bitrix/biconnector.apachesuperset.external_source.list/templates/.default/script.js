/* eslint-disable */
(function (exports,biconnector_grid_editableColumns,main_core,ui_buttons,ui_system_dialog,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _sourceTitleList = /*#__PURE__*/new WeakMap();
	var _datasetUrl = /*#__PURE__*/new WeakMap();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _initHints = /*#__PURE__*/new WeakSet();
	var _showDeleteSourcePopup = /*#__PURE__*/new WeakSet();
	var _getDeleteSourcePopupContent = /*#__PURE__*/new WeakSet();
	var _getDeleteSourceWithDatasetDialog = /*#__PURE__*/new WeakSet();
	var _getDeleteSourceWithDatasetDialogContent = /*#__PURE__*/new WeakSet();
	var _openDatasetSlider = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var ExternalSourceManager = /*#__PURE__*/function () {
	  function ExternalSourceManager(props) {
	    var _BX$Main$gridManager$, _props$sourceTitleLis;
	    babelHelpers.classCallCheck(this, ExternalSourceManager);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _openDatasetSlider);
	    _classPrivateMethodInitSpec(this, _getDeleteSourceWithDatasetDialogContent);
	    _classPrivateMethodInitSpec(this, _getDeleteSourceWithDatasetDialog);
	    _classPrivateMethodInitSpec(this, _getDeleteSourcePopupContent);
	    _classPrivateMethodInitSpec(this, _showDeleteSourcePopup);
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
	    _classPrivateFieldInitSpec(this, _sourceTitleList, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _datasetUrl, {
	      writable: true,
	      value: '/bi/dataset/'
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    babelHelpers.classPrivateFieldSet(this, _filter, BX.Main.filterManager.getById(props.gridId));
	    babelHelpers.classPrivateFieldSet(this, _sourceTitleList, (_props$sourceTitleLis = props.sourceTitleList) !== null && _props$sourceTitleLis !== void 0 ? _props$sourceTitleLis : {});
	    _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	    biconnector_grid_editableColumns.EditableColumnManager.init(props.gridId, [{
	      name: 'DESCRIPTION',
	      saveEndpoint: 'biconnector.externalsource.source.updateComment',
	      onValueCheck: function onValueCheck() {
	        return true;
	      },
	      onSave: function onSave() {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Text.encode(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COMMENT_UPDATED'))
	        });
	      }
	    }]);
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
	      if (moduleId === 'BI') {
	        sliderLink = new main_core.Uri("/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=".concat(id));
	        sliderWidth = 564;
	      } else if (moduleId === 'CRM') {
	        sliderLink = new main_core.Uri("/crm/tracking/source/edit/".concat(id, "/"));
	        sliderWidth = 900;
	      } else {
	        return;
	      }
	      BX.SidePanel.Instance.open(sliderLink.toString(), {
	        width: sliderWidth,
	        allowChangeHistory: false,
	        cacheable: false,
	        events: {
	          onClose: BX.BIConnector.TrackingAnalyticsHandler.handleSliderClose
	        }
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
	      main_core.ajax.runAction('biconnector.externalsource.source.validateBeforeDelete', {
	        data: {
	          id: id,
	          moduleId: moduleId
	        }
	      }).then(function () {
	        _classPrivateMethodGet(_this2, _showDeleteSourcePopup, _showDeleteSourcePopup2).call(_this2, id, moduleId);
	      })["catch"](function (response) {
	        if (response.errors) {
	          if (response.errors.some(function (_ref) {
	            var code = _ref.code;
	            return code === 409;
	          })) {
	            _classPrivateMethodGet(_this2, _getDeleteSourceWithDatasetDialog, _getDeleteSourceWithDatasetDialog2).call(_this2, id).show();
	          } else {
	            _classPrivateMethodGet(_this2, _notifyErrors, _notifyErrors2).call(_this2, response.errors);
	          }
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
	    if (eventId === 'BIConnector:ExternalConnectionGrid:reload' || eventId === 'BIConnector:ExternalConnection:onConnectionSave') {
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
	function _showDeleteSourcePopup2(id, moduleId) {
	  var _this4 = this;
	  var messageBox = new ui_system_dialog.Dialog({
	    content: _classPrivateMethodGet(this, _getDeleteSourcePopupContent, _getDeleteSourcePopupContent2).call(this),
	    centerButtons: [new ui_buttons.Button({
	      color: ui_buttons.AirButtonStyle.FILLED,
	      useAirDesign: true,
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_CAPTION_YES'),
	      onclick: function onclick(button) {
	        button.setWaiting();
	        main_core.ajax.runAction('biconnector.externalsource.source.delete', {
	          data: {
	            id: id,
	            moduleId: moduleId
	          }
	        }).then(function () {
	          _this4.getGrid().reload();
	          messageBox.hide();
	        })["catch"](function (response) {
	          messageBox.hide();
	          if (response.errors) {
	            _classPrivateMethodGet(_this4, _notifyErrors, _notifyErrors2).call(_this4, response.errors);
	          }
	        });
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_CAPTION_NO'),
	      size: ui_buttons.ButtonSize.LARGE,
	      style: ui_buttons.AirButtonStyle.PLAIN,
	      useAirDesign: true,
	      onclick: function onclick() {
	        return messageBox.hide();
	      }
	    })],
	    hasOverlay: true
	  });
	  messageBox.show();
	}
	function _getDeleteSourcePopupContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-delete-source-popup-content\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_TITLE'));
	}
	function _getDeleteSourceWithDatasetDialog2(id) {
	  var _this5 = this;
	  var deleteSourcePopupInstance = new ui_system_dialog.Dialog({
	    title: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_TITLE'),
	    content: _classPrivateMethodGet(this, _getDeleteSourceWithDatasetDialogContent, _getDeleteSourceWithDatasetDialogContent2).call(this),
	    centerButtons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_OK_CAPTION'),
	      size: ui_buttons.ButtonSize.LARGE,
	      style: ui_buttons.AirButtonStyle.FILLED,
	      useAirDesign: true,
	      onclick: function onclick() {
	        deleteSourcePopupInstance.hide();
	        _classPrivateMethodGet(_this5, _openDatasetSlider, _openDatasetSlider2).call(_this5, id);
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_CANCEL_CAPTION'),
	      size: ui_buttons.ButtonSize.LARGE,
	      style: ui_buttons.AirButtonStyle.PLAIN,
	      useAirDesign: true,
	      onclick: function onclick() {
	        return deleteSourcePopupInstance.hide();
	      }
	    })],
	    hasOverlay: true,
	    width: 446
	  });
	  return deleteSourcePopupInstance;
	}
	function _getDeleteSourceWithDatasetDialogContent2() {
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-delete-source-with-dataset-dialog-content\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_DESCRIPTION'));
	}
	function _openDatasetSlider2(id) {
	  var _this6 = this;
	  BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(this, _datasetUrl), {
	    allowChangeHistory: false,
	    cacheable: false,
	    events: {
	      onOpenComplete: function onOpenComplete(event) {
	        var slider = event.getSlider ? event.getSlider() : BX.SidePanel.Instance.getTopSlider();
	        if (!slider) {
	          return;
	        }
	        var sliderWindow = slider.getWindow();
	        var tryApplyFilter = function tryApplyFilter() {
	          if (sliderWindow && sliderWindow.BX && sliderWindow.BX.Main && sliderWindow.BX.Main.filterManager) {
	            var filter = sliderWindow.BX.Main.filterManager.getById('biconnector_superset_external_dataset_grid');
	            if (filter && filter instanceof sliderWindow.BX.Main.Filter) {
	              var filterApi = filter.getApi();
	              filterApi.setFields({
	                'SOURCE.ID': String(id),
	                'SOURCE.ID_label': babelHelpers.classPrivateFieldGet(_this6, _sourceTitleList)[id]
	              });
	              filterApi.apply();
	              return;
	            }
	          }
	          setTimeout(tryApplyFilter, 100);
	        };
	        tryApplyFilter();
	      }
	    }
	  });
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').ExternalSourceManager = ExternalSourceManager;

}((this.window = this.window || {}),BX.BIConnector.Grid,BX,BX.UI,BX.UI.System,BX.Event));
//# sourceMappingURL=script.js.map

/* eslint-disable */
(function (exports,crm_integration_analytics,crm_router,main_core,main_core_events,ui_analytics,ui_dialogs_messagebox) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var _getAutomatedSolutionIdFromFilter = /*#__PURE__*/new WeakSet();
	var _getCurrentFilter = /*#__PURE__*/new WeakSet();
	var TypeListComponent = /*#__PURE__*/function () {
	  function TypeListComponent(params) {
	    babelHelpers.classCallCheck(this, TypeListComponent);
	    _classPrivateMethodInitSpec(this, _getCurrentFilter);
	    _classPrivateMethodInitSpec(this, _getAutomatedSolutionIdFromFilter);
	    if (main_core.Type.isPlainObject(params)) {
	      if (main_core.Type.isString(params.gridId)) {
	        this.gridId = params.gridId;
	      }
	      if (this.gridId && BX.Main.grid && BX.Main.gridManager) {
	        this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	      }
	      if (main_core.Type.isElementNode(params.errorTextContainer)) {
	        this.errorTextContainer = params.errorTextContainer;
	      }
	      if (main_core.Type.isElementNode(params.welcomeMessageContainer)) {
	        this.welcomeMessageContainer = params.welcomeMessageContainer;
	      }
	      if (main_core.Type.isBoolean(params.isExternal)) {
	        this.isExternal = params.isExternal;
	      }
	    }
	  }
	  babelHelpers.createClass(TypeListComponent, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onClickCreate', this.handleTypeCreate.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onClickDelete', this.handleTypeDelete.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onFilterByAutomatedSolution', this.handleFilterByAutomatedSolution.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onResetFilterByAutomatedSolution', this.handleFilterByAutomatedSolution.bind(this));
	      var toolbarComponent = this.getToolbarComponent();
	      if (toolbarComponent) {
	        /** @see BX.Crm.ToolbarComponent.subscribeTypeUpdatedEvent */
	        toolbarComponent.subscribeTypeUpdatedEvent(function (event) {
	          var isUrlChanged = main_core.Type.isObject(event.getData()) && event.getData().isUrlChanged === true;
	          if (isUrlChanged) {
	            window.location.reload();
	            return;
	          }
	          if (_this.gridId && main_core.Reflection.getClass('BX.Main.gridManager.reload')) {
	            main_core.Dom.removeClass(document.getElementById('crm-type-list-container'), 'crm-type-list-grid-empty');
	            BX.Main.gridManager.reload(_this.gridId);
	          }
	        });
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var text = '';
	      errors.forEach(function (message) {
	        text = "".concat(text + message, " ");
	      });
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = text;
	        main_core.Dom.style(this.errorTextContainer.parentElement, {
	          display: 'block'
	        });
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = '';
	        main_core.Dom.style(this.errorTextContainer.parentElement, {
	          display: 'none'
	        });
	      }
	    }
	  }, {
	    key: "showErrorsFromResponse",
	    value: function showErrorsFromResponse(_ref) {
	      var errors = _ref.errors;
	      var messages = [];
	      errors.forEach(function (_ref2) {
	        var message = _ref2.message;
	        return messages.push(message);
	      });
	      this.showErrors(messages);
	    } // region EventHandlers
	  }, {
	    key: "handleTypeCreate",
	    value: function handleTypeCreate(event) {
	      var _event$getData = event.getData(),
	        queryParams = _event$getData.queryParams;
	      if (!main_core.Type.isPlainObject(queryParams)) {
	        queryParams = {};
	      }
	      var automatedSolutionId = _classPrivateMethodGet(this, _getAutomatedSolutionIdFromFilter, _getAutomatedSolutionIdFromFilter2).call(this);
	      if (automatedSolutionId > 0) {
	        queryParams.automatedSolutionId = automatedSolutionId;
	      }
	      void crm_router.Router.Instance.openTypeDetail(0, null, queryParams);
	    }
	  }, {
	    key: "handleTypeDelete",
	    value: function handleTypeDelete(event) {
	      var _this2 = this;
	      var id = main_core.Text.toInteger(event.data.id);
	      if (!id) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
	        return;
	      }
	      var analyticsBuilder = new crm_integration_analytics.Builder.Automation.Type.DeleteEvent().setSubSection(crm_integration_analytics.Dictionary.ELEMENT_GRID_ROW_CONTEXT_MENU).setIsExternal(this.isExternal).setId(id);
	      var isCancelRegistered = false;
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_TITLE'),
	        message: main_core.Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_MESSAGE'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ATTEMPT).buildData());
	          main_core.ajax.runAction('crm.controller.type.delete', {
	            analyticsLabel: 'crmTypeListDeleteType',
	            data: {
	              id: id
	            }
	          }).then(function (response) {
	            ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_SUCCESS).buildData());
	            var isUrlChanged = main_core.Type.isObject(response.data) && response.data.isUrlChanged === true;
	            if (isUrlChanged) {
	              window.location.reload();
	              return;
	            }
	            _this2.grid.reloadTable();
	          })["catch"](function (response) {
	            ui_analytics.sendData(analyticsBuilder.setStatus(crm_integration_analytics.Dictionary.STATUS_ERROR).buildData());
	            _this2.showErrorsFromResponse(response);
	          });
	          messageBox.close();
	        },
	        onCancel: function onCancel(messageBox) {
	          if (isCancelRegistered) {
	            messageBox.close();
	            return;
	          }
	          isCancelRegistered = true;
	          ui_analytics.sendData(analyticsBuilder.setElement(crm_integration_analytics.Dictionary.ELEMENT_CANCEL_BUTTON).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	          messageBox.close();
	        },
	        popupOptions: {
	          events: {
	            onPopupClose: function onPopupClose() {
	              if (isCancelRegistered) {
	                return;
	              }
	              isCancelRegistered = true;
	              ui_analytics.sendData(analyticsBuilder.setElement(null).setStatus(crm_integration_analytics.Dictionary.STATUS_CANCEL).buildData());
	            }
	          }
	        }
	      });
	    } // endregion
	  }, {
	    key: "getToolbarComponent",
	    value: function getToolbarComponent() {
	      if (main_core.Reflection.getClass('BX.Crm.ToolbarComponent')) {
	        return BX.Crm.ToolbarComponent.Instance;
	      }
	      return null;
	    }
	  }, {
	    key: "handleFilterByAutomatedSolution",
	    value: function handleFilterByAutomatedSolution(event) {
	      var _BX$Main$filterManage, _BX$Main$filterManage2;
	      var data = _objectSpread(_objectSpread({}, _classPrivateMethodGet(this, _getCurrentFilter, _getCurrentFilter2).call(this)), {}, {
	        AUTOMATED_SOLUTION: event.data || null
	      });
	      var api = (_BX$Main$filterManage = BX.Main.filterManager) === null || _BX$Main$filterManage === void 0 ? void 0 : (_BX$Main$filterManage2 = _BX$Main$filterManage.getList()[0]) === null || _BX$Main$filterManage2 === void 0 ? void 0 : _BX$Main$filterManage2.getApi();
	      if (!api) {
	        return;
	      }
	      api.setFields(data);
	      api.apply();
	    }
	  }]);
	  return TypeListComponent;
	}();
	function _getAutomatedSolutionIdFromFilter2() {
	  var _classPrivateMethodGe = _classPrivateMethodGet(this, _getCurrentFilter, _getCurrentFilter2).call(this),
	    automatedSolutionId = _classPrivateMethodGe.AUTOMATED_SOLUTION;
	  if (main_core.Text.toInteger(automatedSolutionId) > 0) {
	    return main_core.Text.toInteger(automatedSolutionId);
	  }
	  return null;
	}
	function _getCurrentFilter2() {
	  var _BX$Main$filterManage3, _BX$Main$filterManage4;
	  return ((_BX$Main$filterManage3 = BX.Main.filterManager) === null || _BX$Main$filterManage3 === void 0 ? void 0 : (_BX$Main$filterManage4 = _BX$Main$filterManage3.getList()[0]) === null || _BX$Main$filterManage4 === void 0 ? void 0 : _BX$Main$filterManage4.getFilterFieldsValues()) || {};
	}
	namespace.TypeListComponent = TypeListComponent;

}((this.window = this.window || {}),BX.Crm.Integration.Analytics,BX.Crm,BX,BX.Event,BX.UI.Analytics,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map

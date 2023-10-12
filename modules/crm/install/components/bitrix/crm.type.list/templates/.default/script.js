(function (exports,main_core,main_core_events,ui_dialogs_messagebox) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var TypeListComponent = /*#__PURE__*/function () {
	  function TypeListComponent(params) {
	    babelHelpers.classCallCheck(this, TypeListComponent);
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
	      this.isEmptyList = Boolean(params.isEmptyList);
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
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onClickDelete', this.handleTypeDelete.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onFilterByCustomSection', this.handleFilterByCustomSection.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Crm.TypeListComponent:onResetFilterByCustomSection', this.handleFilterByCustomSection.bind(this));
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
	    key: "handleTypeDelete",
	    value: function handleTypeDelete(event) {
	      var _this2 = this;
	      var id = main_core.Text.toInteger(event.data.id);
	      if (!id) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_TITLE'),
	        message: main_core.Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_MESSAGE'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          main_core.ajax.runAction('crm.controller.type.delete', {
	            analyticsLabel: 'crmTypeListDeleteType',
	            data: {
	              id: id
	            }
	          }).then(function (response) {
	            var isUrlChanged = main_core.Type.isObject(response.data) && response.data.isUrlChanged === true;
	            if (isUrlChanged) {
	              window.location.reload();
	              return;
	            }
	            _this2.grid.reloadTable();
	          })["catch"](_this2.showErrorsFromResponse.bind(_this2));
	          messageBox.close();
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
	    key: "handleFilterByCustomSection",
	    value: function handleFilterByCustomSection(event) {
	      var _BX$Main$filterManage, _BX$Main$filterManage2, _BX$Main$filterManage3, _BX$Main$filterManage4;
	      var currentFilter = ((_BX$Main$filterManage = BX.Main.filterManager) === null || _BX$Main$filterManage === void 0 ? void 0 : (_BX$Main$filterManage2 = _BX$Main$filterManage.getList()[0]) === null || _BX$Main$filterManage2 === void 0 ? void 0 : _BX$Main$filterManage2.getFilterFieldsValues()) || [];
	      var data = _objectSpread(_objectSpread({}, currentFilter), {}, {
	        CUSTOM_SECTION: event.data || null
	      });
	      var api = (_BX$Main$filterManage3 = BX.Main.filterManager) === null || _BX$Main$filterManage3 === void 0 ? void 0 : (_BX$Main$filterManage4 = _BX$Main$filterManage3.getList()[0]) === null || _BX$Main$filterManage4 === void 0 ? void 0 : _BX$Main$filterManage4.getApi();
	      if (!api) {
	        return;
	      }
	      api.setFields(data);
	      api.apply();
	    }
	  }]);
	  return TypeListComponent;
	}();
	namespace.TypeListComponent = TypeListComponent;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map

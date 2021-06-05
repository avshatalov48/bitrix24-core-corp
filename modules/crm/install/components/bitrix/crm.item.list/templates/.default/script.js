(function (exports,main_core,main_core_events,ui_dialogs_messagebox,crm_router) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm');
	var instance;

	var ItemListComponent = /*#__PURE__*/function () {
	  function ItemListComponent(params) {
	    babelHelpers.classCallCheck(this, ItemListComponent);

	    if (main_core.Type.isPlainObject(params)) {
	      this.entityTypeId = main_core.Text.toInteger(params.entityTypeId);
	      this.categoryId = main_core.Text.toInteger(params.categoryId);

	      if (main_core.Type.isString(params.gridId)) {
	        this.gridId = params.gridId;
	      }

	      if (this.gridId && BX.Main.grid && BX.Main.gridManager) {
	        this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	      }

	      if (main_core.Type.isElementNode(params.errorTextContainer)) {
	        this.errorTextContainer = params.errorTextContainer;
	      }

	      this.itemCreateUrl = params.itemCreateUrl;
	    }

	    instance = this;
	  }

	  babelHelpers.createClass(ItemListComponent, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemListComponent:onClickDelete', this.handleItemDelete.bind(this));
	      var toolbarComponent = main_core.Reflection.getClass('BX.Crm.ToolbarComponent') ? main_core.Reflection.getClass('BX.Crm.ToolbarComponent').Instance : null;

	      if (toolbarComponent) {
	        toolbarComponent.subscribeTypeUpdatedEvent(function () {
	          var newUrl = crm_router.Router.Instance.getItemListUrl(_this.entityTypeId, _this.categoryId);

	          if (newUrl) {
	            window.location.href = newUrl;
	            return;
	          }

	          window.location.reload();
	        });

	        if (this.grid) {
	          toolbarComponent.subscribeCategoriesUpdatedEvent(function () {
	            _this.grid.reload();
	          });
	        }
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var text = '';
	      errors.forEach(function (message) {
	        text = text + message + ' ';
	      });

	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = text;
	        this.errorTextContainer.parentElement.style.display = 'block';
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = '';
	        this.errorTextContainer.parentElement.style.display = 'none';
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
	    key: "handleItemDelete",
	    value: function handleItemDelete(event) {
	      var _this2 = this;

	      var entityTypeId = main_core.Text.toInteger(event.data.entityTypeId);
	      var id = main_core.Text.toInteger(event.data.id);

	      if (!entityTypeId) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
	        return;
	      }

	      if (!id) {
	        this.showErrors([main_core.Loc.getMessage('CRM_TYPE_ITEM_NOT_FOUND')]);
	        return;
	      }

	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE'),
	        message: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          main_core.ajax.runAction('crm.controller.item.delete', {
	            analyticsLabel: 'crmItemListDeleteItem',
	            data: {
	              entityTypeId: entityTypeId,
	              id: id
	            }
	          }).then(function () {
	            _this2.grid.reloadTable();
	          }).catch(_this2.showErrorsFromResponse.bind(_this2));
	          messageBox.close();
	        }
	      });
	    } //endregion

	  }], [{
	    key: "handleAddButtonClick",
	    value: function handleAddButtonClick() {
	      if (instance && instance.itemCreateUrl) {
	        crm_router.Router.openSlider(instance.itemCreateUrl).then(function () {
	          if (instance.grid) {
	            instance.grid.reload();
	          }
	        });
	      }
	    }
	  }]);
	  return ItemListComponent;
	}();

	namespace.ItemListComponent = ItemListComponent;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs,BX.Crm));
//# sourceMappingURL=script.js.map

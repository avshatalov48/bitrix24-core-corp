/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,ui_buttons,main_core_events,main_core,ui_dialogs_messagebox) {
	'use strict';

	var DocumentManager = /*#__PURE__*/function () {
	  function DocumentManager() {
	    babelHelpers.classCallCheck(this, DocumentManager);
	  }
	  babelHelpers.createClass(DocumentManager, null, [{
	    key: "getRealizationDocumentDetailUrl",
	    value: function getRealizationDocumentDetailUrl(id) {
	      return new main_core.Uri('/shop/documents/details/sales_order/' + id + '/');
	    }
	  }, {
	    key: "openRealizationDetailDocument",
	    value: function openRealizationDetailDocument(id) {
	      var documentUrl = DocumentManager.getRealizationDocumentDetailUrl(id);
	      return BX.SidePanel.Instance.open(documentUrl.toString());
	    }
	  }]);
	  return DocumentManager;
	}();

	var GridManager = /*#__PURE__*/function () {
	  function GridManager(options) {
	    babelHelpers.classCallCheck(this, GridManager);
	    this.gridId = options.gridId;
	    this.filterId = options.filterId;
	    this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	    this.isConductDisabled = options.isConductDisabled;
	    this.masterSliderUrl = options.masterSliderUrl;
	    this.inventoryManagementSource = options.inventoryManagementSource;
	    this.isInventoryManagementDisabled = options.isInventoryManagementDisabled;
	    this.inventoryManagementFeatureCode = options.inventoryManagementFeatureCode;
	    window.top.BX.addCustomEvent('onEntityEditorDocumentOrderShipmentControllerDocumentSave', this.reloadGrid.bind(this));
	  }
	  babelHelpers.createClass(GridManager, [{
	    key: "getSelectedIds",
	    value: function getSelectedIds() {
	      return this.grid.getRows().getSelectedIds();
	    }
	  }, {
	    key: "deleteDocument",
	    value: function deleteDocument(documentId) {
	      var _this = this;
	      if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_CONTENT_2'), function (messageBox, button) {
	        button.setWaiting();
	        main_core.ajax.runAction('crm.api.realizationdocument.setRealization', {
	          data: {
	            id: documentId,
	            value: 'N'
	          },
	          analyticsLabel: {
	            action: 'delete',
	            inventoryManagementSource: _this.inventoryManagementSource
	          }
	        }).then(function () {
	          messageBox.close();
	          _this.reloadGrid();
	        })["catch"](function (response) {
	          if (response.errors) {
	            BX.UI.Notification.Center.notify({
	              content: BX.util.htmlspecialchars(response.errors[0].message)
	            });
	          }
	          messageBox.close();
	        });
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_BUTTON_CONFIRM'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'));
	    }
	  }, {
	    key: "conductDocument",
	    value: function conductDocument(documentId) {
	      var _this2 = this;
	      if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	        return;
	      }
	      if (this.isConductDisabled) {
	        this.openStoreMasterSlider();
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_CONTENT_2'), function (messageBox, button) {
	        button.setWaiting();
	        main_core.ajax.runAction('crm.api.realizationdocument.setShipped', {
	          data: {
	            id: documentId,
	            value: 'Y'
	          },
	          analyticsLabel: {
	            action: 'deduct',
	            inventoryManagementSource: _this2.inventoryManagementSource
	          }
	        }).then(function () {
	          messageBox.close();
	          _this2.reloadGrid();
	        })["catch"](function (response) {
	          if (response.errors) {
	            BX.UI.Notification.Center.notify({
	              content: BX.util.htmlspecialchars(response.errors[0].message)
	            });
	          }
	          messageBox.close();
	        });
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_BUTTON_CONFIRM'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'));
	    }
	  }, {
	    key: "cancelDocument",
	    value: function cancelDocument(documentId) {
	      var _this3 = this;
	      if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	        return;
	      }
	      if (this.isConductDisabled) {
	        this.openStoreMasterSlider();
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_CONTENT_2'), function (messageBox, button) {
	        button.setWaiting();
	        main_core.ajax.runAction('crm.api.realizationdocument.setShipped', {
	          data: {
	            id: documentId,
	            value: 'N'
	          },
	          analyticsLabel: {
	            action: 'cancelDeduct',
	            inventoryManagementSource: _this3.inventoryManagementSource
	          }
	        }).then(function () {
	          messageBox.close();
	          _this3.reloadGrid();
	        })["catch"](function (response) {
	          if (response.errors) {
	            BX.UI.Notification.Center.notify({
	              content: BX.util.htmlspecialchars(response.errors[0].message)
	            });
	          }
	          messageBox.close();
	        });
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_BUTTON_CONFIRM'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'));
	    }
	  }, {
	    key: "deleteSelectedDocuments",
	    value: function deleteSelectedDocuments() {
	      var _this4 = this;
	      var documentIds = this.getSelectedIds();
	      main_core.ajax.runAction('crm.api.realizationdocument.setRealizationList', {
	        data: {
	          ids: documentIds,
	          value: 'N'
	        },
	        analyticsLabel: {
	          action: 'delete',
	          inventoryManagementSource: this.inventoryManagementSource
	        }
	      }).then(function (response) {
	        _this4.reloadGrid();
	      })["catch"](function (response) {
	        if (response.errors) {
	          response.errors.forEach(function (error) {
	            if (error.message) {
	              BX.UI.Notification.Center.notify({
	                content: BX.util.htmlspecialchars(error.message)
	              });
	            }
	          });
	        }
	        _this4.reloadGrid();
	      });
	    }
	  }, {
	    key: "conductSelectedDocuments",
	    value: function conductSelectedDocuments() {
	      var _this5 = this;
	      if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	        return;
	      }
	      if (this.isConductDisabled) {
	        this.openStoreMasterSlider();
	        return;
	      }
	      var documentIds = this.getSelectedIds();
	      main_core.ajax.runAction('crm.api.realizationdocument.setShippedList', {
	        data: {
	          ids: documentIds,
	          value: 'Y'
	        },
	        analyticsLabel: {
	          inventoryManagementSource: this.inventoryManagementSource,
	          action: 'deduct'
	        }
	      }).then(function (response) {
	        _this5.reloadGrid();
	      })["catch"](function (response) {
	        if (response.errors) {
	          response.errors.forEach(function (error) {
	            if (error.message) {
	              BX.UI.Notification.Center.notify({
	                content: BX.util.htmlspecialchars(error.message)
	              });
	            }
	          });
	        }
	        _this5.reloadGrid();
	      });
	    }
	  }, {
	    key: "cancelSelectedDocuments",
	    value: function cancelSelectedDocuments() {
	      var _this6 = this;
	      if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode) {
	        top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);
	        return;
	      }
	      if (this.isConductDisabled) {
	        this.openStoreMasterSlider();
	        return;
	      }
	      var documentIds = this.getSelectedIds();
	      main_core.ajax.runAction('crm.api.realizationdocument.setShippedList', {
	        data: {
	          ids: documentIds,
	          value: 'N'
	        },
	        analyticsLabel: {
	          inventoryManagementSource: this.inventoryManagementSource,
	          action: 'cancelDeduct'
	        }
	      }).then(function (response) {
	        _this6.reloadGrid();
	      })["catch"](function (response) {
	        if (response.errors) {
	          response.errors.forEach(function (error) {
	            if (error.message) {
	              BX.UI.Notification.Center.notify({
	                content: BX.util.htmlspecialchars(error.message)
	              });
	            }
	          });
	        }
	        _this6.reloadGrid();
	      });
	    }
	  }, {
	    key: "applyFilter",
	    value: function applyFilter(options) {
	      var filterManager = BX.Main.filterManager.getById(this.filterId);
	      if (!filterManager) {
	        return;
	      }
	      filterManager.getApi().extendFilter(options);
	    }
	  }, {
	    key: "processApplyButtonClick",
	    value: function processApplyButtonClick() {
	      var actionValues = this.grid.getActionsPanel().getValues();
	      var selectedAction = actionValues["action_button_".concat(this.gridId)];
	      if (selectedAction === 'conduct') {
	        this.conductSelectedDocuments();
	      }
	      if (selectedAction === 'cancel') {
	        this.cancelSelectedDocuments();
	      }
	    }
	  }, {
	    key: "openHowToShipProducts",
	    value: function openHowToShipProducts() {
	      if (top.BX.Helper) {
	        top.BX.Helper.show('redirect=detail&code=14640548');
	        event.preventDefault();
	      }
	    }
	  }, {
	    key: "openStoreMasterSlider",
	    value: function openStoreMasterSlider() {
	      BX.SidePanel.Instance.open(this.masterSliderUrl, {
	        cacheable: false,
	        data: {
	          openGridOnDone: false
	        },
	        events: {
	          onCloseComplete: function onCloseComplete(event) {
	            var slider = event.getSlider();
	            if (!slider) {
	              return;
	            }
	            if (slider.getData().get('isInventoryManagementEnabled')) {
	              document.location.reload();
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      this.grid.reload();
	    }
	  }]);
	  return GridManager;
	}();

	exports.StoreDocumentGridManager = GridManager;

}((this.BX.Crm = this.BX.Crm || {}),BX.Main,BX.UI,BX.Event,BX,BX.UI.Dialogs));
//# sourceMappingURL=store-document-grid-manager.bundle.js.map

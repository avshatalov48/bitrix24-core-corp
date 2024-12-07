/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getMergerUrl = /*#__PURE__*/new WeakSet();
	var _getDefaultMergerUrl = /*#__PURE__*/new WeakSet();
	var BatchMergeManager = /*#__PURE__*/function () {
	  function BatchMergeManager() {
	    babelHelpers.classCallCheck(this, BatchMergeManager);
	    _classPrivateMethodInitSpec(this, _getDefaultMergerUrl);
	    _classPrivateMethodInitSpec(this, _getMergerUrl);
	    this._id = "";
	    this._settings = {};
	    this._grid = null;
	    this._kanban = null;
	    this._entityTypeId = BX.CrmEntityType.enumeration.undefined;
	    this._entityIds = null;
	    this._errors = null;
	    this._isRunning = false;
	    this._documentUnloadHandler = BX.delegate(this.onDocumentUnload, this);
	    this._requestCompleteHandler = BX.delegate(this.onRequestComplete, this);
	    this._externalEventHandler = null;
	  }
	  babelHelpers.createClass(BatchMergeManager, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = main_core.Type.isStringFilled(id) ? id : "crm_batch_merge_mgr_" + Math.random().toString().substring(2);
	      this._settings = settings ? settings : {};
	      var gridId = BX.prop.getString(this._settings, "gridId", null);
	      if (gridId && BX.Main.gridManager) {
	        this._grid = BX.Main.gridManager.getInstanceById(gridId);
	      }
	      this._kanban = BX.prop.get(this._settings, "kanban", null);
	      this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", BX.CrmEntityType.enumeration.undefined);
	      this._errors = [];
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "getMessage",
	    value: function getMessage(name) {
	      var _this$getMessageFromS;
	      return (_this$getMessageFromS = this.getMessageFromSettings(name)) !== null && _this$getMessageFromS !== void 0 ? _this$getMessageFromS : this.getMessageFromLocalization(name);
	    }
	  }, {
	    key: "getEntityIds",
	    value: function getEntityIds() {
	      return this._entityIds;
	    }
	  }, {
	    key: "getEntityTypeId",
	    value: function getEntityTypeId() {
	      return this._entityTypeId;
	    }
	  }, {
	    key: "getMessageFromSettings",
	    value: function getMessageFromSettings(name) {
	      var _this$_settings$messa, _this$_settings, _this$_settings$messa2;
	      return (_this$_settings$messa = (_this$_settings = this._settings) === null || _this$_settings === void 0 ? void 0 : (_this$_settings$messa2 = _this$_settings.messages) === null || _this$_settings$messa2 === void 0 ? void 0 : _this$_settings$messa2[name]) !== null && _this$_settings$messa !== void 0 ? _this$_settings$messa : null;
	    }
	    /**
	     * CRM_BATCH_MERGER_MANAGER_LEAD_TITLE
	     * CRM_BATCH_MERGER_MANAGER_LEAD_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_LEAD_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_LEAD_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_LEAD_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_DEAL_TITLE
	     * CRM_BATCH_MERGER_MANAGER_DEAL_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_DEAL_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_DEAL_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_DEAL_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_CONTACT_TITLE
	     * CRM_BATCH_MERGER_MANAGER_CONTACT_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_CONTACT_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_CONTACT_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_CONTACT_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_COMPANY_TITLE
	     * CRM_BATCH_MERGER_MANAGER_COMPANY_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_COMPANY_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_COMPANY_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_COMPANY_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_SMART_INVOICE_TITLE
	     * CRM_BATCH_MERGER_MANAGER_SMART_INVOICE_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_SMART_INVOICE_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_SMART_INVOICE_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_SMART_INVOICE_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_QUOTE_TITLE
	     * CRM_BATCH_MERGER_MANAGER_QUOTE_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_QUOTE_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_QUOTE_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_QUOTE_SUMMARYFAILED
	     * CRM_BATCH_MERGER_MANAGER_DYNAMIC_TITLE
	     * CRM_BATCH_MERGER_MANAGER_DYNAMIC_CONFIRMATION
	     * CRM_BATCH_MERGER_MANAGER_DYNAMIC_SUMMARYCAPTION
	     * CRM_BATCH_MERGER_MANAGER_DYNAMIC_SUMMARYSUCCEEDED
	     * CRM_BATCH_MERGER_MANAGER_DYNAMIC_SUMMARYFAILED
	     *
	     * @param name
	     * @returns {string|null}
	     */
	  }, {
	    key: "getMessageFromLocalization",
	    value: function getMessageFromLocalization(name) {
	      var phraseCode = 'CRM_BATCH_MERGER_MANAGER_#ENTITY_TYPE_NAME#_#NAME#'.replace('#ENTITY_TYPE_NAME#', this.getMessageEntityTypeName()).replace('#NAME#', name.toUpperCase());
	      return main_core.Loc.getMessage(phraseCode);
	    }
	  }, {
	    key: "getMessageEntityTypeName",
	    value: function getMessageEntityTypeName() {
	      var entityTypeName = BX.CrmEntityType.isDynamicTypeByTypeId(this.getEntityTypeId()) ? BX.CrmEntityType.names.dynamic : BX.CrmEntityType.resolveName(this.getEntityTypeId());
	      return entityTypeName.toUpperCase();
	    }
	  }, {
	    key: "setEntityIds",
	    value: function setEntityIds(entityIds) {
	      this._entityIds = main_core.Type.isArray(entityIds) ? entityIds : [];
	    }
	  }, {
	    key: "resetEntityIds",
	    value: function resetEntityIds() {
	      this._entityIds = [];
	    }
	  }, {
	    key: "getErrors",
	    value: function getErrors() {
	      return this._errors ? this._errors : [];
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var dialogId = this._id.toLowerCase();
	      var dialog = BX.Crm.ConfirmationDialog.get(dialogId);
	      if (!dialog) {
	        dialog = BX.Crm.ConfirmationDialog.create(dialogId, {
	          title: this.getMessage("title"),
	          content: this.getMessage("confirmation")
	        });
	      }
	      if (!dialog.isOpened()) {
	        dialog.open().then(function (result) {
	          if (!BX.prop.getBoolean(result, "cancel", true)) {
	            this.startRequest();
	          }
	        }.bind(this));
	      }
	    }
	  }, {
	    key: "isRunning",
	    value: function isRunning() {
	      return this._isRunning;
	    }
	  }, {
	    key: "startRequest",
	    value: function startRequest() {
	      if (this._isRunning) {
	        return;
	      }
	      this._isRunning = true;
	      this.disableItemsList();
	      BX.bind(window, "beforeunload", this._documentUnloadHandler);
	      var params = {
	        entityTypeId: this._entityTypeId,
	        extras: BX.prop.getObject(this._settings, "extras", {})
	      };
	      if (main_core.Type.isArray(this._entityIds) && this._entityIds.length > 0) {
	        params["entityIds"] = this._entityIds;
	      }
	      BX.ajax.runAction("crm.api.entity.mergeBatch", {
	        data: {
	          params: params
	        }
	      }).then(this._requestCompleteHandler)["catch"](this._requestCompleteHandler);
	    }
	  }, {
	    key: "disableItemsList",
	    value: function disableItemsList() {
	      if (this._grid) {
	        this._grid.tableFade();
	      }
	      if (this._kanban) {
	        this._kanban.fadeOut();
	      }
	    }
	  }, {
	    key: "enableItemsList",
	    value: function enableItemsList() {
	      if (this._grid) {
	        this._grid.tableUnfade();
	      }
	      if (this._kanban) {
	        this._kanban.fadeIn();
	      }
	    }
	  }, {
	    key: "reloadItemsList",
	    value: function reloadItemsList() {
	      if (this._grid) {
	        this._grid.reload();
	      }
	      if (this._kanban) {
	        this._kanban.reload();
	      }
	    }
	  }, {
	    key: "onRequestComplete",
	    value: function onRequestComplete(response) {
	      this.enableItemsList();
	      BX.unbind(window, "beforeunload", this._documentUnloadHandler);
	      this._isRunning = false;
	      this._errors = [];
	      var status = BX.prop.getString(response, "status", "");
	      var data = BX.prop.getObject(response, "data", {});
	      if (status === "error") {
	        if (BX.prop.getString(data, "STATUS", "") === "CONFLICT") {
	          this.openMerger();
	          return;
	        }
	        var errorInfos = BX.prop.getArray(response, "errors", []);
	        for (var i = 0, length = errorInfos.length; i < length; i++) {
	          this._errors.push(BX.prop.getString(errorInfos[i], "message"));
	        }
	      }
	      this.displaySummary();
	      if (this._errors.length === 0) {
	        window.setTimeout(this.complete.bind(this), 0);
	      }
	    }
	  }, {
	    key: "displaySummary",
	    value: function displaySummary() {
	      var messages = [this.getMessage("summaryCaption")];
	      if (this._errors.length > 0) {
	        messages.push(this.getMessage("summaryFailed").replace(/#number#/gi, this._entityIds.length));
	        messages = messages.concat(this._errors);
	      } else {
	        messages.push(this.getMessage("summarySucceeded").replace(/#number#/gi, this._entityIds.length));
	      }
	      BX.UI.Notification.Center.notify({
	        content: messages.join("<br/>"),
	        position: "top-center",
	        autoHideDelay: 5000
	      });
	    }
	  }, {
	    key: "openMerger",
	    value: function openMerger() {
	      this._contextId = this._id + "_" + BX.util.getRandomString(6).toUpperCase();
	      BX.Crm.Page.open(_classPrivateMethodGet(this, _getMergerUrl, _getMergerUrl2).call(this));
	      if (!this._externalEventHandler) {
	        this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
	        BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
	      }
	    }
	  }, {
	    key: "complete",
	    value: function complete() {
	      BX.onCustomEvent(window, "BX.Crm.BatchMergeManager:onComplete", [this]);
	      this.reloadItemsList();
	    }
	  }, {
	    key: "onDocumentUnload",
	    value: function onDocumentUnload(e) {
	      return e.returnValue = this.getMessage("windowCloseConfirm");
	    }
	  }, {
	    key: "onExternalEvent",
	    value: function onExternalEvent(params) {
	      var eventName = BX.prop.getString(params, "key", "");
	      if (eventName !== "onCrmEntityMergeComplete") {
	        return;
	      }
	      var value = BX.prop.getObject(params, "value", {});
	      if (this._contextId !== BX.prop.getString(value, "context", "")) {
	        return;
	      }
	      BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
	      this._externalEventHandler = null;
	      this.displaySummary();
	      window.setTimeout(this.complete.bind(this), 0);
	    }
	  }], [{
	    key: "getItem",
	    value: function getItem(id) {
	      return BX.prop.get(this.items, id, null);
	    }
	  }, {
	    key: "create",
	    value: function create(id, settings) {
	      var self = new BatchMergeManager();
	      self.initialize(id, settings);
	      this.items[self.getId()] = self;
	      return self;
	    }
	  }]);
	  return BatchMergeManager;
	}();
	function _getMergerUrl2() {
	  var mergerBaseUrl = BX.prop.getString(this._settings, 'mergerUrl', _classPrivateMethodGet(this, _getDefaultMergerUrl, _getDefaultMergerUrl2).call(this));
	  var uri = new main_core.Uri(mergerBaseUrl);
	  uri.setQueryParams({
	    externalContextId: this._contextId,
	    id: this._entityIds
	  });
	  return uri.toString();
	}
	function _getDefaultMergerUrl2() {
	  var lowerEntityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId).toLowerCase();
	  return "/crm/".concat(lowerEntityTypeName, "/merge/");
	}
	BatchMergeManager.messages = {};
	BatchMergeManager.items = {};

	exports.BatchMergeManager = BatchMergeManager;

}((this.BX.Crm = this.BX.Crm || {}),BX));
//# sourceMappingURL=manager.bundle.js.map

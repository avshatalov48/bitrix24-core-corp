this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports) {
	'use strict';

	var OnlyOfficeItem = /*#__PURE__*/function (_BX$UI$Viewer$Item) {
	  babelHelpers.inherits(OnlyOfficeItem, _BX$UI$Viewer$Item);

	  function OnlyOfficeItem(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, OnlyOfficeItem);
	    options = options || {};
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OnlyOfficeItem).call(this, options));
	    _this.objectId = options.objectId;
	    _this.attachedObjectId = options.attachedObjectId;
	    _this.versionId = options.versionId;
	    _this.openEditInsteadPreview = options.openEditInsteadPreview;
	    return _this;
	  }

	  babelHelpers.createClass(OnlyOfficeItem, [{
	    key: "setController",
	    value: function setController(controller) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OnlyOfficeItem.prototype), "setController", this).call(this, controller);
	      this.controller.preload = 0;
	    }
	  }, {
	    key: "enableEditInsteadPreview",
	    value: function enableEditInsteadPreview() {
	      this.openEditInsteadPreview = true;
	    }
	  }, {
	    key: "setPropertiesByNode",
	    value: function setPropertiesByNode(node) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OnlyOfficeItem.prototype), "setPropertiesByNode", this).call(this, node);
	      this.objectId = node.dataset.objectId;
	      this.attachedObjectId = node.dataset.attachedObjectId;
	      this.versionId = node.dataset.versionId;
	      this.openEditInsteadPreview = node.dataset.openEditInsteadPreview;
	    }
	  }, {
	    key: "loadData",
	    value: function loadData() {
	      var uid = BX.util.getRandomString(16);
	      BX.Disk.sendTelemetryEvent({
	        action: 'start',
	        uid: uid
	      });
	      BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()), {
	        width: '100%',
	        cacheable: false,
	        customLeftBoundary: 30,
	        allowChangeHistory: false,
	        data: {
	          documentEditor: true,
	          uid: uid
	        }
	      });
	      return new BX.Promise();
	    }
	  }, {
	    key: "getSliderQueryParameters",
	    value: function getSliderQueryParameters() {
	      var action = 'disk.api.documentService.goToPreview';

	      if (this.openEditInsteadPreview && BX.Disk.getDocumentService() === 'onlyoffice') {
	        action = 'disk.api.documentService.goToEditOrPreview';
	      }

	      return {
	        action: action,
	        serviceCode: 'onlyoffice',
	        objectId: this.objectId || 0,
	        attachedObjectId: this.attachedObjectId || 0,
	        versionId: this.versionId || 0
	      };
	    }
	  }]);
	  return OnlyOfficeItem;
	}(BX.UI.Viewer.Item);

	var OnlyofficeExternalLinkItem = /*#__PURE__*/function (_BX$UI$Viewer$Item) {
	  babelHelpers.inherits(OnlyofficeExternalLinkItem, _BX$UI$Viewer$Item);

	  function OnlyofficeExternalLinkItem(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, OnlyofficeExternalLinkItem);
	    options = options || {};
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OnlyofficeExternalLinkItem).call(this, options));
	    _this.documentViewUrl = options.documentViewUrl;
	    return _this;
	  }

	  babelHelpers.createClass(OnlyofficeExternalLinkItem, [{
	    key: "setPropertiesByNode",
	    value: function setPropertiesByNode(node) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OnlyofficeExternalLinkItem.prototype), "setPropertiesByNode", this).call(this, node);
	      this.documentViewUrl = node.dataset.documentViewUrl;
	    }
	  }, {
	    key: "getDocumentViewUrl",
	    value: function getDocumentViewUrl() {
	      return this.documentViewUrl;
	    }
	  }, {
	    key: "setController",
	    value: function setController(controller) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(OnlyofficeExternalLinkItem.prototype), "setController", this).call(this, controller);
	      this.controller.preload = 0;
	    }
	  }, {
	    key: "loadData",
	    value: function loadData() {
	      var uid = BX.util.getRandomString(16);
	      BX.Disk.sendTelemetryEvent({
	        action: 'start',
	        uid: uid
	      });
	      BX.SidePanel.Instance.open(this.getDocumentViewUrl(), {
	        width: '100%',
	        cacheable: false,
	        customLeftBoundary: 30,
	        allowChangeHistory: false,
	        data: {
	          documentEditor: true,
	          uid: uid
	        }
	      });
	      return new BX.Promise();
	    }
	  }]);
	  return OnlyofficeExternalLinkItem;
	}(BX.UI.Viewer.Item);

	exports.OnlyOfficeItem = OnlyOfficeItem;
	exports.OnlyofficeExternalLinkItem = OnlyofficeExternalLinkItem;

}((this.BX.Disk.Viewer = this.BX.Disk.Viewer || {})));
//# sourceMappingURL=disk.onlyoffice-item.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports) {
	'use strict';

	var BoardItem = /*#__PURE__*/function (_BX$UI$Viewer$Item) {
	  babelHelpers.inherits(BoardItem, _BX$UI$Viewer$Item);
	  function BoardItem(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, BoardItem);
	    options = options || {};
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BoardItem).call(this, options));
	    _this.documentViewUrl = options.documentViewUrl;
	    return _this;
	  }
	  babelHelpers.createClass(BoardItem, [{
	    key: "setPropertiesByNode",
	    value: function setPropertiesByNode(node) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(BoardItem.prototype), "setPropertiesByNode", this).call(this, node);
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
	      babelHelpers.get(babelHelpers.getPrototypeOf(BoardItem.prototype), "setController", this).call(this, controller);
	      this.controller.preload = 0;
	    }
	  }, {
	    key: "loadData",
	    value: function loadData() {
	      var promise = new BX.Promise();
	      this.controller.runActionByNode(this.sourceNode, 'open');
	      promise.fulfill(this);
	      return promise;
	    }
	  }, {
	    key: "getSliderQueryParameters",
	    value: function getSliderQueryParameters() {
	      return {
	        action: 'disk.api.documentService.goToEditOrPreview',
	        serviceCode: 'board',
	        objectId: this.objectId || 0,
	        attachedObjectId: this.attachedObjectId || 0,
	        versionId: this.versionId || 0
	      };
	    }
	  }]);
	  return BoardItem;
	}(BX.UI.Viewer.Item);

	var BoardExternalLinkItem = /*#__PURE__*/function (_BX$UI$Viewer$Item) {
	  babelHelpers.inherits(BoardExternalLinkItem, _BX$UI$Viewer$Item);
	  function BoardExternalLinkItem(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, BoardExternalLinkItem);
	    options = options || {};
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BoardExternalLinkItem).call(this, options));
	    _this.documentViewUrl = options.documentViewUrl;
	    return _this;
	  }
	  babelHelpers.createClass(BoardExternalLinkItem, [{
	    key: "setPropertiesByNode",
	    value: function setPropertiesByNode(node) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(BoardExternalLinkItem.prototype), "setPropertiesByNode", this).call(this, node);
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
	      babelHelpers.get(babelHelpers.getPrototypeOf(BoardExternalLinkItem.prototype), "setController", this).call(this, controller);
	      this.controller.preload = 0;
	    }
	  }, {
	    key: "loadData",
	    value: function loadData() {
	      window.open(this.getDocumentViewUrl(), '_blank').focus();
	      return new BX.Promise();
	    }
	  }]);
	  return BoardExternalLinkItem;
	}(BX.UI.Viewer.Item);

	exports.BoardItem = BoardItem;
	exports.BoardExternalLinkItem = BoardExternalLinkItem;

}((this.BX.Disk.Viewer = this.BX.Disk.Viewer || {})));
//# sourceMappingURL=disk.board-item.bundle.js.map

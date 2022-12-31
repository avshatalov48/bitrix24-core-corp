(function (exports,main_core,ui_buttons) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	var Viewer = main_core.Reflection.namespace('BX.UI.Viewer');
	var defaultComponent = null;
	/**
	 * @memberOf BX.Crm.Component
	 */

	var _initViewer = /*#__PURE__*/new WeakSet();

	var _bindEvents = /*#__PURE__*/new WeakSet();

	var SignDocumentView = /*#__PURE__*/function () {
	  function SignDocumentView(parameters) {
	    babelHelpers.classCallCheck(this, SignDocumentView);

	    _classPrivateMethodInitSpec(this, _bindEvents);

	    _classPrivateMethodInitSpec(this, _initViewer);

	    this.pdfNode = parameters.pdfNode;
	    this.pdfSource = parameters.pdfSource;
	    this.printButton = ui_buttons.ButtonManager.createByUniqId('crm-document-print');
	    this.downloadButton = ui_buttons.ButtonManager.createByUniqId('crm-document-download');

	    _classPrivateMethodGet(this, _initViewer, _initViewer2).call(this);

	    _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);

	    defaultComponent = this;
	  }

	  babelHelpers.createClass(SignDocumentView, [{
	    key: "getViewer",
	    value: function getViewer() {
	      var _this$viewer;

	      if (!this.viewer && this.pdfNode) {
	        this.viewer = new Viewer.SingleDocumentController({
	          baseContainer: this.pdfNode,
	          stretch: true
	        });
	      }

	      return (_this$viewer = this.viewer) !== null && _this$viewer !== void 0 ? _this$viewer : null;
	    }
	  }], [{
	    key: "getDefaultComponent",
	    value: function getDefaultComponent() {
	      return defaultComponent;
	    }
	  }]);
	  return SignDocumentView;
	}();

	function _initViewer2() {
	  var viewer = this.getViewer();

	  if (!viewer) {
	    return;
	  }

	  viewer.setItems([Viewer.buildItemByNode(this.pdfNode)]);
	  viewer.setPdfSource(this.pdfSource);
	  viewer.setScale(1.2);
	  viewer.open();
	}

	function _bindEvents2() {
	  var _this = this;

	  if (this.printButton && this.getViewer()) {
	    this.printButton.bindEvent('click', function () {
	      _this.getViewer().print();
	    });
	  }

	  if (this.downloadButton) {
	    this.downloadButton.bindEvent('click', function () {
	      window.open(_this.pdfSource, '_blank');
	    });
	  }
	}

	namespace.SignDocumentView = SignDocumentView;

}((this.window = this.window || {}),BX,BX.UI));
//# sourceMappingURL=script.js.map

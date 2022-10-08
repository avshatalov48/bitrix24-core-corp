(function (exports,main_core,ui_entitySelector) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Activity');

	var _selector = /*#__PURE__*/new WeakMap();

	var _onProductClick = /*#__PURE__*/new WeakSet();

	var _getProductSelector = /*#__PURE__*/new WeakSet();

	var CrmAddProductRowActivity = /*#__PURE__*/function () {
	  function CrmAddProductRowActivity(options) {
	    babelHelpers.classCallCheck(this, CrmAddProductRowActivity);

	    _classPrivateMethodInitSpec(this, _getProductSelector);

	    _classPrivateMethodInitSpec(this, _onProductClick);

	    _classPrivateFieldInitSpec(this, _selector, {
	      writable: true,
	      value: void 0
	    });

	    if (main_core.Type.isPlainObject(options)) {
	      var form = document.forms[options.formName];

	      if (!main_core.Type.isNil(form)) {
	        this.productNode = form['product_id'];
	      }

	      if (options.productProperty && main_core.Type.isPlainObject(options.productProperty.Settings)) {
	        this.productSettings = options.productProperty.Settings;
	      }
	    }
	  }

	  babelHelpers.createClass(CrmAddProductRowActivity, [{
	    key: "init",
	    value: function init() {
	      if (this.productNode && this.productSettings) {
	        main_core.Event.bind(this.productNode, 'click', _classPrivateMethodGet(this, _onProductClick, _onProductClick2).bind(this));
	      }
	    }
	  }]);
	  return CrmAddProductRowActivity;
	}();

	function _onProductClick2() {
	  _classPrivateMethodGet(this, _getProductSelector, _getProductSelector2).call(this).show();
	}

	function _getProductSelector2() {
	  var _this = this;

	  if (!babelHelpers.classPrivateFieldGet(this, _selector)) {
	    babelHelpers.classPrivateFieldSet(this, _selector, new ui_entitySelector.Dialog({
	      context: 'catalog-products',
	      entities: [{
	        id: 'product',
	        options: {
	          iblockId: this.productSettings.iblockId,
	          basePriceId: this.productSettings.basePriceId
	        }
	      }],
	      targetNode: this.productNode,
	      height: 300,
	      multiple: false,
	      dropdownMode: true,
	      enableSearch: true,
	      events: {
	        'Item:onBeforeSelect': function ItemOnBeforeSelect(event) {
	          event.preventDefault();
	          _this.productNode.value = event.getData().item.getId();
	        }
	      }
	    }));
	  }

	  return babelHelpers.classPrivateFieldGet(this, _selector);
	}

	namespace.CrmAddProductRowActivity = CrmAddProductRowActivity;

}((this.window = this.window || {}),BX,BX.UI.EntitySelector));
//# sourceMappingURL=script.js.map

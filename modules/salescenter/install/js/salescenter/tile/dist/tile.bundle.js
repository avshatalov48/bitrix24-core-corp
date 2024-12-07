/* eslint-disable */
this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,main_core) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base(props) {
	    babelHelpers.classCallCheck(this, Base);
	    this.id = +props.id || null;
	    this.img = main_core.Type.isString(props.img) && props.img.length > 0 ? props.img : '';
	    this.link = main_core.Type.isString(props.link) && props.link.length > 0 ? props.link : '';
	    this.name = main_core.Type.isString(props.name) && props.name.length > 0 ? props.name : '';
	    this.showTitle = main_core.Type.isBoolean(props.showTitle) ? props.showTitle : false;
	    this.group = main_core.Type.isString(props.group) ? props.group : '';
	  }
	  babelHelpers.createClass(Base, [{
	    key: "getType",
	    value: function getType() {
	      return '';
	    }
	  }]);
	  return Base;
	}();

	var Offer = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Offer, _Base);
	  function Offer(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Offer);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Offer).call(this, props));
	    _this.width = 735;
	    return _this;
	  }
	  babelHelpers.createClass(Offer, [{
	    key: "getType",
	    value: function getType() {
	      return Offer.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'offer';
	    }
	  }]);
	  return Offer;
	}(Base);

	var More = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(More, _Base);
	  function More() {
	    babelHelpers.classCallCheck(this, More);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(More).apply(this, arguments));
	  }
	  babelHelpers.createClass(More, [{
	    key: "getType",
	    value: function getType() {
	      return More.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'more';
	    }
	  }]);
	  return More;
	}(Base);

	var Cashbox = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Cashbox, _Base);
	  function Cashbox(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Cashbox);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Cashbox).call(this, props));
	    _this.info = main_core.Type.isString(props.info) && props.info.length > 0 ? props.info : '';
	    return _this;
	  }
	  babelHelpers.createClass(Cashbox, [{
	    key: "getType",
	    value: function getType() {
	      return Cashbox.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'cashbox';
	    }
	  }]);
	  return Cashbox;
	}(Base);

	var Delivery = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Delivery, _Base);
	  function Delivery(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Delivery);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Delivery).call(this, props));
	    _this.code = main_core.Type.isString(props.code) && props.code.length > 0 ? props.code : '';
	    _this.info = main_core.Type.isString(props.info) && props.info.length > 0 ? props.info : '';
	    _this.showTitle = main_core.Type.isBoolean(_this.showTitle) ? _this.showTitle : false;
	    _this.width = 835;
	    return _this;
	  }
	  babelHelpers.createClass(Delivery, [{
	    key: "getType",
	    value: function getType() {
	      return Delivery.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'delivery';
	    }
	  }]);
	  return Delivery;
	}(Base);

	var PaySystem = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(PaySystem, _Base);
	  function PaySystem(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, PaySystem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PaySystem).call(this, props));
	    _this.info = main_core.Type.isString(props.info) && props.info.length > 0 ? props.info : '';
	    _this.sort = main_core.Type.isInteger(props.sort) ? props.sort : 0;
	    _this.psModeName = main_core.Type.isString(props.psModeName) && props.psModeName.length > 0 ? props.psModeName : _this.name;
	    return _this;
	  }
	  babelHelpers.createClass(PaySystem, [{
	    key: "getType",
	    value: function getType() {
	      return PaySystem.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'paysystem';
	    }
	  }]);
	  return PaySystem;
	}(Base);

	var Marketplace = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Marketplace, _Base);
	  function Marketplace(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Marketplace);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Marketplace).call(this, props));
	    _this.id = main_core.Type.isInteger(_this.id) ? _this.id : 0;
	    _this.code = main_core.Type.isString(props.code) && props.code.length > 0 ? props.code : '';
	    _this.info = main_core.Type.isString(props.info) && props.info.length > 0 ? props.info : '';
	    _this.sort = main_core.Type.isInteger(props.sort) ? props.sort : 0;
	    _this.showTitle = main_core.Type.isBoolean(props.showTitle) ? props.showTitle : false;
	    _this.installedApp = main_core.Type.isBoolean(props.installedApp) ? props.installedApp : false;
	    return _this;
	  }
	  babelHelpers.createClass(Marketplace, [{
	    key: "getType",
	    value: function getType() {
	      return Marketplace.type();
	    }
	  }, {
	    key: "isInstalled",
	    value: function isInstalled() {
	      return this.installedApp;
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'marketplace';
	    }
	  }]);
	  return Marketplace;
	}(Base);

	var tiles = [More, Offer, Cashbox, Delivery, PaySystem, Marketplace];
	var Factory = /*#__PURE__*/function () {
	  function Factory() {
	    babelHelpers.classCallCheck(this, Factory);
	  }
	  babelHelpers.createClass(Factory, null, [{
	    key: "create",
	    value: function create(options) {
	      var tile = tiles.filter(function (item) {
	        return options.type === item.type();
	      })[0];
	      if (!tile) {
	        throw new Error("Unknown field type '".concat(options.type, "'"));
	      }
	      return new tile(options);
	    }
	  }]);
	  return Factory;
	}();

	/**
	 * Group of tiles
	 */
	var Group = /*#__PURE__*/function () {
	  function Group(props) {
	    babelHelpers.classCallCheck(this, Group);
	    /**
	     * Group id
	     * @type {string}
	     */
	    this.id = props.id;
	    if (!main_core.Type.isString(props.id)) {
	      throw new Error("Property 'id' is required for Group");
	    }

	    /**
	     * Group name
	     * @type {string}
	     */
	    this.name = main_core.Type.isString(props.name) ? props.name : '';

	    /**
	     * Tiles included in the group
	     * @type {Base[]}
	     */
	    this.tiles = [];
	  }

	  /**
	   * Filling group tiles from array
	   * 
	   * @param {Base[]} tiles
	   */
	  babelHelpers.createClass(Group, [{
	    key: "fillTiles",
	    value: function fillTiles(tiles) {
	      var _this = this;
	      if (main_core.Type.isArray(tiles)) {
	        tiles.forEach(function (item) {
	          if (item instanceof Base && item.group == _this.id) {
	            _this.tiles.push(item);
	          }
	        });
	      }
	    }
	  }]);
	  return Group;
	}();

	exports.Group = Group;
	exports.Offer = Offer;
	exports.More = More;
	exports.Cashbox = Cashbox;
	exports.Factory = Factory;
	exports.Delivery = Delivery;
	exports.PaySystem = PaySystem;
	exports.Marketplace = Marketplace;

}((this.BX.Salescenter.Tile = this.BX.Salescenter.Tile || {}),BX));
//# sourceMappingURL=tile.bundle.js.map

this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,main_core) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base(props) {
	    babelHelpers.classCallCheck(this, Base);
	    this.id = main_core.Type.isString(props.id) && props.id.length > 0 ? props.id : '';
	    this.name = main_core.Type.isString(props.name) && props.name.length > 0 ? props.name : '';
	    this.color = main_core.Type.isString(props.color) && props.color.length > 0 ? props.color : '';
	    this.selected = main_core.Type.isBoolean(props.selected) ? props.selected : false;
	    this.colorText = main_core.Type.isString(props.colorText) && props.colorText.length > 0 ? props.colorText : '';
	  }

	  babelHelpers.createClass(Base, [{
	    key: "getType",
	    value: function getType() {
	      return '';
	    }
	  }]);
	  return Base;
	}();

	var Stage = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Stage, _Base);

	  function Stage() {
	    babelHelpers.classCallCheck(this, Stage);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Stage).apply(this, arguments));
	  }

	  babelHelpers.createClass(Stage, [{
	    key: "getType",
	    value: function getType() {
	      return Stage.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'stage';
	    }
	  }]);
	  return Stage;
	}(Base);

	var Invariable = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Invariable, _Base);

	  function Invariable(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Invariable);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Invariable).call(this, props));
	    _this.id = '';
	    _this.color = '#2fc6f6';
	    _this.colorText = 'dark';
	    return _this;
	  }

	  babelHelpers.createClass(Invariable, [{
	    key: "getType",
	    value: function getType() {
	      return Invariable.type();
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'invariable';
	    }
	  }]);
	  return Invariable;
	}(Base);

	var stages = [Stage, Invariable];

	var Factory = /*#__PURE__*/function () {
	  function Factory() {
	    babelHelpers.classCallCheck(this, Factory);
	  }

	  babelHelpers.createClass(Factory, null, [{
	    key: "create",
	    value: function create(options) {
	      var stage = stages.filter(function (item) {
	        return options.type === item.type();
	      })[0];

	      if (!stage) {
	        throw new Error("Unknown field type '".concat(options.type, "'"));
	      }

	      return new stage(options);
	    }
	  }]);
	  return Factory;
	}();

	exports.Base = Base;
	exports.Stage = Stage;
	exports.Factory = Factory;
	exports.Invariable = Invariable;

}((this.BX.Salescenter.AutomationStage = this.BX.Salescenter.AutomationStage || {}),BX));
//# sourceMappingURL=automation-stage.bundle.js.map

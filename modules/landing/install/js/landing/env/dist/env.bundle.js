this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var defaultOptions = {
	  params: {
	    type: 'EXTERNAL'
	  }
	};

	var optionsKey = Symbol('options');
	var Env =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(Env, null, [{
	    key: "getInstance",
	    value: function getInstance() {
	      return Env.instance || Env.createInstance();
	    }
	  }, {
	    key: "createInstance",
	    value: function createInstance() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      Env.instance = new Env(options);
	      window.top.BX.Landing.Env.instance = Env.instance;
	      return Env.instance;
	    }
	  }]);

	  function Env() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Env);
	    this[optionsKey] = Object.seal(main_core.Runtime.merge(defaultOptions, options));
	  }

	  babelHelpers.createClass(Env, [{
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.objectSpread({}, this[optionsKey]);
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return this.getOptions().params.type;
	    }
	  }]);
	  return Env;
	}();
	babelHelpers.defineProperty(Env, "instance", null);

	exports.Env = Env;

}((this.BX.Landing = this.BX.Landing || {}),BX));
//# sourceMappingURL=env.bundle.js.map

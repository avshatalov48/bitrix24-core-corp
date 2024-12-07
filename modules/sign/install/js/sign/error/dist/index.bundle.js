this.BX = this.BX || {};
(function (exports) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _triggerError = /*#__PURE__*/new WeakSet();
	var Error = /*#__PURE__*/function () {
	  babelHelpers.createClass(Error, null, [{
	    key: "getInstance",
	    /**
	     * Returns Error instance.
	     * @return {Error}
	     */
	    value: function getInstance() {
	      if (!Error.instance) {
	        Error.instance = new Error();
	      }
	      return Error.instance;
	    }
	  }]);
	  function Error() {
	    babelHelpers.classCallCheck(this, Error);
	    _classPrivateMethodInitSpec(this, _triggerError);
	    babelHelpers.defineProperty(this, "errors", []);
	    babelHelpers.defineProperty(this, "callbacks", []);
	  }

	  /**
	   * Adds new error message.
	   * @param {ErrorItem} error
	   */
	  babelHelpers.createClass(Error, [{
	    key: "addError",
	    value: function addError(error) {
	      this.errors.push(error);
	      _classPrivateMethodGet(this, _triggerError, _triggerError2).call(this);
	    }
	    /**
	     * Returns errors collection.
	     * @return {Array<ErrorItem>}
	     */
	  }, {
	    key: "getErrors",
	    value: function getErrors() {
	      return this.errors;
	    }
	    /**
	     * Adds new callback to handle errors.
	     * @param {() => {}} callback
	     */
	  }, {
	    key: "onError",
	    value: function onError(callback) {
	      this.callbacks.push(callback);
	    }
	    /**
	     * Executes all error handlers set through onError.
	     */
	  }]);
	  return Error;
	}();
	function _triggerError2() {
	  var _this = this;
	  this.callbacks.map(function (callback) {
	    callback(_this.errors);
	  });
	}

	exports.Error = Error;

}((this.BX.Sign = this.BX.Sign || {})));
//# sourceMappingURL=index.bundle.js.map

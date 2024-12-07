/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
(function (exports) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base(payload) {
	    babelHelpers.classCallCheck(this, Base);
	    babelHelpers.defineProperty(this, "payload", null);
	    babelHelpers.defineProperty(this, "markers", {});
	    this.payload = payload;
	  }
	  babelHelpers.createClass(Base, [{
	    key: "setMarkers",
	    value: function setMarkers(markers) {
	      this.markers = markers;
	      return this;
	    }
	  }, {
	    key: "getMarkers",
	    value: function getMarkers() {
	      return this.markers;
	    }
	    /**
	     * Returns data in pretty style.
	     *
	     * @return {*}
	     */
	  }, {
	    key: "getPrettifiedData",
	    value: function getPrettifiedData() {
	      return this.payload;
	    }
	    /**
	     * Returns data in raw style.
	     *
	     * @return {*}
	     */
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return this.payload;
	    }
	  }]);
	  return Base;
	}();

	exports.Base = Base;

}((this.BX.AI.Payload = this.BX.AI.Payload || {})));
//# sourceMappingURL=basepayload.bundle.js.map

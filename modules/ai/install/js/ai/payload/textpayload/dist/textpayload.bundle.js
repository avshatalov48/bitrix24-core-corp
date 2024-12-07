/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
(function (exports,ai_payload_basepayload) {
	'use strict';

	var Text = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Text, _Base);
	  /**
	   *
	   * @param {TextPayload} payload
	   */
	  // eslint-disable-next-line no-useless-constructor
	  function Text(payload) {
	    babelHelpers.classCallCheck(this, Text);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Text).call(this, payload));
	  }
	  babelHelpers.createClass(Text, [{
	    key: "setMarkers",
	    value: function setMarkers(markers) {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "setMarkers", this).call(this, markers);
	    }
	  }, {
	    key: "getMarkers",
	    value: function getMarkers() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getMarkers", this).call(this);
	    }
	  }, {
	    key: "getPrettifiedData",
	    value: function getPrettifiedData() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getPrettifiedData", this).call(this);
	    }
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getRawData", this).call(this);
	    }
	  }]);
	  return Text;
	}(ai_payload_basepayload.Base);

	exports.Text = Text;

}((this.BX.AI.Payload = this.BX.AI.Payload || {}),BX.AI.Payload));
//# sourceMappingURL=textpayload.bundle.js.map

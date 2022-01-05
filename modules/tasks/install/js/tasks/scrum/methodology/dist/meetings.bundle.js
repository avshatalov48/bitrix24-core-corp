this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports) {
	'use strict';

	var Meetings = /*#__PURE__*/function () {
	  function Meetings() {
	    babelHelpers.classCallCheck(this, Meetings);
	  }

	  babelHelpers.createClass(Meetings, [{
	    key: "showMenu",
	    value: function showMenu() {
	      console.log('showWidget');
	    }
	  }, {
	    key: "openCalendarSlider",
	    value: function openCalendarSlider() {
	      console.log('openCalendarSlider');
	    }
	  }]);
	  return Meetings;
	}();

	exports.Meetings = Meetings;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {})));
//# sourceMappingURL=meetings.bundle.js.map

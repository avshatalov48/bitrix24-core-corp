/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core) {
	'use strict';

	var Controller = /*#__PURE__*/function () {
	  function Controller() {
	    babelHelpers.classCallCheck(this, Controller);
	  }
	  babelHelpers.createClass(Controller, [{
	    key: "userComments",
	    value: function userComments() {
	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return main_core.ajax.runAction('tasks.viewedGroup.user.markAsRead', {
	        data: {
	          fields: data
	        }
	      });
	    }
	  }, {
	    key: "projectComments",
	    value: function projectComments() {
	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return main_core.ajax.runAction('tasks.viewedGroup.project.markAsRead', {
	        data: {
	          fields: data
	        }
	      });
	    }
	  }, {
	    key: "scrumComments",
	    value: function scrumComments() {
	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return main_core.ajax.runAction('tasks.viewedGroup.scrum.markAsRead', {
	        data: {
	          fields: data
	        }
	      });
	    }
	  }]);
	  return Controller;
	}();

	exports.Controller = Controller;

}((this.BX.Tasks.Viewed = this.BX.Tasks.Viewed || {}),BX));
//# sourceMappingURL=index.bundle.js.map

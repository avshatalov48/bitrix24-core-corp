this.BX = this.BX || {};
(function (exports,main_core_events,main_core) {
	'use strict';

	var Result = /*#__PURE__*/function () {
	  function Result(taskId) {
	    babelHelpers.classCallCheck(this, Result);
	    this.taskId = taskId;
	  }
	  babelHelpers.createClass(Result, [{
	    key: "setClosed",
	    value: function setClosed(value) {
	      this.isClosed = value;
	    }
	  }, {
	    key: "setComments",
	    value: function setComments(comments) {
	      this.comments = comments;
	    }
	  }, {
	    key: "setContext",
	    value: function setContext(context) {
	      this.context = context;
	    }
	  }, {
	    key: "isResult",
	    value: function isResult(commentId) {
	      if (this.comments && this.comments[commentId]) {
	        return true;
	      }
	      return false;
	    }
	  }, {
	    key: "pushComment",
	    value: function pushComment(result) {
	      this.comments[result.commentId] = result;
	    }
	  }, {
	    key: "deleteComment",
	    value: function deleteComment(commentId) {
	      this.comments[commentId] && delete this.comments[commentId];
	    }
	  }, {
	    key: "canSetAsResult",
	    value: function canSetAsResult(commentId) {
	      if (this.comments[commentId]) {
	        return false;
	      }
	      return true;
	    }
	  }, {
	    key: "canUnsetAsResult",
	    value: function canUnsetAsResult(commentId) {
	      if (!this.comments[commentId]) {
	        return false;
	      }
	      return true;
	    }
	  }]);
	  return Result;
	}();

	var ResultManager = /*#__PURE__*/function () {
	  babelHelpers.createClass(ResultManager, null, [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!ResultManager.instance) {
	        ResultManager.instance = new ResultManager();
	      }
	      return ResultManager.instance;
	    }
	  }, {
	    key: "showField",
	    value: function showField() {
	      var node = document.getElementById('IS_TASK_RESULT');
	      if (!node || !node.closest('label')) {
	        return;
	      }
	      node.closest('label').classList.remove('--hidden');
	    }
	  }, {
	    key: "hideField",
	    value: function hideField() {
	      var node = document.getElementById('IS_TASK_RESULT');
	      if (!node || !node.closest('label')) {
	        return;
	      }
	      node.closest('label').classList.add('--hidden');
	    }
	  }]);
	  function ResultManager() {
	    babelHelpers.classCallCheck(this, ResultManager);
	    this.init();
	  }
	  babelHelpers.createClass(ResultManager, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      var compatMode = {
	        compatMode: true
	      };
	      main_core_events.EventEmitter.subscribe('OnUCFormBeforeShow', function (event) {
	        _this.onEditComment(event);
	      }, compatMode);
	      main_core_events.EventEmitter.subscribe('onPullEvent-tasks', function (command, params) {
	        _this.onPushResult(command, params);
	      }, compatMode);
	      main_core_events.EventEmitter.subscribe('onPull-tasks', function (event) {
	        _this.onPushResult(event.command, event.params);
	      }, compatMode);
	    }
	  }, {
	    key: "initResult",
	    value: function initResult(params) {
	      var result = this.getResult(params.taskId);
	      result.setComments(params.comments);
	      if (params.context) {
	        result.setContext(params.context);
	      }
	      if (params.isClosed) {
	        result.setClosed(true);
	      }
	      return result;
	    }
	  }, {
	    key: "getResult",
	    value: function getResult(taskId) {
	      if (!ResultManager.resultRegistry[taskId]) {
	        ResultManager.resultRegistry[taskId] = new Result(taskId);
	      }
	      return ResultManager.resultRegistry[taskId];
	    }
	  }, {
	    key: "onEditComment",
	    value: function onEditComment(event) {
	      if (!event || !event['id'] || event['id'][0].indexOf('TASK_') !== 0) {
	        return;
	      }
	      var node = document.getElementById('IS_TASK_RESULT');
	      if (!node) {
	        return;
	      }
	      var taskId = +/\d+/.exec(event['id'][0]);
	      var result = this.getResult(taskId);
	      node.checked = result.isResult(event['id'][1]);
	    }
	  }, {
	    key: "onPushResult",
	    value: function onPushResult(command, params) {
	      if (command === 'task_update') {
	        var _taskId = parseInt(params.TASK_ID);
	        var _result = this.getResult(_taskId);
	        if (params.AFTER.STATUS && (params.AFTER.STATUS == 4 || params.AFTER.STATUS == 5)) {
	          _result.setClosed(true);
	        } else if (params.AFTER.STATUS) {
	          _result.setClosed(false);
	        }
	        return;
	      }
	      if (command !== 'task_result_create' && command !== 'task_result_delete') {
	        return;
	      }
	      if (!params.result || !params.result.taskId || !params.result.commentId) {
	        return;
	      }
	      var taskId = params.result.taskId;
	      var result = this.getResult(taskId);
	      if (command === 'task_result_create') {
	        result.pushComment(params.result);
	      } else if (command === 'task_result_delete') {
	        result.deleteComment(params.result.commentId);
	      }
	    }
	  }]);
	  return ResultManager;
	}();
	babelHelpers.defineProperty(ResultManager, "resultRegistry", {});
	babelHelpers.defineProperty(ResultManager, "instance", null);

	var ResultAction = /*#__PURE__*/function () {
	  babelHelpers.createClass(ResultAction, null, [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!ResultAction.instance) {
	        ResultAction.instance = new ResultAction();
	      }
	      return ResultAction.instance;
	    }
	  }]);
	  function ResultAction() {
	    babelHelpers.classCallCheck(this, ResultAction);
	  }
	  babelHelpers.createClass(ResultAction, [{
	    key: "canCreateResult",
	    value: function canCreateResult(taskId) {
	      return true;
	    }
	  }, {
	    key: "deleteFromComment",
	    value: function deleteFromComment(commentId) {
	      main_core.ajax.runComponentAction('bitrix:tasks.widget.result', 'deleteFromComment', {
	        mode: 'class',
	        data: {
	          commentId: commentId
	        }
	      }).then(function (response) {
	        if (!response.data) {
	          return;
	        }
	      }.bind(this));
	    }
	  }, {
	    key: "createFromComment",
	    value: function createFromComment(commentId) {
	      main_core.ajax.runComponentAction('bitrix:tasks.widget.result', 'createFromComment', {
	        mode: 'class',
	        data: {
	          commentId: commentId
	        }
	      }).then(function (response) {
	        if (!response.data) {
	          return;
	        }
	      }.bind(this));
	    }
	  }]);
	  return ResultAction;
	}();
	babelHelpers.defineProperty(ResultAction, "instance", null);

	exports.ResultManager = ResultManager;
	exports.ResultAction = ResultAction;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.Event,BX));
//# sourceMappingURL=result.bundle.js.map

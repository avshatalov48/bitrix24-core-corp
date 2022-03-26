this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var TaskResultMobile = /*#__PURE__*/function () {
	  function TaskResultMobile(taskId, userId, params) {
	    babelHelpers.classCallCheck(this, TaskResultMobile);
	    babelHelpers.defineProperty(this, "taskId", 0);
	    babelHelpers.defineProperty(this, "userId", 0);
	    babelHelpers.defineProperty(this, "itemsContentNode", null);
	    babelHelpers.defineProperty(this, "itemsNodes", null);
	    babelHelpers.defineProperty(this, "itemsWrapperNode", null);
	    babelHelpers.defineProperty(this, "needTutorial", false);
	    babelHelpers.defineProperty(this, "messages", {});
	    this.init(taskId, userId, params);
	    this.setHeightAutoFunction = this.setHeightAuto.bind(this);
	  }

	  babelHelpers.createClass(TaskResultMobile, [{
	    key: "init",
	    value: function init(taskId, userId, params) {
	      this.taskId = taskId;
	      this.userId = userId;
	      this.needTutorial = params.needTutorial;
	      this.messages = params.messages;
	      this.initExpand();
	      BXMobileApp.addCustomEvent('onPull-tasks', this.onPush.bind(this));
	    }
	  }, {
	    key: "blockResize",
	    value: function blockResize() {
	      this.contentNode.style.height = "".concat(this.containerNode.scrollHeight, "px");
	    }
	  }, {
	    key: "initExpand",
	    value: function initExpand() {
	      var _this = this;

	      if (this.contentNode) {
	        this.blockResize();
	      }

	      this.contentNode = document.getElementById("mobile-tasks-result-list-container-".concat(this.taskId));
	      this.containerNode = document.getElementById("tasks-result-list-wrapper-".concat(this.taskId));

	      if (!this.containerNode) {
	        return;
	      }

	      this.itemsContentNode = this.containerNode.querySelector('[data-role="mobile-tasks-widget--content"]');
	      this.itemsNodes = this.containerNode.querySelectorAll('[data-role="mobile-tasks-widget--result-item"]');
	      this.itemsWrapperNode = this.containerNode.querySelector('[data-role="mobile-tasks-widget--wrapper"]');

	      if (this.itemsWrapperNode && this.itemsNodes.length > 1) {
	        this.itemsNodes.length === 2 ? this.itemsContentNode.classList.add('--two-results') : this.itemsContentNode.classList.add('--many-results');
	      }

	      this.itemsContentNode && this.itemsContentNode.addEventListener('click', function () {
	        _this.itemsContentNode.classList.add('--open');

	        _this.itemsWrapperNode.style.height = "".concat(_this.itemsWrapperNode.scrollHeight, "px");

	        _this.itemsWrapperNode.addEventListener('transitionend', _this.setHeightAutoFunction);

	        if (_this.contentNode && _this.itemsWrapperNode.offsetHeight === 0) {
	          _this.contentNode.style.height = "".concat(_this.itemsWrapperNode.scrollHeight + _this.containerNode.scrollHeight, "px");
	        }
	      });
	      main_core_events.EventEmitter.subscribe('BX.Forum.Spoiler:toggle', this.onSpoilerToggle.bind(this));
	    }
	  }, {
	    key: "onSpoilerToggle",
	    value: function onSpoilerToggle(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          eventData = _event$getCompatData2[0];

	      if (!eventData.node) {
	        return;
	      }

	      var targetContentNode = eventData.node.closest('.mobile-tasks-result-list-container');

	      if (!targetContentNode || !this.contentNode || targetContentNode.id !== this.contentNode.id) {
	        return;
	      }

	      this.blockResize();
	    }
	  }, {
	    key: "setHeightAuto",
	    value: function setHeightAuto() {
	      this.itemsWrapperNode.style.height = 'auto';
	      this.itemsWrapperNode.removeEventListener('transitionend', this.setHeightAutoFunction);
	    }
	  }, {
	    key: "onPush",
	    value: function onPush(event) {
	      var command = event.command;
	      var params = event.params;

	      if (command === 'comment_add') {
	        this.onCommentAdd(command, params);
	      } else if (command === 'task_result_create' || command === 'task_result_update' || command === 'task_result_delete') {
	        this.onResultUpdate(command, params);
	      }
	    }
	  }, {
	    key: "onResultUpdate",
	    value: function onResultUpdate(command, params) {
	      if (!params.result || !params.result.taskId || params.result.taskId != this.taskId) {
	        return;
	      }

	      this.reloadResults();
	    }
	  }, {
	    key: "onCommentAdd",
	    value: function onCommentAdd(command, params) {
	      if (!this.needTutorial) {
	        return;
	      }

	      if (!params.taskId || params.taskId != this.taskId || !params.ownerId || params.ownerId != this.userId) {
	        return;
	      }

	      new BXMobileApp.UI.NotificationBar({
	        title: this.messages.tutorialTitle,
	        message: this.messages.tutorialMessage,
	        color: "#af000000",
	        textColor: "#ffffff",
	        isGlobal: true,
	        autoHideTimeout: 6500,
	        hideOnTap: true
	      }, 'copy').show(); // send ajax request to disable tutorial

	      main_core.ajax.runComponentAction('bitrix:tasks.widget.result', 'disableTutorial', {
	        mode: 'class',
	        data: {}
	      }).then(function (response) {});
	    }
	  }, {
	    key: "reloadResults",
	    value: function reloadResults() {
	      var _this2 = this;

	      main_core.ajax.runComponentAction('bitrix:tasks.widget.result', 'getResults', {
	        mode: 'class',
	        data: {
	          taskId: this.taskId,
	          mode: 'mobile'
	        }
	      }).then(function (response) {
	        if (!response.data) {
	          return;
	        }

	        _this2.containerNode.innerHTML = response.data;
	        main_core.Runtime.html(_this2.containerNode, response.data).then(function () {
	          _this2.initExpand();
	        });
	      });
	    }
	  }]);
	  return TaskResultMobile;
	}();

	exports.TaskResultMobile = TaskResultMobile;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map

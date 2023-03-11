this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var TaskResult = /*#__PURE__*/function () {
	  function TaskResult(taskId) {
	    babelHelpers.classCallCheck(this, TaskResult);
	    babelHelpers.defineProperty(this, "taskId", null);
	    babelHelpers.defineProperty(this, "itemsContentNode", null);
	    babelHelpers.defineProperty(this, "targetBtnDown", null);
	    babelHelpers.defineProperty(this, "targetBtnUp", null);
	    babelHelpers.defineProperty(this, "itemsNodes", null);
	    babelHelpers.defineProperty(this, "itemsWrapperNode", null);
	    this.init(taskId);
	    this.setHeightAutoFunction = this.setHeightAuto.bind(this);
	  }
	  babelHelpers.createClass(TaskResult, [{
	    key: "init",
	    value: function init(taskId) {
	      this.taskId = taskId;
	      this.initExpand();
	      main_core_events.EventEmitter.subscribe('onPullEvent-tasks', this.onPushResult.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Livefeed:recalculateComments', this.onRecalculateLivefeedComments.bind(this));
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', this.blockResize.bind(this));
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
	      this.initExpandButton();
	      if (this.contentNode) {
	        this.blockResize();
	      }
	      this.targetBtnDown && this.targetBtnDown.addEventListener('click', function () {
	        _this.targetBtnDown.classList.remove('--visible');
	        _this.targetBtnUp.classList.add('--visible');
	        _this.itemsContentNode.classList.add('--open');
	        _this.itemsWrapperNode.style.height = "".concat(_this.itemsWrapperNode.scrollHeight, "px");
	        _this.itemsWrapperNode.addEventListener('transitionend', _this.setHeightAutoFunction);
	        if (_this.contentNode) {
	          _this.contentNode.style.height = "".concat(_this.itemsWrapperNode.scrollHeight + _this.containerNode.scrollHeight, "px");
	        }
	      });
	      this.targetBtnUp && this.targetBtnUp.addEventListener('click', function () {
	        _this.targetBtnUp.classList.remove('--visible');
	        _this.targetBtnDown.classList.add('--visible');
	        _this.itemsContentNode.classList.remove('--open');
	        _this.itemsWrapperNode.style.height = "".concat(_this.itemsWrapperNode.scrollHeight, "px");
	        _this.itemsWrapperNode.clientHeight; // it's needed, P.Rafeev magic
	        _this.itemsWrapperNode.style.height = 0;
	        if (_this.contentNode) {
	          _this.contentNode.style.height = "".concat(_this.itemsNodes[0].offsetHeight + 35, "px");
	        }
	      });
	    }
	  }, {
	    key: "setHeightAuto",
	    value: function setHeightAuto() {
	      this.itemsWrapperNode.style.height = 'auto';
	      this.itemsWrapperNode.removeEventListener('transitionend', this.setHeightAutoFunction);
	    }
	  }, {
	    key: "initExpandButton",
	    value: function initExpandButton() {
	      this.contentNode = document.getElementById("tasks-result-list-container-".concat(this.taskId));
	      this.containerNode = document.getElementById("tasks-result-list-wrapper-".concat(this.taskId));
	      if (!this.containerNode) {
	        return;
	      }
	      this.itemsContentNode = this.containerNode.querySelector('[data-role="tasks-widget--content"]');
	      this.targetBtnDown = this.containerNode.querySelector('[data-role="tasks-widget--btn-down"]');
	      this.targetBtnUp = this.containerNode.querySelector('[data-role="tasks-widget--btn-up"]');
	      this.itemsNodes = this.containerNode.querySelectorAll('[data-role="tasks-widget--result-item"]');
	      this.itemsWrapperNode = this.containerNode.querySelector('[data-role="tasks-widget--wrapper"]');
	      if (!this.itemsWrapperNode || this.itemsNodes.length <= 1) {
	        return;
	      }
	      this.targetBtnDown.classList.add('--visible');
	      this.itemsNodes.length === 2 ? this.itemsContentNode.classList.add('--two-results') : this.itemsContentNode.classList.add('--many-results');
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
	      var targetContentNode = eventData.node.closest('.tasks-result-list-container');
	      if (!targetContentNode || !this.contentNode || targetContentNode.id !== this.contentNode.id) {
	        return;
	      }
	      this.blockResize();
	    }
	  }, {
	    key: "onPushResult",
	    value: function onPushResult(event) {
	      var _event$getData = event.getData(),
	        _event$getData2 = babelHelpers.slicedToArray(_event$getData, 2),
	        command = _event$getData2[0],
	        params = _event$getData2[1];
	      if (command !== 'task_result_create' && command !== 'task_result_update' && command !== 'task_result_delete') {
	        return;
	      }
	      if (!params.result || !params.result.taskId || params.result.taskId != this.taskId) {
	        return;
	      }
	      this.reloadResults();
	    }
	  }, {
	    key: "onRecalculateLivefeedComments",
	    value: function onRecalculateLivefeedComments(event) {
	      var _event$getCompatData3 = event.getCompatData(),
	        _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	        data = _event$getCompatData4[0];
	      if (!main_core.Type.isDomNode(data.rootNode)) {
	        return;
	      }
	      var taskResultContainer = data.rootNode.querySelector('.tasks-result-list-container');
	      if (!taskResultContainer || taskResultContainer.id !== this.contentNode.id) {
	        return;
	      }
	      this.blockResize();
	    }
	  }, {
	    key: "reloadResults",
	    value: function reloadResults() {
	      var _this2 = this;
	      main_core.ajax.runComponentAction('bitrix:tasks.widget.result', 'getResults', {
	        mode: 'class',
	        data: {
	          taskId: this.taskId
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
	  return TaskResult;
	}();

	exports.TaskResult = TaskResult;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map

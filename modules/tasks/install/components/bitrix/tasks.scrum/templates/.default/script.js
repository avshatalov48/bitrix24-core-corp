this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_loader,ui_entitySelector,ui_confetti,ui_draganddrop_draggable,pull_client,ui_label,main_popup,ui_dialogs_messagebox,main_core,main_core_events) {
	'use strict';

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, RequestSender);
	    this.signedParameters = options.signedParameters ? options.signedParameters : '';
	    this.debugMode = options.debugMode;
	  }

	  babelHelpers.createClass(RequestSender, [{
	    key: "getSignedParameters",
	    value: function getSignedParameters() {
	      return this.signedParameters;
	    }
	  }, {
	    key: "sendRequest",
	    value: function sendRequest(action) {
	      var _this = this;

	      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction(action, {
	          signedParameters: _this.signedParameters,
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "sendRequestToComponent",
	    value: function sendRequestToComponent() {
	      var _this2 = this;

	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var action = arguments.length > 1 ? arguments[1] : undefined;
	      data.debugMode = this.debugMode;
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:tasks.scrum', action, {
	          mode: 'class',
	          signedParameters: _this2.signedParameters,
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "batchUpdateItem",
	    value: function batchUpdateItem(data) {
	      return this.sendRequestToComponent(data, 'batchUpdateItem');
	    }
	  }, {
	    key: "batchRemoveItem",
	    value: function batchRemoveItem(data) {
	      return this.sendRequestToComponent(data, 'batchRemoveItem');
	    }
	  }, {
	    key: "updateItemSort",
	    value: function updateItemSort(data) {
	      return this.sendRequestToComponent(data, 'updateItemSort');
	    }
	  }, {
	    key: "updateSprintSort",
	    value: function updateSprintSort(data) {
	      return this.sendRequestToComponent(data, 'updateSprintSort');
	    }
	  }, {
	    key: "createSprint",
	    value: function createSprint(data) {
	      return this.sendRequestToComponent(data, 'createSprint');
	    }
	  }, {
	    key: "startSprint",
	    value: function startSprint(data) {
	      return this.sendRequestToComponent(data, 'startSprint');
	    }
	  }, {
	    key: "completeSprint",
	    value: function completeSprint(data) {
	      return this.sendRequestToComponent(data, 'completeSprint');
	    }
	  }, {
	    key: "createTask",
	    value: function createTask(data) {
	      return this.sendRequestToComponent(data, 'createTask');
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(data) {
	      return this.sendRequestToComponent(data, 'updateItem');
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(data) {
	      return this.sendRequestToComponent(data, 'removeItem');
	    }
	  }, {
	    key: "changeTaskResponsible",
	    value: function changeTaskResponsible(data) {
	      return this.sendRequestToComponent(data, 'changeTaskResponsible');
	    }
	  }, {
	    key: "getCurrentState",
	    value: function getCurrentState(data) {
	      return this.sendRequestToComponent(data, 'getCurrentState');
	    }
	  }, {
	    key: "removeSprint",
	    value: function removeSprint(data) {
	      return this.sendRequestToComponent(data, 'removeSprint');
	    }
	  }, {
	    key: "changeSprintName",
	    value: function changeSprintName(data) {
	      return this.sendRequestToComponent(data, 'changeSprintName');
	    }
	  }, {
	    key: "changeSprintDeadline",
	    value: function changeSprintDeadline(data) {
	      return this.sendRequestToComponent(data, 'changeSprintDeadline');
	    }
	  }, {
	    key: "getSprintCompletedItems",
	    value: function getSprintCompletedItems(data) {
	      return this.sendRequestToComponent(data, 'getSprintCompletedItems');
	    }
	  }, {
	    key: "getEpicDescriptionEditor",
	    value: function getEpicDescriptionEditor(data) {
	      return this.sendRequestToComponent(data, 'getEpicDescriptionEditor');
	    }
	  }, {
	    key: "getEpicDescription",
	    value: function getEpicDescription(data) {
	      return this.sendRequestToComponent(data, 'getEpicDescription');
	    }
	  }, {
	    key: "getEpicFiles",
	    value: function getEpicFiles(data) {
	      return this.sendRequestToComponent(data, 'getEpicFiles');
	    }
	  }, {
	    key: "getAddEpicFormButtons",
	    value: function getAddEpicFormButtons(data) {
	      return this.sendRequestToComponent(data, 'getAddEpicFormButtons');
	    }
	  }, {
	    key: "getViewEpicFormButtonsAction",
	    value: function getViewEpicFormButtonsAction(data) {
	      return this.sendRequestToComponent(data, 'getViewEpicFormButtons');
	    }
	  }, {
	    key: "createEpic",
	    value: function createEpic(data) {
	      return this.sendRequestToComponent(data, 'createEpic');
	    }
	  }, {
	    key: "getEpicsList",
	    value: function getEpicsList(data) {
	      return this.sendRequestToComponent(data, 'getEpicsList');
	    }
	  }, {
	    key: "getEpicListUrl",
	    value: function getEpicListUrl() {
	      return '/bitrix/services/main/ajax.php?mode=class&c=bitrix:tasks.scrum&action=getEpicsList';
	    }
	  }, {
	    key: "attachFilesToTask",
	    value: function attachFilesToTask(data) {
	      return this.sendRequestToComponent(data, 'attachFilesToTask');
	    }
	  }, {
	    key: "attachTagToTask",
	    value: function attachTagToTask(data) {
	      return this.sendRequestToComponent(data, 'attachTagToTask');
	    }
	  }, {
	    key: "batchAttachTagToTask",
	    value: function batchAttachTagToTask(data) {
	      return this.sendRequestToComponent(data, 'batchAttachTagToTask');
	    }
	  }, {
	    key: "deAttachTagToTask",
	    value: function deAttachTagToTask(data) {
	      return this.sendRequestToComponent(data, 'deAttachTagToTask');
	    }
	  }, {
	    key: "batchDeattachTagToTask",
	    value: function batchDeattachTagToTask(data) {
	      return this.sendRequestToComponent(data, 'batchDeattachTagToTask');
	    }
	  }, {
	    key: "updateItemEpic",
	    value: function updateItemEpic(data) {
	      return this.sendRequestToComponent(data, 'updateItemEpic');
	    }
	  }, {
	    key: "batchUpdateItemEpic",
	    value: function batchUpdateItemEpic(data) {
	      return this.sendRequestToComponent(data, 'batchUpdateItemEpic');
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic(data) {
	      return this.sendRequestToComponent(data, 'getEpic');
	    }
	  }, {
	    key: "editEpic",
	    value: function editEpic(data) {
	      return this.sendRequestToComponent(data, 'editEpic');
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic(data) {
	      return this.sendRequestToComponent(data, 'removeEpic');
	    }
	  }, {
	    key: "applyFilter",
	    value: function applyFilter(data) {
	      return this.sendRequestToComponent(data, 'applyFilter');
	    }
	  }, {
	    key: "getSprintStartButtons",
	    value: function getSprintStartButtons(data) {
	      return this.sendRequestToComponent(data, 'getSprintStartButtons');
	    }
	  }, {
	    key: "getSprintCompleteButtons",
	    value: function getSprintCompleteButtons(data) {
	      return this.sendRequestToComponent(data, 'getSprintCompleteButtons');
	    }
	  }, {
	    key: "getBurnDownChartData",
	    value: function getBurnDownChartData(data) {
	      return this.sendRequestToComponent(data, 'getBurnDownChartData');
	    }
	  }, {
	    key: "getTeamSpeedChartData",
	    value: function getTeamSpeedChartData(data) {
	      return this.sendRequestToComponent(data, 'getTeamSpeedChartData');
	    }
	  }, {
	    key: "getDodPanelData",
	    value: function getDodPanelData(data) {
	      return this.sendRequestToComponent(data, 'getDodPanelData');
	    }
	  }, {
	    key: "getDodComponent",
	    value: function getDodComponent(data) {
	      return this.sendRequestToComponent(data, 'getDodComponent');
	    }
	  }, {
	    key: "getDodButtons",
	    value: function getDodButtons(data) {
	      return this.sendRequestToComponent(data, 'getDodButtons');
	    }
	  }, {
	    key: "saveDod",
	    value: function saveDod(data) {
	      return this.sendRequestToComponent(data, 'saveDod');
	    }
	  }, {
	    key: "readAllTasksComment",
	    value: function readAllTasksComment(data) {
	      return this.sendRequest('tasks.task.comment.readAll', data);
	    }
	  }, {
	    key: "updateBorderColorToLinkedItems",
	    value: function updateBorderColorToLinkedItems(data) {
	      return this.sendRequestToComponent(data, 'updateBorderColorToLinkedItems');
	    }
	  }, {
	    key: "getAllUsedItemBorderColors",
	    value: function getAllUsedItemBorderColors(data) {
	      return this.sendRequestToComponent(data, 'getAllUsedItemBorderColors');
	    }
	  }, {
	    key: "getSubTaskItems",
	    value: function getSubTaskItems(data) {
	      return this.sendRequestToComponent(data, 'getSubTaskItems');
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        console.log(response);
	        return;
	      }

	      if (response.errors.length) {
	        var firstError = response.errors.shift();

	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var Filter = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Filter, _EventEmitter);

	  function Filter(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Filter);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Filter).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Filter');

	    _this.filterId = params.filterId;
	    _this.requestSender = params.requestSender;
	    _this.searchFieldApplied = false;

	    _this.initUiFilterManager();

	    _this.bindHandlers();

	    return _this;
	  }

	  babelHelpers.createClass(Filter, [{
	    key: "initUiFilterManager",
	    value: function initUiFilterManager() {
	      /* eslint-disable */
	      this.filterManager = BX.Main.filterManager.getById(this.filterId);
	      /* eslint-enable */
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
	    }
	  }, {
	    key: "isSearchFieldApplied",
	    value: function isSearchFieldApplied() {
	      return this.searchFieldApplied;
	    }
	  }, {
	    key: "getSearchContainer",
	    value: function getSearchContainer() {
	      return this.filterManager.getSearch().getContainer();
	    }
	  }, {
	    key: "scrollToSearchContainer",
	    value: function scrollToSearchContainer() {
	      var filterSearchContainer = this.getSearchContainer();

	      if (!this.isNodeInViewport(filterSearchContainer)) {
	        filterSearchContainer.scrollIntoView(true);
	      }
	    }
	  }, {
	    key: "onApplyFilter",
	    value: function onApplyFilter(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 5),
	          filterId = _event$getCompatData2[0],
	          values = _event$getCompatData2[1],
	          filterInstance = _event$getCompatData2[2],
	          promise = _event$getCompatData2[3],
	          params = _event$getCompatData2[4];

	      if (filterInstance.getSearch().getSearchString()) {
	        this.searchFieldApplied = true;
	      } else {
	        this.searchFieldApplied = false;
	      }

	      if (this.filterId !== filterId) {
	        return;
	      }

	      params.autoResolve = false;
	      this.emit('applyFilter', {
	        promise: promise
	      });
	    }
	  }, {
	    key: "addItemToListTypeField",
	    value: function addItemToListTypeField(name, item) {
	      //todo set item to list after epic crud actions
	      var fieldInstances = this.filterManager.getField(name);
	      var fieldOptions = this.filterManager.getFieldByName(name);

	      if (!fieldInstances || !fieldOptions) {
	        return;
	      }

	      fieldInstances.options.ITEMS.push(item);
	      fieldOptions.ITEMS.push(item);
	      var itemsNode = fieldInstances.node.querySelector('[data-name=' + name + ']');
	      var items = main_core.Dom.attr(itemsNode, 'data-items');
	      items.push(item);
	      main_core.Dom.attr(itemsNode, 'data-items', items);
	    }
	  }, {
	    key: "setValueToField",
	    value: function setValueToField(value) {
	      var filterApi = this.filterManager.getApi();
	      var filterFieldsValues = this.filterManager.getFilterFieldsValues();
	      filterFieldsValues[value.name] = value.value;
	      filterApi.setFields(filterFieldsValues);
	      filterApi.apply();
	    }
	  }, {
	    key: "setValuesToField",
	    value: function setValuesToField(values) {
	      var resetFields = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var filterApi = this.filterManager.getApi();
	      var filterFieldsValues = {};

	      if (!resetFields) {
	        filterFieldsValues = this.filterManager.getFilterFieldsValues();
	      }

	      values.forEach(function (value) {
	        filterFieldsValues[value.name] = value.value;
	      });
	      filterApi.setFields(filterFieldsValues);
	      filterApi.apply();
	    }
	  }, {
	    key: "getValueFromField",
	    value: function getValueFromField(value) {
	      var filterFieldsValues = this.filterManager.getFilterFieldsValues();
	      return filterFieldsValues[value.name];
	    }
	  }, {
	    key: "resetFilter",
	    value: function resetFilter() {
	      this.filterManager.resetFilter();
	    }
	  }, {
	    key: "applyFilter",
	    value: function applyFilter() {
	      this.filterManager.applyFilter();
	    }
	  }, {
	    key: "isNodeInViewport",
	    value: function isNodeInViewport(element) {
	      var rect = element.getBoundingClientRect();
	      return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
	    }
	  }]);
	  return Filter;
	}(main_core_events.EventEmitter);

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"tasks-counter-counter-button\">\n\t\t\t\t<span class=\"tasks-counter-counter-button-icon\"></span>\n\t\t\t\t<span class=\"tasks-counter-counter-button-text\">", "</span>\n\t\t\t</span>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counter-container\">\n\t\t\t\t", "\n\t\t\t\t<span class=\"tasks-counter-counters\">\n\t\t\t\t\t<span class=\"tasks-comment-icon ui-counter ui-counter-success\">\n\t\t\t\t\t\t<span class=\"ui-counter-inner\">", "</span>\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"tasks-comment-text\">", "</span>\n\t\t\t\t</span>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"", "\">", "</span>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Counters = /*#__PURE__*/function () {
	  function Counters(params) {
	    babelHelpers.classCallCheck(this, Counters);
	    this.requestSender = params.requestSender;
	    this.filter = params.filter;
	    this.counters = params.counters ? params.counters : null;
	    this.isOwnerCurrentUser = params.isOwnerCurrentUser;
	    this.userId = params.userId;
	    this.groupId = params.groupId;
	    this.container = null;
	    this.node = null;
	  }

	  babelHelpers.createClass(Counters, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Counters: HTMLElement for Counters not found');
	      }

	      this.container = container;

	      if (!this.isEmptyCounters()) {
	        main_core.Dom.append(this.renderCounters(), this.container);
	      }
	    }
	  }, {
	    key: "updateState",
	    value: function updateState(counters) {
	      this.counters = counters;

	      if (this.isNodeCreated()) {
	        this.destroy();

	        if (!this.isEmptyCounters()) {
	          main_core.Dom.append(this.renderCounters(), this.container);
	        }
	      } else {
	        if (!this.isEmptyCounters()) {
	          main_core.Dom.append(this.renderCounters(), this.container);
	        }
	      }
	    }
	  }, {
	    key: "renderTitle",
	    value: function renderTitle() {
	      var className = this.isEmptyCounters() ? 'tasks-page-name' : 'tasks-counter-page-name';
	      var text = this.isOwnerCurrentUser ? main_core.Loc.getMessage('TASKS_SCRUM_COUNTER_TOTAL') : main_core.Loc.getMessage('TASKS_SCRUM_COUNTER_TOTAL_EMPL');
	      return main_core.Tag.render(_templateObject(), className, text);
	    }
	  }, {
	    key: "renderCounters",
	    value: function renderCounters() {
	      var newCommentsInfo = this.counters['new_comments'];
	      var commentsNumber = parseInt(newCommentsInfo.counter, 10);
	      this.node = main_core.Tag.render(_templateObject2(), this.renderTitle(), commentsNumber, this.getCommentsLabel(commentsNumber), this.renderReadAllButton());
	      var commentIcon = this.node.querySelector('.tasks-comment-icon');
	      var commentText = this.node.querySelector('.tasks-comment-text');

	      var onMouseEnter = function onMouseEnter() {
	        return main_core.Dom.addClass(commentText, 'tasks-comment-text-hover');
	      };

	      var onMouseLeave = function onMouseLeave() {
	        return main_core.Dom.removeClass(commentText, 'tasks-comment-text-hover');
	      };

	      main_core.Event.bind(commentIcon, 'mouseenter', onMouseEnter);
	      main_core.Event.bind(commentIcon, 'mouseleave', onMouseLeave);
	      main_core.Event.bind(commentText, 'mouseenter', onMouseEnter);
	      main_core.Event.bind(commentText, 'mouseleave', onMouseLeave);
	      main_core.Event.bind(commentIcon, 'click', this.applyFilterRequiringAttentionToComments.bind(this));
	      main_core.Event.bind(commentText, 'click', this.applyFilterRequiringAttentionToComments.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "renderReadAllButton",
	    value: function renderReadAllButton() {
	      var title = main_core.Loc.getMessage('TASKS_SCRUM_NEW_COMMENTS_READ_ALL_TITLE');
	      var readAllButton = main_core.Tag.render(_templateObject3(), title);
	      main_core.Event.bind(readAllButton, 'click', this.onClickReadAll.bind(this));
	      return readAllButton;
	    }
	  }, {
	    key: "isEmptyCounters",
	    value: function isEmptyCounters() {
	      return !(this.counters && this.counters['new_comments'].counter > 0);
	    }
	  }, {
	    key: "onClickReadAll",
	    value: function onClickReadAll() {
	      var _this = this;

	      this.requestSender.readAllTasksComment({
	        groupId: this.groupId,
	        userId: this.userId
	      }).then(function (response) {
	        main_core.Dom.clean(_this.container);

	        _this.filter.applyFilter();
	      }).catch(function (response) {
	        _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "isNodeCreated",
	    value: function isNodeCreated() {
	      return this.node !== null;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "getCommentsLabel",
	    value: function getCommentsLabel(count) {
	      if (count > 5) {
	        return main_core.Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_2');
	      } else if (count === 1) {
	        return main_core.Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_0');
	      } else {
	        return main_core.Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_1');
	      }
	    }
	  }, {
	    key: "applyFilterRequiringAttentionToComments",
	    value: function applyFilterRequiringAttentionToComments() {
	      this.filter.setValuesToField([{
	        name: 'COUNTER_TYPE',
	        value: 'TASKS_COUNTER_TYPE_12582912'
	      }, {
	        name: 'PROBLEM',
	        value: '12582912'
	      }], true);
	    }
	  }]);
	  return Counters;
	}();

	var View = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(View, _EventEmitter);

	  function View(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, View);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(View).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.View');

	    _this.isOwnerCurrentUser = params.isOwnerCurrentUser === 'Y';
	    _this.requestSender = new RequestSender({
	      signedParameters: params.signedParameters,
	      debugMode: params.debugMode
	    });
	    _this.filter = new Filter({
	      filterId: params.filterId,
	      scrumManager: babelHelpers.assertThisInitialized(_this),
	      requestSender: _this.requestSender
	    });
	    _this.counters = new Counters({
	      requestSender: _this.requestSender,
	      filter: _this.filter,
	      counters: params.counters,
	      userId: params.userId,
	      groupId: params.groupId,
	      isOwnerCurrentUser: params.isOwnerCurrentUser
	    });
	    _this.userId = parseInt(params.userId, 10);
	    _this.groupId = parseInt(params.groupId, 10);
	    return _this;
	  }

	  babelHelpers.createClass(View, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Scrum: HTMLElement for scrum not found');
	      }
	    }
	  }, {
	    key: "renderCountersTo",
	    value: function renderCountersTo(container) {
	      this.counters.renderTo(container);
	    }
	  }, {
	    key: "getCurrentUserId",
	    value: function getCurrentUserId() {
	      return this.userId;
	    }
	  }]);
	  return View;
	}(main_core_events.EventEmitter);

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"files_chooser\">\n\t\t\t<div id=\"diskuf-selectdialog-", "\" class=\"diskuf-files-entity diskuf-selectdialog bx-disk\">\n\t\t\t\t<div class=\"diskuf-files-block\">\n\t\t\t\t\t<div class=\"diskuf-placeholder\">\n\t\t\t\t\t\t<table class=\"files-list\">\n\t\t\t\t\t\t\t<tbody class=\"diskuf-placeholder-tbody\"></tbody>\n\t\t\t\t\t\t</table>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"diskuf-extended\" style=\"display: block\">\n\t\t\t\t\t<input type=\"hidden\" name=\"[", "][]\" value=\"\"/>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<label for=\"file_loader_", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<input class=\"diskuf-fileUploader\" id=\"file_loader_", "\" type=\n\t\t\t\t\t\t\t\"file\" multiple=\"multiple\" size=\"1\" style=\"display: none\"/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link-cloud\" data-bx-doc-handler=\"gdrive\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var DiskManager = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(DiskManager, _EventEmitter);

	  function DiskManager() {
	    var _this;

	    babelHelpers.classCallCheck(this, DiskManager);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DiskManager).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.DiskManager');

	    _this.diskUrls = {
	      urlSelect: '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID=' + main_core.Loc.getMessage('SITE_ID'),
	      urlRenameFile: '/bitrix/tools/disk/uf.php?action=renameFile',
	      urlDeleteFile: '/bitrix/tools/disk/uf.php?action=deleteFile',
	      urlUpload: '/bitrix/tools/disk/uf.php?action=uploadFile&ncc=1'
	    };
	    _this.attachedIds = [];
	    return _this;
	  }

	  babelHelpers.createClass(DiskManager, [{
	    key: "showAttachmentMenu",
	    value: function showAttachmentMenu(node) {
	      var _this2 = this;

	      var controlId = main_core.Text.getRandom();
	      this.popup = new main_popup.Popup("disk-manager-attachment-menu-".concat(main_core.Text.getRandom()), node, {
	        content: this.getAttachmentsLoaderContent(controlId),
	        autoHide: true,
	        closeByEsc: true,
	        angle: false
	      });
	      this.popup.show();
	      BX.Disk.UF.add({
	        UID: controlId,
	        controlName: "[".concat(controlId, "][]"),
	        hideSelectDialog: false,
	        urlSelect: this.diskUrls.urlSelect,
	        urlRenameFile: this.diskUrls.urlRenameFile,
	        urlDeleteFile: this.diskUrls.urlDeleteFile,
	        urlUpload: this.diskUrls.urlUpload
	      });
	      BX.onCustomEvent(this.popup.contentContainer.querySelector('#files_chooser'), 'DiskLoadFormController', ['show']);
	      main_core_events.EventEmitter.subscribe('onFinish', function () {
	        _this2.popup.close();

	        _this2.emit('onFinish', _this2.attachedIds);
	      });
	    }
	  }, {
	    key: "getAttachmentsLoaderContent",
	    value: function getAttachmentsLoaderContent(controlId) {
	      var filesChooser = main_core.Tag.render(_templateObject$1(), controlId, controlId, controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_COMPUTER'), controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_B24'), main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_CLOUD'));
	      BX.addCustomEvent(filesChooser, 'OnFileUploadSuccess', this.onFileUploadSuccess.bind(this)); //todo show loader

	      return filesChooser;
	    }
	  }, {
	    key: "onFileUploadSuccess",
	    value: function onFileUploadSuccess(fileResult, uf, file, uploaderFile) {
	      if (typeof file === 'undefined' || typeof uploaderFile === 'undefined') {
	        return;
	      }

	      this.attachedIds.push(fileResult.element_id.toString());
	    }
	  }]);
	  return DiskManager;
	}(main_core_events.EventEmitter);

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-actions-panel-container\">\n\t\t\t\t<div class=\"tasks-scrum-actions-panel\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div  id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-remove\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div  id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-decomposition\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div  id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-tags\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div  id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-tags\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-backlog\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-sprint\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-move\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-attachment\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-task\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject$2 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ActionsPanel = /*#__PURE__*/function () {
	  function ActionsPanel(options) {
	    babelHelpers.classCallCheck(this, ActionsPanel);
	    this.actionPanelNodeId = 'tasks-scrum-actions-panel';
	    this.bindElement = options.bindElement;
	    this.itemList = babelHelpers.objectSpread({}, {
	      task: {
	        activity: false
	      },
	      attachment: {
	        activity: false
	      },
	      move: {
	        activity: false
	      },
	      sprint: {
	        activity: false
	      },
	      backlog: {
	        activity: false
	      },
	      tags: {
	        activity: false
	      },
	      epic: {
	        activity: false
	      },
	      decomposition: {
	        activity: false
	      },
	      remove: {
	        activity: false
	      }
	    }, options.itemList);
	    this.listBlockBlurNodes = new Set();
	  }

	  babelHelpers.createClass(ActionsPanel, [{
	    key: "showPanel",
	    value: function showPanel() {
	      var _this = this;

	      main_core.Dom.remove(document.getElementById(this.actionPanelNodeId));
	      var actionsPanelContainer = this.calculatePanelPosition(this.createActionPanel());
	      this.setBlockBlurNode(actionsPanelContainer);
	      main_core.Dom.append(actionsPanelContainer, document.body);

	      var customBlur = function customBlur(event) {
	        var hasNode = false;

	        _this.listBlockBlurNodes.forEach(function (blockBlurNode) {
	          if (blockBlurNode.contains(event.target)) {
	            hasNode = true;
	          }
	        });

	        if (!hasNode) {
	          main_core.Dom.remove(actionsPanelContainer);
	          main_core.Event.unbind(document, 'click', customBlur);
	        }
	      };

	      main_core.Event.bind(document, 'click', customBlur);
	      this.bindItems();
	      var actionsPanel = actionsPanelContainer.querySelector('.tasks-scrum-actions-panel');

	      if (main_core.Dom.hasClass(actionsPanel.lastElementChild, 'tasks-scrum-actions-panel-separator')) {
	        main_core.Dom.remove(actionsPanel.lastElementChild);
	      }
	    }
	  }, {
	    key: "setBlockBlurNode",
	    value: function setBlockBlurNode(node) {
	      this.listBlockBlurNodes.add(node);
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return Boolean(document.getElementById(this.actionPanelNodeId));
	    }
	  }, {
	    key: "createActionPanel",
	    value: function createActionPanel() {
	      var task = '';
	      var attachment = '';
	      var move = '';
	      var sprint = '';
	      var backlog = '';
	      var tags = '';
	      var epic = '';
	      var decomposition = '';
	      var remove = '';

	      if (this.itemList.task.activity) {
	        this.showTaskActionButtonNodeId = 'tasks-scrum-actions-panel-btn-task';
	        task = main_core.Tag.render(_templateObject$2(), this.showTaskActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK'));
	      }

	      if (this.itemList.attachment.activity) {
	        this.showAttachmentActionButtonNodeId = 'tasks-scrum-actions-panel-btn-attachment';
	        attachment = main_core.Tag.render(_templateObject2$1(), this.showAttachmentActionButtonNodeId);
	      }

	      if (this.itemList.move.activity) {
	        this.showMoveActionButtonNodeId = 'tasks-scrum-actions-panel-btn-move';
	        move = main_core.Tag.render(_templateObject3$1(), this.showMoveActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE'));
	      }

	      if (this.itemList.sprint.activity) {
	        this.sprintActionButtonNodeId = 'tasks-scrum-actions-panel-btn-sprint';
	        sprint = main_core.Tag.render(_templateObject4(), this.sprintActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SPRINT'));
	      }

	      if (this.itemList.backlog.activity) {
	        this.backlogActionButtonNodeId = 'tasks-scrum-actions-panel-btn-backlog';
	        backlog = main_core.Tag.render(_templateObject5(), this.backlogActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_BACKLOG'));
	      }

	      if (this.itemList.tags.activity) {
	        this.tagsActionButtonNodeId = 'tasks-scrum-actions-panel-btn-tags';
	        tags = main_core.Tag.render(_templateObject6(), this.tagsActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TAGS'));
	      }

	      if (this.itemList.epic.activity) {
	        this.epicActionButtonNodeId = 'tasks-scrum-actions-panel-btn-epic';
	        epic = main_core.Tag.render(_templateObject7(), this.epicActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_EPIC'));
	      }

	      if (this.itemList.decomposition.activity) {
	        this.decompositionActionButtonNodeId = 'tasks-scrum-actions-panel-btn-decomposition';
	        decomposition = main_core.Tag.render(_templateObject8(), this.decompositionActionButtonNodeId);
	      }

	      if (this.itemList.remove.activity) {
	        this.removeActionButtonNodeId = 'tasks-scrum-actions-panel-btn-remove';
	        remove = main_core.Tag.render(_templateObject9(), this.removeActionButtonNodeId);
	      }

	      return main_core.Tag.render(_templateObject10(), this.actionPanelNodeId, task, attachment, move, sprint, backlog, tags, epic, decomposition, remove);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Dom.remove(document.getElementById(this.actionPanelNodeId));
	    }
	  }, {
	    key: "bindItems",
	    value: function bindItems() {
	      if (this.itemList.task.activity) {
	        main_core.Event.bind(document.getElementById(this.showTaskActionButtonNodeId), 'click', this.itemList.task.callback);
	      }

	      if (this.itemList.attachment.activity) {
	        main_core.Event.bind(document.getElementById(this.showAttachmentActionButtonNodeId), 'click', this.itemList.attachment.callback);
	      }

	      if (this.itemList.move.activity) {
	        main_core.Event.bind(document.getElementById(this.showMoveActionButtonNodeId), 'click', this.itemList.move.callback);
	      }

	      if (this.itemList.sprint.activity) {
	        main_core.Event.bind(document.getElementById(this.sprintActionButtonNodeId), 'click', this.itemList.sprint.callback);
	      }

	      if (this.itemList.backlog.activity) {
	        main_core.Event.bind(document.getElementById(this.backlogActionButtonNodeId), 'click', this.itemList.backlog.callback);
	      }

	      if (this.itemList.tags.activity) {
	        main_core.Event.bind(document.getElementById(this.tagsActionButtonNodeId), 'click', this.itemList.tags.callback);
	      }

	      if (this.itemList.epic.activity) {
	        main_core.Event.bind(document.getElementById(this.epicActionButtonNodeId), 'click', this.itemList.epic.callback);
	      }

	      if (this.itemList.remove.activity) {
	        main_core.Event.bind(document.getElementById(this.removeActionButtonNodeId), 'click', this.itemList.remove.callback);
	      }

	      if (this.itemList.decomposition.activity) {
	        main_core.Event.bind(document.getElementById(this.decompositionActionButtonNodeId), 'click', this.itemList.decomposition.callback);
	      }
	    }
	  }, {
	    key: "calculatePanelPosition",
	    value: function calculatePanelPosition(panel) {
	      var position = main_core.Dom.getPosition(this.bindElement);
	      var top = "".concat(position.top, "px");
	      var left = "".concat(position.left, "px");
	      var fakePanel = panel.cloneNode(true);
	      fakePanel.style.visibility = 'hidden';
	      fakePanel.style.top = "".concat(position.top, "px");
	      fakePanel.style.left = "".concat(position.left, "px");
	      main_core.Dom.append(fakePanel, document.body);

	      if (this.isPanelWiderThanViewport(fakePanel)) {
	        var fakePanelRect = fakePanel.getBoundingClientRect();
	        var windowWidth = window.innerWidth || document.documentElement.clientWidth;
	        left = "".concat(fakePanelRect.left - (fakePanelRect.right - windowWidth + 40), "px");
	      }

	      main_core.Dom.remove(fakePanel);
	      panel.style.top = top;
	      panel.style.left = left;
	      panel.style.zIndex = 1100;
	      return panel;
	    }
	  }, {
	    key: "isPanelWiderThanViewport",
	    value: function isPanelWiderThanViewport(element) {
	      var rect = element.getBoundingClientRect();
	      var windowWidth = window.innerWidth || document.documentElement.clientWidth;
	      return rect.right > windowWidth;
	    }
	  }]);
	  return ActionsPanel;
	}();

	function _templateObject$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span id=\"", "\" class=\"task-title-indicators\">\n\t\t\t\t<div class=\"task-attachment-counter ui-label ui-label-sm ui-label-light\">\n\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class='task-checklist-counter ui-label ui-label-sm ui-label-light'>\n\t\t\t\t\t<span class='ui-label-inner'>", "/", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class='task-comments-counter'>\n\t\t\t\t\t<div class='ui-counter ui-counter-success'>\n\t\t\t\t\t\t<div class='ui-counter-inner'>", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</span>\n\t\t"]);

	  _templateObject$3 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TaskCounts = /*#__PURE__*/function () {
	  function TaskCounts(params) {
	    babelHelpers.classCallCheck(this, TaskCounts);
	    this.setItemId(params.itemId);
	    this.setAttachedFilesCount(params.attachedFilesCount);
	    this.setCheckListComplete(params.checkListComplete);
	    this.setCheckListAll(params.checkListAll);
	    this.setNewCommentsCount(params.newCommentsCount);
	  }

	  babelHelpers.createClass(TaskCounts, [{
	    key: "setItemId",
	    value: function setItemId(itemId) {
	      this.itemId = main_core.Type.isInteger(itemId) ? parseInt(itemId, 10) : main_core.Type.isString(itemId) && itemId ? itemId : main_core.Text.getRandom();
	    }
	  }, {
	    key: "setAttachedFilesCount",
	    value: function setAttachedFilesCount(count) {
	      this.attachedFilesCount = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    }
	  }, {
	    key: "getAttachedFilesCount",
	    value: function getAttachedFilesCount() {
	      return this.attachedFilesCount;
	    }
	  }, {
	    key: "setCheckListComplete",
	    value: function setCheckListComplete(count) {
	      this.checkListComplete = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    }
	  }, {
	    key: "getCheckListComplete",
	    value: function getCheckListComplete() {
	      return this.checkListComplete;
	    }
	  }, {
	    key: "setCheckListAll",
	    value: function setCheckListAll(count) {
	      this.checkListAll = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    }
	  }, {
	    key: "getCheckListAll",
	    value: function getCheckListAll() {
	      return this.checkListAll;
	    }
	  }, {
	    key: "setNewCommentsCount",
	    value: function setNewCommentsCount(count) {
	      this.newCommentsCount = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    }
	  }, {
	    key: "getNewCommentsCount",
	    value: function getNewCommentsCount() {
	      return this.newCommentsCount;
	    }
	  }, {
	    key: "renderIndicators",
	    value: function renderIndicators() {
	      this.indicatorsNodeId = 'tasks-scrum-item-indicators-' + this.itemId;
	      return main_core.Tag.render(_templateObject$3(), this.indicatorsNodeId, this.attachedFilesCount, this.checkListComplete, this.checkListAll, this.newCommentsCount);
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      this.indicatorsNode = document.getElementById(this.indicatorsNodeId);
	      this.attachmentNode = this.indicatorsNode.querySelector('.task-attachment-counter');
	      this.checklistNode = this.indicatorsNode.querySelector('.task-checklist-counter');
	      this.commentsNode = this.indicatorsNode.querySelector('.task-comments-counter');
	      this.updateVisibility();
	    }
	  }, {
	    key: "updateIndicators",
	    value: function updateIndicators(data) {
	      if (!this.indicatorsNode) {
	        return;
	      }

	      if (data.attachedFilesCount) {
	        this.attachedFilesCount = parseInt(data.attachedFilesCount, 10);
	        this.attachmentNode.firstElementChild.textContent = this.attachedFilesCount;
	      }

	      if (data.checkListComplete) {
	        this.checkListComplete = parseInt(data.checkListComplete, 10);
	        this.checklistNode.firstElementChild.textContent = this.checkListComplete + '/' + this.checkListAll;
	      }

	      if (data.checkListAll) {
	        this.checkListAll = parseInt(data.checkListAll, 10);
	        this.checklistNode.firstElementChild.textContent = this.checkListComplete + '/' + this.checkListAll;
	      }

	      if (data.newCommentsCount) {
	        this.newCommentsCount = parseInt(data.newCommentsCount, 10);
	        var innerCommentCounter = this.commentsNode.querySelector('.ui-counter-inner');
	        innerCommentCounter.textContent = this.newCommentsCount;
	      }

	      this.updateVisibility();
	    }
	  }, {
	    key: "updateVisibility",
	    value: function updateVisibility() {
	      if (this.attachedFilesCount > 0) {
	        this.showNode(this.attachmentNode);
	      } else {
	        this.hideNode(this.attachmentNode);
	      }

	      if (this.checkListAll > 0) {
	        this.showNode(this.checklistNode);
	      } else {
	        this.hideNode(this.checklistNode);
	      }

	      if (this.newCommentsCount > 0) {
	        this.showNode(this.commentsNode);
	      } else {
	        this.hideNode(this.commentsNode);
	      }
	    }
	  }, {
	    key: "showNode",
	    value: function showNode(node) {
	      main_core.Dom.style(node, 'display', 'inline-flex');
	    }
	  }, {
	    key: "hideNode",
	    value: function hideNode(node) {
	      main_core.Dom.style(node, 'display', 'none');
	    }
	  }]);
	  return TaskCounts;
	}();

	var StoryPoints = /*#__PURE__*/function () {
	  function StoryPoints() {
	    babelHelpers.classCallCheck(this, StoryPoints);
	    this.clearPoints();
	    this.differencePoints = 0;
	  }

	  babelHelpers.createClass(StoryPoints, [{
	    key: "setPoints",
	    value: function setPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints)) {
	        return;
	      }

	      this.saveDifferencePoints(this.storyPoints ? this.storyPoints : 0, storyPoints);
	      this.storyPoints = String(storyPoints);
	    }
	  }, {
	    key: "getPoints",
	    value: function getPoints() {
	      return String(this.storyPoints);
	    }
	  }, {
	    key: "addPoints",
	    value: function addPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        return;
	      }

	      var currentStoryPoints = this.storyPoints !== '' ? parseFloat(this.storyPoints) : 0;
	      var inputStoryPoints = parseFloat(storyPoints);
	      var result = currentStoryPoints + inputStoryPoints;

	      if (main_core.Type.isFloat(result)) {
	        result = result.toFixed(1);
	      }

	      this.storyPoints = String(result);
	    }
	  }, {
	    key: "subtractPoints",
	    value: function subtractPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        return;
	      }

	      var currentStoryPoints = this.storyPoints !== '' ? parseFloat(this.storyPoints) : 0;
	      var inputStoryPoints = parseFloat(storyPoints);
	      var result = currentStoryPoints - inputStoryPoints;

	      if (main_core.Type.isFloat(result)) {
	        result = result.toFixed(1);
	      }

	      this.storyPoints = String(result);
	    }
	  }, {
	    key: "clearPoints",
	    value: function clearPoints() {
	      this.storyPoints = '';
	    }
	  }, {
	    key: "saveDifferencePoints",
	    value: function saveDifferencePoints(firstPoints, secondPoints) {
	      this.differencePoints = 0;

	      if (main_core.Type.isUndefined(firstPoints) || isNaN(parseFloat(firstPoints))) {
	        return;
	      }

	      if (main_core.Type.isUndefined(secondPoints) || isNaN(parseFloat(secondPoints))) {
	        return;
	      }

	      firstPoints = parseFloat(firstPoints);
	      secondPoints = parseFloat(secondPoints);
	      this.differencePoints = secondPoints - firstPoints;
	    }
	  }, {
	    key: "getDifferencePoints",
	    value: function getDifferencePoints() {
	      return this.differencePoints;
	    }
	  }]);
	  return StoryPoints;
	}();

	function _templateObject8$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-tags-container\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject8$1 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-tags-container\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject7$1 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-subtasks-tick\">\n\t\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-down\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6$1 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-subtasks-btn\">\n\t\t\t\t<span class=\"tasks-scrum-item-subtasks-icon\"></span>\n\t\t\t\t<span class=\"tasks-scrum-item-subtasks-count\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$1 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-story-points\">\n\t\t\t\t<div class=\"tasks-scrum-item-story-points-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-auto ui-ctl-no-border\">\n\t\t\t\t\t<div class=\"ui-ctl-element\" contenteditable=\"false\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-icon ui-icon-common-user tasks-scrum-item-responsible\"><i></i></div>"]);

	  _templateObject3$2 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-name-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-no-border\">\n\t\t\t\t<div class=\"ui-ctl-element\" contenteditable=\"false\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div data-item-id=\"", "\" data-sort=\n\t\t\t\t\"", "\" class=\"", "\">\n\t\t\t\t<div class=\"tasks-scrum-item-inner\">\n\t\t\t\t\t<div class=\"tasks-scrum-item-group-mode-container\">\n\t\t\t\t\t\t<input type=\"checkbox\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-item-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-item-params\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$4 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	//todo single responsibility principle
	var Item = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Item, _EventEmitter);

	  function Item(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Item);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item');

	    _this.setItemParams(params);

	    _this.groupMode = false;
	    _this.previewMode = false;
	    return _this;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "setItemParams",
	    value: function setItemParams(params) {
	      this.setItemNode();
	      this.setItemId(params.itemId);
	      this.setTmpId(params.tmpId);
	      this.setName(params.name);
	      this.setItemType(params.itemType);
	      this.setSort(params.sort);
	      this.setEntityId(params.entityId);
	      this.setEntityType(params.entityType);
	      this.setParentId(params.parentId);
	      this.setSourceId(params.sourceId);
	      this.setParentSourceId(params.parentSourceId);
	      this.setResponsible(params.responsible);
	      this.setStoryPoints(params.storyPoints);
	      this.setInfo(params.info);
	      this.setCompleted(params.completed);
	      this.setDisableStatus(this.isCompleted());
	      this.setAllowedActions(params.allowedActions);
	      this.setEpic(params.epic);
	      this.setTags(params.tags);
	      this.setParentTask(params.isParentTask);
	      this.setSubTasksCount(params.subTasksCount);
	      this.setLinkedTask(params.isLinkedTask);
	      this.setParentTaskId(params.parentTaskId);
	      this.setSubTask(params.isSubTask);
	      this.setTaskCounts(params);
	    }
	  }, {
	    key: "setItemId",
	    value: function setItemId(itemId) {
	      this.itemId = main_core.Type.isInteger(itemId) ? parseInt(itemId, 10) : main_core.Type.isString(itemId) && itemId ? itemId : main_core.Text.getRandom();

	      if (this.isNodeCreated()) {
	        this.getItemNode().dataset.itemId = this.itemId;
	      }
	    }
	  }, {
	    key: "getItemId",
	    value: function getItemId() {
	      return this.itemId;
	    }
	  }, {
	    key: "setTmpId",
	    value: function setTmpId(tmpId) {
	      this.tmpId = main_core.Type.isString(tmpId) ? tmpId : '';
	    }
	  }, {
	    key: "getTmpId",
	    value: function getTmpId() {
	      return this.tmpId;
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.name = main_core.Type.isString(name) ? name : 'Name';

	      if (this.isNodeCreated()) {
	        var nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name-field');
	        nameNode.querySelector('.ui-ctl-element').textContent = main_core.Text.encode(this.name);
	      }
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "setItemType",
	    value: function setItemType(type) {
	      this.itemType = main_core.Type.isString(type) ? type : 'task';
	    }
	  }, {
	    key: "getItemType",
	    value: function getItemType() {
	      return this.itemType;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.setPreviousSort(this.sort);
	      this.sort = main_core.Type.isInteger(sort) ? parseInt(sort, 10) : 0;

	      if (this.isNodeCreated()) {
	        main_core.Dom.attr(this.getItemNode(), 'data-sort', this.sort);
	      }
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.sort;
	    }
	  }, {
	    key: "setPreviousSort",
	    value: function setPreviousSort(sort) {
	      this.previousSort = main_core.Type.isInteger(sort) ? parseInt(sort, 10) : 0;
	    }
	  }, {
	    key: "getPreviousSort",
	    value: function getPreviousSort() {
	      return this.previousSort;
	    }
	  }, {
	    key: "setEntityId",
	    value: function setEntityId(entityId) {
	      this.entityId = main_core.Type.isInteger(entityId) ? parseInt(entityId, 10) : 0;
	    }
	  }, {
	    key: "getEntityId",
	    value: function getEntityId() {
	      return this.entityId;
	    }
	  }, {
	    key: "setEntityType",
	    value: function setEntityType(entityType) {
	      this.entityType = new Set(['backlog', 'sprint']).has(entityType) ? entityType : 'backlog';
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return this.entityType;
	    }
	  }, {
	    key: "setParentId",
	    value: function setParentId(parentId) {
	      this.parentId = main_core.Type.isInteger(parentId) ? parseInt(parentId, 10) : 0;
	    }
	  }, {
	    key: "getParentId",
	    value: function getParentId() {
	      return this.parentId;
	    }
	  }, {
	    key: "setSourceId",
	    value: function setSourceId(sourceId) {
	      this.sourceId = main_core.Type.isInteger(sourceId) ? parseInt(sourceId, 10) : 0;
	    }
	  }, {
	    key: "getSourceId",
	    value: function getSourceId() {
	      return this.sourceId;
	    }
	  }, {
	    key: "setParentSourceId",
	    value: function setParentSourceId(sourceId) {
	      this.parentSourceId = main_core.Type.isInteger(sourceId) ? parseInt(sourceId, 10) : 0;
	    }
	  }, {
	    key: "getParentSourceId",
	    value: function getParentSourceId() {
	      return this.parentSourceId;
	    }
	  }, {
	    key: "setResponsible",
	    value: function setResponsible(responsible) {
	      this.responsible = main_core.Type.isPlainObject(responsible) ? responsible : null;

	      if (this.responsible && this.isNodeCreated()) {
	        this.updateResponsible();
	      }
	    }
	  }, {
	    key: "getResponsible",
	    value: function getResponsible() {
	      return this.responsible;
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      if (!this.storyPoints) {
	        this.storyPoints = new StoryPoints();
	      }

	      this.storyPoints.setPoints(storyPoints);

	      if (this.isNodeCreated()) {
	        var storyPointsNode = this.getItemNode().querySelector('.tasks-scrum-item-story-points');
	        storyPointsNode.querySelector('.ui-ctl-element').textContent = main_core.Text.encode(this.getStoryPoints().getPoints());
	        this.sendEventToUpdateStoryPoints();
	      }
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "setCompleted",
	    value: function setCompleted(value) {
	      this.completed = value === 'Y';

	      if (this.isNodeCreated()) {
	        this.updateCompletedStatus();
	      }
	    }
	  }, {
	    key: "setAllowedActions",
	    value: function setAllowedActions(allowedActions) {
	      this.allowedActions = main_core.Type.isPlainObject(allowedActions) ? allowedActions : {};
	    }
	  }, {
	    key: "setEpic",
	    value: function setEpic(epic) {
	      this.epic = main_core.Type.isPlainObject(epic) ? epic : null;
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic() {
	      return this.epic;
	    }
	  }, {
	    key: "setTags",
	    value: function setTags(tags) {
	      this.tags = main_core.Type.isArray(tags) ? tags : [];
	    }
	  }, {
	    key: "getTags",
	    value: function getTags() {
	      return this.tags;
	    }
	  }, {
	    key: "setParentTask",
	    value: function setParentTask(value) {
	      this.parentTask = value === 'Y';
	    }
	  }, {
	    key: "isParentTask",
	    value: function isParentTask() {
	      return this.parentTask;
	    }
	  }, {
	    key: "setSubTasksCount",
	    value: function setSubTasksCount(count) {
	      this.subTasksCount = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    }
	  }, {
	    key: "getSubTasksCount",
	    value: function getSubTasksCount() {
	      return this.subTasksCount;
	    }
	  }, {
	    key: "setLinkedTask",
	    value: function setLinkedTask(value) {
	      this.linkedTask = value === 'Y';
	    }
	  }, {
	    key: "isLinkedTask",
	    value: function isLinkedTask() {
	      return this.linkedTask;
	    }
	  }, {
	    key: "setParentTaskId",
	    value: function setParentTaskId(id) {
	      this.parentTaskId = main_core.Type.isInteger(id) ? parseInt(id, 10) : 0;
	    }
	  }, {
	    key: "getParentTaskId",
	    value: function getParentTaskId() {
	      return this.parentTaskId;
	    }
	  }, {
	    key: "setSubTask",
	    value: function setSubTask(value) {
	      this.subTask = value === 'Y';
	    }
	  }, {
	    key: "isSubTask",
	    value: function isSubTask() {
	      return this.subTask;
	    }
	  }, {
	    key: "setTaskCounts",
	    value: function setTaskCounts(params) {
	      this.taskCounts = this.itemType === 'task' ? new TaskCounts(params) : null;
	    }
	  }, {
	    key: "getTaskCounts",
	    value: function getTaskCounts() {
	      return this.taskCounts;
	    }
	  }, {
	    key: "setInfo",
	    value: function setInfo(info) {
	      if (main_core.Type.isUndefined(info)) {
	        this.info = {
	          color: '',
	          borderColor: ''
	        };
	        return;
	      }

	      this.info = info;
	      this.setBorderColor(this.info.borderColor);
	    }
	  }, {
	    key: "getInfo",
	    value: function getInfo() {
	      return this.info;
	    }
	  }, {
	    key: "setBorderColor",
	    value: function setBorderColor(color) {
	      this.info.borderColor = main_core.Type.isString(color) ? color : '';

	      if (this.isNodeCreated()) {
	        if (this.getBorderColor()) {
	          main_core.Dom.style(this.getItemNode(), 'border', '2px solid ' + this.getBorderColor());
	        } else {
	          main_core.Dom.style(this.getItemNode(), 'border', null);
	        }
	      }
	    }
	  }, {
	    key: "getBorderColor",
	    value: function getBorderColor() {
	      return main_core.Type.isString(this.info.borderColor) ? this.info.borderColor : '';
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return this.completed;
	    }
	  }, {
	    key: "updateCompletedStatus",
	    value: function updateCompletedStatus() {
	      var nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name');
	      var nameTextNode = nameNode.querySelector('.ui-ctl-element');

	      if (this.isCompleted()) {
	        main_core.Dom.style(nameTextNode, 'textDecoration', 'line-through');
	      } else {
	        main_core.Dom.style(nameTextNode, 'textDecoration', null);
	      }

	      this.sendEventToUpdateStoryPoints();
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return this.disableStatus;
	    }
	  }, {
	    key: "setMoveActivity",
	    value: function setMoveActivity(value) {
	      this.moveActivity = Boolean(value);
	    }
	  }, {
	    key: "isMovable",
	    value: function isMovable() {
	      return this.moveActivity;
	    }
	  }, {
	    key: "setDisableStatus",
	    value: function setDisableStatus(status) {
	      this.disableStatus = Boolean(status);

	      if (!this.isNodeCreated()) {
	        return;
	      }

	      if (status) {
	        this.hideNode(this.getItemNode().querySelector('.tasks-scrum-dragndrop'));
	      } else {
	        this.showNode(this.getItemNode().querySelector('.tasks-scrum-dragndrop'));
	      }
	    }
	  }, {
	    key: "activateGroupMode",
	    value: function activateGroupMode() {
	      var _this2 = this;

	      this.groupMode = true;

	      if (!this.isNodeCreated()) {
	        return;
	      }

	      var groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
	      var groupModeCheckbox = groupModeContainer.querySelector('input');
	      groupModeCheckbox.checked = false;
	      main_core.Event.bind(groupModeCheckbox, 'change', function (event) {
	        main_core.Dom.toggleClass(_this2.getItemNode(), 'tasks-scrum-item-group-mode');

	        if (_this2.getItemNode().classList.contains('tasks-scrum-item-group-mode')) {
	          _this2.emit('addItemToGroupMode');
	        } else {
	          _this2.emit('removeItemFromGroupMode');
	        }

	        _this2.showActionsPanel(event);
	      });
	      this.showNode(groupModeContainer);
	      this.deactivateDragNDrop();
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      if (!this.isNodeCreated()) {
	        return;
	      }

	      main_core.Dom.removeClass(this.getItemNode(), 'tasks-scrum-item-group-mode');
	      var groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
	      this.hideNode(groupModeContainer);
	      main_core.Event.unbindAll(groupModeContainer.querySelector('input'));
	      this.groupMode = false;
	      this.activateDragNDrop();
	    }
	  }, {
	    key: "isGroupMode",
	    value: function isGroupMode() {
	      return this.groupMode;
	    }
	  }, {
	    key: "activatePreviewMode",
	    value: function activatePreviewMode() {
	      this.previewMode = true;
	    }
	  }, {
	    key: "isPreviewMode",
	    value: function isPreviewMode() {
	      return this.previewMode;
	    }
	  }, {
	    key: "getPreviewVersion",
	    value: function getPreviewVersion() {
	      var previewItem = main_core.Runtime.clone(this);
	      previewItem.setItemId();
	      previewItem.itemNode = null;

	      if (previewItem.taskCounts) {
	        previewItem.taskCounts = new TaskCounts(previewItem);
	      }

	      previewItem.activatePreviewMode();
	      return previewItem;
	    }
	  }, {
	    key: "activateDecompositionMode",
	    value: function activateDecompositionMode(color) {
	      this.decompositionMode = true;

	      if (this.getBorderColor() === '') {
	        this.setBorderColor(color);
	      }

	      if (this.getBorderColor()) {
	        main_core.Dom.style(this.getItemNode(), 'border', '2px solid ' + this.getBorderColor());
	      }

	      this.deactivateDragNDrop();
	    }
	  }, {
	    key: "deactivateDecompositionMode",
	    value: function deactivateDecompositionMode() {
	      this.decompositionMode = false;

	      if (this.getBorderColor() === '') {
	        this.setBorderColor();
	        main_core.Dom.style(this.getItemNode(), 'border', null);
	      }

	      this.activateDragNDrop();
	    }
	  }, {
	    key: "activateDragNDrop",
	    value: function activateDragNDrop() {
	      if (this.isNodeCreated()) {
	        if (!this.getItemNode().classList.contains('tasks-scrum-item-drag')) {
	          main_core.Dom.addClass(this.getItemNode(), 'tasks-scrum-item-drag');
	        }
	      }
	    }
	  }, {
	    key: "deactivateDragNDrop",
	    value: function deactivateDragNDrop() {
	      if (this.isNodeCreated()) {
	        main_core.Dom.removeClass(this.getItemNode(), 'tasks-scrum-item-drag');
	      }
	    }
	  }, {
	    key: "isDecompositionMode",
	    value: function isDecompositionMode() {
	      return this.decompositionMode;
	    }
	  }, {
	    key: "setItemNode",
	    value: function setItemNode(node) {
	      try {
	        this.itemNode = node instanceof HTMLElement ? node : null;
	      } catch (e) {
	        this.itemNode = null;
	      }
	    }
	  }, {
	    key: "getItemNode",
	    value: function getItemNode() {
	      return this.itemNode;
	    }
	  }, {
	    key: "isNodeCreated",
	    value: function isNodeCreated() {
	      return this.itemNode !== null;
	    }
	  }, {
	    key: "setParentEntity",
	    value: function setParentEntity(entityId, entityType) {
	      this.setEntityId(entityId);
	      this.setEntityType(entityType);
	    }
	  }, {
	    key: "isEditAllowed",
	    value: function isEditAllowed() {
	      return Boolean(this.allowedActions['task_edit']);
	    }
	  }, {
	    key: "isRemoveAllowed",
	    value: function isRemoveAllowed() {
	      return Boolean(this.allowedActions['task_remove']);
	    }
	  }, {
	    key: "updateYourself",
	    value: function updateYourself(tmpItem) {
	      if (tmpItem.getName() !== this.getName()) {
	        this.setName(tmpItem.getName());
	      }

	      if (tmpItem.getEntityId() !== this.getEntityId()) {
	        this.setEntityId(tmpItem.getEntityId());
	      }

	      if (tmpItem.getResponsible().id !== this.getResponsible().id) {
	        this.setResponsible(tmpItem.getResponsible());
	      }

	      if (tmpItem.getStoryPoints().getPoints() !== this.getStoryPoints().getPoints()) {
	        this.setStoryPoints(tmpItem.getStoryPoints().getPoints());
	      }

	      if (this.getTaskCounts() && tmpItem.getTaskCounts()) {
	        this.getTaskCounts().updateIndicators({
	          attachedFilesCount: tmpItem.getTaskCounts().getAttachedFilesCount(),
	          checkListComplete: tmpItem.getTaskCounts().getCheckListComplete(),
	          checkListAll: tmpItem.getTaskCounts().getCheckListAll(),
	          newCommentsCount: tmpItem.getTaskCounts().getNewCommentsCount()
	        });
	      }

	      if (tmpItem.isCompleted() !== this.isCompleted()) {
	        this.setCompleted(tmpItem.isCompleted() ? 'Y' : 'N');
	      }

	      this.setParentId(tmpItem.getParentId());
	      this.setEpicAndTags(tmpItem.getEpic(), tmpItem.getTags());
	      this.setInfo(tmpItem.getInfo());
	      this.setParentTask(tmpItem.isParentTask() ? 'Y' : 'N');
	      this.setSubTasksCount(tmpItem.getSubTasksCount());
	      this.setLinkedTask(tmpItem.isLinkedTask() ? 'Y' : 'N');
	      this.setParentTaskId(tmpItem.getParentTaskId());
	      this.setSubTask(tmpItem.isSubTask() ? 'Y' : 'N');

	      if (this.isNodeCreated()) {
	        this.updateParentTaskNodes();
	      }
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.getItemNode());
	      this.setItemNode();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var itemClassName = 'tasks-scrum-item';

	      if (this.isSubTask()) {
	        itemClassName += ' tasks-scrum-subtask-item';
	      }

	      this.itemNode = main_core.Tag.render(_templateObject$4(), main_core.Text.encode(this.itemId), main_core.Text.encode(this.sort), itemClassName, this.renderName(), this.taskCounts && this.itemType === 'task' ? this.taskCounts.renderIndicators() : '', this.renderSubTasksCounter(), this.renderResponsible(), this.renderStoryPoints(), this.renderSubTasksTick(), this.renderTags());

	      if (this.isNodeCreated() && this.getBorderColor()) {
	        main_core.Dom.style(this.itemNode, 'border', '2px solid ' + this.getBorderColor());
	      }

	      return this.itemNode;
	    }
	  }, {
	    key: "renderName",
	    value: function renderName() {
	      return main_core.Tag.render(_templateObject2$2(), main_core.Text.encode(this.name));
	    }
	  }, {
	    key: "renderResponsible",
	    value: function renderResponsible() {
	      return main_core.Tag.render(_templateObject3$2());
	    }
	  }, {
	    key: "renderStoryPoints",
	    value: function renderStoryPoints() {
	      return main_core.Tag.render(_templateObject4$1(), main_core.Text.encode(this.getStoryPoints().getPoints()));
	    }
	  }, {
	    key: "renderSubTasksCounter",
	    value: function renderSubTasksCounter() {
	      var _this3 = this;

	      if (!this.isParentTask()) {
	        return '';
	      }

	      var subTasksCounter = main_core.Tag.render(_templateObject5$1(), this.getSubTasksCount());
	      main_core.Event.bind(subTasksCounter, 'click', function () {
	        _this3.emit('showTask');
	      });
	      return subTasksCounter;
	    }
	  }, {
	    key: "renderSubTasksTick",
	    value: function renderSubTasksTick() {
	      var _this4 = this;

	      if (!this.isParentTask()) {
	        return '';
	      }

	      if (this.getEntityType() === 'backlog') {
	        return '';
	      }

	      var subTasksTick = main_core.Tag.render(_templateObject6$1());
	      main_core.Event.bind(subTasksTick, 'click', function () {
	        if (!_this4.isDecompositionMode()) {
	          _this4.emit('toggleSubTasks');
	        }
	      });
	      return subTasksTick;
	    }
	  }, {
	    key: "toggleSubTasksTick",
	    value: function toggleSubTasksTick() {
	      if (!this.isNodeCreated()) {
	        return;
	      }

	      var subTasksTick = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');

	      if (subTasksTick) {
	        subTasksTick.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
	        subTasksTick.firstElementChild.classList.toggle('ui-btn-icon-angle-down');
	      }
	    }
	  }, {
	    key: "renderTags",
	    value: function renderTags() {
	      if (this.epic === null && this.tags.length === 0) {
	        return '';
	      }

	      return main_core.Tag.render(_templateObject7$1(), this.getEpicTag(), this.getListTagNodes());
	    }
	  }, {
	    key: "getEpicTag",
	    value: function getEpicTag() {
	      var _this5 = this;

	      if (this.epic === null) {
	        return '';
	      }

	      var getContrastYIQ = function getContrastYIQ(hexcolor) {
	        if (!hexcolor) {
	          hexcolor = ui_label.Label.Color.DEFAULT;
	        }

	        hexcolor = hexcolor.replace('#', '');
	        var r = parseInt(hexcolor.substr(0, 2), 16);
	        var g = parseInt(hexcolor.substr(2, 2), 16);
	        var b = parseInt(hexcolor.substr(4, 2), 16);
	        var yiq = (r * 299 + g * 587 + b * 114) / 1000;
	        return yiq >= 128 ? 'black' : 'white';
	      };

	      var epicLabel = new ui_label.Label({
	        text: this.epic.name,
	        color: ui_label.Label.Color.DEFAULT,
	        size: ui_label.Label.Size.MD,
	        customClass: 'tasks-scrum-item-epic-label'
	      });
	      var container = epicLabel.getContainer();
	      var innerLabel = container.querySelector('.ui-label-inner');
	      var contrast = getContrastYIQ(this.epic.info.color);

	      if (contrast === 'white') {
	        main_core.Dom.style(innerLabel, 'color', '#ffffff');
	      } else {
	        main_core.Dom.style(innerLabel, 'color', '#525c69');
	      }

	      main_core.Dom.style(container, 'backgroundColor', this.epic.info.color);
	      main_core.Event.bind(container, 'click', function (event) {
	        if (_this5.isGroupMode()) {
	          _this5.clickToGroupModeCheckbox();

	          _this5.showActionsPanel(event);

	          return;
	        }

	        _this5.emit('filterByEpic', _this5.epic.id);
	      });
	      return container;
	    }
	  }, {
	    key: "getListTagNodes",
	    value: function getListTagNodes() {
	      var _this6 = this;

	      return this.tags.map(function (tag) {
	        var tagLabel = new ui_label.Label({
	          text: tag,
	          color: ui_label.Label.Color.TAG_LIGHT,
	          fill: true,
	          size: ui_label.Label.Size.SM,
	          customClass: ''
	        });
	        var container = tagLabel.getContainer();
	        main_core.Event.bind(container, 'click', function (event) {
	          if (_this6.isGroupMode()) {
	            _this6.clickToGroupModeCheckbox();

	            _this6.showActionsPanel(event);

	            return;
	          }

	          _this6.emit('filterByTag', tag);
	        });
	        return container;
	      });
	    } // todo remove all method

	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend(container) {
	      var _this7 = this;

	      this.setItemNode(container.querySelector('[data-item-id="' + this.itemId + '"]'));

	      if (!this.isNodeCreated()) {
	        return;
	      }

	      if (this.taskCounts) {
	        this.taskCounts.onAfterAppend();
	      }

	      this.updateResponsible();

	      if (this.isPreviewMode()) {
	        main_core.Event.bind(this.getItemNode(), 'click', function () {
	          return _this7.emit('showTask');
	        });
	        return;
	      }

	      if (!this.isDecompositionMode()) {
	        this.activateDragNDrop();
	      }

	      if (this.isSubTask()) {
	        this.deactivateDragNDrop();
	      }

	      main_core.Event.unbindAll(this.getItemNode());
	      main_core.Event.bind(this.getItemNode(), 'click', this.onItemClick.bind(this));
	      var nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name');
	      main_core.Event.unbindAll(nameNode);
	      main_core.Event.bind(nameNode, 'click', this.onNameClick.bind(this));
	      var responsibleNode = this.getItemNode().querySelector('.tasks-scrum-item-responsible');
	      main_core.Event.unbindAll(responsibleNode);
	      main_core.Event.bind(responsibleNode, 'click', this.onResponsibleClick.bind(this));
	      var storyPointsNode = this.getItemNode().querySelector('.tasks-scrum-item-story-points');
	      main_core.Event.unbindAll(storyPointsNode);
	      main_core.Event.bind(storyPointsNode, 'click', this.onStoryPointsClick.bind(this));

	      if (this.isCompleted()) {
	        var nameTextNode = nameNode.querySelector('.ui-ctl-element');
	        main_core.Dom.style(nameTextNode, 'textDecoration', 'line-through');
	      }

	      this.updateParentTaskNodes();
	    }
	  }, {
	    key: "isShowIndicators",
	    value: function isShowIndicators() {
	      return Boolean(this.attachedFilesCount || this.checkListAll || this.newCommentsCount);
	    }
	  }, {
	    key: "updateParentTaskNodes",
	    value: function updateParentTaskNodes() {
	      if (this.isParentTask()) {
	        var subTasksCounterNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-btn');

	        if (subTasksCounterNode) {
	          main_core.Dom.replace(subTasksCounterNode, this.renderSubTasksCounter());
	        } else {
	          var paramsNode = this.getItemNode().querySelector('.tasks-scrum-item-params');

	          var _subTasksCounterNode = this.renderSubTasksCounter();

	          main_core.Dom.insertBefore(_subTasksCounterNode, paramsNode.firstElementChild);
	        }

	        var subTasksTickNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');

	        if (subTasksTickNode) {
	          var newSubTasksTickNode = this.renderSubTasksTick();

	          if (newSubTasksTickNode) {
	            main_core.Dom.replace(subTasksTickNode, newSubTasksTickNode);
	          } else {
	            main_core.Dom.remove(subTasksTickNode);
	          }
	        } else {
	          var _paramsNode = this.getItemNode().querySelector('.tasks-scrum-item-params');

	          var _newSubTasksTickNode = this.renderSubTasksTick();

	          main_core.Dom.append(_newSubTasksTickNode, _paramsNode);
	        }
	      } else {
	        var _subTasksCounterNode2 = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-btn');

	        if (_subTasksCounterNode2) {
	          main_core.Dom.remove(_subTasksCounterNode2);
	        }

	        var _subTasksTickNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');

	        if (_subTasksTickNode) {
	          main_core.Dom.remove(_subTasksTickNode);
	        }
	      }
	    }
	  }, {
	    key: "onItemClick",
	    value: function onItemClick(event) {
	      if (this.isClickOnEditableName(event)) {
	        return;
	      }

	      var ignoreTagList = new Set(['I', 'SPAN', 'INPUT']);

	      if (ignoreTagList.has(event.target.tagName)) {
	        return;
	      }

	      if (event.target.closest('.tasks-scrum-item-subtasks-tick')) {
	        return;
	      }

	      this.clickToGroupModeCheckbox();
	      this.showActionsPanel(event);
	    }
	  }, {
	    key: "isClickOnEditableName",
	    value: function isClickOnEditableName(event) {
	      return this.isEditAllowed() && event.target.classList.contains('ui-ctl-element');
	    }
	  }, {
	    key: "onNameClick",
	    value: function onNameClick(event) {
	      var _this8 = this;

	      if (this.isGroupMode()) {
	        this.clickToGroupModeCheckbox();
	        this.showActionsPanel(event);
	        return;
	      }

	      if (!this.isEditAllowed()) {
	        return;
	      }

	      var targetNode = event.target;

	      if (main_core.Dom.hasClass(targetNode, 'ui-ctl-element') && targetNode.contentEditable === 'true') {
	        return;
	      }

	      if (!main_core.Dom.hasClass(targetNode, 'ui-ctl-element') || this.isDisabled()) {
	        return;
	      }

	      var nameNode = event.currentTarget;
	      var borderNode = nameNode.querySelector('.ui-ctl');
	      var valueNode = nameNode.querySelector('.ui-ctl-element');
	      valueNode.textContent = valueNode.textContent.trim();
	      var oldValue = valueNode.textContent;
	      main_core.Dom.addClass(this.getItemNode(), 'tasks-scrum-item-edit-mode');
	      main_core.Dom.toggleClass(borderNode, 'ui-ctl-no-border');
	      valueNode.contentEditable = 'true';
	      this.deactivateDragNDrop();
	      this.placeCursorAtEnd(valueNode);
	      main_core.Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));
	      main_core.Event.bindOnce(valueNode, 'blur', function () {
	        main_core.Event.unbind(valueNode, 'keydown', _this8.blockEnterInput.bind(valueNode));
	        main_core.Dom.removeClass(_this8.getItemNode(), 'tasks-scrum-item-edit-mode');
	        main_core.Dom.addClass(borderNode, 'ui-ctl-no-border');
	        valueNode.contentEditable = 'false';

	        _this8.activateDragNDrop();

	        var newValue = valueNode.textContent.trim();

	        if (oldValue === newValue) {
	          return;
	        }

	        _this8.emit('updateItem', {
	          itemId: _this8.getItemId(),
	          entityId: _this8.getEntityId(),
	          itemType: _this8.getItemType(),
	          name: newValue
	        });

	        _this8.name = newValue;
	      }, true);
	    }
	  }, {
	    key: "clickToGroupModeCheckbox",
	    value: function clickToGroupModeCheckbox() {
	      if (this.isGroupMode()) {
	        var groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
	        var groupModeCheckbox = groupModeContainer.querySelector('input');
	        groupModeCheckbox.click();
	      }
	    }
	  }, {
	    key: "onResponsibleClick",
	    value: function onResponsibleClick(event) {
	      var _this9 = this;

	      if (this.isGroupMode()) {
	        this.clickToGroupModeCheckbox();
	        this.showActionsPanel(event);
	        return;
	      }

	      if (this.isDisabled()) {
	        return;
	      }

	      var responsibleNode = event.currentTarget;
	      var dialog = new ui_entitySelector.Dialog({
	        targetNode: responsibleNode,
	        enableSearch: true,
	        context: 'TASKS',
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            dialog.hide();
	            var selectedItem = event.getData().item;
	            _this9.responsible = {
	              id: selectedItem.getId(),
	              name: selectedItem.getTitle(),
	              photo: {
	                src: selectedItem.getAvatar()
	              }
	            };

	            _this9.updateResponsible();

	            _this9.emit('changeTaskResponsible');
	          }
	        },
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false
	          }
	        }, {
	          id: 'department'
	        }]
	      });
	      dialog.show();
	    }
	  }, {
	    key: "onStoryPointsClick",
	    value: function onStoryPointsClick(event) {
	      var _this10 = this;

	      if (this.isGroupMode()) {
	        this.clickToGroupModeCheckbox();
	        this.showActionsPanel(event);
	        return;
	      }

	      if (this.isDisabled()) {
	        return;
	      }

	      var storyPointsNode = event.currentTarget;
	      var borderNode = storyPointsNode.querySelector('.ui-ctl');
	      var valueNode = storyPointsNode.querySelector('.ui-ctl-element');
	      valueNode.textContent = valueNode.textContent.trim();
	      var oldValue = valueNode.textContent.trim();

	      if (valueNode.contentEditable === 'true') {
	        return;
	      }

	      main_core.Dom.toggleClass(borderNode, 'ui-ctl-no-border');
	      valueNode.contentEditable = 'true';
	      this.placeCursorAtEnd(valueNode);
	      main_core.Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));
	      main_core.Event.bindOnce(valueNode, 'blur', function () {
	        main_core.Event.unbind(valueNode, 'keydown', _this10.blockEnterInput.bind(valueNode));
	        main_core.Dom.toggleClass(borderNode, 'ui-ctl-no-border');
	        valueNode.contentEditable = 'false';
	        var newValue = valueNode.textContent.trim();

	        if (newValue && oldValue === newValue) {
	          valueNode.textContent = oldValue;
	          return;
	        }

	        _this10.setStoryPoints(newValue);

	        _this10.emit('updateItem', {
	          itemId: _this10.getItemId(),
	          entityId: _this10.getEntityId(),
	          itemType: _this10.getItemType(),
	          storyPoints: newValue
	        });

	        _this10.sendEventToUpdateStoryPoints();
	      }, true);
	    }
	  }, {
	    key: "sendEventToUpdateStoryPoints",
	    value: function sendEventToUpdateStoryPoints() {
	      this.emit('updateStoryPoints');
	    }
	  }, {
	    key: "blockEnterInput",
	    value: function blockEnterInput(event) {
	      if (event.isComposing || event.keyCode === 13) {
	        this.blur();
	        return;
	      }
	    }
	  }, {
	    key: "showActionsPanel",
	    value: function showActionsPanel(event) {
	      var _this11 = this;

	      if (this.actionsPanel && this.actionsPanel.isShown() && !this.isGroupMode()) {
	        return;
	      }

	      if (event) {
	        event.stopPropagation();
	      }

	      this.actionsPanel = new ActionsPanel({
	        bindElement: this.getItemNode(),
	        itemList: {
	          task: {
	            activity: this.itemType === 'task' && !this.isGroupMode(),
	            callback: function callback() {
	              _this11.emit('showTask');

	              _this11.actionsPanel.destroy();
	            }
	          },
	          attachment: {
	            activity: !this.isDisabled() && this.isEditAllowed() && !this.isGroupMode(),
	            callback: function callback(event) {
	              var diskManager = new DiskManager({
	                ufDiskFilesFieldName: 'UF_TASK_WEBDAV_FILES'
	              });
	              diskManager.subscribeOnce('onFinish', function (baseEvent) {
	                _this11.emit('attachFilesToTask', baseEvent.getData());

	                _this11.actionsPanel.destroy();
	              });
	              diskManager.showAttachmentMenu(event.currentTarget);
	            }
	          },
	          move: {
	            activity: !this.decompositionMode && this.isMovable() && !this.isSubTask(),
	            callback: function callback(event) {
	              _this11.emit('move', event.currentTarget);
	            }
	          },
	          sprint: {
	            activity: !this.isDisabled() && !this.decompositionMode && !this.isSubTask(),
	            callback: function callback(event) {
	              _this11.emit('moveToSprint', event.currentTarget);
	            }
	          },
	          backlog: {
	            activity: this.entityType === 'sprint' && !this.decompositionMode && !this.isSubTask(),
	            callback: function callback() {
	              _this11.emit('moveToBacklog');

	              _this11.actionsPanel.destroy();
	            }
	          },
	          tags: {
	            activity: true,
	            callback: function callback(event) {
	              _this11.emit('showTagSearcher', event.currentTarget);
	            }
	          },
	          epic: {
	            activity: true,
	            callback: function callback(event) {
	              _this11.emit('showEpicSearcher', event.currentTarget);
	            }
	          },
	          decomposition: {
	            activity: !this.isDisabled() && !this.decompositionMode && !this.isGroupMode() && !this.isSubTask(),
	            callback: function callback(event) {
	              _this11.emit('startDecomposition');

	              _this11.actionsPanel.destroy();
	            }
	          },
	          remove: {
	            activity: this.isRemoveAllowed(),
	            callback: function callback() {
	              var message = _this11.isGroupMode() ? main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASKS') : main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASK');
	              ui_dialogs_messagebox.MessageBox.confirm(message, function (messageBox) {
	                _this11.emit('remove');

	                messageBox.close();

	                _this11.actionsPanel.destroy();
	              }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));
	            }
	          }
	        }
	      });
	      this.actionsPanel.showPanel();
	    }
	  }, {
	    key: "getCurrentActionsPanel",
	    value: function getCurrentActionsPanel() {
	      if (this.actionsPanel && this.actionsPanel.isShown()) {
	        return this.actionsPanel;
	      } else {
	        return null;
	      }
	    }
	  }, {
	    key: "setEpicAndTags",
	    value: function setEpicAndTags(epic, tags) {
	      this.epic = main_core.Type.isPlainObject(epic) || epic === null ? epic : this.epic;
	      this.tags = main_core.Type.isArray(tags) ? tags : this.tags;
	      this.updateTagsContainer();
	    }
	  }, {
	    key: "updateTagsContainer",
	    value: function updateTagsContainer() {
	      if (!this.getItemNode()) {
	        return;
	      }

	      var newContainer = main_core.Tag.render(_templateObject8$1(), this.getEpicTag(), this.getListTagNodes());
	      var tagsContainerNode = this.getItemNode().querySelector('.tasks-scrum-item-tags-container');

	      if (tagsContainerNode) {
	        main_core.Dom.replace(tagsContainerNode, newContainer);
	      } else {
	        main_core.Dom.append(newContainer, this.getItemNode());
	      }
	    }
	  }, {
	    key: "updateResponsible",
	    value: function updateResponsible() {
	      var responsibleNode = this.getItemNode().querySelector('.tasks-scrum-item-responsible');

	      if (!responsibleNode) {
	        return;
	      }

	      main_core.Dom.attr(responsibleNode, 'title', this.responsible.name);

	      if (this.responsible.photo && this.responsible.photo.src) {
	        main_core.Dom.style(responsibleNode.firstElementChild, 'backgroundImage', 'url("' + this.responsible.photo.src + '")');
	      } else {
	        main_core.Dom.style(responsibleNode.firstElementChild, 'backgroundImage', null);
	      }
	    }
	  }, {
	    key: "placeCursorAtEnd",
	    value: function placeCursorAtEnd(node) {
	      node.focus();
	      var selection = window.getSelection();
	      var range = document.createRange();
	      range.selectNodeContents(node);
	      range.collapse(false);
	      selection.removeAllRanges();
	      selection.addRange(range);
	    }
	  }, {
	    key: "updateIndicators",
	    value: function updateIndicators(data) {
	      if (this.taskCounts) {
	        this.taskCounts.updateIndicators(data);
	      }
	    }
	  }, {
	    key: "showNode",
	    value: function showNode(node) {
	      main_core.Dom.style(node, 'display', 'block');
	    }
	  }, {
	    key: "hideNode",
	    value: function hideNode(node) {
	      main_core.Dom.style(node, 'display', 'none');
	    }
	  }], [{
	    key: "buildItem",
	    value: function buildItem(params) {
	      return new Item(params);
	    }
	  }]);
	  return Item;
	}(main_core_events.EventEmitter);

	function _templateObject$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-link ui-link-dashed ui-link-secondary tasks-scrum-group-actions\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"]);

	  _templateObject$5 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var GroupActionsButton = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(GroupActionsButton, _EventEmitter);

	  function GroupActionsButton() {
	    var _this;

	    babelHelpers.classCallCheck(this, GroupActionsButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(GroupActionsButton).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.GroupActionsButton');

	    _this.element = null;
	    return _this;
	  }

	  babelHelpers.createClass(GroupActionsButton, [{
	    key: "render",
	    value: function render() {
	      this.element = main_core.Tag.render(_templateObject$5(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_GROUP'));
	      main_core.Event.bind(this.element, 'click', this.onClick.bind(this, this.element));
	      return this.element;
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      if (this.element && this.element.classList.contains('tasks-scrum-group-actions-active')) {
	        main_core.Dom.toggleClass(this.element, 'tasks-scrum-group-actions');
	        main_core.Dom.toggleClass(this.element, 'tasks-scrum-group-actions-active');
	        main_core.Dom.toggleClass(this.element, 'ui-link-secondary');
	        main_core.Dom.toggleClass(this.element, 'ui-link-dashed');
	        this.emit('deactivateGroupMode');
	      }
	    }
	  }, {
	    key: "onClick",
	    value: function onClick(element) {
	      main_core.Dom.toggleClass(element, 'tasks-scrum-group-actions');
	      main_core.Dom.toggleClass(element, 'tasks-scrum-group-actions-active');
	      main_core.Dom.toggleClass(element, 'ui-link-secondary');
	      main_core.Dom.toggleClass(element, 'ui-link-dashed');

	      if (element.classList.contains('tasks-scrum-group-actions-active')) {
	        this.emit('activateGroupMode');
	      } else {
	        this.emit('deactivateGroupMode');
	      }
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.element);
	      this.element = null;
	    }
	  }]);
	  return GroupActionsButton;
	}(main_core_events.EventEmitter);

	function _templateObject$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-items-list\" data-entity-id=\"", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$6 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ListItems = /*#__PURE__*/function () {
	  function ListItems(entity) {
	    babelHelpers.classCallCheck(this, ListItems);
	    this.entity = entity;
	    this.node = null;
	  }

	  babelHelpers.createClass(ListItems, [{
	    key: "render",
	    value: function render() {
	      var _this = this;

	      this.node = main_core.Tag.render(_templateObject$6(), this.entity.getId(), this.entity.isCompleted() ? '' : this.entity.getInput().render(), babelHelpers.toConsumableArray(this.entity.getItems().values()).map(function (item) {
	        item.setEntityType(_this.entity.getEntityType());
	        return item.render();
	      }));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "setEntityId",
	    value: function setEntityId(entityId) {
	      this.node.dataset.entityId = parseInt(entityId, 10);
	    }
	  }]);
	  return ListItems;
	}();

	function _templateObject2$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-selector-footer-conjunction\"></span>"]);

	  _templateObject2$3 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span onclick=\"", "\" class=\"ui-selector-footer-link ui-selector-footer-link-add\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t"]);

	  _templateObject$7 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TagSearcher = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(TagSearcher, _EventEmitter);

	  function TagSearcher() {
	    var _this;

	    babelHelpers.classCallCheck(this, TagSearcher);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TagSearcher).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.TagSearcher');

	    _this.allTags = new Map();
	    return _this;
	  }

	  babelHelpers.createClass(TagSearcher, [{
	    key: "addTagToSearcher",
	    value: function addTagToSearcher(tagName) {
	      tagName = tagName.trim();
	      this.allTags.set('tag_' + tagName, {
	        id: tagName,
	        entityId: 'tag',
	        tabs: 'recents',
	        title: tagName,
	        avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag.svg'
	      });
	    }
	  }, {
	    key: "addEpicToSearcher",
	    value: function addEpicToSearcher(epic) {
	      var epicName = epic.name.trim();
	      this.allTags.set('epic_' + epicName, {
	        id: epic.id,
	        entityId: 'epic',
	        tabs: 'recents',
	        title: epicName.trim(),
	        avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag-green.svg',
	        name: epicName.trim(),
	        description: epic.description,
	        info: epic.info
	      });
	    }
	  }, {
	    key: "getTagFromSearcher",
	    value: function getTagFromSearcher(name) {
	      return this.allTags.get(name);
	    }
	  }, {
	    key: "removeEpicFromSearcher",
	    value: function removeEpicFromSearcher(epic) {
	      this.allTags.delete('epic_' + epic.name);
	    }
	  }, {
	    key: "getAllList",
	    value: function getAllList() {
	      return babelHelpers.toConsumableArray(this.allTags.values());
	    }
	  }, {
	    key: "getTagsList",
	    value: function getTagsList() {
	      var tagsList = [];
	      babelHelpers.toConsumableArray(this.allTags.values()).forEach(function (tag) {
	        if (tag.entityId === 'tag') {
	          tagsList.push(tag);
	        }
	      });
	      return tagsList;
	    }
	  }, {
	    key: "getEpicList",
	    value: function getEpicList() {
	      var epicList = [];
	      babelHelpers.toConsumableArray(this.allTags.values()).forEach(function (epic) {
	        if (epic.entityId === 'epic') {
	          epicList.push(epic);
	        }
	      });
	      return epicList;
	    }
	  }, {
	    key: "getEpicByName",
	    value: function getEpicByName(epicName) {
	      var epic = null;
	      babelHelpers.toConsumableArray(this.allTags.values()).forEach(function (tag) {
	        if (tag.entityId === 'epic' && tag.name === epicName) {
	          epic = tag;
	        }
	      });
	      return epic;
	    }
	  }, {
	    key: "showTagsDialog",
	    value: function showTagsDialog(item, targetNode) {
	      var _this2 = this;

	      var actionsPanel = item.getCurrentActionsPanel();
	      var currentTags = item.getTags();
	      var selectedItems = [];
	      currentTags.forEach(function (tag) {
	        var currentTag = _this2.allTags.get(('tag_' + tag).trim());

	        if (currentTag) {
	          selectedItems.push(currentTag);
	        }
	      });

	      var createTag = function createTag() {
	        var tagName = _this2.tagDialog.getTagSelector().getTextBoxValue();

	        if (!tagName) {
	          _this2.tagDialog.focusSearch();

	          return;
	        }

	        _this2.addTagToSearcher(tagName);

	        var newTag = _this2.getTagFromSearcher('tag_' + tagName);

	        var item = _this2.tagDialog.addItem(newTag);

	        item.select();

	        _this2.tagDialog.getTagSelector().clearTextBox();

	        _this2.tagDialog.focusSearch();

	        _this2.tagDialog.selectFirstTab();

	        var label = _this2.tagDialog.getContainer().querySelector('.ui-selector-footer-conjunction');

	        label.textContent = '';
	      };

	      var choiceWasMade = false;
	      this.tagDialog = new ui_entitySelector.Dialog({
	        id: item.getItemId(),
	        targetNode: targetNode,
	        width: 400,
	        height: 300,
	        multiple: true,
	        dropdownMode: true,
	        enableSearch: true,
	        selectedItems: selectedItems,
	        items: this.getTagsList(),
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            choiceWasMade = true;
	            var selectedItem = event.getData().item;
	            var tag = selectedItem.getTitle();

	            _this2.emit('attachTagToTask', tag);
	          },
	          'Item:onDeselect': function ItemOnDeselect(event) {
	            choiceWasMade = true;
	            var deselectedItem = event.getData().item;
	            var tag = deselectedItem.getTitle();

	            _this2.emit('deAttachTagToTask', tag);
	          }
	        },
	        tagSelectorOptions: {
	          events: {
	            onInput: function onInput(event) {
	              var selector = event.getData().selector;

	              if (selector) {
	                var dialog = selector.getDialog();
	                var label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
	                label.textContent = main_core.Text.encode(selector.getTextBoxValue());
	              }
	            }
	          }
	        },
	        footer: [main_core.Tag.render(_templateObject$7(), createTag, main_core.Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')), main_core.Tag.render(_templateObject2$3())]
	      });
	      actionsPanel.setBlockBlurNode(this.tagDialog.getContainer());
	      this.tagDialog.subscribe('onHide', function () {
	        if (choiceWasMade) {
	          actionsPanel.destroy();

	          _this2.emit('hideTagDialog');
	        }
	      });
	      this.tagDialog.show();
	    }
	  }, {
	    key: "showEpicDialog",
	    value: function showEpicDialog(item, targetNode) {
	      var _this3 = this;

	      var actionsPanel = item.getCurrentActionsPanel();
	      var currentEpic = item.getEpic();
	      var selectedItems = [];

	      if (currentEpic) {
	        var currentEpicInfo = this.allTags.get(('epic_' + currentEpic.name).trim());

	        if (currentEpicInfo) {
	          selectedItems.push(currentEpicInfo);
	        }
	      }

	      var choiceWasMade = false;
	      var dialog = new ui_entitySelector.Dialog({
	        id: item.getItemId(),
	        targetNode: targetNode,
	        width: 400,
	        height: 300,
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        selectedItems: selectedItems,
	        items: this.getEpicList(),
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            choiceWasMade = true;
	            var selectedItem = event.getData().item;
	            var epicId = selectedItem.getId();

	            _this3.emit('updateItemEpic', epicId);
	          },
	          'Item:onDeselect': function ItemOnDeselect(event) {
	            setTimeout(function () {
	              choiceWasMade = true;

	              if (dialog.getSelectedItems().length === 0) {
	                _this3.emit('updateItemEpic', 0);

	                dialog.hide();
	              }
	            }, 50);
	          }
	        },
	        tagSelectorOptions: {
	          placeholder: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
	        }
	      });
	      actionsPanel.setBlockBlurNode(dialog.getContainer());
	      dialog.subscribe('onHide', function () {
	        if (choiceWasMade) {
	          actionsPanel.destroy();
	        }
	      });
	      dialog.show();
	    }
	  }, {
	    key: "showTagsSearchDialog",
	    value: function showTagsSearchDialog(inputObject, enteredHashTagName) {
	      var input = inputObject.getInputNode();

	      if (this.tagsDialog && this.tagsDialog.getId() !== inputObject.getNodeId()) {
	        this.tagsDialog = null;
	      }

	      if (!this.tagsDialog) {
	        this.tagsDialog = new ui_entitySelector.Dialog({
	          id: inputObject.getNodeId(),
	          targetNode: inputObject.getNode(),
	          width: inputObject.getNode().offsetWidth,
	          height: 210,
	          multiple: false,
	          dropdownMode: true,
	          items: this.getTagsList(),
	          events: {
	            'Item:onSelect': function ItemOnSelect(event) {
	              var selectedItem = event.getData().item;
	              var selectedHashTag = '#' + selectedItem.getTitle();
	              var hashTags = TagSearcher.getHashTagsFromText(input.value);
	              var enteredHashTag = hashTags.length > 0 ? hashTags.pop().trim() : '';
	              input.value = input.value.replace(new RegExp('#([' + enteredHashTag + ']+|)(?:$)', 'g'), selectedHashTag);
	              input.focus();
	              selectedItem.deselect();
	            }
	          }
	        });
	        this.tagsDialog.subscribe('onHide', function () {
	          inputObject.setTagsSearchMode(false);
	        });
	      }

	      inputObject.setTagsSearchMode(true);
	      this.tagsDialog.show();
	      this.tagsDialog.search(enteredHashTagName);
	    }
	  }, {
	    key: "closeTagsSearchDialog",
	    value: function closeTagsSearchDialog() {
	      if (this.tagsDialog) {
	        this.tagsDialog.hide();
	      }
	    }
	  }, {
	    key: "showEpicSearchDialog",
	    value: function showEpicSearchDialog(inputObject, enteredHashEpicName) {
	      var input = inputObject.getInputNode();

	      if (this.epicDialog && this.epicDialog.getId() !== inputObject.getNodeId()) {
	        this.epicDialog = null;
	      }

	      if (!this.epicDialog) {
	        this.epicDialog = new ui_entitySelector.Dialog({
	          id: inputObject.getNodeId(),
	          targetNode: inputObject.getNode(),
	          width: inputObject.getNode().offsetWidth,
	          height: 210,
	          multiple: false,
	          dropdownMode: true,
	          items: this.getEpicList(),
	          events: {
	            'Item:onSelect': function ItemOnSelect(event) {
	              var selectedItem = event.getData().item;
	              var selectedHashEpic = '@' + selectedItem.getTitle();
	              input.value = input.value.replace(new RegExp('(?:^|\\s)(?:@)([^\\s]*)', 'g'), '');
	              input.value = input.value + ' ' + selectedHashEpic;
	              input.focus();
	              selectedItem.deselect();
	              inputObject.setEpicId(selectedItem.getId());
	            }
	          }
	        });
	        this.epicDialog.subscribe('onHide', function () {
	          inputObject.setEpicSearchMode(false);
	        });
	      }

	      inputObject.setEpicSearchMode(true);
	      this.epicDialog.show();
	      this.epicDialog.search(enteredHashEpicName);
	    }
	  }, {
	    key: "closeEpicSearchDialog",
	    value: function closeEpicSearchDialog() {
	      if (this.epicDialog) {
	        this.epicDialog.hide();
	      }
	    }
	  }, {
	    key: "cleanEpicTagsInText",
	    value: function cleanEpicTagsInText(inputText) {
	      var regex = new RegExp('(?:^|\\s)(?:@)(\\S+)', 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[0]);
	      }

	      return matches;
	    }
	  }], [{
	    key: "getHashTagsFromText",
	    value: function getHashTagsFromText(inputText) {
	      var regex = new RegExp('(?:^|\\s)(?:#)(\\S+)', 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[0]);
	      }

	      return matches;
	    }
	  }, {
	    key: "getHashEpicFromText",
	    value: function getHashEpicFromText(inputText) {
	      var regex = new RegExp('(?:^|\\s)(?:@)(\\S+)', 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[0]);
	      }

	      return matches;
	    }
	  }, {
	    key: "getHashTagNamesFromText",
	    value: function getHashTagNamesFromText(inputText) {
	      var regex = new RegExp('(?:^|\\s)(?:#)(\\S+|)', 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[1]);
	      }

	      return matches;
	    }
	  }, {
	    key: "getHashEpicNamesFromText",
	    value: function getHashEpicNamesFromText(inputText) {
	      var regex = new RegExp('(?:^|\\s)(?:@)(\\S+|)', 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[1]);
	      }

	      return matches;
	    }
	  }]);
	  return TagSearcher;
	}(main_core_events.EventEmitter);

	function _templateObject$8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-input\">\n\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" placeholder=\n\t\t\t\t\t\t\"", "\" autocomplete=\"off\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$8 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Input = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Input, _EventEmitter);

	  function Input(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Input);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Input).call(this, options));

	    _this.setEventNamespace('BX.Tasks.Scrum.Input');

	    _this.nodeId = main_core.Text.getRandom();
	    _this.placeholder = main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER');
	    _this.epicId = 0;
	    return _this;
	  }

	  babelHelpers.createClass(Input, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$8(), main_core.Text.encode(this.nodeId), main_core.Text.encode(this.placeholder));
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.node.querySelector('input').disabled = true;
	    }
	  }, {
	    key: "unDisable",
	    value: function unDisable() {
	      this.node.querySelector('input').disabled = false;
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this2 = this;

	      this.setNode();
	      main_core.Event.bind(this.getInputNode(), 'input', function (event) {
	        _this2.onTagSearch(event);

	        _this2.onEpicSearch(event);
	      });
	      main_core.Event.bind(this.getInputNode(), 'keydown', this.onKeydown.bind(this));
	    }
	  }, {
	    key: "setNode",
	    value: function setNode() {
	      this.node = document.getElementById(this.nodeId);
	    }
	  }, {
	    key: "setPlaceholder",
	    value: function setPlaceholder(placeholder) {
	      this.placeholder = placeholder;
	    }
	  }, {
	    key: "setEpicId",
	    value: function setEpicId(parentId) {
	      this.epicId = parseInt(parentId, 10);
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getNodeId",
	    value: function getNodeId() {
	      return this.nodeId;
	    }
	  }, {
	    key: "getInputNode",
	    value: function getInputNode() {
	      return this.node.querySelector('input');
	    }
	  }, {
	    key: "getEpicId",
	    value: function getEpicId() {
	      return this.epicId;
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	    }
	  }, {
	    key: "setTagsSearchMode",
	    value: function setTagsSearchMode(value) {
	      main_core.Dom.attr(this.getInputNode(), 'data-tag-disabled', value);
	    }
	  }, {
	    key: "isTagsSearchMode",
	    value: function isTagsSearchMode() {
	      return main_core.Dom.attr(this.getInputNode(), 'data-tag-disabled');
	    }
	  }, {
	    key: "setEpicSearchMode",
	    value: function setEpicSearchMode(value) {
	      main_core.Dom.attr(this.getInputNode(), 'data-epic-disabled', value);
	    }
	  }, {
	    key: "isEpicSearchMode",
	    value: function isEpicSearchMode() {
	      return main_core.Dom.attr(this.getInputNode(), 'data-epic-disabled');
	    }
	  }, {
	    key: "onTagSearch",
	    value: function onTagSearch(event) {
	      var inputNode = event.target;
	      var enteredHashTags = TagSearcher.getHashTagNamesFromText(inputNode.value);

	      if (event.data === '#') {
	        this.setEpicSearchMode(false);
	        this.setTagsSearchMode(true);
	      }

	      if (enteredHashTags.length > 0 && this.isTagsSearchMode()) {
	        var enteredHashTagName = enteredHashTags.pop().trim();
	        this.emit('tagsSearchOpen', enteredHashTagName);
	      } else {
	        this.emit('tagsSearchClose');
	      }
	    }
	  }, {
	    key: "onEpicSearch",
	    value: function onEpicSearch(event) {
	      var inputNode = event.target;
	      var enteredHashEpics = TagSearcher.getHashEpicNamesFromText(inputNode.value);

	      if (event.data === '@') {
	        this.setTagsSearchMode(false);
	        this.setEpicSearchMode(true);
	      }

	      if (enteredHashEpics.length > 0 && this.isEpicSearchMode()) {
	        var enteredHashTagName = enteredHashEpics.pop().trim();
	        this.emit('epicSearchOpen', enteredHashTagName);
	      } else {
	        this.emit('epicSearchClose');
	      }
	    }
	  }, {
	    key: "onCreateTaskItem",
	    value: function onCreateTaskItem() {
	      if (!this.isTagsSearchMode() && !this.isEpicSearchMode()) {
	        var input = this.getInputNode();

	        if (input.value) {
	          this.emit('createTaskItem', input.value);
	          input.value = '';
	          input.focus();
	        }
	      }
	    }
	  }, {
	    key: "onKeydown",
	    value: function onKeydown(event) {
	      if (event.isComposing || event.keyCode === 13) {
	        this.onCreateTaskItem();
	      }
	    }
	  }]);
	  return Input;
	}(main_core_events.EventEmitter);

	var Entity = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Entity, _EventEmitter);

	  function Entity(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Entity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Entity).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Entity');

	    _this.setEntityParams(params);

	    _this.storyPoints = new StoryPoints();
	    _this.items = new Map();
	    _this.groupMode = false;
	    _this.groupModeItems = new Map();
	    _this.node = null;
	    _this.groupActionsButton = null;
	    _this.listItems = null;
	    _this.input = new Input();
	    return _this;
	  }

	  babelHelpers.createClass(Entity, [{
	    key: "setEntityParams",
	    value: function setEntityParams(params) {
	      this.setId(params.id);
	      this.setViews(params.views);
	      this.setNumberTasks(params.numberTasks);
	      this.exactSearchApplied = params.isExactSearchApplied === 'Y';
	    }
	  }, {
	    key: "addGroupActionsButton",
	    value: function addGroupActionsButton(groupActionsButton) {
	      this.groupActionsButton = groupActionsButton;
	      this.groupActionsButton.subscribe('activateGroupMode', this.onActivateGroupMode.bind(this));
	      this.groupActionsButton.subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));
	    }
	  }, {
	    key: "addListItems",
	    value: function addListItems(listItems) {
	      this.listItems = listItems;
	    }
	  }, {
	    key: "getListItems",
	    value: function getListItems() {
	      return this.listItems;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = main_core.Type.isInteger(id) ? parseInt(id, 10) : 0;

	      if (this.listItems) {
	        this.listItems.setEntityId(this.id);
	      }
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setViews",
	    value: function setViews(views) {
	      this.views = main_core.Type.isPlainObject(views) ? views : {};
	    }
	  }, {
	    key: "getViews",
	    value: function getViews() {
	      return this.views;
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'entity';
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return false;
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return false;
	    }
	  }, {
	    key: "getListItemsNode",
	    value: function getListItemsNode() {
	      return this.listItems ? this.listItems.getNode() : null;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.items.size === 0;
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return this.items;
	    }
	  }, {
	    key: "recalculateItemsSort",
	    value: function recalculateItemsSort() {
	      var _this2 = this;

	      var listItemsNode = this.getListItemsNode();

	      if (!listItemsNode) {
	        return;
	      }

	      var sort = 1;
	      listItemsNode.querySelectorAll('.tasks-scrum-item').forEach(function (node) {
	        var item = _this2.getItems().get(parseInt(node.dataset.itemId, 10));

	        if (item) {
	          item.setSort(sort);
	          sort++;
	        }
	      });
	    }
	  }, {
	    key: "getInput",
	    value: function getInput() {
	      return this.input;
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      var _this3 = this;

	      this.items.set(newItem.getItemId(), newItem);
	      this.subscribeToItem(newItem);
	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this3.setItemMoveActivity(item);
	      });
	      this.addNumberTasks(1);
	    }
	  }, {
	    key: "setItemMoveActivity",
	    value: function setItemMoveActivity(item) {
	      item.setMoveActivity(this.items.size > 2);
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      var _this4 = this;

	      if (this.items.has(item.getItemId())) {
	        this.items.delete(item.getItemId());
	        item.unsubscribeAll();
	        babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	          _this4.setItemMoveActivity(item);
	        });
	        this.subtractNumberTasks(1);
	      }
	    }
	  }, {
	    key: "isNodeCreated",
	    value: function isNodeCreated() {
	      return this.node !== null;
	    }
	  }, {
	    key: "setNumberTasks",
	    value: function setNumberTasks(numberTasks) {
	      this.numberTasks = main_core.Type.isInteger(numberTasks) ? parseInt(numberTasks, 10) : 0;
	    }
	  }, {
	    key: "getNumberTasks",
	    value: function getNumberTasks() {
	      return this.numberTasks ? this.numberTasks : this.getItems().size;
	    }
	  }, {
	    key: "addNumberTasks",
	    value: function addNumberTasks(value) {
	      if (!main_core.Type.isUndefined(value) && !isNaN(parseInt(value, 10))) {
	        this.numberTasks = this.numberTasks + parseInt(value, 10);
	      }
	    }
	  }, {
	    key: "subtractNumberTasks",
	    value: function subtractNumberTasks(value) {
	      if (!main_core.Type.isUndefined(value) && !isNaN(parseInt(value, 10))) {
	        this.numberTasks = this.numberTasks - parseInt(value, 10);
	      }
	    }
	  }, {
	    key: "hasInput",
	    value: function hasInput() {
	      return true;
	    }
	  }, {
	    key: "setExactSearchApplied",
	    value: function setExactSearchApplied(value) {
	      this.exactSearchApplied = Boolean(value);
	    }
	  }, {
	    key: "isExactSearchApplied",
	    value: function isExactSearchApplied() {
	      return this.exactSearchApplied;
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this5 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this5.subscribeToItem(item);

	        _this5.setItemMoveActivity(item);
	      });

	      if (!this.isCompleted()) {
	        this.input.onAfterAppend();
	        this.input.subscribe('tagsSearchOpen', function (baseEvent) {
	          _this5.emit('tagsSearchOpen', {
	            inputObject: baseEvent.getTarget(),
	            enteredHashTagName: baseEvent.getData()
	          });
	        });
	        this.input.subscribe('tagsSearchClose', function () {
	          return _this5.emit('tagsSearchClose');
	        });
	        this.input.subscribe('epicSearchOpen', function (baseEvent) {
	          _this5.emit('epicSearchOpen', {
	            inputObject: baseEvent.getTarget(),
	            enteredHashEpicName: baseEvent.getData()
	          });
	        });
	        this.input.subscribe('epicSearchClose', function () {
	          return _this5.emit('epicSearchClose');
	        });
	        this.input.subscribe('createTaskItem', function (baseEvent) {
	          _this5.emit('createTaskItem', {
	            inputObject: baseEvent.getTarget(),
	            value: baseEvent.getData()
	          });
	        });
	      }

	      this.updateStoryPoints();
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this6 = this;

	      if (!this.getListItemsNode()) {
	        return;
	      }

	      item.onAfterAppend(this.getListItemsNode());
	      item.setEntityType(this.getEntityType());
	      item.subscribe('updateItem', function (baseEvent) {
	        _this6.emit('updateItem', baseEvent.getData());
	      });
	      item.subscribe('updateStoryPoints', function () {
	        return _this6.updateStoryPoints();
	      });
	      item.subscribe('showTask', function (baseEvent) {
	        return _this6.emit('showTask', baseEvent.getTarget());
	      });
	      item.subscribe('move', function (baseEvent) {
	        _this6.emit('moveItem', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('moveToSprint', function (baseEvent) {
	        _this6.emit('moveToSprint', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('attachFilesToTask', function (baseEvent) {
	        _this6.emit('attachFilesToTask', {
	          item: baseEvent.getTarget(),
	          attachedIds: baseEvent.getData()
	        });
	      });
	      item.subscribe('showTagSearcher', function (baseEvent) {
	        _this6.emit('showTagSearcher', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('showEpicSearcher', function (baseEvent) {
	        _this6.emit('showEpicSearcher', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('startDecomposition', function (baseEvent) {
	        _this6.emit('startDecomposition', baseEvent.getTarget());
	      });
	      item.subscribe('remove', function (baseEvent) {
	        if (_this6.isGroupMode()) {
	          _this6.getGroupModeItems().forEach(function (groupModeItem) {
	            _this6.removeItem(groupModeItem);

	            groupModeItem.removeYourself();
	          });
	        } else {
	          var _item = baseEvent.getTarget();

	          _this6.removeItem(_item);

	          _item.removeYourself();
	        }

	        _this6.emit('removeItem', item);
	      });
	      item.subscribe('changeTaskResponsible', function (baseEvent) {
	        var item = baseEvent.getTarget();

	        _this6.emit('changeTaskResponsible', item);
	      });
	      item.subscribe('filterByEpic', function (baseEvent) {
	        _this6.emit('filterByEpic', baseEvent.getData());
	      });
	      item.subscribe('filterByTag', function (baseEvent) {
	        _this6.emit('filterByTag', baseEvent.getData());
	      });
	      item.subscribe('addItemToGroupMode', function (baseEvent) {
	        _this6.addItemToGroupMode(baseEvent.getTarget());
	      });
	      item.subscribe('removeItemFromGroupMode', function (baseEvent) {
	        _this6.removeItemFromGroupMode(baseEvent.getTarget());
	      });
	    }
	  }, {
	    key: "updateStoryPoints",
	    value: function updateStoryPoints() {
	      var _this7 = this;

	      this.storyPoints.clearPoints();
	      babelHelpers.toConsumableArray(this.getItems().values()).map(function (item) {
	        _this7.storyPoints.addPoints(item.getStoryPoints().getPoints());
	      });
	    }
	  }, {
	    key: "addTotalStoryPoints",
	    value: function addTotalStoryPoints(item) {}
	  }, {
	    key: "subtractTotalStoryPoints",
	    value: function subtractTotalStoryPoints(item) {}
	  }, {
	    key: "getItemByItemId",
	    value: function getItemByItemId(itemId) {
	      return this.items.get(main_core.Type.isInteger(itemId) ? parseInt(itemId, 10) : itemId);
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "isFirstItem",
	    value: function isFirstItem(item) {
	      var listItemsNode = this.getListItemsNode();
	      var itemNode = item.getItemNode();
	      var firstElementChild = this.hasInput() ? listItemsNode.firstElementChild.nextElementSibling : listItemsNode.firstElementChild;
	      return firstElementChild.isEqualNode(itemNode);
	    }
	  }, {
	    key: "isLastItem",
	    value: function isLastItem(item) {
	      var listItemsNode = this.getListItemsNode();
	      var itemNode = item.getItemNode();
	      return listItemsNode.lastElementChild.isEqualNode(itemNode);
	    }
	  }, {
	    key: "fadeOut",
	    value: function fadeOut() {
	      this.getListItemsNode().classList.add('tasks-scrum-entity-items-faded');
	    }
	  }, {
	    key: "fadeIn",
	    value: function fadeIn() {
	      this.getListItemsNode().classList.remove('tasks-scrum-entity-items-faded');
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      this.groupActionsButton.deactivateGroupMode();
	    }
	  }, {
	    key: "onActivateGroupMode",
	    value: function onActivateGroupMode(baseEvent) {
	      this.groupMode = true;
	      this.input.disable();
	      this.emit('activateGroupMode');
	    }
	  }, {
	    key: "onDeactivateGroupMode",
	    value: function onDeactivateGroupMode(baseEvent) {
	      this.groupMode = false;
	      this.input.unDisable();
	      this.groupModeItems.forEach(function (item) {
	        item.deactivateGroupMode();
	      });
	      this.groupModeItems.clear();
	      this.emit('deactivateGroupMode');
	    }
	  }, {
	    key: "isGroupMode",
	    value: function isGroupMode() {
	      return this.groupMode;
	    }
	  }, {
	    key: "addItemToGroupMode",
	    value: function addItemToGroupMode(item) {
	      this.groupModeItems.set(item.getItemId(), item);
	    }
	  }, {
	    key: "getGroupModeItems",
	    value: function getGroupModeItems() {
	      return this.groupModeItems;
	    }
	  }, {
	    key: "removeItemFromGroupMode",
	    value: function removeItemFromGroupMode(item) {
	      this.groupModeItems.delete(item.getItemId());
	    }
	  }]);
	  return Entity;
	}(main_core_events.EventEmitter);

	function _templateObject$9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-backlog-title\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-backlog-epics-title ui-btn ui-btn-xs ui-btn-secondary\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div title=\"Definition of Done\" class=\"tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-dod\">\n\t\t\t\t<span class=\"tasks-scrum-entity-dod-icon\"></span>\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-backlog-title-spacer\"></div>\n\t\t\t<div class=\"tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-task\">\n\t\t\t\t<span class=\"tasks-scrum-entity-tasks-icon\"></span>\n\t\t\t\t<span class=\"tasks-scrum-entity-tasks-title\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-backlog-story-point-title\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-backlog-story-point\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$9 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Header = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Header, _EventEmitter);

	  function Header(entity) {
	    var _this;

	    babelHelpers.classCallCheck(this, Header);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Header).call(this, entity));

	    _this.setEventNamespace('BX.Tasks.Scrum.BacklogHeader');

	    _this.entity = entity;
	    _this.element = null;
	    return _this;
	  }

	  babelHelpers.createClass(Header, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      this.element = main_core.Tag.render(_templateObject$9(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_EPICS_TITLE'), this.entity.getNumberTasks(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE_STORY_POINTS'), main_core.Text.encode(this.entity.getStoryPoints().getPoints()));
	      main_core.Event.bind(this.getElementByClassName(this.element, 'tasks-scrum-backlog-epics-title'), 'click', function () {
	        _this2.emit('openListEpicGrid');
	      });
	      main_core.Event.bind(this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-dod'), 'click', function () {
	        _this2.emit('openDefinitionOfDone');
	      });
	      return this.element;
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      this.getElementByClassName(this.element, 'tasks-scrum-backlog-story-point').textContent = main_core.Text.encode(storyPoints);
	    }
	  }, {
	    key: "updateNumberTasks",
	    value: function updateNumberTasks() {
	      var parentNode = this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-task');
	      parentNode.querySelector('.tasks-scrum-entity-tasks-title').textContent = this.entity.getNumberTasks();
	    }
	  }, {
	    key: "getElementByClassName",
	    value: function getElementByClassName(elements, className) {
	      var element = null;
	      elements.forEach(function (elem) {
	        if (elem.classList.contains(className)) {
	          element = elem;
	        }
	      });
	      return element;
	    }
	  }]);
	  return Header;
	}(main_core_events.EventEmitter);

	function _templateObject$a() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-epic\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"]);

	  _templateObject$a = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var EpicCreationButton = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(EpicCreationButton, _EventEmitter);

	  function EpicCreationButton() {
	    var _this;

	    babelHelpers.classCallCheck(this, EpicCreationButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EpicCreationButton).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.EpicCreationButton');

	    return _this;
	  }

	  babelHelpers.createClass(EpicCreationButton, [{
	    key: "render",
	    value: function render() {
	      var element = main_core.Tag.render(_templateObject$a(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD'));
	      main_core.Event.bind(element, 'click', this.onClick.bind(this));
	      return element;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('openAddEpicForm');
	    }
	  }]);
	  return EpicCreationButton;
	}(main_core_events.EventEmitter);

	function _templateObject$b() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-backlog\">\n\t\t\t\t<div class=\"tasks-scrum-backlog-header\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-backlog-actions\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-backlog-items\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$b = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Backlog = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Backlog, _Entity);

	  function Backlog(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Backlog);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Backlog).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Backlog');

	    _this.setBacklogParams(params);

	    _this.header = null;
	    _this.epicCreationButton = null;
	    return _this;
	  }

	  babelHelpers.createClass(Backlog, [{
	    key: "setBacklogParams",
	    value: function setBacklogParams(params) {
	      var _this2 = this;

	      params.items.forEach(function (itemData) {
	        var item = new Item(itemData);

	        _this2.items.set(item.itemId, item);
	      });
	    }
	  }, {
	    key: "addHeader",
	    value: function addHeader(header) {
	      var _this3 = this;

	      this.header = header;
	      this.header.subscribe('openListEpicGrid', function () {
	        return _this3.emit('openListEpicGrid');
	      });
	      this.header.subscribe('openDefinitionOfDone', function () {
	        return _this3.emit('openDefinitionOfDone');
	      });
	    }
	  }, {
	    key: "addEpicCreationButton",
	    value: function addEpicCreationButton(epicCreationButton) {
	      var _this4 = this;

	      this.epicCreationButton = epicCreationButton;
	      this.epicCreationButton.subscribe('openAddEpicForm', function () {
	        return _this4.emit('openAddEpicForm');
	      });
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'backlog';
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return false;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$b(), this.header ? this.header.render() : '', this.epicCreationButton ? this.epicCreationButton.render() : '', this.groupActionsButton ? this.groupActionsButton.render() : '', this.listItems ? this.listItems.render() : '');
	      return this.node;
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "setItem", this).call(this, newItem);
	      this.updateStoryPoints();
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "removeItem", this).call(this, item);
	      this.updateStoryPoints();
	    }
	  }, {
	    key: "addNumberTasks",
	    value: function addNumberTasks(value) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "addNumberTasks", this).call(this, value);

	      if (this.header) {
	        this.header.updateNumberTasks();
	      }
	    }
	  }, {
	    key: "subtractNumberTasks",
	    value: function subtractNumberTasks(value) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "subtractNumberTasks", this).call(this, value);

	      if (this.header) {
	        this.header.updateNumberTasks();
	      }
	    }
	  }, {
	    key: "updateStoryPoints",
	    value: function updateStoryPoints() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "updateStoryPoints", this).call(this);

	      if (this.header) {
	        this.header.setStoryPoints(this.getStoryPoints().getPoints());
	      }
	    }
	  }, {
	    key: "onActivateGroupMode",
	    value: function onActivateGroupMode(baseEvent) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "onActivateGroupMode", this).call(this, baseEvent);
	      main_core.Dom.addClass(this.node.querySelector('.tasks-scrum-backlog-items'), 'tasks-scrum-backlog-items-group-mode');
	    }
	  }, {
	    key: "onDeactivateGroupMode",
	    value: function onDeactivateGroupMode(baseEvent) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "onDeactivateGroupMode", this).call(this, baseEvent);
	      main_core.Dom.removeClass(this.node.querySelector('.tasks-scrum-backlog-items'), 'tasks-scrum-backlog-items-group-mode');
	    }
	  }], [{
	    key: "buildBacklog",
	    value: function buildBacklog(backlogData) {
	      var backlog = new Backlog(backlogData);
	      backlog.addHeader(new Header(backlog));
	      backlog.addEpicCreationButton(new EpicCreationButton());
	      backlog.addGroupActionsButton(new GroupActionsButton());
	      backlog.addListItems(new ListItems(backlog));
	      return backlog;
	    }
	  }]);
	  return Backlog;
	}(Entity);

	function _templateObject$c() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-date\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-start\">", "</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-separator\">-</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-end\">", "</div>\n\t\t\t\t<input type=\"hidden\" name=\"dateStart\">\n\t\t\t\t<input type=\"hidden\" name=\"dateEnd\">\n\t\t\t</div>\n\t\t"]);

	  _templateObject$c = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var SprintDate = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SprintDate, _EventEmitter);

	  function SprintDate(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, SprintDate);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SprintDate).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.SprintDate');

	    _this.sprint = sprint;
	    _this.nodeId = 'tasks-scrum-sprint-header-date-' + main_core.Text.getRandom();
	    _this.defaultSprintDuration = sprint.getDefaultSprintDuration();
	    return _this;
	  }

	  babelHelpers.createClass(SprintDate, [{
	    key: "createDate",
	    value: function createDate(startTimestamp, endTimestamp) {
	      if (this.sprint.isActive() || this.sprint.isCompleted()) {
	        return '';
	      }
	      /* eslint-disable */


	      var dateStart = BX.date.format('j F', startTimestamp);
	      var dateEnd = BX.date.format('j F', endTimestamp);
	      /* eslint-enable */

	      return this.renderNode(this.nodeId, dateStart, dateEnd);
	    }
	  }, {
	    key: "renderNode",
	    value: function renderNode(nodeId, dateStart, dateEnd) {
	      return main_core.Tag.render(_templateObject$c(), nodeId, dateStart, dateEnd);
	    }
	  }, {
	    key: "updateDateStartNode",
	    value: function updateDateStartNode(timestamp) {
	      if (this.node) {
	        var dateStartNode = this.node.querySelector('.tasks-scrum-sprint-date-start');
	        dateStartNode.textContent = BX.date.format('j F', timestamp);
	      }
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      if (this.node) {
	        var dateEndNode = this.node.querySelector('.tasks-scrum-sprint-date-end');
	        dateEndNode.textContent = BX.date.format('j F', timestamp);
	      }
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this2 = this;

	      if (this.sprint.isActive() || this.sprint.isCompleted()) {
	        return;
	      }

	      this.node = document.getElementById(this.nodeId);
	      var parentPopup = this.node.closest('.popup-window');

	      var customBlur = function customBlur() {
	        BX.calendar.get().popup.close();
	      };

	      var showCalendar = function showCalendar(node, field) {
	        /* eslint-disable */
	        BX.calendar({
	          node: node,
	          field: field,
	          bTime: false,
	          bSetFocus: false,
	          bHideTime: false
	        });
	        /* eslint-enable */

	        if (parentPopup) {
	          main_core.Event.bindOnce(parentPopup, 'click', customBlur);
	        }
	      };

	      var updateDateNode = function updateDateNode(node, value) {
	        /* eslint-disable */
	        node.textContent = BX.date.format('j F', Math.floor(BX.parseDate(value).getTime() / 1000));
	        /* eslint-enable */
	      };

	      var sendRequest = function sendRequest(data) {
	        _this2.emit('changeSprintDeadline', data);
	      };

	      var dateStartNode = this.node.querySelector('.tasks-scrum-sprint-date-start');
	      var dateEndNode = this.node.querySelector('.tasks-scrum-sprint-date-end');
	      var dateStartInput = this.node.querySelector('input[name="dateStart"]');
	      var dateEndInput = this.node.querySelector('input[name="dateEnd"]');
	      main_core.Event.bind(this.node, 'click', function (event) {
	        var target = event.target;

	        if (target.classList.contains('tasks-scrum-sprint-date-start')) {
	          showCalendar(target, dateStartInput);
	        } else if (target.classList.contains('tasks-scrum-sprint-date-end')) {
	          showCalendar(target, dateEndInput);
	        }

	        event.stopPropagation();
	      });
	      main_core.Event.bind(dateStartInput, 'change', function (event) {
	        var value = event.target.value;
	        updateDateNode(dateStartNode, value);
	        sendRequest({
	          sprintId: _this2.sprint.getId(),
	          dateStart: Math.floor(BX.parseDate(value).getTime() / 1000)
	        });

	        if (parentPopup) {
	          main_core.Event.unbind(parentPopup, 'click', customBlur);
	        }
	      });
	      main_core.Event.bind(dateEndInput, 'change', function (event) {
	        var value = event.target.value;
	        updateDateNode(dateEndNode, value);
	        sendRequest({
	          sprintId: _this2.sprint.getId(),
	          dateEnd: Math.floor(BX.parseDate(value).getTime() / 1000)
	        });

	        if (parentPopup) {
	          main_core.Event.unbind(parentPopup, 'click', customBlur);
	        }
	      });
	    }
	  }, {
	    key: "getWeeks",
	    value: function getWeeks() {
	      var weekCount = parseInt(this.defaultSprintDuration, 10) / 604800;

	      if (weekCount > 5) {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_3');
	      } else if (weekCount === 1) {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_1');
	      } else {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_2');
	      }
	    }
	  }]);
	  return SprintDate;
	}(main_core_events.EventEmitter);

	var StatsCalculator = /*#__PURE__*/function () {
	  function StatsCalculator() {
	    babelHelpers.classCallCheck(this, StatsCalculator);
	  }

	  babelHelpers.createClass(StatsCalculator, [{
	    key: "calculatePercentage",
	    value: function calculatePercentage(first, second) {
	      var result = Math.round(second * 100 / first);
	      return isNaN(result) ? 0 : result;
	    }
	  }]);
	  return StatsCalculator;
	}();

	var StatsHeader = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(StatsHeader, _EventEmitter);

	  function StatsHeader(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, StatsHeader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StatsHeader).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.StatsHeader');

	    _this.setSprintData(sprint);

	    _this.statsCalculator = new StatsCalculator();
	    _this.weekendDaysTime = sprint.getWeekendDaysTime();
	    _this.headerNode = null;
	    _this.headerClass = 'tasks-scrum-sprint-header-stats';
	    _this.kanbanMode = false;
	    return _this;
	  }

	  babelHelpers.createClass(StatsHeader, [{
	    key: "setKanbanStyle",
	    value: function setKanbanStyle() {
	      this.kanbanMode = true;
	      this.headerClass = 'tasks-scrum-sprint-header-stats-kanban';
	    }
	  }, {
	    key: "isKanbanMode",
	    value: function isKanbanMode() {
	      return this.kanbanMode;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return '';
	    }
	  }, {
	    key: "updateStats",
	    value: function updateStats(sprint) {
	      this.setSprintData(sprint);
	      main_core.Dom.replace(this.headerNode, this.render());
	    }
	  }, {
	    key: "setSprintData",
	    value: function setSprintData(sprint) {
	      this.setStoryPoints(sprint.getTotalStoryPoints().getPoints());
	      this.setCompletedStoryPoints(sprint.getTotalCompletedStoryPoints().getPoints());
	      this.setUncompletedStoryPoints(sprint.getTotalUncompletedStoryPoints().getPoints());
	      this.setEndDate(sprint.getDateEnd());
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        this.storyPoints = 0;
	      } else {
	        this.storyPoints = parseFloat(storyPoints);
	      }
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "setCompletedStoryPoints",
	    value: function setCompletedStoryPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        this.completedStoryPoints = 0;
	      } else {
	        this.completedStoryPoints = parseFloat(storyPoints);
	      }
	    }
	  }, {
	    key: "getCompletedStoryPoints",
	    value: function getCompletedStoryPoints() {
	      return this.completedStoryPoints;
	    }
	  }, {
	    key: "setUncompletedStoryPoints",
	    value: function setUncompletedStoryPoints(storyPoints) {
	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        this.uncompletedStoryPoints = 0;
	      } else {
	        this.uncompletedStoryPoints = parseFloat(storyPoints);
	      }
	    }
	  }, {
	    key: "getUncompletedStoryPoints",
	    value: function getUncompletedStoryPoints() {
	      return this.completedStoryPoints;
	    }
	  }, {
	    key: "setEndDate",
	    value: function setEndDate(endDate) {
	      if (main_core.Type.isUndefined(endDate) || isNaN(parseInt(endDate, 10))) {
	        this.endDate = Date.now() / 1000;
	      } else {
	        this.endDate = parseInt(endDate, 10);
	      }
	    }
	  }, {
	    key: "getEndDate",
	    value: function getEndDate() {
	      return this.endDate;
	    }
	  }]);
	  return StatsHeader;
	}(main_core_events.EventEmitter);

	function _templateObject$d() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$d = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var CompletedStatsHeader = /*#__PURE__*/function (_StatsHeader) {
	  babelHelpers.inherits(CompletedStatsHeader, _StatsHeader);

	  function CompletedStatsHeader() {
	    babelHelpers.classCallCheck(this, CompletedStatsHeader);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompletedStatsHeader).apply(this, arguments));
	  }

	  babelHelpers.createClass(CompletedStatsHeader, [{
	    key: "render",
	    value: function render() {
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var completedDate = this.getCompletedDate(this.getEndDate());
	      var label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL').replace('#percent#', '<b>' + percentage + '%</b>').replace('#date#', completedDate);
	      this.headerNode = main_core.Tag.render(_templateObject$d(), this.headerClass, label);
	      return this.headerNode;
	    }
	  }, {
	    key: "getCompletedDate",
	    value: function getCompletedDate(endDate) {
	      return BX.date.format('j F Y', endDate);
	    }
	  }]);
	  return CompletedStatsHeader;
	}(StatsHeader);

	function _templateObject$e() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$e = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ExpiredStatsHeader = /*#__PURE__*/function (_StatsHeader) {
	  babelHelpers.inherits(ExpiredStatsHeader, _StatsHeader);

	  function ExpiredStatsHeader() {
	    babelHelpers.classCallCheck(this, ExpiredStatsHeader);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExpiredStatsHeader).apply(this, arguments));
	  }

	  babelHelpers.createClass(ExpiredStatsHeader, [{
	    key: "render",
	    value: function render() {
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var expiredDay = this.getExpiredDay(this.getEndDate());
	      var label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_EXPIRED_LABEL').replace('#percent#', '<b>' + percentage + '%</b>').replace('#date#', expiredDay);
	      this.headerNode = main_core.Tag.render(_templateObject$e(), this.headerClass, label);
	      return this.headerNode;
	    }
	  }, {
	    key: "getExpiredDay",
	    value: function getExpiredDay(endDate) {
	      return BX.date.format('j F Y', endDate);
	    }
	  }]);
	  return ExpiredStatsHeader;
	}(StatsHeader);

	function _templateObject$f() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$f = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ActiveStatsHeader = /*#__PURE__*/function (_StatsHeader) {
	  babelHelpers.inherits(ActiveStatsHeader, _StatsHeader);

	  function ActiveStatsHeader() {
	    babelHelpers.classCallCheck(this, ActiveStatsHeader);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActiveStatsHeader).apply(this, arguments));
	  }

	  babelHelpers.createClass(ActiveStatsHeader, [{
	    key: "render",
	    value: function render() {
	      var remainingDays = this.getRemainingDays(this.getEndDate());
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var label = '';

	      if (main_core.Type.isInteger(remainingDays) && remainingDays <= 1) {
	        label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LAST_LABEL').replace('#percent#', '<b>' + percentage + '%</b>');
	      } else {
	        label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL').replace('#days#', '<b>' + remainingDays + '</b>').replace('#percent#', '<b>' + percentage + '%</b>');
	      }

	      this.headerNode = main_core.Tag.render(_templateObject$f(), this.headerClass, label);
	      return this.headerNode;
	    }
	  }, {
	    key: "getRemainingDays",
	    value: function getRemainingDays(endDate) {
	      var dateWithWeekendOffset = new Date();
	      dateWithWeekendOffset.setSeconds(dateWithWeekendOffset.getSeconds() + this.weekendDaysTime);
	      var dateEnd = new Date(endDate * 1000);
	      var msPerMinute = 60 * 1000;
	      var msPerHour = msPerMinute * 60;
	      var msPerDay = msPerHour * 24;
	      var daysRemaining = Math.round((dateEnd - dateWithWeekendOffset) / msPerDay);

	      if (daysRemaining <= 1) {
	        return daysRemaining;
	      } else {
	        return BX.date.format('ddiff', dateWithWeekendOffset, dateEnd);
	      }
	    }
	  }]);
	  return ActiveStatsHeader;
	}(StatsHeader);

	var StatsHeaderBuilder = /*#__PURE__*/function () {
	  function StatsHeaderBuilder() {
	    babelHelpers.classCallCheck(this, StatsHeaderBuilder);
	  }

	  babelHelpers.createClass(StatsHeaderBuilder, null, [{
	    key: "build",
	    value: function build(sprint) {
	      if (sprint.isCompleted()) {
	        return new CompletedStatsHeader(sprint);
	      } else if (sprint.isExpired()) {
	        return new ExpiredStatsHeader(sprint);
	      } else if (sprint.isActive()) {
	        return new ActiveStatsHeader(sprint);
	      } else {
	        return new StatsHeader(sprint);
	      }
	    }
	  }]);
	  return StatsHeaderBuilder;
	}();

	function _templateObject6$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-header-button\">\n\t\t\t\t\t<button class=\"", "\">", "</button>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject6$2 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-sprint-header-remove\"></div>"]);

	  _templateObject5$2 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-sprint-header-empty\"></div>"]);

	  _templateObject4$2 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-sprint-dragndrop\"></div>"]);

	  _templateObject3$3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-header-params\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-tick\">\n\t\t\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-light ", "\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$4 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$g() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-header ", "\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-name-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-edit\"></div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$g = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var SprintHeader = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SprintHeader, _EventEmitter);

	  function SprintHeader(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, SprintHeader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SprintHeader).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.SprintHeader');

	    _this.sprint = sprint;
	    _this.node = null;
	    _this.statsHeader = null;
	    _this.sprintDate = null;
	    return _this;
	  }

	  babelHelpers.createClass(SprintHeader, [{
	    key: "addHeaderStats",
	    value: function addHeaderStats(statsHeader) {
	      this.statsHeader = statsHeader;
	    }
	  }, {
	    key: "addHeaderDate",
	    value: function addHeaderDate(sprintDate) {
	      var _this2 = this;

	      this.sprintDate = sprintDate;
	      this.sprintDate.subscribe('changeSprintDeadline', function (baseEvent) {
	        _this2.emit('changeSprintDeadline', baseEvent.getData());
	      });
	    }
	  }, {
	    key: "initStyle",
	    value: function initStyle(sprint) {
	      this.sprint = sprint;

	      if (this.sprint.isActive()) {
	        this.headerClass = 'tasks-scrum-sprint-header-active';
	        this.buttonClass = 'ui-btn ui-btn-success ui-btn-xs';
	        this.buttonText = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_COMPLETE_BUTTON');
	      } else if (this.sprint.isCompleted()) {
	        this.headerClass = 'tasks-scrum-sprint-header-completed';
	        main_core.Dom.remove(this.buttonNode);
	        this.sprint.hideContent();
	      } else if (this.sprint.isPlanned()) {
	        this.headerClass = 'tasks-scrum-sprint-header-planned';
	        this.buttonClass = 'ui-btn ui-btn-primary ui-btn-xs';
	        this.buttonText = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_START_BUTTON');
	      }

	      if (this.node) {
	        this.addHeaderStats(StatsHeaderBuilder.build(this.sprint));
	        this.addHeaderDate(new SprintDate(this.sprint));

	        if (this.sprint.isDisabled()) {
	          main_core.Dom.remove(this.node.querySelector('.tasks-scrum-sprint-header-remove'));
	        }

	        this.node.className = '';
	        main_core.Dom.addClass(this.node, 'tasks-scrum-sprint-header ' + this.headerClass);
	        var button = this.buttonNode.querySelector('button');
	        button.className = '';
	        main_core.Dom.addClass(button, this.buttonClass);
	        button.firstChild.replaceWith(this.buttonText);
	        main_core.Dom.replace(this.node.querySelector('.tasks-scrum-sprint-header-params'), this.renderParams());
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$g(), this.headerClass, this.renderDragnDrop(), main_core.Text.encode(this.sprint.getName()), this.renderRemove(), this.renderParams());
	      return this.node;
	    }
	  }, {
	    key: "renderParams",
	    value: function renderParams() {
	      var tickAngleClass = this.sprint.isCompleted() ? 'ui-btn-icon-angle-down' : 'ui-btn-icon-angle-up';
	      return main_core.Tag.render(_templateObject2$4(), this.renderStatsHeader(), this.sprintDate ? this.sprintDate.createDate(this.sprint.getDateStart(), this.sprint.getDateEnd()) : '', this.createButton(), tickAngleClass);
	    }
	  }, {
	    key: "getStatsHeader",
	    value: function getStatsHeader() {
	      return this.statsHeader;
	    }
	  }, {
	    key: "renderDragnDrop",
	    value: function renderDragnDrop() {
	      if (this.sprint.isPlanned()) {
	        return main_core.Tag.render(_templateObject3$3());
	      } else {
	        return main_core.Tag.render(_templateObject4$2());
	      }
	    }
	  }, {
	    key: "renderRemove",
	    value: function renderRemove() {
	      if (this.sprint.isPlanned()) {
	        return main_core.Tag.render(_templateObject5$2());
	      } else {
	        return '';
	      }
	    }
	  }, {
	    key: "renderStatsHeader",
	    value: function renderStatsHeader() {
	      return this.statsHeader ? this.statsHeader.render() : '';
	    }
	  }, {
	    key: "updateNameNode",
	    value: function updateNameNode(name) {
	      if (this.node) {
	        this.node.querySelector('.tasks-scrum-sprint-header-name').textContent = main_core.Text.encode(name);
	      }
	    }
	  }, {
	    key: "updateStatsHeader",
	    value: function updateStatsHeader() {
	      this.statsHeader.updateStats(this.sprint);
	    }
	  }, {
	    key: "updateDateStartNode",
	    value: function updateDateStartNode(timestamp) {
	      if (this.sprintDate) {
	        this.sprintDate.updateDateStartNode(timestamp);
	      }
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      if (this.sprintDate) {
	        this.sprintDate.updateDateEndNode(timestamp);
	      }
	    }
	  }, {
	    key: "createButton",
	    value: function createButton() {
	      if (this.sprint.isCompleted()) {
	        return '';
	      } else {
	        this.buttonNodeId = 'tasks-scrum-sprint-header-button-' + this.sprint.getId();
	        return main_core.Tag.render(_templateObject6$2(), this.buttonNodeId, this.buttonClass, this.buttonText);
	      }
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this3 = this;

	      if (!this.sprint.isCompleted()) {
	        this.buttonNode = document.getElementById(this.buttonNodeId);
	        main_core.Event.bind(this.buttonNode, 'click', this.onButtonClick.bind(this));
	      }

	      var nameNode = this.node.querySelector('.tasks-scrum-sprint-header-name-container');
	      var editButtonNode = this.node.querySelector('.tasks-scrum-sprint-header-edit');
	      main_core.Event.bind(editButtonNode, 'click', function () {
	        return _this3.emit('changeName', nameNode);
	      });

	      if (this.sprint.isPlanned()) {
	        var removeNode = this.node.querySelector('.tasks-scrum-sprint-header-remove');
	        main_core.Event.bind(removeNode, 'click', function () {
	          ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_SPRINT'), function (messageBox) {
	            _this3.emit('removeSprint');

	            messageBox.close();
	          }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));
	        });
	      }

	      var tickButtonNode = this.node.querySelector('.tasks-scrum-sprint-header-tick');
	      main_core.Event.bind(tickButtonNode, 'click', function () {
	        tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
	        tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-down');

	        _this3.emit('toggleVisibilityContent');
	      });

	      if (this.sprintDate) {
	        this.sprintDate.onAfterAppend();
	      }
	    }
	  }, {
	    key: "onButtonClick",
	    value: function onButtonClick() {
	      if (this.sprint.isActive()) {
	        this.emit('completeSprint');
	      } else if (this.sprint.isPlanned()) {
	        this.emit('startSprint');
	      }
	    }
	  }], [{
	    key: "buildHeader",
	    value: function buildHeader(sprint) {
	      var sprintHeader = new SprintHeader(sprint);
	      sprintHeader.addHeaderStats(StatsHeaderBuilder.build(sprint));
	      sprintHeader.addHeaderDate(new SprintDate(sprint));
	      return sprintHeader;
	    }
	  }]);
	  return SprintHeader;
	}(main_core_events.EventEmitter);

	function _templateObject$h() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"]);

	  _templateObject$h = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var EventsHeader = /*#__PURE__*/function () {
	  function EventsHeader() {
	    babelHelpers.classCallCheck(this, EventsHeader);
	    this.element = null;
	  }

	  babelHelpers.createClass(EventsHeader, [{
	    key: "render",
	    value: function render() {
	      this.element = main_core.Tag.render(_templateObject$h(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_EVENT'));
	      return this.element;
	    }
	  }]);
	  return EventsHeader;
	}();

	function _templateObject5$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-burn-down-chart\">\n\t\t\t\t<span class=\"tasks-scrum-entity-burn-down-chart-icon\"></span>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$3 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t\t<div class=\"tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-task\">\n\t\t\t\t<span class=\"tasks-scrum-entity-tasks-icon\"></span>\n\t\t\t\t<span class=\"tasks-scrum-entity-tasks-title\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t"]);

	  _templateObject4$3 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-story-point-done-title\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-done\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$4 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-story-point-in-work-title\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-in-work\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$5 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$i() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-story-point-title\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum-sprint-story-point\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$i = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var StoryPointsHeader = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(StoryPointsHeader, _EventEmitter);

	  function StoryPointsHeader(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, StoryPointsHeader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StoryPointsHeader).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.StoryPointsHeader');

	    _this.sprint = sprint;
	    _this.element = null;
	    return _this;
	  }

	  babelHelpers.createClass(StoryPointsHeader, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      this.totalStoryPointsNode = main_core.Tag.render(_templateObject$i(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS'), this.sprint.getTotalStoryPoints().getPoints());
	      this.inWorkStoryPointsNode = this.sprint.isActive() ? main_core.Tag.render(_templateObject2$5(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_IN_WORK'), this.sprint.getTotalUncompletedStoryPoints().getPoints()) : '';
	      this.doneStoryPointsNode = this.sprint.isPlanned() ? '' : main_core.Tag.render(_templateObject3$4(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_DONE'), this.sprint.getTotalCompletedStoryPoints().getPoints());
	      this.element = main_core.Tag.render(_templateObject4$3(), this.renderBurnDownChartIcon(), this.sprint.getNumberTasks(), this.totalStoryPointsNode, this.inWorkStoryPointsNode, this.doneStoryPointsNode);
	      main_core.Event.bind(this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-burn-down-chart'), 'click', function () {
	        return _this2.emit('showSprintBurnDownChart');
	      });
	      return this.element;
	    }
	  }, {
	    key: "renderBurnDownChartIcon",
	    value: function renderBurnDownChartIcon() {
	      if (this.sprint.isPlanned()) {
	        return '';
	      }

	      return main_core.Tag.render(_templateObject5$3());
	    }
	  }, {
	    key: "updateNumberTasks",
	    value: function updateNumberTasks() {
	      var parentNode = this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-task');
	      parentNode.querySelector('.tasks-scrum-entity-tasks-title').textContent = this.sprint.getNumberTasks();
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      if (!this.totalStoryPointsNode) {
	        return;
	      }

	      this.getElementByClassName(this.totalStoryPointsNode, 'tasks-scrum-sprint-story-point').textContent = main_core.Text.encode(storyPoints);
	    }
	  }, {
	    key: "setCompletedStoryPoints",
	    value: function setCompletedStoryPoints(storyPoints) {
	      if (!this.doneStoryPointsNode) {
	        return;
	      }

	      this.getElementByClassName(this.doneStoryPointsNode, 'tasks-scrum-sprint-story-point-done').textContent = main_core.Text.encode(storyPoints);
	    }
	  }, {
	    key: "setUncompletedStoryPoints",
	    value: function setUncompletedStoryPoints(storyPoints) {
	      if (!this.inWorkStoryPointsNode) {
	        return;
	      }

	      this.getElementByClassName(this.inWorkStoryPointsNode, 'tasks-scrum-sprint-story-point-in-work').textContent = main_core.Text.encode(storyPoints);
	    }
	  }, {
	    key: "getElementByClassName",
	    value: function getElementByClassName(elements, className) {
	      var element = null;
	      elements.forEach(function (elem) {
	        if (elem.classList.contains(className)) {
	          element = elem;
	        }
	      });
	      return element;
	    }
	  }]);
	  return StoryPointsHeader;
	}(main_core_events.EventEmitter);

	function _templateObject4$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input type=\"text\" class=\"tasks-scrum-sprint-header-name\" value=\"", "\">\n\t\t\t"]);

	  _templateObject4$4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a href=\"", "\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"]);

	  _templateObject3$5 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-header-event-params\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$6 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$j() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint\" data-sprint-sort=\"", "\" data-sprint-id=\"", "\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sub-header\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-event\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-actions\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-items\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$j = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Sprint = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Sprint, _Entity);

	  function Sprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Sprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint');

	    _this.setSprintParams(params);

	    _this.sprintHeader = null;
	    _this.eventsHeader = null;
	    _this.storyPointsHeader = null;
	    return _this;
	  }

	  babelHelpers.createClass(Sprint, [{
	    key: "setSprintParams",
	    value: function setSprintParams(params) {
	      this.setTmpId(params.tmpId);
	      this.setName(params.name);
	      this.setSort(params.sort);
	      this.setDateStart(params.dateStart);
	      this.setDateEnd(params.dateEnd);
	      this.setWeekendDaysTime(params.weekendDaysTime);
	      this.setDefaultSprintDuration(params.defaultSprintDuration);
	      this.setStatus(params.status);
	      this.setTotalStoryPoints(params.totalStoryPoints);
	      this.setTotalCompletedStoryPoints(params.totalCompletedStoryPoints);
	      this.setTotalUncompletedStoryPoints(params.totalUncompletedStoryPoints);
	      this.setCompletedTasks(params.completedTasks);
	      this.setUncompletedTasks(params.uncompletedTasks);
	      this.setItems(params.items);
	      this.setInfo(params.info);
	    }
	  }, {
	    key: "addSprintHeader",
	    value: function addSprintHeader(sprintHeader) {
	      var _this2 = this;

	      this.sprintHeader = sprintHeader;
	      this.sprintHeader.initStyle(this);
	      this.sprintHeader.subscribe('changeName', this.onChangeName.bind(this));
	      this.sprintHeader.subscribe('removeSprint', this.onRemoveSprint.bind(this));
	      this.sprintHeader.subscribe('completeSprint', function () {
	        return _this2.emit('completeSprint');
	      });
	      this.sprintHeader.subscribe('startSprint', function () {
	        return _this2.emit('startSprint');
	      });
	      this.sprintHeader.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
	      this.sprintHeader.subscribe('toggleVisibilityContent', this.toggleVisibilityContent.bind(this));
	    }
	  }, {
	    key: "addStoryPointsHeader",
	    value: function addStoryPointsHeader(storyPointsHeader) {
	      var _this3 = this;

	      this.storyPointsHeader = storyPointsHeader;
	      this.storyPointsHeader.subscribe('showSprintBurnDownChart', function () {
	        return _this3.emit('showSprintBurnDownChart');
	      });
	    }
	  }, {
	    key: "addEventsHeader",
	    value: function addEventsHeader(eventsHeader) {
	      this.eventsHeader = eventsHeader;
	    }
	  }, {
	    key: "initStyle",
	    value: function initStyle() {
	      if (this.sprintHeader) {
	        this.sprintHeader.initStyle(this);
	      }
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.getStatus() === 'active';
	    }
	  }, {
	    key: "isPlanned",
	    value: function isPlanned() {
	      return this.getStatus() === 'planned';
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return this.getStatus() === 'completed';
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return this.isCompleted();
	    }
	  }, {
	    key: "isExpired",
	    value: function isExpired() {
	      var sprintEnd = new Date(this.dateEnd * 1000);
	      return this.isActive() && sprintEnd.getTime() < new Date().getTime();
	    }
	  }, {
	    key: "hasInput",
	    value: function hasInput() {
	      return !this.isDisabled();
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'sprint';
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "setItem", this).call(this, newItem);
	      newItem.setDisableStatus(this.isDisabled());
	      this.updateStoryPoints();
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "removeItem", this).call(this, item);
	      this.updateStoryPoints();
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.name = main_core.Type.isString(name) ? name : '';

	      if (this.isNodeCreated() && this.sprintHeader) {
	        this.sprintHeader.updateNameNode(this.name);
	      }
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "setTmpId",
	    value: function setTmpId(tmpId) {
	      this.tmpId = main_core.Type.isString(tmpId) ? tmpId : '';
	    }
	  }, {
	    key: "getTmpId",
	    value: function getTmpId() {
	      return this.tmpId;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.sort = main_core.Type.isInteger(sort) ? parseInt(sort, 10) : 1;
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.sort;
	    }
	  }, {
	    key: "setDateStart",
	    value: function setDateStart(dateStart) {
	      this.dateStart = main_core.Type.isInteger(dateStart) ? parseInt(dateStart, 10) : 0;

	      if (this.isNodeCreated() && this.sprintHeader) {
	        this.sprintHeader.updateDateStartNode(this.dateStart);
	      }
	    }
	  }, {
	    key: "getDateStart",
	    value: function getDateStart() {
	      return parseInt(this.dateStart, 10);
	    }
	  }, {
	    key: "setDateEnd",
	    value: function setDateEnd(dateEnd) {
	      this.dateEnd = main_core.Type.isInteger(dateEnd) ? parseInt(dateEnd, 10) : 0;

	      if (this.isNodeCreated() && this.sprintHeader) {
	        this.sprintHeader.updateDateEndNode(this.dateEnd);
	      }
	    }
	  }, {
	    key: "getDateEnd",
	    value: function getDateEnd() {
	      return parseInt(this.dateEnd, 10);
	    }
	  }, {
	    key: "setWeekendDaysTime",
	    value: function setWeekendDaysTime(weekendDaysTime) {
	      this.weekendDaysTime = main_core.Type.isInteger(weekendDaysTime) ? parseInt(weekendDaysTime, 10) : 0;
	    }
	  }, {
	    key: "getWeekendDaysTime",
	    value: function getWeekendDaysTime() {
	      return this.weekendDaysTime;
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.totalStoryPoints;
	    }
	  }, {
	    key: "setTotalStoryPoints",
	    value: function setTotalStoryPoints(totalStoryPoints) {
	      this.totalStoryPoints = new StoryPoints();
	      this.totalStoryPoints.addPoints(totalStoryPoints);
	    }
	  }, {
	    key: "getTotalStoryPoints",
	    value: function getTotalStoryPoints() {
	      return this.totalStoryPoints;
	    }
	  }, {
	    key: "setTotalCompletedStoryPoints",
	    value: function setTotalCompletedStoryPoints(totalCompletedStoryPoints) {
	      this.totalCompletedStoryPoints = new StoryPoints();
	      this.totalCompletedStoryPoints.addPoints(totalCompletedStoryPoints);
	    }
	  }, {
	    key: "getTotalCompletedStoryPoints",
	    value: function getTotalCompletedStoryPoints() {
	      return this.totalCompletedStoryPoints;
	    }
	  }, {
	    key: "setTotalUncompletedStoryPoints",
	    value: function setTotalUncompletedStoryPoints(totalUncompletedStoryPoints) {
	      this.totalUncompletedStoryPoints = new StoryPoints();
	      this.totalUncompletedStoryPoints.addPoints(totalUncompletedStoryPoints);
	    }
	  }, {
	    key: "getTotalUncompletedStoryPoints",
	    value: function getTotalUncompletedStoryPoints() {
	      return this.totalUncompletedStoryPoints;
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      var _this4 = this;

	      if (!main_core.Type.isArray(items)) {
	        return;
	      }

	      items.forEach(function (itemParams) {
	        var item = new Item(itemParams);
	        item.setDisableStatus(_this4.isDisabled());

	        _this4.items.set(item.itemId, item);
	      });
	    }
	  }, {
	    key: "setInfo",
	    value: function setInfo(info) {
	      this.info = main_core.Type.isPlainObject(info) ? info : {
	        sprintGoal: ''
	      };
	    }
	  }, {
	    key: "addNumberTasks",
	    value: function addNumberTasks(value) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "addNumberTasks", this).call(this, value);

	      if (this.storyPointsHeader) {
	        this.storyPointsHeader.updateNumberTasks();
	      }
	    }
	  }, {
	    key: "subtractNumberTasks",
	    value: function subtractNumberTasks(value) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "subtractNumberTasks", this).call(this, value);

	      if (this.storyPointsHeader) {
	        this.storyPointsHeader.updateNumberTasks();
	      }
	    }
	  }, {
	    key: "setCompletedTasks",
	    value: function setCompletedTasks(completedTasks) {
	      this.completedTasks = main_core.Type.isInteger(completedTasks) ? parseInt(completedTasks, 10) : 0;
	    }
	  }, {
	    key: "getCompletedTasks",
	    value: function getCompletedTasks() {
	      return this.completedTasks;
	    }
	  }, {
	    key: "setUncompletedTasks",
	    value: function setUncompletedTasks(uncompletedTasks) {
	      this.uncompletedTasks = main_core.Type.isInteger(uncompletedTasks) ? parseInt(uncompletedTasks, 10) : 0;
	    }
	  }, {
	    key: "getUncompletedTasks",
	    value: function getUncompletedTasks() {
	      return this.uncompletedTasks;
	    }
	  }, {
	    key: "setDefaultSprintDuration",
	    value: function setDefaultSprintDuration(defaultSprintDuration) {
	      this.defaultSprintDuration = main_core.Type.isInteger(defaultSprintDuration) ? parseInt(defaultSprintDuration, 10) : 0;
	    }
	  }, {
	    key: "getDefaultSprintDuration",
	    value: function getDefaultSprintDuration() {
	      return this.defaultSprintDuration;
	    }
	  }, {
	    key: "getInfo",
	    value: function getInfo() {
	      return this.info;
	    }
	  }, {
	    key: "getEpics",
	    value: function getEpics() {
	      var epics = new Map();
	      this.items.forEach(function (item) {
	        //todo wtf, why did not Set work?
	        if (item.getEpic()) {
	          epics.set(item.getEpic().id, item.getEpic());
	        }
	      });
	      return epics;
	    }
	  }, {
	    key: "getUncompletedItems",
	    value: function getUncompletedItems() {
	      var items = new Map();
	      this.items.forEach(function (item) {
	        if (!item.isCompleted()) {
	          items.set(item.getItemId(), item);
	        }
	      });
	      return items;
	    }
	  }, {
	    key: "setStatus",
	    value: function setStatus(status) {
	      var _this5 = this;

	      var availableStatus = new Set(['planned', 'active', 'completed']);
	      this.status = availableStatus.has(status) ? status : 'planned';
	      this.initStyle();
	      this.items.forEach(function (item) {
	        item.setDisableStatus(_this5.isDisabled());
	      });

	      if (this.isDisabled()) {
	        if (this.input) {
	          this.input.removeYourself();
	        }

	        if (this.groupActionsButton) {
	          this.groupActionsButton.removeYourself();
	        }
	      }
	    }
	  }, {
	    key: "getStatus",
	    value: function getStatus() {
	      return this.status;
	    }
	  }, {
	    key: "updateYourself",
	    value: function updateYourself(tmpSprint) {
	      if (tmpSprint.getName() !== this.getName()) {
	        this.setName(tmpSprint.getName());
	      }

	      if (tmpSprint.getDateStart() !== this.getDateStart()) {
	        this.setDateStart(tmpSprint.getDateStart());
	      }

	      if (tmpSprint.getDateEnd() !== this.getDateEnd()) {
	        this.setDateEnd(tmpSprint.getDateEnd());
	      }

	      this.setTotalStoryPoints(tmpSprint.getTotalStoryPoints().getPoints());
	      this.setTotalCompletedStoryPoints(tmpSprint.getTotalCompletedStoryPoints().getPoints());
	      this.setTotalUncompletedStoryPoints(tmpSprint.getTotalUncompletedStoryPoints().getPoints());

	      if (tmpSprint.getStatus() !== this.getStatus()) {
	        this.setStatus(tmpSprint.getStatus());
	      }

	      if (this.node) {
	        this.addStoryPointsHeader(new StoryPointsHeader(this));
	        main_core.Dom.replace(this.node.querySelector('.tasks-scrum-sprint-header-event-params'), this.renderParams());
	      }
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	      this.emit('removeSprint');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$j(), this.sort, this.getId(), this.sprintHeader ? this.sprintHeader.render() : '', this.eventsHeader ? '' : ''
	      /*todo*/
	      , this.isCompleted() ? this.renderLinkToCompletedSprint() : '', this.renderParams(), this.groupActionsButton && !this.isDisabled() ? this.groupActionsButton.render() : '', this.listItems ? this.listItems.render() : '');
	      return this.node;
	    }
	  }, {
	    key: "renderParams",
	    value: function renderParams() {
	      return main_core.Tag.render(_templateObject2$6(), this.storyPointsHeader ? this.storyPointsHeader.render() : '');
	    }
	  }, {
	    key: "renderLinkToCompletedSprint",
	    value: function renderLinkToCompletedSprint() {
	      return main_core.Tag.render(_templateObject3$5(), main_core.Text.encode(this.getViews().completedSprint.url), main_core.Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_LINK'));
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      this.updateVisibility();

	      if (this.sprintHeader) {
	        this.sprintHeader.onAfterAppend();
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "onAfterAppend", this).call(this);
	    }
	  }, {
	    key: "updateStoryPoints",
	    value: function updateStoryPoints() {
	      var _this6 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "updateStoryPoints", this).call(this);

	      if (!this.isCompleted()) {
	        this.totalStoryPoints.clearPoints();
	        babelHelpers.toConsumableArray(this.getItems().values()).map(function (item) {
	          _this6.totalStoryPoints.addPoints(item.getStoryPoints().getPoints());
	        });
	        this.totalCompletedStoryPoints.clearPoints();
	        babelHelpers.toConsumableArray(this.getItems().values()).map(function (item) {
	          if (item.isCompleted()) {
	            _this6.totalCompletedStoryPoints.addPoints(item.getStoryPoints().getPoints());
	          }
	        });
	        this.totalUncompletedStoryPoints.clearPoints();
	        babelHelpers.toConsumableArray(this.getItems().values()).map(function (item) {
	          if (!item.isCompleted()) {
	            _this6.totalUncompletedStoryPoints.addPoints(item.getStoryPoints().getPoints());
	          }
	        });
	      }

	      if (this.storyPointsHeader) {
	        if (this.isActive()) {
	          this.updateActiveSprintStoryPointsHeader();
	        } else if (this.isCompleted()) {
	          this.storyPointsHeader.setStoryPoints(this.getTotalStoryPoints().getPoints());
	          this.storyPointsHeader.setCompletedStoryPoints(this.getTotalCompletedStoryPoints().getPoints());
	        } else if (this.isPlanned()) {
	          this.storyPointsHeader.setStoryPoints(this.storyPoints.getPoints());
	        }
	      }

	      if (this.sprintHeader) {
	        this.sprintHeader.updateStatsHeader();
	      }
	    }
	  }, {
	    key: "addTotalStoryPoints",
	    value: function addTotalStoryPoints(item) {
	      var itemStoryPoints = item.getStoryPoints().getPoints();
	      this.getTotalStoryPoints().addPoints(itemStoryPoints);

	      if (item.isCompleted()) {
	        this.getTotalCompletedStoryPoints().addPoints(itemStoryPoints);
	      } else {
	        this.getTotalUncompletedStoryPoints().addPoints(itemStoryPoints);
	      }
	    }
	  }, {
	    key: "subtractTotalStoryPoints",
	    value: function subtractTotalStoryPoints(item) {
	      var itemStoryPoints = item.getStoryPoints().getPoints();
	      this.getTotalStoryPoints().subtractPoints(itemStoryPoints);

	      if (item.isCompleted()) {
	        this.getTotalCompletedStoryPoints().subtractPoints(itemStoryPoints);
	      } else {
	        this.getTotalUncompletedStoryPoints().subtractPoints(itemStoryPoints);
	      }
	    }
	  }, {
	    key: "updateActiveSprintStoryPointsHeader",
	    value: function updateActiveSprintStoryPointsHeader() {
	      if (this.storyPointsHeader) {
	        this.storyPointsHeader.setStoryPoints(this.getTotalStoryPoints().getPoints());
	        this.storyPointsHeader.setCompletedStoryPoints(this.getTotalCompletedStoryPoints().getPoints());
	        this.storyPointsHeader.setUncompletedStoryPoints(this.getTotalUncompletedStoryPoints().getPoints());
	      }
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this7 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "subscribeToItem", this).call(this, item);
	      item.subscribe('moveToBacklog', function (baseEvent) {
	        _this7.emit('moveToBacklog', {
	          sprint: _this7,
	          item: baseEvent.getTarget()
	        });
	      });
	      item.subscribe('updateCompletedStatus', function (baseEvent) {
	        babelHelpers.toConsumableArray(_this7.getItems().values()).map(function (item) {
	          if (item.isCompleted()) {
	            _this7.setCompletedTasks(_this7.getCompletedTasks() + 1);
	          } else {
	            _this7.setUncompletedTasks(_this7.getUncompletedTasks() - 1);
	          }
	        });
	      });
	      item.subscribe('toggleSubTasks', function (baseEvent) {
	        _this7.emit('toggleSubTasks', baseEvent.getTarget());
	      });
	    }
	  }, {
	    key: "onChangeName",
	    value: function onChangeName(baseEvent) {
	      var _this8 = this;

	      var createInput = function createInput(value) {
	        return main_core.Tag.render(_templateObject4$4(), main_core.Text.encode(value));
	      };

	      var inputNode = createInput(this.name);
	      var nameNode = baseEvent.getData().querySelector('.tasks-scrum-sprint-header-name');
	      main_core.Event.bind(inputNode, 'change', function (event) {
	        var newValue = event.target['value'];

	        _this8.emit('changeSprintName', {
	          sprintId: _this8.getId(),
	          name: newValue
	        });

	        _this8.name = newValue;
	        inputNode.blur();
	      }, true);

	      var blockEnterInput = function blockEnterInput(event) {
	        if (event.isComposing || event.keyCode === 13) inputNode.blur();
	      };

	      main_core.Event.bind(inputNode, 'keydown', blockEnterInput);
	      main_core.Event.bindOnce(inputNode, 'blur', function () {
	        main_core.Event.unbind(inputNode, 'keydown', blockEnterInput);
	        nameNode.textContent = main_core.Text.encode(_this8.name);
	        main_core.Dom.replace(inputNode, nameNode);
	      }, true);
	      main_core.Dom.replace(nameNode, inputNode);
	      inputNode.focus();
	      inputNode.setSelectionRange(this.name.length, this.name.length);
	    }
	  }, {
	    key: "onRemoveSprint",
	    value: function onRemoveSprint() {
	      var _this9 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this9.emit('moveToBacklog', {
	          sprint: _this9,
	          item: item
	        });
	      });
	      this.removeYourself();
	    }
	  }, {
	    key: "removeSprint",
	    value: function removeSprint() {
	      var _this10 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this10.emit('moveToBacklog', {
	          sprint: _this10,
	          item: item
	        });
	      });
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "onChangeSprintDeadline",
	    value: function onChangeSprintDeadline(baseEvent) {
	      var requestData = baseEvent.getData();
	      this.emit('changeSprintDeadline', requestData);

	      if (requestData.hasOwnProperty('dateStart')) {
	        this.dateStart = parseInt(requestData.dateStart, 10);
	      } else if (requestData.hasOwnProperty('dateEnd')) {
	        this.dateEnd = parseInt(requestData.dateEnd, 10);
	      }
	    }
	  }, {
	    key: "updateDateStartNode",
	    value: function updateDateStartNode(timestamp) {
	      if (this.sprintHeader) {
	        this.sprintHeader.updateDateStartNode(timestamp);
	      }
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      if (this.sprintHeader) {
	        this.sprintHeader.updateDateEndNode(timestamp);
	      }
	    }
	  }, {
	    key: "toggleVisibilityContent",
	    value: function toggleVisibilityContent() {
	      if (this.getContentNode().style.display === 'block') {
	        this.hideContent();
	      } else {
	        this.showContent();

	        if (this.isCompleted() && this.getItems().size === 0) {
	          this.emit('getSprintCompletedItems');
	        }
	      }
	    }
	  }, {
	    key: "showContent",
	    value: function showContent() {
	      if (this.getContentNode()) {
	        this.getContentNode().style.display = 'block';
	      }
	    }
	  }, {
	    key: "hideContent",
	    value: function hideContent() {
	      if (this.getContentNode()) {
	        this.getContentNode().style.display = 'none';
	      }
	    }
	  }, {
	    key: "updateVisibility",
	    value: function updateVisibility() {
	      if (this.isCompleted()) {
	        if (this.isEmpty()) {
	          this.hideContent();
	        } else {
	          this.showContent();
	        }

	        this.showSprint();

	        if (this.isExactSearchApplied()) {
	          if (this.isEmpty()) {
	            this.hideSprint();
	          }
	        }
	      } else {
	        this.showContent();

	        if (this.isExactSearchApplied()) {
	          if (this.isEmpty()) {
	            this.hideSprint();
	          } else {
	            this.showSprint();
	          }
	        } else {
	          this.showSprint();
	        }
	      }
	    }
	  }, {
	    key: "showSprint",
	    value: function showSprint() {
	      this.node.style.display = 'block';
	    }
	  }, {
	    key: "hideSprint",
	    value: function hideSprint() {
	      this.node.style.display = 'none';
	    }
	  }, {
	    key: "onActivateGroupMode",
	    value: function onActivateGroupMode(baseEvent) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "onActivateGroupMode", this).call(this, baseEvent);
	      main_core.Dom.addClass(this.node.querySelector('.tasks-scrum-sprint-items'), 'tasks-scrum-sprint-items-group-mode');
	    }
	  }, {
	    key: "onDeactivateGroupMode",
	    value: function onDeactivateGroupMode(baseEvent) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "onDeactivateGroupMode", this).call(this, baseEvent);
	      main_core.Dom.removeClass(this.node.querySelector('.tasks-scrum-sprint-items'), 'tasks-scrum-sprint-items-group-mode');
	    }
	  }, {
	    key: "getContentNode",
	    value: function getContentNode() {
	      if (this.node) {
	        return this.node.querySelector('.tasks-scrum-sprint-content');
	      }
	    }
	  }], [{
	    key: "buildSprint",
	    value: function buildSprint() {
	      var sprintData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var sprint = new Sprint(sprintData);
	      sprint.addSprintHeader(SprintHeader.buildHeader(sprint));
	      sprint.addEventsHeader(new EventsHeader());
	      sprint.addStoryPointsHeader(new StoryPointsHeader(sprint));
	      sprint.addGroupActionsButton(new GroupActionsButton());
	      sprint.addListItems(new ListItems(sprint));
	      return sprint;
	    }
	  }]);
	  return Sprint;
	}(Entity);

	var SidePanel = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SidePanel, _EventEmitter);

	  function SidePanel() {
	    var _this;

	    babelHelpers.classCallCheck(this, SidePanel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SidePanel).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.SidePanel');
	    /* eslint-disable */


	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.bindEvents();

	    return _this;
	  }

	  babelHelpers.createClass(SidePanel, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onLoad', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	            _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	            sliderEvent = _event$getCompatData2[0];

	        var sidePanel = sliderEvent.getSlider();
	        sidePanel.setCacheable(false);

	        _this2.emit('onLoadSidePanel', sidePanel);
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function (event) {
	        var _event$getCompatData3 = event.getCompatData(),
	            _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	            sliderEvent = _event$getCompatData4[0];

	        var sidePanel = sliderEvent.getSlider();

	        _this2.emit('onCloseSidePanel', sidePanel);
	      });
	    }
	  }, {
	    key: "isPreviousSidePanelExist",
	    value: function isPreviousSidePanelExist(currentSidePanel) {
	      return Boolean(this.sidePanelManager.getPreviousSlider(currentSidePanel));
	    }
	  }, {
	    key: "reloadTopSidePanel",
	    value: function reloadTopSidePanel() {
	      if (this.sidePanelManager.getTopSlider()) {
	        this.sidePanelManager.getTopSlider().reload();
	      }
	    }
	  }, {
	    key: "closeTopSidePanel",
	    value: function closeTopSidePanel() {
	      if (this.sidePanelManager.getTopSlider()) {
	        this.sidePanelManager.getTopSlider().close();
	      }
	    }
	  }, {
	    key: "reloadPreviousSidePanel",
	    value: function reloadPreviousSidePanel(currentSidePanel) {
	      var previousSidePanel = this.sidePanelManager.getPreviousSlider(currentSidePanel);
	      previousSidePanel.reload();
	    }
	  }, {
	    key: "openSidePanelByUrl",
	    value: function openSidePanelByUrl(url) {
	      this.sidePanelManager.open(url);
	    }
	  }, {
	    key: "openSidePanel",
	    value: function openSidePanel(id, options) {
	      this.sidePanelManager.open(id, options);
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

	//todo import amchart4 like es6
	var BurnDownChart = /*#__PURE__*/function () {
	  function BurnDownChart(data) {
	    babelHelpers.classCallCheck(this, BurnDownChart);
	    this.data = data;
	  }

	  babelHelpers.createClass(BurnDownChart, [{
	    key: "createChart",
	    value: function createChart(chartDiv) {
	      am4core.useTheme(am4themes_animated);
	      this.chart = am4core.create(chartDiv, am4charts.XYChart);
	      this.chart.data = this.data;
	      this.chart.paddingRight = 40;
	      this.createAxises();
	      this.createIdealLine();
	      this.createRemainLine();
	      this.createLegend();
	    }
	  }, {
	    key: "createAxises",
	    value: function createAxises() {
	      var categoryAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
	      categoryAxis.renderer.grid.template.location = 0;
	      categoryAxis.dataFields.category = 'day';
	      categoryAxis.renderer.minGridDistance = 60;
	      var valueAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
	      valueAxis.min = -0.1;
	    }
	  }, {
	    key: "createIdealLine",
	    value: function createIdealLine() {
	      var lineSeries = this.chart.series.push(new am4charts.LineSeries());
	      lineSeries.name = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_IDEAL_BURN_DOWN_CHART_LINE_TITLE'); //todo move from class

	      lineSeries.stroke = am4core.color('#2882b3');
	      lineSeries.strokeWidth = 2;
	      lineSeries.dataFields.categoryX = 'day';
	      lineSeries.dataFields.valueY = 'idealValue';
	      var circleColor = '#2882b3';
	      var circleBullet = new am4charts.CircleBullet();
	      circleBullet.circle.radius = 4;
	      circleBullet.circle.fill = am4core.color(circleColor);
	      circleBullet.circle.stroke = am4core.color(circleColor);
	      lineSeries.bullets.push(circleBullet);
	      var segment = lineSeries.segments.template;
	      var hoverState = segment.states.create('hover');
	      hoverState.properties.strokeWidth = 4;
	    }
	  }, {
	    key: "createRemainLine",
	    value: function createRemainLine() {
	      var lineSeries = this.chart.series.push(new am4charts.LineSeries());
	      lineSeries.name = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_REMAIN_BURN_DOWN_CHART_LINE_TITLE'); //todo move from class

	      lineSeries.stroke = am4core.color('#9c1f1f');
	      lineSeries.strokeWidth = 2;
	      lineSeries.dataFields.categoryX = 'day';
	      lineSeries.dataFields.valueY = 'remainValue';
	      var circleColor = '#9c1f1f';
	      var circleBullet = new am4charts.CircleBullet();
	      circleBullet.circle.radius = 4;
	      circleBullet.circle.fill = am4core.color(circleColor);
	      circleBullet.circle.stroke = am4core.color(circleColor);
	      lineSeries.bullets.push(circleBullet);
	      var segment = lineSeries.segments.template;
	      var hoverState = segment.states.create('hover');
	      hoverState.properties.strokeWidth = 4;
	    }
	  }, {
	    key: "createLegend",
	    value: function createLegend() {
	      var _this = this;

	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.itemContainers.template.clickable = false;
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.itemContainers.template.events.on('over', function (event) {
	        _this.processOver(event.target.dataItem.dataContext);
	      });
	      this.chart.legend.itemContainers.template.events.on('out', function () {
	        return _this.processOut();
	      });
	    }
	  }, {
	    key: "processOver",
	    value: function processOver(hoveredLine) {
	      hoveredLine.toFront();
	      hoveredLine.segments.each(function (segment) {
	        return segment.setState('hover');
	      });
	    }
	  }, {
	    key: "processOut",
	    value: function processOut() {
	      this.chart.series.each(function (series) {
	        series.segments.each(function (segment) {
	          return segment.setState('default');
	        });
	        series.bulletsContainer.setState('default');
	      });
	    }
	  }, {
	    key: "destroyBurnDownChart",
	    value: function destroyBurnDownChart() {
	      this.chart.dispose();
	    }
	  }]);
	  return BurnDownChart;
	}();

	function _templateObject8$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-block\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-box tasks-scrum-sprint-sidepanel-info-box-w100\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-content\">\n\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-value\">\n\t\t\t\t\t\t\t\t", "%\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-dif ", "\">\n\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-dif-arrow\"></span>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-dif-block\">\n\t\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-dif-val\">", "</span>\n\t\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-dif-icon\">%</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject8$2 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-speed\">\n\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-subtitle\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-result\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-graph\"></div>\n\t\t\t"]);

	  _templateObject7$2 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-block\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-title tasks-scrum-sprint-sidepanel-title-icon\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-field\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-label\">\n\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t\t<option value=\"backlog\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t<option value=\"0\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-uncompleted\">\n\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-subtitle\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-uncompleted-list\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6$3 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-block\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t </div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$4 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-chart\"></div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$5 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$6 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-epic-item\" style=\"background: ", ";\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t"]);

	  _templateObject2$7 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$k() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-sidepanel\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-block\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-content\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-box\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-content\">\n\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-value\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-box tasks-scrum-sprint-sidepanel-info-box-story\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-info-content\">\n\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-value\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span class=\"tasks-scrum-sprint-sidepanel-info-extra\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-epic\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-epic-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-epic-list\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-block\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textarea ui-ctl-resize-y\">\n\t\t\t\t\t\t\t<textarea class=\"ui-ctl-element\"></textarea>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-sidepanel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$k = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var SprintSidePanel = /*#__PURE__*/function () {
	  function SprintSidePanel(params) {
	    babelHelpers.classCallCheck(this, SprintSidePanel);
	    this.sprints = params.sprints ? params.sprints : new Map();
	    this.sidePanel = params.sidePanel;
	    this.requestSender = params.requestSender;
	    this.views = params.views;
	    this.currentSprint = null;
	    this.uncompletedItems = new Map();
	    this.lastCompletedSprint = this.getLastCompletedSprint(this.sprints);
	  }

	  babelHelpers.createClass(SprintSidePanel, [{
	    key: "showStartSidePanel",
	    value: function showStartSidePanel(sprint) {
	      var _this = this;

	      this.sidePanelId = 'tasks-scrum-start-' + main_core.Text.getRandom();
	      this.currentSprint = sprint;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadStartPanel.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this.buildStartPanel());
	          });
	        },
	        zIndex: 1000,
	        width: 600
	      });
	    }
	  }, {
	    key: "showCompleteSidePanel",
	    value: function showCompleteSidePanel(sprint) {
	      var _this2 = this;

	      this.sidePanelId = 'tasks-scrum-start-' + main_core.Text.getRandom();
	      this.currentSprint = sprint;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadCompletePanel.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this2.buildCompletePanel());
	          });
	        },
	        zIndex: 1000,
	        width: 600
	      });
	    }
	  }, {
	    key: "showBurnDownChart",
	    value: function showBurnDownChart(sprint) {
	      var _this3 = this;

	      this.sidePanelId = 'tasks-scrum-burn-down-chart-' + main_core.Text.getRandom();
	      this.currentSprint = sprint;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadSprintBurnDownPanel.bind(this));
	      this.sidePanel.subscribe('onCloseSidePanel', this.onCloseBurnDownChart.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this3.buildBurnDownPanel());
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "buildStartPanel",
	    value: function buildStartPanel() {
	      var differenceStoryPoints = this.currentSprint.getTotalStoryPoints().getPoints();
	      differenceStoryPoints = differenceStoryPoints > 0 ? '+' + differenceStoryPoints : differenceStoryPoints;

	      if (this.lastCompletedSprint) {
	        differenceStoryPoints = this.getDifferenceStoryPointsBetweenSprints(this.currentSprint, this.lastCompletedSprint);
	      }

	      return main_core.Tag.render(_templateObject$k(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_HEADER'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_TASK_COUNT_TITLE'), parseInt(this.currentSprint.getNumberTasks()), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_STORY_POINTS_COUNT_TITLE'), main_core.Text.encode(this.currentSprint.getTotalStoryPoints().getPoints()), differenceStoryPoints, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_EPICS_TITLE'), babelHelpers.toConsumableArray(this.currentSprint.getEpics().values()).map(function (epic) {
	        return main_core.Tag.render(_templateObject2$7(), main_core.Text.encode(epic.info.color), main_core.Text.encode(epic.name));
	      }), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_GOAL_TITLE'));
	    }
	  }, {
	    key: "onLoadStartPanel",
	    value: function onLoadStartPanel(baseEvent) {
	      var _this4 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-sprint-sidepanel');
	      this.onLoadStartButtons().then(function (buttonsContainer) {
	        main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', _this4.onStartSprint.bind(_this4));
	      });
	    }
	  }, {
	    key: "buildCompletePanel",
	    value: function buildCompletePanel() {
	      return main_core.Tag.render(_templateObject3$6(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_HEADER'), this.buildSprintGoal(), this.buildSprintActions(), this.buildSprintPlan());
	    }
	  }, {
	    key: "buildBurnDownPanel",
	    value: function buildBurnDownPanel() {
	      return main_core.Tag.render(_templateObject4$5(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_IDEAL_BURN_DOWN_CHART_HEADER'));
	    }
	  }, {
	    key: "buildSprintGoal",
	    value: function buildSprintGoal() {
	      var sprintInfo = this.currentSprint.getInfo();
	      var sprintGoal = sprintInfo.sprintGoal;

	      if (!sprintGoal) {
	        return '';
	      }

	      return main_core.Tag.render(_templateObject5$4(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_GOAL_TITLE'), main_core.Text.encode(sprintGoal));
	    }
	  }, {
	    key: "buildSprintActions",
	    value: function buildSprintActions() {
	      var _this5 = this;

	      var uncompletedTasks = this.currentSprint.getUncompletedTasks();

	      if (uncompletedTasks === 0) {
	        return '';
	      }

	      var listSprintsOptions = '';
	      this.sprints.forEach(function (sprint) {
	        if (sprint.isPlanned()) {
	          listSprintsOptions += "<option value=\"".concat(sprint.getId(), "\">").concat(main_core.Text.encode(sprint.getName()), "</option>");
	        }
	      });
	      return main_core.Tag.render(_templateObject6$3(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_SELECT'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_BACKLOG'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_NEW_SPRINT'), listSprintsOptions, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_ITEM_LIST'), babelHelpers.toConsumableArray(this.currentSprint.getUncompletedItems().values()).map(function (item) {
	        var previewItem = item.getPreviewVersion();

	        _this5.uncompletedItems.set(previewItem.getItemId(), previewItem);

	        return previewItem.render();
	      }));
	    }
	  }, {
	    key: "buildSprintPlan",
	    value: function buildSprintPlan() {
	      var statsCalculator = new StatsCalculator();
	      var percentage = statsCalculator.calculatePercentage(this.currentSprint.getTotalStoryPoints().getPoints(), this.currentSprint.getTotalCompletedStoryPoints().getPoints());
	      var differencePercentage = statsCalculator.calculatePercentage(this.currentSprint.getTotalStoryPoints().getPoints(), this.currentSprint.getTotalCompletedStoryPoints().getPoints());

	      if (this.lastCompletedSprint) {
	        differencePercentage = this.getDifferencePercentageBetweenSprints(this.currentSprint, this.lastCompletedSprint);
	      }

	      var percentageNodeClass = differencePercentage > 0 ? '' : 'tasks-scrum-sprint-sidepanel-info-dif-min';
	      var absoluteValue = Math.abs(differencePercentage);
	      var speedInfoMessage = differencePercentage > 0 ? main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED_UP').replace('#value#', absoluteValue) : main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED_DOWN').replace('#value#', absoluteValue);

	      var renderSpeed = function renderSpeed(speedInfoMessage) {
	        return ''; //todo chart

	        return main_core.Tag.render(_templateObject7$2(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED'), speedInfoMessage);
	      };

	      return main_core.Tag.render(_templateObject8$2(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_PLAN_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_PLAN_DONE'), percentage, percentageNodeClass, absoluteValue, renderSpeed());
	    }
	  }, {
	    key: "onLoadCompletePanel",
	    value: function onLoadCompletePanel(baseEvent) {
	      var _this6 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-sprint-sidepanel');
	      var itemsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-uncompleted-list');
	      babelHelpers.toConsumableArray(this.uncompletedItems.values()).map(function (previewItem) {
	        previewItem.onAfterAppend(itemsContainer);
	        previewItem.subscribe('showTask', function () {
	          _this6.currentSprint.emit('showTask', previewItem);
	        });
	      });
	      this.onLoadCompleteButtons().then(function (buttonsContainer) {
	        main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', _this6.onCompleteSprint.bind(_this6, sidePanel));
	      });
	    }
	  }, {
	    key: "onLoadSprintBurnDownPanel",
	    value: function onLoadSprintBurnDownPanel(baseEvent) {
	      var _this7 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-sprint-sidepanel');
	      this.getBurnDownChartData().then(function (data) {
	        setTimeout(function () {
	          _this7.burnDownChart = new BurnDownChart(data);

	          _this7.burnDownChart.createChart(_this7.form.querySelector('.tasks-scrum-sprint-sidepanel-chart'));
	        }, 300);
	      });
	    }
	  }, {
	    key: "onCloseBurnDownChart",
	    value: function onCloseBurnDownChart(baseEvent) {
	      var _this8 = this;

	      var sidePanel = baseEvent.getData();

	      if (this.sidePanelId === sidePanel.getUrl()) {
	        setTimeout(function () {
	          _this8.burnDownChart.destroyBurnDownChart();

	          _this8.burnDownChart = null;
	        }, 300);
	      }
	    }
	  }, {
	    key: "getTasksCountLabel",
	    value: function getTasksCountLabel(count) {
	      if (count > 5) {
	        return count + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_TASK_LABEL_3');
	      } else if (count === 1) {
	        return count + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_TASK_LABEL_1');
	      } else {
	        return count + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_TASK_LABEL_2');
	      }
	    }
	  }, {
	    key: "getLastCompletedSprint",
	    value: function getLastCompletedSprint(sprints) {
	      return babelHelpers.toConsumableArray(sprints.values()).find(function (sprint) {
	        return sprint.isCompleted() === true;
	      });
	    }
	  }, {
	    key: "getDifferenceStoryPointsBetweenSprints",
	    value: function getDifferenceStoryPointsBetweenSprints(firstSprint, secondSprint) {
	      var difference = parseFloat(firstSprint.getTotalStoryPoints().getPoints() - secondSprint.getTotalStoryPoints().getPoints());

	      if (main_core.Type.isFloat(difference)) {
	        difference = difference.toFixed(1);
	      }

	      if (difference === 0) {
	        return '';
	      } else {
	        return difference > 0 ? '+' + difference : difference;
	      }
	    }
	  }, {
	    key: "getDifferencePercentageBetweenSprints",
	    value: function getDifferencePercentageBetweenSprints(firstSprint, secondSprint) {
	      var statsCalculator = new StatsCalculator();
	      var firstPercentage = statsCalculator.calculatePercentage(firstSprint.getTotalStoryPoints().getPoints(), firstSprint.getTotalCompletedStoryPoints().getPoints());
	      var secondPercentage = statsCalculator.calculatePercentage(secondSprint.getTotalStoryPoints().getPoints(), secondSprint.getTotalCompletedStoryPoints().getPoints());
	      return parseFloat(firstPercentage) - parseFloat(secondPercentage);
	    }
	  }, {
	    key: "getStartButtons",
	    value: function getStartButtons() {
	      var _this9 = this;

	      return new Promise(function (resolve, reject) {
	        _this9.requestSender.getSprintStartButtons().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getCompleteButtons",
	    value: function getCompleteButtons() {
	      var _this10 = this;

	      return new Promise(function (resolve, reject) {
	        _this10.requestSender.getSprintCompleteButtons().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getBurnDownChartData",
	    value: function getBurnDownChartData() {
	      var _this11 = this;

	      return new Promise(function (resolve, reject) {
	        _this11.requestSender.getBurnDownChartData(_this11.getRequestDataToGetBurnDownChartData()).then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "onLoadStartButtons",
	    value: function onLoadStartButtons() {
	      var _this12 = this;

	      return this.getStartButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this12.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadCompleteButtons",
	    value: function onLoadCompleteButtons() {
	      var _this13 = this;

	      return this.getCompleteButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this13.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onStartSprint",
	    value: function onStartSprint() {
	      var _this14 = this;

	      this.requestSender.startSprint(this.getRequestDataToStartSprint()).then(function (response) {
	        _this14.currentSprint.setStatus('active');

	        location.href = _this14.views['activeSprint'].url;
	      }).catch(function (response) {
	        _this14.removeClockIconFromButton();

	        _this14.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
	      });
	    }
	  }, {
	    key: "onCompleteSprint",
	    value: function onCompleteSprint(sidePanel) {
	      var _this15 = this;

	      this.requestSender.completeSprint(this.getRequestDataToCompleteSprint()).then(function (response) {
	        if (ui_confetti.Confetti) {
	          ui_confetti.Confetti.fire({
	            particleCount: 400,
	            spread: 80,
	            origin: {
	              x: 0.7,
	              y: 0.2
	            },
	            zIndex: sidePanel.getZindex() + 1
	          }).then(function () {
	            location.href = _this15.views['plan'].url;
	          });
	        } else {
	          location.href = _this15.views['plan'].url;
	        }
	      }).catch(function (response) {
	        _this15.removeClockIconFromButton();

	        _this15.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
	      });
	    }
	  }, {
	    key: "getRequestDataToStartSprint",
	    value: function getRequestDataToStartSprint() {
	      var requestData = {};
	      requestData.sprintId = this.currentSprint.getId();
	      requestData.sprintGoal = this.form.querySelector('textarea').value;
	      return requestData;
	    }
	  }, {
	    key: "getRequestDataToCompleteSprint",
	    value: function getRequestDataToCompleteSprint() {
	      var requestData = {};
	      requestData.sprintId = this.currentSprint.getId();
	      var directionSelectNode = this.form.querySelector('select');
	      requestData.direction = directionSelectNode ? directionSelectNode.value : 'backlog';
	      return requestData;
	    }
	  }, {
	    key: "getRequestDataToGetBurnDownChartData",
	    value: function getRequestDataToGetBurnDownChartData() {
	      var requestData = {};
	      requestData.sprintId = this.currentSprint.getId();
	      return requestData;
	    }
	  }, {
	    key: "removeClockIconFromButton",
	    value: function removeClockIconFromButton() {
	      var buttonsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');

	      if (buttonsContainer) {
	        main_core.Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
	      }
	    }
	  }]);
	  return SprintSidePanel;
	}();

	var EntityStorage = /*#__PURE__*/function () {
	  function EntityStorage() {
	    babelHelpers.classCallCheck(this, EntityStorage);
	    this.backlog = null;
	    this.sprints = new Map();
	  }

	  babelHelpers.createClass(EntityStorage, [{
	    key: "addBacklog",
	    value: function addBacklog(backlog) {
	      if (!(backlog instanceof Backlog)) {
	        throw new Error('EntityStorage: Backlog is in wrong format');
	      }

	      this.backlog = backlog;
	    }
	  }, {
	    key: "addSprint",
	    value: function addSprint(sprint) {
	      this.sprints.set(sprint.getId(), sprint);
	    }
	  }, {
	    key: "removeSprint",
	    value: function removeSprint(sprintId) {
	      this.sprints.delete(sprintId);
	    }
	  }, {
	    key: "getBacklog",
	    value: function getBacklog() {
	      if (this.backlog === null) {
	        throw new Error('EntityStorage: Backlog not found');
	      }

	      return this.backlog;
	    }
	  }, {
	    key: "getSprints",
	    value: function getSprints() {
	      return this.sprints;
	    }
	  }, {
	    key: "getSprintsAvailableForFilling",
	    value: function getSprintsAvailableForFilling(entityFrom) {
	      var sprints = new Set();
	      this.sprints.forEach(function (sprint) {
	        if (!sprint.isCompleted() && entityFrom.getId() !== sprint.getId()) {
	          sprints.add(sprint);
	        }
	      });
	      return sprints;
	    }
	  }, {
	    key: "getAllEntities",
	    value: function getAllEntities() {
	      var entities = new Map();
	      entities.set(this.backlog.getId(), this.backlog);
	      babelHelpers.toConsumableArray(this.sprints.values()).map(function (sprint) {
	        return entities.set(sprint.getId(), sprint);
	      });
	      return entities;
	    }
	  }, {
	    key: "getAllItems",
	    value: function getAllItems() {
	      var items = new Map(this.backlog.getItems());
	      babelHelpers.toConsumableArray(this.sprints.values()).map(function (sprint) {
	        return items = new Map([].concat(babelHelpers.toConsumableArray(items), babelHelpers.toConsumableArray(sprint.getItems())));
	      });
	      return items;
	    }
	  }, {
	    key: "recalculateItemsSort",
	    value: function recalculateItemsSort() {
	      this.backlog.recalculateItemsSort();
	      this.sprints.forEach(function (sprint) {
	        return sprint.recalculateItemsSort();
	      });
	    }
	  }, {
	    key: "findEntityByEntityId",
	    value: function findEntityByEntityId(entityId) {
	      entityId = parseInt(entityId, 10);

	      if (this.backlog.getId() === entityId) {
	        return this.backlog;
	      }

	      return babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.getId() === entityId;
	      });
	    }
	  }, {
	    key: "findItemByItemId",
	    value: function findItemByItemId(itemId) {
	      itemId = parseInt(itemId, 10);
	      var backlogItems = this.backlog.getItems();

	      if (backlogItems.has(itemId)) {
	        return backlogItems.get(itemId);
	      }

	      var sprint = babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.getItems().has(itemId);
	      });

	      if (sprint) {
	        return sprint.getItems().get(itemId);
	      }

	      return null;
	    }
	  }, {
	    key: "findItemBySourceId",
	    value: function findItemBySourceId(sourceId) {
	      sourceId = parseInt(sourceId, 10);
	      var items = new Map(this.backlog.getItems());
	      babelHelpers.toConsumableArray(this.sprints.values()).map(function (sprint) {
	        return items = new Map([].concat(babelHelpers.toConsumableArray(items), babelHelpers.toConsumableArray(sprint.getItems())));
	      });
	      return babelHelpers.toConsumableArray(items.values()).find(function (item) {
	        return item.getSourceId() === sourceId;
	      });
	    }
	  }, {
	    key: "findEntityByItemId",
	    value: function findEntityByItemId(itemId) {
	      itemId = parseInt(itemId, 10);
	      var backlogItems = this.backlog.getItems();

	      if (backlogItems.has(itemId)) {
	        return this.backlog;
	      }

	      return babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.getItems().has(itemId);
	      });
	    }
	  }]);
	  return EntityStorage;
	}();

	var ItemStyleDesigner = /*#__PURE__*/function () {
	  function ItemStyleDesigner(params) {
	    babelHelpers.classCallCheck(this, ItemStyleDesigner);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.listAllUsedColors = new Set();
	    this.updateBorderColorForLinkedItems();
	  }

	  babelHelpers.createClass(ItemStyleDesigner, [{
	    key: "updateBorderColorForLinkedItems",
	    value: function updateBorderColorForLinkedItems() {
	      var _this = this;

	      var itemIdsToUpdateColor = new Set();
	      this.entityStorage.getAllItems().forEach(function (item) {
	        if (item.isLinkedTask() && !item.getBorderColor()) {
	          itemIdsToUpdateColor.add(item.getItemId());
	        }
	      });

	      if (itemIdsToUpdateColor.size) {
	        this.getAllUsedColors().then(function () {
	          var items = new Map();
	          itemIdsToUpdateColor.forEach(function (itemId) {
	            items.set(itemId, _this.getRandomColor(_this.getAllColors()));
	          });

	          _this.requestSender.updateBorderColorToLinkedItems({
	            items: Object.fromEntries(items)
	          }).then(function (response) {
	            var updatedItems = response.data;
	            Object.keys(updatedItems).forEach(function (itemId) {
	              var borderColor = updatedItems[itemId];

	              var item = _this.entityStorage.findItemByItemId(itemId);

	              item.setBorderColor(borderColor);
	            });
	          }).catch(function (response) {
	            _this.requestSender.showErrorAlert(response);
	          });
	        });
	      }
	    }
	  }, {
	    key: "getRandomColorForItemBorder",
	    value: function getRandomColorForItemBorder() {
	      var _this2 = this;

	      return this.getAllUsedColors().then(function () {
	        return _this2.getRandomColor(_this2.getAllColors());
	      });
	    }
	  }, {
	    key: "getAllColors",
	    value: function getAllColors() {
	      var colorPicker = this.getColorPicker();
	      var allColors = [];
	      colorPicker.getDefaultColors().forEach(function (defaultColors) {
	        allColors = [].concat(babelHelpers.toConsumableArray(allColors), babelHelpers.toConsumableArray(defaultColors));
	      });
	      return allColors;
	    }
	  }, {
	    key: "getRandomColor",
	    value: function getRandomColor(allColors) {
	      var randomColor = allColors[Math.floor(Math.random() * allColors.length)];

	      if (this.isThisBorderColorAlreadyUse(randomColor)) {
	        return this.getRandomColor(allColors);
	      } else {
	        return randomColor;
	      }
	    }
	  }, {
	    key: "isThisBorderColorAlreadyUse",
	    value: function isThisBorderColorAlreadyUse(color) {
	      var isAlreadyUse = false;
	      this.listAllUsedColors.forEach(function (usedColor) {
	        if (usedColor === color) {
	          isAlreadyUse = true;
	        }
	      });
	      return isAlreadyUse;
	    }
	  }, {
	    key: "getAllUsedColors",
	    value: function getAllUsedColors() {
	      var _this3 = this;

	      var entityIds = new Set();
	      this.entityStorage.getAllEntities().forEach(function (entity) {
	        if (!entity.isCompleted()) {
	          entityIds.add(entity.getId());
	        }
	      });
	      return this.requestSender.getAllUsedItemBorderColors({
	        entityIds: babelHelpers.toConsumableArray(entityIds.values())
	      }).then(function (response) {
	        _this3.listAllUsedColors = new Set([response.data]);
	      }).catch(function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getColorPicker",
	    value: function getColorPicker() {
	      /* eslint-disable */
	      return new BX.ColorPicker();
	      /* eslint-enable */
	    }
	  }]);
	  return ItemStyleDesigner;
	}();

	function _templateObject4$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprints\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$6 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-list\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-active-list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-planned-list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-completed-list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject3$7 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-file-drop tasks-scrum-sprint-sprint-add-drop\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t<small>", "</small>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$8 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$l() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-sprint-create ui-btn ui-btn-md ui-btn-themes ui-btn-light-border ui-btn-icon-add\">\n\t\t\t\t\t<span>", "</span>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject$l = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var DomBuilder = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(DomBuilder, _EventEmitter);

	  function DomBuilder(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, DomBuilder);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DomBuilder).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.DomBuilder');

	    _this.requestSender = params.requestSender;
	    _this.entityStorage = params.entityStorage;
	    _this.defaultSprintDuration = params.defaultSprintDuration;
	    return _this;
	  }

	  babelHelpers.createClass(DomBuilder, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      this.scrumContainer = container;
	      this.append(this.entityStorage.getBacklog().render(), this.scrumContainer);
	      this.entityStorage.getBacklog().onAfterAppend();
	      this.append(this.renderSprintsContainer(), this.scrumContainer);
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        sprint.onAfterAppend();
	      });
	      this.sprintCreatingButtonNode = document.getElementById(this.sprintCreatingButtonNodeId);
	      this.sprintCreatingDropZoneNode = document.getElementById(this.sprintCreatingDropZoneNodeId);
	      this.sprintListNode = document.getElementById(this.sprintListNodeId);
	      main_core.Event.bind(this.sprintCreatingButtonNode, 'click', this.createSprint.bind(this));
	      this.setDraggable();
	    }
	  }, {
	    key: "getSprintCreatingDropZoneNode",
	    value: function getSprintCreatingDropZoneNode() {
	      return this.sprintCreatingDropZoneNode;
	    }
	  }, {
	    key: "getSprintPlannedListNode",
	    value: function getSprintPlannedListNode() {
	      return this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	    }
	  }, {
	    key: "setDraggable",
	    value: function setDraggable() {
	      var _this2 = this;

	      var itemContainers = [];
	      itemContainers.push(this.entityStorage.getBacklog().getListItemsNode());

	      if (this.sprintCreatingDropZoneNode) {
	        itemContainers.push(this.sprintCreatingDropZoneNode);
	      }

	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isDisabled()) {
	          itemContainers.push(sprint.getListItemsNode());
	        }
	      });
	      this.draggableItems = new ui_draganddrop_draggable.Draggable({
	        container: itemContainers,
	        draggable: '.tasks-scrum-item-drag',
	        // todo add tmp class
	        dragElement: '.tasks-scrum-item',
	        type: ui_draganddrop_draggable.Draggable.DROP_PREVIEW,
	        delay: 200
	      });
	      this.draggableItems.subscribe('start', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();

	        _this2.emit('itemMoveStart', dragEndEvent);
	      });
	      this.draggableItems.subscribe('end', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();

	        _this2.emit('itemMoveEnd', dragEndEvent);
	      });
	      this.draggableSprints = new ui_draganddrop_draggable.Draggable({
	        container: this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list'),
	        draggable: '.tasks-scrum-sprint',
	        dragElement: '.tasks-scrum-sprint-dragndrop',
	        type: ui_draganddrop_draggable.Draggable.DROP_PREVIEW
	      });
	      this.draggableSprints.subscribe('end', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();

	        _this2.emit('sprintMove', dragEndEvent);
	      });
	    }
	  }, {
	    key: "renderSprintsContainer",
	    value: function renderSprintsContainer() {
	      var _this3 = this;

	      var createCreatingButton = function createCreatingButton() {
	        _this3.sprintCreatingButtonNodeId = 'tasks-scrum-sprint-creating-button';
	        return main_core.Tag.render(_templateObject$l(), _this3.sprintCreatingButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD'));
	      };

	      var createCreatingDropZone = function createCreatingDropZone() {
	        if (_this3.entityStorage.getSprints().size) {
	          return '';
	        }

	        _this3.sprintCreatingDropZoneNodeId = 'tasks-scrum-sprint-creating-drop-zone';
	        return main_core.Tag.render(_templateObject2$8(), _this3.sprintCreatingDropZoneNodeId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_DROP'));
	      };

	      var createSprintsList = function createSprintsList() {
	        _this3.sprintListNodeId = 'tasks-scrum-sprint-list';
	        return main_core.Tag.render(_templateObject3$7(), _this3.sprintListNodeId, babelHelpers.toConsumableArray(_this3.entityStorage.getSprints().values()).map(function (sprint) {
	          if (sprint.isActive()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }), babelHelpers.toConsumableArray(_this3.entityStorage.getSprints().values()).map(function (sprint) {
	          if (sprint.isPlanned()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }), babelHelpers.toConsumableArray(_this3.entityStorage.getSprints().values()).map(function (sprint) {
	          if (sprint.isCompleted()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }));
	      };

	      return main_core.Tag.render(_templateObject4$6(), createCreatingButton(), createCreatingDropZone(), createSprintsList());
	    }
	  }, {
	    key: "createSprint",
	    value: function createSprint() {
	      var _this4 = this;

	      this.remove(this.sprintCreatingDropZoneNode);
	      var countSprints = this.entityStorage.getSprints().size;
	      var title = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_NAME').replace('%s', countSprints + 1);
	      var storyPoints = 0;
	      var dateStart = Math.floor(Date.now() / 1000);
	      var dateEnd = Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10);
	      var sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	      var sort = sprintListNode.children.length ? sprintListNode.children.length + 1 : 1;
	      var sprint = Sprint.buildSprint({
	        name: title,
	        sort: sort,
	        dateStart: dateStart,
	        dateEnd: dateEnd,
	        storyPoints: storyPoints
	      });
	      this.append(sprint.render(), sprintListNode);
	      var requestData = {
	        tmpId: main_core.Text.getRandom(),
	        name: title,
	        sort: sort,
	        dateStart: dateStart,
	        dateEnd: dateEnd
	      };
	      this.emit('beforeCreateSprint', requestData);
	      return this.requestSender.createSprint(requestData).then(function (response) {
	        sprint.setId(response.data.sprintId);
	        sprint.onAfterAppend();
	        sprint.getNode().scrollIntoView(true);

	        _this4.entityStorage.addSprint(sprint);

	        _this4.draggableItems.addContainer(sprint.getListItemsNode());

	        _this4.emit('createSprint', sprint); //todo move handlers to new classes


	        return sprint;
	      }).catch(function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "createSprintNode",
	    value: function createSprintNode(sprint) {
	      this.remove(this.sprintCreatingDropZoneNode);
	      var sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	      this.append(sprint.render(), sprintListNode);
	      sprint.onAfterAppend();
	      this.entityStorage.addSprint(sprint);
	      this.draggableItems.addContainer(sprint.getListItemsNode());
	      this.emit('createSprintNode', sprint);
	    }
	  }, {
	    key: "moveSprintToActiveListNode",
	    value: function moveSprintToActiveListNode(sprint) {
	      var sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-active-list');
	      this.append(sprint.getNode(), sprintListNode);
	    }
	  }, {
	    key: "moveSprintToCompletedListNode",
	    value: function moveSprintToCompletedListNode(sprint) {
	      var sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-completed-list');

	      if (sprintListNode.firstElementChild) {
	        this.insertBefore(sprint.getNode(), sprintListNode.firstElementChild);
	      } else {
	        this.append(sprint.getNode(), sprintListNode);
	      }
	    }
	  }, {
	    key: "append",
	    value: function append(current, target) {
	      main_core.Dom.append(current, target);
	    }
	  }, {
	    key: "insertBefore",
	    value: function insertBefore(current, target) {
	      main_core.Dom.insertBefore(current, target);
	    }
	  }, {
	    key: "remove",
	    value: function remove(element) {
	      main_core.Dom.remove(element);
	    }
	  }, {
	    key: "getPosition",
	    value: function getPosition(element) {
	      return main_core.Dom.getPosition(element);
	    }
	  }, {
	    key: "appendItemAfterItem",
	    value: function appendItemAfterItem(newItemNode, bindItemNode) {
	      if (bindItemNode.nextElementSibling) {
	        main_core.Dom.insertBefore(newItemNode, bindItemNode.nextElementSibling);
	      } else {
	        main_core.Dom.append(newItemNode, bindItemNode.parentElement);
	      }
	    }
	  }]);
	  return DomBuilder;
	}(main_core_events.EventEmitter);

	var SubTasksManager = /*#__PURE__*/function () {
	  function SubTasksManager(params) {
	    babelHelpers.classCallCheck(this, SubTasksManager);
	    this.requestSender = params.requestSender;
	    this.domBuilder = params.domBuilder;
	    this.listSubTasks = new Map();
	    this.visibilityList = new Set();
	  }

	  babelHelpers.createClass(SubTasksManager, [{
	    key: "toggleSubTasks",
	    value: function toggleSubTasks(sprint, item) {
	      var _this = this;

	      if (this.listSubTasks.has(item.getItemId())) {
	        if (this.isShown(item)) {
	          this.hideSubTaskItems(sprint, item);
	        } else {
	          this.showSubTaskItems(sprint, item);
	        }

	        item.toggleSubTasksTick();
	        return Promise.resolve();
	      } else {
	        return this.requestSender.getSubTaskItems({
	          entityId: sprint.getId(),
	          taskId: item.getSourceId()
	        }).then(function (response) {
	          var listItemParams = response.data;
	          var listSubTaskItems = new Map();
	          listItemParams.forEach(function (itemParams) {
	            var subTaskItem = _this.buildSubTaskItem(itemParams);

	            listSubTaskItems.set(subTaskItem.getItemId(), subTaskItem);
	          });

	          _this.listSubTasks.set(item.getItemId(), listSubTaskItems);

	          if (_this.isShown(item)) {
	            _this.hideSubTaskItems(sprint, item);
	          } else {
	            _this.showSubTaskItems(sprint, item);
	          }

	          item.toggleSubTasksTick();
	        }).catch(function (response) {
	          _this.requestSender.showErrorAlert(response);
	        });
	      }
	    }
	  }, {
	    key: "buildSubTaskItem",
	    value: function buildSubTaskItem(itemParams) {
	      return new Item(itemParams);
	    }
	  }, {
	    key: "showSubTaskItems",
	    value: function showSubTaskItems(sprint, parentItem) {
	      var _this2 = this;

	      var parentItemNode = parentItem.getItemNode();
	      var listSubTasks = this.listSubTasks.get(parentItem.getItemId());

	      if (!listSubTasks) {
	        return;
	      }

	      listSubTasks.forEach(function (subTaskItem) {
	        sprint.setItem(subTaskItem);

	        _this2.domBuilder.appendItemAfterItem(subTaskItem.render(), parentItemNode);

	        subTaskItem.onAfterAppend(sprint.getListItemsNode());
	      });
	      this.setVisibility(parentItem);
	    }
	  }, {
	    key: "hideSubTaskItems",
	    value: function hideSubTaskItems(sprint, parentItem) {
	      var _this3 = this;

	      var listSubTasks = this.listSubTasks.get(parentItem.getItemId());

	      if (!listSubTasks) {
	        return;
	      }

	      listSubTasks.forEach(function (subTaskItem) {
	        if (subTaskItem.isParentTask()) {
	          _this3.hideSubTaskItems(sprint, subTaskItem);
	        }

	        sprint.removeItem(subTaskItem);
	        subTaskItem.removeYourself();
	      });
	      this.cleanVisibility(parentItem);
	    }
	  }, {
	    key: "addSubTask",
	    value: function addSubTask(parentItem, item) {
	      if (this.listSubTasks.has(parentItem.getItemId())) {
	        var listSubTasks = this.listSubTasks.get(parentItem.getItemId());
	        listSubTasks.set(item.getItemId(), item);
	        this.listSubTasks.set(parentItem.getItemId(), listSubTasks);
	      } else {
	        var _listSubTasks = new Map();

	        _listSubTasks.set(item.getItemId(), item);

	        return this.listSubTasks.set(parentItem.getItemId(), _listSubTasks);
	      }
	    }
	  }, {
	    key: "getSubTasks",
	    value: function getSubTasks(parentItem) {
	      return this.listSubTasks.get(parentItem.getItemId());
	    }
	  }, {
	    key: "isShown",
	    value: function isShown(item) {
	      return this.visibilityList.has(item.getItemId());
	    }
	  }, {
	    key: "setVisibility",
	    value: function setVisibility(item) {
	      this.visibilityList.add(item.getItemId());
	    }
	  }, {
	    key: "cleanVisibility",
	    value: function cleanVisibility(item) {
	      this.visibilityList.delete(item.getItemId());
	    }
	  }, {
	    key: "cleanSubTasks",
	    value: function cleanSubTasks(item) {
	      this.listSubTasks.delete(item.getItemId());
	    }
	  }]);
	  return SubTasksManager;
	}();

	function _templateObject$m() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-decomposition-structure\">\n\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$m = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Decomposition = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Decomposition, _EventEmitter);

	  function Decomposition(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Decomposition);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Decomposition).call(this, params));
	    _this.entity = params.entity;
	    _this.itemStyleDesigner = params.itemStyleDesigner;
	    _this.subTasksCreator = params.subTasksCreator;

	    _this.setEventNamespace('BX.Tasks.Scrum.Decomposition');

	    _this.items = new Set();
	    _this.input = new Input();

	    _this.input.setPlaceholder(main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_DECOMPOSITION_INPUT_PLACEHOLDER'));

	    _this.input.subscribe('tagsSearchOpen', function (baseEvent) {
	      _this.emit('tagsSearchOpen', {
	        inputObject: baseEvent.getTarget(),
	        enteredHashTagName: baseEvent.getData()
	      });
	    });

	    _this.input.subscribe('tagsSearchClose', function () {
	      return _this.emit('tagsSearchClose');
	    });

	    return _this;
	  }

	  babelHelpers.createClass(Decomposition, [{
	    key: "decomposeItem",
	    value: function decomposeItem(item) {
	      var _this2 = this;

	      this.addDecomposedItem(item);

	      if (this.isBacklogDecomposition()) {
	        this.onDecomposeItem(item, item.getItemNode());
	      } else {
	        if (!this.subTasksCreator.isShown(item)) {
	          this.subTasksCreator.toggleSubTasks(this.entity, item).then(function () {
	            var lastSubTask = _this2.getSubTasks(item)[0];

	            var targetItemNode = lastSubTask ? lastSubTask.getItemNode() : item.getItemNode();

	            _this2.onDecomposeItem(item, targetItemNode);
	          });
	        } else {
	          var lastSubTask = this.getSubTasks(item)[0];
	          this.onDecomposeItem(item, lastSubTask.getItemNode());
	        }
	      }
	    }
	  }, {
	    key: "onDecomposeItem",
	    value: function onDecomposeItem(item, targetItemNode) {
	      var _this3 = this;

	      main_core.Dom.insertAfter(this.input.render(), targetItemNode);
	      this.input.setNode();
	      var inputNode = this.input.getInputNode();
	      main_core.Event.bind(inputNode, 'input', this.input.onTagSearch.bind(this.input));
	      main_core.Event.bind(inputNode, 'keydown', this.onCreateItem.bind(this));
	      inputNode.focus();
	      var button = this.createButton();
	      main_core.Dom.insertAfter(button, this.input.getNode());
	      main_core.Event.bind(button.querySelector('button'), 'click', function () {
	        if (_this3.isBacklogDecomposition() && _this3.firstDecomposition() && !item.isLinkedTask()) {
	          item.setBorderColor();
	        }

	        if (!_this3.isBacklogDecomposition()) {
	          if (_this3.subTasksCreator.isShown(item)) {
	            _this3.subTasksCreator.hideSubTaskItems(_this3.entity, item);
	          }

	          _this3.subTasksCreator.cleanSubTasks(item);
	        }

	        _this3.deactivateDecompositionMode();

	        _this3.input.removeYourself();

	        main_core.Dom.remove(button);
	      });
	      this.setResponsible(item.getResponsible());
	    }
	  }, {
	    key: "getSubTasks",
	    value: function getSubTasks(parentItem) {
	      return Array.from(this.subTasksCreator.getSubTasks(parentItem).values());
	    }
	  }, {
	    key: "getLastDecomposedItemNode",
	    value: function getLastDecomposedItemNode(parentItem) {
	      if (this.isBacklogDecomposition()) {
	        var decomposedItems = this.getDecomposedItems();
	        var lastDecomposedItem = Array.from(decomposedItems).pop();
	        return lastDecomposedItem.getItemNode();
	      } else {
	        var subTasks = this.getSubTasks(parentItem);

	        if (subTasks.length) {
	          var lastSubTask = this.firstDecomposition() ? subTasks[0] : subTasks.pop();
	          return lastSubTask.getItemNode();
	        } else {
	          return parentItem.getItemNode();
	        }
	      }
	    }
	  }, {
	    key: "isBacklogDecomposition",
	    value: function isBacklogDecomposition() {
	      return this.entity.getEntityType() === 'backlog';
	    }
	  }, {
	    key: "addDecomposedItem",
	    value: function addDecomposedItem(item) {
	      this.activateDecompositionMode(item);
	      item.subscribe('changeTaskResponsible', this.saveSelectedResponsible.bind(this));
	      this.items.add(item);
	    }
	  }, {
	    key: "getDecomposedItems",
	    value: function getDecomposedItems() {
	      return this.items;
	    }
	  }, {
	    key: "activateDecompositionMode",
	    value: function activateDecompositionMode(item) {
	      var _this4 = this;

	      if (this.isBacklogDecomposition()) {
	        this.itemStyleDesigner.getRandomColorForItemBorder().then(function (randomColor) {
	          item.activateDecompositionMode(randomColor);

	          _this4.setBorderColor(item.getBorderColor());
	        });
	      } else {
	        item.activateDecompositionMode();
	      }
	    }
	  }, {
	    key: "deactivateDecompositionMode",
	    value: function deactivateDecompositionMode() {
	      this.items.forEach(function (item) {
	        item.deactivateDecompositionMode();
	      });
	      this.items.clear();
	    }
	  }, {
	    key: "createButton",
	    value: function createButton() {
	      return main_core.Tag.render(_templateObject$m(), main_core.Loc.getMessage('TASKS_SCRUM_DECOMPOSITION_BUTTON'));
	    }
	  }, {
	    key: "onCreateItem",
	    value: function onCreateItem(event) {
	      if (event.isComposing || event.keyCode === 13) {
	        if (!this.input.isTagsSearchMode()) {
	          var inputNode = event.target;

	          if (inputNode.value) {
	            var parentItem = this.getParentItem();

	            if (this.isBacklogDecomposition()) {
	              if (this.firstDecomposition() && !parentItem.isLinkedTask()) {
	                this.emit('updateParentItem', {
	                  itemId: parentItem.getItemId(),
	                  entityId: parentItem.getEntityId(),
	                  itemType: parentItem.getItemType(),
	                  info: parentItem.getInfo()
	                });
	              }
	            }

	            this.emit('createItem', inputNode.value);
	            inputNode.value = '';
	            inputNode.focus();
	          }
	        }
	      }
	    }
	  }, {
	    key: "firstDecomposition",
	    value: function firstDecomposition() {
	      return this.items.size === 1;
	    }
	  }, {
	    key: "getParentItem",
	    value: function getParentItem() {
	      var iterator = this.items.values();
	      return iterator.next().value;
	    }
	  }, {
	    key: "saveSelectedResponsible",
	    value: function saveSelectedResponsible(baseEvent) {
	      var item = baseEvent.getTarget();
	      this.setResponsible(item.getResponsible());
	    }
	  }, {
	    key: "getResponsible",
	    value: function getResponsible() {
	      return this.responsible;
	    }
	  }, {
	    key: "setResponsible",
	    value: function setResponsible(responsible) {
	      this.responsible = responsible;
	    }
	  }, {
	    key: "setBorderColor",
	    value: function setBorderColor(color) {
	      this.borderColor = main_core.Type.isString(color) ? color : '';
	    }
	  }, {
	    key: "getBorderColor",
	    value: function getBorderColor() {
	      return this.borderColor;
	    }
	  }]);
	  return Decomposition;
	}(main_core_events.EventEmitter);

	var FilterHandler = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FilterHandler, _EventEmitter);

	  function FilterHandler(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, FilterHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FilterHandler).call(this, params));
	    _this.filter = params.filter;
	    _this.requestSender = params.requestSender;
	    _this.entityStorage = params.entityStorage;

	    _this.filter.subscribe('applyFilter', _this.onApplyFilter.bind(babelHelpers.assertThisInitialized(_this)));

	    return _this;
	  }

	  babelHelpers.createClass(FilterHandler, [{
	    key: "onApplyFilter",
	    value: function onApplyFilter(baseEvent) {
	      var _this2 = this;

	      this.fadeOutAll();
	      var filterInfo = baseEvent.getData();
	      this.updateExactSearchStatusToEntities();
	      this.requestSender.applyFilter().then(function (response) {
	        filterInfo.promise.fulfill();
	        var filteredItemsData = response.data;

	        _this2.entityStorage.getAllItems().forEach(function (item) {
	          var entity = _this2.entityStorage.findEntityByEntityId(item.getEntityId());

	          entity.removeItem(item);
	          item.removeYourself();
	        });

	        filteredItemsData.forEach(function (itemData) {
	          var item = Item.buildItem(itemData);

	          var entity = _this2.entityStorage.findEntityByEntityId(item.getEntityId());

	          main_core.Dom.append(item.render(), entity.getListItemsNode());
	          entity.setItem(item);
	          item.onAfterAppend(entity.getListItemsNode());
	        });

	        _this2.entityStorage.getBacklog().updateStoryPoints();

	        _this2.entityStorage.getSprints().forEach(function (sprint) {
	          if (!sprint.isCompleted()) {
	            sprint.updateStoryPoints();
	          }
	        });

	        _this2.updateVisibilityToEntities();

	        _this2.fadeInAll();
	      }).catch(function (response) {
	        filterInfo.promise.reject();

	        _this2.fadeInAll();

	        _this2.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "updateExactSearchStatusToEntities",
	    value: function updateExactSearchStatusToEntities() {
	      var _this3 = this;

	      this.entityStorage.getSprints().forEach(function (sprint) {
	        sprint.setExactSearchApplied(_this3.filter.isSearchFieldApplied());
	      });
	    }
	  }, {
	    key: "updateVisibilityToEntities",
	    value: function updateVisibilityToEntities() {
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        sprint.updateVisibility();
	      });
	    }
	  }, {
	    key: "fadeOutAll",
	    value: function fadeOutAll() {
	      this.entityStorage.getBacklog().fadeOut();
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        sprint.fadeOut();
	      });
	    }
	  }, {
	    key: "fadeInAll",
	    value: function fadeInAll() {
	      this.entityStorage.getBacklog().fadeIn();
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        sprint.fadeIn();
	      });
	    }
	  }]);
	  return FilterHandler;
	}(main_core_events.EventEmitter);

	function _templateObject7$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t<input type=\"text\" name=\"name\" value=\"", "\" class=\n\t\t\t\t\t\t\"tasks-scrum-epic-form-header-title-control\" placeholder=\n\t\t\t\t\t\t\"", "\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-btn-angle\">\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject7$3 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-header\">\n\t\t\t\t<div class=\"tasks-scrum-epic-header-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6$4 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epics-empty\">\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-first-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-image\">\n\t\t\t\t\t<svg width=\"124px\" height=\"123px\" viewBox=\"0 0 124 123\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n\t\t\t\t\t\t<g stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\" opacity=\"0.28\">\n\t\t\t\t\t\t\t<path d=\"M83,105 L83,81.4375 L105,81.4375 L105,18 L17,18 L17,81.4375 L39,81.4375 L39,105 L83,105 Z M10.9411765,0 L113.058824,0 C119.101468,0 124,4.85902727 124,10.8529412 L124,112.147059 C124,118.140973 119.101468,123 113.058824,123 L10.9411765,123 C4.89853156,123 0,118.140973 0,112.147059 L0,10.8529412 C0,4.85902727 4.89853156,0 10.9411765,0 Z M44.0142862,47.0500004 L54.2142857,57.4416671 L79.7142857,32 L87,42.75 L54.2142857,75 L36,57.0833333 L44.0142862,47.0500004 Z\" fill=\"#A8ADB4\" />\n\t\t\t\t\t\t</g>\n\t\t\t\t\t</svg>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-second-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-button\">\n\t\t\t\t\t<button class=\"ui-btn ui-btn-primary ui-btn-lg\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$7 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-btn-angle\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\" style=\"padding: 15px 10px 15px 10px;\"></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-files\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$8 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$9 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$n() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum-epics-list\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-add-button\">\n\t\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-primary ui-btn-sm\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epics-list-grid\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject$n = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Epic = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Epic, _EventEmitter);

	  function Epic(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Epic);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Epic).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Epic');

	    _this.requestSender = params.requestSender;
	    _this.entityStorage = params.entityStorage;
	    _this.sidePanel = params.sidePanel;
	    _this.entity = params.entity;
	    _this.filter = params.filter;
	    _this.tagSearcher = params.tagSearcher;
	    _this.form = null;
	    _this.defaultColor = '#69dafc';
	    _this.selectedColor = '';
	    _this.currentEpic = null;
	    return _this;
	  }

	  babelHelpers.createClass(Epic, [{
	    key: "getCurrentEpic",
	    value: function getCurrentEpic() {
	      return this.currentEpic;
	    }
	  }, {
	    key: "openAddForm",
	    value: function openAddForm() {
	      var _this2 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadAddForm.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this2.buildAddForm());
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "openEditForm",
	    value: function openEditForm(epicId) {
	      var _this3 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadEditForm.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            _this3.getEpic(epicId).then(function (response) {
	              _this3.currentEpic = response.data;
	              resolve(_this3.buildEditForm());
	            });
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "openViewForm",
	    value: function openViewForm(epicId) {
	      var _this4 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadViewForm.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            _this4.getEpic(epicId).then(function (response) {
	              _this4.currentEpic = response.data;
	              resolve(_this4.buildViewForm());
	            });
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "openEpicsList",
	    value: function openEpicsList() {
	      var _this5 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequest.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          _this5.sidePanel.subscribeOnce('onLoadSidePanel', _this5.onLoadListGrid.bind(_this5));

	          _this5.sidePanel.subscribeOnce('onCloseSidePanel', _this5.destroyGrid.bind(_this5));

	          return new Promise(function (resolve, reject) {
	            resolve(main_core.Tag.render(_templateObject$n(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')));
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "onLoadAddForm",
	    value: function onLoadAddForm(baseEvent) {
	      var _this6 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
	      this.currentEpic = null;
	      this.onLoadEditor();
	      this.onLoadColorPicker();
	      this.onLoadAddButtons().then(function (buttonsContainer) {
	        main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', function () {
	          _this6.requestSender.createEpic(_this6.getRequestData()).then(function (response) {
	            _this6.onAfterCreateEpic(response.data);

	            if (_this6.sidePanel.isPreviousSidePanelExist(sidePanel)) {
	              _this6.sidePanel.reloadPreviousSidePanel(sidePanel);

	              sidePanel.close();
	            } else {
	              sidePanel.close(false, function () {
	                _this6.openEpicsList();
	              });
	            }
	          }).catch(function (response) {
	            _this6.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_EPIC_CREATE_ERROR_TITLE_POPUP'));

	            sidePanel.close();
	          });
	        });
	      });
	    }
	  }, {
	    key: "onLoadViewForm",
	    value: function onLoadViewForm(baseEvent) {
	      var _this7 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
	      this.onLoadDescription();
	      this.onLoadFiles();
	      this.onLoadViewButtons().then(function (buttonsContainer) {
	        main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', function () {
	          sidePanel.close(false, function () {
	            _this7.openEditForm(_this7.currentEpic.id);
	          });
	        });
	      });
	    }
	  }, {
	    key: "onLoadEditForm",
	    value: function onLoadEditForm(baseEvent) {
	      var _this8 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
	      this.onLoadEditor();
	      this.onLoadColorPicker();
	      this.onLoadEditButtons().then(function (buttonsContainer) {
	        main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', function () {
	          _this8.requestSender.editEpic(_this8.getRequestData()).then(function (response) {
	            _this8.emit('onAfterEditEpic', response);

	            sidePanel.close(false, function () {
	              _this8.reloadGrid();
	            });
	          }).catch(function (response) {
	            _this8.removeClockIconFromButton();

	            _this8.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_EPIC_UPDATE_ERROR_TITLE_POPUP'));
	          });
	        });
	      });
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      /* eslint-disable */
	      if (BX && BX.Main && BX.Main.gridManager) {
	        var gridManager = BX.Main.gridManager.getById(this.gridId);

	        if (gridManager) {
	          gridManager.instance.reload();
	        }
	      }
	      /* eslint-enable */

	    }
	  }, {
	    key: "destroyGrid",
	    value: function destroyGrid() {
	      /* eslint-disable */
	      if (BX && BX.Main && BX.Main.gridManager) {
	        var gridManager = BX.Main.gridManager.getById(this.gridId);

	        if (gridManager) {
	          gridManager.instance.destroy();
	        }
	      }
	      /* eslint-enable */

	    }
	  }, {
	    key: "onLoadEditor",
	    value: function onLoadEditor() {
	      var _this9 = this;

	      this.getDescriptionEditor().then(function (editorHtml) {
	        var descriptionContainer = _this9.form.querySelector('.tasks-scrum-epic-form-description');

	        main_core.Runtime.html(descriptionContainer, editorHtml).then(function () {
	          _this9.editor = window.LHEPostForm.getHandler(_this9.id);
	          window.BXHtmlEditor.Get(_this9.id);
	          main_core_events.EventEmitter.emit(_this9.editor.eventNode, 'OnShowLHE', [true]);
	          setTimeout(function () {
	            _this9.form.querySelector('.tasks-scrum-epic-form-header-title-control').focus();
	          }, 100);
	        });
	      });
	    }
	  }, {
	    key: "onLoadDescription",
	    value: function onLoadDescription() {
	      var _this10 = this;

	      var descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');
	      this.requestSender.getEpicDescription({
	        epicId: this.currentEpic.id,
	        text: this.currentEpic.description
	      }).then(function (response) {
	        main_core.Runtime.html(descriptionContainer, response.data);
	      }).catch(function (response) {
	        _this10.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onLoadFiles",
	    value: function onLoadFiles() {
	      var filesContainer = this.form.querySelector('.tasks-scrum-epic-form-files');
	      this.requestSender.getEpicFiles({
	        epicId: this.currentEpic.id
	      }).then(function (response) {
	        main_core.Runtime.html(filesContainer, response.data.html);
	      });
	    }
	  }, {
	    key: "onLoadColorPicker",
	    value: function onLoadColorPicker() {
	      var _this11 = this;

	      this.selectedColor = this.currentEpic ? this.currentEpic.info.color : this.defaultColor;
	      var colorBlockNode = this.form.querySelector('.tasks-scrum-epic-header-color');
	      main_core.Event.bind(colorBlockNode, 'click', function () {
	        var colorNode = colorBlockNode.querySelector('.tasks-scrum-epic-header-color-current');

	        var picker = _this11.getColorPicker(colorNode);

	        picker.open();
	      });
	    }
	  }, {
	    key: "onLoadAddButtons",
	    value: function onLoadAddButtons() {
	      var _this12 = this;

	      return this.getAddEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this12.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadViewButtons",
	    value: function onLoadViewButtons() {
	      var _this13 = this;

	      return this.getViewEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this13.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadEditButtons",
	    value: function onLoadEditButtons() {
	      var _this14 = this;

	      return this.getAddEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this14.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadListGrid",
	    value: function onLoadListGrid(baseEvent) {
	      var _this15 = this;

	      var sidePanel = baseEvent.getData();
	      var form = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list');
	      var list = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list-grid');
	      this.getEpicsList().then(function (responseData) {
	        if (responseData.html) {
	          main_core.Runtime.html(list, responseData.html);

	          _this15.prepareTagsList(list);

	          var buttonNode = form.querySelector('.tasks-scrum-epic-header-add-button');
	          main_core.Event.bind(buttonNode, 'click', function () {
	            _this15.openAddForm();
	          });
	        } else {
	          main_core.Dom.remove(form.querySelector('.tasks-scrum-epic-header-add-button'));
	          main_core.Dom.append(_this15.getEmptyEpicListForm(), list);

	          var _buttonNode = list.querySelector('.tasks-scrum-epics-empty-button');

	          main_core.Event.bind(_buttonNode, 'click', function () {
	            _this15.openAddForm();
	          });
	        }
	      });
	    }
	  }, {
	    key: "onBeforeGridRequest",
	    value: function onBeforeGridRequest(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	          eventArgs = _event$getCompatData2[1];

	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.method = 'POST';

	      if (!eventArgs.url) {
	        eventArgs.url = this.requestSender.getEpicListUrl();
	      }

	      eventArgs.data = babelHelpers.objectSpread({}, eventArgs.data, {
	        entityId: this.entity.getId(),
	        gridId: this.gridId,
	        signedParameters: this.requestSender.getSignedParameters()
	      });
	    }
	  }, {
	    key: "getEpicsList",
	    value: function getEpicsList() {
	      var _this16 = this;

	      this.gridId = 'EntityEpicsGrid_' + this.entity.getId();
	      return new Promise(function (resolve, reject) {
	        _this16.requestSender.getEpicsList({
	          entityId: _this16.entity.getId(),
	          gridId: _this16.gridId
	        }).then(function (response) {
	          resolve(response.data);
	        }).catch(function (response) {
	          _this16.requestSender.showErrorAlert(response);
	        });
	      });
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic(id) {
	      return this.requestSender.getEpic({
	        id: id
	      });
	    }
	  }, {
	    key: "buildAddForm",
	    value: function buildAddForm() {
	      return main_core.Tag.render(_templateObject2$9(), this.buildFormHeader(main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_FORM_TITLE')), this.buildFormContainerHeader('', '#69dafc'));
	    }
	  }, {
	    key: "buildViewForm",
	    value: function buildViewForm() {
	      return main_core.Tag.render(_templateObject3$8(), this.buildFormHeader(main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_VIEW_EPIC_FORM_TITLE')), main_core.Text.encode(this.currentEpic.name), main_core.Text.encode(this.currentEpic.info.color));
	    }
	  }, {
	    key: "buildEditForm",
	    value: function buildEditForm() {
	      return main_core.Tag.render(_templateObject4$7(), this.buildFormHeader(main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_EDIT_EPIC_FORM_TITLE')), this.buildFormContainerHeader(this.currentEpic.name, this.currentEpic.info.color));
	    }
	  }, {
	    key: "getEmptyEpicListForm",
	    value: function getEmptyEpicListForm() {
	      return main_core.Tag.render(_templateObject5$5(), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_FIRST_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_SECOND_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD'));
	    }
	  }, {
	    key: "buildFormHeader",
	    value: function buildFormHeader(title) {
	      return main_core.Tag.render(_templateObject6$4(), title);
	    }
	  }, {
	    key: "buildFormContainerHeader",
	    value: function buildFormContainerHeader(name, color) {
	      return main_core.Tag.render(_templateObject7$3(), main_core.Text.encode(name), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_NAME_PLACEHOLDER'), main_core.Text.encode(color));
	    }
	  }, {
	    key: "getDescriptionEditor",
	    value: function getDescriptionEditor() {
	      var _this17 = this;

	      var requestData = {
	        editorId: this.id
	      };

	      if (this.currentEpic) {
	        requestData.epicId = this.currentEpic.id;
	        requestData.text = this.currentEpic.description;
	      }

	      return new Promise(function (resolve, reject) {
	        _this17.requestSender.getEpicDescriptionEditor(requestData).then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getAddEpicFormButtons",
	    value: function getAddEpicFormButtons() {
	      var _this18 = this;

	      return new Promise(function (resolve, reject) {
	        _this18.requestSender.getAddEpicFormButtons().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getViewEpicFormButtons",
	    value: function getViewEpicFormButtons() {
	      var _this19 = this;

	      return new Promise(function (resolve, reject) {
	        _this19.requestSender.getViewEpicFormButtonsAction().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getColorPicker",
	    value: function getColorPicker(colorNode) {
	      var _this20 = this;

	      /* eslint-disable */
	      return new BX.ColorPicker({
	        bindElement: colorNode,
	        defaultColor: this.selectedColor,
	        onColorSelected: function onColorSelected(color, picker) {
	          _this20.selectedColor = color;
	          colorNode.style.backgroundColor = color;
	        },
	        popupOptions: {
	          zIndex: 1100,
	          className: 'tasks-scrum-epic-color-popup'
	        },
	        colors: [["#aae9fc", "#bbecf1", "#98e1dc", "#e3f299", "#ffee95", "#ffdd93", "#dfd3b6", "#e3c6bb"], ["#ffad97", "#ffbdbb", "#ffcbd8", "#ffc4e4", "#c4baed", "#dbdde0", "#bfc5cd", "#a2a8b0"]]
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "getRequestData",
	    value: function getRequestData() {
	      var requestData = {};

	      if (this.currentEpic) {
	        requestData.epicId = this.currentEpic.id;
	      }

	      requestData.entityId = this.entity.getId();
	      requestData.name = this.form.querySelector('[name=name]').value.trim();
	      requestData.description = this.editor.oEditor.GetContent();
	      requestData.color = this.selectedColor;
	      requestData.files = this.getAttachmentsFiles();
	      return requestData;
	    }
	  }, {
	    key: "getAttachmentsFiles",
	    value: function getAttachmentsFiles() {
	      var _this21 = this;

	      var files = [];

	      if (!this.editor || !main_core.Type.isPlainObject(this.editor.arFiles) || !main_core.Type.isPlainObject(this.editor.controllers)) {
	        return files;
	      }

	      var fileControllers = [];
	      Object.values(this.editor.arFiles).forEach(function (controller) {
	        if (!fileControllers.includes(controller)) {
	          fileControllers.push(controller);
	        }
	      });
	      fileControllers.forEach(function (fileController) {
	        if (_this21.editor.controllers[fileController] && main_core.Type.isPlainObject(_this21.editor.controllers[fileController].values)) {
	          Object.keys(_this21.editor.controllers[fileController].values).forEach(function (fileId) {
	            if (!files.includes(fileId)) {
	              files.push(fileId);
	            }
	          });
	        }
	      });
	      return files;
	    }
	  }, {
	    key: "prepareTagsList",
	    value: function prepareTagsList(container) {
	      var _this22 = this;

	      var tagsContainers = container.querySelectorAll('.tasks-scrum-epic-grid-tags');
	      tagsContainers.forEach(function (tagsContainer) {
	        var tags = _this22.getTagsFromNode(tagsContainer);

	        main_core.Dom.clean(tagsContainer);
	        tags.forEach(function (tag) {
	          main_core.Dom.append(_this22.getTagNode(tag), tagsContainer);
	        });
	      });
	    }
	  }, {
	    key: "getTagsFromNode",
	    value: function getTagsFromNode(node) {
	      var tags = [];
	      node.childNodes.forEach(function (childNode) {
	        tags.push(childNode.textContent.trim());
	      });
	      return tags;
	    }
	  }, {
	    key: "getTagNode",
	    value: function getTagNode(tag) {
	      var _this23 = this;

	      var tagLabel = new ui_label.Label({
	        text: tag,
	        color: ui_label.Label.Color.TAG_LIGHT,
	        fill: true,
	        size: ui_label.Label.Size.SM,
	        customClass: ''
	      });
	      var container = tagLabel.getContainer();
	      main_core.Event.bind(container, 'click', function () {
	        _this23.emit('filterByTag', tag);

	        _this23.sidePanel.closeTopSidePanel();
	      });
	      return container;
	    }
	  }, {
	    key: "removeClockIconFromButton",
	    value: function removeClockIconFromButton() {
	      var buttonsContainer = this.form.querySelector('.tasks-scrum-epic-form-buttons');

	      if (buttonsContainer) {
	        main_core.Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
	      }
	    }
	  }, {
	    key: "onAfterCreateEpic",
	    value: function onAfterCreateEpic(epic) {
	      this.tagSearcher.addEpicToSearcher(epic);
	      this.filter.addItemToListTypeField('EPIC', {
	        NAME: epic.name.trim(),
	        VALUE: String(epic.id)
	      });
	    }
	  }, {
	    key: "onAfterUpdateEpic",
	    value: function onAfterUpdateEpic(epic) {
	      this.entityStorage.getAllItems().forEach(function (item) {
	        var itemEpic = item.getEpic();

	        if (itemEpic && itemEpic.id === epic.id) {
	          item.setEpicAndTags(epic);
	        }
	      });
	      var oldEpicInfo = this.getCurrentEpic();

	      if (oldEpicInfo) {
	        this.tagSearcher.removeEpicFromSearcher(oldEpicInfo);
	      }

	      this.tagSearcher.addEpicToSearcher(epic);
	    }
	  }, {
	    key: "onAfterRemoveEpic",
	    value: function onAfterRemoveEpic(epic) {
	      this.entityStorage.getAllItems().forEach(function (item) {
	        var itemEpic = item.getEpic();

	        if (itemEpic && itemEpic.id === epic.id) {
	          item.setEpicAndTags(null);
	        }
	      });
	      this.tagSearcher.removeEpicFromSearcher(epic);
	      this.sidePanel.reloadTopSidePanel();
	    }
	  }]);
	  return Epic;
	}(main_core_events.EventEmitter);

	//todo import amchart4 like es6
	var TeamSpeedChart = /*#__PURE__*/function () {
	  function TeamSpeedChart(data) {
	    babelHelpers.classCallCheck(this, TeamSpeedChart);
	    this.data = data;
	    this.chart = null;
	  }

	  babelHelpers.createClass(TeamSpeedChart, [{
	    key: "createChart",
	    value: function createChart(chartDiv) {
	      am4core.useTheme(am4themes_animated);
	      this.chart = am4core.create(chartDiv, am4charts.XYChart);
	      this.chart.data = this.data;
	      this.chart.paddingRight = 40;
	      this.chart.scrollbarX = new am4core.Scrollbar();
	      this.chart.scrollbarX.parent = this.chart.bottomAxesContainer;
	      this.createAxises();
	      this.createColumn('plan', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
	      this.createColumn('done', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');
	      this.createLegend();
	    }
	  }, {
	    key: "createAxises",
	    value: function createAxises() {
	      var xAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
	      xAxis.dataFields.category = 'sprintName';
	      xAxis.renderer.grid.template.location = 0;
	      var label = xAxis.renderer.labels.template;
	      label.wrap = true;
	      label.maxWidth = 120;
	      var yAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
	      yAxis.min = 0;
	    }
	  }, {
	    key: "createColumn",
	    value: function createColumn(valueY, name, color) {
	      var series = this.chart.series.push(new am4charts.ColumnSeries());
	      series.dataFields.valueY = valueY;
	      series.dataFields.categoryX = 'sprintName';
	      series.name = name;
	      series.stroke = am4core.color(color);
	      series.fill = am4core.color(color); // const bullet = series.bullets.push(new am4charts.LabelBullet())
	      // bullet.dy = 10;
	      // bullet.label.text = '{valueY}';
	      // bullet.label.fill = am4core.color('#ffffff');

	      return series;
	    }
	  }, {
	    key: "createLegend",
	    value: function createLegend() {
	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.paddingBottom = 20;
	      this.chart.legend.itemContainers.template.clickable = false;
	    }
	  }, {
	    key: "destroyChart",
	    value: function destroyChart() {
	      this.chart.dispose();
	    }
	  }]);
	  return TeamSpeedChart;
	}();

	function _templateObject2$a() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-project-side-panel\">\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-project-side-panel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-project-dod-panel\"></div>\n\t\t\t\t<div class=\"tasks-scrum-project-dod-options\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element dod-items-required\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$a = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$o() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-project-side-panel\">\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-project-side-panel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-chart\"></div>\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$o = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ProjectSidePanel = /*#__PURE__*/function () {
	  function ProjectSidePanel(params) {
	    babelHelpers.classCallCheck(this, ProjectSidePanel);
	    this.sprints = params.sprints ? params.sprints : new Map();
	    this.sidePanel = params.sidePanel;
	    this.requestSender = params.requestSender;
	  }

	  babelHelpers.createClass(ProjectSidePanel, [{
	    key: "showTeamSpeedChart",
	    value: function showTeamSpeedChart() {
	      var _this = this;

	      this.sidePanelId = 'tasks-scrum-start-' + main_core.Text.getRandom();
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadTeamSpeedChartPanel.bind(this));
	      this.sidePanel.subscribe('onCloseSidePanel', this.onCloseTeamSpeedChart.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this.buildTeamSpeedPanel());
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "showDefinitionOfDone",
	    value: function showDefinitionOfDone(entity) {
	      var _this2 = this;

	      this.sidePanelId = 'tasks-scrum-dod-' + main_core.Text.getRandom();
	      this.entity = entity;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadDodPanel.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, {
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            resolve(_this2.buildDodPanel());
	          });
	        },
	        zIndex: 1000
	      });
	    }
	  }, {
	    key: "buildTeamSpeedPanel",
	    value: function buildTeamSpeedPanel() {
	      return main_core.Tag.render(_templateObject$o(), main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_HEADER'));
	    }
	  }, {
	    key: "buildDodPanel",
	    value: function buildDodPanel() {
	      return main_core.Tag.render(_templateObject2$a(), main_core.Loc.getMessage('TASKS_SCRUM_DOD_HEADER'), main_core.Loc.getMessage('TASKS_SCRUM_DOD_OPTIONS_REQUIRED_LABEL'));
	    }
	  }, {
	    key: "onLoadTeamSpeedChartPanel",
	    value: function onLoadTeamSpeedChartPanel(baseEvent) {
	      var _this3 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');
	      this.getTeamSpeedChartData().then(function (data) {
	        setTimeout(function () {
	          _this3.teamSpeedChart = new TeamSpeedChart(data);

	          _this3.teamSpeedChart.createChart(_this3.form.querySelector('.tasks-scrum-project-side-panel-chart'));
	        }, 300);
	      });
	    }
	  }, {
	    key: "onCloseTeamSpeedChart",
	    value: function onCloseTeamSpeedChart(baseEvent) {
	      var _this4 = this;

	      var sidePanel = baseEvent.getData();

	      if (this.sidePanelId === sidePanel.getUrl()) {
	        setTimeout(function () {
	          _this4.teamSpeedChart.destroyChart();

	          _this4.teamSpeedChart = null;
	        }, 300);
	      }
	    }
	  }, {
	    key: "onLoadDodPanel",
	    value: function onLoadDodPanel(baseEvent) {
	      var _this5 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');
	      this.getDodComponent().then(function (data) {
	        var dodContainer = _this5.form.querySelector('.tasks-scrum-project-dod-panel');

	        main_core.Runtime.html(dodContainer, data.html);
	      }).then(function () {
	        _this5.getDodPanelData().then(function (data) {
	          _this5.prepareDodOptionsContainer(data);
	        });
	      }).then(function () {
	        _this5.requestSender.getDodButtons().then(function (response) {
	          var buttonsContainer = _this5.form.querySelector('.tasks-scrum-project-side-panel-buttons');

	          main_core.Runtime.html(buttonsContainer, response.data.html).then(function () {
	            main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', function () {
	              _this5.requestSender.saveDod(_this5.getRequestDataForSaveList()).then(function (response) {
	                sidePanel.close();
	              });
	            });
	          });
	        });
	      });
	    }
	  }, {
	    key: "getTeamSpeedChartData",
	    value: function getTeamSpeedChartData() {
	      var _this6 = this;

	      return new Promise(function (resolve, reject) {
	        _this6.requestSender.getTeamSpeedChartData().then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "getDodComponent",
	    value: function getDodComponent() {
	      var _this7 = this;

	      return new Promise(function (resolve, reject) {
	        _this7.requestSender.getDodComponent({
	          entityId: _this7.entity.getId()
	        }).then(function (response) {
	          resolve(response.data);
	        }).catch(function (response) {
	          _this7.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));
	        });
	      });
	    }
	  }, {
	    key: "getDodPanelData",
	    value: function getDodPanelData() {
	      var _this8 = this;

	      return new Promise(function (resolve, reject) {
	        _this8.requestSender.getDodPanelData({
	          entityId: _this8.entity.getId()
	        }).then(function (response) {
	          resolve(response.data);
	        }).catch(function (response) {
	          _this8.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));
	        });
	      });
	    }
	  }, {
	    key: "getRequestDataForSaveList",
	    value: function getRequestDataForSaveList() {
	      var requestData = {};
	      requestData.entityId = this.entity.getId();
	      requestData.items = this.getEntityChecklistItems();
	      requestData.required = this.getDodItemsRequired();
	      return requestData;
	    }
	  }, {
	    key: "getEntityChecklistItems",
	    value: function getEntityChecklistItems() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }

	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
	      return treeStructure.getRequestData();
	      /* eslint-enable */
	    }
	  }, {
	    key: "getDodItemsRequired",
	    value: function getDodItemsRequired() {
	      var optionsContainer = this.form.querySelector('.tasks-scrum-project-dod-options');
	      var option = optionsContainer.querySelector('.dod-items-required');
	      return option.checked === true ? 'Y' : 'N';
	    }
	  }, {
	    key: "prepareDodOptionsContainer",
	    value: function prepareDodOptionsContainer(data) {
	      if (data['dodItemsRequired']) {
	        var optionsContainer = this.form.querySelector('.tasks-scrum-project-dod-options');
	        var option = optionsContainer.querySelector('.dod-items-required');
	        option.checked = data['dodItemsRequired'] === 'Y';
	      }
	    }
	  }]);
	  return ProjectSidePanel;
	}();

	var PullSprint = /*#__PURE__*/function () {
	  function PullSprint(params) {
	    babelHelpers.classCallCheck(this, PullSprint);
	    this.requestSender = params.requestSender;
	    this.domBuilder = params.domBuilder;
	    this.entityStorage = params.entityStorage;
	    this.groupId = params.groupId;
	    this.listIdsToSkipAdding = new Set();
	    this.listIdsToSkipUpdating = new Set();
	    this.listIdsToSkipRemoving = new Set();
	  }

	  babelHelpers.createClass(PullSprint, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'tasks';
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return {
	        sprintAdded: this.onSprintAdded.bind(this),
	        sprintUpdated: this.onSprintUpdated.bind(this),
	        sprintRemoved: this.onSprintRemoved.bind(this)
	      };
	    }
	  }, {
	    key: "onSprintAdded",
	    value: function onSprintAdded(params) {
	      if (this.groupId !== params.groupId) {
	        return;
	      }

	      var sprint = Sprint.buildSprint(params);

	      if (this.needSkipAdd(sprint)) {
	        this.cleanSkipAdd(sprint);
	        return;
	      }

	      this.domBuilder.createSprintNode(sprint);
	    }
	  }, {
	    key: "onSprintUpdated",
	    value: function onSprintUpdated(params) {
	      var tmpSprint = Sprint.buildSprint(params);

	      if (this.needSkipUpdate(tmpSprint)) {
	        this.cleanSkipUpdate(tmpSprint);
	        return;
	      }

	      var sprint = this.entityStorage.findEntityByEntityId(tmpSprint.getId());

	      if (sprint) {
	        if (tmpSprint.getStatus() !== sprint.getStatus()) {
	          if (tmpSprint.getStatus() === 'active') {
	            this.domBuilder.moveSprintToActiveListNode(sprint);
	          } else {
	            this.domBuilder.moveSprintToCompletedListNode(sprint);
	          }
	        }

	        sprint.updateYourself(tmpSprint);
	      }
	    }
	  }, {
	    key: "onSprintRemoved",
	    value: function onSprintRemoved(params) {
	      if (this.needSkipRemove(params.sprintId)) {
	        this.cleanSkipRemove(params.sprintId);
	        return;
	      }

	      var sprint = this.entityStorage.findEntityByEntityId(params.sprintId);

	      if (sprint) {
	        sprint.removeSprint();
	        this.entityStorage.removeSprint(sprint.getId());
	      }
	    }
	  }, {
	    key: "addTmpIdToSkipAdding",
	    value: function addTmpIdToSkipAdding(tmpId) {
	      this.listIdsToSkipAdding.add(tmpId);
	    }
	  }, {
	    key: "addIdToSkipUpdating",
	    value: function addIdToSkipUpdating(sprintId) {
	      this.listIdsToSkipUpdating.add(sprintId);
	    }
	  }, {
	    key: "addIdToSkipRemoving",
	    value: function addIdToSkipRemoving(sprintId) {
	      this.listIdsToSkipRemoving.add(sprintId);
	    }
	  }, {
	    key: "needSkipAdd",
	    value: function needSkipAdd(sprint) {
	      return this.listIdsToSkipAdding.has(sprint.getTmpId());
	    }
	  }, {
	    key: "cleanSkipAdd",
	    value: function cleanSkipAdd(sprint) {
	      this.listIdsToSkipAdding.delete(sprint.getTmpId());
	    }
	  }, {
	    key: "needSkipUpdate",
	    value: function needSkipUpdate(sprint) {
	      return this.listIdsToSkipUpdating.has(sprint.getId());
	    }
	  }, {
	    key: "cleanSkipUpdate",
	    value: function cleanSkipUpdate(sprint) {
	      this.listIdsToSkipUpdating.delete(sprint.getId());
	    }
	  }, {
	    key: "needSkipRemove",
	    value: function needSkipRemove(sprintId) {
	      return this.listIdsToSkipRemoving.has(sprintId);
	    }
	  }, {
	    key: "cleanSkipRemove",
	    value: function cleanSkipRemove(sprintId) {
	      this.listIdsToSkipRemoving.delete(sprintId);
	    }
	  }]);
	  return PullSprint;
	}();

	var ItemMover = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ItemMover, _EventEmitter);

	  function ItemMover(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, ItemMover);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemMover).call(this, params));
	    _this.requestSender = params.requestSender;
	    _this.domBuilder = params.domBuilder;
	    _this.entityStorage = params.entityStorage;
	    _this.subTasksCreator = params.subTasksCreator;

	    _this.bindHandlers();

	    return _this;
	  }

	  babelHelpers.createClass(ItemMover, [{
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      var _this2 = this;

	      this.domBuilder.subscribe('itemMoveStart', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();
	        var itemNode = dragEndEvent.source;
	        var itemId = itemNode.dataset.itemId;

	        var item = _this2.entityStorage.findItemByItemId(itemId);

	        var sourceContainer = dragEndEvent.sourceContainer;
	        var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);

	        var sourceEntity = _this2.entityStorage.findEntityByEntityId(sourceEntityId);

	        _this2.hideSubTasks(sourceEntity, item);
	      });
	      this.domBuilder.subscribe('itemMoveEnd', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();
	        var endContainer = dragEndEvent.endContainer;

	        if (!endContainer) {
	          return;
	        }

	        var sourceContainer = dragEndEvent.sourceContainer;
	        var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	        var endEntityId = parseInt(endContainer.dataset.entityId, 10);

	        var sourceEntity = _this2.entityStorage.findEntityByEntityId(sourceEntityId);

	        if (sourceEntityId === endEntityId) {
	          _this2.onItemMove(dragEndEvent);
	        } else {
	          var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

	          _this2.onMoveConfirm(sourceEntity, message).then(function () {
	            _this2.onItemMove(dragEndEvent);
	          }).catch(function () {
	            var itemNode = dragEndEvent.source;
	            var itemId = itemNode.dataset.itemId;

	            var item = _this2.entityStorage.findItemByItemId(itemId);

	            var itemNodeAfterSourceItem = sourceEntity.getListItemsNode().children[item.getSort()];

	            _this2.domBuilder.insertBefore(itemNode, itemNodeAfterSourceItem);
	          });
	        }
	      });
	    }
	  }, {
	    key: "moveItem",
	    value: function moveItem(item, button) {
	      var _this3 = this;

	      var entity = this.entityStorage.findEntityByItemId(item.getItemId());
	      var listToMove = [];

	      if (!entity.isFirstItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
	          onclick: function onclick(event, menuItem) {
	            if (entity.isGroupMode()) {
	              var groupModeItems = entity.getGroupModeItems();
	              var sortedItems = babelHelpers.toConsumableArray(groupModeItems.values()).sort(function (first, second) {
	                if (first.getSort() < second.getSort()) return 1;
	                if (first.getSort() > second.getSort()) return -1;
	              });
	              var sortedItemsIds = new Set();
	              sortedItems.forEach(function (groupModeItem) {
	                sortedItemsIds.add(groupModeItem.getItemId());

	                _this3.hideSubTasks(entity, groupModeItem);

	                _this3.moveItemToUp(groupModeItem, entity.getListItemsNode(), entity.hasInput(), false);
	              });

	              _this3.requestSender.updateItemSort({
	                sortInfo: _this3.calculateSort(entity.getListItemsNode(), sortedItemsIds)
	              }).catch(function (response) {
	                _this3.requestSender.showErrorAlert(response);
	              });

	              entity.deactivateGroupMode();
	            } else {
	              _this3.hideSubTasks(entity, item);

	              _this3.moveItemToUp(item, entity.getListItemsNode(), entity.hasInput());
	            }

	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      if (!entity.isLastItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
	          onclick: function onclick(event, menuItem) {
	            if (entity.isGroupMode()) {
	              var groupModeItems = entity.getGroupModeItems();
	              var sortedItems = babelHelpers.toConsumableArray(groupModeItems.values()).sort(function (first, second) {
	                if (first.getSort() > second.getSort()) return 1;
	                if (first.getSort() < second.getSort()) return -1;
	              });
	              var sortedItemsIds = new Set();
	              sortedItems.forEach(function (groupModeItem) {
	                sortedItemsIds.add(groupModeItem.getItemId());

	                _this3.hideSubTasks(entity, groupModeItem);

	                _this3.moveItemToDown(groupModeItem, entity.getListItemsNode(), false);
	              });

	              _this3.requestSender.updateItemSort({
	                sortInfo: _this3.calculateSort(entity.getListItemsNode(), sortedItemsIds)
	              }).catch(function (response) {
	                _this3.requestSender.showErrorAlert(response);
	              });

	              entity.deactivateGroupMode();
	            } else {
	              _this3.hideSubTasks(entity, item);

	              _this3.moveItemToDown(item, entity.getListItemsNode());
	            }

	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      this.showMoveItemMenu(item, button, listToMove);
	    }
	  }, {
	    key: "moveItemToUp",
	    value: function moveItemToUp(item, listItemsNode) {
	      var entityWithInput = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      var updateSort = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      this.hideSubTasks(entity, item);

	      if (entityWithInput) {
	        this.domBuilder.appendItemAfterItem(item.getItemNode(), listItemsNode.firstElementChild);
	      } else {
	        this.domBuilder.insertBefore(item.getItemNode(), listItemsNode.firstElementChild);
	      }

	      if (updateSort) {
	        this.updateItemsSort(item, listItemsNode);
	      }
	    }
	  }, {
	    key: "moveItemToDown",
	    value: function moveItemToDown(item, listItemsNode) {
	      var updateSort = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      this.domBuilder.append(item.getItemNode(), listItemsNode);

	      if (updateSort) {
	        this.updateItemsSort(item, listItemsNode);
	      }
	    }
	  }, {
	    key: "updateItemsSort",
	    value: function updateItemsSort(item, listItemsNode) {
	      var _this4 = this;

	      this.requestSender.updateItem({
	        itemId: item.getItemId(),
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(listItemsNode, new Set([item.getItemId()])))
	      }).catch(function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onItemMove",
	    value: function onItemMove(dragEndEvent) {
	      var _this5 = this;

	      if (!dragEndEvent.endContainer) {
	        return;
	      }

	      var sourceContainer = dragEndEvent.sourceContainer;
	      var endContainer = dragEndEvent.endContainer;

	      if (endContainer === this.domBuilder.getSprintCreatingDropZoneNode()) {
	        var createNewSprintAndMoveItem = function createNewSprintAndMoveItem() {
	          _this5.domBuilder.createSprint().then(function (sprint) {
	            var itemNode = dragEndEvent.source;
	            var itemId = itemNode.dataset.itemId;

	            var item = _this5.entityStorage.findItemByItemId(itemId);

	            _this5.moveTo(_this5.entityStorage.getBacklog(), sprint, item);
	          });
	        };

	        createNewSprintAndMoveItem();
	        return;
	      }

	      var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	      var endEntityId = parseInt(endContainer.dataset.entityId, 10);

	      if (sourceEntityId === endEntityId) {
	        var moveInCurrentContainer = function moveInCurrentContainer() {
	          var itemNode = dragEndEvent.source;
	          var itemId = parseInt(itemNode.dataset.itemId, 10);

	          _this5.requestSender.updateItemSort({
	            sortInfo: _this5.calculateSort(sourceContainer, new Set([itemId]))
	          }).catch(function (response) {
	            _this5.requestSender.showErrorAlert(response);
	          });
	        };

	        moveInCurrentContainer();
	      } else {
	        var moveInAnotherContainer = function moveInAnotherContainer() {
	          var itemNode = dragEndEvent.source;
	          var itemId = itemNode.dataset.itemId;

	          var item = _this5.entityStorage.findItemByItemId(itemId);

	          var sourceEntity = _this5.entityStorage.findEntityByEntityId(sourceEntityId);

	          var endEntity = _this5.entityStorage.findEntityByEntityId(endEntityId);

	          _this5.moveItemFromEntityToEntity(item, sourceEntity, endEntity);

	          _this5.requestSender.updateItemSort({
	            entityId: endEntity.getId(),
	            itemId: item.getItemId(),
	            itemType: item.getItemType(),
	            sourceEntityId: sourceEntity.getId(),
	            fromActiveSprint: sourceEntity.getEntityType() === 'sprint' && sourceEntity.isActive() ? 'Y' : 'N',
	            toActiveSprint: endEntity.getEntityType() === 'sprint' && endEntity.isActive() ? 'Y' : 'N',
	            sortInfo: _this5.calculateSort(endContainer, new Set([item.getItemId()]), true)
	          }).catch(function (response) {
	            _this5.requestSender.showErrorAlert(response);
	          });
	        };

	        moveInAnotherContainer();
	      }
	    }
	  }, {
	    key: "calculateSort",
	    value: function calculateSort(container, updatedItemsIds) {
	      var _this6 = this;

	      var moveToAnotherEntity = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      var listSortInfo = {};
	      var items = babelHelpers.toConsumableArray(container.querySelectorAll('[data-sort]'));
	      var sort = 1;
	      items.forEach(function (itemNode) {
	        var itemId = parseInt(itemNode.dataset.itemId, 10);

	        var item = _this6.entityStorage.findItemByItemId(itemId);

	        if (item && !item.isSubTask()) {
	          var tmpId = main_core.Text.getRandom();
	          var isSortUpdated = sort !== item.getSort();
	          item.setSort(sort);
	          listSortInfo[itemId] = {
	            sort: sort
	          };

	          if (moveToAnotherEntity) {
	            listSortInfo[itemId].entityId = container.dataset.entityId;
	            isSortUpdated = true;
	          }

	          if (isSortUpdated && updatedItemsIds && updatedItemsIds.has(itemId)) {
	            listSortInfo[itemId].tmpId = tmpId;
	            listSortInfo[itemId].updatedItemId = itemId;
	          }

	          itemNode.dataset.sort = sort;
	        }

	        sort++;
	      });
	      this.emit('calculateSort', listSortInfo);
	      return listSortInfo;
	    }
	  }, {
	    key: "moveToAnotherEntity",
	    value: function moveToAnotherEntity(entityFrom, item, targetEntity, bindButton) {
	      var _this7 = this;

	      var isMoveToSprint = main_core.Type.isNull(targetEntity);
	      var sprints = isMoveToSprint ? this.entityStorage.getSprintsAvailableForFilling(entityFrom) : null;

	      if (entityFrom.isGroupMode()) {
	        if (isMoveToSprint) {
	          if (sprints.size > 1) {
	            this.showListSprintsToMove(entityFrom, item, bindButton);
	          } else {
	            if (sprints.size === 0) {
	              this.domBuilder.createSprint().then(function (sprint) {
	                _this7.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	              });
	            } else {
	              sprints.forEach(function (sprint) {
	                _this7.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	              });
	            }
	          }
	        } else {
	          var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');
	          this.onMoveConfirm(entityFrom, message).then(function () {
	            _this7.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
	          });
	        }
	      } else {
	        if (isMoveToSprint) {
	          if (sprints.size > 1) {
	            this.showListSprintsToMove(entityFrom, item, bindButton);
	          } else {
	            var _message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

	            this.onMoveConfirm(entityFrom, _message).then(function () {
	              if (sprints.size === 0) {
	                _this7.domBuilder.createSprint().then(function (sprint) {
	                  _this7.moveTo(entityFrom, sprint, item);
	                });
	              } else {
	                sprints.forEach(function (sprint) {
	                  _this7.moveTo(entityFrom, sprint, item);
	                });
	              }
	            });
	          }
	        } else {
	          var _message2 = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

	          this.onMoveConfirm(entityFrom, _message2).then(function () {
	            _this7.moveTo(entityFrom, targetEntity, item, false);
	          });
	        }
	      }
	    }
	  }, {
	    key: "moveToWithGroupMode",
	    value: function moveToWithGroupMode(entityFrom, entityTo, item) {
	      var _this8 = this;

	      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      var update = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;
	      var groupModeItems = entityFrom.getGroupModeItems();
	      var sortedItems = babelHelpers.toConsumableArray(groupModeItems.values()).sort(function (first, second) {
	        if (after) {
	          if (first.getSort() > second.getSort()) return 1;
	          if (first.getSort() < second.getSort()) return -1;
	        } else {
	          if (first.getSort() < second.getSort()) return 1;
	          if (first.getSort() > second.getSort()) return -1;
	        }
	      });
	      var sortedItemsIds = new Set();
	      var items = [];
	      sortedItems.forEach(function (groupModeItem) {
	        _this8.moveTo(entityFrom, entityTo, groupModeItem, after, update);

	        sortedItemsIds.add(groupModeItem.getItemId());
	        items.push({
	          itemId: groupModeItem.getItemId(),
	          itemType: groupModeItem.getItemType(),
	          entityId: entityTo.getId(),
	          sourceEntityId: entityFrom.getId(),
	          fromActiveSprint: entityFrom.getEntityType() === 'sprint' && entityFrom.isActive() ? 'Y' : 'N',
	          toActiveSprint: entityTo.getEntityType() === 'sprint' && entityTo.isActive() ? 'Y' : 'N'
	        });
	      });
	      this.requestSender.batchUpdateItem({
	        items: items,
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(entityTo.getListItemsNode(), sortedItemsIds, true), this.calculateSort(entityFrom.getListItemsNode(), new Set(), true))
	      }).catch(function (response) {
	        _this8.requestSender.showErrorAlert(response);
	      });
	      entityFrom.deactivateGroupMode();
	    }
	  }, {
	    key: "hideSubTasks",
	    value: function hideSubTasks(entity, item) {
	      if (item && item.isParentTask()) {
	        if (this.subTasksCreator.isShown(item)) {
	          this.subTasksCreator.toggleSubTasks(entity, item);
	        }
	      }
	    }
	  }, {
	    key: "moveTo",
	    value: function moveTo(entityFrom, entityTo, item) {
	      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      var update = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;
	      this.hideSubTasks(entityFrom, item);
	      var itemNode = item.getItemNode();
	      var entityListNode = entityTo.getListItemsNode();

	      if (after) {
	        this.domBuilder.append(itemNode, entityListNode);
	      } else {
	        this.domBuilder.appendItemAfterItem(itemNode, entityListNode.firstElementChild);
	      }

	      this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

	      if (update) {
	        this.onMoveItemUpdate(entityFrom, entityTo, item);
	      }
	    }
	  }, {
	    key: "moveToPosition",
	    value: function moveToPosition(entityFrom, entityTo, item) {
	      this.hideSubTasks(entityFrom, item);
	      var itemNode = item.getItemNode();
	      var entityListNode = entityTo.getListItemsNode();
	      var bindItemNode = entityListNode.children[item.getSort()];

	      if (main_core.Type.isUndefined(bindItemNode)) {
	        this.domBuilder.append(itemNode, entityListNode);
	      } else {
	        var bindItemSort = parseInt(bindItemNode.dataset.sort, 10);
	        var isMoveFromAnotherEntity = entityFrom.getId() !== entityTo.getId();

	        if (bindItemSort >= item.getPreviousSort()) {
	          if (isMoveFromAnotherEntity) {
	            this.domBuilder.insertBefore(itemNode, bindItemNode);
	          } else {
	            this.domBuilder.appendItemAfterItem(itemNode, bindItemNode);
	          }
	        } else {
	          this.domBuilder.insertBefore(itemNode, bindItemNode);
	        }
	      }

	      this.moveItemFromEntityToEntity(item, entityFrom, entityTo);
	    }
	  }, {
	    key: "onMoveItemUpdate",
	    value: function onMoveItemUpdate(entityFrom, entityTo, item) {
	      var _this9 = this;

	      this.requestSender.updateItem({
	        itemId: item.getItemId(),
	        itemType: item.getItemType(),
	        entityId: entityTo.getId(),
	        sourceEntityId: entityFrom.getId(),
	        fromActiveSprint: entityFrom.getEntityType() === 'sprint' && entityFrom.isActive() ? 'Y' : 'N',
	        toActiveSprint: entityTo.getEntityType() === 'sprint' && entityTo.isActive() ? 'Y' : 'N',
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(entityTo.getListItemsNode(), new Set([item.getItemId()]), true), this.calculateSort(entityFrom.getListItemsNode(), new Set(), true))
	      }).catch(function (response) {
	        _this9.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "moveItemFromEntityToEntity",
	    value: function moveItemFromEntityToEntity(item, entityFrom, entityTo) {
	      if (entityFrom.isActive()) {
	        entityFrom.subtractTotalStoryPoints(item);
	      }

	      if (entityTo.isActive()) {
	        entityTo.addTotalStoryPoints(item);
	      }

	      entityFrom.removeItem(item);
	      item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
	      item.setDisableStatus(false);
	      entityTo.setItem(item);
	    }
	  }, {
	    key: "showListSprintsToMove",
	    value: function showListSprintsToMove(entityFrom, item, button) {
	      var _this10 = this;

	      var id = "item-sprint-action-".concat(entityFrom.getEntityType() + entityFrom.getId() + item.itemId);

	      if (this.moveToSprintMenu) {
	        if (this.moveToSprintMenu.getPopupWindow().getId() === id) {
	          this.moveToSprintMenu.getPopupWindow().setBindElement(button);
	          this.moveToSprintMenu.show();
	          return;
	        }

	        this.moveToSprintMenu.getPopupWindow().destroy();
	      }

	      this.moveToSprintMenu = new main_popup.Menu({
	        id: id,
	        bindElement: button
	      });
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isCompleted() && !_this10.isSameSprint(entityFrom, sprint)) {
	          _this10.moveToSprintMenu.addMenuItem({
	            text: sprint.getName(),
	            onclick: function onclick(event, menuItem) {
	              var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

	              if (entityFrom.isGroupMode()) {
	                message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');
	              }

	              _this10.onMoveConfirm(entityFrom, message).then(function () {
	                if (entityFrom.isGroupMode()) {
	                  _this10.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	                } else {
	                  _this10.moveTo(entityFrom, sprint, item);
	                }
	              });

	              menuItem.getMenuWindow().close();
	            }
	          });
	        }
	      });
	      this.moveToSprintMenu.show();
	    }
	  }, {
	    key: "isSameSprint",
	    value: function isSameSprint(first, second) {
	      return first.getEntityType() === 'sprint' && first.getId() === second.getId();
	    }
	  }, {
	    key: "onMoveConfirm",
	    value: function onMoveConfirm(entity, message) {
	      return new Promise(function (resolve, reject) {
	        if (entity.isActive()) {
	          ui_dialogs_messagebox.MessageBox.confirm(message, function (messageBox) {
	            messageBox.close();
	            resolve();
	          }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'), function (messageBox) {
	            messageBox.close();
	            reject();
	          });
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "showMoveItemMenu",
	    value: function showMoveItemMenu(item, button, listToMove) {
	      var _this11 = this;

	      var id = "item-move-".concat(item.itemId);

	      if (this.moveItemMenu) {
	        this.moveItemMenu.getPopupWindow().destroy();
	      }

	      this.moveItemMenu = new main_popup.Menu({
	        id: id,
	        bindElement: button
	      });
	      listToMove.forEach(function (item) {
	        _this11.moveItemMenu.addMenuItem(item);
	      });
	      this.moveItemMenu.show();
	    }
	  }]);
	  return ItemMover;
	}(main_core_events.EventEmitter);

	var PullItem = /*#__PURE__*/function () {
	  function PullItem(params) {
	    babelHelpers.classCallCheck(this, PullItem);
	    this.requestSender = params.requestSender;
	    this.domBuilder = params.domBuilder;
	    this.entityStorage = params.entityStorage;
	    this.tagSearcher = params.tagSearcher;
	    this.itemMover = params.itemMover;
	    this.subTasksCreator = params.subTasksCreator;
	    this.counters = params.counters;
	    this.currentUserId = params.currentUserId;
	    this.listToAddAfterUpdate = new Map();
	    this.listIdsToSkipAdding = new Set();
	    this.listIdsToSkipUpdating = new Set();
	    this.listIdsToSkipRemoving = new Set();
	    this.listIdsToSkipSorting = new Set();
	    this.itemMover.subscribe('calculateSort', this.onCalculateSort.bind(this));
	  }

	  babelHelpers.createClass(PullItem, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'tasks';
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return {
	        itemAdded: this.onItemAdded.bind(this),
	        itemUpdated: this.onItemUpdated.bind(this),
	        itemRemoved: this.onItemRemoved.bind(this),
	        itemSortUpdated: this.onItemSortUpdated.bind(this),
	        comment_add: this.onCommentAdd.bind(this)
	      };
	    }
	  }, {
	    key: "onItemAdded",
	    value: function onItemAdded(itemData) {
	      var _this = this;

	      var item = new Item(itemData);
	      this.setDelayedAdd(item);
	      this.externalAdd(item).finally(function () {
	        return _this.cleanDelayedAdd(item);
	      }).then(function () {
	        return _this.addItemToEntity(item);
	      }).catch(function () {});
	    }
	  }, {
	    key: "onItemUpdated",
	    value: function onItemUpdated(itemData) {
	      var item = new Item(itemData);

	      if (this.isDelayedAdd(item)) {
	        this.cleanDelayedAdd(item);

	        if (this.needSkipAdd(item)) {
	          this.cleanSkipAdd(item);
	          return;
	        }

	        this.addItemToEntity(item);
	        return;
	      }

	      if (this.needSkipUpdate(item)) {
	        this.cleanSkipUpdate(item);
	        return;
	      }

	      this.updateItem(item);
	    }
	  }, {
	    key: "onItemRemoved",
	    value: function onItemRemoved(params) {
	      if (this.needSkipRemove(params.itemId)) {
	        this.cleanSkipRemove(params.itemId);
	        return;
	      }

	      var item = this.entityStorage.findItemByItemId(params.itemId);

	      if (item) {
	        var entity = this.entityStorage.findEntityByItemId(item.getItemId());
	        entity.removeItem(item);
	        item.removeYourself();
	      }
	    }
	  }, {
	    key: "onItemSortUpdated",
	    value: function onItemSortUpdated(itemsSortInfo) {
	      var _this2 = this;

	      var itemsToSort = new Map();
	      var itemsInfoToSort = new Map();
	      Object.entries(itemsSortInfo).forEach(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            itemId = _ref2[0],
	            info = _ref2[1];

	        var item = _this2.entityStorage.findItemByItemId(itemId);

	        if (item) {
	          if (!_this2.needSkipSort(info.tmpId)) {
	            itemsToSort.set(item.getItemId(), item);
	            itemsInfoToSort.set(item.getItemId(), info);
	          }

	          _this2.cleanSkipRemove(info.tmpId);
	        }
	      });
	      itemsToSort.forEach(function (item) {
	        var itemInfoToSort = itemsInfoToSort.get(item.getItemId());
	        item.setSort(itemInfoToSort.sort);

	        var sourceEntity = _this2.entityStorage.findEntityByEntityId(item.getEntityId());

	        if (sourceEntity) {
	          var targetEntityId = main_core.Type.isUndefined(itemInfoToSort.entityId) ? item.getEntityId() : itemInfoToSort.entityId;

	          var targetEntity = _this2.entityStorage.findEntityByEntityId(targetEntityId);

	          if (!targetEntity || sourceEntity.getId() === targetEntity.getId()) {
	            targetEntity = sourceEntity;
	          }

	          _this2.itemMover.moveToPosition(sourceEntity, targetEntity, item);

	          _this2.entityStorage.recalculateItemsSort();
	        }
	      });
	    }
	  }, {
	    key: "onCommentAdd",
	    value: function onCommentAdd(params) {
	      var _this3 = this;

	      var participants = main_core.Type.isArray(params.participants) ? params.participants : [];

	      if (participants.includes(this.currentUserId.toString())) {
	        var xmlId = params.entityXmlId.split('_');

	        if (xmlId) {
	          var entityType = xmlId[0];
	          var taskId = xmlId[1];
	          var item = this.entityStorage.findItemBySourceId(taskId);

	          if (entityType === 'TASK' && item) {
	            this.requestSender.getCurrentState({
	              taskId: item.getSourceId()
	            }).then(function (response) {
	              var tmpItem = new Item(response.data.itemData);

	              _this3.updateItem(tmpItem, item);

	              _this3.counters.updateState(response.data.counters);
	            }).catch(function (response) {
	              _this3.requestSender.showErrorAlert(response);
	            });
	          }
	        }
	      }
	    }
	  }, {
	    key: "addItemToEntity",
	    value: function addItemToEntity(item) {
	      var _this4 = this;

	      if (item.isSubTask()) {
	        return;
	      }

	      var entity = this.entityStorage.findEntityByEntityId(item.getEntityId());

	      if (!entity) {
	        return;
	      }

	      var bindItemNode = entity.getListItemsNode().children[1];

	      if (bindItemNode) {
	        this.domBuilder.insertBefore(item.render(), bindItemNode);
	      } else {
	        this.domBuilder.append(item.render(), entity.getListItemsNode());
	      }

	      item.onAfterAppend(entity.getListItemsNode());
	      entity.setItem(item);
	      item.getTags().forEach(function (tag) {
	        _this4.tagSearcher.addTagToSearcher(tag);
	      });
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(tmpItem, item) {
	      if (!item) {
	        item = this.entityStorage.findItemByItemId(tmpItem.getItemId());
	      }

	      if (item) {
	        if (item.isParentTask()) {
	          if (this.subTasksCreator.isShown(item)) {
	            var entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
	            this.subTasksCreator.hideSubTaskItems(entity, item);
	          }

	          this.subTasksCreator.cleanSubTasks(item);
	        }

	        var isParentChangeAction = tmpItem.isSubTask() !== item.isSubTask();

	        if (isParentChangeAction) {
	          if (tmpItem.isSubTask()) {
	            var _entity = this.entityStorage.findEntityByItemId(item.getItemId());

	            _entity.removeItem(item);

	            item.removeYourself();
	          }

	          return;
	        }

	        if (tmpItem.getEntityId() !== item.getEntityId()) {
	          var targetEntityId = tmpItem.getEntityId();
	          var sourceEntityId = item.getEntityId();
	          var targetEntity = this.entityStorage.findEntityByEntityId(targetEntityId);
	          var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

	          if (targetEntity && sourceEntity) {
	            this.itemMover.moveToPosition(sourceEntity, targetEntity, item);
	            this.entityStorage.recalculateItemsSort();
	          }
	        }

	        item.updateYourself(tmpItem);
	      } else {
	        this.addItemToEntity(tmpItem);
	      }
	    }
	  }, {
	    key: "onCalculateSort",
	    value: function onCalculateSort(baseEvent) {
	      var _this5 = this;

	      var listSortInfo = baseEvent.getData();
	      Object.entries(listSortInfo).forEach(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	            itemId = _ref4[0],
	            info = _ref4[1];

	        if (Object.prototype.hasOwnProperty.call(info, 'tmpId')) {
	          _this5.addTmpIdToSkipSorting(info.tmpId);
	        }
	      });
	    }
	  }, {
	    key: "addTmpIdsToSkipAdding",
	    value: function addTmpIdsToSkipAdding(tmpId) {
	      this.listIdsToSkipAdding.add(tmpId);
	    }
	  }, {
	    key: "addIdToSkipUpdating",
	    value: function addIdToSkipUpdating(itemId) {
	      this.listIdsToSkipUpdating.add(itemId);
	    }
	  }, {
	    key: "addIdToSkipRemoving",
	    value: function addIdToSkipRemoving(itemId) {
	      this.listIdsToSkipRemoving.add(itemId);
	    }
	  }, {
	    key: "addTmpIdToSkipSorting",
	    value: function addTmpIdToSkipSorting(itemId) {
	      this.listIdsToSkipSorting.add(itemId);
	    }
	  }, {
	    key: "externalAdd",
	    value: function externalAdd(item) {
	      var _this6 = this;

	      return new Promise(function (resolve, reject) {
	        setTimeout(function () {
	          return _this6.isDelayedAdd(item) ? resolve() : reject();
	        }, 3000);
	      });
	    }
	  }, {
	    key: "isDelayedAdd",
	    value: function isDelayedAdd(item) {
	      return this.listToAddAfterUpdate.has(item.getItemId());
	    }
	  }, {
	    key: "setDelayedAdd",
	    value: function setDelayedAdd(item) {
	      this.listToAddAfterUpdate.set(item.getItemId(), item);
	    }
	  }, {
	    key: "cleanDelayedAdd",
	    value: function cleanDelayedAdd(item) {
	      this.listToAddAfterUpdate.delete(item.getItemId());
	    }
	  }, {
	    key: "needSkipAdd",
	    value: function needSkipAdd(item) {
	      return this.listIdsToSkipAdding.has(item.getTmpId());
	    }
	  }, {
	    key: "cleanSkipAdd",
	    value: function cleanSkipAdd(item) {
	      this.listIdsToSkipAdding.delete(item.getTmpId());
	    }
	  }, {
	    key: "needSkipUpdate",
	    value: function needSkipUpdate(item) {
	      return this.listIdsToSkipUpdating.has(item.getItemId());
	    }
	  }, {
	    key: "cleanSkipUpdate",
	    value: function cleanSkipUpdate(item) {
	      this.listIdsToSkipUpdating.delete(item.getItemId());
	    }
	  }, {
	    key: "needSkipRemove",
	    value: function needSkipRemove(itemId) {
	      return this.listIdsToSkipRemoving.has(itemId);
	    }
	  }, {
	    key: "cleanSkipRemove",
	    value: function cleanSkipRemove(itemId) {
	      this.listIdsToSkipRemoving.delete(itemId);
	    }
	  }, {
	    key: "needSkipSort",
	    value: function needSkipSort(tmpId) {
	      return this.listIdsToSkipSorting.has(tmpId);
	    }
	  }, {
	    key: "cleanSkipSort",
	    value: function cleanSkipSort(tmpId) {
	      this.listIdsToSkipSorting.delete(tmpId);
	    }
	  }]);
	  return PullItem;
	}();

	var PullEpic = /*#__PURE__*/function () {
	  function PullEpic(params) {
	    babelHelpers.classCallCheck(this, PullEpic);
	    this.requestSender = params.requestSender;
	    this.domBuilder = params.domBuilder;
	    this.entityStorage = params.entityStorage;
	    this.epic = params.epic;
	    this.listIdsToSkipAdding = new Set();
	    this.listIdsToSkipUpdating = new Set();
	    this.listIdsToSkipRemoving = new Set();
	  }

	  babelHelpers.createClass(PullEpic, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'tasks';
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return {
	        epicAdded: this.onEpicAdded.bind(this),
	        epicUpdated: this.onEpicUpdated.bind(this),
	        epicRemoved: this.onEpicRemoved.bind(this)
	      };
	    }
	  }, {
	    key: "onEpicAdded",
	    value: function onEpicAdded(epicData) {
	      this.epic.onAfterCreateEpic(epicData);
	    }
	  }, {
	    key: "onEpicUpdated",
	    value: function onEpicUpdated(epicData) {
	      this.epic.onAfterUpdateEpic(epicData);
	    }
	  }, {
	    key: "onEpicRemoved",
	    value: function onEpicRemoved(epicData) {
	      this.epic.onAfterRemoveEpic(epicData);
	    }
	  }]);
	  return PullEpic;
	}();

	var SprintMover = /*#__PURE__*/function () {
	  function SprintMover(params) {
	    babelHelpers.classCallCheck(this, SprintMover);
	    this.requestSender = params.requestSender;
	    this.domBuilder = params.domBuilder;
	    this.entityStorage = params.entityStorage;
	    this.bindHandlers();
	  }

	  babelHelpers.createClass(SprintMover, [{
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      this.domBuilder.subscribe('sprintMove', this.onSprintMove.bind(this));
	    }
	  }, {
	    key: "onSprintMove",
	    value: function onSprintMove(baseEvent) {
	      var _this = this;

	      var dragEndEvent = baseEvent.getData();

	      if (!dragEndEvent.endContainer) {
	        return;
	      }

	      this.requestSender.updateSprintSort({
	        sortInfo: this.calculateSprintSort()
	      }).catch(function (response) {
	        _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "calculateSprintSort",
	    value: function calculateSprintSort() {
	      var _this2 = this;

	      var increment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      var listSortInfo = {};
	      var container = this.domBuilder.getSprintPlannedListNode();
	      var sprints = babelHelpers.toConsumableArray(container.querySelectorAll('[data-sprint-sort]'));
	      var sort = 1 + increment;
	      sprints.forEach(function (sprintNode) {
	        var sprintId = sprintNode.dataset.sprintId;

	        var sprint = _this2.entityStorage.findEntityByEntityId(sprintId);

	        if (sprint) {
	          sprint.setSort(sort);
	          listSortInfo[sprintId] = {
	            sort: sort
	          };
	          sprintNode.dataset.sprintSort = sort;
	          sort++;
	        }
	      });
	      return listSortInfo;
	    }
	  }]);
	  return SprintMover;
	}();

	var Plan = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(Plan, _View);

	  function Plan(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Plan);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Plan).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Plan');

	    _this.pathToTask = params.pathToTask;
	    _this.defaultResponsible = params.defaultResponsible;
	    _this.activeSprintId = parseInt(params.activeSprintId, 10);
	    _this.views = params.views;
	    _this.entityStorage = new EntityStorage();

	    _this.entityStorage.addBacklog(Backlog.buildBacklog(params.backlog));

	    params.sprints.forEach(function (sprintData) {
	      sprintData.defaultSprintDuration = params.defaultSprintDuration;
	      var sprint = Sprint.buildSprint(sprintData);

	      _this.entityStorage.addSprint(sprint);
	    });
	    _this.sidePanel = new SidePanel();
	    _this.filterHandler = new FilterHandler({
	      filter: _this.filter,
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage
	    });
	    _this.tagSearcher = new TagSearcher();
	    Object.values(params.tags.epic).forEach(function (epic) {
	      _this.tagSearcher.addEpicToSearcher(epic);
	    });
	    Object.values(params.tags.task).forEach(function (tagName) {
	      _this.tagSearcher.addTagToSearcher(tagName);
	    });
	    _this.domBuilder = new DomBuilder({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      defaultSprintDuration: params.defaultSprintDuration
	    });

	    _this.domBuilder.subscribe('beforeCreateSprint', function (baseEvent) {
	      var requestData = baseEvent.getData();

	      _this.pullSprint.addTmpIdToSkipAdding(requestData.tmpId);
	    });

	    _this.domBuilder.subscribe('createSprint', function (baseEvent) {
	      _this.bindHandlers(baseEvent.getData());
	    });

	    _this.domBuilder.subscribe('createSprintNode', function (baseEvent) {
	      _this.bindHandlers(baseEvent.getData());
	    });

	    _this.sprintMover = new SprintMover({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder,
	      entityStorage: _this.entityStorage
	    });
	    _this.subTasksCreator = new SubTasksManager({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder
	    });
	    _this.itemMover = new ItemMover({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder,
	      entityStorage: _this.entityStorage,
	      subTasksCreator: _this.subTasksCreator
	    });
	    _this.itemStyleDesigner = new ItemStyleDesigner({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage
	    });
	    _this.epic = new Epic({
	      entity: _this.entityStorage.getBacklog(),
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      sidePanel: _this.sidePanel,
	      filter: _this.filter,
	      tagSearcher: _this.tagSearcher
	    });
	    _this.pullSprint = new PullSprint({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder,
	      entityStorage: _this.entityStorage,
	      groupId: _this.groupId
	    });
	    _this.pullItem = new PullItem({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder,
	      entityStorage: _this.entityStorage,
	      tagSearcher: _this.tagSearcher,
	      itemMover: _this.itemMover,
	      subTasksCreator: _this.subTasksCreator,
	      counters: _this.counters,
	      currentUserId: _this.getCurrentUserId()
	    });
	    _this.pullEpic = new PullEpic({
	      requestSender: _this.requestSender,
	      domBuilder: _this.domBuilder,
	      entityStorage: _this.entityStorage,
	      epic: _this.epic
	    });

	    _this.bindHandlers();

	    _this.subscribeToPull();

	    return _this;
	  }

	  babelHelpers.createClass(Plan, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Plan.prototype), "renderTo", this).call(this, container);
	      this.domBuilder.renderTo(container);
	    }
	  }, {
	    key: "subscribeToPull",
	    value: function subscribeToPull() {
	      pull_client.PULL.subscribe(this.pullSprint);
	      pull_client.PULL.subscribe(this.pullItem);
	      pull_client.PULL.subscribe(this.pullEpic);
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers(newSprint) {
	      var _this2 = this;

	      this.teamSpeedChartButtonContainerNode = document.getElementById('tasks-scrum-team-speed-button-container');
	      main_core.Event.bind(this.teamSpeedChartButtonContainerNode, 'click', this.onShowTeamSpeedChart.bind(this));

	      var createTaskItem = function createTaskItem(baseEvent) {
	        var data = baseEvent.getData();
	        var entity = baseEvent.getTarget();
	        var inputObject = data.inputObject;
	        var inputValue = data.value;

	        var newItem = _this2.createItem('task', inputValue);

	        _this2.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());

	        _this2.fillItemBeforeCreation(entity, newItem, inputValue);

	        _this2.domBuilder.appendItemAfterItem(newItem.render(), inputObject.getNode());

	        newItem.onAfterAppend(entity.getListItemsNode());
	        newItem.setParentId(inputObject.getEpicId());
	        inputObject.setEpicId(0);

	        _this2.sendRequestToCreateTask(entity, newItem, inputValue).then(function (response) {
	          _this2.fillItemAfterCreation(newItem, response.data);

	          response.data.tags.forEach(function (tag) {
	            _this2.tagSearcher.addTagToSearcher(tag);
	          });
	          entity.setItem(newItem);
	        }).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onUpdateItem = function onUpdateItem(baseEvent) {
	        var updateData = baseEvent.getData();

	        _this2.pullItem.addIdToSkipUpdating(updateData.itemId);

	        _this2.requestSender.updateItem(baseEvent.getData()).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onShowTask = function onShowTask(baseEvent) {
	        var item = baseEvent.getData();

	        _this2.sidePanel.openSidePanelByUrl(_this2.pathToTask.replace('#task_id#', item.getSourceId()));
	      };

	      var onMoveItem = function onMoveItem(baseEvent) {
	        var data = baseEvent.getData();

	        _this2.itemMover.moveItem(data.item, data.button);
	      };

	      var onMoveToSprint = function onMoveToSprint(baseEvent) {
	        var data = baseEvent.getData();
	        var entityFrom = baseEvent.getTarget();

	        _this2.itemMover.moveToAnotherEntity(entityFrom, data.item, null, data.button);

	        if (_this2.entityStorage.getSprintsAvailableForFilling(entityFrom).size <= 1) {
	          _this2.domBuilder.remove(data.button.parentNode);
	        }
	      };

	      var onMoveToBacklog = function onMoveToBacklog(baseEvent) {
	        var data = baseEvent.getData();

	        _this2.itemMover.moveToAnotherEntity(data.sprint, data.item, _this2.entityStorage.getBacklog());
	      };

	      var onAttachFilesToTask = function onAttachFilesToTask(baseEvent) {
	        var data = baseEvent.getData();

	        _this2.pullItem.addIdToSkipUpdating(data.item.getItemId());

	        _this2.requestSender.attachFilesToTask({
	          taskId: data.item.getSourceId(),
	          itemId: data.item.getItemId(),
	          entityId: data.item.getEntityId(),
	          attachedIds: data.attachedIds
	        }).then(function (response) {
	          data.item.updateIndicators(response.data);
	        }).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onRemoveItem = function onRemoveItem(baseEvent) {
	        var entity = baseEvent.getTarget();

	        if (entity.isGroupMode()) {
	          var items = [];
	          entity.getGroupModeItems().forEach(function (groupModeItem) {
	            items.push({
	              itemId: groupModeItem.getItemId(),
	              entityId: groupModeItem.getEntityId(),
	              itemType: groupModeItem.getItemType(),
	              sourceId: groupModeItem.getSourceId()
	            });

	            _this2.pullItem.addIdToSkipRemoving(groupModeItem.getItemId());
	          });

	          _this2.requestSender.batchRemoveItem({
	            items: items,
	            sortInfo: _this2.itemMover.calculateSort(entity.getListItemsNode())
	          }).catch(function (response) {
	            _this2.requestSender.showErrorAlert(response);
	          });

	          entity.deactivateGroupMode();
	        } else {
	          _this2.pullItem.addIdToSkipRemoving(baseEvent.getData().getItemId());

	          _this2.requestSender.removeItem({
	            itemId: baseEvent.getData().getItemId(),
	            entityId: baseEvent.getData().getEntityId(),
	            itemType: baseEvent.getData().getItemType(),
	            sourceId: baseEvent.getData().getSourceId(),
	            sortInfo: _this2.itemMover.calculateSort(entity.getListItemsNode())
	          }).catch(function (response) {
	            _this2.requestSender.showErrorAlert(response);
	          });
	        }
	      };

	      var onStartSprint = function onStartSprint(baseEvent) {
	        var sprint = baseEvent.getTarget();
	        var sprintSidePanel = new SprintSidePanel({
	          sprints: _this2.entityStorage.getSprints(),
	          sidePanel: _this2.sidePanel,
	          requestSender: _this2.requestSender,
	          views: _this2.views
	        });
	        sprintSidePanel.showStartSidePanel(sprint);
	      };

	      var onCompleteSprint = function onCompleteSprint(baseEvent) {
	        var sprint = baseEvent.getTarget();
	        var sprintSidePanel = new SprintSidePanel({
	          sprints: _this2.entityStorage.getSprints(),
	          sidePanel: _this2.sidePanel,
	          requestSender: _this2.requestSender,
	          views: _this2.views
	        });
	        sprintSidePanel.showCompleteSidePanel(sprint);
	      };

	      var onShowSprintBurnDownChart = function onShowSprintBurnDownChart(baseEvent) {
	        var sprint = baseEvent.getTarget();
	        var sprintSidePanel = new SprintSidePanel({
	          sprints: _this2.entityStorage.getSprints(),
	          sidePanel: _this2.sidePanel,
	          requestSender: _this2.requestSender,
	          views: _this2.views
	        });
	        sprintSidePanel.showBurnDownChart(sprint);
	      };

	      var onChangeTaskResponsible = function onChangeTaskResponsible(baseEvent) {
	        _this2.pullItem.addIdToSkipUpdating(baseEvent.getData().getItemId());

	        _this2.requestSender.changeTaskResponsible({
	          itemId: baseEvent.getData().getItemId(),
	          itemType: baseEvent.getData().getItemType(),
	          sourceId: baseEvent.getData().getSourceId(),
	          responsible: baseEvent.getData().getResponsible()
	        }).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onRemoveSprint = function onRemoveSprint(baseEvent) {
	        var sprint = baseEvent.getTarget();

	        _this2.pullSprint.addIdToSkipRemoving(sprint.getId());

	        _this2.requestSender.removeSprint({
	          sprintId: sprint.getId(),
	          sortInfo: _this2.sprintMover.calculateSprintSort()
	        }).then(function (response) {
	          _this2.entityStorage.removeSprint(sprint.getId());
	        }).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onChangeSprintName = function onChangeSprintName(baseEvent) {
	        var requestData = baseEvent.getData();

	        _this2.pullSprint.addIdToSkipUpdating(requestData.sprintId);

	        _this2.requestSender.changeSprintName(requestData).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onChangeSprintDeadline = function onChangeSprintDeadline(baseEvent) {
	        var requestData = baseEvent.getData();

	        _this2.pullSprint.addIdToSkipUpdating(requestData.sprintId);

	        _this2.requestSender.changeSprintDeadline(requestData).catch(function (response) {
	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var onOpenAddEpicForm = function onOpenAddEpicForm(baseEvent) {
	        _this2.epic.openAddForm();
	      };

	      var onOpenListEpicGrid = function onOpenListEpicGrid(baseEvent) {
	        _this2.epic.openEpicsList();

	        _this2.epic.subscribe('onAfterEditEpic', function (innerBaseEvent) {
	          var response = innerBaseEvent.getData();
	          var updatedEpicInfo = response.data;

	          _this2.epic.onAfterUpdateEpic(updatedEpicInfo);
	        });
	      };

	      var onOpenDefinitionOfDone = function onOpenDefinitionOfDone(baseEvent) {
	        var entity = baseEvent.getTarget();
	        var projectSidePanel = new ProjectSidePanel({
	          sidePanel: _this2.sidePanel,
	          requestSender: _this2.requestSender
	        });
	        projectSidePanel.showDefinitionOfDone(entity);
	      };

	      var onShowTagSearcher = function onShowTagSearcher(baseEvent) {
	        //todo refactor and test
	        var entity = baseEvent.getTarget();
	        var data = baseEvent.getData();
	        var item = data.item;
	        var actionsPanelButton = data.button;

	        _this2.tagSearcher.showTagsDialog(item, actionsPanelButton);

	        _this2.tagSearcher.unsubscribeAll('attachTagToTask');

	        _this2.tagSearcher.subscribe('attachTagToTask', function (innerBaseEvent) {
	          var tag = innerBaseEvent.getData();

	          if (entity.isGroupMode()) {
	            var tasks = [];
	            entity.getGroupModeItems().forEach(function (groupModeItem) {
	              tasks.push({
	                taskId: groupModeItem.getSourceId(),
	                itemId: groupModeItem.getItemId()
	              });
	            });

	            _this2.requestSender.batchAttachTagToTask({
	              tasks: tasks,
	              entityId: entity.getId(),
	              tag: tag
	            }).then(function (response) {
	              entity.getGroupModeItems().forEach(function (groupModeItem) {
	                var currentTags = groupModeItem.getTags();
	                currentTags.push(tag);
	                groupModeItem.setEpicAndTags(groupModeItem.getEpic(), currentTags);
	              });
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          } else {
	            var currentTags = item.getTags();

	            _this2.requestSender.attachTagToTask({
	              taskId: item.getSourceId(),
	              itemId: item.getItemId(),
	              entityId: entity.getId(),
	              tag: tag
	            }).then(function (response) {
	              currentTags.push(tag);
	              item.setEpicAndTags(item.getEpic(), currentTags);
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          }
	        });

	        _this2.tagSearcher.unsubscribeAll('deAttachTagToTask');

	        _this2.tagSearcher.subscribe('deAttachTagToTask', function (innerBaseEvent) {
	          var tag = innerBaseEvent.getData();

	          if (entity.isGroupMode()) {
	            var tasks = [];
	            entity.getGroupModeItems().forEach(function (groupModeItem) {
	              tasks.push({
	                taskId: groupModeItem.getSourceId(),
	                itemId: groupModeItem.getItemId()
	              });
	            });

	            _this2.requestSender.batchDeattachTagToTask({
	              tasks: tasks,
	              entityId: entity.getId(),
	              tag: tag
	            }).then(function (response) {
	              entity.getGroupModeItems().forEach(function (groupModeItem) {
	                var currentTags = groupModeItem.getTags();
	                currentTags.splice(currentTags.indexOf(tag), 1);
	                groupModeItem.setEpicAndTags(groupModeItem.getEpic(), currentTags);
	              });
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          } else {
	            var currentTags = item.getTags();

	            _this2.requestSender.deAttachTagToTask({
	              taskId: item.getSourceId(),
	              itemId: item.getItemId(),
	              entityId: entity.getId(),
	              tag: tag
	            }).then(function (response) {
	              currentTags.splice(currentTags.indexOf(tag), 1);
	              item.setEpicAndTags(item.getEpic(), currentTags);
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          }
	        });

	        _this2.tagSearcher.unsubscribeAll('hideTagDialog');

	        _this2.tagSearcher.subscribe('hideTagDialog', function (innerBaseEvent) {
	          if (entity.isGroupMode()) {
	            entity.deactivateGroupMode();
	          }
	        });
	      };

	      var onShowEpicSearcher = function onShowEpicSearcher(baseEvent) {
	        var entity = baseEvent.getTarget();
	        var data = baseEvent.getData();
	        var item = data.item;
	        var actionsPanelButton = data.button;

	        _this2.tagSearcher.showEpicDialog(item, actionsPanelButton);

	        _this2.tagSearcher.unsubscribeAll('updateItemEpic');

	        _this2.tagSearcher.subscribe('updateItemEpic', function (innerBaseEvent) {
	          if (entity.isGroupMode()) {
	            var items = [];
	            entity.getGroupModeItems().forEach(function (groupModeItem) {
	              items.push({
	                itemId: groupModeItem.getItemId()
	              });

	              _this2.pullItem.addIdToSkipUpdating(groupModeItem.getItemId());
	            });

	            _this2.requestSender.batchUpdateItemEpic({
	              items: items,
	              entityId: entity.getId(),
	              epicId: innerBaseEvent.getData()
	            }).then(function (response) {
	              entity.getGroupModeItems().forEach(function (groupModeItem) {
	                if (main_core.Type.isArray(response.data.epic)) {
	                  groupModeItem.setParentId(0);
	                  groupModeItem.setEpicAndTags(null, null);
	                } else {
	                  groupModeItem.setParentId(response.data.epic.id);
	                  groupModeItem.setEpicAndTags(response.data.epic, groupModeItem.getTags());
	                }
	              });
	              entity.deactivateGroupMode();
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          } else {
	            _this2.pullItem.addIdToSkipUpdating(item.getItemId());

	            _this2.requestSender.updateItemEpic({
	              itemId: item.getItemId(),
	              entityId: entity.getId(),
	              epicId: innerBaseEvent.getData()
	            }).then(function (response) {
	              if (main_core.Type.isArray(response.data.epic)) {
	                item.setParentId(0);
	                item.setEpicAndTags(null, null);
	              } else {
	                item.setParentId(response.data.epic.id);
	                item.setEpicAndTags(response.data.epic, item.getTags());
	              }
	            }).catch(function (response) {
	              _this2.requestSender.showErrorAlert(response);
	            });
	          }
	        });
	      };

	      var onStartDecomposition = function onStartDecomposition(baseEvent) {
	        var entity = baseEvent.getTarget();
	        var parentItem = baseEvent.getData();
	        var decomposition = new Decomposition({
	          entity: entity,
	          itemStyleDesigner: _this2.itemStyleDesigner,
	          subTasksCreator: _this2.subTasksCreator
	        });
	        decomposition.subscribe('tagsSearchOpen', onTagsSearchOpen);
	        decomposition.subscribe('tagsSearchClose', onTagsSearchClose);
	        decomposition.subscribe('createItem', function (innerBaseEvent) {
	          var inputValue = innerBaseEvent.getData();
	          var decomposedItems = decomposition.getDecomposedItems();
	          var lastDecomposedItem = Array.from(decomposedItems).pop();

	          var newItem = _this2.createItem(parentItem.getItemType(), inputValue);

	          _this2.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());

	          _this2.pullItem.addIdToSkipUpdating(parentItem.getItemId());

	          newItem.setParentEntity(entity.getId(), entity.getEntityType());
	          newItem.setParentId(parentItem.getParentId());
	          newItem.setParentSourceId(parentItem.getSourceId());
	          newItem.setEpic(parentItem.getEpic());
	          newItem.setTags(parentItem.getTags());
	          newItem.setResponsible(decomposition.getResponsible());

	          if (decomposition.isBacklogDecomposition()) {
	            parentItem.setLinkedTask('Y');
	            newItem.setSort(lastDecomposedItem.getSort() + 1);
	            newItem.setInfo({
	              borderColor: decomposition.getBorderColor()
	            });
	            newItem.setLinkedTask('Y');
	          } else {
	            parentItem.setParentTask('Y');
	            parentItem.setSubTasksCount(parentItem.getSubTasksCount() + 1);
	            parentItem.updateParentTaskNodes();
	            newItem.setSort(decomposition.getSubTasks(parentItem).length + 1);
	            newItem.setSubTask('Y');
	            newItem.setParentTaskId(parentItem.getSourceId());
	            newItem.setParentTask('N');
	          }

	          _this2.domBuilder.appendItemAfterItem(newItem.render(), decomposition.getLastDecomposedItemNode(parentItem));

	          newItem.onAfterAppend(entity.getListItemsNode());
	          decomposition.addDecomposedItem(newItem);

	          _this2.sendRequestToCreateTask(entity, newItem, inputValue).then(function (response) {
	            _this2.fillItemAfterCreation(newItem, response.data);

	            response.data.tags.forEach(function (tag) {
	              _this2.tagSearcher.addTagToSearcher(tag);
	            });
	            entity.setItem(newItem);

	            if (!decomposition.isBacklogDecomposition()) {
	              _this2.subTasksCreator.addSubTask(parentItem, newItem);
	            }
	          });
	        });
	        decomposition.subscribe('updateParentItem', onUpdateItem);
	        decomposition.decomposeItem(parentItem);
	      };

	      var onTagsSearchOpen = function onTagsSearchOpen(baseEvent) {
	        var data = baseEvent.getData();
	        var inputObject = data.inputObject;
	        var enteredHashTagName = data.enteredHashTagName;

	        _this2.tagSearcher.showTagsSearchDialog(inputObject, enteredHashTagName);
	      };

	      var onTagsSearchClose = function onTagsSearchClose() {
	        _this2.tagSearcher.closeTagsSearchDialog();
	      };

	      var onEpicSearchOpen = function onEpicSearchOpen(baseEvent) {
	        var data = baseEvent.getData();
	        var inputObject = data.inputObject;
	        var enteredHashEpicName = data.enteredHashEpicName;

	        _this2.tagSearcher.showEpicSearchDialog(inputObject, enteredHashEpicName);
	      };

	      var onEpicSearchClose = function onEpicSearchClose() {
	        _this2.tagSearcher.closeEpicSearchDialog();
	      };

	      var onFilterByEpic = function onFilterByEpic(baseEvent) {
	        var epicId = baseEvent.getData();

	        var currentValue = _this2.filter.getValueFromField({
	          name: 'EPIC',
	          value: ''
	        });

	        if (String(epicId) === String(currentValue)) {
	          _this2.filter.setValueToField({
	            name: 'EPIC',
	            value: ''
	          });
	        } else {
	          _this2.filter.setValueToField({
	            name: 'EPIC',
	            value: String(epicId)
	          });
	        }

	        _this2.filter.scrollToSearchContainer();
	      };

	      var onFilterByTag = function onFilterByTag(baseEvent) {
	        var tag = baseEvent.getData();

	        var currentValue = _this2.filter.getValueFromField({
	          name: 'TAG',
	          value: ''
	        });

	        if (String(tag) === String(currentValue)) {
	          _this2.filter.setValueToField({
	            name: 'TAG',
	            value: ''
	          });
	        } else {
	          _this2.filter.setValueToField({
	            name: 'TAG',
	            value: String(tag)
	          });
	        }

	        _this2.filter.scrollToSearchContainer();
	      };

	      var onActivateGroupMode = function onActivateGroupMode(baseEvent) {
	        var entity = baseEvent.getTarget();

	        if (entity.getId() !== _this2.entityStorage.getBacklog().getId()) {
	          _this2.entityStorage.getBacklog().deactivateGroupMode();
	        }

	        _this2.entityStorage.getSprints().forEach(function (sprint) {
	          if (entity.getId() !== sprint.getId()) {
	            sprint.deactivateGroupMode();
	          }
	        });

	        entity.getItems().forEach(function (item) {
	          item.activateGroupMode();
	        });
	      };

	      var onDeactivateGroupMode = function onDeactivateGroupMode(baseEvent) {
	        var entity = baseEvent.getTarget();
	        entity.getItems().forEach(function (item) {
	          item.deactivateGroupMode();
	        });
	      };

	      var onGetSprintCompletedItems = function onGetSprintCompletedItems(baseEvent) {
	        var sprint = baseEvent.getTarget();
	        var listItemsNode = sprint.getListItemsNode();

	        var listPosition = _this2.domBuilder.getPosition(listItemsNode);

	        var loader = new main_loader.Loader({
	          target: listItemsNode,
	          size: 60,
	          mode: 'inline',
	          color: '#eaeaea',
	          offset: {
	            left: "".concat(listPosition.width / 2 - 30, "px")
	          }
	        });
	        loader.show();

	        _this2.requestSender.getSprintCompletedItems({
	          sprintId: sprint.getId()
	        }).then(function (response) {
	          var itemsData = response.data;
	          itemsData.forEach(function (itemData) {
	            var item = new Item(itemData);
	            item.setDisableStatus(sprint.isDisabled());

	            _this2.domBuilder.append(item.render(), listItemsNode);

	            sprint.setItem(item);
	          });
	          loader.hide();
	        }).catch(function (response) {
	          loader.hide();

	          _this2.requestSender.showErrorAlert(response);
	        });
	      };

	      var subscribeToSprint = function subscribeToSprint(sprint) {
	        sprint.subscribe('createTaskItem', createTaskItem);
	        sprint.subscribe('updateItem', onUpdateItem);
	        sprint.subscribe('showTask', onShowTask);
	        sprint.subscribe('moveItem', onMoveItem);
	        sprint.subscribe('moveToSprint', onMoveToSprint);
	        sprint.subscribe('moveToBacklog', onMoveToBacklog);
	        sprint.subscribe('removeItem', onRemoveItem);
	        sprint.subscribe('startSprint', onStartSprint);
	        sprint.subscribe('completeSprint', onCompleteSprint);
	        sprint.subscribe('changeTaskResponsible', onChangeTaskResponsible);
	        sprint.subscribe('removeSprint', onRemoveSprint);
	        sprint.subscribe('changeSprintName', onChangeSprintName);
	        sprint.subscribe('changeSprintDeadline', onChangeSprintDeadline);
	        sprint.subscribe('attachFilesToTask', onAttachFilesToTask);
	        sprint.subscribe('showTagSearcher', onShowTagSearcher);
	        sprint.subscribe('showEpicSearcher', onShowEpicSearcher);
	        sprint.subscribe('startDecomposition', onStartDecomposition);
	        sprint.subscribe('tagsSearchOpen', onTagsSearchOpen);
	        sprint.subscribe('tagsSearchClose', onTagsSearchClose);
	        sprint.subscribe('epicSearchOpen', onEpicSearchOpen);
	        sprint.subscribe('epicSearchClose', onEpicSearchClose);
	        sprint.subscribe('filterByEpic', onFilterByEpic);
	        sprint.subscribe('filterByTag', onFilterByTag);
	        sprint.subscribe('activateGroupMode', onActivateGroupMode);
	        sprint.subscribe('deactivateGroupMode', onDeactivateGroupMode);
	        sprint.subscribe('getSprintCompletedItems', onGetSprintCompletedItems);
	        sprint.subscribe('showSprintBurnDownChart', onShowSprintBurnDownChart);
	        sprint.subscribe('toggleSubTasks', _this2.onToggleSubTasks.bind(_this2));
	      };

	      if (newSprint) {
	        subscribeToSprint(newSprint);
	        return;
	      }

	      this.entityStorage.getBacklog().subscribe('createTaskItem', createTaskItem);
	      this.entityStorage.getBacklog().subscribe('updateItem', onUpdateItem);
	      this.entityStorage.getBacklog().subscribe('showTask', onShowTask);
	      this.entityStorage.getBacklog().subscribe('moveItem', onMoveItem);
	      this.entityStorage.getBacklog().subscribe('moveToSprint', onMoveToSprint);
	      this.entityStorage.getBacklog().subscribe('removeItem', onRemoveItem);
	      this.entityStorage.getBacklog().subscribe('changeTaskResponsible', onChangeTaskResponsible);
	      this.entityStorage.getBacklog().subscribe('openAddEpicForm', onOpenAddEpicForm);
	      this.entityStorage.getBacklog().subscribe('openListEpicGrid', onOpenListEpicGrid);
	      this.entityStorage.getBacklog().subscribe('openDefinitionOfDone', onOpenDefinitionOfDone);
	      this.entityStorage.getBacklog().subscribe('attachFilesToTask', onAttachFilesToTask);
	      this.entityStorage.getBacklog().subscribe('showTagSearcher', onShowTagSearcher);
	      this.entityStorage.getBacklog().subscribe('showEpicSearcher', onShowEpicSearcher);
	      this.entityStorage.getBacklog().subscribe('startDecomposition', onStartDecomposition);
	      this.entityStorage.getBacklog().subscribe('tagsSearchOpen', onTagsSearchOpen);
	      this.entityStorage.getBacklog().subscribe('tagsSearchClose', onTagsSearchClose);
	      this.entityStorage.getBacklog().subscribe('epicSearchOpen', onEpicSearchOpen);
	      this.entityStorage.getBacklog().subscribe('epicSearchClose', onEpicSearchClose);
	      this.entityStorage.getBacklog().subscribe('filterByEpic', onFilterByEpic);
	      this.entityStorage.getBacklog().subscribe('filterByTag', onFilterByTag);
	      this.entityStorage.getBacklog().subscribe('activateGroupMode', onActivateGroupMode);
	      this.entityStorage.getBacklog().subscribe('deactivateGroupMode', onDeactivateGroupMode);
	      this.epic.subscribe('filterByTag', onFilterByTag);
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        subscribeToSprint(sprint);
	      });
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(itemType, value) {
	      //todo test
	      var valueWithoutTags = value.replace(new RegExp('#([^\\s]*)', 'g'), '').replace(new RegExp('@([^\\s]*)', 'g'), '');
	      return new Item({
	        'itemId': '',
	        'itemType': itemType,
	        'name': valueWithoutTags
	      });
	    }
	  }, {
	    key: "sendRequestToCreateTask",
	    value: function sendRequestToCreateTask(entity, item, value) {
	      var requestData = {
	        'tmpId': item.getItemId(),
	        'itemType': item.getItemType(),
	        'name': value,
	        'entityId': item.getEntityId(),
	        'entityType': entity.getEntityType(),
	        'parentId': item.getParentId(),
	        'sort': item.getSort(),
	        'storyPoints': item.getStoryPoints().getPoints(),
	        'tags': item.getTags(),
	        'epic': item.getEpic(),
	        'parentSourceId': item.getParentSourceId(),
	        'responsible': item.getResponsible(),
	        'info': item.getInfo(),
	        'sortInfo': this.itemMover.calculateSort(entity.getListItemsNode()),
	        'isActiveSprint': entity.getEntityType() === 'sprint' && entity.isActive() ? 'Y' : 'N'
	      };
	      return this.requestSender.createTask(requestData);
	    }
	  }, {
	    key: "fillItemBeforeCreation",
	    value: function fillItemBeforeCreation(entity, item, value) {
	      item.setParentEntity(entity.getId(), entity.getEntityType());
	      item.setSort(1);
	      item.setResponsible(this.defaultResponsible);
	      var tags = TagSearcher.getHashTagNamesFromText(value);
	      var epicName = TagSearcher.getHashEpicNamesFromText(value).pop();
	      var inputEpic = null;

	      if (epicName) {
	        inputEpic = this.tagSearcher.getEpicByName(epicName.trim());
	      }

	      if (inputEpic || tags.length > 0) {
	        item.setEpicAndTags(inputEpic, tags);
	      }
	    }
	  }, {
	    key: "fillItemAfterCreation",
	    value: function fillItemAfterCreation(item, responseData) {
	      //todo test
	      item.setItemId(responseData.itemId);

	      if (!main_core.Type.isArray(responseData.epic) || responseData.tags.length > 0) {
	        item.setEpicAndTags(responseData.epic, responseData.tags);
	      }

	      item.setResponsible(responseData.responsible);
	      item.setSourceId(responseData.sourceId);
	      item.setAllowedActions(responseData.allowedActions);
	    }
	  }, {
	    key: "onToggleSubTasks",
	    value: function onToggleSubTasks(baseEvent) {
	      var sprint = baseEvent.getTarget();
	      var item = baseEvent.getData();
	      this.subTasksCreator.toggleSubTasks(sprint, item);
	    }
	  }, {
	    key: "openEpicEditForm",
	    value: function openEpicEditForm(epicId) {
	      this.epic.openEditForm(epicId);
	    }
	  }, {
	    key: "openEpicViewForm",
	    value: function openEpicViewForm(epicId) {
	      this.epic.openViewForm(epicId);
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic(epicId) {
	      var _this3 = this;

	      this.requestSender.removeItem({
	        itemId: epicId,
	        itemType: 'epic'
	      }).then(function (response) {
	        var epicInfo = response.data;

	        _this3.epic.onAfterRemoveEpic(epicInfo);
	      }).catch(function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onShowTeamSpeedChart",
	    value: function onShowTeamSpeedChart() //todo move to class
	    {
	      var projectSidePanel = new ProjectSidePanel({
	        sidePanel: this.sidePanel,
	        requestSender: this.requestSender
	      });
	      projectSidePanel.showTeamSpeedChart();
	    }
	  }]);
	  return Plan;
	}(View);

	var ActiveSprint = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(ActiveSprint, _View);

	  function ActiveSprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, ActiveSprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActiveSprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.ActiveSprint');

	    _this.setParams(params);

	    if (_this.existActiveSprint()) {
	      _this.finishStatus = _this.getActiveSprintParams().finishStatus;
	      _this.sprint = new Sprint(_this.getActiveSprintParams());
	      _this.itemsInFinishStage = new Map();

	      _this.initDomNodes();

	      _this.createSprintStats();

	      _this.bindHandlers();
	    }

	    _this.sidePanel = new SidePanel();
	    return _this;
	  }

	  babelHelpers.createClass(ActiveSprint, [{
	    key: "setParams",
	    value: function setParams(params) {
	      var _this2 = this;

	      this.setPathToTask(params.pathToTask);
	      this.setActiveSprintParams(params.activeSprint);
	      this.sprints = new Map();
	      params.sprints.forEach(function (sprintData) {
	        var sprint = new Sprint(sprintData);

	        _this2.sprints.set(sprint.getId(), sprint);
	      });
	      this.views = params.views;
	    }
	  }, {
	    key: "setPathToTask",
	    value: function setPathToTask(pathToTask) {
	      this.pathToTask = main_core.Type.isString(pathToTask) ? pathToTask : '';
	    }
	  }, {
	    key: "getPathToTask",
	    value: function getPathToTask() {
	      return this.pathToTask;
	    }
	  }, {
	    key: "setActiveSprintParams",
	    value: function setActiveSprintParams(params) {
	      this.activeSprint = main_core.Type.isPlainObject(params) ? params : null;
	    }
	  }, {
	    key: "getActiveSprintParams",
	    value: function getActiveSprintParams() {
	      return this.activeSprint;
	    }
	  }, {
	    key: "existActiveSprint",
	    value: function existActiveSprint() {
	      return this.activeSprint !== null;
	    }
	  }, {
	    key: "initDomNodes",
	    value: function initDomNodes() {
	      this.sprintStatsContainer = document.getElementById('tasks-scrum-active-sprint-stats');
	      var buttonsContainer = document.getElementById('tasks-scrum-actions-complete-sprint');
	      this.chartSprintButtonNode = buttonsContainer.firstElementChild;
	      this.completeSprintButtonNode = buttonsContainer.lastElementChild;
	    }
	  }, {
	    key: "createSprintStats",
	    value: function createSprintStats() {
	      this.statsHeader = StatsHeaderBuilder.build(this.sprint);
	      this.statsHeader.setKanbanStyle();
	      main_core.Dom.append(this.statsHeader.render(), this.sprintStatsContainer);
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      var _this3 = this;

	      main_core.Event.bind(this.chartSprintButtonNode, 'click', this.onShowSprintBurnDownChart.bind(this));
	      main_core.Event.bind(this.completeSprintButtonNode, 'click', this.onCompleteSprint.bind(this)); // eslint-disable-next-line

	      var kanbanManager = BX.Tasks.Scrum.Kanban;

	      if (kanbanManager) {
	        this.bindKanbanHandlers(kanbanManager.getKanban());
	        kanbanManager.getKanbansGroupedByParentTasks().forEach(function (kanban) {
	          _this3.bindKanbanHandlers(kanban);
	        });
	      }
	    }
	  }, {
	    key: "bindKanbanHandlers",
	    value: function bindKanbanHandlers(kanban) {
	      var _this4 = this;

	      this.onKanbanRender(kanban);
	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onItemMoved', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	            _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 3),
	            kanbanItem = _event$getCompatData2[0],
	            targetColumn = _event$getCompatData2[1],
	            beforeItem = _event$getCompatData2[2];

	        _this4.onItemMoved(kanbanItem, targetColumn, beforeItem);
	      });
	    }
	  }, {
	    key: "onCompleteSprint",
	    value: function onCompleteSprint() {
	      var _this5 = this;

	      var sprintSidePanel = new SprintSidePanel({
	        sprints: this.sprints,
	        sidePanel: this.sidePanel,
	        requestSender: this.requestSender,
	        views: this.views
	      });
	      sprintSidePanel.showCompleteSidePanel(this.sprint);
	      this.sprint.subscribe('showTask', function (baseEvent) {
	        var item = baseEvent.getData();

	        _this5.sidePanel.openSidePanelByUrl(_this5.getPathToTask().replace('#task_id#', item.getSourceId()));
	      });
	    }
	    /**
	     * Handles Kanban render.
	     * @param {BX.Tasks.Kanban.Grid} kanbanGrid
	     * @returns {void}
	     */

	  }, {
	    key: "onKanbanRender",
	    value: function onKanbanRender(kanbanGrid) {
	      var items = kanbanGrid.getItems();
	      var hasOwnProperty = Object.prototype.hasOwnProperty;

	      for (var itemId in kanbanGrid.getItems()) {
	        if (hasOwnProperty.call(items, itemId)) {
	          var item = items[itemId];

	          if (item.getColumn().getType() === this.finishStatus) {
	            this.itemsInFinishStage.set(itemId, '');
	          }
	        }
	      }
	    }
	    /**
	     * Hook on item moved.
	     * @param {BX.Tasks.Kanban.Item} kanbanItem
	     * @param {BX.Tasks.Kanban.Column} targetColumn
	     * @param {BX.Tasks.Kanban.Item} [beforeItem]
	     * @returns {void}
	     */

	  }, {
	    key: "onItemMoved",
	    value: function onItemMoved(kanbanItem, targetColumn, beforeItem) {
	      if (targetColumn.type === this.finishStatus) {
	        if (!this.itemsInFinishStage.has(kanbanItem.getId())) {
	          this.updateStatsAfterMovedToFinish(kanbanItem, this.sprint);
	        }
	      } else {
	        if (this.itemsInFinishStage.has(kanbanItem.getId())) {
	          this.updateStatsAfterMovedFromFinish(kanbanItem, this.sprint);
	        }
	      }

	      this.statsHeader.updateStats(this.sprint);
	    }
	  }, {
	    key: "updateStatsAfterMovedToFinish",
	    value: function updateStatsAfterMovedToFinish(kanbanItem, sprint) {
	      this.itemsInFinishStage.set(kanbanItem.getId(), kanbanItem.getStoryPoints());
	      sprint.getTotalCompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());
	      sprint.getTotalUncompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());
	      sprint.setCompletedTasks(sprint.getCompletedTasks() + 1);
	      sprint.setUncompletedTasks(sprint.getUncompletedTasks() - 1);
	      sprint.getItems().forEach(function (scrumItem) {
	        if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10)) {
	          scrumItem.setCompleted('Y');
	        }
	      });
	    }
	  }, {
	    key: "updateStatsAfterMovedFromFinish",
	    value: function updateStatsAfterMovedFromFinish(kanbanItem, sprint) {
	      this.itemsInFinishStage.delete(kanbanItem.getId());
	      sprint.getTotalCompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());
	      sprint.getTotalUncompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());
	      sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
	      sprint.setUncompletedTasks(sprint.getUncompletedTasks() + 1);
	      sprint.getItems().forEach(function (scrumItem) {
	        if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10)) {
	          scrumItem.setCompleted('N');
	        }
	      });
	    }
	  }, {
	    key: "onShowSprintBurnDownChart",
	    value: function onShowSprintBurnDownChart() {
	      var sprintSidePanel = new SprintSidePanel({
	        sidePanel: this.sidePanel,
	        requestSender: this.requestSender,
	        views: this.views
	      });
	      sprintSidePanel.showBurnDownChart(this.sprint);
	    }
	  }]);
	  return ActiveSprint;
	}(View);

	var CompletedSprint = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(CompletedSprint, _View);

	  function CompletedSprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, CompletedSprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompletedSprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.CompletedSprint');

	    _this.setParams(params);

	    _this.initDomNodes();

	    _this.bindHandlers();

	    _this.createTitle();

	    return _this;
	  }

	  babelHelpers.createClass(CompletedSprint, [{
	    key: "setParams",
	    value: function setParams(params) {
	      var _this2 = this;

	      this.completedSprint = new Sprint(params.completedSprint);
	      this.sidePanel = new SidePanel();
	      this.sprints = new Map();
	      params.sprints.forEach(function (sprintData) {
	        var sprint = Sprint.buildSprint(sprintData);

	        _this2.sprints.set(sprint.getId(), sprint);
	      });
	      this.views = params.views;
	    }
	  }, {
	    key: "initDomNodes",
	    value: function initDomNodes() {
	      this.chartSprintButtonNode = document.getElementById('tasks-scrum-completed-sprint-chart');
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      main_core_events.EventEmitter.subscribe('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
	      main_core.Event.bind(this.chartSprintButtonNode, 'click', this.onShowSprintBurnDownChart.bind(this));
	    }
	  }, {
	    key: "createTitle",
	    value: function createTitle() {
	      this.titleContainer = document.getElementById('tasks-scrum-completed-sprint-title');
	      this.titleContainer.textContent = main_core.Text.encode(this.completedSprint.getName());
	    }
	  }, {
	    key: "onSprintSelectorChange",
	    value: function onSprintSelectorChange(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          currentSprint = _event$getCompatData2[0];

	      this.completedSprint = this.findSprintBySprintId(currentSprint.sprintId);
	      this.titleContainer.textContent = main_core.Text.encode(currentSprint.name);
	    }
	  }, {
	    key: "onShowSprintBurnDownChart",
	    value: function onShowSprintBurnDownChart() {
	      var sprintSidePanel = new SprintSidePanel({
	        sidePanel: this.sidePanel,
	        requestSender: this.requestSender,
	        views: this.views
	      });
	      sprintSidePanel.showBurnDownChart(this.completedSprint);
	    }
	  }, {
	    key: "findSprintBySprintId",
	    value: function findSprintBySprintId(sprintId) {
	      return babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.getId() === parseInt(sprintId, 10);
	      });
	    }
	  }]);
	  return CompletedSprint;
	}(View);

	var Entry = /*#__PURE__*/function () {
	  function Entry(params) {
	    babelHelpers.classCallCheck(this, Entry);
	    this.setParams(params);
	    this.buildView(params);
	  }

	  babelHelpers.createClass(Entry, [{
	    key: "setParams",
	    value: function setParams(params) {
	      this.setViewName(params.viewName);
	    }
	  }, {
	    key: "setViewName",
	    value: function setViewName(viewName) {
	      var availableViews = new Set(['plan', 'activeSprint', 'completedSprint']);

	      if (!availableViews.has(viewName)) {
	        throw Error('Invalid value to activeView parameter');
	      }

	      this.viewName = viewName;
	    }
	  }, {
	    key: "getViewName",
	    value: function getViewName() {
	      return this.viewName;
	    }
	  }, {
	    key: "setView",
	    value: function setView(view) {
	      if (view instanceof View) {
	        this.view = view;
	      } else {
	        this.view = null;
	      }
	    }
	  }, {
	    key: "getView",
	    value: function getView() {
	      return this.view;
	    }
	  }, {
	    key: "buildView",
	    value: function buildView(params) {
	      var viewName = this.getViewName();

	      if (viewName === 'plan') {
	        this.setView(new Plan(params));
	      } else if (viewName === 'activeSprint') {
	        this.setView(new ActiveSprint(params));
	      } else if (viewName === 'completedSprint') {
	        this.setView(new CompletedSprint(params));
	      }
	    }
	  }, {
	    key: "renderTo",
	    value: function renderTo(container) {
	      var view = this.getView();

	      if (view instanceof View) {
	        this.getView().renderTo(container);
	      }
	    }
	  }, {
	    key: "renderCountersTo",
	    value: function renderCountersTo(container) {
	      var view = this.getView();

	      if (view instanceof View) {
	        this.getView().renderCountersTo(container);
	      }
	    }
	  }, {
	    key: "openEpicEditForm",
	    value: function openEpicEditForm(epicId) {
	      var view = this.getView();

	      if (view instanceof Plan) {
	        view.openEpicEditForm(epicId);
	      }
	    }
	  }, {
	    key: "openEpicViewForm",
	    value: function openEpicViewForm(epicId) {
	      var view = this.getView();

	      if (view instanceof Plan) {
	        view.openEpicViewForm(epicId);
	      }
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic(epicId) {
	      var view = this.getView();

	      if (view instanceof Plan) {
	        view.removeEpic(epicId);
	      }
	    }
	  }]);
	  return Entry;
	}();

	exports.Entry = Entry;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.UI.EntitySelector,BX.UI,BX.UI.DragAndDrop,BX,BX.UI,BX.Main,BX.UI.Dialogs,BX,BX.Event));
//# sourceMappingURL=script.js.map

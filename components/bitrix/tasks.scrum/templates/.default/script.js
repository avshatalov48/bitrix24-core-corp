this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_shortView,ui_entitySelector,main_popup,ui_dialogs_messagebox,ui_draganddrop_draggable,pull_client,main_loader,main_core,main_core_events) {
	'use strict';

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
	    key: "getTopSidePanel",
	    value: function getTopSidePanel() {
	      var topSidePanel = this.sidePanelManager.getTopSlider();
	      return topSidePanel ? topSidePanel : null;
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
	  }, {
	    key: "showByExtension",
	    value: function showByExtension(name, params) {
	      var extensionName = 'tasks.scrum.' + name.toLowerCase();
	      return main_core.Runtime.loadExtension(extensionName).then(function (exports) {
	        name = name.replaceAll('-', '');

	        if (exports && exports[name]) {
	          var extension = new exports[name](params);
	          extension.show();
	          return extension;
	        } else {
	          return null;
	        }
	      });
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

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
	    key: "removeItems",
	    value: function removeItems(data) {
	      return this.sendRequestToComponent(data, 'removeItems');
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
	    key: "hasTaskInFilter",
	    value: function hasTaskInFilter(data) {
	      return this.sendRequestToComponent(data, 'hasTaskInFilter');
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
	    key: "getCompletedSprints",
	    value: function getCompletedSprints(data) {
	      return this.sendRequestToComponent(data, 'getCompletedSprints');
	    }
	  }, {
	    key: "getCompletedSprintsStats",
	    value: function getCompletedSprintsStats(data) {
	      return this.sendRequestToComponent(data, 'getCompletedSprintsStats');
	    }
	  }, {
	    key: "getItems",
	    value: function getItems(data) {
	      return this.sendRequestToComponent(data, 'getItems');
	    }
	  }, {
	    key: "saveShortView",
	    value: function saveShortView(data) {
	      return this.sendRequestToComponent(data, 'saveShortView');
	    }
	  }, {
	    key: "saveDisplayPriority",
	    value: function saveDisplayPriority(data) {
	      return this.sendRequestToComponent(data, 'saveDisplayPriority');
	    }
	  }, {
	    key: "getEntityCounters",
	    value: function getEntityCounters(data) {
	      return this.sendRequestToComponent(data, 'getEntityCounters');
	    }
	  }, {
	    key: "attachFilesToTask",
	    value: function attachFilesToTask(data) {
	      return this.sendRequestToComponent(data, 'attachFilesToTask');
	    }
	  }, {
	    key: "updateTaskTags",
	    value: function updateTaskTags(data) {
	      return this.sendRequestToComponent(data, 'updateTaskTags');
	    }
	  }, {
	    key: "removeTaskTags",
	    value: function removeTaskTags(data) {
	      return this.sendRequestToComponent(data, 'removeTaskTags');
	    }
	  }, {
	    key: "updateItemEpics",
	    value: function updateItemEpics(data) {
	      return this.sendRequestToComponent(data, 'updateItemEpics');
	    }
	  }, {
	    key: "updateBorderColorToLinkedItems",
	    value: function updateBorderColorToLinkedItems(data) {
	      return this.sendRequestToComponent(data, 'updateBorderColorToLinkedItems');
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
	    key: "showLinkedTasks",
	    value: function showLinkedTasks(data) {
	      return this.sendRequestToComponent(data, 'showLinkedTasks');
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
	    key: "createEpic",
	    value: function createEpic(data) {
	      return this.sendRequest('bitrix:tasks.scrum.epic.createEpic', data);
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
	    key: "getFilterManager",
	    value: function getFilterManager() {
	      return this.filterManager;
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

	      params.autoResolve = true;
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

	      var found = fieldInstances.options.ITEMS.find(function (listItem) {
	        return listItem.NAME === item.NAME;
	      });

	      if (!main_core.Type.isUndefined(found)) {
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

	var _templateObject;
	var Tabs = /*#__PURE__*/function () {
	  function Tabs(params) {
	    babelHelpers.classCallCheck(this, Tabs);
	    this.sidePanel = params.sidePanel;
	    this.views = params.views;
	    this.node = null;
	  }

	  babelHelpers.createClass(Tabs, [{
	    key: "render",
	    value: function render() {
	      var _this = this;

	      var planTabActiveClass = this.views['plan'].active ? 'tasks-view-switcher--item --active' : '';
	      var activeTabActiveClass = this.views['activeSprint'].active ? 'tasks-view-switcher--item --active' : '';
	      var completedTabActiveClass = this.views['completedSprint'].active ? 'tasks-view-switcher--item --active' : '';
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-view-switcher\">\n\t\t\t\t<a\n\t\t\t\t\thref=\"", "\"\n\t\t\t\t\tclass=\"tasks-view-switcher--item ", "\"\n\t\t\t\t>", "</a>\n\t\t\t\t<a\n\t\t\t\t\thref=\"", "\"\n\t\t\t\t\tclass=\"tasks-view-switcher--item ", "\"\n\t\t\t\t>", "</a>\n\t\t\t\t<a\n\t\t\t\t\thref=\"", "\"\n\t\t\t\t\tclass=\"tasks-view-switcher--item ", "\"\n\t\t\t\t>", "</a>\n\t\t\t</div>\n\t\t"])), this.views['plan'].url, planTabActiveClass, main_core.Text.encode(this.views['plan'].name), this.views['activeSprint'].url, activeTabActiveClass, main_core.Text.encode(this.views['activeSprint'].name), this.views['completedSprint'].url, completedTabActiveClass, main_core.Text.encode(this.views['completedSprint'].name));
	      this.node.querySelectorAll('a').forEach(function (tab) {
	        main_core.Event.bind(tab, 'click', function () {
	          var topSidePanel = _this.sidePanel.getTopSidePanel();

	          if (topSidePanel !== null) {
	            topSidePanel.showLoader();
	          }
	        });
	      });
	      return this.node;
	    }
	  }]);
	  return Tabs;
	}();

	var View = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(View, _EventEmitter);

	  function View(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, View);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(View).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.View');

	    _this.isOwnerCurrentUser = params.isOwnerCurrentUser === 'Y';
	    _this.sidePanel = new SidePanel();
	    _this.requestSender = new RequestSender({
	      signedParameters: params.signedParameters,
	      debugMode: params.debugMode
	    });
	    _this.filter = new Filter({
	      filterId: params.filterId,
	      scrumManager: babelHelpers.assertThisInitialized(_this),
	      requestSender: _this.requestSender
	    });
	    _this.userId = parseInt(params.userId, 10);
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.pathToTask = main_core.Type.isString(params.pathToTask) ? params.pathToTask : '';
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
	    key: "renderTabsTo",
	    value: function renderTabsTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Scrum: HTMLElement for tabs not found');
	      }

	      var tabs = new Tabs({
	        sidePanel: this.sidePanel,
	        views: this.views
	      });
	      main_core.Dom.append(tabs.render(), container);
	    }
	  }, {
	    key: "renderSprintStatsTo",
	    value: function renderSprintStatsTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Scrum: HTMLElement for Sprint stats not found');
	      }
	    }
	  }, {
	    key: "renderRightElementsTo",
	    value: function renderRightElementsTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Scrum: HTMLElement for buttons not found');
	      }
	    }
	  }, {
	    key: "setDisplayPriority",
	    value: function setDisplayPriority(value) {
	      var availableValues = new Set(['backlog', 'sprint']);

	      if (!availableValues.has(value)) {
	        throw Error('Invalid parameter to set display priority');
	      }
	    }
	  }, {
	    key: "getCurrentUserId",
	    value: function getCurrentUserId() {
	      return this.userId;
	    }
	  }, {
	    key: "getCurrentGroupId",
	    value: function getCurrentGroupId() {
	      return this.groupId;
	    }
	  }, {
	    key: "getPathToTask",
	    value: function getPathToTask() {
	      return this.pathToTask;
	    }
	  }]);
	  return View;
	}(main_core_events.EventEmitter);

	var _templateObject$1;
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
	        autoHide: false,
	        closeByEsc: true,
	        angle: false,
	        offsetTop: 12,
	        offsetLeft: -32
	      });
	      this.popup.subscribe('onShow', function () {
	        BX.Disk.UF.add({
	          UID: controlId,
	          controlName: "[".concat(controlId, "][]"),
	          hideSelectDialog: false,
	          urlSelect: _this2.diskUrls.urlSelect,
	          urlRenameFile: _this2.diskUrls.urlRenameFile,
	          urlDeleteFile: _this2.diskUrls.urlDeleteFile,
	          urlUpload: _this2.diskUrls.urlUpload
	        });

	        var filesChooser = _this2.popup.contentContainer.querySelector('#files_chooser');

	        BX.onCustomEvent(filesChooser, 'DiskLoadFormController', ['show']);

	        if (BX.DiskFileDialog) {
	          main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'loadItemsDone', _this2.openDiskFileDialog.bind(_this2));
	        }
	      });
	      this.popup.subscribe('onClose', function () {
	        _this2.emit('onFinish', _this2.attachedIds);
	      });
	      main_core_events.EventEmitter.subscribe('onFinish', function () {
	        return _this2.popup.close();
	      });
	      this.popup.show();
	    }
	  }, {
	    key: "openDiskFileDialog",
	    value: function openDiskFileDialog() {
	      var _this3 = this;

	      if (BX.DiskFileDialog.popupWindow != null) {
	        BX.DiskFileDialog.popupWindow.subscribe('onClose', function () {
	          return _this3.popup.close();
	        });
	      }
	    }
	  }, {
	    key: "getAttachmentsLoaderContent",
	    value: function getAttachmentsLoaderContent(controlId) {
	      var filesChooser = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"files_chooser\">\n\t\t\t<div id=\"diskuf-selectdialog-", "\" class=\"diskuf-files-entity diskuf-selectdialog bx-disk\">\n\t\t\t\t<div class=\"diskuf-files-block\">\n\t\t\t\t\t<div class=\"diskuf-placeholder\">\n\t\t\t\t\t\t<table class=\"files-list\">\n\t\t\t\t\t\t\t<tbody class=\"diskuf-placeholder-tbody\"></tbody>\n\t\t\t\t\t\t</table>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"diskuf-extended\" style=\"display: block\">\n\t\t\t\t\t<input type=\"hidden\" name=\"[", "][]\" value=\"\"/>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<label for=\"file_loader_", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<input class=\"diskuf-fileUploader\" id=\"file_loader_", "\" type=\n\t\t\t\t\t\t\t\"file\" multiple=\"multiple\" size=\"1\" style=\"display: none\"/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link-cloud\" data-bx-doc-handler=\"gdrive\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t</div>\n\t\t"])), controlId, controlId, controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_COMPUTER'), controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_B24'), main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_CLOUD'));
	      BX.addCustomEvent(filesChooser, 'OnFileUploadSuccess', this.onFileUploadSuccess.bind(this));
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

	var _templateObject$2;
	var Toggle = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Toggle, _EventEmitter);

	  function Toggle(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Toggle);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Toggle).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.Toggle');

	    _this.visible = params.visible;
	    _this.shown = false;
	    return _this;
	  }

	  babelHelpers.createClass(Toggle, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--btn-toggle-tasks ", "\"></div>\n\t\t"])), this.visible ? '--visible' : '');
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.isShown()) {
	        this.emit('hide');
	      } else {
	        this.emit('show');
	      }
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      this.shown = true;
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.shown = false;
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this.shown;
	    }
	  }]);
	  return Toggle;
	}(main_core_events.EventEmitter);

	var _templateObject$3;
	var Name = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Name, _EventEmitter);

	  function Name(name, completed) {
	    var _this;

	    babelHelpers.classCallCheck(this, Name);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Name).call(this, name));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.Name');

	    _this.value = main_core.Type.isString(name) && name ? name.trim() : '';
	    _this.completed = completed;

	    if (!_this.value) {
	      throw new Error(main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_NAME_ERROR'));
	    }

	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Name, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--title ", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.completed ? '--completed' : '', main_core.Text.encode(this.value));
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.value;
	    }
	  }, {
	    key: "strikeOut",
	    value: function strikeOut() {
	      main_core.Dom.addClass(this.node, '--completed');
	    }
	  }, {
	    key: "unStrikeOut",
	    value: function unStrikeOut() {
	      main_core.Dom.removeClass(this.node, '--completed');
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Name;
	}(main_core_events.EventEmitter);

	var _templateObject$4;
	var Checklist = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Checklist, _EventEmitter);

	  function Checklist(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Checklist);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Checklist).call(this, params));
	    _this.complete = main_core.Type.isInteger(params.complete) ? parseInt(params.complete, 10) : 0;
	    _this.all = main_core.Type.isInteger(params.all) ? parseInt(params.all, 10) : 0;
	    _this.value = "".concat(_this.complete, "/").concat(_this.all);
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Checklist, [{
	    key: "render",
	    value: function render() {
	      var uiClasses = 'ui-label ui-label-sm ui-label-light';
	      this.node = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--entity-tasks ", " ", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.all ? '--visible' : '', uiClasses, this.value);
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.value;
	    }
	  }, {
	    key: "getComplete",
	    value: function getComplete() {
	      return this.complete;
	    }
	  }, {
	    key: "getAll",
	    value: function getAll() {
	      return this.all;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Checklist;
	}(main_core_events.EventEmitter);

	var _templateObject$5;
	var Files = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Files, _EventEmitter);

	  function Files(count) {
	    var _this;

	    babelHelpers.classCallCheck(this, Files);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Files).call(this, count));
	    _this.value = main_core.Type.isInteger(count) ? parseInt(count, 10) : 0;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Files, [{
	    key: "render",
	    value: function render() {
	      var uiClasses = 'ui-label ui-label-sm ui-label-light';
	      this.node = main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--attachment-counter ", " ", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.value ? '--visible' : '', uiClasses, this.value);
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.value;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Files;
	}(main_core_events.EventEmitter);

	var _templateObject$6;
	var Comments = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Comments, _EventEmitter);

	  function Comments(taskCounter) {
	    var _this;

	    babelHelpers.classCallCheck(this, Comments);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Comments).call(this, taskCounter));

	    if (main_core.Type.isUndefined(taskCounter) || main_core.Type.isNull(taskCounter)) {
	      taskCounter = {
	        color: '',
	        value: 0
	      };
	    }

	    _this.taskCounter = taskCounter;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Comments, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--comment-counter ", "\">\n\t\t\t\t<div class='ui-counter ", "'>\n\t\t\t\t\t<div class='ui-counter-inner'>", "</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.taskCounter.value ? '--visible' : '', this.taskCounter.color, parseInt(this.taskCounter.value, 10));
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.taskCounter;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Comments;
	}(main_core_events.EventEmitter);

	var _templateObject$7, _templateObject2;
	var Epic = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Epic, _EventEmitter);

	  function Epic(epic) {
	    var _this;

	    babelHelpers.classCallCheck(this, Epic);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Epic).call(this, epic));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.Epic');

	    if (main_core.Type.isUndefined(epic) || main_core.Type.isArray(epic) || main_core.Type.isNull(epic)) {
	      epic = {
	        id: 0,
	        groupId: 0,
	        name: '',
	        description: '',
	        createdBy: 0,
	        modifiedBy: 0,
	        color: ''
	      };
	    }

	    _this.epic = epic;
	    return _this;
	  }

	  babelHelpers.createClass(Epic, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--epic ", "\">\n\t\t\t\t<i\n\t\t\t\t\tclass=\"tasks-scrum__item--epic-point\"\n\t\t\t\t\tstyle=\"", "\"\n\t\t\t\t></i>\n\t\t\t\t<span>", "</span>\n\t\t\t</div>\n\t\t"])), this.epic.id ? '--visible' : '', "background-color: ".concat(this.epic.color), main_core.Text.encode(this.epic.name));
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "renderFullView",
	    value: function renderFullView() {
	      var colorBorder = this.convertHexToRGBA(this.epic.color, 0.7);
	      var colorBackground = this.convertHexToRGBA(this.epic.color, 0.3);
	      var visibility = this.epic.id > 0 ? '--visible' : '';
	      this.node = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__item--epic-full-view ", "\"\n\t\t\t\tstyle=\"background: ", "; border-color: ", ";\"\n\t\t\t>", "</div>\n\t\t"])), visibility, colorBackground, colorBorder, main_core.Text.encode(this.epic.name));
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.epic;
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.epic.id;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }, {
	    key: "convertHexToRGBA",
	    value: function convertHexToRGBA(hexCode, opacity) {
	      var hex = hexCode.replace('#', '');

	      if (hex.length === 3) {
	        hex = "".concat(hex[0]).concat(hex[0]).concat(hex[1]).concat(hex[1]).concat(hex[2]).concat(hex[2]);
	      }

	      var r = parseInt(hex.substring(0, 2), 16);
	      var g = parseInt(hex.substring(2, 4), 16);
	      var b = parseInt(hex.substring(4, 6), 16);
	      return "rgba(".concat(r, ",").concat(g, ",").concat(b, ",").concat(opacity, ")");
	    }
	  }]);
	  return Epic;
	}(main_core_events.EventEmitter);

	var _templateObject$8, _templateObject2$1, _templateObject3;
	var Tags = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Tags, _EventEmitter);

	  function Tags(tags) {
	    var _this;

	    babelHelpers.classCallCheck(this, Tags);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tags).call(this, tags));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.Tags');

	    _this.tags = main_core.Type.isArray(tags) ? tags : [];
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Tags, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      if (this.tags.length) {
	        this.node = main_core.Tag.render(_templateObject$8 || (_templateObject$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t"])), this.tags.map(function (tag) {
	          return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum__item--hashtag --visible\">#", "</div>"])), main_core.Text.encode(tag));
	        }));
	      } else {
	        this.node = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum__item--hashtag\"></div>"])));
	      }

	      if (main_core.Type.isArray(this.node)) {
	        this.node.forEach(function (node) {
	          main_core.Event.bind(node, 'click', _this2.onClick.bind(_this2));
	        });
	      } else {
	        main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      }

	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.tags;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick(event) {
	      this.emit('click', event.target.textContent.substring(1));
	    }
	  }, {
	    key: "isEqualTags",
	    value: function isEqualTags(tags) {
	      return JSON.stringify(this.getValue()) === JSON.stringify(tags.getValue());
	    }
	  }]);
	  return Tags;
	}(main_core_events.EventEmitter);

	var _templateObject$9;
	var Responsible = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Responsible, _EventEmitter);

	  function Responsible(responsible) {
	    var _this;

	    babelHelpers.classCallCheck(this, Responsible);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Responsible).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.Responsible');

	    _this.responsible = main_core.Type.isPlainObject(responsible) ? responsible : null;
	    return _this;
	  }

	  babelHelpers.createClass(Responsible, [{
	    key: "render",
	    value: function render() {
	      var uiClasses = 'ui-icon ui-icon-common-user';
	      var name = main_core.Text.encode(this.responsible.name);
	      var src = this.responsible.photo ? main_core.Text.encode(this.responsible.photo.src) : null;
	      var photoStyle = src ? "background-image: url('".concat(src, "');") : '';
	      this.node = main_core.Tag.render(_templateObject$9 || (_templateObject$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--responsible\">\n\t\t\t\t<div class=\"tasks-scrum__item--responsible-photo ", "\" title=\"", "\">\n\t\t\t\t\t<i style=\"", "\"></i>\n\t\t\t\t</div>\n\t\t\t\t<span>", "</span>\n\t\t\t</div>\n\t\t"])), uiClasses, name, photoStyle, name);
	      main_core.Event.bind(this.node.querySelector('div'), 'click', this.onClick.bind(this));
	      main_core.Event.bind(this.node.querySelector('span'), 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.responsible;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Responsible;
	}(main_core_events.EventEmitter);

	var StoryPointsStorage = /*#__PURE__*/function () {
	  function StoryPointsStorage() {
	    babelHelpers.classCallCheck(this, StoryPointsStorage);
	    this.storyPoints = '';
	  }

	  babelHelpers.createClass(StoryPointsStorage, [{
	    key: "setPoints",
	    value: function setPoints(storyPoints) {
	      if (storyPoints === '') {
	        this.storyPoints = '';
	        return;
	      }

	      if (main_core.Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints))) {
	        return;
	      }

	      if (main_core.Type.isFloat(storyPoints)) {
	        storyPoints = parseFloat(storyPoints).toFixed(1);
	      }

	      this.storyPoints = String(storyPoints);
	    }
	  }, {
	    key: "getPoints",
	    value: function getPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "clearPoints",
	    value: function clearPoints() {
	      this.storyPoints = '';
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.storyPoints === '';
	    }
	  }]);
	  return StoryPointsStorage;
	}();

	var _templateObject$a;
	var StoryPoints = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(StoryPoints, _EventEmitter);

	  function StoryPoints(storyPoints) {
	    var _this;

	    babelHelpers.classCallCheck(this, StoryPoints);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StoryPoints).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item.StoryPoints');

	    _this.storyPointsStorage = new StoryPointsStorage();

	    _this.storyPointsStorage.setPoints(storyPoints);

	    _this.disableStatus = false;
	    return _this;
	  }

	  babelHelpers.createClass(StoryPoints, [{
	    key: "render",
	    value: function render() {
	      var value = main_core.Text.encode(this.storyPointsStorage.getPoints());
	      this.node = main_core.Tag.render(_templateObject$a || (_templateObject$a = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__item--story-points ", "\"\n\t\t\t\ttitle=\"", "\"\n\t\t\t>\n\t\t\t\t<div class=\"tasks-scrum__item--story-points-content\">\n\t\t\t\t\t<div class=\"tasks-scrum__item--story-points-element\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__item--story-points-input-container\">\n\t\t\t\t\t\t<input\n\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\tclass=\"tasks-scrum__item--story-points-input\"\n\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.storyPointsStorage.isEmpty() ? '--empty' : '', main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS'), this.storyPointsStorage.isEmpty() ? '-' : value, value);
	      main_core.Event.bind(this.node.querySelector('.tasks-scrum__item--story-points-element'), 'click', this.onClick.bind(this));
	      var input = this.node.querySelector('.tasks-scrum__item--story-points-input');
	      main_core.Event.bind(input, 'blur', this.onBlur.bind(this));
	      main_core.Event.bind(input, 'keydown', this.onKeyDown.bind(input));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.storyPointsStorage;
	    }
	  }, {
	    key: "isDisable",
	    value: function isDisable() {
	      return this.disableStatus;
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.disableStatus = true;
	    }
	  }, {
	    key: "unDisable",
	    value: function unDisable() {
	      this.disableStatus = false;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.isDisable()) {
	        return;
	      }

	      var inputContainer = this.node.querySelector('.tasks-scrum__item--story-points-input-container');
	      var input = inputContainer.firstElementChild;
	      var value = this.storyPointsStorage.getPoints();
	      main_core.Dom.addClass(inputContainer, '--active');
	      input.focus();
	      input.setSelectionRange(value.length, value.length);
	    }
	  }, {
	    key: "onBlur",
	    value: function onBlur() {
	      var inputContainer = this.node.querySelector('.tasks-scrum__item--story-points-input-container');
	      var input = inputContainer.firstElementChild;
	      var value = input.value.trim();
	      var currentValue = this.storyPointsStorage.getPoints();

	      if (currentValue !== value) {
	        this.emit('setStoryPoints', value);
	      }

	      main_core.Dom.removeClass(inputContainer, '--active');
	    }
	  }, {
	    key: "onKeyDown",
	    value: function onKeyDown(event) {
	      if (event.isComposing || event.key === 'Escape' || event.key === 'Enter') {
	        this.blur();
	      }
	    }
	  }]);
	  return StoryPoints;
	}(main_core_events.EventEmitter);

	var _templateObject$b;
	var SubTasks = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SubTasks, _EventEmitter);

	  function SubTasks(parentItem) {
	    var _this;

	    babelHelpers.classCallCheck(this, SubTasks);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SubTasks).call(this));
	    _this.parentItem = parentItem;
	    _this.list = new Map();
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(SubTasks, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$b || (_templateObject$b = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum__item-sub-tasks\"></div>"])));
	      main_core.Event.bind(this.node, 'transitionend', this.onTransitionEnd.bind(this, this.node));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.list.size === 0;
	    }
	  }, {
	    key: "getParentItem",
	    value: function getParentItem() {
	      return this.parentItem;
	    }
	  }, {
	    key: "addTask",
	    value: function addTask(item) {
	      this.list.set(item.getId(), item);
	    }
	  }, {
	    key: "getList",
	    value: function getList() {
	      return this.list;
	    }
	  }, {
	    key: "cleanTasks",
	    value: function cleanTasks() {
	      this.list.forEach(function (item) {
	        main_core.Dom.remove(item.getNode());
	      });
	      this.list.clear();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (this.list.size) {
	        this.hideLoader();
	        this.renderSubTasks();
	      } else {
	        this.showLoader();
	      }

	      this.getNode().style.height = "".concat(this.getNode().scrollHeight, "px");
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.hideLoader();
	      this.getNode().style.height = "".concat(this.getNode().scrollHeight, "px");
	      this.getNode().clientHeight;
	      this.getNode().style.height = '0';
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this.node !== null;
	    }
	  }, {
	    key: "renderSubTasks",
	    value: function renderSubTasks() {
	      var _this2 = this;

	      this.node.innerHTML = '';
	      this.list.forEach(function (item) {
	        main_core.Dom.append(item.render(), _this2.getNode());
	      });
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      if (this.loader) {
	        this.loader.show();
	        return;
	      }

	      var listPosition = main_core.Dom.getPosition(this.getNode());
	      this.loader = new main_loader.Loader({
	        target: this.getNode(),
	        size: 60,
	        mode: 'inline',
	        color: 'rgba(82, 92, 105, 0.9)',
	        offset: {
	          top: "12px",
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      this.loader.show();
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      if (this.loader) {
	        this.loader.hide();
	      }
	    }
	  }, {
	    key: "onTransitionEnd",
	    value: function onTransitionEnd(node) {
	      var isHide = node.style.height === '0px';

	      if (isHide) {
	        this.removeYourself();
	      } else {
	        node.style.height = 'auto';
	      }
	    }
	  }]);
	  return SubTasks;
	}(main_core_events.EventEmitter);

	var _templateObject$c, _templateObject2$2;
	var Item = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Item, _EventEmitter);

	  function Item(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Item);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item');

	    _this.groupMode = false;
	    _this.node = null;
	    _this.toggle = null;
	    _this.name = null;
	    _this.checklist = null;
	    _this.files = null;
	    _this.comments = null;
	    _this.epic = null;
	    _this.tags = null;
	    _this.responsible = null;
	    _this.storyPoints = null;
	    _this.subTasks = null;

	    _this.setItemParams(params);

	    return _this;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "setItemParams",
	    value: function setItemParams(params) {
	      this.setId(params.id);
	      this.setTmpId(params.tmpId);
	      this.setSort(params.sort);
	      this.setEntityId(params.entityId);
	      this.setEntityType(params.entityType);
	      this.setSourceId(params.sourceId);
	      this.setInfo(params.info);
	      this.setSubTasksInfo(params.subTasksInfo);
	      this.setParentTask(params.isParentTask);
	      this.setLinkedTask(params.isLinkedTask);
	      this.setParentTaskId(params.parentTaskId);
	      this.setSubTask(params.isSubTask);
	      this.setCompleted(params.completed);
	      this.setDisableStatus(false);
	      this.setAllowedActions(params.allowedActions);
	      this.shortView = 'Y';
	    }
	  }, {
	    key: "setToggle",
	    value: function setToggle(visible) {
	      var toggle = new Toggle({
	        visible: visible
	      });

	      if (this.toggle) {
	        main_core.Dom.replace(this.toggle.getNode(), toggle.render());
	      }

	      this.toggle = toggle;
	      this.toggle.subscribe('show', this.onShowToggle.bind(this));
	      this.toggle.subscribe('hide', this.onHideToggle.bind(this));
	    }
	  }, {
	    key: "getToggle",
	    value: function getToggle() {
	      return this.toggle;
	    }
	  }, {
	    key: "setName",
	    value: function setName(inputName) {
	      var _this2 = this;

	      var name = new Name(inputName, this.isCompleted());

	      if (this.name) {
	        main_core.Dom.replace(this.name.getNode(), name.render());
	      }

	      this.name = name;
	      this.name.subscribe('click', function () {
	        return _this2.emit('showTask');
	      });
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "setChecklist",
	    value: function setChecklist(complete, all) {
	      var _this3 = this;

	      var checklist = new Checklist({
	        complete: complete,
	        all: all
	      });

	      if (this.checklist) {
	        main_core.Dom.replace(this.checklist.getNode(), checklist.render());
	      }

	      this.checklist = checklist;
	      this.checklist.subscribe('click', function () {
	        return _this3.emit('showTask');
	      });
	    }
	  }, {
	    key: "getChecklist",
	    value: function getChecklist() {
	      return this.checklist;
	    }
	  }, {
	    key: "setFiles",
	    value: function setFiles(count) {
	      var _this4 = this;

	      var files = new Files(count);

	      if (this.files) {
	        main_core.Dom.replace(this.files.getNode(), files.render());
	      }

	      this.files = files;
	      this.files.subscribe('click', function () {
	        return _this4.emit('showTask');
	      });
	    }
	  }, {
	    key: "getFiles",
	    value: function getFiles() {
	      return this.files;
	    }
	  }, {
	    key: "setComments",
	    value: function setComments(taskCounter) {
	      var _this5 = this;

	      var comments = new Comments(taskCounter);

	      if (this.comments) {
	        main_core.Dom.replace(this.comments.getNode(), comments.render());
	      }

	      this.comments = comments;
	      this.comments.subscribe('click', function () {
	        return _this5.emit('showTask');
	      });
	    }
	  }, {
	    key: "getComments",
	    value: function getComments() {
	      return this.comments;
	    }
	  }, {
	    key: "setEpic",
	    value: function setEpic(inputEpic) {
	      var _this6 = this;

	      var epic = new Epic(inputEpic);

	      if (this.epic) {
	        main_core.Dom.replace(this.epic.getNode(), this.isShortView() ? epic.render() : epic.renderFullView());
	      }

	      this.epic = epic;
	      this.updateTagsVisibility();
	      this.epic.subscribe('click', function () {
	        return _this6.emit('filterByEpic', _this6.epic.getId());
	      });
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic() {
	      return this.epic;
	    }
	  }, {
	    key: "setTags",
	    value: function setTags(inputTags) {
	      var _this7 = this;

	      var tags = new Tags(inputTags);

	      if (this.tags) {
	        if (this.getNode()) //todo
	          {
	            this.replaceTags(tags);
	          }
	      }

	      this.tags = tags;
	      this.updateTagsVisibility();
	      this.tags.subscribe('click', function (baseEvent) {
	        return _this7.emit('filterByTag', baseEvent.getData());
	      });
	    }
	  }, {
	    key: "getTags",
	    value: function getTags() {
	      return this.tags;
	    }
	  }, {
	    key: "setShortView",
	    value: function setShortView(value) {
	      this.shortView = value === 'Y' ? 'Y' : 'N';

	      if (this.getNode()) {
	        main_core.Dom.replace(this.getNode(), this.render());
	      }
	    }
	  }, {
	    key: "getShortView",
	    value: function getShortView() {
	      return this.shortView;
	    }
	  }, {
	    key: "isShortView",
	    value: function isShortView() {
	      return this.shortView === 'Y';
	    }
	  }, {
	    key: "setResponsible",
	    value: function setResponsible(inputResponsible) {
	      var responsible = new Responsible(inputResponsible);

	      if (this.responsible) {
	        main_core.Dom.replace(this.responsible.getNode(), responsible.render());
	      }

	      this.responsible = responsible;
	      this.responsible.subscribe('click', this.onResponsibleClick.bind(this));
	    }
	  }, {
	    key: "getResponsible",
	    value: function getResponsible() {
	      return this.responsible;
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(inputStoryPoints) {
	      var storyPoints = new StoryPoints(inputStoryPoints);

	      if (this.storyPoints) {
	        main_core.Dom.replace(this.storyPoints.getNode(), storyPoints.render());
	      }

	      if (this.isDisabled()) {
	        storyPoints.disable();
	      }

	      this.storyPoints = storyPoints;
	      this.storyPoints.subscribe('setStoryPoints', this.onSetStoryPoints.bind(this));
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "setSubTasks",
	    value: function setSubTasks() {
	      var _this8 = this;

	      this.subTasks = new SubTasks(this);
	      this.subTasks.subscribe('click', function () {
	        return _this8.emit('showTask');
	      });
	    }
	  }, {
	    key: "getSubTasks",
	    value: function getSubTasks() {
	      return this.subTasks;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = main_core.Type.isInteger(id) ? parseInt(id, 10) : main_core.Type.isString(id) && id ? id : main_core.Text.getRandom();

	      if (this.getNode()) {
	        this.getNode().dataset.id = this.id;
	      }
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
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
	      this.setPreviousSort(this.sort);
	      this.sort = main_core.Type.isInteger(sort) ? parseInt(sort, 10) : 0;

	      if (this.getNode()) {
	        main_core.Dom.attr(this.getNode(), 'data-sort', this.sort);
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
	      this.updateBorderColor();
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return this.entityType;
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
	    key: "setCompleted",
	    value: function setCompleted(value) {
	      var completed = value === 'Y';

	      if (this.name) {
	        if (completed) {
	          this.name.strikeOut();
	        } else {
	          this.name.unStrikeOut();
	        }
	      }

	      this.completed = completed;
	    }
	  }, {
	    key: "setAllowedActions",
	    value: function setAllowedActions(allowedActions) {
	      this.allowedActions = main_core.Type.isPlainObject(allowedActions) ? allowedActions : {};
	    }
	  }, {
	    key: "setSubTasksInfo",
	    value: function setSubTasksInfo(subTasksInfo) {
	      this.subTasksInfo = subTasksInfo;
	    }
	  }, {
	    key: "getSubTasksInfo",
	    value: function getSubTasksInfo() {
	      return this.subTasksInfo;
	    }
	  }, {
	    key: "getSubTasksCount",
	    value: function getSubTasksCount() {
	      if (!this.getSubTasksInfo()) {
	        return 0;
	      }

	      return Object.keys(this.getSubTasksInfo()).length;
	    }
	  }, {
	    key: "setParentTask",
	    value: function setParentTask(value) {
	      this.parentTask = value === 'Y';

	      if (this.getNode()) {
	        this.setToggle(this.isParentTask());

	        if (this.isParentTask()) {
	          main_core.Dom.addClass(this.getNode(), '--parent-tasks');

	          if (this.getSubTasksCount() > 1) {
	            main_core.Dom.addClass(this.getNode(), '--many');
	          } else {
	            main_core.Dom.removeClass(this.getNode(), '--many');
	          }
	        } else {
	          main_core.Dom.removeClass(this.getNode(), '--parent-tasks');
	        }
	      }
	    }
	  }, {
	    key: "isParentTask",
	    value: function isParentTask() {
	      return this.parentTask;
	    }
	  }, {
	    key: "setLinkedTask",
	    value: function setLinkedTask(value) {
	      this.linkedTask = value === 'Y';

	      if (this.getNode()) {
	        if (this.isLinkedTask() && !this.isSubTask()) {
	          main_core.Dom.addClass(this.getNode(), '--linked');
	        } else {
	          main_core.Dom.removeClass(this.getNode(), '--linked');
	        }
	      }
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

	      if (this.getNode()) {
	        if (this.isSubTask()) {
	          main_core.Dom.addClass(this.getNode(), '--subtasks');
	        } else {
	          main_core.Dom.removeClass(this.getNode(), '--subtasks');
	        }
	      }
	    }
	  }, {
	    key: "isSubTask",
	    value: function isSubTask() {
	      return this.subTask;
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
	      this.updateBorderColor();
	    }
	  }, {
	    key: "getBorderColor",
	    value: function getBorderColor() {
	      return main_core.Type.isString(this.info.borderColor) ? this.info.borderColor : '';
	    }
	  }, {
	    key: "updateBorderColor",
	    value: function updateBorderColor() {
	      if (this.isLinkedTask() && !this.isSubTask() && this.getNode() && this.getBorderColor() !== '') {
	        var colorNode = this.getNode().querySelector('.tasks-scrum__item--link');
	        main_core.Dom.style(colorNode, 'backgroundColor', this.getBorderColor());

	        switch (this.getEntityType()) {
	          case 'backlog':
	            main_core.Dom.style(this.getNode(), 'borderLeft', '3px solid' + this.getBorderColor());
	            break;

	          case 'sprint':
	            main_core.Dom.style(this.getNode(), 'borderLeft', null);
	            break;
	        }
	      }
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return this.completed;
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

	      if (this.isDisabled()) {
	        this.storyPoints.disable();
	      }
	    }
	  }, {
	    key: "activateGroupMode",
	    value: function activateGroupMode() {
	      main_core.Dom.addClass(this.getNode(), '--checked');
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      main_core.Dom.removeClass(this.getNode(), '--checked');
	    }
	  }, {
	    key: "activateLinkedMode",
	    value: function activateLinkedMode() {
	      main_core.Dom.addClass(this.getNode(), '--linked-mode');
	    }
	  }, {
	    key: "deactivateLinkedMode",
	    value: function deactivateLinkedMode() {
	      main_core.Dom.removeClass(this.getNode(), '--linked-mode');
	      main_core.Dom.removeClass(this.getNode(), '--linked-mode-current');
	    }
	  }, {
	    key: "activateCurrentLinkedMode",
	    value: function activateCurrentLinkedMode() {
	      main_core.Dom.addClass(this.getNode(), '--linked-mode-current');
	    }
	  }, {
	    key: "deactivateCurrentLinkedMode",
	    value: function deactivateCurrentLinkedMode() {
	      main_core.Dom.removeClass(this.getNode(), '--linked-mode-current');
	    }
	  }, {
	    key: "addItemToGroupMode",
	    value: function addItemToGroupMode() {
	      this.groupMode = true;
	      main_core.Dom.addClass(this.getNode(), ['--group-mode']);
	      this.getNode().querySelector('.tasks-scrum__item--group-mode-input').checked = true;
	    }
	  }, {
	    key: "removeItemFromGroupMode",
	    value: function removeItemFromGroupMode() {
	      this.groupMode = false;
	      main_core.Dom.removeClass(this.getNode(), ['--group-mode']);
	      this.getNode().querySelector('.tasks-scrum__item--group-mode-input').checked = false;
	    }
	  }, {
	    key: "isGroupMode",
	    value: function isGroupMode() {
	      return this.groupMode;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "showSubTasks",
	    value: function showSubTasks() {
	      main_core.Dom.addClass(this.getNode(), '--open');
	      this.toggle.show();
	      this.getSubTasks().show();
	    }
	  }, {
	    key: "hideSubTasks",
	    value: function hideSubTasks() {
	      main_core.Dom.removeClass(this.getNode(), '--open');
	      this.toggle.hide();
	      this.getSubTasks().hide();
	    }
	  }, {
	    key: "isShownSubTasks",
	    value: function isShownSubTasks() {
	      return this.getSubTasks().isShown();
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
	      this.setToggle(tmpItem.isParentTask());

	      if (this.getName().getValue() !== tmpItem.getName().getValue()) {
	        this.setName(tmpItem.getName().getValue());
	      }

	      if (this.getChecklist().getValue() !== tmpItem.getChecklist().getValue()) {
	        this.setChecklist(tmpItem.getChecklist().getComplete(), tmpItem.getChecklist().getAll());
	      }

	      if (this.getFiles().getValue() !== tmpItem.getFiles().getValue()) {
	        this.setFiles(tmpItem.getFiles().getValue());
	      }

	      if (this.getComments().getValue() !== tmpItem.getComments().getValue()) {
	        this.setComments(tmpItem.getComments().getValue());
	      }

	      if (this.getEpic().getValue() !== tmpItem.getEpic().getValue()) {
	        this.setEpic(tmpItem.getEpic().getValue());
	      }

	      if (!this.getTags().isEqualTags(tmpItem.getTags())) {
	        this.setTags(tmpItem.getTags().getValue());
	      }

	      if (this.getResponsible().getValue() !== tmpItem.getResponsible().getValue()) {
	        this.setResponsible(tmpItem.getResponsible().getValue());
	      }

	      if (!this.isSubTask() && this.getStoryPoints().getValue().getPoints() !== tmpItem.getStoryPoints().getValue().getPoints()) {
	        this.setStoryPoints(tmpItem.getStoryPoints().getValue().getPoints());
	      }

	      this.setEntityId(tmpItem.getEntityId());

	      if (this.isCompleted() !== tmpItem.isCompleted()) {
	        this.setCompleted(tmpItem.isCompleted() ? 'Y' : 'N');
	      }

	      this.setParentTask(tmpItem.isParentTask() ? 'Y' : 'N');
	      this.setLinkedTask(tmpItem.isLinkedTask() ? 'Y' : 'N');
	      this.setParentTaskId(tmpItem.getParentTaskId());
	      this.setSubTask(tmpItem.isSubTask() ? 'Y' : 'N');
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (this.isShortView()) {
	        return this.renderShortView();
	      } else {
	        return this.renderFullView();
	      }
	    }
	  }, {
	    key: "renderShortView",
	    value: function renderShortView() {
	      var typeClass = (this.isParentTask() ? ' --parent-tasks ' : ' ') + (this.isSubTask() ? ' --subtasks ' : '');
	      var subClass = this.getSubTasksCount() > 1 ? ' --many ' : '';
	      var linkedClass = this.isLinkedTask() && !this.isSubTask() ? ' --linked ' : '';
	      var entityClass = '--item-' + this.getEntityType();
	      this.node = main_core.Tag.render(_templateObject$c || (_templateObject$c = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__item", "", "", "tasks-scrum__item--drag --short-view ", "\"\n\t\t\t\tdata-id=\"", "\"\n\t\t\t\tdata-sort=\"", "\"\n\t\t\t>\n\t\t\t\t<div class=\"tasks-scrum__item--link\"></div>\n\t\t\t\t<div class=\"tasks-scrum__item--info\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum__item--main-info\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"tasks-scrum__item--tags\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__item--entity-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"tasks-scrum__item--counter-container\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum__item--group-mode\">\n\t\t\t\t\t<input type=\"checkbox\" class=\"tasks-scrum__item--group-mode-input\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__item--substrate\"></div>\n\t\t\t\t<div class=\"tasks-scrum__item--dragstrate\"></div>\n\t\t\t</div>\n\t\t"])), typeClass, subClass, linkedClass, entityClass, main_core.Text.encode(this.getId()), main_core.Text.encode(this.getSort()), this.toggle ? this.toggle.render() : '', this.name ? this.name.render() : '', this.epic ? this.epic.render() : '', this.tags ? this.tags.render() : '', this.comments ? this.comments.render() : '', this.files ? this.files.render() : '', this.checklist ? this.checklist.render() : '', this.responsible ? this.responsible.render() : '', !this.isSubTask() && this.storyPoints ? this.storyPoints.render() : '');
	      main_core.Event.bind(this.node, 'click', this.onItemClick.bind(this));
	      this.updateBorderColor();
	      return this.node;
	    }
	  }, {
	    key: "renderFullView",
	    value: function renderFullView() {
	      var typeClass = (this.isParentTask() ? ' --parent-tasks ' : ' ') + (this.isSubTask() ? ' --subtasks ' : '');
	      var subClass = this.getSubTasksCount() > 1 ? ' --many ' : '';
	      var linkedClass = this.isLinkedTask() && !this.isSubTask() ? ' --linked ' : '';
	      var entityClass = '--item-' + this.getEntityType();
	      this.node = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__item", "", "", "tasks-scrum__item--drag --full-view ", "\"\n\t\t\t\tdata-id=\"", "\"\n\t\t\t\tdata-sort=\"", "\"\n\t\t\t>\n\t\t\t<div class=\"tasks-scrum__item--info-task--basic\">\n\t\t\t\t<div class=\"tasks-scrum__item--link\"></div>\n\t\t\t\t<div class=\"tasks-scrum__item--info\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum__item--main-info\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"tasks-scrum__item--tags\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum__item--info-task--details\">\n\t\t\t\t", "\n\n\t\t\t\t\t<div class=\"tasks-scrum__item--counter-container\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\n\t\t\t\t", "\n\t\t\t</div>\n\n\t\t\t\t<div class=\"tasks-scrum__item--group-mode\">\n\t\t\t\t\t<input type=\"checkbox\" class=\"tasks-scrum__item--group-mode-input\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__item--substrate\"></div>\n\t\t\t\t<div class=\"tasks-scrum__item--dragstrate\"></div>\n\t\t\t</div>\n\t\t"])), typeClass, subClass, linkedClass, entityClass, main_core.Text.encode(this.getId()), main_core.Text.encode(this.getSort()), this.toggle ? this.toggle.render() : '', this.name ? this.name.render() : '', this.epic ? this.epic.renderFullView() : '', this.tags ? this.tags.render() : '', this.comments ? this.comments.render() : '', this.responsible ? this.responsible.render() : '', this.files ? this.files.render() : '', this.checklist ? this.checklist.render() : '', !this.isSubTask() && this.storyPoints ? this.storyPoints.render() : '');
	      this.updateTagsVisibility();
	      main_core.Event.bind(this.node, 'click', this.onItemClick.bind(this));
	      this.updateBorderColor();
	      return this.node;
	    }
	  }, {
	    key: "updateTagsVisibility",
	    value: function updateTagsVisibility() {
	      if (!this.getNode()) {
	        return;
	      }

	      if (this.epic.getValue().id > 0 || this.tags.getValue().length > 0) {
	        main_core.Dom.addClass(this.getNode().querySelector('.tasks-scrum__item--tags'), '--visible');
	      } else {
	        main_core.Dom.removeClass(this.getNode().querySelector('.tasks-scrum__item--tags'), '--visible');
	      }
	    }
	  }, {
	    key: "replaceTags",
	    value: function replaceTags(tags) {
	      var tagsContainer = this.getNode().querySelector('.tasks-scrum__item--tags');
	      var tagsNode = tagsContainer.querySelectorAll('.tasks-scrum__item--hashtag');
	      tagsNode.forEach(function (tagNode) {
	        return main_core.Dom.remove(tagNode);
	      });
	      var tagList = tags.render();

	      if (main_core.Type.isArray(tagList)) {
	        tagList.forEach(function (tagNode) {
	          return main_core.Dom.append(tagNode, tagsContainer);
	        });
	      } else {
	        main_core.Dom.append(tagList, tagsContainer);
	      }
	    }
	  }, {
	    key: "onItemClick",
	    value: function onItemClick(event) {
	      var target = event.target;

	      if (target.classList.contains('tasks-scrum__item--link')) {
	        this.emit('showLinked');
	        return;
	      }

	      if (this.isDisabled()) {
	        return;
	      }

	      if (this.toggle && this.hasNode(this.toggle.getNode(), target)) {
	        return;
	      }

	      if (this.name && this.hasNode(this.name.getNode(), target)) {
	        return;
	      }

	      if (this.checklist && this.hasNode(this.checklist.getNode(), target)) {
	        return;
	      }

	      if (this.files && this.hasNode(this.files.getNode(), target)) {
	        return;
	      }

	      if (this.comments && this.hasNode(this.comments.getNode(), target)) {
	        return;
	      }

	      if (this.epic && this.hasNode(this.epic.getNode(), target)) {
	        return;
	      }

	      if (this.tags && this.hasNode(this.tags.getNode(), target)) {
	        return;
	      }

	      if (this.responsible && this.hasNode(this.responsible.getNode(), target, true)) {
	        return;
	      }

	      if (this.isSubTask()) {
	        return;
	      }

	      if (this.storyPoints && this.hasNode(this.storyPoints.getNode(), target)) {
	        return;
	      }

	      this.emit('toggleActionPanel');
	    }
	  }, {
	    key: "onResponsibleClick",
	    value: function onResponsibleClick() {
	      var _this9 = this;

	      if (this.isGroupMode()) {
	        return;
	      }

	      if (this.isDisabled()) {
	        return;
	      }

	      if (this.responsibleDialog) {
	        this.responsibleDialog.destroy();
	        this.responsibleDialog = null;
	        return;
	      }

	      this.responsibleDialog = new ui_entitySelector.Dialog({
	        targetNode: this.responsible.getNode(),
	        enableSearch: true,
	        context: 'TASKS',
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            _this9.responsibleDialog.hide();

	            var selectedItem = event.getData().item;

	            _this9.setResponsible({
	              id: selectedItem.getId(),
	              name: selectedItem.getTitle(),
	              photo: {
	                src: selectedItem.getAvatar()
	              }
	            });

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
	      this.responsibleDialog.show();
	    }
	  }, {
	    key: "onSetStoryPoints",
	    value: function onSetStoryPoints(baseEvent) {
	      if (this.isDisabled()) {
	        return;
	      }

	      this.emit('updateItem', {
	        itemId: this.getId(),
	        entityId: this.getEntityId(),
	        storyPoints: baseEvent.getData()
	      });
	      this.setStoryPoints(baseEvent.getData());
	    }
	  }, {
	    key: "onShowToggle",
	    value: function onShowToggle() {
	      main_core.Dom.addClass(this.getNode(), '--open');
	      this.toggle.show();
	      this.emit('showSubTasks', this.getSubTasks());
	    }
	  }, {
	    key: "onHideToggle",
	    value: function onHideToggle() {
	      this.hideSubTasks();
	    }
	  }, {
	    key: "hasNode",
	    value: function hasNode(parentNode, searchNode) {
	      var _this10 = this;

	      var skipParent = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

	      if (main_core.Type.isArray(parentNode)) {
	        var result = parentNode.map(function (node) {
	          return _this10.hasNode(node, searchNode, skipParent);
	        }).find(function (result) {
	          return result === true;
	        });
	        return !main_core.Type.isUndefined(result);
	      }

	      if (!skipParent && searchNode.isEqualNode(parentNode)) {
	        return true;
	      }

	      var nodes = parentNode.getElementsByTagName('*');

	      for (var k = 0; k < nodes.length; k++) {
	        if (searchNode.isEqualNode(nodes[k])) {
	          return true;
	        }
	      }

	      return false;
	    }
	  }, {
	    key: "activateBlinking",
	    value: function activateBlinking() {
	      var _this11 = this;

	      if (!this.getNode()) {
	        return;
	      }

	      if (typeof IntersectionObserver === "undefined") {
	        return;
	      }

	      var observer = new IntersectionObserver(function (entries) {
	        if (entries[0].isIntersecting === true) {
	          _this11.blink();

	          observer.disconnect();
	        }
	      }, {
	        threshold: [0]
	      });
	      observer.observe(this.getNode());
	    }
	  }, {
	    key: "blink",
	    value: function blink() {
	      var _this12 = this;

	      if (!this.getNode()) {
	        return;
	      }

	      main_core.Dom.addClass(this.getNode(), '--blink');
	      setTimeout(function () {
	        main_core.Dom.removeClass(_this12.getNode(), '--blink');
	      }, 300);
	    }
	  }], [{
	    key: "buildItem",
	    value: function buildItem(params) {
	      var item = new Item(params);
	      item.setToggle(item.isParentTask());
	      item.setName(params.name);
	      item.setChecklist(params.checkListComplete, params.checkListAll);
	      item.setFiles(params.attachedFilesCount);
	      item.setComments(params.taskCounter);
	      item.setEpic(params.epic);
	      item.setTags(params.tags);
	      item.setResponsible(params.responsible);

	      if (!item.isSubTask()) {
	        item.setStoryPoints(params.storyPoints);
	      }

	      item.setSubTasks();
	      return item;
	    }
	  }]);
	  return Item;
	}(main_core_events.EventEmitter);

	var _templateObject$d, _templateObject2$3;
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

	      this.node = main_core.Tag.render(_templateObject$d || (_templateObject$d = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content-items\" data-entity-id=\"", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.entity.getId(), babelHelpers.toConsumableArray(this.entity.getItems().values()).map(function (item) {
	        item.setEntityType(_this.entity.getEntityType());
	        return item.render();
	      }), this.renderLoader());
	      return this.node;
	    }
	  }, {
	    key: "renderLoader",
	    value: function renderLoader() {
	      return main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-entity-items-loader\"></div>"])));
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getListNode",
	    value: function getListNode() {
	      return this.node;
	    }
	  }, {
	    key: "setEntityId",
	    value: function setEntityId(entityId) {
	      this.node.dataset.entityId = parseInt(entityId, 10);
	    }
	  }, {
	    key: "addScrollbar",
	    value: function addScrollbar() {
	      main_core.Dom.addClass(this.getNode(), '--scrollbar');
	    }
	  }, {
	    key: "removeScrollbar",
	    value: function removeScrollbar() {
	      main_core.Dom.removeClass(this.getNode(), '--scrollbar');
	    }
	  }]);
	  return ListItems;
	}();

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
	      tagName = tagName === '' ? main_core.Text.getRandom() : tagName;
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
	        title: epicName,
	        avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag-green.svg',
	        name: epicName,
	        description: epic.description,
	        color: epic.color,
	        groupId: epic.groupId,
	        createdBy: epic.createdBy,
	        modifiedBy: epic.modifiedBy
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
	    key: "getEpicById",
	    value: function getEpicById(epicId) {
	      return babelHelpers.toConsumableArray(this.allTags.values()).find(function (epic) {
	        return epic.entityId === 'epic' && epic.id === epicId;
	      });
	    }
	  }, {
	    key: "showTagsDialog",
	    value: function showTagsDialog(item, targetNode) {
	      var _this2 = this;

	      var currentTags = item.getTags().getValue();
	      var selectedItems = [];
	      currentTags.forEach(function (tag) {
	        var currentTag = _this2.allTags.get(('tag_' + tag).trim());

	        if (currentTag) {
	          selectedItems.push(currentTag);
	        }
	      });
	      var choiceWasMade = false;
	      this.tagDialog = new ui_entitySelector.Dialog({
	        id: item.getId(),
	        targetNode: targetNode,
	        width: 400,
	        height: 300,
	        multiple: true,
	        dropdownMode: true,
	        enableSearch: true,
	        searchOptions: {
	          allowCreateItem: true,
	          footerOptions: {
	            label: main_core.Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')
	          }
	        },
	        offsetTop: 12,
	        selectedItems: selectedItems,
	        items: this.getTagsList(),
	        events: {
	          'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	            return new Promise(function (resolve) {
	              var _event$getData = event.getData(),
	                  searchQuery = _event$getData.searchQuery;

	              var dialog = event.getTarget();
	              var tagName = searchQuery.getQuery();

	              if (!tagName) {
	                dialog.focusSearch();
	                return;
	              }

	              _this2.addTagToSearcher(tagName);

	              var newTag = _this2.getTagFromSearcher('tag_' + tagName);

	              var item = dialog.addItem(newTag);
	              item.select();
	              dialog.getTagSelector().clearTextBox();
	              dialog.focusSearch();
	              dialog.selectFirstTab();
	              var label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
	              label.textContent = '';
	              resolve();
	            });
	          },
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
	        }
	      });
	      this.tagDialog.subscribe('onHide', function () {
	        if (choiceWasMade) {
	          _this2.emit('hideTagDialog');
	        }
	      });
	      this.tagDialog.show();
	    }
	  }, {
	    key: "showEpicDialog",
	    value: function showEpicDialog(item, targetNode) {
	      var _this3 = this;

	      var currentEpic = item.getEpic().getValue();
	      var selectedItems = [];

	      if (currentEpic) {
	        var currentEpicInfo = this.allTags.get(('epic_' + currentEpic.name).trim());

	        if (currentEpicInfo) {
	          selectedItems.push(currentEpicInfo);
	        }
	      }

	      var choiceWasMade = false;
	      this.epicDialog = new ui_entitySelector.Dialog({
	        id: item.getId(),
	        targetNode: targetNode,
	        width: 400,
	        height: 300,
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        offsetTop: 12,
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

	              if (_this3.epicDialog.getSelectedItems().length === 0) {
	                _this3.emit('updateItemEpic', 0);

	                _this3.epicDialog.hide();
	              }
	            }, 50);
	          }
	        },
	        tagSelectorOptions: {
	          placeholder: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
	        }
	      });
	      this.epicDialog.subscribe('onHide', function () {
	        if (choiceWasMade) {
	          _this3.emit('hideEpicDialog');
	        }
	      });
	      this.epicDialog.show();
	    }
	  }, {
	    key: "isEpicDialogShown",
	    value: function isEpicDialogShown() {
	      return this.epicDialog && this.epicDialog.isOpen();
	    }
	  }, {
	    key: "hasActionPanelDialog",
	    value: function hasActionPanelDialog() {
	      return this.epicDialog || this.tagDialog;
	    }
	  }, {
	    key: "showTagsSearchDialog",
	    value: function showTagsSearchDialog(inputObject, enteredHashTagName) {
	      var _this4 = this;

	      var input = inputObject.getInputNode();

	      if (this.tagSearchDialog && this.tagSearchDialog.getId() !== inputObject.getNodeId()) {
	        this.tagSearchDialog = null;
	      }

	      if (!this.tagSearchDialog) {
	        this.tagSearchDialog = new ui_entitySelector.Dialog({
	          id: inputObject.getNodeId(),
	          targetNode: inputObject.getNode(),
	          width: inputObject.getNode().offsetWidth,
	          height: 210,
	          multiple: false,
	          dropdownMode: true,
	          items: this.getTagsList(),
	          searchOptions: {
	            allowCreateItem: true,
	            footerOptions: {
	              label: main_core.Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')
	            }
	          },
	          events: {
	            'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	              return new Promise(function (resolve) {
	                var dialog = event.getTarget();
	                dialog.hide();
	                input.focus();
	                resolve();
	              });
	            },
	            'Item:onSelect': function ItemOnSelect(event) {
	              var selectedItem = event.getData().item;
	              var selectedHashTag = '#' + selectedItem.getTitle();
	              var hashTags = TagSearcher.getHashTagNamesFromText(input.value);
	              var enteredHashTag = hashTags.length > 0 ? hashTags.pop() : '';
	              input.value = input.value.replace(new RegExp('#' + enteredHashTag, 'g'), selectedHashTag);
	              input.focus();
	              selectedItem.deselect();
	            }
	          }
	        });
	        this.tagSearchDialog.subscribe('onHide', function () {
	          inputObject.setTagsSearchMode(false);
	        });
	        inputObject.subscribeOnce('onMetaEnter', function () {
	          _this4.tagSearchDialog.hide();

	          input.focus();
	        });
	      }

	      inputObject.setTagsSearchMode(true);
	      this.tagSearchDialog.show();
	      this.tagSearchDialog.search(enteredHashTagName);
	    }
	  }, {
	    key: "closeTagsSearchDialog",
	    value: function closeTagsSearchDialog() {
	      if (this.tagSearchDialog) {
	        this.tagSearchDialog.hide();
	      }
	    }
	  }, {
	    key: "showEpicSearchDialog",
	    value: function showEpicSearchDialog(inputObject, enteredHashEpicName) {
	      var _this5 = this;

	      var input = inputObject.getInputNode();

	      if (this.epicSearchDialog && this.epicSearchDialog.getId() !== inputObject.getNodeId()) {
	        this.epicSearchDialog = null;
	      }

	      if (!this.epicSearchDialog) {
	        this.epicSearchDialog = new ui_entitySelector.Dialog({
	          id: inputObject.getNodeId(),
	          targetNode: inputObject.getNode(),
	          width: inputObject.getNode().offsetWidth,
	          height: 210,
	          multiple: false,
	          dropdownMode: true,
	          searchOptions: {
	            allowCreateItem: true,
	            footerOptions: {
	              label: main_core.Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_EPIC_ADD')
	            }
	          },
	          items: this.getEpicList(),
	          events: {
	            'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	              return new Promise(function (resolve) {
	                var dialog = event.getTarget();

	                var _event$getData2 = event.getData(),
	                    searchQuery = _event$getData2.searchQuery;

	                _this5.emit('createEpic', searchQuery.getQuery());

	                dialog.hide();
	                input.focus();
	                _this5.epicSearchDialog = null;
	                resolve();
	              });
	            },
	            'Item:onSelect': function ItemOnSelect(event) {
	              var selectedItem = event.getData().item;
	              var selectedHashEpic = '@' + selectedItem.getTitle();
	              input.value = input.value.replace(selectedHashEpic === '' ? '@' : new RegExp(TagSearcher.epicRegExp, 'g'), '');
	              input.value = input.value + selectedHashEpic;
	              input.focus();
	              selectedItem.deselect();
	              inputObject.setEpic(_this5.getEpicByName(selectedItem.getTitle()));
	            }
	          }
	        });
	        this.epicSearchDialog.subscribe('onHide', function () {
	          inputObject.setEpicSearchMode(false);
	        });
	        inputObject.subscribe('onMetaEnter', function () {
	          _this5.emit('createEpic', _this5.epicSearchDialog.getSearchTab().getLastSearchQuery().getQuery());

	          _this5.epicSearchDialog.hide();

	          _this5.epicSearchDialog = null;
	          input.focus();
	        });
	      }

	      inputObject.setEpicSearchMode(true);
	      this.epicSearchDialog.show();
	      this.epicSearchDialog.search(enteredHashEpicName);
	    }
	  }, {
	    key: "closeEpicSearchDialog",
	    value: function closeEpicSearchDialog() {
	      if (this.epicSearchDialog) {
	        this.epicSearchDialog.hide();
	      }
	    }
	  }], [{
	    key: "getHashTagNamesFromText",
	    value: function getHashTagNamesFromText(inputText) {
	      var regex = new RegExp(TagSearcher.tagRegExp, 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[0].substring(1));
	      }

	      return matches;
	    }
	  }, {
	    key: "getHashEpicNamesFromText",
	    value: function getHashEpicNamesFromText(inputText) {
	      var regex = new RegExp(TagSearcher.epicRegExp, 'g');
	      var matches = [];
	      var match;

	      while (match = regex.exec(inputText)) {
	        matches.push(match[0].substring(1));
	      }

	      return matches;
	    }
	  }]);
	  return TagSearcher;
	}(main_core_events.EventEmitter);
	babelHelpers.defineProperty(TagSearcher, "tagRegExp", '#[^#@](?:[^#@]*[^\s#@])?');
	babelHelpers.defineProperty(TagSearcher, "epicRegExp", '@[^#@](?:[^#@]*[^\s#@])?');

	var _templateObject$e;
	var Input = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Input, _EventEmitter);

	  function Input() {
	    var _this;

	    babelHelpers.classCallCheck(this, Input);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Input).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.Input');

	    _this.entity = null;
	    _this.bindNode = null;
	    _this.node = null;
	    _this.value = '';
	    _this.epic = null;
	    _this.taskCreated = false;
	    return _this;
	  }

	  babelHelpers.createClass(Input, [{
	    key: "setEntity",
	    value: function setEntity(entity) {
	      this.entity = entity;
	    }
	  }, {
	    key: "getEntity",
	    value: function getEntity() {
	      return this.entity;
	    }
	  }, {
	    key: "setBindNode",
	    value: function setBindNode(node) {
	      this.bindNode = node;
	    }
	  }, {
	    key: "getBindNode",
	    value: function getBindNode() {
	      return this.bindNode;
	    }
	  }, {
	    key: "cleanBindNode",
	    value: function cleanBindNode() {
	      this.bindNode = null;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      this.nodeId = main_core.Text.getRandom();
	      this.node = main_core.Tag.render(_templateObject$e || (_templateObject$e = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum__item --add-block\">\n\t\t\t\t<textarea\n\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\tclass=\"tasks-scrum__item--textarea\"\n\t\t\t\t>", "</textarea>\n\t\t\t\t<div class=\"tasks-scrum__item--textarea-help\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.nodeId), main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER'), main_core.Text.encode(this.value), main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER_HELPER'));
	      main_core.Event.bind(this.getInputNode(), 'input', function (event) {
	        _this2.onTagSearch(event);

	        _this2.onEpicSearch(event);
	      });
	      main_core.Event.bind(this.getInputNode(), 'keydown', this.onKeydown.bind(this));
	      main_core.Event.bind(this.getInputNode(), 'blur', this.onBlur.bind(this));
	      this.emit('render');
	      this.taskCreated = false;
	      return this.node;
	    }
	  }, {
	    key: "onKeydown",
	    value: function onKeydown(event) {
	      if (event.isComposing || event.key === 'Escape' || event.key === 'Enter') {
	        if (!this.isTagsSearchMode() && !this.isEpicSearchMode()) {
	          this.getInputNode().blur();
	          event.stopImmediatePropagation();
	        }

	        if (event.key === 'Enter') {
	          this.emit('onEnter', {
	            event: event
	          });

	          if (main_core.Browser.isMac() && event.metaKey || event.ctrlKey) {
	            this.emit('onMetaEnter', {
	              event: event
	            });
	          }
	        }
	      }
	    }
	  }, {
	    key: "onBlur",
	    value: function onBlur() {
	      if (this.isTagsSearchMode() || this.isEpicSearchMode()) {
	        return;
	      }

	      this.disable();
	      var input = this.getInputNode();

	      if (input.value === '') {
	        this.removeYourself();
	      } else {
	        this.createTaskItem();
	      }
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

	      if (this.isTagsSearchMode()) {
	        var enteredHashTagName = enteredHashTags.pop();
	        this.emit('tagsSearchOpen', main_core.Type.isUndefined(enteredHashTagName) ? '' : enteredHashTagName);
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

	      if (this.isEpicSearchMode()) {
	        var enteredHashTagName = enteredHashEpics.pop();
	        this.emit('epicSearchOpen', main_core.Type.isUndefined(enteredHashTagName) ? '' : enteredHashTagName);
	      } else {
	        this.emit('epicSearchClose');
	      }
	    }
	  }, {
	    key: "focus",
	    value: function focus() {
	      var input = this.getInputNode();
	      var length = input.value.length;
	      input.focus();
	      input.setSelectionRange(length, length);
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      main_core.Dom.addClass(this.node, '--disabled');
	      this.getInputNode().disabled = true;
	    }
	  }, {
	    key: "unDisable",
	    value: function unDisable() {
	      main_core.Dom.removeClass(this.node, '--disabled');
	      this.getInputNode().disabled = false;
	    }
	  }, {
	    key: "isTaskCreated",
	    value: function isTaskCreated() {
	      return this.taskCreated;
	    }
	  }, {
	    key: "setEpic",
	    value: function setEpic(epic) {
	      this.epic = epic;
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
	      return this.node.querySelector('textarea');
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic() {
	      return this.epic;
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	      this.emit('remove');
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
	    key: "createTaskItem",
	    value: function createTaskItem() {
	      if (!this.isTagsSearchMode() && !this.isEpicSearchMode()) {
	        var input = this.getInputNode();

	        if (input.value) {
	          this.emit('createTaskItem', input.value);
	          this.taskCreated = true;
	          input.value = '';
	        }
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

	    _this.storyPoints = new StoryPointsStorage();
	    _this.items = new Map();
	    _this.groupMode = false;
	    _this.groupModeItems = new Map();

	    _this.setEntityParams(params);

	    _this.node = null;
	    _this.listItems = null;
	    _this.itemsLoaderNode = null;
	    _this.blank = null;
	    _this.dropzone = null;
	    return _this;
	  }

	  babelHelpers.createClass(Entity, [{
	    key: "setEntityParams",
	    value: function setEntityParams(params) {
	      this.setId(params.id);
	      this.setViews(params.views);
	      this.setNumberTasks(params.numberTasks);
	      this.setStoryPoints(params.storyPoints);
	      this.setShortView(params.isShortView);
	      this.setMandatory(params.mandatoryExists);
	      this.exactSearchApplied = params.isExactSearchApplied === 'Y';
	      this.pageSize = parseInt(params.pageSize, 10);
	      this.pageNumberItems = main_core.Type.isInteger(params.pageNumberItems) ? parseInt(params.pageNumberItems, 10) : 1;
	    }
	  }, {
	    key: "setListItems",
	    value: function setListItems(entity) {
	      this.listItems = new ListItems(entity);
	    }
	  }, {
	    key: "getListItems",
	    value: function getListItems() {
	      return this.listItems;
	    }
	  }, {
	    key: "setShortView",
	    value: function setShortView(value) {
	      var _this2 = this;

	      this.shortView = value === 'Y' ? 'Y' : 'N';
	      this.getItems().forEach(function (item) {
	        if (item.isParentTask() && item.isShownSubTasks()) {
	          item.hideSubTasks();
	        }

	        item.setShortView(_this2.shortView);
	      });
	    }
	  }, {
	    key: "getShortView",
	    value: function getShortView() {
	      return this.shortView;
	    }
	  }, {
	    key: "isShortView",
	    value: function isShortView() {
	      return this.shortView === 'Y';
	    }
	  }, {
	    key: "setMandatory",
	    value: function setMandatory(value) {
	      this.mandatoryExists = value === 'Y';
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
	    key: "isBacklog",
	    value: function isBacklog() {
	      return this.getEntityType() === 'backlog';
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
	      return this.listItems ? this.listItems.getListNode() : null;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.items.size === 0;
	    }
	  }, {
	    key: "getNumberItems",
	    value: function getNumberItems() {
	      return this.items.size;
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return this.items;
	    }
	  }, {
	    key: "hasItem",
	    value: function hasItem(item) {
	      return this.items.has(item.getId());
	    }
	  }, {
	    key: "getPageSize",
	    value: function getPageSize() {
	      return this.pageSize;
	    }
	  }, {
	    key: "getPageNumberItems",
	    value: function getPageNumberItems() {
	      return this.pageNumberItems;
	    }
	  }, {
	    key: "incrementPageNumberItems",
	    value: function incrementPageNumberItems() {
	      this.pageNumberItems++;
	    }
	  }, {
	    key: "recalculateItemsSort",
	    value: function recalculateItemsSort() {
	      var _this3 = this;

	      var listItemsNode = this.getListItemsNode();

	      if (!listItemsNode) {
	        return;
	      }

	      var sort = 1;
	      listItemsNode.querySelectorAll('.tasks-scrum-item').forEach(function (node) {
	        var item = _this3.getItems().get(parseInt(node.dataset.id, 10));

	        if (item) {
	          item.setSort(sort);
	          sort++;
	        }
	      });
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      var _this4 = this;

	      this.items.set(newItem.getId(), newItem);
	      this.subscribeToItem(newItem);
	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this4.setItemMoveActivity(item);
	      });
	      newItem.setEntityType(this.getEntityType());
	      newItem.setShortView(this.getShortView());
	      this.hideBlank();
	      this.hideDropzone();
	      this.adjustListItemsWidth();
	    }
	  }, {
	    key: "setItemMoveActivity",
	    value: function setItemMoveActivity(item) {
	      item.setMoveActivity(this.items.size > 2);
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      var _this5 = this;

	      if (this.items.has(item.getId())) {
	        this.items.delete(item.getId());
	        item.unsubscribeAll();
	        babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	          _this5.setItemMoveActivity(item);
	        });

	        if (item.isParentTask()) {
	          item.getSubTasks().getList().forEach(function (item) {
	            _this5.removeItem(item);
	          });
	        }

	        this.pageNumberItems = 1;
	        this.adjustListItemsWidth();
	      }
	    }
	  }, {
	    key: "appendItemToList",
	    value: function appendItemToList(item) {
	      main_core.Dom.insertBefore(item.render(), this.getListItemsNode().lastElementChild);
	      this.adjustListItemsWidth();
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
	      var _this6 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this6.subscribeToItem(item);

	        _this6.setItemMoveActivity(item);
	      });
	      this.setStats();

	      if (!this.isCompleted()) {
	        this.itemsLoaderNode = this.getNode().querySelector('.tasks-scrum-entity-items-loader');
	        this.bindItemsLoader(this.itemsLoaderNode);
	      }

	      this.adjustListItemsWidth();
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this7 = this;

	      if (!this.getListItemsNode()) {
	        return;
	      }

	      item.setEntityType(this.getEntityType());
	      item.subscribe('updateItem', function (baseEvent) {
	        _this7.emit('updateItem', baseEvent.getData());
	      });
	      item.subscribe('showTask', function (baseEvent) {
	        return _this7.emit('showTask', baseEvent.getTarget());
	      });
	      item.subscribe('changeTaskResponsible', function (baseEvent) {
	        var item = baseEvent.getTarget();

	        _this7.emit('changeTaskResponsible', item);
	      });
	      item.subscribe('filterByEpic', function (baseEvent) {
	        _this7.emit('filterByEpic', baseEvent.getData());
	      });
	      item.subscribe('filterByTag', function (baseEvent) {
	        _this7.emit('filterByTag', baseEvent.getData());
	      });
	      item.subscribe('toggleActionPanel', function (baseEvent) {
	        _this7.emit('toggleActionPanel', baseEvent.getTarget());
	      });
	      item.subscribe('showLinked', function (baseEvent) {
	        _this7.emit('showLinked', baseEvent.getTarget());
	      });
	    }
	  }, {
	    key: "getItemByItemId",
	    value: function getItemByItemId(itemId) {
	      return this.items.get(main_core.Type.isInteger(itemId) ? parseInt(itemId, 10) : itemId);
	    }
	  }, {
	    key: "getItemBySourceId",
	    value: function getItemBySourceId(sourceId) {
	      return babelHelpers.toConsumableArray(this.items.values()).find(function (item) {
	        return item.getSourceId() === sourceId;
	      });
	    }
	  }, {
	    key: "getItemsByParentTaskId",
	    value: function getItemsByParentTaskId(parentTaskId) {
	      var items = new Map();
	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        if (item.getParentTaskId() === parentTaskId) {
	          items.set(item.getId(), item);
	        }
	      });
	      return items;
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      this.storyPoints.setPoints(storyPoints);
	      this.setStats();
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
	      var itemNode = item.getNode();
	      var firstElementChild = listItemsNode.firstElementChild;
	      return firstElementChild.isEqualNode(itemNode);
	    }
	  }, {
	    key: "isLastItem",
	    value: function isLastItem(item) {
	      var listItemsNode = this.getListItemsNode();
	      var itemNode = item.getNode();
	      return listItemsNode.lastElementChild.isEqualNode(itemNode);
	    }
	  }, {
	    key: "getFirstItemNode",
	    value: function getFirstItemNode(input) {
	      var listItemsNode = this.getListItemsNode();
	      var fistNode = listItemsNode.firstElementChild;

	      if (input && fistNode.isEqualNode(input.getNode())) {
	        return fistNode.nextElementSibling;
	      } else {
	        return fistNode;
	      }
	    }
	  }, {
	    key: "getLoaderNode",
	    value: function getLoaderNode() {
	      return this.itemsLoaderNode ? this.itemsLoaderNode : null;
	    }
	  }, {
	    key: "fadeOut",
	    value: function fadeOut() {
	      main_core.Dom.addClass(this.getListItemsNode(), 'tasks-scrum__entity-items-faded');
	    }
	  }, {
	    key: "fadeIn",
	    value: function fadeIn() {
	      main_core.Dom.removeClass(this.getListItemsNode(), 'tasks-scrum__entity-items-faded');
	    }
	  }, {
	    key: "activateGroupMode",
	    value: function activateGroupMode() {
	      this.groupMode = true;
	      this.getItems().forEach(function (item) {
	        item.activateGroupMode();
	      });
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      this.groupMode = false;
	      this.getItems().forEach(function (item) {
	        item.deactivateGroupMode();
	      });
	      this.groupModeItems.forEach(function (item) {
	        item.removeItemFromGroupMode();
	      });
	      this.groupModeItems.clear();
	    }
	  }, {
	    key: "isGroupMode",
	    value: function isGroupMode() {
	      return this.groupMode;
	    }
	  }, {
	    key: "addItemToGroupMode",
	    value: function addItemToGroupMode(item) {
	      this.groupModeItems.set(item.getId(), item);
	      item.addItemToGroupMode();
	    }
	  }, {
	    key: "removeItemFromGroupMode",
	    value: function removeItemFromGroupMode(item) {
	      this.groupModeItems.delete(item.getId());
	      item.removeItemFromGroupMode();
	    }
	  }, {
	    key: "hasItemInGroupMode",
	    value: function hasItemInGroupMode(item) {
	      return this.groupModeItems.has(item.getId());
	    }
	  }, {
	    key: "getGroupModeItems",
	    value: function getGroupModeItems() {
	      return this.groupModeItems;
	    }
	  }, {
	    key: "bindItemsLoader",
	    value: function bindItemsLoader(loader) {
	      var _this8 = this;

	      if (!loader) {
	        if (this.itemsLoaderNode) {
	          loader = this.itemsLoaderNode;
	        } else {
	          return;
	        }
	      }

	      if (typeof IntersectionObserver === "undefined") {
	        return;
	      }

	      var observer = new IntersectionObserver(function (entries) {
	        if (entries[0].isIntersecting === true) {
	          if (!_this8.isActiveLoadItems()) {
	            _this8.emit('loadItems');
	          }
	        }
	      }, {
	        threshold: [0]
	      });
	      observer.observe(loader);
	    }
	  }, {
	    key: "setActiveLoadItems",
	    value: function setActiveLoadItems(value) {
	      this.activeLoadItems = Boolean(value);
	    }
	  }, {
	    key: "isActiveLoadItems",
	    value: function isActiveLoadItems() {
	      return this.activeLoadItems === true;
	    }
	  }, {
	    key: "showItemsLoader",
	    value: function showItemsLoader() {
	      var listPosition = main_core.Dom.getPosition(this.itemsLoaderNode);
	      var loader = new main_loader.Loader({
	        target: this.itemsLoaderNode,
	        size: 60,
	        mode: 'inline',
	        offset: {
	          top: '7px',
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });

	      if (this.getNumberItems() >= this.pageSize) {
	        loader.show();
	      }

	      return loader;
	    }
	  }, {
	    key: "setStats",
	    value: function setStats() {}
	  }, {
	    key: "showBlank",
	    value: function showBlank() {
	      if (this.blank) {
	        main_core.Dom.addClass(this.blank.getNode(), '--open');
	      }
	    }
	  }, {
	    key: "hideBlank",
	    value: function hideBlank() {
	      if (this.blank) {
	        main_core.Dom.removeClass(this.blank.getNode(), '--open');
	      }
	    }
	  }, {
	    key: "showDropzone",
	    value: function showDropzone() {
	      if (this.dropzone) {
	        main_core.Dom.addClass(this.dropzone.getNode(), '--open');
	      }
	    }
	  }, {
	    key: "hideDropzone",
	    value: function hideDropzone() {
	      if (this.dropzone) {
	        main_core.Dom.removeClass(this.dropzone.getNode(), '--open');
	      }
	    }
	  }, {
	    key: "getDropzone",
	    value: function getDropzone() {
	      return this.dropzone ? this.dropzone.getNode() : null;
	    }
	  }, {
	    key: "appendNodeAfterItem",
	    value: function appendNodeAfterItem(newItemNode, bindItemNode) {
	      if (bindItemNode.nextElementSibling) {
	        main_core.Dom.insertBefore(newItemNode, bindItemNode.nextElementSibling);
	      } else {
	        if (this.getLoaderNode()) {
	          main_core.Dom.append(newItemNode, this.getLoaderNode());
	        } else {
	          main_core.Dom.append(newItemNode, this.getListItemsNode());
	        }
	      }
	    }
	  }, {
	    key: "adjustListItemsWidth",
	    value: function adjustListItemsWidth() {
	      var hasListItemsScroll = this.getListItemsNode().scrollHeight > this.getListItemsNode().clientHeight;

	      if (hasListItemsScroll) {
	        this.getListItems().addScrollbar();
	      } else {
	        this.getListItems().removeScrollbar();
	      }
	    }
	  }]);
	  return Entity;
	}(main_core_events.EventEmitter);

	var _templateObject$f, _templateObject2$4;
	var Header = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Header, _EventEmitter);

	  function Header(backlog) {
	    var _this;

	    babelHelpers.classCallCheck(this, Header);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Header).call(this, backlog));

	    _this.setEventNamespace('BX.Tasks.Scrum.BacklogHeader');

	    _this.backlog = backlog;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Header, [{
	    key: "render",
	    value: function render() {
	      var uiEpicClasses = 'ui-btn ui-btn-sm ui-btn-light-border ui-btn-themes ui-btn-round ui-btn-no-caps';
	      var uiTaskClasses = 'ui-btn ui-btn-sm ui-btn-success ui-btn-round ui-btn-no-caps ';
	      this.node = main_core.Tag.render(_templateObject$f || (_templateObject$f = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content-header\">\n\n\t\t\t\t<div class=\"tasks-scrum__name-container\">\n\t\t\t\t\t<div class=\"tasks-scrum__title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\n\t\t\t\t<button class=\"tasks-scrum__backlog-btn ", " ui-btn-icon-add\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t\t<button class=\"tasks-scrum__backlog-btn ", " ui-btn-icon-add\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE'), this.renderTaskCounterLabel(this.backlog.getNumberTasks()), uiEpicClasses, main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_EPIC'), uiTaskClasses, main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_TASK'));
	      var buttons = this.node.querySelectorAll('button');
	      main_core.Event.bind(buttons.item(0), 'click', this.onEpicClick.bind(this, buttons.item(0)));
	      main_core.Event.bind(buttons.item(1), 'click', this.onTaskClick.bind(this)); //todo use it from project scrum button
	      //this.emit('openListEpicGrid');
	      //this.emit('openDefinitionOfDone');

	      return this.node;
	    }
	  }, {
	    key: "updateTaskCounter",
	    value: function updateTaskCounter(value) {
	      main_core.Dom.replace(this.node.querySelector('.tasks-scrum__backlog-tasks'), this.renderTaskCounterLabel(value));
	    }
	  }, {
	    key: "renderTaskCounterLabel",
	    value: function renderTaskCounterLabel(value) {
	      return main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__backlog-tasks\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_TASK_COUNTER').replace('#value#', value));
	    }
	  }, {
	    key: "onEpicClick",
	    value: function onEpicClick(button) {
	      this.emit('epicClick', button);
	    }
	  }, {
	    key: "onTaskClick",
	    value: function onTaskClick() {
	      this.emit('taskClick');
	    }
	  }]);
	  return Header;
	}(main_core_events.EventEmitter);

	var _templateObject$g, _templateObject2$5;
	var Blank = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Blank, _EventEmitter);

	  function Blank(entity) {
	    var _this;

	    babelHelpers.classCallCheck(this, Blank);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Blank).call(this));
	    _this.entity = entity;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Blank, [{
	    key: "render",
	    value: function render() {
	      if (this.entity.isBacklog()) {
	        return this.renderBacklog();
	      } else if (this.entity.isCompleted()) {
	        return this.renderCompletedSprint();
	      }
	    }
	  }, {
	    key: "renderBacklog",
	    value: function renderBacklog() {
	      this.node = main_core.Tag.render(_templateObject$g || (_templateObject$g = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__backlog-empty\">\n\t\t\t\t<div class=\"tasks-scrum__backlog-empty--icon\"></div>\n\t\t\t\t<div class=\"tasks-scrum__backlog-empty--title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__backlog-empty--subtitle\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__backlog-empty--text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__backlog-empty--text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_1'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_2'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_3'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_4'));
	      return this.node;
	    }
	  }, {
	    key: "renderCompletedSprint",
	    value: function renderCompletedSprint() {
	      this.node = main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprints--completed-empty\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_EMPTY'));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "removeNode",
	    value: function removeNode() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }]);
	  return Blank;
	}(main_core_events.EventEmitter);

	var _templateObject$h, _templateObject2$6, _templateObject3$1, _templateObject4;
	var Dropzone = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Dropzone, _EventEmitter);

	  function Dropzone(entity) {
	    var _this;

	    babelHelpers.classCallCheck(this, Dropzone);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Dropzone).call(this, entity));

	    _this.setEventNamespace('BX.Tasks.Scrum.Dropzone');

	    _this.entity = entity;
	    _this.mandatoryExists = false;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Dropzone, [{
	    key: "render",
	    value: function render() {
	      if (this.entity.isBacklog()) {
	        return this.renderBacklog();
	      } else {
	        return this.renderSprint();
	      }
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "removeNode",
	    value: function removeNode() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "renderBacklog",
	    value: function renderBacklog() {
	      var _this2 = this;

	      this.node = main_core.Tag.render(_templateObject$h || (_templateObject$h = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content-empty --empty-backlog\" data-entity-id=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.entity.getId(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_DROPZONE_1'));
	      main_core.Event.bind(this.node, 'click', function () {
	        return _this2.emit('createTask');
	      });
	      return this.node;
	    }
	  }, {
	    key: "renderSprint",
	    value: function renderSprint() {
	      var _this3 = this;

	      this.node = main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content-empty\" data-entity-id=\"", "\">\n\t\t\t\t<div class=\"tasks-scrum__content-empty--title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.entity.getId(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_1'), this.renderSprintEmptyText());

	      if (!this.mandatoryExists) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__content-empty--btn-create'), 'click', function () {
	          return _this3.emit('createTask');
	        });
	      }

	      return this.node;
	    }
	  }, {
	    key: "renderSprintEmptyText",
	    value: function renderSprintEmptyText() {
	      if (this.mandatoryExists) {
	        return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum__content-empty--text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_4'));
	      } else {
	        return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum__content-empty--text\">\n\t\t\t\t\t<span class=\"tasks-scrum__content-empty--btn-create\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_2'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_3'));
	      }
	    }
	  }, {
	    key: "setMandatory",
	    value: function setMandatory() {
	      this.mandatoryExists = true;
	    }
	  }]);
	  return Dropzone;
	}(main_core_events.EventEmitter);

	var _templateObject$i;
	var Backlog = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Backlog, _Entity);

	  function Backlog(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Backlog);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Backlog).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Backlog');

	    _this.setBacklogParams(params);

	    _this.header = null;
	    return _this;
	  }

	  babelHelpers.createClass(Backlog, [{
	    key: "setBacklogParams",
	    value: function setBacklogParams(params) {
	      var _this2 = this;

	      params.items.forEach(function (itemData) {
	        var item = Item.buildItem(itemData);
	        item.setShortView(_this2.getShortView());

	        _this2.items.set(item.getId(), item);
	      });
	    }
	  }, {
	    key: "setHeader",
	    value: function setHeader(backlog) {
	      var _this3 = this;

	      this.header = new Header(backlog);
	      this.header.subscribe('epicClick', function (baseEvent) {
	        return _this3.emit('openAddEpicForm', baseEvent.getData());
	      });
	      this.header.subscribe('taskClick', function () {
	        if (_this3.mandatoryExists) {
	          _this3.emit('openAddTaskForm');
	        } else {
	          _this3.emit('showInput');
	        }
	      });
	    }
	  }, {
	    key: "setBlank",
	    value: function setBlank(backlog) {
	      this.blank = new Blank(backlog);
	    }
	  }, {
	    key: "setDropzone",
	    value: function setDropzone(backlog) {
	      var _this4 = this;

	      this.dropzone = new Dropzone(backlog);
	      this.dropzone.subscribe('createTask', function () {
	        if (_this4.mandatoryExists) {
	          _this4.emit('openAddTaskForm');
	        } else {
	          _this4.emit('showInput');
	        }
	      });
	    }
	  }, {
	    key: "setNumberTasks",
	    value: function setNumberTasks(numberTasks) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "setNumberTasks", this).call(this, numberTasks);

	      if (this.header) {
	        this.header.updateTaskCounter(this.getNumberTasks());
	      }
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
	      this.node = main_core.Tag.render(_templateObject$i || (_templateObject$i = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__backlog\">\n\t\t\t\t<div class=\"tasks-scrum__content --with-header --open\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.header ? this.header.render() : '', this.blank ? this.blank.render() : '', this.dropzone ? this.dropzone.render() : '', this.listItems ? this.listItems.render() : '');
	      main_core.Event.bind(this.node.querySelector('.tasks-scrum__content-items'), 'scroll', this.onItemsScroll.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "setItem", this).call(this, newItem);

	      if (newItem.getNode()) {
	        main_core.Dom.addClass(newItem.getNode(), '--item-backlog');
	      }
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "removeItem", this).call(this, item);

	      if (this.isEmpty()) {
	        this.emit('showBlank');
	      }
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "onAfterAppend", this).call(this);

	      if (this.isEmpty()) {
	        this.emit('showBlank');
	      }
	    }
	  }, {
	    key: "onItemsScroll",
	    value: function onItemsScroll() {
	      this.emit('itemsScroll');
	    }
	  }], [{
	    key: "buildBacklog",
	    value: function buildBacklog(backlogData) {
	      var backlog = new Backlog(backlogData);
	      backlog.setHeader(backlog);
	      backlog.setBlank(backlog);
	      backlog.setDropzone(backlog);
	      backlog.setListItems(backlog);
	      return backlog;
	    }
	  }]);
	  return Backlog;
	}(Entity);

	var _templateObject$j;
	var Date$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Date, _EventEmitter);

	  function Date(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Date);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Date).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Date');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Date, [{
	    key: "render",
	    value: function render() {
	      if (this.sprint.isActive() || this.sprint.isCompleted()) {
	        return '';
	      }

	      this.node = main_core.Tag.render(_templateObject$j || (_templateObject$j = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--date-container\">\n\t\t\t\t<div class=\"tasks-scrum__sprint--date --start\">", "</div>\n\t\t\t\t<div class=\"tasks-scrum__sprint--date-separator\"> - </div>\n\t\t\t\t<div class=\"tasks-scrum__sprint--date --end\">", "</div>\n\t\t\t\t<input type=\"hidden\" name=\"dateStart\" value=\"", ")\">\n\t\t\t\t<input type=\"hidden\" name=\"dateEnd\" value=\"", "\">\n\t\t\t</div>\n\t\t"])), Date.getFormattedDateStart(this.sprint), Date.getFormattedDateEnd(this.sprint), main_core.Text.encode(this.sprint.getDateStartFormatted()), main_core.Text.encode(this.sprint.getDateEndFormatted()));
	      this.bindEvents();
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      if (this.sprint.isActive() || this.sprint.isCompleted()) {
	        return;
	      }

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

	      var dateStartNode = this.node.querySelector('.--start');
	      var dateEndNode = this.node.querySelector('.--end');
	      var dateStartInput = this.node.querySelector('input[name="dateStart"]');
	      var dateEndInput = this.node.querySelector('input[name="dateEnd"]');
	      main_core.Event.bind(this.node, 'click', function (event) {
	        var target = event.target;

	        if (target.classList.contains('--start')) {
	          showCalendar(target, dateStartInput);
	        } else if (target.classList.contains('--end')) {
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
	      var weekCount = parseInt(this.sprint.getDefaultSprintDuration(), 10) / 604800;

	      if (weekCount > 5) {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_3');
	      } else if (weekCount === 1) {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_1');
	      } else {
	        return weekCount + ' ' + main_core.Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_2');
	      }
	    }
	  }], [{
	    key: "getFormattedTitleDatePeriod",
	    value: function getFormattedTitleDatePeriod(sprint) {
	      return Date.getFormattedDateStart(sprint) + ' - ' + Date.getFormattedDateEnd(sprint);
	    }
	  }, {
	    key: "getFormattedDateStart",
	    value: function getFormattedDateStart(sprint) {
	      /* eslint-disable */
	      return BX.date.format('j F', sprint.getDateStart(), null, true);
	      /* eslint-enable */
	    }
	  }, {
	    key: "getFormattedDateEnd",
	    value: function getFormattedDateEnd(sprint) {
	      /* eslint-disable */
	      return BX.date.format('j F', sprint.getDateEnd(), null, true);
	      /* eslint-enable */
	    }
	  }]);
	  return Date;
	}(main_core_events.EventEmitter);

	var StatsCalculator = /*#__PURE__*/function () {
	  function StatsCalculator() {
	    babelHelpers.classCallCheck(this, StatsCalculator);
	  }

	  babelHelpers.createClass(StatsCalculator, [{
	    key: "calculatePercentage",
	    value: function calculatePercentage(first, second) {
	      if (first === 0) {
	        return 0;
	      }

	      var result = Math.round(second * 100 / first);
	      return isNaN(result) ? 0 : result;
	    }
	  }]);
	  return StatsCalculator;
	}();

	var _templateObject$k;
	var Stats = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Stats, _EventEmitter);

	  function Stats(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Stats);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Stats).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Stats');

	    _this.setSprintData(sprint);

	    _this.statsCalculator = new StatsCalculator();
	    _this.node = null;
	    _this.kanbanMode = false;
	    return _this;
	  }

	  babelHelpers.createClass(Stats, [{
	    key: "setKanbanStyle",
	    value: function setKanbanStyle() {
	      this.kanbanMode = true;
	    }
	  }, {
	    key: "isKanbanMode",
	    value: function isKanbanMode() {
	      return this.kanbanMode;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$k || (_templateObject$k = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	      return this.node;
	    }
	  }, {
	    key: "setSprintData",
	    value: function setSprintData(sprint) {
	      this.sprint = sprint;
	      this.setStoryPoints(sprint.getStoryPoints().getPoints());
	      this.setCompletedStoryPoints(sprint.getCompletedStoryPoints().getPoints());
	      this.setUncompletedStoryPoints(sprint.getUncompletedStoryPoints().getPoints());
	      this.setEndDate(sprint.getDateEnd());
	      this.weekendDaysTime = sprint.getWeekendDaysTime();

	      if (this.node) {
	        main_core.Dom.replace(this.node, this.render());
	      }
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
	  return Stats;
	}(main_core_events.EventEmitter);

	var _templateObject$l;
	var CompletedStats = /*#__PURE__*/function (_Stats) {
	  babelHelpers.inherits(CompletedStats, _Stats);

	  function CompletedStats() {
	    babelHelpers.classCallCheck(this, CompletedStats);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompletedStats).apply(this, arguments));
	  }

	  babelHelpers.createClass(CompletedStats, [{
	    key: "render",
	    value: function render() {
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var completedDate = this.getCompletedDate(this.getEndDate());
	      var label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL').replace('#percent#', percentage).replace('#date#', completedDate);
	      var title = Date$1.getFormattedTitleDatePeriod(this.sprint);
	      this.node = main_core.Tag.render(_templateObject$l || (_templateObject$l = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div title=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), title, label);
	      return this.node;
	    }
	  }, {
	    key: "getCompletedDate",
	    value: function getCompletedDate(endDate) {
	      return BX.date.format('j F Y', endDate);
	    }
	  }]);
	  return CompletedStats;
	}(Stats);

	var _templateObject$m;
	var ExpiredStats = /*#__PURE__*/function (_Stats) {
	  babelHelpers.inherits(ExpiredStats, _Stats);

	  function ExpiredStats() {
	    babelHelpers.classCallCheck(this, ExpiredStats);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExpiredStats).apply(this, arguments));
	  }

	  babelHelpers.createClass(ExpiredStats, [{
	    key: "render",
	    value: function render() {
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var expiredDay = this.getExpiredDay(this.getEndDate());
	      var label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_EXPIRED_LABEL').replace('#percent#', percentage).replace('#date#', expiredDay);
	      var title = Date$1.getFormattedTitleDatePeriod(this.sprint);
	      this.node = main_core.Tag.render(_templateObject$m || (_templateObject$m = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div title=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), title, label);
	      return this.node;
	    }
	  }, {
	    key: "getExpiredDay",
	    value: function getExpiredDay(endDate) {
	      return BX.date.format('j F Y', endDate);
	    }
	  }]);
	  return ExpiredStats;
	}(Stats);

	var _templateObject$n;
	var ActiveStats = /*#__PURE__*/function (_Stats) {
	  babelHelpers.inherits(ActiveStats, _Stats);

	  function ActiveStats() {
	    babelHelpers.classCallCheck(this, ActiveStats);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActiveStats).apply(this, arguments));
	  }

	  babelHelpers.createClass(ActiveStats, [{
	    key: "render",
	    value: function render() {
	      var remainingDays = this.getRemainingDays(this.getEndDate());
	      var percentage = this.statsCalculator.calculatePercentage(this.getStoryPoints(), this.getCompletedStoryPoints());
	      var label = '';

	      if (main_core.Type.isInteger(remainingDays) && remainingDays <= 1) {
	        label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LAST_LABEL').replace('#percent#', percentage);
	      } else {
	        label = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL').replace('#days#', remainingDays).replace('#percent#', percentage);
	      }

	      var title = Date$1.getFormattedTitleDatePeriod(this.sprint);
	      this.node = main_core.Tag.render(_templateObject$n || (_templateObject$n = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div title=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), title, label);
	      return this.node;
	    }
	  }, {
	    key: "getRemainingDays",
	    value: function getRemainingDays(endDate) {
	      var dateWithWeekendOffset = new Date();
	      dateWithWeekendOffset.setSeconds(dateWithWeekendOffset.getSeconds() + this.weekendDaysTime);
	      dateWithWeekendOffset.setHours(0, 0, 0, 0);
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
	  return ActiveStats;
	}(Stats);

	var StatsBuilder = /*#__PURE__*/function () {
	  function StatsBuilder() {
	    babelHelpers.classCallCheck(this, StatsBuilder);
	  }

	  babelHelpers.createClass(StatsBuilder, null, [{
	    key: "build",
	    value: function build(sprint) {
	      if (sprint.isCompleted()) {
	        return new CompletedStats(sprint);
	      } else if (sprint.isExpired()) {
	        return new ExpiredStats(sprint);
	      } else if (sprint.isActive()) {
	        return new ActiveStats(sprint);
	      } else {
	        return new Stats(sprint);
	      }
	    }
	  }]);
	  return StatsBuilder;
	}();

	var _templateObject$o;
	var Stats$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Stats, _EventEmitter);

	  function Stats(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Stats);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Stats).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Stats');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Stats, [{
	    key: "render",
	    value: function render() {
	      var stats = StatsBuilder.build(this.sprint);
	      this.node = main_core.Tag.render(_templateObject$o || (_templateObject$o = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content--event-params\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), stats.render());
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }]);
	  return Stats;
	}(main_core_events.EventEmitter);

	var _templateObject$p, _templateObject2$7, _templateObject3$2, _templateObject4$1;
	var Name$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Name, _EventEmitter);

	  function Name(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Name);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Name).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Name');

	    _this.sprint = sprint;
	    _this.date = null;
	    _this.stats = null;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Name, [{
	    key: "setDate",
	    value: function setDate(sprint) {
	      var _this2 = this;

	      var date = new Date$1(sprint);

	      if (this.date) {
	        main_core.Dom.replace(this.date.getNode(), date.render());
	      }

	      this.date = date;
	      this.date.subscribe('changeSprintDeadline', function (baseEvent) {
	        _this2.emit('changeSprintDeadline', baseEvent.getData());
	      });
	    }
	  }, {
	    key: "getDate",
	    value: function getDate() {
	      return this.date;
	    }
	  }, {
	    key: "setStats",
	    value: function setStats(sprint) {
	      var stats = new Stats$1(sprint);

	      if (this.stats) {
	        main_core.Dom.replace(this.stats.getNode(), stats.render());
	      }

	      this.stats = stats;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this3 = this;

	      this.node = main_core.Tag.render(_templateObject$p || (_templateObject$p = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__name-container\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum__title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t"])), this.renderEditInput(), main_core.Text.encode(this.sprint.getName()), this.renderEdit(), this.renderRemove(), this.date ? this.date.render() : '', this.stats ? this.stats.render() : '');
	      var titleNode = this.node.querySelector('.tasks-scrum__title');
	      var editNode = this.node.querySelector('.tasks-scrum__sprint--edit');
	      main_core.Event.bind(titleNode, 'click', function () {
	        _this3.emit('editClick', _this3.node.querySelector('.tasks-scrum__title-editing-input'));
	      });
	      main_core.Event.bind(editNode, 'click', function () {
	        _this3.emit('editClick', _this3.node.querySelector('.tasks-scrum__title-editing-input'));
	      });

	      if (this.sprint.isPlanned()) {
	        var removeNode = this.node.querySelector('.tasks-scrum__sprint--remove');
	        main_core.Event.bind(removeNode, 'click', function () {
	          ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_SPRINT'), function (messageBox) {
	            _this3.emit('removeSprint');

	            messageBox.close();
	          }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));
	        });
	      }

	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "renderEditInput",
	    value: function renderEditInput() {
	      var uiClasses = 'ui-ctl ui-ctl-sm ui-ctl-textbox ui-ctl-underline ui-ctl-no-padding';
	      return main_core.Tag.render(_templateObject2$7 || (_templateObject2$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__title-editing ", "\">\n\t\t\t\t<input\n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tclass=\"tasks-scrum__title-editing-input ui-ctl-element\"\n\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t>\n\t\t\t</div>\n\t\t"])), uiClasses, main_core.Text.encode(this.sprint.getName()));
	    }
	  }, {
	    key: "renderEdit",
	    value: function renderEdit() {
	      if (!this.sprint.isCompleted()) {
	        return main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum__sprint--edit\"></div>"])));
	      } else {
	        return '';
	      }
	    }
	  }, {
	    key: "renderRemove",
	    value: function renderRemove() {
	      if (this.sprint.isPlanned()) {
	        return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum__sprint--remove\"></div>"])));
	      } else {
	        return '';
	      }
	    }
	  }]);
	  return Name;
	}(main_core_events.EventEmitter);

	var _templateObject$q;
	var ChartIcon = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ChartIcon, _EventEmitter);

	  function ChartIcon(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, ChartIcon);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ChartIcon).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.ChartIcon');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(ChartIcon, [{
	    key: "render",
	    value: function render() {
	      var uiChartStyles = 'ui-btn ui-btn-xs ui-btn-light ui-btn-round';
	      this.node = main_core.Tag.render(_templateObject$q || (_templateObject$q = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--btn-burn-down-chart\">\n\t\t\t\t<div class=\"tasks-scrum__sprint--icon-burn-down-chart ", "\"></div>\n\t\t\t</div>\n\t\t"])), uiChartStyles);
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return ChartIcon;
	}(main_core_events.EventEmitter);

	var _templateObject$r;
	var Counters = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Counters, _EventEmitter);

	  function Counters(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Counters);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Counters).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.Counters');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Counters, [{
	    key: "render",
	    value: function render() {
	      //todo maybe need for active sprint
	      //${this.sprint.getUncompletedStoryPoints().getPoints()}
	      //${this.sprint.getCompletedStoryPoints().getPoints()}
	      this.node = main_core.Tag.render(_templateObject$r || (_templateObject$r = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--event-content\">\n\t\t\t\t<div class=\"tasks-scrum__sprint--event-container\">\n\t\t\t\t\t<div class=\"tasks-scrum__sprint--subtitle\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__sprint--point\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__sprint--event-container\">\n\t\t\t\t\t<div class=\"tasks-scrum__sprint--subtitle\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__sprint--point\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_TASK_LABEL'), parseInt(this.sprint.getNumberTasks(), 10), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS'), this.sprint.getStoryPoints().isEmpty() ? '-' : this.sprint.getStoryPoints().getPoints());
	      return this.node;
	    }
	  }]);
	  return Counters;
	}(main_core_events.EventEmitter);

	var _templateObject$s;
	var Button = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Button, _EventEmitter);

	  function Button(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Button);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Button).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.Button');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Button, [{
	    key: "render",
	    value: function render() {
	      var uiBtnStyles = 'ui-btn ui-btn-xs ui-btn-light ui-btn-round ui-btn-icon-add';
	      this.node = main_core.Tag.render(_templateObject$s || (_templateObject$s = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"tasks-scrum__sprint--btn-add ", "\"></button>\n\t\t"])), uiBtnStyles);
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Button;
	}(main_core_events.EventEmitter);

	var _templateObject$t;
	var Info = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Info, _EventEmitter);

	  function Info(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Info);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Info).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info');

	    _this.sprint = sprint;
	    _this.node = null;
	    _this.chartIcon = new ChartIcon(sprint);
	    _this.counters = new Counters(sprint);
	    _this.button = new Button(sprint);
	    return _this;
	  }

	  babelHelpers.createClass(Info, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      this.node = main_core.Tag.render(_templateObject$t || (_templateObject$t = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--info\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.chartIcon.render(), this.counters.render(), this.button.render());
	      this.chartIcon.subscribe('click', function () {
	        return _this2.emit('showBurnDownChart');
	      });
	      this.button.subscribe('click', function (baseEvent) {
	        return _this2.emit('showCreateMenu', baseEvent.getTarget());
	      });
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return Info;
	}(main_core_events.EventEmitter);

	var _templateObject$u;
	var Button$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Button, _EventEmitter);

	  function Button(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Button);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Button).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Button');

	    _this.sprint = sprint;
	    _this.disabled = false;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Button, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$u || (_templateObject$u = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--btn-run ", "\" title=\"", "\">\n\t\t\t\t<span class=\"tasks-scrum__sprint--btn-run-text\">", "</span>\n\t\t\t</div>\n\t\t"])), this.getUiClasses(), this.getButtonText(), this.getButtonText());
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.disabled) {
	        return;
	      }

	      this.emit('click');
	    }
	  }, {
	    key: "getUiClasses",
	    value: function getUiClasses() {
	      return 'ui-btn ui-btn-sm ui-btn-primary ui-btn-round ui-btn-no-caps';
	    }
	  }, {
	    key: "getButtonText",
	    value: function getButtonText() {
	      return main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_BUTTON_' + this.sprint.getStatus().toUpperCase());
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.disabled = true;

	      if (this.node) {
	        main_core.Dom.addClass(this.node, 'ui-btn-disabled');
	      }
	    }
	  }, {
	    key: "unDisable",
	    value: function unDisable() {
	      this.disabled = false;

	      if (this.node) {
	        main_core.Dom.removeClass(this.node, 'ui-btn-disabled');
	      }
	    }
	  }]);
	  return Button;
	}(main_core_events.EventEmitter);

	var _templateObject$v;
	var Tick = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Tick, _EventEmitter);

	  function Tick(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Tick);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tick).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Tick');

	    _this.sprint = sprint;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(Tick, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$v || (_templateObject$v = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprint--btn-dropdown ui-btn ui-btn-sm ui-btn-icon-angle-down --up\"></div>\n\t\t"])));
	      main_core.Event.bind(this.node, 'click', this.onClick.bind(this));

	      if (this.sprint.isCompleted()) {
	        main_core.Dom.removeClass(this.node, '--up');
	      }

	      return this.node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }, {
	    key: "upTick",
	    value: function upTick() {
	      if (!this.node) {
	        return;
	      }

	      main_core.Dom.addClass(this.node, '--up');
	    }
	  }, {
	    key: "downTick",
	    value: function downTick() {
	      if (!this.node) {
	        return;
	      }

	      main_core.Dom.removeClass(this.node, '--up');
	    }
	  }]);
	  return Tick;
	}(main_core_events.EventEmitter);

	var _templateObject$w;
	var Header$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Header, _EventEmitter);

	  function Header(sprint) {
	    var _this;

	    babelHelpers.classCallCheck(this, Header);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Header).call(this, sprint));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header');

	    _this.sprint = sprint;
	    _this.node = null;
	    _this.name = null;
	    _this.stats = null;
	    _this.info = null;
	    _this.button = null;
	    _this.tick = null;
	    return _this;
	  }

	  babelHelpers.createClass(Header, [{
	    key: "setName",
	    value: function setName(sprint) {
	      var _this2 = this;

	      var name = new Name$1(sprint);

	      if (sprint.isPlanned()) {
	        name.setDate(sprint);
	      }

	      if (sprint.isActive()) {
	        name.setStats(sprint);
	      }

	      if (this.name) {
	        main_core.Dom.replace(this.name.getNode(), name.render());
	      }

	      this.name = name;
	      this.name.subscribe('editClick', function (baseEvent) {
	        _this2.emit('changeName', baseEvent.getData());
	      });
	      this.name.subscribe('removeSprint', function () {
	        return _this2.emit('removeSprint');
	      });
	      this.name.subscribe('changeSprintDeadline', function (baseEvent) {
	        _this2.emit('changeSprintDeadline', baseEvent.getData());
	      });
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "setStats",
	    value: function setStats(sprint) {
	      if (!sprint.isCompleted()) {
	        return;
	      }

	      var stats = new Stats$1(sprint);

	      if (this.stats) {
	        main_core.Dom.replace(this.stats.getNode(), stats.render());
	      }

	      this.stats = stats;
	    }
	  }, {
	    key: "setInfo",
	    value: function setInfo(sprint) {
	      var _this3 = this;

	      var info = new Info(sprint);

	      if (this.info) {
	        main_core.Dom.replace(this.info.getNode(), info.render());
	      }

	      this.info = info;
	      this.info.subscribe('showBurnDownChart', function () {
	        return _this3.emit('showBurnDownChart');
	      });
	      this.info.subscribe('showCreateMenu', function (baseEvent) {
	        return _this3.emit('showCreateMenu', baseEvent.getData());
	      });
	    }
	  }, {
	    key: "setButton",
	    value: function setButton(sprint) {
	      var _this4 = this;

	      var button = new Button$1(sprint);

	      if (this.button) {
	        main_core.Dom.replace(this.button.getNode(), button.render());
	      }

	      this.button = button;
	      this.button.subscribe('click', function () {
	        if (_this4.sprint.isActive()) {
	          _this4.emit('completeSprint');
	        } else if (_this4.sprint.isPlanned()) {
	          _this4.emit('startSprint');
	        }
	      });
	    }
	  }, {
	    key: "setTick",
	    value: function setTick(sprint) {
	      var _this5 = this;

	      this.tick = new Tick(sprint);
	      this.tick.subscribe('click', function () {
	        _this5.emit('toggleVisibilityContent');
	      });
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$w || (_templateObject$w = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content-header ", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.getHeaderClass(), this.name ? this.name.render() : '', this.stats ? this.stats.render() : '', this.info ? this.info.render() : '', this.button ? this.button.render() : '', this.tick ? this.tick.render() : '');
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getHeaderClass",
	    value: function getHeaderClass() {
	      return 'tasks-scrum__content-header --' + this.sprint.getStatus();
	    }
	  }, {
	    key: "activateEditMode",
	    value: function activateEditMode() {
	      main_core.Dom.addClass(this.getNode(), '--editing');
	    }
	  }, {
	    key: "deactivateEditMode",
	    value: function deactivateEditMode() {
	      main_core.Dom.removeClass(this.getNode(), '--editing');
	    }
	  }, {
	    key: "upTick",
	    value: function upTick() {
	      if (!this.tick) {
	        return;
	      }

	      this.tick.upTick();
	    }
	  }, {
	    key: "downTick",
	    value: function downTick() {
	      if (!this.tick) {
	        return;
	      }

	      this.tick.downTick();
	    }
	  }, {
	    key: "disableButton",
	    value: function disableButton() {
	      if (this.button) {
	        this.button.disable();
	      }
	    }
	  }, {
	    key: "unDisableButton",
	    value: function unDisableButton() {
	      if (this.button) {
	        this.button.unDisable();
	      }
	    }
	  }], [{
	    key: "buildHeader",
	    value: function buildHeader(sprint) {
	      var header = new Header(sprint);
	      header.setName(sprint);

	      if (sprint.isCompleted()) {
	        header.setStats(sprint);
	      } else {
	        header.setInfo(sprint);
	        header.setButton(sprint);
	      }

	      header.setTick(sprint);
	      return header;
	    }
	  }]);
	  return Header;
	}(main_core_events.EventEmitter);

	var _templateObject$x;
	var Sprint = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Sprint, _Entity);

	  function Sprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Sprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint');

	    _this.completedStoryPoints = new StoryPointsStorage();
	    _this.uncompletedStoryPoints = new StoryPointsStorage();

	    _this.setSprintParams(params);

	    _this.hideCont = _this.isCompleted();
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
	      this.setDateStartFormatted(params.dateStartFormatted);
	      this.setDateEndFormatted(params.dateEndFormatted);
	      this.setWeekendDaysTime(params.weekendDaysTime);
	      this.setDefaultSprintDuration(params.defaultSprintDuration);
	      this.setStatus(params.status);
	      this.setCompletedStoryPoints(params.completedStoryPoints);
	      this.setUncompletedStoryPoints(params.uncompletedStoryPoints);
	      this.setCompletedTasks(params.completedTasks);
	      this.setUncompletedTasks(params.uncompletedTasks);
	      this.setItems(params.items);
	      this.setInfo(params.info);
	    }
	  }, {
	    key: "setHeader",
	    value: function setHeader(sprint) {
	      var _this2 = this;

	      var header = Header$1.buildHeader(sprint);

	      if (this.header) {
	        main_core.Dom.replace(this.header.getNode(), header.render());
	      }

	      this.header = header;
	      this.header.subscribe('changeName', this.onChangeName.bind(this));
	      this.header.subscribe('removeSprint', function () {
	        return _this2.emit('removeSprint');
	      });
	      this.header.subscribe('completeSprint', function () {
	        return _this2.emit('completeSprint');
	      });
	      this.header.subscribe('startSprint', function () {
	        return _this2.emit('startSprint');
	      });
	      this.header.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
	      this.header.subscribe('toggleVisibilityContent', function () {
	        _this2.toggleVisibilityContent(_this2.getContentContainer());
	      });
	      this.header.subscribe('showBurnDownChart', function () {
	        return _this2.emit('showSprintBurnDownChart');
	      });
	      this.header.subscribe('showCreateMenu', function (baseEvent) {
	        if (_this2.mandatoryExists) {
	          _this2.emit('createSprint');
	        } else {
	          _this2.emit('showSprintCreateMenu', baseEvent.getData());
	        }
	      });
	    }
	  }, {
	    key: "setBlank",
	    value: function setBlank(sprint) {
	      this.blank = new Blank(sprint);
	    }
	  }, {
	    key: "setDropzone",
	    value: function setDropzone(sprint) {
	      var _this3 = this;

	      this.dropzone = new Dropzone(sprint);

	      if (this.mandatoryExists) {
	        this.dropzone.setMandatory();
	      }

	      this.dropzone.subscribe('createTask', function () {
	        return _this3.emit('showInput');
	      });
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
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'sprint';
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "setItem", this).call(this, newItem);
	      newItem.setDisableStatus(this.isDisabled());

	      if (newItem.getNode()) {
	        main_core.Dom.addClass(newItem.getNode(), '--item-sprint');
	      }
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "removeItem", this).call(this, item);

	      if (this.isEmpty()) {
	        this.showDropzone();
	      }
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.name = main_core.Type.isString(name) ? name : '';

	      if (this.isNodeCreated()) {
	        this.header.setName(this);
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

	      if (this.getNode()) {
	        this.getNode().dataset.sprintSort = this.sort;
	      }
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

	      if (this.isNodeCreated()) {
	        this.header.setName(this);
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

	      if (this.isNodeCreated()) {
	        this.header.setName(this);
	      }
	    }
	  }, {
	    key: "getDateEnd",
	    value: function getDateEnd() {
	      return parseInt(this.dateEnd, 10);
	    }
	  }, {
	    key: "setDateStartFormatted",
	    value: function setDateStartFormatted(dateStart) {
	      this.dateStartFormatted = main_core.Type.isString(dateStart) ? dateStart : '';
	    }
	  }, {
	    key: "getDateStartFormatted",
	    value: function getDateStartFormatted() {
	      return this.dateStartFormatted;
	    }
	  }, {
	    key: "setDateEndFormatted",
	    value: function setDateEndFormatted(dateEnd) {
	      this.dateEndFormatted = main_core.Type.isString(dateEnd) ? dateEnd : '';
	    }
	  }, {
	    key: "getDateEndFormatted",
	    value: function getDateEndFormatted() {
	      return this.dateEndFormatted;
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
	    key: "setCompletedStoryPoints",
	    value: function setCompletedStoryPoints(completedStoryPoints) {
	      this.completedStoryPoints.setPoints(completedStoryPoints);
	      this.setStats();
	    }
	  }, {
	    key: "getCompletedStoryPoints",
	    value: function getCompletedStoryPoints() {
	      return this.completedStoryPoints;
	    }
	  }, {
	    key: "setUncompletedStoryPoints",
	    value: function setUncompletedStoryPoints(uncompletedStoryPoints) {
	      this.uncompletedStoryPoints.setPoints(uncompletedStoryPoints);
	      this.setStats();
	    }
	  }, {
	    key: "getUncompletedStoryPoints",
	    value: function getUncompletedStoryPoints() {
	      return this.uncompletedStoryPoints;
	    }
	  }, {
	    key: "setStats",
	    value: function setStats() {
	      if (this.header) {
	        this.header.setStats(this);
	        this.header.setName(this);
	        this.header.setInfo(this);
	      }
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      var _this4 = this;

	      if (!main_core.Type.isArray(items)) {
	        return;
	      }

	      items.forEach(function (itemParams) {
	        var item = Item.buildItem(itemParams);
	        item.setDisableStatus(_this4.isDisabled());
	        item.setShortView(_this4.getShortView());

	        _this4.items.set(item.getId(), item);
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
	    key: "setNumberTasks",
	    value: function setNumberTasks(numberTasks) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "setNumberTasks", this).call(this, numberTasks);

	      if (this.header) {
	        this.header.setInfo(this);
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
	    key: "getUncompletedItems",
	    value: function getUncompletedItems() {
	      var items = new Map();
	      this.items.forEach(function (item) {
	        if (!item.isCompleted()) {
	          items.set(item.getId(), item);
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
	      this.setHeader(this);
	      this.items.forEach(function (item) {
	        item.setDisableStatus(_this5.isDisabled());
	      });

	      if (this.isDisabled()) {
	        if (this.input) {
	          this.input.removeYourself();
	        }
	      }
	    }
	  }, {
	    key: "getStatus",
	    value: function getStatus() {
	      return this.status;
	    }
	  }, {
	    key: "disableHeaderButton",
	    value: function disableHeaderButton() {
	      if (this.header) {
	        this.header.disableButton();
	      }
	    }
	  }, {
	    key: "unDisableHeaderButton",
	    value: function unDisableHeaderButton() {
	      if (this.header) {
	        this.header.unDisableButton();
	      }
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

	      this.setStoryPoints(tmpSprint.getStoryPoints().getPoints());
	      this.setCompletedStoryPoints(tmpSprint.getCompletedStoryPoints().getPoints());
	      this.setUncompletedStoryPoints(tmpSprint.getUncompletedStoryPoints().getPoints());

	      if (tmpSprint.getStatus() !== this.getStatus()) {
	        this.setStatus(tmpSprint.getStatus());
	      }

	      if (this.node && this.header) {
	        this.setHeader(this);
	      }
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Event.bind(this.node, 'transitionend', this.removeNode.bind(this));
	      this.node.style.height = "".concat(this.node.scrollHeight, "px");
	      this.node.clientHeight;
	      this.node.style.height = '0';
	    }
	  }, {
	    key: "removeNode",
	    value: function removeNode() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var openClass = this.isCompleted() ? '' : '--open';
	      this.node = main_core.Tag.render(_templateObject$x || (_templateObject$x = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__content --with-header ", "\"\n\t\t\t\tdata-sprint-sort=\"", "\"\n\t\t\t\tdata-sprint-id=\"", "\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum__content-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), openClass, this.sort, this.getId(), this.header ? this.header.render() : '', this.blank ? this.blank.render() : '', this.dropzone ? this.dropzone.render() : '', this.listItems ? this.listItems.render() : '');
	      main_core.Event.bind(this.getContentContainer(), 'transitionend', this.onTransitionEnd.bind(this, this.getContentContainer()));
	      return this.node;
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "onAfterAppend", this).call(this);

	      if (this.isEmpty() && !this.isCompleted()) {
	        this.showDropzone();
	      }
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this6 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "subscribeToItem", this).call(this, item);
	      item.subscribe('showSubTasks', function (baseEvent) {
	        var parentItem = baseEvent.getTarget();
	        var subTasks = baseEvent.getData();

	        if (subTasks.isEmpty()) {
	          _this6.emit('getSubTasks', subTasks);
	        }

	        _this6.appendNodeAfterItem(subTasks.render(), parentItem.getNode());

	        subTasks.show();
	      });
	      item.subscribe('updateCompletedStatus', function (baseEvent) {
	        babelHelpers.toConsumableArray(_this6.getItems().values()).map(function (item) {
	          if (item.isCompleted()) {
	            _this6.setCompletedTasks(_this6.getCompletedTasks() + 1);
	          } else {
	            _this6.setUncompletedTasks(_this6.getUncompletedTasks() - 1);
	          }
	        });
	      });
	      item.subscribe('toggleSubTasks', function (baseEvent) {
	        _this6.emit('toggleSubTasks', baseEvent.getTarget());
	      });
	    }
	  }, {
	    key: "onChangeName",
	    value: function onChangeName(baseEvent) {
	      var _this7 = this;

	      var header = baseEvent.getTarget();
	      var input = baseEvent.getData();
	      header.activateEditMode();
	      var length = input.value.length;
	      input.focus();
	      input.setSelectionRange(length, length);
	      main_core.Event.bind(input, 'keydown', function (event) {
	        if (event.isComposing || event.key === 'Escape' || event.key === 'Enter') {
	          input.blur();
	          event.stopImmediatePropagation();
	        }
	      });
	      main_core.Event.bindOnce(input, 'blur', function () {
	        if (_this7.getName() !== input.value) {
	          _this7.setName(input.value);

	          _this7.emit('changeSprintName', {
	            sprintId: _this7.getId(),
	            name: _this7.getName()
	          });
	        }

	        header.deactivateEditMode();
	      });
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
	    key: "onTransitionEnd",
	    value: function onTransitionEnd(node) {
	      if (node.style.height !== '0px') {
	        node.style.height = 'auto';
	      }

	      this.emit('toggleVisibilityContent');
	    }
	  }, {
	    key: "toggleVisibilityContent",
	    value: function toggleVisibilityContent(node) {
	      if (this.isHideContent()) {
	        this.showContent(node);
	        main_core.Dom.addClass(this.node, '--open');

	        if (this.isCompleted()) {
	          if (this.getItems().size === 0) {
	            this.emit('getSprintCompletedItems');
	          }
	        }
	      } else {
	        this.hideContent(node);
	        main_core.Dom.removeClass(this.node, '--open');
	      }
	    }
	  }, {
	    key: "showContent",
	    value: function showContent(node) {
	      this.hideCont = false;
	      node.style.height = "".concat(node.scrollHeight, "px");

	      if (this.header) {
	        this.header.upTick();
	      }
	    }
	  }, {
	    key: "hideContent",
	    value: function hideContent(node) {
	      this.hideCont = true;
	      node.style.height = "".concat(node.scrollHeight, "px");
	      node.clientHeight;
	      node.style.height = '0';

	      if (this.header) {
	        this.header.downTick();
	      }
	    }
	  }, {
	    key: "isHideContent",
	    value: function isHideContent() {
	      return this.hideCont;
	    }
	  }, {
	    key: "showSprint",
	    value: function showSprint() {
	      if (this.node) {
	        this.node.style.display = 'block';
	      }
	    }
	  }, {
	    key: "hideSprint",
	    value: function hideSprint() {
	      if (this.node) {
	        this.node.style.display = 'none';
	      }
	    }
	  }, {
	    key: "getContentContainer",
	    value: function getContentContainer() {
	      return this.node.querySelector('.tasks-scrum__content-container');
	    }
	  }, {
	    key: "fadeOut",
	    value: function fadeOut() {
	      if (!this.isCompleted()) {
	        babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "fadeOut", this).call(this);
	      }
	    }
	  }, {
	    key: "fadeIn",
	    value: function fadeIn() {
	      if (!this.isCompleted()) {
	        babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "fadeIn", this).call(this);
	      }
	    }
	  }], [{
	    key: "buildSprint",
	    value: function buildSprint(params) {
	      var sprint = new Sprint(params);
	      sprint.setHeader(sprint);

	      if (sprint.isCompleted()) {
	        sprint.setBlank(sprint);
	      }

	      sprint.setDropzone(sprint);
	      sprint.setListItems(sprint);
	      return sprint;
	    }
	  }]);
	  return Sprint;
	}(Entity);

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
	    key: "getActiveSprint",
	    value: function getActiveSprint() {
	      return babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.isActive();
	      });
	    }
	  }, {
	    key: "getPlannedSprints",
	    value: function getPlannedSprints() {
	      var sprints = new Set();
	      this.sprints.forEach(function (sprint) {
	        if (sprint.isPlanned()) {
	          sprints.add(sprint);
	        }
	      });
	      return sprints;
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
	    key: "existCompletedSprint",
	    value: function existCompletedSprint() {
	      return !main_core.Type.isUndefined(babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.isCompleted();
	      }));
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
	      var activeSprint = this.getActiveSprint();

	      if (activeSprint) {
	        items = new Map([].concat(babelHelpers.toConsumableArray(items), babelHelpers.toConsumableArray(activeSprint.getItems())));
	      }

	      babelHelpers.toConsumableArray(this.getPlannedSprints().values()).map(function (sprint) {
	        return items = new Map([].concat(babelHelpers.toConsumableArray(items), babelHelpers.toConsumableArray(sprint.getItems())));
	      });
	      return items;
	    }
	  }, {
	    key: "existsAtLeastOneItem",
	    value: function existsAtLeastOneItem() {
	      var backlogItems = new Map(this.backlog.getItems());

	      if (backlogItems.size > 0) {
	        return true;
	      }

	      var filledSprint = babelHelpers.toConsumableArray(this.sprints.values()).find(function (sprint) {
	        return sprint.getItems().size > 0;
	      });
	      return !main_core.Type.isUndefined(filledSprint);
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

	var _templateObject$y, _templateObject2$8, _templateObject3$3, _templateObject4$2, _templateObject5;
	var CompletedSprints = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(CompletedSprints, _EventEmitter);

	  function CompletedSprints(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, CompletedSprints);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompletedSprints).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.CompletedSprints');

	    _this.requestSender = params.requestSender;
	    _this.entityStorage = params.entityStorage;
	    _this.pageNumber = parseInt(params.pageNumber, 10);
	    _this.isShortView = params.isShortView;
	    _this.statsUploaded = false;
	    _this.sprintsUploaded = false;
	    _this.loader = null;
	    _this.isActiveLoad = false;
	    _this.node = null;
	    _this.listNode = null;
	    return _this;
	  }

	  babelHelpers.createClass(CompletedSprints, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$y || (_templateObject$y = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content\">\n\t\t\t\t<div class=\"tasks-scrum__sprints--completed\">\n\t\t\t\t\t<div class=\"tasks-scrum__sprints--completed-title\">\n\t\t\t\t\t\t<span class=\"tasks-scrum__sprints--completed-title-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_TITLE'), this.renderHeader(), this.renderList());
	      this.listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');
	      main_core.Event.bind(this.listNode, 'transitionend', this.onTransitionEnd.bind(this));
	      var observerTargetNode = this.listNode.querySelector('.tasks-scrum-completed-sprints-observer-target');
	      this.bindLoad(observerTargetNode);
	      return this.node;
	    }
	  }, {
	    key: "renderHeader",
	    value: function renderHeader() {
	      var btnStyles = 'tasks-scrum__sprint--btn-dropdown ui-btn ui-btn-sm ui-btn-icon-angle-down';
	      var header = main_core.Tag.render(_templateObject2$8 || (_templateObject2$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprints--completed-header\">\n\t\t\t\t<div class=\"tasks-scrum__sprints--completed-name\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__sprints--completed-stats\"></div>\n\t\t\t\t<div class=\"tasks-scrum__sprints--completed-btn ", "\"></div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_NAME'), btnStyles);
	      var btnNode = header.querySelector('.tasks-scrum__sprints--completed-btn');
	      main_core.Event.bind(btnNode, 'click', this.onBtnClick.bind(this));
	      return header;
	    }
	  }, {
	    key: "onBtnClick",
	    value: function onBtnClick(event) {
	      var node = event.currentTarget;
	      var isShown = node.classList.contains('--up');

	      if (isShown) {
	        main_core.Dom.removeClass(node, '--up');
	      } else {
	        main_core.Dom.addClass(node, '--up');
	        main_core.Dom.addClass(this.listNode, '--visible');

	        if (!this.isSprintsUploaded() && this.loader === null) {
	          this.loader = this.showLoader();
	        }
	      }

	      if (isShown) {
	        this.hideList();
	      } else {
	        this.showList();
	      }

	      this.onLoadStats();
	    }
	  }, {
	    key: "renderList",
	    value: function renderList() {
	      return main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprints--completed-list\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.renderObserverTarget());
	    }
	  }, {
	    key: "renderObserverTarget",
	    value: function renderObserverTarget() {
	      return main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-completed-sprints-observer-target\"></div>"])));
	    }
	  }, {
	    key: "renderStats",
	    value: function renderStats(stats) {
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_STATS').replace('#tasks#', parseInt(stats.averageNumberTasks, 10)).replace('#storypoints#', parseInt(stats.averageNumberStoryPoints, 10)).replace('#percent#', parseInt(stats.averagePercentageCompletion, 10)));
	    }
	  }, {
	    key: "isSprintsUploaded",
	    value: function isSprintsUploaded() {
	      return this.sprintsUploaded;
	    }
	  }, {
	    key: "bindLoad",
	    value: function bindLoad(loader) {
	      var _this2 = this;

	      if (typeof IntersectionObserver === "undefined") {
	        return;
	      }

	      var observer = new IntersectionObserver(function (entries) {
	        if (entries[0].isIntersecting === true) {
	          if (!_this2.isActiveLoad) {
	            _this2.onLoadCompletedSprints();
	          }
	        }
	      }, {
	        threshold: [0]
	      });
	      observer.observe(loader);
	    }
	  }, {
	    key: "onLoadStats",
	    value: function onLoadStats() {
	      var _this3 = this;

	      if (this.statsUploaded) {
	        return;
	      }

	      this.statsUploaded = true;
	      this.requestSender.getCompletedSprintsStats().then(function (response) {
	        _this3.updateStats(response.data);
	      }).catch(function (response) {
	        _this3.statsUploaded = false;

	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onLoadCompletedSprints",
	    value: function onLoadCompletedSprints() {
	      var _this4 = this;

	      this.isActiveLoad = true;

	      if (this.isSprintsUploaded() && this.loader === null) {
	        this.loader = this.showLoader();
	      }

	      var requestData = {
	        pageNumber: this.pageNumber
	      };
	      this.requestSender.getCompletedSprints(requestData).then(function (response) {
	        var data = response.data;

	        if (main_core.Type.isArray(data) && data.length) {
	          _this4.pageNumber++;

	          _this4.createSprints(data);

	          _this4.isActiveLoad = false;

	          _this4.showList();
	        }

	        _this4.sprintsUploaded = true;

	        if (_this4.loader) {
	          _this4.loader.hide();
	        }
	      }).catch(function (response) {
	        if (_this4.loader) {
	          _this4.loader.hide();
	        }

	        _this4.isActiveLoad = false;

	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      var listPosition = main_core.Dom.getPosition(this.listNode);
	      var loader = new main_loader.Loader({
	        target: this.listNode,
	        size: 60,
	        mode: 'inline',
	        offset: {
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      loader.show();
	      return loader;
	    }
	  }, {
	    key: "updateStats",
	    value: function updateStats(stats) {
	      var nameNode = this.node.querySelector('.tasks-scrum__sprints--completed-name');
	      var statsHeaderNode = this.node.querySelector('.tasks-scrum__sprints--completed-stats');
	      nameNode.textContent = nameNode.textContent + '- ' + parseInt(stats.numberSprints, 10);
	      main_core.Dom.append(this.renderStats(stats), statsHeaderNode);
	    }
	  }, {
	    key: "createSprints",
	    value: function createSprints(sprints) {
	      var _this5 = this;

	      sprints.forEach(function (sprintData) {
	        sprintData.isShortView = 'Y';
	        var sprint = Sprint.buildSprint(sprintData);

	        _this5.entityStorage.addSprint(sprint);

	        main_core.Dom.insertBefore(sprint.render(), _this5.listNode.querySelector('.tasks-scrum-completed-sprints-observer-target'));
	        sprint.onAfterAppend();

	        _this5.emit('createSprint', sprint);
	      });
	    }
	  }, {
	    key: "addSprint",
	    value: function addSprint(sprint) {
	      this.entityStorage.addSprint(sprint);
	      main_core.Dom.insertBefore(sprint.render(), this.listNode.firstElementChild);
	      sprint.onAfterAppend();
	      this.emit('createSprint', sprint);
	    }
	  }, {
	    key: "showList",
	    value: function showList() {
	      var parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');
	      main_core.Dom.addClass(parentNode, '--open');
	      this.listNode.style.height = "".concat(this.listNode.scrollHeight, "px");
	      this.emit('adjustWidth');
	    }
	  }, {
	    key: "hideList",
	    value: function hideList() {
	      var parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');
	      main_core.Dom.removeClass(parentNode, '--open');
	      this.listNode.style.height = "".concat(this.listNode.scrollHeight, "px");
	      this.listNode.clientHeight;
	      this.listNode.style.height = '0';
	    }
	  }, {
	    key: "onTransitionEnd",
	    value: function onTransitionEnd() {
	      var isHide = this.listNode.style.height === '0px';

	      if (isHide) {
	        main_core.Dom.removeClass(this.listNode, '--visible');
	      } else {
	        this.listNode.style.height = 'auto';
	      }

	      this.emit('adjustWidth');
	    }
	  }]);
	  return CompletedSprints;
	}(main_core_events.EventEmitter);

	var _templateObject$z, _templateObject2$9;
	var PlanBuilder = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PlanBuilder, _EventEmitter);

	  function PlanBuilder(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, PlanBuilder);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PlanBuilder).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.DomBuilder');

	    _this.requestSender = params.requestSender;
	    _this.entityStorage = params.entityStorage;
	    _this.defaultSprintDuration = params.defaultSprintDuration;
	    _this.pageNumberToCompletedSprints = params.pageNumberToCompletedSprints;
	    _this.displayPriority = params.displayPriority;
	    _this.isShortView = params.isShortView;
	    _this.mandatoryExists = params.mandatoryExists;
	    return _this;
	  }

	  babelHelpers.createClass(PlanBuilder, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      this.scrumContainer = container;
	      this.setWidthPriority(this.displayPriority);
	      main_core.Dom.append(this.entityStorage.getBacklog().render(), this.scrumContainer);
	      this.entityStorage.getBacklog().onAfterAppend();
	      main_core.Dom.append(this.renderSprintsContainer(), this.scrumContainer);
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isCompleted()) {
	          sprint.onAfterAppend();
	        }
	      });
	      this.emit('setDraggable');
	      this.adjustSprintListWidth();
	    }
	  }, {
	    key: "renderSprintsContainer",
	    value: function renderSprintsContainer() {
	      var _this2 = this;

	      this.completedSprints = new CompletedSprints({
	        requestSender: this.requestSender,
	        entityStorage: this.entityStorage,
	        pageNumber: this.pageNumberToCompletedSprints,
	        isShortView: this.isShortView
	      });
	      this.completedSprints.subscribe('createSprint', function (baseEvent) {
	        _this2.emit('createSprint', baseEvent.getData());
	      });
	      this.completedSprints.subscribe('adjustWidth', function () {
	        return _this2.adjustSprintListWidth();
	      });
	      var activeSprint = this.entityStorage.getActiveSprint();
	      var plannedSprints = this.entityStorage.getPlannedSprints();
	      this.sprintsNode = main_core.Tag.render(_templateObject$z || (_templateObject$z = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__sprints --scrollbar\">\n\t\t\t\t<div class=\"tasks-scrum__sprints--active ", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__sprints--planned ", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), activeSprint ? '' : '--empty', activeSprint ? activeSprint.render() : '', plannedSprints.size ? '' : '--empty', babelHelpers.toConsumableArray(plannedSprints.values()).map(function (sprint) {
	        return sprint.render();
	      }), this.renderSprintDropzone(), this.entityStorage.existCompletedSprint() ? this.completedSprints.render() : '');
	      this.updatePlannedSprints(plannedSprints, !main_core.Type.isUndefined(activeSprint));
	      main_core.Event.bind(this.sprintsNode, 'scroll', this.onSprintsScroll.bind(this));
	      return this.sprintsNode;
	    }
	  }, {
	    key: "renderSprintDropzone",
	    value: function renderSprintDropzone() {
	      this.sprintDropzone = main_core.Tag.render(_templateObject2$9 || (_templateObject2$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__content\">\n\t\t\t\t<div class=\"tasks-scrum__sprints--new-sprint\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_PLAN_SPRINT_DROPZONE'));
	      main_core.Event.bind(this.sprintDropzone, 'click', this.createSprint.bind(this));
	      return this.sprintDropzone;
	    }
	  }, {
	    key: "setWidthPriority",
	    value: function setWidthPriority(value) {
	      if (value === 'backlog') {
	        main_core.Dom.addClass(this.scrumContainer, '--width-priority-backlog');
	      } else {
	        main_core.Dom.removeClass(this.scrumContainer, '--width-priority-backlog');
	      }
	    }
	  }, {
	    key: "setShortView",
	    value: function setShortView(value) {
	      this.isShortView = value;
	    }
	  }, {
	    key: "getSprintsContainer",
	    value: function getSprintsContainer() {
	      return this.sprintsNode;
	    }
	  }, {
	    key: "getSprintDropzone",
	    value: function getSprintDropzone() {
	      return this.sprintDropzone;
	    }
	  }, {
	    key: "isSprintDropzone",
	    value: function isSprintDropzone(container) {
	      if (container.firstElementChild) {
	        return container.firstElementChild.classList.contains('tasks-scrum__sprints--new-sprint');
	      } else {
	        return false;
	      }
	    }
	  }, {
	    key: "createSprint",
	    value: function createSprint() {
	      var _this3 = this;

	      var dateStart = Math.floor(Date.now() / 1000);
	      var dateEnd = Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10);
	      var sort = this.entityStorage.getPlannedSprints().size + 1;
	      var requestData = {
	        tmpId: main_core.Text.getRandom(),
	        sort: sort + 1,
	        dateStart: dateStart,
	        dateEnd: dateEnd
	      };
	      this.emit('beforeCreateSprint', requestData);
	      return this.requestSender.createSprint(requestData).then(function (response) {
	        var sprintParams = response.data;
	        sprintParams.isShortView = _this3.isShortView;
	        sprintParams.mandatoryExists = _this3.mandatoryExists;
	        var sprint = Sprint.buildSprint(sprintParams);

	        _this3.entityStorage.addSprint(sprint);

	        _this3.appendToPlannedContainer(sprint);

	        _this3.scrollToSprint(sprint);

	        _this3.emit('createSprint', sprint);

	        return sprint;
	      }).catch(function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "createSprintNode",
	    value: function createSprintNode(sprint) {
	      sprint.setShortView(this.isShortView);
	      sprint.setMandatory(this.mandatoryExists);
	      this.entityStorage.addSprint(sprint);
	      this.appendToPlannedContainer(sprint);
	      this.emit('createSprintNode', sprint);
	    }
	  }, {
	    key: "appendToPlannedContainer",
	    value: function appendToPlannedContainer(sprint) {
	      var container = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--planned');
	      main_core.Dom.append(sprint.render(), container);
	      main_core.Dom.removeClass(container, '--empty');
	      sprint.onAfterAppend();
	      this.adjustSprintListWidth();
	      this.updatePlannedSprints(this.entityStorage.getPlannedSprints(), !main_core.Type.isUndefined(this.entityStorage.getActiveSprint()));
	    }
	  }, {
	    key: "moveSprintToActiveListNode",
	    value: function moveSprintToActiveListNode(sprint) {
	      var container = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--active');
	      main_core.Dom.append(sprint.getNode(), container);
	      main_core.Dom.removeClass(container, '--empty');
	    }
	  }, {
	    key: "moveSprintToCompletedListNode",
	    value: function moveSprintToCompletedListNode(sprint) {
	      sprint.removeNode();
	      this.entityStorage.removeSprint(sprint.getId());

	      if (this.completedSprints.isSprintsUploaded()) {
	        this.completedSprints.addSprint(sprint);
	      }

	      this.adjustSprintListWidth();
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
	  }, {
	    key: "onSprintsScroll",
	    value: function onSprintsScroll() {
	      this.emit('sprintsScroll');
	    }
	  }, {
	    key: "adjustSprintListWidth",
	    value: function adjustSprintListWidth() {
	      this.updateSprintContainers();
	      var hasScroll = this.getSprintsContainer().scrollHeight > this.getSprintsContainer().clientHeight;

	      if (hasScroll) {
	        main_core.Dom.addClass(this.getSprintsContainer(), '--scrollbar');
	      } else {
	        main_core.Dom.removeClass(this.getSprintsContainer(), '--scrollbar');
	      }
	    }
	  }, {
	    key: "updateSprintContainers",
	    value: function updateSprintContainers() {
	      var activeSprint = this.entityStorage.getActiveSprint();
	      var activeContainer = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--active');

	      if (activeSprint) {
	        main_core.Dom.removeClass(activeContainer, '--empty');
	      } else {
	        main_core.Dom.addClass(activeContainer, '--empty');
	      }

	      var plannedSprints = this.entityStorage.getPlannedSprints();
	      var plannedContainer = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--planned');

	      if (plannedSprints.size) {
	        main_core.Dom.removeClass(plannedContainer, '--empty');
	      } else {
	        main_core.Dom.addClass(plannedContainer, '--empty');
	      }
	    }
	  }, {
	    key: "scrollToSprint",
	    value: function scrollToSprint(sprint) {
	      // todo dynamic focus to sprint node (loadItems)
	      window.scrollTo({
	        top: 240,
	        behavior: 'smooth'
	      });
	      var sprintsContainer = this.getSprintsContainer();
	      var position = main_core.Dom.getRelativePosition(sprint.getNode(), sprintsContainer).bottom;
	      sprintsContainer.scrollTo({
	        top: sprintsContainer.scrollTop + position,
	        behavior: 'smooth'
	      });
	    }
	  }, {
	    key: "updatePlannedSprints",
	    value: function updatePlannedSprints(plannedSprints, existActiveSprint) {
	      if (existActiveSprint) {
	        plannedSprints.forEach(function (plannedSprint) {
	          plannedSprint.disableHeaderButton();
	        });
	      } else {
	        plannedSprints.forEach(function (plannedSprint) {
	          plannedSprint.unDisableHeaderButton();
	        });
	      }
	    }
	  }, {
	    key: "getScrumContainer",
	    value: function getScrumContainer() {
	      return this.scrumContainer;
	    }
	  }, {
	    key: "blockScrumContainerSelect",
	    value: function blockScrumContainerSelect() {
	      main_core.Dom.addClass(this.scrumContainer, '--select-none');
	    }
	  }, {
	    key: "unblockScrumContainerSelect",
	    value: function unblockScrumContainerSelect() {
	      var _this4 = this;

	      setTimeout(function () {
	        main_core.Dom.removeClass(_this4.scrumContainer, '--select-none');
	      }, 500);
	    }
	  }]);
	  return PlanBuilder;
	}(main_core_events.EventEmitter);

	var _templateObject$A, _templateObject2$a, _templateObject3$4, _templateObject4$3, _templateObject5$1, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12;
	var ActionPanel = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ActionPanel, _EventEmitter);

	  function ActionPanel(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, ActionPanel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActionPanel).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.ActionPanel');

	    _this.entity = params.entity;
	    _this.item = params.item;
	    _this.bindElement = _this.item.getNode();
	    _this.itemList = babelHelpers.objectSpread({}, {
	      task: {
	        activity: false
	      },
	      attachment: {
	        activity: false
	      },
	      dod: {
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
	    }, params.itemList);
	    _this.node = null;
	    _this.isBlockBlur = false;

	    _this.observeBindElement();

	    return _this;
	  }

	  babelHelpers.createClass(ActionPanel, [{
	    key: "show",
	    value: function show() {
	      this.node = this.calculatePanelPosition(this.createActionPanel());
	      this.bindItems();
	      main_core.Dom.append(this.node, document.body);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Dom.remove(this.node);
	      this.node = null;

	      if (this.observer) {
	        this.observer.disconnect();
	      }
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "getItem",
	    value: function getItem() {
	      return this.item;
	    }
	  }, {
	    key: "createActionPanel",
	    value: function createActionPanel() {
	      var task = '';
	      var attachment = '';
	      var dod = '';
	      var move = '';
	      var sprint = '';
	      var backlog = '';
	      var tags = '';
	      var epic = '';
	      var decomposition = '';
	      var remove = '';
	      var baseBtnClass = 'tasks-scrum__action-panel--btn';
	      var arrowClass = 'tasks-scrum__action-panel--btn-with-arrow';
	      var selected = main_core.Tag.render(_templateObject$A || (_templateObject$A = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__action-panel--selected-btn tasks-scrum__action-panel--btn-selected\">\n\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t\t<span class=\"tasks-scrum__action-panel--icon\"></span>\n\t\t\t</div>\n\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t"])), this.getSelectedText(this.entity.getGroupModeItems().size));

	      if (this.itemList.task.activity) {
	        var disableClass = this.itemList.task.disable === true ? '--disabled' : '';
	        task = main_core.Tag.render(_templateObject2$a || (_templateObject2$a = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-task ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, disableClass, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK'));
	      }

	      if (this.itemList.attachment.activity) {
	        var _disableClass = this.itemList.attachment.disable === true ? '--disabled' : '';

	        attachment = main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-attachment ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--icon\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, _disableClass);
	      }

	      if (this.itemList.dod.activity) {
	        var _disableClass2 = this.itemList.dod.disable === true ? '--disabled' : '';

	        dod = main_core.Tag.render(_templateObject4$3 || (_templateObject4$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-dod ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, _disableClass2, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_DOD'));
	      }

	      if (this.itemList.move.activity) {
	        var _disableClass3 = this.itemList.move.disable === true ? '--disabled' : '';

	        move = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-move ", " ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, arrowClass, _disableClass3, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE'));
	      }

	      if (this.itemList.sprint.activity) {
	        var _disableClass4 = this.itemList.sprint.disable === true ? '--disabled' : '';

	        var sprintArrowClass = this.itemList.sprint.multiple === true ? arrowClass : '';
	        sprint = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-sprint ", " ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, sprintArrowClass, _disableClass4, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SPRINT'));
	      }

	      if (this.itemList.backlog.activity) {
	        var _disableClass5 = this.itemList.backlog.disable === true ? '--disabled' : '';

	        backlog = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-backlog ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, _disableClass5, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_BACKLOG'));
	      }

	      if (this.itemList.tags.activity) {
	        var _disableClass6 = this.itemList.tags.disable === true ? '--disabled' : '';

	        tags = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-tags ", " ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, arrowClass, _disableClass6, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TAGS'));
	      }

	      if (this.itemList.epic.activity) {
	        var _disableClass7 = this.itemList.epic.disable === true ? '--disabled' : '';

	        epic = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-epics ", " ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, arrowClass, _disableClass7, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_EPIC'));
	      }

	      if (this.itemList.decomposition.activity) {
	        var _disableClass8 = this.itemList.decomposition.disable === true ? '--disabled' : '';

	        decomposition = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-decomposition ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--icon\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__action-panel--separator\"></div>\n\t\t\t"])), baseBtnClass, _disableClass8);
	      }

	      if (this.itemList.remove.activity) {
	        var _disableClass9 = this.itemList.remove.disable === true ? '--disabled' : '';

	        remove = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", " tasks-scrum__action-panel--btn-remove ", "\">\n\t\t\t\t\t<span class=\"tasks-scrum__action-panel--icon\"></span>\n\t\t\t\t</div>\n\t\t\t"])), baseBtnClass, _disableClass9);
	      }

	      return main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__action-panel--container tasks-scrum__action-panel--scope\" tabindex=\"1\">\n\t\t\t\t<div class=\"tasks-scrum__action-panel\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), selected, task, attachment, dod, move, sprint, backlog, tags, epic, decomposition, remove);
	    }
	  }, {
	    key: "bindItems",
	    value: function bindItems() {
	      var _this2 = this;

	      var selectedBtn = this.node.querySelector('.tasks-scrum__action-panel--btn-selected');
	      main_core.Event.bind(selectedBtn.querySelector('.tasks-scrum__action-panel--icon'), 'click', function () {
	        return _this2.emit('unSelect');
	      });

	      if (this.itemList.task.activity && this.itemList.task.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-task'), 'click', this.itemList.task.callback);
	      }

	      if (this.itemList.attachment.activity && this.itemList.attachment.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-attachment'), 'click', this.itemList.attachment.callback);
	      }

	      if (this.itemList.dod.activity && this.itemList.dod.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-dod'), 'click', this.itemList.dod.callback);
	      }

	      if (this.itemList.move.activity && this.itemList.move.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-move'), 'click', this.itemList.move.callback);
	      }

	      if (this.itemList.sprint.activity && this.itemList.sprint.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-sprint'), 'click', this.itemList.sprint.callback);
	      }

	      if (this.itemList.backlog.activity && this.itemList.backlog.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-backlog'), 'click', this.itemList.backlog.callback);
	      }

	      if (this.itemList.tags.activity && this.itemList.tags.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-tags'), 'click', this.itemList.tags.callback);
	      }

	      if (this.itemList.epic.activity && this.itemList.epic.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-epics'), 'click', this.itemList.epic.callback);
	      }

	      if (this.itemList.decomposition.activity && this.itemList.decomposition.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-decomposition'), 'click', this.itemList.decomposition.callback);
	      }

	      if (this.itemList.remove.activity && this.itemList.remove.disable !== true) {
	        main_core.Event.bind(this.node.querySelector('.tasks-scrum__action-panel--btn-remove'), 'click', this.itemList.remove.callback);
	      }
	    }
	  }, {
	    key: "getSelectedText",
	    value: function getSelectedText(number) {
	      return main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SELECTED') + parseInt(number, 10);
	    }
	  }, {
	    key: "observeBindElement",
	    value: function observeBindElement() {
	      var _this3 = this;

	      if (typeof IntersectionObserver === "undefined") {
	        return;
	      }

	      this.observer = new IntersectionObserver(function (entries) {
	        if (entries[0].isIntersecting === true) {
	          _this3.displayPanel();
	        } else {
	          _this3.hidePanel();
	        }
	      }, {
	        threshold: [0]
	      });
	      this.observer.observe(this.bindElement);
	    }
	  }, {
	    key: "displayPanel",
	    value: function displayPanel() {
	      if (!main_core.Dom.isShown(this.getNode())) {
	        main_core.Dom.style(this.getNode(), 'display', 'block');
	      }
	    }
	  }, {
	    key: "hidePanel",
	    value: function hidePanel() {
	      if (main_core.Dom.isShown(this.getNode())) {
	        main_core.Dom.style(this.getNode(), 'display', 'none');
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
	      this.removeLastSeparator(panel);
	      return panel;
	    }
	  }, {
	    key: "isPanelWiderThanViewport",
	    value: function isPanelWiderThanViewport(element) {
	      var rect = element.getBoundingClientRect();
	      var windowWidth = window.innerWidth || document.documentElement.clientWidth;
	      return rect.right > windowWidth;
	    }
	  }, {
	    key: "calculatePanelTopPosition",
	    value: function calculatePanelTopPosition() {
	      if (!this.getNode()) {
	        return;
	      }

	      var position = main_core.Dom.getPosition(this.bindElement);
	      this.getNode().style.top = "".concat(position.top, "px");
	    }
	  }, {
	    key: "removeLastSeparator",
	    value: function removeLastSeparator(panel) {
	      var actionPanel = panel.querySelector('.tasks-scrum__action-panel');

	      if (main_core.Dom.hasClass(actionPanel.lastElementChild, 'tasks-scrum__action-panel--separator')) {
	        main_core.Dom.remove(actionPanel.lastElementChild);
	      }
	    }
	  }]);
	  return ActionPanel;
	}(main_core_events.EventEmitter);

	var _templateObject$B;
	var SearchArrows = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SearchArrows, _EventEmitter);

	  function SearchArrows(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, SearchArrows);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SearchArrows).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.SearchArrows');

	    _this.list = params.list;
	    _this.currentPosition = parseInt(params.currentPosition, 10);
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(SearchArrows, [{
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject$B || (_templateObject$B = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item--nav-linked tasks-scrum__scope\">\n\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-close\"></div>\n\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-block\">\n\t\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-prev\"></div>\n\t\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-num-container --visible\">\n\t\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-num\">", "/", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__item--nav-linked-next\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.currentPosition, this.list.size);
	      var closeBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-close');
	      var prevBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-prev');
	      var nextBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-next');
	      main_core.Event.bind(closeBtn, 'click', this.onClose.bind(this));
	      main_core.Event.bind(prevBtn, 'click', this.onPrev.bind(this));
	      main_core.Event.bind(nextBtn, 'click', this.onNext.bind(this));
	      return this.node;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.node;
	    }
	  }, {
	    key: "updateCurrentPosition",
	    value: function updateCurrentPosition(value) {
	      this.currentPosition = parseInt(value, 10);

	      if (this.node) {
	        this.node.querySelector('.tasks-scrum__item--nav-linked-num').textContent = "".concat(this.currentPosition, "/").concat(this.list.size);
	      }
	    }
	  }, {
	    key: "remove",
	    value: function remove() {
	      main_core.Dom.remove(this.node);
	      this.node = null;
	    }
	  }, {
	    key: "onClose",
	    value: function onClose() {
	      this.emit('close');
	    }
	  }, {
	    key: "onPrev",
	    value: function onPrev() {
	      this.emit('prev');
	    }
	  }, {
	    key: "onNext",
	    value: function onNext() {
	      this.emit('next');
	    }
	  }]);
	  return SearchArrows;
	}(main_core_events.EventEmitter);

	var Scroller = /*#__PURE__*/function () {
	  function Scroller(params) {
	    babelHelpers.classCallCheck(this, Scroller);
	    this.planBuilder = params.planBuilder;
	    this.entityStorage = params.entityStorage;
	  }

	  babelHelpers.createClass(Scroller, [{
	    key: "scrollToItem",
	    value: function scrollToItem(item) {
	      if (this.isItemInViewport(item)) {
	        return;
	      }

	      var offset = 112;

	      if (this.isBacklogItem(item)) {
	        var scrollContainer = this.entityStorage.getBacklog().getListItemsNode();
	        var itemTopPosition = main_core.Dom.getRelativePosition(item.getNode(), scrollContainer).top;
	        scrollContainer.scrollTo({
	          top: scrollContainer.scrollTop + itemTopPosition - offset,
	          behavior: 'smooth'
	        });
	      } else {
	        var sprintsContainer = this.planBuilder.getSprintsContainer();
	        var _itemTopPosition = main_core.Dom.getRelativePosition(item.getNode(), sprintsContainer).top;
	        sprintsContainer.scrollTo({
	          top: sprintsContainer.scrollTop + _itemTopPosition - offset,
	          behavior: 'smooth'
	        });
	      }
	    }
	  }, {
	    key: "isItemInViewport",
	    value: function isItemInViewport(item) {
	      var rect = item.getNode().getBoundingClientRect();
	      return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
	    }
	  }, {
	    key: "isBacklogItem",
	    value: function isBacklogItem(item) {
	      return this.entityStorage.getBacklog().hasItem(item);
	    }
	  }]);
	  return Scroller;
	}();

	var SearchItems = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SearchItems, _EventEmitter);

	  function SearchItems(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, SearchItems);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SearchItems).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.SearchItems');

	    _this.planBuilder = params.planBuilder;
	    _this.entityStorage = params.entityStorage;
	    _this.scroller = new Scroller({
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage
	    });
	    _this.active = false;
	    _this.list = new Set();
	    _this.currentIndex = 0;
	    _this.arrows = null;
	    return _this;
	  }

	  babelHelpers.createClass(SearchItems, [{
	    key: "start",
	    value: function start(startItem, linkedItemIds) {
	      this.active = true;
	      this.setList(linkedItemIds);
	      this.fadeOutAll();

	      if (!this.isBacklogItem(startItem)) {
	        this.scroller.scrollToItem(this.getCurrent());
	      }

	      if (!startItem.isDisabled()) {
	        this.updateCurrentIndexByItem(startItem);
	        this.activateCurrent(startItem);
	      }

	      this.list.forEach(function (item) {
	        item.activateLinkedMode();
	      });
	      this.showArrows();
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      this.active = false;
	      this.currentIndex = 0;
	      this.fadeInAll();
	      this.cleanList();
	      this.removeArrows();
	    }
	  }, {
	    key: "setList",
	    value: function setList(linkedItemIds) {
	      var _this2 = this;

	      this.list = new Set();
	      var items = this.entityStorage.getAllItems();
	      linkedItemIds.forEach(function (itemId) {
	        if (items.has(itemId)) {
	          _this2.list.add(items.get(itemId));
	        }
	      });
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.active;
	    }
	  }, {
	    key: "isBacklogItem",
	    value: function isBacklogItem(item) {
	      return this.entityStorage.getBacklog().hasItem(item);
	    }
	  }, {
	    key: "getCurrent",
	    value: function getCurrent() {
	      return babelHelpers.toConsumableArray(this.list.values())[this.currentIndex];
	    }
	  }, {
	    key: "updateCurrentIndexByItem",
	    value: function updateCurrentIndexByItem(inputItem) {
	      var _this3 = this;

	      this.deactivateCurrent(this.getCurrent());
	      babelHelpers.toConsumableArray(this.list.values()).forEach(function (item, index) {
	        if (inputItem.getId() === item.getId()) {
	          _this3.currentIndex = index;
	        }
	      });
	      this.updateArrows();
	    }
	  }, {
	    key: "moveToPrev",
	    value: function moveToPrev() {
	      this.deactivateCurrent(this.getCurrent());
	      this.currentIndex--;

	      if (this.currentIndex < 0) {
	        this.currentIndex = this.list.size - 1;
	      }

	      this.updateArrows();
	      var currentItem = this.getCurrent();
	      this.scroller.scrollToItem(currentItem);
	      this.activateCurrent(currentItem);
	    }
	  }, {
	    key: "moveToNext",
	    value: function moveToNext() {
	      this.deactivateCurrent(this.getCurrent());
	      this.currentIndex++;

	      if (this.currentIndex === this.list.size) {
	        this.currentIndex = 0;
	      }

	      this.updateArrows();
	      var currentItem = this.getCurrent();
	      this.scroller.scrollToItem(currentItem);
	      this.activateCurrent(currentItem);
	    }
	  }, {
	    key: "activateCurrent",
	    value: function activateCurrent(item) {
	      item.activateCurrentLinkedMode(item);
	    }
	  }, {
	    key: "deactivateCurrent",
	    value: function deactivateCurrent() {
	      this.list.forEach(function (item) {
	        item.deactivateCurrentLinkedMode();
	      });
	    }
	  }, {
	    key: "fadeOutAll",
	    value: function fadeOutAll() {
	      this.entityStorage.getBacklog().fadeOut();
	      this.entityStorage.getBacklog().setActiveLoadItems(true);
	      var activeSprint = this.entityStorage.getActiveSprint();

	      if (activeSprint) {
	        activeSprint.fadeOut();
	        activeSprint.setActiveLoadItems(true);

	        if (activeSprint.isHideContent()) {
	          activeSprint.toggleVisibilityContent(activeSprint.getContentContainer());
	        }
	      }

	      this.entityStorage.getPlannedSprints().forEach(function (sprint) {
	        sprint.fadeOut();
	        sprint.setActiveLoadItems(true);

	        if (sprint.isHideContent()) {
	          sprint.toggleVisibilityContent(sprint.getContentContainer());
	        }
	      });
	    }
	  }, {
	    key: "fadeInAll",
	    value: function fadeInAll() {
	      this.entityStorage.getBacklog().fadeIn();
	      var activeSprint = this.entityStorage.getActiveSprint();

	      if (activeSprint) {
	        activeSprint.fadeIn();
	      }

	      this.entityStorage.getPlannedSprints().forEach(function (sprint) {
	        sprint.fadeIn();
	      });
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode() {
	      this.entityStorage.getBacklog().deactivateGroupMode();
	      var activeSprint = this.entityStorage.getActiveSprint();

	      if (activeSprint) {
	        activeSprint.deactivateGroupMode();
	      }

	      this.entityStorage.getPlannedSprints().forEach(function (sprint) {
	        sprint.deactivateGroupMode();
	      });
	    }
	  }, {
	    key: "showArrows",
	    value: function showArrows() {
	      this.arrows = new SearchArrows({
	        currentPosition: this.currentIndex + 1,
	        list: this.list
	      });
	      this.arrows.subscribe('close', this.stop.bind(this));
	      this.arrows.subscribe('prev', this.moveToPrev.bind(this));
	      this.arrows.subscribe('next', this.moveToNext.bind(this));
	      main_core.Dom.append(this.arrows.render(), document.body);
	      this.adjustArrowsPosition();
	    }
	  }, {
	    key: "updateArrows",
	    value: function updateArrows() {
	      if (this.arrows) {
	        this.arrows.updateCurrentPosition(this.currentIndex + 1);
	      }
	    }
	  }, {
	    key: "adjustArrowsPosition",
	    value: function adjustArrowsPosition() {
	      var arrowsRect = main_core.Dom.getPosition(this.arrows.getNode());
	      var backlogRect = main_core.Dom.getPosition(this.entityStorage.getBacklog().getNode());
	      this.arrows.getNode().style.top = "".concat(backlogRect.height / 2 + (backlogRect.top - arrowsRect.height), "px");
	      this.arrows.getNode().style.left = "".concat(backlogRect.left - (arrowsRect.width / 2 + 16), "px");
	    }
	  }, {
	    key: "cleanList",
	    value: function cleanList() {
	      this.list.forEach(function (item) {
	        return item.deactivateLinkedMode();
	      });
	      this.list = new Set();
	    }
	  }, {
	    key: "removeArrows",
	    value: function removeArrows() {
	      this.arrows.remove();
	    }
	  }, {
	    key: "isClickInside",
	    value: function isClickInside(node) {
	      var isClickInside = false;
	      var backlog = this.entityStorage.getBacklog();

	      if (backlog.getNode().contains(node)) {
	        isClickInside = true;
	      }

	      var activeSprint = this.entityStorage.getActiveSprint();

	      if (activeSprint && activeSprint.getNode().contains(node)) {
	        isClickInside = true;
	      }

	      if (this.arrows && this.arrows.getNode().contains(node)) {
	        isClickInside = true;
	      }

	      this.entityStorage.getPlannedSprints().forEach(function (sprint) {
	        if (sprint.getNode().contains(node)) {
	          isClickInside = true;
	        }
	      });
	      return isClickInside;
	    }
	  }]);
	  return SearchItems;
	}(main_core_events.EventEmitter);

	var SprintMover = /*#__PURE__*/function () {
	  function SprintMover(params) {
	    babelHelpers.classCallCheck(this, SprintMover);
	    this.requestSender = params.requestSender;
	    this.planBuilder = params.planBuilder;
	    this.entityStorage = params.entityStorage;
	    this.bindHandlers();
	  }

	  babelHelpers.createClass(SprintMover, [{
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      this.planBuilder.subscribe('setDraggable', this.onSetDraggable.bind(this)); //this.planBuilder.subscribe('sprintMove', this.onSprintMove.bind(this))
	    }
	  }, {
	    key: "onSetDraggable",
	    value: function onSetDraggable(baseEvent) {// this.draggableSprints = new Draggable({
	      // 	container: this.sprintsNode.querySelector('.tasks-scrum__sprints--planned'),
	      // 	draggable: '.tasks-scrum-sprint',
	      // 	dragElement: '.tasks-scrum-sprint-dragndrop',
	      // 	type: Draggable.DROP_PREVIEW,
	      // });
	      // this.draggableSprints.subscribe('end', (baseEvent) => {
	      // 	const dragEndEvent = baseEvent.getData();
	      // 	this.emit('sprintMove', dragEndEvent);
	      // });
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
	      var increment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      var listSortInfo = {};
	      var sprints = this.entityStorage.getPlannedSprints();
	      var sort = 1 + increment;
	      sprints.forEach(function (sprint) {
	        sprint.setSort(sort);
	        listSortInfo[sprint.getId()] = {
	          sort: sort
	        };
	        sort++;
	      });
	      return listSortInfo;
	    }
	  }]);
	  return SprintMover;
	}();

	var SprintSidePanel = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SprintSidePanel, _EventEmitter);

	  function SprintSidePanel(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, SprintSidePanel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SprintSidePanel).call(this, params));
	    _this.sidePanel = params.sidePanel;
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.views = params.views;
	    _this.pathToBurnDown = params.pathToBurnDown ? params.pathToBurnDown : '';
	    return _this;
	  }

	  babelHelpers.createClass(SprintSidePanel, [{
	    key: "showStartForm",
	    value: function showStartForm(sprint) {
	      var _this2 = this;

	      this.sidePanel.showByExtension('Sprint-Start-Form', {
	        groupId: this.groupId,
	        sprintId: sprint.getId()
	      }).then(function (extension) {
	        if (extension) {
	          extension.subscribe('afterStart', function (baseEvent) {
	            location.href = _this2.views['activeSprint'].url;
	          });
	        }
	      });
	    }
	  }, {
	    key: "showCompletionForm",
	    value: function showCompletionForm() {
	      var _this3 = this;

	      this.sidePanel.showByExtension('Sprint-Completion-Form', {
	        groupId: this.groupId
	      }).then(function (extension) {
	        if (extension) {
	          extension.subscribe('afterComplete', function (baseEvent) {
	            location.href = _this3.views['plan'].url;
	          });
	          extension.subscribe('taskClick', function (baseEvent) {
	            _this3.emit('showTask', baseEvent.getData());
	          });
	        }
	      });
	    }
	  }, {
	    key: "showBurnDownChart",
	    value: function showBurnDownChart(sprint) {
	      if (this.pathToBurnDown) {
	        this.sidePanel.openSidePanel(this.pathToBurnDown.replace('#sprint_id#', sprint.getId()));
	      } else {
	        throw new Error('Could not find a page to display the chart.');
	      }
	    }
	  }]);
	  return SprintSidePanel;
	}(main_core_events.EventEmitter);

	var EntityCounters = /*#__PURE__*/function () {
	  function EntityCounters(params) {
	    babelHelpers.classCallCheck(this, EntityCounters);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	  }

	  babelHelpers.createClass(EntityCounters, [{
	    key: "updateCounters",
	    value: function updateCounters(entities) {
	      var _this = this;

	      var requestData = {
	        entityIds: babelHelpers.toConsumableArray(entities.keys())
	      };
	      this.requestSender.getEntityCounters(requestData).then(function (response) {
	        Object.keys(response.data).forEach(function (entityId) {
	          entityId = parseInt(entityId, 10);
	          var entity = entities.get(entityId);
	          var counters = response.data[entityId];
	          entity.setStoryPoints(counters.storyPoints);
	          entity.setNumberTasks(counters.numberTasks);

	          if (entity.isActive()) {
	            entity.setCompletedStoryPoints(counters.completedStoryPoints);
	            entity.setUncompletedStoryPoints(counters.uncompletedStoryPoints);
	          }
	        });
	      }).catch(function (response) {
	        _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }]);
	  return EntityCounters;
	}();

	var ItemMover = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ItemMover, _EventEmitter);

	  function ItemMover(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, ItemMover);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemMover).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.ItemMover');

	    _this.requestSender = params.requestSender;
	    _this.planBuilder = params.planBuilder;
	    _this.entityStorage = params.entityStorage;
	    _this.entityCounters = params.entityCounters;
	    _this.scroller = new Scroller({
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage
	    });
	    _this.dragItems = new Set();
	    _this.lastOutContainer = null;

	    _this.bindHandlers();

	    return _this;
	  }

	  babelHelpers.createClass(ItemMover, [{
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      var _this2 = this;

	      this.planBuilder.subscribe('setDraggable', this.onSetDraggable.bind(this));
	      this.planBuilder.subscribe('createSprintNode', function (baseEvent) {
	        var sprint = baseEvent.getData();

	        _this2.draggableItems.addContainer(sprint.getListItemsNode());

	        _this2.draggableItems.addDropzone(sprint.getDropzone());
	      });
	    }
	  }, {
	    key: "onSetDraggable",
	    value: function onSetDraggable(baseEvent) {
	      var backlog = this.entityStorage.getBacklog();
	      var containers = [backlog.getListItemsNode()];
	      var dropZones = [backlog.getDropzone(), this.planBuilder.getSprintDropzone()];
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isDisabled()) {
	          containers.push(sprint.getListItemsNode());

	          if (sprint.getDropzone()) {
	            dropZones.push(sprint.getDropzone());
	          }
	        }
	      });
	      this.draggableItems = new ui_draganddrop_draggable.Draggable({
	        container: containers,
	        dropzone: dropZones,
	        draggable: '.tasks-scrum__item--drag',
	        dragElement: '.tasks-scrum__item',
	        type: ui_draganddrop_draggable.Draggable.DROP_PREVIEW,
	        delay: 260
	      });
	      this.draggableItems.subscribe('beforeStart', this.onBeforeDragStart.bind(this));
	      this.draggableItems.subscribe('start', this.onDragStart.bind(this));
	      this.draggableItems.subscribe('end', this.onDragEnd.bind(this));
	      this.draggableItems.subscribe('drop', this.onDropEnd.bind(this));
	      this.draggableItems.subscribe('dropzone:enter', this.onDropZoneEnter.bind(this));
	      this.draggableItems.subscribe('dropzone:out', this.onDropZoneOut.bind(this));
	      this.draggableItems.subscribe('container:enter', this.onDragContainerEnter.bind(this));
	      this.draggableItems.subscribe('container:out', this.onDragContainerOut.bind(this));
	      this.draggableItems.subscribe('out', this.onDragOut.bind(this));
	    }
	  }, {
	    key: "onBeforeDragStart",
	    value: function onBeforeDragStart(baseEvent) {
	      var _this3 = this;

	      var dragBeforeStartEvent = baseEvent.getData();

	      if (!dragBeforeStartEvent.source) {
	        return;
	      }

	      var itemId = parseInt(dragBeforeStartEvent.source.dataset.id, 10);
	      var item = this.entityStorage.findItemByItemId(itemId);

	      if (item.isSubTask() || item.isDisabled()) {
	        baseEvent.preventDefault();
	      } else {
	        if (item.isShownSubTasks()) {
	          item.hideSubTasks();
	        }

	        var sourceContainer = dragBeforeStartEvent.sourceContainer;
	        var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	        var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

	        if (!sourceEntity) {
	          baseEvent.preventDefault();
	          return;
	        }

	        this.dragItems.clear();
	        sourceEntity.getGroupModeItems().forEach(function (selectedItem) {
	          if (selectedItem.getId() !== item.getId()) {
	            _this3.dragItems.add(selectedItem);
	          }
	        });
	        var isMultipleDrag = this.dragItems.size > 0;

	        if (isMultipleDrag) {
	          this.addMultipleMode(item, this.dragItems);
	        }

	        this.entityStorage.getAllEntities().forEach(function (entity) {
	          entity.deactivateGroupMode();
	          entity.getItems().forEach(function (entityItem) {
	            if (entityItem.isShownSubTasks()) {
	              entityItem.hideSubTasks();
	            }
	          });
	        });
	        this.emit('dragStart');
	      }
	    }
	  }, {
	    key: "onDragStart",
	    value: function onDragStart(baseEvent) {
	      this.planBuilder.blockScrumContainerSelect();
	      var dragStartEvent = baseEvent.getData();
	      var sourceContainer = dragStartEvent.sourceContainer;
	      var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	      var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

	      if (!sourceEntity.isBacklog()) {
	        var backlog = this.entityStorage.getBacklog();

	        if (backlog.isEmpty()) {
	          backlog.showDropzone();
	          backlog.hideBlank();
	        }
	      }
	    }
	  }, {
	    key: "onDragEnd",
	    value: function onDragEnd(baseEvent) {
	      var _this4 = this;

	      var dragEndEvent = baseEvent.getData();
	      var itemNode = dragEndEvent.source;
	      var itemId = parseInt(itemNode.dataset.id, 10);
	      var item = this.entityStorage.findItemByItemId(itemId);

	      if (!item) {
	        return;
	      }

	      var isMultipleDrag = this.dragItems.size > 0;

	      if (isMultipleDrag) {
	        this.removeMultipleMode(item, this.dragItems);
	      }

	      this.planBuilder.unblockScrumContainerSelect();
	      var sourceContainer = dragEndEvent.sourceContainer;
	      var endContainer = dragEndEvent.endContainer;

	      if (!endContainer) {
	        if (this.isDropToZone) {
	          this.isDropToZone = false;
	        } else {
	          endContainer = this.lastOutContainer;
	        }
	      }

	      this.lastOutContainer = null;

	      if (!endContainer) {
	        baseEvent.preventDefault();
	        return;
	      }

	      var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	      var endEntityId = parseInt(endContainer.dataset.entityId, 10);
	      var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
	      var endEntity = this.entityStorage.findEntityByEntityId(endEntityId);

	      if (sourceEntity && endEntity) {
	        this.onItemMove(item, sourceEntity, endEntity).then(function () {
	          var isMultipleDrag = _this4.dragItems.size > 0;

	          if (isMultipleDrag) {
	            _this4.dragGroupItems(item, _this4.dragItems, sourceEntity, endEntity);
	          }
	        });
	        sourceEntity.adjustListItemsWidth();
	        endEntity.adjustListItemsWidth();
	      }

	      if (sourceEntity && !sourceEntity.isBacklog()) {
	        var backlog = this.entityStorage.getBacklog();

	        if (backlog.isEmpty()) {
	          backlog.showDropzone();
	        }
	      }

	      this.planBuilder.adjustSprintListWidth();
	    }
	  }, {
	    key: "onDropEnd",
	    value: function onDropEnd(baseEvent) {
	      var _this5 = this;

	      var dragDropEvent = baseEvent.getData();
	      var dropzone = dragDropEvent.dropzone;
	      var sourceContainer = dragDropEvent.sourceContainer;
	      var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	      var endEntityId = parseInt(dropzone.dataset.entityId, 10);
	      var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
	      var endEntity = this.entityStorage.findEntityByEntityId(endEntityId);
	      var itemNode = dragDropEvent.source;
	      var itemId = parseInt(itemNode.dataset.id, 10);
	      var item = this.entityStorage.findItemByItemId(itemId);

	      if (this.planBuilder.isSprintDropzone(dropzone)) {
	        this.planBuilder.createSprint().then(function (sprint) {
	          _this5.addSprintContainers(sprint);

	          _this5.moveTo(sourceEntity, sprint, item);

	          var isMultipleDrag = _this5.dragItems.size > 0;

	          if (isMultipleDrag) {
	            _this5.dragGroupItems(item, _this5.dragItems, sourceEntity, sprint);
	          }
	        });
	      } else {
	        if (sourceEntity && endEntity) {
	          var _itemNode = dragDropEvent.source;

	          var _itemId = parseInt(_itemNode.dataset.id, 10);

	          var _item = this.entityStorage.findItemByItemId(_itemId);

	          if (_item) {
	            this.onItemMove(_item, sourceEntity, endEntity, true).then(function () {
	              var isMultipleDrag = _this5.dragItems.size > 0;

	              if (isMultipleDrag) {
	                _this5.dragGroupItems(_item, _this5.dragItems, sourceEntity, endEntity);
	              }

	              sourceEntity.adjustListItemsWidth();
	              endEntity.adjustListItemsWidth();

	              _this5.planBuilder.adjustSprintListWidth();
	            });
	          }
	        }
	      }

	      if (!sourceEntity.isBacklog()) {
	        var backlog = this.entityStorage.getBacklog();

	        if (backlog.isEmpty()) {
	          backlog.showDropzone();
	        }
	      }
	    }
	  }, {
	    key: "onDropZoneEnter",
	    value: function onDropZoneEnter() {
	      this.isDropToZone = true;
	    }
	  }, {
	    key: "onDropZoneOut",
	    value: function onDropZoneOut() {
	      this.isDropToZone = false;
	    }
	  }, {
	    key: "onDragContainerEnter",
	    value: function onDragContainerEnter(baseEvent) {
	      var dragEnterContainerEvent = baseEvent.getData();
	      var sourceContainer = dragEnterContainerEvent.sourceContainer;
	      var enterContainer = dragEnterContainerEvent.enter;
	      var dropPreview = this.draggableItems.getDropPreview();
	      var width = sourceContainer.isEqualNode(enterContainer) ? parseInt(dropPreview.style.width, 10) : enterContainer.clientWidth;
	      main_core.Dom.style(dropPreview, {
	        width: "".concat(width, "px"),
	        top: "".concat(parseInt(dropPreview.style.top, 10) + enterContainer.scrollTop, "px")
	      });
	    }
	  }, {
	    key: "onDragContainerOut",
	    value: function onDragContainerOut(baseEvent) {
	      var dragOutContainerEvent = baseEvent.getData();
	      this.lastOutContainer = dragOutContainerEvent.out;
	    }
	  }, {
	    key: "onDragOut",
	    value: function onDragOut(baseEvent) {
	      var dragOutEvent = baseEvent.getData();

	      if (main_core.Type.isUndefined(dragOutEvent.outContainer)) {
	        return;
	      }

	      var dropPreview = this.draggableItems.getDropPreview();
	      main_core.Dom.style(dropPreview, {
	        width: "".concat(dragOutEvent.out.offsetWidth, "px"),
	        height: "".concat(dragOutEvent.out.offsetHeight, "px"),
	        top: "".concat(parseInt(dropPreview.style.top, 10) + dragOutEvent.outContainer.scrollTop, "px")
	      });
	    }
	  }, {
	    key: "onItemMove",
	    value: function onItemMove(item, sourceEntity, endEntity, insertDom) {
	      var _this6 = this;

	      if (sourceEntity.getId() === endEntity.getId()) {
	        return this.moveInCurrentContainer(new Set([item.getId()]), sourceEntity);
	      } else {
	        var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');
	        return this.onMoveConfirm(sourceEntity, message).then(function () {
	          if (insertDom) {
	            main_core.Dom.insertBefore(item.getNode(), endEntity.getLoaderNode());
	          }

	          _this6.moveItemFromEntityToEntity(item, sourceEntity, endEntity);

	          _this6.moveInAnotherContainer(new Set([item.getId()]), sourceEntity, endEntity);
	        }).catch(function () {
	          main_core.Dom.insertBefore(item.getNode(), sourceEntity.getListItemsNode().children[item.getSort() - 1]);
	        });
	      }
	    }
	  }, {
	    key: "onMoveItemUpdate",
	    value: function onMoveItemUpdate(entityFrom, entityTo, item) {
	      var _this7 = this;

	      this.requestSender.updateItemSort({
	        entityId: entityTo.getId(),
	        itemIds: [item.getId()],
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(entityTo.getListItemsNode(), new Set([item.getId()]), true), this.calculateSort(entityFrom.getListItemsNode(), new Set(), true))
	      }).then(function () {
	        _this7.updateEntityCounters(entityFrom, entityTo);
	      }).catch(function (response) {
	        _this7.requestSender.showErrorAlert(response);
	      });
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
	    key: "dragGroupItems",
	    value: function dragGroupItems(dragItem, dragItems, entityFrom, entityTo) {
	      var _this8 = this;

	      var isMoveInCurrentContainer = entityFrom.getId() === entityTo.getId();
	      var sortedDragItems = babelHelpers.toConsumableArray(dragItems.values()).sort(function (first, second) {
	        if (first.getSort() < second.getSort()) return 1;
	        if (first.getSort() > second.getSort()) return -1;
	      });
	      var dragItemIds = new Set();
	      sortedDragItems.forEach(function (groupedItem) {
	        dragItemIds.add(groupedItem.getId());
	        entityTo.appendNodeAfterItem(groupedItem.getNode(), dragItem.getNode());

	        if (!isMoveInCurrentContainer) {
	          _this8.moveItemFromEntityToEntity(groupedItem, entityFrom, entityTo);
	        }
	      });

	      if (isMoveInCurrentContainer) {
	        this.moveInCurrentContainer(dragItemIds, entityFrom);
	      } else {
	        this.moveInAnotherContainer(dragItemIds, entityFrom, entityTo);
	      }
	    }
	  }, {
	    key: "addMultipleMode",
	    value: function addMultipleMode(item, dragItems) {
	      main_core.Dom.addClass(item.getNode(), dragItems.size > 1 ? '--multiple-drag-many' : '--multiple-drag');
	      dragItems.forEach(function (dragItem) {
	        main_core.Dom.addClass(dragItem.getNode(), '--multiple-drag-shadow');
	      });
	    }
	  }, {
	    key: "removeMultipleMode",
	    value: function removeMultipleMode(item, dragItems) {
	      main_core.Dom.removeClass(item.getNode(), '--multiple-drag');
	      main_core.Dom.removeClass(item.getNode(), '--multiple-drag-many');
	      dragItems.forEach(function (dragItem) {
	        main_core.Dom.removeClass(dragItem.getNode(), '--multiple-drag-shadow');
	      });
	    }
	  }, {
	    key: "addSprintContainers",
	    value: function addSprintContainers(sprint) {
	      if (!sprint.isDisabled()) {
	        this.draggableItems.addContainer(sprint.getListItemsNode());
	        this.draggableItems.addDropzone(sprint.getDropzone());
	      }
	    }
	  }, {
	    key: "moveItem",
	    value: function moveItem(item, button) {
	      var _this9 = this;

	      var entity = this.entityStorage.findEntityByItemId(item.getId());
	      var listToMove = [];

	      if (!entity.isFirstItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
	          onclick: function onclick(event, menuItem) {
	            var groupModeItems = entity.getGroupModeItems();
	            var sortedItems = babelHelpers.toConsumableArray(groupModeItems.values()).sort(function (first, second) {
	              if (first.getSort() < second.getSort()) return 1;
	              if (first.getSort() > second.getSort()) return -1;
	            });
	            var sortedItemsIds = new Set();
	            sortedItems.forEach(function (groupModeItem) {
	              sortedItemsIds.add(groupModeItem.getId());

	              if (groupModeItem.isParentTask() && groupModeItem.isShownSubTasks()) {
	                groupModeItem.hideSubTasks();
	              }

	              _this9.moveItemToUp(groupModeItem, entity.getListItemsNode(), false);

	              groupModeItem.activateBlinking();
	            });

	            _this9.scroller.scrollToItem(sortedItems.values().next().value);

	            _this9.requestSender.updateItemSort({
	              sortInfo: _this9.calculateSort(entity.getListItemsNode(), sortedItemsIds)
	            }).catch(function (response) {
	              _this9.requestSender.showErrorAlert(response);
	            });

	            entity.deactivateGroupMode();
	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      if (!entity.isLastItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
	          onclick: function onclick(event, menuItem) {
	            var groupModeItems = entity.getGroupModeItems();
	            var sortedItems = babelHelpers.toConsumableArray(groupModeItems.values()).sort(function (first, second) {
	              if (first.getSort() > second.getSort()) return 1;
	              if (first.getSort() < second.getSort()) return -1;
	            });
	            var sortedItemsIds = new Set();
	            sortedItems.forEach(function (groupModeItem) {
	              sortedItemsIds.add(groupModeItem.getId());

	              if (groupModeItem.isParentTask() && groupModeItem.isShownSubTasks()) {
	                groupModeItem.hideSubTasks();
	              }

	              _this9.moveItemToDown(groupModeItem, entity.getListItemsNode(), false);

	              groupModeItem.activateBlinking();
	            });

	            _this9.scroller.scrollToItem(sortedItems.values().next().value);

	            _this9.requestSender.updateItemSort({
	              sortInfo: _this9.calculateSort(entity.getListItemsNode(), sortedItemsIds)
	            }).catch(function (response) {
	              _this9.requestSender.showErrorAlert(response);
	            });

	            entity.deactivateGroupMode();
	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      this.showMoveItemMenu(item, button, listToMove);
	    }
	  }, {
	    key: "moveItemToUp",
	    value: function moveItemToUp(item, listItemsNode) {
	      var updateSort = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      main_core.Dom.insertBefore(item.getNode(), listItemsNode.firstElementChild);

	      if (updateSort) {
	        this.updateItemsSort(item, listItemsNode);
	      }
	    }
	  }, {
	    key: "moveItemToDown",
	    value: function moveItemToDown(item, listItemsNode) {
	      var updateSort = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      main_core.Dom.insertBefore(item.getNode(), listItemsNode.lastElementChild);

	      if (updateSort) {
	        this.updateItemsSort(item, listItemsNode);
	      }
	    }
	  }, {
	    key: "updateItemsSort",
	    value: function updateItemsSort(item, listItemsNode) {
	      var _this10 = this;

	      this.requestSender.updateItemSort({
	        itemIds: [item.getId()],
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(listItemsNode, new Set([item.getId()])))
	      }).catch(function (response) {
	        _this10.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "moveInCurrentContainer",
	    value: function moveInCurrentContainer(itemIds, entity) {
	      var _this11 = this;

	      return this.requestSender.updateItemSort({
	        sortInfo: this.calculateSort(entity.getListItemsNode(), itemIds)
	      }).catch(function (response) {
	        _this11.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "moveInAnotherContainer",
	    value: function moveInAnotherContainer(itemIds, sourceEntity, endEntity) {
	      var _this12 = this;

	      this.requestSender.updateItemSort({
	        entityId: endEntity.getId(),
	        itemIds: Array.from(itemIds),
	        sortInfo: this.calculateSort(endEntity.getListItemsNode(), itemIds, true)
	      }).then(function () {
	        return _this12.updateEntityCounters(sourceEntity, endEntity);
	      }).catch(function (response) {
	        return _this12.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "updateEntityCounters",
	    value: function updateEntityCounters(sourceEntity, endEntity) {
	      var entities = new Map();
	      entities.set(sourceEntity.getId(), sourceEntity);

	      if (endEntity) {
	        entities.set(endEntity.getId(), endEntity);
	      }

	      this.entityCounters.updateCounters(entities);
	    }
	  }, {
	    key: "calculateSort",
	    value: function calculateSort(container, updatedItemsIds) {
	      var _this13 = this;

	      var moveToAnotherEntity = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      var listSortInfo = {};
	      var items = babelHelpers.toConsumableArray(container.querySelectorAll('[data-sort]'));
	      var sort = 1;
	      items.forEach(function (itemNode) {
	        var itemId = parseInt(itemNode.dataset.id, 10);

	        var item = _this13.entityStorage.findItemByItemId(itemId);

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
	          sort++;
	        }
	      });
	      this.emit('calculateSort', listSortInfo);
	      return listSortInfo;
	    }
	  }, {
	    key: "resortItems",
	    value: function resortItems(entity) {
	      var _this14 = this;

	      var sort = 1;
	      babelHelpers.toConsumableArray(entity.getListItemsNode().querySelectorAll('[data-sort]')).forEach(function (itemNode) {
	        var itemId = parseInt(itemNode.dataset.id, 10);

	        var item = _this14.entityStorage.findItemByItemId(itemId);

	        if (item && !item.isSubTask()) {
	          item.setSort(sort);
	          sort++;
	        }
	      });
	    }
	  }, {
	    key: "moveToAnotherEntity",
	    value: function moveToAnotherEntity(entityFrom, item, targetEntity, bindButton) {
	      var _this15 = this;

	      var isMoveToSprint = main_core.Type.isNull(targetEntity);
	      var sprints = isMoveToSprint ? this.entityStorage.getSprintsAvailableForFilling(entityFrom) : null;

	      if (isMoveToSprint) {
	        if (sprints.size > 1) {
	          this.showListSprintsToMove(entityFrom, item, bindButton);
	        } else {
	          if (sprints.size === 0) {
	            this.planBuilder.createSprint().then(function (sprint) {
	              _this15.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	            });
	          } else {
	            sprints.forEach(function (sprint) {
	              _this15.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	            });
	          }
	        }
	      } else {
	        var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');
	        this.onMoveConfirm(entityFrom, message).then(function () {
	          _this15.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
	        }).catch(function () {});
	      }
	    }
	  }, {
	    key: "moveToWithGroupMode",
	    value: function moveToWithGroupMode(entityFrom, entityTo, item) {
	      var _this16 = this;

	      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      var update = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;
	      var groupModeItems = entityFrom.getGroupModeItems();

	      if (!groupModeItems.has(item.getId())) {
	        groupModeItems.set(item.getId(), item);
	      }

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
	      sortedItems.forEach(function (groupModeItem) {
	        _this16.moveTo(entityFrom, entityTo, groupModeItem, after, update);

	        sortedItemsIds.add(groupModeItem.getId());
	        groupModeItem.activateBlinking();
	      });
	      this.scroller.scrollToItem(sortedItems.values().next().value);
	      this.requestSender.updateItemSort({
	        entityId: entityTo.getId(),
	        itemIds: Array.from(sortedItemsIds),
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(entityTo.getListItemsNode(), sortedItemsIds, true), this.calculateSort(entityFrom.getListItemsNode(), new Set(), true))
	      }).then(function () {
	        _this16.updateEntityCounters(entityFrom, entityTo);
	      }).catch(function (response) {
	        _this16.requestSender.showErrorAlert(response);
	      });
	      entityFrom.deactivateGroupMode();
	      entityTo.deactivateGroupMode();
	    }
	  }, {
	    key: "moveTo",
	    value: function moveTo(entityFrom, entityTo, item) {
	      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      var update = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;
	      var itemNode = item.getNode();
	      var entityListNode = entityTo.getListItemsNode();

	      if (item.isParentTask() && item.isShownSubTasks()) {
	        item.hideSubTasks();
	      }

	      if (after) {
	        main_core.Dom.insertBefore(itemNode, entityListNode.lastElementChild);
	      } else {
	        main_core.Dom.insertBefore(itemNode, entityListNode.firstElementChild);
	      }

	      this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

	      if (update) {
	        this.onMoveItemUpdate(entityFrom, entityTo, item);
	      }
	    }
	  }, {
	    key: "moveToPosition",
	    value: function moveToPosition(entityFrom, entityTo, item) {
	      var isMoveFromAnotherEntity = entityFrom.getId() !== entityTo.getId();
	      var itemNode = item.getNode() ? item.getNode() : item.render();
	      var itemSort = item.getSort();
	      var itemPreviousSortSort = item.getPreviousSort();
	      var entityListNode = entityTo.getListItemsNode();
	      var bindItemNode = entityListNode.children[itemSort - 1];

	      if (main_core.Dom.hasClass(bindItemNode, 'tasks-scrum__item')) {
	        var bindItemSort = parseInt(bindItemNode.dataset.sort, 10);
	        var bindItem = this.entityStorage.findItemByItemId(parseInt(bindItemNode.dataset.id, 10));

	        if (bindItem.isParentTask() && bindItem.isShownSubTasks()) {
	          bindItem.hideSubTasks();
	        }

	        if (itemPreviousSortSort > 0 && bindItemSort >= itemPreviousSortSort) {
	          if (isMoveFromAnotherEntity) {
	            main_core.Dom.insertBefore(itemNode, bindItemNode);
	          } else {
	            this.planBuilder.appendItemAfterItem(itemNode, bindItemNode);
	          }
	        } else {
	          main_core.Dom.insertBefore(itemNode, bindItemNode);
	        }
	      } else {
	        if (entityTo.isEmpty()) {
	          main_core.Dom.insertBefore(itemNode, entityTo.getLoaderNode());
	        } else {
	          if (entityTo.isBacklog()) {
	            main_core.Dom.insertBefore(itemNode, entityTo.getFirstItemNode());
	          } else {
	            main_core.Dom.insertBefore(itemNode, entityTo.getLoaderNode());
	          }
	        }
	      }

	      this.moveItemFromEntityToEntity(item, entityFrom, entityTo);
	      this.updateEntityCounters(entityFrom, entityTo);

	      if (isMoveFromAnotherEntity) {
	        this.resortItems(entityFrom);
	      }

	      this.resortItems(entityTo);
	    }
	  }, {
	    key: "moveItemFromEntityToEntity",
	    value: function moveItemFromEntityToEntity(item, entityFrom, entityTo) {
	      entityFrom.removeItem(item);
	      item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
	      entityTo.setItem(item);
	    }
	  }, {
	    key: "showListSprintsToMove",
	    value: function showListSprintsToMove(entityFrom, item, button) {
	      var _this17 = this;

	      var id = "item-sprint-action-".concat(entityFrom.getEntityType() + entityFrom.getId() + item.getId());

	      if (this.moveToSprintMenu) {
	        this.moveToSprintMenu.getPopupWindow().destroy();
	      }

	      this.moveToSprintMenu = new main_popup.Menu({
	        id: id,
	        bindElement: button,
	        offsetTop: 12,
	        offsetLeft: -32
	      });
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isCompleted() && !_this17.isSameSprint(entityFrom, sprint)) {
	          _this17.moveToSprintMenu.addMenuItem({
	            text: sprint.getName(),
	            onclick: function onclick(event, menuItem) {
	              var message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

	              if (entityFrom.isGroupMode()) {
	                message = main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');
	              }

	              _this17.onMoveConfirm(entityFrom, message).then(function () {
	                _this17.moveToWithGroupMode(entityFrom, sprint, item, true, false);
	              }).catch(function () {});

	              menuItem.getMenuWindow().close();
	            }
	          });
	        }
	      });
	      this.moveToSprintMenu.getPopupWindow().subscribe('onClose', function () {
	        _this17.moveToSprintMenu.destroy();

	        _this17.moveToSprintMenu = null;

	        _this17.emit('moveToSprintMenuClose');
	      });
	      this.moveToSprintMenu.show();
	    }
	  }, {
	    key: "isSameSprint",
	    value: function isSameSprint(first, second) {
	      return first.getEntityType() === 'sprint' && first.getId() === second.getId();
	    }
	  }, {
	    key: "showMoveItemMenu",
	    value: function showMoveItemMenu(item, button, listToMove) {
	      var _this18 = this;

	      var id = "item-move-".concat(item.getId());

	      if (this.moveItemMenu) {
	        this.moveItemMenu.getPopupWindow().destroy();
	      }

	      this.moveItemMenu = new main_popup.Menu({
	        id: id,
	        bindElement: button,
	        offsetTop: 12,
	        offsetLeft: -28
	      });
	      listToMove.forEach(function (item) {
	        _this18.moveItemMenu.addMenuItem(item);
	      });
	      this.moveItemMenu.getPopupWindow().subscribe('onClose', function () {
	        _this18.moveItemMenu.destroy();

	        _this18.moveItemMenu = null;

	        _this18.emit('moveMenuClose');
	      });
	      this.moveItemMenu.show();
	    }
	  }, {
	    key: "hasActionPanelDialog",
	    value: function hasActionPanelDialog() {
	      return this.moveItemMenu || this.moveToSprintMenu;
	    }
	  }]);
	  return ItemMover;
	}(main_core_events.EventEmitter);

	var ItemDesigner = /*#__PURE__*/function () {
	  function ItemDesigner(params) {
	    babelHelpers.classCallCheck(this, ItemDesigner);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.listAllUsedColors = new Set();
	    this.randomColorCount = 0;
	    this.defaultColor = '#2ECEFF';
	  }

	  babelHelpers.createClass(ItemDesigner, [{
	    key: "getRandomColorForItemBorder",
	    value: function getRandomColorForItemBorder() {
	      var _this = this;

	      this.randomColorCount = 0;
	      return this.getAllUsedColors().then(function () {
	        return _this.getRandomColor(_this.getAllColors());
	      });
	    }
	  }, {
	    key: "getAllColors",
	    value: function getAllColors() {
	      return ['#2ECEFF', '#10E5FC', '#A5DE00', '#EEC202', '#AD8F47', '#FF5B55', '#EF3001', '#F968B6', '#6B52CC', '#07BAB1', '#5CD1DF', '#A1A6AC', '#949DA9', '#01A64C', '#B02FB0', '#EF008B', '#0202FF', '#555555', '#C4C4C4', '#AAAAAA', '#F89675', '#C5E099', '#7ECB9C', '#78CDCA', '#887FC0', '#BD8AC0', '#F6989C', '#F26A47', '#ABD46B', '#00BBB4', '#3FB2CD', '#5471B9', '#3E8BCD', '#A861AB', '#F26A7B', '#9E0402', '#A46200', '#578520', '#01736A', '#0175A6', '#033172', '#460763', '#630260', '#9F0137', '#B7EB81', '#FFA900', '#F7A700', '#333333', '#EDEEF0', '#E1F3F9'];
	    }
	  }, {
	    key: "getRandomColor",
	    value: function getRandomColor(allColors) {
	      this.randomColorCount++;

	      if (this.randomColorCount >= allColors.length) {
	        return this.defaultColor;
	      }

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
	      var _this2 = this;

	      var entityIds = new Set();
	      this.entityStorage.getAllEntities().forEach(function (entity) {
	        if (!entity.isCompleted()) {
	          entityIds.add(entity.getId());
	        }
	      });
	      return this.requestSender.getAllUsedItemBorderColors({
	        entityIds: babelHelpers.toConsumableArray(entityIds.values())
	      }).then(function (response) {
	        _this2.listAllUsedColors = new Set(response.data);
	      }).catch(function (response) {
	        _this2.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "updateBorderColor",
	    value: function updateBorderColor(items) {
	      var _this3 = this;

	      var itemIdsToUpdateColor = new Set();
	      items.forEach(function (item) {
	        if (item.isLinkedTask() && !item.getBorderColor()) {
	          itemIdsToUpdateColor.add(item.getId());
	        }
	      });

	      if (itemIdsToUpdateColor.size) {
	        this.getAllUsedColors().then(function () {
	          var items = new Map();
	          itemIdsToUpdateColor.forEach(function (itemId) {
	            items.set(itemId, _this3.getRandomColor(_this3.getAllColors()));
	          });

	          _this3.requestSender.updateBorderColorToLinkedItems({
	            items: Object.fromEntries(items)
	          }).then(function (response) {
	            var updatedItems = response.data;
	            Object.keys(updatedItems).forEach(function (itemId) {
	              var borderColor = updatedItems[itemId];

	              var item = _this3.entityStorage.findItemByItemId(itemId);

	              item.setBorderColor(borderColor);
	            });
	          }).catch(function (response) {
	            _this3.requestSender.showErrorAlert(response);
	          });
	        });
	      }
	    }
	  }]);
	  return ItemDesigner;
	}();

	var Epic$1 = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Epic, _EventEmitter);

	  function Epic(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Epic);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Epic).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Epic.Helper');

	    _this.groupId = parseInt(params.groupId, 10);
	    _this.entityStorage = params.entityStorage;
	    _this.sidePanel = params.sidePanel;
	    _this.filter = params.filter;
	    _this.tagSearcher = params.tagSearcher;

	    _this.subscribeToExtension();

	    return _this;
	  }

	  babelHelpers.createClass(Epic, [{
	    key: "subscribeToExtension",
	    value: function subscribeToExtension() {
	      var _this2 = this;

	      main_core_events.EventEmitter.subscribe('BX.Tasks.Scrum.Epic:afterAdd', function (baseEvent) {
	        _this2.onAfterAdd(baseEvent);
	      });
	      main_core_events.EventEmitter.subscribe('BX.Tasks.Scrum.Epic:afterEdit', function (baseEvent) {
	        _this2.onAfterEdit(baseEvent);
	      });
	      main_core_events.EventEmitter.subscribe('BX.Tasks.Scrum.Epic:openEdit', function (baseEvent) {
	        _this2.openEditForm(baseEvent.getData());
	      });
	      main_core_events.EventEmitter.subscribe('BX.Tasks.Scrum.Epic:filterByTag', function (baseEvent) {
	        _this2.emit('filterByTag', baseEvent.getData());
	      });
	      main_core_events.EventEmitter.subscribe('BX.Tasks.Scrum.Epic:afterRemove', function (baseEvent) {
	        _this2.onAfterRemove(baseEvent);
	      });
	    }
	  }, {
	    key: "openAddForm",
	    value: function openAddForm() {
	      return this.sidePanel.showByExtension('Epic', {
	        view: 'add',
	        groupId: this.groupId
	      });
	    }
	  }, {
	    key: "openEditForm",
	    value: function openEditForm(epicId) {
	      return this.sidePanel.showByExtension('Epic', {
	        view: 'edit',
	        groupId: this.groupId,
	        epicId: epicId
	      });
	    }
	  }, {
	    key: "openViewForm",
	    value: function openViewForm(epicId) {
	      return this.sidePanel.showByExtension('Epic', {
	        view: 'view',
	        groupId: this.groupId,
	        epicId: epicId
	      });
	    }
	  }, {
	    key: "openList",
	    value: function openList() {
	      return this.sidePanel.showByExtension('Epic', {
	        view: 'list',
	        groupId: this.groupId
	      });
	    }
	  }, {
	    key: "onAfterAdd",
	    value: function onAfterAdd(baseEvent) {
	      var epic = baseEvent.getData();
	      this.tagSearcher.addEpicToSearcher(epic);
	      this.filter.addItemToListTypeField('EPIC', {
	        NAME: epic.name.trim(),
	        VALUE: String(epic.id)
	      });
	    }
	  }, {
	    key: "onAfterEdit",
	    value: function onAfterEdit(baseEvent) {
	      var epic = baseEvent.getData();
	      this.entityStorage.getAllItems().forEach(function (item) {
	        var itemEpic = item.getEpic().getValue();

	        if (itemEpic && itemEpic.id === epic.id) {
	          item.setEpic(epic);
	        }
	      });
	      this.tagSearcher.removeEpicFromSearcher(epic);
	      this.tagSearcher.addEpicToSearcher(epic);
	    }
	  }, {
	    key: "onAfterRemove",
	    value: function onAfterRemove(baseEvent) {
	      var epic = baseEvent.getData();
	      this.entityStorage.getAllItems().forEach(function (item) {
	        var itemEpic = item.getEpic().getValue();

	        if (itemEpic && itemEpic.id === epic.id) {
	          item.setEpic();
	        }
	      });
	      this.tagSearcher.removeEpicFromSearcher(epic);
	    }
	  }]);
	  return Epic;
	}(main_core_events.EventEmitter);

	var PullSprint = /*#__PURE__*/function () {
	  function PullSprint(params) {
	    babelHelpers.classCallCheck(this, PullSprint);
	    this.requestSender = params.requestSender;
	    this.planBuilder = params.planBuilder;
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

	      this.planBuilder.createSprintNode(sprint);
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
	            this.planBuilder.moveSprintToActiveListNode(sprint);
	          }

	          if (tmpSprint.getStatus() === 'completed') {
	            this.planBuilder.moveSprintToCompletedListNode(sprint);
	          }

	          this.planBuilder.updatePlannedSprints(this.entityStorage.getPlannedSprints(), tmpSprint.getStatus() === 'active');
	        }

	        sprint.updateYourself(tmpSprint);
	        this.planBuilder.updateSprintContainers();
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
	        sprint.removeYourself();
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

	var PullItem = /*#__PURE__*/function () {
	  function PullItem(params) {
	    babelHelpers.classCallCheck(this, PullItem);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.entityCounters = params.entityCounters;
	    this.tagSearcher = params.tagSearcher;
	    this.itemMover = params.itemMover;
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

	      var item = Item.buildItem(itemData);
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
	      var item = Item.buildItem(itemData);

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
	        var entity = this.entityStorage.findEntityByItemId(item.getId());
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
	            itemsToSort.set(item.getId(), item);
	            itemsInfoToSort.set(item.getId(), info);
	          }

	          _this2.cleanSkipRemove(info.tmpId);
	        }
	      });
	      itemsToSort.forEach(function (item) {
	        var itemInfoToSort = itemsInfoToSort.get(item.getId());

	        if (item.isParentTask() && item.isShownSubTasks()) {
	          item.hideSubTasks();
	        }

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
	              var tmpItem = Item.buildItem(response.data.itemData);

	              _this3.updateItem(tmpItem, item);
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

	      this.requestSender.hasTaskInFilter({
	        taskId: item.getSourceId()
	      }).then(function (response) {
	        if (!response.data.has) {
	          return;
	        }

	        var entity = _this4.entityStorage.findEntityByEntityId(item.getEntityId());

	        if (!entity) {
	          return;
	        }

	        _this4.itemMover.moveToPosition(entity, entity, item);

	        _this4.entityStorage.recalculateItemsSort();

	        item.getTags().getValue().forEach(function (tag) {
	          _this4.tagSearcher.addTagToSearcher(tag);
	        });
	      }).catch(function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(tmpItem, item) {
	      if (!item) {
	        item = this.entityStorage.findItemByItemId(tmpItem.getId());
	      }

	      if (item) {
	        var isParentChangeAction = tmpItem.isSubTask() !== item.isSubTask();

	        if (isParentChangeAction) {
	          if (tmpItem.isSubTask()) {
	            var entity = this.entityStorage.findEntityByItemId(item.getId());
	            entity.removeItem(item);
	            item.removeYourself();
	          }

	          return;
	        }

	        var targetEntityId = tmpItem.getEntityId();
	        var sourceEntityId = item.getEntityId();
	        var targetEntity = this.entityStorage.findEntityByEntityId(targetEntityId);
	        var sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

	        if (tmpItem.getEntityId() !== item.getEntityId()) {
	          if (targetEntity && sourceEntity) {
	            this.itemMover.moveToPosition(sourceEntity, targetEntity, item);
	            this.entityStorage.recalculateItemsSort();
	          }
	        } else {
	          this.updateEntityCounters(targetEntity);
	        }

	        item.updateYourself(tmpItem);
	      } else {
	        if (tmpItem.isSubTask()) {
	          var _targetEntityId = tmpItem.getEntityId();

	          var _targetEntity = this.entityStorage.findEntityByEntityId(_targetEntityId);

	          this.updateEntityCounters(_targetEntity);
	        } else {
	          this.addItemToEntity(tmpItem);
	        }
	      }
	    }
	  }, {
	    key: "updateEntityCounters",
	    value: function updateEntityCounters(sourceEntity, endEntity) {
	      var entities = new Map();
	      entities.set(sourceEntity.getId(), sourceEntity);

	      if (endEntity) {
	        entities.set(endEntity.getId(), endEntity);
	      }

	      this.entityCounters.updateCounters(entities);
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
	    value: function addTmpIdToSkipSorting(tmpId) {
	      this.listIdsToSkipSorting.add(tmpId);
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
	      return this.listToAddAfterUpdate.has(item.getId());
	    }
	  }, {
	    key: "setDelayedAdd",
	    value: function setDelayedAdd(item) {
	      this.listToAddAfterUpdate.set(item.getId(), item);
	    }
	  }, {
	    key: "cleanDelayedAdd",
	    value: function cleanDelayedAdd(item) {
	      this.listToAddAfterUpdate.delete(item.getId());
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
	      return this.listIdsToSkipUpdating.has(item.getId());
	    }
	  }, {
	    key: "cleanSkipUpdate",
	    value: function cleanSkipUpdate(item) {
	      this.listIdsToSkipUpdating.delete(item.getId());
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
	      this.epic.onAfterAdd(new main_core_events.BaseEvent().setData(epicData));
	    }
	  }, {
	    key: "onEpicUpdated",
	    value: function onEpicUpdated(epicData) {
	      this.epic.onAfterEdit(new main_core_events.BaseEvent().setData(epicData));
	    }
	  }, {
	    key: "onEpicRemoved",
	    value: function onEpicRemoved(epicData) {
	      this.epic.onAfterRemove(new main_core_events.BaseEvent().setData(epicData));
	    }
	  }]);
	  return PullEpic;
	}();

	var PullCounters = /*#__PURE__*/function () {
	  function PullCounters(params) {
	    babelHelpers.classCallCheck(this, PullCounters);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.filterService = params.filterService;
	    this.userId = params.userId;
	    this.groupId = params.groupId;
	  }

	  babelHelpers.createClass(PullCounters, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'tasks';
	    }
	  }, {
	    key: "getMap",
	    value: function getMap() {
	      return {
	        task_view: this.onTaskView.bind(this),
	        scrum_read_all: this.onCommentsReadAll.bind(this)
	      };
	    }
	  }, {
	    key: "onTaskView",
	    value: function onTaskView(data) {
	      var _this = this;

	      var inputTaskId = parseInt(data.TASK_ID, 10);
	      var inputUserId = parseInt(data.USER_ID, 10);

	      if (inputUserId !== this.userId) {
	        return;
	      }

	      var item = this.entityStorage.findItemBySourceId(inputTaskId);

	      if (item) {
	        this.requestSender.getCurrentState({
	          taskId: item.getSourceId()
	        }).then(function (response) {
	          item.updateYourself(Item.buildItem(response.data.itemData));
	        }).catch(function (response) {
	          _this.requestSender.showErrorAlert(response);
	        });
	      }
	    }
	  }, {
	    key: "onCommentsReadAll",
	    value: function onCommentsReadAll(data) {
	      var groupId = data.GROUP_ID;

	      if (groupId && groupId === this.groupId) {
	        this.filterService.applyFilter();
	      }
	    }
	  }]);
	  return PullCounters;
	}();

	var TaskCounters = /*#__PURE__*/function () {
	  function TaskCounters(params) {
	    babelHelpers.classCallCheck(this, TaskCounters);
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.filterService = params.filter;
	    this.isOwnerCurrentUser = params.isOwnerCurrentUser;
	    this.userId = parseInt(params.userId, 10);
	    this.groupId = parseInt(params.groupId, 10);
	    this.filter = this.filterService.getFilterManager();
	    this.updateFields();
	    this.bindEvents();
	    this.subscribeToPull();
	  }

	  babelHelpers.createClass(TaskCounters, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	      main_core_events.EventEmitter.subscribe('Tasks.Toolbar:onItem', this.onCounterClick.bind(this));
	    }
	  }, {
	    key: "subscribeToPull",
	    value: function subscribeToPull() {
	      pull_client.PULL.subscribe(new PullCounters({
	        requestSender: this.requestSender,
	        entityStorage: this.entityStorage,
	        filterService: this.filterService,
	        userId: this.userId,
	        groupId: this.groupId
	      }));
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply() {
	      this.updateFields();
	    }
	  }, {
	    key: "updateFields",
	    value: function updateFields() {
	      this.fields = this.filter.getFilterFieldsValues();
	    }
	  }, {
	    key: "onCounterClick",
	    value: function onCounterClick(baseEvent) {
	      var data = baseEvent.getData();

	      if (data.counter && data.counter.filter) {
	        this.toggleByField(babelHelpers.defineProperty({}, data.counter.filterField, data.counter.filterValue));
	      }
	    }
	  }, {
	    key: "isFilteredByField",
	    value: function isFilteredByField(field) {
	      if (!Object.keys(this.fields).includes(field)) {
	        return false;
	      }

	      if (main_core.Type.isArray(this.fields[field])) {
	        return this.fields[field].length > 0;
	      }

	      return this.fields[field] !== '';
	    }
	  }, {
	    key: "isFilteredByFieldValue",
	    value: function isFilteredByFieldValue(field, value) {
	      return this.isFilteredByField(field) && this.fields[field] === value;
	    }
	  }, {
	    key: "toggleByField",
	    value: function toggleByField(field) {
	      var _this = this;

	      var name = Object.keys(field)[0];
	      var value = field[name];

	      if (!this.isFilteredByFieldValue(name, value)) {
	        this.filter.getApi().extendFilter(babelHelpers.defineProperty({}, name, value));
	        return;
	      }

	      this.filter.getFilterFields().forEach(function (field) {
	        if (field.getAttribute('data-name') === name) {
	          _this.filter.getFields().deleteField(field);
	        }
	      });
	      this.filter.getSearch().apply();
	    }
	  }]);
	  return TaskCounters;
	}();

	var FilterHandler = /*#__PURE__*/function () {
	  function FilterHandler(params) {
	    babelHelpers.classCallCheck(this, FilterHandler);
	    this.filter = params.filter;
	    this.requestSender = params.requestSender;
	    this.entityStorage = params.entityStorage;
	    this.planBuilder = params.planBuilder;
	    this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));
	  }

	  babelHelpers.createClass(FilterHandler, [{
	    key: "onApplyFilter",
	    value: function onApplyFilter(baseEvent) {
	      var _this = this;

	      this.fadeOutAll();
	      var containerPosition = main_core.Dom.getPosition(this.planBuilder.getScrumContainer());
	      var loader = new main_loader.Loader({
	        target: this.planBuilder.getScrumContainer(),
	        offset: {
	          top: "".concat(containerPosition.top / 2, "px")
	        }
	      });
	      loader.show();
	      this.requestSender.applyFilter().then(function (response) {
	        var filteredItemsData = response.data;

	        _this.entityStorage.getAllItems().forEach(function (item) {
	          var entity = _this.entityStorage.findEntityByEntityId(item.getEntityId());

	          if (!entity.isCompleted()) {
	            entity.removeItem(item);
	            item.removeYourself();
	          }
	        });

	        filteredItemsData.forEach(function (itemParams) {
	          var item = Item.buildItem(itemParams);

	          var entity = _this.entityStorage.findEntityByEntityId(item.getEntityId());

	          item.setShortView(entity.getShortView());

	          if (!entity.isCompleted()) {
	            entity.appendItemToList(item);
	            entity.setItem(item);
	          }
	        });

	        _this.fadeInAll();

	        loader.hide();
	      }).catch(function (response) {
	        _this.fadeInAll();

	        loader.hide();

	        _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "fadeOutAll",
	    value: function fadeOutAll() {
	      this.entityStorage.getBacklog().fadeOut();
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isCompleted()) {
	          sprint.fadeOut();
	        }
	      });
	    }
	  }, {
	    key: "fadeInAll",
	    value: function fadeInAll() {
	      this.entityStorage.getBacklog().fadeIn();
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (!sprint.isCompleted()) {
	          sprint.fadeIn();
	        }
	      });
	    }
	  }]);
	  return FilterHandler;
	}();

	var Decomposition = /*#__PURE__*/function () {
	  function Decomposition(params) {
	    babelHelpers.classCallCheck(this, Decomposition);
	    this.parentItem = params.parentItem;
	    this.count = 1;
	  }

	  babelHelpers.createClass(Decomposition, [{
	    key: "getParentItem",
	    value: function getParentItem() {
	      return this.parentItem;
	    }
	  }, {
	    key: "addNumberDecompositionsPerformed",
	    value: function addNumberDecompositionsPerformed() {
	      this.count++;
	    }
	  }, {
	    key: "getNumberDecompositionsPerformed",
	    value: function getNumberDecompositionsPerformed() {
	      return this.count;
	    }
	  }]);
	  return Decomposition;
	}();

	var Plan = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(Plan, _View);

	  function Plan(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Plan);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Plan).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Plan');

	    _this.pathToTask = params.pathToTask;
	    _this.pathToTaskCreate = params.pathToTaskCreate;
	    _this.pathToBurnDown = params.pathToBurnDown;
	    _this.mandatoryExists = params.mandatoryExists === 'Y';
	    _this.defaultResponsible = params.defaultResponsible;
	    _this.pageSize = parseInt(params.pageSize, 10);
	    _this.activeSprintId = parseInt(params.activeSprintId, 10);
	    _this.views = params.views;
	    _this.isShortView = params.isShortView;
	    _this.displayPriority = params.displayPriority;
	    _this.entityStorage = new EntityStorage();

	    _this.entityStorage.addBacklog(Backlog.buildBacklog(params.backlog));

	    params.sprints.forEach(function (sprintData) {
	      sprintData.defaultSprintDuration = params.defaultSprintDuration;
	      sprintData.isShortView = params.isShortView;
	      var sprint = Sprint.buildSprint(sprintData);

	      _this.entityStorage.addSprint(sprint);
	    });
	    _this.entityCounters = new EntityCounters({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage
	    });
	    _this.taskCounters = new TaskCounters({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      filter: _this.filter,
	      userId: params.userId,
	      groupId: params.groupId,
	      isOwnerCurrentUser: params.isOwnerCurrentUser
	    });
	    _this.tagSearcher = new TagSearcher();
	    Object.values(params.tags.epic).forEach(function (epic) {
	      _this.tagSearcher.addEpicToSearcher(epic);
	    });
	    Object.values(params.tags.task).forEach(function (tagName) {
	      _this.tagSearcher.addTagToSearcher(tagName);
	    });
	    _this.planBuilder = new PlanBuilder({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      defaultSprintDuration: params.defaultSprintDuration,
	      pageNumberToCompletedSprints: params.pageNumberToCompletedSprints,
	      displayPriority: params.displayPriority,
	      isShortView: params.isShortView,
	      mandatoryExists: params.mandatoryExists
	    });

	    _this.planBuilder.subscribe('beforeCreateSprint', function (baseEvent) {
	      var requestData = baseEvent.getData();

	      _this.pullSprint.addTmpIdToSkipAdding(requestData.tmpId);
	    });

	    _this.planBuilder.subscribe('createSprint', function (baseEvent) {
	      var sprint = baseEvent.getData();

	      _this.subscribeToSprint(sprint);

	      _this.itemMover.addSprintContainers(sprint);
	    });

	    _this.planBuilder.subscribe('createSprintNode', function (baseEvent) {
	      var sprint = baseEvent.getData();

	      _this.subscribeToSprint(sprint);

	      _this.itemMover.addSprintContainers(sprint);
	    });

	    _this.planBuilder.subscribe('sprintsScroll', _this.onActionPanelScroll.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.searchItems = new SearchItems({
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage
	    });
	    _this.sprintMover = new SprintMover({
	      requestSender: _this.requestSender,
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage
	    });
	    _this.itemMover = new ItemMover({
	      requestSender: _this.requestSender,
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage,
	      entityCounters: _this.entityCounters
	    });
	    _this.itemDesigner = new ItemDesigner({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage
	    });
	    _this.filterHandler = new FilterHandler({
	      filter: _this.filter,
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      planBuilder: _this.planBuilder
	    });
	    _this.epic = new Epic$1({
	      groupId: _this.groupId,
	      sidePanel: _this.sidePanel,
	      entityStorage: _this.entityStorage,
	      filter: _this.filter,
	      tagSearcher: _this.tagSearcher
	    });
	    _this.pullSprint = new PullSprint({
	      requestSender: _this.requestSender,
	      planBuilder: _this.planBuilder,
	      entityStorage: _this.entityStorage,
	      groupId: _this.getCurrentGroupId()
	    });
	    _this.pullItem = new PullItem({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      entityCounters: _this.entityCounters,
	      tagSearcher: _this.tagSearcher,
	      itemMover: _this.itemMover,
	      currentUserId: _this.getCurrentUserId()
	    });
	    _this.pullEpic = new PullEpic({
	      requestSender: _this.requestSender,
	      entityStorage: _this.entityStorage,
	      epic: _this.epic
	    });
	    _this.input = new Input();

	    _this.input.subscribe('createTaskItem', _this.onCreateTaskItem.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('tagsSearchOpen', _this.onTagsSearchOpen.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('tagsSearchClose', _this.onTagsSearchClose.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('epicSearchOpen', _this.onEpicSearchOpen.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('epicSearchClose', _this.onEpicSearchClose.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('render', _this.onRenderInput.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.input.subscribe('remove', _this.onRemoveInput.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.actionPanel = null;

	    _this.bindHandlers();

	    _this.subscribeToPull();

	    return _this;
	  }

	  babelHelpers.createClass(Plan, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Plan.prototype), "renderTo", this).call(this, container);
	      this.planBuilder.renderTo(container);
	    }
	  }, {
	    key: "renderRightElementsTo",
	    value: function renderRightElementsTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Plan.prototype), "renderRightElementsTo", this).call(this, container);
	      var shortView = new ui_shortView.ShortView({
	        isShortView: this.isShortView
	      });
	      shortView.renderTo(container);
	      shortView.subscribe('change', this.onChangeShortView.bind(this));
	    }
	  }, {
	    key: "setDisplayPriority",
	    value: function setDisplayPriority(value) {
	      var _this2 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(Plan.prototype), "setDisplayPriority", this).call(this, value);
	      this.planBuilder.setWidthPriority(value);
	      this.requestSender.saveDisplayPriority({
	        value: value
	      }).then(function (response) {}).catch(function (response) {
	        _this2.requestSender.showErrorAlert(response);
	      });
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
	    value: function bindHandlers() {
	      var _this3 = this;

	      this.entityStorage.getBacklog().subscribe('showInput', this.onShowBacklogInput.bind(this));
	      this.entityStorage.getBacklog().subscribe('openAddTaskForm', this.onOpenAddTaskForm.bind(this));
	      this.entityStorage.getBacklog().subscribe('updateItem', this.onUpdateItem.bind(this));
	      this.entityStorage.getBacklog().subscribe('showTask', this.onShowTask.bind(this));
	      this.entityStorage.getBacklog().subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
	      this.entityStorage.getBacklog().subscribe('openAddEpicForm', this.onOpenEpicForm.bind(this));
	      this.entityStorage.getBacklog().subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
	      this.entityStorage.getBacklog().subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
	      this.entityStorage.getBacklog().subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
	      this.entityStorage.getBacklog().subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
	      this.entityStorage.getBacklog().subscribe('filterByEpic', this.onFilterByEpic.bind(this));
	      this.entityStorage.getBacklog().subscribe('filterByTag', this.onFilterByTag.bind(this));
	      this.entityStorage.getBacklog().subscribe('loadItems', this.onLoadItems.bind(this));
	      this.entityStorage.getBacklog().subscribe('toggleActionPanel', this.onToggleActionPanel.bind(this));
	      this.entityStorage.getBacklog().subscribe('showLinked', this.onShowLinked.bind(this));
	      this.entityStorage.getBacklog().subscribe('itemsScroll', this.onActionPanelScroll.bind(this));
	      this.entityStorage.getBacklog().subscribe('showBlank', this.onShowBlank.bind(this));
	      this.entityStorage.getSprints().forEach(function (sprint) {
	        return _this3.subscribeToSprint(sprint);
	      });
	      this.epic.subscribe('filterByTag', this.onFilterByTag.bind(this));
	      this.itemMover.subscribe('dragStart', function () {
	        _this3.destroyActionPanel();

	        if (_this3.searchItems.isActive()) {
	          _this3.searchItems.stop();
	        }
	      });
	      document.onkeydown = this.onDocumentKeyDown.bind(this);
	      document.onclick = this.onDocumentClick.bind(this);
	    }
	  }, {
	    key: "subscribeToSprint",
	    value: function subscribeToSprint(sprint) {
	      var _this4 = this;

	      sprint.subscribe('showInput', this.onShowSprintInput.bind(this));
	      sprint.subscribe('createSprint', this.onCreateSprint.bind(this));
	      sprint.subscribe('updateItem', this.onUpdateItem.bind(this));
	      sprint.subscribe('getSubTasks', this.onGetSubTasks.bind(this));
	      sprint.subscribe('showTask', this.onShowTask.bind(this));
	      sprint.subscribe('startSprint', this.onStartSprint.bind(this));
	      sprint.subscribe('completeSprint', this.onCompleteSprint.bind(this));
	      sprint.subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
	      sprint.subscribe('removeSprint', this.onRemoveSprint.bind(this));
	      sprint.subscribe('changeSprintName', this.onChangeSprintName.bind(this));
	      sprint.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
	      sprint.subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
	      sprint.subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
	      sprint.subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
	      sprint.subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
	      sprint.subscribe('filterByEpic', this.onFilterByEpic.bind(this));
	      sprint.subscribe('filterByTag', this.onFilterByTag.bind(this));
	      sprint.subscribe('getSprintCompletedItems', this.onGetSprintCompletedItems.bind(this));
	      sprint.subscribe('showSprintBurnDownChart', this.onShowSprintBurnDownChart.bind(this));
	      sprint.subscribe('showSprintCreateMenu', this.onOpenSprintAddMenu.bind(this));
	      sprint.subscribe('loadItems', this.onLoadItems.bind(this));
	      sprint.subscribe('toggleActionPanel', this.onToggleActionPanel.bind(this));
	      sprint.subscribe('showLinked', this.onShowLinked.bind(this));
	      sprint.subscribe('toggleVisibilityContent', function () {
	        _this4.planBuilder.adjustSprintListWidth();
	      });
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(message) {
	      ui_dialogs_messagebox.MessageBox.alert(message, main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));
	    }
	  }, {
	    key: "onDocumentKeyDown",
	    value: function onDocumentKeyDown(event) {
	      event = event || window.event;

	      if (this.searchItems.isActive()) {
	        if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
	          event.preventDefault();
	          this.searchItems.moveToPrev();
	        }

	        if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
	          event.preventDefault();
	          this.searchItems.moveToNext();
	        }
	      }

	      if (event.key === 'Escape') {
	        var prevented = false;

	        if (this.searchItems.isActive()) {
	          prevented = true;
	          this.searchItems.stop();
	        }

	        if (this.actionPanel) {
	          prevented = true;
	          this.destroyActionPanel();
	          this.entityStorage.getAllEntities().forEach(function (entity) {
	            entity.deactivateGroupMode();
	          });
	        }

	        if (prevented) {
	          event.stopImmediatePropagation();
	        }
	      }
	    }
	  }, {
	    key: "onDocumentClick",
	    value: function onDocumentClick(event) {
	      event = event || window.event;

	      if (this.searchItems.isActive() && !this.searchItems.isClickInside(event.target)) {
	        this.searchItems.stop();
	      }
	    }
	  }, {
	    key: "onShowBacklogInput",
	    value: function onShowBacklogInput(baseEvent) {
	      var backlog = baseEvent.getTarget();
	      this.input.setEntity(backlog);
	      this.input.cleanBindNode();
	      this.renderInput();
	    }
	  }, {
	    key: "onShowSprintInput",
	    value: function onShowSprintInput(baseEvent) {
	      var _this5 = this;

	      var sprint = baseEvent.getTarget();

	      var showInput = function showInput() {
	        _this5.input.setEntity(sprint);

	        _this5.input.cleanBindNode();

	        _this5.renderInput();
	      };

	      if (sprint.isHideContent()) {
	        sprint.subscribeOnce('toggleVisibilityContent', showInput.bind(this));
	        sprint.toggleVisibilityContent(sprint.getContentContainer());
	      } else {
	        showInput();
	      }
	    }
	  }, {
	    key: "onOpenAddTaskForm",
	    value: function onOpenAddTaskForm() {
	      this.sidePanel.openSidePanelByUrl(this.pathToTaskCreate.replace('#task_id#', 0));
	    }
	  }, {
	    key: "onCreateSprint",
	    value: function onCreateSprint() {
	      this.planBuilder.createSprint();
	    }
	  }, {
	    key: "onRenderInput",
	    value: function onRenderInput(baseEvent) {
	      var input = baseEvent.getTarget();
	      var entity = input.getEntity();
	      entity.hideBlank();
	      entity.hideDropzone();
	    }
	  }, {
	    key: "onRemoveInput",
	    value: function onRemoveInput(baseEvent) {
	      var input = baseEvent.getTarget();
	      var entity = input.getEntity();

	      if (!input.isTaskCreated() && entity.isEmpty()) {
	        if (entity.isBacklog()) {
	          if (this.entityStorage.existsAtLeastOneItem()) {
	            entity.showDropzone();
	          } else {
	            entity.showBlank();
	          }
	        } else {
	          entity.showDropzone();
	        }
	      }

	      if (this.decomposition) {
	        this.decomposition = null;
	      }

	      entity.adjustListItemsWidth();
	    }
	  }, {
	    key: "onCreateTaskItem",
	    value: function onCreateTaskItem(baseEvent) {
	      var _this6 = this;

	      var input = baseEvent.getTarget();
	      var entity = input.getEntity();
	      var inputValue = baseEvent.getData();
	      var newItem = null;

	      try {
	        newItem = this.createItem(inputValue);
	      } catch (error) {
	        this.showErrorAlert(error.message);
	        input.removeYourself();
	        return;
	      }

	      if (this.decomposition) {
	        var parentItem = this.decomposition.getParentItem();
	        this.pullItem.addIdToSkipUpdating(parentItem.getId());
	        newItem.setParentEntity(entity.getId(), entity.getEntityType());
	        newItem.setParentTaskId(parentItem.getSourceId());
	        newItem.setEpic(parentItem.getEpic().getValue());
	        newItem.setTags(parentItem.getTags().getValue());
	        newItem.setResponsible(parentItem.getResponsible().getValue());

	        if (entity.isBacklog()) {
	          parentItem.setLinkedTask('Y');
	          parentItem.updateBorderColor();
	          newItem.setLinkedTask('Y');
	          newItem.setBorderColor(parentItem.getBorderColor());
	          newItem.setSort(parentItem.getSort() + this.decomposition.getNumberDecompositionsPerformed());
	          main_core.Dom.insertBefore(newItem.render(), input.getNode());
	        } else {
	          newItem.setSubTask('Y');
	          newItem.setSort(parentItem.getSort() + (parentItem.getSubTasksCount() + 1));
	          newItem.setParentTaskId(parentItem.getSourceId());
	          newItem.setParentTask('N');
	        }

	        this.decomposition.addNumberDecompositionsPerformed();
	      } else {
	        newItem.setEpic(input.getEpic());
	        input.setEpic(null);
	        this.fillItemBeforeCreation(entity, newItem, inputValue);

	        if (entity.isEmpty()) {
	          main_core.Dom.insertBefore(newItem.render(), entity.getLoaderNode());
	        } else {
	          main_core.Dom.insertBefore(newItem.render(), entity.getFirstItemNode(this.input));
	        }
	      }

	      this.pullItem.addTmpIdsToSkipAdding(newItem.getId());
	      this.pullItem.addTmpIdToSkipSorting(newItem.getId());
	      this.sendRequestToCreateTask(entity, newItem).then(function (response) {
	        input.unDisable();
	        input.focus();

	        _this6.fillItemAfterCreation(newItem, response.data);

	        response.data.tags.forEach(function (tag) {
	          _this6.tagSearcher.addTagToSearcher(tag);
	        });
	        entity.setItem(newItem);

	        _this6.updateEntityCounters(entity);

	        if (_this6.decomposition) {
	          var _parentItem = _this6.decomposition.getParentItem();

	          if (!entity.isBacklog()) {
	            var subTasks = _parentItem.getSubTasks();

	            subTasks.addTask(newItem);

	            if (!subTasks.isShown()) {
	              entity.appendNodeAfterItem(subTasks.render(), _parentItem.getNode());
	            }

	            var subTaskInfo = {};
	            subTaskInfo[newItem.getSourceId()] = {
	              sourceId: newItem.getSourceId(),
	              completed: 'N',
	              storyPoints: ''
	            };

	            _parentItem.setSubTasksInfo(babelHelpers.objectSpread({}, _parentItem.getSubTasksInfo(), subTaskInfo));

	            _parentItem.setParentTask('Y');

	            _parentItem.showSubTasks();
	          }
	        }

	        entity.adjustListItemsWidth();

	        _this6.planBuilder.adjustSprintListWidth();
	      }).catch(function (response) {
	        _this6.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onUpdateItem",
	    value: function onUpdateItem(baseEvent) {
	      var _this7 = this;

	      var entity = baseEvent.getTarget();
	      var updateData = baseEvent.getData();
	      this.pullItem.addIdToSkipUpdating(updateData.itemId);
	      this.requestSender.updateItem(baseEvent.getData()).then(function () {
	        var isStoryPointsUpdated = !main_core.Type.isUndefined(updateData.storyPoints);

	        if (isStoryPointsUpdated) {
	          _this7.updateEntityCounters(entity);
	        }
	      }).catch(function (response) {
	        _this7.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onGetSubTasks",
	    value: function onGetSubTasks(baseEvent) {
	      var _this8 = this;

	      var sprint = baseEvent.getTarget();
	      var subTasks = baseEvent.getData();
	      this.requestSender.getSubTaskItems({
	        entityId: sprint.getId(),
	        taskId: subTasks.getParentItem().getSourceId()
	      }).then(function (response) {
	        response.data.forEach(function (itemParams) {
	          var subTaskItem = Item.buildItem(itemParams);
	          sprint.setItem(subTaskItem);
	          subTasks.addTask(subTaskItem);
	        });
	        subTasks.show();

	        _this8.planBuilder.adjustSprintListWidth();
	      }).catch(function (response) {
	        _this8.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onChangeTaskResponsible",
	    value: function onChangeTaskResponsible(baseEvent) {
	      var _this9 = this;

	      this.pullItem.addIdToSkipUpdating(baseEvent.getData().getId());
	      this.requestSender.changeTaskResponsible({
	        itemId: baseEvent.getData().getId(),
	        sourceId: baseEvent.getData().getSourceId(),
	        responsible: baseEvent.getData().getResponsible().getValue()
	      }).catch(function (response) {
	        _this9.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onOpenEpicForm",
	    value: function onOpenEpicForm(baseEvent) {
	      var button = baseEvent.getData();
	      main_core.Dom.addClass(button, 'ui-btn-wait');
	      this.epic.openAddForm().then(function () {
	        main_core.Dom.removeClass(button, 'ui-btn-wait');
	      });
	    }
	  }, {
	    key: "onStartSprint",
	    value: function onStartSprint(baseEvent) {
	      var sprint = baseEvent.getTarget();
	      var sprintSidePanel = new SprintSidePanel({
	        sidePanel: this.sidePanel,
	        groupId: this.groupId,
	        views: this.views
	      });
	      sprintSidePanel.showStartForm(sprint);
	    }
	  }, {
	    key: "onCompleteSprint",
	    value: function onCompleteSprint() {
	      var _this10 = this;

	      var sprintSidePanel = new SprintSidePanel({
	        sidePanel: this.sidePanel,
	        groupId: this.groupId,
	        views: this.views
	      });
	      sprintSidePanel.showCompletionForm();
	      sprintSidePanel.subscribe('showTask', function (innerBaseEvent) {
	        _this10.sidePanel.openSidePanelByUrl(_this10.getPathToTask().replace('#task_id#', innerBaseEvent.getData()));
	      });
	    }
	  }, {
	    key: "onRemoveSprint",
	    value: function onRemoveSprint(baseEvent) {
	      var _this11 = this;

	      var sprint = baseEvent.getTarget();
	      this.pullSprint.addIdToSkipRemoving(sprint.getId());
	      this.requestSender.removeSprint({
	        sprintId: sprint.getId(),
	        sortInfo: this.sprintMover.calculateSprintSort()
	      }).then(function (response) {
	        babelHelpers.toConsumableArray(sprint.getItems().values()).map(function (item) {
	          _this11.moveToBacklog(sprint, item);
	        });

	        _this11.destroyActionPanel();

	        sprint.removeYourself();

	        _this11.entityStorage.removeSprint(sprint.getId());

	        _this11.planBuilder.adjustSprintListWidth();
	      }).catch(function (response) {
	        _this11.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onChangeSprintName",
	    value: function onChangeSprintName(baseEvent) {
	      var _this12 = this;

	      var requestData = baseEvent.getData();
	      this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
	      this.requestSender.changeSprintName(requestData).catch(function (response) {
	        _this12.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onChangeSprintDeadline",
	    value: function onChangeSprintDeadline(baseEvent) {
	      var _this13 = this;

	      var requestData = baseEvent.getData();
	      this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
	      this.requestSender.changeSprintDeadline(requestData).catch(function (response) {
	        _this13.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onGetSprintCompletedItems",
	    value: function onGetSprintCompletedItems(baseEvent) {
	      var _this14 = this;

	      var sprint = baseEvent.getTarget();
	      var listItemsNode = sprint.getListItemsNode();
	      var listPosition = main_core.Dom.getPosition(listItemsNode);
	      sprint.getContentContainer().style.height = 'auto'; //todo ppc

	      var loader = new main_loader.Loader({
	        size: 60,
	        mode: 'inline',
	        color: '#eaeaea',
	        offset: {
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      loader.show(); // todo promise wtf

	      this.requestSender.getSprintCompletedItems({
	        sprintId: sprint.getId()
	      }).then(function (response) {
	        loader.hide();

	        if (response.data.length > 0) {
	          response.data.forEach(function (itemParams) {
	            var item = Item.buildItem(itemParams);
	            item.setDisableStatus(sprint.isDisabled());
	            sprint.appendItemToList(item);
	            sprint.setItem(item);
	          });
	        } else {
	          sprint.showBlank();
	        }

	        sprint.showContent(sprint.getContentContainer());
	      }).catch(function (response) {
	        loader.hide();

	        _this14.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onShowSprintBurnDownChart",
	    value: function onShowSprintBurnDownChart(baseEvent) {
	      var sprint = baseEvent.getTarget();
	      var sprintSidePanel = new SprintSidePanel({
	        sidePanel: this.sidePanel,
	        groupId: this.groupId,
	        views: this.views,
	        pathToBurnDown: this.pathToBurnDown
	      });
	      sprintSidePanel.showBurnDownChart(sprint);
	    }
	  }, {
	    key: "onOpenSprintAddMenu",
	    value: function onOpenSprintAddMenu(baseEvent) {
	      var _this15 = this;

	      var entity = baseEvent.getTarget();
	      var button = baseEvent.getData().getNode();

	      if (this.sprintAddMenu) {
	        this.sprintAddMenu.getPopupWindow().destroy();
	        this.sprintAddMenu = null;
	        return;
	      }

	      var buttonRect = button.getBoundingClientRect();
	      this.sprintAddMenu = new main_popup.Menu({
	        id: 'tasks-scrum-sprint-add-menu',
	        bindElement: button,
	        closeByEsc: true,
	        angle: {
	          position: 'top',
	          offset: 78
	        },
	        offsetLeft: buttonRect.width - 67
	      });
	      this.sprintAddMenu.addMenuItem({
	        text: main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_SPRINT_FIRST_ADD'),
	        onclick: function onclick(event, menuItem) {
	          _this15.onShowSprintInput(new main_core_events.BaseEvent().setTarget(entity));

	          menuItem.getMenuWindow().close();
	        }
	      });
	      this.sprintAddMenu.addMenuItem({
	        text: main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_SPRINT_SECOND_ADD'),
	        onclick: function onclick(event, menuItem) {
	          _this15.planBuilder.createSprint();

	          menuItem.getMenuWindow().close();
	        }
	      });
	      this.sprintAddMenu.getPopupWindow().subscribe('onClose', function () {
	        _this15.sprintAddMenu.getPopupWindow().destroy();

	        _this15.sprintAddMenu = null;
	      });
	      this.sprintAddMenu.getPopupWindow().subscribe('onShow', function () {
	        var angle = _this15.sprintAddMenu.getMenuContainer().querySelector('.popup-window-angly');

	        angle.style.pointerEvents = 'none';
	      });
	      this.sprintAddMenu.show();
	    }
	  }, {
	    key: "onShowTask",
	    value: function onShowTask(baseEvent) {
	      var item = baseEvent.getData();
	      this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.getSourceId()));
	    }
	  }, {
	    key: "onTagsSearchOpen",
	    value: function onTagsSearchOpen(baseEvent) {
	      this.tagSearcher.showTagsSearchDialog(this.input, baseEvent.getData());
	    }
	  }, {
	    key: "onTagsSearchClose",
	    value: function onTagsSearchClose() {
	      this.tagSearcher.closeTagsSearchDialog();
	    }
	  }, {
	    key: "onFilterByTag",
	    value: function onFilterByTag(baseEvent) {
	      var tag = baseEvent.getData();
	      var currentValue = this.filter.getValueFromField({
	        name: 'TAG',
	        value: ''
	      });

	      if (String(tag) === String(currentValue)) {
	        this.filter.setValueToField({
	          name: 'TAG',
	          value: ''
	        });
	      } else {
	        this.filter.setValueToField({
	          name: 'TAG',
	          value: String(tag)
	        });
	      }

	      this.filter.scrollToSearchContainer();
	    }
	  }, {
	    key: "onEpicSearchOpen",
	    value: function onEpicSearchOpen(baseEvent) {
	      var _this16 = this;

	      this.tagSearcher.showEpicSearchDialog(this.input, baseEvent.getData());
	      this.tagSearcher.unsubscribeAll('createEpic');
	      this.tagSearcher.subscribe('createEpic', function (baseEvent) {
	        var epicName = baseEvent.getData();

	        _this16.input.disable();

	        _this16.requestSender.createEpic({
	          groupId: _this16.groupId,
	          name: epicName
	        }).then(function (response) {
	          _this16.input.unDisable();

	          _this16.input.getInputNode().focus();

	          var epic = response.data;

	          _this16.input.setEpic(epic);

	          _this16.epic.onAfterAdd(new main_core_events.BaseEvent().setData(epic));
	        }).catch(function (response) {
	          _this16.requestSender.showErrorAlert(response);
	        });
	      });
	    }
	  }, {
	    key: "onEpicSearchClose",
	    value: function onEpicSearchClose() {
	      this.tagSearcher.closeEpicSearchDialog();
	    }
	  }, {
	    key: "onFilterByEpic",
	    value: function onFilterByEpic(baseEvent) {
	      var epicId = baseEvent.getData();
	      var currentValue = this.filter.getValueFromField({
	        name: 'EPIC',
	        value: ''
	      });

	      if (String(epicId) === String(currentValue)) {
	        this.filter.setValueToField({
	          name: 'EPIC',
	          value: ''
	        });
	      } else {
	        this.filter.setValueToField({
	          name: 'EPIC',
	          value: String(epicId)
	        });
	      }

	      this.filter.scrollToSearchContainer();
	    }
	  }, {
	    key: "onLoadItems",
	    value: function onLoadItems(baseEvent) {
	      var _this17 = this;

	      var entity = baseEvent.getTarget();
	      entity.setActiveLoadItems(true);

	      if (entity.getNumberItems() >= this.pageSize) {
	        entity.getListItems().addScrollbar();
	      }

	      var loader = entity.showItemsLoader();
	      var requestData = {
	        entityId: entity.getId(),
	        pageNumber: entity.getPageNumberItems() + 1,
	        pageSize: this.pageSize
	      };
	      this.requestSender.getItems(requestData).then(function (response) {
	        var items = response.data;

	        if (main_core.Type.isArray(items) && items.length) {
	          entity.incrementPageNumberItems();
	          entity.setActiveLoadItems(false);

	          _this17.createItemsInEntity(entity, items);

	          if (entity.isGroupMode()) {
	            entity.activateGroupMode();
	          }

	          entity.bindItemsLoader();
	        }

	        loader.hide();
	      }).catch(function (response) {
	        loader.hide();
	        entity.setActiveLoadItems(false);

	        _this17.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onShowTeamSpeedChart",
	    value: function onShowTeamSpeedChart() {
	      this.sidePanel.showByExtension('Team-Speed-Chart', {
	        groupId: this.groupId
	      });
	    }
	  }, {
	    key: "onOpenListEpicGrid",
	    value: function onOpenListEpicGrid() {
	      this.epic.openList();
	    }
	  }, {
	    key: "onChangeShortView",
	    value: function onChangeShortView(baseEvent) {
	      var _this18 = this;

	      var isShortView = baseEvent.getData();
	      this.planBuilder.setShortView(isShortView);
	      this.destroyActionPanel();
	      var entities = this.entityStorage.getAllEntities();
	      entities.forEach(function (entity) {
	        if (!entity.isCompleted()) {
	          entity.deactivateGroupMode();
	          entity.fadeOut();
	        }
	      });
	      this.requestSender.saveShortView({
	        isShortView: isShortView
	      }).then(function (response) {
	        entities.forEach(function (entity) {
	          if (!entity.isCompleted()) {
	            entity.setShortView(isShortView);
	            entity.fadeIn();
	          }
	        });
	      }).catch(function (response) {
	        entities.forEach(function (entity) {
	          if (!entity.isCompleted()) {
	            entity.fadeIn();
	          }
	        });

	        _this18.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onToggleActionPanel",
	    value: function onToggleActionPanel(baseEvent) {
	      var item = baseEvent.getData();
	      var entity = baseEvent.getTarget();

	      if (this.actionPanel) {
	        var repeatedClick = this.actionPanel.getItem().getId() === item.getId();
	        this.destroyActionPanel();

	        if (repeatedClick || entity.hasItemInGroupMode(item)) {
	          this.deactivateGroupMode(entity, item);
	          return;
	        }
	      }

	      this.activateGroupMode(entity, item);
	      this.showActionPanel(entity, item);

	      if (this.searchItems.isActive()) {
	        this.searchItems.updateCurrentIndexByItem(item);
	      }
	    }
	  }, {
	    key: "onShowLinked",
	    value: function onShowLinked(baseEvent) {
	      var _this19 = this;

	      var item = baseEvent.getData();

	      if (this.searchItems.isActive()) {
	        this.searchItems.stop();
	        return;
	      }

	      this.destroyActionPanel();
	      var containerPosition = main_core.Dom.getPosition(this.planBuilder.getScrumContainer());
	      window.scrollTo({
	        top: containerPosition.top,
	        behavior: 'smooth'
	      });
	      var loader = new main_loader.Loader({
	        target: this.planBuilder.getScrumContainer(),
	        offset: {
	          top: "".concat(containerPosition.top / 2, "px")
	        }
	      });
	      loader.show();
	      this.searchItems.deactivateGroupMode();
	      this.searchItems.fadeOutAll();
	      this.requestSender.showLinkedTasks({
	        taskId: item.getSourceId()
	      }).then(function (response) {
	        var filteredItems = response.data.items;
	        var linkedItemIds = response.data.linkedItemIds;
	        filteredItems.forEach(function (itemParams) {
	          var item = Item.buildItem(itemParams);

	          var entity = _this19.entityStorage.findEntityByEntityId(item.getEntityId());

	          if (!entity.isCompleted() && !entity.hasItem(item)) {
	            item.setShortView(entity.getShortView());
	            entity.appendItemToList(item);
	            entity.setItem(item);
	          }
	        });

	        var allItems = _this19.entityStorage.getAllItems();

	        var items = new Set();
	        linkedItemIds.forEach(function (itemId) {
	          if (allItems.has(itemId)) {
	            items.add(allItems.get(itemId));
	          }
	        });

	        _this19.itemDesigner.updateBorderColor(items);

	        if (linkedItemIds.length > 0) {
	          _this19.searchItems.start(item, linkedItemIds);
	        } else {
	          _this19.searchItems.fadeInAll();
	        }

	        loader.hide();
	      }).catch(function (response) {
	        _this19.requestSender.showErrorAlert(response);

	        loader.hide();
	      });
	    }
	  }, {
	    key: "onActionPanelScroll",
	    value: function onActionPanelScroll() {
	      if (this.actionPanel) {
	        this.actionPanel.calculatePanelTopPosition();
	      }
	    }
	  }, {
	    key: "onShowBlank",
	    value: function onShowBlank(baseEvent) {
	      var _this20 = this;

	      var backlog = baseEvent.getTarget();
	      setTimeout(function () {
	        if (!backlog.isEmpty()) {
	          return;
	        }

	        if (_this20.entityStorage.existsAtLeastOneItem()) {
	          backlog.showDropzone();
	        } else {
	          backlog.showBlank();
	        }
	      }, 200);
	    }
	  }, {
	    key: "renderInput",
	    value: function renderInput() {
	      var entity = this.input.getEntity();
	      var bindNode = this.input.getBindNode();

	      if (bindNode) {
	        main_core.Dom.insertAfter(this.input.render(), bindNode);
	      } else {
	        if (entity.isEmpty()) {
	          main_core.Dom.insertBefore(this.input.render(), entity.getLoaderNode());
	        } else {
	          main_core.Dom.insertBefore(this.input.render(), entity.getFirstItemNode(this.input));
	        }
	      }

	      this.input.getInputNode().focus();
	      this.scrollToInput();
	    }
	  }, {
	    key: "scrollToInput",
	    value: function scrollToInput() {
	      var entity = this.input.getEntity();

	      if (entity.isBacklog()) {
	        var scrollContainer = entity.getListItemsNode();
	        var topPosition = main_core.Dom.getRelativePosition(this.input.getInputNode(), scrollContainer).top;
	        scrollContainer.scrollTo({
	          top: scrollContainer.scrollTop + topPosition - 100,
	          behavior: 'smooth'
	        });
	      } else {
	        var sprintsContainer = this.planBuilder.getSprintsContainer();
	        var _topPosition = main_core.Dom.getRelativePosition(this.input.getInputNode(), sprintsContainer).top;
	        sprintsContainer.scrollTo({
	          top: sprintsContainer.scrollTop + _topPosition - 100,
	          behavior: 'smooth'
	        });
	      }
	    }
	  }, {
	    key: "createItemsInEntity",
	    value: function createItemsInEntity(entity, items) {
	      var _this21 = this;

	      items.forEach(function (itemData) {
	        var item = Item.buildItem(itemData);
	        item.setEntityType(entity.getEntityType());

	        if (!_this21.entityStorage.findItemByItemId(item.getId())) {
	          item.setShortView(entity.getShortView());
	          entity.appendItemToList(item);
	          entity.setItem(item);
	        }
	      });
	    }
	  }, {
	    key: "showActionPanel",
	    value: function showActionPanel(entity, item) {
	      var _this22 = this;

	      var stopSearch = function stopSearch() {
	        if (_this22.searchItems.isActive()) {
	          _this22.searchItems.stop();
	        }
	      };

	      var isMultipleAction = entity.getGroupModeItems().size > 1; //todo maybe will cool move list actions to item scope

	      this.actionPanel = new ActionPanel({
	        entity: entity,
	        item: item,
	        itemList: {
	          task: {
	            activity: true,
	            disable: isMultipleAction,
	            callback: function callback() {
	              _this22.onShowTask(new main_core_events.BaseEvent({
	                data: item
	              }));

	              _this22.destroyActionPanel();

	              entity.deactivateGroupMode();
	            }
	          },
	          attachment: {
	            activity: true,
	            disable: !item.isEditAllowed(),
	            callback: function callback(event) {
	              var diskManager = new DiskManager({
	                ufDiskFilesFieldName: 'UF_TASK_WEBDAV_FILES'
	              });
	              diskManager.subscribe('onFinish', function (baseEvent) {
	                _this22.attachFilesToTask(entity, baseEvent.getData());
	              });
	              diskManager.showAttachmentMenu(event.currentTarget);
	            }
	          },
	          dod: {
	            activity: true,
	            disable: isMultipleAction,
	            callback: function callback() {
	              _this22.showDod(item);

	              _this22.destroyActionPanel();

	              entity.deactivateGroupMode();
	            }
	          },
	          move: {
	            activity: true,
	            disable: !item.isMovable(),
	            callback: function callback(event) {
	              _this22.moveItem(item, event.currentTarget);

	              stopSearch();
	            }
	          },
	          sprint: {
	            activity: true,
	            disable: false,
	            multiple: this.entityStorage.getSprintsAvailableForFilling(entity).size > 1,
	            callback: function callback(event) {
	              _this22.moveToSprint(entity, item, event.currentTarget);

	              stopSearch();
	            }
	          },
	          backlog: {
	            activity: true,
	            disable: item.getEntityType() !== 'sprint',
	            callback: function callback() {
	              _this22.moveToBacklog(entity, item);

	              _this22.destroyActionPanel();

	              stopSearch();
	            }
	          },
	          tags: {
	            activity: true,
	            callback: function callback(event) {
	              _this22.showTagSearcher(entity, item, event.currentTarget);
	            }
	          },
	          epic: {
	            activity: true,
	            callback: function callback(event) {
	              _this22.showEpicSearcher(entity, item, event.currentTarget);
	            }
	          },
	          decomposition: {
	            activity: true,
	            disable: isMultipleAction,
	            callback: function callback() {
	              if (entity.isBacklog()) {
	                _this22.startBacklogDecomposition(entity, item);
	              } else {
	                _this22.startSprintDecomposition(entity, item);
	              }

	              _this22.destroyActionPanel();

	              entity.deactivateGroupMode();
	              stopSearch();
	            }
	          },
	          remove: {
	            activity: true,
	            disable: !item.isRemoveAllowed(),
	            callback: function callback() {
	              ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASKS'), function (messageBox) {
	                messageBox.close();

	                _this22.removeGroupItems(entity).then(function () {
	                  entity.getGroupModeItems().forEach(function (groupModeItem) {
	                    entity.removeItem(groupModeItem);

	                    _this22.deactivateGroupMode(entity, groupModeItem);

	                    groupModeItem.removeYourself();
	                  });

	                  _this22.updateEntityCounters(entity);
	                });
	              }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));

	              _this22.destroyActionPanel();

	              stopSearch();
	            }
	          }
	        }
	      });
	      this.actionPanel.subscribe('unSelect', function () {
	        _this22.destroyActionPanel();

	        entity.deactivateGroupMode();
	      });
	      this.actionPanel.show();
	    }
	  }, {
	    key: "activateGroupMode",
	    value: function activateGroupMode(entity, item) {
	      if (item) {
	        entity.addItemToGroupMode(item);
	      }

	      if (entity.isGroupMode()) {
	        return;
	      }

	      entity.activateGroupMode();

	      if (entity.getId() !== this.entityStorage.getBacklog().getId()) {
	        this.entityStorage.getBacklog().deactivateGroupMode();
	      }

	      this.entityStorage.getSprints().forEach(function (sprint) {
	        if (entity.getId() !== sprint.getId()) {
	          sprint.deactivateGroupMode();
	        }
	      });
	    }
	  }, {
	    key: "deactivateGroupMode",
	    value: function deactivateGroupMode(entity, item) {
	      if (item) {
	        entity.removeItemFromGroupMode(item);
	      }

	      if (!entity.isGroupMode()) {
	        return;
	      }

	      var groupModeItems = entity.getGroupModeItems();

	      if (groupModeItems.size === 0) {
	        entity.deactivateGroupMode();
	      } else {
	        this.showActionPanel(entity, Array.from(groupModeItems.values()).pop());
	      }
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(value) {
	      var valueWithoutTags = value.replace(new RegExp(TagSearcher.tagRegExp, 'g'), '').replace(new RegExp(TagSearcher.epicRegExp, 'g'), '');
	      var item = Item.buildItem({
	        'itemId': '',
	        'name': valueWithoutTags
	      });
	      item.setShortView(this.isShortView);
	      return item;
	    }
	  }, {
	    key: "sendRequestToCreateTask",
	    value: function sendRequestToCreateTask(entity, item) {
	      var requestData = {
	        'tmpId': item.getId(),
	        'name': item.getName().getValue(),
	        'entityId': item.getEntityId(),
	        'entityType': entity.getEntityType(),
	        'epicId': item.getEpic().getValue().id,
	        'sort': item.getSort(),
	        'storyPoints': item.getStoryPoints().getValue().getPoints(),
	        'tags': item.getTags().getValue(),
	        'parentTaskId': item.getParentTaskId(),
	        'responsible': item.getResponsible().getValue(),
	        'info': item.getInfo(),
	        'sortInfo': this.itemMover.calculateSort(entity.getListItemsNode())
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

	      if (tags.length > 0) {
	        item.setTags(tags);
	      }

	      var epicName = TagSearcher.getHashEpicNamesFromText(value).pop();

	      if (epicName) {
	        item.setEpic(this.tagSearcher.getEpicByName(epicName.trim()));
	      }
	    }
	  }, {
	    key: "fillItemAfterCreation",
	    value: function fillItemAfterCreation(item, responseData) {
	      item.setId(responseData.id);
	      item.setEpic(responseData.epic);
	      item.setTags(responseData.tags);
	      item.setResponsible(responseData.responsible);
	      item.setSourceId(responseData.sourceId);
	      item.setAllowedActions(responseData.allowedActions);
	    }
	  }, {
	    key: "openEpicEditForm",
	    value: function openEpicEditForm(epicId) {
	      this.epic.openEditForm(epicId);
	    }
	  }, {
	    key: "updateEntityCounters",
	    value: function updateEntityCounters(sourceEntity, endEntity) {
	      var entities = new Map();
	      entities.set(sourceEntity.getId(), sourceEntity);

	      if (endEntity) {
	        entities.set(endEntity.getId(), endEntity);
	      }

	      this.entityCounters.updateCounters(entities);
	    }
	  }, {
	    key: "destroyActionPanel",
	    value: function destroyActionPanel() {
	      if (this.actionPanel) {
	        this.actionPanel.destroy();
	      }

	      this.actionPanel = null;
	    }
	  }, {
	    key: "getActionPanel",
	    value: function getActionPanel() {
	      return this.actionPanel;
	    }
	  }, {
	    key: "attachFilesToTask",
	    value: function attachFilesToTask(entity, attachedIds) {
	      var _this23 = this;

	      if (attachedIds.length === 0) {
	        return;
	      }

	      var itemIds = [];
	      entity.getGroupModeItems().forEach(function (groupModeItem) {
	        _this23.pullItem.addIdToSkipUpdating(groupModeItem.getId());

	        itemIds.push(groupModeItem.getId());
	      });
	      this.requestSender.attachFilesToTask({
	        itemIds: itemIds,
	        attachedIds: attachedIds
	      }).then(function (response) {
	        entity.getGroupModeItems().forEach(function (groupModeItem) {
	          groupModeItem.setFiles(response.data.attachedFilesCount[groupModeItem.getId()]);
	        });

	        _this23.destroyActionPanel();

	        entity.deactivateGroupMode();
	      }).catch(function (response) {
	        _this23.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "showDod",
	    value: function showDod(item) {
	      this.sidePanel.showByExtension('Dod', {
	        view: 'list',
	        groupId: this.groupId,
	        taskId: item.getSourceId(),
	        skipNotifications: true
	      });
	    }
	  }, {
	    key: "moveItem",
	    value: function moveItem(item, bindButton) {
	      var _this24 = this;

	      this.itemMover.moveItem(item, bindButton);
	      this.itemMover.subscribe('moveMenuClose', function () {
	        var existOpenMenu = _this24.itemMover.hasActionPanelDialog() || _this24.tagSearcher.hasActionPanelDialog();

	        if (!existOpenMenu) {
	          _this24.destroyActionPanel();
	        }
	      });
	    }
	  }, {
	    key: "moveToSprint",
	    value: function moveToSprint(entityFrom, item, bindButton) {
	      var _this25 = this;

	      this.itemMover.moveToAnotherEntity(entityFrom, item, null, bindButton);
	      this.itemMover.subscribe('moveToSprintMenuClose', function () {
	        var existOpenMenu = _this25.itemMover.hasActionPanelDialog() || _this25.tagSearcher.hasActionPanelDialog();

	        if (!existOpenMenu) {
	          _this25.destroyActionPanel();
	        }
	      });

	      if (this.entityStorage.getSprintsAvailableForFilling(entityFrom).size <= 1) {
	        this.destroyActionPanel();
	      }
	    }
	  }, {
	    key: "moveToBacklog",
	    value: function moveToBacklog(sprint, item) {
	      this.itemMover.moveToAnotherEntity(sprint, item, this.entityStorage.getBacklog());
	    }
	  }, {
	    key: "showTagSearcher",
	    value: function showTagSearcher(entity, item, bindButton) {
	      var _this26 = this;

	      this.tagSearcher.showTagsDialog(item, bindButton);
	      this.tagSearcher.unsubscribeAll('attachTagToTask');
	      this.tagSearcher.subscribe('attachTagToTask', function (innerBaseEvent) {
	        var tag = innerBaseEvent.getData();
	        var itemIds = [];
	        entity.getGroupModeItems().forEach(function (groupModeItem) {
	          _this26.pullItem.addIdToSkipUpdating(groupModeItem.getId());

	          itemIds.push(groupModeItem.getId());
	        });

	        _this26.requestSender.updateTaskTags({
	          itemIds: itemIds,
	          tag: tag
	        }).then(function (response) {
	          entity.getGroupModeItems().forEach(function (groupModeItem) {
	            var currentTags = groupModeItem.getTags().getValue();
	            currentTags.push(tag);
	            groupModeItem.setTags(currentTags);
	          });
	        }).catch(function (response) {
	          _this26.requestSender.showErrorAlert(response);
	        });
	      });
	      this.tagSearcher.unsubscribeAll('deAttachTagToTask');
	      this.tagSearcher.subscribe('deAttachTagToTask', function (innerBaseEvent) {
	        var tag = innerBaseEvent.getData();
	        var itemIds = [];
	        entity.getGroupModeItems().forEach(function (groupModeItem) {
	          _this26.pullItem.addIdToSkipUpdating(groupModeItem.getId());

	          itemIds.push(groupModeItem.getId());
	        });

	        _this26.requestSender.removeTaskTags({
	          itemIds: itemIds,
	          tag: tag
	        }).then(function (response) {
	          entity.getGroupModeItems().forEach(function (groupModeItem) {
	            var currentTags = groupModeItem.getTags().getValue();
	            currentTags.splice(currentTags.indexOf(tag), 1);
	            groupModeItem.setTags(currentTags);
	          });
	        }).catch(function (response) {
	          _this26.requestSender.showErrorAlert(response);
	        });
	      });
	      this.tagSearcher.unsubscribeAll('hideTagDialog');
	      this.tagSearcher.subscribe('hideTagDialog', function () {
	        if (!_this26.tagSearcher.isEpicDialogShown()) {
	          _this26.destroyActionPanel();

	          entity.deactivateGroupMode();
	        }
	      });
	    }
	  }, {
	    key: "showEpicSearcher",
	    value: function showEpicSearcher(entity, item, bindButton) {
	      var _this27 = this;

	      this.tagSearcher.showEpicDialog(item, bindButton);
	      this.tagSearcher.unsubscribeAll('updateItemEpic');
	      this.tagSearcher.subscribe('updateItemEpic', function (innerBaseEvent) {
	        var itemIds = [];
	        var epicId = innerBaseEvent.getData();
	        entity.getGroupModeItems().forEach(function (groupModeItem) {
	          groupModeItem.setEpic(_this27.tagSearcher.getEpicById(epicId));
	          itemIds.push(groupModeItem.getId());

	          _this27.pullItem.addIdToSkipUpdating(groupModeItem.getId());
	        });

	        _this27.requestSender.updateItemEpics({
	          itemIds: itemIds,
	          epicId: epicId
	        }).then(function (response) {}).catch(function (response) {
	          _this27.requestSender.showErrorAlert(response);
	        });
	      });
	      this.tagSearcher.unsubscribeAll('hideEpicDialog');
	      this.tagSearcher.subscribe('hideEpicDialog', function () {
	        _this27.destroyActionPanel();

	        entity.deactivateGroupMode();
	      });
	    }
	  }, {
	    key: "removeGroupItems",
	    value: function removeGroupItems(entity) {
	      var _this28 = this;

	      var itemIds = [];
	      entity.getGroupModeItems().forEach(function (groupModeItem) {
	        itemIds.push(groupModeItem.getId());

	        _this28.pullItem.addIdToSkipRemoving(groupModeItem.getId());
	      });
	      return this.requestSender.removeItems({
	        itemIds: itemIds,
	        sortInfo: this.itemMover.calculateSort(entity.getListItemsNode())
	      }).catch(function (response) {
	        _this28.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "startBacklogDecomposition",
	    value: function startBacklogDecomposition(entity, parentItem) {
	      this.decomposition = new Decomposition({
	        parentItem: parentItem
	      });
	      this.input.setEntity(entity);
	      this.input.setBindNode(parentItem.getNode());

	      if (!parentItem.isLinkedTask()) {
	        this.itemDesigner.getRandomColorForItemBorder().then(function (randomColor) {
	          parentItem.setBorderColor(randomColor);
	        });
	      }

	      this.renderInput();
	    }
	  }, {
	    key: "startSprintDecomposition",
	    value: function startSprintDecomposition(entity, parentItem) {
	      var _this29 = this;

	      this.decomposition = new Decomposition({
	        parentItem: parentItem
	      });

	      var renderInputAfterSubTasks = function renderInputAfterSubTasks(subTasks) {
	        if (!subTasks.isShown()) {
	          entity.appendNodeAfterItem(subTasks.render(), parentItem.getNode());
	        }

	        parentItem.showSubTasks();

	        _this29.input.setEntity(entity);

	        _this29.input.setBindNode(subTasks.getNode());

	        _this29.renderInput();
	      };

	      if (parentItem.isParentTask()) {
	        var subTasks = parentItem.getSubTasks();

	        if (subTasks.isEmpty()) {
	          this.requestSender.getSubTaskItems({
	            entityId: entity.getId(),
	            taskId: parentItem.getSourceId()
	          }).then(function (response) {
	            response.data.forEach(function (itemParams) {
	              var subTaskItem = Item.buildItem(itemParams);
	              entity.setItem(subTaskItem);
	              subTasks.addTask(subTaskItem);
	            });
	            renderInputAfterSubTasks(subTasks);
	          }).catch(function (response) {
	            _this29.requestSender.showErrorAlert(response);
	          });
	        } else {
	          renderInputAfterSubTasks(subTasks);
	        }
	      } else {
	        this.input.setEntity(entity);
	        this.input.setBindNode(parentItem.getNode());
	        this.renderInput();
	      }
	    }
	  }]);
	  return Plan;
	}(View);

	var _templateObject$C;
	var ActiveSprintActionButton = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ActiveSprintActionButton, _EventEmitter);

	  function ActiveSprintActionButton() {
	    var _this;

	    babelHelpers.classCallCheck(this, ActiveSprintActionButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActiveSprintActionButton).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.ActiveSprintButton');

	    return _this;
	  }

	  babelHelpers.createClass(ActiveSprintActionButton, [{
	    key: "render",
	    value: function render() {
	      var node = main_core.Tag.render(_templateObject$C || (_templateObject$C = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-primary ui-btn-xs ui-btn-round ui-btn-no-caps\">\n\t\t\t\t<span>\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_ACTIONS_COMPLETE_SPRINT'));
	      main_core.Event.bind(node, 'click', this.onCompleteSprintClick.bind(this));
	      return node;
	    }
	  }, {
	    key: "onCompleteSprintClick",
	    value: function onCompleteSprintClick() {
	      this.emit('completeSprint');
	    }
	  }]);
	  return ActiveSprintActionButton;
	}(main_core_events.EventEmitter);

	var _templateObject$D;
	var RobotButton = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(RobotButton, _EventEmitter);

	  function RobotButton(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, RobotButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(RobotButton).call(this, params));
	    _this.sidePanel = params.sidePanel;
	    _this.isTaskLimitsExceeded = params.isTaskLimitsExceeded;
	    _this.canUseAutomation = params.canUseAutomation;
	    _this.groupId = params.groupId;

	    _this.setEventNamespace('BX.Tasks.Scrum.RobotButton');

	    return _this;
	  }

	  babelHelpers.createClass(RobotButton, [{
	    key: "render",
	    value: function render() {
	      var className = 'tasks-scrum-robot-btn ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round';

	      if (this.isShowLimitSidePanel()) {
	        className += ' ui-btn-icon-lock';
	      }

	      var node = main_core.Tag.render(_templateObject$D || (_templateObject$D = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"", "\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), className, main_core.Loc.getMessage('TASKS_SCRUM_ROBOTS_BUTTON'));
	      main_core.Event.bind(node, 'click', this.onClick.bind(this));
	      return node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.isShowLimitSidePanel()) {
	        BX.UI.InfoHelper.show('limit_tasks_robots', {
	          isLimit: true,
	          limitAnalyticsLabels: {
	            module: 'tasks',
	            source: 'scrumActiveSprint'
	          }
	        });
	      } else {
	        var url = '/bitrix/components/bitrix/tasks.automation/slider.php?site_id=' + main_core.Loc.getMessage('SITE_ID') + '&project_id=' + this.groupId;
	        this.sidePanel.openSidePanel(url, {
	          customLeftBoundary: 0
	        });
	      }
	    }
	  }, {
	    key: "isShowLimitSidePanel",
	    value: function isShowLimitSidePanel() {
	      return this.isTaskLimitsExceeded && !this.canUseAutomation;
	    }
	  }]);
	  return RobotButton;
	}(main_core_events.EventEmitter);

	var ActiveSprint = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(ActiveSprint, _View);

	  function ActiveSprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, ActiveSprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActiveSprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.ActiveSprint');

	    _this.setParams(params);

	    if (_this.existActiveSprint()) {
	      _this.bindHandlers();
	    }

	    _this.sidePanel = new SidePanel();
	    return _this;
	  }

	  babelHelpers.createClass(ActiveSprint, [{
	    key: "renderSprintStatsTo",
	    value: function renderSprintStatsTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(ActiveSprint.prototype), "renderSprintStatsTo", this).call(this, container); // todo ger data for sprint from server

	      if (this.sprint) {
	        this.stats = StatsBuilder.build(this.sprint);
	        this.stats.setKanbanStyle();
	        main_core.Dom.append(this.stats.render(), container);
	      }
	    }
	  }, {
	    key: "renderRightElementsTo",
	    value: function renderRightElementsTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(ActiveSprint.prototype), "renderRightElementsTo", this).call(this, container);

	      if (!this.existActiveSprint()) {
	        return;
	      }

	      var robotButton = new RobotButton({
	        sidePanel: this.sidePanel,
	        groupId: this.getCurrentGroupId(),
	        isTaskLimitsExceeded: this.isTaskLimitsExceeded(),
	        canUseAutomation: this.isCanUseAutomation()
	      });
	      var activeSprintActionButton = new ActiveSprintActionButton();
	      activeSprintActionButton.subscribe('completeSprint', this.onCompleteSprint.bind(this));
	      main_core.Dom.addClass(container, '--without-bg');
	      main_core.Dom.append(robotButton.render(), container);
	      main_core.Dom.append(activeSprintActionButton.render(), container);
	    }
	  }, {
	    key: "setParams",
	    value: function setParams(params) {
	      this.activeSprintId = parseInt(params.activeSprintId, 10);
	      this.setTaskLimitsExceeded(params.taskLimitExceeded);
	      this.setCanUseAutomation(params.canUseAutomation);
	      this.views = params.views;
	    }
	  }, {
	    key: "setTaskLimitsExceeded",
	    value: function setTaskLimitsExceeded(limitExceeded) {
	      this.limitExceeded = limitExceeded === 'Y';
	    }
	  }, {
	    key: "isTaskLimitsExceeded",
	    value: function isTaskLimitsExceeded() {
	      return this.limitExceeded;
	    }
	  }, {
	    key: "setCanUseAutomation",
	    value: function setCanUseAutomation(canUseAutomation) {
	      this.canUseAutomation = canUseAutomation === 'Y';
	    }
	  }, {
	    key: "isCanUseAutomation",
	    value: function isCanUseAutomation() {
	      return this.canUseAutomation;
	    }
	  }, {
	    key: "existActiveSprint",
	    value: function existActiveSprint() {
	      return this.activeSprintId > 0;
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      var _this2 = this;

	      // eslint-disable-next-line
	      var kanbanManager = BX.Tasks.Scrum.Kanban;

	      if (kanbanManager) {
	        this.bindKanbanHandlers(kanbanManager.getKanban());
	        kanbanManager.getKanbansGroupedByParentTasks().forEach(function (kanban) {
	          _this2.bindKanbanHandlers(kanban);
	        });
	      }
	    }
	  }, {
	    key: "bindKanbanHandlers",
	    value: function bindKanbanHandlers(kanban) {
	      var _this3 = this;

	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onItemMoved', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	            _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 3),
	            kanbanItem = _event$getCompatData2[0],
	            targetColumn = _event$getCompatData2[1],
	            beforeItem = _event$getCompatData2[2];

	        _this3.onItemMoved(kanbanItem, targetColumn, beforeItem);
	      });
	    }
	  }, {
	    key: "onCompleteSprint",
	    value: function onCompleteSprint(baseEvent) {
	      var _this4 = this;

	      var sprintSidePanel = new SprintSidePanel({
	        groupId: this.groupId,
	        sidePanel: this.sidePanel,
	        views: this.views
	      });
	      sprintSidePanel.showCompletionForm();
	      sprintSidePanel.subscribe('showTask', function (innerBaseEvent) {
	        _this4.sidePanel.openSidePanelByUrl(_this4.getPathToTask().replace('#task_id#', innerBaseEvent.getData()));
	      });
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
	    value: function onItemMoved(kanbanItem, targetColumn, beforeItem) {// todo update stats
	      //this.stats.setSprintData(this.sprint);
	    }
	  }]);
	  return ActiveSprint;
	}(View);

	var _templateObject$E;
	var BurnDownButton = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(BurnDownButton, _EventEmitter);

	  function BurnDownButton() {
	    var _this;

	    babelHelpers.classCallCheck(this, BurnDownButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BurnDownButton).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.BurnDownButton');

	    return _this;
	  }

	  babelHelpers.createClass(BurnDownButton, [{
	    key: "render",
	    value: function render() {
	      var node = main_core.Tag.render(_templateObject$E || (_templateObject$E = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-primary ui-btn-xs ui-btn-round ui-btn-no-caps\">\n\t\t\t\t<span>\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON'));
	      main_core.Event.bind(node, 'click', this.onClick.bind(this));
	      return node;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      this.emit('click');
	    }
	  }]);
	  return BurnDownButton;
	}(main_core_events.EventEmitter);

	var CompletedSprint = /*#__PURE__*/function (_View) {
	  babelHelpers.inherits(CompletedSprint, _View);

	  function CompletedSprint(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, CompletedSprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CompletedSprint).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.CompletedSprint');

	    _this.setParams(params);

	    _this.bindHandlers();

	    return _this;
	  }

	  babelHelpers.createClass(CompletedSprint, [{
	    key: "renderRightElementsTo",
	    value: function renderRightElementsTo(container) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(CompletedSprint.prototype), "renderRightElementsTo", this).call(this, container);

	      if (this.completedSprint === null) {
	        return;
	      }

	      var burnDownButton = new BurnDownButton();
	      burnDownButton.subscribe('click', this.onShowSprintBurnDownChart.bind(this));
	      main_core.Dom.addClass(container, '--without-bg');
	      main_core.Dom.append(burnDownButton.render(), container);
	    }
	  }, {
	    key: "setParams",
	    value: function setParams(params) {
	      var _this2 = this;

	      if (main_core.Type.isArray(params.completedSprint)) {
	        this.completedSprint = null;
	      } else {
	        this.completedSprint = new Sprint(params.completedSprint);
	      }

	      this.sidePanel = new SidePanel();
	      this.sprints = new Map();
	      params.sprints.forEach(function (sprintData) {
	        var sprint = Sprint.buildSprint(sprintData);

	        _this2.sprints.set(sprint.getId(), sprint);
	      });
	      this.views = params.views;
	      this.pathToBurnDown = params.pathToBurnDown;
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      main_core_events.EventEmitter.subscribe('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
	    }
	  }, {
	    key: "onSprintSelectorChange",
	    value: function onSprintSelectorChange(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          currentSprint = _event$getCompatData2[0];

	      this.completedSprint = this.findSprintBySprintId(currentSprint.sprintId);
	    }
	  }, {
	    key: "onShowSprintBurnDownChart",
	    value: function onShowSprintBurnDownChart(baseEvent) {
	      var sprintSidePanel = new SprintSidePanel({
	        groupId: this.groupId,
	        sidePanel: this.sidePanel,
	        views: this.views,
	        pathToBurnDown: this.pathToBurnDown
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
	    key: "renderTabsTo",
	    value: function renderTabsTo(container) {
	      var view = this.getView();

	      if (view instanceof View) {
	        this.getView().renderTabsTo(container);
	      }
	    }
	  }, {
	    key: "renderSprintStatsTo",
	    value: function renderSprintStatsTo(container) {
	      var view = this.getView();

	      if (view instanceof View) {
	        this.getView().renderSprintStatsTo(container);
	      }
	    }
	  }, {
	    key: "renderRightElementsTo",
	    value: function renderRightElementsTo(container) {
	      var view = this.getView();

	      if (view instanceof View) {
	        this.getView().renderRightElementsTo(container);
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
	    key: "removeEpic",
	    value: function removeEpic(epicId) {
	      var view = this.getView();

	      if (view instanceof Plan) {
	        view.removeEpic(epicId);
	      }
	    }
	  }, {
	    key: "setDisplayPriority",
	    value: function setDisplayPriority(menuItem, value) {
	      if (!main_core.Dom.hasClass(menuItem, 'menu-popup-item-accept')) {
	        this.refreshIcons(menuItem);
	        var view = this.getView();

	        if (view instanceof View) {
	          this.getView().setDisplayPriority(value);
	        }
	      }
	    }
	  }, {
	    key: "refreshIcons",
	    value: function refreshIcons(item) {
	      item.parentElement.childNodes.forEach(function (element) {
	        main_core.Dom.removeClass(element, 'menu-popup-item-accept');
	      });
	      main_core.Dom.addClass(item, 'menu-popup-item-accept');
	    }
	  }]);
	  return Entry;
	}();

	exports.Entry = Entry;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI.ShortView,BX.UI.EntitySelector,BX.Main,BX.UI.Dialogs,BX.UI.DragAndDrop,BX,BX,BX,BX.Event));
//# sourceMappingURL=script.js.map

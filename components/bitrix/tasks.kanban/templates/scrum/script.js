this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,pull_client,main_core_events,main_core) {
	'use strict';

	var PullItem = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PullItem, _EventEmitter);

	  function PullItem() {
	    var _this;

	    babelHelpers.classCallCheck(this, PullItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PullItem).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.KanbanManager.PullItem');

	    return _this;
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
	        itemUpdated: this.onItemUpdated.bind(this)
	      };
	    }
	  }, {
	    key: "onItemUpdated",
	    value: function onItemUpdated(params) {
	      this.emit('itemUpdated', params);
	    }
	  }]);
	  return PullItem;
	}(main_core_events.EventEmitter);

	var KanbanComponent = /*#__PURE__*/function () {
	  function KanbanComponent(params) {
	    babelHelpers.classCallCheck(this, KanbanComponent);
	    this.filterId = params.filterId;
	    this.defaultPresetId = params.defaultPresetId;
	    this.ajaxComponentPath = params.ajaxComponentPath;
	    this.ajaxComponentParams = params.ajaxComponentParams;
	  }

	  babelHelpers.createClass(KanbanComponent, [{
	    key: "onClickSort",
	    value: function onClickSort(item, order) {
	      if (!main_core.Dom.hasClass(item, 'menu-popup-item-accept')) {
	        this.refreshIcons(item);
	        this.saveSelection(order);
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
	  }, {
	    key: "saveSelection",
	    value: function saveSelection(order) {
	      main_core.ajax({
	        method: 'POST',
	        dataType: 'json',
	        url: this.ajaxComponentPath,
	        data: {
	          action: 'setNewTaskOrder',
	          order: order ? order : 'desc',
	          params: this.ajaxComponentParams,
	          sessid: BX.bitrix_sessid()
	        },
	        onsuccess: function onsuccess(data) {
	          BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
	        }
	      });
	    }
	  }]);
	  return KanbanComponent;
	}();

	var _templateObject, _templateObject2;
	var KanbanManager = /*#__PURE__*/function () {
	  function KanbanManager(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, KanbanManager);
	    this.groupId = parseInt(params.groupId, 10);
	    this.filterId = params.filterId;
	    this.siteTemplateId = params.siteTemplateId;
	    this.ajaxComponentPath = params.ajaxComponentPath;
	    this.ajaxComponentParams = params.ajaxComponentParams;
	    this.sprintSelected = params.sprintSelected;
	    this.isActiveSprint = params.isActiveSprint;
	    this.kanbanHeader = null;
	    this.kanban = null;
	    this.kanbanGroupedByParentTasks = new Map();
	    this.parentTasks = params.parentTasks;
	    this.kanbanComponent = new KanbanComponent({
	      filterId: params.filterId,
	      defaultPresetId: params.defaultPresetId,
	      ajaxComponentPath: params.ajaxComponentPath,
	      ajaxComponentParams: params.ajaxComponentParams
	    });
	    this.pullItem = new PullItem({
	      groupId: this.groupId
	    });
	    this.pullItem.subscribe('itemUpdated', this.onItemUpdated.bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', function (event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 5),
	          filterId = _event$getCompatData2[0],
	          values = _event$getCompatData2[1],
	          filterInstance = _event$getCompatData2[2],
	          promise = _event$getCompatData2[3],
	          params = _event$getCompatData2[4];

	      _this.onApplyFilter(filterId, values, filterInstance, promise, params);
	    });
	    main_core_events.EventEmitter.subscribe('onTasksGroupSelectorChange', this.onChangeSprint.bind(this));
	    pull_client.PULL.subscribe(this.pullItem);
	  }

	  babelHelpers.createClass(KanbanManager, [{
	    key: "drawKanban",
	    value: function drawKanban(renderTo, params) {
	      var _this2 = this;

	      if (!this.sprintSelected) {
	        this.showNotSprintMessage(renderTo);
	        return;
	      }

	      this.inputRenderTo = renderTo;
	      this.inputKanbanParams = params;
	      this.drawKanbanHeader(renderTo, params);
	      this.drawKanbanWithoutGrouping(renderTo, params);
	      this.drawKanbanInGroupingMode(renderTo, params);
	      this.fillNeighborKanbans();
	      this.updateHeaderColumns();
	      this.adjustGroupHeadersWidth();
	      main_core_events.EventEmitter.subscribe(this.kanbanHeader, 'Kanban.Grid:onColumnAddedAsync', function () {
	        _this2.adjustGroupHeadersWidth();
	      });
	      main_core_events.EventEmitter.subscribe(this.kanbanHeader, 'Kanban.Grid:onColumnRemovedAsync', function () {
	        setTimeout(function () {
	          _this2.adjustGroupHeadersWidth();
	        }, 200);
	      });
	    }
	  }, {
	    key: "getKanbanHeader",
	    value: function getKanbanHeader() {
	      return this.kanbanHeader;
	    }
	  }, {
	    key: "getKanban",
	    value: function getKanban() {
	      return this.kanban;
	    }
	  }, {
	    key: "getKanbansGroupedByParentTasks",
	    value: function getKanbansGroupedByParentTasks() {
	      return this.kanbanGroupedByParentTasks;
	    }
	  }, {
	    key: "drawKanbanInGroupingMode",
	    value: function drawKanbanInGroupingMode(renderTo, params) {
	      var _this3 = this;

	      main_core.Dom.addClass(renderTo, 'tasks-scrum-kanban');

	      if (this.isParentTaskGrouping()) {
	        var _loop = function _loop(parentTaskId) {
	          if (_this3.parentTasks[parentTaskId]['isVisibilitySubtasks'] === 'N') {
	            delete _this3.parentTasks[parentTaskId];

	            if (!_this3.kanban.getItem(parentTaskId)) {
	              _this3.kanban.refreshTask(parentTaskId);
	            }

	            return "continue";
	          }

	          var kanbanNode = _this3.createParentTaskKanbanNode(_this3.parentTasks[parentTaskId]);

	          var parentTaskCompleted = _this3.parentTasks[parentTaskId]['completed'] === 'Y';

	          if (parentTaskCompleted) {
	            _this3.setTextDecorationToParentTaskName(kanbanNode);
	          }

	          main_core.Dom.append(kanbanNode, renderTo);
	          var tickButtonNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-tick');
	          main_core.Event.bind(tickButtonNode, 'click', function () {
	            _this3.toggleGroupingVisibility(kanbanNode);
	          });
	          var container = kanbanNode.querySelector('.tasks-scrum-kanban-container');

	          _this3.drawKanbanGroupedByParentTasks(_this3.parentTasks[parentTaskId], container, params);
	        };

	        for (var parentTaskId in this.parentTasks) {
	          var _ret = _loop(parentTaskId);

	          if (_ret === "continue") continue;
	        }
	      }
	    }
	  }, {
	    key: "drawKanbanHeader",
	    value: function drawKanbanHeader(renderTo, params) {
	      var headerParams = main_core.Runtime.clone(params);
	      headerParams.kanbanHeader = true;
	      headerParams.items = [];
	      this.kanbanHeader = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, headerParams));
	      this.kanbanHeader.draw();
	    }
	  }, {
	    key: "drawKanbanWithoutGrouping",
	    value: function drawKanbanWithoutGrouping(renderTo, params) {
	      var _this4 = this;

	      this.kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, params));
	      main_core_events.EventEmitter.subscribe(this.kanban, 'Kanban.Grid:onAddParentTask', function (baseEvent) {
	        var parentTask = baseEvent.getData();

	        _this4.createParentTaskKanban(parentTask);

	        _this4.fillNeighborKanbans();

	        _this4.adjustGroupHeadersWidth();

	        _this4.kanban.addItemsFromQueue();
	      });
	      this.kanban.draw();
	    }
	  }, {
	    key: "drawKanbanGroupedByParentTasks",
	    value: function drawKanbanGroupedByParentTasks(parentTask, renderTo, params) {
	      var _this5 = this;

	      var parentTaskId = parseInt(parentTask.id, 10);
	      var headerParams = main_core.Runtime.clone(params);
	      headerParams.columns = parentTask.columns;
	      headerParams.items = parentTask.items;
	      headerParams.parentTaskId = parentTaskId;
	      headerParams.parentTaskName = parentTask.name;
	      headerParams.parentTaskCompleted = parentTask.completed === 'Y';
	      var kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, headerParams));
	      kanban.draw();

	      if (headerParams.parentTaskCompleted && !kanban.hasItemInProgress()) {
	        var container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
	        this.downGroupingVisibility(container);
	      }

	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onCompleteParentTask', function () {
	        _this5.onCompleteParentTask(kanban);
	      });
	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onRenewParentTask', function () {
	        _this5.onRenewParentTask(kanban);
	      });
	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onProceedParentTask', function () {
	        _this5.onProceedParentTask(kanban);
	      });
	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onAddItemInProgress', function (baseEvent) {
	        var container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');

	        _this5.upGroupingVisibility(container);
	      });
	      this.kanbanGroupedByParentTasks.set(parentTaskId, kanban);
	    }
	  }, {
	    key: "fillNeighborKanbans",
	    value: function fillNeighborKanbans() {
	      var _this6 = this;

	      this.addNeighborKanban(this.kanbanHeader);
	      this.addNeighborKanban(this.kanban);
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        _this6.addNeighborKanban(parentTaskKanban);
	      });
	    }
	  }, {
	    key: "cleanNeighborKanbans",
	    value: function cleanNeighborKanbans() {
	      var _this7 = this;

	      this.kanbanHeader.cleanNeighborGrids();
	      this.kanban.cleanNeighborGrids();
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        _this7.removeParentTaskKanban(parentTaskKanban);

	        parentTaskKanban.cleanNeighborGrids();
	      });
	      this.kanbanGroupedByParentTasks.clear();
	    }
	  }, {
	    key: "updateHeaderColumns",
	    value: function updateHeaderColumns() {
	      this.kanbanHeader.updateTotals();
	    }
	  }, {
	    key: "addNeighborKanban",
	    value: function addNeighborKanban(kanban) {
	      this.kanbanHeader.addNeighborGrid(kanban);
	      this.kanban.addNeighborGrid(kanban);
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        parentTaskKanban.addNeighborGrid(kanban);
	      });
	    }
	  }, {
	    key: "getKanbanParams",
	    value: function getKanbanParams(renderTo, params) {
	      return {
	        isGroupingMode: true,
	        gridHeader: params.kanbanHeader,
	        parentTaskId: params.parentTaskId ? params.parentTaskId : 0,
	        parentTaskName: params.parentTaskName ? params.parentTaskName : '',
	        parentTaskCompleted: params.parentTaskCompleted ? params.parentTaskCompleted : false,
	        renderTo: renderTo,
	        itemType: 'BX.Tasks.Kanban.Item',
	        columnType: 'BX.Tasks.Kanban.Column',
	        canAddColumn: this.isActiveSprint,
	        canEditColumn: this.isActiveSprint,
	        canRemoveColumn: this.isActiveSprint,
	        canSortColumn: this.isActiveSprint,
	        canAddItem: !params.addItemInSlider && this.isActiveSprint,
	        canSortItem: this.isActiveSprint,
	        bgColor: this.siteTemplateId,
	        columns: params.columns,
	        items: params.items,
	        addItemTitleText: main_core.Loc.getMessage('KANBAN_QUICK_TASK'),
	        addDraftItemInfo: main_core.Loc.getMessage('KANBAN_QUICK_TASK_ITEM_INFO'),
	        data: {
	          kanbanType: 'K',
	          ajaxHandlerPath: this.ajaxComponentPath,
	          pathToTask: params.pathToTask,
	          pathToTaskCreate: params.pathToTaskCreate,
	          pathToUser: params.pathToUser,
	          addItemInSlider: params.addItemInSlider,
	          params: this.ajaxComponentParams,
	          gridId: this.filterId,
	          newTaskOrder: params.newTaskOrder,
	          setClientDate: params.setClientDate,
	          clientDate: BX.date.format(BX.date.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATE'))),
	          clientTime: BX.date.format(BX.date.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATETIME'))),
	          rights: {
	            canAddColumn: this.isActiveSprint,
	            canEditColumn: this.isActiveSprint,
	            canRemoveColumn: this.isActiveSprint,
	            canSortColumn: this.isActiveSprint,
	            canAddItem: this.isActiveSprint,
	            canSortItem: this.isActiveSprint
	          },
	          admins: params.admins
	        },
	        messages: {
	          ITEM_TITLE_PLACEHOLDER: main_core.Loc.getMessage('KANBAN_ITEM_TITLE_PLACEHOLDER'),
	          COLUMN_TITLE_PLACEHOLDER: main_core.Loc.getMessage('KANBAN_COLUMN_TITLE_PLACEHOLDER')
	        },
	        ownerId: params.ownerId,
	        groupId: params.groupId,
	        isSprintView: 'Y'
	      };
	    }
	  }, {
	    key: "onClickSort",
	    value: function onClickSort(item, order) {
	      this.kanbanComponent.onClickSort(item, order);
	    }
	  }, {
	    key: "onApplyFilter",
	    value: function onApplyFilter(filterId, values, filterInstance, promise, params) {
	      var _this8 = this;

	      this.fadeOutKanbans();
	      this.kanban.ajax({
	        action: 'applyFilter'
	      }, function (data) {
	        _this8.refreshKanban(_this8.kanban, data);

	        _this8.cleanNeighborKanbans();

	        if (_this8.existsTasksGroupedBySubTasks(data)) {
	          Object.entries(data.parentTasks).forEach(function (parentTask) {
	            var _parentTask = babelHelpers.slicedToArray(parentTask, 2),
	                parentTaskData = _parentTask[1];

	            _this8.createParentTaskKanban(parentTaskData);
	          });
	        }

	        _this8.fillNeighborKanbans();

	        _this8.adjustGroupHeadersWidth();

	        _this8.fadeInKanbans();
	      }, function (error) {
	        _this8.fadeInKanbans();
	      });
	    }
	  }, {
	    key: "onChangeSprint",
	    value: function onChangeSprint(baseEvent) {
	      var _this9 = this;

	      var _baseEvent$getCompatD = baseEvent.getCompatData(),
	          _baseEvent$getCompatD2 = babelHelpers.slicedToArray(_baseEvent$getCompatD, 1),
	          currentGroup = _baseEvent$getCompatD2[0];

	      var gridData = this.kanban.getData();
	      gridData.params.SPRINT_ID = currentGroup.sprintId;
	      this.kanban.setData(gridData);
	      this.kanban.ajax({
	        action: 'changeSprint'
	      }, function (data) {
	        _this9.cleanNeighborKanbans();

	        _this9.kanbanHeader.getColumns().forEach(function (column) {
	          return _this9.kanbanHeader.removeColumn(column);
	        });

	        _this9.kanban.getColumns().forEach(function (column) {
	          return _this9.kanban.removeColumn(column);
	        });

	        _this9.refreshKanban(_this9.kanbanHeader, data);

	        _this9.refreshKanban(_this9.kanban, data);

	        if (_this9.existsTasksGroupedBySubTasks(data)) {
	          Object.entries(data.parentTasks).forEach(function (parentTask) {
	            var _parentTask2 = babelHelpers.slicedToArray(parentTask, 2),
	                parentTaskData = _parentTask2[1];

	            _this9.createParentTaskKanban(parentTaskData);
	          });
	        }

	        _this9.fillNeighborKanbans();

	        _this9.adjustGroupHeadersWidth();
	      }, function (error) {});
	    }
	  }, {
	    key: "onItemUpdated",
	    value: function onItemUpdated(baseEvent) {
	      var data = baseEvent.getData();

	      if (this.groupId !== data.groupId) {
	        return;
	      }

	      var taskId = data.sourceId;

	      if (this.kanban.hasItem(taskId)) {
	        this.kanban.refreshTask(taskId);
	      } else {
	        this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	          if (parentTaskKanban.hasItem(taskId)) {
	            parentTaskKanban.refreshTask(taskId);
	          }
	        });
	      }
	    }
	  }, {
	    key: "refreshKanban",
	    value: function refreshKanban(kanban, data) {
	      kanban.resetPaginationPage();
	      kanban.removeItems();
	      kanban.loadData(data);
	    }
	  }, {
	    key: "refreshParentTasksKanbans",
	    value: function refreshParentTasksKanbans(data) {
	      var _this10 = this;

	      var parentTasksToRefresh = [];
	      var parentTasksToCreate = [];
	      Object.entries(data.parentTasks).forEach(function (parentTask) {
	        var _parentTask3 = babelHelpers.slicedToArray(parentTask, 2),
	            parentTaskId = _parentTask3[0],
	            parentTaskData = _parentTask3[1];

	        if (_this10.kanbanGroupedByParentTasks.has(parseInt(parentTaskId, 10))) {
	          parentTasksToRefresh.push(parentTaskData);
	        } else {
	          parentTasksToCreate.push(parentTaskData);
	        }
	      });
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban, parentTaskId) {
	        if (!data.parentTasks[parentTaskId]) {
	          _this10.hideParentTaskKanban(parentTaskKanban);
	        }
	      });
	      parentTasksToRefresh.forEach(function (parentTaskData) {
	        var parentTaskKanban = _this10.kanbanGroupedByParentTasks.get(parseInt(parentTaskData.id, 10));

	        _this10.refreshParentTaskKanban(parentTaskKanban, parentTaskData);
	      });
	      parentTasksToCreate.forEach(function (parentTaskData) {
	        _this10.createParentTaskKanban(parentTaskData);
	      });
	    }
	  }, {
	    key: "refreshParentTaskKanban",
	    value: function refreshParentTaskKanban(kanban, data) {
	      kanban.resetPaginationPage();
	      kanban.removeItems();
	      var container = kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban');
	      this.showElement(container);
	      this.upGroupingVisibility(container);
	      kanban.loadData(data);

	      if (data['completed'] === 'Y') {
	        this.downGroupingVisibility(container);
	      }
	    }
	  }, {
	    key: "createParentTaskKanban",
	    value: function createParentTaskKanban(parentTaskData) {
	      var _this11 = this;

	      if (parentTaskData.isVisibilitySubtasks === 'N') {
	        if (!this.kanban.getItem(parentTaskData.id)) {
	          this.kanban.refreshTask(parentTaskData.id);
	        }

	        return;
	      }

	      this.parentTasks[parentTaskData.id] = parentTaskData;
	      var kanbanNode = this.createParentTaskKanbanNode(parentTaskData);
	      var parentTaskCompleted = parentTaskData['completed'] === 'Y';

	      if (parentTaskCompleted) {
	        this.setTextDecorationToParentTaskName(kanbanNode);
	      }

	      main_core.Dom.append(kanbanNode, this.inputRenderTo);
	      var tickButtonNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-tick');
	      main_core.Event.bind(tickButtonNode, 'click', function () {
	        _this11.toggleGroupingVisibility(kanbanNode);
	      });
	      var container = kanbanNode.querySelector('.tasks-scrum-kanban-container');
	      this.drawKanbanGroupedByParentTasks(parentTaskData, container, this.inputKanbanParams);
	    }
	  }, {
	    key: "hideParentTaskKanban",
	    value: function hideParentTaskKanban(kanban) {
	      kanban.removeItems();
	      var container = kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban');
	      this.hideElement(container);
	    }
	  }, {
	    key: "removeParentTaskKanban",
	    value: function removeParentTaskKanban(kanban) {
	      main_core.Dom.remove(kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban'));
	    }
	  }, {
	    key: "fadeOutKanbans",
	    value: function fadeOutKanbans() {
	      this.kanban.fadeOut();
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        parentTaskKanban.fadeOut();
	      });
	    }
	  }, {
	    key: "fadeInKanbans",
	    value: function fadeInKanbans() {
	      this.kanban.fadeIn();
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        parentTaskKanban.fadeIn();
	      });
	    }
	  }, {
	    key: "showNotSprintMessage",
	    value: function showNotSprintMessage(renderTo) {
	      var message = this.isActiveSprint ? main_core.Loc.getMessage('KANBAN_NO_ACTIVE_SPRINT') : main_core.Loc.getMessage('KANBAN_NO_COMPLETED_SPRINT');
	      main_core.Dom.append(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-kanban__start\">\n\t\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message), renderTo);
	    }
	  }, {
	    key: "isParentTaskGrouping",
	    value: function isParentTaskGrouping() {
	      return !main_core.Type.isArray(this.parentTasks);
	    }
	  }, {
	    key: "existsTasksGroupedBySubTasks",
	    value: function existsTasksGroupedBySubTasks(response) {
	      return !main_core.Type.isArray(response.parentTasks);
	    }
	  }, {
	    key: "showElement",
	    value: function showElement(element) {
	      main_core.Dom.style(element, 'display', 'block');
	    }
	  }, {
	    key: "hideElement",
	    value: function hideElement(element) {
	      main_core.Dom.style(element, 'display', 'none');
	    }
	  }, {
	    key: "createParentTaskKanbanNode",
	    value: function createParentTaskKanbanNode(parentTaskData) {
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-parent-task-kanban\">\n\t\t\t\t<div class=\"tasks-scrum-kanban-group-header\" style=\"background-color: #eaeaea;\">\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-tick\">\n\t\t\t\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-up\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-name\">\n\t\t\t\t\t\t<a href=\"", "\">", "</a>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-story-points\" title=\"Story Points\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-kanban-container\"></div>\n\t\t\t</div>\n\t\t"])), this.getTaskUrl(parentTaskData.id), main_core.Text.encode(parentTaskData.name), main_core.Text.encode(parentTaskData.storyPoints));
	    }
	  }, {
	    key: "adjustGroupHeadersWidth",
	    value: function adjustGroupHeadersWidth() {
	      var _this12 = this;

	      var groupHeaders = this.inputRenderTo.querySelectorAll('.tasks-scrum-kanban-group-header');
	      groupHeaders.forEach(function (groupHeader) {
	        main_core.Dom.style(groupHeader, 'width', _this12.kanbanHeader.getColumnsWidth());
	      });
	    }
	  }, {
	    key: "upGroupingVisibility",
	    value: function upGroupingVisibility(baseContainer) {
	      var tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
	      var container = baseContainer.querySelector('.tasks-scrum-kanban-container');
	      main_core.Dom.removeClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');
	      main_core.Dom.addClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');
	      this.showElement(container);
	    }
	  }, {
	    key: "downGroupingVisibility",
	    value: function downGroupingVisibility(baseContainer) {
	      var tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
	      var container = baseContainer.querySelector('.tasks-scrum-kanban-container');
	      main_core.Dom.removeClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');
	      main_core.Dom.addClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');
	      this.hideElement(container);
	    }
	  }, {
	    key: "toggleGroupingVisibility",
	    value: function toggleGroupingVisibility(baseContainer) {
	      var tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
	      var container = baseContainer.querySelector('.tasks-scrum-kanban-container');
	      main_core.Dom.toggleClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');
	      main_core.Dom.toggleClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');

	      if (main_core.Dom.style(container, 'display') !== 'none') {
	        this.hideElement(container);
	      } else {
	        this.showElement(container);
	      }

	      var gridContainer = baseContainer.querySelector('.main-kanban-grid');
	      gridContainer.scrollLeft = this.kanban.getGridContainer().scrollLeft;
	    }
	  }, {
	    key: "onCompleteParentTask",
	    value: function onCompleteParentTask(kanban) {
	      var container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
	      this.setTextDecorationToParentTaskName(container);
	    }
	  }, {
	    key: "onRenewParentTask",
	    value: function onRenewParentTask(kanban) {
	      var container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
	      this.unsetTextDecorationToParentTaskName(container);
	    }
	  }, {
	    key: "onProceedParentTask",
	    value: function onProceedParentTask(kanban) {
	      if (this.kanbanGroupedByParentTasks.has(kanban.getParentTaskId())) {
	        this.removeParentTaskKanban(kanban);
	        this.kanbanGroupedByParentTasks["delete"](kanban.getParentTaskId());
	      }
	    }
	  }, {
	    key: "setTextDecorationToParentTaskName",
	    value: function setTextDecorationToParentTaskName(kanbanNode) {
	      var parentTaskNameNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-name');
	      main_core.Dom.style(parentTaskNameNode, 'text-decoration', 'line-through');
	    }
	  }, {
	    key: "unsetTextDecorationToParentTaskName",
	    value: function unsetTextDecorationToParentTaskName(kanbanNode) {
	      var parentTaskNameNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-name');
	      main_core.Dom.style(parentTaskNameNode, 'text-decoration', null);
	    }
	  }, {
	    key: "getTaskUrl",
	    value: function getTaskUrl(taskId) {
	      return this.inputKanbanParams.pathToTask.replace('#task_id#', taskId);
	    }
	  }]);
	  return KanbanManager;
	}();

	exports.KanbanManager = KanbanManager;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Event,BX));
//# sourceMappingURL=script.js.map

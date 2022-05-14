this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_core) {
	'use strict';

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
	          _loop(parentTaskId);
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
	      this.kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, params));
	      this.kanban.draw();
	    }
	  }, {
	    key: "drawKanbanGroupedByParentTasks",
	    value: function drawKanbanGroupedByParentTasks(parentTask, renderTo, params) {
	      var _this4 = this;

	      var parentTaskId = parseInt(parentTask.id, 10);
	      var headerParams = main_core.Runtime.clone(params);
	      headerParams.columns = parentTask.columns;
	      headerParams.items = parentTask.items;
	      headerParams.parentTaskId = parentTaskId;
	      headerParams.parentTaskName = parentTask.name;
	      headerParams.parentTaskCompleted = parentTask.completed === 'Y';
	      var kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, headerParams));
	      kanban.draw();

	      if (headerParams.parentTaskCompleted) {
	        var container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
	        this.downGroupingVisibility(container);
	      }

	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onCompleteParentTask', function () {
	        _this4.onCompleteParentTask(kanban);
	      });
	      main_core_events.EventEmitter.subscribe(kanban, 'Kanban.Grid:onRenewParentTask', function () {
	        _this4.onRenewParentTask(kanban);
	      });
	      this.kanbanGroupedByParentTasks.set(parentTaskId, kanban);
	    }
	  }, {
	    key: "fillNeighborKanbans",
	    value: function fillNeighborKanbans() {
	      var _this5 = this;

	      this.addNeighborKanban(this.kanbanHeader);
	      this.addNeighborKanban(this.kanban);
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        _this5.addNeighborKanban(parentTaskKanban);
	      });
	    }
	  }, {
	    key: "cleanNeighborKanbans",
	    value: function cleanNeighborKanbans() {
	      this.kanbanHeader.cleanNeighborGrids();
	      this.kanban.cleanNeighborGrids();
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	        parentTaskKanban.cleanNeighborGrids();
	      });
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
	    key: "onClickGroup",
	    value: function onClickGroup(item, mode) {
	      this.kanbanComponent.onClickGroup(item, mode);
	    }
	  }, {
	    key: "onClickSort",
	    value: function onClickSort(item, order) {
	      this.kanbanComponent.onClickSort(item, order);
	    }
	  }, {
	    key: "onApplyFilter",
	    value: function onApplyFilter(filterId, values, filterInstance, promise, params) {
	      var _this6 = this;

	      this.fadeOutKanbans();
	      this.kanban.ajax({
	        action: 'applyFilter'
	      }, function (data) {
	        _this6.refreshKanban(_this6.kanban, data);

	        if (_this6.existsTasksGroupedBySubTasks(data)) {
	          _this6.refreshParentTasksKanbans(data);
	        } else {
	          _this6.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	            _this6.hideParentTaskKanban(parentTaskKanban);
	          });
	        }

	        _this6.adjustGroupHeadersWidth();

	        _this6.fadeInKanbans();
	      }, function (error) {
	        _this6.fadeInKanbans();
	      });
	    }
	  }, {
	    key: "onChangeSprint",
	    value: function onChangeSprint(baseEvent) {
	      var _this7 = this;

	      var _baseEvent$getCompatD = baseEvent.getCompatData(),
	          _baseEvent$getCompatD2 = babelHelpers.slicedToArray(_baseEvent$getCompatD, 1),
	          currentGroup = _baseEvent$getCompatD2[0];

	      var gridData = this.kanban.getData();
	      gridData.params.SPRINT_ID = currentGroup.sprintId;
	      this.kanban.setData(gridData);
	      this.kanban.ajax({
	        action: 'changeSprint'
	      }, function (data) {
	        _this7.cleanNeighborKanbans();

	        _this7.kanbanHeader.getColumns().forEach(function (column) {
	          return _this7.kanbanHeader.removeColumn(column);
	        });

	        _this7.kanban.getColumns().forEach(function (column) {
	          return _this7.kanban.removeColumn(column);
	        });

	        _this7.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban) {
	          _this7.removeParentTaskKanban(parentTaskKanban);
	        });

	        _this7.kanbanGroupedByParentTasks.clear();

	        _this7.refreshKanban(_this7.kanbanHeader, data);

	        _this7.refreshKanban(_this7.kanban, data);

	        if (_this7.existsTasksGroupedBySubTasks(data)) {
	          Object.entries(data.parentTasks).forEach(function (parentTask) {
	            var _parentTask = babelHelpers.slicedToArray(parentTask, 2),
	                parentTaskData = _parentTask[1];

	            _this7.createParentTaskKanban(parentTaskData);
	          });
	        }

	        _this7.fillNeighborKanbans();

	        _this7.adjustGroupHeadersWidth();
	      }, function (error) {});
	    }
	  }, {
	    key: "refreshKanban",
	    value: function refreshKanban(kanban, data) {
	      kanban.removeItems();
	      kanban.loadData(data);
	    }
	  }, {
	    key: "refreshParentTasksKanbans",
	    value: function refreshParentTasksKanbans(data) {
	      var _this8 = this;

	      var parentTasksToRefresh = [];
	      var parentTasksToCreate = [];
	      Object.entries(data.parentTasks).forEach(function (parentTask) {
	        var _parentTask2 = babelHelpers.slicedToArray(parentTask, 2),
	            parentTaskId = _parentTask2[0],
	            parentTaskData = _parentTask2[1];

	        if (_this8.kanbanGroupedByParentTasks.has(parseInt(parentTaskId, 10))) {
	          parentTasksToRefresh.push(parentTaskData);
	        } else {
	          parentTasksToCreate.push(parentTaskData);
	        }
	      });
	      this.kanbanGroupedByParentTasks.forEach(function (parentTaskKanban, parentTaskId) {
	        if (!data.parentTasks[parentTaskId]) {
	          _this8.hideParentTaskKanban(parentTaskKanban);
	        }
	      });
	      parentTasksToRefresh.forEach(function (parentTaskData) {
	        var parentTaskKanban = _this8.kanbanGroupedByParentTasks.get(parseInt(parentTaskData.id, 10));

	        _this8.refreshParentTaskKanban(parentTaskKanban, parentTaskData);
	      });
	      parentTasksToCreate.forEach(function (parentTaskData) {
	        _this8.createParentTaskKanban(parentTaskData);
	      });
	    }
	  }, {
	    key: "refreshParentTaskKanban",
	    value: function refreshParentTaskKanban(kanban, data) {
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
	      var _this9 = this;

	      this.parentTasks[parentTaskData.id] = parentTaskData;
	      var kanbanNode = this.createParentTaskKanbanNode(parentTaskData);
	      var parentTaskCompleted = parentTaskData['completed'] === 'Y';

	      if (parentTaskCompleted) {
	        this.setTextDecorationToParentTaskName(kanbanNode);
	      }

	      main_core.Dom.append(kanbanNode, this.inputRenderTo);
	      var tickButtonNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-tick');
	      main_core.Event.bind(tickButtonNode, 'click', function () {
	        _this9.toggleGroupingVisibility(kanbanNode);
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
	      element.style.display = 'block';
	    }
	  }, {
	    key: "hideElement",
	    value: function hideElement(element) {
	      element.style.display = 'none';
	    }
	  }, {
	    key: "createParentTaskKanbanNode",
	    value: function createParentTaskKanbanNode(parentTaskData) {
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-parent-task-kanban\">\n\t\t\t\t<div class=\"tasks-scrum-kanban-group-header\" style=\"background-color: #eaeaea;\">\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-tick\">\n\t\t\t\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-up\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-name\">\n\t\t\t\t\t\t<a href=\"", "\">", "</a>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-kanban-group-header-story-points\" title=\"Story Points\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-kanban-container\"></div>\n\t\t\t</div>\n\t\t"])), this.getTaskUrl(parentTaskData.id), main_core.Text.encode(parentTaskData.name), main_core.Text.encode(parentTaskData.storyPoints));
	    }
	  }, {
	    key: "adjustGroupHeadersWidth",
	    value: function adjustGroupHeadersWidth() {
	      var _this10 = this;

	      var groupHeaders = this.inputRenderTo.querySelectorAll('.tasks-scrum-kanban-group-header');
	      groupHeaders.forEach(function (groupHeader) {
	        groupHeader.style.width = _this10.kanbanHeader.getColumnsWidth();
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
	      tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
	      tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-down');

	      if (container.style.display !== 'none') {
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

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX));
//# sourceMappingURL=script.js.map

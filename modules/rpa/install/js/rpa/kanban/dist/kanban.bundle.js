this.BX = this.BX || {};
(function (exports,ui_buttons,ui_notification,main_kanban,rpa_kanban,main_core,rpa_manager,ui_dialogs_messagebox,main_popup,rpa_fieldspopup) {
	'use strict';

	var PullManager = /*#__PURE__*/function () {
	  function PullManager(grid) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, PullManager);
	    this.eventIds = new Set();

	    if (grid instanceof rpa_kanban.Kanban.Grid) {
	      this.grid = grid;

	      if (main_core.Type.isArray(this.grid.getData().eventIds)) {
	        this.grid.getData().eventIds.forEach(function (eventId) {
	          _this.eventIds.add(eventId);
	        });
	      }

	      if (main_core.Type.isString(grid.getData().pullTag) && main_core.Type.isString(grid.getData().moduleId) && grid.getData().userId > 0) {
	        this.init();
	      }
	    }
	  }

	  babelHelpers.createClass(PullManager, [{
	    key: "registerEventId",
	    value: function registerEventId(eventId) {
	      this.eventIds.add(eventId);
	    }
	  }, {
	    key: "registerRandomEventId",
	    value: function registerRandomEventId() {
	      var eventId = main_core.Text.getRandom();
	      this.registerEventId(eventId);
	      return eventId;
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      var _this2 = this;

	      main_core.Event.ready(function () {
	        var Pull = BX.PULL;

	        if (!Pull) {
	          console.error('pull is not initialized');
	          return;
	        }

	        if (main_core.Type.isString(_this2.grid.getData().pullTag)) {
	          Pull.subscribe({
	            moduleId: _this2.grid.getData().moduleId,
	            command: _this2.grid.getData().pullTag,
	            callback: function callback(params) {
	              if (main_core.Type.isString(params.eventName)) {
	                if (main_core.Type.isString(params.eventId)) {
	                  if (_this2.eventIds.has(params.eventId)) {
	                    return;
	                  }
	                }

	                if (params.eventName.indexOf('ITEMUPDATED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.item)) {
	                  _this2.onPullItemUpdated(params);
	                } else if (params.eventName === 'ITEMADDED' + _this2.grid.getTypeId() && main_core.Type.isPlainObject(params.item)) {
	                  _this2.onPullItemAdded(params);
	                } else if (params.eventName.indexOf('ITEMDELETED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.item)) {
	                  _this2.onPullItemDeleted(params);
	                } else if (params.eventName === 'STAGEADDED' + _this2.grid.getTypeId() && main_core.Type.isPlainObject(params.stage)) {
	                  _this2.onPullStageAdded(params);
	                } else if (params.eventName.indexOf('STAGEUPDATED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.stage)) {
	                  _this2.onPullStageUpdated(params);
	                } else if (params.eventName.indexOf('STAGEDELETED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.stage)) {
	                  _this2.onPullStageDeleted(params);
	                } else if (params.eventName === 'ROBOTADDED' + _this2.grid.getTypeId() && main_core.Type.isPlainObject(params.robot)) {
	                  _this2.onPullRobotAdded(params);
	                } else if (params.eventName.indexOf('ROBOTUPDATED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.robot)) {
	                  _this2.onPullRobotUpdated(params);
	                } else if (params.eventName.indexOf('ROBOTDELETED' + _this2.grid.getTypeId()) === 0 && main_core.Type.isPlainObject(params.robot)) {
	                  _this2.onPullRobotDeleted(params);
	                } else if (params.eventName.indexOf('TYPEUPDATED' + _this2.grid.getTypeId()) === 0) {
	                  _this2.onPullTypeUpdated();
	                }
	              }
	            }
	          });
	          Pull.extendWatch(_this2.grid.getData().pullTag);
	        }

	        if (main_core.Type.isString(_this2.grid.getData().taskCountersPullTag)) {
	          Pull.subscribe({
	            moduleId: _this2.grid.getData().moduleId,
	            command: _this2.grid.getData().taskCountersPullTag,
	            callback: function callback(params) {
	              if (main_core.Type.isString(params.eventId)) {
	                if (_this2.eventIds.has(params.eventId)) {
	                  return;
	                }
	              }

	              if (_this2.grid.getTypeId() === main_core.Text.toInteger(params.typeId)) {
	                _this2.onPullCounters(params);
	              }
	            }
	          });
	          Pull.extendWatch(_this2.grid.getData().taskCountersPullTag);
	        }
	      });
	    }
	  }, {
	    key: "onPullItemUpdated",
	    value: function onPullItemUpdated(params) {
	      this.grid.addUsers(params.item.users);
	      var item = this.grid.getItem(params.item.id);

	      if (item) {
	        item.setData(params.item);
	        this.grid.insertItem(item);
	      } else {
	        var column = this.grid.getColumn(params.item.stageId);

	        if (column && (column.isCanMoveFrom() || column.canAddItems())) {
	          this.onPullItemAdded(params);
	        }
	      }
	    }
	  }, {
	    key: "onPullItemAdded",
	    value: function onPullItemAdded(params) {
	      var itemData = params.item;
	      this.grid.addUsers(itemData.users);
	      var oldItem = this.grid.getItem(itemData.id);

	      if (oldItem) {
	        return;
	      }

	      var item = new rpa_kanban.Kanban.Item({
	        id: itemData.id,
	        columnId: itemData.stageId,
	        name: itemData.name,
	        data: itemData
	      });
	      item.setGrid(this.grid);
	      this.grid.items[item.getId()] = item;
	      var column = this.grid.getColumn(item.getStageId());

	      if (column //&& this.grid.getFirstColumn() !== column
	      && (column.isCanMoveFrom() || column.canAddItems())) {
	        column.addItem(item, column.getFirstItem());
	      }
	    }
	  }, {
	    key: "onPullItemDeleted",
	    value: function onPullItemDeleted(params) {
	      if (!main_core.Type.isPlainObject(params.item)) {
	        return;
	      }

	      this.grid.removeItem(params.item.id);
	    }
	  }, {
	    key: "onPullStageAdded",
	    value: function onPullStageAdded(params) {
	      this.grid.onApplyFilter();
	    }
	  }, {
	    key: "onPullStageUpdated",
	    value: function onPullStageUpdated(params) {
	      var column = this.grid.getColumn(params.stage.id);

	      if (column) {
	        column.update(params);
	      }
	    }
	  }, {
	    key: "onPullStageDeleted",
	    value: function onPullStageDeleted(params) {
	      this.grid.removeColumn(params.stage.id);
	    }
	  }, {
	    key: "onPullRobotAdded",
	    value: function onPullRobotAdded(params) {
	      this.onPullRobotChanged(params.robot.stageId);
	    }
	  }, {
	    key: "onPullRobotUpdated",
	    value: function onPullRobotUpdated(params) {
	      this.onPullRobotChanged(params.robot.stageId);
	    }
	  }, {
	    key: "onPullRobotDeleted",
	    value: function onPullRobotDeleted(params) {
	      if (main_core.Type.isPlainObject(params.robot) && main_core.Type.isString(params.robot.robotName)) {
	        var column = this.grid.getColumn(params.robot.stageId);

	        if (column) {
	          column.setTasks(column.getTasks().filter(function (filteredTask) {
	            return filteredTask.robotName !== params.robot.robotName;
	          }));
	          column.rerenderSubtitle();
	        }
	      }
	    }
	  }, {
	    key: "onPullRobotChanged",
	    value: function onPullRobotChanged(stageId) {
	      var column = this.grid.getColumn(stageId);

	      if (column) {
	        column.loadTasks().then(function () {
	          column.rerenderSubtitle();
	        }).catch(function () {});
	      }
	    }
	  }, {
	    key: "onPullCounters",
	    value: function onPullCounters(params) {
	      var typeId = main_core.Text.toInteger(params.typeId);
	      var itemId = main_core.Text.toInteger(params.itemId);

	      if (typeId !== this.grid.getTypeId()) {
	        return;
	      }

	      var item = this.grid.getItem(itemId);

	      if (item) {
	        var currentCounter = item.getTasksCounter();

	        if (params.counter === '+1') {
	          currentCounter++;
	        } else if (params.counter === '-1') {
	          currentCounter--;
	        }

	        item.setTasksCounter(currentCounter);

	        if (main_core.Type.isPlainObject(params.tasksFaces)) {
	          item.setTasksParticipants(params.tasksFaces);
	        }

	        item.render();
	      }
	    }
	  }, {
	    key: "onPullTypeUpdated",
	    value: function onPullTypeUpdated() {
	      this.grid.onApplyFilter();
	    }
	  }]);
	  return PullManager;
	}();

	function _templateObject16() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"rpa-kanban-tasks-popup-inner\" data-role=\"rpa-kanban-column-tasks-item\">\n\t\t\t\t<div class=\"rpa-kanban-tasks-popup-list\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<a href=\"", "\" class=\"rpa-kanban-tasks-popup-add\">", "</a>\n\t\t\t</div>"]);

	  _templateObject16 = function _templateObject16() {
	    return data;
	  };

	  return data;
	}

	function _templateObject15() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-kanban-tasks-popup-item\">\n\t\t\t\t\t\t<a href=\"", "\" class=\"rpa-kanban-tasks-popup-name\">", "</a>\n\t\t\t\t\t\t<div class=\"rpa-kanban-tasks-popup-desc\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<span class=\"rpa-kanban-tasks-popup-delete\" onclick=\"", "\">", "</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>"]);

	  _templateObject15 = function _templateObject15() {
	    return data;
	  };

	  return data;
	}

	function _templateObject14() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"rpa-kanban-column-task-responsible-item rpa-kanban-column-task-responsible-item-other\">\n\t\t\t\t\t\t<span class=\"rpa-kanban-column-task-responsible-other-text\">+", "</span>\n\t\t\t\t\t</span>"]);

	  _templateObject14 = function _templateObject14() {
	    return data;
	  };

	  return data;
	}

	function _templateObject13() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"rpa-kanban-column-task-responsible\">\n\t\t\t\t\t<div class=\"rpa-kanban-column-task-responsible-list\" style=\"background-color: ", "\" onclick=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject13 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12() {
	  var data = babelHelpers.taggedTemplateLiteral(["<a href=\"", "\" class=\"rpa-kanban-column-task-responsible-add\"></a>"]);

	  _templateObject12 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"rpa-kanban-column-task-responsible-add\"></span>"]);

	  _templateObject11 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"rpa-kanban-column-task-responsible-item\" title=\"", "\">\n\t\t\t\t\t<span class=\"rpa-kanban-column-task-responsible-img\" style=\"border-color: ", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</span>"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<img src=\"", "\" alt=\"\">"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-kanban-column-task-btn\" onclick=\"", "\">\n\t\t\t\t\t\t<span class=\"rpa-kanban-column-task-btn-title\">", "</span>\n\t\t\t\t\t\t<span class=\"rpa-kanban-column-task-btn-counter\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-kanban-column-task-block\">\n\t\t\t\t\t\t<div class=\"rpa-kanban-column-task-inner\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"main-kanban-column-settings-button\">\n\t\t\t\t\t\t<a class=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-no-caps ui-btn-round ui-btn-themes main-kanban-column-settings-button-rpa\" href=\"", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"main-kanban-column-settings-button\" onclick=\"", "\">\n\t\t\t\t<button class=\"ui-btn ui-btn-xs ui-btn-link ui-btn-icon-setting\"></button>\n\t\t\t</div>"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-kanban-form-buttons\">\n\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary\" onclick=\"", "\">", "</button>\n\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-link\" onclick=\"", "\">", "</button>\n\t\t\t</div>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"></div>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"main-kanban-column-add-item-button\" onclick=\"", "\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"main-kanban-column-subtitle-box\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Column = /*#__PURE__*/function (_Kanban$Column) {
	  babelHelpers.inherits(Column, _Kanban$Column);

	  function Column() {
	    babelHelpers.classCallCheck(this, Column);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Column).apply(this, arguments));
	  }

	  babelHelpers.createClass(Column, [{
	    key: "getId",
	    value: function getId() {
	      return parseInt(babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "getId", this).call(this));
	    }
	  }, {
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "setOptions", this).call(this, options);
	      this.canMoveFrom = !!options.canMoveFrom;
	      this.setPermissionProperties();
	    }
	  }, {
	    key: "isDroppable",
	    value: function isDroppable() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "isDroppable", this).call(this) || this.canMoveTo();
	    }
	  }, {
	    key: "isFirstColumn",
	    value: function isFirstColumn() {
	      return this.data.isFirst === true;
	    }
	  }, {
	    key: "setIsFirstColumn",
	    value: function setIsFirstColumn() {
	      var isFirst = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.data.isFirst = isFirst;
	      return this;
	    }
	  }, {
	    key: "onAfterRender",
	    value: function onAfterRender() {
	      if (!this.isDroppable()) {
	        this.getContainer().style.backgroundColor = 'rgba(204, 204, 204, 0.2)';
	      } else {
	        this.getContainer().style.backgroundColor = 'transparent';
	      }
	    }
	  }, {
	    key: "rerenderSubtitle",
	    value: function rerenderSubtitle() {
	      var _this = this;

	      var nodeNames = ['responsible', 'subTitleTasksButton', 'subTitleTasks', 'subTitleAddTaskButton', 'subTitleSettingsButton', 'subTitleAddButton'];
	      nodeNames.forEach(function (nodeName) {
	        main_core.Dom.clean(_this.layout[nodeName]);
	        _this.layout[nodeName] = null;
	      });
	      main_core.Dom.clean(this.layout.subtitleNode);

	      if (this.tasksPopup) {
	        this.tasksPopup.destroy();
	      }

	      this.renderSubTitle();
	    }
	  }, {
	    key: "renderSubTitle",
	    value: function renderSubTitle() {
	      var subTitleNode = this.getSubTitleNode();

	      if (this.isEditable()) {
	        var tasks = this.getTasks();
	        var robotsCnt = this.getData()['robotsCount'];

	        if (tasks && tasks.length > 0) {
	          subTitleNode.appendChild(this.renderSubTitleTasks(tasks));
	        } else {
	          if (!this.isFirstColumn() || this.isFirstColumn() && !robotsCnt) {
	            subTitleNode.appendChild(this.renderSubTitleAddTaskButton());
	          }
	        }
	      }

	      if (this.isFirstColumn() && this.canAddItems()) {
	        subTitleNode.appendChild(this.renderSubTitleAddButton());
	      } else {
	        if (this.layout.subTitleAddButton) {
	          main_core.Dom.remove(this.layout.subTitleAddButton);
	          this.layout.subTitleAddButton = null;
	        }
	      }

	      return subTitleNode;
	    }
	  }, {
	    key: "getSubTitleNode",
	    value: function getSubTitleNode() {
	      if (!this.layout.subtitleNode) {
	        this.layout.subtitleNode = main_core.Tag.render(_templateObject());
	      }

	      return this.layout.subtitleNode;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "getContainer", this).call(this);

	      if (this.isFirstColumn() && this.canAddItems()) {
	        var quickFormContainer = this.renderQuickFormContainer();
	        var itemsContainer = this.getItemsContainer();

	        if (quickFormContainer && itemsContainer) {
	          if (quickFormContainer.parentNode !== itemsContainer) {
	            main_core.Dom.prepend(quickFormContainer, itemsContainer);
	          }
	        }
	      }

	      return container;
	    } //region quick form

	  }, {
	    key: "renderSubTitleAddButton",
	    value: function renderSubTitleAddButton() {
	      if (!this.layout.subTitleAddButton) {
	        this.layout.subTitleAddButton = main_core.Tag.render(_templateObject2(), this.handleAddItemButtonClick.bind(this));
	      }

	      return this.layout.subTitleAddButton;
	    }
	  }, {
	    key: "renderQuickFormContainer",
	    value: function renderQuickFormContainer() {
	      if (!this.layout.quickFormContainer) {
	        var className = 'rpa-kanban-form';

	        if (this.getGrid().canAddColumns()) {
	          className += ' rpa-kanban-form-with-settings';
	        }

	        this.layout.quickFormContainer = main_core.Tag.render(_templateObject3(), className);
	      }

	      return this.layout.quickFormContainer;
	    }
	  }, {
	    key: "renderQuickFormButtons",
	    value: function renderQuickFormButtons() {
	      if (!this.layout.quickFormButtons) {
	        this.layout.quickFormButtons = main_core.Tag.render(_templateObject4(), this.handleFormSaveButtonClick.bind(this), main_core.Loc.getMessage('RPA_KANBAN_QUICK_FORM_SAVE_BUTTON'), this.handleFormCancelButtonClick.bind(this), main_core.Loc.getMessage('RPA_KANBAN_QUICK_FORM_CANCEL_BUTTON'));
	      }

	      return this.layout.quickFormButtons;
	    }
	  }, {
	    key: "handleAddItemButtonClick",
	    value: function handleAddItemButtonClick() {
	      var _this2 = this;

	      if (this.getGrid().isProgress()) {
	        return;
	      }

	      if (this.getGrid().isCreateItemRestricted()) {
	        rpa_manager.Manager.Instance.showFeatureSlider();
	        return;
	      }

	      if (this.isFormVisible()) {
	        return;
	      }

	      this.getGrid().startProgress();
	      main_core.ajax.runAction('rpa.item.getEditor', {
	        analyticsLabel: 'rpaItemOpenQuickForm',
	        data: {
	          typeId: this.getGrid().getTypeId(),
	          id: 0,
	          stageId: this.getId(),
	          eventId: this.getGrid().pullManager.registerRandomEventId()
	        }
	      }).then(function (response) {
	        _this2.getGrid().stopProgress();

	        main_core.Runtime.html(_this2.layout.quickFormContainer, response.data.html).then(function () {
	          _this2.addSelectButtonToEditor();

	          main_core.Dom.append(_this2.renderQuickFormButtons(), _this2.layout.quickFormContainer);

	          _this2.showForm();

	          _this2.bindKeyDownEvents();
	        });
	      }).catch(function (response) {
	        _this2.getGrid().stopProgress();

	        _this2.getGrid().showErrorFromResponse(response);
	      });
	    }
	  }, {
	    key: "showForm",
	    value: function showForm() {
	      this.getBody().scrollTop = 0;
	      this.layout.quickFormContainer.style.display = 'block';
	      this.layout.quickFormButtons.style.display = 'block';
	    }
	  }, {
	    key: "hideForm",
	    value: function hideForm() {
	      this.layout.quickFormContainer.style.display = 'none';
	      this.layout.quickFormButtons.style.display = 'none';
	    }
	  }, {
	    key: "isFormVisible",
	    value: function isFormVisible() {
	      return this.layout.quickFormContainer.style.display === 'block' && this.layout.quickFormButtons.style.display === 'block';
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return rpa_manager.Manager.getEditor(this.getGrid().getTypeId(), 0);
	    }
	  }, {
	    key: "handleFormCancelButtonClick",
	    value: function handleFormCancelButtonClick() {
	      var editor = this.getEditor();

	      if (editor) {
	        editor.rollback();
	        editor.refreshLayout();
	      }

	      this.hideForm();
	    }
	  }, {
	    key: "handleFormSaveButtonClick",
	    value: function handleFormSaveButtonClick() {
	      var editor = this.getEditor();

	      if (editor) {
	        editor.save();
	        this.bindEditorEvents();
	      } else {
	        this.hideForm();
	      }
	    }
	  }, {
	    key: "onEditorSubmit",
	    value: function onEditorSubmit(entityData, response) {
	      if (this.isFormVisible()) {
	        this.hideForm();
	        var itemData = response.data.item;
	        this.getGrid().addUsers(response.data.item.users);
	        var oldItem = this.getGrid().getItem(itemData.id);

	        if (oldItem) {
	          return;
	        }

	        var item = new rpa_kanban.Kanban.Item({
	          id: itemData.id,
	          columnId: itemData.stageId,
	          name: itemData.name,
	          data: itemData
	        });
	        item.setGrid(this.getGrid());
	        this.getGrid().items[item.getId()] = item;
	        var column = this.getGrid().getColumn(item.getStageId());

	        if (column) {
	          column.addItem(item, column.getFirstItem());
	        }
	      }
	    }
	  }, {
	    key: "onEditorErrors",
	    value: function onEditorErrors(errors) {
	      if (this.isFormVisible()) {
	        this.hideForm();
	        this.getGrid().showErrorFromResponse(errors);
	      }
	    }
	  }, {
	    key: "bindEditorEvents",
	    value: function bindEditorEvents() {
	      if (!this.isEditorEventsBinded) {
	        this.isEditorEventsBinded = true;
	        BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmitFailure', this.onEditorErrors.bind(this));
	        BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmit', this.onEditorSubmit.bind(this));
	      }
	    }
	  }, {
	    key: "bindKeyDownEvents",
	    value: function bindKeyDownEvents() {
	      var _this3 = this;

	      if (!this.isKeyDownEventsBinded) {
	        this.isKeyDownEventsBinded = true;

	        var onEnterKeyDown = function onEnterKeyDown(event) {
	          if ((event.code === 'Enter' || event.code === 'NumpadEnter') && _this3.isFormVisible()) {
	            _this3.handleFormSaveButtonClick();
	          }
	        };

	        var isCtrlKey = function isCtrlKey(code) {
	          return code === 'MetaRight' || code === 'MetaLeft' || code === 'ControlRight' || code === 'ControlLeft';
	        };

	        main_core.Event.bind(window, 'keydown', function (event) {
	          if (isCtrlKey(event.code)) {
	            main_core.Event.bind(window, 'keydown', onEnterKeyDown);
	          } else if (event.code === 'Escape') {
	            _this3.handleFormCancelButtonClick();
	          }
	        });
	        main_core.Event.bind(window, 'keyup', function (event) {
	          if (isCtrlKey(event.code)) {
	            main_core.Event.unbind(window, 'keydown', onEnterKeyDown);
	          }
	        });
	      }
	    } //endregion
	    //region settings

	  }, {
	    key: "renderSubTitleSettingsButton",
	    value: function renderSubTitleSettingsButton() {
	      if (!this.layout.subTitleSettingsButton) {
	        this.layout.subTitleSettingsButton = main_core.Tag.render(_templateObject5(), this.openSettings.bind(this));
	      }

	      return this.layout.subTitleSettingsButton;
	    }
	  }, {
	    key: "openSettings",
	    value: function openSettings() {
	      var _this4 = this;

	      var url = this.data.settingsUrl;

	      if (url) {
	        rpa_manager.Manager.openSlider(url).then(function (slider) {
	          var response = slider.getData().get('response');

	          if (response) {
	            _this4.update(response.data);
	          }
	        });
	      }
	    } //endregion
	    //region task

	  }, {
	    key: "renderSubTitleAddTaskButton",
	    value: function renderSubTitleAddTaskButton() {
	      if (!this.layout.subTitleAddTaskButton) {
	        var url = this.buildAddRobotUrl();
	        this.layout.subTitleAddTaskButton = main_core.Tag.render(_templateObject6(), url, main_core.Loc.getMessage('RPA_KANBAN_COLUMN_ADD_TASK_BTN'));
	      }

	      return this.layout.subTitleAddTaskButton;
	    }
	  }, {
	    key: "renderSubTitleTasks",
	    value: function renderSubTitleTasks(tasks) {
	      if (!this.layout.subTitleTasks) {
	        this.layout.subTitleTasks = main_core.Tag.render(_templateObject7(), this.renderSubTitleResponsible(tasks, true), this.renderSubTitleTasksButton(tasks));
	      }

	      return this.layout.subTitleTasks;
	    }
	  }, {
	    key: "renderSubTitleTasksButton",
	    value: function renderSubTitleTasksButton(tasks) {
	      if (!this.layout.subTitleTasksButton) {
	        this.layout.subTitleTasksButton = main_core.Tag.render(_templateObject8(), this.showTasks.bind(this, tasks), main_core.Loc.getMessage('RPA_KANBAN_TASKS'), tasks.length);
	      }

	      return this.layout.subTitleTasksButton;
	    }
	  }, {
	    key: "renderSubTitleResponsible",
	    value: function renderSubTitleResponsible(tasks, showTaskListMenu) {
	      var _this5 = this;

	      var responsibleElements = [];
	      var plusHandler = this.showTasks.bind(this, tasks);
	      tasks.forEach(function (task) {
	        task.users.forEach(function (user) {
	          var img = user.photoSrc ? main_core.Tag.render(_templateObject9(), user.photoSrc) : null;
	          responsibleElements.push(main_core.Tag.render(_templateObject10(), user.name, "#" + _this5.getColor(), img));
	        });
	      });
	      responsibleElements = this.sliceResponsibleListElements(responsibleElements);
	      var plusNode = main_core.Tag.render(_templateObject11());

	      if (showTaskListMenu) {
	        BX.bind(plusNode, 'click', plusHandler);
	      } else {
	        var task = tasks[0];

	        if (task.canAppendResponsibles) {
	          BX.Bizproc.UserSelector.decorateNode(plusNode, {
	            isOnlyDialogMode: true,
	            callbacks: {
	              select: this.addTaskUserHandler.bind(this, task)
	            }
	          });
	        } else {
	          plusNode = main_core.Tag.render(_templateObject12(), this.buildEditRobotUrl(task['robotName']));
	        }
	      }

	      return main_core.Tag.render(_templateObject13(), "#" + this.getColor(), plusHandler, responsibleElements, plusNode);
	    }
	  }, {
	    key: "sliceResponsibleListElements",
	    value: function sliceResponsibleListElements(elements) {
	      if (elements.length > 4) {
	        var counter = elements.length - 4;
	        elements = elements.slice(0, 4);
	        elements.push(main_core.Tag.render(_templateObject14(), counter));
	      }

	      return elements;
	    }
	  }, {
	    key: "showTasks",
	    value: function showTasks(tasks) {
	      this.getTasksPopup(tasks).show();
	    }
	  }, {
	    key: "addTaskUserHandler",
	    value: function addTaskUserHandler(task, value, selector) {
	      main_core.ajax.runAction('rpa.task.addUser', {
	        analyticsLabel: 'rpaTaskAddUser',
	        data: {
	          typeId: this.getGrid().getData().typeId,
	          stageId: this.getId(),
	          robotName: task.robotName,
	          userValue: value
	        },
	        getParameters: {
	          context: 'kanban'
	        }
	      }).then(function (response) {});
	    }
	  }, {
	    key: "getTasksPopup",
	    value: function getTasksPopup(tasks) {
	      var _this6 = this;

	      if (!this.tasksPopup) {
	        var button = this.layout.subTitleTasksButton;

	        if (!button) {
	          button = this.renderSubTitleTasksButton(this.getTasks());
	        }

	        this.tasksPopup = new main_popup.Popup('rpa-tasks-' + this.getId(), button, {
	          autoHide: true,
	          draggable: false,
	          offsetTop: -5,
	          offsetLeft: 30,
	          noAllPaddings: true,
	          bindOptions: {
	            forceBindPosition: true
	          },
	          closeByEsc: true,
	          cacheable: false,
	          angle: {
	            offset: 81,
	            position: 'top'
	          },
	          events: {
	            onPopupDestroy: function onPopupDestroy() {
	              _this6.tasksPopup = null;
	            }
	          },
	          overlay: {
	            backgroundColor: 'transparent'
	          },
	          content: this.renderTasksPopup(tasks)
	        });
	      }

	      return this.tasksPopup;
	    }
	  }, {
	    key: "renderTasksPopup",
	    value: function renderTasksPopup(tasks) {
	      var _this7 = this;

	      var elements = tasks.map(function (task) {
	        return main_core.Tag.render(_templateObject15(), _this7.buildEditRobotUrl(task['robotName']), main_core.Text.encode(task['title']), _this7.renderSubTitleResponsible([task]), _this7.deleteTaskHandler.bind(_this7, task), main_core.Loc.getMessage('RPA_KANBAN_COLUMN_DELETE_TASK_BTN'));
	      });
	      return main_core.Tag.render(_templateObject16(), elements, this.buildAddRobotUrl(), main_core.Loc.getMessage('RPA_KANBAN_COLUMN_ADD_TASK_BTN'));
	    }
	  }, {
	    key: "deleteTaskHandler",
	    value: function deleteTaskHandler(task, event) {
	      var _this8 = this;

	      ui_dialogs_messagebox.MessageBox.show({
	        message: main_core.Loc.getMessage('RPA_KANBAN_COLUMN_DELETE_TASK_CONFIRM'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        onOk: function onOk() {
	          if (_this8.getGrid().isProgress()) {
	            return;
	          }

	          _this8.getGrid().startProgress();

	          var promise = new BX.Promise();
	          main_core.ajax.runAction('rpa.task.delete', {
	            analyticsLabel: 'rpaKanbanTaskDelete',
	            data: {
	              typeId: _this8.getGrid().getData().typeId,
	              stageId: _this8.getId(),
	              robotName: task['robotName'],
	              eventId: _this8.getGrid().pullManager.registerRandomEventId()
	            },
	            getParameters: {
	              context: 'kanban'
	            }
	          }).then(function () {
	            if (_this8.tasksPopup) {
	              _this8.tasksPopup.destroy();
	            }

	            _this8.setTasks(_this8.getTasks().filter(function (filteredTask) {
	              return filteredTask.robotName !== task.robotName;
	            }));

	            _this8.rerenderSubtitle();

	            _this8.getGrid().stopProgress();

	            promise.fulfill();
	          }).catch(function (response) {
	            _this8.getGrid().stopProgress();

	            promise.reject();
	          });
	          return promise;
	        },
	        popupOptions: {
	          zIndexAbsolute: 1200
	        }
	      });
	    }
	  }, {
	    key: "buildAddRobotUrl",
	    value: function buildAddRobotUrl() {
	      var typeId = this.getGrid().getData().typeId;
	      var url = new main_core.Uri("/rpa/automation/".concat(typeId, "/addrobot/")); //TODO use URI from urlManager

	      url.setQueryParams({
	        stage: this.getId()
	      });
	      return url;
	    }
	  }, {
	    key: "buildEditRobotUrl",
	    value: function buildEditRobotUrl(robotName) {
	      var typeId = this.getGrid().getData().typeId;
	      var url = new main_core.Uri("/rpa/automation/".concat(typeId, "/editrobot/")); //TODO use URI from urlManager

	      url.setQueryParams({
	        stage: this.getId(),
	        robotName: robotName
	      });
	      return url;
	    } //endregion

	  }, {
	    key: "getFields",
	    value: function getFields() {
	      var fields = this.getData().userFields;

	      if (!fields || !main_core.Type.isPlainObject(fields)) {
	        fields = this.getGrid().getFields();
	      }

	      if (!fields || !main_core.Type.isPlainObject(fields)) {
	        fields = {};
	      }

	      return fields;
	    }
	  }, {
	    key: "getPossibleNextStages",
	    value: function getPossibleNextStages() {
	      return this.getData().possibleNextStages;
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return parseInt(this.getData().sort);
	    }
	  }, {
	    key: "isCanMoveFrom",
	    value: function isCanMoveFrom() {
	      return !!this.canMoveFrom;
	    }
	  }, {
	    key: "canMoveTo",
	    value: function canMoveTo() {
	      var _this9 = this;

	      var result = false;
	      this.getGrid().getColumns().forEach(function (column) {
	        if (column.isCanMoveFrom() && column.getPossibleNextStages().includes(_this9.getId())) {
	          result = true;
	        }
	      });
	      return result;
	    }
	  }, {
	    key: "update",
	    value: function update(data) {
	      var _this10 = this;

	      if (main_core.Type.isPlainObject(data) && data.stage && main_core.Type.isPlainObject(data.stage) && parseInt(data.stage.id) === this.getId()) {
	        var stageData = data.stage;
	        this.setName(stageData.name);
	        this.setColor(stageData.color);
	        this.setData(stageData);
	        this.processPermissions(stageData);
	        this.getGrid().moveColumn(this, this.getTargetColumn());
	        this.render();
	        this.getGrid().getColumns().forEach(function (column) {
	          if (column !== _this10) {
	            column.processPermissions();
	            column.onAfterRender();
	          }
	        });
	      }
	    }
	  }, {
	    key: "getTargetColumn",
	    value: function getTargetColumn() {
	      var _this11 = this;

	      var columns = this.getGrid().getColumns();
	      var targetColumn = null;
	      columns.forEach(function (gridColumn) {
	        if (gridColumn.getId() !== _this11.getId() && gridColumn.getSort() >= _this11.getSort() && (!targetColumn || targetColumn.getSort() > gridColumn.getSort())) {
	          targetColumn = gridColumn;
	        }
	      });
	      return targetColumn;
	    }
	  }, {
	    key: "processPermissions",
	    value: function processPermissions() {
	      this.setPermissionProperties();
	      this.processDraggingOptions();
	    }
	  }, {
	    key: "setPermissionProperties",
	    value: function setPermissionProperties() {
	      var _this12 = this;

	      var data = this.getData();
	      var permissions = {};

	      if (data.permissions && main_core.Type.isPlainObject(data.permissions)) {
	        permissions = data.permissions;
	      }

	      Object.keys(permissions).forEach(function (name) {
	        _this12[name] = permissions[name];
	      });
	    }
	  }, {
	    key: "processDraggingOptions",
	    value: function processDraggingOptions() {
	      if (this.isDraggable()) {
	        this.makeDraggable();
	      } else {
	        this.disableDragging();
	      }

	      if (this.isDroppable()) {
	        this.makeDroppable();
	      } else {
	        this.disableDropping();
	      }

	      this.getItems().forEach(function (item) {
	        item.processPermissions();
	      });
	    }
	  }, {
	    key: "loadTasks",
	    value: function loadTasks() {
	      var _this13 = this;

	      return new Promise(function (resolve, reject) {
	        _this13.getGrid().startProgress();

	        main_core.ajax.runAction('rpa.stage.getTasks', {
	          data: {
	            id: _this13.getId()
	          }
	        }).then(function (response) {
	          _this13.getGrid().stopProgress();

	          _this13.setTasks(response.data.tasks);

	          resolve();
	        }).catch(function (response) {
	          _this13.getGrid().stopProgress().showErrorFromResponse(response);

	          reject();
	        });
	      });
	    }
	  }, {
	    key: "getTasks",
	    value: function getTasks() {
	      if (!this.data) {
	        this.data = {};
	      }

	      if (!this.data.tasks || !main_core.Type.isArray(this.data.tasks)) {
	        this.data.tasks = [];
	      }

	      return Array.from(this.data.tasks);
	    }
	  }, {
	    key: "setTasks",
	    value: function setTasks(tasks) {
	      if (!main_core.Type.isArray(tasks)) {
	        tasks = [];
	      }

	      this.data.tasks = tasks;
	      return this;
	    }
	  }, {
	    key: "addSelectButtonToEditor",
	    value: function addSelectButtonToEditor() {
	      var editor = this.getEditor();

	      if (!editor) {
	        return;
	      }

	      var editorMainSection = this.getGrid().getEditorMainSection(editor);

	      if (!editorMainSection) {
	        return;
	      }

	      if (editorMainSection._addChildButton) {
	        return;
	      }

	      editorMainSection.ensureButtonPanelCreated();
	      editorMainSection._addChildButton = BX.create("span", {
	        props: {
	          className: "ui-entity-editor-content-add-lnk"
	        },
	        text: BX.message("UI_ENTITY_EDITOR_SELECT_FIELD"),
	        events: {
	          click: BX.delegate(editorMainSection.onAddChildBtnClick, editorMainSection)
	        }
	      });
	      editorMainSection.addButtonElement(editorMainSection._addChildButton, {
	        position: "left"
	      });
	    }
	  }]);
	  return Column;
	}(main_kanban.Kanban.Column);

	function _templateObject13$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"rpa-kanban-item-counter\" onclick=\"", "\" ", ">\n\t\t\t\t\t<div class=\"rpa-kanban-item-counter-text\">", "</div>\n\t\t\t\t\t<div class=\"rpa-kanban-item-counter-value\">", "</div>\n\t\t\t\t</div>\n\t\t"]);

	  _templateObject13$1 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"rpa-kanban-column-task-responsible-item ", "\" \n\t\t\t href=\"", "\" title=\"", "\">\n\t\t\t\t<span class=\"rpa-kanban-column-task-responsible-img\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</a>\n\t\t"]);

	  _templateObject12$1 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"rpa-kanban-column-task-responsible-list\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t"]);

	  _templateObject11$1 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"rpa-kanban-item-contact\">\n\t\t\t\t\t<span class=\"rpa-kanban-item-contact-im\"></span>\n\t\t\t\t</div>\n\t\t"]);

	  _templateObject10$1 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-kanban-item-user\">\n\t\t\t\t", "\n\t\t\t\t<a class=\"rpa-kanban-item-user-name rpa-kanban-item-field-item-value\" href=\"", "\">", "</a>\n\t\t\t</div>"]);

	  _templateObject9$1 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<a class=\"rpa-kanban-item-user-photo\" href=\"", "\" style=\"background-image: url(", ")\"></a>"]);

	  _templateObject8$1 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"rpa-kanban-item-description\"></div>\n\t\t"]);

	  _templateObject7$1 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"rpa-kanban-item-shadow\"></div>\n\t\t"]);

	  _templateObject6$1 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"rpa-kanban-item-field-item\">\n\t\t\t\t\t\t\t<span class=\"rpa-kanban-item-field-item-name\">", "</span>\n\t\t\t\t\t\t\t<span class=\"rpa-kanban-item-field-item-value\">", "</span>\n\t\t\t\t\t\t</div>"]);

	  _templateObject5$1 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"rpa-kanban-item-field-item\">\n\t\t\t\t\t\t\t\t<span class=\"rpa-kanban-item-field-item-name\">", "</span>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-kanban-item-field-list\"></div>"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<a class=\"rpa-kanban-item-title\" href=\"", "\">", "</a>"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div ondblclick=\"", "\" class=\"main-kanban-item-default rpa-kanban-item\"></div>"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Item = /*#__PURE__*/function (_Kanban$Item) {
	  babelHelpers.inherits(Item, _Kanban$Item);

	  function Item() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, Item);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Item)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "currentState", {});
	    return _this;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Item.prototype), "setOptions", this).call(this, options);
	      this.setPermissionProperties();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      this.layout.title = null;
	      this.renderDescription();
	      this.renderFieldsList();
	      this.renderShadow();

	      if (!this.layout.content) {
	        this.layout.content = main_core.Tag.render(_templateObject$1(), this.onDoubleClick.bind(this));
	      } else {
	        main_core.Dom.clean(this.layout.content);
	      }

	      if (this.layout.title) {
	        this.layout.content.appendChild(this.layout.title);
	      }

	      if (this.layout.fieldList) {
	        this.layout.content.appendChild(this.layout.fieldList);
	      }

	      if (this.layout.description) {
	        this.layout.content.appendChild(this.layout.description);
	      }

	      this.layout.content.appendChild(this.renderShadow()); //this.layout.content.appendChild(this.renderContact());

	      this.layout.description.appendChild(this.renderTasksParticipants());
	      this.layout.description.appendChild(this.renderTasksCounter());
	      this.layout.content.style.borderLeft = "2px solid #" + this.getColumn().getColor();

	      if (this.isDraggable()) {
	        this.layout.content.style.backgroundColor = "#fff";
	      } else {
	        this.layout.content.style.backgroundColor = "#aaa";
	      }

	      main_core.Event.bindOnce(this.layout.content, "animationend", function () {
	        BX.removeClass(_this2.layout.container, "main-kanban-item-new");
	      });
	      return this.layout.content;
	    }
	  }, {
	    key: "setTasksParticipants",
	    value: function setTasksParticipants(tasksFaces) {
	      this.data.tasksFaces = tasksFaces;
	      return this;
	    }
	  }, {
	    key: "getTasksCounter",
	    value: function getTasksCounter() {
	      return main_core.Text.toInteger(this.data.tasksCounter);
	    }
	  }, {
	    key: "getTasksParticipants",
	    value: function getTasksParticipants() {
	      var userId = main_core.Text.toInteger(this.getData()['createdBy']);
	      var currentUserId = this.getGrid().getUserId();
	      var startedBy = this.getGrid().getUser(userId);
	      var faces = this.data.tasksFaces;
	      var completedById = faces.completed[0];
	      var waitingForId = faces.running.includes(currentUserId) ? currentUserId : faces.running[0];
	      var completedCnt = faces.completed.length;
	      var waitingForCnt = faces.running.length;
	      var completedBy = null;
	      var waitingFor = null;

	      if (completedById) {
	        completedBy = this.getGrid().getUser(main_core.Text.toInteger(completedById));
	      }

	      if (waitingForId) {
	        waitingFor = this.getGrid().getUser(main_core.Text.toInteger(waitingForId));
	      }

	      return {
	        startedBy: startedBy,
	        completedBy: completedBy,
	        waitingFor: waitingFor,
	        completedCnt: completedCnt,
	        waitingForCnt: waitingForCnt
	      };
	    }
	  }, {
	    key: "setTasksCounter",
	    value: function setTasksCounter(counter) {
	      this.data.tasksCounter = counter;
	      return this;
	    }
	  }, {
	    key: "bindEditorEvents",
	    value: function bindEditorEvents() {
	      if (!this.isEditorEventsBinded) {
	        this.isEditorEventsBinded = true;
	        BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmitFailure', this.onEditorErrors.bind(this));
	        BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmit', this.onEditorSubmit.bind(this));
	      }
	    }
	  }, {
	    key: "showEditor",
	    value: function showEditor(columnId) {
	      var _this3 = this;

	      main_core.Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
	      this.bindEditorEvents();
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('rpa.item.getEditor', {
	          analyticsLabel: 'rpaItemMovedMandatoryFieldsPopupOpen',
	          data: {
	            typeId: _this3.getTypeId(),
	            id: _this3.getId(),
	            stageId: columnId > 0 ? columnId : null,
	            eventId: _this3.getGrid().pullManager.registerRandomEventId()
	          }
	        }).then(function (response) {
	          var popup = _this3.getPopup();

	          if (popup) {
	            main_core.Runtime.html(popup.getContentContainer(), response.data.html).then(function () {
	              popup.show();
	              _this3.editorResolve = resolve;
	              _this3.editorReject = reject;
	            });
	          }
	        }).catch(function (response) {
	          main_core.Dom.removeClass(_this3.layout.container, 'main-kanban-item-waiting');
	          reject(response.errors);
	        });
	      });
	    }
	  }, {
	    key: "onEditorSaveClick",
	    value: function onEditorSaveClick() {
	      var editor = this.getEditor();

	      if (!editor) {
	        this.getPopup().close();

	        if (main_core.Type.isFunction(this.editorReject)) {
	          this.editorReject('Editor not found');
	          this.editorResolve = null;
	          this.editorReject = null;
	        }
	      } else {
	        editor.save();
	      }
	    }
	  }, {
	    key: "onEditorCancelClick",
	    value: function onEditorCancelClick() {
	      main_core.Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
	      this.getPopup().close();

	      if (main_core.Type.isFunction(this.editorResolve)) {
	        this.editorResolve({
	          cancel: true
	        });
	        this.editorResolve = null;
	        this.editorReject = null;
	      }
	    }
	  }, {
	    key: "onEditorSubmit",
	    value: function onEditorSubmit(entityData, response) {
	      if (this.getPopup().isShown()) {
	        main_core.Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
	        this.getPopup().close();
	        this.setData(response.data.item);
	        this.saveCurrentState();
	        this.render();
	      }
	    }
	  }, {
	    key: "onEditorErrors",
	    value: function onEditorErrors(errors) {
	      if (this.getPopup().isShown()) {
	        main_core.Dom.removeClass(this.layout.container, 'main-kanban-item-waiting');
	        this.getPopup().close();

	        if (main_core.Type.isFunction(this.editorReject)) {
	          this.editorReject({
	            errors: errors
	          }, false);
	          this.editorResolve = null;
	          this.editorReject = null;
	        }
	      }
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var popupId = 'rpa-kanban-item-popup-' + this.getId();
	      var popup = main_popup.PopupWindowManager.getPopupById(popupId);

	      if (!popup) {
	        popup = new main_popup.PopupWindow(popupId, null, {
	          zIndex: 200,
	          className: "",
	          autoHide: false,
	          closeByEsc: false,
	          closeIcon: false,
	          width: 600,
	          overlay: true,
	          lightShadow: false,
	          buttons: this.getItemPopupButtons()
	        });
	      }

	      return popup;
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return rpa_manager.Manager.getEditor(this.getTypeId(), parseInt(this.getId()));
	    }
	  }, {
	    key: "getItemPopupButtons",
	    value: function getItemPopupButtons() {
	      return [new BX.PopupWindowButton({
	        text: main_core.Loc.getMessage('RPA_KANBAN_POPUP_SAVE'),
	        className: "ui-btn ui-btn-md ui-btn-primary",
	        events: {
	          click: this.onEditorSaveClick.bind(this)
	        }
	      }), new BX.PopupWindowButton({
	        text: main_core.Loc.getMessage('RPA_KANBAN_POPUP_CANCEL'),
	        className: "ui-btn ui-btn-md",
	        events: {
	          click: this.onEditorCancelClick.bind(this)
	        }
	      })];
	    }
	  }, {
	    key: "showTasks",
	    value: function showTasks() {
	      var _this4 = this;

	      main_core.Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
	      return new Promise(function (resolve) {
	        rpa_manager.Manager.Instance.openTasks(_this4.getTypeId(), _this4.getId()).then(function (result) {
	          resolve(result);
	          main_core.Dom.removeClass(_this4.layout.container, 'main-kanban-item-waiting');
	        });
	      });
	    }
	  }, {
	    key: "getStageId",
	    value: function getStageId() {
	      return main_core.Text.toInteger(this.getData().stageId);
	    }
	  }, {
	    key: "setStageId",
	    value: function setStageId(stageId) {
	      this.data.stageId = stageId;
	      return this;
	    }
	  }, {
	    key: "getTypeId",
	    value: function getTypeId() {
	      return main_core.Text.toInteger(this.getData().typeId);
	    }
	  }, {
	    key: "saveCurrentState",
	    value: function saveCurrentState() {
	      var column = this.getColumn();
	      var nextItem = column.getNextItemSibling(this);
	      var previousItem = column.getPreviousItemSibling(this);
	      this.data.stageId = this.getColumnId();
	      this.currentState.nextItemId = nextItem ? nextItem.getId() : 0;
	      this.currentState.previousItemId = previousItem ? previousItem.getId() : 0;
	      this.currentState.stageId = this.data.stageId;
	      return this;
	    }
	  }, {
	    key: "savePosition",
	    value: function savePosition() {
	      var _this5 = this;

	      var data = {
	        id: this.getId(),
	        typeId: this.getTypeId(),
	        fields: {
	          stageId: this.getStageId(),
	          previousItemId: this.currentState.previousItemId || null
	        },
	        eventId: this.getGrid().pullManager.registerRandomEventId()
	      };
	      main_core.Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('rpa.item.update', {
	          analyticsLabel: 'rpaItemMoved',
	          data: data
	        }).then(function (response) {
	          _this5.data = response.data.item;

	          if (!_this5.moveToActualColumn()) {
	            _this5.render();
	          }

	          main_core.Dom.removeClass(_this5.layout.container, 'main-kanban-item-waiting');
	          resolve(response);
	        }).catch(function (response) {
	          reject(response);
	          main_core.Dom.removeClass(_this5.layout.container, 'main-kanban-item-waiting');
	        });
	      });
	    }
	  }, {
	    key: "moveToActualColumn",
	    value: function moveToActualColumn() {
	      if (this.getStageId() !== this.getColumn().getId()) {
	        var column = this.getGrid().getColumn(this.getStageId());

	        if (column) {
	          this.getGrid().moveItem(this, column, column.getFirstItem());
	        } else {
	          this.getGrid().moveItem(this, this.getStageId());
	        }

	        return true;
	      }

	      return false;
	    }
	  }, {
	    key: "saveSort",
	    value: function saveSort() {
	      var _this6 = this;

	      var data = {
	        id: this.getId(),
	        typeId: this.getTypeId(),
	        previousItemId: this.currentState.previousItemId
	      };
	      main_core.Dom.addClass(this.layout.container, 'main-kanban-item-waiting');
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('rpa.item.sort', {
	          analyticsLabel: 'rpaItemSorted',
	          data: data
	        }).then(function (response) {
	          main_core.Dom.removeClass(_this6.layout.container, 'main-kanban-item-waiting');
	          resolve(response);
	        }).catch(function (response) {
	          reject(response);
	          main_core.Dom.removeClass(_this6.layout.container, 'main-kanban-item-waiting');
	        });
	      });
	    }
	  }, {
	    key: "getCurrentState",
	    value: function getCurrentState() {
	      return Object.assign({}, this.currentState);
	    }
	  }, {
	    key: "restoreState",
	    value: function restoreState(previousState) {
	      this.currentState = previousState;
	      this.data.stageId = this.currentState.stageId;
	      return this;
	    }
	  }, {
	    key: "onDoubleClick",
	    value: function onDoubleClick() {
	      if (this.data.detailUrl) {
	        rpa_manager.Manager.openSlider(this.data.detailUrl);
	      }
	    }
	  }, {
	    key: "getMovedBy",
	    value: function getMovedBy() {
	      return main_core.Text.toInteger(this.getData().movedBy);
	    }
	  }, {
	    key: "getUpdatedBy",
	    value: function getUpdatedBy() {
	      return main_core.Text.toInteger(this.getData().updatedBy);
	    }
	  }, {
	    key: "getCreatedBy",
	    value: function getCreatedBy() {
	      return main_core.Text.toInteger(this.getData().createdBy);
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.data.name;
	    }
	  }, {
	    key: "renderTitle",
	    value: function renderTitle(title) {
	      title = main_core.Text.encode(title);
	      var href = 'javascript:void(0);';

	      if (this.data.detailUrl) {
	        href = this.data.detailUrl;
	      }

	      if (!this.layout.title) {
	        this.layout.title = main_core.Tag.render(_templateObject2$1(), href, title);
	      } else {
	        this.layout.title.innerText = title;
	      }

	      return this.layout.title;
	    }
	  }, {
	    key: "renderFieldsList",
	    value: function renderFieldsList() {
	      var _this7 = this;

	      if (!this.layout.fieldList) {
	        this.layout.fieldList = main_core.Tag.render(_templateObject3$1());
	      }

	      this.layout.fieldList.innerHTML = '';
	      var fields = this.getGrid().getFields();
	      Object.keys(fields).forEach(function (fieldName) {
	        if (fields[fieldName]['isVisibleOnKanban'] && !_this7.isEmptyValue(_this7.getData()[fieldName])) {
	          if (fields[fieldName]['isTitle']) {
	            _this7.renderTitle(_this7.getData()[fieldName]);
	          } else if (fieldName === 'createdBy' || fieldName === 'updatedBy' || fieldName === 'movedBy') {
	            var renderedUser = _this7.renderUser(fieldName);

	            if (renderedUser) {
	              _this7.layout.fieldList.appendChild(main_core.Tag.render(_templateObject4$1(), main_core.Text.encode(fields[fieldName].title), renderedUser));
	            }
	          } else {
	            _this7.layout.fieldList.appendChild(main_core.Tag.render(_templateObject5$1(), main_core.Text.encode(fields[fieldName].title), _this7.getDisplayableValue(fieldName))); // field with link

	            /*this.layout.fieldList.appendChild(Tag.render`
	            	<div class="rpa-kanban-item-field-item">
	            		<span class="rpa-kanban-item-field-item-name">Link</span>
	            		<a class="rpa-kanban-item-field-item-value-link" href="#">Bitrix Inc.</a>
	            	</div>`
	            );*/

	          }
	        }
	      });
	      return this.layout.fieldList;
	    }
	  }, {
	    key: "renderShadow",
	    value: function renderShadow() {
	      return main_core.Tag.render(_templateObject6$1());
	    }
	  }, {
	    key: "renderDescription",
	    value: function renderDescription() {
	      this.layout.description = main_core.Tag.render(_templateObject7$1());
	    }
	  }, {
	    key: "renderUserPhoto",
	    value: function renderUserPhoto(_ref) {
	      var link = _ref.link,
	          photo = _ref.photo;

	      if (main_core.Type.isString(link) && main_core.Type.isString(photo)) {
	        return main_core.Tag.render(_templateObject8$1(), main_core.Text.encode(link), main_core.Text.encode(photo));
	      }

	      return null;
	    }
	  }, {
	    key: "renderUser",
	    value: function renderUser(fieldName) {
	      var userId = main_core.Text.toInteger(this.getData()[fieldName]);
	      var userInfo = this.getGrid().getUser(userId);

	      if (userInfo) {
	        var photo = this.renderUserPhoto(userInfo);
	        return main_core.Tag.render(_templateObject9$1(), photo ? photo : '', main_core.Text.encode(userInfo.link), main_core.Text.encode(userInfo.fullName));
	      }

	      return null;
	    }
	  }, {
	    key: "renderContact",
	    value: function renderContact() {
	      return main_core.Tag.render(_templateObject10$1());
	    }
	  }, {
	    key: "renderTasksParticipants",
	    value: function renderTasksParticipants() {
	      var _this$getTasksPartici = this.getTasksParticipants(),
	          startedBy = _this$getTasksPartici.startedBy,
	          completedBy = _this$getTasksPartici.completedBy,
	          waitingFor = _this$getTasksPartici.waitingFor,
	          completedCnt = _this$getTasksPartici.completedCnt,
	          waitingForCnt = _this$getTasksPartici.waitingForCnt;

	      var elements = [];

	      if (startedBy) {
	        elements.push(this.renderTaskParticipant(startedBy));
	      }

	      if (completedBy) {
	        elements.push(this.renderTaskParticipant(completedBy, completedCnt > 1));
	      }

	      if (waitingFor) {
	        elements.push(this.renderTaskParticipant(waitingFor, waitingForCnt > 1));
	      }

	      return main_core.Tag.render(_templateObject11$1(), elements);
	    }
	  }, {
	    key: "renderTaskParticipant",
	    value: function renderTaskParticipant(_ref2, isMore) {
	      var link = _ref2.link,
	          photo = _ref2.photo,
	          fullName = _ref2.fullName;
	      return main_core.Tag.render(_templateObject12$1(), isMore ? 'rpa-kanban-column-task-responsible-item-more' : '', main_core.Text.encode(link), main_core.Text.encode(fullName), photo ? "<img src=\"".concat(main_core.Text.encode(photo), "\" alt=\"\">") : '');
	    }
	  }, {
	    key: "renderTasksCounter",
	    value: function renderTasksCounter() {
	      return main_core.Tag.render(_templateObject13$1(), this.showTasks.bind(this), this.getTasksCounter() <= 0 ? 'style="display: none;"' : '', main_core.Loc.getMessage('RPA_KANBAN_TASKS'), this.getTasksCounter());
	    }
	  }, {
	    key: "hasEmptyMandatoryFields",
	    value: function hasEmptyMandatoryFields(column) {
	      var _this8 = this;

	      var result = false;

	      if (!column) {
	        column = this.getStageId();
	      }

	      column = this.getGrid().getColumn(column);

	      if (!column) {
	        throw new Error("Column not found");
	      }

	      var fields = column.getFields();
	      Object.keys(fields).forEach(function (fieldName) {
	        if (fields[fieldName].mandatory && _this8.isEmptyValue(_this8.getData()[fieldName])) {
	          result = true;
	        }
	      });
	      return result;
	    }
	  }, {
	    key: "isEmptyValue",
	    value: function isEmptyValue(value) {
	      return main_core.Type.isNil(value) || value === false || (main_core.Type.isString(value) || main_core.Type.isArray(value)) && value.length <= 0 || main_core.Type.isNumber(value) && value === 0;
	    }
	  }, {
	    key: "update",
	    value: function update(data) {
	      if (main_core.Type.isPlainObject(data) && data.item && main_core.Type.isPlainObject(data.item) && parseInt(data.item.id) === this.getId()) {
	        this.data = data.item;
	        this.processPermissions();
	        this.render();
	      }

	      return this;
	    }
	  }, {
	    key: "processPermissions",
	    value: function processPermissions() {
	      this.setPermissionProperties();
	      this.processDraggingOptions();
	      return this;
	    }
	  }, {
	    key: "setPermissionProperties",
	    value: function setPermissionProperties() {
	      var _this9 = this;

	      var data = this.getData();
	      var permissions = {};

	      if (data.permissions && main_core.Type.isPlainObject(data.permissions)) {
	        permissions = data.permissions;
	      }

	      Object.keys(permissions).forEach(function (name) {
	        _this9[name] = permissions[name];
	      });
	      return this;
	    }
	  }, {
	    key: "processDraggingOptions",
	    value: function processDraggingOptions() {
	      if (this.isDraggable()) {
	        this.makeDraggable();
	      } else {
	        this.disableDragging();
	      }

	      this.render();
	      return this;
	    }
	  }, {
	    key: "getDisplayableValue",
	    value: function getDisplayableValue(fieldName) {
	      var result = null;

	      if (this.data.display && this.data.display[fieldName]) {
	        result = this.data.display[fieldName];
	      } else if (this.data[fieldName]) {
	        result = this.data[fieldName];
	      }

	      if (main_core.Type.isArray(result)) {
	        result = result.join(', ');
	      }

	      return result;
	    }
	  }, {
	    key: "isDeletable",
	    value: function isDeletable() {
	      return this.canDelete !== false;
	    }
	  }]);
	  return Item;
	}(main_kanban.Kanban.Item);

	var Grid = /*#__PURE__*/function (_Kanban$Grid) {
	  babelHelpers.inherits(Grid, _Kanban$Grid);

	  function Grid() {
	    babelHelpers.classCallCheck(this, Grid);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Grid).apply(this, arguments));
	  }

	  babelHelpers.createClass(Grid, [{
	    key: "getTypeId",
	    value: function getTypeId() {
	      return main_core.Text.toInteger(this.getData().typeId);
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return main_core.Text.toInteger(this.getData().userId);
	    }
	  }, {
	    key: "isCreateItemRestricted",
	    value: function isCreateItemRestricted() {
	      return this.getData().isCreateItemRestricted === true;
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemCaptured", this.onBeforeItemCaptured.bind(this));
	      BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemRestored", this.onBeforeItemRestored.bind(this));
	      BX.addCustomEvent("Kanban.Column:render", function (column) {
	        if (column.getGrid() === _this && column instanceof Column) {
	          column.onAfterRender.apply(column);
	        }
	      });
	      BX.addCustomEvent("BX.Main.Filter:apply", this.onApplyFilter.bind(this));
	      BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", function (promises) {
	        promises.push(function (column) {
	          return _this.getColumnItems(column);
	        });
	      });
	      BX.addCustomEvent(this, "Kanban.Grid:onBeforeItemMoved", this.saveItemState);
	      BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", this.onItemMoved);
	      BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", this.onColumnUpdated);
	      BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", this.onColumnMoved);
	      BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", function (promises) {
	        promises.push(function (column) {
	          return _this.addStage(column);
	        });
	      });
	      BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", function (promises) {
	        promises.push(function (column) {
	          return _this.removeStage(column);
	        });
	      });
	      BX.addCustomEvent(window, 'BX.UI.EntityEditorSection:onOpenChildMenu', this.onOpenSelectFieldMenu.bind(this));
	      BX.addCustomEvent('SidePanel.Slider:onMessage', function (message) {
	        if (message.getEventId() === 'userfield-list-update') {
	          _this.onApplyFilter();
	        }
	      });
	      this.pullManager = new PullManager(this);
	    }
	  }, {
	    key: "onBeforeItemCaptured",
	    value: function onBeforeItemCaptured(dropZoneEvent) {
	      var _this2 = this;

	      main_core.Event.EventEmitter.emit('BX.Rpa.Kanban.Grid:onBeforeItemCapturedStart', [this, dropZoneEvent]);
	      var item = dropZoneEvent.getItem();

	      if (!(item instanceof Item)) {
	        return;
	      }

	      if (!dropZoneEvent.isActionAllowed()) {
	        return;
	      }

	      var dropZone = dropZoneEvent.getDropZone();

	      if (dropZone.getId() === 'delete') {
	        if (!item.isDeletable()) {
	          dropZoneEvent.denyAction();
	          return;
	        }

	        if (this.deleteCommand && !this.deleteCommand.isCompleted()) {
	          this.deleteCommand.run();
	        }

	        this.deleteCommand = new Command(item, function (commandItem) {
	          _this2.deleteItem(commandItem);
	        }, function (commandItem) {
	          _this2.unhideItem(commandItem);
	        });
	        this.deleteCommand.start(dropZone.getDropZoneArea().getDropZoneTimeout());
	      } else if (dropZone.getData().isColumn === true) {
	        dropZoneEvent.denyAction();
	        var targetColumn = this.getColumn(dropZone.getId());

	        if (!targetColumn) {
	          item.saveCurrentState();
	          this.hideItem(item);
	        } else {
	          this.moveItem(item, targetColumn);
	        }

	        this.moveItemToStage(item, dropZone.getId(), item.getColumn());
	      }
	    }
	  }, {
	    key: "onBeforeItemRestored",
	    value: function onBeforeItemRestored(dropZoneEvent) {
	      var item = dropZoneEvent.getItem();

	      if (!(item instanceof Item)) {
	        return;
	      }

	      var dropZone = dropZoneEvent.getDropZone();

	      if (dropZone.getId() === 'delete') {
	        if (this.deleteCommand) {
	          this.deleteCommand.cancel();
	          this.deleteCommand = null;
	        }
	      }
	    }
	  }, {
	    key: "saveItemState",
	    value: function saveItemState(dropEvent) {
	      dropEvent.getItem().saveCurrentState();
	    }
	  }, {
	    key: "getFirstColumn",
	    value: function getFirstColumn() {
	      var columns = this.getColumns();

	      if (columns.length > 0) {
	        return columns[0];
	      }

	      return null;
	    }
	  }, {
	    key: "onItemMoved",
	    value: function onItemMoved(item, targetColumn, beforeItem, skipHandler) {
	      var _this3 = this;

	      var itemPreviousState = item.getCurrentState(); // moving in the same column

	      if (parseInt(item.getStageId()) === parseInt(targetColumn.getId())) {
	        if (!beforeItem && item.getCurrentState().nextItemId === 0 || beforeItem && parseInt(beforeItem.getId()) === parseInt(item.getCurrentState().nextItemId)) {
	          // skip moving on the same place
	          this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	          return;
	        } // save sorting


	        item.saveCurrentState().saveSort().catch(function (response) {
	          _this3.onItemMoveError(item, response, itemPreviousState);
	        });
	        return;
	      } // check permissions and next stage


	      var previousColumn = this.getColumn(item.getStageId()); //const isPossibleNextStagesIncludesTargetColumn = previousColumn.getPossibleNextStages().includes(targetColumn.getId());
	      //sorry but for now we do not check possible next stages

	      var isPossibleNextStagesIncludesTargetColumn = true;
	      /*if(!isPossibleNextStagesIncludesTargetColumn && previousColumn.canMoveTo() && item.getMovedBy() === this.getUserId() && targetColumn.getPossibleNextStages().includes(previousColumn.getId()))
	      {
	      	// item is moving back - no editor just moving
	      	item.saveCurrentState().savePosition().catch((response) =>
	      	{
	      		this.onItemMoveError(item, response, itemPreviousState);
	      	});
	      }
	      else */

	      if (previousColumn.isCanMoveFrom() && isPossibleNextStagesIncludesTargetColumn) {
	        this.moveItemToStage(item, targetColumn.getId(), previousColumn);
	      } else if (!previousColumn.isCanMoveFrom() && isPossibleNextStagesIncludesTargetColumn) {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('RPA_KANBAN_MOVE_PERMISSION_NOTIFY').replace('#STAGE#', main_core.Text.encode(previousColumn.getName()))
	        });
	        this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	      } else if (previousColumn.isCanMoveFrom() && !isPossibleNextStagesIncludesTargetColumn) {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('RPA_KANBAN_MOVE_WRONG_STAGE_NOTIFY').replace('#STAGE_FROM#', main_core.Text.encode(previousColumn.getName())).replace('#STAGE_TO#', main_core.Text.encode(targetColumn.getName()))
	        });
	        this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	      } else {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('RPA_KANBAN_MOVE_ITEM_PERMISSION_NOTIFY').replace('#ITEM#', main_core.Text.encode(item.getName())).replace('#STAGE#', main_core.Text.encode(previousColumn.getName()))
	        });
	        this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	      }
	    }
	  }, {
	    key: "moveItemToStage",
	    value: function moveItemToStage(item, targetColumnId, previousColumn) {
	      var _this4 = this;

	      var itemPreviousState = item.getCurrentState();

	      if (item.hasEmptyMandatoryFields(previousColumn)) {
	        if (!previousColumn.canAddItems()) {
	          this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	          main_kanban.Kanban.Utils.showErrorDialog(main_core.Loc.getMessage('RPA_KANBAN_MOVE_EMPTY_MANDATORY_FIELDS_ERROR'), false);
	          return;
	        }

	        item.showEditor(targetColumnId).then(function (response) {
	          _this4.onEditorSave(item, response);
	        }).catch(function (response) {
	          _this4.onItemMoveError(item, response, itemPreviousState);
	        });
	      } else {
	        var targetColumn = this.getColumn(targetColumnId);

	        if (targetColumn) {
	          item.saveCurrentState();
	        } else {
	          item.setStageId(targetColumnId);
	        }

	        item.savePosition().catch(function (response) {
	          var isShowEditor = false;
	          var isShowTasks = false;
	          var isTasksError = false;
	          response.errors.forEach(function (error) {
	            if (error.code && error.code === 'RPA_MANDATORY_FIELD_EMPTY') {
	              // show editor in case we missed some empty mandatory field
	              isShowEditor = true;
	            } else if (error.code && error.code === 'RPA_ITEM_USER_HAS_TASKS') {
	              isShowTasks = true;
	            } else if (error.code && error.code === 'RPA_ITEM_TASKS_NOT_COMPLETED') {
	              isTasksError = true;
	            }
	          });

	          if (isShowEditor) {
	            if (!previousColumn.canAddItems()) {
	              BX.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('RPA_KANBAN_MOVE_ITEM_PERMISSION_NOTIFY').replace('#ITEM#', main_core.Text.encode(item.getName())).replace('#STAGE#', main_core.Text.encode(previousColumn.getName()))
	              });

	              _this4.onItemMoveError(item, null, itemPreviousState);

	              return;
	            }

	            item.showEditor(targetColumnId).then(function (response) {
	              if (response.cancel === true) {
	                _this4.onItemMoveError(item, null, itemPreviousState);
	              }
	            }).catch(function (response) {
	              _this4.onItemMoveError(item, response, itemPreviousState);
	            });
	          } else if (isShowTasks) {
	            item.showTasks().then(function (response) {
	              // move back
	              if (response.isCompleted !== true) {
	                _this4.onItemMoveError(item, null, itemPreviousState);
	              } else {
	                item.update(response);

	                if (!item.moveToActualColumn()) {
	                  item.render();
	                }
	              }
	            }).catch(function (response) {
	              _this4.onItemMoveError(item, response, itemPreviousState);
	            });
	          } else if (isTasksError) {
	            BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('RPA_KANBAN_MOVE_ITEM_HAS_TASKS_ERROR')
	            });

	            _this4.onItemMoveError(item, null, itemPreviousState);
	          } else {
	            _this4.onItemMoveError(item, response, itemPreviousState);
	          }
	        });
	      }
	    }
	  }, {
	    key: "onItemMoveError",
	    value: function onItemMoveError(item) {
	      var response = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var previousState = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

	      if (previousState) {
	        item.restoreState(previousState);

	        if (!item.isVisible()) {
	          this.unhideItem(item);
	        }

	        this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	      }

	      if (response) {
	        this.showErrorFromResponse(response);
	      }
	    }
	  }, {
	    key: "onEditorSave",
	    value: function onEditorSave(item, response) {
	      if (response.cancel === true) {
	        this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
	      }
	    }
	  }, {
	    key: "showErrorFromResponse",
	    value: function showErrorFromResponse(response) {
	      var fatal = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var errors = null;

	      if (main_core.Type.isPlainObject(response) && response.errors && main_core.Type.isArray(response.errors)) {
	        errors = response.errors;
	      } else if (main_core.Type.isArray(response)) {
	        errors = response;
	      }

	      var message = '';

	      if (main_core.Type.isArray(errors)) {
	        errors.forEach(function (error) {
	          message += error.message + "\n";
	        });
	      } else {
	        message = 'Unknown error';
	      }

	      main_kanban.Kanban.Utils.showErrorDialog(message, fatal);
	    }
	  }, {
	    key: "onColumnUpdated",
	    value: function onColumnUpdated(column) {
	      var _this5 = this;

	      this.startProgress();
	      main_core.ajax.runAction('rpa.stage.update', {
	        analyticsLabel: 'rpaKanbanStageUpdate',
	        data: {
	          id: column.getId(),
	          fields: {
	            name: column.getName(),
	            color: column.getColor()
	          },
	          eventId: this.pullManager.registerRandomEventId()
	        },
	        getParameters: {
	          context: 'kanban'
	        }
	      }).then(function (response) {
	        _this5.stopProgress();

	        column.update(response.data);
	      }).catch(function (response) {
	        _this5.stopProgress();

	        _this5.showErrorFromResponse(response, true);
	      });
	    }
	  }, {
	    key: "addStage",
	    value: function addStage(column) {
	      var _this6 = this;

	      this.startProgress();
	      var previousColumn = this.getPreviousColumnSibling(column);
	      var previousColumnId = previousColumn ? previousColumn.getId() : 0;
	      var promise = new BX.Promise();
	      main_core.ajax.runAction('rpa.stage.add', {
	        analyticsLabel: 'rpaKanbanStageAdd',
	        data: {
	          fields: {
	            name: column.getName(),
	            color: column.getColor(),
	            previousStageId: previousColumnId,
	            typeId: this.getTypeId()
	          },
	          eventId: this.pullManager.registerRandomEventId()
	        },
	        getParameters: {
	          context: 'kanban'
	        }
	      }).then(function (response) {
	        promise.fulfill(_this6.transformColumnActionResponseToColumnOptions(response));

	        _this6.stopProgress();
	      }).catch(function (response) {
	        var error = response.errors.pop().message;
	        promise.reject(error);

	        _this6.stopProgress();
	      });
	      return promise;
	    }
	  }, {
	    key: "removeStage",
	    value: function removeStage(column) {
	      var _this7 = this;

	      var promise = new BX.Promise();
	      this.startProgress();
	      main_core.ajax.runAction('rpa.stage.delete', {
	        analyticsLabel: 'rpaKanbanStageDelete',
	        data: {
	          id: column.getId()
	        },
	        getParameters: {
	          context: 'kanban'
	        }
	      }).then(function () {
	        _this7.stopProgress();

	        promise.fulfill();
	      }).catch(function (response) {
	        _this7.stopProgress();

	        var error = response.errors.pop().message;
	        column.enableDragging();
	        column.getContainer().classList.remove("main-kanban-column-edit-mode");
	        promise.reject(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "onColumnMoved",
	    value: function onColumnMoved(column) {
	      var _this8 = this;

	      var previousColumn = this.getPreviousColumnSibling(column);
	      var previousColumnId = previousColumn ? previousColumn.getId() : 0;
	      main_core.ajax.runAction('rpa.stage.update', {
	        analyticsLabel: 'rpaKanbanStageMove',
	        data: {
	          id: column.getId(),
	          fields: {
	            previousStageId: previousColumnId
	          },
	          eventId: this.pullManager.registerRandomEventId()
	        },
	        getParameters: {
	          context: 'kanban'
	        }
	      }).then(function (response) {
	        var wasFirst = column.isFirstColumn();
	        var isFirst = true;
	        column.update(response.data);

	        if (column.isFirstColumn() && wasFirst) {
	          return;
	        }

	        if (column.isFirstColumn() || wasFirst) {
	          _this8.getColumns().forEach(function (renderedColumn) {
	            if (renderedColumn !== column) {
	              renderedColumn.setIsFirstColumn(wasFirst && isFirst);
	              isFirst = false;
	            }

	            renderedColumn.rerenderSubtitle();
	          });
	        }

	        _this8.getFirstColumn().rerenderSubtitle();
	      }).catch(function (response) {
	        _this8.showErrorFromResponse(response, true);
	      });
	    }
	  }, {
	    key: "transformColumnActionResponseToColumnOptions",
	    value: function transformColumnActionResponseToColumnOptions(response) {
	      return {
	        id: response.data.stage.id,
	        name: response.data.stage.name,
	        color: response.data.stage.color,
	        total: response.data.stage.total,
	        data: response.data.stage
	      };
	    }
	  }, {
	    key: "getColumnItems",
	    value: function getColumnItems(column) {
	      var page = column.getPagination().page + 1;
	      var size = this.getData().pageSize;
	      var promise = new BX.Promise();
	      main_core.ajax.runComponentAction('bitrix:rpa.kanban', 'getColumn', {
	        mode: 'class',
	        analyticsLabel: 'rpaKanbanPagination',
	        signedParameters: this.getData().signedParameters,
	        data: {
	          stageId: column.getId()
	        },
	        navigation: {
	          page: page,
	          size: size
	        }
	      }).then(function (response) {
	        var items = [];
	        response.data.items.forEach(function (itemData) {
	          items.push({
	            id: itemData.id,
	            columnId: itemData.stageId,
	            name: itemData.name,
	            data: itemData
	          });
	        });
	        promise.fulfill(items);
	      }).catch(function (response) {
	        var error = response.errors.pop().message;
	        promise.reject(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "insertItem",
	    value: function insertItem(item) {
	      if (!(item instanceof Item)) {
	        return;
	      }

	      var beforeItem = null;
	      var newColumn = this.getColumn(item.getStageId());

	      if (newColumn) {
	        beforeItem = newColumn.getFirstItem();
	        this.moveItem(item, item.getStageId(), beforeItem);
	        item.processPermissions();
	      } else {
	        this.removeItem(item);
	      }
	    }
	  }, {
	    key: "onApplyFilter",
	    value: function onApplyFilter(filterId, values, filterInstance, promise, params) {
	      var _this9 = this;

	      if (main_core.Type.isPlainObject(params)) {
	        params.autoResolve = false;
	      }

	      this.startProgress();
	      main_core.ajax.runComponentAction('bitrix:rpa.kanban', 'get', {
	        analyticsLabel: 'rpaKanbanApplyFilter',
	        signedParameters: this.getData().signedParameters,
	        mode: 'class'
	      }).then(function (response) {
	        _this9.stopProgress();

	        _this9.getColumns().forEach(function (column) {
	          var pagination = column.getPagination();

	          if (pagination) {
	            pagination.page = 1;
	          }
	        });

	        _this9.getColumns().forEach(function (column) {
	          _this9.removeColumn(column);
	        });

	        _this9.removeItems();

	        _this9.loadData(response.data.kanban);

	        if (!main_core.Type.isNil(promise)) {
	          promise.fulfill();
	        }
	      }).catch(function (response) {
	        _this9.stopProgress();

	        _this9.showErrorFromResponse(response);

	        if (!main_core.Type.isNil(promise)) {
	          promise.reject();
	        }
	      });
	    }
	  }, {
	    key: "loadData",
	    value: function loadData(json) {
	      if (main_core.Type.isPlainObject(json.data)) {
	        this.addUsers(json.data.users);
	        this.data.fields = json.data.fields;
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "loadData", this).call(this, json);
	    }
	  }, {
	    key: "addUsers",
	    value: function addUsers(users) {
	      var _this10 = this;

	      if (main_core.Type.isPlainObject(users)) {
	        if (!this.users) {
	          this.users = new Map();
	        }

	        Object.keys(users).forEach(function (userId) {
	          userId = main_core.Text.toInteger(userId);

	          if (userId > 0) {
	            _this10.users.set(userId, users[userId]);
	          }
	        });
	      }
	    }
	  }, {
	    key: "getUser",
	    value: function getUser(userId) {
	      if (!this.users) {
	        this.users = new Map();
	      }

	      return this.users.get(userId);
	    }
	  }, {
	    key: "getFields",
	    value: function getFields() {
	      var fields = this.getData().fields;

	      if (!fields || !main_core.Type.isPlainObject(fields)) {
	        fields = {};
	      }

	      return fields;
	    }
	  }, {
	    key: "onOpenSelectFieldMenu",
	    value: function onOpenSelectFieldMenu(editor, params) {
	      params.cancel = true;
	      var popupId = 'rpa-kanban-column-select-fields-menu-' + this.getTypeId();
	      var popup = main_popup.PopupWindowManager.getPopupById(popupId);

	      if (!popup) {
	        popup = new main_popup.PopupMenuWindow({
	          id: 'rpa-kanban-column-select-fields-menu-' + this.getTypeId(),
	          bindElement: params.button,
	          items: [{
	            text: main_core.Loc.getMessage('RPA_KANBAN_FIELDS_VIEW_SETTINGS'),
	            onclick: this.onSelectFieldsViewSettingsClick.bind(this)
	          }, {
	            text: main_core.Loc.getMessage('RPA_KANBAN_FIELDS_MODIFY_SETTINGS'),
	            onclick: this.onSelectFieldsModifySettingsClick.bind(this)
	          }],
	          autoHide: true,
	          closeByEsc: true,
	          cacheable: false
	        });
	      } else {
	        popup.setBindElement(params.button);
	      }

	      popup.show();
	    }
	  }, {
	    key: "onSelectFieldsViewSettingsClick",
	    value: function onSelectFieldsViewSettingsClick() {
	      var _this11 = this;

	      if (!this.canAddColumns()) {
	        return;
	      }

	      var fields = this.getFields();
	      var data = [];
	      Object.keys(fields).forEach(function (fieldName) {
	        data.push({
	          title: fields[fieldName].title,
	          name: fieldName,
	          checked: fields[fieldName].isVisibleOnKanban
	        });
	      });
	      var fieldsPopup = new rpa_fieldspopup.FieldsPopup('rpa-kanban-view-' + this.getTypeId(), data, main_core.Loc.getMessage('RPA_KANBAN_FIELDS_VIEW_SETTINGS'));
	      fieldsPopup.show().then(function (result) {
	        if (result !== false) {
	          if (_this11.isProgress()) {
	            return;
	          }

	          _this11.startProgress();

	          main_core.ajax.runAction('rpa.fields.setVisibilitySettings', {
	            analyticsLabel: 'rpaKanbanSaveVisibleFields',
	            data: {
	              typeId: _this11.getTypeId(),
	              fields: Array.from(result),
	              visibility: 'kanban'
	            }
	          }).then(function (response) {
	            _this11.stopProgress();

	            Object.keys(_this11.getFields()).forEach(function (fieldName) {
	              _this11.data.fields[fieldName]['isVisibleOnKanban'] = result.has(fieldName);
	            });

	            _this11.getColumns().forEach(function (column) {
	              column.getItems().forEach(function (item) {
	                item.render();
	              });
	            });
	          }).catch(function (response) {
	            _this11.stopProgress();

	            _this11.showErrorFromResponse(response);
	          });
	        }
	      });
	    }
	  }, {
	    key: "onSelectFieldsModifySettingsClick",
	    value: function onSelectFieldsModifySettingsClick() {
	      var _this12 = this;

	      if (!this.canAddColumns()) {
	        return;
	      }

	      var firstColumn = this.getFirstColumn();

	      if (!firstColumn) {
	        return;
	      }

	      var editor = firstColumn.getEditor();

	      if (!editor) {
	        return;
	      }

	      var fields = this.getFields();
	      var data = [];
	      Object.keys(fields).forEach(function (fieldName) {
	        if (fields[fieldName].canBeEdited) {
	          data.push({
	            title: fields[fieldName].title,
	            name: fieldName,
	            checked: !!editor.getControlById(fieldName)
	          });
	        }
	      });
	      var fieldsPopup = new rpa_fieldspopup.FieldsPopup('rpa-kanban-edit-' + this.getTypeId(), data, main_core.Loc.getMessage('RPA_KANBAN_FIELDS_MODIFY_SETTINGS'));
	      fieldsPopup.show().then(function (result) {
	        if (!result) {
	          return;
	        }

	        if (_this12.isProgress()) {
	          return;
	        }

	        _this12.startProgress();

	        main_core.ajax.runAction('rpa.fields.setVisibilitySettings', {
	          data: {
	            typeId: _this12.getTypeId(),
	            fields: Array.from(result),
	            visibility: 'create'
	          },
	          analyticsLabel: 'rpaKanbanSaveCreateFields'
	        }).then(function (response) {
	          _this12.stopProgress();

	          _this12.syncEditorFields(editor, result);
	        }).catch(function (response) {
	          _this12.stopProgress();

	          _this12.showErrorFromResponse(response);
	        });
	      });
	    }
	  }, {
	    key: "syncEditorFields",
	    value: function syncEditorFields(editor, availableFields) {
	      var fields = this.getFields();
	      var editorMainSection = this.getEditorMainSection(editor);

	      if (!editorMainSection) {
	        return;
	      }

	      Object.keys(fields).forEach(function (fieldName) {
	        var control = editor.getControlById(fieldName);

	        if (control && !availableFields.has(fieldName)) {
	          editorMainSection.removeChild(control, {
	            enableSaving: false
	          });
	        } else if (!control && availableFields.has(fieldName)) {
	          var element = editor.getAvailableSchemeElementByName(fieldName);

	          if (element) {
	            var field = editor.createControl(element.getType(), element.getName(), {
	              schemeElement: element,
	              model: editor._model,
	              mode: editor._mode
	            });

	            if (field) {
	              editorMainSection.addChild(field, {
	                layout: {
	                  forceDisplay: true
	                },
	                enableSaving: false
	              });
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "getEditorMainSection",
	    value: function getEditorMainSection(editor) {
	      var editorMainSection = editor.getControlById('main');

	      if (editorMainSection instanceof BX.UI.EntityEditorColumn) {
	        editorMainSection = editorMainSection.getChildById('main');
	      }

	      return editorMainSection;
	    }
	  }, {
	    key: "isProgress",
	    value: function isProgress() {
	      return this.progress === true;
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      this.progress = true;
	      this.showLoader().fadeOut();
	      return this;
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      this.progress = false;
	      this.hideLoader().fadeIn();
	      return this;
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      this.getLoader().style.display = 'block';
	      return this;
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      this.getLoader().style.display = 'none';
	      return this;
	    }
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(item) {
	      var _this13 = this;

	      if (this.isProgress()) {
	        return;
	      }

	      this.startProgress();
	      main_core.ajax.runAction('rpa.item.delete', {
	        analyticsLabel: 'rpaKanbanItemDelete',
	        data: {
	          typeId: this.getTypeId(),
	          id: item.getId()
	        }
	      }).then(function () {
	        if (_this13.getItem(item)) {
	          item.getColumn().removeItem(item);
	        }

	        _this13.stopProgress();
	      }).catch(function (response) {
	        _this13.stopProgress();

	        _this13.showErrorFromResponse(response);
	      });
	    }
	  }]);
	  return Grid;
	}(main_kanban.Kanban.Grid);

	var Command = /*#__PURE__*/function () {
	  function Command(item, action, restore) {
	    babelHelpers.classCallCheck(this, Command);
	    this.item = item;
	    this.action = action;
	    this.restore = restore;
	    this.timeoutId = null;
	  }

	  babelHelpers.createClass(Command, [{
	    key: "start",
	    value: function start(timeout) {
	      this.timeoutId = setTimeout(this.run.bind(this), timeout);
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      clearTimeout(this.timeoutId);
	      this.timeoutId = null;

	      if (main_core.Type.isFunction(this.action)) {
	        this.action(this.item);
	      }
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      clearTimeout(this.timeoutId);
	      this.timeoutId = null;

	      if (main_core.Type.isFunction(this.restore)) {
	        this.restore(this.item);
	      }
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return !(this.timeoutId > 0);
	    }
	  }]);
	  return Command;
	}();

	var Kanban = {
	  Grid: Grid,
	  Item: Item,
	  Column: Column
	};

	exports.Kanban = Kanban;

}((this.BX.Rpa = this.BX.Rpa || {}),BX.UI,BX,BX,BX.Rpa,BX,BX.Rpa,BX.UI.Dialogs,BX.Main,BX.Rpa));
//# sourceMappingURL=kanban.bundle.js.map

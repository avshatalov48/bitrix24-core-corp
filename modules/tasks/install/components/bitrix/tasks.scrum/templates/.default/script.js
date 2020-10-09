this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_label,ui_entitySelector,main_popup,ui_buttons,ui_draganddrop_draggable,ui_dialogs_messagebox,main_core,main_core_events) {
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
	    value: function sendRequest() {
	      var _this = this;

	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var action = arguments.length > 1 ? arguments[1] : undefined;
	      data.debugMode = this.debugMode;
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction('bitrix:tasks.scrum', action, {
	          mode: 'class',
	          signedParameters: _this.signedParameters,
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "updateItemSort",
	    value: function updateItemSort(data) {
	      return this.sendRequest(data, 'updateItemSort');
	    }
	  }, {
	    key: "updateSprintSort",
	    value: function updateSprintSort(data) {
	      return this.sendRequest(data, 'updateSprintSort');
	    }
	  }, {
	    key: "createSprint",
	    value: function createSprint(data) {
	      return this.sendRequest(data, 'createSprint');
	    }
	  }, {
	    key: "startSprint",
	    value: function startSprint(data) {
	      return this.sendRequest(data, 'startSprint');
	    }
	  }, {
	    key: "completeSprint",
	    value: function completeSprint(data) {
	      return this.sendRequest(data, 'completeSprint');
	    }
	  }, {
	    key: "createTask",
	    value: function createTask(data) {
	      return this.sendRequest(data, 'createTask');
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem(data) {
	      return this.sendRequest(data, 'updateItem');
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(data) {
	      return this.sendRequest(data, 'removeItem');
	    }
	  }, {
	    key: "changeTaskResponsible",
	    value: function changeTaskResponsible(data) {
	      return this.sendRequest(data, 'changeTaskResponsible');
	    }
	  }, {
	    key: "removeSprint",
	    value: function removeSprint(data) {
	      return this.sendRequest(data, 'removeSprint');
	    }
	  }, {
	    key: "changeSprintName",
	    value: function changeSprintName(data) {
	      return this.sendRequest(data, 'changeSprintName');
	    }
	  }, {
	    key: "changeSprintDeadline",
	    value: function changeSprintDeadline(data) {
	      return this.sendRequest(data, 'changeSprintDeadline');
	    }
	  }, {
	    key: "getEpicDescriptionEditor",
	    value: function getEpicDescriptionEditor(data) {
	      return this.sendRequest(data, 'getEpicDescriptionEditor');
	    }
	  }, {
	    key: "getEpicDescription",
	    value: function getEpicDescription(data) {
	      return this.sendRequest(data, 'getEpicDescription');
	    }
	  }, {
	    key: "getEpicFiles",
	    value: function getEpicFiles(data) {
	      return this.sendRequest(data, 'getEpicFiles');
	    }
	  }, {
	    key: "getAddEpicFormButtons",
	    value: function getAddEpicFormButtons(data) {
	      return this.sendRequest(data, 'getAddEpicFormButtons');
	    }
	  }, {
	    key: "getViewEpicFormButtonsAction",
	    value: function getViewEpicFormButtonsAction(data) {
	      return this.sendRequest(data, 'getViewEpicFormButtons');
	    }
	  }, {
	    key: "createEpic",
	    value: function createEpic(data) {
	      return this.sendRequest(data, 'createEpic');
	    }
	  }, {
	    key: "getEpicsList",
	    value: function getEpicsList(data) {
	      return this.sendRequest(data, 'getEpicsList');
	    }
	  }, {
	    key: "getEpicListUrl",
	    value: function getEpicListUrl() {
	      return '/bitrix/services/main/ajax.php?mode=class&c=bitrix:tasks.scrum&action=getEpicsList';
	    }
	  }, {
	    key: "attachFilesToTask",
	    value: function attachFilesToTask(data) {
	      return this.sendRequest(data, 'attachFilesToTask');
	    }
	  }, {
	    key: "attachTagToTask",
	    value: function attachTagToTask(data) {
	      return this.sendRequest(data, 'attachTagToTask');
	    }
	  }, {
	    key: "deAttachTagToTask",
	    value: function deAttachTagToTask(data) {
	      return this.sendRequest(data, 'deAttachTagToTask');
	    }
	  }, {
	    key: "attachEpicToItem",
	    value: function attachEpicToItem(data) {
	      return this.sendRequest(data, 'attachEpicToItem');
	    }
	  }, {
	    key: "deAttachEpicToItem",
	    value: function deAttachEpicToItem(data) {
	      return this.sendRequest(data, 'deAttachEpicToItem');
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic(data) {
	      return this.sendRequest(data, 'getEpic');
	    }
	  }, {
	    key: "editEpic",
	    value: function editEpic(data) {
	      return this.sendRequest(data, 'editEpic');
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic(data) {
	      return this.sendRequest(data, 'removeEpic');
	    }
	  }, {
	    key: "applyFilter",
	    value: function applyFilter(data) {
	      return this.sendRequest(data, 'applyFilter');
	    }
	  }]);
	  return RequestSender;
	}();

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-actions-header\">\n\t\t\t\t<div class=\"tasks-scrum-actions\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-group\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-epic\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ActionsHeader = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ActionsHeader, _EventEmitter);

	  function ActionsHeader(entity) {
	    var _this;

	    babelHelpers.classCallCheck(this, ActionsHeader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ActionsHeader).call(this, entity));

	    _this.setEventNamespace('BX.Tasks.Scrum.ActionsHeader');

	    _this.entity = entity;
	    _this.headerId = _this.entity.getEntityType() + '-' + _this.entity.getId();
	    return _this;
	  }

	  babelHelpers.createClass(ActionsHeader, [{
	    key: "createActionsHeader",
	    value: function createActionsHeader() {
	      if (this.entity.getEntityType() === 'sprint') {
	        return '';
	      }

	      this.nodeId = 'tasks-scrum-actions-header-' + this.headerId;

	      var getAddEpicActionNode = function getAddEpicActionNode() {
	        return main_core.Tag.render(_templateObject(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD'));
	      };

	      var getGroupActions = function getGroupActions() {
	        return '';
	        return main_core.Tag.render(_templateObject2(), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_GROUP'));
	      };

	      return main_core.Tag.render(_templateObject3(), this.nodeId, getAddEpicActionNode(), getGroupActions());
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this2 = this;

	      if (this.entity.isDisabled() || this.entity.getEntityType() === 'sprint') {
	        return;
	      }

	      this.node = document.getElementById(this.nodeId);
	      var addEpicNode = this.node.querySelector('.tasks-scrum-action-epic');
	      main_core.Event.bind(addEpicNode, 'click', function () {
	        return _this2.emit('openAddEpicForm');
	      });
	    }
	  }]);
	  return ActionsHeader;
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

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-task\">\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-icon\"></span>\n\t\t\t\t\t<span class=\"tasks-scrum-actions-panel-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-actions-panel-separator\"></div>\n\t\t\t"]);

	  _templateObject$1 = function _templateObject() {
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
	      var actionsPanelContainer = this.createActionPanel();
	      this.setBlockBlurNode(actionsPanelContainer);
	      var position = main_core.Dom.getPosition(this.bindElement);
	      actionsPanelContainer.style.top = "".concat(position.top, "px");
	      actionsPanelContainer.style.left = "".concat(position.left, "px");
	      actionsPanelContainer.style.width = "".concat(position.width, "px");
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
	        task = main_core.Tag.render(_templateObject$1(), this.showTaskActionButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK'));
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
	  }]);
	  return ActionsPanel;
	}();

	function _templateObject$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"files_chooser\">\n\t\t\t<div id=\"diskuf-selectdialog-", "\" class=\"diskuf-files-entity diskuf-selectdialog bx-disk\">\n\t\t\t\t<div class=\"diskuf-files-block\">\n\t\t\t\t\t<div class=\"diskuf-placeholder\">\n\t\t\t\t\t\t<table class=\"files-list\">\n\t\t\t\t\t\t\t<tbody class=\"diskuf-placeholder-tbody\"></tbody>\n\t\t\t\t\t\t</table>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"diskuf-extended\" style=\"display: block\">\n\t\t\t\t\t<input type=\"hidden\" name=\"[", "][]\" value=\"\"/>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<label for=\"file_loader_", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<input class=\"diskuf-fileUploader\" id=\"file_loader_", "\" type=\n\t\t\t\t\t\t\t\"file\" multiple=\"multiple\" size=\"1\" style=\"display: none\"/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"diskuf-extended-item\">\n\t\t\t\t\t\t<span class=\"diskuf-selector-link-cloud\" data-bx-doc-handler=\"gdrive\">\n\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$2 = function _templateObject() {
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
	      var filesChooser = main_core.Tag.render(_templateObject$2(), controlId, controlId, controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_COMPUTER'), controlId, main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_B24'), main_core.Loc.getMessage('TASKS_SCRUM_FILES_LOADER_POPUP_FROM_CLOUD'));
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

	function _templateObject$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span id=\"", "\" class=\"task-title-indicators\">\n\t\t\t\t<div class=\"task-attachment-counter ui-label ui-label-sm ui-label-light\">\n\t\t\t\t\t<span class=\"ui-label-inner\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class='task-checklist-counter ui-label ui-label-sm ui-label-light'>\n\t\t\t\t\t<span class='ui-label-inner'>", "/", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class='task-comments-counter'>\n\t\t\t\t\t<div class='ui-counter ui-counter-success'>\n\t\t\t\t\t\t<div class='ui-counter-inner'>", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</span>\n\t\t"]);

	  _templateObject$3 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TaskCounts = /*#__PURE__*/function () {
	  function TaskCounts(options) {
	    babelHelpers.classCallCheck(this, TaskCounts);
	    this.itemId = options.itemId ? options.itemId : main_core.Text.getRandom();
	    this.attachedFilesCount = options.attachedFilesCount ? parseInt(options.attachedFilesCount, 10) : 0;
	    this.checkListComplete = options.checkListComplete ? parseInt(options.checkListComplete, 10) : 0;
	    this.checkListAll = options.checkListAll ? parseInt(options.checkListAll, 10) : 0;
	    this.newCommentsCount = options.newCommentsCount ? parseInt(options.newCommentsCount, 10) : 0;
	  }

	  babelHelpers.createClass(TaskCounts, [{
	    key: "createIndicators",
	    value: function createIndicators() {
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

	function _templateObject3$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-tags-container\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$2 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-item-tags-container\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div data-item-id=\"", "\" data-sort=\n\t\t\t\t\"", "\" class=\"tasks-scrum-item\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-item-inner\">\n\t\t\t\t\t<div class=\"tasks-scrum-item-name\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-item-name-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-no-border\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-element\" contenteditable=\"false\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-item-params\">\n\t\t\t\t\t\t<div class=\"ui-icon ui-icon-common-user tasks-scrum-item-responsible\"><i></i></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-item-story-points\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-item-story-points-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-auto ui-ctl-no-border\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element\" contenteditable=\"false\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$4 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Item = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Item, _EventEmitter);

	  function Item(item) {
	    var _this;

	    babelHelpers.classCallCheck(this, Item);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item).call(this, item));

	    _this.setEventNamespace('BX.Tasks.Scrum.Item');

	    _this.itemNode = null;
	    _this.itemId = item.itemId ? item.itemId : main_core.Text.getRandom();
	    _this.itemType = item.itemType;
	    _this.name = item.name;
	    _this.responsible = item.responsible ? item.responsible : '';
	    _this.entityId = item.entityId;
	    _this.entityType = item.entityType;
	    _this.parentId = item.parentId ? item.parentId : 0;
	    _this.sort = item.sort;
	    _this.storyPoints = main_core.Type.isUndefined(item.storyPoints) ? '' : item.storyPoints;
	    _this.sourceId = item.sourceId ? item.sourceId : 0;
	    _this.parentSourceId = item.parentSourceId ? item.parentSourceId : 0;

	    if (_this.itemType === 'task') {
	      _this.taskCounts = new TaskCounts(item);
	    }

	    _this.completed = item.completed === 'Y';
	    _this.epic = main_core.Type.isPlainObject(item.epic) ? item.epic : null;
	    _this.tags = main_core.Type.isArray(item.tags) ? item.tags : [];
	    return _this;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "setDisableStatus",
	    value: function setDisableStatus(status) {
	      this.disableStatus = status;

	      if (this.itemNode) {
	        if (status) {
	          this.hideNode(this.itemNode.querySelector('.tasks-scrum-dragndrop'));
	        } else {
	          this.showNode(this.itemNode.querySelector('.tasks-scrum-dragndrop'));
	        }
	      }
	    }
	  }, {
	    key: "setMoveActivity",
	    value: function setMoveActivity(value) {
	      this.moveActivity = value;
	    }
	  }, {
	    key: "activateDecompositionMode",
	    value: function activateDecompositionMode() {
	      this.decompositionMode = true;
	      main_core.Dom.addClass(this.itemNode, 'tasks-scrum-item-decomposition-mode');
	    }
	  }, {
	    key: "deactivateDecompositionMode",
	    value: function deactivateDecompositionMode() {
	      this.decompositionMode = false;
	      main_core.Dom.removeClass(this.itemNode, 'tasks-scrum-item-decomposition-mode');
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return Boolean(this.disableStatus);
	    }
	  }, {
	    key: "getItemId",
	    value: function getItemId() {
	      return this.itemId;
	    }
	  }, {
	    key: "getItemType",
	    value: function getItemType() {
	      return this.itemType;
	    }
	  }, {
	    key: "getItemNode",
	    value: function getItemNode() {
	      return this.itemNode;
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.sort;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.sort = parseInt(sort, 10);
	    }
	  }, {
	    key: "getParentId",
	    value: function getParentId() {
	      return this.parentId;
	    }
	  }, {
	    key: "setParentId",
	    value: function setParentId(parentId) {
	      this.parentId = parseInt(parentId, 10);
	    }
	  }, {
	    key: "getSourceId",
	    value: function getSourceId() {
	      return this.sourceId;
	    }
	  }, {
	    key: "setSourceId",
	    value: function setSourceId(sourceId) {
	      this.sourceId = parseInt(sourceId, 10);
	    }
	  }, {
	    key: "getParentSourceId",
	    value: function getParentSourceId() {
	      return this.parentSourceId;
	    }
	  }, {
	    key: "setParentSourceId",
	    value: function setParentSourceId(sourceId) {
	      this.parentSourceId = parseInt(sourceId, 10);
	    }
	  }, {
	    key: "setParentEntity",
	    value: function setParentEntity(entityId, entityType) {
	      this.entityId = entityId;
	      this.entityType = entityType;
	    }
	  }, {
	    key: "getEntityId",
	    value: function getEntityId() {
	      return this.entityId;
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.itemNode);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var dragnDropNode = this.isDisabled() ? '' : '<div class="tasks-scrum-dragndrop"></div>';
	      return main_core.Tag.render(_templateObject$4(), main_core.Text.encode(this.itemId), main_core.Text.encode(this.sort), dragnDropNode, main_core.Text.encode(this.name), this.taskCounts.createIndicators(), main_core.Text.encode(this.storyPoints), this.getTagsContainer());
	    }
	  }, {
	    key: "getEpicTag",
	    value: function getEpicTag() {
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
	        size: ui_label.Label.Size.SM,
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
	      return container;
	    }
	  }, {
	    key: "getListTagNodes",
	    value: function getListTagNodes() {
	      return this.tags.map(function (tag) {
	        var tagLabel = new ui_label.Label({
	          text: tag,
	          color: ui_label.Label.Color.TAG_LIGHT,
	          fill: true,
	          size: ui_label.Label.Size.SM,
	          customClass: ''
	        });
	        return tagLabel.getContainer();
	      });
	    }
	  }, {
	    key: "getTagsContainer",
	    value: function getTagsContainer() {
	      if (this.epic === null && this.tags.length === 0) {
	        return '';
	      }

	      return main_core.Tag.render(_templateObject2$2(), this.getEpicTag(), this.getListTagNodes());
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend(container) {
	      this.itemNode = container.querySelector('[data-item-id="' + this.itemId + '"]');
	      var nameNode = this.itemNode.querySelector('.tasks-scrum-item-name');
	      main_core.Event.bind(nameNode, 'click', this.onNameClick.bind(this));
	      var tagsNode = this.itemNode.querySelector('.tasks-scrum-item-tags-container');
	      main_core.Event.bind(tagsNode, 'click', this.onTagsClick.bind(this));
	      var responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');
	      main_core.Event.bind(responsibleNode, 'click', this.onResponsibleClick.bind(this));
	      var storyPointsNode = this.itemNode.querySelector('.tasks-scrum-item-story-points');
	      main_core.Event.bind(storyPointsNode, 'click', this.onStoryPointsClick.bind(this));

	      if (this.completed) {
	        var nameTextNode = nameNode.querySelector('.ui-ctl-element');
	        main_core.Dom.style(nameTextNode, 'textDecoration', 'line-through');
	        this.setDisableStatus(true);
	      }

	      this.updateResponsible();

	      if (this.taskCounts) {
	        this.taskCounts.onAfterAppend();
	      }
	    }
	  }, {
	    key: "isShowIndicators",
	    value: function isShowIndicators() {
	      return this.attachedFilesCount || this.checkListAll || this.newCommentsCount;
	    }
	  }, {
	    key: "onNameClick",
	    value: function onNameClick(event) {
	      var _this2 = this;

	      var targetNode = event.target;

	      if (main_core.Dom.hasClass(targetNode, 'ui-ctl-element') && targetNode.contentEditable === 'true') {
	        return;
	      }

	      this.showActionsPanel(event);

	      if (!main_core.Dom.hasClass(targetNode, 'ui-ctl-element') || this.isDisabled()) {
	        return;
	      }

	      var nameNode = event.currentTarget;
	      var borderNode = nameNode.querySelector('.ui-ctl');
	      var valueNode = nameNode.querySelector('.ui-ctl-element');
	      valueNode.textContent = valueNode.textContent.trim();
	      var oldValue = valueNode.textContent;
	      main_core.Dom.addClass(this.itemNode, 'tasks-scrum-item-edit-mode');
	      main_core.Dom.toggleClass(borderNode, 'ui-ctl-no-border');
	      valueNode.contentEditable = 'true';
	      this.placeCursorAtEnd(valueNode);
	      main_core.Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));
	      main_core.Event.bindOnce(valueNode, 'blur', function () {
	        main_core.Event.unbind(valueNode, 'keydown', _this2.blockEnterInput.bind(valueNode));
	        main_core.Dom.removeClass(_this2.itemNode, 'tasks-scrum-item-edit-mode');
	        main_core.Dom.addClass(borderNode, 'ui-ctl-no-border');
	        valueNode.contentEditable = 'false';
	        var newValue = valueNode.textContent.trim();

	        if (oldValue === newValue) {
	          return;
	        }

	        _this2.emit('updateItem', {
	          itemId: _this2.itemId,
	          itemType: _this2.itemType,
	          name: newValue
	        });

	        _this2.name = newValue;
	      }, true);
	    }
	  }, {
	    key: "onTagsClick",
	    value: function onTagsClick(event) {
	      this.showActionsPanel(event);
	    }
	  }, {
	    key: "onResponsibleClick",
	    value: function onResponsibleClick(event) {
	      var _this3 = this;

	      if (this.isDisabled()) {
	        return;
	      }

	      var responsibleNode = event.currentTarget;
	      var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
	        scope: responsibleNode,
	        id: 'tasks-scrum-change-responsible-' + this.itemId,
	        mode: 'user',
	        query: false,
	        useSearch: true,
	        useAdd: false,
	        parent: this,
	        popupOffsetLeft: 10
	      });
	      selector.bindEvent('item-selected', function (data) {
	        _this3.responsible = {
	          id: data.id,
	          name: data.nameFormatted,
	          photo: {
	            src: data.avatar
	          }
	        };

	        _this3.updateResponsible();

	        selector.close();

	        _this3.emit('changeTaskResponsible');
	      });
	      selector.open();
	    }
	  }, {
	    key: "onStoryPointsClick",
	    value: function onStoryPointsClick(event) {
	      var _this4 = this;

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
	        main_core.Event.unbind(valueNode, 'keydown', _this4.blockEnterInput.bind(valueNode));
	        main_core.Dom.toggleClass(borderNode, 'ui-ctl-no-border');
	        valueNode.contentEditable = 'false';
	        var newValue = valueNode.textContent.trim();

	        if (newValue && oldValue === newValue) {
	          valueNode.textContent = oldValue;
	          return;
	        }

	        _this4.emit('updateItem', {
	          itemId: _this4.itemId,
	          itemType: _this4.itemType,
	          storyPoints: newValue
	        });

	        _this4.emit('updateStoryPoints', {
	          oldValue: _this4.storyPoints,
	          newValue: newValue
	        });

	        _this4.storyPoints = newValue;
	      }, true);
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
	      var _this5 = this;

	      if (this.actionsPanel && this.actionsPanel.isShown()) {
	        return;
	      }

	      event.stopPropagation();
	      this.actionsPanel = new ActionsPanel({
	        bindElement: this.itemNode,
	        itemList: {
	          task: {
	            activity: this.itemType === 'task',
	            callback: function callback() {
	              _this5.emit('showTask');

	              _this5.actionsPanel.destroy();
	            }
	          },
	          attachment: {
	            activity: this.itemType === 'task' && !this.disableStatus,
	            callback: function callback(event) {
	              var diskManager = new DiskManager({
	                ufDiskFilesFieldName: 'UF_TASK_WEBDAV_FILES'
	              });
	              diskManager.subscribeOnce('onFinish', function (baseEvent) {
	                _this5.emit('attachFilesToTask', baseEvent.getData());

	                _this5.actionsPanel.destroy();
	              });
	              diskManager.showAttachmentMenu(event.currentTarget);
	            }
	          },
	          move: {
	            activity: !this.decompositionMode && this.moveActivity,
	            callback: function callback(event) {
	              _this5.emit('move', event.currentTarget);
	            }
	          },
	          sprint: {
	            activity: this.entityType === 'backlog' && !this.disableStatus && !this.decompositionMode,
	            callback: function callback(event) {
	              _this5.emit('moveToSprint', event.currentTarget);
	            }
	          },
	          backlog: {
	            activity: this.entityType === 'sprint' && !this.disableStatus && !this.decompositionMode,
	            callback: function callback() {
	              _this5.emit('moveToBacklog');

	              _this5.actionsPanel.destroy();
	            }
	          },
	          tags: {
	            activity: !this.disableStatus && !this.decompositionMode,
	            callback: function callback(event) {
	              _this5.emit('showTagSearcher', event.currentTarget);
	            }
	          },
	          epic: {
	            activity: !this.disableStatus && !this.decompositionMode,
	            callback: function callback(event) {
	              _this5.emit('showEpicSearcher', event.currentTarget);
	            }
	          },
	          decomposition: {
	            activity: !this.disableStatus && !this.decompositionMode,
	            callback: function callback(event) {
	              _this5.emit('startDecomposition');

	              _this5.actionsPanel.destroy();
	            }
	          },
	          remove: {
	            activity: !this.decompositionMode,
	            callback: function callback() {
	              ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASK'), function (messageBox) {
	                _this5.emit('remove');

	                messageBox.close();

	                _this5.actionsPanel.destroy();
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
	    key: "setItemId",
	    value: function setItemId(itemId) {
	      this.itemId = main_core.Type.isInteger(itemId) ? parseInt(itemId, 10) : 0;
	      this.itemNode.dataset.itemId = this.itemId;
	    }
	  }, {
	    key: "setEpicAndTags",
	    value: function setEpicAndTags(epic, tags) {
	      this.epic = main_core.Type.isPlainObject(epic) || epic === null ? epic : this.epic;
	      this.tags = main_core.Type.isArray(tags) ? tags : this.tags;
	      this.updateTagsContainer();
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
	    key: "getEpic",
	    value: function getEpic() {
	      return this.epic;
	    }
	  }, {
	    key: "gerResponsible",
	    value: function gerResponsible() {
	      return this.responsible;
	    }
	  }, {
	    key: "setResponsible",
	    value: function setResponsible(responsible) {
	      this.responsible = responsible;
	      this.updateResponsible();
	    }
	  }, {
	    key: "updateTagsContainer",
	    value: function updateTagsContainer() {
	      var newContainer = main_core.Tag.render(_templateObject3$2(), this.getEpicTag(), this.getListTagNodes());
	      var tagsContainerNode = this.itemNode.querySelector('.tasks-scrum-item-tags-container');

	      if (tagsContainerNode) {
	        main_core.Dom.replace(tagsContainerNode, newContainer);
	      } else {
	        main_core.Dom.append(newContainer, this.itemNode);
	      }

	      main_core.Event.bind(newContainer, 'click', this.onTagsClick.bind(this));
	    }
	  }, {
	    key: "updateResponsible",
	    value: function updateResponsible() {
	      var responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');

	      if (!responsibleNode) {
	        return;
	      }

	      if (this.entityType === 'backlog') {
	        responsibleNode.style.display = 'none';
	      } else if (this.entityType === 'sprint') {
	        responsibleNode.style.display = 'block';
	      }

	      main_core.Dom.attr(responsibleNode, 'title', this.responsible.name);

	      if (this.responsible.photo && this.responsible.photo.src) {
	        main_core.Dom.style(responsibleNode.firstElementChild, 'backgroundImage', 'url(' + this.responsible.photo.src + ')');
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
	  }]);
	  return Item;
	}(main_core_events.EventEmitter);

	function _templateObject2$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-selector-footer-conjunction\"></span>"]);

	  _templateObject2$3 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span onclick=\"", "\" class=\"ui-selector-footer-link ui-selector-footer-link-add\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t"]);

	  _templateObject$5 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TagSearcher = /*#__PURE__*/function () {
	  function TagSearcher(options) {
	    babelHelpers.classCallCheck(this, TagSearcher);
	    this.requestSender = options.requestSender;
	    this.allTags = new Map();
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
	        title: epicName,
	        avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag-green.svg'
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
	    key: "showTagsDialog",
	    value: function showTagsDialog(item, targetNode) {
	      var _this = this;

	      var actionsPanel = item.getCurrentActionsPanel();
	      var currentTags = item.getTags();
	      var requestData = {};
	      var selectedItems = [];
	      currentTags.forEach(function (tag) {
	        selectedItems.push(_this.allTags.get('tag_' + tag));
	      });

	      var createTag = function createTag() {
	        var tagName = _this.tagDialog.getTagSelector().getTextBoxValue();

	        if (!tagName) {
	          _this.tagDialog.focusSearch();

	          return;
	        }

	        _this.addTagToSearcher(tagName);

	        var newTag = _this.getTagFromSearcher('tag_' + tagName);

	        var item = _this.tagDialog.addItem(newTag);

	        item.select();

	        _this.tagDialog.getTagSelector().clearTextBox();

	        _this.tagDialog.focusSearch();

	        _this.tagDialog.selectFirstTab();

	        var label = _this.tagDialog.getContainer().querySelector('.ui-selector-footer-conjunction');

	        label.textContent = '';
	      };

	      this.tagDialog = new ui_entitySelector.Dialog({
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
	            var selectedItem = event.getData().item;
	            requestData.taskId = item.getSourceId();
	            requestData.tag = selectedItem.title;

	            _this.requestSender.attachTagToTask(requestData).then(function (response) {
	              currentTags.push(requestData.tag);
	              item.setEpicAndTags(item.getEpic(), currentTags);
	            }).catch(function (response) {});
	          },
	          'Item:onDeselect': function ItemOnDeselect(event) {
	            var deselectedItem = event.getData().item;
	            requestData.taskId = item.getSourceId();
	            requestData.tag = deselectedItem.title;

	            _this.requestSender.deAttachTagToTask(requestData).then(function (response) {
	              currentTags.splice(currentTags.indexOf(requestData.tag), 1);
	              item.setEpicAndTags(item.getEpic(), currentTags);
	            }).catch(function (response) {});
	          }
	        },
	        tagSelectorOptions: {
	          events: {
	            onInput: function onInput(event) {
	              var selector = event.getData().selector;
	              var dialog = selector.getDialog();
	              var label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
	              label.textContent = main_core.Text.encode(selector.getTextBoxValue());
	            }
	          }
	        },
	        footer: [main_core.Tag.render(_templateObject$5(), createTag, main_core.Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')), main_core.Tag.render(_templateObject2$3())]
	      });
	      actionsPanel.setBlockBlurNode(this.tagDialog.getContainer());
	      this.tagDialog.subscribe('onHide', function () {
	        actionsPanel.destroy();
	      });
	      this.tagDialog.show();
	    }
	  }, {
	    key: "showEpicDialog",
	    value: function showEpicDialog(item, targetNode) {
	      var _this2 = this;

	      var actionsPanel = item.getCurrentActionsPanel();
	      var currentEpic = item.getEpic();
	      var requestData = {};
	      var selectedItems = [];

	      if (currentEpic) {
	        selectedItems.push(this.allTags.get('epic_' + currentEpic.name));
	      }

	      var dialog = new ui_entitySelector.Dialog({
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
	            var selectedItem = event.getData().item;
	            requestData.itemId = item.getItemId();
	            requestData.epicId = selectedItem.id;

	            _this2.requestSender.attachEpicToItem(requestData).then(function (response) {
	              item.setParentId(response.data.epic.id);
	              item.setEpicAndTags(response.data.epic, item.getTags());
	            }).catch(function (response) {});
	          },
	          'Item:onDeselect': function ItemOnDeselect(event) {
	            requestData.itemId = item.getItemId();

	            _this2.requestSender.deAttachEpicToItem(requestData).then(function (response) {
	              item.setParentId(0);
	              item.setEpicAndTags(null, null);
	            }).catch(function (response) {});
	          }
	        },
	        tagSelectorOptions: {
	          placeholder: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
	        }
	      });
	      actionsPanel.setBlockBlurNode(dialog.getContainer());
	      dialog.subscribe('onHide', function () {
	        actionsPanel.destroy();
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
	              var selectedHashTag = '#' + selectedItem.title;
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
	              var selectedHashEpic = '@' + selectedItem.title;
	              input.value = input.value.replace(new RegExp('(?:^|\\s)(?:@)([^\\s]*)', 'g'), '');
	              input.value = input.value + ' ' + selectedHashEpic;
	              input.focus();
	              selectedItem.deselect();
	              inputObject.setEpicId(selectedItem.id);
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
	}();

	function _templateObject$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-input\">\n\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" placeholder=\n\t\t\t\t\t\t\"", "\" autocomplete=\"off\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$6 = function _templateObject() {
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
	    _this.placeholder = main_core.Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_PLACEHOLDER');
	    _this.epicId = 0;
	    return _this;
	  }

	  babelHelpers.createClass(Input, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$6(), main_core.Text.encode(this.nodeId), main_core.Text.encode(this.placeholder));
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

	  function Entity() {
	    var _this;

	    var entityData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Entity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Entity).call(this, entityData));
	    _this.listItemsNode = null;
	    _this.storyPointsNode = null;
	    _this.id = main_core.Type.isInteger(entityData.id) ? parseInt(entityData.id, 10) : 0;

	    _this.setStoryPoints(entityData.storyPoints ? entityData.storyPoints : '');

	    _this.items = new Map();
	    _this.actionsHeader = new ActionsHeader(babelHelpers.assertThisInitialized(_this));
	    _this.input = new Input();
	    return _this;
	  }

	  babelHelpers.createClass(Entity, [{
	    key: "getId",
	    value: function getId() {
	      return this.id;
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
	      return this.listItemsNode;
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      return this.items;
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      var _this2 = this;

	      this.items.set(newItem.getItemId(), newItem);
	      this.subscribeToItem(newItem);
	      this.updateStoryPoints(newItem.getStoryPoints());
	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this2.setItemMoveActivity(item);
	      });
	    }
	  }, {
	    key: "setItemMoveActivity",
	    value: function setItemMoveActivity(item) {
	      item.setMoveActivity(this.items.size > 2);
	    }
	  }, {
	    key: "removeItem",
	    value: function removeItem(item) {
	      var _this3 = this;

	      if (this.items.has(item.getItemId())) {
	        this.items.delete(item.getItemId());
	        this.updateStoryPoints(item.getStoryPoints(), false);
	        item.unsubscribeAll();
	        babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	          _this3.setItemMoveActivity(item);
	        });
	      }
	    }
	  }, {
	    key: "hasInput",
	    value: function hasInput() {
	      return true;
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this4 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this4.subscribeToItem(item);

	        _this4.setItemMoveActivity(item);
	      });
	      this.actionsHeader.onAfterAppend();
	      this.actionsHeader.subscribe('openAddEpicForm', function () {
	        return _this4.emit('openAddEpicForm');
	      });

	      if (!this.isCompleted()) {
	        this.input.onAfterAppend();
	        this.input.subscribe('tagsSearchOpen', function (baseEvent) {
	          _this4.emit('tagsSearchOpen', {
	            inputObject: baseEvent.getTarget(),
	            enteredHashTagName: baseEvent.getData()
	          });
	        });
	        this.input.subscribe('tagsSearchClose', function () {
	          return _this4.emit('tagsSearchClose');
	        });
	        this.input.subscribe('epicSearchOpen', function (baseEvent) {
	          _this4.emit('epicSearchOpen', {
	            inputObject: baseEvent.getTarget(),
	            enteredHashEpicName: baseEvent.getData()
	          });
	        });
	        this.input.subscribe('epicSearchClose', function () {
	          return _this4.emit('epicSearchClose');
	        });
	        this.input.subscribe('createTaskItem', function (baseEvent) {
	          _this4.emit('createTaskItem', {
	            inputObject: baseEvent.getTarget(),
	            value: baseEvent.getData()
	          });
	        });
	      }
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this5 = this;

	      item.onAfterAppend(this.listItemsNode);
	      item.subscribe('updateItem', function (baseEvent) {
	        _this5.emit('updateItem', baseEvent.getData());
	      });
	      item.subscribe('updateStoryPoints', function (baseEvent) {
	        var data = baseEvent.getData();
	        var newValue = data.newValue ? String(data.newValue).trim() : '';
	        var oldValue = data.oldValue ? String(data.oldValue).trim() : '';

	        if (!newValue) {
	          _this5.updateStoryPoints(oldValue, false);
	        } else if (newValue > oldValue) {
	          _this5.updateStoryPoints(newValue - oldValue);
	        } else {
	          _this5.updateStoryPoints(oldValue - newValue, false);
	        }
	      });
	      item.subscribe('showTask', function (baseEvent) {
	        return _this5.emit('showTask', baseEvent.getTarget());
	      });
	      item.subscribe('move', function (baseEvent) {
	        _this5.emit('moveItem', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('moveToSprint', function (baseEvent) {
	        _this5.emit('moveToSprint', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('attachFilesToTask', function (baseEvent) {
	        _this5.emit('attachFilesToTask', {
	          item: baseEvent.getTarget(),
	          attachedIds: baseEvent.getData()
	        });
	      });
	      item.subscribe('showTagSearcher', function (baseEvent) {
	        _this5.emit('showTagSearcher', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('showEpicSearcher', function (baseEvent) {
	        _this5.emit('showEpicSearcher', {
	          item: baseEvent.getTarget(),
	          button: baseEvent.getData()
	        });
	      });
	      item.subscribe('startDecomposition', function (baseEvent) {
	        _this5.emit('startDecomposition', baseEvent.getTarget());
	      });
	      item.subscribe('remove', function (baseEvent) {
	        var item = baseEvent.getTarget();

	        _this5.removeItem(item);

	        item.removeYourself();

	        _this5.emit('removeItem', item);
	      });
	      item.subscribe('changeTaskResponsible', function (baseEvent) {
	        var item = baseEvent.getTarget();

	        _this5.emit('changeTaskResponsible', item);
	      });
	    }
	  }, {
	    key: "updateStoryPoints",
	    value: function updateStoryPoints(inputStoryPoints) {
	      var increment = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
	      inputStoryPoints = inputStoryPoints ? parseFloat(inputStoryPoints) : '';
	      var currentStoryPoints = this.storyPoints ? parseFloat(this.storyPoints) : '';
	      var storyPoints = increment ? currentStoryPoints + inputStoryPoints : currentStoryPoints - inputStoryPoints;
	      this.setStoryPoints(storyPoints);
	    }
	  }, {
	    key: "getItemByItemId",
	    value: function getItemByItemId(itemId) {
	      return this.items.get(parseInt(itemId, 10));
	    }
	  }, {
	    key: "getStoryPoints",
	    value: function getStoryPoints() {
	      return this.storyPoints;
	    }
	  }, {
	    key: "setStoryPoints",
	    value: function setStoryPoints(storyPoints) {
	      this.storyPoints = main_core.Type.isFloat(storyPoints) ? storyPoints.toFixed(1) : storyPoints;

	      if (this.storyPoints === 0) {
	        this.storyPoints = '';
	      }

	      if (this.storyPointsNode) {
	        this.storyPointsNode.textContent = main_core.Text.encode(this.storyPoints);
	      }
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
	  }]);
	  return Entity;
	}(main_core_events.EventEmitter);

	function _templateObject3$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-backlog-items\">\n\t\t\t\t\t<div id=\"", "\" class=\n\t\t\t\t\t\t\"tasks-scrum-backlog-items-list\" data-entity-id=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject3$3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-backlog-header\">\n\t\t\t\t\t<div class=\"tasks-scrum-backlog-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-backlog-epics-title ui-btn ui-btn-xs ui-btn-secondary\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-backlog-title-spacer\"></div>\n\t\t\t\t\t<div class=\"tasks-scrum-backlog-story-point-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-backlog-story-point\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$4 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-backlog\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject$7 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Backlog = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Backlog, _Entity);

	  function Backlog() {
	    var _this;

	    var backlogData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Backlog);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Backlog).call(this, backlogData));

	    _this.setEventNamespace('BX.Tasks.Scrum.Backlog');

	    backlogData.items.forEach(function (itemData) {
	      var item = new Item(itemData);

	      _this.items.set(item.itemId, item);
	    });
	    return _this;
	  }

	  babelHelpers.createClass(Backlog, [{
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'backlog';
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return false;
	    }
	    /**
	     * @returns {HTMLElement}
	     */

	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      var createBacklog = function createBacklog(title, actions, items) {
	        return main_core.Tag.render(_templateObject$7(), title, actions, items);
	      };

	      var createBacklogTitle = function createBacklogTitle() {
	        _this2.headerNodeId = 'tasks-scrum-backlog-story-points';
	        return main_core.Tag.render(_templateObject2$4(), _this2.headerNodeId, main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_EPICS_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE_STORY_POINTS'), main_core.Text.encode(_this2.storyPoints));
	      };

	      var createBacklogItems = function createBacklogItems() {
	        _this2.backlogItemsNodeId = 'tasks-scrum-backlog-items';
	        return main_core.Tag.render(_templateObject3$3(), _this2.backlogItemsNodeId, _this2.getId(), _this2.input.render(), babelHelpers.toConsumableArray(_this2.items.values()).map(function (item) {
	          return item.render();
	        }));
	      };

	      return createBacklog(createBacklogTitle(), this.actionsHeader.createActionsHeader(), createBacklogItems());
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this3 = this;

	      this.listItemsNode = document.getElementById(this.backlogItemsNodeId);
	      this.headerNode = document.getElementById(this.headerNodeId);
	      this.storyPointsNode = this.headerNode.querySelector('.tasks-scrum-backlog-story-point');
	      var listEpicNode = this.headerNode.querySelector('.tasks-scrum-backlog-epics-title');
	      main_core.Event.bind(listEpicNode, 'click', function () {
	        return _this3.emit('openListEpicGrid');
	      });
	      babelHelpers.get(babelHelpers.getPrototypeOf(Backlog.prototype), "onAfterAppend", this).call(this);
	    }
	  }]);
	  return Backlog;
	}(Entity);

	function _templateObject3$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$4 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2$5 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-header-stats\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject$8 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var SprintStats = /*#__PURE__*/function () {
	  function SprintStats(sprint) {
	    babelHelpers.classCallCheck(this, SprintStats);
	    this.sprint = sprint;
	    this.dateEnd = this.sprint.getDateEnd();
	    this.storyPoints = this.sprint.getStoryPoints();
	    this.completedStoryPoints = this.sprint.getCompletedStoryPoints();
	    this.kanbanMode = false;
	  }

	  babelHelpers.createClass(SprintStats, [{
	    key: "setKanbanMode",
	    value: function setKanbanMode() {
	      this.kanbanMode = true;
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      this.activeStatsNode = document.getElementById(this.activeStatsNodeId);
	    }
	  }, {
	    key: "createStats",
	    value: function createStats() {
	      if (this.sprint.isCompleted()) {
	        return this.createCompletedStatsInfo();
	      } else if (this.sprint.isActive() && this.sprint.isExpired()) {
	        return this.createExpiredStatsInfo();
	      } else if (this.sprint.isActive()) {
	        return this.createActiveStatsInfo();
	      } else {
	        return '';
	      }
	    }
	  }, {
	    key: "createActiveStatsInfo",
	    value: function createActiveStatsInfo() {
	      this.activeStatsNodeId = 'tasks-scrum-sprint-header-stats';
	      this.activeStatsClasses = this.kanbanMode ? 'tasks-scrum-sprint-header-stats-kanban' : 'tasks-scrum-sprint-header-stats';
	      return this.createActiveStatsNode(this.getRemainingDays(), this.getPercentageCompletedStoryPoints());
	    }
	  }, {
	    key: "createCompletedStatsInfo",
	    value: function createCompletedStatsInfo() {
	      return main_core.Tag.render(_templateObject$8(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL').replace('#percent#', this.getPercentageCompletedStoryPoints()).replace('#date#', BX.date.format('j F Y', this.dateEnd)));
	    }
	  }, {
	    key: "createExpiredStatsInfo",
	    value: function createExpiredStatsInfo() {
	      this.expiredStatsNodeId = 'tasks-scrum-sprint-expired-stats';
	      var statsClass = this.kanbanMode ? 'tasks-scrum-sprint-header-stats-kanban' : 'tasks-scrum-sprint-header-stats';
	      return main_core.Tag.render(_templateObject2$5(), this.expiredStatsNodeId, statsClass, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_EXPIRED_LABEL').replace('#percent#', this.getPercentageCompletedStoryPoints()).replace('#date#', BX.date.format('j F Y', this.dateEnd)));
	    }
	  }, {
	    key: "updateActiveStats",
	    value: function updateActiveStats(inputStoryPoints) {
	      var increment = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
	      inputStoryPoints = inputStoryPoints ? parseFloat(inputStoryPoints) : '';
	      this.completedStoryPoints = increment ? this.completedStoryPoints + inputStoryPoints : this.completedStoryPoints - inputStoryPoints;

	      if (this.sprint.isExpired()) {
	        main_core.Dom.replace(document.getElementById(this.expiredStatsNodeId), this.createExpiredStatsInfo());
	      } else {
	        if (!this.activeStatsNode) {
	          return;
	        }

	        var newActiveStatsNode = this.createActiveStatsNode(this.getRemainingDays(), this.getPercentageCompletedStoryPoints());
	        main_core.Dom.replace(this.activeStatsNode, newActiveStatsNode);
	        this.activeStatsNode = document.getElementById(this.activeStatsNodeId);
	      }
	    }
	  }, {
	    key: "getPercentageCompletedStoryPoints",
	    value: function getPercentageCompletedStoryPoints() {
	      var percentage = this.storyPoints > 0 ? Math.round(this.completedStoryPoints * 100 / this.storyPoints) : 0;
	      return "<b>".concat(percentage, "%</b>");
	    }
	  }, {
	    key: "getRemainingDays",
	    value: function getRemainingDays() {
	      return "<b>".concat(BX.date.format('ddiff', new Date(), this.dateEnd), "</b>");
	    }
	  }, {
	    key: "createActiveStatsNode",
	    value: function createActiveStatsNode(remainingDays, percentageCompletedStoryPoints) {
	      return main_core.Tag.render(_templateObject3$4(), this.activeStatsNodeId, this.activeStatsClasses, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL').replace('#days#', remainingDays).replace('#percent#', percentageCompletedStoryPoints));
	    }
	  }]);
	  return SprintStats;
	}();

	function _templateObject$9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-date\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-start\">", "</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-separator\">-</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-date-end\">", "</div>\n\t\t\t\t<input type=\"hidden\" name=\"dateStart\">\n\t\t\t\t<input type=\"hidden\" name=\"dateEnd\">\n\t\t\t</div>\n\t\t"]);

	  _templateObject$9 = function _templateObject() {
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

	      return main_core.Tag.render(_templateObject$9(), this.nodeId, dateStart, dateEnd);
	    }
	  }, {
	    key: "updateDateStartNode",
	    value: function updateDateStartNode(timestamp) {
	      var dateStartNode = this.node.querySelector('.tasks-scrum-sprint-date-start');
	      dateStartNode.textContent = BX.date.format('j F', timestamp);
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      var dateEndNode = this.node.querySelector('.tasks-scrum-sprint-date-end');
	      dateEndNode.textContent = BX.date.format('j F', timestamp);
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

	function _templateObject2$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-header-button\">\n\t\t\t\t\t<button class=\"", "\">", "</button>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$6 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$a() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-header ", "\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-name-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-edit\"></div>\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-params\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-tick\">\n\t\t\t\t\t\t<div class=\"ui-btn ui-btn-sm ui-btn-light ", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$a = function _templateObject() {
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
	    _this.sprintStats = new SprintStats(_this.sprint);
	    _this.sprintDate = new SprintDate(_this.sprint);

	    _this.sprintDate.subscribe('changeSprintDeadline', function (baseEvent) {
	      _this.emit('changeSprintDeadline', baseEvent.getData());
	    });

	    return _this;
	  }

	  babelHelpers.createClass(SprintHeader, [{
	    key: "initStyle",
	    value: function initStyle() {
	      if (this.sprint.isActive()) {
	        this.headerClass = 'tasks-scrum-sprint-header-active';
	        this.buttonClass = 'ui-btn ui-btn-success ui-btn-xs';
	        this.buttonText = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_COMPLETE_BUTTON');
	      } else if (this.sprint.isCompleted()) {
	        this.headerClass = 'tasks-scrum-sprint-header-completed';
	        main_core.Dom.remove(this.buttonNode);
	      } else if (this.sprint.isPlanned()) {
	        this.headerClass = 'tasks-scrum-sprint-header-planned';
	        this.buttonClass = 'ui-btn ui-btn-primary ui-btn-xs';
	        this.buttonText = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_START_BUTTON');
	      }

	      if (this.headerNode) {
	        if (this.sprint.isDisabled()) {
	          main_core.Dom.remove(this.headerNode.querySelector('.tasks-scrum-sprint-header-remove'));
	        }

	        this.headerNode.className = '';
	        main_core.Dom.addClass(this.headerNode, 'tasks-scrum-sprint-header ' + this.headerClass);
	        var button = this.buttonNode.querySelector('button');
	        button.className = '';
	        main_core.Dom.addClass(button, this.buttonClass);
	        button.firstChild.replaceWith(this.buttonText);
	      }
	    }
	  }, {
	    key: "createHeader",
	    value: function createHeader() {
	      this.headerNodeId = 'tasks-scrum-sprint-header-' + this.sprint.getId();
	      var dragndropNode = this.sprint.isPlanned() ? '<div class="tasks-scrum-sprint-dragndrop"></div>' : '<div class="tasks-scrum-sprint-header-empty"></div>';
	      var removeNode = this.sprint.isPlanned() ? '<div class="tasks-scrum-sprint-header-remove"></div>' : '';
	      var tickAngleClass = this.sprint.isCompleted() ? 'ui-btn-icon-angle-down' : 'ui-btn-icon-angle-up';
	      return main_core.Tag.render(_templateObject$a(), this.headerNodeId, this.headerClass, dragndropNode, main_core.Text.encode(this.sprint.getName()), removeNode, this.sprintStats.createStats(), this.sprintDate.createDate(this.sprint.getDateStart(), this.sprint.getDateEnd()), this.createButton(), tickAngleClass);
	    }
	  }, {
	    key: "updateDateStartNode",
	    value: function updateDateStartNode(timestamp) {
	      this.sprintDate.updateDateStartNode(timestamp);
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      this.sprintDate.updateDateEndNode(timestamp);
	    }
	  }, {
	    key: "createButton",
	    value: function createButton() {
	      if (this.sprint.isCompleted()) {
	        return '';
	      } else {
	        this.buttonNodeId = 'tasks-scrum-sprint-header-button-' + this.sprint.getId();
	        return main_core.Tag.render(_templateObject2$6(), this.buttonNodeId, this.buttonClass, this.buttonText);
	      }
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this2 = this;

	      this.headerNode = document.getElementById(this.headerNodeId);

	      if (!this.sprint.isCompleted()) {
	        this.buttonNode = document.getElementById(this.buttonNodeId);
	        main_core.Event.bind(this.buttonNode, 'click', this.onButtonClick.bind(this));
	      }

	      var nameNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-name-container');
	      var editButtonNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-edit');
	      main_core.Event.bind(editButtonNode, 'click', function () {
	        return _this2.emit('changeName', nameNode);
	      });

	      if (this.sprint.isPlanned()) {
	        var removeNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-remove');
	        main_core.Event.bind(removeNode, 'click', function () {
	          ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_SPRINT'), function (messageBox) {
	            _this2.emit('removeSprint');

	            messageBox.close();
	          }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));
	        });
	      }

	      var tickButtonNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-tick');
	      main_core.Event.bind(tickButtonNode, 'click', function () {
	        tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
	        tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-down');

	        _this2.emit('toggleVisibilityContent');
	      });
	      this.sprintDate.onAfterAppend();
	      this.sprintStats.onAfterAppend();
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
	  }]);
	  return SprintHeader;
	}(main_core_events.EventEmitter);

	function _templateObject8$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input type=\"text\" class=\"tasks-scrum-sprint-header-name\" value=\"", "\">\n\t\t\t"]);

	  _templateObject8$1 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint\" data-sprint-sort=\n\t\t\t\t\"", "\" data-sprint-id=\"", "\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-sprint-content\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject7$1 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-items\">\n\t\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-items-list\" data-entity-id=\n\t\t\t\t\t\t\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject6$1 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-header-events\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-event\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-header-event-params\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject5$1 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-story-point-done-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-done\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-story-point-in-work-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-in-work\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject3$5 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-story-point-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-story-point\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$7 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$b() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-link\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"]);

	  _templateObject$b = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Sprint = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Sprint, _Entity);

	  function Sprint() {
	    var _this;

	    var sprintData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Sprint);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sprint).call(this, sprintData));

	    _this.setEventNamespace('BX.Tasks.Scrum.Sprint');

	    _this.name = sprintData.name;
	    _this.dateStart = main_core.Type.isInteger(sprintData.dateStart) ? parseInt(sprintData.dateStart, 10) : 0;
	    _this.dateEnd = main_core.Type.isInteger(sprintData.dateEnd) ? parseInt(sprintData.dateEnd, 10) : 0;
	    _this.status = sprintData.status ? sprintData.status : 'planned';
	    _this.sort = main_core.Type.isInteger(sprintData.sort) ? parseInt(sprintData.sort, 10) : 1;
	    _this.completedStoryPoints = main_core.Type.isNumber(sprintData.completedStoryPoints) ? parseFloat(sprintData.completedStoryPoints) : '';
	    _this.unCompletedStoryPoints = main_core.Type.isNumber(sprintData.unCompletedStoryPoints) ? parseFloat(sprintData.unCompletedStoryPoints) : '';
	    _this.completedTasks = main_core.Type.isInteger(sprintData.completedTasks) ? parseInt(sprintData.completedTasks, 10) : 0;
	    _this.unCompletedTasks = main_core.Type.isInteger(sprintData.unCompletedTasks) ? parseInt(sprintData.unCompletedTasks, 10) : 0;
	    _this.defaultSprintDuration = main_core.Type.isInteger(sprintData.defaultSprintDuration) ? parseInt(sprintData.defaultSprintDuration, 10) : 0;

	    if (sprintData.items) {
	      sprintData.items.forEach(function (itemData) {
	        var item = new Item(itemData);
	        item.setDisableStatus(_this.isDisabled());

	        _this.items.set(item.itemId, item);
	      });
	    }

	    _this.sprintHeader = new SprintHeader(babelHelpers.assertThisInitialized(_this));

	    _this.initStyle();

	    return _this;
	  }

	  babelHelpers.createClass(Sprint, [{
	    key: "initStyle",
	    value: function initStyle() {
	      this.sprintHeader.initStyle();
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.status === 'active';
	    }
	  }, {
	    key: "isPlanned",
	    value: function isPlanned() {
	      return this.status === 'planned';
	    }
	  }, {
	    key: "isCompleted",
	    value: function isCompleted() {
	      return this.status === 'completed';
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return this.isCompleted();
	    }
	  }, {
	    key: "isExpired",
	    value: function isExpired() {
	      return new Date(this.dateEnd * 1000).getTime() < Date.now();
	    }
	  }, {
	    key: "hasInput",
	    value: function hasInput() {
	      return !this.isDisabled();
	    }
	  }, {
	    key: "setItem",
	    value: function setItem(newItem) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "setItem", this).call(this, newItem);
	      newItem.setDisableStatus(this.isDisabled());
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.sprintId = parseInt(id, 10);
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.sort;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.sort = parseInt(sort, 10);
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "getDateStart",
	    value: function getDateStart() {
	      return parseInt(this.dateStart, 10);
	    }
	  }, {
	    key: "getDateEnd",
	    value: function getDateEnd() {
	      return parseInt(this.dateEnd, 10);
	    }
	  }, {
	    key: "getEntityType",
	    value: function getEntityType() {
	      return 'sprint';
	    }
	  }, {
	    key: "setCompletedStoryPoints",
	    value: function setCompletedStoryPoints(completedStoryPoints) {
	      this.completedStoryPoints = parseFloat(completedStoryPoints);
	    }
	  }, {
	    key: "getCompletedStoryPoints",
	    value: function getCompletedStoryPoints() {
	      return this.completedStoryPoints;
	    }
	  }, {
	    key: "setUnCompletedStoryPoints",
	    value: function setUnCompletedStoryPoints(unCompletedStoryPoints) {
	      this.unCompletedStoryPoints = parseFloat(unCompletedStoryPoints);
	    }
	  }, {
	    key: "getUnCompletedStoryPoints",
	    value: function getUnCompletedStoryPoints() {
	      return this.unCompletedStoryPoints;
	    }
	  }, {
	    key: "setCompletedTasks",
	    value: function setCompletedTasks(completedTasks) {
	      this.completedTasks = parseInt(completedTasks, 10);
	    }
	  }, {
	    key: "getCompletedTasks",
	    value: function getCompletedTasks() {
	      return this.completedTasks;
	    }
	  }, {
	    key: "setUnCompletedTasks",
	    value: function setUnCompletedTasks(unCompletedTasks) {
	      this.unCompletedTasks = parseInt(unCompletedTasks, 10);
	    }
	  }, {
	    key: "getUnCompletedTasks",
	    value: function getUnCompletedTasks() {
	      return this.unCompletedTasks;
	    }
	  }, {
	    key: "getDefaultSprintDuration",
	    value: function getDefaultSprintDuration() {
	      return this.defaultSprintDuration;
	    }
	  }, {
	    key: "setStatus",
	    value: function setStatus(status) {
	      var _this2 = this;

	      this.status = status;
	      this.initStyle();
	      this.items.forEach(function (item) {
	        item.setDisableStatus(_this2.isDisabled());
	      });

	      if (this.isDisabled()) {
	        this.input.removeYourself();
	      }

	      if (this.isDisabled()) {
	        this.actionsHeader.removeYourself();
	      }
	    }
	  }, {
	    key: "getSprintNode",
	    value: function getSprintNode() {
	      return this.node;
	    }
	  }, {
	    key: "removeYourself",
	    value: function removeYourself() {
	      main_core.Dom.remove(this.node);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this3 = this;

	      var getStartEventsButton = function getStartEventsButton() {
	        return '';
	        return main_core.Tag.render(_templateObject$b(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_EVENT'));
	      };

	      var getTotalPoints = function getTotalPoints() {
	        _this3.storyPointsNodeId = 'tasks-scrum-sprint-story-points-' + _this3.getId();
	        return main_core.Tag.render(_templateObject2$7(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS'), _this3.storyPointsNodeId, _this3.storyPoints);
	      };

	      var getInWorkPoints = function getInWorkPoints() {
	        if (!_this3.isActive()) {
	          return '';
	        }

	        return main_core.Tag.render(_templateObject3$5(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_IN_WORK'), _this3.unCompletedStoryPoints);
	      };

	      var getDonePoints = function getDonePoints() {
	        if (_this3.isPlanned()) {
	          return '';
	        }

	        return main_core.Tag.render(_templateObject4$1(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_DONE'), _this3.completedStoryPoints);
	      };

	      var createEvents = function createEvents() {
	        return main_core.Tag.render(_templateObject5$1(), getStartEventsButton(), getTotalPoints(), getInWorkPoints(), getDonePoints());
	      };

	      var createItems = function createItems() {
	        _this3.itemsNodeId = 'tasks-scrum-sprint-items-' + _this3.getId();
	        return main_core.Tag.render(_templateObject6$1(), _this3.itemsNodeId, _this3.getId(), _this3.isCompleted() ? '' : _this3.input.render(), babelHelpers.toConsumableArray(_this3.items.values()).map(function (item) {
	          return item.render();
	        }));
	      };

	      this.nodeId = 'tasks-scrum-sprint-' + this.getId();
	      return main_core.Tag.render(_templateObject7$1(), this.nodeId, this.sort, this.getId(), this.sprintHeader.createHeader(), createEvents(), this.actionsHeader.createActionsHeader(), createItems());
	    }
	  }, {
	    key: "onAfterAppend",
	    value: function onAfterAppend() {
	      var _this4 = this;

	      this.node = document.getElementById(this.nodeId);
	      this.contentNode = this.node.querySelector('.tasks-scrum-sprint-content');
	      this.listItemsNode = document.getElementById(this.itemsNodeId);
	      this.storyPointsNode = document.getElementById(this.storyPointsNodeId);

	      if (!this.isCompleted()) {
	        this.showContent();
	      }

	      this.sprintHeader.onAfterAppend();
	      this.sprintHeader.subscribe('changeName', this.onChangeName.bind(this));
	      this.sprintHeader.subscribe('removeSprint', this.onRemoveSprint.bind(this));
	      this.sprintHeader.subscribe('completeSprint', function () {
	        return _this4.emit('completeSprint');
	      });
	      this.sprintHeader.subscribe('startSprint', function () {
	        return _this4.emit('startSprint');
	      });
	      this.sprintHeader.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
	      this.sprintHeader.subscribe('toggleVisibilityContent', this.toggleVisibilityContent.bind(this));
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "onAfterAppend", this).call(this);
	    }
	  }, {
	    key: "subscribeToItem",
	    value: function subscribeToItem(item) {
	      var _this5 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(Sprint.prototype), "subscribeToItem", this).call(this, item);
	      item.subscribe('moveToBacklog', function (baseEvent) {
	        _this5.emit('moveToBacklog', {
	          sprint: _this5,
	          item: baseEvent.getTarget()
	        });
	      });
	    }
	  }, {
	    key: "onChangeName",
	    value: function onChangeName(baseEvent) {
	      var _this6 = this;

	      var createInput = function createInput(value) {
	        return main_core.Tag.render(_templateObject8$1(), main_core.Text.encode(value));
	      };

	      var inputNode = createInput(this.name);
	      var nameNode = baseEvent.getData().querySelector('.tasks-scrum-sprint-header-name');
	      main_core.Event.bind(inputNode, 'change', function (event) {
	        var newValue = event.target['value'];

	        _this6.emit('changeSprintName', {
	          sprintId: _this6.getId(),
	          name: newValue
	        });

	        _this6.name = newValue;
	        inputNode.blur();
	      }, true);

	      var blockEnterInput = function blockEnterInput(event) {
	        if (event.isComposing || event.keyCode === 13) inputNode.blur();
	      };

	      main_core.Event.bind(inputNode, 'keydown', blockEnterInput);
	      main_core.Event.bindOnce(inputNode, 'blur', function () {
	        main_core.Event.unbind(inputNode, 'keydown', blockEnterInput);
	        nameNode.textContent = main_core.Text.encode(_this6.name);
	        main_core.Dom.replace(inputNode, nameNode);
	      }, true);
	      main_core.Dom.replace(nameNode, inputNode);
	      inputNode.focus();
	      inputNode.setSelectionRange(this.name.length, this.name.length);
	    }
	  }, {
	    key: "onRemoveSprint",
	    value: function onRemoveSprint() {
	      var _this7 = this;

	      babelHelpers.toConsumableArray(this.items.values()).map(function (item) {
	        _this7.emit('moveToBacklog', {
	          sprint: _this7,
	          item: item
	        });
	      });
	      this.removeYourself();
	      this.emit('removeSprint');
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
	      this.sprintHeader.updateDateStartNode(timestamp);
	    }
	  }, {
	    key: "updateDateEndNode",
	    value: function updateDateEndNode(timestamp) {
	      this.sprintHeader.updateDateEndNode(timestamp);
	    }
	  }, {
	    key: "toggleVisibilityContent",
	    value: function toggleVisibilityContent() {
	      if (this.contentNode.style.display === 'block') {
	        this.hideContent();
	      } else {
	        this.showContent();
	      }
	    }
	  }, {
	    key: "showContent",
	    value: function showContent() {
	      this.contentNode.style.display = 'block';
	    }
	  }, {
	    key: "hideContent",
	    value: function hideContent() {
	      this.contentNode.style.display = 'none';
	    }
	  }]);
	  return Sprint;
	}(Entity);

	function _templateObject2$8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-result\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-result-header\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-result-completed\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-result-uncompleted\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-actions\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-move-header\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-complete-popup-move-select\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t\t<select id=\"", "\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t\t\t\t<option value=\"backlog\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t\t\t<option value=\"sprint\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject2$8 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$c() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-complete-popup-move-sprint\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t<select id=\"", "\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t\t\t<option value=\"0\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</option>\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"]);

	  _templateObject$c = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var SprintPopup = /*#__PURE__*/function () {
	  function SprintPopup(options) {
	    babelHelpers.classCallCheck(this, SprintPopup);
	    this.sprints = options.sprints;
	  }

	  babelHelpers.createClass(SprintPopup, [{
	    key: "showCompletePopup",
	    value: function showCompletePopup(sprint) {
	      var _this = this;

	      return new Promise(function (resolve, reject) {
	        var popupId = 'tasks-scrum-complete-sprint' + main_core.Text.getRandom();
	        var moveSelectId = 'tasks-scrum-sprint-complete-popup-move-select';
	        var moveSprintsBlockId = 'tasks-scrum-sprint-complete-popup-move-sprints-block';
	        var moveSprintSelectId = 'tasks-scrum-sprint-complete-popup-move-sprint-select';

	        var getPopupContent = function getPopupContent() {
	          var moveSprint = function moveSprint() {
	            var listSprintsOptions = '';

	            _this.sprints.forEach(function (sprint) {
	              if (sprint.isPlanned()) {
	                listSprintsOptions += "<option value=\"".concat(sprint.getId(), "\">").concat(sprint.getName(), "</option>");
	              }
	            });

	            return main_core.Tag.render(_templateObject$c(), moveSprintsBlockId, moveSprintSelectId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_NEW_SPRINT'), listSprintsOptions);
	          };

	          return main_core.Tag.render(_templateObject2$8(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_HEADER'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_COMPLETED').replace('#tasks#', _this.getTasksCountLabel(sprint.getCompletedTasks())).replace('#storyPoints#', sprint.getCompletedStoryPoints()), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_UNCOMPLETED').replace('#tasks#', _this.getTasksCountLabel(sprint.getUnCompletedTasks())).replace('#storyPoints#', sprint.getUnCompletedStoryPoints()), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_HEADER'), moveSelectId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_BACKLOG'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_SPRINT'), moveSprint());
	        };

	        var popup = new main_popup.Popup(popupId, null, {
	          width: 360,
	          autoHide: true,
	          closeByEsc: true,
	          offsetTop: 0,
	          offsetLeft: 0,
	          closeIcon: true,
	          draggable: true,
	          resizable: false,
	          lightShadow: true,
	          cacheable: false,
	          titleBar: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TITLE_POPUP').replace('#name#', sprint.getName()),
	          content: getPopupContent(),
	          buttons: [new ui_buttons.Button({
	            text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_COMPLETE_BUTTON'),
	            color: ui_buttons.Button.Color.SUCCESS,
	            events: {
	              click: function click() {
	                var moveSelect = document.getElementById(moveSelectId);
	                var moveSprintSelect = document.getElementById(moveSprintSelectId);
	                resolve({
	                  sprintId: sprint.getId(),
	                  direction: moveSelect.value,
	                  targetSprint: moveSprintSelect.value
	                });
	              }
	            }
	          }), new ui_buttons.Button({
	            text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_CANCEL_POPUP'),
	            color: ui_buttons.Button.Color.LINK,
	            events: {
	              click: function click() {
	                return popup.close();
	              }
	            }
	          })]
	        });
	        popup.show();
	        main_core.Event.bind(document.getElementById(moveSelectId), 'change', function (event) {
	          if (event.target.value === 'sprint') {
	            document.getElementById(moveSprintsBlockId).style.display = 'block';
	          } else {
	            document.getElementById(moveSprintsBlockId).style.display = 'none';
	          }
	        });
	      });
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
	  }]);
	  return SprintPopup;
	}();

	var Kanban = /*#__PURE__*/function () {
	  function Kanban(options) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Kanban);
	    this.activeSprintData = main_core.Type.isPlainObject(options.activeSprintData) ? options.activeSprintData : null;

	    if (this.activeSprintData) {
	      this.requestSender = new RequestSender({
	        signedParameters: options.signedParameters
	      });
	      this.finishStatus = this.activeSprintData.finishStatus;
	      this.sprint = new Sprint(this.activeSprintData);
	      this.sprints = new Map();
	      options.sprints.forEach(function (sprintData) {
	        var sprint = new Sprint(sprintData);

	        _this.sprints.set(sprint.getId(), sprint);
	      });
	      this.tabs = options.tabs;
	      this.itemsInFinishStage = new Map();
	      this.initDomNodes();
	      this.createSprintStats();
	      this.bindHandlers();
	    }
	  }

	  babelHelpers.createClass(Kanban, [{
	    key: "initDomNodes",
	    value: function initDomNodes() {
	      this.sprintStatsContainer = document.getElementById('tasks-scrum-active-sprint-stats');
	      this.completeSprintButtonNode = document.getElementById('tasks-scrum-actions-complete-sprint');
	    }
	  }, {
	    key: "createSprintStats",
	    value: function createSprintStats() {
	      this.sprintStats = new SprintStats(this.sprint);
	      this.sprintStats.setKanbanMode();
	      main_core.Dom.append(this.sprintStats.createStats(), this.sprintStatsContainer);
	      this.sprintStats.onAfterAppend();
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      main_core.Event.bind(this.completeSprintButtonNode, 'click', this.onCompleteSprint.bind(this));

	      if (window.Kanban) {
	        this.onKanbanRender(window.Kanban);
	        BX.addCustomEvent(window.Kanban, 'Kanban.Grid:onItemMoved', this.onItemMoved.bind(this));
	      }
	    }
	  }, {
	    key: "onCompleteSprint",
	    value: function onCompleteSprint() {
	      var _this2 = this;

	      var sprintPopup = new SprintPopup({
	        sprints: this.sprints
	      });
	      sprintPopup.showCompletePopup(this.sprint).then(function (requestData) {
	        _this2.requestSender.completeSprint(requestData).then(function (response) {
	          location.href = _this2.tabs['planning'].url;
	        }).catch(function (response) {});
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
	     * @param {BX.Tasks.Kanban.Item} item
	     * @param {BX.Tasks.Kanban.Column} targetColumn
	     * @param {BX.Tasks.Kanban.Item} [beforeItem]
	     * @returns {void}
	     */

	  }, {
	    key: "onItemMoved",
	    value: function onItemMoved(item, targetColumn, beforeItem) {
	      if (targetColumn.type === this.finishStatus) {
	        if (!this.itemsInFinishStage.has(item.getId())) {
	          this.updateStatsAfterMovedToFinish(item, this.sprint);
	        }
	      } else {
	        if (this.itemsInFinishStage.has(item.getId())) {
	          this.updateStatsAfterMovedFromFinish(item, this.sprint);
	        }
	      }
	    }
	  }, {
	    key: "updateStatsAfterMovedToFinish",
	    value: function updateStatsAfterMovedToFinish(item, sprint) {
	      this.itemsInFinishStage.set(item.getId(), item.getStoryPoints());
	      this.sprintStats.updateActiveStats(item.getStoryPoints());
	      sprint.setCompletedStoryPoints(sprint.getCompletedStoryPoints() + parseFloat(item.getStoryPoints()));
	      sprint.setUnCompletedStoryPoints(sprint.getUnCompletedStoryPoints() - parseFloat(item.getStoryPoints()));
	      sprint.setCompletedTasks(sprint.getCompletedTasks() + 1);
	      sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() - 1);
	    }
	  }, {
	    key: "updateStatsAfterMovedFromFinish",
	    value: function updateStatsAfterMovedFromFinish(item, sprint) {
	      this.itemsInFinishStage.delete(item.getId());
	      this.sprintStats.updateActiveStats(item.getStoryPoints(), false);
	      sprint.setCompletedStoryPoints(sprint.getCompletedStoryPoints() - parseFloat(item.getStoryPoints()));
	      sprint.setUnCompletedStoryPoints(sprint.getUnCompletedStoryPoints() + parseFloat(item.getStoryPoints()));
	      sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
	      sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() + 1);
	    }
	  }]);
	  return Kanban;
	}();

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

	      /* eslint-disable */
	      BX.addCustomEvent(window, 'SidePanel.Slider:onLoad', function (event) {
	        var sidePanel = event.getSlider();
	        sidePanel.setCacheable(false);

	        _this2.emit('onLoadSidePanel', sidePanel);
	      });
	      BX.addCustomEvent(window, 'SidePanel.Slider:onClose', function (event) {
	        var sidePanel = event.getSlider();

	        _this2.emit('onCloseSidePanel', sidePanel);
	      });
	      BX.addCustomEvent(window, 'onAfterPopupShow', function (popupWindow) {
	        var topSlider = _this2.sidePanelManager.getTopSlider();

	        var topSidePanelZIndex = topSlider ? topSlider.getZindex() : 1000;
	        var popupWindowZIndex = popupWindow.getZindex();
	        var zIndex = topSidePanelZIndex > popupWindowZIndex ? topSidePanelZIndex + 1 : popupWindowZIndex + 1;
	        main_core.Dom.style(popupWindow.getPopupContainer(), 'zIndex', zIndex);
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "isPreviousSidePanelExist",
	    value: function isPreviousSidePanelExist(currentSidePanel) {
	      return Boolean(this.sidePanelManager.getPreviousSlider(currentSidePanel));
	    }
	  }, {
	    key: "reloadTopSidePanel",
	    value: function reloadTopSidePanel() {
	      this.sidePanelManager.getTopSlider().reload();
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
	    value: function openSidePanel(id, contentCallback) {
	      this.sidePanelManager.open(id, {
	        contentCallback: contentCallback,
	        zIndex: 1000
	      });
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

	function _templateObject7$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t<input type=\"text\" name=\"name\" value=\"", "\" class=\n\t\t\t\t\t\t\"tasks-scrum-epic-form-header-title-control\" placeholder=\n\t\t\t\t\t\t\"", "\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-btn-angle\">\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject7$2 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-header\">\n\t\t\t\t<div class=\"tasks-scrum-epic-header-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6$2 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epics-empty\">\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-first-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-image\">\n\t\t\t\t\t<svg width=\"124px\" height=\"123px\" viewBox=\"0 0 124 123\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n\t\t\t\t\t\t<g stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\" opacity=\"0.28\">\n\t\t\t\t\t\t\t<path d=\"M83,105 L83,81.4375 L105,81.4375 L105,18 L17,18 L17,81.4375 L39,81.4375 L39,105 L83,105 Z M10.9411765,0 L113.058824,0 C119.101468,0 124,4.85902727 124,10.8529412 L124,112.147059 C124,118.140973 119.101468,123 113.058824,123 L10.9411765,123 C4.89853156,123 0,118.140973 0,112.147059 L0,10.8529412 C0,4.85902727 4.89853156,0 10.9411765,0 Z M44.0142862,47.0500004 L54.2142857,57.4416671 L79.7142857,32 L87,42.75 L54.2142857,75 L36,57.0833333 L44.0142862,47.0500004 Z\" fill=\"#A8ADB4\" />\n\t\t\t\t\t\t</g>\n\t\t\t\t\t</svg>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-second-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-button\">\n\t\t\t\t\t<button class=\"ui-btn ui-btn-primary ui-btn-lg\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$2 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$2 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-btn-angle\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\" style=\"padding: 15px 10px 15px 10px;\"></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-files\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3$6 = function _templateObject3() {
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

	function _templateObject$d() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum-epics-list\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-add-button\">\n\t\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-primary ui-btn-sm\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epics-list-grid\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject$d = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Epic = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Epic, _EventEmitter);

	  function Epic(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Epic);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Epic).call(this, options));
	    _this.requestSender = options.requestSender;
	    _this.sidePanel = options.sidePanel;
	    _this.entity = options.entity;
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
	      this.sidePanel.openSidePanel(this.sidePanelId, function () {
	        return new Promise(function (resolve, reject) {
	          resolve(_this2.buildAddForm());
	        });
	      });
	    }
	  }, {
	    key: "openEditForm",
	    value: function openEditForm(epicId) {
	      var _this3 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadEditForm.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, function () {
	        return new Promise(function (resolve, reject) {
	          _this3.getEpic(epicId).then(function (response) {
	            _this3.currentEpic = response.data;
	            resolve(_this3.buildEditForm());
	          }).catch(function (response) {});
	        });
	      });
	    }
	  }, {
	    key: "openViewForm",
	    value: function openViewForm(epicId) {
	      var _this4 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadViewForm.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, function () {
	        return new Promise(function (resolve, reject) {
	          _this4.getEpic(epicId).then(function (response) {
	            _this4.currentEpic = response.data;
	            resolve(_this4.buildViewForm());
	          }).catch(function (response) {});
	        });
	      });
	    }
	  }, {
	    key: "openEpicsList",
	    value: function openEpicsList() {
	      var _this5 = this;

	      this.id = main_core.Text.getRandom();
	      this.sidePanelId = 'tasks-scrum-epic-' + this.id;
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequest.bind(this));
	      this.sidePanel.openSidePanel(this.sidePanelId, function () {
	        _this5.sidePanel.subscribeOnce('onLoadSidePanel', _this5.onLoadListGrid.bind(_this5));

	        _this5.sidePanel.subscribeOnce('onCloseSidePanel', _this5.destroyGrid.bind(_this5));

	        return new Promise(function (resolve, reject) {
	          resolve(main_core.Tag.render(_templateObject$d(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')));
	        });
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
	            _this6.emit('onAfterCreateEpic', response);

	            if (_this6.sidePanel.isPreviousSidePanelExist(sidePanel)) {
	              _this6.sidePanel.reloadPreviousSidePanel(sidePanel);

	              sidePanel.close();
	            } else {
	              sidePanel.close(false, function () {
	                _this6.openEpicsList();
	              });
	            }
	          }).catch(function (response) {
	            ui_dialogs_messagebox.MessageBox.alert(response.errors.shift().message, main_core.Loc.getMessage('TASKS_SCRUM_EPIC_CREATE_ERROR_TITLE_POPUP'));
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
	            ui_dialogs_messagebox.MessageBox.alert(response.errors.shift().message, main_core.Loc.getMessage('TASKS_SCRUM_EPIC_UPDATE_ERROR_TITLE_POPUP'));
	          });
	        });
	      });
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      /* eslint-disable */
	      BX.Main.gridManager.getById(this.gridId).instance.reload();
	      /* eslint-enable */
	    }
	  }, {
	    key: "destroyGrid",
	    value: function destroyGrid() {
	      /* eslint-disable */
	      BX.Main.gridManager.getById(this.gridId).instance.destroy();
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
	      var descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');
	      this.requestSender.getEpicDescription({
	        epicId: this.currentEpic.id,
	        text: this.currentEpic.description
	      }).then(function (response) {
	        main_core.Runtime.html(descriptionContainer, response.data);
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
	      var _this10 = this;

	      this.selectedColor = this.currentEpic ? this.currentEpic.info.color : this.defaultColor;
	      var colorBlockNode = this.form.querySelector('.tasks-scrum-epic-header-color');
	      main_core.Event.bind(colorBlockNode, 'click', function () {
	        var colorNode = colorBlockNode.querySelector('.tasks-scrum-epic-header-color-current');

	        var picker = _this10.getColorPicker(colorNode);

	        picker.open();
	      });
	    }
	  }, {
	    key: "onLoadAddButtons",
	    value: function onLoadAddButtons() {
	      var _this11 = this;

	      return this.getAddEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this11.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadViewButtons",
	    value: function onLoadViewButtons() {
	      var _this12 = this;

	      return this.getViewEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this12.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadEditButtons",
	    value: function onLoadEditButtons() {
	      var _this13 = this;

	      return this.getAddEpicFormButtons().then(function (buttonsHtml) {
	        var buttonsContainer = _this13.form.querySelector('.tasks-scrum-epic-form-buttons');

	        return main_core.Runtime.html(buttonsContainer, buttonsHtml).then(function () {
	          return buttonsContainer;
	        });
	      });
	    }
	  }, {
	    key: "onLoadListGrid",
	    value: function onLoadListGrid(baseEvent) {
	      var _this14 = this;

	      var sidePanel = baseEvent.getData();
	      var form = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list');
	      var list = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list-grid');
	      this.getEpicsList().then(function (responseData) {
	        if (responseData.html) {
	          main_core.Runtime.html(list, responseData.html);
	          var buttonNode = form.querySelector('.tasks-scrum-epic-header-add-button');
	          main_core.Event.bind(buttonNode, 'click', function () {
	            _this14.openAddForm();
	          });
	        } else {
	          main_core.Dom.remove(form.querySelector('.tasks-scrum-epic-header-add-button'));
	          main_core.Dom.append(_this14.getEmptyEpicListForm(), list);

	          var _buttonNode = list.querySelector('.tasks-scrum-epics-empty-button');

	          main_core.Event.bind(_buttonNode, 'click', function () {
	            _this14.openAddForm();
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
	      var _this15 = this;

	      this.gridId = 'EpicsGrid' + main_core.Text.getRandom();
	      return new Promise(function (resolve, reject) {
	        _this15.requestSender.getEpicsList({
	          entityId: _this15.entity.getId(),
	          gridId: _this15.gridId
	        }).then(function (response) {
	          resolve(response.data);
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
	      return main_core.Tag.render(_templateObject3$6(), this.buildFormHeader(main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_VIEW_EPIC_FORM_TITLE')), main_core.Text.encode(this.currentEpic.name), main_core.Text.encode(this.currentEpic.info.color));
	    }
	  }, {
	    key: "buildEditForm",
	    value: function buildEditForm() {
	      return main_core.Tag.render(_templateObject4$2(), this.buildFormHeader(main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_EDIT_EPIC_FORM_TITLE')), this.buildFormContainerHeader(this.currentEpic.name, this.currentEpic.info.color));
	    }
	  }, {
	    key: "getEmptyEpicListForm",
	    value: function getEmptyEpicListForm() {
	      return main_core.Tag.render(_templateObject5$2(), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_FIRST_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_SECOND_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD'));
	    }
	  }, {
	    key: "buildFormHeader",
	    value: function buildFormHeader(title) {
	      return main_core.Tag.render(_templateObject6$2(), title);
	    }
	  }, {
	    key: "buildFormContainerHeader",
	    value: function buildFormContainerHeader(name, color) {
	      return main_core.Tag.render(_templateObject7$2(), main_core.Text.encode(name), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_NAME_PLACEHOLDER'), main_core.Text.encode(color));
	    }
	  }, {
	    key: "getDescriptionEditor",
	    value: function getDescriptionEditor() {
	      var _this16 = this;

	      var requestData = {
	        editorId: this.id
	      };

	      if (this.currentEpic) {
	        requestData.epicId = this.currentEpic.id;
	        requestData.text = this.currentEpic.description;
	      }

	      return new Promise(function (resolve, reject) {
	        _this16.requestSender.getEpicDescriptionEditor(requestData).then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getAddEpicFormButtons",
	    value: function getAddEpicFormButtons() {
	      var _this17 = this;

	      return new Promise(function (resolve, reject) {
	        _this17.requestSender.getAddEpicFormButtons().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getViewEpicFormButtons",
	    value: function getViewEpicFormButtons() {
	      var _this18 = this;

	      return new Promise(function (resolve, reject) {
	        _this18.requestSender.getViewEpicFormButtonsAction().then(function (response) {
	          resolve(response.data.html);
	        });
	      });
	    }
	  }, {
	    key: "getColorPicker",
	    value: function getColorPicker(colorNode) {
	      var _this19 = this;

	      /* eslint-disable */
	      return new BX.ColorPicker({
	        bindElement: colorNode,
	        defaultColor: this.selectedColor,
	        onColorSelected: function onColorSelected(color, picker) {
	          _this19.selectedColor = color;
	          colorNode.style.backgroundColor = color;
	        },
	        popupOptions: {
	          zIndex: 1100
	        }
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
	      requestData.name = this.form.querySelector('[name=name]').value;
	      requestData.description = this.editor.oEditor.GetContent();
	      requestData.color = this.selectedColor;
	      requestData.files = this.getAttachmentsFiles();
	      return requestData;
	    }
	  }, {
	    key: "getAttachmentsFiles",
	    value: function getAttachmentsFiles() {
	      var _this20 = this;

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
	        if (_this20.editor.controllers[fileController] && main_core.Type.isPlainObject(_this20.editor.controllers[fileController].values)) {
	          Object.keys(_this20.editor.controllers[fileController].values).forEach(function (fileId) {
	            if (!files.includes(fileId)) {
	              files.push(fileId);
	            }
	          });
	        }
	      });
	      return files;
	    }
	  }]);
	  return Epic;
	}(main_core_events.EventEmitter);

	function _templateObject$e() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-decomposition-structure\">\n\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"]);

	  _templateObject$e = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Decomposition = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Decomposition, _EventEmitter);

	  function Decomposition() {
	    var _this;

	    babelHelpers.classCallCheck(this, Decomposition);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Decomposition).call(this));

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
	      main_core.Dom.insertAfter(this.input.render(), item.getItemNode());
	      this.input.setNode();
	      var inputNode = this.input.getInputNode();
	      main_core.Event.bind(inputNode, 'input', this.input.onTagSearch.bind(this.input));
	      main_core.Event.bind(inputNode, 'keydown', this.onCreateItem.bind(this));
	      inputNode.focus();
	      var button = this.createButton();
	      main_core.Dom.insertAfter(button, this.input.getNode());
	      main_core.Event.bind(button.querySelector('button'), 'click', function () {
	        _this2.deactivateDecompositionMode();

	        _this2.input.removeYourself();

	        main_core.Dom.remove(button);
	      });
	    }
	  }, {
	    key: "addDecomposedItem",
	    value: function addDecomposedItem(item) {
	      item.activateDecompositionMode();
	      this.items.add(item);
	    }
	  }, {
	    key: "getDecomposedItems",
	    value: function getDecomposedItems() {
	      return this.items;
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
	      return main_core.Tag.render(_templateObject$e(), main_core.Loc.getMessage('TASKS_SCRUM_DECOMPOSITION_BUTTON'));
	    }
	  }, {
	    key: "onCreateItem",
	    value: function onCreateItem(event) {
	      if (event.isComposing || event.keyCode === 13) {
	        if (!this.input.isTagsSearchMode()) {
	          var inputNode = event.target;

	          if (inputNode.value) {
	            this.emit('createItem', inputNode.value);
	            inputNode.value = '';
	            inputNode.focus();
	          }
	        }
	      }
	    }
	  }]);
	  return Decomposition;
	}(main_core_events.EventEmitter);

	var Filter = /*#__PURE__*/function () {
	  function Filter(options) {
	    babelHelpers.classCallCheck(this, Filter);
	    this.filterId = options.filterId;
	    this.scrumManager = options.scrumManager;
	    this.requestSender = options.requestSender;
	    this.initUiFilterManager();
	    this.bindHandlers();
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
	      /* eslint-disable */
	      BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
	      /* eslint-enable */
	    }
	  }, {
	    key: "onApplyFilter",
	    value: function onApplyFilter(filterId, values, filterInstance, promise, params) {
	      var _this = this;

	      if (this.filterId !== filterId) {
	        return;
	      }

	      this.scrumManager.fadeOutAll();
	      params.autoResolve = false;
	      this.requestSender.applyFilter().then(function (response) {
	        var filteredItemsData = response.data;

	        _this.scrumManager.getAllItems().forEach(function (item) {
	          _this.scrumManager.removeItemFromEntities(item);
	        });

	        filteredItemsData.forEach(function (itemData) {
	          _this.scrumManager.appendNewItemToEntity(_this.scrumManager.createTaskItemByItemData(itemData));
	        });
	        promise.fulfill();

	        _this.scrumManager.fadeInAll();
	      }).catch(function (response) {
	        promise.reject();

	        _this.scrumManager.fadeInAll();
	      });
	    }
	  }]);
	  return Filter;
	}();

	function _templateObject5$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup\">\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-duration\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-content-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-content-info\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-taken\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-content-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-sprint-start-popup-content-info\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject5$3 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprints\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject4$3 = function _templateObject4() {
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

	function _templateObject2$a() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-file-drop tasks-scrum-sprint-sprint-add-drop\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t<small>", "</small>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject2$a = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$f() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div id=\"", "\" class=\"tasks-scrum-sprint-create ui-btn ui-btn-md ui-btn-themes ui-btn-light-border ui-btn-icon-add\">\n\t\t\t\t\t<span>", "</span>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject$f = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Scrum = /*#__PURE__*/function () {
	  function Scrum(options) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Scrum);
	    this.defaultSprintDuration = options.defaultSprintDuration;
	    this.pathToTask = options.pathToTask;
	    this.requestSender = new RequestSender({
	      signedParameters: options.signedParameters,
	      debugMode: options.debugMode
	    });
	    this.activeSprintId = parseInt(options.activeSprintId, 10);
	    this.tabs = options.tabs;
	    this.activeTab = options.activeTab;

	    if (this.activeTab === 'activeSprint') {
	      this.kanban = new Kanban(options);
	    } else {
	      this.backlog = new Backlog(options.backlog);
	      this.sprints = new Map();
	      options.sprints.forEach(function (sprintData) {
	        sprintData.defaultSprintDuration = _this.defaultSprintDuration;
	        var sprint = new Sprint(sprintData);

	        _this.sprints.set(sprint.getId(), sprint);
	      });
	      this.sidePanel = new SidePanel();
	      this.tagSearcher = new TagSearcher({
	        requestSender: this.requestSender
	      });
	      Object.values(options.tags.epic).forEach(function (epic) {
	        _this.tagSearcher.addEpicToSearcher(epic);
	      });
	      Object.values(options.tags.task).forEach(function (tagName) {
	        _this.tagSearcher.addTagToSearcher(tagName);
	      });
	      this.epic = new Epic({
	        entity: this.backlog,
	        requestSender: this.requestSender,
	        sidePanel: this.sidePanel
	      });
	      this.epic.subscribe('onAfterCreateEpic', function (baseEvent) {
	        var response = baseEvent.getData();

	        _this.tagSearcher.addEpicToSearcher(response.data);
	      });
	      this.filter = new Filter({
	        filterId: options.filterId,
	        scrumManager: this,
	        requestSender: this.requestSender
	      });
	      this.bindHandlers();
	    }
	  }

	  babelHelpers.createClass(Scrum, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        throw new Error('Scrum: HTMLElement for Scrum not found');
	      }

	      this.scrumContainer = container;
	      main_core.Dom.append(this.backlog.render(), this.scrumContainer);
	      this.backlog.onAfterAppend();
	      main_core.Dom.append(this.renderSprintsContainer(), this.scrumContainer);
	      this.sprints.forEach(function (sprint) {
	        sprint.onAfterAppend();
	      });
	      this.sprintCreatingButtonNode = document.getElementById(this.sprintCreatingButtonNodeId);
	      this.sprintCreatingDropZoneNode = document.getElementById(this.sprintCreatingDropZoneNodeId);
	      this.sprintListNode = document.getElementById(this.sprintListNodeId);
	      main_core.Event.bind(this.sprintCreatingButtonNode, 'click', this.createSprint.bind(this));
	      this.setDraggable();
	    }
	  }, {
	    key: "setDraggable",
	    value: function setDraggable() {
	      var _this2 = this;

	      var itemContainers = [];
	      itemContainers.push(this.backlog.getListItemsNode());

	      if (this.sprintCreatingDropZoneNode) {
	        itemContainers.push(this.sprintCreatingDropZoneNode);
	      }

	      this.sprints.forEach(function (sprint) {
	        if (!sprint.isDisabled()) {
	          itemContainers.push(sprint.getListItemsNode());
	        }
	      });
	      this.draggableItems = new ui_draganddrop_draggable.Draggable({
	        container: itemContainers,
	        draggable: '.tasks-scrum-item',
	        dragElement: '.tasks-scrum-dragndrop',
	        type: ui_draganddrop_draggable.Draggable.DROP_PREVIEW
	      });
	      this.draggableItems.subscribe('end', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();

	        _this2.onItemMove(dragEndEvent);
	      });
	      this.draggableSprints = new ui_draganddrop_draggable.Draggable({
	        container: this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list'),
	        draggable: '.tasks-scrum-sprint',
	        dragElement: '.tasks-scrum-sprint-dragndrop',
	        type: ui_draganddrop_draggable.Draggable.DROP_PREVIEW
	      });
	      this.draggableSprints.subscribe('end', function (baseEvent) {
	        var dragEndEvent = baseEvent.getData();

	        _this2.onSprintMove(dragEndEvent);
	      });
	    }
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers(newSprint) {
	      var _this3 = this;

	      var createTaskItem = function createTaskItem(baseEvent) {
	        var data = baseEvent.getData();
	        var entity = baseEvent.getTarget();
	        var inputObject = data.inputObject;
	        var inputValue = data.value;

	        var newItem = _this3.createItem('task', inputValue);

	        newItem.setParentEntity(entity.getId(), entity.getEntityType());
	        newItem.setSort(1);

	        _this3.appendItem(newItem, entity.getListItemsNode(), inputObject.getNode());

	        newItem.setParentId(inputObject.getEpicId());

	        _this3.sendRequestToCreateTask(entity, newItem, inputValue).then(function (response) {
	          _this3.fillItemAfterCreation(newItem, response.data);

	          response.data.tags.forEach(function (tag) {
	            _this3.tagSearcher.addTagToSearcher(tag);
	          });
	          entity.setItem(newItem);
	        });
	      };

	      var onUpdateItem = function onUpdateItem(baseEvent) {
	        _this3.requestSender.updateItem(baseEvent.getData());
	      };

	      var onShowTask = function onShowTask(baseEvent) {
	        var item = baseEvent.getData();

	        _this3.sidePanel.openSidePanelByUrl(_this3.pathToTask.replace('#task_id#', item.sourceId));
	      };

	      var onMoveItem = function onMoveItem(baseEvent) {
	        var data = baseEvent.getData();

	        _this3.moveItem(data.item, data.button);
	      };

	      var onMoveToSprint = function onMoveToSprint(baseEvent) {
	        var data = baseEvent.getData();

	        _this3.moveToSprint(data.item, data.button);
	      };

	      var onMoveToBacklog = function onMoveToBacklog(baseEvent) {
	        var data = baseEvent.getData();

	        _this3.moveToBacklog(data.sprint, data.item);
	      };

	      var onAttachFilesToTask = function onAttachFilesToTask(baseEvent) {
	        var data = baseEvent.getData();

	        _this3.requestSender.attachFilesToTask({
	          taskId: data.item.getSourceId(),
	          attachedIds: data.attachedIds
	        }).then(function (response) {
	          data.item.updateIndicators(response.data);
	        });
	      };

	      var onRemoveItem = function onRemoveItem(baseEvent) {
	        _this3.requestSender.removeItem({
	          itemId: baseEvent.getData().getItemId(),
	          itemType: baseEvent.getData().getItemType(),
	          sourceId: baseEvent.getData().getSourceId(),
	          sortInfo: _this3.calculateSort(baseEvent.getTarget().getListItemsNode())
	        });
	      };

	      var onStartSprint = function onStartSprint(baseEvent) {
	        _this3.showStartSprintPopup(baseEvent.getTarget());
	      };

	      var onCompleteSprint = function onCompleteSprint(baseEvent) {
	        var sprint = baseEvent.getTarget();
	        var sprintPopup = new SprintPopup({
	          sprints: _this3.sprints
	        });
	        sprintPopup.showCompletePopup(sprint).then(function (requestData) {
	          _this3.requestSender.completeSprint(requestData).then(function (response) {
	            location.reload();
	          }).catch(function (response) {});
	        });
	      };

	      var onChangeTaskResponsible = function onChangeTaskResponsible(baseEvent) {
	        _this3.requestSender.changeTaskResponsible({
	          itemId: baseEvent.getData().getItemId(),
	          itemType: baseEvent.getData().getItemType(),
	          sourceId: baseEvent.getData().getSourceId(),
	          responsible: baseEvent.getData().gerResponsible()
	        });
	      };

	      var onRemoveSprint = function onRemoveSprint(baseEvent) {
	        var sprint = baseEvent.getTarget();

	        _this3.sprints.delete(sprint.getId());

	        _this3.requestSender.removeSprint({
	          sprintId: sprint.getId(),
	          sortInfo: _this3.calculateSprintSort()
	        });
	      };

	      var onChangeSprintName = function onChangeSprintName(baseEvent) {
	        _this3.requestSender.changeSprintName(baseEvent.getData());
	      };

	      var onChangeSprintDeadline = function onChangeSprintDeadline(baseEvent) {
	        _this3.requestSender.changeSprintDeadline(baseEvent.getData());
	      };

	      var onOpenAddEpicForm = function onOpenAddEpicForm(baseEvent) {
	        _this3.epic.openAddForm();
	      };

	      var onOpenListEpicGrid = function onOpenListEpicGrid(baseEvent) {
	        _this3.epic.openEpicsList();

	        _this3.epic.subscribe('onAfterEditEpic', function (innerBaseEvent) {
	          var response = innerBaseEvent.getData();
	          var updatedEpicInfo = response.data;

	          var oldEpicInfo = _this3.epic.getCurrentEpic();

	          _this3.getAllItems().forEach(function (item) {
	            var itemEpic = item.getEpic();

	            if (itemEpic && itemEpic.name === oldEpicInfo.name) {
	              item.setEpicAndTags(updatedEpicInfo);
	            }
	          });

	          _this3.tagSearcher.removeEpicFromSearcher(oldEpicInfo);

	          _this3.tagSearcher.addEpicToSearcher(updatedEpicInfo);
	        });
	      };

	      var onShowTagSearcher = function onShowTagSearcher(baseEvent) {
	        var data = baseEvent.getData();
	        var item = data.item;
	        var actionsPanelButton = data.button;

	        _this3.tagSearcher.showTagsDialog(item, actionsPanelButton);
	      };

	      var onShowEpicSearcher = function onShowEpicSearcher(baseEvent) {
	        var data = baseEvent.getData();
	        var item = data.item;
	        var actionsPanelButton = data.button;

	        _this3.tagSearcher.showEpicDialog(item, actionsPanelButton);
	      };

	      var onStartDecomposition = function onStartDecomposition(baseEvent) {
	        var entity = baseEvent.getTarget();
	        var parentItem = baseEvent.getData();
	        var decomposition = new Decomposition();
	        decomposition.subscribe('tagsSearchOpen', onTagsSearchOpen);
	        decomposition.subscribe('tagsSearchClose', onTagsSearchClose);
	        decomposition.subscribe('createItem', function (innerBaseEvent) {
	          var inputValue = innerBaseEvent.getData();
	          var decomposedItems = decomposition.getDecomposedItems();
	          var lastDecomposedItem = Array.from(decomposedItems).pop();

	          var newItem = _this3.createItem(parentItem.getItemType(), inputValue);

	          newItem.setParentEntity(entity.getId(), entity.getEntityType());
	          newItem.setParentId(parentItem.getParentId());
	          newItem.setParentSourceId(parentItem.getSourceId());
	          newItem.setSort(lastDecomposedItem.getSort() + 1);
	          newItem.setTags(parentItem.getTags());

	          _this3.appendItem(newItem, entity.getListItemsNode(), lastDecomposedItem.getItemNode());

	          decomposition.addDecomposedItem(newItem);

	          _this3.sendRequestToCreateTask(entity, newItem, inputValue).then(function (response) {
	            _this3.fillItemAfterCreation(newItem, response.data);

	            response.data.tags.forEach(function (tag) {
	              _this3.tagSearcher.addTagToSearcher(tag);
	            });
	            entity.setItem(newItem);
	          });
	        });
	        decomposition.decomposeItem(parentItem);
	      };

	      var onTagsSearchOpen = function onTagsSearchOpen(baseEvent) {
	        var data = baseEvent.getData();
	        var inputObject = data.inputObject;
	        var enteredHashTagName = data.enteredHashTagName;

	        _this3.tagSearcher.showTagsSearchDialog(inputObject, enteredHashTagName);
	      };

	      var onTagsSearchClose = function onTagsSearchClose() {
	        _this3.tagSearcher.closeTagsSearchDialog();
	      };

	      var onEpicSearchOpen = function onEpicSearchOpen(baseEvent) {
	        var data = baseEvent.getData();
	        var inputObject = data.inputObject;
	        var enteredHashEpicName = data.enteredHashEpicName;

	        _this3.tagSearcher.showEpicSearchDialog(inputObject, enteredHashEpicName);
	      };

	      var onEpicSearchClose = function onEpicSearchClose() {
	        _this3.tagSearcher.closeEpicSearchDialog();
	      };

	      var subscribeToSprint = function subscribeToSprint(sprint) {
	        sprint.subscribe('createTaskItem', createTaskItem);
	        sprint.subscribe('updateItem', onUpdateItem);
	        sprint.subscribe('showTask', onShowTask);
	        sprint.subscribe('moveItem', onMoveItem);
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
	      };

	      if (newSprint) {
	        subscribeToSprint(newSprint);
	        return;
	      }

	      this.backlog.subscribe('createTaskItem', createTaskItem);
	      this.backlog.subscribe('updateItem', onUpdateItem);
	      this.backlog.subscribe('showTask', onShowTask);
	      this.backlog.subscribe('moveItem', onMoveItem);
	      this.backlog.subscribe('moveToSprint', onMoveToSprint);
	      this.backlog.subscribe('removeItem', onRemoveItem);
	      this.backlog.subscribe('changeTaskResponsible', onChangeTaskResponsible);
	      this.backlog.subscribe('openAddEpicForm', onOpenAddEpicForm);
	      this.backlog.subscribe('openListEpicGrid', onOpenListEpicGrid);
	      this.backlog.subscribe('attachFilesToTask', onAttachFilesToTask);
	      this.backlog.subscribe('showTagSearcher', onShowTagSearcher);
	      this.backlog.subscribe('showEpicSearcher', onShowEpicSearcher);
	      this.backlog.subscribe('startDecomposition', onStartDecomposition);
	      this.backlog.subscribe('tagsSearchOpen', onTagsSearchOpen);
	      this.backlog.subscribe('tagsSearchClose', onTagsSearchClose);
	      this.backlog.subscribe('epicSearchOpen', onEpicSearchOpen);
	      this.backlog.subscribe('epicSearchClose', onEpicSearchClose);
	      this.sprints.forEach(function (sprint) {
	        subscribeToSprint(sprint);
	      });
	    }
	    /**
	     * @returns {HTMLElement}
	     */

	  }, {
	    key: "renderSprintsContainer",
	    value: function renderSprintsContainer() {
	      var _this4 = this;

	      var createCreatingButton = function createCreatingButton() {
	        _this4.sprintCreatingButtonNodeId = 'tasks-scrum-sprint-creating-button';
	        return main_core.Tag.render(_templateObject$f(), _this4.sprintCreatingButtonNodeId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD'));
	      };

	      var createCreatingDropZone = function createCreatingDropZone() {
	        if (_this4.sprints.size) {
	          return '';
	        }

	        _this4.sprintCreatingDropZoneNodeId = 'tasks-scrum-sprint-creating-drop-zone';
	        return main_core.Tag.render(_templateObject2$a(), _this4.sprintCreatingDropZoneNodeId, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_DROP'));
	      };

	      var createSprintsList = function createSprintsList() {
	        _this4.sprintListNodeId = 'tasks-scrum-sprint-list';
	        return main_core.Tag.render(_templateObject3$7(), _this4.sprintListNodeId, babelHelpers.toConsumableArray(_this4.sprints.values()).map(function (sprint) {
	          if (sprint.isActive()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }), babelHelpers.toConsumableArray(_this4.sprints.values()).map(function (sprint) {
	          if (sprint.isPlanned()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }), babelHelpers.toConsumableArray(_this4.sprints.values()).map(function (sprint) {
	          if (sprint.isCompleted()) {
	            return sprint.render();
	          } else {
	            return '';
	          }
	        }));
	      };

	      return main_core.Tag.render(_templateObject4$3(), createCreatingButton(), createCreatingDropZone(), createSprintsList());
	    }
	  }, {
	    key: "createSprint",
	    value: function createSprint() {
	      var _this5 = this;

	      main_core.Dom.remove(this.sprintCreatingDropZoneNode);
	      var countSprints = this.sprints.size;
	      var title = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_NAME').replace('%s', countSprints + 1);
	      var storyPoints = 0;
	      var dateStart = Math.floor(Date.now() / 1000);
	      var dateEnd = Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10);
	      var sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	      var data = {
	        name: title,
	        sort: 1,
	        dateStart: dateStart,
	        dateEnd: dateEnd,
	        sortInfo: this.calculateSprintSort(1)
	      };
	      return this.requestSender.createSprint(data).then(function (response) {
	        var sprint = new Sprint({
	          id: response.data.sprintId,
	          name: title,
	          sort: 1,
	          dateStart: dateStart,
	          dateEnd: dateEnd,
	          storyPoints: storyPoints
	        });

	        if (sprintListNode.children.length) {
	          main_core.Dom.insertBefore(sprint.render(), sprintListNode.firstElementChild);
	        } else {
	          main_core.Dom.insertBefore(sprint.render(), sprintListNode);
	        }

	        sprint.onAfterAppend();
	        sprint.getSprintNode().scrollIntoView(true);

	        _this5.sprints.set(sprint.getId(), sprint);

	        _this5.bindHandlers(sprint);

	        _this5.draggableItems.addContainer(sprint.getListItemsNode());

	        return sprint;
	      }).catch(function (response) {});
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(itemType, value) {
	      var valueWithoutTags = value.replace(new RegExp('#([^\\s]*)', 'g'), '').replace(new RegExp('@([^\\s]*)', 'g'), '');
	      return new Item({
	        'itemId': '',
	        'itemType': itemType,
	        'name': valueWithoutTags
	      });
	    }
	  }, {
	    key: "appendItem",
	    value: function appendItem(item, entityListNode, bindItemNode) {
	      this.appendItemAfterItem(item.render(), bindItemNode);
	      item.onAfterAppend(entityListNode);
	    }
	  }, {
	    key: "sendRequestToCreateTask",
	    value: function sendRequestToCreateTask(entity, item, value) {
	      var requestData = {
	        'itemType': item.getItemType(),
	        'name': value,
	        'entityId': item.getEntityId(),
	        'entityType': entity.getEntityType(),
	        'parentId': item.getParentId(),
	        'sort': item.getSort(),
	        'storyPoints': item.getStoryPoints(),
	        'tags': item.getTags(),
	        'epic': item.getEpic(),
	        'parentSourceId': item.getParentSourceId(),
	        'sortInfo': this.calculateSort(entity.getListItemsNode()),
	        'isActiveSprint': entity.getEntityType() === 'sprint' && entity.isActive() ? 'Y' : 'N'
	      };
	      return this.requestSender.createTask(requestData);
	    }
	  }, {
	    key: "fillItemAfterCreation",
	    value: function fillItemAfterCreation(item, responseData) {
	      item.setItemId(responseData.itemId);
	      item.setEpicAndTags(responseData.epic, responseData.tags);
	      item.setResponsible(responseData.responsible);
	      item.setSourceId(responseData.sourceId);
	    }
	  }, {
	    key: "moveToSprint",
	    value: function moveToSprint(item, button) {
	      var _this6 = this;

	      var getAvailableSprintsToMove = function getAvailableSprintsToMove() {
	        var sprints = new Set();

	        _this6.sprints.forEach(function (sprint) {
	          if (!sprint.isCompleted()) {
	            sprints.add(sprint);
	          }
	        });

	        return sprints;
	      };

	      var sprints = getAvailableSprintsToMove();

	      if (sprints.size > 1) {
	        this.showMoveToSprintMenu(item, button);
	      } else {
	        var moveToNewSprint = function moveToNewSprint() {
	          _this6.createSprint().then(function (sprint) {
	            _this6.moveTo(_this6.backlog, sprint, item);
	          });
	        };

	        if (sprints.size === 0) {
	          moveToNewSprint();
	        } else {
	          sprints.forEach(function (sprint) {
	            _this6.moveTo(_this6.backlog, sprint, item);
	          });
	        }

	        main_core.Dom.remove(button.parentNode);
	      }
	    }
	  }, {
	    key: "moveToBacklog",
	    value: function moveToBacklog(sprint, item) {
	      this.moveTo(sprint, this.backlog, item, false);
	    }
	  }, {
	    key: "moveTo",
	    value: function moveTo(entityFrom, entityTo, item) {
	      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
	      var itemNode = item.getItemNode();
	      var entityListNode = entityTo.getListItemsNode();

	      if (after) {
	        main_core.Dom.append(itemNode, entityListNode);
	      } else {
	        this.appendItemAfterItem(itemNode, entityListNode.firstElementChild);
	      }

	      this.moveItemFromEntityToEntity(item, entityFrom, entityTo);
	      this.requestSender.updateItem({
	        itemId: item.getItemId(),
	        itemType: item.getItemType(),
	        entityId: entityTo.getId(),
	        fromActiveSprint: entityFrom.getEntityType() === 'sprint' && entityFrom.isActive() ? 'Y' : 'N',
	        toActiveSprint: entityTo.getEntityType() === 'sprint' && entityTo.isActive() ? 'Y' : 'N',
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(entityFrom.getListItemsNode()), this.calculateSort(entityTo.getListItemsNode()))
	      });
	    }
	  }, {
	    key: "moveItemFromEntityToEntity",
	    value: function moveItemFromEntityToEntity(item, entityFrom, entityTo) {
	      entityFrom.removeItem(item);
	      item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
	      item.setDisableStatus(false);
	      entityTo.setItem(item);
	    }
	  }, {
	    key: "showMoveToSprintMenu",
	    value: function showMoveToSprintMenu(item, button) {
	      var _this7 = this;

	      var id = "item-sprint-action-".concat(item.itemId);

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
	      this.sprints.forEach(function (sprint) {
	        if (!sprint.isCompleted()) {
	          _this7.moveToSprintMenu.addMenuItem({
	            text: sprint.getName(),
	            onclick: function onclick(event, menuItem) {
	              _this7.moveTo(_this7.backlog, sprint, item);

	              menuItem.getMenuWindow().close();
	            }
	          });
	        }
	      });
	      this.moveToSprintMenu.show();
	    }
	  }, {
	    key: "calculateSort",
	    value: function calculateSort(container) {
	      var _this8 = this;

	      var listSortInfo = {};
	      var items = babelHelpers.toConsumableArray(container.querySelectorAll('[data-sort]'));
	      var sort = 1;
	      items.forEach(function (itemNode) {
	        var itemId = itemNode.dataset.itemId;

	        var item = _this8.findItemByItemId(itemId);

	        if (item) {
	          item.setSort(sort);
	          listSortInfo[itemId] = {
	            entityId: container.dataset.entityId,
	            sort: sort
	          };
	          itemNode.dataset.sort = sort;
	        }

	        sort++;
	      });
	      return listSortInfo;
	    }
	  }, {
	    key: "calculateSprintSort",
	    value: function calculateSprintSort() {
	      var _this9 = this;

	      var increment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      var listSortInfo = {};
	      var container = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	      var sprints = babelHelpers.toConsumableArray(container.querySelectorAll('[data-sprint-sort]'));
	      var sort = 1 + increment;
	      sprints.forEach(function (sprintNode) {
	        var sprintId = sprintNode.dataset.sprintId;

	        var sprint = _this9.findEntityByEntityId(sprintId);

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
	  }, {
	    key: "appendItemAfterItem",
	    value: function appendItemAfterItem(newNode, item) {
	      if (item.nextElementSibling) {
	        main_core.Dom.insertBefore(newNode, item.nextElementSibling);
	      } else {
	        main_core.Dom.append(newNode, item.parentElement);
	      }
	    }
	  }, {
	    key: "showStartSprintPopup",
	    value: function showStartSprintPopup(sprint) {
	      var _this10 = this;

	      //todo move to sprint.popup
	      this.popupId = 'tasks-scrum-start-sprint' + main_core.Text.getRandom();
	      var sprintDate = new SprintDate(sprint);
	      sprintDate.subscribe('changeSprintDeadline', function (baseEvent) {
	        var requestData = baseEvent.getData();

	        _this10.requestSender.changeSprintDeadline(baseEvent.getData()).then(function (response) {
	          if (requestData.hasOwnProperty('dateStart')) {
	            sprint.updateDateStartNode(requestData.dateStart);
	          } else if (requestData.hasOwnProperty('dateEnd')) {
	            sprint.updateDateEndNode(requestData.dateEnd);
	          }
	        }).catch(function (response) {});
	      });

	      var getPopupContent = function getPopupContent() {
	        return main_core.Tag.render(_templateObject5$3(), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_DURATION'), main_core.Text.encode(sprintDate.createDate(sprint.getDateStart(), sprint.getDateEnd())), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_TAKEN'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_TAKEN_VALUE').replace('#storyPoints#', sprint.getStoryPoints()));
	      };

	      var startSprint = function startSprint() {
	        _this10.requestSender.startSprint({
	          sprintId: sprint.getId()
	        }).then(function (response) {
	          sprint.setStatus('active');

	          _this10.popup.close();

	          location.href = _this10.tabs['activeSprint'].url;
	        }).catch(function (response) {
	          ui_dialogs_messagebox.MessageBox.alert(response.errors.shift().message, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
	        });
	      };

	      this.popup = new main_popup.Popup(this.popupId, null, {
	        width: 360,
	        autoHide: true,
	        closeByEsc: true,
	        offsetTop: 0,
	        offsetLeft: 0,
	        closeIcon: true,
	        draggable: true,
	        resizable: false,
	        lightShadow: true,
	        cacheable: false,
	        titleBar: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_TITLE_POPUP').replace('#name#', sprint.getName()),
	        content: getPopupContent(),
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_START_POPUP'),
	          color: ui_buttons.Button.Color.PRIMARY,
	          events: {
	            click: function click() {
	              return startSprint();
	            }
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_CANCEL_POPUP'),
	          color: ui_buttons.Button.Color.LINK,
	          events: {
	            click: function click() {
	              return _this10.popup.close();
	            }
	          }
	        })]
	      });
	      this.popup.show();
	      sprintDate.onAfterAppend();
	    }
	  }, {
	    key: "onItemMove",
	    value: function onItemMove(dragEndEvent) {
	      var _this11 = this;

	      if (!dragEndEvent.endContainer) {
	        return;
	      }

	      var sourceContainer = dragEndEvent.sourceContainer;
	      var endContainer = dragEndEvent.endContainer;

	      if (endContainer === this.sprintCreatingDropZoneNode) {
	        var createNewSprintAndMoveItem = function createNewSprintAndMoveItem() {
	          _this11.createSprint().then(function (sprint) {
	            var itemNode = dragEndEvent.source;
	            var itemId = itemNode.dataset.itemId;

	            var item = _this11.findItemByItemId(itemId);

	            _this11.moveTo(_this11.backlog, sprint, item);
	          });
	        };

	        createNewSprintAndMoveItem();
	        return;
	      }

	      var sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
	      var endEntityId = parseInt(endContainer.dataset.entityId, 10);

	      if (sourceEntityId === endEntityId) {
	        var moveInCurrentContainer = function moveInCurrentContainer() {
	          _this11.requestSender.updateItemSort({
	            sortInfo: _this11.calculateSort(sourceContainer)
	          });
	        };

	        moveInCurrentContainer();
	      } else {
	        var moveInAnotherContainer = function moveInAnotherContainer() {
	          var itemNode = dragEndEvent.source;
	          var itemId = itemNode.dataset.itemId;

	          var item = _this11.findItemByItemId(itemId);

	          var sourceEntity = _this11.findEntityByEntityId(sourceEntityId);

	          var endEntity = _this11.findEntityByEntityId(endEntityId);

	          _this11.moveItemFromEntityToEntity(item, sourceEntity, endEntity);

	          _this11.requestSender.updateItemSort({
	            entityId: endEntity.getId(),
	            itemId: item.getItemId(),
	            itemType: item.getItemType(),
	            fromActiveSprint: sourceEntity.getEntityType() === 'sprint' && sourceEntity.isActive() ? 'Y' : 'N',
	            toActiveSprint: endEntity.getEntityType() === 'sprint' && endEntity.isActive() ? 'Y' : 'N',
	            sortInfo: _this11.calculateSort(endContainer)
	          });
	        };

	        moveInAnotherContainer();
	      }
	    }
	  }, {
	    key: "onSprintMove",
	    value: function onSprintMove(dragEndEvent) {
	      if (!dragEndEvent.endContainer) {
	        return;
	      }

	      this.requestSender.updateSprintSort({
	        sortInfo: this.calculateSprintSort()
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
	    key: "moveItem",
	    value: function moveItem(item, button) {
	      var _this12 = this;

	      var entity = this.findEntityByItemId(item.getItemId());
	      var listToMove = [];

	      if (!entity.isFirstItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
	          onclick: function onclick(event, menuItem) {
	            _this12.moveItemToUp(item, entity.getListItemsNode(), entity.hasInput());

	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      if (!entity.isLastItem(item)) {
	        listToMove.push({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
	          onclick: function onclick(event, menuItem) {
	            _this12.moveItemToDown(item, entity.getListItemsNode());

	            menuItem.getMenuWindow().close();
	          }
	        });
	      }

	      this.showMoveItemMenu(item, button, listToMove);
	    }
	  }, {
	    key: "showMoveItemMenu",
	    value: function showMoveItemMenu(item, button, listToMove) {
	      var _this13 = this;

	      var id = "item-move-".concat(item.itemId);

	      if (this.moveItemMenu) {
	        this.moveItemMenu.getPopupWindow().destroy();
	      }

	      this.moveItemMenu = new main_popup.Menu({
	        id: id,
	        bindElement: button
	      });
	      listToMove.forEach(function (item) {
	        _this13.moveItemMenu.addMenuItem(item);
	      });
	      this.moveItemMenu.show();
	    }
	  }, {
	    key: "moveItemToUp",
	    value: function moveItemToUp(item, listItemsNode) {
	      var entityWithInput = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

	      if (entityWithInput) {
	        this.appendItemAfterItem(item.getItemNode(), listItemsNode.firstElementChild);
	      } else {
	        main_core.Dom.insertBefore(item.getItemNode(), listItemsNode.firstElementChild);
	      }

	      this.requestSender.updateItem({
	        itemId: item.getItemId(),
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(listItemsNode))
	      });
	    }
	  }, {
	    key: "moveItemToDown",
	    value: function moveItemToDown(item, listItemsNode) {
	      main_core.Dom.append(item.getItemNode(), listItemsNode);
	      this.requestSender.updateItem({
	        itemId: item.getItemId(),
	        sortInfo: babelHelpers.objectSpread({}, this.calculateSort(listItemsNode))
	      });
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
	      var _this14 = this;

	      this.requestSender.removeItem({
	        itemId: epicId,
	        itemType: 'epic'
	      }).then(function (response) {
	        var epicInfo = response.data;

	        _this14.getAllItems().forEach(function (item) {
	          var itemEpic = item.getEpic();

	          if (itemEpic && itemEpic.name === epicInfo.name) {
	            item.setEpicAndTags(null);
	          }
	        });

	        _this14.tagSearcher.removeEpicFromSearcher(epicInfo);

	        _this14.sidePanel.reloadTopSidePanel();
	      });
	    }
	  }, {
	    key: "fadeOutAll",
	    value: function fadeOutAll() {
	      this.backlog.fadeOut();
	      this.sprints.forEach(function (sprint) {
	        sprint.fadeOut();
	      });
	    }
	  }, {
	    key: "fadeInAll",
	    value: function fadeInAll() {
	      this.backlog.fadeIn();
	      this.sprints.forEach(function (sprint) {
	        sprint.fadeIn();
	      });
	    }
	  }, {
	    key: "removeItemFromEntities",
	    value: function removeItemFromEntities(item) {
	      this.backlog.removeItem(item);
	      this.sprints.forEach(function (sprint) {
	        sprint.removeItem(item);
	      });
	      item.removeYourself();
	    }
	  }, {
	    key: "createTaskItemByItemData",
	    value: function createTaskItemByItemData(itemData) {
	      return new Item({
	        itemId: itemData.itemId,
	        itemType: itemData.itemType,
	        name: itemData.name,
	        entityId: itemData.entityId,
	        entityType: itemData.entityType,
	        parentId: itemData.parentId,
	        sort: itemData.sort,
	        storyPoints: itemData.storyPoints,
	        sourceId: itemData.sourceId,
	        completed: itemData.completed,
	        responsible: itemData.responsible,
	        attachedFilesCount: itemData.attachedFilesCount,
	        checkListComplete: itemData.checkListComplete,
	        checkListAll: itemData.checkListAll,
	        newCommentsCount: itemData.newCommentsCount,
	        epic: itemData.epic,
	        tags: itemData.tags
	      });
	    }
	  }, {
	    key: "appendNewItemToEntity",
	    value: function appendNewItemToEntity(newItem) {
	      var entity = this.findEntityByEntityId(newItem.getEntityId());
	      main_core.Dom.append(newItem.render(), entity.getListItemsNode());
	      entity.setItem(newItem);
	      newItem.onAfterAppend(entity.getListItemsNode());
	    }
	  }]);
	  return Scrum;
	}();

	exports.Scrum = Scrum;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI,BX.UI.EntitySelector,BX.Main,BX.UI,BX.UI.DragAndDrop,BX.UI.Dialogs,BX,BX.Event));
//# sourceMappingURL=script.js.map

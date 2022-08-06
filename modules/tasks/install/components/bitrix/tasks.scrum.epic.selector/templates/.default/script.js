this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,main_core_events,ui_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	var ViewSelector = /*#__PURE__*/function () {
	  function ViewSelector(params) {
	    babelHelpers.classCallCheck(this, ViewSelector);
	    this.groupId = params.groupId;
	    this.taskId = params.taskId;
	    this.epic = params.epic;
	    this.canEdit = params.canEdit;
	    this.dialog = null;
	    this.node = null;
	    this.nameNode = null;
	    this.selectorNode = null;
	    main_core_events.EventEmitter.subscribe('onChangeProjectLink', this.onChangeTaskProject.bind(this));
	  }

	  babelHelpers.createClass(ViewSelector, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      main_core.Dom.append(this.render(), container);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.renderName(), this.renderSelector());
	      return this.node;
	    }
	  }, {
	    key: "onChangeTaskProject",
	    value: function onChangeTaskProject(baseEvent) {
	      var _baseEvent$getCompatD = baseEvent.getCompatData(),
	          _baseEvent$getCompatD2 = babelHelpers.slicedToArray(_baseEvent$getCompatD, 2),
	          groupId = _baseEvent$getCompatD2[0],
	          taskId = _baseEvent$getCompatD2[1];

	      this.groupId = parseInt(groupId, 10);
	      this.taskId = parseInt(taskId, 10);
	      this.epic = null;
	      this.dialog = null;
	      this.updateSelector(null);
	    }
	  }, {
	    key: "renderName",
	    value: function renderName() {
	      if (main_core.Type.isNull(this.epic)) {
	        return '';
	      }

	      var colorBorder = this.convertHexToRGBA(this.epic.color, 0.7);
	      var colorBackground = this.convertHexToRGBA(this.epic.color, 0.3);
	      this.nameNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__epic-selector--epic\"\n\t\t\t\tstyle=\"background: ", "; border-color: ", ";\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), colorBackground, colorBorder, main_core.Text.encode(this.epic.name));
	      return this.nameNode;
	    }
	  }, {
	    key: "renderSelector",
	    value: function renderSelector() {
	      this.selectorNode = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"ui-btn-link tasks-scrum__epic-selector--link\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.getButtonText());
	      var buttonNode = this.selectorNode.firstElementChild;
	      main_core.Event.bind(buttonNode, 'click', this.onClick.bind(this, buttonNode));
	      return this.selectorNode;
	    }
	  }, {
	    key: "onClick",
	    value: function onClick(buttonNode) {
	      var _this = this;

	      if (this.dialog) {
	        if (this.dialog.isOpen()) {
	          this.dialog.hide();
	        } else {
	          this.dialog.show();
	        }

	        return;
	      }

	      this.dialog = new ui_entitySelector.Dialog({
	        id: main_core.Text.getRandom(),
	        targetNode: this.selectorNode,
	        width: 350,
	        height: 300,
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        compactView: true,
	        hideOnDeselect: true,
	        selectedItems: [],
	        items: [],
	        searchOptions: {
	          allowCreateItem: true,
	          footerOptions: {
	            label: main_core.Loc.getMessage('TSE_SELECTOR_SEARCHER_EPIC_ADD')
	          }
	        },
	        events: {
	          'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	            return new Promise(function (resolve) {
	              var _event$getData = event.getData(),
	                  searchQuery = _event$getData.searchQuery;

	              _this.createEpic(searchQuery.getQuery()).then(function (epic) {
	                var epicDialogItem = _this.getEpicDialogItem(epic);

	                epicDialogItem.selected = true;
	                epicDialogItem.sort = 1;

	                _this.dialog.addItem(epicDialogItem);

	                _this.dialog.hide();

	                resolve();
	              });
	            });
	          }
	        },
	        tagSelectorOptions: {
	          textBoxWidth: 300
	        }
	      });
	      this.dialog.subscribe('onHide', function () {
	        var selectedItems = _this.dialog.getSelectedItems();

	        var epicId = selectedItems.length ? selectedItems[0].getId() : 0;

	        _this.changeTaskEpic(epicId).then(function (epic) {
	          return _this.updateSelector(epic);
	        });
	      });
	      this.dialog.showLoader();
	      this.dialog.show();
	      this.getEpics().then(function (epics) {
	        if (!main_core.Type.isNull(_this.epic)) {
	          var selectedItem = _this.getEpicDialogItem(_this.epic);

	          selectedItem.selected = true;
	          selectedItem.sort = 1;

	          _this.dialog.addItem(selectedItem);
	        }

	        epics.forEach(function (item) {
	          _this.dialog.addItem(item);
	        });

	        _this.dialog.hideLoader();
	      })["catch"](function (response) {
	        _this.showErrorAlert(response);

	        _this.dialog.hideLoader();
	      });
	    }
	  }, {
	    key: "updateSelector",
	    value: function updateSelector(epic) {
	      this.epic = epic;
	      this.selectorNode.firstElementChild.textContent = this.getButtonText();

	      if (main_core.Type.isNull(epic)) {
	        main_core.Dom.remove(this.nameNode);
	        this.nameNode = null;
	      } else {
	        if (main_core.Type.isNull(this.nameNode)) {
	          main_core.Dom.insertBefore(this.renderName(), this.selectorNode);
	        } else {
	          main_core.Dom.replace(this.nameNode, this.renderName());
	        }
	      }
	    }
	  }, {
	    key: "getEpics",
	    value: function getEpics() {
	      var _this2 = this;

	      return main_core.ajax.runComponentAction('bitrix:tasks.scrum.epic.selector', 'getEpics', {
	        mode: 'class',
	        data: {
	          groupId: this.groupId
	        }
	      }).then(function (response) {
	        var epics = response.data;

	        if (main_core.Type.isNull(epics)) {
	          return [];
	        }

	        var list = [];
	        epics.forEach(function (epic) {
	          list.push(_this2.getEpicDialogItem(epic));
	        });
	        return list;
	      })["catch"](function (response) {
	        return _this2.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getEpicDialogItem",
	    value: function getEpicDialogItem(epic) {
	      var avatar = '/bitrix/components/bitrix/tasks.scrum.epic.selector/templates/.default' + '/images/search-hashtag-green.svg';
	      return {
	        id: epic.id,
	        entityId: 'epic',
	        title: epic.name,
	        tabs: 'recents',
	        avatar: avatar
	      };
	    }
	  }, {
	    key: "changeTaskEpic",
	    value: function changeTaskEpic(epicId) {
	      var _this3 = this;

	      return main_core.ajax.runComponentAction('bitrix:tasks.scrum.epic.selector', 'changeTaskEpic', {
	        mode: 'class',
	        data: {
	          taskId: this.taskId,
	          epicId: epicId
	        }
	      }).then(function (response) {
	        return response.data;
	      })["catch"](function (response) {
	        return _this3.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "createEpic",
	    value: function createEpic(epicName) {
	      var _this4 = this;

	      return main_core.ajax.runAction('bitrix:tasks.scrum.epic.createEpic', {
	        data: {
	          groupId: this.groupId,
	          name: epicName
	        }
	      }).then(function (response) {
	        return response.data;
	      })["catch"](function (response) {
	        return _this4.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getButtonText",
	    value: function getButtonText() {
	      if (main_core.Type.isNull(this.epic)) {
	        return main_core.Loc.getMessage('TSE_SELECTOR_ADD');
	      } else {
	        return main_core.Loc.getMessage('TSE_SELECTOR_EDIT');
	      }
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
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        return;
	      }

	      if (response.errors.length) {
	        var firstError = response.errors.shift();

	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSE_SELECTOR_ERROR_POPUP_TITLE');
	          top.BX.UI.Dialogs.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return ViewSelector;
	}();

	var _templateObject$1;
	var EditSelector = /*#__PURE__*/function () {
	  function EditSelector(params) {
	    babelHelpers.classCallCheck(this, EditSelector);
	    this.groupId = params.groupId;
	    this.taskId = params.taskId;
	    this.savedEpic = params.epic;
	    this.inputName = params.inputName;
	    this.selector = null;
	    this.listEpics = new Map();
	    this.node = null;
	    this.inputNode = null;
	    main_core_events.EventEmitter.subscribe('BX.Tasks.MemberSelector:projectSelected', this.onProjectSelected.bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Tasks.Component.Task:projectPreselected', this.onProjectPreselected.bind(this));
	  }

	  babelHelpers.createClass(EditSelector, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      this.node = container;
	      main_core.Dom.addClass(this.node, 'tasks-scrum-epic-edit-selector');

	      if (this.inputName) {
	        main_core.Dom.append(this.renderInput(), this.node);
	      }

	      this.buildSelector().renderTo(this.node);
	    }
	  }, {
	    key: "renderInput",
	    value: function renderInput() {
	      var value = this.savedEpic ? parseInt(this.savedEpic.id, 10) : 0;
	      this.inputNode = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"hidden\" name=\"", "\" value=\"", "\">\n\t\t"])), main_core.Text.encode(this.inputName), value);
	      return this.inputNode;
	    }
	  }, {
	    key: "buildSelector",
	    value: function buildSelector() {
	      var _this = this;

	      // todo add scrum epic provider
	      this.selector = new ui_entitySelector.TagSelector({
	        multiple: false,
	        textBoxWidth: 200,
	        dialogOptions: {
	          width: 350,
	          height: 240,
	          dropdownMode: true,
	          compactView: true,
	          multiple: false,
	          hideOnDeselect: true,
	          searchOptions: {
	            allowCreateItem: true,
	            footerOptions: {
	              label: main_core.Loc.getMessage('TSE_SELECTOR_SEARCHER_EPIC_ADD')
	            }
	          },
	          items: [],
	          events: {
	            'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	              return new Promise(function (resolve) {
	                var _event$getData = event.getData(),
	                    searchQuery = _event$getData.searchQuery;

	                var dialog = event.getTarget();

	                _this.createEpic(searchQuery.getQuery()).then(function (epic) {
	                  var epicDialogItem = _this.getEpicDialogItem(epic);

	                  epicDialogItem.selected = true;
	                  epicDialogItem.sort = 1;
	                  dialog.addItem(epicDialogItem);

	                  _this.updateInputValue(epicDialogItem.id);

	                  resolve();
	                });
	              });
	            },
	            'Item:onSelect': function ItemOnSelect(event) {
	              var selectedItem = event.getData().item;

	              _this.updateInputValue(selectedItem.getId());
	            },
	            'Item:onDeselect': function ItemOnDeselect(event) {
	              var dialog = event.getTarget();
	              setTimeout(function () {
	                if (dialog.getSelectedItems().length === 0) {
	                  _this.updateInputValue(0);
	                }
	              }, 50);
	            }
	          }
	        }
	      });
	      this.selector.subscribe('onMetaEnter', function (baseEvent) {
	        var tagSelector = baseEvent.getTarget();

	        if (tagSelector.getDialog().isOpen()) {
	          var _baseEvent$getData = baseEvent.getData(),
	              keyboardEvent = _baseEvent$getData.event;

	          keyboardEvent.stopPropagation();
	        }
	      });
	      this.selector.getDialog().subscribe('onShow', function () {
	        _this.updateSelectorItems();
	      });

	      if (this.savedEpic) {
	        this.updateSelectorItems();
	      }

	      return this.selector;
	    }
	  }, {
	    key: "onProjectSelected",
	    value: function onProjectSelected(baseEvent) {
	      var data = baseEvent.getData();
	      this.groupId = parseInt(data.ID, 10);
	      this.listEpics.clear();
	      this.selector.getDialog().removeItems();
	      this.updateInputValue(0);
	    }
	  }, {
	    key: "onProjectPreselected",
	    value: function onProjectPreselected(baseEvent) {
	      var data = baseEvent.getData();
	      this.groupId = parseInt(data.groupId, 10);
	      this.listEpics.clear();
	      this.selector.getDialog().removeItems();
	      this.updateInputValue(0);
	    }
	  }, {
	    key: "updateSelectorItems",
	    value: function updateSelectorItems() {
	      var _this2 = this;

	      if (this.groupId === 0 || this.listEpics.has(this.groupId)) {
	        return;
	      }

	      this.selector.getDialog().removeItems();
	      this.selector.getDialog().showLoader();
	      this.getEpics().then(function (epics) {
	        epics.forEach(function (item) {
	          if (_this2.savedEpic && _this2.savedEpic.id === item.id) {
	            item.selected = true;
	            item.sort = 1;

	            _this2.selector.addTag(item);

	            _this2.updateInputValue(item.id);
	          }

	          _this2.selector.getDialog().addItem(item);
	        });

	        _this2.selector.getDialog().hideLoader();

	        return true;
	      })["catch"](function (response) {
	        return _this2.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "updateInputValue",
	    value: function updateInputValue(epicId) {
	      this.inputNode.value = parseInt(epicId, 10);
	    }
	  }, {
	    key: "getEpics",
	    value: function getEpics() {
	      var _this3 = this;

	      return main_core.ajax.runComponentAction('bitrix:tasks.scrum.epic.selector', 'getEpics', {
	        mode: 'class',
	        data: {
	          groupId: this.groupId
	        }
	      }).then(function (response) {
	        var epics = response.data;

	        if (main_core.Type.isNull(epics)) {
	          return [];
	        }

	        var list = [];
	        epics.forEach(function (epic) {
	          list.push(_this3.getEpicDialogItem(epic));
	        });

	        _this3.listEpics.set(_this3.groupId, list);

	        return list;
	      })["catch"](function (response) {
	        return _this3.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getEpicDialogItem",
	    value: function getEpicDialogItem(epic) {
	      var avatar = '/bitrix/components/bitrix/tasks.scrum.epic.selector/templates/.default' + '/images/search-hashtag-green.svg';
	      return {
	        id: epic.id,
	        entityId: 'epic',
	        title: epic.name,
	        tabs: 'recents',
	        avatar: avatar
	      };
	    }
	  }, {
	    key: "createEpic",
	    value: function createEpic(epicName) {
	      var _this4 = this;

	      return main_core.ajax.runAction('bitrix:tasks.scrum.epic.createEpic', {
	        data: {
	          groupId: this.groupId,
	          name: epicName
	        }
	      }).then(function (response) {
	        return response.data;
	      })["catch"](function (response) {
	        return _this4.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        return;
	      }

	      if (response.errors.length) {
	        var firstError = response.errors.shift();

	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSE_SELECTOR_ERROR_POPUP_TITLE');
	          top.BX.UI.Dialogs.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return EditSelector;
	}();

	var EpicSelector = /*#__PURE__*/function () {
	  function EpicSelector(params) {
	    babelHelpers.classCallCheck(this, EpicSelector);
	    this.groupId = parseInt(params.groupId, 10);
	    this.taskId = parseInt(params.taskId, 10);
	    this.epic = main_core.Type.isPlainObject(params.epic) ? params.epic : null;
	    this.canEdit = params.canEdit === 'Y';
	    this.mode = params.mode === 'edit' ? 'edit' : 'view';
	    this.inputName = params.inputName;
	  }

	  babelHelpers.createClass(EpicSelector, [{
	    key: "renderTo",
	    value: function renderTo(container) {
	      if (this.mode === 'view') {
	        new ViewSelector({
	          groupId: this.groupId,
	          taskId: this.taskId,
	          epic: this.epic,
	          canEdit: this.canEdit
	        }).renderTo(container);
	      } else {
	        new EditSelector({
	          groupId: this.groupId,
	          taskId: this.taskId,
	          epic: this.epic,
	          inputName: this.inputName
	        }).renderTo(container);
	      }
	    }
	  }]);
	  return EpicSelector;
	}();

	exports.EpicSelector = EpicSelector;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Event,BX.UI.EntitySelector));
//# sourceMappingURL=script.js.map

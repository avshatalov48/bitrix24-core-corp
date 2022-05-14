this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_sidepanel_layout,ui_layoutForm,ui_forms,main_core,main_core_events,main_loader,ui_dialogs_messagebox,ui_buttons) {
	'use strict';

	var ItemType = /*#__PURE__*/function () {
	  function ItemType() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ItemType);
	    this.setId(params.id);
	    this.setName(params.name);
	    this.setSort(params.sort);
	    this.setDodRequired(params.dodRequired);
	    this.setParticipants(params.participants);
	  }

	  babelHelpers.createClass(ItemType, [{
	    key: "setId",
	    value: function setId(id) {
	      this.id = main_core.Type.isInteger(id) ? parseInt(id, 10) : main_core.Type.isString(id) && id ? id : main_core.Text.getRandom();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.name = main_core.Type.isString(name) ? name : '';
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.name;
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(sort) {
	      this.sort = main_core.Type.isInteger(sort) ? parseInt(sort, 10) : 0;
	    }
	  }, {
	    key: "getSort",
	    value: function getSort() {
	      return this.sort;
	    }
	  }, {
	    key: "setDodRequired",
	    value: function setDodRequired(value) {
	      this.dodRequired = value === 'Y';
	    }
	  }, {
	    key: "isDodRequired",
	    value: function isDodRequired() {
	      return this.dodRequired;
	    }
	  }, {
	    key: "setParticipants",
	    value: function setParticipants(participants) {
	      var _this = this;

	      this.participants = [];

	      if (!main_core.Type.isArray(participants)) {
	        return;
	      }

	      participants.forEach(function (participant) {
	        _this.participants.push([participant.entityId, participant.id]);
	      });
	    }
	  }, {
	    key: "getParticipants",
	    value: function getParticipants() {
	      return this.participants;
	    }
	  }]);
	  return ItemType;
	}();

	var TypeStorage = /*#__PURE__*/function () {
	  function TypeStorage() {
	    babelHelpers.classCallCheck(this, TypeStorage);
	  }

	  babelHelpers.createClass(TypeStorage, [{
	    key: "setTypes",
	    value: function setTypes(types) {
	      this.types = types;
	    }
	  }, {
	    key: "getNextType",
	    value: function getNextType() {
	      return this.types.values().next().value;
	    }
	  }, {
	    key: "getTypes",
	    value: function getTypes() {
	      return this.types;
	    }
	  }, {
	    key: "addType",
	    value: function addType(type) {
	      this.types.set(type.getId(), type);
	    }
	  }, {
	    key: "getType",
	    value: function getType(typeId) {
	      return this.types.get(typeId);
	    }
	  }, {
	    key: "removeType",
	    value: function removeType(type) {
	      this.types["delete"](type.getId());
	    }
	  }]);
	  return TypeStorage;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	var Tabs = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Tabs, _EventEmitter);

	  function Tabs() {
	    var _this;

	    babelHelpers.classCallCheck(this, Tabs);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tabs).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.Dod.Tabs');

	    _this.tabNodes = new Map();
	    _this.activeType = null;
	    _this.previousType = null;
	    return _this;
	  }

	  babelHelpers.createClass(Tabs, [{
	    key: "setTypeStorage",
	    value: function setTypeStorage(typeStorage) {
	      this.typeStorage = typeStorage;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      var sidebarClass = 'tasks-scrum-dod-settings-container-sidebar' + ' tasks-scrum-dod-settings-container-sidebar-settings';
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), sidebarClass, babelHelpers.toConsumableArray(this.typeStorage.getTypes().values()).map(function (type) {
	        return _this2.renderTab(type);
	      }));
	      return this.node;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.tabNodes.size === 0;
	    }
	  }, {
	    key: "renderTab",
	    value: function renderTab(type) {
	      var _this3 = this;

	      if (this.isEmptyType(type)) {
	        var addNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sidebar-tab-link\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-type-name\">\n\t\t\t\t\t\t+ ", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_CREATE_TYPE'));
	        main_core.Event.bind(addNode, 'click', this.createType.bind(this));
	        return addNode;
	      } else {
	        var tabClass = this.isActiveType(type) ? 'sidebar-tab sidebar-tab-active' : 'sidebar-tab';
	        var tabNode = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-type-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-type-edit\"></div>\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-type-remove\"></div>\n\t\t\t\t</div>\n\t\t\t"])), tabClass, main_core.Text.encode(type.getName()));
	        this.tabNodes.set(type.getId(), tabNode);
	        main_core.Event.bind(tabNode, 'click', function (event) {
	          var edit = event.target.classList.contains('tasks-scrum-dod-settings-type-edit');
	          var remove = event.target.classList.contains('tasks-scrum-dod-settings-type-remove');

	          if (!_this3.isActiveType(type)) {
	            _this3.switchType(type, tabNode);
	          }

	          if (edit) {
	            _this3.changeTypeName(type, tabNode);
	          }

	          if (remove) {
	            _this3.removeType(type, tabNode);
	          }
	        });
	        return tabNode;
	      }
	    }
	  }, {
	    key: "addType",
	    value: function addType(newType, tmpType) {
	      if (tmpType) {
	        main_core.Dom.remove(this.tabNodes.get(tmpType.getId()));
	        this.tabNodes["delete"](tmpType.getId());
	      }

	      var node = this.renderTab(newType);
	      main_core.Dom.insertBefore(node, this.node.lastElementChild);
	      this.switchType(newType, node);
	    }
	  }, {
	    key: "switchType",
	    value: function switchType(type, typeNode) {
	      var savePrevious = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      this.tabNodes.forEach(function (node) {
	        main_core.Dom.removeClass(node, 'sidebar-tab-active');
	      });
	      main_core.Dom.addClass(typeNode, 'sidebar-tab-active');

	      if (savePrevious && !this.isEmpty()) {
	        this.setPreviousType(this.getActiveType());
	      } else {
	        this.setPreviousType(null);
	      }

	      this.setActiveType(type);
	      this.emit('switchType', type);
	    }
	  }, {
	    key: "createType",
	    value: function createType() {
	      var _this4 = this;

	      var type = new ItemType();
	      type.setSort(this.typeStorage.getTypes().size);
	      this.tabNodes.forEach(function (node) {
	        main_core.Dom.removeClass(node, 'sidebar-tab-active');
	      });
	      var node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sidebar-tab sidebar-tab-active\">\n\t\t\t\t<input type=\"text\" class=\"tasks-scrum-dod-settings-type-name-input\">\n\t\t\t</div>\n\t\t"])));
	      var nameNode = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-dod-settings-type-name\"></div>"])));
	      main_core.Dom.insertBefore(node, this.node.lastElementChild);
	      var input = node.querySelector('input');
	      main_core.Event.bind(input, 'change', function (event) {
	        type.setName(event.target['value']);

	        _this4.emit('createType', type);

	        nameNode.textContent = main_core.Text.encode(type.getName());
	        main_core.Dom.replace(input, nameNode);
	      }, true);
	      main_core.Event.bind(input, 'blur', function (event) {
	        if (event.target['value'].trim() === '') {
	          main_core.Dom.remove(node);
	        }
	      }, true);
	      main_core.Event.bind(input, 'keydown', function (event) {
	        if (event.isComposing || event.keyCode === 13) {
	          input.blur();
	        }
	      });
	      input.focus();
	      this.tabNodes.set(type.getId(), node);
	    }
	  }, {
	    key: "changeTypeName",
	    value: function changeTypeName(type, typeNode) {
	      var _this5 = this;

	      var inputNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"text\" class=\"tasks-scrum-dod-settings-type-name-input\" value=\"", "\">\n\t\t"])), main_core.Text.encode(type.getName()));
	      var nameNode = typeNode.querySelector('.tasks-scrum-dod-settings-type-name');
	      main_core.Event.bind(inputNode, 'change', function (event) {
	        type.setName(event.target['value']);

	        _this5.emit('changeTypeName', type);

	        inputNode.blur();
	      }, true);
	      main_core.Event.bind(inputNode, 'blur', function () {
	        nameNode.textContent = main_core.Text.encode(type.getName());
	        main_core.Dom.replace(inputNode, nameNode);
	      }, true);
	      main_core.Event.bind(inputNode, 'keydown', function (event) {
	        if (event.isComposing || event.keyCode === 13) {
	          inputNode.blur();
	        }
	      });
	      main_core.Dom.replace(nameNode, inputNode);
	      inputNode.focus();
	      inputNode.setSelectionRange(type.getName().length, type.getName().length);
	    }
	  }, {
	    key: "removeType",
	    value: function removeType(type, typeNode) {
	      var _this6 = this;

	      top.BX.UI.Dialogs.MessageBox.confirm(main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE'), function (messageBox) {
	        _this6.tabNodes["delete"](type.getId());

	        if (_this6.isActiveType(type)) {
	          _this6.setActiveType(null);
	        }

	        _this6.setPreviousType(null);

	        main_core.Dom.remove(typeNode);

	        _this6.emit('removeType', type);

	        messageBox.close();
	      }, main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'));
	    }
	  }, {
	    key: "setActiveType",
	    value: function setActiveType(type) {
	      this.activeType = type;
	    }
	  }, {
	    key: "getActiveType",
	    value: function getActiveType() {
	      return this.activeType;
	    }
	  }, {
	    key: "setPreviousType",
	    value: function setPreviousType(type) {
	      this.previousType = type;
	    }
	  }, {
	    key: "getPreviousType",
	    value: function getPreviousType() {
	      return this.previousType;
	    }
	  }, {
	    key: "isActiveType",
	    value: function isActiveType(type) {
	      return this.activeType && this.activeType.getId() === type.getId();
	    }
	  }, {
	    key: "setEmptyType",
	    value: function setEmptyType(type) {
	      this.emptyType = type;
	    }
	  }, {
	    key: "isEmptyType",
	    value: function isEmptyType(type) {
	      return type.getId() === this.emptyType.getId();
	    }
	  }, {
	    key: "switchToType",
	    value: function switchToType(type) {
	      this.switchType(type, this.tabNodes.get(type.getId()), false);
	    }
	  }]);
	  return Tabs;
	}(main_core_events.EventEmitter);

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }

	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(controller, action) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      return new Promise(function (resolve, reject) {
	        top.BX.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "isNecessary",
	    value: function isNecessary(data) {
	      return this.sendRequest('doD', 'isNecessary', data);
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings(data) {
	      return this.sendRequest('doD', 'getSettings', data);
	    }
	  }, {
	    key: "getChecklist",
	    value: function getChecklist(data) {
	      return this.sendRequest('doD', 'getChecklist', data);
	    }
	  }, {
	    key: "saveSettings",
	    value: function saveSettings(data) {
	      return this.sendRequest('doD', 'saveSettings', data);
	    }
	  }, {
	    key: "getList",
	    value: function getList(data) {
	      return this.sendRequest('doD', 'getList', data);
	    }
	  }, {
	    key: "saveList",
	    value: function saveList(data) {
	      return this.sendRequest('doD', 'saveList', data);
	    }
	  }, {
	    key: "createType",
	    value: function createType(data) {
	      return this.sendRequest('type', 'createType', data);
	    }
	  }, {
	    key: "changeTypeName",
	    value: function changeTypeName(data) {
	      return this.sendRequest('type', 'changeTypeName', data);
	    }
	  }, {
	    key: "removeType",
	    value: function removeType(data) {
	      return this.sendRequest('type', 'removeType', data);
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
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSD_ERROR_POPUP_TITLE');
	          top.BX.UI.Dialogs.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1;
	var Settings = /*#__PURE__*/function () {
	  function Settings(params) {
	    babelHelpers.classCallCheck(this, Settings);
	    this.requestSender = params.requestSender;
	    this.groupId = parseInt(params.groupId, 10);
	    this.taskId = parseInt(params.taskId, 10);
	    this.typeStorage = new TypeStorage();
	    this.tabs = new Tabs();
	    this.tabs.subscribe('switchType', this.onSwitchType.bind(this));
	    this.tabs.subscribe('createType', this.onCreateType.bind(this));
	    this.tabs.subscribe('changeTypeName', this.onChangeTypeName.bind(this));
	    this.tabs.subscribe('removeType', this.onRemoveType.bind(this));
	  }

	  babelHelpers.createClass(Settings, [{
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.tabs.isEmpty();
	    }
	  }, {
	    key: "renderContent",
	    value: function renderContent() {
	      var _this = this;

	      return this.requestSender.getSettings({
	        groupId: this.groupId
	      }).then(function (response) {
	        var types = main_core.Type.isArray(response.data.types) ? response.data.types : [];
	        var itemTypes = new Map();
	        types.forEach(function (typeData) {
	          var itemType = new ItemType(typeData);
	          itemTypes.set(itemType.getId(), itemType);
	        });

	        _this.typeStorage.setTypes(itemTypes);

	        _this.tabs.setTypeStorage(_this.typeStorage);

	        _this.tabs.setActiveType(_this.typeStorage.getNextType());

	        _this.addEmptyCreationType();

	        return _this.render();
	      })["catch"](function (response) {
	        _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var currentType = this.typeStorage.getNextType();
	      this.node = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-dod-settings\">\n\t\t\t\t<div class=\"tasks-scrum-dod-settings-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-container-wrap\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-container-shell\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-container-sidebar-wrapper\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.tabs.render(), this.renderContainer(currentType));
	      return this.node;
	    }
	  }, {
	    key: "renderContainer",
	    value: function renderContainer(type) {
	      if (this.tabs.isEmpty()) {
	        return this.renderEmptyForm();
	      } else {
	        return this.renderEditingForm(type);
	      }
	    }
	  }, {
	    key: "renderEditingForm",
	    value: function renderEditingForm(type) {
	      return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content ui-form-content-dod-list\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.renderRequiredOption(type), this.renderParticipantsSelector());
	    }
	  }, {
	    key: "renderEmptyForm",
	    value: function renderEmptyForm() {
	      return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_CREATE_TYPE_PROMPT'));
	    }
	  }, {
	    key: "renderRequiredOption",
	    value: function renderRequiredOption(type) {
	      var _this2 = this;

	      var node = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element ui-form-content-required-option\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</label>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_OPTIONS_REQUIRED_LABEL'));
	      var checkbox = node.querySelector('.ui-form-content-required-option');
	      checkbox.checked = type.isDodRequired();
	      main_core.Event.bind(checkbox, 'click', function () {
	        _this2.updateActiveType();
	      });
	      return node;
	    }
	  }, {
	    key: "renderParticipantsSelector",
	    value: function renderParticipantsSelector() {
	      return ''; //todo tmp

	      return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-user-selector\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_USER_SELECTOR'));
	    }
	  }, {
	    key: "initParticipantsSelector",
	    value: function initParticipantsSelector(type) {
	      var participantsSelectorContainer = this.node.querySelector('.tasks-scrum-dod-settings-user-selector');

	      if (main_core.Type.isNil(participantsSelectorContainer)) {
	        return;
	      }

	      var selectorId = 'tasks-scrum-dod-settings-participants-selector-' + type.getId();
	      this.participantsSelector = new top.BX.UI.EntitySelector.TagSelector({
	        id: selectorId,
	        dialogOptions: {
	          id: selectorId,
	          context: 'TASKS',
	          preselectedItems: this.tabs.getActiveType().getParticipants(),
	          entities: [{
	            id: 'user',
	            options: {
	              inviteEmployeeLink: false
	            }
	          }, {
	            id: 'project-roles',
	            options: {
	              projectId: this.groupId
	            },
	            dynamicLoad: true
	          }]
	        }
	      });
	      this.participantsSelector.renderTo(participantsSelectorContainer);
	    }
	  }, {
	    key: "buildEditingForm",
	    value: function buildEditingForm(type) {
	      var _this3 = this;

	      var container = this.cleanTypeForm();
	      main_core.Dom.append(this.renderEditingForm(type), container);
	      this.initParticipantsSelector(type);
	      var listContainer = this.node.querySelector('.ui-form-content-dod-list');
	      var loader = this.showLoader(listContainer);
	      this.requestSender.getChecklist({
	        groupId: this.groupId,
	        typeId: type.getId()
	      }).then(function (response) {
	        loader.hide();
	        top.BX.Runtime.html(listContainer, response.data.html);
	      })["catch"](function (response) {
	        loader.hide();

	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "buildEmptyForm",
	    value: function buildEmptyForm() {
	      var container = this.cleanTypeForm();
	      main_core.Dom.append(this.renderEmptyForm(), container);
	    }
	  }, {
	    key: "onSwitchType",
	    value: function onSwitchType(baseEvent) {
	      var _this4 = this;

	      var type = baseEvent.getData();
	      var previousType = this.tabs.getPreviousType();

	      if (previousType) {
	        this.saveSettings(previousType).then(function (response) {
	          var updatedType = response.data.type;
	          previousType.setDodRequired(updatedType.dodRequired);
	          previousType.setParticipants(updatedType.participants);

	          _this4.buildEditingForm(type);
	        });
	      } else {
	        this.buildEditingForm(type);
	      }
	    }
	  }, {
	    key: "onCreateType",
	    value: function onCreateType(baseEvent) {
	      var _this5 = this;

	      var container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');
	      var loader = this.showLoader(container);
	      var tmpType = baseEvent.getData();
	      this.requestSender.createType({
	        groupId: this.groupId,
	        name: tmpType.getName(),
	        sort: tmpType.getSort()
	      }).then(function (response) {
	        loader.hide();
	        var createdType = new ItemType(response.data);

	        _this5.typeStorage.addType(createdType);

	        _this5.tabs.addType(createdType, tmpType);
	      })["catch"](function (response) {
	        loader.hide();

	        _this5.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onChangeTypeName",
	    value: function onChangeTypeName(baseEvent) {
	      var _this6 = this;

	      var type = baseEvent.getData();
	      this.requestSender.changeTypeName({
	        groupId: this.groupId,
	        id: type.getId(),
	        name: type.getName()
	      })["catch"](function (response) {
	        _this6.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onRemoveType",
	    value: function onRemoveType(baseEvent) {
	      var _this7 = this;

	      var type = baseEvent.getData();
	      this.requestSender.removeType({
	        groupId: this.groupId,
	        id: type.getId()
	      }).then(function () {
	        _this7.typeStorage.removeType(type);

	        if (_this7.tabs.isEmpty()) {
	          _this7.buildEmptyForm();
	        } else {
	          var nextType = babelHelpers.toConsumableArray(_this7.typeStorage.getTypes().values()).find(function (type) {
	            return !_this7.tabs.isEmptyType(type);
	          });

	          _this7.tabs.switchToType(nextType);
	        }
	      })["catch"](function (response) {
	        _this7.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "saveSettings",
	    value: function saveSettings(inputType) {
	      var _this8 = this;

	      if (this.tabs.isEmpty()) {
	        return Promise.resolve();
	      }

	      var type = inputType ? inputType : this.tabs.getActiveType();

	      if (!(type instanceof ItemType)) {
	        return Promise.resolve();
	      }

	      return this.requestSender.saveSettings({
	        groupId: this.groupId,
	        typeId: type.getId(),
	        requiredOption: this.getRequiredOptionValue(),
	        items: this.getChecklistItems(),
	        participants: this.getSelectedParticipants()
	      })["catch"](function (response) {
	        _this8.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getChecklistItems",
	    value: function getChecklistItems() {
	      /* eslint-disable */
	      if (typeof top.BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }

	      var treeStructure = top.BX.Tasks.CheckListInstance.getTreeStructure();
	      return treeStructure.getRequestData();
	      /* eslint-enable */
	    }
	  }, {
	    key: "getSelectedParticipants",
	    value: function getSelectedParticipants() {
	      if (main_core.Type.isNil(this.participantsSelector)) {
	        return [];
	      }

	      var selectedParticipants = [];
	      this.participantsSelector.getTags().forEach(function (tag) {
	        selectedParticipants.push({
	          id: tag.getId(),
	          entityId: tag.getEntityId()
	        });
	      });
	      return selectedParticipants;
	    }
	  }, {
	    key: "getRequiredOptionValue",
	    value: function getRequiredOptionValue() {
	      var requiredOption = this.node.querySelector('.ui-form-content-required-option');
	      return requiredOption.checked === true ? 'Y' : 'N';
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(container) {
	      var listPosition = main_core.Dom.getPosition(container);
	      var loader = new main_loader.Loader({
	        target: container,
	        size: 60,
	        mode: 'inline',
	        color: 'rgba(82, 92, 105, 0.9)',
	        offset: {
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      loader.show();
	      return loader;
	    }
	  }, {
	    key: "getActiveType",
	    value: function getActiveType() {
	      return this.tabs.getActiveType();
	    }
	  }, {
	    key: "updateActiveType",
	    value: function updateActiveType() {
	      var type = this.tabs.getActiveType();
	      type.setDodRequired(this.getRequiredOptionValue());
	    }
	  }, {
	    key: "cleanTypeForm",
	    value: function cleanTypeForm() {
	      var container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');
	      main_core.Dom.clean(container);
	      return container;
	    }
	  }, {
	    key: "addEmptyCreationType",
	    value: function addEmptyCreationType() {
	      var itemType = new ItemType();
	      this.tabs.setEmptyType(itemType);
	      this.typeStorage.addType(itemType);
	    }
	  }]);
	  return Settings;
	}();

	var _templateObject$2, _templateObject2$2;
	var List = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(List, _EventEmitter);

	  function List(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, List);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(List).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Dod.List');

	    _this.requestSender = params.requestSender;
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.taskId = parseInt(params.taskId, 10);
	    _this.skipNotifications = main_core.Type.isBoolean(params.skipNotifications) ? params.skipNotifications : false;
	    _this.typeStorage = new TypeStorage();
	    _this.tabs = new Tabs();
	    _this.empty = true;
	    _this.activeTypeData = null;
	    _this.node = null;
	    return _this;
	  }

	  babelHelpers.createClass(List, [{
	    key: "renderContent",
	    value: function renderContent() {
	      var _this2 = this;

	      return this.requestSender.getSettings({
	        groupId: this.groupId,
	        taskId: this.taskId,
	        saveRequest: this.isSkipNotifications() ? 'Y' : 'N'
	      }).then(function (response) {
	        var types = main_core.Type.isArray(response.data.types) ? response.data.types : [];
	        var activeTypeId = main_core.Type.isInteger(response.data.activeTypeId) ? parseInt(response.data.activeTypeId, 10) : 0;
	        _this2.empty = types.length === 0;
	        var itemTypes = new Map();
	        types.forEach(function (typeData) {
	          var itemType = new ItemType(typeData);
	          itemTypes.set(itemType.getId(), itemType);
	        });

	        _this2.typeStorage.setTypes(itemTypes);

	        _this2.tabs.setTypeStorage(_this2.typeStorage);

	        var activeType = itemTypes.get(activeTypeId);

	        if (main_core.Type.isUndefined(activeType)) {
	          _this2.tabs.setActiveType(_this2.typeStorage.getNextType());
	        } else {
	          _this2.tabs.setActiveType(activeType);
	        }

	        if (_this2.isEmpty()) {
	          if (!_this2.isSkipNotifications()) {
	            _this2.emit('resolve');
	          }

	          return _this2.renderEmpty();
	        }

	        return _this2.render();
	      })["catch"](function (response) {
	        _this2.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderList",
	    value: function renderList() {
	      var _this3 = this;

	      var listNode = this.node.querySelector('.tasks-scrum-dod-checklist');
	      main_core.Dom.clean(listNode);
	      var loader = this.showLoader(listNode);
	      this.requestSender.getList({
	        groupId: this.groupId,
	        taskId: this.taskId,
	        typeId: this.getActiveType().getId()
	      }).then(function (response) {
	        loader.hide();
	        top.BX.Runtime.html(listNode, response.data.html);
	      })["catch"](function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderEmpty",
	    value: function renderEmpty() {
	      var _this4 = this;

	      var node = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_EMPTY'));
	      main_core.Event.bind(node.querySelector('span'), 'click', function () {
	        return _this4.emit('showSettings');
	      });
	      return node;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this5 = this;

	      var activeType = this.getActiveType();

	      var renderOption = function renderOption(typeData) {
	        var selected = activeType.getId() === typeData.id ? 'selected' : '';
	        return "<option value=\"".concat(parseInt(typeData.id, 10), "\" ").concat(selected, ">").concat(main_core.Text.encode(typeData.name), "</option>");
	      };

	      this.node = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element tasks-scrum-dod-types\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content tasks-scrum-dod-checklist\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_TYPES'), babelHelpers.toConsumableArray(this.typeStorage.getTypes().values()).map(function (typeData) {
	        return renderOption(typeData);
	      }).join(''));
	      var typeSelector = this.node.querySelector('.tasks-scrum-dod-types');
	      main_core.Event.bind(typeSelector, 'change', function (event) {
	        var typeId = parseInt(event.target.value, 10);

	        _this5.tabs.setActiveType(_this5.typeStorage.getType(typeId));

	        _this5.renderList();
	      });
	      return this.node;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _this6 = this;

	      var activeType = this.getActiveType();
	      this.requestSender.saveList({
	        typeId: activeType.getId(),
	        taskId: this.taskId,
	        groupId: this.groupId,
	        items: this.getListItems()
	      }).then(function () {
	        if (_this6.isSkipNotifications()) {
	          _this6.solve();
	        } else {
	          if (_this6.isListRequired(_this6.getActiveType())) {
	            if (_this6.isAllToggled()) {
	              _this6.emit('resolve');
	            } else {
	              _this6.emit('reject');

	              _this6.showInfoPopup();
	            }
	          } else {
	            if (_this6.isAllToggled()) {
	              _this6.emit('resolve');
	            } else {
	              _this6.showConfirmPopup();
	            }
	          }
	        }
	      })["catch"](function (response) {
	        _this6.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "isSkipNotifications",
	    value: function isSkipNotifications() {
	      return this.skipNotifications;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.empty;
	    }
	  }, {
	    key: "getActiveType",
	    value: function getActiveType() {
	      return this.tabs.getActiveType();
	    }
	  }, {
	    key: "isListRequired",
	    value: function isListRequired(type) {
	      return type.isDodRequired();
	    }
	  }, {
	    key: "solve",
	    value: function solve() {
	      if (this.isListRequired(this.getActiveType())) {
	        if (this.isAllToggled()) {
	          this.emit('resolve');
	        } else {
	          this.emit('reject');
	        }
	      } else {
	        this.emit('resolve');
	      }
	    }
	  }, {
	    key: "getListItems",
	    value: function getListItems() {
	      /* eslint-disable */
	      if (typeof top.BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }

	      var treeStructure = top.BX.Tasks.CheckListInstance.getTreeStructure();
	      return treeStructure.getRequestData();
	      /* eslint-enable */
	    }
	  }, {
	    key: "isAllToggled",
	    value: function isAllToggled() {
	      /* eslint-disable */
	      if (typeof top.BX.Tasks.CheckListInstance === 'undefined') {
	        return false;
	      }

	      var isAllToggled = true;
	      var treeStructure = top.BX.Tasks.CheckListInstance.getTreeStructure();
	      treeStructure.getDescendants().forEach(function (checkList) {
	        if (checkList.countTotalCount() > 0 && !checkList.checkIsComplete()) {
	          isAllToggled = false;
	        }
	      });
	      return isAllToggled;
	      /* eslint-enable */
	    }
	  }, {
	    key: "showInfoPopup",
	    value: function showInfoPopup() {
	      ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'));
	    }
	  }, {
	    key: "showConfirmPopup",
	    value: function showConfirmPopup() {
	      var _this7 = this;

	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_TEXT_COMPLETE'),
	        modal: true,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.SUCCESS,
	          events: {
	            click: function click() {
	              _this7.emit('resolve');

	              messageBox.close();
	            }
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.LINK,
	          events: {
	            click: function click() {
	              _this7.emit('reject');

	              messageBox.close();
	            }
	          }
	        })]
	      });
	      messageBox.show();
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(container) {
	      var listPosition = main_core.Dom.getPosition(container);
	      var loader = new main_loader.Loader({
	        target: container,
	        size: 60,
	        mode: 'inline',
	        color: 'rgba(82, 92, 105, 0.9)',
	        offset: {
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      loader.show();
	      return loader;
	    }
	  }]);
	  return List;
	}(main_core_events.EventEmitter);

	var Dod = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Dod, _EventEmitter);

	  function Dod(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Dod);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Dod).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Scrum.Dod');

	    _this.view = params.view;
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.taskId = parseInt(params.taskId, 10);
	    /* eslint-disable */

	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.requestSender = new RequestSender();
	    _this.settings = new Settings({
	      requestSender: _this.requestSender,
	      groupId: _this.groupId,
	      taskId: _this.taskId
	    });
	    _this.list = new List({
	      requestSender: _this.requestSender,
	      groupId: _this.groupId,
	      taskId: _this.taskId,
	      skipNotifications: params.skipNotifications
	    });

	    _this.list.subscribe('resolve', function () {
	      return _this.emit('resolve');
	    });

	    _this.list.subscribe('reject', function () {
	      return _this.emit('reject');
	    });

	    _this.list.subscribe('showSettings', function () {
	      _this.sidePanelManager.close(false, function () {
	        return _this.showSettings();
	      });
	    });

	    return _this;
	  }

	  babelHelpers.createClass(Dod, [{
	    key: "isNecessary",
	    value: function isNecessary() {
	      var _this2 = this;

	      return this.requestSender.isNecessary({
	        groupId: this.groupId,
	        taskId: this.taskId
	      }).then(function (response) {
	        return response.data;
	      })["catch"](function (response) {
	        _this2.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      switch (this.view) {
	        case 'settings':
	          this.showSettings();
	          break;

	        case 'list':
	          this.showList();
	          break;
	      }
	    }
	  }, {
	    key: "showSettings",
	    value: function showSettings() {
	      var _this3 = this;

	      this.sidePanelManager.open('tasks-scrum-dod-settings-side-panel', {
	        cacheable: false,
	        width: 1000,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.dod', 'ui.entity-selector'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
	            content: _this3.createSettingsContent.bind(_this3),
	            design: {
	              section: false
	            },
	            buttons: []
	          });
	        },
	        events: {
	          onLoad: this.onLoadSettings.bind(this),
	          onClose: this.onCloseSettings.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "showList",
	    value: function showList() {
	      var _this4 = this;

	      this.sidePanelManager.open('tasks-scrum-dod-list-side-panel', {
	        cacheable: false,
	        width: 800,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.dod'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
	            content: _this4.createListContent.bind(_this4),
	            design: {
	              section: false
	            },
	            buttons: function buttons(_ref) {
	              var cancelButton = _ref.cancelButton,
	                  SaveButton = _ref.SaveButton;
	              return [new SaveButton({
	                text: _this4.getListButtonText(),
	                onclick: _this4.onSaveList.bind(_this4)
	              }), cancelButton];
	            }
	          });
	        },
	        events: {
	          onLoad: this.onLoadList.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "createSettingsContent",
	    value: function createSettingsContent() {
	      var _this5 = this;

	      return new Promise(function (resolve, reject) {
	        _this5.settings.renderContent().then(function (content) {
	          resolve(content);
	        });
	      });
	    }
	  }, {
	    key: "createListContent",
	    value: function createListContent() {
	      var _this6 = this;

	      return new Promise(function (resolve, reject) {
	        _this6.list.renderContent().then(function (content) {
	          resolve(content);
	        });
	      });
	    }
	  }, {
	    key: "onLoadSettings",
	    value: function onLoadSettings() {
	      if (!this.settings.isEmpty()) {
	        this.settings.buildEditingForm(this.settings.getActiveType());
	      }
	    }
	  }, {
	    key: "onLoadList",
	    value: function onLoadList() {
	      if (this.list.isEmpty()) {
	        return;
	      }

	      this.list.renderList();
	    }
	  }, {
	    key: "onCloseSettings",
	    value: function onCloseSettings() {
	      this.settings.saveSettings().then(function () {})["catch"](function () {});
	    }
	  }, {
	    key: "onSaveList",
	    value: function onSaveList() {
	      if (this.list.isEmpty()) {
	        return;
	      }

	      this.list.save();
	      this.sidePanelManager.close(false);
	    }
	  }, {
	    key: "getListButtonText",
	    value: function getListButtonText() {
	      if (this.list.isSkipNotifications()) {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT');
	      } else {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT');
	      }
	    }
	  }]);
	  return Dod;
	}(main_core_events.EventEmitter);

	exports.Dod = Dod;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI.SidePanel,BX.UI,BX,BX,BX.Event,BX,BX.UI.Dialogs,BX.UI));
//# sourceMappingURL=dod.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_entitySelector,ui_sidepanel_menu,ui_notification,main_core,main_core_events,main_loader,ui_dialogs_messagebox,ui_buttons,ui_sidepanel_layout) {
	'use strict';

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }
	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(controller, action) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
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
	        console.error(response);
	        return;
	      }
	      if (response.errors.length) {
	        var firstError = response.errors.shift();
	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSD_ERROR_POPUP_TITLE');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var ItemType = /*#__PURE__*/function () {
	  function ItemType() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ItemType);
	    this.setId(params.id);
	    this.setName(params.name);
	    this.setSort(params.sort);
	    this.setDodRequired(params.dodRequired);
	    this.setParticipants(params.participants);
	    this.setActive(params.active === 'Y');
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
	    key: "setActive",
	    value: function setActive(value) {
	      this.active = main_core.Type.isBoolean(value) ? value : false;
	    }
	  }, {
	    key: "isActive",
	    value: function isActive() {
	      return this.active;
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
	    this.types = new Map();
	  }
	  babelHelpers.createClass(TypeStorage, [{
	    key: "setTypes",
	    value: function setTypes(types) {
	      this.types = types;
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
	  }, {
	    key: "setActiveType",
	    value: function setActiveType(inputType) {
	      inputType = main_core.Type.isNil(inputType) ? this.types.values().next().value : inputType;
	      this.types.forEach(function (type) {
	        return type.setActive(inputType.getId() === type.getId());
	      });
	    }
	  }, {
	    key: "getActiveType",
	    value: function getActiveType() {
	      var foundItem = babelHelpers.toConsumableArray(this.types.values()).find(function (type) {
	        return type.isActive();
	      });
	      if (main_core.Type.isNil(foundItem)) {
	        return this.types.values().next().value;
	      }
	      return foundItem;
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.types.size === 0;
	    }
	  }]);
	  return TypeStorage;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	var Settings = /*#__PURE__*/function () {
	  function Settings(params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Settings);
	    this.requestSender = params.requestSender;
	    this.groupId = parseInt(params.groupId, 10);
	    this.taskId = parseInt(params.taskId, 10);
	    this.sidePanelManager = BX.SidePanel.Instance;
	    this.typeStorage = new TypeStorage();
	    this.layoutMenu = null;
	    this.nameInput = null;
	    this.changed = false;
	    main_core_events.EventEmitter.subscribe('BX.Tasks.CheckListItem:CheckListChanged', function () {
	      _this.setChanged();
	    });
	  }
	  babelHelpers.createClass(Settings, [{
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      this.sidePanelManager.open('tasks-scrum-dod-settings-side-panel', {
	        cacheable: false,
	        width: 1000,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createLayout({
	            extensions: ['tasks.scrum.dod', 'ui.entity-selector', 'tasks'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
	            content: _this2.renderContent.bind(_this2),
	            design: {
	              section: false
	            },
	            menu: {},
	            toolbar: function toolbar(_ref) {
	              var Button = _ref.Button;
	              return [new Button({
	                color: Button.Color.LIGHT_BORDER,
	                text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_BTN_CREATE_TYPE'),
	                onclick: function onclick() {
	                  _this2.showTypeForm();
	                }
	              })];
	            },
	            buttons: []
	          }).then(function (layout) {
	            _this2.layoutMenu = layout.getMenu();
	            _this2.layoutMenu.subscribe('click', _this2.onMenuItemClick.bind(_this2));
	            return layout.render();
	          });
	        },
	        events: {
	          onLoad: this.onLoadSettings.bind(this),
	          onClose: this.onCloseSettings.bind(this),
	          onCloseComplete: this.onCloseSettingsComplete.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "onLoadSettings",
	    value: function onLoadSettings() {
	      this.layoutMenu.setItems(this.getMenuItems());
	      if (!this.isEmpty()) {
	        this.buildEditingForm(this.typeStorage.getActiveType());
	      }
	    }
	  }, {
	    key: "onCloseSettings",
	    value: function onCloseSettings() {
	      if (this.isChanged()) {
	        this.saveSettings().then(function () {
	          ui_notification.UI.Notification.Center.notify({
	            autoHideDelay: 1000,
	            content: main_core.Loc.getMessage('TASKS_SCRUM_DOD_SAVE_SETTINGS_NOTIFY')
	          });
	        })["catch"](function () {});
	      }
	    }
	  }, {
	    key: "onCloseSettingsComplete",
	    value: function onCloseSettingsComplete() {
	      var currentSlider = this.sidePanelManager.getTopSlider();
	      if (currentSlider) {
	        if (currentSlider.getUrl() === 'tasks-scrum-dod-list-side-panel' && this.isChanged()) {
	          currentSlider.reload();
	        }
	      }
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return this.typeStorage.isEmpty();
	    }
	  }, {
	    key: "isChanged",
	    value: function isChanged() {
	      return this.changed;
	    }
	  }, {
	    key: "setChanged",
	    value: function setChanged() {
	      this.changed = true;
	    }
	  }, {
	    key: "renderContent",
	    value: function renderContent() {
	      var _this3 = this;
	      return this.requestSender.getSettings({
	        groupId: this.groupId
	      }).then(function (response) {
	        var types = main_core.Type.isArray(response.data.types) ? response.data.types : [];
	        var itemTypes = new Map();
	        types.forEach(function (typeData) {
	          var itemType = new ItemType(typeData);
	          itemTypes.set(itemType.getId(), itemType);
	        });
	        _this3.typeStorage.setTypes(itemTypes);
	        _this3.typeStorage.setActiveType();
	        return _this3.render(_this3.typeStorage.getActiveType());
	      })["catch"](function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "render",
	    value: function render(type) {
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-dod-settings\">\n\t\t\t\t<div class=\"tasks-scrum-dod-settings-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-container-wrap\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-container-sidebar-wrapper\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.renderContainer(type));
	      return this.node;
	    }
	  }, {
	    key: "renderContainer",
	    value: function renderContainer(type) {
	      if (this.typeStorage.isEmpty()) {
	        return this.renderEmptyForm();
	      } else {
	        return this.renderEditingForm(type);
	      }
	    }
	  }, {
	    key: "renderEditingForm",
	    value: function renderEditingForm(type) {
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content ui-form-content-dod-list\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.renderRequiredOption(type), this.renderParticipantsSelector());
	    }
	  }, {
	    key: "renderEmptyForm",
	    value: function renderEmptyForm() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_CREATE_TYPE_PROMPT'));
	    }
	  }, {
	    key: "renderRequiredOption",
	    value: function renderRequiredOption(type) {
	      var _this4 = this;
	      var node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element ui-form-content-required-option\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</label>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_OPTIONS_REQUIRED_LABEL'));
	      var checkbox = node.querySelector('.ui-form-content-required-option');
	      checkbox.checked = type.isDodRequired();
	      main_core.Event.bind(checkbox, 'click', function () {
	        _this4.setChanged();
	        _this4.updateActiveType();
	      });
	      return node;
	    }
	  }, {
	    key: "renderParticipantsSelector",
	    value: function renderParticipantsSelector() {
	      return ''; //todo tmp

	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t<div class=\"tasks-scrum-dod-settings-user-selector\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_USER_SELECTOR'));
	    }
	  }, {
	    key: "renderTypeForm",
	    value: function renderTypeForm(type) {
	      var _this5 = this;
	      var name = type ? type.getName() : '';
	      this.typeFormNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-dod-settings-type-form\">\n\t\t\t\t<div class=\"ui-alert ui-alert-danger --hidden\">\n\t\t\t\t\t<span class=\"ui-alert-message\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t<input\n\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_POPUP_INPUT_PLACEHOLDER'), main_core.Text.encode(name));
	      this.nameInput = this.typeFormNode.querySelector('input');
	      main_core.Event.bind(this.nameInput, 'keydown', function (event) {
	        if (event.key === 'Enter') {
	          _this5.onOkTypeForm(type);
	        }
	        _this5.hideTypeFormError();
	      });
	      return this.typeFormNode;
	    }
	  }, {
	    key: "initParticipantsSelector",
	    value: function initParticipantsSelector(type) {
	      var participantsSelectorContainer = this.node.querySelector('.tasks-scrum-dod-settings-user-selector');
	      if (main_core.Type.isNil(participantsSelectorContainer)) {
	        return;
	      }
	      var selectorId = 'tasks-scrum-dod-settings-participants-selector-' + type.getId();

	      // todo change to scrum-user provider
	      this.participantsSelector = new ui_entitySelector.TagSelector({
	        id: selectorId,
	        dialogOptions: {
	          id: selectorId,
	          context: 'TASKS',
	          preselectedItems: this.typeStorage.getActiveType().getParticipants(),
	          entities: [{
	            id: 'user',
	            options: {
	              inviteEmployeeLink: false,
	              analyticsSource: 'task'
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
	      var _this6 = this;
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
	        main_core.Runtime.html(listContainer, response.data.html);
	      })["catch"](function (response) {
	        loader.hide();
	        _this6.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "buildEmptyForm",
	    value: function buildEmptyForm() {
	      var container = this.cleanTypeForm();
	      main_core.Dom.append(this.renderEmptyForm(), container);
	    }
	  }, {
	    key: "showTypeForm",
	    value: function showTypeForm(type) {
	      var _this7 = this;
	      this.typeForm = new ui_dialogs_messagebox.MessageBox({
	        popupOptions: this.getDefaultPopupOptions(),
	        title: main_core.Type.isUndefined(type) ? main_core.Loc.getMessage('TASKS_SCRUM_DOD_POPUP_TITLE_CREATE') : main_core.Loc.getMessage('TASKS_SCRUM_DOD_POPUP_TITLE_EDIT'),
	        message: this.renderTypeForm(type),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Type.isUndefined(type) ? main_core.Loc.getMessage('TASKS_SCRUM_DOD_BTN_CREATE_TYPE') : main_core.Loc.getMessage('TASKS_SCRUM_DOD_BTN_SAVE'),
	        onOk: function onOk() {
	          return _this7.onOkTypeForm(type);
	        }
	      });
	      var popup = this.typeForm.getPopupWindow();
	      popup.subscribe('onAfterShow', function () {
	        var length = _this7.nameInput.value.length;
	        _this7.nameInput.focus();
	        _this7.nameInput.setSelectionRange(length, length);
	      });
	      this.typeForm.show();
	    }
	  }, {
	    key: "onOkTypeForm",
	    value: function onOkTypeForm(type) {
	      var _this8 = this;
	      if (!this.nameInput.value.trim()) {
	        this.showTypeFormError(main_core.Loc.getMessage('TASKS_SCRUM_DOD_POPUP_EMPTY_NAME'));
	        this.typeForm.getOkButton().setDisabled(false);
	        return;
	      }
	      this.typeForm.close();
	      if (main_core.Type.isUndefined(type)) {
	        var skipPrevious = this.typeStorage.isEmpty();
	        this.createType(this.nameInput.value).then(function (createdType) {
	          if (createdType) {
	            _this8.addMenuItem(createdType);
	            _this8.switchType(createdType, skipPrevious);
	          }
	        });
	      } else {
	        type.setName(this.nameInput.value);
	        this.changeType(type).then(function (changedType) {
	          if (changedType) {
	            _this8.changeMenuItem(changedType);
	            _this8.switchType(changedType);
	          }
	        });
	      }
	    }
	  }, {
	    key: "showTypeFormError",
	    value: function showTypeFormError(message) {
	      var alertNode = this.typeFormNode.querySelector('.ui-alert');
	      alertNode.querySelector('.ui-alert-message').textContent = message;
	      main_core.Dom.removeClass(alertNode, '--hidden');
	    }
	  }, {
	    key: "hideTypeFormError",
	    value: function hideTypeFormError() {
	      var alertNode = this.typeFormNode.querySelector('.ui-alert');
	      if (!main_core.Dom.hasClass(alertNode, '--hidden')) {
	        main_core.Dom.addClass(this.typeFormNode.querySelector('.ui-alert'), '--hidden');
	      }
	    }
	  }, {
	    key: "createType",
	    value: function createType(name) {
	      var _this9 = this;
	      var container = this.node.querySelector('.tasks-scrum-dod-settings-container-sidebar-wrapper');
	      var loader = this.showLoader(container);
	      return this.requestSender.createType({
	        groupId: this.groupId,
	        name: name,
	        sort: this.typeStorage.getTypes().size + 1
	      }).then(function (response) {
	        _this9.setChanged();
	        loader.hide();
	        var createdType = new ItemType(response.data);
	        _this9.typeStorage.addType(createdType);
	        return createdType;
	      })["catch"](function (response) {
	        loader.hide();
	        _this9.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "switchType",
	    value: function switchType(type) {
	      var _this10 = this;
	      var skipPrevious = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var menuItem = this.getMenuItem(type);
	      var previousType = null;
	      if (!skipPrevious) {
	        previousType = this.typeStorage.getActiveType();
	        if (menuItem.getId() === previousType.getId()) {
	          return;
	        }
	      }
	      this.typeStorage.setActiveType(type);
	      this.setActiveMenuItem(type);
	      if (previousType) {
	        this.saveSettings(previousType).then(function (response) {
	          var updatedType = response.data.type;
	          previousType.setDodRequired(updatedType.dodRequired);
	          previousType.setParticipants(updatedType.participants);
	          _this10.buildEditingForm(type);
	        });
	      } else {
	        this.buildEditingForm(type);
	      }
	    }
	  }, {
	    key: "changeType",
	    value: function changeType(type) {
	      var _this11 = this;
	      return this.requestSender.changeTypeName({
	        groupId: this.groupId,
	        id: type.getId(),
	        name: type.getName()
	      }).then(function () {
	        _this11.setChanged();
	        return type;
	      })["catch"](function (response) {
	        _this11.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "removeType",
	    value: function removeType(type) {
	      var _this12 = this;
	      return this.requestSender.removeType({
	        groupId: this.groupId,
	        id: type.getId()
	      }).then(function () {
	        _this12.setChanged();
	        _this12.typeStorage.removeType(type);
	        if (_this12.typeStorage.isEmpty()) {
	          _this12.buildEmptyForm();
	          return null;
	        } else {
	          return _this12.typeStorage.getActiveType();
	        }
	      })["catch"](function (response) {
	        _this12.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "saveSettings",
	    value: function saveSettings(inputType) {
	      var _this13 = this;
	      if (this.typeStorage.isEmpty()) {
	        return Promise.resolve();
	      }
	      var type = inputType ? inputType : this.typeStorage.getActiveType();
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
	        _this13.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "getChecklistItems",
	    value: function getChecklistItems() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }
	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
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
	    key: "updateActiveType",
	    value: function updateActiveType() {
	      var type = this.typeStorage.getActiveType();
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
	    key: "getMenuItems",
	    value: function getMenuItems() {
	      var _this14 = this;
	      if (this.typeStorage.isEmpty()) {
	        return [];
	      }
	      var items = [];
	      this.typeStorage.getTypes().forEach(function (type) {
	        items.push(_this14.getMenuItemOptions(type, items.length === 0));
	      });
	      return items;
	    }
	  }, {
	    key: "addMenuItem",
	    value: function addMenuItem(type) {
	      return this.layoutMenu.add(this.getMenuItemOptions(type));
	    }
	  }, {
	    key: "changeMenuItem",
	    value: function changeMenuItem(type) {
	      return this.layoutMenu.change(type.getId(), {
	        label: type.getName()
	      });
	    }
	  }, {
	    key: "removeMenuItem",
	    value: function removeMenuItem(type) {
	      this.layoutMenu.remove(type.getId());
	    }
	  }, {
	    key: "getMenuItem",
	    value: function getMenuItem(type) {
	      return this.layoutMenu.get(type.getId());
	    }
	  }, {
	    key: "setActiveMenuItem",
	    value: function setActiveMenuItem(type) {
	      this.getMenuItem(type).setActive();
	    }
	  }, {
	    key: "getMenuItemOptions",
	    value: function getMenuItemOptions(type) {
	      var _this15 = this;
	      var active = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      return {
	        id: type.getId(),
	        label: type.getName(),
	        active: active,
	        actions: [{
	          label: main_core.Loc.getMessage('TASKS_SCRUM_DOD_BTN_EDIT_TYPE'),
	          onclick: function onclick() {
	            _this15.showTypeForm(type);
	          }
	        }, {
	          label: main_core.Loc.getMessage('TASKS_SCRUM_DOD_BTN_REMOVE_TYPE'),
	          onclick: function onclick(item) {
	            new ui_dialogs_messagebox.MessageBox({
	              title: main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE_TITLE'),
	              message: main_core.Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE_NEW').replace('#name#', main_core.Text.encode(type.getName())),
	              popupOptions: _this15.getDefaultPopupOptions(),
	              okCaption: main_core.Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
	              buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	              minHeight: 100,
	              onOk: function onOk(messageBox) {
	                _this15.removeType(type).then(function (nextType) {
	                  _this15.removeMenuItem(type);
	                  if (!main_core.Type.isNull(nextType)) {
	                    _this15.switchType(nextType, true);
	                  }
	                  messageBox.close();
	                });
	              }
	            }).show();
	          }
	        }]
	      };
	    }
	  }, {
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(baseEvent) {
	      var _baseEvent$getData = baseEvent.getData(),
	        menuItem = _baseEvent$getData.item;
	      this.switchType(this.typeStorage.getType(menuItem.getId()));
	    }
	  }, {
	    key: "getDefaultPopupOptions",
	    value: function getDefaultPopupOptions() {
	      var popupOptions = {};
	      var currentSlider = this.sidePanelManager.getTopSlider();
	      if (currentSlider) {
	        popupOptions.targetContainer = currentSlider.getContainer();
	      }
	      return popupOptions;
	    }
	  }]);
	  return Settings;
	}();

	var _templateObject$1, _templateObject2$1;
	var List = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(List, _EventEmitter);
	  function List(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, List);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(List).call(this, params));
	    _this.setEventNamespace('BX.Tasks.Scrum.Dod.List');
	    _this.sidePanelManager = BX.SidePanel.Instance;
	    _this.requestSender = params.requestSender;
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.taskId = parseInt(params.taskId, 10);
	    _this.skipNotifications = main_core.Type.isBoolean(params.skipNotifications) ? params.skipNotifications : false;
	    _this.typeStorage = new TypeStorage();
	    _this.empty = true;
	    _this.node = null;
	    return _this;
	  }
	  babelHelpers.createClass(List, [{
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      this.sidePanelManager.open('tasks-scrum-dod-list-side-panel', {
	        cacheable: false,
	        width: 800,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.dod', 'tasks'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
	            content: _this2.renderContent.bind(_this2),
	            design: {
	              section: false
	            },
	            toolbar: function toolbar(_ref) {
	              var Button = _ref.Button;
	              return [new Button({
	                color: Button.Color.LIGHT_BORDER,
	                text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_TOOLBAR_SETTINGS'),
	                onclick: function onclick() {
	                  return _this2.emit('showSettings', false);
	                }
	              })];
	            },
	            buttons: function buttons(_ref2) {
	              var cancelButton = _ref2.cancelButton,
	                SaveButton = _ref2.SaveButton;
	              return [new SaveButton({
	                text: _this2.getListButtonText(),
	                onclick: _this2.onSaveList.bind(_this2)
	              }), new ui_buttons.CancelButton({
	                onclick: function onclick() {
	                  _this2.emit('reject');
	                  _this2.sidePanelManager.close(false);
	                }
	              })];
	            }
	          });
	        },
	        events: {
	          onLoad: this.onLoadList.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "onLoadList",
	    value: function onLoadList() {
	      if (this.isEmpty()) {
	        return;
	      }
	      this.renderList();
	    }
	  }, {
	    key: "onSaveList",
	    value: function onSaveList() {
	      var _this3 = this;
	      if (this.isEmpty()) {
	        return;
	      }
	      this.save().then(function (decision) {
	        if (decision === 'resolve') {
	          _this3.emit('resolve');
	          _this3.sidePanelManager.close(false);
	        } else if (decision === 'reject') {
	          _this3.emit('reject');
	          _this3.sidePanelManager.close(false);
	        }
	      });
	    }
	  }, {
	    key: "getListButtonText",
	    value: function getListButtonText() {
	      if (this.isSkipNotifications()) {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT');
	      } else {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT');
	      }
	    }
	  }, {
	    key: "renderContent",
	    value: function renderContent() {
	      var _this4 = this;
	      return this.requestSender.getSettings({
	        groupId: this.groupId,
	        taskId: this.taskId,
	        saveRequest: this.isSkipNotifications() ? 'Y' : 'N'
	      }).then(function (response) {
	        var types = main_core.Type.isArray(response.data.types) ? response.data.types : [];
	        var activeTypeId = main_core.Type.isInteger(response.data.activeTypeId) ? parseInt(response.data.activeTypeId, 10) : 0;
	        _this4.empty = types.length === 0;
	        var itemTypes = new Map();
	        types.forEach(function (typeData) {
	          var itemType = new ItemType(typeData);
	          itemTypes.set(itemType.getId(), itemType);
	        });
	        _this4.typeStorage.setTypes(itemTypes);
	        _this4.typeStorage.setActiveType(itemTypes.get(activeTypeId));
	        if (_this4.isEmpty()) {
	          if (!_this4.isSkipNotifications()) {
	            _this4.emit('resolve');
	          }
	          return _this4.renderEmpty();
	        }
	        return _this4.render();
	      })["catch"](function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderList",
	    value: function renderList() {
	      var _this5 = this;
	      var listNode = this.node.querySelector('.tasks-scrum-dod-checklist');
	      main_core.Dom.clean(listNode);
	      var loader = this.showLoader(listNode);
	      this.requestSender.getList({
	        groupId: this.groupId,
	        taskId: this.taskId,
	        typeId: this.getActiveType().getId()
	      }).then(function (response) {
	        loader.hide();
	        main_core.Runtime.html(listNode, response.data.html);
	      })["catch"](function (response) {
	        _this5.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderEmpty",
	    value: function renderEmpty() {
	      var _this6 = this;
	      var node = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_EMPTY'));
	      main_core.Event.bind(node.querySelector('span'), 'click', function () {
	        return _this6.emit('showSettings', true);
	      });
	      return node;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this7 = this;
	      var activeType = this.getActiveType();
	      var renderOption = function renderOption(typeData) {
	        var selected = activeType.getId() === typeData.id ? 'selected' : '';
	        return "<option value=\"".concat(parseInt(typeData.id, 10), "\" ").concat(selected, ">").concat(main_core.Text.encode(typeData.name), "</option>");
	      };
	      this.node = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element tasks-scrum-dod-types\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content tasks-scrum-dod-checklist\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_TYPES'), babelHelpers.toConsumableArray(this.typeStorage.getTypes().values()).map(function (typeData) {
	        return renderOption(typeData);
	      }).join(''));
	      var typeSelector = this.node.querySelector('.tasks-scrum-dod-types');
	      main_core.Event.bind(typeSelector, 'change', function (event) {
	        var typeId = parseInt(event.target.value, 10);
	        _this7.typeStorage.setActiveType(_this7.typeStorage.getType(typeId));
	        _this7.renderList();
	      });
	      return this.node;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _this8 = this;
	      var activeType = this.getActiveType();
	      return this.requestSender.saveList({
	        typeId: activeType.getId(),
	        taskId: this.taskId,
	        groupId: this.groupId,
	        items: this.getListItems()
	      }).then(function () {
	        if (_this8.isSkipNotifications()) {
	          return _this8.solve();
	        } else {
	          if (_this8.isListRequired(_this8.getActiveType())) {
	            if (_this8.isAllToggled()) {
	              return 'resolve';
	            } else {
	              _this8.showInfoPopup();
	              return 'wait';
	            }
	          } else {
	            if (_this8.isAllToggled()) {
	              return 'resolve';
	            } else {
	              _this8.showConfirmPopup();
	              return 'wait';
	            }
	          }
	        }
	      })["catch"](function (response) {
	        _this8.requestSender.showErrorAlert(response);
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
	      return this.typeStorage.getActiveType();
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
	          return 'resolve';
	        } else {
	          return 'reject';
	        }
	      } else {
	        return 'resolve';
	      }
	    }
	  }, {
	    key: "getListItems",
	    value: function getListItems() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }
	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
	      return treeStructure.getRequestData();
	      /* eslint-enable */
	    }
	  }, {
	    key: "isAllToggled",
	    value: function isAllToggled() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return false;
	      }
	      var isAllToggled = true;
	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
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
	      var popupOptions = {};
	      var currentSlider = this.sidePanelManager.getTopSlider();
	      if (currentSlider) {
	        popupOptions.targetContainer = currentSlider.getContainer();
	      }
	      new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'),
	        popupOptions: popupOptions,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK
	      }).show();
	    }
	  }, {
	    key: "showConfirmPopup",
	    value: function showConfirmPopup() {
	      var _this9 = this;
	      var popupOptions = {};
	      var currentSlider = this.sidePanelManager.getTopSlider();
	      if (currentSlider) {
	        popupOptions.targetContainer = currentSlider.getContainer();
	      }
	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        popupOptions: popupOptions,
	        message: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_TEXT_COMPLETE'),
	        modal: true,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.SUCCESS,
	          events: {
	            click: function click() {
	              _this9.emit('resolve');
	              messageBox.close();
	            }
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.LINK,
	          events: {
	            click: function click() {
	              _this9.emit('reject');
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
	      _this.emit('resolve');
	      _this.sidePanelManager.close(false);
	    });
	    _this.list.subscribe('reject', function () {
	      _this.emit('reject');
	      _this.sidePanelManager.close(false);
	    });
	    _this.list.subscribe('showSettings', function (baseEvent) {
	      var close = baseEvent.getData();
	      if (close) {
	        _this.sidePanelManager.close(false, function () {
	          return _this.showSettings();
	        });
	      } else {
	        _this.showSettings();
	      }
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
	      this.settings.show();
	    }
	  }, {
	    key: "showList",
	    value: function showList() {
	      this.list.show();
	    }
	  }]);
	  return Dod;
	}(main_core_events.EventEmitter);

	exports.Dod = Dod;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI.EntitySelector,BX.UI.SidePanel,BX,BX,BX.Event,BX,BX.UI.Dialogs,BX.UI,BX.UI.SidePanel));
//# sourceMappingURL=dod.bundle.js.map

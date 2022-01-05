this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,ui_sidepanel_layout,ui_label,main_core) {
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
	        top.BX.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
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
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSE_ERROR_POPUP_TITLE');
	          top.BX.UI.Dialogs.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epics-empty\">\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-first-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-image\">\n\t\t\t\t\t<svg width=\"124px\" height=\"123px\" viewBox=\"0 0 124 123\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n\t\t\t\t\t\t<g stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\" opacity=\"0.28\">\n\t\t\t\t\t\t\t<path d=\"M83,105 L83,81.4375 L105,81.4375 L105,18 L17,18 L17,81.4375 L39,81.4375 L39,105 L83,105 Z M10.9411765,0 L113.058824,0 C119.101468,0 124,4.85902727 124,10.8529412 L124,112.147059 C124,118.140973 119.101468,123 113.058824,123 L10.9411765,123 C4.89853156,123 0,118.140973 0,112.147059 L0,10.8529412 C0,4.85902727 4.89853156,0 10.9411765,0 Z M44.0142862,47.0500004 L54.2142857,57.4416671 L79.7142857,32 L87,42.75 L54.2142857,75 L36,57.0833333 L44.0142862,47.0500004 Z\" fill=\"#A8ADB4\" />\n\t\t\t\t\t\t</g>\n\t\t\t\t\t</svg>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-second-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epics-empty-button\">\n\t\t\t\t\t<button class=\"ui-btn ui-btn-primary ui-btn-lg\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-description\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t<input type=\"text\" name=\"name\" value=\"", "\" class=\n\t\t\t\t\t\t\"tasks-scrum-epic-form-header-title-control\" placeholder=\n\t\t\t\t\t\t\"", "\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-btn-angle\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-header-separate\"></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum-epic-header-color-current\" style=\n\t\t\t\t\t\t\t\t\"background-color: ", ";\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum-epic-form-body\">\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-description\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum-epic-form-files\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-epic-list\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-epic-form\">\n\t\t\t\t<div class=\"tasks-scrum-epic-form-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject = function _templateObject() {
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

	    _this.view = params.view ? params.view : '';
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.epicId = parseInt(params.epicId, 10);
	    _this.requestSender = new RequestSender();
	    /* eslint-disable */

	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.sidePanel = null;
	    _this.id = main_core.Text.getRandom();
	    _this.form = null;
	    _this.formData = null;
	    _this.listData = null;
	    _this.editorHandler = null;
	    _this.defaultColor = '#69dafc';
	    _this.selectedColor = '';
	    return _this;
	  }

	  babelHelpers.createClass(Epic, [{
	    key: "show",
	    value: function show() {
	      switch (this.view) {
	        case 'add':
	          this.showAddForm();
	          break;

	        case 'list':
	          this.showList();
	          break;

	        case 'view':
	          this.showViewForm();
	          break;

	        case 'edit':
	          this.showEditForm();
	          break;
	      }
	    }
	  }, {
	    key: "showAddForm",
	    value: function showAddForm() {
	      var _this2 = this;

	      this.sidePanelManager.open('tasks-scrum-epic-add-form-side-panel', {
	        cacheable: false,
	        width: 800,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.epic'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_ADD_EPIC_FORM_TITLE'),
	            content: _this2.createAddContent.bind(_this2),
	            design: {
	              section: false
	            },
	            buttons: function buttons(_ref) {
	              var cancelButton = _ref.cancelButton,
	                  SaveButton = _ref.SaveButton;
	              return [new SaveButton({
	                onclick: _this2.onSaveAddForm.bind(_this2)
	              }), cancelButton];
	            }
	          });
	        },
	        events: {
	          onLoad: this.onLoadAddForm.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "showList",
	    value: function showList() {
	      var _this3 = this;

	      this.gridId = 'EntityEpicsGrid_' + this.groupId;
	      var sidePanelId = 'tasks-scrum-epic-list-side-panel';
	      this.subscribeListToEvents(sidePanelId);
	      this.sidePanelManager.open(sidePanelId, {
	        cacheable: false,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.epic'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TITLE'),
	            toolbar: function toolbar(_ref2) {
	              var Button = _ref2.Button;
	              return [new Button({
	                text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TOOLBAR_BUTTON'),
	                color: Button.Color.PRIMARY,
	                onclick: function onclick() {
	                  _this3.showAddForm();
	                }
	              })];
	            },
	            content: _this3.createListContent.bind(_this3),
	            design: {
	              section: false
	            },
	            buttons: []
	          });
	        },
	        events: {
	          onLoad: this.onLoadList.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "showViewForm",
	    value: function showViewForm() {
	      var _this4 = this;

	      this.subscribeViewToEvents();
	      this.sidePanelManager.open('tasks-scrum-epic-view-form-side-panel', {
	        cacheable: false,
	        width: 800,
	        contentCallback: function contentCallback() {
	          return new Promise(function (resolve, reject) {
	            _this4.getEpic().then(function (response) {
	              var epic = response.data;
	              resolve(ui_sidepanel_layout.Layout.createContent({
	                extensions: ['tasks.scrum.epic'],
	                title: main_core.Loc.getMessage('TASKS_SCRUM_VIEW_EPIC_FORM_TITLE'),
	                content: _this4.createViewContent.bind(_this4, epic),
	                design: {
	                  section: false
	                },
	                buttons: function buttons(_ref3) {
	                  var cancelButton = _ref3.cancelButton,
	                      SaveButton = _ref3.SaveButton;
	                  return [new SaveButton({
	                    text: main_core.Loc.getMessage('TASKS_SCRUM_EPIC_EDIT_BUTTON'),
	                    onclick: function onclick() {
	                      _this4.sidePanel.close(false, function () {
	                        main_core_events.EventEmitter.emit(_this4.getEventNamespace() + ':' + 'openEdit', epic.id);
	                      });
	                    }
	                  }), cancelButton];
	                }
	              }));
	            });
	          });
	        },
	        events: {
	          onLoad: this.onLoadViewForm.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "showEditForm",
	    value: function showEditForm() {
	      var _this5 = this;

	      this.sidePanelManager.open('tasks-scrum-epic-edit-form-side-panel', {
	        cacheable: false,
	        width: 800,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.epic'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_EDIT_EPIC_FORM_TITLE'),
	            content: _this5.createEditContent.bind(_this5),
	            design: {
	              section: false
	            },
	            buttons: function buttons(_ref4) {
	              var cancelButton = _ref4.cancelButton,
	                  SaveButton = _ref4.SaveButton;
	              return [new SaveButton({
	                onclick: _this5.onSaveEditForm.bind(_this5)
	              }), cancelButton];
	            }
	          });
	        },
	        events: {
	          onLoad: this.onLoadEditForm.bind(this)
	        }
	      });
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic() {
	      var _this6 = this;

	      return main_core.ajax.runAction('bitrix:tasks.scrum.epic.removeEpic', {
	        data: {
	          groupId: this.groupId,
	          epicId: this.epicId
	        }
	      }).then(function (response) {
	        main_core_events.EventEmitter.emit(_this6.getEventNamespace() + ':' + 'afterRemove', response.data);
	        return true;
	      });
	    }
	  }, {
	    key: "subscribeListToEvents",
	    value: function subscribeListToEvents(sidePanelId) {
	      var _this7 = this;

	      main_core_events.EventEmitter.subscribe(this.getEventNamespace() + ':' + 'afterAdd', function () {
	        _this7.reloadSidePanel(sidePanelId);
	      });
	      top.BX.Event.EventEmitter.subscribe(this.getEventNamespace() + ':' + 'afterEdit', function () {
	        _this7.reloadSidePanel(sidePanelId);
	      });
	      main_core_events.EventEmitter.subscribe(this.getEventNamespace() + ':' + 'afterRemove', function () {
	        _this7.reloadSidePanel(sidePanelId);
	      });
	    }
	  }, {
	    key: "subscribeViewToEvents",
	    value: function subscribeViewToEvents() {
	      var _this8 = this;

	      main_core_events.EventEmitter.subscribe(this.getEventNamespace() + ':' + 'openEdit', function (baseEvent) {
	        Epic.showEdit(_this8.groupId, baseEvent.getData());
	      });
	    }
	  }, {
	    key: "reloadSidePanel",
	    value: function reloadSidePanel(sidePanelId) {
	      if (main_core.Type.isUndefined(sidePanelId)) {
	        this.sidePanelManager.reload();
	      } else {
	        var openSliders = this.sidePanelManager.getOpenSliders();

	        if (openSliders.length > 0) {
	          openSliders.forEach(function (slider) {
	            if (slider.getUrl() === sidePanelId) {
	              slider.reload();
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "createAddContent",
	    value: function createAddContent() {
	      var _this9 = this;

	      return new Promise(function (resolve, reject) {
	        top.BX.ajax.runAction('bitrix:tasks.scrum.epic.getDescriptionEditor', {
	          data: {
	            groupId: _this9.groupId,
	            editorId: _this9.id
	          }
	        }).then(function (response) {
	          _this9.formData = response.data;
	          resolve(_this9.renderAddForm());
	        });
	      });
	    }
	  }, {
	    key: "createListContent",
	    value: function createListContent() {
	      var _this10 = this;

	      return new Promise(function (resolve, reject) {
	        top.BX.ajax.runAction('bitrix:tasks.scrum.epic.getList', {
	          data: {
	            groupId: _this10.groupId,
	            gridId: _this10.gridId
	          }
	        }).then(function (response) {
	          _this10.listData = response.data;
	          resolve(_this10.renderList());
	        });
	      });
	    }
	  }, {
	    key: "createViewContent",
	    value: function createViewContent(epic) {
	      var _this11 = this;

	      return new Promise(function (resolve, reject) {
	        top.BX.ajax.runAction('bitrix:tasks.scrum.epic.getEpicFiles', {
	          data: {
	            groupId: _this11.groupId,
	            epicId: epic.id
	          }
	        }).then(function (response) {
	          _this11.epicFiles = main_core.Type.isUndefined(response.data.html) ? '' : response.data.html;
	          resolve(_this11.renderViewForm(epic));
	        });
	      });
	    }
	  }, {
	    key: "createEditContent",
	    value: function createEditContent() {
	      var _this12 = this;

	      return new Promise(function (resolve, reject) {
	        _this12.getEpic().then(function (response) {
	          var epic = response.data;
	          top.BX.ajax.runAction('bitrix:tasks.scrum.epic.getDescriptionEditor', {
	            data: {
	              groupId: _this12.groupId,
	              editorId: _this12.id,
	              epicId: epic.id,
	              text: epic.description
	            }
	          }).then(function (response) {
	            _this12.currentEpic = epic;
	            _this12.formData = response.data;
	            resolve(_this12.renderEditForm(epic));
	          });
	        });
	      });
	    }
	  }, {
	    key: "getEpic",
	    value: function getEpic() {
	      var _this13 = this;

	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.epic.getEpic', {
	          data: {
	            groupId: _this13.groupId,
	            epicId: _this13.epicId
	          }
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "onLoadAddForm",
	    value: function onLoadAddForm(event) {
	      this.sidePanel = event.getSlider();
	      this.form = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
	      var descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');

	      if (main_core.Type.isUndefined(this.formData.html)) {
	        return;
	      }

	      this.renderEditor(descriptionContainer);
	    }
	  }, {
	    key: "onSaveAddForm",
	    value: function onSaveAddForm() {
	      var _this14 = this;

	      main_core.ajax.runAction('bitrix:tasks.scrum.epic.createEpic', {
	        data: this.getRequestData()
	      }).then(function (response) {
	        _this14.sidePanel.close(false, function () {
	          main_core_events.EventEmitter.emit(_this14.getEventNamespace() + ':' + 'afterAdd', response.data);
	        });
	      }).catch(function (response) {
	        _this14.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onLoadList",
	    value: function onLoadList(event) {
	      var _this15 = this;

	      this.sidePanel = event.getSlider();
	      var listContainer = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-list');

	      if (main_core.Type.isUndefined(this.listData.html)) {
	        main_core.Dom.append(this.renderListBlank(), listContainer);
	        main_core.Event.bind(listContainer.querySelector('.tasks-scrum-epics-empty-button'), 'click', this.showAddForm.bind(this));
	      } else {
	        top.BX.Runtime.html(listContainer, this.listData.html).then(function () {
	          top.BX.addCustomEvent('Grid::beforeRequest', _this15.onBeforeGridRequest.bind(_this15));

	          _this15.prepareTagsList(listContainer);
	        });
	      }
	    }
	  }, {
	    key: "onLoadViewForm",
	    value: function onLoadViewForm(event) {
	      this.sidePanel = event.getSlider();

	      if (this.epicFiles) {
	        var filesContainer = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form-files');
	        main_core.Runtime.html(filesContainer, this.epicFiles);
	      }
	    }
	  }, {
	    key: "onLoadEditForm",
	    value: function onLoadEditForm(event) {
	      this.sidePanel = event.getSlider();
	      this.form = this.sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
	      var descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');

	      if (main_core.Type.isUndefined(this.formData.html)) {
	        return;
	      }

	      this.renderEditor(descriptionContainer);
	    }
	  }, {
	    key: "onSaveEditForm",
	    value: function onSaveEditForm() {
	      var _this16 = this;

	      main_core.ajax.runAction('bitrix:tasks.scrum.epic.editEpic', {
	        data: this.getRequestData()
	      }).then(function (response) {
	        _this16.sidePanel.close(false, function () {
	          top.BX.Event.EventEmitter.emit(_this16.getEventNamespace() + ':' + 'afterEdit', response.data);
	        });
	      }).catch(function (response) {
	        _this16.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "onBeforeGridRequest",
	    value: function onBeforeGridRequest(gridObject, eventArgs) {
	      /* eslint-disable */
	      eventArgs.sessid = BX.bitrix_sessid();
	      /* eslint-enable */

	      eventArgs.method = 'POST';

	      if (!eventArgs.url) {
	        eventArgs.url = this.getListUrl();
	      }

	      eventArgs.data = babelHelpers.objectSpread({}, eventArgs.data, {
	        groupId: this.groupId,
	        gridId: this.gridId
	      });
	    }
	  }, {
	    key: "getListUrl",
	    value: function getListUrl() {
	      return '/bitrix/services/main/ajax.php?action=bitrix:tasks.scrum.epic.getList';
	    }
	  }, {
	    key: "renderAddForm",
	    value: function renderAddForm() {
	      return main_core.Tag.render(_templateObject(), this.renderNameField('', this.defaultColor), this.renderDescriptionField());
	    }
	  }, {
	    key: "renderList",
	    value: function renderList() {
	      return main_core.Tag.render(_templateObject2());
	    }
	  }, {
	    key: "renderViewForm",
	    value: function renderViewForm(epic) {
	      return main_core.Tag.render(_templateObject3(), main_core.Text.encode(epic.name), main_core.Text.encode(epic.color), epic.description);
	    }
	  }, {
	    key: "renderEditForm",
	    value: function renderEditForm(epic) {
	      this.selectedColor = epic.color;
	      return main_core.Tag.render(_templateObject4(), this.renderNameField(epic.name, this.selectedColor), this.renderDescriptionField());
	    }
	  }, {
	    key: "renderNameField",
	    value: function renderNameField(name, color) {
	      var _this17 = this;

	      var nameField = main_core.Tag.render(_templateObject5(), main_core.Text.encode(name), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_NAME_PLACEHOLDER'), main_core.Text.encode(color));
	      var pickerContainer = nameField.querySelector('.tasks-scrum-epic-header-color');
	      main_core.Event.bind(pickerContainer, 'click', function () {
	        var colorNode = pickerContainer.querySelector('.tasks-scrum-epic-header-color-current');

	        var picker = _this17.getColorPicker(colorNode);

	        picker.open();
	      });
	      return nameField;
	    }
	  }, {
	    key: "renderDescriptionField",
	    value: function renderDescriptionField() {
	      return main_core.Tag.render(_templateObject6());
	    }
	  }, {
	    key: "renderEditor",
	    value: function renderEditor(container) {
	      var _this18 = this;

	      setTimeout(function () {
	        top.BX.Runtime.html(container, _this18.formData.html).then(function () {
	          if (window.top.LHEPostForm) {
	            _this18.editorHandler = window.top.LHEPostForm.getHandler(_this18.id);
	            main_core_events.EventEmitter.emit(_this18.editorHandler.eventNode, 'OnShowLHE', [true]);
	          }

	          _this18.focusToName();
	        });
	      }, 300);
	    }
	  }, {
	    key: "renderListBlank",
	    value: function renderListBlank() {
	      return main_core.Tag.render(_templateObject7(), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_FIRST_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_SECOND_TITLE'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TOOLBAR_BUTTON'));
	    }
	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      /* eslint-disable */
	      if (top.BX && top.BX.Main && top.BX.Main.gridManager) {
	        return top.BX.Main.gridManager.getById(this.gridId);
	      }
	      /* eslint-enable */


	      return null;
	    }
	  }, {
	    key: "prepareTagsList",
	    value: function prepareTagsList(container) {
	      var _this19 = this;

	      var tagsContainers = container.querySelectorAll('.tasks-scrum-epic-grid-tags');
	      tagsContainers.forEach(function (tagsContainer) {
	        var tags = _this19.getTagsFromNode(tagsContainer);

	        main_core.Dom.clean(tagsContainer);
	        tags.forEach(function (tag) {
	          main_core.Dom.append(_this19.getTagNode(tag), tagsContainer);
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
	      var _this20 = this;

	      var tagLabel = new ui_label.Label({
	        text: tag,
	        color: ui_label.Label.Color.TAG_LIGHT,
	        fill: true,
	        size: ui_label.Label.Size.SM,
	        customClass: ''
	      });
	      var container = tagLabel.getContainer();
	      main_core.Event.bind(container, 'click', function () {
	        _this20.sidePanel.close(false, function () {
	          main_core_events.EventEmitter.emit(_this20.getEventNamespace() + ':' + 'filterByTag', tag);
	        });
	      });
	      return container;
	    }
	  }, {
	    key: "getColorPicker",
	    value: function getColorPicker(colorNode) {
	      var _this21 = this;

	      /* eslint-disable */
	      return new top.BX.ColorPicker({
	        bindElement: colorNode,
	        defaultColor: this.defaultColor,
	        selectedColor: this.selectedColor ? this.selectedColor : this.defaultColor,
	        onColorSelected: function onColorSelected(color, picker) {
	          _this21.selectedColor = color;
	          colorNode.style.backgroundColor = color;
	        },
	        popupOptions: {
	          className: 'tasks-scrum-epic-color-popup'
	        },
	        allowCustomColor: false,
	        colors: [['#aae9fc', '#bbecf1', '#98e1dc', '#e3f299', '#ffee95', '#ffdd93', '#dfd3b6', '#e3c6bb'], ['#ffad97', '#ffbdbb', '#ffcbd8', '#ffc4e4', '#c4baed', '#dbdde0', '#bfc5cd', '#a2a8b0']]
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "focusToName",
	    value: function focusToName() {
	      var _this22 = this;

	      setTimeout(function () {
	        _this22.form.querySelector('.tasks-scrum-epic-form-header-title-control').focus();
	      }, 50);
	    }
	  }, {
	    key: "getRequestData",
	    value: function getRequestData() {
	      var requestData = {};

	      if (this.currentEpic) {
	        requestData.epicId = this.currentEpic.id;
	      }

	      requestData.groupId = this.groupId;
	      requestData.name = this.form.querySelector('[name=name]').value.trim();
	      requestData.description = this.editorHandler.getEditor().GetContent();
	      requestData.color = this.selectedColor ? this.selectedColor : this.defaultColor;
	      requestData.files = this.getAttachmentsFiles();
	      return requestData;
	    }
	  }, {
	    key: "getAttachmentsFiles",
	    value: function getAttachmentsFiles() {
	      var _this23 = this;

	      var files = [];

	      if (!this.editorHandler || !main_core.Type.isPlainObject(this.editorHandler.arFiles) || !main_core.Type.isPlainObject(this.editorHandler.controllers)) {
	        return files;
	      }

	      var fileControllers = [];
	      Object.values(this.editorHandler.arFiles).forEach(function (controller) {
	        if (!fileControllers.includes(controller)) {
	          fileControllers.push(controller);
	        }
	      });
	      fileControllers.forEach(function (fileController) {
	        if (_this23.editorHandler.controllers[fileController] && main_core.Type.isPlainObject(_this23.editorHandler.controllers[fileController].values)) {
	          Object.keys(_this23.editorHandler.controllers[fileController].values).forEach(function (fileId) {
	            if (!files.includes(fileId)) {
	              files.push(fileId);
	            }
	          });
	        }
	      });
	      return files;
	    }
	  }], [{
	    key: "showView",
	    value: function showView(groupId, epicId) {
	      var epic = new Epic({
	        view: 'view',
	        groupId: groupId,
	        epicId: epicId
	      });
	      epic.show();
	    }
	  }, {
	    key: "showEdit",
	    value: function showEdit(groupId, epicId) {
	      var epic = new Epic({
	        view: 'edit',
	        groupId: groupId,
	        epicId: epicId
	      });
	      epic.show();
	    }
	  }, {
	    key: "removeEpic",
	    value: function removeEpic(groupId, epicId) {
	      var epic = new Epic({
	        view: 'edit',
	        groupId: groupId,
	        epicId: epicId
	      });
	      epic.removeEpic().then(function () {
	        epic.reloadSidePanel();
	      });
	    }
	  }]);
	  return Epic;
	}(main_core_events.EventEmitter);

	exports.Epic = Epic;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX.UI.SidePanel,BX.UI,BX));
//# sourceMappingURL=epic.bundle.js.map

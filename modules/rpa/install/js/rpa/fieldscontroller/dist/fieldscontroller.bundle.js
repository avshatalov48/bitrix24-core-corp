this.BX = this.BX || {};
(function (exports,main_core,ui_userfieldfactory,ui_userfield,main_loader,main_core_events,main_popup,rpa_manager) {
	'use strict';

	function _templateObject14() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-select-field-button\" onclick=\"", "\">", "</div>"]);

	  _templateObject14 = function _templateObject14() {
	    return data;
	  };

	  return data;
	}

	function _templateObject13() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-create-field-button\" onclick=\"", "\">", "</div>"]);

	  _templateObject13 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-footer\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject12 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span data-switcher='", "' class=\"ui-switcher rpa-fields-controller-switcher\"></span>"]);

	  _templateObject11 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-settings\"></div>"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-wrapper-gear\"></div>"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-wrapper\"></div>"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-title\">", "</div>"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-subtitle\">", "</div>"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-container\"></div>"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-field-row\" data-role=\"field-row-", "\"></div>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-fields\"></div>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-controller-container\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	/**
	 * @memberof BX.Rpa
	 * @mixes EventEmitter
	 */

	var FieldsController =
	/*#__PURE__*/
	function () {
	  function FieldsController(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, FieldsController);
	    main_core_events.EventEmitter.makeObservable(this, 'BX.Rpa.FieldsController');
	    this.fields = new Map();
	    this.hiddenFields = new Map();
	    this.layout = {};
	    this.fieldSubTitle = main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_FIELD_DEFAULT_SUBTITLE');
	    this.errorContainer = null;
	    this.progress = false;

	    if (main_core.Type.isPlainObject(params)) {
	      if (params.factory instanceof ui_userfieldfactory.Factory) {
	        this.factory = params.factory;
	      }

	      if (main_core.Type.isPlainObject(params.fields)) {
	        this.setFields(params.fields);
	      }

	      if (main_core.Type.isPlainObject(params.hiddenFields)) {
	        this.setHiddenFields(params.hiddenFields);
	      }

	      if (main_core.Type.isString(params.fieldSubTitle)) {
	        this.fieldSubTitle = params.fieldSubTitle;
	      }

	      if (main_core.Type.isDomNode(params.errorContainer)) {
	        this.errorContainer = params.errorContainer;
	      }

	      if (main_core.Type.isPlainObject(params.settings)) {
	        this.settings = params.settings;

	        if (!main_core.Type.isString(this.settings.inputName)) {
	          this.settings.inputName = '';
	        }

	        if (!main_core.Type.isPlainObject(this.settings.values)) {
	          this.settings.values = {};
	        }
	      }

	      this.typeId = main_core.Text.toInteger(params.typeId);
	      this.languageId = params.languageId || main_core.Loc.getMessage('LANGUAGE_ID');

	      if (this.factory) {
	        this.factory.setCustomTypesUrl(rpa_manager.Manager.Instance.getFieldDetailUrl(this.typeId, 0));
	        this.factory.subscribe('onCreateCustomUserField', function (event) {
	          var userField = event.getData().userField;

	          _this.addField(userField).renderFields();
	        });
	      }
	    }
	  }

	  babelHelpers.createClass(FieldsController, [{
	    key: "getFields",
	    value: function getFields() {
	      return this.fields;
	    }
	  }, {
	    key: "setFields",
	    value: function setFields(fields) {
	      var _this2 = this;

	      Object.keys(fields).forEach(function (fieldName) {
	        _this2.addField(new ui_userfield.UserField(fields[fieldName], {
	          languageId: _this2.languageId,
	          moduleId: _this2.factory ? _this2.factory.moduleId : null
	        }));
	      });
	      return this;
	    }
	  }, {
	    key: "addField",
	    value: function addField(userField) {
	      this.fields.set(userField.getName(), userField);
	      return this;
	    }
	  }, {
	    key: "removeField",
	    value: function removeField(userField) {
	      this.fields.delete(userField.getName());
	      return this;
	    }
	  }, {
	    key: "setHiddenFields",
	    value: function setHiddenFields(fields) {
	      var _this3 = this;

	      Object.keys(fields).forEach(function (fieldName) {
	        _this3.addHiddenField(new ui_userfield.UserField(fields[fieldName], {
	          languageId: _this3.languageId,
	          moduleId: _this3.factory ? _this3.factory.moduleId : null
	        }));
	      });
	      return this;
	    }
	  }, {
	    key: "addHiddenField",
	    value: function addHiddenField(userField) {
	      this.hiddenFields.set(userField.getName(), userField);
	      return this;
	    }
	  }, {
	    key: "removeHiddenField",
	    value: function removeHiddenField(userField) {
	      this.hiddenFields.delete(userField.getName());
	      return this;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var container = this.renderContainer();
	      container.appendChild(this.renderFields());

	      if (this.factory) {
	        this.layout.configurator = main_core.Tag.render(_templateObject());
	        container.appendChild(this.layout.configurator);
	        container.appendChild(this.renderFooter());
	      }

	      return container;
	    }
	  }, {
	    key: "renderContainer",
	    value: function renderContainer() {
	      this.layout.container = main_core.Tag.render(_templateObject2());
	      return this.getContainer();
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.layout.container;
	    }
	  }, {
	    key: "renderFields",
	    value: function renderFields() {
	      var _this4 = this;

	      if (this.layout.fieldsContainer) {
	        main_core.Dom.clean(this.layout.fieldsContainer);

	        if (this.settings) {
	          this.settings.values = this.getSettings();
	        }
	      } else {
	        this.layout.fieldsContainer = main_core.Tag.render(_templateObject3());
	      }

	      Array.from(this.fields.values()).forEach(function (userField) {
	        _this4.layout.fieldsContainer.appendChild(_this4.renderField(userField));
	      });
	      return this.layout.fieldsContainer;
	    }
	  }, {
	    key: "getFieldRow",
	    value: function getFieldRow(userField) {
	      if (!this.layout.fieldsContainer) {
	        return null;
	      }

	      return this.layout.fieldsContainer.querySelector("[data-role=\"field-row-".concat(userField.getName(), "\"]"));
	    }
	  }, {
	    key: "renderField",
	    value: function renderField(userField) {
	      var _this5 = this;

	      var row = main_core.Tag.render(_templateObject4(), userField.getName());
	      var container = main_core.Tag.render(_templateObject5());

	      if (this.fieldSubTitle) {
	        container.appendChild(main_core.Tag.render(_templateObject6(), main_core.Text.encode(this.fieldSubTitle)));
	      }

	      container.appendChild(main_core.Tag.render(_templateObject7(), main_core.Text.encode(userField.getTitle())));
	      var wrapper = main_core.Tag.render(_templateObject8());
	      wrapper.appendChild(container);

	      if (this.settings) {
	        wrapper.appendChild(this.renderSwitcher(userField));
	      } else {
	        var fieldSettingsButton = main_core.Tag.render(_templateObject9());
	        this.getSettingsMenu(fieldSettingsButton, userField).destroy();
	        main_core.Event.bind(fieldSettingsButton, 'click', function () {
	          _this5.getSettingsMenu(fieldSettingsButton, userField).show();
	        });
	        wrapper.appendChild(fieldSettingsButton);
	      }

	      row.appendChild(wrapper);
	      row.appendChild(main_core.Tag.render(_templateObject10()));
	      return row;
	    }
	  }, {
	    key: "renderSwitcher",
	    value: function renderSwitcher(userField) {
	      var data = {
	        id: FieldsController.getSwitcherId(this.settings.inputName, userField.getName()),
	        checked: this.settings.values[userField.getName()] === true,
	        inputName: this.settings.inputName + '[' + userField.getName() + ']'
	      };
	      var switcher = main_core.Tag.render(_templateObject11(), JSON.stringify(data));
	      new BX.UI.Switcher({
	        node: switcher
	      });
	      return switcher;
	    }
	  }, {
	    key: "renderFooter",
	    value: function renderFooter() {
	      if (!this.layout.footer) {
	        this.layout.footer = main_core.Tag.render(_templateObject12(), this.getSelectButton(), this.getCreateButton());
	      }

	      this.updateSelectButtonAppearance();
	      return this.layout.footer;
	    }
	  }, {
	    key: "updateSelectButtonAppearance",
	    value: function updateSelectButtonAppearance() {
	      if (this.hiddenFields.size <= 0) {
	        this.getSelectButton().style.display = 'none';
	      } else {
	        this.getSelectButton().style.display = 'inline-block';
	      }

	      return this;
	    }
	  }, {
	    key: "getCreateButton",
	    value: function getCreateButton() {
	      if (!this.layout.createButton) {
	        this.layout.createButton = main_core.Tag.render(_templateObject13(), this.handleCreateButtonClick.bind(this), main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_FILED_CREATE_BUTTON'));
	      }

	      return this.layout.createButton;
	    }
	  }, {
	    key: "handleCreateButtonClick",
	    value: function handleCreateButtonClick() {
	      if (this.factory) {
	        this.factory.getMenu({
	          bindElement: this.getCreateButton()
	        }).open(this.handleUserFieldTypeClick.bind(this));
	      }
	    }
	  }, {
	    key: "getSelectButton",
	    value: function getSelectButton() {
	      if (!this.layout.selectButton) {
	        this.layout.selectButton = main_core.Tag.render(_templateObject14(), this.handleSelectButtonClick.bind(this), main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_FIELD_SELECT_BUTTON'));
	      }

	      return this.layout.selectButton;
	    }
	  }, {
	    key: "handleSelectButtonClick",
	    value: function handleSelectButtonClick() {
	      this.getSelectFieldsMenu().show();
	    }
	  }, {
	    key: "handleUserFieldTypeClick",
	    value: function handleUserFieldTypeClick(fieldType) {
	      if (!this.factory) {
	        return;
	      }

	      var userField = this.factory.createUserField(fieldType);

	      if (!userField) {
	        return;
	      }

	      this.showFieldConfigurator(userField);
	    }
	  }, {
	    key: "showFieldConfigurator",
	    value: function showFieldConfigurator(userField) {
	      var _this6 = this;

	      if (userField.isSaved()) {
	        var row = this.getFieldRow(userField);

	        if (row) {
	          var settings = row.querySelector('.rpa-fields-controller-field-settings');

	          if (settings) {
	            row.classList.add('rpa-fields-controller-edit');
	            main_core.Dom.clean(settings);
	            settings.appendChild(this.factory.getConfigurator({
	              userField: userField,
	              onSave: this.handleFieldSave.bind(this),
	              onCancel: function onCancel() {
	                _this6.hideFieldConfigurator(userField);
	              }
	            }).render());
	          }
	        }
	      } else {
	        main_core.Dom.clean(this.layout.configurator);
	        this.layout.configurator.appendChild(this.factory.getConfigurator({
	          userField: userField,
	          onSave: this.handleFieldSave.bind(this)
	        }).render());
	      }
	    }
	  }, {
	    key: "hideFieldConfigurator",
	    value: function hideFieldConfigurator(userField) {
	      var row = this.getFieldRow(userField);

	      if (row) {
	        row.classList.remove('rpa-fields-controller-edit');
	        var settings = row.querySelector('.rpa-fields-controller-field-settings');

	        if (settings) {
	          main_core.Dom.clean(settings);
	        }
	      }
	    }
	  }, {
	    key: "handleFieldSave",
	    value: function handleFieldSave(userField) {
	      var _this7 = this;

	      if (!this.factory) {
	        return;
	      }

	      if (this.isProgress()) {
	        return;
	      }

	      this.startProgress();
	      userField.save().then(function () {
	        _this7.stopProgress().addField(userField).renderFields();

	        main_core.Dom.clean(_this7.layout.configurator);

	        _this7.emit('onFieldSave', {
	          userField: userField
	        });
	      }).catch(function (errors) {
	        _this7.stopProgress();

	        _this7.showError(errors);
	      });
	    }
	  }, {
	    key: "isProgress",
	    value: function isProgress() {
	      return this.progress;
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      this.progress = true;

	      if (!this.getLoader().isShown()) {
	        this.getLoader().show(this.getContainer());
	      }

	      return this;
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      this.progress = false;
	      this.getLoader().hide();
	      return this;
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 150
	        });
	      }

	      return this.loader;
	    }
	  }, {
	    key: "showError",
	    value: function showError(error, errorContainer) {
	      if (!errorContainer) {
	        errorContainer = this.errorContainer;
	      }

	      var message = '';

	      if (main_core.Type.isArray(error)) {
	        message = error.join(", ");
	      } else if (main_core.Type.isString(error)) {
	        message = error;
	      }

	      if (message) {
	        if (main_core.Type.isDomNode(errorContainer)) {
	          errorContainer.innerHTML = message;
	          errorContainer.parentNode.style.display = 'block';
	          window.scrollTo(0, main_core.Dom.getPosition(errorContainer).top);
	        } else {
	          console.error(message);
	        }
	      }
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      var _this8 = this;

	      var settings = {};

	      if (!this.settings) {
	        return settings;
	      }

	      Array.from(this.fields.values()).forEach(function (userField) {
	        var switcher = BX.UI.Switcher.getById(FieldsController.getSwitcherId(_this8.settings.inputName, userField.getName()));

	        if (switcher) {
	          settings[userField.getName()] = switcher.isChecked();
	        }
	      });
	      return settings;
	    }
	  }, {
	    key: "getSelectFieldsMenuId",
	    value: function getSelectFieldsMenuId() {
	      return 'rpa-fieldscontorller-select-field-menu';
	    }
	  }, {
	    key: "getSelectFieldsMenuItems",
	    value: function getSelectFieldsMenuItems() {
	      var _this9 = this;

	      var items = [];
	      Array.from(this.hiddenFields.values()).forEach(function (userField) {
	        items.push({
	          text: userField.getTitle(),
	          onclick: function onclick() {
	            _this9.handleHiddenUserFieldClick(userField);
	          }
	        });
	      });
	      return items;
	    }
	  }, {
	    key: "getSelectFieldsMenu",
	    value: function getSelectFieldsMenu() {
	      var _this10 = this;

	      if (!this.getSelectButton()) {
	        return;
	      }

	      return main_popup.MenuManager.create({
	        id: this.getSelectFieldsMenuId(),
	        bindElement: this.getSelectButton(),
	        items: this.getSelectFieldsMenuItems(),
	        offsetTop: 0,
	        offsetLeft: 16,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this10.getSelectFieldsMenu().destroy();
	          }
	        }
	      });
	    }
	  }, {
	    key: "handleHiddenUserFieldClick",
	    value: function handleHiddenUserFieldClick(userField) {
	      this.addField(userField).removeHiddenField(userField).updateSelectButtonAppearance().renderFields();
	      this.getSelectFieldsMenu().close();
	    }
	  }, {
	    key: "getSettingsMenu",
	    value: function getSettingsMenu(button, userField) {
	      var _this11 = this;

	      return main_popup.MenuManager.create({
	        id: 'rpa-fieldscontroller-field-settings-' + userField.getId(),
	        bindElement: button,
	        items: [{
	          text: main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_HIDE'),
	          onclick: function onclick(event, item) {
	            _this11.removeField(userField).addHiddenField(userField).updateSelectButtonAppearance().renderFields();

	            if (item && item.menuWindow) {
	              item.menuWindow.close();
	            }
	          }
	        }, {
	          text: main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_EDIT'),
	          onclick: function onclick(event, item) {
	            _this11.showFieldConfigurator(userField);

	            if (item && item.menuWindow) {
	              item.menuWindow.close();
	            }
	          }
	        }, {
	          text: main_core.Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_ADJUST'),
	          onclick: function onclick(event, item) {
	            rpa_manager.Manager.Instance.openFieldDetail(_this11.typeId, userField.getId(), {
	              width: 900,
	              cacheable: false
	            }).then(function (slider) {
	              var userFieldData = slider.getData().get('userFieldData');

	              if (userFieldData) {
	                userField = ui_userfield.UserField.unserialize(userFieldData);

	                if (userField.isDeleted()) {
	                  _this11.removeField(userField).renderFields();
	                } else {
	                  _this11.addField(userField);

	                  _this11.renderFields();
	                }
	              }
	            });

	            if (item && item.menuWindow) {
	              item.menuWindow.close();
	            }
	          }
	        }]
	      });
	    }
	  }], [{
	    key: "getSwitcherId",
	    value: function getSwitcherId(inputName, fieldName) {
	      return 'rpa-fields-controller-' + inputName + '-' + fieldName;
	    }
	  }]);
	  return FieldsController;
	}();

	exports.FieldsController = FieldsController;

}((this.BX.Rpa = this.BX.Rpa || {}),BX,BX.UI.UserFieldFactory,BX.UI.UserField,BX,BX.Event,BX.Main,BX.Rpa));
//# sourceMappingURL=fieldscontroller.bundle.js.map

(function (exports,main_core,rpa_component,rpa_fieldscontroller,ui_userfieldfactory) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var TypeComponent =
	/*#__PURE__*/
	function (_Component) {
	  babelHelpers.inherits(TypeComponent, _Component);

	  function TypeComponent() {
	    babelHelpers.classCallCheck(this, TypeComponent);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TypeComponent).apply(this, arguments));
	  }

	  babelHelpers.createClass(TypeComponent, [{
	    key: "setFieldsController",
	    value: function setFieldsController(fieldsController) {
	      if (fieldsController instanceof rpa_fieldscontroller.FieldsController) {
	        this.fieldsController = fieldsController;
	      }

	      return this;
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(TypeComponent.prototype), "init", this).call(this);

	      if (main_core.Type.isPlainObject(this.params.type)) {
	        this.addDataToSlider('type', this.params.type);
	      }
	    }
	  }, {
	    key: "prepareData",
	    value: function prepareData() {
	      var data = {
	        fields: {}
	      };
	      var fields = Array.from(this.form.querySelectorAll('[data-type="field"]'));
	      fields.forEach(function (field) {
	        if (field.name === 'id') {
	          if (main_core.Text.toInteger(field.value) > 0) {
	            data.id = field.value;
	          }
	        } else {
	          data.fields[field.name] = field.value;
	        }
	      });
	      data.fields.permissions = this.getPermissions();
	      data.fields.settings = this.getSettings();
	      var selectedIcon = TypeComponent.getSelectedIcon();

	      if (selectedIcon) {
	        data.fields.image = selectedIcon.dataset.icon;
	      }

	      return data;
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      var settings = {
	        scenarios: []
	      };
	      var nodes = Array.from(this.form.querySelectorAll('[data-type="scenario"]'));
	      nodes.forEach(function (node) {
	        if (node.checked) {
	          settings.scenarios.push(node.value);
	        }
	      });
	      return settings;
	    }
	  }, {
	    key: "getPermissionSelectors",
	    value: function getPermissionSelectors() {
	      return [{
	        action: 'ITEMS_CREATE',
	        selector: '[data-role="permission-setting-items_create"]'
	      }, {
	        action: 'MODIFY',
	        selector: '[data-role="permission-setting-modify"]'
	      }, {
	        action: 'VIEW',
	        selector: '[data-role="permission-setting-view"]'
	      }];
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(response) {
	      var _this = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(TypeComponent.prototype), "afterSave", this).call(this, response);

	      var exit = function exit() {
	        if (!_this.getSlider()) {
	          Manager.Instance.openKanban(response.type.id);
	        } else {
	          var slider = _this.getSlider();

	          if (slider) {
	            slider.close();
	          }
	        }
	      };

	      if (this.fieldsController) {
	        var fieldNames = [];
	        this.fieldsController.getFields().forEach(function (field) {
	          fieldNames.push(field.getName());
	        });
	        main_core.ajax.runAction('rpa.fields.setVisibilitySettings', {
	          data: {
	            typeId: this.params.type.typeId,
	            fields: fieldNames,
	            visibility: 'create'
	          }
	        }).then(exit).catch(exit);
	      } else {
	        exit();
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(TypeComponent.prototype), "showErrors", this).call(this, errors);
	      this.errorsContainer.parentNode.style.display = 'block';
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(TypeComponent.prototype), "hideErrors", this).call(this);
	      this.errorsContainer.parentNode.style.display = 'none';
	    }
	  }], [{
	    key: "getIconsNode",
	    value: function getIconsNode() {
	      return document.querySelector('[data-role="icon-selector"]');
	    }
	  }, {
	    key: "getIcons",
	    value: function getIcons() {
	      var iconsNode = TypeComponent.getIconsNode();

	      if (!iconsNode) {
	        return null;
	      }

	      var nodeList = iconsNode.querySelectorAll('.rpa-automation-options-item');

	      if (nodeList.length > 0) {
	        return Array.from(nodeList);
	      }

	      return null;
	    }
	  }, {
	    key: "onIconClick",
	    value: function onIconClick(icon) {
	      var icons = TypeComponent.getIcons();

	      if (!icons) {
	        return;
	      }

	      icons.forEach(function (node) {
	        node.classList.remove('rpa-automation-options-item-selected');
	      });
	      icon.classList.add('rpa-automation-options-item-selected');
	    }
	  }, {
	    key: "getSelectedIcon",
	    value: function getSelectedIcon() {
	      var iconsNode = TypeComponent.getIconsNode();

	      if (!iconsNode) {
	        return null;
	      }

	      return iconsNode.querySelector('.rpa-automation-options-item-selected');
	    }
	  }]);
	  return TypeComponent;
	}(rpa_component.Component);

	namespace.TypeComponent = TypeComponent;

}((this.window = this.window || {}),BX,BX.Rpa,BX.Rpa,BX.UI.UserFieldFactory));
//# sourceMappingURL=script.js.map

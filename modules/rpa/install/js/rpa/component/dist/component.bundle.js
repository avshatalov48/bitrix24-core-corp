/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_loader) {
	'use strict';

	var Component = /*#__PURE__*/function () {
	  function Component(form, params) {
	    babelHelpers.classCallCheck(this, Component);
	    babelHelpers.defineProperty(this, "analyticsLabel", '');
	    this.params = params;
	    this.form = form;
	    this.saveButton = document.getElementById('ui-button-panel-save');
	    this.cancelButton = document.getElementById('ui-button-panel-cancel');
	    this.deleteButton = document.getElementById('ui-button-panel-remove');
	    if (main_core.Type.isString(params.analyticsLabel)) {
	      this.analyticsLabel = params.analyticsLabel;
	    }
	    if (main_core.Type.isString(params.method)) {
	      this.method = params.method;
	    }
	    if (main_core.Type.isDomNode(params.errorsContainer)) {
	      this.errorsContainer = params.errorsContainer;
	    }
	  }
	  babelHelpers.createClass(Component, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core.Event.bind(this.saveButton, 'click', function (event) {
	        _this.save(event);
	      }, {
	        passive: false
	      });
	      if (this.deleteButton) {
	        main_core.Event.bind(this.deleteButton, 'click', function (event) {
	          _this["delete"](event);
	        });
	      }
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
	    key: "startProgress",
	    value: function startProgress() {
	      this.isProgress = true;
	      if (!this.getLoader().isShown()) {
	        this.getLoader().show(this.form);
	      }
	      this.hideErrors();
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      var _this2 = this;
	      this.isProgress = false;
	      this.getLoader().hide();
	      setTimeout(function () {
	        main_core.Dom.removeClass(_this2.saveButton, 'ui-btn-wait');
	        main_core.Dom.removeClass(_this2.closeButton, 'ui-btn-wait');
	        if (_this2.deleteButton) {
	          main_core.Dom.removeClass(_this2.deleteButton, 'ui-btn-wait');
	        }
	      }, 200);
	    }
	  }, {
	    key: "prepareData",
	    value: function prepareData() {}
	  }, {
	    key: "save",
	    value: function save(event) {
	      var _this3 = this;
	      event.preventDefault();
	      if (!this.form) {
	        return;
	      }
	      if (this.isProgress) {
	        return;
	      }
	      if (!this.method) {
	        return;
	      }
	      this.startProgress();
	      var data = this.prepareData();
	      main_core.ajax.runAction(this.method, {
	        analyticsLabel: this.analyticsLabel,
	        data: data
	      }).then(function (response) {
	        _this3.afterSave(response);
	        _this3.stopProgress();
	      })["catch"](function (response) {
	        _this3.showErrors(response.errors);
	        _this3.stopProgress();
	      });
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(response) {
	      this.addDataToSlider('response', response);
	    }
	  }, {
	    key: "getSlider",
	    value: function getSlider() {
	      if (main_core.Reflection.getClass('BX.SidePanel')) {
	        return BX.SidePanel.Instance.getSliderByWindow(window);
	      }
	      return null;
	    }
	  }, {
	    key: "addDataToSlider",
	    value: function addDataToSlider(key, data) {
	      if (main_core.Type.isString(key) && main_core.Type.isPlainObject(data)) {
	        var slider = this.getSlider();
	        if (slider) {
	          slider.data.set(key, data);
	        }
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var text = '';
	      errors.forEach(function (_ref) {
	        var message = _ref.message;
	        text += message;
	      });
	      if (main_core.Type.isDomNode(this.errorsContainer)) {
	        this.errorsContainer.innerText = text;
	      } else {
	        console.error(text);
	      }
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isDomNode(this.errorsContainer)) {
	        this.errorsContainer.innerText = '';
	      }
	    }
	  }, {
	    key: "delete",
	    value: function _delete(event) {
	      event.preventDefault();
	    }
	  }, {
	    key: "getPermissionSelectors",
	    value: function getPermissionSelectors() {
	      return [];
	    }
	  }, {
	    key: "getPermissions",
	    value: function getPermissions() {
	      var _this4 = this;
	      var permissions = [];
	      this.getPermissionSelectors().forEach(function (permission) {
	        var node = _this4.form.querySelector(permission.selector);
	        if (node) {
	          permissions = [].concat(babelHelpers.toConsumableArray(permissions), babelHelpers.toConsumableArray(_this4.getPermission(node, permission.action)));
	        }
	      });
	      if (permissions.length <= 0) {
	        permissions = false;
	      }
	      return permissions;
	    }
	  }, {
	    key: "getPermission",
	    value: function getPermission(node, action) {
	      var permissions = [];
	      var inputs = Array.from(node.querySelectorAll('input[type="hidden"]'));
	      var select = node.querySelector('select');
	      inputs.forEach(function (input) {
	        if (input.value && main_core.Type.isString(input.value) && input.value.length > 0) {
	          permissions.push({
	            accessCode: input.value,
	            permission: select.value,
	            action: action
	          });
	        }
	      });
	      return permissions;
	    }
	  }]);
	  return Component;
	}();

	exports.Component = Component;

}((this.BX.Rpa = this.BX.Rpa || {}),BX,BX));
//# sourceMappingURL=component.bundle.js.map

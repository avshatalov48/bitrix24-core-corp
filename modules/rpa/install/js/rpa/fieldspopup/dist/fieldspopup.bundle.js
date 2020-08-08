this.BX = this.BX || {};
(function (exports,main_core,main_popup) {
	'use strict';

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-fields-popup-wrapper\">\n\t\t\t\t\t\t\t<div class=\"rpa-fields-popup-inner\">", "</div>\n\t\t\t\t\t\t</div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var FieldsPopup =
	/*#__PURE__*/
	function () {
	  function FieldsPopup(id, fields, title) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, FieldsPopup);
	    this.selectedFields = new Set();
	    this.fields = new Map();

	    if (main_core.Type.isString(id)) {
	      this.title = title;
	      this.id = id;

	      if (main_core.Type.isArray(fields)) {
	        fields.forEach(function (field) {
	          _this.fields.set(field.name, field);
	        });
	        this.save();
	      }
	    }
	  }

	  babelHelpers.createClass(FieldsPopup, [{
	    key: "getPopup",
	    value: function getPopup(onSave) {
	      var popup = main_popup.PopupWindowManager.getPopupById(this.id);

	      if (!popup) {
	        popup = new BX.PopupWindow(this.id, null, {
	          titleBar: this.title,
	          zIndex: 200,
	          className: "rpa-fields-popup",
	          autoHide: false,
	          closeByEsc: false,
	          closeIcon: false,
	          content: this.getContent(),
	          width: 500,
	          overlay: true,
	          lightShadow: false,
	          buttons: this.getButtons(),
	          cacheable: false
	        });
	      }

	      if (main_core.Type.isFunction(onSave)) {
	        popup.setButtons(this.getButtons(onSave));
	      }

	      return popup;
	    }
	  }, {
	    key: "getButtons",
	    value: function getButtons(onSave) {
	      var _this2 = this;

	      return [new main_popup.PopupWindowButton({
	        text: main_core.Loc.getMessage('RPA_POPUP_SAVE_BUTTON'),
	        className: "ui-btn ui-btn-md ui-btn-primary",
	        events: {
	          click: function click() {
	            _this2.save();

	            if (main_core.Type.isFunction(onSave)) {
	              onSave(_this2.getSelectedFields());
	            }

	            _this2.getPopup().close();
	          }
	        }
	      }), new main_popup.PopupWindowButton({
	        text: main_core.Loc.getMessage('RPA_POPUP_CANCEL_BUTTON'),
	        className: "ui-btn ui-btn-md",
	        events: {
	          click: function click() {
	            if (main_core.Type.isFunction(onSave)) {
	              onSave(false);
	            }

	            _this2.getPopup().close();
	          }
	        }
	      })];
	    }
	  }, {
	    key: "getContent",
	    value: function getContent() {
	      var _this3 = this;

	      var content = '';
	      this.fields.forEach(function (field) {
	        content += _this3.renderField(field);
	      });
	      return main_core.Tag.render(_templateObject(), content);
	    }
	  }, {
	    key: "renderField",
	    value: function renderField(_ref) {
	      var title = _ref.title,
	          name = _ref.name,
	          checked = _ref.checked;
	      var checkedString = checked === true ? 'checked="checked"' : '';
	      return "\n\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t<input data-role=\"field-checkbox\" type=\"checkbox\" class=\"ui-ctl-element\" name=\"".concat(main_core.Text.encode(name), "\" value=\"y\" ").concat(checkedString, ">\n\t\t\t<div class=\"ui-ctl-label-text\">").concat(main_core.Text.encode(title), "</div>\n\t\t</label>");
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this4 = this;

	      return new Promise(function (resolve) {
	        _this4.getPopup(resolve).show();
	      });
	    }
	  }, {
	    key: "getSelectedFields",
	    value: function getSelectedFields() {
	      return this.selectedFields;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _this5 = this;

	      this.selectedFields.clear();
	      var container = this.getPopup().getContentContainer();

	      if (container) {
	        var inputs = Array.from(container.querySelectorAll('[data-role="field-checkbox"]'));
	        inputs.forEach(function (input) {
	          if (input.checked) {
	            _this5.selectedFields.add(input.name);
	          }
	        });
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      this.getPopup().close();
	    }
	  }]);
	  return FieldsPopup;
	}();

	exports.FieldsPopup = FieldsPopup;

}((this.BX.Rpa = this.BX.Rpa || {}),BX,BX.Main));
//# sourceMappingURL=fieldspopup.bundle.js.map

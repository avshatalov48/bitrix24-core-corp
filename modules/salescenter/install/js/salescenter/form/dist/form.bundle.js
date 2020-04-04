this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["", ""]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<input \n\t\t\t\tonchange=\"", "\" \n\t\t\t\tname=\"", "\"\n\t\t\t\tvalue=\"\"\n\t\t\t\tclass=\"", "\"\n\t\t\t\ttype=\"file\">"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\">", "</div>"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<div class=\"", "", "\">\n\t\t\t\t\t", "", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<div class=\"", " ", "\">\n\t\t\t\t\t<div class=\"", "\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<label class=\"", " ", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t</label>\n\t\t\t\t<span></span>\n\t\t\t\t", "\n\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<label class=\"", " ", "\">", "", "", "</label>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-ctl-after\" data-hint=\"", "\"></span>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["", ""]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["", ""]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Form =
	/*#__PURE__*/
	function () {
	  function Form(id) {
	    var _this = this;

	    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
	      config: [],
	      fields: [],
	      data: [],
	      classes: {},
	      container: null
	    };
	    babelHelpers.classCallCheck(this, Form);
	    this.id = id;
	    this.config = options.config;
	    this.fields = options.fields;
	    this.data = options.data;

	    if (options.container) {
	      this.setContainer(options.container);
	    }

	    this.classes = new Map([['sectionContainer', 'salescenter-form-settings-section'], ['sectionTitle', 'ui-title-6'], ['controlContainer', 'salescenter-control-container'], ['controlRequired', 'salescenter-control-required'], ['controlTitle', 'ui-ctl-label-text'], ['controlInner', 'ui-ctl ui-ctl-w100'], ['controlAfterIcon', 'ui-ctl-after-icon'], ['controlSelect', 'ui-ctl-dropdown ui-ctl-after-icon'], ['controlSelectIcon', 'ui-ctl-after ui-ctl-icon-angle'], ['controlFile', 'ui-ctl-file-btn ui-ctl-w33'], ['controlInput', 'ui-ctl-element'], ['controlCheckbox', 'ui-ctl-checkbox'], ['controlCheckboxLabel', 'ui-ctl-label-text']]);

	    if (main_core.Type.isPlainObject(options.classes)) {
	      this.classes.forEach(function (value, name) {
	        if (main_core.Type.isString(options.classes[name])) {
	          _this.classes[name] = options.classes[name];
	        }
	      });
	    }
	  }

	  babelHelpers.createClass(Form, [{
	    key: "render",

	    /**
	     * @param {HTMLElement|null} nodeTo
	     * @returns {HTMLElement[]}
	     */
	    value: function render() {
	      var _this2 = this;

	      var nodeTo = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var result = '';
	      this.config.forEach(function (section) {
	        result += _this2.renderSection(section);
	      });
	      var nodes = main_core.Tag.render(_templateObject(), result);

	      if (!main_core.Type.isArray(nodes)) {
	        nodes = [nodes];
	      }

	      if (main_core.Type.isDomNode(nodeTo)) {
	        nodes.forEach(function (node) {
	          nodeTo.appendChild(node);
	        });
	      }

	      return nodes;
	    }
	    /**
	     * @param field
	     * @returns {HTMLElement}
	     */

	  }, {
	    key: "renderField",
	    value: function renderField(field) {
	      var result = '';

	      if (!main_core.Type.isObject(field)) {
	        return result;
	      }

	      if (!field.html) {
	        field.html = this.renderFieldInput(field);
	      }

	      if (main_core.Type.isDomNode(field.html)) {
	        field.input = field.html;
	        field.html = field.html.innerHTML;
	      } else {
	        field.input = main_core.Tag.render(_templateObject2(), field.html);
	      }

	      var label = '';
	      var hint = '';

	      if (field.hint) {
	        hint = main_core.Tag.render(_templateObject3(), field.hint);
	      }

	      var title = '';

	      if (field.title) {
	        title = "<div class=\"".concat(this.classes.get('controlTitle'), " ").concat(field.required ? this.classes.get('controlRequired') : '', "\">").concat(field.title, "</div>");
	      }

	      if (field.html.indexOf('type="checkbox"') > 0) {
	        label = main_core.Tag.render(_templateObject4(), this.classes.get('controlInner'), this.classes.get('controlCheckbox'), field.input, field.title ? '<div class="' + this.classes.get('controlCheckboxLabel') + '">' + field.title + '</div>' : '', hint);
	      } else if (field.type === 'file') {
	        var hiddenFileInput = '';

	        if (field.addHidden === true) {
	          var hiddenFileField = {
	            name: field.name,
	            type: 'hidden',
	            value: field.value
	          };
	          hiddenFileInput = this.renderFieldInput(hiddenFileField);
	        }

	        label = main_core.Tag.render(_templateObject5(), title, this.classes.get('controlInner'), this.classes.get('controlFile'), field.input, field.label ? '<div class="ui-ctl-label-text">' + field.label + '</div>' : '', hiddenFileInput);
	      } else if (field.type === 'list' || field.html.indexOf('select') > 0) {
	        label = main_core.Tag.render(_templateObject6(), title, this.classes.get('controlSelect'), this.classes.get('controlInner'), this.classes.get('controlSelectIcon'), field.input);
	      } else {
	        label = main_core.Tag.render(_templateObject7(), title, this.classes.get('controlInner'), hint ? ' ' + this.classes.get('controlAfterIcon') : '', field.input, hint);
	      }

	      result = main_core.Tag.render(_templateObject8(), this.classes.get('controlContainer'), label);
	      return result;
	    }
	    /**
	     * @param field
	     * @returns {string}
	     */

	  }, {
	    key: "renderFieldInput",
	    value: function renderFieldInput(field) {
	      var result = '';
	      var type = field.type;

	      if (!type) {
	        type = 'text';
	      }

	      var value = '';

	      if (field.hasOwnProperty('value')) {
	        value = field.value;
	      } else if (this.data[field.name]) {
	        value = this.data[field.name];
	      }

	      var required = '';

	      if (field.required === true) {
	        required = ' required="required"';
	      }

	      if (type === 'text') {
	        result = "<input name=\"".concat(field.name, "\"\n\t\t\t\tclass=\"").concat(this.classes.get('controlInput'), "\"\n\t\t\t\tvalue=\"").concat(value, "\"").concat(required, "\n\t\t\t\ttype=\"text\">");
	      } else if (type === 'boolean') {
	        value = 'Y';
	        result = "<input type=\"checkbox\" name=\"".concat(field.name, "\"").concat(this.data[field.name] === value ? ' checked="checked"' : '').concat(field.disabled ? ' disabled="disabled"' : '').concat(required, "\n\t\t\t\tvalue=\"").concat(value, "\" class=\"").concat(this.classes.get('controlInput'), "\">");
	      } else if (type === 'list') {
	        result = "<select class=\"".concat(this.classes.get('controlInput'), "\" name=\"").concat(field.name, "\"").concat(required, ">");

	        if (field.data && main_core.Type.isArray(field.data.items)) {
	          field.data.items.forEach(function (item) {
	            result += "<option".concat(main_core.Type.isString(item.VALUE) ? ' value="' + item.VALUE + '"' : '').concat(item.SELECTED ? ' selected="selected"' : '', ">").concat(item.NAME, "</option>");
	          });
	        }

	        result += "</select>";
	      } else if (type === 'hidden') {
	        result = "<input name=\"".concat(field.name, "\"\n\t\t\t\tvalue=\"").concat(value, "\"\n\t\t\t\ttype=\"hidden\">");
	      } else if (type === 'file') {
	        var onFileChange = function onFileChange(_ref) {
	          var target = _ref.target;
	          var value = target.value.split(/(\\|\/)/g).pop();
	          target.parentNode.nextSibling.innerText = value;
	        };

	        result = main_core.Tag.render(_templateObject9(), onFileChange, field.name, this.classes.get('controlInput'));
	      }

	      return result;
	    }
	    /**
	     * @param section
	     * @returns {HTMLElement}
	     */

	  }, {
	    key: "renderSection",
	    value: function renderSection(section) {
	      var _this3 = this;

	      var result = null;

	      if (!main_core.Type.isObject(section)) {
	        return result;
	      }

	      if (!main_core.Type.isArray(section.elements)) {
	        section.elements = [];
	      }

	      var sectionId = '';

	      if (section.name) {
	        sectionId = ' id="' + this.id + '-' + section.name + '"';
	      }

	      result = "<div".concat(sectionId, " class=\"").concat(this.classes.get('sectionContainer'), "\">");

	      if (section.title) {
	        result += "<div class=\"".concat(this.classes.get('sectionTitle'), "\">").concat(section.title, "</div><hr class=\"ui-hr ui-mb-15\">");
	      }

	      result += "</div>";
	      result = main_core.Tag.render(_templateObject10(), result);
	      section.elements.forEach(function (element) {
	        if (main_core.Type.isObject(element) && element.name) {
	          var field = Form.getByName(_this3.fields, element.name);

	          if (field) {
	            result.appendChild(_this3.renderField(field));
	          }
	        }
	      });
	      return result;
	    }
	    /**
	     * @param container
	     */

	  }, {
	    key: "setContainer",
	    value: function setContainer(container) {
	      if (main_core.Type.isDomNode(container)) {
	        this.container = container;
	      }
	    }
	    /**
	     * @returns {HTMLElement}
	     */

	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var container = this.container;

	      if (!container) {
	        container = document;
	      }

	      return container;
	    }
	    /**
	     * @param field
	     * @returns {Element | null}
	     */

	  }, {
	    key: "getFieldInput",
	    value: function getFieldInput(field) {
	      if (!field.input) {
	        var container = this.getContainer();
	        field.input = container.querySelector('[name="' + field.name + '"]');
	      }

	      return field.input;
	    }
	    /**
	     * @returns {Object}
	     */

	  }, {
	    key: "getData",
	    value: function getData() {
	      var _this4 = this;

	      var result = {};
	      var container = this.getContainer();

	      if (container.nodeName === 'FORM') {
	        return new FormData(container);
	      }

	      this.fields.forEach(function (field) {
	        var input = _this4.getFieldInput(field);

	        if (main_core.Type.isDomNode(input)) {
	          if (input.getAttribute('type') === 'checkbox') {
	            if (input.checked) {
	              result[field.name] = input.value;
	            }
	          } else {
	            result[field.name] = input.value;
	          }
	        }
	      });
	      return result;
	    }
	  }], [{
	    key: "getByName",
	    value: function getByName(collection, name) {
	      var items = [];

	      if (main_core.Type.isArray(collection) && main_core.Type.isString(name)) {
	        items = collection.filter(function (item) {
	          return item.name === name;
	        });

	        if (items.length > 0) {
	          return items[0];
	        }
	      }

	      return null;
	    }
	  }]);
	  return Form;
	}();

	exports.Form = Form;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX));
//# sourceMappingURL=form.bundle.js.map

this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_sidepanel_layout) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _data = /*#__PURE__*/new WeakMap();

	var _container = /*#__PURE__*/new WeakMap();

	var _render = /*#__PURE__*/new WeakSet();

	var Captcha = /*#__PURE__*/function () {
	  function Captcha() {
	    babelHelpers.classCallCheck(this, Captcha);

	    _classPrivateMethodInitSpec(this, _render);

	    _classPrivateFieldInitSpec(this, _data, {
	      writable: true,
	      value: {
	        key: null,
	        secret: null,
	        canChange: false,
	        hasDefaults: false
	      }
	    });

	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: void 0
	    });
	  }

	  babelHelpers.createClass(Captcha, [{
	    key: "hasKeys",
	    value: function hasKeys() {
	      var data = babelHelpers.classPrivateFieldGet(this, _data);
	      return data.hasDefaults || data.key && data.secret;
	    }
	  }, {
	    key: "canChange",
	    value: function canChange() {
	      return babelHelpers.classPrivateFieldGet(this, _data).canChange;
	    }
	  }, {
	    key: "load",
	    value: function load() {
	      var _this = this;

	      return main_core.ajax.runAction('crm.form.getCaptcha', {
	        json: {}
	      }).then(function (response) {
	        babelHelpers.classPrivateFieldSet(_this, _data, response.data);
	        return _classPrivateMethodGet(_this, _render, _render2).call(_this);
	      });
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _this2 = this;

	      var keyNode = babelHelpers.classPrivateFieldGet(this, _container).querySelector('input[name="key"]');
	      var secretNode = babelHelpers.classPrivateFieldGet(this, _container).querySelector('input[name="secret"]');
	      var key = keyNode.value || '';
	      var secret = secretNode.value || '';
	      keyNode.parentElement.classList.remove('ui-ctl-danger');
	      secretNode.parentElement.classList.remove('ui-ctl-danger');

	      if (main_core.Type.isStringFilled(key) !== main_core.Type.isStringFilled(secret)) {
	        if (!key) {
	          keyNode.parentElement.classList.add('ui-ctl-danger');
	        }

	        if (!secret) {
	          secretNode.parentElement.classList.add('ui-ctl-danger');
	        }

	        return Promise.reject();
	      }

	      return main_core.ajax.runAction('crm.form.setCaptcha', {
	        json: {
	          key: key,
	          secret: secret
	        }
	      }).then(function (response) {
	        babelHelpers.classPrivateFieldSet(_this2, _data, response.data);
	        return babelHelpers.classPrivateFieldGet(_this2, _data);
	      });
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _data);
	    }
	  }], [{
	    key: "open",
	    value: function open() {
	      var resolver;
	      var promise = new Promise(function (resolve) {
	        resolver = resolve;
	      });
	      var instance = new Captcha();
	      BX.SidePanel.Instance.open("crm.webform:captcha", {
	        width: 700,
	        cacheable: false,
	        events: {
	          onCloseComplete: function onCloseComplete() {
	            resolver(_objectSpread({}, instance.getValue()));
	          }
	        },
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['crm.form.captcha', 'ui.forms', 'ui.sidepanel-content'],
	            title: main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_TITLE'),
	            design: {
	              section: false
	            },
	            content: function content() {
	              return instance.load();
	            },
	            buttons: function buttons(_ref) {
	              var SaveButton = _ref.SaveButton,
	                  closeButton = _ref.closeButton;
	              return [new SaveButton({
	                onclick: function onclick(btn) {
	                  if (!instance.canChange()) {
	                    btn.setDisabled(true);
	                    BX.UI.Notification.Center.notify({
	                      content: main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_ACCESS_DENIED')
	                    });
	                    return;
	                  }

	                  btn.setWaiting(true);
	                  instance.save().then(function () {
	                    btn.setWaiting(false);
	                    BX.SidePanel.Instance.close();
	                  })["catch"](function () {
	                    btn.setWaiting(false);
	                  });
	                }
	              }), closeButton];
	            }
	          });
	        }
	      });
	      return promise;
	    }
	  }]);
	  return Captcha;
	}();

	function _render2() {
	  var key = main_core.Tag.safe(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["", ""])), babelHelpers.classPrivateFieldGet(this, _data).key);
	  var secret = main_core.Tag.safe(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["", ""])), babelHelpers.classPrivateFieldGet(this, _data).secret);
	  babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\t\t\t\t\n\t\t\t<div>\n\t\t\t\t<div class=\"ui-slider-section\" ", ">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-alert ui-alert-success\">\n\t\t\t\t\t\t\t<span class=\"ui-alert-message\">", "</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div class=\"ui-slider-section\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">", "</div>\n\t\t\t\t\t\t<p class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<br>\n\t\t\t\t\t\t\t<a href=\"https://www.google.com/recaptcha/about/\" target=\"_blank\">", "</a>\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div>\n\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">Key</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\t\t\tname=\"key\"\n\t\t\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\tonfocus=\"this.parentElement.classList.remove('ui-ctl-danger')\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-form-row\" style=\"margin: 20px 0 0;\">\n\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">Secret</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\t\t\tname=\"secret\"\n\t\t\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\tonfocus=\"this.parentElement.classList.remove('ui-ctl-danger')\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _data).hasDefaults ? '' : 'hidden', main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_STD_TITLE'), main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_STD_TEXT'), main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_TITLE'), main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_TEXT'), main_core.Loc.getMessage('CRM_FORM_CAPTCHA_JS_CUSTOM_HOWTO'), key, secret));
	  return babelHelpers.classPrivateFieldGet(this, _container);
	}

	exports.Captcha = Captcha;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX,BX.UI.SidePanel));
//# sourceMappingURL=captcha.bundle.js.map

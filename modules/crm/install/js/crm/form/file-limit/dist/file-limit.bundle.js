this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_sidepanel_layout,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var _data = /*#__PURE__*/new WeakMap();
	var _ui = /*#__PURE__*/new WeakMap();
	var _render = /*#__PURE__*/new WeakSet();
	var _createLimitPercentageBlock = /*#__PURE__*/new WeakSet();
	var _getLimitPercentageText = /*#__PURE__*/new WeakSet();
	var FileLimit = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FileLimit, _EventEmitter);
	  function FileLimit() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, FileLimit);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(FileLimit)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getLimitPercentageText);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _createLimitPercentageBlock);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _render);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _data, {
	      writable: true,
	      value: {
	        limitMb: undefined,
	        currentBytes: null,
	        canChange: null
	      }
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _ui, {
	      writable: true,
	      value: {
	        container: HTMLElement = null,
	        limit: {
	          block: HTMLElement = null,
	          input: HTMLInputElement = null
	        },
	        percentage: {
	          block: HTMLElement = null
	        }
	      }
	    });
	    return _this;
	  }
	  babelHelpers.createClass(FileLimit, [{
	    key: "open",
	    value: function open() {
	      var resolver;
	      var promise = new Promise(function (resolve) {
	        resolver = resolve;
	      });
	      var instance = FileLimit.instance();
	      BX.SidePanel.Instance.open("crm.webform:file-limit", {
	        width: 700,
	        cacheable: false,
	        events: {
	          onCloseComplete: function onCloseComplete() {
	            resolver(_objectSpread({}, instance.getValue()));
	          }
	        },
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['crm.form.file-limit', 'ui.forms', 'ui.sidepanel-content'],
	            title: main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_TITLE'),
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
	                      content: main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_ACCESS_DENIED')
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
	  }, {
	    key: "canChange",
	    value: function canChange() {
	      return babelHelpers.classPrivateFieldGet(this, _data).canChange;
	    }
	  }, {
	    key: "load",
	    value: function load() {
	      var _this2 = this;
	      return main_core.ajax.runAction('crm.form.getFileLimit', {
	        json: {}
	      }).then(function (response) {
	        babelHelpers.classPrivateFieldSet(_this2, _data, response.data);
	        return _classPrivateMethodGet(_this2, _render, _render2).call(_this2);
	      });
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _babelHelpers$classPr,
	        _this3 = this;
	      var limitMb = babelHelpers.classPrivateFieldGet(this, _ui).limit.input.value;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _ui)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.limit.block.classList.remove('ui-ctl-danger');
	      if (main_core.Type.isInteger(limitMb) && limitMb <= 0 || main_core.Type.isStringFilled(limitMb && !main_core.Type.isInteger(limitMb))) {
	        babelHelpers.classPrivateFieldGet(this, _ui).limit.block.classList.add('ui-ctl-danger');
	        return Promise.reject();
	      }
	      limitMb = main_core.Type.isStringFilled(limitMb) ? Number(limitMb) : null;
	      return main_core.ajax.runAction('crm.form.setFileLimit', {
	        json: {
	          limitMb: limitMb
	        }
	      }).then(function (response) {
	        babelHelpers.classPrivateFieldSet(_this3, _data, response.data);
	        _this3.emit('onSuccessLimitChanged', {
	          limit: babelHelpers.classPrivateFieldGet(_this3, _data).limitMb
	        });
	        return babelHelpers.classPrivateFieldGet(_this3, _data);
	      });
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _data);
	    }
	  }], [{
	    key: "instance",
	    value: function instance() {
	      if (!_classStaticPrivateFieldSpecGet(FileLimit, FileLimit, _instance)) {
	        _classStaticPrivateFieldSpecSet(FileLimit, FileLimit, _instance, new FileLimit());
	      }
	      return _classStaticPrivateFieldSpecGet(FileLimit, FileLimit, _instance);
	    }
	  }]);
	  return FileLimit;
	}(main_core_events.EventEmitter);
	function _render2() {
	  var limitMb = babelHelpers.classPrivateFieldGet(this, _data).limitMb;
	  babelHelpers.classPrivateFieldGet(this, _ui).percentage.block = _classPrivateMethodGet(this, _createLimitPercentageBlock, _createLimitPercentageBlock2).call(this);
	  babelHelpers.classPrivateFieldGet(this, _ui).limit.input = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input \n\t\t\t\t\ttype=\"number\" \n\t\t\t\t\tname=\"limit\"\n\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\tmin=\"1\"\n\t\t\t\t\tmaxlength=\"5\"\n\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\tonfocus=\"this.parentElement.classList.remove('ui-ctl-danger')\"\n\t\t\t\t>\n\t\t"])), limitMb);
	  babelHelpers.classPrivateFieldGet(this, _ui).limit.block = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _ui).limit.input);
	  babelHelpers.classPrivateFieldGet(this, _ui).container = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"ui-slider-section\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">", "</div>\n\t\t\t\t\t\t<p class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-slider-section\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-4\">", "</div>\n\t\t\t\t\t\t<p class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div>\n\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_DESCRIPTION_TITLE'), main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_DESCRIPTION_TEXT'), babelHelpers.classPrivateFieldGet(this, _ui).percentage.block, main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_SETTING_TITLE'), main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_SETTING_DISABLE_HINT'), main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_LIMIT_SETTING_TITLE'), babelHelpers.classPrivateFieldGet(this, _ui).limit.block);
	  return babelHelpers.classPrivateFieldGet(this, _ui).container;
	}
	function _createLimitPercentageBlock2() {
	  var percentage = main_core.Type.isInteger(babelHelpers.classPrivateFieldGet(this, _data).limitMb) ? Math.ceil(babelHelpers.classPrivateFieldGet(this, _data).currentBytes / (babelHelpers.classPrivateFieldGet(this, _data).limitMb * 1024 * 1024) * 100) : 0;
	  var percentageBlock = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-alert\"></div>\n\t\t"])));
	  if (main_core.Type.isInteger(babelHelpers.classPrivateFieldGet(this, _data).limitMb)) {
	    var colorAlertStyle = 'ui-alert-success';
	    if (percentage >= 95) {
	      colorAlertStyle = 'ui-alert-danger';
	    } else if (percentage >= 85) {
	      colorAlertStyle = 'ui-alert-warning';
	    }
	    BX.addClass(percentageBlock, colorAlertStyle);
	    percentageBlock.innerText = _classPrivateMethodGet(this, _getLimitPercentageText, _getLimitPercentageText2).call(this, Math.min(percentage, 100));
	  } else if (main_core.Type.isNull(babelHelpers.classPrivateFieldGet(this, _data).limitMb)) {
	    BX.addClass(percentageBlock, 'ui-alert-default');
	    percentageBlock.innerText = main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_LIMIT_DISABLED');
	  } else {
	    BX.Hide(percentageBlock);
	  }
	  return percentageBlock;
	}
	function _getLimitPercentageText2(percentage) {
	  var percentageText = main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_CURRENT_LIMIT_PERCENTAGE_TEXT').replace('%percentage%', percentage);
	  if (percentage >= 85) {
	    percentageText = main_core.Loc.getMessage('CRM_FORM_FILE_LIMIT_JS_CURRENT_LIMIT_USERS_MIGHT_TROUBLE').replace('%percentage%', percentage);
	  }
	  return percentageText;
	}
	var _instance = {
	  writable: true,
	  value: null
	};

	exports.FileLimit = FileLimit;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX,BX.UI.SidePanel,BX.Event));
//# sourceMappingURL=file-limit.bundle.js.map

this.BX = this.BX || {};
(function (exports,main_core,main_core_events,landing_ui_form_styleform,landing_loc,landing_ui_field_colorpickerfield,landing_backend,landing_env) {
	'use strict';

	var themesMap = new Map();
	themesMap.set('business-light', {
	  theme: 'business-light',
	  dark: false,
	  style: '',
	  color: {
	    primary: '#0f58d0ff',
	    primaryText: '#ffffffff',
	    background: '#ffffffff',
	    text: '#000000ff',
	    fieldBackground: '#00000011',
	    fieldFocusBackground: '#ffffffff',
	    fieldBorder: '#00000016'
	  },
	  shadow: true,
	  font: {
	    uri: '',
	    family: ''
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('business-dark', {
	  theme: 'business-dark',
	  dark: true,
	  style: '',
	  color: {
	    primary: '#0f58d0ff',
	    primaryText: '#ffffffff',
	    background: '#282d30ff',
	    text: '#ffffffff',
	    fieldBackground: '#ffffff11',
	    fieldFocusBackground: '#00000028',
	    fieldBorder: '#ffffff16'
	  },
	  shadow: true,
	  font: {
	    uri: '',
	    family: ''
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('modern-light', {
	  theme: 'modern-light',
	  dark: false,
	  style: 'modern',
	  color: {
	    primary: '#ffd110ff',
	    primaryText: '#000000ff',
	    background: '#ffffffff',
	    text: '#000000ff',
	    fieldBackground: '#00000000',
	    fieldFocusBackground: '#00000000',
	    fieldBorder: '#00000011'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap&subset=cyrillic',
	    family: 'Open Sans'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('modern-dark', {
	  theme: 'modern-dark',
	  dark: true,
	  style: 'modern',
	  color: {
	    primary: '#ffd110ff',
	    primaryText: '#000000ff',
	    background: '#282d30ff',
	    text: '#ffffffff',
	    fieldBackground: '#00000000',
	    fieldFocusBackground: '#00000000',
	    fieldBorder: '#ffffff11'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap&subset=cyrillic',
	    family: 'Open Sans'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('classic-light', {
	  theme: 'classic-light',
	  dark: false,
	  style: '',
	  color: {
	    primary: '#000000ff',
	    primaryText: '#ffffffff',
	    background: '#ffffffff',
	    text: '#000000ff',
	    fieldBackground: '#00000011',
	    fieldFocusBackground: '#0000000a',
	    fieldBorder: '#00000011'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&display=swap&subset=cyrillic',
	    family: 'PT Serif'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('classic-dark', {
	  theme: 'classic-dark',
	  dark: true,
	  style: '',
	  color: {
	    primary: '#ffffffff',
	    primaryText: '#000000ff',
	    background: '#000000ff',
	    text: '#ffffffff',
	    fieldBackground: '#ffffff11',
	    fieldFocusBackground: '#ffffff0a',
	    fieldBorder: '#ffffff11'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&display=swap&subset=cyrillic',
	    family: 'PT Serif'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('fun-light', {
	  theme: 'fun-light',
	  dark: false,
	  style: '',
	  color: {
	    primary: '#f09b22ff',
	    primaryText: '#000000ff',
	    background: '#ffffffff',
	    text: '#000000ff',
	    fieldBackground: '#f09b2211',
	    fieldFocusBackground: '#0000000a',
	    fieldBorder: '#00000011'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=Pangolin&display=swap&subset=cyrillic',
	    family: 'Pangolin'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('fun-dark', {
	  theme: 'fun-dark',
	  dark: true,
	  style: '',
	  color: {
	    primary: '#f09b22ff',
	    primaryText: '#000000ff',
	    background: '#221400ff',
	    text: '#ffffffff',
	    fieldBackground: '#f09b2211',
	    fieldFocusBackground: '#ffffff0a',
	    fieldBorder: '#f09b220a'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=Pangolin&display=swap&subset=cyrillic',
	    family: 'Pangolin'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('pixel-light', {
	  theme: 'pixel-light',
	  dark: true,
	  style: '',
	  color: {
	    primary: '#00a74cff',
	    primaryText: '#ffffffff',
	    background: '#282d30ff',
	    text: '#90ee90ff',
	    fieldBackground: '#ffffff11',
	    fieldFocusBackground: '#00000028',
	    fieldBorder: '#ffffff16'
	  },
	  shadow: true,
	  font: {
	    uri: 'https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap&subset=cyrillic',
	    family: 'Press Start 2P'
	  },
	  border: {
	    left: false,
	    top: false,
	    bottom: true,
	    right: false
	  }
	});
	themesMap.set('pixel-dark', babelHelpers.objectSpread({}, themesMap.get('pixel-light'), {
	  theme: 'pixel-dark'
	}));

	/**
	 * @memberOf BX.Landing
	 */

	var FormStyleAdapter = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FormStyleAdapter, _EventEmitter);

	  function FormStyleAdapter(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, FormStyleAdapter);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FormStyleAdapter).call(this));

	    _this.setEventNamespace('BX.Landing.FormStyleAdapter');

	    _this.options = babelHelpers.objectSpread({}, options);
	    _this.cache = new main_core.Cache.MemoryCache();
	    _this.onDebouncedFormChange = main_core.Runtime.debounce(_this.onDebouncedFormChange, 500);
	    return _this;
	  }

	  babelHelpers.createClass(FormStyleAdapter, [{
	    key: "setFormOptions",
	    value: function setFormOptions(options) {
	      this.cache.set('formOptions', babelHelpers.objectSpread({}, options));
	    }
	  }, {
	    key: "getFormOptions",
	    value: function getFormOptions() {
	      return this.cache.get('formOptions');
	    }
	  }, {
	    key: "load",
	    value: function load() {
	      var _this2 = this;

	      if (main_core.Text.capitalize(landing_env.Env.getInstance().getOptions().params.type) === 'SMN') {
	        this.setFormOptions({
	          data: {
	            design: main_core.Runtime.clone(this.getCrmForm().design)
	          }
	        });
	        return Promise.resolve(this);
	      }

	      return main_core.Runtime.loadExtension('crm.form.client').then(function (_ref) {
	        var FormClient = _ref.FormClient;

	        if (FormClient) {
	          return FormClient.getInstance().getOptions(_this2.options.formId).then(function (result) {
	            _this2.setFormOptions(main_core.Runtime.merge(main_core.Runtime.clone(result), {
	              data: {
	                design: main_core.Runtime.clone(_this2.getCrmForm().design)
	              }
	            }));

	            return _this2;
	          });
	        }

	        return null;
	      });
	    }
	  }, {
	    key: "getThemeField",
	    value: function getThemeField() {
	      var _this3 = this;

	      return this.cache.remember('themeField', function () {
	        var theme = _this3.getFormOptions().data.design.theme;

	        return new BX.Landing.UI.Field.Dropdown({
	          selector: 'theme',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_TITLE'),
	          content: main_core.Type.isString(theme) ? theme.split('-')[0] : '',
	          onChange: _this3.onThemeChange.bind(_this3),
	          items: [{
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_ITEM_BUSINESS'),
	            value: 'business'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_ITEM_MODERN'),
	            value: 'modern'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_ITEM_CLASSIC'),
	            value: 'classic'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_ITEM_FUN'),
	            value: 'fun'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_THEME_FIELD_ITEM_PIXEL'),
	            value: 'pixel'
	          }]
	        });
	      });
	    }
	  }, {
	    key: "getDarkField",
	    value: function getDarkField() {
	      var _this4 = this;

	      return this.cache.remember('darkField', function () {
	        var theme = _this4.getFormOptions().data.design.theme;

	        return new BX.Landing.UI.Field.Dropdown({
	          selector: 'dark',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_DARK_FIELD_TITLE'),
	          content: main_core.Type.isString(theme) ? theme.split('-')[1] : '',
	          onChange: _this4.onThemeChange.bind(_this4),
	          items: [{
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_DARK_FIELD_ITEM_LIGHT'),
	            value: 'light'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_DARK_FIELD_ITEM_DARK'),
	            value: 'dark'
	          }]
	        });
	      });
	    }
	  }, {
	    key: "onThemeChange",
	    value: function onThemeChange() {
	      var themeId = this.getStyleForm().serialize().theme;
	      var theme = themesMap.get(themeId);

	      if (theme) {
	        if (main_core.Type.isPlainObject(theme.color)) {
	          this.getPrimaryColorField().setValue(theme.color.primary, true);
	          this.getPrimaryTextColorField().setValue(theme.color.primaryText, true);
	          this.getBackgroundColorField().setValue(theme.color.background);
	          this.getTextColorField().setValue(theme.color.text, true);
	          this.getFieldBackgroundColorField().setValue(theme.color.fieldBackground, true);
	          this.getFieldFocusBackgroundColorField().setValue(theme.color.fieldFocusBackground, true);
	          this.getFieldBorderColorField().setValue(theme.color.fieldBorder);
	        }

	        this.getStyleField().setValue(theme.style);

	        if (main_core.Type.isBoolean(theme.shadow)) {
	          this.getShadowField().setValue(theme.shadow);
	        }

	        if (main_core.Type.isPlainObject(theme.font)) {
	          var font = babelHelpers.objectSpread({}, theme.font);

	          if (!main_core.Type.isStringFilled(font.family)) {
	            font.family = landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FONT_DEFAULT');
	          }

	          this.getFontField().setValue(font);
	        }

	        if (main_core.Type.isPlainObject(theme.border)) {
	          var borders = Object.entries(theme.border).reduce(function (acc, _ref2) {
	            var _ref3 = babelHelpers.slicedToArray(_ref2, 2),
	                key = _ref3[0],
	                value = _ref3[1];

	            if (value) {
	              acc.push(key);
	            }

	            return acc;
	          }, []);
	          this.getBorderField().setValue(borders);
	        }
	      }
	    }
	  }, {
	    key: "getShadowField",
	    value: function getShadowField() {
	      var _this5 = this;

	      return this.cache.remember('shadow', function () {
	        return new BX.Landing.UI.Field.Dropdown({
	          selector: 'shadow',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_SHADOW'),
	          content: _this5.getFormOptions().data.design.shadow,
	          items: [{
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_SHADOW_USE'),
	            value: true
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_SHADOW_NOT_USE'),
	            value: false
	          }]
	        });
	      });
	    }
	  }, {
	    key: "getStyleField",
	    value: function getStyleField() {
	      var _this6 = this;

	      return this.cache.remember('styleField', function () {
	        return new BX.Landing.UI.Field.Dropdown({
	          selector: 'style',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_STYLE_FIELD_TITLE'),
	          content: _this6.getFormOptions().data.design.style,
	          items: [{
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_STYLE_FIELD_ITEM_STANDARD'),
	            value: ''
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_STYLE_FIELD_ITEM_MODERN'),
	            value: 'modern'
	          }]
	        });
	      });
	    }
	  }, {
	    key: "getPrimaryColorField",
	    value: function getPrimaryColorField() {
	      var _this7 = this;

	      return this.cache.remember('primaryColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'primary',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_PRIMARY_COLOR'),
	          value: _this7.getFormOptions().data.design.color.primary
	        });
	      });
	    }
	  }, {
	    key: "getPrimaryTextColorField",
	    value: function getPrimaryTextColorField() {
	      var _this8 = this;

	      return this.cache.remember('primaryTextColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'primaryText',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_PRIMARY_TEXT_COLOR'),
	          value: _this8.getFormOptions().data.design.color.primaryText
	        });
	      });
	    }
	  }, {
	    key: "getBackgroundColorField",
	    value: function getBackgroundColorField() {
	      var _this9 = this;

	      return this.cache.remember('backgroundColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'background',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BACKGROUND_COLOR'),
	          value: _this9.getFormOptions().data.design.color.background
	        });
	      });
	    }
	  }, {
	    key: "getTextColorField",
	    value: function getTextColorField() {
	      var _this10 = this;

	      return this.cache.remember('textColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'text',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_TEXT_COLOR'),
	          value: _this10.getFormOptions().data.design.color.text
	        });
	      });
	    }
	  }, {
	    key: "getFieldBackgroundColorField",
	    value: function getFieldBackgroundColorField() {
	      var _this11 = this;

	      return this.cache.remember('fieldBackgroundColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'fieldBackground',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FIELD_BACKGROUND_COLOR'),
	          value: _this11.getFormOptions().data.design.color.fieldBackground
	        });
	      });
	    }
	  }, {
	    key: "getFieldFocusBackgroundColorField",
	    value: function getFieldFocusBackgroundColorField() {
	      var _this12 = this;

	      return this.cache.remember('fieldFocusBackgroundColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'fieldFocusBackground',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FIELD_FOCUS_BACKGROUND_COLOR'),
	          value: _this12.getFormOptions().data.design.color.fieldFocusBackground
	        });
	      });
	    }
	  }, {
	    key: "getFieldBorderColorField",
	    value: function getFieldBorderColorField() {
	      var _this13 = this;

	      return this.cache.remember('fieldBorderColorField', function () {
	        return new landing_ui_field_colorpickerfield.ColorPickerField({
	          selector: 'fieldBorder',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FIELD_BORDER_COLOR'),
	          value: _this13.getFormOptions().data.design.color.fieldBorder
	        });
	      });
	    }
	  }, {
	    key: "getFontField",
	    value: function getFontField() {
	      var _this14 = this;

	      return this.cache.remember('fontField', function () {
	        var value = babelHelpers.objectSpread({}, _this14.getFormOptions().data.design.font);

	        if (!main_core.Type.isStringFilled(value.family)) {
	          value.family = landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FONT_DEFAULT');
	        }

	        return new BX.Landing.UI.Field.Font({
	          selector: 'font',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FONT'),
	          headlessMode: true,
	          value: value
	        });
	      });
	    }
	  }, {
	    key: "getBorderField",
	    value: function getBorderField() {
	      var _this15 = this;

	      return this.cache.remember('borderField', function () {
	        return new BX.Landing.UI.Field.Checkbox({
	          selector: 'border',
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BORDER'),
	          value: function () {
	            var border = _this15.getFormOptions().data.design.border;

	            return Object.entries(border).reduce(function (acc, _ref4) {
	              var _ref5 = babelHelpers.slicedToArray(_ref4, 2),
	                  key = _ref5[0],
	                  value = _ref5[1];

	              if (value) {
	                acc.push(key);
	              }

	              return acc;
	            }, []);
	          }(),
	          items: [{
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BORDER_LEFT'),
	            value: 'left'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BORDER_RIGHT'),
	            value: 'right'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BORDER_TOP'),
	            value: 'top'
	          }, {
	            name: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_BORDER_BOTTOM'),
	            value: 'bottom'
	          }]
	        });
	      });
	    }
	  }, {
	    key: "getStyleForm",
	    value: function getStyleForm() {
	      var _this16 = this;

	      return this.cache.remember('styleForm', function () {
	        return new landing_ui_form_styleform.StyleForm({
	          title: landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FORM_TITLE'),
	          fields: [_this16.getThemeField(), _this16.getDarkField(), _this16.getStyleField(), _this16.getShadowField(), _this16.getPrimaryColorField(), _this16.getPrimaryTextColorField(), _this16.getBackgroundColorField(), _this16.getTextColorField(), _this16.getFieldBackgroundColorField(), _this16.getFieldFocusBackgroundColorField(), _this16.getFieldBorderColorField(), _this16.getFontField(), _this16.getBorderField()],
	          onChange: main_core.Runtime.throttle(_this16.onFormChange.bind(_this16), 16),
	          serializeModifier: function serializeModifier(value) {
	            value.theme = "".concat(value.theme, "-").concat(value.dark);
	            value.dark = value.dark === 'dark';
	            value.shadow = main_core.Text.toBoolean(value.shadow);
	            value.color = {
	              primary: value.primary,
	              primaryText: value.primaryText,
	              text: value.text,
	              background: value.background,
	              fieldBackground: value.fieldBackground,
	              fieldFocusBackground: value.fieldFocusBackground,
	              fieldBorder: value.fieldBorder
	            };
	            value.border = {
	              left: value.border.includes('left'),
	              right: value.border.includes('right'),
	              top: value.border.includes('top'),
	              bottom: value.border.includes('bottom')
	            };

	            if (value.font.family === landing_loc.Loc.getMessage('LANDING_FORM_STYLE_ADAPTER_FONT_DEFAULT')) {
	              value.font.family = '';
	              value.font.uri = '';
	            }

	            delete value.primary;
	            delete value.primaryText;
	            delete value.text;
	            delete value.background;
	            delete value.fieldBackground;
	            delete value.fieldFocusBackground;
	            delete value.fieldBorder;
	            return value;
	          }
	        });
	      });
	    }
	  }, {
	    key: "getCrmForm",
	    value: function getCrmForm() {
	      var formApp = main_core.Reflection.getClass('b24form.App');

	      if (formApp) {
	        if (this.options.instanceId) {
	          return formApp.get(this.options.instanceId);
	        }

	        return formApp.list()[0];
	      }

	      return null;
	    }
	  }, {
	    key: "onFormChange",
	    value: function onFormChange(event) {
	      var currentFormOptions = this.getFormOptions();
	      var designOptions = {
	        data: {
	          design: event.getTarget().serialize()
	        }
	      };
	      var mergedOptions = main_core.Runtime.merge(currentFormOptions, designOptions);
	      this.setFormOptions(mergedOptions);
	      this.getCrmForm().adjust(mergedOptions.data);
	      this.onDebouncedFormChange();
	    } // eslint-disable-next-line class-methods-use-this

	  }, {
	    key: "isCrmFormPage",
	    value: function isCrmFormPage() {
	      return landing_env.Env.getInstance().getOptions().specialType === 'crm_forms';
	    }
	  }, {
	    key: "saveFormDesign",
	    value: function saveFormDesign() {
	      var _this17 = this;

	      return main_core.Runtime.loadExtension('crm.form.client').then(function (_ref6) {
	        var FormClient = _ref6.FormClient;

	        if (FormClient) {
	          var formClient = FormClient.getInstance();

	          var formOptions = _this17.getFormOptions();

	          formClient.resetCache(formOptions.id);
	          return formClient.saveOptions(formOptions);
	        }

	        return null;
	      });
	    }
	  }, {
	    key: "saveBlockDesign",
	    value: function saveBlockDesign() {
	      var _this18 = this;

	      var currentBlock = this.options.currentBlock;
	      var design = this.getFormOptions().data.design;
	      var formNode = currentBlock.node.querySelector('.bitrix24forms');
	      main_core.Dom.attr(formNode, {
	        'data-b24form-design': design,
	        'data-b24form-use-style': 'Y'
	      });
	      main_core.Runtime.loadExtension('crm.form.client').then(function (_ref7) {
	        var FormClient = _ref7.FormClient;

	        if (FormClient) {
	          var formClient = FormClient.getInstance();

	          var formOptions = _this18.getFormOptions();

	          formClient.resetCache(formOptions.id);
	        }
	      });
	      landing_backend.Backend.getInstance().action('Landing\\Block::updateNodes', {
	        block: currentBlock.id,
	        data: {
	          '.bitrix24forms': {
	            attrs: {
	              'data-b24form-design': JSON.stringify(design),
	              'data-b24form-use-style': 'Y'
	            }
	          }
	        },
	        lid: currentBlock.lid,
	        siteId: currentBlock.siteId
	      }, {
	        code: currentBlock.manifest.code
	      });
	    }
	  }, {
	    key: "onDebouncedFormChange",
	    value: function onDebouncedFormChange() {
	      var _this19 = this;

	      if (this.isCrmFormPage()) {
	        main_core.Runtime.loadExtension('landing.ui.panel.formsettingspanel').then(function (_ref8) {
	          var FormSettingsPanel = _ref8.FormSettingsPanel;
	          var formSettingsPanel = FormSettingsPanel.getInstance();
	          formSettingsPanel.setCurrentBlock(_this19.options.currentBlock);
	          void _this19.saveFormDesign();

	          if (formSettingsPanel.useBlockDesign()) {
	            formSettingsPanel.disableUseBlockDesign();
	          }
	        });
	      } else {
	        this.saveBlockDesign();
	      }
	    }
	  }]);
	  return FormStyleAdapter;
	}(main_core_events.EventEmitter);

	exports.FormStyleAdapter = FormStyleAdapter;

}((this.BX.Landing = this.BX.Landing || {}),BX,BX.Event,BX.Landing.UI.Form,BX.Landing,BX.Landing.Ui.Field,BX.Landing,BX.Landing));
//# sourceMappingURL=formstyleadapter.bundle.js.map

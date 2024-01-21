/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_sidepanel,ui_sidepanel_layout,ui_buttons) {
	'use strict';

	var Slider = /*#__PURE__*/function () {
	  function Slider(options) {
	    babelHelpers.classCallCheck(this, Slider);
	    babelHelpers.defineProperty(this, "DEFAULT_OPTIONS", {
	      title: main_core.Loc.getMessage('CRM_COMMON_COPILOT'),
	      allowChangeTitle: false,
	      allowChangeHistory: false,
	      cacheable: false,
	      toolbar: this.getDefaultToolbarButtons,
	      buttons: [],
	      width: 795,
	      extensions: [],
	      events: {},
	      label: {}
	    });
	    babelHelpers.defineProperty(this, "isOpen", false);
	    this.initOptions(options);
	  }
	  babelHelpers.createClass(Slider, [{
	    key: "initOptions",
	    value: function initOptions(options) {
	      this.title = main_core.Type.isString(options.title) ? options.title : this.DEFAULT_OPTIONS.title;
	      this.sliderTitle = main_core.Type.isString(options.sliderTitle) ? options.sliderTitle : this.DEFAULT_OPTIONS.title;
	      this.toolbar = main_core.Type.isFunction(options.toolbar) ? options.toolbar : this.DEFAULT_OPTIONS.toolbar;
	      this.buttons = main_core.Type.isFunction(options.buttons) ? options.buttons : this.DEFAULT_OPTIONS.buttons;
	      this.cacheable = main_core.Type.isBoolean(options.cacheable) ? options.cacheable : this.DEFAULT_OPTIONS.cacheable;
	      this.width = main_core.Type.isInteger(options.width) ? options.width : this.DEFAULT_OPTIONS.width;
	      this.label = main_core.Type.isPlainObject(options.label) ? options.label : this.DEFAULT_OPTIONS.label;
	      this.extensions = main_core.Type.isArray(options.extensions) ? options.extensions : this.DEFAULT_OPTIONS.extensions;
	      this.events = main_core.Type.isPlainObject(options.events) ? options.events : this.DEFAULT_OPTIONS.events;

	      // Need to buttons to always be transparent-white when enable DependOnTheme in Button
	      this.enableLightThemeIntoSlider = main_core.Type.isBoolean(options.enableLightThemeIntoSlider) ? options.enableLightThemeIntoSlider : true;
	      this.allowChangeTitle = main_core.Type.isBoolean(options.allowChangeTitle) ? options.allowChangeTitle : this.DEFAULT_OPTIONS.allowChangeTitle;
	      this.allowChangeHistory = main_core.Type.isBoolean(options.allowChangeHistory) ? options.allowChangeHistory : this.DEFAULT_OPTIONS.allowChangeHistory;
	      this.setContent(options.content);
	      this.url = main_core.Type.isString(options.url) ? options.url : this.getDefaultUrl();
	    }
	  }, {
	    key: "setContent",
	    value: function setContent(content) {
	      if (!main_core.Type.isFunction(content)) {
	        this.content = function () {
	          return content;
	        };
	        return;
	      }
	      this.content = content;
	    }
	  }, {
	    key: "open",
	    value: function open() {
	      this.isOpen = ui_sidepanel.SidePanel.Instance.open(this.url, this.getSliderOptions());
	      return this.isOpen;
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (!this.isOpen) {
	        return;
	      }
	      ui_sidepanel.SidePanel.Instance.close();
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      ui_sidepanel.SidePanel.Instance.destroy(this.url);
	    }
	  }, {
	    key: "getDefaultUrl",
	    value: function getDefaultUrl() {
	      return 'crm.copilot-wrapper';
	    }
	  }, {
	    key: "getSliderOptions",
	    value: function getSliderOptions() {
	      var _this = this;
	      return {
	        contentClassName: this.getSliderContentClassName(),
	        title: this.title,
	        allowChangeTitle: this.allowChangeTitle,
	        width: this.width,
	        cacheable: this.cacheable,
	        allowChangeHistory: this.allowChangeHistory,
	        label: this.label,
	        contentCallback: function contentCallback(slider) {
	          return ui_sidepanel_layout.Layout.createContent({
	            title: _this.sliderTitle,
	            toolbar: _this.toolbar,
	            content: _this.content,
	            buttons: _this.buttons,
	            design: {
	              section: false
	            },
	            extensions: ['crm.ai.slider'].concat(babelHelpers.toConsumableArray(_this.extensions))
	          });
	        },
	        events: this.events
	      };
	    }
	  }, {
	    key: "getSliderContentClassName",
	    value: function getSliderContentClassName() {
	      var className = 'crm-copilot-wrapper';
	      if (this.enableLightThemeIntoSlider) {
	        className += ' bitrix24-light-theme';
	      }
	      return className;
	    }
	  }, {
	    key: "getDefaultToolbarButtons",
	    value: function getDefaultToolbarButtons() {
	      return Slider.makeDefaultToolbarButtons();
	    }
	  }], [{
	    key: "makeDefaultToolbarButtons",
	    value: function makeDefaultToolbarButtons() {
	      var helpdeskCode = '18799442';
	      var helpButton = new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_COPILOT_WRAPPER_HELP_BUTTON_TITLE'),
	        size: ui_buttons.Button.Size.MEDIUM,
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        dependOnTheme: true,
	        onclick: function onclick() {
	          if (top.BX.Helper) {
	            top.BX.Helper.show("redirect=detail&code=".concat(helpdeskCode));
	          }
	        }
	      });
	      return [helpButton];
	    }
	  }]);
	  return Slider;
	}();

	exports.Slider = Slider;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX,BX,BX.UI.SidePanel,BX.UI));
//# sourceMappingURL=slider.bundle.js.map

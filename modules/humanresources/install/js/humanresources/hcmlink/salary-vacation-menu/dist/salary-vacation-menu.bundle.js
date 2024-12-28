/* eslint-disable */
this.BX = this.BX || {};
this.BX.HumanResources = this.BX.HumanResources || {};
(function (exports,main_core,main_core_cache,main_popup) {
	'use strict';

	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _mode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mode");
	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _getMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenu");
	var _openApplication = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openApplication");
	var _attachHintToButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("attachHintToButton");
	var _getDisabledHintHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDisabledHintHtml");
	class SalaryVacationMenu {
	  constructor(mode = 'profile-menu') {
	    Object.defineProperty(this, _getDisabledHintHtml, {
	      value: _getDisabledHintHtml2
	    });
	    Object.defineProperty(this, _attachHintToButton, {
	      value: _attachHintToButton2
	    });
	    Object.defineProperty(this, _openApplication, {
	      value: _openApplication2
	    });
	    Object.defineProperty(this, _getMenu, {
	      value: _getMenu2
	    });
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _mode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _mode)[_mode] = mode;
	  }
	  async load() {
	    if (main_core.Type.isObject(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = await main_core.ajax.runAction('humanresources.hcmlink.placement.loadSalaryVacation').then(response => {
	      return response.data;
	    }).catch(() => {
	      return {
	        show: false
	      };
	    });
	  }
	  isHidden() {
	    var _babelHelpers$classPr;
	    return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings]) == null ? void 0 : _babelHelpers$classPr.show) !== true;
	  }
	  isDisabled() {
	    var _babelHelpers$classPr2;
	    return ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings]) == null ? void 0 : _babelHelpers$classPr2.disabled) === true;
	  }
	  show(bindElement) {
	    if (this.isHidden() || this.isDisabled()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().setBindElement(bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().show();
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().adjustPosition();
	  }
	  bindButton(button) {
	    // Only 'ui-btn' mode is supported
	    if (babelHelpers.classPrivateFieldLooseBase(this, _mode)[_mode] !== 'ui-btn') {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] = button;
	    if (!this.isHidden() && !this.isDisabled()) {
	      main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button], 'click', () => {
	        this.show(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button]);
	      });
	    }
	    if (this.isDisabled()) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button], 'ui-btn-disabled');
	      babelHelpers.classPrivateFieldLooseBase(this, _attachHintToButton)[_attachHintToButton](babelHelpers.classPrivateFieldLooseBase(this, _button)[_button]);
	    }
	    return this;
	  }
	  setSettings(settings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = settings;
	    return this;
	  }
	}
	function _getMenu2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('hcmLinkSalaryVacationMenu', () => {
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].options.map(option => {
	      return {
	        text: option.title,
	        onclick: (event, item) => {
	          menu.close();
	          babelHelpers.classPrivateFieldLooseBase(this, _openApplication)[_openApplication](option);
	        }
	      };
	    });
	    const menu = new main_popup.Menu({
	      id: 'hcmLink-vacation-salary-menu',
	      items,
	      autoHide: true
	    });
	    return menu;
	  });
	}
	function _openApplication2(option) {
	  BX.rest.AppLayout.openApplication(option.appId, option.options, {
	    PLACEMENT: option.code,
	    PLACEMENT_ID: option.id
	  });
	}
	function _attachHintToButton2(button) {
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].setAttribute('data-hint', babelHelpers.classPrivateFieldLooseBase(this, _getDisabledHintHtml)[_getDisabledHintHtml]());
	  main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button], 'data-hint-no-icon', 'y');
	  main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button], 'data-hint-html', 'y');
	  main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button], 'data-hint-interactivity', 'y');
	  if (BX.UI.Hint) {
	    BX.UI.Hint.init(button.parentElement);
	  }
	}
	function _getDisabledHintHtml2() {
	  return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_SALARY_VACATION_MENU_DISABLED_HINT', {
	    '[LINK]': `
				<a target='_self'
					onclick='(() => {
						BX.Helper.show(\`redirect=detail&code=23343028\`);
					})()'
					style='cursor:pointer;'
				>
			`,
	    '[/LINK]': '</a>'
	  });
	}

	exports.SalaryVacationMenu = SalaryVacationMenu;

}((this.BX.HumanResources.HcmLink = this.BX.HumanResources.HcmLink || {}),BX,BX.Cache,BX.Main));
//# sourceMappingURL=salary-vacation-menu.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,main_core) {
	'use strict';

	var _menuLinux = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuLinux");
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _installersForLinux = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("installersForLinux");
	class Bitrix24Banner {
	  constructor() {
	    Object.defineProperty(this, _menuLinux, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _installersForLinux, {
	      writable: true,
	      value: void 0
	    });
	  }
	  static getInstance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	  showMenuForLinux(event, target, links) {
	    event.preventDefault();
	    babelHelpers.classPrivateFieldLooseBase(this, _installersForLinux)[_installersForLinux] = links;
	    babelHelpers.classPrivateFieldLooseBase(this, _menuLinux)[_menuLinux] = babelHelpers.classPrivateFieldLooseBase(this, _menuLinux)[_menuLinux] || new main_popup.Menu({
	      className: 'system-auth-form__popup',
	      bindElement: target,
	      items: [{
	        text: main_core.Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_DEB'),
	        href: babelHelpers.classPrivateFieldLooseBase(this, _installersForLinux)[_installersForLinux].deb,
	        onclick: element => {
	          element.close();
	        }
	      }, {
	        text: main_core.Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_RPM'),
	        href: babelHelpers.classPrivateFieldLooseBase(this, _installersForLinux)[_installersForLinux].rpm,
	        onclick: element => {
	          element.close();
	        }
	      }],
	      angle: true,
	      offsetLeft: 40
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _menuLinux)[_menuLinux].toggle();
	  }
	}
	Object.defineProperty(Bitrix24Banner, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.Bitrix24Banner = Bitrix24Banner;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Main,BX));
//# sourceMappingURL=script.js.map

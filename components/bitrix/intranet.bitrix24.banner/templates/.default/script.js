this.BX = this.BX || {};
(function (exports,main_popup,main_core) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var _menuLinux = /*#__PURE__*/new WeakMap();
	var _typesInstallersForLinux = /*#__PURE__*/new WeakMap();
	var Bitrix24Banner = /*#__PURE__*/function () {
	  function Bitrix24Banner() {
	    babelHelpers.classCallCheck(this, Bitrix24Banner);
	    _classPrivateFieldInitSpec(this, _menuLinux, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _typesInstallersForLinux, {
	      writable: true,
	      value: {
	        'DEB': {
	          text: main_core.Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_DEB'),
	          href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.deb'
	        },
	        'RPM': {
	          text: main_core.Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_RPM'),
	          href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.rpm'
	        }
	      }
	    });
	  }
	  babelHelpers.createClass(Bitrix24Banner, [{
	    key: "showMenuForLinux",
	    value: function showMenuForLinux(event, target) {
	      event.preventDefault();
	      babelHelpers.classPrivateFieldSet(this, _menuLinux, babelHelpers.classPrivateFieldGet(this, _menuLinux) || new main_popup.Menu({
	        className: 'system-auth-form__popup',
	        bindElement: target,
	        items: [{
	          text: babelHelpers.classPrivateFieldGet(this, _typesInstallersForLinux).DEB.text,
	          href: babelHelpers.classPrivateFieldGet(this, _typesInstallersForLinux).DEB.href,
	          onclick: function onclick(element) {
	            element.close();
	          }
	        }, {
	          text: babelHelpers.classPrivateFieldGet(this, _typesInstallersForLinux).RPM.text,
	          href: babelHelpers.classPrivateFieldGet(this, _typesInstallersForLinux).RPM.href,
	          onclick: function onclick(element) {
	            element.close();
	          }
	        }],
	        angle: true,
	        offsetLeft: 40
	      }));
	      babelHelpers.classPrivateFieldGet(this, _menuLinux).toggle();
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!_classStaticPrivateFieldSpecGet(this, Bitrix24Banner, _instance)) {
	        _classStaticPrivateFieldSpecSet(this, Bitrix24Banner, _instance, new this());
	      }
	      return _classStaticPrivateFieldSpecGet(this, Bitrix24Banner, _instance);
	    }
	  }]);
	  return Bitrix24Banner;
	}();
	var _instance = {
	  writable: true,
	  value: void 0
	};

	exports.Bitrix24Banner = Bitrix24Banner;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Main,BX));
//# sourceMappingURL=script.js.map

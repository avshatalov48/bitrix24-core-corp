this.BX = this.BX || {};
(function (exports,ui_popupcomponentsmaker,main_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5;
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _isBitrix = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24");
	var _isAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	var _isRequisite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRequisite");
	var _getWidgetPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetPopup");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	class SettingsWidgetLoader {
	  constructor(params) {
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _getWidgetPopup, {
	      value: _getWidgetPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isBitrix, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isAdmin, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isRequisite, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] = params['isBitrix24'];
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] = params['isAdmin'];
	    babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite] = params['isRequisite'];
	  }
	  showOnce(node) {
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _getWidgetPopup)[_getWidgetPopup]().getPopup();
	    popup.setBindElement(node);
	    popup.show();
	    const popupContainer = popup.getPopupContainer();
	    if (popupContainer.getBoundingClientRect().left < 30) {
	      popupContainer.style.left = '30px';
	    }
	    (typeof BX.Intranet.SettingsWidget !== 'undefined' ? Promise.resolve() : babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]()).then(() => {
	      if (typeof BX.Intranet.SettingsWidget !== 'undefined') {
	        BX.Intranet.SettingsWidget.bindAndShow(node);
	      }
	    });
	  }
	  static init(options) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this(options);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	}
	function _getWidgetPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  const popup = new ui_popupcomponentsmaker.PopupComponentsMaker({
	    width: 374
	  });
	  const container = popup.getPopup().getPopupContainer();
	  main_core.Dom.clean(container);
	  main_core.Dom.addClass(container, 'intranet-settings-widget__container');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] && !babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin]) {
	    main_core.Dom.append(main_core.Tag.render(_t || (_t = _`<div class="intranet-settings-widget__skeleton-not-admin"></div>`)), container);
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] && babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite]) {
	    main_core.Dom.append(main_core.Tag.render(_t2 || (_t2 = _`<div class="intranet-settings-widget__skeleton"></div>`)), container);
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] && !babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite]) {
	    main_core.Dom.append(main_core.Tag.render(_t3 || (_t3 = _`<div class="intranet-settings-widget__skeleton-no-requisite"></div>`)), container);
	  } else if (!babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] && babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite]) {
	    main_core.Dom.append(main_core.Tag.render(_t4 || (_t4 = _`<div class="intranet-settings-widget__skeleton-no-holding"></div>`)), container);
	  } else {
	    main_core.Dom.append(main_core.Tag.render(_t5 || (_t5 = _`<div class="intranet-settings-widget__skeleton-no-requisite-holding"></div>`)), container);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = popup;
	  return popup;
	}
	function _load2() {
	  return new Promise(resolve => {
	    main_core.ajax.runComponentAction('bitrix:intranet.settings.widget', 'getWidgetComponent', {
	      mode: 'class'
	    }).then(response => {
	      return new Promise(resolve => {
	        const loadCss = response.data.assets ? response.data.assets.css : [];
	        const loadJs = response.data.assets ? response.data.assets.js : [];
	        BX.load(loadCss, () => {
	          BX.loadScript(loadJs, () => {
	            main_core.Runtime.html(null, response.data.html).then(resolve);
	          });
	        });
	      });
	    }).then(() => {
	      if (typeof BX.Intranet.SettingsWidget !== 'undefined') {
	        setTimeout(() => {
	          BX.Intranet.SettingsWidget.bindWidget(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetPopup)[_getWidgetPopup]());
	          resolve();
	        }, 0);
	      }
	    });
	  });
	}
	Object.defineProperty(SettingsWidgetLoader, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.SettingsWidgetLoader = SettingsWidgetLoader;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI,BX));
//# sourceMappingURL=script.js.map

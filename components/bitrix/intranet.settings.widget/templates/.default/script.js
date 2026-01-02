/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,intranet_widgetLoader) {
	'use strict';

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _widgetLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widgetLoader");
	var _isBitrix = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24");
	var _isAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	var _isRequisite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRequisite");
	var _isMainPageAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMainPageAvailable");
	var _node = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("node");
	var _getWidgetLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetLoader");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	class SettingsWidgetLoader {
	  constructor(params) {
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _getWidgetLoader, {
	      value: _getWidgetLoader2
	    });
	    Object.defineProperty(this, _widgetLoader, {
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
	    Object.defineProperty(this, _isMainPageAvailable, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _node, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] = params['isBitrix24'];
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] = params['isAdmin'];
	    babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite] = params['isRequisite'];
	    babelHelpers.classPrivateFieldLooseBase(this, _isMainPageAvailable)[_isMainPageAvailable] = params['isMainPageAvailable'];
	  }
	  showOnce(node) {
	    babelHelpers.classPrivateFieldLooseBase(this, _node)[_node] = node;
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().getPopup();
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
	function _getWidgetLoader2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _widgetLoader)[_widgetLoader]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _widgetLoader)[_widgetLoader];
	  }
	  const widgetLoader = new intranet_widgetLoader.WidgetLoader({
	    id: "bx-settings-header-popup",
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _node)[_node],
	    width: 374
	  });
	  widgetLoader.addHeaderSkeleton();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isRequisite)[_isRequisite]) {
	    widgetLoader.addItemSkeleton(22);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isMainPageAvailable)[_isMainPageAvailable]) {
	    widgetLoader.addItemSkeleton(22);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin]) {
	    widgetLoader.addSplitItemSkeleton(22);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix]) {
	    widgetLoader.addItemSkeleton(22);
	  }
	  widgetLoader.addItemSkeleton(22);
	  widgetLoader.addFooterSkeleton();
	  babelHelpers.classPrivateFieldLooseBase(this, _widgetLoader)[_widgetLoader] = widgetLoader;
	  return babelHelpers.classPrivateFieldLooseBase(this, _widgetLoader)[_widgetLoader];
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
	          BX.Intranet.SettingsWidget.bindWidget(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]());
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

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Intranet));
//# sourceMappingURL=script.js.map

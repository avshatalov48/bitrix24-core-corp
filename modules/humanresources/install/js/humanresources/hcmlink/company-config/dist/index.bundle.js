/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_hcmlink_api,main_core,ui_sidepanel_layout) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _companyConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyConfig");
	var _loadConfigPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadConfigPromise");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _getContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _getBody = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBody");
	var _getElementNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getElementNode");
	class HcmlinkCompanyConfig {
	  constructor(options) {
	    Object.defineProperty(this, _getElementNode, {
	      value: _getElementNode2
	    });
	    Object.defineProperty(this, _getBody, {
	      value: _getBody2
	    });
	    Object.defineProperty(this, _getContent, {
	      value: _getContent2
	    });
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _companyConfig, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _loadConfigPromise, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new humanresources_hcmlink_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	  }
	  static openSlider(options, sliderOptions) {
	    var _sliderOptions$onClos;
	    const companyId = options.companyId;
	    BX.SidePanel.Instance.open(`humanresources:hcmlink-company-config-${companyId}`, {
	      width: 800,
	      loader: 'default-loader',
	      cacheable: false,
	      contentCallback: () => {
	        return top.BX.Runtime.loadExtension('humanresources.hcmlink.company-config').then(exports => {
	          return new exports.HcmlinkCompanyConfig(options).getLayout();
	        });
	      },
	      events: {
	        onClose: (_sliderOptions$onClos = sliderOptions == null ? void 0 : sliderOptions.onCloseHandler) != null ? _sliderOptions$onClos : () => {}
	      }
	    });
	  }
	  async getLayout() {
	    const data = await babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	    babelHelpers.classPrivateFieldLooseBase(this, _companyConfig)[_companyConfig] = data.config;
	    return ui_sidepanel_layout.Layout.createContent({
	      title: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].title,
	      content: () => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _getContent)[_getContent]();
	      },
	      buttons() {
	        return [];
	      }
	    });
	  }
	}
	async function _load2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loadConfigPromise)[_loadConfigPromise]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _loadConfigPromise)[_loadConfigPromise];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loadConfigPromise)[_loadConfigPromise] = babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadCompanyConfig({
	    companyId: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].companyId
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _loadConfigPromise)[_loadConfigPromise];
	}
	function _getContent2() {
	  const body = babelHelpers.classPrivateFieldLooseBase(this, _getBody)[_getBody]();
	  return main_core.Tag.render(_t || (_t = _`
			<div>
				<h2 class="hr-hcmlink-company-config-header">${0}</h2>
				${0}
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_CONFIG_SLIDER_TITLE'), body);
	}
	function _getBody2() {
	  const bodyContainer = main_core.Tag.render(_t2 || (_t2 = _`<div class="hr-hcmlink-company-config-container"></div>`));
	  babelHelpers.classPrivateFieldLooseBase(this, _companyConfig)[_companyConfig].forEach(item => main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getElementNode)[_getElementNode](item), bodyContainer));
	  return bodyContainer;
	}
	function _getElementNode2(item) {
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="hr-hcmlink-company-config-container__element">
				<div class="hr-hcmlink-company-config-container__label">${0}</div>
				<div class="hr-hcmlink-company-config-container__value">${0}</div>
			</div>
		`), main_core.Text.encode(item.title), main_core.Text.encode(item.value));
	}

	exports.HcmlinkCompanyConfig = HcmlinkCompanyConfig;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX.Humanresources.Hcmlink,BX,BX.UI.SidePanel));
//# sourceMappingURL=index.bundle.js.map

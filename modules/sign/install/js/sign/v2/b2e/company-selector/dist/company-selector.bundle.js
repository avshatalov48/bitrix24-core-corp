/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_loader,sign_v2_b2e_hcmLinkCompanySelector,sign_v2_b2e_schemeSelector,sign_v2_companyEditor,main_core,main_core_cache,main_core_events,main_date,main_popup,sign_tour,sign_type,sign_v2_helper,ui_alerts,ui_entitySelector,ui_label,sign_v2_api) {
	'use strict';

	function hide(element) {
	  if (main_core.Type.isElementNode(element)) {
	    BX.hide(element);
	  }
	}
	function show(element) {
	  if (main_core.Type.isElementNode(element)) {
	    BX.show(element);
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16,
	  _t17,
	  _t18,
	  _t19;
	const allowedSignatureProviders = ['goskey', 'external', 'ses-ru', 'ses-com', 'ses-ru-express'];
	const sesComLearnMoreLink = new main_core.Uri('https://www.bitrix24.com/terms/esignature-for-hr-rules.php');
	var _layoutCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutCache");
	var _providerMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerMenu");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _showTaxId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showTaxId");
	var _providerExpiresDaysToShowInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerExpiresDaysToShowInfo");
	var _company = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("company");
	var _companyList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyList");
	var _registerIframe = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("registerIframe");
	var _iframeConnectInterval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("iframeConnectInterval");
	var _isSubscribedIframeCloseEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSubscribedIframeCloseEvent");
	var _isSubscribedIframeConnectedEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSubscribedIframeConnectedEvent");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _providerConnectedSelectDropdownBtnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerConnectedSelectDropdownBtnLayout");
	var _providerConnectedNameLabelLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerConnectedNameLabelLayout");
	var _providerConnectedNameLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerConnectedNameLayout");
	var _providerConnectedDescriptionLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerConnectedDescriptionLayout");
	var _providerDisconnectedBtnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerDisconnectedBtnLayout");
	var _providerInfoLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerInfoLayout");
	var _providerUnsetLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerUnsetLayout");
	var _providerDisconnectedLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerDisconnectedLayout");
	var _connectedProviderLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("connectedProviderLayout");
	var _providerConnectDropdownBtnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerConnectDropdownBtnLayout");
	var _getProviderMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderMenu");
	var _getProviderAddButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderAddButton");
	var _onProviderSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProviderSelect");
	var _chooseProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chooseProvider");
	var _setProviderImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setProviderImage");
	var _makeGoskeyApikeyExpiredLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeGoskeyApikeyExpiredLabel");
	var _showConnectMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showConnectMenu");
	var _tryStartProviderTour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tryStartProviderTour");
	var _updateProviderMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateProviderMenu");
	var _getProviderConnectedDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderConnectedDescription");
	var _getConnectedName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getConnectedName");
	var _getProviderNameByCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderNameByCode");
	var _resetProviderClasses = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetProviderClasses");
	var _resetProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetProvider");
	var _onProviderDeselect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProviderDeselect");
	var _renderProviderInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderProviderInfo");
	var _getProviderAlert = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderAlert");
	var _getProviderAlertMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderAlertMessage");
	var _resetProviderState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetProviderState");
	var _isProviderExpiresSoon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isProviderExpiresSoon");
	var _isProviderExpired = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isProviderExpired");
	var _getProviderDaysLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderDaysLeft");
	var _getCompanyById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyById");
	var _disconnectCurrentProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disconnectCurrentProvider");
	var _openProvidersConnectionSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openProvidersConnectionSlider");
	var _subscribeIframeConnectedEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeIframeConnectedEvent");
	var _subscribeIframeCloseEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeIframeCloseEvent");
	var _initIframeConnect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initIframeConnect");
	var _getEntityAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityAvatar");
	var _getEntityBadges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityBadges");
	class ProviderSelector extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getEntityBadges, {
	      value: _getEntityBadges2
	    });
	    Object.defineProperty(this, _getEntityAvatar, {
	      value: _getEntityAvatar2
	    });
	    Object.defineProperty(this, _initIframeConnect, {
	      value: _initIframeConnect2
	    });
	    Object.defineProperty(this, _subscribeIframeCloseEvent, {
	      value: _subscribeIframeCloseEvent2
	    });
	    Object.defineProperty(this, _subscribeIframeConnectedEvent, {
	      value: _subscribeIframeConnectedEvent2
	    });
	    Object.defineProperty(this, _openProvidersConnectionSlider, {
	      value: _openProvidersConnectionSlider2
	    });
	    Object.defineProperty(this, _disconnectCurrentProvider, {
	      value: _disconnectCurrentProvider2
	    });
	    Object.defineProperty(this, _getCompanyById, {
	      value: _getCompanyById2
	    });
	    Object.defineProperty(this, _getProviderDaysLeft, {
	      value: _getProviderDaysLeft2
	    });
	    Object.defineProperty(this, _isProviderExpired, {
	      value: _isProviderExpired2
	    });
	    Object.defineProperty(this, _isProviderExpiresSoon, {
	      value: _isProviderExpiresSoon2
	    });
	    Object.defineProperty(this, _resetProviderState, {
	      value: _resetProviderState2
	    });
	    Object.defineProperty(this, _getProviderAlertMessage, {
	      value: _getProviderAlertMessage2
	    });
	    Object.defineProperty(this, _getProviderAlert, {
	      value: _getProviderAlert2
	    });
	    Object.defineProperty(this, _renderProviderInfo, {
	      value: _renderProviderInfo2
	    });
	    Object.defineProperty(this, _onProviderDeselect, {
	      value: _onProviderDeselect2
	    });
	    Object.defineProperty(this, _resetProvider, {
	      value: _resetProvider2
	    });
	    Object.defineProperty(this, _resetProviderClasses, {
	      value: _resetProviderClasses2
	    });
	    Object.defineProperty(this, _getProviderNameByCode, {
	      value: _getProviderNameByCode2
	    });
	    Object.defineProperty(this, _getConnectedName, {
	      value: _getConnectedName2
	    });
	    Object.defineProperty(this, _getProviderConnectedDescription, {
	      value: _getProviderConnectedDescription2
	    });
	    Object.defineProperty(this, _updateProviderMenu, {
	      value: _updateProviderMenu2
	    });
	    Object.defineProperty(this, _tryStartProviderTour, {
	      value: _tryStartProviderTour2
	    });
	    Object.defineProperty(this, _showConnectMenu, {
	      value: _showConnectMenu2
	    });
	    Object.defineProperty(this, _makeGoskeyApikeyExpiredLabel, {
	      value: _makeGoskeyApikeyExpiredLabel2
	    });
	    Object.defineProperty(this, _setProviderImage, {
	      value: _setProviderImage2
	    });
	    Object.defineProperty(this, _chooseProvider, {
	      value: _chooseProvider2
	    });
	    Object.defineProperty(this, _onProviderSelect, {
	      value: _onProviderSelect2
	    });
	    Object.defineProperty(this, _getProviderAddButton, {
	      value: _getProviderAddButton2
	    });
	    Object.defineProperty(this, _getProviderMenu, {
	      value: _getProviderMenu2
	    });
	    Object.defineProperty(this, _providerConnectDropdownBtnLayout, {
	      get: _get_providerConnectDropdownBtnLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _connectedProviderLayout, {
	      get: _get_connectedProviderLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerDisconnectedLayout, {
	      get: _get_providerDisconnectedLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerUnsetLayout, {
	      get: _get_providerUnsetLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerInfoLayout, {
	      get: _get_providerInfoLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerDisconnectedBtnLayout, {
	      get: _get_providerDisconnectedBtnLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerConnectedDescriptionLayout, {
	      get: _get_providerConnectedDescriptionLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerConnectedNameLayout, {
	      get: _get_providerConnectedNameLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerConnectedNameLabelLayout, {
	      get: _get_providerConnectedNameLabelLayout,
	      set: void 0
	    });
	    Object.defineProperty(this, _providerConnectedSelectDropdownBtnLayout, {
	      get: _get_providerConnectedSelectDropdownBtnLayout,
	      set: void 0
	    });
	    this.events = {
	      onProviderSelect: 'providerSelected',
	      onProviderDeselect: 'providerDeselected',
	      onProviderDisconnect: 'onProviderDisconnect',
	      providerConnectionSlider: {
	        onClose: 'onClose'
	      }
	    };
	    Object.defineProperty(this, _layoutCache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _providerMenu, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _showTaxId, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _providerExpiresDaysToShowInfo, {
	      writable: true,
	      value: 45
	    });
	    Object.defineProperty(this, _company, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _companyList, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _registerIframe, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _iframeConnectInterval, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isSubscribedIframeCloseEvent, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isSubscribedIframeConnectedEvent, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.CompanySelector:ProviderSelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('layout', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div class="sign-document-b2e-company__provider">
					<div class="sign-document-b2e-company__provider_content">
						${0}
						${0}
						${0}
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedLayout)[_providerDisconnectedLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerUnsetLayout)[_providerUnsetLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    });
	  }
	  setShowTaxId(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showTaxId)[_showTaxId] = value;
	    return this;
	  }
	  setProvider(rqInn, company) {
	    var _company$providers;
	    babelHelpers.classPrivateFieldLooseBase(this, _resetProviderState)[_resetProviderState]();
	    if ((company == null ? void 0 : (_company$providers = company.providers) == null ? void 0 : _company$providers.length) > 0) {
	      hide(babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedLayout)[_providerDisconnectedLayout]);
	      babelHelpers.classPrivateFieldLooseBase(this, _updateProviderMenu)[_updateProviderMenu](company);
	      return;
	    }
	    hide(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout]);
	    hide(babelHelpers.classPrivateFieldLooseBase(this, _providerUnsetLayout)[_providerUnsetLayout]);
	    hide(babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    if (!rqInn) {
	      hide(babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedBtnLayout)[_providerDisconnectedBtnLayout]);
	    }
	  }
	  setProviderById(id) {
	    const providerMenu = babelHelpers.classPrivateFieldLooseBase(this, _getProviderMenu)[_getProviderMenu]();
	    const providers = providerMenu.getItems();
	    const currentProvider = providers.find(provider => provider.id === id);
	    currentProvider == null ? void 0 : currentProvider.select();
	  }
	  setCompanyList(companies) {
	    babelHelpers.classPrivateFieldLooseBase(this, _companyList)[_companyList] = main_core.Runtime.clone(companies);
	  }
	  setCompany(company) {
	    babelHelpers.classPrivateFieldLooseBase(this, _company)[_company] = main_core.Runtime.clone(company);
	  }
	  validate() {
	    return main_core.Type.isObject(babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider) && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.uid) && !babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider);
	  }
	  async registerVirtualProviderIfNeed() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.virtual) {
	      return;
	    }
	    const selectedItem = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById](babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].id);
	    const {
	      id
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].registerB2eCompany(babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.code, selectedItem.rqInn, babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].id, babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.externalProviderId);
	    babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.uid = id;
	    babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.virtual = false;
	  }
	  getCurrentProvider() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company]) == null ? void 0 : _babelHelpers$classPr.provider;
	  }
	}
	function _get_providerConnectedSelectDropdownBtnLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerConnectedSelectDropdownBtnLayout', () => {
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<span
					class="sign-document-b2e-company-info-dropdown-btn sign-document-b2e-company__provider_dropdown-btn"
					onclick="${0}"
				></span>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu].show());
	  });
	}
	function _get_providerConnectedNameLabelLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerConnectedNameLabel', () => {
	    return main_core.Tag.render(_t3 || (_t3 = _`
				<div class="sign-document-b2e-company__provider_name_label"></div>
			`));
	  });
	}
	function _get_providerConnectedNameLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerConnectedName', () => {
	    return main_core.Tag.render(_t4 || (_t4 = _`
				<span class="sign-document-b2e-company__provider_name"></span>
			`));
	  });
	}
	function _get_providerConnectedDescriptionLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerConnectedDescription', () => {
	    return main_core.Tag.render(_t5 || (_t5 = _`
				<span class="sign-document-b2e-company__provider_descr"></span>
			`));
	  });
	}
	function _get_providerDisconnectedBtnLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerDisconnectedBtn', () => {
	    return main_core.Tag.render(_t6 || (_t6 = _`
				<button
					class="ui-btn ui-btn-success ui-btn-xs ui-btn-round"
					onclick="${0}"
				>
					${0}
				</button>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _openProvidersConnectionSlider)[_openProvidersConnectionSlider](), main_core.Loc.getMessage('SIGN_B2E_PROVIDER_CONNECT'));
	  });
	}
	function _get_providerInfoLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerInfo', () => {
	    return main_core.Tag.render(_t7 || (_t7 = _`
				<div class="sign-document-b2e-company__provider_info"></div>
			`));
	  });
	}
	function _get_providerUnsetLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerUInset', () => {
	    return main_core.Tag.render(_t8 || (_t8 = _`
				<div class="sign-document-b2e-company-select --provider">
					<span class="sign-document-b2e-company-select-text">
						${0}
					</span>
					<button
						class="ui-btn ui-btn-success ui-btn-xs ui-btn-round"
						onclick="${0}"
					>
						${0}
					</button>
				</div>
			`), main_core.Loc.getMessage('SIGN_B2E_COMPANY_NOT_SET_PROVIDER_STATUS'), () => babelHelpers.classPrivateFieldLooseBase(this, _getProviderMenu)[_getProviderMenu]().show(), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON'));
	  });
	}
	function _get_providerDisconnectedLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerDisconnected', () => {
	    return main_core.Tag.render(_t9 || (_t9 = _`
				<div class="sign-document-b2e-company-select --provider">
					<span class="sign-document-b2e-company-select-text">
						${0}
					</span>
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_B2E_COMPANY_NOT_CONNECTED_PROVIDER_STATUS'), babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedBtnLayout)[_providerDisconnectedBtnLayout]);
	  });
	}
	function _get_connectedProviderLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('connectedProvider', () => {
	    return main_core.Tag.render(_t10 || (_t10 = _`
				<div class="sign-document-b2e-company__provider_selected">
					<div class="sign-document-b2e-company__provider_selected__external-image-container">
						<img class="sign-document-b2e-company__provider_selected__external-img"
							referrerpolicy="no-referrer"
						 alt="provider image">
					</div>
					<div>
						<div class="sign-document-b2e-company__provider_name_container">
							${0}
							${0}
						</div>
						${0}
					</div>
					${0}
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedNameLayout)[_providerConnectedNameLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedNameLabelLayout)[_providerConnectedNameLabelLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedDescriptionLayout)[_providerConnectedDescriptionLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedSelectDropdownBtnLayout)[_providerConnectedSelectDropdownBtnLayout], babelHelpers.classPrivateFieldLooseBase(this, _providerConnectDropdownBtnLayout)[_providerConnectDropdownBtnLayout]);
	  });
	}
	function _get_providerConnectDropdownBtnLayout() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('providerConnectDropdownBtnLayout', () => {
	    return main_core.Tag.render(_t11 || (_t11 = _`
				<span
					class="sign-document-b2e-company-info-edit"
					onclick="${0}"
				></span>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _showConnectMenu)[_showConnectMenu]());
	  });
	}
	function _getProviderMenu2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu] = new ui_entitySelector.Dialog({
	    width: 425,
	    height: 363,
	    targetNode: this.getLayout(),
	    items: [],
	    showAvatars: true,
	    dropdownMode: true,
	    multiple: false,
	    autoHide: true,
	    tabs: [{
	      id: 'b2e-providers',
	      title: main_core.Loc.getMessage('SIGN_B2E_PROVIDERS_TAB')
	    }],
	    events: {
	      'Item:OnSelect': ({
	        data
	      }) => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onProviderSelect)[_onProviderSelect](data.item.id);
	      },
	      'Item:OnDeselect': () => babelHelpers.classPrivateFieldLooseBase(this, _onProviderDeselect)[_onProviderDeselect]()
	    },
	    footer: babelHelpers.classPrivateFieldLooseBase(this, _getProviderAddButton)[_getProviderAddButton]()
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu];
	}
	function _getProviderAddButton2() {
	  var _babelHelpers$classPr2;
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById]((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company]) == null ? void 0 : _babelHelpers$classPr2.id);
	  if (company != null && company.registerUrl) {
	    return main_core.Tag.render(_t12 || (_t12 = _`
				<span
					class="ui-selector-footer-link ui-selector-footer-link-add"
					onclick="${0}"
				>
					${0}
				</span>
			`), () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu].hide();
	      babelHelpers.classPrivateFieldLooseBase(this, _openProvidersConnectionSlider)[_openProvidersConnectionSlider]();
	    }, main_core.Loc.getMessage('SIGN_B2E_PROVIDER_CONNECT_SELECTOR'));
	  }
	  return null;
	}
	function _onProviderSelect2(id) {
	  var _babelHelpers$classPr3;
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById]((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].id) != null ? _babelHelpers$classPr3 : 0);
	  const provider = company.providers.find(({
	    uid
	  }) => uid === id);
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _providerUnsetLayout)[_providerUnsetLayout]);
	  show(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout]);
	  show(babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	  babelHelpers.classPrivateFieldLooseBase(this, _chooseProvider)[_chooseProvider](provider, company.rqInn);
	  babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu].hide();
	  this.emit(this.events.onProviderSelect, {
	    provider
	  });
	}
	function _chooseProvider2(provider, rqInn) {
	  if (!allowedSignatureProviders.includes(provider.code)) {
	    return;
	  }
	  const {
	    providerName,
	    description
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getConnectedName)[_getConnectedName](provider, rqInn);
	  babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedNameLayout)[_providerConnectedNameLayout].textContent = providerName;
	  babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedDescriptionLayout)[_providerConnectedDescriptionLayout].textContent = description;
	  babelHelpers.classPrivateFieldLooseBase(this, _renderProviderInfo)[_renderProviderInfo](provider);
	  babelHelpers.classPrivateFieldLooseBase(this, _resetProviderClasses)[_resetProviderClasses]();
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout], `--with-icon --${provider.code}`);
	  if (provider.code === ProviderCode.external) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setProviderImage)[_setProviderImage](provider);
	  }
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedNameLabelLayout)[_providerConnectedNameLabelLayout]);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](provider)) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout], '--expired');
	    const expiredLabel = babelHelpers.classPrivateFieldLooseBase(this, _makeGoskeyApikeyExpiredLabel)[_makeGoskeyApikeyExpiredLabel]();
	    main_core.Dom.append(expiredLabel.render(), babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedNameLabelLayout)[_providerConnectedNameLabelLayout]);
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _providerConnectDropdownBtnLayout)[_providerConnectDropdownBtnLayout], 'display', provider.autoRegister ? 'none' : 'flex');
	  babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider = provider;
	}
	function _setProviderImage2(provider) {
	  var _this$getLayout$getEl, _this$getLayout;
	  if (!main_core.Type.isStringFilled(provider.iconUrl)) {
	    return;
	  }
	  const imgClassName = 'sign-document-b2e-company__provider_selected__external-img';
	  const img = (_this$getLayout$getEl = (_this$getLayout = this.getLayout()) == null ? void 0 : _this$getLayout.getElementsByClassName(imgClassName)[0]) != null ? _this$getLayout$getEl : null;
	  if (!img) {
	    return;
	  }
	  img.src = provider.iconUrl;
	}
	function _makeGoskeyApikeyExpiredLabel2() {
	  return new ui_label.Label({
	    text: main_core.Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED'),
	    color: ui_label.LabelColor.WARNING,
	    fill: true,
	    customClass: 'sign-document-b2e-company__provider_label'
	  });
	}
	function _showConnectMenu2() {
	  const menu = new main_popup.Menu({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _providerConnectDropdownBtnLayout)[_providerConnectDropdownBtnLayout],
	    cacheable: false
	  });
	  menu.addMenuItem({
	    text: main_core.Loc.getMessage('SIGN_B2E_PROVIDER_DISCONNECT'),
	    onclick: () => {
	      void babelHelpers.classPrivateFieldLooseBase(this, _disconnectCurrentProvider)[_disconnectCurrentProvider]();
	      menu.close();
	    }
	  });
	  menu.show();
	}
	function _tryStartProviderTour2() {
	  const guide = new sign_tour.Guide({
	    id: 'sign-b2e-provider-tour',
	    onEvents: true,
	    autoSave: true,
	    steps: [{
	      target: babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedSelectDropdownBtnLayout)[_providerConnectedSelectDropdownBtnLayout],
	      title: `
						<p class="sign-document-b2e-company__provider_tour-step-head">
							${main_core.Loc.getMessage('SIGN_B2E_TOUR_HEAD')}
						</p>
					`,
	      text: `
						<p class="sign-document-b2e-company__provider_tour-step-text">
							${main_core.Loc.getMessage('SIGN_B2E_TOUR_TEXT')}
						</p>
						<span class="sign-document-b2e-company__provider_tour-step-icon"></span>
					`,
	      condition: {
	        top: true,
	        bottom: false,
	        color: 'primary'
	      }
	    }],
	    popupOptions: {
	      width: 380,
	      autoHide: true,
	      className: 'sign-document-b2e-company__provider_popup-tour',
	      centerAngle: true
	    }
	  });
	  void guide.startOnce();
	}
	function _updateProviderMenu2(company) {
	  var _company$providers2;
	  babelHelpers.classPrivateFieldLooseBase(this, _resetProvider)[_resetProvider]();
	  const menu = babelHelpers.classPrivateFieldLooseBase(this, _getProviderMenu)[_getProviderMenu]();
	  company.providers.forEach(provider => {
	    const {
	      providerName,
	      description
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getConnectedName)[_getConnectedName](provider, company.rqInn);
	    menu.addItem({
	      id: provider.uid,
	      title: providerName,
	      subtitle: description,
	      avatar: babelHelpers.classPrivateFieldLooseBase(this, _getEntityAvatar)[_getEntityAvatar](provider),
	      entityId: 'b2e-provider',
	      tabs: 'b2e-providers',
	      badges: babelHelpers.classPrivateFieldLooseBase(this, _getEntityBadges)[_getEntityBadges](provider)
	    });
	  });
	  const [firstItem] = menu.getItems();
	  firstItem.select();
	  const nothingToSelect = !(company != null && company.registerUrl) && (company == null ? void 0 : (_company$providers2 = company.providers) == null ? void 0 : _company$providers2.length) < 2;
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _providerConnectedSelectDropdownBtnLayout)[_providerConnectedSelectDropdownBtnLayout], 'display', nothingToSelect ? 'none' : 'block');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].region === 'ru' && babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].documentInitiatedType !== sign_type.DocumentInitiated.employee) {
	    babelHelpers.classPrivateFieldLooseBase(this, _tryStartProviderTour)[_tryStartProviderTour]();
	  }
	}
	function _getProviderConnectedDescription2(provider, rqInn) {
	  if (provider.autoRegister) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _showTaxId)[_showTaxId] ? main_core.Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_DATE', {
	      '#RQINN#': rqInn
	    }) : main_core.Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_INN_DATE');
	  }
	  const formattedDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('FORMAT_DATE'), provider.timestamp);
	  return babelHelpers.classPrivateFieldLooseBase(this, _showTaxId)[_showTaxId] ? main_core.Loc.getMessage('SIGN_B2E_SELECT_PROVIDER', {
	    '#RQINN#': rqInn,
	    '#DATE#': formattedDate
	  }) : main_core.Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_INN', {
	    '#DATE#': formattedDate
	  });
	}
	function _getConnectedName2(provider, rqInn) {
	  const providerName = provider.code === 'external' ? provider.name : babelHelpers.classPrivateFieldLooseBase(this, _getProviderNameByCode)[_getProviderNameByCode](provider.code);
	  const description = babelHelpers.classPrivateFieldLooseBase(this, _getProviderConnectedDescription)[_getProviderConnectedDescription](provider, rqInn);
	  return {
	    providerName,
	    description
	  };
	}
	function _getProviderNameByCode2(code) {
	  switch (code) {
	    case 'goskey':
	      return main_core.Loc.getMessage('SIGN_B2E_PROVIDER_GOSKEY_NAME');
	    case 'ses-ru':
	      return main_core.Loc.getMessage('SIGN_B2E_PROVIDER_SES_NAME');
	    case 'ses-com':
	      return main_core.Loc.getMessage('SIGN_B2E_PROVIDER_SES_COM_NAME');
	    case 'ses-ru-express':
	      return main_core.Loc.getMessage('SIGN_B2E_PROVIDER_SES_RU_EXPRESS_NAME');
	    default:
	      return '';
	  }
	}
	function _resetProviderClasses2() {
	  const providerClasses = allowedSignatureProviders.map(provider => `--${provider}`);
	  providerClasses.push('--expired');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout], providerClasses);
	}
	function _resetProvider2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu] = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _resetProviderClasses)[_resetProviderClasses]();
	}
	function _onProviderDeselect2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _providerUnsetLayout)[_providerUnsetLayout], 'display', 'flex');
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout]);
	  babelHelpers.classPrivateFieldLooseBase(this, _renderProviderInfo)[_renderProviderInfo]();
	  babelHelpers.classPrivateFieldLooseBase(this, _providerMenu)[_providerMenu].hide();
	  babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider = null;
	  this.emit(this.events.onProviderDeselect);
	}
	function _renderProviderInfo2(provider = null) {
	  var _provider$code, _providerCodeToProvid, _providerCodeToHelpde;
	  const code = (_provider$code = provider == null ? void 0 : provider.code) != null ? _provider$code : '';
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	  if (!provider) {
	    const firstParagraph = main_core.Tag.render(_t13 || (_t13 = _`
				<p>
					${0}
				</p>
			`), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_PARAGRAPH_1'));
	    const secondParagraph = main_core.Tag.render(_t14 || (_t14 = _`
				<p>
					${0}
				</p>
			`), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_PARAGRAPH_2'));
	    const thirdParagraph = main_core.Tag.render(_t15 || (_t15 = _`
				<p>
					${0}
				</p>
			`), sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_MORE'), HelpdeskCodes.HowToChooseProvider));
	    main_core.Dom.append(firstParagraph, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    main_core.Dom.append(secondParagraph, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    main_core.Dom.append(thirdParagraph, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    return;
	  }
	  if (code === ProviderCode.external) {
	    const element = main_core.Tag.render(_t16 || (_t16 = _`
				<p> ${0} </p>
			`), main_core.Text.encode(provider.description));
	    main_core.Dom.append(element, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    return;
	  }
	  if (code === ProviderCode.sesCom) {
	    var _Loc$getMessage;
	    let providerInfo = (_Loc$getMessage = main_core.Loc.getMessage('SIGN_B2E_COMPANY_SES_COM_INFO')) != null ? _Loc$getMessage : '';
	    providerInfo = sign_v2_helper.Helpdesk.replaceLink(providerInfo, HelpdeskCodes.SesComDetails);
	    providerInfo = sign_v2_helper.Link.replaceInLoc(providerInfo, sesComLearnMoreLink);
	    const text = main_core.Tag.render(_t17 || (_t17 = _`<span>${0}</span>`), providerInfo);
	    main_core.Dom.style(text.firstElementChild, {
	      display: 'none'
	    });
	    main_core.Dom.append(text, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	    return;
	  }
	  const providerCodeToProviderInfoTextMap = {
	    goskey: main_core.Loc.getMessage('SIGN_B2E_COMPANY_GOSKEY_INFO'),
	    'ses-ru': main_core.Loc.getMessage('SIGN_B2E_COMPANY_SES_RU_INFO'),
	    'ses-ru-express': main_core.Loc.getMessage('SIGN_B2E_COMPANY_SES_RU_EXPRESS_INFO')
	  };
	  const providerCodeToHelpdeskCodeMap = {
	    goskey: HelpdeskCodes.GoskeyDetails,
	    'ses-ru': HelpdeskCodes.SesRuDetails,
	    'ses-ru-express': HelpdeskCodes.SesRuExpressDetails
	  };
	  const text = main_core.Tag.render(_t18 || (_t18 = _`<span>${0}</span>`), sign_v2_helper.Helpdesk.replaceLink((_providerCodeToProvid = providerCodeToProviderInfoTextMap[code]) != null ? _providerCodeToProvid : '', (_providerCodeToHelpde = providerCodeToHelpdeskCodeMap[code]) != null ? _providerCodeToHelpde : ''));
	  main_core.Dom.append(text, babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpiresSoon)[_isProviderExpiresSoon](provider) || babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](provider)) {
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getProviderAlert)[_getProviderAlert](provider).render(), babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	  }
	}
	function _getProviderAlert2(provider) {
	  return new ui_alerts.Alert({
	    text: babelHelpers.classPrivateFieldLooseBase(this, _getProviderAlertMessage)[_getProviderAlertMessage](provider),
	    customClass: 'sign-document-b2e-company__provider_alert'
	  });
	}
	function _getProviderAlertMessage2(provider) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](provider)) {
	    return sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED_MORE_MSGVER_1'), HelpdeskCodes.GoskeyApiKey);
	  }
	  const daysLeft = babelHelpers.classPrivateFieldLooseBase(this, _getProviderDaysLeft)[_getProviderDaysLeft](provider.expires);
	  const alertText = main_core.Loc.getMessagePlural('SIGN_B2E_GOSKEY_APIKEY_EXPIRES_MSGVER_1', daysLeft, {
	    '#DAYS#': daysLeft
	  });
	  return sign_v2_helper.Helpdesk.replaceLink(alertText, HelpdeskCodes.GoskeyApiKey);
	}
	function _resetProviderState2() {
	  show(this.getLayout());
	  show(babelHelpers.classPrivateFieldLooseBase(this, _providerInfoLayout)[_providerInfoLayout]);
	  show(babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedLayout)[_providerDisconnectedLayout]);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _connectedProviderLayout)[_connectedProviderLayout], 'display', 'flex');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _providerDisconnectedLayout)[_providerDisconnectedLayout], 'display', 'flex');
	}
	function _isProviderExpiresSoon2(provider) {
	  if (!provider.expires) {
	    return false;
	  }
	  const daysLeft = babelHelpers.classPrivateFieldLooseBase(this, _getProviderDaysLeft)[_getProviderDaysLeft](provider.expires);
	  return daysLeft <= babelHelpers.classPrivateFieldLooseBase(this, _providerExpiresDaysToShowInfo)[_providerExpiresDaysToShowInfo] && daysLeft >= 1;
	}
	function _isProviderExpired2(provider) {
	  return provider.expires && babelHelpers.classPrivateFieldLooseBase(this, _getProviderDaysLeft)[_getProviderDaysLeft](provider.expires) < 1;
	}
	function _getProviderDaysLeft2(expires) {
	  const now = Date.now() / 1000;
	  return Math.floor((expires - now) / 86400);
	}
	function _getCompanyById2(id) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _companyList)[_companyList].find(company => id === company.id);
	}
	async function _disconnectCurrentProvider2() {
	  var _babelHelpers$classPr4;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider) {
	    return;
	  }
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById]((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].id) != null ? _babelHelpers$classPr4 : 0);
	  if (!company) {
	    return;
	  }
	  const id = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.uid;
	  if (!id || babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider.autoRegister) {
	    return;
	  }
	  this.emit(this.events.onProviderDisconnect, {
	    provider: babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].provider
	  });
	}
	function _openProvidersConnectionSlider2() {
	  var _babelHelpers$classPr5;
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById]((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company]) == null ? void 0 : _babelHelpers$classPr5.id);
	  if (company && company.registerUrl) {
	    const url = new URL(company.registerUrl);
	    const allowedOrigin = url.origin;
	    BX.SidePanel.Instance.open('sign:stub', {
	      width: 1100,
	      cacheable: false,
	      allowCrossDomain: true,
	      allowChangeHistory: false,
	      contentCallback: () => {
	        const frameStyles = 'position: absolute; left: 0; top: 0; padding: 0;' + ' border: none; margin: 0; width: 100%; height: 100%;';
	        babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe] = main_core.Tag.render(_t19 || (_t19 = _`<iframe src="${0}" style="${0}"></iframe>`), company.registerUrl, frameStyles);
	        return babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe];
	      },
	      events: {
	        onClose: () => this.emit(this.events.providerConnectionSlider.onClose)
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _initIframeConnect)[_initIframeConnect](allowedOrigin);
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeIframeConnectedEvent)[_subscribeIframeConnectedEvent](allowedOrigin);
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeIframeCloseEvent)[_subscribeIframeCloseEvent](allowedOrigin);
	  }
	}
	function _subscribeIframeConnectedEvent2(allowedOrigin) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isSubscribedIframeConnectedEvent)[_isSubscribedIframeConnectedEvent]) {
	    return;
	  }
	  main_core.Event.bind(window, 'message', event => {
	    if (event.origin === allowedOrigin && event.data === 'Event:b2e-crossorigin:connected') {
	      var _babelHelpers$classPr6;
	      clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _iframeConnectInterval)[_iframeConnectInterval]);
	      const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById)[_getCompanyById]((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _company)[_company].id) != null ? _babelHelpers$classPr6 : 0);
	      if (company) {
	        babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe].contentWindow.postMessage({
	          companyName: company.title
	        }, allowedOrigin);
	      }
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _isSubscribedIframeConnectedEvent)[_isSubscribedIframeConnectedEvent] = true;
	}
	function _subscribeIframeCloseEvent2(allowedOrigin) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isSubscribedIframeCloseEvent)[_isSubscribedIframeCloseEvent]) {
	    return;
	  }
	  main_core.Event.bind(window, 'message', event => {
	    if (event.origin === allowedOrigin && event.data === 'Event:b2e-crossorigin:close-iframe') {
	      BX.SidePanel.Instance.close();
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _isSubscribedIframeCloseEvent)[_isSubscribedIframeCloseEvent] = true;
	}
	function _initIframeConnect2(allowedOrigin) {
	  babelHelpers.classPrivateFieldLooseBase(this, _iframeConnectInterval)[_iframeConnectInterval] = setInterval(() => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe] && babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe].contentWindow) {
	      babelHelpers.classPrivateFieldLooseBase(this, _registerIframe)[_registerIframe].contentWindow.postMessage('Event:b2e-crossorigin:initConnection', allowedOrigin);
	    }
	  }, 500);
	}
	function _getEntityAvatar2(provider) {
	  if (provider.code === ProviderCode.goskey) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](provider) ? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYiIGhlaWdodD0iMzYiIHZpZXdCb3g9IjAgMCAzNiAzNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjM2IiBoZWlnaHQ9IjM2IiByeD0iMTgiIGZpbGw9IiNCREMxQzYiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xNi4zMzA1IDE0Ljg5OTlMMTkuNzU3MiAxMS40NzMxQzIwLjM4ODEgMTAuODQyMyAyMS40MTA5IDEwLjg0MjMgMjIuMDQxNyAxMS40NzMxTDI0LjcwNyAxNC4xMzg0QzI1LjMzNzggMTQuNzY5MiAyNS4zMzc4IDE1Ljc5MiAyNC43MDcgMTYuNDIyOUwyMS4yODAyIDE5Ljg0OTZDMjAuODU5NyAyMC4yNzAyIDIwLjE3OTEgMjAuMjcxNSAxOS43NTg1IDE5Ljg1MDlMMTYuMzI5OCAxNi40MjIyQzE1LjkwOTMgMTYuMDAxNiAxNS45MDk5IDE1LjMyMDQgMTYuMzMwNSAxNC44OTk5Wk0yMS42NjEgMTUuNjYxNEMyMS4zNDU2IDE1Ljk3NjggMjAuODM0MiAxNS45NzY4IDIwLjUxODcgMTUuNjYxNEMyMC4yMDMzIDE1LjM0NiAyMC4yMDMzIDE0LjgzNDYgMjAuNTE4NyAxNC41MTkxQzIwLjgzNDIgMTQuMjAzNyAyMS4zNDU2IDE0LjIwMzcgMjEuNjYxIDE0LjUxOTFDMjEuOTc2NCAxNC44MzQ2IDIxLjk3NjQgMTUuMzQ2IDIxLjY2MSAxNS42NjE0WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTE3LjA5MiAxNy45NDU5TDE4LjQyNDYgMTkuMjc4NUMxOC42MzQ5IDE5LjQ4ODggMTguNjM0OSAxOS44Mjk3IDE4LjQyNDYgMjAuMDRMMTcuMDU5MyAyMS40MDUzQzE2Ljk1ODQgMjEuNTA2MyAxNi44MjE0IDIxLjU2MyAxNi42Nzg2IDIxLjU2M0gxNS4zNzg2VjIyLjg2M0MxNS4zNzg2IDIzLjAwNTggMTUuMzIxOSAyMy4xNDI3IDE1LjIyMDkgMjMuMjQzN0wxNS4xNTU2IDIzLjMwOUMxNS4wNTQ2IDIzLjQxIDE0LjkxNzYgMjMuNDY2OCAxNC43NzQ4IDIzLjQ2NjhIMTMuNDc0OVYyNC43NjY3QzEzLjQ3NDkgMjQuOTA5NSAxMy40MTgxIDI1LjA0NjUgMTMuMzE3MiAyNS4xNDc1TDEyLjg3MTEgMjUuNTkzNUMxMi43NzAxIDI1LjY5NDUgMTIuNjMzMSAyNS43NTEzIDEyLjQ5MDMgMjUuNzUxM0gxMS4zMjVDMTEuMjM4OCAyNS43NTEzIDExLjE1NjEgMjUuNzE3IDExLjA5NTIgMjUuNjU2MUMxMS4wMzQyIDI1LjU5NTEgMTEgMjUuNTEyNSAxMSAyNS40MjYzVjIzLjY1NzFMMTUuMTg4MiAxOS40Njg5QzE0Ljk3OCAxOS4yNTg2IDE0Ljk3OCAxOC45MTc3IDE1LjE4ODIgMTguNzA3NEwxNS45NDk3IDE3Ljk0NTlDMTYuMjY1MiAxNy42MzA1IDE2Ljc3NjYgMTcuNjMwNSAxNy4wOTIgMTcuOTQ1OVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNS42MTUyNiAxNy4wNTY0VjE5LjMwMDJDNS42MTUyNiAyMC43MzA5IDUuNjE2ODIgMjEuNjk0NiA1LjY4NTA2IDIyLjQ1MjJDNS43NTAzMSAyMy4xNzY1IDUuODY4ODYgMjMuNTk0MiA2LjA0MzgxIDIzLjkzNDVDNi4yMTg3NyAyNC4yNzQ5IDYuNDg4MzggMjQuNjEyMyA3LjAzNjA3IDI1LjA4MDRDNy42MDg4NiAyNS41NyA4LjM4NjI5IDI2LjEyMTcgOS41NDE2OCAyNi45Mzg3TDExLjA0NTkgMjguMDAyNEwxMy41Nzc5IDI5LjQyMjRDMTQuODc2MiAzMC4xNTA1IDE1Ljc1MjYgMzAuNjQwMSAxNi40NzUgMzAuOTU4NUMxNy4xNjYgMzEuMjYzIDE3LjYwNCAzMS4zNTc5IDE3Ljk5OTkgMzEuMzU3OUMxOC4zOTU3IDMxLjM1NzkgMTguODMzNyAzMS4yNjMgMTkuNTI0NyAzMC45NTg1QzIwLjI0NzIgMzAuNjQwMSAyMS4xMjM1IDMwLjE1MDUgMjIuNDIxOSAyOS40MjI0TDI0Ljk1MzkgMjguMDAyNEwyNi40NTgxIDI2LjkzODdDMjcuNjEzNSAyNi4xMjE3IDI4LjM5MDkgMjUuNTcgMjguOTYzNyAyNS4wODA0QzI5LjUxMTQgMjQuNjEyMyAyOS43ODEgMjQuMjc0OSAyOS45NTU5IDIzLjkzNDVDMzAuMTMwOSAyMy41OTQyIDMwLjI0OTQgMjMuMTc2NSAzMC4zMTQ3IDIyLjQ1MjJDMzAuMzgyOSAyMS42OTQ2IDMwLjM4NDUgMjAuNzMwOSAzMC4zODQ1IDE5LjMwMDJWMTcuMDU2NEMzMC4zODQ1IDE1LjYyNTcgMzAuMzgyOSAxNC42NjE5IDMwLjMxNDcgMTMuOTA0NEMzMC4yNDk0IDEzLjE4IDMwLjEzMDkgMTIuNzYyMyAyOS45NTU5IDEyLjQyMkMyOS43ODEgMTIuMDgxNiAyOS41MTE0IDExLjc0NDIgMjguOTYzNyAxMS4yNzYxQzI4LjM5MDkgMTAuNzg2NSAyNy42MTM1IDEwLjIzNDkgMjYuNDU4MSA5LjQxNzgzTDI0LjkxOTggOC4zMzAwNEwyMi44MjczIDcuMDA5OEMyMS40MTc3IDYuMTIwNDIgMjAuNDYzNCA1LjUyMDc0IDE5LjY3MzQgNS4xMzA3QzE4LjkxNzMgNC43NTczMyAxOC40MzY4IDQuNjQyMDYgMTcuOTk5OSA0LjY0MjA2QzE3LjU2MyA0LjY0MjA2IDE3LjA4MjUgNC43NTczMyAxNi4zMjYzIDUuMTMwN0MxNS41MzYzIDUuNTIwNzQgMTQuNTgyMSA2LjEyMDQyIDEzLjE3MjUgNy4wMDk4TDExLjA4IDguMzMwMDNMOS41NDE2NyA5LjQxNzgzQzguMzg2MjkgMTAuMjM0OSA3LjYwODg2IDEwLjc4NjUgNy4wMzYwNyAxMS4yNzYxQzYuNDg4MzggMTEuNzQ0MiA2LjIxODc3IDEyLjA4MTYgNi4wNDM4MSAxMi40MjJDNS44Njg4NiAxMi43NjIzIDUuNzUwMzEgMTMuMTggNS42ODUwNiAxMy45MDQ0QzUuNjE2ODIgMTQuNjYxOSA1LjYxNTI2IDE1LjYyNTcgNS42MTUyNiAxNy4wNTY0Wk0xMC4xOTIyIDYuOTU3NTNMMTIuMzIwNiA1LjYxNDY0QzE1LjA4MzMgMy44NzE1NSAxNi40NjQ2IDMgMTcuOTk5OSAzQzE5LjUzNTEgMyAyMC45MTY1IDMuODcxNTUgMjMuNjc5MiA1LjYxNDY0TDI1LjgwNzYgNi45NTc1M0wyNy4zODA2IDguMDY5ODZDMjkuNjQzOCA5LjY3MDI5IDMwLjc3NTQgMTAuNDcwNSAzMS4zODc3IDExLjY2MTVDMzEuOTk5OSAxMi44NTI1IDMxLjk5OTkgMTQuMjUzOCAzMS45OTk5IDE3LjA1NjRWMTkuMzAwMkMzMS45OTk5IDIyLjEwMjcgMzEuOTk5OSAyMy41MDQgMzEuMzg3NyAyNC42OTVDMzAuNzc1NCAyNS44ODYgMjkuNjQzOCAyNi42ODYyIDI3LjM4MDYgMjguMjg2N0wyNS44MDc2IDI5LjM5OUwyMy4yMDIzIDMwLjg2MDFDMjAuNjU4NiAzMi4yODY3IDE5LjM4NjggMzMgMTcuOTk5OSAzM0MxNi42MTMgMzMgMTUuMzQxMiAzMi4yODY3IDEyLjc5NzUgMzAuODYwMUwxMC4xOTIyIDI5LjM5OUw4LjYxOTE3IDI4LjI4NjZDNi4zNTU5MyAyNi42ODYyIDUuMjI0MzEgMjUuODg2IDQuNjEyMDkgMjQuNjk1QzMuOTk5ODggMjMuNTA0IDMuOTk5ODggMjIuMTAyNyAzLjk5OTg4IDE5LjMwMDJWMTcuMDU2NEMzLjk5OTg4IDE0LjI1MzggMy45OTk4OCAxMi44NTI1IDQuNjEyMDkgMTEuNjYxNUM1LjIyNDMxIDEwLjQ3MDUgNi4zNTU5NCA5LjY3MDI5IDguNjE5MTggOC4wNjk4NkwxMC4xOTIyIDYuOTU3NTNaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K' : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAcKSURBVHgBtVh5VFRVGP/dN8PMMIBMCOLRQswkRFHJJe1UjuVJMyvotGBFQh5KRCuKczKtzHY7ndTTaqdSOi0cLcXMylJxwX0BFxT3ccWFZZRFZuHd7r0DOPPmzWb2++cN73734/fu9333/r5LECIMyV8mEtKaTqhmAAU1E0JNoMQkBgm1QiYWClggyesodZS0VBVYQnAPEqyhIfnzbAIygU0wIxRQVDDS85qq8hcGYx6QkCH5UzOBZgEzTMR/g4WAzgpEzCchU+Ick0Ovn0kJXsJ1BPM3V3/FNstqKbAiWEKG5DmJEtUvZaMD8f/AIsM2Ui2/iBoZAn1poBB1i49A7vi+GP9gErp2McLZSlF5qBbL/j6G4t8O4+yFJlwLKQ9CPEw2g77cH5noKB3ynk5FXlY/RBh1WL/tDMorL4LKQFq/ONx7x404eaYB3y06gB+WVqGmvgU+wRJeZ7ONdA+fB6GIPl/MoVQ9ZySJ4JExvTBjymD06B6FZauO4+P5u1B5uM7DblBqF7z7yjAMv60rTp5twAdf7MTPyw7BJyeWU1cOTC7wIsTLWgJZoDZpYEos5s28GwPYs2xHNQrf3YCqY1b4w/iHeuO1yYOR0C0KG9mcvBmljGCjqq2MVha6qWv5b037y7DYcUsZO5PSeMaUIZj//khoNASF72/E9I82+w9DG/YdrMNXP1ZCq5Ew5u4ETMpKRVOzEzv2XPCyJZDMjpoV8zoIta1OttJwzpt3If+ZVCxacQSZU1Ziu4qzQFi/7SyWrDzGVjkOE59IwaUGuxopky527AlHzR8VEv9LouRFpUX6fTcj57E+mP3VTjw/vRT1l22B/jdenpiGHb89gakT+nu857n0cO7vKF5+GG8VDEV8nNFrrgxpAn9qRJkT7YdKAx4mWZaRVfAPZIqA4Pny+tQh6HyDAffccZPYBjbtrPaw2VJ+HlOz+0MiBKWbT3uM8crWxGUsZGNh6Urnep1GJPCSv44Jx/7AfGM6IzMtb5D4u7pt/3mDkSvMTfOwram/gv1srxpr7qHuC/YMicjaEcoBvulxlO+/iEAozL0Nr7aRKdt+FoPGFeOXP4+6SL0wFL0Soj3s17GciosNV/VFKBnAckhOVA7ExhjEs7bOfzXxyuObZDu6xBqRmtwZt/Z0FauV5V1zi9NjTk3dFXSK1EEXJql4pGaJZXSi8jUPGYezVYY/tLJwpj+/ArVt20ASI7Ly+3RBqqHJjtHPLOsIYTusl+3iGd1J7+2QwCR1iCs3hGldhBoaHfCHh0b1xILZ92La7E0dpMQ8RmbUUyWoOlrv/RGy6yMjwrXeDiknpArqMdkXCp4diF49ovHWi0Px8de74HTIuNxohzlziSoZ4bmtRsL1WtVxrZCdilWy211EoqP08IVpkwYhrW+c+N1ic2L56uP4e8NJGI1hOGK55HOeJLnWQHVfI7BqKSVW5ZHBv9IfobcLbscL2QOwgVXVqrJTYievvhhQbgjEROvEs1b9+LFoGZl1UMgNvr1z3GDyJvRe4XDkZ7kqi6/Ip0V7EAq6xUeisckBh9M7HahMTrBTQ65QDpyvaRblmnJLjMf70XclCDIXapvx3GtrUPRrFUIFlyeHj/tQChJdK1GqKVG+t9lbUbatGk9n3CoEWTviYlwb2sGjViz640hHaINFz5s6YUj/Lijdclp1nKVPidBD4clfMslKze6DfZM6Y/2iR7Bw8QEUflAmqoOT6941UijCxmYHQgH/mOXfjhOnwPCMxThzTpFzBBXNByaniZSnkIuUDrg+njVvGyZmpmBryeNMaEWK3NrPFGKoZG4f2BUbf31U7OCTmHLwIsP5UHpVDzmZDtHGPpCtrLatFedx4sxlPPZAb6YAk6BhJbul/ByChdEQhneYnJ375p2oY1WV9fI/WL1RNVyW5qr8nA5CHNrYMbuZcstWWnLlt5iVdUrvGNFlZIy+mYWsEUdPXvJLJjezL77/ZBTMw7rjm+L9yClcjUM+kpmlSwEXZ67fbojo8/lcqiLW2sF18rQ8JvJvjMKaTaeZRN3LSv9Ux7iRHQf3j+jBiPfDsLR4bGX6573PtgvV6As8VE0H81+6Ss4NvA2y6/Wl/hrEGJMBTz6chOfYaiWw7mPH3gus6uqFLhrcP14csCdON2D+T/vEttDkP98supbwNKslR70N4hC3G6ABG8UIdkQ8OrYXMlmjyCtHw9qkvQdrRY58U1yJIMAaRcK6jTyL+0sfrXRwpK4ZrEGUCclQkuHQqNk7a1ZYIyNHFclhGrYTkmEevmgQAtsdLJbuX81zRmcz5jQeyVUt1yCuY8S90MzrcR3DGsKc9obQF0K6sBLtUog3Imw91zIVWHTdLqy8ibmu9NhJOILIbNW4BHa70nPJGbKWktbd/JxUyxN/+BdHDte5gKLXDAAAAABJRU5ErkJggg==';
	  }
	  if (provider.code === ProviderCode.external && main_core.Type.isStringFilled(provider.iconUrl)) {
	    return provider.iconUrl;
	  }
	  return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAANQSURBVHgBxZi/b9NAFMffnZOaFgopEkggEK5AohKgJKgLIETzD6B2YmAo3boBAyv9sXaAbh2QCgMzVQbWpELQBYErFpCQcAUqPyogoqipm9jHe5fYikMs2/n5lZxcchffJ+/dvXfPDCJqZK2o9VnWOFOUJAgxhlcCGEvITiEK2DaAgyHK9iovlVb0zJAR4fbAwg688PLvLc6VSQkRTbot7MV3Vw48DjM4EOj8i+0xRVGWsalBazIQbC4IzBcolfudsFV1BgfcgTZKADzkpjmHrixAWCCE0YSqPqMmdEYGM81Mo/XFfGBy0LqLmoJidTAJhHnbBRhHehXKdR+v7aU100UYUqo6pyvXQnJbM74MPRCzrIx+dTBPbddCCDMDPZKohJUKB72QdaANrpo+GYfnF/fBcTV0vHWkVRkqQJyx29CiCGb6REzCPDqnRoaSWYDeaZujF1uKNw6MI4IZPcij3QRTUvqNOMXteHwcWlA9DGnpcwmyWxZElbVbnOAsxq9Bk/KDWfpShmbEGEtysIUGTajdMFLoNo7ZToOI6ghMRQnuHq56D1MFiqCbx2KdhJHi8tgZQrSVc78s+LAjOgaDKsTQZQQU6DayzughBe6+N+HBiAq5n+V2w5AMLkCshhlJMGcHGMyfjsON9V14/ceG+TN9nohMwdCJ1o6uH1HkFUbIshHDba9j7pgMGpz9XpLuIhCa0IHJDHFY2CjDIM55T4tXII7GYMEoyc8OXHarCMFieZbCskYI8QlCiqzgwNCayhz2/vvtsoDBGPN8Jtc+/RrsXgyMw/KXybViLkx5Q+6if79pCrj/cU9ai1xEoYAmJqvUujL7A0G+WbIvhPT1ywNpCZTC1C9CHs5oHW3ugWcSmnzbgrATNxQT9pSOJZJr2+SrHXKbBr2RgdYZpoYbGPEYOQU9Elpnzmm7QHSmFVjyQpeFMIt6TTXrSR18rzSL0UCH7smAgf2ztV94gKg+YoxPyIFdgMFtntHTrOALJKEu9cuBHYbSJQzOVd/RMNtLKNNMd2JN0Zph/f0NYWR/0A2qMaodFa1BO9kpCP0UulapgFG5FLlCyaNVnujtemD1HxjmPsBHeoJTcYDncToCex/p0ZVHa6yDoqz4ucZP/wB0m3kbYruWeAAAAABJRU5ErkJggg==';
	}
	function _getEntityBadges2(provider) {
	  if (provider.code === ProviderCode.goskey && babelHelpers.classPrivateFieldLooseBase(this, _isProviderExpired)[_isProviderExpired](provider)) {
	    return [{
	      title: main_core.Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED'),
	      textColor: 'var(--ui-color-palette-white-base)',
	      bgColor: 'var(--ui-color-palette-orange-60)'
	    }];
	  }
	  return [];
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4$1,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8$1,
	  _t9$1,
	  _t10$1,
	  _t11$1;
	const ProviderCode = Object.freeze({
	  goskey: 'goskey',
	  sesCom: 'ses-com',
	  sesRuExpress: 'ses-ru-express',
	  sesRu: 'ses-ru',
	  external: 'external'
	});
	const HelpdeskCodes = Object.freeze({
	  HowToChooseProvider: '19740650',
	  GoskeyDetails: '19740688',
	  SesRuDetails: '19740668',
	  SesComDetails: '19740668',
	  SesRuExpressDetails: '26311976',
	  TaxcomDetails: '19740696',
	  GoskeyApiKey: '19740816'
	});
	var _api$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _layoutCache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutCache");
	var _companyList$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyList");
	var _reloadDelayForHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reloadDelayForHide");
	var _company$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("company");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _showTaxId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showTaxId");
	var _options$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _loadPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadPromise");
	var _providerSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("providerSelector");
	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _getInfoLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoLayout");
	var _getInfoRqBtnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoRqBtnLayout");
	var _getInfoEditBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoEditBtn");
	var _getInfoTitleLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoTitleLayout");
	var _getInfoHeaderTitleNameLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoHeaderTitleNameLayout");
	var _getInfoRqInnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoRqInnLayout");
	var _getSelectorBtnLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorBtnLayout");
	var _onSelectorBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelectorBtnClick");
	var _getSelectorLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorLayout");
	var _setEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEmptyState");
	var _setInfoState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setInfoState");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _getDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDialog");
	var _selectProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectProvider");
	var _onProviderDeselect$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProviderDeselect");
	var _onProviderDisconnect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProviderDisconnect");
	var _onProviderSelect$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProviderSelect");
	var _onCompanySelectedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCompanySelectedHandler");
	var _refreshView = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refreshView");
	var _updateDialogItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateDialogItems");
	var _getCompanyById$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyById");
	var _onCompanyDeselectedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCompanyDeselectedHandler");
	var _showEditMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showEditMenu");
	var _createCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createCompany");
	var _editCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editCompany");
	var _getDefaultSchemeByProviderCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDefaultSchemeByProviderCode");
	var _getCompanyInfoLabelLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyInfoLabelLayout");
	var _getCompanySaveAndEditRequireCrmPermissionLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanySaveAndEditRequireCrmPermissionLayout");
	var _onInfoDropDownBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onInfoDropDownBtnClick");
	var _getCurrentProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentProvider");
	class CompanySelector extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    var _options$documentInit;
	    super();
	    Object.defineProperty(this, _getCurrentProvider, {
	      value: _getCurrentProvider2
	    });
	    Object.defineProperty(this, _onInfoDropDownBtnClick, {
	      value: _onInfoDropDownBtnClick2
	    });
	    Object.defineProperty(this, _getCompanySaveAndEditRequireCrmPermissionLayout, {
	      value: _getCompanySaveAndEditRequireCrmPermissionLayout2
	    });
	    Object.defineProperty(this, _getCompanyInfoLabelLayout, {
	      value: _getCompanyInfoLabelLayout2
	    });
	    Object.defineProperty(this, _getDefaultSchemeByProviderCode, {
	      value: _getDefaultSchemeByProviderCode2
	    });
	    Object.defineProperty(this, _editCompany, {
	      value: _editCompany2
	    });
	    Object.defineProperty(this, _createCompany, {
	      value: _createCompany2
	    });
	    Object.defineProperty(this, _showEditMenu, {
	      value: _showEditMenu2
	    });
	    Object.defineProperty(this, _onCompanyDeselectedHandler, {
	      value: _onCompanyDeselectedHandler2
	    });
	    Object.defineProperty(this, _getCompanyById$1, {
	      value: _getCompanyById2$1
	    });
	    Object.defineProperty(this, _updateDialogItems, {
	      value: _updateDialogItems2
	    });
	    Object.defineProperty(this, _refreshView, {
	      value: _refreshView2
	    });
	    Object.defineProperty(this, _onCompanySelectedHandler, {
	      value: _onCompanySelectedHandler2
	    });
	    Object.defineProperty(this, _onProviderSelect$1, {
	      value: _onProviderSelect2$1
	    });
	    Object.defineProperty(this, _onProviderDisconnect, {
	      value: _onProviderDisconnect2
	    });
	    Object.defineProperty(this, _onProviderDeselect$1, {
	      value: _onProviderDeselect2$1
	    });
	    Object.defineProperty(this, _selectProvider, {
	      value: _selectProvider2
	    });
	    Object.defineProperty(this, _getDialog, {
	      value: _getDialog2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _setInfoState, {
	      value: _setInfoState2
	    });
	    Object.defineProperty(this, _setEmptyState, {
	      value: _setEmptyState2
	    });
	    Object.defineProperty(this, _getSelectorLayout, {
	      value: _getSelectorLayout2
	    });
	    Object.defineProperty(this, _onSelectorBtnClick, {
	      value: _onSelectorBtnClick2
	    });
	    Object.defineProperty(this, _getSelectorBtnLayout, {
	      value: _getSelectorBtnLayout2
	    });
	    Object.defineProperty(this, _getInfoRqInnLayout, {
	      value: _getInfoRqInnLayout2
	    });
	    Object.defineProperty(this, _getInfoHeaderTitleNameLayout, {
	      value: _getInfoHeaderTitleNameLayout2
	    });
	    Object.defineProperty(this, _getInfoTitleLayout, {
	      value: _getInfoTitleLayout2
	    });
	    Object.defineProperty(this, _getInfoEditBtn, {
	      value: _getInfoEditBtn2
	    });
	    Object.defineProperty(this, _getInfoRqBtnLayout, {
	      value: _getInfoRqBtnLayout2
	    });
	    Object.defineProperty(this, _getInfoLayout, {
	      value: _getInfoLayout2
	    });
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _subscribeOnEvents, {
	      value: _subscribeOnEvents2
	    });
	    this.events = {
	      onCompaniesLoad: 'onCompaniesLoad',
	      onSelect: 'onSelect',
	      onProviderSelect: 'onProviderSelect'
	    };
	    Object.defineProperty(this, _api$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layoutCache$1, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _companyList$1, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _reloadDelayForHide, {
	      writable: true,
	      value: 1000
	    });
	    Object.defineProperty(this, _company$1, {
	      writable: true,
	      value: {
	        id: null,
	        provider: {
	          code: null,
	          uid: null,
	          timestamp: null
	        }
	      }
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _showTaxId$1, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _options$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadPromise, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _providerSelector, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.CompanySelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector] = new ProviderSelector({
	      region: options.region,
	      documentInitiatedType: (_options$documentInit = options.documentInitiatedType) != null ? _options$documentInit : sign_type.DocumentInitiated.company
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _setEmptyState)[_setEmptyState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _loadPromise)[_loadPromise] = babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEvents)[_subscribeOnEvents]();
	  }
	  async load(companyUid, companyEntityId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadPromise)[_loadPromise];
	    const company = babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].find(({
	      id,
	      providers
	    }) => {
	      if (!main_core.Type.isNull(companyEntityId) && companyEntityId > 0) {
	        return companyEntityId === id;
	      }
	      return providers.some(({
	        uid
	      }) => {
	        return uid === companyUid;
	      });
	    });
	    if (main_core.Type.isUndefined(company)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id = company.id;
	    babelHelpers.classPrivateFieldLooseBase(this, _updateDialogItems)[_updateDialogItems]();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectProvider)[_selectProvider](companyUid);
	  }
	  async loadFirstCompany() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadPromise)[_loadPromise];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].length === 0) {
	      return;
	    }
	    const company = babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1][0];
	    babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id = company.id;
	    babelHelpers.classPrivateFieldLooseBase(this, _updateDialogItems)[_updateDialogItems]();
	    if (company.providers.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _selectProvider)[_selectProvider](company.providers[0].uid);
	    }
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1] = {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1],
	      ...options
	    };
	  }
	  getProviderLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].getLayout();
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('layout', () => {
	      const requireCrmPermissionLayout = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].needOpenCrmSaveAndEditCompanySliders ? babelHelpers.classPrivateFieldLooseBase(this, _getCompanySaveAndEditRequireCrmPermissionLayout)[_getCompanySaveAndEditRequireCrmPermissionLayout]() : '';
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _showTaxId$1)[_showTaxId$1]) {
	        hide(babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqInnLayout)[_getInfoRqInnLayout]());
	      }
	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div>
					<div class="sign-document-b2e-company">
						${0}
						${0}
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getSelectorLayout)[_getSelectorLayout](), babelHelpers.classPrivateFieldLooseBase(this, _getInfoLayout)[_getInfoLayout](), requireCrmPermissionLayout);
	    });
	  }
	  getSelectedCompanyProvider() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentProvider)[_getCurrentProvider]()) != null ? _babelHelpers$classPr : null;
	  }
	  selectCompany(id) {
	    var _company$providers, _babelHelpers$classPr2;
	    const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1](id);
	    if (main_core.Type.isUndefined(company)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id = company.id;
	    babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].provider = null;
	    if ((company == null ? void 0 : (_company$providers = company.providers) == null ? void 0 : _company$providers.length) > 0) {
	      var _company$providers$fi, _company$providers2;
	      const filteredProviders = (_company$providers$fi = (_company$providers2 = company.providers) == null ? void 0 : _company$providers2.filter(provider => allowedSignatureProviders.includes(provider.code))) != null ? _company$providers$fi : [];
	      if (filteredProviders.length > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].provider = filteredProviders[0];
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setCompany(babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1]);
	    babelHelpers.classPrivateFieldLooseBase(this, _refreshView)[_refreshView]();
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]().getItems().find(item => item.id === babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id)) == null ? void 0 : _babelHelpers$classPr2.select();
	    this.emit(this.events.onSelect, {
	      companyId: babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id
	    });
	    const event = new main_core_events.BaseEvent({
	      data: {
	        companyId: babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id,
	        provider: this.getSelectedCompanyProvider()
	      }
	    });
	    this.emit(this.events.onSelect, event);
	  }
	  getCompanyId() {
	    var _babelHelpers$classPr3;
	    return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1]) == null ? void 0 : _babelHelpers$classPr3.id;
	  }
	  validate() {
	    var _babelHelpers$classPr4;
	    main_core.Dom.removeClass(this.getLayout(), '--invalid');
	    main_core.Dom.removeClass(this.getProviderLayout().firstElementChild, '--invalid');
	    const isProviderValid = babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].validate();
	    const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1]((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id) != null ? _babelHelpers$classPr4 : 0);
	    const isCompanyValid = main_core.Type.isObject(company) && company.id > 0 && company.rqInn > 0;
	    const isValid = isCompanyValid && isProviderValid;
	    if (!isCompanyValid) {
	      main_core.Dom.addClass(this.getLayout(), '--invalid');
	    } else if (!isProviderValid) {
	      main_core.Dom.addClass(this.getProviderLayout().firstElementChild, '--invalid');
	    }
	    return isValid;
	  }
	  async save(documentId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].registerVirtualProviderIfNeed();
	    const provider = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentProvider)[_getCurrentProvider]();
	    if (main_core.Type.isNull(provider)) {
	      return Promise.reject();
	    }
	    if (main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id)) {
	      return Promise.reject();
	    }
	    return Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].modifyB2eCompany(documentId, provider.uid, babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id), babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].modifyB2eDocumentScheme(documentId, babelHelpers.classPrivateFieldLooseBase(this, _getDefaultSchemeByProviderCode)[_getDefaultSchemeByProviderCode](provider.code))]);
	  }

	  /**
	   * This method is required for backward compatibility.
	   * It should be removed once the SES RU provider supports the default scheme.
	   *
	   * @param {ProviderCodeType} provider - The provider code.
	   * @returns {Scheme} - The signing scheme for the given provider.
	   */

	  setInitiatedByType(initiatedByType) {
	    this.setOptions({
	      documentInitiatedType: initiatedByType
	    });
	  }
	  async reloadCompanyProviders(selectFirst = true) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	    if (selectFirst) {
	      await this.loadFirstCompany();
	    }
	  }
	}
	function _subscribeOnEvents2() {
	  const providerSelectorEvents = babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].events;
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].subscribe(providerSelectorEvents.onProviderSelect, event => {
	    babelHelpers.classPrivateFieldLooseBase(this, _onProviderSelect$1)[_onProviderSelect$1](event.getData().provider.id);
	    this.emit(this.events.onProviderSelect, {
	      provider: this.getSelectedCompanyProvider(event)
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].subscribe(providerSelectorEvents.onProviderDeselect, () => babelHelpers.classPrivateFieldLooseBase(this, _onProviderDeselect$1)[_onProviderDeselect$1]());
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].subscribe(providerSelectorEvents.providerConnectionSlider.onClose, () => babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]());
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].subscribe(providerSelectorEvents.onProviderDisconnect, babelHelpers.classPrivateFieldLooseBase(this, _onProviderDisconnect)[_onProviderDisconnect].bind(this));
	}
	function _showLoader2() {
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _getInfoLayout)[_getInfoLayout]());
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _getSelectorLayout)[_getSelectorLayout]());
	  babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show(this.getLayout());
	}
	function _hideLoader2() {
	  show(babelHelpers.classPrivateFieldLooseBase(this, _getSelectorLayout)[_getSelectorLayout]());
	  babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().hide();
	}
	function _getInfoLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoLayout', () => {
	    return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<div class="sign-document-b2e-company-info">
					<div class="sign-document-b2e-company-info-img"></div>
					${0}
					${0}
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getInfoTitleLayout)[_getInfoTitleLayout](), babelHelpers.classPrivateFieldLooseBase(this, _getInfoEditBtn)[_getInfoEditBtn](), babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqBtnLayout)[_getInfoRqBtnLayout]());
	  });
	}
	function _getInfoRqBtnLayout2() {
	  var _babelHelpers$classPr5;
	  if (!((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].canEditCompany) != null ? _babelHelpers$classPr5 : true)) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoRqBtnLayout', () => {
	    return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
				<button class="ui-btn ui-btn-xs ui-btn-round ui-btn-success" onclick="${0}">
					${0}
				</button>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _editCompany)[_editCompany](), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_CHANGE_INN_1'));
	  });
	}
	function _getInfoEditBtn2() {
	  var _babelHelpers$classPr6;
	  if (!((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].canEditCompany) != null ? _babelHelpers$classPr6 : true)) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoEditBtn', () => {
	    return main_core.Tag.render(_t4$1 || (_t4$1 = _$1`
				<div class="sign-document-b2e-company-info-edit" onclick="${0}"></div>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _showEditMenu)[_showEditMenu]());
	  });
	}
	function _getInfoTitleLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoTitleLayout', () => {
	    return main_core.Tag.render(_t5$1 || (_t5$1 = _$1`
				<div class="sign-document-b2e-company-info-title">
					<div class="sign-document-b2e-company-info-header">
						${0}
						${0}
						<div class="sign-document-b2e-company-info-dropdown-btn"
							onclick="${0}">
						</div>
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getInfoHeaderTitleNameLayout)[_getInfoHeaderTitleNameLayout](), babelHelpers.classPrivateFieldLooseBase(this, _getCompanyInfoLabelLayout)[_getCompanyInfoLabelLayout](), () => babelHelpers.classPrivateFieldLooseBase(this, _onInfoDropDownBtnClick)[_onInfoDropDownBtnClick](), babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqInnLayout)[_getInfoRqInnLayout]());
	  });
	}
	function _getInfoHeaderTitleNameLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoHeaderTitleNameLayout', () => {
	    return main_core.Tag.render(_t6$1 || (_t6$1 = _$1`
				<div class="sign-document-b2e-company-info-name"></div>
			`));
	  });
	}
	function _getInfoRqInnLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('infoRqInnLayout', () => {
	    return main_core.Tag.render(_t7$1 || (_t7$1 = _$1`
				<div class="sign-document-b2e-company-info-rq-inn"></div>
			`));
	  });
	}
	function _getSelectorBtnLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('selectorBtnLayout', () => {
	    return main_core.Tag.render(_t8$1 || (_t8$1 = _$1`
				<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-round" onclick="${0}">
					${0}
				</button>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _onSelectorBtnClick)[_onSelectorBtnClick](), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON'));
	  });
	}
	function _onSelectorBtnClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]().setTargetNode(this.getLayout());
	  babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]().show();
	}
	function _getSelectorLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('selectorLayout', () => {
	    return main_core.Tag.render(_t9$1 || (_t9$1 = _$1`
				<div class="sign-document-b2e-company-select">
					<span class="sign-document-b2e-company-select-text">
						${0}
					</span>
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_B2E_COMPANIES_NOT_CHANGED'), babelHelpers.classPrivateFieldLooseBase(this, _getSelectorBtnLayout)[_getSelectorBtnLayout]());
	  });
	}
	function _setEmptyState2() {
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _getInfoLayout)[_getInfoLayout]());
	  hide(this.getProviderLayout());
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getSelectorLayout)[_getSelectorLayout](), 'display', 'flex');
	}
	function _setInfoState2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getInfoLayout)[_getInfoLayout](), 'display', 'flex');
	  hide(babelHelpers.classPrivateFieldLooseBase(this, _getSelectorLayout)[_getSelectorLayout]());
	}
	async function _load2() {
	  var _babelHelpers$classPr7, _babelHelpers$classPr8;
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  const loadCompanyPromise = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].loadCompanyPromise) != null ? _babelHelpers$classPr7 : babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].loadB2eCompanyList((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].documentInitiatedType) != null ? _babelHelpers$classPr8 : sign_type.DocumentInitiated.company);
	  let data = null;
	  try {
	    data = await loadCompanyPromise;
	  } catch (error) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    console.error(error);
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	  if (main_core.Type.isObject(data.companies) && main_core.Type.isArray(data.companies)) {
	    var _data;
	    babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1] = data.companies;
	    babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setCompanyList(babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1]);
	    babelHelpers.classPrivateFieldLooseBase(this, _showTaxId$1)[_showTaxId$1] = Boolean((_data = data) == null ? void 0 : _data.showTaxId);
	    babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setShowTaxId(babelHelpers.classPrivateFieldLooseBase(this, _showTaxId$1)[_showTaxId$1]);
	    babelHelpers.classPrivateFieldLooseBase(this, _updateDialogItems)[_updateDialogItems]();
	    this.emit(this.events.onCompaniesLoad, {
	      companies: babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1]
	    });
	  }
	}
	function _getLoader2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new BX.Loader({
	    target: this.getLayout(),
	    mode: 'inline',
	    size: 40
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	}
	function _getDialog2() {
	  var _babelHelpers$classPr9;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	  }
	  let footer = null;
	  if ((_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].canCreateCompany) != null ? _babelHelpers$classPr9 : true) {
	    footer = main_core.Tag.render(_t10$1 || (_t10$1 = _$1`
				<span
					class="ui-selector-footer-link ui-selector-footer-link-add"
					onclick="${0}"
				>
					${0}
				</span>
			`), () => babelHelpers.classPrivateFieldLooseBase(this, _createCompany)[_createCompany](), main_core.Loc.getMessage('SIGN_B2E_ADD_COMPANY'));
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = new ui_entitySelector.Dialog({
	    targetNode: this.getLayout(),
	    width: 425,
	    height: 363,
	    items: babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].map(company => {
	      var _babelHelpers$classPr10;
	      return {
	        id: company.id,
	        entityId: 'b2e-company',
	        title: company.title,
	        tabs: 'b2e-companies',
	        deselectable: (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCompaniesDeselectable) != null ? _babelHelpers$classPr10 : true
	      };
	    }),
	    tabs: [{
	      id: 'b2e-companies',
	      title: main_core.Loc.getMessage('SIGN_B2E_COMPANIES_TAB')
	    }],
	    showAvatars: false,
	    dropdownMode: true,
	    multiple: false,
	    enableSearch: true,
	    events: {
	      'Item:OnSelect': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onCompanySelectedHandler)[_onCompanySelectedHandler](event);
	        babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	      },
	      'Item:OnDeselect': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onCompanyDeselectedHandler)[_onCompanyDeselectedHandler](event);
	        babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	      }
	    },
	    footer
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	}
	function _selectProvider2(id) {
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setProviderById(id);
	}
	function _onProviderDeselect2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].provider = null;
	}
	async function _onProviderDisconnect2(event) {
	  var _babelHelpers$classPr11;
	  const provider = event.getData().provider;
	  const id = provider.uid;
	  if (!id || provider.autoRegister) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1]((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id) != null ? _babelHelpers$classPr11 : 0);
	  try {
	    await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].deleteB2eCompany(id);
	  } catch (e) {
	    console.error(e);
	    return;
	  }
	  const newCompanyProviders = company.providers.filter(({
	    uid
	  }) => uid !== id);
	  babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].providers = newCompanyProviders;
	  babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1](babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id).providers = newCompanyProviders;
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setCompanyList(babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1]);
	  babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	  this.selectCompany(company.id);
	}
	function _onProviderSelect2$1(id) {
	  var _babelHelpers$classPr12;
	  const company = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1]((_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id) != null ? _babelHelpers$classPr12 : 0);
	  babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].provider = company.providers.find(provider => provider.uid === id);
	}
	function _onCompanySelectedHandler2(event) {
	  if (!event.data || event.data.length === 0) {
	    return;
	  }
	  const selectedItem = event.data.item;
	  if ((selectedItem == null ? void 0 : selectedItem.id) <= 0) {
	    return;
	  }
	  this.selectCompany(selectedItem == null ? void 0 : selectedItem.id);
	}
	function _refreshView2() {
	  var _babelHelpers$classPr13, _babelHelpers$classPr14;
	  const selectedItem = babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1]((_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1]) == null ? void 0 : _babelHelpers$classPr13.id);
	  if (!selectedItem) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getInfoHeaderTitleNameLayout)[_getInfoHeaderTitleNameLayout]().innerText = selectedItem.title;
	  show(babelHelpers.classPrivateFieldLooseBase(this, _getInfoEditBtn)[_getInfoEditBtn]());
	  show(babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqBtnLayout)[_getInfoRqBtnLayout]());
	  if (main_core.Type.isStringFilled(selectedItem.rqInn)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqInnLayout)[_getInfoRqInnLayout]().innerText = main_core.Loc.getMessage('SIGN_B2E_COMPANIES_INN', {
	      '%innValue%': main_core.Text.encode(selectedItem.rqInn)
	    });
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqInnLayout)[_getInfoRqInnLayout](), 'display', babelHelpers.classPrivateFieldLooseBase(this, _showTaxId$1)[_showTaxId$1] ? '' : 'none');
	    main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _getCompanyInfoLabelLayout)[_getCompanyInfoLabelLayout]());
	    hide(babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqBtnLayout)[_getInfoRqBtnLayout]());
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _getInfoRqInnLayout)[_getInfoRqInnLayout]().textContent = '';
	    main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _getCompanyInfoLabelLayout)[_getCompanyInfoLabelLayout]());
	    hide(babelHelpers.classPrivateFieldLooseBase(this, _getInfoEditBtn)[_getInfoEditBtn]());
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].setProvider(selectedItem.rqInn, babelHelpers.classPrivateFieldLooseBase(this, _getCompanyById$1)[_getCompanyById$1]((_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id) != null ? _babelHelpers$classPr14 : 0));
	  babelHelpers.classPrivateFieldLooseBase(this, _setInfoState)[_setInfoState]();
	}
	function _updateDialogItems2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]();
	  const item = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getItems().find(({
	    id
	  }) => id === babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id);
	  item == null ? void 0 : item.select();
	}
	function _getCompanyById2$1(id) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].find(company => id === company.id);
	}
	function _onCompanyDeselectedHandler2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].provider = {
	    key: null,
	    uid: null
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _setEmptyState)[_setEmptyState]();
	}
	function _showEditMenu2() {
	  const menu = new main_popup.Menu({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _getInfoEditBtn)[_getInfoEditBtn](),
	    cacheable: false
	  });
	  menu.addMenuItem({
	    text: main_core.Loc.getMessage('SIGN_B2E_COMPANIES_EDIT'),
	    onclick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _editCompany)[_editCompany]();
	      menu.close();
	    }
	  });
	  menu.show();
	}
	function _createCompany2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].needOpenCrmSaveAndEditCompanySliders) {
	    const companiesIdsBeforeSliderClose = new Set(babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].map(company => company.id));
	    BX.SidePanel.Instance.open('/crm/company/details/0/?mycompany=y', {
	      cacheable: false,
	      events: {
	        onClose: async () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	          await babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	          const newCompany = babelHelpers.classPrivateFieldLooseBase(this, _companyList$1)[_companyList$1].find(({
	            id
	          }) => !companiesIdsBeforeSliderClose.has(id));
	          if (!main_core.Type.isUndefined(newCompany)) {
	            this.selectCompany(newCompany.id);
	          }
	        }
	      }
	    });
	    return;
	  }
	  sign_v2_companyEditor.CompanyEditor.openSlider({
	    mode: sign_v2_companyEditor.CompanyEditorMode.Create,
	    documentEntityId: babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].entityId,
	    layoutTitle: main_core.Loc.getMessage('SIGN_B2E_COMPANY_CREATE'),
	    entityTypeId: sign_v2_companyEditor.DocumentEntityTypeId.B2e,
	    guid: sign_v2_companyEditor.EditorTypeGuid.B2e,
	    events: {
	      onCompanySavedHandler: companyId => {
	        babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id = companyId;
	      }
	    }
	  }, {
	    onCloseHandler: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	    }
	  });
	}
	function _editCompany2() {
	  if (!main_core.Type.isInteger(babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].needOpenCrmSaveAndEditCompanySliders) {
	    BX.SidePanel.Instance.open(`/crm/company/details/${babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id}/`, {
	      cacheable: false,
	      events: {
	        onClose: () => babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]()
	      }
	    });
	    return;
	  }
	  sign_v2_companyEditor.CompanyEditor.openSlider({
	    mode: sign_v2_companyEditor.CompanyEditorMode.Edit,
	    documentEntityId: babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].entityId,
	    companyId: babelHelpers.classPrivateFieldLooseBase(this, _company$1)[_company$1].id,
	    layoutTitle: main_core.Loc.getMessage('SIGN_B2E_COMPANY_EDIT'),
	    entityTypeId: sign_v2_companyEditor.DocumentEntityTypeId.B2e,
	    guid: sign_v2_companyEditor.EditorTypeGuid.B2e
	  }, {
	    onCloseHandler: () => babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]()
	  });
	}
	function _getDefaultSchemeByProviderCode2(provider) {
	  return provider === ProviderCode.sesRu && babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].documentInitiatedType === sign_type.DocumentInitiated.company ? sign_v2_b2e_schemeSelector.SchemeType.Order : sign_v2_b2e_schemeSelector.SchemeType.Default;
	}
	function _getCompanyInfoLabelLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('companyInfoLabel', () => {
	    return main_core.Tag.render(_t11$1 || (_t11$1 = _$1`
				<div class="ui-label ui-label-orange ui-label-fill sign-document-b2e-company-info-label">
					<div class="ui-label-inner">${0}</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_V2_B2E_COMPANY_SELECTOR_COMPANY_RQ_WARNING_LABEL'));
	  });
	}
	function _getCompanySaveAndEditRequireCrmPermissionLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache$1)[_layoutCache$1].remember('companySaveAndEditRequireCrmPermissionLayout', () => {
	    const alert = new ui_alerts.Alert({
	      text: main_core.Loc.getMessage('SIGN_V2_B2E_COMPANY_SELECTOR_SAVE_AND_EDIT_REQUIRE_CRM_PERMISSION'),
	      color: ui_alerts.AlertColor.WARNING,
	      size: ui_alerts.AlertSize.XS,
	      customClass: 'sign-document-b2e-company__alert'
	    });
	    return alert.render();
	  });
	}
	function _onInfoDropDownBtnClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]().setTargetNode(this.getLayout());
	  babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog]().show();
	}
	function _getCurrentProvider2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _providerSelector)[_providerSelector].getCurrentProvider();
	}

	exports.ProviderCode = ProviderCode;
	exports.HelpdeskCodes = HelpdeskCodes;
	exports.CompanySelector = CompanySelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX,BX.Cache,BX.Event,BX.Main,BX.Main,BX.Sign.Tour,BX.Sign,BX.Sign.V2,BX.UI,BX.UI.EntitySelector,BX.UI,BX.Sign.V2));
//# sourceMappingURL=company-selector.bundle.js.map

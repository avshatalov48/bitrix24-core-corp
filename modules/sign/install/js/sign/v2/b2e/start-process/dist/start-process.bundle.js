/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,main_core_events,main_loader,sign_v2_api,sign_v2_b2e_companySelector,sign_v2_b2e_signDropdown) {
	'use strict';

	let _ = t => t,
	  _t;
	const dropdownTemplateEntityId = 'sign-b2e-start-process-type';
	const dropdownProcessTabId = 'sign-b2e-start-process-types';
	var _resettableCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resettableCache");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _templatesList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templatesList");
	var _getProcessTypeLayoutLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProcessTypeLayoutLoader");
	var _getProcessTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProcessTypeDropdown");
	var _getCompanySelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanySelector");
	var _createProcessTypeDropdownItemByTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createProcessTypeDropdownItemByTemplate");
	var _getCompanySelectorLoadCompanyPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanySelectorLoadCompanyPromise");
	var _getUniqueCompanies = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUniqueCompanies");
	var _onCompaniesSelectorCompaniesLoad = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCompaniesSelectorCompaniesLoad");
	var _onCompanySelectorSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCompanySelectorSelect");
	class StartProcess extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _onCompanySelectorSelect, {
	      value: _onCompanySelectorSelect2
	    });
	    Object.defineProperty(this, _onCompaniesSelectorCompaniesLoad, {
	      value: _onCompaniesSelectorCompaniesLoad2
	    });
	    Object.defineProperty(this, _getUniqueCompanies, {
	      value: _getUniqueCompanies2
	    });
	    Object.defineProperty(this, _getCompanySelectorLoadCompanyPromise, {
	      value: _getCompanySelectorLoadCompanyPromise2
	    });
	    Object.defineProperty(this, _createProcessTypeDropdownItemByTemplate, {
	      value: _createProcessTypeDropdownItemByTemplate2
	    });
	    Object.defineProperty(this, _getCompanySelector, {
	      value: _getCompanySelector2
	    });
	    Object.defineProperty(this, _getProcessTypeDropdown, {
	      value: _getProcessTypeDropdown2
	    });
	    Object.defineProperty(this, _getProcessTypeLayoutLoader, {
	      value: _getProcessTypeLayoutLoader2
	    });
	    this.events = {
	      onProcessTypeSelect: 'onProcessTypeSelect'
	    };
	    Object.defineProperty(this, _resettableCache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    Object.defineProperty(this, _templatesList, {
	      writable: true,
	      value: babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.getList()
	    });
	    this.setEventNamespace('BX.V2.B2e.StartProcess');
	    void babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeLayoutLoader)[_getProcessTypeLayoutLoader]().show();
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _resettableCache)[_resettableCache].remember('layout', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div>
					<h1 class="sign-b2e-settings__header">${0}</h1>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${0}
						</p>
						${0}
					</div>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${0}
						</p>
						${0}
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_START_PROCESS_HEAD'), main_core.Loc.getMessage('SIGN_START_PROCESS_COMPANY'), babelHelpers.classPrivateFieldLooseBase(this, _getCompanySelector)[_getCompanySelector]().getLayout(), main_core.Loc.getMessage('SIGN_START_PROCESS_TYPE'), babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getLayout());
	    });
	  }
	  getSelectedTemplateUid() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getSelectedId();
	  }
	  getTemplates() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList];
	  }
	  getFields(templateUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.getFields(templateUid);
	  }
	  resetCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _resettableCache)[_resettableCache] = new main_core_cache.MemoryCache();
	  }
	}
	function _getProcessTypeLayoutLoader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _resettableCache)[_resettableCache].remember('processTypeLayoutLoader', () => new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getLayout()
	  }));
	}
	function _getProcessTypeDropdown2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _resettableCache)[_resettableCache].remember('processTypeDropdown', () => {
	    const signDropdown = new sign_v2_b2e_signDropdown.SignDropdown({
	      tabs: [{
	        id: dropdownProcessTabId,
	        title: ' '
	      }],
	      entities: [{
	        id: dropdownTemplateEntityId
	      }],
	      items: [],
	      isEnableSearch: true
	    });
	    signDropdown.subscribe(signDropdown.events.onSelect, event => this.emit(this.events.onProcessTypeSelect, event));
	    return signDropdown;
	  });
	}
	function _getCompanySelector2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _resettableCache)[_resettableCache].remember('companySelector', () => {
	    const companySelector = new sign_v2_b2e_companySelector.CompanySelector({
	      loadCompanyPromise: babelHelpers.classPrivateFieldLooseBase(this, _getCompanySelectorLoadCompanyPromise)[_getCompanySelectorLoadCompanyPromise](),
	      canCreateCompany: false,
	      canEditCompany: false,
	      isCompaniesDeselectable: false
	    });
	    companySelector.subscribe(companySelector.events.onCompaniesLoad, () => babelHelpers.classPrivateFieldLooseBase(this, _onCompaniesSelectorCompaniesLoad)[_onCompaniesSelectorCompaniesLoad]());
	    companySelector.subscribe(companySelector.events.onSelect, event => babelHelpers.classPrivateFieldLooseBase(this, _onCompanySelectorSelect)[_onCompanySelectorSelect](event));
	    return companySelector;
	  });
	}
	function _createProcessTypeDropdownItemByTemplate2(template) {
	  return {
	    id: template.uid,
	    title: template.title,
	    entityId: dropdownTemplateEntityId,
	    tabs: dropdownProcessTabId,
	    deselectable: false
	  };
	}
	async function _getCompanySelectorLoadCompanyPromise2() {
	  const uniqueCompanies = await babelHelpers.classPrivateFieldLooseBase(this, _getUniqueCompanies)[_getUniqueCompanies]();
	  const companySelectorCompanies = uniqueCompanies.map(({
	    id,
	    name,
	    taxId
	  }) => ({
	    id,
	    title: name,
	    rqInn: taxId
	  }));

	  // todo: get actual showTaxId
	  return {
	    companies: companySelectorCompanies,
	    showTaxId: true
	  };
	}
	async function _getUniqueCompanies2() {
	  const templates = await babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList];
	  const companies = templates.map(template => template.company);
	  const uniqCompanyIds = new Set(companies.map(({
	    id
	  }) => id));
	  return [...uniqCompanyIds].map(id => companies.find(company => company.id === id));
	}
	async function _onCompaniesSelectorCompaniesLoad2() {
	  var _lastUsedTemplate$com;
	  const companySelector = babelHelpers.classPrivateFieldLooseBase(this, _getCompanySelector)[_getCompanySelector]();
	  const templates = await babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList];
	  const lastUsedTemplate = templates.find(({
	    isLastUsed
	  }) => isLastUsed);
	  let selectedCompanyId = lastUsedTemplate == null ? void 0 : (_lastUsedTemplate$com = lastUsedTemplate.company) == null ? void 0 : _lastUsedTemplate$com.id;
	  if (main_core.Type.isUndefined(selectedCompanyId)) {
	    var _companies$at;
	    const companies = await babelHelpers.classPrivateFieldLooseBase(this, _getUniqueCompanies)[_getUniqueCompanies]();
	    selectedCompanyId = (_companies$at = companies.at(0)) == null ? void 0 : _companies$at.id;
	  }
	  if (main_core.Type.isUndefined(selectedCompanyId)) {
	    return;
	  }
	  companySelector.selectCompany(selectedCompanyId);
	}
	async function _onCompanySelectorSelect2(event) {
	  void babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeLayoutLoader)[_getProcessTypeLayoutLoader]().show();
	  const companyId = event.getData().companyId;
	  const templates = await babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList];
	  const processTypeItems = templates.filter(({
	    company
	  }) => company.id === companyId).map(template => babelHelpers.classPrivateFieldLooseBase(this, _createProcessTypeDropdownItemByTemplate)[_createProcessTypeDropdownItemByTemplate](template));
	  const signDropdown = babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]();
	  signDropdown.removeItems();
	  signDropdown.addItems(processTypeItems);
	  signDropdown.selectFirstItem();
	  void babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeLayoutLoader)[_getProcessTypeLayoutLoader]().hide();
	}

	exports.StartProcess = StartProcess;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX.Event,BX,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e));
//# sourceMappingURL=start-process.bundle.js.map

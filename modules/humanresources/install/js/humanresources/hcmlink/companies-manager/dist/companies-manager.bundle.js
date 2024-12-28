/* eslint-disable */
this.BX = this.BX || {};
this.BX.HumanResources = this.BX.HumanResources || {};
(function (exports,main_core,ui_sidepanel_layout,humanresources_hcmlink_companyConnectPage) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6;
	const maxCounterValue = 99;
	var _companies = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companies");
	var _root = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("root");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _getConnectCompanyList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getConnectCompanyList");
	var _getConnectCompanyItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getConnectCompanyItem");
	var _getConnectedCompanyList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getConnectedCompanyList");
	var _makeConnectedCompanyItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeConnectedCompanyItem");
	var _makeCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeCounter");
	var _reload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reload");
	var _connectCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("connectCompany");
	var _openCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openCompany");
	class CompaniesManager {
	  constructor(options) {
	    var _options$companies;
	    Object.defineProperty(this, _openCompany, {
	      value: _openCompany2
	    });
	    Object.defineProperty(this, _connectCompany, {
	      value: _connectCompany2
	    });
	    Object.defineProperty(this, _reload, {
	      value: _reload2
	    });
	    Object.defineProperty(this, _makeCounter, {
	      value: _makeCounter2
	    });
	    Object.defineProperty(this, _makeConnectedCompanyItem, {
	      value: _makeConnectedCompanyItem2
	    });
	    Object.defineProperty(this, _getConnectedCompanyList, {
	      value: _getConnectedCompanyList2
	    });
	    Object.defineProperty(this, _getConnectCompanyItem, {
	      value: _getConnectCompanyItem2
	    });
	    Object.defineProperty(this, _getConnectCompanyList, {
	      value: _getConnectCompanyList2
	    });
	    Object.defineProperty(this, _companies, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _root, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _companies)[_companies] = (_options$companies = options.companies) != null ? _options$companies : [];
	  }
	  renderTo(root) {
	    babelHelpers.classPrivateFieldLooseBase(this, _root)[_root] = root;
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t || (_t = _`
			<div class="hr-hcmlink-company-manager-container">
				<div class="hr-hcmlink-company-manager-header">
					<div class="hr-hcmlink-company-manager-header__title">
						${0}
					</div>
					<div class="hr-hcmlink-company-manager-header__hint">
						${0}
					</div>
				</div>
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_TITLE'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_HINT'), babelHelpers.classPrivateFieldLooseBase(this, _getConnectCompanyList)[_getConnectCompanyList](), babelHelpers.classPrivateFieldLooseBase(this, _companies)[_companies].length ? babelHelpers.classPrivateFieldLooseBase(this, _getConnectedCompanyList)[_getConnectedCompanyList]() : '');
	    if (BX.UI.Hint) {
	      BX.UI.Hint.init(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    }
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], babelHelpers.classPrivateFieldLooseBase(this, _root)[_root]);
	  }
	}
	function _getConnectCompanyList2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('connect-company-list', () => main_core.Tag.render(_t2 || (_t2 = _`
				<div class="hr-hcmlink-company-manager-list-container --connect">
					<div class="hr-hcmlink-company-manager-list__title">
						${0}
					</div>
					<div class="hr-hcmlink-company-manager-list">
						${0}
					</div>
				</div>
			`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_INTEGRATION_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _getConnectCompanyItem)[_getConnectCompanyItem]()));
	}
	function _getConnectCompanyItem2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('connect-company-item', () => {
	    const element = main_core.Tag.render(_t3 || (_t3 = _`
				<div class="hr-hcmlink-company-manager-list__item --connect">
					<div class="hr-hcmlink-company-manager-list__item-content">
						<div class="hr-hcmlink-company-manager-list__item-title">
							${0}
						</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_CONNECT'));
	    main_core.Event.bind(element, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _connectCompany)[_connectCompany]());
	    return element;
	  });
	}
	function _getConnectedCompanyList2() {
	  const elements = babelHelpers.classPrivateFieldLooseBase(this, _companies)[_companies].map(item => babelHelpers.classPrivateFieldLooseBase(this, _makeConnectedCompanyItem)[_makeConnectedCompanyItem](item));
	  return main_core.Tag.render(_t4 || (_t4 = _`
			<div class="hr-hcmlink-company-manager-list-container">
				<div class="hr-hcmlink-company-manager-list__title">
					${0}
				</div>
				<div class="hr-hcmlink-company-manager-list">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_MY_COMPANIES'), elements);
	}
	function _makeConnectedCompanyItem2(company) {
	  const item = main_core.Tag.render(_t5 || (_t5 = _`
			<div class="hr-hcmlink-company-manager-list__item">
				<div class="hr-hcmlink-company-manager-list__item-content">
					<div class="hr-hcmlink-company-manager-list__item-title">
						<div class="hr-hcmlink-company-manager-list__item-title-text"
							title="${0}"
						>
							${0}
						</div>
					</div>
				</div>
			</div>
		`), main_core.Text.encode(company.title), main_core.Text.encode(company.title));
	  if (company.notMappedCount > 0) {
	    let value = company.notMappedCount;
	    if (company.notMappedCount > maxCounterValue) {
	      value = maxCounterValue + '+';
	    }
	    main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _makeCounter)[_makeCounter](value), item);
	  }
	  main_core.Event.bind(item, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _openCompany)[_openCompany](company.id));
	  return item;
	}
	function _makeCounter2(value) {
	  return main_core.Tag.render(_t6 || (_t6 = _`
			<div class="hr-hcmlink-company-manager-list__item-counter ui-counter ui-counter-danger"
				data-hint="${0}"
				data-hint-no-icon
			>
    			<div class="ui-counter-inner">${0}</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_NOT_SYNCED_HINT'), value);
	}
	function _reload2() {
	  var _top$BX$SidePanel$Ins;
	  (_top$BX$SidePanel$Ins = top.BX.SidePanel.Instance.getSliderByWindow(window)) == null ? void 0 : _top$BX$SidePanel$Ins.reload();
	}
	function _connectCompany2() {
	  BX.SidePanel.Instance.open('humanresources:hcmlink-connect-slider', {
	    contentCallback: () => {
	      return ui_sidepanel_layout.Layout.createContent({
	        extensions: ['humanresources.hcmlink.company-connect-page'],
	        design: {
	          section: false,
	          margin: true
	        },
	        content() {
	          return new humanresources_hcmlink_companyConnectPage.CompanyConnectPage().getLayout();
	        },
	        buttons() {
	          return [];
	        }
	      });
	    },
	    animationDuration: 200,
	    width: 920,
	    cacheable: false,
	    events: {
	      onClose: () => babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]()
	    }
	  });
	}
	function _openCompany2(companyId) {
	  BX.SidePanel.Instance.open(`/bitrix/components/bitrix/humanresources.hcmlink.mapped.users/slider.php?entity_id=${companyId}`, {
	    cacheable: false,
	    width: 900,
	    animationDuration: 200,
	    events: {
	      onClose: () => babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]()
	    }
	  });
	}

	exports.CompaniesManager = CompaniesManager;

}((this.BX.HumanResources.HcmLink = this.BX.HumanResources.HcmLink || {}),BX,BX.UI.SidePanel,BX.Humanresources.Hcmlink));
//# sourceMappingURL=companies-manager.bundle.js.map

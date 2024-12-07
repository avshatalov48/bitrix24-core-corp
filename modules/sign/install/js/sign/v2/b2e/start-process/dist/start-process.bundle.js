/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,main_loader,sign_v2_api,sign_v2_b2e_signDropdown) {
	'use strict';

	let _ = t => t,
	  _t;
	const dropdownTemplateEntityId = 'sign-b2e-start-process-type';
	const dropdownProcessTabId = 'sign-b2e-start-process-types';
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _templatesList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templatesList");
	var _getProcessTypeLayoutLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProcessTypeLayoutLoader");
	var _getProcessTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProcessTypeDropdown");
	var _onProcessTypesLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProcessTypesLoaded");
	var _createProcessTypeDropdownItemByTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createProcessTypeDropdownItemByTemplate");
	class StartProcess {
	  constructor() {
	    Object.defineProperty(this, _createProcessTypeDropdownItemByTemplate, {
	      value: _createProcessTypeDropdownItemByTemplate2
	    });
	    Object.defineProperty(this, _onProcessTypesLoaded, {
	      value: _onProcessTypesLoaded2
	    });
	    Object.defineProperty(this, _getProcessTypeDropdown, {
	      value: _getProcessTypeDropdown2
	    });
	    Object.defineProperty(this, _getProcessTypeLayoutLoader, {
	      value: _getProcessTypeLayoutLoader2
	    });
	    Object.defineProperty(this, _cache, {
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
	    void babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeLayoutLoader)[_getProcessTypeLayoutLoader]().show();
	    void babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList].then(data => babelHelpers.classPrivateFieldLooseBase(this, _onProcessTypesLoaded)[_onProcessTypesLoaded](data));
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('layout', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div>
					<h1 class="sign-b2e-settings__header">${0}</h1>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${0}
						</p>
						${0}
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_START_PROCESS_HEAD'), main_core.Loc.getMessage('SIGN_START_PROCESS_TYPE'), babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getLayout());
	    });
	  }
	  getSelectedTemplateUid() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getSelectedId();
	  }
	  getTemplates() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _templatesList)[_templatesList];
	  }
	}
	function _getProcessTypeLayoutLoader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('processTypeLayoutLoader', () => new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]().getLayout()
	  }));
	}
	function _getProcessTypeDropdown2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('processTypeDropdown', () => new sign_v2_b2e_signDropdown.SignDropdown({
	    tabs: [{
	      id: dropdownProcessTabId,
	      title: ' '
	    }],
	    entities: [{
	      id: dropdownTemplateEntityId
	    }],
	    items: []
	  }));
	}
	function _onProcessTypesLoaded2(templates) {
	  var _dropdownItems$at;
	  const dropdownItems = templates.map(template => babelHelpers.classPrivateFieldLooseBase(this, _createProcessTypeDropdownItemByTemplate)[_createProcessTypeDropdownItemByTemplate](template));
	  const processTypeDropdown = babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeDropdown)[_getProcessTypeDropdown]();
	  dropdownItems.forEach(item => processTypeDropdown.addItem(item));
	  void babelHelpers.classPrivateFieldLooseBase(this, _getProcessTypeLayoutLoader)[_getProcessTypeLayoutLoader]().hide();
	  const firstDropdownItemId = (_dropdownItems$at = dropdownItems.at(0)) == null ? void 0 : _dropdownItems$at.id;
	  if (!main_core.Type.isNil(firstDropdownItemId)) {
	    processTypeDropdown.selectItem(firstDropdownItemId);
	  }
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

	exports.StartProcess = StartProcess;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX,BX.Sign.V2,BX.Sign.V2.B2e));
//# sourceMappingURL=start-process.bundle.js.map

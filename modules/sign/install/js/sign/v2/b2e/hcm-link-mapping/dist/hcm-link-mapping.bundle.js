/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_loader,main_core_events,humanresources_hcmlink_dataMapper) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _integrationId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("integrationId");
	var _employeeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("employeeIds");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _linkText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkText");
	var _loaderContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderContainer");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _isValid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isValid");
	var _enabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enabled");
	var _checkNotMapped = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkNotMapped");
	var _refresh = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refresh");
	var _getText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getText");
	var _setValid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setValid");
	var _openMapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openMapper");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _hide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hide");
	var _show = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("show");
	class HcmLinkMapping extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _show, {
	      value: _show2
	    });
	    Object.defineProperty(this, _hide, {
	      value: _hide2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _openMapper, {
	      value: _openMapper2
	    });
	    Object.defineProperty(this, _setValid, {
	      value: _setValid2
	    });
	    Object.defineProperty(this, _getText, {
	      value: _getText2
	    });
	    Object.defineProperty(this, _refresh, {
	      value: _refresh2
	    });
	    Object.defineProperty(this, _checkNotMapped, {
	      value: _checkNotMapped2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _integrationId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _employeeIds, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _linkText, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loaderContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isValid, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _enabled, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = options.api;
	    this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkMapping');
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = this.render();
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	    }
	    const {
	      root,
	      loaderContainer
	    } = main_core.Tag.render(_t || (_t = _`
			<div class="sign-b2e-hcm-link-mapping-container">
				<div 
					class="sign-b2e-hcm-link-mapping-loader"
					ref="loaderContainer"
				></div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getText)[_getText]());
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = root;
	    babelHelpers.classPrivateFieldLooseBase(this, _loaderContainer)[_loaderContainer] = loaderContainer;
	    babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  setEnabled(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _enabled)[_enabled] = value;
	  }
	  setDocumentUid(uid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = uid;
	    babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _enabled)[_enabled]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _refresh)[_refresh]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _setValid)[_setValid](true);
	    }
	  }
	}
	async function _checkNotMapped2() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid])) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  const {
	    integrationId,
	    userIds
	  } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].checkNotMappedMembersHrIntegration(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid]);
	  babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds] = userIds;
	  babelHelpers.classPrivateFieldLooseBase(this, _integrationId)[_integrationId] = integrationId;
	  const isAllMapped = !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds]);
	  babelHelpers.classPrivateFieldLooseBase(this, _setValid)[_setValid](isAllMapped);
	  if (isAllMapped) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _show)[_show]();
	  }
	}
	function _refresh2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	  babelHelpers.classPrivateFieldLooseBase(this, _setValid)[_setValid](false);
	  void babelHelpers.classPrivateFieldLooseBase(this, _checkNotMapped)[_checkNotMapped]();
	}
	function _getText2() {
	  const syncButton = main_core.Tag.render(_t2 || (_t2 = _`
			<a class="sign-b2e-hcm-link-mapping-sync-button">
				${0}
			</a>
		`), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_SYNC_BUTTON'));
	  main_core.Event.bind(syncButton, 'click', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _openMapper)[_openMapper]();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _linkText)[_linkText] = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="sign-b2e-hcm-link-mapping-text">
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TEXT'), syncButton);
	  return babelHelpers.classPrivateFieldLooseBase(this, _linkText)[_linkText];
	}
	function _setValid2(value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _isValid)[_isValid] = value;
	  this.emit('validUpdate', {
	    value: babelHelpers.classPrivateFieldLooseBase(this, _isValid)[_isValid]
	  });
	}
	function _openMapper2() {
	  humanresources_hcmlink_dataMapper.Mapper.openSlider({
	    companyId: babelHelpers.classPrivateFieldLooseBase(this, _integrationId)[_integrationId],
	    userIds: new Set(babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds]),
	    mode: humanresources_hcmlink_dataMapper.Mapper.MODE_DIRECT
	  }, {
	    onCloseHandler: () => void babelHelpers.classPrivateFieldLooseBase(this, _checkNotMapped)[_checkNotMapped]()
	  });
	}
	function _showLoader2() {
	  main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _linkText)[_linkText]);
	  void babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show(babelHelpers.classPrivateFieldLooseBase(this, _loaderContainer)[_loaderContainer]);
	}
	function _getLoader2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 30,
	      mode: 'inline'
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	}
	function _hide2() {
	  void babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().destroy();
	  main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	}
	function _show2() {
	  void babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().destroy();
	  main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _linkText)[_linkText]);
	  main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	}

	exports.HcmLinkMapping = HcmLinkMapping;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX,BX.Event,BX.Humanresources.Hcmlink));
//# sourceMappingURL=hcm-link-mapping.bundle.js.map

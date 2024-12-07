/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_label,main_popup,ui_analytics,main_core_events,main_core,ui_popupcomponentsmaker) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _companyId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyId");
	var _requisiteId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisiteId");
	var _isConnected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isConnected");
	var _isPublic = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPublic");
	var _publicUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("publicUrl");
	var _editUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editUrl");
	var _requisiteElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisiteElement");
	var _requisitesPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisitesPopup");
	var _requisiteButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisiteButton");
	var _updateOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateOptions");
	var _updateElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateElement");
	var _getRequisiteSettingsButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisiteSettingsButton");
	var _getRequisitesPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisitesPopup");
	var _getButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonText");
	var _getRequisiteButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisiteButton");
	var _handleButtonOnclick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleButtonOnclick");
	var _handleOpenRequisite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOpenRequisite");
	var _handleCreateLanding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCreateLanding");
	var _createLanding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createLanding");
	var _handleEditRequisite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEditRequisite");
	var _handleCreateCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCreateCompany");
	var _getRequisites = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisites");
	class RequisiteSection extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getRequisites, {
	      value: _getRequisites2
	    });
	    Object.defineProperty(this, _handleCreateCompany, {
	      value: _handleCreateCompany2
	    });
	    Object.defineProperty(this, _handleEditRequisite, {
	      value: _handleEditRequisite2
	    });
	    Object.defineProperty(this, _createLanding, {
	      value: _createLanding2
	    });
	    Object.defineProperty(this, _handleCreateLanding, {
	      value: _handleCreateLanding2
	    });
	    Object.defineProperty(this, _handleOpenRequisite, {
	      value: _handleOpenRequisite2
	    });
	    Object.defineProperty(this, _handleButtonOnclick, {
	      value: _handleButtonOnclick2
	    });
	    Object.defineProperty(this, _getRequisiteButton, {
	      value: _getRequisiteButton2
	    });
	    Object.defineProperty(this, _getButtonText, {
	      value: _getButtonText2
	    });
	    Object.defineProperty(this, _getRequisitesPopup, {
	      value: _getRequisitesPopup2
	    });
	    Object.defineProperty(this, _getRequisiteSettingsButton, {
	      value: _getRequisiteSettingsButton2
	    });
	    Object.defineProperty(this, _updateElement, {
	      value: _updateElement2
	    });
	    Object.defineProperty(this, _updateOptions, {
	      value: _updateOptions2
	    });
	    Object.defineProperty(this, _companyId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisiteId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isConnected, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isPublic, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _publicUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _editUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisiteElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisitesPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisiteButton, {
	      writable: true,
	      value: void 0
	    });
	    if (_options) {
	      babelHelpers.classPrivateFieldLooseBase(this, _updateOptions)[_updateOptions](_options);
	      top.BX.addCustomEvent('onLocalStorageSet', params => {
	        var _params$key;
	        const eventName = (_params$key = params == null ? void 0 : params.key) != null ? _params$key : null;
	        if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate' || eventName === 'BX.Crm.RequisiteSliderDetails:onSave') {
	          babelHelpers.classPrivateFieldLooseBase(this, _getRequisites)[_getRequisites]().then(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _updateElement)[_updateElement]();
	          });
	        }
	      });
	    }
	  }
	  getElement() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _requisiteElement)[_requisiteElement]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _requisiteElement)[_requisiteElement] = main_core.Tag.render(_t || (_t = _`
				<div class="intranet-settings-widget__business-card intranet-settings-widget_box">
					<div class="intranet-settings-widget__business-card_head intranet-settings-widget_inner">
						<div class="intranet-settings-widget_icon-box --gray">
							<div class="ui-icon-set --customer-card-1"></div>
						</div>
						<div class="intranet-settings-widget__title" data-role="requisite-widget-title">
							${0}
						</div>
						<i class="ui-icon-set --help" onclick="BX.Helper.show('redirect=detail&code=18213326')"></i>
					</div>

					<div class="intranet-settings-widget__business-card_footer">
						${0}
						${0}
					</div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isConnected)[_isConnected] ? main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_SITE_TITLE') : main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_REQUISITE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _getRequisiteButton)[_getRequisiteButton]().getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId] ? babelHelpers.classPrivateFieldLooseBase(this, _getRequisiteSettingsButton)[_getRequisiteSettingsButton]() : '');
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _requisiteElement)[_requisiteElement];
	  }
	}
	function _updateOptions2(options) {
	  var _options$companyId, _options$requisiteId;
	  babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId] = (_options$companyId = options.companyId) != null ? _options$companyId : 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _requisiteId)[_requisiteId] = (_options$requisiteId = options.requisiteId) != null ? _options$requisiteId : 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _isConnected)[_isConnected] = main_core.Type.isBoolean(options.isConnected) ? options.isConnected : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isPublic)[_isPublic] = main_core.Type.isBoolean(options.isPublic) ? options.isPublic : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _publicUrl)[_publicUrl] = main_core.Type.isString(options.publicUrl) ? options.publicUrl : '';
	  babelHelpers.classPrivateFieldLooseBase(this, _editUrl)[_editUrl] = main_core.Type.isString(options.editUrl) ? options.editUrl : '';
	}
	function _updateElement2() {
	  const currentElement = this.getElement();
	  babelHelpers.classPrivateFieldLooseBase(this, _requisiteElement)[_requisiteElement] = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _requisiteButton)[_requisiteButton] = null;
	  main_core.Dom.replace(currentElement, this.getElement());
	}
	function _getRequisiteSettingsButton2() {
	  const onclickRequisitesSettings = () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _getRequisitesPopup)[_getRequisitesPopup]().show();
	  };
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<span onclick="${0}" class="intranet-settings-widget__requisite-btn">
				<i class='ui-icon-set --more-information'></i>
			</span>
		`), onclickRequisitesSettings);
	}
	function _getRequisitesPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup]) {
	    const onclickCopyLink = () => {
	      if (BX.clipboard.copy(babelHelpers.classPrivateFieldLooseBase(this, _publicUrl)[_publicUrl])) {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_COPIED_POPUP'),
	          position: 'top-left',
	          autoHideDelay: 3000
	        });
	      }
	    };
	    const onclickConfigureSite = () => {
	      window.open(babelHelpers.classPrivateFieldLooseBase(this, _editUrl)[_editUrl], '_blank');
	      babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup].close();
	      SettingsWidget.close();
	    };
	    let copyLinkButton = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _publicUrl)[_publicUrl]) {
	      copyLinkButton = {
	        html: `
							<div class="intranet-settings-widget__popup-item">
								<div class="ui-icon-set --link-3"></div> 
								<div class="intranet-settings-widget__popup-name">${main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_COPY_LINK_BUTTON')}</div>
							</div>
						`,
	        onclick: onclickCopyLink
	      };
	    }
	    let configureSiteButton = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _editUrl)[_editUrl]) {
	      configureSiteButton = {
	        html: `
							<div class="intranet-settings-widget__popup-item">
								<div class="ui-icon-set --paint-1"></div> 
								<div class="intranet-settings-widget__popup-name">${main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_CUTAWAY_SITE_BUTTON')}</div>
							</div>
						`,
	        onclick: onclickConfigureSite
	      };
	    }
	    const onclickConfigureRequisites = () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup].close();
	      }
	      SettingsWidget.close();
	      BX.SidePanel.Instance.open(`/crm/company/details/${babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId]}/?init_mode=edit&rqedit=y`);
	    };
	    const configureRequisiteButton = {
	      html: `
						<div class="intranet-settings-widget__popup-item">
							<div class="ui-icon-set --pencil-40"></div>
							<div class="intranet-settings-widget__popup-name">${main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_REQUISITE_BUTTON')}</div>
						</div>
					`,
	      onclick: onclickConfigureRequisites
	    };
	    const popupWidth = 240;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup] = BX.PopupMenu.create('requisites-settings', event.currentTarget, [copyLinkButton, configureRequisiteButton, configureSiteButton], {
	      closeByEsc: true,
	      autoHide: true,
	      width: popupWidth,
	      offsetLeft: -72,
	      angle: {
	        offset: popupWidth / 2 - 15
	      },
	      events: {
	        onShow: () => {
	          setTimeout(() => {
	            main_core.Event.bindOnce(SettingsWidget.getInstance().getWidget().getPopup().getPopupContainer(), 'click', () => {
	              babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup].close();
	            });
	          }, 0);
	        }
	      }
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup];
	}
	function _getButtonText2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isConnected)[_isConnected]) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_REDIRECT_TO_REQUISITE_BUTTON');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId] > 0 && babelHelpers.classPrivateFieldLooseBase(this, _requisiteId)[_requisiteId] > 0) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING');
	  }
	  return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIGURE_REQUISITE_BUTTON');
	}
	function _getRequisiteButton2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _requisiteButton)[_requisiteButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _requisiteButton)[_requisiteButton] = new BX.UI.Button({
	      id: 'requisite-btn',
	      text: babelHelpers.classPrivateFieldLooseBase(this, _getButtonText)[_getButtonText](),
	      noCaps: true,
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleButtonOnclick)[_handleButtonOnclick].bind(this),
	      className: 'ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light'
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _requisiteButton)[_requisiteButton];
	}
	function _handleButtonOnclick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isConnected)[_isConnected]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _handleOpenRequisite)[_handleOpenRequisite]();
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId] > 0) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _requisiteId)[_requisiteId] > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _handleCreateLanding)[_handleCreateLanding]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _handleEditRequisite)[_handleEditRequisite]();
	    }
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _handleCreateCompany)[_handleCreateCompany]();
	  }
	}
	function _handleOpenRequisite2() {
	  SettingsWidget.close();
	  window.open(babelHelpers.classPrivateFieldLooseBase(this, _publicUrl)[_publicUrl], '_blank');
	}
	function _handleCreateLanding2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getRequisiteButton)[_getRequisiteButton]().setWaiting(true);
	  babelHelpers.classPrivateFieldLooseBase(this, _createLanding)[_createLanding]().then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _requisitesPopup)[_requisitesPopup] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisiteButton)[_requisiteButton] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _updateElement)[_updateElement]();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isPublic)[_isPublic]) {
	      const errorPopup = new Popup('public-landing-error', this.getElement().querySelector('[data-role="requisite-widget-title"]'), {
	        autoHide: true,
	        closeByEsc: true,
	        angle: true,
	        darkMode: true,
	        content: main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_CREATE_LANDING_ERROR'),
	        events: {
	          onShow: () => {
	            setTimeout(() => {
	              main_core.Event.bindOnce(SettingsWidget.getInstance().getWidget().getPopup().getPopupContainer(), 'click', () => {
	                errorPopup.close();
	              });
	            }, 0);
	          },
	          onClose: () => {
	            errorPopup.destroy();
	          }
	        }
	      });
	      errorPopup.show();
	    }
	  });
	}
	function _createLanding2() {
	  return new Promise(resolve => {
	    main_core.ajax.runComponentAction('bitrix:intranet.settings.widget', 'createRequisiteLanding', {
	      mode: 'class'
	    }).then(({
	      data: {
	        isConnected,
	        isPublic,
	        publicUrl,
	        editUrl
	      }
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _isConnected)[_isConnected] = isConnected;
	      babelHelpers.classPrivateFieldLooseBase(this, _isPublic)[_isPublic] = isPublic;
	      babelHelpers.classPrivateFieldLooseBase(this, _publicUrl)[_publicUrl] = publicUrl;
	      babelHelpers.classPrivateFieldLooseBase(this, _editUrl)[_editUrl] = editUrl;
	      resolve();
	    });
	  });
	}
	function _handleEditRequisite2() {
	  SettingsWidget.close();
	  BX.SidePanel.Instance.open(`/crm/company/details/${babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId]}/?init_mode=edit&rqedit=y`);
	}
	function _handleCreateCompany2() {
	  SettingsWidget.close();
	  BX.SidePanel.Instance.open('/crm/company/details/0/?mycompany=y&rqedit=y');
	}
	function _getRequisites2() {
	  return new Promise(resolve => {
	    main_core.ajax.runComponentAction('bitrix:intranet.settings.widget', 'getRequisites', {
	      mode: 'class'
	    }).then(({
	      data: {
	        requisite
	      }
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _updateOptions)[_updateOptions](requisite);
	      resolve();
	    });
	  });
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
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
	  _t15;
	var _widgetPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widgetPopup");
	var _target = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("target");
	var _otp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("otp");
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _marketUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("marketUrl");
	var _theme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("theme");
	var _holding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("holding");
	var _holdingWidget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("holdingWidget");
	var _isBitrix = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24");
	var _isFreeLicense = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFreeLicense");
	var _isAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	var _requisite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisite");
	var _requisiteSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisiteSection");
	var _settingsUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsUrl");
	var _isRenameable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRenameable");
	var _mainPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainPage");
	var _setOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _setHoldingOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setHoldingOptions");
	var _getItemsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItemsList");
	var _drawItemsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("drawItemsList");
	var _getLinkHeaderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLinkHeaderIcon");
	var _getEditHeaderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEditHeaderIcon");
	var _getHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeader");
	var _applyTheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyTheme");
	var _getFooter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFooter");
	var _prepareElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareElement");
	var _getMainPageElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMainPageElement");
	var _getRequisitesElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisitesElement");
	var _getHoldingsElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHoldingsElement");
	var _getHoldingWidget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHoldingWidget");
	var _getEmptyHoldingsElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyHoldingsElement");
	var _getSecurityAndSettingsElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSecurityAndSettingsElement");
	var _getSecurityElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSecurityElement");
	var _getGeneralSettingsElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getGeneralSettingsElement");
	var _getMigrateElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMigrateElement");
	class SettingsWidget extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getMigrateElement, {
	      value: _getMigrateElement2
	    });
	    Object.defineProperty(this, _getGeneralSettingsElement, {
	      value: _getGeneralSettingsElement2
	    });
	    Object.defineProperty(this, _getSecurityElement, {
	      value: _getSecurityElement2
	    });
	    Object.defineProperty(this, _getSecurityAndSettingsElement, {
	      value: _getSecurityAndSettingsElement2
	    });
	    Object.defineProperty(this, _getEmptyHoldingsElement, {
	      value: _getEmptyHoldingsElement2
	    });
	    Object.defineProperty(this, _getHoldingWidget, {
	      value: _getHoldingWidget2
	    });
	    Object.defineProperty(this, _getHoldingsElement, {
	      value: _getHoldingsElement2
	    });
	    Object.defineProperty(this, _getRequisitesElement, {
	      value: _getRequisitesElement2
	    });
	    Object.defineProperty(this, _getMainPageElement, {
	      value: _getMainPageElement2
	    });
	    Object.defineProperty(this, _prepareElement, {
	      value: _prepareElement2
	    });
	    Object.defineProperty(this, _getFooter, {
	      value: _getFooter2
	    });
	    Object.defineProperty(this, _applyTheme, {
	      value: _applyTheme2
	    });
	    Object.defineProperty(this, _getHeader, {
	      value: _getHeader2
	    });
	    Object.defineProperty(this, _getEditHeaderIcon, {
	      value: _getEditHeaderIcon2
	    });
	    Object.defineProperty(this, _getLinkHeaderIcon, {
	      value: _getLinkHeaderIcon2
	    });
	    Object.defineProperty(this, _drawItemsList, {
	      value: _drawItemsList2
	    });
	    Object.defineProperty(this, _getItemsList, {
	      value: _getItemsList2
	    });
	    Object.defineProperty(this, _setHoldingOptions, {
	      value: _setHoldingOptions2
	    });
	    Object.defineProperty(this, _setOptions, {
	      value: _setOptions2
	    });
	    Object.defineProperty(this, _widgetPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _otp, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _marketUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _theme, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _holding, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _holdingWidget, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isBitrix, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isFreeLicense, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisite, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisiteSection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isRenameable, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mainPage, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Intranet.SettingsWidget');
	    babelHelpers.classPrivateFieldLooseBase(this, _marketUrl)[_marketUrl] = _options.marketUrl;
	    babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] = _options.isBitrix24;
	    babelHelpers.classPrivateFieldLooseBase(this, _isFreeLicense)[_isFreeLicense] = _options.isFreeLicense;
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] = _options.isAdmin;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisite)[_requisite] = _options.requisite;
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsUrl)[_settingsUrl] = _options.settingsPath;
	    babelHelpers.classPrivateFieldLooseBase(this, _isRenameable)[_isRenameable] = _options.isRenameable;
	    babelHelpers.classPrivateFieldLooseBase(this, _mainPage)[_mainPage] = _options.mainPage;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisiteSection)[_requisiteSection] = new RequisiteSection(_options.requisite);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions)[_setOptions](_options);
	  }
	  setTarget(target) {
	    babelHelpers.classPrivateFieldLooseBase(this, _target)[_target] = target;
	    return this;
	  }
	  setWidgetPopup(widgetPopup) {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetPopup)[_widgetPopup] = widgetPopup;
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetPopup)[_widgetPopup].getPopup().subscribe('onClose', () => {
	      main_core.Event.unbindAll(this.getWidget().getPopup().getPopupContainer(), 'click');
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _getItemsList)[_getItemsList]().then(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _drawItemsList)[_drawItemsList]();
	    });
	    return this;
	  }
	  static bindWidget(popup) {
	    const instance = this.getInstance();
	    if (instance) {
	      instance.setWidgetPopup(popup);
	    }
	    return instance;
	  }
	  static bindAndShow(button) {
	    const instance = this.getInstance();
	    if (instance) {
	      main_core.Event.unbindAll(button);
	      main_core.Event.bind(button, 'click', instance.toggle.bind(instance, button));
	      instance.show(button);
	    }
	    return instance;
	  }
	  static init(options) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this(options);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	  static getInstance() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	  static close() {
	    const instance = this.getInstance();
	    if (instance) {
	      instance.getWidget().close();
	    }
	  }
	  toggle(targetNode) {
	    const popup = this.getWidget().getPopup();
	    if (popup.isShown()) {
	      popup.close();
	    } else {
	      this.show(targetNode);
	    }
	  }
	  show(targetNode) {
	    const popup = this.getWidget().getPopup();
	    popup.setBindElement(targetNode);
	    popup.show();
	    if (popup.getPopupContainer().getBoundingClientRect().left < 30) {
	      main_core.Dom.style(popup.getPopupContainer(), {
	        left: '30px'
	      });
	    }
	    this.setTarget(targetNode);
	  }
	  getWidget() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _widgetPopup)[_widgetPopup];
	  }
	}
	function _setOptions2(options) {
	  options.theme ? babelHelpers.classPrivateFieldLooseBase(this, _theme)[_theme] = options.theme : null;
	  options.otp ? babelHelpers.classPrivateFieldLooseBase(this, _otp)[_otp] = options.otp : null;
	  options.holding ? babelHelpers.classPrivateFieldLooseBase(this, _setHoldingOptions)[_setHoldingOptions](options.holding) : null;
	}
	function _setHoldingOptions2(options) {
	  var _options$isHolding, _options$affiliate, _options$canBeHolding, _options$canBeAffilia;
	  if (!main_core.Type.isPlainObject(options)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding] = null;
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding] = {
	    isHolding: (_options$isHolding = options.isHolding) != null ? _options$isHolding : false,
	    affiliate: (_options$affiliate = options.affiliate) != null ? _options$affiliate : null,
	    canBeHolding: (_options$canBeHolding = options.canBeHolding) != null ? _options$canBeHolding : false,
	    canBeAffiliate: (_options$canBeAffilia = options.canBeAffiliate) != null ? _options$canBeAffilia : false
	  };
	}
	function _getItemsList2(reload = false) {
	  if (reload === true || typeof babelHelpers.classPrivateFieldLooseBase(this, _theme)[_theme] === 'undefined') {
	    return new Promise(resolve => {
	      main_core.ajax.runComponentAction('bitrix:intranet.settings.widget', 'getData', {
	        mode: 'class'
	      }).then(({
	        data: {
	          theme,
	          otp,
	          holding
	        }
	      }) => {
	        babelHelpers.classPrivateFieldLooseBase(this, _theme)[_theme] = theme;
	        babelHelpers.classPrivateFieldLooseBase(this, _otp)[_otp] = otp;
	        babelHelpers.classPrivateFieldLooseBase(this, _setHoldingOptions)[_setHoldingOptions](holding);
	        resolve();
	      });
	    });
	  }
	  return Promise.resolve();
	}
	function _drawItemsList2() {
	  const container = this.getWidget().getPopup().getPopupContainer();
	  main_core.Dom.clean(container);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getHeader)[_getHeader](), container);
	  const content = [babelHelpers.classPrivateFieldLooseBase(this, _requisite)[_requisite] && babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] ? babelHelpers.classPrivateFieldLooseBase(this, _getRequisitesElement)[_getRequisitesElement]() : null, babelHelpers.classPrivateFieldLooseBase(this, _mainPage)[_mainPage].isAvailable ? babelHelpers.classPrivateFieldLooseBase(this, _getMainPageElement)[_getMainPageElement]() : null, babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] ? babelHelpers.classPrivateFieldLooseBase(this, _getSecurityAndSettingsElement)[_getSecurityAndSettingsElement]() : null, babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] ? babelHelpers.classPrivateFieldLooseBase(this, _getHoldingsElement)[_getHoldingsElement]() : null, babelHelpers.classPrivateFieldLooseBase(this, _getMigrateElement)[_getMigrateElement]()];
	  content.forEach(element => {
	    main_core.Dom.append(element, container);
	  });
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getFooter)[_getFooter](), container);
	}
	function _getLinkHeaderIcon2() {
	  const onclickCopyLink = () => {
	    if (BX.clipboard.copy(window.location.origin)) {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_LINK_COPIED_POPUP'),
	        position: 'top-left',
	        autoHideDelay: 3000
	      });
	    }
	  };
	  return main_core.Tag.render(_t$1 || (_t$1 = _$1`<span class='ui-icon-set --link-3 intranet-settings-widget__header-btn' onclick="${0}"></span>`), onclickCopyLink);
	}
	function _getEditHeaderIcon2() {
	  const onclickEditLink = () => {
	    this.getWidget().close();
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _settingsUrl)[_settingsUrl] + '?analyticContext=widget_settings_settings&page=portal&option=subDomainName');
	  };
	  return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<span class='ui-icon-set --pencil-40 intranet-settings-widget__header-btn' onclick="${0}"></span>`), onclickEditLink);
	}
	function _getHeader2() {
	  const header = main_core.Tag.render(_t3 || (_t3 = _$1`
				<div class="intranet-settings-widget__header">
					<div class="intranet-settings-widget__header_inner">
						<span class="intranet-settings-widget__header-name">${0}</span>
						${0}
					</div>
				</div>
			`), window.location.host, babelHelpers.classPrivateFieldLooseBase(this, _isRenameable)[_isRenameable] ? babelHelpers.classPrivateFieldLooseBase(this, _getEditHeaderIcon)[_getEditHeaderIcon]() : babelHelpers.classPrivateFieldLooseBase(this, _getLinkHeaderIcon)[_getLinkHeaderIcon]());
	  babelHelpers.classPrivateFieldLooseBase(this, _applyTheme)[_applyTheme](header, babelHelpers.classPrivateFieldLooseBase(this, _theme)[_theme]);
	  const adaptedEmptyHeader = new ui_popupcomponentsmaker.PopupComponentsMakerItem({
	    withoutBackground: true,
	    html: header
	  }).getContainer();
	  main_core.Dom.addClass(adaptedEmptyHeader, '--widget-header');
	  main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', ({
	    data: {
	      theme
	    }
	  }) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _applyTheme)[_applyTheme](header, theme);
	  });
	  return adaptedEmptyHeader;
	}
	function _applyTheme2(container, theme) {
	  const previewImage = `url('${main_core.Text.encode(theme.previewImage)}')`;
	  main_core.Dom.style(container, 'backgroundImage', previewImage);
	  main_core.Dom.removeClass(container, 'bitrix24-theme-dark bitrix24-theme-light');
	  const themeClass = String(theme.id).indexOf('dark:') === 0 ? 'bitrix24-theme-dark' : 'bitrix24-theme-light';
	  main_core.Dom.addClass(container, themeClass);
	}
	function _getFooter2() {
	  const onclickOpenPartnerOrder = () => {
	    this.getWidget().close();
	    BX.UI.InfoHelper.show('info_implementation_request');
	  };
	  const partnerOrder = main_core.Tag.render(_t4 || (_t4 = _$1`
			<span class="intranet-settings-widget__footer-item" onclick="${0}">
				${0}
			</span>
		`), onclickOpenPartnerOrder, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_ORDER_PARTNER_LINK_MSGVER_1'));
	  const onclickWhereToBegin = () => {
	    if (top.BX.Helper) {
	      this.getWidget().close();
	      top.BX.Helper.show('redirect=detail&code=18371844');
	    }
	  };
	  const onclickSupport = () => {
	    if (top.BX.Helper) {
	      this.getWidget().close();
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isFreeLicense)[_isFreeLicense]) {
	        BX.UI.InfoHelper.show('limit_support_bitrix');
	      } else {
	        BX.Helper.show('redirect=detail&code=12925062');
	      }
	    }
	  };
	  return main_core.Tag.render(_t5 || (_t5 = _$1`
				<div class="intranet-settings-widget__footer">
					${0}
					<span class="intranet-settings-widget__footer-item" onclick="${0}">
						${0}
					</span>
					<span class="intranet-settings-widget__footer-item" onclick="${0}">
						${0}
					</span>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] ? partnerOrder : '', onclickWhereToBegin, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_WHERE_TO_BEGIN_LINK'), onclickSupport, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SUPPORT_BUTTON'));
	}
	function _prepareElement2(element) {
	  const item = this.getWidget().getItem({
	    html: element
	  });
	  const node = item.getContainer();
	  main_core.Dom.addClass(node, '--widget-item');
	  return node;
	}
	function _getMainPageElement2() {
	  const onclick = () => {
	    this.getWidget().close();
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _mainPage)[_mainPage].settingsPath);
	    BX.UI.Analytics.sendData({
	      tool: 'landing',
	      category: 'vibe',
	      event: 'open_settings_main',
	      c_sub_section: 'from_widget_vibe_point'
	    });
	  };
	  const label = new ui_label.Label({
	    text: main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_LABEL_NEW'),
	    customClass: 'ui-label-new',
	    size: ui_label.LabelSize.SM,
	    fill: true
	  });
	  const labelWrapper = main_core.Tag.render(_t6 || (_t6 = _$1`
			<div class="intranet-settings-widget__label-new">
				${0}
			</div>
		`), label.render());
	  const element = main_core.Tag.render(_t7 || (_t7 = _$1`
			<div onclick="${0}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --green">
						<div class="ui-icon-set --home-page"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${0}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
				${0}
			</div>
		`), onclick, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_MAIN_PAGE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _mainPage)[_mainPage].isNew ? labelWrapper : null);
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	function _getRequisitesElement2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](babelHelpers.classPrivateFieldLooseBase(this, _requisiteSection)[_requisiteSection].getElement());
	}
	function _getHoldingsElement2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isBitrix)[_isBitrix] !== true || babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding] === null) {
	    return null;
	  }
	  if (!main_core.Type.isPlainObject(babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding].affiliate)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getEmptyHoldingsElement)[_getEmptyHoldingsElement]();
	  }
	  const affiliate = babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding].affiliate;
	  const onclickOpen = () => {
	    this.getWidget().close();
	    babelHelpers.classPrivateFieldLooseBase(this, _getHoldingWidget)[_getHoldingWidget]().show(babelHelpers.classPrivateFieldLooseBase(this, _target)[_target]);
	  };
	  const profilePhotoStyle = affiliate.profilePhoto ? `background: url('${encodeURI(main_core.Text.encode(affiliate.profilePhoto))}') no-repeat center; background-size: cover;` : '';
	  const element = main_core.Tag.render(_t8 || (_t8 = _$1`
		<div class="intranet-settings-widget__branch" onclick="${0}">
			<div class="intranet-settings-widget__branch-icon_box">
				<div class="ui-icon-set intranet-settings-widget__branch-icon --filial-network"></div>
			</div>
			<div class="intranet-settings-widget__branch_content">
				<div class="intranet-settings-widget__branch-title">
					${0}
				</div>
				<div class="intranet-settings-widget__title">
					${0}
				</div>
			</div>
			<div class="intranet-settings-widget__branch-btn_box">
				<button class="ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light">
					${0}
				</button>
			</div>
		</div>
		`), onclickOpen, affiliate.isHolding ? main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_MAIN_BRANCH') : main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECONDARY_BRANCH'), affiliate.name, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_BRANCHES'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	function _getHoldingWidget2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _holdingWidget)[_holdingWidget]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _holdingWidget)[_holdingWidget] = BX.Intranet.HoldingWidget.getInstance();
	    const onclickClose = () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _holdingWidget)[_holdingWidget].getWidget().close();
	      this.show();
	    };
	    const holdingWidgetCloseBtn = main_core.Tag.render(_t9 || (_t9 = _$1`
				<div class="intranet-settings-widget__close-btn">
					<div onclick="${0}" class="ui-icon-set --arrow-left intranet-settings-widget__close-btn_icon"></div>
					<div class="intranet-settings-widget__close-btn_name">${0}</div>
				</div>
			`), onclickClose, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_BRANCH_LIST'));
	    babelHelpers.classPrivateFieldLooseBase(this, _holdingWidget)[_holdingWidget].getWidget().getPopup().getContentContainer().prepend(holdingWidgetCloseBtn);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _holdingWidget)[_holdingWidget];
	}
	function _getEmptyHoldingsElement2() {
	  if (!main_core.Type.isPlainObject(babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding])) {
	    return null;
	  }
	  const title = babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] ? main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_NETWORK') : main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_NETWORK_UNAVAILABLE');
	  const buttonText = babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] ? main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_SETTINGS') : main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_FILIAL_ABOUT');
	  const onclickOpen = () => {
	    this.getWidget().close();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding].canBeHolding) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getHoldingWidget)[_getHoldingWidget]().show(babelHelpers.classPrivateFieldLooseBase(this, _target)[_target]);
	    } else {
	      BX.UI.InfoHelper.show('limit_office_multiple_branches');
	    }
	  };
	  const lockIcon = main_core.Tag.render(_t10 || (_t10 = _$1`
			<div class="intranet-settings-widget__branch-lock-icon_box">
				<div class="ui-icon-set intranet-settings-widget__branch-lock-icon --lock"></div>
			</div>
		`));
	  const element = main_core.Tag.render(_t11 || (_t11 = _$1`
			<div class="intranet-settings-widget__branch" onclick="${0}">
				<div class="intranet-settings-widget__branch-icon_box">
					<div class="ui-icon-set intranet-settings-widget__branch-icon --filial-network"></div>
					${0}
				</div>
				<div class="intranet-settings-widget__branch_content">
					<div class="intranet-settings-widget__title">${0}</div>
				</div>
				<div class="intranet-settings-widget__branch-btn_box">
					<button class="ui-btn ui-btn-light-border ui-btn-round ui-btn-xs ui-btn-no-caps intranet-setting__btn-light">${0}</button>
				</div>
			</div>
		`), onclickOpen, !babelHelpers.classPrivateFieldLooseBase(this, _holding)[_holding].canBeHolding ? lockIcon : '', title, buttonText);
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	function _getSecurityAndSettingsElement2() {
	  return main_core.Tag.render(_t12 || (_t12 = _$1`
			<div class="intranet-settings-widget_inline-box">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getSecurityElement)[_getSecurityElement](), babelHelpers.classPrivateFieldLooseBase(this, _getGeneralSettingsElement)[_getGeneralSettingsElement]());
	}
	function _getSecurityElement2() {
	  const onclick = () => {
	    this.getWidget().close();
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _settingsUrl)[_settingsUrl] + '?page=security&analyticContext=widget_settings_settings');
	  };
	  const element = main_core.Tag.render(_t13 || (_t13 = _$1`
			<span onclick="${0}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box ${0}">
						<div class="ui-icon-set --shield"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${0}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</span>
		`), onclick, babelHelpers.classPrivateFieldLooseBase(this, _otp)[_otp].IS_ACTIVE === 'Y' ? '--green' : '--yellow', main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_SECURITY_TITLE'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	function _getGeneralSettingsElement2() {
	  const onclick = () => {
	    this.getWidget().close();
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _settingsUrl)[_settingsUrl] + '?analyticContext=widget_settings_settings');
	  };
	  const element = main_core.Tag.render(_t14 || (_t14 = _$1`
			<span onclick="${0}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --gray">
						<div class="ui-icon-set --settings-2"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${0}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</span>
		`), onclick, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_SETTINGS_TITLE'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	function _getMigrateElement2() {
	  const onclick = () => {
	    this.getWidget().close();
	    BX.SidePanel.Instance.open(`${babelHelpers.classPrivateFieldLooseBase(this, _marketUrl)[_marketUrl]}category/migration/`);
	  };
	  const element = main_core.Tag.render(_t15 || (_t15 = _$1`
			<div onclick="${0}" class="intranet-settings-widget_box --clickable">
				<div class="intranet-settings-widget_inner">
					<div class="intranet-settings-widget_icon-box --gray">
						<div class="ui-icon-set --market-1"></div>
					</div>
					<div class="intranet-settings-widget__title">
						${0}
					</div>
				</div>
				<div class="intranet-settings-widget__arrow-btn ui-icon-set --arrow-right"></div>
			</div>
		`), onclick, main_core.Loc.getMessage('INTRANET_SETTINGS_WIDGET_SECTION_MIGRATION_TITLE'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _prepareElement)[_prepareElement](element);
	}
	Object.defineProperty(SettingsWidget, _instance, {
	  writable: true,
	  value: null
	});

	exports.SettingsWidget = SettingsWidget;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI,BX.Main,BX.UI.Analytics,BX.Event,BX,BX.UI));
//# sourceMappingURL=script.js.map

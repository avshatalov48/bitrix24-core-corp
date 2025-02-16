/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_router,ui_dialogs_messagebox,main_core_events,main_core,crm_dataStructures) {
	'use strict';

	const Types = Object.freeze({
	  bitrix24: 'bitrix24',
	  sms: 'sms_provider'
	});
	var _senderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("senderType");
	var _showConsentAgreementBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showConsentAgreementBox");
	var _getButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtons");
	var _approveConsent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("approveConsent");
	var _closeAgreementBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeAgreementBox");
	var _showErrorNotify = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showErrorNotify");
	var _showNotify = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotify");
	class ConsentApprover {
	  constructor(senderType = null) {
	    Object.defineProperty(this, _showNotify, {
	      value: _showNotify2
	    });
	    Object.defineProperty(this, _showErrorNotify, {
	      value: _showErrorNotify2
	    });
	    Object.defineProperty(this, _closeAgreementBox, {
	      value: _closeAgreementBox2
	    });
	    Object.defineProperty(this, _approveConsent, {
	      value: _approveConsent2
	    });
	    Object.defineProperty(this, _getButtons, {
	      value: _getButtons2
	    });
	    Object.defineProperty(this, _showConsentAgreementBox, {
	      value: _showConsentAgreementBox2
	    });
	    Object.defineProperty(this, _senderType, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _senderType)[_senderType] = senderType;
	  }
	  async checkAndApprove() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _senderType)[_senderType] !== Types.bitrix24) {
	      return Promise.resolve(true);
	    }
	    return new Promise(resolve => {
	      main_core.ajax.runAction('notifications.consent.Agreement.get').then(({
	        data
	      }) => {
	        if (!data || !data.html) {
	          resolve(true);
	          return;
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _showConsentAgreementBox)[_showConsentAgreementBox](data, resolve);
	      }).catch(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showErrorNotify)[_showErrorNotify]();
	        resolve(false);
	      });
	    });
	  }
	}
	function _showConsentAgreementBox2({
	  title,
	  html: message
	}, resolve) {
	  BX.UI.Dialogs.MessageBox.show({
	    modal: true,
	    minWidth: 980,
	    title,
	    message,
	    buttons: babelHelpers.classPrivateFieldLooseBase(this, _getButtons)[_getButtons](resolve),
	    popupOptions: {
	      className: 'crm-agreement-terms-popup'
	    }
	  });
	}
	function _getButtons2(resolve) {
	  return [new BX.UI.Button({
	    className: 'ui-btn-round',
	    color: BX.UI.Button.Color.SUCCESS,
	    text: main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_ACCEPT'),
	    onclick: button => {
	      babelHelpers.classPrivateFieldLooseBase(this, _approveConsent)[_approveConsent]().then(isApprovedConsent => {
	        if (isApprovedConsent) {
	          babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_ACCEPT'));
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _closeAgreementBox)[_closeAgreementBox](button);
	        resolve(true);
	      }).catch(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showErrorNotify)[_showErrorNotify]();
	        resolve(false);
	      });
	    }
	  }), new BX.UI.Button({
	    className: 'ui-btn-round',
	    color: BX.UI.Button.Color.LIGHT_BORDER,
	    text: main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_REJECT'),
	    onclick: button => {
	      babelHelpers.classPrivateFieldLooseBase(this, _closeAgreementBox)[_closeAgreementBox](button);
	      resolve(false);
	    }
	  })];
	}
	function _approveConsent2() {
	  return new Promise(resolve => {
	    main_core.ajax.runAction('notifications.consent.Agreement.approve').then(response => {
	      if ((response == null ? void 0 : response.status) === 'success' && response != null && response.data) {
	        resolve(true);
	        return;
	      }
	      resolve(false);
	    }).catch(() => {
	      resolve(false);
	    });
	  });
	}
	function _closeAgreementBox2({
	  context
	}) {
	  context.close();
	}
	function _showErrorNotify2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_AGREEMENT_VALIDATION_ERROR'));
	}
	function _showNotify2(content) {
	  BX.UI.Notification.Center.notify({
	    content
	  });
	}

	const showNotify = content => {
	  BX.UI.Notification.Center.notify({
	    content
	  });
	};

	var _showConnectAlertMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showConnectAlertMessage");
	class Base {
	  constructor(params) {
	    Object.defineProperty(this, _showConnectAlertMessage, {
	      value: _showConnectAlertMessage2
	    });
	    this.openLineItems = null;
	    this.senderType = null;
	    this.entityTypeId = null;
	    if (main_core.Type.isPlainObject(params == null ? void 0 : params.openLineItems)) {
	      var _params$openLineItems, _params$senderType;
	      this.openLineItems = (_params$openLineItems = params.openLineItems) != null ? _params$openLineItems : null;
	      this.senderType = (_params$senderType = params.senderType) != null ? _params$senderType : null;
	    }
	    if (main_core.Type.isNumber(params == null ? void 0 : params.entityTypeId)) {
	      this.entityTypeId = params.entityTypeId;
	    }
	  }
	  getOpenLineCode() {
	    throw new Error('Must be implement in child class');
	  }
	  async checkAndGetLineId() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _showConnectAlertMessage)[_showConnectAlertMessage]();
	    return Promise.resolve(null);
	  }
	  async isOpenLineItemSelected(force = false) {
	    const item = await this.getOpenLineItem(force);
	    if (!item) {
	      throw new ReferenceError(`OpenLine item with code: ${this.getOpenLineCode()} not found`);
	    }
	    return item.selected;
	  }
	  async getOpenLineItem(force = false, openLineCode = null) {
	    if (this.openLineItems === null || force) {
	      this.openLineItems = await this.fetchOpenLineItems();
	    }
	    return this.openLineItems[openLineCode != null ? openLineCode : this.getOpenLineCode()];
	  }
	  async fetchOpenLineItems() {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('crm.controller.integration.openlines.getItems').then(({
	        status,
	        data,
	        errors
	      }) => {
	        if (status === 'success') {
	          resolve(data);
	          return;
	        }
	        reject(errors);
	      }).catch(data => {
	        reject(data);
	      });
	    });
	  }
	  async getLineId() {
	    return new Promise(resolve => {
	      const ajaxParameters = {
	        connectorId: this.getOpenLineCode(),
	        withConnector: true
	      };
	      main_core.ajax.runAction('imconnector.Openlines.list', {
	        data: ajaxParameters
	      }).then(({
	        data
	      }) => {
	        if (main_core.Type.isArrayFilled(data)) {
	          const {
	            lineId
	          } = data[data.length - 1];
	          resolve(lineId);
	          return;
	        }
	        resolve(null);
	      }).catch(() => {
	        showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	      });
	    });
	  }
	  async canEditConnector() {
	    return new Promise(resolve => {
	      main_core.ajax.runAction('imconnector.Openlines.hasAccess').then(({
	        data
	      }) => {
	        if (data.canEditConnector) {
	          resolve(true);
	          return;
	        }
	        resolve(false);
	      }).catch(() => babelHelpers.classPrivateFieldLooseBase(this, _showConnectAlertMessage)[_showConnectAlertMessage]());
	    });
	  }
	  async openConnectSidePanel(url, onCloseCallback) {
	    return new Promise(resolve => {
	      if (main_core.Type.isStringFilled(url)) {
	        void crm_router.Router.openSlider(url, {
	          width: 700,
	          cacheable: false
	        }).then(() => {
	          onCloseCallback(resolve);
	        });
	        return;
	      }
	      showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	      resolve(null);
	    });
	  }
	}
	async function _showConnectAlertMessage2() {
	  const item = await this.getOpenLineItem();
	  const message = main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONNECT_ACCESS_DENIED', {
	    '#SERVICE_NAME#': item.name
	  });
	  showNotify(message);
	}

	var _checkVirtualWhatsAppAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkVirtualWhatsAppAvailable");
	var _fetchVirtualWhatsAppConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchVirtualWhatsAppConfig");
	var _isVirtualWhatsAppConnected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isVirtualWhatsAppConnected");
	class RuWhatsApp extends Base {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _isVirtualWhatsAppConnected, {
	      value: _isVirtualWhatsAppConnected2
	    });
	    Object.defineProperty(this, _fetchVirtualWhatsAppConfig, {
	      value: _fetchVirtualWhatsAppConfig2
	    });
	    Object.defineProperty(this, _checkVirtualWhatsAppAvailable, {
	      value: _checkVirtualWhatsAppAvailable2
	    });
	  }
	  async checkAndGetLineId() {
	    const isWhatsAppAvailable = await babelHelpers.classPrivateFieldLooseBase(this, _checkVirtualWhatsAppAvailable)[_checkVirtualWhatsAppAvailable]();
	    if (!isWhatsAppAvailable) {
	      return null;
	    }
	    const isSelected = await this.isOpenLineItemSelected();
	    if (isSelected) {
	      if (await babelHelpers.classPrivateFieldLooseBase(this, _isVirtualWhatsAppConnected)[_isVirtualWhatsAppConnected]()) {
	        return this.getLineId();
	      }
	      const canEditConnector = await this.canEditConnector();
	      if (canEditConnector) {
	        var _this$openLineItems, _this$openLineItems$v;
	        const url = (_this$openLineItems = this.openLineItems) == null ? void 0 : (_this$openLineItems$v = _this$openLineItems.virtual_whatsapp) == null ? void 0 : _this$openLineItems$v.url;
	        return this.openConnectSidePanel(url, this.onConnectVirtualWhatsApp.bind(this));
	      }
	      return super.checkAndGetLineId();
	    }
	    const canEditConnector = await this.canEditConnector();
	    if (canEditConnector) {
	      const item = await this.getOpenLineItem();
	      return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
	    }
	    return super.checkAndGetLineId();
	  }
	  async onConnectVirtualWhatsApp(resolve) {
	    if (await babelHelpers.classPrivateFieldLooseBase(this, _isVirtualWhatsAppConnected)[_isVirtualWhatsAppConnected]()) {
	      return resolve(this.getLineId());
	    }
	    showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	    return resolve(null);
	  }
	  async onConnect(resolve) {
	    const isSelected = await this.isOpenLineItemSelected(true);
	    if (isSelected) {
	      return resolve(this.checkAndGetLineId());
	    }
	    showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	    return resolve(null);
	  }
	  getOpenLineCode() {
	    return 'notifications';
	  }
	}
	async function _checkVirtualWhatsAppAvailable2() {
	  const config = await babelHelpers.classPrivateFieldLooseBase(this, _fetchVirtualWhatsAppConfig)[_fetchVirtualWhatsAppConfig]();
	  if (main_core.Type.isStringFilled(config.infoHelperCode)) {
	    if (main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	      BX.UI.InfoHelper.show(config.infoHelperCode);
	    }
	    return false;
	  }
	  return true;
	}
	function _fetchVirtualWhatsAppConfig2() {
	  const {
	    entityTypeId
	  } = this;
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.controller.messagesender.conditionchecker.getVirtualWhatsAppConfig', {
	      data: {
	        entityTypeId
	      }
	    }).then(({
	      status,
	      data,
	      errors
	    }) => {
	      if (status === 'success') {
	        resolve(data);
	        return;
	      }
	      reject(errors);
	    }).catch(data => reject(data));
	  });
	}
	async function _isVirtualWhatsAppConnected2() {
	  const virtualWhatsAppItem = await this.getOpenLineItem(true, 'virtual_whatsapp');
	  return virtualWhatsAppItem == null ? void 0 : virtualWhatsAppItem.selected;
	}

	var _checkConsentApproved = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkConsentApproved");
	class Telegram extends Base {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _checkConsentApproved, {
	      value: _checkConsentApproved2
	    });
	  }
	  async checkAndGetLineId() {
	    const isSelected = await this.isOpenLineItemSelected();
	    if (isSelected) {
	      const isApproved = await babelHelpers.classPrivateFieldLooseBase(this, _checkConsentApproved)[_checkConsentApproved]();
	      if (isApproved) {
	        const lineId = await this.getLineId();
	        if (!lineId) {
	          const item = await this.getOpenLineItem();
	          return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
	        }
	        return Promise.resolve(lineId);
	      }
	      showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_NOTIFY'));
	      return Promise.resolve(null);
	    }
	    const canEditConnector = await this.canEditConnector();
	    if (canEditConnector) {
	      const item = await this.getOpenLineItem();
	      return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
	    }
	    return super.checkAndGetLineId();
	  }
	  async onConnect(resolve) {
	    const lineId = await this.getLineId();
	    if (lineId === null) {
	      showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	      return resolve(null);
	    }
	    const item = await this.getOpenLineItem(true);
	    showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONNECT_SUCCESS', {
	      '#LINE_NAME#': item.name
	    }));
	    const isApproved = await babelHelpers.classPrivateFieldLooseBase(this, _checkConsentApproved)[_checkConsentApproved]();
	    if (isApproved) {
	      return resolve(lineId);
	    }
	    showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_NOTIFY'));
	    return resolve(null);
	  }
	  getOpenLineCode() {
	    return 'telegrambot';
	  }
	}
	async function _checkConsentApproved2() {
	  return new ConsentApprover(this.senderType).checkAndApprove();
	}

	var _checkVirtualWhatsAppAvailable$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkVirtualWhatsAppAvailable");
	var _fetchVirtualWhatsAppConfig$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchVirtualWhatsAppConfig");
	var _isVirtualWhatsAppConnected$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isVirtualWhatsAppConnected");
	var _hasAvailableSmsProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasAvailableSmsProvider");
	var _getSmsSenders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSmsSenders");
	var _showMarketplaceDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMarketplaceDialog");
	var _openMarketplace = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openMarketplace");
	var _onCloseMarketplace = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCloseMarketplace");
	var _getSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSettings");
	class WhatsApp extends Base {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getSettings, {
	      value: _getSettings2
	    });
	    Object.defineProperty(this, _onCloseMarketplace, {
	      value: _onCloseMarketplace2
	    });
	    Object.defineProperty(this, _openMarketplace, {
	      value: _openMarketplace2
	    });
	    Object.defineProperty(this, _showMarketplaceDialog, {
	      value: _showMarketplaceDialog2
	    });
	    Object.defineProperty(this, _getSmsSenders, {
	      value: _getSmsSenders2
	    });
	    Object.defineProperty(this, _hasAvailableSmsProvider, {
	      value: _hasAvailableSmsProvider2
	    });
	    Object.defineProperty(this, _isVirtualWhatsAppConnected$1, {
	      value: _isVirtualWhatsAppConnected2$1
	    });
	    Object.defineProperty(this, _fetchVirtualWhatsAppConfig$1, {
	      value: _fetchVirtualWhatsAppConfig2$1
	    });
	    Object.defineProperty(this, _checkVirtualWhatsAppAvailable$1, {
	      value: _checkVirtualWhatsAppAvailable2$1
	    });
	  }
	  async checkAndGetLineId() {
	    const isWhatsAppAvailable = await babelHelpers.classPrivateFieldLooseBase(this, _checkVirtualWhatsAppAvailable$1)[_checkVirtualWhatsAppAvailable$1]();
	    if (!isWhatsAppAvailable) {
	      return null;
	    }
	    if (await babelHelpers.classPrivateFieldLooseBase(this, _isVirtualWhatsAppConnected$1)[_isVirtualWhatsAppConnected$1]()) {
	      const hasAvailableProvider = await babelHelpers.classPrivateFieldLooseBase(this, _hasAvailableSmsProvider)[_hasAvailableSmsProvider]();
	      if (hasAvailableProvider) {
	        // notification connector does not take into account the open line number when generating the link
	        return Promise.resolve(0);
	      }
	      const canEditConnector = await this.canEditConnector();
	      if (canEditConnector) {
	        return babelHelpers.classPrivateFieldLooseBase(this, _showMarketplaceDialog)[_showMarketplaceDialog]();
	      }
	      return super.checkAndGetLineId();
	    }
	    const canEditConnector = await this.canEditConnector();
	    if (canEditConnector) {
	      var _this$openLineItems, _this$openLineItems$v;
	      const url = (_this$openLineItems = this.openLineItems) == null ? void 0 : (_this$openLineItems$v = _this$openLineItems.virtual_whatsapp) == null ? void 0 : _this$openLineItems$v.url;
	      return this.openConnectSidePanel(url, this.onConnectVirtualWhatsApp.bind(this));
	    }
	    return super.checkAndGetLineId();
	  }
	  async onConnectVirtualWhatsApp(resolve) {
	    if (await babelHelpers.classPrivateFieldLooseBase(this, _isVirtualWhatsAppConnected$1)[_isVirtualWhatsAppConnected$1]()) {
	      return resolve(this.checkAndGetLineId());
	    }
	    showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	    return resolve(null);
	  }
	  getOpenLineCode() {
	    return 'notifications';
	  }
	}
	async function _checkVirtualWhatsAppAvailable2$1() {
	  const config = await babelHelpers.classPrivateFieldLooseBase(this, _fetchVirtualWhatsAppConfig$1)[_fetchVirtualWhatsAppConfig$1]();
	  if (main_core.Type.isStringFilled(config.infoHelperCode)) {
	    if (main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	      BX.UI.InfoHelper.show(config.infoHelperCode);
	    }
	    return false;
	  }
	  return true;
	}
	function _fetchVirtualWhatsAppConfig2$1() {
	  const {
	    entityTypeId
	  } = this;
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.controller.messagesender.conditionchecker.getVirtualWhatsAppConfig', {
	      data: {
	        entityTypeId
	      }
	    }).then(({
	      status,
	      data,
	      errors
	    }) => {
	      if (status === 'success') {
	        resolve(data);
	        return;
	      }
	      reject(errors);
	    }).catch(data => reject(data));
	  });
	}
	async function _isVirtualWhatsAppConnected2$1() {
	  const virtualWhatsAppItem = await this.getOpenLineItem(true, 'virtual_whatsapp');
	  return virtualWhatsAppItem == null ? void 0 : virtualWhatsAppItem.selected;
	}
	async function _hasAvailableSmsProvider2() {
	  const smsSenders = await babelHelpers.classPrivateFieldLooseBase(this, _getSmsSenders)[_getSmsSenders]();
	  return Promise.resolve(smsSenders.some(provider => provider.canUse && !provider.isTemplatesBased));
	}
	async function _getSmsSenders2() {
	  const {
	    entityTypeId
	  } = this;
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.controller.messagesender.conditionchecker.getSmsSenders', {
	      data: {
	        entityTypeId
	      }
	    }).then(({
	      status,
	      data,
	      errors
	    }) => {
	      if (status === 'success') {
	        resolve(data);
	        return;
	      }
	      reject(errors);
	    }).catch(data => reject(data));
	  });
	}
	function _showMarketplaceDialog2() {
	  return new Promise(resolve => {
	    ui_dialogs_messagebox.MessageBox.show({
	      message: main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONDITION_CHECKER_MARKET_MESSAGE'),
	      modal: true,
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_CONDITION_CHECKER_OK_BTN_TEXT'),
	      onOk: messageBox => {
	        void babelHelpers.classPrivateFieldLooseBase(this, _openMarketplace)[_openMarketplace](resolve);
	        messageBox.close();
	      }
	    });
	  });
	}
	function _openMarketplace2(resolve) {
	  const marketUrl = babelHelpers.classPrivateFieldLooseBase(this, _getSettings)[_getSettings]().marketUrl;
	  BX.SidePanel.Instance.open(marketUrl, {
	    cacheable: false,
	    events: {
	      onClose: () => {
	        void babelHelpers.classPrivateFieldLooseBase(this, _onCloseMarketplace)[_onCloseMarketplace](resolve);
	      }
	    }
	  });
	}
	async function _onCloseMarketplace2(resolve) {
	  const hasAvailableSmsProvider = await babelHelpers.classPrivateFieldLooseBase(this, _hasAvailableSmsProvider)[_hasAvailableSmsProvider]();
	  if (hasAvailableSmsProvider) {
	    resolve(0);
	    return;
	  }
	  showNotify(main_core.Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
	  resolve(null);
	}
	function _getSettings2() {
	  return main_core.Extension.getSettings('crm.messagesender');
	}

	class Factory {
	  static getScenarioInstance(name, params) {
	    if (name === 'telegrambot') {
	      return new Telegram(params);
	    }
	    if (name === 'ru-whatsapp')
	      // for RU region
	      {
	        return new RuWhatsApp(params);
	      }
	    if (name === 'whatsapp')
	      // for not RU region
	      {
	        return new WhatsApp(params);
	      }
	    throw new RangeError(`Unknown scenario name: ${name}`);
	  }
	}

	var _openLineItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openLineItems");
	var _senderType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("senderType");
	var _serviceId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("serviceId");
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	class ConditionChecker {
	  /**
	   * @param {SenderType} senderType
	   * @param {OpenLineItems | null} openLineItems
	   * @param {string | null} serviceId
	   * @param {number | null} entityTypeId
	   * @returns {Promise<number|null>}
	   */
	  static async checkAndGetLine({
	    senderType,
	    openLineItems = null,
	    serviceId = null,
	    entityTypeId = null
	  }) {
	    const instance = new ConditionChecker({
	      senderType
	    });
	    if (main_core.Type.isObjectLike(openLineItems)) {
	      instance.setOpenLineItems(openLineItems);
	    }
	    if (main_core.Type.isStringFilled(serviceId)) {
	      instance.setServiceId(serviceId);
	    } else {
	      throw new TypeError('ServiceId is required');
	    }
	    if (BX.CrmEntityType.isDefined(entityTypeId)) {
	      instance.setEntityTypeId(entityTypeId);
	    } else {
	      throw new TypeError('EntityTypeId is not specified or incorrect');
	    }
	    return instance.check();
	  }
	  static async checkIsApproved({
	    senderType
	  }) {
	    const instance = new ConditionChecker({
	      senderType
	    });
	    return instance.checkApproveConsent();
	  }

	  /**
	   * @param {string} openLineCode
	   * @param {string} senderType
	   */
	  constructor({
	    senderType
	  }) {
	    Object.defineProperty(this, _openLineItems, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _senderType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _serviceId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _senderType$1)[_senderType$1] = senderType;
	  }
	  setOpenLineItems(items) {
	    babelHelpers.classPrivateFieldLooseBase(this, _openLineItems)[_openLineItems] = items;
	    return this;
	  }
	  setServiceId(serviceId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _serviceId)[_serviceId] = serviceId;
	    return this;
	  }
	  setEntityTypeId(entityTypeId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = entityTypeId;
	    return this;
	  }
	  async check() {
	    const scenario = Factory.getScenarioInstance(babelHelpers.classPrivateFieldLooseBase(this, _serviceId)[_serviceId], {
	      senderType: babelHelpers.classPrivateFieldLooseBase(this, _senderType$1)[_senderType$1],
	      openLineItems: babelHelpers.classPrivateFieldLooseBase(this, _openLineItems)[_openLineItems],
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]
	    });
	    return scenario.checkAndGetLineId();
	  }
	  async checkApproveConsent() {
	    const isApproved = await new ConsentApprover(babelHelpers.classPrivateFieldLooseBase(this, _senderType$1)[_senderType$1]).checkAndApprove();
	    if (isApproved) {
	      return Promise.resolve(true);
	    }
	    return Promise.resolve(null);
	  }
	}

	function ensureIsItemIdentifier(candidate) {
	  if (candidate instanceof crm_dataStructures.ItemIdentifier) {
	    return;
	  }
	  throw new Error('Argument should be an instance of ItemIdentifier');
	}
	function ensureIsReceiver(candidate) {
	  if (candidate instanceof Receiver) {
	    return;
	  }
	  throw new Error('Argument should be an instance of Receiver');
	}
	function ensureIsValidMultifieldValue(candidate) {
	  // noinspection OverlyComplexBooleanExpressionJS
	  const isValidValue = main_core.Type.isPlainObject(candidate) && (main_core.Type.isNil(candidate.id) || main_core.Type.isInteger(candidate.id)) && main_core.Type.isStringFilled(candidate.typeId) && main_core.Type.isStringFilled(candidate.valueType) && main_core.Type.isStringFilled(candidate.value);
	  if (isValidValue) {
	    return;
	  }
	  throw new Error('Argument should be an object of valid MultifieldValue structure');
	}
	function ensureIsValidSourceData(candidate) {
	  const isValid = main_core.Type.isPlainObject(candidate) && main_core.Type.isStringFilled(candidate.title);
	  if (isValid) {
	    return;
	  }
	  throw new Error('Argument should be an object of valid SourceData structure');
	}

	var _rootSource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rootSource");
	var _addressSource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addressSource");
	var _addressSourceData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addressSourceData");
	var _address = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("address");
	class Receiver {
	  constructor(rootSource, addressSource, address, addressSourceData = null) {
	    Object.defineProperty(this, _rootSource, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addressSource, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addressSourceData, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _address, {
	      writable: true,
	      value: void 0
	    });
	    ensureIsItemIdentifier(rootSource);
	    babelHelpers.classPrivateFieldLooseBase(this, _rootSource)[_rootSource] = rootSource;
	    ensureIsItemIdentifier(addressSource);
	    babelHelpers.classPrivateFieldLooseBase(this, _addressSource)[_addressSource] = addressSource;
	    ensureIsValidMultifieldValue(address);
	    babelHelpers.classPrivateFieldLooseBase(this, _address)[_address] = Object.freeze({
	      id: address.id,
	      typeId: address.typeId,
	      valueType: address.valueType,
	      value: address.value,
	      valueFormatted: address.valueFormatted
	    });
	    if (addressSourceData) {
	      ensureIsValidSourceData(addressSourceData);
	      babelHelpers.classPrivateFieldLooseBase(this, _addressSourceData)[_addressSourceData] = Object.freeze({
	        title: addressSourceData.title
	      });
	    }
	  }
	  static fromJSON(data) {
	    const rootSource = crm_dataStructures.ItemIdentifier.fromJSON(data == null ? void 0 : data.rootSource);
	    if (!rootSource) {
	      return null;
	    }
	    const addressSource = crm_dataStructures.ItemIdentifier.fromJSON(data == null ? void 0 : data.addressSource);
	    if (!addressSource) {
	      return null;
	    }
	    try {
	      return new Receiver(rootSource, addressSource, data == null ? void 0 : data.address, data == null ? void 0 : data.addressSourceData);
	    } catch (e) {
	      return null;
	    }
	  }
	  get rootSource() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _rootSource)[_rootSource];
	  }
	  get addressSource() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _addressSource)[_addressSource];
	  }
	  get addressSourceData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _addressSourceData)[_addressSourceData];
	  }
	  get address() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _address)[_address];
	  }
	  isEqualTo(another) {
	    if (!(another instanceof Receiver)) {
	      return false;
	    }

	    // noinspection OverlyComplexBooleanExpressionJS
	    return this.rootSource.isEqualTo(another.rootSource) && this.addressSource.isEqualTo(another.addressSource) && String(this.address.typeId) === String(another.address.typeId) && String(this.address.valueType) === String(another.address.valueType) && String(this.address.value) === String(another.address.value);
	  }
	}

	function extractReceivers(item, entityData) {
	  const receivers = [];
	  if (entityData != null && entityData.hasOwnProperty('MULTIFIELD_DATA')) {
	    receivers.push(...extractReceiversFromMultifieldData(item, entityData));
	  }
	  if (entityData != null && entityData.hasOwnProperty('CLIENT_INFO')) {
	    receivers.push(...extractReceiversFromClientInfo(item, entityData.CLIENT_INFO));
	  }
	  return unique(receivers);
	}
	function extractReceiversFromMultifieldData(item, entityData) {
	  const receivers = [];
	  const multifields = entityData.MULTIFIELD_DATA;
	  for (const multifieldTypeId in multifields) {
	    if (!multifields.hasOwnProperty(multifieldTypeId) || !main_core.Type.isPlainObject(multifields[multifieldTypeId])) {
	      continue;
	    }
	    for (const itemSlug in multifields[multifieldTypeId]) {
	      if (!multifields[multifieldTypeId].hasOwnProperty(itemSlug) || !main_core.Type.isArrayFilled(multifields[multifieldTypeId][itemSlug])) {
	        continue;
	      }
	      const [entityTypeId, entityId] = itemSlug.split('_');
	      let addressSource;
	      try {
	        addressSource = new crm_dataStructures.ItemIdentifier(main_core.Text.toInteger(entityTypeId), main_core.Text.toInteger(entityId));
	      } catch (e) {
	        continue;
	      }
	      const addressSourceTitle = getAddressSourceTitle(item, addressSource, entityData);
	      for (const singleMultifield of multifields[multifieldTypeId][itemSlug]) {
	        try {
	          receivers.push(new Receiver(item, addressSource, {
	            id: main_core.Text.toInteger(singleMultifield.ID),
	            typeId: String(multifieldTypeId),
	            valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
	            value: stringOrUndefined(singleMultifield.VALUE),
	            valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED)
	          }, {
	            title: addressSourceTitle
	          }));
	        } catch (e) {}
	      }
	    }
	  }
	  return receivers;
	}
	function getAddressSourceTitle(rootSource, addressSource, entityData) {
	  var _entityData$CLIENT_IN;
	  if (rootSource.isEqualTo(addressSource)) {
	    var _ref, _entityData$TITLE;
	    return (_ref = (_entityData$TITLE = entityData == null ? void 0 : entityData.TITLE) != null ? _entityData$TITLE : entityData.FORMATTED_NAME) != null ? _ref : '';
	  }
	  const clientDataKey = `${BX.CrmEntityType.resolveName(addressSource.entityTypeId)}_DATA`;
	  if (main_core.Type.isArrayFilled(entityData == null ? void 0 : (_entityData$CLIENT_IN = entityData.CLIENT_INFO) == null ? void 0 : _entityData$CLIENT_IN[clientDataKey])) {
	    const client = entityData.CLIENT_INFO[clientDataKey].find(clientInfo => {
	      return main_core.Text.toInteger(clientInfo.id) === addressSource.entityId;
	    });
	    if (main_core.Type.isString(client == null ? void 0 : client.title)) {
	      return client.title;
	    }
	  }
	  return '';
	}
	function extractReceiversFromClientInfo(item, clientInfo) {
	  const receivers = [];
	  for (const clientsOfSameType of Object.values(clientInfo)) {
	    if (!main_core.Type.isArrayFilled(clientsOfSameType)) {
	      continue;
	    }
	    for (const singleClient of clientsOfSameType) {
	      var _singleClient$advance;
	      if (!main_core.Type.isPlainObject(singleClient)) {
	        continue;
	      }
	      let addressSource;
	      try {
	        addressSource = new crm_dataStructures.ItemIdentifier(BX.CrmEntityType.resolveId(singleClient.typeName), singleClient.id);
	      } catch (e) {
	        continue;
	      }
	      const multifields = (_singleClient$advance = singleClient.advancedInfo) == null ? void 0 : _singleClient$advance.multiFields;
	      if (!main_core.Type.isArrayFilled(multifields)) {
	        continue;
	      }
	      for (const singleMultifield of multifields) {
	        try {
	          receivers.push(new Receiver(item, addressSource, {
	            id: main_core.Text.toInteger(singleMultifield.ID),
	            typeId: stringOrUndefined(singleMultifield.TYPE_ID),
	            valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
	            value: stringOrUndefined(singleMultifield.VALUE),
	            valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED)
	          }, {
	            title: stringOrUndefined(singleClient.title)
	          }));
	        } catch (e) {}
	      }
	    }
	  }
	  return receivers;
	}
	function stringOrUndefined(value) {
	  return main_core.Type.isNil(value) ? undefined : String(value);
	}
	function unique(receivers) {
	  return receivers.filter((receiver, index) => {
	    const anotherIndex = receivers.findIndex(anotherReceiver => receiver.isEqualTo(anotherReceiver));
	    return anotherIndex === index;
	  });
	}

	const OBSERVED_EVENTS = new Set(['onCrmEntityCreate', 'onCrmEntityUpdate', 'onCrmEntityDelete']);

	/**
	 * @memberOf BX.Crm.MessageSender
	 * @mixes EventEmitter
	 *
	 * @emits BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged
	 * @emits BX.Crm.MessageSender.ReceiverRepository:OnItemDeleted
	 *
	 * Currently, this class is supposed to work only in the context of entity details tab.
	 * In the future, it can be extended to work on any page. (see todos)
	 */
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _onDetailsTabChangeEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDetailsTabChangeEventHandler");
	var _storage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("storage");
	var _observedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observedItems");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _destroy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroy");
	var _onCrmEntityChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCrmEntityChange");
	var _addReceivers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addReceivers");
	var _startObservingItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startObservingItem");
	class ReceiverRepository {
	  static get Instance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance] = new ReceiverRepository();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance];
	  }

	  /**
	   * @internal This class is a singleton. Use Instance getter instead of constructing a new instance
	   */
	  constructor() {
	    Object.defineProperty(this, _startObservingItem, {
	      value: _startObservingItem2
	    });
	    Object.defineProperty(this, _addReceivers, {
	      value: _addReceivers2
	    });
	    Object.defineProperty(this, _onCrmEntityChange, {
	      value: _onCrmEntityChange2
	    });
	    Object.defineProperty(this, _destroy, {
	      value: _destroy2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _onDetailsTabChangeEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _storage, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _observedItems, {
	      writable: true,
	      value: {}
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance]) {
	      throw new Error('Attempt to make a new instance of a singleton');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  /**
	   * @internal
	   */
	  static onDetailsLoad(entityTypeId, entityId, receiversJSONString) {
	    let item = null;
	    try {
	      item = new crm_dataStructures.ItemIdentifier(entityTypeId, entityId);
	    } catch {
	      return;
	    }
	    const instance = ReceiverRepository.Instance;
	    // todo notify instances of this class on other tabs/sliders
	    babelHelpers.classPrivateFieldLooseBase(instance, _startObservingItem)[_startObservingItem](item);
	    const receiversJSON = JSON.parse(receiversJSONString);
	    if (main_core.Type.isArrayFilled(receiversJSON)) {
	      const receivers = [];
	      for (const singleReceiverJSON of receiversJSON) {
	        const receiver = Receiver.fromJSON(singleReceiverJSON);
	        if (!main_core.Type.isNil(receiver)) {
	          receivers.push(receiver);
	        }
	      }
	      if (main_core.Type.isArrayFilled(receivers)) {
	        // todo add receivers to instances of this class on other tabs/sliders
	        babelHelpers.classPrivateFieldLooseBase(instance, _addReceivers)[_addReceivers](item, receivers);
	      }
	    }
	  }
	  getReceivers(entityTypeId, entityId) {
	    try {
	      return this.getReceiversByIdentifier(new crm_dataStructures.ItemIdentifier(entityTypeId, entityId));
	    } catch {
	      return [];
	    }
	  }
	  getReceiversByIdentifier(item) {
	    var _babelHelpers$classPr;
	    ensureIsItemIdentifier(item);
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash]) != null ? _babelHelpers$classPr : [];
	  }
	}
	function _init2() {
	  var _BX$SidePanel, _BX$SidePanel$Instanc;
	  main_core_events.EventEmitter.makeObservable(this, 'BX.Crm.MessageSender.ReceiverRepository');
	  babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler] = event => {
	    if (!(event instanceof main_core_events.BaseEvent)) {
	      console.error('unexpected event type', event);
	      return;
	    }
	    if (!main_core.Type.isArrayFilled(event.getData()) || !main_core.Type.isPlainObject(event.getData()[0])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _onCrmEntityChange)[_onCrmEntityChange](event.getType(), event.getData()[0]);
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler].bind(this);
	  for (const eventName of OBSERVED_EVENTS) {
	    // todo use BX.Crm.EntityEvent.subscribe instead, we will get data from all tabs/sliders
	    main_core_events.EventEmitter.subscribe(eventName, babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler]);
	  }
	  if ((_BX$SidePanel = BX.SidePanel) != null && (_BX$SidePanel$Instanc = _BX$SidePanel.Instance) != null && _BX$SidePanel$Instanc.isOpen()) {
	    // we are on entity details slider
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onDestroy', babelHelpers.classPrivateFieldLooseBase(this, _destroy)[_destroy].bind(this));
	  }
	}
	function _destroy2() {
	  for (const eventName of OBSERVED_EVENTS) {
	    main_core_events.EventEmitter.unsubscribe(eventName, babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance] = null;
	}
	function _onCrmEntityChange2(eventType, {
	  entityTypeId,
	  entityId,
	  entityData
	}) {
	  var _babelHelpers$classPr2;
	  if (!((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][entityTypeId]) != null && _babelHelpers$classPr2.has(entityId))) {
	    return;
	  }
	  const item = new crm_dataStructures.ItemIdentifier(entityTypeId, entityId);
	  if (eventType.toLowerCase() === 'onCrmEntityCreate'.toLowerCase() || eventType.toLowerCase() === 'onCrmEntityUpdate'.toLowerCase()) {
	    var _babelHelpers$classPr3;
	    const oldReceivers = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash]) != null ? _babelHelpers$classPr3 : [];
	    const newReceivers = extractReceivers(item, entityData);
	    babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash] = newReceivers;
	    const added = newReceivers.filter(newReceiver => {
	      return main_core.Type.isNil(oldReceivers.find(oldReceiver => oldReceiver.isEqualTo(newReceiver)));
	    });
	    const deleted = oldReceivers.filter(oldReceiver => {
	      return main_core.Type.isNil(newReceivers.find(newReceiver => newReceiver.isEqualTo(oldReceiver)));
	    });
	    if (added.length > 0 || deleted.length > 0) {
	      this.emit('OnReceiversChanged', {
	        item,
	        previous: oldReceivers,
	        current: newReceivers,
	        added,
	        deleted
	      });
	    }
	  } else if (eventType.toLowerCase() === 'onCrmEntityDelete'.toLowerCase()) {
	    delete babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash];
	    babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId].delete(item.entityId);
	    this.emit('OnItemDeleted', {
	      item
	    });
	  } else {
	    console.error('unknown event type', eventType);
	  }
	}
	function _addReceivers2(item, receivers) {
	  ensureIsItemIdentifier(item);
	  babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash] = [];
	  for (const receiver of receivers) {
	    ensureIsReceiver(receiver);
	    babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash].push(receiver);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _startObservingItem)[_startObservingItem](item);
	}
	function _startObservingItem2(item) {
	  var _babelHelpers$classPr4;
	  ensureIsItemIdentifier(item);
	  const observedItemsOfThisType = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId]) != null ? _babelHelpers$classPr4 : new Set();
	  observedItemsOfThisType.add(item.entityId);
	  babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId] = observedItemsOfThisType;
	}
	Object.defineProperty(ReceiverRepository, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.ConditionChecker = ConditionChecker;
	exports.ReceiverRepository = ReceiverRepository;
	exports.Receiver = Receiver;
	exports.Types = Types;

}((this.BX.Crm.MessageSender = this.BX.Crm.MessageSender || {}),BX.Crm,BX.UI.Dialogs,BX.Event,BX,BX.Crm.DataStructures));
//# sourceMappingURL=messagesender.bundle.js.map

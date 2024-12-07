/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,ui_buttons,ui_forms,ui_alerts,ui_layoutForm,main_core,ui_dialogs_messagebox) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	const DEFAULT_LANGUAGE_ID = 'en';
	var _getSelectedServer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedServer");
	var _getLanguageId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLanguageId");
	var _getServiceName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getServiceName");
	var _showOnlyErrorRowInPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showOnlyErrorRowInPopup");
	var _buildUsefulErrorText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildUsefulErrorText");
	class ClientRegistration {
	  constructor(options) {
	    Object.defineProperty(this, _buildUsefulErrorText, {
	      value: _buildUsefulErrorText2
	    });
	    Object.defineProperty(this, _showOnlyErrorRowInPopup, {
	      value: _showOnlyErrorRowInPopup2
	    });
	    Object.defineProperty(this, _getServiceName, {
	      value: _getServiceName2
	    });
	    Object.defineProperty(this, _getLanguageId, {
	      value: _getLanguageId2
	    });
	    Object.defineProperty(this, _getSelectedServer, {
	      value: _getSelectedServer2
	    });
	    this.popupContainerId = 'content-register-modal';
	    this.options = options;
	    this.bindEvents();
	  }
	  bindEvents() {}
	  start() {
	    const allowedServerPromise = main_core.ajax.runAction('ai.integration.b24cloudai.listAllowedServers').then(response => {
	      return response.data.servers;
	    });
	    const warning = main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_WARNING', {
	      '#NAME#': babelHelpers.classPrivateFieldLooseBase(this, _getServiceName)[_getServiceName]()
	    });
	    const popupContent = main_core.Tag.render(_t || (_t = _`
			<div class="ui-form ai__cloud-client-registration-form" id="${0}">
				<div class="ui-form-row" style="display: none">
					<div class="ui-alert ui-alert-icon-danger ui-alert-xs ui-alert-danger">
						<span class="ui-alert-message"></span>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">${0}</div>
					</div>
					<div class="ui-form-content">
						<div
							ref="selectWrapper"
							class="ui-ctl ui-ctl-w100 --loading ui-ctl-after-icon ui-ctl-dropdown ai__cloud-client-registration-form_servers-select-wrapper"
						>
							<div class="ui-ctl-after ui-ctl-icon-loader"></div>
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select ref="select" class="ui-ctl-element"></select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
					<div class="ui-alert ui-alert-icon-info ui-alert-xs">
						<span class="ui-alert-message">
							${0}
						</span>
				</div>
			</div>
		`), this.popupContainerId, main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_SELECT_SERVER_LABEL'), warning);
	    const popup = new main_popup.Popup({
	      overlay: true,
	      minHeight: 280,
	      width: 400,
	      content: popupContent.root,
	      closeIcon: true,
	      cacheable: false,
	      buttons: [new ui_buttons.SaveButton({
	        id: 'save-button',
	        text: main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_BUTTON'),
	        onclick: this.handleClickRegister.bind(this),
	        state: ui_buttons.SaveButton.State.DISABLED
	      })]
	    });
	    popup.show();
	    allowedServerPromise.then(servers => {
	      const select = popupContent.select;
	      servers.forEach(server => {
	        const regionSuffix = server.region ? ` (${server.region})` : '';
	        select.add(main_core.Tag.render(_t2 || (_t2 = _`<option value="${0}">${0}${0}</option>`), server.proxy, server.proxy, regionSuffix));
	      });
	      const btn = popup.getButton('save-button');
	      btn.setState(null);
	      main_core.Dom.removeClass(popupContent.selectWrapper, '--loading');
	    }).catch(response => {
	      console.error('Error fetching allowed servers', response);
	      babelHelpers.classPrivateFieldLooseBase(this, _showOnlyErrorRowInPopup)[_showOnlyErrorRowInPopup](main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_ALLOWED_SERVERS'));
	      main_core.Dom.removeClass(popupContent.selectWrapper, '--loading');
	      const btn = popup.getButton('save-button');
	      btn.setState(ui_buttons.SaveButton.State.DISABLED);
	    });
	  }
	  handleClickRegister(button) {
	    button.setDisabled();
	    button.setState(ui_buttons.SaveButton.State.WAITING);
	    main_core.ajax.runAction('ai.integration.b24cloudai.register', {
	      data: {
	        serviceUrl: babelHelpers.classPrivateFieldLooseBase(this, _getSelectedServer)[_getSelectedServer](),
	        languageId: babelHelpers.classPrivateFieldLooseBase(this, _getLanguageId)[_getLanguageId]()
	      }
	    }).then(() => {
	      document.location.reload();
	    }).catch(response => {
	      console.error('Registration error', response);
	      button.setState(ui_buttons.SaveButton.State.DISABLED);
	      babelHelpers.classPrivateFieldLooseBase(this, _showOnlyErrorRowInPopup)[_showOnlyErrorRowInPopup](babelHelpers.classPrivateFieldLooseBase(this, _buildUsefulErrorText)[_buildUsefulErrorText](response.errors || []));
	    });
	  }
	}
	function _getSelectedServer2() {
	  const selectNode = document.querySelector(`#${this.popupContainerId} select`);
	  if (!selectNode) {
	    return '';
	  }
	  return selectNode.value;
	}
	function _getLanguageId2() {
	  return main_core.Loc.hasMessage('LANGUAGE_ID') ? main_core.Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID;
	}
	function _getServiceName2() {
	  return 'AiProxy';
	}
	function _showOnlyErrorRowInPopup2(message) {
	  const rows = document.querySelectorAll(`#${this.popupContainerId} .ui-form-row`);
	  rows.forEach(row => {
	    main_core.Dom.style(row, 'display', 'none');
	  });
	  main_core.Dom.style(rows[0], 'display', '');
	  rows[0].querySelector('.ui-alert-message').textContent = message;
	}
	function _buildUsefulErrorText2(errors) {
	  for (const error of errors) {
	    if (error.code === 'tariff_restriction') {
	      return main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_AFTER_REG', {
	        '#NAME#': babelHelpers.classPrivateFieldLooseBase(this, _getServiceName)[_getServiceName]()
	      });
	    }
	    if (error.code === 'should_show_in_ui') {
	      return error.message;
	    }
	    if (error.code === 'domain_verification') {
	      return main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_DOMAIN_VERIFICATION', {
	        '#NAME#': babelHelpers.classPrivateFieldLooseBase(this, _getServiceName)[_getServiceName](),
	        '#DOMAIN#': error.customData.domain
	      });
	    }
	  }
	  return main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_COMMON');
	}

	const DEFAULT_LANGUAGE_ID$1 = 'en';
	var _getLanguageId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLanguageId");
	var _getServiceName$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getServiceName");
	var _buildUsefulErrorText$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildUsefulErrorText");
	class ClientUnRegistration {
	  constructor(options) {
	    Object.defineProperty(this, _buildUsefulErrorText$1, {
	      value: _buildUsefulErrorText2$1
	    });
	    Object.defineProperty(this, _getServiceName$1, {
	      value: _getServiceName2$1
	    });
	    Object.defineProperty(this, _getLanguageId$1, {
	      value: _getLanguageId2$1
	    });
	    this.popupContainerId = 'content-register-modal';
	    this.options = options;
	    this.bindEvents();
	  }
	  bindEvents() {}
	  start() {
	    this.messageBox = ui_dialogs_messagebox.MessageBox.create({
	      title: main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_TITLE', {
	        '#NAME#': babelHelpers.classPrivateFieldLooseBase(this, _getServiceName$1)[_getServiceName$1]()
	      }),
	      message: main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_MSG', {
	        '#NAME#': babelHelpers.classPrivateFieldLooseBase(this, _getServiceName$1)[_getServiceName$1]()
	      }),
	      buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_UNREGISTER_BTN'),
	      onOk: () => {
	        this.handleClickUnregister();
	      }
	    });
	    this.messageBox.show();
	  }
	  handleClickUnregister() {
	    main_core.ajax.runAction('ai.integration.b24cloudai.unregister', {
	      data: {
	        languageId: babelHelpers.classPrivateFieldLooseBase(this, _getLanguageId$1)[_getLanguageId$1]()
	      }
	    }).then(() => {
	      document.location.reload();
	    }).catch(response => {
	      // eslint-disable-next-line no-console
	      console.warn('Unregistration error', response);
	      this.messageBox.setMessage(babelHelpers.classPrivateFieldLooseBase(this, _buildUsefulErrorText$1)[_buildUsefulErrorText$1](response.errors || []));
	    });
	  }
	}
	function _getLanguageId2$1() {
	  return main_core.Loc.hasMessage('LANGUAGE_ID') ? main_core.Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID$1;
	}
	function _getServiceName2$1() {
	  return main_core.Extension.getSettings('disk.b24-documents-client-registration').get('serviceName');
	}
	function _buildUsefulErrorText2$1(errors) {
	  for (const error of errors) {
	    if (error.code === 'should_show_in_ui') {
	      return error.message;
	    }
	  }
	  return main_core.Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_ERROR_COMMON');
	}

	exports.ClientRegistration = ClientRegistration;
	exports.ClientUnRegistration = ClientUnRegistration;

}((this.BX.AI = this.BX.AI || {}),BX.Main,BX.UI,BX,BX.UI,BX.UI,BX,BX.UI.Dialogs));
//# sourceMappingURL=cloud-client-registration.bundle.js.map

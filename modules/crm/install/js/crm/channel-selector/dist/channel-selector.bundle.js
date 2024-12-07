/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_router,main_core,main_core_events,main_loader,main_popup,ui_icons_service,ui_menuConfigurable) {
	'use strict';

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
	  _t12;
	const CrmActivityEditor = main_core.Reflection.namespace('BX.CrmActivityEditor');
	const UserOptions = main_core.Reflection.namespace('BX.userOptions');
	const NotificationCenter = main_core.Reflection.namespace('BX.UI.Notification.Center');
	const CHANNEL_TYPE_PHONE = 'PHONE';
	const CHANNEL_TYPE_EMAIL = 'EMAIL';
	const CHANNEL_TYPE_IM = 'IM';
	const MAX_VISIBLE_ITEMS = 4;
	const MARKET_LINK = 'category/crm_robot_sms/';
	const LINK_IN_MESSAGE_PLACEHOLDER = '#LINK#';
	const items = new Map();

	/**
	 * @memberof BX.Crm.ChannelSelector
	 */
	var _link = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("link");
	var _isInsertLinkInMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isInsertLinkInMessage");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _getLinkPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLinkPromise");
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _menuConfigurable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuConfigurable");
	var _getChannelById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getChannelById");
	var _isChannelAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isChannelAvailable");
	var _getLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLink");
	var _handleFooterClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFooterClick");
	var _handleSettingsClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSettingsClick");
	var _openMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openMenu");
	var _getMenuItemsInViewMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemsInViewMode");
	var _getMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenu");
	var _switchMenuToEditMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("switchMenuToEditMode");
	var _saveSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveSettings");
	var _switchMenuToViewMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("switchMenuToViewMode");
	var _getMenuItemsInEditMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemsInEditMode");
	var _getMenuConfigurable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuConfigurable");
	var _showGetLinkErrorNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showGetLinkErrorNotification");
	var _showNotice = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotice");
	var _handleChannelClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleChannelClick");
	var _getSmsText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSmsText");
	var _openContactCenter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openContactCenter");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	class List extends main_core_events.EventEmitter {
	  constructor(parameters) {
	    var _parameters$templateC;
	    super();
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _openContactCenter, {
	      value: _openContactCenter2
	    });
	    Object.defineProperty(this, _getSmsText, {
	      value: _getSmsText2
	    });
	    Object.defineProperty(this, _handleChannelClick, {
	      value: _handleChannelClick2
	    });
	    Object.defineProperty(this, _showNotice, {
	      value: _showNotice2
	    });
	    Object.defineProperty(this, _showGetLinkErrorNotification, {
	      value: _showGetLinkErrorNotification2
	    });
	    Object.defineProperty(this, _getMenuConfigurable, {
	      value: _getMenuConfigurable2
	    });
	    Object.defineProperty(this, _getMenuItemsInEditMode, {
	      value: _getMenuItemsInEditMode2
	    });
	    Object.defineProperty(this, _switchMenuToViewMode, {
	      value: _switchMenuToViewMode2
	    });
	    Object.defineProperty(this, _saveSettings, {
	      value: _saveSettings2
	    });
	    Object.defineProperty(this, _switchMenuToEditMode, {
	      value: _switchMenuToEditMode2
	    });
	    Object.defineProperty(this, _getMenu, {
	      value: _getMenu2
	    });
	    Object.defineProperty(this, _getMenuItemsInViewMode, {
	      value: _getMenuItemsInViewMode2
	    });
	    Object.defineProperty(this, _openMenu, {
	      value: _openMenu2
	    });
	    Object.defineProperty(this, _handleSettingsClick, {
	      value: _handleSettingsClick2
	    });
	    Object.defineProperty(this, _handleFooterClick, {
	      value: _handleFooterClick2
	    });
	    Object.defineProperty(this, _getLink, {
	      value: _getLink2
	    });
	    Object.defineProperty(this, _isChannelAvailable, {
	      value: _isChannelAvailable2
	    });
	    Object.defineProperty(this, _getChannelById, {
	      value: _getChannelById2
	    });
	    Object.defineProperty(this, _link, {
	      writable: true,
	      value: void 0
	    });
	    this.isCombineMessageWithLink = true;
	    Object.defineProperty(this, _isInsertLinkInMessage, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _getLinkPromise, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuConfigurable, {
	      writable: true,
	      value: void 0
	    });
	    this.title = main_core.Type.isStringFilled(parameters.title) ? parameters.title : main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_DEFAULT_TITLE');
	    this.documentTitle = String(parameters.documentTitle);
	    this.body = String(parameters.body);
	    this.fullBody = String(parameters.fullBody);
	    babelHelpers.classPrivateFieldLooseBase(this, _link)[_link] = String(parameters.link);
	    this.isLinkObtainable = main_core.Text.toBoolean(parameters.isLinkObtainable);
	    this.entityTypeId = main_core.Text.toInteger(parameters.entityTypeId);
	    this.entityId = main_core.Text.toInteger(parameters.entityId);
	    this.id = main_core.Type.isStringFilled(parameters.id) ? parameters.id : this.entityTypeId + '_' + this.entityId + '_' + Math.random().toString().substring(2);
	    this.storageTypeId = main_core.Text.toInteger(parameters.storageTypeId);
	    this.files = main_core.Type.isArray(parameters.files) ? parameters.files : [];
	    this.activityEditorId = String(parameters.activityEditorId);
	    this.smsUrl = String(parameters.smsUrl);
	    this.isCombineMessageWithLink = main_core.Type.isBoolean(parameters.isCombineMessageWithLink) ? parameters.isCombineMessageWithLink : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isInsertLinkInMessage)[_isInsertLinkInMessage] = main_core.Type.isBoolean(parameters.isInsertLinkInMessage) ? parameters.isInsertLinkInMessage : false;
	    this.templateCode = (_parameters$templateC = parameters.templateCode) != null ? _parameters$templateC : null;
	    this.setChannels(parameters.channels);
	    this.communications = main_core.Type.isPlainObject(parameters.communications) ? parameters.communications : {};
	    this.hasVisibleChannels = main_core.Text.toBoolean(parameters.hasVisibleChannels);
	    this.channelIcons = main_core.Type.isArray(parameters.channelIcons) ? parameters.channelIcons : [];
	    this.contactCenterUrl = main_core.Type.isStringFilled(parameters.contactCenterUrl) ? parameters.contactCenterUrl : '/contact_center/';
	    this.layout = {
	      channels: {}
	    };
	    this.setEventNamespace('BX.Crm.ChannelSelector.List');
	    if (items.size === 0) {
	      items.set('default', this);
	    }
	    items.set(this.id, this);
	  }
	  setChannels(channels) {
	    this.channels = [];
	    if (main_core.Type.isArray(channels)) {
	      channels.forEach(channel => {
	        this.channels.push(channel);
	      });
	    }
	    return this;
	  }
	  render() {
	    if (this.layout.container) {
	      return this.layout.container;
	    }
	    this.layout.title = main_core.Tag.render(_t || (_t = _`<div class="crm__channel-selector--title">${0}</div>`), main_core.Text.encode(this.title));
	    if (babelHelpers.classPrivateFieldLooseBase(this, _link)[_link] || this.isLinkObtainable) {
	      this.layout.link = main_core.Tag.render(_t2 || (_t2 = _`<input type="text" class="crm__channel-selector--footer-link-hidden" value="${0}" />`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]));
	      // class="crm__channel-selector--footer --disabled"
	      this.layout.footer = main_core.Tag.render(_t3 || (_t3 = _`<div class="crm__channel-selector--footer" onclick="${0}">
				<div class="crm__channel-selector--footer-copy-link">
					<span class="crm__channel-selector--footer-text">${0}</span>
					${0}
				</div>
			</div>`), babelHelpers.classPrivateFieldLooseBase(this, _handleFooterClick)[_handleFooterClick].bind(this), main_core.Loc.getMessage('CRM_CHANNEL_FOOTER_TITLE'), this.layout.link);
	    } else {
	      this.layout.footer = null;
	    }
	    this.layout.settings = null;
	    if (this.hasVisibleChannels) {
	      this.layout.settings = main_core.Tag.render(_t4 || (_t4 = _`<button class="ui-btn ui-btn-xs ui-btn-link ui-btn-icon-setting crm__channel-selector--setting-btn" onclick="${0}"></button>`), babelHelpers.classPrivateFieldLooseBase(this, _handleSettingsClick)[_handleSettingsClick].bind(this));
	      this.layout.list = main_core.Tag.render(_t5 || (_t5 = _`<div class="crm__channel-selector--list"></div>`));
	      this.channels.forEach(channel => {
	        const channelNode = this.renderChannel(channel);
	        if (channelNode) {
	          this.layout.channels[channel.id] = channelNode;
	        }
	      });
	    } else {
	      this.layout.list = main_core.Tag.render(_t6 || (_t6 = _`<div class="crm__channel-selector--body">
			<div class="crm__channel-selector--networks">
				<div class="crm__channel-selector--networks-title">${0}</div>
				<div class="crm__channel-selector--networks-block">
					${0}
					<span class="crm__channel-selector--network-link --link" onclick="${0}">+ 15</span>
				</div>
				<span class="ui-btn ui-btn-xs ui-btn-primary ui-btn-no-caps ui-btn-round" onclick="${0};">${0}</span>
			</div>
		</div>`), main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_NO_ACTIVE_CHANNELS_TEXT'), this.channelIcons.map(icon => main_core.Tag.render(_t7 || (_t7 = _`<span class="crm__channel-selector--network-link --${0}" onclick="${0}"></span>`), icon, babelHelpers.classPrivateFieldLooseBase(this, _openContactCenter)[_openContactCenter].bind(this))), babelHelpers.classPrivateFieldLooseBase(this, _openContactCenter)[_openContactCenter].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _openContactCenter)[_openContactCenter].bind(this), main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_ACTIVATE_CHANNELS'));
	    }
	    this.layout.container = main_core.Tag.render(_t8 || (_t8 = _`<div class="crm__channel-selector--container">
			<div class="crm__channel-selector--header">
				${0}
				${0}
			</div>
			${0}
		</div>`), this.layout.title, this.layout.settings, this.layout.list);
	    if (this.layout.footer) {
	      this.layout.container.appendChild(this.layout.footer);
	    }
	    this.adjustAppearance();
	    return this.layout.container;
	  }
	  renderChannel(channel) {
	    const channelHandler = () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _handleChannelClick)[_handleChannelClick](channel);
	    };
	    const icon = List.getChannelIcon(channel);
	    return main_core.Tag.render(_t9 || (_t9 = _`<div 
			class="crm__channel-selector--channel"
			onclick="${0}"
		>
			${0}
			${0}
			${0}
			<div class="crm__channel-selector--channel-helper">
				<span class="crm__channel-selector--channel-helper-text">${0}</span>
			</div>
		</div>`), channelHandler, icon ? main_core.Tag.render(_t10 || (_t10 = _`<div class="crm__channel-selector--channel-icon ${0}"></div>`), icon) : '', this.renderChannelMainTitle(channel), this.renderChannelTitle(channel), main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_SEND_BUTTON'));
	  }
	  renderChannelMainTitle(channel) {
	    var _channel$categoryTitl;
	    return main_core.Tag.render(_t11 || (_t11 = _`<div class="crm__channel-selector--channel-main-title">
			${0}
		</div>`), main_core.Text.encode((_channel$categoryTitl = channel.categoryTitle) != null ? _channel$categoryTitl : channel.title));
	  }
	  renderChannelTitle(channel) {
	    if (!channel.categoryTitle) {
	      return null;
	    }
	    return main_core.Tag.render(_t12 || (_t12 = _`<div class="crm__channel-selector--channel-title">
			${0}
		</div>`), main_core.Text.encode(channel.title));
	  }
	  adjustAppearance() {
	    if (this.hasVisibleChannels) {
	      main_core.Dom.clean(this.layout.list);
	      let allChannelsAreHidden = true;
	      this.channels.forEach(channel => {
	        const node = this.layout.channels[channel.id];
	        if (node) {
	          this.layout.list.append(node);
	          if (babelHelpers.classPrivateFieldLooseBase(this, _isChannelAvailable)[_isChannelAvailable](channel)) {
	            main_core.Dom.removeClass(node, 'crm__channel-selector--channel-disabled');
	          } else {
	            main_core.Dom.addClass(node, 'crm__channel-selector--channel-disabled');
	          }
	          if (channel.isHidden) {
	            main_core.Dom.addClass(node, 'crm__channel-selector--channel-hidden');
	          } else {
	            main_core.Dom.removeClass(node, 'crm__channel-selector--channel-hidden');
	            allChannelsAreHidden = false;
	          }
	        }
	      });
	      if (allChannelsAreHidden) {
	        main_core.Dom.addClass(this.layout.list, '--empty');
	      } else {
	        main_core.Dom.removeClass(this.layout.list, '--empty');
	      }
	    }
	  }
	  setFiles(files) {
	    this.files = main_core.Type.isArray(files) ? files : [];
	    this.adjustAppearance();
	    return this;
	  }
	  setLink(link) {
	    babelHelpers.classPrivateFieldLooseBase(this, _link)[_link] = link != null ? link : null;
	    this.adjustAppearance();
	    return this;
	  }
	  sendEmail(channel) {
	    if (this.files.length <= 0 || Number(this.storageTypeId) <= 0) {
	      const channelNode = this.layout.channels[channel.id];
	      babelHelpers.classPrivateFieldLooseBase(this, _getLink)[_getLink]().then(link => {
	        var _CrmActivityEditor$it;
	        CrmActivityEditor == null ? void 0 : (_CrmActivityEditor$it = CrmActivityEditor.items[this.activityEditorId]) == null ? void 0 : _CrmActivityEditor$it.addEmail({
	          subject: this.documentTitle,
	          body: main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_MESSAGE_WITH_LINK', {
	            '#MESSAGE#': this.documentTitle,
	            '#LINK#': link
	          })
	        });
	      }).catch(reason => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showGetLinkErrorNotification)[_showGetLinkErrorNotification](channelNode, reason);
	      });
	    } else {
	      var _CrmActivityEditor$it2;
	      CrmActivityEditor == null ? void 0 : (_CrmActivityEditor$it2 = CrmActivityEditor.items[this.activityEditorId]) == null ? void 0 : _CrmActivityEditor$it2.addEmail({
	        subject: this.documentTitle,
	        diskfiles: this.files,
	        storageTypeID: this.storageTypeId
	      });
	    }
	  }
	  sendSms(channel) {
	    const channelNode = this.layout.channels[channel.id];
	    if (!this.smsUrl) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showGetLinkErrorNotification)[_showGetLinkErrorNotification](channelNode, 'No sms url');
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getLink)[_getLink]().then(link => {
	      const requestParams = {
	        entityTypeId: this.entityTypeId,
	        entityId: this.entityId,
	        text: babelHelpers.classPrivateFieldLooseBase(this, _getSmsText)[_getSmsText](channel, link),
	        providerId: channel.id,
	        isProviderFixed: 'N',
	        canUseBitrix24Provider: 'Y'
	      };
	      if (channel.templateCode) {
	        requestParams.templateCode = channel.templateCode;
	        requestParams.templatePlaceholders = channel.templatePlaceholders;
	        requestParams.templatePlaceholders.DOCUMENT_URL = link;
	        requestParams.isEditable = 'N';
	      }
	      crm_router.Router.openSlider(this.smsUrl, {
	        width: 443,
	        requestMethod: 'post',
	        requestParams
	      });
	    }).catch(reason => {
	      babelHelpers.classPrivateFieldLooseBase(this, _showGetLinkErrorNotification)[_showGetLinkErrorNotification](channelNode, reason);
	    });
	  }
	  openMessenger(channel) {
	    if (!top.BXIM) {
	      return;
	    }
	    if (!this.communications[channel.type]) {
	      return;
	    }
	    top.BXIM.openMessenger(this.communications[channel.type].VALUE, {
	      RECENT: "N",
	      MENU: "N"
	    });
	  }
	  getId() {
	    return this.id;
	  }
	  static getDefault() {
	    return items.get('default');
	  }
	  static getById(id) {
	    return items.get(id);
	  }
	  static getChannelIcon(channel) {
	    return channel.icon || List.getIconByChannelId(channel.id);
	  }
	  static getIconByChannelId(id) {
	    if (id === 'bitrix24') {
	      return '--service-bitrix24';
	    }
	    if (id === CHANNEL_TYPE_EMAIL) {
	      return '--service-email';
	    }
	    if (id === CHANNEL_TYPE_IM) {
	      return '--service-livechat';
	    }
	    if (id === 'twilio') {
	      return '--service-whatsapp';
	    }
	    if (id === 'ednaru' || id === 'ednaruimhpx') {
	      return '--service-edna';
	    }
	    return '--service-sms';
	  }
	}
	function _getChannelById2(id) {
	  return this.channels.find(channel => channel.id === id);
	}
	function _isChannelAvailable2(channel) {
	  if (!channel.isAvailable) {
	    return false;
	  }
	  const hasLink = this.isLinkObtainable || Boolean(babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]);
	  const hasFiles = CrmActivityEditor && this.storageTypeId > 0 && this.files.length > 0;
	  if (channel.type === CHANNEL_TYPE_PHONE || channel.type === CHANNEL_TYPE_IM) {
	    return hasLink;
	  }
	  if (channel.type === CHANNEL_TYPE_EMAIL) {
	    return hasLink || hasFiles;
	  }
	  if (channel.type === CHANNEL_TYPE_EMAIL && !(CrmActivityEditor != null && CrmActivityEditor.items[this.activityEditorId])) {
	    console.log('Email channel is disabled because the CrmActivityEditor instance is not found');
	    return false;
	  }
	  return channel.isAvailable;
	}
	function _getLink2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]) {
	    return Promise.resolve(babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]);
	  }
	  if (!this.isLinkObtainable) {
	    return Promise.reject();
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getLinkPromise)[_getLinkPromise]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getLinkPromise)[_getLinkPromise];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getLinkPromise)[_getLinkPromise] = new Promise((resolve, reject) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	    this.emitAsync('getLink').then(result => {
	      result.forEach(link => {
	        this.setLink(link);
	      });
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]) {
	        reject();
	      } else {
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]);
	      }
	    }).catch(reject).finally(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getLinkPromise)[_getLinkPromise] = null;
	      babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    });
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _getLinkPromise)[_getLinkPromise];
	}
	function _handleFooterClick2() {
	  if (!this.layout.link) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getLink)[_getLink]().then(link => {
	    this.layout.link.value = link;
	    this.layout.link.focus();
	    this.layout.link.setSelectionRange(0, this.layout.link.value.length);
	    document.execCommand("copy");
	    babelHelpers.classPrivateFieldLooseBase(this, _showNotice)[_showNotice](main_core.Loc.getMessage('CRM_CHANNEL_PUBLIC_LINK_COPIED_NOTIFICATION_MESSAGE'));
	  }).catch(reason => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showGetLinkErrorNotification)[_showGetLinkErrorNotification](this.layout.footer, reason);
	  });
	}
	function _handleSettingsClick2() {
	  const event = new main_core_events.BaseEvent();
	  this.emit('onSettingsClick', event);
	  if (event.isDefaultPrevented()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu]();
	}
	function _openMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().show();
	}
	function _getMenuItemsInViewMode2() {
	  const settingsItems = [];
	  this.channels.forEach(channel => {
	    if (channel.isHidden) {
	      settingsItems.push({
	        text: channel.title,
	        id: channel.id,
	        onclick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _handleChannelClick)[_handleChannelClick](channel);
	        }
	      });
	    }
	  });
	  settingsItems.push({
	    delimiter: true
	  });
	  settingsItems.push({
	    text: main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_CHOOSE_FROM_MARKET'),
	    id: 'market',
	    href: main_core.Loc.getMessage('MARKET_BASE_PATH') + MARKET_LINK,
	    onclick: (event, item) => {
	      var _item$getMenuWindow;
	      const menu = ((_item$getMenuWindow = item.getMenuWindow()) == null ? void 0 : _item$getMenuWindow.getRootMenuWindow()) || item.getMenuWindow();
	      menu == null ? void 0 : menu.close();
	    }
	  });
	  settingsItems.push({
	    delimiter: true
	  });
	  settingsItems.push({
	    text: main_core.Loc.getMessage('CRM_COMMON_ACTION_CONFIG'),
	    id: 'configure',
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _switchMenuToEditMode)[_switchMenuToEditMode].bind(this)
	  });
	  return settingsItems;
	}
	function _getMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = main_popup.MenuManager.create({
	      id: this.id + '-settings-popup',
	      bindElement: this.layout.settings,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemsInViewMode)[_getMenuItemsInViewMode]()
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu];
	}
	function _switchMenuToEditMode2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().close();
	  babelHelpers.classPrivateFieldLooseBase(this, _getMenuConfigurable)[_getMenuConfigurable]().open().then(result => {
	    if (result.isCanceled) {
	      return;
	    }
	    if (main_core.Type.isArray(result.items)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _saveSettings)[_saveSettings](result.items);
	      babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu]();
	    }
	  });
	}
	function _saveSettings2(items) {
	  const channels = [];
	  items.forEach(item => {
	    const channel = babelHelpers.classPrivateFieldLooseBase(this, _getChannelById)[_getChannelById](item.id);
	    if (channel) {
	      channel.isHidden = item.isHidden;
	      channels.push(channel);
	    }
	  });
	  this.setChannels(channels);
	  this.adjustAppearance();
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].destroy();
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = null;
	  UserOptions.save("crm", "channel-selector", "items", JSON.stringify(items));
	}
	function _switchMenuToViewMode2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable]) == null ? void 0 : _babelHelpers$classPr.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().show();
	}
	function _getMenuItemsInEditMode2() {
	  const items = [];
	  this.channels.forEach(channel => {
	    items.push({
	      text: channel.title,
	      id: channel.id,
	      isHidden: channel.isHidden
	    });
	  });
	  return items;
	}
	function _getMenuConfigurable2() {
	  const items = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemsInEditMode)[_getMenuItemsInEditMode]();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable] = new ui_menuConfigurable.Menu({
	      items,
	      bindElement: this.layout.settings,
	      maxVisibleItems: MAX_VISIBLE_ITEMS
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable].subscribe('Cancel', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu]();
	    });
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable].setItems(items);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _menuConfigurable)[_menuConfigurable];
	}
	function _showGetLinkErrorNotification2(bindElement, text) {
	  if (!main_core.Type.isStringFilled(text)) {
	    text = main_core.Loc.getMessage('CRM_CHANNEL_PUBLIC_LINK_NOT_AVAILABLE_NOTIFICATION_MESSAGE');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _showNotice)[_showNotice](text);
	}
	function _showNotice2(content) {
	  if (NotificationCenter) {
	    NotificationCenter.notify({
	      content
	    });
	  }
	}
	function _handleChannelClick2(channel) {
	  const event = new main_core_events.BaseEvent();
	  event.setData({
	    channel,
	    communications: this.communications[channel.type]
	  });
	  this.emit('onChannelClick', event);
	  if (event.isDefaultPrevented()) {
	    return;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isChannelAvailable)[_isChannelAvailable](channel)) {
	    return;
	  }
	  if (channel.type === CHANNEL_TYPE_EMAIL) {
	    this.sendEmail(channel);
	    return;
	  }
	  if (channel.type === CHANNEL_TYPE_PHONE) {
	    this.sendSms(channel);
	    return;
	  }
	  if (channel.type === CHANNEL_TYPE_IM) {
	    this.openMessenger(channel);
	  }
	}
	function _getSmsText2(channel, link) {
	  const message = channel.id === 'bitrix24' && this.fullBody ? this.fullBody : this.body;
	  if (this.isCombineMessageWithLink) {
	    return main_core.Loc.getMessage('CRM_CHANNEL_SELECTOR_MESSAGE_WITH_LINK', {
	      '#MESSAGE#': message,
	      '#LINK#': link
	    });
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isInsertLinkInMessage)[_isInsertLinkInMessage]) {
	    return message.replace(LINK_IN_MESSAGE_PLACEHOLDER, link);
	  }
	  return message;
	}
	function _openContactCenter2() {
	  crm_router.Router.openSlider(this.contactCenterUrl).then(() => {
	    location.reload();
	  });
	}
	function _showLoader2() {
	  const loader = babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]();
	  if (loader) {
	    loader.show(this.layout.container);
	  }
	}
	function _hideLoader2() {
	  const loader = babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]();
	  if (loader) {
	    loader.hide();
	  }
	}
	function _getLoader2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] && main_loader.Loader) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 100,
	      offset: {
	        left: "35%",
	        top: "-25%"
	      }
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	}

	exports.List = List;

}((this.BX.Crm.ChannelSelector = this.BX.Crm.ChannelSelector || {}),BX.Crm,BX,BX.Event,BX,BX.Main,BX,BX.UI.MenuConfigurable));
//# sourceMappingURL=channel-selector.bundle.js.map

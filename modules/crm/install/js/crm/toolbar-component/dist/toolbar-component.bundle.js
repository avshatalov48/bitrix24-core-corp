/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_clientSelector,ui_dialogs_messagebox,ui_navigationpanel,crm_router,pull_client,main_core,main_core_events,main_popup,ui_buttons,ui_tour) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _settings = /*#__PURE__*/new WeakMap();
	var _button = /*#__PURE__*/new WeakMap();
	var _isMenuOpened = /*#__PURE__*/new WeakMap();
	var _menuPopup = /*#__PURE__*/new WeakMap();
	var _isEnabled = /*#__PURE__*/new WeakMap();
	var _clientSelector = /*#__PURE__*/new WeakMap();
	var _getOwnerTypeName = /*#__PURE__*/new WeakSet();
	var _getOwnerId = /*#__PURE__*/new WeakSet();
	var _hasData = /*#__PURE__*/new WeakSet();
	var _enable = /*#__PURE__*/new WeakSet();
	var _openClientSelector = /*#__PURE__*/new WeakSet();
	var _openPopupMenu = /*#__PURE__*/new WeakSet();
	var _closeMenu = /*#__PURE__*/new WeakSet();
	var _onReceiversChange = /*#__PURE__*/new WeakSet();
	/**
	 * @abstract
	 */
	let CommunicationButton = /*#__PURE__*/function () {
	  /**
	   * @protected
	   */

	  /**
	   * @protected
	   */

	  /**
	   * @protected
	   */

	  function CommunicationButton(settings) {
	    babelHelpers.classCallCheck(this, CommunicationButton);
	    _classPrivateMethodInitSpec(this, _onReceiversChange);
	    _classPrivateMethodInitSpec(this, _closeMenu);
	    _classPrivateMethodInitSpec(this, _openPopupMenu);
	    _classPrivateMethodInitSpec(this, _openClientSelector);
	    _classPrivateMethodInitSpec(this, _enable);
	    _classPrivateMethodInitSpec(this, _hasData);
	    _classPrivateMethodInitSpec(this, _getOwnerId);
	    _classPrivateMethodInitSpec(this, _getOwnerTypeName);
	    _classPrivateFieldInitSpec(this, _settings, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isMenuOpened, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _menuPopup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isEnabled, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _clientSelector, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _settings, settings || {});
	    babelHelpers.classPrivateFieldSet(this, _button, babelHelpers.classPrivateFieldGet(this, _settings).button);
	    babelHelpers.classPrivateFieldGet(this, _button).bindEvent('click', this.onButtonClick.bind(this));
	    this.ownerInfo = BX.prop.getObject(babelHelpers.classPrivateFieldGet(this, _settings), 'ownerInfo', {});
	    this.data = BX.prop.getObject(babelHelpers.classPrivateFieldGet(this, _settings), 'data', {});
	    _classPrivateMethodGet(this, _enable, _enable2).call(this, _classPrivateMethodGet(this, _hasData, _hasData2).call(this));
	    this.useClientSelector = BX.prop.getBoolean(babelHelpers.classPrivateFieldGet(this, _settings), 'useClientSelector', false);
	    main_core_events.EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', _classPrivateMethodGet(this, _onReceiversChange, _onReceiversChange2).bind(this));
	  }
	  babelHelpers.createClass(CommunicationButton, [{
	    key: "getOwnerInfo",
	    value: function getOwnerInfo() {
	      return {
	        ownerID: this.ownerInfo.ENTITY_ID,
	        ownerType: this.ownerInfo.ENTITY_TYPE_NAME,
	        ownerUrl: this.ownerInfo.SHOW_URL,
	        ownerTitle: this.ownerInfo.TITLE
	      };
	    }
	  }, {
	    key: "getMultifieldTypeName",
	    /**
	     * @abstract
	     */
	    value: function getMultifieldTypeName() {
	      return '';
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      return babelHelpers.classPrivateFieldGet(this, _isEnabled);
	    }
	  }, {
	    key: "getAddAddressSourceMessage",
	    value: function getAddAddressSourceMessage(entityTypeName) {
	      return '';
	    }
	    /**
	     * @abstract
	     */
	  }, {
	    key: "onButtonClick",
	    value: function onButtonClick(button, event) {}
	  }, {
	    key: "prepareMenuItem",
	    value: function prepareMenuItem(key, value) {}
	  }, {
	    key: "openMenu",
	    value: function openMenu() {
	      if (babelHelpers.classPrivateFieldGet(this, _isMenuOpened)) {
	        _classPrivateMethodGet(this, _closeMenu, _closeMenu2).call(this);
	        return;
	      }
	      const menuItems = [];
	      for (const [key, items] of Object.entries(this.data)) {
	        for (const item of items) {
	          menuItems.push(this.prepareMenuItem(key, item));
	        }
	      }
	      if (this.useClientSelector) {
	        _classPrivateMethodGet(this, _openClientSelector, _openClientSelector2).call(this, menuItems);
	      } else {
	        _classPrivateMethodGet(this, _openPopupMenu, _openPopupMenu2).call(this, menuItems);
	      }
	    }
	  }, {
	    key: "onClientSelectorSelect",
	    value: function onClientSelectorSelect({
	      data: {
	        item
	      }
	    }) {
	      // may be implement in children classes
	    }
	  }]);
	  return CommunicationButton;
	}();
	function _getOwnerTypeName2() {
	  return BX.prop.getString(this.ownerInfo, 'ENTITY_TYPE_NAME', '');
	}
	function _getOwnerId2() {
	  return BX.prop.getInteger(this.ownerInfo, 'ENTITY_ID', 0);
	}
	function _hasData2() {
	  return main_core.Type.isPlainObject(this.data) && main_core.Type.isArrayFilled(Object.keys(this.data));
	}
	function _enable2(enabled) {
	  babelHelpers.classPrivateFieldSet(this, _isEnabled, Boolean(enabled));
	  babelHelpers.classPrivateFieldGet(this, _button).setDisabled(!babelHelpers.classPrivateFieldGet(this, _isEnabled));
	  if (!babelHelpers.classPrivateFieldGet(this, _isEnabled)) {
	    const title = this.getAddAddressSourceMessage(_classPrivateMethodGet(this, _getOwnerTypeName, _getOwnerTypeName2).call(this));
	    if (title) {
	      babelHelpers.classPrivateFieldGet(this, _button).getContainer().title = title;
	    }
	  }
	}
	function _openClientSelector2(menuItems) {
	  if (!babelHelpers.classPrivateFieldGet(this, _clientSelector)) {
	    babelHelpers.classPrivateFieldSet(this, _clientSelector, BX.Crm.ClientSelector.createFromItems({
	      targetNode: babelHelpers.classPrivateFieldGet(this, _button).getContainer(),
	      items: menuItems,
	      events: {
	        onSelect: this.onClientSelectorSelect.bind(this),
	        onShow: () => {
	          babelHelpers.classPrivateFieldSet(this, _isMenuOpened, true);
	        },
	        onHide: () => {
	          babelHelpers.classPrivateFieldSet(this, _isMenuOpened, false);
	        }
	      }
	    }));
	  }
	  babelHelpers.classPrivateFieldGet(this, _clientSelector).show();
	}
	function _openPopupMenu2(menuItems) {
	  babelHelpers.classPrivateFieldSet(this, _menuPopup, new main_popup.Menu({
	    bindElement: babelHelpers.classPrivateFieldGet(this, _button).getContainer(),
	    offsetTop: 0,
	    offsetLeft: 0,
	    cacheable: false,
	    items: menuItems,
	    events: {
	      onPopupShow: () => {
	        babelHelpers.classPrivateFieldSet(this, _isMenuOpened, true);
	      },
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldSet(this, _isMenuOpened, false);
	      },
	      onPopupDestroy: () => {
	        babelHelpers.classPrivateFieldSet(this, _isMenuOpened, false);
	        babelHelpers.classPrivateFieldSet(this, _menuPopup, null);
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _menuPopup).show();
	}
	function _closeMenu2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _menuPopup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.close();
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _clientSelector)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.hide();
	}
	function _onReceiversChange2(event) {
	  const {
	    item,
	    current
	  } = event.getData();
	  if (item.entityTypeName !== _classPrivateMethodGet(this, _getOwnerTypeName, _getOwnerTypeName2).call(this) || item.entityId !== _classPrivateMethodGet(this, _getOwnerId, _getOwnerId2).call(this)) {
	    return;
	  }
	  this.data = {};
	  for (const receiver of current) {
	    var _this$data, _this$data$key;
	    if (receiver.address.typeId !== this.getMultifieldTypeName()) {
	      continue;
	    }
	    const key = `${receiver.addressSource.entityTypeId}_${receiver.addressSource.entityId}`;
	    (_this$data$key = (_this$data = this.data)[key]) !== null && _this$data$key !== void 0 ? _this$data$key : _this$data[key] = [];
	    this.data[key].push({
	      ID: receiver.address.id,
	      ENTITY_ID: receiver.addressSource.entityId,
	      ENTITY_TYPE_NAME: receiver.addressSource.entityTypeName,
	      TYPE_ID: receiver.address.typeId,
	      VALUE_TYPE: receiver.address.valueType,
	      VALUE: receiver.address.value,
	      VALUE_FORMATTED: receiver.address.valueFormatted,
	      COMPLEX_ID: receiver.address.complexId,
	      COMPLEX_NAME: receiver.address.complexName,
	      OWNER: {
	        ID: receiver.addressSource.entityId,
	        TYPE_ID: receiver.addressSource.entityTypeId,
	        TITLE: receiver.addressSourceData.title
	      }
	    });
	  }
	  _classPrivateMethodGet(this, _enable, _enable2).call(this, _classPrivateMethodGet(this, _hasData, _hasData2).call(this));
	}

	let EmailButton = /*#__PURE__*/function (_CommunicationButton) {
	  babelHelpers.inherits(EmailButton, _CommunicationButton);
	  function EmailButton() {
	    babelHelpers.classCallCheck(this, EmailButton);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EmailButton).apply(this, arguments));
	  }
	  babelHelpers.createClass(EmailButton, [{
	    key: "getAddAddressSourceMessage",
	    value: function getAddAddressSourceMessage(entityTypeName) {
	      return main_core.Loc.getMessage(`CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_EMAIL_SEND_${entityTypeName}`) || main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_EMAIL_SEND');
	    }
	  }, {
	    key: "onButtonClick",
	    value: function onButtonClick(button, event) {
	      if (!this.isEnabled()) {
	        return;
	      }
	      if (!this.useClientSelector) {
	        BX.CrmActivityEditor.addEmail(this.getOwnerInfo());
	        return;
	      }
	      const keys = Object.keys(this.data);
	      if (keys.length === 1) {
	        const firstKey = keys[0];
	        const items = this.data[firstKey];
	        if (items.length === 1) {
	          BX.CrmActivityEditor.addEmail(this.getOwnerInfo());
	          return;
	        }
	      }
	      this.openMenu();
	    }
	  }, {
	    key: "prepareMenuItem",
	    value: function prepareMenuItem(key, value) {
	      var _value$VALUE, _value$VALUE_TYPE;
	      if (!main_core.Type.isPlainObject(value) || !this.useClientSelector) {
	        return {};
	      }
	      return {
	        id: value.ID,
	        title: value.OWNER ? value.OWNER.TITLE : value.VALUE_FORMATTED,
	        subtitle: value.OWNER ? `${value.VALUE_FORMATTED}, ${value.COMPLEX_NAME}` : value.COMPLEX_NAME,
	        avatar: null,
	        customData: {
	          entityId: value.OWNER ? value.OWNER.ID : null,
	          entityTypeId: value.OWNER ? value.OWNER.TYPE_ID : null,
	          value: (_value$VALUE = value.VALUE) !== null && _value$VALUE !== void 0 ? _value$VALUE : null,
	          valueType: (_value$VALUE_TYPE = value.VALUE_TYPE) !== null && _value$VALUE_TYPE !== void 0 ? _value$VALUE_TYPE : null
	        }
	      };
	    }
	  }, {
	    key: "onClientSelectorSelect",
	    value: function onClientSelectorSelect({
	      data: {
	        item
	      }
	    }) {
	      const {
	        customData
	      } = item;
	      const entityTypeId = customData.get('entityTypeId');
	      const data = this.getOwnerInfo();
	      data.mailDefaultCommunications = [{
	        ENTITY_ID: customData.get('entityId'),
	        ENTITY_TYPE_ID: entityTypeId,
	        ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(entityTypeId),
	        TYPE: this.getMultifieldTypeName(),
	        VALUE: customData.get('value'),
	        VALUE_TYPE: customData.get('valueType')
	      }];
	      BX.CrmActivityEditor.addEmail(data);
	    }
	  }, {
	    key: "getMultifieldTypeName",
	    value: function getMultifieldTypeName() {
	      return 'EMAIL';
	    }
	  }]);
	  return EmailButton;
	}(CommunicationButton);

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _openChat = /*#__PURE__*/new WeakSet();
	let MessengerButton = /*#__PURE__*/function (_CommunicationButton) {
	  babelHelpers.inherits(MessengerButton, _CommunicationButton);
	  function MessengerButton(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, MessengerButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MessengerButton).call(this, ...args));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _openChat);
	    return _this;
	  }
	  babelHelpers.createClass(MessengerButton, [{
	    key: "onButtonClick",
	    value: function onButtonClick(button, event) {
	      const keys = Object.keys(this.data);
	      if (keys.length === 1) {
	        const firstKey = keys[0];
	        const items = this.data[firstKey];
	        if (items.length === 1) {
	          const parts = firstKey.split('_');
	          if (parts.length >= 2) {
	            _classPrivateMethodGet$1(this, _openChat, _openChat2).call(this, firstKey, items[0]);
	            return;
	          }
	        }
	      }
	      this.openMenu();
	    }
	  }, {
	    key: "prepareMenuItem",
	    value: function prepareMenuItem(key, value) {
	      let messengerText = '';
	      let messengerValue = '';
	      if (main_core.Type.isPlainObject(value)) {
	        messengerValue = BX.prop.getString(value, 'VALUE', '');
	        const valueType = BX.prop.getString(value, 'VALUE_TYPE', '');
	        if (valueType === 'OPENLINE') {
	          // Open line does not have formatted value
	          messengerText = BX.prop.getString(value, 'COMPLEX_NAME', '');
	        } else {
	          messengerText = `${BX.prop.getString(value, 'COMPLEX_NAME', '')}: ${BX.prop.getString(value, 'VALUE_FORMATTED', '')}`;
	        }
	      } else {
	        messengerText = value;
	        messengerValue = value;
	      }
	      return {
	        text: messengerText,
	        onclick: () => {
	          _classPrivateMethodGet$1(this, _openChat, _openChat2).call(this, key, messengerValue);
	        }
	      };
	    }
	  }, {
	    key: "getMultifieldTypeName",
	    value: function getMultifieldTypeName() {
	      return 'IM';
	    }
	  }]);
	  return MessengerButton;
	}(CommunicationButton);
	function _openChat2(entityKey, messenger) {
	  if (main_core.Type.isNil(window.top.BXIM)) {
	    console.error('crm.toolbar-component: messaging not supported');
	    return;
	  }
	  const messengerValue = main_core.Type.isPlainObject(messenger) ? messenger.VALUE : messenger;
	  window.top.BXIM.openMessengerSlider(messengerValue, {
	    RECENT: 'N',
	    MENU: 'N'
	  });
	}

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _createClientSelectorMenuItem = /*#__PURE__*/new WeakSet();
	var _createPopupMenuItem = /*#__PURE__*/new WeakSet();
	var _addCall = /*#__PURE__*/new WeakSet();
	let PhoneButton = /*#__PURE__*/function (_CommunicationButton) {
	  babelHelpers.inherits(PhoneButton, _CommunicationButton);
	  function PhoneButton(...args) {
	    var _this;
	    babelHelpers.classCallCheck(this, PhoneButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PhoneButton).call(this, ...args));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _addCall);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _createPopupMenuItem);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _createClientSelectorMenuItem);
	    return _this;
	  }
	  babelHelpers.createClass(PhoneButton, [{
	    key: "getAddAddressSourceMessage",
	    value: function getAddAddressSourceMessage(entityTypeName) {
	      return main_core.Loc.getMessage(`CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_CALL_${entityTypeName}`) || main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_CALL');
	    }
	  }, {
	    key: "onButtonClick",
	    value: function onButtonClick(button, event) {
	      if (!this.isEnabled()) {
	        return;
	      }
	      const keys = Object.keys(this.data);
	      if (keys.length === 1) {
	        const firstKey = keys[0];
	        const items = this.data[firstKey];
	        if (items.length === 1) {
	          const parts = firstKey.split('_');
	          if (parts.length >= 2) {
	            _classPrivateMethodGet$2(this, _addCall, _addCall2).call(this, firstKey, items[0]);
	            return;
	          }
	        }
	      }
	      this.openMenu();
	    }
	  }, {
	    key: "prepareMenuItem",
	    value: function prepareMenuItem(key, value) {
	      let phoneText = value;
	      let phoneValue = value;
	      if (main_core.Type.isPlainObject(value)) {
	        const complexName = BX.prop.getString(value, 'COMPLEX_NAME', '');
	        const valueFormatted = BX.prop.getString(value, 'VALUE_FORMATTED', '');
	        phoneText = `${complexName}: ${valueFormatted}`;
	        phoneValue = BX.prop.getString(value, 'VALUE', '');
	        if (this.useClientSelector) {
	          return _classPrivateMethodGet$2(this, _createClientSelectorMenuItem, _createClientSelectorMenuItem2).call(this, value);
	        }
	      }
	      return _classPrivateMethodGet$2(this, _createPopupMenuItem, _createPopupMenuItem2).call(this, key, phoneValue, phoneText);
	    }
	  }, {
	    key: "onClientSelectorSelect",
	    value: function onClientSelectorSelect({
	      data: {
	        item
	      }
	    }) {
	      const {
	        customData
	      } = item;
	      const entityKey = `${customData.get('entityTypeId')}_${customData.get('entityId')}`;
	      const value = customData.get('value');
	      _classPrivateMethodGet$2(this, _addCall, _addCall2).call(this, entityKey, value);
	    }
	  }, {
	    key: "getMultifieldTypeName",
	    value: function getMultifieldTypeName() {
	      return 'PHONE';
	    }
	  }]);
	  return PhoneButton;
	}(CommunicationButton);
	function _createClientSelectorMenuItem2(value) {
	  const complexName = BX.prop.getString(value, 'COMPLEX_NAME', '');
	  const valueFormatted = BX.prop.getString(value, 'VALUE_FORMATTED', '');
	  const phoneValue = BX.prop.getString(value, 'VALUE', '');
	  const owner = main_core.Type.isObjectLike(value.OWNER) ? value.OWNER : null;
	  return {
	    id: value.ID,
	    title: owner ? owner.TITLE : valueFormatted,
	    subtitle: owner ? `${valueFormatted}, ${complexName}` : complexName,
	    avatar: null,
	    customData: {
	      entityId: owner ? owner.ID : null,
	      entityTypeId: owner ? owner.TYPE_ID : null,
	      value: phoneValue
	    }
	  };
	}
	function _createPopupMenuItem2(entityKey, value, text) {
	  return {
	    text,
	    onclick: () => {
	      _classPrivateMethodGet$2(this, _addCall, _addCall2).call(this, entityKey, value);
	    }
	  };
	}
	function _addCall2(entityKey, phone) {
	  if (main_core.Type.isNil(window.top.BXIM)) {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_TELEPHONY_NOT_SUPPORTED'), null, main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_TELEPHONY_NOT_SUPPORTED_OK'));
	    return;
	  }
	  const parts = entityKey.split('_');
	  if (parts.length < 2) {
	    return;
	  }
	  const entityTypeId = main_core.Text.toInteger(parts[0]);
	  const entityId = main_core.Text.toInteger(parts[1]);
	  const ownerTypeId = BX.prop.getInteger(this.ownerInfo, 'ENTITY_TYPE_ID', 0);
	  const ownerId = BX.prop.getInteger(this.ownerInfo, 'ENTITY_ID', 0);
	  const phoneValue = main_core.Type.isPlainObject(phone) ? phone.VALUE : phone;
	  const params = {
	    ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(entityTypeId),
	    ENTITY_ID: entityId,
	    AUTO_FOLD: true
	  };
	  if (ownerTypeId !== entityTypeId || ownerId !== entityId) {
	    params.BINDINGS = [{
	      OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(ownerTypeId),
	      OWNER_ID: ownerId
	    }];
	  }
	  window.top.BXIM.phoneTo(phoneValue, params);
	}

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _id = /*#__PURE__*/new WeakMap();
	var _binding = /*#__PURE__*/new WeakMap();
	let NavigationBar = /*#__PURE__*/function (_NavigationPanel) {
	  babelHelpers.inherits(NavigationBar, _NavigationPanel);
	  function NavigationBar(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, NavigationBar);
	    if (!main_core.Type.isPlainObject(options)) {
	      throw 'BX.Crm.NavigationBar: The "options" argument must be object.';
	    }
	    options.items = main_core.Type.isArray(options.items) ? options.items : [];
	    options.items.forEach(item => {
	      if (!item.hasOwnProperty('active') && item.hasOwnProperty('isActive')) {
	        item.active = item.isActive;
	      }
	      if (main_core.Type.isStringFilled(item.lockedCallback)) {
	        item.locked = true;
	        item.url = '';
	        item.events = {
	          click: () => eval(item.lockedCallback)
	        };
	      }
	      if (main_core.Type.isStringFilled(item.url)) {
	        item.events = {
	          click: () => _this.openUrl(item.id, item.url)
	        };
	      }
	    });
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(NavigationBar).call(this, {
	      target: BX(options.id),
	      items: options.items
	    }));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _binding, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id, options.id);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _binding, options.binding);
	    return _this;
	  }
	  babelHelpers.createClass(NavigationBar, [{
	    key: "openUrl",
	    value: function openUrl(itemId, url) {
	      if (!main_core.Type.isStringFilled(url)) {
	        return;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _binding) && main_core.Type.isPlainObject(babelHelpers.classPrivateFieldGet(this, _binding))) {
	        const category = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).category) ? babelHelpers.classPrivateFieldGet(this, _binding).category : '';
	        const name = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).name) ? babelHelpers.classPrivateFieldGet(this, _binding).name : '';
	        const key = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _binding).key) ? babelHelpers.classPrivateFieldGet(this, _binding).key : '';
	        if (category !== '' && name !== '' && key !== '') {
	          const value = itemId + ":" + BX.formatDate(new Date(), 'YYYYMMDD');
	          BX.userOptions.save(category, name, key, value, false);
	        }
	      }
	      setTimeout(function () {
	        window.location.href = url;
	      }, 150);
	    }
	  }]);
	  return NavigationBar;
	}(ui_navigationpanel.NavigationPanel);

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const MENU_ID_PREFIX = 'toolbar-category-';
	let instance = null;
	let ToolbarEvents = function ToolbarEvents() {
	  babelHelpers.classCallCheck(this, ToolbarEvents);
	};
	/**
	 * @memberOf BX.Crm
	 */
	babelHelpers.defineProperty(ToolbarEvents, "TYPE_UPDATED", 'TypeUpdated');
	babelHelpers.defineProperty(ToolbarEvents, "CATEGORIES_UPDATED", 'CategoriesUpdated');
	babelHelpers.defineProperty(ToolbarEvents, "AUTOMATED_SOLUTION_UPDATED", 'CategoriesUpdated');
	var _bindAutomationGuide = /*#__PURE__*/new WeakSet();
	var _reloadCategoriesMenu = /*#__PURE__*/new WeakSet();
	var _reloadAddButtonMenu = /*#__PURE__*/new WeakSet();
	var _tryRedirectToDefaultCategory = /*#__PURE__*/new WeakSet();
	let ToolbarComponent = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ToolbarComponent, _EventEmitter);
	  function ToolbarComponent() {
	    var _this;
	    babelHelpers.classCallCheck(this, ToolbarComponent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ToolbarComponent).call(this));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _tryRedirectToDefaultCategory);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _reloadAddButtonMenu);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _reloadCategoriesMenu);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _bindAutomationGuide);
	    _this.initHints();
	    _this.setEventNamespace('BX.Crm.ToolbarComponent');
	    main_core.Event.ready(_this.bindEvents.bind(babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }
	  babelHelpers.createClass(ToolbarComponent, [{
	    key: "initHints",
	    value: function initHints() {
	      BX.UI.Hint.init(BX('ui-toolbar-after-title-buttons'));
	      BX.UI.Hint.popupParameters = {
	        closeByEsc: true,
	        autoHide: true,
	        angle: {
	          offset: 60
	        },
	        offsetLeft: 40
	      };
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      const buttonNode = document.querySelector('[data-role="bx-crm-toolbar-categories-button"]');
	      if (buttonNode) {
	        const toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
	        const button = toolbar.getButton(main_core.Dom.attr(buttonNode, 'data-btn-uniqid'));
	        const entityTypeId = Number(buttonNode.dataset.entityTypeId);
	        if (button.counterNode && button.counterNode.innerText > 99) {
	          button.counterNode.innerText = '99+';
	        }
	        if (button && entityTypeId > 0) {
	          if (pull_client.PULL) {
	            pull_client.PULL.subscribe({
	              moduleId: 'crm',
	              command: 'CRM_CATEGORIES_UPDATED',
	              callback: data => {
	                if (data.entityTypeId !== entityTypeId) {
	                  return;
	                }
	                _classPrivateMethodGet$3(this, _reloadCategoriesMenu, _reloadCategoriesMenu2).call(this, button, entityTypeId, buttonNode.dataset.categoryId);
	              }
	            });
	            pull_client.PULL.extendWatch('CRM_CATEGORIES_UPDATED');
	          }
	          this.subscribeCategoriesUpdatedEvent(() => {
	            _classPrivateMethodGet$3(this, _reloadCategoriesMenu, _reloadCategoriesMenu2).call(this, button, entityTypeId, buttonNode.dataset.categoryId);
	          });
	        }
	      }
	      _classPrivateMethodGet$3(this, _bindAutomationGuide, _bindAutomationGuide2).call(this);
	    }
	  }, {
	    key: "emitTypeUpdatedEvent",
	    value: function emitTypeUpdatedEvent(data) {
	      this.emit(ToolbarEvents.TYPE_UPDATED, data);
	    }
	  }, {
	    key: "emitAutomatedSolutionUpdatedEvent",
	    value: function emitAutomatedSolutionUpdatedEvent(data) {
	      this.emit(ToolbarEvents.AUTOMATED_SOLUTION_UPDATED, data);
	    }
	  }, {
	    key: "emitCategoriesUpdatedEvent",
	    value: function emitCategoriesUpdatedEvent(data) {
	      this.emit(ToolbarEvents.CATEGORIES_UPDATED, data);
	    }
	  }, {
	    key: "subscribeTypeUpdatedEvent",
	    value: function subscribeTypeUpdatedEvent(callback) {
	      this.subscribe(ToolbarEvents.TYPE_UPDATED, callback);
	    }
	  }, {
	    key: "subscribeCategoriesUpdatedEvent",
	    value: function subscribeCategoriesUpdatedEvent(callback) {
	      this.subscribe(ToolbarEvents.CATEGORIES_UPDATED, callback);
	    }
	  }, {
	    key: "subscribeAutomatedSolutionUpdatedEvent",
	    value: function subscribeAutomatedSolutionUpdatedEvent(callback) {
	      this.subscribe(ToolbarEvents.AUTOMATED_SOLUTION_UPDATED, callback);
	    }
	  }, {
	    key: "unsubscribeAutomatedSolutionUpdatedEvent",
	    value: function unsubscribeAutomatedSolutionUpdatedEvent(callback) {
	      this.unsubscribe(ToolbarEvents.AUTOMATED_SOLUTION_UPDATED, callback);
	    }
	  }, {
	    key: "getSettingsButton",
	    value: function getSettingsButton() {
	      const toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
	      if (!toolbar) {
	        return null;
	      }
	      for (const [key, button] of Object.entries(toolbar.getButtons())) {
	        if (button.getIcon() === ui_buttons.ButtonIcon.SETTING) {
	          return button;
	        }
	      }
	      return null;
	    }
	  }], [{
	    key: "Instance",
	    get: function () {
	      if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.ToolbarComponent')) {
	        return window.top.BX.Crm.ToolbarComponent.Instance;
	      }
	      if (instance === null) {
	        instance = new ToolbarComponent();
	      }
	      return instance;
	    }
	  }]);
	  return ToolbarComponent;
	}(main_core_events.EventEmitter);
	function _bindAutomationGuide2() {
	  const hash = document.location.hash;
	  let guide = null;
	  if (hash === '#robots') {
	    const robotsBtn = document.querySelector('.crm-robot-btn');
	    if (robotsBtn) {
	      guide = new ui_tour.Guide({
	        steps: [{
	          target: robotsBtn,
	          title: main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_ROBOTS_GUIDE_TEXT_1'),
	          text: ''
	        }],
	        onEvents: true
	      });
	    }
	  } else if (hash === '#scripts') {
	    const scriptsBtn = document.querySelector('.intranet-binding-menu-btn');
	    if (scriptsBtn) {
	      guide = new ui_tour.Guide({
	        steps: [{
	          target: scriptsBtn,
	          title: main_core.Loc.getMessage('CRM_TOOLBAR_COMPONENT_SCRIPTS_GUIDE_TEXT'),
	          article: '13281632',
	          text: ''
	        }],
	        onEvents: true
	      });
	    }
	  }
	  if (guide) {
	    guide.start();
	    guide.getPopup().setAutoHide(true);
	    guide.getPopup().setClosingByEsc(true);
	  }
	}
	function _reloadCategoriesMenu2(button, entityTypeId, categoryId) {
	  const menu = button.getMenuWindow();
	  if (!menu) {
	    return;
	  }
	  main_core.ajax.runAction('crm.controller.category.list', {
	    data: {
	      entityTypeId
	    }
	  }).then(response => {
	    let startKey = 0;
	    const items = [];
	    const categories = response.data.categories;
	    menu.menuItems.forEach(item => {
	      if (item.id.indexOf(MENU_ID_PREFIX) !== 0) {
	        items.push(item.options);
	      } else if (item.id === 'toolbar-category-all') {
	        items.push(item.options);
	        startKey = 1;
	      }
	    });
	    menu.destroy();
	    main_core.Event.unbindAll(button.getContainer(), 'click');
	    categories.forEach(category => {
	      var _Router$Instance$getD, _Router$Instance$getI;
	      const link = entityTypeId === BX.CrmEntityType.enumeration.deal ? (_Router$Instance$getD = crm_router.Router.Instance.getDealKanbanUrl(category.id)) === null || _Router$Instance$getD === void 0 ? void 0 : _Router$Instance$getD.toString() : (_Router$Instance$getI = crm_router.Router.Instance.getItemListUrlInCurrentView(entityTypeId, category.id)) === null || _Router$Instance$getI === void 0 ? void 0 : _Router$Instance$getI.toString();
	      items.splice(startKey, 0, {
	        id: `${MENU_ID_PREFIX}${category.id}`,
	        text: main_core.Text.encode(category.name),
	        href: link || null,
	        dataset: {
	          isDefault: category.isDefault || false,
	          categoryId: category.id
	        }
	      });
	      if (category.id >= 0 && categoryId >= 0 && Number(categoryId) === Number(category.id)) {
	        button.setText(category.name);
	      }
	      startKey++;
	    });
	    const options = menu.params;
	    options.items = items;
	    button.menuWindow = new main_popup.Menu(options);
	    main_core.Event.bind(button.getContainer(), 'click', button.menuWindow.show.bind(button.menuWindow));
	    if (entityTypeId === BX.CrmEntityType.enumeration.deal) {
	      _classPrivateMethodGet$3(this, _reloadAddButtonMenu, _reloadAddButtonMenu2).call(this, categories);
	    }
	    _classPrivateMethodGet$3(this, _tryRedirectToDefaultCategory, _tryRedirectToDefaultCategory2).call(this, items, categoryId);
	  }).catch(response => {
	    console.log('error trying reload categories', response.errors);
	  });
	}
	function _reloadAddButtonMenu2(categories) {
	  const addButtonNode = document.querySelector('.ui-btn-split.ui-btn-success');
	  if (!addButtonNode) {
	    return;
	  }
	  const addButtonId = addButtonNode.dataset.btnUniqid;
	  const toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
	  const button = toolbar.getButton(addButtonId, 'data-btn-uniqid');
	  if (!button) {
	    return;
	  }
	  const menu = button.menuWindow;
	  if (!menu) {
	    return;
	  }
	  const menuItemsIds = menu.getMenuItems().map(item => item.id).filter(id => main_core.Type.isInteger(id));
	  const categoryIds = new Set(categories.map(item => item.id));
	  const idsToRemove = menuItemsIds.filter(id => !categoryIds.has(id));
	  const newCategories = categories.filter(item => !menuItemsIds.includes(item.id) && item.id > 0);

	  // remove menu item(s)
	  if (idsToRemove.length > 0) {
	    idsToRemove.forEach(idToRemove => menu.removeMenuItem(idToRemove));
	  }

	  // add new item(s)
	  if (newCategories.length > 0) {
	    const targetItemId = menu.getMenuItems().map(item => item.id).filter(id => main_core.Type.isString(id)).at(1);
	    newCategories.forEach(item => {
	      menu.addMenuItem({
	        id: item.id,
	        text: item.name,
	        onclick() {
	          BX.SidePanel.Instance.open(`/crm/deal/details/0/?category_id=${item.id}`);
	        }
	      }, targetItemId);
	    });
	  }
	}
	function _tryRedirectToDefaultCategory2(items, currentCategoryId) {
	  const tryToRedirect = () => {
	    const isStaticCategory = currentCategoryId <= 0;
	    const isCurrentCategoryDefined = items.some(row => {
	      var _row$dataset;
	      return Number(row === null || row === void 0 ? void 0 : (_row$dataset = row.dataset) === null || _row$dataset === void 0 ? void 0 : _row$dataset.categoryId) === Number(currentCategoryId);
	    });
	    if (!isCurrentCategoryDefined && !isStaticCategory) {
	      const defaultPage = items.find(row => {
	        var _row$dataset2;
	        return row === null || row === void 0 ? void 0 : (_row$dataset2 = row.dataset) === null || _row$dataset2 === void 0 ? void 0 : _row$dataset2.isDefault;
	      });
	      if (main_core.Type.isObject(defaultPage) && main_core.Type.isStringFilled(defaultPage.href)) {
	        window.location.href = defaultPage.href;
	      }
	    }
	  };

	  // wait until all sliders are closed before redirecting
	  if (BX.SidePanel.Instance.getTopSlider()) {
	    main_core_events.EventEmitter.subscribeOnce(BX.SidePanel.Instance.getTopSlider(), 'SidePanel.Slider:onCloseComplete', () => {
	      _classPrivateMethodGet$3(this, _tryRedirectToDefaultCategory, _tryRedirectToDefaultCategory2).call(this, items, currentCategoryId);
	    });
	    return;
	  }
	  tryToRedirect();
	}

	/**
	 * @memberOf BX.Crm.ToolbarComponent.Communications
	 */
	ToolbarComponent.Communications = Object.freeze({
	  PhoneButton,
	  EmailButton,
	  MessengerButton
	});

	exports.ToolbarComponent = ToolbarComponent;
	exports.NavigationBar = NavigationBar;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm,BX.UI.Dialogs,BX.UI,BX.Crm,BX,BX,BX.Event,BX.Main,BX.UI,BX.UI.Tour));
//# sourceMappingURL=toolbar-component.bundle.js.map

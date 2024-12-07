/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_autorun,ui_notification,main_popup,ui_entityCatalog,main_core,main_core_events) {
	'use strict';

	async function fetchTemplates(entityTypeId, entityCategoryId) {
	  const resp = await BX.ajax.runAction('crm.activity.sms.getTemplates', {
	    data: {
	      senderId: DEFAULT_PROVIDER,
	      context: {
	        module: 'crm',
	        entityTypeId,
	        entityCategoryId,
	        entityId: null
	      }
	    }
	  });
	  return resp.data.templates;
	}
	async function fetchSmsProvidersConfig() {
	  const resp = await main_core.ajax.runAction('crm.api.messagesender.providersConfig', {
	    data: {
	      providerName: DEFAULT_PROVIDER
	    }
	  });
	  return resp.data || [];
	}

	/**
	 * Currently only 'ednaru' provider is supported. To extend must implement select provider logic
	 */
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _currentFromNumberId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentFromNumberId");
	var _rawProviders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rawProviders");
	var _getEdnaProviderFromRaw = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEdnaProviderFromRaw");
	var _createProvidersMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createProvidersMenu");
	var _createFromNumberMenus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createFromNumberMenus");
	var _onFromNumbersChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFromNumbersChange");
	class SettingsCreator {
	  constructor(_currentFromNumber) {
	    Object.defineProperty(this, _onFromNumbersChange, {
	      value: _onFromNumbersChange2
	    });
	    Object.defineProperty(this, _createFromNumberMenus, {
	      value: _createFromNumberMenus2
	    });
	    Object.defineProperty(this, _createProvidersMenu, {
	      value: _createProvidersMenu2
	    });
	    Object.defineProperty(this, _getEdnaProviderFromRaw, {
	      value: _getEdnaProviderFromRaw2
	    });
	    Object.defineProperty(this, _instance, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _currentFromNumberId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _rawProviders, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _currentFromNumberId)[_currentFromNumberId] = _currentFromNumber;
	  }
	  async create() {
	    babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders] = await fetchSmsProvidersConfig();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _currentFromNumberId)[_currentFromNumberId] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _currentFromNumberId)[_currentFromNumberId] = babelHelpers.classPrivateFieldLooseBase(this, _getEdnaProviderFromRaw)[_getEdnaProviderFromRaw](babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders]).fromList[0].id;
	    }
	    const menuId = 'crm-whatsapp-channels-settings-menu';
	    babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = main_popup.MenuManager.create({
	      id: menuId,
	      bindElement: document.querySelector('.bx-crm-group-actions-messages-settings-icon'),
	      items: [{
	        delimiter: true,
	        text: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_SETTINGS')
	      }, {
	        id: 'channelSubmenu',
	        text: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_SENDER_SELECTOR'),
	        items: babelHelpers.classPrivateFieldLooseBase(this, _createProvidersMenu)[_createProvidersMenu](babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders])
	      }, {
	        id: 'phoneSubmenu',
	        text: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_NUMBER_SELECTOR'),
	        items: babelHelpers.classPrivateFieldLooseBase(this, _createFromNumberMenus)[_createFromNumberMenus](babelHelpers.classPrivateFieldLooseBase(this, _getEdnaProviderFromRaw)[_getEdnaProviderFromRaw](babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders]).fromList, babelHelpers.classPrivateFieldLooseBase(this, _currentFromNumberId)[_currentFromNumberId])
	      }]
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	}
	function _getEdnaProviderFromRaw2(rawProviders) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders].length !== 1 && rawProviders[0].id !== DEFAULT_PROVIDER) {
	    throw new Error(`Currently only ${DEFAULT_PROVIDER} is supported.`);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _rawProviders)[_rawProviders][0];
	}
	function _createProvidersMenu2(providers) {
	  const ednaProvider = babelHelpers.classPrivateFieldLooseBase(this, _getEdnaProviderFromRaw)[_getEdnaProviderFromRaw](providers);
	  return [{
	    id: ednaProvider.id,
	    title: ednaProvider.name,
	    text: ednaProvider.name,
	    disabled: true,
	    className: 'menu-popup-item-accept'
	  }];
	}
	function _createFromNumberMenus2(fromList, currentFromNumber) {
	  return fromList.map(fromNumber => {
	    const className = fromNumber.id === currentFromNumber ? 'menu-popup-item-accept' : 'menu-popup-item-none';
	    return {
	      id: fromNumber.id,
	      title: fromNumber.name,
	      text: fromNumber.name,
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _onFromNumbersChange)[_onFromNumbersChange].bind(this),
	      className
	    };
	  });
	}
	function _onFromNumbersChange2(event, fromMenu) {
	  const selectedChannelPhone = fromMenu.id;
	  BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected', {
	    phone: selectedChannelPhone
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance].close();
	}

	let editorInstance = null;
	function createOrUpdatePlaceholder(templateId, entityTypeId, entityCategoryId, params) {
	  const {
	    id,
	    value,
	    entityType,
	    text
	  } = params;
	  main_core.ajax.runAction('crm.activity.smsplaceholder.createOrUpdatePlaceholder', {
	    data: {
	      placeholderId: id,
	      fieldName: main_core.Type.isStringFilled(value) ? value : null,
	      entityType: main_core.Type.isStringFilled(entityType) ? entityType : null,
	      fieldValue: main_core.Type.isStringFilled(text) ? text : null,
	      templateId,
	      entityTypeId,
	      entityCategoryId
	    }
	  });
	}
	const SmsEditorWrapper = {
	  name: 'SmsEditorWrapper',
	  props: {
	    templateParam: Object,
	    title: String,
	    entityTypeId: Number,
	    categoryId: Number
	  },
	  data() {
	    return {
	      counter: 0,
	      editorInstance: null,
	      messages: {
	        send: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_SEND')
	      }
	    };
	  },
	  methods: {
	    onSend() {
	      if (!editorInstance) {
	        console.error('SmsEditorWrapper: editorInstance is null');
	        return;
	      }
	      let text = '';
	      if (editorInstance) {
	        const tplEditorData = editorInstance.getData();
	        if (main_core.Type.isPlainObject(tplEditorData)) {
	          text = tplEditorData.body;
	        }
	      }
	      if (text === '') {
	        text = this.templateParam.PREVIEW;
	      }
	      const templateId = this.templateParam.ID;
	      main_core_events.EventEmitter.emit('BX.Crm.SmsEditorWrapper:click', {
	        text,
	        templateId
	      });
	    }
	  },
	  mounted() {
	    const editorParams = {
	      target: this.$refs.editorContainerEl,
	      categoryId: this.categoryId,
	      entityId: 0,
	      entityTypeId: this.entityTypeId,
	      onSelect: params => {
	        // this callback is called when templates placeholder is changed
	        createOrUpdatePlaceholder(this.templateParam.ORIGINAL_ID, this.entityTypeId, this.categoryId, {
	          id: params.id,
	          value: params.value,
	          entityType: params.entityType,
	          text: params.text
	        });
	      }
	    };
	    const preview = this.templateParam.PREVIEW;
	    const placeholders = this.templateParam.PLACEHOLDERS || {};
	    const filledPlaceholders = this.templateParam.FILLED_PLACEHOLDERS || [];
	    editorInstance = new BX.Crm.Template.Editor(editorParams).setPlaceholders(placeholders).setFilledPlaceholders(filledPlaceholders);
	    editorInstance.setBody(preview);
	  },
	  unmounted() {
	    editorInstance = null;
	  },
	  template: `
		<div class="bx-crm-group-actions-messages__item">
			<div class="bx-crm-group-actions-messages__item-title">{{ title }}</div>
			<div class="bx-crm-group-actions-messages__editor" ref="editorContainerEl"></div>

			<button
				@click="onSend"
				class="ui-btn ui-btn-primary ui-btn-md bx-crm-group-actions-messages__button"
			>{{ messages.send }}</button>
		</div>
	`
	};

	var _messages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("messages");
	var _itemSlot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("itemSlot");
	var _getTemplateItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTemplateItems");
	var _catalogHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("catalogHeader");
	var _catalogFooter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("catalogFooter");
	class TemplateCatalogCreator {
	  constructor() {
	    Object.defineProperty(this, _catalogFooter, {
	      value: _catalogFooter2
	    });
	    Object.defineProperty(this, _catalogHeader, {
	      value: _catalogHeader2
	    });
	    Object.defineProperty(this, _getTemplateItems, {
	      value: _getTemplateItems2
	    });
	    Object.defineProperty(this, _itemSlot, {
	      value: _itemSlot2
	    });
	    Object.defineProperty(this, _messages, {
	      writable: true,
	      value: {
	        startConversation: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_START'),
	        sendFirst: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_FIRST'),
	        howItWork: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_HOW'),
	        learnCompliance: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_COMPLIANCE'),
	        learnMore: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_MORE')
	      }
	    });
	  }
	  async create(entityTypeId, categoryId) {
	    const rawTemplates = await fetchTemplates(entityTypeId, categoryId);
	    const itemsData = babelHelpers.classPrivateFieldLooseBase(this, _getTemplateItems)[_getTemplateItems](entityTypeId, categoryId, rawTemplates);
	    const itemSlot = babelHelpers.classPrivateFieldLooseBase(this, _itemSlot)[_itemSlot]();
	    return new ui_entityCatalog.EntityCatalog({
	      canDeselectGroups: false,
	      showEmptyGroups: false,
	      customComponents: {
	        SmsEditorWrapper
	      },
	      slots: {
	        [ui_entityCatalog.EntityCatalog.SLOT_MAIN_CONTENT_ITEM]: itemSlot,
	        [ui_entityCatalog.EntityCatalog.SLOT_MAIN_CONTENT_HEADER]: babelHelpers.classPrivateFieldLooseBase(this, _catalogHeader)[_catalogHeader](),
	        [ui_entityCatalog.EntityCatalog.SLOT_MAIN_CONTENT_FOOTER]: babelHelpers.classPrivateFieldLooseBase(this, _catalogFooter)[_catalogFooter]()
	      },
	      groups: itemsData.groups,
	      items: itemsData.templateItems,
	      title: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_POPUP_TITLE'),
	      popupOptions: {
	        overlay: true
	      }
	    });
	  }
	}
	function _itemSlot2() {
	  return `
			<div>
				<SmsEditorWrapper  
					:templateParam="itemSlotProps.itemData.customData"
					:title="itemSlotProps.itemData.title" 
					:entityTypeId="itemSlotProps.itemData.entityTypeId"
					:categoryId="itemSlotProps.itemData.categoryId"
				/>
			</div>
		`;
	}
	function _getTemplateItems2(entityTypeId, categoryId, templates) {
	  const groups = templates.map(template => {
	    return {
	      id: template.ID,
	      name: template.TITLE
	    };
	  });
	  if (groups.length > 0) {
	    groups[0].selected = true;
	  }
	  const templateItems = templates.map(template => {
	    return {
	      id: template.ORIGINAL_ID,
	      title: template.TITLE,
	      entityTypeId,
	      categoryId,
	      groupIds: [template.ID],
	      customData: {
	        title: template.TITLE,
	        FILLED_PLACEHOLDERS: template.FILLED_PLACEHOLDERS || [],
	        ...template
	      }
	    };
	  });
	  return {
	    groups,
	    templateItems
	  };
	}
	function _catalogHeader2() {
	  return `
			<div class="bx-crm-group-actions-messages-tpl-header">
				<div class="bx-crm-group-actions-messages-tpl-header-left">
					<div class="bx-crm-group-actions-messages-whatsapp-icon"></div>
				</div>
				<div class="bx-crm-group-actions-messages-tpl-header-center">
					<strong 
						class="bx-crm-group-actions-messages-tpl-header-center-title"
					>${babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].startConversation}</strong><br>
					<span class="bx-crm-group-actions-messages-tpl-header_gray">${babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].sendFirst}</span><br>
					<a 
							href="#" 
							onclick="BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.Settings:help', { code: 20526810});"
						>${babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].howItWork}</a>
				</div>
				<div class="bx-crm-group-actions-messages-tpl-header-right">
					<div 
						class="bx-crm-group-actions-messages-settings-icon"
						onclick="BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.Settings:click');"
					></div>
				</div>
			</div>
		`;
	}
	function _catalogFooter2() {
	  return `
			<div class="bx-crm-group-actions-messages-compliance">
				${babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].learnCompliance}
			</div>
		`;
	}

	const DEFAULT_PROVIDER = 'ednaru';
	const SELECTED_FROM_NUMBER_LOCALSTORE_KEY = 'bx.crm.group_actions.messages.selected_from_number';
	var _instance$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _progressBarRepo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("progressBarRepo");
	var _catalog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("catalog");
	var _settingsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsMenu");
	var _selectedFromNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedFromNumber");
	var _messages$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("messages");
	var _showSettingsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsMenu");
	var _showHelpArticle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showHelpArticle");
	var _destroy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroy");
	var _fromPhoneSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fromPhoneSelected");
	var _sendMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendMessages");
	var _showAnotherProcessRunningNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAnotherProcessRunningNotification");
	var _showGridLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showGridLoader");
	var _hideGridLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideGridLoader");
	var _getGridLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getGridLoader");
	var _restoreLastSelectedFromNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("restoreLastSelectedFromNumber");
	var _storeLastSelectedFromNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("storeLastSelectedFromNumber");
	class Messages {
	  static getInstance(progressBarRepo, options) {
	    if (babelHelpers.classPrivateFieldLooseBase(Messages, _instance$1)[_instance$1]) {
	      babelHelpers.classPrivateFieldLooseBase(Messages, _instance$1)[_instance$1].setOptions(options);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(Messages, _instance$1)[_instance$1] = new Messages(progressBarRepo, options);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(Messages, _instance$1)[_instance$1];
	  }
	  constructor(progressBarRepo, options) {
	    Object.defineProperty(this, _storeLastSelectedFromNumber, {
	      value: _storeLastSelectedFromNumber2
	    });
	    Object.defineProperty(this, _restoreLastSelectedFromNumber, {
	      value: _restoreLastSelectedFromNumber2
	    });
	    Object.defineProperty(this, _getGridLoader, {
	      value: _getGridLoader2
	    });
	    Object.defineProperty(this, _hideGridLoader, {
	      value: _hideGridLoader2
	    });
	    Object.defineProperty(this, _showGridLoader, {
	      value: _showGridLoader2
	    });
	    Object.defineProperty(this, _showAnotherProcessRunningNotification, {
	      value: _showAnotherProcessRunningNotification2
	    });
	    Object.defineProperty(this, _sendMessages, {
	      value: _sendMessages2
	    });
	    Object.defineProperty(this, _fromPhoneSelected, {
	      value: _fromPhoneSelected2
	    });
	    Object.defineProperty(this, _destroy, {
	      value: _destroy2
	    });
	    Object.defineProperty(this, _showHelpArticle, {
	      value: _showHelpArticle2
	    });
	    Object.defineProperty(this, _showSettingsMenu, {
	      value: _showSettingsMenu2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _progressBarRepo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _catalog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsMenu, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _selectedFromNumber, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _messages$1, {
	      writable: true,
	      value: {
	        inProgress: main_core.Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_IN_PROGRESS')
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo)[_progressBarRepo] = progressBarRepo;
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedFromNumber)[_selectedFromNumber] = babelHelpers.classPrivateFieldLooseBase(this, _restoreLastSelectedFromNumber)[_restoreLastSelectedFromNumber]();
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  async execute() {
	    main_core_events.EventEmitter.subscribeOnce('BX.Crm.SmsEditorWrapper:click', babelHelpers.classPrivateFieldLooseBase(this, _sendMessages)[_sendMessages].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected', babelHelpers.classPrivateFieldLooseBase(this, _fromPhoneSelected)[_fromPhoneSelected].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _showGridLoader)[_showGridLoader]();
	    babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog] = await new TemplateCatalogCreator().create(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].entityTypeId, babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].categoryId);
	    babelHelpers.classPrivateFieldLooseBase(this, _hideGridLoader)[_hideGridLoader]();
	    babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog].show();
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog].getPopup();
	    popup.subscribeOnce('onClose', babelHelpers.classPrivateFieldLooseBase(this, _destroy)[_destroy].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Crm.GroupActionsWhatsApp.Settings:click', babelHelpers.classPrivateFieldLooseBase(this, _showSettingsMenu)[_showSettingsMenu].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Crm.GroupActionsWhatsApp.Settings:help', babelHelpers.classPrivateFieldLooseBase(this, _showHelpArticle)[_showHelpArticle].bind(this));
	  }
	}
	async function _showSettingsMenu2(event) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu] !== null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu].close();
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu] = null;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu] = await new SettingsCreator(babelHelpers.classPrivateFieldLooseBase(this, _selectedFromNumber)[_selectedFromNumber]).create();
	  babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu].show();
	}
	function _showHelpArticle2(event) {
	  const articleCode = event.getData().code;
	  if (!articleCode) {
	    throw new Error('articleCode is not defined');
	  }
	  const Helper = main_core.Reflection.getClass('top.BX.Helper');
	  if (Helper) {
	    Helper.show(`redirect=detail&code=${articleCode}`);
	  }
	}
	function _destroy2() {
	  main_core_events.EventEmitter.unsubscribeAll('BX.Crm.SmsEditorWrapper:click');
	  main_core_events.EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.Settings:click');
	  main_core_events.EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.Settings:help');
	  main_core_events.EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog].getPopup().unsubscribeAll('onClose');
	    babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog].close();
	    babelHelpers.classPrivateFieldLooseBase(this, _catalog)[_catalog] = null;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu].close();
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsMenu)[_settingsMenu] = null;
	  }
	}
	function _fromPhoneSelected2(event) {
	  const fromNumber = event.getData().phone;
	  babelHelpers.classPrivateFieldLooseBase(this, _storeLastSelectedFromNumber)[_storeLastSelectedFromNumber](fromNumber);
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedFromNumber)[_selectedFromNumber] = fromNumber;
	}
	async function _sendMessages2(event) {
	  var _event$getData, _event$getData2;
	  const gridId = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].gridId;
	  const entityTypeId = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].entityTypeId;
	  const messageBody = ((_event$getData = event.getData()) == null ? void 0 : _event$getData.text) || '';
	  const messageTemplate = ((_event$getData2 = event.getData()) == null ? void 0 : _event$getData2.templateId) || null;
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _progressBarRepo)[_progressBarRepo].getOrCreateProgressBarContainer('whatsapp-message').id;
	  const settings = {
	    gridId,
	    entityTypeId,
	    container
	  };
	  const bwmManager = crm_autorun.BatchWhatsappMessageManager.getInstance(gridId, settings);
	  if (bwmManager.isRunning()) {
	    return;
	  }
	  if (crm_autorun.ProcessRegistry.isProcessRunning(gridId)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showAnotherProcessRunningNotification)[_showAnotherProcessRunningNotification]();
	    return;
	  }
	  bwmManager.setTemplateParams({
	    messageBody,
	    messageTemplate,
	    fromPhone: babelHelpers.classPrivateFieldLooseBase(this, _selectedFromNumber)[_selectedFromNumber]
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].forAll) {
	    bwmManager.resetEntityIds();
	  } else {
	    bwmManager.setEntityIds(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].selectedIds);
	  }
	  bwmManager.execute();
	  babelHelpers.classPrivateFieldLooseBase(this, _destroy)[_destroy]();
	}
	function _showAnotherProcessRunningNotification2() {
	  ui_notification.UI.Notification.Center.notify({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _messages$1)[_messages$1].inProgress,
	    autoHide: true,
	    autoHideDelay: 5000
	  });
	}
	function _showGridLoader2() {
	  const gridLoader = babelHelpers.classPrivateFieldLooseBase(this, _getGridLoader)[_getGridLoader]();
	  if (gridLoader) {
	    gridLoader.show();
	  }
	}
	function _hideGridLoader2() {
	  const gridLoader = babelHelpers.classPrivateFieldLooseBase(this, _getGridLoader)[_getGridLoader]();
	  if (gridLoader) {
	    gridLoader.hide();
	  }
	}
	function _getGridLoader2() {
	  var _BX$Main$gridManager$, _BX$Main$gridManager$2;
	  return (_BX$Main$gridManager$ = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].gridId)) == null ? void 0 : (_BX$Main$gridManager$2 = _BX$Main$gridManager$.instance) == null ? void 0 : _BX$Main$gridManager$2.getLoader();
	}
	function _restoreLastSelectedFromNumber2() {
	  return localStorage.getItem(SELECTED_FROM_NUMBER_LOCALSTORE_KEY) || null;
	}
	function _storeLastSelectedFromNumber2(fromNumber) {
	  localStorage.setItem(SELECTED_FROM_NUMBER_LOCALSTORE_KEY, fromNumber);
	}
	Object.defineProperty(Messages, _instance$1, {
	  writable: true,
	  value: null
	});

	exports.DEFAULT_PROVIDER = DEFAULT_PROVIDER;
	exports.Messages = Messages;

}((this.BX.Crm.GroupActions = this.BX.Crm.GroupActions || {}),BX.Crm.Autorun,BX,BX.Main,BX.UI,BX,BX.Event));
//# sourceMappingURL=messages.bundle.js.map

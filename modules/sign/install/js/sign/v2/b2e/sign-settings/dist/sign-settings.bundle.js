/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_api,sign_v2_b2e_companySelector,sign_v2_b2e_documentSend,sign_v2_b2e_documentSetup,sign_v2_b2e_parties,sign_v2_b2e_userParty,sign_v2_documentSetup,sign_v2_editor,sign_v2_helper,sign_v2_signSettings) {
	'use strict';

	var _companyParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyParty");
	var _userParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userParty");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _prepareConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareConfig");
	var _setupParties = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setupParties");
	var _syncMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("syncMembers");
	var _sleep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sleep");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _isDocumentInitiatedByEmployee = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDocumentInitiatedByEmployee");
	var _getAssignee = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAssignee");
	var _getSigner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSigner");
	var _makeSetupMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeSetupMembers");
	var _parseMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("parseMembers");
	var _getSetupStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSetupStep");
	var _getCompanyStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyStep");
	var _getEmployeeStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmployeeStep");
	var _executeDocumentSendActions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeDocumentSendActions");
	var _executeEditorActions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeEditorActions");
	var _getSendStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSendStep");
	var _setSecondPartySectionVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSecondPartySectionVisibility");
	var _decorateStepsBeforeCompletionWithAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("decorateStepsBeforeCompletionWithAnalytics");
	var _sendAnalyticsOnSetupStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSetupStep");
	var _sendAnalyticsOnSetupError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSetupError");
	var _sendAnalyticsOnCompanyStepSuccess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnCompanyStepSuccess");
	var _sendAnalyticsOnCompanyStepError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnCompanyStepError");
	var _sendAnalyticsOnSendStepSuccess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSendStepSuccess");
	var _sendAnalyticsOnSendStepError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSendStepError");
	var _sendAnalyticsOnStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnStart");
	class B2ESignSettings extends sign_v2_signSettings.SignSettings {
	  constructor(containerId, _signOptions) {
	    super(containerId, _signOptions, {
	      next: {
	        className: 'ui-btn-success'
	      },
	      complete: {
	        className: 'ui-btn-success'
	      },
	      swapButtons: true
	    });
	    Object.defineProperty(this, _sendAnalyticsOnStart, {
	      value: _sendAnalyticsOnStart2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnSendStepError, {
	      value: _sendAnalyticsOnSendStepError2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnSendStepSuccess, {
	      value: _sendAnalyticsOnSendStepSuccess2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnCompanyStepError, {
	      value: _sendAnalyticsOnCompanyStepError2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnCompanyStepSuccess, {
	      value: _sendAnalyticsOnCompanyStepSuccess2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnSetupError, {
	      value: _sendAnalyticsOnSetupError2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnSetupStep, {
	      value: _sendAnalyticsOnSetupStep2
	    });
	    Object.defineProperty(this, _decorateStepsBeforeCompletionWithAnalytics, {
	      value: _decorateStepsBeforeCompletionWithAnalytics2
	    });
	    Object.defineProperty(this, _setSecondPartySectionVisibility, {
	      value: _setSecondPartySectionVisibility2
	    });
	    Object.defineProperty(this, _getSendStep, {
	      value: _getSendStep2
	    });
	    Object.defineProperty(this, _executeEditorActions, {
	      value: _executeEditorActions2
	    });
	    Object.defineProperty(this, _executeDocumentSendActions, {
	      value: _executeDocumentSendActions2
	    });
	    Object.defineProperty(this, _getEmployeeStep, {
	      value: _getEmployeeStep2
	    });
	    Object.defineProperty(this, _getCompanyStep, {
	      value: _getCompanyStep2
	    });
	    Object.defineProperty(this, _getSetupStep, {
	      value: _getSetupStep2
	    });
	    Object.defineProperty(this, _parseMembers, {
	      value: _parseMembers2
	    });
	    Object.defineProperty(this, _makeSetupMembers, {
	      value: _makeSetupMembers2
	    });
	    Object.defineProperty(this, _getSigner, {
	      value: _getSigner2
	    });
	    Object.defineProperty(this, _getAssignee, {
	      value: _getAssignee2
	    });
	    Object.defineProperty(this, _isDocumentInitiatedByEmployee, {
	      get: _get_isDocumentInitiatedByEmployee,
	      set: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      get: _get_documentUid,
	      set: void 0
	    });
	    Object.defineProperty(this, _sleep, {
	      value: _sleep2
	    });
	    Object.defineProperty(this, _syncMembers, {
	      value: _syncMembers2
	    });
	    Object.defineProperty(this, _setupParties, {
	      value: _setupParties2
	    });
	    Object.defineProperty(this, _prepareConfig, {
	      value: _prepareConfig2
	    });
	    Object.defineProperty(this, _companyParty, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userParty, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    const {
	      b2eFeatureConfig,
	      blankSelectorConfig: _blankSelectorConfig,
	      documentSendConfig: _documentSendConfig,
	      userPartyConfig
	    } = babelHelpers.classPrivateFieldLooseBase(this, _prepareConfig)[_prepareConfig](_signOptions);
	    _blankSelectorConfig.hideValidationParty = sign_v2_signSettings.isTemplateMode(this.documentMode);
	    this.documentSetup = new sign_v2_b2e_documentSetup.DocumentSetup(_blankSelectorConfig);
	    this.documentSend = new sign_v2_b2e_documentSend.DocumentSend(_documentSendConfig);
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty] = new sign_v2_b2e_parties.Parties({
	      ..._blankSelectorConfig,
	      documentInitiatedType: _signOptions.initiatedByType,
	      documentMode: _signOptions.documentMode
	    }, b2eFeatureConfig.hcmLinkAvailable);
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty] = new sign_v2_b2e_userParty.UserParty({
	      mode: 'edit',
	      ...userPartyConfig
	    });
	    this.subscribeOnEvents();
	  }
	  subscribeOnEvents() {
	    super.subscribeOnEvents();
	    this.documentSend.subscribe('changeTitle', ({
	      data
	    }) => {
	      this.documentSetup.setDocumentTitle(data.title);
	    });
	    this.documentSend.subscribe('disableBack', () => {
	      this.wizard.toggleBtnActiveState('back', true);
	    });
	    this.documentSend.subscribe('enableBack', () => {
	      this.wizard.toggleBtnActiveState('back', false);
	    });
	    this.documentSend.subscribe('enableComplete', () => {
	      this.wizard.toggleBtnActiveState('complete', false);
	    });
	    this.documentSend.subscribe('disableComplete', () => {
	      this.wizard.toggleBtnActiveState('complete', true);
	    });
	    this.documentSend.subscribe(this.documentSend.events.onTemplateComplete, event => {
	      if (this.isTemplateMode() && !this.isEditMode()) {
	        const templateId = event.getData().templateId;
	        this.getAnalytics().send({
	          event: 'turn_on_off_template',
	          type: 'auto',
	          c_element: 'on',
	          p5: `templateId_${templateId}`
	        });
	        this.getAnalytics().send({
	          event: 'click_save_template',
	          c_element: 'create_button',
	          p5: `templateId_${templateId}`,
	          status: 'success'
	        });
	      }
	    });
	  }
	  async applyDocumentData(uid) {
	    const setupData = await this.setupDocument(uid, true);
	    if (!setupData) {
	      return false;
	    }
	    const {
	      entityId,
	      representativeId,
	      companyUid,
	      hcmLinkCompanyId
	    } = setupData;
	    this.documentSend.documentData = setupData;
	    this.editor.documentData = setupData;
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setEntityId(entityId);
	    if (companyUid) {
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setLastSavedIntegrationId(hcmLinkCompanyId);
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadCompany(companyUid);
	    }
	    if (representativeId) {
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadRepresentative(representativeId);
	    }
	    const members = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadMembers(uid);
	    const parsedMembers = babelHelpers.classPrivateFieldLooseBase(this, _parseMembers)[_parseMembers](members);
	    const {
	      signers = [],
	      reviewers = [],
	      editors = []
	    } = parsedMembers;
	    if (signers.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].load(signers);
	    }
	    if (reviewers.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadValidator(reviewers[0], sign_v2_api.MemberRole.reviewer);
	    }
	    if (editors.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadValidator(editors[0], sign_v2_api.MemberRole.editor);
	    }
	    return true;
	  }
	  async applyTemplateData(templateUid) {
	    super.applyTemplateData(templateUid);
	    this.documentSetup.setupData.templateUid = templateUid;
	    this.documentSend.setExistingTemplate();
	    return true;
	  }
	  getStepsMetadata(signSettings, documentUid, templateUid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnStart)[_sendAnalyticsOnStart](documentUid);
	    const steps = {
	      setup: babelHelpers.classPrivateFieldLooseBase(this, _getSetupStep)[_getSetupStep](signSettings, documentUid),
	      company: babelHelpers.classPrivateFieldLooseBase(this, _getCompanyStep)[_getCompanyStep](signSettings)
	    };
	    if (this.isDocumentMode()) {
	      steps.employees = babelHelpers.classPrivateFieldLooseBase(this, _getEmployeeStep)[_getEmployeeStep](signSettings);
	    }
	    steps.send = babelHelpers.classPrivateFieldLooseBase(this, _getSendStep)[_getSendStep](signSettings);
	    babelHelpers.classPrivateFieldLooseBase(this, _decorateStepsBeforeCompletionWithAnalytics)[_decorateStepsBeforeCompletionWithAnalytics](steps, documentUid);
	    return steps;
	  }
	  onComplete() {
	    if (this.isTemplateMode()) {
	      return;
	    }
	    super.onComplete();
	  }
	  isTemplateCreateMode() {
	    return sign_v2_signSettings.isTemplateMode(this.documentMode) && !this.isEditMode();
	  }
	}
	function _prepareConfig2(signOptions) {
	  const {
	    config,
	    documentMode
	  } = signOptions;
	  const {
	    blankSelectorConfig,
	    documentSendConfig
	  } = config;
	  blankSelectorConfig.documentMode = documentMode;
	  documentSendConfig.documentMode = documentMode;
	  return config;
	}
	async function _setupParties2() {
	  const uid = babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid];
	  const {
	    representative
	  } = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  const {
	    members,
	    signerParty
	  } = babelHelpers.classPrivateFieldLooseBase(this, _makeSetupMembers)[_makeSetupMembers]();
	  await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].setupB2eParties(uid, representative.entityId, members);
	  const membersData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadMembers(uid);
	  if (!main_core.Type.isArrayFilled(membersData)) {
	    throw new Error('Members are empty');
	  }
	  await babelHelpers.classPrivateFieldLooseBase(this, _syncMembers)[_syncMembers](uid, signerParty);
	  return membersData.map(memberData => {
	    var _memberData$entityTyp, _memberData$entityId, _memberData$role;
	    return {
	      presetId: memberData == null ? void 0 : memberData.presetId,
	      part: memberData == null ? void 0 : memberData.party,
	      uid: memberData == null ? void 0 : memberData.uid,
	      entityTypeId: (_memberData$entityTyp = memberData == null ? void 0 : memberData.entityTypeId) != null ? _memberData$entityTyp : null,
	      entityId: (_memberData$entityId = memberData == null ? void 0 : memberData.entityId) != null ? _memberData$entityId : null,
	      role: (_memberData$role = memberData == null ? void 0 : memberData.role) != null ? _memberData$role : null
	    };
	  });
	}
	async function _syncMembers2(uid, signerParty) {
	  let syncFinished = false;
	  while (!syncFinished) {
	    // eslint-disable-next-line no-await-in-loop
	    const response = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].syncB2eMembersWithDepartments(uid, signerParty);
	    syncFinished = response.syncFinished;
	    // eslint-disable-next-line no-await-in-loop
	    await babelHelpers.classPrivateFieldLooseBase(this, _sleep)[_sleep](1000);
	  }
	}
	function _sleep2(ms) {
	  return new Promise(resolve => {
	    setTimeout(resolve, ms);
	  });
	}
	function _get_documentUid() {
	  return this.documentSetup.setupData.uid;
	}
	function _get_isDocumentInitiatedByEmployee() {
	  return this.documentSetup.setupData.initiatedByType === sign_v2_documentSetup.DocumentInitiated.employee;
	}
	function _getAssignee2(currentParty, companyId) {
	  return {
	    entityType: 'company',
	    entityId: companyId,
	    party: currentParty,
	    role: sign_v2_api.MemberRole.assignee
	  };
	}
	function _getSigner2(currentParty, entity) {
	  return {
	    entityType: entity.entityType,
	    entityId: entity.entityId,
	    party: currentParty,
	    role: sign_v2_api.MemberRole.signer
	  };
	}
	function _makeSetupMembers2() {
	  const {
	    company,
	    validation
	  } = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  const userPartyEntities = babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getEntities();
	  let currentParty = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee] ? 2 : 1;
	  const members = validation.map(item => {
	    const result = {
	      ...item,
	      party: currentParty
	    };
	    currentParty++;
	    return result;
	  });
	  members.push(babelHelpers.classPrivateFieldLooseBase(this, _getAssignee)[_getAssignee](currentParty, company.entityId));
	  let signerParty = currentParty;
	  if (this.isDocumentMode()) {
	    signerParty = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee] ? 1 : currentParty + 1;
	    const signers = userPartyEntities.map(entity => babelHelpers.classPrivateFieldLooseBase(this, _getSigner)[_getSigner](signerParty, entity));
	    members.push(...signers);
	  }
	  return {
	    members,
	    signerParty
	  };
	}
	function _parseMembers2(loadedMembers) {
	  return loadedMembers.reduce((acc, member) => {
	    var _acc$role;
	    const {
	      entityType,
	      entityId
	    } = member;
	    if (entityType !== 'user') {
	      return acc;
	    }
	    const role = `${member.role}s`;
	    return {
	      ...acc,
	      [role]: [...((_acc$role = acc[role]) != null ? _acc$role : []), entityId]
	    };
	  }, {});
	}
	function _getSetupStep2(signSettings, documentUid) {
	  return {
	    get content() {
	      const layout = signSettings.documentSetup.layout;
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
	    beforeCompletion: async () => {
	      const isValid = this.documentSetup.validate();
	      if (!isValid) {
	        return false;
	      }
	      const setupDataPromise = this.setupDocument();
	      if (!setupDataPromise) {
	        return false;
	      }
	      const setupData = await setupDataPromise;
	      if (!setupData) {
	        return false;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setInitiatedByType(setupData.initiatedByType);
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setEntityId(setupData.entityId);
	      return true;
	    }
	  };
	}
	function _getCompanyStep2(signSettings) {
	  const titleLocCode = this.isTemplateMode() ? 'SIGN_SETTINGS_B2E_ROUTES' : 'SIGN_SETTINGS_B2E_COMPANY';
	  return {
	    get content() {
	      const layout = babelHelpers.classPrivateFieldLooseBase(signSettings, _companyParty)[_companyParty].getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage(titleLocCode),
	    beforeCompletion: async () => {
	      const {
	        uid
	      } = this.documentSetup.setupData;
	      try {
	        await babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].save(uid);
	        this.documentSetup.setupData.integrationId = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getSelectedIntegrationId();
	        babelHelpers.classPrivateFieldLooseBase(this, _setSecondPartySectionVisibility)[_setSecondPartySectionVisibility]();
	        if (this.isTemplateMode()) {
	          this.editor.entityData = await babelHelpers.classPrivateFieldLooseBase(this, _setupParties)[_setupParties]();
	          const {
	            title,
	            isTemplate,
	            entityId,
	            externalId,
	            templateUid
	          } = this.documentSetup.setupData;
	          const blocks = await this.documentSetup.loadBlocks(uid);
	          babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions]({
	            uid,
	            title,
	            blocks,
	            externalId,
	            templateUid
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _executeEditorActions)[_executeEditorActions]({
	            isTemplate,
	            uid,
	            blocks,
	            entityId
	          });
	        }
	      } catch {
	        return false;
	      }
	      return true;
	    }
	  };
	}
	function _getEmployeeStep2(signSettings) {
	  return {
	    get content() {
	      const layout = babelHelpers.classPrivateFieldLooseBase(signSettings, _userParty)[_userParty].getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_B2E_EMPLOYEES'),
	    beforeCompletion: async () => {
	      try {
	        const isValid = babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].validate();
	        if (!isValid) {
	          return isValid;
	        }
	        this.editor.entityData = await babelHelpers.classPrivateFieldLooseBase(this, _setupParties)[_setupParties]();
	        const {
	          uid,
	          title,
	          isTemplate,
	          externalId,
	          entityId,
	          integrationId
	        } = this.documentSetup.setupData;
	        const blocks = await this.documentSetup.loadBlocks(uid);
	        babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions]({
	          uid,
	          title,
	          blocks,
	          externalId,
	          integrationId
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _executeEditorActions)[_executeEditorActions]({
	          isTemplate,
	          uid,
	          blocks,
	          entityId
	        });
	        return true;
	      } catch (e) {
	        console.error(e);
	        return false;
	      }
	    }
	  };
	}
	async function _executeDocumentSendActions2(documentSendData) {
	  const partiesData = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  Object.assign(partiesData, {
	    employees: babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getEntities().map(entity => {
	      return {
	        entityType: entity.entityType,
	        entityId: entity.entityId
	      };
	    })
	  });
	  this.documentSend.documentData = documentSendData;
	  this.documentSend.resetUserPartyPopup();
	  this.documentSend.setPartiesData(partiesData);
	}
	async function _executeEditorActions2(editorData) {
	  this.editor.documentData = editorData;
	  await this.editor.waitForPagesUrls();
	  await this.editor.renderDocument();
	  this.wizard.toggleBtnLoadingState('next', false);
	  await this.editor.show();
	}
	function _getSendStep2(signSettings) {
	  const titleLocCode = this.isTemplateMode() ? 'SIGN_SETTINGS_SEND_DOCUMENT_CREATE' : 'SIGN_SETTINGS_SEND_DOCUMENT';
	  return {
	    get content() {
	      const layout = signSettings.documentSend.getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage(titleLocCode),
	    beforeCompletion: () => this.documentSend.sendForSign()
	  };
	}
	function _setSecondPartySectionVisibility2() {
	  const selectedProvider = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getSelectedProvider();
	  const isNotSesRuProvider = selectedProvider.code !== sign_v2_b2e_companySelector.ProviderCode.sesRu;
	  const isInitiatedByEmployee = this.documentSetup.setupData.initiatedByType === sign_v2_documentSetup.DocumentInitiated.employee;
	  const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === sign_v2_documentSetup.DocumentInitiated.company;
	  const isSecondPartySectionVisible = isNotSesRuProvider || sign_v2_signSettings.isTemplateMode(this.documentMode) && isInitiatedByEmployee;
	  const isHcmLinkIntegrationSectionVisible = this.documentSetup.setupData.integrationId > 0 || isInitiatedByCompany;
	  this.editor.setSectionVisibilityByType(sign_v2_editor.SectionType.HcmLinkIntegration, isHcmLinkIntegrationSectionVisible);
	  this.editor.setSectionVisibilityByType(sign_v2_editor.SectionType.SecondParty, isSecondPartySectionVisible);
	}
	function _decorateStepsBeforeCompletionWithAnalytics2(steps, documentUid) {
	  const analytics = this.getAnalytics();
	  if (main_core.Type.isPlainObject(steps.setup)) {
	    steps.setup.beforeCompletion = sign_v2_signSettings.decorateResultBeforeCompletion(steps.setup.beforeCompletion, () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnSetupStep)[_sendAnalyticsOnSetupStep](analytics, documentUid), () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnSetupError)[_sendAnalyticsOnSetupError](analytics));
	  }
	  if (main_core.Type.isPlainObject(steps.company)) {
	    steps.company.beforeCompletion = sign_v2_signSettings.decorateResultBeforeCompletion(steps.company.beforeCompletion, () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnCompanyStepSuccess)[_sendAnalyticsOnCompanyStepSuccess](analytics), () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnCompanyStepError)[_sendAnalyticsOnCompanyStepError](analytics));
	  }
	  if (main_core.Type.isPlainObject(steps.send)) {
	    steps.send.beforeCompletion = sign_v2_signSettings.decorateResultBeforeCompletion(steps.send.beforeCompletion, () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnSendStepSuccess)[_sendAnalyticsOnSendStepSuccess](analytics, this.documentSetup.setupData.uid), () => babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnSendStepError)[_sendAnalyticsOnSendStepError](analytics, this.documentSetup.setupData.uid));
	  }
	}
	function _sendAnalyticsOnSetupStep2(analytics, documentUid) {
	  if (this.isTemplateCreateMode()) {
	    analytics.send({
	      event: 'proceed_step_document',
	      c_element: 'create_button',
	      status: 'success'
	    });
	    analytics.send({
	      event: 'turn_on_off_template',
	      type: 'auto',
	      c_element: 'off',
	      p5: `templateId_${this.documentSetup.setupData.templateId}`
	    });
	  }
	}
	function _sendAnalyticsOnSetupError2(analytics) {
	  if (this.isTemplateCreateMode()) {
	    analytics.send({
	      event: 'proceed_step_document',
	      status: 'error',
	      c_element: 'create_button'
	    });
	  }
	}
	function _sendAnalyticsOnCompanyStepSuccess2(analytics) {
	  if (this.isTemplateCreateMode()) {
	    analytics.send({
	      event: 'proceed_step_route',
	      status: 'success',
	      c_element: 'create_button'
	    });
	  }
	}
	function _sendAnalyticsOnCompanyStepError2(analytics) {
	  if (this.isTemplateCreateMode()) {
	    analytics.send({
	      event: 'proceed_step_route',
	      status: 'error',
	      c_element: 'create_button'
	    });
	  }
	}
	async function _sendAnalyticsOnSendStepSuccess2(analytics, documentUid) {
	  if (this.isDocumentMode()) {
	    analytics.sendWithProviderTypeAndDocId({
	      event: 'sent_document_to_sign',
	      c_element: 'create_button',
	      status: 'success'
	    }, documentUid);
	  }
	}
	async function _sendAnalyticsOnSendStepError2(analytics, documentUid) {
	  if (this.isTemplateCreateMode()) {
	    this.getAnalytics().send({
	      event: 'click_save_template',
	      status: 'error',
	      c_element: 'create_button'
	    });
	  }
	  if (this.isDocumentMode()) {
	    analytics.sendWithProviderTypeAndDocId({
	      event: 'sent_document_to_sign',
	      c_element: 'create_button',
	      status: 'error'
	    }, documentUid);
	  }
	}
	function _sendAnalyticsOnStart2(documentUid) {
	  const analytics = this.getAnalytics();
	  if (this.isTemplateCreateMode()) {
	    analytics.send({
	      event: 'open_wizard',
	      c_element: 'create_button'
	    });
	  } else if (this.isDocumentMode()) {
	    const context = {
	      event: 'click_create_document',
	      c_element: 'create_button'
	    };
	    if (this.isEditMode() && main_core.Type.isStringFilled(documentUid)) {
	      analytics.sendWithDocId(context, documentUid);
	    } else {
	      analytics.send(context);
	    }
	  }
	}

	exports.B2ESignSettings = B2ESignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=sign-settings.bundle.js.map

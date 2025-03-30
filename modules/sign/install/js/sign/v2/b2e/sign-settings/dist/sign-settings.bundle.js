/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,sign_featureStorage,sign_type,sign_v2_api,sign_v2_b2e_documentSend,sign_v2_b2e_documentSetup,sign_v2_b2e_parties,sign_v2_b2e_userParty,sign_v2_editor,sign_v2_helper,sign_v2_signSettings,ui_sidepanel_layout,ui_uploader_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8;
	const acceptedUploaderFileTypes = new Set(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'rtf', 'odt']);
	var _companyParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyParty");
	var _userParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userParty");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _maxDocumentCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("maxDocumentCount");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _regionDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("regionDocumentTypes");
	var _saveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveButton");
	var _isMultiDocumentSaveProcessGone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMultiDocumentSaveProcessGone");
	var _prepareConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareConfig");
	var _editDocumentData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editDocumentData");
	var _handleEditedDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEditedDocument");
	var _removeDocumentElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeDocumentElement");
	var _attachGroupToDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("attachGroupToDocument");
	var _saveUpdatedDocumentData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveUpdatedDocumentData");
	var _deleteDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deleteDocument");
	var _setupParties = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setupParties");
	var _isTemplateModeForCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTemplateModeForCompany");
	var _syncMembersWithDepartments = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("syncMembersWithDepartments");
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
	var _setHcmLinkIntegrationSectionVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setHcmLinkIntegrationSectionVisibility");
	var _isInitiatedByEmployee = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isInitiatedByEmployee");
	var _decorateStepsBeforeCompletionWithAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("decorateStepsBeforeCompletionWithAnalytics");
	var _sendAnalyticsOnSetupStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSetupStep");
	var _sendAnalyticsOnSetupError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSetupError");
	var _sendAnalyticsOnCompanyStepSuccess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnCompanyStepSuccess");
	var _sendAnalyticsOnCompanyStepError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnCompanyStepError");
	var _sendAnalyticsOnSendStepSuccess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSendStepSuccess");
	var _sendAnalyticsOnSendStepError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnSendStepError");
	var _sendAnalyticsOnStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnStart");
	var _processSetupData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processSetupData");
	var _addDocumentInGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addDocumentInGroup");
	var _scrollToTop = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollToTop");
	var _scrollToDown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollToDown");
	var _resetDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetDocument");
	var _disableDocumentSectionIfLimitReached = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableDocumentSectionIfLimitReached");
	var _onBeforePreviewBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePreviewBtnClick");
	var _getMultiDocumentAddSidePanelContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMultiDocumentAddSidePanelContent");
	var _getDocumentNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentNumber");
	var _getUploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUploader");
	var _resetAfterPreviewSidePanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetAfterPreviewSidePanel");
	var _getUploadFileButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUploadFileButton");
	var _getUploadFileFromDirButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUploadFileFromDirButton");
	var _onInputFileChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onInputFileChange");
	var _onFileAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFileAdd");
	var _onBeforePreviewSaveBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePreviewSaveBtnClick");
	var _onUploadComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUploadComplete");
	var _getDocumentTypeSelectorLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTypeSelectorLayout");
	var _onBeforeFilesAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeFilesAdd");
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
	    Object.defineProperty(this, _onBeforeFilesAdd, {
	      value: _onBeforeFilesAdd2
	    });
	    Object.defineProperty(this, _getDocumentTypeSelectorLayout, {
	      value: _getDocumentTypeSelectorLayout2
	    });
	    Object.defineProperty(this, _onUploadComplete, {
	      value: _onUploadComplete2
	    });
	    Object.defineProperty(this, _onBeforePreviewSaveBtnClick, {
	      value: _onBeforePreviewSaveBtnClick2
	    });
	    Object.defineProperty(this, _onFileAdd, {
	      value: _onFileAdd2
	    });
	    Object.defineProperty(this, _onInputFileChange, {
	      value: _onInputFileChange2
	    });
	    Object.defineProperty(this, _getUploadFileFromDirButton, {
	      value: _getUploadFileFromDirButton2
	    });
	    Object.defineProperty(this, _getUploadFileButton, {
	      value: _getUploadFileButton2
	    });
	    Object.defineProperty(this, _resetAfterPreviewSidePanel, {
	      value: _resetAfterPreviewSidePanel2
	    });
	    Object.defineProperty(this, _getUploader, {
	      value: _getUploader2
	    });
	    Object.defineProperty(this, _getDocumentNumber, {
	      value: _getDocumentNumber2
	    });
	    Object.defineProperty(this, _getMultiDocumentAddSidePanelContent, {
	      value: _getMultiDocumentAddSidePanelContent2
	    });
	    Object.defineProperty(this, _onBeforePreviewBtnClick, {
	      value: _onBeforePreviewBtnClick2
	    });
	    Object.defineProperty(this, _disableDocumentSectionIfLimitReached, {
	      value: _disableDocumentSectionIfLimitReached2
	    });
	    Object.defineProperty(this, _resetDocument, {
	      value: _resetDocument2
	    });
	    Object.defineProperty(this, _scrollToDown, {
	      value: _scrollToDown2
	    });
	    Object.defineProperty(this, _scrollToTop, {
	      value: _scrollToTop2
	    });
	    Object.defineProperty(this, _addDocumentInGroup, {
	      value: _addDocumentInGroup2
	    });
	    Object.defineProperty(this, _processSetupData, {
	      value: _processSetupData2
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
	    Object.defineProperty(this, _isInitiatedByEmployee, {
	      value: _isInitiatedByEmployee2
	    });
	    Object.defineProperty(this, _setHcmLinkIntegrationSectionVisibility, {
	      value: _setHcmLinkIntegrationSectionVisibility2
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
	    Object.defineProperty(this, _syncMembersWithDepartments, {
	      value: _syncMembersWithDepartments2
	    });
	    Object.defineProperty(this, _isTemplateModeForCompany, {
	      value: _isTemplateModeForCompany2
	    });
	    Object.defineProperty(this, _setupParties, {
	      value: _setupParties2
	    });
	    Object.defineProperty(this, _deleteDocument, {
	      value: _deleteDocument2
	    });
	    Object.defineProperty(this, _saveUpdatedDocumentData, {
	      value: _saveUpdatedDocumentData2
	    });
	    Object.defineProperty(this, _attachGroupToDocument, {
	      value: _attachGroupToDocument2
	    });
	    Object.defineProperty(this, _removeDocumentElement, {
	      value: _removeDocumentElement2
	    });
	    Object.defineProperty(this, _handleEditedDocument, {
	      value: _handleEditedDocument2
	    });
	    Object.defineProperty(this, _editDocumentData, {
	      value: _editDocumentData2
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
	    Object.defineProperty(this, _maxDocumentCount, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _regionDocumentTypes, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _saveButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isMultiDocumentSaveProcessGone, {
	      writable: true,
	      value: false
	    });
	    const {
	      b2eFeatureConfig,
	      blankSelectorConfig: _blankSelectorConfig,
	      documentSendConfig: _documentSendConfig,
	      userPartyConfig
	    } = babelHelpers.classPrivateFieldLooseBase(this, _prepareConfig)[_prepareConfig](_signOptions);
	    this.documentSetup = new sign_v2_b2e_documentSetup.DocumentSetup(_blankSelectorConfig);
	    this.documentSend = new sign_v2_b2e_documentSend.DocumentSend(_documentSendConfig);
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty] = new sign_v2_b2e_parties.Parties({
	      ..._blankSelectorConfig,
	      documentInitiatedType: _signOptions.initiatedByType,
	      documentMode: _signOptions.documentMode
	    }, b2eFeatureConfig.hcmLinkAvailable);
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _maxDocumentCount)[_maxDocumentCount] = _signOptions.b2eDocumentLimitCount;
	    babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty] = new sign_v2_b2e_userParty.UserParty({
	      mode: 'edit',
	      ...userPartyConfig
	    });
	    this.subscribeOnEvents();
	    babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes] = _blankSelectorConfig.regionDocumentTypes;
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
	    this.documentSetup.subscribe('addDocument', () => {
	      this.setDocumentsGroup();
	    });
	    this.documentSetup.subscribe('deleteDocument', ({
	      data
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _deleteDocument)[_deleteDocument](data);
	    });
	    this.documentSetup.subscribe('editDocument', ({
	      data
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _editDocumentData)[_editDocumentData](data.uid);
	    });
	    this.documentSend.subscribe('enableComplete', () => {
	      this.wizard.toggleBtnActiveState('complete', false);
	    });
	    this.documentSend.subscribe('disableComplete', () => {
	      this.wizard.toggleBtnActiveState('complete', true);
	    });
	    this.documentSetup.subscribe('documentsLimitExceeded', () => {
	      this.documentSetup.setAvailabilityDocumentSection(false);
	    });
	    this.documentSetup.subscribe('documentsLimitNotExceeded', () => {
	      this.documentSetup.setAvailabilityDocumentSection(true);
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
	  async setDocumentsGroup() {
	    if (this.documentSetup.blankIsNotSelected || !this.documentSetup.validate()) {
	      return;
	    }
	    this.documentSetup.switchAddDocumentButtonLoadingState(true);
	    try {
	      const documentData = await this.setupDocument();
	      this.documentsGroup.set(documentData.uid, documentData);
	      this.addInDocumentsGroupUids(documentData.uid);
	      this.documentSetup.blankSelector.disableSelectedBlank(documentData.blankId);
	      await babelHelpers.classPrivateFieldLooseBase(this, _attachGroupToDocument)[_attachGroupToDocument](documentData);
	      this.documentSetup.switchAddDocumentButtonLoadingState(false);
	      if (this.editedDocument) {
	        await babelHelpers.classPrivateFieldLooseBase(this, _handleEditedDocument)[_handleEditedDocument](documentData);
	      } else {
	        this.documentSetup.renderDocumentBlock(documentData);
	      }
	    } catch {
	      this.documentSetup.switchAddDocumentButtonLoadingState(false);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _scrollToTop)[_scrollToTop]();
	    this.documentSetup.documentCounters.update(this.documentsGroup.size);
	    babelHelpers.classPrivateFieldLooseBase(this, _resetDocument)[_resetDocument]();
	    this.wizard.toggleBtnActiveState('next', false);
	  }
	  addInDocumentsGroupUids(uid) {
	    if (!this.documentsGroupUids.includes(uid)) {
	      this.documentsGroupUids.push(uid);
	    }
	  }
	  replaceInDocumentsGroupUids(oldUid, newUid) {
	    const index = this.documentsGroupUids.indexOf(oldUid);
	    if (index !== -1) {
	      this.documentsGroupUids.splice(index, 1, newUid);
	    }
	  }
	  deleteFromDocumentsGroupUids(uid) {
	    const index = this.documentsGroupUids.indexOf(uid);
	    if (index === -1) {
	      return;
	    }
	    this.documentsGroupUids.splice(index, 1);
	  }
	  async applyDocumentData(uid) {
	    const setupData = await this.setupDocument(uid, true);
	    if (!setupData) {
	      return false;
	    }
	    if (setupData.groupId) {
	      const documentsGroupData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getDocumentListInGroup(setupData.groupId);
	      for (const item of documentsGroupData) {
	        this.addInDocumentsGroupUids(item.uid);
	        const blocks = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadBlocksByDocument(item.uid);
	        const updatedItem = {
	          ...item,
	          blocks
	        };
	        this.documentsGroup.set(item.uid, updatedItem);
	        this.documentSetup.renderDocumentBlock(updatedItem);
	        this.documentSetup.blankSelector.disableSelectedBlank(updatedItem.blankId);
	      }
	      this.groupId = setupData.groupId;
	      this.documentSetup.documentCounters.update(this.documentsGroup.size);
	      babelHelpers.classPrivateFieldLooseBase(this, _resetDocument)[_resetDocument]();
	    } else {
	      this.documentsGroup.set(setupData.uid, setupData);
	      this.addInDocumentsGroupUids(setupData.uid);
	    }
	    const firstDocument = this.getFirstDocumentDataFromGroup();
	    const {
	      entityId,
	      representativeId,
	      companyUid,
	      hcmLinkCompanyId
	    } = firstDocument;
	    this.documentSend.documentData = this.documentsGroup;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentSectionIfLimitReached)[_disableDocumentSectionIfLimitReached]();
	    if (this.isSingleDocument()) {
	      this.editor.documentData = firstDocument;
	    }
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
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadValidator(reviewers[0], sign_type.MemberRole.reviewer);
	    }
	    if (editors.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].loadValidator(editors[0], sign_type.MemberRole.editor);
	    }
	    return true;
	  }
	  async applyTemplateData(templateUid) {
	    super.applyTemplateData(templateUid);
	    this.documentSetup.setupData.templateUid = templateUid;
	    this.documentSend.setExistingTemplate();
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setIntegrationSelectorAvailability(babelHelpers.classPrivateFieldLooseBase(this, _isTemplateModeForCompany)[_isTemplateModeForCompany]());
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
	  async init(uid, templateUid) {
	    await super.init(uid, templateUid);
	    if (this.isEditMode() && !main_core.Type.isNull(this.getAfterPreviewLayout())) {
	      BX.hide(this.getAfterPreviewLayout());
	    }
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
	  getAfterPreviewLayout() {
	    if (!this.isDocumentMode() || !sign_featureStorage.FeatureStorage.isMultiDocumentLoadingEnabled()) {
	      return null;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('beforePreviewLayout', () => main_core.Tag.render(_t || (_t = _`
			<button class="ui-btn ui-btn-light-border ui-btn-md" style="margin-top: 20px;" onclick="${0}">
				${0}
			</button>
		`), () => babelHelpers.classPrivateFieldLooseBase(this, _onBeforePreviewBtnClick)[_onBeforePreviewBtnClick](), main_core.Loc.getMessage('SIGN_SETTINGS_B2E_BEFORE_PREVIEW')));
	  }
	}
	function _prepareConfig2(signOptions) {
	  const {
	    config,
	    documentMode,
	    b2eDocumentLimitCount
	  } = signOptions;
	  const {
	    blankSelectorConfig,
	    documentSendConfig
	  } = config;
	  blankSelectorConfig.documentMode = documentMode;
	  blankSelectorConfig.b2eDocumentLimitCount = b2eDocumentLimitCount;
	  documentSendConfig.documentMode = documentMode;
	  return config;
	}
	async function _editDocumentData2(uid) {
	  if (this.editedDocument) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _saveUpdatedDocumentData)[_saveUpdatedDocumentData](this.editedDocument.uid);
	  }
	  if (!this.documentSetup.editMode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentSectionIfLimitReached)[_disableDocumentSectionIfLimitReached]();
	    babelHelpers.classPrivateFieldLooseBase(this, _resetDocument)[_resetDocument]();
	    return;
	  }
	  this.editedDocument = this.documentsGroup.get(uid);
	  this.documentSetup.setAvailabilityDocumentSection(true);
	  if (this.documentSetup.isRuRegion()) {
	    this.documentSetup.setDocumentNumber(this.editedDocument.externalId);
	  }
	  this.documentSetup.setDocumentTitle(this.editedDocument.title);
	  babelHelpers.classPrivateFieldLooseBase(this, _scrollToDown)[_scrollToDown]();
	}
	async function _handleEditedDocument2(documentData) {
	  if (this.documentSetup.blankIsNotSelected) {
	    this.documentSetup.resetEditMode();
	    await babelHelpers.classPrivateFieldLooseBase(this, _saveUpdatedDocumentData)[_saveUpdatedDocumentData](this.editedDocument.uid);
	    return;
	  }
	  this.deleteFromDocumentsGroupUids(documentData.uid);
	  this.replaceInDocumentsGroupUids(this.editedDocument.uid, documentData.uid);
	  this.documentSetup.replaceDocumentBlock(this.editedDocument, documentData);
	  await babelHelpers.classPrivateFieldLooseBase(this, _deleteDocument)[_deleteDocument](this.editedDocument);
	  await babelHelpers.classPrivateFieldLooseBase(this, _attachGroupToDocument)[_attachGroupToDocument](documentData);
	  this.editor.setUrls([]);
	}
	function _removeDocumentElement2(documentId) {
	  const deletedElement = this.documentSetup.layout.querySelector(`[data-id="document-id-${documentId}"]`);
	  deletedElement == null ? void 0 : deletedElement.remove();
	}
	async function _attachGroupToDocument2(documentData) {
	  if (!this.groupId) {
	    const {
	      groupId
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].createDocumentsGroup();
	    this.groupId = groupId;
	  }
	  try {
	    const targetDocument = this.documentsGroup.get(documentData.uid);
	    if (targetDocument && !targetDocument.groupId) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].attachGroupToDocument(documentData.uid, this.groupId);
	      targetDocument.groupId = this.groupId;
	    }
	  } catch (error) {
	    console.error(error);
	  }
	}
	async function _saveUpdatedDocumentData2(uid) {
	  const updatedDocumentData = await this.documentSetup.updateDocumentData(this.editedDocument);
	  this.documentSetup.updateDocumentBlock(this.editedDocument.id);
	  if (uid) {
	    this.documentsGroup.set(this.editedDocument.uid, updatedDocumentData);
	    this.addInDocumentsGroupUids(this.editedDocument.uid);
	    this.documentSend.setDocumentsBlock(this.documentsGroup);
	  }
	}
	async function _deleteDocument2(data) {
	  const {
	    id,
	    uid,
	    blankId,
	    deleteButton
	  } = data;
	  this.documentSetup.ready = false;
	  try {
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].removeDocument(uid);
	    this.documentSetup.ready = true;
	    if (this.isFirstDocumentSelected(uid)) {
	      this.resetPreview();
	      this.hasPreviewUrls = false;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _removeDocumentElement)[_removeDocumentElement](id);
	    this.documentSend.deleteDocument(uid);
	    this.documentsGroup.delete(uid);
	    this.deleteFromDocumentsGroupUids(uid);
	    this.documentSetup.blankSelector.enableSelectedBlank(blankId);
	    this.documentSetup.deleteDocumentFromList(blankId);
	    this.documentSetup.documentCounters.update(this.documentsGroup.size);
	    this.documentSetup.resetEditMode();
	    if (this.documentsGroup.size === 0) {
	      this.wizard.toggleBtnActiveState('next', true);
	    } else {
	      this.documentSetup.setupData = this.getFirstDocumentDataFromGroup();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _resetDocument)[_resetDocument]();
	  } catch {
	    this.documentSetup.toggleDeleteBtnLoadingState(deleteButton);
	    this.documentSetup.ready = true;
	  }
	}
	async function _setupParties2() {
	  const {
	    representative
	  } = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  const {
	    members,
	    signerParty
	  } = babelHelpers.classPrivateFieldLooseBase(this, _makeSetupMembers)[_makeSetupMembers]();
	  const documentUids = [...this.documentsGroup.keys()];
	  for (const documentUid of documentUids) {
	    // eslint-disable-next-line no-await-in-loop
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].setupB2eParties(documentUid, representative.entityId, members);
	  }
	  const uid = babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid];
	  const membersData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadMembers(uid);
	  if (!main_core.Type.isArrayFilled(membersData)) {
	    throw new Error('Members are empty');
	  }
	  const syncMemberPromises = documentUids.map(uid => babelHelpers.classPrivateFieldLooseBase(this, _syncMembersWithDepartments)[_syncMembersWithDepartments](uid, signerParty));
	  await Promise.all(syncMemberPromises);
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
	function _isTemplateModeForCompany2() {
	  const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === sign_type.DocumentInitiated.company;
	  return sign_v2_signSettings.isTemplateMode(this.documentMode) && isInitiatedByCompany;
	}
	async function _syncMembersWithDepartments2(uid, signerParty) {
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
	  return this.documentSetup.setupData.initiatedByType === sign_type.DocumentInitiated.employee;
	}
	function _getAssignee2(currentParty, companyId) {
	  return {
	    entityType: 'company',
	    entityId: companyId,
	    party: currentParty,
	    role: sign_type.MemberRole.assignee
	  };
	}
	function _getSigner2(currentParty, entity) {
	  return {
	    entityType: entity.entityType,
	    entityId: entity.entityId,
	    party: currentParty,
	    role: sign_type.MemberRole.signer
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
	      if (!main_core.Type.isNull(signSettings.getAfterPreviewLayout())) {
	        BX.show(signSettings.getAfterPreviewLayout());
	      }
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
	    beforeCompletion: async () => {
	      const blankIsSelected = this.documentSetup.blankSelector.selectedBlankId !== 0;
	      if (blankIsSelected || this.documentSetup.isFileAdded) {
	        const isValid = this.documentSetup.validate();
	        if (!isValid) {
	          return false;
	        }
	      }
	      const setupData = await this.setupDocument();
	      if (!setupData) {
	        return false;
	      }
	      await babelHelpers.classPrivateFieldLooseBase(this, _addDocumentInGroup)[_addDocumentInGroup](setupData);
	      if (this.editedDocument) {
	        await babelHelpers.classPrivateFieldLooseBase(this, _handleEditedDocument)[_handleEditedDocument](setupData);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _resetDocument)[_resetDocument]();
	      babelHelpers.classPrivateFieldLooseBase(this, _processSetupData)[_processSetupData]();
	      babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentSectionIfLimitReached)[_disableDocumentSectionIfLimitReached]();
	      if (!main_core.Type.isNull(this.getAfterPreviewLayout())) {
	        BX.hide(this.getAfterPreviewLayout());
	      }
	      return true;
	    }
	  };
	}
	function _getCompanyStep2(signSettings) {
	  const titleLocCode = this.isTemplateMode() ? 'SIGN_SETTINGS_B2E_ROUTES' : 'SIGN_SETTINGS_B2E_COMPANY';
	  return {
	    get content() {
	      const layout = babelHelpers.classPrivateFieldLooseBase(signSettings, _companyParty)[_companyParty].getLayout();
	      const isTemplateModeForCompany = babelHelpers.classPrivateFieldLooseBase(signSettings, _isTemplateModeForCompany)[_isTemplateModeForCompany]();
	      if (signSettings.isTemplateMode()) {
	        babelHelpers.classPrivateFieldLooseBase(signSettings, _companyParty)[_companyParty].setEditorAvailability(isTemplateModeForCompany);
	      }
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage(titleLocCode),
	    beforeCompletion: async () => {
	      const {
	        uid,
	        initiatedByType
	      } = this.documentSetup.setupData;
	      try {
	        for (const [uid] of this.documentsGroup) {
	          await babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].save(uid);
	        }
	        this.editor.setSenderType(initiatedByType);
	        this.documentSetup.setupData.integrationId = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getSelectedIntegrationId();
	        this.documentSend.hcmLinkEnabled = this.documentSetup.setupData.integrationId > 0;
	        babelHelpers.classPrivateFieldLooseBase(this, _setSecondPartySectionVisibility)[_setSecondPartySectionVisibility]();
	        babelHelpers.classPrivateFieldLooseBase(this, _setHcmLinkIntegrationSectionVisibility)[_setHcmLinkIntegrationSectionVisibility]();
	        if (this.isTemplateMode()) {
	          const entityData = await babelHelpers.classPrivateFieldLooseBase(this, _setupParties)[_setupParties]();
	          this.editor.entityData = entityData;
	          const {
	            isTemplate,
	            entityId
	          } = this.documentSetup.setupData;
	          const blocks = await this.documentSetup.loadBlocks(uid);
	          babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions]();
	          const editorData = {
	            isTemplate,
	            uid,
	            blocks,
	            entityId
	          };
	          babelHelpers.classPrivateFieldLooseBase(this, _executeEditorActions)[_executeEditorActions](editorData);
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
	          isTemplate,
	          entityId
	        } = this.documentSetup.setupData;
	        const blocks = await this.documentSetup.loadBlocks(uid);
	        babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions]();
	        const editorData = {
	          isTemplate,
	          uid,
	          blocks,
	          entityId
	        };
	        await babelHelpers.classPrivateFieldLooseBase(this, _executeEditorActions)[_executeEditorActions](editorData);
	        return true;
	      } catch (e) {
	        console.error(e);
	        return false;
	      }
	    }
	  };
	}
	async function _executeDocumentSendActions2() {
	  const partiesData = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  Object.assign(partiesData, {
	    employees: babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getEntities().map(entity => {
	      return {
	        entityType: entity.entityType,
	        entityId: entity.entityId
	      };
	    })
	  });
	  this.documentSend.documentData = this.documentsGroup;
	  this.documentSend.resetUserPartyPopup();
	  this.documentSend.setPartiesData(partiesData);
	}
	async function _executeEditorActions2(editorData) {
	  if (this.isTemplateCreateMode()) {
	    this.editor.setAnalytics(this.getAnalytics());
	  }
	  this.wizard.toggleBtnLoadingState('next', false);
	  if (this.isSingleDocument()) {
	    this.editor.documentData = editorData;
	    await this.editor.waitForPagesUrls();
	    await this.editor.renderDocument();
	    await this.editor.show();
	  }
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
	  const isNotSesRuProvider = selectedProvider.code !== sign_type.ProviderCode.sesRu;
	  const isSecondPartySectionVisible = isNotSesRuProvider || sign_v2_signSettings.isTemplateMode(this.documentMode) && babelHelpers.classPrivateFieldLooseBase(this, _isInitiatedByEmployee)[_isInitiatedByEmployee]();
	  this.editor.setSectionVisibilityByType(sign_v2_editor.SectionType.SecondParty, isSecondPartySectionVisible);
	}
	async function _setHcmLinkIntegrationSectionVisibility2() {
	  const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === sign_type.DocumentInitiated.company;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isInitiatedByEmployee)[_isInitiatedByEmployee]() && this.documentSetup.isRuRegion()) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeIntegrationId(this.documentSetup.setupData.uid, null);
	  }
	  const isHcmLinkIntegrationSectionVisible = this.documentSetup.setupData.integrationId > 0 && isInitiatedByCompany;
	  this.editor.setSectionVisibilityByType(sign_v2_editor.SectionType.HcmLinkIntegration, isHcmLinkIntegrationSectionVisible);
	}
	function _isInitiatedByEmployee2() {
	  return this.documentSetup.setupData.initiatedByType === sign_type.DocumentInitiated.employee;
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
	async function _processSetupData2() {
	  const firstDocumentData = this.getFirstDocumentDataFromGroup();
	  babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setInitiatedByType(firstDocumentData.initiatedByType);
	  babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setEntityId(firstDocumentData.entityId);
	  if (this.isTemplateMode()) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].reloadCompanyProviders();
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setIntegrationSelectorAvailability(babelHelpers.classPrivateFieldLooseBase(this, _isTemplateModeForCompany)[_isTemplateModeForCompany]());
	  }
	  this.editedDocument = null;
	  if (this.hasPreviewUrls) {
	    this.wizard.toggleBtnActiveState('next', false);
	  }
	}
	async function _addDocumentInGroup2(setupData) {
	  if (this.documentSetup.blankIsNotSelected) {
	    return;
	  }
	  if (this.isTemplateMode() || !sign_featureStorage.FeatureStorage.isGroupSendingEnabled()) {
	    this.setSingleDocument(setupData);
	    return;
	  }
	  this.documentsGroup.set(setupData.uid, setupData);
	  this.addInDocumentsGroupUids(setupData.uid);
	  this.documentSetup.blankSelector.disableSelectedBlank(setupData.blankId);
	  if (!setupData.groupId && !this.editedDocument) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _attachGroupToDocument)[_attachGroupToDocument](setupData);
	  }
	  if (!this.isTemplateMode()) {
	    this.documentSetup.documentCounters.update(this.documentsGroup.size);
	  }
	  if (!this.editedDocument) {
	    this.documentSetup.renderDocumentBlock(setupData);
	  }
	}
	function _scrollToTop2() {
	  window.scrollTo({
	    top: 0,
	    behavior: 'smooth'
	  });
	}
	function _scrollToDown2() {
	  window.scrollTo({
	    top: document.body.scrollHeight,
	    behavior: 'smooth'
	  });
	}
	function _resetDocument2() {
	  if (this.isTemplateMode() || !sign_featureStorage.FeatureStorage.isGroupSendingEnabled()) {
	    return;
	  }
	  this.documentSetup.resetDocument();
	  this.editedDocument = null;
	}
	function _disableDocumentSectionIfLimitReached2() {
	  if (this.documentsGroup.size >= babelHelpers.classPrivateFieldLooseBase(this, _maxDocumentCount)[_maxDocumentCount]) {
	    this.documentSetup.setAvailabilityDocumentSection(false);
	  }
	}
	function _onBeforePreviewBtnClick2() {
	  // eslint-disable-next-line unicorn/no-this-assignment
	  const self = this;
	  BX.SidePanel.Instance.open('sign-settings:afterPreviewSidePanel', {
	    cacheable: false,
	    width: 750,
	    contentCallback: () => {
	      babelHelpers.classPrivateFieldLooseBase(self, _resetAfterPreviewSidePanel)[_resetAfterPreviewSidePanel]();
	      return ui_sidepanel_layout.Layout.createContent({
	        extensions: ['ui.forms'],
	        title: 'Добавить папку с файлами',
	        content() {
	          babelHelpers.classPrivateFieldLooseBase(self, _getUploader)[_getUploader]();
	          return babelHelpers.classPrivateFieldLooseBase(self, _getMultiDocumentAddSidePanelContent)[_getMultiDocumentAddSidePanelContent]();
	        },
	        buttons({
	          cancelButton,
	          SaveButton
	        }) {
	          babelHelpers.classPrivateFieldLooseBase(self, _saveButton)[_saveButton] = new SaveButton({
	            onclick: () => babelHelpers.classPrivateFieldLooseBase(self, _onBeforePreviewSaveBtnClick)[_onBeforePreviewSaveBtnClick]()
	          });
	          return [babelHelpers.classPrivateFieldLooseBase(self, _saveButton)[_saveButton]];
	        }
	      });
	    },
	    events: {
	      onClose: event => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isMultiDocumentSaveProcessGone)[_isMultiDocumentSaveProcessGone]) {
	          event.denyAction();
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _resetAfterPreviewSidePanel)[_resetAfterPreviewSidePanel]();
	      }
	    }
	  });
	}
	function _getMultiDocumentAddSidePanelContent2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('multiDocumentAddSidePanelContent', () => main_core.Tag.render(_t2 || (_t2 = _`
			<div id="multiple-document-add-container" style="display: flex; flex-direction: column;">
				<div style="flex-direction: row;">
					${0}
					${0}
				</div>
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getUploadFileFromDirButton)[_getUploadFileFromDirButton]().root, babelHelpers.classPrivateFieldLooseBase(this, _getUploadFileButton)[_getUploadFileButton](), babelHelpers.classPrivateFieldLooseBase(this, _getDocumentNumber)[_getDocumentNumber]().root, babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeSelectorLayout)[_getDocumentTypeSelectorLayout]().root));
	}
	function _getDocumentNumber2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('documentNumber', () => main_core.Tag.render(_t3 || (_t3 = _`
			<div>
				<h3>${0}</h3>
				<div class="ui-ctl ui-ctl-w100" style="margin-top: 25px;">
					<input type="text" class="ui-ctl-element" ref="numberInput" maxlength="255"/>
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_DOCUMENT_NUMBER_TITLE')));
	}
	function _getUploader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('uploader', () => {
	    return new ui_uploader_core.Uploader({
	      id: 'sign-settings-uploader',
	      controller: 'sign.upload.blankUploadController',
	      acceptedFileTypes: [...acceptedUploaderFileTypes.values()].map(a => `.${a}`),
	      multiple: true,
	      autoUpload: false,
	      maxFileSize: 52428800,
	      imageMaxFileSize: 10485760,
	      maxTotalFileSize: 52428800,
	      events: {
	        [ui_uploader_core.UploaderEvent.BEFORE_FILES_ADD]: event => babelHelpers.classPrivateFieldLooseBase(this, _onBeforeFilesAdd)[_onBeforeFilesAdd](event),
	        [ui_uploader_core.UploaderEvent.FILE_ADD]: event => babelHelpers.classPrivateFieldLooseBase(this, _onFileAdd)[_onFileAdd](event.getData().file),
	        [ui_uploader_core.UploaderEvent.UPLOAD_COMPLETE]: event => babelHelpers.classPrivateFieldLooseBase(this, _onUploadComplete)[_onUploadComplete](event)
	      }
	    });
	  });
	}
	function _resetAfterPreviewSidePanel2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('uploader');
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('uploadButton');
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('uploadFromDirButton');
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('documentNumber');
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('numberSelectorLayout');
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('multiDocumentAddSidePanelContent');
	}
	function _getUploadFileButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('uploadButton', () => {
	    const layout = main_core.Tag.render(_t4 || (_t4 = _`
				<div>
					<button class="ui-btn ui-btn-light-border" style="margin-top: 15px;" onclick="${0}">${0}</button>
					<input ref="fileInput" hidden type="file" multiple ref="fileInput" onchange="${0}"
						accept="${0}"
					>
				</div>
			`), () => layout.fileInput.click(), main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_ADD_FILE'), event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onInputFileChange)[_onInputFileChange](event);
	      event.target.value = '';
	    }, [...acceptedUploaderFileTypes.values()].map(n => `.${n}`).join(', '));
	    return layout.root;
	  });
	}
	function _getUploadFileFromDirButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('uploadFromDirButton', () => {
	    const layout = main_core.Tag.render(_t5 || (_t5 = _`
				<div>
					<button class="ui-btn ui-btn-primary" style="margin-top: 15px;" onclick="${0}">${0}</button>
					<input hidden type="file" webkitdirectory multiple ref="fileInput" onchange="${0}">
				</div>
			`), () => layout.fileInput.click(), main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_LOAD_FROM_DIRS'), event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onInputFileChange)[_onInputFileChange](event);
	      event.target.value = '';
	    });
	    return layout;
	  });
	}
	function _onInputFileChange2(event) {
	  const target = event.target;
	  const files = target.files;
	  const validatedFiles = [...files].filter(f => acceptedUploaderFileTypes.has(f.name.split('.').at(-1)));
	  babelHelpers.classPrivateFieldLooseBase(this, _getUploader)[_getUploader]().addFiles(validatedFiles);
	}
	function _onFileAdd2(file) {
	  main_core.Dom.insertAfter(main_core.Tag.render(_t6 || (_t6 = _`<p style="color: #666">${0}</p>`), main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_LOADED_FILE_NAME', {
	    '#FILENAME#': main_core.Text.encode(file.getName())
	  })), babelHelpers.classPrivateFieldLooseBase(this, _getUploadFileButton)[_getUploadFileButton]());
	}
	function _onBeforePreviewSaveBtnClick2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getUploader)[_getUploader]().getFiles().length === 0) {
	    alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_NO_FILES_SELECTED'));
	    return;
	  }
	  if (!((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeSelectorLayout)[_getDocumentTypeSelectorLayout]().selector.value) != null && _babelHelpers$classPr.trim())) {
	    alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_NO_DOCUMENT_TYPE_SELECTED'));
	    return;
	  }
	  if (!((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getDocumentNumber)[_getDocumentNumber]().numberInput.value) != null && _babelHelpers$classPr2.trim())) {
	    alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_NO_DOCUMENT_NUMBER'));
	    return;
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getMultiDocumentAddSidePanelContent)[_getMultiDocumentAddSidePanelContent](), {
	    opacity: 0.6,
	    'pointer-events': 'none'
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _isMultiDocumentSaveProcessGone)[_isMultiDocumentSaveProcessGone] = true;
	  babelHelpers.classPrivateFieldLooseBase(this, _getUploader)[_getUploader]().start();
	  babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton].setClocking(true);
	}
	async function _onUploadComplete2(event) {
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _getUploader)[_getUploader]();
	  for (const file of uploader.getFiles()) {
	    try {
	      const blankId = await this.documentSetup.blankSelector.createBlankFromOuterUploaderFiles([file]);
	      this.documentSetup.blankSelector.selectBlank(blankId);
	      this.documentSetup.setDocumentType(babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeSelectorLayout)[_getDocumentTypeSelectorLayout]().selector.value);
	      this.documentSetup.setDocumentNumber(babelHelpers.classPrivateFieldLooseBase(this, _getDocumentNumber)[_getDocumentNumber]().numberInput.value);
	      await this.setDocumentsGroup();
	    } catch (e) {
	      console.error(`Error while add file with name ${file.getName()}`, e);
	    }
	  }
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getMultiDocumentAddSidePanelContent)[_getMultiDocumentAddSidePanelContent](), {
	    opacity: 1,
	    'pointer-events': 'auto'
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _isMultiDocumentSaveProcessGone)[_isMultiDocumentSaveProcessGone] = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton].setClocking(false);
	  BX.SidePanel.Instance.close();
	}
	function _getDocumentTypeSelectorLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('numberSelectorLayout', () => {
	    return main_core.Tag.render(_t7 || (_t7 = _`
				<div style="margin-top: 15px;">
					<h3>${0}</h3>
					<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" ref="selector">
							${0}
						</select>
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_DOCUMENT_TYPE_SELECTOR_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes].map(({
	      code,
	      description
	    }) => main_core.Tag.render(_t8 || (_t8 = _`
								<option value="${0}">${0}: ${0}</option>
							`), main_core.Text.encode(code), code, main_core.Text.encode(description))));
	  });
	}
	function _onBeforeFilesAdd2(event) {
	  const uploaderConfig = {
	    maxFileSize: 52428800,
	    imageMaxFileSize: 10485760,
	    maxTotalFileSize: 52428800
	  };
	  const data = event.getData();
	  const files = data.files;
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _getUploader)[_getUploader]();
	  const allFilesWithNew = [...files, ...uploader.getFiles()];
	  if (allFilesWithNew.length + this.documentsGroup.size > babelHelpers.classPrivateFieldLooseBase(this, _maxDocumentCount)[_maxDocumentCount]) {
	    alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_MAX_DOCUMENTS_COUNT_EXCEEDED'));
	    event.preventDefault();
	    return;
	  }
	  for (const file of files) {
	    if (file.getSize() > uploaderConfig.maxFileSize) {
	      alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_INVALID_FILE_SIZE'));
	      event.preventDefault();
	      return;
	    }
	    if (file.isImage() && file.getSize() > uploaderConfig.imageMaxFileSize) {
	      alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_INVALID_IMAGE_FILE_SIZE'));
	      event.preventDefault();
	      return;
	    }
	  }
	  const totalFileSize = allFilesWithNew.reduce((acc, file) => acc + file.getSize(), 0);
	  if (totalFileSize > uploaderConfig.maxTotalFileSize) {
	    alert(main_core.Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_MAX_TOTAL_FILE_SIZE_EXCEEDED'));
	    event.preventDefault();
	  }
	}

	exports.B2ESignSettings = B2ESignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX.Sign,BX.Sign,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.UI.SidePanel,BX.UI.Uploader));
//# sourceMappingURL=sign-settings.bundle.js.map

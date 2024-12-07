/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_featureStorage,sign_v2_api,sign_v2_b2e_companySelector,sign_v2_b2e_documentSend,sign_v2_b2e_documentSetup,sign_v2_b2e_parties,sign_v2_b2e_userParty,sign_v2_documentSetup,sign_v2_editor,sign_v2_helper,sign_v2_signSettings) {
	'use strict';

	let _ = t => t,
	  _t;
	var _companyParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyParty");
	var _userParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userParty");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _prepareConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareConfig");
	var _setupParties = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setupParties");
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
	      blankSelectorConfig: _blankSelectorConfig,
	      documentSendConfig: _documentSendConfig,
	      userPartyConfig
	    } = babelHelpers.classPrivateFieldLooseBase(this, _prepareConfig)[_prepareConfig](_signOptions);
	    _blankSelectorConfig.hideValidationParty = sign_v2_signSettings.isTemplateMode(this.documentMode);
	    this.documentSetup = new sign_v2_b2e_documentSetup.DocumentSetup(_blankSelectorConfig);
	    this.documentSend = new sign_v2_b2e_documentSend.DocumentSend(_documentSendConfig);
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty] = new sign_v2_b2e_parties.Parties(_blankSelectorConfig);
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
	  }
	  async applyDocumentData(uid) {
	    const setupData = await this.setupDocument(uid, true);
	    if (!setupData) {
	      return false;
	    }
	    const {
	      entityId,
	      representativeId,
	      companyUid
	    } = setupData;
	    this.documentSend.documentData = setupData;
	    this.editor.documentData = setupData;
	    babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].setEntityId(entityId);
	    if (companyUid) {
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
	  getStepsMetadata(signSettings) {
	    const steps = {
	      setup: babelHelpers.classPrivateFieldLooseBase(this, _getSetupStep)[_getSetupStep](signSettings),
	      company: babelHelpers.classPrivateFieldLooseBase(this, _getCompanyStep)[_getCompanyStep](signSettings)
	    };
	    if (!this.isTemplateMode()) {
	      steps.employees = babelHelpers.classPrivateFieldLooseBase(this, _getEmployeeStep)[_getEmployeeStep](signSettings);
	    }
	    steps.send = babelHelpers.classPrivateFieldLooseBase(this, _getSendStep)[_getSendStep](signSettings);
	    return steps;
	  }
	  onComplete() {
	    if (this.isTemplateMode()) {
	      BX.SidePanel.Instance.close();
	      BX.SidePanel.Instance.open('sign-settings-template-created', {
	        contentCallback: () => {
	          return Promise.resolve(this.getTemplateStatusLayout());
	        }
	      });
	      return;
	    }
	    super.onComplete();
	  }
	  getTemplateStatusLayout() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="sign-b2e-template-status">
				<div class="sign-b2e-template-status-inner">
					<div class="sign-b2e-template-status-img"></div>
					<div class="sign-b2e-template-status-title">${0}</div>
					<button class="ui-btn ui-btn-light-border ui-btn-round" onclick="BX.SidePanel.Instance.close();">${0}</button>
				</div>
			 </div>
		`), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED'), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_LIST'));
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
	  const members = babelHelpers.classPrivateFieldLooseBase(this, _makeSetupMembers)[_makeSetupMembers]();
	  await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].setupB2eParties(uid, representative.entityId, members);
	  const membersData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadMembers(uid);
	  if (!main_core.Type.isArrayFilled(membersData)) {
	    throw new Error('Members are empty');
	  }
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
	function _getSigner2(currentParty, userId) {
	  return {
	    entityType: 'user',
	    entityId: userId,
	    party: currentParty,
	    role: sign_v2_api.MemberRole.signer
	  };
	}
	function _makeSetupMembers2() {
	  const {
	    company,
	    validation
	  } = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  const userPartyIds = babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getUserIds();
	  let members = [];
	  let currentParty = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee] ? 2 : 1;
	  members = validation.map(item => {
	    const result = {
	      ...item,
	      party: currentParty
	    };
	    currentParty++;
	    return result;
	  });
	  members.push(babelHelpers.classPrivateFieldLooseBase(this, _getAssignee)[_getAssignee](currentParty, company.entityId));
	  if (!this.isTemplateMode()) {
	    const signerParty = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee] ? 1 : currentParty + 1;
	    const signers = userPartyIds.map(userId => babelHelpers.classPrivateFieldLooseBase(this, _getSigner)[_getSigner](signerParty, userId));
	    members.push(...signers);
	  }
	  return members;
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
	function _getSetupStep2(signSettings) {
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
	  return {
	    get content() {
	      const layout = babelHelpers.classPrivateFieldLooseBase(signSettings, _companyParty)[_companyParty].getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_B2E_COMPANY'),
	    beforeCompletion: async () => {
	      const {
	        uid
	      } = this.documentSetup.setupData;
	      try {
	        await babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].save(uid);
	        babelHelpers.classPrivateFieldLooseBase(this, _setSecondPartySectionVisibility)[_setSecondPartySectionVisibility]();
	        if (this.isTemplateMode()) {
	          const entityData = await babelHelpers.classPrivateFieldLooseBase(this, _setupParties)[_setupParties]();
	          this.editor.entityData = entityData;
	          const {
	            title,
	            isTemplate,
	            entityId,
	            externalId,
	            templateUid
	          } = this.documentSetup.setupData;
	          const blocks = await this.documentSetup.loadBlocks(uid);
	          const documentSendData = {
	            uid,
	            title,
	            blocks,
	            externalId,
	            templateUid
	          };
	          babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions](documentSendData, entityData);
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
	        const entityData = await babelHelpers.classPrivateFieldLooseBase(this, _setupParties)[_setupParties]();
	        this.editor.entityData = entityData;
	        const {
	          uid,
	          title,
	          isTemplate,
	          externalId,
	          entityId
	        } = this.documentSetup.setupData;
	        const blocks = await this.documentSetup.loadBlocks(uid);
	        const documentSendData = {
	          uid,
	          title,
	          blocks,
	          externalId
	        };
	        babelHelpers.classPrivateFieldLooseBase(this, _executeDocumentSendActions)[_executeDocumentSendActions](documentSendData, entityData);
	        const editorData = {
	          isTemplate,
	          uid,
	          blocks,
	          entityId
	        };
	        babelHelpers.classPrivateFieldLooseBase(this, _executeEditorActions)[_executeEditorActions](editorData);
	        return true;
	      } catch (e) {
	        console.error(e);
	        return false;
	      }
	    }
	  };
	}
	async function _executeDocumentSendActions2(documentSendData, entityData) {
	  const partiesData = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getParties();
	  Object.assign(partiesData, {
	    employees: babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getUserIds().map(userId => {
	      return {
	        entityType: 'user',
	        entityId: userId
	      };
	    })
	  });
	  this.documentSend.documentData = documentSendData;
	  this.documentSend.setPartiesData(partiesData);
	  this.documentSend.members = entityData;
	}
	async function _executeEditorActions2(editorData) {
	  this.editor.documentData = editorData;
	  await this.editor.waitForPagesUrls();
	  await this.editor.renderDocument();
	  this.wizard.toggleBtnLoadingState('next', false);
	  await this.editor.show();
	}
	function _getSendStep2(signSettings) {
	  return {
	    get content() {
	      const layout = signSettings.documentSend.getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_SEND_DOCUMENT'),
	    beforeCompletion: () => this.documentSend.sendForSign()
	  };
	}
	function _setSecondPartySectionVisibility2() {
	  const selectedProvider = babelHelpers.classPrivateFieldLooseBase(this, _companyParty)[_companyParty].getSelectedProvider();
	  const isSecondPartySectionVisible = selectedProvider.code !== sign_v2_b2e_companySelector.ProviderCode.sesRu || sign_v2_signSettings.isTemplateMode(this.documentMode);
	  this.editor.setSectionVisibilityByType(sign_v2_editor.SectionType.SecondParty, isSecondPartySectionVisible);
	}

	exports.B2ESignSettings = B2ESignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=sign-settings.bundle.js.map

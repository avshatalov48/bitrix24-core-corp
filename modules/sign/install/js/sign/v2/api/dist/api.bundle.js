/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,ui_notification,ui_sidepanelContent) {
	'use strict';

	async function request(method, endpoint, data, notifyError = true) {
	  const config = {
	    method
	  };
	  if (method === 'POST') {
	    Object.assign(config, {
	      data
	    }, {
	      preparePost: false,
	      headers: [{
	        name: 'Content-Type',
	        value: 'application/json'
	      }]
	    });
	  }
	  try {
	    var _response$errors;
	    const response = await main_core.ajax.runAction(endpoint, config);
	    if (((_response$errors = response.errors) == null ? void 0 : _response$errors.length) > 0) {
	      throw new Error(response.errors[0].message);
	    }
	    return response.data;
	  } catch (ex) {
	    var _errors$0$code, _errors$, _errors$0$message, _errors$2;
	    if (!notifyError) {
	      return ex;
	    }
	    const {
	      message = `Error in ${endpoint}`,
	      errors = []
	    } = ex;
	    const errorCode = (_errors$0$code = (_errors$ = errors[0]) == null ? void 0 : _errors$.code) != null ? _errors$0$code : '';
	    if (errorCode === 'SIGN_CLIENT_CONNECTION_ERROR') {
	      const stub = new ui_sidepanelContent.StubNotAvailable({
	        title: main_core.Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_TITLE'),
	        desc: main_core.Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_DESC'),
	        type: ui_sidepanelContent.StubType.noConnection,
	        link: {
	          text: main_core.Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_LINK_TEXT'),
	          value: '18740976',
	          type: ui_sidepanelContent.StubLinkType.helpdesk
	        }
	      });
	      stub.openSlider();
	      throw ex;
	    }
	    if (errorCode === 'LICENSE_LIMITATIONS') {
	      top.BX.UI.InfoHelper.show('limit_office_e_signature_box');
	      throw ex;
	    }
	    if (errorCode === 'SIGN_DOCUMENT_INCORRECT_STATUS') {
	      const stub = new ui_sidepanelContent.StubNotAvailable({
	        title: main_core.Loc.getMessage('SIGN_DOCUMENT_INCORRECT_STATUS_STUB_TITLE'),
	        desc: main_core.Loc.getMessage('SIGN_DOCUMENT_INCORRECT_STATUS_STUB_DESC'),
	        type: ui_sidepanelContent.StubType.notAvailable
	      });
	      stub.openSlider();

	      //close previous slider (with editor)
	      const slider = BX.SidePanel.Instance.getTopSlider();
	      const onSliderCloseHandler = e => {
	        if (slider !== e.getSlider()) {
	          return;
	        }
	        window.top.BX.removeCustomEvent(slider.getWindow(), 'SidePanel.Slider:onClose', onSliderCloseHandler);
	        const sliders = window.top.BX.SidePanel.Instance.getOpenSliders();
	        for (let i = sliders.length - 2; i >= 0; i--) {
	          if (sliders[i].getUrl().startsWith('/sign/doc/')) {
	            sliders[i].close();
	            return;
	          }
	        }
	      };
	      window.top.BX.addCustomEvent(slider.getWindow(), 'SidePanel.Slider:onClose', onSliderCloseHandler);
	      throw ex;
	    }
	    if (errorCode === 'B2E_RESTRICTED_ON_TARIFF' || errorCode === 'B2E_SIGNERS_LIMIT_REACHED_ON_TARIFF') {
	      top.BX.UI.InfoHelper.show('limit_office_e_signature');
	      throw ex;
	    }
	    const content = (_errors$0$message = (_errors$2 = errors[0]) == null ? void 0 : _errors$2.message) != null ? _errors$0$message : message;
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(content),
	      autoHideDelay: 4000
	    });
	    throw ex;
	  }
	}
	function post(endpoint, data = null, notifyError = true) {
	  return request('POST', endpoint, data, notifyError);
	}

	class TemplateApi {
	  getList() {
	    return post('sign.api_v1.b2e.document.template.list');
	  }
	  completeTemplate(templateUid) {
	    return post('sign.api_v1.b2e.document.template.complete', {
	      uid: templateUid
	    });
	  }
	  send(templateUid) {
	    return post('sign.api_v1.b2e.document.template.send', {
	      uid: templateUid
	    });
	  }
	}

	const MemberRole = Object.freeze({
	  assignee: 'assignee',
	  signer: 'signer',
	  editor: 'editor',
	  reviewer: 'reviewer'
	});
	const MemberStatus = Object.freeze({
	  done: 'done',
	  wait: 'wait',
	  ready: 'ready',
	  refused: 'refused',
	  stopped: 'stopped',
	  stoppableReady: 'stoppable_ready',
	  processing: 'processing'
	});

	var _post = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("post");
	class Api {
	  constructor() {
	    Object.defineProperty(this, _post, {
	      value: _post2
	    });
	    this.template = new TemplateApi();
	  }
	  register(blankId, scenarioType = null, asTemplate = false) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.register', {
	      blankId,
	      scenarioType,
	      asTemplate
	    });
	  }
	  upload(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.upload', {
	      uid
	    });
	  }
	  getPages(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.pages.list', {
	      uid
	    }, false);
	  }
	  loadBlanks(page, scenario = null) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.list', {
	      page,
	      scenario
	    });
	  }
	  createBlank(files, scenario = null, forTemplate = false) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.create', {
	      files,
	      scenario,
	      forTemplate
	    });
	  }
	  saveBlank(documentUid, blocks) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.block.save', {
	      documentUid,
	      blocks
	    }, false);
	  }
	  loadBlocksData(documentUid, blocks) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.block.loadData', {
	      documentUid,
	      blocks
	    });
	  }
	  changeDocument(uid, blankId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.changeBlank', {
	      uid,
	      blankId
	    });
	  }
	  changeDocumentLanguages(uid, lang) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.changeDocumentLanguages', {
	      uid,
	      lang
	    });
	  }
	  changeRegionDocumentType(uid, type) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyRegionDocumentType', {
	      uid,
	      type
	    });
	  }
	  changeExternalId(uid, id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyExternalId', {
	      uid,
	      id
	    });
	  }
	  changeExternalDate(uid, externalDate) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyExternalDate', {
	      uid,
	      externalDate
	    });
	  }
	  loadDocument(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.load', {
	      uid
	    });
	  }
	  configureDocument(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.configure', {
	      uid
	    });
	  }
	  loadBlocksByDocument(documentUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.block.loadByDocument', {
	      documentUid
	    });
	  }
	  startSigning(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.signing.start', {
	      uid
	    });
	  }
	  addMember(documentUid, entityType, entityId, party, presetId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.add', {
	      documentUid,
	      entityType,
	      entityId,
	      party,
	      presetId
	    });
	  }
	  removeMember(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.remove', {
	      uid
	    });
	  }
	  loadMembers(documentUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.load', {
	      documentUid
	    });
	  }
	  modifyCommunicationChannel(uid, channelType, channelValue) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.modifyCommunicationChannel', {
	      uid,
	      channelType,
	      channelValue
	    });
	  }
	  loadCommunications(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.loadCommunications', {
	      uid
	    });
	  }
	  modifyTitle(uid, title) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyTitle', {
	      uid,
	      title
	    });
	  }
	  modifyInitiator(uid, initiator) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyInitiator', {
	      uid,
	      initiator
	    });
	  }
	  modifyLanguageId(uid, langId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyLangId', {
	      uid,
	      langId
	    });
	  }
	  modifyReminderTypeForMemberRole(documentUid, memberRole, reminderType) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.b2e.member.reminder.set', {
	      documentUid,
	      memberRole,
	      type: reminderType
	    });
	  }
	  loadLanguages() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.loadLanguage');
	  }
	  refreshEntityNumber(documentUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.refreshEntityNumber', {
	      documentUid
	    });
	  }
	  changeDomain() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.portal.changeDomain');
	  }
	  loadRestrictions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.portal.hasRestrictions');
	  }
	  saveStamp(memberUid, fileId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.saveStamp', {
	      memberUid,
	      fileId
	    });
	  }
	  setupB2eParties(documentUid, representativeId, members) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.setupB2eParties', {
	      documentUid,
	      representativeId,
	      members
	    });
	  }
	  updateChannelTypeToB2eMembers(membersUids, channelType) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.b2e.member.communication.updateMembersChannelType', {
	      members: membersUids,
	      channelType
	    });
	  }
	  loadB2eCompanyList() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.integration.crm.b2ecompany.list');
	  }
	  modifyB2eCompany(documentUid, companyUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyCompany', {
	      documentUid,
	      companyUid
	    });
	  }
	  modifyB2eDocumentScheme(uid, scheme) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.modifyScheme', {
	      uid,
	      scheme
	    });
	  }
	  loadB2eAvaialbleSchemes(documentUid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.b2e.scheme.load', {
	      documentUid
	    });
	  }
	  deleteB2eCompany(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.integration.crm.b2ecompany.delete', {
	      id
	    });
	  }
	  getLinkForSigning(memberId, notifyError = true) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.b2e.member.link.getLinkForSigning', {
	      memberId
	    }, notifyError);
	  }
	  memberLoadReadyForMessageStatus(memberIds) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.send.getMembersForResend', {
	      memberIds
	    });
	  }
	  memberResendMessage(memberIds) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.send.resendMessage', {
	      memberIds
	    });
	  }
	  getBlankById(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.blank.getById', {
	      id
	    });
	  }
	  registerB2eCompany(providerCode, taxId, companyId, externalProviderId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.integration.crm.b2ecompany.register', {
	      providerCode,
	      taxId,
	      companyId,
	      externalProviderId
	    });
	  }
	  setDecisionToSesB2eAgreement() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.b2e.member.communication.setAgreementDecision', {});
	  }
	  createDocumentChat(chatType, documentId, isEntityId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.integration.im.groupChat.createDocumentChat', {
	      chatType,
	      documentId,
	      isEntityId
	    });
	  }
	  getDocumentFillAndStartProgress(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.getFillAndStartProgress', {
	      uid
	    });
	  }
	  getMember(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('sign.api_v1.document.member.get', {
	      uid
	    });
	  }
	}
	function _post2(endpoint, data = null, notifyError = true) {
	  return post(endpoint, data, notifyError);
	}

	exports.Api = Api;
	exports.MemberRole = MemberRole;
	exports.MemberStatus = MemberStatus;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX,BX.UI.Sidepanel.Content));
//# sourceMappingURL=api.bundle.js.map

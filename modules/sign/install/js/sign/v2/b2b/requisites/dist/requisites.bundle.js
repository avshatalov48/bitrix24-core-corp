/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,main_core_events,main_loader,sign_v2_api,sign_v2_helper) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const crmEditorSettings = {
	  entityTypeId: 36,
	  guid: 'sign_entity_editor',
	  params: {
	    ENABLE_CONFIGURATION_UPDATE: 'N',
	    ENABLE_PAGE_TITLE_CONTROLS: false,
	    ENABLE_MODE_TOGGLE: true,
	    IS_EMBEDDED: 'N',
	    forceDefaultConfig: 'Y',
	    enableSingleSectionCombining: 'N'
	  }
	};
	const companyEntity = 'company';
	const contactEntity = 'contact';
	const events = ['onCrmEntityUpdate', 'BX.Crm.EntityEditor:onFailedValidation', 'onCrmEntityUpdateError', 'BX.Crm.EntityEditor:onEntitySaveFailure'];
	var _requisitesNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisitesNode");
	var _initiatorNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initiatorNode");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _editors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editors");
	var _members = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("members");
	var _loadEntityEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadEntityEditor");
	var _showEntityEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showEntityEditor");
	var _getMembersData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMembersData");
	var _saveEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveEditor");
	var _addMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addMembers");
	var _removeMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeMembers");
	var _loadMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadMembers");
	var _getRequisiteData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequisiteData");
	var _toggleCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleCompany");
	var _toggleContact = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleContact");
	var _toggleEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleEntity");
	class Requisites extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _toggleEntity, {
	      value: _toggleEntity2
	    });
	    Object.defineProperty(this, _toggleContact, {
	      value: _toggleContact2
	    });
	    Object.defineProperty(this, _toggleCompany, {
	      value: _toggleCompany2
	    });
	    Object.defineProperty(this, _getRequisiteData, {
	      value: _getRequisiteData2
	    });
	    Object.defineProperty(this, _loadMembers, {
	      value: _loadMembers2
	    });
	    Object.defineProperty(this, _removeMembers, {
	      value: _removeMembers2
	    });
	    Object.defineProperty(this, _addMembers, {
	      value: _addMembers2
	    });
	    Object.defineProperty(this, _saveEditor, {
	      value: _saveEditor2
	    });
	    Object.defineProperty(this, _getMembersData, {
	      value: _getMembersData2
	    });
	    Object.defineProperty(this, _showEntityEditor, {
	      value: _showEntityEditor2
	    });
	    Object.defineProperty(this, _loadEntityEditor, {
	      value: _loadEntityEditor2
	    });
	    Object.defineProperty(this, _requisitesNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initiatorNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _editors, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _members, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.Requisites');
	    babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode] = main_core.Tag.render(_t || (_t = _`
			<div class="sign-wizard__requisites"></div>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode] = main_core.Tag.render(_t2 || (_t2 = _`
			<input type="text" class="ui-ctl-element" />
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    this.documentData = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _members)[_members] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors] = {};
	  }
	  getLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode].value = main_core.Text.encode(this.documentData.initiator);
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode], 'change', ({
	      target
	    }) => {
	      this.emit('changeInitiator', {
	        initiator: target.value
	      });
	    });
	    const preparingNode = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="sign-wizard__preparing">
				<div class="sign-wizard__first-party_responsible">
					<span class="sign-wizard__first-party_responsible-title">
						${0}
						<span data-hint="${0}"></span>
					</span>
					<div class="ui-ctl ui-ctl-textbox sign-wizard__first-party_responsible-name">
						${0}
					</div>
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_PARTY_RESPONSIBLE_TITLE'), main_core.Loc.getMessage('SIGN_PARTY_RESPONSIBLE_HINT'), babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode], babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode]);
	    sign_v2_helper.Hint.create(preparingNode);
	    babelHelpers.classPrivateFieldLooseBase(this, _showEntityEditor)[_showEntityEditor]();
	    return preparingNode;
	  }
	  checkInitiator(initiator) {
	    const parentNode = babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode].parentNode;
	    if (!initiator) {
	      main_core.Dom.addClass(parentNode, 'ui-ctl-warning');
	      babelHelpers.classPrivateFieldLooseBase(this, _initiatorNode)[_initiatorNode].focus();
	      return false;
	    }
	    main_core.Dom.removeClass(parentNode, 'ui-ctl-warning');
	    return true;
	  }
	  async processMembers() {
	    const entityData = await babelHelpers.classPrivateFieldLooseBase(this, _saveEditor)[_saveEditor]();
	    if (!entityData) {
	      return null;
	    }
	    try {
	      const {
	        uid: documentUid = ''
	      } = this.documentData;
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _members)[_members][documentUid]) {
	        await babelHelpers.classPrivateFieldLooseBase(this, _loadMembers)[_loadMembers](documentUid);
	      }
	      const {
	        MYCOMPANY_ID_INFO: {
	          COMPANY_DATA: [companyData]
	        },
	        CLIENT_INFO: {
	          CONTACT_DATA: [contactData]
	        },
	        REQUISITE_BINDING: {
	          REQUISITE_ID: selectedContactRequisite
	        }
	      } = entityData;
	      const companyEntityId = companyData.id;
	      const contactEntityId = contactData.id;
	      const companyRequisiteData = babelHelpers.classPrivateFieldLooseBase(this, _getRequisiteData)[_getRequisiteData](companyData);
	      const contactRequisiteData = babelHelpers.classPrivateFieldLooseBase(this, _getRequisiteData)[_getRequisiteData](contactData, selectedContactRequisite);
	      const documentMembers = babelHelpers.classPrivateFieldLooseBase(this, _members)[_members][documentUid];
	      const itemsForRemove = documentMembers == null ? void 0 : documentMembers.filter((member, index) => {
	        const entityId = member.entityId;
	        if (index === 0) {
	          return entityId !== companyEntityId;
	        }
	        return entityId !== contactEntityId;
	      });
	      const membersData = babelHelpers.classPrivateFieldLooseBase(this, _getMembersData)[_getMembersData](companyData, contactData);
	      const result = {
	        company: {
	          ...membersData[0],
	          ...companyRequisiteData,
	          part: 1
	        },
	        contact: {
	          ...membersData[1],
	          ...contactRequisiteData,
	          part: 2
	        }
	      };
	      if (documentMembers) {
	        if (itemsForRemove.length === 0) {
	          const [company, contact] = documentMembers;
	          result.company.uid = company.uid;
	          result.contact.uid = contact.uid;
	          return result;
	        }
	        await babelHelpers.classPrivateFieldLooseBase(this, _removeMembers)[_removeMembers](itemsForRemove);
	      }
	      const [companyUid, contactUid] = await babelHelpers.classPrivateFieldLooseBase(this, _addMembers)[_addMembers](documentUid, {
	        companyPresetId: companyRequisiteData.presetId,
	        contactPresetId: contactRequisiteData.presetId,
	        companyEntityId,
	        contactEntityId
	      });
	      result.company.uid = companyUid;
	      result.contact.uid = contactUid;
	      return result;
	    } catch {
	      return null;
	    }
	  }
	}
	async function _loadEntityEditor2() {
	  const loader = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode],
	    size: 80
	  });
	  try {
	    var _response$data;
	    loader.show();
	    const response = await main_core.ajax.runAction('crm.api.item.getEditor', {
	      data: {
	        ...crmEditorSettings,
	        id: this.documentData.entityId
	      }
	    });
	    loader.destroy();
	    return (response == null ? void 0 : (_response$data = response.data) == null ? void 0 : _response$data.html) || '';
	  } catch (e) {
	    console.error(e);
	    loader.destroy();
	    return '';
	  }
	}
	async function _showEntityEditor2() {
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode]);
	  const documentId = this.documentData.entityId;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][documentId]) {
	    const editorNode = babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][documentId].getContainer();
	    babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode].appendChild(editorNode);
	  } else {
	    const editorHtml = await babelHelpers.classPrivateFieldLooseBase(this, _loadEntityEditor)[_loadEntityEditor]();
	    await main_core.Runtime.html(babelHelpers.classPrivateFieldLooseBase(this, _requisitesNode)[_requisitesNode], editorHtml);
	    const Editor = main_core.Reflection.getClass('BX.Crm.EntityEditor');
	    babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors] = {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors],
	      [documentId]: Editor.defaultInstance
	    };
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleCompany)[_toggleCompany]();
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleContact)[_toggleContact]();
	}
	function _getMembersData2(companyData, contactData) {
	  return [companyData, contactData].map(party => {
	    var _advancedInfo$multiFi;
	    const {
	      id,
	      title,
	      url,
	      advancedInfo,
	      type
	    } = party;
	    const multiFields = (_advancedInfo$multiFi = advancedInfo == null ? void 0 : advancedInfo.multiFields) != null ? _advancedInfo$multiFi : [];
	    return {
	      id,
	      title,
	      url,
	      type,
	      multiFields
	    };
	  });
	}
	async function _saveEditor2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][this.documentData.entityId]) {
	    return null;
	  }
	  const editor = babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][this.documentData.entityId];
	  const clientControl = editor.getControlById('CLIENT');
	  if (clientControl != null && clientControl.isInViewMode() && !clientControl.hasContentToDisplay()) {
	    return null;
	  }
	  const promise = new Promise(resolve => {
	    const [successFullEvent, ...errorEvents] = events;
	    main_core_events.EventEmitter.subscribeOnce(successFullEvent, event => {
	      const [{
	        entityData
	      }] = event.data;
	      resolve(entityData);
	    });
	    errorEvents.forEach(event => {
	      main_core_events.EventEmitter.subscribeOnce(event, () => resolve(null));
	    });
	  });
	  editor.save();
	  const entityData = await promise;
	  events.forEach(event => main_core_events.EventEmitter.unsubscribeAll(event));
	  return entityData;
	}
	async function _addMembers2(documentUid, entityData) {
	  var _babelHelpers$classPr, _documentMembers$, _documentMembers$2;
	  const {
	    companyEntityId,
	    contactEntityId,
	    companyPresetId,
	    contactPresetId
	  } = entityData;
	  const documentMembers = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _members)[_members][documentUid]) != null ? _babelHelpers$classPr : [];
	  const sameCompany = ((_documentMembers$ = documentMembers[0]) == null ? void 0 : _documentMembers$.entityId) === companyEntityId;
	  const sameContact = ((_documentMembers$2 = documentMembers[1]) == null ? void 0 : _documentMembers$2.entityId) === contactEntityId;
	  const companyPromise = sameCompany ? Promise.resolve({
	    uid: documentMembers[0].uid
	  }) : babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].addMember(documentUid, companyEntity, companyEntityId, 1, companyPresetId);
	  const contactPromise = sameContact ? Promise.resolve({
	    uid: documentMembers[1].uid
	  }) : babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].addMember(documentUid, contactEntity, contactEntityId, 2, contactPresetId);
	  const [companyUid, contactUid] = await Promise.all([companyPromise, contactPromise]);
	  babelHelpers.classPrivateFieldLooseBase(this, _members)[_members] = {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _members)[_members],
	    [documentUid]: [{
	      entityId: companyEntityId,
	      uid: companyUid.uid
	    }, {
	      entityId: contactEntityId,
	      uid: contactUid.uid
	    }]
	  };
	  return [companyUid.uid, contactUid.uid];
	}
	async function _removeMembers2(removeItems = []) {
	  await Promise.all([removeItems.map(removeItem => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].removeMember(removeItem.uid);
	  })]);
	}
	async function _loadMembers2(documentUid) {
	  const membersData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadMembers(documentUid);
	  membersData.forEach(memberData => {
	    var _babelHelpers$classPr2;
	    babelHelpers.classPrivateFieldLooseBase(this, _members)[_members][documentUid] = [...((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _members)[_members][documentUid]) != null ? _babelHelpers$classPr2 : []), {
	      entityId: memberData.entityId,
	      uid: memberData.uid
	    }];
	  });
	}
	function _getRequisiteData2(memberData, selectedRequisiteId = null) {
	  var _requisiteData$find;
	  const {
	    requisiteData
	  } = memberData.advancedInfo;
	  const selectedItem = (_requisiteData$find = requisiteData.find(item => {
	    if (selectedRequisiteId !== null) {
	      return selectedRequisiteId === item.requisiteId;
	    }
	    return item.selected === true;
	  })) != null ? _requisiteData$find : {};
	  const {
	    entityTypeId = 0,
	    presetId = 0
	  } = selectedItem;
	  return {
	    entityTypeId,
	    presetId
	  };
	}
	function _toggleCompany2() {
	  const crmEditor = babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][this.documentData.entityId];
	  const companyField = crmEditor.getControlById('MYCOMPANY_ID');
	  const companySection = crmEditor.getControlById('myCompany');
	  if (!companyField) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleEntity)[_toggleEntity](companyField, companySection);
	}
	function _toggleContact2() {
	  const crmEditor = babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][this.documentData.entityId];
	  const contactsField = crmEditor.getControlById('CLIENT');
	  const clientSection = crmEditor.getControlById('client');
	  if (!contactsField) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleEntity)[_toggleEntity](contactsField, clientSection);
	}
	function _toggleEntity2(field, section) {
	  const switchToSingleEditMode = field.switchToSingleEditMode;
	  const crmEditor = babelHelpers.classPrivateFieldLooseBase(this, _editors)[_editors][this.documentData.entityId];
	  field.isRequired = () => true;
	  field.switchToSingleEditMode = (...args) => {
	    if ((section == null ? void 0 : section.getMode()) === BX.UI.EntityEditorMode.view) {
	      crmEditor.switchControlMode(section, BX.UI.EntityEditorMode.edit);
	    }
	    switchToSingleEditMode.apply(field, args);
	  };
	  const layout = field.layout;
	  field.layout = (...args) => {
	    layout.apply(field, args);
	    main_core.Dom.remove(field._addContactButton);
	  };
	  const switchToViewMode = field.getId() === 'CLIENT' ? field.hasContacts() : field.hasCompanies();
	  if (switchToViewMode && (section == null ? void 0 : section.getMode()) === BX.UI.EntityEditorMode.edit) {
	    crmEditor.switchControlMode(section, BX.UI.EntityEditorMode.view);
	    return;
	  }
	  section == null ? void 0 : section.enableToggling(false);
	  main_core.Dom.remove(field._addContactButton);
	}

	exports.Requisites = Requisites;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Event,BX,BX.Sign.V2,BX.Sign.V2));

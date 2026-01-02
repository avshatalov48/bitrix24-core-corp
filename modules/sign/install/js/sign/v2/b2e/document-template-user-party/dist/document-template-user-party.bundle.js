/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_api,ui_vue3,sign_v2_b2e_userParty,sign_v2_b2e_signSettingsTemplates) {
	'use strict';

	// @vue/component
	const UserPartyApp = {
	  name: 'UserPartyApp',
	  props: {
	    userParty: {
	      /** @type UserParty */
	      type: Object,
	      required: true
	    },
	    region: {
	      type: String,
	      required: true
	    }
	  },
	  mounted() {
	    const userPartyLayout = this.userParty.getLayout(this.region);
	    this.$refs.userPartyContainer.appendChild(userPartyLayout);
	  },
	  template: `
		<div ref="userPartyContainer" class="sign-b2e-user-party-container"></div>
	`
	};

	let _ = t => t,
	  _t;
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _vueApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("vueApp");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _userParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userParty");
	var _store = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("store");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _createApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createApp");
	var _updatePartiesCountInStore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updatePartiesCountInStore");
	var _waitForDepartmentSync = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("waitForDepartmentSync");
	var _syncMembersWithDepartments = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("syncMembersWithDepartments");
	var _sleep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sleep");
	var _getB2eSignersCountLimit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getB2eSignersCountLimit");
	var _getRegion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRegion");
	class DocumentTemplateUserParty {
	  constructor(store) {
	    Object.defineProperty(this, _getRegion, {
	      value: _getRegion2
	    });
	    Object.defineProperty(this, _getB2eSignersCountLimit, {
	      value: _getB2eSignersCountLimit2
	    });
	    Object.defineProperty(this, _sleep, {
	      value: _sleep2
	    });
	    Object.defineProperty(this, _syncMembersWithDepartments, {
	      value: _syncMembersWithDepartments2
	    });
	    Object.defineProperty(this, _waitForDepartmentSync, {
	      value: _waitForDepartmentSync2
	    });
	    Object.defineProperty(this, _updatePartiesCountInStore, {
	      value: _updatePartiesCountInStore2
	    });
	    Object.defineProperty(this, _createApp, {
	      value: _createApp2
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _vueApp, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userParty, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _store, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    const b2eSignersLimitCount = babelHelpers.classPrivateFieldLooseBase(this, _getB2eSignersCountLimit)[_getB2eSignersCountLimit]();
	    const region = babelHelpers.classPrivateFieldLooseBase(this, _getRegion)[_getRegion]();
	    babelHelpers.classPrivateFieldLooseBase(this, _store)[_store] = store;
	    babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty] = new sign_v2_b2e_userParty.UserParty({
	      mode: 'edit',
	      b2eSignersLimitCount,
	      region
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  async syncMembers() {
	    const documentStore = sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore();
	    const storeDocuments = documentStore.createdDocuments;
	    const ids = storeDocuments.map(value => value.document.id);
	    const {
	      shouldCheckDepartmentsSync,
	      documents
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.setupSigners(ids, babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getEntities(), babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].isRejectExcludedEnabled());
	    babelHelpers.classPrivateFieldLooseBase(this, _updatePartiesCountInStore)[_updatePartiesCountInStore](documents); // can rid of this if make syncDepartmentForSigners method
	    if (shouldCheckDepartmentsSync) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _waitForDepartmentSync)[_waitForDepartmentSync]();
	    }
	  }
	  getLayout() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = BX.Tag.render(_t || (_t = _`<div></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _createApp)[_createApp](babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  validate() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].validate()) {
	      return false;
	    }
	    const limit = babelHelpers.classPrivateFieldLooseBase(this, _getB2eSignersCountLimit)[_getB2eSignersCountLimit]();
	    if (limit > 0 && babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].getUniqueUsersCount() > limit) {
	      top.BX.UI.InfoHelper.show('limit_office_e_signature');
	      return false;
	    }
	    return true;
	  }
	  unmount() {
	    var _babelHelpers$classPr;
	    this.closeCounterGuide();
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app]) == null ? void 0 : _babelHelpers$classPr.unmount();
	  }
	  closeCounterGuide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].closeCounterGuide();
	  }
	  isRejectExcludedEnabled() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].isRejectExcludedEnabled();
	  }
	}
	function _createApp2(container) {
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(UserPartyApp, {
	    userParty: babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty],
	    region: babelHelpers.classPrivateFieldLooseBase(this, _getRegion)[_getRegion]()
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].use(babelHelpers.classPrivateFieldLooseBase(this, _store)[_store]);
	  babelHelpers.classPrivateFieldLooseBase(this, _vueApp)[_vueApp] = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(container);
	}
	function _updatePartiesCountInStore2(documents) {
	  sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore().createdDocuments.forEach(templateCreatedDocument => {
	    const storeDocument = templateCreatedDocument.document;
	    const id = storeDocument.id;
	    const document = documents.find(value => value.id === id);
	    if (!document) {
	      throw new Error('Created document not found in update parties documents');
	    }
	    storeDocument.parties = document.parties;
	  });
	}
	async function _waitForDepartmentSync2() {
	  const createdDocuments = sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore().createdDocuments;
	  const syncMemberPromises = createdDocuments.map(value => babelHelpers.classPrivateFieldLooseBase(this, _syncMembersWithDepartments)[_syncMembersWithDepartments](value.document.uid, value.document.parties));
	  await Promise.all(syncMemberPromises);
	}
	async function _syncMembersWithDepartments2(uid, signerParty) {
	  let syncFinished = false;
	  while (!syncFinished) {
	    // eslint-disable-next-line no-await-in-loop
	    const response = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].syncB2eMembersWithDepartments(uid, signerParty, babelHelpers.classPrivateFieldLooseBase(this, _userParty)[_userParty].isRejectExcludedEnabled());
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
	function _getB2eSignersCountLimit2() {
	  return main_core.Extension.getSettings('sign.v2.b2e.document-template-user-party').get('signersLimitCount');
	}
	function _getRegion2() {
	  return main_core.Extension.getSettings('sign.v2.b2e.document-template-user-party').get('region');
	}

	exports.DocumentTemplateUserParty = DocumentTemplateUserParty;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2,BX.Vue3,BX.Sign.V2.B2e,BX.Sign.V2.B2e));
//# sourceMappingURL=document-template-user-party.bundle.js.map

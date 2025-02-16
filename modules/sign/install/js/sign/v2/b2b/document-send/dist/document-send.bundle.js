/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,main_core_events,main_popup,sign_v2_api,sign_v2_helper,sign_v2_langSelector,sign_v2_documentSummary) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	const menuPrefix = 'sign-member-communication';
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _menus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menus");
	var _sendContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendContainer");
	var _documentData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentData");
	var _langContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("langContainer");
	var _config = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("config");
	var _langSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("langSelector");
	var _documentSummary = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentSummary");
	var _attachMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("attachMenu");
	var _formatPhoneNumberForUi = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatPhoneNumberForUi");
	var _highlightCommunicationWithError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightCommunicationWithError");
	var _updatePhoneAttr = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updatePhoneAttr");
	var _showMemberInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMemberInfo");
	var _createParties = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createParties");
	var _updateCommunications = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCommunications");
	var _checkFillAndStartProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkFillAndStartProgress");
	var _sleep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sleep");
	class DocumentSend extends main_core_events.EventEmitter {
	  constructor(config) {
	    super();
	    Object.defineProperty(this, _sleep, {
	      value: _sleep2
	    });
	    Object.defineProperty(this, _checkFillAndStartProgress, {
	      value: _checkFillAndStartProgress2
	    });
	    Object.defineProperty(this, _updateCommunications, {
	      value: _updateCommunications2
	    });
	    Object.defineProperty(this, _createParties, {
	      value: _createParties2
	    });
	    Object.defineProperty(this, _showMemberInfo, {
	      value: _showMemberInfo2
	    });
	    Object.defineProperty(this, _updatePhoneAttr, {
	      value: _updatePhoneAttr2
	    });
	    Object.defineProperty(this, _highlightCommunicationWithError, {
	      value: _highlightCommunicationWithError2
	    });
	    Object.defineProperty(this, _formatPhoneNumberForUi, {
	      value: _formatPhoneNumberForUi2
	    });
	    Object.defineProperty(this, _attachMenu, {
	      value: _attachMenu2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menus, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _sendContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentData, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _langContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _langSelector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentSummary, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.DocumentSend');
	    this.entityData = {};
	    this.communications = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _config)[_config] = config;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _langSelector)[_langSelector] = new sign_v2_langSelector.LangSelector(babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].region, babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].languages);
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSummary)[_documentSummary] = new sign_v2_documentSummary.DocumentSummary({
	      events: {
	        changeTitle: event => {
	          const data = event.getData();
	          babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData].title = data.title;
	          this.emit('changeTitle', data);
	        },
	        showEditor: event => this.emit('showEditor')
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _langContainer)[_langContainer] = main_core.Tag.render(_t || (_t = _`
			<div class="sign-document-send__lang-container">
				${0}
				<span data-hint="${0}"></span>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _langSelector)[_langSelector].getLayout(), main_core.Loc.getMessage('SIGN_DOCUMENT_LANGUAGE_BUTTON_INFO'));
	    sign_v2_helper.Hint.create(babelHelpers.classPrivateFieldLooseBase(this, _langContainer)[_langContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData] = {};
	  }
	  get documentData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData];
	  }
	  set documentData(documentData) {
	    const {
	      uid,
	      title
	    } = documentData;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSummary)[_documentSummary].addItem(uid, {
	      uid,
	      title
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData] = documentData;
	  }
	  getLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _langSelector)[_langSelector].setDocumentUid(babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData].uid);
	    babelHelpers.classPrivateFieldLooseBase(this, _sendContainer)[_sendContainer] = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-document-send">
				<div class="sign-document-send__summary-title-wrapper">
					<p class="sign-document-send__title">
						${0}
					</p>
					${0}
				</div>
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _langContainer)[_langContainer], babelHelpers.classPrivateFieldLooseBase(this, _documentSummary)[_documentSummary].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _createParties)[_createParties]());
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendContainer)[_sendContainer];
	  }
	  resetCommunicationErrors() {
	    const elems = babelHelpers.classPrivateFieldLooseBase(this, _sendContainer)[_sendContainer].querySelectorAll('.sign-document-send__party');
	    elems.forEach(elem => {
	      main_core.Dom.removeClass(elem, '--validation-error');
	    });
	  }
	  async sendForSign() {
	    try {
	      const {
	        communications,
	        entityData
	      } = this;
	      const entries = Object.entries(communications);
	      let allowToComplete = true;
	      const restrictions = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadRestrictions();
	      for (const [entityType, item] of entries) {
	        const {
	          type,
	          value
	        } = item;
	        const {
	          uid: memberUid
	        } = entityData[entityType];
	        if (!restrictions.smsAllowed && type === 'PHONE') {
	          top.BX.UI.InfoHelper.show('limit_crm_sign_messenger_identification');
	          allowToComplete = false;
	          continue;
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyCommunicationChannel(memberUid, type, value);
	      }
	      const {
	        uid: documentUid,
	        initiator
	      } = babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData];
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyInitiator(documentUid, initiator);
	      if (!allowToComplete) {
	        return false;
	      }
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].configureDocument(documentUid);
	      await babelHelpers.classPrivateFieldLooseBase(this, _checkFillAndStartProgress)[_checkFillAndStartProgress](documentUid);
	      return true;
	    } catch (e) {
	      if (main_core.Type.isArrayFilled(e == null ? void 0 : e.errors)) {
	        const firstError = e.errors[0];
	        e.errors.forEach(error => {
	          var _error$customData;
	          if ((error == null ? void 0 : error.code) === firstError.code && ((error == null ? void 0 : error.code) === 'MEMBER_INVALID_PHONE' || (error == null ? void 0 : error.code) === 'MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE') && main_core.Type.isStringFilled(error == null ? void 0 : (_error$customData = error.customData) == null ? void 0 : _error$customData.phone)) {
	            babelHelpers.classPrivateFieldLooseBase(this, _highlightCommunicationWithError)[_highlightCommunicationWithError](error.customData.phone);
	          }
	        });
	      }
	      return false;
	    }
	  }
	  setDocumentsBlock(documents) {
	    const documentsObject = Object.fromEntries(documents);
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSummary)[_documentSummary].setItems(documentsObject);
	  }
	}
	function _attachMenu2(idMeans, entityData) {
	  let menuItems = [];
	  const menuId = `${menuPrefix}-${entityData.entityTypeId}-${entityData.id}`;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId]) {
	    let items = babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].getMenuItems();
	    const swap = (array, from, to) => {
	      const tmp = array[to];
	      // eslint-disable-next-line no-param-reassign
	      array[to] = array[from];
	      // eslint-disable-next-line no-param-reassign
	      array[from] = tmp;
	    };
	    while (items.length > 1) {
	      if (items[0].id === 'show-member') {
	        swap(items, 0, 1);
	        continue;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].removeMenuItem(items[0].id);
	      items = babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].getMenuItems();
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadCommunications(entityData.uid).then(async multiFields => {
	    var _selectedCommunicatio2, _selectedCommunicatio3, _selectedCommunicatio4, _selectedCommunicatio5, _selectedCommunicatio6;
	    let selectedCommunication = {};
	    const restrictions = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadRestrictions();
	    const mapper = communication => {
	      var _selectedCommunicatio;
	      let text = communication.VALUE;
	      if ((communication == null ? void 0 : communication.TYPE) === 'PHONE' && BX.PhoneNumberParser) {
	        BX.PhoneNumberParser.getInstance().parse(communication.VALUE).then(parsedNumber => {
	          text = parsedNumber.format(BX.PhoneNumber.Format.INTERNATIONAL);
	        });
	      }
	      if ((communication == null ? void 0 : communication.TYPE) === 'PHONE' && restrictions.smsAllowed && ((_selectedCommunicatio = selectedCommunication) == null ? void 0 : _selectedCommunicatio.TYPE) !== 'PHONE' || (communication == null ? void 0 : communication.TYPE) === 'EMAIL' && Object.keys(selectedCommunication).length === 0) {
	        selectedCommunication = communication;
	      }
	      return {
	        text,
	        onclick: ({
	          target
	        }) => {
	          babelHelpers.classPrivateFieldLooseBase(this, _updateCommunications)[_updateCommunications](entityData, communication);
	          // eslint-disable-next-line no-param-reassign
	          idMeans.firstElementChild.textContent = text;
	          babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].close();
	          babelHelpers.classPrivateFieldLooseBase(this, _updatePhoneAttr)[_updatePhoneAttr](idMeans, (communication == null ? void 0 : communication.TYPE) === 'PHONE' ? communication.VALUE : null);
	        }
	      };
	    };
	    menuItems = [
	    // eslint-disable-next-line no-unsafe-optional-chaining
	    ...(multiFields != null && multiFields.EMAIL ? multiFields == null ? void 0 : multiFields.EMAIL.map(element => mapper(element)) : []),
	    // eslint-disable-next-line no-unsafe-optional-chaining
	    ...(multiFields != null && multiFields.PHONE ? await Promise.all(multiFields == null ? void 0 : multiFields.PHONE.map(async element => {
	      const item = mapper(element);
	      item.text = await babelHelpers.classPrivateFieldLooseBase(this, _formatPhoneNumberForUi)[_formatPhoneNumberForUi](item.text);
	      return item;
	    })) : [])];
	    menuItems.map(item => babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].addMenuItem(item, null));
	    babelHelpers.classPrivateFieldLooseBase(this, _updateCommunications)[_updateCommunications](entityData, selectedCommunication);
	    idMeans.firstElementChild.textContent = ((_selectedCommunicatio2 = selectedCommunication) == null ? void 0 : _selectedCommunicatio2.TYPE) === 'PHONE' ? await babelHelpers.classPrivateFieldLooseBase(this, _formatPhoneNumberForUi)[_formatPhoneNumberForUi]((_selectedCommunicatio3 = selectedCommunication) == null ? void 0 : _selectedCommunicatio3.VALUE) : (_selectedCommunicatio4 = selectedCommunication) == null ? void 0 : _selectedCommunicatio4.VALUE;
	    babelHelpers.classPrivateFieldLooseBase(this, _updatePhoneAttr)[_updatePhoneAttr](idMeans, ((_selectedCommunicatio5 = selectedCommunication) == null ? void 0 : _selectedCommunicatio5.TYPE) === 'PHONE' ? (_selectedCommunicatio6 = selectedCommunication) == null ? void 0 : _selectedCommunicatio6.VALUE : null);
	  }).catch(() => {});
	  menuItems.push({
	    id: 'show-member',
	    text: main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_OPEN_VIEW'),
	    onclick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _showMemberInfo)[_showMemberInfo](idMeans, entityData);
	      babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].close();
	    }
	  });
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId] = main_popup.MenuManager.create({
	      id: menuId,
	      items: menuItems
	    });
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId].getPopupWindow();
	    popup.setBindElement(idMeans);
	  }

	  // eslint-disable-next-line no-param-reassign
	  idMeans.firstElementChild.textContent = menuItems[0].text;
	  return babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus][menuId];
	}
	async function _formatPhoneNumberForUi2(phone) {
	  let phoneFormatted = phone;
	  if (BX.PhoneNumberParser) {
	    phoneFormatted = await BX.PhoneNumberParser.getInstance().parse(phone).then(parsedNumber => {
	      return parsedNumber.format(BX.PhoneNumber.Format.INTERNATIONAL);
	    });
	  }
	  return phoneFormatted;
	}
	function _highlightCommunicationWithError2(phoneNumber) {
	  const selectors = [`.sign-document-send__party_id-means[data-phone-source="${phoneNumber}"]`, `.sign-document-send__party_id-means[data-phone-normalized="${phoneNumber}"]`];
	  selectors.forEach(selector => {
	    const aIdMeans = babelHelpers.classPrivateFieldLooseBase(this, _sendContainer)[_sendContainer].querySelectorAll(selector);
	    aIdMeans.forEach(elem => {
	      const wrapper = elem.closest('.sign-document-send__party');
	      main_core.Dom.addClass(wrapper, '--validation-error');
	    });
	  });
	}
	async function _updatePhoneAttr2(elem, phone = null) {
	  const normalized = await BX.PhoneNumberParser.getInstance().parse(phone).then(parsedNumber => {
	    return parsedNumber.format(BX.PhoneNumber.Format.E164);
	  });
	  if (main_core.Type.isStringFilled(phone)) {
	    main_core.Dom.attr(elem, 'data-phone-source', phone);
	    main_core.Dom.attr(elem, 'data-phone-normalized', normalized);
	  } else {
	    elem.removeAttribute('data-phone-source');
	    elem.removeAttribute('data-phone-normalized');
	  }
	}
	function _showMemberInfo2(idMeans, entityData) {
	  const {
	    Instance: slider
	  } = main_core.Reflection.getClass('BX.SidePanel');
	  slider.open(entityData.url, {
	    cacheable: false,
	    allowChangeHistory: false,
	    events: {
	      onClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _attachMenu)[_attachMenu](idMeans, entityData);
	      }
	    }
	  });
	}
	function _createParties2() {
	  const parties = [{
	    partyTitle: main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY'),
	    entityData: this.entityData.company
	  }, {
	    partyTitle: main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_PARTNER'),
	    entityData: this.entityData.contact
	  }];
	  Object.keys(main_popup.MenuManager.Data).forEach(menuId => {
	    if (menuId.includes(menuPrefix)) {
	      main_popup.MenuManager.destroy(menuId);
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _menus)[_menus] = [];
	  return parties.map(party => {
	    const {
	      partyTitle,
	      entityData
	    } = party;
	    const {
	      title
	    } = entityData;
	    const idMeans = main_core.Tag.render(_t3 || (_t3 = _`
				<span
					class="sign-document-send__party_id-means"
					onclick="${0}"
				>
					<span></span>
				</span>
			`), () => menu.show());
	    const menu = babelHelpers.classPrivateFieldLooseBase(this, _attachMenu)[_attachMenu](idMeans, entityData);
	    return main_core.Tag.render(_t4 || (_t4 = _`
				<div class="sign-document-send__party">
					<div class="sign-document-send__party_summary">
						<p class="sign-document-send__party_title">${0}</p>
						<span class="sign-document-send__party_member-name">
							${0}
						</span>
						<span class="sign-document-send__party_status">
							${0}
						</span>
					</div>
					<div class="sign-document-send__party_id">
						<p class="sign-document-send__party_title">
							${0}
						</p>
						${0}
					</div>
				</div>
			`), partyTitle, main_core.Text.encode(title), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_NOT_SIGNED'), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_PARTY_ID'), idMeans);
	  });
	}
	function _updateCommunications2(entityData, communication) {
	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	  if (typeof communication === 'undefined') {
	    return;
	  }
	  const {
	    TYPE: type,
	    VALUE: value
	  } = communication;
	  this.communications = {
	    ...this.communications,
	    [entityData.type]: {
	      type,
	      value
	    }
	  };
	  this.resetCommunicationErrors();
	}
	async function _checkFillAndStartProgress2(uid) {
	  let completed = false;
	  while (!completed) {
	    // eslint-disable-next-line no-await-in-loop
	    const result = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getDocumentFillAndStartProgress(uid);
	    completed = result.completed;
	    if (!completed) {
	      // eslint-disable-next-line no-await-in-loop
	      await babelHelpers.classPrivateFieldLooseBase(this, _sleep)[_sleep](1000);
	    }
	  }
	}
	function _sleep2(ms) {
	  return new Promise(resolve => {
	    setTimeout(resolve, ms);
	  });
	}

	exports.DocumentSend = DocumentSend;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Event,BX.Main,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=document-send.bundle.js.map

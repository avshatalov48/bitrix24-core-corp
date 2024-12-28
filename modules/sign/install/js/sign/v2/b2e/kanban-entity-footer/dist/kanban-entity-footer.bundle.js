/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_events,sign_v2_b2e_signCancellation,sign_v2_b2e_signLink,crm_router) {
	'use strict';

	const SIGN_BUTTON_TYPE = 'sign';
	const EDIT_BUTTON_TYPE = 'edit';
	const REVIEW_BUTTON_TYPE = 'review';
	const PREVIEW_BUTTON_TYPE = 'preview';
	const MODIFY_BUTTON_TYPE = 'modify';
	const CANCEL_BUTTON_TYPE = 'cancel';
	const PROCESS_BUTTON_TYPE = 'process';
	var _buttons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttons");
	var _isUserCanSign = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanSign");
	var _isUserCanEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanEdit");
	var _isUserCanReview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanReview");
	var _isUserCanCancel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanCancel");
	var _isUserCanModify = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanModify");
	var _isUserCanPreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanPreview");
	var _isUserCanProcess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUserCanProcess");
	var _memberId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("memberId");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _onBeforeAsideCreate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeAsideCreate");
	var _onBeforeFooterCreate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeFooterCreate");
	var _setData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setData");
	var _createLastActivityBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createLastActivityBlock");
	var _createButtonsBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createButtonsBlock");
	var _createButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createButton");
	var _signShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("signShow");
	var _signCancel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("signCancel");
	var _modifyDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("modifyDocument");
	var _previewDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previewDocument");
	var _showDocumentProcess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showDocumentProcess");
	class KanbanEntityFooter {
	  constructor() {
	    Object.defineProperty(this, _showDocumentProcess, {
	      value: _showDocumentProcess2
	    });
	    Object.defineProperty(this, _previewDocument, {
	      value: _previewDocument2
	    });
	    Object.defineProperty(this, _modifyDocument, {
	      value: _modifyDocument2
	    });
	    Object.defineProperty(this, _signCancel, {
	      value: _signCancel2
	    });
	    Object.defineProperty(this, _signShow, {
	      value: _signShow2
	    });
	    Object.defineProperty(this, _createButton, {
	      value: _createButton2
	    });
	    Object.defineProperty(this, _createButtonsBlock, {
	      value: _createButtonsBlock2
	    });
	    Object.defineProperty(this, _createLastActivityBlock, {
	      value: _createLastActivityBlock2
	    });
	    Object.defineProperty(this, _setData, {
	      value: _setData2
	    });
	    Object.defineProperty(this, _onBeforeFooterCreate, {
	      value: _onBeforeFooterCreate2
	    });
	    Object.defineProperty(this, _onBeforeAsideCreate, {
	      value: _onBeforeAsideCreate2
	    });
	    Object.defineProperty(this, _buttons, {
	      writable: true,
	      value: {
	        sign: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_SIGN_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-sign-document']
	        },
	        edit: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_EDIT_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-edit-document']
	        },
	        review: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_REVIEW_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-review-document']
	        },
	        cancel: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_CANCEL_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-cancel', 'sign-b2e-cancel-document']
	        },
	        preview: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_PREVIEW_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-view', 'sign-b2e-preview-document']
	        },
	        modify: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_MODIFY_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-fill', 'sign-b2e-modify-document']
	        },
	        process: {
	          title: main_core.Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_PROCESS_TITLE'),
	          classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-fill', 'sign-b2e-process-document']
	        }
	      }
	    });
	    Object.defineProperty(this, _isUserCanSign, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanEdit, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanReview, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanCancel, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanModify, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanPreview, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isUserCanProcess, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _memberId, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: 0
	    });
	  }
	  init() {
	    main_core_events.EventEmitter.subscribe('BX.Crm.Kanban.Item::onBeforeFooterCreate', event => babelHelpers.classPrivateFieldLooseBase(this, _onBeforeFooterCreate)[_onBeforeFooterCreate](event));
	    main_core_events.EventEmitter.subscribe('BX.Crm.Kanban.Item::onBeforeAsideCreate', event => babelHelpers.classPrivateFieldLooseBase(this, _onBeforeAsideCreate)[_onBeforeAsideCreate](event));
	  }
	}
	function _onBeforeAsideCreate2(event) {
	  const data = event.getData();
	  data.elements = [];
	}
	function _onBeforeFooterCreate2(event) {
	  const data = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _setData)[_setData](data.item.data);
	  data.elements = [];
	  data.elements.push({
	    id: 'lastActivityBlockItem',
	    node: babelHelpers.classPrivateFieldLooseBase(this, _createLastActivityBlock)[_createLastActivityBlock](data.item.lastActivityTime, data.item.lastActivityBy)
	  }, {
	    id: 'buttonsBlock',
	    node: babelHelpers.classPrivateFieldLooseBase(this, _createButtonsBlock)[_createButtonsBlock]()
	  });
	}
	function _setData2(itemData) {
	  var _itemData$isUserCanSi, _itemData$isUserCanRe, _itemData$isUserCanEd, _itemData$isUserCanMo, _itemData$isUserCanPr, _itemData$isUserCanCa, _itemData$isUserCanPr2, _itemData$documentUid, _itemData$entityId, _itemData$memberId;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanSign)[_isUserCanSign] = (_itemData$isUserCanSi = itemData == null ? void 0 : itemData.isUserCanSign) != null ? _itemData$isUserCanSi : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanReview)[_isUserCanReview] = (_itemData$isUserCanRe = itemData == null ? void 0 : itemData.isUserCanReview) != null ? _itemData$isUserCanRe : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanEdit)[_isUserCanEdit] = (_itemData$isUserCanEd = itemData == null ? void 0 : itemData.isUserCanEdit) != null ? _itemData$isUserCanEd : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanModify)[_isUserCanModify] = (_itemData$isUserCanMo = itemData == null ? void 0 : itemData.isUserCanModify) != null ? _itemData$isUserCanMo : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanPreview)[_isUserCanPreview] = (_itemData$isUserCanPr = itemData == null ? void 0 : itemData.isUserCanPreview) != null ? _itemData$isUserCanPr : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanCancel)[_isUserCanCancel] = (_itemData$isUserCanCa = itemData == null ? void 0 : itemData.isUserCanCancel) != null ? _itemData$isUserCanCa : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _isUserCanProcess)[_isUserCanProcess] = (_itemData$isUserCanPr2 = itemData == null ? void 0 : itemData.isUserCanProcess) != null ? _itemData$isUserCanPr2 : false;
	  babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = (_itemData$documentUid = itemData == null ? void 0 : itemData.documentUid) != null ? _itemData$documentUid : '';
	  babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = (_itemData$entityId = itemData == null ? void 0 : itemData.entityId) != null ? _itemData$entityId : '';
	  babelHelpers.classPrivateFieldLooseBase(this, _memberId)[_memberId] = (_itemData$memberId = itemData == null ? void 0 : itemData.memberId) != null ? _itemData$memberId : 0;
	}
	function _createLastActivityBlock2(lastActivityBy, lastActivityTime) {
	  const lastActivityBlock = main_core.Dom.create('div');
	  main_core.Dom.addClass(lastActivityBlock, 'crm-kanban-item-last-activity');
	  main_core.Dom.append(lastActivityBy, lastActivityBlock);
	  main_core.Dom.append(lastActivityTime, lastActivityBlock);
	  return lastActivityBlock;
	}
	function _createButtonsBlock2() {
	  const buttonsBlock = main_core.Dom.create('div');
	  main_core.Dom.addClass(buttonsBlock, 'sign-b2e-buttons-block');
	  let buttonType = '';
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanSign)[_isUserCanSign]) {
	    buttonType = SIGN_BUTTON_TYPE;
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanEdit)[_isUserCanEdit]) {
	    buttonType = EDIT_BUTTON_TYPE;
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanReview)[_isUserCanReview]) {
	    buttonType = REVIEW_BUTTON_TYPE;
	  }
	  let isShowDelimiter = false;
	  if (buttonType && babelHelpers.classPrivateFieldLooseBase(this, _memberId)[_memberId] > 0) {
	    const memberId = babelHelpers.classPrivateFieldLooseBase(this, _memberId)[_memberId];
	    const signBlock = babelHelpers.classPrivateFieldLooseBase(this, _createButton)[_createButton](buttonType, () => babelHelpers.classPrivateFieldLooseBase(this, _signShow)[_signShow](memberId));
	    main_core.Dom.append(signBlock, buttonsBlock);
	    isShowDelimiter = true;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanCancel)[_isUserCanCancel] && babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid]) {
	    const documentUid = babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid];
	    const cancelBlock = babelHelpers.classPrivateFieldLooseBase(this, _createButton)[_createButton](CANCEL_BUTTON_TYPE, () => babelHelpers.classPrivateFieldLooseBase(this, _signCancel)[_signCancel](documentUid));
	    main_core.Dom.append(cancelBlock, buttonsBlock);
	    isShowDelimiter = true;
	  }
	  const entityId = babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanModify)[_isUserCanModify] && entityId) {
	    const modifyBlock = babelHelpers.classPrivateFieldLooseBase(this, _createButton)[_createButton](MODIFY_BUTTON_TYPE, () => babelHelpers.classPrivateFieldLooseBase(this, _modifyDocument)[_modifyDocument](entityId));
	    main_core.Dom.append(modifyBlock, buttonsBlock);
	    isShowDelimiter = true;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanPreview)[_isUserCanPreview] && entityId) {
	    if (isShowDelimiter === true) {
	      const buttonDelimiterBlock = main_core.Dom.create('div');
	      main_core.Dom.addClass(buttonDelimiterBlock, 'sign-b2e-ui-button-delimiter');
	      main_core.Dom.append(buttonDelimiterBlock, buttonsBlock);
	    }
	    const previewBlock = babelHelpers.classPrivateFieldLooseBase(this, _createButton)[_createButton](PREVIEW_BUTTON_TYPE, () => babelHelpers.classPrivateFieldLooseBase(this, _previewDocument)[_previewDocument](entityId));
	    main_core.Dom.append(previewBlock, buttonsBlock);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isUserCanProcess)[_isUserCanProcess] && entityId) {
	    const processBlock = babelHelpers.classPrivateFieldLooseBase(this, _createButton)[_createButton](PROCESS_BUTTON_TYPE, () => babelHelpers.classPrivateFieldLooseBase(this, _showDocumentProcess)[_showDocumentProcess](entityId));
	    main_core.Dom.append(processBlock, buttonsBlock);
	  }
	  return buttonsBlock;
	}
	function _createButton2(type, callback) {
	  const buttonBlock = main_core.Dom.create('div');
	  main_core.Event.bind(buttonBlock, 'click', event => {
	    callback();
	    event.stopPropagation();
	  });
	  main_core.Dom.addClass(buttonBlock, babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][type].classes);
	  main_core.Dom.attr(buttonBlock, 'title', babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][type].title);
	  return buttonBlock;
	}
	function _signShow2(memberId) {
	  const signLink = new sign_v2_b2e_signLink.SignLink({
	    memberId
	  });
	  signLink.openSlider();
	}
	function _signCancel2(documentUid) {
	  const signCancellation = new sign_v2_b2e_signCancellation.SignCancellation();
	  signCancellation.cancelWithConfirm(documentUid);
	}
	function _modifyDocument2(entityId) {
	  return crm_router.Router.openSlider(`/sign/b2e/doc/0/?docId=${entityId}&stepId=changePartner&noRedirect=Y`, {
	    width: 1250
	  });
	}
	function _previewDocument2(entityId) {
	  return crm_router.Router.openSlider(`/sign/b2e/preview/0/?docId=${entityId}&noRedirect=Y`);
	}
	function _showDocumentProcess2(entityId) {
	  return crm_router.Router.openSlider(`/bitrix/components/bitrix/sign.document.list/slider.php?type=document&entity_id=${entityId}&apply_filter=N`);
	}

	exports.KanbanEntityFooter = KanbanEntityFooter;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Event,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Crm));
//# sourceMappingURL=kanban-entity-footer.bundle.js.map

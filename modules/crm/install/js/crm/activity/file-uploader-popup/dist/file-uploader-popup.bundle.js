this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_popup,ui_buttons,crm_activity_fileUploader) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	const SAVE_BUTTON_ID = 'save';
	const CANCEL_BUTTON_ID = 'cancel';
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _files = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("files");
	var _ownerTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ownerTypeId");
	var _ownerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ownerId");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _fileUploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileUploader");
	var _updateFiles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFiles");
	var _revertButtonsState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("revertButtonsState");
	var _closePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closePopup");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _getPopupTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupTitle");
	var _getPopupButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupButtons");
	var _changeUploaderContainerSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeUploaderContainerSize");
	class FileUploaderPopup {
	  constructor(params) {
	    Object.defineProperty(this, _changeUploaderContainerSize, {
	      value: _changeUploaderContainerSize2
	    });
	    Object.defineProperty(this, _getPopupButtons, {
	      value: _getPopupButtons2
	    });
	    Object.defineProperty(this, _getPopupTitle, {
	      value: _getPopupTitle2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _closePopup, {
	      value: _closePopup2
	    });
	    Object.defineProperty(this, _revertButtonsState, {
	      value: _revertButtonsState2
	    });
	    Object.defineProperty(this, _updateFiles, {
	      value: _updateFiles2
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _files, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _ownerTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _ownerId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _fileUploader, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = params.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _files)[_files] = main_core.Type.isArrayFilled(params.files) ? params.files : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId] = params.ownerTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId] = params.ownerId;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      const htmlStyles = getComputedStyle(document.documentElement);
	      const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
	      const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
	      const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	        className: 'crm-activity__file-uploader-popup',
	        closeIcon: true,
	        closeByEsc: true,
	        padding: popupPaddingNumberValue,
	        overlay: {
	          opacity: 40,
	          backgroundColor: popupOverlayColor
	        },
	        cacheable: false,
	        content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	        buttons: babelHelpers.classPrivateFieldLooseBase(this, _getPopupButtons)[_getPopupButtons](),
	        minWidth: 650,
	        width: 650,
	        maxHeight: 650
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	}
	function _updateFiles2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr2 = _babelHelpers$classPr.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr2.setState(ui_buttons.ButtonState.WAITING);
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr4.setState(ui_buttons.ButtonState.DISABLED);
	  main_core.ajax.runAction('crm.activity.todo.updateFiles', {
	    data: {
	      ownerTypeId: babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId],
	      ownerId: babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId],
	      id: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	      fileTokens: babelHelpers.classPrivateFieldLooseBase(this, _fileUploader)[_fileUploader] ? babelHelpers.classPrivateFieldLooseBase(this, _fileUploader)[_fileUploader].getServerFileIds() : []
	    }
	  }).then(result => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	    if (!(result.hasOwnProperty('errors') && result.errors.length)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	    }
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	  });
	}
	function _revertButtonsState2() {
	  var _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8;
	  (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr6 = _babelHelpers$classPr5.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr6.setState(null);
	  (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr8 = _babelHelpers$classPr7.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr8.setState(null);
	}
	function _closePopup2() {
	  var _babelHelpers$classPr9;
	  (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr9.close();
	}
	function _getPopupContent2() {
	  const uploaderContainer = main_core.Tag.render(_t || (_t = _`<div></div>`));
	  const content = main_core.Tag.render(_t2 || (_t2 = _`<div class="crm-activity__file-uploader">
			<div class="crm-activity__file-uploader_title">${0}</div>
			<div class="crm-activity__file-uploader_content">
				${0}
			</div>
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _getPopupTitle)[_getPopupTitle](), uploaderContainer);
	  babelHelpers.classPrivateFieldLooseBase(this, _fileUploader)[_fileUploader] = new crm_activity_fileUploader.FileUploader({
	    events: {
	      'File:onComplete': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	      },
	      'File:onRemove': event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _changeUploaderContainerSize)[_changeUploaderContainerSize]();
	        babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	      },
	      'onUploadStart': event => {
	        var _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12, _babelHelpers$classPr13;
	        babelHelpers.classPrivateFieldLooseBase(this, _changeUploaderContainerSize)[_changeUploaderContainerSize]();
	        (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr11 = _babelHelpers$classPr10.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr11.setState(ui_buttons.ButtonState.DISABLED);
	        (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr13 = _babelHelpers$classPr12.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr13.setState(ui_buttons.ButtonState.DISABLED);
	      }
	      // TODO: not implemented yet
	      //		'onUploadComplete'
	    },

	    ownerId: babelHelpers.classPrivateFieldLooseBase(this, _ownerId)[_ownerId],
	    ownerTypeId: babelHelpers.classPrivateFieldLooseBase(this, _ownerTypeId)[_ownerTypeId],
	    activityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	    files: babelHelpers.classPrivateFieldLooseBase(this, _files)[_files]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _fileUploader)[_fileUploader].renderTo(uploaderContainer);
	  return content;
	}
	function _getPopupTitle2() {
	  return main_core.Loc.getMessage('CRM_FILE_UPLOADER_POPUP_TITLE_2');
	}
	function _getPopupButtons2() {
	  return [new ui_buttons.SaveButton({
	    id: SAVE_BUTTON_ID,
	    round: true,
	    state: ui_buttons.ButtonState.DISABLED,
	    events: {
	      click: babelHelpers.classPrivateFieldLooseBase(this, _updateFiles)[_updateFiles].bind(this)
	    }
	  }), new ui_buttons.CancelButton({
	    id: CANCEL_BUTTON_ID,
	    round: true,
	    events: {
	      click: babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup].bind(this)
	    },
	    text: main_core.Loc.getMessage('CRM_FILE_UPLOADER_POPUP_CANCEL'),
	    color: ui_buttons.ButtonColor.LIGHT_BORDER
	  })];
	}
	function _changeUploaderContainerSize2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].adjustPosition();
	  }
	}

	exports.FileUploaderPopup = FileUploaderPopup;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Main,BX.UI,BX.Crm.Activity));
//# sourceMappingURL=file-uploader-popup.bundle.js.map

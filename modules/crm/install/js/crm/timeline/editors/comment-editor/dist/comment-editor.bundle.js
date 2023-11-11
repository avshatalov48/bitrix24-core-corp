/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Timeline = this.BX.Crm.Timeline || {};
(function (exports,main_core,main_loader,ui_notification) {
	'use strict';

	var _commentId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commentId");
	var _editorName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editorName");
	var _editorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editorContainer");
	var _editor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editor");
	var _postForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("postForm");
	var _commentMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commentMessage");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _onEditorHtmlLoad = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEditorHtmlLoad");
	var _onRunRequestError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onRunRequestError");
	var _showEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showEditor");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	class CommentEditor {
	  constructor(commentId) {
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _showEditor, {
	      value: _showEditor2
	    });
	    Object.defineProperty(this, _onRunRequestError, {
	      value: _onRunRequestError2
	    });
	    Object.defineProperty(this, _onEditorHtmlLoad, {
	      value: _onEditorHtmlLoad2
	    });
	    Object.defineProperty(this, _commentId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _editorName, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _editorContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _editor, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _postForm, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _commentMessage, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    if (commentId <= 0) {
	      throw new Error('Comment ID must be specified');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _commentId)[_commentId] = main_core.Text.toInteger(commentId);
	    babelHelpers.classPrivateFieldLooseBase(this, _editorName)[_editorName] = 'CrmTimeLineComment' + babelHelpers.classPrivateFieldLooseBase(this, _commentId)[_commentId] + BX.util.getRandomString(4);
	  }
	  show(editorContainer) {
	    babelHelpers.classPrivateFieldLooseBase(this, _editorContainer)[_editorContainer] = main_core.Type.isDomNode(editorContainer) ? editorContainer : null;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _editorContainer)[_editorContainer]) {
	      throw new Error('Editor container must be specified');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.SetContent(babelHelpers.classPrivateFieldLooseBase(this, _commentMessage)[_commentMessage]);
	      babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].ReInitIframe();
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader](true);
	    main_core.ajax.runAction('crm.api.timeline.loadEditor', {
	      data: {
	        id: babelHelpers.classPrivateFieldLooseBase(this, _commentId)[_commentId],
	        name: babelHelpers.classPrivateFieldLooseBase(this, _editorName)[_editorName]
	      }
	    }).then(result => {
	      const assets = result.data.assets;
	      const assetsToLoad = [...(assets.hasOwnProperty('css') ? assets.css : []), ...(assets.hasOwnProperty('js') ? assets.js : [])];
	      BX.load(assetsToLoad, () => {
	        if (assets.hasOwnProperty('string')) {
	          Promise.all(assets.string.map(stringValue => main_core.Runtime.html(null, stringValue))).then(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _onEditorHtmlLoad)[_onEditorHtmlLoad](result);
	          });
	        } else {
	          babelHelpers.classPrivateFieldLooseBase(this, _onEditorHtmlLoad)[_onEditorHtmlLoad](result);
	        }
	      });
	    }).catch(result => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onRunRequestError)[_onRunRequestError](result);
	    });
	  }
	  getContent() {
	    let content = '';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm]) {
	      content = babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.GetContent().trim();
	      babelHelpers.classPrivateFieldLooseBase(this, _commentMessage)[_commentMessage] = content;
	    }
	    if (!main_core.Type.isStringFilled(content)) {
	      ui_notification.UI.Notification.Center.notify({
	        content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE')
	      });
	    }
	    return content;
	  }
	  getHtmlContent() {
	    let content = '';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm]) {
	      content = babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.currentViewName === 'wysiwyg' ? babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.iframeView.GetValue() : babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.content;
	    }
	    return content;
	  }
	  getAttachments() {
	    let attachmentList = [];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].eventNode.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]').forEach(input => attachmentList.push(input.value));
	    }
	    return attachmentList;
	  }
	}
	function _onEditorHtmlLoad2(result) {
	  if (main_core.Type.isObject(result) && main_core.Type.isObject(result.data) && main_core.Type.isStringFilled(result.data.html)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader](false);
	    main_core.Runtime.html(babelHelpers.classPrivateFieldLooseBase(this, _editorContainer)[_editorContainer], result.data.html).then(() => {
	      if (LHEPostForm) {
	        setTimeout(babelHelpers.classPrivateFieldLooseBase(this, _showEditor)[_showEditor].bind(this), 0);
	      }
	    });
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _onRunRequestError)[_onRunRequestError](result);
	  }
	}
	function _onRunRequestError2(result) {
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader](false);
	  if (main_core.Type.isObject(result) && main_core.Type.isArray(result.errors) && result.errors.length > 0) {
	    ui_notification.UI.Notification.Center.notify({
	      content: result.errors[0].message,
	      autoHideDelay: 5000
	    });
	  }
	  if (result.status !== 'success') {
	    throw new Error('Unable to load editor component');
	  }
	}
	function _showEditor2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm] = LHEPostForm.getHandler(babelHelpers.classPrivateFieldLooseBase(this, _editorName)[_editorName]);
	  babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor] = BXHtmlEditor.Get(babelHelpers.classPrivateFieldLooseBase(this, _editorName)[_editorName]);
	  BX.onCustomEvent(babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].eventNode, 'OnShowLHE', [true]);
	  babelHelpers.classPrivateFieldLooseBase(this, _commentMessage)[_commentMessage] = babelHelpers.classPrivateFieldLooseBase(this, _postForm)[_postForm].oEditor.GetContent();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dom) {
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dom.textareaCont.style.opacity = 1;
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].dom.iframeCont.style.opacity = 1;
	  }
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor].Focus(true);
	  }, 100);
	}
	function _showLoader2(showLoader) {
	  if (showLoader) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] && main_loader.Loader) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	        size: 45,
	        offset: {
	          top: '1%'
	        }
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show(babelHelpers.classPrivateFieldLooseBase(this, _editorContainer)[_editorContainer]);
	  } else {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] && main_loader.Loader) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].hide();
	    }
	  }
	}

	exports.CommentEditor = CommentEditor;

}((this.BX.Crm.Timeline.Editors = this.BX.Crm.Timeline.Editors || {}),BX,BX,BX));
//# sourceMappingURL=comment-editor.bundle.js.map

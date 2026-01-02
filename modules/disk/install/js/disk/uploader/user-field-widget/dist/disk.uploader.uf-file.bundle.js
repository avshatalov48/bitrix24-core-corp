/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,ui_uploader_vue,ui_uploader_tileWidget,main_core_events,ui_buttons,ui_infoHelper,disk_document,ui_vue3_components_richLoc,main_core,ui_system_menu,ui_uploader_core,ui_icons_generator,ui_iconSet_api_core) {
	'use strict';

	var _form = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("form");
	var _parserId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("parserId");
	var _tag = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tag");
	var _regexp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("regexp");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _parse = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("parse");
	var _unparse = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unparse");
	var _getIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIds");
	class HtmlParser {
	  constructor(form) {
	    Object.defineProperty(this, _getIds, {
	      value: _getIds2
	    });
	    Object.defineProperty(this, _unparse, {
	      value: _unparse2
	    });
	    Object.defineProperty(this, _parse, {
	      value: _parse2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _form, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _parserId, {
	      writable: true,
	      value: 'diskfile0'
	    });
	    Object.defineProperty(this, _tag, {
	      writable: true,
	      value: '[DISK FILE ID=#id#]'
	    });
	    Object.defineProperty(this, _regexp, {
	      writable: true,
	      value: /\[(?:DOCUMENT ID|DISK FILE ID)=(n?[0-9]+)\]/ig
	    });
	    this.syncHighlightsDebounced = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = form;
	    this.syncHighlightsDebounced = main_core.Runtime.debounce(this.syncHighlights, 500, this);

	    // BBCode Parser Registration ([DISK FILE ID=190])
	    main_core_events.EventEmitter.emit(babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getEventObject(), 'OnParserRegister', this.getParser());
	  }
	  getParser() {
	    return {
	      id: babelHelpers.classPrivateFieldLooseBase(this, _parserId)[_parserId],
	      init: babelHelpers.classPrivateFieldLooseBase(this, _init)[_init].bind(this),
	      parse: babelHelpers.classPrivateFieldLooseBase(this, _parse)[_parse].bind(this),
	      unparse: babelHelpers.classPrivateFieldLooseBase(this, _unparse)[_unparse].bind(this)
	    };
	  }

	  /**
	   *
	   * @returns {Window.BXEditor}
	   */
	  getHtmlEditor() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getHtmlEditor();
	  }
	  insertFile(file) {
	    const bbDelimiter = file.isImage() ? '\n' : ' ';
	    const htmlDelimiter = file.isImage() ? '<br>' : '&nbsp;';
	    main_core_events.EventEmitter.emit(this.getHtmlEditor(), 'OnInsertContent', [bbDelimiter + this.createItemBBCode(file) + bbDelimiter, htmlDelimiter + this.createItemHtml(file) + htmlDelimiter]);
	    this.syncHighlights();
	  }
	  removeFile(item) {
	    if (this.getHtmlEditor().GetViewMode() === 'wysiwyg') {
	      const doc = this.getHtmlEditor().GetIframeDoc();
	      Object.keys(this.getHtmlEditor().bxTags).forEach(tagId => {
	        const tag = this.getHtmlEditor().bxTags[tagId];
	        if (tag.tag === babelHelpers.classPrivateFieldLooseBase(this, _parserId)[_parserId] && tag.serverFileId === item.serverFileId) {
	          const node = doc.getElementById(tagId);
	          if (node) {
	            node.parentNode.removeChild(node);
	          }
	        }
	      });
	      this.getHtmlEditor().SaveContent();
	    } else {
	      const content = this.getHtmlEditor().GetContent().replace(babelHelpers.classPrivateFieldLooseBase(this, _regexp)[_regexp], (str, foundId) => {
	        const {
	          objectId,
	          attachedId
	        } = babelHelpers.classPrivateFieldLooseBase(this, _getIds)[_getIds](foundId);
	        const items = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getItems();
	        const item = items.find(item => {
	          return item.serverFileId === attachedId || item.customData.objectId === objectId;
	        });
	        return item ? '' : str;
	      });
	      this.getHtmlEditor().SetContent(content);
	      this.getHtmlEditor().Focus();
	    }
	    this.syncHighlights();
	  }
	  selectItem(file) {
	    file.setCustomData('tileSelected', true);
	  }
	  deselectItem(file) {
	    file.setCustomData('tileSelected', false);
	  }
	  syncHighlights() {
	    const doc = this.getHtmlEditor().GetIframeDoc();
	    const inserted = new Set();
	    Object.keys(this.getHtmlEditor().bxTags).forEach(tagId => {
	      const tag = this.getHtmlEditor().bxTags[tagId];
	      if (tag.tag === babelHelpers.classPrivateFieldLooseBase(this, _parserId)[_parserId] && doc.getElementById(tagId)) {
	        inserted.add(tag.serverFileId);
	      }
	    });
	    let hasInsertedItems = false;
	    const files = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getFiles();
	    files.forEach(file => {
	      if (inserted.has(file.getServerFileId())) {
	        hasInsertedItems = true;
	        this.selectItem(file);
	      } else {
	        this.deselectItem(file);
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getPhotoTemplateMode() === 'auto') {
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().setPhotoTemplate(hasInsertedItems ? 'gallery' : 'grid');
	    }
	  }
	  createItemHtml(file, id) {
	    const tagId = this.getHtmlEditor().SetBxTag(false, {
	      tag: babelHelpers.classPrivateFieldLooseBase(this, _parserId)[_parserId],
	      serverFileId: file.getServerFileId(),
	      hideContextMenu: true,
	      fileId: file.getServerFileId()
	    });
	    if (file.isImage()) {
	      const imageSrc = this.getHtmlEditor().bbCode ? file.getPreviewUrl() : file.getServerPreviewUrl();
	      const previewWidth = this.getHtmlEditor().bbCode ? file.getPreviewWidth() : file.getServerPreviewWidth();
	      const previewHeight = this.getHtmlEditor().bbCode ? file.getPreviewHeight() : file.getServerPreviewHeight();
	      const renderWidth = 600; // half size of imagePreviewWidth
	      const renderHeight = 600; // half size of imagePreviewHeight
	      const ratioWidth = renderWidth / previewWidth;
	      const ratioHeight = renderHeight / previewHeight;
	      const ratio = Math.min(ratioWidth, ratioHeight);
	      const useOriginalSize = ratio > 1; // image is too small
	      const width = useOriginalSize ? previewWidth : previewWidth * ratio;
	      const height = useOriginalSize ? previewHeight : previewHeight * ratio;
	      return `<img style="max-width: 90%;" width="${width}" height="${height}" data-bx-file-id="${main_core.Text.encode(file.getServerFileId())}" id="${tagId}" src="${imageSrc}" title="${main_core.Text.encode(file.getName())}" data-bx-paste-check="Y" />`;
	    } else if (file.getCustomData('fileType') === 'player') {
	      return `<img contenteditable="false" class="bxhtmled-player-surrogate" data-bx-file-id="${main_core.Text.encode(file.getServerFileId())}" id="${tagId}" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-bx-paste-check="Y" />`;
	    }
	    return `<span contenteditable="false" data-bx-file-id="${main_core.Text.encode(file.getServerFileId())}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${main_core.Text.encode(file.getName())}</span>`;
	  }
	  createItemBBCode(file) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _tag)[_tag].replace('#id#', file.getServerFileId());
	  }
	}
	function _init2(htmlEditor) {
	  // stub
	}
	function _parse2(content) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _regexp)[_regexp].test(content)) {
	    return content;
	  }
	  this.syncHighlightsDebounced();
	  return content.replace(babelHelpers.classPrivateFieldLooseBase(this, _regexp)[_regexp], (str, id) => {
	    const {
	      objectId,
	      attachedId
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getIds)[_getIds](id);
	    const files = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getFiles();
	    const insertedFile = files.find(file => {
	      return file.getServerFileId() === attachedId || file.getCustomData('objectId') === objectId;
	    });
	    if (insertedFile) {
	      this.selectItem(insertedFile);
	      return this.createItemHtml(insertedFile, id);
	    }
	    return str;
	  });
	}
	function _unparse2(bxTag) {
	  const {
	    serverFileId
	  } = bxTag;
	  const files = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getFiles();
	  const uploaderFile = files.find(file => {
	    return file.getServerFileId() === serverFileId;
	  });
	  if (uploaderFile) {
	    return this.createItemBBCode(uploaderFile);
	  }
	  return '';
	}
	function _getIds2(id) {
	  let objectId = null;
	  let attachedId = null;
	  if (id[0] === 'n') {
	    objectId = main_core.Text.toInteger(id.replace('n', ''));
	  } else {
	    attachedId = main_core.Text.toInteger(id);
	  }
	  return {
	    objectId,
	    attachedId
	  };
	}

	let _ = t => t,
	  _t;
	var _userFieldControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userFieldControl");
	var _createDocumentButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDocumentButton");
	var _eventObject = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("eventObject");
	var _htmlParser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("htmlParser");
	var _htmlEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("htmlEditor");
	var _inited = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inited");
	var _handleDocumentReady = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleDocumentReady");
	var _handlePostFormReady = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlePostFormReady");
	var _init$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _getPostForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPostForm");
	var _bindEventObject = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEventObject");
	var _bindAdapterEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindAdapterEvents");
	var _handleReinitializeBefore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleReinitializeBefore");
	var _addCreateDocumentButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addCreateDocumentButton");
	var _handleButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleButtonClick");
	var _handleUploaderPanelToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleUploaderPanelToggle");
	var _handleDocumentPanelToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleDocumentPanelToggle");
	class MainPostForm extends main_core_events.EventEmitter {
	  constructor(userFieldControl, options) {
	    super();
	    Object.defineProperty(this, _handleDocumentPanelToggle, {
	      value: _handleDocumentPanelToggle2
	    });
	    Object.defineProperty(this, _handleUploaderPanelToggle, {
	      value: _handleUploaderPanelToggle2
	    });
	    Object.defineProperty(this, _handleButtonClick, {
	      value: _handleButtonClick2
	    });
	    Object.defineProperty(this, _addCreateDocumentButton, {
	      value: _addCreateDocumentButton2
	    });
	    Object.defineProperty(this, _handleReinitializeBefore, {
	      value: _handleReinitializeBefore2
	    });
	    Object.defineProperty(this, _bindAdapterEvents, {
	      value: _bindAdapterEvents2
	    });
	    Object.defineProperty(this, _bindEventObject, {
	      value: _bindEventObject2
	    });
	    Object.defineProperty(this, _getPostForm, {
	      value: _getPostForm2
	    });
	    Object.defineProperty(this, _init$1, {
	      value: _init2$1
	    });
	    Object.defineProperty(this, _handlePostFormReady, {
	      value: _handlePostFormReady2
	    });
	    Object.defineProperty(this, _handleDocumentReady, {
	      value: _handleDocumentReady2
	    });
	    Object.defineProperty(this, _userFieldControl, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _createDocumentButton, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _eventObject, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _htmlParser, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _htmlEditor, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _inited, {
	      writable: true,
	      value: false
	    });
	    this.setEventNamespace('BX.Disk.Uploader.Integration');
	    babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl] = userFieldControl;
	    babelHelpers.classPrivateFieldLooseBase(this, _eventObject)[_eventObject] = options.eventObject;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEventObject)[_bindEventObject]();
	    this.subscribeFromOptions(options.events);
	    this.subscribeOnce('onReady', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].canCreateDocuments()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _addCreateDocumentButton)[_addCreateDocumentButton]();
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].subscribe('onUploaderPanelToggle', babelHelpers.classPrivateFieldLooseBase(this, _handleUploaderPanelToggle)[_handleUploaderPanelToggle].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].subscribe('onDocumentPanelToggle', babelHelpers.classPrivateFieldLooseBase(this, _handleDocumentPanelToggle)[_handleDocumentPanelToggle].bind(this));
	    main_core.Event.ready(babelHelpers.classPrivateFieldLooseBase(this, _handleDocumentReady)[_handleDocumentReady].bind(this));
	  }
	  getUserFieldControl() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl];
	  }
	  getParser() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _htmlParser)[_htmlParser];
	  }

	  /**
	   *
	   * @returns {BXEditor}
	   */
	  getHtmlEditor() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _htmlEditor)[_htmlEditor];
	  }
	  getEventObject() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _eventObject)[_eventObject];
	  }
	  selectFileButton() {
	    const event = new main_core_events.BaseEvent({
	      data: 'show',
	      // needs to determine our own event (main.post.form emits onShowControllers as well)
	      compatData: ['user-field-widget']
	    });
	    main_core_events.EventEmitter.emit(babelHelpers.classPrivateFieldLooseBase(this, _eventObject)[_eventObject], 'onShowControllers', event);
	  }
	  deselectFileButton() {
	    const event = new main_core_events.BaseEvent({
	      data: 'hide',
	      // needs to determine our own event (main.post.form emits onShowControllers as well)
	      compatData: ['user-field-widget']
	    });
	    main_core_events.EventEmitter.emit(babelHelpers.classPrivateFieldLooseBase(this, _eventObject)[_eventObject], 'onShowControllers', event);
	  }
	  selectCreateDocumentButton() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton]) {
	      const container = babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton].closest('[data-id="disk-document"]');
	      if (container) {
	        container.setAttribute('data-bx-button-status', 'active');
	      }
	    }
	  }
	  deselectCreateDocumentButton() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton]) {
	      const container = babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton].closest('[data-id="disk-document"]');
	      if (container) {
	        container.removeAttribute('data-bx-button-status');
	      }
	    }
	  }
	  insertIntoText(item) {
	    const file = babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].getFile(item.id);
	    babelHelpers.classPrivateFieldLooseBase(this, _htmlParser)[_htmlParser].insertFile(file);
	  }
	}
	function _handleDocumentReady2() {
	  const postForm = babelHelpers.classPrivateFieldLooseBase(this, _getPostForm)[_getPostForm]();
	  if (postForm === null) {
	    setTimeout(() => {
	      const postForm = babelHelpers.classPrivateFieldLooseBase(this, _getPostForm)[_getPostForm]();
	      if (postForm) {
	        babelHelpers.classPrivateFieldLooseBase(this, _handlePostFormReady)[_handlePostFormReady](postForm);
	      } else {
	        console.error('Disk User Field: Post Form Not Found.');
	      }
	    }, 100);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _handlePostFormReady)[_handlePostFormReady](postForm);
	  }
	}
	function _handlePostFormReady2(postForm) {
	  if (postForm.isReady) {
	    babelHelpers.classPrivateFieldLooseBase(this, _init$1)[_init$1](postForm);
	  } else {
	    main_core_events.EventEmitter.subscribe(postForm, 'OnEditorIsLoaded', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _init$1)[_init$1](postForm);
	    });
	  }
	}
	function _init2$1(postForm) {
	  babelHelpers.classPrivateFieldLooseBase(this, _bindAdapterEvents)[_bindAdapterEvents]();
	  babelHelpers.classPrivateFieldLooseBase(this, _htmlEditor)[_htmlEditor] = postForm.getEditor();
	  babelHelpers.classPrivateFieldLooseBase(this, _htmlParser)[_htmlParser] = new HtmlParser(this);
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _htmlEditor)[_htmlEditor], 'OnContentChanged', event => {
	    babelHelpers.classPrivateFieldLooseBase(this, _htmlParser)[_htmlParser].syncHighlightsDebounced();
	  });
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _htmlEditor)[_htmlEditor], 'BXEditor:onBeforePasteAsync', event => {
	    return new Promise((resolve, reject) => {
	      const clipboardEvent = event.getData().clipboardEvent;
	      const clipboardData = clipboardEvent.clipboardData;
	      clipboardEvent.stopImmediatePropagation(); // Skip HTML Editor InitClipboardHandler
	      if (!clipboardData || !ui_uploader_core.isFilePasted(clipboardData)) {
	        resolve();
	        return;
	      }
	      clipboardEvent.preventDefault(); // Prevent Browser behavior
	      event.preventDefault(); // Prevent invoking HTMLEditor Paste Handler (OnPasteHandler)

	      ui_uploader_core.getFilesFromDataTransfer(clipboardData).then(files => {
	        files.forEach(file => {
	          this.getUserFieldControl().getUploader().addFile(file, {
	            events: {
	              [ui_uploader_core.FileEvent.LOAD_ERROR]: () => {},
	              [ui_uploader_core.FileEvent.UPLOAD_ERROR]: () => {},
	              [ui_uploader_core.FileEvent.LOAD_COMPLETE]: event => {
	                // const file: UploaderFile = event.getTarget();
	                // const item: TileWidgetItem = this.getUserFieldControl().getItem(file.getId());
	                // We could try insert a file/image stub.
	                // if (item)
	                // {
	                // 	this.getUserFieldControl().show();
	                // 	this.getParser().insertFile(item);
	                // }
	              },
	              [ui_uploader_core.FileEvent.UPLOAD_COMPLETE]: event => {
	                const uploadedFile = event.getTarget();
	                this.getUserFieldControl().showUploaderPanel();
	                this.getParser().insertFile(uploadedFile);
	              }
	            }
	          });
	        });
	        resolve();
	      }).catch(() => {
	        resolve();
	      });
	    });
	  });
	  this.emit('onReady');
	  babelHelpers.classPrivateFieldLooseBase(this, _inited)[_inited] = true;
	}
	function _getPostForm2() {
	  const PostForm = main_core.Reflection.getClass('BX.Main.PostForm');
	  if (!PostForm) {
	    return null;
	  }
	  let result = null;
	  PostForm.repo.forEach(editor => {
	    if (editor.getEventObject() === this.getEventObject()) {
	      result = editor;
	    }
	  });
	  return result;
	}
	function _bindEventObject2() {
	  // Show / Hide files control panel
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', event => {
	    if (main_core.Type.isArrayFilled(event.getCompatData()) && event.getCompatData()[0] === 'user-field-widget') {
	      // Skip our own event (main.post.form emits onShowControllers as well).
	      return;
	    }
	    const status = main_core.Type.isArray(event.getData()) ? event.getData().shift() : event.getData();
	    if (status === 'show') {
	      this.getUserFieldControl().showUploaderPanel();
	    } else {
	      this.getUserFieldControl().hide();
	    }
	  });

	  // Inline a post/comment editing
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onReinitializeBeforeAsync', event => {
	    return new Promise(resolve => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _inited)[_inited]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _handleReinitializeBefore)[_handleReinitializeBefore](event).then(() => resolve());
	      } else {
	        this.subscribeOnce('onReady', () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _handleReinitializeBefore)[_handleReinitializeBefore](event).then(() => resolve());
	        });
	      }
	    });
	  });

	  // Some components get attachments from main.post.form via arFiles and controllers properties.
	  // See main.post.form/templates/.default/src/editor.js:778
	  // See timeline/src/commenteditor.js:320
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onCollectControllers', event => {
	    const data = event.getData();
	    const fieldName = this.getUserFieldControl().getUploader().getHiddenFieldName();
	    const ids = this.getUserFieldControl().getItems().map(item => {
	      return item.serverFileId;
	    });
	    data[fieldName] = {
	      storage: 'disk',
	      tag: '[DISK FILE ID=#id#]',
	      values: ids,
	      handler: {
	        selectFile: (tab, path, selected) => {
	          Object.values(selected).forEach(item => {
	            this.getUserFieldControl().getUploader().addFile(item);
	          });
	        },
	        removeFiles: files => {
	          if (files !== undefined && Array.isArray(files)) {
	            const uploader = this.getUserFieldControl().getUploader();
	            const uploadFiles = uploader.getFiles();
	            let filteredFiles = files.map(item => uploadFiles.find(uploadFile => uploadFile.getServerFileId() === item).getId());
	            filteredFiles.forEach(file => {
	              uploader.removeFile(file);
	            });
	          }
	        }
	      }
	    };
	  });

	  // Video records
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'OnVideoHasCaught', event => {
	    event.stopImmediatePropagation();
	    this.getUserFieldControl().getUploader().addFile(event.getData(), {
	      events: {
	        [ui_uploader_core.FileEvent.UPLOAD_COMPLETE]: event => {
	          const file = event.getTarget();
	          this.getUserFieldControl().showUploaderPanel();
	          this.getParser().insertFile(file);
	        }
	      }
	    });
	  });

	  // An old approach (see BXEditor:onBeforePasteAsync) to process images from clipboard. Just in case.
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'OnImageHasCaught', event => {
	    event.stopImmediatePropagation();
	    return new Promise((resolve, reject) => {
	      this.getUserFieldControl().getUploader().addFile(event.getData(), {
	        events: {
	          [ui_uploader_core.FileEvent.LOAD_ERROR]: event => {
	            const error = event.getData().error;
	            reject(error);
	          },
	          [ui_uploader_core.FileEvent.UPLOAD_ERROR]: () => event => {
	            const error = event.getData().error;
	            reject(error);
	          },
	          [ui_uploader_core.FileEvent.UPLOAD_COMPLETE]: event => {
	            const file = event.getTarget();
	            const item = this.getUserFieldControl().getItem(file.getId());
	            if (item) {
	              this.getParser().syncHighlights();
	              resolve({
	                image: {
	                  src: file.getPreviewUrl(),
	                  width: file.getPreviewWidth(),
	                  height: file.getPreviewHeight()
	                },
	                html: this.getParser().createItemHtml(file)
	              });
	            } else {
	              reject(new ui_uploader_core.UploaderError('WRONG_FILE_SOURCE'));
	            }
	          }
	        }
	      });
	    });
	  });

	  // Files from Drag&Drop
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveCaught', event => {
	    // Skip this because an event doesn't have all Drag&Drop data
	    event.stopImmediatePropagation();
	  });
	  main_core_events.EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveDropped', event => {
	    event.stopImmediatePropagation();
	    const dragEvent = event.getData().event;
	    ui_uploader_core.getFilesFromDataTransfer(dragEvent.dataTransfer).then(files => {
	      this.getUserFieldControl().getUploader().addFiles(files);
	    }).catch(() => {});
	  });
	}
	function _bindAdapterEvents2() {
	  // Button counter: File -> File (1) -> File (2)
	  const adapter = this.getUserFieldControl().getAdapter();
	  adapter.subscribe('Item:onAdd', () => {
	    main_core_events.EventEmitter.emit(this.getEventObject(), 'onShowControllers:File:Increment');
	  });
	  adapter.subscribe('Item:onRemove', event => {
	    main_core_events.EventEmitter.emit(this.getEventObject(), 'onShowControllers:File:Decrement');
	    const item = event.getData().item;
	    if (this.getParser()) {
	      this.getParser().removeFile(item);
	    }
	  });
	}
	function _handleReinitializeBefore2(event) {
	  this.getUserFieldControl().clear();
	  const [, userFields] = event.getData();
	  const fieldName = this.getUserFieldControl().getUploader().getHiddenFieldName();
	  const userField = userFields && userFields[fieldName] && userFields[fieldName]['USER_TYPE_ID'] === 'disk_file' ? userFields[fieldName] : null;
	  if (userField !== null) {
	    // existing entity
	    if (main_core.Type.isPlainObject(userField['CUSTOM_DATA']) && main_core.Type.isStringFilled(userField['CUSTOM_DATA']['PHOTO_TEMPLATE'])) {
	      this.getUserFieldControl().setPhotoTemplateMode('manual');
	      this.getUserFieldControl().setPhotoTemplate(userField['CUSTOM_DATA']['PHOTO_TEMPLATE']);
	    } else {
	      this.getUserFieldControl().setPhotoTemplateMode('auto');
	    }
	  } else {
	    // new entity
	    this.getUserFieldControl().setPhotoTemplateMode('auto');
	    this.getUserFieldControl().setPhotoTemplate('grid');
	  }
	  if (userField === null) {
	    return Promise.resolve();
	  }

	  // nextTick needs to unmount a TileList component after clear().
	  // Component unmounting resets an auto collapse.
	  if (main_core.Type.isArray(userField['FILES'])) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].nextTick().then(() => {
	      userField['FILES'].forEach(file => {
	        if (!this.getUserFieldControl().getUploader().getFile(file.serverFileId)) {
	          this.getUserFieldControl().getUploader().addFile(file);
	        }
	      });
	      if (this.getUserFieldControl().getUploader().getFiles().length > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].enableAutoCollapse();
	      }
	    });
	  } else if (main_core.Type.isArrayFilled(userField['VALUE'])) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].nextTick().then(() => {
	      return new Promise(resolve => {
	        let fileIds = userField['VALUE'];
	        fileIds = fileIds.filter(id => !this.getUserFieldControl().getUploader().getFile(id));
	        let loaded = 0;
	        let addedFiles = [];
	        const onLoad = () => {
	          loaded++;
	          if (loaded === addedFiles.length) {
	            resolve();
	          }
	        };
	        const events = {
	          [ui_uploader_core.FileEvent.LOAD_COMPLETE]: onLoad,
	          [ui_uploader_core.FileEvent.LOAD_ERROR]: onLoad
	        };
	        const fileOptions = fileIds.map(id => [id, {
	          events
	        }]);
	        if (fileOptions.length > 0) {
	          addedFiles = this.getUserFieldControl().getUploader().addFiles(fileOptions);
	          if (addedFiles.length === 0) {
	            resolve();
	          } else {
	            babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl)[_userFieldControl].enableAutoCollapse();
	          }
	        } else {
	          resolve();
	        }
	      });
	    });
	  }
	  return Promise.resolve();
	}
	function _addCreateDocumentButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton] = main_core.Tag.render(_t || (_t = _`
			<div onclick="${0}">
				<i></i>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _handleButtonClick)[_handleButtonClick].bind(this), main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_DOCUMENT'));
	  main_core_events.EventEmitter.emit(this.getEventObject(), 'OnAddButton', [{
	    BODY: babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton],
	    ID: 'disk-document'
	  }, 'file']);
	}
	function _handleButtonClick2() {
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _createDocumentButton)[_createDocumentButton].closest('[data-id="disk-document"]');
	  if (container && container.hasAttribute('data-bx-button-status')) {
	    this.getUserFieldControl().hide();
	  } else {
	    this.getUserFieldControl().showDocumentPanel();
	  }
	}
	function _handleUploaderPanelToggle2(event) {
	  const isOpen = event.getData().isOpen;
	  if (isOpen) {
	    this.selectFileButton();
	  } else {
	    this.deselectFileButton();
	  }
	}
	function _handleDocumentPanelToggle2(event) {
	  const isOpen = event.getData().isOpen;
	  if (isOpen) {
	    this.selectCreateDocumentButton();
	  } else {
	    this.deselectCreateDocumentButton();
	  }
	}

	const DocumentService = Object.freeze({
	  Google: 'gdrive',
	  Office: 'office365',
	  Dropbox: 'dropbox',
	  Onedrive: 'onedrive',
	  OnlyOffice: 'onlyoffice',
	  Local: 'l'
	});
	const DocumentType = Object.freeze({
	  Docx: 'docx',
	  Xlsx: 'xlsx',
	  Pptx: 'pptx',
	  Board: 'board'
	});

	const settings = main_core.Extension.getSettings('disk.uploader.user-field-widget');
	class UserFieldSettings {
	  canCreateDocuments() {
	    return settings.get('canCreateDocuments', false);
	  }
	  getDocumentServices() {
	    const documentHandlers = settings.get('documentHandlers', {});
	    if (main_core.Type.isPlainObject(documentHandlers)) {
	      return documentHandlers;
	    }
	    return {};
	  }
	  getImportServices() {
	    const importHandlers = settings.get('importHandlers', {});
	    if (main_core.Type.isPlainObject(importHandlers)) {
	      return importHandlers;
	    }
	    return {};
	  }
	  canUseImportService() {
	    return settings.get('canUseImport', true);
	  }
	  getImportFeatureId() {
	    return settings.get('importFeatureId', '');
	  }
	  isBoardsEnabled() {
	    return settings.get('isBoardsEnabled', false);
	  }
	}
	const userFieldSettings = new UserFieldSettings();

	let _$1 = t => t,
	  _t$1,
	  _t2;
	const instances = new Map();
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _adapter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adapter");
	var _mainPostForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainPostForm");
	var _allowDocumentFieldName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("allowDocumentFieldName");
	var _photoTemplateFieldName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("photoTemplateFieldName");
	var _photoTemplateInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("photoTemplateInput");
	var _photoTemplateMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("photoTemplateMode");
	var _widgetComponent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widgetComponent");
	var _bindHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindHandlers");
	var _unbindHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unbindHandlers");
	var _handleItemAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemAdd");
	var _handleItemComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemComplete");
	var _handleItemRemove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemRemove");
	class UserFieldControl extends main_core_events.EventEmitter {
	  constructor(widgetComponent) {
	    super();
	    Object.defineProperty(this, _unbindHandlers, {
	      value: _unbindHandlers2
	    });
	    Object.defineProperty(this, _bindHandlers, {
	      value: _bindHandlers2
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _adapter, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _mainPostForm, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _allowDocumentFieldName, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _photoTemplateFieldName, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _photoTemplateInput, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _photoTemplateMode, {
	      writable: true,
	      value: 'auto'
	    });
	    Object.defineProperty(this, _widgetComponent, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _handleItemAdd, {
	      writable: true,
	      value: event => {
	        const item = event.getData().item;
	        this.emit('Item:onAdd', {
	          item
	        });
	      }
	    });
	    Object.defineProperty(this, _handleItemComplete, {
	      writable: true,
	      value: event => {
	        const item = event.getData().item;
	        this.setDocumentEdit(item);
	        this.emit('Item:onComplete', {
	          item
	        });
	      }
	    });
	    Object.defineProperty(this, _handleItemRemove, {
	      writable: true,
	      value: event => {
	        const item = event.getData().item;
	        this.removeAllowDocumentEditInput(item);
	        this.emit('Item:onRemove', {
	          item
	        });
	      }
	    });
	    this.setEventNamespace('BX.Disk.Uploader.Integration');
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent] = widgetComponent;
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter] = widgetComponent.adapter;
	    const options = main_core.Type.isPlainObject(widgetComponent.widgetOptions) ? widgetComponent.widgetOptions : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName] = main_core.Type.isStringFilled(options.photoTemplateFieldName) ? options.photoTemplateFieldName : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName] = main_core.Type.isStringFilled(options.allowDocumentFieldName) ? options.allowDocumentFieldName : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindHandlers)[_bindHandlers]();
	    if (options.disableLocalEdit) {
	      // it would be better to load disk.document on demand
	      BX.Disk.Document.Local.Instance.disable();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName] !== null && this.getUploader().getHiddenFieldsContainer() !== null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput] = main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<input 
					name="${0}" 
					value="${0}"
					type="hidden" 
				/>
			`), babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName], main_core.Type.isStringFilled(options.photoTemplate) ? options.photoTemplate : 'grid');
	      this.setPhotoTemplateMode(options.photoTemplateMode);
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput], this.getUploader().getHiddenFieldsContainer());
	    }
	    if (this.getUploader().getHiddenFieldsContainer() === null && (babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName] !== null || babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName] !== null)) {
	      // eslint-disable-next-line no-console
	      console.warn('DiskUserField: to use "photoTemplateFieldName" or "allowDocumentFieldName" options ' + 'you have to set "hiddenFieldsContainer" in the uploader options.');
	    }
	    this.subscribeFromOptions(options.events);
	    const eventObject = main_core.Type.isElementNode(options.eventObject) ? options.eventObject : null;
	    if (eventObject) {
	      babelHelpers.classPrivateFieldLooseBase(this, _mainPostForm)[_mainPostForm] = new MainPostForm(this, {
	        eventObject,
	        events: {
	          onReady: () => {
	            this.getUploader().addFiles(options.files);
	            if (this.getUploader().getFiles().length > 0) {
	              this.showUploaderPanel();
	              this.enableAutoCollapse();
	            }
	          }
	        }
	      });
	    } else {
	      this.getUploader().addFiles(options.files);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = main_core.Type.isStringFilled(options.mainPostFormId) ? options.mainPostFormId : `user-field-control-${main_core.Text.getRandom().toLowerCase()}`;
	    instances.set(babelHelpers.classPrivateFieldLooseBase(this, _id)[_id], this);
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _unbindHandlers)[_unbindHandlers]();
	  }
	  static getById(id) {
	    return instances.get(id) || null;
	  }
	  static getInstances() {
	    return [...instances.values()];
	  }
	  canCreateDocuments() {
	    const canCreateDocuments = userFieldSettings.canCreateDocuments();
	    return canCreateDocuments && babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].widgetOptions.canCreateDocuments !== false;
	  }
	  getAdapter() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter];
	  }
	  getMainPostForm() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _mainPostForm)[_mainPostForm];
	  }
	  getItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems();
	  }
	  getItem(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItem(id);
	  }
	  getFiles() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().getFiles();
	  }
	  getFile(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().getFile(id);
	  }
	  getUploader() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader();
	  }
	  nextTick() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].$nextTick();
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].priorityVisibility = 'hidden';
	    this.emit('onUploaderPanelToggle', {
	      isOpen: false
	    });
	    this.emit('onDocumentPanelToggle', {
	      isOpen: false
	    });
	  }
	  showUploaderPanel() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].priorityVisibility = 'uploader';
	    this.emit('onUploaderPanelToggle', {
	      isOpen: true
	    });
	    this.emit('onDocumentPanelToggle', {
	      isOpen: false
	    });
	  }
	  showDocumentPanel() {
	    if (!this.canCreateDocuments()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].priorityVisibility = 'documents';
	    this.emit('onUploaderPanelToggle', {
	      isOpen: false
	    });
	    this.emit('onDocumentPanelToggle', {
	      isOpen: true
	    });
	  }
	  clear() {
	    this.getUploader().removeFiles({
	      removeFromServer: false
	    });
	  }
	  enableAutoCollapse() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].enableAutoCollapse();
	  }
	  canAllowDocumentEdit() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName] !== null && this.getUploader().getHiddenFieldsContainer() !== null;
	  }
	  canItemAllowEdit(item) {
	    return this.canAllowDocumentEdit() && item.customData.isEditable === true && item.customData.canUpdate === true;
	  }
	  getAllowDocumentEditInput(item) {
	    const selector = `input[name='${babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName]}[${item.serverFileId}]']`;
	    if (this.getUploader().getHiddenFieldsContainer() !== null) {
	      return this.getUploader().getHiddenFieldsContainer().querySelector(selector);
	    }
	    return null;
	  }
	  removeAllowDocumentEditInput(item) {
	    const input = this.getAllowDocumentEditInput(item);
	    if (input !== null) {
	      main_core.Dom.remove(input);
	    }
	  }
	  setDocumentEdit(item, allowEdit = null) {
	    if (!this.canItemAllowEdit(item)) {
	      return;
	    }
	    let input = this.getAllowDocumentEditInput(item);
	    if (input === null) {
	      input = main_core.Tag.render(_t2 || (_t2 = _$1`<input name="${0}[${0}]" type="hidden" />`), babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName], item.serverFileId);
	      main_core.Dom.append(input, this.getUploader().getHiddenFieldsContainer());
	    }
	    allowEdit = allowEdit === null ? item.customData.allowEdit === true : allowEdit;
	    input.value = allowEdit ? 1 : 0;
	    const file = this.getFile(item.id);
	    file.setCustomData('allowEdit', allowEdit);
	  }
	  canChangePhotoTemplate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput] !== null;
	  }
	  setPhotoTemplate(name) {
	    if (main_core.Type.isStringFilled(name) && babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput] !== null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput].value = name;
	    }
	  }
	  getPhotoTemplate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput] !== null ? babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateInput)[_photoTemplateInput].value : '';
	  }
	  setPhotoTemplateMode(mode) {
	    if (mode === 'auto' || mode === 'manual') {
	      babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateMode)[_photoTemplateMode] = mode;
	    }
	  }
	  getPhotoTemplateMode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateMode)[_photoTemplateMode];
	  }
	  getDocumentServices() {
	    return userFieldSettings.getDocumentServices();
	  }
	  getCurrentDocumentService() {
	    let currentServiceCode = BX.Disk.getDocumentService();
	    if (!currentServiceCode && BX.Disk.isAvailableOnlyOffice()) {
	      currentServiceCode = DocumentService.OnlyOffice;
	    } else if (!currentServiceCode) {
	      currentServiceCode = DocumentService.Local;
	    }
	    return this.getDocumentServices()[currentServiceCode] || null;
	  }
	}
	function _bindHandlers2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribe('Item:onAdd', babelHelpers.classPrivateFieldLooseBase(this, _handleItemAdd)[_handleItemAdd]);
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribe('Item:onComplete', babelHelpers.classPrivateFieldLooseBase(this, _handleItemComplete)[_handleItemComplete]);
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribe('Item:onRemove', babelHelpers.classPrivateFieldLooseBase(this, _handleItemRemove)[_handleItemRemove]);
	}
	function _unbindHandlers2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribe('Item:onAdd', babelHelpers.classPrivateFieldLooseBase(this, _handleItemAdd)[_handleItemAdd]);
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribe('Item:onComplete', babelHelpers.classPrivateFieldLooseBase(this, _handleItemComplete)[_handleItemComplete]);
	  babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribe('Item:onRemove', babelHelpers.classPrivateFieldLooseBase(this, _handleItemRemove)[_handleItemRemove]);
	}

	const loadDiskFileDialog = (dialogName, params = {}) => {
	  return new Promise(resolve => {
	    main_core.Runtime.loadExtension('disk.legacy.file-dialog').then(() => {
	      const handleInit = event => {
	        const [name] = event.getData();
	        if (dialogName === name) {
	          main_core_events.EventEmitter.unsubscribe(BX.DiskFileDialog, 'inited', handleInit);
	          resolve();
	        }
	      };
	      main_core_events.EventEmitter.subscribe(BX.DiskFileDialog, 'inited', handleInit);

	      // Invokes BX.DiskFileDialog.init
	      main_core.ajax.get(getDialogInitUrl(dialogName, params));
	    });
	  });
	};
	const getDialogInitUrl = (dialogName, params = {}) => {
	  const url = `/bitrix/tools/disk/uf.php?action=openDialog&SITE_ID=${main_core.Loc.getMessage('SITE_ID')}&dialog2=Y&ACTION=SELECT&MULTI=Y&dialogName=${dialogName}`;
	  return main_core.Uri.addParam(url, params);
	};

	let _$2 = t => t,
	  _t$2,
	  _t2$1;
	var _userFieldControl$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userFieldControl");
	var _item = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("item");
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _folderDialogId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("folderDialogId");
	var _showRenameMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showRenameMenu");
	class ItemMenu {
	  constructor(userFieldControl, item, menu) {
	    Object.defineProperty(this, _showRenameMenu, {
	      value: _showRenameMenu2
	    });
	    Object.defineProperty(this, _userFieldControl$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _item, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _folderDialogId, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1] = userFieldControl;
	    babelHelpers.classPrivateFieldLooseBase(this, _item)[_item] = item;
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = menu;
	    babelHelpers.classPrivateFieldLooseBase(this, _folderDialogId)[_folderDialogId] = `folder-dialog-${main_core.Text.getRandom(5)}`;
	  }
	  build() {
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getPopupWindow().setMaxWidth(500);
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.viewLink)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        id: 'view',
	        text: main_core.Loc.getMessage('DISK_UF_WIDGET_OPEN_FILE_MENU_TITLE'),
	        href: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.viewLink,
	        target: '_blank',
	        onclick: (event, menuItem) => menuItem.getMenuWindow().close()
	      }, 'download');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].canItemAllowEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        delimiter: true
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        id: 'allow-edit',
	        className: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.allowEdit === true ? 'disk-user-field-item-checked' : '',
	        text: main_core.Loc.getMessage('DISK_UF_WIDGET_ALLOW_DOCUMENT_EDIT'),
	        onclick: (event, menuItem) => {
	          if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.allowEdit === true) {
	            babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].setDocumentEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item], false);
	          } else {
	            babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].setDocumentEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item], true);
	          }
	          menuItem.getMenuWindow().close();
	        }
	      });
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.canRename) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        delimiter: true
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        id: 'rename',
	        text: main_core.Loc.getMessage('DISK_UF_WIDGET_RENAME_FILE_MENU_TITLE'),
	        events: {
	          'SubMenu:onShow': event => {
	            const renameItem = event.getTarget();
	            babelHelpers.classPrivateFieldLooseBase(this, _showRenameMenu)[_showRenameMenu](renameItem);
	          }
	        },
	        items: [{
	          id: 'rename-textarea',
	          html: '<div class="disk-user-field-rename-loading"></div>',
	          className: 'disk-user-field-rename-menu-item'
	        }]
	      });
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.storage)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        delimiter: true
	        // text: Loc.getMessage('DISK_UF_WIDGET_SAVED_IN_DISK_FOLDER'),
	      });

	      if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.canMove) {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	          id: 'storage',
	          text: `${babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.storage}&mldr;`,
	          onclick: () => {
	            this.openFolderDialog();
	            babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].close();
	          },
	          disabled: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.tileSelected === true
	        });
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	          id: 'storage',
	          text: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.storage,
	          disabled: true
	        });
	      }
	    }
	  }
	  rename(newName) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('disk.api.commonActions.rename', {
	        data: {
	          objectId: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.objectId,
	          newName,
	          autoCorrect: true,
	          generateUniqueName: true
	        }
	      }).then(response => {
	        var _response$data, _response$data$object;
	        if ((response == null ? void 0 : response.status) === 'success' && (response == null ? void 0 : (_response$data = response.data) == null ? void 0 : (_response$data$object = _response$data.object) == null ? void 0 : _response$data$object.name) !== babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].name) {
	          const file = babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].getFile(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].id);
	          const name = response.data.object.name;
	          file.setName(name);
	        }
	        resolve();
	      }).catch(response => {
	        BX.Disk.showModalWithStatusAction(response);
	        reject();
	      });
	    });
	  }
	  openFolderDialog() {
	    loadDiskFileDialog(babelHelpers.classPrivateFieldLooseBase(this, _folderDialogId)[_folderDialogId], {
	      wish: 'fakemove'
	    }).then(() => {
	      BX.DiskFileDialog.obCallback[babelHelpers.classPrivateFieldLooseBase(this, _folderDialogId)[_folderDialogId]] = {
	        saveButton: (tab, path, selectedItems, folderByPath) => {
	          const selectedItem = Object.values(selectedItems)[0] || folderByPath;
	          if (!selectedItem) {
	            return;
	          }
	          const folderId = selectedItem.id === 'root' ? tab.rootObjectId : selectedItem.id;
	          main_core.ajax.runAction('disk.api.commonActions.move', {
	            data: {
	              objectId: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData.objectId,
	              toFolderId: folderId
	            }
	          }).then(response => {
	            if ((response == null ? void 0 : response.status) === 'success') {
	              const file = babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].getFile(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].id);
	              const name = response.data.object.name;
	              const id = response.data.object.id;
	              file.setServerFileId(`n${id}`);
	              file.setName(name);
	              if (selectedItem.id === 'root') {
	                file.setCustomData('storage', `${tab.name} / `);
	              } else {
	                file.setCustomData('storage', `${tab.name} / ${selectedItem.name}`);
	              }
	            }
	          }).catch(response => {
	            BX.Disk.showModalWithStatusAction(response);
	          });
	        }
	      };
	      if (BX.DiskFileDialog.popupWindow === null) {
	        BX.DiskFileDialog.openDialog(babelHelpers.classPrivateFieldLooseBase(this, _folderDialogId)[_folderDialogId]);
	      }
	    }).catch(() => {
	      // just ignore
	    });
	  }
	}
	function _showRenameMenu2(renameItem) {
	  main_core.Runtime.loadExtension('ui.buttons').then(exports => {
	    const Button = exports.Button;
	    const ButtonSize = exports.ButtonSize;
	    const ButtonColor = exports.ButtonColor;
	    const CancelButton = exports.CancelButton;
	    const handleKeydown = event => {
	      if (event.code === 'Enter') {
	        handleRenameClick();
	      }
	    };
	    const nameWithoutExtension = ui_uploader_core.getFilenameWithoutExtension(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].name);
	    const handleRenameClick = () => {
	      const textareaValue = textarea.value.trim();
	      if (!main_core.Type.isStringFilled(textareaValue) || textareaValue === nameWithoutExtension) {
	        renameItem.getMenuWindow().close();
	        return;
	      }
	      renameBtn.setWaiting(true);
	      const newFilename = `${textareaValue}.${ui_uploader_core.getFileExtension(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].name)}`;
	      this.rename(newFilename).then(() => {
	        renameBtn.setWaiting(false);
	        renameItem.getMenuWindow().close();
	      }).catch(() => {
	        renameBtn.setWaiting(false);
	      });
	    };
	    const textarea = main_core.Tag.render(_t$2 || (_t$2 = _$2`
				<textarea 
					class="disk-user-field-rename-textarea" 
					onkeydown="${0}"
				>${0}</textarea>
			`), handleKeydown, main_core.Text.encode(nameWithoutExtension));
	    const renameBtn = new Button({
	      text: main_core.Loc.getMessage('DISK_UF_WIDGET_RENAME_FILE_BUTTON_TITLE'),
	      color: ButtonColor.PRIMARY,
	      size: ButtonSize.SMALL,
	      onclick: handleRenameClick
	    });
	    const cancelBtn = new CancelButton({
	      size: ButtonSize.SMALL,
	      onclick: () => {
	        renameItem.getMenuWindow().close();
	      }
	    });
	    const submenu = renameItem.getSubMenu();
	    const textareaItem = submenu.getMenuItem('rename-textarea');
	    textareaItem.setText(main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
					<div class="disk-user-field-rename-form">
						${0}
						<div class="disk-user-field-rename-buttons">${0}</div>
					</div>
				`), textarea, [renameBtn.render(), cancelBtn.render()]), true);
	    renameItem.showSubMenu();
	  });
	}

	var _userFieldControl$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userFieldControl");
	var _menu$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _getItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItems");
	class SettingsMenu {
	  constructor(userFieldControl) {
	    Object.defineProperty(this, _getItems, {
	      value: _getItems2
	    });
	    Object.defineProperty(this, _userFieldControl$2, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _menu$1, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2] = userFieldControl;
	  }
	  getMenu(button) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _menu$1))[_menu$1]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_menu$1] = new ui_system_menu.Menu({
	      bindElement: button.getContainer(),
	      angle: true,
	      autoHide: true,
	      offsetLeft: 16,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems](),
	      events: {
	        onShow: () => button.select(),
	        onClose: () => button.deselect()
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1];
	  }
	  show(button) {
	    this.getMenu(button).show();
	  }
	  toggle(button) {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4;
	    if ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1]) != null && (_babelHelpers$classPr4 = _babelHelpers$classPr3.getPopup()) != null && _babelHelpers$classPr4.isShown()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].close();
	    } else {
	      this.show(button);
	    }
	  }
	  hide() {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1]) == null ? void 0 : _babelHelpers$classPr5.close();
	  }
	  hasItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems]().length > 0;
	  }
	}
	function _getItems2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].canChangePhotoTemplate()) {
	    return [];
	  }
	  return [{
	    isSelected: babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].getPhotoTemplate() === 'grid',
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_ALLOW_PHOTO_COLLAGE'),
	    onClick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplateMode('manual');
	      if (babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].getPhotoTemplate() === 'grid') {
	        babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplate('gallery');
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplate('grid');
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].updateItems(babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems]());
	    }
	  }];
	}

	const loadingDialogs = new Set();
	const openDiskFileDialog = options => {
	  options = main_core.Type.isPlainObject(options) ? options : {};
	  const dialogId = main_core.Type.isStringFilled(options.dialogId) ? options.dialogId : `file-dialog-${main_core.Text.getRandom(5)}`;
	  const onLoad = main_core.Type.isFunction(options.onLoad) ? options.onLoad : null;
	  const onSelect = main_core.Type.isFunction(options.onSelect) ? options.onSelect : null;
	  const onClose = main_core.Type.isFunction(options.onClose) ? options.onClose : null;
	  const uploader = options.uploader instanceof ui_uploader_core.Uploader ? options.uploader : null;
	  if (loadingDialogs.has(dialogId)) {
	    return;
	  }
	  loadingDialogs.add(dialogId);
	  loadDiskFileDialog(dialogId).then(() => {
	    loadingDialogs.delete(dialogId);
	    if (onLoad !== null) {
	      onLoad();
	    }
	    BX.DiskFileDialog.obCallback[dialogId] = {
	      saveButton: (tab, path, selectedItems) => {
	        Object.values(selectedItems).forEach(item => {
	          if (uploader !== null) {
	            uploader.addFile(item.id, {
	              name: item.name,
	              preload: true
	            });
	          }
	        });
	        if (onSelect !== null) {
	          onSelect(tab, path, selectedItems);
	        }
	      },
	      popupDestroy: () => {
	        loadingDialogs.delete(dialogId);
	        if (onClose !== null) {
	          onClose();
	        }
	      }
	    };
	    if (BX.DiskFileDialog.popupWindow === null) {
	      BX.DiskFileDialog.openDialog(dialogId);
	    }
	  });
	};

	class CloudLoadController extends ui_uploader_core.AbstractLoadController {
	  constructor(server, options = {}) {
	    super(server, options);
	  }
	  load(file) {
	    this.emit('onProgress', {
	      progress: 100
	    });
	    this.emit('onLoad');
	  }
	  abort() {}
	}

	var _fileId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileId");
	var _serviceId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("serviceId");
	class CloudUploadController extends ui_uploader_core.AbstractUploadController {
	  constructor(server, options = {}) {
	    super(server, options);
	    Object.defineProperty(this, _fileId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _serviceId, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _fileId)[_fileId] = options.fileId;
	    babelHelpers.classPrivateFieldLooseBase(this, _serviceId)[_serviceId] = options.serviceId;
	  }
	  upload(file) {
	    BX.Disk.ExternalLoader.startLoad({
	      file: {
	        id: babelHelpers.classPrivateFieldLooseBase(this, _fileId)[_fileId],
	        service: babelHelpers.classPrivateFieldLooseBase(this, _serviceId)[_serviceId]
	      },
	      onFinish: newData => {
	        this.emit('onUpload', {
	          fileInfo: newData.fileInfo
	        });
	      },
	      onProgress: progress => {
	        this.emit('onProgress', {
	          progress: progress
	        });
	      },
	      onError: errors => {
	        this.emit('onError', {
	          error: ui_uploader_core.UploaderError.createFromAjaxErrors(errors)
	        });
	      }
	    });
	  }
	  abort() {}
	}

	const loadingDialogs$1 = new Set();
	const openCloudFileDialog = options => {
	  if (!userFieldSettings.canUseImportService()) {
	    ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      featureId: userFieldSettings.getImportFeatureId()
	    }).show();
	    return;
	  }
	  options = main_core.Type.isPlainObject(options) ? options : {};
	  const dialogId = main_core.Type.isStringFilled(options.dialogId) ? options.dialogId : `cloud-dialog-${main_core.Text.getRandom(5)}`;
	  const serviceId = main_core.Type.isStringFilled(options.serviceId) ? options.serviceId : DocumentService.Google;
	  const onLoad = main_core.Type.isFunction(options.onLoad) ? options.onLoad : null;
	  const onSelect = main_core.Type.isFunction(options.onSelect) ? options.onSelect : null;
	  const onClose = main_core.Type.isFunction(options.onClose) ? options.onClose : null;
	  const uploader = options.uploader instanceof ui_uploader_core.Uploader ? options.uploader : null;
	  if (loadingDialogs$1.has(dialogId)) {
	    return;
	  }
	  loadingDialogs$1.add(dialogId);
	  loadDiskFileDialog(dialogId, {
	    service: serviceId,
	    cloudImport: 1
	  }).then(() => {
	    loadingDialogs$1.delete(dialogId);
	    if (onLoad !== null) {
	      onLoad();
	    }
	    BX.DiskFileDialog.obCallback[dialogId] = {
	      saveButton: (tab, path, selectedItems) => {
	        main_core.Runtime.loadExtension('disk.legacy.external-loader').then(() => {
	          Object.values(selectedItems).forEach(item => {
	            if (item.type === 'file' && uploader !== null) {
	              uploader.addFile({
	                id: item.id,
	                serverFileId: item.id,
	                name: item.name,
	                size: main_core.Text.toNumber(item.sizeInt),
	                loadController: new CloudLoadController(uploader.getServer(), {
	                  fileId: item.id,
	                  serviceId: item.provider
	                }),
	                uploadController: new CloudUploadController(uploader.getServer(), {
	                  fileId: item.id,
	                  serviceId: item.provider
	                })
	              });
	            }
	          });
	          if (onSelect !== null) {
	            onSelect(tab, path, selectedItems);
	          }
	        });
	      },
	      popupDestroy: () => {
	        loadingDialogs$1.delete(dialogId);
	        if (onClose !== null) {
	          onClose();
	        }
	      }
	    };
	    if (serviceId === DocumentService.Google) {
	      main_core.ajax({
	        url: '/bitrix/tools/disk/uf.php?action=getGoogleAppData',
	        dataType: 'json',
	        onsuccess(data) {
	          if (data.authUrl) {
	            openAuthPopup(data.authUrl, options);
	            return;
	          }
	          initGooglePicker(data, dialogId).then(picker => {
	            picker.loadAndShowPicker();
	          }).catch(error => {
	            console.error(error);
	          });
	        },
	        onfailure(data) {
	          BX.DiskFileDialog.sendRequest = false;
	        }
	      });
	      return;
	    }
	    if (BX.DiskFileDialog.popupWindow === null) {
	      BX.DiskFileDialog.openDialog(dialogId);
	    }
	  });
	};
	const openAuthPopup = function (authUrl, dialogOptions) {
	  BX.util.popup(authUrl, 1030, 700);
	  main_core.Event.bind(window, 'hashchange', () => {
	    const matches = document.location.hash.match(/external-auth-(\w+)/);
	    if (!matches) {
	      return;
	    }
	    BX.DiskFileDialog.sendRequest = false;
	    openCloudFileDialog(dialogOptions);
	  });
	};
	const initGooglePicker = async function (data, dialogId) {
	  return main_core.Runtime.loadExtension('disk.google-drive-picker').then(({
	    GoogleDrivePicker
	  }) => {
	    return new GoogleDrivePicker(data.clientId, data.appId, data.apiKey, data.accessToken, BX.DiskFileDialog.obCallback[dialogId]);
	  });
	};

	const createDocumentDialog = (options = {}) => {
	  const uploader = options.uploader instanceof ui_uploader_core.Uploader ? options.uploader : null;
	  const documentType = main_core.Type.isStringFilled(options.documentType) ? options.documentType : null;
	  const onAddFile = main_core.Type.isFunction(options.onAddFile) ? options.onAddFile : null;

	  // TODO: load disk and disk.document extensions on demand
	  if (!BX.Disk.getDocumentService()) {
	    const service = BX.Disk.isAvailableOnlyOffice() ? DocumentService.OnlyOffice : DocumentService.Local;
	    BX.Disk.saveDocumentService(service);
	  }
	  let newTab = null;
	  if (documentType === DocumentType.Board) {
	    newTab = window.open('', '_blank');
	  }
	  if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk() || documentType === 'board') {
	    BX.Disk.Document.Local.Instance.createFile({
	      type: documentType
	    }).then(response => {
	      if (response.status === 'success') {
	        if (documentType === 'board') {
	          BX.UI.Analytics.sendData({
	            event: 'create',
	            tool: 'boards',
	            category: 'boards',
	            c_element: 'docs_attach_uploader_widget'
	          });
	        }
	        uploader.addFile(`n${response.object.id}`, {
	          name: response.object.name,
	          preload: true
	        });
	        onAddFile == null ? void 0 : onAddFile();
	        if (newTab !== null && response.openUrl) {
	          newTab.location.href = response.openUrl;
	        }
	      }
	    });
	  } else {
	    const documentService = BX.Disk.getDocumentService();
	    const byUnifiedLink = options.documentHandlers.some(handler => handler.supportsUnifiedLink && handler.code === documentService);
	    const saveCallback = response => {
	      if (response.status !== 'success') {
	        return;
	      }
	      const key = response.object ? 'object' : 'data';
	      if (response[key]) {
	        uploader.addFile(`n${response[key].id}`, {
	          name: response[key].name,
	          size: response[key].size,
	          preload: true
	        });
	        onAddFile == null ? void 0 : onAddFile();
	      }
	    };
	    const createProcess = new BX.Disk.Document.CreateProcess({
	      typeFile: documentType,
	      serviceCode: documentService,
	      byUnifiedLink,
	      onAfterSave: saveCallback,
	      onAfterCreateFile: saveCallback
	    });
	    createProcess.start();
	  }
	};

	const Loader = {
	  name: 'Loader',
	  props: {
	    size: {
	      type: Number,
	      default: 70
	    },
	    color: {
	      type: String,
	      default: '#2fc6f6'
	    },
	    offset: {
	      type: Object,
	      default: null
	    },
	    mode: {
	      type: String,
	      default: ''
	    }
	  },
	  created() {
	    this.loader = null;
	  },
	  mounted() {
	    main_core.Runtime.loadExtension('main.loader').then(exports => {
	      const {
	        Loader
	      } = exports;
	      this.loader = new Loader({
	        target: this.$refs.container,
	        size: this.size,
	        color: this.color,
	        offset: this.offset,
	        mode: this.mode
	      });
	      this.loader.show();
	    });
	  },
	  beforeUnmount() {
	    if (this.loader) {
	      this.loader.destroy();
	      this.loader = null;
	    }
	  },
	  template: '<span ref="container"></span>'
	};

	// @vue/component
	const ControlPanel = {
	  name: 'ControlPanel',
	  components: {
	    Loader
	  },
	  inject: ['userFieldControl', 'uploader', 'getMessage'],
	  setup() {
	    return {
	      DocumentService,
	      importServices: userFieldSettings.getImportServices()
	    };
	  },
	  data: () => ({
	    showDialogLoader: false,
	    showCloudDialogLoader: false,
	    currentServiceId: null
	  }),
	  created() {
	    this.fileDialogId = `file-dialog-${main_core.Text.getRandom(5)}`;
	    this.cloudDialogId = `cloud-dialog-${main_core.Text.getRandom(5)}`;
	  },
	  mounted() {
	    this.uploader.assignBrowse(this.$refs.upload);
	  },
	  methods: {
	    openDiskFileDialog() {
	      if (this.showDialogLoader) {
	        return;
	      }
	      this.showDialogLoader = true;
	      openDiskFileDialog({
	        dialogId: this.fileDialogId,
	        uploader: this.uploader,
	        onLoad: () => {
	          this.showDialogLoader = false;
	        },
	        onClose: () => {
	          this.showDialogLoader = false;
	        }
	      });
	    },
	    openCloudFileDialog(serviceId) {
	      if (this.showCloudDialogLoader) {
	        return;
	      }
	      this.currentServiceId = serviceId;
	      this.showCloudDialogLoader = true;
	      const finalize = () => {
	        this.showCloudDialogLoader = false;
	        this.currentServiceId = null;
	      };
	      openCloudFileDialog({
	        dialogId: this.cloudDialogId,
	        uploader: this.uploader,
	        serviceId,
	        onLoad: finalize,
	        onClose: finalize
	      });
	    }
	  },
	  template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-file-wrap">
				<div class="disk-user-field-panel-card-box disk-user-field-panel-card-file" ref="upload">
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--upload">
						<div class="disk-user-field-panel-card-content">
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_UPLOAD_FILES') }}</div>
						</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box disk-user-field-panel-card-file" @click="openDiskFileDialog">
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--b24">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showDialogLoader" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_MY_DRIVE') }}</div>
						</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-divider"></div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Google]"
					@click="openCloudFileDialog(DocumentService.Google)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--google-docs">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Google" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Google]['name'] }}</div>
						</div>
					</div>
				</div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Office]"
					@click="openCloudFileDialog(DocumentService.Office)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--office365">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Office" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Office].name }}</div>
						</div>
					</div>
				</div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Dropbox]"
					@click="openCloudFileDialog(DocumentService.Dropbox)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--dropbox">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Dropbox" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Dropbox].name }}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const DocumentPanel = {
	  name: 'DocumentPanel',
	  components: {
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  inject: ['uploader', 'userFieldControl', 'getMessage'],
	  setup() {
	    return {
	      DocumentType,
	      isBoardsEnabled: userFieldSettings.isBoardsEnabled()
	    };
	  },
	  data() {
	    var _this$userFieldContro;
	    return {
	      currentServiceName: (_this$userFieldContro = this.userFieldControl.getCurrentDocumentService()) == null ? void 0 : _this$userFieldContro.name
	    };
	  },
	  computed: {
	    createServiceFormatted() {
	      const labelText = main_core.Loc.getMessage('DISK_UF_WIDGET_EDIT_SERVICE_LABEL');
	      const macros = '#NAME#';
	      const position = labelText.indexOf(macros);
	      const preText = labelText.slice(0, position);
	      const postText = labelText.slice(position + macros.length);
	      return `${preText}[link]${this.currentServiceName}[/link]${postText}`;
	    }
	  },
	  methods: {
	    renderSvg(documentType) {
	      return new ui_icons_generator.FileIcon({
	        name: documentType,
	        size: 36,
	        align: 'center'
	      }).generate().outerHTML;
	    },
	    createDocument(documentType) {
	      createDocumentDialog({
	        uploader: this.uploader,
	        documentType,
	        documentHandlers: Object.values(userFieldSettings.getDocumentServices()),
	        onAddFile: () => this.userFieldControl.showUploaderPanel()
	      });
	    },
	    openMenu() {
	      var _this$menu;
	      (_this$menu = this.menu) != null ? _this$menu : this.menu = new ui_system_menu.Menu({
	        bindElement: this.$refs.service,
	        autoHide: true,
	        offsetTop: 5,
	        items: this.getMenuItems()
	      });
	      this.menu.show();
	    },
	    getMenuItems() {
	      var _this$userFieldContro2;
	      const services = Object.values(userFieldSettings.getDocumentServices());
	      const currentServiceCode = (_this$userFieldContro2 = this.userFieldControl.getCurrentDocumentService()) == null ? void 0 : _this$userFieldContro2.code;
	      return services.map(service => ({
	        title: service.name,
	        isSelected: currentServiceCode === service.code,
	        onClick: () => {
	          BX.Disk.saveDocumentService(service.code);
	          this.currentServiceName = service.name;
	          this.menu.updateItems(this.getMenuItems());
	        }
	      }));
	    }
	  },
	  template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-doc-wrap">
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Docx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--doc">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Docx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_DOCX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Xlsx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--xls">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Xlsx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_XLSX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Pptx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--ppt">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Pptx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_PPTX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Board)" v-if="isBoardsEnabled">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--board">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_BOARD') }}</div>
					</div>
				</div>
			</div>
			<div class="disk-user-field-create-document-by-service" @click="openMenu">
				<RichLoc :text="createServiceFormatted" placeholder="[link]">
					<template #link="{ text }">
						<span class="disk-user-field-document-current-service" ref="service">{{ text }}</span>
					</template>
				</RichLoc>
			</div>
		</div>
	`
	};

	/**
	 * @memberof BX.Disk.Uploader
	 * @vue/component
	 */
	const UserFieldWidgetComponent = {
	  name: 'UserFieldWidget',
	  components: {
	    TileWidgetComponent: ui_uploader_tileWidget.TileWidgetComponent,
	    DocumentPanel
	  },
	  extends: ui_uploader_vue.VueUploaderComponent,
	  provide() {
	    return {
	      userFieldControl: this.userFieldControl,
	      postForm: this.userFieldControl.getMainPostForm(),
	      getMessage: this.getMessage
	    };
	  },
	  props: {
	    visibility: {
	      type: String,
	      default(props) {
	        const mainPostFormContext = main_core.Type.isElementNode(props.widgetOptions.eventObject);
	        return mainPostFormContext ? 'hidden' : 'both';
	      }
	    }
	  },
	  setup() {
	    return {
	      customUploaderOptions: UserFieldWidget.getDefaultUploaderOptions()
	    };
	  },
	  data() {
	    return {
	      documentsCollapsed: this.visibility === 'both',
	      priorityVisibility: null
	    };
	  },
	  computed: {
	    tileWidgetOptions() {
	      const widgetOptions = this.widgetOptions;
	      const tileWidgetOptions = main_core.Type.isPlainObject(widgetOptions.tileWidgetOptions) ? {
	        ...widgetOptions.tileWidgetOptions
	      } : {};
	      tileWidgetOptions.slots = main_core.Type.isPlainObject(tileWidgetOptions.slots) ? tileWidgetOptions.slots : {};
	      if (widgetOptions.withControlPanel !== false) {
	        tileWidgetOptions.slots[ui_uploader_tileWidget.TileWidgetSlot.AFTER_TILE_LIST] = ControlPanel;
	      }
	      tileWidgetOptions.insertIntoText = main_core.Type.isBoolean(widgetOptions.insertIntoText) ? widgetOptions.insertIntoText : this.userFieldControl.getMainPostForm() !== null;
	      tileWidgetOptions.showItemMenuButton = true;
	      tileWidgetOptions.events = tileWidgetOptions.events || {};
	      tileWidgetOptions.events['TileItem:onMenuCreate'] = event => {
	        const {
	          item,
	          menu
	        } = event.getData();
	        const itemMenu = new ItemMenu(this.userFieldControl, item, menu);
	        itemMenu.build();
	      };
	      if (this.userFieldControl.getMainPostForm() !== null) {
	        tileWidgetOptions.events.onInsertIntoText = event => {
	          const {
	            item
	          } = event.getData();
	          this.userFieldControl.getMainPostForm().insertIntoText(item);
	        };
	        tileWidgetOptions.enableDropzone = false;
	      }
	      const settingsMenu = new SettingsMenu(this.userFieldControl);
	      if (settingsMenu.hasItems()) {
	        tileWidgetOptions.showSettingsButton = true;
	        tileWidgetOptions.events['SettingsButton:onClick'] = event => {
	          const {
	            button
	          } = event.getData();
	          settingsMenu.toggle(button);
	        };
	      }
	      return tileWidgetOptions;
	    },
	    shouldShowCreateDocumentLink() {
	      return this.userFieldControl.canCreateDocuments() && this.documentsCollapsed && this.finalVisibility === 'both';
	    },
	    shouldShowDocuments() {
	      return this.userFieldControl.canCreateDocuments() && (this.finalVisibility === 'documents' || this.finalVisibility === 'both' && !this.documentsCollapsed);
	    },
	    finalVisibility() {
	      if (this.priorityVisibility !== null) {
	        return this.priorityVisibility;
	      }
	      return this.visibility;
	    }
	  },
	  beforeCreate() {
	    this.userFieldControl = new UserFieldControl(this);
	  },
	  beforeUnmount() {
	    this.userFieldControl.destroy();
	  },
	  methods: {
	    getMessage(code, replacements) {
	      return main_core.Loc.getMessage(code, replacements);
	    },
	    enableAutoCollapse() {
	      this.$refs.tileWidget.enableAutoCollapse();
	    },
	    getUploaderOptions() {
	      return UserFieldWidget.prepareUploaderOptions(this.uploaderOptions);
	    },
	    getUserFieldControl() {
	      return this.userFieldControl;
	    }
	  },
	  template: `
		<div
			class="disk-user-field-control"
			:class="{ '--has-files': items.length > 0, '--embedded': widgetOptions.isEmbedded }"
			:style="{ display: finalVisibility === 'hidden' ? 'none' : 'block' }"
			ref="container"
		>
			<div 
				class="disk-user-field-uploader-panel"
				:class="[{ '--hidden': finalVisibility !== 'uploader' && finalVisibility !== 'both' }]"
				ref="uploader-container"
			>
				<TileWidgetComponent
					:widgetOptions="tileWidgetOptions"
					:uploader-adapter="adapter"
					ref="tileWidget"
				/>
			</div>
			<div
				class="disk-user-field-create-document"
				v-if="shouldShowCreateDocumentLink"
				@click="documentsCollapsed = false"
			>{{ getMessage('DISK_UF_WIDGET_CREATE_DOCUMENT') }}</div>
			<div
				class="disk-user-field-document-panel"
				:class="{ '--single': finalVisibility !== 'both' }"
				ref="document-container"
				v-if="shouldShowDocuments"
			>
				<DocumentPanel />
			</div>
		</div>
	`
	};

	/**
	 * @memberof BX.Disk.Uploader
	 */
	class UserFieldWidget extends ui_uploader_vue.VueUploaderWidget {
	  constructor(uploaderOptions, options) {
	    const widgetOptions = main_core.Type.isPlainObject(options) ? {
	      ...options
	    } : {};
	    super(UserFieldWidget.prepareUploaderOptions(uploaderOptions), widgetOptions);
	  }
	  defineComponent() {
	    return UserFieldWidgetComponent;
	  }
	  static prepareUploaderOptions(uploaderOptions) {
	    return {
	      ...UserFieldWidget.getDefaultUploaderOptions(),
	      ...(main_core.Type.isPlainObject(uploaderOptions) ? uploaderOptions : {})
	    };
	  }
	  static getDefaultUploaderOptions() {
	    return {
	      controller: 'disk.uf.integration.diskUploaderController',
	      multiple: true,
	      maxFileSize: null
	    };
	  }
	}

	const sectionCreateDocument = 'create-document';
	const sectionCreateDocumentService = 'create-document-service';
	const importServices = userFieldSettings.getImportServices();
	const createServices = userFieldSettings.getDocumentServices();
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _menu$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _browseElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("browseElement");
	var _getSections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSections");
	var _getItems$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItems");
	var _browse = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("browse");
	var _getSelectImportServiceItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectImportServiceItem");
	var _getImportDocumentItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getImportDocumentItem");
	var _getCreateDocumentItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateDocumentItem");
	var _getCreateDocumentTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateDocumentTitle");
	var _getDocumentSvg = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentSvg");
	var _getSelectCreateServiceItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectCreateServiceItem");
	var _canCreateDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canCreateDocument");
	class UserFieldMenu {
	  constructor(params) {
	    Object.defineProperty(this, _canCreateDocument, {
	      value: _canCreateDocument2
	    });
	    Object.defineProperty(this, _getSelectCreateServiceItem, {
	      value: _getSelectCreateServiceItem2
	    });
	    Object.defineProperty(this, _getDocumentSvg, {
	      value: _getDocumentSvg2
	    });
	    Object.defineProperty(this, _getCreateDocumentTitle, {
	      value: _getCreateDocumentTitle2
	    });
	    Object.defineProperty(this, _getCreateDocumentItem, {
	      value: _getCreateDocumentItem2
	    });
	    Object.defineProperty(this, _getImportDocumentItem, {
	      value: _getImportDocumentItem2
	    });
	    Object.defineProperty(this, _getSelectImportServiceItem, {
	      value: _getSelectImportServiceItem2
	    });
	    Object.defineProperty(this, _browse, {
	      value: _browse2
	    });
	    Object.defineProperty(this, _getItems$1, {
	      value: _getItems2$1
	    });
	    Object.defineProperty(this, _getSections, {
	      value: _getSections2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menu$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _browseElement, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    if (!BX.Disk.getDocumentService()) {
	      const service = BX.Disk.isAvailableOnlyOffice() ? DocumentService.OnlyOffice : DocumentService.Local;
	      BX.Disk.saveDocumentService(service);
	    }
	  }
	  getMenu() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _menu$2)[_menu$2];
	  }
	  show(bindElement) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _menu$2))[_menu$2]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_menu$2] = new ui_system_menu.Menu({
	      id: `disk-user-field-menu-${Date.now()}`,
	      minWidth: 250,
	      sections: babelHelpers.classPrivateFieldLooseBase(this, _getSections)[_getSections](),
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getItems$1)[_getItems$1](),
	      ...babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].menuOptions
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _menu$2)[_menu$2].show(bindElement);
	  }
	}
	function _getSections2() {
	  return [{
	    code: sectionCreateDocument,
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE')
	  }, {
	    code: sectionCreateDocumentService
	  }];
	}
	function _getItems2$1() {
	  const items = [{
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_UPLOAD_FILES'),
	    icon: ui_iconSet_api_core.Outline.DOWNLOAD,
	    onClick: () => babelHelpers.classPrivateFieldLooseBase(this, _browse)[_browse]()
	  }, {
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_MY_DRIVE'),
	    icon: ui_iconSet_api_core.Outline.UPLOAD,
	    onClick: () => openDiskFileDialog({
	      dialogId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].dialogId,
	      uploader: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].uploader
	    })
	  }];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].compact === true) {
	    return items;
	  }
	  items.push(babelHelpers.classPrivateFieldLooseBase(this, _getSelectImportServiceItem)[_getSelectImportServiceItem](), babelHelpers.classPrivateFieldLooseBase(this, _getCreateDocumentItem)[_getCreateDocumentItem](DocumentType.Docx), babelHelpers.classPrivateFieldLooseBase(this, _getCreateDocumentItem)[_getCreateDocumentItem](DocumentType.Xlsx), babelHelpers.classPrivateFieldLooseBase(this, _getCreateDocumentItem)[_getCreateDocumentItem](DocumentType.Pptx), babelHelpers.classPrivateFieldLooseBase(this, _getCreateDocumentItem)[_getCreateDocumentItem](DocumentType.Board), babelHelpers.classPrivateFieldLooseBase(this, _getSelectCreateServiceItem)[_getSelectCreateServiceItem]());
	  return items;
	}
	function _browse2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement] = document.createElement('div');
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].uploader.assignBrowse(babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement].click();
	}
	function _getSelectImportServiceItem2() {
	  const items = [babelHelpers.classPrivateFieldLooseBase(this, _getImportDocumentItem)[_getImportDocumentItem](DocumentService.Google), babelHelpers.classPrivateFieldLooseBase(this, _getImportDocumentItem)[_getImportDocumentItem](DocumentService.Office), babelHelpers.classPrivateFieldLooseBase(this, _getImportDocumentItem)[_getImportDocumentItem](DocumentService.Dropbox)].filter(it => it);
	  if (items.length === 0) {
	    return null;
	  }
	  return {
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_EXTERNAL_DRIVES'),
	    subMenu: {
	      items
	    }
	  };
	}
	function _getImportDocumentItem2(documentService) {
	  if (!importServices[documentService]) {
	    return null;
	  }
	  return {
	    title: importServices[documentService].name,
	    onClick: () => openCloudFileDialog({
	      dialogId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].dialogId,
	      uploader: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].uploader,
	      serviceId: documentService
	    })
	  };
	}
	function _getCreateDocumentItem2(documentType) {
	  const cantCreateDocuments = !babelHelpers.classPrivateFieldLooseBase(this, _canCreateDocument)[_canCreateDocument]();
	  const boardsDisabled = documentType === DocumentType.Board && !userFieldSettings.isBoardsEnabled();
	  if (cantCreateDocuments || boardsDisabled) {
	    return null;
	  }
	  return {
	    sectionCode: sectionCreateDocument,
	    title: babelHelpers.classPrivateFieldLooseBase(this, _getCreateDocumentTitle)[_getCreateDocumentTitle](documentType),
	    svg: babelHelpers.classPrivateFieldLooseBase(this, _getDocumentSvg)[_getDocumentSvg](documentType),
	    onClick: () => createDocumentDialog({
	      uploader: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].uploader,
	      documentType,
	      documentHandlers: Object.values(userFieldSettings.getDocumentServices())
	    })
	  };
	}
	function _getCreateDocumentTitle2(documentType) {
	  return {
	    [DocumentType.Docx]: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_DOCX'),
	    [DocumentType.Xlsx]: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_XLSX'),
	    [DocumentType.Pptx]: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_PPTX'),
	    [DocumentType.Board]: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_BOARD')
	  }[documentType];
	}
	function _getDocumentSvg2(name) {
	  const size = 20;
	  const svg = new ui_icons_generator.FileIcon({
	    name,
	    size
	  }).generate();
	  main_core.Dom.style(svg, 'width', `${size}px`);
	  main_core.Dom.style(svg, 'height', `${size}px`);
	  return svg;
	}
	function _getSelectCreateServiceItem2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _canCreateDocument)[_canCreateDocument]()) {
	    return null;
	  }
	  return {
	    sectionCode: sectionCreateDocumentService,
	    title: main_core.Loc.getMessage('DISK_UF_WIDGET_CREATE_WITH'),
	    subtitle: createServices[BX.Disk.getDocumentService()].name,
	    closeOnSubItemClick: false,
	    subMenu: {
	      items: Object.keys(createServices).map(documentService => ({
	        title: createServices[documentService].name,
	        isSelected: BX.Disk.getDocumentService() === documentService,
	        onClick: () => {
	          BX.Disk.saveDocumentService(documentService);
	          babelHelpers.classPrivateFieldLooseBase(this, _menu$2)[_menu$2].updateItems(babelHelpers.classPrivateFieldLooseBase(this, _getItems$1)[_getItems$1]());
	        }
	      }))
	    }
	  };
	}
	function _canCreateDocument2() {
	  return userFieldSettings.canCreateDocuments() && Object.keys(createServices).length > 0;
	}

	exports.UserFieldWidget = UserFieldWidget;
	exports.UserFieldWidgetComponent = UserFieldWidgetComponent;
	exports.UserFieldControl = UserFieldControl;
	exports.UserFieldMenu = UserFieldMenu;
	exports.loadDiskFileDialog = loadDiskFileDialog;
	exports.openDiskFileDialog = openDiskFileDialog;
	exports.openCloudFileDialog = openCloudFileDialog;
	exports.createDocumentDialog = createDocumentDialog;

}((this.BX.Disk.Uploader = this.BX.Disk.Uploader || {}),BX.UI.Uploader,BX.UI.Uploader,BX.Event,BX.UI,BX.UI,BX,BX.UI.Vue3.Components,BX,BX.UI.System,BX.UI.Uploader,BX.UI.Icons.Generator,BX.UI.IconSet));
//# sourceMappingURL=disk.uploader.uf-file.bundle.js.map

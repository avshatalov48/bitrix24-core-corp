this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,ui_uploader_vue,ui_uploader_tileWidget,main_core_events,ui_uploader_core,main_core,main_popup) {
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
	  insertFile(item) {
	    const bbDelimiter = item.isImage ? '\n' : ' ';
	    const htmlDelimiter = item.isImage ? '<br>' : '&nbsp;';
	    main_core_events.EventEmitter.emit(this.getHtmlEditor(), 'OnInsertContent', [bbDelimiter + this.createItemBBCode(item) + bbDelimiter, htmlDelimiter + this.createItemHtml(item) + htmlDelimiter]);
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
	  selectItem(item) {
	    item.tileWidgetData.selected = true;
	  }
	  deselectItem(item) {
	    item.tileWidgetData.selected = false;
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
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getItems();
	    items.forEach(item => {
	      if (inserted.has(item.serverFileId)) {
	        hasInsertedItems = true;
	        this.selectItem(item);
	      } else {
	        this.deselectItem(item);
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getPhotoTemplateMode() === 'auto') {
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().setPhotoTemplate(hasInsertedItems ? 'gallery' : 'grid');
	    }
	  }
	  createItemHtml(item, id) {
	    const tagId = this.getHtmlEditor().SetBxTag(false, {
	      tag: babelHelpers.classPrivateFieldLooseBase(this, _parserId)[_parserId],
	      serverFileId: item.serverFileId,
	      hideContextMenu: true,
	      fileId: item.serverFileId
	    });
	    if (item.isImage) {
	      const imageSrc = this.getHtmlEditor().bbCode ? item.previewUrl : item.serverPreviewUrl;
	      const previewWidth = this.getHtmlEditor().bbCode ? item.previewWidth : item.serverPreviewWidth;
	      const previewHeight = this.getHtmlEditor().bbCode ? item.previewHeight : item.serverPreviewHeight;
	      const renderWidth = 600; // half size of imagePreviewWidth
	      const renderHeight = 600; // half size of imagePreviewHeight
	      const ratioWidth = renderWidth / previewWidth;
	      const ratioHeight = renderHeight / previewHeight;
	      const ratio = Math.min(ratioWidth, ratioHeight);
	      const useOriginalSize = ratio > 1; // image is too small
	      const width = useOriginalSize ? previewWidth : previewWidth * ratio;
	      const height = useOriginalSize ? previewHeight : previewHeight * ratio;
	      return `<img style="max-width: 90%;" width="${width}" height="${height}" data-bx-file-id="${main_core.Text.encode(item.serverFileId)}" id="${tagId}" src="${imageSrc}" title="${main_core.Text.encode(item.name)}" data-bx-paste-check="Y" />`;
	    } else if (item.customData.fileType === 'player') {
	      return `<img contenteditable="false" class="bxhtmled-player-surrogate" data-bx-file-id="${main_core.Text.encode(item.serverFileId)}" id="${tagId}" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-bx-paste-check="Y" />`;
	    }
	    return `<span contenteditable="false" data-bx-file-id="${main_core.Text.encode(item.serverFileId)}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${main_core.Text.encode(item.name)}</span>`;
	  }
	  createItemBBCode(item) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _tag)[_tag].replace('#id#', item.serverFileId);
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
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getItems();
	    const item = items.find(item => {
	      return item.serverFileId === attachedId || item.customData.objectId === objectId;
	    });
	    if (item) {
	      this.selectItem(item);
	      return this.createItemHtml(item, id);
	    }
	    return str;
	  });
	}
	function _unparse2(bxTag) {
	  const {
	    serverFileId
	  } = bxTag;
	  const items = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getUserFieldControl().getItems();
	  const item = items.find(item => {
	    return item.serverFileId === serverFileId;
	  });
	  if (item) {
	    return this.createItemBBCode(item);
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
	class MainPostForm extends main_core_events.EventEmitter {
	  constructor(userFieldControl, options) {
	    super();
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
	                const file = event.getTarget();
	                const item = this.getUserFieldControl().getItem(file.getId());
	                if (item) {
	                  this.getUserFieldControl().showUploaderPanel();
	                  this.getParser().insertFile(item);
	                }
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
	          const item = this.getUserFieldControl().getItem(file.getId());
	          if (item) {
	            this.getUserFieldControl().showUploaderPanel();
	            this.getParser().insertFile(item);
	          }
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
	                html: this.getParser().createItemHtml(item)
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
	class UserFieldControl extends main_core_events.EventEmitter {
	  constructor(widgetComponent) {
	    super();
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
	    this.setEventNamespace('BX.Disk.Uploader.Integration');
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent] = widgetComponent;
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter] = widgetComponent.adapter;
	    const options = main_core.Type.isPlainObject(widgetComponent.widgetOptions) ? widgetComponent.widgetOptions : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName] = main_core.Type.isStringFilled(options.photoTemplateFieldName) ? options.photoTemplateFieldName : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _allowDocumentFieldName)[_allowDocumentFieldName] = main_core.Type.isStringFilled(options.allowDocumentFieldName) ? options.allowDocumentFieldName : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribe('Item:onComplete', event => {
	      const item = event.getData().item;
	      this.setDocumentEdit(item);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribe('Item:onRemove', event => {
	      const item = event.getData().item;
	      this.removeAllowDocumentEditInput(item);
	    });
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
	  static getById(id) {
	    return instances.get(id) || null;
	  }
	  static getInstances() {
	    return [...instances.values()];
	  }
	  canCreateDocuments() {
	    const settings = main_core.Extension.getSettings('disk.uploader.user-field-widget');
	    const canCreateDocuments = settings.get('canCreateDocuments', false);
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
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].show(true);
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].hide(true);
	    this.hideUploaderPanel();
	    this.hideDocumentPanel();
	  }
	  showUploaderPanel() {
	    this.show();
	    this.hideDocumentPanel();
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].showUploaderPanel();
	    if (this.getMainPostForm()) {
	      this.getMainPostForm().selectFileButton();
	    }
	  }
	  hideUploaderPanel() {
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].hideUploaderPanel();
	    if (this.getMainPostForm()) {
	      this.getMainPostForm().deselectFileButton();
	    }
	  }
	  showDocumentPanel() {
	    if (!this.canCreateDocuments()) {
	      return;
	    }
	    this.show();
	    this.hideUploaderPanel();
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].showDocumentPanel();
	    if (this.getMainPostForm()) {
	      this.getMainPostForm().selectCreateDocumentButton();
	    }
	  }
	  hideDocumentPanel() {
	    if (!this.canCreateDocuments()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetComponent)[_widgetComponent].hideDocumentPanel();
	    if (this.getMainPostForm()) {
	      this.getMainPostForm().deselectCreateDocumentButton();
	    }
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
	    return this.canAllowDocumentEdit() && item.customData['isEditable'] === true && item.customData['canUpdate'] === true;
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
	    allowEdit = allowEdit === null ? item.customData['allowEdit'] === true : allowEdit;
	    input.value = allowEdit ? 1 : 0;
	    const file = this.getFile(item.id);
	    file.setCustomData('allowEdit', allowEdit);
	  }
	  canChangePhotoTemplate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _photoTemplateFieldName)[_photoTemplateFieldName] !== null;
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
	    const settings = main_core.Extension.getSettings('disk.uploader.user-field-widget');
	    const documentHandlers = settings.get('documentHandlers', {});
	    if (main_core.Type.isPlainObject(documentHandlers)) {
	      return documentHandlers;
	    }
	    return {};
	  }
	  getCurrentDocumentService() {
	    let currentServiceCode = BX.Disk.getDocumentService();
	    if (!currentServiceCode && BX.Disk.isAvailableOnlyOffice()) {
	      currentServiceCode = 'onlyoffice';
	    } else if (!currentServiceCode) {
	      currentServiceCode = 'l';
	    }
	    return this.getDocumentServices()[currentServiceCode] || null;
	  }
	  getImportServices() {
	    const settings = main_core.Extension.getSettings('disk.uploader.user-field-widget');
	    const importHandlers = settings.get('importHandlers', {});
	    if (main_core.Type.isPlainObject(importHandlers)) {
	      return importHandlers;
	    }
	    return {};
	  }
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
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    const firstItemId = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuItems()[0]) == null ? void 0 : _babelHelpers$classPr2.id) != null ? _babelHelpers$classPr : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getPopupWindow().setMaxWidth(500);
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	      id: 'filesize',
	      text: main_core.Loc.getMessage('DISK_UF_WIDGET_FILE_SIZE', {
	        '#filesize#': babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].sizeFormatted
	      }),
	      disabled: true
	    }, firstItemId);
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	      delimiter: true
	    }, firstItemId);
	    const postForm = babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].getMainPostForm();
	    if (postForm) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        id: 'insert-into-text',
	        text: main_core.Loc.getMessage('DISK_UF_WIDGET_INSERT_INTO_THE_TEXT'),
	        onclick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].close();
	          postForm.getParser().insertFile(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item]);
	        }
	      }, firstItemId);
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].canItemAllowEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        delimiter: true
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        id: 'allow-edit',
	        className: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['allowEdit'] === true ? 'disk-user-field-item-checked' : '',
	        text: main_core.Loc.getMessage('DISK_UF_WIDGET_ALLOW_DOCUMENT_EDIT'),
	        onclick: (event, menuItem) => {
	          if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['allowEdit'] === true) {
	            babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].setDocumentEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item], false);
	          } else {
	            babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$1)[_userFieldControl$1].setDocumentEdit(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item], true);
	          }
	          menuItem.getMenuWindow().close();
	        }
	      });
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['canRename']) {
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
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['storage'])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	        delimiter: true
	        //text: Loc.getMessage('DISK_UF_WIDGET_SAVED_IN_DISK_FOLDER'),
	      });

	      if (babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['canMove']) {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	          id: 'storage',
	          text: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['storage'] + '&mldr;',
	          onclick: () => {
	            this.openFolderDialog();
	            babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].close();
	          },
	          disabled: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].tileWidgetData.selected === true
	        });
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].addMenuItem({
	          id: 'storage',
	          text: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['storage'],
	          disabled: true
	        });
	      }
	    }
	  }
	  rename(newName) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('disk.api.commonActions.rename', {
	        data: {
	          objectId: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['objectId'],
	          newName: newName,
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
	              objectId: babelHelpers.classPrivateFieldLooseBase(this, _item)[_item].customData['objectId'],
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
	    if (babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] !== null) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] = new main_popup.Menu({
	      bindElement: button.getContainer(),
	      className: 'disk-user-field-settings-popup',
	      angle: true,
	      autoHide: true,
	      offsetLeft: 16,
	      cacheable: false,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems](),
	      events: {
	        onShow: () => {
	          button.select();
	        },
	        onDestroy: () => {
	          button.deselect();
	          babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] = null;
	        }
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1];
	  }
	  show(button) {
	    this.getMenu(button).show();
	  }
	  toggle(button) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] !== null && babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].getPopupWindow().isShown()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].close();
	    } else {
	      this.show(button);
	    }
	  }
	  hide() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] !== null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].close();
	    }
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
	    className: babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].getPhotoTemplate() === 'grid' ? 'disk-user-field-item-checked' : '',
	    text: main_core.Loc.getMessage('DISK_UF_WIDGET_ALLOW_PHOTO_COLLAGE'),
	    onclick: (event, menuItem) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplateMode('manual');
	      if (babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].getPhotoTemplate() === 'grid') {
	        babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplate('gallery');
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _userFieldControl$2)[_userFieldControl$2].setPhotoTemplate('grid');
	      }
	      menuItem.getMenuWindow().close();
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
	  options = main_core.Type.isPlainObject(options) ? options : {};
	  const dialogId = main_core.Type.isStringFilled(options.dialogId) ? options.dialogId : `cloud-dialog-${main_core.Text.getRandom(5)}`;
	  const serviceId = main_core.Type.isStringFilled(options.serviceId) ? options.serviceId : `gdrive`;
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
	    if (BX.DiskFileDialog.popupWindow === null) {
	      BX.DiskFileDialog.openDialog(dialogId);
	    }
	  });
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
	  template: `<span ref="container"></span>`
	};

	const ControlPanel = {
	  name: 'ControlPanel',
	  inject: ['userFieldControl', 'uploader', 'getMessage'],
	  components: {
	    Loader
	  },
	  data: () => ({
	    showDialogLoader: false,
	    showCloudDialogLoader: false,
	    currentServiceId: null
	  }),
	  created() {
	    this.fileDialogId = `file-dialog-${main_core.Text.getRandom(5)}`;
	    this.cloudDialogId = `cloud-dialog-${main_core.Text.getRandom(5)}`;
	    this.importServices = this.userFieldControl.getImportServices();
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
	  // language=Vue
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
				v-if="importServices['gdrive']"
				@click="openCloudFileDialog('gdrive')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--google-docs">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'gdrive'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['gdrive']['name'] }}</div>
					</div>
				</div>
			</div>
			<div 
				class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
				v-if="importServices['office365']"
				@click="openCloudFileDialog('office365')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--office365">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'office365'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['office365']['name'] }}</div>
					</div>
				</div>
			</div>
			<div 
				class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
				v-if="importServices['dropbox']"
				@click="openCloudFileDialog('dropbox')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--dropbox">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'dropbox'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['dropbox']['name'] }}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	`
	};

	let _$3 = t => t,
	  _t$3,
	  _t2$2;
	const DocumentPanel = {
	  name: 'DocumentPanel',
	  inject: ['uploader', 'userFieldControl', 'getMessage'],
	  props: {
	    item: {
	      type: Object,
	      default: {}
	    }
	  },
	  created() {
	    this.menu = null;
	    this.currentServiceNode = null;
	  },
	  mounted() {
	    const labelText = main_core.Loc.getMessage('DISK_UF_WIDGET_EDIT_SERVICE_LABEL') || '';
	    const macros = '#NAME#';
	    const position = labelText.indexOf(macros);
	    if (position !== -1) {
	      var _this$userFieldContro;
	      const preText = labelText.substring(0, position);
	      const postText = labelText.substring(position + macros.length);
	      this.currentServiceNode = main_core.Tag.render(_t$3 || (_t$3 = _$3`
				<span class="disk-user-field-document-current-service">${0}</span>
			`), (_this$userFieldContro = this.userFieldControl.getCurrentDocumentService()) == null ? void 0 : _this$userFieldContro.name);
	      const label = main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
				<span>
					<span>${0}</span>
					${0}
					<span>${0}</span>
				</span>`), preText, this.currentServiceNode, postText);
	      main_core.Dom.append(label, this.$refs['document-services']);
	    }
	  },
	  methods: {
	    createDocument(documentType) {
	      // TODO: load disk and disk.document extensions on demand
	      if (!BX.Disk.getDocumentService() && BX.Disk.isAvailableOnlyOffice()) {
	        BX.Disk.saveDocumentService('onlyoffice');
	      } else if (!BX.Disk.getDocumentService()) {
	        BX.Disk.saveDocumentService('l');
	      }
	      if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk()) {
	        BX.Disk.Document.Local.Instance.createFile({
	          type: documentType
	        }).then(response => {
	          if (response.status === 'success') {
	            this.uploader.addFile(`n${response.object.id}`, {
	              name: response.object.name,
	              preload: true
	            });
	            this.userFieldControl.showUploaderPanel();
	          }
	        });
	      } else {
	        const createProcess = new BX.Disk.Document.CreateProcess({
	          typeFile: documentType,
	          serviceCode: BX.Disk.getDocumentService(),
	          onAfterSave: (response, fileData) => {
	            if (response.status !== 'success') {
	              return;
	            }
	            if (fileData && fileData.object) {
	              this.uploader.addFile(`n${fileData.object.id}`, {
	                name: fileData.object.name,
	                size: fileData.object.sizeInt,
	                preload: true
	              });
	              this.userFieldControl.showUploaderPanel();
	            } else if (response.objectId) {
	              this.uploader.addFile(`n${response.objectId}`, {
	                name: Type.isStringFilled(response.newName) ? response.newName : '',
	                preload: true
	              });
	              this.userFieldControl.showUploaderPanel();
	            }
	          }
	        });
	        createProcess.start();
	      }
	    },
	    openMenu() {
	      if (this.menu !== null) {
	        this.menu.destroy();
	        return;
	      }
	      this.menu = new main_popup.Menu({
	        bindElement: this.currentServiceNode,
	        className: 'disk-user-field-settings-popup',
	        angle: true,
	        autoHide: true,
	        offsetTop: 5,
	        cacheable: false,
	        items: this.getMenuItems(),
	        events: {
	          onDestroy: () => {
	            this.menu = null;
	          }
	        }
	      });
	      this.menu.show();
	    },
	    getMenuItems() {
	      var _this$userFieldContro2;
	      const items = [];
	      const currentServiceCode = (_this$userFieldContro2 = this.userFieldControl.getCurrentDocumentService()) == null ? void 0 : _this$userFieldContro2.code;
	      const services = Object.values(this.userFieldControl.getDocumentServices());
	      services.forEach(service => {
	        items.push({
	          text: service.name,
	          className: currentServiceCode === service.code ? 'disk-user-field-item-checked' : 'disk-user-field-item-stub',
	          onclick: (event, item) => {
	            BX.Disk.saveDocumentService(service.code);
	            this.currentServiceNode.textContent = service.name;
	            this.menu.close();
	          }
	        });
	      });
	      return items;
	    }
	  },
	  // language=Vue
	  template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-doc-wrap">
				<div class="disk-user-field-panel-card-box" @click="createDocument('docx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--doc">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_DOCX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument('xlsx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--xls">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_XLSX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument('pptx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--ppt">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_PPTX') }}</div>
					</div>
				</div>
			</div>
			<div class="disk-user-field-create-document-by-service" @click="openMenu" ref="document-services"></div>
		</div>
	`
	};

	const InsertIntoTextButton = {
	  name: 'InsertIntoTextButton',
	  inject: ['uploader', 'postForm'],
	  props: {
	    item: {
	      type: Object,
	      default: {}
	    }
	  },
	  methods: {
	    click() {
	      this.postForm.getParser().insertFile(this.item);
	    },
	    handleMouseEnter(event) {
	      if (this.hintPopup) {
	        return;
	      }
	      const targetNode = event.currentTarget;
	      const targetNodeWidth = targetNode.offsetWidth;
	      this.hintPopup = new main_popup.Popup({
	        content: main_core.Loc.getMessage('DISK_UF_WIDGET_INSERT_INTO_THE_TEXT'),
	        cacheable: false,
	        animation: 'fading-slide',
	        bindElement: targetNode,
	        offsetTop: 0,
	        bindOptions: {
	          position: 'top'
	        },
	        darkMode: true,
	        events: {
	          onClose: () => {
	            this.hintPopup.destroy();
	            this.hintPopup = null;
	          },
	          onShow: event => {
	            const popup = event.getTarget();
	            const popupWidth = popup.getPopupContainer().offsetWidth;
	            const offsetLeft = targetNodeWidth / 2 - popupWidth / 2;
	            const angleShift = main_popup.Popup.getOption('angleLeftOffset') - main_popup.Popup.getOption('angleMinTop');
	            popup.setAngle({
	              offset: popupWidth / 2 - angleShift
	            });
	            popup.setOffset({
	              offsetLeft: offsetLeft + main_popup.Popup.getOption('angleLeftOffset')
	            });
	          }
	        }
	      });
	      this.hintPopup.show();
	    },
	    handleMouseLeave(event) {
	      if (this.hintPopup) {
	        this.hintPopup.close();
	        this.hintPopup = null;
	      }
	    }
	  },
	  // language=Vue
	  template: `
		<div 
			class="disk-user-field-insert-into-text-button"
			:class="[{ '--inserted': item.tileWidgetData.selected }]"
			@mouseenter="handleMouseEnter" 
			@mouseleave="handleMouseLeave" 
			@click="click"
		></div>
	`
	};

	/**
	 * @memberof BX.Disk.Uploader
	 */
	const UserFieldWidgetComponent = {
	  name: 'UserFieldWidget',
	  extends: ui_uploader_vue.VueUploaderComponent,
	  components: {
	    TileWidgetComponent: ui_uploader_tileWidget.TileWidgetComponent,
	    DocumentPanel
	  },
	  setup() {
	    return {
	      customUploaderOptions: UserFieldWidget.getDefaultUploaderOptions()
	    };
	  },
	  data() {
	    const options = this.widgetOptions;
	    return {
	      controlVisibility: main_core.Type.isBoolean(options.controlVisibility) ? options.controlVisibility : true,
	      uploaderPanelVisibility: main_core.Type.isBoolean(options.uploaderPanelVisibility) ? options.uploaderPanelVisibility : true,
	      documentPanelVisibility: main_core.Type.isBoolean(options.documentPanelVisibility) ? options.documentPanelVisibility : false
	    };
	  },
	  provide() {
	    return {
	      userFieldControl: this.userFieldControl,
	      postForm: this.userFieldControl.getMainPostForm(),
	      getMessage: this.getMessage
	    };
	  },
	  beforeCreate() {
	    this.userFieldControl = new UserFieldControl(this);
	  },
	  methods: {
	    getMessage(code, replacements) {
	      return main_core.Loc.getMessage(code, replacements);
	    },
	    show(forceUpdate = false) {
	      if (forceUpdate) {
	        this.$refs.container.style.display = 'block';
	      }
	      this.controlVisibility = true;
	    },
	    hide(forceUpdate = false) {
	      if (forceUpdate) {
	        this.$refs.container.style.display = 'none';
	      }
	      this.controlVisibility = false;
	    },
	    showUploaderPanel() {
	      this.uploaderPanelVisibility = true;
	    },
	    hideUploaderPanel() {
	      this.uploaderPanelVisibility = false;
	    },
	    showDocumentPanel() {
	      this.documentPanelVisibility = true;
	    },
	    hideDocumentPanel() {
	      this.documentPanelVisibility = false;
	    },
	    enableAutoCollapse() {
	      this.$refs.tileWidget.enableAutoCollapse();
	    },
	    getUploaderOptions() {
	      return UserFieldWidget.prepareUploaderOptions(this.uploaderOptions);
	    }
	  },
	  computed: {
	    tileWidgetOptions() {
	      const tileWidgetOptions = main_core.Type.isPlainObject(this.widgetOptions.tileWidgetOptions) ? Object.assign({}, this.widgetOptions.tileWidgetOptions) : {};
	      tileWidgetOptions.slots = main_core.Type.isPlainObject(tileWidgetOptions.slots) ? tileWidgetOptions.slots : {};
	      tileWidgetOptions.slots[ui_uploader_tileWidget.TileWidgetSlot.AFTER_TILE_LIST] = ControlPanel;
	      if (this.userFieldControl.getMainPostForm()) {
	        tileWidgetOptions.slots[ui_uploader_tileWidget.TileWidgetSlot.ITEM_EXTRA_ACTION] = InsertIntoTextButton;
	      }
	      tileWidgetOptions.showItemMenuButton = true;
	      tileWidgetOptions.events = {
	        'TileItem:onMenuCreate': event => {
	          const {
	            item,
	            menu
	          } = event.getData();
	          const itemMenu = new ItemMenu(this.userFieldControl, item, menu);
	          itemMenu.build();
	        }
	      };
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
	    }
	  },
	  // language=Vue
	  template: `
		<div 
			class="disk-user-field-control" 
			:class="[{ '--has-files': this.items.length > 0 }]"
			:style="{ display: controlVisibility ? 'block' : 'none' }"
			ref="container"
		>
			<div 
				class="disk-user-field-uploader-panel"
				:class="[{ '--hidden': !uploaderPanelVisibility }]"
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
				v-if="this.userFieldControl.canCreateDocuments() && !this.userFieldControl.getMainPostForm() && !documentPanelVisibility"
				@click="documentPanelVisibility = true"
			>{{ getMessage('DISK_UF_WIDGET_CREATE_DOCUMENT') }}</div>

			<div 
				class="disk-user-field-document-panel"
				:class="{ '--single': this.userFieldControl.getMainPostForm() !== null }"
				ref="document-container"
				v-if="this.userFieldControl.canCreateDocuments() && documentPanelVisibility"
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
	    const widgetOptions = main_core.Type.isPlainObject(options) ? Object.assign({}, options) : {};
	    super(UserFieldWidget.prepareUploaderOptions(uploaderOptions), widgetOptions);
	  }
	  defineComponent() {
	    return UserFieldWidgetComponent;
	  }
	  static prepareUploaderOptions(uploaderOptions) {
	    return Object.assign({}, UserFieldWidget.getDefaultUploaderOptions(), main_core.Type.isPlainObject(uploaderOptions) ? uploaderOptions : {});
	  }
	  static getDefaultUploaderOptions() {
	    return {
	      controller: 'disk.uf.integration.diskUploaderController',
	      multiple: true,
	      maxFileSize: null
	    };
	  }
	}

	exports.UserFieldWidget = UserFieldWidget;
	exports.UserFieldWidgetComponent = UserFieldWidgetComponent;
	exports.UserFieldControl = UserFieldControl;
	exports.loadDiskFileDialog = loadDiskFileDialog;
	exports.openDiskFileDialog = openDiskFileDialog;
	exports.openCloudFileDialog = openCloudFileDialog;

}((this.BX.Disk.Uploader = this.BX.Disk.Uploader || {}),BX.UI.Uploader,BX.UI.Uploader,BX.Event,BX.UI.Uploader,BX,BX.Main));
//# sourceMappingURL=disk.uploader.uf-file.bundle.js.map

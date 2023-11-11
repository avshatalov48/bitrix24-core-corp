this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_activity_todoEditor,main_core_events,main_popup,ui_tour,calendar_sharing_interface,crm_zoom,main_core) {
	'use strict';

	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _isReadonly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isReadonly");
	var _menuBarContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuBarContainer");
	class Context {
	  constructor(params) {
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isReadonly, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _menuBarContainer, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = params.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _isReadonly)[_isReadonly] = params.isReadonly;
	    babelHelpers.classPrivateFieldLooseBase(this, _menuBarContainer)[_menuBarContainer] = params.menuBarContainer;
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId];
	  }
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	  }
	  isReadonly() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isReadonly)[_isReadonly];
	  }
	  getMenuBarContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _menuBarContainer)[_menuBarContainer];
	  }
	}

	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _eventEmitter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("eventEmitter");
	var _isVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isVisible");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	class Item {
	  constructor() {
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _eventEmitter, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isVisible, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	  }
	  initialize(context, settings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = context;
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = settings;
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter)[_eventEmitter] = new main_core_events.EventEmitter();
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter)[_eventEmitter].setEventNamespace('BX.Crm.Timeline.MenuBar');
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].isReadonly() && this.supportsLayout()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = this.createLayout();
	      main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], this.getMenuBarContainer());
	      this.initializeLayout();
	    }
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getEntityTypeId();
	  }
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getEntityId();
	  }
	  getMenuBarContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getMenuBarContainer();
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  supportsLayout() {
	    return true;
	  }
	  activate() {
	    if (this.supportsLayout()) {
	      this.setVisible(true);
	    } else {
	      this.showSlider();
	    }
	  }
	  deactivate() {
	    this.setVisible(false);
	  }
	  showSlider() {
	    throw new Error('Method showSlider() must be overridden');
	  }
	  getSetting(setting, defaultValue = null) {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings][setting]) != null ? _babelHelpers$classPr : defaultValue;
	  }
	  getSettings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings];
	  }
	  setVisible(visible) {
	    visible = !!visible;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isVisible)[_isVisible] === visible) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isVisible)[_isVisible] = visible;
	    const container = this.getContainer();
	    if (!container) {
	      return;
	    }
	    if (visible) {
	      main_core.Dom.removeClass(container, '--hidden');
	      this.onShow();
	    } else {
	      this.onHide();
	      main_core.Dom.addClass(container, '--hidden');
	    }
	  }
	  isVisible() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isVisible)[_isVisible];
	  }
	  setFocused(isFocused) {
	    const container = this.getContainer();
	    if (!container) {
	      return;
	    }
	    if (isFocused) {
	      main_core.Dom.addClass(container, '--focus');
	    } else {
	      main_core.Dom.removeClass(container, '--focus');
	    }
	  }
	  addFinishEditListener(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter)[_eventEmitter].subscribe(Item.ON_FINISH_EDIT_EVENT, callback);
	  }
	  emitFinishEditEvent() {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter)[_eventEmitter].emit(Item.ON_FINISH_EDIT_EVENT);
	  }
	  createLayout() {
	    throw new Error('Method createLayout() must be overridden');
	  }
	  initializeLayout() {}
	  onShow() {}
	  onHide() {}
	}
	Item.ON_FINISH_EDIT_EVENT = 'onFinishEdit';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _toDoEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toDoEditor");
	var _todoEditorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("todoEditorContainer");
	var _saveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveButton");
	var _createEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createEditor");
	var _onChangeDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeDescription");
	class ToDo extends Item {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onChangeDescription, {
	      value: _onChangeDescription2
	    });
	    Object.defineProperty(this, _createEditor, {
	      value: _createEditor2
	    });
	    Object.defineProperty(this, _toDoEditor, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _todoEditorContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _saveButton, {
	      writable: true,
	      value: null
	    });
	  }
	  createLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _todoEditorContainer)[_todoEditorContainer] = main_core.Tag.render(_t || (_t = _`<div></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton] = main_core.Tag.render(_t2 || (_t2 = _`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-disabled" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SAVE_BUTTON'));
	    return main_core.Tag.render(_t3 || (_t3 = _`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-todo --hidden">
			${0}
			<div class="crm-entity-stream-content-new-comment-btn-container">
				${0}
				<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>
			</div>
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _todoEditorContainer)[_todoEditorContainer], babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	  }
	  initializeLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _createEditor)[_createEditor]();
	  }
	  onSaveButtonClick(e) {
	    if (main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-wait') || main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled')) {
	      return;
	    }
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-wait');
	    const removeButtonWaitClass = () => main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-wait');
	    this.save().then(() => removeButtonWaitClass(), () => removeButtonWaitClass());
	  }
	  onCancelButtonClick() {
	    this.cancel();
	    this.emitFinishEditEvent();
	  }
	  save() {
	    if (main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled')) {
	      return false;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].save().then(response => {
	      if (main_core.Type.isArray(response.errors) && response.errors.length) {
	        return false;
	      }
	      this.cancel();
	      this.emitFinishEditEvent();
	      return true;
	    });
	  }
	  cancel() {
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].clearValue();
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled');
	    this.setFocused(false);
	  }
	  bindInputHandlers() {
	    // do nothing
	  }
	  setParentActivityId(activityId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].setParentActivityId(activityId);
	  }
	  setDeadLine(deadLine) {
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].setDeadline(deadLine);
	  }
	  focus() {
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].setFocused();
	  }
	}
	function _createEditor2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor] = new crm_activity_todoEditor.TodoEditor({
	    container: babelHelpers.classPrivateFieldLooseBase(this, _todoEditorContainer)[_todoEditorContainer],
	    defaultDescription: '',
	    ownerTypeId: this.getEntityTypeId(),
	    ownerId: this.getEntityId(),
	    currentUser: this.getSetting('currentUser'),
	    events: {
	      onFocus: this.setFocused.bind(this, true),
	      onChangeDescription: babelHelpers.classPrivateFieldLooseBase(this, _onChangeDescription)[_onChangeDescription].bind(this)
	    },
	    enableCalendarSync: this.getSetting('enableTodoCalendarSync', false)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].show();
	}
	function _onChangeDescription2(event) {
	  let {
	    description
	  } = event.getData();
	  description = description.trim();
	  if (!description.length && !main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled')) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled');
	  } else if (description.length && main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled')) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled');
	  }
	}

	/** @memberof BX.Crm.Timeline.MenuBar */

	class WithEditor extends Item {
	  initializeLayout() {
	    this._ownerTypeId = this.getEntityTypeId();
	    this._ownerId = this.getEntityId();
	    this._ghostInput = null;
	    this._saveButtonHandler = BX.delegate(this.onSaveButtonClick, this);
	    this._cancelButtonHandler = BX.delegate(this.onCancelButtonClick, this);
	    this._focusHandler = BX.delegate(this.onFocus, this);
	    this._blurHandler = BX.delegate(this.onBlur, this);
	    this._keyupHandler = BX.delegate(this.resizeForm, this);
	    this._delayedKeyupHandler = BX.delegate(function () {
	      setTimeout(this.resizeForm.bind(this), 0);
	    }, this);
	    this._hideButtonsOnBlur = true;
	    this.bindInputHandlers();
	    this.doInitialize();
	  }
	  doInitialize() {}
	  bindInputHandlers() {
	    BX.bind(this._input, "focus", this._focusHandler);
	    BX.bind(this._input, "blur", this._blurHandler);
	    BX.bind(this._input, "keyup", this._keyupHandler);
	    BX.bind(this._input, "cut", this._delayedKeyupHandler);
	    BX.bind(this._input, "paste", this._delayedKeyupHandler);
	  }
	  onFocus(e) {
	    this.setFocused(true);
	  }
	  onBlur(e) {
	    if (!this._hideButtonsOnBlur) {
	      return;
	    }
	    if (this._input.value === "") {
	      window.setTimeout(BX.delegate(function () {
	        this.setFocused(false);
	        this._input.style.minHeight = "";
	      }, this), 200);
	    }
	  }
	  onSaveButtonClick(e) {
	    main_core.Dom.addClass(this._saveButton, 'ui-btn-wait');
	    const removeButtonWaitClass = () => main_core.Dom.removeClass(this._saveButton, 'ui-btn-wait');
	    const saveResult = this.save();
	    if (saveResult instanceof BX.Promise || saveResult instanceof Promise) {
	      saveResult.then(() => removeButtonWaitClass(), () => removeButtonWaitClass());
	    } else {
	      removeButtonWaitClass();
	    }
	  }
	  onCancelButtonClick() {
	    this.cancel();
	    this.emitFinishEditEvent();
	  }
	  save() {}
	  cancel() {}
	  release() {
	    if (this._ghostInput) {
	      this._ghostInput = BX.remove(this._ghostInput);
	    }
	  }
	  ensureGhostCreated() {
	    if (this._ghostInput) {
	      return this._ghostInput;
	    }
	    this._ghostInput = BX.create('div', {
	      props: {
	        className: 'crm-entity-stream-content-new-comment-textarea-shadow'
	      },
	      text: this._input.value
	    });
	    this._ghostInput.style.width = this._input.offsetWidth + 'px';
	    document.body.appendChild(this._ghostInput);
	    return this._ghostInput;
	  }
	  resizeForm() {
	    const ghost = this.ensureGhostCreated();
	    const computedStyle = getComputedStyle(this._input);
	    const diff = parseInt(computedStyle.paddingBottom) + parseInt(computedStyle.paddingTop) + parseInt(computedStyle.borderTopWidth) + parseInt(computedStyle.borderBottomWidth) || 0;
	    ghost.innerHTML = BX.util.htmlspecialchars(this._input.value.replace(/[\r\n]{1}/g, '<br>'));
	    this._input.style.minHeight = ghost.scrollHeight + diff + 'px';
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4;

	/** @memberof BX.Crm.Timeline.MenuBar */
	class Comment extends WithEditor {
	  createLayout() {
	    this._saveButton = main_core.Tag.render(_t$1 || (_t$1 = _$1`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SEND'));
	    this._cancelButton = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	    this._input = main_core.Tag.render(_t3$1 || (_t3$1 = _$1`<textarea  rows="1" class="crm-entity-stream-content-new-comment-textarea" placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_COMMENT_PLACEHOLDER'));
	    return main_core.Tag.render(_t4 || (_t4 = _$1`<div class="crm-entity-stream-content-new-detail --hidden">
					${0}
					<div class="crm-entity-stream-content-new-comment-btn-container">
						${0}
						${0}
					</div>
				</div>`), this._input, this._saveButton, this._cancelButton);
	  }
	  doInitialize() {
	    this._postForm = null;
	    this._editor = null;
	    this._isRequestRunning = false;
	    this._isLocked = false;
	    BX.unbind(this._input, "blur", this._blurHandler);
	    BX.unbind(this._input, "keyup", this._keyupHandler);
	  }
	  loadEditor() {
	    this._editorName = 'CrmTimeLineComment0';
	    if (this._postForm) return;
	    BX.ajax.runAction("crm.api.timeline.loadEditor", {
	      data: {
	        name: this._editorName
	      }
	    }).then(this.onLoadEditorSuccess.bind(this));
	  }
	  onLoadEditorSuccess(result) {
	    const html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
	    BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor, this)).then(BX.delegate(this.addEvents, this));
	  }
	  addEvents() {
	    BX.addCustomEvent(this._editorContainer.firstElementChild, 'onFileIsAppended', BX.delegate(function (id, item) {
	      BX.addClass(this._saveButton, 'ui-btn-disabled');
	      BX.addClass(this._saveButton, 'ui-btn-clock');
	      this._saveButton.removeEventListener("click", this._saveButtonHandler);
	    }, this));
	    BX.addCustomEvent(this._editorContainer.firstElementChild, 'onFileIsAdded', BX.delegate(function (file, controller, obj, blob) {
	      BX.removeClass(this._saveButton, 'ui-btn-clock');
	      BX.removeClass(this._saveButton, 'ui-btn-disabled');
	      this._saveButton.addEventListener("click", this._saveButtonHandler);
	    }, this));
	  }
	  showEditor() {
	    if (LHEPostForm) {
	      window.setTimeout(BX.delegate(function () {
	        this._postForm = LHEPostForm.getHandler(this._editorName);
	        this._editor = BXHtmlEditor.Get(this._editorName);
	        BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
	      }, this), 100);
	    }
	  }
	  onFocus(e) {
	    this._input.style.display = 'none';
	    if (this._editor && this._postForm) {
	      this._postForm.eventNode.style.display = 'block';
	      this._editor.Focus();
	    } else {
	      if (!BX.type.isDomNode(this._editorContainer)) {
	        this._editorContainer = BX.create("div", {
	          attrs: {
	            className: "crm-entity-stream-section-comment-editor"
	          }
	        });
	        this._editorContainer.appendChild(BX.create("DIV", {
	          attrs: {
	            className: "crm-timeline-wait"
	          }
	        }));
	        this.getContainer().appendChild(this._editorContainer);
	      }
	      window.setTimeout(BX.delegate(function () {
	        this.loadEditor();
	      }, this), 100);
	    }
	    this.setFocused(true);
	  }
	  save() {
	    let text = "";
	    const attachmentList = [];
	    if (this._postForm) {
	      text = this._postForm.oEditor.GetContent();
	      this._postForm.eventNode.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]').forEach(function (input) {
	        attachmentList.push(input.value);
	      });
	    } else {
	      text = this._input.value;
	    }
	    if (text === "") {
	      if (!this.emptyCommentMessage) {
	        this.emptyCommentMessage = new BX.PopupWindow('timeline_empty_new_comment_' + this.getEntityId(), this._saveButton, {
	          content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
	          darkMode: true,
	          autoHide: true,
	          zIndex: 990,
	          angle: {
	            position: 'top',
	            offset: 77
	          },
	          closeByEsc: true,
	          bindOptions: {
	            forceBindPosition: true
	          }
	        });
	      }
	      this.emptyCommentMessage.show();
	      return;
	    }
	    if (this._isRequestRunning || this._isLocked) {
	      return;
	    }
	    this._isRequestRunning = this._isLocked = true;
	    return main_core.ajax.runAction('crm.timeline.comment.add', {
	      data: {
	        fields: {
	          ENTITY_ID: this.getEntityId(),
	          ENTITY_TYPE_ID: this.getEntityTypeId(),
	          COMMENT: text,
	          ATTACHMENTS: attachmentList
	        }
	      }
	    }).then(result => {
	      this.onSaveSuccess();
	      return result;
	    }).catch(result => {
	      this.onSaveFailure();
	      return result;
	    });
	  }
	  cancel() {
	    this._input.value = "";
	    this._input.style.minHeight = "";
	    if (BX.type.isDomNode(this._editorContainer)) this._postForm.eventNode.style.display = 'none';
	    this._input.style.display = 'block';
	    this.setFocused(false);
	    this.release();
	  }
	  onSaveSuccess(data) {
	    this._isRequestRunning = false;
	    this._isLocked = false;
	    this.release();
	    if (this._postForm) {
	      this._postForm.reinit('', {});
	    }
	    this.emitFinishEditEvent();
	    this.cancel();
	  }
	  onSaveFailure() {
	    this._isRequestRunning = this._isLocked = false;
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$2,
	  _t4$1,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13;

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _renderEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEditor");
	var _renderSetupText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSetupText");
	var _renderTemplatesContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTemplatesContainer");
	var _renderFilesSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFilesSelector");
	var _subscribeToReceiversChanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToReceiversChanges");
	class Sms extends WithEditor {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _subscribeToReceiversChanges, {
	      value: _subscribeToReceiversChanges2
	    });
	    Object.defineProperty(this, _renderFilesSelector, {
	      value: _renderFilesSelector2
	    });
	    Object.defineProperty(this, _renderTemplatesContainer, {
	      value: _renderTemplatesContainer2
	    });
	    Object.defineProperty(this, _renderSetupText, {
	      value: _renderSetupText2
	    });
	    Object.defineProperty(this, _renderEditor, {
	      value: _renderEditor2
	    });
	  }
	  createLayout() {
	    const canSend = this.getSetting('canSendMessage', false);
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`<div class="crm-entity-stream-content-new-detail --focus --hidden">
			${0}
		</div>`), canSend ? babelHelpers.classPrivateFieldLooseBase(this, _renderEditor)[_renderEditor]() : babelHelpers.classPrivateFieldLooseBase(this, _renderSetupText)[_renderSetupText]());
	  }
	  doInitialize() {
	    this._isRequestRunning = false;
	    this._isLocked = false;
	    this._senderId = null;
	    this._from = null;
	    this._commEntityTypeId = null;
	    this._commEntityId = null;
	    this._to = null;
	    this._fromList = [];
	    this._toList = [];
	    this._defaults = {};
	    this._communications = [];
	    this._menu = null;
	    this._isMenuShown = false;
	    this._shownMenuId = null;
	    this._documentSelector = null;
	    this._source = null;
	    this._paymentId = null;
	    this._shipmentId = null;
	    this._compilationProductIds = [];
	    this._templateId = null;
	    this._templateFieldHintNode = null;
	    this._templateSelectorNode = null;
	    this._templateTemplateTitleNode = null;
	    this._templatePreviewNode = null;
	    this._templateSelectorMenuId = 'CrmTimelineSmsEditorTemplateSelector';
	    this._templateFieldHintHandler = BX.delegate(this.onTemplateHintIconClick, this);
	    this._templateSeletorClickHandler = BX.delegate(this.onTemplateSelectClick, this);
	    this._selectTemplateHandler = BX.delegate(this.onSelectTemplate, this);
	    this._serviceUrl = BX.util.remove_url_param(this.getSetting("serviceUrl", ""), ['sessid', 'site']);
	    const config = this.getSetting('smsConfig', {});
	    this._canUse = BX.prop.getBoolean(config, "canUse", false);
	    this._canSendMessage = BX.prop.getBoolean(config, "canSendMessage", false);
	    this._manageUrl = BX.prop.getString(config, "manageUrl", '');
	    this._senders = BX.prop.getArray(config, "senders", []);
	    this._defaults = BX.prop.getObject(config, "defaults", {
	      senderId: null,
	      from: null
	    });
	    this._communications = BX.prop.getArray(config, "communications", []);
	    this._isSalescenterEnabled = BX.prop.getBoolean(config, "isSalescenterEnabled", false);
	    this._isDocumentsEnabled = BX.prop.getBoolean(config, "isDocumentsEnabled", false);
	    if (this._isDocumentsEnabled) {
	      this._documentsProvider = BX.prop.getString(config, "documentsProvider", '');
	      this._documentsValue = BX.prop.getString(config, "documentsValue", '');
	    }
	    this._isFilesEnabled = BX.prop.getBoolean(config, "isFilesEnabled", false);
	    if (this._isFilesEnabled) {
	      this._diskUrls = BX.prop.getObject(config, "diskUrls");
	      this._isFilesExternalLinkEnabled = BX.prop.getBoolean(config, "isFilesExternalLinkEnabled", true);
	    }
	    this._senderSelectorNode = this.getContainer().querySelector('[data-role="sender-selector"]');
	    this._fromContainerNode = this.getContainer().querySelector('[data-role="from-container"]');
	    this._fromSelectorNode = this.getContainer().querySelector('[data-role="from-selector"]');
	    this._clientContainerNode = this.getContainer().querySelector('[data-role="client-container"]');
	    this._clientSelectorNode = this.getContainer().querySelector('[data-role="client-selector"]');
	    this._toSelectorNode = this.getContainer().querySelector('[data-role="to-selector"]');
	    this._messageLengthCounterWrapperNode = this.getContainer().querySelector('[data-role="message-length-counter-wrap"]');
	    this._messageLengthCounterNode = this.getContainer().querySelector('[data-role="message-length-counter"]');
	    this._salescenterStarter = this.getContainer().querySelector('[data-role="salescenter-starter"]');
	    this._smsDetailSwitcher = this.getContainer().querySelector('[data-role="sms-detail-switcher"]');
	    this._smsDetail = this.getContainer().querySelector('[data-role="sms-detail"]');
	    this._documentSelectorButton = this.getContainer().querySelector('[data-role="sms-document-selector"]');
	    this._fileSelectorButton = this.getContainer().querySelector('[data-role="sms-file-selector"]');
	    this._fileUploadZone = this.getContainer().querySelector('[data-role="sms-file-upload-zone"]');
	    this._fileUploadLabel = this.getContainer().querySelector('[data-role="sms-file-upload-label"]');
	    this._fileSelectorBitrix = this.getContainer().querySelector('[data-role="sms-file-selector-bitrix"]');
	    this._fileExternalLinkDisabledContent = this.getContainer().querySelector('[data-role="sms-file-external-link-disabled"]');
	    if (this._templatesContainer) {
	      this._templateFieldHintNode = this._templatesContainer.querySelector('[data-role="hint"]');
	      this._templateSelectorNode = this._templatesContainer.querySelector('[data-role="template-selector"]');
	      this._templateTemplateTitleNode = this._templatesContainer.querySelector('[data-role="template-title"]');
	      this._templatePreviewNode = this._templatesContainer.querySelector('[data-role="preview"]');
	    }
	    if (this._templateFieldHintNode) {
	      BX.bind(this._templateFieldHintNode, "click", this._templateFieldHintHandler);
	    }
	    if (this._templateSelectorNode) {
	      BX.bind(this._templateSelectorNode, "click", this._templateSeletorClickHandler);
	    }
	    if (this._canUse && this._senders.length > 0) {
	      this.initSenderSelector();
	    }
	    if (this._canUse && this._canSendMessage) {
	      this.initDetailSwitcher();
	      this.initFromSelector();
	      this.initClientContainer();
	      this.initClientSelector();
	      this.initToSelector();
	      this.initMessageLengthCounter();
	      this.setMessageLengthCounter();
	      if (this._isDocumentsEnabled) {
	        this.initDocumentSelector();
	      }
	      if (this._isFilesEnabled) {
	        this.initFileSelector();
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToReceiversChanges)[_subscribeToReceiversChanges]();
	    if (this._isSalescenterEnabled) {
	      this.initSalescenterApplication();
	    }
	  }
	  initDetailSwitcher() {
	    BX.bind(this._smsDetailSwitcher, 'click', function () {
	      if (this._smsDetail.classList.contains('hidden')) {
	        this._smsDetail.classList.remove('hidden');
	        this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_COLLAPSE');
	      } else {
	        this._smsDetail.classList.add('hidden');
	        this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_DETAILS');
	      }
	    }.bind(this));
	  }
	  initSenderSelector() {
	    const defaultSenderId = this._defaults.senderId;
	    let defaultSender = this._senders[0].canUse ? this._senders[0] : null;
	    let restSender = null;
	    const menuItems = [];
	    const handler = this.onSenderSelectorClick.bind(this);
	    for (let i = 0; i < this._senders.length; ++i) {
	      if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender)) {
	        defaultSender = this._senders[i];
	      }
	      if (this._senders[i].id === 'rest') {
	        restSender = this._senders[i];
	        continue;
	      }
	      menuItems.push({
	        text: this._senders[i].name,
	        sender: this._senders[i],
	        onclick: handler,
	        className: !this._senders[i].canUse || !this._senders[i].fromList.length ? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : ''
	      });
	    }
	    if (restSender) {
	      if (restSender.fromList.length > 0) {
	        menuItems.push({
	          delimiter: true
	        });
	        for (let i = 0; i < restSender.fromList.length; ++i) {
	          menuItems.push({
	            text: restSender.fromList[i].name,
	            sender: restSender,
	            from: restSender.fromList[i],
	            onclick: handler
	          });
	        }
	      }
	      menuItems.push({
	        delimiter: true
	      }, {
	        text: BX.message('CRM_TIMELINE_SMS_REST_MARKETPLACE'),
	        href: '/marketplace/category/crm_robot_sms/',
	        target: '_blank'
	      });
	    }
	    if (defaultSender) {
	      this.setSender(defaultSender);
	    }
	    BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	  }
	  onSenderSelectorClick(e, item) {
	    if (item.sender) {
	      if (!item.sender.canUse || !item.sender.fromList.length) {
	        const url = BX.Uri.addParam(item.sender.manageUrl, {
	          'IFRAME': 'Y'
	        });
	        const slider = BX.SidePanel.Instance.getTopSlider();
	        const options = {
	          events: {
	            onClose: function () {
	              if (slider) {
	                slider.reload();
	              }
	            },
	            onCloseComplete: function () {
	              if (!slider) {
	                document.location.reload();
	              }
	            }
	          }
	        };
	        if (item.sender.id === 'ednaru') {
	          options.width = 700;
	        }
	        BX.SidePanel.Instance.open(url, options);
	        return;
	      }
	      this.setSender(item.sender, true);
	      const from = item.from ? item.from : item.sender.fromList[0];
	      this.setFrom(from, true);
	    }
	    this._menu.close();
	  }
	  setSender(sender, setAsDefault) {
	    this._senderId = sender.id;
	    this._fromList = sender.fromList;
	    this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;
	    this._templateId = null;
	    if (sender.isTemplatesBased) {
	      this.showNode(this._templatesContainer);
	      this.hideNode(this._messageLengthCounterWrapperNode);
	      this.hideNode(this._fileSelectorButton);
	      this.hideNode(this._documentSelectorButton);
	      this.hideNode(this._input);
	      this.toggleTemplateSelectAvailability();
	      this.toggleSaveButton();
	      this._hideButtonsOnBlur = false;
	      this.onFocus();
	    } else {
	      this.hideNode(this._templatesContainer);
	      this.showNode(this._messageLengthCounterWrapperNode);
	      this.showNode(this._fileSelectorButton);
	      this.showNode(this._documentSelectorButton);
	      this.showNode(this._input);
	      this.setMessageLengthCounter();
	      this._hideButtonsOnBlur = true;
	    }
	    const visualFn = sender.id === 'rest' ? 'hide' : 'show';
	    BX[visualFn](this._fromContainerNode);
	    if (setAsDefault) {
	      BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
	    }
	  }
	  showNode(node) {
	    if (node) {
	      node.style.display = "";
	    }
	  }
	  hideNode(node) {
	    if (node) {
	      node.style.display = "none";
	    }
	  }
	  initFromSelector() {
	    if (this._fromList.length > 0) {
	      const defaultFromId = this._defaults.from || this._fromList[0].id;
	      let defaultFrom = null;
	      for (let i = 0; i < this._fromList.length; ++i) {
	        if (this._fromList[i].id === defaultFromId || !defaultFrom) {
	          defaultFrom = this._fromList[i];
	        }
	      }
	      if (defaultFrom) {
	        this.setFrom(defaultFrom);
	      }
	    }
	    BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	  }
	  onFromSelectorClick(e) {
	    const menuItems = [];
	    const handler = this.onFromSelectorItemClick.bind(this);
	    for (let i = 0; i < this._fromList.length; ++i) {
	      menuItems.push({
	        text: this._fromList[i].name,
	        from: this._fromList[i],
	        onclick: handler
	      });
	    }
	    this.openMenu('from_' + this._senderId, this._fromSelectorNode, menuItems, e);
	  }
	  onFromSelectorItemClick(e, item) {
	    if (item.from) {
	      this.setFrom(item.from, true);
	    }
	    this._menu.close();
	  }
	  setFrom(from, setAsDefault) {
	    this._from = from.id;
	    if (this._senderId === 'rest') {
	      this._senderSelectorNode.textContent = from.name;
	    } else {
	      this._fromSelectorNode.textContent = from.name;
	    }
	    if (setAsDefault) {
	      BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
	    }
	  }
	  initClientContainer() {
	    if (!main_core.Type.isDomNode(this._clientContainerNode)) {
	      return;
	    }
	    if (this._communications.length === 0) {
	      BX.hide(this._clientContainerNode);
	    } else {
	      BX.show(this._clientContainerNode);
	    }
	  }
	  initClientSelector() {
	    const defaultClient = this._communications[0];
	    if (defaultClient) {
	      this.setClient(defaultClient);
	    }
	    const handler = this.onClientSelectorClick.bind(this);
	    BX.bind(this._clientSelectorNode, 'click', event => {
	      const menuItems = [];
	      for (const communication of this._communications) {
	        menuItems.push({
	          text: communication.caption,
	          client: communication,
	          onclick: handler
	        });
	      }
	      this.openMenu('comm', this._clientSelectorNode, menuItems, event);
	    });
	  }
	  onClientSelectorClick(e, item) {
	    if (item.client) {
	      this.setClient(item.client);
	    }
	    this._menu.close();
	  }
	  setClient(client) {
	    var _client$phones, _client$phones$;
	    this._commEntityTypeId = client == null ? void 0 : client.entityTypeId;
	    this._commEntityId = client == null ? void 0 : client.entityId;
	    if (main_core.Type.isDomNode(this._clientSelectorNode)) {
	      var _client$caption;
	      this._clientSelectorNode.textContent = (_client$caption = client == null ? void 0 : client.caption) != null ? _client$caption : '';
	    }
	    this._toList = (_client$phones = client == null ? void 0 : client.phones) != null ? _client$phones : [];
	    this.setTo((_client$phones$ = client == null ? void 0 : client.phones[0]) != null ? _client$phones$ : {});
	  }
	  initToSelector() {
	    BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	  }
	  onToSelectorClick(e) {
	    const menuItems = [];
	    const handler = this.onToSelectorItemClick.bind(this);
	    for (let i = 0; i < this._toList.length; ++i) {
	      menuItems.push({
	        text: this._toList[i].valueFormatted || this._toList[i].value,
	        to: this._toList[i],
	        onclick: handler
	      });
	    }
	    this.openMenu('to_' + this._commEntityTypeId + '_' + this._commEntityId, this._toSelectorNode, menuItems, e);
	  }
	  onToSelectorItemClick(e, item) {
	    if (item.to) {
	      this.setTo(item.to);
	    }
	    this._menu.close();
	  }
	  setTo(to) {
	    this._to = to == null ? void 0 : to.value;
	    if (main_core.Type.isDomNode(this._toSelectorNode)) {
	      var _ref;
	      this._toSelectorNode.textContent = (_ref = (to == null ? void 0 : to.valueFormatted) || (to == null ? void 0 : to.value)) != null ? _ref : '';
	    }
	  }
	  openMenu(menuId, bindElement, menuItems, e) {
	    if (this._shownMenuId === menuId) {
	      return;
	    }
	    if (this._shownMenuId !== null && this._menu) {
	      this._menu.close();
	      this._shownMenuId = null;
	    }
	    BX.PopupMenu.show(this._id + menuId, bindElement, menuItems, {
	      cacheable: false,
	      offsetTop: 0,
	      offsetLeft: 36,
	      angle: {
	        position: "top",
	        offset: 0
	      },
	      events: {
	        onPopupClose: BX.delegate(this.onMenuClose, this)
	      }
	    });
	    this._menu = BX.PopupMenu.currentItem;
	    e.preventDefault();
	  }
	  onMenuClose() {
	    this._shownMenuId = null;
	    this._menu = null;
	  }
	  initMessageLengthCounter() {
	    this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
	    BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
	    BX.bind(this._input, 'cut', this.setMessageLengthCounterDelayed.bind(this));
	    BX.bind(this._input, 'paste', this.setMessageLengthCounterDelayed.bind(this));
	  }
	  setMessageLengthCounterDelayed() {
	    setTimeout(this.setMessageLengthCounter.bind(this), 0);
	  }
	  setMessageLengthCounter() {
	    const length = this._input.value.length;
	    this._messageLengthCounterNode.textContent = length;
	    const classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
	    BX[classFn](this._messageLengthCounterNode, 'crm-entity-stream-content-sms-symbol-counter-number-overhead');
	    this.toggleSaveButton();
	  }
	  toggleSaveButton() {
	    const sender = this.getSelectedSender();
	    let enabled;
	    if (!sender || !sender.isTemplatesBased) {
	      enabled = this._input.value.length > 0;
	    } else {
	      enabled = !!this._templateId;
	    }
	    if (enabled) {
	      BX.removeClass(this._saveButton, 'ui-btn-disabled');
	    } else {
	      BX.addClass(this._saveButton, 'ui-btn-disabled');
	    }
	  }
	  save() {
	    const sender = this.getSelectedSender();
	    let text = '';
	    let templateId = '';
	    if (!sender || !sender.isTemplatesBased) {
	      text = this._input.value;
	      if (text === '') {
	        return;
	      }
	    } else {
	      const template = this.getSelectedTemplate();
	      if (!template) {
	        return;
	      }
	      text = template.PREVIEW;
	      templateId = template.ID;
	    }
	    if (!this._communications.length) {
	      alert(BX.message('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));
	      return;
	    }
	    if (this._isRequestRunning || this._isLocked) {
	      return;
	    }
	    this._isRequestRunning = this._isLocked = true;
	    return new Promise((resolve, reject) => {
	      BX.ajax({
	        url: BX.util.add_url_param(this._serviceUrl, {
	          "action": "save_sms_message",
	          "sender": this._senderId
	        }),
	        method: "POST",
	        dataType: "json",
	        data: {
	          'site': BX.message('SITE_ID'),
	          'sessid': BX.bitrix_sessid(),
	          'source': this._source,
	          "ACTION": "SAVE_SMS_MESSAGE",
	          "SENDER_ID": this._senderId,
	          "MESSAGE_FROM": this._from,
	          "MESSAGE_TO": this._to,
	          "MESSAGE_BODY": text,
	          "MESSAGE_TEMPLATE": templateId,
	          "OWNER_TYPE_ID": this._ownerTypeId,
	          "OWNER_ID": this._ownerId,
	          "TO_ENTITY_TYPE_ID": this._commEntityTypeId,
	          "TO_ENTITY_ID": this._commEntityId,
	          "PAYMENT_ID": this._paymentId,
	          "SHIPMENT_ID": this._shipmentId,
	          "COMPILATION_PRODUCT_IDS": this._compilationProductIds
	        },
	        onsuccess: () => {
	          this.onSaveSuccess();
	          resolve();
	        },
	        onfailure: () => {
	          this.onSaveFailure();
	          reject();
	        }
	      });
	    });
	  }
	  cancel() {
	    this._input.value = "";
	    this.setMessageLengthCounter();
	    this._input.style.minHeight = "";
	    this.release();
	  }
	  onSaveSuccess(data) {
	    this._isRequestRunning = this._isLocked = false;
	    const error = BX.prop.getString(data, "ERROR", "");
	    if (error !== "") {
	      alert(error);
	      return;
	    }
	    this._input.value = "";
	    this.setMessageLengthCounter();
	    this._input.style.minHeight = "";
	    this.emitFinishEditEvent();
	    this.release();
	  }
	  onSaveFailure() {
	    this._isRequestRunning = this._isLocked = false;
	  }
	  initSalescenterApplication() {
	    BX.bind(this._salescenterStarter, 'click', this.startSalescenterApplication.bind(this));
	  }
	  startSalescenterApplication() {
	    BX.loadExt('salescenter.manager').then(function () {
	      BX.Salescenter.Manager.openApplication({
	        disableSendButton: this._canSendMessage ? '' : 'y',
	        context: 'sms',
	        ownerTypeId: this._ownerTypeId,
	        ownerId: this._ownerId,
	        mode: this._ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment'
	      }).then(function (result) {
	        if (result && result.get('action')) {
	          if (result.get('action') === 'sendPage' && result.get('page') && result.get('page').url) {
	            this._input.focus();
	            this._input.value = this._input.value + result.get('page').name + ' ' + result.get('page').url;
	            this.setMessageLengthCounter();
	          } else if (result.get('action') === 'sendPayment' && result.get('order')) {
	            this._input.focus();
	            this._input.value = this._input.value + result.get('order').title;
	            this.setMessageLengthCounter();
	            this._source = 'order';
	            this._paymentId = result.get('order').paymentId;
	            this._shipmentId = result.get('order').shipmentId;
	          } else if (result.get('action') === 'sendCompilation' && result.get('compilation')) {
	            this._input.focus();
	            this._input.value = this._input.value + result.get('compilation').title;
	            this.setMessageLengthCounter();
	            this._source = 'deal';
	            this._compilationProductIds = result.get('compilation').productIds;
	          }
	        }
	      }.bind(this));
	    }.bind(this));
	  }
	  initDocumentSelector() {
	    BX.bind(this._documentSelectorButton, 'click', this.onDocumentSelectorClick.bind(this));
	  }
	  onDocumentSelectorClick() {
	    if (!this._documentSelector) {
	      BX.loadExt('documentgenerator.selector').then(function () {
	        this._documentSelector = new BX.DocumentGenerator.Selector.Menu({
	          node: this._documentSelectorButton,
	          moduleId: 'crm',
	          provider: this._documentsProvider,
	          value: this._documentsValue,
	          analyticsLabelPrefix: 'crmTimelineSmsEditor'
	        });
	        this.selectPublicUrl();
	      }.bind(this));
	    } else {
	      this.selectPublicUrl();
	    }
	  }
	  selectPublicUrl() {
	    if (!this._documentSelector) {
	      return;
	    }
	    this._documentSelector.show().then(function (object) {
	      if (object instanceof BX.DocumentGenerator.Selector.Template) {
	        this._documentSelector.createDocument(object).then(function (document) {
	          this.pasteDocumentUrl(document);
	        }.bind(this)).catch(function (error) {
	          console.error(error);
	        }.bind(this));
	      } else if (object instanceof BX.DocumentGenerator.Selector.Document) {
	        this.pasteDocumentUrl(object);
	      }
	    }.bind(this)).catch(function (error) {
	      console.error(error);
	    }.bind(this));
	  }
	  pasteDocumentUrl(document) {
	    this._documentSelector.getDocumentPublicUrl(document).then(function (publicUrl) {
	      this._input.focus();
	      this._input.value = this._input.value + ' ' + document.getTitle() + ' ' + publicUrl;
	      this.setMessageLengthCounter();
	      this._source = 'document';
	    }.bind(this)).catch(function (error) {
	      console.error(error);
	    }.bind(this));
	  }
	  initFileSelector() {
	    BX.bind(this._fileSelectorButton, 'click', this.onFileSelectorClick.bind(this));
	  }
	  closeFileSelector() {
	    BX.PopupMenu.destroy('sms-file-selector');
	  }
	  onFileSelectorClick() {
	    BX.PopupMenu.show('sms-file-selector', this._fileSelectorButton, [{
	      text: BX.message('CRM_TIMELINE_SMS_UPLOAD_FILE'),
	      onclick: this.uploadFile.bind(this),
	      className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
	    }, {
	      text: BX.message('CRM_TIMELINE_SMS_FIND_FILE'),
	      onclick: this.findFile.bind(this),
	      className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
	    }]);
	  }
	  getFileUploadInput() {
	    return document.getElementById(this._fileUploadLabel.getAttribute('for'));
	  }
	  uploadFile() {
	    this.closeFileSelector();
	    if (this._isFilesExternalLinkEnabled) {
	      this.initDiskUF();
	      BX.fireEvent(this.getFileUploadInput(), 'click');
	    } else {
	      this.showFilesExternalLinkFeaturePopup();
	    }
	  }
	  findFile() {
	    this.closeFileSelector();
	    if (this._isFilesExternalLinkEnabled) {
	      this.initDiskUF();
	      BX.fireEvent(this._fileSelectorBitrix, 'click');
	    } else {
	      this.showFilesExternalLinkFeaturePopup();
	    }
	  }
	  getLoader() {
	    if (!this.loader) {
	      this.loader = new BX.Loader({
	        size: 50
	      });
	    }
	    return this.loader;
	  }
	  showLoader(node) {
	    if (node && !this.getLoader().isShown()) {
	      this.getLoader().show(node);
	    }
	  }
	  hideLoader() {
	    if (this.getLoader().isShown()) {
	      this.getLoader().hide();
	    }
	  }
	  initDiskUF() {
	    if (this.isDiskFileUploaderInited || !this._isFilesEnabled) {
	      return;
	    }
	    this.isDiskFileUploaderInited = true;
	    BX.addCustomEvent(this._fileUploadZone, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
	    BX.addCustomEvent(this._fileUploadZone, 'DiskDLoadFormControllerInit', function (uf) {
	      uf._onUploadProgress = function () {
	        this.showLoader(this._fileSelectorButton.parentNode.parentNode);
	      }.bind(this);
	    }.bind(this));
	    BX.Disk.UF.add({
	      UID: this._fileUploadZone.getAttribute('data-node-id'),
	      controlName: this._fileUploadLabel.getAttribute('for'),
	      hideSelectDialog: false,
	      urlSelect: this._diskUrls.urlSelect,
	      urlRenameFile: this._diskUrls.urlRenameFile,
	      urlDeleteFile: this._diskUrls.urlDeleteFile,
	      urlUpload: this._diskUrls.urlUpload
	    });
	    BX.onCustomEvent(this._fileUploadZone, 'DiskLoadFormController', ['show']);
	  }
	  OnFileUploadSuccess(fileResult, uf, file, uploaderFile) {
	    this.hideLoader();
	    const diskFileId = parseInt(fileResult.element_id.replace('n', ''));
	    const fileName = fileResult.element_name;
	    this.pasteFileUrl(diskFileId, fileName);
	  }
	  pasteFileUrl(diskFileId, fileName) {
	    this.showLoader(this._fileSelectorButton.parentNode.parentNode);
	    BX.ajax.runAction('disk.file.generateExternalLink', {
	      analyticsLabel: 'crmTimelineSmsEditorGetFilePublicUrl',
	      data: {
	        fileId: diskFileId
	      }
	    }).then(function (response) {
	      this.hideLoader();
	      if (response.data.externalLink && response.data.externalLink.link) {
	        this._input.focus();
	        this._input.value = this._input.value + ' ' + fileName + ' ' + response.data.externalLink.link;
	        this.setMessageLengthCounter();
	        this._source = 'file';
	      }
	    }.bind(this)).catch(function (response) {
	      console.error(response.errors.pop().message);
	    });
	  }
	  getFeaturePopup(content) {
	    if (this.featurePopup != null) {
	      return this.featurePopup;
	    }
	    this.featurePopup = new BX.PopupWindow('bx-popup-crm-sms-editor-feature-popup', null, {
	      zIndex: 200,
	      autoHide: true,
	      closeByEsc: true,
	      closeIcon: true,
	      overlay: true,
	      events: {
	        onPopupDestroy: function () {
	          this.featurePopup = null;
	        }.bind(this)
	      },
	      content: content,
	      contentColor: 'white'
	    });
	    return this.featurePopup;
	  }
	  showFilesExternalLinkFeaturePopup() {
	    this.getFeaturePopup(this._fileExternalLinkDisabledContent).show();
	  }
	  onTemplateHintIconClick() {
	    if (this._senderId === 'ednaru') {
	      top.BX.Helper.show("redirect=detail&code=14214014");
	    }
	  }
	  showTemplateSelectDropdown(items) {
	    const menuItems = [];
	    if (BX.Type.isArray(items)) {
	      if (items.length) {
	        items.forEach(function (item) {
	          menuItems.push({
	            value: item.ID,
	            text: item.TITLE,
	            onclick: this._selectTemplateHandler
	          });
	        }.bind(this));
	        BX.PopupMenu.show({
	          id: this._templateSelectorMenuId,
	          bindElement: this._templateSelectorNode,
	          items: menuItems,
	          angle: false,
	          width: this._templateSelectorNode.offsetWidth
	        });
	      }
	    } else if (this._senderId) {
	      const loaderMenuId = this._templateSelectorMenuId + 'loader';
	      const loaderMenuLoaderId = this._templateSelectorMenuId + 'loader';
	      BX.PopupMenu.show({
	        id: loaderMenuId,
	        bindElement: this._templateSelectorNode,
	        items: [{
	          html: '<div id="' + loaderMenuLoaderId + '"></div>'
	        }],
	        angle: false,
	        width: this._templateSelectorNode.offsetWidth,
	        height: 60,
	        events: {
	          onDestroy: function () {
	            this.hideLoader();
	          }.bind(this)
	        }
	      });
	      this.showLoader(BX(loaderMenuLoaderId));
	      if (!this._isRequestRunning) {
	        this._isRequestRunning = true;
	        const senderId = this._senderId;
	        BX.ajax.runAction('messageservice.Sender.getTemplates', {
	          data: {
	            id: senderId,
	            context: {
	              module: 'crm',
	              entityTypeId: this.getEntityTypeId(),
	              entityId: this.getEntityId()
	            }
	          }
	        }).then(function (response) {
	          this._isRequestRunning = false;
	          const sender = this._senders.find(function (sender) {
	            return sender.id === senderId;
	          }.bind(this));
	          if (sender) {
	            sender.templates = response.data.templates;
	            this.toggleTemplateSelectAvailability();
	            if (BX.PopupMenu.getMenuById(loaderMenuId)) {
	              BX.PopupMenu.getMenuById(loaderMenuId).close();
	              this.showTemplateSelectDropdown(sender.templates);
	            }
	          }
	        }.bind(this)).catch(function (response) {
	          this._isRequestRunning = false;
	          if (BX.PopupMenu.getMenuById(loaderMenuId)) {
	            if (response && response.errors && response.errors[0] && response.errors[0].message) {
	              alert(response.errors[0].message);
	            }
	            BX.PopupMenu.getMenuById(loaderMenuId).close();
	          }
	        }.bind(this));
	      }
	    }
	  }
	  getSelectedSender() {
	    return this._senders.find(function (sender) {
	      return sender.id === this._senderId;
	    }.bind(this));
	  }
	  getSelectedTemplate() {
	    const sender = this.getSelectedSender();
	    if (!this._templateId || !sender || !sender.templates) {
	      return null;
	    }
	    const template = sender.templates.find(function (template) {
	      return template.ID == this._templateId;
	    }.bind(this));
	    return template ? template : null;
	  }
	  onTemplateSelectClick() {
	    const sender = this.getSelectedSender();
	    if (sender) {
	      this.showTemplateSelectDropdown(sender.templates);
	    }
	  }
	  onSelectTemplate(e, item) {
	    this._templateId = item.value;
	    this.applySelectedTemplate();
	    this.toggleSaveButton();
	    const menu = BX.PopupMenu.getMenuById(this._templateSelectorMenuId);
	    if (menu) {
	      menu.close();
	    }
	  }
	  toggleTemplateSelectAvailability() {
	    const sender = this.getSelectedSender();
	    if (sender && BX.Type.isArray(sender.templates) && !sender.templates.length) {
	      BX.addClass(this._templateSelectorNode, 'ui-ctl-disabled');
	      this._templateTemplateTitleNode.textContent = BX.message('CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND');
	    } else {
	      BX.removeClass(this._templateSelectorNode, 'ui-ctl-disabled');
	      this.applySelectedTemplate();
	    }
	  }
	  applySelectedTemplate() {
	    const sender = this.getSelectedSender();
	    if (!this._templateId || !sender || !sender.templates) {
	      this.hideNode(this._templatePreviewNode);
	      this._templateTemplateTitleNode.textContent = '';
	    } else {
	      const template = this.getSelectedTemplate();
	      if (template) {
	        const preview = BX.Text.encode(template.PREVIEW).replace(/\n/g, '<br>');
	        this.showNode(this._templatePreviewNode);
	        this._templatePreviewNode.innerHTML = preview;
	        this._templateTemplateTitleNode.textContent = template.TITLE;
	      } else {
	        this.hideNode(this._templatePreviewNode);
	        this._templateTemplateTitleNode.textContent = '';
	      }
	    }
	  }
	  static create(id, settings) {
	    const self = new Sms();
	    self.initialize(id, settings);
	    Sms.items[self.getId()] = self;
	    return self;
	  }
	}
	function _renderEditor2() {
	  const config = this.getSetting('smsConfig', {});
	  const enableSalesCenter = BX.prop.getBoolean(config, 'isSalescenterEnabled', false);
	  const enableDocuments = BX.prop.getBoolean(config, 'isDocumentsEnabled', false);
	  const enableFiles = this.getSetting('enableFiles', false);
	  this._saveButton = main_core.Tag.render(_t2$2 || (_t2$2 = _$2`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SEND'));
	  this._cancelButton = main_core.Tag.render(_t3$2 || (_t3$2 = _$2`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	  this._input = main_core.Tag.render(_t4$1 || (_t4$1 = _$2`<textarea class="crm-entity-stream-content-new-sms-textarea" rows='1' placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_ENTER_MESSAGE'));
	  return main_core.Tag.render(_t5 || (_t5 = _$2`<div class="crm-entity-stream-content-sms-buttons-container">
			${0}
			${0}
			${0}
				<div class="crm-entity-stream-content-sms-detail-toggle" data-role="sms-detail-switcher">
					${0}
				</div>
			</div>
			<div class="crm-entity-stream-content-sms-conditions-container hidden" data-role="sms-detail">
				<div class="crm-entity-stream-content-sms-conditions">
					<div class="crm-entity-stream-content-sms-conditions-text">
						${0}
						<a href="#" data-role="sender-selector">sender</a><span data-role="from-container">${0}
						<a data-role="from-selector" href="#">from_number</a></span>
						<span data-role="client-container"> ${0}
						<a data-role="client-selector" href="#">client_caption</a> <a data-role="to-selector" href="#">to_number</a></span>
					</div>
				</div>
			</div>
			${0}
			${0}
			${0}

			<div class="crm-entity-stream-content-new-sms-btn-container">
				${0}
				${0}

				<div class="crm-entity-stream-content-sms-symbol-counter" data-role="message-length-counter-wrap">
					${0}
					<span class="crm-entity-stream-content-sms-symbol-counter-number" data-role="message-length-counter" data-length-max="200">0</span>
					${0}
					<span class="crm-entity-stream-content-sms-symbol-counter-number">200</span>
				</div>
			</div>
		`), enableSalesCenter ? main_core.Tag.render(_t6 || (_t6 = _$2`
				<div class="crm-entity-stream-content-sms-button" data-role="salescenter-starter">
					<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')) : null, enableFiles ? main_core.Tag.render(_t7 || (_t7 = _$2`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-file-selector">
					<div class="crm-entity-stream-content-sms-file-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SEND_FILE')) : null, enableDocuments ? main_core.Tag.render(_t8 || (_t8 = _$2`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-document-selector">
					<div class="crm-entity-stream-content-sms-document-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SEND_DOCUMENT')) : null, main_core.Loc.getMessage('CRM_TIMELINE_DETAILS'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SENDER'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_FROM'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_TO'), this._input, babelHelpers.classPrivateFieldLooseBase(this, _renderTemplatesContainer)[_renderTemplatesContainer](), babelHelpers.classPrivateFieldLooseBase(this, _renderFilesSelector)[_renderFilesSelector](), this._saveButton, this._cancelButton, main_core.Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS_FROM'));
	}
	function _renderSetupText2() {
	  const enableSalesCenter = BX.prop.getBoolean(this.getSetting('smsConfig', {}), 'isSalescenterEnabled', false);
	  return main_core.Tag.render(_t9 || (_t9 = _$2`<div class="crm-entity-stream-content-sms-conditions-container">
			<div class="crm-entity-stream-content-sms-conditions">
				<div class="crm-entity-stream-content-sms-conditions-text">
					<strong>${0}</strong><br>
					${0}<br>
					${0}
				</div>
			</div>
		</div>
		<div class="crm-entity-stream-content-new-sms-btn-container">
			<a href="#" data-role="sender-selector" target="_top" class="crm-entity-stream-content-new-sms-connect-link">${0}</a>
			${0}
		</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_1'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_2'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_3_MSGVER_1'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_URL'), enableSalesCenter ? main_core.Tag.render(_t10 || (_t10 = _$2`<div class="crm-entity-stream-content-sms-salescenter-container-absolute" data-role="salescenter-starter">
	<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
	<div class="crm-entity-stream-content-sms-button-text">${0}</div>
</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')) : null);
	}
	function _renderTemplatesContainer2() {
	  this._templatesContainer = main_core.Tag.render(_t11 || (_t11 = _$2`<div class="crm-entity-stream-content-new-sms-templates">
				<div class="ui-ctl-label-text">
					${0}<span class="ui-hint" data-role="hint"><span class="ui-hint-icon"></span></span>
				</div>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100" data-role="template-selector">
					<div class="ui-ctl-element" data-role="template-title"></div>
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				</div>
				<div class="crm-entity-stream-content-new-sms-preview" data-role="preview"></div>
			</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_TEMPLATE_LIST_TITLE'));
	  return this._templatesContainer;
	}
	function _renderFilesSelector2() {
	  const config = this.getSetting('smsConfig', {});
	  const showFiles = this.getSetting('showFiles', false);
	  const enableFilesExternalLink = BX.prop.getBoolean(config, 'isFilesExternalLinkEnabled', false);
	  if (enableFilesExternalLink) {
	    const fileInputPrefix = 'crm-' + this.getEntityTypeId() + '-' + this.getEntityId();
	    const fileInputName = fileInputPrefix + '-sms-files';
	    const fileUploaderInputName = fileInputPrefix + '-sms-files-uploader';
	    const fileUploaderZoneId = 'diskuf-selectdialog-' + fileInputPrefix;
	    return main_core.Tag.render(_t12 || (_t12 = _$2`<div class="crm-entity-stream-content-sms-file-uploader-zone" data-role="sms-file-upload-zone" data-node-id="${0}">
				<div id="${0}" class="diskuf-files-entity diskuf-selectdialog bx-disk">
					<div class="diskuf-files-block checklist-loader-files">
						<div class="diskuf-placeholder">
							<table class="files-list">
								<tbody class="diskuf-placeholder-tbody"></tbody>
							</table>
						</div>
					</div>
					<div class="diskuf-extended">
						<input type="hidden" name="${0}[]" value="" />
					</div>
					<div class="diskuf-extended-item">
						<label for="${0}" data-role="sms-file-upload-label"></label>
						<input class="diskuf-fileUploader" id="${0}" type="file" data-role="sms-file-upload-input" />
					</div>
					<div class="diskuf-extended-item">
						<span class="diskuf-selector-link" data-role="sms-file-selector-bitrix">
						</span>
					</div>
				</div>
			</div>`), fileInputPrefix, fileUploaderZoneId, fileInputName, fileUploaderInputName, fileUploaderInputName);
	  }
	  if (showFiles) {
	    return main_core.Tag.render(_t13 || (_t13 = _$2`<div class="crm-entity-stream-content-sms-file-external-link-popup" data-role="sms-file-external-link-disabled">
				<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-container">
					<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-inner">
						<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc">
							<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img">
								<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img-lock"></div>
							</div>
							<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc-text">
								${0}
							</div>
						</div>
					</div>
				</div>
			</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_FILE_EXTERNAL_LINK_FEATURE'));
	  }
	  return null;
	}
	function _subscribeToReceiversChanges2() {
	  main_core_events.EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', event => {
	    const {
	      item,
	      current
	    } = event.getData();
	    if (this.getEntityTypeId() !== (item == null ? void 0 : item.entityTypeId) || this.getEntityId() !== (item == null ? void 0 : item.entityId)) {
	      return;
	    }
	    if (!main_core.Type.isArray(current)) {
	      return;
	    }
	    const phoneReceivers = current.filter(receiver => receiver.address.typeId === 'PHONE');
	    const newCommunications = {};
	    for (const receiver of phoneReceivers) {
	      let communication = newCommunications[receiver.addressSource.hash];
	      if (!communication) {
	        var _receiver$addressSour;
	        communication = {
	          entityTypeId: receiver.addressSource.entityTypeId,
	          entityTypeName: BX.CrmEntityType.resolveName(receiver.addressSource.entityTypeId),
	          entityId: receiver.addressSource.entityId,
	          caption: (_receiver$addressSour = receiver.addressSourceData) == null ? void 0 : _receiver$addressSour.title,
	          phones: []
	        };
	      }
	      communication.phones.push({
	        type: receiver.address.typeId,
	        value: receiver.address.value,
	        valueFormatted: receiver.address.valueFormatted
	      });
	      newCommunications[receiver.addressSource.hash] = communication;
	    }
	    this._communications = Object.values(newCommunications);
	    const oldSelectedClient = this._communications.find(communication => {
	      return communication.entityTypeId === this._commEntityTypeId && communication.entityId === this._commEntityId;
	    });
	    this.setClient(oldSelectedClient != null ? oldSelectedClient : this._communications[0]);
	    this.initClientContainer();
	  });
	}
	Sms.items = {};

	class Call extends Item {
	  showSlider() {
	    const planner = new BX.Crm.Activity.Planner();
	    planner.showEdit({
	      'TYPE_ID': BX.CrmActivityType.call,
	      'OWNER_TYPE_ID': this.getEntityTypeId(),
	      'OWNER_ID': this.getEntityId()
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	class Email extends Item {
	  showSlider() {
	    const ownerInfo = BX.CrmTimelineManager.getDefault().getOwnerInfo();
	    BX.CrmActivityEditor.getDefault().addEmail({
	      'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
	      'ownerID': this.getEntityId(),
	      'ownerUrl': ownerInfo['SHOW_URL'],
	      'ownerTitle': ownerInfo['TITLE'],
	      'subject': ''
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	class Meeting extends Item {
	  showSlider() {
	    const planner = new BX.Crm.Activity.Planner();
	    planner.showEdit({
	      'TYPE_ID': BX.CrmActivityType.meeting,
	      'OWNER_TYPE_ID': this.getEntityTypeId(),
	      'OWNER_ID': this.getEntityId()
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	class Task extends Item {
	  showSlider() {
	    BX.CrmActivityEditor.getDefault().addTask({
	      'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
	      'ownerID': this.getEntityId()
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$3,
	  _t3$3,
	  _t4$2,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8$1,
	  _t9$1,
	  _t10$1,
	  _t11$1,
	  _t12$1;

	/** @memberof BX.Crm.Timeline.MenuBar */
	class Sharing extends WithEditor {
	  constructor(...args) {
	    super(...args);
	    this.HELPDESK_CODE = 17502612;
	  }
	  /**
	   * @override
	   */
	  initialize(context, settings) {
	    const config = settings.config;
	    this.link = config.link;
	    this.setContacts(config.contacts);
	    this.isNotificationsAvailable = config.isNotificationsAvailable;
	    this.areCommunicationChannelsAvailable = config.areCommunicationChannelsAvailable;
	    if (this.areCommunicationChannelsAvailable) {
	      this.setCommunicationChannels(config.communicationChannels, config.selectedChannelId);
	    }
	    this.doPayAttentionToNewFeature = config.doPayAttentionToNewFeature;
	    super.initialize(context, settings);
	    if (this.getSetting('isAvailable')) {
	      this.bindEvents();
	    }
	  }
	  activate() {
	    if (this.getSetting('isAvailable')) {
	      this.setVisible(true);
	    } else {
	      var _BX$UI, _BX$UI$InfoHelper;
	      (_BX$UI = BX.UI) == null ? void 0 : (_BX$UI$InfoHelper = _BX$UI.InfoHelper) == null ? void 0 : _BX$UI$InfoHelper.show('limit_crm_calendar_free_slots');
	    }
	  }
	  supportsLayout() {
	    return this.getSetting('isAvailable');
	  }
	  bindEvents() {
	    main_core_events.EventEmitter.subscribe('CalendarSharing:LinkCopied', () => this.onLinkCopied());
	    main_core_events.EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', this.onContactsChangedHandler.bind(this));
	  }

	  /**
	   * @override
	   */
	  doInitialize() {
	    if (this.doPayAttentionToNewFeature) {
	      this.payAttentionToNewFeature();
	      BX.ajax.runAction('crm.api.timeline.calendar.sharing.disableOptionPayAttentionToNewCrmSharingFeature');
	    }
	  }

	  /**
	   * @override
	   */
	  createLayout() {
	    this.DOM = {
	      menuBarItem: document.querySelector('.crm-entity-stream-section-menu [data-id=sharing]')
	    };
	    return main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div class="crm-entity-stream-content-sharing --hidden">
				<div id="_sharing_content_container">
					<div class="crm-entity-stream-calendar-sharing-container">
						<div class="crm-entity-stream-calendar-sharing-main">
							<div class="crm-entity-stream-calendar-sharing-icon"></div>
							<div class="crm-entity-stream-calendar-sharing-info">
								<div class="crm-entity-stream-calendar-sharing-header">
									${0}
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-item">
									<div class="crm-entity-stream-calendar-sharing-info-item-icon"></div>
									<div class="crm-entity-stream-calendar-sharing-info-item-text">
										${0}
									</div>
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-item">
									<div class="crm-entity-stream-calendar-sharing-info-item-icon"></div>
									<div class="crm-entity-stream-calendar-sharing-info-item-text">
										${0}
									</div>
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-btn-settings">
									<div class="crm-entity-stream-calendar-sharing-info-icon-qr"></div>
									${0}
								</div>
							</div>
							${0}
						</div>
					</div>
				</div>
				<div class="crm-entity-stream-calendar-sharing-btn-container">
					${0}
					${0}
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_TITLE'), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_ITEM_1'), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_ITEM_2'), this.createConfigureSlotsButton(), this.createSettingsButton(), this.createSendButton(), this.createCancelButton(), this.createMoreInfoButton());
	  }
	  createConfigureSlotsButton() {
	    this.DOM.configureSlotsButton = main_core.Tag.render(_t2$3 || (_t2$3 = _$3`
			<div class="crm-entity-stream-calendar-sharing-info-btn-settings-text">
				${0}
			</div>
		`), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_CONFIGURE_SLOTS'));
	    main_core.Event.bind(this.DOM.configureSlotsButton, 'click', () => this.onConfigureSlotsButtonClick());
	    return this.DOM.configureSlotsButton;
	  }
	  createSettingsButton() {
	    this.DOM.settingsButton = main_core.Tag.render(_t3$3 || (_t3$3 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-icon"></div>
		`));
	    this.updateSettingsButton();
	    main_core.Event.bind(this.DOM.settingsButton, 'click', () => this.onSettingsButtonClick());
	    return this.DOM.settingsButton;
	  }
	  updateSettingsButton() {
	    if (this.isContactAvailable()) {
	      this.DOM.settingsButton.style.display = '';
	    } else {
	      this.DOM.settingsButton.style.display = 'none';
	    }
	  }
	  createSendButton() {
	    this.DOM.sendButton = main_core.Tag.render(_t4$2 || (_t4$2 = _$3`
			<button class="ui-btn ui-btn-xs ui-btn-primary">
				${0}
			</button>
		`), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_SEND_BUTTON'));
	    main_core.Event.bind(this.DOM.sendButton, 'click', () => this.onSendButtonClick());
	    this._saveButton = this.DOM.sendButton;
	    return this.DOM.sendButton;
	  }
	  createCancelButton() {
	    this.DOM.cancelButton = main_core.Tag.render(_t5$1 || (_t5$1 = _$3`
			<span class="ui-btn ui-btn-xs ui-btn-link">
				${0}
			</span>
		`), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_CANCEL_BUTTON'));
	    main_core.Event.bind(this.DOM.cancelButton, 'click', () => this.onCancelButtonClick());
	    this._cancelButton = this.DOM.cancelButton;
	    return this.DOM.cancelButton;
	  }
	  createMoreInfoButton() {
	    this.DOM.moreInfoButton = main_core.Tag.render(_t6$1 || (_t6$1 = _$3`
			<span class="crm-entity-stream-calendar-sharing-more-btn">
				${0}
			</span>
		`), main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_MORE_INFO_BUTTON'));
	    main_core.Event.bind(this.DOM.moreInfoButton, 'click', () => this.onMoreInfoButtonClick());
	    return this.DOM.moreInfoButton;
	  }
	  onConfigureSlotsButtonClick() {
	    this.showConfigureSlotsPopup();
	  }
	  onSettingsButtonClick() {
	    this.showSettingsPopup();
	  }
	  onSendButtonClick() {
	    if (!this.isContactAvailable()) {
	      this.showWarningNoContact();
	      return;
	    }
	    if (!this.areCommunicationChannelsAvailable) {
	      this.showWarningNoCommunicationChannels();
	      return;
	    }
	    this.onSaveButtonClick();
	  }
	  onLinkCopied() {
	    this.saveLinkAction({
	      isActionCopy: true
	    });
	  }
	  onMoreInfoButtonClick() {
	    this.openHelpDesk();
	  }
	  showConfigureSlotsPopup() {
	    if (!this.newDialog) {
	      this.newDialog = new calendar_sharing_interface.DialogNew({
	        bindElement: this.DOM.configureSlotsButton,
	        sharingUrl: this.link.url,
	        context: "crm"
	      });
	    }
	    this.newDialog.show();
	  }
	  showSettingsPopup() {
	    if (this.settingsMenu) {
	      this.settingsMenu.destroy();
	    }
	    this.settingsMenu = this.getSettingsMenu();
	    this.settingsMenu.show();
	  }
	  isSettingsPopupShown() {
	    var _this$settingsMenu;
	    return (_this$settingsMenu = this.settingsMenu) == null ? void 0 : _this$settingsMenu.popupWindow.isShown();
	  }
	  getSettingsMenu() {
	    const items = [this.getSharingReceiverItem()];
	    if (this.areCommunicationChannelsAvailable && this.isChannelsAvailable()) {
	      items.push(this.getSharingChannelsItem());
	    }
	    if (this.currentFromList) {
	      items.push(this.getSharingSenderItem());
	    }
	    return main_popup.MenuManager.create({
	      id: 'crm-calendar-sharing-settings',
	      bindElement: this.DOM.settingsButton,
	      items: items
	    });
	  }
	  getSharingReceiverItem() {
	    return {
	      id: 'sharing_receiver',
	      text: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_RECEIVER'),
	      items: this.contacts.map(contact => {
	        return this.getContactMenuItem(contact);
	      })
	    };
	  }
	  getSharingChannelsItem() {
	    return {
	      id: 'sharing_channels',
	      text: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_COMMUNICATION_CHANNELS'),
	      items: this.channels.map(channel => {
	        return this.getChannelMenuItem(channel);
	      })
	    };
	  }
	  getSharingSenderItem() {
	    return {
	      id: 'sharing_sender',
	      text: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_SENDER'),
	      items: this.currentFromList.map(from => {
	        return this.getFromMenuItem(from);
	      })
	    };
	  }
	  getContactMenuItem(contact) {
	    const isSelected = contact.entityId === this.contact.entityId && contact.entityTypeId === this.contact.entityTypeId;
	    const itemHtml = main_core.Tag.render(_t7$1 || (_t7$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${0} (${0})</div>
			</div>
		`), contact.name, contact.phone);
	    contact.check = main_core.Tag.render(_t8$1 || (_t8$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${0}"></div>
		`), isSelected ? '--show' : '');
	    itemHtml.append(contact.check);
	    return {
	      html: itemHtml,
	      onclick: () => {
	        main_core.Dom.removeClass(this.contact.check, '--show');
	        main_core.Dom.addClass(contact.check, '--show');
	        this.contact = contact;
	      }
	    };
	  }
	  getChannelMenuItem(channel) {
	    const isSelected = channel.id === this.channel.id;
	    const itemHtml = main_core.Tag.render(_t9$1 || (_t9$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${0}</div>
			</div>
		`), channel.name);
	    channel.check = main_core.Tag.render(_t10$1 || (_t10$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${0}"></div>
		`), isSelected ? '--show' : '');
	    itemHtml.append(channel.check);
	    return {
	      html: itemHtml,
	      onclick: () => {
	        main_core.Dom.removeClass(this.channel.check, '--show');
	        main_core.Dom.addClass(channel.check, '--show');
	        this.channel = channel;
	        this.updateSenderList();
	      }
	    };
	  }
	  getFromMenuItem(from) {
	    const isSelected = from.id === this.currentFrom.id;
	    const itemHtml = main_core.Tag.render(_t11$1 || (_t11$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${0}</div>
			</div>
		`), from.name);
	    from.check = main_core.Tag.render(_t12$1 || (_t12$1 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${0}"></div>
		`), isSelected ? '--show' : '');
	    itemHtml.append(from.check);
	    return {
	      html: itemHtml,
	      onclick: () => {
	        main_core.Dom.removeClass(this.currentFrom.check, '--show');
	        main_core.Dom.addClass(from.check, '--show');
	        this.currentFrom = from;
	      }
	    };
	  }
	  showWarningNoCommunicationChannels() {
	    let title;
	    let text;
	    if (this.isNotificationsAvailable) {
	      title = main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TITLE');
	      text = `
				<div>${main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_1')}</div>
				</br>
				<div>${main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_2')}</div>
			`;
	    } else {
	      title = main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE');
	      text = `
				<div>${main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE_1')}</div>
				</br>
				<div>${main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_2')}</div>
			`;
	    }
	    const noCommunicationChannelsWarningGuide = this.getWarningGuide(title, text);
	    noCommunicationChannelsWarningGuide.showNextStep();
	    const guidePopup = noCommunicationChannelsWarningGuide.getPopup();
	    const guideContentContainer = guidePopup.getContentContainer();
	    const openConfigurationButton = guideContentContainer.querySelector('span[data-role=crm-timeline-calendar-sharing_open-configure-slots]');
	    openConfigurationButton.addEventListener('click', () => {
	      guidePopup.close();
	      this.showConfigureSlotsPopup();
	    });
	  }
	  showWarningNoContact() {
	    const title = main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TITLE');
	    const text = main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TEXT');
	    const noContactWarningGuide = this.getWarningGuide(title, text);
	    noContactWarningGuide.showNextStep();
	  }
	  updateSenderList() {
	    this.currentFromList = this.channel.fromList;
	    this.currentFrom = this.channel.fromList[0];
	    if (this.settingsMenu) {
	      this.settingsMenu.removeMenuItem('sharing_sender');
	      const item = this.getSharingSenderItem();
	      this.settingsMenu.addMenuItem(item);
	    }
	  }
	  copyLink(link) {
	    BX.clipboard.copy(link);
	    BX.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_COPY_LINK_NOTIFICATION')
	    });
	    main_core_events.EventEmitter.emit('CalendarSharing:LinkCopied');
	  }

	  /**
	   * @override
	   */
	  save() {
	    return this.saveLinkAction();
	  }
	  saveLinkAction(options = {
	    isActionCopy: false
	  }) {
	    let action;
	    let data = {
	      ownerId: this.getEntityId(),
	      ownerTypeId: this.getEntityTypeId()
	    };
	    if (this.isContactAvailable() && this.isChannelsAvailable() && !options.isActionCopy) {
	      action = 'crm.api.timeline.calendar.sharing.sendLink';
	      data.contactId = this.contact.entityId || null;
	      data.contactTypeId = this.contact.entityTypeId || null;
	      data.channelId = this.channel.id || null;
	      data.senderId = this.currentFrom.id || null;
	    } else {
	      action = 'crm.api.timeline.calendar.sharing.onLinkCopied';
	      data.linkHash = this.link.hash;
	    }
	    return BX.ajax.runAction(action, {
	      data
	    }).then(response => {
	      if (response.data) {
	        this.emitFinishEditEvent();
	        return true;
	      }
	      return false;
	    }, error => {
	      console.error(error);
	      return false;
	    });
	  }
	  onContactsChangedHandler(event) {
	    const {
	      item,
	      current
	    } = event.getData();
	    const isCurrentDeal = this.getEntityTypeId() === (item == null ? void 0 : item.entityTypeId) && this.getEntityId() === (item == null ? void 0 : item.entityId);
	    if (!isCurrentDeal || !main_core.Type.isArray(current)) {
	      return;
	    }
	    const phoneReceivers = current.filter(receiver => receiver.address.typeId === 'PHONE');
	    const contacts = [];
	    const contactsHashes = [];
	    for (const receiver of phoneReceivers) {
	      var _receiver$addressSour;
	      if (contactsHashes.includes(receiver.addressSource.hash)) {
	        continue;
	      }
	      contactsHashes.push(receiver.addressSource.hash);
	      contacts.push({
	        entityId: receiver.addressSource.entityId,
	        entityTypeId: receiver.addressSource.entityTypeId,
	        name: (_receiver$addressSour = receiver.addressSourceData) == null ? void 0 : _receiver$addressSour.title,
	        phone: receiver.address.value
	      });
	    }
	    this.setContacts(contacts);
	    this.updateSettingsButton();
	    if (this.isSettingsPopupShown()) {
	      this.showSettingsPopup();
	    }
	  }
	  setContacts(contacts) {
	    var _contacts$find;
	    this.contacts = contacts.filter(contact => contact.entityId && contact.entityTypeId && contact.phone && contact.name).sort((a, b) => a.entityId - b.entityId) // sort by id
	    .sort((a, b) => a.entityTypeId - b.entityTypeId); // sort company last

	    this.contact = (_contacts$find = contacts.find(contact => {
	      var _this$contact, _this$contact2;
	      return contact.entityTypeId === ((_this$contact = this.contact) == null ? void 0 : _this$contact.entityTypeId) && contact.entityId === ((_this$contact2 = this.contact) == null ? void 0 : _this$contact2.entityId);
	    })) != null ? _contacts$find : this.contacts[0];
	  }
	  setCommunicationChannels(channels, selectedId) {
	    this.channels = channels || [];
	    if (selectedId) {
	      var _channels$find;
	      this.channel = (_channels$find = channels.find(channel => {
	        return channel.id === selectedId;
	      })) != null ? _channels$find : this.channels[0];
	    } else {
	      this.channel = this.channels ? this.channels[0] : null;
	    }
	    if (this.channel && this.channel.fromList) {
	      this.currentFromList = this.channel.fromList;
	      this.currentFrom = this.channel.fromList[0];
	    }
	  }
	  isContactAvailable() {
	    return main_core.Type.isArrayFilled(this.contacts);
	  }
	  isChannelsAvailable() {
	    return main_core.Type.isArrayFilled(this.channels);
	  }
	  openHelpDesk() {
	    if (top.BX.Helper) {
	      top.BX.Helper.show(`redirect=detail&code=${this.HELPDESK_CODE}`);
	    }
	  }
	  getWarningGuide(title, text) {
	    const warningGuide = new ui_tour.Guide({
	      simpleMode: true,
	      onEvents: true,
	      steps: [{
	        target: this.DOM.sendButton,
	        title,
	        text,
	        condition: {
	          top: false,
	          bottom: true,
	          color: 'warning'
	        }
	      }]
	    });
	    const guidePopup = warningGuide.getPopup();
	    main_core.Dom.addClass(guidePopup.popupContainer, 'crm-calendar-sharing-configure-slots-popup-ui-tour-animate');
	    guidePopup.setWidth(390);
	    const guideContent = guidePopup.getContentContainer().firstElementChild;
	    const offsetFromCloseIcon = parseInt(getComputedStyle(guidePopup.closeIcon)['width']);
	    const existingPadding = parseInt(getComputedStyle(guideContent)['paddingRight']);
	    guidePopup.getContentContainer().style.paddingRight = offsetFromCloseIcon - existingPadding + 'px';
	    guidePopup.setAutoHide(true);
	    guidePopup.subscribe('onAfterShow', () => {
	      setTimeout(() => {
	        const arrowContainer = guidePopup.angle.element;
	        const arrow = arrowContainer.firstElementChild;
	        arrow.style.border = '2px solid var(--ui-color-text-warning, #ffa900)';
	        if (guidePopup.getContentContainer().getBoundingClientRect().top > this.DOM.sendButton.getBoundingClientRect().top) {
	          const condition = guidePopup.getContentContainer().querySelector('.ui-tour-popup-condition-bottom');
	          condition.className = 'ui-tour-popup-condition-top';
	          arrowContainer.style.top = '-20px';
	        } else {
	          arrowContainer.style.bottom = '-18px';
	        }
	      }, 0);
	    });
	    return warningGuide;
	  }
	  payAttentionToNewFeature() {
	    const guide = this.getGuide();
	    const pulsar = this.getPulsar();
	    setTimeout(() => {
	      guide.showNextStep();
	      pulsar.show();
	    }, 1000);
	  }
	  getGuide() {
	    const guide = new ui_tour.Guide({
	      simpleMode: true,
	      onEvents: true,
	      steps: [{
	        target: this.DOM.menuBarItem,
	        title: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_PAY_ATTENTION_TO_NEW_FEATURE_TITLE'),
	        text: main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_PAY_ATTENTION_TO_NEW_FEATURE_TEXT'),
	        article: this.HELPDESK_CODE,
	        condition: {
	          top: true,
	          bottom: false,
	          color: 'primary'
	        }
	      }]
	    });
	    const guidePopup = guide.getPopup();
	    main_core.Dom.addClass(guidePopup.popupContainer, 'crm-calendar-sharing-configure-slots-popup-ui-tour-animate');
	    guidePopup.setWidth(400);
	    guidePopup.getContentContainer().style.paddingRight = getComputedStyle(guidePopup.closeIcon)['width'];
	    return guide;
	  }
	  getPulsar() {
	    const pulsar = new BX.SpotLight({
	      targetElement: this.DOM.menuBarItem,
	      targetVertex: 'middle-center'
	    });
	    pulsar.bindEvents({
	      'onTargetEnter': () => pulsar.close()
	    });
	    return pulsar;
	  }
	}

	/** @memberof BX.Crm.Timeline.Tools */
	class WaitConfigurationDialog {
	  constructor() {
	    this._id = "";
	    this._settings = {};
	    this._type = Wait.WaitingType.undefined;
	    this._duration = 0;
	    this._target = "";
	    this._targetDates = null;
	    this._container = null;
	    this._durationMeasureNode = null;
	    this._durationInput = null;
	    this._targetDateNode = null;
	    this._popup = null;
	  }
	  initialize(id, settings) {
	    this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	    this._settings = settings ? settings : {};
	    this._type = BX.prop.getInteger(this._settings, "type", Wait.WaitingType.after);
	    this._duration = BX.prop.getInteger(this._settings, "duration", 1);
	    this._target = BX.prop.getString(this._settings, "target", "");
	    this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);
	    this._menuId = this._id + "_target_date_sel";
	  }
	  getId() {
	    return this._id;
	  }
	  getType() {
	    return this._type;
	  }
	  setType(type) {
	    this._type = type;
	  }
	  getDuration() {
	    return this._duration;
	  }
	  setDuration(duration) {
	    this._duration = duration;
	  }
	  getTarget() {
	    return this._target;
	  }
	  setTarget(target) {
	    this._target = target;
	  }
	  getMessage(name) {
	    const m = WaitConfigurationDialog.messages;
	    return m.hasOwnProperty(name) ? m[name] : name;
	  }
	  getDurationText(duration, enableNumber) {
	    return Wait.Helper.getDurationText(duration, enableNumber);
	  }
	  getTargetDateCaption(name) {
	    const length = this._targetDates.length;
	    for (let i = 0; i < length; i++) {
	      const info = this._targetDates[i];
	      if (info["name"] === name) {
	        return info["caption"];
	      }
	    }
	    return "";
	  }
	  open() {
	    this._popup = new BX.PopupWindow(this._id, null,
	    //this._configSelector,
	    {
	      autoHide: true,
	      draggable: false,
	      bindOptions: {
	        forceBindPosition: false
	      },
	      closeByEsc: true,
	      zIndex: 0,
	      content: this.prepareDialogContent(),
	      events: {
	        onPopupShow: BX.delegate(this.onPopupShow, this),
	        onPopupClose: BX.delegate(this.onPopupClose, this),
	        onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
	      },
	      buttons: [new BX.PopupWindowButton({
	        text: main_core.Loc.getMessage('CRM_TIMELINE_CHOOSE'),
	        className: "popup-window-button-accept",
	        events: {
	          click: BX.delegate(this.onSaveButtonClick, this)
	        }
	      }), new BX.PopupWindowButtonLink({
	        text: BX.message("JS_CORE_WINDOW_CANCEL"),
	        events: {
	          click: BX.delegate(this.onCancelButtonClick, this)
	        }
	      })]
	    });
	    this._popup.show();
	  }
	  close() {
	    if (this._popup) {
	      this._popup.close();
	    }
	  }
	  prepareDialogContent() {
	    const container = BX.create("div", {
	      attrs: {
	        className: "crm-wait-popup-select-block"
	      }
	    });
	    const wrapper = BX.create("div", {
	      attrs: {
	        className: "crm-wait-popup-select-wrapper"
	      }
	    });
	    container.appendChild(wrapper);
	    this._durationInput = BX.create("input", {
	      attrs: {
	        type: "text",
	        className: "crm-wait-popup-settings-input",
	        value: this._duration
	      },
	      events: {
	        keyup: BX.delegate(this.onDurationChange, this)
	      }
	    });
	    this._durationMeasureNode = BX.create("span", {
	      attrs: {
	        className: "crm-wait-popup-settings-title"
	      },
	      text: this.getDurationText(this._duration, false)
	    });
	    if (this._type === Wait.WaitingType.after) {
	      wrapper.appendChild(BX.create("span", {
	        attrs: {
	          className: "crm-wait-popup-settings-title"
	        },
	        text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_AFTER')
	      }));
	      wrapper.appendChild(this._durationInput);
	      wrapper.appendChild(this._durationMeasureNode);
	    } else {
	      wrapper.appendChild(BX.create("span", {
	        attrs: {
	          className: "crm-wait-popup-settings-title"
	        },
	        text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_BEFORE')
	      }));
	      wrapper.appendChild(this._durationInput);
	      wrapper.appendChild(this._durationMeasureNode);
	      wrapper.appendChild(BX.create("span", {
	        attrs: {
	          className: "crm-wait-popup-settings-title"
	        },
	        text: " " + main_core.Loc.getMessage('CRM_TIMELINE_WAIT_TARGET_PREFIX_TYPE_BEFORE')
	      }));
	      this._targetDateNode = BX.create("span", {
	        attrs: {
	          className: "crm-automation-popup-settings-link"
	        },
	        text: this.getTargetDateCaption(this._target),
	        events: {
	          click: BX.delegate(this.toggleTargetMenu, this)
	        }
	      });
	      wrapper.appendChild(this._targetDateNode);
	    }
	    return container;
	  }
	  onDurationChange() {
	    let duration = parseInt(this._durationInput.value);
	    if (isNaN(duration) || duration <= 0) {
	      duration = 1;
	    }
	    this._duration = duration;
	    this._durationMeasureNode.innerHTML = BX.util.htmlspecialchars(this.getDurationText(duration, false));
	  }
	  toggleTargetMenu() {
	    if (this.isTargetMenuOpened()) {
	      this.closeTargetMenu();
	    } else {
	      this.openTargetMenu();
	    }
	  }
	  isTargetMenuOpened() {
	    return !!BX.PopupMenu.getMenuById(this._menuId);
	  }
	  openTargetMenu() {
	    const menuItems = [];
	    let i = 0;
	    const length = this._targetDates.length;
	    for (; i < length; i++) {
	      const info = this._targetDates[i];
	      menuItems.push({
	        text: info["caption"],
	        title: info["caption"],
	        value: info["name"],
	        onclick: BX.delegate(this.onTargetSelect, this)
	      });
	    }
	    BX.PopupMenu.show(this._menuId, this._targetDateNode, menuItems, {
	      zIndex: 200,
	      autoHide: true,
	      offsetLeft: BX.pos(this._targetDateNode)["width"] / 2,
	      angle: {
	        position: 'top',
	        offset: 0
	      }
	    });
	  }
	  closeTargetMenu() {
	    BX.PopupMenu.destroy(this._menuId);
	  }
	  onPopupShow(e, item) {}
	  onPopupClose() {
	    if (this._popup) {
	      this._popup.destroy();
	    }
	    this.closeTargetMenu();
	  }
	  onPopupDestroy() {
	    if (this._popup) {
	      this._popup = null;
	    }
	  }
	  onSaveButtonClick(e) {
	    const callback = BX.prop.getFunction(this._settings, "onSave", null);
	    if (!callback) {
	      return;
	    }
	    const params = {
	      type: this._type
	    };
	    params["duration"] = this._duration;
	    params["target"] = this._type === Wait.WaitingType.before ? this._target : "";
	    callback(this, params);
	  }
	  onCancelButtonClick(e) {
	    const callback = BX.prop.getFunction(this._settings, "onCancel", null);
	    if (callback) {
	      callback(this);
	    }
	  }
	  onTargetSelect(e, item) {
	    const fieldName = BX.prop.getString(item, "value", "");
	    if (fieldName !== "") {
	      this._target = fieldName;
	      this._targetDateNode.innerHTML = BX.util.htmlspecialchars(this.getTargetDateCaption(fieldName));
	    }
	    this.closeTargetMenu();
	    e.preventDefault ? e.preventDefault() : e.returnValue = false;
	  }
	  static create(id, settings) {
	    const self = new WaitConfigurationDialog();
	    self.initialize(id, settings);
	    return self;
	  }
	}
	WaitConfigurationDialog.messages = {};

	let _$4 = t => t,
	  _t$4,
	  _t2$4,
	  _t3$4,
	  _t4$3,
	  _t5$2;

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _waitConfigContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("waitConfigContainer");
	class Wait extends WithEditor {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _waitConfigContainer, {
	      writable: true,
	      value: null
	    });
	  }
	  createLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _waitConfigContainer)[_waitConfigContainer] = main_core.Tag.render(_t$4 || (_t$4 = _$4`<div class="crm-entity-stream-content-wait-conditions"></div>`));
	    this._saveButton = main_core.Tag.render(_t2$4 || (_t2$4 = _$4`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CREATE_WAITING'));
	    this._cancelButton = main_core.Tag.render(_t3$4 || (_t3$4 = _$4`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	    this._input = main_core.Tag.render(_t4$3 || (_t4$3 = _$4`<textarea rows="1" class="crm-entity-stream-content-wait-comment-textarea" placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_WAIT_PLACEHOLDER'));
	    return main_core.Tag.render(_t5$2 || (_t5$2 = _$4`<div class="crm-entity-stream-content-wait-detail --focus --hidden">
			<div class="crm-entity-stream-content-wait-conditions-container">
				${0}
			</div>
			${0}
			<div class="crm-entity-stream-content-wait-comment-btn-container">
				${0}
				${0}
			</div>
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _waitConfigContainer)[_waitConfigContainer], this._input, this._saveButton, this._cancelButton);
	  }
	  doInitialize() {
	    this._isRequestRunning = false;
	    this._isLocked = false;
	    this._hideButtonsOnBlur = false;
	    //region Config
	    this._type = Wait.WaitingType.after;
	    this._duration = 1;
	    this._target = "";
	    this._configSelector = null;
	    //endregion

	    this._isMenuShown = false;
	    this._menu = null;
	    this._configDialog = null;
	    this._serviceUrl = this.getSetting('serviceUrl', '');
	    const config = this.getSetting('config', {});
	    this._type = Wait.WaitingType.resolveTypeId(BX.prop.getString(config, 'type', Wait.WaitingType.names.after));
	    this._duration = BX.prop.getInteger(config, 'duration', 1);
	    this._target = BX.prop.getString(config, 'target', '');
	    this._targetDates = this.getSetting('targetDates', []);
	    this.layoutConfigurationSummary();
	  }
	  getDurationText(duration, enableNumber) {
	    return Wait.Helper.getDurationText(duration, enableNumber);
	  }
	  getTargetDateCaption(name) {
	    let i = 0;
	    const length = this._targetDates.length;
	    for (; i < length; i++) {
	      const info = this._targetDates[i];
	      if (info["name"] === name) {
	        return info["caption"];
	      }
	    }
	    return "";
	  }
	  onSelectorClick(e) {
	    if (!this._isMenuShown) {
	      this.openMenu();
	    } else {
	      this.closeMenu();
	    }
	    e.preventDefault ? e.preventDefault() : e.returnValue = false;
	  }
	  openMenu() {
	    if (this._isMenuShown) {
	      return;
	    }
	    const handler = BX.delegate(this.onMenuItemClick, this);
	    const menuItems = [{
	      id: "day_1",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_1D'),
	      onclick: handler
	    }, {
	      id: "day_2",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_2D'),
	      onclick: handler
	    }, {
	      id: "day_3",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_3D'),
	      onclick: handler
	    }, {
	      id: "week_1",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_1W'),
	      onclick: handler
	    }, {
	      id: "week_2",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_2W'),
	      onclick: handler
	    }, {
	      id: "week_3",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_3W'),
	      onclick: handler
	    }];
	    const customMenu = {
	      id: "custom",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_CUSTOM'),
	      items: []
	    };
	    customMenu["items"].push({
	      id: "afterDays",
	      text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_AFTER_CUSTOM_DAYS'),
	      onclick: handler
	    });
	    if (this._targetDates.length > 0) {
	      customMenu["items"].push({
	        id: "beforeDate",
	        text: main_core.Loc.getMessage('CRM_TIMELINE_WAIT_BEFORE_CUSTOM_DATE'),
	        onclick: handler
	      });
	    }
	    menuItems.push(customMenu);
	    BX.PopupMenu.show(this._id, this._configSelector, menuItems, {
	      offsetTop: 0,
	      offsetLeft: 36,
	      angle: {
	        position: "top",
	        offset: 0
	      },
	      events: {
	        onPopupShow: BX.delegate(this.onMenuShow, this),
	        onPopupClose: BX.delegate(this.onMenuClose, this),
	        onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
	      }
	    });
	    this._menu = BX.PopupMenu.currentItem;
	  }
	  closeMenu() {
	    if (!this._isMenuShown) {
	      return;
	    }
	    if (this._menu) {
	      this._menu.close();
	    }
	  }
	  onMenuItemClick(e, item) {
	    this.closeMenu();
	    if (item.id === "afterDays" || item.id === "beforeDate") {
	      this.openConfigDialog(item.id === "afterDays" ? Wait.WaitingType.after : Wait.WaitingType.before);
	      return;
	    }
	    const params = {
	      type: Wait.WaitingType.after
	    };
	    if (item.id === "day_1") {
	      params["duration"] = 1;
	    } else if (item.id === "day_2") {
	      params["duration"] = 2;
	    } else if (item.id === "day_3") {
	      params["duration"] = 3;
	    }
	    if (item.id === "week_1") {
	      params["duration"] = 7;
	    } else if (item.id === "week_2") {
	      params["duration"] = 14;
	    } else if (item.id === "week_3") {
	      params["duration"] = 21;
	    }
	    this.saveConfiguration(params);
	  }
	  openConfigDialog(type) {
	    if (!this._configDialog) {
	      this._configDialog = WaitConfigurationDialog.create("", {
	        targetDates: this._targetDates,
	        onSave: BX.delegate(this.onConfigDialogSave, this),
	        onCancel: BX.delegate(this.onConfigDialogCancel, this)
	      });
	    }
	    this._configDialog.setType(type);
	    this._configDialog.setDuration(this._duration);
	    let target = this._target;
	    if (target === "" && this._targetDates.length > 0) {
	      target = this._targetDates[0]["name"];
	    }
	    this._configDialog.setTarget(target);
	    this._configDialog.open();
	  }
	  onConfigDialogSave(sender, params) {
	    this.saveConfiguration(params);
	    this._configDialog.close();
	  }
	  onConfigDialogCancel(sender) {
	    this._configDialog.close();
	  }
	  onMenuShow() {
	    this._isMenuShown = true;
	  }
	  onMenuClose() {
	    if (this._menu && this._menu.popupWindow) {
	      this._menu.popupWindow.destroy();
	    }
	  }
	  onMenuDestroy() {
	    this._isMenuShown = false;
	    this._menu = null;
	    if (typeof BX.PopupMenu.Data[this._id] !== "undefined") {
	      delete BX.PopupMenu.Data[this._id];
	    }
	  }
	  saveConfiguration(params) {
	    //region Parse params
	    this._type = BX.prop.getInteger(params, "type", Wait.WaitingType.after);
	    this._duration = BX.prop.getInteger(params, "duration", 0);
	    if (this._duration <= 0) {
	      this._duration = 1;
	    }
	    this._target = this._type === Wait.WaitingType.before ? BX.prop.getString(params, "target", "") : "";
	    //endregion
	    //region Save settings
	    const optionName = this.getSetting('optionName');
	    BX.userOptions.save("crm.timeline.wait", optionName, "type", this._type === Wait.WaitingType.after ? "after" : "before");
	    BX.userOptions.save("crm.timeline.wait", optionName, "duration", this._duration);
	    BX.userOptions.save("crm.timeline.wait", optionName, "target", this._target);
	    //endregion
	    this.layoutConfigurationSummary();
	  }
	  getSummaryHtml() {
	    if (this._type === Wait.WaitingType.before) {
	      return main_core.Loc.getMessage('CRM_TIMELINE_WAIT_COMPLETION_TYPE_BEFORE').replace("#DURATION#", this.getDurationText(this._duration, true)).replace("#TARGET_DATE#", this.getTargetDateCaption(this._target));
	    }
	    return main_core.Loc.getMessage('CRM_TIMELINE_WAIT_COMPLETION_TYPE_AFTER').replace("#DURATION#", this.getDurationText(this._duration, true));
	  }
	  getSummaryText() {
	    return BX.util.strip_tags(this.getSummaryHtml());
	  }
	  layoutConfigurationSummary() {
	    babelHelpers.classPrivateFieldLooseBase(this, _waitConfigContainer)[_waitConfigContainer].innerHTML = this.getSummaryHtml();
	    this._configSelector = babelHelpers.classPrivateFieldLooseBase(this, _waitConfigContainer)[_waitConfigContainer].querySelector("a");
	    if (this._configSelector) {
	      BX.bind(this._configSelector, 'click', this.onSelectorClick.bind(this));
	    }
	  }
	  postpone(id, offset, callback) {
	    BX.ajax({
	      url: this._serviceUrl,
	      method: "POST",
	      dataType: "json",
	      data: {
	        "ACTION": "POSTPONE_WAIT",
	        "DATA": {
	          "ID": id,
	          "OFFSET": offset
	        }
	      },
	      onsuccess: callback
	    });
	  }
	  complete(id, completed, callback) {
	    BX.ajax({
	      url: this._serviceUrl,
	      method: "POST",
	      dataType: "json",
	      data: {
	        "ACTION": "COMPLETE_WAIT",
	        "DATA": {
	          "ID": id,
	          "COMPLETED": completed ? 'Y' : 'N'
	        }
	      },
	      onsuccess: callback
	    });
	  }
	  save() {
	    if (this._isRequestRunning || this._isLocked) {
	      return;
	    }
	    let description = this.getSummaryText();
	    const comment = BX.util.trim(this._input.value);
	    if (comment !== "") {
	      description += "\n" + comment;
	    }
	    const data = {
	      ID: 0,
	      typeId: this._type,
	      duration: this._duration,
	      targetFieldName: this._target,
	      subject: "",
	      description: description,
	      completed: 0,
	      ownerType: BX.CrmEntityType.resolveName(this.getEntityTypeId()),
	      ownerID: this.getEntityId()
	    };
	    BX.ajax({
	      url: this._serviceUrl,
	      method: "POST",
	      dataType: "json",
	      data: {
	        "ACTION": "SAVE_WAIT",
	        "DATA": data
	      },
	      onsuccess: BX.delegate(this.onSaveSuccess, this),
	      onfailure: BX.delegate(this.onSaveFailure, this)
	    });
	    this._isRequestRunning = this._isLocked = true;
	  }
	  cancel() {
	    this._input.value = "";
	    this._input.style.minHeight = "";
	    this.release();
	  }
	  onSaveSuccess(data) {
	    this._isRequestRunning = this._isLocked = false;
	    const error = BX.prop.getString(data, "ERROR", "");
	    if (error !== "") {
	      alert(error);
	      return;
	    }
	    this._input.value = "";
	    this._input.style.minHeight = "";
	    this.emitFinishEditEvent();
	    this.release();
	  }
	  onSaveFailure() {
	    this._isRequestRunning = this._isLocked = false;
	  }
	  getMessage(name) {
	    const m = Wait.messages;
	    return m.hasOwnProperty(name) ? m[name] : name;
	  }
	}
	Wait.WaitingType = {
	  undefined: 0,
	  after: 1,
	  before: 2,
	  names: {
	    after: "after",
	    before: "before"
	  },
	  resolveTypeId: function (name) {
	    if (name === this.names.after) {
	      return this.after;
	    } else if (name === this.names.before) {
	      return this.before;
	    }
	    return this.undefined;
	  }
	};
	Wait.messages = {};
	Wait.Helper = {
	  getDurationText: function (duration, enableNumber) {
	    enableNumber = !!enableNumber;
	    let result = "";
	    let type = "D";
	    if (enableNumber) {
	      if (duration % 7 === 0) {
	        duration = duration / 7;
	        type = "W";
	      }
	    }
	    if (type === "W") {
	      result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_WEEK', duration);
	    } else {
	      result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_DAY', duration);
	    }
	    if (enableNumber) {
	      result = duration.toString() + " " + result;
	    }
	    return result;
	  },
	  getMessage: function (name) {
	    return Wait.Helper.messages.hasOwnProperty(name) ? Wait.Helper.messages[name] : name;
	  },
	  messages: {}
	};

	let _$5 = t => t,
	  _t$5;
	var _editor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editor");
	var _createEditor$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createEditor");
	var _onFinishEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFinishEdit");
	class Zoom extends Item {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onFinishEdit, {
	      value: _onFinishEdit2
	    });
	    Object.defineProperty(this, _createEditor$1, {
	      value: _createEditor2$1
	    });
	    Object.defineProperty(this, _editor, {
	      writable: true,
	      value: null
	    });
	  }
	  showSlider() {
	    if (this.getSetting('isAvailable')) {
	      BX.Crm.Zoom.onNotConnectedHandler(main_core.Loc.getMessage('USER_ID'));
	    } else
	      // not available
	      {
	        BX.Crm.Zoom.onNotAvailableHandler();
	      }
	  }
	  supportsLayout() {
	    return this.getSetting('isConnected') && this.getSetting('isAvailable');
	  }
	  createLayout() {
	    return main_core.Tag.render(_t$5 || (_t$5 = _$5`<div class="crm-entity-stream-content-new-detail ui-timeline-zoom-editor --focus --hidden"></div>`));
	  }
	  onFocus(e) {
	    this.setFocused(true);
	  }
	  onShow() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _createEditor$1)[_createEditor$1]();
	    }
	  }
	}
	function _createEditor2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _editor)[_editor] = new crm_zoom.Zoom({
	    ownerTypeId: this.getEntityTypeId(),
	    ownerId: this.getEntityId(),
	    container: this.getContainer(),
	    onFinishEdit: babelHelpers.classPrivateFieldLooseBase(this, _onFinishEdit)[_onFinishEdit].bind(this)
	  });
	}
	function _onFinishEdit2() {
	  this.emitFinishEditEvent();
	}

	class Delivery extends Item {
	  showSlider() {
	    BX.CrmActivityEditor.getDefault().addDelivery({
	      'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
	      'ownerID': this.getEntityId(),
	      "orderList": BX.CrmTimelineManager.getDefault().getOwnerInfo()['ORDER_LIST']
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	class Visit extends Item {
	  showSlider() {
	    var _this$getSettings;
	    const visitParameters = (_this$getSettings = this.getSettings()) != null ? _this$getSettings : {};
	    visitParameters['OWNER_TYPE'] = BX.CrmEntityType.resolveName(this.getEntityTypeId());
	    visitParameters['OWNER_ID'] = this.getEntityId();
	    BX.CrmActivityVisit.create(visitParameters).showEdit();
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	var _interfaceInitialized = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interfaceInitialized");
	var _initializeInterface = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initializeInterface");
	class RestPlacement extends Item {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _initializeInterface, {
	      value: _initializeInterface2
	    });
	    Object.defineProperty(this, _interfaceInitialized, {
	      writable: true,
	      value: false
	    });
	  }
	  showSlider() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _interfaceInitialized)[_interfaceInitialized]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _interfaceInitialized)[_interfaceInitialized] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _initializeInterface)[_initializeInterface]();
	    }
	    const appId = this.getSetting('appId', '');
	    BX.rest.AppLayout.openApplication(appId, {
	      ID: this.getEntityId()
	    }, {
	      PLACEMENT: this.getSetting('placement', ''),
	      PLACEMENT_ID: this.getSetting('placementId', '')
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}
	function _initializeInterface2() {
	  if (!!top.BX.rest && !!top.BX.rest.AppLayout) {
	    const PlacementInterface = top.BX.rest.AppLayout.initializePlacement(this.getSetting('placement', ''));
	    if (!PlacementInterface.prototype.reloadData) {
	      const entityTypeId = this.getEntityTypeId();
	      const entityId = this.getEntityId();
	      PlacementInterface.prototype.reloadData = function (params, cb) {
	        BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
	        cb();
	      };
	    }
	  }
	}

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _fireUpdateEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fireUpdateEvent");
	class Market extends Item {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _fireUpdateEvent, {
	      value: _fireUpdateEvent2
	    });
	  }
	  showSlider() {
	    BX.rest.Marketplace.open({
	      PLACEMENT: this.getSetting('placement', '')
	    });
	    top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', babelHelpers.classPrivateFieldLooseBase(this, _fireUpdateEvent)[_fireUpdateEvent].bind(this));
	  }
	  supportsLayout() {
	    return false;
	  }
	}
	function _fireUpdateEvent2() {
	  const entityTypeId = this.getEntityTypeId();
	  const entityId = this.getEntityId();
	  setTimeout(function () {
	    console.log('fireUpdate', entityId, entityTypeId);
	    BX.Crm.EntityEvent.fire(BX.Crm.EntityEvent.names.invalidate, entityTypeId, entityId, '');
	  }, 3000);
	}

	class Factory {
	  static createItem(id, context, settings) {
	    let item = null;
	    switch (id) {
	      case 'todo':
	        item = new ToDo();
	        break;
	      case 'comment':
	        item = new Comment();
	        break;
	      case 'sms':
	        item = new Sms();
	        break;
	      case 'call':
	        item = new Call();
	        break;
	      case 'email':
	        item = new Email();
	        break;
	      case 'meeting':
	        item = new Meeting();
	        break;
	      case 'task':
	        item = new Task();
	        break;
	      case 'sharing':
	        item = new Sharing();
	        break;
	      case 'wait':
	        item = new Wait();
	        break;
	      case 'zoom':
	        item = new Zoom();
	        break;
	      case 'delivery':
	        item = new Delivery();
	        break;
	      case 'visit':
	        item = new Visit();
	        break;
	      case 'activity_rest_applist':
	        item = new Market();
	        break;
	    }
	    if (!item && id.match(/^activity_rest_/)) {
	      item = new RestPlacement();
	    }
	    if (item) {
	      item.initialize(context, settings);
	    }
	    return item;
	  }
	}

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _entityTypeId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _isReadonly$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isReadonly");
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _selectedItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedItemId");
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _onItemFinishEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onItemFinishEdit");
	var _getFirstItemIdWithLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFirstItemIdWithLayout");
	var _defaultInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultInstance");
	var _selectMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectMenuItem");
	class MenuBar {
	  constructor(_id, params) {
	    var _params$menuId;
	    Object.defineProperty(this, _selectMenuItem, {
	      value: _selectMenuItem2
	    });
	    Object.defineProperty(this, _getFirstItemIdWithLayout, {
	      value: _getFirstItemIdWithLayout2
	    });
	    Object.defineProperty(this, _onItemFinishEdit, {
	      value: _onItemFinishEdit2
	    });
	    Object.defineProperty(this, _entityTypeId$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityId$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isReadonly$1, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _container$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _selectedItemId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1] = params.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _isReadonly$1)[_isReadonly$1] = params.isReadonly;
	    babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] = document.getElementById(params.containerId);
	    const menuId = (_params$menuId = params.menuId) != null ? _params$menuId : (BX.CrmEntityType.resolveName(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1]) + '_menu').toLowerCase();
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = BX.Main.interfaceButtonsManager.getById(menuId);
	    const context = new Context({
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	      entityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1],
	      isReadonly: babelHelpers.classPrivateFieldLooseBase(this, _isReadonly$1)[_isReadonly$1],
	      menuBarContainer: babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]
	    });
	    params.items.forEach(itemData => {
	      var _itemData$settings;
	      const id = itemData.id;
	      const item = Factory.createItem(id, context, (_itemData$settings = itemData.settings) != null ? _itemData$settings : null);
	      if (item) {
	        item.addFinishEditListener(babelHelpers.classPrivateFieldLooseBase(this, _onItemFinishEdit)[_onItemFinishEdit].bind(this));
	        babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][id] = item;
	      }
	    });
	    this.setActiveItemById(babelHelpers.classPrivateFieldLooseBase(this, _getFirstItemIdWithLayout)[_getFirstItemIdWithLayout]());
	  }
	  getItemById(id) {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][id]) != null ? _babelHelpers$classPr : null;
	  }
	  onMenuItemClick(selectedItemId) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isReadonly$1)[_isReadonly$1]) {
	      return;
	    }
	    this.setActiveItemById(selectedItemId);
	  }
	  setActiveItemById(selectedItemId) {
	    if (!selectedItemId || babelHelpers.classPrivateFieldLooseBase(this, _selectedItemId)[_selectedItemId] === selectedItemId) {
	      return false;
	    }
	    const menuBarItem = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][selectedItemId];
	    if (!menuBarItem) {
	      return false;
	    }
	    menuBarItem.activate();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isReadonly$1)[_isReadonly$1] && menuBarItem.supportsLayout()) {
	      Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]).forEach(itemId => {
	        if (itemId !== selectedItemId) {
	          babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][itemId].deactivate();
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _selectMenuItem)[_selectMenuItem](selectedItemId);
	      babelHelpers.classPrivateFieldLooseBase(this, _selectedItemId)[_selectedItemId] = selectedItemId;
	      return true;
	    }
	    return false;
	  }
	  static create(id, params) {
	    const self = new MenuBar(id, params);
	    MenuBar.instances[id] = self;
	    return self;
	  }
	  static getDefault() {
	    return babelHelpers.classPrivateFieldLooseBase(MenuBar, _defaultInstance)[_defaultInstance];
	  }
	  static setDefault(instance) {
	    babelHelpers.classPrivateFieldLooseBase(MenuBar, _defaultInstance)[_defaultInstance] = instance;
	  }
	  static getById(id) {
	    return MenuBar.instances[id] || null;
	  }
	}
	function _onItemFinishEdit2() {
	  this.setActiveItemById(babelHelpers.classPrivateFieldLooseBase(this, _getFirstItemIdWithLayout)[_getFirstItemIdWithLayout]());
	}
	function _getFirstItemIdWithLayout2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isReadonly$1)[_isReadonly$1]) {
	    return null;
	  }
	  let firstId = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getAllItems().forEach(function (itemElement) {
	    if (firstId === null) {
	      const id = itemElement.dataset.id;
	      const item = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][id];
	      if (item && item.supportsLayout()) {
	        firstId = id;
	      }
	    }
	  }.bind(this));
	  return firstId;
	}
	function _selectMenuItem2(id) {
	  const activeItem = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getItemById(babelHelpers.classPrivateFieldLooseBase(this, _selectedItemId)[_selectedItemId]);
	  const currentDiv = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getItemById(id);
	  let wasActiveInMoreMenu = false;
	  if (currentDiv && activeItem !== currentDiv) {
	    wasActiveInMoreMenu = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].isActiveInMoreMenu();
	    main_core.Dom.addClass(currentDiv, babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getItemData) {
	      const currentDivData = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getItemData(currentDiv);
	      currentDivData['IS_ACTIVE'] = true;
	      if (BX.type.isDomNode(activeItem)) {
	        main_core.Dom.removeClass(activeItem, babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	        const activeItemData = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getItemData(activeItem);
	        activeItemData['IS_ACTIVE'] = false;
	      }
	    }
	    const isActiveInMoreMenu = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].isActiveInMoreMenu();
	    if (isActiveInMoreMenu || wasActiveInMoreMenu) {
	      const submenu = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getSubmenu();
	      if (submenu) {
	        submenu.getMenuItems().forEach(menuItem => {
	          const container = menuItem.getContainer();
	          if (isActiveInMoreMenu && container.title === currentDiv.title) {
	            main_core.Dom.addClass(container, babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	          } else if (wasActiveInMoreMenu && container.title === activeItem.title) {
	            main_core.Dom.removeClass(container, babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	          }
	        });
	      }
	      if (isActiveInMoreMenu) {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMoreButton(), babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	      } else if (wasActiveInMoreMenu) {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMoreButton(), babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].classes.itemActive);
	      }
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].closeSubmenu();
	}
	Object.defineProperty(MenuBar, _defaultInstance, {
	  writable: true,
	  value: null
	});
	MenuBar.instances = {};

	exports.MenuBar = MenuBar;
	exports.Item = Item;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Crm.Activity,BX.Event,BX.Main,BX.UI.Tour,BX.Calendar.Sharing,BX.Crm,BX));
//# sourceMappingURL=toolbar.bundle.js.map

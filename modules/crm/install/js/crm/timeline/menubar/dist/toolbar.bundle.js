/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_entitySelector,ui_iconSet_actions,ui_iconSet_main,ui_iconSet_social,ui_iconSet_api_core,crm_clientSelector,main_loader,ui_buttons,ui_vue3,main_popup,ui_tour,crm_tourManager,calendar_sharing_interface,crm_messagesender,main_core_events,crm_activity_todoEditor,crm_zoom,main_core) {
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
	    this.initializeSettings();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].isReadonly() && this.supportsLayout()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = this.createLayout();
	      main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], this.getMenuBarContainer());
	      this.initializeLayout();
	    }
	    this.showTour();
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getEntityTypeId();
	  }
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getEntityId();
	  }
	  getEntityCategoryId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getEntityCategoryId();
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
	  setLocked(isLocked) {
	    const container = this.getContainer();
	    if (!container) {
	      return;
	    }
	    if (isLocked) {
	      main_core.Dom.addClass(container, '--locked');
	    } else {
	      main_core.Dom.removeClass(container, '--locked');
	    }
	  }
	  isLocked() {
	    const container = this.getContainer();
	    if (!container) {
	      return false;
	    }
	    return main_core.Dom.hasClass(container, '--locked');
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
	  initializeSettings() {}
	  initializeLayout() {}
	  onShow() {}
	  onHide() {}
	  showTour() {}
	}
	Item.ON_FINISH_EDIT_EVENT = 'onFinishEdit';

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
	    this.setLocked(true);
	    const saveResult = this.save();
	    if (saveResult instanceof BX.Promise || saveResult instanceof Promise) {
	      saveResult.then(() => this.setLocked(false), () => this.setLocked(false)).catch(() => this.setLocked(false));
	    } else {
	      this.setLocked(false);
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

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;

	/** @memberof BX.Crm.Timeline.MenuBar */
	class Comment extends WithEditor {
	  createLayout() {
	    this._saveButton = main_core.Tag.render(_t || (_t = _`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SEND'));
	    this._cancelButton = main_core.Tag.render(_t2 || (_t2 = _`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	    this._input = main_core.Tag.render(_t3 || (_t3 = _`<textarea  rows="1" class="crm-entity-stream-content-new-comment-textarea" placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_COMMENT_PLACEHOLDER'));
	    return main_core.Tag.render(_t4 || (_t4 = _`<div class="crm-entity-stream-content-new-detail --hidden">
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

	const ServicesConfig = new Map([['telegrambot', {
	  id: 'telegrambot',
	  available: true,
	  connectLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_TELEGRAM'),
	  inviteLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_TELEGRAM'),
	  title: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_TELEGRAM'),
	  commonClass: '--telegram',
	  iconClass: ui_iconSet_api_core.Social.TELEGRAM_IN_CIRCLE,
	  iconColor: '#2FC6F6'
	}], ['whatsappbyedna', {
	  id: 'whatsappbyedna',
	  available: false,
	  connectLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_WHATSAPP'),
	  inviteLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_WHATSAPP'),
	  soonLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_WHATSAPP'),
	  title: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_WHATSAPP'),
	  commonClass: '--whatsapp',
	  iconClass: ui_iconSet_api_core.Social.WHATSAPP
	}], ['vkgroup', {
	  id: 'vkgroup',
	  available: false,
	  connectLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_VK'),
	  inviteLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_VK'),
	  soonLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_VK'),
	  title: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_VK'),
	  region: 'ru',
	  commonClass: '--vk',
	  iconClass: ui_iconSet_api_core.Social.VK
	}], ['facebook', {
	  id: 'facebook',
	  available: false,
	  connectLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_FACEBOOK'),
	  inviteLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_FACEBOOK'),
	  soonLabel: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_FACEBOOK'),
	  title: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_FACEBOOK'),
	  region: '!ru',
	  commonClass: '--facebook',
	  iconClass: ui_iconSet_api_core.Social.FACEBOOK
	}]]);

	const UserOptions = main_core.Reflection.namespace('BX.userOptions');
	const ARTICLE_CODE = '18114500';

	/** @memberof BX.Crm.Timeline.MenuBar.GoToChat */
	var _guideBindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("guideBindElement");
	var _targetElementRect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetElementRect");
	var _observerTimeoutId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observerTimeoutId");
	var _guide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("guide");
	var _spotlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("spotlight");
	var _createSpotlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createSpotlight");
	var _createGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createGuide");
	class Tour {
	  constructor() {
	    Object.defineProperty(this, _createGuide, {
	      value: _createGuide2
	    });
	    Object.defineProperty(this, _createSpotlight, {
	      value: _createSpotlight2
	    });
	    Object.defineProperty(this, _guideBindElement, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _targetElementRect, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _observerTimeoutId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _guide, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _spotlight, {
	      writable: true,
	      value: null
	    });
	    this.onWindowResize = main_core.Runtime.debounce(this.onWindowResize.bind(this), 100);
	    main_core.Event.bind(window, 'resize', this.onWindowResize);
	  }
	  canShow() {
	    return true;
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight] = babelHelpers.classPrivateFieldLooseBase(this, _createSpotlight)[_createSpotlight]();
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight].show();
	    this.getGuide().showNextStep();
	  }
	  getGuide() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide] = babelHelpers.classPrivateFieldLooseBase(this, _createGuide)[_createGuide](this.getGuideBindElement(), {
	        onClose: () => {
	          UserOptions.save('crm', 'gotochat', 'isTimelineTourViewedInWeb', 1);
	          babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight].close();
	          if (babelHelpers.classPrivateFieldLooseBase(this, _observerTimeoutId)[_observerTimeoutId]) {
	            clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _observerTimeoutId)[_observerTimeoutId]);
	            babelHelpers.classPrivateFieldLooseBase(this, _observerTimeoutId)[_observerTimeoutId] = null;
	          }
	          main_core.Event.unbind(window, 'resize', this.onWindowResize);
	        }
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide];
	  }
	  onWindowResize() {
	    const target = this.getGuideBindElement(true);
	    babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getCurrentStep().setTarget(target);
	    babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].showNextStep();
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight].setTargetElement(target);
	  }
	  getGuideBindElement(force = false) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement] || force) {
	      babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement] = document.querySelector('[data-id="gotochat"]');
	      if (babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement].offsetTop) {
	        babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement] = babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement].parentElement.nextElementSibling;
	      }
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement];
	  }
	  handleTargetElementResize() {
	    const currentRect = main_core.Dom.getPosition(this.getGuideBindElement());
	    if (currentRect.left !== babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect].left || currentRect.right !== babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect].right || currentRect.top !== babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect].top || currentRect.bottom !== babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect].bottom) {
	      babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect] = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement]);
	      const targetElement = babelHelpers.classPrivateFieldLooseBase(this, _guideBindElement)[_guideBindElement];
	      const isVisible = Boolean(targetElement.offsetWidth || targetElement.offsetHeight || targetElement.getClientRects().length > 0);
	      const guidePopup = babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getPopup();
	      if (isVisible) {
	        main_core.Dom.removeClass(guidePopup.popupContainer, '--hidden');
	        guidePopup.adjustPosition();
	      } else {
	        main_core.Dom.addClass(guidePopup.popupContainer, '--hidden');
	      }
	    }
	  }
	}
	function _createSpotlight2() {
	  return new BX.SpotLight({
	    id: 'spotlight-crm-timeline-gotochat-guide',
	    targetElement: this.getGuideBindElement(),
	    autoSave: 'no',
	    targetVertex: 'middle-center',
	    zIndex: 200
	  });
	}
	function _createGuide2(target, guideEvents = {}) {
	  const guideText = {
	    title: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_TITLE'),
	    text: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_TEXT')
	  };
	  const guide = new ui_tour.Guide({
	    onEvents: true,
	    steps: [{
	      target,
	      title: guideText.title,
	      text: guideText.text,
	      position: 'bottom',
	      events: guideEvents,
	      article: ARTICLE_CODE,
	      linkTitle: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_ARTICLE_TITLE'),
	      rounded: true
	    }]
	  });
	  const guidePopup = guide.getPopup();
	  guidePopup.setWidth(400);
	  const link = guidePopup.contentContainer.querySelector('.ui-tour-popup-link');
	  main_core.Dom.addClass(link, 'crm-entity-stream-content-new-detail-gotochat-guide-link');
	  babelHelpers.classPrivateFieldLooseBase(this, _targetElementRect)[_targetElementRect] = main_core.Dom.getPosition(this.getGuideBindElement());
	  babelHelpers.classPrivateFieldLooseBase(this, _observerTimeoutId)[_observerTimeoutId] = setInterval(this.handleTargetElementResize.bind(this), 1000);
	  return guide;
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4$1,
	  _t5,
	  _t6,
	  _t7;
	const MENU_ITEM_STUB_ID = 'stub';
	const ACTIVE_MENU_ITEM_CLASS = 'menu-popup-item-accept';
	const DEFAULT_MENU_ITEM_CLASS = 'menu-popup-item-none';
	const TOOLBAR_CONTAINER_CLASS = 'crm-entity-stream-content-gotochat-toolbar-container';
	const BUTTONS_CONTAINER_CLASS = 'crm-entity-stream-content-gotochat-buttons-container';
	const CLIENTS_SELECTOR_TITLE_CLASS = 'crm-entity-stream-content-gotochat-clients-selector-title';
	const HELP_ARTICLE_CODE = '18114500';

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _context$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _chatServiceButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatServiceButtons");
	var _region = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("region");
	var _isTourViewed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTourViewed");
	var _entityEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityEditor");
	var _userSelectorDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userSelectorDialog");
	var _clientSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clientSelector");
	var _subscribeToReceiversChanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToReceiversChanges");
	var _fetchConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchConfig");
	var _prepareParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareParams");
	var _setCommunicationsParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCommunicationsParams");
	var _setChannelDefaultPhoneId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setChannelDefaultPhoneId");
	var _getCurrentChannel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentChannel");
	var _getClientTitleHtmlElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClientTitleHtmlElement");
	var _getUserSelectorDialogTargetNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserSelectorDialogTargetNode");
	var _getClientSelectorEntities = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClientSelectorEntities");
	var _bindClient = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindClient");
	var _getOwnerEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOwnerEntity");
	var _showHelp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showHelp");
	var _showSettingsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsMenu");
	var _getSubmenuStubItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSubmenuStubItems");
	var _onSubMenuShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubMenuShow");
	var _adjustClientTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustClientTitle");
	var _showContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showContent");
	var _showAddClientTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAddClientTitle");
	var _hideContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideContent");
	var _removeCurrentClient = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeCurrentClient");
	var _adjustChatServiceButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustChatServiceButtons");
	var _getServiceButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getServiceButtons");
	var _fillChatServiceButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fillChatServiceButtons");
	var _isServiceSupportedInRegion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isServiceSupportedInRegion");
	var _createChatServiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createChatServiceButton");
	var _renderButtonIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderButtonIcon");
	var _getButtonIconColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonIconColor");
	var _isServiceSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isServiceSelected");
	var _isAvailableService = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAvailableService");
	var _isEntityInEditorMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEntityInEditorMode");
	var _showEditorInEditModePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showEditorInEditModePopup");
	var _getEntityEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityEditor");
	var _showNotSelectedClientNotify = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotSelectedClientNotify");
	var _showNotify = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotify");
	var _setOpenLineItemIsSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOpenLineItemIsSelected");
	var _restoreButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("restoreButton");
	var _getChannelById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getChannelById");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	class GoToChat extends Item {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _getChannelById, {
	      value: _getChannelById2
	    });
	    Object.defineProperty(this, _restoreButton, {
	      value: _restoreButton2
	    });
	    Object.defineProperty(this, _setOpenLineItemIsSelected, {
	      value: _setOpenLineItemIsSelected2
	    });
	    Object.defineProperty(this, _showNotify, {
	      value: _showNotify2
	    });
	    Object.defineProperty(this, _showNotSelectedClientNotify, {
	      value: _showNotSelectedClientNotify2
	    });
	    Object.defineProperty(this, _getEntityEditor, {
	      value: _getEntityEditor2
	    });
	    Object.defineProperty(this, _showEditorInEditModePopup, {
	      value: _showEditorInEditModePopup2
	    });
	    Object.defineProperty(this, _isEntityInEditorMode, {
	      value: _isEntityInEditorMode2
	    });
	    Object.defineProperty(this, _isAvailableService, {
	      value: _isAvailableService2
	    });
	    Object.defineProperty(this, _isServiceSelected, {
	      value: _isServiceSelected2
	    });
	    Object.defineProperty(this, _getButtonIconColor, {
	      value: _getButtonIconColor2
	    });
	    Object.defineProperty(this, _renderButtonIcon, {
	      value: _renderButtonIcon2
	    });
	    Object.defineProperty(this, _createChatServiceButton, {
	      value: _createChatServiceButton2
	    });
	    Object.defineProperty(this, _isServiceSupportedInRegion, {
	      value: _isServiceSupportedInRegion2
	    });
	    Object.defineProperty(this, _fillChatServiceButtons, {
	      value: _fillChatServiceButtons2
	    });
	    Object.defineProperty(this, _getServiceButtons, {
	      value: _getServiceButtons2
	    });
	    Object.defineProperty(this, _adjustChatServiceButtons, {
	      value: _adjustChatServiceButtons2
	    });
	    Object.defineProperty(this, _removeCurrentClient, {
	      value: _removeCurrentClient2
	    });
	    Object.defineProperty(this, _hideContent, {
	      value: _hideContent2
	    });
	    Object.defineProperty(this, _showAddClientTitle, {
	      value: _showAddClientTitle2
	    });
	    Object.defineProperty(this, _showContent, {
	      value: _showContent2
	    });
	    Object.defineProperty(this, _adjustClientTitle, {
	      value: _adjustClientTitle2
	    });
	    Object.defineProperty(this, _onSubMenuShow, {
	      value: _onSubMenuShow2
	    });
	    Object.defineProperty(this, _getSubmenuStubItems, {
	      value: _getSubmenuStubItems2
	    });
	    Object.defineProperty(this, _showSettingsMenu, {
	      value: _showSettingsMenu2
	    });
	    Object.defineProperty(this, _showHelp, {
	      value: _showHelp2
	    });
	    Object.defineProperty(this, _getOwnerEntity, {
	      value: _getOwnerEntity2
	    });
	    Object.defineProperty(this, _bindClient, {
	      value: _bindClient2
	    });
	    Object.defineProperty(this, _getClientSelectorEntities, {
	      value: _getClientSelectorEntities2
	    });
	    Object.defineProperty(this, _getUserSelectorDialogTargetNode, {
	      value: _getUserSelectorDialogTargetNode2
	    });
	    Object.defineProperty(this, _getClientTitleHtmlElement, {
	      value: _getClientTitleHtmlElement2
	    });
	    Object.defineProperty(this, _getCurrentChannel, {
	      value: _getCurrentChannel2
	    });
	    Object.defineProperty(this, _setChannelDefaultPhoneId, {
	      value: _setChannelDefaultPhoneId2
	    });
	    Object.defineProperty(this, _setCommunicationsParams, {
	      value: _setCommunicationsParams2
	    });
	    Object.defineProperty(this, _prepareParams, {
	      value: _prepareParams2
	    });
	    Object.defineProperty(this, _fetchConfig, {
	      value: _fetchConfig2
	    });
	    Object.defineProperty(this, _subscribeToReceiversChanges, {
	      value: _subscribeToReceiversChanges2
	    });
	    Object.defineProperty(this, _context$1, {
	      writable: true,
	      value: null
	    });
	    this.selectedClient = null;
	    this.settingsMenu = null;
	    this.channels = [];
	    this.communications = [];
	    this.currentChannelId = null;
	    this.fromPhoneId = null;
	    this.toName = null;
	    this.toPhoneId = null;
	    this.openLineItems = null;
	    this.isFetchedConfig = false;
	    this.isSending = false;
	    Object.defineProperty(this, _chatServiceButtons, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _region, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isTourViewed, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _entityEditor, {
	      writable: true,
	      value: null
	    });
	    this.marketplaceUrl = '';
	    Object.defineProperty(this, _userSelectorDialog, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _clientSelector, {
	      writable: true,
	      value: null
	    });
	  }
	  initialize(context, settings) {
	    super.initialize(context, settings);
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = context;
	    this.onSelectClient = this.onSelectClient.bind(this);
	    this.onSelectClientPhone = this.onSelectClientPhone.bind(this);
	    this.onSelectSender = this.onSelectSender.bind(this);
	    this.onSelectSenderPhone = this.onSelectSenderPhone.bind(this);
	  }
	  initializeLayout() {
	    super.initializeLayout();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToReceiversChanges)[_subscribeToReceiversChanges]();
	  }
	  initializeSettings() {
	    babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] = this.getSetting('region');
	    babelHelpers.classPrivateFieldLooseBase(this, _isTourViewed)[_isTourViewed] = this.getSetting('isTourViewed');
	  }
	  activate() {
	    super.activate();
	    babelHelpers.classPrivateFieldLooseBase(this, _fetchConfig)[_fetchConfig]();
	  }
	  createLayout() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-gotochat --hidden --skeleton">
			<div class="crm-entity-stream-content-new-detail-gotochat-container hidden">
				<div class="crm-entity-stream-content-gotochat-settings-container">
					<div class="crm-entity-stream-content-gotochat-clients-selector-container">
						<div class="${0}">
							${0}
						</div>
						<div class="crm-entity-stream-content-gotochat-clients-selector-description">
							${0}
						</div>
					</div>
					<div class="${0}">
						<button 
							class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-help"
							onclick="${0}"
						></button>
						<button
							class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-setting"
							onclick="${0}"
						></button>
					</div>
				</div>
				${0}
			</div>
		</div>`), CLIENTS_SELECTOR_TITLE_CLASS, babelHelpers.classPrivateFieldLooseBase(this, _getClientTitleHtmlElement)[_getClientTitleHtmlElement](), main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_SELECTOR_DESCRIPTION'), TOOLBAR_CONTAINER_CLASS, babelHelpers.classPrivateFieldLooseBase(this, _showHelp)[_showHelp], babelHelpers.classPrivateFieldLooseBase(this, _showSettingsMenu)[_showSettingsMenu].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _getServiceButtons)[_getServiceButtons]());
	  }
	  onToggleClientSelector() {
	    const id = 'client-selector-dialog';
	    const {
	      entityTypeId
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getOwnerEntity)[_getOwnerEntity]();
	    const context = `CRM_TIMELINE_GOTOCHAT-${entityTypeId}`;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog] = new ui_entitySelector.Dialog({
	        id,
	        context,
	        targetNode: babelHelpers.classPrivateFieldLooseBase(this, _getUserSelectorDialogTargetNode)[_getUserSelectorDialogTargetNode](),
	        multiple: false,
	        dropdownMode: false,
	        showAvatars: true,
	        enableSearch: true,
	        width: 450,
	        zIndex: 2500,
	        entities: babelHelpers.classPrivateFieldLooseBase(this, _getClientSelectorEntities)[_getClientSelectorEntities](),
	        events: {
	          'Item:onSelect': this.onSelectClient
	        }
	      });
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog].isOpen()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog].hide();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog].setTargetNode(babelHelpers.classPrivateFieldLooseBase(this, _getUserSelectorDialogTargetNode)[_getUserSelectorDialogTargetNode]());
	      babelHelpers.classPrivateFieldLooseBase(this, _userSelectorDialog)[_userSelectorDialog].show();
	    }
	  }
	  async onSelectClient(event) {
	    const {
	      item
	    } = event.getData();
	    this.selectedClient = {
	      entityId: item.id,
	      entityTypeId: BX.CrmEntityType.resolveId(item.entityId)
	    };
	    const isBound = await babelHelpers.classPrivateFieldLooseBase(this, _bindClient)[_bindClient]();
	    if (isBound) {
	      this.adjustLayout();
	      BX.Crm.EntityEditor.getDefault().reload();
	    }
	  }
	  initSettingsMenu() {
	    const menuId = 'crm-gotochat-channels-settings-menu';
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _getSubmenuStubItems)[_getSubmenuStubItems]();
	    this.settingsMenu = main_popup.MenuManager.create({
	      id: menuId,
	      bindElement: document.querySelector(`.${TOOLBAR_CONTAINER_CLASS}`),
	      items: [{
	        delimiter: true,
	        text: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SETTINGS')
	      }, {
	        id: 'channelSubmenu',
	        text: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SENDER_SELECTOR'),
	        items,
	        events: {
	          onSubMenuShow: event => {
	            babelHelpers.classPrivateFieldLooseBase(this, _onSubMenuShow)[_onSubMenuShow](event, this.getChannelsSubmenuItems());
	          }
	        }
	      }, {
	        id: 'phoneSubmenu',
	        text: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_NUMBER_SELECTOR'),
	        items,
	        disabled: !main_core.Type.isArrayFilled(this.getPhoneSubMenuItems()),
	        events: {
	          onSubMenuShow: event => {
	            babelHelpers.classPrivateFieldLooseBase(this, _onSubMenuShow)[_onSubMenuShow](event, this.getPhoneSubMenuItems());
	          }
	        }
	      }]
	    });
	  }

	  // eslint-disable-next-line class-methods-use-this

	  onShowClientPhoneSelector() {
	    const targetNode = document.getElementById('crm-gotochat-client-selector--selected');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _clientSelector)[_clientSelector] && babelHelpers.classPrivateFieldLooseBase(this, _clientSelector)[_clientSelector].isOpen()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _clientSelector)[_clientSelector].hide();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _clientSelector)[_clientSelector] = crm_clientSelector.ClientSelector.createFromCommunications({
	        targetNode,
	        communications: this.communications,
	        events: {
	          onSelect: this.onSelectClientPhone
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _clientSelector)[_clientSelector].setSelected([this.toPhoneId]).show();
	    }
	  }
	  onSelectClientPhone(event) {
	    const {
	      item: {
	        id,
	        customData
	      }
	    } = event.getData();
	    this.selectedClient = {
	      entityId: customData.get('entityId'),
	      entityTypeId: customData.get('entityTypeId')
	    };
	    this.toName = this.getCurrentCommunication().caption;
	    this.toPhoneId = id;
	    this.adjustLayout();
	  }
	  getCurrentPhone() {
	    const client = this.getCurrentCommunication();
	    if (!client || !main_core.Type.isObjectLike(client.phones)) {
	      return null;
	    }
	    return client.phones.find(phone => phone.id === this.toPhoneId);
	  }
	  getCurrentCommunication() {
	    if (!this.selectedClient) {
	      return null;
	    }
	    const {
	      entityTypeId,
	      entityId
	    } = this.selectedClient;
	    return this.communications.find(communication => {
	      return Number(communication.entityTypeId) === Number(entityTypeId) && Number(communication.entityId) === Number(entityId);
	    });
	  }
	  adjustLayout() {
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustClientTitle)[_adjustClientTitle]();
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustChatServiceButtons)[_adjustChatServiceButtons]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showContent)[_showContent]();
	  }
	  async showRegistrarAndSend(code) {
	    if (this.isSending || !babelHelpers.classPrivateFieldLooseBase(this, _isAvailableService)[_isAvailableService](code)) {
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isEntityInEditorMode)[_isEntityInEditorMode]()) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _showEditorInEditModePopup)[_showEditorInEditModePopup]();
	    }
	    if (!this.selectedClient) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotSelectedClientNotify)[_showNotSelectedClientNotify]();
	      return;
	    }
	    if (!this.toPhoneId) {
	      const content = main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_HAVE_NO_PHONE');
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](content);
	      return;
	    }
	    this.showButtonLoader(code);
	    const lineId = await crm_messagesender.ConditionChecker.checkAndGetLine({
	      openLineCode: code,
	      senderType: this.getSenderType(),
	      openLineItems: this.openLineItems
	    });
	    if (lineId) {
	      this.send(lineId, code);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _restoreButton)[_restoreButton](code);
	    }
	  }
	  saveEntityEditor() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getEntityEditor)[_getEntityEditor]().saveChanged();
	  }
	  send(lineId, code) {
	    this.isSending = true;
	    const {
	      entityTypeId: ownerTypeId,
	      entityId: ownerId
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getOwnerEntity)[_getOwnerEntity]();
	    const senderType = this.getSenderType();
	    const senderId = this.currentChannelId;
	    const from = this.fromPhoneId;
	    const to = this.toPhoneId;
	    const ajaxParameters = {
	      ownerTypeId,
	      ownerId,
	      params: {
	        senderType,
	        senderId,
	        from,
	        to,
	        lineId
	      }
	    };
	    main_core.ajax.runAction('crm.activity.gotochat.send', {
	      data: ajaxParameters
	    }).then(() => {
	      this.isSending = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _setOpenLineItemIsSelected)[_setOpenLineItemIsSelected](code);
	      babelHelpers.classPrivateFieldLooseBase(this, _restoreButton)[_restoreButton](code);
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SEND_SUCCESS'));
	      this.emitFinishEditEvent();
	    }).catch(data => {
	      this.isSending = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _restoreButton)[_restoreButton](code);
	      if (data.errors.length > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](data.errors[0].message);
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SEND_ERROR'));
	    });
	  }
	  showButtonLoader(code) {
	    const button = babelHelpers.classPrivateFieldLooseBase(this, _chatServiceButtons)[_chatServiceButtons].get(code);
	    main_core.Dom.addClass(button == null ? void 0 : button.firstElementChild, '--loading');
	  }
	  getSenderType() {
	    return this.currentChannelId === crm_messagesender.Types.bitrix24 ? crm_messagesender.Types.bitrix24 : crm_messagesender.Types.sms;
	  }

	  // eslint-disable-next-line class-methods-use-this
	  getEntityAvatarPath(entityTypeName) {
	    // eslint-disable-next-line no-param-reassign
	    entityTypeName = entityTypeName.toLowerCase();
	    const whiteList = ['contact', 'company', 'lead'];
	    if (!whiteList.includes(entityTypeName)) {
	      return '';
	    }
	    return `/bitrix/images/crm/entity_provider_icons/${entityTypeName}.svg`;
	  }
	  getChannelsSubmenuItems() {
	    const items = [];
	    this.channels.forEach(({
	      id,
	      shortName: text,
	      canUse,
	      fromList
	    }) => {
	      const className = id === this.currentChannelId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS;
	      items.push({
	        id,
	        text,
	        className,
	        disabled: !canUse || !main_core.Type.isArrayFilled(fromList),
	        onclick: this.onSelectSender
	      });
	    });
	    return [...items, {
	      id: 'connectOtherSenderDelimiter',
	      delimiter: true
	    }, {
	      id: 'connectOtherSender',
	      text: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_OTHER_SENDER_SERVICE'),
	      className: DEFAULT_MENU_ITEM_CLASS,
	      onclick: () => BX.SidePanel.Instance.open(this.marketplaceUrl)
	    }];
	  }
	  onSelectSender(event, item) {
	    const {
	      id
	    } = item;
	    this.currentChannelId = id;
	    const channel = babelHelpers.classPrivateFieldLooseBase(this, _getChannelById)[_getChannelById](id);
	    this.fromPhoneId = channel.fromList[0].id;
	    this.settingsMenu.close();
	  }
	  getPhoneSubMenuItems() {
	    const currentChannel = babelHelpers.classPrivateFieldLooseBase(this, _getChannelById)[_getChannelById](this.currentChannelId);
	    const items = [];
	    currentChannel.fromList.forEach(({
	      id,
	      name: text
	    }) => {
	      const className = id === this.fromPhoneId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS;
	      items.push({
	        id,
	        text,
	        className,
	        onclick: this.onSelectSenderPhone
	      });
	    });
	    return items;
	  }
	  onSelectSenderPhone(event, item) {
	    const {
	      id
	    } = item;
	    this.fromPhoneId = id;
	    this.settingsMenu.close();
	  }
	  onHide() {
	    if (this.loader) {
	      this.loader.destroy();
	    }
	  }
	  showTour() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isTourViewed)[_isTourViewed] && !BX.Crm.EntityEditor.getDefault().isNew()) {
	      crm_tourManager.TourManager.getInstance().registerWithLaunch(new Tour());
	    }
	  }
	}
	function _subscribeToReceiversChanges2() {
	  main_core_events.EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', event => {
	    const {
	      item
	    } = event.getData();
	    if (this.getEntityTypeId() !== (item == null ? void 0 : item.entityTypeId) || this.getEntityId() !== (item == null ? void 0 : item.entityId)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _hideContent)[_hideContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _removeCurrentClient)[_removeCurrentClient]();
	    babelHelpers.classPrivateFieldLooseBase(this, _fetchConfig)[_fetchConfig](true);
	  });
	}
	function _fetchConfig2(force = false) {
	  if (this.isFetchedConfig && !force) {
	    return;
	  }
	  this.isFetchedConfig = false;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1].getEntityId()) {
	    return;
	  }
	  const ajaxParameters = {
	    entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1].getEntityTypeId(),
	    entityId: babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1].getEntityId()
	  };
	  main_core.ajax.runAction('crm.activity.gotochat.getConfig', {
	    data: ajaxParameters
	  }).then(({
	    data
	  }) => {
	    this.isFetchedConfig = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _prepareParams)[_prepareParams](data);
	    babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    this.adjustLayout();
	  }).catch(() => babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONFIG_ERROR')));
	}
	function _prepareParams2(data) {
	  const {
	    currentChannelId,
	    channels,
	    communications,
	    openLineItems,
	    marketplaceUrl
	  } = data;
	  this.currentChannelId = currentChannelId;
	  this.channels = channels;
	  this.communications = communications;
	  this.openLineItems = openLineItems;
	  this.marketplaceUrl = marketplaceUrl;
	  babelHelpers.classPrivateFieldLooseBase(this, _setCommunicationsParams)[_setCommunicationsParams]();
	  babelHelpers.classPrivateFieldLooseBase(this, _setChannelDefaultPhoneId)[_setChannelDefaultPhoneId]();
	}
	function _setCommunicationsParams2() {
	  if (this.communications.length === 0) {
	    return;
	  }
	  const communication = this.communications[0];
	  if (Array.isArray(communication.phones) && communication.phones.length > 0) {
	    this.toPhoneId = communication.phones[0].id;
	  }
	  this.selectedClient = {
	    entityId: communication.entityId,
	    entityTypeId: communication.entityTypeId
	  };
	  this.toName = communication.caption;
	}
	function _setChannelDefaultPhoneId2() {
	  const channel = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentChannel)[_getCurrentChannel]();
	  if (!channel || !Array.isArray(channel.fromList) || channel.fromList.length === 0) {
	    return;
	  }
	  const {
	    fromList
	  } = channel;
	  const defaultPhone = fromList.find(item => item.default);
	  this.fromPhoneId = defaultPhone ? defaultPhone.id : fromList[0].id;
	}
	function _getCurrentChannel2() {
	  const channel = this.channels.find(item => item.id === this.currentChannelId);
	  return channel != null ? channel : null;
	}
	function _getClientTitleHtmlElement2() {
	  const clientStart = '<span id="crm-gotochat-client-selector" class="crm-entity-stream-content-gotochat-user-selector-link">';
	  const clientFinish = '</span>';
	  const titleContainer = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<span>
				${0}
			</span>
		`), main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_SELECTOR_TITLE', {
	    '[client]': clientStart,
	    '[/client]': clientFinish
	  }));
	  main_core.Event.bind(titleContainer.childNodes[0], 'click', this.onToggleClientSelector.bind(this));
	  return titleContainer;
	}
	function _getUserSelectorDialogTargetNode2() {
	  return document.getElementById('crm-gotochat-client-selector');
	}
	function _getClientSelectorEntities2() {
	  const contact = {
	    id: 'contact',
	    dynamicLoad: true,
	    dynamicSearch: true,
	    options: {
	      showTab: true,
	      showPhones: true,
	      showMails: true
	    }
	  };
	  const company = {
	    id: 'company',
	    dynamicLoad: true,
	    dynamicSearch: true,
	    options: {
	      excludeMyCompany: true,
	      showTab: true,
	      showPhones: true,
	      showMails: true
	    }
	  };
	  const {
	    entityTypeId
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getOwnerEntity)[_getOwnerEntity]();
	  if (entityTypeId === BX.CrmEntityType.enumeration.contact) {
	    return [company];
	  }
	  if (entityTypeId === BX.CrmEntityType.enumeration.company) {
	    return [contact];
	  }
	  return [contact, company];
	}
	async function _bindClient2() {
	  const {
	    entityId,
	    entityTypeId
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getOwnerEntity)[_getOwnerEntity]();
	  const {
	    entityId: clientId,
	    entityTypeId: clientTypeId
	  } = this.selectedClient;
	  const ajaxParams = {
	    entityId,
	    entityTypeId,
	    clientId,
	    clientTypeId
	  };
	  return new Promise(resolve => {
	    main_core.ajax.runAction('crm.activity.gotochat.bindClient', {
	      data: ajaxParams
	    }).then(({
	      data
	    }) => {
	      if (!data) {
	        resolve(false);
	      }
	      const {
	        channels,
	        communications,
	        currentChannelId
	      } = data;
	      this.channels = channels;
	      this.communications = communications;
	      this.currentChannelId = currentChannelId;
	      babelHelpers.classPrivateFieldLooseBase(this, _setCommunicationsParams)[_setCommunicationsParams]();
	      babelHelpers.classPrivateFieldLooseBase(this, _setChannelDefaultPhoneId)[_setChannelDefaultPhoneId]();
	      resolve(true);
	    }).catch(data => {
	      if (data.errors.length > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](data.errors[0].message);
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_BIND_CLIENT_ERROR'));
	    });
	  });
	}
	function _getOwnerEntity2() {
	  const context = babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1];
	  return {
	    entityId: context.getEntityId(),
	    entityTypeId: context.getEntityTypeId()
	  };
	}
	function _showHelp2() {
	  top.BX.Helper.show(`redirect=detail&code=${HELP_ARTICLE_CODE}`);
	}
	function _showSettingsMenu2() {
	  if (!this.selectedClient) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showNotSelectedClientNotify)[_showNotSelectedClientNotify]();
	    return;
	  }
	  if (!this.settingsMenu) {
	    this.initSettingsMenu();
	  }
	  this.settingsMenu.show();
	}
	function _getSubmenuStubItems2() {
	  // needed for emitted the onSubMenuShow event
	  return [{
	    id: MENU_ITEM_STUB_ID
	  }];
	}
	function _onSubMenuShow2(event, items) {
	  var _target$getSubMenu2;
	  const target = event.getTarget();
	  for (const itemOptionsToAdd of items) {
	    var _target$getSubMenu;
	    (_target$getSubMenu = target.getSubMenu()) == null ? void 0 : _target$getSubMenu.addMenuItem(itemOptionsToAdd);
	  }
	  (_target$getSubMenu2 = target.getSubMenu()) == null ? void 0 : _target$getSubMenu2.removeMenuItem(MENU_ITEM_STUB_ID);
	}
	function _adjustClientTitle2() {
	  const client = this.getCurrentCommunication();
	  if (!client) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showContent)[_showContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showAddClientTitle)[_showAddClientTitle]();
	    return;
	  }
	  const phone = this.getCurrentPhone();
	  if (!phone) {
	    /*
	    now the situation of the absence of the client�s phone
	    has not been worked out by the product manager in any way
	    	@todo need handle this situation
	     */
	    babelHelpers.classPrivateFieldLooseBase(this, _showContent)[_showContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showAddClientTitle)[_showAddClientTitle]();
	    return;
	  }
	  const clientElement = main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
			<span 
				id="crm-gotochat-client-selector--selected" 
				class="crm-entity-stream-content-gotochat-user-selector-link --selected" 
				onclick="${0}"
			>
				<span 
					class="crm-entity-stream-content-gotochat-client-avatar"
					style="background-image: url('${0}');"
				>
				</span>
				${0}, ${0}
				<span class="crm-entity-stream-content-gotochat-client-chevron"></span>
			</span>
		`), this.onShowClientPhoneSelector.bind(this), this.getEntityAvatarPath(client.entityTypeName.toLowerCase()), main_core.Text.encode(client.caption), main_core.Text.encode(phone.valueFormatted));
	  const titleContainer = main_core.Tag.render(_t4$1 || (_t4$1 = _$1`
			<span>
				${0}
			</span>
		`), main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SELECTED_CLIENT_TITLE'));
	  const titleElement = titleContainer.firstChild;
	  const labelIndex = titleElement.textContent.indexOf('#CLIENT_NAME#');
	  titleElement.nodeValue = titleElement.nodeValue.replace('#CLIENT_NAME#', '');
	  main_core.Dom.insertBefore(clientElement, titleElement.splitText(labelIndex));
	  const container = document.querySelector(`.${CLIENTS_SELECTOR_TITLE_CLASS}`);
	  main_core.Dom.clean(container);
	  main_core.Dom.append(titleContainer, container);
	}
	function _showContent2() {
	  main_core.Dom.removeClass(document.querySelector('.crm-entity-stream-content-new-detail-gotochat-container'), 'hidden');
	  main_core.Dom.removeClass(document.querySelector('.crm-entity-stream-content-new-detail-gotochat'), '--skeleton');
	}
	function _showAddClientTitle2() {
	  const container = document.querySelector(`.${CLIENTS_SELECTOR_TITLE_CLASS}`);
	  main_core.Dom.clean(container);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getClientTitleHtmlElement)[_getClientTitleHtmlElement](), container);
	}
	function _hideContent2() {
	  main_core.Dom.addClass(document.querySelector('.crm-entity-stream-content-new-detail-gotochat-container'), 'hidden');
	  main_core.Dom.addClass(document.querySelector('.crm-entity-stream-content-new-detail-gotochat'), '--skeleton');
	}
	function _removeCurrentClient2() {
	  this.selectedClient = null;
	  this.fromPhoneId = null;
	}
	function _adjustChatServiceButtons2() {
	  const oldContainer = document.querySelector(`.${BUTTONS_CONTAINER_CLASS}`);
	  const newContainer = babelHelpers.classPrivateFieldLooseBase(this, _getServiceButtons)[_getServiceButtons]();
	  main_core.Dom.replace(oldContainer, newContainer);
	}
	function _getServiceButtons2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fillChatServiceButtons)[_fillChatServiceButtons]();
	  return main_core.Tag.render(_t5 || (_t5 = _$1`
			<div class="${0}">
				${0}
			</div>
		`), BUTTONS_CONTAINER_CLASS, [...babelHelpers.classPrivateFieldLooseBase(this, _chatServiceButtons)[_chatServiceButtons].values()]);
	}
	function _fillChatServiceButtons2() {
	  ServicesConfig.forEach(service => {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isServiceSupportedInRegion)[_isServiceSupportedInRegion](service)) {
	      return;
	    }
	    const button = babelHelpers.classPrivateFieldLooseBase(this, _createChatServiceButton)[_createChatServiceButton](service);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatServiceButtons)[_chatServiceButtons].set(service.id, button);
	  });
	}
	function _isServiceSupportedInRegion2(service) {
	  if (!service.region || !babelHelpers.classPrivateFieldLooseBase(this, _region)[_region]) {
	    return true;
	  }
	  if (service.region !== babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] && service.region[0] !== '!') {
	    return false;
	  }
	  return service.region !== `!${babelHelpers.classPrivateFieldLooseBase(this, _region)[_region]}`;
	}
	function _createChatServiceButton2(service) {
	  let className = service.commonClass;
	  let label = service.connectLabel;
	  if (!service.available) {
	    className += ' --disabled';
	    label = service.soonLabel;
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isServiceSelected)[_isServiceSelected](service)) {
	    className += ' --ready';
	    label = service.inviteLabel;
	  }
	  return main_core.Tag.render(_t6 || (_t6 = _$1`
			<div 
				class="crm-entity-stream-content-gotochat-button"
				onclick="${0}"
			>
				<button 
					class="crm-entity-stream-content-new-detail-gotochat_button ${0}"
					data-code="${0}"
				>
					${0}
					<span class="crm-entity-stream-content-new-detail-gotochat_button-text">${0}</span>
				</button>
			</div>
		`), this.showRegistrarAndSend.bind(this, service.id), className, service.id, babelHelpers.classPrivateFieldLooseBase(this, _renderButtonIcon)[_renderButtonIcon](service), label);
	}
	function _renderButtonIcon2(service) {
	  if (!service) {
	    return '';
	  }
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: service.iconClass,
	    size: 40,
	    color: babelHelpers.classPrivateFieldLooseBase(this, _getButtonIconColor)[_getButtonIconColor](service)
	  });
	  return main_core.Tag.render(_t7 || (_t7 = _$1`
			<i class="crm-entity-stream-content-new-detail-gotochat_button-icon">
				${0}
			</i>
		`), icon.render());
	}
	function _getButtonIconColor2(service) {
	  if (!service.available) {
	    return getComputedStyle(document.body).getPropertyValue('--ui-color-base-40');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isServiceSelected)[_isServiceSelected](service)) {
	    return getComputedStyle(document.body).getPropertyValue('--ui-color-background-primary');
	  }
	  return service.iconColor;
	}
	function _isServiceSelected2(service) {
	  return this.openLineItems && this.openLineItems[service.id] && this.openLineItems[service.id].selected;
	}
	function _isAvailableService2(code) {
	  var _ServicesConfig$get;
	  return (_ServicesConfig$get = ServicesConfig.get(code)) == null ? void 0 : _ServicesConfig$get.available;
	}
	function _isEntityInEditorMode2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getEntityEditor)[_getEntityEditor]().getMode() === BX.UI.EntityEditorMode.edit;
	}
	async function _showEditorInEditModePopup2() {
	  const {
	    entityTypeId
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getOwnerEntity)[_getOwnerEntity]();
	  const entityType = BX.CrmEntityType.resolveName(entityTypeId);
	  const message = main_core.Loc.getMessage(`CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_TEXT_${entityType}`) || main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_TEXT');
	  return new Promise(resolve => {
	    BX.UI.Dialogs.MessageBox.show({
	      modal: true,
	      message,
	      buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_SAVE_AND_CONTINUE'),
	      onOk: messageBox => {
	        this.saveEntityEditor();
	        messageBox.close();
	        resolve();
	      },
	      cancelCaption: main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_FORCE_CONTINUE'),
	      onCancel: function (messageBox) {
	        messageBox.close();
	        resolve();
	      }
	    });
	  });
	}
	function _getEntityEditor2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _entityEditor)[_entityEditor]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityEditor)[_entityEditor] = BX.Crm.EntityEditor.getDefault();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _entityEditor)[_entityEditor];
	}
	function _showNotSelectedClientNotify2() {
	  const content = main_core.Loc.getMessage('CRM_TIMELINE_GOTOCHAT_NO_SELECTED_CLIENT');
	  babelHelpers.classPrivateFieldLooseBase(this, _showNotify)[_showNotify](content);
	}
	function _showNotify2(content) {
	  BX.UI.Notification.Center.notify({
	    content
	  });
	}
	function _setOpenLineItemIsSelected2(code) {
	  this.openLineItems[code].selected = true;
	}
	function _restoreButton2(code) {
	  const oldButton = babelHelpers.classPrivateFieldLooseBase(this, _chatServiceButtons)[_chatServiceButtons].get(code);
	  const newButton = babelHelpers.classPrivateFieldLooseBase(this, _createChatServiceButton)[_createChatServiceButton](ServicesConfig.get(code));
	  babelHelpers.classPrivateFieldLooseBase(this, _chatServiceButtons)[_chatServiceButtons].set(code, newButton);
	  main_core.Dom.replace(oldButton, newButton);
	}
	function _getChannelById2(id) {
	  return this.channels.find(channel => channel.id === id);
	}
	function _getLoader2() {
	  if (!this.loader) {
	    this.loader = new main_loader.Loader({
	      color: '#2fc6f6',
	      size: 36
	    });
	  }
	  return this.loader;
	}
	function _hideLoader2() {
	  if (this.loader) {
	    void this.loader.hide();
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

	class ButtonType {}
	ButtonType.PRIMARY = 'primary';
	ButtonType.SECONDARY = 'secondary';

	class ActionType {}
	ActionType.LAYOUT_JS_EVENT = 'layoutEvent';
	ActionType.FOOTER_BUTTON_CLICK = 'footerButtonClick';
	ActionType.OPEN_REST_APP = 'openRestApp';
	ActionType.REDIRECT = 'redirect';

	class EventType {}
	EventType.FOOTER_BUTTON_CLICK = 'footerButtonClick';
	EventType.LAYOUT_EVENT = 'layoutEvent';
	EventType.VALUE_CHANGED_EVENT = 'valueChangedEvent';

	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _value = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("value");
	var _sliderParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sliderParams");
	class Action {
	  constructor(params) {
	    var _params$value, _params$sliderParams;
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _value, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _sliderParams, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = params.type;
	    babelHelpers.classPrivateFieldLooseBase(this, _value)[_value] = (_params$value = params.value) != null ? _params$value : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams] = (_params$sliderParams = params.sliderParams) != null ? _params$sliderParams : null;
	  }
	  execute(vueComponent) {
	    return new Promise((resolve, reject) => {
	      if (this.isLayoutJsEvent()) {
	        var _vueComponent$$parent, _vueComponent$$parent2;
	        vueComponent.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
	          event: EventType.LAYOUT_EVENT,
	          value: {
	            id: (_vueComponent$$parent = vueComponent.$parent) != null && _vueComponent$$parent.getIdByComponentInstance ? (_vueComponent$$parent2 = vueComponent.$parent) == null ? void 0 : _vueComponent$$parent2.getIdByComponentInstance(vueComponent) : null,
	            value: babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]
	          }
	        });
	        resolve(true);
	      } else if (this.isOpenRestApp()) {
	        var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12;
	        const params = {
	          ...(main_core.Type.isPlainObject(babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]) ? babelHelpers.classPrivateFieldLooseBase(this, _value)[_value] : {
	            value: `${babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]}`
	          })
	        };
	        const appId = vueComponent.$root.getAppId();
	        if (main_core.Type.isStringFilled((_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr2.title) != null ? _babelHelpers$classPr : null)) {
	          params.bx24_title = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].title;
	        }
	        if (main_core.Type.isNumber((_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr4.width) != null ? _babelHelpers$classPr3 : null)) {
	          params.bx24_width = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].width;
	        }
	        if (main_core.Type.isNumber((_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr6.leftBoundary) != null ? _babelHelpers$classPr5 : null)) {
	          params.bx24_leftBoundary = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].leftBoundary;
	        }
	        const labelParams = {};
	        if (main_core.Type.isStringFilled((_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr8.labelBgColor) != null ? _babelHelpers$classPr7 : null)) {
	          labelParams.bgColor = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].labelBgColor;
	        }
	        if (main_core.Type.isStringFilled((_babelHelpers$classPr9 = (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr10.labelColor) != null ? _babelHelpers$classPr9 : null)) {
	          labelParams.color = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].labelColor;
	        }
	        if (main_core.Type.isStringFilled((_babelHelpers$classPr11 = (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams]) == null ? void 0 : _babelHelpers$classPr12.labelText) != null ? _babelHelpers$classPr11 : null)) {
	          labelParams.text = babelHelpers.classPrivateFieldLooseBase(this, _sliderParams)[_sliderParams].labelText;
	        }
	        if (Object.keys(labelParams).length > 0) {
	          params.bx24_label = labelParams;
	        }
	        if (BX.rest && BX.rest.AppLayout) {
	          BX.rest.AppLayout.openApplication(appId, params);
	        }
	      } else if (this.isRedirect()) {
	        const linkAttrs = {
	          href: babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]
	        };

	        // this magic allows auto opening internal links in slider if possible:
	        const link = main_core.Dom.create('a', {
	          attrs: linkAttrs,
	          text: '',
	          style: {
	            display: 'none'
	          }
	        });
	        main_core.Dom.append(link, document.body);
	        link.click();
	        setTimeout(() => main_core.Dom.remove(link), 10);
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]);
	      } else if (this.isFooterButtonClick()) {
	        vueComponent.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
	          event: EventType.FOOTER_BUTTON_CLICK,
	          value: babelHelpers.classPrivateFieldLooseBase(this, _value)[_value]
	        });
	        resolve(true);
	      } else {
	        reject(false);
	      }
	    });
	  }
	  isFooterButtonClick() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === ActionType.FOOTER_BUTTON_CLICK;
	  }
	  isLayoutJsEvent() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === ActionType.LAYOUT_JS_EVENT;
	  }
	  isOpenRestApp() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === ActionType.OPEN_REST_APP;
	  }
	  isRedirect() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === ActionType.REDIRECT;
	  }
	  getValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _value)[_value];
	  }
	}

	class ButtonState {}
	ButtonState.DEFAULT = '';
	ButtonState.LOADING = 'loading';
	ButtonState.DISABLED = 'disabled';

	var Button = {
	  props: {
	    id: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    state: {
	      type: String,
	      required: false,
	      default: ButtonState.DEFAULT
	    },
	    type: {
	      type: String,
	      required: false,
	      default: ButtonType.SECONDARY
	    },
	    action: Object
	  },
	  data() {
	    return {
	      uiButton: Object.freeze(null)
	    };
	  },
	  computed: {
	    buttonContainerRef() {
	      return this.$refs.buttonContainer;
	    },
	    itemStateToButtonStateDict() {
	      return {
	        [ButtonState.LOADING]: ui_buttons.Button.State.WAITING,
	        [ButtonState.DISABLED]: ui_buttons.Button.State.DISABLED
	      };
	    },
	    itemTypeToButtonColorDict() {
	      return {
	        [ButtonType.PRIMARY]: ui_buttons.Button.Color.PRIMARY,
	        [ButtonType.SECONDARY]: ui_buttons.Button.Color.LINK
	      };
	    },
	    className() {
	      var _this$itemTypeToButto, _this$itemStateToButt;
	      return [ui_buttons.Button.BASE_CLASS, (_this$itemTypeToButto = this.itemTypeToButtonColorDict[this.type]) != null ? _this$itemTypeToButto : ui_buttons.Button.Color.LINK, ui_buttons.Button.Size.EXTRA_SMALL, ui_buttons.Button.Style.ROUND, (_this$itemStateToButt = this.itemStateToButtonStateDict[this.state]) != null ? _this$itemStateToButt : ''];
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.action && ![ButtonState.LOADING, ButtonState.DISABLED].includes(this.state)) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }
	  },
	  template: `
		<button :class="className" @click="executeAction">{{ title }}</button>
	`
	};

	const ITEM_ACTION_EVENT = 'crm:activityplacement:item:action';
	const Layout = {
	  components: {
	    Button
	  },
	  props: {
	    id: String,
	    appId: String,
	    onAction: Function
	  },
	  data() {
	    var _this$layout, _this$layout2;
	    return {
	      layout: {},
	      loader: Object.freeze(null),
	      isLoading: true,
	      primaryButtonParams: this.getButtonParams(ButtonType.PRIMARY, null, (_this$layout = this.layout) == null ? void 0 : _this$layout.primaryButton),
	      secondaryButtonParams: this.getButtonParams(ButtonType.SECONDARY, null, (_this$layout2 = this.layout) == null ? void 0 : _this$layout2.secondaryButton),
	      primaryButtonAction: Object.freeze({
	        type: ActionType.FOOTER_BUTTON_CLICK,
	        value: ButtonType.PRIMARY
	      }),
	      secondaryButtonAction: Object.freeze({
	        type: ActionType.FOOTER_BUTTON_CLICK,
	        value: ButtonType.SECONDARY
	      })
	    };
	  },
	  created() {
	    this.$Bitrix.eventEmitter.subscribe(ITEM_ACTION_EVENT, this.onActionEvent);
	  },
	  mounted() {
	    this.showLoader(true);
	  },
	  beforeDestroy() {
	    this.$Bitrix.eventEmitter.unsubscribe(ITEM_ACTION_EVENT, this.onActionEvent);
	  },
	  watch: {
	    layout(newLayout) {
	      this.primaryButtonParams = this.getButtonParams(ButtonType.PRIMARY, this.primaryButtonParams, newLayout.primaryButton);
	      this.secondaryButtonParams = this.getButtonParams(ButtonType.SECONDARY, this.secondaryButtonParams, newLayout.secondaryButton);
	    }
	  },
	  methods: {
	    setLayout(newLayout) {
	      this.layout = newLayout;
	      this.$Bitrix.eventEmitter.emit('layout:updated');
	    },
	    showLoader(showLoader) {
	      if (showLoader) {
	        if (!this.loader) {
	          this.loader = new main_loader.Loader({
	            size: 50
	          });
	        }
	        this.loader.show(this.$refs.loader);
	      } else if (this.loader) {
	        this.loader.hide();
	      }
	      this.isLoading = showLoader;
	    },
	    setLayoutItemState(id, visible, properties, callback) {
	      if (this.$refs.blocks.setLayoutItemState(id, visible, properties)) {
	        this.$nextTick(callback({
	          result: 'success'
	        }));
	      } else {
	        this.$nextTick(callback({
	          result: 'error',
	          errors: ['item not found']
	        }));
	      }
	    },
	    setButtonState(id, state, callback) {
	      switch (id) {
	        case ButtonType.PRIMARY:
	          this.primaryButtonParams = this.getButtonParams(ButtonType.PRIMARY, this.primaryButtonParams, state);
	          break;
	        case ButtonType.SECONDARY:
	          this.secondaryButtonParams = this.getButtonParams(ButtonType.SECONDARY, this.secondaryButtonParams, state);
	          break;
	      }
	      this.$nextTick(callback({
	        result: 'success'
	      }));
	    },
	    getButtonParams(buttonType, oldValue, newValue) {
	      if (main_core.Type.isNull(newValue)) {
	        return null;
	      }
	      return {
	        ...oldValue,
	        ...newValue,
	        type: buttonType
	      };
	    },
	    getAppId() {
	      return this.appId;
	    },
	    onActionEvent(event) {
	      const eventData = event.getData();
	      this.onAction(main_core.Runtime.clone(eventData));
	    }
	  },
	  computed: {
	    hasPrimaryButton() {
	      return Boolean(this.primaryButtonParams);
	    },
	    hasSecondaryButton() {
	      return Boolean(this.secondaryButtonParams);
	    }
	  },
	  template: `
		<div class="crm-entity-stream-restapp-loader" ref="loader" v-show="isLoading"></div>
		<BlocksCollection  
			v-show="!isLoading" 
			containerCssClass="crm-entity-stream-restapp-container"
			itemCssClass="crm-timeline__restapp-container_block"
			ref="blocks"
			:blocks="layout?.blocks ?? {}"></BlocksCollection>

		<div class="crm-entity-stream-restapp-btn-container" v-show="!isLoading && (hasPrimaryButton || hasSecondaryButton)">
			<Button v-if="hasPrimaryButton" v-bind="primaryButtonParams" :action="primaryButtonAction"></Button>
			<Button v-if="hasSecondaryButton" v-bind="secondaryButtonParams" :action="secondaryButtonAction"></Button>
		</div>
	`
	};

	class BlockType {}
	BlockType.text = 'Text';
	BlockType.link = 'Link';
	BlockType.lineOfBlocks = 'LineOfTextBlocks';
	BlockType.withTitle = 'WithTitle';
	BlockType.section = 'Section';
	BlockType.list = 'List';
	BlockType.dropdownMenu = 'DropdownMenu';
	BlockType.input = 'Input';
	BlockType.select = 'Select';
	BlockType.textarea = 'Textarea';

	class TextColor {}
	TextColor.PRIMARY = 'primary';
	TextColor.WARNING = 'warning';
	TextColor.DANGER = 'danger';
	TextColor.SUCCESS = 'success';
	TextColor.BASE_50 = 'base-50';
	TextColor.BASE_60 = 'base-60';
	TextColor.BASE_70 = 'base-70';
	TextColor.BASE_90 = 'base-90';

	class TextSize {}
	TextSize.XS = 'xs';
	TextSize.SM = 'sm';
	TextSize.MD = 'md';
	TextSize.LG = 'lg';
	TextSize.XL = 'xl';

	var Text = {
	  inheritAttrs: false,
	  props: {
	    value: String | Number,
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    bold: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'md'
	    },
	    multiline: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__text-block', this.colorClassname, this.boldClassname, this.sizeClassname];
	    },
	    colorClassname() {
	      var _TextColor$upperCaseC;
	      const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
	      const color = (_TextColor$upperCaseC = TextColor[upperCaseColorProp]) != null ? _TextColor$upperCaseC : '';
	      return color ? `--color-${color}` : '';
	    },
	    boldClassname() {
	      const weight = this.bold ? 'bold' : 'normal';
	      return `--weight-${weight}`;
	    },
	    sizeClassname() {
	      var _TextSize$upperCaseWe;
	      const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
	      const size = (_TextSize$upperCaseWe = TextSize[upperCaseWeightProp]) != null ? _TextSize$upperCaseWe : TextSize.SM;
	      return `--size-${size}`;
	    },
	    encodedText() {
	      let text = main_core.Text.encode(this.value);
	      if (this.multiline) {
	        text = text.replace(/\n/g, '<br />');
	      }
	      return text;
	    }
	  },
	  template: `
		<span
			:title="title"
			:class="className"
			v-html="encodedText"
		></span>`
	};

	var Link = {
	  inheritAttrs: false,
	  props: {
	    text: String,
	    action: Object,
	    size: {
	      type: String,
	      required: false,
	      default: 'md'
	    },
	    bold: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    href() {
	      if (!this.action) {
	        return null;
	      }
	      const action = new Action(this.action);
	      if (action.isRedirect()) {
	        return action.getValue();
	      }
	      return null;
	    },
	    linkAttrs() {
	      if (!this.action) {
	        return {};
	      }
	      const action = new Action(this.action);
	      if (!action.isRedirect()) {
	        return {};
	      }
	      return {
	        href: action.getValue()
	      };
	    },
	    className() {
	      return ['crm-timeline__card_link', this.bold ? '--bold' : '', this.sizeClassname];
	    },
	    sizeClassname() {
	      var _TextSize$upperCaseWe;
	      const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
	      const size = (_TextSize$upperCaseWe = TextSize[upperCaseWeightProp]) != null ? _TextSize$upperCaseWe : TextSize.SM;
	      return `--size-${size}`;
	    }
	  },
	  methods: {
	    executeAction() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }
	  },
	  template: `
			<a
				v-if="href"
				v-bind="linkAttrs"
				:class="className"
			>
			{{text}}
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
			>
				{{text}}
			</span>
		`
	};

	class BlockWithTitleWidth {}
	BlockWithTitleWidth.SM = 'sm';
	BlockWithTitleWidth.MD = 'md';
	BlockWithTitleWidth.LG = 'lg';

	var BaseBlocksCollection = {
	  inheritAttrs: false,
	  props: {
	    blocks: Object
	  },
	  computed: {
	    allowedTypes() {
	      return Object.values(BlockType);
	    },
	    containerCssClass() {
	      return '';
	    },
	    containerTagName() {
	      return 'div';
	    },
	    itemCssClass() {
	      return '';
	    },
	    itemTagName() {
	      return 'div';
	    },
	    isInline() {
	      return false;
	    }
	  },
	  methods: {
	    setLayoutItemState(id, visible, properties) {
	      return this.$refs.blocks.setLayoutItemState(id, visible, properties);
	    }
	  },
	  // language=Vue
	  template: `
		<BlocksCollection 
			:containerCssClass="containerCssClass"
			:containerTagName="containerTagName"
			:itemCssClass="itemCssClass"
			:itemTagName="itemTagName"
			ref="blocks"
			:blocks="blocks ?? {}" 
			:inline="true"
			:allowedTypes="allowedTypes"
		></BlocksCollection>`
	};

	const LineOfTextBlocks = ui_vue3.BitrixVue.cloneComponent(BaseBlocksCollection, {
	  computed: {
	    allowedTypes() {
	      return [BlockType.text, BlockType.link, BlockType.dropdownMenu];
	    },
	    containerCssClass() {
	      return 'crm-timeline-block-line-of-texts';
	    },
	    containerTagName() {
	      return 'span';
	    },
	    itemTagName() {
	      return 'span';
	    },
	    isInline() {
	      return true;
	    }
	  }
	});

	var WithTitle = {
	  inheritAttrs: false,
	  components: {
	    Text,
	    Link,
	    LineOfTextBlocks
	  },
	  props: {
	    id: String,
	    title: String,
	    inline: Boolean,
	    titleWidth: {
	      type: String,
	      required: false,
	      default: BlockWithTitleWidth.MD
	    },
	    block: Object
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__card-container_info', '--word-wrap', this.widthClassname, this.inline ? '--inline' : ''];
	    },
	    widthClassname() {
	      var _BlockWithTitleWidth$;
	      const width = (_BlockWithTitleWidth$ = BlockWithTitleWidth[this.titleWidth.toUpperCase()]) != null ? _BlockWithTitleWidth$ : BlockWithTitleWidth.MD;
	      return `--width-${width}`;
	    },
	    isValidBlock() {
	      return [BlockType.text, BlockType.link, BlockType.lineOfBlocks].includes(this.rendererName);
	    },
	    rendererName() {
	      var _BlockType$this$block, _this$block;
	      return (_BlockType$this$block = BlockType[(_this$block = this.block) == null ? void 0 : _this$block.type]) != null ? _BlockType$this$block : null;
	    }
	  },
	  methods: {
	    isTitleCropped() {
	      const titleElem = this.$refs.title;
	      return titleElem.scrollWidth > titleElem.clientWidth;
	    }
	  },
	  mounted() {
	    this.$nextTick(() => {
	      if (this.isTitleCropped()) {
	        main_core.Dom.attr(this.$refs.title, 'title', this.title);
	      }
	    });
	  },
	  template: `
			<div :class="className" v-if="isValidBlock">
				<div ref="title" class="crm-timeline__card-container_info-title">{{ title }}</div>
				<div class="crm-timeline__card-container_info-value">
					<component :is="rendererName" v-bind="block.properties" :id="id"></component>
				</div>
			</div>
		`
	};

	const MenuId = 'restapp-dropdown-menu';
	var DropdownMenu = {
	  inheritAttrs: false,
	  props: {
	    values: Object,
	    id: String,
	    selectedValue: {
	      required: false,
	      default: ''
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'md'
	    }
	  },
	  data() {
	    return {
	      currentSelectedValue: this.selectedValue
	    };
	  },
	  beforeUnmount() {
	    const menu = main_popup.MenuManager.getMenuById(MenuId);
	    if (menu) {
	      menu.destroy();
	    }
	  },
	  computed: {
	    className() {
	      return ['crm-timeline-block-dropdownmenu', this.sizeClassname];
	    },
	    sizeClassname() {
	      var _TextSize$upperCaseWe;
	      const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
	      const size = (_TextSize$upperCaseWe = TextSize[upperCaseWeightProp]) != null ? _TextSize$upperCaseWe : TextSize.SM;
	      return `--size-${size}`;
	    },
	    selectedValueCode() {
	      let selectedValue = this.currentSelectedValue;
	      if (!Object.hasOwn(this.values, selectedValue)) {
	        const allValues = Object.keys(this.values);
	        selectedValue = allValues.length > 0 ? allValues[0] : '';
	      }
	      return selectedValue;
	    },
	    selectedValueTitle() {
	      var _this$values$this$sel;
	      return String((_this$values$this$sel = this.values[this.selectedValueCode]) != null ? _this$values$this$sel : '');
	    },
	    isValid() {
	      return main_core.Type.isPlainObject(this.values) && Object.keys(this.values).length > 0;
	    }
	  },
	  watch: {
	    selectedValue(newSelectedValue) {
	      this.currentSelectedValue = newSelectedValue;
	    }
	  },
	  methods: {
	    onMenuItemClick(valueId) {
	      var _MenuManager$getCurre;
	      this.currentSelectedValue = valueId;
	      (_MenuManager$getCurre = main_popup.MenuManager.getCurrentMenu()) == null ? void 0 : _MenuManager$getCurre.close();
	      this.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
	        event: EventType.VALUE_CHANGED_EVENT,
	        value: {
	          id: this.id,
	          value: valueId
	        }
	      });
	    },
	    showMenu() {
	      const menuItems = [];
	      Object.keys(this.values).forEach(valueId => {
	        menuItems.push({
	          text: String(this.values[valueId]),
	          value: valueId,
	          onclick: () => {
	            this.onMenuItemClick(valueId);
	          }
	        });
	      });
	      main_popup.MenuManager.show({
	        id: MenuId,
	        cacheable: false,
	        bindElement: this.$el,
	        items: menuItems
	      });
	    }
	  },
	  template: `
		<span v-if="isValid" :class="className" @click="showMenu"><span class="crm-timeline-block-dropdownmenu-content">{{selectedValueTitle}}</span><span class="crm-timeline-block-dropdownmenu-arrow"></span></span>`
	};

	var Input = {
	  emits: ['update:modelValue'],
	  props: {
	    modelValue: String,
	    placeholder: String,
	    disabled: Boolean
	  },
	  data() {
	    return {
	      currentValue: this.modelValue
	    };
	  },
	  methods: {
	    onChange() {
	      this.$emit('update:modelValue', this.currentValue);
	    }
	  },
	  watch: {
	    modelValue(newValue) {
	      this.currentValue = newValue;
	    }
	  },
	  template: `
		<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
			<input :placeholder="placeholder" :disabled="disabled" v-model="currentValue" @input="onChange" type="text" class="ui-ctl-element" />
		</div>
	`
	};

	var Select = {
	  emits: ['update:modelValue'],
	  props: {
	    modelValue: String,
	    values: Array,
	    disabled: Boolean
	  },
	  data() {
	    return {
	      currentValue: this.getSelectedValue(this.modelValue)
	    };
	  },
	  methods: {
	    onChange() {
	      this.$emit('update:modelValue', this.currentValue);
	    },
	    getSelectedValue(valueCandidate) {
	      if (!main_core.Type.isArray(this.values)) {
	        return '';
	      }
	      if (this.values.some(item => item.id === valueCandidate)) {
	        return valueCandidate;
	      }
	      return this.values.length > 0 ? this.values[0].id : '';
	    }
	  },
	  watch: {
	    modelValue(newValue) {
	      this.currentValue = this.getSelectedValue(newValue);
	    }
	  },
	  template: `
		<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
			<select :disabled="disabled" v-model="currentValue" @change="onChange" class="ui-ctl-element">
				<option :value="option.id" :key="option.id" :selected="option.id===currentValue" v-for="option in values">{{ option.value }}</option>
			</select>
		</div>
	`
	};

	var Textarea = {
	  emits: ['update:modelValue'],
	  props: {
	    modelValue: String,
	    placeholder: String,
	    disabled: Boolean
	  },
	  data() {
	    return {
	      currentValue: this.modelValue
	    };
	  },
	  mounted() {
	    this.adjustTextareaHeight();
	  },
	  methods: {
	    onChange() {
	      this.$emit('update:modelValue', this.currentValue);
	      this.adjustTextareaHeight();
	    },
	    adjustTextareaHeight() {
	      const textareaNode = this.$refs.textarea;
	      this.$nextTick(() => {
	        main_core.Dom.style(textareaNode, 'height', 0);
	        let height = textareaNode.scrollHeight;
	        if (height < 120) {
	          height = 120;
	        }
	        if (height > 1000) {
	          height = 1000;
	        }
	        height += 12;
	        height += 'px';
	        main_core.Dom.style(textareaNode, 'height', height);
	        main_core.Dom.style(textareaNode.parentNode, 'height', height);
	      });
	    }
	  },
	  watch: {
	    modelValue(newValue) {
	      this.currentValue = newValue;
	      this.$nextTick(() => {
	        this.adjustTextareaHeight(this.$refs.textarea);
	      });
	    }
	  },
	  template: `
		<div class="ui-ctl ui-ctl-textarea ui-ctl-w100 ui-ctl-no-resize">
			<textarea ref="textarea" :placeholder="placeholder" :disabled="disabled" v-model="currentValue" @input="onChange" class="ui-ctl-element"></textarea>
		</div>
	`
	};

	var BaseInput = {
	  inheritAttrs: false,
	  components: {
	    Input,
	    Select,
	    Textarea
	  },
	  props: {
	    id: String,
	    title: String,
	    errorText: String,
	    value: String,
	    disabled: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  data() {
	    return {
	      currentValue: this.getInitialValue()
	    };
	  },
	  computed: {
	    className() {
	      return ['ui-ctl-container', 'ui-ctl-w100', this.hasError ? 'ui-ctl-warning' : ''];
	    },
	    hasTitle() {
	      return Boolean(this.title);
	    },
	    hasError() {
	      return Boolean(this.errorText);
	    },
	    componentName() {
	      throw new Error('Must be overridden');
	    },
	    componentProps() {
	      throw new Error('Must be overridden');
	    }
	  },
	  watch: {
	    value(newValue) {
	      this.currentValue = newValue;
	    }
	  },
	  methods: {
	    getInitialValue() {
	      return this.value;
	    },
	    onChange(newValue) {
	      this.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
	        event: EventType.VALUE_CHANGED_EVENT,
	        value: {
	          id: this.id,
	          value: newValue
	        }
	      });
	    }
	  },
	  template: `
		<div :class="className">
			<div class="ui-ctl-top" v-if="hasTitle">
				<div class="ui-ctl-title">{{ title }}</div>
			</div>
			<component :is="componentName" v-bind="componentProps" :disabled="disabled" v-model="currentValue" @update:modelValue="onChange"></component>
			<div v-if="hasError" class="ui-ctl-bottom">{{ errorText }}</div>
		</div>
	`
	};

	const Input$1 = ui_vue3.BitrixVue.cloneComponent(BaseInput, {
	  props: {
	    placeholder: String
	  },
	  computed: {
	    componentName() {
	      return 'Input';
	    },
	    componentProps() {
	      return {
	        placeholder: this.placeholder
	      };
	    }
	  }
	});

	const Select$1 = ui_vue3.BitrixVue.cloneComponent(BaseInput, {
	  props: {
	    selectedValue: String,
	    values: Object
	  },
	  computed: {
	    componentName() {
	      return 'Select';
	    },
	    componentProps() {
	      return {
	        values: this.preparedValues
	      };
	    },
	    preparedValues() {
	      if (!main_core.Type.isPlainObject(this.values)) {
	        return [];
	      }
	      const result = [];
	      Object.keys(this.values).forEach(key => {
	        result.push({
	          id: key,
	          value: String(this.values[key])
	        });
	      });
	      return result;
	    }
	  },
	  watch: {
	    selectedValue(newValue) {
	      this.currentValue = newValue;
	    }
	  },
	  methods: {
	    getInitialValue() {
	      return `${this.selectedValue}`;
	    }
	  }
	});

	const Textarea$1 = ui_vue3.BitrixVue.cloneComponent(BaseInput, {
	  props: {
	    placeholder: String
	  },
	  computed: {
	    componentName() {
	      return 'Textarea';
	    },
	    componentProps() {
	      return {
	        placeholder: this.placeholder
	      };
	    }
	  }
	});

	const List = ui_vue3.BitrixVue.cloneComponent(BaseBlocksCollection, {
	  computed: {
	    allowedTypes() {
	      return [BlockType.text, BlockType.link, BlockType.lineOfBlocks];
	    },
	    containerCssClass() {
	      return 'crm-timeline-block-list';
	    },
	    itemCssClass() {
	      return 'crm-timeline-block-list-item';
	    }
	  }
	});

	class SectionImageSize {}
	SectionImageSize.SM = 'sm';
	SectionImageSize.MD = 'md';
	SectionImageSize.LG = 'lg';

	class SectionType {}
	SectionType.default = 'default';
	SectionType.primary = 'primary';
	SectionType.warning = 'warning';
	SectionType.danger = 'danger';
	SectionType.success = 'success';
	SectionType.withBorder = 'with-border';

	const Section = ui_vue3.BitrixVue.cloneComponent(BaseBlocksCollection, {
	  props: {
	    type: {
	      type: String,
	      required: false,
	      default: SectionType.default
	    },
	    imageSrc: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    imageSize: {
	      type: String,
	      required: false,
	      default: SectionImageSize.LG
	    }
	  },
	  computed: {
	    allowedTypes() {
	      return Object.values(BlockType).filter(item => item !== BlockType.section);
	    },
	    className() {
	      return ['crm-timeline-block-section', this.typeClassname];
	    },
	    imageClassName() {
	      return ['crm-timeline-block-section-img', this.imageSizeClassname];
	    },
	    typeClassname() {
	      var _SectionType$this$typ;
	      const type = (_SectionType$this$typ = SectionType[this.type]) != null ? _SectionType$this$typ : SectionType.default;
	      return type ? `--type-${type}` : '';
	    },
	    imageSizeClassname() {
	      var _SectionImageSize$thi;
	      const size = (_SectionImageSize$thi = SectionImageSize[this.imageSize.toUpperCase()]) != null ? _SectionImageSize$thi : SectionImageSize.LG;
	      return size ? `--size-${size}` : '';
	    },
	    imageUri() {
	      if (!this.imageSrc) {
	        return null;
	      }
	      const regex = /^(http|https):\/\//;
	      if (!regex.test(this.imageSrc)) {
	        return null;
	      }
	      return this.imageSrc;
	    }
	  },
	  // language=Vue
	  template: `
		<div :class="className">
			<div v-if="imageUri" :class="imageClassName">
				<img :src="imageUri" />
			</div>
		<BlocksCollection
			ref="blocks"
			containerCssClass="crm-timeline-block-section-blocks"
			itemCssClass="crm-timeline__restapp-container_block"
			:blocks="blocks ?? {}"
			:allowedTypes="allowedTypes"
		></BlocksCollection>
		</div>`
	});

	var BlocksCollection = {
	  components: {
	    Text,
	    Link,
	    LineOfTextBlocks,
	    DropdownMenu,
	    Input: Input$1,
	    Select: Select$1,
	    Textarea: Textarea$1,
	    List,
	    WithTitle,
	    Section
	  },
	  props: {
	    containerTagName: {
	      type: String,
	      required: false,
	      default: 'div'
	    },
	    containerCssClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    itemTagName: {
	      type: String,
	      required: false,
	      default: 'div'
	    },
	    itemCssClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    inline: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    allowedTypes: {
	      type: Array,
	      required: false,
	      default: Object.values(BlockType)
	    },
	    blocks: Object
	  },
	  data() {
	    return {
	      currentBlocks: this.blocks,
	      blockRefs: {}
	    };
	  },
	  beforeUpdate() {
	    this.blockRefs = {};
	  },
	  updated() {
	    this.setDataIdAttribute();
	  },
	  mounted() {
	    this.setDataIdAttribute();
	  },
	  watch: {
	    blocks(newBlocks) {
	      this.currentBlocks = newBlocks;
	    }
	  },
	  methods: {
	    saveRef(ref, id) {
	      this.blockRefs[id] = ref;
	    },
	    setDataIdAttribute() {
	      if (!this.blockRefs || this.visibleBlocks.length === 0) {
	        return;
	      }
	      this.visibleBlocks.forEach((block, index) => {
	        var _this$blockRefs$block;
	        const blockId = block.id;
	        const node = (_this$blockRefs$block = this.blockRefs[blockId]) == null ? void 0 : _this$blockRefs$block.$el;
	        if (main_core.Type.isElementNode(node)) {
	          node.setAttribute('data-id', blockId);
	        }
	      });
	    },
	    setLayoutItemState(id, visible, properties) {
	      if (!Object.hasOwn(this.currentBlocks, id)) {
	        return Object.keys(this.currentBlocks).reduce((result, blockId) => {
	          if (this.blockRefs[blockId] && main_core.Type.isFunction(this.blockRefs[blockId].setLayoutItemState)) {
	            return this.blockRefs[blockId].setLayoutItemState(id, visible, properties) || result;
	          }
	          return result;
	        }, false);
	      }
	      if (main_core.Type.isPlainObject(properties)) {
	        this.currentBlocks[id].properties = {
	          ...this.currentBlocks[id].properties,
	          ...properties
	        };
	      }
	      if (main_core.Type.isBoolean(visible)) {
	        this.currentBlocks[id].visible = visible;
	      }
	      return true;
	    },
	    getIdByComponentInstance(componentInstance) {
	      const id = Object.keys(this.blockRefs).find(blockId => this.blockRefs[blockId] === componentInstance);
	      return id || null;
	    },
	    getItemCssClassList(block) {
	      const list = [];
	      if (this.itemCssClass) {
	        list.push(this.itemCssClass);
	      }
	      if (!block.visible) {
	        list.push('--hidden');
	      }
	      if (block.id === this.firstVisibleBlockId) {
	        list.push('--first-visible');
	      }
	      if (block.id === this.lastVisibleBlockId) {
	        list.push('--last-visible');
	      }
	      return list;
	    }
	  },
	  computed: {
	    visibleBlocks() {
	      if (!this.currentBlocks) {
	        return [];
	      }
	      return Object.keys(this.currentBlocks).map(id => {
	        var _BlockType$block$type;
	        const block = this.currentBlocks[id];
	        const rendererName = (_BlockType$block$type = BlockType[block.type]) != null ? _BlockType$block$type : null;
	        const visible = !main_core.Type.isBoolean(block.visible) || block.visible;
	        return {
	          id,
	          rendererName,
	          ...this.currentBlocks[id],
	          visible
	        };
	      }).filter(item => this.allowedTypes.includes(item.rendererName));
	    },
	    firstVisibleBlockId() {
	      const visibleBlocks = this.visibleBlocks.filter(item => item.visible);
	      if (!visibleBlocks.length) {
	        return null;
	      }
	      return visibleBlocks[0].id;
	    },
	    lastVisibleBlockId() {
	      const visibleBlocks = this.visibleBlocks.filter(item => item.visible);
	      if (!visibleBlocks.length) {
	        return null;
	      }
	      return visibleBlocks[visibleBlocks.length - 1].id;
	    }
	  },
	  // language=Vue
	  template: `
		<component :is="containerTagName" :class="containerCssClass">
			<component :is="itemTagName"
				:class="getItemCssClassList(block)"
				v-for="(block) in visibleBlocks"
				:key="block.id"
			>
				<component :is="block.rendererName"
						   :id="block.id"
						   v-bind="block.properties"
						   :ref="(el) => this.saveRef(el, block.id)"
				/>
				<span v-if="inline">&nbsp;</span>
			</component>
		</component>`
	};

	const SPOTLIGHT_ID_PREFIX = 'rest_placement_spotlight';
	const MODULE_ID = 'crm';
	const USER_SEEN_OPTION = 'rest_placement_tour_viewed';
	const REST_PLACEMENT_SLIDER_WIDTH = 800;
	const CHECK_TARGET_CHANGE_INTERVAL = 1000;
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _text = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("text");
	var _isCanShowTour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCanShowTour");
	var _appContext = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appContext");
	var _isHidden = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isHidden");
	var _currentTarget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentTarget");
	var _guide$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("guide");
	var _spotlight$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("spotlight");
	var _checkTargetChangeIntervalID = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkTargetChangeIntervalID");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _unbindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unbindEvents");
	var _onTargetChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onTargetChange");
	var _isVisible$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isVisible");
	var _hide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hide");
	var _unHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unHide");
	var _rebindTarget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rebindTarget");
	var _getSpotlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSpotlight");
	var _getTarget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTarget");
	var _prepareMoreDetailsLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareMoreDetailsLink");
	var _openAppPlacementSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openAppPlacementSlider");
	class Tour$1 {
	  constructor(data) {
	    Object.defineProperty(this, _openAppPlacementSlider, {
	      value: _openAppPlacementSlider2
	    });
	    Object.defineProperty(this, _prepareMoreDetailsLink, {
	      value: _prepareMoreDetailsLink2
	    });
	    Object.defineProperty(this, _getTarget, {
	      value: _getTarget2
	    });
	    Object.defineProperty(this, _getSpotlight, {
	      value: _getSpotlight2
	    });
	    Object.defineProperty(this, _rebindTarget, {
	      value: _rebindTarget2
	    });
	    Object.defineProperty(this, _unHide, {
	      value: _unHide2
	    });
	    Object.defineProperty(this, _hide, {
	      value: _hide2
	    });
	    Object.defineProperty(this, _isVisible$1, {
	      value: _isVisible2
	    });
	    Object.defineProperty(this, _onTargetChange, {
	      value: _onTargetChange2
	    });
	    Object.defineProperty(this, _unbindEvents, {
	      value: _unbindEvents2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _text, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isCanShowTour, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _appContext, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isHidden, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _currentTarget, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _guide$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _spotlight$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _checkTargetChangeIntervalID, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = main_core.Type.isStringFilled(data.id) ? data.id : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = main_core.Type.isStringFilled(data.title) ? data.title : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _text)[_text] = main_core.Type.isStringFilled(data.text) ? data.text : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _isCanShowTour)[_isCanShowTour] = main_core.Type.isBoolean(data.isCanShowTour) ? data.isCanShowTour : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _appContext)[_appContext] = main_core.Type.isPlainObject(data.appContext) ? data.appContext : {};
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getSpotlight)[_getSpotlight]().show();
	    this.getGuide().showNextStep();
	    babelHelpers.classPrivateFieldLooseBase(this, _prepareMoreDetailsLink)[_prepareMoreDetailsLink]();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	  canShow() {
	    let isValidStringFields = true;
	    const stringFields = [babelHelpers.classPrivateFieldLooseBase(this, _id)[_id], babelHelpers.classPrivateFieldLooseBase(this, _title)[_title], babelHelpers.classPrivateFieldLooseBase(this, _text)[_text]];
	    stringFields.forEach(field => {
	      if (!main_core.Type.isStringFilled(field)) {
	        isValidStringFields = false;
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _isCanShowTour)[_isCanShowTour] && isValidStringFields && main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget]());
	  }
	  getGuide() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _guide$1)[_guide$1]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _guide$1)[_guide$1] = new ui_tour.Guide({
	        onEvents: true,
	        steps: [{
	          target: babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](),
	          title: main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title]),
	          text: main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _text)[_text]),
	          position: 'bottom',
	          rounded: true,
	          link: '##',
	          events: {
	            onClose: () => {
	              BX.userOptions.save(MODULE_ID, USER_SEEN_OPTION, babelHelpers.classPrivateFieldLooseBase(this, _id)[_id], true);
	              babelHelpers.classPrivateFieldLooseBase(this, _getSpotlight)[_getSpotlight]().close();
	              babelHelpers.classPrivateFieldLooseBase(this, _unbindEvents)[_unbindEvents]();
	            }
	          }
	        }]
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _guide$1)[_guide$1];
	  }
	}
	function _bindEvents2() {
	  this.currentTarget = babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget]();
	  babelHelpers.classPrivateFieldLooseBase(this, _checkTargetChangeIntervalID)[_checkTargetChangeIntervalID] = setInterval(babelHelpers.classPrivateFieldLooseBase(this, _onTargetChange)[_onTargetChange].bind(this), CHECK_TARGET_CHANGE_INTERVAL);
	}
	function _unbindEvents2() {
	  clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _checkTargetChangeIntervalID)[_checkTargetChangeIntervalID]);
	}
	function _onTargetChange2() {
	  const possibleNewTarget = babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget]();
	  const isTargetVisible = babelHelpers.classPrivateFieldLooseBase(this, _isVisible$1)[_isVisible$1](possibleNewTarget);
	  const isTargetChange = babelHelpers.classPrivateFieldLooseBase(this, _currentTarget)[_currentTarget] !== possibleNewTarget;
	  if (isTargetVisible) {
	    babelHelpers.classPrivateFieldLooseBase(this, _unHide)[_unHide]();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	  }
	  if (isTargetChange) {
	    babelHelpers.classPrivateFieldLooseBase(this, _rebindTarget)[_rebindTarget](possibleNewTarget);
	    babelHelpers.classPrivateFieldLooseBase(this, _currentTarget)[_currentTarget] = possibleNewTarget;
	  }
	}
	function _isVisible2(element) {
	  return Boolean(element.offsetWidth || element.offsetHeight || element.getClientRects().length > 0);
	}
	function _hide2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isHidden)[_isHidden]) {
	    return;
	  }
	  const guidePopupContainer = this.getGuide().getPopup().getPopupContainer();
	  main_core.Dom.addClass(guidePopupContainer, '--hidden');
	  babelHelpers.classPrivateFieldLooseBase(this, _isHidden)[_isHidden] = true;
	}
	function _unHide2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isHidden)[_isHidden]) {
	    return;
	  }
	  const guidePopup = this.getGuide().getPopup();
	  main_core.Dom.removeClass(guidePopup.popupContainer, '--hidden');
	  guidePopup.adjustPosition();
	  babelHelpers.classPrivateFieldLooseBase(this, _isHidden)[_isHidden] = false;
	}
	function _rebindTarget2(newTarget) {
	  this.getGuide().getCurrentStep().setTarget(newTarget);
	  this.getGuide().showNextStep();
	  babelHelpers.classPrivateFieldLooseBase(this, _prepareMoreDetailsLink)[_prepareMoreDetailsLink]();
	  babelHelpers.classPrivateFieldLooseBase(this, _getSpotlight)[_getSpotlight]().setTargetElement(newTarget);
	}
	function _getSpotlight2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _spotlight$1)[_spotlight$1]) {
	    const id = `${SPOTLIGHT_ID_PREFIX}_${babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]}`;
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight$1)[_spotlight$1] = new BX.SpotLight({
	      id,
	      targetElement: babelHelpers.classPrivateFieldLooseBase(this, _getTarget)[_getTarget](),
	      autoSave: 'no',
	      targetVertex: 'middle-center',
	      zIndex: 200
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _spotlight$1)[_spotlight$1];
	}
	function _getTarget2() {
	  var _target;
	  let target = document.querySelector(`[data-id="${babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]}"]`);
	  if ((_target = target) != null && _target.offsetTop) {
	    target = target.parentElement.nextElementSibling;
	  }
	  return target;
	}
	function _prepareMoreDetailsLink2() {
	  const moreDetailsLink = this.getGuide().getLink();
	  moreDetailsLink.removeAttribute('href');
	  moreDetailsLink.removeAttribute('target');
	  main_core.Dom.style(moreDetailsLink, 'cursor', 'pointer');
	  moreDetailsLink.onclick = babelHelpers.classPrivateFieldLooseBase(this, _openAppPlacementSlider)[_openAppPlacementSlider].bind(this);
	}
	function _openAppPlacementSlider2() {
	  const {
	    applicationId,
	    placementOptions,
	    additionalComponentParam,
	    closeCallback
	  } = babelHelpers.classPrivateFieldLooseBase(this, _appContext)[_appContext];
	  placementOptions.newUserNotification = 'Y';
	  placementOptions.bx24_width = REST_PLACEMENT_SLIDER_WIDTH;
	  BX.rest.AppLayout.openApplication(applicationId, placementOptions, additionalComponentParam, closeCallback);
	}

	class Base extends Item {
	  showTour() {
	    const tour = new Tour$1({
	      id: this.getSetting('id'),
	      title: this.getSetting('newUserNotificationTitle'),
	      text: this.getSetting('newUserNotificationText'),
	      isCanShowTour: this.getSetting('isCanShowTour') && !BX.Crm.EntityEditor.getDefault().isNew(),
	      appContext: {
	        applicationId: this.getSetting('appId', ''),
	        placementOptions: {
	          entityTypeId: this.getEntityTypeId(),
	          entityId: this.getEntityId()
	        }
	      }
	    });
	    crm_tourManager.TourManager.getInstance().registerWithLaunch(tour);
	  }
	}

	class LayoutValidator {
	  validate(layout) {
	    return [];
	  }
	}

	var _placementCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("placementCode");
	var _methodsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("methodsList");
	var _handlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlers");
	var _initializeInterface = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initializeInterface");
	var _interfaceCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interfaceCallback");
	class PlacementInterfaceManager {
	  constructor(placementCode, methodsList) {
	    Object.defineProperty(this, _interfaceCallback, {
	      value: _interfaceCallback2
	    });
	    Object.defineProperty(this, _initializeInterface, {
	      value: _initializeInterface2
	    });
	    Object.defineProperty(this, _placementCode, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _methodsList, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _handlers, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _placementCode)[_placementCode] = placementCode;
	    babelHelpers.classPrivateFieldLooseBase(this, _methodsList)[_methodsList] = methodsList;
	    babelHelpers.classPrivateFieldLooseBase(this, _initializeInterface)[_initializeInterface]();
	  }
	  static getInstance(placementCode, methodsList) {
	    if (!Object.hasOwn(PlacementInterfaceManager.Instances, placementCode)) {
	      PlacementInterfaceManager.Instances[placementCode] = new PlacementInterfaceManager(placementCode, methodsList);
	    }
	    return PlacementInterfaceManager.Instances[placementCode];
	  }
	  registerHandlers(placementId, handlers) {
	    babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers][placementId] = handlers;
	  }
	}
	function _initializeInterface2() {
	  const PlacementInterface = BX.rest.AppLayout.initializePlacement(babelHelpers.classPrivateFieldLooseBase(this, _placementCode)[_placementCode]);
	  babelHelpers.classPrivateFieldLooseBase(this, _methodsList)[_methodsList].forEach(methodName => {
	    PlacementInterface.prototype[methodName] = babelHelpers.classPrivateFieldLooseBase(this, _interfaceCallback)[_interfaceCallback].bind(this, methodName);
	  });
	}
	function _interfaceCallback2() {
	  var _arguments$, _arguments$3$params$p, _arguments$2, _arguments$2$params, _babelHelpers$classPr;
	  const methodName = (_arguments$ = arguments[0]) != null ? _arguments$ : null;
	  const placementId = (_arguments$3$params$p = (_arguments$2 = arguments[3]) == null ? void 0 : (_arguments$2$params = _arguments$2.params) == null ? void 0 : _arguments$2$params.placementId) != null ? _arguments$3$params$p : null;
	  if (!methodName || !placementId) {
	    return;
	  }
	  const placementHandlers = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers][placementId]) != null ? _babelHelpers$classPr : {};
	  if (main_core.Type.isFunction(placementHandlers[methodName])) {
	    var _arguments$3, _arguments$4;
	    placementHandlers[methodName]((_arguments$3 = arguments[1]) != null ? _arguments$3 : null, (_arguments$4 = arguments[2]) != null ? _arguments$4 : null);
	  }
	}
	PlacementInterfaceManager.Instances = {};

	let _$2 = t => t,
	  _t$2,
	  _t2$2;
	const LAYOUT_EVENT_NAME = 'LayoutEvent';
	const PRIMARY_BTN_CLICK_EVENT_NAME = 'PrimaryButtonClickEvent';
	const SECONDARY_BTN_CLICK_EVENT_NAME = 'SecondaryButtonClickEvent';
	const VALUE_CHANGE_EVENT_NAME = 'ValueChangeEvent';
	const ENTITY_UPDATE_EVENT_NAME = 'entityUpdateEvent';
	var _layoutComponent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutComponent");
	var _layoutApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutApp");
	var _activated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activated");
	var _eventEmitter$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("eventEmitter");
	var _initializeInterface$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initializeInterface");
	var _setLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLayout");
	var _setLayoutItemState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLayoutItemState");
	var _setButtonState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setButtonState");
	var _bindEventCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEventCallback");
	var _finish = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("finish");
	var _executeEventCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeEventCallback");
	var _onLayoutAppAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLayoutAppAction");
	var _executeCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeCallback");
	var _loadApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadApp");
	class WithLayout extends Base {
	  constructor() {
	    super();
	    Object.defineProperty(this, _loadApp, {
	      value: _loadApp2
	    });
	    Object.defineProperty(this, _executeCallback, {
	      value: _executeCallback2
	    });
	    Object.defineProperty(this, _onLayoutAppAction, {
	      value: _onLayoutAppAction2
	    });
	    Object.defineProperty(this, _executeEventCallback, {
	      value: _executeEventCallback2
	    });
	    Object.defineProperty(this, _finish, {
	      value: _finish2
	    });
	    Object.defineProperty(this, _bindEventCallback, {
	      value: _bindEventCallback2
	    });
	    Object.defineProperty(this, _setButtonState, {
	      value: _setButtonState2
	    });
	    Object.defineProperty(this, _setLayoutItemState, {
	      value: _setLayoutItemState2
	    });
	    Object.defineProperty(this, _setLayout, {
	      value: _setLayout2
	    });
	    Object.defineProperty(this, _initializeInterface$1, {
	      value: _initializeInterface2$1
	    });
	    Object.defineProperty(this, _layoutComponent, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _layoutApp, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _activated, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _eventEmitter$1, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1] = new main_core_events.EventEmitter();
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].setEventNamespace('RestPlacement');
	    main_core_events.EventEmitter.subscribe('onCrmEntityUpdate', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].emit(ENTITY_UPDATE_EVENT_NAME, {});
	    });
	  }
	  createLayout() {
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`<div class="crm-entity-stream-content-new-detail --hidden"></div>`));
	  }
	  initializeLayout() {
	    super.initializeLayout();
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutApp)[_layoutApp] = ui_vue3.BitrixVue.createApp(Layout, {
	      id: String(this.getSetting('placementId', '')),
	      appId: this.getSetting('appId', ''),
	      onAction: babelHelpers.classPrivateFieldLooseBase(this, _onLayoutAppAction)[_onLayoutAppAction].bind(this)
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutApp)[_layoutApp].component('BlocksCollection', BlocksCollection);
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent] = babelHelpers.classPrivateFieldLooseBase(this, _layoutApp)[_layoutApp].mount(this.getContainer());
	  }
	  activate() {
	    super.activate();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _activated)[_activated]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _activated)[_activated] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _initializeInterface$1)[_initializeInterface$1]();
	      babelHelpers.classPrivateFieldLooseBase(this, _loadApp)[_loadApp]();
	    }
	  }
	}
	function _initializeInterface2$1() {
	  const placementInterfaceManager = PlacementInterfaceManager.getInstance(this.getSetting('placement', ''), ['setLayout', 'setLayoutItemState', 'bindLayoutEventCallback', 'bindValueChangeCallback', 'setPrimaryButtonState', 'setSecondaryButtonState', 'bindPrimaryButtonClickCallback', 'bindSecondaryButtonClickCallback', 'bindEntityUpdateCallback', 'finish', 'lock', 'unlock']);
	  placementInterfaceManager.registerHandlers(this.getSetting('placementId', ''), {
	    setLayout: babelHelpers.classPrivateFieldLooseBase(this, _setLayout)[_setLayout].bind(this),
	    setLayoutItemState: babelHelpers.classPrivateFieldLooseBase(this, _setLayoutItemState)[_setLayoutItemState].bind(this),
	    bindLayoutEventCallback: babelHelpers.classPrivateFieldLooseBase(this, _bindEventCallback)[_bindEventCallback].bind(this, LAYOUT_EVENT_NAME),
	    bindValueChangeCallback: babelHelpers.classPrivateFieldLooseBase(this, _bindEventCallback)[_bindEventCallback].bind(this, VALUE_CHANGE_EVENT_NAME),
	    setPrimaryButtonState: babelHelpers.classPrivateFieldLooseBase(this, _setButtonState)[_setButtonState].bind(this, ButtonType.PRIMARY),
	    setSecondaryButtonState: babelHelpers.classPrivateFieldLooseBase(this, _setButtonState)[_setButtonState].bind(this, ButtonType.SECONDARY),
	    bindPrimaryButtonClickCallback: babelHelpers.classPrivateFieldLooseBase(this, _bindEventCallback)[_bindEventCallback].bind(this, PRIMARY_BTN_CLICK_EVENT_NAME),
	    bindSecondaryButtonClickCallback: babelHelpers.classPrivateFieldLooseBase(this, _bindEventCallback)[_bindEventCallback].bind(this, SECONDARY_BTN_CLICK_EVENT_NAME),
	    bindEntityUpdateCallback: babelHelpers.classPrivateFieldLooseBase(this, _bindEventCallback)[_bindEventCallback].bind(this, ENTITY_UPDATE_EVENT_NAME),
	    finish: babelHelpers.classPrivateFieldLooseBase(this, _finish)[_finish].bind(this),
	    lock: this.setLocked.bind(this, true),
	    unlock: this.setLocked.bind(this, false)
	  });
	}
	function _setLayout2(layout, callback) {
	  const validator = new LayoutValidator();
	  const errors = validator.validate(layout);
	  if (errors.length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, {
	      result: 'error',
	      errors
	    });
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent].showLoader(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent].setLayout(layout);
	    babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, {
	      result: 'success'
	    });
	  }
	}
	function _setLayoutItemState2(params, callback) {
	  var _params$id, _params$properties, _params$visible;
	  const id = (_params$id = params.id) != null ? _params$id : null;
	  let properties = (_params$properties = params.properties) != null ? _params$properties : null;
	  let visible = (_params$visible = params.visible) != null ? _params$visible : null;
	  if (!main_core.Type.isStringFilled(id)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, {
	      result: 'error',
	      errors: ['Wrong id']
	    });
	    return;
	  }
	  const isCorrectVisible = main_core.Type.isBoolean(visible);
	  const isCorrectProps = main_core.Type.isPlainObject(properties);
	  if (!isCorrectProps && !isCorrectVisible) {
	    babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, {
	      result: 'error',
	      errors: ['Wrong state']
	    });
	    return;
	  }
	  if (!isCorrectVisible) {
	    visible = null;
	  }
	  if (!isCorrectProps) {
	    properties = null;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent].setLayoutItemState(id, visible, properties, result => babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, result));
	}
	function _setButtonState2(buttonId, params, callback) {
	  if (!main_core.Type.isPlainObject(params) && !(main_core.Type.isArray(params) && params.length === 0) && !main_core.Type.isNull(params)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, {
	      result: 'error',
	      errors: ['Wrong params']
	    });
	    return;
	  }
	  let state = params;
	  if (main_core.Type.isArray(params) && params.length === 0) {
	    state = null;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent].setButtonState(buttonId, state, result => babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, result));
	}
	function _bindEventCallback2(eventName, params, callback) {
	  babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].subscribe(eventName, babelHelpers.classPrivateFieldLooseBase(this, _executeEventCallback)[_executeEventCallback].bind(this, params, callback));
	}
	function _finish2() {
	  this.emitFinishEditEvent();
	}
	function _executeEventCallback2(params, callback, eventData) {
	  const data = eventData.getData();
	  if (main_core.Type.isStringFilled(params))
	    // if need to call callback only for definite id
	    {
	      var _data$id;
	      if (((_data$id = data.id) != null ? _data$id : '') === params) {
	        babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, data);
	      }
	      return;
	    }
	  babelHelpers.classPrivateFieldLooseBase(this, _executeCallback)[_executeCallback](callback, data);
	}
	function _onLayoutAppAction2(eventData) {
	  var _eventData$event, _eventData$value;
	  const event = (_eventData$event = eventData.event) != null ? _eventData$event : null;
	  const value = (_eventData$value = eventData.value) != null ? _eventData$value : null;
	  if (event === EventType.FOOTER_BUTTON_CLICK && value === ButtonType.PRIMARY) {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].emit(PRIMARY_BTN_CLICK_EVENT_NAME, {});
	  }
	  if (event === EventType.FOOTER_BUTTON_CLICK && value === ButtonType.SECONDARY) {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].emit(SECONDARY_BTN_CLICK_EVENT_NAME, {});
	  }
	  if (event === EventType.LAYOUT_EVENT) {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].emit(LAYOUT_EVENT_NAME, value);
	  }
	  if (event === EventType.VALUE_CHANGED_EVENT) {
	    babelHelpers.classPrivateFieldLooseBase(this, _eventEmitter$1)[_eventEmitter$1].emit(VALUE_CHANGE_EVENT_NAME, value);
	  }
	}
	function _executeCallback2(callback, data) {
	  if (main_core.Type.isFunction(callback)) {
	    callback(data);
	  }
	}
	function _loadApp2() {
	  main_core.ajax.runComponentAction('bitrix:app.layout', 'getComponent', {
	    data: {
	      placementId: this.getSetting('placementId', ''),
	      placementOptions: {
	        entityTypeId: this.getEntityTypeId(),
	        entityId: this.getEntityId(),
	        useBuiltInInterface: 'Y'
	      }
	    }
	  }).then(response => {
	    if (!(response && response.data && response.data.componentResult)) {
	      return;
	    }
	    const componentResult = response.data.componentResult;
	    this.appSid = componentResult.APP_SID;
	    const iframeNode = main_core.Tag.render(_t2$2 || (_t2$2 = _$2`<div style="display: none; overflow: hidden;"></div>`));
	    main_core.Dom.append(iframeNode, document.body);
	    main_core.Runtime.html(iframeNode, response.data.html);
	  });
	}

	var _interfaceInitialized = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interfaceInitialized");
	var _initializeInterface$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initializeInterface");
	class WithSlider extends Base {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _initializeInterface$2, {
	      value: _initializeInterface2$2
	    });
	    Object.defineProperty(this, _interfaceInitialized, {
	      writable: true,
	      value: false
	    });
	  }
	  showSlider() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _interfaceInitialized)[_interfaceInitialized]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _interfaceInitialized)[_interfaceInitialized] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _initializeInterface$2)[_initializeInterface$2]();
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
	function _initializeInterface2$2() {
	  var _top$BX$rest;
	  if ((_top$BX$rest = top.BX.rest) != null && _top$BX$rest.AppLayout) {
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

	let _$3 = t => t,
	  _t$3,
	  _t2$3,
	  _t3$2,
	  _t4$2,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12;

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _getTour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTour");
	class Sharing extends WithEditor {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getTour, {
	      value: _getTour2
	    });
	    this.HELPDESK_CODE = 17502612;
	  }
	  /**
	   * @override
	   */
	  initialize(context, settings) {
	    const config = settings.config;
	    this.link = config.link;
	    this.calendarSettings = config.calendarSettings;
	    this.isResponsible = config.isResponsible;
	    this.setContacts(config.contacts);
	    this.isNotificationsAvailable = config.isNotificationsAvailable;
	    this.areCommunicationChannelsAvailable = config.areCommunicationChannelsAvailable;
	    this.setCommunicationChannels(config.communicationChannels, config.selectedChannelId);
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
	    main_core_events.EventEmitter.subscribe('CalendarSharing:RuleUpdated', () => this.onRuleUpdated());
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
			<div class="crm-entity-stream-content-sharing crm-entity-stream-content-wait-detail --hidden">
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
	    this.DOM.settingsButton = main_core.Tag.render(_t3$2 || (_t3$2 = _$3`
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
			<button class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round">
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
	  async onSendButtonClick() {
	    if (!this.isContactAvailable()) {
	      this.showWarningNoContact();
	      return;
	    }
	    if (!this.areCommunicationChannelsAvailable && this.isNotificationsAvailable && this.isChannelsAvailable()) {
	      const bitrix24Channel = this.channels.find(channel => {
	        return this.isChannelBitrix24(channel);
	      });
	      if (bitrix24Channel) {
	        const isApproved = await this.isBitrix24Approved();
	        if (isApproved) {
	          this.channel = bitrix24Channel;
	          this.updateSenderList();
	          this.onSaveButtonClick();
	          return;
	        }
	      }
	    }
	    if (!this.areCommunicationChannelsAvailable) {
	      this.showWarningNoCommunicationChannels();
	      return;
	    }
	    if (this.isNotificationsAvailable && this.isChannelBitrix24(this.channel)) {
	      const isApproved = await this.isBitrix24Approved();
	      if (isApproved) {
	        this.onSaveButtonClick();
	        return;
	      } else {
	        this.showWarningNoCommunicationChannels();
	        return;
	      }
	    }
	    this.onSaveButtonClick();
	  }
	  onLinkCopied() {
	    this.saveLinkAction({
	      isActionCopy: true
	    });
	  }
	  onRuleUpdated() {
	    this.onRuleUpdatedAction();
	  }
	  onMoreInfoButtonClick() {
	    this.openHelpDesk();
	  }
	  showConfigureSlotsPopup() {
	    if (!this.newDialog) {
	      this.newDialog = new calendar_sharing_interface.DialogNew({
	        bindElement: this.DOM.configureSlotsButton,
	        sharingUrl: this.link.url,
	        sharingRule: this.link.rule,
	        linkHash: this.link.hash,
	        context: "crm",
	        readOnly: !this.isResponsible,
	        calendarSettings: this.calendarSettings
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
				<div>${0}</div>
			</div>
		`), main_core.Text.encode(`${contact.name} (${contact.phone})`));
	    contact.check = main_core.Tag.render(_t8 || (_t8 = _$3`
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
	    const itemHtml = main_core.Tag.render(_t9 || (_t9 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${0}</div>
			</div>
		`), main_core.Text.encode(channel.name));
	    channel.check = main_core.Tag.render(_t10 || (_t10 = _$3`
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
	    const itemHtml = main_core.Tag.render(_t11 || (_t11 = _$3`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${0}</div>
			</div>
		`), main_core.Text.encode(from.name));
	    from.check = main_core.Tag.render(_t12 || (_t12 = _$3`
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
				<div>${main_core.Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE_1').replaceAll('/marketplace/', main_core.Loc.getMessage('MARKET_BASE_PATH'))}</div>
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
	      ownerTypeId: this.getEntityTypeId(),
	      ruleArray: this.getSharingRule()
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
	  onRuleUpdatedAction() {
	    return BX.ajax.runAction('crm.api.timeline.calendar.sharing.onRuleUpdated', {
	      data: {
	        linkHash: this.link.hash,
	        ownerId: this.getEntityId(),
	        ownerTypeId: this.getEntityTypeId()
	      }
	    }).then(response => {}, error => {
	      console.error(error);
	      return false;
	    });
	  }
	  getSharingRule() {
	    var _this$newDialog$getSe, _this$newDialog;
	    return (_this$newDialog$getSe = (_this$newDialog = this.newDialog) == null ? void 0 : _this$newDialog.getSettingsControlRule()) != null ? _this$newDialog$getSe : this.link.rule;
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
	  isChannelBitrix24(channel) {
	    return channel.id === crm_messagesender.Types.bitrix24;
	  }
	  async isBitrix24Approved() {
	    return await crm_messagesender.ConditionChecker.checkIsApproved({
	      openLineCode: crm_messagesender.OpenLineCodes.notifications,
	      senderType: crm_messagesender.Types.bitrix24
	    });
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
	    crm_tourManager.TourManager.getInstance().registerWithLaunch(babelHelpers.classPrivateFieldLooseBase(this, _getTour)[_getTour]());
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
	function _getTour2() {
	  const guide = this.getGuide();
	  const pulsar = this.getPulsar();
	  return new class SharingTour {
	    canShow() {
	      return true;
	    }
	    show() {
	      setTimeout(() => {
	        guide.showNextStep();
	        pulsar.show();
	      }, 1000);
	    }
	    getGuide() {
	      return guide;
	    }
	  }();
	}

	let _$4 = t => t,
	  _t$4,
	  _t2$4,
	  _t3$3,
	  _t4$3,
	  _t5$2,
	  _t6$2,
	  _t7$2,
	  _t8$1,
	  _t9$1,
	  _t10$1,
	  _t11$1,
	  _t12$1,
	  _t13;

	/** @memberof BX.Crm.Timeline.MenuBar */
	var _renderEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEditor");
	var _renderSetupText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSetupText");
	var _renderTemplatesContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTemplatesContainer");
	var _renderFilesSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFilesSelector");
	var _subscribeToReceiversChanges$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToReceiversChanges");
	class Sms extends WithEditor {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _subscribeToReceiversChanges$1, {
	      value: _subscribeToReceiversChanges2$1
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
	    return main_core.Tag.render(_t$4 || (_t$4 = _$4`<div class="crm-entity-stream-content-new-detail --focus --hidden">
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
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToReceiversChanges$1)[_subscribeToReceiversChanges$1]();
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
	        href: BX.message('MARKET_BASE_PATH') + 'category/crm_robot_sms/',
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
	    const isSalescenterToolEnabled = BX.prop.getBoolean(this.getSetting('smsConfig', {}), 'isSalescenterToolEnabled', false);
	    if (!isSalescenterToolEnabled) {
	      BX.loadExt('salescenter.tool-availability-manager').then(() => {
	        BX.Salescenter.ToolAvailabilityManager.openSalescenterToolDisabledSlider();
	      });
	      return;
	    }
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
	  this._saveButton = main_core.Tag.render(_t2$4 || (_t2$4 = _$4`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SEND'));
	  this._cancelButton = main_core.Tag.render(_t3$3 || (_t3$3 = _$4`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	  this._input = main_core.Tag.render(_t4$3 || (_t4$3 = _$4`<textarea class="crm-entity-stream-content-new-sms-textarea" rows='1' placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_ENTER_MESSAGE'));
	  return main_core.Tag.render(_t5$2 || (_t5$2 = _$4`<div class="crm-entity-stream-content-sms-buttons-container">
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
		`), enableSalesCenter ? main_core.Tag.render(_t6$2 || (_t6$2 = _$4`
				<div class="crm-entity-stream-content-sms-button" data-role="salescenter-starter">
					<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')) : null, enableFiles ? main_core.Tag.render(_t7$2 || (_t7$2 = _$4`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-file-selector">
					<div class="crm-entity-stream-content-sms-file-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SEND_FILE')) : null, enableDocuments ? main_core.Tag.render(_t8$1 || (_t8$1 = _$4`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-document-selector">
					<div class="crm-entity-stream-content-sms-document-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${0}</div>
				</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SEND_DOCUMENT')) : null, main_core.Loc.getMessage('CRM_TIMELINE_DETAILS'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SENDER'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_FROM'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_TO'), this._input, babelHelpers.classPrivateFieldLooseBase(this, _renderTemplatesContainer)[_renderTemplatesContainer](), babelHelpers.classPrivateFieldLooseBase(this, _renderFilesSelector)[_renderFilesSelector](), this._saveButton, this._cancelButton, main_core.Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS_FROM'));
	}
	function _renderSetupText2() {
	  const enableSalesCenter = BX.prop.getBoolean(this.getSetting('smsConfig', {}), 'isSalescenterEnabled', false);
	  return main_core.Tag.render(_t9$1 || (_t9$1 = _$4`<div class="crm-entity-stream-content-sms-conditions-container">
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
		</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_1'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_2'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_3_MSGVER_1'), main_core.Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_URL'), enableSalesCenter ? main_core.Tag.render(_t10$1 || (_t10$1 = _$4`<div class="crm-entity-stream-content-sms-salescenter-container-absolute" data-role="salescenter-starter">
	<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
	<div class="crm-entity-stream-content-sms-button-text">${0}</div>
</div>`), main_core.Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')) : null);
	}
	function _renderTemplatesContainer2() {
	  this._templatesContainer = main_core.Tag.render(_t11$1 || (_t11$1 = _$4`<div class="crm-entity-stream-content-new-sms-templates">
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
	    return main_core.Tag.render(_t12$1 || (_t12$1 = _$4`<div class="crm-entity-stream-content-sms-file-uploader-zone" data-role="sms-file-upload-zone" data-node-id="${0}">
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
	    return main_core.Tag.render(_t13 || (_t13 = _$4`<div class="crm-entity-stream-content-sms-file-external-link-popup" data-role="sms-file-external-link-disabled">
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
	function _subscribeToReceiversChanges2$1() {
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

	class Task extends Item {
	  showSlider() {
	    BX.CrmActivityEditor.getDefault().addTask({
	      'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
	      'ownerID': this.getEntityId(),
	      'fromTimeline': true
	    });
	  }
	  supportsLayout() {
	    return false;
	  }
	}

	let _$5 = t => t,
	  _t$5,
	  _t2$5,
	  _t3$4;

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
	    babelHelpers.classPrivateFieldLooseBase(this, _todoEditorContainer)[_todoEditorContainer] = main_core.Tag.render(_t$5 || (_t$5 = _$5`<div></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton] = main_core.Tag.render(_t2$5 || (_t2$5 = _$5`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-disabled" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_SAVE_BUTTON'));
	    return main_core.Tag.render(_t3$4 || (_t3$4 = _$5`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-todo --hidden">
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
	    if (this.isLocked() || main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _saveButton)[_saveButton], 'ui-btn-disabled')) {
	      return;
	    }
	    this.setLocked(true);
	    this.save().then(() => this.setLocked(false), () => this.setLocked(false)).catch(() => this.setLocked(false));
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
	    pingSettings: this.getSetting('pingSettings'),
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

	let _$6 = t => t,
	  _t$6,
	  _t2$6,
	  _t3$5,
	  _t4$4,
	  _t5$3;

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
	    babelHelpers.classPrivateFieldLooseBase(this, _waitConfigContainer)[_waitConfigContainer] = main_core.Tag.render(_t$6 || (_t$6 = _$6`<div class="crm-entity-stream-content-wait-conditions"></div>`));
	    this._saveButton = main_core.Tag.render(_t2$6 || (_t2$6 = _$6`<button onclick="${0}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round" >${0}</button>`), this.onSaveButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CREATE_WAITING'));
	    this._cancelButton = main_core.Tag.render(_t3$5 || (_t3$5 = _$6`<span onclick="${0}"  class="ui-btn ui-btn-xs ui-btn-link">${0}</span>`), this.onCancelButtonClick.bind(this), main_core.Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'));
	    this._input = main_core.Tag.render(_t4$4 || (_t4$4 = _$6`<textarea rows="1" class="crm-entity-stream-content-wait-comment-textarea" placeholder="${0}"></textarea>`), main_core.Loc.getMessage('CRM_TIMELINE_WAIT_PLACEHOLDER'));
	    return main_core.Tag.render(_t5$3 || (_t5$3 = _$6`<div class="crm-entity-stream-content-wait-detail --focus --hidden">
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

	let _$7 = t => t,
	  _t$7;
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
	    return main_core.Tag.render(_t$7 || (_t$7 = _$7`<div class="crm-entity-stream-content-new-detail ui-timeline-zoom-editor --focus --hidden"></div>`));
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
	    onFinishEdit: babelHelpers.classPrivateFieldLooseBase(this, _onFinishEdit)[_onFinishEdit].bind(this),
	    onStartSave: () => this.setLocked(true),
	    onFinishSave: () => this.setLocked(false)
	  });
	}
	function _onFinishEdit2() {
	  this.emitFinishEditEvent();
	}

	class Factory {
	  static createItem(id, context, settings) {
	    let item;
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
	      case 'gotochat':
	        item = new GoToChat();
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
	      default:
	        item = null;
	    }
	    if (!item && id.startsWith('activity_rest_')) {
	      if (main_core.Type.isPlainObject(settings) && main_core.Type.isBoolean(settings.useBuiltInInterface) && settings.useBuiltInInterface) {
	        item = new WithLayout();
	      } else {
	        item = new WithSlider();
	      }
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

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.UI.EntitySelector,BX,BX,BX,BX.UI.IconSet,BX.Crm,BX,BX.UI,BX.Vue3,BX.Main,BX.UI.Tour,BX.Crm,BX.Calendar.Sharing,BX.Crm.MessageSender,BX.Event,BX.Crm.Activity,BX.Crm,BX));
//# sourceMappingURL=toolbar.bundle.js.map

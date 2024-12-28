/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,main_core_cache,ui_wizard,sign_v2_preview,sign_v2_analytics) {
	'use strict';

	function decorateResultBeforeCompletion$$1(innerCallback, onSuccess, onFail) {
	  return async () => {
	    let result = false;
	    try {
	      result = await innerCallback();
	    } catch (e) {
	      await onFail();
	      throw e;
	    }
	    if (result) {
	      await onSuccess();
	    } else {
	      await onFail();
	    }
	    return result;
	  };
	}
	function isTemplateMode$$1(mode) {
	  return mode === DocumentMode.template;
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const DocumentMode = Object.freeze({
	  document: 'document',
	  template: 'template'
	});
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _containerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("containerId");
	var _preview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preview");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _wizardOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wizardOptions");
	var _previewLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previewLayout");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _overlayContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("overlayContainer");
	var _currentOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentOverlay");
	var _isEditMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEditMode");
	var _createHead = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createHead");
	var _getHeaderTitleSubText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeaderTitleSubText");
	var _getHeaderTitleText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeaderTitleText");
	var _getLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLayout");
	var _getOverlayContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOverlayContainer");
	var _showCompleteNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCompleteNotification");
	var _renderPages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPages");
	var _subscribeOnEditorEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEditorEvents");
	var _appendOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendOverlay");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _showOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showOverlay");
	var _hideOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideOverlay");
	class SignSettings {
	  constructor(containerId, signOptions = {}, wizardOptions = {}) {
	    var _config$documentSendC;
	    Object.defineProperty(this, _hideOverlay, {
	      value: _hideOverlay2
	    });
	    Object.defineProperty(this, _showOverlay, {
	      value: _showOverlay2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _appendOverlay, {
	      value: _appendOverlay2
	    });
	    Object.defineProperty(this, _subscribeOnEditorEvents, {
	      value: _subscribeOnEditorEvents2
	    });
	    Object.defineProperty(this, _renderPages, {
	      value: _renderPages2
	    });
	    Object.defineProperty(this, _showCompleteNotification, {
	      value: _showCompleteNotification2
	    });
	    Object.defineProperty(this, _getOverlayContainer, {
	      value: _getOverlayContainer2
	    });
	    Object.defineProperty(this, _getLayout, {
	      value: _getLayout2
	    });
	    Object.defineProperty(this, _getHeaderTitleText, {
	      value: _getHeaderTitleText2
	    });
	    Object.defineProperty(this, _getHeaderTitleSubText, {
	      value: _getHeaderTitleSubText2
	    });
	    Object.defineProperty(this, _createHead, {
	      value: _createHead2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _containerId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preview, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wizardOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _previewLayout, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _overlayContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _currentOverlay, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isEditMode, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId] = containerId;
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview] = new sign_v2_preview.Preview();
	    babelHelpers.classPrivateFieldLooseBase(this, _wizardOptions)[_wizardOptions] = wizardOptions;
	    const {
	      type = '',
	      config = {},
	      documentMode,
	      initiatedByType
	    } = signOptions;
	    this.documentMode = documentMode;
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = type;
	    const {
	      languages
	    } = (_config$documentSendC = config.documentSendConfig) != null ? _config$documentSendC : {};
	    const EditorConstructor = main_core.Reflection.getClass('top.BX.Sign.V2.Editor');
	    this.editor = new EditorConstructor(type, {
	      languages,
	      isTemplateMode: this.isTemplateMode(),
	      documentInitiatedByType: initiatedByType
	    });
	  }
	  isTemplateMode() {
	    return this.documentMode === DocumentMode.template;
	  }
	  isDocumentMode() {
	    return this.documentMode === DocumentMode.document;
	  }
	  onComplete(showNotification = true) {
	    BX.SidePanel.Instance.close();
	    if (showNotification) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showCompleteNotification)[_showCompleteNotification]();
	    }
	    const queryString = window.location.search;
	    const urlParams = new URLSearchParams(queryString);
	    if (!urlParams.has('noRedirect')) {
	      const {
	        entityTypeId,
	        entityId
	      } = this.documentSetup.setupData;
	      const detailsUrl = `/crm/type/${entityTypeId}/details/${entityId}/`;
	      BX.SidePanel.Instance.open(detailsUrl);
	    }
	  }
	  subscribeOnEvents() {
	    const settingsEvents = [{
	      type: 'toggleActivity',
	      stage: 'setup',
	      method: ({
	        data
	      }) => {
	        const {
	          selected
	        } = data;
	        this.wizard.toggleBtnActiveState('next', !selected);
	      }
	    }, {
	      type: 'addFile',
	      stage: 'setup',
	      method: ({
	        data
	      }) => {
	        this.wizard.toggleBtnActiveState('next', !data.ready);
	      }
	    }, {
	      type: 'removeFile',
	      stage: 'setup',
	      method: ({
	        data
	      }) => {
	        this.wizard.toggleBtnActiveState('next', !data.ready);
	      }
	    }, {
	      type: 'clearFiles',
	      stage: 'setup',
	      method: () => this.wizard.toggleBtnActiveState('next', true)
	    }, {
	      type: 'showEditor',
	      stage: 'send',
	      method: () => this.editor.show()
	    }, {
	      type: 'changeTitle',
	      stage: 'send',
	      method: ({
	        data
	      }) => {
	        this.documentSetup.setupData = {
	          ...this.documentSetup.setupData,
	          title: data.title
	        };
	        const {
	          blankTitle
	        } = data;
	        if (blankTitle) {
	          const {
	            blankSelector,
	            setupData
	          } = this.documentSetup;
	          blankSelector.modifyBlankTitle(setupData.blankId, blankTitle);
	        }
	      }
	    }, {
	      type: 'close',
	      stage: 'send',
	      method: () => this.onComplete(false)
	    }, {
	      type: 'hidePreview',
	      stage: 'send',
	      method: () => main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _previewLayout)[_previewLayout], 'display', 'none')
	    }, {
	      type: 'showPreview',
	      stage: 'send',
	      method: () => main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _previewLayout)[_previewLayout], 'display', 'flex')
	    }, {
	      type: 'appendOverlay',
	      stage: 'send',
	      method: event => {
	        var _event$data;
	        return babelHelpers.classPrivateFieldLooseBase(this, _appendOverlay)[_appendOverlay](event == null ? void 0 : (_event$data = event.data) == null ? void 0 : _event$data.overlay);
	      }
	    }, {
	      type: 'showOverlay',
	      stage: 'send',
	      method: () => babelHelpers.classPrivateFieldLooseBase(this, _showOverlay)[_showOverlay]()
	    }, {
	      type: 'hideOverlay',
	      stage: 'send',
	      method: () => babelHelpers.classPrivateFieldLooseBase(this, _hideOverlay)[_hideOverlay]()
	    }];
	    settingsEvents.forEach(({
	      type,
	      method,
	      stage
	    }) => {
	      const step = stage === 'setup' ? this.documentSetup : this.documentSend;
	      step.subscribe(type, method);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEditorEvents)[_subscribeOnEditorEvents]();
	  }
	  async setupDocument(uid, preparedPages = false) {
	    if (this.documentSetup.isSameBlankSelected()) {
	      void (await this.documentSetup.setup(uid));
	      return this.documentSetup.setupData;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].urls = [];
	    this.editor.setUrls([], 0);
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].setBlocks();
	    await this.documentSetup.setup(uid);
	    const {
	      setupData
	    } = this.documentSetup;
	    if (!setupData) {
	      return null;
	    }
	    const {
	      blocks
	    } = setupData;
	    await babelHelpers.classPrivateFieldLooseBase(this, _renderPages)[_renderPages](blocks, preparedPages);
	    return setupData;
	  }
	  async init(uid, templateUid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] = main_core.Type.isStringFilled(uid) || main_core.Type.isStringFilled(templateUid);
	    const metadata = this.getStepsMetadata(this, uid, templateUid);
	    const {
	      complete,
	      ...rest
	    } = babelHelpers.classPrivateFieldLooseBase(this, _wizardOptions)[_wizardOptions];
	    const title = this.isTemplateMode() ? main_core.Loc.getMessage('SIGN_SETTINGS_CREATE_TEMPLATE') : main_core.Loc.getMessage('SIGN_SETTINGS_SEND_FOR_SIGN');
	    this.wizard = new ui_wizard.Wizard(metadata, {
	      back: {
	        className: 'ui-btn-light-border'
	      },
	      next: {
	        className: 'ui-btn-primary'
	      },
	      complete: {
	        className: 'ui-btn-primary',
	        title,
	        onComplete: () => this.onComplete(),
	        ...complete
	      },
	      ...rest
	    });
	    if (uid) {
	      await this.applyDocumentData(uid);
	    }
	    if (templateUid) {
	      await this.applyTemplateData(templateUid);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _render)[_render](uid);
	  }
	  async applyTemplateData(templateUid) {}
	  getStepsMetadata(signSettings, documentUid, templateUid) {
	    return {};
	  }
	  setAnalyticsContext(context) {
	    this.getAnalytics().setContext(new sign_v2_analytics.Context(context));
	  }
	  getAnalytics() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('analytics', () => {
	      var _top$BX$Sign$V2$Analy, _top$BX;
	      return new ((_top$BX$Sign$V2$Analy = (_top$BX = top.BX) == null ? void 0 : _top$BX.Sign.V2.Analytics) != null ? _top$BX$Sign$V2$Analy : sign_v2_analytics.Analytics)();
	    });
	  }
	  isEditMode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode];
	  }
	}
	function _createHead2() {
	  const headerTitle = babelHelpers.classPrivateFieldLooseBase(this, _getHeaderTitleText)[_getHeaderTitleText]();
	  const headerTitleSub = babelHelpers.classPrivateFieldLooseBase(this, _getHeaderTitleSubText)[_getHeaderTitleSubText]();
	  return main_core.Tag.render(_t || (_t = _`
			<div class="sign-settings__head">
				<div>
					<p class="sign-settings__head_title">${0}</p>
					<p class="sign-settings__head_title --sub">
						${0}
					</p>
				</div>
			</div>
		`), headerTitle, headerTitleSub);
	}
	function _getHeaderTitleSubText2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === 'b2b') {
	    return main_core.Loc.getMessage('SIGN_SETTINGS_B2B_TITLE_SUB');
	  }
	  if (this.isTemplateMode() && babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode]) {
	    return null;
	  }
	  return main_core.Loc.getMessage('SIGN_SETTINGS_B2E_TITLE_SUB');
	}
	function _getHeaderTitleText2() {
	  if (this.isTemplateMode()) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] ? main_core.Loc.getMessage('SIGN_SETTINGS_TITLE_TEMPLATE_EDIT') : main_core.Loc.getMessage('SIGN_SETTINGS_TITLE_TEMPLATE');
	  }
	  return main_core.Loc.getMessage('SIGN_SETTINGS_TITLE');
	}
	function _getLayout2() {
	  const className = babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === 'b2e' ? 'sign-settings --b2e' : 'sign-settings';
	  babelHelpers.classPrivateFieldLooseBase(this, _previewLayout)[_previewLayout] = babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].getLayout();
	  babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-settings__scope ${0}">
				<div class="sign-settings__sidebar">
					${0}
					${0}
				</div>
				${0}
			</div>
		`), className, babelHelpers.classPrivateFieldLooseBase(this, _createHead)[_createHead](), this.wizard.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _previewLayout)[_previewLayout]);
	  return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	}
	function _getOverlayContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer] = main_core.Tag.render(_t3 || (_t3 = _`<div class="sign-settings__overlay"></div>`));
	  }
	  main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer]);
	  return babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer];
	}
	function _showCompleteNotification2() {
	  const Notification = main_core.Reflection.getClass('top.BX.UI.Notification');
	  Notification.Center.notify({
	    content: main_core.Text.encode(main_core.Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT')),
	    autoHideDelay: 4000
	  });
	}
	function _renderPages2(blocks, preparedPages = false) {
	  babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].ready = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].setBlocks(blocks);
	  this.wizard.toggleBtnActiveState('back', true);
	  const handler = (urls, totalPages) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].ready = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].urls = urls;
	    this.editor.setUrls(urls, totalPages);
	    this.wizard.toggleBtnActiveState('back', false);
	  };
	  this.documentSetup.waitForPagesList(handler, preparedPages);
	}
	function _subscribeOnEditorEvents2() {
	  this.editor.subscribe('save', ({
	    data
	  }) => {
	    const blocks = data.blocks;
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].setBlocks(blocks);
	    this.documentSetup.setupData = {
	      ...this.documentSetup.setupData,
	      blocks
	    };
	    this.documentSend.documentData = {
	      ...this.documentSend.documentData
	    };
	  });
	}
	function _appendOverlay2(overlay) {
	  if (!overlay) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _currentOverlay)[_currentOverlay]) {
	    main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _currentOverlay)[_currentOverlay]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _currentOverlay)[_currentOverlay] = overlay;
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _currentOverlay)[_currentOverlay], babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer]);
	}
	function _render2(uid) {
	  const container = document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId]);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getOverlayContainer)[_getOverlayContainer](), container);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout](), container);
	  const step = this.documentSetup.setupData ? 1 : 0;
	  const isDraft = main_core.Type.isStringFilled(uid);
	  this.wizard.toggleBtnActiveState('next', !isDraft);
	  this.wizard.moveOnStep(step);
	}
	function _showOverlay2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'display', 'none');
	  main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer]);
	}
	function _hideOverlay2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'display', 'flex');
	  main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _overlayContainer)[_overlayContainer]);
	}

	exports.DocumentMode = DocumentMode;
	exports.decorateResultBeforeCompletion = decorateResultBeforeCompletion$$1;
	exports.isTemplateMode = isTemplateMode$$1;
	exports.SignSettings = SignSettings;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Cache,BX.Ui,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=sign-settings.bundle.js.map

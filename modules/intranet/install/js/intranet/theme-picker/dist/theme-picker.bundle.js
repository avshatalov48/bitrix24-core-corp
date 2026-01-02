/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core,main_core_cache,main_core_events,main_popup,intranet_themePicker_dialog,main_loader,main_sidepanel) {
	'use strict';

	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _getCurrentSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentSlider");
	var _handleSliderLoad = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderLoad");
	var _handleThemeApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleThemeApply");
	var _handleThemeAfterApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleThemeAfterApply");
	var _applySliderTheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applySliderTheme");
	var _resetSliderTheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetSliderTheme");
	var _getOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _setVideoEventHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setVideoEventHandlers");
	var _setPrintEventHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setPrintEventHandlers");
	var _loadThemeDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadThemeDialog");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _trySaveBlurredImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("trySaveBlurredImage");
	class ThemePicker {
	  constructor(options) {
	    Object.defineProperty(this, _trySaveBlurredImage, {
	      value: _trySaveBlurredImage2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _loadThemeDialog, {
	      value: _loadThemeDialog2
	    });
	    Object.defineProperty(this, _setPrintEventHandlers, {
	      value: _setPrintEventHandlers2
	    });
	    Object.defineProperty(this, _setVideoEventHandlers, {
	      value: _setVideoEventHandlers2
	    });
	    Object.defineProperty(this, _getOptions, {
	      value: _getOptions2
	    });
	    Object.defineProperty(this, _resetSliderTheme, {
	      value: _resetSliderTheme2
	    });
	    Object.defineProperty(this, _applySliderTheme, {
	      value: _applySliderTheme2
	    });
	    Object.defineProperty(this, _handleThemeAfterApply, {
	      value: _handleThemeAfterApply2
	    });
	    Object.defineProperty(this, _handleThemeApply, {
	      value: _handleThemeApply2
	    });
	    Object.defineProperty(this, _handleSliderLoad, {
	      value: _handleSliderLoad2
	    });
	    Object.defineProperty(this, _getCurrentSlider, {
	      value: _getCurrentSlider2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', {
	      themeId: options.themeId,
	      templateId: options.templateId,
	      appliedThemeId: options.themeId,
	      appliedTheme: main_core.Type.isPlainObject(options.theme) ? options.theme : null,
	      siteId: options.siteId,
	      entityType: options.entityType,
	      entityId: options.entityId,
	      maxUploadSize: main_core.Type.isNumber(options.maxUploadSize) ? options.maxUploadSize : 5 * 1024 * 1024,
	      ajaxHandlerPath: main_core.Type.isStringFilled(options.ajaxHandlerPath) ? options.ajaxHandlerPath : null,
	      isAdmin: options.isAdmin === true,
	      allowSetDefaultTheme: options.allowSetDefaultTheme === true,
	      isVideo: options.isVideo === true,
	      behaviour: main_core.Type.isStringFilled(options.behaviour) ? options.behaviour : 'apply',
	      returnValue: options.behaviour === 'return' ? options.themeId : null,
	      isBodyClassRemoved: false
	    });
	    if (main_core.Type.isStringFilled(options == null ? void 0 : options.theme.bgImageUrlToBlur)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _trySaveBlurredImage)[_trySaveBlurredImage](options.theme);
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().isVideo) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setVideoEventHandlers)[_setVideoEventHandlers]();
	    }
	    const iframeMode = window !== window.top;
	    if (iframeMode) {
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onLoad', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderLoad)[_handleSliderLoad].bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', babelHelpers.classPrivateFieldLooseBase(this, _handleThemeApply)[_handleThemeApply].bind(this));
	      main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeAfterApply', babelHelpers.classPrivateFieldLooseBase(this, _handleThemeAfterApply)[_handleThemeAfterApply].bind(this));
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setPrintEventHandlers)[_setPrintEventHandlers]();
	  }
	  showDialog(scrollToTop = true) {
	    if (scrollToTop) {
	      window.scrollTo({
	        top: 0,
	        left: 0
	      });
	    }
	    const dialogPopup = this.getDialogPopup();
	    if (dialogPopup.isShown()) {
	      return;
	    }
	    dialogPopup.show();
	    babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show(dialogPopup.getContentContainer());
	    babelHelpers.classPrivateFieldLooseBase(this, _loadThemeDialog)[_loadThemeDialog]();
	  }
	  closeDialog() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog]) == null ? void 0 : _babelHelpers$classPr.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = null;
	    this.getDialogPopup().close();
	  }
	  enableThemeListDialog() {
	    main_core.Dom.removeClass(this.getDialogPopup().popupContainer, 'theme-dialog-popup-window-container-disabled');
	  }
	  disableThemeListDialog() {
	    main_core.Dom.addClass(this.getDialogPopup().popupContainer, 'theme-dialog-popup-window-container-disabled');
	  }
	  getDialogPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('dialogPopup', () => {
	      return new main_popup.Popup({
	        width: 800,
	        height: 500,
	        titleBar: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_TITLE'),
	        fixed: true,
	        closeByEsc: true,
	        cacheable: false,
	        bindOnResize: false,
	        closeIcon: true,
	        draggable: true,
	        events: {
	          onAfterClose: () => {
	            this.closeDialog();
	          },
	          onDestroy: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('dialogPopup');
	          }
	        }
	      });
	    });
	  }
	  hideLoader() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().hide();
	  }
	  getTemplateId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().templateId;
	  }
	  getSiteId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().siteId;
	  }
	  getEntityType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().entityType;
	  }
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().entityId;
	  }
	  getAjaxHandlerPath() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().ajaxHandlerPath;
	  }
	  getVideoElement() {
	    return document.querySelector('.theme-video');
	  }
	  getMaxUploadSize() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().maxUploadSize;
	  }
	  isCurrentUserAdmin() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().isAdmin;
	  }
	  canSetDefaultTheme() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().allowSetDefaultTheme;
	  }
	  getThemeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().themeId;
	  }
	  setThemeId(themeId) {
	    const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();
	    options.themeId = themeId;
	  }
	  getAppliedThemeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().appliedThemeId;
	  }
	  setAppliedThemeId(themeId) {
	    const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();
	    options.appliedThemeId = themeId;
	  }
	  getAppliedTheme() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().appliedTheme;
	  }
	  setThemes(themes) {
	    if (main_core.Type.isArray(themes)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('themes', themes);
	    }
	  }
	  getThemes() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('themes', []);
	  }
	  setBaseThemes(themes) {
	    if (main_core.Type.isPlainObject(themes)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('baseTheme', themes);
	    }
	  }
	  getBaseThemes() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('baseTheme', {});
	  }
	  getTheme(themeId) {
	    const themes = this.getThemes();
	    for (const theme of themes) {
	      if (theme.id === themeId) {
	        return theme;
	      }
	    }
	    return null;
	  }
	  removeTheme(themeId) {
	    this.setThemes(this.getThemes().filter(theme => theme.id !== themeId));
	  }
	  updateTheme(themeId, theme) {
	    const themes = this.getThemes();
	    const themeIndex = themes.findIndex(t => t.id === themeId);
	    if (themeIndex === -1 || !main_core.Type.isPlainObject(theme)) {
	      return;
	    }
	    for (const [key, value] of Object.entries(theme)) {
	      themes[themeIndex][key] = value;
	    }
	  }
	  setReturnValue(themeId) {
	    const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();
	    options.returnValue = themeId;
	  }
	  getReturnValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().returnValue;
	  }
	  needReturnValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().behaviour === 'return';
	  }
	  shouldPlayVideo() {
	    const iframeMode = window !== window.top;
	    if (iframeMode) {
	      return main_sidepanel.SidePanel.Instance.getSliderByWindow(window) === main_sidepanel.SidePanel.Instance.getTopSlider();
	    }
	    return !main_sidepanel.SidePanel.Instance.isOpen();
	  }
	  playVideo() {
	    const video = this.getVideoElement();
	    if (video) {
	      video.play().catch(() => {});
	    }
	  }
	  pauseVideo() {
	    const video = this.getVideoElement();
	    if (video) {
	      video.pause();
	    }
	  }
	  handleWindowFocus() {
	    if (this.shouldPlayVideo()) {
	      this.playVideo();
	    }
	  }
	  handleWindowBlur() {
	    this.pauseVideo();
	  }
	  handleSliderOpen() {
	    this.handleWindowBlur();
	  }
	  handleSliderClose() {
	    this.handleWindowFocus();
	  }
	  handleVisibilityChange() {
	    const video = this.getVideoElement();
	    if (video) {
	      if (document.visibilityState === 'hidden') {
	        this.handleWindowBlur();
	      } else {
	        this.handleWindowFocus();
	      }
	    }
	  }
	  handleBeforePrint(event) {
	    window.scroll(0, 0);
	    if (main_core.Dom.hasClass(document.body, 'bitrix24-light-theme')) {
	      main_core.Dom.removeClass(document.body, 'bitrix24-light-theme');
	      babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().isBodyClassRemoved = true;
	    }
	    const contextsToRemove = ['--ui-context-edge-dark', '--ui-context-edge-light', '--ui-context-content-dark'];
	    contextsToRemove.forEach(contextToRemove => {
	      if (main_core.Dom.hasClass(document.body, contextToRemove)) {
	        main_core.Dom.removeClass(document.body, contextToRemove);
	        babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().removedContextClassname = contextToRemove;
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().removedContextClassname) {
	      main_core.Dom.addClass(document.body, '--ui-context-content-light');
	    }
	  }
	  handleAfterPrint() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().isBodyClassRemoved) {
	      main_core.Dom.addClass(document.body, 'bitrix24-light-theme');
	      babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().isBodyClassRemoved = false;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().removedContextClassname) {
	      main_core.Dom.removeClass(document.body, 'ui-context-content-light');
	      main_core.Dom.addClass(document.body, babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().removedContextClassname);
	      babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().removedContextClassname = null;
	    }
	  }
	  handleMediaPrint(mql) {
	    if (mql.matches) {
	      this.handleBeforePrint();
	    } else {
	      this.handleAfterPrint();
	    }
	  }
	  getVideoContainer() {
	    return document.querySelector('.theme-video-container');
	  }
	}
	function _getCurrentSlider2() {
	  const sliderManager = main_core.Reflection.getClass('top.BX.SidePanel.Instance');
	  if (sliderManager === null) {
	    return null;
	  }
	  return sliderManager.getSliderByWindow(window);
	}
	function _handleSliderLoad2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _applySliderTheme)[_applySliderTheme]();
	}
	function _handleThemeApply2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resetSliderTheme)[_resetSliderTheme]();
	}
	function _handleThemeAfterApply2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _applySliderTheme)[_applySliderTheme]();
	}
	function _applySliderTheme2() {
	  const currentSlider = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentSlider)[_getCurrentSlider]();
	  if (currentSlider === null) {
	    return;
	  }
	  const background = main_core.Dom.style(document.body, '--air-theme-background');
	  if (main_core.Type.isStringFilled(background)) {
	    main_core.Dom.style(currentSlider.getContentContainer(), 'background', background);
	    main_core.Dom.style(document.body, '--air-theme-background', 'transparent');
	  } else {
	    main_core.Dom.style(currentSlider.getContentContainer(), 'background', null);
	  }
	}
	function _resetSliderTheme2() {
	  const currentSlider = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentSlider)[_getCurrentSlider]();
	  if (currentSlider === null) {
	    return;
	  }
	  main_core.Dom.style(document.body, '--air-theme-background', null);
	  main_core.Dom.style(currentSlider.getContentContainer(), 'background', null);
	}
	function _getOptions2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	}
	function _setVideoEventHandlers2() {
	  main_core.Event.bind(window, 'focus', this.handleWindowFocus.bind(this));
	  main_core.Event.bind(window, 'blur', this.handleWindowBlur.bind(this));
	  main_core_events.EventEmitter.subscribe('OnIframeFocus', this.handleWindowFocus.bind(this));
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', this.handleSliderOpen.bind(this));
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose.bind(this));
	  const eventHandler = this.handleVisibilityChange.bind(this);
	  main_core.Event.bind(window, 'load', eventHandler);
	  main_core.Event.bind(document, 'visibilitychange', eventHandler);
	}
	function _setPrintEventHandlers2() {
	  if ('onbeforeprint' in window) {
	    main_core.Event.bind(window, 'beforeprint', this.handleBeforePrint.bind(this));
	    main_core.Event.bind(window, 'afterprint', this.handleAfterPrint.bind(this));
	  } else if (window.matchMedia) {
	    window.matchMedia('print').addListener(this.handleMediaPrint.bind(this));
	  }
	}
	function _loadThemeDialog2() {
	  main_core.Runtime.loadExtension('intranet.theme-picker.dialog').then(exports => {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3;
	    const {
	      ThemePickerDialog: Dialog
	    } = exports;
	    (_babelHelpers$classPr3 = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr3 : _babelHelpers$classPr2[_dialog] = new Dialog(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].show();
	  }).catch(error => {
	    console.error(error);
	  });
	}
	function _getLoader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('loader', () => {
	    return new main_loader.Loader({
	      size: 120
	    });
	  });
	}
	function _trySaveBlurredImage2(theme) {
	  const saveBlurredImage = async () => {
	    try {
	      const {
	        ThemePickerDialog: Dialog
	      } = await main_core.Runtime.loadExtension('intranet.theme-picker.dialog');
	      const dialog = new Dialog(this);
	      await dialog.makeCustomThemeBlurred(theme, {
	        ignoreErrors: true,
	        applyTheme: true
	      });
	      await new Promise(() => {}); // Keep the lock exclusive between browser tabs
	    } catch (ex) {
	      console.error(ex);
	    }
	  };
	  if (window.navigator.locks) {
	    window.navigator.locks.request('save-blurred-image', saveBlurredImage);
	  } else {
	    void saveBlurredImage();
	  }
	}

	exports.ThemePicker = ThemePicker;

}((this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {}),BX,BX.Cache,BX.Event,BX.Main,BX.Intranet.Bitrix24.ThemePicker,BX,BX.SidePanel));
//# sourceMappingURL=theme-picker.bundle.js.map

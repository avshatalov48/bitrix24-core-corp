/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core,main_core_events,main_sidepanel) {
	'use strict';

	var _slider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("slider");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _loaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaded");
	var _handleSliderOpenStartOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderOpenStartOnce");
	var _handleSliderOpening = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderOpening");
	var _handleSliderClosing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderClosing");
	var _handleSliderCloseComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderCloseComplete");
	var _handleSliderDestroy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderDestroy");
	var _handleZIndexChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleZIndexChange");
	class ChatMenuBar {
	  constructor(slider) {
	    Object.defineProperty(this, _handleZIndexChange, {
	      value: _handleZIndexChange2
	    });
	    Object.defineProperty(this, _handleSliderDestroy, {
	      value: _handleSliderDestroy2
	    });
	    Object.defineProperty(this, _handleSliderCloseComplete, {
	      value: _handleSliderCloseComplete2
	    });
	    Object.defineProperty(this, _handleSliderClosing, {
	      value: _handleSliderClosing2
	    });
	    Object.defineProperty(this, _handleSliderOpening, {
	      value: _handleSliderOpening2
	    });
	    Object.defineProperty(this, _handleSliderOpenStartOnce, {
	      value: _handleSliderOpenStartOnce2
	    });
	    Object.defineProperty(this, _slider, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loaded, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider] = slider;
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById('im-chat-menu');
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      console.warn('ChatMenu: container not found');
	      return;
	    }
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], document.body);
	    main_core_events.EventEmitter.subscribeOnce(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider], 'SidePanel.Slider:onOpenStart', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderOpenStartOnce)[_handleSliderOpenStartOnce].bind(this));
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider], 'SidePanel.Slider:onOpening', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderOpening)[_handleSliderOpening].bind(this));
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider], 'SidePanel.Slider:onClosing', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderClosing)[_handleSliderClosing].bind(this));
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider], 'SidePanel.Slider:onCloseComplete', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderCloseComplete)[_handleSliderCloseComplete].bind(this));
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider], 'SidePanel.Slider:onDestroy', babelHelpers.classPrivateFieldLooseBase(this, _handleSliderDestroy)[_handleSliderDestroy].bind(this));
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpening', event => {
	      const [sliderEvent] = event.getData();
	      if (sliderEvent.getSlider() !== babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider]) {
	        main_core.Dom.style(this.getContainer(), 'background', babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].getOverlayBgColor());
	        main_core.Dom.style(this.getContainer(), 'box-shadow', `0px 0px 10px 3px ${babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].getOverlayBgColor()}`);
	      }
	    });
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClosing', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider] === main_sidepanel.SidePanel.Instance.getPreviousSlider()) {
	        main_core.Dom.style(this.getContainer(), 'background', null);
	        main_core.Dom.style(this.getContainer(), 'box-shadow', null);
	      }
	    });
	    main_core_events.EventEmitter.subscribe('IM.Layout:onLayoutChange', () => {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _loaded)[_loaded]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _loaded)[_loaded] = true;
	        main_core.Dom.addClass(this.getContainer(), '--loaded');
	      }
	    });
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  setZIndex(zIndex) {
	    main_core.Dom.style(this.getContainer(), 'z-index', zIndex);
	  }
	  reset() {
	    var _this$getMenu;
	    (_this$getMenu = this.getMenu()) == null ? void 0 : _this$getMenu.reset();
	  }
	  getMenu() {
	    /**
	     *
	     * @type {BX.Main.interfaceButtonsManager}
	     */
	    const menuManager = main_core.Reflection.getClass('BX.Main.interfaceButtonsManager');
	    if (menuManager) {
	      return menuManager.getById('chat-menu');
	    }
	    return null;
	  }
	}
	function _handleSliderOpenStartOnce2() {
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].getZIndexComponent(), 'onZIndexChange', babelHelpers.classPrivateFieldLooseBase(this, _handleZIndexChange)[_handleZIndexChange].bind(this));
	}
	function _handleSliderOpening2() {
	  this.setZIndex(babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].getZIndexComponent().getZIndex() + 1);
	  main_core.Dom.style(this.getContainer(), 'display', 'block');
	  main_core.Dom.style(this.getContainer(), 'background', null);
	  main_core.Dom.style(this.getContainer(), 'box-shadow', null);
	  requestAnimationFrame(() => {
	    main_core.Dom.addClass(this.getContainer(), '--open');
	  });
	}
	function _handleSliderClosing2() {
	  this.reset();
	  main_core.Dom.removeClass(this.getContainer(), '--open');
	}
	function _handleSliderCloseComplete2() {
	  main_core.Dom.style(this.getContainer(), 'display', 'none');
	  main_core.Dom.style(this.getContainer(), 'background', null);
	  main_core.Dom.style(this.getContainer(), 'box-shadow', null);
	}
	function _handleSliderDestroy2() {
	  this.reset();
	}
	function _handleZIndexChange2() {
	  const sliderZIndex = babelHelpers.classPrivateFieldLooseBase(this, _slider)[_slider].getZIndexComponent().getZIndex();
	  this.setZIndex(sliderZIndex + 1);
	}

	const MENU_COLLAPSED_WIDTH = 65;
	const MENU_EXPANDED_WIDTH = 240;
	var _onWindowResize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onWindowResize");
	var _chatMenuBar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatMenuBar");
	var _verticalScrollWidth = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("verticalScrollWidth");
	var _handleWindowResize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleWindowResize");
	class Slider extends main_sidepanel.Slider {
	  constructor(url, sliderOptions) {
	    const options = main_core.Type.isPlainObject(sliderOptions) ? {
	      ...sliderOptions
	    } : {};
	    const isMessenger = url.startsWith('im:slider');
	    if (isMessenger) {
	      options.hideControls = false;
	      options.autoOffset = false;
	    }
	    super(url, options);
	    Object.defineProperty(this, _handleWindowResize, {
	      value: _handleWindowResize2
	    });
	    Object.defineProperty(this, _onWindowResize, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _chatMenuBar, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _chatMenuBar)[_chatMenuBar] = isMessenger ? new ChatMenuBar(this) : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onWindowResize)[_onWindowResize] = babelHelpers.classPrivateFieldLooseBase(this, _handleWindowResize)[_handleWindowResize].bind(this);
	  }
	  applyHacks() {
	    babelHelpers.classPrivateFieldLooseBase(Slider, _verticalScrollWidth)[_verticalScrollWidth] = window.innerWidth - document.documentElement.clientWidth;
	    this.adjustBackgroundSize();
	    main_core.Event.bind(window, 'resize', babelHelpers.classPrivateFieldLooseBase(this, _onWindowResize)[_onWindowResize]);
	    if (this.getRightBar() && !this.isMessengerSlider()) {
	      const stack = main_core.ZIndexManager.getOrAddStack(document.body);
	      stack.register(this.getRightBar());
	      main_core.Dom.addClass(this.getRightBar(), '--ui-context-edge-dark --overlay-mode');
	    }
	    return true;
	  }
	  resetHacks() {
	    this.resetBackgroundSize();
	    main_core.Event.unbind(window, 'resize', babelHelpers.classPrivateFieldLooseBase(this, _onWindowResize)[_onWindowResize]);
	    if (this.getRightBar()) {
	      const stack = main_core.ZIndexManager.getOrAddStack(document.body);
	      stack.unregister(this.getRightBar());
	      main_core.Dom.removeClass(this.getRightBar(), '--ui-context-edge-dark --overlay-mode');
	      main_core.Dom.style(this.getRightBar(), 'z-index', null); // ZIndexManager may not remove z-index, so we do it manually
	    }
	  }

	  open() {
	    const opened = super.open();
	    if (this.getRightBar() && opened) {
	      const stack = main_core.ZIndexManager.getOrAddStack(document.body);
	      if (!this.isMessengerSlider() && !Slider.isMessengerOpen() && !Slider.isVideoCallOpen()) {
	        stack.bringToFront(this.getRightBar());
	      }
	    }
	    return opened;
	  }
	  static isMessengerOpen() {
	    const MessengerSlider = main_core.Reflection.getClass('BX.Messenger.v2.Lib.MessengerSlider');
	    if (MessengerSlider && MessengerSlider.getInstance().isOpened()) {
	      return true;
	    }
	    return Slider.isMessengerEmbedded();
	  }
	  static isMessengerEmbedded() {
	    const LayoutManager = main_core.Reflection.getClass('BX.Messenger.v2.Lib.LayoutManager');
	    return LayoutManager && LayoutManager.getInstance().isEmbeddedMode();
	  }
	  static isMessengerOpenBeforeSlider(slider) {
	    if (Slider.isMessengerEmbedded()) {
	      return true;
	    }
	    const sliders = main_sidepanel.SidePanel.Instance.getOpenSliders();
	    for (const openSlider of sliders) {
	      if (openSlider === slider) {
	        return false;
	      }
	      if (openSlider != null && openSlider.isMessengerSlider()) {
	        return true;
	      }
	    }
	    return false;
	  }
	  isMessengerSlider() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _chatMenuBar)[_chatMenuBar] !== null;
	  }
	  static isVideoCallOpen() {
	    const CallManager = main_core.Reflection.getClass('BX.Messenger.v2.Lib.CallManager');
	    return CallManager && CallManager.getInstance().hasCurrentCall();
	  }
	  getRightBar() {
	    return document.getElementById('right-bar');
	  }
	  getLeftBoundary() {
	    const windowWidth = main_core.Browser.isMobile() ? window.innerWidth : document.documentElement.clientWidth;
	    if (windowWidth < 1260) {
	      return this.getMinLeftBoundary();
	    }
	    const LeftMenu = main_core.Reflection.getClass('BX.Intranet.LeftMenu');
	    return LeftMenu != null && LeftMenu.isCollapsed() || this.isMessengerSlider() ? MENU_COLLAPSED_WIDTH : MENU_EXPANDED_WIDTH;
	  }
	  getRightBoundary() {
	    return 0;
	  }
	  getTopBoundary() {
	    return 0;
	  }
	  calculateOuterBoundary() {
	    var _this$getRightBar;
	    if (this.isMessengerSlider() || Slider.isMessengerOpenBeforeSlider(this) || Slider.isVideoCallOpen()) {
	      return {
	        top: this.isMessengerSlider() ? 58 : 16,
	        right: 18
	      };
	    }
	    const rightBarWidth = ((_this$getRightBar = this.getRightBar()) == null ? void 0 : _this$getRightBar.offsetWidth) || 0;
	    const rightMargin = babelHelpers.classPrivateFieldLooseBase(Slider, _verticalScrollWidth)[_verticalScrollWidth] + rightBarWidth;
	    return {
	      top: 16,
	      right: rightMargin
	    };
	  }
	  adjustBackgroundSize() {
	    const themePicker = main_core.Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
	    if (!themePicker) {
	      return;
	    }
	    const theme = themePicker.getAppliedTheme();
	    if (theme && theme.resizable === true) {
	      if (theme.video) {
	        this.adjustVideoSize();
	      } else if (theme.width > 0 && theme.height > 0) {
	        this.adjustImageSize(theme.width, theme.height);
	      }
	    }
	  }
	  adjustImageSize(imgWidth, imgHeight) {
	    const containerWidth = document.documentElement.clientWidth;
	    const containerHeight = document.documentElement.clientHeight;
	    const imgRatio = imgHeight / imgWidth;
	    const containerRatio = containerHeight / containerWidth;
	    const width = containerRatio > imgRatio ? containerHeight / imgRatio : containerWidth;
	    const height = containerRatio > imgRatio ? containerHeight : containerWidth * imgRatio;
	    main_core.Dom.style(document.body, '--air-theme-bg-size', `${width}px ${height}px`);
	  }
	  adjustVideoSize() {
	    const themePicker = main_core.Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
	    if (!themePicker) {
	      return;
	    }
	    const videoContainer = themePicker.getVideoContainer();
	    if (videoContainer) {
	      main_core.Dom.style(videoContainer, 'right', `${window.innerWidth - document.documentElement.clientWidth}px`);
	    }
	  }
	  resetBackgroundSize() {
	    main_core.Dom.style(document.body, '--air-theme-bg-size', null);
	    const themePicker = main_core.Reflection.getClass('BX.Intranet.Bitrix24.ThemePicker.Singleton');
	    if (themePicker) {
	      const videoContainer = themePicker.getVideoContainer();
	      if (videoContainer) {
	        main_core.Dom.style(videoContainer, 'right', null);
	      }
	    }
	  }
	}
	function _handleWindowResize2() {
	  this.adjustBackgroundSize();
	}
	Object.defineProperty(Slider, _verticalScrollWidth, {
	  writable: true,
	  value: null
	});

	main_sidepanel.SliderManager.registerSliderClass('BX.Intranet.Bitrix24.Slider', {
	  startPosition: 'bottom',
	  overlayBgColor: '#00204E',
	  overlayBgCallback: (state, slider) => {
	    const {
	      intensity
	    } = state;
	    const overlayBgColor = slider.getOverlayBgColor();
	    const overlayOpacity = slider.getOverlayOpacity();
	    const start = Math.round(overlayOpacity * intensity / 100).toString(16).padStart(2, 0);
	    const end = Math.round(100 * intensity / 100).toString(16).padStart(2, 0);
	    if (slider.isMessengerSlider()) {
	      return `linear-gradient(to bottom, ${overlayBgColor}${end} 0%, ${overlayBgColor}${end} 35px, ${overlayBgColor}${start} 145px, ${overlayBgColor}${end} 100%)`;
	    }
	    return `linear-gradient(to bottom, ${overlayBgColor}${start} 0%, ${overlayBgColor}${end} 100%)`;
	  },
	  overlayOpacity: 52,
	  autoOffset: false,
	  copyLinkLabel: true,
	  newWindowLabel: true
	}, {
	  animationDuration: 200,
	  label: {
	    text: ''
	  }
	});
	const namespace = main_core.Reflection.namespace('BX.Bitrix24');
	Object.defineProperty(namespace, 'Slider', {
	  get: () => {
	    // eslint-disable-next-line no-console
	    console.warn('Don\'t use BX.Bitrix24.Slider.');
	    return main_sidepanel.SidePanel.Instance;
	  }
	});
	Object.defineProperty(namespace, 'PageSlider', {
	  get: () => {
	    // eslint-disable-next-line no-console
	    console.warn('Don\'t use BX.Bitrix24.PageSlider.');
	    return main_sidepanel.SidePanel.Instance;
	  }
	});

	exports.Slider = Slider;

}((this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {}),BX,BX.Event,BX.SidePanel));
//# sourceMappingURL=air-sidepanel.bundle.js.map

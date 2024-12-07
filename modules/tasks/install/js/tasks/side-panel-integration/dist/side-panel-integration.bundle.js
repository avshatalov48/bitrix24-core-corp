/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup) {
	'use strict';

	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _sliders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sliders");
	var _frozen = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("frozen");
	var _frozenProps = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("frozenProps");
	class SidePanelIntegration {
	  constructor(popup) {
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _sliders, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _frozen, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _frozenProps, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = popup;
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].subscribe('onShow', this.handlePopupShow.bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].subscribe('onClose', this.handlePopupClose.bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].subscribe('onDestroy', this.handlePopupClose.bind(this));
	    this.handleSliderOpen = this.handleSliderOpen.bind(this);
	    this.handleSliderClose = this.handleSliderClose.bind(this);
	    this.handleSliderDestroy = this.handleSliderDestroy.bind(this);
	  }
	  handlePopupShow() {
	    this.bindEvents();
	  }
	  handlePopupClose() {
	    babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].clear();
	    this.unbindEvents();
	    this.unfreeze();
	  }
	  bindEvents() {
	    this.unbindEvents();
	    if (top.BX) {
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onOpen', this.handleSliderOpen);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose);
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
	    }
	  }
	  unbindEvents() {
	    if (top.BX) {
	      top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onOpen', this.handleSliderOpen);
	      top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose);
	      top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
	    }
	  }
	  handleSliderOpen(event) {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    if (!this.isPopupInSlider(slider)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].add(slider);
	      this.freeze();
	    }
	  }
	  handleSliderClose(event) {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].delete(slider);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].size === 0) {
	      this.unfreeze();
	    }
	  }
	  handleSliderDestroy(event) {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    if (this.isPopupInSlider(slider)) {
	      this.unbindEvents();
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].destroy();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].delete(slider);
	      if (babelHelpers.classPrivateFieldLooseBase(this, _sliders)[_sliders].size === 0) {
	        this.unfreeze();
	      }
	    }
	  }
	  isPopupInSlider(slider) {
	    if (slider.getFrameWindow()) {
	      return slider.getFrameWindow().document.contains(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer());
	    } else {
	      return slider.getContainer().contains(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer());
	    }
	  }
	  freeze() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _frozen)[_frozen]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _frozenProps)[_frozenProps] = {
	      autoHide: babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].autoHide,
	      closeByEsc: babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].closeByEsc
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setAutoHide(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setClosingByEsc(false);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer(), 'pointer-events', 'none');
	    babelHelpers.classPrivateFieldLooseBase(this, _frozen)[_frozen] = true;
	  }
	  unfreeze() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _frozen)[_frozen]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setAutoHide(babelHelpers.classPrivateFieldLooseBase(this, _frozenProps)[_frozenProps].autoHide !== false);
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setClosingByEsc(babelHelpers.classPrivateFieldLooseBase(this, _frozenProps)[_frozenProps].closeByEsc !== false);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer(), 'pointer-events', '');
	    babelHelpers.classPrivateFieldLooseBase(this, _frozen)[_frozen] = false;
	  }
	}

	exports.SidePanelIntegration = SidePanelIntegration;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Main));
//# sourceMappingURL=side-panel-integration.bundle.js.map

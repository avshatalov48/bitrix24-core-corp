this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core_events,main_core) {
	'use strict';

	let _ = t => t,
	    _t,
	    _t2;

	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");

	var _windowMessageHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("windowMessageHandler");

	var _getContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");

	var _handleFrameLoad = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFrameLoad");

	var _handleWindowMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleWindowMessage");

	class ReleaseSlider extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _handleWindowMessage, {
	      value: _handleWindowMessage2
	    });
	    Object.defineProperty(this, _handleFrameLoad, {
	      value: _handleFrameLoad2
	    });
	    Object.defineProperty(this, _getContent, {
	      value: _getContent2
	    });
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: 'intranet:polar-star'
	    });
	    Object.defineProperty(this, _windowMessageHandler, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Intranet.Bitrix24.ReleaseSlider');
	    options = main_core.Type.isPlainObject(options) ? options : {};
	    this.subscribeFromOptions(options.events);
	    this.url = main_core.Type.isStringFilled(options.url) ? options.url : 'about:blank';
	    this.sliderOptions = main_core.Type.isPlainObject(options.sliderOptions) ? options.sliderOptions : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _windowMessageHandler)[_windowMessageHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleWindowMessage)[_handleWindowMessage].bind(this);
	    this.html = new main_core.Cache.MemoryCache();
	  }

	  show() {
	    if (this.isOpen()) {
	      return;
	    }

	    const defaultOptions = {
	      width: 1100,
	      customLeftBoundary: 0
	    };
	    const options = Object.assign({}, defaultOptions, this.sliderOptions);
	    const userEvents = main_core.Type.isPlainObject(options.events) ? options.events : {};
	    options.events = {
	      onCloseComplete: () => {
	        main_core.Event.unbind(window, 'message', babelHelpers.classPrivateFieldLooseBase(this, _windowMessageHandler)[_windowMessageHandler]);
	        this.emit('onCloseComplete');
	      },
	      onOpenComplete: () => {
	        main_core.Event.bind(window, 'message', babelHelpers.classPrivateFieldLooseBase(this, _windowMessageHandler)[_windowMessageHandler]);
	      }
	    };

	    options.contentCallback = slider => {
	      for (const eventName in userEvents) {
	        if (main_core.Type.isFunction(userEvents[eventName])) {
	          main_core_events.EventEmitter.subscribe(slider, BX.SidePanel.Slider.getEventFullName(eventName), userEvents[eventName]);
	        }
	      }

	      return new Promise((resolve, reject) => {
	        if (this.getFrame().src !== this.url) {
	          this.getFrame().src = this.url;
	        }

	        main_core.Event.bind(this.getFrame(), 'load', babelHelpers.classPrivateFieldLooseBase(this, _handleFrameLoad)[_handleFrameLoad].bind(this));
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _getContent)[_getContent]());
	      });
	    };

	    BX.SidePanel.Instance.open(this.getId(), options);
	  }

	  hide() {
	    const slider = this.getSlider();

	    if (slider) {
	      slider.close();
	    }
	  }

	  isOpen() {
	    return this.getSlider() && this.getSlider().isOpen();
	  }

	  getId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _id)[_id];
	  }

	  getSlider() {
	    return BX.SidePanel.Instance.getSlider(this.getId());
	  }

	  getFrame() {
	    return this.html.remember('frame', () => {
	      return main_core.Tag.render(_t || (_t = _`<iframe src="about:blank" class="intranet-polar-star-iframe"></iframe>`));
	    });
	  }

	}

	function _getContent2() {
	  return this.html.remember('content', () => {
	    return main_core.Tag.render(_t2 || (_t2 = _`<div class="intranet-polar-star-container">${0}</div>`), this.getFrame());
	  });
	}

	function _handleFrameLoad2() {
	  this.emit('onLoad');
	}

	function _handleWindowMessage2(event) {
	  const frameOrigin = new URL(this.url);

	  if (event.origin !== frameOrigin.origin) {
	    return;
	  }

	  this.emit('onMessage', {
	    message: event.data,
	    event
	  });
	}

	let _$1 = t => t,
	    _t$1;
	class ReleaseEar extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    this.container = null;
	    this.setEventNamespace('BX.Intranet.Bitrix24.ReleaseEar');
	    options = main_core.Type.isPlainObject(options) ? options : {};
	    this.zone = main_core.Type.isStringFilled(options.zone) ? options.zone : 'en';
	    this.subscribeFromOptions(options.events);
	  }

	  show(animate = false) {
	    if (animate) {
	      main_core.Dom.removeClass(this.getContainer(), '--hidden');
	      requestAnimationFrame(() => {
	        requestAnimationFrame(() => {
	          main_core.Dom.removeClass(this.getContainer(), '--hidden');
	        });
	      });
	    } else {
	      main_core.Dom.removeClass(this.getContainer(), '--hidden');
	    }
	  }

	  hide() {
	    main_core.Dom.addClass(this.getContainer(), '--hidden');
	  }

	  getContainer() {
	    if (this.container === null) {
	      this.container = main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="intranet-polar-star-ear" onclick="${0}">
					<div class="intranet-polar-star-button"><i></i></div>
					<div class="intranet-polar-star-logo --${0}"></div>
				</div>
			`), this.handleClick.bind(this), this.zone);
	      main_core.Dom.append(this.container, document.body);
	    }

	    return this.container;
	  }

	  handleClick() {
	    this.emit('onClick');
	  }

	}

	var _deactivated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deactivated");

	var _runAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("runAction");

	var _handleSliderClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSliderClose");

	var _handleEarClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEarClick");

	var _handleFrameMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFrameMessage");

	class PolarStar {
	  constructor(options) {
	    Object.defineProperty(this, _handleFrameMessage, {
	      value: _handleFrameMessage2
	    });
	    Object.defineProperty(this, _handleEarClick, {
	      value: _handleEarClick2
	    });
	    Object.defineProperty(this, _handleSliderClose, {
	      value: _handleSliderClose2
	    });
	    Object.defineProperty(this, _runAction, {
	      value: _runAction2
	    });
	    Object.defineProperty(this, _deactivated, {
	      writable: true,
	      value: false
	    });
	    options = main_core.Type.isPlainObject(options) ? options : {};

	    if (!main_core.Type.isStringFilled(options.url)) {
	      throw new Error('PolarStar: the "url" parameter is required.');
	    }

	    this.slider = new ReleaseSlider({
	      url: options.url,
	      sliderOptions: options.sliderOptions,
	      events: {
	        onCloseComplete: babelHelpers.classPrivateFieldLooseBase(this, _handleSliderClose)[_handleSliderClose].bind(this),
	        onMessage: babelHelpers.classPrivateFieldLooseBase(this, _handleFrameMessage)[_handleFrameMessage].bind(this)
	      }
	    });
	    this.ear = new ReleaseEar({
	      zone: options.zone,
	      events: {
	        onClick: babelHelpers.classPrivateFieldLooseBase(this, _handleEarClick)[_handleEarClick].bind(this)
	      }
	    });
	  }

	  show(mode = 'ear') {
	    if (mode === 'slider') {
	      this.getSlider().show();

	      babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction]('show', {
	        context: 'auto'
	      });
	    } else {
	      this.getEar().show();
	    }
	  }

	  getSlider() {
	    return this.slider;
	  }

	  getEar() {
	    return this.ear;
	  }

	}

	function _runAction2(action, labels = {}, data = {}) {
	  return main_core.ajax.runComponentAction('bitrix:intranet.bitrix24.polar-star', action, {
	    mode: 'class',
	    data,
	    analyticsLabel: Object.assign({
	      module: 'intranet',
	      service: 'polar-star',
	      action: action
	    }, labels)
	  });
	}

	function _handleSliderClose2() {
	  this.getEar().show(true);

	  babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction]('close');
	}

	function _handleEarClick2() {
	  this.getEar().hide();
	  this.getSlider().show();

	  babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction]('show', {
	    context: 'ear-click'
	  });
	}

	function _handleFrameMessage2(event) {
	  const {
	    message
	  } = event.getData();

	  if (!main_core.Type.isPlainObject(message)) {
	    return;
	  }

	  if (message.command === 'endOfScroll' && babelHelpers.classPrivateFieldLooseBase(this, _deactivated)[_deactivated] === false) {
	    babelHelpers.classPrivateFieldLooseBase(this, _deactivated)[_deactivated] = true;

	    babelHelpers.classPrivateFieldLooseBase(this, _runAction)[_runAction]('deactivate').catch(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _deactivated)[_deactivated] = false;
	    });
	  }

	  if (message.command === 'openHelper') {
	    if (BX.Helper) {
	      BX.Helper.show(message.options);
	    }
	  }
	}

	exports.PolarStar = PolarStar;
	exports.ReleaseSlider = ReleaseSlider;
	exports.ReleaseEar = ReleaseEar;

}((this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {}),BX.Event,BX));
//# sourceMappingURL=script.js.map

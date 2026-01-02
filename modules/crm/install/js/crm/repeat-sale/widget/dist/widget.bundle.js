/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_confetti,ui_notification,crm_timeline_tools,ui_feedback_form,crm_integration_analytics,main_core,main_popup,ui_analytics,ui_lottie) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _isConfettiShowed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isConfettiShowed");
	var _isPreparing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPreparing");
	var _getBindElementData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBindElementData");
	var _getParentBindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getParentBindElement");
	var _sendAnalyticsCloseEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsCloseEvent");
	var _showConfetti = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showConfetti");
	class Base {
	  constructor(params = {}) {
	    Object.defineProperty(this, _showConfetti, {
	      value: _showConfetti2
	    });
	    Object.defineProperty(this, _sendAnalyticsCloseEvent, {
	      value: _sendAnalyticsCloseEvent2
	    });
	    Object.defineProperty(this, _getParentBindElement, {
	      value: _getParentBindElement2
	    });
	    Object.defineProperty(this, _getBindElementData, {
	      value: _getBindElementData2
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isConfettiShowed, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _isPreparing, {
	      writable: true,
	      value: false
	    });
	    this.params = {};
	    this.params = params;
	    if (main_core.Type.isBoolean(this.params.showConfetti)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _isConfettiShowed)[_isConfettiShowed] = !this.params.showConfetti;
	    }
	  }
	  getType() {
	    throw new Error('Must be implement in child class');
	  }
	  async show(forceShowConfetti = false, onCloseCallback = null) {
	    const data = await this.getData();
	    if (data === null) {
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup(this.getPopupParams(data, {
	        forceShowConfetti,
	        onCloseCallback
	      }));
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].adjustPosition();
	  }
	  getPopupParams(data, params = {}) {
	    return {
	      id: `crm_repeat_sale_widget_${this.getType()}`,
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _getBindElementData)[_getBindElementData](),
	      content: this.getPopupContent(data),
	      cacheable: false,
	      isScrollBlock: false,
	      className: `crm-repeat-sale-widget-popup --${this.getType()}`,
	      closeByEsc: true,
	      closeIcon: true,
	      padding: 16,
	      width: this.getPopupWidth(),
	      maxHeight: 500,
	      overlay: null,
	      autoHide: this.isAutoHidePopup(),
	      events: {
	        onclose: () => {
	          this.onClose();
	          if (main_core.Type.isFunction(params == null ? void 0 : params.onCloseCallback)) {
	            params.onCloseCallback();
	          }
	        },
	        onFirstShow: () => {
	          this.onFirstShow();
	          if (babelHelpers.classPrivateFieldLooseBase(this, _isConfettiShowed)[_isConfettiShowed] && (params == null ? void 0 : params.forceShowConfetti) !== true) {
	            return;
	          }
	          setTimeout(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _showConfetti)[_showConfetti]();
	            babelHelpers.classPrivateFieldLooseBase(this, _isConfettiShowed)[_isConfettiShowed] = true;
	          }, 100);
	        }
	      }
	    };
	  }
	  getPopupWidth() {
	    return 469;
	  }
	  isAutoHidePopup() {
	    return false;
	  }
	  onFirstShow() {
	    // may be implement in child class
	  }
	  getPopupContent(data = null) {
	    throw new Error('Must be implement in child class');
	  }
	  setPopupContent(content) {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setContent(content);
	  }
	  onClose() {
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsCloseEvent)[_sendAnalyticsCloseEvent]();
	    if (this.params.showConfetti) {
	      void main_core.ajax.runAction('crm.repeatsale.widget.incrementShowedConfettiCount');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	  }
	  getAnalyticsType() {
	    return '';
	  }
	  async getData() {
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = await this.fetchData();
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data];
	  }
	  async fetchData() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isPreparing)[_isPreparing]) {
	      return Promise.resolve(null);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isPreparing)[_isPreparing] = true;
	    return new Promise(resolve => {
	      main_core.ajax.runAction(this.getFetchUrl(), {
	        data: this.getFetchParams()
	      }).then(response => {
	        babelHelpers.classPrivateFieldLooseBase(this, _isPreparing)[_isPreparing] = false;
	        if (response.status === 'success') {
	          resolve(response.data);
	          return;
	        }
	        this.showError();
	      }, () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _isPreparing)[_isPreparing] = false;
	        this.showError();
	      }).catch(response => {
	        babelHelpers.classPrivateFieldLooseBase(this, _isPreparing)[_isPreparing] = false;
	        this.showError();
	        throw response;
	      });
	    });
	  }
	  getFetchUrl() {
	    throw new Error('Must be implement in child class');
	  }
	  getFetchParams() {
	    return {};
	  }
	  showError() {
	    const messageCode = 'CRM_REPEAT_SALE_WIDGET_ERROR';
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage(messageCode),
	      autoHideDelay: 6000
	    });
	  }
	  setBindElement(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = element;
	    return this;
	  }
	  getAnalyticsSubSection() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getParentBindElement)[_getParentBindElement]().dataset.subsection) != null ? _babelHelpers$classPr : null;
	  }
	  isShown() {
	    var _babelHelpers$classPr2;
	    return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr2.isShown();
	  }
	  close() {
	    var _babelHelpers$classPr3;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr3.close();
	  }
	  renderLottieAnimation() {
	    const container = main_core.Tag.render(_t || (_t = _`
			<div class="crm-rs__w-lottie-container">
				<div ref="lottie" class="crm-rs__w-lottie"></div>
			</div>
		`));
	    const mainAnimation = ui_lottie.Lottie.loadAnimation({
	      path: '/bitrix/js/crm/repeat-sale/widget/lottie/animation.json',
	      container: container.lottie,
	      renderer: 'svg',
	      loop: true,
	      autoplay: true
	    });
	    mainAnimation.setSpeed(0.75);
	    return container.root;
	  }
	}
	function _getBindElementData2() {
	  const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getParentBindElement)[_getParentBindElement]();
	  const bindElementRect = bindElement.getBoundingClientRect();
	  return {
	    top: bindElementRect.top + bindElementRect.height + 5 + window.pageYOffset,
	    left: bindElementRect.right - bindElement.clientWidth / 2 - this.getPopupWidth() / 2 + window.pageXOffset
	  };
	}
	function _getParentBindElement2() {
	  const hasParentButton = Boolean(babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].closest('button'));
	  const hasParentLink = Boolean(babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].closest('a'));
	  if (hasParentButton) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].closest('button');
	  }
	  if (hasParentLink) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].closest('a');
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement];
	}
	function _sendAnalyticsCloseEvent2() {
	  const type = this.getAnalyticsType();
	  const subSection = this.getAnalyticsSubSection();
	  ui_analytics.sendData(crm_integration_analytics.Builder.RepeatSale.Banner.CloseEvent.createDefault(type, subSection).buildData());
	}
	function _showConfetti2() {
	  var _babelHelpers$classPr4;
	  const container = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr4.getPopupContainer();
	  if (!container) {
	    return;
	  }
	  let canvas = null;
	  if (container.getElementsByTagName('canvas').length === 0) {
	    canvas = main_core.Tag.render(_t2 || (_t2 = _`<canvas></canvas>`));
	    main_core.Dom.style(canvas, {
	      position: 'fixed',
	      top: 0,
	      left: 0,
	      pointerEvents: 'none',
	      zIndex: '9',
	      width: '100%',
	      height: '100%'
	    });
	    main_core.Dom.append(canvas, babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer());
	  } else {
	    canvas = container.getElementsByTagName('canvas')[0];
	  }
	  const confetti = ui_confetti.Confetti.create(canvas, {
	    resize: true,
	    useWorker: true
	  });
	  confetti({
	    particleCount: 400,
	    origin: {
	      y: 1.2,
	      x: 0
	    },
	    spread: 100
	  });
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3;
	var _showSettingsButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsButton");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _onSettingsClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSettingsClick");
	var _onFeedbackClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFeedbackClick");
	var _showFeedbackCrmForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showFeedbackCrmForm");
	var _getFeedbackFormParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFeedbackFormParams");
	var _getClickEventBuilder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClickEventBuilder");
	class Footer {
	  constructor(showSettingsButton = false, analytics = {}) {
	    Object.defineProperty(this, _getClickEventBuilder, {
	      value: _getClickEventBuilder2
	    });
	    Object.defineProperty(this, _getFeedbackFormParams, {
	      value: _getFeedbackFormParams2
	    });
	    Object.defineProperty(this, _showFeedbackCrmForm, {
	      value: _showFeedbackCrmForm2
	    });
	    Object.defineProperty(this, _onFeedbackClick, {
	      value: _onFeedbackClick2
	    });
	    Object.defineProperty(this, _onSettingsClick, {
	      value: _onSettingsClick2
	    });
	    Object.defineProperty(this, _showSettingsButton, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton)[_showSettingsButton] = showSettingsButton;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = analytics;
	  }
	  getFooterContent() {
	    const settingsButton = babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton)[_showSettingsButton] ? main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div
				onclick="${0}"
			 	class="crm-rs__w-footer-button --settings"
			 >
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onSettingsClick)[_onSettingsClick].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_POPUP_FOOTER_SETTINGS')) : '';
	    const feedbackButton = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div
				onclick="${0}"
				class="crm-rs__w-footer-button --feedback"
			>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onFeedbackClick)[_onFeedbackClick].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_POPUP_FOOTER_FEEDBACK'));
	    return main_core.Tag.render(_t3 || (_t3 = _$1`
			<div class="crm-rs__w-footer-row">
				${0}
				${0}
			</div>
		`), main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _getFeedbackFormParams)[_getFeedbackFormParams]()) ? feedbackButton : '<div></div>', settingsButton);
	  }
	}
	function _onSettingsClick2() {
	  const eventBuilder = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder)[_getClickEventBuilder]();
	  eventBuilder.setElement('config');
	  ui_analytics.sendData(eventBuilder.buildData());
	  window.location.href = '/crm/repeat-sale-segment/';
	}
	function _onFeedbackClick2() {
	  const eventBuilder = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder)[_getClickEventBuilder]();
	  eventBuilder.setElement('feedback');
	  ui_analytics.sendData(eventBuilder.buildData());
	  babelHelpers.classPrivateFieldLooseBase(this, _showFeedbackCrmForm)[_showFeedbackCrmForm]();
	}
	function _showFeedbackCrmForm2() {
	  BX.UI.Feedback.Form.open({
	    id: Math.random().toString(),
	    forms: babelHelpers.classPrivateFieldLooseBase(this, _getFeedbackFormParams)[_getFeedbackFormParams]()
	  });
	}
	function _getFeedbackFormParams2() {
	  return main_core.Extension.getSettings('crm.repeat-sale.widget').get('feedbackFormParams');
	}
	function _getClickEventBuilder2() {
	  const type = babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].type;
	  const subSection = babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].subSection;
	  return crm_integration_analytics.Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$1,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9;

	// @todo need refactor and merge with start.js
	var _isFlowStarted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFlowStarted");
	var _showSettingsButton$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsButton");
	var _hasClients = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasClients");
	var _canEnableFeature = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canEnableFeature");
	var _flowExpectedEnableTimestamp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowExpectedEnableTimestamp");
	var _getBodyContentWithClients = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyContentWithClients");
	var _getBubble = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBubble");
	var _getFooterContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFooterContent");
	var _getTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitle");
	var _getBodyTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyTitle");
	var _getButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButton");
	var _getDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDescription");
	var _getDescriptionContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDescriptionContent");
	var _onButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onButtonClick");
	var _onReadMoreButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReadMoreButtonClick");
	var _showReadMore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showReadMore");
	var _isHasClients = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isHasClients");
	var _getClickEventBuilder$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClickEventBuilder");
	var _sendShowAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendShowAnalytics");
	class ForceStart extends Base {
	  constructor(params) {
	    var _params$showSettingsB;
	    super(params);
	    Object.defineProperty(this, _sendShowAnalytics, {
	      value: _sendShowAnalytics2
	    });
	    Object.defineProperty(this, _getClickEventBuilder$1, {
	      value: _getClickEventBuilder2$1
	    });
	    Object.defineProperty(this, _isHasClients, {
	      value: _isHasClients2
	    });
	    Object.defineProperty(this, _showReadMore, {
	      value: _showReadMore2
	    });
	    Object.defineProperty(this, _onReadMoreButtonClick, {
	      value: _onReadMoreButtonClick2
	    });
	    Object.defineProperty(this, _onButtonClick, {
	      value: _onButtonClick2
	    });
	    Object.defineProperty(this, _getDescriptionContent, {
	      value: _getDescriptionContent2
	    });
	    Object.defineProperty(this, _getDescription, {
	      value: _getDescription2
	    });
	    Object.defineProperty(this, _getButton, {
	      value: _getButton2
	    });
	    Object.defineProperty(this, _getBodyTitle, {
	      value: _getBodyTitle2
	    });
	    Object.defineProperty(this, _getTitle, {
	      value: _getTitle2
	    });
	    Object.defineProperty(this, _getFooterContent, {
	      value: _getFooterContent2
	    });
	    Object.defineProperty(this, _getBubble, {
	      value: _getBubble2
	    });
	    Object.defineProperty(this, _getBodyContentWithClients, {
	      value: _getBodyContentWithClients2
	    });
	    Object.defineProperty(this, _isFlowStarted, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _showSettingsButton$1, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _hasClients, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _canEnableFeature, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _flowExpectedEnableTimestamp, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$1)[_showSettingsButton$1] = (_params$showSettingsB = params.showSettingsButton) != null ? _params$showSettingsB : true;
	  }
	  getType() {
	    return WidgetType.forceStart;
	  }
	  onClose() {
	    super.onClose();
	    void main_core.ajax.runAction('crm.repeatsale.widget.incrementShowedFlowStartCount');
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted] && babelHelpers.classPrivateFieldLooseBase(this, _flowExpectedEnableTimestamp)[_flowExpectedEnableTimestamp] === null && babelHelpers.classPrivateFieldLooseBase(this, _canEnableFeature)[_canEnableFeature]) {
	      void main_core.ajax.runAction('crm.repeatsale.flow.saveExpectedEnableDate');
	    }
	  }
	  getPopupContent(data = null) {
	    if (main_core.Type.isObject(data)) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted] === null) {
	        const {
	          isFlowStarted,
	          canEnableFeature,
	          flowExpectedEnableTimestamp
	        } = data;
	        babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted] = isFlowStarted;
	        babelHelpers.classPrivateFieldLooseBase(this, _canEnableFeature)[_canEnableFeature] = canEnableFeature != null ? canEnableFeature : false;
	        babelHelpers.classPrivateFieldLooseBase(this, _flowExpectedEnableTimestamp)[_flowExpectedEnableTimestamp] = flowExpectedEnableTimestamp != null ? flowExpectedEnableTimestamp : null;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _hasClients)[_hasClients] = babelHelpers.classPrivateFieldLooseBase(this, _isHasClients)[_isHasClients](data);
	    }
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div>
				<header class="crm-rs__w-header">
					${0}
				</header>
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getTitle)[_getTitle](), babelHelpers.classPrivateFieldLooseBase(this, _getBodyContentWithClients)[_getBodyContentWithClients](), babelHelpers.classPrivateFieldLooseBase(this, _getFooterContent)[_getFooterContent]());
	  }
	  getFetchUrl() {
	    return 'crm.repeatsale.start.getData';
	  }
	  getFetchParams() {
	    return {};
	  }
	  onFirstShow() {
	    const type = this.getAnalyticsType();
	    const subSection = this.getAnalyticsSubSection();
	    babelHelpers.classPrivateFieldLooseBase(this, _sendShowAnalytics)[_sendShowAnalytics](type, subSection);
	  }
	  getAnalyticsType() {
	    return crm_integration_analytics.Dictionary.TYPE_REPEAT_SALE_BANNER_START_FORCE;
	  }
	}
	function _getBodyContentWithClients2() {
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content">
					<div class="crm-rs__w-body-title">
						${0}
					</div>
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getBodyTitle)[_getBodyTitle](), babelHelpers.classPrivateFieldLooseBase(this, _canEnableFeature)[_canEnableFeature] ? babelHelpers.classPrivateFieldLooseBase(this, _getButton)[_getButton]() : null, babelHelpers.classPrivateFieldLooseBase(this, _getBubble)[_getBubble]());
	}
	function _getBubble2() {
	  const hasClients = babelHelpers.classPrivateFieldLooseBase(this, _hasClients)[_hasClients];
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
			<div class="crm-rs__w-body-bubble ${0} ${0}">
				${0}
				<div class="crm-rs__w-body-icon"></div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted] ? '--flow-started' : '', hasClients ? '--has-clients' : '', this.renderLottieAnimation());
	}
	function _getFooterContent2() {
	  return main_core.Tag.render(_t4 || (_t4 = _$2`
			<footer class="crm-rs__w-footer">
				${0}
			</footer>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getDescription)[_getDescription]());
	}
	function _getTitle2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted]) {
	    return main_core.Tag.render(_t5 || (_t5 = _$2`
				<span>${0}</span>
			`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_FLOW_STARTED_POPUP_TITLE'));
	  }
	  return main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_TITLE');
	}
	function _getBodyTitle2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted]) {
	    return main_core.Tag.render(_t6 || (_t6 = _$2`
				<span>${0}</span>
			`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_FLOW_STARTED_TITLE'));
	  }
	  return main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE');
	}
	function _getButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted]) {
	    return null;
	  }
	  return main_core.Tag.render(_t7 || (_t7 = _$2`
			<div class="crm-rs__w-body-title-btn">
				<span
					onclick="${0}"
				>${0}</span>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onButtonClick)[_onButtonClick].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_FORCE'));
	}
	function _getDescription2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted]) {
	    const footer = new Footer(babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$1)[_showSettingsButton$1], {
	      type: this.getAnalyticsType(),
	      subSection: this.getAnalyticsSubSection()
	    });
	    return main_core.Tag.render(_t8 || (_t8 = _$2`
				<div class="crm-rs__w-buttons-wrapper">
					${0}
				</div>
			`), footer.getFooterContent());
	  }
	  const hasClients = babelHelpers.classPrivateFieldLooseBase(this, _hasClients)[_hasClients];
	  const content = babelHelpers.classPrivateFieldLooseBase(this, _getDescriptionContent)[_getDescriptionContent]();
	  return main_core.Tag.render(_t9 || (_t9 = _$2`
			<div class="crm-rs__w-body-description ${0}">
				${0}
				<div class="crm-rs__w-body-description-text ${0}">
					<span>${0}</span>
				</div>
				<div class="crm-rs__w-body-description-btn">
					<span
						onclick="${0}"
					>${0}</span>
				</div>
			</div>
		`), hasClients ? '--has-clients' : '', hasClients ? null : '<div class="crm-rs__w-body-description-border"></div>', hasClients ? '--has-clients' : '', content, babelHelpers.classPrivateFieldLooseBase(this, _onReadMoreButtonClick)[_onReadMoreButtonClick].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_READ_MORE'));
	}
	function _getDescriptionContent2() {
	  let code = null;
	  const replacements = {};
	  let isNeedReplaceLink = false;
	  let isNeedReplaceDate = false;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _flowExpectedEnableTimestamp)[_flowExpectedEnableTimestamp] === null && babelHelpers.classPrivateFieldLooseBase(this, _canEnableFeature)[_canEnableFeature]) {
	    if (this.params.isRepeatSaleGrid) {
	      code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_IN_RS_GRID';
	    } else {
	      code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME';
	      isNeedReplaceLink = true;
	    }
	    isNeedReplaceLink = true;
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _flowExpectedEnableTimestamp)[_flowExpectedEnableTimestamp] === null) {
	    code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_AND_PERMISSIONS';
	    isNeedReplaceDate = true;
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _canEnableFeature)[_canEnableFeature]) {
	    if (this.params.isRepeatSaleGrid) {
	      code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_IN_RS_GRID';
	    } else {
	      code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_TIME_AND_PERMISSIONS';
	      isNeedReplaceLink = true;
	    }
	    isNeedReplaceDate = true;
	  } else {
	    code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_TIME';
	    isNeedReplaceDate = true;
	  }
	  if (isNeedReplaceLink) {
	    replacements['[link]'] = '<a class="ui-link" href="/crm/repeat-sale-segment/">';
	    replacements['[/link]'] = '</a>';
	  }
	  if (isNeedReplaceDate) {
	    const userTime = crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(babelHelpers.classPrivateFieldLooseBase(this, _flowExpectedEnableTimestamp)[_flowExpectedEnableTimestamp]).toUserTime();
	    replacements['#DATE#'] = userTime.toDateString();
	    replacements['#TIME#'] = userTime.toTimeString();
	  }
	  return main_core.Loc.getMessage(code, replacements);
	}
	function _onButtonClick2() {
	  main_core.ajax.runAction('crm.repeatsale.flow.enable').then(response => {
	    if (response.status === 'success') {
	      babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted)[_isFlowStarted] = true;
	      this.setPopupContent(this.getPopupContent());
	      const instance = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder$1)[_getClickEventBuilder$1]();
	      instance.setElement('start_flow');
	      ui_analytics.sendData(instance.buildData());
	      return;
	    }
	    this.showError();
	    this.close();
	  }, response => {
	    this.showError();
	    this.close();
	  }).catch(response => {
	    this.showError();
	    this.close();
	  });
	}
	function _onReadMoreButtonClick2() {
	  const instance = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder$1)[_getClickEventBuilder$1]();
	  instance.setElement('info_button');
	  ui_analytics.sendData(instance.buildData());
	  babelHelpers.classPrivateFieldLooseBase(this, _showReadMore)[_showReadMore]();
	}
	function _showReadMore2() {
	  var _top$BX, _top$BX$Helper;
	  (_top$BX = top.BX) == null ? void 0 : (_top$BX$Helper = _top$BX.Helper) == null ? void 0 : _top$BX$Helper.show('redirect=detail&code=25376986');
	}
	function _isHasClients2(data) {
	  return data.count > 0;
	}
	function _getClickEventBuilder2$1() {
	  const type = this.getAnalyticsType();
	  const subSection = this.getAnalyticsSubSection();
	  return crm_integration_analytics.Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}
	function _sendShowAnalytics2(type, subSection) {
	  const instance = crm_integration_analytics.Builder.RepeatSale.Banner.ViewEvent.createDefault(type, subSection);
	  ui_analytics.sendData(instance.buildData());
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$3,
	  _t3$2,
	  _t4$1,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8$1,
	  _t9$1,
	  _t10;
	var _isFlowStarted$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFlowStarted");
	var _showSettingsButton$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsButton");
	var _hasClients$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasClients");
	var _getBodyContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyContent");
	var _getBodyContentWithClients$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyContentWithClients");
	var _getBubble$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBubble");
	var _getFooterContent$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFooterContent");
	var _getTitle$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitle");
	var _getBodyTitle$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBodyTitle");
	var _getButton$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButton");
	var _getDescription$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDescription");
	var _onButtonClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onButtonClick");
	var _onReadMoreButtonClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReadMoreButtonClick");
	var _showReadMore$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showReadMore");
	var _isHasClients$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isHasClients");
	var _getClickEventBuilder$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClickEventBuilder");
	var _sendShowAnalytics$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendShowAnalytics");
	class Start extends Base {
	  constructor(params) {
	    var _params$showSettingsB;
	    super(params);
	    Object.defineProperty(this, _sendShowAnalytics$1, {
	      value: _sendShowAnalytics2$1
	    });
	    Object.defineProperty(this, _getClickEventBuilder$2, {
	      value: _getClickEventBuilder2$2
	    });
	    Object.defineProperty(this, _isHasClients$1, {
	      value: _isHasClients2$1
	    });
	    Object.defineProperty(this, _showReadMore$1, {
	      value: _showReadMore2$1
	    });
	    Object.defineProperty(this, _onReadMoreButtonClick$1, {
	      value: _onReadMoreButtonClick2$1
	    });
	    Object.defineProperty(this, _onButtonClick$1, {
	      value: _onButtonClick2$1
	    });
	    Object.defineProperty(this, _getDescription$1, {
	      value: _getDescription2$1
	    });
	    Object.defineProperty(this, _getButton$1, {
	      value: _getButton2$1
	    });
	    Object.defineProperty(this, _getBodyTitle$1, {
	      value: _getBodyTitle2$1
	    });
	    Object.defineProperty(this, _getTitle$1, {
	      value: _getTitle2$1
	    });
	    Object.defineProperty(this, _getFooterContent$1, {
	      value: _getFooterContent2$1
	    });
	    Object.defineProperty(this, _getBubble$1, {
	      value: _getBubble2$1
	    });
	    Object.defineProperty(this, _getBodyContentWithClients$1, {
	      value: _getBodyContentWithClients2$1
	    });
	    Object.defineProperty(this, _getBodyContent, {
	      value: _getBodyContent2
	    });
	    Object.defineProperty(this, _isFlowStarted$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _showSettingsButton$2, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _hasClients$1, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$2)[_showSettingsButton$2] = (_params$showSettingsB = params.showSettingsButton) != null ? _params$showSettingsB : true;
	  }
	  getType() {
	    return WidgetType.start;
	  }
	  onClose() {
	    super.onClose();
	    void main_core.ajax.runAction('crm.repeatsale.widget.incrementShowedFlowStartCount');
	  }
	  getPopupContent(data = null) {
	    if (main_core.Type.isObject(data)) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1] === null) {
	        const {
	          isFlowStarted
	        } = data;
	        babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1] = isFlowStarted;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1] = babelHelpers.classPrivateFieldLooseBase(this, _isHasClients$1)[_isHasClients$1](data);
	    }
	    return main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div>
				<header class="crm-rs__w-header">
					${0}
				</header>
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getTitle$1)[_getTitle$1](), babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1] ? babelHelpers.classPrivateFieldLooseBase(this, _getBodyContentWithClients$1)[_getBodyContentWithClients$1]() : babelHelpers.classPrivateFieldLooseBase(this, _getBodyContent)[_getBodyContent](), babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1] ? babelHelpers.classPrivateFieldLooseBase(this, _getFooterContent$1)[_getFooterContent$1]() : null);
	  }
	  getFetchUrl() {
	    return 'crm.repeatsale.start.getData';
	  }
	  getFetchParams() {
	    return {};
	  }
	  onFirstShow() {
	    const type = this.getAnalyticsType();
	    const subSection = this.getAnalyticsSubSection();
	    babelHelpers.classPrivateFieldLooseBase(this, _sendShowAnalytics$1)[_sendShowAnalytics$1](type, subSection);
	  }
	  getAnalyticsType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1] ? crm_integration_analytics.Dictionary.TYPE_REPEAT_SALE_BANNER_START_EMPTY : crm_integration_analytics.Dictionary.TYPE_REPEAT_SALE_BANNER_START;
	  }
	}
	function _getBodyContent2() {
	  return main_core.Tag.render(_t2$3 || (_t2$3 = _$3`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content">
					<div class="crm-rs__w-body-title">
						${0}
					</div>
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getBodyTitle$1)[_getBodyTitle$1](), babelHelpers.classPrivateFieldLooseBase(this, _getDescription$1)[_getDescription$1](), babelHelpers.classPrivateFieldLooseBase(this, _getBubble$1)[_getBubble$1]());
	}
	function _getBodyContentWithClients2$1() {
	  return main_core.Tag.render(_t3$2 || (_t3$2 = _$3`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content --has-clients">
					<div class="crm-rs__w-body-title">
						${0}
					</div>
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getBodyTitle$1)[_getBodyTitle$1](), babelHelpers.classPrivateFieldLooseBase(this, _getButton$1)[_getButton$1](), babelHelpers.classPrivateFieldLooseBase(this, _getBubble$1)[_getBubble$1]());
	}
	function _getBubble2$1() {
	  const hasClients = babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1];
	  return main_core.Tag.render(_t4$1 || (_t4$1 = _$3`
			<div class="crm-rs__w-body-bubble ${0} ${0}">
				${0}
				<div class="crm-rs__w-body-icon"></div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1] ? '--flow-started' : '', hasClients ? '--has-clients' : '', this.renderLottieAnimation());
	}
	function _getFooterContent2$1() {
	  return main_core.Tag.render(_t5$1 || (_t5$1 = _$3`
			<footer class="crm-rs__w-footer">
				${0}
			</footer>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getDescription$1)[_getDescription$1]());
	}
	function _getTitle2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1]) {
	    return main_core.Tag.render(_t6$1 || (_t6$1 = _$3`
				<span>${0}</span>
			`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_FLOW_STARTED_POPUP_TITLE'));
	  }
	  return main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_TITLE');
	}
	function _getBodyTitle2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1]) {
	    return main_core.Tag.render(_t7$1 || (_t7$1 = _$3`
				<span>${0}</span>
			`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_FLOW_STARTED_TITLE'));
	  }
	  const code = babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1] ? 'CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE_WITH_CLIENTS' : 'CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE_WITHOUT_CLIENTS';
	  return main_core.Loc.getMessage(code);
	}
	function _getButton2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1]) {
	    return null;
	  }
	  return main_core.Tag.render(_t8$1 || (_t8$1 = _$3`
			<div class="crm-rs__w-body-title-btn --has-clients">
				<span
					onclick="${0}"
				>${0}</span>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onButtonClick$1)[_onButtonClick$1].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN'));
	}
	function _getDescription2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1]) {
	    const footer = new Footer(babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$2)[_showSettingsButton$2], {
	      type: this.getAnalyticsType(),
	      subSection: this.getAnalyticsSubSection()
	    });
	    return main_core.Tag.render(_t9$1 || (_t9$1 = _$3`
				<div class="crm-rs__w-buttons-wrapper">
					${0}
				</div>
			`), footer.getFooterContent());
	  }
	  const hasClients = babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1];
	  const code = hasClients ? 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_CLIENTS' : 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_CLIENTS';
	  const content = main_core.Loc.getMessage(code);
	  return main_core.Tag.render(_t10 || (_t10 = _$3`
			<div class="crm-rs__w-body-description ${0}">
				${0}
				<div class="crm-rs__w-body-description-text ${0}">
					${0}
				</div>
				<div class="crm-rs__w-body-description-btn">
					<span
						onclick="${0}"
					>${0}</span>
				</div>
			</div>
		`), hasClients ? '--has-clients' : '', hasClients ? null : '<div class="crm-rs__w-body-description-border"></div>', hasClients ? '--has-clients' : '', content, babelHelpers.classPrivateFieldLooseBase(this, _onReadMoreButtonClick$1)[_onReadMoreButtonClick$1].bind(this), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_READ_MORE'));
	}
	function _onButtonClick2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasClients$1)[_hasClients$1]) {
	    main_core.ajax.runAction('crm.repeatsale.flow.enable').then(response => {
	      if (response.status === 'success') {
	        babelHelpers.classPrivateFieldLooseBase(this, _isFlowStarted$1)[_isFlowStarted$1] = true;
	        this.setPopupContent(this.getPopupContent());
	        const instance = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder$2)[_getClickEventBuilder$2]();
	        instance.setElement('start_flow');
	        ui_analytics.sendData(instance.buildData());
	        return;
	      }
	      this.showError();
	      this.close();
	    }, response => {
	      this.showError();
	      this.close();
	    }).catch(response => {
	      this.showError();
	      this.close();
	    });
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _showReadMore$1)[_showReadMore$1]();
	  }
	}
	function _onReadMoreButtonClick2$1() {
	  const instance = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder$2)[_getClickEventBuilder$2]();
	  instance.setElement('info_button');
	  ui_analytics.sendData(instance.buildData());
	  babelHelpers.classPrivateFieldLooseBase(this, _showReadMore$1)[_showReadMore$1]();
	}
	function _showReadMore2$1() {
	  var _top$BX, _top$BX$Helper;
	  (_top$BX = top.BX) == null ? void 0 : (_top$BX$Helper = _top$BX.Helper) == null ? void 0 : _top$BX$Helper.show('redirect=detail&code=25376986');
	}
	function _isHasClients2$1(data) {
	  return data.count > 0;
	}
	function _getClickEventBuilder2$2() {
	  const type = this.getAnalyticsType();
	  const subSection = this.getAnalyticsSubSection();
	  return crm_integration_analytics.Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}
	function _sendShowAnalytics2$1(type, subSection) {
	  const instance = crm_integration_analytics.Builder.RepeatSale.Banner.ViewEvent.createDefault(type, subSection);
	  ui_analytics.sendData(instance.buildData());
	}

	let _$4 = t => t,
	  _t$4,
	  _t2$4,
	  _t3$3,
	  _t4$2,
	  _t5$2,
	  _t6$2,
	  _t7$2;
	const UserOptions = main_core.Reflection.namespace('BX.userOptions');
	var _periodType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("periodType");
	var _showSettingsButton$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSettingsButton");
	var _hint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hint");
	var _getLoadingPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoadingPopupContent");
	var _renderLoadingLottieAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLoadingLottieAnimation");
	var _getPopupTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupTitle");
	var _getSelectorTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorTitle");
	var _getFooterContent$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFooterContent");
	var _onPeriodChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPeriodChange");
	var _savePeriodTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("savePeriodTypeId");
	var _showHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showHint");
	var _hideHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideHint");
	var _getHintInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHintInstance");
	var _getClickEventBuilder$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClickEventBuilder");
	class Statistics extends Base {
	  constructor(params) {
	    var _params$showSettingsB, _params$periodTypeId;
	    super(params);
	    Object.defineProperty(this, _getClickEventBuilder$3, {
	      value: _getClickEventBuilder2$3
	    });
	    Object.defineProperty(this, _getHintInstance, {
	      value: _getHintInstance2
	    });
	    Object.defineProperty(this, _hideHint, {
	      value: _hideHint2
	    });
	    Object.defineProperty(this, _showHint, {
	      value: _showHint2
	    });
	    Object.defineProperty(this, _savePeriodTypeId, {
	      value: _savePeriodTypeId2
	    });
	    Object.defineProperty(this, _onPeriodChange, {
	      value: _onPeriodChange2
	    });
	    Object.defineProperty(this, _getFooterContent$2, {
	      value: _getFooterContent2$2
	    });
	    Object.defineProperty(this, _getSelectorTitle, {
	      value: _getSelectorTitle2
	    });
	    Object.defineProperty(this, _getPopupTitle, {
	      value: _getPopupTitle2
	    });
	    Object.defineProperty(this, _renderLoadingLottieAnimation, {
	      value: _renderLoadingLottieAnimation2
	    });
	    Object.defineProperty(this, _getLoadingPopupContent, {
	      value: _getLoadingPopupContent2
	    });
	    Object.defineProperty(this, _periodType, {
	      writable: true,
	      value: PeriodType.day30
	    });
	    Object.defineProperty(this, _showSettingsButton$3, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _hint, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$3)[_showSettingsButton$3] = (_params$showSettingsB = params.showSettingsButton) != null ? _params$showSettingsB : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _periodType)[_periodType] = (_params$periodTypeId = params.periodTypeId) != null ? _params$periodTypeId : PeriodType.day30;
	  }
	  getType() {
	    return WidgetType.statistics;
	  }
	  getPopupWidth() {
	    return 489;
	  }
	  isAutoHidePopup() {
	    return true;
	  }
	  getPopupContent(data = null) {
	    var _data$repeatSaleProce, _data$repeatSaleProce2, _data$repeatSaleWinCo, _data$repeatSaleWinSu;
	    return main_core.Tag.render(_t$4 || (_t$4 = _$4`
			<div>
				<header class="crm-rs__w-header --statistics">
					${0}
				</header>
				<div class="crm-rs__w-body">
					<div class="crm-rs__w-body-content --statistics">
						<div class="crm-rs__w-body-statistics-table-container">
							<table class="crm-rs__w-body-statistics-table">
								<thead>
									<tr>
										<th></th>
										<th>${0}</th>
										<th>${0}</th>
									</tr>
								</thead>
								<tbody class="crm-rs__w-body-statistics-table-body">
									<tr>
										<td><span>${0}</span></td>
										<td><span>${0}</span></td>
										<td><span>${0}</span></td>
									</tr>
									<tr>
										<td>${0}</td>
										<td>${0}</td>
										<td>${0}</td>
									</tr>
								</tbody>
								<tfoot class="crm-rs__w-body-statistics-table-footer">
									<tr>
										<td>
											<div>
												${0}
												<span 
													class="crm-rs__w-body-statistics-hint"
													onmouseenter="${0}"
													onmouseleave="${0}"
												></span>
											</div>
										</td>
										<td><span>${0}${0}</span></td>
										<td>${0}${0}</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
				<footer class="crm-rs__w-footer --statistics">
					${0}
				</footer>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getPopupTitle)[_getPopupTitle](data), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_COUNT'), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_SUM'), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_DEALS_IN_WORK'), (_data$repeatSaleProce = data.repeatSaleProcessCount) != null ? _data$repeatSaleProce : 0, (_data$repeatSaleProce2 = data.repeatSaleProcessSum) != null ? _data$repeatSaleProce2 : 0, main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_WIN_DEALS'), (_data$repeatSaleWinCo = data.repeatSaleWinCount) != null ? _data$repeatSaleWinCo : 0, (_data$repeatSaleWinSu = data.repeatSaleWinSum) != null ? _data$repeatSaleWinSu : 0, main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_CONVERSION'), babelHelpers.classPrivateFieldLooseBase(this, _showHint)[_showHint].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _hideHint)[_hideHint].bind(this), data.conversionByCount, data.conversionByCount > 0 ? '%' : '', data.conversionBySum, data.conversionBySum > 0 ? '%' : '', babelHelpers.classPrivateFieldLooseBase(this, _getFooterContent$2)[_getFooterContent$2]());
	  }
	  getAnalyticsType() {
	    return crm_integration_analytics.Dictionary.TYPE_REPEAT_SALE_BANNER_STATISTICS;
	  }
	  getFetchUrl() {
	    return 'crm.repeatsale.statistics.getData';
	  }
	  getFetchParams() {
	    return {
	      periodType: babelHelpers.classPrivateFieldLooseBase(this, _periodType)[_periodType]
	    };
	  }
	}
	function _getLoadingPopupContent2() {
	  return main_core.Tag.render(_t2$4 || (_t2$4 = _$4`
			<div>
				<header class="crm-rs__w-header --statistics">
					${0}
				</header>
				<div class="crm-rs__w-body">
					<div class="crm-rs__w-body-loading-bubble">
						<div class="crm-rs__w-body-loading-bubble-wrapper">
							${0}
							${0}
						</div>
						<div class="crm-rs__w-body-bubble-subtitle">
							${0}
						</div>
					</div>
				</div>
				<footer class="crm-rs__w-footer --statistics">
					${0}
				</footer>
			</div>
		`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE'), this.renderLottieAnimation(), babelHelpers.classPrivateFieldLooseBase(this, _renderLoadingLottieAnimation)[_renderLoadingLottieAnimation](), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_LOADING'), babelHelpers.classPrivateFieldLooseBase(this, _getFooterContent$2)[_getFooterContent$2]());
	}
	function _renderLoadingLottieAnimation2() {
	  const container = main_core.Tag.render(_t3$3 || (_t3$3 = _$4`
			<div class="crm-rs__w-loading-lottie-container">
				<div ref="lottie" class="crm-rs__w-lottie"></div>
			</div>
		`));
	  const mainAnimation = ui_lottie.Lottie.loadAnimation({
	    path: '/bitrix/js/crm/repeat-sale/widget/lottie/loading.json',
	    container: container.lottie,
	    renderer: 'svg',
	    loop: true,
	    autoplay: true
	  });
	  mainAnimation.setSpeed(0.75);
	  return container.root;
	}
	function _getPopupTitle2(data) {
	  var _data$repeatSaleTotal, _data$repeatSaleTotal2, _data$repeatSaleToday;
	  const repeatSaleForPeriodText = main_core.Tag.render(_t4$2 || (_t4$2 = _$4`
			<span>
				${0}
			</span>
		`), main_core.Loc.getMessagePlural('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TOTAL_DEALS', (_data$repeatSaleTotal = data.repeatSaleTotalCount) != null ? _data$repeatSaleTotal : 0, {
	    '#COUNT#': (_data$repeatSaleTotal2 = data.repeatSaleTotalCount) != null ? _data$repeatSaleTotal2 : 0
	  }));
	  const repeatSaleTodayCount = (_data$repeatSaleToday = data.repeatSaleTodayCount) != null ? _data$repeatSaleToday : 0;
	  let repeatSaleTodayText = null;
	  if (repeatSaleTodayCount > 0) {
	    var _data$repeatSaleToday2, _data$repeatSaleToday3;
	    repeatSaleTodayText = main_core.Tag.render(_t5$2 || (_t5$2 = _$4`
				<span>
					${0}
				</span>
			`), main_core.Loc.getMessagePlural('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TODAY_DEALS', (_data$repeatSaleToday2 = data.repeatSaleTodayCount) != null ? _data$repeatSaleToday2 : 0, {
	      '#COUNT#': (_data$repeatSaleToday3 = data.repeatSaleTodayCount) != null ? _data$repeatSaleToday3 : 0
	    }));
	  } else {
	    const todayNoDealsMessage = main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TODAY_NO_DEALS');
	    if (todayNoDealsMessage) {
	      repeatSaleTodayText = main_core.Tag.render(_t6$2 || (_t6$2 = _$4`<span>${0}</span>`), todayNoDealsMessage);
	    }
	  }
	  return main_core.Tag.render(_t7$2 || (_t7$2 = _$4`
			<div>
				${0}
				<div class="crm-rs__w-header-span-wrapper">
					${0}
					${0}
				</div>
			</div>
			<div 
				class="crm-rs__w-period-selector"
				onclick="${0}"
			>
				${0}
				<span class="crm-rs__w-period-selector-icon"></span>
			</div>
		`), main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE'), repeatSaleForPeriodText, repeatSaleTodayText, babelHelpers.classPrivateFieldLooseBase(this, _onPeriodChange)[_onPeriodChange].bind(this, babelHelpers.classPrivateFieldLooseBase(this, _periodType)[_periodType]), babelHelpers.classPrivateFieldLooseBase(this, _getSelectorTitle)[_getSelectorTitle]());
	}
	function _getSelectorTitle2() {
	  let code = null;
	  const periodType = Number(babelHelpers.classPrivateFieldLooseBase(this, _periodType)[_periodType]);
	  switch (periodType) {
	    case PeriodType.day30:
	      code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_DAY_30';
	      break;
	    case PeriodType.quarter:
	      code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_QUARTER';
	      break;
	    case PeriodType.halfYear:
	      code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_HALF_YEAR';
	      break;
	    case PeriodType.year:
	      code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_YEAR';
	      break;
	    default:
	      throw new RangeError(`Unknown period type: ${periodType}`);
	  }
	  return main_core.Loc.getMessage(code);
	}
	function _getFooterContent2$2() {
	  const footer = new Footer(babelHelpers.classPrivateFieldLooseBase(this, _showSettingsButton$3)[_showSettingsButton$3], {
	    type: this.getAnalyticsType(),
	    subSection: this.getAnalyticsSubSection()
	  });
	  return footer.getFooterContent();
	}
	function _onPeriodChange2(periodTypeId) {
	  let nextPeriodTypeId = PeriodType.day30;
	  const periodTypeIds = Object.values(PeriodType);
	  if (periodTypeIds.includes(periodTypeId)) {
	    const index = periodTypeIds.indexOf(periodTypeId);
	    if (index + 1 < periodTypeIds.length) {
	      nextPeriodTypeId = index + 1;
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _savePeriodTypeId)[_savePeriodTypeId](nextPeriodTypeId);
	  const data = {
	    periodTypeId: nextPeriodTypeId
	  };
	  const eventBuilder = babelHelpers.classPrivateFieldLooseBase(this, _getClickEventBuilder$3)[_getClickEventBuilder$3]();
	  eventBuilder.setElement('change_period');
	  eventBuilder.setPeriod(nextPeriodTypeId);
	  ui_analytics.sendData(eventBuilder.buildData());

	  // @todo maybe pointless loader
	  // const popup = PopupManager.getPopupById(`crm_repeat_sale_widget_${this.getType()}`);
	  // if (popup)
	  // {
	  // 	popup.setContent(this.#getLoadingPopupContent());
	  // }

	  main_core.ajax.runAction(this.getFetchUrl(), {
	    data
	  }).then(response => {
	    if (response.status === 'success') {
	      const popup = main_popup.PopupManager.getPopupById(`crm_repeat_sale_widget_${this.getType()}`);
	      if (popup === null) {
	        return;
	      }
	      this.data = response.data;
	      babelHelpers.classPrivateFieldLooseBase(this, _periodType)[_periodType] = nextPeriodTypeId;
	      popup.setContent(this.getPopupContent(this.data));
	      return;
	    }
	    this.showError();
	  }, response => {
	    //popup.setContent(this.getPopupContent(this.data));
	    this.showError();
	  }).catch(response => {
	    //popup.setContent(this.getPopupContent(this.data));
	    this.showError();
	  });
	}
	function _savePeriodTypeId2(periodTypeId) {
	  UserOptions.save('crm', 'repeat-sale', 'statistics-period-type-id', periodTypeId);
	}
	function _showHint2(event) {
	  var _babelHelpers$classPr;
	  if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getHintInstance)[_getHintInstance]().popup) != null && _babelHelpers$classPr.isShown()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getHintInstance)[_getHintInstance]().show(event.target, main_core.Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_CONVERSION_HINT'), true);
	}
	function _hideHint2() {
	  var _babelHelpers$classPr2;
	  if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getHintInstance)[_getHintInstance]().popup) != null && _babelHelpers$classPr2.isShown()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getHintInstance)[_getHintInstance]().hide();
	  }
	}
	function _getHintInstance2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hint)[_hint] === null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hint)[_hint] = BX.UI.Hint.createInstance({
	      popupParameters: {
	        autoHide: true,
	        events: {
	          onFirstShow: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _hint)[_hint].popup.setOffset({
	              offsetLeft: 9
	            });
	          }
	        }
	      }
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _hint)[_hint];
	}
	function _getClickEventBuilder2$3() {
	  const type = this.getAnalyticsType();
	  const subSection = this.getAnalyticsSubSection();
	  return crm_integration_analytics.Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}

	class ContentFactory {
	  static getContentInstance(widgetType, params = {}) {
	    switch (widgetType) {
	      case WidgetType.start:
	        return new Start(params);
	      case WidgetType.forceStart:
	        return new ForceStart(params);
	      case WidgetType.statistics:
	        return new Statistics(params);
	      default:
	        return null;
	    }
	  }
	}

	const PeriodType = Object.freeze({
	  day30: 0,
	  quarter: 1,
	  halfYear: 2,
	  year: 3
	});
	const WidgetType = Object.freeze({
	  start: 'start',
	  forceStart: 'forceStart',
	  statistics: 'statistics'
	});
	var _contentPopupInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentPopupInstance");
	class Widget {
	  static execute(widgetType, bindElement = null, params = {}, event = null, onCloseCallback = null) {
	    if (!this.instance[widgetType]) {
	      this.instance[widgetType] = new Widget(widgetType, bindElement, params);
	    }
	    if (this.instance[widgetType].isShown()) {
	      this.instance[widgetType].close();
	    } else {
	      var _ref;
	      const forceShowConfetti = (_ref = (event == null ? void 0 : event.altKey) && (event == null ? void 0 : event.ctrlKey)) != null ? _ref : false;
	      this.instance[widgetType].show(forceShowConfetti, onCloseCallback);
	    }
	  }
	  constructor(widgetType, bindElement = null, params = {}) {
	    Object.defineProperty(this, _contentPopupInstance, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _contentPopupInstance)[_contentPopupInstance] = ContentFactory.getContentInstance(widgetType, params);
	    if (bindElement) {
	      babelHelpers.classPrivateFieldLooseBase(this, _contentPopupInstance)[_contentPopupInstance].setBindElement(bindElement);
	    }
	  }
	  show(forceShowConfetti = false, onCloseCallback = null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _contentPopupInstance)[_contentPopupInstance].show(forceShowConfetti, onCloseCallback);
	  }
	  isShown() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _contentPopupInstance)[_contentPopupInstance].isShown();
	  }
	  close() {
	    babelHelpers.classPrivateFieldLooseBase(this, _contentPopupInstance)[_contentPopupInstance].close();
	  }
	}
	Widget.instance = [];

	exports.PeriodType = PeriodType;
	exports.WidgetType = WidgetType;
	exports.Widget = Widget;

}((this.BX.Crm.RepeatSale = this.BX.Crm.RepeatSale || {}),BX.UI,BX,BX.Crm.Timeline,BX.UI.Feedback,BX.Crm.Integration.Analytics,BX,BX.Main,BX.UI.Analytics,BX.UI));
//# sourceMappingURL=widget.bundle.js.map

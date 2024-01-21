/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,ui_tour,spotlight,main_popup) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onboardingData = /*#__PURE__*/new WeakMap();
	var _contentContainer = /*#__PURE__*/new WeakMap();
	var _serviceUrl = /*#__PURE__*/new WeakMap();
	var _dealDetailManager = /*#__PURE__*/new WeakMap();
	var _activeDocumentGuide = /*#__PURE__*/new WeakMap();
	var _getContentContainer = /*#__PURE__*/new WeakSet();
	var _getButtonsContainer = /*#__PURE__*/new WeakSet();
	var _isHintCanBeShown = /*#__PURE__*/new WeakSet();
	var _processProductTabHint = /*#__PURE__*/new WeakSet();
	var _createHintToProductTab = /*#__PURE__*/new WeakSet();
	var _hintToVisibleProductTab = /*#__PURE__*/new WeakSet();
	var _hintToHiddenProductTab = /*#__PURE__*/new WeakSet();
	var _hintProductListField = /*#__PURE__*/new WeakSet();
	var _hintAddDocumentLink = /*#__PURE__*/new WeakSet();
	var _hintSuccessDealDocumentInTimeline = /*#__PURE__*/new WeakSet();
	var _createHintToSuccessDocument = /*#__PURE__*/new WeakSet();
	var DealOnboardingManager = /*#__PURE__*/function () {
	  babelHelpers.createClass(DealOnboardingManager, null, [{
	    key: "productsTabId",
	    get: function get() {
	      return 'tab_products';
	    }
	  }]);
	  function DealOnboardingManager(params) {
	    babelHelpers.classCallCheck(this, DealOnboardingManager);
	    _classPrivateMethodInitSpec(this, _createHintToSuccessDocument);
	    _classPrivateMethodInitSpec(this, _hintSuccessDealDocumentInTimeline);
	    _classPrivateMethodInitSpec(this, _hintAddDocumentLink);
	    _classPrivateMethodInitSpec(this, _hintProductListField);
	    _classPrivateMethodInitSpec(this, _hintToHiddenProductTab);
	    _classPrivateMethodInitSpec(this, _hintToVisibleProductTab);
	    _classPrivateMethodInitSpec(this, _createHintToProductTab);
	    _classPrivateMethodInitSpec(this, _processProductTabHint);
	    _classPrivateMethodInitSpec(this, _isHintCanBeShown);
	    _classPrivateMethodInitSpec(this, _getButtonsContainer);
	    _classPrivateMethodInitSpec(this, _getContentContainer);
	    _classPrivateFieldInitSpec(this, _onboardingData, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _contentContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _serviceUrl, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _dealDetailManager, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _activeDocumentGuide, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _onboardingData, params.onboardingData);
	    babelHelpers.classPrivateFieldSet(this, _contentContainer, params.contentContainer);
	    babelHelpers.classPrivateFieldSet(this, _serviceUrl, params.serviceUrl);
	    babelHelpers.classPrivateFieldSet(this, _dealDetailManager, params.dealDetailManager);
	  }
	  babelHelpers.createClass(DealOnboardingManager, [{
	    key: "processOnboarding",
	    value: function processOnboarding() {
	      if (!_classPrivateMethodGet(this, _isHintCanBeShown, _isHintCanBeShown2).call(this)) {
	        return;
	      }
	      var chain = babelHelpers.classPrivateFieldGet(this, _onboardingData).chain;
	      var step = babelHelpers.classPrivateFieldGet(this, _onboardingData).chainStep;
	      var successDealGuideIsOver = babelHelpers.classPrivateFieldGet(this, _onboardingData).successDealGuideIsOver;
	      if (chain === 0) {
	        if (step < 1) {
	          _classPrivateMethodGet(this, _processProductTabHint, _processProductTabHint2).call(this);
	        }
	        if (step < 2) {
	          _classPrivateMethodGet(this, _hintProductListField, _hintProductListField2).call(this);
	        }
	      } else if (chain === 1 && step === 0) {
	        _classPrivateMethodGet(this, _hintAddDocumentLink, _hintAddDocumentLink2).call(this);
	      }
	      if (!successDealGuideIsOver) {
	        _classPrivateMethodGet(this, _hintSuccessDealDocumentInTimeline, _hintSuccessDealDocumentInTimeline2).call(this);
	      }
	    }
	  }]);
	  return DealOnboardingManager;
	}();
	function _getContentContainer2() {
	  return babelHelpers.classPrivateFieldGet(this, _contentContainer);
	}
	function _getButtonsContainer2() {
	  return _classPrivateMethodGet(this, _getContentContainer, _getContentContainer2).call(this).querySelector('.main-buttons');
	}
	function _isHintCanBeShown2() {
	  if (main_popup.PopupWindowManager && main_popup.PopupWindowManager.isAnyPopupShown()) {
	    return false;
	  }
	  return true;
	}
	function _processProductTabHint2() {
	  var guideText = {
	    title: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE'),
	    text: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT')
	  };
	  if (babelHelpers.classPrivateFieldGet(this, _dealDetailManager).isTabButtonVisible(DealOnboardingManager.productsTabId)) {
	    _classPrivateMethodGet(this, _hintToVisibleProductTab, _hintToVisibleProductTab2).call(this);
	  } else {
	    _classPrivateMethodGet(this, _hintToHiddenProductTab, _hintToHiddenProductTab2).call(this);
	  }
	}
	function _createHintToProductTab2(target) {
	  var guideEvents = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	  var guideText = {
	    title: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE'),
	    text: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT')
	  };
	  return new ui_tour.Guide({
	    steps: [{
	      target: target,
	      title: guideText.title,
	      text: guideText.text,
	      position: 'bottom',
	      events: guideEvents
	    }],
	    onEvents: true
	  });
	}
	function _hintToVisibleProductTab2() {
	  var _this = this;
	  var productsTabButton = babelHelpers.classPrivateFieldGet(this, _dealDetailManager).getTabMenuItemContainer(DealOnboardingManager.productsTabId);
	  var productsTabGuide = _classPrivateMethodGet(this, _createHintToProductTab, _createHintToProductTab2).call(this, productsTabButton, {
	    onClose: function onClose() {
	      main_core.userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);
	    }
	  });
	  productsTabGuide.showNextStep();
	  var tabsContainer = babelHelpers.classPrivateFieldGet(this, _dealDetailManager).getTabManager().getTabMenuContainer();
	  var windowResizeHandler = function windowResizeHandler() {
	    if (!babelHelpers.classPrivateFieldGet(_this, _dealDetailManager).isTabButtonVisible(DealOnboardingManager.productsTabId)) {
	      productsTabGuide.close();
	      main_core.Event.unbind(window, 'resize', windowResizeHandler);
	    }
	  };
	  main_core.Event.bind(window, 'resize', windowResizeHandler);
	  main_core.Event.bindOnce(tabsContainer, 'mousedown', function () {
	    productsTabGuide.close();
	    main_core.Event.unbind(window, 'resize', windowResizeHandler);
	  });
	}
	function _hintToHiddenProductTab2() {
	  var _this2 = this;
	  var moreButton = babelHelpers.classPrivateFieldGet(this, _dealDetailManager).getTabManager().getMoreButton();
	  var spotlight$$1 = new BX.SpotLight({
	    id: "".concat(DealOnboardingManager.productsTabId, "_spotlight"),
	    targetElement: moreButton,
	    autoSave: true,
	    targetVertex: "middle-center",
	    zIndex: 200
	  });
	  spotlight$$1.show();
	  spotlight$$1.container.style.pointerEvents = "none";
	  var onOpenMoreMenuHandler = function onOpenMoreMenuHandler(event) {
	    var eventMoreMenu = event.target.getMoreMenu();
	    var dealMoreMenu = babelHelpers.classPrivateFieldGet(_this2, _dealDetailManager).getTabManager().getMoreMenu();
	    if (eventMoreMenu === dealMoreMenu) {
	      spotlight$$1.close();
	      var productsTabGuide = _classPrivateMethodGet(_this2, _createHintToProductTab, _createHintToProductTab2).call(_this2, babelHelpers.classPrivateFieldGet(_this2, _dealDetailManager).getTabFromMoreMenu(DealOnboardingManager.productsTabId));
	      var moreMenuContainer = eventMoreMenu.getMenuContainer();
	      var tabHintTimeout = setTimeout(function () {
	        productsTabGuide.showNextStep();
	        BX.bindOnce(moreMenuContainer, 'click', moreMenuClickHandler);
	        var productsTabPopup = productsTabGuide.getPopup();
	        main_core_events.EventEmitter.subscribeOnce(productsTabPopup, 'onClose', onPopupCloseHandler);
	        var popupContainer = productsTabGuide.getPopup().getContentContainer().parentNode;
	        BX.bind(popupContainer, 'mouseenter', function () {
	          event.target.showMoreMenu();
	        });
	        BX.bind(popupContainer, 'mouseleave', function () {
	          var outOfPopupTimeout = setTimeout(function () {
	            event.target.closeMoreMenu();
	          }, 300);
	          main_core.Event.bindOnce(dealMoreMenu.getMenuContainer(), 'mouseenter', function () {
	            clearTimeout(outOfPopupTimeout);
	          });
	        });
	      }, 50);
	      var onPopupCloseHandler = function onPopupCloseHandler(event) {
	        main_core.userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);
	        main_core.Event.unbind(window, 'resize', windowResizeHandler);
	        main_core.Event.unbind(moreMenuContainer, 'click', moreMenuClickHandler);
	        main_core_events.EventEmitter.unsubscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);
	      };
	      var moreMenuClickHandler = function moreMenuClickHandler() {
	        main_core.userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);
	        productsTabGuide.close();
	      };
	      main_core.Event.bind(dealMoreMenu.getMenuContainer(), 'click', onPopupCloseHandler);
	      main_core_events.EventEmitter.subscribeOnce('BX.Main.InterfaceButtons:onMoreMenuClose', function (event) {
	        var eventMoreMenu = event.target.getMoreMenu();
	        var dealMoreMenu = babelHelpers.classPrivateFieldGet(_this2, _dealDetailManager).getTabManager().getMoreMenu();
	        if (eventMoreMenu === dealMoreMenu) {
	          clearTimeout(tabHintTimeout);
	          main_core.Event.unbind(moreMenuContainer, 'click', moreMenuClickHandler);
	          productsTabGuide.close();
	        }
	      });
	    }
	  };
	  main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);
	  var windowResizeHandler = function windowResizeHandler() {
	    if (babelHelpers.classPrivateFieldGet(_this2, _dealDetailManager).isTabButtonVisible(DealOnboardingManager.productsTabId)) {
	      spotlight$$1.close();
	      _classPrivateMethodGet(_this2, _hintToVisibleProductTab, _hintToVisibleProductTab2).call(_this2);
	      main_core.Event.unbind(window, 'resize', windowResizeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);
	    }
	  };
	  main_core.Event.bind(window, 'resize', windowResizeHandler);
	}
	function _hintProductListField2() {
	  var _this3 = this;
	  var buttonsContainer = _classPrivateMethodGet(this, _getContentContainer, _getContentContainer2).call(this).querySelector('.main-buttons');
	  var productListTabListener = function productListTabListener(event) {
	    var _event$data = babelHelpers.slicedToArray(event.data, 1),
	      productListEditor = _event$data[0];
	    var buttonsPanelListener = function buttonsPanelListener() {
	      var activeHint = productListEditor.getActiveHint();
	      if (activeHint !== null) {
	        activeHint.close();
	        main_core.Event.unbind(buttonsContainer, 'click', buttonsPanelListener);
	      }
	    };
	    main_core.Event.bind(buttonsContainer, 'click', buttonsPanelListener);
	    var productList = productListEditor.products;
	    var rowId = '';
	    if (productList instanceof Array) {
	      var firstProductRow = productList.find(function (row) {
	        return !row.getModel().isService();
	      });
	      if (firstProductRow) {
	        rowId = firstProductRow.getId();
	      }
	    }
	    if (!rowId) {
	      return;
	    }
	    productListEditor.showFieldTourHint('STORE_INFO', {
	      title: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TITLE'),
	      text: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TEXT')
	    }, function () {
	      main_core.userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 2);
	      BX.ajax.post(babelHelpers.classPrivateFieldGet(_this3, _serviceUrl), {
	        ACTION: 'FIX_FIRST_ONBOARD_CHAIN_VIEW'
	      });
	      main_core.Event.unbind(buttonsContainer, 'click', buttonsPanelListener);
	      main_core_events.EventEmitter.unsubscribe('onDemandRecalculateWrapper', productListTabListener);
	    }, ['RESERVE_INFO'], rowId);
	  };
	  main_core_events.EventEmitter.subscribe('onDemandRecalculateWrapper', productListTabListener);
	}
	function _hintAddDocumentLink2() {
	  var _this4 = this;
	  var documentsListTourListener = function documentsListTourListener(event) {
	    if (babelHelpers.classPrivateFieldGet(_this4, _activeDocumentGuide) !== null) {
	      babelHelpers.classPrivateFieldGet(_this4, _activeDocumentGuide).close();
	    }
	    var buttonsContainer = _classPrivateMethodGet(_this4, _getButtonsContainer, _getButtonsContainer2).call(_this4);
	    var sumControlContainer = document.querySelector('[data-cid="OPPORTUNITY_WITH_CURRENCY"]');
	    var addDocumentButton = sumControlContainer && sumControlContainer.querySelector('.crm-entity-widget-payment-add-box');
	    if (addDocumentButton !== null) {
	      var settingsButton = sumControlContainer.querySelector('.ui-entity-editor-block-context-menu');
	      var dragButton = sumControlContainer.querySelector('.ui-entity-editor-draggable-btn');
	      var guideText = {
	        title: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TITLE'),
	        text: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TEXT')
	      };
	      var addDocumentGuide = new ui_tour.Guide({
	        steps: [{
	          target: addDocumentButton,
	          title: guideText.title,
	          text: guideText.text,
	          events: {
	            onClose: function onClose() {
	              main_core.Event.unbind(buttonsContainer, 'click', userCloseHintHandler);
	              main_core.Event.unbind(settingsButton, 'click', userCloseHintHandler);
	              main_core.Event.unbind(dragButton, 'mousedown', userCloseHintHandler);
	            }
	          }
	        }],
	        onEvents: true
	      });
	      babelHelpers.classPrivateFieldSet(_this4, _activeDocumentGuide, addDocumentGuide);
	      var userCloseHintHandler = function userCloseHintHandler() {
	        main_core.Event.unbind(buttonsContainer, 'click', userCloseHintHandler);
	        main_core_events.EventEmitter.unsubscribe('PaymentDocuments.EntityEditor:changeDocuments', documentsListTourListener);
	        addDocumentGuide.close();
	        main_core.userOptions.save('crm', 'warehouse-onboarding', 'secondChainStage', 1);
	      };
	      addDocumentGuide.showNextStep();
	      main_core.Event.bind(addDocumentGuide.getPopup().closeIcon, 'click', userCloseHintHandler);
	      main_core.Event.bind(buttonsContainer, 'click', userCloseHintHandler);
	      main_core.Event.bind(addDocumentButton, 'click', userCloseHintHandler);
	      main_core.Event.bind(settingsButton, 'click', userCloseHintHandler);
	      main_core.Event.bind(dragButton, 'mousedown', userCloseHintHandler);
	    }
	  };
	  main_core_events.EventEmitter.subscribe('PaymentDocuments.EntityEditor:changeDocuments', documentsListTourListener);
	}
	function _hintSuccessDealDocumentInTimeline2() {
	  var _this5 = this;
	  var timelineGuideListener = function timelineGuideListener(event) {
	    if (event.data[1].currentStepId === 'WON' && event.data[1].currentSemantics === 'success') {
	      main_core_events.EventEmitter.unsubscribe('Crm.EntityProgress.Saved', timelineGuideListener);
	      var onHistoryNodeAddedHandler = function onHistoryNodeAddedHandler(event) {
	        main_core_events.EventEmitter.unsubscribe('BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded', onHistoryNodeAddedHandler);
	        BX.onCustomEvent(window, 'OpenEntityDetailTab', ['main']);
	        var _event$data2 = babelHelpers.slicedToArray(event.data, 1),
	          timelineDocsNode = _event$data2[0];
	        var previousNodePos = {
	          x: 0,
	          y: 0
	        };
	        var documentLinkNodeWatcherId = setInterval(function () {
	          var documentLinkNode = timelineDocsNode.querySelector('.crm-entity-stream-content-document-description');
	          if (documentLinkNode === null) {
	            return;
	          }
	          var nodePos = main_core.Dom.getPosition(documentLinkNode);
	          if (nodePos.x === 0 && nodePos.y === 0) {
	            return;
	          }
	          if (nodePos.x !== previousNodePos.x || nodePos.y !== previousNodePos.y) {
	            previousNodePos.x = nodePos.x;
	            previousNodePos.y = nodePos.y;
	            return;
	          }
	          clearInterval(documentLinkNodeWatcherId);
	          var successDealGuide = _classPrivateMethodGet(_this5, _createHintToSuccessDocument, _createHintToSuccessDocument2).call(_this5, documentLinkNode, {
	            onClose: function onClose() {
	              main_core.userOptions.save('crm', 'warehouse-onboarding', 'successDealGuideIsOver', true);
	              unsubscribeFromHintClicks();
	            }
	          });
	          var dealContainer = _classPrivateMethodGet(_this5, _getContentContainer, _getContentContainer2).call(_this5);
	          var buttonsContainer = _classPrivateMethodGet(_this5, _getButtonsContainer, _getButtonsContainer2).call(_this5);
	          var unsubscribeFromHintClicks = function unsubscribeFromHintClicks() {
	            main_core.Event.unbind(dealContainer, 'click', successDealGuide.close.bind(successDealGuide));
	            main_core.Event.unbind(buttonsContainer, 'click', successDealGuide.close.bind(successDealGuide));
	          };
	          window.scrollTo(0, nodePos.y - 250);
	          successDealGuide.showNextStep();
	          main_core.Event.bind(buttonsContainer, 'click', successDealGuide.close.bind(successDealGuide));
	          setTimeout(function () {
	            main_core.Event.bind(dealContainer, 'click', successDealGuide.close.bind(successDealGuide));
	          }, 3000);
	        }, 100);
	      };
	      main_core_events.EventEmitter.subscribe('BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded', onHistoryNodeAddedHandler);
	    }
	  };
	  main_core_events.EventEmitter.subscribe('Crm.EntityProgress.Saved', timelineGuideListener);
	}
	function _createHintToSuccessDocument2(target) {
	  var guideEvents = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	  var guideText = {
	    title: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TITLE'),
	    text: main_core.Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TEXT')
	  };
	  return new ui_tour.Guide({
	    steps: [{
	      target: target,
	      title: guideText.title,
	      text: guideText.text,
	      position: 'bottom',
	      events: guideEvents
	    }],
	    onEvents: true
	  });
	}

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _dealGuid = /*#__PURE__*/new WeakMap();
	var _dealDetailManager$1 = /*#__PURE__*/new WeakMap();
	var _dealOnboardingManager = /*#__PURE__*/new WeakMap();
	var _cache = /*#__PURE__*/new WeakMap();
	var DealManager = /*#__PURE__*/function () {
	  function DealManager(params) {
	    babelHelpers.classCallCheck(this, DealManager);
	    _classPrivateFieldInitSpec$1(this, _dealGuid, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _dealDetailManager$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _dealOnboardingManager, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    babelHelpers.classPrivateFieldSet(this, _dealGuid, params.guid);
	    babelHelpers.classPrivateFieldSet(this, _dealDetailManager$1, BX.Crm.EntityDetailManager.get(babelHelpers.classPrivateFieldGet(this, _dealGuid)));
	  }
	  babelHelpers.createClass(DealManager, [{
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;
	      return babelHelpers.classPrivateFieldGet(this, _cache).remember('container', function () {
	        return document.getElementById(babelHelpers.classPrivateFieldGet(_this, _dealGuid) + '_container');
	      });
	    }
	  }, {
	    key: "getDealDetailManager",
	    value: function getDealDetailManager() {
	      return babelHelpers.classPrivateFieldGet(this, _dealDetailManager$1);
	    }
	  }, {
	    key: "enableOnboardingChain",
	    value: function enableOnboardingChain(onboardingData, serviceUrl) {
	      if (babelHelpers.classPrivateFieldGet(this, _dealOnboardingManager) === null && this.getDealDetailManager() !== null) {
	        babelHelpers.classPrivateFieldSet(this, _dealOnboardingManager, new DealOnboardingManager({
	          onboardingData: onboardingData,
	          contentContainer: this.getContainer(),
	          serviceUrl: serviceUrl,
	          dealDetailManager: this.getDealDetailManager()
	        }));
	        babelHelpers.classPrivateFieldGet(this, _dealOnboardingManager).processOnboarding();
	      }
	    }
	  }]);
	  return DealManager;
	}();

	exports.DealManager = DealManager;

}((this.BX.Crm.Deal = this.BX.Crm.Deal || {}),BX,BX.Event,BX.UI.Tour,BX,BX.Main));
//# sourceMappingURL=script.js.map

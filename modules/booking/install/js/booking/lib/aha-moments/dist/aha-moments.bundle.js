/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup,spotlight,ui_tour,ui_autoLaunch,ui_bannerDispatcher,booking_core,booking_const,booking_provider_service_optionService) {
	'use strict';

	var _bookingForAhaMoment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bookingForAhaMoment");
	var _shownPopups = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shownPopups");
	var _shouldShowBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowBanner");
	var _shouldShowAddResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowAddResource");
	var _shouldShowAddClient = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowAddClient");
	var _shouldShowResourceIntersection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowResourceIntersection");
	var _shouldShowExpandGrid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowExpandGrid");
	var _shouldShowSelectResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowSelectResources");
	var _wasNotShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wasNotShown");
	var _getOptionName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptionName");
	class AhaMoments {
	  constructor() {
	    Object.defineProperty(this, _getOptionName, {
	      value: _getOptionName2
	    });
	    Object.defineProperty(this, _wasNotShown, {
	      value: _wasNotShown2
	    });
	    Object.defineProperty(this, _shouldShowSelectResources, {
	      value: _shouldShowSelectResources2
	    });
	    Object.defineProperty(this, _shouldShowExpandGrid, {
	      value: _shouldShowExpandGrid2
	    });
	    Object.defineProperty(this, _shouldShowResourceIntersection, {
	      value: _shouldShowResourceIntersection2
	    });
	    Object.defineProperty(this, _shouldShowAddClient, {
	      value: _shouldShowAddClient2
	    });
	    Object.defineProperty(this, _shouldShowAddResource, {
	      value: _shouldShowAddResource2
	    });
	    Object.defineProperty(this, _shouldShowBanner, {
	      value: _shouldShowBanner2
	    });
	    Object.defineProperty(this, _bookingForAhaMoment, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _shownPopups, {
	      writable: true,
	      value: {}
	    });
	  }
	  show(params) {
	    if (!ui_autoLaunch.AutoLauncher.isEnabled()) {
	      ui_autoLaunch.AutoLauncher.enable();
	    }
	    return new Promise(resolve => {
	      ui_bannerDispatcher.BannerDispatcher.critical.toQueue(async onDone => {
	        await this.showGuide(params);
	        onDone();
	        resolve();
	      });
	    });
	  }
	  showGuide(params) {
	    const guide = new ui_tour.Guide({
	      id: params.id,
	      overlay: false,
	      simpleMode: true,
	      onEvents: true,
	      steps: [{
	        target: params.target,
	        title: params.title,
	        text: params.text,
	        position: params.top ? 'top' : 'bottom',
	        condition: {
	          top: !params.top,
	          bottom: Boolean(params.top),
	          color: 'primary'
	        },
	        article: params.article.code,
	        articleAnchor: params.article.anchorCode
	      }],
	      targetContainer: params.targetContainer
	    });
	    const pulsar = new BX.SpotLight({
	      targetElement: params.target,
	      targetVertex: 'middle-center',
	      color: 'var(--ui-color-primary)'
	    });
	    return new Promise(resolve => {
	      const guidePopup = guide.getPopup();
	      guidePopup.setAutoHide(true);
	      guidePopup.setAngle({
	        offset: params.target.offsetWidth / 2
	      });
	      const adjustPosition = () => {
	        guidePopup.adjustPosition();
	      };
	      const onClose = () => {
	        pulsar.close();
	        main_core.Event.unbind(document, 'scroll', adjustPosition, true);
	        resolve();
	      };
	      guidePopup.subscribe('onClose', onClose);
	      guidePopup.subscribe('onDestroy', onClose);
	      pulsar.show();
	      guide.start();
	      guidePopup.adjustPosition({
	        forceTop: !params.top,
	        forceBindPosition: true
	      });
	      main_core.Event.bind(document, 'scroll', adjustPosition, true);
	    });
	  }
	  shouldShow(ahaMoment, params = {}) {
	    return {
	      [booking_const.AhaMoment.Banner]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowBanner)[_shouldShowBanner](ahaMoment),
	      [booking_const.AhaMoment.TrialBanner]: babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](ahaMoment),
	      [booking_const.AhaMoment.AddResource]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowAddResource)[_shouldShowAddResource](),
	      [booking_const.AhaMoment.MessageTemplate]: babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](ahaMoment),
	      [booking_const.AhaMoment.AddClient]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowAddClient)[_shouldShowAddClient](params),
	      [booking_const.AhaMoment.ResourceWorkload]: babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](ahaMoment),
	      [booking_const.AhaMoment.ResourceIntersection]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowResourceIntersection)[_shouldShowResourceIntersection](),
	      [booking_const.AhaMoment.ExpandGrid]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowExpandGrid)[_shouldShowExpandGrid](),
	      [booking_const.AhaMoment.SelectResources]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowSelectResources)[_shouldShowSelectResources]()
	    }[ahaMoment];
	  }
	  setShown(ahaMoment) {
	    const optionName = babelHelpers.classPrivateFieldLooseBase(this, _getOptionName)[_getOptionName](ahaMoment);
	    this.setPopupShown(ahaMoment);
	    void booking_provider_service_optionService.optionService.setBool(optionName, true);
	  }
	  setPopupShown(ahaMoment) {
	    babelHelpers.classPrivateFieldLooseBase(this, _shownPopups)[_shownPopups][ahaMoment] = true;
	  }
	  setBookingForAhaMoment(bookingId) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _bookingForAhaMoment))[_bookingForAhaMoment]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_bookingForAhaMoment] = bookingId;
	  }
	}
	function _shouldShowBanner2(ahaMoment) {
	  const canTurnOnDemo = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/canTurnOnDemo`];
	  if (canTurnOnDemo) {
	    return true;
	  } else {
	    return babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](ahaMoment);
	  }
	}
	function _shouldShowAddResource2() {
	  const wasNotShown = babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.AddResource);
	  const isLoaded = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/isLoaded`];
	  const resourcesIds = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resourcesIds`];
	  return wasNotShown && isLoaded && resourcesIds.length === 0;
	}
	function _shouldShowAddClient2(params) {
	  const wasNotShown = babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.AddClient);
	  const isBookingForAhaMoment = babelHelpers.classPrivateFieldLooseBase(this, _bookingForAhaMoment)[_bookingForAhaMoment] === params.bookingId;
	  return wasNotShown && isBookingForAhaMoment;
	}
	function _shouldShowResourceIntersection2() {
	  const wasNotShown = babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.ResourceIntersection);
	  const isLoaded = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/isLoaded`];
	  const resourcesIds = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resourcesIds`];
	  return wasNotShown && isLoaded && resourcesIds.length >= 2 && !main_popup.PopupManager.isAnyPopupShown();
	}
	function _shouldShowExpandGrid2() {
	  const wasNotShown = babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.ExpandGrid);
	  const previousAhaMomentsShown = [booking_const.AhaMoment.ResourceWorkload, booking_const.AhaMoment.ResourceWorkload].every(ahaMoment => !babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](ahaMoment));
	  return wasNotShown && previousAhaMomentsShown && !main_popup.PopupManager.isAnyPopupShown();
	}
	function _shouldShowSelectResources2() {
	  const wasNotShown = babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.SelectResources);
	  const previousAhaMomentShown = !babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](booking_const.AhaMoment.ExpandGrid);
	  return wasNotShown && previousAhaMomentShown && !main_popup.PopupManager.isAnyPopupShown();
	}
	function _wasNotShown2(ahaMoment) {
	  const {
	    ahaMoments
	  } = booking_core.Core.getParams();
	  return ahaMoments[ahaMoment] && !babelHelpers.classPrivateFieldLooseBase(this, _shownPopups)[_shownPopups][ahaMoment];
	}
	function _getOptionName2(ahaMoment) {
	  return {
	    [booking_const.AhaMoment.Banner]: booking_const.Option.AhaBanner,
	    [booking_const.AhaMoment.TrialBanner]: booking_const.Option.AhaTrialBanner,
	    [booking_const.AhaMoment.AddResource]: booking_const.Option.AhaAddResource,
	    [booking_const.AhaMoment.MessageTemplate]: booking_const.Option.AhaMessageTemplate,
	    [booking_const.AhaMoment.AddClient]: booking_const.Option.AhaAddClient,
	    [booking_const.AhaMoment.ResourceWorkload]: booking_const.Option.AhaResourceWorkload,
	    [booking_const.AhaMoment.ResourceIntersection]: booking_const.Option.AhaResourceIntersection,
	    [booking_const.AhaMoment.ExpandGrid]: booking_const.Option.AhaExpandGrid,
	    [booking_const.AhaMoment.SelectResources]: booking_const.Option.AhaSelectResources
	  }[ahaMoment];
	}
	const ahaMoments = new AhaMoments();

	exports.ahaMoments = ahaMoments;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Main,BX,BX.UI.Tour,BX.UI.AutoLaunch,BX.UI,BX.Booking,BX.Booking.Const,BX.Booking.Provider.Service));
//# sourceMappingURL=aha-moments.bundle.js.map

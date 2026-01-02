/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core,spotlight,ui_tour,tasks_v2_core,tasks_v2_const,tasks_v2_provider_service_optionService) {
	'use strict';

	var _ahaPopupWidth = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ahaPopupWidth");
	var _shownPopups = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shownPopups");
	var _shouldShowAuditorsInCard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowAuditorsInCard");
	var _wasNotShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wasNotShown");
	var _wasShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wasShown");
	class AhaMoments {
	  constructor() {
	    Object.defineProperty(this, _wasShown, {
	      value: _wasShown2
	    });
	    Object.defineProperty(this, _wasNotShown, {
	      value: _wasNotShown2
	    });
	    Object.defineProperty(this, _shouldShowAuditorsInCard, {
	      value: _shouldShowAuditorsInCard2
	    });
	    Object.defineProperty(this, _ahaPopupWidth, {
	      writable: true,
	      value: 380
	    });
	    Object.defineProperty(this, _shownPopups, {
	      writable: true,
	      value: {}
	    });
	  }
	  show(params) {
	    var _params$article, _params$article2, _params$article3;
	    if (params.ahaMoment && babelHelpers.classPrivateFieldLooseBase(this, _wasShown)[_wasShown](params.ahaMoment)) {
	      return;
	    }
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
	        article: (_params$article = params.article) == null ? void 0 : _params$article.code,
	        articleAnchor: (_params$article2 = params.article) == null ? void 0 : _params$article2.anchorCode,
	        linkTitle: (_params$article3 = params.article) == null ? void 0 : _params$article3.title
	      }],
	      targetContainer: params.targetContainer
	    });

	    // default is 280
	    if (babelHelpers.classPrivateFieldLooseBase(this, _ahaPopupWidth)[_ahaPopupWidth]) {
	      guide.getPopup().setWidth(babelHelpers.classPrivateFieldLooseBase(this, _ahaPopupWidth)[_ahaPopupWidth]);
	    }
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
	      if (params.isPulsarTransparent) {
	        main_core.Dom.style(pulsar.container, 'pointer-events', 'none');
	      }
	      main_core.Event.bind(document, 'scroll', adjustPosition, true);
	    });
	  }
	  shouldShow(ahaMoment, params = {}) {
	    return {
	      [tasks_v2_const.Option.AhaAuditorsInCompactFormPopup]: babelHelpers.classPrivateFieldLooseBase(this, _shouldShowAuditorsInCard)[_shouldShowAuditorsInCard]()
	    }[ahaMoment];
	  }
	  setShown(ahaMoment) {
	    this.setPopupShown(ahaMoment);
	    void tasks_v2_provider_service_optionService.optionService.setBool(ahaMoment, true);
	  }
	  setPopupShown(ahaMoment) {
	    babelHelpers.classPrivateFieldLooseBase(this, _shownPopups)[_shownPopups][ahaMoment] = true;
	  }
	}
	function _shouldShowAuditorsInCard2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _wasNotShown)[_wasNotShown](tasks_v2_const.Option.AhaAuditorsInCompactFormPopup);
	}
	function _wasNotShown2(ahaMoment) {
	  return !babelHelpers.classPrivateFieldLooseBase(this, _wasShown)[_wasShown](ahaMoment);
	}
	function _wasShown2(ahaMoment) {
	  const {
	    ahaMoments
	  } = tasks_v2_core.Core.getParams();
	  return !ahaMoments[ahaMoment] || babelHelpers.classPrivateFieldLooseBase(this, _shownPopups)[_shownPopups][ahaMoment];
	}
	const ahaMoments = new AhaMoments();

	exports.ahaMoments = ahaMoments;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX,BX,BX.UI.Tour,BX.Tasks.V2,BX.Tasks.V2.Const,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=aha-moments.bundle.js.map

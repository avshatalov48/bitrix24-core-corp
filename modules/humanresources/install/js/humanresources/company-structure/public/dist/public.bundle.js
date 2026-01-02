/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var _isCurrentPageStructureView = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCurrentPageStructureView");
	var _appendParamsFromOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendParamsFromOptions");
	var _handleInPlaceStructureNavigation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInPlaceStructureNavigation");
	var _openSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSlider");
	class Structure {
	  static open(options = {}) {
	    const settings = main_core.Extension.getSettings('humanresources.company-structure.public');
	    const baseUrl = settings.get('url');
	    if (!baseUrl) {
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isCurrentPageStructureView)[_isCurrentPageStructureView](baseUrl)) {
	      const url = new main_core.Uri(baseUrl);
	      babelHelpers.classPrivateFieldLooseBase(this, _appendParamsFromOptions)[_appendParamsFromOptions](url, options);
	      babelHelpers.classPrivateFieldLooseBase(this, _openSlider)[_openSlider](url);
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _handleInPlaceStructureNavigation)[_handleInPlaceStructureNavigation](options);
	  }
	}
	function _isCurrentPageStructureView2(url) {
	  return window.location.href.includes(url);
	}
	function _appendParamsFromOptions2(url, options) {
	  if (main_core.Type.isInteger(options.focusNodeId)) {
	    url.setQueryParam('focusNodeId', options.focusNodeId);
	  }
	  return url;
	}
	function _handleInPlaceStructureNavigation2(options) {
	  if (options.focusNodeId) {
	    main_core_events.EventEmitter.emit('HumanResources.CompanyStructure:focusNode', {
	      nodeId: options.focusNodeId
	    });
	  }
	}
	function _openSlider2(url) {
	  top.BX.SidePanel.Instance.open(url.toString());
	}
	Object.defineProperty(Structure, _openSlider, {
	  value: _openSlider2
	});
	Object.defineProperty(Structure, _handleInPlaceStructureNavigation, {
	  value: _handleInPlaceStructureNavigation2
	});
	Object.defineProperty(Structure, _appendParamsFromOptions, {
	  value: _appendParamsFromOptions2
	});
	Object.defineProperty(Structure, _isCurrentPageStructureView, {
	  value: _isCurrentPageStructureView2
	});

	exports.Structure = Structure;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX,BX.Event));
//# sourceMappingURL=public.bundle.js.map

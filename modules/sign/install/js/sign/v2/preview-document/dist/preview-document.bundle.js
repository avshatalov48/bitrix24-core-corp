/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,sign_v2_api,sign_v2_preview) {
	'use strict';

	var _documentId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentId");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _preview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preview");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	class PreviewDocument {
	  constructor(options) {
	    Object.defineProperty(this, _documentId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preview, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _documentId)[_documentId] = options.documentId;
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = options.container;
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview] = new sign_v2_preview.Preview();
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  async render() {
	    const pagesResponse = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getPages(babelHelpers.classPrivateFieldLooseBase(this, _documentId)[_documentId]);
	    if (main_core.Type.isObject(pagesResponse) && main_core.Type.isArrayFilled(pagesResponse.pages)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].urls = pagesResponse.pages.map(page => page.url);
	      babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].ready = true;
	    }
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  }
	}

	exports.PreviewDocument = PreviewDocument;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=preview-document.bundle.js.map

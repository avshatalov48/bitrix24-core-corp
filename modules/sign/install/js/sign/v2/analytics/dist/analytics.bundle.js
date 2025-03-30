/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,sign_type,sign_v2_api,ui_analytics) {
	'use strict';

	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	class Context {
	  constructor(options = {}) {
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  update(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _options)[_options],
	      ...options
	    };
	  }
	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options];
	  }
	}

	var _documentUidToIdCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUidToIdCache");
	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _loadDocumentIdByUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadDocumentIdByUid");
	var _sendWithProviderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendWithProviderType");
	var _convertProviderCodeToP1IntegrationType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertProviderCodeToP1IntegrationType");
	class Analytics {
	  constructor(_options = {}) {
	    var _options$contextOptio;
	    Object.defineProperty(this, _convertProviderCodeToP1IntegrationType, {
	      value: _convertProviderCodeToP1IntegrationType2
	    });
	    Object.defineProperty(this, _sendWithProviderType, {
	      value: _sendWithProviderType2
	    });
	    Object.defineProperty(this, _loadDocumentIdByUid, {
	      value: _loadDocumentIdByUid2
	    });
	    Object.defineProperty(this, _documentUidToIdCache, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = new Context((_options$contextOptio = _options.contextOptions) != null ? _options$contextOptio : {});
	  }
	  send(options) {
	    ui_analytics.sendData({
	      ...babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].getOptions(),
	      ...options,
	      tool: 'sign'
	    });
	  }
	  setContext(context) {
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = context;
	  }
	  getContext() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context)[_context];
	  }
	  sendWithProviderTypeAndDocId(options, documentUidOrId, providerCode) {
	    void babelHelpers.classPrivateFieldLooseBase(this, _sendWithProviderType)[_sendWithProviderType](options, documentUidOrId, providerCode);
	  }
	  sendWithDocId(options, documentUidOrId) {
	    if (main_core.Type.isNumber(documentUidOrId)) {
	      this.send({
	        ...options,
	        p5: `docId_${documentUidOrId}`
	      });
	      return;
	    }
	    (async () => {
	      const documentId = await babelHelpers.classPrivateFieldLooseBase(this, _loadDocumentIdByUid)[_loadDocumentIdByUid](documentUidOrId);
	      this.send({
	        ...options,
	        p5: `docId_${documentId}`
	      });
	    })();
	  }
	}
	async function _loadDocumentIdByUid2(documentUid) {
	  if (main_core.Type.isNumber(babelHelpers.classPrivateFieldLooseBase(this, _documentUidToIdCache)[_documentUidToIdCache][documentUid])) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _documentUidToIdCache)[_documentUidToIdCache][documentUid];
	  }
	  const document = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadDocument(documentUid);
	  if (document) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUidToIdCache)[_documentUidToIdCache][documentUid] = document.id;
	    return document.id;
	  }
	  return null;
	}
	async function _sendWithProviderType2(options, documentUidOrId, providerCode) {
	  var _babelHelpers$classPr;
	  let documentId = main_core.Type.isNumber(documentUidOrId) ? documentUidOrId : (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _documentUidToIdCache)[_documentUidToIdCache][documentUidOrId]) != null ? _babelHelpers$classPr : null;
	  let providerType = providerCode;
	  if (main_core.Type.isNull(documentId) || main_core.Type.isUndefined(providerCode)) {
	    const document = main_core.Type.isString(documentUidOrId) ? await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadDocument(documentUidOrId) : await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadDocumentById(documentUidOrId);
	    if (!document) {
	      console.warn('Document not found by identifier', documentUidOrId);
	      return;
	    }
	    documentId = document.id;
	    providerType = document.providerCode;
	  }
	  this.send({
	    ...options,
	    p1: babelHelpers.classPrivateFieldLooseBase(this, _convertProviderCodeToP1IntegrationType)[_convertProviderCodeToP1IntegrationType](providerType),
	    p5: `docId_${documentId}`
	  });
	}
	function _convertProviderCodeToP1IntegrationType2(providerType) {
	  switch (providerType) {
	    case sign_type.ProviderCode.sesRu:
	    case sign_type.ProviderCode.sesCom:
	      return 'integration_bitrix24KEDO';
	    case sign_type.ProviderCode.goskey:
	      return 'integration_Goskluch';
	    case sign_type.ProviderCode.external:
	      return 'integration_external';
	    default:
	      return 'integration_N';
	  }
	}

	exports.Context = Context;
	exports.Analytics = Analytics;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Sign,BX.Sign.V2,BX.UI.Analytics));
//# sourceMappingURL=analytics.bundle.js.map

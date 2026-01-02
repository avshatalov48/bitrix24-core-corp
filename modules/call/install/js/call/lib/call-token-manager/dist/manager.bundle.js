/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports) {
	'use strict';

	var _tokens = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tokens");
	var _pendingTokens = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pendingTokens");
	var _loadToken = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadToken");
	class Manager {
	  constructor(options) {
	    Object.defineProperty(this, _loadToken, {
	      value: _loadToken2
	    });
	    Object.defineProperty(this, _tokens, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _pendingTokens, {
	      writable: true,
	      value: {}
	    });
	  }
	  async getToken(chatId) {
	    const pendingToken = babelHelpers.classPrivateFieldLooseBase(this, _pendingTokens)[_pendingTokens][chatId];
	    if (pendingToken) {
	      return pendingToken;
	    }
	    const tokenPromise = new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _pendingTokens)[_pendingTokens][chatId] = tokenPromise;
	      const token = babelHelpers.classPrivateFieldLooseBase(this, _tokens)[_tokens][chatId];
	      if (token) {
	        return resolve(token);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _loadToken)[_loadToken](chatId).then(token => {
	        resolve(token);
	      });
	    });
	    return tokenPromise;
	  }
	  setToken(chatId, token) {
	    babelHelpers.classPrivateFieldLooseBase(this, _tokens)[_tokens][chatId] = token;
	  }
	}
	async function _loadToken2(chatId) {
	  try {
	    var _response$data;
	    const response = await BX.rest.callMethod('call.Call.getCallToken', {
	      chatId
	    });
	    return (_response$data = response.data()) == null ? void 0 : _response$data.token;
	  } catch (error) {
	    console.error('Error during call token retrieving', error);
	  }
	}

	exports.Manager = Manager;

}((this.BX.Call.Lib = this.BX.Call.Lib || {})));
//# sourceMappingURL=manager.bundle.js.map

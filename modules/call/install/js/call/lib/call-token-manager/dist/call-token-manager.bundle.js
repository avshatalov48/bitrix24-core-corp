/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,main_core) {
	'use strict';

	const {
	  callInstalled
	} = main_core.Extension.getSettings('call.core');
	var _tokenList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tokenList");
	var _pendingTokenList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pendingTokenList");
	var _queryParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("queryParams");
	var _userToken = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userToken");
	var _loadToken = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadToken");
	class TokenManager {
	  constructor() {
	    Object.defineProperty(this, _loadToken, {
	      value: _loadToken2
	    });
	    Object.defineProperty(this, _tokenList, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pendingTokenList, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _queryParams, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userToken, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _queryParams)[_queryParams] = {};
	    if (callInstalled) {
	      babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken] = main_core.Loc.getMessage('user_jwt');
	    }
	  }
	  setQueryParams(queryParams) {
	    if (!main_core.Type.isPlainObject(queryParams)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _queryParams)[_queryParams] = queryParams;
	  }
	  getTokenCached(chatId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList][chatId];
	  }
	  async getToken(chatId) {
	    const token = babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList][chatId];
	    const pendingToken = babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	    if (token) {
	      return token;
	    }
	    if (pendingToken) {
	      return pendingToken;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId] = new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadToken)[_loadToken](chatId).then(() => {
	        delete babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList][chatId]);
	      });
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	  }
	  setToken(chatId, token) {
	    babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList][chatId] = token;
	  }
	  async getUserToken(chatId) {
	    const pendingToken = babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken];
	    }
	    if (pendingToken) {
	      return pendingToken;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId] = new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadToken)[_loadToken](chatId).then(() => {
	        delete babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken]);
	      });
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList][chatId];
	  }
	  setUserToken(token) {
	    babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken] = token;
	  }
	  clearTokenList() {
	    babelHelpers.classPrivateFieldLooseBase(this, _tokenList)[_tokenList] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _pendingTokenList)[_pendingTokenList] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _userToken)[_userToken] = null;
	  }
	}
	async function _loadToken2(chatId) {
	  try {
	    var _response$data, _response$data2;
	    const params = {
	      chatId,
	      ...babelHelpers.classPrivateFieldLooseBase(this, _queryParams)[_queryParams]
	    };
	    const response = await BX.rest.callMethod('call.Call.getCallToken', params);
	    const callToken = (_response$data = response.data()) == null ? void 0 : _response$data.callToken;
	    const userToken = (_response$data2 = response.data()) == null ? void 0 : _response$data2.userToken;
	    if (callToken) {
	      this.setToken(chatId, callToken);
	    }
	    if (userToken) {
	      this.setUserToken(userToken);
	    }
	  } catch (error) {
	    console.error('Error during call token retrieving', error);
	  }
	}
	const CallTokenManager = new TokenManager();

	exports.CallTokenManager = CallTokenManager;

}((this.BX.Call.Lib = this.BX.Call.Lib || {}),BX));
//# sourceMappingURL=call-token-manager.bundle.js.map

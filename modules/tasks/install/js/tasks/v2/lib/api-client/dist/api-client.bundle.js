/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core) {
	'use strict';

	var _baseUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("baseUrl");
	var _contentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentType");
	class ApiClient {
	  constructor(baseUrl = 'tasks.v2.', contentType = 'json') {
	    Object.defineProperty(this, _baseUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _contentType, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _baseUrl)[_baseUrl] = baseUrl;
	    babelHelpers.classPrivateFieldLooseBase(this, _contentType)[_contentType] = contentType;
	  }
	  async get(endpoint, params = {}) {
	    const url = this.buildUrl(endpoint);
	    const response = await main_core.ajax.runAction(url, {
	      [babelHelpers.classPrivateFieldLooseBase(this, _contentType)[_contentType]]: {
	        method: 'GET',
	        ...params
	      }
	    });
	    return this.handleResponse(response);
	  }
	  async post(endpoint, data) {
	    const url = this.buildUrl(endpoint);
	    const response = await main_core.ajax.runAction(url, {
	      [babelHelpers.classPrivateFieldLooseBase(this, _contentType)[_contentType]]: data,
	      navigation: data == null ? void 0 : data.navigation
	    });
	    return this.handleResponse(response);
	  }
	  async put(endpoint, data) {
	    const url = this.buildUrl(endpoint);
	    const response = await main_core.ajax.runAction(url, {
	      method: 'PUT',
	      headers: {
	        'Content-Type': 'application/json'
	      },
	      [babelHelpers.classPrivateFieldLooseBase(this, _contentType)[_contentType]]: data
	    });
	    return this.handleResponse(response);
	  }
	  async delete(endpoint, params = {}) {
	    const url = this.buildUrl(endpoint, params);
	    const response = await main_core.ajax.runAction(url, {
	      method: 'DELETE'
	    });
	    return this.handleResponse(response);
	  }
	  buildUrl(endpoint, params = {}) {
	    let url = `${babelHelpers.classPrivateFieldLooseBase(this, _baseUrl)[_baseUrl]}${endpoint}`;
	    if (Object.keys(params).length > 0) {
	      url += `?${new URLSearchParams(params).toString()}`;
	    }
	    return url;
	  }
	  async handleResponse(response) {
	    const {
	      data,
	      error
	    } = response;
	    if (error) {
	      throw error;
	    }
	    return data;
	  }
	}
	const apiClient = new ApiClient();

	exports.ApiClient = ApiClient;
	exports.apiClient = apiClient;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX));
//# sourceMappingURL=api-client.bundle.js.map

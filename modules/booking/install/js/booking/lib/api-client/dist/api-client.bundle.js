/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core) {
	'use strict';

	class ApiClient {
	  constructor(baseUrl = 'booking.api_v1.') {
	    this.baseUrl = baseUrl;
	  }
	  async get(endpoint, params = {}) {
	    const url = this.buildUrl(endpoint);
	    const response = await main_core.ajax.runAction(url, {
	      json: {
	        method: 'GET',
	        ...params
	      }
	    });
	    return this.handleResponse(response);
	  }
	  async post(endpoint, data) {
	    const url = this.buildUrl(endpoint);
	    const response = await main_core.ajax.runAction(url, {
	      json: data
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
	      json: data
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
	    let url = `${this.baseUrl}${endpoint}`;
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

	exports.ApiClient = ApiClient;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX));
//# sourceMappingURL=api-client.bundle.js.map

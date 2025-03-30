/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_core,ui_notification,ui_analytics) {
	'use strict';

	const AnalyticsSourceType = Object.freeze({
	  HEADER: 'header',
	  CARD: 'card',
	  DETAIL: 'dept_menu',
	  PLUS: 'plus'
	});

	const memberRoles = Object.freeze({
	  employee: 'MEMBER_EMPLOYEE',
	  head: 'MEMBER_HEAD',
	  deputyHead: 'MEMBER_DEPUTY_HEAD'
	});

	const request = async (method, endPoint, data = {}, analytics = {}) => {
	  var _analytics$event;
	  const config = {
	    method
	  };
	  if (method === 'POST') {
	    Object.assign(config, {
	      data
	    }, {
	      headers: [{
	        name: 'Content-Type',
	        value: 'application/json'
	      }]
	    });
	  }
	  let response = null;
	  try {
	    if (method === 'POST') {
	      response = await main_core.ajax.runAction(endPoint, config);
	    } else {
	      const getConfig = {
	        data
	      };
	      response = await main_core.ajax.runAction(endPoint, getConfig);
	    }
	  } catch (ex) {
	    handleResponseError(ex);
	    return null;
	  }
	  if ((analytics == null ? void 0 : (_analytics$event = analytics.event) == null ? void 0 : _analytics$event.length) > 0) {
	    ui_analytics.sendData(analytics);
	  }
	  return response.data;
	};
	const handleResponseError = response => {
	  var _response$errors;
	  if (((_response$errors = response.errors) == null ? void 0 : _response$errors.length) > 0) {
	    const [error] = response.errors;
	    if (error.code !== 'STRUCTURE_ACCESS_DENIED') {
	      throw error;
	    }
	    ui_notification.UI.Notification.Center.notify({
	      content: error.message,
	      autoHideDelay: 4000
	    });
	  }
	};
	const getData = (endPoint, data, analytics) => request('GET', endPoint, data != null ? data : {}, analytics != null ? analytics : {});
	const postData = (endPoint, data, analytics) => request('POST', endPoint, data, analytics != null ? analytics : {});

	exports.getData = getData;
	exports.postData = postData;
	exports.memberRoles = memberRoles;
	exports.AnalyticsSourceType = AnalyticsSourceType;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX,BX,BX.UI.Analytics));
//# sourceMappingURL=api.bundle.js.map

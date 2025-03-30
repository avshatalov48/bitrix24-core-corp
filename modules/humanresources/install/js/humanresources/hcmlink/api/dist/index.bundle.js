/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_notification,main_core) {
	'use strict';

	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _get = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("get");
	var _post = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("post");
	var _request = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("request");
	class Api {
	  // eslint-disable-next-line no-unused-private-class-members

	  constructor(options) {
	    Object.defineProperty(this, _request, {
	      value: _request2
	    });
	    Object.defineProperty(this, _post, {
	      value: _post2
	    });
	    Object.defineProperty(this, _get, {
	      value: _get2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  saveMapping(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.save', data, true);
	  }
	  loadMapperConfig(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.load', data, true);
	  }
	  getJobStatus(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.getJobStatus', data, true);
	  }
	  loadCompanyConfig(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Company.Config.load', data, true);
	  }
	  closeInfoAlert() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.closeInfoAlert');
	  }
	  removeLinkMapped(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.delete', data, true);
	  }
	  createUpdateEmployeeListJob(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.start', data, true);
	  }
	  getLastJob(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.getLastJob', data, true);
	  }
	  cancelJob(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.cancelJob', data, true);
	  }
	  createCompleteMappingEmployeeListJob(data) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _post)[_post]('humanresources.HcmLink.Mapper.end', data, true);
	  }
	}
	function _get2(endpoint, displayErrors = true) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]('GET', endpoint, null, displayErrors);
	}
	function _post2(endpoint, data = null, displayErrors = true) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]('POST', endpoint, data, displayErrors);
	}
	async function _request2(method, endpoint, data = {}, displayError = true) {
	  const config = {
	    method
	  };
	  if (method === 'POST') {
	    Object.assign(config, {
	      data
	    }, {
	      preparePost: false,
	      headers: [{
	        name: 'Content-Type',
	        value: 'application/json'
	      }]
	    });
	  }
	  try {
	    var _response$errors;
	    const response = await main_core.ajax.runAction(endpoint, config);
	    if (((_response$errors = response.errors) == null ? void 0 : _response$errors.length) > 0) {
	      throw new Error(response.errors[0].message);
	    }
	    return response.data;
	  } catch (ex) {
	    var _errors$0$message, _errors$;
	    const {
	      message = `Error in ${endpoint}`,
	      errors = []
	    } = ex;
	    const content = (_errors$0$message = (_errors$ = errors[0]) == null ? void 0 : _errors$.message) != null ? _errors$0$message : message;
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(content),
	      autoHideDelay: 4000
	    });
	    throw ex;
	  }
	}

	exports.Api = Api;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX,BX));
//# sourceMappingURL=index.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,sign_error) {
	'use strict';

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }
	  babelHelpers.createClass(Backend, null, [{
	    key: "isErrorOccurred",
	    /**
	     * Check ajax response and generate error, if exists.
	     * @param {Object} sourceResponse
	     * @return {boolean}
	     */
	    value: function isErrorOccurred(sourceResponse) {
	      if (sourceResponse.status === 'error') {
	        if (main_core.Type.isArray(sourceResponse.errors)) {
	          sourceResponse.errors.map(function (error) {
	            if (error.code === 'invalid_csrf' && sourceResponse.data.sessid) {
	              main_core.Loc.setMessage('bitrix_sessid', sourceResponse.data.sessid);
	            }
	            sign_error.Error.getInstance().addError(error);
	          });
	        }
	        return true;
	      }
	      return false;
	    }
	    /**
	     * Sends request to Controller API and returns Promise on result.
	     * @param {ControllerType} options
	     * @return {Promise}
	     */
	  }, {
	    key: "controller",
	    value: function controller(options) {
	      var _this = this;
	      options.getData = options.getData || {};
	      options.postData = options.postData || {};
	      var command = options.command,
	        postData = options.postData,
	        getData = options.getData;
	      return new Promise(function (resolve, reject) {
	        postData.sessid = main_core.Loc.getMessage('bitrix_sessid');
	        getData.action = (options.module || 'sign') + '.api.' + command;
	        var fd = postData instanceof FormData ? postData : main_core.Http.Data.convertObjectToFormData(postData);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          timeout: options.timeout || 60,
	          url: new main_core.Uri(Backend.controllerUri).setQueryParams(getData).toString(),
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(sourceResponse) {
	            if (_this.isErrorOccurred(sourceResponse)) {
	              reject(sourceResponse);
	              return;
	            }
	            resolve(sourceResponse.data);
	          },
	          onfailure: function onfailure(sourceResponse) {
	            reject(sourceResponse);
	          }
	        });
	        xhr.send(fd);
	      });
	    }
	  }]);
	  return Backend;
	}();
	babelHelpers.defineProperty(Backend, "controllerUri", '/bitrix/services/main/ajax.php');

	exports.Backend = Backend;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX.Sign));
//# sourceMappingURL=index.bundle.js.map

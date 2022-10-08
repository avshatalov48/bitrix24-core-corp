this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	function request(options) {
	  var action = options.action.replace('crm.api.form.', '');
	  var data = main_core.Type.isPlainObject(options.data) ? options.data : {};
	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runAction("crm.api.form.".concat(action), {
	      json: data
	    }).then(function (response) {
	      resolve(response.data);
	    })["catch"](function (error) {
	      reject(error.errors);
	    });
	  });
	}

	var instance = Symbol('instance');
	/**
	 * Crm-From client
	 * Implements singleton pattern
	 *
	 * @example
	 * import {Client} from 'crm.form.client';
	 * const client = Client.getInstance();
	 *
	 * client
	 * 		.loadOptionsById(formId)
	 * 		.then((options) => {
	 * 			// ...
	 * 		});
	 *
	 * @memberOf BX.Crm.Form
	 */

	var FormClient = /*#__PURE__*/function () {
	  function FormClient() {
	    babelHelpers.classCallCheck(this, FormClient);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	  }

	  babelHelpers.createClass(FormClient, [{
	    key: "getOptions",
	    value: function getOptions(formId) {
	      return this.cache.remember("formOptions#".concat(formId), function () {
	        return request({
	          action: 'get',
	          data: {
	            id: formId
	          }
	        });
	      });
	    }
	  }, {
	    key: "getDictionary",
	    value: function getDictionary() {
	      return this.cache.remember('formDictionary', function () {
	        return request({
	          action: 'getDict'
	        });
	      });
	    } // eslint-disable-next-line class-methods-use-this

	  }, {
	    key: "prepareOptions",
	    value: function prepareOptions(options, preparing) {
	      return request({
	        action: 'prepare',
	        data: {
	          options: options,
	          preparing: preparing
	        }
	      });
	    } // eslint-disable-next-line class-methods-use-this

	  }, {
	    key: "saveOptions",
	    value: function saveOptions(options) {
	      return request({
	        action: 'save',
	        data: {
	          options: options
	        }
	      });
	    } // eslint-disable-next-line class-methods-use-this

	  }, {
	    key: "checkFields",
	    value: function checkFields(options) {
	      return request({
	        action: 'check',
	        data: {
	          options: options
	        }
	      });
	    }
	  }, {
	    key: "resetCache",
	    value: function resetCache(formId) {
	      var _this = this;

	      if (main_core.Type.isNumber(formId) || main_core.Type.isStringFilled(formId)) {
	        this.cache["delete"]("formOptions#".concat(formId));
	      } else {
	        this.cache.keys().filter(function (key) {
	          return key.startsWith('formOptions#');
	        }).forEach(function (key) {
	          _this.cache["delete"](key);
	        });
	      }
	    }
	  }, {
	    key: "check",
	    value: function check(options) {
	      return request({
	        action: 'check',
	        data: {
	          options: options
	        }
	      });
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!FormClient[instance]) {
	        FormClient[instance] = new FormClient();
	      }

	      return FormClient[instance];
	    }
	  }]);
	  return FormClient;
	}();

	exports.FormClient = FormClient;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX));
//# sourceMappingURL=client.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var Ajax = /*#__PURE__*/function () {
	  function Ajax() {
	    babelHelpers.classCallCheck(this, Ajax);
	    this.type = null;
	    this.method = null;
	    this.url = null;
	    this.callback = function () {};
	    this.failure_callback = function () {};
	    this.progress_callback = null;
	    this.loadstart_callback = null;
	    this.loadend_callback = null;
	    this.offline = null;
	    this.processData = null;
	    this.xhr = null;
	    this.data = null;
	    this.headers = null;
	    this.aborted = null;
	    this.formData = null;
	  }
	  babelHelpers.createClass(Ajax, [{
	    key: "instanceWrap",
	    value: function instanceWrap(params) {
	      var _this = this;
	      this.init(params);
	      this.xhr = main_core.ajax({
	        timeout: 30,
	        start: this.start,
	        preparePost: this.preparePost,
	        method: this.method,
	        dataType: this.type,
	        url: this.url,
	        data: this.data,
	        headers: this.headers,
	        processData: this.processData,
	        onsuccess: function onsuccess(response) {
	          var failed = false;
	          if (_this.xhr.status === 0) {
	            _this.failure_callback();
	            return;
	          } else if (_this.type == 'json') {
	            failed = main_core.Type.isPlainObject(response) && !main_core.Type.isNull(response) && main_core.Type.isStringFilled(response.status) && response.status === 'failed';
	          } else if (_this.type == 'html') {
	            failed = response === '{"status":"failed"}';
	          }
	          if (failed) {
	            if (!_this.aborted) {
	              _this.repeatRequest();
	            }
	          } else {
	            _this.callback(response);
	          }
	        },
	        onfailure: function onfailure(errorCode, requestStatus) {
	          if (main_core.Type.isStringFilled(errorCode) && errorCode === 'status' && typeof requestStatus !== 'undefined' && requestStatus == 401) {
	            _this.repeatRequest();
	          } else {
	            _this.failure_callback();
	          }
	        }
	      });
	      this.bindHandlers();
	      main_core.Event.bind(this.xhr, 'abort', function () {
	        _this.aborted = true;
	      });
	      return this.xhr;
	    }
	  }, {
	    key: "init",
	    value: function init(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      this.type = params.type !== 'json' ? 'html' : 'json';
	      this.method = params.method !== 'POST' ? 'GET' : 'POST';
	      this.url = params.url;
	      this.data = params.data;
	      this.headers = typeof params.headers !== 'undefined' ? params.headers : [];
	      this.processData = main_core.Type.isBoolean(params.processData) ? params.processData : true;
	      this.start = params.start;
	      this.preparePost = params.preparePost;
	      this.callback = params.callback;
	      if (main_core.Type.isFunction(params.callback_failure)) {
	        this.failure_callback = params.callback_failure;
	      }
	      if (main_core.Type.isFunction(params.callback_progress)) {
	        this.progress_callback = params.callback_progress;
	      }
	      if (main_core.Type.isFunction(params.callback_loadstart)) {
	        this.loadstart_callback = params.callback_loadstart;
	      }
	      if (main_core.Type.isFunction(params.callback_loadend)) {
	        this.loadend_callback = params.callback_loadend;
	      }
	      if (typeof params.formData !== 'undefined') {
	        this.formData = params.formData;
	      }
	    }
	  }, {
	    key: "instanceRunComponentAction",
	    value: function instanceRunComponentAction(component, action, config, callbacks) {
	      var _this2 = this;
	      if (!main_core.Type.isPlainObject(callbacks)) {
	        callbacks = {};
	      }
	      return new Promise(function (resolve, reject) {
	        config.onrequeststart = function (requestXhr) {
	          _this2.xhr = requestXhr;
	        };
	        main_core.ajax.runComponentAction(component, action, config).then(function (response) {
	          if (main_core.Type.isFunction(callbacks.success)) {
	            callbacks.success(response);
	          }
	          resolve(response);
	        }, function (response) {
	          if (_this2.xhr.status == 401) {
	            _this2.repeatComponentAction(component, action, config, callbacks);
	          } else {
	            if (main_core.Type.isFunction(callbacks.failure)) {
	              callbacks.failure(response);
	            }
	            reject(response);
	          }
	        });
	        _this2.bindHandlers();
	      });
	    }
	    /**
	     * @private
	     */
	  }, {
	    key: "repeatComponentAction",
	    value: function repeatComponentAction(component, action, config, callbacks) {
	      if (!main_core.Type.isPlainObject(callbacks)) {
	        callbacks = {};
	      }
	      return new Promise(function (resolve, reject) {
	        app.BasicAuth({
	          success: function success(auth_data) {
	            main_core.ajax.runComponentAction(component, action, config).then(function (response) {
	              if (main_core.Type.isFunction(callbacks.success)) {
	                callbacks.success(response);
	              }
	              resolve(response);
	            }, function (response) {
	              if (main_core.Type.isFunction(callbacks.failure)) {
	                callbacks.failure(response);
	              }
	              reject(response);
	            });
	          },
	          failture: function failture() {
	            if (main_core.Type.isFunction(callbacks.failure)) {
	              callbacks.failure();
	            }
	            reject();
	          }
	        });
	      });
	    }
	  }, {
	    key: "instanceRunAction",
	    value: function instanceRunAction(action, config, callbacks) {
	      var _this3 = this;
	      if (!main_core.Type.isPlainObject(callbacks)) {
	        callbacks = {};
	      }
	      return new Promise(function (resolve, reject) {
	        config.onrequeststart = function (requestXhr) {
	          _this3.xhr = requestXhr;
	        };
	        main_core.ajax.runAction(action, config).then(function (response) {
	          if (main_core.Type.isFunction(callbacks.success)) {
	            callbacks.success(response);
	          }
	          resolve(response);
	        }, function (response) {
	          if (_this3.xhr.status == 401) {
	            return _this3.repeatAction(action, config, callbacks);
	          } else {
	            if (main_core.Type.isFunction(callbacks.failure)) {
	              callbacks.failure(response);
	            }
	            reject(response);
	          }
	        });
	        _this3.bindHandlers();
	      });
	    }
	  }, {
	    key: "repeatAction",
	    value: function repeatAction(action, config, callbacks) {
	      if (!main_core.Type.isPlainObject(callbacks)) {
	        callbacks = {};
	      }
	      return new Promise(function (resolve, reject) {
	        app.BasicAuth({
	          success: function success(auth_data) {
	            main_core.ajax.runAction(action, config).then(function (response) {
	              if (main_core.Type.isFunction(callbacks.success)) {
	                callbacks.success(response);
	              }
	              resolve(response);
	            }, function (response) {
	              if (main_core.Type.isFunction(callbacks.failure)) {
	                callbacks.failure(response);
	              }
	              reject(response);
	            });
	          },
	          failture: function failture() {
	            if (main_core.Type.isFunction(callbacks.failure)) {
	              callbacks.failure();
	            }
	            reject();
	          }
	        });
	      });
	    }
	  }, {
	    key: "repeatRequest",
	    value: function repeatRequest() {
	      var _this4 = this;
	      app.BasicAuth({
	        success: function success(auth_data) {
	          _this4.data.sessid = auth_data.sessid_md5;
	          if (_this4.formData !== null && _this4.formData.get('sessid') !== null) {
	            _this4.formData.set('sessid', auth_data.sessid_md5);
	          }
	          _this4.xhr = main_core.ajax({
	            timeout: 30,
	            preparePost: _this4.preparePost,
	            start: _this4.start,
	            method: _this4.method,
	            dataType: _this4.type,
	            url: _this4.url,
	            data: _this4.data,
	            onsuccess: function onsuccess(response_ii) {
	              var failed = false;
	              if (_this4.xhr.status === 0) {
	                failed = true;
	              } else if (_this4.type === 'json') {
	                failed = main_core.Type.isPlainObject(response_ii) && main_core.Type.isStringFilled(response_ii.status) && response_ii.status === 'failed';
	              } else if (_this4.type === 'html') {
	                failed = response_ii === '{"status":"failed"}';
	              }
	              if (failed) {
	                _this4.failure_callback();
	              } else {
	                _this4.callback(response_ii);
	              }
	            },
	            onfailure: function onfailure(response) {
	              _this4.failure_callback();
	            }
	          });
	          if (!_this4.start && _this4.formData !== null) {
	            _this4.xhr.send(_this4.formData);
	          }
	        },
	        failture: function failture() {
	          _this4.failure_callback();
	        }
	      });
	    }
	    /**
	     * @private
	     */
	  }, {
	    key: "bindHandlers",
	    value: function bindHandlers() {
	      if (main_core.Type.isFunction(this.progress_callback)) {
	        main_core.Event.bind(this.xhr, 'progress', this.progress_callback);
	      }
	      if (main_core.Type.isFunction(this.load_callback)) {
	        main_core.Event.bind(this.xhr, 'load', this.load_callback);
	      }
	      if (main_core.Type.isFunction(this.loadstart_callback)) {
	        main_core.Event.bind(this.xhr, 'loadstart', this.loadstart_callback);
	      }
	      if (main_core.Type.isFunction(this.loadend_callback)) {
	        main_core.Event.bind(this.xhr, 'loadend', this.loadend_callback);
	      }
	      if (main_core.Type.isFunction(this.error_callback)) {
	        main_core.Event.bind(this.xhr, 'error', this.error_callback);
	      }
	      if (main_core.Type.isFunction(this.abort_callback)) {
	        main_core.Event.bind(this.xhr, 'abort', this.abort_callback);
	      }
	    }
	  }], [{
	    key: "wrap",
	    value: function wrap(params) {
	      var instance = new Ajax();
	      return instance.instanceWrap(params);
	    }
	  }, {
	    key: "runComponentAction",
	    value: function runComponentAction(component, action, config, callbacks) {
	      var instance = new Ajax();
	      return instance.instanceRunComponentAction(component, action, config, callbacks);
	    }
	  }, {
	    key: "runAction",
	    value: function runAction(action, config, callbacks) {
	      var instance = new Ajax();
	      return instance.instanceRunAction(action, config, callbacks);
	    }
	  }]);
	  return Ajax;
	}();

	exports.Ajax = Ajax;

}((this.BX.Mobile = this.BX.Mobile || {}),BX));
//# sourceMappingURL=ajax.bundle.js.map

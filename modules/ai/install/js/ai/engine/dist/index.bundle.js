this.BX = this.BX || {};
(function (exports,ai_payload_basepayload,main_core) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _moduleId = /*#__PURE__*/new WeakMap();
	var _contextId = /*#__PURE__*/new WeakMap();
	var _contextParameters = /*#__PURE__*/new WeakMap();
	var _payload = /*#__PURE__*/new WeakMap();
	var _historyState = /*#__PURE__*/new WeakMap();
	var _historyGroupId = /*#__PURE__*/new WeakMap();
	var _parameters = /*#__PURE__*/new WeakMap();
	var _addSystemParameters = /*#__PURE__*/new WeakSet();
	var _registerPullListener = /*#__PURE__*/new WeakSet();
	var _send = /*#__PURE__*/new WeakSet();
	var _isOffline = /*#__PURE__*/new WeakSet();
	var Engine = /*#__PURE__*/function () {
	  function Engine() {
	    babelHelpers.classCallCheck(this, Engine);
	    _classPrivateMethodInitSpec(this, _isOffline);
	    _classPrivateMethodInitSpec(this, _send);
	    _classPrivateMethodInitSpec(this, _registerPullListener);
	    _classPrivateMethodInitSpec(this, _addSystemParameters);
	    _classPrivateFieldInitSpec(this, _moduleId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _contextId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _contextParameters, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _payload, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _historyState, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _historyGroupId, {
	      writable: true,
	      value: -1
	    });
	    _classPrivateFieldInitSpec(this, _parameters, {
	      writable: true,
	      value: {}
	    });
	  }
	  babelHelpers.createClass(Engine, [{
	    key: "setPayload",
	    /**
	     * Sets Payload for Engine.
	     *
	     * @param {PayloadBase} payload
	     * @return {Engine}
	     */
	    value: function setPayload(payload) {
	      babelHelpers.classPrivateFieldSet(this, _payload, payload);
	      return this;
	    }
	  }, {
	    key: "getPayload",
	    value: function getPayload() {
	      return babelHelpers.classPrivateFieldGet(this, _payload);
	    }
	    /**
	     * Sets allowed (by core) parameters for Engine.
	     *
	     * @param {{[key: string]: string}} parameters
	     * @return {Engine}
	     */
	  }, {
	    key: "setParameters",
	    value: function setParameters(parameters) {
	      babelHelpers.classPrivateFieldSet(this, _parameters, parameters);
	      return this;
	    }
	    /**
	     * Sets current module id. Its should be Bitrix's module.
	     *
	     * @param {string} moduleId
	     * @return {Engine}
	     */
	  }, {
	    key: "setModuleId",
	    value: function setModuleId(moduleId) {
	      babelHelpers.classPrivateFieldSet(this, _moduleId, moduleId);
	      return this;
	    }
	    /**
	     * Sets current context id. Its may be just a string unique within the moduleId.
	     *
	     * @param {string} contextId
	     * @return {Engine}
	     */
	  }, {
	    key: "setContextId",
	    value: function setContextId(contextId) {
	      babelHelpers.classPrivateFieldSet(this, _contextId, contextId);
	      return this;
	    }
	  }, {
	    key: "setContextParameters",
	    value: function setContextParameters(contextParameters) {
	      babelHelpers.classPrivateFieldSet(this, _contextParameters, contextParameters);
	      return this;
	    }
	    /**
	     * Write or not history, in depend on $state.
	     *
	     * @param {boolean} state
	     * @return {Engine}
	     */
	  }, {
	    key: "setHistoryState",
	    value: function setHistoryState(state) {
	      babelHelpers.classPrivateFieldSet(this, _historyState, state);
	      return this;
	    }
	    /**
	     * Set group ID for save history.
	     * -1 - no grouped, 0 - first item of group
	     * @param id
	     * @return {Engine}
	     */
	  }, {
	    key: "setHistoryGroupId",
	    value: function setHistoryGroupId(id) {
	      babelHelpers.classPrivateFieldSet(this, _historyGroupId, id);
	      return this;
	    }
	  }, {
	    key: "setBannerLaunched",
	    value: function setBannerLaunched() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.setBannerLaunchedUrl, {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	    /**
	     * Makes request for text completions.
	     *
	     * @return {Promise}
	     */
	  }, {
	    key: "textCompletions",
	    value: function textCompletions() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.textCompletionsUrl, {
	        prompt: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().prompt,
	        engineCode: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().engineCode,
	        markers: babelHelpers.classPrivateFieldGet(this, _payload).getMarkers(),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	    /**
	     * Makes request for image completions.
	     *
	     * @return {Promise}
	     */
	  }, {
	    key: "imageCompletions",
	    value: function imageCompletions() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.imageCompletionsUrl, {
	        prompt: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().prompt,
	        engineCode: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().engineCode,
	        markers: babelHelpers.classPrivateFieldGet(this, _payload).getMarkers(),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	  }, {
	    key: "getTooling",
	    value: function getTooling(category) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        category: category,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.getToolingUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "installKit",
	    value: function installKit(code) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        code: code,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.installKitUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	    /**
	     * Send user's acceptation of agreement.
	     *
	     * @return {Promise<string>}
	     */
	  }, {
	    key: "acceptImageAgreement",
	    value: function acceptImageAgreement(engineCode) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        engineCode: engineCode,
	        sessid: main_core.Loc.getMessage('bitrix_sessid'),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.imageAcceptationUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "acceptTextAgreement",
	    value: function acceptTextAgreement(engineCode) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        engineCode: engineCode,
	        sessid: main_core.Loc.getMessage('bitrix_sessid'),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.textAcceptationUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "saveImage",
	    value: function saveImage(imageUrl) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        pictureUrl: imageUrl,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.saveImageUrl, data);
	    }
	    /**
	     * Adds additional system parameters.
	     */
	  }]);
	  return Engine;
	}();
	function _addSystemParameters2() {
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_module = babelHelpers.classPrivateFieldGet(this, _moduleId);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_context = babelHelpers.classPrivateFieldGet(this, _contextId);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_context_parameters = babelHelpers.classPrivateFieldGet(this, _contextParameters);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_history = babelHelpers.classPrivateFieldGet(this, _historyState);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_history_group_id = babelHelpers.classPrivateFieldGet(this, _historyGroupId);
	}
	function _registerPullListener2(queueHash, resolve, reject) {
	  main_core.addCustomEvent('onPullEvent-ai', function (command, params) {
	    var hash = params.hash,
	      data = params.data,
	      error = params.error;
	    if (command === 'onQueueJobExecute' && hash === queueHash) {
	      resolve({
	        data: data
	      });
	    } else if (command === 'onQueueJobFail' && hash === queueHash) {
	      reject({
	        error: error
	      });
	    }
	  });
	}
	function _send2(url, data) {
	  var _this = this;
	  if (_classPrivateMethodGet(this, _isOffline, _isOffline2).call(this)) {
	    return Promise.reject(new Error(main_core.Loc.getMessage('AI_ENGINE_INTERNET_PROBLEM')));
	  }
	  return new Promise(function (resolve, reject) {
	    var fd = main_core.Http.Data.convertObjectToFormData(data);
	    var xhr = main_core.ajax({
	      method: 'POST',
	      dataType: 'json',
	      url: url,
	      data: fd,
	      start: false,
	      preparePost: false,
	      onsuccess: function onsuccess(response) {
	        if (response.status === 'error') {
	          reject(response);
	        } else {
	          var _response$data;
	          var queueHash = (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.queue;
	          if (queueHash) {
	            _classPrivateMethodGet(_this, _registerPullListener, _registerPullListener2).call(_this, queueHash, resolve, reject);
	          } else {
	            resolve(response);
	          }
	        }
	      },
	      onfailure: function onfailure(res, resData) {
	        if (res === 'processing' && (resData === null || resData === void 0 ? void 0 : resData.bProactive) === true) {
	          reject(resData.data);
	        }
	        reject(res);
	      }
	    });
	    xhr.send(fd);
	  });
	}
	function _isOffline2() {
	  return !window.navigator.onLine;
	}
	babelHelpers.defineProperty(Engine, "textCompletionsUrl", '/bitrix/services/main/ajax.php?action=ai.api.text.completions');
	babelHelpers.defineProperty(Engine, "imageCompletionsUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.completions');
	babelHelpers.defineProperty(Engine, "textAcceptationUrl", '/bitrix/services/main/ajax.php?action=ai.api.text.acceptation');
	babelHelpers.defineProperty(Engine, "imageAcceptationUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.acceptation');
	babelHelpers.defineProperty(Engine, "saveImageUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.save');
	babelHelpers.defineProperty(Engine, "getToolingUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.get');
	babelHelpers.defineProperty(Engine, "installKitUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.installKit');
	babelHelpers.defineProperty(Engine, "setBannerLaunchedUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.setLaunched');

	exports.Engine = Engine;

}((this.BX.AI = this.BX.AI || {}),BX.AI.Payload,BX));
//# sourceMappingURL=index.bundle.js.map

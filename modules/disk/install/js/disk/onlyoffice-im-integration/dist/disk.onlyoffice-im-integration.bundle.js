this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,disk_viewer_onlyofficeItem,main_core) {
	'use strict';

	var CreateDocument = /*#__PURE__*/function () {
	  function CreateDocument(options) {
	    babelHelpers.classCallCheck(this, CreateDocument);
	    this.options = options;
	    this.bindEvents();
	  }

	  babelHelpers.createClass(CreateDocument, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      window.addEventListener("message", function (event) {
	        if (event.data.type === 'selectedTemplate') {
	          _this.options.delegate.setMaxWidth(961);

	          _this.options.delegate.onDocumentCreated();
	        }
	      });
	    }
	  }, {
	    key: "getIframeUrlForCreate",
	    value: function getIframeUrlForCreate(options) {
	      var _this2 = this;

	      this.options.delegate.setMaxWidth(961);
	      return new Promise(function (resolve, reject) {
	        var url = BX.util.add_url_param('/bitrix/services/main/ajax.php', {
	          action: 'disk.api.integration.messengerCall.createDocumentInCall',
	          typeFile: options.typeFile,
	          callId: _this2.options.call.id,
	          dialogId: _this2.options.dialog.id
	        });
	        resolve(url);
	      });
	    }
	  }, {
	    key: "getIframeUrl",
	    value: function getIframeUrl(options) {
	      this.options.delegate.setMaxWidth(961);
	      return new Promise(function (resolve, reject) {
	        options.viewerItem.enableEditInsteadPreview();
	        var queryParameters = options.viewerItem.getSliderQueryParameters();
	        var url = BX.util.add_url_param('/bitrix/services/main/ajax.php', queryParameters);
	        resolve(url);
	      });
	    }
	  }, {
	    key: "getIframeUrlForTemplates",
	    value: function getIframeUrlForTemplates() {
	      var _this3 = this;

	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.integration.messengerCall.selectTemplateOrOpenExisting', {
	          data: {
	            callId: _this3.options.call.id,
	            dialogId: _this3.options.dialog.id
	          }
	        }).then(function (response) {
	          if (response.data.document && response.data.document.urlToEdit) {
	            _this3.options.delegate.setMaxWidth(961);

	            resolve(response.data.document.urlToEdit);
	          } else if (response.data.template && response.data.template.urlToSelect) {
	            _this3.options.delegate.setMaxWidth(328);

	            resolve(response.data.template.urlToSelect);
	          } else {
	            reject();
	          }
	        });
	      });
	    }
	  }, {
	    key: "listResumesInChat",
	    value: function listResumesInChat(chatId) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('disk.api.integration.messengerCall.listResumesInChat', {
	          data: {
	            chatId: chatId
	          }
	        }).then(function (response) {
	          if (response.data) {
	            resolve(response.data.resumes);
	          } else {
	            reject();
	          }
	        })["catch"](function () {
	          reject();
	        });
	      });
	    }
	  }, {
	    key: "onCloseIframe",
	    value: function onCloseIframe(iframe) {
	      iframe.contentWindow.postMessage('closeIframe', '*');
	      return true;
	    }
	  }]);
	  return CreateDocument;
	}();

	exports.CreateDocument = CreateDocument;

}((this.BX.Disk.OnlyOfficeImIntegration = this.BX.Disk.OnlyOfficeImIntegration || {}),BX.Disk.Viewer,BX));
//# sourceMappingURL=disk.onlyoffice-im-integration.bundle.js.map

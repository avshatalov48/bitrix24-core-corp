this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,main_core_events,pull_client) {
	'use strict';

	var OnlyOffice = /*#__PURE__*/function () {
	  function OnlyOffice(editorOptions) {
	    babelHelpers.classCallCheck(this, OnlyOffice);
	    babelHelpers.defineProperty(this, "editor", null);
	    babelHelpers.defineProperty(this, "editorJson", null);
	    babelHelpers.defineProperty(this, "editorNode", null);
	    babelHelpers.defineProperty(this, "editorWrapperNode", null);
	    babelHelpers.defineProperty(this, "targetNode", null);
	    babelHelpers.defineProperty(this, "documentSession", null);
	    babelHelpers.defineProperty(this, "object", null);
	    babelHelpers.defineProperty(this, "documentWasChanged", false);
	    babelHelpers.defineProperty(this, "dontEndCurrentDocumentSession", false);
	    var options = main_core.Type.isPlainObject(editorOptions) ? editorOptions : {};
	    this.documentSession = options.documentSession;
	    this.object = options.object;
	    this.targetNode = options.targetNode;
	    this.editorNode = options.editorNode;
	    this.editorWrapperNode = options.editorWrapperNode;
	    this.initializeEditor(options.editorJson);
	    var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
	    currentSlider.getData().set('documentSession', this.documentSession);
	    this.bindEvents();
	  }

	  babelHelpers.createClass(OnlyOffice, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe("SidePanel.Slider:onClose", this.handleClose.bind(this));
	    }
	  }, {
	    key: "initializeEditor",
	    value: function initializeEditor(options) {
	      this.adjustEditorHeight(options);
	      options.events = {
	        onDocumentStateChange: this.handleDocumentStateChange.bind(this),
	        onDocumentReady: this.handleDocumentReady.bind(this),
	        onInfo: this.handleInfo.bind(this) // onRequestClose: this.handleClose.bind(this),

	      };

	      if (options.document.permissions.edit === true) {
	        //in that case we will show Edit button
	        options.events.onRequestEditRights = this.handleRequestEditRights.bind(this);
	      }

	      this.editorJson = options;
	      this.editor = new DocsAPI.DocEditor(this.editorNode.id, options);
	    }
	  }, {
	    key: "adjustEditorHeight",
	    value: function adjustEditorHeight(options) {
	      options.height = document.body.clientHeight + 'px';
	    }
	  }, {
	    key: "emitEventOnSaved",
	    value: function emitEventOnSaved() {
	      var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);

	      if (sliderByWindow) {
	        BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onSaved', {
	          documentSession: this.documentSession,
	          object: this.object
	        });
	      }

	      main_core_events.EventEmitter.emit('Disk.OnlyOffice:onSaved', {
	        documentSession: this.documentSession,
	        object: this.object
	      });
	    }
	  }, {
	    key: "emitEventOnClosed",
	    value: function emitEventOnClosed() {
	      var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
	      var process = 'edit';

	      if (sliderByWindow) {
	        process = sliderByWindow.getData().get('process') || 'edit';
	        BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onClosed', {
	          documentSession: this.documentSession,
	          object: this.object,
	          process: process
	        });
	      }

	      main_core_events.EventEmitter.emit('Disk.OnlyOffice:onClosed', {
	        documentSession: this.documentSession,
	        object: this.object,
	        process: process
	      });
	    }
	  }, {
	    key: "handleSaveButtonClick",
	    value: function handleSaveButtonClick() {
	      var _this = this;

	      pull_client.PULL.subscribe({
	        moduleId: 'disk',
	        command: 'onlyoffice',
	        callback: function callback(data) {
	          if (data.hash === _this.documentSession.hash) {
	            _this.emitEventOnSaved();

	            window.BX.Disk.showModalWithStatusAction();
	            BX.SidePanel.Instance.close();
	          }
	        }
	      });
	    }
	  }, {
	    key: "handleClose",
	    value: function handleClose() {
	      this.emitEventOnClosed();

	      if (this.dontEndCurrentDocumentSession) {
	        return;
	      }

	      top.BX.Disk.endEditSession({
	        id: this.documentSession.id,
	        hash: this.documentSession.hash,
	        documentWasChanged: this.documentWasChanged
	      });
	    }
	  }, {
	    key: "handleDocumentStateChange",
	    value: function handleDocumentStateChange(event) {
	      if (!this.caughtDocumentReady || !this.caughtInfoEvent) {
	        return;
	      }

	      if (Date.now() - Math.max(this.caughtDocumentReady, this.caughtInfoEvent) < 500) {
	        return;
	      }

	      this.documentWasChanged = true;
	    }
	  }, {
	    key: "handleInfo",
	    value: function handleInfo() {
	      this.caughtInfoEvent = Date.now();
	    }
	  }, {
	    key: "handleDocumentReady",
	    value: function handleDocumentReady() {
	      this.caughtDocumentReady = Date.now();
	    }
	  }, {
	    key: "handleRequestEditRights",
	    value: function handleRequestEditRights() {
	      this.dontEndCurrentDocumentSession = true;
	      var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
	      var customLeftBoundary = currentSlider.getCustomLeftBoundary();
	      currentSlider.close();
	      BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', {
	        action: 'disk.api.documentService.goToEdit',
	        serviceCode: 'onlyoffice',
	        documentSessionId: this.documentSession.id,
	        documentSessionHash: this.documentSession.hash
	      }), {
	        width: '100%',
	        customLeftBoundary: customLeftBoundary,
	        cacheable: false,
	        allowChangeHistory: false,
	        data: {
	          documentEditor: true
	        }
	      });
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return this.editor;
	    }
	  }, {
	    key: "getEditorNode",
	    value: function getEditorNode() {
	      return this.editorNode;
	    }
	  }, {
	    key: "getEditorWrapperNode",
	    value: function getEditorWrapperNode() {
	      return this.editorWrapperNode;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.targetNode;
	    }
	  }]);
	  return OnlyOffice;
	}();

	exports.OnlyOffice = OnlyOffice;

}((this.BX.Disk.Editor = this.BX.Disk.Editor || {}),BX,BX.Event,BX));
//# sourceMappingURL=script.js.map

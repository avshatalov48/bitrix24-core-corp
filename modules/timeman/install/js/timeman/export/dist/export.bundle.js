this.BX = this.BX || {};
(function (exports,main_popup,main_core,ui_buttons) {
	'use strict';

	var ExportState = /*#__PURE__*/function (_Event$EventEmitter) {
	  babelHelpers.inherits(ExportState, _Event$EventEmitter);

	  function ExportState() {
	    var _this;

	    babelHelpers.classCallCheck(this, ExportState);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExportState).call(this));
	    _this.states = {
	      intermediate: 0,
	      running: 1,
	      completed: 2,
	      stopped: 3,
	      error: 4
	    };
	    return _this;
	  }

	  babelHelpers.createClass(ExportState, [{
	    key: "isRunning",
	    value: function isRunning() {
	      return this.state === this.states.running;
	    }
	  }, {
	    key: "setRunning",
	    value: function setRunning() {
	      this.state = this.states.running;
	      this.emit('running');
	    }
	  }, {
	    key: "setIntermediate",
	    value: function setIntermediate() {
	      this.state = this.states.intermediate;
	      this.emit('intermediate');
	    }
	  }, {
	    key: "setStopped",
	    value: function setStopped() {
	      this.state = this.states.stopped;
	      this.emit('stopped');
	    }
	  }, {
	    key: "setCompleted",
	    value: function setCompleted() {
	      this.state = this.states.completed;
	      this.emit('completed');
	    }
	  }, {
	    key: "setError",
	    value: function setError() {
	      this.state = this.states.error;
	      this.emit('error');
	    }
	  }]);
	  return ExportState;
	}(main_core.Event.EventEmitter);

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"timeman-export-content-final-buttons\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"timeman-export-progress-bar-container\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ExportPopup = /*#__PURE__*/function () {
	  function ExportPopup(options) {
	    babelHelpers.classCallCheck(this, ExportPopup);
	    options = babelHelpers.objectSpread({}, {
	      exportManager: null,
	      exportState: null
	    }, options);
	    this.exportManager = options.exportManager instanceof Export ? options.exportManager : null;
	    this.exportState = options.exportState instanceof ExportState ? options.exportState : new ExportState();
	    this.popup = null;
	    this.popupIsShown = false;
	    this.popupContentId = 'timeman-export-popup-content';
	    this.createProgressBar();
	    this.subscribeToState();
	  }

	  babelHelpers.createClass(ExportPopup, [{
	    key: "createPopup",
	    value: function createPopup() {
	      this.popup = main_popup.PopupManager.create(main_core.Text.getRandom(), null, {
	        autoHide: false,
	        bindOptions: {
	          forceBindPosition: false
	        },
	        buttons: this.getPopupButtons(),
	        closeByEsc: false,
	        closeIcon: false,
	        content: this.getPopupContent(),
	        draggable: true,
	        events: {
	          onPopupClose: this.onPopupClose.bind(this)
	        },
	        offsetLeft: 0,
	        offsetTop: 0,
	        titleBar: main_core.Loc.getMessage('TIMEMAN_EXPORT_POPUP_TITLE_EXCEL'),
	        overlay: true
	      });
	    }
	  }, {
	    key: "showPopup",
	    value: function showPopup() {
	      if (this.popupIsShown || !this.popup) {
	        return;
	      }

	      this.popup.adjustPosition();

	      if (!this.popup.isShown()) {
	        this.popup.show();
	      }

	      this.popupIsShown = this.popup.isShown();
	    }
	  }, {
	    key: "adjustPosition",
	    value: function adjustPosition() {
	      if (this.popup) {
	        this.popup.adjustPosition();
	      }
	    }
	  }, {
	    key: "createProgressBar",
	    value: function createProgressBar() {
	      var _this = this;

	      /* eslint-disable */
	      BX.loadExt('ui.progressbar').then(function () {
	        _this.progressBar = new BX.UI.ProgressBar({
	          statusType: BX.UI.ProgressBar.Status.COUNTER,
	          size: BX.UI.ProgressBar.Size.LARGE,
	          fill: true
	        });
	        _this.progressBarContainer = main_core.Tag.render(_templateObject(), _this.progressBar.getContainer());

	        _this.progressBarHide();
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "subscribeToState",
	    value: function subscribeToState() {
	      var _this2 = this;

	      this.exportState.subscribe('running', function () {
	        _this2.hideCloseButton();
	      }).subscribe('intermediate', function () {
	        _this2.showCloseButton();
	      }).subscribe('stopped', function () {
	        _this2.showCloseButton();
	      }).subscribe('completed', function () {
	        _this2.showCloseButton();

	        _this2.progressBarHide();
	      }).subscribe('error', function () {
	        _this2.showCloseButton();

	        _this2.progressBarSetDanger();
	      });
	    }
	  }, {
	    key: "showCloseButton",
	    value: function showCloseButton() {
	      if (this.buttons['close'] !== 'undefined') {
	        this.buttons['close'].button.style.display = '';
	      }
	    }
	  }, {
	    key: "hideCloseButton",
	    value: function hideCloseButton() {
	      if (this.buttons['close'] !== 'undefined') {
	        this.buttons['close'].button.style.display = 'none';
	      }
	    }
	  }, {
	    key: "progressBarShow",
	    value: function progressBarShow() {
	      if (this.progressBarContainer) {
	        this.progressBarContainer.style.display = '';
	      }
	    }
	  }, {
	    key: "progressBarHide",
	    value: function progressBarHide() {
	      if (this.progressBarContainer) {
	        this.progressBarContainer.style.display = 'none';
	      }
	    }
	  }, {
	    key: "progressBarSetDanger",
	    value: function progressBarSetDanger() {
	      if (this.progressBar) {
	        // eslint-disable-next-line
	        this.progressBar.setColor(BX.UI.ProgressBar.Color.DANGER);
	      }
	    }
	  }, {
	    key: "getPopupButtons",
	    value: function getPopupButtons() {
	      this.buttons = {}; //todo stop

	      this.buttons['close'] = new ui_buttons.Button({
	        text: main_core.Loc.getMessage('TIMEMAN_EXPORT_POPUP_CLOSE'),
	        color: ui_buttons.Button.Color.LINK,
	        events: {
	          click: this.handleCloseButtonClick.bind(this)
	        }
	      });
	      return [this.buttons['close']];
	    }
	  }, {
	    key: "getDownloadButton",
	    value: function getDownloadButton(text, downloadUrl) {
	      return new ui_buttons.Button({
	        text: text,
	        color: ui_buttons.Button.Color.SUCCESS,
	        icon: ui_buttons.Button.Icon.DOWNLOAD,
	        tag: ui_buttons.Button.Tag.LINK,
	        link: downloadUrl
	      });
	    }
	  }, {
	    key: "getDeleteButton",
	    value: function getDeleteButton(text) {
	      var _this3 = this;

	      return new ui_buttons.Button({
	        text: text,
	        icon: ui_buttons.Button.Icon.REMOVE,
	        onclick: function onclick(btn, event) {
	          _this3.exportManager.clearRequest();
	        }
	      });
	    }
	  }, {
	    key: "setPopupContent",
	    value: function setPopupContent(data) {
	      var popupContent = document.getElementById(this.popupContentId);
	      popupContent.innerHTML = data['SUMMARY_HTML'];

	      if (data['DOWNLOAD_LINK']) {
	        popupContent.appendChild(this.renderFinalButtons(data));
	      }
	    }
	  }, {
	    key: "getPopupContent",
	    value: function getPopupContent() {
	      this.popupContent = "<div id=\"".concat(this.popupContentId, "\"></div>");
	      return main_core.Tag.render(_templateObject2(), this.popupContent, this.progressBarContainer);
	    }
	  }, {
	    key: "renderFinalButtons",
	    value: function renderFinalButtons(data) {
	      return main_core.Tag.render(_templateObject3(), this.getDownloadButton(data['DOWNLOAD_LINK_NAME'], data['DOWNLOAD_LINK']).render(), this.getDeleteButton(data['CLEAR_LINK_NAME']).render());
	    }
	  }, {
	    key: "onPopupClose",
	    value: function onPopupClose() {
	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }

	      this.popupIsShown = false;
	    }
	  }, {
	    key: "handleCloseButtonClick",
	    value: function handleCloseButtonClick() {
	      if (this.popup && !this.exportState.isRunning()) {
	        this.popup.close();
	      }
	    }
	  }, {
	    key: "setProgressBar",
	    value: function setProgressBar(processedItems, totalItems) {
	      if (totalItems) {
	        if (this.progressBar) {
	          this.progressBarShow();
	          this.progressBar.setMaxValue(totalItems);
	          this.progressBar.update(processedItems);
	        }
	      } else {
	        this.progressBarHide();
	      }
	    }
	  }]);
	  return ExportPopup;
	}();

	var Export = /*#__PURE__*/function () {
	  function Export(options) {
	    babelHelpers.classCallCheck(this, Export);
	    options = babelHelpers.objectSpread({}, {
	      signedParameters: '',
	      componentName: '',
	      siteId: '',
	      stExportId: '',
	      managerId: '',
	      sToken: ''
	    }, options);
	    this.signedParameters = options.signedParameters;
	    this.componentName = options.componentName;
	    this.siteId = options.siteId;
	    this.stExportId = options.stExportId;
	    this.managerId = options.managerId;
	    this.sToken = options.sToken;
	    this.cToken = 'c';
	    this.exportState = new ExportState();
	    this.exportPopup = new ExportPopup({
	      exportManager: this,
	      exportState: this.exportState
	    });
	    this.availableTypes = ['excel', 'csv'];
	  }

	  babelHelpers.createClass(Export, [{
	    key: "startExport",
	    value: function startExport(exportType) {
	      if (!this.availableTypes.includes(exportType)) {
	        throw 'Export: parameter "exportType" has invalid value';
	      }

	      this.exportType = exportType;
	      this.exportPopup.createPopup();
	      this.exportPopup.showPopup();
	      this.startRequest();
	    }
	  }, {
	    key: "getExcelExportType",
	    value: function getExcelExportType() {
	      return 'excel';
	    }
	  }, {
	    key: "getCsvExportType",
	    value: function getCsvExportType() {
	      return 'csv';
	    }
	  }, {
	    key: "startRequest",
	    value: function startRequest() {
	      this.cToken += Date.now();
	      this.request('timeman.api.export.dispatcher');
	    }
	  }, {
	    key: "nextRequest",
	    value: function nextRequest() {
	      this.request('timeman.api.export.dispatcher');
	    }
	  }, {
	    key: "stopRequest",
	    value: function stopRequest() {
	      this.request('timeman.api.export.cancel');
	    }
	  }, {
	    key: "clearRequest",
	    value: function clearRequest() {
	      this.request('timeman.api.export.clear');
	    }
	  }, {
	    key: "request",
	    value: function request(action) {
	      var _this = this;

	      this.exportState.setRunning();
	      main_core.ajax.runAction(action, {
	        data: {
	          'SITE_ID': this.siteId,
	          'PROCESS_TOKEN': this.sToken + this.cToken,
	          'EXPORT_TYPE': this.exportType,
	          'COMPONENT_NAME': this.componentName,
	          'signedParameters': this.signedParameters
	        }
	      }).then(function (response) {
	        _this.handleResponse(response);
	      }).catch(function (response) {
	        _this.handleResponse(response);
	      });
	    }
	  }, {
	    key: "handleResponse",
	    value: function handleResponse(response) {
	      var _this2 = this;

	      if (response.errors.length) {
	        this.exportPopup.setPopupContent(response.errors.shift().message);
	        this.exportState.setError();
	      } else if (response.status === 'success') {
	        var data = response.data;

	        switch (data['STATUS']) {
	          case 'COMPLETED':
	          case 'NOT_REQUIRED':
	            this.exportState.setCompleted();
	            break;

	          case 'PROGRESS':
	            var processedItems = main_core.Type.isInteger(data['PROCESSED_ITEMS']) ? data['PROCESSED_ITEMS'] : 0;
	            var totalItems = main_core.Type.isInteger(data['TOTAL_ITEMS']) ? data['TOTAL_ITEMS'] : 0;
	            this.exportPopup.setProgressBar(processedItems, totalItems);
	            setTimeout(function () {
	              return _this2.nextRequest();
	            }, 200);
	            break;
	        }

	        this.exportPopup.setPopupContent(data);
	      } else {
	        this.exportState.setError();
	      }

	      this.exportPopup.adjustPosition();
	    }
	  }]);
	  return Export;
	}();

	exports.Export = Export;

}((this.BX.Timeman = this.BX.Timeman || {}),BX.Main,BX,BX.UI));
//# sourceMappingURL=export.bundle.js.map

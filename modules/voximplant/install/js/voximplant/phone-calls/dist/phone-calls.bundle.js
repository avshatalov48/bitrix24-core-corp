/* eslint-disable */
this.BX = this.BX || {};
(function (exports,intranet_desktopDownload,main_core_events,main_core,im_v2_lib_desktopApi,main_popup,ui_dialogs_messagebox) {
	'use strict';

	var baseZIndex = 15000;
	var nop = function nop() {};

	var digits = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'];
	var Keypad = /*#__PURE__*/function () {
	  function Keypad(params) {
	    babelHelpers.classCallCheck(this, Keypad);
	    if (!main_core.Type.isPlainObject(params)) {
	      params = {};
	    }
	    this.bindElement = params.bindElement || null;
	    this.offsetTop = params.offsetTop || 0;
	    this.offsetLeft = params.offsetLeft || 0;
	    this.anglePosition = params.anglePosition || '';
	    this.angleOffset = params.angleOffset || 0;
	    this.history = params.history || [];
	    this.selectedLineId = params.defaultLineId;
	    this.lines = params.lines || {};
	    this.availableLines = params.availableLines || [];
	    this.callInterceptAllowed = params.callInterceptAllowed === true;
	    this.zIndex = baseZIndex + 200;

	    //flags
	    this.hideDial = params.hideDial === true;
	    this.plusEntered = false;
	    this.callbacks = {
	      onButtonClick: main_core.Type.isFunction(params.onButtonClick) ? params.onButtonClick : nop,
	      onDial: main_core.Type.isFunction(params.onDial) ? params.onDial : nop,
	      onIntercept: main_core.Type.isFunction(params.onIntercept) ? params.onIntercept : nop,
	      onClose: main_core.Type.isFunction(params.onClose) ? params.onClose : nop
	    };
	    this.elements = {
	      inputContainer: null,
	      input: null,
	      lineSelector: null,
	      lineName: null,
	      interceptButton: null,
	      historyButton: null
	    };
	    this.plusKeyTimeout = null;
	    this.popup = this.createPopup();
	  }
	  babelHelpers.createClass(Keypad, [{
	    key: "createPopup",
	    value: function createPopup() {
	      var popupOptions = {
	        id: 'phone-call-view-popup-keypad',
	        bindElement: this.bindElement,
	        targetContainer: document.body,
	        darkMode: true,
	        closeByEsc: true,
	        autoHide: true,
	        zIndex: this.zIndex,
	        content: this.render(),
	        noAllPaddings: true,
	        offsetTop: this.offsetTop,
	        offsetLeft: this.offsetLeft,
	        overlay: {
	          backgroundColor: 'white',
	          opacity: 0
	        },
	        events: {
	          onPopupClose: this.onPopupClose.bind(this)
	        }
	      };
	      if (this.anglePosition !== '') {
	        popupOptions.angle = {
	          position: this.anglePosition,
	          offset: this.angleOffset
	        };
	      }
	      return new main_popup.Popup(popupOptions);
	    }
	  }, {
	    key: "onPopupClose",
	    value: function onPopupClose() {
	      this.callbacks.onClose();
	      if (this.popup) {
	        this.popup.destroy();
	      }
	    }
	  }, {
	    key: "canSelectLine",
	    value: function canSelectLine() {
	      return this.availableLines.length > 1;
	    }
	  }, {
	    key: "setSelectedLineId",
	    value: function setSelectedLineId(lineId) {
	      this.selectedLineId = lineId;
	      if (this.elements.lineName) {
	        this.elements.lineName.innerText = this.getLineName(lineId);
	      }
	    }
	  }, {
	    key: "getLineName",
	    value: function getLineName(lineId) {
	      return this.lines.hasOwnProperty(lineId) ? this.lines[lineId].SHORT_NAME : '';
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this = this;
	      return main_core.Dom.create("div", {
	        props: {
	          className: "bx-messenger-calc-wrap"
	        },
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: "bx-messenger-calc-body"
	          },
	          children: [this.elements.inputContainer = main_core.Dom.create("div", {
	            props: {
	              className: 'bx-messenger-calc-panel'
	            },
	            children: [main_core.Dom.create("span", {
	              props: {
	                className: "bx-messenger-calc-panel-delete"
	              },
	              events: {
	                click: this._onDeleteButtonClick.bind(this)
	              }
	            }), this.elements.input = main_core.Dom.create("input", {
	              attrs: {
	                'readonly': this.hideDial,
	                type: "text",
	                value: '',
	                placeholder: main_core.Loc.getMessage(this.hideDial ? 'IM_PHONE_PUT_DIGIT' : 'IM_PHONE_PUT_NUMBER')
	              },
	              props: {
	                className: "bx-messenger-calc-panel-input"
	              },
	              events: {
	                keydown: this._onInputKeydown.bind(this),
	                keyup: function keyup() {
	                  return _this._onAfterNumberChanged();
	                }
	              }
	            })]
	          }), main_core.Dom.create("div", {
	            props: {
	              className: "bx-messenger-calc-btns-block"
	            },
	            children: digits.map(function (digit) {
	              return renderNumber(digit, _this._onKeyMouseDown.bind(_this), _this._onKeyMouseUp.bind(_this));
	            })
	          })]
	        }), this.hideDial ? null : main_core.Dom.create("div", {
	          props: {
	            className: "bx-messenger-call-btn-wrap"
	          },
	          children: [this.elements.lineSelector = !this.canSelectLine() ? null : main_core.Dom.create("div", {
	            props: {
	              className: "im-phone-select-line"
	            },
	            children: [this.elements.lineName = main_core.Dom.create("span", {
	              props: {
	                className: "im-phone-select-line-name"
	              },
	              text: this.getLineName(this.selectedLineId)
	            }), main_core.Dom.create("span", {
	              props: {
	                className: "im-phone-select-line-select"
	              }
	            })],
	            events: {
	              click: this._onLineSelectClick.bind(this)
	            }
	          }), main_core.Dom.create("span", {
	            props: {
	              className: "bx-messenger-call-btn-separate"
	            },
	            children: [main_core.Dom.create("span", {
	              props: {
	                className: "bx-messenger-call-btn"
	              },
	              children: [main_core.Dom.create("span", {
	                props: {
	                  className: "bx-messenger-call-btn-text"
	                },
	                text: main_core.Loc.getMessage('IM_PHONE_CALL')
	              })],
	              events: {
	                click: this._onDialButtonClick.bind(this)
	              }
	            }), this.elements.historyButton = main_core.Dom.create("span", {
	              props: {
	                className: "bx-messenger-call-btn-arrow"
	              },
	              events: {
	                click: this._onShowHistoryButtonClick.bind(this)
	              }
	            })]
	          }), this.elements.interceptButton = main_core.Dom.create("span", {
	            props: {
	              className: "im-phone-intercept-button" + (this.callInterceptAllowed ? "" : " im-phone-intercept-button-locked")
	            },
	            text: main_core.Loc.getMessage("IM_PHONE_CALL_VIEW_INTERCEPT"),
	            events: {
	              click: this._onInterceptButtonClick.bind(this)
	            }
	          })]
	        })]
	      });
	    }
	  }, {
	    key: "_onInputKeydown",
	    value: function _onInputKeydown(e) {
	      if (e.keyCode == 13) {
	        this.callbacks.onDial({
	          phoneNumber: this.elements.input.value,
	          lineId: this.selectedLineId
	        });
	      } else if (e.keyCode == 37 || e.keyCode == 39 || e.keyCode == 8 || e.keyCode == 107 || e.keyCode == 46 || e.keyCode == 35 || e.keyCode == 36)
	        // left, right, backspace, num plus, home, end
	        ; else if (e.key === '+' || e.key === '#' || e.key === '*')
	        // +
	        ; else if ((e.keyCode == 67 || e.keyCode == 86 || e.keyCode == 65 || e.keyCode == 88) && (e.metaKey || e.ctrlKey))
	        // ctrl+v/c/a/x
	        ; else if (e.keyCode >= 48 && e.keyCode <= 57 && !e.shiftKey)
	        // 0-9
	        {
	          insertAtCursor(this.elements.input, e.key);
	          e.preventDefault();
	          this.callbacks.onButtonClick({
	            key: e.key
	          });
	        } else if (e.keyCode >= 96 && e.keyCode <= 105 && !e.shiftKey)
	        // extra 0-9
	        {
	          insertAtCursor(this.elements.input, e.key);
	          e.preventDefault();
	          this.callbacks.onButtonClick({
	            key: e.key
	          });
	        } else if (!e.ctrlKey && !e.metaKey && !e.altKey) {
	        e.preventDefault();
	      }
	    }
	  }, {
	    key: "_onAfterNumberChanged",
	    value: function _onAfterNumberChanged() {
	      this.elements.inputContainer.classList.toggle('bx-messenger-calc-panel-active', this.elements.input.value.length > 0);
	      this.elements.input.focus();
	    }
	  }, {
	    key: "_onDeleteButtonClick",
	    value: function _onDeleteButtonClick() {
	      this.elements.input.value = this.elements.input.value.substr(0, this.elements.input.value.length - 1);
	      this._onAfterNumberChanged();
	    }
	  }, {
	    key: "_onDialButtonClick",
	    value: function _onDialButtonClick() {
	      this.callbacks.onDial({
	        phoneNumber: this.elements.input.value,
	        lineId: this.selectedLineId
	      });
	    }
	  }, {
	    key: "_onInterceptButtonClick",
	    value: function _onInterceptButtonClick() {
	      this.callbacks.onIntercept({
	        interceptButton: this.elements.interceptButton
	      });
	    }
	  }, {
	    key: "_onKeyMouseDown",
	    value: function _onKeyMouseDown(key) {
	      var _this2 = this;
	      if (key === '0') {
	        this.plusEntered = false;
	        this.plusKeyTimeout = setTimeout(function () {
	          if (!_this2.elements.input.value.startsWith('+')) {
	            _this2.plusEntered = true;
	            _this2.elements.input.value = '+' + _this2.elements.input.value;
	          }
	        }, 500);
	      }
	    }
	  }, {
	    key: "_onKeyMouseUp",
	    value: function _onKeyMouseUp(key) {
	      if (key === '0') {
	        clearTimeout(this.plusKeyTimeout);
	        if (!this.plusEntered) {
	          insertAtCursor(this.elements.input, '0');
	        }
	        this.plusEntered = false;
	      } else {
	        insertAtCursor(this.elements.input, key);
	      }
	      this._onAfterNumberChanged();
	      this.callbacks.onButtonClick({
	        key: key
	      });
	    }
	  }, {
	    key: "_onShowHistoryButtonClick",
	    value: function _onShowHistoryButtonClick() {
	      var _this3 = this;
	      var menuItems = [];
	      if (!main_core.Type.isArray(this.history) || this.history.length === 0) {
	        return;
	      }
	      this.history.forEach(function (phoneNumber, index) {
	        menuItems.push({
	          id: "history_" + index,
	          text: main_core.Text.encode(phoneNumber),
	          onclick: function onclick() {
	            _this3.historySelectMenu.close();
	            _this3.callbacks.onDial({
	              phoneNumber: phoneNumber,
	              lineId: _this3.selectedLineId
	            });
	          }
	        });
	      });
	      this.historySelectMenu = new main_popup.Menu('phoneCallViewDialHistory', this.elements.historyButton, menuItems, {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 0,
	        zIndex: baseZIndex + 300,
	        bindOptions: {
	          position: 'top'
	        },
	        angle: {
	          offset: 33
	        },
	        closeByEsc: true,
	        overlay: {
	          backgroundColor: 'white',
	          opacity: 0
	        },
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this3.historySelectMenu.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this3.historySelectMenu = null;
	          }
	        }
	      });
	      this.historySelectMenu.show();
	    }
	  }, {
	    key: "_onLineSelectClick",
	    value: function _onLineSelectClick(e) {
	      var _this4 = this;
	      var menuItems = [];
	      this.availableLines.forEach(function (lineId) {
	        menuItems.push({
	          id: "selectLine_" + lineId,
	          text: main_core.Text.encode(_this4.getLineName(lineId)),
	          onclick: function onclick() {
	            _this4.lineSelectMenu.close();
	            _this4.setSelectedLineId(lineId);
	          }
	        });
	      });
	      this.lineSelectMenu = new main_popup.Menu('phoneCallViewSelectLine', this.elements.lineSelector, menuItems, {
	        autoHide: true,
	        zIndex: this.zIndex + 100,
	        closeByEsc: true,
	        bindOptions: {
	          position: 'top'
	        },
	        offsetLeft: 35,
	        angle: {
	          offset: 33
	        },
	        overlay: {
	          backgroundColor: 'white',
	          opacity: 0
	        },
	        maxHeight: 600,
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this4.lineSelectMenu.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this4.lineSelectMenu = null;
	          }
	        }
	      });
	      this.lineSelectMenu.show();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (this.popup) {
	        this.popup.show();
	        this.elements.input.focus();
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (this.lineSelectMenu) {
	        this.lineSelectMenu.destroy();
	      }
	      if (this.historySelectMenu) {
	        this.historySelectMenu.destroy();
	      }
	      if (this.popup) {
	        this.popup.close();
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (this.lineSelectMenu) {
	        this.lineSelectMenu.destroy();
	      }
	      if (this.historySelectMenu) {
	        this.historySelectMenu.destroy();
	      }
	      if (this.popup) {
	        this.popup.destroy();
	      }
	      this.popup = null;
	    }
	  }]);
	  return Keypad;
	}();
	function renderNumber(number, onMouseDown, onMouseUp) {
	  var classSuffix;
	  if (number == '*') {
	    classSuffix = '10';
	  } else if (number == '#') {
	    classSuffix = '11';
	  } else {
	    classSuffix = number;
	  }
	  return main_core.Dom.create("span", {
	    dataset: {
	      'digit': number
	    },
	    props: {
	      className: "bx-messenger-calc-btn bx-messenger-calc-btn-" + classSuffix
	    },
	    children: [main_core.Dom.create("span", {
	      props: {
	        className: 'bx-messenger-calc-btn-num'
	      }
	    })],
	    events: {
	      mousedown: function mousedown(e) {
	        return onMouseDown(e.currentTarget.dataset.digit);
	      },
	      mouseup: function mouseup(e) {
	        return onMouseUp(e.currentTarget.dataset.digit);
	      }
	    }
	  });
	}
	function insertAtCursor(inputElement, value) {
	  if (inputElement.selectionStart || inputElement.selectionStart == '0') {
	    var startPos = inputElement.selectionStart;
	    var endPos = inputElement.selectionEnd;
	    inputElement.value = inputElement.value.substring(0, startPos) + value + inputElement.value.substring(endPos, inputElement.value.length);
	    inputElement.selectionStart = startPos + value.length;
	    inputElement.selectionEnd = startPos + value.length;
	  } else {
	    inputElement.value += value;
	  }
	}

	var callCardEvents = {
	  addCommentButtonClick: 'addCommentButtonClick',
	  muteButtonClick: 'muteButtonClick',
	  holdButtonClick: 'holdButtonClick',
	  transferButtonClick: 'transferButtonClick',
	  cancelTransferButtonClick: 'cancelTransferButtonClick',
	  completeTransferButtonClick: 'completeTransferButtonClick',
	  hangupButtonClick: 'hangupButtonClick',
	  nextButtonClick: 'nextButtonClick',
	  skipButtonClick: 'skipButtonClick',
	  answerButtonClick: 'answerButtonClick',
	  entityChanged: 'entityChanged',
	  qualityMeterClick: 'qualityMeterClick',
	  dialpadButtonClick: 'dialpadButtonClick',
	  makeCallButtonClick: 'makeCallButtonClick',
	  notifyAdminButtonClick: 'notifyAdminButtonClick',
	  closeButtonClick: 'closeButtonClick'
	};
	var UndefinedCallCard = {
	  result: 'error',
	  errorCode: 'Call card is undefined'
	};

	/** @abstract */
	var BaseWorker = /*#__PURE__*/function () {
	  function BaseWorker() {
	    babelHelpers.classCallCheck(this, BaseWorker);
	    babelHelpers.defineProperty(this, "isExternalCall", false);
	    babelHelpers.defineProperty(this, "used", false);
	  }
	  babelHelpers.createClass(BaseWorker, [{
	    key: "initializePlacement",
	    /** @abstract */value: function initializePlacement() {
	      throw new Error('You have to implement the method initializePlacement!');
	    } /** @abstract */
	  }, {
	    key: "initializeInterface",
	    value: function initializeInterface(placement) {
	      throw new Error('You have to implement the method initializeInterface!');
	    } /** @abstract */
	  }, {
	    key: "emitInitializeEvent",
	    value: function emitInitializeEvent(params) {
	      throw new Error('You have to implement the method emitInitializeEvent!');
	    } /** @abstract */
	  }, {
	    key: "emitEvent",
	    value: function emitEvent(name, params) {
	      throw new Error('You have to implement the method emitEvent!');
	    }
	  }, {
	    key: "setCallCard",
	    value: function setCallCard(callCard) {
	      this.CallCard = callCard;
	      return this;
	    }
	  }, {
	    key: "setIsExternalCall",
	    value: function setIsExternalCall(isExternalCall) {
	      this.isExternalCall = isExternalCall;
	      return this;
	    }
	  }, {
	    key: "isCardActive",
	    value: function isCardActive() {
	      return this.CallCard instanceof PhoneCallView;
	    }
	  }, {
	    key: "isUsed",
	    value: function isUsed() {
	      return this.used;
	    }
	  }, {
	    key: "initializeInterfaceEvents",
	    value: function initializeInterfaceEvents(placement) {
	      placement.prototype.events.push('BackgroundCallCard::initialized');
	      placement.prototype.events.push('BackgroundCallCard::addCommentButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::muteButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::holdButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::closeButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::transferButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::cancelTransferButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::completeTransferButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::hangupButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::nextButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::skipButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::answerButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::entityChanged');
	      placement.prototype.events.push('BackgroundCallCard::qualityMeterClick');
	      placement.prototype.events.push('BackgroundCallCard::dialpadButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::makeCallButtonClick');
	      placement.prototype.events.push('BackgroundCallCard::notifyAdminButtonClick');
	      return this;
	    }
	  }, {
	    key: "getEvents",
	    value: function getEvents() {
	      return callCardEvents;
	    }
	  }, {
	    key: "getListUiStates",
	    value: function getListUiStates(params, callback) {
	      this.used = true;
	      callback(Object.keys(UiState).filter(function (state) {
	        switch (state) {
	          case 'sipPhoneError':
	            return false;
	          case 'idle':
	            return false;
	          case 'externalCard':
	            return false;
	          default:
	            return true;
	        }
	      }));
	    }
	  }, {
	    key: "setUiState",
	    value: function setUiState(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (params && params.uiState && UiState[params.uiState]) {
	        this.CallCard.setUiState(UiState[params.uiState]);
	        // BX.onCustomEvent(window, "CallCard::CallStateChanged", [callState, additionalParams]);
	        // this.setOnSlave(desktopEvents.setCallState, [callState, additionalParams]);
	      } else {
	        callback([{
	          result: 'error',
	          errorCode: 'Invalid ui state'
	        }]);
	        return;
	      }
	      if (params.uiState === 'connected') {
	        if (params.disableAutoStartTimer) {
	          this.CallCard.stopTimer();
	          this.hideTimer();
	        } else {
	          this.showTimer();
	        }
	      }
	      if (params.uiState !== 'connected' && !this.CallCard.isTimerStarted()) {
	        this.hideTimer();
	      }
	      callback([]);
	    }
	  }, {
	    key: "setMute",
	    value: function setMute(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (this.CallCard.isMuted() === !!params.muted) {
	        callback([]);
	        return;
	      }
	      if (params.muted) {
	        this.CallCard.setMuted(params.muted);
	        BX.addClass(this.CallCard.elements.buttons.mute, 'active');
	        if (this.CallCard.isDesktop() && this.CallCard.slave) {
	          BX.desktop.onCustomEvent(desktopEvents.onMute, []);
	        } else {
	          this.CallCard.callbacks.mute();
	        }
	      } else {
	        this.CallCard.setMuted(params.muted);
	        BX.removeClass(this.CallCard.elements.buttons.mute, 'active');
	        if (this.CallCard.isDesktop() && this.CallCard.slave) {
	          BX.desktop.onCustomEvent(desktopEvents.onUnMute, []);
	        } else {
	          this.CallCard.callbacks.unmute();
	        }
	      }
	      callback([]);
	    }
	  }, {
	    key: "setHold",
	    value: function setHold(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (this.CallCard.isHeld() === !!params.held) {
	        callback([]);
	        return;
	      }
	      if (params.held) {
	        this.CallCard.setHeld(params.held);
	        BX.addClass(this.CallCard.elements.buttons.hold, 'active');
	        if (this.CallCard.isDesktop() && this.CallCard.slave) {
	          BX.desktop.onCustomEvent(desktopEvents.onHold, []);
	        } else {
	          this.CallCard.callbacks.hold();
	        }
	      } else {
	        this.CallCard.setHeld(params.held);
	        BX.removeClass(this.CallCard.elements.buttons.hold, 'active');
	        if (this.CallCard.isDesktop() && this.CallCard.slave) {
	          BX.desktop.onCustomEvent(desktopEvents.onUnHold, []);
	        } else {
	          this.CallCard.callbacks.unhold();
	        }
	      }
	      callback([]);
	    }
	  }, {
	    key: "startTimer",
	    value: function startTimer(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.showTimer();
	      this.CallCard.startTimer();
	    }
	  }, {
	    key: "stopTimer",
	    value: function stopTimer(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.CallCard.stopTimer();
	    }
	  }, {
	    key: "close",
	    value: function close(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.CallCard.close();
	      callback([]);
	      this.CallCard = false;
	    }
	  }, {
	    key: "setCardTitle",
	    value: function setCardTitle(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.CallCard.setTitle(params.title);
	      callback([]);
	    }
	  }, {
	    key: "setStatusText",
	    value: function setStatusText(params, callback) {
	      this.used = true;
	      if (!this.isCardActive() || !this.isExternalCall) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.CallCard.setStatusText(params.statusText);
	      callback([]);
	    }
	  }, {
	    key: "showTimer",
	    value: function showTimer() {
	      if (!this.CallCard.elements.timer.visible) {
	        this.CallCard.sections.timer.visible = true;
	        this.CallCard.elements.timer.style.display = '';
	        if (this.CallCard.isFolded()) {
	          this.CallCard.unfoldedElements.timer.style.display = '';
	        }
	      }
	    }
	  }, {
	    key: "hideTimer",
	    value: function hideTimer() {
	      if (this.CallCard.sections.timer) {
	        this.CallCard.sections.timer.visible = false;
	      }
	      if (this.CallCard.elements.timer) {
	        this.CallCard.elements.timer.style.display = 'none';
	      }
	      this.CallCard.initialTimestamp = 0;
	    }
	  }, {
	    key: "getUndefinedCallCardError",
	    value: function getUndefinedCallCardError() {
	      return UndefinedCallCard;
	    }
	  }]);
	  return BaseWorker;
	}();

	var desktopMethodEvents = {
	  setUiState: 'DesktopCallCardSetUiState',
	  setMute: 'DesktopCallCardSetMute',
	  setHold: 'DesktopCallCardSetHold',
	  getListUiState: 'DesktopCallCardGetListUiState',
	  setCardTitle: 'DesktopCallCardSetCardTitle',
	  setStatusText: 'DesktopCallCardSetStatusText',
	  close: 'DesktopCallCardClose',
	  startTimer: 'DesktopCallCardStartTimer',
	  stopTimer: 'DesktopCallCardStopTimer'
	};
	var corporatePortalPageEvents = {
	  addCommentButtonClick: 'DesktopCallCardAddCommentButtonClick',
	  muteButtonClick: 'DesktopCallCardMuteButtonClick',
	  holdButtonClick: 'DesktopCallCardHoldButtonClick',
	  transferButtonClick: 'DesktopCallCardTransferButtonClick',
	  cancelTransferButtonClick: 'DesktopCallCardCancelTransferButtonClick',
	  completeTransferButtonClick: 'DesktopCallCardCompleteTransferButtonClick',
	  hangupButtonClick: 'DesktopCallCardHangupButtonClick',
	  nextButtonClick: 'DesktopCallCardNextButtonClick',
	  skipButtonClick: 'DesktopCallCardSkipButtonClick',
	  answerButtonClick: 'DesktopCallCardAnswerButtonClick',
	  entityChanged: 'DesktopCallCardEntityChanged',
	  qualityMeterClick: 'DesktopCallCardQualityMeterClick',
	  dialpadButtonClick: 'DesktopCallCardDialpadButtonClick',
	  makeCallButtonClick: 'DesktopCallCardMakeCallButtonClick',
	  notifyAdminButtonClick: 'DesktopCallCardNotifyAdminButtonClick'
	};
	var DesktopWorker = /*#__PURE__*/function (_BaseWorker) {
	  babelHelpers.inherits(DesktopWorker, _BaseWorker);
	  function DesktopWorker() {
	    var _this;
	    babelHelpers.classCallCheck(this, DesktopWorker);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DesktopWorker).call(this));
	    _this.eventHandlers = [];
	    return _this;
	  }
	  babelHelpers.createClass(DesktopWorker, [{
	    key: "isCardActive",
	    value: function isCardActive() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(DesktopWorker.prototype), "isCardActive", this).call(this) || this.CallCard !== null;
	    }
	  }, {
	    key: "initializePlacement",
	    value: function initializePlacement() {
	      var placement = BX.rest.AppLayout.initializePlacement('PAGE_BACKGROUND_WORKER');
	      this.initializeInterfaceMethods(placement);
	      this.initializeInterfaceEvents(placement);
	      this.addEventHandlersForEventsFromCallCard();
	    }
	  }, {
	    key: "initializeInterface",
	    value: function initializeInterface(placement) {
	      this.initializeInterfaceMethods(placement);
	      this.addTransmitEventHandlers();
	    }
	  }, {
	    key: "initializeInterfaceMethods",
	    value: function initializeInterfaceMethods(placement) {
	      var _this2 = this;
	      placement.prototype.CallCardGetListUiStates = function (params, callback) {
	        return _this2.getListUiStates(params, callback);
	      };
	      placement.prototype.CallCardSetMute = function (params, callback) {
	        return _this2.transmitSetMute(params, callback);
	      };
	      placement.prototype.CallCardSetHold = function (params, callback) {
	        return _this2.transmitSetHold(params, callback);
	      };
	      placement.prototype.CallCardSetUiState = function (params, callback) {
	        return _this2.transmitSetUiState(params, callback);
	      };
	      placement.prototype.CallCardSetCardTitle = function (params, callback) {
	        return _this2.transmitSetCardTitle(params, callback);
	      };
	      placement.prototype.CallCardSetStatusText = function (params, callback) {
	        return _this2.transmitSetStatusText(params, callback);
	      };
	      placement.prototype.CallCardClose = function (params, callback) {
	        return _this2.transmitClose(params, callback);
	      };
	      placement.prototype.CallCardStartTimer = function (params, callback) {
	        return _this2.transmitStartTimer(params, callback);
	      };
	      placement.prototype.CallCardStopTimer = function (params, callback) {
	        return _this2.transmitStopTimer(params, callback);
	      };
	    }
	  }, {
	    key: "emitInitializeEvent",
	    value: function emitInitializeEvent(params) {
	      if (!this.isExternalCall) {
	        return;
	      }
	      if (this.isCallCardPage()) {
	        BXDesktopSystem.BroadcastEvent('DesktopCallCardInitialized', [params]);
	        return;
	      }
	      BX.onCustomEvent(window, "BackgroundCallCard::initialized", [params]);
	    }
	  }, {
	    key: "emitEvent",
	    value: function emitEvent(name, params) {
	      if (!this.isExternalCall) {
	        return;
	      }
	      if (this.isCallCardPage()) {
	        var desktopEventName = 'DesktopCallCard' + (name[0].toUpperCase() + name.slice(1));
	        BXDesktopSystem.BroadcastEvent(desktopEventName, [params]);
	      }
	      BX.onCustomEvent(window, 'BackgroundCallCard::' + name, [params]);
	    }
	  }, {
	    key: "removeDesktopEventHandlers",
	    value: function removeDesktopEventHandlers() {
	      for (var event in this.getEvents()) {
	        this.removeCustomEvents('DesktopCallCard' + (event[0].toUpperCase() + event.slice(1)));
	      }
	      this.removeCustomEvents('DesktopCallCardInitialized');
	      this.removeCustomEvents('DesktopCallCardCloseButtonClick');
	    }
	    /*
	     Transmit an event about changing the call card by calling methods
	     from the rest application from the corporate portal window
	     */
	    //region Transmit events
	  }, {
	    key: "addTransmitEventHandlers",
	    value: function addTransmitEventHandlers() {
	      var _this3 = this;
	      this.addCustomEvent(desktopMethodEvents.getListUiState, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getListUiStates);
	      }).addCustomEvent(desktopMethodEvents.setUiState, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().setUiState);
	      }).addCustomEvent(desktopMethodEvents.setMute, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().setMute);
	      }).addCustomEvent(desktopMethodEvents.setHold, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().setHold);
	      }).addCustomEvent(desktopMethodEvents.setCardTitle, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().setCardTitle);
	      }).addCustomEvent(desktopMethodEvents.setStatusText, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().setStatusText);
	      }).addCustomEvent(desktopMethodEvents.close, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().close);
	      }).addCustomEvent(desktopMethodEvents.startTimer, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().startTimer);
	      }).addCustomEvent(desktopMethodEvents.stopTimer, function (params, callback) {
	        return _this3.onTransmitHandler(params, callback, _this3.getCallCardPlatformWorker().stopTimer);
	      });
	    }
	  }, {
	    key: "onTransmitHandler",
	    value: function onTransmitHandler(params, callback, handler) {
	      if (this.isCorporatePortalPage()) {
	        return;
	      }
	      this.used = true;
	      callback = typeof callback === 'function' ? callback : BX.DoNothing;
	      handler(params, callback);
	    }
	  }, {
	    key: "transmitSetUiState",
	    value: function transmitSetUiState(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (!params.hasOwnProperty('uiState') || !UiState[params.uiState]) {
	        callback([{
	          result: 'error',
	          errorCode: 'Invalid ui state'
	        }]);
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setUiState, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitSetMute",
	    value: function transmitSetMute(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (!params.hasOwnProperty('muted')) {
	        callback({
	          result: 'error',
	          errorCode: 'missing field muted'
	        });
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setMute, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitSetHold",
	    value: function transmitSetHold(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (!params.hasOwnProperty('held')) {
	        callback([{
	          result: 'error',
	          errorCode: 'missing field held'
	        }]);
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setHold, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitStartTimer",
	    value: function transmitStartTimer(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.startTimer, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitStopTimer",
	    value: function transmitStopTimer(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.stopTimer, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitClose",
	    value: function transmitClose(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      this.CallCard = null;
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.close, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitSetCardTitle",
	    value: function transmitSetCardTitle(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (!params.hasOwnProperty('title')) {
	        callback([{
	          result: 'error',
	          errorCode: 'missing field title'
	        }]);
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setCardTitle, [params, BX.DoNothing]);
	      callback([]);
	    }
	  }, {
	    key: "transmitSetStatusText",
	    value: function transmitSetStatusText(params, callback) {
	      if (!this.isCardActive()) {
	        callback([this.getUndefinedCallCardError()]);
	        return;
	      }
	      if (!params.hasOwnProperty('statusText')) {
	        callback([{
	          result: 'error',
	          errorCode: 'missing field statusText'
	        }]);
	        return;
	      }
	      BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setStatusText, [params, BX.DoNothing]);
	      callback([]);
	    } //endregion
	  }, {
	    key: "addEventHandlersForEventsFromCallCard",
	    value: function addEventHandlersForEventsFromCallCard() {
	      var _this4 = this;
	      if (!this.isCorporatePortalPage()) {
	        return;
	      }
	      var _loop = function _loop(event) {
	        _this4.addCustomEvent(corporatePortalPageEvents[event], function (params, callback) {
	          BX.onCustomEvent(window, 'BackgroundCallCard::' + event, [params, callback]);
	        });
	      };
	      for (var event in this.getEvents()) {
	        _loop(event);
	      }
	      this.addCustomEvent('DesktopCallCardInitialized', function (params, callback) {
	        if (!_this4.CallCard) {
	          _this4.CallCard = true;
	        }
	        BX.onCustomEvent(window, 'BackgroundCallCard::initialized', [params, callback]);
	      });
	      this.addCustomEvent('DesktopCallCardCloseButtonClick', function (params, callback) {
	        _this4.CallCard = null;
	        BX.onCustomEvent(window, 'BackgroundCallCard::closeButtonClick', [params, callback]);
	      });
	    }
	  }, {
	    key: "addCustomEvent",
	    value: function addCustomEvent(eventName, eventHandler) {
	      var realHandler = function realHandler(e) {
	        var arEventParams = [];
	        for (var i in e.detail) {
	          arEventParams.push(e.detail[i]);
	        }
	        eventHandler.apply(window, arEventParams);
	      };
	      if (!this.eventHandlers[eventName]) {
	        this.eventHandlers[eventName] = [];
	      }
	      this.eventHandlers[eventName].push(realHandler);
	      window.addEventListener(eventName, realHandler);
	      return this;
	    }
	  }, {
	    key: "removeCustomEvents",
	    value: function removeCustomEvents(eventName) {
	      if (!this.eventHandlers[eventName]) {
	        return false;
	      }
	      this.eventHandlers[eventName].forEach(function (eventHandler) {
	        return window.removeEventListener(eventName, eventHandler);
	      });
	      this.eventHandlers[eventName] = [];
	    }
	  }, {
	    key: "isCallCardPage",
	    value: function isCallCardPage() {
	      return BXDesktopWindow.GetWindowId() !== BXDesktopSystem.GetMainWindow().GetWindowId();
	    }
	  }, {
	    key: "isCorporatePortalPage",
	    value: function isCorporatePortalPage() {
	      return typeof BXDesktopSystem == "undefined" && typeof BXDesktopWindow == "undefined";
	    }
	  }, {
	    key: "getCallCardWindow",
	    value: function getCallCardWindow() {
	      return BXWindows.find(function (element) {
	        return element.name === 'callWindow';
	      });
	    }
	  }, {
	    key: "getCallCardPlatformWorker",
	    value: function getCallCardPlatformWorker() {
	      var callWindow = this.getCallCardWindow();
	      return callWindow.PCW.backgroundWorker.platformWorker;
	    }
	  }]);
	  return DesktopWorker;
	}(BaseWorker);

	var BrowserWorker = /*#__PURE__*/function (_BaseWorker) {
	  babelHelpers.inherits(BrowserWorker, _BaseWorker);
	  function BrowserWorker() {
	    babelHelpers.classCallCheck(this, BrowserWorker);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BrowserWorker).apply(this, arguments));
	  }
	  babelHelpers.createClass(BrowserWorker, [{
	    key: "initializePlacement",
	    value: function initializePlacement() {
	      var placement = BX.rest.AppLayout.initializePlacement('PAGE_BACKGROUND_WORKER');
	      this.initializeInterface(placement);
	      this.initializeInterfaceEvents(placement);
	    }
	  }, {
	    key: "initializeInterface",
	    value: function initializeInterface(placement) {
	      var _this = this;
	      placement.prototype.CallCardSetMute = function (params, callback) {
	        return _this.setMute(params, callback);
	      };
	      placement.prototype.CallCardSetHold = function (params, callback) {
	        return _this.setHold(params, callback);
	      };
	      placement.prototype.CallCardSetUiState = function (params, callback) {
	        return _this.setUiState(params, callback);
	      };
	      placement.prototype.CallCardGetListUiStates = function (params, callback) {
	        return _this.getListUiStates(params, callback);
	      };
	      placement.prototype.CallCardSetCardTitle = function (params, callback) {
	        return _this.setCardTitle(params, callback);
	      };
	      placement.prototype.CallCardSetStatusText = function (params, callback) {
	        return _this.setStatusText(params, callback);
	      };
	      placement.prototype.CallCardClose = function (params, callback) {
	        return _this.close(params, callback);
	      };
	      placement.prototype.CallCardStartTimer = function (params, callback) {
	        return _this.startTimer(params, callback);
	      };
	      placement.prototype.CallCardStopTimer = function (params, callback) {
	        return _this.stopTimer(params, callback);
	      };
	    }
	  }, {
	    key: "emitEvent",
	    value: function emitEvent(name, params) {
	      if (!this.isExternalCall) {
	        return;
	      }
	      BX.onCustomEvent(window, 'BackgroundCallCard::' + name, [params]);
	    }
	  }, {
	    key: "emitInitializeEvent",
	    value: function emitInitializeEvent(params) {
	      if (!this.isExternalCall) {
	        return;
	      }
	      BX.onCustomEvent(window, "BackgroundCallCard::initialized", [params]);
	    }
	  }]);
	  return BrowserWorker;
	}(BaseWorker);

	var backgroundWorkerEvents = callCardEvents;
	var BackgroundWorker = /*#__PURE__*/function () {
	  function BackgroundWorker() {
	    babelHelpers.classCallCheck(this, BackgroundWorker);
	    this.initializePlacement();
	  }
	  babelHelpers.createClass(BackgroundWorker, [{
	    key: "setCallCard",
	    value: function setCallCard(callCard) {
	      this.platformWorker.CallCard = callCard;
	    }
	  }, {
	    key: "initializePlacement",
	    value: function initializePlacement() {
	      if (this.isDesktop()) {
	        this.platformWorker = new DesktopWorker();
	      } else {
	        this.platformWorker = new BrowserWorker();
	      }
	      this.platformWorker.initializePlacement();
	    }
	  }, {
	    key: "emitEvent",
	    value: function emitEvent(name, params) {
	      this.platformWorker.emitEvent(name, params);
	    }
	  }, {
	    key: "removeDesktopEventHandlers",
	    value: function removeDesktopEventHandlers() {
	      this.platformWorker.removeDesktopEventHandlers();
	    }
	  }, {
	    key: "isDesktop",
	    value: function isDesktop() {
	      return typeof BXDesktopSystem !== 'undefined';
	    }
	  }, {
	    key: "isUsed",
	    value: function isUsed() {
	      return this.platformWorker.isUsed();
	    }
	  }, {
	    key: "isActiveIntoCurrentCall",
	    value: function isActiveIntoCurrentCall() {
	      return this.isExternalCall && this.isUsed();
	    }
	  }, {
	    key: "setExternalCall",
	    value: function setExternalCall(isExternalCall) {
	      this.platformWorker.setIsExternalCall(isExternalCall);
	    }
	  }]);
	  return BackgroundWorker;
	}();

	var nop$1 = function nop() {};
	var FormManager = /*#__PURE__*/function () {
	  function FormManager(params) {
	    babelHelpers.classCallCheck(this, FormManager);
	    this.node = params.node;
	    this.currentForm = null;
	    this.callbacks = {
	      onFormLoad: main_core.Type.isFunction(params.onFormLoad) ? params.onFormLoad : nop$1,
	      onFormUnLoad: main_core.Type.isFunction(params.onFormUnLoad) ? params.onFormUnLoad : nop$1,
	      onFormSend: main_core.Type.isFunction(params.onFormSend) ? params.onFormSend : nop$1
	    };
	  }

	  /**
	   * @param {object} params
	   * @param {int} params.id
	   * @param {string} params.secCode
	   */
	  babelHelpers.createClass(FormManager, [{
	    key: "load",
	    value: function load(params) {
	      var formData = this.getFormData(params);
	      window.Bitrix24FormLoader.load(formData);
	      this.currentForm = formData;
	    }
	  }, {
	    key: "unload",
	    value: function unload() {
	      if (this.currentForm) {
	        window.Bitrix24FormLoader.unload(this.currentForm);
	        this.currentForm = null;
	      }
	    }
	  }, {
	    key: "getFormData",
	    /**
	     * @param {object} params
	     * @param {int} params.id
	     * @param {string} params.secCode
	     * @returns {object}
	     */
	    value: function getFormData(params) {
	      return {
	        id: params.id,
	        sec: params.secCode,
	        type: 'inline',
	        lang: 'ru',
	        ref: window.location.href,
	        node: this.node,
	        handlers: {
	          'load': this._onFormLoad.bind(this),
	          'unload': this._onFormUnLoad.bind(this),
	          'send': this.onFormSend.bind(this)
	        },
	        options: {
	          'borders': false,
	          'logo': false
	        }
	      };
	    }
	  }, {
	    key: "_onFormLoad",
	    value: function _onFormLoad(form) {
	      this.callbacks.onFormLoad(form);
	    }
	  }, {
	    key: "_onFormUnLoad",
	    value: function _onFormUnLoad(form) {
	      this.callbacks.onFormUnLoad(form);
	    }
	  }, {
	    key: "onFormSend",
	    value: function onFormSend(form) {
	      this.callbacks.onFormSend(form);
	    }
	  }]);
	  return FormManager;
	}();

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var CallList = /*#__PURE__*/function () {
	  function CallList(params) {
	    babelHelpers.classCallCheck(this, CallList);
	    this.node = params.node;
	    this.id = params.id;
	    this.isDesktop = params.isDesktop;
	    this.entityType = '';
	    this.statuses = new Map(); // {STATUS_ID (string): { STATUS_NAME; string, CLASS: string, ITEMS: []}
	    this.elements = {};
	    this.currentStatusId = params.callListStatusId || 'IN_WORK';
	    this.currentItemIndex = params.itemIndex || 0;
	    this.callingStatusId = null;
	    this.callingItemIndex = null;
	    this.selectionLocked = false;

	    // this.itemActionMenu = null;
	    this.callbacks = {
	      onError: main_core.Type.isFunction(params.onError) ? params.onError : nop,
	      onSelectedItem: main_core.Type.isFunction(params.onSelectedItem) ? params.onSelectedItem : nop
	    };
	    this.showLimit = 10;
	    this.showDelta = 10;
	  }
	  babelHelpers.createClass(CallList, [{
	    key: "init",
	    value: function init(next) {
	      var _this = this;
	      if (!main_core.Type.isFunction(next)) {
	        next = nop;
	      }
	      this.load(function () {
	        var currentStatus = _this.statuses.get(_this.currentStatusId);
	        if (currentStatus && currentStatus.ITEMS.length > 0) {
	          _this.update();
	          _this.selectItem(_this.currentStatusId, _this.currentItemIndex);
	          next();
	        } else {
	          BX.debug('empty call list. don\'t know what to do');
	        }
	      });
	    }
	  }, {
	    key: "reinit",
	    /**
	     * @param {object} params
	     * @param {Node} params.node DOM node to render call list.
	     */
	    value: function reinit(params) {
	      if (main_core.Type.isDomNode(params.node)) {
	        this.node = params.node;
	      }
	      this.update();
	      this.selectItem(this.currentStatusId, this.currentItemIndex);
	      if (this.callingStatusId !== null && this.callingItemIndex !== null) {
	        this.setCallingElement(this.callingStatusId, this.callingItemIndex);
	      }
	    }
	  }, {
	    key: "load",
	    value: function load(next) {
	      var _this2 = this;
	      var params = {
	        'sessid': BX.bitrix_sessid(),
	        'ajax_action': 'GET_CALL_LIST',
	        'callListId': this.id
	      };
	      BX.ajax({
	        url: CallList.getAjaxUrl(),
	        method: 'POST',
	        dataType: 'json',
	        data: params,
	        onsuccess: function onsuccess(data) {
	          if (!data.ERROR) {
	            if (main_core.Type.isArray(data.STATUSES)) {
	              //this.statuses = data.STATUSES;
	              data.STATUSES.forEach(function (statusRecord) {
	                statusRecord.ITEMS = [];
	                _this2.statuses.set(statusRecord.STATUS_ID, statusRecord);
	              });
	              data.ITEMS.forEach(function (item) {
	                var itemStatus = _this2.statuses.get(item.STATUS_ID);
	                if (itemStatus) {
	                  itemStatus.ITEMS.push(item);
	                }
	              });
	            }
	            _this2.entityType = data.ENTITY_TYPE;
	            var currentStatus = _this2.statuses.get(_this2.currentStatusId);
	            if (currentStatus && currentStatus.ITEMS.length === 0) {
	              _this2.currentStatusId = _this2.getNonEmptyStatusId();
	              _this2.currentItemIndex = 0;
	            }
	            next();
	          } else {
	            console.log(data);
	          }
	        }
	      });
	    }
	  }, {
	    key: "selectItem",
	    value: function selectItem(statusId, newIndex) {
	      var currentNode = this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex]._node;
	      BX.removeClass(currentNode, 'im-phone-call-list-customer-block-active');
	      if (this.itemActionMenu) {
	        this.itemActionMenu.close();
	      }
	      this.currentStatusId = statusId;
	      this.currentItemIndex = newIndex;
	      currentNode = this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex]._node;
	      BX.addClass(currentNode, 'im-phone-call-list-customer-block-active');
	      var newEntity = this.statuses.get(statusId).ITEMS[newIndex];
	      if ((this.entityType == 'DEAL' || this.entityType == 'QUOTE' || this.entityType == 'INVOICE') && newEntity.ASSOCIATED_ENTITY) {
	        this.callbacks.onSelectedItem({
	          type: newEntity.ASSOCIATED_ENTITY.TYPE,
	          id: newEntity.ASSOCIATED_ENTITY.ID,
	          bindings: [{
	            type: this.entityType,
	            id: newEntity.ELEMENT_ID
	          }],
	          phones: newEntity.ASSOCIATED_ENTITY.PHONES,
	          statusId: statusId,
	          index: newIndex
	        });
	      } else {
	        this.callbacks.onSelectedItem({
	          type: this.entityType,
	          id: newEntity.ELEMENT_ID,
	          phones: newEntity.PHONES,
	          statusId: statusId,
	          index: newIndex
	        });
	      }
	    }
	  }, {
	    key: "moveToNextItem",
	    value: function moveToNextItem() {
	      var newIndex = this.currentItemIndex + 1;
	      if (newIndex >= this.statuses.get(this.currentStatusId).ITEMS.length) {
	        newIndex = 0;
	      }
	      this.selectItem(this.currentStatusId, newIndex);
	    }
	  }, {
	    key: "setCallingElement",
	    value: function setCallingElement(statusId, index) {
	      this.callingStatusId = statusId;
	      this.callingItemIndex = index;
	      var currentNode = this.statuses.get(this.callingStatusId).ITEMS[this.callingItemIndex]._node;
	      BX.addClass(currentNode, 'im-phone-call-list-customer-block-calling');
	      this.selectionLocked = true;
	    }
	  }, {
	    key: "resetCallingElement",
	    value: function resetCallingElement() {
	      if (this.callingStatusId === null || this.callingItemIndex === null) {
	        return;
	      }
	      var currentNode = this.statuses.get(this.callingStatusId).ITEMS[this.callingItemIndex]._node;
	      BX.removeClass(currentNode, 'im-phone-call-list-customer-block-calling');
	      this.callingStatusId = null;
	      this.callingItemIndex = null;
	      this.selectionLocked = false;
	    }
	  }, {
	    key: "update",
	    value: function update() {
	      main_core.Dom.clean(this.node);
	      this.node.append(this.render());
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-list-container'
	        },
	        children: this.renderStatusBlocks()
	      });
	    }
	  }, {
	    key: "renderStatusBlocks",
	    value: function renderStatusBlocks() {
	      var result = [];
	      var _iterator = _createForOfIteratorHelper(this.statuses),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _step$value = babelHelpers.slicedToArray(_step.value, 2),
	            statusId = _step$value[0],
	            status = _step$value[1];
	          if (!status || status.ITEMS.length === 0) {
	            continue;
	          }
	          status._node = this.renderStatusBlock(status);
	          result.push(status._node);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return result;
	    }
	  }, {
	    key: "renderCallListItems",
	    value: function renderCallListItems(statusId) {
	      var result = [];
	      var status = this.statuses.get(statusId);
	      if (status._shownCount > 0) {
	        if (status._shownCount > status.ITEMS.length) {
	          status._shownCount = status.ITEMS.length;
	        }
	      } else {
	        status._shownCount = Math.min(this.showLimit, status.ITEMS.length);
	      }
	      for (var i = 0; i < status._shownCount; i++) {
	        result.push(this.renderCallListItem(status.ITEMS[i], statusId, i));
	      }
	      if (status.ITEMS.length > status._shownCount) {
	        status._showMoreNode = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-show-more-wrap'
	          },
	          children: [main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-call-list-show-more-button'
	            },
	            dataset: {
	              statusId: statusId
	            },
	            text: main_core.Loc.getMessage('IM_PHONE_CALL_LIST_MORE').replace('#COUNT#', status.ITEMS.length - status._shownCount),
	            events: {
	              click: this.onShowMoreClick.bind(this)
	            }
	          })]
	        });
	        result.push(status._showMoreNode);
	      } else {
	        status._showMoreNode = null;
	      }
	      return result;
	    }
	  }, {
	    key: "renderCallListItem",
	    value: function renderCallListItem(itemDescriptor, statusId, itemIndex) {
	      var _this3 = this;
	      var statusName = this.statuses.get(statusId).NAME;
	      var phonesText = '';
	      if (main_core.Type.isArray(itemDescriptor.PHONES)) {
	        itemDescriptor.PHONES.forEach(function (phone, index) {
	          if (index !== 0) {
	            phonesText += '; ';
	          }
	          phonesText += main_core.Text.encode(phone.VALUE);
	        });
	      }
	      itemDescriptor._node = main_core.Dom.create("div", {
	        props: {
	          className: this.currentStatusId == statusId && this.currentItemIndex == itemIndex ? 'im-phone-call-list-customer-block im-phone-call-list-customer-block-active' : 'im-phone-call-list-customer-block'
	        },
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-customer-block-action'
	          },
	          children: [main_core.Dom.create("span", {
	            text: statusName
	          })],
	          events: {
	            click: function click(e) {
	              e.preventDefault();
	              if (_this3.itemActionMenu) {
	                _this3.itemActionMenu.close();
	              } else {
	                _this3.showItemMenu(itemDescriptor, e.target);
	              }
	            }
	          }
	        }), main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-name' + (itemDescriptor.ASSOCIATED_ENTITY ? ' im-phone-call-list-connection-line' : '')
	          },
	          children: [main_core.Dom.create("a", {
	            attrs: {
	              href: itemDescriptor.EDIT_URL,
	              target: '_blank'
	            },
	            props: {
	              className: 'im-phone-call-list-item-customer-link'
	            },
	            text: itemDescriptor.NAME,
	            events: {
	              click: function click(e) {
	                e.preventDefault();
	                window.open(itemDescriptor.EDIT_URL);
	              }
	            }
	          })]
	        }), itemDescriptor.POST ? main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: itemDescriptor.POST
	        }) : null, itemDescriptor.COMPANY_TITLE ? main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: itemDescriptor.COMPANY_TITLE
	        }) : null, phonesText ? main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: phonesText
	        }) : null, itemDescriptor.ASSOCIATED_ENTITY ? this.renderAssociatedEntity(itemDescriptor.ASSOCIATED_ENTITY) : null],
	        events: {
	          click: function click() {
	            if (!_this3.selectionLocked && (_this3.currentStatusId != itemDescriptor.STATUS_ID || _this3.currentItemIndex != itemIndex)) {
	              _this3.selectItem(itemDescriptor.STATUS_ID, itemIndex);
	            }
	          }
	        }
	      });
	      return itemDescriptor._node;
	    }
	  }, {
	    key: "renderAssociatedEntity",
	    value: function renderAssociatedEntity(associatedEntity) {
	      var phonesText = '';
	      if (main_core.Type.isArray(associatedEntity.PHONES)) {
	        associatedEntity.PHONES.forEach(function (phone, index) {
	          if (index !== 0) {
	            phonesText += '; ';
	          }
	          phonesText += main_core.Text.encode(phone.VALUE);
	        });
	      }
	      return main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-list-item-customer-entity im-phone-call-list-connection-line-item'
	        },
	        children: [main_core.Dom.create("a", {
	          attrs: {
	            href: associatedEntity.EDIT_URL,
	            target: '_blank'
	          },
	          props: {
	            className: 'im-phone-call-list-item-customer-link'
	          },
	          text: associatedEntity.NAME,
	          events: {
	            click: function click(e) {
	              e.preventDefault();
	              window.open(associatedEntity.EDIT_URL);
	            }
	          }
	        }), main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: associatedEntity.POST
	        }), main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: associatedEntity.COMPANY_TITLE
	        }), phonesText ? main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-item-customer-info'
	          },
	          text: phonesText
	        }) : null]
	      });
	    }
	  }, {
	    key: "onShowMoreClick",
	    value: function onShowMoreClick(e) {
	      var statusId = e.target.dataset.statusId;
	      var status = this.statuses.get(statusId);
	      status._shownCount += this.showDelta;
	      if (status._shownCount > status.ITEMS.length) {
	        status._shownCount = status.ITEMS.length;
	      }
	      var newStatusNode = this.renderStatusBlock(status);
	      status._node.parentNode.replaceChild(newStatusNode, status._node);
	      status._node = newStatusNode;
	    }
	  }, {
	    key: "showItemMenu",
	    value: function showItemMenu(callListItem, node) {
	      var _this4 = this;
	      var menuItems = [];
	      var menuItem;
	      var _iterator2 = _createForOfIteratorHelper(this.statuses),
	        _step2;
	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var _step2$value = babelHelpers.slicedToArray(_step2.value, 2),
	            statusId = _step2$value[0],
	            status = _step2$value[1];
	          menuItem = {
	            id: "setStatus_" + statusId,
	            text: status.NAME,
	            onclick: this.actionMenuItemClickHandler(callListItem.ELEMENT_ID, statusId).bind(this)
	          };
	          menuItems.push(menuItem);
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	      menuItems.push({
	        id: 'callListItemActionMenu_delimiter',
	        delimiter: true
	      });
	      menuItems.push({
	        id: "defer15min",
	        text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_DEFER_15_MIN'),
	        onclick: function onclick() {
	          _this4.itemActionMenu.close();
	          _this4.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 35);
	        }
	      });
	      menuItems.push({
	        id: "defer1hour",
	        text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_DEFER_HOUR'),
	        onclick: function onclick() {
	          _this4.itemActionMenu.close();
	          _this4.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 185);
	        }
	      });
	      menuItems.push({
	        id: "moveToEnd",
	        text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_TO_END'),
	        onclick: function onclick() {
	          _this4.itemActionMenu.close();
	          _this4.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 5100);
	        }
	      });
	      this.itemActionMenu = new main_popup.Menu('callListItemActionMenu', node, menuItems, {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 0,
	        angle: {
	          position: "top"
	        },
	        zIndex: baseZIndex + 200,
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this4.itemActionMenu.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this4.itemActionMenu = null;
	          }
	        }
	      });
	      this.itemActionMenu.show();
	    }
	  }, {
	    key: "actionMenuItemClickHandler",
	    value: function actionMenuItemClickHandler(elementId, statusId) {
	      var _this5 = this;
	      return function () {
	        _this5.itemActionMenu.close();
	        _this5.setElementStatus(elementId, statusId);
	      };
	    }
	  }, {
	    key: "setElementRank",
	    value: function setElementRank(elementId, rank) {
	      var _this6 = this;
	      this.executeItemAction({
	        action: 'SET_ELEMENT_RANK',
	        parameters: {
	          callListId: this.id,
	          elementId: elementId,
	          rank: rank
	        },
	        successCallback: function successCallback(data) {
	          if (data.ITEMS) {
	            _this6.repopulateItems(data.ITEMS);
	            _this6.update();
	          }
	        }
	      });
	    }
	  }, {
	    key: "setElementStatus",
	    value: function setElementStatus(elementId, statusId) {
	      var _this7 = this;
	      this.executeItemAction({
	        action: 'SET_ELEMENT_STATUS',
	        parameters: {
	          callListId: this.id,
	          elementId: elementId,
	          statusId: statusId
	        },
	        successCallback: function successCallback(data) {
	          _this7.repopulateItems(data.ITEMS);
	          _this7.update();
	        }
	      });
	    }
	  }, {
	    key: "setWebformResult",
	    /**
	     * @param {int} elementId
	     * @param {int} webformResultId
	     */
	    value: function setWebformResult(elementId, webformResultId) {
	      this.executeItemAction({
	        action: 'SET_WEBFORM_RESULT',
	        parameters: {
	          callListId: this.id,
	          elementId: elementId,
	          webformResultId: webformResultId
	        }
	      });
	    }
	  }, {
	    key: "executeItemAction",
	    value: function executeItemAction(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (!main_core.Type.isFunction(params.successCallback)) {
	        params.successCallback = nop;
	      }
	      var requestParams = {
	        'sessid': BX.bitrix_sessid(),
	        'ajax_action': params.action,
	        'parameters': params.parameters
	      };
	      BX.ajax({
	        url: CallList.getAjaxUrl(),
	        method: 'POST',
	        dataType: 'json',
	        data: requestParams,
	        onsuccess: function onsuccess(data) {
	          return params.successCallback(data);
	        }
	      });
	    }
	  }, {
	    key: "repopulateItems",
	    value: function repopulateItems(items) {
	      var _this8 = this;
	      var _iterator3 = _createForOfIteratorHelper(this.statuses),
	        _step3;
	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var _step3$value = babelHelpers.slicedToArray(_step3.value, 2),
	            statusId = _step3$value[0],
	            status = _step3$value[1];
	          status.ITEMS = [];
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }
	      items.forEach(function (item) {
	        return _this8.statuses.get(item.STATUS_ID).ITEMS.push(item);
	      });
	      if (this.statuses.get(this.currentStatusId).ITEMS.length === 0) {
	        this.currentStatusId = this.getNonEmptyStatusId();
	        this.currentItemIndex = 0;
	      } else {
	        if (this.currentItemIndex >= this.statuses.get(this.currentStatusId).ITEMS.length) {
	          this.currentItemIndex = 0;
	        }
	      }
	      this.selectItem(this.currentStatusId, this.currentItemIndex);
	    }
	  }, {
	    key: "getNonEmptyStatusId",
	    value: function getNonEmptyStatusId() {
	      var foundStatusId = false;
	      var _iterator4 = _createForOfIteratorHelper(this.statuses),
	        _step4;
	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var _step4$value = babelHelpers.slicedToArray(_step4.value, 2),
	            statusId = _step4$value[0],
	            status = _step4$value[1];
	          if (status.ITEMS.length > 0) {
	            foundStatusId = statusId;
	            break;
	          }
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }
	      return foundStatusId;
	    }
	  }, {
	    key: "getCurrentElement",
	    value: function getCurrentElement() {
	      return this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex];
	    }
	  }, {
	    key: "getStatusTitle",
	    value: function getStatusTitle(statusId) {
	      var count = this.statuses.get(statusId).ITEMS.length;
	      return main_core.Text.encode(this.statuses.get(statusId).NAME) + ' (' + count.toString() + ')';
	    }
	  }, {
	    key: "renderStatusBlock",
	    value: function renderStatusBlock(status) {
	      var animationTimeout;
	      var itemsNode;
	      var measuringNode;
	      var statusId = status.STATUS_ID;
	      if (!status.hasOwnProperty('_folded')) {
	        status._folded = false;
	      }
	      var className = 'im-phone-call-list-block';
	      if (status.CLASS != '') {
	        className = className + ' ' + status.CLASS;
	      }
	      return main_core.Dom.create("div", {
	        props: {
	          className: className
	        },
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-block-title' + (status._folded ? '' : ' active')
	          },
	          children: [main_core.Dom.create("span", {
	            text: this.getStatusTitle(statusId)
	          }), main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-list-block-title-arrow'
	            }
	          })],
	          events: {
	            click: function click(e) {
	              e.preventDefault();
	              clearTimeout(animationTimeout);
	              status._folded = !status._folded;
	              if (status._folded) {
	                BX.removeClass(e.target, 'active');
	                itemsNode.style.height = measuringNode.clientHeight.toString() + 'px';
	                animationTimeout = setTimeout(function () {
	                  itemsNode.style.height = 0;
	                }, 50);
	              } else {
	                BX.addClass(e.target, 'active');
	                itemsNode.style.height = 0;
	                animationTimeout = setTimeout(function () {
	                  itemsNode.style.height = measuringNode.clientHeight + 'px';
	                }, 50);
	              }
	            }
	          }
	        }), itemsNode = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-list-items-block'
	          },
	          children: [measuringNode = main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-list-items-measuring'
	            },
	            children: this.renderCallListItems(statusId)
	          })],
	          events: {
	            transitionend: function transitionend() {
	              if (!status._folded) {
	                itemsNode.style.removeProperty('height');
	              }
	            }
	          }
	        })]
	      });
	    }
	  }], [{
	    key: "getAjaxUrl",
	    value: function getAjaxUrl() {
	      return this.isDesktop ? '/desktop_app/call_list.ajax.php' : '/bitrix/components/bitrix/crm.activity.call_list/ajax.php';
	    }
	  }]);
	  return CallList;
	}();

	var avatars = {};
	var Events = {
	  onUnfold: "onUnfold"
	};
	var FoldedCallView = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FoldedCallView, _EventEmitter);
	  function FoldedCallView(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, FoldedCallView);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FoldedCallView).call(this));
	    _this.setEventNamespace("BX.VoxImplant.FoldedCallView");
	    _this.subscribeFromOptions(params.events);
	    _this.currentItem = {};
	    _this.callListParams = {
	      id: 0,
	      webformId: 0,
	      webformSecCode: '',
	      itemIndex: 0,
	      itemStatusId: '',
	      statusList: {},
	      entityType: ''
	    };
	    _this.node = null;
	    _this.elements = {
	      avatar: null,
	      callButton: null,
	      nextButton: null,
	      unfoldButton: null
	    };
	    _this._lsKey = 'bx-im-folded-call-view-data';
	    _this._lsTtl = 86400;
	    _this.init();
	    return _this;
	  }
	  babelHelpers.createClass(FoldedCallView, [{
	    key: "init",
	    value: function init() {
	      this.load();
	      if (this.callListParams.id > 0) {
	        this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
	        this.render();
	      }
	    }
	  }, {
	    key: "load",
	    value: function load() {
	      var savedData = BX.localStorage.get(this._lsKey);
	      if (main_core.Type.isPlainObject(savedData)) {
	        this.callListParams = savedData;
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (this.node) {
	        main_core.Dom.remove(this.node);
	        this.node = null;
	      }
	      BX.localStorage.remove(this._lsKey);
	    }
	  }, {
	    key: "store",
	    value: function store() {
	      BX.localStorage.set(this._lsKey, this.callListParams, this._lsTtl);
	    }
	  }, {
	    key: "fold",
	    value: function fold(params, animation) {
	      animation = animation === true;
	      this.callListParams.id = params.callListId;
	      this.callListParams.webformId = params.webformId;
	      this.callListParams.webformSecCode = params.webformSecCode;
	      this.callListParams.itemIndex = params.currentItemIndex;
	      this.callListParams.itemStatusId = params.currentItemStatusId;
	      this.callListParams.statusList = Object.fromEntries(params.statusList);
	      this.callListParams.entityType = params.entityType;
	      this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
	      this.store();
	      this.render(animation);
	    }
	  }, {
	    key: "unfold",
	    value: function unfold(makeCall) {
	      var _this2 = this;
	      main_core.Dom.addClass(this.node, "im-phone-folded-call-view-unfold");
	      this.node.addEventListener('animationend', function () {
	        if (_this2.node) {
	          main_core.Dom.remove(_this2.node);
	          _this2.node = null;
	        }
	        BX.localStorage.remove(_this2._lsKey);
	        if (_this2.callListParams.id === 0) {
	          return false;
	        }
	        var restoredParams = {};
	        if (_this2.callListParams.webformId > 0 && _this2.callListParams.webformSecCode !== '') {
	          restoredParams.webformId = _this2.callListParams.webformId;
	          restoredParams.webformSecCode = _this2.callListParams.webformSecCode;
	        }
	        restoredParams.callListStatusId = _this2.callListParams.itemStatusId;
	        restoredParams.callListItemIndex = _this2.callListParams.itemIndex;
	        restoredParams.makeCall = makeCall;
	        _this2.emit(Events.onUnfold, {
	          callListId: _this2.callListParams.id,
	          callListParams: restoredParams
	        });
	      });
	    }
	  }, {
	    key: "moveToNext",
	    value: function moveToNext() {
	      this.callListParams.itemIndex++;
	      if (this.callListParams.itemIndex >= this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS.length) {
	        this.callListParams.itemIndex = 0;
	      }
	      this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
	      this.store();
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function render(animation) {
	      animation = animation === true;
	      if (this.node === null) {
	        this.node = main_core.Dom.create("div", {
	          props: {
	            id: 'im-phone-folded-call-view',
	            className: 'im-phone-call-wrapper im-phone-call-wrapper-fixed im-phone-call-panel'
	          },
	          events: {
	            dblclick: this._onViewDblClick.bind(this)
	          }
	        });
	        document.body.appendChild(this.node);
	      } else {
	        main_core.Dom.clean(this.node);
	      }
	      this.node.appendChild(main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-wrapper-fixed-left'
	        },
	        style: animation ? {
	          bottom: '-90px'
	        } : {},
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-wrapper-fixed-user'
	          },
	          children: [main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-wrapper-fixed-user-image'
	            },
	            children: [this.elements.avatar = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-wrapper-fixed-user-image-item'
	              }
	            })]
	          }), main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-wrapper-fixed-user-info'
	            },
	            children: this.renderUserInfo()
	          })]
	        })]
	      }));
	      this.node.appendChild(main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-wrapper-fixed-right'
	        },
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-wrapper-fixed-btn-container'
	          },
	          children: [this.elements.callButton = main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-call-btn im-phone-call-btn-green'
	            },
	            text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_FOLDED_BUTTON_CALL'),
	            events: {
	              click: this._onDialButtonClick.bind(this)
	            }
	          }), this.elements.nextButton = main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-call-btn im-phone-call-btn-gray im-phone-call-btn-arrow'
	            },
	            text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_FOLDED_BUTTON_NEXT'),
	            events: {
	              click: this._onNextButtonClick.bind(this)
	            }
	          })]
	        })]
	      }));
	      this.node.appendChild(main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-btn-block'
	        },
	        children: [this.elements.unfoldButton = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-btn-arrow'
	          },
	          children: [main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-btn-arrow-inner'
	            },
	            text: main_core.Loc.getMessage("IM_PHONE_CALL_VIEW_UNFOLD")
	          })],
	          events: {
	            click: this._onUnfoldButtonClick.bind(this)
	          }
	        })]
	      }));
	      if (avatars[this.currentItem.ELEMENT_ID]) {
	        this.elements.avatar.style.backgroundImage = 'url(\'' + main_core.Text.encode(avatars[this.currentItem.ELEMENT_ID]) + '\')';
	      } else {
	        this.loadAvatar(this.callListParams.entityType, this.currentItem.ELEMENT_ID);
	      }
	      if (animation) {
	        main_core.Dom.addClass(this.node, 'im-phone-folded-call-view-fold');
	        this.node.addEventListener('animationend', function () {
	          BX.removeClass(this.node, 'im-phone-folded-call-view-fold');
	        });
	      }
	    }
	  }, {
	    key: "renderUserInfo",
	    value: function renderUserInfo() {
	      var result = [];
	      result.push(main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-wrapper-fixed-user-name'
	        },
	        text: this.currentItem.NAME
	      }));
	      if (this.currentItem.POST) {
	        result.push(main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-wrapper-fixed-user-item'
	          },
	          text: this.currentItem.POST
	        }));
	      }
	      if (this.currentItem.COMPANY_TITLE) {
	        result.push(main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-wrapper-fixed-user-item'
	          },
	          text: this.currentItem.COMPANY_TITLE
	        }));
	      }
	      return result;
	    }
	  }, {
	    key: "loadAvatar",
	    value: function loadAvatar(entityType, entityId) {
	      var _this3 = this;
	      BX.ajax({
	        url: CallList.getAjaxUrl(),
	        method: 'POST',
	        dataType: 'json',
	        data: {
	          'sessid': BX.bitrix_sessid(),
	          'ajax_action': 'GET_AVATAR',
	          'entityType': entityType,
	          'entityId': entityId
	        },
	        onsuccess: function onsuccess(data) {
	          if (!data.avatar) {
	            return;
	          }
	          avatars[entityId] = data.avatar;
	          if (_this3.currentItem.ELEMENT_ID == entityId && _this3.elements.avatar) {
	            _this3.elements.avatar.style.backgroundImage = 'url(\'' + main_core.Text.encode(data.avatar) + '\')';
	          }
	        }
	      });
	    }
	  }, {
	    key: "_onViewDblClick",
	    value: function _onViewDblClick(e) {
	      e.preventDefault();
	      this.unfold(false);
	    }
	  }, {
	    key: "_onDialButtonClick",
	    value: function _onDialButtonClick(e) {
	      e.preventDefault();
	      this.unfold(true);
	    }
	  }, {
	    key: "_onNextButtonClick",
	    value: function _onNextButtonClick(e) {
	      e.preventDefault();
	      this.moveToNext();
	    }
	  }, {
	    key: "_onUnfoldButtonClick",
	    value: function _onUnfoldButtonClick(e) {
	      e.preventDefault();
	      this.unfold(false);
	    }
	  }]);
	  return FoldedCallView;
	}(main_core_events.EventEmitter);
	babelHelpers.defineProperty(FoldedCallView, "Events", Events);

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var desktopFeatureMap = {
	  'iframe': 39
	};
	var _getHtmlPage = /*#__PURE__*/new WeakSet();
	var Desktop = /*#__PURE__*/function () {
	  function Desktop(params) {
	    babelHelpers.classCallCheck(this, Desktop);
	    _classPrivateMethodInitSpec(this, _getHtmlPage);
	    this.parentPhoneCallView = params.parentPhoneCallView;
	    this.closable = params.closable;
	    this.title = params.title || '';
	    this.window = null;
	  }
	  babelHelpers.createClass(Desktop, [{
	    key: "openCallWindow",
	    value: function openCallWindow(content, js, params) {
	      var _this = this;
	      params = params || {};
	      if (params.minSettingsWidth) {
	        this.minSettingsWidth = params.minSettingsWidth;
	      }
	      if (params.minSettingsHeight) {
	        this.minSettingsHeight = params.minSettingsHeight;
	      }
	      params.resizable = params.resizable === true;
	      im_v2_lib_desktopApi.DesktopApi.createWindow("callWindow", function (callWindow) {
	        callWindow.SetProperty("clientSize", {
	          Width: params.width,
	          Height: params.height
	        });
	        callWindow.SetProperty("resizable", params.resizable);
	        if (params.resizable && params.hasOwnProperty('minWidth') && params.hasOwnProperty('minHeight')) {
	          callWindow.SetProperty("minClientSize", {
	            Width: params.minWidth,
	            Height: params.minHeight
	          });
	        }
	        callWindow.SetProperty("title", _this.title);
	        callWindow.SetProperty("closable", true);

	        //callWindow.OpenDeveloperTools();
	        var html = _classPrivateMethodGet(_this, _getHtmlPage, _getHtmlPage2).call(_this, content, js, {});
	        callWindow.ExecuteCommand("html.load", html);
	        _this.window = callWindow;
	      });
	    }
	  }, {
	    key: "setClosable",
	    value: function setClosable(closable) {
	      this.closable = closable === true;
	      if (this.window) {
	        this.window.SetProperty("closable", this.closable);
	      }
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      this.title = title;
	      if (this.window) {
	        this.window.SetProperty("title", title);
	      }
	    }
	  }, {
	    key: "addCustomEvent",
	    value: function addCustomEvent(eventName, eventHandler) {
	      BX.desktop.addCustomEvent(eventName, eventHandler);
	    }
	  }, {
	    key: "onCustomEvent",
	    value: function onCustomEvent(windowTarget, eventName, arEventParams) {
	      BX.desktop.onCustomEvent(windowTarget, eventName, arEventParams);
	    }
	  }, {
	    key: "resize",
	    value: function resize(width, height) {
	      BXDesktopWindow.SetProperty("clientSize", {
	        Width: width,
	        Height: height
	      });
	    }
	  }, {
	    key: "setResizable",
	    value: function setResizable(resizable) {
	      resizable = resizable === true;
	      BXDesktopWindow.SetProperty("resizable", resizable);
	    }
	  }, {
	    key: "setMinSize",
	    value: function setMinSize(width, height) {
	      BXDesktopWindow.SetProperty("minClientSize", {
	        Width: width,
	        Height: height
	      });
	    }
	  }, {
	    key: "setWindowPosition",
	    value: function setWindowPosition(params) {
	      BXDesktopWindow.SetProperty("position", params);
	    }
	  }, {
	    key: "center",
	    value: function center() {
	      BXDesktopWindow.ExecuteCommand("center");
	    }
	  }, {
	    key: "getVersion",
	    value: function getVersion(full) {
	      if (typeof BXDesktopSystem == 'undefined') {
	        return 0;
	      }
	      if (!this.clientVersion) {
	        this.clientVersion = BXDesktopSystem.GetProperty('versionParts');
	      }
	      return full ? this.clientVersion.join('.') : this.clientVersion[3];
	    }
	  }, {
	    key: "isFeatureSupported",
	    value: function isFeatureSupported(featureName) {
	      if (!desktopFeatureMap.hasOwnProperty(featureName)) {
	        return false;
	      }
	      return this.getVersion() >= desktopFeatureMap[featureName];
	    }
	  }]);
	  return Desktop;
	}();
	function _getHtmlPage2(content, jsContent, initImJs, bodyClass) {
	  content = content || '';
	  jsContent = jsContent || '';
	  bodyClass = bodyClass || '';
	  if (this.htmlWrapperHead == null) {
	    this.htmlWrapperHead = document.head.outerHTML.replace(/BX\.PULL\.start\([^)]*\);/g, '');
	  }
	  if (main_core.Type.isDomNode(content)) {
	    content = content.outerHTML;
	  }
	  if (main_core.Type.isDomNode(jsContent)) {
	    jsContent = jsContent.outerHTML;
	  }
	  if (main_core.Type.isStringFilled(jsContent)) {
	    jsContent = "<script>\n\t\t\t\t\tBX.ready(function() {\n\t\t\t\t\t\t".concat(jsContent, "\n\t\t\t\t\t});\n\t\t\t\t</script>");
	  }
	  var initJs = '';
	  if (initImJs) {
	    initJs = "\n\t\t\t\t<script>\n\t\t\t\t\tBX.ready(function() {\n\t\t\t\t\t\t\tconst backgroundWorker = new BX.Voximplant.BackgroundWorker();\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\twindow.PCW = new BX.Voximplant.PhoneCallView({\n\t\t\t\t\t\t\t\tisDesktop: true,\n\t\t\t\t\t\t\t\tslave: true, \n\t\t\t\t\t\t\t\tskipOnResize: true, \n\t\t\t\t\t\t\t\tcallId: '".concat(this.parentPhoneCallView.callId, "',\n\t\t\t\t\t\t\t\tuiState: ").concat(this.parentPhoneCallView._uiState, ",\n\t\t\t\t\t\t\t\tphoneNumber: '").concat(this.parentPhoneCallView.phoneNumber, "',\n\t\t\t\t\t\t\t\tcompanyPhoneNumber: '").concat(this.parentPhoneCallView.companyPhoneNumber, "',\n\t\t\t\t\t\t\t\tdirection: '").concat(this.parentPhoneCallView.direction, "',\n\t\t\t\t\t\t\t\tfromUserId: '").concat(this.parentPhoneCallView.fromUserId, "',\n\t\t\t\t\t\t\t\ttoUserId: '").concat(this.parentPhoneCallView.toUserId, "',\n\t\t\t\t\t\t\t\tcrm: ").concat(this.parentPhoneCallView.crm, ",\n\t\t\t\t\t\t\t\thasSipPhone: ").concat(this.parentPhoneCallView.hasSipPhone, ",\n\t\t\t\t\t\t\t\tdeviceCall: ").concat(this.parentPhoneCallView.deviceCall, ",\n\t\t\t\t\t\t\t\ttransfer: ").concat(this.parentPhoneCallView.transfer, ",\n\t\t\t\t\t\t\t\tcrmEntityType: '").concat(this.parentPhoneCallView.crmEntityType, "',\n\t\t\t\t\t\t\t\tcrmEntityId: '").concat(this.parentPhoneCallView.crmEntityId, "',\n\t\t\t\t\t\t\t\tcrmActivityId: '").concat(this.parentPhoneCallView.crmActivityId, "',\n\t\t\t\t\t\t\t\tcrmActivityEditUrl: '").concat(this.parentPhoneCallView.crmActivityEditUrl, "',\n\t\t\t\t\t\t\t\tcallListId: ").concat(this.parentPhoneCallView.callListId, ",\n\t\t\t\t\t\t\t\tcallListStatusId: '").concat(this.parentPhoneCallView.callListStatusId, "',\n\t\t\t\t\t\t\t\tcallListItemIndex: ").concat(this.parentPhoneCallView.callListItemIndex, ",\n\t\t\t\t\t\t\t\tconfig: ").concat(this.parentPhoneCallView.config ? JSON.stringify(this.parentPhoneCallView.config) : '{}', ",\n\t\t\t\t\t\t\t\tportalCall: ").concat(this.parentPhoneCallView.portalCall ? 'true' : 'false', ",\n\t\t\t\t\t\t\t\tportalCallData: ").concat(this.parentPhoneCallView.portalCallData ? JSON.stringify(this.parentPhoneCallView.portalCallData) : '{}', ",\n\t\t\t\t\t\t\t\tportalCallUserId: ").concat(this.parentPhoneCallView.portalCallUserId, ",\n\t\t\t\t\t\t\t\twebformId: ").concat(this.parentPhoneCallView.webformId, ",\n\t\t\t\t\t\t\t\twebformSecCode: '").concat(this.parentPhoneCallView.webformSecCode, "',\n\t\t\t\t\t\t\t\tbackgroundWorker: backgroundWorker,\n\t\t\t\t\t\t\t\trestApps: ").concat(this.parentPhoneCallView.restApps ? JSON.stringify(this.parentPhoneCallView.restApps) : '[]', ",\n\t\t\t\t\t\t\t});\n\t\t\t\t\t});\n\t\t\t\t</script>");
	  }
	  return "\n\t\t\t<!DOCTYPE html>\n\t\t\t<html lang=\"".concat(document.documentElement.lang, "\">\n\t\t\t\t").concat(this.htmlWrapperHead, "\n\t\t\t\t<body class=\"im-desktop im-desktop-popup ").concat(bodyClass, "\">\n\t\t\t\t\t<div id=\"placeholder-messanger\">").concat(content, "</div>\n\t\t\t\t\t").concat(initJs, "\n\t\t\t\t\t").concat(jsContent, "\n\t\t\t\t</body>\n\t\t\t</html>\n\t\t");
	}

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var Direction = {
	  incoming: 'incoming',
	  outgoing: 'outgoing',
	  callback: 'callback'
	};
	var UiState = {
	  incoming: 1,
	  transferIncoming: 2,
	  outgoing: 3,
	  connectingIncoming: 4,
	  connectingOutgoing: 5,
	  connected: 6,
	  transferring: 7,
	  transferFailed: 8,
	  transferConnected: 9,
	  idle: 10,
	  error: 11,
	  moneyError: 12,
	  sipPhoneError: 13,
	  redial: 14,
	  externalCard: 15
	};
	var CallState = {
	  idle: 'idle',
	  connecting: 'connecting',
	  connected: 'connected'
	};
	var CallProgress = {
	  connect: 'connect',
	  error: 'error',
	  offline: 'offline',
	  online: 'online',
	  wait: 'wait'
	};
	var ButtonLayouts = {
	  centered: 'centered',
	  spaced: 'spaced'
	};

	/* Phone Call UI */
	var layouts = {
	  simple: 'simple',
	  crm: 'crm'
	};
	var initialSize = {
	  simple: {
	    width: 550,
	    height: 492
	  },
	  crm: {
	    width: 550,
	    height: 650
	  }
	};
	var lsKeys = {
	  height: 'im-phone-call-view-height',
	  width: 'im-phone-call-view-width',
	  callView: 'bx-vox-call-view',
	  callInited: 'viInitedCall',
	  externalCall: 'viExternalCard',
	  currentCall: 'bx-vox-current-call'
	};
	var desktopEvents = {
	  setTitle: 'phoneCallViewSetTitle',
	  setStatus: 'phoneCallViewSetStatus',
	  setUiState: 'phoneCallViewSetUiState',
	  setDeviceCall: 'phoneCallViewSetDeviceCall',
	  setCrmEntity: 'phoneCallViewSetCrmEntity',
	  setPortalCall: 'phoneCallViewSetPortalCall',
	  setPortalCallUserId: 'phoneCallViewSetPortalCallUserId',
	  setPortalCallQueueName: 'phoneCallViewSetPortalCallQueueName',
	  setPortalCallData: 'phoneCallViewSetPortalCallData',
	  setConfig: 'phoneCallViewSetConfig',
	  setCallState: 'phoneCallViewSetCallState',
	  reloadCrmCard: 'phoneCallViewReloadCrmCard',
	  setCallId: 'phoneCallViewSetCallId',
	  setLineNumber: 'phoneCallViewSetLineNumber',
	  setPhoneNumber: 'phoneCallViewSetPhoneNumber',
	  setCompanyPhoneNumber: 'phoneCallViewSetCompanyPhoneNumber',
	  setTransfer: 'phoneCallViewSetTransfer',
	  closeWindow: 'phoneCallViewCloseWindow',
	  onHold: 'phoneCallViewOnHold',
	  onUnHold: 'phoneCallViewOnUnHold',
	  onMute: 'phoneCallViewOnMute',
	  onUnMute: 'phoneCallViewOnUnMute',
	  onMakeCall: 'phoneCallViewOnMakeCall',
	  onCallListMakeCall: 'phoneCallViewOnCallListMakeCall',
	  onAnswer: 'phoneCallViewOnAnswer',
	  onSkip: 'phoneCallViewOnSkip',
	  onHangup: 'phoneCallViewOnHangup',
	  onClose: 'phoneCallViewOnClose',
	  onStartTransfer: 'phoneCallViewOnStartTransfer',
	  onCompleteTransfer: 'phoneCallViewOnCompleteTransfer',
	  onCancelTransfer: 'phoneCallViewOnCancelTransfer',
	  onBeforeUnload: 'phoneCallViewOnBeforeUnload',
	  onSwitchDevice: 'phoneCallViewOnSwitchDevice',
	  onQualityGraded: 'phoneCallViewOnQualityGraded',
	  onDialpadButtonClicked: 'phoneCallViewOnDialpadButtonClicked',
	  onCommentShown: 'phoneCallViewOnCommentShown',
	  onSaveComment: 'phoneCallViewOnSaveComment',
	  onSetAutoClose: 'phoneCallViewOnSetAutoClose'
	};
	var blankAvatar = '/bitrix/js/im/images/blank.gif';
	var _onExternalEvent = /*#__PURE__*/new WeakMap();
	var _onWindowUnload = /*#__PURE__*/new WeakMap();
	var PhoneCallView = /*#__PURE__*/function () {
	  function PhoneCallView(_params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, PhoneCallView);
	    _classPrivateFieldInitSpec(this, _onExternalEvent, {
	      writable: true,
	      value: function value(params) {
	        console.warn('#onExternalEvent', params);
	        return;
	        params = main_core.Type.isPlainObject(params) ? params : {};
	        params.key = params.key || '';
	        var value = params.value || {};
	        value.entityTypeName = value.entityTypeName || '';
	        value.context = value.context || '';
	        value.isCanceled = main_core.Type.isBoolean(value.isCanceled) ? value.isCanceled : false;
	        if (value.isCanceled) {
	          return;
	        }
	        if (params.key === "onCrmEntityCreate" && _this.externalRequests[value.context]) {
	          if (_this.externalRequests[value.context]) {
	            if (_this.externalRequests[value.context]['type'] == 'create') {
	              _this.crmEntityType = value.entityTypeName;
	              _this.crmEntityId = value.entityInfo.id;
	              _this.loadCrmCard(_this.crmEntityType, _this.crmEntityId);
	            } else if (_this.externalRequests[value.context]['type'] == 'add') {
	              // reload crm card
	              _this.loadCrmCard(_this.crmEntityType, _this.crmEntityId);
	            }
	            if (_this.externalRequests[value.context]['window']) {
	              _this.externalRequests[value.context]['window'].close();
	            }
	            delete _this.externalRequests[value.context];
	          }
	        }
	      }
	    });
	    _classPrivateFieldInitSpec(this, _onWindowUnload, {
	      writable: true,
	      value: function value() {
	        console.log('onWindowUnload call view event', location.href, im_v2_lib_desktopApi.DesktopApi.isChatWindow());
	        _this.close();
	      }
	    });
	    this.id = 'im-phone-call-view';
	    this.darkMode = _params.darkMode === true;

	    //params
	    this.phoneNumber = _params.phoneNumber || 'hidden';
	    this.lineNumber = _params.lineNumber || '';
	    this.companyPhoneNumber = _params.companyPhoneNumber || '';
	    this.direction = _params.direction || Direction.incoming;
	    this.fromUserId = _params.fromUserId;
	    this.toUserId = _params.toUserId;
	    this.config = _params.config || {};
	    this.callId = _params.callId || '';
	    this.callState = CallState.idle;

	    //associated crm entities
	    this.crmEntityType = BX.prop.getString(_params, 'crmEntityType', '');
	    this.crmEntityId = BX.prop.getInteger(_params, 'crmEntityId', 0);
	    this.crmActivityId = BX.prop.getInteger(_params, 'crmActivityId', 0);
	    this.crmActivityEditUrl = BX.prop.getString(_params, 'crmActivityEditUrl', '');
	    this.crmData = BX.prop.getObject(_params, 'crmData', {});
	    this.crmBindings = BX.prop.getArray(_params, 'crmBindings', []);
	    this.externalRequests = {};

	    //portal call
	    this.portalCallData = _params.portalCallData;
	    this.portalCallUserId = _params.portalCallUserId;
	    this.portalCallQueueName = _params.portalCallQueueName;

	    //flags
	    this.hasSipPhone = _params.hasSipPhone === true;
	    this.deviceCall = _params.deviceCall === true;
	    this.portalCall = _params.portalCall === true;
	    this.crm = _params.crm === true;
	    this.held = false;
	    this.muted = false;
	    this.recording = _params.recording === true;
	    this.makeCall = _params.makeCall === true; // emulate pressing on "dial" button right after showing call view
	    this.closable = false;
	    this.allowAutoClose = true;
	    this.folded = _params.folded === true;
	    this.autoFold = _params.autoFold === true;
	    this.transfer = _params.transfer === true;
	    this.title = '';
	    this._uiState = _params.uiState || UiState.idle;
	    this.statusText = _params.statusText || '';
	    this.progress = '';
	    this.quality = 0;
	    this.qualityPopup = null;
	    this.qualityGrade = 0;
	    this.comment = '';
	    this.commentShown = false;

	    //timer
	    this.initialTimestamp = _params.initialTimestamp || 0;
	    this.timerInterval = null;
	    this.autoCloseTimer = null;
	    this.autoCloseTimeout = 65000;
	    this.elements = this.getInitialElements();
	    this.sections = this.getInitialSections();
	    var uiStateButtons = this.getUiStateButtons(this._uiState);
	    this.buttonLayout = uiStateButtons.layout;
	    this.buttons = uiStateButtons.buttons;
	    this.restApps = _params.restApps || [];
	    if (!main_core.Type.isPlainObject(_params.events)) {
	      _params.events = {};
	    }
	    this.callbacks = {
	      hold: main_core.Type.isFunction(_params.events.hold) ? _params.events.hold : nop,
	      unhold: main_core.Type.isFunction(_params.events.unhold) ? _params.events.unhold : nop,
	      mute: main_core.Type.isFunction(_params.events.mute) ? _params.events.mute : nop,
	      unmute: main_core.Type.isFunction(_params.events.unmute) ? _params.events.unmute : nop,
	      makeCall: main_core.Type.isFunction(_params.events.makeCall) ? _params.events.makeCall : nop,
	      callListMakeCall: main_core.Type.isFunction(_params.events.callListMakeCall) ? _params.events.callListMakeCall : nop,
	      answer: main_core.Type.isFunction(_params.events.answer) ? _params.events.answer : nop,
	      skip: main_core.Type.isFunction(_params.events.skip) ? _params.events.skip : nop,
	      hangup: main_core.Type.isFunction(_params.events.hangup) ? _params.events.hangup : nop,
	      close: main_core.Type.isFunction(_params.events.close) ? _params.events.close : nop,
	      transfer: main_core.Type.isFunction(_params.events.transfer) ? _params.events.transfer : nop,
	      completeTransfer: main_core.Type.isFunction(_params.events.completeTransfer) ? _params.events.completeTransfer : nop,
	      cancelTransfer: main_core.Type.isFunction(_params.events.cancelTransfer) ? _params.events.cancelTransfer : nop,
	      switchDevice: main_core.Type.isFunction(_params.events.switchDevice) ? _params.events.switchDevice : nop,
	      qualityGraded: main_core.Type.isFunction(_params.events.qualityGraded) ? _params.events.qualityGraded : nop,
	      dialpadButtonClicked: main_core.Type.isFunction(_params.events.dialpadButtonClicked) ? _params.events.dialpadButtonClicked : nop,
	      saveComment: main_core.Type.isFunction(_params.events.saveComment) ? _params.events.saveComment : nop,
	      notifyAdmin: main_core.Type.isFunction(_params.events.notifyAdmin) ? _params.events.notifyAdmin : nop
	    };
	    this.popup = null;

	    // event handlers
	    this._onBeforeUnloadHandler = this._onBeforeUnload.bind(this);
	    this._onDblClickHandler = this._onDblClick.bind(this);
	    this._onHoldButtonClickHandler = this._onHoldButtonClick.bind(this);
	    this._onMuteButtonClickHandler = this._onMuteButtonClick.bind(this);
	    this._onTransferButtonClickHandler = this._onTransferButtonClick.bind(this);
	    this._onTransferCompleteButtonClickHandler = this._onTransferCompleteButtonClick.bind(this);
	    this._onTransferCancelButtonClickHandler = this._onTransferCancelButtonClick.bind(this);
	    this._onDialpadButtonClickHandler = this._onDialpadButtonClick.bind(this);
	    this._onHangupButtonClickHandler = this._onHangupButtonClick.bind(this);
	    this._onCloseButtonClickHandler = this._onCloseButtonClick.bind(this);
	    this._onMakeCallButtonClickHandler = this._onMakeCallButtonClick.bind(this);
	    this._onNextButtonClickHandler = this._onNextButtonClick.bind(this);
	    this._onRedialButtonClickHandler = this._onRedialButtonClick.bind(this);
	    this._onFoldButtonClickHandler = this._onFoldButtonClick.bind(this);
	    this._onAnswerButtonClickHandler = this._onAnswerButtonClick.bind(this);
	    this._onSkipButtonClickHandler = this._onSkipButtonClick.bind(this);
	    this._onSwitchDeviceButtonClickHandler = this._onSwitchDeviceButtonClick.bind(this);
	    this._onQualityMeterClickHandler = this._onQualityMeterClick.bind(this);
	    this._onPullEventCrmHandler = this._onPullEventCrm.bind(this);

	    // tabs
	    this.hiddenTabs = [];
	    this.currentTabName = '';
	    this.moreTabsMenu = null;

	    //customTabs
	    this.customTabs = {};

	    // callList
	    this.callListId = _params.callListId || 0;
	    this.callListStatusId = _params.callListStatusId || null;
	    this.callListItemIndex = _params.callListItemIndex || null;
	    this.callListView = null;
	    this.currentEntity = null;
	    this.callingEntity = null;
	    this.numberSelectMenu = null;

	    // webform
	    this.webformId = _params.webformId || 0;
	    this.webformSecCode = _params.webformSecCode || '';
	    this.webformLoaded = false;

	    // partner data
	    this.restAppLayoutLoaded = false;
	    this.restAppLayoutLoading = false;
	    this.restAppInterface = null;

	    // desktop integration
	    this.callWindow = null;
	    this.slave = _params.slave === true;
	    this.skipOnResize = _params.skipOnResize === true;
	    this.desktop = new Desktop({
	      parentPhoneCallView: this,
	      closable: this.callListId > 0 ? true : this.closable
	    });
	    this.currentLayout = this.callListId > 0 ? layouts.crm : layouts.simple;
	    this.backgroundWorker = _params.backgroundWorker;
	    this.backgroundWorker.setCallCard(this);
	    this.backgroundWorker.setExternalCall(!!_params.isExternalCall);
	    this._isDesktop = _params.messengerFacade ? _params.messengerFacade.isDesktop() : _params.isDesktop === true;
	    this.messengerFacade = _params.messengerFacade;
	    this.foldedCallView = _params.foldedCallView;
	    this.init();
	    if (this.backgroundWorker.isDesktop()) {
	      this.backgroundWorker.removeDesktopEventHandlers();
	    }
	    this.backgroundWorker.platformWorker.emitInitializeEvent(this.getPlacementOptions());
	    this.createTitle().then(function (title) {
	      return _this.setTitle(title);
	    });
	    if (_params.hasOwnProperty('uiState')) {
	      this.setUiState(_params['uiState']);
	    }
	  }
	  babelHelpers.createClass(PhoneCallView, [{
	    key: "getInitialElements",
	    value: function getInitialElements() {
	      return {
	        main: null,
	        title: null,
	        sections: {
	          status: null,
	          timer: null,
	          crmButtons: null
	        },
	        avatar: null,
	        progress: null,
	        timer: null,
	        status: null,
	        commentEditorContainer: null,
	        commentEditor: null,
	        qualityMeter: null,
	        crmCard: null,
	        crmButtonsContainer: null,
	        crmButtons: {},
	        buttonsContainer: null,
	        topLevelButtonsContainer: null,
	        topButtonsContainer: null,
	        //well..
	        buttons: {},
	        sidebarContainer: null,
	        tabsContainer: null,
	        tabsBodyContainer: null,
	        tabs: {
	          callList: null,
	          webform: null,
	          app: null,
	          custom: null
	        },
	        tabsBody: {
	          callList: null,
	          webform: null,
	          app: null
	        },
	        moreTabs: null
	      };
	    }
	  }, {
	    key: "getInitialSections",
	    value: function getInitialSections() {
	      return {
	        status: {
	          visible: false
	        },
	        timer: {
	          visible: false
	        },
	        crmButtons: {
	          visible: false
	        },
	        commentEditor: {
	          visible: false
	        }
	      };
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      var _this2 = this;
	      if (im_v2_lib_desktopApi.DesktopApi.isChatWindow() && !this.slave) {
	        console.log('Init phone call view window:', location.href);
	        this.desktop.openCallWindow('', null, {
	          width: this.getInitialWidth(),
	          height: this.getInitialHeight(),
	          resizable: this.currentLayout == layouts.crm,
	          minWidth: this.elements.sidebarContainer ? 950 : 550,
	          minHeight: 650
	        });
	        this.bindMasterDesktopEvents();
	        window.addEventListener('beforeunload', babelHelpers.classPrivateFieldGet(this, _onWindowUnload)); //master window unload
	        return;
	      }
	      this.elements.main = this.createLayout();
	      this.updateView();
	      if (this.isDesktop() && this.slave) {
	        document.body.appendChild(this.elements.main);
	        this.bindSlaveDesktopEvents();
	      } else if (!this.isDesktop() && this.isFolded()) {
	        document.body.appendChild(this.elements.main);
	      } else if (!this.isDesktop()) {
	        this.popup = this.createPopup();
	        BX.addCustomEvent(window, "onLocalStorageSet", babelHelpers.classPrivateFieldGet(this, _onExternalEvent));
	      }
	      if (this.callListId > 0) {
	        if (this.callListView) {
	          this.callListView.reinit({
	            node: this.elements.tabsBody.callList
	          });
	        } else {
	          this.callListView = new CallList({
	            node: this.elements.tabsBody.callList,
	            id: this.callListId,
	            statusId: this.callListStatusId,
	            itemIndex: this.callListItemIndex,
	            makeCall: this.makeCall,
	            isDesktop: this.isDesktop,
	            onSelectedItem: this.onCallListSelectedItem.bind(this)
	          });
	          this.callListView.init(function () {
	            if (_this2.makeCall) {
	              _this2._onMakeCallButtonClick();
	            }
	          });
	          this.setUiState(UiState.outgoing);
	        }
	      } else if (this.crm && !this.isFolded()) {
	        this.loadCrmCard(this.crmEntityType, this.crmEntityId);
	      }
	      BX.addCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);
	      if (!this.isDesktop()) {
	        window.addEventListener('beforeunload', this._onBeforeUnloadHandler);
	      }
	    }
	  }, {
	    key: "reinit",
	    value: function reinit() {
	      this.elements = this.getInitialElements();
	      var unloadHandler = this.isDesktop() ? babelHelpers.classPrivateFieldGet(this, _onWindowUnload) : this._onBeforeUnloadHandler;
	      window.removeEventListener('beforeunload', unloadHandler);
	      BX.removeCustomEvent(window, "onLocalStorageSet", babelHelpers.classPrivateFieldGet(this, _onExternalEvent));
	      BX.removeCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);
	      this.init();
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (!this.popup && this.isDesktop()) {
	        return;
	      }
	      if (!this.popup) {
	        this.reinit();
	      }
	      if (!this.isDesktop() && !this.isFolded()) {
	        this.disableDocumentScroll();
	      }
	      this.popup.show();
	      BX.localStorage.set(lsKeys.callView, this.callId, 86400);
	      return this;
	    }
	  }, {
	    key: "createPopup",
	    value: function createPopup() {
	      var _this3 = this;
	      return new main_popup.Popup({
	        id: this.getId(),
	        bindElement: null,
	        targetContainer: document.body,
	        content: this.elements.main,
	        closeIcon: false,
	        noAllPaddings: true,
	        zIndex: baseZIndex,
	        offsetLeft: 0,
	        offsetTop: 0,
	        closeByEsc: false,
	        draggable: {
	          restrict: false
	        },
	        overlay: {
	          backgroundColor: 'black',
	          opacity: 30
	        },
	        events: {
	          onPopupClose: function onPopupClose() {
	            if (_this3.isFolded()) ; else {
	              _this3.callbacks.close();
	            }
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this3.popup = null;
	          }
	        }
	      });
	    }
	  }, {
	    key: "createLayout",
	    value: function createLayout() {
	      if (this.isFolded()) {
	        return this.createLayoutFolded();
	      } else if (this.currentLayout == layouts.crm) {
	        return this.createLayoutCrm();
	      } else {
	        return this.createLayoutSimple();
	      }
	    }
	  }, {
	    key: "createLayoutCrm",
	    value: function createLayoutCrm() {
	      var _this4 = this;
	      var result = main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-top-level'
	        },
	        events: {
	          dblclick: this._onDblClickHandler
	        },
	        children: [this.elements.topLevelButtonsContainer = main_core.Dom.create("div"), this.elements.phoneCallWrapper = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-wrapper' + (this.hasSideBar() ? '' : ' im-phone-call-wrapper-without-sidebar')
	          },
	          children: [main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-container' + (this.hasSideBar() ? '' : ' im-phone-call-container-without-sidebar')
	            },
	            children: [main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-header-container'
	              },
	              children: [main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-header'
	                },
	                children: [this.elements.title = main_core.Dom.create('div', {
	                  props: {
	                    className: 'im-phone-call-title-text'
	                  },
	                  html: this.renderTitle()
	                })]
	              })]
	            }), this.elements.crmCard = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-crm-card'
	              }
	            }), this.elements.sections.status = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-section'
	              },
	              style: this.sections.status.visible ? {} : {
	                display: 'none'
	              },
	              children: [main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-status-description'
	                },
	                children: [this.elements.status = main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-status-description-item'
	                  },
	                  text: this.statusText
	                })]
	              })]
	            }), this.elements.sections.timer = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-section'
	              },
	              style: this.sections.timer.visible ? {} : {
	                display: 'none'
	              },
	              children: [main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-status-timer'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-status-timer-item'
	                  },
	                  children: [this.elements.timer = main_core.Dom.create("span")]
	                })]
	              })]
	            }), this.elements.commentEditorContainer = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-section'
	              },
	              style: this.commentShown ? {} : {
	                display: 'none'
	              },
	              children: [main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-comments'
	                },
	                children: [this.elements.commentEditor = main_core.Dom.create("textarea", {
	                  props: {
	                    className: 'im-phone-call-comments-textarea',
	                    value: this.comment,
	                    placeholder: main_core.Loc.getMessage('IM_PHONE_CALL_COMMENT_PLACEHOLDER')
	                  },
	                  events: {
	                    bxchange: this._onCommentChanged.bind(this)
	                  }
	                })]
	              })]
	            }), this.elements.sections.crmButtons = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-section'
	              },
	              style: this.sections.crmButtons.visible ? {} : {
	                display: 'none'
	              },
	              children: [this.elements.crmButtonsContainer = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-buttons'
	                }
	              })]
	            }), this.elements.buttonsContainer = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-buttons-container'
	              }
	            }), this.elements.topButtonsContainer = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-call-buttons-container-top'
	              }
	            })]
	          })]
	        })]
	      });
	      if (this.hasSideBar()) {
	        this.createSidebarLayout();
	        if (this.elements.sidebarContainer) {
	          result.appendChild(this.elements.sidebarContainer);
	        }
	        setTimeout(function () {
	          return _this4.checkMoreButton();
	        }, 0);
	      }
	      if (this.isDesktop()) {
	        result.style.position = 'fixed';
	        result.style.top = 0;
	        result.style.bottom = 0;
	        result.style.left = 0;
	        result.style.right = 0;
	      } else {
	        result.style.width = this.getInitialWidth() + 'px';
	        result.style.height = this.getInitialHeight() + 'px';
	      }
	      return result;
	    }
	  }, {
	    key: "hasSideBar",
	    /**
	     * @return boolean
	     */
	    value: function hasSideBar() {
	      if (this.isDesktop() && !this.desktop.isFeatureSupported('iframe')) {
	        return this.callListId > 0;
	      } else {
	        return this.callListId > 0 || this.webformId > 0 || this.restApps.length > 0 || Object.keys(this.customTabs).length > 0;
	      }
	    }
	  }, {
	    key: "getInitialWidth",
	    value: function getInitialWidth() {
	      var storedWidth = window.localStorage ? parseInt(window.localStorage.getItem(lsKeys.width)) : 0;
	      if (this.currentLayout == layouts.simple) {
	        return initialSize.simple.width;
	      } else if (this.hasSideBar()) {
	        if (storedWidth > 0) {
	          return storedWidth;
	        } else {
	          return Math.min(Math.floor(screen.width * 0.8), 1200);
	        }
	      } else {
	        return initialSize.crm.width;
	      }
	    }
	  }, {
	    key: "getInitialHeight",
	    value: function getInitialHeight() {
	      var storedHeight = window.localStorage ? parseInt(window.localStorage.getItem(lsKeys.height)) : 0;
	      if (this.currentLayout == layouts.simple) {
	        return initialSize.simple.height;
	      } else if (storedHeight > 0) {
	        return storedHeight;
	      } else {
	        return initialSize.crm.height;
	      }
	    }
	  }, {
	    key: "saveInitialSize",
	    value: function saveInitialSize(width, height) {
	      if (!window.localStorage) {
	        return false;
	      }
	      if (this.currentLayout == layouts.crm) {
	        window.localStorage.setItem(lsKeys.height, height.toString());
	        if (this.hasSideBar()) {
	          window.localStorage.setItem(lsKeys.width, width);
	        }
	      }
	    }
	  }, {
	    key: "showSections",
	    value: function showSections(sections) {
	      var _this5 = this;
	      if (!main_core.Type.isArray(sections)) {
	        return;
	      }
	      sections.forEach(function (sectionName) {
	        if (_this5.elements.sections[sectionName]) {
	          _this5.elements.sections[sectionName].style.removeProperty('display');
	        }
	        if (_this5.sections[sectionName]) {
	          _this5.sections[sectionName].visible = true;
	        }
	      });
	    }
	  }, {
	    key: "hideSections",
	    value: function hideSections(sections) {
	      var _this6 = this;
	      if (!main_core.Type.isArray(sections)) {
	        return;
	      }
	      sections.forEach(function (sectionName) {
	        if (_this6.elements.sections[sectionName]) {
	          _this6.elements.sections[sectionName].style.display = 'none';
	        }
	        if (_this6.sections[sectionName]) {
	          _this6.sections[sectionName].visible = false;
	        }
	      });
	    }
	  }, {
	    key: "showOnlySections",
	    value: function showOnlySections(sections) {
	      if (!main_core.Type.isArray(sections)) {
	        return;
	      }
	      var sectionsIndex = {};
	      sections.forEach(function (sectionName) {
	        return sectionsIndex[sectionName] = true;
	      });
	      for (var sectionName in this.elements.sections) {
	        if (!this.elements.sections.hasOwnProperty(sectionName) || !main_core.Type.isDomNode(this.elements.sections[sectionName])) {
	          continue;
	        }
	        if (sectionsIndex[sectionName]) {
	          this.elements.sections[sectionName].style.removeProperty('display');
	          if (this.sections.hasOwnProperty(sectionName)) {
	            this.sections[sectionName].visible = true;
	          }
	        } else {
	          this.elements.sections[sectionName].style.display = 'none';
	          if (this.sections.hasOwnProperty(sectionName)) {
	            this.sections[sectionName].visible = false;
	          }
	        }
	      }
	    }
	  }, {
	    key: "createSidebarLayout",
	    value: function createSidebarLayout() {
	      var _this7 = this;
	      var tabs = [];
	      var tabsBody = [];
	      if (Object.keys(this.customTabs).length > 0) {
	        Object.keys(this.customTabs).forEach(function (tabKey) {
	          var customTabId = _this7.customTabs[tabKey].id;
	          var tabTitle = _this7.customTabs[tabKey].title;
	          var tabId = "custom".concat(customTabId);
	          _this7.elements.tabs[tabId] = main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-sidebar-tab'
	            },
	            dataset: {
	              tabId: tabId,
	              tabBodyId: "custom".concat(customTabId)
	            },
	            text: main_core.Text.encode(tabTitle),
	            events: {
	              click: _this7._onTabHeaderClick.bind(_this7)
	            }
	          });
	          tabs.push(_this7.elements.tabs[tabId]);
	          if (!_this7.elements.tabsBody[tabId]) {
	            _this7.elements.tabsBody[tabId] = main_core.Dom.create('div', {
	              props: {
	                className: "voximplant-phone-call-".concat(tabId, "-container")
	              },
	              children: [main_core.Dom.create('div', {
	                props: {
	                  className: "voximplant-phone-call-".concat(tabId, "-tab-content voximplant-phone-call-custom-container")
	                }
	              })]
	            });
	          }
	          tabsBody.push(_this7.elements.tabsBody[tabId]);
	        });
	      }
	      if (this.callListId > 0) {
	        this.elements.tabs.callList = main_core.Dom.create("span", {
	          props: {
	            className: 'im-phone-sidebar-tab'
	          },
	          dataset: {
	            tabId: 'callList',
	            tabBodyId: 'callList'
	          },
	          text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_TITLE'),
	          events: {
	            click: this._onTabHeaderClick.bind(this)
	          }
	        });
	        tabs.push(this.elements.tabs.callList);
	        if (!this.elements.tabsBody.callList) {
	          this.elements.tabsBody.callList = main_core.Dom.create('div');
	        }
	        tabsBody.push(this.elements.tabsBody.callList);
	      }
	      if (this.webformId > 0 && this.isWebformSupported()) {
	        this.elements.tabs.webform = main_core.Dom.create("span", {
	          props: {
	            className: 'im-phone-sidebar-tab'
	          },
	          dataset: {
	            tabId: 'webform',
	            tabBodyId: 'webform'
	          },
	          text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_WEBFORM_TITLE'),
	          events: {
	            click: this._onTabHeaderClick.bind(this)
	          }
	        });
	        tabs.push(this.elements.tabs.webform);
	        if (!this.elements.tabsBody.webform) {
	          this.elements.tabsBody.webform = main_core.Dom.create('div', {
	            props: {
	              className: 'im-phone-call-form-container'
	            }
	          });
	        }
	        tabsBody.push(this.elements.tabsBody.webform);
	        if (!this.formManager) {
	          this.formManager = new FormManager({
	            node: this.elements.tabsBody.webform,
	            onFormSend: this._onFormSend.bind(this)
	          });
	        }
	      }
	      if (this.restApps.length > 0 && this.isRestAppsSupported()) {
	        this.restApps.forEach(function (restApp) {
	          var restAppId = restApp.id;
	          var tabId = 'restApp' + restAppId;
	          _this7.elements.tabs[tabId] = main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-sidebar-tab'
	            },
	            dataset: {
	              tabId: tabId,
	              tabBodyId: 'app',
	              restAppId: restAppId
	            },
	            text: main_core.Text.encode(restApp.name),
	            events: {
	              click: _this7._onTabHeaderClick.bind(_this7)
	            }
	          });
	          tabs.push(_this7.elements.tabs[tabId]);
	        });
	        if (!this.elements.tabsBody.app) {
	          this.elements.tabsBody.app = main_core.Dom.create('div', {
	            props: {
	              className: 'im-phone-call-app-container'
	            }
	          });
	        }
	        tabsBody.push(this.elements.tabsBody.app);
	      }
	      this.elements.tabsTitleListContainer = main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-sidebar-tabs-container'
	        },
	        children: [this.elements.tabsContainer = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-sidebar-tabs-left'
	          },
	          children: tabs
	        }), main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-sidebar-tabs-right'
	          },
	          children: [this.elements.moreTabs = main_core.Dom.create("span", {
	            props: {
	              className: 'im-phone-sidebar-tab im-phone-sidebar-tab-more'
	            },
	            style: {
	              display: 'none'
	            },
	            dataset: {},
	            text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_MORE'),
	            events: {
	              click: this._onTabMoreClick.bind(this)
	            }
	          })]
	        })]
	      });
	      this.elements.tabsBodyContainer = main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-sidebar-tabs-body-container'
	        },
	        children: tabsBody
	      });
	      if (this.elements.sidebarContainer) {
	        this.elements.sidebarContainer.replaceChild(this.elements.tabsTitleListContainer, this.elements.sidebarContainer.firstChild);
	        this.elements.sidebarContainer.replaceChild(this.elements.tabsBodyContainer, this.elements.sidebarContainer.lastChild);
	        setTimeout(function () {
	          return _this7.checkMoreButton();
	        }, 0);
	      } else {
	        this.elements.sidebarContainer = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-sidebar-wrap'
	          },
	          children: [this.elements.tabsTitleListContainer, this.elements.tabsBodyContainer]
	        });
	      }
	      if (Object.keys(this.customTabs).length > 0) {
	        var selectedCustomTab = Object.keys(this.customTabs)[0];
	        this.setActiveTab({
	          tabId: "custom".concat(this.customTabs[selectedCustomTab].id),
	          tabBodyId: "custom".concat(this.customTabs[selectedCustomTab].id),
	          hidden: this.customTabs[selectedCustomTab].visible
	        });
	      } else if (this.callListId > 0) {
	        this.setActiveTab({
	          tabId: 'callList',
	          tabBodyId: 'callList'
	        });
	      } else if (this.webformId > 0 && this.isWebformSupported()) {
	        this.setActiveTab({
	          tabId: 'webform',
	          tabBodyId: 'webform'
	        });
	      } else if (this.restApps.length > 0 && this.isRestAppsSupported()) {
	        this.setActiveTab({
	          tabId: 'restApp' + this.restApps[0].id,
	          tabBodyId: 'app',
	          restAppId: this.restApps[0].id
	        });
	      }
	    }
	  }, {
	    key: "createLayoutSimple",
	    value: function createLayoutSimple() {
	      var portalCallUserImage = '';
	      if (this.isPortalCall() && this.portalCallData.hrphoto && this.portalCallData.hrphoto[this.portalCallUserId] && this.portalCallData.hrphoto[this.portalCallUserId] != blankAvatar) {
	        portalCallUserImage = this.portalCallData.hrphoto[this.portalCallUserId];
	      }
	      var result = main_core.Dom.create("div", {
	        props: {
	          className: 'im-phone-call-wrapper'
	        },
	        children: [main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-container'
	          },
	          children: [main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-calling-section'
	            },
	            children: [this.elements.title = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-calling-text'
	              }
	            })]
	          }), main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-section im-phone-calling-progress-section'
	            },
	            children: [main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-calling-progress-container'
	              },
	              children: [main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-calling-progress-container-block-l'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-calling-progress-phone'
	                  }
	                })]
	              }), this.elements.progress = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-calling-progress-container-block-c'
	                }
	              }), main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-calling-progress-container-block-r'
	                },
	                children: [this.elements.avatar = main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-calling-progress-customer'
	                  },
	                  style: main_core.Type.isStringFilled(portalCallUserImage) ? {
	                    'background-image': 'url(\'' + portalCallUserImage + '\')'
	                  } : {}
	                })]
	              })]
	            })]
	          }), main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-section'
	            },
	            children: [this.elements.status = main_core.Dom.create("div", {
	              props: {
	                className: 'im-phone-calling-process-status'
	              }
	            })]
	          }), this.elements.buttonsContainer = main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-buttons-container'
	            }
	          }), this.elements.topButtonsContainer = main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-call-buttons-container-top'
	            }
	          })]
	        })]
	      });
	      result.style.width = this.getInitialWidth() + 'px';
	      result.style.height = this.getInitialHeight() + 'px';
	      return result;
	    }
	  }, {
	    key: "createLayoutFolded",
	    value: function createLayoutFolded() {
	      var _this8 = this;
	      return main_core.Dom.create("div", {
	        props: {
	          className: "im-phone-call-panel-mini"
	        },
	        style: {
	          zIndex: baseZIndex
	        },
	        children: [this.elements.sections.timer = this.elements.timer = main_core.Dom.create("div", {
	          props: {
	            className: "im-phone-call-panel-mini-time"
	          },
	          style: this.sections.timer.visible ? {} : {
	            display: 'none'
	          }
	        }), this.elements.buttonsContainer = main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-call-panel-mini-buttons'
	          }
	        }), main_core.Dom.create("div", {
	          props: {
	            className: "im-phone-call-panel-mini-expand"
	          },
	          events: {
	            click: function click() {
	              return _this8.unfold();
	            }
	          }
	        })]
	      });
	    }
	  }, {
	    key: "addTab",
	    value: function addTab(tabName) {
	      var _this9 = this;
	      var tabId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      if (!tabId) {
	        tabId = Math.random().toString(36).substr(2, 9);
	      }
	      return new Promise(function (resolve) {
	        var tab = {
	          title: tabName,
	          id: tabId,
	          callId: _this9.callId,
	          contentContainerId: "voximplant-phone-call-".concat(tabId, "-container"),
	          visible: true,
	          visibilityChangeCallback: null,
	          getContentContainerId: function getContentContainerId() {
	            return _this9.elements.tabsBody["custom".concat(tabId)];
	          },
	          setContent: function setContent(content) {
	            _this9.elements.tabsBody["custom".concat(tabId)].replaceChild(content, _this9.elements.tabsBody["custom".concat(tabId)].firstChild);
	          },
	          setVisibilityChangeCallback: function setVisibilityChangeCallback(callback) {
	            this.visibilityChangeCallback = callback;
	          },
	          setTitle: function setTitle(newTitle) {
	            _this9.customTabs[tabId].title = newTitle;
	            _this9.elements.tabs["custom".concat(tabId)].innerText = newTitle;
	          },
	          setVisibility: function setVisibility(newValue) {
	            _this9.customTabs[tabId].visible = newValue;
	            _this9.createSidebarLayout();
	          },
	          remove: function remove() {
	            delete _this9.customTabs[tabId];
	            if (!_this9.hasSideBar()) {
	              _this9.elements.main.removeChild(_this9.elements.sidebarContainer);
	              _this9.elements.sidebarContainer = null;
	              _this9.resizeCallCard();
	              return;
	            }
	            _this9.createSidebarLayout();
	          }
	        };
	        _this9.customTabs[tabId] = tab;
	        if (_this9.elements.sidebarContainer) {
	          _this9.createSidebarLayout();
	        } else {
	          _this9.createSidebarLayout();
	          _this9.elements.main.appendChild(_this9.elements.sidebarContainer);
	          _this9.elements.phoneCallWrapper.classList.remove('im-phone-call-wrapper-without-sidebar');
	          _this9.resizeCallCard();
	        }
	        resolve(tab);
	      });
	    }
	  }, {
	    key: "resizeCallCard",
	    value: function resizeCallCard() {
	      if (this.isDesktop()) {
	        this.elements.main.style.position = 'fixed';
	        this.elements.main.style.top = 0;
	        this.elements.main.style.bottom = 0;
	        this.elements.main.style.left = 0;
	        this.elements.main.style.right = 0;
	      } else {
	        this.elements.main.style.width = this.getInitialWidth() + 'px';
	        this.elements.main.style.height = this.getInitialHeight() + 'px';
	      }
	      if (this.isDesktop()) {
	        this.resizeWindow(this.getInitialWidth(), this.getInitialHeight());
	      }
	      this.adjust();
	    }
	  }, {
	    key: "setActiveTab",
	    value: function setActiveTab(params) {
	      var tabId = params.tabId;
	      var tabBodyId = params.tabBodyId;
	      var restAppId = params.restAppId || '';
	      params.hidden = params.hidden === true;
	      for (var tab in this.elements.tabs) {
	        if (this.elements.tabs.hasOwnProperty(tab) && main_core.Type.isDomNode(this.elements.tabs[tab])) {
	          this.elements.tabs[tab].classList.toggle('im-phone-sidebar-tab-active', tab == tabId);
	        }
	      }
	      this.elements.moreTabs.classList.toggle('im-phone-sidebar-tab-active', params.hidden);
	      for (var _tab in this.elements.tabsBody) {
	        if (this.elements.tabsBody.hasOwnProperty(_tab) && main_core.Type.isDomNode(this.elements.tabsBody[_tab])) {
	          if (_tab == tabBodyId) {
	            this.elements.tabsBody[_tab].style.removeProperty('display');
	          } else {
	            this.elements.tabsBody[_tab].style.display = 'none';
	          }
	        }
	      }
	      this.currentTabName = tabId;
	      if (tabId === 'webform' && !this.webformLoaded) {
	        this.loadForm({
	          id: this.webformId,
	          secCode: this.webformSecCode
	        });
	      }
	      if (restAppId !== '') {
	        this.loadRestApp({
	          id: restAppId,
	          callId: this.callId,
	          node: this.elements.tabsBody.app
	        });
	      }
	    }
	  }, {
	    key: "isCurrentTabHidden",
	    value: function isCurrentTabHidden() {
	      var result = false;
	      for (var i = 0; i < this.hiddenTabs.length; i++) {
	        if (this.hiddenTabs[i].dataset.tabId == this.currentTabName) {
	          result = true;
	          break;
	        }
	      }
	      return result;
	    }
	  }, {
	    key: "checkMoreButton",
	    value: function checkMoreButton() {
	      if (!this.elements.tabsContainer) {
	        return;
	      }
	      var tabs = this.elements.tabsContainer.children;
	      var currentTab;
	      this.hiddenTabs = [];
	      for (var i = 0; i < tabs.length; i++) {
	        currentTab = tabs.item(i);
	        if (currentTab.offsetTop > 7) {
	          this.hiddenTabs.push(currentTab);
	        }
	      }
	      if (this.hiddenTabs.length > 0) {
	        this.elements.moreTabs.style.removeProperty('display');
	      } else {
	        this.elements.moreTabs.style.display = 'none';
	      }
	      if (this.isCurrentTabHidden()) {
	        main_core.Dom.addClass(this.elements.moreTabs, 'im-phone-sidebar-tab-active');
	      } else {
	        main_core.Dom.removeClass(this.elements.moreTabs, 'im-phone-sidebar-tab-active');
	      }
	    }
	  }, {
	    key: "_onTabHeaderClick",
	    value: function _onTabHeaderClick(e) {
	      if (this.moreTabsMenu) {
	        this.moreTabsMenu.close();
	      }
	      this.setActiveTab({
	        tabId: e.target.dataset.tabId,
	        tabBodyId: e.target.dataset.tabBodyId,
	        restAppId: e.target.dataset.restAppId || '',
	        hidden: false
	      });
	    }
	  }, {
	    key: "_onTabMoreClick",
	    value: function _onTabMoreClick() {
	      var _this10 = this;
	      if (this.hiddenTabs.length === 0) {
	        return;
	      }
	      if (this.moreTabsMenu) {
	        this.moreTabsMenu.close();
	        return;
	      }
	      var menuItems = [];
	      this.hiddenTabs.forEach(function (tabElement) {
	        menuItems.push({
	          id: "selectTab_" + tabElement.dataset.tabId,
	          text: tabElement.innerText,
	          onclick: function onclick() {
	            _this10.moreTabsMenu.close();
	            _this10.setActiveTab({
	              tabId: tabElement.dataset.tabId,
	              tabBodyId: tabElement.dataset.tabBodyId,
	              restAppId: tabElement.dataset.restAppId || '',
	              hidden: true
	            });
	          }
	        });
	      });
	      this.moreTabsMenu = new main_popup.Menu('phoneCallViewMoreTabs', this.elements.moreTabs, menuItems, {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 0,
	        angle: {
	          position: "top"
	        },
	        zIndex: baseZIndex + 100,
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this10.moreTabsMenu.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this10.moreTabsMenu = null;
	          }
	        }
	      });
	      this.moreTabsMenu.show();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "createTitle",
	    value: function createTitle() {
	      var _this11 = this;
	      var callTitle = '';
	      return new Promise(function (resolve) {
	        BX.PhoneNumberParser.getInstance().parse(_this11.phoneNumber).then(function (parsedNumber) {
	          if (_this11.phoneNumber == 'unknown') {
	            resolve(main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_NUMBER_UNKNOWN'));
	            return;
	          }
	          if (_this11.phoneNumber == 'hidden') {
	            callTitle = main_core.Loc.getMessage('IM_PHONE_HIDDEN_NUMBER');
	          } else {
	            callTitle = _this11.phoneNumber.toString();
	            if (parsedNumber.isValid()) {
	              callTitle = parsedNumber.format();
	              if (parsedNumber.isInternational() && callTitle.charAt(0) != '+') {
	                callTitle = '+' + callTitle;
	              }
	            } else {
	              callTitle = _this11.phoneNumber.toString();
	            }
	          }
	          if (_this11.isCallback()) {
	            callTitle = main_core.Loc.getMessage('IM_PHONE_CALLBACK_TO').replace('#PHONE#', callTitle);
	          } else if (_this11.isPortalCall()) {
	            switch (_this11.direction) {
	              case Direction.incoming:
	                if (_this11.portalCallUserId) {
	                  callTitle = main_core.Loc.getMessage("IM_M_CALL_VOICE_FROM").replace('#USER#', _this11.portalCallData.users[_this11.portalCallUserId].name);
	                }
	                break;
	              case Direction.outgoing:
	                if (_this11.portalCallUserId) {
	                  callTitle = main_core.Loc.getMessage("IM_M_CALL_VOICE_TO").replace('#USER#', _this11.portalCallData.users[_this11.portalCallUserId].name);
	                } else {
	                  callTitle = main_core.Loc.getMessage("IM_M_CALL_VOICE_TO").replace('#USER#', _this11.portalCallQueueName) + ' (' + _this11.phoneNumber + ')';
	                }
	                break;
	            }
	          } else {
	            callTitle = main_core.Loc.getMessage(_this11.direction === Direction.incoming ? 'IM_PHONE_CALL_VOICE_FROM' : 'IM_PHONE_CALL_VOICE_TO').replace('#PHONE#', callTitle);
	            if (_this11.direction === Direction.incoming && _this11.companyPhoneNumber) {
	              callTitle = callTitle + ', ' + main_core.Loc.getMessage('IM_PHONE_CALL_TO_PHONE').replace('#PHONE#', _this11.companyPhoneNumber);
	            }
	            if (_this11.isTransfer()) {
	              callTitle = callTitle + ' ' + main_core.Loc.getMessage('IM_PHONE_CALL_TRANSFERED');
	            }
	          }
	          resolve(callTitle);
	        });
	      });
	    }
	  }, {
	    key: "renderTitle",
	    value: function renderTitle() {
	      return main_core.Text.encode(this.title);
	    }
	  }, {
	    key: "renderAvatar",
	    value: function renderAvatar() {
	      var portalCallUserImage = '';
	      if (this.isPortalCall() && this.elements.avatar && this.portalCallData.hrphoto && this.portalCallData.hrphoto[this.portalCallUserId] && this.portalCallData.hrphoto[this.portalCallUserId] != blankAvatar) {
	        portalCallUserImage = this.portalCallData.hrphoto[this.portalCallUserId];
	        main_core.Dom.adjust(this.elements.avatar, {
	          style: portalCallUserImage === '' ? {} : {
	            'background-image': 'url(\'' + portalCallUserImage + '\')'
	          }
	        });
	      }
	    }
	  }, {
	    key: "_getCrmEditUrl",
	    value: function _getCrmEditUrl(entityTypeName, entityId) {
	      if (!main_core.Type.isStringFilled(entityTypeName)) {
	        return '';
	      }
	      entityId = Number(entityId);
	      return '/crm/' + entityTypeName.toLowerCase() + '/edit/' + entityId.toString() + '/';
	    }
	  }, {
	    key: "_generateExternalContext",
	    value: function _generateExternalContext() {
	      return this._getRandomString(16);
	    }
	  }, {
	    key: "_getRandomString",
	    value: function _getRandomString(len) {
	      var charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	      var randomString = '';
	      for (var i = 0; i < len; i++) {
	        var randomPoz = Math.floor(Math.random() * charSet.length);
	        randomString += charSet.substring(randomPoz, randomPoz + 1);
	      }
	      return randomString;
	    }
	  }, {
	    key: "setPhoneNumber",
	    value: function setPhoneNumber(phoneNumber) {
	      this.phoneNumber = phoneNumber;
	      this.setOnSlave(desktopEvents.setPhoneNumber, [phoneNumber]);
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      this.title = title;
	      if (this.isDesktop()) {
	        if (this.slave) {
	          BXDesktopWindow.SetProperty('title', title);
	        } else {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.setTitle, [title]);
	        }
	      }
	      if (this.elements.title) {
	        this.elements.title.innerHTML = this.renderTitle();
	      }
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.title;
	    }
	  }, {
	    key: "setQuality",
	    value: function setQuality(quality) {
	      this.quality = quality;
	      if (this.elements.qualityMeter) {
	        this.elements.qualityMeter.style.width = this.getQualityMeterWidth();
	      }
	    }
	  }, {
	    key: "getQualityMeterWidth",
	    value: function getQualityMeterWidth() {
	      if (this.quality > 0 && this.quality <= 5) {
	        return this.quality * 20 + '%';
	      } else {
	        return '0';
	      }
	    }
	  }, {
	    key: "setProgress",
	    value: function setProgress(progress) {
	      if (this.progress === progress) {
	        return;
	      }
	      this.progress = progress;
	      if (!this.elements.progress) {
	        return;
	      }
	      main_core.Dom.clean(this.elements.progress);
	      this.elements.progress.appendChild(this.renderProgress(this.progress));
	    }
	  }, {
	    key: "setStatusText",
	    value: function setStatusText(statusText) {
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.setStatus, [statusText]);
	        return;
	      }
	      this.statusText = statusText;
	      if (this.elements.status) {
	        this.elements.status.innerText = this.statusText;
	      }
	    }
	  }, {
	    key: "setConfig",
	    value: function setConfig(config) {
	      if (!main_core.Type.isPlainObject(config)) {
	        return;
	      }
	      this.config = config;
	      if (!this.isDesktop() || this.slave) {
	        this.renderCrmButtons();
	      }
	      this.setOnSlave(desktopEvents.setConfig, [config]);
	    }
	  }, {
	    key: "setCallId",
	    value: function setCallId(callId) {
	      this.callId = callId;
	      this.setOnSlave(desktopEvents.setCallId, [callId]);
	    }
	  }, {
	    key: "setLineNumber",
	    value: function setLineNumber(lineNumber) {
	      this.lineNumber = lineNumber;
	      this.setOnSlave(desktopEvents.setLineNumber, [lineNumber]);
	    }
	  }, {
	    key: "setCompanyPhoneNumber",
	    value: function setCompanyPhoneNumber(companyPhoneNumber) {
	      this.companyPhoneNumber = companyPhoneNumber;
	      this.setOnSlave(desktopEvents.setCompanyPhoneNumber, [companyPhoneNumber]);
	    }
	  }, {
	    key: "setButtons",
	    value: function setButtons(buttons, layout) {
	      if (!ButtonLayouts[layout]) {
	        layout = ButtonLayouts.centered;
	      }
	      this.buttonLayout = layout;
	      this.buttons = buttons;
	      this.renderButtons();
	    }
	  }, {
	    key: "setUiState",
	    value: function setUiState(uiState) {
	      this._uiState = uiState;
	      var stateButtons = this.getUiStateButtons(uiState);
	      this.buttons = stateButtons.buttons;
	      this.buttonLayout = stateButtons.layout;
	      switch (uiState) {
	        case UiState.incoming:
	          this.setClosable(false);
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          this.stopTimer();
	          break;
	        case UiState.transferIncoming:
	          this.setClosable(false);
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          this.stopTimer();
	          break;
	        case UiState.outgoing:
	          this.setClosable(true);
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          this.stopTimer();
	          this.hideCallIcon();
	          break;
	        case UiState.connectingIncoming:
	          this.setClosable(false);
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          this.stopTimer();
	          break;
	        case UiState.connectingOutgoing:
	          this.setClosable(false);
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          this.showCallIcon();
	          this.stopTimer();
	          break;
	        case UiState.connected:
	          if (this.deviceCall) {
	            this.setClosable(true);
	          } else {
	            this.setClosable(false);
	          }
	          this.showSections(['status', 'timer']);
	          this.renderCrmButtons();
	          this.showCallIcon();
	          this.startTimer();
	          break;
	        case UiState.transferring:
	          this.setClosable(false);
	          this.showSections(['status', 'timer']);
	          this.renderCrmButtons();
	          break;
	        case UiState.idle:
	          this.setClosable(true);
	          this.stopTimer();
	          this.hideCallIcon();
	          this.showOnlySections(['status']);
	          this.renderCrmButtons();
	          break;
	        case UiState.error:
	          this.setClosable(true);
	          this.stopTimer();
	          this.hideCallIcon();
	          break;
	        case UiState.moneyError:
	          this.setClosable(true);
	          this.stopTimer();
	          this.hideCallIcon();
	          break;
	        case UiState.sipPhoneError:
	          this.setClosable(true);
	          this.stopTimer();
	          this.hideCallIcon();
	          break;
	        case UiState.redial:
	          this.setClosable(true);
	          this.stopTimer();
	          this.hideCallIcon();
	          break;
	      }
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.setUiState, [uiState]);
	        return;
	      }
	      this.renderButtons();
	    }
	  }, {
	    key: "setCallState",
	    /**
	     * @param {string} callState
	     * @param {object} additionalParams
	     * @see CallState
	     */
	    value: function setCallState(callState, additionalParams) {
	      if (this.callState === callState) {
	        return;
	      }
	      this.callState = callState;
	      if (!main_core.Type.isPlainObject(additionalParams)) {
	        additionalParams = {};
	      }
	      this.renderButtons();
	      if (callState === CallState.connected && this.isAutoFoldAllowed()) {
	        this.fold();
	      }
	      BX.onCustomEvent(window, "CallCard::CallStateChanged", [callState, additionalParams]);
	      this.setOnSlave(desktopEvents.setCallState, [callState, additionalParams]);
	    }
	  }, {
	    key: "isAutoFoldAllowed",
	    value: function isAutoFoldAllowed() {
	      return this.autoFold === true && !this.isDesktop() && !this.isFolded() && this.restApps.length === 0;
	    }
	  }, {
	    key: "isHeld",
	    value: function isHeld() {
	      return this.held;
	    }
	  }, {
	    key: "setHeld",
	    value: function setHeld(held) {
	      this.held = held;
	    }
	  }, {
	    key: "setRecording",
	    value: function setRecording(recording) {
	      this.recording = recording;
	    }
	  }, {
	    key: "isRecording",
	    value: function isRecording() {
	      return this.recording;
	    }
	  }, {
	    key: "isMuted",
	    value: function isMuted() {
	      return this.muted;
	    }
	  }, {
	    key: "setMuted",
	    value: function setMuted(muted) {
	      this.muted = muted;
	    }
	  }, {
	    key: "isTransfer",
	    value: function isTransfer() {
	      return this.transfer;
	    }
	  }, {
	    key: "setTransfer",
	    value: function setTransfer(transfer) {
	      transfer = transfer === true;
	      if (this.transfer == transfer) {
	        return;
	      }
	      this.transfer = transfer;
	      this.setOnSlave(desktopEvents.setTransfer, [transfer]);
	      this.setUiState(this._uiState);
	    }
	  }, {
	    key: "isCallback",
	    value: function isCallback() {
	      return this.direction === Direction.callback;
	    }
	  }, {
	    key: "isPortalCall",
	    value: function isPortalCall() {
	      return this.portalCall;
	    }
	  }, {
	    key: "setCallback",
	    value: function setCallback(eventName, callback) {
	      if (!this.callbacks.hasOwnProperty(eventName)) {
	        return false;
	      }
	      this.callbacks[eventName] = main_core.Type.isFunction(callback) ? callback : nop;
	    }
	  }, {
	    key: "setDeviceCall",
	    value: function setDeviceCall(deviceCall) {
	      this.deviceCall = deviceCall;
	      if (this.elements.buttons.sipPhone) {
	        if (deviceCall) {
	          main_core.Dom.addClass(this.elements.buttons.sipPhone, 'active');
	        } else {
	          main_core.Dom.removeClass(this.elements.buttons.sipPhone, 'active');
	        }
	      }
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.setDeviceCall, [deviceCall]);
	      }
	    }
	  }, {
	    key: "setCrmEntity",
	    value: function setCrmEntity(params) {
	      this.crmEntityType = params.type;
	      this.crmEntityId = params.id;
	      this.crmActivityId = params.activityId || '';
	      this.crmActivityEditUrl = params.activityEditUrl || '';
	      this.crmBindings = main_core.Type.isArray(params.bindings) ? params.bindings : [];
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.setCrmEntity, [params]);
	      }
	    }
	  }, {
	    key: "setCrmData",
	    value: function setCrmData(crmData) {
	      if (!main_core.Type.isPlainObject(crmData)) {
	        return;
	      }
	      this.crm = true;
	      this.crmData = crmData;
	    }
	  }, {
	    key: "loadCrmCard",
	    value: function loadCrmCard(entityType, entityId) {
	      var _this12 = this;
	      BX.onCustomEvent(window, 'CallCard::EntityChanged', [{
	        'CRM_ENTITY_TYPE': entityType,
	        'CRM_ENTITY_ID': entityId,
	        'PHONE_NUMBER': this.phoneNumber
	      }]);
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.entityChanged, {
	        'CRM_ENTITY_TYPE': entityType,
	        'CRM_ENTITY_ID': entityId,
	        'PHONE_NUMBER': this.phoneNumber
	      });
	      var enableCopilotReplacement = 'Y';
	      if (this.isCallListMode()) {
	        enableCopilotReplacement = 'N';
	      }
	      BX.ajax.runAction("voximplant.callview.getCrmCard", {
	        data: {
	          entityType: entityType,
	          entityId: entityId,
	          isEnableCopilotReplacement: enableCopilotReplacement
	        }
	      }).then(function (response) {
	        if (_this12.currentLayout == layouts.simple) {
	          _this12.currentLayout = layouts.crm;
	          _this12.crm = true;
	          var newMainElement = _this12.createLayoutCrm();
	          _this12.elements.main.parentNode.replaceChild(newMainElement, _this12.elements.main);
	          _this12.elements.main = newMainElement;
	          _this12.setUiState(_this12._uiState);
	          _this12.setStatusText(_this12.statusText);
	        }
	        if (_this12.elements.crmCard) {
	          BX.html(_this12.elements.crmCard, response.data.html);
	          setTimeout(function () {
	            if (_this12.isDesktop()) {
	              _this12.resizeWindow(_this12.getInitialWidth(), _this12.getInitialHeight());
	            }
	            _this12.adjust();
	            _this12.bindCrmCardEvents();
	          }, 100);
	        }
	        _this12.renderCrmButtons();
	      })["catch"](function (response) {
	        return console.error("Could not load crm card: ", response.errors[0]);
	      });
	    }
	  }, {
	    key: "reloadCrmCard",
	    value: function reloadCrmCard() {
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.reloadCrmCard, []);
	      } else {
	        this.loadCrmCard(this.crmEntityType, this.crmEntityId);
	      }
	    }
	  }, {
	    key: "bindCrmCardEvents",
	    value: function bindCrmCardEvents() {
	      if (!this.elements.crmCard) {
	        return;
	      }
	      if (!BX.Crm || !BX.Crm.Page) {
	        return;
	      }
	      var anchors = this.elements.crmCard.querySelectorAll('a[data-use-slider=Y]');
	      for (var i = 0; i < anchors.length; i++) {
	        BX.bind(anchors[i], 'click', this.onCrmAnchorClick.bind(this));
	      }
	    }
	  }, {
	    key: "onCrmAnchorClick",
	    value: function onCrmAnchorClick(e) {
	      if (BX.Crm.Page.isSliderEnabled(e.currentTarget.href)) {
	        if (!this.isFolded()) {
	          this.fold();
	        }
	      }
	    }
	  }, {
	    key: "setPortalCallUserId",
	    value: function setPortalCallUserId(userId) {
	      var _this13 = this;
	      this.portalCallUserId = userId;
	      this.setOnSlave(desktopEvents.setPortalCallUserId, [userId]);
	      if (this.portalCallData && this.portalCallData.users[this.portalCallUserId]) {
	        this.renderAvatar();
	        this.createTitle().then(function (title) {
	          return _this13.setTitle(title);
	        });
	      }
	    }
	  }, {
	    key: "setPortalCallQueueName",
	    value: function setPortalCallQueueName(queueName) {
	      var _this14 = this;
	      this.portalCallQueueName = queueName;
	      this.setOnSlave(desktopEvents.setPortalCallQueueName, [queueName]);
	      this.createTitle().then(function (title) {
	        return _this14.setTitle(title);
	      });
	    }
	  }, {
	    key: "setPortalCall",
	    value: function setPortalCall(portalCall) {
	      this.portalCall = portalCall === true;
	      this.setOnSlave(desktopEvents.setPortalCall, [portalCall]);
	    }
	  }, {
	    key: "setPortalCallData",
	    value: function setPortalCallData(data) {
	      this.portalCallData = data;
	      this.setOnSlave(desktopEvents.setPortalCallData, [data]);
	    }
	  }, {
	    key: "setOnSlave",
	    value: function setOnSlave(message, parameters) {
	      if (this.isDesktop() && !this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(message, parameters);
	      }
	    }
	  }, {
	    key: "updateView",
	    value: function updateView() {
	      if (this.elements.title) {
	        this.elements.title.innerHTML = this.renderTitle();
	      }
	      if (this.elements.progress) {
	        main_core.Dom.clean(this.elements.progress);
	        this.elements.progress.appendChild(this.renderProgress(this.progress));
	      }
	      if (this.elements.status) {
	        this.elements.status.innerText = this.statusText;
	      }
	      this.renderButtons();
	      this.renderTimer();
	    }
	  }, {
	    key: "renderProgress",
	    value: function renderProgress(progress) {
	      var result;
	      switch (progress) {
	        case CallProgress.connect:
	          result = main_core.Dom.create("div", {
	            props: {
	              className: 'bx-messenger-call-overlay-progress'
	            },
	            children: [main_core.Dom.create("img", {
	              props: {
	                className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-1'
	              }
	            }), main_core.Dom.create("img", {
	              props: {
	                className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-2'
	              }
	            })]
	          });
	          break;
	        case CallProgress.online:
	          result = main_core.Dom.create("div", {
	            props: {
	              className: 'bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-online'
	            },
	            children: [main_core.Dom.create("img", {
	              props: {
	                className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-3'
	              }
	            })]
	          });
	          break;
	        case CallProgress.error:
	          progress = CallProgress.offline;
	        // fallthrough to default
	        default:
	          result = main_core.Dom.create("div", {
	            props: {
	              className: 'bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-' + progress
	            }
	          });
	      }
	      return result;
	    }
	  }, {
	    key: "getUiStateButtons",
	    /**
	     * @param uiState UiState
	     * @returns object {buttons: string[], layout: string}
	     */
	    value: function getUiStateButtons(uiState) {
	      var result = {
	        buttons: [],
	        layout: ButtonLayouts.centered
	      };
	      switch (uiState) {
	        case UiState.incoming:
	          result.buttons = ['answer', 'skip'];
	          break;
	        case UiState.transferIncoming:
	          result.buttons = ['answer', 'skip'];
	          break;
	        case UiState.outgoing:
	          result.buttons = ['call'];
	          if (this.callListId > 0) {
	            result.buttons.push('next');
	            result.buttons.push('fold');
	            if (!this.isDesktop()) {
	              result.buttons.push('topClose');
	            }
	          }
	          break;
	        case UiState.connectingIncoming:
	          result.buttons = ['hangup'];
	          break;
	        case UiState.connectingOutgoing:
	          if (this.hasSipPhone) {
	            result.buttons.push('sipPhone');
	          }
	          result.buttons.push('hangup');
	          break;
	        case UiState.error:
	          if (this.hasSipPhone) {
	            result.buttons.push('sipPhone');
	          }
	          if (this.callListId > 0) {
	            result.buttons.push('redial', 'next', 'topClose');
	          } else {
	            result.buttons.push('close');
	          }
	          break;
	        case UiState.moneyError:
	          result.buttons = ['notifyAdmin', 'close'];
	          break;
	        case UiState.sipPhoneError:
	          result.buttons = ['sipPhone', 'close'];
	          break;
	        case UiState.connected:
	          result.buttons = this.isTransfer() ? [] : ['hold'];
	          if (!this.deviceCall) {
	            result.buttons.push('mute', 'qualityMeter');
	          }
	          result.buttons.push('fold');
	          if (!this.callListId && !this.isTransfer()) {
	            result.buttons.push('transfer');
	          }
	          if (this.deviceCall) {
	            result.buttons.push('close');
	          } else {
	            result.buttons.push('dialpad', 'hangup');
	          }
	          result.layout = ButtonLayouts.spaced;
	          break;
	        case UiState.transferring:
	          result.buttons = ['transferComplete', 'transferCancel'];
	          break;
	        case UiState.transferFailed:
	          result.buttons = ['transferCancel'];
	          break;
	        case UiState.transferConnected:
	          result.buttons = ['hangup'];
	          break;
	        case UiState.idle:
	          if (this.hasSipPhone) {
	            result.buttons = ['close'];
	          } else if (this.direction == Direction.incoming) {
	            result.buttons = ['close'];
	          } else if (this.direction == Direction.outgoing) {
	            result.buttons = ['redial'];
	            if (this.callListId > 0) {
	              result.buttons.push('next');
	              result.buttons.push('fold');
	            } else {
	              result.buttons.push('close');
	            }
	          }
	          if (this.callListId > 0 && !this.isDesktop()) {
	            result.buttons.push('topClose');
	          }
	          break;
	        case UiState.redial:
	          result.buttons = ['redial'];
	          break;
	        case UiState.externalCard:
	          result.buttons = ['close'];
	          result.buttons.push('fold');
	          break;
	      }
	      return result;
	    }
	  }, {
	    key: "renderButtons",
	    value: function renderButtons() {
	      if (this.isFolded()) {
	        this.renderButtonsFolded();
	      } else {
	        this.renderButtonsDefault();
	      }
	    }
	  }, {
	    key: "renderButtonsDefault",
	    value: function renderButtonsDefault() {
	      var _this15 = this;
	      var buttonsFragment = document.createDocumentFragment();
	      var topButtonsFragment = document.createDocumentFragment();
	      var topLevelButtonsFragment = document.createDocumentFragment();
	      var subContainers = {
	        left: null,
	        right: null
	      };
	      this.elements.buttons = {};
	      if (this.buttonLayout == ButtonLayouts.spaced) {
	        subContainers.left = main_core.Dom.create('div', {
	          props: {
	            className: 'im-phone-call-buttons-container-left'
	          }
	        });
	        subContainers.right = main_core.Dom.create('div', {
	          props: {
	            className: 'im-phone-call-buttons-container-right'
	          }
	        });
	        buttonsFragment.appendChild(subContainers.left);
	        buttonsFragment.appendChild(subContainers.right);
	      }
	      this.buttons.forEach(function (buttonName) {
	        var buttonNode;
	        switch (buttonName) {
	          case 'hold':
	            buttonNode = renderSimpleButton('', 'im-phone-call-btn-hold', _this15._onHoldButtonClickHandler);
	            if (_this15.isHeld()) {
	              main_core.Dom.addClass(buttonNode, 'active');
	            }
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.left.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'mute':
	            buttonNode = renderSimpleButton('', 'im-phone-call-btn-mute', _this15._onMuteButtonClickHandler);
	            if (_this15.isMuted()) {
	              main_core.Dom.addClass(buttonNode, 'active');
	            }
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.left.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'transfer':
	            buttonNode = renderSimpleButton('', 'im-phone-call-btn-transfer', _this15._onTransferButtonClickHandler);
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.left.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'transferComplete':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_TRANSFER'), 'im-phone-call-btn im-phone-call-btn-blue im-phone-call-btn-arrow', _this15._onTransferCompleteButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'transferCancel':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_RETURN'), 'im-phone-call-btn im-phone-call-btn-red', _this15._onTransferCancelButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'dialpad':
	            buttonNode = renderSimpleButton('', 'im-phone-call-btn-dialpad', _this15._onDialpadButtonClickHandler);
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.left.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'call':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_PHONE_CALL'), 'im-phone-call-btn im-phone-call-btn-green', _this15._onMakeCallButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'answer':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_PHONE_BTN_ANSWER'), 'im-phone-call-btn im-phone-call-btn-green', _this15._onAnswerButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'skip':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_PHONE_BTN_BUSY'), 'im-phone-call-btn im-phone-call-btn-red', _this15._onSkipButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'hangup':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_HANGUP'), 'im-phone-call-btn im-phone-call-btn-red  im-phone-call-btn-tube', _this15._onHangupButtonClickHandler);
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.right.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'close':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_CLOSE'), 'im-phone-call-btn im-phone-call-btn-red', _this15._onCloseButtonClickHandler);
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.right.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'topClose':
	            if (!_this15.isDesktop()) {
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-top-close-btn'
	                },
	                events: {
	                  click: _this15._onCloseButtonClickHandler
	                }
	              });
	              topLevelButtonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'notifyAdmin':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_NOTIFY_ADMIN'), 'im-phone-call-btn im-phone-call-btn-blue im-phone-call-btn-arrow', function () {
	              _this15.backgroundWorker.isUsed ? _this15.backgroundWorker.emitEvent(backgroundWorkerEvents.notifyAdminButtonClick) : _this15.callbacks.notifyAdmin();
	            });
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'sipPhone':
	            buttonNode = renderSimpleButton('', _this15.deviceCall ? 'im-phone-call-btn-phone active' : 'im-phone-call-btn-phone', _this15._onSwitchDeviceButtonClickHandler);
	            if (_this15.buttonLayout == ButtonLayouts.spaced) {
	              subContainers.left.appendChild(buttonNode);
	            } else {
	              buttonsFragment.appendChild(buttonNode);
	            }
	            break;
	          case 'qualityMeter':
	            buttonNode = main_core.Dom.create("span", {
	              props: {
	                className: 'im-phone-call-btn-signal'
	              },
	              events: {
	                click: _this15._onQualityMeterClickHandler
	              },
	              children: [main_core.Dom.create("span", {
	                props: {
	                  className: 'im-phone-call-btn-signal-icon-container'
	                },
	                children: [main_core.Dom.create("span", {
	                  props: {
	                    className: 'im-phone-call-btn-signal-background'
	                  }
	                }), _this15.elements.qualityMeter = main_core.Dom.create("span", {
	                  props: {
	                    className: 'im-phone-call-btn-signal-active'
	                  },
	                  style: {
	                    width: _this15.getQualityMeterWidth()
	                  }
	                })]
	              })]
	            });
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'settings':
	            // todo
	            break;
	          case 'next':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_NEXT'), 'im-phone-call-btn im-phone-call-btn-gray im-phone-call-btn-arrow', _this15._onNextButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'redial':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_RECALL'), 'im-phone-call-btn im-phone-call-btn-green', _this15._onMakeCallButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'fold':
	            if (!_this15.isDesktop() && _this15.canBeFolded()) {
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-btn-arrow'
	                },
	                text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_FOLD'),
	                events: {
	                  click: _this15._onFoldButtonClickHandler
	                }
	              });
	              topButtonsFragment.appendChild(buttonNode);
	            }
	            break;
	          default:
	            throw "Unknown button " + buttonName;
	        }
	        if (buttonNode) {
	          _this15.elements.buttons[buttonName] = buttonNode;
	        }
	      });
	      if (this.elements.buttonsContainer) {
	        main_core.Dom.clean(this.elements.buttonsContainer);
	        this.elements.buttonsContainer.appendChild(buttonsFragment);
	      }
	      if (this.elements.topButtonsContainer) {
	        main_core.Dom.clean(this.elements.topButtonsContainer);
	        this.elements.topButtonsContainer.appendChild(topButtonsFragment);
	      }
	      if (this.elements.topLevelButtonsContainer) {
	        main_core.Dom.clean(this.elements.topLevelButtonsContainer);
	        this.elements.topLevelButtonsContainer.appendChild(topLevelButtonsFragment);
	      }
	    }
	  }, {
	    key: "renderButtonsFolded",
	    value: function renderButtonsFolded() {
	      var _this16 = this;
	      var buttonsFragment = document.createDocumentFragment();
	      var buttonNode;
	      this.elements.buttons = {};
	      this.buttons.forEach(function (buttonName) {
	        switch (buttonName) {
	          case 'hangup':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_HANGUP'), 'im-phone-call-panel-mini-cancel', _this16._onHangupButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	          case 'close':
	            buttonNode = renderSimpleButton(main_core.Loc.getMessage('IM_M_CALL_BTN_CLOSE'), 'im-phone-call-panel-mini-cancel', _this16._onCloseButtonClickHandler);
	            buttonsFragment.appendChild(buttonNode);
	            break;
	        }
	      });
	      if (this.elements.buttonsContainer) {
	        main_core.Dom.clean(this.elements.buttonsContainer);
	        this.elements.buttonsContainer.appendChild(buttonsFragment);
	      }
	    }
	  }, {
	    key: "renderCrmButtons",
	    value: function renderCrmButtons() {
	      var _this17 = this;
	      var buttonsFragment = document.createDocumentFragment();
	      this.elements.crmButtons = {};
	      if (!this.elements.crmButtonsContainer) {
	        return;
	      }
	      var buttons = ['addComment'];
	      if (this.crmEntityType == 'CONTACT') {
	        buttons.push('addDeal');
	        buttons.push('addInvoice');
	      } else if (this.crmEntityType == 'COMPANY') {
	        buttons.push('addDeal');
	        buttons.push('addInvoice');
	      } else if (!this.crmEntityType && this.config.CRM_CREATE == 'none') {
	        buttons.push('addLead');
	        buttons.push('addContact');
	      }
	      if (buttons.length > 0) {
	        buttons.forEach(function (buttonName) {
	          var buttonNode;
	          switch (buttonName) {
	            case 'addComment':
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-button im-phone-call-crm-button-comment' + (_this17.commentShown ? ' im-phone-call-crm-button-active' : '')
	                },
	                children: [_this17.elements.crmButtons.addCommentLabel = main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-crm-button-item'
	                  },
	                  text: _this17.commentShown ? main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_SAVE') : main_core.Loc.getMessage('IM_PHONE_ACTION_CRM_COMMENT')
	                })],
	                events: {
	                  click: _this17._onAddCommentButtonClick.bind(_this17)
	                }
	              });
	              break;
	            case 'addDeal':
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-button'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-crm-button-item'
	                  },
	                  text: main_core.Loc.getMessage('IM_PHONE_ACTION_CRM_DEAL')
	                })],
	                events: {
	                  click: _this17._onAddDealButtonClick.bind(_this17)
	                }
	              });
	              break;
	            case 'addInvoice':
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-button'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-crm-button-item'
	                  },
	                  text: main_core.Loc.getMessage('IM_PHONE_ACTION_CRM_INVOICE')
	                })],
	                events: {
	                  click: _this17._onAddInvoiceButtonClick.bind(_this17)
	                }
	              });
	              break;
	            case 'addLead':
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-button'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-crm-button-item'
	                  },
	                  text: main_core.Loc.getMessage('IM_CRM_BTN_NEW_LEAD')
	                })],
	                events: {
	                  click: _this17._onAddLeadButtonClick.bind(_this17)
	                }
	              });
	              break;
	            case 'addContact':
	              buttonNode = main_core.Dom.create("div", {
	                props: {
	                  className: 'im-phone-call-crm-button'
	                },
	                children: [main_core.Dom.create("div", {
	                  props: {
	                    className: 'im-phone-call-crm-button-item'
	                  },
	                  text: main_core.Loc.getMessage('IM_CRM_BTN_NEW_CONTACT')
	                })],
	                events: {
	                  click: _this17._onAddContactButtonClick.bind(_this17)
	                }
	              });
	              break;
	          }
	          if (buttonNode) {
	            buttonsFragment.appendChild(buttonNode);
	            _this17.elements.crmButtons[buttonName] = buttonNode;
	          }
	        });
	        main_core.Dom.clean(this.elements.crmButtonsContainer);
	        this.elements.crmButtonsContainer.appendChild(buttonsFragment);
	        this.showSections(['crmButtons']);
	      } else {
	        main_core.Dom.clean(this.elements.crmButtonsContainer);
	        this.hideSections(['crmButtons']);
	      }
	    }
	  }, {
	    key: "loadForm",
	    value: function loadForm(params) {
	      if (!this.formManager) {
	        return;
	      }
	      this.formManager.load({
	        id: params.id,
	        secCode: params.secCode
	      });
	    }
	  }, {
	    key: "unloadForm",
	    value: function unloadForm() {
	      if (!this.formManager) {
	        return;
	      }
	      this.formManager.unload();
	      main_core.Dom.clean(this.elements.tabsBody.webform);
	    }
	  }, {
	    key: "_onFormSend",
	    value: function _onFormSend(e) {
	      if (!this.callListView) {
	        return;
	      }
	      var currentElement = this.callListView.getCurrentElement();
	      this.callListView.setWebformResult(currentElement.ELEMENT_ID, e.resultId);
	    }
	  }, {
	    key: "loadRestApp",
	    value: function loadRestApp(params) {
	      var _this18 = this;
	      var restAppId = params.id;
	      var node = params.node;
	      if (this.restAppLayoutLoaded) {
	        BX.rest.AppLayout.getPlacement('CALL_CARD').load(restAppId, this.getPlacementOptions());
	        return;
	      }
	      if (this.restAppLayoutLoading) {
	        return;
	      }
	      this.restAppLayoutLoading = true;
	      BX.ajax.runAction("voximplant.callView.loadRestApp", {
	        data: {
	          'appId': restAppId,
	          'placementOptions': this.getPlacementOptions()
	        }
	      }).then(function (response) {
	        if (!_this18.popup && !_this18.isDesktop()) {
	          return;
	        }
	        main_core.Runtime.html(node, response.data.html);
	        _this18.restAppLayoutLoaded = true;
	        _this18.restAppLayoutLoading = false;
	        _this18.restAppInterface = BX.rest.AppLayout.initializePlacement('CALL_CARD');
	        _this18.initializeAppInterface(_this18.restAppInterface);
	      });
	    }
	  }, {
	    key: "unloadRestApps",
	    value: function unloadRestApps() {
	      if (!BX.rest || !BX.rest.AppLayout) {
	        return false;
	      }
	      var placement = BX.rest.AppLayout.getPlacement('CALL_CARD');
	      if (this.restAppLayoutLoaded && placement) {
	        placement.destroy();
	        this.restAppLayoutLoaded = false;
	      }
	    }
	  }, {
	    key: "initializeAppInterface",
	    value: function initializeAppInterface(appInterface) {
	      var _this19 = this;
	      appInterface.prototype.events.push('CallCard::EntityChanged');
	      appInterface.prototype.events.push('CallCard::BeforeClose');
	      appInterface.prototype.events.push('CallCard::CallStateChanged');
	      appInterface.prototype.getStatus = function (params, cb) {
	        cb(_this19.getPlacementOptions());
	      };
	      appInterface.prototype.disableAutoClose = function (params, cb) {
	        _this19.disableAutoClose();
	        cb([]);
	      };
	      appInterface.prototype.enableAutoClose = function (params, cb) {
	        _this19.enableAutoClose();
	        cb([]);
	      };
	    }
	  }, {
	    key: "getPlacementOptions",
	    value: function getPlacementOptions() {
	      return {
	        'CALL_ID': this.callId,
	        'PHONE_NUMBER': this.phoneNumber === "unknown" ? undefined : this.phoneNumber,
	        'LINE_NUMBER': this.lineNumber,
	        'LINE_NAME': this.companyPhoneNumber,
	        'CRM_ENTITY_TYPE': this.crmEntityType,
	        'CRM_ENTITY_ID': this.crmEntityId,
	        'CRM_ACTIVITY_ID': this.crmActivityId === 0 ? undefined : this.crmActivityId,
	        'CRM_BINDINGS': this.crmBindings,
	        'CALL_DIRECTION': this.direction,
	        'CALL_STATE': this.callState,
	        'CALL_LIST_MODE': this.callListId > 0
	      };
	    }
	  }, {
	    key: "isUnloadAllowed",
	    value: function isUnloadAllowed() {
	      if (this.backgroundWorker.isActiveIntoCurrentCall()) {
	        return false;
	      }
	      return this.folded && (this.deviceCall || this._uiState === UiState.idle || this._uiState === UiState.error || this._uiState === UiState.externalCard);
	    }
	  }, {
	    key: "_onBeforeUnload",
	    value: function _onBeforeUnload(e) {
	      if (!this.isUnloadAllowed()) {
	        e.returnValue = main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_DONT_LEAVE');
	        return main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_DONT_LEAVE');
	      }
	    }
	  }, {
	    key: "_onDblClick",
	    value: function _onDblClick(e) {
	      e.preventDefault();
	      if (!this.isFolded() && this.canBeFolded()) {
	        this.fold();
	      }
	    }
	  }, {
	    key: "_onHoldButtonClick",
	    value: function _onHoldButtonClick() {
	      if (this.isHeld()) {
	        this.held = false;
	        main_core.Dom.removeClass(this.elements.buttons.hold, 'active');
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onUnHold, []);
	        } else {
	          this.callbacks.unhold();
	        }
	      } else {
	        this.held = true;
	        main_core.Dom.addClass(this.elements.buttons.hold, 'active');
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onHold, []);
	        } else {
	          this.callbacks.hold();
	        }
	      }
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.holdButtonClick, this.isHeld());
	    }
	  }, {
	    key: "_onMuteButtonClick",
	    value: function _onMuteButtonClick() {
	      if (this.isMuted()) {
	        this.muted = false;
	        main_core.Dom.removeClass(this.elements.buttons.mute, 'active');
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onUnMute, []);
	        } else {
	          this.callbacks.unmute();
	        }
	      } else {
	        this.muted = true;
	        main_core.Dom.addClass(this.elements.buttons.mute, 'active');
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onMute, []);
	        } else {
	          this.callbacks.mute();
	        }
	      }
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.muteButtonClick, this.isMuted());
	    }
	  }, {
	    key: "_onTransferButtonClick",
	    value: function _onTransferButtonClick() {
	      var _this20 = this;
	      this.selectTransferTarget(function (result) {
	        _this20.backgroundWorker.emitEvent(backgroundWorkerEvents.transferButtonClick, result);
	        if (_this20.isDesktop() && _this20.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onStartTransfer, [result]);
	        } else {
	          _this20.callbacks.transfer(result);
	        }
	      });
	    }
	  }, {
	    key: "_onTransferCompleteButtonClick",
	    value: function _onTransferCompleteButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.completeTransferButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCompleteTransfer, []);
	      } else {
	        this.callbacks.completeTransfer();
	      }
	    }
	  }, {
	    key: "_onTransferCancelButtonClick",
	    value: function _onTransferCancelButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.cancelTransferButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCancelTransfer, []);
	      } else {
	        this.callbacks.cancelTransfer();
	      }
	    }
	  }, {
	    key: "_onDialpadButtonClick",
	    value: function _onDialpadButtonClick() {
	      var _this21 = this;
	      this.keypad = new Keypad({
	        bindElement: this.elements.buttons.dialpad,
	        hideDial: true,
	        onButtonClick: function onButtonClick(e) {
	          var key = e.key;
	          if (_this21.isDesktop() && _this21.slave) {
	            im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onDialpadButtonClicked, [key]);
	          } else {
	            _this21.callbacks.dialpadButtonClicked(key);
	          }
	          _this21.backgroundWorker.emitEvent(backgroundWorkerEvents.dialpadButtonClick, key);
	        },
	        onClose: function onClose() {
	          _this21.keypad.destroy();
	          _this21.keypad = null;
	        }
	      });
	      this.keypad.show();
	    }
	  }, {
	    key: "_onHangupButtonClick",
	    value: function _onHangupButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.hangupButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onHangup, []);
	      } else {
	        this.callbacks.hangup();
	      }
	    }
	  }, {
	    key: "_onCloseButtonClick",
	    value: function _onCloseButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.closeButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onClose, []);
	      } else {
	        this.close();
	      }
	    }
	  }, {
	    key: "_onMakeCallButtonClick",
	    value: function _onMakeCallButtonClick() {
	      var _this22 = this;
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.makeCallButtonClick);
	      var event = {};
	      if (this.callListId > 0) {
	        this.callingEntity = this.currentEntity;
	        if (this.currentEntity.phones.length === 0) {
	          // show keypad and dial entered number
	          this.keypad = new Keypad({
	            bindElement: this.elements.buttons.call ? this.elements.buttons.call : null,
	            onClose: function onClose() {
	              _this22.keypad.destroy();
	              _this22.keypad = null;
	            },
	            onDial: function onDial(e) {
	              _this22.keypad.close();
	              _this22.phoneNumber = e.phoneNumber;
	              _this22.createTitle().then(function (title) {
	                return _this22.setTitle(title);
	              });
	              event = {
	                phoneNumber: e.phoneNumber,
	                crmEntityType: _this22.crmEntityType,
	                crmEntityId: _this22.crmEntityId,
	                callListId: _this22.callListId
	              };
	              if (_this22.isDesktop() && _this22.slave) {
	                im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
	              } else {
	                _this22.callbacks.callListMakeCall(event);
	              }
	            }
	          });
	          this.keypad.show();
	        } else if (this.currentEntity.phones.length == 1) {
	          // just dial the number
	          event.phoneNumber = this.currentEntity.phones[0].VALUE;
	          event.crmEntityType = this.crmEntityType;
	          event.crmEntityId = this.crmEntityId;
	          event.callListId = this.callListId;
	          if (this.isDesktop() && this.slave) {
	            im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
	          } else {
	            this.callbacks.callListMakeCall(event);
	          }
	        } else {
	          // allow user to select the number
	          this.showNumberSelectMenu({
	            bindElement: this.elements.buttons.call ? this.elements.buttons.call : null,
	            phoneNumbers: this.currentEntity.phones,
	            onSelect: function onSelect(e) {
	              _this22.closeNumberSelectMenu();
	              _this22.phoneNumber = e.phoneNumber;
	              _this22.createTitle().then(function (title) {
	                return _this22.setTitle(title);
	              });
	              event = {
	                phoneNumber: e.phoneNumber,
	                crmEntityType: _this22.crmEntityType,
	                crmEntityId: _this22.crmEntityId,
	                callListId: _this22.callListId
	              };
	              if (_this22.isDesktop() && _this22.slave) {
	                im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
	              } else {
	                _this22.callbacks.callListMakeCall(event);
	              }
	            }
	          });
	        }
	      } else {
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onMakeCall, [this.phoneNumber]);
	        } else {
	          this.callbacks.makeCall(this.phoneNumber);
	        }
	      }
	    }
	  }, {
	    key: "_onNextButtonClick",
	    value: function _onNextButtonClick() {
	      if (!this.callListView) {
	        return;
	      }
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.nextButtonClick);
	      this.setUiState(UiState.outgoing);
	      this.callListView.moveToNextItem();
	      this.setStatusText('');
	    }
	  }, {
	    key: "_onRedialButtonClick",
	    value: function _onRedialButtonClick(e) {}
	  }, {
	    key: "_onCommentChanged",
	    value: function _onCommentChanged() {
	      this.comment = this.elements.commentEditor.value;
	      //Update callView close timer when printing a comment
	      this.updateAutoCloseTimer();
	    }
	  }, {
	    key: "_onAddCommentButtonClick",
	    value: function _onAddCommentButtonClick() {
	      this.commentShown = !this.commentShown;
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onCommentShown, [this.commentShown]);
	      }
	      if (this.commentShown) {
	        if (this.elements.crmButtons.addComment) {
	          main_core.Dom.addClass(this.elements.crmButtons.addComment, 'im-phone-call-crm-button-active');
	          this.elements.crmButtons.addCommentLabel.innerText = main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_SAVE');
	        }
	        if (this.elements.commentEditor) {
	          this.elements.commentEditor.value = this.comment;
	          this.elements.commentEditor.focus();
	        }
	        if (this.elements.commentEditorContainer) {
	          this.elements.commentEditorContainer.style.removeProperty('display');
	        }
	      } else {
	        if (this.elements.crmButtons.addComment) {
	          main_core.Dom.removeClass(this.elements.crmButtons.addComment, 'im-phone-call-crm-button-active');
	          this.elements.crmButtons.addCommentLabel.innerText = main_core.Loc.getMessage('IM_PHONE_ACTION_CRM_COMMENT');
	        }
	        if (this.elements.commentEditorContainer) {
	          this.elements.commentEditorContainer.style.display = 'none';
	        }
	        if (this.isDesktop() && this.slave) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onSaveComment, [this.comment]);
	        } else {
	          this.saveComment();
	        }
	        this.backgroundWorker.emitEvent(backgroundWorkerEvents.addCommentButtonClick, this.comment);
	      }
	    }
	  }, {
	    key: "_onAddDealButtonClick",
	    value: function _onAddDealButtonClick() {
	      var url = this._getCrmEditUrl('DEAL', 0);
	      var externalContext = this._generateExternalContext();
	      if (this.crmEntityType === 'CONTACT') {
	        url = main_core.Uri.addParam(url, {
	          contact_id: this.crmEntityId
	        });
	      } else if (this.crmEntityType === 'COMPANY') {
	        url = main_core.Uri.addParam(url, {
	          company_id: this.crmEntityId
	        });
	      }
	      url = main_core.Uri.addParam(url, {
	        external_context: externalContext
	      });
	      if (this.callListId > 0) {
	        url = main_core.Uri.addParam(url, {
	          call_list_id: this.callListId
	        });
	        url = main_core.Uri.addParam(url, {
	          call_list_element: this.currentEntity.id
	        });
	      }
	      this.externalRequests[externalContext] = {
	        type: 'add',
	        context: externalContext,
	        window: window.open(url)
	      };
	    }
	  }, {
	    key: "_onAddInvoiceButtonClick",
	    value: function _onAddInvoiceButtonClick() {
	      var url = this._getCrmEditUrl('INVOICE', 0);
	      var externalContext = this._generateExternalContext();
	      url = main_core.Uri.addParam(url, {
	        redirect: "y"
	      });
	      if (this.crmEntityType === 'CONTACT') {
	        url = main_core.Uri.addParam(url, {
	          contact: this.crmEntityId
	        });
	      } else if (this.crmEntityType === 'COMPANY') {
	        url = main_core.Uri.addParam(url, {
	          company: this.crmEntityId
	        });
	      }
	      url = main_core.Uri.addParam(url, {
	        external_context: externalContext
	      });
	      if (this.callListId > 0) {
	        url = main_core.Uri.addParam(url, {
	          call_list_id: this.callListId
	        });
	        url = main_core.Uri.addParam(url, {
	          call_list_element: this.currentEntity.id
	        });
	      }
	      this.externalRequests[externalContext] = {
	        type: 'add',
	        context: externalContext,
	        window: window.open(url)
	      };
	    }
	  }, {
	    key: "_onAddLeadButtonClick",
	    value: function _onAddLeadButtonClick() {
	      var url = this._getCrmEditUrl('LEAD', 0);
	      url = main_core.Uri.addParam(url, {
	        phone: this.phoneNumber,
	        origin_id: 'VI_' + this.callId
	      });
	      window.open(url);
	    }
	  }, {
	    key: "_onAddContactButtonClick",
	    value: function _onAddContactButtonClick() {
	      var url = this._getCrmEditUrl('CONTACT', 0);
	      url = main_core.Uri.addParam(url, {
	        phone: this.phoneNumber,
	        origin_id: 'VI_' + this.callId
	      });
	      window.open(url);
	    }
	  }, {
	    key: "_onFoldButtonClick",
	    value: function _onFoldButtonClick() {
	      this.fold();
	    }
	  }, {
	    key: "_onAnswerButtonClick",
	    value: function _onAnswerButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.answerButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onAnswer, []);
	      } else {
	        this.callbacks.answer();
	      }
	    }
	  }, {
	    key: "_onSkipButtonClick",
	    value: function _onSkipButtonClick() {
	      this.backgroundWorker.emitEvent(backgroundWorkerEvents.skipButtonClick);
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onSkip, []);
	      } else {
	        this.callbacks.skip();
	      }
	    }
	  }, {
	    key: "_onSwitchDeviceButtonClick",
	    value: function _onSwitchDeviceButtonClick() {
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onSwitchDevice, [{
	          phoneNumber: this.phoneNumber
	        }]);
	      } else {
	        this.callbacks.switchDevice({
	          phoneNumber: this.phoneNumber
	        });
	      }
	    }
	  }, {
	    key: "_onQualityMeterClick",
	    value: function _onQualityMeterClick() {
	      var _this23 = this;
	      this.showQualityPopup({
	        onSelect: function onSelect(qualityGrade) {
	          _this23.backgroundWorker.emitEvent(backgroundWorkerEvents.qualityMeterClick, qualityGrade);
	          _this23.qualityGrade = qualityGrade;
	          _this23.closeQualityPopup();
	          if (_this23.isDesktop() && _this23.slave) {
	            im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onQualityGraded, [qualityGrade]);
	          } else {
	            _this23.callbacks.qualityGraded(qualityGrade);
	          }
	        }
	      });
	    }
	  }, {
	    key: "_onPullEventCrm",
	    value: function _onPullEventCrm(command, params) {
	      if (command === 'external_event') {
	        if (params.NAME === 'onCrmEntityCreate' && params.IS_CANCELED == false) {
	          var eventParams = params.PARAMS;
	          if (this.externalRequests[eventParams.context]) {
	            var crmEntityType = eventParams.entityTypeName;
	            var crmEntityId = eventParams.entityInfo.id;
	            if (this.callListView) {
	              var currentElement = this.callListView.getCurrentElement();
	            }
	          }
	        }
	      }
	    }
	  }, {
	    key: "onCallListSelectedItem",
	    value: function onCallListSelectedItem(entity) {
	      var _this24 = this;
	      this.currentEntity = entity;
	      this.crmEntityType = entity.type;
	      this.crmEntityId = entity.id;
	      this.comment = "";
	      if (main_core.Type.isArray(entity.bindings)) {
	        this.crmBindings = entity.bindings.map(function (value) {
	          return {
	            'ENTITY_TYPE': value.type,
	            'ENTITY_ID': value.id
	          };
	        });
	      } else {
	        this.crmBindings = [];
	      }
	      if (entity.phones.length > 0) {
	        this.phoneNumber = entity.phones[0].VALUE;
	      } else {
	        this.phoneNumber = 'unknown';
	      }
	      this.createTitle().then(function (title) {
	        return _this24.setTitle(title);
	      });
	      this.loadCrmCard(entity.type, entity.id);
	      if (this.currentTabName === 'webform') {
	        this.formManager.unload();
	        this.formManager.load({
	          id: this.webformId,
	          secCode: this.webformSecCode,
	          lang: main_core.Loc.getMessage("LANGUAGE_ID")
	        });
	      }
	      if (this._uiState === UiState.redial) {
	        this.setUiState(UiState.outgoing);
	      }
	      this.updateView();
	    }
	  }, {
	    key: "showCallIcon",
	    value: function showCallIcon() {
	      if (!this.callListView) {
	        return;
	      }
	      if (!this.callingEntity) {
	        return;
	      }
	      this.callListView.setCallingElement(this.callingEntity.statusId, this.callingEntity.index);
	    }
	  }, {
	    key: "hideCallIcon",
	    value: function hideCallIcon() {
	      if (!this.callListView) {
	        return;
	      }
	      this.callListView.resetCallingElement();
	    }
	  }, {
	    key: "isTimerStarted",
	    value: function isTimerStarted() {
	      return !!this.timerInterval;
	    }
	  }, {
	    key: "startTimer",
	    value: function startTimer() {
	      if (this.isTimerStarted()) {
	        return;
	      }
	      if (this.initialTimestamp === 0) {
	        this.initialTimestamp = new Date().getTime();
	      }
	      this.timerInterval = setInterval(this.renderTimer.bind(this), 1000);
	      this.renderTimer();
	    }
	  }, {
	    key: "renderTimer",
	    value: function renderTimer() {
	      if (!this.elements.timer) {
	        return;
	      }
	      var currentTimestamp = new Date().getTime();
	      var elapsedMilliSeconds = currentTimestamp - this.initialTimestamp;
	      var elapsedSeconds = Math.floor(elapsedMilliSeconds / 1000);
	      var minutes = Math.floor(elapsedSeconds / 60).toString();
	      if (minutes.length < 2) {
	        minutes = '0' + minutes;
	      }
	      var seconds = (elapsedSeconds % 60).toString();
	      if (seconds.length < 2) {
	        seconds = '0' + seconds;
	      }
	      var template = this.isRecording() ? main_core.Loc.getMessage('IM_PHONE_TIMER_WITH_RECORD') : main_core.Loc.getMessage('IM_PHONE_TIMER_WITHOUT_RECORD');
	      if (this.isFolded()) {
	        this.elements.timer.innerText = minutes + ':' + seconds;
	      } else {
	        this.elements.timer.innerText = template.replace('#MIN#', minutes).replace('#SEC#', seconds);
	      }
	    }
	  }, {
	    key: "stopTimer",
	    value: function stopTimer() {
	      if (!this.isTimerStarted()) {
	        return;
	      }
	      clearInterval(this.timerInterval);
	      this.timerInterval = null;
	    }
	  }, {
	    key: "showQualityPopup",
	    value: function showQualityPopup(params) {
	      var _this25 = this;
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (!main_core.Type.isFunction(params.onSelect)) {
	        params.onSelect = nop;
	      }
	      var elements = {
	        '1': null,
	        '2': null,
	        '3': null,
	        '4': null,
	        '5': null
	      };
	      this.qualityPopup = new main_popup.Popup({
	        id: 'PhoneCallViewQualityGrade',
	        bindElement: this.elements.qualityMeter,
	        targetContainer: document.body,
	        darkMode: true,
	        closeByEsc: true,
	        autoHide: true,
	        zIndex: baseZIndex + 200,
	        noAllPaddings: true,
	        overlay: {
	          backgroundColor: 'white',
	          opacity: 0
	        },
	        bindOptions: {
	          position: 'top'
	        },
	        angle: {
	          position: 'bottom',
	          offset: 30
	        },
	        cacheable: false,
	        content: main_core.Dom.create("div", {
	          props: {
	            className: 'im-phone-popup-rating'
	          },
	          children: [main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-popup-rating-title'
	            },
	            text: main_core.Loc.getMessage('IM_PHONE_CALL_VIEW_RATE_QUALITY')
	          }), main_core.Dom.create("div", {
	            props: {
	              className: 'im-phone-popup-rating-stars'
	            },
	            children: [elements['1'] = createStar(1, this.qualityGrade == '1', params.onSelect), elements['2'] = createStar(2, this.qualityGrade == '2', params.onSelect), elements['3'] = createStar(3, this.qualityGrade == '3', params.onSelect), elements['4'] = createStar(4, this.qualityGrade == '4', params.onSelect), elements['5'] = createStar(5, this.qualityGrade == '5', params.onSelect)],
	            events: {
	              mouseover: function mouseover() {
	                if (elements[_this25.qualityGrade]) {
	                  main_core.Dom.removeClass(elements[_this25.qualityGrade], 'im-phone-popup-rating-stars-item-active');
	                }
	              },
	              mouseout: function mouseout() {
	                if (elements[_this25.qualityGrade]) {
	                  main_core.Dom.addClass(elements[_this25.qualityGrade], 'im-phone-popup-rating-stars-item-active');
	                }
	              }
	            }
	          })]
	        }),
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this25.qualityPopup = null;
	          }
	        }
	      });
	      this.qualityPopup.show();
	    }
	  }, {
	    key: "closeQualityPopup",
	    value: function closeQualityPopup() {
	      if (this.qualityPopup) {
	        this.qualityPopup.close();
	      }
	    }
	  }, {
	    key: "saveComment",
	    value: function saveComment() {
	      this.callbacks.saveComment({
	        callId: this.callId,
	        comment: this.comment
	      });
	    }
	  }, {
	    key: "showNumberSelectMenu",
	    value: function showNumberSelectMenu(params) {
	      var _this26 = this;
	      var menuItems = [];
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (!main_core.Type.isArray(params.phoneNumbers)) {
	        return;
	      }
	      params.onSelect = main_core.Type.isFunction(params.onSelect) ? params.onSelect : BX.DoNothing;
	      params.phoneNumbers.forEach(function (phoneNumber) {
	        menuItems.push({
	          id: 'number-select-' + BX.util.getRandomString(10),
	          text: phoneNumber.VALUE,
	          onclick: function onclick() {
	            params.onSelect({
	              phoneNumber: phoneNumber.VALUE
	            });
	          }
	        });
	      });
	      this.numberSelectMenu = new main_popup.Menu('im-phone-call-view-number-select', params.bindElement, menuItems, {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 40,
	        angle: {
	          position: "top"
	        },
	        zIndex: baseZIndex + 200,
	        closeByEsc: true,
	        overlay: {
	          backgroundColor: 'white',
	          opacity: 0
	        },
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this26.numberSelectMenu.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            return _this26.numberSelectMenu = null;
	          }
	        }
	      });
	      this.numberSelectMenu.show();
	    }
	  }, {
	    key: "closeNumberSelectMenu",
	    value: function closeNumberSelectMenu() {
	      if (this.numberSelectMenu) {
	        this.numberSelectMenu.close();
	      }
	    }
	  }, {
	    key: "fold",
	    value: function fold() {
	      if (!this.canBeFolded()) {
	        return false;
	      }
	      if (this.callListId > 0 && this.callState === CallState.idle) {
	        this.foldCallView();
	      } else {
	        this.foldCall();
	      }
	    }
	  }, {
	    key: "unfold",
	    value: function unfold() {
	      if (!this.isDesktop() && this.isFolded()) {
	        main_core.Dom.remove(this.elements.main);
	        this.folded = false;
	        this.elements = this.unfoldedElements;
	        this.show();
	      }
	    }
	  }, {
	    key: "foldCall",
	    value: function foldCall() {
	      var _this27 = this;
	      if (this.isDesktop() || !this.popup) {
	        return;
	      }
	      var popupNode = this.popup.getPopupContainer();
	      var overlayNode = this.popup.overlay.element;
	      main_core.Dom.addClass(popupNode, 'im-phone-call-view-folding');
	      main_core.Dom.addClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
	      setTimeout(function () {
	        _this27.folded = true;
	        _this27.popup.close();
	        _this27.unfoldedElements = _this27.elements;
	        main_core.Dom.removeClass(popupNode, 'im-phone-call-view-folding');
	        main_core.Dom.removeClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
	        _this27.reinit();
	        _this27.enableDocumentScroll();
	      }, 300);
	    }
	  }, {
	    key: "foldCallView",
	    value: function foldCallView() {
	      var _this28 = this;
	      var popupNode = this.popup.getPopupContainer();
	      var overlayNode = this.popup.overlay.element;
	      main_core.Dom.addClass(popupNode, 'im-phone-call-view-folding');
	      main_core.Dom.addClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
	      setTimeout(function () {
	        _this28.close();
	        _this28.foldedCallView.fold({
	          callListId: _this28.callListId,
	          webformId: _this28.webformId,
	          webformSecCode: _this28.webformSecCode,
	          currentItemIndex: _this28.callListView.currentItemIndex,
	          currentItemStatusId: _this28.callListView.currentStatusId,
	          statusList: _this28.callListView.statuses,
	          entityType: _this28.callListView.entityType
	        }, true);
	      }, 300);
	    }
	  }, {
	    key: "bindSlaveDesktopEvents",
	    value: function bindSlaveDesktopEvents() {
	      var _this29 = this;
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setTitle, this.setTitle.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setStatus, this.setStatusText.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setUiState, this.setUiState.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setDeviceCall, this.setDeviceCall.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setCrmEntity, this.setCrmEntity.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.reloadCrmCard, this.reloadCrmCard.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setPortalCall, this.setPortalCall.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setPortalCallUserId, this.setPortalCallUserId.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setPortalCallQueueName, this.setPortalCallQueueName.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setPortalCallData, this.setPortalCallData.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setConfig, this.setConfig.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setCallId, this.setCallId.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setLineNumber, this.setLineNumber.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setCompanyPhoneNumber, this.setCompanyPhoneNumber.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setPhoneNumber, this.setPhoneNumber.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setTransfer, this.setTransfer.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.setCallState, this.setCallState.bind(this));
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.closeWindow, function () {
	        return window.close();
	      });
	      BX.bind(window, "beforeunload", function () {
	        BX.unbindAll(window, "beforeunload");
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onBeforeUnload, []);
	      });
	      BX.bind(window, "resize", main_core.Runtime.debounce(function () {
	        if (_this29.skipOnResize) {
	          _this29.skipOnResize = false;
	          return;
	        }
	        _this29.saveInitialSize(window.innerWidth, window.innerHeight);
	      }, 100));
	      BX.addCustomEvent("SidePanel.Slider:onOpen", function (event) {
	        if (!event.getSlider().isSelfContained()) {
	          event.denyAction();
	          window.open(event.slider.url);
	        }
	      });

	      /*BX.bind(window, "keydown", function(e)
	      {
	      	if(e.keyCode === 27)
	      	{
	      		DesktopApi.emit(desktopEvents.onBeforeUnload, []);
	      	}
	      }.bind(this));*/
	    }
	  }, {
	    key: "bindMasterDesktopEvents",
	    value: function bindMasterDesktopEvents() {
	      var _this30 = this;
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onHold, function () {
	        return _this30.callbacks.hold();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onUnHold, function () {
	        return _this30.callbacks.unhold();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onMute, function () {
	        return _this30.callbacks.mute();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onUnMute, function () {
	        return _this30.callbacks.unmute();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onMakeCall, function (phoneNumber) {
	        return _this30.callbacks.makeCall(phoneNumber);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onCallListMakeCall, function (e) {
	        return _this30.callbacks.callListMakeCall(e);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onAnswer, function () {
	        return _this30.callbacks.answer();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onSkip, function () {
	        return _this30.callbacks.skip();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onHangup, function () {
	        return _this30.callbacks.hangup();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onClose, function () {
	        return _this30.close();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onStartTransfer, function (e) {
	        return _this30.callbacks.transfer(e);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onCompleteTransfer, function () {
	        return _this30.callbacks.completeTransfer();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onCancelTransfer, function () {
	        return _this30.callbacks.cancelTransfer();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onSwitchDevice, function (e) {
	        return _this30.callbacks.switchDevice(e);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onBeforeUnload, function () {
	        _this30.desktop.window = null;
	        _this30.callbacks.hangup();
	        _this30.callbacks.close();
	      }); //slave window unload
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onQualityGraded, function (grade) {
	        return _this30.callbacks.qualityGraded(grade);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onDialpadButtonClicked, function (grade) {
	        return _this30.callbacks.dialpadButtonClicked(grade);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onCommentShown, function (commentShown) {
	        return _this30.commentShown = commentShown;
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onSaveComment, function (comment) {
	        _this30.comment = comment;
	        _this30.saveComment();
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe(desktopEvents.onSetAutoClose, function (autoClose) {
	        return _this30.autoClose = autoClose;
	      });
	    }
	  }, {
	    key: "unbindDesktopEvents",
	    value: function unbindDesktopEvents() {
	      for (var eventId in desktopEvents) {
	        if (desktopEvents.hasOwnProperty(eventId)) {
	          im_v2_lib_desktopApi.DesktopApi.unsubscribe(desktopEvents[eventId]);
	        }
	      }
	    }
	  }, {
	    key: "isDesktop",
	    value: function isDesktop() {
	      return this._isDesktop;
	    }
	  }, {
	    key: "isFolded",
	    value: function isFolded() {
	      return this.folded;
	    }
	  }, {
	    key: "canBeFolded",
	    value: function canBeFolded() {
	      return this.allowAutoClose && (this.callState === CallState.connected || this.callState === CallState.idle && this.callListId > 0);
	    }
	  }, {
	    key: "getFoldedHeight",
	    value: function getFoldedHeight() {
	      if (!this.folded) {
	        return 0;
	      }
	      if (!this.elements.main) {
	        return 0;
	      }
	      return this.elements.main.clientHeight + (this.elements.sections.status ? this.elements.sections.status.clientHeight : 0);
	    }
	  }, {
	    key: "isWebformSupported",
	    value: function isWebformSupported() {
	      return !this.isDesktop() || this.desktop.isFeatureSupported('iframe');
	    }
	  }, {
	    key: "isRestAppsSupported",
	    value: function isRestAppsSupported() {
	      return !this.isDesktop() || this.desktop.isFeatureSupported('iframe');
	    }
	  }, {
	    key: "setClosable",
	    value: function setClosable(closable) {
	      closable = closable === true;
	      this.closable = closable;
	      if (this.isDesktop()) ; else if (this.popup) {
	        this.popup.setClosingByEsc(closable);
	        //this.popup.setAutoHide(closable);
	      }
	    }
	  }, {
	    key: "isClosable",
	    value: function isClosable() {
	      return this.closable;
	    }
	  }, {
	    key: "adjust",
	    value: function adjust() {
	      if (this.popup) {
	        this.popup.adjustPosition();
	      }
	      if (this.isDesktop() && this.slave) {
	        if (this.currentLayout == layouts.simple) {
	          this.desktop.setResizable(false);
	        } else {
	          this.desktop.setResizable(true);
	          this.desktop.setMinSize(this.elements.sidebarContainer ? 900 : 550, 650);
	        }
	        this.desktop.center();
	      }
	    }
	  }, {
	    key: "resizeWindow",
	    value: function resizeWindow(width, height) {
	      if (!this.isDesktop() || !this.slave) {
	        return false;
	      }
	      this.skipOnResize = true;
	      this.desktop.resize(width, height);
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      var _this31 = this;
	      BX.onCustomEvent(window, 'CallCard::BeforeClose', []);
	      if (this.isFolded() && this.elements.main) {
	        main_core.Dom.addClass(this.elements.main, 'im-phone-call-panel-mini-closing');
	        setTimeout(function () {
	          main_core.Dom.remove(_this31.elements.main);
	          _this31.elements = _this31.getInitialElements();
	        }, 300);
	      }
	      if (this.popup) {
	        this.popup.close();
	      }
	      if (this.desktop.window) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.closeWindow, []);
	        //this.desktop.window.ExecuteCommand('close');
	        //this.desktop.window = null;
	      }

	      this.enableDocumentScroll();
	      this.callbacks.close();
	      BX.onCustomEvent(window, 'CallCard::AfterClose', []);
	    }
	  }, {
	    key: "disableAutoClose",
	    value: function disableAutoClose() {
	      this.allowAutoClose = false;
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onSetAutoClose, [this.allowAutoClose]);
	      }
	      this.renderButtons();

	      //Update callView close timer on every call disableAutoClose()
	      this.updateAutoCloseTimer();
	    }
	  }, {
	    key: "enableAutoClose",
	    value: function enableAutoClose() {
	      this.allowAutoClose = true;
	      if (this.isDesktop() && this.slave) {
	        im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.onSetAutoClose, [this.allowAutoClose]);
	      }
	      this.renderButtons();
	      if (this.autoCloseTimer) {
	        clearTimeout(this.autoCloseTimer);
	        this.autoCloseTimer = null;
	        this.autoCloseAfterTimeout();
	      }
	    }
	  }, {
	    key: "autoClose",
	    value: function autoClose() {
	      var _this32 = this;
	      if (this.allowAutoClose && !this.commentShown) {
	        this.close();
	      } else {
	        BX.onCustomEvent(window, 'CallCard::BeforeClose', []);
	        this.autoCloseTimer = setTimeout(function () {
	          return _this32.autoCloseAfterTimeout();
	        }, this.autoCloseTimeout);
	      }
	    }
	  }, {
	    key: "autoCloseAfterTimeout",
	    value: function autoCloseAfterTimeout() {
	      console.log('Auto close after timeout', this.commentShown, this.autoCloseTimer, BX.localStorage.get(lsKeys.currentCall));
	      if (this.commentShown) {
	        this._onAddCommentButtonClick();
	      }
	      if (!BX.localStorage.get(lsKeys.currentCall)) {
	        this.close();
	      }
	      this.autoCloseTimer = null;
	    }
	  }, {
	    key: "updateAutoCloseTimer",
	    value: function updateAutoCloseTimer() {
	      var _this33 = this;
	      if (this.autoCloseTimer) {
	        clearTimeout(this.autoCloseTimer);
	        this.autoCloseTimer = setTimeout(function () {
	          return _this33.autoCloseAfterTimeout();
	        }, this.autoCloseTimeout);
	      }
	    }
	  }, {
	    key: "disableDocumentScroll",
	    value: function disableDocumentScroll() {
	      var scrollWidth = window.innerWidth - document.documentElement.clientWidth;
	      document.body.style.setProperty('padding-right', scrollWidth + "px");
	      document.body.classList.add('im-phone-call-disable-scroll');
	      var imBar = document.getElementById('bx-im-bar');
	      if (imBar) {
	        imBar.style.setProperty('right', scrollWidth + "px");
	      }
	    }
	  }, {
	    key: "enableDocumentScroll",
	    value: function enableDocumentScroll() {
	      document.body.classList.remove('im-phone-call-disable-scroll');
	      document.body.style.removeProperty('padding-right');
	      var imBar = document.getElementById('bx-im-bar');
	      if (imBar) {
	        imBar.style.removeProperty('right');
	      }
	    }
	  }, {
	    key: "dispose",
	    value: function dispose() {
	      var _this34 = this;
	      window.removeEventListener('beforeunload', this._onBeforeUnloadHandler);
	      BX.removeCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);
	      this.unloadRestApps();
	      this.unloadForm();
	      if (this.isFolded() && this.elements.main) {
	        main_core.Dom.addClass(this.elements.main, 'im-phone-call-panel-mini-closing');
	        setTimeout(function () {
	          return main_core.Dom.remove(_this34.elements.main);
	        }, 300);
	      }
	      if (this.backgroundWorker) {
	        this.backgroundWorker.setCallCard(null);
	        this.backgroundWorker = null;
	      }
	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }
	      if (this.qualityPopup) {
	        this.qualityPopup.close();
	      }
	      if (this.keypad) {
	        this.keypad.close();
	      }
	      if (this.numberSelectMenu) {
	        this.closeNumberSelectMenu();
	      }
	      this.enableDocumentScroll();
	      if (this.isDesktop()) {
	        this.unbindDesktopEvents();
	        if (this.desktop.window) {
	          im_v2_lib_desktopApi.DesktopApi.emit(desktopEvents.closeWindow, []);
	          //this.desktop.window.ExecuteCommand('close');
	          this.desktop.window = null;
	        }
	        if (!this.slave) {
	          window.removeEventListener('beforeunload', babelHelpers.classPrivateFieldGet(this, _onWindowUnload)); //master window unload
	        }
	      } else {
	        window.removeEventListener('beforeunload', this._onBeforeUnloadHandler);
	      }
	      if (!BX.localStorage.get(lsKeys.callInited) && !BX.localStorage.get(lsKeys.externalCall)) {
	        BX.localStorage.remove(lsKeys.callView);
	      }
	    }
	  }, {
	    key: "canBeUnloaded",
	    value: function canBeUnloaded() {
	      if (this.backgroundWorker.isUsed()) {
	        return false;
	      }
	      return this.allowAutoClose && this.isFolded();
	    }
	  }, {
	    key: "isCallListMode",
	    value: function isCallListMode() {
	      return this.callListId > 0;
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        callId: this.callId,
	        folded: this.folded,
	        uiState: this._uiState,
	        phoneNumber: this.phoneNumber,
	        companyPhoneNumber: this.companyPhoneNumber,
	        direction: this.direction,
	        fromUserId: this.fromUserId,
	        toUserId: this.toUserId,
	        statusText: this.statusText,
	        crm: this.crm,
	        hasSipPhone: this.hasSipPhone,
	        deviceCall: this.deviceCall,
	        transfer: this.transfer,
	        crmEntityType: this.crmEntityType,
	        crmEntityId: this.crmEntityId,
	        crmActivityId: this.crmActivityId,
	        crmActivityEditUrl: this.crmActivityEditUrl,
	        callListId: this.callListId,
	        callListStatusId: this.callListStatusId,
	        callListItemIndex: this.callListItemIndex,
	        config: this.config ? this.config : '{}',
	        portalCall: this.portalCall ? 'true' : 'false',
	        portalCallData: this.portalCallData ? this.portalCallData : '{}',
	        portalCallUserId: this.portalCallUserId,
	        webformId: this.webformId,
	        webformSecCode: this.webformSecCode,
	        initialTimestamp: this.initialTimestamp,
	        crmData: this.crmData
	      };
	    }
	  }, {
	    key: "selectTransferTarget",
	    value: function selectTransferTarget(resultCallback) {
	      var _this35 = this;
	      resultCallback = main_core.Type.isFunction(resultCallback) ? resultCallback : BX.DoNothing;
	      main_core.Runtime.loadExtension('ui.entity-selector').then(function (exports) {
	        var config = _this35.backgroundWorker.isUsed() ? _this35.getDialogConfigForBackgroundApp(resultCallback) : _this35.getDefaultDialogConfig(resultCallback);
	        var Dialog = exports.Dialog;
	        var transferDialog = new Dialog(config);
	        transferDialog.show();
	      });
	    }
	  }, {
	    key: "getDialogConfigForBackgroundApp",
	    value: function getDialogConfigForBackgroundApp(resultCallback) {
	      var _this36 = this;
	      return {
	        targetNode: this.elements.buttons.transfer,
	        multiple: false,
	        cacheable: false,
	        hideOnSelect: false,
	        enableSearch: true,
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false,
	            selectFields: ['personalPhone', 'personalMobile', 'workPhone']
	          }
	        }, {
	          id: 'department'
	        }],
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            event.target.deselectAll();
	            var item = event.data.item;
	            if (item.getEntityId() === 'user') {
	              var customData = item.getCustomData();
	              if (customData.get('personalPhone') || customData.get('personalMobile') || customData.get('workPhone')) {
	                _this36.showTransferToUserMenu({
	                  userId: item.getId(),
	                  customData: Object.fromEntries(customData),
	                  darkMode: _this36.darkMode,
	                  onSelect: function onSelect(result) {
	                    event.target.hide();
	                    resultCallback({
	                      phoneNumber: _this36.phoneNumber,
	                      target: result.target
	                    });
	                  }
	                });
	              } else {
	                event.target.hide();
	                resultCallback({
	                  phoneNumber: _this36.phoneNumber,
	                  target: item.getId()
	                });
	              }
	            }
	          }
	        }
	      };
	    }
	  }, {
	    key: "getDefaultDialogConfig",
	    value: function getDefaultDialogConfig(resultCallback) {
	      var _this37 = this;
	      return {
	        targetNode: this.elements.buttons.transfer,
	        multiple: false,
	        cacheable: false,
	        hideOnSelect: false,
	        enableSearch: true,
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false,
	            selectFields: ['personalPhone', 'personalMobile', 'workPhone']
	          }
	        }, {
	          id: 'department'
	        }, {
	          id: 'voximplant_group'
	        }],
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            event.target.deselectAll();
	            var item = event.data.item;
	            if (item.getEntityId() === 'user') {
	              var customData = item.getCustomData();
	              if (customData.get('personalPhone') || customData.get('personalMobile') || customData.get('workPhone')) {
	                _this37.showTransferToUserMenu({
	                  userId: item.getId(),
	                  customData: Object.fromEntries(customData),
	                  darkMode: _this37.darkMode,
	                  onSelect: function onSelect(result) {
	                    event.target.hide();
	                    resultCallback(result);
	                  }
	                });
	              } else {
	                event.target.hide();
	                resultCallback({
	                  type: 'user',
	                  target: item.getId()
	                });
	              }
	            } else if (item.getEntityId() === 'voximplant_group') {
	              event.target.hide();
	              resultCallback({
	                type: 'queue',
	                target: item.getId()
	              });
	            }
	          }
	        }
	      };
	    }
	  }, {
	    key: "showTransferToUserMenu",
	    value: function showTransferToUserMenu() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var userId = main_core.Type.isInteger(options.userId) ? options.userId : 0;
	      var userCustomData = main_core.Type.isPlainObject(options.customData) ? options.customData : {};
	      var darkMode = options.darkMode === true;
	      var onSelect = main_core.Type.isFunction(options.onSelect) ? options.onSelect : nop;
	      var popup;
	      var onMenuItemClick = function onMenuItemClick(e) {
	        var type = e.currentTarget.dataset["type"];
	        var target = e.currentTarget.dataset["target"];
	        onSelect({
	          type: type,
	          target: target
	        });
	        popup.close();
	      };
	      var menuItems = [{
	        icon: 'bx-messenger-menu-call-voice',
	        text: main_core.Loc.getMessage('IM_PHONE_INNER_CALL'),
	        dataset: {
	          type: 'user',
	          target: userId
	        },
	        onclick: onMenuItemClick
	      }, {
	        delimiter: true
	      }];
	      if (userCustomData["personalMobile"]) {
	        menuItems.push({
	          html: renderTransferMenuItem(main_core.Loc.getMessage("IM_PHONE_PERSONAL_MOBILE"), main_core.Text.encode(userCustomData["personalMobile"])),
	          dataset: {
	            type: 'pstn',
	            target: userCustomData["personalMobile"]
	          },
	          onclick: onMenuItemClick
	        });
	      }
	      if (userCustomData["personalPhone"]) {
	        menuItems.push({
	          type: "call",
	          html: renderTransferMenuItem(main_core.Loc.getMessage("IM_PHONE_PERSONAL_PHONE"), main_core.Text.encode(userCustomData["personalPhone"])),
	          dataset: {
	            type: 'pstn',
	            target: userCustomData["personalPhone"]
	          },
	          onclick: onMenuItemClick
	        });
	      }
	      if (userCustomData["workPhone"]) {
	        menuItems.push({
	          html: renderTransferMenuItem(main_core.Loc.getMessage("IM_PHONE_WORK_PHONE"), main_core.Text.encode(userCustomData["workPhone"])),
	          dataset: {
	            type: 'pstn',
	            target: userCustomData["workPhone"]
	          },
	          onclick: onMenuItemClick
	        });
	      }
	      popup = new main_popup.Menu({
	        id: "bx-messenger-phone-transfer-menu",
	        bindElement: null,
	        targetContainer: document.body,
	        darkMode: darkMode,
	        lightShadow: true,
	        autoHide: true,
	        closeByEsc: true,
	        cacheable: false,
	        overlay: {
	          backgroundColor: '#FFFFFF',
	          opacity: 0
	        },
	        items: menuItems
	      });
	      popup.show();
	    }
	  }]);
	  return PhoneCallView;
	}();
	function renderTransferMenuItem(surTitle, text) {
	  return "<div class=\"transfer-menu-item-surtitle\">".concat(main_core.Text.encode(surTitle), "</div><div class=\"transfer-menu-item-text\">").concat(main_core.Text.encode(text), "</div>");
	}
	function renderSimpleButton(text, className, clickCallback) {
	  var params = {};
	  if (main_core.Type.isStringFilled(text)) {
	    params.text = text;
	  }
	  if (main_core.Type.isStringFilled(className)) {
	    params.props = {
	      className: className
	    };
	  }
	  if (main_core.Type.isFunction(clickCallback)) {
	    params.events = {
	      click: clickCallback
	    };
	  }
	  return main_core.Dom.create('span', params);
	}
	function createStar(grade, active, onSelect) {
	  return main_core.Dom.create("div", {
	    props: {
	      className: 'im-phone-popup-rating-stars-item ' + (active ? 'im-phone-popup-rating-stars-item-active' : '')
	    },
	    dataset: {
	      grade: grade
	    },
	    events: {
	      click: function click(e) {
	        e.preventDefault();
	        var grade = e.currentTarget.dataset.grade;
	        onSelect(grade);
	      }
	    }
	  });
	}

	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var lsKeys$1 = {
	  callInited: 'viInitedCall',
	  externalCall: 'viExternalCard',
	  vite: 'vite',
	  dialHistory: 'vox-dial-history',
	  foldedView: 'vox-folded-call-card',
	  callView: 'bx-vox-call-view',
	  currentCall: 'bx-vox-current-call'
	};
	var Events$1 = {
	  onCallCreated: 'onCallCreated',
	  onCallConnected: 'onCallConnected',
	  onCallDestroyed: 'onCallDestroyed',
	  onDeviceCallStarted: 'onDeviceCallStarted'
	};
	var DeviceType = {
	  Webrtc: 'WEBRTC',
	  Phone: 'PHONE'
	};
	var _setCallEventListeners = /*#__PURE__*/new WeakSet();
	var _removeCallEventListeners = /*#__PURE__*/new WeakSet();
	var _onPullEvent = /*#__PURE__*/new WeakSet();
	var _onPullInvite = /*#__PURE__*/new WeakSet();
	var _onPullAnswerSelf = /*#__PURE__*/new WeakSet();
	var _onPullTimeout = /*#__PURE__*/new WeakSet();
	var _onPullOutgoing = /*#__PURE__*/new WeakSet();
	var _onPullStart = /*#__PURE__*/new WeakSet();
	var _onPullHold = /*#__PURE__*/new WeakSet();
	var _onPullUnhold = /*#__PURE__*/new WeakSet();
	var _onPullUpdateCrm = /*#__PURE__*/new WeakSet();
	var _onPullUpdatePortalUser = /*#__PURE__*/new WeakSet();
	var _onPullCompleteTransfer = /*#__PURE__*/new WeakSet();
	var _onPullPhoneDeviceActive = /*#__PURE__*/new WeakSet();
	var _onPullChangeDefaultLineId = /*#__PURE__*/new WeakSet();
	var _onPullReplaceCallerId = /*#__PURE__*/new WeakSet();
	var _onPullShowExternalCall = /*#__PURE__*/new WeakSet();
	var _onPullHideExternalCall = /*#__PURE__*/new WeakSet();
	var _onIncomingCall = /*#__PURE__*/new WeakSet();
	var _startCall = /*#__PURE__*/new WeakSet();
	var _onCallFailed = /*#__PURE__*/new WeakSet();
	var _onCallDisconnected = /*#__PURE__*/new WeakSet();
	var _onProgressToneStart = /*#__PURE__*/new WeakSet();
	var _onProgressToneStop = /*#__PURE__*/new WeakSet();
	var _onConnectionEstablished = /*#__PURE__*/new WeakSet();
	var _onConnectionFailed = /*#__PURE__*/new WeakSet();
	var _onConnectionClosed = /*#__PURE__*/new WeakSet();
	var _onMicResult = /*#__PURE__*/new WeakSet();
	var _onNetStatsReceived = /*#__PURE__*/new WeakSet();
	var _doOpenKeyPad = /*#__PURE__*/new WeakSet();
	var _doPhoneCall = /*#__PURE__*/new WeakSet();
	var _doCallListMakeCall = /*#__PURE__*/new WeakSet();
	var _phoneOnSDKReady = /*#__PURE__*/new WeakSet();
	var _onCallConnected = /*#__PURE__*/new WeakSet();
	var _bindPhoneViewCallbacks = /*#__PURE__*/new WeakSet();
	var _onCallViewMute = /*#__PURE__*/new WeakSet();
	var _onCallViewUnmute = /*#__PURE__*/new WeakSet();
	var _onCallViewHold = /*#__PURE__*/new WeakSet();
	var _onCallViewUnhold = /*#__PURE__*/new WeakSet();
	var _onCallViewAnswer = /*#__PURE__*/new WeakSet();
	var _onCallViewSkip = /*#__PURE__*/new WeakSet();
	var _onCallViewHangup = /*#__PURE__*/new WeakSet();
	var _onCallViewTransfer = /*#__PURE__*/new WeakSet();
	var _onCallViewCancelTransfer = /*#__PURE__*/new WeakSet();
	var _onCallViewCompleteTransfer = /*#__PURE__*/new WeakSet();
	var _onCallViewCallListMakeCall = /*#__PURE__*/new WeakSet();
	var _onCallViewClose = /*#__PURE__*/new WeakSet();
	var _onCallViewSwitchDevice = /*#__PURE__*/new WeakSet();
	var _onCallViewQualityGraded = /*#__PURE__*/new WeakSet();
	var _onCallViewDialpadButtonClicked = /*#__PURE__*/new WeakSet();
	var _onCallViewSaveComment = /*#__PURE__*/new WeakSet();
	var PhoneCallsController = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PhoneCallsController, _EventEmitter);
	  /** @see DeviceType */

	  function PhoneCallsController(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, PhoneCallsController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PhoneCallsController).call(this));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewSaveComment);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewDialpadButtonClicked);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewQualityGraded);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewSwitchDevice);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewClose);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewCallListMakeCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewCompleteTransfer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewCancelTransfer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewTransfer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewHangup);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewSkip);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewAnswer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewUnhold);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewHold);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewUnmute);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallViewMute);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _bindPhoneViewCallbacks);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallConnected);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _phoneOnSDKReady);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _doCallListMakeCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _doPhoneCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _doOpenKeyPad);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onNetStatsReceived);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onMicResult);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onConnectionClosed);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onConnectionFailed);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onConnectionEstablished);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onProgressToneStop);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onProgressToneStart);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallDisconnected);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onCallFailed);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _startCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onIncomingCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullHideExternalCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullShowExternalCall);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullReplaceCallerId);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullChangeDefaultLineId);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullPhoneDeviceActive);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullCompleteTransfer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullUpdatePortalUser);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullUpdateCrm);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullUnhold);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullHold);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullStart);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullOutgoing);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullTimeout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullAnswerSelf);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullInvite);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onPullEvent);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _removeCallEventListeners);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _setCallEventListeners);
	    _this.setEventNamespace('BX.Voximplant.PhoneCallsControllerOptions');
	    _this.subscribeFromOptions(options.events);
	    _this.debug = false;
	    _this.phoneEnabled = main_core.Type.isBoolean(options.phoneEnabled) ? options.phoneEnabled : false;
	    _this.userId = options.userId;
	    _this.userEmail = options.userEmail;
	    _this.isAdmin = options.isAdmin;
	    var history = BX.localStorage.get(lsKeys$1.dialHistory);
	    _this.dialHistory = main_core.Type.isArray(history) ? history : [];
	    _this.availableLines = main_core.Type.isArray(options.availableLines) ? options.availableLines : [];
	    _this.defaultLineId = main_core.Type.isString(options.defaultLineId) ? options.defaultLineId : '';
	    _this.callInterceptAllowed = options.canInterceptCall || false;
	    _this.restApps = options.restApps;
	    _this.hasSipPhone = options.deviceActive === true;
	    _this.skipIncomingCallTimer = null;
	    _this.hasActiveCallView = false;
	    _this.readDefaults();
	    if (main_core.Browser.isLocalStorageSupported()) {
	      BX.addCustomEvent(window, "onLocalStorageSet", _this.storageSet.bind(babelHelpers.assertThisInitialized(_this)));
	    }
	    BX.addCustomEvent("onPullEvent-voximplant", _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onPullEvent, _onPullEvent2).bind(babelHelpers.assertThisInitialized(_this)));

	    // call event handlers
	    _this.onCallConnectedHandler = _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onCallConnected, _onCallConnected2).bind(babelHelpers.assertThisInitialized(_this));
	    _this.onCallDisconnectedHandler = _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onCallDisconnected, _onCallDisconnected2).bind(babelHelpers.assertThisInitialized(_this));
	    _this.onCallFailedHandler = _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onCallFailed, _onCallFailed2).bind(babelHelpers.assertThisInitialized(_this));
	    _this.onProgressToneStartHandler = _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onProgressToneStart, _onProgressToneStart2).bind(babelHelpers.assertThisInitialized(_this));
	    _this.onProgressToneStopHandler = _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onProgressToneStop, _onProgressToneStop2).bind(babelHelpers.assertThisInitialized(_this));
	    _this.messengerFacade = options.messengerFacade;
	    _this.backgroundWorker = new BackgroundWorker();
	    _this.foldedCallView = new FoldedCallView({
	      events: babelHelpers.defineProperty({}, FoldedCallView.Events.onUnfold, function (event) {
	        var data = event.getData();
	        _this.startCallList(data.callListId, data.callListParams);
	      })
	    });
	    _this.restoreFoldedCallView();
	    BX.garbage(function () {
	      if (_this.hasActiveCall() && _this.callView && _this.callView.canBeUnloaded() && (_this.hasExternalCall || _this.deviceType === 'PHONE')) {
	        BX.localStorage.set(lsKeys$1.foldedView, {
	          callId: _this.callId,
	          phoneCrm: _this.phoneCrm,
	          deviceType: _this.deviceType,
	          hasExternalCall: _this.hasExternalCall,
	          callView: _this.callView.getState()
	        }, 15);
	      }
	    });
	    return _this;
	  }
	  babelHelpers.createClass(PhoneCallsController, [{
	    key: "hasActiveCall",
	    value: function hasActiveCall() {
	      return Boolean(this._currentCall || this.callView);
	    }
	  }, {
	    key: "ready",
	    value: function ready() {
	      return true; // TODO ??
	    }
	  }, {
	    key: "readDefaults",
	    value: function () {
	      var _readDefaults = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        var _this2 = this;
	        var deviceList, result;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (localStorage) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return");
	            case 2:
	              this.defaultMicrophone = localStorage.getItem('bx-im-settings-default-microphone');
	              this.defaultCamera = localStorage.getItem('bx-im-settings-default-camera');
	              this.defaultSpeaker = localStorage.getItem('bx-im-settings-default-speaker');
	              this.enableMicAutoParameters = localStorage.getItem('bx-im-settings-enable-mic-auto-parameters') !== 'N';
	              if (!(!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices || !this.defaultMicrophone)) {
	                _context.next = 8;
	                break;
	              }
	              return _context.abrupt("return");
	            case 8:
	              _context.next = 10;
	              return navigator.mediaDevices.enumerateDevices();
	            case 10:
	              deviceList = _context.sent;
	              result = deviceList.filter(function (device) {
	                return device.kind === 'audioinput' && device.deviceId === _this2.defaultMicrophone;
	              });
	              this.defaultMicrophone = result.length ? this.defaultMicrophone : null;
	            case 13:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function readDefaults() {
	        return _readDefaults.apply(this, arguments);
	      }
	      return readDefaults;
	    }()
	  }, {
	    key: "correctPhoneNumber",
	    value: function correctPhoneNumber(number) {
	      return number.toString().replace(/[^0-9+#*;,]/g, '');
	    }
	  }, {
	    key: "getCallParams",
	    value: function getCallParams() {
	      var result = main_core.Type.isPlainObject(this.phoneParams) ? main_core.Runtime.clone(this.phoneParams) : {};
	      if (this.phoneFullNumber != this.phoneNumber) {
	        result['FULL_NUMBER'] = this.phoneFullNumber;
	      }
	      return JSON.stringify(result);
	    }
	  }, {
	    key: "phoneCallFinish",
	    value: function phoneCallFinish() {
	      clearInterval(this.phoneConnectedInterval);
	      clearInterval(this.phoneCallTimeInterval);
	      BX.localStorage.remove(lsKeys$1.callInited);
	      this.callOverlayTimer('pause');
	      this.showCallViewBalloon();
	      if (this.currentCall) {
	        try {
	          this.currentCall.hangup({
	            "X-Disconnect-Code": 200,
	            "X-Disconnect-Reason": "Normal hangup"
	          });
	        } catch (e) {}
	        this.currentCall = null;
	        this.phoneLog('Call hangup call');
	      } else {
	        this.scheduleApiDisconnect();
	      }
	      if (this.keypad) {
	        this.keypad.close();
	      }
	      BX.localStorage.set(lsKeys$1.vite, false, 1);
	      this.phoneRinging = 0;
	      this.phoneIncoming = false;
	      this.callActive = false;
	      this.callId = '';
	      this.hasExternalCall = false;
	      this.deviceType = DeviceType.Webrtc;
	      //this.phonePortalCall = false;
	      this.phoneNumber = '';
	      this.phoneNumberUser = '';
	      this.phoneParams = {};
	      this.callOverlayOptions = {};
	      this.callSelfDisabled = false;
	      //this.phoneCrm = {};
	      this.isMuted = false;
	      this.isCallHold = false;
	      this.isCallTransfer = false;
	      this.phoneMicAccess = false;
	      this.phoneTransferTargetType = '';
	      this.phoneTransferTargetId = 0;
	      this.phoneTransferCallId = '';
	      this.phoneTransferEnabled = false;
	    }
	  }, {
	    key: "phoneOnAuthResult",
	    value: function phoneOnAuthResult() {
	      if (this.deviceType == DeviceType.Phone) {
	        return false;
	      }
	      if (this.phoneIncoming) {
	        BX.rest.callMethod('voximplant.call.sendReady', {
	          'CALL_ID': this.callId
	        });
	      } else if (this.callInitUserId == this.userId) {
	        _classPrivateMethodGet$1(this, _startCall, _startCall2).call(this);
	      }
	    }
	  }, {
	    key: "scheduleApiDisconnect",
	    value: function scheduleApiDisconnect() {
	      var _this3 = this;
	      if (this.voximplantClient && this.voximplantClient.connected()) {
	        setTimeout(function () {
	          if (_this3.voximplantClient && _this3.voximplantClient.connected()) {
	            _this3.voximplantClient.disconnect();
	          }
	        }, 500);
	      }
	    }
	  }, {
	    key: "holdCall",
	    value: function holdCall() {
	      this.toggleCallHold(true);
	    }
	  }, {
	    key: "unholdCall",
	    value: function unholdCall() {
	      this.toggleCallHold(false);
	    }
	  }, {
	    key: "toggleCallHold",
	    value: function toggleCallHold(state) {
	      if (!this.currentCall && this.deviceType == DeviceType.Webrtc) {
	        return false;
	      }
	      if (typeof state != 'undefined') {
	        this.isCallHold = !state;
	      }
	      if (this.isCallHold) {
	        if (this.deviceType === DeviceType.Webrtc) {
	          this.currentCall.sendMessage(JSON.stringify({
	            'COMMAND': 'unhold'
	          }));
	        } else {
	          BX.rest.callMethod('voximplant.call.unhold', {
	            'CALL_ID': this.callId
	          });
	        }
	      } else {
	        if (this.deviceType === DeviceType.Webrtc) {
	          this.currentCall.sendMessage(JSON.stringify({
	            'COMMAND': 'hold'
	          }));
	        } else {
	          BX.rest.callMethod('voximplant.call.hold', {
	            'CALL_ID': this.callId
	          });
	        }
	      }
	      this.isCallHold = !this.isCallHold;
	    }
	  }, {
	    key: "sendDTMF",
	    value: function sendDTMF(key) {
	      if (!this.currentCall) {
	        return false;
	      }
	      this.phoneLog('Send DTMF code', this.currentCall.id(), key);
	      this.currentCall.sendTone(key);
	    }
	  }, {
	    key: "startCallViaRestApp",
	    value: function startCallViaRestApp(number, lineId, params) {
	      BX.rest.callMethod('voximplant.call.startViaRest', {
	        'NUMBER': number,
	        'LINE_ID': lineId,
	        'PARAMS': params,
	        'SHOW': 'Y'
	      });
	    }
	  }, {
	    key: "phoneSupport",
	    value: function phoneSupport() {
	      return this.phoneEnabled && (this.hasSipPhone || this.ready());
	    }
	  }, {
	    key: "muteCall",
	    value: function muteCall() {
	      if (!this.currentCall) {
	        return false;
	      }
	      this.isMuted = true;
	      this.currentCall.muteMicrophone();
	    }
	  }, {
	    key: "unmuteCall",
	    value: function unmuteCall() {
	      if (!this.currentCall) {
	        return false;
	      }
	      this.isMuted = false;
	      this.currentCall.unmuteMicrophone();
	    }
	  }, {
	    key: "toggleCallAudio",
	    value: function toggleCallAudio() {
	      if (!this.currentCall) {
	        return false;
	      }
	      if (this.isMuted) {
	        this.currentCall.unmuteMicrophone();
	        this.callView.setMuted(false);
	      } else {
	        this.currentCall.muteMicrophone();
	      }
	      this.isMuted = !this.isMuted;
	    }
	  }, {
	    key: "phoneDeviceCall",
	    value: function phoneDeviceCall(status) {
	      var result = true;
	      if (typeof status == 'boolean') {
	        this.messengerFacade.setLocalConfig('viDeviceCallBlock', !status);
	        BX.localStorage.set('viDeviceCallBlock', !status, 86400);
	        if (this.callView) {
	          this.callView.setDeviceCall(status);
	        }
	      } else {
	        var deviceCallBlock = this.messengerFacade.getLocalConfig('viDeviceCallBlock');
	        if (!deviceCallBlock) {
	          deviceCallBlock = BX.localStorage.get('viDeviceCallBlock');
	        }
	        result = this.hasSipPhone && !deviceCallBlock;
	      }
	      return result;
	    }
	  }, {
	    key: "openKeyPad",
	    value: function openKeyPad() {
	      var _this4 = this;
	      var e = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      if (main_core.Loc.getMessage["voximplantCanMakeCalls"] == "N") {
	        main_core.Runtime.loadExtension("voximplant.common").then(function () {
	          return BX.Voximplant.openLimitSlider();
	        });
	        return;
	      }
	      this.loadPhoneLines().then(function () {
	        return _classPrivateMethodGet$1(_this4, _doOpenKeyPad, _doOpenKeyPad2).call(_this4, e);
	      });
	    }
	  }, {
	    key: "onKeyPadDial",
	    value: function onKeyPadDial(e) {
	      var params = {};
	      this.closeKeyPad();
	      if (e.lineId) {
	        params['LINE_ID'] = e.lineId;
	      }
	      this.phoneCall(e.phoneNumber, params);
	    }
	  }, {
	    key: "onKeyPadIntercept",
	    value: function onKeyPadIntercept(e) {
	      var _this5 = this;
	      if (!this.callInterceptAllowed) {
	        this.keypad.close();
	        if ('UI' in BX && 'InfoHelper' in BX.UI) {
	          BX.UI.InfoHelper.show('limit_contact_center_telephony_intercept');
	        }
	        return;
	      }
	      BX.rest.callMethod('voximplant.call.intercept').then(function (response) {
	        var data = response.data();
	        if (!data.FOUND || data.FOUND == 'Y') {
	          _this5.keypad.close();
	        } else {
	          if (data.ERROR) {
	            _this5.interceptErrorPopup = new main_popup.Popup({
	              id: 'intercept-call-error',
	              bindElement: e.interceptButton,
	              targetContainer: document.body,
	              content: main_core.Text.encode(data.ERROR),
	              autoHide: true,
	              closeByEsc: true,
	              cacheable: false,
	              bindOptions: {
	                position: 'bottom'
	              },
	              angle: {
	                offset: 40
	              },
	              events: {
	                onPopupClose: function onPopupClose(e) {
	                  return _this5.interceptErrorPopup = null;
	                }
	              }
	            });
	            _this5.interceptErrorPopup.show();
	          }
	        }
	      });
	    }
	  }, {
	    key: "onKeyPadClose",
	    value: function onKeyPadClose() {
	      this.keypad = null;
	    }
	  }, {
	    key: "closeKeyPad",
	    value: function closeKeyPad() {
	      if (this.keypad) {
	        this.keypad.close();
	      }
	    }
	  }, {
	    key: "phoneDisplayExternal",
	    value: function phoneDisplayExternal(params) {
	      var number = params.phoneNumber;
	      this.phoneLog(number, params);
	      this.phoneNumberUser = main_core.Text.encode(number);
	      number = this.correctPhoneNumber(number);
	      if (babelHelpers["typeof"](params) != 'object') {
	        params = {};
	      }
	      if (this.callActive) {
	        return;
	      }
	      if (this.callView) {
	        return;
	      }
	      this.initiator = true;
	      this.callInitUserId = this.userId;
	      this.callActive = false;
	      this.callUserId = 0;
	      this.phoneNumber = number;
	      this.callView = new PhoneCallView({
	        callId: params.callId,
	        config: params.config,
	        direction: Direction.outgoing,
	        phoneNumber: this.phoneNumber,
	        statusText: main_core.Loc.getMessage('IM_M_CALL_ST_CONNECT'),
	        hasSipPhone: true,
	        deviceCall: true,
	        portalCall: params.portalCall,
	        portalCallUserId: params.portalCallUserId,
	        portalCallData: params.portalCallData,
	        portalCallQueueName: params.portalCallQueueName,
	        crm: params.showCrmCard,
	        crmEntityType: params.crmEntityType,
	        crmEntityId: params.crmEntityId,
	        crmData: this.phoneCrm,
	        foldedCallView: this.foldedCallView,
	        backgroundWorker: this.backgroundWorker,
	        messengerFacade: this.messengerFacade,
	        restApps: this.restApps
	      });
	      _classPrivateMethodGet$1(this, _bindPhoneViewCallbacks, _bindPhoneViewCallbacks2).call(this, this.callView);
	      this.callView.setUiState(UiState.idle);
	      this.callView.setCallState(CallState.connected);
	      this.callView.show();
	    }
	  }, {
	    key: "loadPhoneLines",
	    value: function loadPhoneLines() {
	      var _this6 = this;
	      var cachedLines = BX.localStorage.get('bx-im-phone-lines');
	      if (cachedLines) {
	        this.phoneLines = cachedLines;
	        return Promise.resolve(cachedLines);
	      }
	      return new Promise(function (resolve, reject) {
	        if (_this6.phoneLines) {
	          return resolve(_this6.phoneLines);
	        }
	        BX.ajax.runAction("voximplant.callView.getLines").then(function (response) {
	          _this6.phoneLines = response.data;
	          BX.localStorage.set('bx-im-phone-lines', _this6.phoneLines, 86400);
	          {
	            resolve(_this6.phoneLines);
	          }
	        })["catch"](function (err) {
	          console.error(err);
	          reject(err);
	        });
	      });
	    }
	  }, {
	    key: "isRestLine",
	    value: function isRestLine(lineId) {
	      if (!this.phoneLines) {
	        throw new Error("Phone lines are not loaded. Call PhoneCallsController.loadPhoneLines prior to using this method");
	      }
	      if (this.phoneLines.hasOwnProperty(lineId)) {
	        return this.phoneLines[lineId].TYPE === 'REST';
	      } else {
	        return false;
	      }
	    }
	  }, {
	    key: "setPhoneNumber",
	    value: function setPhoneNumber(phoneNumber) {
	      var matches = /(\+?\d+)([;#]*)([\d,]*)/.exec(phoneNumber);
	      this.phoneFullNumber = phoneNumber;
	      if (matches) {
	        this.phoneNumber = matches[1];
	      }
	    }
	  }, {
	    key: "phoneCall",
	    value: function phoneCall(number, params) {
	      var _this7 = this;
	      this.loadPhoneLines().then(function () {
	        return _classPrivateMethodGet$1(_this7, _doPhoneCall, _doPhoneCall2).call(_this7, number, params);
	      });
	    }
	  }, {
	    key: "showUnsupported",
	    value: function showUnsupported() {
	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('IM_CALL_NO_WEBRT'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('IM_M_CALL_BTN_DOWNLOAD'),
	        cancelCaption: main_core.Loc.getMessage('IM_NOTIFY_CONFIRM_CLOSE'),
	        onOk: function onOk() {
	          var url = intranet_desktopDownload.DesktopDownload.getLinkForCurrentUser();
	          window.open(url, "desktopApp");
	          return true;
	        }
	      });
	      messageBox.show();
	    }
	  }, {
	    key: "addToHistory",
	    value: function addToHistory(phoneNumber) {
	      var oldHistory = this.dialHistory;
	      var phoneIndex = oldHistory.indexOf(phoneNumber);
	      if (phoneIndex === 0) ; else if (phoneIndex > 0) {
	        //moving number to the top
	        oldHistory.splice(phoneIndex, phoneIndex);
	        this.dialHistory = [phoneNumber].concat(oldHistory);
	      } else {
	        //adding as the top element of history
	        this.dialHistory = [phoneNumber].concat(oldHistory.slice(0, 4));
	      }
	      BX.localStorage.set(lsKeys$1.dialHistory, this.dialHistory, 31536000);
	      this.messengerFacade.setLocalConfig('phone-history', this.dialHistory);
	    }
	  }, {
	    key: "startCallList",
	    value: function startCallList(callListId, params) {
	      callListId = Number(callListId);
	      if (callListId === 0 || this.currentCall || this.callView || this.isCallListMode()) {
	        return false;
	      }
	      this.foldedCallView.destroy();
	      this.callListId = callListId;
	      this.callView = new PhoneCallView({
	        crm: true,
	        callListId: callListId,
	        callListStatusId: params.callListStatusId,
	        callListItemIndex: params.callListItemIndex,
	        direction: Direction.outgoing,
	        makeCall: params.makeCall === true,
	        uiState: UiState.outgoing,
	        webformId: params.webformId || 0,
	        webformSecCode: params.webformSecCode || '',
	        hasSipPhone: this.hasSipPhone,
	        deviceCall: this.phoneDeviceCall(),
	        crmData: this.phoneCrm,
	        foldedCallView: this.foldedCallView,
	        backgroundWorker: this.backgroundWorker,
	        messengerFacade: this.messengerFacade,
	        restApps: this.restApps
	      });
	      _classPrivateMethodGet$1(this, _bindPhoneViewCallbacks, _bindPhoneViewCallbacks2).call(this, this.callView);
	      this.callView.show();
	      return true;
	    }
	  }, {
	    key: "isCallListMode",
	    value: function isCallListMode() {
	      return this.callListId > 0;
	    }
	  }, {
	    key: "callListMakeCall",
	    value: function callListMakeCall(e) {
	      var _this8 = this;
	      this.loadPhoneLines().then(function () {
	        return _classPrivateMethodGet$1(_this8, _doCallListMakeCall, _doCallListMakeCall2).call(_this8, e);
	      });
	    }
	  }, {
	    key: "phoneIncomingAnswer",
	    value: function phoneIncomingAnswer() {
	      var _this9 = this;
	      this.clearSkipIncomingCallTimer();
	      this.messengerFacade.stopRepeatSound('ringtone');
	      this.callSelfDisabled = true;
	      BX.rest.callMethod('voximplant.call.answer', {
	        'CALL_ID': this.callId
	      });
	      if (this.keypad) {
	        this.keypad.close();
	      }
	      this.callView.setUiState(UiState.connectingIncoming);
	      this.callView.setCallState(CallState.connecting);
	      this.phoneApiInit().then(function () {
	        return BX.rest.callMethod('voximplant.call.sendReady', {
	          'CALL_ID': _this9.callId
	        });
	      });
	    }
	  }, {
	    key: "phoneApiInit",
	    value: function phoneApiInit() {
	      var _this10 = this;
	      if (!this.phoneSupport()) {
	        return Promise.reject('Telephony is not supported');
	      }
	      if (this.voximplantClient && this.voximplantClient.connected()) {
	        if (this.defaultMicrophone) {
	          this.voximplantClient.useAudioSource(this.defaultMicrophone);
	        }
	        if (this.defaultSpeaker) {
	          VoxImplant.Hardware.AudioDeviceManager.get().setDefaultAudioSettings({
	            outputId: this.defaultSpeaker
	          });
	        }
	        return Promise.resolve();
	      }
	      var phoneApiParameters = {
	        useRTCOnly: true,
	        micRequired: true,
	        videoSupport: false,
	        progressTone: false
	      };
	      if (this.enableMicAutoParameters === false) {
	        phoneApiParameters.audioConstraints = {
	          optional: [{
	            echoCancellation: false
	          }, {
	            googEchoCancellation: false
	          }, {
	            googEchoCancellation2: false
	          }, {
	            googDAEchoCancellation: false
	          }, {
	            googAutoGainControl: false
	          }, {
	            googAutoGainControl2: false
	          }, {
	            mozAutoGainControl: false
	          }, {
	            googNoiseSuppression: false
	          }, {
	            googNoiseSuppression2: false
	          }, {
	            googHighpassFilter: false
	          }, {
	            googTypingNoiseDetection: false
	          }, {
	            googAudioMirroring: false
	          }]
	        };
	      }
	      return new Promise(function (resolve, reject) {
	        BX.Voximplant.getClient({
	          debug: _this10.debug,
	          apiParameters: phoneApiParameters
	        }).then(function (client) {
	          _this10.voximplantClient = client;
	          if (_this10.defaultMicrophone) {
	            _this10.voximplantClient.useAudioSource(_this10.defaultMicrophone);
	          }
	          if (_this10.defaultSpeaker) {
	            VoxImplant.Hardware.AudioDeviceManager.get().setDefaultAudioSettings({
	              outputId: _this10.defaultSpeaker
	            });
	          }
	          if (_this10.messengerFacade.isDesktop() && main_core.Type.isFunction(_this10.voximplantClient.setLoggerCallback)) {
	            _this10.voximplantClient.enableSilentLogging();
	            _this10.voximplantClient.setLoggerCallback(function (e) {
	              return _this10.phoneLog(e.label + ": " + e.message);
	            });
	          }
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.ConnectionFailed, _classPrivateMethodGet$1(_this10, _onConnectionFailed, _onConnectionFailed2).bind(_this10));
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.ConnectionClosed, _classPrivateMethodGet$1(_this10, _onConnectionClosed, _onConnectionClosed2).bind(_this10));
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.IncomingCall, _classPrivateMethodGet$1(_this10, _onIncomingCall, _onIncomingCall2).bind(_this10));
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.MicAccessResult, _classPrivateMethodGet$1(_this10, _onMicResult, _onMicResult2).bind(_this10));
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.SourcesInfoUpdated, _this10.phoneOnInfoUpdated.bind(_this10));
	          _this10.voximplantClient.addEventListener(VoxImplant.Events.NetStatsReceived, _classPrivateMethodGet$1(_this10, _onNetStatsReceived, _onNetStatsReceived2).bind(_this10));
	          resolve();
	        })["catch"](function (e) {
	          BX.rest.callMethod('voximplant.call.onConnectionError', {
	            'CALL_ID': _this10.callId,
	            'ERROR': e
	          });
	          _this10.phoneCallFinish();
	          _this10.messengerFacade.playSound('error');
	          _this10.callOverlayProgress('offline');
	          _this10.callAbort(main_core.Loc.getMessage('IM_PHONE_ERROR'));
	          _this10.callView.setUiState(UiState.error);
	          _this10.callView.setCallState(CallState.idle);
	          reject('Could not connect to Voximplant cloud');
	        });
	      });
	    }
	  }, {
	    key: "phoneOnInfoUpdated",
	    value: function phoneOnInfoUpdated(e) {
	      this.phoneLog('Info updated', this.voximplantClient.audioSources(), this.voximplantClient.videoSources());
	    }
	  }, {
	    key: "displayIncomingCall",
	    value: function displayIncomingCall(params) {
	      var _this11 = this;
	      /*chatId, callId, callerId, lineNumber, companyPhoneNumber, isCallback*/
	      params.isCallback = !!params.isCallback;
	      this.phoneLog('incoming call', params);
	      if (!this.phoneSupport()) {
	        this.showUnsupported();
	        return false;
	      }
	      this.phoneNumberUser = main_core.Text.encode(params.callerId);
	      params.callerId = params.callerId.replace(/[^a-zA-Z0-9\.]/g, '');
	      if (this.callActive) {
	        return false;
	      }
	      this.initiator = true;
	      this.callInitUserId = 0;
	      this.callActive = false;
	      this.callUserId = 0;
	      this.phoneIncoming = true;
	      this.callId = params.callId;
	      this.phoneNumber = params.callerId;
	      this.phoneParams = {};
	      var direction = params.isCallback ? Direction.callback : Direction.incoming;
	      this.callView = new PhoneCallView({
	        userId: this.userId,
	        phoneNumber: this.phoneNumber,
	        lineNumber: params.lineNumber,
	        companyPhoneNumber: params.companyPhoneNumber,
	        callTitle: this.phoneNumberUser,
	        direction: direction,
	        transfer: this.isCallTransfer,
	        statusText: params.isCallback ? main_core.Loc.getMessage('IM_PHONE_INVITE_CALLBACK') : main_core.Loc.getMessage('IM_PHONE_INVITE'),
	        crm: params.showCrmCard,
	        crmEntityType: params.crmEntityType,
	        crmEntityId: params.crmEntityId,
	        crmActivityId: params.crmActivityId,
	        crmActivityEditUrl: params.crmActivityEditUrl,
	        callId: this.callId,
	        crmData: this.phoneCrm,
	        foldedCallView: this.foldedCallView,
	        backgroundWorker: this.backgroundWorker,
	        messengerFacade: this.messengerFacade,
	        restApps: this.restApps
	      });
	      _classPrivateMethodGet$1(this, _bindPhoneViewCallbacks, _bindPhoneViewCallbacks2).call(this, this.callView);
	      this.callView.setUiState(UiState.incoming);
	      this.callView.setCallState(CallState.connecting);
	      if (params.config) {
	        this.callView.setConfig(params.config);
	      }
	      this.callView.show();
	      if (params.portalCall) {
	        this.callView.setPortalCall(true);
	        this.callView.setPortalCallData(params.portalCallData);
	        this.callView.setPortalCallUserId(params.portalCallUserId);
	      }
	      this.hasActiveCallView = true;
	      this.skipIncomingCallTimer = setTimeout(function () {
	        console.log('Skip phone call by timer');
	        if (!_this11.currentCall) {
	          var _this11$callView;
	          (_this11$callView = _this11.callView) === null || _this11$callView === void 0 ? void 0 : _this11$callView._onSkipButtonClick();
	        }
	        _this11.skipIncomingCallTimer = null;
	      }, 40000);
	    }
	  }, {
	    key: "sendInviteTransfer",
	    value: function sendInviteTransfer() {
	      var _this12 = this;
	      if (!this.currentCall && this.deviceType == DeviceType.Webrtc) {
	        return false;
	      }
	      if (!this.phoneTransferTargetType || !this.phoneTransferTargetId) {
	        return false;
	      }
	      var transferParams = {
	        'CALL_ID': this.callId,
	        'TARGET_TYPE': this.phoneTransferTargetType,
	        'TARGET_ID': this.phoneTransferTargetId
	      };
	      BX.rest.callMethod('voximplant.call.startTransfer', transferParams).then(function (response) {
	        var data = response.data();
	        if (data.SUCCESS == 'Y') {
	          _this12.phoneTransferEnabled = true;
	          BX.localStorage.set(lsKeys$1.vite, true, 1);
	          _this12.phoneTransferCallId = data.DATA.CALL.CALL_ID;
	          _this12.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_TRANSFER'));
	          _this12.callView.setUiState(UiState.transferring);
	        } else {
	          console.error("Could not start call transfer. Error: ", data.ERRORS);
	        }
	      });
	    }
	  }, {
	    key: "cancelInviteTransfer",
	    value: function cancelInviteTransfer() {
	      if (!this.currentCall && this.deviceType == DeviceType.Webrtc) {
	        return false;
	      }
	      this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_ONLINE'));
	      this.callView.setUiState(UiState.connected);
	      if (this.phoneTransferCallId !== '') {
	        BX.rest.callMethod('voximplant.call.cancelTransfer', {
	          'CALL_ID': this.phoneTransferCallId
	        });
	      }
	      this.phoneTransferTargetId = 0;
	      this.phoneTransferTargetType = '';
	      this.phoneTransferCallId = '';
	      this.phoneTransferEnabled = false;
	      BX.localStorage.set(lsKeys$1.vite, false, 1);
	    }
	  }, {
	    key: "errorInviteTransfer",
	    value: function errorInviteTransfer(code, reason) {
	      if (code == '403' || code == '410' || code == '486') {
	        this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_TRANSFER_' + code));
	      } else {
	        this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_TRANSFER_1'));
	      }
	      this.messengerFacade.playSound('error', true);
	      this.callView.setUiState(UiState.transferFailed);
	      this.phoneTransferTargetId = 0;
	      this.phoneTransferTargetType = '';
	      this.phoneTransferCallId = '';
	      this.phoneTransferEnabled = false;
	      BX.localStorage.set(lsKeys$1.vite, false, 1);
	    }
	  }, {
	    key: "completeTransfer",
	    value: function completeTransfer() {
	      BX.rest.callMethod('voximplant.call.completeTransfer', {
	        'CALL_ID': this.phoneTransferCallId
	      });
	    }
	  }, {
	    key: "showExternalCall",
	    value: function showExternalCall(params) {
	      var _this13 = this;
	      var direction;
	      if (this.callView) {
	        return;
	      }
	      setTimeout(function () {
	        return BX.localStorage.set(lsKeys$1.externalCall, true, 5);
	      }, 100);
	      clearInterval(this.phoneConnectedInterval);
	      this.phoneConnectedInterval = setInterval(function () {
	        if (_this13.hasExternalCall) {
	          BX.localStorage.set(lsKeys$1.externalCall, true, 5);
	        }
	      }, 5000);
	      this.callId = params.callId;
	      this.callActive = true;
	      this.hasExternalCall = true;
	      if (params.isCallback) {
	        direction = Direction.callback;
	      } else if (params.fromUserId > 0) {
	        direction = Direction.outgoing;
	      } else {
	        direction = Direction.incoming;
	      }
	      this.callView = new PhoneCallView({
	        callId: params.callId,
	        direction: direction,
	        phoneNumber: params.phoneNumber,
	        lineNumber: params.lineNumber,
	        companyPhoneNumber: params.companyPhoneNumber,
	        fromUserId: params.fromUserId,
	        toUserId: params.toUserId,
	        crm: params.showCrmCard,
	        crmEntityType: params.crmEntityType,
	        crmEntityId: params.crmEntityId,
	        crmBindings: params.crmBindings,
	        crmActivityId: params.crmActivityId,
	        crmActivityEditUrl: params.crmActivityEditUrl,
	        crmData: this.phoneCrm,
	        isExternalCall: true,
	        foldedCallView: this.foldedCallView,
	        backgroundWorker: this.backgroundWorker,
	        messengerFacade: this.messengerFacade,
	        restApps: this.restApps
	      });
	      this.bindPhoneViewCallbacksExternalCall(this.callView);
	      this.callView.setUiState(UiState.externalCard);
	      this.callView.setCallState(CallState.connected);
	      this.callView.setConfig(params.config);
	      this.callView.show();
	      if (params.portalCall) {
	        this.callView.setPortalCall(true);
	        this.callView.setPortalCallData(params.portalCallData);
	        this.callView.setPortalCallUserId(params.portalCallUserId);
	      }
	    }
	  }, {
	    key: "bindPhoneViewCallbacksExternalCall",
	    value: function bindPhoneViewCallbacksExternalCall(callView) {
	      var _this14 = this;
	      callView.setCallback('close', function () {
	        if (_this14.callView) {
	          _this14.callView.dispose();
	          _this14.closeCallViewBalloon();
	          _this14.callView = null;
	        }
	        _this14.hasActiveCallView = false;
	        _this14.callId = '';
	        _this14.callActive = false;
	        _this14.hasExternalCall = false;
	        _this14.callSelfDisabled = false;
	        clearInterval(_this14.phoneConnectedInterval);
	        BX.localStorage.set(lsKeys$1.externalCall, false);
	      });
	      callView.setCallback('saveComment', _classPrivateMethodGet$1(this, _onCallViewSaveComment, _onCallViewSaveComment2).bind(this));
	    }
	  }, {
	    key: "hideExternalCall",
	    value: function hideExternalCall(clearFlag) {
	      if (this.callView && !this.callView.isCallListMode()) {
	        this.callView.autoClose();
	      }
	    }
	  }, {
	    key: "phoneLog",
	    value: function phoneLog() {
	      if (this.messengerFacade.isDesktop()) {
	        var text = '';
	        for (var i = 0; i < arguments.length; i++) {
	          if (BX.type.isPlainObject(arguments[i])) {
	            try {
	              text = text + ' | ' + JSON.stringify(arguments[i]);
	            } catch (e) {
	              text = text + ' | (circular structure)';
	            }
	          } else {
	            text = text + ' | ' + arguments[i];
	          }
	        }
	        im_v2_lib_desktopApi.DesktopApi.writeToLogFile('phone.' + this.userEmail + '.log', text.substring(3));
	      }
	      if (this.debug) {
	        if (console) {
	          try {
	            console.log('Phone Log', JSON.stringify(arguments));
	          } catch (e) {
	            console.log('Phone Log', arguments[0]);
	          }
	        }
	      }
	    }
	    /**
	     * Returns promise which will be resolved if
	     *  - either Bitrix Desktop is found and this code is running inside it
	     *  - or no Bitrix Desktop found
	     * @returns {Promise}
	     */
	  }, {
	    key: "checkDesktop",
	    value: function checkDesktop() {
	      if (main_core.Reflection.getClass('BX.Messenger.v2.Lib.DesktopManager')) {
	        return new Promise(function (resolve) {
	          var desktop = BX.Messenger.v2.Lib.DesktopManager.getInstance();
	          desktop.checkStatusInDifferentContext().then(function (result) {
	            if (result === false) {
	              resolve();
	            }
	          });
	        });
	      }
	      if (main_core.Reflection.getClass('BX.desktopUtils')) {
	        return new Promise(function (resolve) {
	          BX.desktopUtils.runningCheck(function () {}, function () {
	            return resolve();
	          });
	        });
	      }
	      return Promise.resolve();
	    }
	  }, {
	    key: "restoreFoldedCallView",
	    value: function restoreFoldedCallView() {
	      var _this15 = this;
	      var callProperties = BX.localStorage.get(lsKeys$1.foldedView);
	      if (!main_core.Type.isPlainObject(callProperties)) {
	        return;
	      }
	      this.callActive = true;
	      this.callId = callProperties.callId;
	      this.phoneCrm = callProperties.phoneCrm;
	      this.deviceType = callProperties.phoneCallDevice;
	      this.hasExternalCall = callProperties.hasExternalCall;
	      var callViewProperties = callProperties.callView;
	      callViewProperties.foldedCallView = this.foldedCallView;
	      callViewProperties.backgroundWorker = this.backgroundWorker;
	      callViewProperties.messengerFacade = this.messengerFacade;
	      this.callView = new PhoneCallView(callProperties.callView);
	      if (this.hasExternalCall) {
	        this.callView.setUiState(UiState.externalCard);
	        this.callView.setCallState(CallState.connected);
	        this.bindPhoneViewCallbacksExternalCall(this.callView);
	      } else {
	        _classPrivateMethodGet$1(this, _bindPhoneViewCallbacks, _bindPhoneViewCallbacks2).call(this, this.callView);
	      }
	      if (this.hasExternalCall) {
	        BX.localStorage.set(lsKeys$1.externalCall, true, 5);
	        this.phoneConnectedInterval = setInterval(function () {
	          if (_this15.hasExternalCall) {
	            BX.localStorage.set(lsKeys$1.externalCall, true, 5);
	          }
	        }, 5000);
	      }
	      BX.rest.callMethod('voximplant.call.get', {
	        'CALL_ID': this.callId
	      })["catch"](function () {
	        // call is not found

	        _this15.callId = '';
	        _this15.callActive = false;
	        _this15.hasExternalCall = false;
	        _this15.callSelfDisabled = false;
	        clearInterval(_this15.phoneConnectedInterval);
	        BX.localStorage.set(lsKeys$1.externalCall, false);
	        if (_this15.callView) {
	          _this15.callView.dispose();
	          _this15.closeCallViewBalloon();
	          _this15.callView = null;
	        }
	        _this15.hasActiveCallView = false;
	      });
	    }
	  }, {
	    key: "displayCallQuality",
	    value: function displayCallQuality(percent) {
	      if (!this.currentCall || this.currentCall.state() != "CONNECTED") {
	        return false;
	      }
	      var grade = 5;
	      if (100 == percent) {
	        grade = 5;
	      } else if (percent >= 99) {
	        grade = 4;
	      } else if (percent >= 97) {
	        grade = 3;
	      } else if (percent >= 95) {
	        grade = 2;
	      } else {
	        grade = 1;
	      }
	      this.callView.setQuality(grade);
	      return grade;
	    }
	  }, {
	    key: "callOverlayProgress",
	    value: function callOverlayProgress(progress) {
	      if (this.callView) {
	        this.callView.setProgress(progress);
	        if (progress === 'offline') {
	          this.messengerFacade.playSound('error');
	        }
	      }
	    }
	  }, {
	    key: "callOverlayStatus",
	    value: function callOverlayStatus(status) {
	      if (!main_core.Type.isStringFilled(status)) {
	        return false;
	      }
	      if (this.callView) {
	        this.callView.setStatusText(status);
	      }
	    }
	  }, {
	    key: "setCallOverlayTitle",
	    value: function setCallOverlayTitle(title) {
	      if (this.callView) {
	        this.callView.setTitle(title);
	      }
	    }
	  }, {
	    key: "callOverlayTimer",
	    value: function callOverlayTimer(state)
	    // TODO not ready yet
	    {
	      var _this16 = this;
	      state = typeof state == 'undefined' ? 'start' : state;
	      if (state == 'start') {
	        this.phoneCallTimeInterval = setInterval(function () {
	          return _this16.phoneCallTime++;
	        }, 1000);
	      } else {
	        clearInterval(this.phoneCallTimeInterval);
	      }
	    }
	  }, {
	    key: "callAbort",
	    value: function callAbort(reason) {
	      this.callOverlayDeleteEvents();
	      if (reason && this.callView) {
	        if (this.callView) {
	          this.callView.setStatusText(reason);
	        }
	      }
	    }
	  }, {
	    key: "callOverlayDeleteEvents",
	    value: function callOverlayDeleteEvents() {
	      // this.desktop.closeTopmostWindow();

	      this.phoneCallFinish();
	      this.clearSkipIncomingCallTimer();
	      this.messengerFacade.stopRepeatSound('ringtone');
	      this.messengerFacade.stopRepeatSound('dialtone');
	      clearTimeout(this.callDialogAllowTimeout);
	      if (this.callDialogAllow) {
	        this.callDialogAllow.close();
	      }
	    }
	  }, {
	    key: "storageSet",
	    value: function storageSet(params) {
	      if (params.key == lsKeys$1.vite) {
	        if (params.value === true || !this.callSelfDisabled) {
	          this.phoneTransferEnabled = params.value;
	        }
	      } else if (params.key == lsKeys$1.externalCall) {
	        if (params.value === false) {
	          this.hideExternalCall();
	        }
	      }
	    }
	  }, {
	    key: "getDebugInfo",
	    value: function getDebugInfo() {
	      var _this$callView, _this$callView2, _this$voximplantClien;
	      return {
	        vInitedCall: BX.localStorage.get('vInitedCall') ? 'Y' : 'N',
	        isDesktop: this.messengerFacade.isDesktop() ? 'Y' : 'N',
	        appVersion: navigator.appVersion,
	        hasActiveCall: this.messengerFacade.hasActiveCall() ? 'Y' : 'N',
	        isCallListMode: this.isCallListMode() ? this.callListId : 'N',
	        currentCall: this.currentCall ? this.currentCall.id() : 'N',
	        callView: this.callView ? this.callView.callId : 'N',
	        callViewPopup: (_this$callView = this.callView) !== null && _this$callView !== void 0 && _this$callView.popup ? 'Y' : 'N',
	        hasActiveCallView: this.hasActiveCallView ? 'Y' : 'N',
	        isFoldedCallView: (_this$callView2 = this.callView) !== null && _this$callView2 !== void 0 && _this$callView2.isFolded() ? 'Y' : 'N',
	        voximplantClient: this.voximplantClient ? (_this$voximplantClien = this.voximplantClient) === null || _this$voximplantClien === void 0 ? void 0 : _this$voximplantClien.connected() : 'N'
	      };
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(notificationText, actions) {
	      var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      if (!actions) {
	        actions = [];
	      }
	      var options = {
	        content: main_core.Text.encode(notificationText),
	        position: "top-right",
	        closeButton: true,
	        actions: actions
	      };
	      if (params.autoHideDelay) {
	        options.autoHideDelay = params.autoHideDelay;
	      } else {
	        options.autoHide = false;
	      }
	      return BX.UI.Notification.Center.notify(options);
	    }
	  }, {
	    key: "showCallViewBalloon",
	    value: function showCallViewBalloon() {
	      if (!this.openedCallViewBalloon && this.callView) {
	        this.openedCallViewBalloon = this.showNotification(main_core.Loc.getMessage('VOXIMPLANT_WARN_CLOSE_CALL_VIEW'));
	      }
	    }
	  }, {
	    key: "closeCallViewBalloon",
	    value: function closeCallViewBalloon() {
	      if (this.openedCallViewBalloon) {
	        this.openedCallViewBalloon.close();
	        this.openedCallViewBalloon = null;
	      }
	    }
	  }, {
	    key: "clearSkipIncomingCallTimer",
	    value: function clearSkipIncomingCallTimer() {
	      if (this.skipIncomingCallTimer) {
	        console.log('Clear skip incoming call timer: ' + this.skipIncomingCallTimer);
	        clearTimeout(this.skipIncomingCallTimer);
	        this.skipIncomingCallTimer = null;
	      }
	    }
	  }, {
	    key: "testSimple",
	    value: function testSimple() {
	      var _this17 = this;
	      var callId = 'test-call';
	      this.callView = new PhoneCallView({
	        callId: callId,
	        restApps: this.restApps,
	        foldedCallView: this.foldedCallView,
	        backgroundWorker: this.backgroundWorker,
	        messengerFacade: this.messengerFacade,
	        darkMode: this.messengerFacade.isThemeDark(),
	        events: {
	          close: function close() {
	            var _this17$callView;
	            console.trace('close');
	            (_this17$callView = _this17.callView) === null || _this17$callView === void 0 ? void 0 : _this17$callView.dispose();
	            _this17.callView = null;
	          },
	          hangup: function hangup() {
	            return _this17.callView.close();
	          },
	          transfer: function transfer(e) {
	            return console.log('transfer', e);
	          },
	          dialpadButtonClicked: function dialpadButtonClicked(e) {
	            return console.log('dialpadButtonClicked', e);
	          },
	          hold: function hold() {
	            return console.log('hold');
	          },
	          unhold: function unhold() {
	            return console.log('unhold');
	          },
	          mute: function mute() {
	            return console.log('mute');
	          },
	          unmute: function unmute() {
	            return console.log('unmute');
	          }
	        }
	      });
	      this.callView.show();
	    }
	  }, {
	    key: "testCrm",
	    value: function testCrm() {}
	  }, {
	    key: "testUser",
	    value: function testUser() {
	      this.callView = new PhoneCallView({
	        messengerFacade: this.messengerFacade
	      });
	    }
	  }, {
	    key: "currentCall",
	    get: function get() {
	      return this._currentCall;
	    },
	    set: function set(call) {
	      if (this._currentCall) {
	        _classPrivateMethodGet$1(this, _removeCallEventListeners, _removeCallEventListeners2).call(this, this._currentCall);
	        this.emit(Events$1.onCallDestroyed, {
	          call: this._currentCall
	        });
	      }
	      call !== null && call !== void 0 && call.id() ? BX.localStorage.set(lsKeys$1.currentCall, call === null || call === void 0 ? void 0 : call.id(), 86400) : BX.localStorage.remove(lsKeys$1.currentCall);
	      this._currentCall = call;
	      this.hasActiveCallView = Boolean(this._currentCall);
	      if (this._currentCall) {
	        _classPrivateMethodGet$1(this, _setCallEventListeners, _setCallEventListeners2).call(this, call);
	        this.emit(Events$1.onCallCreated, {
	          call: call
	        });
	      }
	    }
	  }]);
	  return PhoneCallsController;
	}(main_core_events.EventEmitter);
	function _setCallEventListeners2(call) {
	  call.addEventListener(VoxImplant.CallEvents.Connected, this.onCallConnectedHandler);
	  call.addEventListener(VoxImplant.CallEvents.Disconnected, this.onCallDisconnectedHandler);
	  call.addEventListener(VoxImplant.CallEvents.Failed, this.onCallFailedHandler);
	  call.addEventListener(VoxImplant.CallEvents.ProgressToneStart, this.onProgressToneStartHandler);
	  call.addEventListener(VoxImplant.CallEvents.ProgressToneStop, this.onProgressToneStopHandler);
	}
	function _removeCallEventListeners2(call) {
	  call.removeEventListener(VoxImplant.CallEvents.Connected, this.onCallConnectedHandler);
	  call.removeEventListener(VoxImplant.CallEvents.Disconnected, this.onCallDisconnectedHandler);
	  call.removeEventListener(VoxImplant.CallEvents.Failed, this.onCallFailedHandler);
	  call.removeEventListener(VoxImplant.CallEvents.ProgressToneStart, this.onProgressToneStartHandler);
	  call.removeEventListener(VoxImplant.CallEvents.ProgressToneStop, this.onProgressToneStopHandler);
	}
	function _onPullEvent2(command, params) {
	  var handlers = {
	    'invite': _classPrivateMethodGet$1(this, _onPullInvite, _onPullInvite2),
	    'answer_self': _classPrivateMethodGet$1(this, _onPullAnswerSelf, _onPullAnswerSelf2),
	    'timeout': _classPrivateMethodGet$1(this, _onPullTimeout, _onPullTimeout2),
	    'outgoing': _classPrivateMethodGet$1(this, _onPullOutgoing, _onPullOutgoing2),
	    'start': _classPrivateMethodGet$1(this, _onPullStart, _onPullStart2),
	    'hold': _classPrivateMethodGet$1(this, _onPullHold, _onPullHold2),
	    'unhold': _classPrivateMethodGet$1(this, _onPullUnhold, _onPullUnhold2),
	    'update_crm': _classPrivateMethodGet$1(this, _onPullUpdateCrm, _onPullUpdateCrm2),
	    'updatePortalUser': _classPrivateMethodGet$1(this, _onPullUpdatePortalUser, _onPullUpdatePortalUser2),
	    'completeTransfer': _classPrivateMethodGet$1(this, _onPullCompleteTransfer, _onPullCompleteTransfer2),
	    'phoneDeviceActive': _classPrivateMethodGet$1(this, _onPullPhoneDeviceActive, _onPullPhoneDeviceActive2),
	    'changeDefaultLineId': _classPrivateMethodGet$1(this, _onPullChangeDefaultLineId, _onPullChangeDefaultLineId2),
	    'replaceCallerId': _classPrivateMethodGet$1(this, _onPullReplaceCallerId, _onPullReplaceCallerId2),
	    'showExternalCall': _classPrivateMethodGet$1(this, _onPullShowExternalCall, _onPullShowExternalCall2),
	    'hideExternalCall': _classPrivateMethodGet$1(this, _onPullHideExternalCall, _onPullHideExternalCall2)
	  };
	  if (handlers.hasOwnProperty(command)) {
	    handlers[command].apply(this, [params]);
	  }
	}
	function _onPullInvite2(params) {
	  var _this$callView3,
	    _this$callView4,
	    _this$callView5,
	    _this$callView6,
	    _this$callView7,
	    _this$callView8,
	    _this18 = this;
	  if (!this.phoneSupport()) {
	    return false;
	  }
	  var popupConditions = ((_this$callView3 = this.callView) === null || _this$callView3 === void 0 ? void 0 : _this$callView3.popup) && !((_this$callView4 = this.callView) !== null && _this$callView4 !== void 0 && _this$callView4.commentShown) && !((_this$callView5 = this.callView) !== null && _this$callView5 !== void 0 && _this$callView5.autoCloseTimer) && !this.hasActiveCallView;
	  if (this.callView && !((_this$callView6 = this.callView) !== null && _this$callView6 !== void 0 && _this$callView6.popup) && !this.currentCall || popupConditions && !this.currentCall || this.callView && (_this$callView7 = this.callView) !== null && _this$callView7 !== void 0 && _this$callView7.isFolded() && !this.currentCall || this.callView && !this.voximplantClient || this.callView && this.voximplantClient && !this.voximplantClient.connected()) {
	    console.log('Close a stuck call view');
	    _classPrivateMethodGet$1(this, _onCallViewClose, _onCallViewClose2).call(this);
	  }
	  if (BX.localStorage.get(lsKeys$1.callView) && (_this$callView8 = this.callView) !== null && _this$callView8 !== void 0 && _this$callView8.popup && !Boolean(this._currentCall) && !this.isCallListMode() && !this.messengerFacade.hasActiveCall()) {
	    this.showCallViewBalloon();
	  }
	  if (this.hasActiveCall() || this.isCallListMode() || this.messengerFacade.hasActiveCall()) {
	    BX.rest.callMethod('voximplant.call.busy', {
	      CALL_ID: params.callId,
	      DEBUG_INFO: this.getDebugInfo()
	    });
	    return false;
	  }
	  if (BX.localStorage.get(lsKeys$1.callInited) || BX.localStorage.get(lsKeys$1.externalCall)) {
	    return false;
	  }
	  this.checkDesktop().then(function () {
	    if (params.CRM && params.CRM.FOUND) {
	      _this18.phoneCrm = params.CRM;
	    } else {
	      _this18.phoneCrm = {};
	    }
	    _this18.phonePortalCall = !!params.portalCall;
	    if (_this18.phonePortalCall && params.portalCallData) {
	      var userData = params.portalCallData[params.portalCallUserId];
	      if (userData) {
	        params.callerId = userData.name;
	      }
	      params.phoneNumber = '';
	    }
	    _this18.phoneCallConfig = params.config ? params.config : {};
	    _this18.phoneCallTime = 0;
	    _this18.messengerFacade.repeatSound('ringtone', 5000, true);
	    BX.rest.callMethod('voximplant.call.sendWait', {
	      'CALL_ID': params.callId,
	      'DEBUG_INFO': _this18.getDebugInfo()
	    });
	    _this18.isCallTransfer = !!params.isTransfer;
	    _this18.displayIncomingCall({
	      chatId: params.chatId,
	      callId: params.callId,
	      callerId: params.callerId,
	      lineNumber: params.lineNumber,
	      companyPhoneNumber: params.phoneNumber,
	      isCallback: params.isCallback,
	      showCrmCard: params.showCrmCard,
	      crmEntityType: params.crmEntityType,
	      crmEntityId: params.crmEntityId,
	      crmActivityId: params.crmActivityId,
	      crmActivityEditUrl: params.crmActivityEditUrl,
	      portalCall: params.portalCall,
	      portalCallUserId: params.portalCallUserId,
	      portalCallData: params.portalCallData,
	      config: params.config
	    });
	  })["catch"](function () {});
	}
	function _onPullAnswerSelf2(params) {
	  this.clearSkipIncomingCallTimer();
	  if (this.callSelfDisabled || this.callId != params.callId) {
	    return false;
	  }
	  this.messengerFacade.stopRepeatSound('ringtone');
	  this.messengerFacade.stopRepeatSound('dialtone');
	  this.phoneCallFinish();
	  this.callAbort();
	  this.callView.close();
	  this.callId = params.callId;
	}
	function _onPullTimeout2(params) {
	  this.clearSkipIncomingCallTimer();
	  if (this.phoneTransferCallId === params.callId) {
	    return this.errorInviteTransfer(params.failedCode, params.failedReason);
	  } else if (this.callId != params.callId) {
	    return false;
	  }
	  clearInterval(this.phoneConnectedInterval);
	  BX.localStorage.remove(lsKeys$1.callInited);
	  var external = this.hasExternalCall;
	  this.messengerFacade.stopRepeatSound('ringtone');
	  this.messengerFacade.stopRepeatSound('dialtone');
	  this.phoneCallFinish();
	  this.callAbort();
	  if (!this.callView) {
	    return;
	  }
	  this.showCallViewBalloon();
	  this.callView.setCallState(CallState.idle, {
	    failedCode: params.failedCode
	  });
	  if (external && params.failedCode == 486) {
	    this.callView.setProgress(CallProgress.offline);
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_ERROR_BUSY_PHONE'));
	    this.callView.setUiState(UiState.sipPhoneError);
	  } else if (external && params.failedCode == 480) {
	    this.callView.setProgress(CallProgress.error);
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_ERROR_NA_PHONE'));
	    this.callView.setUiState(UiState.sipPhoneError);
	  } else {
	    if (this.isCallListMode()) {
	      this.callView.setStatusText('');
	      this.callView.setUiState(UiState.outgoing);
	    } else {
	      this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_END'));
	      this.callView.setUiState(UiState.idle);
	      this.callView.autoClose();
	    }
	  }
	}
	function _onPullOutgoing2(params) {
	  var _this19 = this;
	  if (this.phoneNumber && (this.phoneNumber === params.phoneNumber || params.phoneNumber.indexOf(this.phoneNumber) >= 0)) {
	    this.deviceType = params.callDevice == DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
	    this.phonePortalCall = !!params.portalCall;
	    this.phoneNumber = params.phoneNumber;
	    if (this.hasExternalCall && this.deviceType == DeviceType.Phone) {
	      this.callView.setProgress(CallProgress.connect);
	      this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_WAIT_ANSWER'));
	    }
	    this.phoneCallConfig = params.config ? params.config : {};
	    this.callId = params.callId;
	    this.phoneCallTime = 0;
	    this.phoneCrm = params.CRM;
	    if (this.callView && params.showCrmCard) {
	      this.callView.setCrmData(params.CRM);
	      this.callView.setCrmEntity({
	        type: params.crmEntityType,
	        id: params.crmEntityId,
	        activityId: params.crmActivityId,
	        activityEditUrl: params.crmActivityEditUrl,
	        bindings: params.crmBindings
	      });
	      this.callView.setConfig(params.config);
	      this.callView.setCallId(params.callId);
	      if (params.lineNumber) {
	        this.callView.setLineNumber(params.lineNumber);
	      }
	      if (params.lineName) {
	        this.callView.setCompanyPhoneNumber(params.lineName);
	      }
	      this.callView.reloadCrmCard();
	    }
	    if (this.callView && this.phonePortalCall) {
	      this.callView.setPortalCall(true);
	      this.callView.setPortalCallData(params.portalCallData);
	      this.callView.setPortalCallUserId(params.portalCallUserId);
	      this.callView.setPortalCallQueueName(params.portalCallQueueName);
	    }
	  } else if (!this.hasActiveCall() && params.callDevice === DeviceType.Phone) {
	    this.checkDesktop().then(function () {
	      _this19.deviceType = params.callDevice === DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
	      _this19.phonePortalCall = !!params.portalCall;
	      _this19.callId = params.callId;
	      _this19.phoneCallTime = 0;
	      _this19.phoneCallConfig = params.config ? params.config : {};
	      _this19.phoneCrm = params.CRM;
	      _this19.phoneDisplayExternal({
	        callId: params.callId,
	        config: params.config ? params.config : {},
	        phoneNumber: params.phoneNumber,
	        portalCall: params.portalCall,
	        portalCallUserId: params.portalCallUserId,
	        portalCallData: params.portalCallData,
	        portalCallQueueName: params.portalCallQueueName,
	        showCrmCard: params.showCrmCard,
	        crmEntityType: params.crmEntityType,
	        crmEntityId: params.crmEntityId
	      });
	    })["catch"](function () {});
	  }
	}
	function _onPullStart2(params) {
	  this.clearSkipIncomingCallTimer();
	  if (this.phoneTransferCallId === params.callId) {
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_TRANSFER_CONNECTED'));
	    return;
	  }
	  if (this.callId != params.callId) {
	    return;
	  }
	  this.callOverlayTimer('start');
	  this.messengerFacade.stopRepeatSound('ringtone');
	  if (this.callId == params.callId && this.deviceType == DeviceType.Phone && (this.deviceType == params.callDevice || this.phonePortalCall)) {
	    _classPrivateMethodGet$1(this, _onCallConnected, _onCallConnected2).call(this);
	  } else if (this.callId == params.callId && params.callDevice == DeviceType.Phone && this.phoneIncoming) {
	    this.deviceType = DeviceType.Phone;
	    if (this.callView) {
	      this.callView.setDeviceCall(true);
	    }
	    _classPrivateMethodGet$1(this, _onCallConnected, _onCallConnected2).call(this);
	  }
	  if (params.CRM) {
	    this.phoneCrm = params.CRM;
	  }
	  if (this.phoneNumber !== '') {
	    this.phoneNumberLast = this.phoneNumber;
	    this.messengerFacade.setLocalConfig('phone_last', this.phoneNumber);
	  }
	}
	function _onPullHold2(params) {
	  if (this.callId == params.callId) {
	    this.isCallHold = true;
	  }
	}
	function _onPullUnhold2(params) {
	  if (this.callId == params.callId) {
	    this.isCallHold = false;
	  }
	}
	function _onPullUpdateCrm2(params) {
	  if (this.callId == params.callId && params.CRM && params.CRM.FOUND) {
	    this.phoneCrm = params.CRM;
	    if (this.callView) {
	      this.callView.setCrmData(params.CRM);
	      if (params.showCrmCard) {
	        this.callView.setCrmEntity({
	          type: params.crmEntityType,
	          id: params.crmEntityId,
	          activityId: params.crmActivityId,
	          activityEditUrl: params.crmActivityEditUrl,
	          bindings: params.crmBindings
	        });
	        this.callView.reloadCrmCard();
	      }
	    }
	  }
	}
	function _onPullUpdatePortalUser2(params) {
	  if (this.callId == params.callId && this.callView) {
	    this.callView.setPortalCall(true);
	    this.callView.setPortalCallData(params.portalCallData);
	    this.callView.setPortalCallUserId(params.portalCallUserId);
	  }
	}
	function _onPullCompleteTransfer2(params) {
	  if (this.callId != params.callId) {
	    return false;
	  }
	  this.callId = params.newCallId;
	  this.phoneTransferTargetId = 0;
	  this.phoneTransferTargetType = '';
	  this.phoneTransferCallId = '';
	  this.phoneTransferEnabled = false;
	  BX.localStorage.set(lsKeys$1.vite, false, 1);
	  this.deviceType = params.callDevice == DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
	  if (this.deviceType == DeviceType.Phone) {
	    this.callView.setDeviceCall(true);
	  }
	  this.callView.setTransfer(false);
	  _classPrivateMethodGet$1(this, _onCallConnected, _onCallConnected2).call(this);
	}
	function _onPullPhoneDeviceActive2(params) {
	  this.hasSipPhone = params.active == 'Y';
	}
	function _onPullChangeDefaultLineId2(params) {
	  this.defaultLineId = params.defaultLineId;
	}
	function _onPullReplaceCallerId2(params) {
	  var callTitle = main_core.Loc.getMessage('IM_PHONE_CALL_TRANSFER').replace('#PHONE#', params.callerId);
	  this.setCallOverlayTitle(callTitle);
	  this.callView.setPhoneNumber(params.callerId);
	  if (params.CRM) {
	    this.phoneCrm = params.CRM;
	    this.callView.setCrmData(params.CRM);
	    if (params.showCrmCard) {
	      this.callView.setCrmEntity({
	        type: params.crmEntityType,
	        id: params.crmEntityId,
	        activityId: params.crmActivityId,
	        activityEditUrl: params.crmActivityEditUrl,
	        bindings: params.crmBindings
	      });
	      this.callView.reloadCrmCard();
	    }
	  }
	}
	function _onPullShowExternalCall2(params) {
	  var _this20 = this;
	  if (this.messengerFacade.hasActiveCall()) {
	    return false;
	  }
	  if (BX.localStorage.get(lsKeys$1.callInited) || BX.localStorage.get(lsKeys$1.externalCall)) {
	    return false;
	  }
	  this.checkDesktop().then(function () {
	    if (params.CRM && params.CRM.FOUND) {
	      _this20.phoneCrm = params.CRM;
	    } else {
	      _this20.phoneCrm = {};
	    }
	    _this20.showExternalCall({
	      callId: params.callId,
	      fromUserId: params.fromUserId,
	      toUserId: params.toUserId,
	      isCallback: params.isCallback,
	      phoneNumber: params.phoneNumber,
	      lineNumber: params.lineNumber,
	      companyPhoneNumber: params.companyPhoneNumber,
	      showCrmCard: params.showCrmCard,
	      crmEntityType: params.crmEntityType,
	      crmEntityId: params.crmEntityId,
	      crmBindings: params.crmBindings,
	      crmActivityId: params.crmActivityId,
	      crmActivityEditUrl: params.crmActivityEditUrl,
	      config: params.config,
	      portalCall: params.portalCall,
	      portalCallData: params.portalCallData,
	      portalCallUserId: params.portalCallUserId
	    });
	  })["catch"](function () {});
	}
	function _onPullHideExternalCall2(params) {
	  if (this.hasActiveCall() && this.hasExternalCall && this.callId == params.callId) {
	    this.hideExternalCall();
	  }
	}
	function _onIncomingCall2(params) {
	  // we can't use hasActiveCall here because the call view is open
	  if (this.currentCall) {
	    return false;
	  }
	  this.currentCall = params.call;
	  this.currentCall.answer();
	}
	function _startCall2() {
	  var _this21 = this;
	  this.phoneParams['CALLER_ID'] = '';
	  this.phoneParams['USER_ID'] = this.userId;
	  this.phoneLog('Call params: ', this.phoneNumber, this.phoneParams);
	  if (!this.voximplantClient.connected()) {
	    _classPrivateMethodGet$1(this, _phoneOnSDKReady, _phoneOnSDKReady2).call(this);
	    return false;
	  }
	  this.currentCall = this.voximplantClient.call(this.phoneNumber, false, this.getCallParams());
	  var initParams = {
	    'NUMBER': this.phoneNumber,
	    'NUMBER_USER': main_core.Text.decode(this.phoneNumberUser),
	    'IM_AJAX_CALL': 'Y'
	  };
	  BX.rest.callMethod('voximplant.call.init', initParams).then(function (response) {
	    var data = response.data();
	    if (!(data.HR_PHOTO.length === 0)) {
	      _this21.callOverlayUserId = data.DIALOG_ID;
	    } else {
	      _this21.callOverlayChatId = data.DIALOG_ID.substring(4);
	    }
	  });
	}
	function _onCallFailed2(e) {
	  var headers = e.headers || {};
	  this.phoneLog('Call failed', e.code, e.reason);
	  var reason = main_core.Loc.getMessage('IM_PHONE_END');
	  if (e.code == 603) {
	    reason = main_core.Loc.getMessage('IM_PHONE_DECLINE');
	  } else if (e.code == 380) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERR_SIP_LICENSE');
	  } else if (e.code == 436) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERR_NEED_RENT');
	  } else if (e.code == 438) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERR_BLOCK_RENT');
	  } else if (e.code == 400) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERR_LICENSE');
	  } else if (e.code == 401) {
	    reason = main_core.Loc.getMessage('IM_PHONE_401');
	  } else if (e.code == 480 || e.code == 503) {
	    if (this.phoneNumber == 911 || this.phoneNumber == 112) {
	      reason = main_core.Loc.getMessage('IM_PHONE_NO_EMERGENCY');
	    } else {
	      reason = main_core.Loc.getMessage('IM_PHONE_UNAVAILABLE');
	    }
	  } else if (e.code == 484 || e.code == 404) {
	    if (this.phoneNumber == 911 || this.phoneNumber == 112) {
	      reason = main_core.Loc.getMessage('IM_PHONE_NO_EMERGENCY');
	    } else {
	      reason = main_core.Loc.getMessage('IM_PHONE_INCOMPLETED');
	    }
	  } else if (e.code == 402) {
	    if (headers.hasOwnProperty('X-Reason') && headers['X-Reason'] === "SIP_PAYMENT_REQUIRED") {
	      reason = main_core.Loc.getMessage('IM_PHONE_ERR_SIP_LICENSE');
	    } else {
	      reason = main_core.Loc.getMessage('IM_PHONE_NO_MONEY') + (this.isAdmin ? ' ' + main_core.Loc.getMessage('IM_PHONE_PAY_URL_NEW') : '');
	    }
	  } else if (e.code == 486 && this.phoneRinging > 1) {
	    reason = main_core.Loc.getMessage('IM_M_CALL_ST_DECLINE');
	  } else if (e.code == 486) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERROR_BUSY');
	  } else if (e.code == 403) {
	    reason = main_core.Loc.getMessage('IM_PHONE_403');
	    this.phoneServer = '';
	    this.phoneLogin = '';
	    this.phoneCheckBalance = true;
	  } else if (e.code == 504) {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERROR_CONNECT');
	  } else {
	    reason = main_core.Loc.getMessage('IM_PHONE_ERROR');
	  }
	  if (e.code == 408 || e.code == 403) {
	    this.scheduleApiDisconnect();
	  }
	  this.callOverlayProgress('offline');
	  this.callAbort(reason);
	  this.callView.setUiState(UiState.error);
	  this.callView.setCallState(CallState.idle);
	}
	function _onCallDisconnected2(e) {
	  this.phoneLog('Call disconnected', this.currentCall ? this.currentCall.id() : '-', this.currentCall ? this.currentCall.state() : '-');
	  if (this.currentCall) {
	    this.phoneCallFinish();
	    this.callOverlayDeleteEvents();
	    this.callOverlayStatus(main_core.Loc.getMessage('IM_M_CALL_ST_END'));
	    this.messengerFacade.playSound('stop');
	    this.callView.setCallState(CallState.idle);
	    if (this.isCallListMode()) {
	      this.callView.setUiState(UiState.outgoing);
	    } else {
	      this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_END'));
	      this.callView.setUiState(UiState.idle);
	      this.callView.autoClose();
	    }
	  }
	  this.scheduleApiDisconnect();
	}
	function _onProgressToneStart2(e) {
	  if (!this.currentCall) {
	    return false;
	  }
	  this.phoneLog('Progress tone start', this.currentCall.id());
	  this.phoneRinging++;
	  this.callOverlayStatus(main_core.Loc.getMessage('IM_PHONE_WAIT_ANSWER'));
	}
	function _onProgressToneStop2(e) {
	  if (!this.currentCall) {
	    return false;
	  }
	  this.phoneLog('Progress tone stop', this.currentCall.id());
	}
	function _onConnectionFailed2(e) {
	  this.phoneLog('Connection failed');
	  this.phoneCallFinish();
	  this.callAbort(main_core.Loc.getMessage('IM_M_CALL_ERR'));
	}
	function _onConnectionClosed2(e) {
	  this.phoneLog('Connection closed');
	}
	function _onMicResult2(e) {
	  this.phoneMicAccess = e.result;
	  this.phoneLog('Mic Access Allowed', e.result);
	  if (e.result) {
	    this.callOverlayProgress('connect');
	    this.callOverlayStatus(main_core.Loc.getMessage('IM_M_CALL_ST_CONNECT'));
	  } else {
	    this.phoneCallFinish();
	    this.callOverlayProgress('offline');
	    this.callAbort(main_core.Loc.getMessage('IM_M_CALL_ST_NO_ACCESS'));
	    this.callView.setUiState(UiState.error);
	    this.callView.setCallState(CallState.idle);
	  }
	}
	function _onNetStatsReceived2(e) {
	  if (!this.currentCall || this.currentCall.state() != "CONNECTED") {
	    return false;
	  }
	  var percent = 100 - parseInt(e.stats.packetLoss);
	  var grade = this.displayCallQuality(percent);
	  this.currentCall.sendMessage(JSON.stringify({
	    'COMMAND': 'meter',
	    'PACKETLOSS': e.stats.packetLoss,
	    'PERCENT': percent,
	    'GRADE': grade
	  }));
	}
	function _doOpenKeyPad2(e) {
	  var _this22 = this;
	  if (!this.phoneSupport() && !this.isRestLine(this.defaultLineId)) {
	    this.showUnsupported();
	    return false;
	  }
	  if (this.hasActiveCall() || BX.localStorage.get(lsKeys$1.callInited) || BX.localStorage.get(lsKeys$1.externalCall)) {
	    return false;
	  }
	  if (this.keypad) {
	    this.keypad.close();
	    return false;
	  }
	  this.keypad = new Keypad({
	    bindElement: e.bindElement,
	    offsetTop: e.offsetTop,
	    offsetLeft: e.offsetLeft,
	    anglePosition: e.anglePosition,
	    angleOffset: e.angleOffset,
	    defaultLineId: this.defaultLineId,
	    lines: this.phoneLines,
	    availableLines: this.availableLines,
	    history: this.dialHistory,
	    callInterceptAllowed: this.callInterceptAllowed,
	    onDial: this.onKeyPadDial.bind(this),
	    onIntercept: this.onKeyPadIntercept.bind(this),
	    onClose: function onClose() {
	      _this22.onKeyPadClose();
	      if (main_core.Type.isFunction(e.onClose)) {
	        e.onClose();
	      }
	    }
	  });
	  this.keypad.show();
	}
	function _doPhoneCall2(number) {
	  var _this23 = this;
	  var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	  if (BX.localStorage.get(lsKeys$1.callInited) || this.callView || this.hasActiveCall()) {
	    return false;
	  }
	  if (!this.phoneSupport()) {
	    this.showUnsupported();
	    return false;
	  }
	  if (this.keypad) {
	    this.keypad.close();
	  }
	  if (main_core.Type.isStringFilled(number)) {
	    this.addToHistory(number);
	  }
	  var lineId = main_core.Type.isStringFilled(params['LINE_ID']) ? params['LINE_ID'] : this.defaultLineId;
	  if (this.isRestLine(lineId)) {
	    this.startCallViaRestApp(number, lineId, params);
	    return true;
	  }
	  this.phoneLog(number, params);
	  this.phoneNumberUser = main_core.Text.encode(number);
	  var numberOriginal = number;
	  if (babelHelpers["typeof"](params) != 'object') {
	    params = {};
	  }
	  var internationalNumber = this.correctPhoneNumber(number);
	  if (internationalNumber.length <= 0) {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('IM_PHONE_WRONG_NUMBER_DESC'), main_core.Loc.getMessage('IM_PHONE_WRONG_NUMBER'));
	    return false;
	  }
	  this.setPhoneNumber(internationalNumber);
	  this.initiator = true;
	  this.callInitUserId = this.userId;
	  this.callActive = false;
	  this.callUserId = 0;
	  this.hasExternalCall = this.phoneDeviceCall();
	  this.phoneParams = params;
	  this.callView = new PhoneCallView({
	    darkMode: this.messengerFacade.isThemeDark(),
	    phoneNumber: this.phoneFullNumber,
	    callTitle: this.phoneNumberUser,
	    fromUserId: this.userId,
	    direction: Direction.outgoing,
	    uiState: UiState.connectingOutgoing,
	    status: main_core.Loc.getMessage('IM_M_CALL_ST_CONNECT'),
	    hasSipPhone: this.hasSipPhone,
	    deviceCall: this.hasExternalCall,
	    crmData: this.phoneCrm,
	    autoFold: params['AUTO_FOLD'] === true,
	    foldedCallView: this.foldedCallView,
	    backgroundWorker: this.backgroundWorker,
	    messengerFacade: this.messengerFacade,
	    restApps: this.restApps
	  });
	  _classPrivateMethodGet$1(this, _bindPhoneViewCallbacks, _bindPhoneViewCallbacks2).call(this, this.callView);
	  this.callView.show();
	  this.messengerFacade.playSound("start");
	  if (this.hasExternalCall) {
	    this.deviceType = DeviceType.Phone;
	    this.callView.setProgress(CallProgress.wait);
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_PHONE_NOTICE'));
	    var callStartParams = {
	      'NUMBER': numberOriginal.toString().replace(/[^0-9+*#,;]/g, ''),
	      'PARAMS': params
	    };
	    BX.rest.callMethod('voximplant.call.startWithDevice', callStartParams).then(function (response) {
	      var data = response.data();
	      _this23.callId = data.CALL_ID;
	      _this23.hasExternalCall = data.EXTERNAL === true;
	      _this23.phoneCallConfig = data.CONFIG;
	      _this23.callView.setProgress(CallProgress.wait);
	      _this23.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_WAIT_PHONE'));
	      _this23.callView.setUiState(UiState.connectingOutgoing);
	      _this23.callView.setCallState(CallState.connecting);
	      _this23.emit(Events$1.onDeviceCallStarted, {
	        callId: data.CALL_ID,
	        config: data.CONFIG
	      });
	    })["catch"](function (err) {
	      _this23.callView.setProgress(CallProgress.error);
	      _this23.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_PHONE_ERROR'));
	      _this23.callView.setUiState(UiState.error);
	      _this23.callView.setCallState(CallState.idle);
	    });
	  } else {
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_CALL_INIT'));
	    this.phoneApiInit().then(function () {
	      return _classPrivateMethodGet$1(_this23, _phoneOnSDKReady, _phoneOnSDKReady2).call(_this23);
	    });
	  }
	}
	function _doCallListMakeCall2(e) {
	  var _this24 = this;
	  if (this.isRestLine(this.defaultLineId)) {
	    this.startCallViaRestApp(e.phoneNumber, this.defaultLineId, {
	      'ENTITY_TYPE': 'CRM_' + e.crmEntityType,
	      'ENTITY_ID': e.crmEntityId,
	      'CALL_LIST_ID': e.callListId
	    });
	    return true;
	  }
	  if (BX.localStorage.get(lsKeys$1.callInited)) {
	    return false;
	  }
	  if (this.callActive) {
	    return false;
	  }
	  if (!this.callView) {
	    return false;
	  }
	  this.lastCallListCallParams = e;
	  if (!this.phoneSupport()) {
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_CALL_NO_WEBRT'));
	    this.callView.setUiState(UiState.error);
	    this.callView.setCallState(CallState.idle);
	    return false;
	  }
	  var number = e.phoneNumber;
	  var numberOriginal = number;
	  var internationalNumber = this.correctPhoneNumber(number);
	  if (internationalNumber.length <= 0) {
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_PHONE_WRONG_NUMBER_DESC').replace("<br/>", "\n"));
	    return false;
	  }
	  this.initiator = true;
	  this.callInitUserId = this.userId;
	  this.callActive = false;
	  this.callUserId = 0;
	  this.hasExternalCall = this.phoneDeviceCall();
	  this.setPhoneNumber(internationalNumber);
	  this.phoneParams = {
	    'ENTITY_TYPE': 'CRM_' + e.crmEntityType,
	    'ENTITY_ID': e.crmEntityId,
	    'CALL_LIST_ID': e.callListId
	  };
	  this.messengerFacade.playSound("start");
	  if (this.hasExternalCall) {
	    this.deviceType = DeviceType.Phone;
	    this.callView.setProgress(CallProgress.wait);
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_PHONE_NOTICE'));
	    this.callView.setUiState(UiState.connectingOutgoing);
	    this.callView.setCallState(CallState.connecting);
	    var callStartParams = {
	      'NUMBER': numberOriginal.toString().replace(/[^0-9+*#,;]/g, ''),
	      'PARAMS': this.phoneParams
	    };
	    BX.rest.callMethod('voximplant.call.startWithDevice', callStartParams).then(function (response) {
	      var data = response.data();
	      _this24.callId = data.CALL_ID;

	      // TODO: is this necessary? It did not work previously
	      _this24.hasExternalCall = data.EXTERNAL === true;
	      _this24.phoneCallConfig = data.CONFIG;
	      _this24.callView.setProgress(CallProgress.wait);
	      _this24.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_WAIT_PHONE'));
	      _this24.callView.setUiState(UiState.connectingOutgoing);
	      _this24.callView.setCallState(CallState.connecting);
	      _this24.emit(Events$1.onDeviceCallStarted, {
	        callId: data.CALL_ID,
	        config: data.CONFIG
	      });
	    })["catch"](function (err) {
	      _this24.callView.setProgress(CallProgress.error);
	      _this24.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_PHONE_ERROR'));
	      _this24.callView.setUiState(UiState.error);
	      _this24.callView.setCallState(CallState.idle);
	    });
	  } else {
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_CALL_INIT'));
	    this.callView.setUiState(UiState.connectingOutgoing);
	    this.callView.setCallState(CallState.connecting);
	    this.phoneApiInit().then(function () {
	      return _classPrivateMethodGet$1(_this24, _phoneOnSDKReady, _phoneOnSDKReady2).call(_this24);
	    });
	  }
	}
	function _phoneOnSDKReady2(params) {
	  var _this25 = this;
	  this.phoneLog('SDK ready');
	  params = params || {};
	  params.delay = params.delay || false;
	  if (!params.delay && this.hasSipPhone) {
	    if (!this.phoneIncoming && !this.phoneDeviceCall()) {
	      this.callOverlayProgress('wait');
	      this.callDialogAllowTimeout = setTimeout(function () {
	        return _classPrivateMethodGet$1(_this25, _phoneOnSDKReady, _phoneOnSDKReady2).call(_this25, {
	          delay: true
	        });
	      }, 5000);
	      return false;
	    }
	  }
	  this.phoneLog('Connection exists');
	  this.callView.setProgress(CallProgress.connect);
	  this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_CONNECT'));
	  this.phoneOnAuthResult({
	    result: true
	  });
	  this.callView.setCallState(CallState.connecting);
	  if (this.phoneIncoming) {
	    this.callView.setUiState(UiState.connectingIncoming);
	  } else {
	    this.callView.setUiState(UiState.connectingOutgoing);
	  }
	}
	function _onCallConnected2(e) {
	  this.clearSkipIncomingCallTimer();
	  this.messengerFacade.stopRepeatSound('ringtone', 5000);
	  BX.localStorage.set(lsKeys$1.callInited, true, 7);
	  clearInterval(this.phoneConnectedInterval);
	  this.phoneConnectedInterval = setInterval(function () {
	    return BX.localStorage.set(lsKeys$1.callInited, true, 7);
	  }, 5000);

	  // this.desktop.closeTopmostWindow();

	  this.phoneLog('Call connected', e);
	  if (this.callView) {
	    BX.localStorage.set(lsKeys$1.callView, this.callView.callId, 86400);
	    this.callView.setUiState(UiState.connected);
	    this.callView.setCallState(CallState.connected);
	    this.callView.setProgress(CallProgress.online);
	    this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_ONLINE'));
	  }
	  this.callActive = true;
	  this.emit(Events$1.onCallConnected, {
	    call: this.currentCall,
	    isIncoming: this.phoneIncoming,
	    isDeviceCall: this.phoneDeviceCall()
	  });
	}
	function _bindPhoneViewCallbacks2(callView) {
	  if (!callView instanceof PhoneCallView) {
	    return false;
	  }
	  callView.setCallback('mute', _classPrivateMethodGet$1(this, _onCallViewMute, _onCallViewMute2).bind(this));
	  callView.setCallback('unmute', _classPrivateMethodGet$1(this, _onCallViewUnmute, _onCallViewUnmute2).bind(this));
	  callView.setCallback('hold', _classPrivateMethodGet$1(this, _onCallViewHold, _onCallViewHold2).bind(this));
	  callView.setCallback('unhold', _classPrivateMethodGet$1(this, _onCallViewUnhold, _onCallViewUnhold2).bind(this));
	  callView.setCallback('answer', _classPrivateMethodGet$1(this, _onCallViewAnswer, _onCallViewAnswer2).bind(this));
	  callView.setCallback('skip', _classPrivateMethodGet$1(this, _onCallViewSkip, _onCallViewSkip2).bind(this));
	  callView.setCallback('hangup', _classPrivateMethodGet$1(this, _onCallViewHangup, _onCallViewHangup2).bind(this));
	  callView.setCallback('transfer', _classPrivateMethodGet$1(this, _onCallViewTransfer, _onCallViewTransfer2).bind(this));
	  callView.setCallback('cancelTransfer', _classPrivateMethodGet$1(this, _onCallViewCancelTransfer, _onCallViewCancelTransfer2).bind(this));
	  callView.setCallback('completeTransfer', _classPrivateMethodGet$1(this, _onCallViewCompleteTransfer, _onCallViewCompleteTransfer2).bind(this));
	  callView.setCallback('callListMakeCall', _classPrivateMethodGet$1(this, _onCallViewCallListMakeCall, _onCallViewCallListMakeCall2).bind(this));
	  callView.setCallback('close', _classPrivateMethodGet$1(this, _onCallViewClose, _onCallViewClose2).bind(this));
	  callView.setCallback('switchDevice', _classPrivateMethodGet$1(this, _onCallViewSwitchDevice, _onCallViewSwitchDevice2).bind(this));
	  callView.setCallback('qualityGraded', _classPrivateMethodGet$1(this, _onCallViewQualityGraded, _onCallViewQualityGraded2).bind(this));
	  callView.setCallback('dialpadButtonClicked', _classPrivateMethodGet$1(this, _onCallViewDialpadButtonClicked, _onCallViewDialpadButtonClicked2).bind(this));
	  callView.setCallback('saveComment', _classPrivateMethodGet$1(this, _onCallViewSaveComment, _onCallViewSaveComment2).bind(this));
	}
	function _onCallViewMute2() {
	  this.muteCall();
	}
	function _onCallViewUnmute2() {
	  this.unmuteCall();
	}
	function _onCallViewHold2() {
	  this.holdCall();
	}
	function _onCallViewUnhold2() {
	  this.unholdCall();
	}
	function _onCallViewAnswer2() {
	  this.phoneIncomingAnswer();
	}
	function _onCallViewSkip2() {
	  BX.rest.callMethod('voximplant.call.skip', {
	    'CALL_ID': this.callId
	  });
	  this.phoneCallFinish();
	  this.callAbort();
	  this.callView.close();
	}
	function _onCallViewHangup2() {
	  if (this.hasExternalCall && this.callId) {
	    BX.rest.callMethod('voximplant.call.hangupDevice', {
	      'CALL_ID': this.callId
	    });
	  }
	  this.phoneCallFinish();
	  this.messengerFacade.playSound('stop');
	  if (!this.callView) {
	    return;
	  }
	  this.callView.setStatusText(main_core.Loc.getMessage('IM_M_CALL_ST_FINISHED'));
	  this.callView.setCallState(CallState.idle);
	  if (this.isCallListMode()) {
	    this.callView.setUiState(UiState.outgoing);
	    if (this.callView.isFolded()) {
	      this.callView.unfold();
	    }
	  } else {
	    this.callView.close();
	  }
	}
	function _onCallViewTransfer2(e) {
	  if (e.type == 'user' || e.type == 'pstn' || e.type == 'queue') {
	    this.phoneTransferTargetType = e.type;
	    this.phoneTransferTargetId = e.target;
	    this.sendInviteTransfer();
	  } else {
	    console.error('Unknown transfer type', e);
	  }
	}
	function _onCallViewCancelTransfer2(e) {
	  this.cancelInviteTransfer(e);
	}
	function _onCallViewCompleteTransfer2(e) {
	  this.completeTransfer(e);
	}
	function _onCallViewCallListMakeCall2(e) {
	  this.callListMakeCall(e);
	}
	function _onCallViewClose2() {
	  this.clearSkipIncomingCallTimer();
	  this.messengerFacade.stopRepeatSound('ringtone');
	  this.messengerFacade.stopRepeatSound('dialtone');
	  this.callListId = 0;
	  if (this.callView) {
	    this.callView.dispose();
	    this.closeCallViewBalloon();
	    this.callView = null;
	  }
	  this.hasActiveCallView = false;
	  if (this.deviceType == DeviceType.Phone) {
	    this.callId = '';
	    this.callActive = false;
	    this.hasExternalCall = false;
	    this.callSelfDisabled = false;
	    clearInterval(this.phoneConnectedInterval);
	    BX.localStorage.set(lsKeys$1.externalCall, false);
	  }
	}
	function _onCallViewSwitchDevice2(e) {
	  var phoneNumber = e.phoneNumber;
	  var lastCallListCallParams = this.lastCallListCallParams;
	  if (this.hasExternalCall && this.callId) {
	    BX.rest.callMethod('voximplant.call.hangupDevice', {
	      'CALL_ID': this.callId
	    });
	  }
	  this.phoneCallFinish();
	  this.callAbort();
	  this.phoneDeviceCall(!this.phoneDeviceCall());
	  this.callView.setDeviceCall(this.phoneDeviceCall());
	  if (this.isCallListMode()) {
	    this.callListMakeCall(lastCallListCallParams);
	  } else {
	    this.callView.close();
	    this.phoneCall(phoneNumber);
	  }
	}
	function _onCallViewQualityGraded2(grade) {
	  var message = {
	    COMMAND: 'gradeQuality',
	    grade: grade
	  };
	  if (this.currentCall) {
	    this.currentCall.sendMessage(JSON.stringify(message));
	  }
	}
	function _onCallViewDialpadButtonClicked2(key) {
	  this.sendDTMF(key);
	}
	function _onCallViewSaveComment2(e) {
	  BX.rest.callMethod("voximplant.call.saveComment", {
	    'CALL_ID': e.callId,
	    'COMMENT': e.comment
	  });
	}
	babelHelpers.defineProperty(PhoneCallsController, "Events", Events$1);

	// legacy compat
	BX.FoldedCallView = FoldedCallView;

	exports.PhoneCallsController = PhoneCallsController;
	exports.PhoneCallView = PhoneCallView;
	exports.BackgroundWorker = BackgroundWorker;

}((this.BX.Voximplant = this.BX.Voximplant || {}),BX.Intranet,BX.Event,BX,BX.Messenger.v2.Lib,BX.Main,BX.UI.Dialogs));
//# sourceMappingURL=phone-calls.bundle.js.map

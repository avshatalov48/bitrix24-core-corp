this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	const speechConverterEvents = Object.freeze({
	  result: 'result',
	  start: 'start',
	  stop: 'stop',
	  error: 'error'
	});
	var _speechRecognition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("speechRecognition");
	var _isRecording = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRecording");
	var _initSpeechRecognition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initSpeechRecognition");
	var _handleStartEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleStartEvent");
	var _handleEndEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEndEvent");
	var _handleErrorEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleErrorEvent");
	var _handleResultEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleResultEvent");
	var _getTextFromResults = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextFromResults");
	class SpeechConverter extends main_core_events.EventEmitter {
	  constructor(options) {
	    if (SpeechConverter.isBrowserSupport() === false) {
	      throw new Error('Your browser don\'t support WebSpeechAPI. Please, use last version of Chrome or Safari');
	    }
	    super();
	    Object.defineProperty(this, _getTextFromResults, {
	      value: _getTextFromResults2
	    });
	    Object.defineProperty(this, _handleResultEvent, {
	      value: _handleResultEvent2
	    });
	    Object.defineProperty(this, _handleErrorEvent, {
	      value: _handleErrorEvent2
	    });
	    Object.defineProperty(this, _handleEndEvent, {
	      value: _handleEndEvent2
	    });
	    Object.defineProperty(this, _handleStartEvent, {
	      value: _handleStartEvent2
	    });
	    Object.defineProperty(this, _initSpeechRecognition, {
	      value: _initSpeechRecognition2
	    });
	    Object.defineProperty(this, _speechRecognition, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isRecording, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI:SpeechConverter');
	    babelHelpers.classPrivateFieldLooseBase(this, _isRecording)[_isRecording] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _initSpeechRecognition)[_initSpeechRecognition]();
	  }
	  static isBrowserSupport() {
	    return Boolean(window.webkitSpeechRecognition || window.SpeechRecognition);
	  }
	  start() {
	    babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].start();
	  }
	  stop() {
	    babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].stop();
	  }
	  isRecording() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isRecording)[_isRecording];
	  }
	}
	function _initSpeechRecognition2() {
	  if (window.webkitSpeechRecognition) {
	    // eslint-disable-next-line new-cap
	    babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition] = new window.webkitSpeechRecognition();
	  } else if (window.SpeechRecognition) {
	    babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition] = new window.SpeechRecognition();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].lang = main_core.Loc.getMessage('LANGUAGE_ID') || 'en';
	  babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].continuous = true;
	  babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].interimResults = true;
	  babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition].maxAlternatives = 1;
	  main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition], 'start', babelHelpers.classPrivateFieldLooseBase(this, _handleStartEvent)[_handleStartEvent].bind(this));
	  main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition], 'end', babelHelpers.classPrivateFieldLooseBase(this, _handleEndEvent)[_handleEndEvent].bind(this));
	  main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition], 'error', babelHelpers.classPrivateFieldLooseBase(this, _handleErrorEvent)[_handleErrorEvent].bind(this));
	  main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _speechRecognition)[_speechRecognition], 'result', babelHelpers.classPrivateFieldLooseBase(this, _handleResultEvent)[_handleResultEvent].bind(this));
	}
	function _handleStartEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _isRecording)[_isRecording] = true;
	  this.emit(speechConverterEvents.start);
	}
	function _handleEndEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _isRecording)[_isRecording] = false;
	  this.emit(speechConverterEvents.stop);
	}
	function _handleErrorEvent2(e) {
	  const event = new main_core_events.BaseEvent({
	    data: {
	      error: e.error,
	      message: e.message
	    }
	  });
	  this.emit(speechConverterEvents.error, event);
	}
	function _handleResultEvent2(e) {
	  const event = new main_core_events.BaseEvent({
	    data: {
	      text: babelHelpers.classPrivateFieldLooseBase(this, _getTextFromResults)[_getTextFromResults](e.results)
	    }
	  });
	  this.emit(speechConverterEvents.result, event);
	}
	function _getTextFromResults2(results) {
	  return [...results].reduce((finalResultText, currentResult) => {
	    const alternative = currentResult.item(0);
	    return `${finalResultText + alternative.transcript} `;
	  }, '');
	}

	exports.speechConverterEvents = speechConverterEvents;
	exports.SpeechConverter = SpeechConverter;

}((this.BX.AI = this.BX.AI || {}),BX,BX.Event));
//# sourceMappingURL=speech-converter.bundle.js.map

var VoxImplant =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/build";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 40);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * @hidden
 */
var LogLevel;
(function (LogLevel) {
    LogLevel[LogLevel["NONE"] = 0] = "NONE";
    LogLevel[LogLevel["ERROR"] = 1] = "ERROR";
    LogLevel[LogLevel["WARNING"] = 2] = "WARNING";
    LogLevel[LogLevel["INFO"] = 3] = "INFO";
    LogLevel[LogLevel["TRACE"] = 4] = "TRACE";
})(LogLevel = exports.LogLevel || (exports.LogLevel = {}));
/**
 * @hidden
 */
var LogCategory;
(function (LogCategory) {
    LogCategory[LogCategory["SIGNALING"] = 0] = "SIGNALING";
    LogCategory[LogCategory["RTC"] = 1] = "RTC";
    LogCategory[LogCategory["USERMEDIA"] = 2] = "USERMEDIA";
    LogCategory[LogCategory["CALL"] = 3] = "CALL";
    LogCategory[LogCategory["CALLEXP2P"] = 4] = "CALLEXP2P";
    LogCategory[LogCategory["CALLEXSERVER"] = 5] = "CALLEXSERVER";
    LogCategory[LogCategory["CALLMANAGER"] = 6] = "CALLMANAGER";
    LogCategory[LogCategory["CLIENT"] = 7] = "CLIENT";
    LogCategory[LogCategory["AUTHENTICATOR"] = 8] = "AUTHENTICATOR";
    LogCategory[LogCategory["PCFACTORY"] = 9] = "PCFACTORY";
    LogCategory[LogCategory["UTILS"] = 10] = "UTILS";
    LogCategory[LogCategory["ORTC"] = 11] = "ORTC";
    LogCategory[LogCategory["MESSAGING"] = 12] = "MESSAGING";
    LogCategory[LogCategory["REINVITEQ"] = 13] = "REINVITEQ";
    LogCategory[LogCategory["HARDWARE"] = 14] = "HARDWARE";
    LogCategory[LogCategory["ENDPOINT"] = 15] = "ENDPOINT";
    LogCategory[LogCategory["EVENTTARGET"] = 16] = "EVENTTARGET";
})(LogCategory = exports.LogCategory || (exports.LogCategory = {}));
/**
 * The client states
 */
var ClientState;
(function (ClientState) {
    /**
     * The client is currently disconnected
     */
    ClientState[ClientState["DISCONNECTED"] = 'DISCONNECTED'] = "DISCONNECTED";
    /**
     * The client is currently connecting
     */
    ClientState[ClientState["CONNECTING"] = 'CONNECTING'] = "CONNECTING";
    /**
     * The client is currently connected
     */
    ClientState[ClientState["CONNECTED"] = 'CONNECTED'] = "CONNECTED";
    /**
     * The client is currently logging in
     */
    ClientState[ClientState["LOGGING_IN"] = 'LOGGING_IN'] = "LOGGING_IN";
    /**
     * The client is currently logged in
     */
    ClientState[ClientState["LOGGED_IN"] = 'LOGGED_IN'] = "LOGGED_IN";
})(ClientState = exports.ClientState || (exports.ClientState = {}));
/**
 * Common logger
 * @hidden
 */

var Logger = function () {
    function Logger(category, label, provider) {
        _classCallCheck(this, Logger);

        this.category = category;
        this.label = label;
        this.provider = provider;
    }

    _createClass(Logger, [{
        key: "log",
        value: function log(level, message) {
            this.provider.writeMessage(this.category, this.label, level, message);
        }
    }, {
        key: "error",
        value: function error(message) {
            this.log(LogLevel.ERROR, message);
        }
    }, {
        key: "warning",
        value: function warning(message) {
            this.log(LogLevel.WARNING, message);
        }
    }, {
        key: "info",
        value: function info(message) {
            this.log(LogLevel.INFO, message);
        }
    }, {
        key: "trace",
        value: function trace(message) {
            this.log(LogLevel.TRACE, message);
        }
    }]);

    return Logger;
}();

exports.Logger = Logger;
/**
 * @hidden
 */

var LogManager = function () {
    function LogManager() {
        _classCallCheck(this, LogManager);

        this.levels = {};
        this._shadowLogging = false;
    }

    _createClass(LogManager, [{
        key: "getSLog",
        value: function getSLog() {
            return this._shadowLog;
        }
    }, {
        key: "clearSilentLog",
        value: function clearSilentLog() {
            this._shadowLog = [];
        }
    }, {
        key: "setLoggerCallback",
        value: function setLoggerCallback(callback) {
            this._outerCallback = callback;
        }
    }, {
        key: "setPrettyPrint",
        value: function setPrettyPrint(state) {
            this.prettyPrint = state;
        }
    }, {
        key: "setLogLevel",
        value: function setLogLevel(category, level) {
            this.levels[LogCategory[category]] = level;
        }
    }, {
        key: "writeMessage",
        value: function writeMessage(category, label, level, message) {
            LogManager.tick++;
            var sampleMessage = "VIWSLR " + LogManager.tick + " " + new Date().toString() + " " + LogLevel[level] + " " + label + ": " + message;
            var currentLevel = LogLevel.NONE;
            if (typeof this.levels[LogCategory[category]] != 'undefined') currentLevel = this.levels[LogCategory[category]];
            if (level <= currentLevel) {
                if (typeof console.debug != 'undefined' && typeof console.info != 'undefined' && typeof console.error != 'undefined' && typeof console.warn != 'undefined') {
                    if (this.prettyPrint) {
                        if (typeof message != 'string') message = JSON.stringify(message);
                        var formatedMessage = "%c VIWSLR " + LogManager.tick + " " + new Date().toUTCString() + " [" + LogLevel[level] + "] %c" + label + ": %c" + message.replace('\r\n', '<br>');
                        if (level === LogLevel.ERROR) console.error(sampleMessage);else if (level === LogLevel.WARNING) console.warn(formatedMessage, 'color:#ccc', 'color:#2375a2', 'color:#000');else if (level === LogLevel.INFO) console.info(formatedMessage, 'color:#ccc', 'color:#2375a2', 'color:#000');else if (level === LogLevel.TRACE) console.log(formatedMessage, 'color:#ccc', 'color:#2375a2', 'color:#000');else console.log(formatedMessage, 'color:#ccc', 'color:#2375a2', 'color:#000');
                    } else {
                        if (level === LogLevel.ERROR) console.error(sampleMessage);else if (level === LogLevel.WARNING) console.warn(sampleMessage);else if (level === LogLevel.INFO) console.info(sampleMessage);else if (level === LogLevel.TRACE) console.debug(sampleMessage);else console.log(sampleMessage);
                    }
                } else console.log(sampleMessage);
            }
            if (this.shadowLogging) {
                this._shadowLog.push(sampleMessage);
            }
            if (typeof this._outerCallback === 'function') {
                this._outerCallback({
                    formattedText: sampleMessage,
                    category: category,
                    label: label,
                    level: level,
                    message: message
                });
            }
        }
    }, {
        key: "createLogger",
        value: function createLogger(category, label) {
            return new Logger(category, label, this);
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Logger';
        }
    }, {
        key: "shadowLogging",
        get: function get() {
            return this._shadowLogging;
        },
        set: function set(flag) {
            if (!this._shadowLogging) this._shadowLog = [];
            this._shadowLogging = flag;
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof this.inst == 'undefined') {
                this.inst = new LogManager();
                this.inst.prettyPrint = false;
            }
            return this.inst;
        }
        //# decorator 4 trace

    }, {
        key: "d_trace",
        value: function d_trace(category) {
            return function (target, key, _value) {
                return {
                    value: function value() {
                        var a = '';

                        for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                            args[_key] = arguments[_key];
                        }

                        try {
                            a = args.map(function (a) {
                                return JSON.stringify(a);
                            }).join();
                        } catch (e) {
                            a = 'circular structure';
                        }
                        var className = '';
                        if (target._traceName) className = target._traceName();
                        LogManager.get().writeMessage(category, className, LogLevel.TRACE, key + "(" + a + ")");
                        var result = _value.value.apply(this, args);
                        return result;
                    }
                };
            };
        }
    }]);

    return LogManager;
}();

LogManager.tick = 0;
exports.LogManager = LogManager;

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Structures_1 = __webpack_require__(31);
var Events_1 = __webpack_require__(18);
var Utils_1 = __webpack_require__(23);
var VoxSignaling_1 = __webpack_require__(2);
var Authenticator_1 = __webpack_require__(10);
var BrowserSpecific_1 = __webpack_require__(8);
var Logger_1 = __webpack_require__(0);
var PCFactory_1 = __webpack_require__(9);
var CallManager_1 = __webpack_require__(5);
var EventTarget_1 = __webpack_require__(14);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var CallstatsIo_1 = __webpack_require__(21);
var ZingayaAPI_1 = __webpack_require__(54);
var PushService_1 = __webpack_require__(55);
var GUID_1 = __webpack_require__(28);
var Hardware_1 = __webpack_require__(4);
/**
 * The Client class is used to control platform functions. Can't be instantiated directly (singleton), so use the [getInstance] method to get the class instance.
 *
 *
 * Example:
 * ``` js
 * // Getting an instance
 * var vox = VoxImplant.getInstance();
 * ```

 */

var Client = function (_EventTarget_1$EventT) {
    _inherits(Client, _EventTarget_1$EventT);

    /**
     * @hidden
     */
    function Client() {
        _classCallCheck(this, Client);

        /**
         * WS connected flag
         * @type {boolean}
         * @private
         * @hidden
         */
        var _this = _possibleConstructorReturn(this, (Client.__proto__ || Object.getPrototypeOf(Client)).call(this));

        _this._connected = false;
        /**
         * Template for progress tone
         * @type {{US: string, RU: string}}
         * @hidden
         */
        _this.progressToneScript = {
            US: '440@-19,480@-19;*(2/4/1+2)',
            RU: '425@-19;*(1/3/1)'
        };
        /**
         * Flag of now playing progress tone
         * @type {boolean}
         * @hidden
         */
        _this.playingNow = false;
        /**
         * List of available servers, returned by balancer
         * @type {Array}
         * @hidden
         */
        _this.serversList = [];
        /**
         * Global voluem level
         * @type {number}
         * @private
         * @hidden
         */
        _this.level = 100;
        /**
         * Require microphone on getUserMedia
         * @type {boolean}
         * @hidden
         */
        _this.micRequired = false;
        /**
         * Video settings to getUserMedia
         * @type {null}
         * @hidden
         */
        _this.videoConstraints = null;
        /**
         * Country for progress tone
         * now supported only "US" and "RU"
         * @type {string}
         * @hidden
         */
        _this.progressToneCountry = 'US';
        /**
         * Play progress tone on outgoing call
         * @type {boolean}
         * @hidden
         */
        _this.progressTone = true;
        /**
         * If true - set log level to TRACE
         * @type {boolean}
         * @hidden
         */
        _this.showDebugInfo = false;
        /**
         * If true - set log level to WARNING
         * @type {boolean}
         * @hidden
         */
        _this.showWarnings = false;
        /**
         * Is xRTC supported by this browser
         * @type {boolean}
         * @hidden
         */
        _this.RTCsupported = false;
        /**
         * @hidden
         * @type {boolean}
         * @private
         */
        _this._deviceEnumAPI = false;
        /**
         * @hidden
         */
        _this._h264first = false;
        /**
         * @hidden
         */
        /**
         * @hidden
         */
        _this._VP8first = false;
        /**
         * @hidden
         */
        _this.depLastDevices = { ai: [], ao: [], vi: [] };
        _this.applyMixins(Client, [EventTarget_1.EventTarget]);
        if (Client.instance) {
            throw new Error('Error - use VoxImplant.getInstance()');
        }
        Client.instance = _this;
        _this._promises = {};
        BrowserSpecific_1.default.init();
        var pc = PCFactory_1.PCFactory.get();
        pc.requireMedia = false;
        _this.voxSignaling = VoxSignaling_1.VoxSignaling.get();
        _this.voxCallManager = CallManager_1.CallManager.get();
        _this.setLogLevelAll(Logger_1.LogLevel.NONE);
        Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'SDK ver.', Logger_1.LogLevel.TRACE, _this.version);
        VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.onPCStats, function (id, stats) {
            if (PCFactory_1.PCFactory.get().getPeerConnect(id)) _this.dispatchEvent({
                name: 'NetStatsReceived',
                stats: stats
            });
        });
        _this._defaultSinkId = null;
        _this.loginState = 0;
        return _this;
    }
    /**
     * Return VoxImplant Web SDK version
     * @function
     * @hidden
     */


    _createClass(Client, [{
        key: "playProgressTone",

        /**
         * Plays progress tone according to specified country in config.progressToneCountry
         * @hidden
         */
        value: function playProgressTone() {
            var check = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

            if (!check || check && this.progressTone) {
                if (this.progressToneScript[this.progressToneCountry] !== null) {
                    if (!this.playingNow) this.playToneScript(this.progressToneScript[this.progressToneCountry]);
                    this.playingNow = true;
                }
            }
        }
        /**
         * Stop progress tone
         * @hidden
         */

    }, {
        key: "stopProgressTone",
        value: function stopProgressTone() {
            if (this.playingNow) {
                this.stopPlayback();
                this.playingNow = false;
            }
        }
        /**
         * @hidden
         */

    }, {
        key: "onIncomingCall",
        value: function onIncomingCall(id, callerid, displayName, headers, hasVideo) {
            this.dispatchEvent({
                name: Events_1.Events.IncomingCall,
                call: CallManager_1.CallManager.get().calls[id],
                headers: headers,
                video: hasVideo
            });
        }
        /**
         * Initialize SDK. The [Events.SDKReady] event will be dispatched after successful SDK initialization. SDK can't be used until it's initialized
         * @param {VoxImplant.Config} [config] Client configuration options
         */

    }, {
        key: "init",
        value: function init(config) {
            var _this2 = this;

            return new Promise(function (resolve, reject) {
                //if (this.config !== null) throw ("VoxImplant.Client has been already initialized");
                _this2._config = typeof config !== 'undefined' ? config : {};
                if (_this2._config.progressToneCountry !== undefined) _this2.progressToneCountry = _this2._config.progressToneCountry;
                if (_this2._config.progressTone !== true) _this2.progressTone = false;
                if (_this2._config.serverIp !== undefined) _this2.serverIp = _this2._config.serverIp;
                if (_this2._config.showDebugInfo !== undefined) _this2.showDebugInfo = _this2._config.showDebugInfo;
                if (_this2._config.showWarnings !== false) _this2.showWarnings = true;
                if (typeof _this2._config.videoContainerId === 'string') _this2.remoteVideoContainerId = _this2._config.videoContainerId;
                if (typeof _this2._config.remoteVideoContainerId === 'string') _this2.remoteVideoContainerId = _this2._config.remoteVideoContainerId;
                if (typeof _this2._config.localVideoContainerId === 'string') _this2.localVideoContainerId = _this2._config.localVideoContainerId;
                if (_this2._config.micRequired !== false) _this2.micRequired = true;
                if (typeof _this2._config.videoSupport != 'undefined') _this2.videoSupport = _this2._config.videoSupport;else _this2.videoSupport = false;
                if (typeof _this2._config.H264first != 'undefined') {
                    _this2._h264first = _this2._config.H264first;
                    CallManager_1.CallManager.get()._h264first = _this2._h264first;
                }
                if (typeof _this2._config.VP8first != 'undefined') _this2._VP8first = _this2._config.VP8first;
                if (typeof _this2._config.rtcStatsCollectionInterval != 'undefined') CallManager_1.CallManager.get().rtcStatsCollectionInterval = _this2._config.rtcStatsCollectionInterval;else CallManager_1.CallManager.get().rtcStatsCollectionInterval = 10000;
                if (_this2._config.protocolVersion && (_this2._config.protocolVersion === '2' || _this2._config.protocolVersion === '3')) {
                    _this2._callProtocolVersion = _this2._config.protocolVersion;
                    CallManager_1.CallManager.get().setProtocolVersion(_this2._callProtocolVersion);
                } else _this2._callProtocolVersion = '3';
                if (_this2._config.callstatsIoParams) CallstatsIo_1.CallstatsIo.get(_this2._config.callstatsIoParams);
                if (_this2._config.prettyPrint) Logger_1.LogManager.get().setPrettyPrint(_this2._config.prettyPrint);
                if (_this2.showWarnings) _this2.setLogLevelAll(Logger_1.LogLevel.WARNING);
                if (_this2.showDebugInfo) _this2.setLogLevelAll(Logger_1.LogLevel.TRACE);
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, '[sdkinit]', Logger_1.LogLevel.TRACE, _this2.version);
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, '[sdkinit]', Logger_1.LogLevel.TRACE, JSON.stringify(_this2._config));
                if (_this2._config.videoConstraints !== undefined) {
                    _this2.videoConstraints = _this2._config.videoConstraints;
                    var videoConfig = Hardware_1.default.CameraManager.legacyParamConverter(_this2._config.videoConstraints);
                    Hardware_1.default.CameraManager.get().setDefaultVideoSettings(videoConfig);
                }
                Hardware_1.default.CameraManager.get().getInputDevices().then(function (e) {
                    return _this2.depLastDevices.vi = e;
                });
                Hardware_1.default.AudioDeviceManager.get().getInputDevices().then(function (e) {
                    return _this2.depLastDevices.ai = e;
                });
                Hardware_1.default.AudioDeviceManager.get().getOutputDevices().then(function (e) {
                    return _this2.depLastDevices.ao = e;
                });
                // Show warning about getUserMedia w/o https
                if (window.location.hostname != '127.0.0.1' && window.location.hostname != 'localhost' && window.location.protocol != 'https:') {
                    if (typeof console.error != 'undefined' && _this2.showWarnings) Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'WARNING:', Logger_1.LogLevel.WARNING, 'getUserMedia() is deprecated on insecure origins, and support will be removed in the future. You should consider switching your application to a secure origin, such as HTTPS. See https://goo.gl/rStTGz for more details.');
                }
                if (_this2._config.experiments && _this2._config.experiments.ignorewebrtc) {
                    _this2.RTCsupported = true;
                } else {
                    /* Check if WebRTC is supported */
                    if (typeof webkitRTCPeerConnection != 'undefined' || typeof mozRTCPeerConnection != 'undefined' || typeof RTCPeerConnection != 'undefined' || typeof RTCIceGatherer != 'undefined') {
                        if (typeof mozRTCPeerConnection != 'undefined') {
                            try {
                                new mozRTCPeerConnection({ 'iceServers': [] });
                                _this2.RTCsupported = true;
                            } catch (e) {/* not enabled */
                            }
                        } else _this2.RTCsupported = true;
                    }
                }
                if (_this2.RTCsupported) {
                    var ts;
                    // Show warning about WebRTC security restrictions
                    if (window.location.href.match(/^file\:\/{3}.*$/g) != null) {
                        if (typeof console.error != 'undefined' && _this2.showWarnings) console.error('WebRTC requires application to be loaded from a web server');
                    }
                    // work with low-level API
                    _this2.voxAuth = Authenticator_1.Authenticator.get();
                    _this2.voxAuth.setHandler({
                        onLoginSuccessful: function onLoginSuccessful(displayName, tokens) {
                            _this2.loginState = 2;
                            var event = { name: Events_1.Events.AuthResult, displayName: displayName, result: true, tokens: tokens };
                            _this2._resolvePromise('login', event);
                            _this2.dispatchEvent(event);
                        },
                        onLoginFailed: function onLoginFailed(statusCode) {
                            _this2.loginState = 0;
                            var event = { name: Events_1.Events.AuthResult, code: statusCode, result: false };
                            _this2._rejectPromise('login', event);
                            _this2.dispatchEvent(event);
                        },
                        onSecondStageInitiated: function onSecondStageInitiated() {
                            var event = { name: Events_1.Events.AuthResult, code: 301, result: false };
                            _this2._rejectPromise('login', event);
                            _this2.dispatchEvent(event);
                        },
                        onOneTimeKeyGenerated: function onOneTimeKeyGenerated(key) {
                            var event = { name: Events_1.Events.AuthResult, key: key, code: 302, result: false };
                            _this2._resolvePromise('loginkey', event);
                            _this2.dispatchEvent(event);
                        },
                        onRefreshTokenFailed: function onRefreshTokenFailed(code) {
                            var event = { name: Events_1.Events.RefreshTokenResult, code: code, result: false };
                            _this2.dispatchEvent(event);
                        },
                        onRefreshTokenSuccess: function onRefreshTokenSuccess(oauth) {
                            var event = { name: Events_1.Events.RefreshTokenResult, tokens: oauth, result: true };
                            _this2.dispatchEvent(event);
                        }
                    });
                    _this2.voxSignaling.addHandler(_this2);
                    ts = setInterval(function () {
                        if (typeof document != 'undefined') {
                            clearInterval(ts);
                            _this2.dispatchEvent({ name: Events_1.Events.SDKReady, version: _this2.version });
                            resolve({ name: Events_1.Events.SDKReady, version: _this2.version });
                        }
                    }, 100);
                } else {
                    reject(new Error('NO_WEBRTC_SUPPORT'));
                    throw new Error('NO_WEBRTC_SUPPORT');
                }
                VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.sipRegisterSuccessful, function (id, sipuri) {
                    _this2.dispatchEvent({
                        name: 'SIPRegistrationSuccessful',
                        id: id,
                        sipuri: sipuri
                    });
                });
                VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.onACDStatus, function (id, status) {
                    _this2.dispatchEvent({
                        name: Events_1.Events.ACDStatusUpdated,
                        id: id,
                        status: status
                    });
                });
                VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.sipRegisterFailed, function (id, sipuri, status, reason) {
                    _this2.dispatchEvent({
                        name: 'SIPRegistrationFailed',
                        id: id,
                        sipuri: sipuri,
                        status: status,
                        reason: reason
                    });
                });
            });
        }
        /**
         * Create call
         * @name VoxImplant.Client.call
         * @param {String} num The number to call. For SIP compatibility reasons it should be a non-empty string even if the number itself is not used by a Voximplant cloud scenario.
         * @param {Boolean} useVideo Tells if video should be supported for the call. It's false by default.
         * @param {String} customData Custom string associated with the call session. It can be passed to the cloud to be obtained from the [CallAlerting](https://voximplant.com/docs/references/voxengine/appevents#callalerting) event or [Call History](https://voximplant.com/docs/references/httpapi/managing_history#getcallhistory) using HTTP API. Maximum size is 200 bytes. Use the [Call.sendMessage] method to pass a string over the limit; in order to pass a large data use [media_session_access_url](https://voximplant.com/docs/references/httpapi/managing_scenarios#startscenarios) on your backend.
         * @param {Object} extraHeaders Optional custom parameters (SIP headers) that should be passed with call (INVITE) message. Parameter names must start with "X-" to be processed by application. IMPORTANT: Headers size limit is 200 bytes.
             * @returns {VoxImplant.Call}
         */

    }, {
        key: "call",
        value: function call(num, useVideo, customData, extraHeaders) {
            Utils_1.Utils.checkCA();
            var sets = {
                H264first: this._h264first,
                VP8first: this._VP8first
            };
            if (typeof num === 'string' || typeof num === 'number') {
                sets = {
                    number: num,
                    video: useVideo,
                    customData: customData,
                    extraHeaders: extraHeaders
                };
            } else {
                sets = num;
            }
            switch (_typeof(sets.video)) {
                case 'boolean':
                    sets.video = { sendVideo: sets.video, receiveVideo: sets.video };
                    break;
                case 'undefined':
                    sets.video = { sendVideo: false, receiveVideo: true };
                    break;
            }
            var newCall = this.voxCallManager.call(sets);
            return newCall;
        }
        /**
         * Create call to a dedicated conference without proxy session. For details see <a href="https://medium.com/voximplant/video-conferencing-guide-for-voximplant-developers-8b1096e30129"> the video conferencing guide</a>
         * @param {String} num The number to call. For SIP compatibility reasons it should be a non-empty string even if the number itself is not used by a Voximplant cloud scenario.
         * @param {Boolean} useVideo Tells if video should be supported for the call. It's false by default.
         * @param {String} customData Custom string associated with the call session. It can be passed to the cloud to be obtained from the [CallAlerting](https://voximplant.com/docs/references/voxengine/appevents#callalerting) event or [Call History](https://voximplant.com/docs/references/httpapi/managing_history#getcallhistory) using HTTP API. Maximum size is 200 bytes. Use the [Call.sendMessage] method to pass a string over the limit; in order to pass a large data use [media_session_access_url](https://voximplant.com/docs/references/httpapi/managing_scenarios#startscenarios) on your backend.
         * @param {Object} extraHeaders Optional custom parameters (SIP headers) that should be passed with call (INVITE) message. Parameter names must start with "X-" to be processed by application. IMPORTANT: Headers size limit is 200 bytes.
         * @returns {Call}
         */

    }, {
        key: "callConference",
        value: function callConference(num, useVideo, customData, extraHeaders) {
            Utils_1.Utils.checkCA();
            var sets = {
                H264first: this._h264first,
                VP8first: this._VP8first
            };
            if (typeof num === 'string' || typeof num === 'number') {
                sets = {
                    number: num,
                    video: useVideo,
                    customData: customData,
                    extraHeaders: extraHeaders
                };
            } else {
                sets = num;
            }
            switch (_typeof(sets.video)) {
                case 'boolean':
                    sets.video = { sendVideo: sets.video, receiveVideo: sets.video };
                    break;
                case 'undefined':
                    sets.video = { sendVideo: false, receiveVideo: true };
                    break;
            }
            sets.isConference = true;
            var newCall = this.voxCallManager.callConference(sets);
            return newCall;
        }
        /**
         * Get current config
         */

    }, {
        key: "config",
        value: function config() {
            return this._config;
        }
        /**
         * Connect to VoxImplant Cloud
         */

    }, {
        key: "connect",
        value: function connect(connectivityCheck) {
            var _this3 = this;

            if (typeof this._config === 'undefined') Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'WARNING:', Logger_1.LogLevel.WARNING, 'Please, run VoxImplant init before connect.');
            if (typeof connectivityCheck === 'undefined' && this._config.micRequired === false) connectivityCheck = false;
            return new Promise(function (resolve, reject) {
                _this3._promises['connect'] = { resolve: resolve, reject: reject };
                if (_this3.serverIp !== undefined) {
                    var host = void 0;
                    if (_typeof(_this3.serverIp) === 'object') {
                        _this3.serversList = _this3.serverIp;
                        host = _this3.serversList[0];
                    } else host = _this3.serverIp;
                    if (typeof _this3._config.tryingServers === 'undefined') _this3._config.tryingServers = [];
                    _this3._config.tryingServers.push(host);
                    Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'connecting', Logger_1.LogLevel.TRACE, host + ' trying');
                    _this3.connectTo(host, null, connectivityCheck);
                } else {
                    var balancerResult = function balancerResult(data) {
                        var ind = String(data).indexOf(';'),
                            host = void 0;
                        if (ind == -1) {
                            // one IP available
                            host = data;
                        } else {
                            this.serversList = data.split(';');
                            host = this.serversList[0];
                        }
                        if (typeof this._config.tryingServers === 'undefined') this._config.tryingServers = [];
                        this._config.tryingServers.push(host);
                        Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'connecting', Logger_1.LogLevel.TRACE, host + ' trying');
                        this.connectTo(host, null, connectivityCheck);
                    };
                    Utils_1.Utils.getServers(balancerResult.bind(_this3), false, _this3);
                }
            });
        }
        /**
         * Connect to specific VoxImplant Cloud host
         * @name VoxImplant.Client.connectTo
         * @hidden
         */

    }, {
        key: "connectTo",
        value: function connectTo(host, omitMicDetection, connectivityCheck) {
            if (this._connected) {
                throw new Error('ALREADY_CONNECTED_TO_VOXIMPLANT');
            }
            this.host = host;
            this.voxSignaling.connectTo(host, true, true, connectivityCheck, this._callProtocolVersion); //this.zingayaAPI.connectTo(host, "platform");
        }
        /**
         * Disconnect from VoxImplant Cloud
         */

    }, {
        key: "disconnect",
        value: function disconnect() {
            this.checkConnection();
            this.voxSignaling.disconnect();
            Hardware_1.default.StreamManager.get().clear();
            this.voxSignaling.removeRPCHandler(RemoteEvent_1.RemoteEvent.onCallRemoteFunctionError);
            this.voxSignaling.removeRPCHandler(RemoteEvent_1.RemoteEvent.handleError);
        }
        /**
         * Set ACD status
         * @param {OperatorACDStatuses} Automatic call distributor status
         */

    }, {
        key: "setOperatorACDStatus",
        value: function setOperatorACDStatus(status) {
            var _this4 = this;

            return new Promise(function (resolve, reject) {
                Utils_1.Utils.checkCA();
                if (!Object.values(Structures_1.OperatorACDStatuses).includes(status)) {
                    reject(new Error("Wrong ACD status name " + status));
                }
                _this4.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.setOperatorACDStatus, status);
                resolve();
            });
        }
        /**
         * Return current ACD status of the operator.
         * @returns {Promise<OperatorACDStatuses>}
         */

    }, {
        key: "getOperatorACDStatus",
        value: function getOperatorACDStatus() {
            var _this5 = this;

            return new Promise(function (resolve, reject) {
                Utils_1.Utils.checkCA();
                _this5.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.getOperatorACDStatus);
                var callback = function callback(e) {
                    resolve(e.status);
                    _this5.off(Events_1.Events.ACDStatusUpdated, callback);
                };
                setTimeout(function () {
                    reject();
                    _this5.off(Events_1.Events.ACDStatusUpdated, callback);
                }, 5000);
                _this5.on(Events_1.Events.ACDStatusUpdated, callback);
            });
        }
        /**
         * Log in to an application. The method triggers the [Events.AuthResult] event.
         * @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
         * @param {String} password
         * @param {VoxImplant.LoginOptions} [options]
         */

    }, {
        key: "login",
        value: function login(username, password, options) {
            var _this6 = this;

            this.loginState = 1;
            //Sentry.getInstance().setUserContext(username);
            return new Promise(function (resolve, reject) {
                _this6._promises['login'] = { resolve: resolve, reject: reject };
                options = typeof options !== 'undefined' ? options : {};
                options = Utils_1.Utils.extend({}, options);
                if (!_this6._connected) {
                    reject(new Error('NOT_CONNECTED_TO_VOXIMPLANT'));
                    throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
                }
                //if (this.RTCsupported) this.zingayaAPI.login(username, password, options);
                if (_this6._config.experiments && _this6._config.experiments.mediaServer) {
                    options.mediaServer = _this6._config.experiments.mediaServer;
                }
                _this6.voxAuth.basicLogin(username, password, options);
            });
        }
        /**
         * Log in to an application using the 'code' auth method. The method triggers the [Events.AuthResult] event.
         *
         * Please, read <a href="http://voximplant.com/docs/quickstart/24/automated-login/">howto page</a>
         * @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
         * @param {String} code
         * @param {VoxImplant.LoginOptions} [options]
         * @hidden
         */

    }, {
        key: "loginWithCode",
        value: function loginWithCode(username, code, options) {
            var _this7 = this;

            this.loginState = 1;
            return new Promise(function (resolve, reject) {
                _this7._promises['login'] = { resolve: resolve, reject: reject };
                options = typeof options !== 'undefined' ? options : {};
                options = Utils_1.Utils.extend({ serverPresenceControl: false }, options);
                if (!_this7._connected) {
                    reject(new Error('NOT_CONNECTED_TO_VOXIMPLANT'));
                    throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
                }
                //if (this.RTCsupported) this.zingayaAPI.loginStage2(username, code, options);
                _this7.voxAuth.loginStage2(username, code, options);
            });
        }
        /**
         * Log in to an application using an accessToken. The method triggers the [Events.AuthResult] event.
         * @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
         * @param {String} token
         * @param {VoxImplant.LoginOptions} [options]
         */

    }, {
        key: "loginWithToken",
        value: function loginWithToken(username, token, options) {
            var _this8 = this;

            this.loginState = 1;
            return new Promise(function (resolve, reject) {
                _this8._promises['login'] = { resolve: resolve, reject: reject };
                options = typeof options !== 'undefined' ? options : {};
                options = Utils_1.Utils.extend({ serverPresenceControl: false }, options);
                options.accessToken = token;
                if (!_this8._connected) {
                    reject(new Error('NOT_CONNECTED_TO_VOXIMPLANT'));
                    throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
                }
                //if (this.RTCsupported) this.zingayaAPI.loginStage2(username, code, options);
                _this8.voxAuth.tokenLogin(username, options);
            });
        }
        /**
         * Refresh expired access token
         * @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
         * @param {String} refreshToken
         * @param {String} deviceToken A unique token for the current device
         */

    }, {
        key: "tokenRefresh",
        value: function tokenRefresh(username, refreshToken, deviceToken) {
            var _this9 = this;

            return new Promise(function (resolve, reject) {
                var listener = function listener(e) {
                    if (e.result) resolve(e);else reject(e);
                    _this9.off(Events_1.Events.RefreshTokenResult, listener);
                };
                _this9.on(Events_1.Events.RefreshTokenResult, listener);
                _this9.voxAuth.tokenRefresh(username, refreshToken, deviceToken);
            });
        }
        /**
         * Request a key for the 'onetimekey' auth method.
         * Server will send the key in the [Events.AuthResult] event with the code 302.
         *
         * Please, read the <a href="http://voximplant.com/docs/quickstart/24/automated-login/">how-to page</a>.
         * @param {String} username
         */

    }, {
        key: "requestOneTimeLoginKey",
        value: function requestOneTimeLoginKey(username) {
            var _this10 = this;

            return new Promise(function (resolve, reject) {
                _this10._promises['loginkey'] = { resolve: resolve, reject: reject };
                if (!_this10._connected) {
                    reject(new Error('NOT_CONNECTED_TO_VOXIMPLANT'));
                    throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
                }
                //if (this.RTCsupported) this.zingayaAPI.loginGenerateOneTimeKey(username);
                _this10.voxAuth.generateOneTimeKey(username);
            });
        }
        /**
         * Log in to an application using the 'onetimekey' auth method.
         * Hash should be calculated with the key from the triggered [Events.AuthResult] event.
         *
         * Please, read the <a href="http://voximplant.com/docs/quickstart/24/automated-login/">how-to page</a>.
         * @param {String} username
         * @param {String} hash
         * @param {VoxImplant.LoginOptions} [options]
         */

    }, {
        key: "loginWithOneTimeKey",
        value: function loginWithOneTimeKey(username, hash, options) {
            var _this11 = this;

            this.loginState = 1;
            return new Promise(function (resolve, reject) {
                _this11._promises['login'] = { resolve: resolve, reject: reject };
                options = typeof options !== 'undefined' ? options : {};
                options = Utils_1.Utils.extend({ serverPresenceControl: false }, options);
                if (!_this11._connected) {
                    reject(new Error('NOT_COFNNECTED_TO_VOXIMPLANT'));
                    throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
                }
                //if (this.RTCsupported) this.zingayaAPI.loginUsingOneTimeKey(username, hash, options);
                _this11.voxAuth.loginUsingOneTimeKey(username, hash, options);
            });
        }
        /**
         * Check if connected to VoxImplant Cloud
         * @deprecated
         * See [[Client.getClientState]]
         */

    }, {
        key: "connected",
        value: function connected() {
            return this._connected;
        }
        /**
         * Show/hide local video. *IMPORTANT*: Safari browser for iOS requires a user interface for playing video during a call. It should be interactive element like an HTML "button" with "onclick" handler that calls "play" method on the "video" HTML element.
         * @param {Boolean} [flag=true] Show/hide - true/false
         * @param {Boolean} [mirror=false] Mirror local video
         * @param {Boolean} [detachCamera=false] Detach camera on hide local video
         */

    }, {
        key: "showLocalVideo",
        value: function showLocalVideo() {
            var flag = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
            var mirror = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
            var detachCamera = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

            if (flag) return Hardware_1.default.StreamManager.get().showLocalVideo();else return Hardware_1.default.StreamManager.get().hideLocalVideo();
        }
        /**
         * Set local video position
         * @param {Number} x Horizontal position (px)
         * @param {Number} y Vertical position (px)
         * @function
         * @hidden
         * @deprecated
         * @name VoxImplant.Client.setLocalVideoPosition
         */

    }, {
        key: "setLocalVideoPosition",
        value: function setLocalVideoPosition(x, y) {
            throw new Error('Deprecated: please use CSS to position \'#voximplantlocalvideo\' element');
        }
        /**
         * Set local video size
         * @param {Number} width Width in pixels
         * @param {Number} height Height in pixels
         * @function
         * @hidden
         * @deprecated
         * @name VoxImplant.Client.setLocalVideoSize
         */

    }, {
        key: "setLocalVideoSize",
        value: function setLocalVideoSize(width, height) {
            throw new Error('Deprecated: please use CSS to set size of \'#voximplantlocalvideo\' element');
        }
        /**
         * Set video settings globally. This settings will be used for the next call.
         * @param {VoxImplant.VideoSettings|VoxImplant.FlashVideoSettings} settings Video settings
         * @param {Function} [successCallback] Success callback function has MediaStream object as its argument
         * @param {Function} [failedCallback] Failed callback function
         * @deprecated
         * @hidden
         */

    }, {
        key: "setVideoSettings",
        value: function setVideoSettings(settings, successCallback, failedCallback) {
            Hardware_1.default.CameraManager.get().setDefaultVideoSettings(Hardware_1.default.CameraManager.legacyParamConverter(settings));
            CallManager_1.CallManager.get().setVideoSettings(settings).then(function () {
                if (successCallback) successCallback(null);
            }, function (err) {
                if (failedCallback) failedCallback(null);
            });
        }
        /**
         * Set bandwidth limit for video calls. Currently supported by Chrome/Chromium. (WebRTC mode only). The limit will be applied for the next call.
         * @param {Number} bandwidth Bandwidth limit in kilobits per second (kbps)
         */

    }, {
        key: "setVideoBandwidth",
        value: function setVideoBandwidth(bandwidth) {
            this.checkConnection();
            PCFactory_1.PCFactory.get().setBandwidthParams(bandwidth);
            this.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.setDesiredVideoBandwidth, bandwidth);
        }
        /**
         * Play ToneScript using WebAudio API
         * @param {String} script Tonescript string
         * @param {Boolean} [loop=false] Loop playback if true
         */

    }, {
        key: "playToneScript",
        value: function playToneScript(script) {
            var loop = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

            Utils_1.Utils.playToneScript(script, loop);
        }
        /**
         * Stop playing ToneScript using WebAudio API
         */

    }, {
        key: "stopPlayback",
        value: function stopPlayback() {
            if (Utils_1.Utils.stopPlayback()) this.dispatchEvent({ name: Events_1.Events.PlaybackFinished });
        }
        /**
         * Change current global sound volume
         * @deprecated
         * @param {Number} level New sound volume value between 0 and 100
         * @function
         * @hidden
         */

    }, {
        key: "volume",
        value: function volume(level) {
            if (typeof level !== 'undefined') {
                if (level > 100) level = 100;
                if (level < 0) level = 0;
                CallManager_1.CallManager.get().setAllCallsVolume(level);
                this.level = level;
            }
            return this.level;
        }
        /**
         * Get a list of all currently available audio sources / microphones
         * @deprecated
         * @hidden
         */

    }, {
        key: "audioSources",
        value: function audioSources() {
            return this.depLastDevices.ai;
        }
        /**
         * Get a list of all currently available video sources / cameras
         * @deprecated
         * @hidden
         */

    }, {
        key: "videoSources",
        value: function videoSources() {
            return this.depLastDevices.vi;
        }
        /**
         * Get a list of all currently available audio playback devices
         * @deprecated
         * @hidden
         */

    }, {
        key: "audioOutputs",
        value: function audioOutputs() {
            return this.depLastDevices.ao;
        }
        /**
         * Use specified audio source, use [audioSources] to get the list of available audio sources
         * If SDK was init with micRequired: false, force attach microphone.
         * @param {String} id Id of the audio source
         * @param {Function} [successCallback] Called in WebRTC mode if audio source changed successfully
         * @param {Function} [failedCallback] Called in WebRTC mode if audio source couldn't changed successfully
         * @deprecated
         * @hidden
         */

    }, {
        key: "useAudioSource",
        value: function useAudioSource(id, successCallback, failedCallback) {
            var currentDefaultSettings = Hardware_1.default.AudioDeviceManager.get().getDefaultAudioSettings();
            Hardware_1.default.AudioDeviceManager.get().setDefaultAudioSettings(Object.assign({}, currentDefaultSettings, { inputId: id }));
            return new Promise(function (resolve, reject) {
                return CallManager_1.CallManager.get().useAudioSource(id).then(function () {
                    if (successCallback) successCallback(null);
                    resolve(null);
                }, function (err) {
                    if (failedCallback) failedCallback(err);
                    reject(err);
                });
            });
        }
        /**
         * Use specified video source, use [videoSources] to get the list of available video sources
         * @param {String} id Id of the video source
         * @param {Function} [successCallback] Called if video source changed successfully, has MediaStream object as its argument
         * @param {Function} [failedCallback] Called if video source couldn't be changed successfully, has MediaStreamError object as its argument
         * @deprecated
         * @hidden
         */

    }, {
        key: "useVideoSource",
        value: function useVideoSource(id, successCallback, failedCallback) {
            var currentDefaultSettings = Hardware_1.default.CameraManager.get().getDefaultVideoSettings();
            Hardware_1.default.CameraManager.get().setDefaultVideoSettings(Object.assign({}, currentDefaultSettings, { cameraId: id }));
            return new Promise(function (resolve, reject) {
                return CallManager_1.CallManager.get().useVideoSource(id).then(function () {
                    if (successCallback) successCallback(null);
                    resolve(null);
                }, function (err) {
                    if (failedCallback) failedCallback(err);
                    reject(err);
                });
            });
        }
        /**
         * Use specified audio output for new calls, use [audioOutputs] to get the list of available audio output
         * @param {String} id Id of the audio source
         * @deprecated
         * @hidden
         */

    }, {
        key: "useAudioOutput",
        value: function useAudioOutput(id) {
            var _this12 = this;

            return new Promise(function (resolve, reject) {
                if (BrowserSpecific_1.default.getWSVendor(true) !== 'chrome') reject(new Error('Unsupported browser. Only Google Chrome 49 and above.'));
                _this12._defaultSinkId = id;
                resolve();
            });
        }
        /**
         * Enable microphone/camera if micRequired in [Config] was set to false.
         * @param {Function} successCallback Called if selected recording devices were attached successfully, has MediaStream object as its argument
         * @param {Function} failedCallback Called if selected recording devices couldn't be attached, has MediaStreamError object as its argument
         * @deprecated
         * @hidden
         */

    }, {
        key: "attachRecordingDevice",
        value: function attachRecordingDevice(successCallback, failedCallback) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'DEPRECATED', Logger_1.LogLevel.ERROR, 'Now all media connection on demand. There is no reason do it by hand.');
            if (successCallback) successCallback(null);
            return;
        }
        /**
         * Disable microphone/camera if micRequired in [Config] was set to false
         * @deprecated
         * @hidden
         */

    }, {
        key: "detachRecordingDevice",
        value: function detachRecordingDevice() {
            Hardware_1.default.StreamManager.get().clear();
        }
        /**
         * Set active call
         * @param {VoxImplant.Call} call VoxImplant call instance
         * @param {Boolean} [active=true] If true make call active, otherwise make call inactive
         * @deprecated
         * @hidden
         */

    }, {
        key: "setCallActive",
        value: function setCallActive(call) {
            var active = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

            return new Promise(function (resolve, reject) {
                Utils_1.Utils.checkCA();
                if (call) return call.setActive(active);else reject('trying to hold unknown call ' + call);
            });
        }
        /**
         * Start/stop sending local video to remote party/parties. *IMPORTANT*: Safari browser for iOS requires a user interface for playing video during a call. It should be interactive element like an HTML "button" with "onclick" handler that calls "play" method on the "video" HTML element.
         * @param {Boolean} [flag=true] Start/stop - true/false
         * @deprecated
         * @hidden
         */

    }, {
        key: "sendVideo",
        value: function sendVideo() {
            var flag = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'DEPRECATED', Logger_1.LogLevel.ERROR, 'This function deprecated. Use Call.sendVideo() instead.');
            //TODO: alert that function not supported more
        }
        /**
         * Check if WebRTC support is available
         * @returns {Boolean}
         */

    }, {
        key: "isRTCsupported",
        value: function isRTCsupported() {
            if (typeof webkitRTCPeerConnection != 'undefined' || typeof mozRTCPeerConnection != 'undefined' || typeof RTCPeerConnection != 'undefined' || typeof RTCIceGatherer != 'undefined') {
                if (typeof mozRTCPeerConnection != 'undefined') {
                    try {
                        new mozRTCPeerConnection({ 'iceServers': [] });
                        return true;
                    } catch (e) {
                        return false;
                    }
                } else {
                    return true;
                }
            }
        }
        /**
         * Transfer call, depending on the result [CallEvents.TransferComplete] or [CallEvents.TransferFailed] event will be dispatched.
         * @param {VoxImplant.Call} call1 Call which will be transferred
         * @param {VoxImplant.Call} call2 Call where call1 will be transferred
         */

    }, {
        key: "transferCall",
        value: function transferCall(call1, call2) {
            Utils_1.Utils.checkCA();
            this.voxCallManager.transferCall(call1, call2);
        }
        /**
         * Set log levels for specified log categories
         * @param {LogCategory} category Log category
         * @param {LogLevel} level Log level
         * @hidden
         */

    }, {
        key: "setLogLevel",
        value: function setLogLevel(category, level) {
            Logger_1.LogManager.get().setLogLevel(category, level);
        }
        /**
         * @hidden
         */

    }, {
        key: "onSignalingConnected",
        value: function onSignalingConnected() {
            this._connected = true;
            var event = { name: Events_1.Events.ConnectionEstablished };
            this._resolvePromise('connect', event);
            this.dispatchEvent(event);
        }
    }, {
        key: "onSignalingClosed",

        /**
         * @hidden
         */
        value: function onSignalingClosed() {
            this._connected = false;
            this.dispatchEvent({ name: Events_1.Events.ConnectionClosed });
            if (this.progressTone) this.stopProgressTone();
        }
    }, {
        key: "onSignalingConnectionFailed",

        /**
         * @hidden
         */
        value: function onSignalingConnectionFailed(reason) {
            this._connected = false;
            if (this.serversList.length > 1 && (typeof this.serverIp === 'undefined' || _typeof(this.serverIp) === 'object')) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'connecting', Logger_1.LogLevel.TRACE, "Connection to the " + this.serversList[0] + " falled");
                this.serversList.splice(0, 1);
                var host = this.serversList[0];
                if (typeof this._config.tryingServers === 'undefined') this._config.tryingServers = [];
                this._config.tryingServers.push(host);
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'connecting', Logger_1.LogLevel.TRACE, host + ' trying');
                this.connectTo(host, true);
            } else {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, '', Logger_1.LogLevel.INFO, "We can't connect to the Voximplant cloud. Please, check UDP connection to Voximplant servers: " + this._config.tryingServers.join(', '));
                var event = { name: Events_1.Events.ConnectionFailed, message: reason };
                this._rejectPromise('connect', event);
                this.dispatchEvent(event);
            }
        }
        /**
         * @hidden
         */

    }, {
        key: "onMediaConnectionFailed",
        value: function onMediaConnectionFailed() {}
    }, {
        key: "getCall",

        /**
         * Not documented function for backward compatibility
         * @hidden
         * @param string call_id Call ID
         * @returns {Call}
         */
        value: function getCall(call_id) {
            return CallManager_1.CallManager.get().calls[call_id];
        }
        /**
         * Not documented function for backward compatibility
         * Remove call from calls array
         * @param string call_id Call id
         * @hidden
         */

    }, {
        key: "removeCall",
        value: function removeCall(call_id) {
            CallManager_1.CallManager.get().removeCall(call_id);
        }
        /**
         * Returns promise that is resolved with a boolean flag. The boolean flag
         * is set to 'true' if screen sharing is supported.
         * Promise is rejected in case of an internal error.
         */

    }, {
        key: "screenSharingSupported",
        value: function screenSharingSupported() {
            return BrowserSpecific_1.default.screenSharingSupported();
        }
        /**
         * Register handler for specified event
         * @param event Event class (i.e. [Events.SDKReady]). See [Events]
         * @param handler Handler function. A single parameter is passed - object with event information
         * @deprecated
         * @hidden
         */

    }, {
        key: "addEventListener",
        value: function addEventListener(event, handler) {
            _get(Client.prototype.__proto__ || Object.getPrototypeOf(Client.prototype), "addEventListener", this).call(this, event, handler);
        }
        /**
         * Remove handler for specified event
         * @param {Function} event Event class (i.e. [Events.SDKReady]). See [Events]
         * @param {Function} [handler] Handler function, if not specified all event handlers will be removed
         * @function
         * @deprecated
         * @hidden
         */

    }, {
        key: "removeEventListener",
        value: function removeEventListener(event, handler) {
            _get(Client.prototype.__proto__ || Object.getPrototypeOf(Client.prototype), "removeEventListener", this).call(this, event, handler);
        }
        /**
         * Register a handler for the specified event. The method is a shorter equivalent for *addEventListener*. One event can have more than one handler; handlers are executed in order of registration.
         * Use the [Client.off] method to delete a handler.
         * @param {Function} event Event class (i.e. [Events.SDKReady]). See [Events]
         * @param {Function} handler Handler function. A single parameter is passed - object with event information
         * @function
         */

    }, {
        key: "on",
        value: function on(event, handler) {
            _get(Client.prototype.__proto__ || Object.getPrototypeOf(Client.prototype), "on", this).call(this, event, handler);
        }
        /**
         * Remove a handler for the specified event. The method is a shorter equivalent for *removeEventListener*. If a number of events has the same function as a handler, the method can be called multiple times with the same handler argument.
         * @param {Function} event Event class (i.e. [Events.SDKReady]). See [Events]
         * @param {Function} [handler] Handler function, if not specified all event handlers will be removed
         * @function
         */

    }, {
        key: "off",
        value: function off(event, handler) {
            _get(Client.prototype.__proto__ || Object.getPrototypeOf(Client.prototype), "off", this).call(this, event, handler);
        }
        /**
         * @hidden
         * @param val
         */

    }, {
        key: "sslset",
        value: function sslset(val) {
            this.voxSignaling.writeLog = val;
        }
        /**
         * @hidden
         * @returns {Array<string>}
         */

    }, {
        key: "sslget",
        value: function sslget() {
            return this.voxSignaling.getLog();
        }
        /**
         * @hidden
         */

    }, {
        key: "getZingayaAPI",
        value: function getZingayaAPI() {
            return new ZingayaAPI_1.ZingayaAPI(this);
        }
        /**
         * Register for push notifications. Application will receive push notifications from VoxImplant Server after first log in.
         * @hidden
         * @param token FCM registration token that can be retrieved by calling firebase.messaging().getToken() inside a service worker
         * @returns {Promise<void>}
         */

    }, {
        key: "registerForPushNotificatuons",
        value: function registerForPushNotificatuons(token) {
            return PushService_1.PushService.register(token);
        }
        /**
         * Unregister from push notifications. Application will no longer receive push notifications from VoxImplant server.
         * @hidden
         * @param token FCM registration token that was used to register for push notifications
         * @returns {Promise<void>}
         */

    }, {
        key: "unregisterForPushNotificatuons",
        value: function unregisterForPushNotificatuons(token) {
            return PushService_1.PushService.unregister(token);
        }
        /**
         * Handle incoming push notification
         * @hidden
         * @param message  Incoming push notification that comes from the firebase.messaging().setBackgroundMessageHandler callback inside a service worker
         * @returns {Promise<void>}
         */

    }, {
        key: "handlePushNotification",
        value: function handlePushNotification(message) {
            return PushService_1.PushService.incomingPush(message);
        }
        /**
         * Generate a new GUID identifier. Unique each time.
         * @hidden
         */

    }, {
        key: "getGUID",
        value: function getGUID() {
            return new GUID_1.GUID().toString();
        }
        /**
         * @hidden
         * @param {boolean} flag
         */

    }, {
        key: "setSilentLogging",
        value: function setSilentLogging(flag) {
            this.enableSilentLogging(flag);
        }
        /**
         * Set the state of the silent logging inside SDK (it is disabled by default). When it is enabled, the WebSDK will save all log messages into the log until you disable it.
         *
         * Note that enabling of the silent logging automatically clears all existed log records before the start.
         *
         * You can get current log by the [getSilentLog] function and clean it by the [clearSilentLog] function.
         * @param {boolean} flag
         */

    }, {
        key: "enableSilentLogging",
        value: function enableSilentLogging(flag) {
            Logger_1.LogManager.get().shadowLogging = flag;
        }
        /**
         * Clear the log journal and free some memory.
         */

    }, {
        key: "clearSilentLog",
        value: function clearSilentLog() {
            Logger_1.LogManager.get().clearSilentLog();
        }
        /**
         * Get records from the log journal.
         * @returns {Array<string>}
         */

    }, {
        key: "getSilentLog",
        value: function getSilentLog() {
            return Logger_1.LogManager.get().getSLog();
        }
        /**
         * Set outer logging callback.
         *
         * The method allows integrating logging pipeline of the WebSDK into your own logger i.e. the method call sends all events to your function.
         * *IMPORTANT:* the callback strictly ignores Loglevel settings of the WebSDK.
         *
         * @param {{(record: LogRecord): void}} callback
         */

    }, {
        key: "setLoggerCallback",
        value: function setLoggerCallback(callback) {
            Logger_1.LogManager.get().setLoggerCallback(callback);
        }
        /**
         * Get current client state
         * @return {ClientState}
         */

    }, {
        key: "getClientState",
        value: function getClientState() {
            var signalingState = this.voxSignaling.currentState;
            if (signalingState == VoxSignaling_1.VoxSignalingState.CONNECTING || signalingState == VoxSignaling_1.VoxSignalingState.WSCONNECTED) {
                return Logger_1.ClientState.CONNECTING;
            } else if (signalingState == VoxSignaling_1.VoxSignalingState.CLOSING || signalingState == VoxSignaling_1.VoxSignalingState.IDLE) return Logger_1.ClientState.DISCONNECTED;else if (signalingState == VoxSignaling_1.VoxSignalingState.CONNECTED) {
                if (this.loginState == 1) {
                    return Logger_1.ClientState.LOGGING_IN;
                } else if (this.loginState == 2) {
                    return Logger_1.ClientState.LOGGED_IN;
                }
                return Logger_1.ClientState.CONNECTED;
            }
        }
        /**
         * @hidden
         * @deprecated
         * @returns {any}
         */

    }, {
        key: "setSwfColor",
        value: function setSwfColor() {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.CLIENT, 'NOT SUPPORTED', Logger_1.LogLevel.ERROR, 'setSwfColor deprecated, and not supported!');
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Client';
        }
        /**
         * @hidden
         * @param {number} as
         * @param {number} tias
         */

    }, {
        key: "setXAS",
        value: function setXAS(as, tias) {
            if (typeof this._config.experiments === 'undefined') this._config.experiments = {};
            this._config.experiments.xas = { as: as, tias: tias };
        }
        /**
         * @hidden
         */

    }, {
        key: "removeCC",
        value: function removeCC(flag) {
            if (typeof this._config.experiments === 'undefined') this._config.experiments = {};
            this._config.experiments.removeTransportCC = flag;
        }
        /**
         * Helper for apply mixins
         * @hidden
         * @param derivedCtor
         * @param baseCtors
         */

    }, {
        key: "applyMixins",
        value: function applyMixins(derivedCtor, baseCtors) {
            baseCtors.forEach(function (baseCtor) {
                Object.getOwnPropertyNames(baseCtor.prototype).forEach(function (name) {
                    derivedCtor.prototype[name] = baseCtor.prototype[name];
                });
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "checkConnection",
        value: function checkConnection() {
            if (!this._connected) throw new Error('NOT_CONNECTED_TO_VOXIMPLANT');
        }
        /**
         * @hidden
         * @param {string} eventName
         * @param {Object} event
         * @private
         */

    }, {
        key: "_resolvePromise",
        value: function _resolvePromise(eventName, event) {
            var promise = this._promises[eventName];
            if (promise) {
                promise.resolve(event);
                this._promises[eventName] = undefined;
            }
        }
        /**
         * @hidden
         * @param {string} eventName
         * @param {Object} event
         * @private
         */

    }, {
        key: "_rejectPromise",
        value: function _rejectPromise(eventName, event) {
            var promise = this._promises[eventName];
            if (promise) {
                promise.reject(event);
                this._promises[eventName] = undefined;
            }
        }
        /**
         * @hidden
         * @param level
         */

    }, {
        key: "setLogLevelAll",
        value: function setLogLevelAll(level) {
            this.setLogLevel(Logger_1.LogCategory.SIGNALING, level);
            this.setLogLevel(Logger_1.LogCategory.RTC, level);
            this.setLogLevel(Logger_1.LogCategory.ORTC, level);
            this.setLogLevel(Logger_1.LogCategory.USERMEDIA, level);
            this.setLogLevel(Logger_1.LogCategory.CALL, level);
            this.setLogLevel(Logger_1.LogCategory.CALLEXP2P, level);
            this.setLogLevel(Logger_1.LogCategory.CALLEXSERVER, level);
            this.setLogLevel(Logger_1.LogCategory.CALLMANAGER, level);
            this.setLogLevel(Logger_1.LogCategory.CLIENT, level);
            this.setLogLevel(Logger_1.LogCategory.AUTHENTICATOR, level);
            this.setLogLevel(Logger_1.LogCategory.PCFACTORY, level);
            this.setLogLevel(Logger_1.LogCategory.UTILS, level);
            this.setLogLevel(Logger_1.LogCategory.MESSAGING, level);
            this.setLogLevel(Logger_1.LogCategory.REINVITEQ, level);
            this.setLogLevel(Logger_1.LogCategory.HARDWARE, level);
            this.setLogLevel(Logger_1.LogCategory.ENDPOINT, level);
            this.setLogLevel(Logger_1.LogCategory.EVENTTARGET, level);
        }
    }, {
        key: "version",
        get: function get() {
            return '4.3.31692-1543311155';
        }
        /**
         * @hidden
         */

    }], [{
        key: "getInstance",
        value: function getInstance() {
            if (typeof Client.instance == 'undefined') Client.instance = new Client();
            return Client.instance;
        }
    }]);

    return Client;
}(EventTarget_1.EventTarget);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "playProgressTone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "stopProgressTone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "onIncomingCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "init", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "call", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "callConference", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "config", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "connect", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "connectTo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "disconnect", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setOperatorACDStatus", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "getOperatorACDStatus", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "login", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "loginWithCode", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "loginWithToken", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "tokenRefresh", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "requestOneTimeLoginKey", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "loginWithOneTimeKey", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "connected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "showLocalVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setLocalVideoPosition", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setLocalVideoSize", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setVideoBandwidth", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "playToneScript", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "stopPlayback", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "volume", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "audioSources", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "videoSources", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "audioOutputs", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "useAudioSource", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "useVideoSource", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "useAudioOutput", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "attachRecordingDevice", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "detachRecordingDevice", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setCallActive", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "sendVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "isRTCsupported", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "transferCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setLogLevel", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "onSignalingConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "onSignalingClosed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "onSignalingConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "onMediaConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "getCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "removeCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "addEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "removeEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "on", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "off", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "checkConnection", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client.prototype, "setLogLevelAll", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Client, "getInstance", null);
exports.Client = Client;

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var BrowserSpecific_1 = __webpack_require__(8);
var PCFactory_1 = __webpack_require__(9);
var CallManager_1 = __webpack_require__(5);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var MsgSignaling_1 = __webpack_require__(15);
var Client_1 = __webpack_require__(1);
/**
 * @hidden
 */
var VoxSignalingState;
(function (VoxSignalingState) {
    VoxSignalingState[VoxSignalingState["IDLE"] = 0] = "IDLE";
    VoxSignalingState[VoxSignalingState["CONNECTING"] = 1] = "CONNECTING";
    VoxSignalingState[VoxSignalingState["WSCONNECTED"] = 2] = "WSCONNECTED";
    VoxSignalingState[VoxSignalingState["CONNECTED"] = 3] = "CONNECTED";
    VoxSignalingState[VoxSignalingState["CLOSING"] = 4] = "CLOSING";
})(VoxSignalingState = exports.VoxSignalingState || (exports.VoxSignalingState = {}));
/**
 * Websocket-based implementation of signaling protocol
 * Singleton
 * IDLE => CONNECTING => WSCONNECTED => CONNECTED => CLOSING => IDLE
 *                 ||        ||      /\           \--(close() called)
 * (WS connection  ||        ||       |
 *      failed)    \/        ||       \-- (__connectionSuccessful RPC)
 *                IDLE       ||
 *                           ||
 * (__connectionFailed RPC)  ||
 *                           ||
 *                           \/
 *                          IDLE
 *
 * (Simplified graph)
 *
 * @hidden
 */

var VoxSignaling = function () {
    function VoxSignaling() {
        var _this = this;

        _classCallCheck(this, VoxSignaling);

        /**
         * ver 2 - old version
         * ver 3 - new call scheme
         * @type {string}
         */
        this.ver = "3";
        this.handlers = [];
        this.rpcHandlers = {};
        /**
         * Link for ping timer
         * @type {null}
         */
        this.pingTimer = null;
        /**
         * Link for pong await timer
         * @type {null}
         */
        this.pongTimer = null;
        this.manualDisconnect = false;
        this.platform = 'platform';
        this.referrer = 'platform';
        this.extra = '';
        this.closing = false;
        this.writeLog = false;
        this._opLog = [];
        this.token = '';
        this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.SIGNALING, "VoxSignaling");
        this.currentState = VoxSignalingState.IDLE;
        this.setRPCHandler(RemoteEvent_1.RemoteEvent.connectionSuccessful, function (token) {
            _this.onConnectionSuccessfulRPC(token);
        });
        this.setRPCHandler(RemoteEvent_1.RemoteEvent.connectionFailed, function () {
            _this.onConnectionFailedRPC();
        });
        this.setRPCHandler(RemoteEvent_1.RemoteEvent.createConnection, function (token) {
            _this.onConnectionSuccessfulRPC(token);
        });
    }

    _createClass(VoxSignaling, [{
        key: "addHandler",

        /**
         * Add signaling event handler
         * @param h
         */
        value: function addHandler(h) {
            this.handlers.push(h);
        }
        /**
         * Disconnect WS and run onWSClosed
         */

    }, {
        key: "close",
        value: function close() {
            this.closing = true;
            if (this.ws) {
                this.ws.onclose = null;
                this.ws.close();
                this.onWSClosed(null);
            } else {
                this.log.warning("Try close unused WS in state " + VoxSignalingState[this.currentState]);
            }
        }
        /**
         * clear ping&pong timeouts
         */

    }, {
        key: "cleanup",
        value: function cleanup() {
            PCFactory_1.PCFactory.get().closeAll();
            if (this.pingTimer) clearTimeout(this.pingTimer);
            if (this.pongTimer) clearTimeout(this.pongTimer);
        }
        /**
         * Change synthetical state and fire userEvent wher WS connecting
         */

    }, {
        key: "onConnectionSuccessfulRPC",
        value: function onConnectionSuccessfulRPC(token) {
            if (this.currentState != VoxSignalingState.WSCONNECTED) {
                this.log.error("Can't handle __connectionSuccessful while in state " + VoxSignalingState[this.currentState]);
                return;
            }
            if (token) this.token = token;
            this.currentState = VoxSignalingState.CONNECTED;
            if (this.handlers.length > 0) {
                for (var i = 0; i < this.handlers.length; ++i) {
                    try {
                        this.handlers[i].onSignalingConnected();
                    } catch (e) {
                        this.log.warning("Error in onSignalingConnected callback: " + e);
                    }
                }
            } else {
                this.log.warning("No VoxSignaling handler specified");
            }
        }
        /**
         * Change synthetical state and fire userEvent wher WS disconnecting
         */

    }, {
        key: "onConnectionFailedRPC",
        value: function onConnectionFailedRPC() {
            if (this.currentState != VoxSignalingState.WSCONNECTED) {
                this.log.error("Can't handle __connectionSuccessful while in state " + VoxSignalingState[this.currentState]);
                return;
            }
            this.ws.onerror = null;
            this.ws.close();
            this.ws = null;
            this.currentState = VoxSignalingState.IDLE;
            if (this.handlers.length > 0) {
                for (var i = 0; i < this.handlers.length; ++i) {
                    try {
                        this.handlers[i].onMediaConnectionFailed();
                    } catch (e) {
                        this.log.warning("Error in onMediaConnectionFailed callback: " + e);
                    }
                }
            } else {
                this.log.warning("No VoxSignaling handler specified");
            }
        }
        /**
         * Connect to selected WS server and bind WSEvents
         * @param host
         * @param isVideo
         * @param secure
         * @param connectivityCheck
         * @param version
         */

    }, {
        key: "connectTo",
        value: function connectTo(host) {
            var isVideo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
            var secure = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

            var _this2 = this;

            var connectivityCheck = arguments[3];
            var version = arguments[4];

            //Sentry.setLastHost(host);
            this.manualDisconnect = false;
            this.ver = version;
            if (this.currentState != VoxSignalingState.IDLE) {
                this.log.error("Can't establish connection while in state " + VoxSignalingState[this.currentState]);
                return;
            }
            this.currentState = VoxSignalingState.CONNECTING;
            var browser = BrowserSpecific_1.default.getWSVendor();
            this.ws = new WebSocket("ws" + (secure ? 's' : '') + "://" + host + "/" + this.platform + "?version=" + this.ver + "&client=" + browser + "&ccheck=" + (typeof connectivityCheck === "undefined" ? true : connectivityCheck) + "&referrer=&extra=" + this.extra + "&video=" + (isVideo ? "true" : "false") + "&client_version=" + Client_1.Client.getInstance().version);
            this.ws.onopen = function (e) {
                return _this2.onWSConnected();
            };
            this.ws.onclose = function (e) {
                return _this2.onWSClosed(e);
            };
            this.ws.onerror = function (e) {
                return _this2.onWSError();
            };
            this.ws.onmessage = function (e) {
                return _this2.onWSData(e.data);
            };
        }
        /**
         * Set handler for Server -> Client RPC
         */

    }, {
        key: "setRPCHandler",
        value: function setRPCHandler(name, callback) {
            if (typeof this.rpcHandlers[name] != "undefined") {
                this.log.warning("Overwriting RPC handler for function " + name);
            }
            this.rpcHandlers[name] = callback;
        }
        /**
         * Set handler for Server -> Client RPC
         * @param name
         */

    }, {
        key: "removeRPCHandler",
        value: function removeRPCHandler(name) {
            if (typeof this.rpcHandlers[name] == "undefined" && !this.closing) {
                this.log.warning("There is no RPC handler for function " + name);
            }
            delete this.rpcHandlers[name];
        }
        /**
         * Invoke Client->Server RPC
         * @param name
         * @param params
         */

    }, {
        key: "callRemoteFunction",
        value: function callRemoteFunction(name) {
            for (var _len = arguments.length, params = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
                params[_key - 1] = arguments[_key];
            }

            if (this.currentState != VoxSignalingState.CONNECTED && this.currentState != VoxSignalingState.WSCONNECTED) {
                if (!this.closing) this.log.error("Can't make a RPC call in state " + VoxSignalingState[this.currentState]);
                return false;
            }
            if (typeof this.ws != "undefined") {
                if (this.writeLog) this._opLog.push("send:" + JSON.stringify({ "name": name, "params": params }));
                var data = JSON.stringify({ "name": name, "params": params });
                this.ws.send(data);
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.SIGNALING, '[wsdataout]', Logger_1.LogLevel.INFO, data);
                return true;
            }
        }
        /**
         * WebSocket callbacks
         */

    }, {
        key: "onWSData",
        value: function onWSData(data) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.SIGNALING, '[wsdatain]', Logger_1.LogLevel.INFO, data);
            if (this.writeLog) this._opLog.push("recv:" + data);
            var parsedData = void 0;
            try {
                parsedData = JSON.parse(data);
            } catch (e) {
                this.log.error("Can't parse JSON data: " + data);
                return;
            }
            if (typeof parsedData['service'] != "undefined") this.onWSMessData(parsedData);else this.onWSVoipData(parsedData);
        }
        /**
         * Handle new messaging protocol
         * @hidden
         * @param parsedData
         */

    }, {
        key: "onWSMessData",
        value: function onWSMessData(parsedData) {
            MsgSignaling_1.MsgSignaling.get().handleWsData(parsedData);
        }
        /**
         * Send WS message to default old flow
         * @hidden
         * @param parsedData
         */

    }, {
        key: "onWSVoipData",
        value: function onWSVoipData(parsedData) {
            var functionName = parsedData["name"];
            var callParams = parsedData["params"];
            if (typeof this.rpcHandlers[functionName] != "undefined") {
                try {
                    this.rpcHandlers[functionName].apply(null, callParams);
                } catch (e) {
                    this.log.warning("Error in '" + functionName + "' handler : " + e);
                }
            } else {
                this.log.warning("No handler for " + functionName);
            }
        }
        /**
         * Manually disconnect transport proto
         */

    }, {
        key: "disconnect",
        value: function disconnect() {
            this.closing = true;
            this.manualDisconnect = true;
            this.onWSClosed(null);
            this.cleanup();
        }
    }, {
        key: "onWSClosed",
        value: function onWSClosed(e) {
            if (this.currentState != VoxSignalingState.CONNECTED && this.currentState != VoxSignalingState.CONNECTING && this.currentState != VoxSignalingState.CLOSING) {
                if (!this.closing) this.log.warning("onWSClosed in state " + VoxSignalingState[this.currentState]);else return;
            }
            if (this.ws) {
                this.ws.close();
                this.ws = undefined;
            }
            var oldState = this.currentState;
            //unbind __ping and __pong timeouts
            if (this.pingTimer) {
                clearTimeout(this.pingTimer);
            }
            if (this.pongTimer) {
                clearTimeout(this.pongTimer);
            }
            this.cleanup();
            this.currentState = VoxSignalingState.IDLE;
            if (this.handlers.length > 0) {
                for (var i = 0; i < this.handlers.length; ++i) {
                    if ((oldState == VoxSignalingState.CONNECTING || oldState == VoxSignalingState.WSCONNECTED || oldState == VoxSignalingState.IDLE) && !this.manualDisconnect) {
                        try {
                            this.handlers[i].onSignalingConnectionFailed(e.reason);
                        } catch (e) {
                            this.log.warning("Error in onSignalingConnectionFailed callback: " + e);
                        }
                    } else {
                        try {
                            this.handlers[i].onSignalingClosed();
                        } catch (e) {
                            this.log.warning("Error in onSignalingClosed callback: " + e);
                        }
                    }
                }
            } else {
                this.log.warning("No VoxSignaling handler specified");
            }
        }
    }, {
        key: "onWSConnected",
        value: function onWSConnected() {
            var _this3 = this;

            this.closing = false;
            if (this.currentState != VoxSignalingState.CONNECTING) {
                this.log.warning("onWSConnected in state " + VoxSignalingState[this.currentState]);
            }
            this.currentState = VoxSignalingState.WSCONNECTED;
            this.pingTimer = window.setTimeout(function () {
                return _this3.doPing();
            }, VoxSignaling.PING_DELAY);
            //Set inner message handlers
            this.setRPCHandler(RemoteEvent_1.RemoteEvent.pong, function () {
                return _this3.pongReceived();
            });
            //Set deprecated message handlers
            this.setRPCHandler(RemoteEvent_1.RemoteEvent.increaseGain, function () {
                _this3.log.info("Deprecated increaseGain");
            });
        }
        /**
         * Event for error on main signaling socket
         */

    }, {
        key: "onWSError",
        value: function onWSError() {
            if (this.currentState != VoxSignalingState.CONNECTING) {
                this.log.warning("onWSError in state " + this.currentState);
            }
            this.ws.close();
            this.ws = undefined;
            //unbind __ping and __pong timeouts
            if (this.pingTimer) {
                clearTimeout(this.pingTimer);
            }
            if (this.pongTimer) {
                clearTimeout(this.pongTimer);
            }
            this.cleanup();
            this.currentState = VoxSignalingState.IDLE;
            if (typeof this.handlers != "undefined") {
                for (var i = 0; i < this.handlers.length; ++i) {
                    try {
                        this.handlers[i].onSignalingConnectionFailed("Error connecting to VoxImplant server");
                    } catch (e) {
                        this.log.warning("Error in onSignalingConnectionFailed callback: " + e);
                    }
                }
            } else {
                this.log.warning("No VoxSignaling handler specified");
            }
        }
        /**
         * Fx run every PING_TIMEOUT ms
         */

    }, {
        key: "doPing",
        value: function doPing() {
            var _this4 = this;

            this.pingTimer = null;
            this.callRemoteFunction(RemoteFunction_1.RemoteFunction.ping, []);
            this.pongTimer = window.setTimeout(function () {
                if (CallManager_1.CallManager.get().numCalls > 0) {
                    _this4.pongReceived();
                    return;
                }
                _this4.pongTimer = null;
                for (var i = 0; i < _this4.handlers.length; ++i) {
                    if (_this4.currentState == VoxSignalingState.CONNECTED) {
                        try {
                            _this4.handlers[i].onSignalingClosed();
                        } catch (e) {
                            _this4.log.warning("Error in onSignalingClosed callback: " + e);
                        }
                    } else {
                        try {
                            _this4.handlers[i].onSignalingConnectionFailed("Connection closed");
                        } catch (e) {
                            _this4.log.warning("Error in onSignalingConnectionFailed callback: " + e);
                        }
                    }
                }
                _this4.ws.close();
                _this4.currentState = VoxSignalingState.IDLE;
            }, VoxSignaling.PONG_DELAY);
        }
        /**
         * Reciver for pong
         * @see doPing()
         */

    }, {
        key: "pongReceived",
        value: function pongReceived() {
            var _this5 = this;

            if (this.pongTimer) {
                clearTimeout(this.pongTimer);
                this.pongTimer = null;
                this.pingTimer = window.setTimeout(function () {
                    return _this5.doPing();
                }, VoxSignaling.PING_DELAY);
            }
        }
        /**
         *
         * @param {MsgBusMessage} data
         * @returns {boolean}
         */

    }, {
        key: "sendRaw",
        value: function sendRaw(data) {
            if (this.writeLog) this._opLog.push("send:" + JSON.stringify(data));
            var xdata = JSON.stringify(data);
            this.ws.send(xdata);
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.SIGNALING, '[wsdataout]', Logger_1.LogLevel.INFO, xdata);
            return true;
        }
    }, {
        key: "getLog",
        value: function getLog() {
            return this._opLog;
        }
    }, {
        key: "lagacyConnectTo",
        value: function lagacyConnectTo(server, referrer, extra, appName) {
            this.ver = '2';
            this.platform = appName;
            this.referrer = referrer;
            this.connectTo(server, false, true, true, '2');
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'VoxSignaling';
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof this.inst == "undefined") {
                this.inst = new VoxSignaling();
            }
            return this.inst;
        }
    }]);

    return VoxSignaling;
}();
/**
 * Timeout for __ping and __pong method
 * @type {number}
 */


VoxSignaling.PING_DELAY = 10000;
VoxSignaling.PONG_DELAY = 10000;
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "addHandler", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "close", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "cleanup", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "onConnectionSuccessfulRPC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "onConnectionFailedRPC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "connectTo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "setRPCHandler", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "removeRPCHandler", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "callRemoteFunction", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "disconnect", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "onWSClosed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "onWSConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "onWSError", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.SIGNALING)], VoxSignaling.prototype, "sendRaw", null);
exports.VoxSignaling = VoxSignaling;

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Enum for callRemoteFunction
 *
 * @author Igor Sheko
 * @hidden
 */
var RemoteFunction;
(function (RemoteFunction) {
    RemoteFunction[RemoteFunction["ping"] = "__ping"] = "ping";
    RemoteFunction[RemoteFunction["login"] = "login"] = "login";
    RemoteFunction[RemoteFunction["loginGenerateOneTimeKey"] = "loginGenerateOneTimeKey"] = "loginGenerateOneTimeKey";
    RemoteFunction[RemoteFunction["loginStage2"] = "loginStage2"] = "loginStage2";
    RemoteFunction[RemoteFunction["setOperatorACDStatus"] = "setOperatorACDStatus"] = "setOperatorACDStatus";
    RemoteFunction[RemoteFunction["getOperatorACDStatus"] = "getOperatorACDStatus"] = "getOperatorACDStatus";
    RemoteFunction[RemoteFunction["setDesiredVideoBandwidth"] = "setDesiredVideoBandwidth"] = "setDesiredVideoBandwidth";
    RemoteFunction[RemoteFunction["rejectCall"] = "rejectCall"] = "rejectCall";
    RemoteFunction[RemoteFunction["disconnectCall"] = "disconnectCall"] = "disconnectCall";
    RemoteFunction[RemoteFunction["sendDTMF"] = "sendDTMF"] = "sendDTMF";
    RemoteFunction[RemoteFunction["sendSIPInfo"] = "sendSIPInfo"] = "sendSIPInfo";
    RemoteFunction[RemoteFunction["hold"] = "hold"] = "hold";
    RemoteFunction[RemoteFunction["unhold"] = "unhold"] = "unhold";
    RemoteFunction[RemoteFunction["acceptCall"] = "acceptCall"] = "acceptCall";
    RemoteFunction[RemoteFunction["createCall"] = "createCall"] = "createCall";
    RemoteFunction[RemoteFunction["callConference"] = "callConference"] = "callConference";
    RemoteFunction[RemoteFunction["transferCall"] = "transferCall"] = "transferCall";
    RemoteFunction[RemoteFunction["muteLocal"] = "__muteLocal"] = "muteLocal";
    RemoteFunction[RemoteFunction["reInvite"] = "ReInvite"] = "reInvite";
    RemoteFunction[RemoteFunction["acceptReInvite"] = "AcceptReInvite"] = "acceptReInvite";
    RemoteFunction[RemoteFunction["rejectReInvite"] = "RejectReInvite"] = "rejectReInvite";
    RemoteFunction[RemoteFunction["confirmPC"] = "__confirmPC"] = "confirmPC";
    RemoteFunction[RemoteFunction["addCandidate"] = "__addCandidate"] = "addCandidate";
    RemoteFunction[RemoteFunction["loginUsingOneTimeKey"] = "loginUsingOneTimeKey"] = "loginUsingOneTimeKey";
    RemoteFunction[RemoteFunction["refreshOauthToken"] = "refreshOauthToken"] = "refreshOauthToken";
    //    =========================Legacy ZAPI
    RemoteFunction[RemoteFunction["zPromptFinished"] = "promptFinished"] = "zPromptFinished";
    RemoteFunction[RemoteFunction["zStartPreFlightCheck"] = "__startPreFlightCheck"] = "zStartPreFlightCheck";
    //    =========================Legacy ZAPI
    //    =========================Push service
    RemoteFunction[RemoteFunction["registerPushToken"] = "registerPushToken"] = "registerPushToken";
    RemoteFunction[RemoteFunction["unregisterPushToken"] = "unregisterPushToken"] = "unregisterPushToken";
    RemoteFunction[RemoteFunction["pushFeedback"] = "pushFeedback"] = "pushFeedback";
    //    =========================Push service
})(RemoteFunction = exports.RemoteFunction || (exports.RemoteFunction = {}));

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

Object.defineProperty(exports, "__esModule", { value: true });
var Implement = __webpack_require__(44);
var Hardware;
(function (Hardware) {
  /**
   * Events that are triggered when hardware device is added/removed/updated.
   */
  var HardwareEvents = void 0;
  (function (HardwareEvents) {
    /**
     * Event is triggered each time when device is added/removed. Devices that trigger the event: microphones, video camera and sound output (available only in Google Chrome).
     */
    HardwareEvents[HardwareEvents["DevicesUpdated"] = 'DevicesUpdated'] = "DevicesUpdated";
    /**
     * Event is triggered when local video or audio is started. E.g. when local video or screen sharing is stared.
     */
    HardwareEvents[HardwareEvents["MediaRendererAdded"] = 'MediaRendererAdded'] = "MediaRendererAdded";
    /**
     * Event is triggered when local video or audio streaming is stopped. E.g. when local video or screen sharing streaming is stopped.
     */
    HardwareEvents[HardwareEvents["MediaRendererRemoved"] = 'MediaRendererRemoved'] = "MediaRendererRemoved";
    /**
     * Event is triggered before local video or audio streaming is stopped. E.g. before local video or screen sharing streaming is stopped.
     */
    HardwareEvents[HardwareEvents["BeforeMediaRendererRemoved"] = 'BeforeMediaRendererRemoved'] = "BeforeMediaRendererRemoved";
  })(HardwareEvents = Hardware.HardwareEvents || (Hardware.HardwareEvents = {}));
  /**
   * Enum that represents video quality.
   */
  var VideoQuality = void 0;
  (function (VideoQuality) {
    /**
     * Set better video quality for the current web camera.
     * This option uses the last value from the [CameraManager.testResolutions] function result,
     * or the data set to the [CameraManager.loadResolutionTestResult].
     * If there is no result for a target web camera, use 1280x720 resolution
     */
    VideoQuality[VideoQuality["VIDEO_QUALITY_HIGH"] = 'video_quality_high'] = "VIDEO_QUALITY_HIGH";
    /**
     * Set medium video quality for the current web camera.
     * This option uses the last value from the [CameraManager.testResolutions] function result,
     * or the data set to the [CameraManager.loadResolutionTestResult].
     * If there is no result for a target web camera, use 640x480 resolution
     */
    VideoQuality[VideoQuality["VIDEO_QUALITY_LOW"] = 'video_quality_low'] = "VIDEO_QUALITY_LOW";
    /**
     * Set lower video quality for the current web camera.
     * This option uses the last value from the [CameraManager.testResolutions] function result,
     * or the data set to the [CameraManager.loadResolutionTestResult].
     * If there is no result for a target web camera, use 320x240 resolution
     */
    VideoQuality[VideoQuality["VIDEO_QUALITY_MEDIUM"] = 'video_quality_medium'] = "VIDEO_QUALITY_MEDIUM";
    /**
     * 160x120 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_QQVGA"] = 'video_size_qqvga'] = "VIDEO_SIZE_QQVGA";
    /**
     * 176x144 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_QCIF"] = 'video_size_qcif'] = "VIDEO_SIZE_QCIF";
    /**
     * 320x240 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_QVGA"] = 'video_size_qvga'] = "VIDEO_SIZE_QVGA";
    /**
     * 352x288 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_CIF"] = 'video_size_cif'] = "VIDEO_SIZE_CIF";
    /**
     * 640x360 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_nHD"] = 'video_size_nhd'] = "VIDEO_SIZE_nHD";
    /**
     * 640x480 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_VGA"] = 'video_size_vga'] = "VIDEO_SIZE_VGA";
    /**
     * 800x600 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_SVGA"] = 'video_size_svga'] = "VIDEO_SIZE_SVGA";
    /**
     * 1280x720 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_HD"] = 'video_size_hd'] = "VIDEO_SIZE_HD";
    /**
     * 1600x1200 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_UXGA"] = 'video_size_uxga'] = "VIDEO_SIZE_UXGA";
    /**
     * 1920x1080 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_FHD"] = 'video_size_fhd'] = "VIDEO_SIZE_FHD";
    /**
     * 3840x2160 resolution
     */
    VideoQuality[VideoQuality["VIDEO_SIZE_UHD"] = 'video_size_uhd'] = "VIDEO_SIZE_UHD";
  })(VideoQuality = Hardware.VideoQuality || (Hardware.VideoQuality = {}));
  /**
   * Interface that may be used to manage audio devices, i.e. see current active device, select another active device and get the list of available devices.
   */

  var AudioDeviceManager = function (_Implement$AudioDevic) {
    _inherits(AudioDeviceManager, _Implement$AudioDevic);

    function AudioDeviceManager() {
      _classCallCheck(this, AudioDeviceManager);

      return _possibleConstructorReturn(this, (AudioDeviceManager.__proto__ || Object.getPrototypeOf(AudioDeviceManager)).apply(this, arguments));
    }

    return AudioDeviceManager;
  }(Implement.AudioDeviceManager);

  Hardware.AudioDeviceManager = AudioDeviceManager;
  ;
  ;
  /**
   * Interface that may be used to manage cameras on Android device.
   */

  var CameraManager = function (_Implement$CameraMana) {
    _inherits(CameraManager, _Implement$CameraMana);

    function CameraManager() {
      _classCallCheck(this, CameraManager);

      return _possibleConstructorReturn(this, (CameraManager.__proto__ || Object.getPrototypeOf(CameraManager)).apply(this, arguments));
    }

    return CameraManager;
  }(Implement.CameraManager);

  Hardware.CameraManager = CameraManager;
  ;
  ;
  ;
  /**
   * Interface for extended management of local audio/video streams.
   */

  var StreamManager = function (_Implement$StreamMana) {
    _inherits(StreamManager, _Implement$StreamMana);

    function StreamManager() {
      _classCallCheck(this, StreamManager);

      return _possibleConstructorReturn(this, (StreamManager.__proto__ || Object.getPrototypeOf(StreamManager)).apply(this, arguments));
    }

    return StreamManager;
  }(Implement.StreamManager);

  Hardware.StreamManager = StreamManager;
  ;
  /**
   * @hidden
   */

  var IOSCacheManager = function (_Implement$IOSCacheMa) {
    _inherits(IOSCacheManager, _Implement$IOSCacheMa);

    function IOSCacheManager() {
      _classCallCheck(this, IOSCacheManager);

      return _possibleConstructorReturn(this, (IOSCacheManager.__proto__ || Object.getPrototypeOf(IOSCacheManager)).apply(this, arguments));
    }

    return IOSCacheManager;
  }(Implement.IOSCacheManager);

  Hardware.IOSCacheManager = IOSCacheManager;
  ;
})(Hardware = exports.Hardware || (exports.Hardware = {}));
exports.default = Hardware;

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Call_1 = __webpack_require__(13);
var CallEvents_1 = __webpack_require__(6);
var VoxSignaling_1 = __webpack_require__(2);
var Utils_1 = __webpack_require__(23);
var Authenticator_1 = __webpack_require__(10);
var Constants_1 = __webpack_require__(11);
var Logger_1 = __webpack_require__(0);
var PCFactory_1 = __webpack_require__(9);
var Client_1 = __webpack_require__(1);
var PeerConnection_1 = __webpack_require__(24);
var CallExServer_1 = __webpack_require__(48);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var CallExMedia_1 = __webpack_require__(49);
var CallstatsIo_1 = __webpack_require__(21);
var CodecSorterHelpers_1 = __webpack_require__(50);
var SDPMuggle_1 = __webpack_require__(20);
var EndpointManager_1 = __webpack_require__(26);
var Hardware_1 = __webpack_require__(4);
/**
 * Implenets signaling protocol and local call management'
 * Singleton
 * All call manipulation MUST be there
 * @hidden
 */

var CallManager = function () {
    function CallManager() {
        var _this = this;

        _classCallCheck(this, CallManager);

        this.protocolVersion = '3';
        this._h264first = false;
        this._calls = {};
        this.voxSignaling = VoxSignaling_1.VoxSignaling.get();
        this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.SIGNALING, 'CallManager');
        this.voxSignaling.addHandler(this);
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleIncomingConnection, function (id, callerid, displayName, headers, sdp) {
            _this.handleIncomingConnection(id, callerid, displayName, headers, sdp);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleConnectionConnected, function (id, headers, sdp, endPointData) {
            _this.handleConnectionConnected(id, headers, sdp);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleConnectionDisconnected, function (id, headers, params) {
            _this.handleConnectionDisconnected(id, headers, params);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleRingOut, function (id) {
            _this.handleRingOut(id);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.stopRinging, function (id) {
            _this.stopRinging(id);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleConnectionFailed, function (id, code, reason, headers) {
            _this.handleConnectionFailed(id, code, reason, headers);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleSIPInfo, function (callId, type, subType, body, headers) {
            _this.handleSIPInfo(callId, type, subType, body, headers);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleSipEvent, function (callId) {
            _this.handleSipEvent(callId);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleTransferStarted, function (callId) {
            _this.handleTransferStarted(callId);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleTransferComplete, function (callId) {
            _this.handleTransferComplete(callId);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleTransferFailed, function (callId) {
            _this.handleTransferFailed(callId);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleReInvite, function (callid, headers, sdp, schemeString) {
            var scheme = JSON.parse(schemeString);
            _this.handleInReinvite(callid, headers, sdp, scheme);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleAcceptReinvite, function (callid, headers, sdp) {
            _this.handleReinvite(callid, headers, sdp);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.handleRejectReinvite, function (callid, headers, sdp) {
            _this.handleRejectReinvite(callid, headers, sdp);
        });
        this.voxSignaling.setRPCHandler(RemoteEvent_1.RemoteEvent.startEarlyMedia, function (id, headers, sdp) {
            _this.startEarlyMedia(id, headers, sdp);
        });
    }

    _createClass(CallManager, [{
        key: "call",

        /**
         * Place an outgoing call
         * @param {string} number Number to place call
         * @param {object} headers Additional headers
         * @param {boolean} video Initial state of video - enabled/disabled
         * @param {object} extraParams DEPRECATED
         */
        value: function call(sets) {
            var _this2 = this;

            var defaults = {
                number: null,
                video: { sendVideo: false, receiveVideo: false },
                customData: null,
                extraHeaders: {},
                wiredLocal: true,
                wiredRemote: true,
                H264first: this._h264first,
                VP8first: false,
                forceActive: false,
                extraParams: {}
            };
            //here will media pain
            var settings = Utils_1.Utils.mixObjectToLeft(defaults, sets);
            settings = CallManager.addCustomDataToHeaders(settings);
            var id = Utils_1.Utils.generateUUID();
            if (this._calls[id]) {
                this.log.error('Call ' + id + ' already exists');
                throw new Error('Internal error');
            }
            var call = this.getCallInstance(id, Authenticator_1.Authenticator.get().displayName, false, settings);
            if (CallstatsIo_1.CallstatsIo.isModuleEnabled()) {
                settings.extraHeaders[Constants_1.Constants.CALLSTATSIOID_HEADER] = id;
            }
            if (settings.VP8first) call.rearangeCodecs = CodecSorterHelpers_1.CodecSorterHelpers.VP8Sorter;
            if (settings.H264first) call.rearangeCodecs = CodecSorterHelpers_1.CodecSorterHelpers.H264Sorter;
            //
            var pcHold = false;
            call.settings.active = true;
            if (Object.keys(this._calls).length > 1 && !settings.forceActive) {
                call.setActiveForce(false);
                pcHold = true;
            }
            if (typeof settings.extraHeaders[Constants_1.Constants.DIRECT_CALL_HEADER] === 'undefined' && this.protocolVersion == '2') {
                this.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.createCall, -1, settings.number, settings.video, id, null, null, settings.extraHeaders, settings.extraParams);
            } else {
                PCFactory_1.PCFactory.get().setupDirectPC(id, PeerConnection_1.PeerConnectionMode.P2P, sets.video, pcHold).then(function (sdpOffer) {
                    call.peerConnection = PCFactory_1.PCFactory.get().peerConnections[id];
                    var extra = { tracks: call.peerConnection.getTrackKind() };
                    _this2.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.createCall, -1, settings.number, true, id, null, null, settings.extraHeaders, '', sdpOffer.sdp, extra);
                }).catch(function (e) {
                    _this2.handleConnectionFailed(call.id(), 403, 'Media access denied', {});
                });
            }
            call.sendVideo(settings.video.sendVideo);
            return call;
        }
    }, {
        key: "callConference",
        value: function callConference(sets) {
            var _this3 = this;

            var defaults = {
                number: null,
                video: { sendVideo: false, receiveVideo: false },
                customData: null,
                extraHeaders: {},
                wiredLocal: true,
                wiredRemote: true,
                H264first: this._h264first,
                VP8first: false,
                forceActive: false,
                extraParams: {}
            };
            //here will media pain
            var settings = Utils_1.Utils.mixObjectToLeft(defaults, sets);
            settings = CallManager.addCustomDataToHeaders(settings);
            var id = Utils_1.Utils.generateUUID();
            if (this._calls[id]) {
                this.log.error('Call ' + id + ' already exists');
                throw new Error('Internal error');
            }
            // console.error(settings);
            var call = this.getCallInstance(id, Authenticator_1.Authenticator.get().displayName, false, settings);
            if (CallstatsIo_1.CallstatsIo.isModuleEnabled()) {
                settings.extraHeaders[Constants_1.Constants.CALLSTATSIOID_HEADER] = id;
            }
            if (settings.VP8first) call.rearangeCodecs = CodecSorterHelpers_1.CodecSorterHelpers.VP8Sorter;
            if (settings.H264first) call.rearangeCodecs = CodecSorterHelpers_1.CodecSorterHelpers.H264Sorter;
            //
            var pcHold = false;
            call.settings.active = true;
            call.settings.isConference = true;
            if (Object.keys(this._calls).length > 1 && !settings.forceActive) {
                call.setActiveForce(false);
                pcHold = true;
            }
            PCFactory_1.PCFactory.get().setupDirectPC(id, PeerConnection_1.PeerConnectionMode.CONFERENCE, sets.video, pcHold).then(function (sdpOffer) {
                call.peerConnection = PCFactory_1.PCFactory.get().peerConnections[id];
                var extra = { tracks: call.peerConnection.getTrackKind() };
                _this3.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.callConference, -1, settings.number, true, id, null, null, settings.extraHeaders, '', sdpOffer.sdp, extra);
            }).catch(function (e) {
                _this3.handleConnectionFailed(call.id(), 403, 'Media access denied', {});
            });
            call.sendVideo(settings.video.sendVideo);
            return call;
        }
        /**
         * Check if sdp have video section with send flow
         * @param sdp
         * @returns {boolean}
         */

    }, {
        key: "isSDPHasVideo",
        value: function isSDPHasVideo(sdp) {
            var videoPos = sdp.indexOf('m=video');
            if (videoPos === -1) return false;
            var sendresvPos = sdp.indexOf('a=sendrecv', videoPos);
            var sendonlyPos = sdp.indexOf('a=sendonly', videoPos);
            var nextM = sdp.indexOf('m=', videoPos);
            if (sendresvPos !== -1 && (sendresvPos < nextM || nextM === -1) || sendonlyPos !== -1 && (sendonlyPos < nextM || nextM === -1)) return true;
            return false;
        }
    }, {
        key: "handleConnectionFailed",
        value: function handleConnectionFailed(id, code, reason, headers) {
            var c = this.findCall(id, 'handleConnectionFailed');
            if (typeof c == 'undefined') return;
            delete this._calls[id];
            Client_1.Client.getInstance().stopProgressTone();
            c.onFailed(code, reason, headers);
        }
    }, {
        key: "onSignalingConnected",
        value: function onSignalingConnected() {}
    }, {
        key: "onSignalingClosed",
        value: function onSignalingClosed() {
            for (var i in this._calls) {
                if (this._calls.hasOwnProperty(i)) {
                    this._calls[i].hangup();
                    this._calls[i].onFailed(409, 'Connection Closed', {});
                }
            }
        }
    }, {
        key: "onSignalingConnectionFailed",
        value: function onSignalingConnectionFailed(errorMessage) {}
    }, {
        key: "onMediaConnectionFailed",
        value: function onMediaConnectionFailed() {}
    }, {
        key: "transferCall",
        value: function transferCall(call1, call2) {
            var x = [call1, call2];
            for (var i = 0; i < x.length; i++) {
                var call = this._calls[x[i].id()];
                if (call) {
                    if (call.stateValue != Call_1.CallState.CONNECTED) {
                        this.log.error('trying to transfer call ' + call.id() + ' in state ' + call.state());
                        return;
                    }
                } else {
                    this.log.error('trying to transfer unknown call ' + call.id());
                    return;
                }
            }
            this.voxSignaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.transferCall, call1.id(), call2.id());
        }
        /**
         * Fx for backward compatibility with hidden Fx Client.removeCall
         * @param call_id
         */

    }, {
        key: "removeCall",
        value: function removeCall(call_id) {
            delete this._calls[call_id];
        }
    }, {
        key: "setProtocolVersion",
        value: function setProtocolVersion(ver) {
            this.protocolVersion = ver;
        }
    }, {
        key: "setAllCallsVolume",
        value: function setAllCallsVolume(level) {
            for (var callId in this._calls) {
                if (this._calls.hasOwnProperty(callId)) {
                    EndpointManager_1.EndpointManager.get().setCallVolume(this._calls[callId], level);
                }
            }
        }
    }, {
        key: "useVideoSource",
        value: function useVideoSource(id) {
            var _this4 = this;

            var callMax = Object.keys(this._calls).length;
            return new Promise(function (resolve, reject) {
                for (var callId in _this4._calls) {
                    if (_this4._calls.hasOwnProperty(callId)) {
                        var call = _this4._calls[callId];
                        Hardware_1.default.CameraManager.get().setCallVideoSettings(call, Object.assign({}, Hardware_1.default.CameraManager.get().getCallVideoSettings(call), { cameraId: id }));
                        Hardware_1.default.StreamManager.get().updateCallStream(call).then(function (stream) {
                            callMax--;
                            if (callMax <= 0) resolve();
                        }, function (e) {
                            reject(e);
                        });
                    }
                }
            });
        }
    }, {
        key: "setVideoSettings",
        value: function setVideoSettings(settings) {
            var _this5 = this;

            var callMax = Object.keys(this._calls).length;
            return new Promise(function (resolve, reject) {
                if (callMax === 0) {
                    resolve();
                }
                for (var callId in _this5._calls) {
                    if (_this5._calls.hasOwnProperty(callId)) {
                        var call = _this5._calls[callId];
                        Hardware_1.default.CameraManager.get().setCallVideoSettings(call, Hardware_1.default.CameraManager.legacyParamConverter(settings)).then(function (stream) {
                            callMax--;
                            if (callMax <= 0) resolve();
                        }, function (e) {
                            reject(e);
                        });
                    }
                }
            });
        }
    }, {
        key: "useAudioSource",
        value: function useAudioSource(id) {
            var _this6 = this;

            var callMax = Object.keys(this._calls).length;
            return new Promise(function (resolve, reject) {
                if (callMax === 0) resolve();
                for (var callId in _this6._calls) {
                    if (_this6._calls.hasOwnProperty(callId)) {
                        var call = _this6._calls[callId];
                        Hardware_1.default.AudioDeviceManager.get().setCallAudioSettings(call, Object.assign({}, Hardware_1.default.AudioDeviceManager.get().getCallAudioSettings(call), { inputId: id })).then(function (stream) {
                            callMax--;
                            if (callMax <= 0) resolve();
                        }, function (e) {
                            reject(e);
                        });
                    }
                }
            });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'CallManager';
        }
        /**
         * Handle incoming call
         * @hidden
         * @param id
         * @param callerid
         * @param displayName
         * @param headers
         * @param sdp
         */

    }, {
        key: "handleIncomingConnection",
        value: function handleIncomingConnection(id, callerid, displayName, headers, sdp) {
            var _this7 = this;

            if (this._calls[id]) {
                this.log.error('Call ' + id + ' already exists');
                throw new Error('Internal error');
            }
            var remoteDirections = SDPMuggle_1.SDPMuggle.detectDirections(sdp);
            var hasVideo = remoteDirections.some(function (i) {
                return i.type === 'video' && (i.direction === 'sendonly' || i.direction === 'sendrecv');
            });
            var settings = {
                number: callerid,
                extraHeaders: headers,
                video: hasVideo,
                wiredLocal: true,
                wiredRemote: true,
                forceActive: false
            };
            var call = this.getCallInstance(id, displayName, true, settings);
            if (this._h264first) call.rearangeCodecs = CodecSorterHelpers_1.CodecSorterHelpers.H264Sorter;
            var pcHold = false;
            call.settings.active = true;
            if (Object.keys(this._calls).length > 1) {
                call.setActiveForce(false);
                pcHold = true;
            }
            if (typeof settings.extraHeaders[Constants_1.Constants.DIRECT_CALL_HEADER] === 'undefined' && this.protocolVersion == '2') {
                call.peerConnection = PCFactory_1.PCFactory.get().getPeerConnect(id);
                Client_1.Client.getInstance().onIncomingCall(id, callerid, displayName, headers, this.isSDPHasVideo(sdp));
            } else {
                PCFactory_1.PCFactory.get().incomeDirectPC(id, { receiveVideo: true, sendVideo: true }, sdp, pcHold).then(function (pc) {
                    call.peerConnection = pc;
                    Client_1.Client.getInstance().onIncomingCall(id, callerid, displayName, headers, _this7.isSDPHasVideo(sdp));
                });
            }
        }
    }, {
        key: "getCallInstance",
        value: function getCallInstance(id, displayName, direction, settings) {
            var call = void 0;
            if (this.protocolVersion == '3') {
                call = new CallExMedia_1.CallExMedia(id, displayName, direction, settings);
            } else if (typeof settings.extraHeaders[Constants_1.Constants.DIRECT_CALL_HEADER] != 'undefined') call = new CallExMedia_1.CallExMedia(id, displayName, direction, settings);else call = new CallExServer_1.CallExServer(id, displayName, direction, settings);
            this._calls[id] = call;
            EndpointManager_1.EndpointManager.get().registerCall(call);
            return call;
        }
    }, {
        key: "findCall",
        value: function findCall(id, functionName) {
            var c = this._calls[id];
            if (id === '') c = this._calls[Object.keys(this._calls)[0]];
            if (typeof c == 'undefined') {
                this.log.warning('Received ' + functionName + ' for unknown call ' + id);
                return null;
            }
            return c;
        }
    }, {
        key: "handleRingOut",
        value: function handleRingOut(id) {
            var c = this.findCall(id, 'handleRingOut');
            if (typeof c == 'undefined') return;
            Client_1.Client.getInstance().playProgressTone(true);
            c.onRingOut();
            c.canStartSendingCandidates();
        }
    }, {
        key: "handleConnectionConnected",
        value: function handleConnectionConnected(id, headers, sdp) {
            var c = this.findCall(id, 'handleConnectionConnected');
            c.signalingConnected = true;
            c.canStartSendingCandidates();
            if (typeof c == 'undefined') {
                return;
            }
            c.onConnected(headers, sdp);
            if (typeof sdp != 'undefined' && sdp.length > 0) {
                //TODO:REMOVE THIS AFTER IVAN PATCH!!!
                var videoPos = sdp.indexOf('m=video');
                if (videoPos !== -1) {
                    var sendresvPos = sdp.indexOf('a=sendrecv', videoPos);
                    var sendonlyPos = sdp.indexOf('a=sendonly', videoPos);
                    var recvonlyPos = sdp.indexOf('a=recvonly', videoPos);
                    var inactivePos = sdp.indexOf('a=inactive', videoPos);
                    if (sendresvPos === -1 && sendonlyPos === -1 && recvonlyPos === -1 && inactivePos === -1) sdp += 'a=inactive\r\n';
                }
                //ENDTODO
                c.peerConnection.processRemoteAnswer(headers, sdp);
            }
        }
    }, {
        key: "startEarlyMedia",
        value: function startEarlyMedia(id, headers, sdp) {
            var c = this.findCall(id, 'startEarlyMedia');
            c.settings.hasEarlyMedia = true;
            if (typeof sdp != 'undefined') {
                c.peerConnection.processRemoteAnswer(headers, sdp);
            }
            Client_1.Client.getInstance().stopProgressTone();
        }
    }, {
        key: "handleConnectionDisconnected",
        value: function handleConnectionDisconnected(id, headers, params) {
            var _this8 = this;

            var c = this.findCall(id, 'handleConnectionDisconnected');
            if (!c) return;
            Client_1.Client.getInstance().stopProgressTone();
            c.onDisconnected(headers, params).then(function () {
                delete _this8._calls[id];
            }).catch(function (e) {
                _this8.log.error("Can't remove the call " + id + ": " + e.message);
            });
        }
    }, {
        key: "handleSIPInfo",
        value: function handleSIPInfo(callId, type, subType, body, headers) {
            var c = this.findCall(callId, 'handleSIPInfo');
            if (typeof c == 'undefined') return;
            c.onInfo(c, type, subType, body, headers);
        }
    }, {
        key: "stopRinging",
        value: function stopRinging(id) {
            var c = this.findCall(id, 'stopRinging');
            c.canStartSendingCandidates();
            if (typeof c == 'undefined') return;
            Client_1.Client.getInstance().stopProgressTone();
            c.onStopRinging();
        }
    }, {
        key: "handleSipEvent",
        value: function handleSipEvent(id) {}
    }, {
        key: "handleTransferStarted",
        value: function handleTransferStarted(id) {}
    }, {
        key: "handleTransferComplete",
        value: function handleTransferComplete(id) {
            var c = this.findCall(id, 'handleTransferComplete');
            if (typeof c == 'undefined') return;
            c.onTransferComplete();
        }
    }, {
        key: "handleTransferFailed",
        value: function handleTransferFailed(id) {
            var c = this.findCall(id, 'handleTransferFailed');
            if (typeof c == 'undefined') return;
            c.onTransferFailed();
        }
    }, {
        key: "handleReinvite",
        value: function handleReinvite(id, headers, sdp) {
            var c = this.findCall(id, 'handleReinvite');
            if (typeof c == 'undefined') return;
            var hasVideo = this.isSDPHasVideo(sdp);
            c.peerConnection.handleReinvite(headers, sdp, hasVideo);
        }
    }, {
        key: "handleRejectReinvite",
        value: function handleRejectReinvite(id, headers, sdp) {
            var c = this.findCall(id, 'handleReinvite');
            if (typeof c == 'undefined') return;
            c.dispatchEvent({ code: 20, call: c });
        }
    }, {
        key: "handleInReinvite",
        value: function handleInReinvite(id, headers, sdp, scheme) {
            var c = this.findCall(id, 'handleReinvite');
            if (typeof c == 'undefined') return;
            EndpointManager_1.EndpointManager.get().setEndpointDescription(c, scheme);
            c.runIncomingReInvite(headers, sdp);
            c.dispatchEvent({ name: CallEvents_1.CallEvents.PendingUpdate, result: true, call: c });
        }
        // Recalculate active call count

    }, {
        key: "recalculateNumCalls",
        value: function recalculateNumCalls() {
            this._numCalls = 0;
            for (var i in this._calls) {
                if (this._calls.hasOwnProperty(i)) {
                    this._numCalls++;
                }
            }
        }
    }, {
        key: "calls",
        get: function get() {
            return this._calls;
        }
        /**
         * Get active call count
         * @hidden
         * @returns {number}
         */

    }, {
        key: "numCalls",
        get: function get() {
            return this._numCalls;
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof this.inst == 'undefined') {
                this.inst = new CallManager();
            }
            return this.inst;
        }
        /**
         * Remove all non X- headers
         * @param headers
         * @returns {{}}
         */

    }, {
        key: "cleanHeaders",
        value: function cleanHeaders(headers) {
            var res = {};
            for (var key in headers) {
                if (key.substring(0, 2) == 'X-' || key == Constants_1.Constants.CALL_DATA_HEADER) {
                    res[key] = headers[key];
                }
            }
            return res;
        }
    }, {
        key: "addCustomDataToHeaders",

        /**
         * snipet to process customData into CallSettings
         * @param settings
         * @returns {CallSettings}
         */
        value: function addCustomDataToHeaders(settings) {
            if (typeof settings.customData != 'undefined') {
                if (typeof settings.extraHeaders == 'undefined') settings.extraHeaders = {};
                settings.extraHeaders['VI-CallData'] = settings.customData;
            }
            return settings;
        }
    }]);

    return CallManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)
// call(num: string, headers: { [id: string]: string } = {}, video: boolean = false, extraParams: { [id: string]: string } = {}): Call {
], CallManager.prototype, "call", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "onSignalingConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "onSignalingClosed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "onSignalingConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "removeCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "setAllCallsVolume", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "useVideoSource", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "setVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "useAudioSource", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleIncomingConnection", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "findCall", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleRingOut", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleConnectionConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleConnectionDisconnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLEXSERVER)], CallManager.prototype, "handleSIPInfo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "stopRinging", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleSipEvent", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleTransferStarted", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleTransferComplete", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleTransferFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleReinvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleRejectReinvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager.prototype, "handleInReinvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLMANAGER)], CallManager, "addCustomDataToHeaders", null);
exports.CallManager = CallManager;

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * The events that are triggered by [Call] instance.
 *
 * Use [Call.on] to subscribe on
 * any of these events.
 *
 *
 * Example:
 * ``` js
 * var currentCall = vox.call("exampleUser");
 * currentCall.on(VoxImplant.CallEvents.Connected,onConnected);
 * currentCall.on(VoxImplant.CallEvents.Disconnected,onDisconnected);
 * currentCall.on(VoxImplant.CallEvents.Failed,onFailed);
 * currentCall.on(VoxImplant.CallEvents.ICETimeout,onICETimeout)
 * ```
 */
var CallEvents;
(function (CallEvents) {
  /**
   * Event is triggered when a reliable connection is established for the call. Depending on network conditions there can be a 2-3 seconds delay between first audio data and this event.
   * Handler function receives [EventHandlers.CallEventWithHeaders] object as an argument.
   */
  CallEvents[CallEvents["Connected"] = "Connected"] = "Connected";
  /**
   *  Event is triggered when a call was disconnected
   *  Handler function receives the [EventHandlers.Disconnected] object as an argument.
   */
  CallEvents[CallEvents["Disconnected"] = "Disconnected"] = "Disconnected";
  /**
   *  Event is triggered due to a call failure
   *
   *  Most frequent status codes:
   *
   * |Code|Description                      |
   * |----|---------------------------------|
   * |486 |Destination number is busy       |
   * |487 |Request terminated               |
   * |603 |Call was rejected                |
   * |404 |Invalid number                   |
   * |480 |Destination number is unavailable|
   * |402 |Insufficient funds               |
   *
   * Handler function receives the [EventHandlers.Failed] object as an argument.
   */
  CallEvents[CallEvents["Failed"] = "Failed"] = "Failed";
  /**
   *  Event is triggered when a progress tone playback starts.
   *  Handler function receives the [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["ProgressToneStart"] = "ProgressToneStart"] = "ProgressToneStart";
  /**
   *  Event is triggered when a progress tone playback stops.
   *  Handler function receives the [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["ProgressToneStop"] = "ProgressToneStop"] = "ProgressToneStop";
  /**
   *  Event is triggered when a text message is received.
   *  Handler function receives the [EventHandlers.MessageReceived] object as an argument.
   */
  CallEvents[CallEvents["MessageReceived"] = "onSendMessage"] = "MessageReceived";
  /**
   *  Event is triggered when the INFO message is received
   *  Handler function receives [EventHandlers.InfoReceived] object as an argument.
   */
  CallEvents[CallEvents["InfoReceived"] = "InfoReceived"] = "InfoReceived";
  /**
   *  Event is triggered when a call has been transferred successfully.
   *  Handler function receives the [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["TransferComplete"] = "TransferComplete"] = "TransferComplete";
  /**
   *  Event is triggered when a call transfer failed
   *  Handler function receives the [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["TransferFailed"] = "TransferFailed"] = "TransferFailed";
  /**
   *  Event is triggered when connection was not established due to a network connection problem between 2 peers
   *  Handler function receives [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["ICETimeout"] = "ICETimeout"] = "ICETimeout";
  CallEvents[CallEvents["RTCStatsReceived"] = "RTCStatsReceived"] = "RTCStatsReceived";
  /**
   * Event is triggered when a new HTMLMediaElement for the call's media playback has been created
   * Handler function receives [EventHandlers.MediaElementCreated] object as an argument.
   * @hidden
   * @deprecated
   */
  CallEvents[CallEvents["MediaElementCreated"] = "MediaElementCreated"] = "MediaElementCreated";
  /**
   * @hidden
   * @deprecated
   * @type {string}
   */
  CallEvents[CallEvents["MediaElementRemoved"] = "MediaElementRemoved"] = "MediaElementRemoved";
  // VideoPlaybackStarted =<any>"VideoPlaybackStarted",
  /**
   *  Event is triggered when an ICE connection is complete
   *  Handler function receives [EventHandlers.CallEvent] object as an argument.
   */
  CallEvents[CallEvents["ICECompleted"] = "ICECompleted"] = "ICECompleted";
  /**
   * Event is triggered when a call was updated. For example, video was added/removed.
   * Handler function receives the [EventHandlers.Updated] object as an argument.
   */
  CallEvents[CallEvents["Updated"] = "Updated"] = "Updated";
  /**
   * Event is triggered when user receives the call update from another side. For example, a video was added/removed on the remote side.
   * Handler function receives [EventHandlers.CallEvent] object as an argument.
   * @hidden
   * @deprecated
   */
  CallEvents[CallEvents["PendingUpdate"] = "PendingUpdate"] = "PendingUpdate";
  /**
   * Event is triggered when multiple participants tried to update the same call simultaneously. For example, video added/removed on a local and remote side at the same time.
   * Handler function receives [EventHandlers.UpdateFailed] object as an argument.
   * @hidden
   * @deprecated
   */
  CallEvents[CallEvents["UpdateFailed"] = "UpdateFailed"] = "UpdateFailed";
  /**
   * Handler function receives [EventHandlers.LocalVideoStreamAdded] object as an argument.
   * @deprecated
   * @hidden
   */
  CallEvents[CallEvents["LocalVideoStreamAdded"] = "LocalVideoStreamAdded"] = "LocalVideoStreamAdded";
  /**
   * Event is triggered when a new Endpoint is created. [Endpoint] represents an another participant in your call or conference.
   */
  CallEvents[CallEvents["EndpointAdded"] = "EndpointAdded"] = "EndpointAdded";
})(CallEvents = exports.CallEvents || (exports.CallEvents = {}));

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var logDisabled_ = true;
var deprecationWarnings_ = true;

// Utility methods.
var utils = {
  disableLog: function(bool) {
    if (typeof bool !== 'boolean') {
      return new Error('Argument type: ' + typeof bool +
          '. Please use a boolean.');
    }
    logDisabled_ = bool;
    return (bool) ? 'adapter.js logging disabled' :
        'adapter.js logging enabled';
  },

  /**
   * Disable or enable deprecation warnings
   * @param {!boolean} bool set to true to disable warnings.
   */
  disableWarnings: function(bool) {
    if (typeof bool !== 'boolean') {
      return new Error('Argument type: ' + typeof bool +
          '. Please use a boolean.');
    }
    deprecationWarnings_ = !bool;
    return 'adapter.js deprecation warnings ' + (bool ? 'disabled' : 'enabled');
  },

  log: function() {
    if (typeof window === 'object') {
      if (logDisabled_) {
        return;
      }
      if (typeof console !== 'undefined' && typeof console.log === 'function') {
        console.log.apply(console, arguments);
      }
    }
  },

  /**
   * Shows a deprecation warning suggesting the modern and spec-compatible API.
   */
  deprecated: function(oldMethod, newMethod) {
    if (!deprecationWarnings_) {
      return;
    }
    console.warn(oldMethod + ' is deprecated, please use ' + newMethod +
        ' instead.');
  },

  /**
   * Extract browser version out of the provided user agent string.
   *
   * @param {!string} uastring userAgent string.
   * @param {!string} expr Regular expression used as match criteria.
   * @param {!number} pos position in the version string to be returned.
   * @return {!number} browser version.
   */
  extractVersion: function(uastring, expr, pos) {
    var match = uastring.match(expr);
    return match && match.length >= pos && parseInt(match[pos], 10);
  },

  /**
   * Browser detector.
   *
   * @return {object} result containing browser and version
   *     properties.
   */
  detectBrowser: function(window) {
    var navigator = window && window.navigator;

    // Returned result object.
    var result = {};
    result.browser = null;
    result.version = null;

    // Fail early if it's not a browser
    if (typeof window === 'undefined' || !window.navigator) {
      result.browser = 'Not a browser.';
      return result;
    }

    // Firefox.
    if (navigator.mozGetUserMedia) {
      result.browser = 'firefox';
      result.version = this.extractVersion(navigator.userAgent,
          /Firefox\/(\d+)\./, 1);
    } else if (navigator.webkitGetUserMedia) {
      // Chrome, Chromium, Webview, Opera, all use the chrome shim for now
      if (window.webkitRTCPeerConnection) {
        result.browser = 'chrome';
        result.version = this.extractVersion(navigator.userAgent,
          /Chrom(e|ium)\/(\d+)\./, 2);
      } else { // Safari (in an unpublished version) or unknown webkit-based.
        if (navigator.userAgent.match(/Version\/(\d+).(\d+)/)) {
          result.browser = 'safari';
          result.version = this.extractVersion(navigator.userAgent,
            /AppleWebKit\/(\d+)\./, 1);
        } else { // unknown webkit-based browser.
          result.browser = 'Unsupported webkit-based browser ' +
              'with GUM support but no WebRTC support.';
          return result;
        }
      }
    } else if (navigator.mediaDevices &&
        navigator.userAgent.match(/Edge\/(\d+).(\d+)$/)) { // Edge.
      result.browser = 'edge';
      result.version = this.extractVersion(navigator.userAgent,
          /Edge\/(\d+).(\d+)$/, 2);
    } else if (navigator.mediaDevices &&
        navigator.userAgent.match(/AppleWebKit\/(\d+)\./)) {
        // Safari, with webkitGetUserMedia removed.
      result.browser = 'safari';
      result.version = this.extractVersion(navigator.userAgent,
          /AppleWebKit\/(\d+)\./, 1);
    } else { // Default fallthrough: not supported.
      result.browser = 'Not a supported browser.';
      return result;
    }

    return result;
  },

};

// Export.
module.exports = {
  log: utils.log,
  deprecated: utils.deprecated,
  disableLog: utils.disableLog,
  disableWarnings: utils.disableWarnings,
  extractVersion: utils.extractVersion,
  shimCreateObjectURL: utils.shimCreateObjectURL,
  detectBrowser: utils.detectBrowser.bind(utils)
};


/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var FF_1 = __webpack_require__(41);
var Webkit_1 = __webpack_require__(42);
var WebRTCPC_1 = __webpack_require__(43);
var Edge_1 = __webpack_require__(52);
var SignalingDTMFSender_1 = __webpack_require__(19);
var Safari_1 = __webpack_require__(53);
var Client_1 = __webpack_require__(1);
/**
 * Browser-specific implementation of webrtc functionality
 * @hidden
 */
var BrowserSpecific;
(function (BrowserSpecific) {
    var Vendor = void 0;
    (function (Vendor) {
        Vendor[Vendor["Firefox"] = 1] = "Firefox";
        Vendor[Vendor["Webkit"] = 2] = "Webkit";
        Vendor[Vendor["Edge"] = 3] = "Edge";
        Vendor[Vendor["Safari"] = 4] = "Safari";
    })(Vendor || (Vendor = {}));
    var vendor = void 0;
    function applyIdealConstraint(constraints, name, value) {
        var r = constraints;
        if ((typeof r === "undefined" ? "undefined" : _typeof(r)) != "object") {
            r = {};
        }
        r[name] = { ideal: value };
        return r;
    }
    function peerConnectionFactory(id, mode, videoEnabled) {
        switch (vendor) {
            case Vendor.Firefox:
                return new WebRTCPC_1.WebRTCPC(id, mode, videoEnabled);
            case Vendor.Webkit:
                return new WebRTCPC_1.WebRTCPC(id, mode, videoEnabled);
            case Vendor.Safari:
                return new WebRTCPC_1.WebRTCPC(id, mode, videoEnabled);
            case Vendor.Edge:
                return new WebRTCPC_1.WebRTCPC(id, mode, videoEnabled);
            //return new ORTC(id, mode, videoEnabled);
            default:
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.RTC, "Core", Logger_1.LogLevel.INFO, "Unsupported browser " + navigator.userAgent);
                return null;
        }
    }
    BrowserSpecific.peerConnectionFactory = peerConnectionFactory;
    function isIphone() {
        if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.emulate_ios) return true;
        return navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i);
    }
    BrowserSpecific.isIphone = isIphone;
    function isScreenSharingSupported() {
        return vendor === Vendor.Firefox || vendor === Vendor.Webkit;
    }
    BrowserSpecific.isScreenSharingSupported = isScreenSharingSupported;
    function defaultGetUserMedia(constraints) {
        return navigator.mediaDevices.getUserMedia(constraints);
    }
    function defaultGetDTMFSender(pc, callId) {
        return new SignalingDTMFSender_1.SignalingDTMFSender(callId);
    }
    function defaultScreenSharingSupported() {
        return new Promise(function (resolve) {
            resolve(false);
        });
    }
    //Convert user specified config to constraints object that can be recognized by browser
    function composeConstraintsDefault(config) {
        var audioConstraints = false;
        var videoConstraints = false;
        if (config.audioEnabled) {
            audioConstraints = true;
            if (config.audioInputId) audioConstraints = applyIdealConstraint(audioConstraints, "deviceId", config.audioInputId);
        }
        if (config.videoEnabled) {
            videoConstraints = true;
            if (config.videoSettings) {
                videoConstraints = config.videoSettings;
            }
            if (config.videoInputId) videoConstraints = applyIdealConstraint(videoConstraints, "deviceId", config.videoInputId);
        }
        return { peerIdentity: null, audio: audioConstraints, video: videoConstraints };
    }
    function getWSVendor() {
        var connectivityCheck = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

        if (connectivityCheck === false) {
            return "voxmobile";
        }
        if (!vendor) {
            detectVendor();
        }
        switch (vendor) {
            case Vendor.Firefox:
                return "firefox";
            case Vendor.Webkit:
                return "chrome";
            case Vendor.Safari:
                return "safari";
            case Vendor.Edge:
                return "edge";
            default:
                return "";
        }
    }
    BrowserSpecific.getWSVendor = getWSVendor;
    function detectVendor() {
        if (navigator["mozGetUserMedia"]) {
            vendor = Vendor.Firefox;
        } else if (navigator["webkitGetUserMedia"]) {
            vendor = Vendor.Webkit;
        } else if (navigator.mediaDevices && navigator.userAgent.match(/Edge\/(\d+).(\d+)$/)) {
            vendor = Vendor.Edge;
        } else if (navigator["getUserMedia"]) {
            vendor = Vendor.Safari;
        }
    }
    function detectFirefoxVersion() {}
    //This function must be called before usage
    function init() {
        if (!vendor) {
            detectVendor();
        }
        if (vendor) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.RTC, "Core", Logger_1.LogLevel.INFO, "Detected browser " + Vendor[vendor]);
        }
        BrowserSpecific.getUserMedia = defaultGetUserMedia;
        BrowserSpecific.getDTMFSender = defaultGetDTMFSender;
        BrowserSpecific.screenSharingSupported = defaultScreenSharingSupported;
        switch (vendor) {
            case Vendor.Firefox:
                BrowserSpecific.attachMedia = FF_1.FF.attachStream;
                BrowserSpecific.detachMedia = FF_1.FF.detachStream;
                BrowserSpecific.getScreenMedia = FF_1.FF.getScreenMedia;
                BrowserSpecific.getRTCStats = FF_1.FF.getRTCStats;
                BrowserSpecific.getUserMedia = FF_1.FF.getUserMedia;
                BrowserSpecific.screenSharingSupported = FF_1.FF.screenSharingSupported;
                BrowserSpecific.getDTMFSender = FF_1.FF.getDTMFSender;
                break;
            case Vendor.Webkit:
                BrowserSpecific.attachMedia = Webkit_1.Webkit.attachStream;
                BrowserSpecific.detachMedia = Webkit_1.Webkit.detachStream;
                BrowserSpecific.getScreenMedia = Webkit_1.Webkit.getScreenMedia;
                BrowserSpecific.getRTCStats = Webkit_1.Webkit.getRTCStats;
                BrowserSpecific.getUserMedia = Webkit_1.Webkit.getUserMedia;
                BrowserSpecific.screenSharingSupported = Webkit_1.Webkit.screenSharingSupported;
                BrowserSpecific.getDTMFSender = Webkit_1.Webkit.getDTMFSender;
                break;
            case Vendor.Safari:
                BrowserSpecific.attachMedia = Safari_1.Safari.attachStream;
                BrowserSpecific.detachMedia = Safari_1.Safari.detachStream;
                BrowserSpecific.getScreenMedia = Safari_1.Safari.getScreenMedia;
                BrowserSpecific.getRTCStats = Safari_1.Safari.getRTCStats;
                BrowserSpecific.getUserMedia = FF_1.FF.getUserMedia;
                BrowserSpecific.getDTMFSender = Safari_1.Safari.getDTMFSender;
                break;
            case Vendor.Edge:
                BrowserSpecific.attachMedia = Edge_1.Edge.attachStream;
                BrowserSpecific.detachMedia = Edge_1.Edge.detachStream;
                BrowserSpecific.getScreenMedia = Edge_1.Edge.getScreenMedia;
                BrowserSpecific.screenSharingSupported = Edge_1.Edge.screenSharingSupported;
                BrowserSpecific.getRTCStats = Edge_1.Edge.getRTCStats;
                break;
            default:
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.RTC, "Core", Logger_1.LogLevel.INFO, "Unsupported browser " + navigator.userAgent);
        }
        BrowserSpecific.composeConstraints = composeConstraintsDefault;
    }
    BrowserSpecific.init = init;
})(BrowserSpecific || (BrowserSpecific = {}));
exports.default = BrowserSpecific;

/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var PeerConnection_1 = __webpack_require__(24);
var VoxSignaling_1 = __webpack_require__(2);
var Logger_1 = __webpack_require__(0);
var CallManager_1 = __webpack_require__(5);
var Call_1 = __webpack_require__(13);
var BrowserSpecific_1 = __webpack_require__(8);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var Client_1 = __webpack_require__(1);
var index_1 = __webpack_require__(4);
var SDPMuggle_1 = __webpack_require__(20);
/**
 * Peer connection manager
 * @hidden
 */

var PCFactory = function () {
    function PCFactory() {
        var _this = this;

        _classCallCheck(this, PCFactory);

        this.iceConfig = null;
        this._peerConnections = {};
        this.waitingPeerConnections = {};
        this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.RTC, 'PCFactory');
        this._requireMedia = true;
        if (BrowserSpecific_1.default.getWSVendor() === 'firefox') PCFactory.hasTransceivers = true;
        VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.createPC, function (id, sdpOffer) {
            _this.rpcHandlerCreatePC(id, sdpOffer);
        });
        VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.destroyPC, function (id) {
            _this.rpcHandlerDestroyPC(id);
        });
        VoxSignaling_1.VoxSignaling.get().addHandler(this);
    }

    _createClass(PCFactory, [{
        key: "setupDirectPC",
        value: function setupDirectPC(id, mode, videoEnabled, pcHold) {
            var _this2 = this;

            var peerConnection = BrowserSpecific_1.default.peerConnectionFactory(id, mode, videoEnabled);
            peerConnection.setHoldKey(pcHold);
            var appConfig = Client_1.Client.getInstance().config();
            var _sm = index_1.default.StreamManager.get();
            var _call = CallManager_1.CallManager.get().calls[id];
            return _sm.getCallStream(_call).then(function (stream) {
                if (stream !== null) {
                    peerConnection.fastAddCustomMedia(stream);
                }
                _this2._peerConnections[id] = peerConnection;
                return peerConnection.getLocalOffer();
            });
        }
    }, {
        key: "incomeDirectPC",
        value: function incomeDirectPC(id, videoEnabled, sdp, pcHold) {
            var _this3 = this;

            var peerConnection = BrowserSpecific_1.default.peerConnectionFactory(id, PeerConnection_1.PeerConnectionMode.P2P, videoEnabled);
            peerConnection.setHoldKey(pcHold);
            return peerConnection._setRemoteDescription(sdp).then(function () {
                _this3._peerConnections[id] = peerConnection;
                return peerConnection;
            });
        }
    }, {
        key: "getPeerConnect",
        value: function getPeerConnect(id) {
            return this._peerConnections[id];
        }
    }, {
        key: "onSignalingConnected",
        value: function onSignalingConnected() {}
    }, {
        key: "onSignalingClosed",
        value: function onSignalingClosed() {
            this.log.info('Closing all peer connections because signaling connection has closed');
            this.waitingPeerConnections = {};
            for (var i in this._peerConnections) {
                this._peerConnections[i].close();
            }
            this._peerConnections = {};
        }
        //Specifies if user media access is required in current application.

    }, {
        key: "onSignalingConnectionFailed",
        value: function onSignalingConnectionFailed(errorMessage) {}
    }, {
        key: "onMediaConnectionFailed",
        value: function onMediaConnectionFailed() {}
        /**
         * Close all current peer connections
         * @hidden
         */

    }, {
        key: "closeAll",
        value: function closeAll() {
            for (var i in this._peerConnections) {
                this._peerConnections[i].close();
            }this._peerConnections = {};
        }
    }, {
        key: "setBandwidthParams",
        value: function setBandwidthParams(bandwidt) {
            this._bandwidthParams = bandwidt;
        }
    }, {
        key: "addBandwidthParams",
        value: function addBandwidthParams(sdp) {
            if (this._bandwidthParams) sdp.sdp = sdp.sdp.replace(/(a=mid:video.*\r\n)/g, '$1b=AS:' + this._bandwidthParams + '\r\n');
            return sdp;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'PCFactory';
        }
    }, {
        key: "rpcHandlerCreatePC",
        value: function rpcHandlerCreatePC(id, sdpOffer) {
            sdpOffer = SDPMuggle_1.SDPMuggle.addSetupAttribute(sdpOffer);
            var videoEnabled = PCFactory.sdpOffersVideo(sdpOffer);
            var mode = PeerConnection_1.PeerConnectionMode.CLIENT_SERVER_V1;
            VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.muteLocal, id, false);
            var pc = BrowserSpecific_1.default.peerConnectionFactory(id, mode, videoEnabled);
            this._peerConnections[id] = pc;
            var call = CallManager_1.CallManager.get().calls[id];
            index_1.default.StreamManager.get().getCallStream(call).then(function (stream) {
                if (id === '__default') sdpOffer = sdpOffer.replace('a=sendrecv', 'a=recvonly');
                pc.fastAddCustomMedia(stream);
                pc.processRemoteOffer(sdpOffer).then(function (localAnswer) {
                    if (typeof call === 'undefined' || call.checkCallMode(Call_1.CallMode.SERVER)) VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.confirmPC, id, localAnswer);else VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.acceptCall, [id, CallManager_1.CallManager.cleanHeaders(call.headers()), localAnswer]);
                    if (id !== '__default' && typeof CallManager_1.CallManager.get().calls[id] !== 'undefined') {
                        CallManager_1.CallManager.get().calls[id].peerConnection = pc;
                    }
                });
            }).catch(function (e) {
                if (typeof call !== 'undefined') {
                    CallManager_1.CallManager.get().handleConnectionFailed(call.id(), 403, 'Media access denied', {});
                } else {
                    VoxSignaling_1.VoxSignaling.get().onConnectionFailedRPC();
                }
            });
        }
    }, {
        key: "rpcHandlerDestroyPC",
        value: function rpcHandlerDestroyPC(id) {
            var _this4 = this;

            if (this._peerConnections[id]) {
                if (id === '__default') {
                    setTimeout(function () {
                        _this4._peerConnections[id].close();
                        delete _this4._peerConnections[id];
                    }, 200);
                } else {
                    this._peerConnections[id].close();
                    delete this._peerConnections[id];
                }
            }
            delete this.waitingPeerConnections[id];
        }
    }, {
        key: "peerConnections",
        get: function get() {
            return this._peerConnections;
        }
    }, {
        key: "requireMedia",
        get: function get() {
            return this._requireMedia;
        },
        set: function set(b) {
            this._requireMedia = b;
        }
    }], [{
        key: "get",
        value: function get() {
            if (this.inst === null) {
                this.inst = new PCFactory();
            }
            return this.inst;
        }
        /**
         * Check if SDP contains video media
         */

    }, {
        key: "sdpOffersVideo",
        value: function sdpOffersVideo(sdpOffer) {
            return { receiveVideo: sdpOffer.indexOf('m=video') !== -1, sendVideo: true };
        }
    }]);

    return PCFactory;
}();

PCFactory.inst = null;
/**
 * Get state of transivers API
 * @type {boolean}
 */
PCFactory.hasTransceivers = false;
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "setupDirectPC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "incomeDirectPC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "getPeerConnect", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "onSignalingConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "onSignalingClosed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "onSignalingConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "onMediaConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "closeAll", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "setBandwidthParams", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "addBandwidthParams", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "rpcHandlerCreatePC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory.prototype, "rpcHandlerDestroyPC", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.PCFACTORY)], PCFactory, "sdpOffersVideo", null);
exports.PCFactory = PCFactory;

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var VoxSignaling_1 = __webpack_require__(2);
var Logger_1 = __webpack_require__(0);
var PCFactory_1 = __webpack_require__(9);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var CallstatsIo_1 = __webpack_require__(21);
/**
 * @hidden
 */
var AuthenticatorState;
(function (AuthenticatorState) {
    AuthenticatorState[AuthenticatorState["IDLE"] = 0] = "IDLE";
    AuthenticatorState[AuthenticatorState["IN_PROGRESS"] = 1] = "IN_PROGRESS";
})(AuthenticatorState = exports.AuthenticatorState || (exports.AuthenticatorState = {}));
;
/**
 * Class that performs user login
 * Implemented as singleton
 * @hidden
 */

var Authenticator = function () {
    function Authenticator() {
        var _this = this;

        _classCallCheck(this, Authenticator);

        this.FAIL_CODE_SECOND_STAGE = 301;
        this.FAIL_CODE_ONE_TIME_KEY = 302;
        this._displayName = null;
        this._username = null;
        this._authorized = false;
        this.signaling = VoxSignaling_1.VoxSignaling.get();
        this.currentState = AuthenticatorState.IDLE;
        this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.SIGNALING, "Authenticator");
        //Register handlers for Server->Client RPC
        this.signaling.setRPCHandler(RemoteEvent_1.RemoteEvent.loginFailed, function (code, extra) {
            _this.onLoginFailed(code, extra);
        });
        this.signaling.setRPCHandler(RemoteEvent_1.RemoteEvent.loginSuccessful, function (displayName, params) {
            _this.onLoginSuccesful(displayName, params);
        });
        this.signaling.setRPCHandler(RemoteEvent_1.RemoteEvent.refreshOauthTokenFailed, function (code) {
            _this.handler.onRefreshTokenFailed(code);
        });
        this.signaling.setRPCHandler(RemoteEvent_1.RemoteEvent.refreshOauthTokenSuccessful, function (oauth) {
            _this.handler.onRefreshTokenSuccess(oauth.OAuth);
        });
        this.signaling.addHandler(this);
    }

    _createClass(Authenticator, [{
        key: "setHandler",
        value: function setHandler(h) {
            this.handler = h;
        }
    }, {
        key: "onLoginFailed",
        value: function onLoginFailed(code, extra) {
            this.currentState = AuthenticatorState.IDLE;
            switch (code) {
                case this.FAIL_CODE_ONE_TIME_KEY:
                    this.handler.onOneTimeKeyGenerated(extra);
                    break;
                case this.FAIL_CODE_SECOND_STAGE:
                    this.handler.onSecondStageInitiated();
                    break;
                default:
                    this.handler.onLoginFailed(code);
                    break;
            }
        }
    }, {
        key: "onLoginSuccesful",
        value: function onLoginSuccesful(displayName, params) {
            this.currentState = AuthenticatorState.IDLE;
            this._authorized = true;
            if (params) PCFactory_1.PCFactory.get().iceConfig = params.iceConfig;
            this._displayName = displayName;
            CallstatsIo_1.CallstatsIo.get().init({ userName: this._username, aliasName: this._displayName });
            this.handler.onLoginSuccessful(displayName, params.OAuth);
        }
        /**
         * User display name. Is returned by server`
         */

    }, {
        key: "basicLogin",
        value: function basicLogin(username, password, options) {
            if (this.currentState != AuthenticatorState.IDLE) {
                this.log.error("Login operation already in progress");
                return;
            }
            this._username = username;
            this.currentState = AuthenticatorState.IN_PROGRESS;
            this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.login, username, password, options);
        }
    }, {
        key: "tokenLogin",
        value: function tokenLogin(username, options) {
            if (this.currentState != AuthenticatorState.IDLE) {
                this.log.error("Login operation already in progress");
                return;
            }
            this._username = username;
            this.currentState = AuthenticatorState.IN_PROGRESS;
            this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.login, username, '', options);
        }
    }, {
        key: "tokenRefresh",
        value: function tokenRefresh(username, refreshToken, deviceToken) {
            if (deviceToken) this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.refreshOauthToken, username, { refreshToken: refreshToken, deviceToken: deviceToken });else this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.refreshOauthToken, username, refreshToken);
        }
    }, {
        key: "loginUsingOneTimeKey",
        value: function loginUsingOneTimeKey(username, hash, options) {
            if (this.currentState != AuthenticatorState.IDLE) {
                this.log.error("Login operation already in progress");
                return;
            }
            this._username = username;
            this.currentState = AuthenticatorState.IN_PROGRESS;
            this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.loginUsingOneTimeKey, username, hash, options);
        }
    }, {
        key: "loginStage2",
        value: function loginStage2(username, code, options) {
            if (this.currentState != AuthenticatorState.IDLE) {
                this.log.error("Login operation already in progress");
                return;
            }
            this._username = username;
            this.currentState = AuthenticatorState.IN_PROGRESS;
            this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.loginStage2, username, code, options);
        }
    }, {
        key: "generateOneTimeKey",
        value: function generateOneTimeKey(username) {
            if (this.currentState != AuthenticatorState.IDLE) {
                this.log.error("Login operation already in progress");
                return;
            }
            this.currentState = AuthenticatorState.IN_PROGRESS;
            this.signaling.callRemoteFunction(RemoteFunction_1.RemoteFunction.loginGenerateOneTimeKey, username);
        }
    }, {
        key: "username",
        value: function username() {
            return this._username;
        }
    }, {
        key: "authorized",
        value: function authorized() {
            return this._authorized;
        }
    }, {
        key: "onSignalingConnected",
        value: function onSignalingConnected() {}
    }, {
        key: "onSignalingConnectionFailed",
        value: function onSignalingConnectionFailed(errorMessage) {}
    }, {
        key: "onSignalingClosed",
        value: function onSignalingClosed() {
            this._authorized = false;
            this._displayName = null;
            this._username = null;
        }
    }, {
        key: "onMediaConnectionFailed",
        value: function onMediaConnectionFailed() {}
    }, {
        key: "ziAuthorized",
        value: function ziAuthorized(state) {
            this._authorized = state;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Authenticator';
        }
    }, {
        key: "displayName",
        get: function get() {
            return this._displayName;
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof this.inst == "undefined") {
                this.inst = new Authenticator();
            }
            return this.inst;
        }
    }]);

    return Authenticator;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "setHandler", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onLoginFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onLoginSuccesful", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "basicLogin", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "tokenLogin", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "tokenRefresh", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "loginUsingOneTimeKey", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "loginStage2", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "generateOneTimeKey", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "username", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "authorized", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onSignalingConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onSignalingConnectionFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onSignalingClosed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.AUTHENTICATOR)], Authenticator.prototype, "onMediaConnectionFailed", null);
exports.Authenticator = Authenticator;

/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * @hidden
 */

var Constants = function Constants() {
  _classCallCheck(this, Constants);
};

Constants.DIRECT_CALL_HEADER = "X-DirectCall";
Constants.VIAMEDIA_CALL_HEADER = "X-ViaMedia";
Constants.CALLSTATSIOID_HEADER = "X-CallstatsIOID";
Constants.CALL_DATA_HEADER = "VI-CallData";
Constants.ZINGAYA_IM_MIME_TYPE = "application/zingaya-im";
Constants.P2P_SPD_FRAG_MIME_TYPE = "voximplant/sdpfrag";
Constants.VI_HOLD_EMUL = "vi/holdemul";
Constants.VI_SPD_OFFER_MIME_TYPE = "vi/sdpoffer";
Constants.VI_SPD_ANSWER_MIME_TYPE = "vi/sdpanswer";
Constants.VI_CONF_PARTICIPANT_INFO_ADDED = "vi/conf-info-added";
Constants.VI_CONF_PARTICIPANT_INFO_REMOVED = "vi/conf-info-removed";
Constants.VI_CONF_PARTICIPANT_INFO_UPDATED = "vi/conf-info-updated";
exports.Constants = Constants;

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Enum for handle remote events
 * @hidden
 */
var RemoteEvent;
(function (RemoteEvent) {
    RemoteEvent[RemoteEvent["loginFailed"] = "loginFailed"] = "loginFailed";
    RemoteEvent[RemoteEvent["loginSuccessful"] = "loginSuccessful"] = "loginSuccessful";
    RemoteEvent[RemoteEvent["handleError"] = "handleError"] = "handleError";
    RemoteEvent[RemoteEvent["onPCStats"] = "__onPCStats"] = "onPCStats";
    RemoteEvent[RemoteEvent["handleIncomingConnection"] = "handleIncomingConnection"] = "handleIncomingConnection";
    RemoteEvent[RemoteEvent["handleConnectionConnected"] = "handleConnectionConnected"] = "handleConnectionConnected";
    RemoteEvent[RemoteEvent["handleConnectionDisconnected"] = "handleConnectionDisconnected"] = "handleConnectionDisconnected";
    RemoteEvent[RemoteEvent["handleRingOut"] = "handleRingOut"] = "handleRingOut";
    RemoteEvent[RemoteEvent["startEarlyMedia"] = "startEarlyMedia"] = "startEarlyMedia";
    RemoteEvent[RemoteEvent["stopRinging"] = "stopRinging"] = "stopRinging";
    RemoteEvent[RemoteEvent["handleConnectionFailed"] = "handleConnectionFailed"] = "handleConnectionFailed";
    RemoteEvent[RemoteEvent["handleSIPInfo"] = "handleSIPInfo"] = "handleSIPInfo";
    RemoteEvent[RemoteEvent["handleSipEvent"] = "handleSipEvent"] = "handleSipEvent";
    RemoteEvent[RemoteEvent["handleTransferStarted"] = "handleTransferStarted"] = "handleTransferStarted";
    RemoteEvent[RemoteEvent["handleTransferComplete"] = "handleTransferComplete"] = "handleTransferComplete";
    RemoteEvent[RemoteEvent["handleTransferFailed"] = "handleTransferFailed"] = "handleTransferFailed";
    RemoteEvent[RemoteEvent["handleReInvite"] = "handleReInvite"] = "handleReInvite";
    RemoteEvent[RemoteEvent["handleAcceptReinvite"] = "handleAcceptReinvite"] = "handleAcceptReinvite";
    RemoteEvent[RemoteEvent["handleRejectReinvite"] = "handleRejectReinvite"] = "handleRejectReinvite";
    RemoteEvent[RemoteEvent["createPC"] = "__createPC"] = "createPC";
    RemoteEvent[RemoteEvent["destroyPC"] = "__destroyPC"] = "destroyPC";
    RemoteEvent[RemoteEvent["connectionSuccessful"] = "__connectionSuccessful"] = "connectionSuccessful";
    RemoteEvent[RemoteEvent["connectionFailed"] = "__connectionFailed"] = "connectionFailed";
    RemoteEvent[RemoteEvent["createConnection"] = "__createConnection"] = "createConnection";
    RemoteEvent[RemoteEvent["pong"] = "__pong"] = "pong";
    RemoteEvent[RemoteEvent["increaseGain"] = "increaseGain"] = "increaseGain";
    RemoteEvent[RemoteEvent["handlePreFlightCheckResult"] = "handlePreFlightCheckResult"] = "handlePreFlightCheckResult";
    RemoteEvent[RemoteEvent["handleVoicemail"] = "handleVoicemail"] = "handleVoicemail";
    RemoteEvent[RemoteEvent["onCallRemoteFunctionError"] = "onCallRemoteFunctionError"] = "onCallRemoteFunctionError";
    RemoteEvent[RemoteEvent["refreshOauthTokenFailed"] = "refreshOauthTokenFailed"] = "refreshOauthTokenFailed";
    RemoteEvent[RemoteEvent["refreshOauthTokenSuccessful"] = "refreshOauthTokenSuccessful"] = "refreshOauthTokenSuccessful";
    RemoteEvent[RemoteEvent["sipRegisterSuccessful"] = "sipRegisterSuccessful"] = "sipRegisterSuccessful";
    RemoteEvent[RemoteEvent["sipRegisterFailed"] = "sipRegisterFailed"] = "sipRegisterFailed";
    RemoteEvent[RemoteEvent["onACDStatus"] = "onACDStatus"] = "onACDStatus";
})(RemoteEvent = exports.RemoteEvent || (exports.RemoteEvent = {}));

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var CallEvents_1 = __webpack_require__(6);
var VoxSignaling_1 = __webpack_require__(2);
var Constants_1 = __webpack_require__(11);
var Logger_1 = __webpack_require__(0);
var PCFactory_1 = __webpack_require__(9);
var CallManager_1 = __webpack_require__(5);
var BrowserSpecific_1 = __webpack_require__(8);
var RemoteFunction_1 = __webpack_require__(3);
var Client_1 = __webpack_require__(1);
var EventTarget_1 = __webpack_require__(14);
var EndpointManager_1 = __webpack_require__(26);
var Hardware_1 = __webpack_require__(4);
/**
 * The Call class represents a single call, inbound or outbound. For outbound call it is retrieved from the <a href="client.html#call">Client</a> class instance. For inbound call use a handler for the <a href="//voximplant.com/refs/websdk/enums/events.html#incomingcall">IncomingCall</a> event.
 *
 * Note that Web SDK allows is able to manage a lot of calls.
 *
 *
 * Example:
 * ``` js
 * // Getting an incoming call
 * let currentCall;
 * VoxImplant.getInstance().addEventListener(VoxImplant.Events.IncomingCall, onIncomingCall);
 *
 * function onIncomingCall(event) {
 *   currentCall = event.call;
 *   // ...
 * }
 * ```

 */
/**
 * @hidden
 */
var CallState;
(function (CallState) {
    CallState[CallState["ALERTING"] = 'ALERTING'] = "ALERTING";
    CallState[CallState["PROGRESSING"] = 'PROGRESSING'] = "PROGRESSING";
    CallState[CallState["CONNECTED"] = 'CONNECTED'] = "CONNECTED";
    CallState[CallState["UPDATING"] = 'UPDATING'] = "UPDATING";
    CallState[CallState["ENDED"] = 'ENDED'] = "ENDED";
})(CallState = exports.CallState || (exports.CallState = {}));
/**
 * @hidden
 */
var CallMode;
(function (CallMode) {
    CallMode[CallMode["P2P"] = 0] = "P2P";
    CallMode[CallMode["SERVER"] = 1] = "SERVER";
})(CallMode = exports.CallMode || (exports.CallMode = {}));
/**
 *
 */

var Call = function (_EventTarget_1$EventT) {
    _inherits(Call, _EventTarget_1$EventT);

    /**
     * @hidden
     */
    function Call(id, dn, incoming, settings) {
        _classCallCheck(this, Call);

        /**
         * @hidden
         */
        var _this = _possibleConstructorReturn(this, (Call.__proto__ || Object.getPrototypeOf(Call)).call(this));

        _this.remoteMuteState = true;
        _this.signalingConnected = false;
        _this.settings = settings;
        _this.settings.id = id;
        _this.settings.displayName = dn;
        _this.settings.mode = CallMode.P2P;
        _this.settings.active = true;
        _this.settings.usedSinkId = null;
        _this.settings.incoming = incoming;
        _this.settings.state = incoming ? CallState.ALERTING : CallState.PROGRESSING;
        var appConfig = Client_1.Client.getInstance().config();
        _this.settings.audioDirections = { sendAudio: true }; // always sendAudio by default
        _this.settings.videoDirections = typeof settings.video === 'boolean' ? { sendVideo: settings.video, receiveVideo: true } : settings.video; // set VideoDirection with backward compat
        _this.settings.hasEarlyMedia = false;
        _this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.CALL, 'Call ' + id);
        _this._callManager = CallManager_1.CallManager.get();
        return _this;
    }
    /**
     * @hidden
     * @returns {Promise<Object>}
     */


    _createClass(Call, [{
        key: "id",

        /**
         * Returns call id
         * @returns {String}
         */
        value: function id() {
            return this.settings.id;
        }
        /**
         * Returns dialed number or caller id
         * @returns {String}
         */

    }, {
        key: "number",
        value: function number() {
            return this.settings.number;
        }
        /**
         * Returns display name, i.e. a name of the calling user, that will be displayed to the called user. Normally it's a human-readable version of CallerID, e.g. a person's name.
         */

    }, {
        key: "displayName",
        value: function displayName() {
            return this.settings.displayName;
        }
        /**
         * Returns headers
         * @returns {Object}
         */

    }, {
        key: "headers",
        value: function headers() {
            return this.settings.extraHeaders;
        }
        /**
         * Returns 'true' if a call is active, otherwise returns 'false'. A single call (either inbound or outbound) is active by default, all other calls are inactive and should be activated via the <a href="#setactive">setActive</a> method. Only the active call sends and receives an audio/video stream.
         */

    }, {
        key: "active",
        value: function active() {
            return this.settings.active;
        }
        /**
         * Get the current state of a call.
         * Possible values are: "ALERTING", "PROGRESSING", "CONNECTED", "ENDED".
         * @returns {String}
         */

    }, {
        key: "state",
        value: function state() {
            return CallState[this.settings.state];
        }
        /**
         * Answer the incoming call. There are two methods for an <a href="//voximplant.com/refs/websdk/enums/events.html#incomingcall">incoming call</a>: <a href="#answer">answer</a> and <a href="#decline">decline</a>. Voice can be sended only after the <a href="#answer">answer</a> method call.
         * @param {String} customData Set custom string associated with call session. It can be later obtained from Call History <a href="//voximplant.com/docs/references/httpapi/#toc-getcallhistory">using HTTP API</a>, see the <a href="//voximplant.com/docs/references/httpapi/#struct_CallSessionInfoType">custom_data field in result</a>. Custom data can be retrieved on the part of Voxengine via the <a href="//voximplant.com/docs/references/appengine/VoxEngine.html#VoxEngine_customData">customData</a> method. Maximum size is 200 bytes.
         * @param {Object} extraHeaders Optional custom parameters (SIP headers) that are sent to another participant after accepting an incoming call. Header names have to begin with the 'X-' prefix. The "X-" headers could be handled only by SIP phones/devices.
         * @param {VideoFlags} useVideo [A set of flags](https://voximplant.com/docs/references/websdk/voximplant/videoflags) defining if sending and receiving video is allowed.
         */

    }, {
        key: "answer",
        value: function answer(customData, extraHeaders, useVideo) {
            if (typeof customData != 'undefined') {
                if (typeof extraHeaders == 'undefined' || (typeof extraHeaders === "undefined" ? "undefined" : _typeof(extraHeaders)) !== 'object') extraHeaders = {};
                extraHeaders[Constants_1.Constants.CALL_DATA_HEADER] = customData;
            }
            if (typeof useVideo !== 'undefined') useVideo = {
                sendVideo: Client_1.Client.getInstance().config().videoSupport,
                receiveVideo: Client_1.Client.getInstance().config().videoSupport
            };
            if (this.settings.state != CallState.ALERTING) throw new Error('WRONG_CALL_STATE');
            if (typeof useVideo != 'undefined') {
                this._peerConnection.setVideoFlags(useVideo);
            }
        }
        /**
         * Reject incoming call on all devices, where this user logged in.
         * @param {Object} extraHeaders Optional custom parameters (SIP headers) that should be sent after rejecting incoming call. Parameter names must start with "X-" to be processed by application
         */

    }, {
        key: "decline",
        value: function decline(extraHeaders) {
            if (this.settings.state != CallState.ALERTING) throw new Error('WRONG_CALL_STATE');
            VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.rejectCall, this.settings.id, false, CallManager_1.CallManager.cleanHeaders(extraHeaders));
        }
        /**
         * Reject incoming call on the part of Web SDK. If a call is initiated from the PSTN, the network will receive "reject" command; in case of a call from another Web SDK client, it will receive the [CallEvents.Failed] event with the 603 code.
         * @param {Object} extraHeaders Optional custom parameters (SIP headers) that should be sent after rejecting incoming call. Parameter names must start with "X-" to be processed by application
         */

    }, {
        key: "reject",
        value: function reject(extraHeaders) {
            if (this.settings.state != CallState.ALERTING) throw new Error('WRONG_CALL_STATE');
            VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.rejectCall, this.settings.id, true, CallManager_1.CallManager.cleanHeaders(extraHeaders));
        }
        /**
         * Hangup call
         * @param {[id:string]:string} extraHeaders Optional custom parameters (SIP headers) that should be sent after disconnecting/cancelling call. Parameter names must start with "X-" to be processed by application
         */

    }, {
        key: "hangup",
        value: function hangup(extraHeaders) {
            if (this.settings.state == CallState.CONNECTED || this.settings.state == CallState.UPDATING || this.settings.state == CallState.PROGRESSING) VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.disconnectCall, this.settings.id, CallManager_1.CallManager.cleanHeaders(extraHeaders));else if (this.settings.state == CallState.ALERTING) VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.rejectCall, this.settings.id, true, CallManager_1.CallManager.cleanHeaders(extraHeaders));else throw new Error('WRONG_CALL_STATE');
        }
        /**
         * Send tone (DTMF). It triggers the <a href="https://voximplant.com/docs/references/appengine/CallEvents.html#CallEvents_ToneReceived">CallEvents.ToneReceived</a> event in our cloud.
         * @param {String} key Send tone according to pressed key: 0-9 , * , #
         */

    }, {
        key: "sendTone",
        value: function sendTone(key) {
            if (this.settings.active) this._peerConnection.sendDTMF(key);
        }
        /**
         * Mute sound
         */

    }, {
        key: "mutePlayback",
        value: function mutePlayback() {
            this.remoteMuteState = false;
            EndpointManager_1.EndpointManager.get().getEndpoints(this).forEach(function (endPoint) {
                endPoint.mediaRenderers.forEach(function (mediaRenderer) {
                    mediaRenderer.setVolume(0);
                });
            });
        }
        /**
         * Unmute sound
         */

    }, {
        key: "unmutePlayback",
        value: function unmutePlayback() {
            this.remoteMuteState = true;
            EndpointManager_1.EndpointManager.get().getEndpoints(this).forEach(function (endPoint) {
                endPoint.mediaRenderers.forEach(function (mediaRenderer) {
                    mediaRenderer.setVolume(1);
                });
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "restoreRMute",
        value: function restoreRMute() {
            var _this2 = this;

            if (this.settings.active) {
                EndpointManager_1.EndpointManager.get().getEndpoints(this).forEach(function (endPoint) {
                    endPoint.mediaRenderers.forEach(function (mediaRenderer) {
                        mediaRenderer.setVolume(_this2.remoteMuteState ? 1 : 0);
                    });
                });
            }
        }
        /**
         * Mute microphone
         */

    }, {
        key: "muteMicrophone",
        value: function muteMicrophone() {
            this.peerConnection.muteMicrophone(true);
        }
        /**
         * Unmute microphone
         */

    }, {
        key: "unmuteMicrophone",
        value: function unmuteMicrophone() {
            this.peerConnection.muteMicrophone(false);
        }
        /**
         * Show/hide remote party video. *IMPORTANT*: Safari browser for iOS requires a user interface for playing video during a call. It should be interactive element like an HTML "button" with "onclick" handler that calls "play" method on the "video" HTML element.
         * @param {Boolean} [flag=true] Show/hide - true/false
         * @deprecated
         * @hidden
         */

    }, {
        key: "showRemoteVideo",
        value: function showRemoteVideo() {
            var flag = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

            if (typeof flag == 'undefined') flag = true;
            var endPoints = EndpointManager_1.EndpointManager.get().getEndpoints(this);
            if (endPoints) {
                endPoints.forEach(function (endPoint) {
                    if (endPoint.mediaRenderers) {
                        endPoint.mediaRenderers.forEach(function (mediaRenderer) {
                            if (mediaRenderer.element) mediaRenderer.element.style.display = flag ? 'block' : 'none';
                        });
                    }
                });
            }
        }
        /**
         * Set remote video position
         * @param {Number} x Horizontal position (px)
         * @param {Number} y Vertical position (px)
         * @function
         * @hidden
         */

    }, {
        key: "setRemoteVideoPosition",
        value: function setRemoteVideoPosition(x, y) {
            throw new Error('Deprecated: please use CSS to position \'#voximplantcontainer\' element');
        }
        /**
         * Set remote video size
         * @param {Number} width Width in pixels
         * @param {Number} height Height in pixels
         * @function
         * @deprecated
         * @hidden
         */

    }, {
        key: "setRemoteVideoSize",
        value: function setRemoteVideoSize(width, height) {
            throw new Error('Deprecated: please use CSS to set size of \'#voximplantcontainer\' element');
        }
        /**
         * Send Info (SIP INFO) message inside the call
         *
         * You can get this message via the Voxengine [CallEvents.InfoReceived] event in our cloud.
         *
         * You can get this message in Web SDK on other side via the [CallEvents.InfoReceived] event; see the similar events for the <a href="//voximplant.com/docs/references/mobilesdk/ios/all/index.html#//api/name/call:didReceiveInfo:type:headers:">iOS</a> and <a href="//voximplant.com/docs/references/mobilesdk/android/com/voximplant/sdk/call/ICallListener.html#onSIPInfoReceived-com.voximplant.sdk.call.ICall-java.lang.String-java.lang.String-java.util.Map-">Android</a> SDKs.
         * @param {String} mimeType MIME type of the message, e.g. "text/plain", "multipart/mixed" etc.
         * @param {String} body Message content
         * @param {[id:string]:string} extraHeaders Optional headers to be passed with the message
         */

    }, {
        key: "sendInfo",
        value: function sendInfo(mimeType, body, extraHeaders) {
            var type,
                subtype,
                i = mimeType.indexOf('/');
            if (i == -1) {
                type = 'application';
                subtype = mimeType;
            } else {
                type = mimeType.substring(0, i);
                subtype = mimeType.substring(i + 1);
            }
            //if (this._state != CallState.CONNECTED)
            //    throw new Error("WRONG_CALL_STATE");
            VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.sendSIPInfo, this.settings.id, type, subtype, body, CallManager_1.CallManager.cleanHeaders(extraHeaders));
        }
        /**
         * Send text message. It is a special case of the [sendInfo] method as it allows to send messages only of "text/plain" type.
         *
         * You can get this message via the Voxengine [CallEvents.MessageReceived] event in our cloud.
         *
         * You can get this message in Web SDK on other side via the [CallEvents.MessageReceived] event; see the similar events for the <a href="//voximplant.com/docs/references/mobilesdk/ios/all/index.html#//api/name/call:didReceiveMessage:headers:">iOS</a> and <a href="//voximplant.com/docs/references/mobilesdk/android/com/voximplant/sdk/call/ICallListener.html#onMessageReceived-com.voximplant.sdk.call.ICall-java.lang.String-">Android</a> SDKs.
         * @param {String} msg Message text
         */

    }, {
        key: "sendMessage",
        value: function sendMessage(msg) {
            this.sendInfo(Constants_1.Constants.ZINGAYA_IM_MIME_TYPE, msg, {});
        }
        /**
         * Set video settings
         * @param {VoxImplant.VideoSettings|VoxImplant.FlashVideoSettings} settings Video settings for current call
         * @param {Function} [successCallback] Called in WebRTC mode if video settings were applied successfully
         * @param {Function} [failedCallback] Called in WebRTC mode if video settings couldn't be applied
         * @deprecated
         * @hidden
         */

    }, {
        key: "setVideoSettings",
        value: function setVideoSettings(settings, successCallback, failedCallback) {
            Hardware_1.default.CameraManager.get().setCallVideoSettings(this, Hardware_1.default.CameraManager.legacyParamConverter(settings));
            Hardware_1.default.StreamManager.get().updateCallStream(this).then(function (stream) {
                if (successCallback) successCallback(stream);
            }, function (e) {
                if (failedCallback) failedCallback();
            });
        }
        /**
         * Returns HTML video element's id for the call
         * @deprecated
         * @hidden
         */

    }, {
        key: "getVideoElementId",
        value: function getVideoElementId() {
            var endPoints = EndpointManager_1.EndpointManager.get().getEndpoints(this);
            if (typeof endPoints !== 'undefined') endPoints.forEach(function (endPoint) {
                endPoint.mediaRenderers.forEach(function (mediaRenderer) {
                    if (mediaRenderer.stream) {
                        var videoTracks = mediaRenderer.stream.getVideoTracks();
                        if (videoTracks.length) return mediaRenderer.stream.getTracks()[0].id;
                    }
                });
            });
            return '';
        }
        /**
         * Register a handler for the specified event. One event can have more than one handler; handlers are executed in order of registration.
         * Use the [removeEventListener] method to delete a handler.
         * @param {Function} event Event class (i.e. [CallEvents.Connected]). See [CallEvents]
         * @param {Function} handler Handler function. A single parameter is passed - object with event information
         * @deprecated
         * @hidden
         */

    }, {
        key: "addEventListener",
        value: function addEventListener(event, handler) {
            _get(Call.prototype.__proto__ || Object.getPrototypeOf(Call.prototype), "addEventListener", this).call(this, event, handler);
        }
        /**
         * Register a handler for the specified event. The method is a shorter equivalent for *addEventListener*. One event can have more than one handler; handlers are executed in order of registration.
         * Use the [Call.off] method to delete a handler.
         *
         *
         * @example
         *   var currentCall = vox.call("exampleUser");
         *   currentCall.on(VoxImplant.CallEvents.Connected,onConnected);
         *   currentCall.on(VoxImplant.CallEvents.Disconnected,onDisconnected);
         *   currentCall.on(VoxImplant.CallEvents.Failed,onFailed);
         *   currentCall.on(VoxImplant.CallEvents.ICETimeout,onICETimeout);
         * @param {Function} event Event class (i.e. [CallEvents.Connected]. See [CallEvents]
         * @param {Function} handler Handler function. A single parameter is passed - object with event information
         */

    }, {
        key: "on",
        value: function on(event, handler) {
            _get(Call.prototype.__proto__ || Object.getPrototypeOf(Call.prototype), "on", this).call(this, event, handler);
        }
        /**
         * Remove handler for specified event
         * @param {Function} event Event class (i.e. [CallEvents.Connected]). See [CallEvents]
         * @param {Function} handler Handler function, if not specified all event handlers will be removed
         * @deprecated
         * @hidden
         */

    }, {
        key: "removeEventListener",
        value: function removeEventListener(event, handler) {
            _get(Call.prototype.__proto__ || Object.getPrototypeOf(Call.prototype), "removeEventListener", this).call(this, event, handler);
        }
        /**
         * Remove a handler for the specified event. The method is a shorter equivalent for *removeEventListener*. If a number of events has the same function as a handler, the method can be called multiple times with the same handler argument.
         * @param {Function} event Event class (i.e. [CallEvents.Connected]). See [VoxImplant.CallEvents].
         * @param {Function} handler Handler function, if not specified all event handlers will be removed
         * @function
         */

    }, {
        key: "off",
        value: function off(event, handler) {
            _get(Call.prototype.__proto__ || Object.getPrototypeOf(Call.prototype), "off", this).call(this, event, handler);
        }
        /**
         * @hidden
         */

    }, {
        key: "dispatchEvent",
        value: function dispatchEvent(e) {
            if (e.name === CallEvents_1.CallEvents.Updated || e.name === CallEvents_1.CallEvents.UpdateFailed) {
                this.settings.state = CallState.CONNECTED;
            }
            _get(Call.prototype.__proto__ || Object.getPrototypeOf(Call.prototype), "dispatchEvent", this).call(this, e);
        }
        /**
         * @hidden
         * @param headers
         * @param sdp
         * @returns {boolean}
         */

    }, {
        key: "onConnected",
        value: function onConnected(headers, sdp) {
            if (this.signalingConnected) {
                if (!this.checkState([CallState.PROGRESSING, CallState.ALERTING], 'onConnected')) return false;
                this.settings.state = CallState.CONNECTED;
                this.startTime = Date.now();
                this.dispatchEvent({ name: 'Connected', call: this, headers: headers });
                return true;
            }
        }
        /**
         * @hidden
         * @param headers
         * @param params
         * @returns {boolean}
         */

    }, {
        key: "onDisconnected",
        value: function onDisconnected(headers, params) {
            var _this3 = this;

            return new Promise(function (resolve, reject) {
                _this3.stopSharingScreen();
                if (!_this3.checkState([CallState.CONNECTED, CallState.ALERTING, CallState.PROGRESSING, CallState.UPDATING], 'onDisconnected')) {
                    reject(new Error("Call in the wrong state " + _this3.state()));
                    return false;
                }
                _this3.settings.state = CallState.ENDED;
                EndpointManager_1.EndpointManager.get().clear(_this3).then(function () {
                    resolve(true);
                    _this3.dispatchEvent({ name: 'Disconnected', call: _this3, headers: headers, params: params });
                }).catch(function (e) {
                    reject(new Error("Endpoint manager got some error: " + e.message));
                });
            });
        }
        /**
         * @hidden
         * @param code
         * @param reason
         * @param headers
         * @returns {boolean}
         */

    }, {
        key: "onFailed",
        value: function onFailed(code, reason, headers) {
            // if (!this.checkState(CallState.PROGRESSING, "onFailed"))
            //     return false;
            this.dispatchEvent({ name: 'Failed', call: this, headers: headers, code: code, reason: reason });
            this.settings.state = CallState.ENDED;
            EndpointManager_1.EndpointManager.get().clear(this);
            return true;
        }
        /**
         * @hidden
         * @returns {boolean}
         */

    }, {
        key: "onStopRinging",
        value: function onStopRinging() {
            if (!this.checkState([CallState.PROGRESSING, CallState.CONNECTED], 'onStopRinging')) return false;
            this.dispatchEvent({ name: 'ProgressToneStop', call: this });
            return true;
        }
        /**
         * @hidden
         * @returns {boolean}
         */

    }, {
        key: "onRingOut",
        value: function onRingOut() {
            if (!this.checkState(CallState.PROGRESSING, 'onRingOut')) return false;
            this.dispatchEvent({ name: 'ProgressToneStart', call: this });
            return true;
        }
        /**
         * @hidden
         * @returns {boolean}
         */

    }, {
        key: "onTransferComplete",
        value: function onTransferComplete() {
            if (!this.checkState(CallState.CONNECTED, 'onTransferComplete')) return false;
            this.dispatchEvent({ name: 'TransferComplete', call: this });
            return true;
        }
        /**
         * @hidden
         * @returns {boolean}
         */

    }, {
        key: "onTransferFailed",
        value: function onTransferFailed() {
            if (!this.checkState(CallState.CONNECTED, 'onTransferFailed')) return false;
            this.dispatchEvent({ name: 'TransferFailed', call: this });
            return true;
        }
        /**
         * @hidden
         * @param call
         * @param type
         * @param subType
         * @param body
         * @param headers
         * @returns {boolean}
         */

    }, {
        key: "onInfo",
        value: function onInfo(call, type, subType, body, headers) {
            if (call.stateValue == CallState.CONNECTED || call.stateValue == CallState.PROGRESSING || call.stateValue == CallState.ALERTING || call.stateValue == CallState.UPDATING) {
                var mimeType = type + '/' + subType;
                if (mimeType == Constants_1.Constants.ZINGAYA_IM_MIME_TYPE) {
                    this.dispatchEvent({ name: 'onSendMessage', call: this, text: body });
                } else if (mimeType == Constants_1.Constants.P2P_SPD_FRAG_MIME_TYPE) {
                    var candidates = JSON.parse(body);
                    for (var i in candidates) {
                        if (typeof call !== 'undefined' && typeof call.peerConnection !== 'undefined') call.peerConnection.addRemoteCandidate(candidates[i][1], candidates[i][0]);else this.log.info('Candidate skipped. Connection not created yet.');
                    }
                } else if (mimeType === Constants_1.Constants.VI_CONF_PARTICIPANT_INFO_ADDED || mimeType === Constants_1.Constants.VI_CONF_PARTICIPANT_INFO_REMOVED || mimeType === Constants_1.Constants.VI_CONF_PARTICIPANT_INFO_UPDATED) {
                    var endpointInfo = JSON.parse(body);
                    if (endpointInfo) {
                        EndpointManager_1.EndpointManager.get().endpointInfoUpdated(this, mimeType, endpointInfo);
                    } else {
                        this.log.info('WARN: Wrong endpointInfo');
                    }
                } else {
                    this.dispatchEvent({
                        name: 'InfoReceived',
                        call: this,
                        body: body,
                        headers: headers,
                        mimeType: mimeType
                    });
                }
                return true;
            } else {
                this.log.warning('received handleSIPInfo for call: ' + call.id() + ' in invalid state: ' + call.state());
            }
        }
        /**
         *
         * The method makes a call active, i.e. change the [active] flag to 'true'.
         *  A single call (either inbound or outbound) is active by default, all other calls are inactive and should be activated.
         * @param {boolean} flag
         * @returns {Promise<EventHandlers.Updated>}
         */

    }, {
        key: "setActive",
        value: function setActive(flag) {
            var _this4 = this;

            return new Promise(function (resolve, reject) {
                if (flag === _this4.settings.active) {
                    resolve({ name: CallEvents_1.CallEvents['Updated'], result: false, call: _this4 });
                    return;
                }
                if (BrowserSpecific_1.default.getWSVendor() === 'firefox') {
                    _this4.sendInfo(Constants_1.Constants.VI_HOLD_EMUL, JSON.stringify({ hold: !flag }));
                    resolve({ name: CallEvents_1.CallEvents['Updated'], call: _this4, result: true });
                    return;
                }
                if (_this4.settings.state == CallState.CONNECTED) {
                    _this4.settings.state = CallState.UPDATING;
                    _this4.settings.active = flag;
                    if (!flag) VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.hold, _this4.settings.id);else VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.unhold, _this4.settings.id);
                    resolve({ name: CallEvents_1.CallEvents['Updated'], result: true, call: _this4 });
                } else {
                    reject({ name: CallEvents_1.CallEvents.UpdateFailed, code: 11, call: _this4 });
                }
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "checkCallMode",
        value: function checkCallMode(mode) {
            return this.settings.mode == mode;
        }
        /**
         * @hidden
         */

    }, {
        key: "canStartSendingCandidates",
        value: function canStartSendingCandidates() {
            if (typeof this._peerConnection == 'undefined') this._peerConnection = PCFactory_1.PCFactory.get().peerConnections[this.settings.id];
            this._peerConnection.canStartSendingCandidates();
        }
    }, {
        key: "notifyICETimeout",

        /**
         * @hidden
         */
        value: function notifyICETimeout() {
            this.dispatchEvent({ name: 'ICETimeout', call: this });
        }
        /**
         *  Start/stop sending video from a call. In case of a remote participant uses a Web SDK client, it will receive either the [EndpointEvents.RemoteMediaAdded] or [EndpointEvents.RemoteMediaRemoved] event accordingly.
         * *IMPORTANT*: Safari browser for iOS requires a user interface for playing video during a call. It should be interactive element like an HTML "button" with "onclick" handler that calls "play" method on the "video" HTML element.
         * @param flag
         */

    }, {
        key: "sendVideo",
        value: function sendVideo(flag) {
            var _this5 = this;

            if (!this.peerConnection) {
                return new Promise(function (resolve, reject) {
                    resolve({ call: _this5, name: CallEvents_1.CallEvents[CallEvents_1.CallEvents.Updated], result: true });
                });
            }
            if (this.settings.videoDirections.sendVideo === flag) {
                return new Promise(function (resolve, reject) {
                    resolve({
                        call: _this5,
                        name: CallEvents_1.CallEvents[CallEvents_1.CallEvents.Updated],
                        result: true
                    });
                });
            }
            this.settings.videoDirections.sendVideo = flag;
            return Hardware_1.default.StreamManager.get().updateCallStream(this);
        }
        /**
         * @hidden
         */

    }, {
        key: "receiveVideo",
        value: function receiveVideo() {
            var _this6 = this;

            this.settings.state = CallState.UPDATING;
            return new Promise(function (resolve, reject) {
                if (_this6.settings.videoDirections.receiveVideo === true) {
                    reject();
                    return;
                }
                _this6.settings.videoDirections.receiveVideo = true;
                _this6._peerConnection.hdnFRS().then(resolve, reject);
            });
        }
        /**
         * @hidden
         * @param audio
         * @param video
         */

    }, {
        key: "sendMedia",
        value: function sendMedia(audio, video) {
            var _this7 = this;

            this.settings.state = CallState.UPDATING;
            if (typeof audio === 'undefined' || audio === null) audio = this.settings.audioDirections.sendAudio;
            if (typeof video === 'undefined' || video === null) video = this.settings.videoDirections.sendVideo;
            return this.peerConnection.sendMedia(audio, video).then(function (e) {
                if (typeof video !== 'undefined' && video !== null) {
                    _this7.settings.videoDirections.sendVideo = video;
                }
                if (typeof audio !== 'undefined' && audio !== null) {
                    _this7.settings.audioDirections.sendAudio = audio;
                }
                return e;
            });
        }
        /**
         * @hidden
         * @param flag
         */

    }, {
        key: "sendAudio",
        value: function sendAudio(flag) {
            var _this8 = this;

            var appConfig = Client_1.Client.getInstance().config();
            this.settings.audioDirections.sendAudio = flag;
            if (this.peerConnection.hasLocalAudio()) {
                this.peerConnection.muteMicrophone(!flag);
                return new Promise(function (resolve, reject) {
                    resolve({ call: _this8, name: CallEvents_1.CallEvents[CallEvents_1.CallEvents.Updated], result: true });
                });
            } else if (flag) {
                return this.sendMedia(null, flag);
            }
        }
        // New stream api
        /**
         * Get current PeerConnection LocalStream OR if set wiredLocal === false - try get newOne from UserMediaManager
         * @hidden
         * @deprecated
         */

    }, {
        key: "getLocalStream",
        value: function getLocalStream() {
            return Hardware_1.default.StreamManager.get().getCallStream(this);
        }
        /**
         * @hidden
         * @deprecated
         * @param stream
         * @returns {Promise<void>|Promise}
         */

    }, {
        key: "setLocalStream",
        value: function setLocalStream(stream) {
            //TODO: Not implemented
            return new Promise(function (resolve, reject) {
                reject(new Error('Not implemented'));
            });
        }
        /**
         * Enable screen sharing. Works in Chrome and Firefox. For Chrome, custom
         * extension must be created and installed from this template:
         * "https://github.com/voximplant/voximplant-chrome-extension". "matches"
         * section in the extension's "manifest.json" should be set to app website url(s).
         * Browser will ask user for a window or screen to share. Can be called multiple times
         * to share multiple windows.
         * @param {boolean} showLocalView if set to true, a screen sharing preview will be displayed locally in the same
         * way as it's done for video calls. It is false by default. *IMPORTANT*: Safari browser for iOS requires a user interface for playing video during a call. It should be interactive element like an HTML "button" with "onclick" handler that calls "play" method on the "video" HTML element.
         *
         */

    }, {
        key: "shareScreen",
        value: function shareScreen() {
            var _this9 = this;

            var showLocalView = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

            return new Promise(function (resolve, reject) {
                var sharingStreamList = Hardware_1.default.StreamManager.get()._getScreenSharing(_this9);
                if (sharingStreamList.length) {
                    _this9.log.warning('Screen sharing already active.');
                    reject(new Error('Screen sharing already active.'));
                }
                if (BrowserSpecific_1.default.isScreenSharingSupported) {
                    Hardware_1.default.StreamManager.get()._newScreenSharing(_this9, showLocalView).then(function (e) {
                        if (e.renderer) {
                            _this9.dispatchEvent({
                                name: CallEvents_1.CallEvents.LocalVideoStreamAdded,
                                type: e.renderer.kind,
                                element: e.renderer.element,
                                videoStream: e.renderer.stream
                            });
                        }
                        _this9.peerConnection.addCustomMedia(e.stream).then(function () {
                            resolve({ name: CallEvents_1.CallEvents.Updated, result: true, call: _this9 });
                        });
                    }).catch(function (error) {
                        _this9.log.warning(error.message);
                        reject(error);
                    });
                } else {
                    _this9.log.warning('Sorry, this browser does not support screen sharing.');
                    reject(new Error('Sorry, this browser does not support screen sharing.'));
                }
            });
        }
        /**
         * Stops screen sharing. If 'shareScreen' was called multiple times, this will stop
         * sharing for all windows/screens
         */

    }, {
        key: "stopSharingScreen",
        value: function stopSharingScreen() {
            var _this10 = this;

            return new Promise(function (resolve, reject) {
                var sharingStreamList = Hardware_1.default.StreamManager.get()._getScreenSharing(_this10);
                if (sharingStreamList) {
                    var totallShares = sharingStreamList.length;
                    if (totallShares === 0) resolve({ name: CallEvents_1.CallEvents.Updated, result: true, call: _this10 });
                    sharingStreamList.forEach(function (sharingStream) {
                        var needPC = !sharingStream.renderer;
                        Hardware_1.default.StreamManager.get()._clearScreenSharing(_this10, sharingStream).then(function () {
                            if (needPC) {
                                return _this10.peerConnection.removeCustomMedia(sharingStream.stream);
                            }
                        }).then(function () {
                            sharingStream.stream = undefined;
                            totallShares--;
                            if (totallShares <= 0) {
                                resolve({ name: CallEvents_1.CallEvents.Updated, result: true, call: _this10 });
                                return;
                            }
                        }).catch(function (e) {
                            _this10.log.warning(e.message);
                            reject(e);
                            return;
                        });
                    });
                } else {
                    _this10.log.warning('Sorry, screen sharing not started yet.');
                    reject(new Error('Sorry, screen sharing not started yet.'));
                }
            });
        }
        /**
         * @hidden
         * @deprecated
         * @returns {Promise<void>|Promise}
         */

    }, {
        key: "wireRemoteStream",
        value: function wireRemoteStream() {
            return new Promise(function (resolve, reject) {
                resolve();
                //     if (this.peerConnection)
                //         if (typeof (this.peerConnection.remoteStreams[0]) != "undefined") {
                //             this.peerConnection.wireRemoteStream(true).then(() => {
                //                 this.settings.wiredRemote = true;
                //                 resolve();
                //             }).catch(reject);
                //         } else
                //             reject(new Error('We have no remote MediaStream for this call yet'));
                //     else
                //         reject(new Error('We have no PC for this call yet'));
            });
        }
        // TODO: fix if many streams
        /**
         * @hidden
         * @returns {Promise<MediaStream>|Promise}
         */

    }, {
        key: "getRemoteAudioStreams",
        value: function getRemoteAudioStreams() {
            var _this11 = this;

            return new Promise(function (resolve, reject) {
                if (_this11.peerConnection) {
                    _this11.peerConnection.remoteStreams.forEach(function (stream) {
                        if (stream.getAudioTracks().length) {
                            resolve(new MediaStream(stream.getAudioTracks()));
                            return;
                        }
                    });
                    reject(new Error('We have no remote MediaStream for this call yet'));
                } else reject(new Error('We have no PC for this call yet'));
            });
        }
        // TODO: fix if many streams
        /**
         * @hidden
         * @deprecated
         * @returns {Promise<MediaStream>|Promise}
         */

    }, {
        key: "getRemoteVideoStreams",
        value: function getRemoteVideoStreams() {
            var _this12 = this;

            return new Promise(function (resolve, reject) {
                if (_this12.peerConnection) {
                    if (typeof _this12.peerConnection.remoteStreams[0] != 'undefined' && _this12.peerConnection.remoteStreams[0].getVideoTracks().length != 0) resolve(new MediaStream(_this12.peerConnection.remoteStreams[0].getVideoTracks()));else reject(new Error('We have no remote MediaStream for this call yet'));
                } else reject(new Error('We have no PC for this call yet'));
            });
        }
        /**
         * get wired state for remote audio streams
         * @hidden
         * @deprecated
         * @returns {boolean}
         */

    }, {
        key: "getRemoteWiredState",
        value: function getRemoteWiredState() {
            return this.settings.wiredRemote;
        }
        /**
         * get wired state for local audio streams
         * @hidden
         * @deprecated
         * @returns {boolean}
         */

    }, {
        key: "getLocalWiredState",
        value: function getLocalWiredState() {
            return this.settings.wiredLocal;
        }
        /**
         * Use specified audio output , use [audioOutputs] to get the list of available audio output
         * @param {String} id Id of the audio source
         * @hidden
         * @deprecated
         */

    }, {
        key: "useAudioOutput",
        value: function useAudioOutput(id) {
            EndpointManager_1.EndpointManager.get().useAudioOutput(this, id);
            return;
        }
        /**
         * Returns HTML audio element's id for the audio call
         * @returns string
         * @deprecated
         * @hidden
         */

    }, {
        key: "getAudioElementId",
        value: function getAudioElementId() {
            if (this._peerConnection.remoteStreams.length = 0) return null;
            if (this._peerConnection.remoteStreams[0].getAudioTracks().length = 0) return null;
            return this._peerConnection.remoteStreams[0].getAudioTracks()[0].id;
        }
        /**
         * For testing and debug
         * @hidden
         */

    }, {
        key: "getDirections",
        value: function getDirections() {
            if (typeof this.peerConnection !== 'undefined') return this.peerConnection.getDirections();
        }
        /**
         * For testing and debug
         * @hidden
         */

    }, {
        key: "getStreamActivity",
        value: function getStreamActivity() {
            return {};
            // if(typeof this.peerConnection !=="undefined")
            //     return this.peerConnection.getStreamActivity();
        }
        /**
         * @hidden
         */

    }, {
        key: "hdnFRS",
        value: function hdnFRS() {
            this.peerConnection._hdnFRS();
        }
        /**
         * @hidden
         */

    }, {
        key: "hdnFRSPrep",
        value: function hdnFRSPrep() {
            var _this13 = this;

            if (typeof this.peerConnection !== 'undefined') this.peerConnection._hdnFRSPrep();else setTimeout(function () {
                _this13.hdnFRSPrep();
            }, 1000);
        }
        /**
         * @hidden
         * @param headers
         * @param sdp
         */

    }, {
        key: "runIncomingReInvite",
        value: function runIncomingReInvite(headers, sdp) {
            var _this14 = this;

            if (this.settings.state === CallState.UPDATING) {
                VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.rejectReInvite, this.settings.id, {});
            } else {
                this.settings.state = CallState.UPDATING;
                var hasVideo = CallManager_1.CallManager.get().isSDPHasVideo(sdp);
                this.peerConnection.handleReinvite(headers, sdp, hasVideo).then(function () {
                    _this14.peerConnection.restoreMute();
                });
            }
        }
        /**
         * @hidden
         * @param state
         */

    }, {
        key: "setActiveForce",
        value: function setActiveForce(state) {
            this.settings.active = state;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Call';
        }
        /**
         * Get the call duration
         * @return the call duration in milliseconds
         */

    }, {
        key: "getCallDuration",
        value: function getCallDuration() {
            return Date.now() - this.startTime;
        }
        /**
         * Get all current [Endpoints] in the call.
         * @returns {Endpoint[]}
         */

    }, {
        key: "getEndpoints",
        value: function getEndpoints() {
            return EndpointManager_1.EndpointManager.get().getEndpoints(this);
        }
        /**
         *
         * @param validState
         * @param functionName
         * @returns {boolean}
         * @hidden
         */

    }, {
        key: "checkState",
        value: function checkState(validState, functionName) {
            if (validState) {
                if (typeof validState != 'string') {
                    var valid = false;
                    var validStateList = validState;
                    for (var i = 0; i < validStateList.length; i++) {
                        if (validStateList[i] == this.settings.state) {
                            valid = true;
                        }
                    }
                    if (!valid) {
                        this.log.warning('Received ' + functionName + ' in invalid state ' + this.settings.state);
                        return false;
                    }
                } else if (this.settings.state != validState) {
                    this.log.warning('Received ' + functionName + ' in invalid state ' + this.settings.state);
                    return false;
                }
            }
            return true;
        }
    }, {
        key: "promise",
        get: function get() {
            return this._promise;
        }
        /**
         * @hidden
         * @returns {PeerConnection}
         */

    }, {
        key: "peerConnection",
        get: function get() {
            return this._peerConnection;
        }
        /**
         * @hidden
         * @param peerConnection
         */
        ,
        set: function set(peerConnection) {
            this._peerConnection = peerConnection;
        }
        /**
         * @hidden
         * @returns {CallState}
         */

    }, {
        key: "stateValue",
        get: function get() {
            return this.settings.state;
        }
    }]);

    return Call;
}(EventTarget_1.EventTarget);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "number", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "displayName", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "headers", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "active", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "state", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "answer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "decline", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "reject", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "hangup", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "sendTone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "mutePlayback", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "unmutePlayback", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "restoreRMute", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "muteMicrophone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "unmuteMicrophone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "showRemoteVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "setRemoteVideoPosition", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "setRemoteVideoSize", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "sendInfo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "sendMessage", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "setVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getVideoElementId", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "addEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "on", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "removeEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "off", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "dispatchEvent", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onConnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onDisconnected", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onStopRinging", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onRingOut", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onTransferComplete", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onTransferFailed", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "onInfo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "setActive", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "checkCallMode", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "canStartSendingCandidates", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "notifyICETimeout", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "sendVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "receiveVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "sendAudio", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getLocalStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "setLocalStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "shareScreen", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "stopSharingScreen", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "wireRemoteStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getRemoteAudioStreams", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getRemoteVideoStreams", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getRemoteWiredState", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "getLocalWiredState", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "useAudioOutput", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "getAudioElementId", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "getStreamActivity", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "hdnFRS", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "hdnFRSPrep", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "runIncomingReInvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CLIENT)], Call.prototype, "setActiveForce", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], Call.prototype, "checkState", null);
exports.Call = Call;

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
/**
 * @hidden
 */

var EventTarget = function () {
    function EventTarget() {
        _classCallCheck(this, EventTarget);

        /**
         * @hidden
         * @type {{}}
         */
        this.eventListeners = {};
        /**
         * @hidden
         * @type {{}}
         */
        this.defaultEventListeners = {};
    }
    /**
     * @hidden
     * @deprecated
     * @param {EventType} event
     * @param {Function} handler
     */


    _createClass(EventTarget, [{
        key: "addEventListener",
        value: function addEventListener(event, handler) {
            this.on(event, handler);
        }
        /**
         * @hidden
         * @param {EventType} event
         * @param {Function} handler
         */

    }, {
        key: "addDefaultEventListener",
        value: function addDefaultEventListener(event, handler) {
            this.defaultEventListeners[event] = handler;
        }
        /**
         * @hidden
         * @param {EventType} event
         */

    }, {
        key: "removeDefaultEventListener",
        value: function removeDefaultEventListener(event) {
            this.defaultEventListeners[event] = undefined;
        }
        /**
         * @hidden
         * @param e
         */

    }, {
        key: "dispatchEvent",
        value: function dispatchEvent(e) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.UTILS, '', Logger_1.LogLevel.INFO, e.name + " dispatched");
            var event = e.name;
            if (typeof this.eventListeners[event] != 'undefined') {
                for (var i = 0; i < this.eventListeners[event].length; i++) {
                    if (typeof this.eventListeners[event][i] == 'function') {
                        try {
                            this.eventListeners[event][i](e);
                        } catch (e) {
                            console.error(e);
                            // LogManager.get().writeMessage(LogCategory.UTILS, '',
                            //   LogLevel.ERROR,
                            //   `There is some error on the ${e.name} event listener function: ${e.message}.`);
                        }
                    }
                }
            }
            if (typeof this.eventListeners[event] === 'undefined' || this.eventListeners[event].length == 0) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.UTILS, '', Logger_1.LogLevel.INFO, "The " + e.name + " event dispatched, but no handler registered for this event type.");
                if (this.defaultEventListeners[event]) this.defaultEventListeners[event](e);
            }
        }
        /**
         * @hidden
         * @deprecated
         * @param {EventType} event
         * @param {Function} handler
         */

    }, {
        key: "removeEventListener",
        value: function removeEventListener(event, handler) {
            this.off(event, handler);
        }
    }, {
        key: "on",
        value: function on(event, handler) {
            if (typeof this.eventListeners[event] == 'undefined') this.eventListeners[event] = [];
            this.eventListeners[event].push(handler);
        }
    }, {
        key: "off",
        value: function off(event, handler) {
            if (typeof this.eventListeners[event] == 'undefined') return;
            if (typeof handler === 'function') {
                for (var i = 0; i < this.eventListeners[event].length; i++) {
                    if (this.eventListeners[event][i] == handler) {
                        this.eventListeners[event].splice(i, 1);
                        break;
                    }
                }
            } else {
                this.eventListeners[event] = [];
            }
        }
    }]);

    return EventTarget;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "addEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "addDefaultEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "removeDefaultEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "removeEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "on", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.EVENTTARGET)], EventTarget.prototype, "off", null);
exports.EventTarget = EventTarget;

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var MsgEnums_1 = __webpack_require__(16);
var VoxSignaling_1 = __webpack_require__(2);
var EventTarget_1 = __webpack_require__(14);
/**
 * Created by irbisadm on 24/10/2016.
 * @hidden
 */

var MsgSignaling = function (_EventTarget_1$EventT) {
    _inherits(MsgSignaling, _EventTarget_1$EventT);

    function MsgSignaling() {
        _classCallCheck(this, MsgSignaling);

        var _this = _possibleConstructorReturn(this, (MsgSignaling.__proto__ || Object.getPrototypeOf(MsgSignaling)).call(this));

        if (MsgSignaling.instance) {
            throw new Error("Error - use Client.getMessagingInstance()");
        }
        _this.query = [];
        setInterval(function () {
            _this.updateQuery();
        }, 220);
        return _this;
    }
    /**
     * Core event handler
     * @hidden
     * @param parsedData
     */


    _createClass(MsgSignaling, [{
        key: "handleWsData",
        value: function handleWsData(parsedData) {
            var validEvents = ["onCreateConversation", "onEditConversation", "onRemoveConversation", "onJoinConversation", "onLeaveConversation", "onGetConversation", "onSendMessage", "onEditMessage", "onRemoveMessage", "onTyping", "onRetransmitEvents", "onEditUser", "onGetUser", "isRead", "isDelivered", "onError", "onSubscribe", "onUnSubscribe", "onSetStatus"];
            if (validEvents.indexOf(parsedData.event) != -1) this.dispatchEvent(parsedData.event, parsedData);else throw new Error('Unknown messaging event ' + parsedData.event + ' with payload ' + JSON.stringify(parsedData.payload));
        }
    }, {
        key: "sendPayload",

        /**
         * Core messaging sender
         * @param event
         * @param payload
         * @returns {boolean}
         */
        value: function sendPayload(event, payload) {
            var rawTemplate = {
                service: MsgEnums_1.MsgService.Chat,
                event: event,
                payload: payload
            };
            this.query.push(rawTemplate);
            return true;
        }
    }, {
        key: "updateQuery",
        value: function updateQuery() {
            if (this.query.length) {
                var item = this.query.splice(0, 1);
                VoxSignaling_1.VoxSignaling.get().sendRaw(item[0]);
            }
        }
    }, {
        key: "dispatchEvent",
        value: function dispatchEvent(event, data) {
            if (typeof this.eventListeners[event] != 'undefined') for (var i = 0; i < this.eventListeners[event].length; i++) {
                if (typeof this.eventListeners[event][i] == "function") this.eventListeners[event][i](data.payload);
            }
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'MsgSignaling';
        }
    }], [{
        key: "get",
        value: function get() {
            MsgSignaling.instance = MsgSignaling.instance || new MsgSignaling();
            return MsgSignaling.instance;
        }
    }]);

    return MsgSignaling;
}(EventTarget_1.EventTarget);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], MsgSignaling.prototype, "handleWsData", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], MsgSignaling.prototype, "sendPayload", null);
exports.MsgSignaling = MsgSignaling;

/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Created by irbisadm on 22/09/16.
 */
/**
 * @hidden
 */
var MsgService;
(function (MsgService) {
  MsgService[MsgService["Chat"] = "chat"] = "Chat";
})(MsgService = exports.MsgService || (exports.MsgService = {}));
/**
 * @hidden
 */
var MsgAction;
(function (MsgAction) {
  MsgAction[MsgAction["createConversation"] = "createConversation"] = "createConversation";
  MsgAction[MsgAction["editConversation"] = "editConversation"] = "editConversation";
  MsgAction[MsgAction["removeConversation"] = "removeConversation"] = "removeConversation";
  MsgAction[MsgAction["joinConversation"] = "joinConversation"] = "joinConversation";
  MsgAction[MsgAction["leaveConversation"] = "leaveConversation"] = "leaveConversation";
  MsgAction[MsgAction["getConversation"] = "getConversation"] = "getConversation";
  MsgAction[MsgAction["getConversations"] = "getConversations"] = "getConversations";
  MsgAction[MsgAction["sendMessage"] = "sendMessage"] = "sendMessage";
  MsgAction[MsgAction["editMessage"] = "editMessage"] = "editMessage";
  MsgAction[MsgAction["removeMessage"] = "removeMessage"] = "removeMessage";
  MsgAction[MsgAction["typingMessage"] = "typingMessage"] = "typingMessage";
  MsgAction[MsgAction["editUser"] = "editUser"] = "editUser";
  MsgAction[MsgAction["getUser"] = "getUser"] = "getUser";
  MsgAction[MsgAction["getUsers"] = "getUsers"] = "getUsers";
  MsgAction[MsgAction["retransmitEvents"] = "retransmitEvents"] = "retransmitEvents";
  MsgAction[MsgAction["isRead"] = "isRead"] = "isRead";
  MsgAction[MsgAction["isDelivered"] = "isDelivered"] = "isDelivered";
  MsgAction[MsgAction["addParticipants"] = "addParticipants"] = "addParticipants";
  MsgAction[MsgAction["editParticipants"] = "editParticipants"] = "editParticipants";
  MsgAction[MsgAction["removeParticipants"] = "removeParticipants"] = "removeParticipants";
  MsgAction[MsgAction["addModerators"] = "addModerators"] = "addModerators";
  MsgAction[MsgAction["removeModerators"] = "removeModerators"] = "removeModerators";
  MsgAction[MsgAction["subscribe"] = "subscribe"] = "subscribe";
  MsgAction[MsgAction["unsubscribe"] = "unsubscribe"] = "unsubscribe";
  MsgAction[MsgAction["setStatus"] = "setStatus"] = "setStatus";
})(MsgAction = exports.MsgAction || (exports.MsgAction = {}));
/**
 * @hidden
 */
var MsgEvent;
(function (MsgEvent) {
  MsgEvent[MsgEvent["onCreateConversation"] = "onCreateConversation"] = "onCreateConversation";
  MsgEvent[MsgEvent["onEditConversation"] = "onEditConversation"] = "onEditConversation";
  MsgEvent[MsgEvent["onRemoveConversation"] = "onRemoveConversation"] = "onRemoveConversation";
  MsgEvent[MsgEvent["onJoinConversation"] = "onJoinConversation"] = "onJoinConversation";
  MsgEvent[MsgEvent["onLeaveConversation"] = "onLeaveConversation"] = "onLeaveConversation";
  MsgEvent[MsgEvent["onGetConversation"] = "onGetConversation"] = "onGetConversation";
  MsgEvent[MsgEvent["onSendMessage"] = "onSendMessage"] = "onSendMessage";
  MsgEvent[MsgEvent["onEditMessage"] = "onEditMessage"] = "onEditMessage";
  MsgEvent[MsgEvent["onRemoveMessage"] = "onRemoveMessage"] = "onRemoveMessage";
  MsgEvent[MsgEvent["onTyping"] = "onTyping"] = "onTyping";
  MsgEvent[MsgEvent["onRetransmitEvents"] = "onRetransmitEvents"] = "onRetransmitEvents";
  MsgEvent[MsgEvent["onEditUser"] = "onEditUser"] = "onEditUser";
  MsgEvent[MsgEvent["onGetUser"] = "onGetUser"] = "onGetUser";
  MsgEvent[MsgEvent["onError"] = "onError"] = "onError";
  MsgEvent[MsgEvent["isRead"] = "isRead"] = "isRead";
  MsgEvent[MsgEvent["isDelivered"] = "isDelivered"] = "isDelivered";
  MsgEvent[MsgEvent["onsubscribe"] = "onsubscribe"] = "onsubscribe";
  MsgEvent[MsgEvent["onUnSubscribe"] = "onUnSubscribe"] = "onUnSubscribe";
  MsgEvent[MsgEvent["onSetStatus"] = "onSetStatus"] = "onSetStatus";
})(MsgEvent = exports.MsgEvent || (exports.MsgEvent = {}));

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

Object.defineProperty(exports, "__esModule", { value: true });
var Implement = __webpack_require__(68);
/**
 * Messaging allows exchanging instant messages between 2 or more participants.
 * Messaging supports text and metadata. The conversation doesn't bind or depend on the audio/video calls, but there is a possibility to integrate messaging in audio/video calls.
 *
 * FEATURES:
 * 1. messaging is the separate part of WEB SDK, but it uses the [Client.login], [Client.loginWithOneTimeKey] and [Client.loginWithToken] methods - in brief, if a user was already logged in he can use messaging functionality.
 * 1. messaging doesn't use backend JS scenario at all
 * See the minimum example to create messaging and to be able start a conversation:
 * @example
 * const voxSDK = VoxImplant.getInstance();
 * voxSDK.init({micRequired:false})
 *   .then(()=>voxSDK.connect())
 *   .then(()=>sdk.login('foo@bar.baz.voximplant.com', 'secretpass'))
 *   .then((e)=>{
 *     if(!e.result)
 *       throw e.message;
 *     console.log('[Voximplant] Ready, connected and logged in.');
 *     return VoxImplant.getMessenger();
 *   })
 *   .then((messaging)=>{
 *     messaging.on(VoxImplant.Messaging.MessengerEvents.CreateConversation,onCreateConversation);
 *     messaging.createConversation([]);
 *   })
 *   .catch(e=>console.error('[Voximplant] Oops! Something went wrong',e));
 *
 * function onCreateConversation(e){
 *   console.log(`[Voximplant] New conversation here! ID:${e.conversation.uuid}`);
 *   e.conversation.sendMessage('Hello world!');
 * }
 *
 * function onSendMessage(e){
 *   console.log(`[Voximplant] Message from ${e.message.sender}: ${e.message.text}`)
 * }
 *
 */
var Messaging;
(function (Messaging) {
  /**
   * Messaging supports these events. See the details within a particular event.
   */
  var EventHandlers = void 0;
  (function (EventHandlers) {
    ;
    ;
    ;
    ;
    ;
    ;
    ;
    ;
    ;
  })(EventHandlers = Messaging.EventHandlers || (Messaging.EventHandlers = {}));
  /**
   * @hidden
   * @deprecated
   */
  var MessengerEventsCallbacks = void 0;
  (function (MessengerEventsCallbacks) {
    ;
    ;
    ;
    ;
    ;
    ;
    ;
    ;
    ;
  })(MessengerEventsCallbacks = Messaging.MessengerEventsCallbacks || (Messaging.MessengerEventsCallbacks = {}));
  /**
   * Conversation instance. Created by the [Messenger.createConversation] method. Used to send messages, add or remove users, change moderators list etc.
   */

  var Conversation = function (_Implement$Conversati) {
    _inherits(Conversation, _Implement$Conversati);

    function Conversation() {
      _classCallCheck(this, Conversation);

      return _possibleConstructorReturn(this, (Conversation.__proto__ || Object.getPrototypeOf(Conversation)).apply(this, arguments));
    }

    return Conversation;
  }(Implement.Conversation);

  Messaging.Conversation = Conversation;
  ;
  /**
   * Describes single message. Received via the [MessengerEvents.SendMessage] or [MessengerEvents.EditMessage] events and used to serialize or edit the message.
   */

  var Message = function (_Implement$Message) {
    _inherits(Message, _Implement$Message);

    function Message() {
      _classCallCheck(this, Message);

      return _possibleConstructorReturn(this, (Message.__proto__ || Object.getPrototypeOf(Message)).apply(this, arguments));
    }

    return Message;
  }(Implement.Message);

  Messaging.Message = Message;
  ;
  /**
   * Messenger class is used to control messaging functions. Can't be instantiated directly (singleton), please use [getMessenger] to get the class instance.
   */

  var Messenger = function (_Implement$Messenger) {
    _inherits(Messenger, _Implement$Messenger);

    function Messenger() {
      _classCallCheck(this, Messenger);

      return _possibleConstructorReturn(this, (Messenger.__proto__ || Object.getPrototypeOf(Messenger)).apply(this, arguments));
    }

    return Messenger;
  }(Implement.Messenger);

  Messaging.Messenger = Messenger;
  ;
  ;
  ;
  ;
  ;
  ;
  ;
  var MessengerEvents = void 0;
  (function (MessengerEvents) {
    /**
     * New conversation created.
     * You receive this event when anybody created a new conversation with the current user in participant array. Also this event dispatch on conversation creator.
     */
    MessengerEvents["CreateConversation"] = "CreateConversation";
    /**
     * Conversation properties were modified.
     */
    MessengerEvents["EditConversation"] = "EditConversation";
    /**
     * The conversation was removed.
     */
    MessengerEvents["RemoveConversation"] = "RemoveConversation";
    /**
     * Conversation description is received. Triggered in response to the 'getConversation'.
     */
    MessengerEvents["GetConversation"] = "GetConversation";
    /**
     * Event is triggered when a new message is received as a result of the [Conversation.sendMessage] method call.
     */
    MessengerEvents["SendMessage"] = "SendMessage";
    /**
     * Message was edited.
     */
    MessengerEvents["EditMessage"] = "EditMessage";
    /**
     * Message was removed.
     */
    MessengerEvents["RemoveMessage"] = "RemoveMessage";
    /**
     * Information that some user is typing something is received. Triggered in response to the 'typing' called by any user.
     */
    MessengerEvents["Typing"] = "Typing";
    /**
     * Dispatch when [Messenger.editUser] successful done into cloud. Triggered only for users specified in the 'subscribe' method call.
     */
    MessengerEvents["EditUser"] = "EditUser";
    /**
     * Return user, requested in [Messenger.getUser] function
     */
    MessengerEvents["GetUser"] = "GetUser";
    /**
     * Event is triggered in case of an error while creating a conversation. See the details in the [MessengerEventsCallbacks.ErrorEvent] interface.
     */
    MessengerEvents["Error"] = "Error";
    /**
     * Event is triggered after [Conversation.retransmitEvents] method is called on some conversation for this SDK instance.
     */
    MessengerEvents["RetransmitEvents"] = "RetransmitEvents";
    /**
     *  Event is triggered after another device with same logged in user called the [Conversation.markAsRead] method.
     */
    MessengerEvents["Read"] = "Read";
    /**
     * Event is triggered after another device with same logged in user called the [Conversation.markAsDelivered] method.
     */
    MessengerEvents["Delivered"] = "Delivered";
    /**
     * Event is triggered after the [Messenger.subscribe] method is called.
     */
    MessengerEvents["Subscribe"] = "Subscribe";
    /**
     * Event is triggered after the [Messenger.unsubscribe] method is called.
     */
    MessengerEvents["Unsubscribe"] = "Unsubscribe";
    /**
     * Event is triggered after the user presence state has changed.
     */
    MessengerEvents["SetStatus"] = "SetStatus";
  })(MessengerEvents = Messaging.MessengerEvents || (Messaging.MessengerEvents = {}));
  /**
   * Available methods to manipulate the messaging flow. Note if the action triggers any of [MessengerEvents], the action's name will be set as a value of [ConversationEvent.messengerAction].
   */
  var MessengerAction = void 0;
  (function (MessengerAction) {
    MessengerAction[MessengerAction["createConversation"] = 'createConversation'] = "createConversation";
    MessengerAction[MessengerAction["editConversation"] = 'editConversation'] = "editConversation";
    MessengerAction[MessengerAction["removeConversation"] = 'removeConversation'] = "removeConversation";
    MessengerAction[MessengerAction["joinConversation"] = 'joinConversation'] = "joinConversation";
    MessengerAction[MessengerAction["leaveConversation"] = 'leaveConversation'] = "leaveConversation";
    MessengerAction[MessengerAction["getConversation"] = 'getConversation'] = "getConversation";
    MessengerAction[MessengerAction["getConversations"] = 'getConversations'] = "getConversations";
    MessengerAction[MessengerAction["sendMessage"] = 'sendMessage'] = "sendMessage";
    MessengerAction[MessengerAction["editMessage"] = 'editMessage'] = "editMessage";
    MessengerAction[MessengerAction["removeMessage"] = 'removeMessage'] = "removeMessage";
    MessengerAction[MessengerAction["typingMessage"] = 'typingMessage'] = "typingMessage";
    MessengerAction[MessengerAction["editUser"] = 'editUser'] = "editUser";
    MessengerAction[MessengerAction["getUser"] = 'getUser'] = "getUser";
    MessengerAction[MessengerAction["getUsers"] = 'getUsers'] = "getUsers";
    MessengerAction[MessengerAction["retransmitEvents"] = 'retransmitEvents'] = "retransmitEvents";
    MessengerAction[MessengerAction["isRead"] = 'isRead'] = "isRead";
    MessengerAction[MessengerAction["isDelivered"] = 'isDelivered'] = "isDelivered";
    MessengerAction[MessengerAction["addParticipants"] = 'addParticipants'] = "addParticipants";
    MessengerAction[MessengerAction["editParticipants"] = 'editParticipants'] = "editParticipants";
    MessengerAction[MessengerAction["removeParticipants"] = 'removeParticipants'] = "removeParticipants";
    MessengerAction[MessengerAction["addModerators"] = 'addModerators'] = "addModerators";
    MessengerAction[MessengerAction["removeModerators"] = 'removeModerators'] = "removeModerators";
    MessengerAction[MessengerAction["subscribe"] = 'subscribe'] = "subscribe";
    MessengerAction[MessengerAction["unsubscribe"] = 'unsubscribe'] = "unsubscribe";
    MessengerAction[MessengerAction["setStatus"] = 'setStatus'] = "setStatus";
  })(MessengerAction = Messaging.MessengerAction || (Messaging.MessengerAction = {}));
  /**
   *
   */
  var MessengerError = void 0;
  (function (MessengerError) {
    /**
     * Wrong transport message structure
     */
    MessengerError[MessengerError["Error_1"] = '1'] = "Error_1";
    /**
     * Unknown event name
     */
    MessengerError[MessengerError["Error_2"] = '2'] = "Error_2";
    /**
     * User not auth
     */
    MessengerError[MessengerError["Error_3"] = '3'] = "Error_3";
    /**
     * Wrong message structure
     */
    MessengerError[MessengerError["Error_4"] = '4'] = "Error_4";
    /**
     * Conversation not found or user not in participant list
     */
    MessengerError[MessengerError["Error_5"] = '5'] = "Error_5";
    /**
     * Conversation not found or user can't moderate conversation
     */
    MessengerError[MessengerError["Error_6"] = '6'] = "Error_6";
    /**
     * Conversation already exists
     */
    MessengerError[MessengerError["Error_7"] = '7'] = "Error_7";
    /**
     * Conversation does not exist
     */
    MessengerError[MessengerError["Error_8"] = '8'] = "Error_8";
    /**
     * Message already exists
     */
    MessengerError[MessengerError["Error_9"] = '9'] = "Error_9";
    /**
     * Message does not exist
     */
    MessengerError[MessengerError["Error_10"] = '10'] = "Error_10";
    /**
     * Message was deleted
     */
    MessengerError[MessengerError["Error_11"] = '11'] = "Error_11";
    /**
     * ACL error
     */
    MessengerError[MessengerError["Error_12"] = '12'] = "Error_12";
    /**
     * User already in participant list
     */
    MessengerError[MessengerError["Error_13"] = '13'] = "Error_13";
    /**
     * No rights to edit user
     */
    MessengerError[MessengerError["Error_14"] = '14'] = "Error_14";
    /**
     * Public join is not available in this conversation
     */
    MessengerError[MessengerError["Error_15"] = '15'] = "Error_15";
    /**
     * Conversation was deleted
     */
    MessengerError[MessengerError["Error_16"] = '16'] = "Error_16";
    /**
     * Conversation is distinct
     */
    MessengerError[MessengerError["Error_17"] = '17'] = "Error_17";
    /**
     * User validation Error
     */
    MessengerError[MessengerError["Error_18"] = '18'] = "Error_18";
    /**
     * Lists mismatch
     */
    MessengerError[MessengerError["Error_19"] = '19'] = "Error_19";
    /**
     * Range larger then allowed by service
     */
    MessengerError[MessengerError["Error_21"] = '21'] = "Error_21";
    /**
     * Number of requested objects is larger then allowed by service
     */
    MessengerError[MessengerError["Error_22"] = '22'] = "Error_22";
    /**
     * Message size so large
     */
    MessengerError[MessengerError["Error_23"] = '23'] = "Error_23";
    /**
     * Seq is too big
     */
    MessengerError[MessengerError["Error_24"] = '24'] = "Error_24";
    /**
     * IM service not available
     */
    MessengerError[MessengerError["Error_30"] = '30'] = "Error_30";
    /**
     * Internal error
     */
    MessengerError[MessengerError["Error_500"] = '500'] = "Error_500";
    /**
     * Oops! Something went wrong
     */
    MessengerError[MessengerError["Error_777"] = '777'] = "Error_777";
  })(MessengerError = Messaging.MessengerError || (Messaging.MessengerError = {}));
})(Messaging = exports.Messaging || (exports.Messaging = {}));
exports.default = Messaging;

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * The events that are triggered by [Client] instance. See the [getInstance] method.
 *
 *
 * Example:
 * ``` js
 * var vox = VoxImplant.getInstance();
 * vox.init({micRequired: true});
 * vox.addEventListener(VoxImplant.Events.SDKReady, function() {
 *   vox.connect();
 * });
 * ```
 */
var Events;
(function (Events) {
  /**
   *    The event is triggered after SDK was successfully initialized after the [Client.init] function call
   *
   *    Handler function receives [EventHandlers.SDKReady] object as an argument.
   */
  Events[Events["SDKReady"] = 'SDKReady'] = "SDKReady";
  /**
   *    The event is triggered after connection to VoxImplant Cloud was established successfully.
   *    See [Client.connect] function
   *
   *    Handler function receives no arguments.
   */
  Events[Events["ConnectionEstablished"] = 'ConnectionEstablished'] = "ConnectionEstablished";
  /**
   *    The event is triggered if a connection to the VoxImplant cloud couldn't be established.
   *    See [Client.connect] function
   *
   * Handler function receives the [EventHandlers.ConnectionFailed] object as an argument.
   */
  Events[Events["ConnectionFailed"] = 'ConnectionFailed'] = "ConnectionFailed";
  /**
   * The event is triggered if a connection to VoxImplant Cloud was closed because of network problems.
   *
   *    See the [Client.connect] function
   *
   *    Handler function receives no arguments.
   */
  Events[Events["ConnectionClosed"] = 'ConnectionClosed'] = "ConnectionClosed";
  /**
   * The event is triggered after the [Client.login], [Client.loginWithOneTimeKey], [Client.requestOneTimeLoginKey] and Client.loginWithCode methods call.
   *
   * Handler function receives [EventHandlers.AuthResult] object as an argument.
   */
  Events[Events["AuthResult"] = 'AuthResult'] = "AuthResult";
  /**
   *   The event is triggered after the [LoginTokens.refreshToken] call
   *   Handler function receives the the [EventHandlers.AuthTokenResult] object as an argument.
   */
  Events[Events["RefreshTokenResult"] = 'RefreshTokenResult'] = "RefreshTokenResult";
  /**
   *    The event is triggered after sound playback was stopped.
   *
   *    See [Client.playToneScript]
   *    and [Client.stopPlayback] functions
   *
   *    Handler function receives no arguments.
   */
  Events[Events["PlaybackFinished"] = 'PlaybackFinished'] = "PlaybackFinished";
  /**
   * @hidden
   * @deprecated
   */
  Events[Events["MicAccessResult"] = 'MicAccessResult'] = "MicAccessResult";
  /**
   *    The event is triggered when there is a new incoming call to current user
   *
   *    Handler function receives [EventHandlers.IncomingCall] object as an argument.
   */
  Events[Events["IncomingCall"] = 'IncomingCall'] = "IncomingCall";
  /**
   * The event is triggered when audio and video sources information was updated.
   *    See the [Client.audioSources] and [Client.videoSources] for details
   * @hidden
   * @deprecated
   */
  Events[Events["SourcesInfoUpdated"] = 'SourcesInfoUpdated'] = "SourcesInfoUpdated";
  /**
   * @hidden
   * @deprecated
   */
  Events[Events["NetStatsReceived"] = 'NetStatsReceived'] = "NetStatsReceived";
  /**
   * @hidden
   */
  Events[Events["SIPRegistrationSuccessful"] = 'SIPRegistrationSuccessful'] = "SIPRegistrationSuccessful";
  /**
   * @hidden
   */
  Events[Events["SIPRegistrationFailed"] = 'SIPRegistrationFailed'] = "SIPRegistrationFailed";
  /**
   * The event is triggered when ACD status of current user changed from SDK or from inside the ACD service.
   */
  Events[Events["ACDStatusUpdated"] = 'ACDStatusUpdated'] = "ACDStatusUpdated";
})(Events = exports.Events || (exports.Events = {}));

/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var VoxSignaling_1 = __webpack_require__(2);
var RemoteFunction_1 = __webpack_require__(3);
/**
 * @hidden
 */

var SignalingDTMFSender = function () {
    function SignalingDTMFSender(_id) {
        _classCallCheck(this, SignalingDTMFSender);

        this._id = _id;
    }

    _createClass(SignalingDTMFSender, [{
        key: "insertDTMF",
        value: function insertDTMF(tones, duration, interToneGap) {
            var _this = this;

            tones.split('').forEach(function (key) {
                return _this.sendKey(key);
            });
        }
    }, {
        key: "sendKey",
        value: function sendKey(key) {
            var k = void 0;
            if (key == '*') k = 10;else if (key == '#') k = 11;else {
                k = parseInt(key);
            }
            if (k >= 0 || k <= 11) VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.sendDTMF, this._id, k);
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'SignalingDTMFSender';
        }
    }]);

    return SignalingDTMFSender;
}();

exports.SignalingDTMFSender = SignalingDTMFSender;

/***/ }),
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var Client_1 = __webpack_require__(1);
/**
 * @hidden
 */

var SDPMuggle = function () {
    function SDPMuggle() {
        _classCallCheck(this, SDPMuggle);
    }

    _createClass(SDPMuggle, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'SDPMuggle';
        }
    }], [{
        key: "detectDirections",
        value: function detectDirections(sdp) {
            var ret = [];
            var splitsdp = sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
            var currentSection = '';
            splitsdp.forEach(function (item) {
                if (item.indexOf('m=') === 0) {
                    var directionStr = item.substr(2);
                    currentSection = directionStr.split(' ')[0];
                }
                if (currentSection !== '' && (item === 'a=sendrecv' || item === 'a=sendonly' || item === 'a=recvonly' || item === 'a=inactive')) {
                    ret.push({ type: currentSection, direction: item.substr(2) });
                    currentSection = '';
                }
            });
            return ret;
        }
    }, {
        key: "removeTelephoneEvents",
        value: function removeTelephoneEvents(sdp) {
            if (sdp.sdp.indexOf('a=rtpmap:127 telephone-event/8000') !== -1) {
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
                var removenumber = -1;
                for (var i = 0; i < sdpLines.length; i++) {
                    if (sdpLines[i].indexOf('m=audio') !== -1) {
                        var line = sdpLines[i];
                        if (typeof line === 'string') sdpLines[i] = line.replace(' 127', '');
                    }
                    if (sdpLines[i].indexOf('a=rtpmap:127 telephone-event/8000') !== -1) removenumber = i;
                }
                sdpLines.splice(removenumber, 1);
                return new RTCSessionDescription({ sdp: sdpLines.join('\r\n') + '\r\n', type: sdp.type });
            }
            return sdp;
        }
    }, {
        key: "removeDoubleOpus",
        value: function removeDoubleOpus(sdp) {
            if (sdp.sdp.indexOf('a=rtpmap:109 opus') !== -1 && sdp.sdp.indexOf('a=rtpmap:111 opus') !== -1) {
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
                var removenumber = -1;
                for (var i = 0; i < sdpLines.length; i++) {
                    if (sdpLines[i].indexOf('m=audio') !== -1) {
                        var line = sdpLines[i];
                        if (typeof line === 'string') sdpLines[i] = line.replace(' 109', '');
                    }
                    if (sdpLines[i].indexOf('a=rtpmap:109 opus') !== -1) removenumber = i;
                }
                sdpLines.splice(removenumber, 1);
                return new RTCSessionDescription({ sdp: sdpLines.join('\r\n') + '\r\n', type: sdp.type });
            }
            return sdp;
        }
    }, {
        key: "removeTransportCC",
        value: function removeTransportCC(sdp) {
            if (sdp.sdp.indexOf('http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01') !== -1) {
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
                var removenumbers = [];
                sdpLines.forEach(function (item, index) {
                    if (item.indexOf('http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01') !== -1) removenumbers.unshift(index);
                });
                removenumbers.forEach(function (item) {
                    return sdpLines.splice(item, 1);
                });
                return new RTCSessionDescription({ sdp: sdpLines.join('\r\n') + '\r\n', type: sdp.type });
            }
            return sdp;
        }
    }, {
        key: "removeTIAS",
        value: function removeTIAS(sdp) {
            if (sdp.sdp.indexOf('b=TIAS:13888000') !== -1 || sdp.sdp.indexOf('b=AS:13888') !== -1) {
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
                var removenumbers = [];
                sdpLines.forEach(function (item, index) {
                    if (item.indexOf('b=TIAS:13888000') !== -1 || item.indexOf('b=AS:13888') !== -1) removenumbers.unshift(index);
                });
                removenumbers.forEach(function (item) {
                    return sdpLines.splice(item, 1);
                });
                sdp = { type: sdp.type, sdp: sdpLines.join('\r\n') + '\r\n' };
            }
            return sdp;
        }
    }, {
        key: "fixVideoRecieve",
        value: function fixVideoRecieve(sdp, recieveVideo) {
            var videoPosition = sdp.sdp.indexOf('m=video');
            if (videoPosition !== -1 && !recieveVideo) {
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
                var videoindex = null;
                sdpLines = sdpLines.map(function (item, index) {
                    if (videoindex === null) {
                        if (item.indexOf('m=video') !== -1) videoindex = index;
                    } else {
                        if (item === 'a=sendrecv') item = 'a=sendonly';else if (item === 'a=recvonly') item = 'a=inactive';
                    }
                    return item;
                });
                return new RTCSessionDescription({ sdp: sdpLines.join('\r\n') + '\r\n', type: sdp.type });
            }
            return sdp;
        }
    }, {
        key: "addSetupAttribute",
        value: function addSetupAttribute(sdp) {
            var setupPosition = sdp.indexOf('a=setup:');
            if (setupPosition == -1) {
                sdp += 'a=setup:actpass\r\n';
            }
            return sdp;
        }
    }, {
        key: "findTrackByMid",
        value: function findTrackByMid(remoteDescription, mid) {
            var lines = remoteDescription.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
            var sectionStart = -1;
            for (var i = 0; i < lines.length; i++) {
                if (lines[i].indexOf("a=mid:") !== -1 && sectionStart !== -1) {
                    break;
                }
                if (lines[i].indexOf("a=mid:" + mid) !== -1) {
                    sectionStart = i;
                }
                if (lines[i].indexOf("msid:") !== -1 && sectionStart !== -1) {
                    return lines[i].split(' ').slice(-1).pop();
                }
            }
            return '';
        }
    }, {
        key: "addXAS",
        value: function addXAS(sdp) {
            if (Client_1.Client.getInstance().config().experiments.xas && Client_1.Client.getInstance().config().experiments.xas) {
                var sdptext = sdp.sdp;
                var xas = Client_1.Client.getInstance().config().experiments.xas;
                if (typeof xas.as != 'undefined' && xas.as !== -1) {
                    sdptext = sdptext.replace(/(a=mid:video.*\r\n)/g, '$1b=AS:' + xas.as + '\r\n');
                }
                if (typeof xas.tias != 'undefined' && xas.tias !== -1) {
                    sdptext = sdptext.replace(/(a=mid:video.*\r\n)/g, '$1b=TIAS:' + xas.as + '\r\n');
                }
                return new RTCSessionDescription({ sdp: sdptext, type: sdp.type });
            }
            return sdp;
        }
    }, {
        key: "fixFFMIDBug",
        value: function fixFFMIDBug(sdp) {
            if (sdp.sdp.indexOf('a=mid') == -1) {
                var sdptext = sdp.sdp.replace(/(m=audio.*\r\n)/g, '$1a=mid:0\r\n');
                return new RTCSessionDescription({ sdp: sdptext, type: sdp.type });
            } else {
                return sdp;
            }
        }
    }, {
        key: "fixFMTP",
        value: function fixFMTP(sdp) {
            var lines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(SDPMuggle.validLine);
            var rCodecs = [];
            lines = lines.filter(function (line) {
                if (line.indexOf('a=fmtp') == 0) {
                    var splitedDescription = line.split(' ')[1];
                    var codec = splitedDescription.replace('apt=', '');
                    if (sdp.sdp.indexOf("a=rtpmap:" + codec) !== -1) return true;else {
                        rCodecs.push(splitedDescription[0].replace('a=fmtp:', ''));
                        return false;
                    }
                } else return true;
            }).filter(function (line) {
                if (line.indexOf('a=rtpmap:') !== -1) {
                    return rCodecs.indexOf(line.split(' ')[0].replace('a=rtpmap:', '')) === -1;
                } else return true;
            }).map(function (line) {
                if (line.indexOf('m=') !== -1) {
                    var parts = line.split(' ');
                    return parts.filter(function (i) {
                        return rCodecs.indexOf(i) === -1;
                    }).join(' ');
                } else return line;
            });
            return new RTCSessionDescription({ sdp: lines.join('\r\n') + '\r\n', type: sdp.type });
        }
    }]);

    return SDPMuggle;
}();

SDPMuggle.validLine = RegExp.prototype.test.bind(/^([a-z])=(.*)/);
exports.SDPMuggle = SDPMuggle;

/***/ }),
/* 21 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
/**
 * Created by irbisadm on 18/10/2016.
 */
/**
 * @hidden
 */
var CallstatsIoFabricUsage;
(function (CallstatsIoFabricUsage) {
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["multiplex"] = "multiplex"] = "multiplex";
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["audio"] = "audio"] = "audio";
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["video"] = "video"] = "video";
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["screen"] = "screen"] = "screen";
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["data"] = "data"] = "data";
    CallstatsIoFabricUsage[CallstatsIoFabricUsage["unbundled"] = "unbundled"] = "unbundled";
})(CallstatsIoFabricUsage = exports.CallstatsIoFabricUsage || (exports.CallstatsIoFabricUsage = {}));
/**
 * @hidden
 */
var CallstatsIoFabricEvent;
(function (CallstatsIoFabricEvent) {
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["fabricHold"] = "fabricHold"] = "fabricHold";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["fabricResume"] = "fabricResume"] = "fabricResume";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["audioMute"] = "audioMute"] = "audioMute";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["audioUnmute"] = "audioUnmute"] = "audioUnmute";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["videoPause"] = "videoPause"] = "videoPause";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["videoResume"] = "videoResume"] = "videoResume";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["fabricTerminated"] = "fabricTerminated"] = "fabricTerminated";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["screenShareStart"] = "screenShareStart"] = "screenShareStart";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["screenShareStop"] = "screenShareStop"] = "screenShareStop";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["dominantSpeaker"] = "dominantSpeaker"] = "dominantSpeaker";
    CallstatsIoFabricEvent[CallstatsIoFabricEvent["activeDeviceList"] = "activeDeviceList"] = "activeDeviceList";
})(CallstatsIoFabricEvent = exports.CallstatsIoFabricEvent || (exports.CallstatsIoFabricEvent = {}));
/**
 * @hidden
 */
var CallstatsioWrtcFuncNames;
(function (CallstatsioWrtcFuncNames) {
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["getUserMedia"] = "getUserMedia"] = "getUserMedia";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["createOffer"] = "createOffer"] = "createOffer";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["createAnswer"] = "createAnswer"] = "createAnswer";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["setLocalDescription"] = "setLocalDescription"] = "setLocalDescription";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["setRemoteDescription"] = "setRemoteDescription"] = "setRemoteDescription";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["addIceCandidate"] = "addIceCandidate"] = "addIceCandidate";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["iceConnectionFailure"] = "iceConnectionFailure"] = "iceConnectionFailure";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["signalingError"] = "signalingError"] = "signalingError";
    CallstatsioWrtcFuncNames[CallstatsioWrtcFuncNames["applicationLog"] = "applicationLog"] = "applicationLog";
})(CallstatsioWrtcFuncNames = exports.CallstatsioWrtcFuncNames || (exports.CallstatsioWrtcFuncNames = {}));
/**
 * @hidden
 */

var CallstatsIo = function () {
    function CallstatsIo(params) {
        _classCallCheck(this, CallstatsIo);

        this._params = params;
        this.inited = false;
        this.pendingFabric = [];
        var x_window = window;
        if (typeof x_window.callstats != "undefined") this.callstats = new x_window.callstats(null, x_window.io);
    }

    _createClass(CallstatsIo, [{
        key: "init",
        value: function init(userId) {
            var _this = this;

            if (!CallstatsIo.moduleEnabled) return false;
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.UTILS, "[CallstatsIo]", Logger_1.LogLevel.INFO, " Callstats.io SDK initialization start");
            this.callstats.initialize(this._params.AppID, this._params.AppSecret, userId, function () {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.UTILS, "[CallstatsIo]", Logger_1.LogLevel.INFO, " Callstats.io SDK initialization successful");
                _this.inited = true;
                _this.pendingFabric.map(function (item) {
                    _this.callstats.addNewFabric(item.pc, item.remoteUser, item.fabricUsage, item.callID);
                });
            }, function () {}, this.packParams());
            return true;
        }
    }, {
        key: "packParams",
        value: function packParams() {
            var ax = {};
            if (this._params.disableBeforeUnloadHandler) ax['disableBeforeUnloadHandler'] = this._params.disableBeforeUnloadHandler;
            if (this._params.applicationVersion) ax['applicationVersion'] = this._params.applicationVersion;
            return ax;
        }
    }, {
        key: "addNewFabric",
        value: function addNewFabric(pc, remoteUser, fabricUsage, callID) {
            if (!CallstatsIo.moduleEnabled) return false;
            if (this.inited) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.UTILS, "[CallstatsIo]", Logger_1.LogLevel.INFO, " Callstats.io addNewFabric");
                this.callstats.addNewFabric(pc, remoteUser, fabricUsage, callID);
            } else {
                this.pendingFabric.push({ pc: pc, remoteUser: remoteUser, fabricUsage: fabricUsage, callID: callID });
            }
        }
    }, {
        key: "sendFabricEvent",
        value: function sendFabricEvent(pc, fabricEvent, callID) {
            if (!CallstatsIo.moduleEnabled) return false;
            this.callstats.sendFabricEvent(pc, fabricEvent, callID);
        }
    }, {
        key: "reportError",
        value: function reportError(pc, callID, wrtcFuncName, domError, localSDP, remoteSDP) {
            if (!CallstatsIo.moduleEnabled) return false;
            this.callstats.reportError(pc, callID, wrtcFuncName, domError, localSDP, remoteSDP);
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'CallstatsIo';
        }
    }], [{
        key: "isModuleEnabled",
        value: function isModuleEnabled() {
            return CallstatsIo.moduleEnabled;
        }
    }, {
        key: "get",
        value: function get(params) {
            var x_window = window;
            if (typeof x_window.callstats != "undefined") CallstatsIo.moduleEnabled = true;
            if (typeof CallstatsIo.instance == "undefined") {
                CallstatsIo.instance = new CallstatsIo(params);
            }
            if (typeof params != "undefined") {
                CallstatsIo.instance._params = params;
            }
            return CallstatsIo.instance;
        }
    }]);

    return CallstatsIo;
}();

CallstatsIo.moduleEnabled = false;
exports.CallstatsIo = CallstatsIo;

/***/ }),
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var GUID_1 = __webpack_require__(28);
var Logger_1 = __webpack_require__(0);
var MsgEnums_1 = __webpack_require__(16);
var MsgSignaling_1 = __webpack_require__(15);
/**
 * @hidden
 */

var Message = function () {
    /**
     * @hidden
     * @param {string} message
     * @param {Array<Payload>} payload
     */
    function Message(message, payload) {
        _classCallCheck(this, Message);

        this._text = message;
        if (typeof this.payload !== 'undefined') this._payload = payload;else this._payload = [];
        this._uuid = new GUID_1.GUID().toString();
    }
    /**
     * Universally unique identifier of message. Can be used on client side for housekeeping.
     * @returns {string}
     */


    _createClass(Message, [{
        key: "sendTo",

        /**
         * @hidden
         * @param conversation
         */
        value: function sendTo(conversation) {
            this._conversation = conversation.uuid;
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.sendMessage, this.getPayload());
        }
        /**
         * @hidden
         * @returns {{uuid: string, text: string, conversation: string}}
         */

    }, {
        key: "getPayload",
        value: function getPayload() {
            var str = {
                uuid: this._uuid,
                text: this._text,
                conversation: this._conversation
            };
            if (typeof this._payload !== 'undefined') str['payload'] = this._payload;else str['payload'] = [];
            return str;
        }
        /**
         * Sends text and payload changes to the server.
         */

    }, {
        key: "update",
        value: function update() {
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.editMessage, this.getPayload());
        }
        /**
         * Remove the message.
         * Triggers the [MessengerEvents.RemoveMessage]
         * event for all messenger objects on all clients, including this one.
         */

    }, {
        key: "remove",
        value: function remove() {
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.removeMessage, { uuid: this._uuid, conversation: this.conversation });
        }
        /**
         * Serialize message so it can be stored into some storage (like IndexedDB) and later restored via [Messenger.createMessageFromCache]
         */

    }, {
        key: "toCache",
        value: function toCache() {
            return {
                seq: this._seq,
                uuid: this._uuid,
                text: this._text,
                payload: this._payload,
                conversation: this._conversation,
                sender: this._sender
            };
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Message';
        }
    }, {
        key: "uuid",
        get: function get() {
            return this._uuid;
        }
        /**
         * UUID of the conversation this message belongs to.
         */

    }, {
        key: "conversation",
        get: function get() {
            return this._conversation;
        }
        /**
         * Message text.
         */

    }, {
        key: "text",
        get: function get() {
            return this._text;
        },
        set: function set(value) {
            this._text = value;
        }
        /**
         * Array of 'Payload' objects associated with the message.
         */

    }, {
        key: "payload",
        get: function get() {
            return this._payload;
        },
        set: function set(value) {
            this._payload = value;
        }
        /**
         * Message sequence number.
         */

    }, {
        key: "seq",
        get: function get() {
            return this._seq;
        }
        //FIXME: remove!

    }, {
        key: "sender",
        get: function get() {
            return this._sender;
        }
        /**
         * Create message from bus
         * @param busMessage
         * @param seq
         * @hidden
         */

    }], [{
        key: "_createFromBus",
        value: function _createFromBus(busMessage, seq) {
            var message = new Message(busMessage.text, busMessage.payload);
            message._uuid = busMessage.uuid;
            message._conversation = busMessage.conversation;
            message._sender = busMessage.sender;
            message._seq = seq;
            return message;
        }
        /**
         * @hidden
         * @param cacheMessage
         * @returns {Message}
         */

    }, {
        key: "createFromCache",
        value: function createFromCache(cacheMessage) {
            var message = new Message(cacheMessage.text, cacheMessage.payload);
            message._uuid = cacheMessage.uuid;
            message._conversation = cacheMessage.conversation;
            message._sender = cacheMessage.sender;
            message._seq = cacheMessage.seq;
            return message;
        }
    }]);

    return Message;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message.prototype, "sendTo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message.prototype, "getPayload", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message.prototype, "update", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message.prototype, "remove", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message.prototype, "toCache", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message, "_createFromBus", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Message, "createFromCache", null);
exports.Message = Message;

/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var Client_1 = __webpack_require__(1);
var Authenticator_1 = __webpack_require__(10);
var Hardware_1 = __webpack_require__(4);
/**
 * @hidden
 */

var Utils = function () {
    function Utils() {
        _classCallCheck(this, Utils);
    }

    _createClass(Utils, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'Logger';
        }
    }], [{
        key: "extend",

        /**
         * @param objects Objects for merging
         * @hidden
         * @returns {Object}
         */
        value: function extend() {
            for (var _len = arguments.length, objects = Array(_len), _key = 0; _key < _len; _key++) {
                objects[_key] = arguments[_key];
            }

            var extended = {};
            var merge = function merge(obj) {
                for (var prop in obj) {
                    if (Object.prototype.hasOwnProperty.call(obj, prop)) {
                        extended[prop] = obj[prop];
                    }
                }
            };
            merge(arguments[0]);
            for (var i = 1; i < arguments.length; i++) {
                var obj = arguments[i];
                merge(obj);
            }
            return extended;
        }
        /**
         * Convert <tt>headersObj</tt> to string
         * @param {Object} headersObj Object contains headers (as properties) to stringify
         * @returns {String}
         * @hidden
         */

    }, {
        key: "stringifyExtraHeaders",
        value: function stringifyExtraHeaders(headersObj) {
            if (Object.prototype.toString.call(headersObj) == '[object Object]') headersObj = JSON.stringify(headersObj);else headersObj = null;
            return headersObj;
        }
        /**
         * Parse cadence sections
         * @param {String} script
         * @retruns {Object}
         * @hidden
         */

    }, {
        key: "cadScript",
        value: function cadScript(script) {
            var cads = script.split(';');
            return cads.map(function (cad) {
                if (cad.length === 0) {
                    return;
                }
                var matchParens = cad.match(/\([0-9\/\.,\*\+]*\)$/),
                    ringLength = cad.substring(0, matchParens.index),
                    segments = matchParens.pop();
                if (matchParens.length) {
                    throw new Error('cadence script should be of the form `%f(%f/%f[,%f/%f])`');
                }
                ringLength = ringLength === '*' ? Infinity : parseFloat(ringLength);
                if (isNaN(ringLength)) {
                    throw new Error('cadence length should be of the form `%f`');
                }
                segments = segments.slice(1, segments.length - 1).split(',').map(function (segment) {
                    try {
                        var onOff = segment.split('/');
                        if (onOff.length > 3) {
                            throw new Error();
                        }
                        onOff = onOff.map(function (string, i) {
                            if (i === 2) {
                                // Special rules for frequencies
                                var freqs = string.split('+').map(function (f) {
                                    var integer = parseInt(f, 10);
                                    if (isNaN(integer)) {
                                        throw new Error();
                                    }
                                    return integer - 1;
                                });
                                return freqs;
                            }
                            var flt;
                            // Special rules for Infinity;
                            if (string == '*') {
                                flt = Infinity;
                            }
                            flt = flt ? flt : parseFloat(string);
                            if (isNaN(flt)) {
                                throw new Error();
                            }
                            return flt;
                        });
                        return {
                            on: onOff[0],
                            off: onOff[1],
                            // frequency is an extension for full toneScript.
                            frequencies: onOff[2]
                        };
                    } catch (err) {
                        throw new Error('cadence segments should be of the form `%f/%f[%d[+%d]]`');
                    }
                });
                return {
                    duration: ringLength,
                    sections: segments
                };
            });
        }
        /**
         * Parse frequency sections
         * @param {String} script
         * @returns {Object}
         * @hidden
         */

    }, {
        key: "freqScript",
        value: function freqScript(script) {
            var freqs = script.split(',');
            return freqs.map(function (freq) {
                try {
                    var tonePair = freq.split('@'),
                        frequency = parseInt(tonePair.shift()),
                        dB = parseFloat(tonePair.shift());
                    if (tonePair.length) {
                        throw Error();
                    }
                    return {
                        frequency: frequency,
                        decibels: dB
                    };
                } catch (err) {
                    throw new Error('freqScript pairs are expected to be of the form `%d@%f[,%d@%f]`');
                }
            });
        }
        /**
         * Parse full tonescripts
         * @param {String} script Tonescript string
         * @returns {Object} Object with frequencies and cadences properties
         * @hidden
         */

    }, {
        key: "toneScript",
        value: function toneScript(script) {
            var sections = script.split(';'),
                frequencies = this.freqScript(sections.shift()),
                cadences = this.cadScript(sections.join(';'));
            return {
                frequencies: frequencies,
                cadences: cadences
            };
        }
        /**
         * Plays tonescript using WebAudio API
         * @param {String} script Tonescript string to be parsed and played
         * @param {Boolean} [loop=false] Plays tonescript audio in a loop if true
         * @hidden
         */

    }, {
        key: "playToneScript",
        value: function playToneScript(script) {
            var loop = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

            if (typeof window.AudioContext != 'undefined' || typeof window.webkitAudioContext != 'undefined') {
                var context = Hardware_1.default.AudioDeviceManager.get().getAudioContext();
                if (context === null) return;
                var parsedToneScript = this.toneScript(script),
                    samples = [],
                    fullDuration = 0;
                var addSilence = function addSilence(sec) {
                    for (var t = 0; t < context.sampleRate * sec; t++) {
                        samples.push(0);
                    }
                };
                var addSound = function addSound(freq, sec) {
                    for (var t = 0; t < context.sampleRate * sec; t++) {
                        var sample = 0;
                        for (var f = 0; f < freq.length; f++) {
                            sample += Math.pow(10, parsedToneScript.frequencies[freq[f]].decibels / 20) * Math.sin((samples.length + t) * (3.14159265359 / context.sampleRate) * parsedToneScript.frequencies[freq[f]].frequency);
                            if (t < 10) sample *= t / 10;
                            if (t > context.sampleRate * sec - 10) sample *= (context.sampleRate * sec - t) / 10;
                        }
                        samples.push(sample);
                    }
                };
                var processSection = function processSection(section, duration) {
                    if (duration != Infinity) var t = duration;else t = duration = 20;
                    if (section.off !== 0 && section.off != Infinity) {
                        while (t > 0) {
                            addSound(section.frequencies, section.on);
                            t -= section.on;
                            addSilence(section.off);
                            t -= section.off;
                            var tt = t * 10;
                            t = parseInt(String(t * 10)) / 10;
                        }
                    } else {
                        addSound(section.frequencies, duration);
                    }
                };
                var processCadence = function processCadence(cadence) {
                    if (cadence.duration != Infinity) fullDuration += cadence.duration;else fullDuration += 20;
                    for (var i = 0; i < cadence.sections.length; i++) {
                        processSection(cadence.sections[i], cadence.duration);
                    }
                };
                this.source = context.createBufferSource();
                for (var k = 0; k < parsedToneScript.cadences.length; k++) {
                    if (parsedToneScript.cadences[k].duration == Infinity) this.source.loop = true;
                    processCadence(parsedToneScript.cadences[k]);
                }
                this.source.connect(context.destination);
                var sndBuffer = context.createBuffer(1, fullDuration * context.sampleRate, context.sampleRate);
                var bufferData = sndBuffer.getChannelData(0);
                for (var i = 0; i < fullDuration * context.sampleRate; i++) {
                    bufferData[i] = samples[i];
                }
                samples = null;
                this.source.buffer = sndBuffer;
                if (loop === true) this.source.loop = true;
                this.source.start(0);
            }
        }
        /**
         * Stops tonescript audio playback
         * @returns {Boolean} True if audio playback was stopped
         * @hidden
         */

    }, {
        key: "stopPlayback",
        value: function stopPlayback() {
            if (typeof this.source !== "undefined" && this.source !== null) {
                this.source.stop(0);
                this.source = null;
                return true;
            }
            return false;
        }
        /**
         * Makes cross-browser XmlHttpRequest
         * @param {String} url URL for HTTP request
         * @param {Function} [callback] Function to be called on compvarion
         * @param {Function} [error] Function to be called in case of error
         * @param {String} [postData] Data to be sent with POST request
         * @hidden
         */

    }, {
        key: "sendRequest",
        value: function sendRequest(url, callback, error, postData) {
            var xdr = false;
            var createXMLHTTPObject = function createXMLHTTPObject() {
                var XMLHttpFactories = [
                //function() { return new XDomainRequest(); },
                function () {
                    return new XMLHttpRequest();
                }, function () {
                    return new ActiveXObject("Msxml2.XMLHTTP");
                }, function () {
                    return new ActiveXObject("Msxml3.XMLHTTP");
                }, function () {
                    return new ActiveXObject("Microsoft.XMLHTTP");
                }];
                var xmlhttp;
                for (var i = 0; i < XMLHttpFactories.length; i++) {
                    try {
                        xmlhttp = XMLHttpFactories[i]();
                        if (i === 0) xdr = true;
                    } catch (e) {
                        continue;
                    }
                    break;
                }
                return xmlhttp;
            };
            var req = createXMLHTTPObject();
            if (!req) return;
            var method = postData ? "POST" : "GET";
            if (!xdr) {
                req.open(method, url, true);
                if (postData) req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                req.onreadystatechange = function () {
                    if (req.readyState != 4) return;
                    if (req.status != 200 && req.status != 304) {
                        error(req);
                        return;
                    }
                    callback(req);
                };
                if (req.readyState == 4) return;
                req.send(postData);
            } else {
                req.onerror = function () {
                    error(req);
                };
                req.ontimeout = function () {
                    error(req);
                };
                req.onload = function () {
                    callback(req);
                };
                req.open(method, url);
                req.timeout = 5000;
                req.send();
            }
        }
        /**
         * Makes request to VoxImplant Load Balancer to get media gateway IP address
         * @param {Function} callback Function to be called on compvarion
         * @param {Boolean} [reservedBalancer=false] Try reserved balancer if true
         * @hidden
         */

    }, {
        key: "getServers",
        value: function getServers(callback) {
            var reservedBalancer = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
            var vi = arguments[2];

            var protocol = 'https:' == document.location.protocol ? 'https://' : 'http://';
            var balancer_url = protocol + "balancer.voximplant.com/getNearestHost";
            this.sendRequest(balancer_url, function (XHR) {
                balancerCompare(XHR.responseText);
            }, function (XHR) {
                balancerCompare(null);
            });
            function balancerCompare(data) {
                if (data !== null) callback(data);else if (reservedBalancer !== true) Utils.getServers(callback, true, vi);else vi.dispatchEvent({ name: 'ConnectionFailed', message: "VoxImplant Cloud is unavailable" });
            }
        }
        /**
         * @hidden
         * The simplest function to get an UUID string.
         * @returns {string} A version 4 UUID string.
         */

    }, {
        key: "generateUUID",
        value: function generateUUID() {
            var rand = this._gri,
                hex = this._ha;
            return hex(rand(32), 8) + "-" + hex(rand(16), 4) + "-" + hex(0x4000 | rand(12), 4) + "-" + hex(0x8000 | rand(14), 4) + "-" + hex(rand(48), 12);
        }
        /**
         * Returns an unsigned x-bit random integer.
         * @hidden
         * @param {int} x A positive integer ranging from 0 to 53, inclusive.
         * @returns {int} An unsigned x-bit random integer (0 <= f(x) < 2^x).
         */

    }, {
        key: "_gri",
        value: function _gri(x) {
            if (x < 0) return NaN;
            if (x <= 30) return 0 | Math.random() * (1 << x);
            if (x <= 53) return (0 | Math.random() * (1 << 30)) + (0 | Math.random() * (1 << x - 30)) * (1 << 30);
            return NaN;
        }
        /**
         * Converts an integer to a zero-filled hexadecimal string.
         * @hidden
         * @param {int} num
         * @param {int} length
         * @returns {string}
         */

    }, {
        key: "_ha",
        value: function _ha(num, length) {
            var str = num.toString(16),
                i = length - str.length,
                z = "0";
            for (; i > 0; i >>>= 1, z += z) {
                if (i & 1) {
                    str = z + str;
                }
            }
            return str;
        }
    }, {
        key: "filterXSS",
        value: function filterXSS(content) {
            var div = document.createElement("div");
            div.appendChild(document.createTextNode(content));
            content = div.innerHTML;
            return content;
        }
        /**
         * Check if !connected
         * @hidden
         */

    }, {
        key: "checkCA",
        value: function checkCA() {
            if (!Client_1.Client.getInstance().connected()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
            if (!Authenticator_1.Authenticator.get().authorized()) throw new Error("NOT_AUTHORIZED");
        }
        /**
         * Promise to check browser compability level
         * @param level 'webrtc'|'signaling'
         */

    }, {
        key: "canRTC",
        value: function canRTC(level) {
            return;
        }
        /**
         * Complite defaults with settings
         * @param left defaults
         * @param right settings
         * @returns {Object}
         */

    }, {
        key: "mixObjectToLeft",
        value: function mixObjectToLeft(left, right) {
            for (var left_key in left) {
                if (typeof right[left_key] == "undefined") continue;
                left[left_key] = right[left_key];
            }
            return left;
        }
    }, {
        key: "makeRandomString",
        value: function makeRandomString(length) {
            var possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/";
            var randomSrtring = '';
            for (var i = 0; i < length; i++) {
                randomSrtring += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            return randomSrtring;
        }
    }]);

    return Utils;
}();

Utils.source = null;
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "extend", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "stringifyExtraHeaders", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "cadScript", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "freqScript", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "toneScript", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "playToneScript", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "stopPlayback", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "sendRequest", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "getServers", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "generateUUID", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "_gri", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "_ha", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "filterXSS", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.UTILS)], Utils, "checkCA", null);
exports.Utils = Utils;

/***/ }),
/* 24 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var VoxSignaling_1 = __webpack_require__(2);
var CallManager_1 = __webpack_require__(5);
var Call_1 = __webpack_require__(13);
var Constants_1 = __webpack_require__(11);
var RemoteFunction_1 = __webpack_require__(3);
var ReInviteQ_1 = __webpack_require__(37);
var Client_1 = __webpack_require__(1);
var index_1 = __webpack_require__(4);
/**
 * @hidden
 */
var PeerConnectionState;
(function (PeerConnectionState) {
    PeerConnectionState[PeerConnectionState["IDLE"] = 0] = "IDLE";
    PeerConnectionState[PeerConnectionState["REMOTEOFFER"] = 1] = "REMOTEOFFER";
    PeerConnectionState[PeerConnectionState["LOCALOFFER"] = 2] = "LOCALOFFER";
    PeerConnectionState[PeerConnectionState["ESTABLISHING"] = 3] = "ESTABLISHING";
    PeerConnectionState[PeerConnectionState["ESTABLISHED"] = 4] = "ESTABLISHED";
    PeerConnectionState[PeerConnectionState["CLOSED"] = 5] = "CLOSED";
})(PeerConnectionState = exports.PeerConnectionState || (exports.PeerConnectionState = {}));
/**
 * @hidden
 */
var PeerConnectionMode;
(function (PeerConnectionMode) {
    PeerConnectionMode[PeerConnectionMode["CLIENT_SERVER_V1"] = 0] = "CLIENT_SERVER_V1";
    PeerConnectionMode[PeerConnectionMode["P2P"] = 1] = "P2P";
    PeerConnectionMode[PeerConnectionMode["CONFERENCE"] = 2] = "CONFERENCE";
})(PeerConnectionMode = exports.PeerConnectionMode || (exports.PeerConnectionMode = {}));
/**
 * Peer connection wrapper. Will have implementations for WebRTC/ORTC
 * @hidden
 */

var PeerConnection = function () {
    function PeerConnection(id, mode, videoEnabled) {
        _classCallCheck(this, PeerConnection);

        this.id = id;
        this.mode = mode;
        this.videoEnabled = videoEnabled;
        /**
         * @hidden
         * @param state
         */
        this.onHold = false;
        this.muteMicState = false;
        this.SEND_CANDIDATE_DELAY = 1000;
        this.mediaRepository = [];
        this.candidateList = [];
        this.localCandidateTimer = -1;
        this.log = Logger_1.LogManager.get().createLogger(Logger_1.LogCategory.RTC, 'PeerConnection ' + id);
        this.state = PeerConnectionState.IDLE;
        this.log.info('Created PC');
        this.pendingCandidates = [];
        if (id !== '_default' && CallManager_1.CallManager.get().calls[id]) this.reInviteQ = new ReInviteQ_1.ReInviteQ(CallManager_1.CallManager.get().calls[id], this._canReInvite);
    }

    _createClass(PeerConnection, [{
        key: "getId",
        value: function getId() {
            return this.id;
        }
    }, {
        key: "getState",
        value: function getState() {
            return this.state;
        }
    }, {
        key: "processRemoteAnswer",
        value: function processRemoteAnswer(headers, sdp) {
            // if (this.state == PeerConnectionState.ESTABLISHING) {
            this.log.info('Called processRemoteAnswer');
            this.state = PeerConnectionState.ESTABLISHING;
            return this._processRemoteAnswer(headers, sdp);
            // } else {
            //     this.log.error("Called processRemoteAnswer in state " + PeerConnectionState[this.state]);
            // }
        }
    }, {
        key: "getLocalOffer",
        value: function getLocalOffer() {
            if (this.state === PeerConnectionState.IDLE || this.state === PeerConnectionState.ESTABLISHED || PeerConnectionState.LOCALOFFER) {
                this.log.info('Called getLocalOffer');
                this.state = PeerConnectionState.LOCALOFFER;
                return this._getLocalOffer();
            } else {
                this.log.error('Called getLocalOffer in state ' + PeerConnectionState[this.state]);
                return new Promise(function (resolve, reject) {
                    reject('Invalid state');
                });
            }
        }
    }, {
        key: "getLocalAnswer",
        value: function getLocalAnswer() {
            return this._getLocalAnswer();
        }
    }, {
        key: "processRemoteOffer",
        value: function processRemoteOffer(sdp) {
            if (this.state === PeerConnectionState.IDLE || this.state === PeerConnectionState.ESTABLISHED) {
                this.log.info('Called processRemoteOffer');
                this.state = PeerConnectionState.ESTABLISHING;
                return this._processRemoteOffer(sdp);
            } else {
                this.log.error('Called processRemoteOffer in state ' + PeerConnectionState[this.state]);
                return new Promise(function (resolve, reject) {
                    reject('Invalid state');
                });
            }
        }
    }, {
        key: "close",
        value: function close() {
            this.log.info('Called close');
            this._close();
        }
    }, {
        key: "addRemoteCandidate",
        value: function addRemoteCandidate(candidate, mLineIndex) {
            this.log.info('Called addRemoteCandidate');
            return this._addRemoteCandidate(candidate, mLineIndex);
        }
    }, {
        key: "handleReinvite",
        value: function handleReinvite(headers, sdp, hasVideo) {
            return this._handleReinvite(headers, sdp, hasVideo);
        }
    }, {
        key: "addCandidateToSend",
        value: function addCandidateToSend(attrString, mLineIndex) {
            this.pendingCandidates.push([mLineIndex, attrString]);
            if (this.canSendCandidates) this.startCandidateSendTimer();
        }
    }, {
        key: "canStartSendingCandidates",
        value: function canStartSendingCandidates() {
            this.canSendCandidates = true;
            this.startCandidateSendTimer();
        }
    }, {
        key: "sendDTMF",
        value: function sendDTMF(key) {
            // const duration = 3000;
            var duration = 500;
            var gap = 50;
            this._sendDTMF(key, duration, gap);
        }
    }, {
        key: "setVideoEnabled",
        value: function setVideoEnabled(newVal) {
            var oldvalRecieve = this.videoEnabled.receiveVideo;
            this.videoEnabled = newVal;
            if (oldvalRecieve != newVal.receiveVideo) {
                this._hold(this.onHold);
            }
        }
    }, {
        key: "setVideoFlags",
        value: function setVideoFlags(newFlags) {
            this.videoEnabled = newFlags;
        }
        /**
         * Get sdp audio/video directions from sdp
         * @hidden
         */

    }, {
        key: "getDirections",
        value: function getDirections() {
            return this._getDirections();
        }
        /**
         * @hidden
         * @param state
         */

    }, {
        key: "setHoldKey",
        value: function setHoldKey(state) {
            this.onHold = state;
        }
    }, {
        key: "getTrackKind",
        value: function getTrackKind() {
            if (this._call) {
                return index_1.default.StreamManager.get()._getTracksKind(this._call);
            } else return {};
        }
    }, {
        key: "sendMedia",
        value: function sendMedia(audio, video) {
            var _this = this;

            return new Promise(function (_resolve, reject) {
                if (_this.onHold) {
                    reject({ result: false, call: _this._call });
                    return;
                }
                _this.reInviteQ.add({
                    fx: function fx() {
                        var appConfig = Client_1.Client.getInstance().config();
                        index_1.default.StreamManager.get().updateCallStream(_this._call);
                    }, reject: reject, resolve: function resolve(e) {
                        _this.restoreMute();
                        _resolve(e);
                    }
                });
            });
        }
        /**
         * Hold/Unhold action for protocol v3 (Fully implement RFC 4566
         * @param newState
         */

    }, {
        key: "hold",
        value: function hold(newState) {
            var _this2 = this;

            return new Promise(function (_resolve2, reject) {
                _this2.reInviteQ.add({
                    fx: function fx() {
                        _this2._hold(newState);
                    }, reject: reject, resolve: function resolve(e) {
                        _this2.restoreMute();
                        _resolve2(e);
                    }
                });
            });
        }
    }, {
        key: "hdnFRS",
        value: function hdnFRS() {
            var _this3 = this;

            return new Promise(function (_resolve3, reject) {
                if (_this3.onHold) {
                    reject({ result: false, call: _this3._call });
                    return;
                }
                _this3.reInviteQ.add({
                    fx: function fx() {
                        _this3._hdnFRS();
                    }, reject: reject, resolve: function resolve(e) {
                        _this3.restoreMute();
                        _resolve3(e);
                    }
                });
            });
        }
    }, {
        key: "muteMicrophone",
        value: function muteMicrophone(newState) {
            var _this4 = this;

            if (this.muteMicState === newState) return;
            this.muteMicState = newState;
            index_1.default.StreamManager.get().getCallStream(this._call).then(function (stream) {
                stream.getAudioTracks().forEach(function (track) {
                    track.enabled = !_this4.muteMicState;
                });
            });
        }
    }, {
        key: "restoreMute",
        value: function restoreMute() {
            var _this5 = this;

            if (this._call.settings.active) {
                var that = this;
                setTimeout(function () {
                    if (_this5._call.settings.state !== Call_1.CallState.ENDED) {
                        _this5._call.restoreRMute();
                        index_1.default.StreamManager.get().getCallStream(_this5._call).then(function (stream) {
                            stream.getAudioTracks().forEach(function (track) {
                                track.enabled = !that.muteMicState;
                            });
                        });
                    }
                }, 300);
            }
        }
    }, {
        key: "addCustomMedia",
        value: function addCustomMedia(stream) {
            var _this6 = this;

            return new Promise(function (_resolve4, reject) {
                _this6.reInviteQ.add({
                    fx: function fx() {
                        _this6._addCustomMedia(stream);
                    }, reject: reject, resolve: function resolve(e) {
                        _this6.restoreMute();
                        _resolve4();
                    }
                });
            });
        }
        /**
         * @hidden
         * @param {MediaStream} stream
         */

    }, {
        key: "fastAddCustomMedia",
        value: function fastAddCustomMedia(stream) {
            this._addCustomMedia(stream);
        }
        /**
         * @hidden
         * @param {MediaStream} stream
         */

    }, {
        key: "fastRemoveCustomMedia",
        value: function fastRemoveCustomMedia(stream) {
            this._removeCustomMedia(stream);
        }
    }, {
        key: "removeCustomMedia",
        value: function removeCustomMedia(stream) {
            var _this7 = this;

            return new Promise(function (_resolve5, reject) {
                _this7.reInviteQ.add({
                    fx: function fx() {
                        _this7._removeCustomMedia(stream);
                    }, reject: reject, resolve: function resolve(e) {
                        _this7.restoreMute();
                        _resolve5();
                    }
                });
            });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'PeerConnection';
        }
    }, {
        key: "setState",
        value: function setState(st) {
            this.log.info('Transmitting from ' + PeerConnectionState[this.state] + ' to ' + PeerConnectionState[st]);
            this.state = st;
        }
    }, {
        key: "sendLocalCandidateToPeer",
        value: function sendLocalCandidateToPeer(cand, mLineIndex) {
            var _this8 = this;

            this._call = CallManager_1.CallManager.get().calls[this.id];
            if (this.mode === PeerConnectionMode.CLIENT_SERVER_V1) {
                VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.addCandidate, this.id, cand, mLineIndex);
            } else {
                this.candidateList.push([mLineIndex, cand]);
                if (this.localCandidateTimer <= 0) {
                    this.localCandidateTimer = window.setTimeout(function () {
                        window.clearTimeout(_this8.localCandidateTimer);
                        _this8.localCandidateTimer = -1;
                        if (CallManager_1.CallManager.get().calls[_this8.id]) CallManager_1.CallManager.get().calls[_this8.id].sendInfo(Constants_1.Constants.P2P_SPD_FRAG_MIME_TYPE, JSON.stringify(_this8.candidateList), {});
                        _this8.candidateList = [];
                    }, 200);
                }
            }
        }
    }, {
        key: "startCandidateSendTimer",
        value: function startCandidateSendTimer() {
            var _this9 = this;

            if (this.candidateSendTimer === null || typeof this.candidateSendTimer === 'undefined') {
                this.candidateSendTimer = setTimeout(function () {
                    _this9.candidateSendTimer = null;
                    if (_this9.pendingCandidates.length > 0) {
                        if (CallManager_1.CallManager.get().calls[_this9.id]) CallManager_1.CallManager.get().calls[_this9.id].sendInfo(Constants_1.Constants.P2P_SPD_FRAG_MIME_TYPE, JSON.stringify(_this9.pendingCandidates), {});
                    }
                    _this9.pendingCandidates = [];
                }, this.SEND_CANDIDATE_DELAY);
            }
        }
    }, {
        key: "remoteStreams",
        get: function get() {
            return this._remoteStreams;
        }
    }]);

    return PeerConnection;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "getState", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "processRemoteAnswer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "getLocalOffer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "getLocalAnswer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "processRemoteOffer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "close", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "addRemoteCandidate", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "handleReinvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "addCandidateToSend", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "canStartSendingCandidates", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "sendDTMF", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "setVideoEnabled", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "setVideoFlags", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "setHoldKey", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "getTrackKind", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "sendMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "hold", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "hdnFRS", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "muteMicrophone", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "restoreMute", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "addCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "fastAddCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "fastRemoveCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "removeCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "setState", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "sendLocalCandidateToPeer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], PeerConnection.prototype, "startCandidateSendTimer", null);
exports.PeerConnection = PeerConnection;

/***/ }),
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var __1 = __webpack_require__(4);
var EventTarget_1 = __webpack_require__(14);
var CameraManager_1 = __webpack_require__(33);
var BrowserSpecific_1 = __webpack_require__(8);
var Events_1 = __webpack_require__(18);
var Client_1 = __webpack_require__(1);
var AudioDeviceManager_1 = __webpack_require__(32);
var MediaRenderer_1 = __webpack_require__(34);
/**
 * @hidden
 */

var StreamManager = function (_EventTarget_1$EventT) {
    _inherits(StreamManager, _EventTarget_1$EventT);

    /**
     * @hidden
     */
    function StreamManager() {
        _classCallCheck(this, StreamManager);

        var _this = _possibleConstructorReturn(this, (StreamManager.__proto__ || Object.getPrototypeOf(StreamManager)).call(this));

        if (typeof StreamManager.instance !== 'undefined') throw new Error('Error - use StreamManager.get()');
        _this._callStreams = {};
        _this._localMediaRenderers = [];
        _this._sharingStreams = {};
        return _this;
    }
    /**
     * Get the StreamManager instance
     */


    _createClass(StreamManager, [{
        key: "getMirrorStream",

        /**
         * Return link to the mirror stream, if exist. Or get a new one.
         * @hidden
         * @return {Promise<MediaStream>}
         */
        value: function getMirrorStream() {
            var _this2 = this;

            // TODO: Переписать на общее получение медиа (irbisadm 15.09.18)
            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.customMirrorMedia) {
                return new Promise(function (resolve, reject) {
                    Client_1.Client.getInstance().config().experiments.customMirrorMedia({
                        videoSettings: CameraManager_1.CameraManager.get().getDefaultVideoSettings()
                    }).then(function (stream) {
                        _this2._mirrorStream = stream;
                        _this2._mirrorStream.getTracks().forEach(function (track) {
                            track.onended = function () {
                                _this2.onMirrorEnded();
                            };
                            track.onmute = function () {
                                _this2.onMirrorEnded;
                            };
                        });
                        resolve(stream);
                    }).catch(function (e) {
                        reject(e);
                    });
                });
            }
            return new Promise(function (resolve, reject) {
                if (typeof _this2._mirrorStream !== 'undefined') resolve(_this2._mirrorStream);else {
                    if (BrowserSpecific_1.default.isIphone()) {
                        return __1.default.IOSCacheManager.get().getStream({
                            video: CameraManager_1.CameraManager.get().getCallConstraints('__local__'),
                            audio: AudioDeviceManager_1.AudioDeviceManager.get().getCallConstraints('__local__')
                        }).then(function (stream) {
                            _this2._mirrorStream = stream;
                            _this2._mirrorStream.getTracks().forEach(function (track) {
                                track.onended = function () {
                                    _this2.onMirrorEnded();
                                };
                                track.onmute = function () {
                                    _this2.onMirrorEnded;
                                };
                            });
                            resolve(stream);
                        }, reject);
                    } else {
                        return navigator.mediaDevices.getUserMedia({ video: CameraManager_1.CameraManager.get().getCallConstraints('__local__') }).then(function (stream) {
                            _this2._mirrorStream = stream;
                            _this2._mirrorStream.getTracks().forEach(function (track) {
                                track.onended = function () {
                                    _this2.onMirrorEnded();
                                };
                                track.onmute = function () {
                                    _this2.onMirrorEnded;
                                };
                            });
                            resolve(stream);
                        }, reject);
                    }
                }
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "remMirrorStream",
        value: function remMirrorStream() {
            if (typeof this._mirrorStream === 'undefined') return;
            this._mirrorStream.getTracks().forEach(function (track) {
                track.onended = undefined;
                track.onmute = undefined;
                track.stop();
            });
            this._mirrorStream = undefined;
        }
        /**
         * @hidden
         * @param {Call} call
         * @returns {Promise<MediaStream>}
         */

    }, {
        key: "getCallStream",
        value: function getCallStream(call) {
            var _this3 = this;

            var ignore = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

            // TODO: Переписать на общее получение медиа (irbisadm 15.09.18)
            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.customCallMedia) {
                return new Promise(function (resolve, reject) {
                    var videoSettings = false;
                    var audioSettings = AudioDeviceManager_1.AudioDeviceManager.get().getDefaultAudioSettings();
                    if (call) {
                        videoSettings = call.settings.videoDirections && call.settings.videoDirections.sendVideo ? CameraManager_1.CameraManager.get().getCallVideoSettings(call) : false;
                        audioSettings = AudioDeviceManager_1.AudioDeviceManager.get().getCallAudioSettings(call);
                    }
                    Client_1.Client.getInstance().config().experiments.customCallMedia({
                        call: call,
                        audioSettings: audioSettings,
                        videoSettings: videoSettings
                    }).then(function (stream) {
                        Client_1.Client.getInstance().dispatchEvent({
                            name: Events_1.Events.MicAccessResult,
                            result: true,
                            stream: stream
                        });
                        resolve(stream);
                    }).catch(function (e) {
                        Client_1.Client.getInstance().dispatchEvent({
                            name: Events_1.Events.MicAccessResult,
                            result: false,
                            stream: null
                        });
                        reject(e);
                    });
                });
            }
            return new Promise(function (resolve, reject) {
                var callId = typeof call === 'undefined' ? '__default' : call.id();
                if (_this3._callStreams[callId] && !ignore) {
                    resolve(_this3._callStreams[callId]);
                } else {
                    var constraints = _this3._composeConstraints(call);
                    if (!constraints.audio && !constraints.video && callId !== '__default') {
                        resolve(null);
                        return;
                    }
                    if (BrowserSpecific_1.default.getWSVendor() !== 'firefox') {
                        if (BrowserSpecific_1.default.isIphone()) {
                            __1.default.IOSCacheManager.get().getStream(constraints).then(function (stream) {
                                _this3._callStreams[callId] = stream;
                                stream.getTracks().forEach(function (track) {
                                    track.onended = _this3.onCallEnded;
                                    track.onmute = _this3.onCallEnded;
                                });
                                Client_1.Client.getInstance().dispatchEvent({
                                    name: Events_1.Events.MicAccessResult,
                                    result: true,
                                    stream: stream
                                });
                                resolve(stream);
                            });
                        } else {
                            navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
                                _this3._callStreams[callId] = stream;
                                stream.getTracks().forEach(function (track) {
                                    track.onended = _this3.onCallEnded;
                                    track.onmute = _this3.onCallEnded;
                                });
                                Client_1.Client.getInstance().dispatchEvent({
                                    name: Events_1.Events.MicAccessResult,
                                    result: true,
                                    stream: stream
                                });
                                resolve(stream);
                            }, function (e) {
                                if (e.name === 'NotFoundError') {
                                    var backupConstrains = { audio: true };
                                    if (typeof call !== 'undefined') {
                                        call.settings.videoDirections.sendVideo = false;
                                        backupConstrains = _this3._composeConstraints(call);
                                    }
                                    navigator.mediaDevices.getUserMedia(backupConstrains).then(function (stream) {
                                        _this3._callStreams[callId] = stream;
                                        stream.getTracks().forEach(function (track) {
                                            track.onended = _this3.onCallEnded;
                                            track.onmute = _this3.onCallEnded;
                                        });
                                        Client_1.Client.getInstance().dispatchEvent({
                                            name: Events_1.Events.MicAccessResult,
                                            result: true,
                                            stream: stream
                                        });
                                        resolve(stream);
                                    }, function (e) {
                                        Client_1.Client.getInstance().dispatchEvent({
                                            name: Events_1.Events.MicAccessResult,
                                            result: false,
                                            stream: null
                                        });
                                        reject(e);
                                    });
                                } else {
                                    Client_1.Client.getInstance().dispatchEvent({
                                        name: Events_1.Events.MicAccessResult,
                                        result: false,
                                        stream: null
                                    });
                                    reject(e);
                                }
                            });
                        }
                    } else {
                        var audioConstraint = null;
                        var videoConstraint = null;
                        if (constraints.audio) {
                            audioConstraint = { audio: constraints.audio };
                        }
                        if (constraints.video) {
                            videoConstraint = { video: constraints.video };
                        }
                        navigator.mediaDevices.getUserMedia(audioConstraint).then(function (stream) {
                            if (!videoConstraint) {
                                _this3._callStreams[callId] = stream;
                                stream.getTracks().forEach(function (track) {
                                    track.onended = _this3.onCallEnded;
                                    track.onmute = _this3.onCallEnded;
                                });
                                Client_1.Client.getInstance().dispatchEvent({
                                    name: Events_1.Events.MicAccessResult,
                                    result: true,
                                    stream: stream
                                });
                                resolve(stream);
                            } else {
                                navigator.mediaDevices.getUserMedia(videoConstraint).then(function (stream2) {
                                    var fullStream = new MediaStream();
                                    stream.getTracks().forEach(function (track) {
                                        fullStream.addTrack(track);
                                    });
                                    stream2.getTracks().forEach(function (track) {
                                        fullStream.addTrack(track);
                                    });
                                    _this3._callStreams[callId] = fullStream;
                                    fullStream.getTracks().forEach(function (track) {
                                        track.onended = _this3.onCallEnded;
                                        track.onmute = _this3.onCallEnded;
                                    });
                                    Client_1.Client.getInstance().dispatchEvent({
                                        name: Events_1.Events.MicAccessResult,
                                        result: true,
                                        stream: fullStream
                                    });
                                    resolve(fullStream);
                                }, function () {
                                    _this3._callStreams[callId] = stream;
                                    stream.getTracks().forEach(function (track) {
                                        track.onended = _this3.onCallEnded;
                                        track.onmute = _this3.onCallEnded;
                                    });
                                    Client_1.Client.getInstance().dispatchEvent({
                                        name: Events_1.Events.MicAccessResult,
                                        result: true,
                                        stream: stream
                                    });
                                    resolve(stream);
                                });
                            }
                        }, function (e) {
                            Client_1.Client.getInstance().dispatchEvent({
                                name: Events_1.Events.MicAccessResult,
                                result: false,
                                stream: null
                            });
                            reject(e);
                        });
                    }
                }
            });
        }
        /**
         * @hidden
         * @param {Call} call
         * @returns {Promise<MediaStream>}
         * @private
         */

    }, {
        key: "_updateCallStream",
        value: function _updateCallStream(call) {
            this.remCallStream(call);
            return this.getCallStream(call);
        }
        /**
         * @hidden
         * @param {Call} call
         * @returns {Promise<EventHandlers.Updated>}
         */

    }, {
        key: "updateCallStream",
        value: function updateCallStream(call) {
            var _this4 = this;

            return new Promise(function (resolve, reject) {
                var oldMedia = _this4._callStreams[call.id()];
                _this4.getCallStream(call, true).then(function (stream) {
                    call.peerConnection.fastRemoveCustomMedia(oldMedia);
                    _this4._remCallStream(oldMedia);
                    call.peerConnection.addCustomMedia(stream).then(function (e) {
                        resolve(e);
                    }, function (e) {
                        return reject(e);
                    });
                });
            });
        }
        /**
         * @hidden
         * @param {Call} call
         */

    }, {
        key: "remCallStream",
        value: function remCallStream(call) {
            var callId = typeof call === 'undefined' ? '__default' : call.id();
            if (this._callStreams[callId]) {
                this._remCallStream(this._callStreams[callId]);
                this._callStreams[callId] = undefined;
                delete this._callStreams[callId];
            }
        }
        /**
         * @hidden
         * @param {Call} call
         */

    }, {
        key: "_remCallStream",
        value: function _remCallStream(stream) {
            if (!stream) return;
            if (BrowserSpecific_1.default.isIphone()) return;
            stream.getTracks().forEach(function (track) {
                track.onended = undefined;
                track.onmute = undefined;
                track.stop();
                stream.removeTrack(track);
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "clear",
        value: function clear() {
            if (this._mirrorStream) {
                this._mirrorStream.getTracks().forEach(function (track) {
                    track.onended = undefined;
                    track.onmute = undefined;
                    track.stop();
                });
            }
            this._mirrorStream = undefined;
            for (var key in this._callStreams) {
                if (this._callStreams.hasOwnProperty(key)) {
                    var stream = this._callStreams[key];
                    if (stream) {
                        stream.getTracks().forEach(function (track) {
                            track.onended = undefined;
                            track.onmute = undefined;
                            track.stop();
                        });
                    }
                }
            }
            this._callStreams = {};
        }
        /**
         * List of currently used containers for local audio and video streams.
         */

    }, {
        key: "getLocalMediaRenderers",
        value: function getLocalMediaRenderers() {
            return this._localMediaRenderers;
        }
        /**
         * Turn on local video. The container for local video elements must be specified via in the
         * [Config.localVideoContainerId] field in the [Client.init] config.
         *   If it's not specified, local videos will be appended to end of the *<body>* element.
         *  Use the <a href="#hidelocalvideo">hideLocalVideo</a> method to turn off local video.
         */

    }, {
        key: "showLocalVideo",
        value: function showLocalVideo() {
            var _this5 = this;

            if (this._mirrorMediaRendererId) throw new Error('Local video already displayed. Please, use Hardware.StreamManager.get().hideLocalVideo ' + 'before request a new one.');else {
                return new Promise(function (resolve, reject) {
                    __1.default.StreamManager.get().getMirrorStream().then(function (stream) {
                        var localRenderer = new MediaRenderer_1.MediaRenderer(stream, 'video', true, true, 'voximplantlocalvideo');
                        _this5._localMediaRenderers.push(localRenderer);
                        _this5._mirrorMediaRendererId = localRenderer.id;
                        resolve(localRenderer);
                        _this5.dispatchEvent({ name: __1.default.HardwareEvents.MediaRendererAdded, renderer: localRenderer });
                    }).catch(reject);
                });
            }
        }
        /**
         * Turn off local video. Use the <a href="#showlocalvideo">showLocalVideo</a> method to turn on local video.
         */

    }, {
        key: "hideLocalVideo",
        value: function hideLocalVideo() {
            var _this6 = this;

            if (!this._mirrorMediaRendererId) throw new Error('Local video not displayed yet. Please, use Hardware.StreamManager.get().showLocalVideo ' + 'to request a new one.');else {
                return new Promise(function (resolve, reject) {
                    var mirrorRendererList = _this6._localMediaRenderers.filter(function (r) {
                        return r.id === _this6._mirrorMediaRendererId;
                    });
                    if (mirrorRendererList) mirrorRendererList.forEach(function (r) {
                        r.clear();
                    });
                    _this6.remMirrorStream();
                    _this6._localMediaRenderers = _this6._localMediaRenderers.filter(function (r) {
                        return r.id !== _this6._mirrorMediaRendererId;
                    });
                    _this6._mirrorMediaRendererId = undefined;
                    resolve();
                });
            }
        }
        /**
         * Register a handler for the specified event. The method is a shorter equivalent for *addEventListener*. One event can have more than one handler; handlers are executed in order of registration.
         * Use the [StreamManager.off] method to delete a handler.
         */

    }, {
        key: "on",
        value: function on(event, handler) {
            _get(StreamManager.prototype.__proto__ || Object.getPrototypeOf(StreamManager.prototype), "on", this).call(this, event, handler);
        }
        /**
         * Remove a handler for the specified event. The method is a shorter equivalent for *removeEventListener*. If a number of events has the same function as a handler, the method can be called multiple times with the same handler argument.
         */

    }, {
        key: "off",
        value: function off(event, handler) {
            _get(StreamManager.prototype.__proto__ || Object.getPrototypeOf(StreamManager.prototype), "off", this).call(this, event, handler);
        }
        /**
         * Get sharing media and create renderer if need.
         * @hidden
         * @param {Call} call
         * @param {boolean} showLocalVideo
         * @returns {Promise<Hardware.SharingStream>}
         */

    }, {
        key: "_newScreenSharing",
        value: function _newScreenSharing(call, showLocalVideo) {
            var _this7 = this;

            // TODO: Переписать на общее получение медиа (irbisadm 15.09.18)
            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.customScreenMedia) {
                return new Promise(function (resolve, reject) {
                    Client_1.Client.getInstance().config().experiments.customScreenMedia({
                        call: call
                    }).then(function (stream) {
                        var result = { stream: stream, renderer: null };
                        if (showLocalVideo) {
                            var localRenderer = new MediaRenderer_1.MediaRenderer(stream, 'sharing', true, true);
                            result.renderer = localRenderer;
                            _this7.dispatchEvent({ name: __1.default.HardwareEvents.MediaRendererAdded, renderer: localRenderer });
                            localRenderer.onBeforeDestroy = function () {
                                result.renderer = null;
                                _this7._sharingStreams[call.id()] = _this7._sharingStreams[call.id()].filter(function (sharingStream) {
                                    return sharingStream.stream.id !== stream.id;
                                });
                                call.peerConnection.removeCustomMedia(result.stream).then(function () {
                                    result.stream = undefined;
                                });
                            };
                        } else {
                            stream.getTracks().forEach(function (track) {
                                track.onended = function () {
                                    if (!stream.getTracks().some(function (item) {
                                        return item.readyState === 'live';
                                    })) {
                                        _this7._sharingStreams[call.id()] = _this7._sharingStreams[call.id()].filter(function (sharingStream) {
                                            return sharingStream.stream.id !== stream.id;
                                        });
                                        call.peerConnection.removeCustomMedia(result.stream).then(function () {
                                            result.stream = undefined;
                                        });
                                    }
                                };
                            });
                        }
                        if (typeof _this7._sharingStreams[call.id()] === 'undefined') {
                            _this7._sharingStreams[call.id()] = [];
                        }
                        _this7._sharingStreams[call.id()].push(result);
                        resolve(result);
                    }).catch(function (e) {
                        reject(e);
                    });
                });
            }
            return new Promise(function (resolve, reject) {
                BrowserSpecific_1.default.getScreenMedia().then(function (stream) {
                    var result = { stream: stream, renderer: null };
                    if (showLocalVideo) {
                        var localRenderer = new MediaRenderer_1.MediaRenderer(stream, 'sharing', true, true);
                        result.renderer = localRenderer;
                        _this7.dispatchEvent({ name: __1.default.HardwareEvents.MediaRendererAdded, renderer: localRenderer });
                        localRenderer.onBeforeDestroy = function () {
                            result.renderer = null;
                            _this7._sharingStreams[call.id()] = _this7._sharingStreams[call.id()].filter(function (sharingStream) {
                                return sharingStream.stream.id !== stream.id;
                            });
                            call.peerConnection.removeCustomMedia(result.stream).then(function () {
                                result.stream = undefined;
                            });
                        };
                    } else {
                        stream.getTracks().forEach(function (track) {
                            track.onended = function () {
                                if (!stream.getTracks().some(function (item) {
                                    return item.readyState === 'live';
                                })) {
                                    _this7._sharingStreams[call.id()] = _this7._sharingStreams[call.id()].filter(function (sharingStream) {
                                        return sharingStream.stream.id !== stream.id;
                                    });
                                    call.peerConnection.removeCustomMedia(result.stream).then(function () {
                                        result.stream = undefined;
                                    });
                                }
                            };
                        });
                    }
                    if (typeof _this7._sharingStreams[call.id()] === 'undefined') {
                        _this7._sharingStreams[call.id()] = [];
                    }
                    _this7._sharingStreams[call.id()].push(result);
                    resolve(result);
                }).catch(function (e) {
                    return reject(e);
                });
            });
        }
        /**
         * @hidden
         * @param {Call} call
         * @returns {Hardware.SharingStream[]}
         * @private
         */

    }, {
        key: "_getScreenSharing",
        value: function _getScreenSharing(call) {
            if (typeof this._sharingStreams[call.id()] !== 'undefined') return this._sharingStreams[call.id()];else return [];
        }
        /**
         * @hidden
         * @param {Call} call
         * @param {SharingStream} sharingStream
         * @returns {Promise<void>}
         * @private
         */

    }, {
        key: "_clearScreenSharing",
        value: function _clearScreenSharing(call, sharingStream) {
            var _this8 = this;

            return new Promise(function (resolve, reject) {
                _this8._sharingStreams[call.id()] = _this8._sharingStreams[call.id()].filter(function (exSharingStream) {
                    return exSharingStream.stream.id !== sharingStream.stream.id;
                });
                if (sharingStream.renderer) {
                    _this8.dispatchEvent({ name: __1.default.HardwareEvents.BeforeMediaRendererRemoved, renderer: sharingStream.renderer });
                    sharingStream.renderer.clear();
                    _this8.dispatchEvent({ name: __1.default.HardwareEvents.MediaRendererRemoved, renderer: sharingStream.renderer });
                    sharingStream.renderer = undefined;
                }
                var tracks = sharingStream.stream.getTracks();
                tracks.forEach(function (track) {
                    //sharingStream.stream.removeTrack(track);
                    track.stop();
                });
                resolve();
            });
        }
        /**
         * @hidden
         * @param {Call} call
         * @returns {{[p: string]: TrackType}}
         * @private
         */

    }, {
        key: "_getTracksKind",
        value: function _getTracksKind(call) {
            var returns = {};
            var localStreams = this._callStreams[call.id()];
            if (typeof localStreams !== 'undefined') {
                localStreams.getTracks().forEach(function (track) {
                    return returns[track.id] = track.kind;
                });
            }
            var sharingStreams = this._sharingStreams[call.id()];
            if (typeof sharingStreams !== 'undefined') {
                sharingStreams.forEach(function (sharingStream) {
                    sharingStream.stream.getTracks().forEach(function (track) {
                        return returns[track.id] = 'sharing';
                    });
                });
            }
            return returns;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'StreamManager';
        }
        /**
         * For onended and onmute callback of the mirror stream
         * @hidden
         */

    }, {
        key: "onMirrorEnded",
        value: function onMirrorEnded() {
            var _this9 = this;

            this.remMirrorStream();
            this.getMirrorStream().then(function (stream) {
                _this9.dispatchEvent({ name: __1.default.HardwareEvents.BeforeMediaRendererRemoved, renderer: stream });
                _this9.dispatchEvent({ name: __1.default.HardwareEvents.MediaRendererRemoved, renderer: null });
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "onCallEnded",
        value: function onCallEnded() {}
        /**
         * @hidden
         * @param {Call} call
         * @returns {Object}
         * @private
         */

    }, {
        key: "_composeConstraints",
        value: function _composeConstraints(call) {
            var callId = typeof call === 'undefined' ? '__default' : call.id();
            var constraints = {};
            if (callId !== '__default' && typeof call !== 'undefined' && call.settings.videoDirections.sendVideo) {
                constraints.video = CameraManager_1.CameraManager.get().getCallConstraints(callId);
            } else {
                constraints.video = false;
            }
            if (callId === '__default' || call.settings.audioDirections.sendAudio) {
                constraints.audio = AudioDeviceManager_1.AudioDeviceManager.get().getCallConstraints(callId);
            } else {
                constraints.audio = false;
            }
            return constraints;
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof StreamManager.instance === 'undefined') StreamManager.instance = new StreamManager();
            return StreamManager.instance;
        }
    }]);

    return StreamManager;
}(EventTarget_1.EventTarget);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "getMirrorStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "remMirrorStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "getCallStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_updateCallStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "updateCallStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "remCallStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_remCallStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "clear", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "getLocalMediaRenderers", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "showLocalVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "hideLocalVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "on", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "off", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_newScreenSharing", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_getScreenSharing", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_clearScreenSharing", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_getTracksKind", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "onMirrorEnded", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "onCallEnded", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], StreamManager.prototype, "_composeConstraints", null);
exports.StreamManager = StreamManager;

/***/ }),
/* 26 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var PCFactory_1 = __webpack_require__(9);
var TransceiversEndpointManager_1 = __webpack_require__(46);
var PlainEndpointManager_1 = __webpack_require__(47);
/**
 * @hidden
 */

var EndpointManager = function () {
    /**
     * Please, use the EndpointManager.get() instead create new object
     */
    function EndpointManager() {
        _classCallCheck(this, EndpointManager);

        throw new Error('Please, use the EndpointManager.get() instead create new object');
    }
    /**
     * Return instance of EndpointManager
     * @returns {ChromeEndpointManager}
     */


    _createClass(EndpointManager, null, [{
        key: "get",
        value: function get() {
            if (typeof this.instance == 'undefined') {
                if (PCFactory_1.PCFactory.hasTransceivers) EndpointManager.instance = new TransceiversEndpointManager_1.TransceiversEndpointManager();else EndpointManager.instance = new PlainEndpointManager_1.PlainEndpointManager();
            }
            return this.instance;
        }
    }]);

    return EndpointManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], EndpointManager, "get", null);
exports.EndpointManager = EndpointManager;

/***/ }),
/* 27 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Events that are triggered when Endpoint is updated/edited, removed or started/stopped to receive stream from another Endpoint.
 */
var EndpointEvents;
(function (EndpointEvents) {
  /**
   * Event is triggered when an Endpoint is updated/edited. E.g. when
   * a display name is changed via the [setDisplayName](https://voximplant.com/docs/references/voxengine/conference/endpoint#setdisplayname) method.
   * [Voxengine](https://voximplant.com/docs/references/voxengine) example:
   * ```javascript
   * require(Modules.Conference);
   * // ...
   * endpoint.setDisplayName("Chuck Spadina");
   * ```
   * Web SDK example:
   * ```javascript
   * Endpoint.on(Voximplant.EndpointEvents.InfoUpdated, (e)=>{
   *   console.log(e.endpoint.displayName);
   *   // > Chuck Spadina
   * });
   * ```
   * Handler function receives the [EventHandlers.EndpointHandler] object as an argument.
   */
  EndpointEvents[EndpointEvents["InfoUpdated"] = 'InfoUpdated'] = "InfoUpdated";
  /**
   * Event is triggered when an Endpoint is removed. E.g. when a participant left the conference or [player](https://voximplant.com/docs/references/voxengine/player) was removed.
   * Handler function receives the [EventHandlers.EndpointHandler] object as an argument.
   */
  EndpointEvents[EndpointEvents["Removed"] = 'Removed'] = "Removed";
  /**
   * Event is triggered when an Endpoint started to receive an audio / video / screensharing stream from another Endpoint.
   * __IMPORTANT__: if you subscribe to the event, Web SDK will no longer render remote audio/video stream automatically; you have to render remote streams manually via the [MediaRenderer.render] method.
   * Handler function receives the [EventHandlers.EndpointMediaHandler] object as an argument.
   */
  EndpointEvents[EndpointEvents["RemoteMediaAdded"] = 'RemoteMediaAdded'] = "RemoteMediaAdded";
  /**
   * Event is triggered when an Endpoint stopped to receive an audio / video / screensharing stream from another Endpoint.
   * Handler function receives the [EventHandlers.EndpointMediaHandler] object as an argument.
   */
  EndpointEvents[EndpointEvents["RemoteMediaRemoved"] = 'RemoteMediaRemoved'] = "RemoteMediaRemoved";
  /**
   * @hidden
   */
  EndpointEvents[EndpointEvents["RTCStatsReceived"] = 'RTCStatsReceived'] = "RTCStatsReceived";
})(EndpointEvents = exports.EndpointEvents || (exports.EndpointEvents = {}));

/***/ }),
/* 28 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Created by irbisadm on 23/09/2016.
 * @hidden
 */

var GUID = function () {
    function GUID(str) {
        _classCallCheck(this, GUID);

        this.str = str || GUID.getNewGUIDString();
    }

    _createClass(GUID, [{
        key: "toString",
        value: function toString() {
            return this.str;
        }
    }, {
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'GUID';
        }
    }], [{
        key: "getNewGUIDString",
        value: function getNewGUIDString() {
            // your favourite guid generation function could go here
            // ex: http://stackoverflow.com/a/8809472/188246
            var d = new Date().getTime();
            if (window.performance && typeof window.performance.now === "function") {
                d += performance.now(); //use high-precision timer if available
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = (d + Math.random() * 16) % 16 | 0;
                d = Math.floor(d / 16);
                return (c == 'x' ? r : r & 0x3 | 0x8).toString(16);
            });
        }
    }]);

    return GUID;
}();

exports.GUID = GUID;

/***/ }),
/* 29 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var GUID_1 = __webpack_require__(28);
var Message_1 = __webpack_require__(22);
var MsgEnums_1 = __webpack_require__(16);
var MsgSignaling_1 = __webpack_require__(15);
var ConversationManager_1 = __webpack_require__(39);
var index_1 = __webpack_require__(17);
var Messenger_1 = __webpack_require__(30);
/**
 * @hidden
 */

var Conversation = function () {
    /**
     * @hidden
     */
    function Conversation(participants, distinct, publicJoin, customData, moderators) {
        _classCallCheck(this, Conversation);

        this._distinct = distinct;
        this._publicJoin = publicJoin;
        this._participants = participants;
        this._customData = customData;
        this._moderators = moderators;
    }
    /**
     * Universally unique identifier of current conversation. Used in methods like 'get', 'remove' etc.
     * @returns {string}
     */


    _createClass(Conversation, [{
        key: "_getPayload",

        /**
         *
         * @hidden
         */
        value: function _getPayload() {
            if (typeof this._uuid == 'undefined') throw Error('You must create UUID with createUUID() function!');
            var str = {
                uuid: this._uuid,
                participants: this._prepareParticipants(this._participants)
            };
            if (typeof this._title != 'undefined') str['title'] = this._title;else str['title'] = '';
            if (typeof this._moderators != 'undefined') str['moderators'] = this._moderators;else str['moderators'] = [];
            if (typeof this._lastRead != 'undefined') str['last_readed'] = this._lastRead;
            if (typeof this._distinct != 'undefined') str['distinct'] = this._distinct;else str['distinct'] = false;
            if (typeof this._publicJoin != 'undefined') str['enable_public_join'] = this._publicJoin;else str['enable_public_join'] = false;
            if (typeof this._customData != 'undefined') str['custom_data'] = this._customData;else str['custom_data'] = {};
            if (typeof this._createdAt != 'undefined') str['created_at'] = this._createdAt;
            if (typeof this._createdAt != 'undefined') str['uber_conversation'] = this._uberConversation;
            return str;
        }
        /**
         *
         * @hidden
         */

    }, {
        key: "_getSimplePayload",
        value: function _getSimplePayload() {
            if (typeof this._uuid == 'undefined') throw Error('You must create UUID with createUUID() function!');
            return {
                uuid: this._uuid,
                title: typeof this._title != 'undefined' ? this._title : '',
                distinct: typeof this._distinct != 'undefined' ? this._distinct : false,
                enable_public_join: typeof this._publicJoin != 'undefined' ? this._publicJoin : false,
                custom_data: typeof this._customData != 'undefined' ? this._customData : {}
            };
        }
        /**
         * Generate UUID for new conversation
         *
         * @hidden
         */

    }, {
        key: "_createUUID",
        value: function _createUUID() {
            if (typeof this._uuid != 'undefined') throw Error('UUID already created!');
            this._uuid = new GUID_1.GUID().toString();
        }
        //==============msg part============
        /**
         * Serialize conversation so it can be stored into some storage (like IndexedDB) and later restored via [Messenger.createConversationFromCache]
         * @returns {SerializedConversation}
         */

    }, {
        key: "toCache",
        value: function toCache() {
            return {
                uuid: this._uuid,
                seq: this._lastSeq,
                lastUpdate: this._lastUpdate,
                moderators: this._moderators,
                title: this._title,
                createdAt: this._createdAt,
                lastRead: this._lastRead,
                distinct: this._distinct,
                publicJoin: this._publicJoin,
                participants: this._participants,
                customData: this._customData
            };
        }
    }, {
        key: "sendMessage",
        value: function sendMessage(message, payload) {
            var msg = new Message_1.Message(message, payload);
            msg.sendTo(this);
            return msg;
        }
        /**
         * Calling this method will inform backend that user is typing some text. Calls within 10s interval from the last call are discarded.
         * @returns {boolean} 'true' is message was actually sent, 'false' if it was discarded.
         */

    }, {
        key: "typing",
        value: function typing() {
            var _this = this;

            if (this._debounceLock) return false;
            setTimeout(function () {
                _this._debounceLock = false;
            }, 10000);
            this._debounceLock = true;
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.typingMessage, { conversation: this._uuid });
            return true;
        }
        /**
         * Mark the event with the specified sequence as 'read'. This affects 'lastRead' and is used to display unread messages and events. Triggers the [MessengerEvents.Read] event for all messenger objects on all connected clients, including this one.
         * @param seq
         */

    }, {
        key: "markAsRead",
        value: function markAsRead(seq) {
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.isRead, { conversation: this._uuid, seq: seq });
            this._lastRead = seq;
        }
        /**
         * Mark event as handled by current logged-in device. If single user is logged in on multiple devices, this can be used to display delivery status by subscribing to the [MessengerEvents.Delivered] event.
         * @param seq
         */

    }, {
        key: "markAsDelivered",
        value: function markAsDelivered(seq) {
            MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.isDelivered, { conversation: this._uuid, seq: seq });
        }
        /**
         * Remove current conversation. All participants, including this one, will receive the [MessengerEvents.RemoveConversation] event.
         */

    }, {
        key: "remove",
        value: function remove() {
            ConversationManager_1.ConversationManager.get().removeConversation(this._uuid);
        }
        /**
         * Send conversation changes to the server: title, public join flag, distinct flag and custom data. Used to send all changes modified via properties. Changes via 'setTitle', 'setPublicJoin' etc are sent instantly.
         */

    }, {
        key: "update",
        value: function update() {
            ConversationManager_1.ConversationManager.get().editConversation(this);
        }
        /**
         * Set the conversation title and send changes to the server.
         */

    }, {
        key: "setTitle",
        value: function setTitle(title) {
            this._title = title;
            ConversationManager_1.ConversationManager.get().editConversation(this);
        }
        /**
         * Set the public join flag and send changes to the server.
         */

    }, {
        key: "setPublicJoin",
        value: function setPublicJoin(publicJoin) {
            this._publicJoin = publicJoin;
            ConversationManager_1.ConversationManager.get().editConversation(this);
        }
        /**
         * Set the distinct flag and send changes to the server.
         */

    }, {
        key: "setDistinct",
        value: function setDistinct(distinct) {
            this._distinct = distinct;
            ConversationManager_1.ConversationManager.get().editConversation(this);
        }
        /**
         * Set the JS object custom data and send changes to the server.
         */

    }, {
        key: "setCustomData",
        value: function setCustomData(customData) {
            this._customData = customData;
            ConversationManager_1.ConversationManager.get().editConversation(this);
        }
        /**
         * Add new participants to the conversation.
         * Duplicated users are ignored.
         * Will fail if any user does not exist.
         * Triggers the [MessengerEvents.EditConversation]
         * event for all messenger objects on all clients, including this one.
         * @param participants
         * @returns {Promise<EditConversation>|Promise}
         */

    }, {
        key: "addParticipants",
        value: function addParticipants(participants) {
            var _this2 = this;

            return new Promise(function (resolve, reject) {
                if (participants.length == 0) reject();
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.addParticipants, { uuid: _this2._uuid, participants: _this2._prepareParticipants(participants) });
                Messenger_1.Messenger.getInstance()._registerPromise(index_1.default.MessengerEvents.EditConversation, resolve, reject);
            });
        }
        /**
         * Change access rights for the existing participants.
         * This function doesn't apply any changes to the participant list.
         * Use the [Conversation.addParticipants] or [Conversation.removeParticipants] methods instead.
         * Triggers the [MessengerEvents.EditConversation]
         * event for all messenger objects on all clients, including this one.
         * @param participants
         * @returns {Promise<EditConversation>|Promise}
         */

    }, {
        key: "editParticipants",
        value: function editParticipants(participants) {
            var _this3 = this;

            return new Promise(function (resolve, reject) {
                if (participants.length == 0) reject();
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.editParticipants, { uuid: _this3._uuid, participants: _this3._prepareParticipants(participants) });
                Messenger_1.Messenger.getInstance()._registerPromise(index_1.default.MessengerEvents.EditConversation, resolve, reject);
            });
        }
        /**
         * Remove participants from the conversation.
         * Duplicated users are ignored.
         * Will fail if any user does not exist.
         * Triggers the [MessengerEvents.EditConversation]
         * event for all messenger objects on all clients, including this one.
         * @param participants
         * @returns {Promise<EditConversation>|Promise}
         */

    }, {
        key: "removeParticipants",
        value: function removeParticipants(participants) {
            var _this4 = this;

            return new Promise(function (resolve, reject) {
                if (participants.length == 0) reject();
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.removeParticipants, {
                    uuid: _this4._uuid, participants: participants.map(function (item) {
                        if (typeof item.userId !== 'undefined') return item.userId;
                    })
                });
                Messenger_1.Messenger.getInstance()._registerPromise(index_1.default.MessengerEvents.EditConversation, resolve, reject);
            });
        }
        /**
         * Add new moderators to the conversation.
         * Duplicated users are ignored.
         * Will fail if any user does not exist.
         * Triggers the [MessengerEvents.EditConversation]
         * event for all messenger objects on all clients, including this one.
         * @param participants
         * @returns {Promise<EditConversation>|Promise}
         */

    }, {
        key: "addModerators",
        value: function addModerators(moderators) {
            var _this5 = this;

            return new Promise(function (resolve, reject) {
                if (moderators.length == 0) reject();
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.addModerators, { uuid: _this5._uuid, moderators: moderators });
                Messenger_1.Messenger.getInstance()._registerPromise(index_1.default.MessengerEvents.EditConversation, resolve, reject);
            });
        }
        /**
         * Remove moderators from the conversation.
         * Duplicated users are ignored.
         * Will fail if any user does not exist.
         * Triggers the [MessengerEvents.EditConversation]
         * event for all messenger objects on all clients, including this one.
         * @param participants
         * @returns {Promise<EditConversation>|Promise}
         */

    }, {
        key: "removeModerators",
        value: function removeModerators(moderators) {
            var _this6 = this;

            return new Promise(function (resolve, reject) {
                if (moderators.length == 0) reject();
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.removeModerators, { uuid: _this6._uuid, moderators: moderators });
                Messenger_1.Messenger.getInstance()._registerPromise(index_1.default.MessengerEvents.EditConversation, resolve, reject);
            });
        }
        /**
         * Request events in the specified sequence range to be sent from server into this client.
         * Maximum 100 events can be requested by one method call.
         * Sequence numbers of the resulting events may contain 'holes' due to the server-side implementation.
         * Method is used to get history or get missed events in case of network disconnect.
         * Please note that server will not push any events that was missed due to the client being offline.
         * Client should use this method to request all events based on the last event sequence received from the server and last event sequence saved locally (if any).
         * @param eventsFrom first event in range sequence, inclusive
         * @param eventsTo last event in range sequence, inclusive
         */

    }, {
        key: "retransmitEvents",
        value: function retransmitEvents(eventsFrom, eventsTo) {
            var _this7 = this;

            return new Promise(function (resolve, reject) {
                eventsFrom = eventsFrom | 0;
                eventsTo = eventsTo | 0;
                var callback = function callback(e) {
                    index_1.default.Messenger.getInstance().off(index_1.default.MessengerEvents.RetransmitEvents, callback);
                    index_1.default.Messenger.getInstance().off(index_1.default.MessengerEvents.Error, errorCallback);
                    resolve(e);
                };
                var errorCallback = function errorCallback(e) {
                    if (e.messengerAction == index_1.default.MessengerAction.getConversation) {
                        index_1.default.Messenger.getInstance().off(index_1.default.MessengerEvents.RetransmitEvents, callback);
                        index_1.default.Messenger.getInstance().off(index_1.default.MessengerEvents.Error, errorCallback);
                        reject(e);
                    }
                };
                index_1.default.Messenger.getInstance().on(index_1.default.MessengerEvents.RetransmitEvents, callback);
                index_1.default.Messenger.getInstance().on(index_1.default.MessengerEvents.Error, errorCallback);
                MsgSignaling_1.MsgSignaling.get().sendPayload(MsgEnums_1.MsgAction.retransmitEvents, {
                    uuid: _this7._uuid,
                    eventsFrom: eventsFrom,
                    eventsTo: eventsTo
                });
            });
        }
        /**
         * @hidden
         * @param newSeq
         */

    }, {
        key: "updateSeq",
        value: function updateSeq(newSeq) {
            if (newSeq > this._lastSeq) {
                this._lastSeq = newSeq;
            }
            this._lastUpdate = Date.now() / 1000 | 0;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Conversation';
        }
        /**
         * Correction participants list for backend
         * @returns {Array}
         * @hidden
         */

    }, {
        key: "_prepareParticipants",
        value: function _prepareParticipants(participants) {
            var ret = [];
            var _iteratorNormalCompletion = true;
            var _didIteratorError = false;
            var _iteratorError = undefined;

            try {
                for (var _iterator = participants[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                    var participant = _step.value;

                    if (typeof participant.userId != 'undefined') {
                        var item = { user_id: ConversationManager_1.ConversationManager.extractUserName(participant.userId) };
                        item['can_write'] = typeof participant.canWrite == 'undefined' ? true : participant.canWrite;
                        item['can_manage_participants'] = typeof participant.canManageParticipants == 'undefined' ? false : participant.canManageParticipants;
                        ret.push(item);
                    }
                }
            } catch (err) {
                _didIteratorError = true;
                _iteratorError = err;
            } finally {
                try {
                    if (!_iteratorNormalCompletion && _iterator.return) {
                        _iterator.return();
                    }
                } finally {
                    if (_didIteratorError) {
                        throw _iteratorError;
                    }
                }
            }

            return ret;
        }
    }, {
        key: "uuid",
        get: function get() {
            return this._uuid;
        }
        /**
         * Conversation moderator names list.
         */

    }, {
        key: "moderators",
        get: function get() {
            return this._moderators;
        }
    }, {
        key: "createdAt",
        get: function get() {
            return this._createdAt;
        }
    }, {
        key: "title",
        get: function get() {
            return this._title;
        }
        /**
         * Sets current conversation title. Note that setting this property does not send changes to the server. Use the 'update' to send all changes at once or 'setTitle' to update and set the title.
         * @param value
         */
        ,
        set: function set(value) {
            this._title = value;
        }
    }, {
        key: "distinct",
        get: function get() {
            return this._distinct;
        }
        /**
         * If two conversations are created with same set of users and moderators and both have 'distinct' flag, second create call will fail with the UUID of conversation already created. Note that changing users or moderators list will clear 'distinct' flag.
         * Note that setting this property does not send changes to the server. Use the 'update' to send all changes at once or 'setDistinct' to update and set the distinct flag.
         * @param value
         */
        ,
        set: function set(value) {
            this._distinct = value;
        }
    }, {
        key: "publicJoin",
        get: function get() {
            return this._publicJoin;
        }
        /**
         * If set to 'true', anyone can join conversation by UUID. Note that setting this property does not send changes to the server. Use the 'update' to send all changes at once or 'setPublicJoin' to update and set the public join flag.
         * @param value
         */
        ,
        set: function set(value) {
            this._publicJoin = value;
        }
        /**
         * Conversation participants list alongside with their rights.
         */

    }, {
        key: "participants",
        get: function get() {
            return this._participants;
        }
    }, {
        key: "customData",
        get: function get() {
            return this._customData;
        }
        /**
         * JavaScript object with custom data, up to 5kb. Note that setting this property does not send changes to the server. Use the 'update' to send all changes at once or 'setCustomData' to update and set the custom data.
         * @param value
         */
        ,
        set: function set(value) {
            this._customData = value;
        }
        /**
         * Last event sequence for this conversation. Used with 'lastRead' to display unread messages and events.
         */

    }, {
        key: "lastSeq",
        get: function get() {
            return this._lastSeq;
        }
        /**
         * UNIX timestamp integer that specifies the time of the last event in the conversation. It's same as 'Date.now()' divided by 1000.
         */

    }, {
        key: "lastUpdate",
        get: function get() {
            return this._lastUpdate;
        }
        /**
         * Returns sequence of last event that was read by user. Used to display unread messages, events etc.
         * @returns {any}
         */

    }, {
        key: "lastRead",
        get: function get() {
            return this._lastRead;
        }
        /**
         * @hidden
         * @return {boolean}
         */

    }, {
        key: "uberConversation",
        get: function get() {
            return this._uberConversation;
        }
        /**
         * Create conversation from buss
         * @param busConversation
         * @hidden
         */

    }], [{
        key: "_createFromBus",
        value: function _createFromBus(busConversation, seq) {
            var conversation = new Conversation([]);
            conversation._lastSeq = seq;
            conversation._uuid = busConversation.uuid;
            conversation._title = busConversation.title;
            conversation._moderators = busConversation.moderators;
            conversation._createdAt = busConversation.created_at;
            conversation._lastRead = busConversation.last_read;
            conversation._distinct = busConversation.distinct;
            conversation._publicJoin = busConversation.enable_public_join;
            conversation._uberConversation = busConversation.uber_conversation;
            if (busConversation.participants) conversation._participants = busConversation.participants.map(function (item) {
                return {
                    userId: item.user_id,
                    canWrite: item.can_write,
                    canManageParticipants: item.can_manage_participants
                };
            });
            if (busConversation.custom_data) conversation._customData = busConversation.custom_data;
            conversation._lastUpdate = busConversation.last_update;
            return conversation;
        }
        /**
         * Restore conversation from cache
         * @param cacheConversation
         * @returns {Conversation}
         * @hidden
         */

    }, {
        key: "createFromCache",
        value: function createFromCache(cacheConversation) {
            var conversation = new Conversation([]);
            conversation._uuid = cacheConversation.uuid;
            conversation._lastSeq = cacheConversation.seq;
            conversation._lastUpdate = cacheConversation.lastUpdate;
            conversation._title = cacheConversation.title;
            conversation._moderators = cacheConversation.moderators;
            conversation._createdAt = cacheConversation.createdAt;
            conversation._lastRead = cacheConversation.lastRead;
            conversation._distinct = cacheConversation.distinct;
            conversation._publicJoin = cacheConversation.publicJoin;
            conversation._participants = cacheConversation.participants;
            conversation._customData = cacheConversation.customData;
            conversation._uberConversation = cacheConversation.uberConversation;
            return conversation;
        }
    }]);

    return Conversation;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "_getPayload", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "_getSimplePayload", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "_createUUID", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "toCache", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "markAsRead", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "markAsDelivered", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "remove", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "update", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "addParticipants", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "editParticipants", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "removeParticipants", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "addModerators", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "removeModerators", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "retransmitEvents", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "updateSeq", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation.prototype, "_prepareParticipants", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation, "_createFromBus", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Conversation, "createFromCache", null);
exports.Conversation = Conversation;

/***/ }),
/* 30 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var MsgSignaling_1 = __webpack_require__(15);
var ConversationManager_1 = __webpack_require__(39);
var MsgEnums_1 = __webpack_require__(16);
var Logger_1 = __webpack_require__(0);
var Authenticator_1 = __webpack_require__(10);
var Conversation_1 = __webpack_require__(29);
var Message_1 = __webpack_require__(22);
var index_1 = __webpack_require__(17);
/**
 * @hidden
 */

var Messenger = function () {
    /**
     * @hidden
     */
    function Messenger() {
        var _this = this;

        _classCallCheck(this, Messenger);

        if (Messenger.instance) {
            throw new Error('Error - use Client.getIM()');
        }
        this.eventListeners = {};
        this.signalling = MsgSignaling_1.MsgSignaling.get();
        this.cm = ConversationManager_1.ConversationManager.get();
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onError, function (payload) {
            _this._dispatchEvent(index_1.default.MessengerEvents.Error, payload);
        });
        ConversationManager_1.ConversationManager.get();
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onEditUser, function (payload) {
            var eventPayload = payload.object;
            var checkedPayload = {
                user: {
                    customData: eventPayload.custom_data,
                    privateCustomData: eventPayload.private_custom_data,
                    userId: eventPayload.user_id
                },
                userId: payload.user_id,
                seq: payload.seq,
                onIncomingEvent: payload.on_incoming_event
            };
            _this._dispatchEvent(index_1.default.MessengerEvents.EditUser, checkedPayload);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onGetUser, function (payload) {
            var eventPayload = payload.object;
            var checkedPayload = {
                user: {
                    conversationsList: eventPayload.conversations_list,
                    leaveConversationList: eventPayload.leave_conversation_list,
                    customData: eventPayload.custom_data,
                    privateCustomData: eventPayload.private_custom_data,
                    userId: eventPayload.user_id
                },
                userId: payload.user_id,
                seq: payload.seq,
                onIncomingEvent: payload.on_incoming_event
            };
            _this._dispatchEvent(index_1.default.MessengerEvents.GetUser, checkedPayload);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onsubscribe, function (payload) {
            _this._dispatchEvent(index_1.default.MessengerEvents.Subscribe, { users: payload.users });
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onUnSubscribe, function (payload) {
            _this._dispatchEvent(index_1.default.MessengerEvents.Unsubscribe, { users: payload.users });
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onSetStatus, function (payload) {
            _this._dispatchEvent(index_1.default.MessengerEvents.SetStatus, {
                user: {
                    userId: payload.object.user_id,
                    online: payload.object.online,
                    timestamp: payload.object.timestamp
                },
                userId: payload.user_id,
                seq: payload.seq,
                onIncomingEvent: payload.on_incoming_event
            });
        });
        this.awaitPromiseList = [];
    }
    /**
     * @hidden
     */


    _createClass(Messenger, [{
        key: "createConversation",

        /**
         * Create a new conversation.
         * Triggers either the [MessengerEvents.Error] or [MessengerEvents.CreateConversation] event on all connected clients that are mentioned in the 'participants' array.
         * @see Messengerevents.CreateConversation
         * @see Messengerevents.Error
         * @param participants Array of participants alongside with access rights params
         * @param moderators Array of moderators
         * @param distinct If two conversations are created with same set of users and moderators and both have 'distinct' flag, second creation of conversation (with the same participants) will fail with the UUID of conversation already created. Note that changing users or moderators list will clear 'distinct' flag.
         * @param enablePublicJoin The feature allows users from any Voximplant account to join the conversation using its uuid.
         * @param customData JavaScript object with custom data, up to 5kb. Note that setting this property does not send changes to the server. Use [Conversation.update] to send all changes at once or [Conversation.setCustomData] to update and set the custom data.
         * @param title conversation title
         */
        value: function createConversation(participants, title, distinct, enablePublicJoin, customData, moderators) {
            this.cm.createConversation(participants, title, distinct, enablePublicJoin, customData, moderators);
        }
        /**
         * Get conversation by it's UUID.The method triggers the [MessengerEvents.GetConversation] or [MessengerEvents.Error] event.
         * The handler function receives the [EventHandlers.ConversationEvent] object with UUID etc.
         * @see [MessengerEvents.GetConversation]
         * @see [MessengerEvents.Error]
         * @param uuid
         */

    }, {
        key: "getConversation",
        value: function getConversation(uuid) {
            this.cm.getConversation(uuid);
        }
        /**
         * Get multiple conversations by array of UUIDs. Maximum 30 conversation. Note that calling this method will result in *multiple* 'getConversation' events.
         * @see [MessengerEvents.GetConversation]
         * @see [MessengerEvents.Error]
         * @param conversations Array of UUIDs
         * @returns {Array<Conversation>}
         */

    }, {
        key: "getConversations",
        value: function getConversations(conversations) {
            if (conversations.length > 30) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.MESSAGING, 'Rate limit', Logger_1.LogLevel.ERROR, 'you can get maximum 30 conversation in one getConversations');
                return;
            }
            return this.cm.getConversations(conversations);
        }
        /**
         * @hidden
         */

    }, {
        key: "getRawConversations",
        value: function getRawConversations(conversations) {
            return this.cm.getConversations(conversations);
        }
        /**
         * Remove the conversation specified by the UUID
         * @see [MessengerEvents.RemoveConversation]
         * @see [MessengerEvents.Error]
         * @param uuid Universally Unique Identifier of the conversation
         */

    }, {
        key: "removeConversation",
        value: function removeConversation(uuid) {
            this.cm.removeConversation(uuid);
        }
        /**
         * Join current user to the conversation specified by the UUID
         * @see [MessengerEvents.EditConversation]
         * @see [MessengerEvents.Error]
         * @param uuid Universally Unique Identifier of the conversation
         */

    }, {
        key: "joinConversation",
        value: function joinConversation(uuid) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.joinConversation, { uuid: uuid });
        }
        /**
         * Leave current user from the conversation specified by the UUID
         * @see [MessengerEvents.EditConversation]
         * @see [MessengerEvents.Error]
         * @param uuid  Universally Unique Identifier of the conversation
         */

    }, {
        key: "leaveConversation",
        value: function leaveConversation(uuid) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.leaveConversation, { uuid: uuid });
        }
        /**
         * Get user information for the user specified by the full Voximplant user identifier, ex 'username@appname.accname'
         * @see [MessengerEvents.GetUser]
         * @see [MessengerEvents.Error]
         * @param user_id User identifier
         */

    }, {
        key: "getUser",
        value: function getUser(user_id) {
            var _this2 = this;

            return new Promise(function (resolve, reject) {
                var resolveListener = function resolveListener(e) {
                    if (e.user.userId === user_id) {
                        resolve(e);
                        _this2.off(index_1.default.MessengerEvents.GetUser, resolveListener);
                        _this2.off(index_1.default.MessengerEvents.Error, rejectListener);
                    }
                };
                var rejectListener = function rejectListener(e) {
                    if (e.messengerAction == index_1.default.MessengerAction.getUser) {
                        reject(e);
                        _this2.off(index_1.default.MessengerEvents.GetUser, resolveListener);
                        _this2.off(index_1.default.MessengerEvents.Error, rejectListener);
                    }
                };
                _this2.on(index_1.default.MessengerEvents.GetUser, resolveListener);
                _this2.on(index_1.default.MessengerEvents.Error, rejectListener);
                _this2.signalling.sendPayload(MsgEnums_1.MsgAction.getUser, { user_id: user_id });
            });
        }
        /**
         * Get the full Voximplant user identifier, ex 'username@appname.accname', for the current user
         * @returns {string} current user short identifier
         */

    }, {
        key: "getMe",
        value: function getMe() {
            return ConversationManager_1.ConversationManager.extractUserName(Authenticator_1.Authenticator.get().username());
        }
        /**
         * Edit current user information.
         * @see [MessengerEvents.EditUser]
         * @see [MessengerEvents.Error]
         * @param custom_data Public custom data available for all users
         * @param private_custom_data Private custom data available only to the user themselves.
         */

    }, {
        key: "editUser",
        value: function editUser(customData, privateCustomData) {
            var user = { user_id: ConversationManager_1.ConversationManager.extractUserName(Authenticator_1.Authenticator.get().username()) };
            if (customData) user['custom_data'] = customData;
            if (privateCustomData) user['private_custom_data'] = privateCustomData;
            this.signalling.sendPayload(MsgEnums_1.MsgAction.editUser, user);
        }
        /**
         * Get user information for the users specified by the array of the full Voximplant user identifiers, ex 'username@appname.accname'
         * @see [MessengerEvents.GetUser]
         * @see [MessengerEvents.Error]
         * @param users List of user identifiers
         */

    }, {
        key: "getUsers",
        value: function getUsers(users) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.getUsers, { users: users });
        }
        /**
         * Register handler for the specified event
         * @hidden
         * @deprecated
         * @param event Event identifier
         * @param handler JavaScript function that will be called when the specified event is triggered. Please note that function is called without 'this' binding.
         */

    }, {
        key: "addEventListener",
        value: function addEventListener(event, handler) {
            if (typeof this.eventListeners[event] === 'undefined') this.eventListeners[event] = [];
            this.eventListeners[event].push(handler);
        }
        /**
         * Remove handler for the specified event
         * @hidden
         * @deprecated
         * @param event Event identifier
         * @param handler Reference to the JavaScript function to remove from event listeners. If not specified, removes all event listeners from the specified event.
         */

    }, {
        key: "removeEventListener",
        value: function removeEventListener(event, handler) {
            if (typeof this.eventListeners[event] === 'undefined') return;
            if (typeof handler === 'function') {
                for (var i = 0; i < this.eventListeners[event].length; i++) {
                    if (this.eventListeners[event][i] === handler) {
                        this.eventListeners[event].splice(i, 1);
                        break;
                    }
                }
            } else {
                this.eventListeners[event] = [];
            }
        }
        /**
         * @hidden
         * @param event
         * @param payload
         */

    }, {
        key: "_dispatchEvent",
        value: function _dispatchEvent(event, payload) {
            payload.name = index_1.default.MessengerEvents[event];
            if (typeof this.eventListeners[event] !== 'undefined') this.eventListeners[event].forEach(function (item) {
                if (typeof item === 'function') item(payload);
            });
            if (typeof this.awaitPromiseList[event] !== 'undefined' && this.awaitPromiseList[event].length != 0) {
                var nowPromise = this.awaitPromiseList[event].splice(0, 1);
                if (typeof nowPromise.resolve === 'undefined') nowPromise.resolve(payload);
                window.clearTimeout(nowPromise.expire);
            }
            if (typeof this.eventListeners[event] === 'undefined' || this.eventListeners[event].length == 0) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.MESSAGING, '', Logger_1.LogLevel.INFO, "The " + event + " event dispatched, but no handler registered for this event type.");
            }
        }
        /**
         * Register a handler for the specified event. The method is a shorter equivalent for *addEventListener*. One event can have more than one handler; handlers are executed in order of registration.
         * Use the [Messenger.off] method to delete a handler.
         * @param event
         * @param handler
         */

    }, {
        key: "on",
        value: function on(event, handler) {
            this.addEventListener(event, handler);
        }
        /**
         * Remove a handler for the specified event. The method is a shorter equivalent for *removeEventListener*. If a number of events has the same function as a handler, the method can be called multiple times with the same handler argument.
         * @param event
         * @param handler
         */

    }, {
        key: "off",
        value: function off(event, handler) {
            this.removeEventListener(event, handler);
        }
        /**
         * Add new promice for awaiting.
         * @param event
         * @param resolve
         * @param reject
         * @hidden
         */

    }, {
        key: "_registerPromise",
        value: function _registerPromise(event, resolve, reject) {
            if (typeof this.awaitPromiseList[event] === 'undefined') this.awaitPromiseList[event] = [];
            this.awaitPromiseList[event].push({
                resolve: resolve, reject: reject, expire: setTimeout(function () {
                    reject();
                }, 20000)
            });
        }
        /**
         * Restore conversation from cache that is previously created by the 'toCache' method.
         * @param cacheConversation JavaScript object for the serialized conversation
         * @returns {Conversation}
         */

    }, {
        key: "createConversationFromCache",
        value: function createConversationFromCache(cacheConversation) {
            if (typeof cacheConversation === 'undefined') return null;
            return Conversation_1.Conversation.createFromCache(cacheConversation);
        }
        /**
         * Restore message from cache that is previously created by the 'toCache' method.
         * @param cacheMessage JavaScript object for the serialized conversation
         * @returns {Message}
         */

    }, {
        key: "createMessageFromCache",
        value: function createMessageFromCache(cacheMessage) {
            if (typeof cacheMessage === 'undefined') return null;
            return Message_1.Message.createFromCache(cacheMessage);
        }
        /**
         * Subscribe for user information change and presence status change; a method call triggers the [MessengerEvents.Subscribe] event.
         * @see [MessengerEvents.Subscribe]
         * @see [MessengerEvents.Error]
         * @param users List of full Voximplant user identifiers, ex 'username@appname.accname'
         */

    }, {
        key: "subscribe",
        value: function subscribe(users) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.subscribe, { users: users });
        }
        /**
         * Unsubscribe for user information change and presence status change; a method call triggers the [MessengerEvents.Unsubscribe] event.
         * @see [MessengerEvents.Unsubscribe]
         * @see [MessengerEvents.Error]
         * @param users List of full Voximplant user identifiers, ex 'username@appname.accname'
         */

    }, {
        key: "unsubscribe",
        value: function unsubscribe(users) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.unsubscribe, { users: users });
        }
        /**
         * @hidden
         * @deprecated
         * @param status
         */

    }, {
        key: "setPresence",
        value: function setPresence(status) {
            this.setStatus(status);
        }
        /**
         * Set user presence status.
         * Triggers the [MessengerEvents.SetStatus] event for all messenger objects on all *connected* clients which are subscribed for notifications about this user. Including this one if conditions are met.
         * @see [MessengerEvents.SetStatus]
         * @see [MessengerEvents.Error]
         * @param status true if user is available for messaging.
         */

    }, {
        key: "setStatus",
        value: function setStatus(status) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.setStatus, { online: status });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'Messenger';
        }
    }], [{
        key: "getInstance",
        value: function getInstance() {
            Messenger.instance = Messenger.instance || new Messenger();
            return Messenger.instance;
        }
    }]);

    return Messenger;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "createConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getConversations", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getRawConversations", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "removeConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "joinConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "leaveConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getUser", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getMe", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "editUser", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "getUsers", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "addEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "removeEventListener", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "_dispatchEvent", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "on", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "off", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "_registerPromise", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "createConversationFromCache", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "createMessageFromCache", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "subscribe", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "unsubscribe", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "setPresence", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger.prototype, "setStatus", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], Messenger, "getInstance", null);
exports.Messenger = Messenger;

/***/ }),
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Enumeration of ACD statuses, use
 * [Client.setOperatorACDStatus] to set the status.
 *  For detailed information of ACD concept see the <a href="https://voximplant.com/docs/references/appengine/Module_ACD.html">Modules.ACD</a> documentation and the <a href="http://voximplant.com/docs/howto/#callcenter">appropriate HowTo's</a>.
 *
 * <img src="//voximplant.com/assets/images/2018/03/13/acdflow-2018-updated.svg" style="width: 500px;display: block;margin: 10px auto 0 auto;"/>
 *
 *
 * Example:
 * ``` js
 * // Enable ACD module
 * require(Modules.ACD);
 * //create Client instance and connect to the cloud
 * var vox = VoxImplant.getInstance();
 * vox.init({micRequired: true});
 * vox.addEventListener(VoxImplant.Events.SDKReady, function() {
 *   vox.connect();
 * });
 * //set the operator's status
 * vox.setOperatorACDStatus(VoxImplant.OperatorACDStatuses.Ready);
 * ```

 */
var OperatorACDStatuses;
(function (OperatorACDStatuses) {
  /**
   * Operator is offline
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status  |This status|To status|
   * |-------------|-----------|---------|
   * |NONE         |OFFLINE    |ONLINE   |
   * |ONLINE       |OFFLINE    |ONLINE   |
   * |READY        |OFFLINE    |ONLINE   |
   * |AFTER_SERVICE|OFFLINE    |ONLINE   |
   * |DND          |OFFLINE    |ONLINE   |
   * |TIMEOUT      |OFFLINE    |ONLINE   |
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["Offline"] = "OFFLINE"] = "Offline";
  /**
   * The operator is logged in, but not ready to handle incoming calls yet
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status  |This status|To status|
   * |-------------|-----------|---------|
   * |OFFLINE      |ONLINE     |READY    |
   * |READY        |OFFLINE    |ONLINE   |
   *
   * <strong>!!! Set status to ONLINE and then to READY, if you want to flush operator's ban (after missed call)</strong>
   */
  OperatorACDStatuses[OperatorACDStatuses["Online"] = "ONLINE"] = "Online";
  /**
   * Ready to handle incoming calls
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status  |This status|To status |
   * |-------------|-----------|----------|
   * |OFFLINE      |READY      |IN_SERVICE|
   * |DND          |READY      |ONLINE    |
   * |AFTER_SERVICE|READY      |DND       |
   * |TIMEOUT      |READY      |TIMEOUT   |
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["Ready"] = "READY"] = "Ready";
  /**
   * Incoming call is in service
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status|This status|To status    |
   * |-----------|-----------|-------------|
   * |READY      |IN_SERVICE |AFTER_SERVICE|
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["InService"] = "IN_SERVICE"] = "InService";
  /**
   * An incoming call has ended and now an operator is processing after service work.
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status|This status  |To status|
   * |-----------|-------------|---------|
   * |IN_SERVICE |AFTER_SERVICE|READY    |
   * |IN_SERVICE |AFTER_SERVICE|TIMEOUT  |
   * |IN_SERVICE |AFTER_SERVICE|DND      |
   * |IN_SERVICE |AFTER_SERVICE|OFFLINE  |
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["AfterService"] = "AFTER_SERVICE"] = "AfterService";
  /**
   * The operator is on a break (e.g. having lunch).
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status  |This status|To status|
   * |-------------|-----------|---------|
   * |READY        |TIMEOUT    |READY    |
   * |AFTER_SERVICE|TIMEOUT    |READY    |
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["Timeout"] = "TIMEOUT"] = "Timeout";
  /**
   * The operator is busy now and not ready to handle incoming calls (e.g. working on another call)
   *
   *
   * <strong>Recommended logic flow</strong>
   *
   * |From status  |This status|To status|
   * |-------------|-----------|---------|
   * |READY        |DND        |READY    |
   * |AFTER_SERVICE|DND        |READY    |
   *
   */
  OperatorACDStatuses[OperatorACDStatuses["DND"] = "DND"] = "DND";
})(OperatorACDStatuses = exports.OperatorACDStatuses || (exports.OperatorACDStatuses = {}));

/***/ }),
/* 32 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var StreamManager_1 = __webpack_require__(25);
/**
 * @hidden
 */

var AudioDeviceManager = function () {
    /**
     * @hidden
     */
    function AudioDeviceManager() {
        _classCallCheck(this, AudioDeviceManager);

        if (typeof AudioDeviceManager.instance !== 'undefined') throw new Error('Error - use StreamManager.get()');
        if (navigator.mediaDevices.getSupportedConstraints) this._supportedConstraints = navigator.mediaDevices.getSupportedConstraints();else this._supportedConstraints = {};
        this.__defaultParams = {};
        this._lastAudioInputDevices = [];
        this._lastAudioOutputDevices = [];
        this._callParams = {};
    }
    /**
     * @hidden
     */


    _createClass(AudioDeviceManager, [{
        key: "getAudioContext",
        value: function getAudioContext() {
            if (this.audioContext) return this.audioContext;
            if (typeof window.AudioContext != 'undefined' || typeof window.webkitAudioContext != 'undefined') {
                window.AudioContext = window.AudioContext || window.webkitAudioContext;
                try {
                    this.audioContext = new AudioContext();
                    return this.audioContext;
                } catch (e) {
                    this.audioContext = null;
                    return null;
                }
            }
        }
        /**
         * Create an AudioContext object inside SDK. This function must be used on a user gesture at Google Chrome 66 and above
         * See <a href="https://developers.google.com/web/updates/2017/09/autoplay-policy-changes#webaudio"> Google Developers Blog post</a> about this issue
         */

    }, {
        key: "prepareAudioContext",
        value: function prepareAudioContext() {
            this.getAudioContext();
        }
        /**
         * Get the AudioDeviceManager instance
         */

    }, {
        key: "getInputDevices",

        /**
         * Return available audio input devices (sound card/processor). Note that if new passive microphone was plugged into the same sound card, the method will return that sound card; if new microphone has its own sound processor, the method will return the updated array with new device.
         */
        value: function getInputDevices() {
            var _this = this;

            return navigator.mediaDevices.enumerateDevices().then(function (devices) {
                _this._lastAudioInputDevices = devices.map(function (device) {
                    if (device.kind === 'audio' || device.kind === 'audioinput') {
                        return {
                            id: device.deviceId,
                            name: device.label,
                            group: device.groupId
                        };
                    }
                }).filter(function (e) {
                    return typeof e !== "undefined";
                });
                return _this._lastAudioInputDevices;
            });
        }
        /**
         * Return available audio output devices (sound card/processor). If new plugged device has its own sound processor, the method will return the updated array with new device.
         */

    }, {
        key: "getOutputDevices",
        value: function getOutputDevices() {
            var _this2 = this;

            return navigator.mediaDevices.enumerateDevices().then(function (devices) {
                _this2._lastAudioOutputDevices = devices.map(function (device) {
                    if (device.kind === 'audiooutput') {
                        return {
                            id: device.deviceId,
                            name: device.label,
                            group: device.groupId
                        };
                    }
                }).filter(function (e) {
                    return typeof e !== "undefined";
                });
                return _this2._lastAudioOutputDevices;
            });
        }
        /**
         * Return default audio settings as the [AudioParams] object.
         */

    }, {
        key: "getDefaultAudioSettings",
        value: function getDefaultAudioSettings() {
            return this.__defaultParams;
        }
        /**
         * Set default audio settings for calls.
         */

    }, {
        key: "setDefaultAudioSettings",
        value: function setDefaultAudioSettings(params) {
            this.__defaultParams = params;
        }
        /**
         * Set audio settings for specified call.
         */

    }, {
        key: "setCallAudioSettings",
        value: function setCallAudioSettings(call, params) {
            var _this3 = this;

            return new Promise(function (resolve, reject) {
                if (_this3._callParams[call.id()] === params) resolve();
                var mustUpdateRenderers = _this3._callParams[call.id()].outputId !== params.outputId;
                var mustUpdateSource = _this3._callParams[call.id()].inputId !== params.inputId || _this3._callParams[call.id()].noiseSuppression !== params.noiseSuppression || _this3._callParams[call.id()].echoCancellation !== params.echoCancellation || _this3._callParams[call.id()].disableAudio !== params.disableAudio;
                _this3._callParams[call.id()] = params;
                if (mustUpdateRenderers) {
                    call.getEndpoints().forEach(function (ep) {
                        ep.mediaRenderers.forEach(function (mr) {
                            return mr.useAudioOutput(params.outputId);
                        });
                    });
                }
                if (mustUpdateSource) {
                    StreamManager_1.StreamManager.get().updateCallStream(call).then(function () {
                        resolve();
                    }).catch(function (e) {
                        reject(e);
                    });
                } else {
                    resolve();
                }
            });
        }
        /**
         * Return audio settings of specified call as the [AudioParams] object.
         */

    }, {
        key: "getCallAudioSettings",
        value: function getCallAudioSettings(call) {
            return this._callParams[call.id()];
        }
        /**
         * @hidden
         */

    }, {
        key: "getCallConstraints",
        value: function getCallConstraints(callID) {
            if (this._callParams[callID]) return this._getAudioConstraints(this._callParams[callID]);else {
                this._callParams[callID] = this.__defaultParams;
                return this._getAudioConstraints(this.__defaultParams);
            }
        }
        /**
         * @hidden
         */

    }, {
        key: "_getAudioConstraints",
        value: function _getAudioConstraints(params) {
            if (params.disableAudio) return false;
            var constraintsType = 'ideal';
            if (params.strict) constraintsType = 'exact';
            var audioConstraints = {};
            if (params.inputId) {
                if (this._lastAudioInputDevices) {
                    if (this._lastAudioInputDevices.some(function (item) {
                        return item.id === params.inputId;
                    })) {
                        audioConstraints['deviceId'] = {};
                        audioConstraints['deviceId'][constraintsType] = params.inputId;
                    } else Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Warning:', Logger_1.LogLevel.WARNING, "There is no audio input device with id " + params.inputId);
                } else {
                    Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Warning:', Logger_1.LogLevel.WARNING, "There is no audio input device with id " + params.inputId);
                }
            }
            if (params.echoCancellation && this._supportedConstraints['echoCancellation']) {
                audioConstraints['echoCancellation'] = params.echoCancellation;
            }
            if (params.noiseSuppression && this._supportedConstraints['noiseSuppression']) {
                audioConstraints['noiseSuppression'] = params.echoCancellation;
            }
            if (params.autoGainControl && this._supportedConstraints['autoGainControl']) {
                audioConstraints['autoGainControl'] = params.autoGainControl;
            }
            if (Object.keys(audioConstraints)) return audioConstraints;else return true;
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'AudioDeviceManager';
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof AudioDeviceManager.instance === 'undefined') AudioDeviceManager.instance = new AudioDeviceManager();
            return AudioDeviceManager.instance;
        }
    }]);

    return AudioDeviceManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "getInputDevices", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "getOutputDevices", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "getDefaultAudioSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "setDefaultAudioSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "setCallAudioSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "getCallAudioSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "getCallConstraints", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager.prototype, "_getAudioConstraints", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], AudioDeviceManager, "get", null);
exports.AudioDeviceManager = AudioDeviceManager;

/***/ }),
/* 33 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var StreamManager_1 = __webpack_require__(25);
var __1 = __webpack_require__(4);
/**
 * @hidden
 */

var CameraManager = function () {
    /**
     * @hidden
     */
    function CameraManager() {
        _classCallCheck(this, CameraManager);

        if (typeof CameraManager.instance !== 'undefined') throw new Error('Error - use StreamManager.get()');
        if (navigator.mediaDevices.getSupportedConstraints) this._supportedConstraints = navigator.mediaDevices.getSupportedConstraints();else this._supportedConstraints = {};
        this._callParams = {};
        this._lastCameraDevices = [];
        this.__defaultParams = {};
    }
    /**
     * Get the CameraManager instance
     */


    _createClass(CameraManager, [{
        key: "setDefaultVideoSettings",

        /**
         * Set default video settings for calls.
         */
        value: function setDefaultVideoSettings(params) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                var validParams = _this._validateCameraParams(params);
                _this.__defaultParams = validParams;
                resolve(null);
            });
        }
        /**
         * Return default audio settings as the [CameraParams] object.
         */

    }, {
        key: "getDefaultVideoSettings",
        value: function getDefaultVideoSettings() {
            return this.__defaultParams;
        }
        /**
         * Set video settings for specified call.
         */

    }, {
        key: "setCallVideoSettings",
        value: function setCallVideoSettings(call, params) {
            var validParams = this._validateCameraParams(params);
            this._callParams[call.id()] = validParams;
            return new Promise(function (resolve, reject) {
                StreamManager_1.StreamManager.get().updateCallStream(call).then(function () {
                    resolve();
                }).catch(function (e) {
                    reject(e);
                });
            });
        }
        /**
         * Return video settings of specified call as the [CameraParams] object.
         */

    }, {
        key: "getCallVideoSettings",
        value: function getCallVideoSettings(call) {
            return this._callParams[call.id()];
        }
        /**
         * @hidden
         */

    }, {
        key: "getCallConstraints",
        value: function getCallConstraints(callID) {
            if (this._callParams[callID]) return this._getVideoConstraints(this._callParams[callID]);else {
                this._callParams[callID] = this.__defaultParams;
                return this._getVideoConstraints(this.__defaultParams);
            }
        }
        /**
         * Return available video input devices (web camera(s)).
         */

    }, {
        key: "getInputDevices",
        value: function getInputDevices() {
            var _this2 = this;

            return navigator.mediaDevices.enumerateDevices().then(function (devices) {
                _this2._lastCameraDevices = devices.map(function (device) {
                    if (device.kind === 'video' || device.kind === 'videoinput') {
                        return {
                            id: device.deviceId,
                            name: device.label,
                            group: device.groupId
                        };
                    }
                }).filter(function (e) {
                    return typeof e !== "undefined";
                });
                return _this2._lastCameraDevices;
            });
        }
        /**
         * @hidden
         */

    }, {
        key: "_getVideoConstraints",
        value: function _getVideoConstraints(params) {
            var constraintsType = 'ideal';
            var videoConstraints = {};
            if (params.cameraId) {
                if (this._lastCameraDevices) {
                    if (this._lastCameraDevices.some(function (item) {
                        return item.id === params.cameraId;
                    })) {
                        videoConstraints['deviceId'] = {};
                        videoConstraints['deviceId'][constraintsType] = params.cameraId;
                    } else Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Warning:', Logger_1.LogLevel.WARNING, "There is no video device with id " + params.cameraId);
                } else Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Warning:', Logger_1.LogLevel.WARNING, "There is no video device with id " + params.cameraId);
            } else if (typeof params.facingMode !== 'undefined') {
                if (params.facingMode === false) {
                    videoConstraints['facingMode'] = 'environment';
                } else {
                    videoConstraints['facingMode'] = 'user';
                }
            }
            if (params.frameHeight) {
                videoConstraints['height'] = {};
                if (params.strict) {
                    videoConstraints['height']['min'] = params.frameHeight;
                } else videoConstraints['height'][constraintsType] = params.frameHeight;
            }
            if (params.frameWidth) {
                videoConstraints['width'] = {};
                if (params.strict) {
                    videoConstraints['width']['min'] = params.frameWidth;
                } else videoConstraints['width'][constraintsType] = params.frameWidth;
            }
            if (params.frameRate && params.frameRate > 0 && this._supportedConstraints['frameRate']) {
                videoConstraints['frameRate'] = params.frameRate + '';
            }
            if (Object.keys(videoConstraints)) return videoConstraints;else return true;
        }
        /**
         * @hidden
         */

    }, {
        key: "_validateCameraParams",
        value: function _validateCameraParams(params) {
            if (params.videoQuality) {
                if (params.frameHeight || params.frameWidth) Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Warning:', Logger_1.LogLevel.WARNING, '"videoQuality" parameter detected. The "frameHeight" ' + 'and the "frameWidth" params will be ignored');
                var format = this._videoQualityToSize(params.videoQuality);
                params.frameWidth = format.w;
                params.frameHeight = format.h;
            }
            return params;
        }
    }, {
        key: "_videoQualityToSize",
        value: function _videoQualityToSize(qualityEnum) {
            switch (qualityEnum) {
                case __1.Hardware.VideoQuality.VIDEO_QUALITY_HIGH:
                    return { w: 1280, h: 720 };
                case __1.Hardware.VideoQuality.VIDEO_QUALITY_MEDIUM:
                    return { w: 640, h: 480 };
                case __1.Hardware.VideoQuality.VIDEO_QUALITY_LOW:
                    return { w: 320, h: 240 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_QQVGA:
                    return { w: 160, h: 120 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_QCIF:
                    return { w: 176, h: 144 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_QVGA:
                    return { w: 320, h: 240 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_CIF:
                    return { w: 352, h: 288 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_nHD:
                    return { w: 640, h: 360 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_VGA:
                    return { w: 640, h: 480 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_SVGA:
                    return { w: 800, h: 600 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_HD:
                    return { w: 1280, h: 720 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_UXGA:
                    return { w: 1600, h: 1200 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_FHD:
                    return { w: 1920, h: 1080 };
                case __1.Hardware.VideoQuality.VIDEO_SIZE_UHD:
                    return { w: 3840, h: 2160 };
                default:
                    return { w: 320, h: 240 };
            }
        }
        /**
         * @hidden
         */

    }, {
        key: "testResolutions",

        /**
         * Start camera resolution test for each video source in system.</br>
         * *Attention!* This procedure may take a lot of time and will send multiple Camera requests for
         * the Mozilla Firefox and Apple Safari browsers!</br>
         * Please, don't run it without warning user's request and attention.</br>
         * After running this function, please, save result to a browser storage (like LocalStorage or IndexedDB) and use it
         * in future with the [loadResolutionTestResult] function to restore
         * results.
         * This function mandatory only if you will use Hardware.VideoQuality.VIDEO_QUALITY_HIGH,Hardware.VideoQuality.VIDEO_QUALITY_MEDIUM or
         * Hardware.VideoQuality.VIDEO_QUALITY_LOW enums as video settings and strongly not recommended to use in another case.
         * @returns {Promise<any>}
         */
        value: function testResolutions(cameraId) {
            var _this3 = this;

            var testQuality = [__1.Hardware.VideoQuality.VIDEO_SIZE_QQVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_QCIF, __1.Hardware.VideoQuality.VIDEO_SIZE_QVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_CIF, __1.Hardware.VideoQuality.VIDEO_SIZE_nHD, __1.Hardware.VideoQuality.VIDEO_SIZE_VGA, __1.Hardware.VideoQuality.VIDEO_SIZE_SVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_HD, __1.Hardware.VideoQuality.VIDEO_SIZE_UXGA, __1.Hardware.VideoQuality.VIDEO_SIZE_FHD, __1.Hardware.VideoQuality.VIDEO_SIZE_UHD];
            if (this._lastResolutionTestResult) {
                return new Promise(function (resolve, reject) {
                    resolve(_this3._lastResolutionTestResult);
                });
            } else {
                return this._testResolutions(testQuality, {}, cameraId);
            }
        }
        /**
         * @hidden
         * @param {Hardware.Hardware.VideoQuality[]} testQuality
         * @param result
         * @param {string} cameraId
         * @returns {Promise<any>}
         * @private
         */

    }, {
        key: "_testResolutions",
        value: function _testResolutions(testQuality, result, cameraId) {
            var _this4 = this;

            if (testQuality.length) {
                var quality = testQuality.shift();
                var settings = {
                    strict: true
                };
                var format = this._videoQualityToSize(quality);
                settings.frameWidth = format.w;
                settings.frameHeight = format.h;
                if (cameraId) {
                    settings.cameraId = cameraId;
                }
                var constrains = { video: this._getVideoConstraints(settings) };
                return navigator.mediaDevices.getUserMedia(constrains).then(function (e) {
                    e.getTracks().forEach(function (t) {
                        return t.stop();
                    });
                    result[__1.Hardware.VideoQuality[quality]] = true;
                    return _this4._testResolutions(testQuality, result, cameraId);
                }, function (e) {
                    result[__1.Hardware.VideoQuality[quality]] = false;
                    return _this4._testResolutions(testQuality, result, cameraId);
                });
            } else {
                this._lastResolutionTestResult = result;
                return result;
            }
        }
        /**
         * Restoring a camera resolution test result previously got by [testResolutions]
         * function.
         * @returns {Promise<void>}
         */

    }, {
        key: "loadResolutionTestResult",
        value: function loadResolutionTestResult(data) {
            var testQuality = [__1.Hardware.VideoQuality.VIDEO_SIZE_QQVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_QCIF, __1.Hardware.VideoQuality.VIDEO_SIZE_QVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_CIF, __1.Hardware.VideoQuality.VIDEO_SIZE_nHD, __1.Hardware.VideoQuality.VIDEO_SIZE_VGA, __1.Hardware.VideoQuality.VIDEO_SIZE_SVGA, __1.Hardware.VideoQuality.VIDEO_SIZE_HD, __1.Hardware.VideoQuality.VIDEO_SIZE_UXGA, __1.Hardware.VideoQuality.VIDEO_SIZE_FHD, __1.Hardware.VideoQuality.VIDEO_SIZE_UHD];
            if (!testQuality.every(function (item) {
                return typeof data[__1.Hardware.VideoQuality[item]] !== "undefined";
            })) {
                return false;
            } else {
                this._lastResolutionTestResult = data;
                return true;
            }
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'CameraManager';
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof CameraManager.instance === 'undefined') CameraManager.instance = new CameraManager();
            return CameraManager.instance;
        }
    }, {
        key: "legacyParamConverter",
        value: function legacyParamConverter(videoParams) {
            var params = {
                videoQuality: __1.Hardware.VideoQuality.VIDEO_QUALITY_MEDIUM
            };
            if (videoParams.width) {
                if (typeof videoParams.width === 'string' || typeof videoParams.width === 'number') {
                    delete params.videoQuality;
                    params.frameWidth = videoParams.width;
                } else if (typeof videoParams.width.exact === 'string' || typeof videoParams.width.exact === 'number') {
                    delete params.videoQuality;
                    params.frameWidth = videoParams.width.exact;
                    params.strict = true;
                } else if (typeof videoParams.width.min === 'string' || typeof videoParams.width.min === 'number') {
                    delete params.videoQuality;
                    params.frameWidth = videoParams.width.min;
                } else if (typeof videoParams.width.max === 'string' || typeof videoParams.width.max === 'number') {
                    delete params.videoQuality;
                    params.frameWidth = videoParams.width.max;
                } else if (typeof videoParams.width.ideal === 'string' || typeof videoParams.width.ideal === 'number') {
                    delete params.videoQuality;
                    params.frameWidth = videoParams.width.ideal;
                }
            }
            if (videoParams.height) {
                if (typeof videoParams.height === 'string' || typeof videoParams.height === 'number') {
                    delete params.videoQuality;
                    params.frameHeight = videoParams.height;
                } else if (typeof videoParams.height.exact === 'string' || typeof videoParams.height.exact === 'number') {
                    delete params.videoQuality;
                    params.frameHeight = videoParams.height.exact;
                    params.strict = true;
                } else if (typeof videoParams.height.min === 'string' || typeof videoParams.height.min === 'number') {
                    delete params.videoQuality;
                    params.frameHeight = videoParams.height.min;
                } else if (typeof videoParams.height.max === 'string' || typeof videoParams.height.max === 'number') {
                    delete params.videoQuality;
                    params.frameHeight = videoParams.height.max;
                } else if (typeof videoParams.height.ideal === 'string' || typeof videoParams.height.ideal === 'number') {
                    delete params.videoQuality;
                    params.frameHeight = videoParams.height.ideal;
                }
            }
            return params;
        }
    }]);

    return CameraManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "setDefaultVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "getDefaultVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "setCallVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "getCallVideoSettings", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "getCallConstraints", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "getInputDevices", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "_getVideoConstraints", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager.prototype, "_validateCameraParams", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager, "get", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.HARDWARE)], CameraManager, "legacyParamConverter", null);
exports.CameraManager = CameraManager;

/***/ }),
/* 34 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var BrowserSpecific_1 = __webpack_require__(8);
var Client_1 = __webpack_require__(1);
var Logger_1 = __webpack_require__(0);
var Utils_1 = __webpack_require__(23);
/**
 * It is the wrapper for the HTMLMediaElement and its MediaStream.
 *   You can get this object on
 *   the [HardwareEvents.MediaRendererAdded] and
 *   [HardwareEvents.MediaRendererRemoved] for local media.
 *
 *   For remote media sources, you can get an instance
 *   of this object from [Endpoint] or
 *   [EndpointEvents.RemoteMediaAdded]
 *   or
 *   [EndpointEvents.RemoteMediaRemoved]
 */

var MediaRenderer = function () {
    /**
     * Create new MediaRenderer for a local or a remote media stream
     * @param {MediaStream} stream
     * @param {"audio" | "video" | "sharing"} kind
     * @param {boolean} placeOnDom
     * @param {boolean} isLocal
     * @hidden
     */
    function MediaRenderer(
    /**
     * A source stream sended from/to some Endpoint. The type of a stream is specified via the [kind] property.
     *
     * You can use the property for modifying and filtering source streams. E.g. for face masks and CV (computer vision).
     */
    stream,
    /**
     * Describe the tag and type of media, which are placed in this container.
     *   <ul>
     *   <li>Kind "audio" means &lt;audio&gt; HTML element and sound-only media stream</li>
     *   <li>Kind "video" means &lt;video&gt; HTML element and either video-only or audio plus video media stream</li>
     *   <li>Kind "sharing" the same as kind "video", but literally tell you "This is screen sharing"</li>
     *   </ul>
     */
    kind) {
        var placeOnDom = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
        var isLocal = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;

        var _this = this;

        var deprecatedId = arguments[4];
        var isConference = arguments[5];

        _classCallCheck(this, MediaRenderer);

        this.stream = stream;
        this.kind = kind;
        this.placeOnDom = placeOnDom;
        this.isLocal = isLocal;
        /**
         * @hidden
         * @type {boolean}
         */
        this.isRemoving = false;
        if (typeof this.kind === "undefined") {
            this.kind = stream.getVideoTracks().length ? "video" : "audio";
        }
        this._id = Utils_1.Utils.generateUUID();
        this._logger = new Logger_1.Logger(Logger_1.LogCategory.USERMEDIA, 'MediaRenderer', Logger_1.LogManager.get());
        this.stream.getTracks().forEach(function (track) {
            track.onended = function () {
                _this.checkStreamActive(stream);
            };
        });
        if (this.stream.getTracks().length) {
            this.element = document.getElementById(this.stream.getTracks()[0].id) || document.createElement(this.kind === 'sharing' ? 'video' : this.kind);
            this.element.autoplay = true;
            if (this.isLocal) this.element.muted = true;
            this.element.setAttribute('playsinline', null);
            if (deprecatedId) {
                if (document.getElementById('voximplantlocalvideo')) this.element.id = this.stream.getTracks()[0].id;else this.element.id = 'voximplantlocalvideo';
            } else {
                this.element.id = this.stream.getTracks()[0].id;
            }
            if (kind !== 'audio') {
                this.element.width = 400;
                this.element.height = 300;
            }
            BrowserSpecific_1.default.attachMedia(this.stream, this.element);
            if (this.placeOnDom) {
                this.renderDefault();
            }
        }
    }
    /**
     * @hidden
     * @param {MediaStream} stream
     */


    _createClass(MediaRenderer, [{
        key: "checkStreamActive",
        value: function checkStreamActive(stream) {
            if (!stream.getTracks().some(function (item) {
                return item.readyState === 'live';
            })) {
                this.clear();
            }
        }
        /**
         * Unique ID of MediaRender
         */

    }, {
        key: "renderDefault",

        /**
         * @hidden
         */
        value: function renderDefault() {
            var client = Client_1.Client.getInstance();
            var containerId = this.isLocal ? client.config().localVideoContainerId : client.config().remoteVideoContainerId;
            var container = document.getElementById(containerId);
            this.render(container);
        }
        /**
         * Render (display) current instance of MediaRenderer to the HTMLElement in the DOM tree. If the container paramater is not specified, the method will append rendering to the body element.
         * The method allows to render manually in cases of:
         * 1. Default rendering was turned off. If you subscribe to the [EndpointEvents.RemoteMediaAdded] event, Web SDK will no longer render remote audio/video stream automatically so you have to call this method with optional __container__ parameter.
         * 2. default rendering is active, but you want to change rendering container. Call the method with the specified HTMLElement.
         * @param {HTMLElement} container place for rendering.
         */

    }, {
        key: "render",
        value: function render(container) {
            var _this2 = this;

            if (Client_1.Client.getInstance().config() && Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.preventRendering) {
                return;
            }
            var parrent = container || document.body;
            if (typeof this.element.parentElement != 'undefined' && this.element.parentElement !== null) {
                if (this.element.parentElement) this.element.parentElement.removeChild(this.element);
            } else if (this.element.parentNode) {
                if (this.element.parentNode) this.element.parentNode.removeChild(this.element);
            }
            parrent.appendChild(this.element);
            this.element.play().then(function () {}, function (e) {
                setTimeout(function () {
                    _this2.element.play().then(function () {}, function (e) {
                        _this2._logger.warning("Can't start playing MediaRenderer ID:" + _this2._id);
                    });
                }, 400);
            });
        }
        /**
         * Destroy current unit and free resources.
         * @hidden
         */

    }, {
        key: "clear",
        value: function clear() {
            if (this.isRemoving) {
                this._logger.trace("MediaRendered ID:" + this._id + " already removing. Ignored.");
                return;
            }
            this.isRemoving = true;
            if (this.onBeforeDestroy) this.onBeforeDestroy();
            if (this.element) {
                BrowserSpecific_1.default.detachMedia(this.element);
                this.element.id = '';
                if (typeof this.element.parentElement != 'undefined' && this.element.parentElement !== null) {
                    if (this.element.parentElement) this.element.parentElement.removeChild(this.element);
                } else if (this.element.parentNode) {
                    if (this.element.parentNode) this.element.parentNode.removeChild(this.element);
                }
            }
            if (this.onDestroy) this.onDestroy();
        }
        /**
         * Set current MediaRenderer output volume. The range is from 0 to 1.
         * @param {number} level
         */

    }, {
        key: "setVolume",
        value: function setVolume(level) {
            if (this.element) {
                this.element.volume = level;
            }
        }
        /**
         * Set the output audio device for current MediaRenderer. ID can be retrieved via the [AudioDeviceManager.getOutputDevices] method.
         * @param {string} id`
         */

    }, {
        key: "useAudioOutput",
        value: function useAudioOutput(id) {
            try {
                this.element.setSinkId(id);
            } catch (e) {
                this._logger.warning('Set audio output is impossible. Browser not support this option.');
            }
        }
    }, {
        key: "id",
        get: function get() {
            return this._id;
        }
    }]);

    return MediaRenderer;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "checkStreamActive", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "renderDefault", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "render", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "clear", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "setVolume", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.USERMEDIA)], MediaRenderer.prototype, "useAudioOutput", null);
exports.MediaRenderer = MediaRenderer;

/***/ }),
/* 35 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
var Endpoint_1 = __webpack_require__(36);
var MediaRenderer_1 = __webpack_require__(34);
var EndpointEvents_1 = __webpack_require__(27);
var CallEvents_1 = __webpack_require__(6);
var Constants_1 = __webpack_require__(11);
/**
 * @hidden
 */

var AbstractEndpointManager = function () {
    function AbstractEndpointManager() {
        _classCallCheck(this, AbstractEndpointManager);

        /**
         * Map with all endpoints for each call
         */
        this._endpointList = {};
        /**
         * Map with reference between an Endpoint ID's and a Tracks ID's for each call
         */
        this._trackListMap = {};
        this._trackKindMap = {};
        /**
         * Additional information about endpoint. F.o.e. display name
         */
        this._endpointInfo = {};
        /**
         * All active MediaRenders
         */
        this._mediaRendererList = {};
        this._logger = new Logger_1.Logger(Logger_1.LogCategory.ENDPOINT, 'EndpointManager', Logger_1.LogManager.get());
    }
    /**
     * Set volume level for all EndPoints in the call
     * @param {Call} call
     * @param {number} level
     */


    _createClass(AbstractEndpointManager, [{
        key: "setCallVolume",
        value: function setCallVolume(call, level) {
            var endPoints = this._endpointList[call.id()] || {};
            Object.values(endPoints).forEach(function (endPoint) {
                (endPoint.mediaRenderers || []).forEach(function (mediaRenderer) {
                    return mediaRenderer.setVolume(level);
                });
            });
        }
    }, {
        key: "_getEndpointByTrackId",
        value: function _getEndpointByTrackId(call, trackId) {
            var trackListMap = this._trackListMap[call.id()] || {};
            var endpointList = this._endpointList[call.id()] || {};
            if (trackListMap[trackId] && endpointList[trackListMap[trackId]]) return endpointList[trackListMap[trackId]];else return this.getDefaultEndPoint(call);
        }
    }, {
        key: "_getMediaTypeTrack",
        value: function _getMediaTypeTrack(call, track) {
            var trackKindMap = this._trackKindMap[call.id()] || {};
            return trackKindMap[track.id] || track.kind;
        }
        /**
         * Add Endpoint solve pending MediaStreams
         * @param {Call} call
         * @param {Endpoint} endpoint
         * @returns {Endpoint[]}
         */

    }, {
        key: "addEndPoint",
        value: function addEndPoint(call, endpoint) {
            var endpointInfo = this._endpointInfo[call.id()];
            this._endpointList[call.id()][endpoint.id] = endpoint;
            if (endpointInfo[endpoint.id]) {
                endpoint.displayName = endpointInfo[endpoint.id].displayName;
                endpoint.place = endpointInfo[endpoint.id].place;
                endpoint.sipUri = endpointInfo[endpoint.id].sipURI;
                endpoint.userName = endpointInfo[endpoint.id].username;
                call.dispatchEvent({
                    name: CallEvents_1.CallEvents.EndpointAdded,
                    call: call,
                    endpoint: endpoint
                });
            }
            return endpoint;
        }
    }, {
        key: "getDefaultEndPoint",

        /**
         * @hidden
         */
        value: function getDefaultEndPoint(call) {
            var endPointList = this._endpointList[call.id()] || {};
            if (endPointList[call.id()]) return endPointList[call.id()];
            var defaultEndpoint = new Endpoint_1.Endpoint(true);
            defaultEndpoint.id = call.id();
            this.addEndPoint(call, defaultEndpoint);
            return defaultEndpoint;
        }
        /**
         * Remove Endpoint from the call, and return his list of EndPoints.
         * Dispatch EndpointEvents.RemoteMediaRemoved for each MediaRenderer in endpoint
         * and then EndpointEvents.EndPointRemoved event
         * @param {Call} call
         * @param {Endpoint} endpoint
         * @returns {Endpoint[]}
         */

    }, {
        key: "deleteEndpoint",
        value: function deleteEndpoint(call, endpoint) {
            var endPointList = this._endpointList[call.id()] || {};
            if (endPointList[endpoint.id]) {
                endpoint.mediaRenderers.forEach(function (mediaRenderer) {
                    mediaRenderer.clear();
                });
                endpoint.dispatchEvent({ name: EndpointEvents_1.EndpointEvents.Removed, call: call, endpoint: endpoint });
                delete this._endpointList[call.id()][endpoint.id];
            } else this._logger.error("Trying remove non existing endpoint with id:" + endpoint.id + " on the call: " + call.id());
        }
    }, {
        key: "addStreamToEndpoint",
        value: function addStreamToEndpoint(call, endpoint, stream) {
            var _this = this;

            //if(endpoint.mediaRenderers&&endpoint.mediaRenderers.some(renderer=>renderer.stream.getTracks()[0].id===stream.getTracks()[0].id))
            //return;
            var mediaType = 'video';
            if (stream.getTracks().length === 1) mediaType = this.getMediaTypeTrack(call, stream.getTracks()[0]);
            var mediaRenderer = new MediaRenderer_1.MediaRenderer(stream, mediaType, false, false, undefined, call.settings.isConference);
            if (!this._mediaRendererList[call.id()]) this._mediaRendererList[call.id()] = [];
            this._mediaRendererList[call.id()].push(mediaRenderer);
            endpoint.mediaRenderers.push(mediaRenderer);
            endpoint.dispatchEvent({
                name: EndpointEvents_1.EndpointEvents.RemoteMediaAdded,
                call: call,
                endpoint: endpoint,
                mediaRenderer: mediaRenderer
            });
            call.dispatchEvent({
                name: CallEvents_1.CallEvents.MediaElementCreated,
                call: call,
                stream: mediaRenderer.stream,
                element: mediaRenderer.element,
                type: mediaRenderer.kind
            });
            mediaRenderer.onBeforeDestroy = function () {
                if (endpoint && endpoint.mediaRenderers) endpoint.mediaRenderers = endpoint.mediaRenderers.filter(function (mr) {
                    return mr.id !== mediaRenderer.id;
                });
                if (_this._mediaRendererList && _this._mediaRendererList[call.id()]) _this._mediaRendererList[call.id()] = _this._mediaRendererList[call.id()].filter(function (mr) {
                    return mr.id !== mediaRenderer.id;
                });
                endpoint.dispatchEvent({
                    name: EndpointEvents_1.EndpointEvents.RemoteMediaRemoved,
                    call: call,
                    endpoint: endpoint,
                    mediaRenderer: mediaRenderer
                });
                call.dispatchEvent({
                    name: CallEvents_1.CallEvents.MediaElementRemoved,
                    call: call,
                    stream: mediaRenderer.stream,
                    element: mediaRenderer.element,
                    type: mediaRenderer.kind
                });
            };
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'AbstractEndpointManager';
        }
    }, {
        key: "addStream",
        value: function addStream(call, stream) {
            var tracks = stream.getTracks();
            var endpoint = void 0;
            for (var trackId in tracks) {
                endpoint = endpoint || this.getEndpointByTrack(call, tracks[trackId]);
            }if (endpoint) this.addStreamToEndpoint(call, endpoint, stream);
        }
    }, {
        key: "addTrack",
        value: function addTrack(call, track) {
            var _this2 = this;

            if (call.settings.isConference) {
                if (!track.muted) this.addStream(call, new MediaStream([track]));else track.onunmute = function () {
                    return _this2.addStream(call, new MediaStream([track]));
                };
            } else this.addStream(call, new MediaStream([track]));
        }
    }, {
        key: "clear",
        value: function clear(call) {
            var _this3 = this;

            delete this._mediaRendererList[call.id()];
            delete this._endpointInfo[call.id()];
            delete this._trackListMap[call.id()];
            return new Promise(function (resolve, reject) {
                var endpointList = _this3._endpointList[call.id()] || {};
                var _iteratorNormalCompletion = true;
                var _didIteratorError = false;
                var _iteratorError = undefined;

                try {
                    for (var _iterator = Object.values(endpointList)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                        var endpoint = _step.value;

                        endpoint.mediaRenderers.forEach(function (mediaRenderer) {
                            mediaRenderer.clear();
                        });
                        endpoint.dispatchEvent({ name: EndpointEvents_1.EndpointEvents.Removed, call: call, endpoint: endpoint });
                    }
                } catch (err) {
                    _didIteratorError = true;
                    _iteratorError = err;
                } finally {
                    try {
                        if (!_iteratorNormalCompletion && _iterator.return) {
                            _iterator.return();
                        }
                    } finally {
                        if (_didIteratorError) {
                            throw _iteratorError;
                        }
                    }
                }

                delete _this3._endpointList[call.id()];
                resolve();
            });
        }
        /**
         * The endpoint additional info updated from the VoxEngine
         * @param call
         * @param kind
         * @param message
         */

    }, {
        key: "endpointInfoUpdated",
        value: function endpointInfoUpdated(call, kind, message) {
            var endpointInfo = this._endpointInfo[call.id()] || {};
            var endpointList = this._endpointList[call.id()] || {};
            endpointInfo[message.id] = message;
            var targetEndpoint = endpointList[message.id];
            if (targetEndpoint && (kind == Constants_1.Constants.VI_CONF_PARTICIPANT_INFO_ADDED || kind === Constants_1.Constants.VI_CONF_PARTICIPANT_INFO_UPDATED)) {
                var needEvent = !targetEndpoint.sipUri;
                targetEndpoint.displayName = message.displayName;
                targetEndpoint.place = message.place;
                targetEndpoint.sipUri = message.sipURI;
                targetEndpoint.userName = message.username;
                if (needEvent) {
                    call.dispatchEvent({
                        name: CallEvents_1.CallEvents.EndpointAdded,
                        call: call,
                        endpoint: targetEndpoint
                    });
                } else {
                    targetEndpoint.dispatchEvent({
                        name: EndpointEvents_1.EndpointEvents.InfoUpdated,
                        call: call,
                        endpoint: targetEndpoint
                    });
                }
            }
            this._endpointInfo[call.id()] = endpointInfo;
        }
    }, {
        key: "setEndpointDescription",
        value: function setEndpointDescription(call, description) {
            var _this4 = this;

            if (!description) return;
            if (!description.endpoints) return;
            var endpointList = this._endpointList[call.id()];
            var rmEndpoints = [];
            for (var existEndpointId in endpointList) {
                if (endpointList.hasOwnProperty(existEndpointId)) {
                    if (!description.endpoints[existEndpointId] && existEndpointId !== call.id()) rmEndpoints.push(endpointList[existEndpointId]);
                }
            }
            rmEndpoints.forEach(function (endpoint) {
                return _this4.deleteEndpoint(call, endpoint);
            });
            var trackListMap = {};
            var trackKindMap = {};
            for (var endpointId in description.endpoints) {
                if (description.endpoints.hasOwnProperty(endpointId)) {
                    if (endpointId === '') this.getDefaultEndPoint(call);else if (!endpointList[endpointId]) {
                        var endpoint = new Endpoint_1.Endpoint(false);
                        endpoint.id = endpointId;
                        endpoint.place = description.endpoints[endpointId].place;
                        this.addEndPoint(call, endpoint);
                    }
                    if (description.endpoints[endpointId].tracks) {
                        for (var trackId in description.endpoints[endpointId].tracks) {
                            if (description.endpoints[endpointId].tracks.hasOwnProperty(trackId)) {
                                trackListMap[trackId] = endpointId;
                                trackKindMap[trackId] = description.endpoints[endpointId].tracks[trackId];
                            }
                        }
                    }
                }
            }
            this._trackListMap[call.id()] = trackListMap;
            this._trackKindMap[call.id()] = trackKindMap;
        }
    }, {
        key: "useAudioOutput",
        value: function useAudioOutput(call, id) {
            var endPoints = this._endpointList[call.id()];
            if (endPoints) Object.values(endPoints).forEach(function (mediaRenderer) {
                return mediaRenderer.useAudioOutput(id);
            });
        }
    }, {
        key: "getEndpoints",
        value: function getEndpoints(call) {
            return Object.values(this._endpointList[call.id()] || {});
        }
    }, {
        key: "registerCall",
        value: function registerCall(call) {
            this._endpointList[call.id()] = {};
            this._trackListMap[call.id()] = {};
            this._trackKindMap[call.id()] = {};
            this._endpointInfo[call.id()] = {};
            this._mediaRendererList[call.id()] = [];
        }
    }]);

    return AbstractEndpointManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "setCallVolume", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "_getEndpointByTrackId", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "_getMediaTypeTrack", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "addEndPoint", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "getDefaultEndPoint", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "deleteEndpoint", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "addStreamToEndpoint", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], AbstractEndpointManager.prototype, "useAudioOutput", null);
exports.AbstractEndpointManager = AbstractEndpointManager;

/***/ }),
/* 36 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

Object.defineProperty(exports, "__esModule", { value: true });
var EventTarget_1 = __webpack_require__(14);
var EndpointEvents_1 = __webpack_require__(27);
/**
 * Interface that represents any remote media unit in a call. Current endpoints can be retrieved via the [Call.getEndpoints] method.
 *
 * Endpoint can be :
 * <ol>
 * <li><a href="//voximplant.com/docs/references/appengine/Module_ASR.html">ASR</a></li>
 * <li><a href="//voximplant.com/docs/references/appengine/Module_Recorder.html">Recorder</a></li>
 * <li><a href="//voximplant.com/docs/references/appengine/Module_Player.html">Player</a></li>
 * <li> or another <a href="//voximplant.com/docs/references/appengine/Call.html">call</a> (e.g. which is joined to the conference)</li>
 * </ol>
 */

var Endpoint = function (_EventTarget_1$EventT) {
  _inherits(Endpoint, _EventTarget_1$EventT);

  /**
   * @hidden
   */
  function Endpoint() {
    var isDefault = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

    _classCallCheck(this, Endpoint);

    var _this = _possibleConstructorReturn(this, (Endpoint.__proto__ || Object.getPrototypeOf(Endpoint)).call(this));

    _this.isDefault = isDefault;
    /**
     * Get <a href="https://tools.ietf.org/html/rfc3261##section-19.1.1" target="_blank">SIP URI</a> of the endpoint
     */
    _this.sipUri = '';
    /**
     * Get user display name of the endpoint.
     */
    _this.displayName = '';
    /**
     * Get user name of the endpoint.
     */
    _this.userName = '';
    _this.mediaRenderers = [];
    _this.addDefaultEventListener(EndpointEvents_1.EndpointEvents.RemoteMediaAdded, function (e) {
      e.mediaRenderer.renderDefault();
    });
    return _this;
  }
  /**
   * Set audio output device for current Endpoint. Now supported by Google Chrome only
   * @param {string} id
   */


  _createClass(Endpoint, [{
    key: "useAudioOutput",
    value: function useAudioOutput(id) {
      if (this.mediaRenderers) this.mediaRenderers.forEach(function (mediaRenderer) {
        return mediaRenderer.useAudioOutput(id);
      });
    }
    /**
     * @hidden
     */

  }, {
    key: "updateInfo",
    value: function updateInfo(data) {
      this.place = data.place;
      this.sipUri = data.sipURI;
      this.displayName = data.displayName;
      this.userName = data.username;
    }
    /**
     * @hidden
     * @return {string}
     * @private
     */

  }, {
    key: "_traceName",
    value: function _traceName() {
      return 'Endpoint';
    }
    /**
     * Register a handler for the specified event. The method is a shorter equivalent for *addEventListener*. One event can have more than one handler; handlers are executed in order of registration.
     * Use the [Endpoint.off] method to delete a handler.
     */

  }, {
    key: "on",
    value: function on(event, handler) {
      _get(Endpoint.prototype.__proto__ || Object.getPrototypeOf(Endpoint.prototype), "on", this).call(this, event, handler);
    }
    /**
     * Remove a handler for the specified event. The method is a shorter equivalent for *removeEventListener*. If a number of events has the same function as a handler, the method can be called multiple times with the same handler argument.
     */

  }, {
    key: "off",
    value: function off(event, handler) {
      _get(Endpoint.prototype.__proto__ || Object.getPrototypeOf(Endpoint.prototype), "off", this).call(this, event, handler);
    }
  }]);

  return Endpoint;
}(EventTarget_1.EventTarget);

exports.Endpoint = Endpoint;

/***/ }),
/* 37 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var CallEvents_1 = __webpack_require__(6);
var Logger_1 = __webpack_require__(0);
/**
 * @hidden
 */

var ReInviteQ = function () {
    function ReInviteQ(call, _pcStatus) {
        var _this = this;

        _classCallCheck(this, ReInviteQ);

        this._pcStatus = _pcStatus;
        this._q = [];
        call.on(CallEvents_1.CallEvents.Updated, function (e) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.REINVITEQ, 'CallEvent', Logger_1.LogLevel.TRACE, "Updated with result " + e.result);
            if (ReInviteQ._currentReinvite) {
                var reinvite = ReInviteQ._currentReinvite;
                if (e.result) reinvite.resolve(e);else reinvite.reject(e);
                ReInviteQ._currentReinvite = undefined;
            }
            _this.runNext();
        });
        call.on(CallEvents_1.CallEvents.PendingUpdate, function (e) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.REINVITEQ, 'CallEvent', Logger_1.LogLevel.TRACE, "IncomingUpdate. Local RI==" + _typeof(ReInviteQ._currentReinvite));
            if (ReInviteQ._currentReinvite) {
                ReInviteQ._currentReinvite.reject();
                ReInviteQ._currentReinvite = undefined;
            }
        });
        call.on(CallEvents_1.CallEvents.UpdateFailed, function (e) {
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.REINVITEQ, 'CallEvent', Logger_1.LogLevel.TRACE, "UpdateFailed");
            if (ReInviteQ._currentReinvite) {
                ReInviteQ._currentReinvite.reject();
                ReInviteQ._currentReinvite = undefined;
            }
        });
    }

    _createClass(ReInviteQ, [{
        key: "runNext",
        value: function runNext() {
            if (typeof ReInviteQ._currentReinvite === "undefined" && this._q.length > 0 && this._pcStatus()) {
                ReInviteQ._currentReinvite = this._q.splice(0, 1)[0];
                ReInviteQ._currentReinvite.fx();
            }
        }
    }, {
        key: "add",
        value: function add(member) {
            this._q.push(member);
            this.runNext();
        }
    }, {
        key: "clear",
        value: function clear() {
            this._q.forEach(function (member) {
                member.reject();
            });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'ReInviteQ';
        }
    }]);

    return ReInviteQ;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.REINVITEQ)], ReInviteQ.prototype, "runNext", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.REINVITEQ)], ReInviteQ.prototype, "add", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.REINVITEQ)], ReInviteQ.prototype, "clear", null);
exports.ReInviteQ = ReInviteQ;

/***/ }),
/* 38 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
 /* eslint-env node */


// SDP helpers.
var SDPUtils = {};

// Generate an alphanumeric identifier for cname or mids.
// TODO: use UUIDs instead? https://gist.github.com/jed/982883
SDPUtils.generateIdentifier = function() {
  return Math.random().toString(36).substr(2, 10);
};

// The RTCP CNAME used by all peerconnections from the same JS.
SDPUtils.localCName = SDPUtils.generateIdentifier();

// Splits SDP into lines, dealing with both CRLF and LF.
SDPUtils.splitLines = function(blob) {
  return blob.trim().split('\n').map(function(line) {
    return line.trim();
  });
};
// Splits SDP into sessionpart and mediasections. Ensures CRLF.
SDPUtils.splitSections = function(blob) {
  var parts = blob.split('\nm=');
  return parts.map(function(part, index) {
    return (index > 0 ? 'm=' + part : part).trim() + '\r\n';
  });
};

// returns the session description.
SDPUtils.getDescription = function(blob) {
  var sections = SDPUtils.splitSections(blob);
  return sections && sections[0];
};

// returns the individual media sections.
SDPUtils.getMediaSections = function(blob) {
  var sections = SDPUtils.splitSections(blob);
  sections.shift();
  return sections;
};

// Returns lines that start with a certain prefix.
SDPUtils.matchPrefix = function(blob, prefix) {
  return SDPUtils.splitLines(blob).filter(function(line) {
    return line.indexOf(prefix) === 0;
  });
};

// Parses an ICE candidate line. Sample input:
// candidate:702786350 2 udp 41819902 8.8.8.8 60769 typ relay raddr 8.8.8.8
// rport 55996"
SDPUtils.parseCandidate = function(line) {
  var parts;
  // Parse both variants.
  if (line.indexOf('a=candidate:') === 0) {
    parts = line.substring(12).split(' ');
  } else {
    parts = line.substring(10).split(' ');
  }

  var candidate = {
    foundation: parts[0],
    component: parseInt(parts[1], 10),
    protocol: parts[2].toLowerCase(),
    priority: parseInt(parts[3], 10),
    ip: parts[4],
    port: parseInt(parts[5], 10),
    // skip parts[6] == 'typ'
    type: parts[7]
  };

  for (var i = 8; i < parts.length; i += 2) {
    switch (parts[i]) {
      case 'raddr':
        candidate.relatedAddress = parts[i + 1];
        break;
      case 'rport':
        candidate.relatedPort = parseInt(parts[i + 1], 10);
        break;
      case 'tcptype':
        candidate.tcpType = parts[i + 1];
        break;
      case 'ufrag':
        candidate.ufrag = parts[i + 1]; // for backward compability.
        candidate.usernameFragment = parts[i + 1];
        break;
      default: // extension handling, in particular ufrag
        candidate[parts[i]] = parts[i + 1];
        break;
    }
  }
  return candidate;
};

// Translates a candidate object into SDP candidate attribute.
SDPUtils.writeCandidate = function(candidate) {
  var sdp = [];
  sdp.push(candidate.foundation);
  sdp.push(candidate.component);
  sdp.push(candidate.protocol.toUpperCase());
  sdp.push(candidate.priority);
  sdp.push(candidate.ip);
  sdp.push(candidate.port);

  var type = candidate.type;
  sdp.push('typ');
  sdp.push(type);
  if (type !== 'host' && candidate.relatedAddress &&
      candidate.relatedPort) {
    sdp.push('raddr');
    sdp.push(candidate.relatedAddress);
    sdp.push('rport');
    sdp.push(candidate.relatedPort);
  }
  if (candidate.tcpType && candidate.protocol.toLowerCase() === 'tcp') {
    sdp.push('tcptype');
    sdp.push(candidate.tcpType);
  }
  if (candidate.usernameFragment || candidate.ufrag) {
    sdp.push('ufrag');
    sdp.push(candidate.usernameFragment || candidate.ufrag);
  }
  return 'candidate:' + sdp.join(' ');
};

// Parses an ice-options line, returns an array of option tags.
// a=ice-options:foo bar
SDPUtils.parseIceOptions = function(line) {
  return line.substr(14).split(' ');
}

// Parses an rtpmap line, returns RTCRtpCoddecParameters. Sample input:
// a=rtpmap:111 opus/48000/2
SDPUtils.parseRtpMap = function(line) {
  var parts = line.substr(9).split(' ');
  var parsed = {
    payloadType: parseInt(parts.shift(), 10) // was: id
  };

  parts = parts[0].split('/');

  parsed.name = parts[0];
  parsed.clockRate = parseInt(parts[1], 10); // was: clockrate
  parsed.channels = parts.length === 3 ? parseInt(parts[2], 10) : 1;
  // legacy alias, got renamed back to channels in ORTC.
  parsed.numChannels = parsed.channels;
  return parsed;
};

// Generate an a=rtpmap line from RTCRtpCodecCapability or
// RTCRtpCodecParameters.
SDPUtils.writeRtpMap = function(codec) {
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  var channels = codec.channels || codec.numChannels || 1;
  return 'a=rtpmap:' + pt + ' ' + codec.name + '/' + codec.clockRate +
      (channels !== 1 ? '/' + channels : '') + '\r\n';
};

// Parses an a=extmap line (headerextension from RFC 5285). Sample input:
// a=extmap:2 urn:ietf:params:rtp-hdrext:toffset
// a=extmap:2/sendonly urn:ietf:params:rtp-hdrext:toffset
SDPUtils.parseExtmap = function(line) {
  var parts = line.substr(9).split(' ');
  return {
    id: parseInt(parts[0], 10),
    direction: parts[0].indexOf('/') > 0 ? parts[0].split('/')[1] : 'sendrecv',
    uri: parts[1]
  };
};

// Generates a=extmap line from RTCRtpHeaderExtensionParameters or
// RTCRtpHeaderExtension.
SDPUtils.writeExtmap = function(headerExtension) {
  return 'a=extmap:' + (headerExtension.id || headerExtension.preferredId) +
      (headerExtension.direction && headerExtension.direction !== 'sendrecv'
          ? '/' + headerExtension.direction
          : '') +
      ' ' + headerExtension.uri + '\r\n';
};

// Parses an ftmp line, returns dictionary. Sample input:
// a=fmtp:96 vbr=on;cng=on
// Also deals with vbr=on; cng=on
SDPUtils.parseFmtp = function(line) {
  var parsed = {};
  var kv;
  var parts = line.substr(line.indexOf(' ') + 1).split(';');
  for (var j = 0; j < parts.length; j++) {
    kv = parts[j].trim().split('=');
    parsed[kv[0].trim()] = kv[1];
  }
  return parsed;
};

// Generates an a=ftmp line from RTCRtpCodecCapability or RTCRtpCodecParameters.
SDPUtils.writeFmtp = function(codec) {
  var line = '';
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  if (codec.parameters && Object.keys(codec.parameters).length) {
    var params = [];
    Object.keys(codec.parameters).forEach(function(param) {
      if (codec.parameters[param]) {
        params.push(param + '=' + codec.parameters[param]);
      } else {
        params.push(param);
      }
    });
    line += 'a=fmtp:' + pt + ' ' + params.join(';') + '\r\n';
  }
  return line;
};

// Parses an rtcp-fb line, returns RTCPRtcpFeedback object. Sample input:
// a=rtcp-fb:98 nack rpsi
SDPUtils.parseRtcpFb = function(line) {
  var parts = line.substr(line.indexOf(' ') + 1).split(' ');
  return {
    type: parts.shift(),
    parameter: parts.join(' ')
  };
};
// Generate a=rtcp-fb lines from RTCRtpCodecCapability or RTCRtpCodecParameters.
SDPUtils.writeRtcpFb = function(codec) {
  var lines = '';
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  if (codec.rtcpFeedback && codec.rtcpFeedback.length) {
    // FIXME: special handling for trr-int?
    codec.rtcpFeedback.forEach(function(fb) {
      lines += 'a=rtcp-fb:' + pt + ' ' + fb.type +
      (fb.parameter && fb.parameter.length ? ' ' + fb.parameter : '') +
          '\r\n';
    });
  }
  return lines;
};

// Parses an RFC 5576 ssrc media attribute. Sample input:
// a=ssrc:3735928559 cname:something
SDPUtils.parseSsrcMedia = function(line) {
  var sp = line.indexOf(' ');
  var parts = {
    ssrc: parseInt(line.substr(7, sp - 7), 10)
  };
  var colon = line.indexOf(':', sp);
  if (colon > -1) {
    parts.attribute = line.substr(sp + 1, colon - sp - 1);
    parts.value = line.substr(colon + 1);
  } else {
    parts.attribute = line.substr(sp + 1);
  }
  return parts;
};

// Extracts the MID (RFC 5888) from a media section.
// returns the MID or undefined if no mid line was found.
SDPUtils.getMid = function(mediaSection) {
  var mid = SDPUtils.matchPrefix(mediaSection, 'a=mid:')[0];
  if (mid) {
    return mid.substr(6);
  }
}

SDPUtils.parseFingerprint = function(line) {
  var parts = line.substr(14).split(' ');
  return {
    algorithm: parts[0].toLowerCase(), // algorithm is case-sensitive in Edge.
    value: parts[1]
  };
};

// Extracts DTLS parameters from SDP media section or sessionpart.
// FIXME: for consistency with other functions this should only
//   get the fingerprint line as input. See also getIceParameters.
SDPUtils.getDtlsParameters = function(mediaSection, sessionpart) {
  var lines = SDPUtils.matchPrefix(mediaSection + sessionpart,
      'a=fingerprint:');
  // Note: a=setup line is ignored since we use the 'auto' role.
  // Note2: 'algorithm' is not case sensitive except in Edge.
  return {
    role: 'auto',
    fingerprints: lines.map(SDPUtils.parseFingerprint)
  };
};

// Serializes DTLS parameters to SDP.
SDPUtils.writeDtlsParameters = function(params, setupType) {
  var sdp = 'a=setup:' + setupType + '\r\n';
  params.fingerprints.forEach(function(fp) {
    sdp += 'a=fingerprint:' + fp.algorithm + ' ' + fp.value + '\r\n';
  });
  return sdp;
};
// Parses ICE information from SDP media section or sessionpart.
// FIXME: for consistency with other functions this should only
//   get the ice-ufrag and ice-pwd lines as input.
SDPUtils.getIceParameters = function(mediaSection, sessionpart) {
  var lines = SDPUtils.splitLines(mediaSection);
  // Search in session part, too.
  lines = lines.concat(SDPUtils.splitLines(sessionpart));
  var iceParameters = {
    usernameFragment: lines.filter(function(line) {
      return line.indexOf('a=ice-ufrag:') === 0;
    })[0].substr(12),
    password: lines.filter(function(line) {
      return line.indexOf('a=ice-pwd:') === 0;
    })[0].substr(10)
  };
  return iceParameters;
};

// Serializes ICE parameters to SDP.
SDPUtils.writeIceParameters = function(params) {
  return 'a=ice-ufrag:' + params.usernameFragment + '\r\n' +
      'a=ice-pwd:' + params.password + '\r\n';
};

// Parses the SDP media section and returns RTCRtpParameters.
SDPUtils.parseRtpParameters = function(mediaSection) {
  var description = {
    codecs: [],
    headerExtensions: [],
    fecMechanisms: [],
    rtcp: []
  };
  var lines = SDPUtils.splitLines(mediaSection);
  var mline = lines[0].split(' ');
  for (var i = 3; i < mline.length; i++) { // find all codecs from mline[3..]
    var pt = mline[i];
    var rtpmapline = SDPUtils.matchPrefix(
        mediaSection, 'a=rtpmap:' + pt + ' ')[0];
    if (rtpmapline) {
      var codec = SDPUtils.parseRtpMap(rtpmapline);
      var fmtps = SDPUtils.matchPrefix(
          mediaSection, 'a=fmtp:' + pt + ' ');
      // Only the first a=fmtp:<pt> is considered.
      codec.parameters = fmtps.length ? SDPUtils.parseFmtp(fmtps[0]) : {};
      codec.rtcpFeedback = SDPUtils.matchPrefix(
          mediaSection, 'a=rtcp-fb:' + pt + ' ')
        .map(SDPUtils.parseRtcpFb);
      description.codecs.push(codec);
      // parse FEC mechanisms from rtpmap lines.
      switch (codec.name.toUpperCase()) {
        case 'RED':
        case 'ULPFEC':
          description.fecMechanisms.push(codec.name.toUpperCase());
          break;
        default: // only RED and ULPFEC are recognized as FEC mechanisms.
          break;
      }
    }
  }
  SDPUtils.matchPrefix(mediaSection, 'a=extmap:').forEach(function(line) {
    description.headerExtensions.push(SDPUtils.parseExtmap(line));
  });
  // FIXME: parse rtcp.
  return description;
};

// Generates parts of the SDP media section describing the capabilities /
// parameters.
SDPUtils.writeRtpDescription = function(kind, caps) {
  var sdp = '';

  // Build the mline.
  sdp += 'm=' + kind + ' ';
  sdp += caps.codecs.length > 0 ? '9' : '0'; // reject if no codecs.
  sdp += ' UDP/TLS/RTP/SAVPF ';
  sdp += caps.codecs.map(function(codec) {
    if (codec.preferredPayloadType !== undefined) {
      return codec.preferredPayloadType;
    }
    return codec.payloadType;
  }).join(' ') + '\r\n';

  sdp += 'c=IN IP4 0.0.0.0\r\n';
  sdp += 'a=rtcp:9 IN IP4 0.0.0.0\r\n';

  // Add a=rtpmap lines for each codec. Also fmtp and rtcp-fb.
  caps.codecs.forEach(function(codec) {
    sdp += SDPUtils.writeRtpMap(codec);
    sdp += SDPUtils.writeFmtp(codec);
    sdp += SDPUtils.writeRtcpFb(codec);
  });
  var maxptime = 0;
  caps.codecs.forEach(function(codec) {
    if (codec.maxptime > maxptime) {
      maxptime = codec.maxptime;
    }
  });
  if (maxptime > 0) {
    sdp += 'a=maxptime:' + maxptime + '\r\n';
  }
  sdp += 'a=rtcp-mux\r\n';

  if (caps.headerExtensions) {
    caps.headerExtensions.forEach(function(extension) {
      sdp += SDPUtils.writeExtmap(extension);
    });
  }
  // FIXME: write fecMechanisms.
  return sdp;
};

// Parses the SDP media section and returns an array of
// RTCRtpEncodingParameters.
SDPUtils.parseRtpEncodingParameters = function(mediaSection) {
  var encodingParameters = [];
  var description = SDPUtils.parseRtpParameters(mediaSection);
  var hasRed = description.fecMechanisms.indexOf('RED') !== -1;
  var hasUlpfec = description.fecMechanisms.indexOf('ULPFEC') !== -1;

  // filter a=ssrc:... cname:, ignore PlanB-msid
  var ssrcs = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
  .map(function(line) {
    return SDPUtils.parseSsrcMedia(line);
  })
  .filter(function(parts) {
    return parts.attribute === 'cname';
  });
  var primarySsrc = ssrcs.length > 0 && ssrcs[0].ssrc;
  var secondarySsrc;

  var flows = SDPUtils.matchPrefix(mediaSection, 'a=ssrc-group:FID')
  .map(function(line) {
    var parts = line.substr(17).split(' ');
    return parts.map(function(part) {
      return parseInt(part, 10);
    });
  });
  if (flows.length > 0 && flows[0].length > 1 && flows[0][0] === primarySsrc) {
    secondarySsrc = flows[0][1];
  }

  description.codecs.forEach(function(codec) {
    if (codec.name.toUpperCase() === 'RTX' && codec.parameters.apt) {
      var encParam = {
        ssrc: primarySsrc,
        codecPayloadType: parseInt(codec.parameters.apt, 10),
      };
      if (primarySsrc && secondarySsrc) {
        encParam.rtx = {ssrc: secondarySsrc};
      }
      encodingParameters.push(encParam);
      if (hasRed) {
        encParam = JSON.parse(JSON.stringify(encParam));
        encParam.fec = {
          ssrc: secondarySsrc,
          mechanism: hasUlpfec ? 'red+ulpfec' : 'red'
        };
        encodingParameters.push(encParam);
      }
    }
  });
  if (encodingParameters.length === 0 && primarySsrc) {
    encodingParameters.push({
      ssrc: primarySsrc
    });
  }

  // we support both b=AS and b=TIAS but interpret AS as TIAS.
  var bandwidth = SDPUtils.matchPrefix(mediaSection, 'b=');
  if (bandwidth.length) {
    if (bandwidth[0].indexOf('b=TIAS:') === 0) {
      bandwidth = parseInt(bandwidth[0].substr(7), 10);
    } else if (bandwidth[0].indexOf('b=AS:') === 0) {
      // use formula from JSEP to convert b=AS to TIAS value.
      bandwidth = parseInt(bandwidth[0].substr(5), 10) * 1000 * 0.95
          - (50 * 40 * 8);
    } else {
      bandwidth = undefined;
    }
    encodingParameters.forEach(function(params) {
      params.maxBitrate = bandwidth;
    });
  }
  return encodingParameters;
};

// parses http://draft.ortc.org/#rtcrtcpparameters*
SDPUtils.parseRtcpParameters = function(mediaSection) {
  var rtcpParameters = {};

  var cname;
  // Gets the first SSRC. Note that with RTX there might be multiple
  // SSRCs.
  var remoteSsrc = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
      .map(function(line) {
        return SDPUtils.parseSsrcMedia(line);
      })
      .filter(function(obj) {
        return obj.attribute === 'cname';
      })[0];
  if (remoteSsrc) {
    rtcpParameters.cname = remoteSsrc.value;
    rtcpParameters.ssrc = remoteSsrc.ssrc;
  }

  // Edge uses the compound attribute instead of reducedSize
  // compound is !reducedSize
  var rsize = SDPUtils.matchPrefix(mediaSection, 'a=rtcp-rsize');
  rtcpParameters.reducedSize = rsize.length > 0;
  rtcpParameters.compound = rsize.length === 0;

  // parses the rtcp-mux attrіbute.
  // Note that Edge does not support unmuxed RTCP.
  var mux = SDPUtils.matchPrefix(mediaSection, 'a=rtcp-mux');
  rtcpParameters.mux = mux.length > 0;

  return rtcpParameters;
};

// parses either a=msid: or a=ssrc:... msid lines and returns
// the id of the MediaStream and MediaStreamTrack.
SDPUtils.parseMsid = function(mediaSection) {
  var parts;
  var spec = SDPUtils.matchPrefix(mediaSection, 'a=msid:');
  if (spec.length === 1) {
    parts = spec[0].substr(7).split(' ');
    return {stream: parts[0], track: parts[1]};
  }
  var planB = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
  .map(function(line) {
    return SDPUtils.parseSsrcMedia(line);
  })
  .filter(function(parts) {
    return parts.attribute === 'msid';
  });
  if (planB.length > 0) {
    parts = planB[0].value.split(' ');
    return {stream: parts[0], track: parts[1]};
  }
};

// Generate a session ID for SDP.
// https://tools.ietf.org/html/draft-ietf-rtcweb-jsep-20#section-5.2.1
// recommends using a cryptographically random +ve 64-bit value
// but right now this should be acceptable and within the right range
SDPUtils.generateSessionId = function() {
  return Math.random().toString().substr(2, 21);
};

// Write boilder plate for start of SDP
// sessId argument is optional - if not supplied it will
// be generated randomly
// sessVersion is optional and defaults to 2
SDPUtils.writeSessionBoilerplate = function(sessId, sessVer) {
  var sessionId;
  var version = sessVer !== undefined ? sessVer : 2;
  if (sessId) {
    sessionId = sessId;
  } else {
    sessionId = SDPUtils.generateSessionId();
  }
  // FIXME: sess-id should be an NTP timestamp.
  return 'v=0\r\n' +
      'o=thisisadapterortc ' + sessionId + ' ' + version + ' IN IP4 127.0.0.1\r\n' +
      's=-\r\n' +
      't=0 0\r\n';
};

SDPUtils.writeMediaSection = function(transceiver, caps, type, stream) {
  var sdp = SDPUtils.writeRtpDescription(transceiver.kind, caps);

  // Map ICE parameters (ufrag, pwd) to SDP.
  sdp += SDPUtils.writeIceParameters(
      transceiver.iceGatherer.getLocalParameters());

  // Map DTLS parameters to SDP.
  sdp += SDPUtils.writeDtlsParameters(
      transceiver.dtlsTransport.getLocalParameters(),
      type === 'offer' ? 'actpass' : 'active');

  sdp += 'a=mid:' + transceiver.mid + '\r\n';

  if (transceiver.direction) {
    sdp += 'a=' + transceiver.direction + '\r\n';
  } else if (transceiver.rtpSender && transceiver.rtpReceiver) {
    sdp += 'a=sendrecv\r\n';
  } else if (transceiver.rtpSender) {
    sdp += 'a=sendonly\r\n';
  } else if (transceiver.rtpReceiver) {
    sdp += 'a=recvonly\r\n';
  } else {
    sdp += 'a=inactive\r\n';
  }

  if (transceiver.rtpSender) {
    // spec.
    var msid = 'msid:' + stream.id + ' ' +
        transceiver.rtpSender.track.id + '\r\n';
    sdp += 'a=' + msid;

    // for Chrome.
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
        ' ' + msid;
    if (transceiver.sendEncodingParameters[0].rtx) {
      sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
          ' ' + msid;
      sdp += 'a=ssrc-group:FID ' +
          transceiver.sendEncodingParameters[0].ssrc + ' ' +
          transceiver.sendEncodingParameters[0].rtx.ssrc +
          '\r\n';
    }
  }
  // FIXME: this should be written by writeRtpDescription.
  sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
      ' cname:' + SDPUtils.localCName + '\r\n';
  if (transceiver.rtpSender && transceiver.sendEncodingParameters[0].rtx) {
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
        ' cname:' + SDPUtils.localCName + '\r\n';
  }
  return sdp;
};

// Gets the direction from the mediaSection or the sessionpart.
SDPUtils.getDirection = function(mediaSection, sessionpart) {
  // Look for sendrecv, sendonly, recvonly, inactive, default to sendrecv.
  var lines = SDPUtils.splitLines(mediaSection);
  for (var i = 0; i < lines.length; i++) {
    switch (lines[i]) {
      case 'a=sendrecv':
      case 'a=sendonly':
      case 'a=recvonly':
      case 'a=inactive':
        return lines[i].substr(2);
      default:
        // FIXME: What should happen here?
    }
  }
  if (sessionpart) {
    return SDPUtils.getDirection(sessionpart);
  }
  return 'sendrecv';
};

SDPUtils.getKind = function(mediaSection) {
  var lines = SDPUtils.splitLines(mediaSection);
  var mline = lines[0].split(' ');
  return mline[0].substr(2);
};

SDPUtils.isRejected = function(mediaSection) {
  return mediaSection.split(' ', 2)[1] === '0';
};

SDPUtils.parseMLine = function(mediaSection) {
  var lines = SDPUtils.splitLines(mediaSection);
  var parts = lines[0].substr(2).split(' ');
  return {
    kind: parts[0],
    port: parseInt(parts[1], 10),
    protocol: parts[2],
    fmt: parts.slice(3).join(' ')
  };
};

SDPUtils.parseOLine = function(mediaSection) {
  var line = SDPUtils.matchPrefix(mediaSection, 'o=')[0];
  var parts = line.substr(2).split(' ');
  return {
    username: parts[0],
    sessionId: parts[1],
    sessionVersion: parseInt(parts[2], 10),
    netType: parts[3],
    addressType: parts[4],
    address: parts[5],
  };
}

// Expose public methods.
if (true) {
  module.exports = SDPUtils;
}


/***/ }),
/* 39 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var MsgSignaling_1 = __webpack_require__(15);
var Conversation_1 = __webpack_require__(29);
var MsgEnums_1 = __webpack_require__(16);
var Messenger_1 = __webpack_require__(30);
var Message_1 = __webpack_require__(22);
var Logger_1 = __webpack_require__(0);
var index_1 = __webpack_require__(17);
/**
 * @hidden
 */

var ConversationManager = function () {
    function ConversationManager() {
        var _this = this;

        _classCallCheck(this, ConversationManager);

        if (ConversationManager.instance) throw new Error('Error - use ConversationManager.get()');
        this.signalling = MsgSignaling_1.MsgSignaling.get();
        this.awaitingConversations = {};
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onEditConversation, function (payload) {
            _this.resolveEvent(payload, index_1.default.MessengerEvents.EditConversation);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onGetConversation, function (payload) {
            _this.resolveEvent(payload, index_1.default.MessengerEvents.GetConversation);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onRemoveConversation, function (payload) {
            _this.resolveEvent(payload, index_1.default.MessengerEvents.RemoveConversation);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onCreateConversation, function (payload) {
            _this.resolveEvent(payload, index_1.default.MessengerEvents.CreateConversation);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onSendMessage, function (payload) {
            _this.resolveMessageEvent(payload, index_1.default.MessengerEvents.SendMessage);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onEditMessage, function (payload) {
            _this.resolveMessageEvent(payload, index_1.default.MessengerEvents.EditMessage);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onRemoveMessage, function (payload) {
            _this.resolveMessageEvent(payload, index_1.default.MessengerEvents.RemoveMessage);
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.isRead, function (payload) {
            var realPayload = payload.object;
            Messenger_1.Messenger.getInstance()._dispatchEvent(index_1.default.MessengerEvents.Read, {
                conversation: realPayload.conversation,
                timestamp: new Date(realPayload.timestamp * 1000),
                userId: payload.user_id,
                seq: realPayload.seq,
                onIncomingEvent: payload.on_incoming_event,
                name: index_1.default.MessengerEvents[index_1.default.MessengerEvents.Read]
            });
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.isDelivered, function (payload) {
            var realPayload = payload.object;
            Messenger_1.Messenger.getInstance()._dispatchEvent(index_1.default.MessengerEvents.Delivered, {
                conversation: realPayload.conversation,
                timestamp: new Date(realPayload.timestamp * 1000),
                userId: payload.user_id,
                seq: realPayload.seq,
                onIncomingEvent: payload.on_incoming_event,
                name: index_1.default.MessengerEvents[index_1.default.MessengerEvents.Delivered]
            });
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onTyping, function (payload) {
            Messenger_1.Messenger.getInstance()._dispatchEvent(index_1.default.MessengerEvents.Typing, {
                name: index_1.default.MessengerEvents[index_1.default.MessengerEvents.Typing],
                conversation: payload.object.conversation,
                userId: payload.user_id,
                onIncomingEvent: payload.on_incoming_event
            });
        });
        this.signalling.addEventListener(MsgEnums_1.MsgEvent.onRetransmitEvents, function (payload) {
            Messenger_1.Messenger.getInstance()._dispatchEvent(index_1.default.MessengerEvents.RetransmitEvents, {
                events: payload.object.map(function (item) {
                    if (item.event) {
                        if (item.event.indexOf('Message') == -1) return {
                            name: item.event,
                            conversation: Conversation_1.Conversation._createFromBus(item.payload.object, item.payload.seq),
                            userId: item.payload.user_id,
                            seq: item.payload.seq,
                            onIncomingEvent: item.payload.on_incoming_event
                        };else return {
                            name: item.event,
                            message: Message_1.Message._createFromBus(item.payload.object, item.payload.seq),
                            userId: item.payload.user_id,
                            seq: item.payload.seq,
                            onIncomingEvent: item.payload.on_incoming_event
                        };
                    }
                }),
                userId: payload.user_id,
                seq: payload.seq,
                from: payload.from,
                to: payload.to,
                onIncomingEvent: payload.on_incoming_event
            });
        });
        this._converasationList = [];
    }

    _createClass(ConversationManager, [{
        key: "createConversation",

        /**
         * Create new conversation
         * @param participants
         * @param title
         * @param distinct
         * @param enablePublicJoin
         * @param customData
         * @returns {Promise<Conversation>|Promise}
         */
        value: function createConversation(participants, title, distinct, enablePublicJoin, customData, moderators) {
            var newConversation = new Conversation_1.Conversation(participants, distinct, enablePublicJoin, customData, moderators);
            newConversation.title = title;
            newConversation._createUUID();
            this.signalling.sendPayload(MsgEnums_1.MsgAction.createConversation, newConversation._getPayload());
        }
        /**
         * Remove conversation
         * @param uuid
         * @returns {Promise<Conversation>|Promise}
         */

    }, {
        key: "removeConversation",
        value: function removeConversation(uuid) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.removeConversation, { uuid: uuid });
        }
        /**
         * Edit conversation
         * @param conversation
         * @returns {Promise<Conversation>|Promise}
         */

    }, {
        key: "editConversation",
        value: function editConversation(conversation) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.editConversation, conversation._getSimplePayload());
        }
        /**
         * Return conversation from memory. If not exist, or "force" set to true - get conversation from backend
         * @param uuid
         * @returns {Promise<Conversation>|Promise}
         */

    }, {
        key: "getConversation",
        value: function getConversation(uuid) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.getConversation, { uuid: uuid });
        }
    }, {
        key: "getConversationByUUID",
        value: function getConversationByUUID(uuid) {
            var _this2 = this;

            return new Promise(function (resolve, reject) {
                var _iteratorNormalCompletion = true;
                var _didIteratorError = false;
                var _iteratorError = undefined;

                try {
                    for (var _iterator = _this2._converasationList[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                        var conversation = _step.value;

                        if (conversation.uuid === uuid) {
                            Messenger_1.Messenger.getInstance()._dispatchEvent(index_1.default.MessengerEvents.GetConversation, { conversation: conversation });
                            resolve(conversation);
                            return;
                        }
                    }
                } catch (err) {
                    _didIteratorError = true;
                    _iteratorError = err;
                } finally {
                    try {
                        if (!_iteratorNormalCompletion && _iterator.return) {
                            _iterator.return();
                        }
                    } finally {
                        if (_didIteratorError) {
                            throw _iteratorError;
                        }
                    }
                }

                _this2.awaitingConversations[uuid] = resolve;
            });
        }
    }, {
        key: "getConversations",
        value: function getConversations(conversations) {
            this.signalling.sendPayload(MsgEnums_1.MsgAction.getConversations, { uuids: conversations });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'ConversationManager';
        }
        /**
         * Resolve Event
         * @param payload
         * @param seq
         * @param realEvent
         */

    }, {
        key: "resolveEvent",
        value: function resolveEvent(payload, realEvent) {
            if (index_1.default.MessengerEvents[realEvent] === index_1.default.MessengerEvents[index_1.default.MessengerEvents.RemoveConversation]) {
                var payloadObject = payload.object;
                Messenger_1.Messenger.getInstance()._dispatchEvent(realEvent, {
                    name: index_1.default.MessengerEvents[realEvent],
                    uuid: payloadObject.uuid,
                    userId: payload.user_id,
                    seq: payload.seq,
                    onIncomingEvent: payload.on_incoming_event
                });
                return;
            }
            var conversation = Conversation_1.Conversation._createFromBus(payload.object, payload.seq);
            this.registerConversation(conversation);
            if (typeof conversation != 'undefined') conversation.updateSeq(payload.seq);
            Messenger_1.Messenger.getInstance()._dispatchEvent(realEvent, {
                name: index_1.default.MessengerEvents[realEvent],
                conversation: conversation,
                userId: payload.user_id,
                seq: payload.seq,
                onIncomingEvent: payload.on_incoming_event
            });
            //resolve awaiting conversation events, such new message
            if (realEvent === index_1.default.MessengerEvents.GetConversation && typeof this.awaitingConversations[conversation.uuid] !== 'undefined') {
                this.awaitingConversations[conversation.uuid](conversation);
                delete this.awaitingConversations[conversation.uuid];
            }
        }
        /**
         * Resolve message Event
         * @param payload
         * @param seq
         * @param realEvent
         */

    }, {
        key: "resolveMessageEvent",
        value: function resolveMessageEvent(payload, realEvent) {
            var message = Message_1.Message._createFromBus(payload.object, payload.seq);
            if (typeof this._converasationList[message.conversation] != 'undefined') this._converasationList[message.conversation].updateSeq(payload.seq);
            Messenger_1.Messenger.getInstance()._dispatchEvent(realEvent, {
                name: index_1.default.MessengerEvents[realEvent],
                message: message,
                userId: payload.user_id,
                seq: payload.seq,
                onIncomingEvent: payload.on_incoming_event
            });
        }
        /**
         * Add conversation to conversation list and database
         * @param conversation
         */

    }, {
        key: "registerConversation",
        value: function registerConversation(conversation) {
            this._converasationList.filter(function (item) {
                return item.uuid !== conversation.uuid;
            });
            this._converasationList.push(conversation);
        }
    }], [{
        key: "get",
        value: function get() {
            ConversationManager.instance = ConversationManager.instance || new ConversationManager();
            return ConversationManager.instance;
        }
        /**
         * Remove custom domain ending
         * @param username
         * @returns {string}
         */

    }, {
        key: "extractUserName",
        value: function extractUserName(username) {
            if (username.indexOf('@') === -1) {
                return username;
            } else {
                var userParts = username.split('@');
                userParts[1] = userParts[1].split('.').splice(0, 2).join('.');
                return userParts.join('@');
            }
        }
        /**
         * Deserialize conversation from disc cache
         * @hidden
         * @param value
         * @returns {Conversation}
         */

    }, {
        key: "deserialize",
        value: function deserialize(value) {
            return Conversation_1.Conversation.createFromCache(value);
        }
        /**
         * Serialize conversation for disc storage
         * @param conversation
         * @returns {SerializedConversation}
         */

    }, {
        key: "serialize",
        value: function serialize(conversation) {
            return conversation.toCache();
        }
    }]);

    return ConversationManager;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "createConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "removeConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "editConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "getConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "getConversationByUUID", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "getConversations", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "resolveEvent", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "resolveMessageEvent", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager.prototype, "registerConversation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager, "get", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager, "extractUserName", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager, "deserialize", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.MESSAGING)], ConversationManager, "serialize", null);
exports.ConversationManager = ConversationManager;

/***/ }),
/* 40 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function __export(m) {
    for (var p in m) {
        if (!exports.hasOwnProperty(p)) exports[p] = m[p];
    }
}
Object.defineProperty(exports, "__esModule", { value: true });
var Client_1 = __webpack_require__(1);
// import '../node_modules/webrtc-adapter/out/adapter_no_edge.js';
__webpack_require__(56);
var Authenticator_1 = __webpack_require__(10);
var index_1 = __webpack_require__(17);
var Events_1 = __webpack_require__(18);
exports.Events = Events_1.Events;
var CallEvents_1 = __webpack_require__(6);
exports.CallEvents = CallEvents_1.CallEvents;
var Endpoint_1 = __webpack_require__(36);
exports.Endpoint = Endpoint_1.Endpoint;
var EndpointEvents_1 = __webpack_require__(27);
exports.EndpointEvents = EndpointEvents_1.EndpointEvents;
var Messenger_1 = __webpack_require__(17);
exports.Messaging = Messenger_1.Messaging;
var Structures_1 = __webpack_require__(31);
exports.OperatorACDStatuses = Structures_1.OperatorACDStatuses;
var Logger_1 = __webpack_require__(0);
exports.LogCategory = Logger_1.LogCategory;
exports.LogLevel = Logger_1.LogLevel;
exports.ClientState = Logger_1.ClientState;
__export(__webpack_require__(4));
/**
 * Get a [Client] instance to use platform functions
 * @example
 *  var vox = VoxImplant.getInstance();
 *  vox.init({micRequired: true});
 *  vox.addEventListener(VoxImplant.Events.SDKReady, handleSDKReady);
 * @returns {Client}
 */
function getInstance() {
    return Client_1.Client.getInstance();
}
exports.getInstance = getInstance;
/**
 * VoxImplant Web SDK lib version
 */
exports.version = Client_1.Client.getInstance().version;
/**
 * Get instance of messaging subsystem
 * @returns {Messenger}
 *
 */
function getMessenger() {
    if (!Authenticator_1.Authenticator.get().authorized()) throw new Error("NOT_AUTHORIZED");
    return index_1.default.Messenger.getInstance();
}
exports.getMessenger = getMessenger;

/***/ }),
/* 41 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var SignalingDTMFSender_1 = __webpack_require__(19);
var Logger_1 = __webpack_require__(0);
/**
 * Firefox specific implementation
 * @hidden
 */

var FF = function () {
    function FF() {
        _classCallCheck(this, FF);
    }

    _createClass(FF, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'FF';
        }
    }], [{
        key: "attachStream",
        value: function attachStream(stream, element) {
            if (typeof element.srcObject === "undefined") {
                element["mozSrcObject"] = stream;
            } else {
                element.srcObject = stream;
            }
            element.load();
            element.play();
        }
    }, {
        key: "detachStream",
        value: function detachStream(element) {
            if (typeof element.srcObject === "undefined") {
                element["mozSrcObject"] = null;
            } else {
                element.srcObject = null;
            }
            element.load();
            element.src = "";
        }
    }, {
        key: "screenSharingSupported",
        value: function screenSharingSupported() {
            return new Promise(function (resolve, reject) {
                if (window.location.protocol != "https:") {
                    resolve(false);
                    return;
                }
                resolve(true);
            });
        }
    }, {
        key: "getScreenMedia",
        value: function getScreenMedia() {
            var constraints = { "audio": false, "video": { mediaSource: 'window' || 'screen' } };
            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, '[constraints]', Logger_1.LogLevel.TRACE, JSON.stringify(constraints));
            return navigator.mediaDevices.getUserMedia(constraints);
        }
    }, {
        key: "getRTCStats",
        value: function getRTCStats(pc) {
            return new Promise(function (resolve, reject) {
                pc.getStats(null).then(function (e) {
                    var resultArray = [];
                    e.forEach(function (result) {
                        if (result.type == "inboundrtp" || result.type == "outboundrtp") resultArray.push(result);
                    });
                    resolve(resultArray);
                }).catch(reject);
            });
        }
    }, {
        key: "getUserMedia",
        value: function getUserMedia(constraints) {
            return navigator.mediaDevices.getUserMedia(constraints);
        }
    }, {
        key: "getDTMFSender",
        value: function getDTMFSender(pc, callId) {
            var pattern = /Firefox\/([0-9\.]+)(?:\s|$)/;
            var ua = navigator.userAgent;
            if (pattern.test(ua)) {
                var browser = pattern.exec(ua);
                var version = browser[1].split('.');
                if (+version[0] >= 53) {
                    var dtmfSenders = pc.getSenders().map(function (sender) {
                        if (sender.track && sender.track.kind === "audio" && !!sender.dtmf) return sender.dtmf;
                    });
                    if (dtmfSenders.length > 0) return dtmfSenders[0];
                }
            }
            return new SignalingDTMFSender_1.SignalingDTMFSender(callId);
        }
    }]);

    return FF;
}();

exports.FF = FF;

/***/ }),
/* 42 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var SignalingDTMFSender_1 = __webpack_require__(19);
var Client_1 = __webpack_require__(1);
var Logger_1 = __webpack_require__(0);
/**
 * @hidden
 */

var Webkit = function () {
    function Webkit() {
        _classCallCheck(this, Webkit);
    }

    _createClass(Webkit, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'Webkit';
        }
    }], [{
        key: "attachStream",
        value: function attachStream(stream, element) {
            try {
                element.srcObject = stream;
                element.load();
                if (element instanceof HTMLVideoElement) element.play().catch(function (e) {});else {
                    element.play().catch(function (e) {});
                    var sinkId = Client_1.Client.getInstance()._defaultSinkId;
                    if (sinkId != null) element.setSinkId(sinkId);
                }
            } catch (e) {
                Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, 'Webkit: ', Logger_1.LogLevel.WARNING, JSON.stringify(e));
            }
        }
    }, {
        key: "detachStream",
        value: function detachStream(element) {
            element.srcObject = null;
            if (element instanceof HTMLVideoElement) {
                var promise = element.pause();
                if (typeof promise !== "undefined") promise.catch(function (e) {});
            } else element.pause();
            element.src = "";
        }
    }, {
        key: "getDTMFSender",
        value: function getDTMFSender(pc, callId) {
            if (!!pc.createDTMFSender) {
                var audioTracks = [];
                pc['getLocalStreams']().forEach(function (stream) {
                    stream.getAudioTracks().forEach(function (track) {
                        audioTracks.push(track);
                    });
                });
                if (audioTracks.length) {
                    return pc.createDTMFSender(audioTracks[0]);
                }
            } else return new SignalingDTMFSender_1.SignalingDTMFSender(callId);
        }
    }, {
        key: "getUserMedia",
        value: function getUserMedia(constraint) {
            return navigator.mediaDevices.getUserMedia(constraint);
        }
    }, {
        key: "screenSharingSupported",
        value: function screenSharingSupported() {
            return new Promise(function (resolve, reject) {
                var listener = function listener(event) {
                    if (event.origin === window.location.origin && event.data === 'VoximplantWebsdkExtensionLoaded') {
                        window.removeEventListener('message', listener);
                        clearTimeout(failTimer);
                        resolve(true);
                    }
                };
                window.addEventListener('message', listener);
                window.postMessage('VoximplantWebsdkCheckExtension', '*');
                var failTimer = setTimeout(function () {
                    window.removeEventListener('message', listener);
                    resolve(false);
                }, 800);
            });
        }
    }, {
        key: "getScreenMedia",
        value: function getScreenMedia() {
            if (navigator['getDisplayMedia']) {
                return navigator.getDisplayMedia({ video: true });
            } else {
                return new Promise(function (resolve, reject) {
                    window.postMessage('voximplantWebsdkGetSourceId', '*');
                    var listener = function listener(event) {
                        if (!event.data || event.origin !== window.location.origin) return;
                        if (!event.data.result) return;
                        if (event.data.result === 'err') return reject(new Error(event.data.reason));
                        if (event.data.result === 'ok' && typeof event.data.sourceId !== 'undefined') {
                            window.removeEventListener('message', listener);
                            var mediaParams = {
                                audio: false,
                                video: {
                                    mandatory: {
                                        chromeMediaSource: 'desktop',
                                        maxWidth: screen.width > 1920 ? screen.width : 1920,
                                        maxHeight: screen.height > 1080 ? screen.height : 1080,
                                        chromeMediaSourceId: event.data.sourceId
                                    },
                                    optional: [{ googTemporalLayeredScreencast: true }]
                                }
                            };
                            Logger_1.LogManager.get().writeMessage(Logger_1.LogCategory.USERMEDIA, '[constraints]', Logger_1.LogLevel.TRACE, JSON.stringify(mediaParams));
                            navigator.mediaDevices.getUserMedia(mediaParams).then(resolve, reject);
                        }
                    };
                    window.addEventListener('message', listener);
                });
            }
        }
    }, {
        key: "getRTCStats",
        value: function getRTCStats(pc) {
            return new Promise(function (resolve, reject) {
                var resultArray = [];
                pc.getStats(null).then(function (e) {
                    e.forEach(function (result) {
                        if (result.type == "outbound-rtp" || result.type == "inbound-rtp") {
                            resultArray.push(result);
                        }
                    });
                    resolve(resultArray);
                    return;
                }).catch(reject);
            });
        }
    }]);

    return Webkit;
}();

exports.Webkit = Webkit;

/***/ }),
/* 43 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var PeerConnection_1 = __webpack_require__(24);
var BrowserSpecific_1 = __webpack_require__(8);
var PCFactory_1 = __webpack_require__(9);
var Logger_1 = __webpack_require__(0);
var VoxSignaling_1 = __webpack_require__(2);
var CallManager_1 = __webpack_require__(5);
var RemoteFunction_1 = __webpack_require__(3);
var CodecSorter_1 = __webpack_require__(51);
var CallEvents_1 = __webpack_require__(6);
var CallstatsIo_1 = __webpack_require__(21);
var Constants_1 = __webpack_require__(11);
var SDPMuggle_1 = __webpack_require__(20);
var Client_1 = __webpack_require__(1);
var ReInviteQ_1 = __webpack_require__(37);
var index_1 = __webpack_require__(4);
var EndpointManager_1 = __webpack_require__(26);
/**
 * @hidden
 */
var RTCSdpType;
(function (RTCSdpType) {
    RTCSdpType[RTCSdpType["offer"] = 'offer'] = "offer";
    RTCSdpType[RTCSdpType["answer"] = 'answer'] = "answer";
    RTCSdpType[RTCSdpType["pranswer"] = 'pranswer'] = "pranswer";
    RTCSdpType[RTCSdpType["rollback"] = 'rollback'] = "rollback";
})(RTCSdpType || (RTCSdpType = {}));
/**
 * @hidden
 */
var RTCIceRole;
(function (RTCIceRole) {
    RTCIceRole[RTCIceRole["controlling"] = 'controlling'] = "controlling";
    RTCIceRole[RTCIceRole["controlled"] = 'controlled'] = "controlled";
})(RTCIceRole || (RTCIceRole = {}));
//WebRTC implementation of PeerConnection
/**
 * @hidden
 */

var WebRTCPC = function (_PeerConnection_1$Pee) {
    _inherits(WebRTCPC, _PeerConnection_1$Pee);

    function WebRTCPC(id, mode, videoEnabled) {
        _classCallCheck(this, WebRTCPC);

        var _this = _possibleConstructorReturn(this, (WebRTCPC.__proto__ || Object.getPrototypeOf(WebRTCPC)).call(this, id, mode, videoEnabled));

        _this.iceTimer = null;
        _this.needTransportRestart = true;
        /**
         * Max time to ICE
         * @type {number}
         */
        _this.ICE_TIMEOUT = 20000;
        /**
         * max renegotiation time
         * @type {number}
         */
        _this.RENEGOTIATION_TIMEOUT = 15000;
        _this._canReInvite = function () {
            return _this.impl.iceConnectionState === 'connected' || _this.impl.iceConnectionState === 'completed';
        };
        var cfg = PCFactory_1.PCFactory.get().iceConfig;
        var xconf = cfg;
        if (typeof xconf === 'undefined' || xconf === null) {
            xconf = { gatherPolicy: 'all', iceServers: [] };
        }
        xconf.bundlePolicy = 'max-compat';
        if (BrowserSpecific_1.default.getWSVendor() === "chrome" && Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.unifiedPlan) xconf.sdpSemantics = "unified-plan";
        if (_this.mode === PeerConnection_1.PeerConnectionMode.CONFERENCE && BrowserSpecific_1.default.getWSVendor() === 'chrome') {
            var sdpOptional = { mandatory: {}, optional: [{ googHighStartBitrate: true }, { googHighBitrate: true }, { googSkipEncodingUnusedStreams: true }, { googScreencastMinBitrate: 400 }, { googVeryHighBitrate: true }, { googCpuOveruseDetection: true }, { googCpuOveruseEncodeUsage: true }, { googCpuUnderuseThreshold: 55 }, { googCpuOveruseThreshold: 85 }] };
            var xRTCPeerConnection = RTCPeerConnection;
            _this.impl = new xRTCPeerConnection(xconf, sdpOptional);
        } else {
            _this.impl = new RTCPeerConnection(xconf);
        }
        // (this.impl as any).ondatachannel = (e)=>{
        //   console.error('DATA CHANNEL CREATED');
        //   let receiveChannel = e.channel;
        //   receiveChannel.onmessage = (ev)=>{
        //     console.error('NEW DATA CHANNEL MESSAGE');
        //     console.error((event as any).data);
        //   };
        //   receiveChannel.onopen = ()=>{
        //     console.error('DATA CHANNEL OPPENED');
        //   };
        //   receiveChannel.onclose = ()=>{
        //     console.error('DATA CHANNEL CLOSED');
        //   };
        // }
        if (_this.impl.getTransceivers && BrowserSpecific_1.default.getWSVendor() === "firefox") // Check if we have transivers. They better
            PCFactory_1.PCFactory.hasTransceivers = true;
        // FF 44 implementation
        if (typeof _this.impl.ontrack !== 'undefined') {
            _this.impl.ontrack = function (e) {
                return _this.onAddTrack(e);
            };
        } else if (typeof _this.impl['onaddtrack'] !== 'undefined') {
            _this.impl['onaddtrack'] = function (e) {
                return _this.onAddTrack(e);
            };
        } else {
            _this.impl['onaddstream'] = function (e) {
                return _this.onAddStream(e);
            };
        }
        _this.impl.onicecandidate = function (ev) {
            _this.onICECandidate(ev['candidate']);
        };
        _this.impl.oniceconnectionstatechange = function (e) {
            if (_this.impl.iceConnectionState === 'completed' || _this.impl.iceConnectionState === 'connected') {
                _this.iceTimer && clearTimeout(_this.iceTimer);
                _this.iceTimer = null;
                if (_this.reInviteQ) _this.reInviteQ.runNext();
            }
        };
        _this.rtpSenders = [];
        _this.renegotiationInProgress = false;
        _this.impl.onnegotiationneeded = function (e) {
            return _this.onRenegotiation();
        };
        _this.impl.onsignalingstatechange = function (e) {
            return _this.onSignalingStateChange();
        };
        _this.impl.oniceconnectionstatechange = function (e) {
            return _this.onConnectionChange();
        };
        _this.iceRole = RTCIceRole.controlling;
        _this._remoteStreams = [];
        _this.banReinviteAnswer = false;
        _this._call = CallManager_1.CallManager.get().calls[_this.id];
        //Check if call not active, set HOLD
        if (typeof _this._call != 'undefined') _this.onHold = !_this._call.active();else _this.onHold = false;
        _this.rtcCollectingCycle = setInterval(function () {
            _this.getPCStats();
        }, CallManager_1.CallManager.get().rtcStatsCollectionInterval);
        // Callstats.io integration
        if (typeof _this._call !== 'undefined') {
            var CSIOID = _this._call.headers()[Constants_1.Constants.CALLSTATSIOID_HEADER];
            if (typeof CSIOID === 'undefined') CSIOID = _this._call.id();
            CallstatsIo_1.CallstatsIo.get().addNewFabric(_this.impl, _this._call.number(), videoEnabled ? CallstatsIo_1.CallstatsIoFabricUsage.multiplex : CallstatsIo_1.CallstatsIoFabricUsage.audio, CSIOID);
        }
        _this.needTransportRestart = false;
        if (id !== '_default' && CallManager_1.CallManager.get().calls[id]) _this.reInviteQ = new ReInviteQ_1.ReInviteQ(CallManager_1.CallManager.get().calls[id], _this._canReInvite);
        return _this;
    }

    _createClass(WebRTCPC, [{
        key: "onSignalingStateChange",
        value: function onSignalingStateChange() {
            this.log.info('Signal state changed to ' + this.impl.signalingState + ' for PC:' + this.id);
            if (this.impl.signalingState === 'stable') {
                //TODO: there was screen sharing
            }
        }
    }, {
        key: "getPCStats",
        value: function getPCStats() {
            var _this2 = this;

            BrowserSpecific_1.default.getRTCStats(this.impl).then(function (statistic) {
                if (typeof _this2._call !== 'undefined') _this2._call.dispatchEvent({ name: 'RTCStatsReceived', stats: statistic });
            });
        }
    }, {
        key: "onConnectionChange",
        value: function onConnectionChange() {
            if (this.impl.iceConnectionState === 'completed') {
                if (typeof this._call !== 'undefined') {
                    this._call.dispatchEvent({ name: 'ICECompleted', call: this._call });
                }
            }
            if (this.impl.iceConnectionState === 'completed' || this.impl.iceConnectionState === 'connected') {
                this.iceTimer && clearTimeout(this.iceTimer);
                this.iceTimer = null;
                if (this.reInviteQ) this.reInviteQ.runNext();
            }
        }
        /**
         * Testing variant for renegotiation function
         *
         */

    }, {
        key: "onRenegotiation",
        value: function onRenegotiation() {
            var _this3 = this;

            if (typeof this.impl === 'undefined') return;
            if (this.impl.connectionState === 'disconnected' || this.impl.connectionState === 'failed') {
                this.log.info('Renegotiation requested on closed PeerConnection');
                return;
            }
            if (this.impl.localDescription === null) {
                this.log.info('Renegotiation needed, but no local SD, skipping');
                return;
            }
            if (this.impl.iceConnectionState !== 'connected' && this.impl.iceConnectionState !== 'completed') {
                this.log.info('Renegotiation requested while ice state is ' + this.impl.iceConnectionState + '. Postponing');
                setTimeout(this.onRenegotiation, 100);
                return;
            }
            if (this.renegotiationInProgress) {
                this.log.info('Renegotiation in progress. Queueing');
                return;
            } else {
                this.log.info('Renegotiation started');
            }
            if (this.renegotiationInProgress === false) {
                this.renegotiationInProgress = true;
                var offerOption = {};
                if (!(this.impl.getTransceivers && BrowserSpecific_1.default.getWSVendor() === "firefox")) offerOption = this.getReceiveOptions();
                this.updateHoldState();
                this.impl.createOffer(offerOption).then(function (sdp) {
                    return _this3.codecRearrange(sdp);
                }).then(function (sdp) {
                    var tempsdp = { type: sdp.type, sdp: sdp.sdp };
                    tempsdp = PCFactory_1.PCFactory.get().addBandwidthParams(tempsdp);
                    tempsdp = SDPMuggle_1.SDPMuggle.removeTelephoneEvents(tempsdp);
                    tempsdp = SDPMuggle_1.SDPMuggle.removeDoubleOpus(tempsdp);
                    tempsdp = SDPMuggle_1.SDPMuggle.fixVideoRecieve(tempsdp, _this3.videoEnabled.receiveVideo);
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        tempsdp = SDPMuggle_1.SDPMuggle.removeTransportCC(tempsdp);
                    }
                    // add xAS
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                        tempsdp = SDPMuggle_1.SDPMuggle.addXAS(tempsdp);
                    }
                    return tempsdp;
                }).then(function (sdp) {
                    _this3.srcLocalSDP = sdp.sdp;
                    _this3.pendingOffer = sdp;
                    return sdp;
                }).then(function () {
                    var extra = { tracks: _this3.getTrackKind() };
                    VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.reInvite, _this3._call.id(), {}, _this3.pendingOffer.sdp, extra);
                }).catch(function (e) {
                    _this3.log.error('Error when renegatiation start ' + e.message);
                });
            } else {
                this.log.error('Another renegatiation in progress');
            }
        }
    }, {
        key: "getReceiveOptions",
        value: function getReceiveOptions() {
            return {
                'offerToReceiveAudio': !this.onHold,
                'offerToReceiveVideo': this.videoEnabled.receiveVideo && !this.onHold
            };
        }
    }, {
        key: "updateHoldState",
        value: function updateHoldState() {
            var _this4 = this;

            this.impl['getLocalStreams']().forEach(function (stream) {
                stream.getTracks().forEach(function (track) {
                    track.enabled = !_this4.onHold;
                });
            });
            this.impl['getRemoteStreams']().forEach(function (stream) {
                stream.getTracks().forEach(function (track) {
                    track.enabled = !_this4.onHold;
                });
            });
        }
        /**
         * Callback to add new local candidates to send
         * @param cand
         */

    }, {
        key: "onICECandidate",
        value: function onICECandidate(cand) {
            if (cand && cand !== null) {
                this.sendLocalCandidateToPeer('a=' + cand.candidate, cand.sdpMLineIndex);
            } else {
                this.log.info('End of candidates');
            }
        }
        /**
         * Callback to add new Track
         * @param e
         */

    }, {
        key: "onAddTrack",
        value: function onAddTrack(e) {
            if (this._call) {
                EndpointManager_1.EndpointManager.get().addTrack(this._call, e.track);
            }
        }
    }, {
        key: "onAddStream",
        value: function onAddStream(e) {
            if (this._call) {
                EndpointManager_1.EndpointManager.get().addStream(this._call, e.stream);
            }
        }
    }, {
        key: "_processRemoteAnswer",
        value: function _processRemoteAnswer(headers, sdp) {
            var _this5 = this;

            if (sdp.length === 0 && this._call) {
                this.log.error('Empty SDP from server. Call will be terminated.');
                this._call.hangup({ 'X-WebRTCError': 'no sdp' });
                return;
            }
            this.iceTimer = setTimeout(function () {
                _this5._call.notifyICETimeout();
            }, this.ICE_TIMEOUT);
            this.pendingEvent = [headers, sdp];
            if (this.impl.remoteDescription !== null) if (this.impl.remoteDescription.sdp != '') return;
            var d = { sdp: sdp, type: RTCSdpType.answer };
            this.srcRemoteSDP = sdp;
            d = SDPMuggle_1.SDPMuggle.removeTIAS(d);
            return this.impl.setRemoteDescription(d);
        }
    }, {
        key: "_getLocalOffer",
        value: function _getLocalOffer() {
            var _this6 = this;

            this.iceRole = RTCIceRole.controlling;
            return new Promise(function (resolve, reject) {
                var rtcOfferOptions = _this6.getReceiveOptions();
                _this6.impl.createOffer(rtcOfferOptions).then(function (sdp) {
                    var tempsdp = { type: sdp.type, sdp: sdp.sdp };
                    tempsdp = PCFactory_1.PCFactory.get().addBandwidthParams(tempsdp);
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        tempsdp = SDPMuggle_1.SDPMuggle.removeTransportCC(tempsdp);
                    }
                    // add xAS
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                        tempsdp = SDPMuggle_1.SDPMuggle.addXAS(tempsdp);
                    }
                    return _this6.codecRearrange(tempsdp);
                }).then(function (sdp) {
                    _this6.srcLocalSDP = sdp.sdp;
                    return _this6.impl.setLocalDescription(sdp);
                }).then(function () {
                    resolve(_this6.impl.localDescription);
                }).catch(function (e) {
                    reject(e);
                });
            });
        }
    }, {
        key: "_getLocalAnswer",
        value: function _getLocalAnswer() {
            var _this7 = this;

            this.iceRole = RTCIceRole.controlled;
            return new Promise(function (resolve, reject) {
                var rtcAnswerOptions = { mandatory: _this7.getReceiveOptions() };
                _this7.impl.createAnswer(rtcAnswerOptions).then(function (sdp) {
                    var tempsdp = { type: sdp.type, sdp: sdp.sdp };
                    tempsdp = PCFactory_1.PCFactory.get().addBandwidthParams(tempsdp);
                    tempsdp = SDPMuggle_1.SDPMuggle.fixVideoRecieve(tempsdp, _this7._call.settings.videoDirections.receiveVideo);
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        tempsdp = SDPMuggle_1.SDPMuggle.removeTransportCC(tempsdp);
                    }
                    // add xAS
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                        tempsdp = SDPMuggle_1.SDPMuggle.addXAS(tempsdp);
                    }
                    return _this7.codecRearrange(tempsdp);
                }).then(function (sdp) {
                    _this7.srcLocalSDP = sdp.sdp;
                    return _this7.impl.setLocalDescription(sdp);
                }).then(function () {
                    resolve({ type: RTCSdpType.answer, sdp: _this7.impl.localDescription.sdp });
                }).catch(function (e) {
                    reject(e);
                });
            });
        }
    }, {
        key: "_setRemoteDescription",
        value: function _setRemoteDescription(sdp) {
            if (sdp.length === 0 && this._call) {
                this.log.error('Empty SDP from server. Call will be terminated.');
                this._call.hangup({ 'X-WebRTCError': 'no sdp' });
                return;
            }
            var d = new RTCSessionDescription({ sdp: sdp, type: RTCSdpType.offer });
            d = SDPMuggle_1.SDPMuggle.removeTIAS(d);
            // remove transportCC
            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                d = SDPMuggle_1.SDPMuggle.removeTransportCC(d);
            }
            this.srcRemoteSDP = sdp;
            return this.impl.setRemoteDescription(d);
        }
    }, {
        key: "_processRemoteOffer",
        value: function _processRemoteOffer(sdp) {
            var _this8 = this;

            if (sdp.length === 0 && this._call) {
                this.log.error('Empty SDP from server. Call will be terminated.');
                this._call.hangup({ 'X-WebRTCError': 'no sdp' });
                return;
            }
            this.iceRole = RTCIceRole.controlled;
            return new Promise(function (resolve, reject) {
                var d = new RTCSessionDescription({ sdp: sdp, type: RTCSdpType.offer });
                // remove transportCC
                if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                    d = SDPMuggle_1.SDPMuggle.removeTransportCC(d);
                }
                _this8.srcRemoteSDP = sdp;
                d = SDPMuggle_1.SDPMuggle.removeTIAS(d);
                d = SDPMuggle_1.SDPMuggle.fixFFMIDBug(d);
                _this8.impl.setRemoteDescription(d).then(function () {
                    var rtcAnswerOptions = { mandatory: _this8.getReceiveOptions() };
                    return _this8.impl.createAnswer(rtcAnswerOptions);
                }).then(function (sdp) {
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        sdp = SDPMuggle_1.SDPMuggle.removeTransportCC(sdp);
                    }
                    // add xAS
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                        sdp = SDPMuggle_1.SDPMuggle.addXAS(sdp);
                    }
                    return _this8.codecRearrange(sdp);
                }).then(function (sdp) {
                    _this8.srcLocalSDP = sdp.sdp;
                    return _this8.impl.setLocalDescription(sdp);
                }).then(function () {
                    // let receiveChannel = (this.impl as any).createDataChannel('title',{ordered:true});
                    // console.error('DATA CHANNEL CREATING');
                    // receiveChannel.onmessage = (ev) => {
                    //   console.error('NEW DATA CHANNEL MESSAGE');
                    //   console.error((event as any).data);
                    // };
                    // receiveChannel.onopen = () => {
                    //   console.error('DATA CHANNEL OPPENED');
                    // };
                    // receiveChannel.onclose = () => {
                    //   console.error('DATA CHANNEL CLOSED');
                    // };
                    resolve(_this8.impl.localDescription.sdp);
                }).catch(function (e) {
                    reject(e);
                });
            });
        }
        /**
         * Close curent PeerConnection
         *
         * @private
         */

    }, {
        key: "_close",
        value: function _close() {
            var _this9 = this;

            clearInterval(this.rtcCollectingCycle);
            this.impl.onnegotiationneeded = null;
            var appConfig = Client_1.Client.getInstance().config();
            if (this.impl.removeTrack) {
                this.rtpSenders.forEach(function (sender) {
                    _this9.impl.removeTrack(sender);
                });
                index_1.default.StreamManager.get().remCallStream(this._call);
            } else {
                index_1.default.StreamManager.get().remCallStream(this._call);
                this.impl['getLocalStreams']().forEach(function (stream) {
                    _this9.impl['removeStream'](stream);
                });
            }
            this.impl.close();
            if (typeof this._call !== 'undefined') CallstatsIo_1.CallstatsIo.get().sendFabricEvent(this.impl, CallstatsIo_1.CallstatsIoFabricEvent.fabricTerminated, this._call.id());
            this._localStream = null;
            this._remoteStreams = null;
        }
        /**
         * Add remote candidate from peer
         *
         * @param candidate
         * @param mLineIndex
         * @returns {Promise<void>}
         * @private
         */

    }, {
        key: "_addRemoteCandidate",
        value: function _addRemoteCandidate(candidate, mLineIndex) {
            var _this10 = this;

            return new Promise(function (resolve, reject) {
                try {
                    _this10.impl.addIceCandidate(new RTCIceCandidate({
                        candidate: candidate.substring(2),
                        sdpMLineIndex: mLineIndex
                    })).then(function () {
                        resolve();
                    }).catch(function () {
                        resolve();
                    });
                } catch (e) {
                    resolve();
                }
            });
        }
        /**
         * Action for ReInvite message from server
         * if incoming sdp empty: start renegotiation - else create answer
         *
         * @author Igor Sheko
         * @param headers
         * @param sdp
         * @returns {Promise<void>|Promise}
         * @private
         */

    }, {
        key: "_handleReinvite",
        value: function _handleReinvite(headers, sdp) {
            var _this11 = this;

            if (sdp.length === 0 && this._call) {
                this.log.error('Empty SDP from server. Call will be terminated.');
                this._call.hangup({ 'X-WebRTCError': 'no sdp' });
                return;
            }
            return new Promise(function (resolve, reject) {
                if (_this11.banReinviteAnswer) {
                    reject(new Error());
                }
                if (_this11.renegotiationInProgress === false) {
                    _this11.renegotiationInProgress = true;
                    var d = { sdp: sdp, type: RTCSdpType.offer };
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        d = SDPMuggle_1.SDPMuggle.removeTransportCC(d);
                    }
                    _this11.srcRemoteSDP = sdp;
                    d = SDPMuggle_1.SDPMuggle.removeTIAS(d);
                    _this11.impl.setRemoteDescription(d).then(function () {
                        var rtcAnswerOptions = { mandatory: _this11.getReceiveOptions() };
                        _this11.impl.createAnswer(rtcAnswerOptions).then(function (localSDP) {
                            var tempsdp = { type: localSDP.type, sdp: localSDP.sdp };
                            tempsdp = SDPMuggle_1.SDPMuggle.removeDoubleOpus(tempsdp);
                            tempsdp = SDPMuggle_1.SDPMuggle.fixVideoRecieve(tempsdp, _this11.videoEnabled.receiveVideo);
                            // remove transportCC
                            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                                tempsdp = SDPMuggle_1.SDPMuggle.removeTransportCC(tempsdp);
                            }
                            // add xAS
                            if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                                tempsdp = SDPMuggle_1.SDPMuggle.addXAS(tempsdp);
                            }
                            _this11.srcLocalSDP = tempsdp.sdp;
                            try {
                                _this11.impl.setLocalDescription(tempsdp).then(function () {
                                    var extra = { tracks: _this11.getTrackKind() };
                                    VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.acceptReInvite, _this11._call.id(), headers, _this11.impl.localDescription.sdp, extra);
                                    _this11.renegotiationInProgress = false;
                                    _this11._call.dispatchEvent({ name: CallEvents_1.CallEvents.Updated, result: true, call: _this11._call });
                                    _this11.updateHoldState();
                                    resolve();
                                });
                            } catch (e) {
                                _this11.renegotiationInProgress = false;
                                reject(e);
                            }
                        });
                    });
                } else if (_this11.renegotiationInProgress === true) {
                    //get remoteAnswer
                    var _d = { sdp: sdp, type: RTCSdpType.answer };
                    _this11.renegotiationInProgress = false;
                    // remove transportCC
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.removeTransportCC) {
                        _d = SDPMuggle_1.SDPMuggle.removeTransportCC(_d);
                    }
                    // add xAS
                    if (Client_1.Client.getInstance().config().experiments && Client_1.Client.getInstance().config().experiments.xas) {
                        _d = SDPMuggle_1.SDPMuggle.addXAS(_d);
                    }
                    _this11.srcRemoteSDP = sdp;
                    _d = SDPMuggle_1.SDPMuggle.removeTIAS(_d);
                    _d = SDPMuggle_1.SDPMuggle.fixFMTP(_d);
                    _this11.impl.setLocalDescription(_this11.pendingOffer).then(function () {
                        try {
                            _this11.impl.setRemoteDescription(_d).then(function () {
                                _this11._call.dispatchEvent({ name: CallEvents_1.CallEvents.Updated, result: true, call: _this11._call });
                                _this11.updateHoldState();
                                resolve();
                            });
                        } catch (e) {
                            _this11._call.dispatchEvent({ name: CallEvents_1.CallEvents.Updated, result: false, call: _this11._call });
                            _this11.renegotiationInProgress = false;
                            _this11.log.error(JSON.stringify(e));
                            reject(e);
                        }
                        clearTimeout(_this11.renegotiationTimer);
                    });
                } else {
                    reject(new Error('Universe was broken!'));
                }
            });
        }
        /**
         * Promise to rearrange codec by user
         *
         * @author Igor Sheko
         * @param sdp
         * @returns {Promise<RTCSessionDescription>|Promise}
         */

    }, {
        key: "codecRearrange",
        value: function codecRearrange(sdp) {
            var _this12 = this;

            return new Promise(function (resolve, reject) {
                var call = CallManager_1.CallManager.get().calls[_this12.id];
                if (typeof call !== 'undefined') {
                    var codecSorter = new CodecSorter_1.CodecSorter(sdp.sdp);
                    var userCodecList = codecSorter.getUserCodecList();
                    if (typeof call.rearangeCodecs !== 'undefined') {
                        call.rearangeCodecs(userCodecList, call.settings.incoming).then(function (newCodecList) {
                            codecSorter.setUserCodecList(newCodecList);
                            resolve({ type: sdp.type, sdp: codecSorter.getSDP() });
                        }, function (e) {
                            _this12.log.error(JSON.stringify(e));
                            reject(e);
                        });
                    } else {
                        _this12.log.info('No sdp transformer registered');
                        codecSorter.setUserCodecList(userCodecList);
                        resolve({ type: sdp.type, sdp: codecSorter.getSDP() });
                    }
                } else {
                    resolve(sdp);
                }
            });
        }
        /**
         * Sed DTMF via WebRTC if can
         *
         * @author Igor Sheko
         * @param key
         * @param duration
         * @param gap
         * @private
         */

    }, {
        key: "_sendDTMF",
        value: function _sendDTMF(key, duration, gap) {
            if (typeof this.dtmfSender !== 'undefined') {
                this.dtmfSender.insertDTMF(key, duration, gap);
            }
        }
        /**
         * Hold call by remove local stream and start renegotiation process
         * Hold call by add local stream and start renegotiation process
         * @param newState
         * @returns {undefined}
         * @private
         */

    }, {
        key: "_hold",
        value: function _hold(newState) {
            CallstatsIo_1.CallstatsIo.get().sendFabricEvent(this.impl, newState ? CallstatsIo_1.CallstatsIoFabricEvent.fabricHold : CallstatsIo_1.CallstatsIoFabricEvent.fabricResume, this._call.id());
            this.onHold = newState;
            if (this.impl.getTransceivers && BrowserSpecific_1.default.getWSVendor() === "firefox") {
                this.impl.getTransceivers().forEach(function (transceiver) {
                    if (newState) transceiver.direction = "sendonly";else transceiver.direction = "sendrecv";
                });
            } else {
                this.onRenegotiation();
            }
        }
    }, {
        key: "_getDirections",
        value: function _getDirections() {
            var directions = {};
            directions['local'] = SDPMuggle_1.SDPMuggle.detectDirections(this.impl.localDescription.sdp);
            directions['remote'] = SDPMuggle_1.SDPMuggle.detectDirections(this.impl.remoteDescription.sdp);
            return directions;
        }
    }, {
        key: "_getStreamActivity",
        value: function _getStreamActivity() {
            var status = {};
            status['local'] = this.getMediaActivity(this.impl['getLocalStreams']());
            status['remote'] = this.getMediaActivity(this.impl['getRemoteStreams']());
            return status;
        }
    }, {
        key: "getMediaActivity",
        value: function getMediaActivity(streams) {
            return streams.map(function (item) {
                return item.getTracks().map(function (x_item) {
                    return {
                        id: x_item.id,
                        kind: x_item.kind,
                        mutted: x_item.muted,
                        active: x_item.enabled,
                        label: x_item.label
                    };
                });
            });
        }
    }, {
        key: "_hdnFRSPrep",
        value: function _hdnFRSPrep() {
            this.banReinviteAnswer = true;
        }
    }, {
        key: "_hdnFRS",
        value: function _hdnFRS() {
            this.renegotiationInProgress = false;
            this.onRenegotiation();
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'WebRTCPC';
        }
    }, {
        key: "hasLocalAudio",
        value: function hasLocalAudio() {
            return this.impl['getLocalStreams']().some(function (stream) {
                if (stream.getAudioTracks().length) return true;else return false;
            });
        }
    }, {
        key: "hasLocalVideo",
        value: function hasLocalVideo() {
            var _this13 = this;

            return this.impl['getLocalStreams']().some(function (stream) {
                return stream.getVideoTracks().some(function (track) {
                    if (!_this13.shareScreenMedia || !_this13.shareScreenMedia.some(function (sStream) {
                        return sStream.getTracks().some(function (sTrack) {
                            return sTrack.id === track.id;
                        });
                    })) {
                        return true;
                    } else {
                        return false;
                    }
                });
            });
        }
    }, {
        key: "enableVideo",
        value: function enableVideo(flag) {
            var sharingList = [];
            if (this._call) sharingList = index_1.default.StreamManager.get()._getScreenSharing(this._call) || [];
            var sharingIds = [];
            sharingList.forEach(function (sharingData) {
                return sharingData.stream.getTracks().forEach(function (track) {
                    return sharingIds.push(track.id);
                });
            });
            this.impl['getLocalStreams']().forEach(function (stream) {
                stream.getVideoTracks().forEach(function (track) {
                    if (!sharingIds.some(function (id) {
                        return track.id === id;
                    })) {
                        track.enabled = flag;
                    }
                });
            });
        }
    }, {
        key: "getTransceivers",
        value: function getTransceivers() {
            if (this.impl.getTransceivers) return this.impl.getTransceivers();else return [];
        }
    }, {
        key: "getRemoteDescription",
        value: function getRemoteDescription() {
            if (this.impl.remoteDescription && this.impl.remoteDescription.sdp) return this.impl.remoteDescription.sdp;else return '';
        }
    }, {
        key: "_addCustomMedia",
        value: function _addCustomMedia(stream) {
            var _this14 = this;

            if (!stream) return;
            if (BrowserSpecific_1.default.getWSVendor() === 'firefox') {
                stream.getTracks().forEach(function (track) {
                    var newStream = new MediaStream([track]);
                    _this14.rtpSenders.push(_this14.impl.addTrack(track, newStream));
                    _this14.onRenegotiation();
                });
            } else {
                this.impl['addStream'](stream);
            }
            if (!this.dtmfSender && this._call) {
                var newSender = BrowserSpecific_1.default.getDTMFSender(this.impl, this._call.id());
                if (newSender) this.dtmfSender = newSender;
            }
        }
    }, {
        key: "_removeCustomMedia",
        value: function _removeCustomMedia(stream) {
            var _this15 = this;

            if (!stream) return;
            if (BrowserSpecific_1.default.getWSVendor() === 'firefox') {
                if (this.impl.getTransceivers) {
                    var transceiverList = this.impl.getTransceivers();
                    transceiverList.forEach(function (transceiver) {
                        if (stream.getTracks().find(function (track) {
                            return track.id === transceiver.sender.track.id;
                        })) {
                            transceiver.stop();
                        }
                    });
                } else {
                    this.impl.getSenders().forEach(function (sender) {
                        if (stream.getTracks().indexOf(sender.track) !== -1) _this15.impl.removeTrack(sender);
                    });
                }
            } else {
                this.impl['removeStream'](stream);
            }
        }
    }, {
        key: "_fixFFSoundBug",
        value: function _fixFFSoundBug() {
            var _this16 = this;

            if (BrowserSpecific_1.default.getWSVendor() === 'firefox' && PCFactory_1.PCFactory.hasTransceivers) {
                setTimeout(function () {
                    if (_this16.impl.getSenders) {
                        var senders = _this16.impl.getSenders();
                        senders.forEach(function (sender) {
                            if (sender.replaceTrack) {
                                var track = sender.track;
                                sender.replaceTrack(track);
                            }
                        });
                    }
                }, 1000);
            }
        }
    }]);

    return WebRTCPC;
}(PeerConnection_1.PeerConnection);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onSignalingStateChange", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "getPCStats", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onConnectionChange", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onRenegotiation", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "getReceiveOptions", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "updateHoldState", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onICECandidate", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onAddTrack", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "onAddStream", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_processRemoteAnswer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_getLocalOffer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_getLocalAnswer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_setRemoteDescription", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_processRemoteOffer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_close", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_addRemoteCandidate", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_handleReinvite", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "codecRearrange", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_sendDTMF", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_hold", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_getStreamActivity", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "getMediaActivity", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_hdnFRSPrep", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_hdnFRS", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "hasLocalAudio", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "hasLocalVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "enableVideo", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_addCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_removeCustomMedia", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], WebRTCPC.prototype, "_fixFFSoundBug", null);
exports.WebRTCPC = WebRTCPC;

/***/ }),
/* 44 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
/**
 * @hidden
 */
var AudioDeviceManager_1 = __webpack_require__(32);
exports.AudioDeviceManager = AudioDeviceManager_1.AudioDeviceManager;
/**
 * @hidden
 */
var CameraManager_1 = __webpack_require__(33);
exports.CameraManager = CameraManager_1.CameraManager;
/**
 * @hidden
 */
var StreamManager_1 = __webpack_require__(25);
exports.StreamManager = StreamManager_1.StreamManager;
/**
 * @hidden
 */
var IOSCacheManager_1 = __webpack_require__(45);
exports.IOSCacheManager = IOSCacheManager_1.IOSCacheManager;

/***/ }),
/* 45 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });

var IOSCacheManager = function () {
    function IOSCacheManager() {
        _classCallCheck(this, IOSCacheManager);

        this._lastCameraParams = {};
        this._lastAudioParams = {};
        if (typeof IOSCacheManager.instance !== 'undefined') throw new Error('Error - use StreamManager.get()');
    }

    _createClass(IOSCacheManager, [{
        key: "getStream",
        value: function getStream(constrains) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                if (_this._localMedia) {
                    resolve(_this._localMedia);
                    return;
                }
                navigator.mediaDevices.getUserMedia(constrains).then(function (stream) {
                    _this._localMedia = stream;
                    resolve(stream);
                }).catch(function (e) {
                    return reject(e);
                });
            });
        }
    }, {
        key: "clear",
        value: function clear(call) {
            for (var mediaRendererId in this._callRendererList[call.id()]) {
                if (this._callRendererList[call.id()].hasOwnProperty(mediaRendererId)) delete this._callRendererList[call.id()][mediaRendererId];
            }delete this._callRendererList[call.id()];
        }
    }, {
        key: "registerMediaRenderer",
        value: function registerMediaRenderer(call, mediaRenderer) {}
        /**
         * Check if must renew cache, because videoParams
         * @param cameraParams
         */

    }, {
        key: "diffCameraParams",
        value: function diffCameraParams(cameraParams) {
            return this.fastDiffObjects(this._lastCameraParams, cameraParams);
        }
        /**
         * Check if must renew cache, because audioParams
         * @param audioParams
         */

    }, {
        key: "diffAudioParams",
        value: function diffAudioParams(audioParams) {
            return this.fastDiffObjects(this._lastAudioParams, audioParams);
        }
    }, {
        key: "fastDiffObjects",
        value: function fastDiffObjects(a, b) {
            return Object.keys(a).length !== Object.keys(b).length ? false : Object.keys(a).every(function (key) {
                return a[key] === b[key];
            });
        }
    }], [{
        key: "get",
        value: function get() {
            if (typeof IOSCacheManager.instance === 'undefined') IOSCacheManager.instance = new IOSCacheManager();
            return IOSCacheManager.instance;
        }
    }]);

    return IOSCacheManager;
}();

exports.IOSCacheManager = IOSCacheManager;

/***/ }),
/* 46 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var AbstractEndpointManager_1 = __webpack_require__(35);
var Logger_1 = __webpack_require__(0);
var SDPMuggle_1 = __webpack_require__(20);
/**
 * Endpoint manager for browsers, witch support Transceivers API
 * @hidden
 */

var TransceiversEndpointManager = function (_AbstractEndpointMana) {
    _inherits(TransceiversEndpointManager, _AbstractEndpointMana);

    function TransceiversEndpointManager() {
        _classCallCheck(this, TransceiversEndpointManager);

        return _possibleConstructorReturn(this, (TransceiversEndpointManager.__proto__ || Object.getPrototypeOf(TransceiversEndpointManager)).call(this));
    }

    _createClass(TransceiversEndpointManager, [{
        key: "getEndpointByTrack",
        value: function getEndpointByTrack(call, track) {
            var rTrackId = this.getRtrackId(call, track.id);
            return this._getEndpointByTrackId(call, rTrackId);
        }
    }, {
        key: "getRtrackId",
        value: function getRtrackId(call, trackId) {
            if (typeof call.peerConnection !== "undefined") {
                var transceivers = call.peerConnection.getTransceivers();
                for (var i = 0; i < transceivers.length; i++) {
                    if (transceivers[i].mid !== null && transceivers[i].receiver.track.id === trackId) {
                        var rtrackId = SDPMuggle_1.SDPMuggle.findTrackByMid(call.peerConnection.getRemoteDescription(), transceivers[i].mid);
                        if (rtrackId) return rtrackId;
                    }
                }
            } else {
                return '';
            }
        }
    }, {
        key: "getMediaTypeTrack",
        value: function getMediaTypeTrack(call, track) {
            var rTrackId = this.getRtrackId(call, track.id);
            return this._getMediaTypeTrack(call, { id: rTrackId, kind: track.kind });
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'TransceiversEndpointManager';
        }
    }]);

    return TransceiversEndpointManager;
}(AbstractEndpointManager_1.AbstractEndpointManager);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], TransceiversEndpointManager.prototype, "getEndpointByTrack", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.ENDPOINT)], TransceiversEndpointManager.prototype, "getMediaTypeTrack", null);
exports.TransceiversEndpointManager = TransceiversEndpointManager;

/***/ }),
/* 47 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

Object.defineProperty(exports, "__esModule", { value: true });
var AbstractEndpointManager_1 = __webpack_require__(35);
/**
 * Endpoint manager based on track names
 * @hidden
 */

var PlainEndpointManager = function (_AbstractEndpointMana) {
    _inherits(PlainEndpointManager, _AbstractEndpointMana);

    function PlainEndpointManager() {
        _classCallCheck(this, PlainEndpointManager);

        return _possibleConstructorReturn(this, (PlainEndpointManager.__proto__ || Object.getPrototypeOf(PlainEndpointManager)).apply(this, arguments));
    }

    _createClass(PlainEndpointManager, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'PlainEndpointManager';
        }
    }, {
        key: "getEndpointByTrack",
        value: function getEndpointByTrack(call, track) {
            return this._getEndpointByTrackId(call, track.id);
        }
    }, {
        key: "getMediaTypeTrack",
        value: function getMediaTypeTrack(call, track) {
            return this._getMediaTypeTrack(call, track);
        }
    }]);

    return PlainEndpointManager;
}(AbstractEndpointManager_1.AbstractEndpointManager);

exports.PlainEndpointManager = PlainEndpointManager;

/***/ }),
/* 48 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Call_1 = __webpack_require__(13);
var VoxSignaling_1 = __webpack_require__(2);
var CallManager_1 = __webpack_require__(5);
var Logger_1 = __webpack_require__(0);
var RemoteFunction_1 = __webpack_require__(3);
/**
 * @hidden
 */

var CallExServer = function (_Call_1$Call) {
    _inherits(CallExServer, _Call_1$Call);

    function CallExServer(id, dn, incoming, settings) {
        _classCallCheck(this, CallExServer);

        var _this = _possibleConstructorReturn(this, (CallExServer.__proto__ || Object.getPrototypeOf(CallExServer)).call(this, id, dn, incoming, settings));

        _this.settings.mode = Call_1.CallMode.SERVER;
        return _this;
    }

    _createClass(CallExServer, [{
        key: "answer",
        value: function answer(customData, extraHeaders) {
            _get(CallExServer.prototype.__proto__ || Object.getPrototypeOf(CallExServer.prototype), "answer", this).call(this, customData, extraHeaders);
            var extra = { tracks: this.peerConnection.getTrackKind() };
            VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.acceptCall, this.settings.id, CallManager_1.CallManager.cleanHeaders(extraHeaders), extra);
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'CallExServer';
        }
    }]);

    return CallExServer;
}(Call_1.Call);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALLEXSERVER)], CallExServer.prototype, "answer", null);
exports.CallExServer = CallExServer;

/***/ }),
/* 49 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Call_1 = __webpack_require__(13);
var VoxSignaling_1 = __webpack_require__(2);
var Logger_1 = __webpack_require__(0);
var RemoteFunction_1 = __webpack_require__(3);
var Constants_1 = __webpack_require__(11);
var Client_1 = __webpack_require__(1);
var index_1 = __webpack_require__(4);
var CallEvents_1 = __webpack_require__(6);
/**
 * @hidden
 */

var CallExMedia = function (_Call_1$Call) {
    _inherits(CallExMedia, _Call_1$Call);

    function CallExMedia() {
        _classCallCheck(this, CallExMedia);

        return _possibleConstructorReturn(this, (CallExMedia.__proto__ || Object.getPrototypeOf(CallExMedia)).apply(this, arguments));
    }

    _createClass(CallExMedia, [{
        key: "answer",
        value: function answer(customData, extraHeaders, useVideo) {
            var _this2 = this;

            _get(CallExMedia.prototype.__proto__ || Object.getPrototypeOf(CallExMedia.prototype), "answer", this).call(this, customData, extraHeaders);
            if (typeof customData != 'undefined') {
                if (typeof extraHeaders == 'undefined' || (typeof extraHeaders === "undefined" ? "undefined" : _typeof(extraHeaders)) !== "object") extraHeaders = {};
                extraHeaders[Constants_1.Constants.CALL_DATA_HEADER] = customData;
            }
            var appConfig = Client_1.Client.getInstance().config();
            return new Promise(function (resolve, reject) {
                if ((typeof useVideo === "undefined" ? "undefined" : _typeof(useVideo)) === "object") _this2.settings.videoDirections = useVideo;
                index_1.default.StreamManager.get().getCallStream(_this2).then(function (stream) {
                    _this2._peerConnection.fastAddCustomMedia(stream);
                    _this2._peerConnection.getLocalAnswer().then(function (activeLocalSD) {
                        var extra = { tracks: _this2.peerConnection.getTrackKind() };
                        VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.acceptCall, _this2.id(), extraHeaders, activeLocalSD.sdp, extra);
                        _this2._peerConnection._fixFFSoundBug();
                        resolve();
                    });
                }).catch(function (e) {
                    return reject(e);
                });
            });
        }
        /**
         * New version of setActive function - attach/detach by changes in SDP
         * @param newState
         */

    }, {
        key: "setActive",
        value: function setActive(newState) {
            var _this3 = this;

            if (newState === this.settings.active) {
                return new Promise(function (a, resolve) {
                    resolve({ name: CallEvents_1.CallEvents['Updated'], result: false, call: _this3 });
                });
            }
            this.settings.active = newState;
            return this.peerConnection.hold(!newState);
        }
        /**
         * @hidden
         * @return {string}
         * @private
         */

    }, {
        key: "_traceName",
        value: function _traceName() {
            return 'CallExMedia';
        }
    }]);

    return CallExMedia;
}(Call_1.Call);

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], CallExMedia.prototype, "answer", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.CALL)], CallExMedia.prototype, "setActive", null);
exports.CallExMedia = CallExMedia;

/***/ }),
/* 50 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
    var c = arguments.length,
        r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
        d;
    if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
        if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    }return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
var Logger_1 = __webpack_require__(0);
/**
 * @hidden
 */

var CodecSorterHelpers = function () {
    function CodecSorterHelpers() {
        _classCallCheck(this, CodecSorterHelpers);
    }

    _createClass(CodecSorterHelpers, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'CodecSorterHelpers';
        }
    }], [{
        key: "H264Sorter",
        value: function H264Sorter(codecList, incoming) {
            if (!incoming) return new Promise(function (resolve) {
                for (var i = 0; i < codecList.sections.length; i++) {
                    if (codecList.sections[i].kind.toLowerCase() == "video") {
                        codecList.sections[i].codec.sort(function (a, b) {
                            if (a.toLowerCase().indexOf("h264") != -1 && a.toLowerCase().indexOf("uc") == -1) return -1;
                            if (b.toLowerCase().indexOf("h264") != -1 && b.toLowerCase().indexOf("uc") == -1) return 1;
                            return 0;
                        });
                    }
                }
                resolve(codecList);
            });else return new Promise(function (resolve) {
                for (var i = 0; i < codecList.sections.length; i++) {
                    if (codecList.sections[i].kind.toLowerCase() == "video") {
                        var codecCandidate = codecList.sections[i].codec.filter(function (item) {
                            return item.toLowerCase().indexOf("h264") != -1 && item.toLowerCase().indexOf("uc") == -1;
                        });
                        if (codecCandidate.length) codecList.sections[i].codec = codecCandidate;
                    }
                }
                resolve(codecList);
            });
        }
    }, {
        key: "VP8Sorter",
        value: function VP8Sorter(codecList, incoming) {
            return new Promise(function (resolve, reject) {
                for (var i = 0; i < codecList.sections.length; i++) {
                    if (codecList.sections[i].kind.toLowerCase() == "video") {
                        codecList.sections[i].codec.sort(function (a, b) {
                            if (a.toLowerCase().indexOf("vp8") != -1) return -1;
                            if (b.toLowerCase().indexOf("vp8") != -1) return 1;
                            return 0;
                        });
                    }
                }
                resolve(codecList);
            });
        }
    }]);

    return CodecSorterHelpers;
}();

__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], CodecSorterHelpers, "H264Sorter", null);
__decorate([Logger_1.LogManager.d_trace(Logger_1.LogCategory.RTC)], CodecSorterHelpers, "VP8Sorter", null);
exports.CodecSorterHelpers = CodecSorterHelpers;

/***/ }),
/* 51 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * @hidden
 */

var CodecSorter = function () {
    function CodecSorter(sdp) {
        _classCallCheck(this, CodecSorter);

        this.originalSDP = sdp;
    }

    _createClass(CodecSorter, [{
        key: "getCodecList",

        /**
         * Parcing source sdp to inner codec list
         *
         * @returns {CodecSorterCodecList}
         */
        value: function getCodecList() {
            this.originalCodecList = {
                prefix: '',
                sections: [],
                sufix: ''
            };
            var validLine = RegExp.prototype.test.bind(/^([a-z])=(.*)/);
            var sections = CodecSorter.splitSections(this.originalSDP);
            this.originalCodecList.prefix = sections[0];
            for (var i = 1; i < sections.length; i++) {
                var mediaCodec = {
                    kind: 'audio',
                    firstLine: '',
                    prefix: '',
                    sufix: '',
                    codec: []
                };
                var preparced = sections[i].split('\na=rtpmap');
                preparced = preparced.map(function (part, index) {
                    return (index > 0 ? 'a=rtpmap' + part : part).trim() + '\r\n';
                });
                mediaCodec.prefix = preparced.shift();
                var tempsufix = preparced.pop();
                tempsufix = tempsufix.split(/(\r\n|\r|\n)/).filter(validLine);
                var needparse = true;
                preparced.push('');
                while (needparse) {
                    needparse = false;
                    if (tempsufix.length !== 0) {
                        var el = tempsufix.shift();
                        if (el.indexOf('a=rtpmap') === 0 || el.indexOf('a=rtcp-fb') === 0 || el.indexOf('a=fmtp') === 0 || el.indexOf('a=x-caps') === 0 || el.indexOf('a=maxptime') === 0) {
                            preparced[preparced.length - 1] += el + '\r\n';
                            needparse = true;
                        } else tempsufix.unshift(el);
                    }
                }
                for (var j = 0; j < preparced.length; j++) {
                    mediaCodec.codec.push(preparced[j].split(/(\r\n|\r|\n)/).filter(validLine));
                }
                var parsedPrefix = mediaCodec.prefix.split(/(\r\n|\r|\n)/).filter(validLine);
                mediaCodec.firstLine = parsedPrefix.shift();
                var firstLineSplited = mediaCodec.firstLine.split(' ');
                firstLineSplited.splice(-1 * mediaCodec.codec.length, mediaCodec.codec.length);
                mediaCodec.kind = firstLineSplited[0].substring(2);
                mediaCodec.prefix = parsedPrefix.join('\r\n') + '\r\n';
                mediaCodec.firstLine = firstLineSplited.join(' ');
                if (tempsufix.length > 0) mediaCodec.sufix = tempsufix.join('\r\n') + '\r\n';
                this.originalCodecList.sections.push(mediaCodec);
            }
            return this.originalCodecList;
        }
        /**
         * Return user readable list of sections with list of codec inside
         *
         * @returns {CodecSorterUserCodecList}
         */

    }, {
        key: "getUserCodecList",
        value: function getUserCodecList() {
            if (typeof this.originalCodecList === 'undefined') this.getCodecList();
            var userChL = {
                sections: []
            };
            userChL.sections = this.originalCodecList.sections.filter(function (value) {
                return value.kind === 'video' || value.kind === 'audio';
            }).map(function (currentValue, index, array) {
                var list = {
                    kind: currentValue.kind,
                    codec: currentValue.codec.map(function (item) {
                        return CodecSorter.codecToUserCodec(item);
                    })
                };
                var resultArr = [];
                var _iteratorNormalCompletion = true;
                var _didIteratorError = false;
                var _iteratorError = undefined;

                try {
                    for (var _iterator = list.codec[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                        var item = _step.value;

                        if (resultArr.indexOf(item) === -1) resultArr.push(item);
                    }
                } catch (err) {
                    _didIteratorError = true;
                    _iteratorError = err;
                } finally {
                    try {
                        if (!_iteratorNormalCompletion && _iterator.return) {
                            _iterator.return();
                        }
                    } finally {
                        if (_didIteratorError) {
                            throw _iteratorError;
                        }
                    }
                }

                list.codec = resultArr;
                return list;
            });
            return userChL;
        }
    }, {
        key: "setUserCodecList",
        value: function setUserCodecList(userCL) {
            if (typeof this.originalCodecList === 'undefined') this.getCodecList();
            for (var i = 0; i < userCL.sections.length; i++) {
                if (userCL.sections[i].kind === this.originalCodecList.sections[i].kind) {
                    this.originalCodecList.sections[i].codec = CodecSorter.resortSection(userCL.sections[i].codec, this.originalCodecList.sections[i].codec);
                }
            }
        }
    }, {
        key: "getSDP",
        value: function getSDP() {
            var resultSDP = this.originalCodecList.prefix;
            for (var i = 0; i < this.originalCodecList.sections.length; i++) {
                var codecPart = '';
                var codecOrder = [];
                for (var j = 0; j < this.originalCodecList.sections[i].codec.length; j++) {
                    codecOrder.push(this.originalCodecList.sections[i].codec[j][0].split(' ')[0].substring(9));
                    codecPart += this.originalCodecList.sections[i].codec[j].join('\r\n') + '\r\n';
                }
                resultSDP += this.originalCodecList.sections[i].firstLine + ' ' + codecOrder.join(' ') + '\r\n';
                resultSDP += this.originalCodecList.sections[i].prefix;
                resultSDP += codecPart;
                resultSDP += this.originalCodecList.sections[i].sufix;
            }
            return resultSDP;
        }
    }, {
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'CodecSorter';
        }
    }], [{
        key: "splitSections",
        value: function splitSections(blob) {
            var parts = blob.split('\nm=');
            return parts.map(function (part, index) {
                return (index > 0 ? 'm=' + part : part).trim() + '\r\n';
            });
        }
    }, {
        key: "codecToUserCodec",
        value: function codecToUserCodec(item) {
            var splited = item[0].split(' ');
            splited.shift();
            return splited.join(' ');
        }
    }, {
        key: "resortSection",
        value: function resortSection(userCodec, originalCodec) {
            var newCodecs = [];
            for (var i = 0; i < userCodec.length; i++) {
                for (var j = 0; j < originalCodec.length; j++) {
                    if (userCodec[i] === CodecSorter.codecToUserCodec(originalCodec[j])) {
                        newCodecs.push(originalCodec[j]);
                    }
                }
            }
            return newCodecs;
        }
    }, {
        key: "downOpusBandwidth",
        value: function downOpusBandwidth(sdp) {
            return new Promise(function (resolve, reject) {
                var validLine = RegExp.prototype.test.bind(/^([a-z])=(.*)/);
                var sdpLines = sdp.sdp.split(/(\r\n|\r|\n)/).filter(validLine);
                var changed = false;
                for (var i = 0; i < sdpLines.length; i++) {
                    if (sdpLines[i].indexOf('a=fmtp:114') !== -1) {
                        sdpLines[i] = 'a=fmtp:114 minptime=10; useinbandfec=1; sprop-maxcapturerate=8000';
                        changed = true;
                    }
                    if (sdpLines[i].indexOf('a=fmtp:111') !== -1) {
                        sdpLines[i] = 'a=fmtp:111 minptime=10; useinbandfec=1; sprop-maxcapturerate=8000';
                        changed = true;
                    }
                }
                if (!changed) {
                    reject(sdp);
                }
                resolve(new RTCSessionDescription({ sdp: sdpLines.join('\r\n') + '\r\n', type: sdp.type }));
            });
        }
    }]);

    return CodecSorter;
}();

exports.CodecSorter = CodecSorter;

/***/ }),
/* 52 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Edge specific implementation
 * @hidden
 */

var Edge = function () {
    function Edge() {
        _classCallCheck(this, Edge);
    }

    _createClass(Edge, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'Edge';
        }
    }], [{
        key: "attachStream",
        value: function attachStream(stream, element) {
            element.srcObject = stream;
            element.play();
        }
    }, {
        key: "detachStream",
        value: function detachStream(element) {
            element.pause();
            element.src = "";
        }
    }, {
        key: "getScreenMedia",
        value: function getScreenMedia() {
            if (navigator.getDisplayMedia) {
                return navigator.getDisplayMedia();
            }
            return new Promise(function (resolve, reject) {
                reject(new Error('Screen sharing not allowed for you platform'));
            });
        }
    }, {
        key: "getRTCStats",
        value: function getRTCStats(pc) {
            return new Promise(function (resolve, reject) {
                reject(new Error('RTCStats sharing not allowed for you platform'));
            });
        }
    }, {
        key: "screenSharingSupported",
        value: function screenSharingSupported() {
            return new Promise(function (resolve, reject) {
                if (navigator.getDisplayMedia) {
                    resolve(true);
                    return;
                }
                resolve(false);
            });
        }
    }]);

    return Edge;
}();

exports.Edge = Edge;

/***/ }),
/* 53 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var SignalingDTMFSender_1 = __webpack_require__(19);
/**
 * @hidden
 */

var Safari = function () {
    function Safari() {
        _classCallCheck(this, Safari);
    }

    _createClass(Safari, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'Safari';
        }
    }], [{
        key: "attachStream",
        value: function attachStream(stream, element) {
            element.srcObject = stream;
        }
    }, {
        key: "detachStream",
        value: function detachStream(element) {
            if (element instanceof HTMLVideoElement) {
                var promice = element.pause();
                if (typeof promice != "undefined") promice.catch(function (e) {});
            } else element.pause();
            element.src = "";
        }
    }, {
        key: "getDTMFSender",
        value: function getDTMFSender(pc, callId) {
            if (!!pc.createDTMFSender) return pc.createDTMFSender(pc.getLocalStreams()[0].getAudioTracks()[0]);else return new SignalingDTMFSender_1.SignalingDTMFSender(callId);
        }
    }, {
        key: "getScreenMedia",
        value: function getScreenMedia() {
            return new Promise(function (resolve, reject) {
                window.postMessage('get-sourceId', '*');
                window.addEventListener('message', function (event) {
                    if (event.origin == window.location.origin) {
                        if (event.data == 'PermissionDeniedError') {
                            reject(new Error('PermissionDeniedError'));
                        }
                        if (typeof event.data != 'string' && typeof event.data.sourceId != "undefined") {
                            var mediaParams = {
                                audio: false,
                                video: {
                                    mandatory: {
                                        chromeMediaSource: 'desktop',
                                        maxWidth: screen.width > 1920 ? screen.width : 1920,
                                        maxHeight: screen.height > 1080 ? screen.height : 1080,
                                        chromeMediaSourceId: event.data.sourceId
                                        // minAspectRatio: 1.77
                                    },
                                    optional: [{
                                        googTemporalLayeredScreencast: true
                                    }]
                                }
                            };
                            navigator.mediaDevices.getUserMedia(mediaParams).then(function (stream) {
                                resolve(stream);
                            }).catch(function (e) {
                                reject(e);
                            });
                        }
                    }
                });
            });
        }
    }, {
        key: "getRTCStats",
        value: function getRTCStats(pc) {
            return new Promise(function (resolve, reject) {
                var resultArray = [];
                pc.getStats(null).then(function (e) {
                    e.forEach(function (result) {
                        if (result.type == "ssrc") {
                            var item = {};
                            item.id = result.id;
                            item.type = result.type;
                            item.timestamp = result.timestamp;
                            resultArray.push(item);
                        }
                    });
                    resolve(resultArray);
                }).catch(reject);
            });
        }
    }]);

    return Safari;
}();

exports.Safari = Safari;

/***/ }),
/* 54 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var Events_1 = __webpack_require__(18);
var CallManager_1 = __webpack_require__(5);
var VoxSignaling_1 = __webpack_require__(2);
var RemoteFunction_1 = __webpack_require__(3);
var RemoteEvent_1 = __webpack_require__(12);
var Authenticator_1 = __webpack_require__(10);
var CallEvents_1 = __webpack_require__(6);
var Hardware_1 = __webpack_require__(4);
/**
 * @hidden
 */

var ZingayaAPI = function () {
  /**
   * @hidden
   * @param client
   */
  /**
   * @hidden
   * @param client
   */
  function ZingayaAPI(client) {
    var _this = this;

    _classCallCheck(this, ZingayaAPI);

    this.client = client;
    /**
     * @hidden
     */
    this.currentCall = null;
    /**
     * @hidden
     */
    this.onConnectionFailed = null;
    /**
     * @hidden
     */
    this.onConnectionEstablished = null;
    /**
     * @hidden
     */
    this.onCheckComplete = null;
    /**
     * @hidden
     */
    this.onCallFailed = null;
    /**
     * @hidden
     */
    this.onCallConnected = null;
    /**
     * @hidden
     */
    this.onCallEnded = null;
    /**
     * @hidden
     */
    this.onCallRinging = null;
    /**
     * @hidden
     */
    this.onCallMediaStarted = null;
    /**
     * @hidden
     */
    this.onVoicemail = null;
    /**
     * @hidden
     */
    this.onNetStatsReceived = null;
    //console.log(`[ZA] constructor`);
    CallManager_1.CallManager.get().protocolVersion == "2";
    client.on(Events_1.Events.ConnectionFailed, function (event) {
      return _this.runLegacyCallback(_this.onConnectionFailed, event);
    });
    client.on(Events_1.Events.ConnectionEstablished, function (event) {
      return _this.runLegacyCallback(_this.onConnectionEstablished, event);
    });
    VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.handlePreFlightCheckResult, function (a, b, c) {
      return _this.onCheckComplete(a, b, c);
    });
    VoxSignaling_1.VoxSignaling.get().setRPCHandler(RemoteEvent_1.RemoteEvent.handleVoicemail, function (event) {
      return _this.runLegacyCallback(_this.onVoicemail, event);
    });
  }
  /**
   * @hidden
   * @param serverAddress
   * @param referrer
   * @param extra
   * @param appName
   */


  _createClass(ZingayaAPI, [{
    key: "connectTo",
    value: function connectTo(serverAddress, referrer, extra, appName) {
      //console.log(`[ZA] connectTo(${serverAddress},${referrer},${extra},${appName}`);
      var signaling = VoxSignaling_1.VoxSignaling.get();
      Authenticator_1.Authenticator.get().ziAuthorized(true);
      signaling.lagacyConnectTo(serverAddress, referrer, extra, appName);
    }
    /**
     * @hidden
     */

  }, {
    key: "connect",
    value: function connect() {
      //console.log(`[ZA] connect`);
    }
  }, {
    key: "requestMedia",

    /**
     * @hidden
     * @param video
     * @param onMediaAccessGranted
     * @param onMediaAccessRejected
     * @param stopStream
     */
    value: function requestMedia(video, onMediaAccessGranted, onMediaAccessRejected, stopStream) {
      Hardware_1.default.StreamManager.get().getCallStream(void 0).then(function (ev) {
        if (onMediaAccessGranted) onMediaAccessGranted(ev);
      }).catch(function (e) {
        if (onMediaAccessRejected) onMediaAccessRejected(e);
      });
    }
  }, {
    key: "hangupCall",

    /**
     * @hidden
     * @param callId
     * @param headers
     */
    value: function hangupCall(callId, headers) {
      //console.log(`[ZA] hangupCall(${callId},${JSON.stringify(headers)})`);
      CallManager_1.CallManager.get().calls[callId].hangup(headers);
      VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.disconnectCall, callId, {});
    }
  }, {
    key: "callTo",

    /**
     * @hidden
     * @param destination
     * @param useVideo
     * @param headers
     * @param extraParams
     */
    value: function callTo(destination, useVideo, headers, extraParams) {
      //console.log(`[ZA] callTo(${destination},${useVideo},${JSON.stringify(headers)},${JSON.stringify(extraParams)})`);
      this.currentCall = this.client.call({
        number: destination,
        video: useVideo,
        extraHeaders: headers,
        extraParams: extraParams
      });
      this.bindCurrentCall();
      return this.currentCall.id();
    }
  }, {
    key: "voicemailPromptFinished",

    /**
     * @hidden
     * @param callId
     */
    value: function voicemailPromptFinished(callId) {
      //console.log(`[ZA] voicemailPromptFinished(${callId})`);
      VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.zPromptFinished, callId);
    }
  }, {
    key: "makeid",

    /**
     * @hidden
     * @param len
     */
    value: function makeid(len) {
      //console.log(`[ZA] makeid(${len})`);
      var text = "";
      var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
      for (var i = 0; i < len; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
      }return text;
    }
  }, {
    key: "muteMicrophone",

    /**
     * @hidden
     * @param doMute
     */
    value: function muteMicrophone(doMute) {
      //console.log(`[ZA] muteMicrophone(${doMute})`);
      var cm = CallManager_1.CallManager.get();
      for (var call in cm.calls) {
        if (cm.calls.hasOwnProperty(call)) {
          if (doMute) cm.calls[call].muteMicrophone();else cm.calls[call].unmuteMicrophone();
        }
      }
    }
  }, {
    key: "sendDigit",

    /**
     * @hidden
     * @param callId
     * @param digit
     */
    value: function sendDigit(callId, digit) {
      //console.log(`[ZA] sendDigit(${callId},${digit})`);
      VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.sendDTMF, callId, digit);
    }
  }, {
    key: "startPreFlightCheck",

    /**
     * @hidden
     * @param mic
     * @param net
     */
    value: function startPreFlightCheck(mic, net) {
      //console.log(`[ZA] startPreFlightCheck(${mic},${net})`);
      if (this.onCheckComplete) this.onCheckComplete(true, true, true);
    }
  }, {
    key: "runLegacyCallback",

    /**
     * @hidden
     * @param callback
     * @param event
     */
    value: function runLegacyCallback(callback, event) {
      //console.log(`[ZA] runLegacyCallback(${event.name})`);
      if (typeof callback !== "undefined" && callback !== null) {
        callback(event);
      }
    }
    /**
     * @hidden
     */

  }, {
    key: "bindCurrentCall",
    value: function bindCurrentCall() {
      var _this2 = this;

      window['currentCall'] = this.currentCall;
      this.currentCall.on(CallEvents_1.CallEvents.Failed, function (event) {
        _this2.runLegacyCallback(_this2.onCallFailed, event);
        _this2.unbindCurrentCall();
      });
      this.currentCall.on(CallEvents_1.CallEvents.Connected, function (event) {
        _this2.runLegacyCallback(_this2.onCallConnected, event);
        _this2.runLegacyCallback(_this2.onCallMediaStarted, event);
        var cm = CallManager_1.CallManager.get();
        setTimeout(function () {
          var renderer = document.getElementById(window['currentCall'].peerConnection.impl.getRemoteStreams()[0].getTracks()[0].id);
          renderer.srcObject = window['currentCall'].peerConnection.impl.getRemoteStreams()[0];
          renderer.load();
          renderer.play();
        }, 1000);
      });
      this.currentCall.on(CallEvents_1.CallEvents.Disconnected, function (event) {
        _this2.runLegacyCallback(_this2.onCallEnded, event);
        _this2.unbindCurrentCall();
      });
      this.client.on(Events_1.Events.NetStatsReceived, function (event) {
        return _this2.onNetStatsReceived(event);
      });
    }
    /**
     * @hidden
     */

  }, {
    key: "unbindCurrentCall",
    value: function unbindCurrentCall() {
      this.currentCall.off(CallEvents_1.CallEvents.Failed);
      this.currentCall.off(CallEvents_1.CallEvents.Connected);
      this.currentCall.off(CallEvents_1.CallEvents.Disconnected);
      this.currentCall.off(CallEvents_1.CallEvents.ProgressToneStart);
      this.currentCall.off(CallEvents_1.CallEvents.Connected);
      this.client.off(Events_1.Events.NetStatsReceived);
    }
    /**
     * @hidden
     * @return {string}
     * @private
     */

  }, {
    key: "_traceName",
    value: function _traceName() {
      return 'ZingayaAPI';
    }
  }]);

  return ZingayaAPI;
}();

exports.ZingayaAPI = ZingayaAPI;

/***/ }),
/* 55 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var VoxSignaling_1 = __webpack_require__(2);
var RemoteFunction_1 = __webpack_require__(3);
/**
 * @hidden
 */

var PushService = function () {
    function PushService() {
        _classCallCheck(this, PushService);
    }

    _createClass(PushService, [{
        key: "_traceName",

        /**
         * @hidden
         * @return {string}
         * @private
         */
        value: function _traceName() {
            return 'PushService';
        }
    }], [{
        key: "register",
        value: function register(token) {
            return new Promise(function (resolve, reject) {
                var sendResult = VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.registerPushToken, token);
                if (sendResult) resolve();else reject();
            });
        }
    }, {
        key: "unregister",
        value: function unregister(token) {
            return new Promise(function (resolve, reject) {
                var sendResult = VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.unregisterPushToken, token);
                if (sendResult) resolve();else reject();
            });
        }
    }, {
        key: "incomingPush",
        value: function incomingPush(data) {
            return new Promise(function (resolve, reject) {
                var sendResult = VoxSignaling_1.VoxSignaling.get().callRemoteFunction(RemoteFunction_1.RemoteFunction.pushFeedback, data);
                if (sendResult) resolve();else reject();
            });
        }
    }]);

    return PushService;
}();

exports.PushService = PushService;

/***/ }),
/* 56 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */



var adapterFactory = __webpack_require__(58);
module.exports = adapterFactory({window: global.window});

/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(57)))

/***/ }),
/* 57 */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || Function("return this")() || (1,eval)("this");
} catch(e) {
	// This works if the window reference is available
	if(typeof window === "object")
		g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),
/* 58 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */



var utils = __webpack_require__(7);
// Shimming starts here.
module.exports = function(dependencies, opts) {
  var window = dependencies && dependencies.window;

  var options = {
    shimChrome: true,
    shimFirefox: true,
    shimEdge: true,
    shimSafari: true,
  };

  for (var key in opts) {
    if (hasOwnProperty.call(opts, key)) {
      options[key] = opts[key];
    }
  }

  // Utils.
  var logging = utils.log;
  var browserDetails = utils.detectBrowser(window);

  // Export to the adapter global object visible in the browser.
  var adapter = {
    browserDetails: browserDetails,
    extractVersion: utils.extractVersion,
    disableLog: utils.disableLog,
    disableWarnings: utils.disableWarnings
  };

  // Uncomment the line below if you want logging to occur, including logging
  // for the switch statement below. Can also be turned on in the browser via
  // adapter.disableLog(false), but then logging from the switch statement below
  // will not appear.
  // require('./utils').disableLog(false);

  // Browser shims.
  var chromeShim = __webpack_require__(59) || null;
  var edgeShim = __webpack_require__(61) || null;
  var firefoxShim = __webpack_require__(64) || null;
  var safariShim = __webpack_require__(66) || null;
  var commonShim = __webpack_require__(67) || null;

  // Shim browser if found.
  switch (browserDetails.browser) {
    case 'chrome':
      if (!chromeShim || !chromeShim.shimPeerConnection ||
          !options.shimChrome) {
        logging('Chrome shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming chrome.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = chromeShim;
      //commonShim.shimCreateObjectURL(window);

      chromeShim.shimGetUserMedia(window);
      chromeShim.shimMediaStream(window);
      chromeShim.shimSourceObject(window);
      chromeShim.shimPeerConnection(window);
      chromeShim.shimOnTrack(window);
      chromeShim.shimAddTrackRemoveTrack(window);
      chromeShim.shimGetSendersWithDtmf(window);

      commonShim.shimRTCIceCandidate(window);
      break;
    case 'firefox':
      if (!firefoxShim || !firefoxShim.shimPeerConnection ||
          !options.shimFirefox) {
        logging('Firefox shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming firefox.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = firefoxShim;
      commonShim.shimCreateObjectURL(window);

      firefoxShim.shimGetUserMedia(window);
      firefoxShim.shimSourceObject(window);
      firefoxShim.shimPeerConnection(window);
      firefoxShim.shimOnTrack(window);
      firefoxShim.shimRemoveStream(window);

      commonShim.shimRTCIceCandidate(window);
      break;
    case 'edge':
      if (!edgeShim || !edgeShim.shimPeerConnection || !options.shimEdge) {
        logging('MS edge shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming edge.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = edgeShim;
      commonShim.shimCreateObjectURL(window);

      edgeShim.shimGetUserMedia(window);
      edgeShim.shimPeerConnection(window);
      edgeShim.shimReplaceTrack(window);

      // the edge shim implements the full RTCIceCandidate object.
      break;
    case 'safari':
      if (!safariShim || !options.shimSafari) {
        logging('Safari shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming safari.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = safariShim;
      commonShim.shimCreateObjectURL(window);

      safariShim.shimRTCIceServerUrls(window);
      safariShim.shimCallbacksAPI(window);
      safariShim.shimLocalStreamsAPI(window);
      safariShim.shimRemoteStreamsAPI(window);
      safariShim.shimTrackEventTransceiver(window);
      safariShim.shimGetUserMedia(window);
      safariShim.shimCreateOfferLegacy(window);

      commonShim.shimRTCIceCandidate(window);
      break;
    default:
      logging('Unsupported browser!');
      break;
  }

  return adapter;
};


/***/ }),
/* 59 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */

var utils = __webpack_require__(7);
var logging = utils.log;

var chromeShim = {
  shimMediaStream: function(window) {
    window.MediaStream = window.MediaStream || window.webkitMediaStream;
  },

  shimOnTrack: function(window) {
    if (typeof window === 'object' && window.RTCPeerConnection && !('ontrack' in
        window.RTCPeerConnection.prototype)) {
      Object.defineProperty(window.RTCPeerConnection.prototype, 'ontrack', {
        get: function() {
          return this._ontrack;
        },
        set: function(f) {
          if (this._ontrack) {
            this.removeEventListener('track', this._ontrack);
          }
          this.addEventListener('track', this._ontrack = f);
        }
      });
      var origSetRemoteDescription =
          window.RTCPeerConnection.prototype.setRemoteDescription;
      window.RTCPeerConnection.prototype.setRemoteDescription = function() {
        var pc = this;
        if (!pc._ontrackpoly) {
          pc._ontrackpoly = function(e) {
            // onaddstream does not fire when a track is added to an existing
            // stream. But stream.onaddtrack is implemented so we use that.
            e.stream.addEventListener('addtrack', function(te) {
              var receiver;
              if (window.RTCPeerConnection.prototype.getReceivers) {
                receiver = pc.getReceivers().find(function(r) {
                  return r.track && r.track.id === te.track.id;
                });
              } else {
                receiver = {track: te.track};
              }

              var event = new Event('track');
              event.track = te.track;
              event.receiver = receiver;
              event.transceiver = {receiver: receiver};
              event.streams = [e.stream];
              pc.dispatchEvent(event);
            });
            e.stream.getTracks().forEach(function(track) {
              var receiver;
              if (window.RTCPeerConnection.prototype.getReceivers) {
                receiver = pc.getReceivers().find(function(r) {
                  return r.track && r.track.id === track.id;
                });
              } else {
                receiver = {track: track};
              }
              var event = new Event('track');
              event.track = track;
              event.receiver = receiver;
              event.transceiver = {receiver: receiver};
              event.streams = [e.stream];
              pc.dispatchEvent(event);
            });
          };
          pc.addEventListener('addstream', pc._ontrackpoly);
        }
        return origSetRemoteDescription.apply(pc, arguments);
      };
    }
  },

  shimGetSendersWithDtmf: function(window) {
    // Overrides addTrack/removeTrack, depends on shimAddTrackRemoveTrack.
    if (typeof window === 'object' && window.RTCPeerConnection &&
        !('getSenders' in window.RTCPeerConnection.prototype) &&
        'createDTMFSender' in window.RTCPeerConnection.prototype) {
      var shimSenderWithDtmf = function(pc, track) {
        return {
          track: track,
          get dtmf() {
            if (this._dtmf === undefined) {
              if (track.kind === 'audio') {
                this._dtmf = pc.createDTMFSender(track);
              } else {
                this._dtmf = null;
              }
            }
            return this._dtmf;
          },
          _pc: pc
        };
      };

      // augment addTrack when getSenders is not available.
      if (!window.RTCPeerConnection.prototype.getSenders) {
        window.RTCPeerConnection.prototype.getSenders = function() {
          this._senders = this._senders || [];
          return this._senders.slice(); // return a copy of the internal state.
        };
        var origAddTrack = window.RTCPeerConnection.prototype.addTrack;
        window.RTCPeerConnection.prototype.addTrack = function(track, stream) {
          var pc = this;
          var sender = origAddTrack.apply(pc, arguments);
          if (!sender) {
            sender = shimSenderWithDtmf(pc, track);
            pc._senders.push(sender);
          }
          return sender;
        };

        var origRemoveTrack = window.RTCPeerConnection.prototype.removeTrack;
        window.RTCPeerConnection.prototype.removeTrack = function(sender) {
          var pc = this;
          origRemoveTrack.apply(pc, arguments);
          var idx = pc._senders.indexOf(sender);
          if (idx !== -1) {
            pc._senders.splice(idx, 1);
          }
        };
      }
      var origAddStream = window.RTCPeerConnection.prototype.addStream;
      window.RTCPeerConnection.prototype.addStream = function(stream) {
        var pc = this;
        pc._senders = pc._senders || [];
        origAddStream.apply(pc, [stream]);
        stream.getTracks().forEach(function(track) {
          pc._senders.push(shimSenderWithDtmf(pc, track));
        });
      };

      var origRemoveStream = window.RTCPeerConnection.prototype.removeStream;
      window.RTCPeerConnection.prototype.removeStream = function(stream) {
        var pc = this;
        pc._senders = pc._senders || [];
        origRemoveStream.apply(pc, [stream]);

        stream.getTracks().forEach(function(track) {
          var sender = pc._senders.find(function(s) {
            return s.track === track;
          });
          if (sender) {
            pc._senders.splice(pc._senders.indexOf(sender), 1); // remove sender
          }
        });
      };
    } else if (typeof window === 'object' && window.RTCPeerConnection &&
               'getSenders' in window.RTCPeerConnection.prototype &&
               'createDTMFSender' in window.RTCPeerConnection.prototype &&
               window.RTCRtpSender &&
               !('dtmf' in window.RTCRtpSender.prototype)) {
      var origGetSenders = window.RTCPeerConnection.prototype.getSenders;
      window.RTCPeerConnection.prototype.getSenders = function() {
        var pc = this;
        var senders = origGetSenders.apply(pc, []);
        senders.forEach(function(sender) {
          sender._pc = pc;
        });
        return senders;
      };

      Object.defineProperty(window.RTCRtpSender.prototype, 'dtmf', {
        get: function() {
          if (this._dtmf === undefined) {
            if (this.track.kind === 'audio') {
              this._dtmf = this._pc.createDTMFSender(this.track);
            } else {
              this._dtmf = null;
            }
          }
          return this._dtmf;
        }
      });
    }
  },

  shimSourceObject: function(window) {
    var URL = window && window.URL;

    if (typeof window === 'object') {
      if (window.HTMLMediaElement &&
        !('srcObject' in window.HTMLMediaElement.prototype)) {
        // Shim the srcObject property, once, when HTMLMediaElement is found.
        Object.defineProperty(window.HTMLMediaElement.prototype, 'srcObject', {
          get: function() {
            return this._srcObject;
          },
          set: function(stream) {
            var self = this;
            // Use _srcObject as a private property for this shim
            this._srcObject = stream;
            if (this.src) {
              URL.revokeObjectURL(this.src);
            }

            if (!stream) {
              this.src = '';
              return undefined;
            }
            this.src = URL.createObjectURL(stream);
            // We need to recreate the blob url when a track is added or
            // removed. Doing it manually since we want to avoid a recursion.
            stream.addEventListener('addtrack', function() {
              if (self.src) {
                URL.revokeObjectURL(self.src);
              }
              self.src = URL.createObjectURL(stream);
            });
            stream.addEventListener('removetrack', function() {
              if (self.src) {
                URL.revokeObjectURL(self.src);
              }
              self.src = URL.createObjectURL(stream);
            });
          }
        });
      }
    }
  },

  shimAddTrackRemoveTrack: function(window) {
    var browserDetails = utils.detectBrowser(window);
    // shim addTrack and removeTrack.
    if (window.RTCPeerConnection.prototype.addTrack &&
        browserDetails.version >= 64) {
      return;
    }

    // also shim pc.getLocalStreams when addTrack is shimmed
    // to return the original streams.
    var origGetLocalStreams = window.RTCPeerConnection.prototype
        .getLocalStreams;
    window.RTCPeerConnection.prototype.getLocalStreams = function() {
      var self = this;
      var nativeStreams = origGetLocalStreams.apply(this);
      self._reverseStreams = self._reverseStreams || {};
      return nativeStreams.map(function(stream) {
        return self._reverseStreams[stream.id];
      });
    };

    var origAddStream = window.RTCPeerConnection.prototype.addStream;
    window.RTCPeerConnection.prototype.addStream = function(stream) {
      var pc = this;
      pc._streams = pc._streams || {};
      pc._reverseStreams = pc._reverseStreams || {};

      stream.getTracks().forEach(function(track) {
        var alreadyExists = pc.getSenders().find(function(s) {
          return s.track === track;
        });
        if (alreadyExists) {
          throw new DOMException('Track already exists.',
              'InvalidAccessError');
        }
      });
      // Add identity mapping for consistency with addTrack.
      // Unless this is being used with a stream from addTrack.
      if (!pc._reverseStreams[stream.id]) {
        var newStream = new window.MediaStream(stream.getTracks());
        pc._streams[stream.id] = newStream;
        pc._reverseStreams[newStream.id] = stream;
        stream = newStream;
      }
      origAddStream.apply(pc, [stream]);
    };

    var origRemoveStream = window.RTCPeerConnection.prototype.removeStream;
    window.RTCPeerConnection.prototype.removeStream = function(stream) {
      var pc = this;
      pc._streams = pc._streams || {};
      pc._reverseStreams = pc._reverseStreams || {};

      origRemoveStream.apply(pc, [(pc._streams[stream.id] || stream)]);
      delete pc._reverseStreams[(pc._streams[stream.id] ?
          pc._streams[stream.id].id : stream.id)];
      delete pc._streams[stream.id];
    };

    window.RTCPeerConnection.prototype.addTrack = function(track, stream) {
      var pc = this;
      if (pc.signalingState === 'closed') {
        throw new DOMException(
          'The RTCPeerConnection\'s signalingState is \'closed\'.',
          'InvalidStateError');
      }
      var streams = [].slice.call(arguments, 1);
      if (streams.length !== 1 ||
          !streams[0].getTracks().find(function(t) {
            return t === track;
          })) {
        // this is not fully correct but all we can manage without
        // [[associated MediaStreams]] internal slot.
        throw new DOMException(
          'The adapter.js addTrack polyfill only supports a single ' +
          ' stream which is associated with the specified track.',
          'NotSupportedError');
      }

      var alreadyExists = pc.getSenders().find(function(s) {
        return s.track === track;
      });
      if (alreadyExists) {
        throw new DOMException('Track already exists.',
            'InvalidAccessError');
      }

      pc._streams = pc._streams || {};
      pc._reverseStreams = pc._reverseStreams || {};
      var oldStream = pc._streams[stream.id];
      if (oldStream) {
        // this is using odd Chrome behaviour, use with caution:
        // https://bugs.chromium.org/p/webrtc/issues/detail?id=7815
        // Note: we rely on the high-level addTrack/dtmf shim to
        // create the sender with a dtmf sender.
        oldStream.addTrack(track);

        // Trigger ONN async.
        Promise.resolve().then(function() {
          pc.dispatchEvent(new Event('negotiationneeded'));
        });
      } else {
        var newStream = new window.MediaStream([track]);
        pc._streams[stream.id] = newStream;
        pc._reverseStreams[newStream.id] = stream;
        pc.addStream(newStream);
      }
      return pc.getSenders().find(function(s) {
        return s.track === track;
      });
    };

    // replace the internal stream id with the external one and
    // vice versa.
    function replaceInternalStreamId(pc, description) {
      var sdp = description.sdp;
      Object.keys(pc._reverseStreams || []).forEach(function(internalId) {
        var externalStream = pc._reverseStreams[internalId];
        var internalStream = pc._streams[externalStream.id];
        sdp = sdp.replace(new RegExp(internalStream.id, 'g'),
            externalStream.id);
      });
      return new RTCSessionDescription({
        type: description.type,
        sdp: sdp
      });
    }
    function replaceExternalStreamId(pc, description) {
      var sdp = description.sdp;
      Object.keys(pc._reverseStreams || []).forEach(function(internalId) {
        var externalStream = pc._reverseStreams[internalId];
        var internalStream = pc._streams[externalStream.id];
        sdp = sdp.replace(new RegExp(externalStream.id, 'g'),
            internalStream.id);
      });
      return new RTCSessionDescription({
        type: description.type,
        sdp: sdp
      });
    }
    ['createOffer', 'createAnswer'].forEach(function(method) {
      var nativeMethod = window.RTCPeerConnection.prototype[method];
      window.RTCPeerConnection.prototype[method] = function() {
        var pc = this;
        var args = arguments;
        var isLegacyCall = arguments.length &&
            typeof arguments[0] === 'function';
        if (isLegacyCall) {
          return nativeMethod.apply(pc, [
            function(description) {
              var desc = replaceInternalStreamId(pc, description);
              args[0].apply(null, [desc]);
            },
            function(err) {
              if (args[1]) {
                args[1].apply(null, err);
              }
            }, arguments[2]
          ]);
        }
        return nativeMethod.apply(pc, arguments)
        .then(function(description) {
          return replaceInternalStreamId(pc, description);
        });
      };
    });

    var origSetLocalDescription =
        window.RTCPeerConnection.prototype.setLocalDescription;
    window.RTCPeerConnection.prototype.setLocalDescription = function() {
      var pc = this;
      if (!arguments.length || !arguments[0].type) {
        return origSetLocalDescription.apply(pc, arguments);
      }
      arguments[0] = replaceExternalStreamId(pc, arguments[0]);
      return origSetLocalDescription.apply(pc, arguments);
    };

    // TODO: mangle getStats: https://w3c.github.io/webrtc-stats/#dom-rtcmediastreamstats-streamidentifier

    var origLocalDescription = Object.getOwnPropertyDescriptor(
        window.RTCPeerConnection.prototype, 'localDescription');
    Object.defineProperty(window.RTCPeerConnection.prototype,
        'localDescription', {
          get: function() {
            var pc = this;
            var description = origLocalDescription.get.apply(this);
            if (description.type === '') {
              return description;
            }
            return replaceInternalStreamId(pc, description);
          }
        });

    window.RTCPeerConnection.prototype.removeTrack = function(sender) {
      var pc = this;
      if (pc.signalingState === 'closed') {
        throw new DOMException(
          'The RTCPeerConnection\'s signalingState is \'closed\'.',
          'InvalidStateError');
      }
      // We can not yet check for sender instanceof RTCRtpSender
      // since we shim RTPSender. So we check if sender._pc is set.
      if (!sender._pc) {
        throw new DOMException('Argument 1 of RTCPeerConnection.removeTrack ' +
            'does not implement interface RTCRtpSender.', 'TypeError');
      }
      var isLocal = sender._pc === pc;
      if (!isLocal) {
        throw new DOMException('Sender was not created by this connection.',
            'InvalidAccessError');
      }

      // Search for the native stream the senders track belongs to.
      pc._streams = pc._streams || {};
      var stream;
      Object.keys(pc._streams).forEach(function(streamid) {
        var hasTrack = pc._streams[streamid].getTracks().find(function(track) {
          return sender.track === track;
        });
        if (hasTrack) {
          stream = pc._streams[streamid];
        }
      });

      if (stream) {
        if (stream.getTracks().length === 1) {
          // if this is the last track of the stream, remove the stream. This
          // takes care of any shimmed _senders.
          pc.removeStream(pc._reverseStreams[stream.id]);
        } else {
          // relying on the same odd chrome behaviour as above.
          stream.removeTrack(sender.track);
        }
        pc.dispatchEvent(new Event('negotiationneeded'));
      }
    };
  },

  shimPeerConnection: function(window) {
    var browserDetails = utils.detectBrowser(window);

    // The RTCPeerConnection object.
    if (!window.RTCPeerConnection) {
      window.RTCPeerConnection = function(pcConfig, pcConstraints) {
        // Translate iceTransportPolicy to iceTransports,
        // see https://code.google.com/p/webrtc/issues/detail?id=4869
        // this was fixed in M56 along with unprefixing RTCPeerConnection.
        logging('PeerConnection');
        if (pcConfig && pcConfig.iceTransportPolicy) {
          pcConfig.iceTransports = pcConfig.iceTransportPolicy;
        }

        return new window.webkitRTCPeerConnection(pcConfig, pcConstraints);
      };
      window.RTCPeerConnection.prototype =
          window.webkitRTCPeerConnection.prototype;
      // wrap static methods. Currently just generateCertificate.
      if (window.webkitRTCPeerConnection.generateCertificate) {
        Object.defineProperty(window.RTCPeerConnection, 'generateCertificate', {
          get: function() {
            return window.webkitRTCPeerConnection.generateCertificate;
          }
        });
      }
    } else {
      // migrate from non-spec RTCIceServer.url to RTCIceServer.urls
      var OrigPeerConnection = window.RTCPeerConnection;
      window.RTCPeerConnection = function(pcConfig, pcConstraints) {
        if (pcConfig && pcConfig.iceServers) {
          var newIceServers = [];
          for (var i = 0; i < pcConfig.iceServers.length; i++) {
            var server = pcConfig.iceServers[i];
            if (!server.hasOwnProperty('urls') &&
                server.hasOwnProperty('url')) {
              utils.deprecated('RTCIceServer.url', 'RTCIceServer.urls');
              server = JSON.parse(JSON.stringify(server));
              server.urls = server.url;
              newIceServers.push(server);
            } else {
              newIceServers.push(pcConfig.iceServers[i]);
            }
          }
          pcConfig.iceServers = newIceServers;
        }
        return new OrigPeerConnection(pcConfig, pcConstraints);
      };
      window.RTCPeerConnection.prototype = OrigPeerConnection.prototype;
      // wrap static methods. Currently just generateCertificate.
      Object.defineProperty(window.RTCPeerConnection, 'generateCertificate', {
        get: function() {
          return OrigPeerConnection.generateCertificate;
        }
      });
    }

    var origGetStats = window.RTCPeerConnection.prototype.getStats;
    window.RTCPeerConnection.prototype.getStats = function(selector,
        successCallback, errorCallback) {
      var self = this;
      var args = arguments;

      // If selector is a function then we are in the old style stats so just
      // pass back the original getStats format to avoid breaking old users.
      if (arguments.length > 0 && typeof selector === 'function') {
        return origGetStats.apply(this, arguments);
      }

      // When spec-style getStats is supported, return those when called with
      // either no arguments or the selector argument is null.
      if (origGetStats.length === 0 && (arguments.length === 0 ||
          typeof arguments[0] !== 'function')) {
        return origGetStats.apply(this, []);
      }

      var fixChromeStats_ = function(response) {
        var standardReport = {};
        var reports = response.result();
        reports.forEach(function(report) {
          var standardStats = {
            id: report.id,
            timestamp: report.timestamp,
            type: {
              localcandidate: 'local-candidate',
              remotecandidate: 'remote-candidate'
            }[report.type] || report.type
          };
          report.names().forEach(function(name) {
            standardStats[name] = report.stat(name);
          });
          standardReport[standardStats.id] = standardStats;
        });

        return standardReport;
      };

      // shim getStats with maplike support
      var makeMapStats = function(stats) {
        return new Map(Object.keys(stats).map(function(key) {
          return [key, stats[key]];
        }));
      };

      if (arguments.length >= 2) {
        var successCallbackWrapper_ = function(response) {
          args[1](makeMapStats(fixChromeStats_(response)));
        };

        return origGetStats.apply(this, [successCallbackWrapper_,
          arguments[0]]);
      }

      // promise-support
      return new Promise(function(resolve, reject) {
        origGetStats.apply(self, [
          function(response) {
            resolve(makeMapStats(fixChromeStats_(response)));
          }, reject]);
      }).then(successCallback, errorCallback);
    };

    // add promise support -- natively available in Chrome 51
    if (browserDetails.version < 51) {
      ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate']
          .forEach(function(method) {
            var nativeMethod = window.RTCPeerConnection.prototype[method];
            window.RTCPeerConnection.prototype[method] = function() {
              var args = arguments;
              var self = this;
              var promise = new Promise(function(resolve, reject) {
                nativeMethod.apply(self, [args[0], resolve, reject]);
              });
              if (args.length < 2) {
                return promise;
              }
              return promise.then(function() {
                args[1].apply(null, []);
              },
              function(err) {
                if (args.length >= 3) {
                  args[2].apply(null, [err]);
                }
              });
            };
          });
    }

    // promise support for createOffer and createAnswer. Available (without
    // bugs) since M52: crbug/619289
    if (browserDetails.version < 52) {
      ['createOffer', 'createAnswer'].forEach(function(method) {
        var nativeMethod = window.RTCPeerConnection.prototype[method];
        window.RTCPeerConnection.prototype[method] = function() {
          var self = this;
          if (arguments.length < 1 || (arguments.length === 1 &&
              typeof arguments[0] === 'object')) {
            var opts = arguments.length === 1 ? arguments[0] : undefined;
            return new Promise(function(resolve, reject) {
              nativeMethod.apply(self, [resolve, reject, opts]);
            });
          }
          return nativeMethod.apply(this, arguments);
        };
      });
    }

    // shim implicit creation of RTCSessionDescription/RTCIceCandidate
    ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate']
        .forEach(function(method) {
          var nativeMethod = window.RTCPeerConnection.prototype[method];
          window.RTCPeerConnection.prototype[method] = function() {
            arguments[0] = new ((method === 'addIceCandidate') ?
                window.RTCIceCandidate :
                window.RTCSessionDescription)(arguments[0]);
            return nativeMethod.apply(this, arguments);
          };
        });

    // support for addIceCandidate(null or undefined)
    var nativeAddIceCandidate =
        window.RTCPeerConnection.prototype.addIceCandidate;
    window.RTCPeerConnection.prototype.addIceCandidate = function() {
      if (!arguments[0]) {
        if (arguments[1]) {
          arguments[1].apply(null);
        }
        return Promise.resolve();
      }
      return nativeAddIceCandidate.apply(this, arguments);
    };
  }
};


// Expose public methods.
module.exports = {
  shimMediaStream: chromeShim.shimMediaStream,
  shimOnTrack: chromeShim.shimOnTrack,
  shimAddTrackRemoveTrack: chromeShim.shimAddTrackRemoveTrack,
  shimGetSendersWithDtmf: chromeShim.shimGetSendersWithDtmf,
  shimSourceObject: chromeShim.shimSourceObject,
  shimPeerConnection: chromeShim.shimPeerConnection,
  shimGetUserMedia: __webpack_require__(60)
};


/***/ }),
/* 60 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */

var utils = __webpack_require__(7);
var logging = utils.log;

// Expose public methods.
module.exports = function(window) {
  var browserDetails = utils.detectBrowser(window);
  var navigator = window && window.navigator;

  var constraintsToChrome_ = function(c) {
    if (typeof c !== 'object' || c.mandatory || c.optional) {
      return c;
    }
    var cc = {};
    Object.keys(c).forEach(function(key) {
      if (key === 'require' || key === 'advanced' || key === 'mediaSource') {
        return;
      }
      var r = (typeof c[key] === 'object') ? c[key] : {ideal: c[key]};
      if (r.exact !== undefined && typeof r.exact === 'number') {
        r.min = r.max = r.exact;
      }
      var oldname_ = function(prefix, name) {
        if (prefix) {
          return prefix + name.charAt(0).toUpperCase() + name.slice(1);
        }
        return (name === 'deviceId') ? 'sourceId' : name;
      };
      if (r.ideal !== undefined) {
        cc.optional = cc.optional || [];
        var oc = {};
        if (typeof r.ideal === 'number') {
          oc[oldname_('min', key)] = r.ideal;
          cc.optional.push(oc);
          oc = {};
          oc[oldname_('max', key)] = r.ideal;
          cc.optional.push(oc);
        } else {
          oc[oldname_('', key)] = r.ideal;
          cc.optional.push(oc);
        }
      }
      if (r.exact !== undefined && typeof r.exact !== 'number') {
        cc.mandatory = cc.mandatory || {};
        cc.mandatory[oldname_('', key)] = r.exact;
      } else {
        ['min', 'max'].forEach(function(mix) {
          if (r[mix] !== undefined) {
            cc.mandatory = cc.mandatory || {};
            cc.mandatory[oldname_(mix, key)] = r[mix];
          }
        });
      }
    });
    if (c.advanced) {
      cc.optional = (cc.optional || []).concat(c.advanced);
    }
    return cc;
  };

  var shimConstraints_ = function(constraints, func) {
    if (browserDetails.version >= 61) {
      return func(constraints);
    }
    constraints = JSON.parse(JSON.stringify(constraints));
    if (constraints && typeof constraints.audio === 'object') {
      var remap = function(obj, a, b) {
        if (a in obj && !(b in obj)) {
          obj[b] = obj[a];
          delete obj[a];
        }
      };
      constraints = JSON.parse(JSON.stringify(constraints));
      remap(constraints.audio, 'autoGainControl', 'googAutoGainControl');
      remap(constraints.audio, 'noiseSuppression', 'googNoiseSuppression');
      constraints.audio = constraintsToChrome_(constraints.audio);
    }
    if (constraints && typeof constraints.video === 'object') {
      // Shim facingMode for mobile & surface pro.
      var face = constraints.video.facingMode;
      face = face && ((typeof face === 'object') ? face : {ideal: face});
      var getSupportedFacingModeLies = browserDetails.version < 66;

      if ((face && (face.exact === 'user' || face.exact === 'environment' ||
                    face.ideal === 'user' || face.ideal === 'environment')) &&
          !(navigator.mediaDevices.getSupportedConstraints &&
            navigator.mediaDevices.getSupportedConstraints().facingMode &&
            !getSupportedFacingModeLies)) {
        delete constraints.video.facingMode;
        var matches;
        if (face.exact === 'environment' || face.ideal === 'environment') {
          matches = ['back', 'rear'];
        } else if (face.exact === 'user' || face.ideal === 'user') {
          matches = ['front'];
        }
        if (matches) {
          // Look for matches in label, or use last cam for back (typical).
          return navigator.mediaDevices.enumerateDevices()
          .then(function(devices) {
            devices = devices.filter(function(d) {
              return d.kind === 'videoinput';
            });
            var dev = devices.find(function(d) {
              return matches.some(function(match) {
                return d.label.toLowerCase().indexOf(match) !== -1;
              });
            });
            if (!dev && devices.length && matches.indexOf('back') !== -1) {
              dev = devices[devices.length - 1]; // more likely the back cam
            }
            if (dev) {
              constraints.video.deviceId = face.exact ? {exact: dev.deviceId} :
                                                        {ideal: dev.deviceId};
            }
            constraints.video = constraintsToChrome_(constraints.video);
            logging('chrome: ' + JSON.stringify(constraints));
            return func(constraints);
          });
        }
      }
      constraints.video = constraintsToChrome_(constraints.video);
    }
    logging('chrome: ' + JSON.stringify(constraints));
    return func(constraints);
  };

  var shimError_ = function(e) {
    return {
      name: {
        PermissionDeniedError: 'NotAllowedError',
        InvalidStateError: 'NotReadableError',
        DevicesNotFoundError: 'NotFoundError',
        ConstraintNotSatisfiedError: 'OverconstrainedError',
        TrackStartError: 'NotReadableError',
        MediaDeviceFailedDueToShutdown: 'NotReadableError',
        MediaDeviceKillSwitchOn: 'NotReadableError'
      }[e.name] || e.name,
      message: e.message,
      constraint: e.constraintName,
      toString: function() {
        return this.name + (this.message && ': ') + this.message;
      }
    };
  };

  var getUserMedia_ = function(constraints, onSuccess, onError) {
    shimConstraints_(constraints, function(c) {
      navigator.webkitGetUserMedia(c, onSuccess, function(e) {
        if (onError) {
          onError(shimError_(e));
        }
      });
    });
  };

  navigator.getUserMedia = getUserMedia_;

  // Returns the result of getUserMedia as a Promise.
  var getUserMediaPromise_ = function(constraints) {
    return new Promise(function(resolve, reject) {
      navigator.getUserMedia(constraints, resolve, reject);
    });
  };

  if (!navigator.mediaDevices) {
    navigator.mediaDevices = {
      getUserMedia: getUserMediaPromise_,
      enumerateDevices: function() {
        return new Promise(function(resolve) {
          var kinds = {audio: 'audioinput', video: 'videoinput'};
          return window.MediaStreamTrack.getSources(function(devices) {
            resolve(devices.map(function(device) {
              return {label: device.label,
                kind: kinds[device.kind],
                deviceId: device.id,
                groupId: ''};
            }));
          });
        });
      },
      getSupportedConstraints: function() {
        return {
          deviceId: true, echoCancellation: true, facingMode: true,
          frameRate: true, height: true, width: true
        };
      }
    };
  }

  // A shim for getUserMedia method on the mediaDevices object.
  // TODO(KaptenJansson) remove once implemented in Chrome stable.
  if (!navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia = function(constraints) {
      return getUserMediaPromise_(constraints);
    };
  } else {
    // Even though Chrome 45 has navigator.mediaDevices and a getUserMedia
    // function which returns a Promise, it does not accept spec-style
    // constraints.
    var origGetUserMedia = navigator.mediaDevices.getUserMedia.
        bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = function(cs) {
      return shimConstraints_(cs, function(c) {
        return origGetUserMedia(c).then(function(stream) {
          if (c.audio && !stream.getAudioTracks().length ||
              c.video && !stream.getVideoTracks().length) {
            stream.getTracks().forEach(function(track) {
              track.stop();
            });
            throw new DOMException('', 'NotFoundError');
          }
          return stream;
        }, function(e) {
          return Promise.reject(shimError_(e));
        });
      });
    };
  }

  // Dummy devicechange event methods.
  // TODO(KaptenJansson) remove once implemented in Chrome stable.
  if (typeof navigator.mediaDevices.addEventListener === 'undefined') {
    navigator.mediaDevices.addEventListener = function() {
      logging('Dummy mediaDevices.addEventListener called.');
    };
  }
  if (typeof navigator.mediaDevices.removeEventListener === 'undefined') {
    navigator.mediaDevices.removeEventListener = function() {
      logging('Dummy mediaDevices.removeEventListener called.');
    };
  }
};


/***/ }),
/* 61 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var utils = __webpack_require__(7);
var shimRTCPeerConnection = __webpack_require__(62);

module.exports = {
  shimGetUserMedia: __webpack_require__(63),
  shimPeerConnection: function(window) {
    var browserDetails = utils.detectBrowser(window);

    if (window.RTCIceGatherer) {
      // ORTC defines an RTCIceCandidate object but no constructor.
      // Not implemented in Edge.
      if (!window.RTCIceCandidate) {
        window.RTCIceCandidate = function(args) {
          return args;
        };
      }
      // ORTC does not have a session description object but
      // other browsers (i.e. Chrome) that will support both PC and ORTC
      // in the future might have this defined already.
      if (!window.RTCSessionDescription) {
        window.RTCSessionDescription = function(args) {
          return args;
        };
      }
      // this adds an additional event listener to MediaStrackTrack that signals
      // when a tracks enabled property was changed. Workaround for a bug in
      // addStream, see below. No longer required in 15025+
      if (browserDetails.version < 15025) {
        var origMSTEnabled = Object.getOwnPropertyDescriptor(
            window.MediaStreamTrack.prototype, 'enabled');
        Object.defineProperty(window.MediaStreamTrack.prototype, 'enabled', {
          set: function(value) {
            origMSTEnabled.set.call(this, value);
            var ev = new Event('enabled');
            ev.enabled = value;
            this.dispatchEvent(ev);
          }
        });
      }
    }

    // ORTC defines the DTMF sender a bit different.
    // https://github.com/w3c/ortc/issues/714
    if (window.RTCRtpSender && !('dtmf' in window.RTCRtpSender.prototype)) {
      Object.defineProperty(window.RTCRtpSender.prototype, 'dtmf', {
        get: function() {
          if (this._dtmf === undefined) {
            if (this.track.kind === 'audio') {
              this._dtmf = new window.RTCDtmfSender(this);
            } else if (this.track.kind === 'video') {
              this._dtmf = null;
            }
          }
          return this._dtmf;
        }
      });
    }

    window.RTCPeerConnection =
        shimRTCPeerConnection(window, browserDetails.version);
  },
  shimReplaceTrack: function(window) {
    // ORTC has replaceTrack -- https://github.com/w3c/ortc/issues/614
    if (window.RTCRtpSender &&
        !('replaceTrack' in window.RTCRtpSender.prototype)) {
      window.RTCRtpSender.prototype.replaceTrack =
          window.RTCRtpSender.prototype.setTrack;
    }
  }
};


/***/ }),
/* 62 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2017 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var SDPUtils = __webpack_require__(38);

function fixStatsType(stat) {
  return {
    inboundrtp: 'inbound-rtp',
    outboundrtp: 'outbound-rtp',
    candidatepair: 'candidate-pair',
    localcandidate: 'local-candidate',
    remotecandidate: 'remote-candidate'
  }[stat.type] || stat.type;
}

function writeMediaSection(transceiver, caps, type, stream, dtlsRole) {
  var sdp = SDPUtils.writeRtpDescription(transceiver.kind, caps);

  // Map ICE parameters (ufrag, pwd) to SDP.
  sdp += SDPUtils.writeIceParameters(
      transceiver.iceGatherer.getLocalParameters());

  // Map DTLS parameters to SDP.
  sdp += SDPUtils.writeDtlsParameters(
      transceiver.dtlsTransport.getLocalParameters(),
      type === 'offer' ? 'actpass' : dtlsRole || 'active');

  sdp += 'a=mid:' + transceiver.mid + '\r\n';

  if (transceiver.rtpSender && transceiver.rtpReceiver) {
    sdp += 'a=sendrecv\r\n';
  } else if (transceiver.rtpSender) {
    sdp += 'a=sendonly\r\n';
  } else if (transceiver.rtpReceiver) {
    sdp += 'a=recvonly\r\n';
  } else {
    sdp += 'a=inactive\r\n';
  }

  if (transceiver.rtpSender) {
    var trackId = transceiver.rtpSender._initialTrackId ||
        transceiver.rtpSender.track.id;
    transceiver.rtpSender._initialTrackId = trackId;
    // spec.
    var msid = 'msid:' + (stream ? stream.id : '-') + ' ' +
        trackId + '\r\n';
    sdp += 'a=' + msid;
    // for Chrome. Legacy should no longer be required.
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
        ' ' + msid;

    // RTX
    if (transceiver.sendEncodingParameters[0].rtx) {
      sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
          ' ' + msid;
      sdp += 'a=ssrc-group:FID ' +
          transceiver.sendEncodingParameters[0].ssrc + ' ' +
          transceiver.sendEncodingParameters[0].rtx.ssrc +
          '\r\n';
    }
  }
  // FIXME: this should be written by writeRtpDescription.
  sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
      ' cname:' + SDPUtils.localCName + '\r\n';
  if (transceiver.rtpSender && transceiver.sendEncodingParameters[0].rtx) {
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
        ' cname:' + SDPUtils.localCName + '\r\n';
  }
  return sdp;
}

// Edge does not like
// 1) stun: filtered after 14393 unless ?transport=udp is present
// 2) turn: that does not have all of turn:host:port?transport=udp
// 3) turn: with ipv6 addresses
// 4) turn: occurring muliple times
function filterIceServers(iceServers, edgeVersion) {
  var hasTurn = false;
  iceServers = JSON.parse(JSON.stringify(iceServers));
  return iceServers.filter(function(server) {
    if (server && (server.urls || server.url)) {
      var urls = server.urls || server.url;
      if (server.url && !server.urls) {
        console.warn('RTCIceServer.url is deprecated! Use urls instead.');
      }
      var isString = typeof urls === 'string';
      if (isString) {
        urls = [urls];
      }
      urls = urls.filter(function(url) {
        var validTurn = url.indexOf('turn:') === 0 &&
            url.indexOf('transport=udp') !== -1 &&
            url.indexOf('turn:[') === -1 &&
            !hasTurn;

        if (validTurn) {
          hasTurn = true;
          return true;
        }
        return url.indexOf('stun:') === 0 && edgeVersion >= 14393 &&
            url.indexOf('?transport=udp') === -1;
      });

      delete server.url;
      server.urls = isString ? urls[0] : urls;
      return !!urls.length;
    }
  });
}

// Determines the intersection of local and remote capabilities.
function getCommonCapabilities(localCapabilities, remoteCapabilities) {
  var commonCapabilities = {
    codecs: [],
    headerExtensions: [],
    fecMechanisms: []
  };

  var findCodecByPayloadType = function(pt, codecs) {
    pt = parseInt(pt, 10);
    for (var i = 0; i < codecs.length; i++) {
      if (codecs[i].payloadType === pt ||
          codecs[i].preferredPayloadType === pt) {
        return codecs[i];
      }
    }
  };

  var rtxCapabilityMatches = function(lRtx, rRtx, lCodecs, rCodecs) {
    var lCodec = findCodecByPayloadType(lRtx.parameters.apt, lCodecs);
    var rCodec = findCodecByPayloadType(rRtx.parameters.apt, rCodecs);
    return lCodec && rCodec &&
        lCodec.name.toLowerCase() === rCodec.name.toLowerCase();
  };

  localCapabilities.codecs.forEach(function(lCodec) {
    for (var i = 0; i < remoteCapabilities.codecs.length; i++) {
      var rCodec = remoteCapabilities.codecs[i];
      if (lCodec.name.toLowerCase() === rCodec.name.toLowerCase() &&
          lCodec.clockRate === rCodec.clockRate) {
        if (lCodec.name.toLowerCase() === 'rtx' &&
            lCodec.parameters && rCodec.parameters.apt) {
          // for RTX we need to find the local rtx that has a apt
          // which points to the same local codec as the remote one.
          if (!rtxCapabilityMatches(lCodec, rCodec,
              localCapabilities.codecs, remoteCapabilities.codecs)) {
            continue;
          }
        }
        rCodec = JSON.parse(JSON.stringify(rCodec)); // deepcopy
        // number of channels is the highest common number of channels
        rCodec.numChannels = Math.min(lCodec.numChannels,
            rCodec.numChannels);
        // push rCodec so we reply with offerer payload type
        commonCapabilities.codecs.push(rCodec);

        // determine common feedback mechanisms
        rCodec.rtcpFeedback = rCodec.rtcpFeedback.filter(function(fb) {
          for (var j = 0; j < lCodec.rtcpFeedback.length; j++) {
            if (lCodec.rtcpFeedback[j].type === fb.type &&
                lCodec.rtcpFeedback[j].parameter === fb.parameter) {
              return true;
            }
          }
          return false;
        });
        // FIXME: also need to determine .parameters
        //  see https://github.com/openpeer/ortc/issues/569
        break;
      }
    }
  });

  localCapabilities.headerExtensions.forEach(function(lHeaderExtension) {
    for (var i = 0; i < remoteCapabilities.headerExtensions.length;
         i++) {
      var rHeaderExtension = remoteCapabilities.headerExtensions[i];
      if (lHeaderExtension.uri === rHeaderExtension.uri) {
        commonCapabilities.headerExtensions.push(rHeaderExtension);
        break;
      }
    }
  });

  // FIXME: fecMechanisms
  return commonCapabilities;
}

// is action=setLocalDescription with type allowed in signalingState
function isActionAllowedInSignalingState(action, type, signalingState) {
  return {
    offer: {
      setLocalDescription: ['stable', 'have-local-offer'],
      setRemoteDescription: ['stable', 'have-remote-offer']
    },
    answer: {
      setLocalDescription: ['have-remote-offer', 'have-local-pranswer'],
      setRemoteDescription: ['have-local-offer', 'have-remote-pranswer']
    }
  }[type][action].indexOf(signalingState) !== -1;
}

function maybeAddCandidate(iceTransport, candidate) {
  // Edge's internal representation adds some fields therefore
  // not all fieldѕ are taken into account.
  var alreadyAdded = iceTransport.getRemoteCandidates()
      .find(function(remoteCandidate) {
        return candidate.foundation === remoteCandidate.foundation &&
            candidate.ip === remoteCandidate.ip &&
            candidate.port === remoteCandidate.port &&
            candidate.priority === remoteCandidate.priority &&
            candidate.protocol === remoteCandidate.protocol &&
            candidate.type === remoteCandidate.type;
      });
  if (!alreadyAdded) {
    iceTransport.addRemoteCandidate(candidate);
  }
  return !alreadyAdded;
}


function makeError(name, description) {
  var e = new Error(description);
  e.name = name;
  // legacy error codes from https://heycam.github.io/webidl/#idl-DOMException-error-names
  e.code = {
    NotSupportedError: 9,
    InvalidStateError: 11,
    InvalidAccessError: 15,
    TypeError: undefined,
    OperationError: undefined
  }[name];
  return e;
}

module.exports = function(window, edgeVersion) {
  // https://w3c.github.io/mediacapture-main/#mediastream
  // Helper function to add the track to the stream and
  // dispatch the event ourselves.
  function addTrackToStreamAndFireEvent(track, stream) {
    stream.addTrack(track);
    stream.dispatchEvent(new window.MediaStreamTrackEvent('addtrack',
        {track: track}));
  }

  function removeTrackFromStreamAndFireEvent(track, stream) {
    stream.removeTrack(track);
    stream.dispatchEvent(new window.MediaStreamTrackEvent('removetrack',
        {track: track}));
  }

  function fireAddTrack(pc, track, receiver, streams) {
    var trackEvent = new Event('track');
    trackEvent.track = track;
    trackEvent.receiver = receiver;
    trackEvent.transceiver = {receiver: receiver};
    trackEvent.streams = streams;
    window.setTimeout(function() {
      pc._dispatchEvent('track', trackEvent);
    });
  }

  var RTCPeerConnection = function(config) {
    var pc = this;

    var _eventTarget = document.createDocumentFragment();
    ['addEventListener', 'removeEventListener', 'dispatchEvent']
        .forEach(function(method) {
          pc[method] = _eventTarget[method].bind(_eventTarget);
        });

    this.canTrickleIceCandidates = null;

    this.needNegotiation = false;

    this.localStreams = [];
    this.remoteStreams = [];

    this.localDescription = null;
    this.remoteDescription = null;

    this.signalingState = 'stable';
    this.iceConnectionState = 'new';
    this.connectionState = 'new';
    this.iceGatheringState = 'new';

    config = JSON.parse(JSON.stringify(config || {}));

    this.usingBundle = config.bundlePolicy === 'max-bundle';
    if (config.rtcpMuxPolicy === 'negotiate') {
      throw(makeError('NotSupportedError',
          'rtcpMuxPolicy \'negotiate\' is not supported'));
    } else if (!config.rtcpMuxPolicy) {
      config.rtcpMuxPolicy = 'require';
    }

    switch (config.iceTransportPolicy) {
      case 'all':
      case 'relay':
        break;
      default:
        config.iceTransportPolicy = 'all';
        break;
    }

    switch (config.bundlePolicy) {
      case 'balanced':
      case 'max-compat':
      case 'max-bundle':
        break;
      default:
        config.bundlePolicy = 'balanced';
        break;
    }

    config.iceServers = filterIceServers(config.iceServers || [], edgeVersion);

    this._iceGatherers = [];
    if (config.iceCandidatePoolSize) {
      for (var i = config.iceCandidatePoolSize; i > 0; i--) {
        this._iceGatherers.push(new window.RTCIceGatherer({
          iceServers: config.iceServers,
          gatherPolicy: config.iceTransportPolicy
        }));
      }
    } else {
      config.iceCandidatePoolSize = 0;
    }

    this._config = config;

    // per-track iceGathers, iceTransports, dtlsTransports, rtpSenders, ...
    // everything that is needed to describe a SDP m-line.
    this.transceivers = [];

    this._sdpSessionId = SDPUtils.generateSessionId();
    this._sdpSessionVersion = 0;

    this._dtlsRole = undefined; // role for a=setup to use in answers.

    this._isClosed = false;
  };

  // set up event handlers on prototype
  RTCPeerConnection.prototype.onicecandidate = null;
  RTCPeerConnection.prototype.onaddstream = null;
  RTCPeerConnection.prototype.ontrack = null;
  RTCPeerConnection.prototype.onremovestream = null;
  RTCPeerConnection.prototype.onsignalingstatechange = null;
  RTCPeerConnection.prototype.oniceconnectionstatechange = null;
  RTCPeerConnection.prototype.onconnectionstatechange = null;
  RTCPeerConnection.prototype.onicegatheringstatechange = null;
  RTCPeerConnection.prototype.onnegotiationneeded = null;
  RTCPeerConnection.prototype.ondatachannel = null;

  RTCPeerConnection.prototype._dispatchEvent = function(name, event) {
    if (this._isClosed) {
      return;
    }
    this.dispatchEvent(event);
    if (typeof this['on' + name] === 'function') {
      this['on' + name](event);
    }
  };

  RTCPeerConnection.prototype._emitGatheringStateChange = function() {
    var event = new Event('icegatheringstatechange');
    this._dispatchEvent('icegatheringstatechange', event);
  };

  RTCPeerConnection.prototype.getConfiguration = function() {
    return this._config;
  };

  RTCPeerConnection.prototype.getLocalStreams = function() {
    return this.localStreams;
  };

  RTCPeerConnection.prototype.getRemoteStreams = function() {
    return this.remoteStreams;
  };

  // internal helper to create a transceiver object.
  // (which is not yet the same as the WebRTC 1.0 transceiver)
  RTCPeerConnection.prototype._createTransceiver = function(kind, doNotAdd) {
    var hasBundleTransport = this.transceivers.length > 0;
    var transceiver = {
      track: null,
      iceGatherer: null,
      iceTransport: null,
      dtlsTransport: null,
      localCapabilities: null,
      remoteCapabilities: null,
      rtpSender: null,
      rtpReceiver: null,
      kind: kind,
      mid: null,
      sendEncodingParameters: null,
      recvEncodingParameters: null,
      stream: null,
      associatedRemoteMediaStreams: [],
      wantReceive: true
    };
    if (this.usingBundle && hasBundleTransport) {
      transceiver.iceTransport = this.transceivers[0].iceTransport;
      transceiver.dtlsTransport = this.transceivers[0].dtlsTransport;
    } else {
      var transports = this._createIceAndDtlsTransports();
      transceiver.iceTransport = transports.iceTransport;
      transceiver.dtlsTransport = transports.dtlsTransport;
    }
    if (!doNotAdd) {
      this.transceivers.push(transceiver);
    }
    return transceiver;
  };

  RTCPeerConnection.prototype.addTrack = function(track, stream) {
    if (this._isClosed) {
      throw makeError('InvalidStateError',
          'Attempted to call addTrack on a closed peerconnection.');
    }

    var alreadyExists = this.transceivers.find(function(s) {
      return s.track === track;
    });

    if (alreadyExists) {
      throw makeError('InvalidAccessError', 'Track already exists.');
    }

    var transceiver;
    for (var i = 0; i < this.transceivers.length; i++) {
      if (!this.transceivers[i].track &&
          this.transceivers[i].kind === track.kind) {
        transceiver = this.transceivers[i];
      }
    }
    if (!transceiver) {
      transceiver = this._createTransceiver(track.kind);
    }

    this._maybeFireNegotiationNeeded();

    if (this.localStreams.indexOf(stream) === -1) {
      this.localStreams.push(stream);
    }

    transceiver.track = track;
    transceiver.stream = stream;
    transceiver.rtpSender = new window.RTCRtpSender(track,
        transceiver.dtlsTransport);
    return transceiver.rtpSender;
  };

  RTCPeerConnection.prototype.addStream = function(stream) {
    var pc = this;
    if (edgeVersion >= 15025) {
      stream.getTracks().forEach(function(track) {
        pc.addTrack(track, stream);
      });
    } else {
      // Clone is necessary for local demos mostly, attaching directly
      // to two different senders does not work (build 10547).
      // Fixed in 15025 (or earlier)
      var clonedStream = stream.clone();
      stream.getTracks().forEach(function(track, idx) {
        var clonedTrack = clonedStream.getTracks()[idx];
        track.addEventListener('enabled', function(event) {
          clonedTrack.enabled = event.enabled;
        });
      });
      clonedStream.getTracks().forEach(function(track) {
        pc.addTrack(track, clonedStream);
      });
    }
  };

  RTCPeerConnection.prototype.removeTrack = function(sender) {
    if (this._isClosed) {
      throw makeError('InvalidStateError',
          'Attempted to call removeTrack on a closed peerconnection.');
    }

    if (!(sender instanceof window.RTCRtpSender)) {
      throw new TypeError('Argument 1 of RTCPeerConnection.removeTrack ' +
          'does not implement interface RTCRtpSender.');
    }

    var transceiver = this.transceivers.find(function(t) {
      return t.rtpSender === sender;
    });

    if (!transceiver) {
      throw makeError('InvalidAccessError',
          'Sender was not created by this connection.');
    }
    var stream = transceiver.stream;

    transceiver.rtpSender.stop();
    transceiver.rtpSender = null;
    transceiver.track = null;
    transceiver.stream = null;

    // remove the stream from the set of local streams
    var localStreams = this.transceivers.map(function(t) {
      return t.stream;
    });
    if (localStreams.indexOf(stream) === -1 &&
        this.localStreams.indexOf(stream) > -1) {
      this.localStreams.splice(this.localStreams.indexOf(stream), 1);
    }

    this._maybeFireNegotiationNeeded();
  };

  RTCPeerConnection.prototype.removeStream = function(stream) {
    var pc = this;
    stream.getTracks().forEach(function(track) {
      var sender = pc.getSenders().find(function(s) {
        return s.track === track;
      });
      if (sender) {
        pc.removeTrack(sender);
      }
    });
  };

  RTCPeerConnection.prototype.getSenders = function() {
    return this.transceivers.filter(function(transceiver) {
      return !!transceiver.rtpSender;
    })
    .map(function(transceiver) {
      return transceiver.rtpSender;
    });
  };

  RTCPeerConnection.prototype.getReceivers = function() {
    return this.transceivers.filter(function(transceiver) {
      return !!transceiver.rtpReceiver;
    })
    .map(function(transceiver) {
      return transceiver.rtpReceiver;
    });
  };


  RTCPeerConnection.prototype._createIceGatherer = function(sdpMLineIndex,
      usingBundle) {
    var pc = this;
    if (usingBundle && sdpMLineIndex > 0) {
      return this.transceivers[0].iceGatherer;
    } else if (this._iceGatherers.length) {
      return this._iceGatherers.shift();
    }
    var iceGatherer = new window.RTCIceGatherer({
      iceServers: this._config.iceServers,
      gatherPolicy: this._config.iceTransportPolicy
    });
    Object.defineProperty(iceGatherer, 'state',
        {value: 'new', writable: true}
    );

    this.transceivers[sdpMLineIndex].bufferedCandidateEvents = [];
    this.transceivers[sdpMLineIndex].bufferCandidates = function(event) {
      var end = !event.candidate || Object.keys(event.candidate).length === 0;
      // polyfill since RTCIceGatherer.state is not implemented in
      // Edge 10547 yet.
      iceGatherer.state = end ? 'completed' : 'gathering';
      if (pc.transceivers[sdpMLineIndex].bufferedCandidateEvents !== null) {
        pc.transceivers[sdpMLineIndex].bufferedCandidateEvents.push(event);
      }
    };
    iceGatherer.addEventListener('localcandidate',
      this.transceivers[sdpMLineIndex].bufferCandidates);
    return iceGatherer;
  };

  // start gathering from an RTCIceGatherer.
  RTCPeerConnection.prototype._gather = function(mid, sdpMLineIndex) {
    var pc = this;
    var iceGatherer = this.transceivers[sdpMLineIndex].iceGatherer;
    if (iceGatherer.onlocalcandidate) {
      return;
    }
    var bufferedCandidateEvents =
      this.transceivers[sdpMLineIndex].bufferedCandidateEvents;
    this.transceivers[sdpMLineIndex].bufferedCandidateEvents = null;
    iceGatherer.removeEventListener('localcandidate',
      this.transceivers[sdpMLineIndex].bufferCandidates);
    iceGatherer.onlocalcandidate = function(evt) {
      if (pc.usingBundle && sdpMLineIndex > 0) {
        // if we know that we use bundle we can drop candidates with
        // ѕdpMLineIndex > 0. If we don't do this then our state gets
        // confused since we dispose the extra ice gatherer.
        return;
      }
      var event = new Event('icecandidate');
      event.candidate = {sdpMid: mid, sdpMLineIndex: sdpMLineIndex};

      var cand = evt.candidate;
      // Edge emits an empty object for RTCIceCandidateComplete‥
      var end = !cand || Object.keys(cand).length === 0;
      if (end) {
        // polyfill since RTCIceGatherer.state is not implemented in
        // Edge 10547 yet.
        if (iceGatherer.state === 'new' || iceGatherer.state === 'gathering') {
          iceGatherer.state = 'completed';
        }
      } else {
        if (iceGatherer.state === 'new') {
          iceGatherer.state = 'gathering';
        }
        // RTCIceCandidate doesn't have a component, needs to be added
        cand.component = 1;
        // also the usernameFragment. TODO: update SDP to take both variants.
        cand.ufrag = iceGatherer.getLocalParameters().usernameFragment;

        var serializedCandidate = SDPUtils.writeCandidate(cand);
        event.candidate = Object.assign(event.candidate,
            SDPUtils.parseCandidate(serializedCandidate));

        event.candidate.candidate = serializedCandidate;
        event.candidate.toJSON = function() {
          return {
            candidate: event.candidate.candidate,
            sdpMid: event.candidate.sdpMid,
            sdpMLineIndex: event.candidate.sdpMLineIndex,
            usernameFragment: event.candidate.usernameFragment
          };
        };
      }

      // update local description.
      var sections = SDPUtils.getMediaSections(pc.localDescription.sdp);
      if (!end) {
        sections[event.candidate.sdpMLineIndex] +=
            'a=' + event.candidate.candidate + '\r\n';
      } else {
        sections[event.candidate.sdpMLineIndex] +=
            'a=end-of-candidates\r\n';
      }
      pc.localDescription.sdp =
          SDPUtils.getDescription(pc.localDescription.sdp) +
          sections.join('');
      var complete = pc.transceivers.every(function(transceiver) {
        return transceiver.iceGatherer &&
            transceiver.iceGatherer.state === 'completed';
      });

      if (pc.iceGatheringState !== 'gathering') {
        pc.iceGatheringState = 'gathering';
        pc._emitGatheringStateChange();
      }

      // Emit candidate. Also emit null candidate when all gatherers are
      // complete.
      if (!end) {
        pc._dispatchEvent('icecandidate', event);
      }
      if (complete) {
        pc._dispatchEvent('icecandidate', new Event('icecandidate'));
        pc.iceGatheringState = 'complete';
        pc._emitGatheringStateChange();
      }
    };

    // emit already gathered candidates.
    window.setTimeout(function() {
      bufferedCandidateEvents.forEach(function(e) {
        iceGatherer.onlocalcandidate(e);
      });
    }, 0);
  };

  // Create ICE transport and DTLS transport.
  RTCPeerConnection.prototype._createIceAndDtlsTransports = function() {
    var pc = this;
    var iceTransport = new window.RTCIceTransport(null);
    iceTransport.onicestatechange = function() {
      pc._updateIceConnectionState();
      pc._updateConnectionState();
    };

    var dtlsTransport = new window.RTCDtlsTransport(iceTransport);
    dtlsTransport.ondtlsstatechange = function() {
      pc._updateConnectionState();
    };
    dtlsTransport.onerror = function() {
      // onerror does not set state to failed by itself.
      Object.defineProperty(dtlsTransport, 'state',
          {value: 'failed', writable: true});
      pc._updateConnectionState();
    };

    return {
      iceTransport: iceTransport,
      dtlsTransport: dtlsTransport
    };
  };

  // Destroy ICE gatherer, ICE transport and DTLS transport.
  // Without triggering the callbacks.
  RTCPeerConnection.prototype._disposeIceAndDtlsTransports = function(
      sdpMLineIndex) {
    var iceGatherer = this.transceivers[sdpMLineIndex].iceGatherer;
    if (iceGatherer) {
      delete iceGatherer.onlocalcandidate;
      delete this.transceivers[sdpMLineIndex].iceGatherer;
    }
    var iceTransport = this.transceivers[sdpMLineIndex].iceTransport;
    if (iceTransport) {
      delete iceTransport.onicestatechange;
      delete this.transceivers[sdpMLineIndex].iceTransport;
    }
    var dtlsTransport = this.transceivers[sdpMLineIndex].dtlsTransport;
    if (dtlsTransport) {
      delete dtlsTransport.ondtlsstatechange;
      delete dtlsTransport.onerror;
      delete this.transceivers[sdpMLineIndex].dtlsTransport;
    }
  };

  // Start the RTP Sender and Receiver for a transceiver.
  RTCPeerConnection.prototype._transceive = function(transceiver,
      send, recv) {
    var params = getCommonCapabilities(transceiver.localCapabilities,
        transceiver.remoteCapabilities);
    if (send && transceiver.rtpSender) {
      params.encodings = transceiver.sendEncodingParameters;
      params.rtcp = {
        cname: SDPUtils.localCName,
        compound: transceiver.rtcpParameters.compound
      };
      if (transceiver.recvEncodingParameters.length) {
        params.rtcp.ssrc = transceiver.recvEncodingParameters[0].ssrc;
      }
      transceiver.rtpSender.send(params);
    }
    if (recv && transceiver.rtpReceiver && params.codecs.length > 0) {
      // remove RTX field in Edge 14942
      if (transceiver.kind === 'video'
          && transceiver.recvEncodingParameters
          && edgeVersion < 15019) {
        transceiver.recvEncodingParameters.forEach(function(p) {
          delete p.rtx;
        });
      }
      if (transceiver.recvEncodingParameters.length) {
        params.encodings = transceiver.recvEncodingParameters;
      } else {
        params.encodings = [{}];
      }
      params.rtcp = {
        compound: transceiver.rtcpParameters.compound
      };
      if (transceiver.rtcpParameters.cname) {
        params.rtcp.cname = transceiver.rtcpParameters.cname;
      }
      if (transceiver.sendEncodingParameters.length) {
        params.rtcp.ssrc = transceiver.sendEncodingParameters[0].ssrc;
      }
      transceiver.rtpReceiver.receive(params);
    }
  };

  RTCPeerConnection.prototype.setLocalDescription = function(description) {
    var pc = this;

    // Note: pranswer is not supported.
    if (['offer', 'answer'].indexOf(description.type) === -1) {
      return Promise.reject(makeError('TypeError',
          'Unsupported type "' + description.type + '"'));
    }

    if (!isActionAllowedInSignalingState('setLocalDescription',
        description.type, pc.signalingState) || pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not set local ' + description.type +
          ' in state ' + pc.signalingState));
    }

    var sections;
    var sessionpart;
    if (description.type === 'offer') {
      // VERY limited support for SDP munging. Limited to:
      // * changing the order of codecs
      sections = SDPUtils.splitSections(description.sdp);
      sessionpart = sections.shift();
      sections.forEach(function(mediaSection, sdpMLineIndex) {
        var caps = SDPUtils.parseRtpParameters(mediaSection);
        pc.transceivers[sdpMLineIndex].localCapabilities = caps;
      });

      pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
        pc._gather(transceiver.mid, sdpMLineIndex);
      });
    } else if (description.type === 'answer') {
      sections = SDPUtils.splitSections(pc.remoteDescription.sdp);
      sessionpart = sections.shift();
      var isIceLite = SDPUtils.matchPrefix(sessionpart,
          'a=ice-lite').length > 0;
      sections.forEach(function(mediaSection, sdpMLineIndex) {
        var transceiver = pc.transceivers[sdpMLineIndex];
        var iceGatherer = transceiver.iceGatherer;
        var iceTransport = transceiver.iceTransport;
        var dtlsTransport = transceiver.dtlsTransport;
        var localCapabilities = transceiver.localCapabilities;
        var remoteCapabilities = transceiver.remoteCapabilities;

        // treat bundle-only as not-rejected.
        var rejected = SDPUtils.isRejected(mediaSection) &&
            SDPUtils.matchPrefix(mediaSection, 'a=bundle-only').length === 0;

        if (!rejected && !transceiver.rejected) {
          var remoteIceParameters = SDPUtils.getIceParameters(
              mediaSection, sessionpart);
          var remoteDtlsParameters = SDPUtils.getDtlsParameters(
              mediaSection, sessionpart);
          if (isIceLite) {
            remoteDtlsParameters.role = 'server';
          }

          if (!pc.usingBundle || sdpMLineIndex === 0) {
            pc._gather(transceiver.mid, sdpMLineIndex);
            if (iceTransport.state === 'new') {
              iceTransport.start(iceGatherer, remoteIceParameters,
                  isIceLite ? 'controlling' : 'controlled');
            }
            if (dtlsTransport.state === 'new') {
              dtlsTransport.start(remoteDtlsParameters);
            }
          }

          // Calculate intersection of capabilities.
          var params = getCommonCapabilities(localCapabilities,
              remoteCapabilities);

          // Start the RTCRtpSender. The RTCRtpReceiver for this
          // transceiver has already been started in setRemoteDescription.
          pc._transceive(transceiver,
              params.codecs.length > 0,
              false);
        }
      });
    }

    pc.localDescription = {
      type: description.type,
      sdp: description.sdp
    };
    if (description.type === 'offer') {
      pc._updateSignalingState('have-local-offer');
    } else {
      pc._updateSignalingState('stable');
    }

    return Promise.resolve();
  };

  RTCPeerConnection.prototype.setRemoteDescription = function(description) {
    var pc = this;

    // Note: pranswer is not supported.
    if (['offer', 'answer'].indexOf(description.type) === -1) {
      return Promise.reject(makeError('TypeError',
          'Unsupported type "' + description.type + '"'));
    }

    if (!isActionAllowedInSignalingState('setRemoteDescription',
        description.type, pc.signalingState) || pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not set remote ' + description.type +
          ' in state ' + pc.signalingState));
    }

    var streams = {};
    pc.remoteStreams.forEach(function(stream) {
      streams[stream.id] = stream;
    });
    var receiverList = [];
    var sections = SDPUtils.splitSections(description.sdp);
    var sessionpart = sections.shift();
    var isIceLite = SDPUtils.matchPrefix(sessionpart,
        'a=ice-lite').length > 0;
    var usingBundle = SDPUtils.matchPrefix(sessionpart,
        'a=group:BUNDLE ').length > 0;
    pc.usingBundle = usingBundle;
    var iceOptions = SDPUtils.matchPrefix(sessionpart,
        'a=ice-options:')[0];
    if (iceOptions) {
      pc.canTrickleIceCandidates = iceOptions.substr(14).split(' ')
          .indexOf('trickle') >= 0;
    } else {
      pc.canTrickleIceCandidates = false;
    }

    sections.forEach(function(mediaSection, sdpMLineIndex) {
      var lines = SDPUtils.splitLines(mediaSection);
      var kind = SDPUtils.getKind(mediaSection);
      // treat bundle-only as not-rejected.
      var rejected = SDPUtils.isRejected(mediaSection) &&
          SDPUtils.matchPrefix(mediaSection, 'a=bundle-only').length === 0;
      var protocol = lines[0].substr(2).split(' ')[2];

      var direction = SDPUtils.getDirection(mediaSection, sessionpart);
      var remoteMsid = SDPUtils.parseMsid(mediaSection);

      var mid = SDPUtils.getMid(mediaSection) || SDPUtils.generateIdentifier();

      // Reject datachannels which are not implemented yet.
      if ((kind === 'application' && protocol === 'DTLS/SCTP') || rejected) {
        // TODO: this is dangerous in the case where a non-rejected m-line
        //     becomes rejected.
        pc.transceivers[sdpMLineIndex] = {
          mid: mid,
          kind: kind,
          rejected: true
        };
        return;
      }

      if (!rejected && pc.transceivers[sdpMLineIndex] &&
          pc.transceivers[sdpMLineIndex].rejected) {
        // recycle a rejected transceiver.
        pc.transceivers[sdpMLineIndex] = pc._createTransceiver(kind, true);
      }

      var transceiver;
      var iceGatherer;
      var iceTransport;
      var dtlsTransport;
      var rtpReceiver;
      var sendEncodingParameters;
      var recvEncodingParameters;
      var localCapabilities;

      var track;
      // FIXME: ensure the mediaSection has rtcp-mux set.
      var remoteCapabilities = SDPUtils.parseRtpParameters(mediaSection);
      var remoteIceParameters;
      var remoteDtlsParameters;
      if (!rejected) {
        remoteIceParameters = SDPUtils.getIceParameters(mediaSection,
            sessionpart);
        remoteDtlsParameters = SDPUtils.getDtlsParameters(mediaSection,
            sessionpart);
        remoteDtlsParameters.role = 'client';
      }
      recvEncodingParameters =
          SDPUtils.parseRtpEncodingParameters(mediaSection);

      var rtcpParameters = SDPUtils.parseRtcpParameters(mediaSection);

      var isComplete = SDPUtils.matchPrefix(mediaSection,
          'a=end-of-candidates', sessionpart).length > 0;
      var cands = SDPUtils.matchPrefix(mediaSection, 'a=candidate:')
          .map(function(cand) {
            return SDPUtils.parseCandidate(cand);
          })
          .filter(function(cand) {
            return cand.component === 1;
          });

      // Check if we can use BUNDLE and dispose transports.
      if ((description.type === 'offer' || description.type === 'answer') &&
          !rejected && usingBundle && sdpMLineIndex > 0 &&
          pc.transceivers[sdpMLineIndex]) {
        pc._disposeIceAndDtlsTransports(sdpMLineIndex);
        pc.transceivers[sdpMLineIndex].iceGatherer =
            pc.transceivers[0].iceGatherer;
        pc.transceivers[sdpMLineIndex].iceTransport =
            pc.transceivers[0].iceTransport;
        pc.transceivers[sdpMLineIndex].dtlsTransport =
            pc.transceivers[0].dtlsTransport;
        if (pc.transceivers[sdpMLineIndex].rtpSender) {
          pc.transceivers[sdpMLineIndex].rtpSender.setTransport(
              pc.transceivers[0].dtlsTransport);
        }
        if (pc.transceivers[sdpMLineIndex].rtpReceiver) {
          pc.transceivers[sdpMLineIndex].rtpReceiver.setTransport(
              pc.transceivers[0].dtlsTransport);
        }
      }
      if (description.type === 'offer' && !rejected) {
        transceiver = pc.transceivers[sdpMLineIndex] ||
            pc._createTransceiver(kind);
        transceiver.mid = mid;

        if (!transceiver.iceGatherer) {
          transceiver.iceGatherer = pc._createIceGatherer(sdpMLineIndex,
              usingBundle);
        }

        if (cands.length && transceiver.iceTransport.state === 'new') {
          if (isComplete && (!usingBundle || sdpMLineIndex === 0)) {
            transceiver.iceTransport.setRemoteCandidates(cands);
          } else {
            cands.forEach(function(candidate) {
              maybeAddCandidate(transceiver.iceTransport, candidate);
            });
          }
        }

        localCapabilities = window.RTCRtpReceiver.getCapabilities(kind);

        // filter RTX until additional stuff needed for RTX is implemented
        // in adapter.js
        if (edgeVersion < 15019) {
          localCapabilities.codecs = localCapabilities.codecs.filter(
              function(codec) {
                return codec.name !== 'rtx';
              });
        }

        sendEncodingParameters = transceiver.sendEncodingParameters || [{
          ssrc: (2 * sdpMLineIndex + 2) * 1001
        }];

        // TODO: rewrite to use http://w3c.github.io/webrtc-pc/#set-associated-remote-streams
        var isNewTrack = false;
        if (direction === 'sendrecv' || direction === 'sendonly') {
          isNewTrack = !transceiver.rtpReceiver;
          rtpReceiver = transceiver.rtpReceiver ||
              new window.RTCRtpReceiver(transceiver.dtlsTransport, kind);

          if (isNewTrack) {
            var stream;
            track = rtpReceiver.track;
            // FIXME: does not work with Plan B.
            if (remoteMsid && remoteMsid.stream === '-') {
              // no-op. a stream id of '-' means: no associated stream.
            } else if (remoteMsid) {
              if (!streams[remoteMsid.stream]) {
                streams[remoteMsid.stream] = new window.MediaStream();
                Object.defineProperty(streams[remoteMsid.stream], 'id', {
                  get: function() {
                    return remoteMsid.stream;
                  }
                });
              }
              Object.defineProperty(track, 'id', {
                get: function() {
                  return remoteMsid.track;
                }
              });
              stream = streams[remoteMsid.stream];
            } else {
              if (!streams.default) {
                streams.default = new window.MediaStream();
              }
              stream = streams.default;
            }
            if (stream) {
              addTrackToStreamAndFireEvent(track, stream);
              transceiver.associatedRemoteMediaStreams.push(stream);
            }
            receiverList.push([track, rtpReceiver, stream]);
          }
        } else if (transceiver.rtpReceiver && transceiver.rtpReceiver.track) {
          transceiver.associatedRemoteMediaStreams.forEach(function(s) {
            var nativeTrack = s.getTracks().find(function(t) {
              return t.id === transceiver.rtpReceiver.track.id;
            });
            if (nativeTrack) {
              removeTrackFromStreamAndFireEvent(nativeTrack, s);
            }
          });
          transceiver.associatedRemoteMediaStreams = [];
        }

        transceiver.localCapabilities = localCapabilities;
        transceiver.remoteCapabilities = remoteCapabilities;
        transceiver.rtpReceiver = rtpReceiver;
        transceiver.rtcpParameters = rtcpParameters;
        transceiver.sendEncodingParameters = sendEncodingParameters;
        transceiver.recvEncodingParameters = recvEncodingParameters;

        // Start the RTCRtpReceiver now. The RTPSender is started in
        // setLocalDescription.
        pc._transceive(pc.transceivers[sdpMLineIndex],
            false,
            isNewTrack);
      } else if (description.type === 'answer' && !rejected) {
        transceiver = pc.transceivers[sdpMLineIndex];
        iceGatherer = transceiver.iceGatherer;
        iceTransport = transceiver.iceTransport;
        dtlsTransport = transceiver.dtlsTransport;
        rtpReceiver = transceiver.rtpReceiver;
        sendEncodingParameters = transceiver.sendEncodingParameters;
        localCapabilities = transceiver.localCapabilities;

        pc.transceivers[sdpMLineIndex].recvEncodingParameters =
            recvEncodingParameters;
        pc.transceivers[sdpMLineIndex].remoteCapabilities =
            remoteCapabilities;
        pc.transceivers[sdpMLineIndex].rtcpParameters = rtcpParameters;

        if (cands.length && iceTransport.state === 'new') {
          if ((isIceLite || isComplete) &&
              (!usingBundle || sdpMLineIndex === 0)) {
            iceTransport.setRemoteCandidates(cands);
          } else {
            cands.forEach(function(candidate) {
              maybeAddCandidate(transceiver.iceTransport, candidate);
            });
          }
        }

        if (!usingBundle || sdpMLineIndex === 0) {
          if (iceTransport.state === 'new') {
            iceTransport.start(iceGatherer, remoteIceParameters,
                'controlling');
          }
          if (dtlsTransport.state === 'new') {
            dtlsTransport.start(remoteDtlsParameters);
          }
        }

        pc._transceive(transceiver,
            direction === 'sendrecv' || direction === 'recvonly',
            direction === 'sendrecv' || direction === 'sendonly');

        // TODO: rewrite to use http://w3c.github.io/webrtc-pc/#set-associated-remote-streams
        if (rtpReceiver &&
            (direction === 'sendrecv' || direction === 'sendonly')) {
          track = rtpReceiver.track;
          if (remoteMsid) {
            if (!streams[remoteMsid.stream]) {
              streams[remoteMsid.stream] = new window.MediaStream();
            }
            addTrackToStreamAndFireEvent(track, streams[remoteMsid.stream]);
            receiverList.push([track, rtpReceiver, streams[remoteMsid.stream]]);
          } else {
            if (!streams.default) {
              streams.default = new window.MediaStream();
            }
            addTrackToStreamAndFireEvent(track, streams.default);
            receiverList.push([track, rtpReceiver, streams.default]);
          }
        } else {
          // FIXME: actually the receiver should be created later.
          delete transceiver.rtpReceiver;
        }
      }
    });

    if (pc._dtlsRole === undefined) {
      pc._dtlsRole = description.type === 'offer' ? 'active' : 'passive';
    }

    pc.remoteDescription = {
      type: description.type,
      sdp: description.sdp
    };
    if (description.type === 'offer') {
      pc._updateSignalingState('have-remote-offer');
    } else {
      pc._updateSignalingState('stable');
    }
    Object.keys(streams).forEach(function(sid) {
      var stream = streams[sid];
      if (stream.getTracks().length) {
        if (pc.remoteStreams.indexOf(stream) === -1) {
          pc.remoteStreams.push(stream);
          var event = new Event('addstream');
          event.stream = stream;
          window.setTimeout(function() {
            pc._dispatchEvent('addstream', event);
          });
        }

        receiverList.forEach(function(item) {
          var track = item[0];
          var receiver = item[1];
          if (stream.id !== item[2].id) {
            return;
          }
          fireAddTrack(pc, track, receiver, [stream]);
        });
      }
    });
    receiverList.forEach(function(item) {
      if (item[2]) {
        return;
      }
      fireAddTrack(pc, item[0], item[1], []);
    });

    // check whether addIceCandidate({}) was called within four seconds after
    // setRemoteDescription.
    window.setTimeout(function() {
      if (!(pc && pc.transceivers)) {
        return;
      }
      pc.transceivers.forEach(function(transceiver) {
        if (transceiver.iceTransport &&
            transceiver.iceTransport.state === 'new' &&
            transceiver.iceTransport.getRemoteCandidates().length > 0) {
          console.warn('Timeout for addRemoteCandidate. Consider sending ' +
              'an end-of-candidates notification');
          transceiver.iceTransport.addRemoteCandidate({});
        }
      });
    }, 4000);

    return Promise.resolve();
  };

  RTCPeerConnection.prototype.close = function() {
    this.transceivers.forEach(function(transceiver) {
      /* not yet
      if (transceiver.iceGatherer) {
        transceiver.iceGatherer.close();
      }
      */
      if (transceiver.iceTransport) {
        transceiver.iceTransport.stop();
      }
      if (transceiver.dtlsTransport) {
        transceiver.dtlsTransport.stop();
      }
      if (transceiver.rtpSender) {
        transceiver.rtpSender.stop();
      }
      if (transceiver.rtpReceiver) {
        transceiver.rtpReceiver.stop();
      }
    });
    // FIXME: clean up tracks, local streams, remote streams, etc
    this._isClosed = true;
    this._updateSignalingState('closed');
  };

  // Update the signaling state.
  RTCPeerConnection.prototype._updateSignalingState = function(newState) {
    this.signalingState = newState;
    var event = new Event('signalingstatechange');
    this._dispatchEvent('signalingstatechange', event);
  };

  // Determine whether to fire the negotiationneeded event.
  RTCPeerConnection.prototype._maybeFireNegotiationNeeded = function() {
    var pc = this;
    if (this.signalingState !== 'stable' || this.needNegotiation === true) {
      return;
    }
    this.needNegotiation = true;
    window.setTimeout(function() {
      if (pc.needNegotiation) {
        pc.needNegotiation = false;
        var event = new Event('negotiationneeded');
        pc._dispatchEvent('negotiationneeded', event);
      }
    }, 0);
  };

  // Update the ice connection state.
  RTCPeerConnection.prototype._updateIceConnectionState = function() {
    var newState;
    var states = {
      'new': 0,
      closed: 0,
      checking: 0,
      connected: 0,
      completed: 0,
      disconnected: 0,
      failed: 0
    };
    this.transceivers.forEach(function(transceiver) {
      states[transceiver.iceTransport.state]++;
    });

    newState = 'new';
    if (states.failed > 0) {
      newState = 'failed';
    } else if (states.checking > 0) {
      newState = 'checking';
    } else if (states.disconnected > 0) {
      newState = 'disconnected';
    } else if (states.new > 0) {
      newState = 'new';
    } else if (states.connected > 0) {
      newState = 'connected';
    } else if (states.completed > 0) {
      newState = 'completed';
    }

    if (newState !== this.iceConnectionState) {
      this.iceConnectionState = newState;
      var event = new Event('iceconnectionstatechange');
      this._dispatchEvent('iceconnectionstatechange', event);
    }
  };

  // Update the connection state.
  RTCPeerConnection.prototype._updateConnectionState = function() {
    var newState;
    var states = {
      'new': 0,
      closed: 0,
      connecting: 0,
      connected: 0,
      completed: 0,
      disconnected: 0,
      failed: 0
    };
    this.transceivers.forEach(function(transceiver) {
      states[transceiver.iceTransport.state]++;
      states[transceiver.dtlsTransport.state]++;
    });
    // ICETransport.completed and connected are the same for this purpose.
    states.connected += states.completed;

    newState = 'new';
    if (states.failed > 0) {
      newState = 'failed';
    } else if (states.connecting > 0) {
      newState = 'connecting';
    } else if (states.disconnected > 0) {
      newState = 'disconnected';
    } else if (states.new > 0) {
      newState = 'new';
    } else if (states.connected > 0) {
      newState = 'connected';
    }

    if (newState !== this.connectionState) {
      this.connectionState = newState;
      var event = new Event('connectionstatechange');
      this._dispatchEvent('connectionstatechange', event);
    }
  };

  RTCPeerConnection.prototype.createOffer = function() {
    var pc = this;

    if (pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createOffer after close'));
    }

    var numAudioTracks = pc.transceivers.filter(function(t) {
      return t.kind === 'audio';
    }).length;
    var numVideoTracks = pc.transceivers.filter(function(t) {
      return t.kind === 'video';
    }).length;

    // Determine number of audio and video tracks we need to send/recv.
    var offerOptions = arguments[0];
    if (offerOptions) {
      // Reject Chrome legacy constraints.
      if (offerOptions.mandatory || offerOptions.optional) {
        throw new TypeError(
            'Legacy mandatory/optional constraints not supported.');
      }
      if (offerOptions.offerToReceiveAudio !== undefined) {
        if (offerOptions.offerToReceiveAudio === true) {
          numAudioTracks = 1;
        } else if (offerOptions.offerToReceiveAudio === false) {
          numAudioTracks = 0;
        } else {
          numAudioTracks = offerOptions.offerToReceiveAudio;
        }
      }
      if (offerOptions.offerToReceiveVideo !== undefined) {
        if (offerOptions.offerToReceiveVideo === true) {
          numVideoTracks = 1;
        } else if (offerOptions.offerToReceiveVideo === false) {
          numVideoTracks = 0;
        } else {
          numVideoTracks = offerOptions.offerToReceiveVideo;
        }
      }
    }

    pc.transceivers.forEach(function(transceiver) {
      if (transceiver.kind === 'audio') {
        numAudioTracks--;
        if (numAudioTracks < 0) {
          transceiver.wantReceive = false;
        }
      } else if (transceiver.kind === 'video') {
        numVideoTracks--;
        if (numVideoTracks < 0) {
          transceiver.wantReceive = false;
        }
      }
    });

    // Create M-lines for recvonly streams.
    while (numAudioTracks > 0 || numVideoTracks > 0) {
      if (numAudioTracks > 0) {
        pc._createTransceiver('audio');
        numAudioTracks--;
      }
      if (numVideoTracks > 0) {
        pc._createTransceiver('video');
        numVideoTracks--;
      }
    }

    var sdp = SDPUtils.writeSessionBoilerplate(pc._sdpSessionId,
        pc._sdpSessionVersion++);
    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      // For each track, create an ice gatherer, ice transport,
      // dtls transport, potentially rtpsender and rtpreceiver.
      var track = transceiver.track;
      var kind = transceiver.kind;
      var mid = transceiver.mid || SDPUtils.generateIdentifier();
      transceiver.mid = mid;

      if (!transceiver.iceGatherer) {
        transceiver.iceGatherer = pc._createIceGatherer(sdpMLineIndex,
            pc.usingBundle);
      }

      var localCapabilities = window.RTCRtpSender.getCapabilities(kind);
      // filter RTX until additional stuff needed for RTX is implemented
      // in adapter.js
      if (edgeVersion < 15019) {
        localCapabilities.codecs = localCapabilities.codecs.filter(
            function(codec) {
              return codec.name !== 'rtx';
            });
      }
      localCapabilities.codecs.forEach(function(codec) {
        // work around https://bugs.chromium.org/p/webrtc/issues/detail?id=6552
        // by adding level-asymmetry-allowed=1
        if (codec.name === 'H264' &&
            codec.parameters['level-asymmetry-allowed'] === undefined) {
          codec.parameters['level-asymmetry-allowed'] = '1';
        }

        // for subsequent offers, we might have to re-use the payload
        // type of the last offer.
        if (transceiver.remoteCapabilities &&
            transceiver.remoteCapabilities.codecs) {
          transceiver.remoteCapabilities.codecs.forEach(function(remoteCodec) {
            if (codec.name.toLowerCase() === remoteCodec.name.toLowerCase() &&
                codec.clockRate === remoteCodec.clockRate) {
              codec.preferredPayloadType = remoteCodec.payloadType;
            }
          });
        }
      });
      localCapabilities.headerExtensions.forEach(function(hdrExt) {
        var remoteExtensions = transceiver.remoteCapabilities &&
            transceiver.remoteCapabilities.headerExtensions || [];
        remoteExtensions.forEach(function(rHdrExt) {
          if (hdrExt.uri === rHdrExt.uri) {
            hdrExt.id = rHdrExt.id;
          }
        });
      });

      // generate an ssrc now, to be used later in rtpSender.send
      var sendEncodingParameters = transceiver.sendEncodingParameters || [{
        ssrc: (2 * sdpMLineIndex + 1) * 1001
      }];
      if (track) {
        // add RTX
        if (edgeVersion >= 15019 && kind === 'video' &&
            !sendEncodingParameters[0].rtx) {
          sendEncodingParameters[0].rtx = {
            ssrc: sendEncodingParameters[0].ssrc + 1
          };
        }
      }

      if (transceiver.wantReceive) {
        transceiver.rtpReceiver = new window.RTCRtpReceiver(
            transceiver.dtlsTransport, kind);
      }

      transceiver.localCapabilities = localCapabilities;
      transceiver.sendEncodingParameters = sendEncodingParameters;
    });

    // always offer BUNDLE and dispose on return if not supported.
    if (pc._config.bundlePolicy !== 'max-compat') {
      sdp += 'a=group:BUNDLE ' + pc.transceivers.map(function(t) {
        return t.mid;
      }).join(' ') + '\r\n';
    }
    sdp += 'a=ice-options:trickle\r\n';

    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      sdp += writeMediaSection(transceiver, transceiver.localCapabilities,
          'offer', transceiver.stream, pc._dtlsRole);
      sdp += 'a=rtcp-rsize\r\n';

      if (transceiver.iceGatherer && pc.iceGatheringState !== 'new' &&
          (sdpMLineIndex === 0 || !pc.usingBundle)) {
        transceiver.iceGatherer.getLocalCandidates().forEach(function(cand) {
          cand.component = 1;
          sdp += 'a=' + SDPUtils.writeCandidate(cand) + '\r\n';
        });

        if (transceiver.iceGatherer.state === 'completed') {
          sdp += 'a=end-of-candidates\r\n';
        }
      }
    });

    var desc = new window.RTCSessionDescription({
      type: 'offer',
      sdp: sdp
    });
    return Promise.resolve(desc);
  };

  RTCPeerConnection.prototype.createAnswer = function() {
    var pc = this;

    if (pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createAnswer after close'));
    }

    if (!(pc.signalingState === 'have-remote-offer' ||
        pc.signalingState === 'have-local-pranswer')) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createAnswer in signalingState ' + pc.signalingState));
    }

    var sdp = SDPUtils.writeSessionBoilerplate(pc._sdpSessionId,
        pc._sdpSessionVersion++);
    if (pc.usingBundle) {
      sdp += 'a=group:BUNDLE ' + pc.transceivers.map(function(t) {
        return t.mid;
      }).join(' ') + '\r\n';
    }
    var mediaSectionsInOffer = SDPUtils.getMediaSections(
        pc.remoteDescription.sdp).length;
    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      if (sdpMLineIndex + 1 > mediaSectionsInOffer) {
        return;
      }
      if (transceiver.rejected) {
        if (transceiver.kind === 'application') {
          sdp += 'm=application 0 DTLS/SCTP 5000\r\n';
        } else if (transceiver.kind === 'audio') {
          sdp += 'm=audio 0 UDP/TLS/RTP/SAVPF 0\r\n' +
              'a=rtpmap:0 PCMU/8000\r\n';
        } else if (transceiver.kind === 'video') {
          sdp += 'm=video 0 UDP/TLS/RTP/SAVPF 120\r\n' +
              'a=rtpmap:120 VP8/90000\r\n';
        }
        sdp += 'c=IN IP4 0.0.0.0\r\n' +
            'a=inactive\r\n' +
            'a=mid:' + transceiver.mid + '\r\n';
        return;
      }

      // FIXME: look at direction.
      if (transceiver.stream) {
        var localTrack;
        if (transceiver.kind === 'audio') {
          localTrack = transceiver.stream.getAudioTracks()[0];
        } else if (transceiver.kind === 'video') {
          localTrack = transceiver.stream.getVideoTracks()[0];
        }
        if (localTrack) {
          // add RTX
          if (edgeVersion >= 15019 && transceiver.kind === 'video' &&
              !transceiver.sendEncodingParameters[0].rtx) {
            transceiver.sendEncodingParameters[0].rtx = {
              ssrc: transceiver.sendEncodingParameters[0].ssrc + 1
            };
          }
        }
      }

      // Calculate intersection of capabilities.
      var commonCapabilities = getCommonCapabilities(
          transceiver.localCapabilities,
          transceiver.remoteCapabilities);

      var hasRtx = commonCapabilities.codecs.filter(function(c) {
        return c.name.toLowerCase() === 'rtx';
      }).length;
      if (!hasRtx && transceiver.sendEncodingParameters[0].rtx) {
        delete transceiver.sendEncodingParameters[0].rtx;
      }

      sdp += writeMediaSection(transceiver, commonCapabilities,
          'answer', transceiver.stream, pc._dtlsRole);
      if (transceiver.rtcpParameters &&
          transceiver.rtcpParameters.reducedSize) {
        sdp += 'a=rtcp-rsize\r\n';
      }
    });

    var desc = new window.RTCSessionDescription({
      type: 'answer',
      sdp: sdp
    });
    return Promise.resolve(desc);
  };

  RTCPeerConnection.prototype.addIceCandidate = function(candidate) {
    var pc = this;
    var sections;
    if (candidate && !(candidate.sdpMLineIndex !== undefined ||
        candidate.sdpMid)) {
      return Promise.reject(new TypeError('sdpMLineIndex or sdpMid required'));
    }

    // TODO: needs to go into ops queue.
    return new Promise(function(resolve, reject) {
      if (!pc.remoteDescription) {
        return reject(makeError('InvalidStateError',
            'Can not add ICE candidate without a remote description'));
      } else if (!candidate || candidate.candidate === '') {
        for (var j = 0; j < pc.transceivers.length; j++) {
          if (pc.transceivers[j].rejected) {
            continue;
          }
          pc.transceivers[j].iceTransport.addRemoteCandidate({});
          sections = SDPUtils.getMediaSections(pc.remoteDescription.sdp);
          sections[j] += 'a=end-of-candidates\r\n';
          pc.remoteDescription.sdp =
              SDPUtils.getDescription(pc.remoteDescription.sdp) +
              sections.join('');
          if (pc.usingBundle) {
            break;
          }
        }
      } else {
        var sdpMLineIndex = candidate.sdpMLineIndex;
        if (candidate.sdpMid) {
          for (var i = 0; i < pc.transceivers.length; i++) {
            if (pc.transceivers[i].mid === candidate.sdpMid) {
              sdpMLineIndex = i;
              break;
            }
          }
        }
        var transceiver = pc.transceivers[sdpMLineIndex];
        if (transceiver) {
          if (transceiver.rejected) {
            return resolve();
          }
          var cand = Object.keys(candidate.candidate).length > 0 ?
              SDPUtils.parseCandidate(candidate.candidate) : {};
          // Ignore Chrome's invalid candidates since Edge does not like them.
          if (cand.protocol === 'tcp' && (cand.port === 0 || cand.port === 9)) {
            return resolve();
          }
          // Ignore RTCP candidates, we assume RTCP-MUX.
          if (cand.component && cand.component !== 1) {
            return resolve();
          }
          // when using bundle, avoid adding candidates to the wrong
          // ice transport. And avoid adding candidates added in the SDP.
          if (sdpMLineIndex === 0 || (sdpMLineIndex > 0 &&
              transceiver.iceTransport !== pc.transceivers[0].iceTransport)) {
            if (!maybeAddCandidate(transceiver.iceTransport, cand)) {
              return reject(makeError('OperationError',
                  'Can not add ICE candidate'));
            }
          }

          // update the remoteDescription.
          var candidateString = candidate.candidate.trim();
          if (candidateString.indexOf('a=') === 0) {
            candidateString = candidateString.substr(2);
          }
          sections = SDPUtils.getMediaSections(pc.remoteDescription.sdp);
          sections[sdpMLineIndex] += 'a=' +
              (cand.type ? candidateString : 'end-of-candidates')
              + '\r\n';
          pc.remoteDescription.sdp =
              SDPUtils.getDescription(pc.remoteDescription.sdp) +
              sections.join('');
        } else {
          return reject(makeError('OperationError',
              'Can not add ICE candidate'));
        }
      }
      resolve();
    });
  };

  RTCPeerConnection.prototype.getStats = function(selector) {
    if (selector && selector instanceof window.MediaStreamTrack) {
      var senderOrReceiver = null;
      this.transceivers.forEach(function(transceiver) {
        if (transceiver.rtpSender &&
            transceiver.rtpSender.track === selector) {
          senderOrReceiver = transceiver.rtpSender;
        } else if (transceiver.rtpReceiver &&
            transceiver.rtpReceiver.track === selector) {
          senderOrReceiver = transceiver.rtpReceiver;
        }
      });
      if (!senderOrReceiver) {
        throw makeError('InvalidAccessError', 'Invalid selector.');
      }
      return senderOrReceiver.getStats();
    }

    var promises = [];
    this.transceivers.forEach(function(transceiver) {
      ['rtpSender', 'rtpReceiver', 'iceGatherer', 'iceTransport',
          'dtlsTransport'].forEach(function(method) {
            if (transceiver[method]) {
              promises.push(transceiver[method].getStats());
            }
          });
    });
    return Promise.all(promises).then(function(allStats) {
      var results = new Map();
      allStats.forEach(function(stats) {
        stats.forEach(function(stat) {
          results.set(stat.id, stat);
        });
      });
      return results;
    });
  };

  // fix low-level stat names and return Map instead of object.
  var ortcObjects = ['RTCRtpSender', 'RTCRtpReceiver', 'RTCIceGatherer',
    'RTCIceTransport', 'RTCDtlsTransport'];
  ortcObjects.forEach(function(ortcObjectName) {
    var obj = window[ortcObjectName];
    if (obj && obj.prototype && obj.prototype.getStats) {
      var nativeGetstats = obj.prototype.getStats;
      obj.prototype.getStats = function() {
        return nativeGetstats.apply(this)
        .then(function(nativeStats) {
          var mapStats = new Map();
          Object.keys(nativeStats).forEach(function(id) {
            nativeStats[id].type = fixStatsType(nativeStats[id]);
            mapStats.set(id, nativeStats[id]);
          });
          return mapStats;
        });
      };
    }
  });

  // legacy callback shims. Should be moved to adapter.js some days.
  var methods = ['createOffer', 'createAnswer'];
  methods.forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[0] === 'function' ||
          typeof args[1] === 'function') { // legacy
        return nativeMethod.apply(this, [arguments[2]])
        .then(function(description) {
          if (typeof args[0] === 'function') {
            args[0].apply(null, [description]);
          }
        }, function(error) {
          if (typeof args[1] === 'function') {
            args[1].apply(null, [error]);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  methods = ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate'];
  methods.forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[1] === 'function' ||
          typeof args[2] === 'function') { // legacy
        return nativeMethod.apply(this, arguments)
        .then(function() {
          if (typeof args[1] === 'function') {
            args[1].apply(null);
          }
        }, function(error) {
          if (typeof args[2] === 'function') {
            args[2].apply(null, [error]);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  // getStats is special. It doesn't have a spec legacy method yet we support
  // getStats(something, cb) without error callbacks.
  ['getStats'].forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[1] === 'function') {
        return nativeMethod.apply(this, arguments)
        .then(function() {
          if (typeof args[1] === 'function') {
            args[1].apply(null);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  return RTCPeerConnection;
};


/***/ }),
/* 63 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


// Expose public methods.
module.exports = function(window) {
  var navigator = window && window.navigator;

  var shimError_ = function(e) {
    return {
      name: {PermissionDeniedError: 'NotAllowedError'}[e.name] || e.name,
      message: e.message,
      constraint: e.constraint,
      toString: function() {
        return this.name;
      }
    };
  };

  // getUserMedia error shim.
  var origGetUserMedia = navigator.mediaDevices.getUserMedia.
      bind(navigator.mediaDevices);
  navigator.mediaDevices.getUserMedia = function(c) {
    return origGetUserMedia(c).catch(function(e) {
      return Promise.reject(shimError_(e));
    });
  };
};


/***/ }),
/* 64 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var utils = __webpack_require__(7);

var firefoxShim = {
  shimOnTrack: function(window) {
    if (typeof window === 'object' && window.RTCPeerConnection && !('ontrack' in
        window.RTCPeerConnection.prototype)) {
      Object.defineProperty(window.RTCPeerConnection.prototype, 'ontrack', {
        get: function() {
          return this._ontrack;
        },
        set: function(f) {
          if (this._ontrack) {
            this.removeEventListener('track', this._ontrack);
            this.removeEventListener('addstream', this._ontrackpoly);
          }
          this.addEventListener('track', this._ontrack = f);
          this.addEventListener('addstream', this._ontrackpoly = function(e) {
            e.stream.getTracks().forEach(function(track) {
              var event = new Event('track');
              event.track = track;
              event.receiver = {track: track};
              event.transceiver = {receiver: event.receiver};
              event.streams = [e.stream];
              this.dispatchEvent(event);
            }.bind(this));
          }.bind(this));
        }
      });
    }
    if (typeof window === 'object' && window.RTCTrackEvent &&
        ('receiver' in window.RTCTrackEvent.prototype) &&
        !('transceiver' in window.RTCTrackEvent.prototype)) {
      Object.defineProperty(window.RTCTrackEvent.prototype, 'transceiver', {
        get: function() {
          return {receiver: this.receiver};
        }
      });
    }
  },

  shimSourceObject: function(window) {
    // Firefox has supported mozSrcObject since FF22, unprefixed in 42.
    if (typeof window === 'object') {
      if (window.HTMLMediaElement &&
        !('srcObject' in window.HTMLMediaElement.prototype)) {
        // Shim the srcObject property, once, when HTMLMediaElement is found.
        Object.defineProperty(window.HTMLMediaElement.prototype, 'srcObject', {
          get: function() {
            return this.mozSrcObject;
          },
          set: function(stream) {
            this.mozSrcObject = stream;
          }
        });
      }
    }
  },

  shimPeerConnection: function(window) {
    var browserDetails = utils.detectBrowser(window);

    if (typeof window !== 'object' || !(window.RTCPeerConnection ||
        window.mozRTCPeerConnection)) {
      return; // probably media.peerconnection.enabled=false in about:config
    }
    // The RTCPeerConnection object.
    if (!window.RTCPeerConnection) {
      window.RTCPeerConnection = function(pcConfig, pcConstraints) {
        if (browserDetails.version < 38) {
          // .urls is not supported in FF < 38.
          // create RTCIceServers with a single url.
          if (pcConfig && pcConfig.iceServers) {
            var newIceServers = [];
            for (var i = 0; i < pcConfig.iceServers.length; i++) {
              var server = pcConfig.iceServers[i];
              if (server.hasOwnProperty('urls')) {
                for (var j = 0; j < server.urls.length; j++) {
                  var newServer = {
                    url: server.urls[j]
                  };
                  if (server.urls[j].indexOf('turn') === 0) {
                    newServer.username = server.username;
                    newServer.credential = server.credential;
                  }
                  newIceServers.push(newServer);
                }
              } else {
                newIceServers.push(pcConfig.iceServers[i]);
              }
            }
            pcConfig.iceServers = newIceServers;
          }
        }
        return new window.mozRTCPeerConnection(pcConfig, pcConstraints);
      };
      window.RTCPeerConnection.prototype =
          window.mozRTCPeerConnection.prototype;

      // wrap static methods. Currently just generateCertificate.
      if (window.mozRTCPeerConnection.generateCertificate) {
        Object.defineProperty(window.RTCPeerConnection, 'generateCertificate', {
          get: function() {
            return window.mozRTCPeerConnection.generateCertificate;
          }
        });
      }

      window.RTCSessionDescription = window.mozRTCSessionDescription;
      window.RTCIceCandidate = window.mozRTCIceCandidate;
    }

    // shim away need for obsolete RTCIceCandidate/RTCSessionDescription.
    ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate']
        .forEach(function(method) {
          var nativeMethod = window.RTCPeerConnection.prototype[method];
          window.RTCPeerConnection.prototype[method] = function() {
            arguments[0] = new ((method === 'addIceCandidate') ?
                window.RTCIceCandidate :
                window.RTCSessionDescription)(arguments[0]);
            return nativeMethod.apply(this, arguments);
          };
        });

    // support for addIceCandidate(null or undefined)
    var nativeAddIceCandidate =
        window.RTCPeerConnection.prototype.addIceCandidate;
    window.RTCPeerConnection.prototype.addIceCandidate = function() {
      if (!arguments[0]) {
        if (arguments[1]) {
          arguments[1].apply(null);
        }
        return Promise.resolve();
      }
      return nativeAddIceCandidate.apply(this, arguments);
    };

    // shim getStats with maplike support
    var makeMapStats = function(stats) {
      var map = new Map();
      Object.keys(stats).forEach(function(key) {
        map.set(key, stats[key]);
        map[key] = stats[key];
      });
      return map;
    };

    var modernStatsTypes = {
      inboundrtp: 'inbound-rtp',
      outboundrtp: 'outbound-rtp',
      candidatepair: 'candidate-pair',
      localcandidate: 'local-candidate',
      remotecandidate: 'remote-candidate'
    };

    var nativeGetStats = window.RTCPeerConnection.prototype.getStats;
    window.RTCPeerConnection.prototype.getStats = function(
      selector,
      onSucc,
      onErr
    ) {
      return nativeGetStats.apply(this, [selector || null])
        .then(function(stats) {
          if (browserDetails.version < 48) {
            stats = makeMapStats(stats);
          }
          if (browserDetails.version < 53 && !onSucc) {
            // Shim only promise getStats with spec-hyphens in type names
            // Leave callback version alone; misc old uses of forEach before Map
            try {
              stats.forEach(function(stat) {
                stat.type = modernStatsTypes[stat.type] || stat.type;
              });
            } catch (e) {
              if (e.name !== 'TypeError') {
                throw e;
              }
              // Avoid TypeError: "type" is read-only, in old versions. 34-43ish
              stats.forEach(function(stat, i) {
                stats.set(i, Object.assign({}, stat, {
                  type: modernStatsTypes[stat.type] || stat.type
                }));
              });
            }
          }
          return stats;
        })
        .then(onSucc, onErr);
    };
  },

  shimRemoveStream: function(window) {
    if ('removeStream' in window.RTCPeerConnection.prototype) {
      return;
    }
    window.RTCPeerConnection.prototype.removeStream = function(stream) {
      var pc = this;
      utils.deprecated('removeStream', 'removeTrack');
      this.getSenders().forEach(function(sender) {
        if (sender.track && stream.getTracks().indexOf(sender.track) !== -1) {
          pc.removeTrack(sender);
        }
      });
    };
  }
};

// Expose public methods.
module.exports = {
  shimOnTrack: firefoxShim.shimOnTrack,
  shimSourceObject: firefoxShim.shimSourceObject,
  shimPeerConnection: firefoxShim.shimPeerConnection,
  shimRemoveStream: firefoxShim.shimRemoveStream,
  shimGetUserMedia: __webpack_require__(65)
};


/***/ }),
/* 65 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var utils = __webpack_require__(7);
var logging = utils.log;

// Expose public methods.
module.exports = function(window) {
  var browserDetails = utils.detectBrowser(window);
  var navigator = window && window.navigator;
  var MediaStreamTrack = window && window.MediaStreamTrack;

  var shimError_ = function(e) {
    return {
      name: {
        InternalError: 'NotReadableError',
        NotSupportedError: 'TypeError',
        PermissionDeniedError: 'NotAllowedError',
        SecurityError: 'NotAllowedError'
      }[e.name] || e.name,
      message: {
        'The operation is insecure.': 'The request is not allowed by the ' +
        'user agent or the platform in the current context.'
      }[e.message] || e.message,
      constraint: e.constraint,
      toString: function() {
        return this.name + (this.message && ': ') + this.message;
      }
    };
  };

  // getUserMedia constraints shim.
  var getUserMedia_ = function(constraints, onSuccess, onError) {
    var constraintsToFF37_ = function(c) {
      if (typeof c !== 'object' || c.require) {
        return c;
      }
      var require = [];
      Object.keys(c).forEach(function(key) {
        if (key === 'require' || key === 'advanced' || key === 'mediaSource') {
          return;
        }
        var r = c[key] = (typeof c[key] === 'object') ?
            c[key] : {ideal: c[key]};
        if (r.min !== undefined ||
            r.max !== undefined || r.exact !== undefined) {
          require.push(key);
        }
        if (r.exact !== undefined) {
          if (typeof r.exact === 'number') {
            r. min = r.max = r.exact;
          } else {
            c[key] = r.exact;
          }
          delete r.exact;
        }
        if (r.ideal !== undefined) {
          c.advanced = c.advanced || [];
          var oc = {};
          if (typeof r.ideal === 'number') {
            oc[key] = {min: r.ideal, max: r.ideal};
          } else {
            oc[key] = r.ideal;
          }
          c.advanced.push(oc);
          delete r.ideal;
          if (!Object.keys(r).length) {
            delete c[key];
          }
        }
      });
      if (require.length) {
        c.require = require;
      }
      return c;
    };
    constraints = JSON.parse(JSON.stringify(constraints));
    if (browserDetails.version < 38) {
      logging('spec: ' + JSON.stringify(constraints));
      if (constraints.audio) {
        constraints.audio = constraintsToFF37_(constraints.audio);
      }
      if (constraints.video) {
        constraints.video = constraintsToFF37_(constraints.video);
      }
      logging('ff37: ' + JSON.stringify(constraints));
    }
    return navigator.mozGetUserMedia(constraints, onSuccess, function(e) {
      onError(shimError_(e));
    });
  };

  // Returns the result of getUserMedia as a Promise.
  var getUserMediaPromise_ = function(constraints) {
    return new Promise(function(resolve, reject) {
      getUserMedia_(constraints, resolve, reject);
    });
  };

  // Shim for mediaDevices on older versions.
  if (!navigator.mediaDevices) {
    navigator.mediaDevices = {getUserMedia: getUserMediaPromise_,
      addEventListener: function() { },
      removeEventListener: function() { }
    };
  }
  navigator.mediaDevices.enumerateDevices =
      navigator.mediaDevices.enumerateDevices || function() {
        return new Promise(function(resolve) {
          var infos = [
            {kind: 'audioinput', deviceId: 'default', label: '', groupId: ''},
            {kind: 'videoinput', deviceId: 'default', label: '', groupId: ''}
          ];
          resolve(infos);
        });
      };

  if (browserDetails.version < 41) {
    // Work around http://bugzil.la/1169665
    var orgEnumerateDevices =
        navigator.mediaDevices.enumerateDevices.bind(navigator.mediaDevices);
    navigator.mediaDevices.enumerateDevices = function() {
      return orgEnumerateDevices().then(undefined, function(e) {
        if (e.name === 'NotFoundError') {
          return [];
        }
        throw e;
      });
    };
  }
  if (browserDetails.version < 49) {
    var origGetUserMedia = navigator.mediaDevices.getUserMedia.
        bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = function(c) {
      return origGetUserMedia(c).then(function(stream) {
        // Work around https://bugzil.la/802326
        if (c.audio && !stream.getAudioTracks().length ||
            c.video && !stream.getVideoTracks().length) {
          stream.getTracks().forEach(function(track) {
            track.stop();
          });
          throw new DOMException('The object can not be found here.',
                                 'NotFoundError');
        }
        return stream;
      }, function(e) {
        return Promise.reject(shimError_(e));
      });
    };
  }
  if (!(browserDetails.version > 55 &&
      'autoGainControl' in navigator.mediaDevices.getSupportedConstraints())) {
    var remap = function(obj, a, b) {
      if (a in obj && !(b in obj)) {
        obj[b] = obj[a];
        delete obj[a];
      }
    };

    var nativeGetUserMedia = navigator.mediaDevices.getUserMedia.
        bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = function(c) {
      if (typeof c === 'object' && typeof c.audio === 'object') {
        c = JSON.parse(JSON.stringify(c));
        remap(c.audio, 'autoGainControl', 'mozAutoGainControl');
        remap(c.audio, 'noiseSuppression', 'mozNoiseSuppression');
      }
      return nativeGetUserMedia(c);
    };

    if (MediaStreamTrack && MediaStreamTrack.prototype.getSettings) {
      var nativeGetSettings = MediaStreamTrack.prototype.getSettings;
      MediaStreamTrack.prototype.getSettings = function() {
        var obj = nativeGetSettings.apply(this, arguments);
        remap(obj, 'mozAutoGainControl', 'autoGainControl');
        remap(obj, 'mozNoiseSuppression', 'noiseSuppression');
        return obj;
      };
    }

    if (MediaStreamTrack && MediaStreamTrack.prototype.applyConstraints) {
      var nativeApplyConstraints = MediaStreamTrack.prototype.applyConstraints;
      MediaStreamTrack.prototype.applyConstraints = function(c) {
        if (this.kind === 'audio' && typeof c === 'object') {
          c = JSON.parse(JSON.stringify(c));
          remap(c, 'autoGainControl', 'mozAutoGainControl');
          remap(c, 'noiseSuppression', 'mozNoiseSuppression');
        }
        return nativeApplyConstraints.apply(this, [c]);
      };
    }
  }
  navigator.getUserMedia = function(constraints, onSuccess, onError) {
    if (browserDetails.version < 44) {
      return getUserMedia_(constraints, onSuccess, onError);
    }
    // Replace Firefox 44+'s deprecation warning with unprefixed version.
    utils.deprecated('navigator.getUserMedia',
        'navigator.mediaDevices.getUserMedia');
    navigator.mediaDevices.getUserMedia(constraints).then(onSuccess, onError);
  };
};


/***/ }),
/* 66 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */

var utils = __webpack_require__(7);

var safariShim = {
  // TODO: DrAlex, should be here, double check against LayoutTests

  // TODO: once the back-end for the mac port is done, add.
  // TODO: check for webkitGTK+
  // shimPeerConnection: function() { },

  shimLocalStreamsAPI: function(window) {
    if (typeof window !== 'object' || !window.RTCPeerConnection) {
      return;
    }
    if (!('getLocalStreams' in window.RTCPeerConnection.prototype)) {
      window.RTCPeerConnection.prototype.getLocalStreams = function() {
        if (!this._localStreams) {
          this._localStreams = [];
        }
        return this._localStreams;
      };
    }
    if (!('getStreamById' in window.RTCPeerConnection.prototype)) {
      window.RTCPeerConnection.prototype.getStreamById = function(id) {
        var result = null;
        if (this._localStreams) {
          this._localStreams.forEach(function(stream) {
            if (stream.id === id) {
              result = stream;
            }
          });
        }
        if (this._remoteStreams) {
          this._remoteStreams.forEach(function(stream) {
            if (stream.id === id) {
              result = stream;
            }
          });
        }
        return result;
      };
    }
    if (!('addStream' in window.RTCPeerConnection.prototype)) {
      var _addTrack = window.RTCPeerConnection.prototype.addTrack;
      window.RTCPeerConnection.prototype.addStream = function(stream) {
        if (!this._localStreams) {
          this._localStreams = [];
        }
        if (this._localStreams.indexOf(stream) === -1) {
          this._localStreams.push(stream);
        }
        var self = this;
        stream.getTracks().forEach(function(track) {
          _addTrack.call(self, track, stream);
        });
      };

      window.RTCPeerConnection.prototype.addTrack = function(track, stream) {
        if (stream) {
          if (!this._localStreams) {
            this._localStreams = [stream];
          } else if (this._localStreams.indexOf(stream) === -1) {
            this._localStreams.push(stream);
          }
        }
        _addTrack.call(this, track, stream);
      };
    }
    if (!('removeStream' in window.RTCPeerConnection.prototype)) {
      window.RTCPeerConnection.prototype.removeStream = function(stream) {
        if (!this._localStreams) {
          this._localStreams = [];
        }
        var index = this._localStreams.indexOf(stream);
        if (index === -1) {
          return;
        }
        this._localStreams.splice(index, 1);
        var self = this;
        var tracks = stream.getTracks();
        this.getSenders().forEach(function(sender) {
          if (tracks.indexOf(sender.track) !== -1) {
            self.removeTrack(sender);
          }
        });
      };
    }
  },
  shimRemoteStreamsAPI: function(window) {
    if (typeof window !== 'object' || !window.RTCPeerConnection) {
      return;
    }
    if (!('getRemoteStreams' in window.RTCPeerConnection.prototype)) {
      window.RTCPeerConnection.prototype.getRemoteStreams = function() {
        return this._remoteStreams ? this._remoteStreams : [];
      };
    }
    if (!('onaddstream' in window.RTCPeerConnection.prototype)) {
      Object.defineProperty(window.RTCPeerConnection.prototype, 'onaddstream', {
        get: function() {
          return this._onaddstream;
        },
        set: function(f) {
          if (this._onaddstream) {
            this.removeEventListener('addstream', this._onaddstream);
            this.removeEventListener('track', this._onaddstreampoly);
          }
          this.addEventListener('addstream', this._onaddstream = f);
          this.addEventListener('track', this._onaddstreampoly = function(e) {
            var stream = e.streams[0];
            if (!this._remoteStreams) {
              this._remoteStreams = [];
            }
            if (this._remoteStreams.indexOf(stream) >= 0) {
              return;
            }
            this._remoteStreams.push(stream);
            var event = new Event('addstream');
            event.stream = e.streams[0];
            this.dispatchEvent(event);
          }.bind(this));
        }
      });
    }
  },
  shimCallbacksAPI: function(window) {
    if (typeof window !== 'object' || !window.RTCPeerConnection) {
      return;
    }
    var prototype = window.RTCPeerConnection.prototype;
    var createOffer = prototype.createOffer;
    var createAnswer = prototype.createAnswer;
    var setLocalDescription = prototype.setLocalDescription;
    var setRemoteDescription = prototype.setRemoteDescription;
    var addIceCandidate = prototype.addIceCandidate;

    prototype.createOffer = function(successCallback, failureCallback) {
      var options = (arguments.length >= 2) ? arguments[2] : arguments[0];
      var promise = createOffer.apply(this, [options]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };

    prototype.createAnswer = function(successCallback, failureCallback) {
      var options = (arguments.length >= 2) ? arguments[2] : arguments[0];
      var promise = createAnswer.apply(this, [options]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };

    var withCallback = function(description, successCallback, failureCallback) {
      var promise = setLocalDescription.apply(this, [description]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };
    prototype.setLocalDescription = withCallback;

    withCallback = function(description, successCallback, failureCallback) {
      var promise = setRemoteDescription.apply(this, [description]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };
    prototype.setRemoteDescription = withCallback;

    withCallback = function(candidate, successCallback, failureCallback) {
      var promise = addIceCandidate.apply(this, [candidate]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };
    prototype.addIceCandidate = withCallback;
  },
  shimGetUserMedia: function(window) {
    var navigator = window && window.navigator;

    if (!navigator.getUserMedia) {
      if (navigator.webkitGetUserMedia) {
        navigator.getUserMedia = navigator.webkitGetUserMedia.bind(navigator);
      } else if (navigator.mediaDevices &&
          navigator.mediaDevices.getUserMedia) {
        navigator.getUserMedia = function(constraints, cb, errcb) {
          navigator.mediaDevices.getUserMedia(constraints)
          .then(cb, errcb);
        }.bind(navigator);
      }
    }
  },
  shimRTCIceServerUrls: function(window) {
    // migrate from non-spec RTCIceServer.url to RTCIceServer.urls
    var OrigPeerConnection = window.RTCPeerConnection;
    window.RTCPeerConnection = function(pcConfig, pcConstraints) {
      if (pcConfig && pcConfig.iceServers) {
        var newIceServers = [];
        for (var i = 0; i < pcConfig.iceServers.length; i++) {
          var server = pcConfig.iceServers[i];
          if (!server.hasOwnProperty('urls') &&
              server.hasOwnProperty('url')) {
            utils.deprecated('RTCIceServer.url', 'RTCIceServer.urls');
            server = JSON.parse(JSON.stringify(server));
            server.urls = server.url;
            delete server.url;
            newIceServers.push(server);
          } else {
            newIceServers.push(pcConfig.iceServers[i]);
          }
        }
        pcConfig.iceServers = newIceServers;
      }
      return new OrigPeerConnection(pcConfig, pcConstraints);
    };
    window.RTCPeerConnection.prototype = OrigPeerConnection.prototype;
    // wrap static methods. Currently just generateCertificate.
    if ('generateCertificate' in window.RTCPeerConnection) {
      Object.defineProperty(window.RTCPeerConnection, 'generateCertificate', {
        get: function() {
          return OrigPeerConnection.generateCertificate;
        }
      });
    }
  },
  shimTrackEventTransceiver: function(window) {
    // Add event.transceiver member over deprecated event.receiver
    if (typeof window === 'object' && window.RTCPeerConnection &&
        ('receiver' in window.RTCTrackEvent.prototype) &&
        // can't check 'transceiver' in window.RTCTrackEvent.prototype, as it is
        // defined for some reason even when window.RTCTransceiver is not.
        !window.RTCTransceiver) {
      Object.defineProperty(window.RTCTrackEvent.prototype, 'transceiver', {
        get: function() {
          return {receiver: this.receiver};
        }
      });
    }
  },

  shimCreateOfferLegacy: function(window) {
    var origCreateOffer = window.RTCPeerConnection.prototype.createOffer;
    window.RTCPeerConnection.prototype.createOffer = function(offerOptions) {
      var pc = this;
      if (offerOptions) {
        var audioTransceiver = pc.getTransceivers().find(function(transceiver) {
          return transceiver.sender.track &&
              transceiver.sender.track.kind === 'audio';
        });
        if (offerOptions.offerToReceiveAudio === false && audioTransceiver) {
          if (audioTransceiver.direction === 'sendrecv') {
            audioTransceiver.setDirection('sendonly');
          } else if (audioTransceiver.direction === 'recvonly') {
            audioTransceiver.setDirection('inactive');
          }
        } else if (offerOptions.offerToReceiveAudio === true &&
            !audioTransceiver) {
          pc.addTransceiver('audio');
        }

        var videoTransceiver = pc.getTransceivers().find(function(transceiver) {
          return transceiver.sender.track &&
              transceiver.sender.track.kind === 'video';
        });
        if (offerOptions.offerToReceiveVideo === false && videoTransceiver) {
          if (videoTransceiver.direction === 'sendrecv') {
            videoTransceiver.setDirection('sendonly');
          } else if (videoTransceiver.direction === 'recvonly') {
            videoTransceiver.setDirection('inactive');
          }
        } else if (offerOptions.offerToReceiveVideo === true &&
            !videoTransceiver) {
          pc.addTransceiver('video');
        }
      }
      return origCreateOffer.apply(pc, arguments);
    };
  }
};

// Expose public methods.
module.exports = {
  shimCallbacksAPI: safariShim.shimCallbacksAPI,
  shimLocalStreamsAPI: safariShim.shimLocalStreamsAPI,
  shimRemoteStreamsAPI: safariShim.shimRemoteStreamsAPI,
  shimGetUserMedia: safariShim.shimGetUserMedia,
  shimRTCIceServerUrls: safariShim.shimRTCIceServerUrls,
  shimTrackEventTransceiver: safariShim.shimTrackEventTransceiver,
  shimCreateOfferLegacy: safariShim.shimCreateOfferLegacy
  // TODO
  // shimPeerConnection: safariShim.shimPeerConnection
};


/***/ }),
/* 67 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/*
 *  Copyright (c) 2017 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var SDPUtils = __webpack_require__(38);
var utils = __webpack_require__(7);

// Wraps the peerconnection event eventNameToWrap in a function
// which returns the modified event object.
function wrapPeerConnectionEvent(window, eventNameToWrap, wrapper) {
  if (!window.RTCPeerConnection) {
    return;
  }
  var proto = window.RTCPeerConnection.prototype;
  var nativeAddEventListener = proto.addEventListener;
  proto.addEventListener = function(nativeEventName, cb) {
    if (nativeEventName !== eventNameToWrap) {
      return nativeAddEventListener.apply(this, arguments);
    }
    var wrappedCallback = function(e) {
      cb(wrapper(e));
    };
    this._eventMap = this._eventMap || {};
    this._eventMap[cb] = wrappedCallback;
    return nativeAddEventListener.apply(this, [nativeEventName,
      wrappedCallback]);
  };

  var nativeRemoveEventListener = proto.removeEventListener;
  proto.removeEventListener = function(nativeEventName, cb) {
    if (nativeEventName !== eventNameToWrap || !this._eventMap
        || !this._eventMap[cb]) {
      return nativeRemoveEventListener.apply(this, arguments);
    }
    var unwrappedCb = this._eventMap[cb];
    delete this._eventMap[cb];
    return nativeRemoveEventListener.apply(this, [nativeEventName,
      unwrappedCb]);
  };

  Object.defineProperty(proto, 'on' + eventNameToWrap, {
    get: function() {
      return this['_on' + eventNameToWrap];
    },
    set: function(cb) {
      if (this['_on' + eventNameToWrap]) {
        this.removeEventListener(eventNameToWrap,
            this['_on' + eventNameToWrap]);
        delete this['_on' + eventNameToWrap];
      }
      if (cb) {
        this.addEventListener(eventNameToWrap,
            this['_on' + eventNameToWrap] = cb);
      }
    }
  });
}

module.exports = {
  shimRTCIceCandidate: function(window) {
    // foundation is arbitrarily chosen as an indicator for full support for
    // https://w3c.github.io/webrtc-pc/#rtcicecandidate-interface
    if (window.RTCIceCandidate && 'foundation' in
        window.RTCIceCandidate.prototype) {
      return;
    }

    var NativeRTCIceCandidate = window.RTCIceCandidate;
    window.RTCIceCandidate = function(args) {
      // Remove the a= which shouldn't be part of the candidate string.
      if (typeof args === 'object' && args.candidate &&
          args.candidate.indexOf('a=') === 0) {
        args = JSON.parse(JSON.stringify(args));
        args.candidate = args.candidate.substr(2);
      }

      // Augment the native candidate with the parsed fields.
      var nativeCandidate = new NativeRTCIceCandidate(args);
      var parsedCandidate = SDPUtils.parseCandidate(args.candidate);
      var augmentedCandidate = Object.assign(nativeCandidate,
          parsedCandidate);

      // Add a serializer that does not serialize the extra attributes.
      augmentedCandidate.toJSON = function() {
        return {
          candidate: augmentedCandidate.candidate,
          sdpMid: augmentedCandidate.sdpMid,
          sdpMLineIndex: augmentedCandidate.sdpMLineIndex,
          usernameFragment: augmentedCandidate.usernameFragment,
        };
      };
      return augmentedCandidate;
    };

    // Hook up the augmented candidate in onicecandidate and
    // addEventListener('icecandidate', ...)
    wrapPeerConnectionEvent(window, 'icecandidate', function(e) {
      if (e.candidate) {
        Object.defineProperty(e, 'candidate', {
          value: new window.RTCIceCandidate(e.candidate),
          writable: 'false'
        });
      }
      return e;
    });
  },

  // shimCreateObjectURL must be called before shimSourceObject to avoid loop.

  shimCreateObjectURL: function(window) {
    var URL = window && window.URL;

    if (!(typeof window === 'object' && window.HTMLMediaElement &&
          'srcObject' in window.HTMLMediaElement.prototype &&
        URL.createObjectURL && URL.revokeObjectURL)) {
      // Only shim CreateObjectURL using srcObject if srcObject exists.
      return undefined;
    }

    var nativeCreateObjectURL = URL.createObjectURL.bind(URL);
    var nativeRevokeObjectURL = URL.revokeObjectURL.bind(URL);
    var streams = new Map(), newId = 0;

    URL.createObjectURL = function(stream) {
      if ('getTracks' in stream) {
        var url = 'polyblob:' + (++newId);
        streams.set(url, stream);
        utils.deprecated('URL.createObjectURL(stream)',
            'elem.srcObject = stream');
        return url;
      }
      return nativeCreateObjectURL(stream);
    };
    URL.revokeObjectURL = function(url) {
      nativeRevokeObjectURL(url);
      streams.delete(url);
    };

    var dsc = Object.getOwnPropertyDescriptor(window.HTMLMediaElement.prototype,
                                              'src');
    Object.defineProperty(window.HTMLMediaElement.prototype, 'src', {
      get: function() {
        return dsc.get.apply(this);
      },
      set: function(url) {
        this.srcObject = streams.get(url) || null;
        return dsc.set.apply(this, [url]);
      }
    });

    var nativeSetAttribute = window.HTMLMediaElement.prototype.setAttribute;
    window.HTMLMediaElement.prototype.setAttribute = function() {
      if (arguments.length === 2 &&
          ('' + arguments[0]).toLowerCase() === 'src') {
        this.srcObject = streams.get(arguments[1]) || null;
      }
      return nativeSetAttribute.apply(this, arguments);
    };
  }
};


/***/ }),
/* 68 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", { value: true });
var Conversation_1 = __webpack_require__(29);
exports.Conversation = Conversation_1.Conversation;
var Message_1 = __webpack_require__(22);
exports.Message = Message_1.Message;
var Messenger_1 = __webpack_require__(30);
exports.Messenger = Messenger_1.Messenger;

/***/ })
/******/ ]);
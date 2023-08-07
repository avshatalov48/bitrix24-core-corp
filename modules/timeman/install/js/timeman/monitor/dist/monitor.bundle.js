/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue_vuex,main_md5,main_sha1,timeman_const,ui_notification,timeman_monitorReport,timeman_dateformatter,timeman_timeformatter,pull_client,main_core,im_v2_lib_desktopApi) {
	'use strict';

	var Code = /*#__PURE__*/function () {
	  function Code() {
	    babelHelpers.classCallCheck(this, Code);
	  }
	  babelHelpers.createClass(Code, null, [{
	    key: "createPublic",
	    value: function createPublic() {
	      for (var _len = arguments.length, params = new Array(_len), _key = 0; _key < _len; _key++) {
	        params[_key] = arguments[_key];
	      }
	      return main_md5.md5(params.join(''));
	    }
	  }, {
	    key: "createPrivate",
	    value: function createPrivate() {
	      for (var _len2 = arguments.length, params = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
	        params[_key2] = arguments[_key2];
	      }
	      return main_sha1.sha1(params.join(''));
	    }
	  }, {
	    key: "createSecret",
	    value: function createSecret() {
	      if (typeof BXDesktopSystem === 'undefined') {
	        return null;
	      }
	      return Code.createPrivate(BXDesktopSystem.UserAccount(), BXDesktopSystem.UserOsMark(), +Date.now());
	    }
	  }, {
	    key: "getDesktopCode",
	    value: function getDesktopCode() {
	      if (typeof BXDesktopSystem === 'undefined') {
	        return null;
	      }
	      return Code.createPublic(BXDesktopSystem.UserAccount(), BXDesktopSystem.UserOsMark());
	    }
	  }]);
	  return Code;
	}();

	var Time = /*#__PURE__*/function () {
	  function Time() {
	    babelHelpers.classCallCheck(this, Time);
	  }
	  babelHelpers.createClass(Time, null, [{
	    key: "calculateInEntityOnADate",
	    value: function calculateInEntityOnADate(state, entity, date) {
	      return state.history.filter(function (entry) {
	        return entry.privateCode === entity.privateCode && entry.dateLog === date;
	      }).map(Time.calculateInEntry).reduce(function (sum, time) {
	        return sum + time;
	      }, 0);
	    }
	  }, {
	    key: "calculateInEntry",
	    value: function calculateInEntry(entry) {
	      var time = entry.time.map(function (interval) {
	        var finish = interval.finish ? new Date(interval.finish) : new Date();
	        return finish - new Date(interval.start);
	      }).reduce(function (sum, interval) {
	        return sum + interval;
	      }, 0);
	      return Math.round(time / 1000);
	    }
	  }, {
	    key: "formatDateToTime",
	    value: function formatDateToTime(date) {
	      var addZero = function addZero(num) {
	        return num >= 0 && num <= 9 ? '0' + num : num;
	      };
	      var hour = date.getHours();
	      var min = addZero(date.getMinutes());
	      return hour + ':' + min;
	    }
	  }, {
	    key: "msToSec",
	    value: function msToSec(ms) {
	      return ms / 1000;
	    }
	  }]);
	  return Time;
	}();

	var Logger = /*#__PURE__*/function () {
	  function Logger() {
	    babelHelpers.classCallCheck(this, Logger);
	    this.storageKey = 'bx-timeman-monitor-logger-enabled';
	    this.enabled = null;
	  }
	  babelHelpers.createClass(Logger, [{
	    key: "start",
	    value: function start() {
	      if (!monitor.isEnabled()) {
	        return;
	      }
	      this.enabled = true;
	      if (typeof window.localStorage !== 'undefined') {
	        try {
	          window.localStorage.setItem(this.storageKey, 'Y');
	        } catch (e) {}
	      }
	      return this.enabled;
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      this.enabled = false;
	      if (typeof window.localStorage !== 'undefined') {
	        try {
	          window.localStorage.removeItem(this.storageKey);
	        } catch (e) {}
	      }
	      return this.enabled;
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      if (!monitor.isEnabled()) {
	        return false;
	      }
	      if (this.enabled === null) {
	        if (typeof window.localStorage !== 'undefined') {
	          try {
	            this.enabled = window.localStorage.getItem(this.storageKey) === 'Y';
	          } catch (e) {}
	        }
	      }
	      return this.enabled === true;
	    }
	  }, {
	    key: "log",
	    value: function log() {
	      if (this.isEnabled()) {
	        var _console;
	        (_console = console).log.apply(_console, arguments);
	      }
	    }
	  }, {
	    key: "info",
	    value: function info() {
	      if (this.isEnabled()) {
	        var _console2;
	        (_console2 = console).info.apply(_console2, arguments);
	      }
	    }
	  }, {
	    key: "warn",
	    value: function warn() {
	      if (this.isEnabled()) {
	        var _console3;
	        (_console3 = console).warn.apply(_console3, arguments);
	      }
	    }
	  }, {
	    key: "error",
	    value: function error() {
	      var _console4;
	      (_console4 = console).error.apply(_console4, arguments);
	    }
	  }, {
	    key: "trace",
	    value: function trace() {
	      var _console5;
	      (_console5 = console).trace.apply(_console5, arguments);
	    }
	  }]);
	  return Logger;
	}();
	var logger = new Logger();

	var Debug = /*#__PURE__*/function () {
	  function Debug() {
	    babelHelpers.classCallCheck(this, Debug);
	    this.enabled = false;
	  }
	  babelHelpers.createClass(Debug, [{
	    key: "isEnabled",
	    value: function isEnabled() {
	      return this.enabled;
	    }
	  }, {
	    key: "enable",
	    value: function enable() {
	      this.enabled = true;
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.enabled = false;
	    }
	  }, {
	    key: "log",
	    value: function log() {
	      if (!this.isEnabled()) {
	        return;
	      }
	      var text = this.getLogMessage.apply(this, arguments);
	      BX.desktop.log(main_core.Loc.getMessage('USER_ID') + '.monitor.log', text.substr(3));
	    }
	  }, {
	    key: "space",
	    value: function space() {
	      if (!this.isEnabled()) {
	        return;
	      }
	      BX.desktop.log(main_core.Loc.getMessage('USER_ID') + '.monitor.log', ' ');
	    }
	  }, {
	    key: "getLogMessage",
	    value: function getLogMessage() {
	      if (!this.isEnabled()) {
	        return;
	      }
	      var text = '';
	      for (var i = 0; i < arguments.length; i++) {
	        if (arguments[i] instanceof Error) {
	          text = arguments[i].message + "\n" + arguments[i].stack;
	        } else {
	          try {
	            text = text + ' | ' + (babelHelpers["typeof"](arguments[i]) == 'object' ? JSON.stringify(arguments[i]) : arguments[i]);
	          } catch (e) {
	            text = text + ' | (circular structure)';
	          }
	        }
	      }
	      return text;
	    }
	  }]);
	  return Debug;
	}();
	var debug = new Debug();

	var ActionTimer = /*#__PURE__*/function () {
	  function ActionTimer() {
	    babelHelpers.classCallCheck(this, ActionTimer);
	    this.actionsCollection = {};
	  }
	  babelHelpers.createClass(ActionTimer, [{
	    key: "start",
	    value: function start(key) {
	      this.actionsCollection[key] = {};
	      this.actionsCollection[key].start = Date.now();
	    }
	  }, {
	    key: "finish",
	    value: function finish(key) {
	      if (!this.actionsCollection[key] || !this.actionsCollection[key].start || this.actionsCollection[key].finish) {
	        return;
	      }
	      this.actionsCollection[key].finish = Date.now();
	    }
	  }, {
	    key: "getDuration",
	    value: function getDuration(key) {
	      if (!this.actionsCollection[key] || !this.actionsCollection[key].start || !this.actionsCollection[key].finish) {
	        return;
	      }
	      var timeInSeconds = (this.actionsCollection[key].finish - this.actionsCollection[key].start) / 1000;
	      return "ACTION: ".concat(key, ", TIME: ").concat(timeInSeconds.toFixed(2), "s");
	    }
	  }]);
	  return ActionTimer;
	}();
	var actionTimer = new ActionTimer();

	var Notification = /*#__PURE__*/function () {
	  function Notification(title, text, callback) {
	    babelHelpers.classCallCheck(this, Notification);
	    this.title = title.toString();
	    this.text = text.toString();
	    if (main_core.Type.isFunction(callback)) {
	      this.callback = callback;
	    }
	    return this;
	  }
	  babelHelpers.createClass(Notification, [{
	    key: "show",
	    value: function show() {
	      var _this = this;
	      BXIM.playSound('newMessage1');
	      var messageTemplate = BXIM.notify.createNotify({
	        id: 0,
	        type: 4,
	        date: new Date(),
	        params: {},
	        title: this.title,
	        text: this.text
	      }, true);
	      var messageJs = "\n\t\t\tvar notify = BX.findChildByClassName(document.body, \"bx-notifier-item\");\n\t\t\t\n\t\t\tnotify.style.cursor = \"pointer\";\n\t\t\t\n\t\t\tBX.bind(notify, \"click\", function() {\n\t\t\t\tBX.desktop.onCustomEvent(\"main\", \"bxImClickPwtMessage\", []);\n\t\t\t\tBX.desktop.windowCommand(\"close\")\n\t\t\t});\n\t\t\t\n\t\t\tBX.bind(BX.findChildByClassName(notify, \"bx-notifier-item-delete\"), \"click\", function(event) { \n\t\t\t\tBX.desktop.windowCommand(\"close\"); \n\t\t\t\tBX.MessengerCommon.preventDefault(event); \n\t\t\t});\n\t\t\t\n\t\t\tBX.bind(notify, \"contextmenu\", function() {\n\t\t\t\tBX.desktop.windowCommand(\"close\")\n\t\t\t});\n\t\t";
	      BXIM.desktop.openNewMessage('pwt' + new Date(), messageTemplate, messageJs);
	      BX.desktop.addCustomEvent('bxImClickPwtMessage', function () {
	        return _this.click();
	      });
	    }
	  }, {
	    key: "click",
	    value: function click() {
	      if (main_core.Type.isFunction(this.callback)) {
	        this.callback();
	      }
	      BX.desktop.removeCustomEvents('bxImClickPwtMessage');
	    }
	  }]);
	  return Notification;
	}();

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var MonitorModel = /*#__PURE__*/function (_VuexBuilderModel) {
	  babelHelpers.inherits(MonitorModel, _VuexBuilderModel);
	  function MonitorModel() {
	    babelHelpers.classCallCheck(this, MonitorModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MonitorModel).apply(this, arguments));
	  }
	  babelHelpers.createClass(MonitorModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'monitor';
	    }
	  }, {
	    key: "getSaveTimeout",
	    value: function getSaveTimeout() {
	      return 1000;
	    }
	  }, {
	    key: "getLoadTimeout",
	    value: function getLoadTimeout() {
	      return false;
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        config: {
	          secret: Code.createSecret(),
	          desktopCode: Code.getDesktopCode(),
	          otherTime: this.getVariable('config.otherTime', 1800000),
	          shortAbsenceTime: this.getVariable('config.shortAbsenceTime', 1800000),
	          pausedUntil: null,
	          lastSuccessfulSendDate: null,
	          lastRemindDate: null,
	          grantingPermissionDate: null,
	          deferredGrantingPermissionShowDate: null
	        },
	        reportState: {
	          dateLog: this.getDateLog(),
	          comments: []
	        },
	        personal: [],
	        strictlyWorking: [],
	        entity: [],
	        history: [],
	        sentQueue: []
	      };
	    }
	  }, {
	    key: "getEntityState",
	    value: function getEntityState() {
	      return {
	        type: timeman_const.EntityType.unknown,
	        title: '',
	        publicCode: '',
	        privateCode: '',
	        comments: [],
	        extra: {}
	      };
	    }
	  }, {
	    key: "getHistoryState",
	    value: function getHistoryState() {
	      return {
	        dateLog: this.getDateLog(),
	        privateCode: '',
	        time: [{
	          start: new Date(),
	          preFinish: null,
	          finish: null
	        }]
	      };
	    }
	  }, {
	    key: "getSentQueueState",
	    value: function getSentQueueState() {
	      return {
	        dateLog: this.getDateLog(),
	        comment: '',
	        historyPackage: [],
	        chartPackage: [],
	        desktopCode: ''
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this = this;
	      return {
	        setDateLog: function setDateLog(store, payload) {
	          if (main_core.Type.isString(payload)) {
	            var date = new Date(payload);
	            if (main_core.Type.isDate(date) && !isNaN(date) && payload.length === 10) {
	              store.commit('setDateLog', payload);
	            }
	          }
	        },
	        refreshDateLog: function refreshDateLog(store) {
	          if (main_core.Type.isArrayFilled(store.state.history)) {
	            var firstHistoryEntryDateLog = store.state.history[0].dateLog;
	            _this.getActions().setDateLog(store, firstHistoryEntryDateLog);
	            logger.log("Report date is set for ".concat(firstHistoryEntryDateLog));
	            debug.log("Report date is set for ".concat(firstHistoryEntryDateLog));
	          } else {
	            var dateLog = _this.getDateLog();
	            _this.getActions().setDateLog(store, dateLog);
	            logger.log("Report date is set for ".concat(dateLog));
	            debug.log("Report date is set for ".concat(dateLog));
	          }
	        },
	        setLastRemindDate: function setLastRemindDate(store, date) {
	          store.commit('setLastRemindDate', date);
	        },
	        grantPermission: function grantPermission(store) {
	          store.commit('setGrantingPermissionDate', new Date());
	        },
	        showGrantingPermissionLater: function showGrantingPermissionLater(store) {
	          var date = new Date();
	          date.setDate(date.getDate() + 1);
	          var formattedDate = MonitorModel.prototype.formatDateLog(date);
	          store.commit('setDeferredGrantingPermissionShowDate', formattedDate);
	        },
	        setLastSuccessfulSendDate: function setLastSuccessfulSendDate(store, date) {
	          if (main_core.Type.isDate(date) && !isNaN(date)) {
	            store.commit('setLastSuccessfulSendDate', date);
	          }
	        },
	        addPersonal: function addPersonal(store, privateCode) {
	          store.commit('addPersonal', _this.validatePersonal(privateCode));
	        },
	        removePersonal: function removePersonal(store, privateCode) {
	          store.commit('removePersonal', _this.validatePersonal(privateCode));
	        },
	        addToStrictlyWorking: function addToStrictlyWorking(store, privateCode) {
	          store.commit('addToStrictlyWorking', privateCode);
	        },
	        removeFromStrictlyWorking: function removeFromStrictlyWorking(store, privateCode) {
	          store.commit('removeFromStrictlyWorking', privateCode);
	        },
	        clearStrictlyWorking: function clearStrictlyWorking(store) {
	          store.commit('clearStrictlyWorking');
	        },
	        clearPersonal: function clearPersonal(store) {
	          store.commit('clearPersonal');
	        },
	        addEntity: function addEntity(store, payload) {
	          var result = _this.validateEntity(_objectSpread({}, payload));
	          if (result.type !== timeman_const.EntityType.absence && result.type !== timeman_const.EntityType.custom) {
	            result.publicCode = Code.createPublic(result.title);
	            result.privateCode = Code.createPrivate(result.title, store.state.config.secret);
	          } else {
	            var date = new Date();
	            var timestamp = +date;
	            result.publicCode = Code.createPublic(result.title, timestamp);
	            result.privateCode = Code.createPrivate(result.title, timestamp, store.state.config.secret);
	            if (result.type === timeman_const.EntityType.absence) {
	              result.extra = {
	                timeStart: date
	              };
	              result.title += ' ' + main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE_FROM_TIME') + ' ' + Time.formatDateToTime(result.extra.timeStart);
	            }
	          }
	          store.commit('addEntity', _objectSpread(_objectSpread({}, _this.getEntityState()), result));
	          if (!store.state.strictlyWorking.find(function (privateCode) {
	            return privateCode === result.privateCode;
	          })) {
	            var isBitrix24Cp = result.type === timeman_const.EntityType.site && result.title === location.host;
	            var isBitrix24Desktop = result.type === timeman_const.EntityType.app && payload.isBitrix24Desktop;
	            if (isBitrix24Cp || isBitrix24Desktop || result.type === timeman_const.EntityType.custom) {
	              store.commit('addToStrictlyWorking', result.privateCode);
	            }
	          }
	          return result;
	        },
	        removeEntityByPrivateCode: function removeEntityByPrivateCode(store, payload) {
	          store.commit('removeEntityByPrivateCode', payload);
	        },
	        clearEntities: function clearEntities(store) {
	          store.commit('clearEntities');
	        },
	        addHistory: function addHistory(store, payload) {
	          store.commit('finishLastInterval');
	          var result = _this.validateHistory(_objectSpread({}, payload));
	          var entity;
	          var privateCode;
	          var historyEntry = null;
	          if (result.type === timeman_const.EntityType.app || result.type === timeman_const.EntityType.site || result.type === timeman_const.EntityType.unknown || result.type === timeman_const.EntityType.incognito) {
	            entity = _this.getEntityByTitle(store, result.title);
	            privateCode = entity ? entity.privateCode : null;
	            if (!privateCode) {
	              privateCode = _this.getActions().addEntity(store, payload).privateCode;
	            }
	            historyEntry = entity && entity.type === timeman_const.EntityType.site ? _this.getHistoryEntryBySiteUrl(store, result.siteUrl) : _this.getHistoryEntryByPrivateCode(store, privateCode);
	          } else if (result.type === timeman_const.EntityType.absence || result.type === timeman_const.EntityType.custom) {
	            entity = _this.getActions().addEntity(store, payload);
	            privateCode = entity.privateCode;
	          }
	          var logEntity = {
	            type: result.type,
	            title: result.title
	          };
	          if (!historyEntry) {
	            delete result.title;
	            store.commit('addHistory', _objectSpread(_objectSpread(_objectSpread({}, _this.getHistoryState()), result), {}, {
	              privateCode: privateCode
	            }));
	            actionTimer.finish('CATCH_ENTITY');
	            debug.log('Caught new:', logEntity, actionTimer.getDuration('CATCH_ENTITY'));
	            logger.log('Caught new:', logEntity, actionTimer.getDuration('CATCH_ENTITY'));
	            return;
	          }
	          if (result.type !== timeman_const.EntityType.custom) {
	            store.commit('startIntervalForHistoryEntry', historyEntry);
	          }
	          var lastRemindDate = store.state.config.lastRemindDate;
	          var canShowReminder = new Date(lastRemindDate) < new Date(_this.getDateLog());
	          var isHistorySent = _this.getGetters().isHistorySent(store.state);
	          if (!lastRemindDate || canShowReminder && !isHistorySent) {
	            if (_this.getGetters().getWorkingTimeForToday(store.state) >= 32400)
	              //9 hour in seconds
	              {
	                new Notification(main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REMINDER_TITLE'), main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REMINDER_TEXT'), function () {
	                  BX.desktop.windowCommand("show");
	                  BX.desktop.changeTab('im');
	                  BX.MessengerWindow.changeTab('timeman-pwt');
	                }).show();
	                store.commit('setLastRemindDate', _this.getDateLog());
	              }
	          }
	          actionTimer.finish('CATCH_ENTITY');
	          debug.log('Caught:', logEntity, actionTimer.getDuration('CATCH_ENTITY'));
	          logger.log('Caught:', logEntity, actionTimer.getDuration('CATCH_ENTITY'));
	        },
	        preFinishLastInterval: function preFinishLastInterval(store) {
	          store.commit('preFinishLastInterval');
	        },
	        finishLastInterval: function finishLastInterval(store) {
	          store.commit('finishLastInterval');
	        },
	        clearHistory: function clearHistory(store) {
	          store.commit('clearHistory');
	        },
	        createSentQueue: function createSentQueue(store) {
	          if (store.state.history.length === 0) {
	            return;
	          }
	          var sentQueue = _this.collectSentQueue(store);
	          var reportComment = store.state.reportState.comments.find(function (comment) {
	            return comment.dateLog === store.state.reportState.dateLog;
	          });
	          var result = _this.validateSentQueue({
	            dateLog: store.state.reportState.dateLog,
	            comment: reportComment ? reportComment.text : '',
	            historyPackage: sentQueue.history,
	            chartPackage: sentQueue.chart,
	            desktopCode: store.state.config.desktopCode
	          });
	          store.commit('createSentQueue', _objectSpread(_objectSpread({}, _this.getSentQueueState()), result));
	        },
	        clearSentQueue: function clearSentQueue(store) {
	          store.commit('clearSentQueue');
	        },
	        clearSentHistory: function clearSentHistory(store) {
	          var lastSuccessfulSendDate = store.state.config.lastSuccessfulSendDate;
	          if (!lastSuccessfulSendDate) {
	            return;
	          }
	          if (new Date(_this.getDateLog()) > new Date(lastSuccessfulSendDate)) {
	            store.commit('clearStorageBeforeDate', new Date(lastSuccessfulSendDate));
	          }
	        },
	        clearStorageBeforeDate: function clearStorageBeforeDate(store, date) {
	          store.commit('clearStorageBeforeDate', new Date(date));
	        },
	        setComment: function setComment(store, payload) {
	          var entity = store.state.entity.find(function (entity) {
	            return entity.privateCode === payload.privateCode;
	          });
	          if (entity && (main_core.Type.isString(payload.comment) || main_core.Type.isNumber(payload.comment))) {
	            store.commit('setComment', {
	              entity: entity,
	              comment: payload.comment.toString()
	            });
	          }
	        },
	        setPausedUntil: function setPausedUntil(store, dateTime) {
	          if (main_core.Type.isDate(dateTime) && main_core.Type.isNumber(dateTime.getTime()) && dateTime > new Date()) {
	            store.commit('setPausedUntil', dateTime);
	            logger.warn('Monitor paused until ', dateTime.toString());
	            debug.log('Monitor paused until ', dateTime.toString());
	          }
	        },
	        clearPausedUntil: function clearPausedUntil(store) {
	          store.commit('clearPausedUntil');
	        },
	        setReportComment: function setReportComment(store, comment) {
	          store.commit('setReportComment', comment.toString());
	        },
	        processUnfinishedEvents: function processUnfinishedEvents(store) {
	          store.commit('processUnfinishedEvents');
	        },
	        migrateHistory: function migrateHistory(store) {
	          store.commit('migrateHistory');
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this2 = this;
	      return {
	        setDateLog: function setDateLog(state, payload) {
	          state.reportState.dateLog = payload;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setLastSuccessfulSendDate: function setLastSuccessfulSendDate(state, date) {
	          state.config.lastSuccessfulSendDate = date;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setLastRemindDate: function setLastRemindDate(state, date) {
	          state.config.lastRemindDate = date;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setGrantingPermissionDate: function setGrantingPermissionDate(state, date) {
	          state.config.grantingPermissionDate = date;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setDeferredGrantingPermissionShowDate: function setDeferredGrantingPermissionShowDate(state, date) {
	          state.config.deferredGrantingPermissionShowDate = date;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addPersonal: function addPersonal(state, payload) {
	          state.personal.push(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        removePersonal: function removePersonal(state, payload) {
	          state.personal = state.personal.filter(function (privateCode) {
	            return privateCode !== payload;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addToStrictlyWorking: function addToStrictlyWorking(state, payload) {
	          state.strictlyWorking.push(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        removeFromStrictlyWorking: function removeFromStrictlyWorking(state, payload) {
	          state.strictlyWorking = state.strictlyWorking.filter(function (publicCode) {
	            return publicCode !== payload;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearStrictlyWorking: function clearStrictlyWorking(state) {
	          state.strictlyWorking = [];
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearPersonal: function clearPersonal(state) {
	          state.personal = [];
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addEntity: function addEntity(state, payload) {
	          state.entity.push(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        removeEntityByPrivateCode: function removeEntityByPrivateCode(state, payload) {
	          state.entity = state.entity.filter(function (entity) {
	            return entity.privateCode !== payload;
	          });
	          state.history = state.history.filter(function (entry) {
	            return entry.privateCode !== payload;
	          });
	          state.strictlyWorking = state.strictlyWorking.filter(function (privateCode) {
	            return privateCode !== payload;
	          });
	          state.personal = state.personal.filter(function (privateCode) {
	            return privateCode !== payload;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearEntities: function clearEntities(state) {
	          state.entity = [];
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addHistory: function addHistory(state, payload) {
	          state.history.push(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        startIntervalForHistoryEntry: function startIntervalForHistoryEntry(state, historyEntry) {
	          historyEntry.time.push({
	            start: new Date(),
	            finish: null
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        finishLastInterval: function finishLastInterval(state) {
	          var shouldRemoveTransitionInterval = false;
	          state.history.map(function (entry) {
	            entry.time = entry.time.map(function (time) {
	              if (time.finish === null) {
	                time.finish = new Date();
	                time.preFinish = null;
	                if (new Date(time.start).getDate() !== time.finish.getDate()) {
	                  shouldRemoveTransitionInterval = true;
	                  time.markedForDeletion = true;
	                  logger.warn('Interval marked for deletion');
	                  debug.log('Interval marked for deletion');
	                }
	                if (entry.type !== timeman_const.EntityType.absence) {
	                  return time;
	                }
	                var shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);
	                state.entity.filter(function (entity) {
	                  if (!state.personal.includes(entity.privateCode)) {
	                    return entity;
	                  }
	                }).map(function (entity) {
	                  return _objectSpread(_objectSpread({}, entity), {}, {
	                    time: Time.calculateInEntityOnADate(state, entity, state.reportState.dateLog)
	                  });
	                }).sort(function (currentEntity, nextEntity) {
	                  return currentEntity.time - nextEntity.time;
	                }).forEach(function (entity) {
	                  if (state.strictlyWorking.includes(entity.privateCode) || entity.type !== timeman_const.EntityType.absence) {
	                    return;
	                  }
	                  if (MonitorModel.prototype.getCommentByEntity(state, entity).trim() === '') {
	                    if (shortAbsenceTimeRest - entity.time >= 0) {
	                      shortAbsenceTimeRest -= entity.time;
	                      return;
	                    }
	                    state.personal.push(entity.privateCode);
	                  }
	                });
	              }
	              return time;
	            }).filter(function (interval) {
	              return !interval.markedForDeletion;
	            });
	            return entry;
	          });
	          if (shouldRemoveTransitionInterval) {
	            state.history = state.history.filter(function (entry) {
	              return main_core.Type.isArrayFilled(entry.time);
	            });
	          }
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        preFinishLastInterval: function preFinishLastInterval(state) {
	          state.history = state.history.map(function (entry) {
	            entry.time = entry.time.map(function (time) {
	              if (time.finish === null) {
	                time.preFinish = new Date();
	                logger.log('Last interval for ', entry.privateCode, ' preFinished');
	              }
	              return time;
	            });
	            return entry;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearHistory: function clearHistory(state) {
	          state.history = [];
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        createSentQueue: function createSentQueue(state, payload) {
	          state.sentQueue.push(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearSentQueue: function clearSentQueue(state) {
	          state.sentQueue = [];
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearStorageBeforeDate: function clearStorageBeforeDate(state, date) {
	          state.history = state.history.filter(function (entry) {
	            return new Date(entry.dateLog) > date;
	          });
	          var getCodesToStore = function getCodesToStore(privateCode) {
	            var entity = state.entity.find(function (entity) {
	              return entity.privateCode === privateCode;
	            });
	            if (main_core.Type.isObject(entity) && entity.hasOwnProperty('type')) {
	              if (entity.type !== timeman_const.EntityType.absence) {
	                return true;
	              }
	              var isInUnsentHistory = state.history.find(function (entry) {
	                return entry.privateCode === privateCode;
	              });
	              if (isInUnsentHistory) {
	                return true;
	              }
	              logger.warn("".concat(entity.title, " has been removed from personal"));
	              debug.log("".concat(entity.title, " has been removed from personal"));
	            }
	            return false;
	          };
	          state.personal = state.personal.filter(getCodesToStore);
	          state.strictlyWorking = state.strictlyWorking.filter(getCodesToStore);
	          if (main_core.Type.isArrayFilled(state.history)) {
	            var privateCodesToStore = [];
	            state.history.forEach(function (entry) {
	              if (!privateCodesToStore.includes(entry.privateCode)) {
	                privateCodesToStore.push(entry.privateCode);
	              }
	            });
	            state.entity = state.entity.filter(function (entity) {
	              return privateCodesToStore.includes(entity.privateCode);
	            });
	            state.entity = state.entity.map(function (entity) {
	              entity.comments = entity.comments.filter(function (comment) {
	                return new Date(comment.dateLog) > date;
	              });
	              return entity;
	            });
	          } else {
	            state.entity = [];
	          }
	          state.sentQueue = [];
	          state.reportState.comments = state.reportState.comments.filter(function (comment) {
	            return new Date(comment.dateLog) > date;
	          });
	          logger.log("Local history before ".concat(timeman_dateformatter.DateFormatter.toString(date), " cleared"));
	          debug.space();
	          debug.log("Local history before ".concat(timeman_dateformatter.DateFormatter.toString(date), " cleared"));
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setComment: function setComment(state, payload) {
	          var dateLog = state.reportState.dateLog;
	          var comment = payload.entity.comments.find(function (comment) {
	            return comment.dateLog === dateLog;
	          });
	          if (comment) {
	            comment.text = payload.comment;
	          } else {
	            payload.entity.comments.push({
	              dateLog: dateLog,
	              text: payload.comment
	            });
	          }
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setReportComment: function setReportComment(state, text) {
	          var dateLog = state.reportState.dateLog;
	          var comment = state.reportState.comments.find(function (comment) {
	            return comment.dateLog === dateLog;
	          });
	          if (comment) {
	            comment.text = text;
	          } else {
	            state.reportState.comments.push({
	              dateLog: dateLog,
	              text: text
	            });
	          }
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setPausedUntil: function setPausedUntil(state, dateTime) {
	          state.config.pausedUntil = dateTime;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        clearPausedUntil: function clearPausedUntil(state) {
	          state.config.pausedUntil = null;
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        processUnfinishedEvents: function processUnfinishedEvents(state) {
	          state.history.map(function (entry) {
	            entry.time = entry.time.map(function (interval) {
	              if (interval.finish === null && interval.preFinish !== null) {
	                interval.finish = interval.preFinish;
	                interval.preFinish = null;
	                logger.log('Unfinished interval closed based on preFinish time');
	                debug.space();
	                debug.log('Unfinished interval closed based on preFinish time');
	              }
	              return interval;
	            });
	            entry.time = entry.time.filter(function (time) {
	              if (time.finish != null) {
	                return true;
	              } else {
	                logger.log('Unfinished interval has been removed');
	                debug.space();
	                debug.log('Unfinished interval has been removed');
	                return false;
	              }
	            });
	            return entry;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        migrateHistory: function migrateHistory(state) {
	          state.entity.map(function (entity) {
	            if (!entity.hasOwnProperty('comments')) {
	              entity.comments = [];
	              if (entity.comment) {
	                entity.comments.push({
	                  dateLog: state.reportState.dateLog,
	                  text: entity.comment
	                });
	              }
	            }
	            delete entity.comment;
	            return entity;
	          });
	          if (!state.reportState.hasOwnProperty('comments')) {
	            state.reportState.comments = [];
	            if (state.reportState.comment) {
	              state.reportState.comments.push({
	                dateLog: state.reportState.dateLog,
	                text: state.reportState.comment
	              });
	            }
	            delete state.reportState.comment;
	          }
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        }
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      return {
	        getWorkingEntities: function getWorkingEntities(state) {
	          var workingEntities = state.entity.filter(function (entity) {
	            if (!state.personal.includes(entity.privateCode)) {
	              return entity;
	            }
	          });
	          workingEntities = workingEntities.map(function (entity) {
	            var comment = entity.comments.find(function (comment) {
	              return comment.dateLog === state.reportState.dateLog;
	            });
	            var workingEntity = _objectSpread(_objectSpread({}, entity), {}, {
	              time: Time.calculateInEntityOnADate(state, entity, state.reportState.dateLog),
	              comment: comment ? comment.text : ''
	            });
	            if (workingEntity.type === timeman_const.EntityType.unknown) {
	              workingEntity.hint = timeman_const.EntityGroup.unknown.hint;
	            }
	            return workingEntity;
	          }).filter(function (entity) {
	            return entity.time > 0;
	          });
	          var otherTimeRest = Time.msToSec(state.config.otherTime);
	          var others = workingEntities.sort(function (currentEntity, nextEntity) {
	            return currentEntity.time - nextEntity.time;
	          }).filter(function (entity) {
	            if (state.strictlyWorking.includes(entity.privateCode) || entity.type === timeman_const.EntityType.absence) {
	              return false;
	            }
	            if (otherTimeRest - entity.time >= 0) {
	              otherTimeRest -= entity.time;
	              return true;
	            } else {
	              return false;
	            }
	          });
	          var shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);
	          var shortAbsence = workingEntities.sort(function (currentEntity, nextEntity) {
	            return currentEntity.time - nextEntity.time;
	          }).filter(function (entity) {
	            if (state.strictlyWorking.includes(entity.privateCode) || entity.type !== timeman_const.EntityType.absence) {
	              return false;
	            }
	            if (MonitorModel.prototype.getCommentByEntity(state, entity).trim() === '') {
	              if (shortAbsenceTimeRest - entity.time >= 0) {
	                shortAbsenceTimeRest -= entity.time;
	                return true;
	              }
	              return false;
	            }
	          });
	          var otherCodes = others.map(function (entity) {
	            return entity.privateCode;
	          });
	          var shortAbsenceCodes = shortAbsence.map(function (entity) {
	            return entity.privateCode;
	          });
	          var excludeCodes = otherCodes.concat(shortAbsenceCodes);
	          workingEntities = workingEntities.filter(function (entity) {
	            return !excludeCodes.includes(entity.privateCode);
	          }).sort(function (currentEntity, nextEntity) {
	            return nextEntity.time - currentEntity.time;
	          });
	          if (main_core.Type.isArrayFilled(others)) {
	            workingEntities.push({
	              type: timeman_const.EntityType.group,
	              title: timeman_const.EntityGroup.other.title,
	              time: Time.msToSec(state.config.otherTime) - otherTimeRest,
	              allowedTime: Time.msToSec(state.config.otherTime),
	              hint: timeman_const.EntityGroup.other.hint,
	              privateCode: timeman_const.EntityGroup.other.value
	            });
	          }
	          if (main_core.Type.isArrayFilled(shortAbsence)) {
	            workingEntities.push({
	              type: timeman_const.EntityType.group,
	              title: timeman_const.EntityGroup.absence.title,
	              time: shortAbsence.reduce(function (sum, entity) {
	                return sum + entity.time;
	              }, 0),
	              allowedTime: Time.msToSec(state.config.shortAbsenceTime),
	              hint: timeman_const.EntityGroup.absence.hint,
	              privateCode: timeman_const.EntityGroup.absence.value
	            });
	          }
	          return workingEntities;
	        },
	        getPersonalEntities: function getPersonalEntities(state) {
	          var personalEntities = state.entity.filter(function (entity) {
	            if (state.personal.includes(entity.privateCode)) {
	              return entity;
	            }
	          });
	          return personalEntities.map(function (entity) {
	            var comment = entity.comments.find(function (comment) {
	              return comment.dateLog === state.reportState.dateLog;
	            });
	            return _objectSpread(_objectSpread({}, entity), {}, {
	              time: Time.calculateInEntityOnADate(state, entity, state.reportState.dateLog),
	              comment: comment ? comment.text : ''
	            });
	          }).filter(function (entity) {
	            return entity.time > 0;
	          }).sort(function (a, b) {
	            return b.time - a.time;
	          });
	        },
	        getSiteDetailByPrivateCode: function getSiteDetailByPrivateCode(state) {
	          return function (privateCode) {
	            var history = BX.util.objectClone(state.history);
	            var entries = history.filter(function (entry) {
	              return entry.privateCode === privateCode && entry.dateLog === state.reportState.dateLog;
	            });
	            entries.map(function (entry) {
	              entry.time = Time.calculateInEntry(entry);
	            });
	            return entries;
	          };
	        },
	        getChartData: function getChartData(state) {
	          var segments = [];
	          var reportDate = new Date(state.reportState.dateLog);
	          var emptyChart = [{
	            start: new Date(reportDate.getFullYear(), reportDate.getMonth(), reportDate.getDate(), 0, 0),
	            finish: new Date(reportDate.getFullYear(), reportDate.getMonth(), reportDate.getDate(), 23, 59),
	            type: timeman_const.EntityGroup.inactive.value,
	            clickable: true,
	            clickableHint: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT'),
	            stretchable: true
	          }];
	          if (!main_core.Type.isArrayFilled(state.history)) {
	            return emptyChart;
	          }
	          var history = state.history.filter(function (entry) {
	            return entry.dateLog === state.reportState.dateLog;
	          });
	          var minute = 60000;

	          //collecting real intervals
	          history.forEach(function (entry) {
	            var type;
	            if (state.personal.includes(entry.privateCode)) {
	              type = timeman_const.EntityGroup.personal.value;
	            } else if (entry.type === timeman_const.EntityType.custom) {
	              type = timeman_const.EntityGroup.workingCustom.value;
	            } else if (entry.type === timeman_const.EntityType.absence) {
	              var entity = state.entity.find(function (entity) {
	                return entity.privateCode === entry.privateCode;
	              });
	              var comment = MonitorModel.prototype.getCommentByEntity(state, entity);
	              type = comment ? timeman_const.EntityGroup.workingCustom.value : timeman_const.EntityGroup.working.value;
	            } else {
	              type = timeman_const.EntityGroup.working.value;
	            }
	            entry.time.forEach(function (interval) {
	              var start = new Date(interval.start);
	              var finish = interval.finish ? new Date(interval.finish) : new Date();
	              segments.push({
	                type: type,
	                start: start,
	                finish: finish
	              });
	            });
	          });
	          if (!main_core.Type.isArrayFilled(segments)) {
	            return emptyChart;
	          }
	          segments = segments.sort(function (currentSegment, nextSegment) {
	            return currentSegment.start - nextSegment.start;
	          });

	          //fill the voids with inactive intervals

	          //create the leftmost interval
	          var firstSegmentFrom = segments[0].start;
	          if (firstSegmentFrom.getHours() + firstSegmentFrom.getMinutes() > 0) {
	            segments.unshift({
	              start: new Date(firstSegmentFrom.getFullYear(), firstSegmentFrom.getMonth(), firstSegmentFrom.getDate(), 0, 0),
	              finish: firstSegmentFrom,
	              type: timeman_const.EntityGroup.inactive.value
	            });
	          }

	          //create inactive intervals throughout the day
	          segments.forEach(function (interval, index) {
	            if (index > 0 && interval.start - segments[index - 1].finish >= minute * 3) {
	              var start = segments[index - 1].finish;
	              var finish = interval.start;
	              start.setMinutes(start.getMinutes() + 1);
	              finish.setMinutes(finish.getMinutes() - 1);
	              segments.push({
	                start: start,
	                finish: finish,
	                type: timeman_const.EntityGroup.inactive.value
	              });
	            }
	          });
	          segments = segments.sort(function (currentSegment, nextSegment) {
	            return currentSegment.start - nextSegment.start;
	          });

	          //create the rightmost interval
	          var lastSegmentTo = segments[segments.length - 1].finish;
	          if (lastSegmentTo.getHours() + lastSegmentTo.getMinutes() < 82) {
	            lastSegmentTo.setMinutes(lastSegmentTo.getMinutes() + 1);
	            segments.push({
	              start: lastSegmentTo,
	              finish: new Date(lastSegmentTo.getFullYear(), lastSegmentTo.getMonth(), lastSegmentTo.getDate(), 23, 59),
	              type: timeman_const.EntityGroup.inactive.value
	            });
	          }

	          //collapse intervals shorter than a minute
	          segments = segments.filter(function (interval) {
	            return interval.finish - interval.start >= minute;
	          });
	          var chartData = [];
	          var lastSegmentType = null;

	          //create data for the graph from intervals
	          segments.forEach(function (segment, index) {
	            if (index > 0 && segment.type !== timeman_const.EntityGroup.inactive.value) {
	              chartData[chartData.length - 1].finish = segment.start;
	            }
	            if (segment.type !== lastSegmentType) {
	              lastSegmentType = segment.type;
	              chartData.push({
	                start: segment.start,
	                finish: segment.finish,
	                type: segment.type,
	                clickable: segment.type === timeman_const.EntityGroup.inactive.value && segment.start < new Date(),
	                clickableHint: segment.type === timeman_const.EntityGroup.inactive.value ? main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT') : ''
	              });
	            } else if (segment.type !== timeman_const.EntityGroup.inactive.value) {
	              chartData[chartData.length - 1].finish = segment.finish;
	            }
	          });
	          return chartData;
	        },
	        getOverChartData: function getOverChartData(state) {
	          return function (selectedPrivateCode) {
	            var selectedCodes = [];
	            var workingEntities = [];
	            if (selectedPrivateCode === timeman_const.EntityGroup.other.value || selectedPrivateCode === timeman_const.EntityGroup.absence.value) {
	              workingEntities = state.entity.filter(function (entity) {
	                if (!state.personal.includes(entity.privateCode)) {
	                  return entity;
	                }
	              }).map(function (entity) {
	                var workingEntity = _objectSpread(_objectSpread({}, entity), {}, {
	                  time: Time.calculateInEntityOnADate(state, entity, state.reportState.dateLog)
	                });
	                if (workingEntity.type === timeman_const.EntityType.unknown) {
	                  workingEntity.hint = timeman_const.EntityGroup.unknown.hint;
	                }
	                return workingEntity;
	              }).filter(function (entity) {
	                return entity.time > 0;
	              });
	            }
	            if (selectedPrivateCode === timeman_const.EntityGroup.other.value) {
	              var otherTimeRest = Time.msToSec(state.config.otherTime);
	              var others = workingEntities.sort(function (currentEntity, nextEntity) {
	                return currentEntity.time - nextEntity.time;
	              }).filter(function (entity) {
	                if (state.strictlyWorking.includes(entity.privateCode) || entity.type === timeman_const.EntityType.absence) {
	                  return false;
	                }
	                if (otherTimeRest - entity.time >= 0) {
	                  otherTimeRest -= entity.time;
	                  return true;
	                } else {
	                  return false;
	                }
	              });
	              others.forEach(function (entity) {
	                return selectedCodes.push(entity.privateCode);
	              });
	            } else if (selectedPrivateCode === timeman_const.EntityGroup.absence.value) {
	              var shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);
	              var shortAbsence = workingEntities.sort(function (currentEntity, nextEntity) {
	                return currentEntity.time - nextEntity.time;
	              }).filter(function (entity) {
	                if (state.strictlyWorking.includes(entity.privateCode) || entity.type !== timeman_const.EntityType.absence) {
	                  return false;
	                }
	                if (MonitorModel.prototype.getCommentByEntity(state, entity).trim() === '') {
	                  if (shortAbsenceTimeRest - entity.time >= 0) {
	                    shortAbsenceTimeRest -= entity.time;
	                    return true;
	                  }
	                  return false;
	                }
	              });
	              shortAbsence.forEach(function (entity) {
	                return selectedCodes.push(entity.privateCode);
	              });
	            } else {
	              selectedCodes = [selectedPrivateCode];
	            }
	            var segments = [];
	            var history = BX.util.objectClone(state.history).filter(function (entry) {
	              return entry.dateLog === state.reportState.dateLog;
	            });
	            var minute = 60000;

	            //collecting real intervals
	            history.forEach(function (entry) {
	              var type = state.personal.includes(entry.privateCode) ? timeman_const.EntityGroup.personal.value : timeman_const.EntityGroup.working.value;
	              entry.display = selectedCodes.includes(entry.privateCode) ? 'selected' : 'transparent';
	              entry.time.forEach(function (interval) {
	                var start = new Date(interval.start);
	                var finish = interval.finish ? new Date(interval.finish) : new Date();
	                segments.push({
	                  type: type,
	                  start: start,
	                  finish: finish,
	                  display: entry.display
	                });
	              });
	            });
	            segments = segments.sort(function (currentSegment, nextSegment) {
	              return currentSegment.start - nextSegment.start;
	            });

	            //create the leftmost interval
	            var firstSegmentFrom = segments[0].start;
	            if (firstSegmentFrom.getHours() + firstSegmentFrom.getMinutes() > 0) {
	              segments.unshift({
	                start: new Date(firstSegmentFrom.getFullYear(), firstSegmentFrom.getMonth(), firstSegmentFrom.getDate(), 0, 0),
	                finish: firstSegmentFrom,
	                type: timeman_const.EntityGroup.inactive.value,
	                display: 'transparent'
	              });
	            }

	            //create inactive intervals throughout the day
	            segments.forEach(function (interval, index) {
	              if (index > 0 && interval.start - segments[index - 1].finish >= minute * 3) {
	                var start = segments[index - 1].finish;
	                var finish = interval.start;
	                start.setMinutes(start.getMinutes() + 1);
	                finish.setMinutes(finish.getMinutes() - 1);
	                segments.push({
	                  start: start,
	                  finish: finish,
	                  type: timeman_const.EntityGroup.inactive.value,
	                  display: 'transparent'
	                });
	              }
	            });
	            segments = segments.sort(function (currentSegment, nextSegment) {
	              return currentSegment.start - nextSegment.start;
	            });

	            //create the rightmost interval
	            var lastSegmentTo = segments[segments.length - 1].finish;
	            if (lastSegmentTo.getHours() + lastSegmentTo.getMinutes() < 82) {
	              lastSegmentTo.setMinutes(lastSegmentTo.getMinutes() + 1);
	              segments.push({
	                start: lastSegmentTo,
	                finish: new Date(lastSegmentTo.getFullYear(), lastSegmentTo.getMonth(), lastSegmentTo.getDate(), 23, 59),
	                type: timeman_const.EntityGroup.inactive.value,
	                display: 'transparent'
	              });
	            }
	            return segments;
	          };
	        },
	        isHistorySent: function isHistorySent(state) {
	          var lastSuccessfulSendDate = state.config.lastSuccessfulSendDate;
	          var hasUnsentHistory = main_core.Type.isArrayFilled(state.history) && new Date(state.history[0].dateLog) < new Date(MonitorModel.prototype.getDateLog());
	          if (!lastSuccessfulSendDate) {
	            return !hasUnsentHistory;
	          }
	          lastSuccessfulSendDate = new Date(lastSuccessfulSendDate);
	          lastSuccessfulSendDate.setHours(0);
	          lastSuccessfulSendDate.setMinutes(0);
	          lastSuccessfulSendDate.setSeconds(0);
	          lastSuccessfulSendDate.setMilliseconds(0);
	          var currentDate = new Date();
	          currentDate.setHours(0);
	          currentDate.setMinutes(0);
	          currentDate.setSeconds(0);
	          currentDate.setMilliseconds(0);
	          return currentDate - lastSuccessfulSendDate <= 86400000 || !hasUnsentHistory;
	        },
	        getWorkingTimeForToday: function getWorkingTimeForToday(state) {
	          var workingEntities = state.entity.filter(function (entity) {
	            if (!state.personal.includes(entity.privateCode)) {
	              return entity;
	            }
	          });
	          return workingEntities.map(function (entity) {
	            return _objectSpread(_objectSpread({}, entity), {}, {
	              time: Time.calculateInEntityOnADate(state, entity, MonitorModel.prototype.getDateLog())
	            });
	          }).reduce(function (sum, entity) {
	            return sum + entity.time;
	          }, 0);
	        },
	        getReportComment: function getReportComment(state) {
	          var reportComment = state.reportState.comments.find(function (comment) {
	            return comment.dateLog === state.reportState.dateLog;
	          });
	          return reportComment ? reportComment.text : '';
	        },
	        hasActivityOtherThanBitrix24: function hasActivityOtherThanBitrix24(state) {
	          var hasActivity = false;
	          var appSiteEntities = state.entity.filter(function (entity) {
	            return entity.type === timeman_const.EntityType.app || entity.type === timeman_const.EntityType.site;
	          });
	          if (main_core.Type.isArrayFilled(appSiteEntities) && appSiteEntities.length > 1) {
	            hasActivity = true;
	          }
	          return hasActivity;
	        }
	      };
	    }
	  }, {
	    key: "validatePersonal",
	    value: function validatePersonal() {
	      var personal = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var result = '';
	      if (personal && (main_core.Type.isString(personal) || main_core.Type.isNumber(personal))) {
	        result = personal.toString();
	      }
	      return result;
	    }
	  }, {
	    key: "validateEntity",
	    value: function validateEntity() {
	      var entity = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var result = {};
	      if (main_core.Type.isObject(entity) && entity) {
	        if (main_core.Type.isString(entity.type)) {
	          result.type = entity.type;
	        }
	        if (main_core.Type.isString(entity.title) || main_core.Type.isNumber(entity.title)) {
	          result.title = entity.title.toString();
	        }
	        if (main_core.Type.isArrayFilled(entity.comments)) {
	          result.comments = entity.comments;
	        }
	      }
	      return result;
	    }
	  }, {
	    key: "validateHistory",
	    value: function validateHistory() {
	      var historyEntry = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var result = {};
	      if (main_core.Type.isObject(historyEntry) && historyEntry) {
	        if (main_core.Type.isString(historyEntry.dateLog) && main_core.Type.isDate(new Date(historyEntry.dateLog)) && !isNaN(new Date(historyEntry.dateLog))) {
	          result.dateLog = historyEntry.dateLog;
	        }
	        if (main_core.Type.isString(historyEntry.title) || main_core.Type.isNumber(historyEntry.title)) {
	          result.title = historyEntry.title.toString();
	        }
	        if (main_core.Type.isString(historyEntry.type) && timeman_const.EntityType.hasOwnProperty(historyEntry.type)) {
	          result.type = historyEntry.type;
	          if (historyEntry.type === timeman_const.EntityType.site) {
	            result.siteUrl = historyEntry.siteUrl;
	            result.siteTitle = historyEntry.siteTitle.toString();
	          }
	        }
	        if (main_core.Type.isArrayFilled(historyEntry.time)) {
	          result.time = historyEntry.time;
	        }
	      }
	      return result;
	    }
	  }, {
	    key: "validateSentQueue",
	    value: function validateSentQueue() {
	      var sentQueueItem = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var result = {};
	      if (main_core.Type.isObject(sentQueueItem) && sentQueueItem) {
	        if (main_core.Type.isString(sentQueueItem.dateLog)) {
	          result.dateLog = sentQueueItem.dateLog;
	        }
	        if (main_core.Type.isArrayFilled(sentQueueItem.historyPackage)) {
	          result.historyPackage = sentQueueItem.historyPackage;
	        }
	        if (main_core.Type.isArrayFilled(sentQueueItem.chartPackage)) {
	          result.chartPackage = sentQueueItem.chartPackage;
	        }
	        if (main_core.Type.isString(sentQueueItem.desktopCode)) {
	          result.desktopCode = sentQueueItem.desktopCode;
	        }
	        if (main_core.Type.isString(sentQueueItem.comment)) {
	          result.comment = sentQueueItem.comment;
	        }
	      }
	      return result;
	    }
	  }, {
	    key: "getHistoryEntryByPrivateCode",
	    value: function getHistoryEntryByPrivateCode(store, privateCode) {
	      var _this3 = this;
	      return store.state.history.find(function (entry) {
	        return entry.privateCode === privateCode && entry.dateLog === _this3.getDateLog();
	      });
	    }
	  }, {
	    key: "getHistoryEntryBySiteUrl",
	    value: function getHistoryEntryBySiteUrl(store, siteUrl) {
	      var _this4 = this;
	      return store.state.history.find(function (entry) {
	        return entry.siteUrl === siteUrl && entry.dateLog === _this4.getDateLog();
	      });
	    }
	  }, {
	    key: "getEntityByPrivateCode",
	    value: function getEntityByPrivateCode(store, privateCode) {
	      return store.state.entity.find(function (entity) {
	        return entity.privateCode === privateCode;
	      });
	    }
	  }, {
	    key: "getEntityByTitle",
	    value: function getEntityByTitle(store, title) {
	      return store.state.entity.find(function (entity) {
	        return entity.title === title;
	      });
	    }
	  }, {
	    key: "getCommentByEntity",
	    value: function getCommentByEntity(state, entity) {
	      var comment = entity.comments.find(function (comment) {
	        return comment.dateLog === state.reportState.dateLog;
	      });
	      return comment ? comment.text : '';
	    }
	  }, {
	    key: "getDateLog",
	    value: function getDateLog() {
	      return this.formatDateLog(new Date());
	    }
	  }, {
	    key: "formatDateLog",
	    value: function formatDateLog(date) {
	      var addZero = function addZero(num) {
	        return num >= 0 && num <= 9 ? '0' + num : num;
	      };
	      var year = date.getFullYear();
	      var month = addZero(date.getMonth() + 1);
	      var day = addZero(date.getDate());
	      return year + '-' + month + '-' + day;
	    }
	  }, {
	    key: "collectSentQueue",
	    value: function collectSentQueue(store) {
	      var history = BX.util.objectClone(this.getGetters().getWorkingEntities(store.state));
	      history = history.map(function (entry) {
	        if (entry.type === timeman_const.EntityType.group && entry.title === timeman_const.EntityGroup.other.title) {
	          entry.type = timeman_const.EntityType.other;
	          entry.publicCode = Code.createPublic(timeman_const.EntityType.other);
	          entry.privateCode = Code.createPrivate(timeman_const.EntityType.other);
	          delete entry.items;
	        } else if (entry.type === timeman_const.EntityType.group && entry.title === timeman_const.EntityGroup.absence.title) {
	          entry.type = timeman_const.EntityType.absenceShort;
	          entry.publicCode = Code.createPublic(timeman_const.EntityType.absenceShort);
	          entry.privateCode = Code.createPrivate(timeman_const.EntityType.absenceShort);
	          delete entry.items;
	        } else if (entry.type === timeman_const.EntityType.absence) {
	          if (entry.extra.hasOwnProperty('timeStart')) {
	            entry.timeStart = entry.extra.timeStart;
	          }
	          entry.title = main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE');
	          entry.publicCode = Code.createPublic(timeman_const.EntityType.absence);
	          entry.privateCode = Code.createPrivate(timeman_const.EntityType.absence);
	        }
	        delete entry.extra;
	        delete entry.hint;
	        return entry;
	      });
	      logger.log('History to send:', history);
	      debug.space();
	      debug.log('History to send:', history);
	      var chart = this.getGetters().getChartData(store.state).map(function (interval) {
	        return {
	          type: interval.type,
	          start: interval.start,
	          finish: interval.finish
	        };
	      });
	      logger.log('ChartData to send:', chart);
	      return {
	        history: history,
	        chart: chart
	      };
	    }
	  }]);
	  return MonitorModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var Entity = /*#__PURE__*/function () {
	  function Entity() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Entity);
	    switch (params.type) {
	      case timeman_const.EntityType.site:
	        this.createSite(params);
	        break;
	      case timeman_const.EntityType.app:
	        this.createApp(params);
	        break;
	      case timeman_const.EntityType.absence:
	        this.createAbsence();
	        break;
	      case timeman_const.EntityType.unknown:
	        this.createUnknown(params);
	        break;
	      case timeman_const.EntityType.incognito:
	        this.createIncognito();
	        break;
	    }
	  }
	  babelHelpers.createClass(Entity, [{
	    key: "createSite",
	    value: function createSite(params) {
	      this.type = timeman_const.EntityType.site;
	      var host;
	      try {
	        host = new URL(params.url).host;
	      } catch (err) {
	        host = params.url;
	      }
	      if (host === '') {
	        var hostFragments = params.url.split('/');
	        host = hostFragments[hostFragments.length - 1] !== '' ? hostFragments[hostFragments.length - 1] : params.url;
	      } else if (host.split('.')[0] === 'www') {
	        host = host.substring(4);
	      }
	      this.title = host.toString();
	      this.siteUrl = params.url.toString();
	      this.siteTitle = params.title.toString();
	    }
	  }, {
	    key: "createApp",
	    value: function createApp(params) {
	      this.type = timeman_const.EntityType.app;
	      this.title = params.name.toString();
	      if (params.isBitrix24Desktop) {
	        this.isBitrix24Desktop = params.isBitrix24Desktop;
	      }
	    }
	  }, {
	    key: "createAbsence",
	    value: function createAbsence() {
	      this.type = timeman_const.EntityType.absence;
	      this.title = main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE');
	    }
	  }, {
	    key: "createUnknown",
	    value: function createUnknown(params) {
	      this.type = timeman_const.EntityType.unknown;
	      this.title = main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_UNKNOWN');
	      this.pureName = params.name;
	      this.pureTitle = params.title;
	    }
	  }, {
	    key: "createIncognito",
	    value: function createIncognito() {
	      this.type = timeman_const.EntityType.incognito;
	      this.title = main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_INCOGNITO');
	    }
	  }]);
	  return Entity;
	}();

	var EventHandler = /*#__PURE__*/function () {
	  function EventHandler() {
	    babelHelpers.classCallCheck(this, EventHandler);
	  }
	  babelHelpers.createClass(EventHandler, [{
	    key: "init",
	    value: function init(store) {
	      this.enabled = false;
	      this.store = store;
	      this.preFinishInterval = null;
	      this.lastCaught = {
	        name: null,
	        url: null
	      };
	    }
	  }, {
	    key: "catch",
	    value: function _catch(process, name, title, url) {
	      if (!this.enabled || !process) {
	        return;
	      }
	      actionTimer.start('CATCH_ENTITY');
	      var type = this.getEntityTypeByEvent({
	        process: process,
	        name: name,
	        title: title,
	        url: url
	      });
	      var isBitrix24Desktop = false;
	      if (type === timeman_const.EntityType.app) {
	        if (['Bitrix24.exe', 'Bitrix24'].includes(this.getNameByProcess(process))) {
	          isBitrix24Desktop = true;
	        }
	      }
	      if (type !== timeman_const.EntityType.absence) {
	        if (type === timeman_const.EntityType.app) {
	          switch (name) {
	            case 'Application Frame Host':
	              name = title;
	              break;
	            case 'StartMenuExperienceHost':
	            case 'Search application':
	              name = main_core.Loc.getMessage('TIMEMAN_PWT_WINDOWS_START_ALIAS');
	              break;
	            case 'Windows Shell Experience Host':
	              name = main_core.Loc.getMessage('TIMEMAN_PWT_WINDOWS_NOTIFICATIONS_ALIAS');
	              break;
	          }
	        }
	        if (name === '') {
	          name = this.getNameByProcess(process);
	        }
	        if (!this.isNewEvent(name, url)) {
	          return;
	        }
	        this.lastCaught = {
	          name: name,
	          url: url
	        };
	      }
	      this.store.dispatch('monitor/addHistory', new Entity({
	        type: type,
	        name: name,
	        title: title,
	        url: url,
	        isBitrix24Desktop: isBitrix24Desktop
	      }));
	    }
	  }, {
	    key: "catchAbsence",
	    value: function catchAbsence(away) {
	      if (away) {
	        this.store.dispatch('monitor/addHistory', new Entity({
	          type: timeman_const.EntityType.absence
	        }));
	        this.lastCaught = {
	          name: timeman_const.EntityType.absence,
	          url: null
	        };
	      } else {
	        if (this.isWorkingDayStarted() && this.isTrackerGetActiveAppAvailable()) {
	          BXDesktopSystem.TrackerGetActiveApp();
	        }
	      }
	    }
	  }, {
	    key: "catchAppClose",
	    value: function catchAppClose() {
	      logger.warn('Application shutdown recognized. The last interval is finished.');
	      debug.log('Application shutdown recognized. The last interval is finished.');
	      this.store.dispatch('monitor/finishLastInterval');
	    }
	  }, {
	    key: "getEntityTypeByEvent",
	    value: function getEntityTypeByEvent(event) {
	      var type = timeman_const.EntityType.unknown;
	      if (event.url === 'unknown') {
	        type = timeman_const.EntityType.unknown;
	      } else if (event.url === 'incognito') {
	        type = timeman_const.EntityType.incognito;
	      } else if (event.url) {
	        type = timeman_const.EntityType.site;
	      } else if (event.process !== '') {
	        type = timeman_const.EntityType.app;
	      }
	      return type;
	    }
	  }, {
	    key: "getNameByProcess",
	    value: function getNameByProcess(process) {
	      var separator = process.includes('/') ? '/' : '\\';
	      var path = process.split(separator);
	      return path[path.length - 1];
	    }
	  }, {
	    key: "isNewEvent",
	    value: function isNewEvent(name, url) {
	      if (this.url === '') {
	        if (this.lastCaught.name === name) {
	          return false;
	        }
	      } else {
	        if (this.lastCaught.name === name && this.lastCaught.url === url) {
	          return false;
	        }
	      }
	      return true;
	    }
	  }, {
	    key: "isTrackerGetActiveAppAvailable",
	    value: function isTrackerGetActiveAppAvailable() {
	      return BX.desktop.getApiVersion() >= 56;
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      var _this = this;
	      if (this.enabled) {
	        logger.warn('EventHandler already started');
	        return;
	      }
	      this.enabled = true;
	      im_v2_lib_desktopApi.DesktopApi.subscribe('BXUserApp', function (process, name, title, url) {
	        return _this["catch"](process, name, title, url);
	      });
	      im_v2_lib_desktopApi.DesktopApi.subscribe('BXExitApplication', this.catchAppClose.bind(this));
	      if (this.isTrackerGetActiveAppAvailable()) {
	        BXDesktopSystem.TrackerGetActiveApp();
	      }
	      this.preFinishInterval = setInterval(function () {
	        return _this.store.dispatch('monitor/preFinishLastInterval');
	      }, 60000);
	      logger.log('EventHandler started');
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      if (!this.enabled) {
	        logger.warn('EventHandler already stopped');
	        return;
	      }
	      this.enabled = false;
	      this.lastCaught = {
	        name: null,
	        url: null
	      };
	      this.preFinishInterval = null;
	      this.store.dispatch('monitor/finishLastInterval');
	      logger.log('EventHandler stopped');
	    }
	  }]);
	  return EventHandler;
	}();
	var eventHandler = new EventHandler();

	var Sender = /*#__PURE__*/function () {
	  function Sender() {
	    babelHelpers.classCallCheck(this, Sender);
	  }
	  babelHelpers.createClass(Sender, [{
	    key: "init",
	    value: function init(store) {
	      this.enabled = false;
	      this.store = store;
	      this.attempt = 0;
	      this.resendTimeout = 5000;
	      this.resendTimeoutId = null;
	    }
	  }, {
	    key: "send",
	    value: function send() {
	      var _this = this;
	      logger.warn('Trying to send history...');
	      BX.ajax.runAction('bitrix:timeman.api.monitor.recordhistory', {
	        data: {
	          history: JSON.stringify(this.getSentQueue())
	        }
	      }).then(function (result) {
	        debug.log('History sent');
	        if (result.status === 'success') {
	          logger.warn('SUCCESS!');
	          _this.attempt = 0;
	          _this.afterSuccessSend();
	          if (result.data.enabled === monitor.getStatusDisabled()) {
	            logger.warn('Disabled after server response');
	            debug.log('Disabled after server response');
	            monitor.disable();
	          }
	        } else {
	          logger.error('ERROR!');
	          _this.attempt++;
	          _this.startSendingTimer();
	        }
	      })["catch"](function () {
	        logger.error('CONNECTION ERROR!');
	        _this.attempt++;
	        _this.startSendingTimer();
	      });
	    }
	  }, {
	    key: "startSendingTimer",
	    value: function startSendingTimer() {
	      this.resendTimeoutId = setTimeout(this.send.bind(this), this.getSendingDelay());
	      logger.log("Next send in ".concat(this.getSendingDelay() / 1000, " seconds..."));
	    }
	  }, {
	    key: "getSendingDelay",
	    value: function getSendingDelay() {
	      return this.attempt === 0 ? this.resendTimeout : this.resendTimeout * this.attempt;
	    }
	  }, {
	    key: "getSentQueue",
	    value: function getSentQueue() {
	      return this.store.state.monitor.sentQueue;
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      if (this.enabled) {
	        logger.warn('Sender already started');
	        return;
	      }
	      this.enabled = true;
	      this.attempt = 0;
	      if (main_core.Type.isArrayFilled(this.getSentQueue())) {
	        logger.log('Preparing to send old history...');
	        this.startSendingTimer();
	      }
	      logger.log("Sender started");
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      if (!this.enabled) {
	        logger.warn('Sender already stopped');
	        return;
	      }
	      this.enabled = false;
	      this.attempt = 0;
	      clearTimeout(this.resendTimeoutId);
	      logger.log("Sender stopped");
	    }
	  }, {
	    key: "afterSuccessSend",
	    value: function afterSuccessSend() {
	      var _this2 = this;
	      logger.warn('History sent');
	      debug.space();
	      debug.log('History sent');
	      this.store.dispatch('monitor/setLastSuccessfulSendDate', new Date(this.store.state.monitor.reportState.dateLog)).then(function () {
	        _this2.store.dispatch('monitor/clearSentHistory').then(function () {
	          _this2.store.dispatch('monitor/refreshDateLog');
	          _this2.store.dispatch('monitor/clearSentQueue');
	          BX.SidePanel.Instance.close();
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SENT'),
	            autoHideDelay: 5000
	          });
	        });
	      });
	    }
	  }]);
	  return Sender;
	}();
	var sender = new Sender();

	var CommandHandler = /*#__PURE__*/function () {
	  function CommandHandler() {
	    babelHelpers.classCallCheck(this, CommandHandler);
	  }
	  babelHelpers.createClass(CommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'timeman';
	    }
	  }, {
	    key: "handleChangeMonitorEnabled",
	    value: function handleChangeMonitorEnabled(params) {
	      if (params.enabled === monitor.getStatusEnabled()) {
	        logger.warn('Enabled via API');
	        debug.log('Enabled via API');
	        location.reload();
	      } else {
	        monitor.stop();
	        monitor.disable();
	        logger.warn('Disabled via API');
	        debug.log('Disabled via API');
	      }
	    }
	  }, {
	    key: "handleChangeMonitorDebugEnabled",
	    value: function handleChangeMonitorDebugEnabled(params) {
	      if (params.enabled) {
	        debug.enable();
	        logger.warn('Debug mode enabled via API');
	        debug.log('Debug mode enabled via API');
	      } else {
	        logger.warn('Debug mode disabled via API');
	        debug.log('Debug mode disabled via API');
	        debug.disable();
	      }
	    }
	  }]);
	  return CommandHandler;
	}();

	var Monitor = /*#__PURE__*/function () {
	  function Monitor() {
	    babelHelpers.classCallCheck(this, Monitor);
	  }
	  babelHelpers.createClass(Monitor, [{
	    key: "init",
	    value: function init(options) {
	      this.enabled = options.enabled;
	      this.playTimeout = null;
	      this.isAway = false;
	      this.vuex = {};
	      this.defaultStorageConfig = {
	        config: {
	          otherTime: options.otherTime,
	          shortAbsenceTime: options.shortAbsenceTime
	        }
	      };
	      this.dateFormat = options.dateFormat;
	      this.timeFormat = options.timeFormat;
	      if (options.debugEnabled) {
	        debug.enable();
	      }
	      debug.space();
	      debug.log('Desktop launched!');
	      if (this.isEnabled() && logger.isEnabled()) {
	        BXDesktopSystem.LogInfo = function () {};
	        logger.start();
	      }
	      debug.log("Enabled: ".concat(this.enabled));
	      if (this.isEnabled()) {
	        this.initApp();
	      }
	      pull_client.PULL.subscribe(new CommandHandler());
	    }
	  }, {
	    key: "initApp",
	    value: function initApp() {
	      var _this = this;
	      if (!this.isEnabled()) {
	        return;
	      }
	      new ui_vue_vuex.VuexBuilder().addModel(MonitorModel.create().setVariables(this.defaultStorageConfig).useDatabase(true)).setDatabaseConfig({
	        name: 'timeman-pwt',
	        type: ui_vue_vuex.VuexBuilder.DatabaseType.indexedDb,
	        siteId: main_core.Loc.getMessage('SITE_ID'),
	        userId: main_core.Loc.getMessage('USER_ID')
	      }).build().then(function (builder) {
	        _this.vuex.store = builder.store;
	        _this.getStorage().dispatch('monitor/processUnfinishedEvents').then(function () {
	          return _this.initTracker(_this.getStorage());
	        });
	      })["catch"](function () {
	        var errorMessage = "PWT: Storage initialization error";
	        logger.error(errorMessage);
	        debug.log(errorMessage);
	      });
	    }
	  }, {
	    key: "initTracker",
	    value: function initTracker(store) {
	      var _this2 = this;
	      timeman_dateformatter.DateFormatter.init(this.dateFormat);
	      timeman_timeformatter.TimeFormatter.init(this.timeFormat);
	      eventHandler.init(store);
	      sender.init(store);
	      im_v2_lib_desktopApi.DesktopApi.subscribe('BXUserAway', function (away) {
	        return _this2.onAway(away);
	      });
	      if (BX.MessengerWindow && BX.MessengerWindow.addTab) {
	        BX.MessengerWindow.addTab({
	          id: 'timeman-pwt',
	          title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE'),
	          order: 540,
	          target: false,
	          events: {
	            open: function open() {
	              return _this2.openReport();
	            }
	          }
	        });
	      }
	      BX.desktop.addCustomEvent('BXProtocolUrl', function (command) {
	        if (command === 'timemanpwt') {
	          if (!BX.MessengerCommon.isDesktop()) {
	            return false;
	          }
	          BX.MessengerWindow.changeTab('timeman-pwt', true);
	          BX.desktop.setActiveWindow();
	          BX.desktop.windowCommand("show");
	        }
	      });
	      if (this.isEnabled()) {
	        this.launch();
	      } else {
	        logger.warn('Monitor is disabled');
	      }
	    }
	  }, {
	    key: "launch",
	    value: function launch() {
	      var _this3 = this;
	      if (this.isAway) {
	        logger.log('Pause is over, but computer is in sleep mode. Waiting for the return of the user.');
	        debug.log('Pause is over, but computer is in sleep mode. Waiting for the return of the user.');
	        return;
	      }
	      if (!this.getStorage().state.monitor.config.grantingPermissionDate) {
	        logger.log('History access not provided. Monitor is not started.');
	        debug.log('History access not provided. Monitor is not started.');
	        if (this.shouldShowGrantingPermissionWindow()) {
	          this.openReport();
	          BXDesktopWindow.ExecuteCommand('show.active');
	        }
	        return;
	      }
	      this.getStorage().dispatch('monitor/migrateHistory').then(function () {
	        _this3.getStorage().dispatch('monitor/clearSentHistory').then(function () {
	          _this3.getStorage().dispatch('monitor/refreshDateLog').then(function () {
	            if (_this3.isPaused()) {
	              if (_this3.isPauseRelevant()) {
	                logger.warn("Can't start, monitor is paused!");
	                debug.log("Can't start, monitor is paused!");
	                _this3.setPlayTimeout();
	                return;
	              }
	              _this3.clearPausedUntil().then(function () {
	                return _this3.start();
	              });
	              return;
	            }
	            _this3.start();
	          });
	        });
	      });
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      if (!this.isEnabled()) {
	        logger.warn("Can't start, monitor is disabled!");
	        debug.log("Can't start, monitor is disabled!");
	        return;
	      }
	      if (this.isPaused()) {
	        logger.warn("Can't start, monitor is paused!");
	        debug.log("Can't start, monitor is paused!");
	        return;
	      }
	      debug.log('Monitor started');
	      debug.space();
	      if (this.isTrackerEventsApiAvailable()) {
	        logger.log('Events started');
	        BXDesktopSystem.TrackerStart();
	      }
	      eventHandler.start();
	      sender.start();
	      logger.warn('Monitor started');
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      if (this.isTrackerEventsApiAvailable()) {
	        logger.log('Events stopped');
	        BXDesktopSystem.TrackerStop();
	      }
	      eventHandler.stop();
	      sender.stop();
	      logger.warn('Monitor stopped');
	      debug.log('Monitor stopped');
	    }
	  }, {
	    key: "pause",
	    value: function pause() {
	      this.stop();
	      this.setPlayTimeout();
	    }
	  }, {
	    key: "onAway",
	    value: function onAway(away) {
	      if (!this.isEnabled() || this.isPaused()) {
	        return;
	      }
	      if (away && !this.isAway) {
	        this.isAway = true;
	        logger.warn('User AWAY');
	        debug.space();
	        debug.log('User AWAY');
	        this.stop();
	        eventHandler.catchAbsence(away);
	      } else if (!away && this.isAway) {
	        this.isAway = false;
	        logger.warn('User RETURNED, continue monitoring...');
	        debug.space();
	        debug.log('User RETURNED, continue monitoring...');
	        this.launch();
	      }
	    }
	  }, {
	    key: "send",
	    value: function send() {
	      if (!this.vuex.hasOwnProperty('store')) {
	        logger.warn('Unable to send report. Store is not initialized.');
	        debug.log('Unable to send report. Store is not initialized.');
	        return;
	      }
	      this.getStorage().dispatch('monitor/createSentQueue').then(function () {
	        return sender.send();
	      });
	    }
	  }, {
	    key: "openReport",
	    value: function openReport() {
	      if (!this.isEnabled()) {
	        return;
	      }
	      timeman_monitorReport.MonitorReport.open(this.getStorage());
	    }
	  }, {
	    key: "openReportPreview",
	    value: function openReportPreview() {
	      if (!this.isEnabled()) {
	        return;
	      }
	      timeman_monitorReport.MonitorReport.openPreview(this.getStorage());
	    }
	  }, {
	    key: "getPausedUntilTime",
	    value: function getPausedUntilTime() {
	      return this.getStorage().state.monitor.config.pausedUntil;
	    }
	  }, {
	    key: "clearPausedUntil",
	    value: function clearPausedUntil() {
	      return this.getStorage().dispatch('monitor/clearPausedUntil');
	    }
	  }, {
	    key: "isPaused",
	    value: function isPaused() {
	      return !!this.getPausedUntilTime();
	    }
	  }, {
	    key: "isPauseRelevant",
	    value: function isPauseRelevant() {
	      return this.getPausedUntilTime() - new Date() > 0;
	    }
	  }, {
	    key: "setPlayTimeout",
	    value: function setPlayTimeout() {
	      var _this4 = this;
	      logger.warn("Monitor will be turned on at ".concat(this.getPausedUntilTime().toString()));
	      debug.log("Monitor will be turned on at ".concat(this.getPausedUntilTime().toString()));
	      clearTimeout(this.playTimeout);
	      this.playTimeout = setTimeout(function () {
	        return _this4.clearPausedUntil().then(function () {
	          return _this4.launch();
	        });
	      }, this.getPausedUntilTime() - new Date());
	    }
	  }, {
	    key: "pauseUntil",
	    value: function pauseUntil(dateTime) {
	      var _this5 = this;
	      if (main_core.Type.isDate(dateTime) && main_core.Type.isNumber(dateTime.getTime()) && dateTime > new Date()) {
	        this.getStorage().dispatch('monitor/setPausedUntil', dateTime).then(function () {
	          return _this5.pause();
	        });
	      } else {
	        throw Error('Pause must be set as a date in the future');
	      }
	    }
	  }, {
	    key: "shouldShowGrantingPermissionWindow",
	    value: function shouldShowGrantingPermissionWindow() {
	      var config = this.getStorage().state.monitor.config;
	      if (!config) {
	        return false;
	      }
	      if (config.grantingPermissionDate !== null) {
	        return false;
	      }
	      var deferredGrantingPermissionShowDate = config.deferredGrantingPermissionShowDate;
	      if (deferredGrantingPermissionShowDate === null) {
	        return true;
	      }
	      return new Date(MonitorModel.prototype.getDateLog()) >= new Date(deferredGrantingPermissionShowDate);
	    }
	  }, {
	    key: "showGrantingPermissionLater",
	    value: function showGrantingPermissionLater() {
	      return this.getStorage().dispatch('monitor/showGrantingPermissionLater');
	    }
	  }, {
	    key: "play",
	    value: function play() {
	      var _this6 = this;
	      clearTimeout(this.playTimeout);
	      this.playTimeout = null;
	      this.clearPausedUntil().then(function () {
	        return _this6.launch();
	      });
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      return this.enabled === this.getStatusEnabled();
	    }
	  }, {
	    key: "enable",
	    value: function enable() {
	      this.enabled = this.getStatusEnabled();
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.stop();
	      BX.MessengerWindow.hideTab('timeman-pwt');
	      this.vuex = {};
	      this.enabled = this.getStatusDisabled();
	    }
	  }, {
	    key: "getStatusEnabled",
	    value: function getStatusEnabled() {
	      return 'Y';
	    }
	  }, {
	    key: "getStatusDisabled",
	    value: function getStatusDisabled() {
	      return 'N';
	    }
	  }, {
	    key: "isTrackerEventsApiAvailable",
	    value: function isTrackerEventsApiAvailable() {
	      return im_v2_lib_desktopApi.DesktopApi.getApiVersion() >= 55;
	    }
	  }, {
	    key: "getStorage",
	    value: function getStorage() {
	      return this.vuex.hasOwnProperty('store') ? this.vuex.store : null;
	    }
	  }]);
	  return Monitor;
	}();
	var monitor = new Monitor();

	exports.Monitor = monitor;

}((this.BX.Timeman = this.BX.Timeman || {}),BX,BX,BX,BX.Timeman.Const,BX,BX.Timeman,BX.Timeman,BX.Timeman,BX,BX,BX.Messenger.v2.Lib));
//# sourceMappingURL=monitor.bundle.js.map

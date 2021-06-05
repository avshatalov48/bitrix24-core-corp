this.BX = this.BX || {};
(function (exports,ui_vue_vuex,main_md5,main_sha1,ui_notification,ui_forms,ui_layoutForm,ui_alerts,ui_vuex,ui_vue_components_hint,ui_vue_portal,main_popup,ui_dialogs_messagebox,ui_icons,ui_vue,timeman_component_timeline,timeman_const,main_loader,timeman_dateformatter,timeman_timeformatter,pull_client,main_core) {
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
	    key: "calculateInEntity",
	    value: function calculateInEntity(state, entity) {
	      return state.history.filter(function (entry) {
	        return entry.privateCode === entity.privateCode;
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
	            text = text + ' | ' + (babelHelpers.typeof(arguments[i]) == 'object' ? JSON.stringify(arguments[i]) : arguments[i]);
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
	          shortAbsenceTime: this.getVariable('config.shortAbsenceTime', 1800000)
	        },
	        reportState: {
	          dateLog: this.getDateLog()
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
	        comment: '',
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
	          var result = _this.validateEntity(babelHelpers.objectSpread({}, payload));

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

	          store.commit('addEntity', babelHelpers.objectSpread({}, _this.getEntityState(), result));

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

	          var result = _this.validateHistory(babelHelpers.objectSpread({}, payload));

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

	          if (!historyEntry) {
	            delete result.title;
	            store.commit('addHistory', babelHelpers.objectSpread({}, _this.getHistoryState(), result, {
	              privateCode: privateCode
	            }));
	            return;
	          }

	          if (result.type !== timeman_const.EntityType.custom) {
	            store.commit('startIntervalForHistoryEntry', historyEntry);
	          }
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

	          var result = _this.validateSentQueue({
	            dateLog: store.state.reportState.dateLog,
	            historyPackage: sentQueue.history,
	            chartPackage: sentQueue.chart,
	            desktopCode: store.state.config.desktopCode
	          });

	          store.commit('createSentQueue', babelHelpers.objectSpread({}, _this.getSentQueueState(), result));
	        },
	        clearSentQueue: function clearSentQueue(store) {
	          store.commit('clearSentQueue');
	        },
	        clearStorage: function clearStorage(store) {
	          store.commit('clearStorage');
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
	        processUnfinishedEvents: function processUnfinishedEvents(store) {
	          store.commit('processUnfinishedEvents');
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
	          state.history.map(function (entry) {
	            entry.time = entry.time.map(function (time) {
	              if (time.finish === null) {
	                time.finish = new Date();
	                time.preFinish = null;

	                if (entry.type !== timeman_const.EntityType.absence) {
	                  return time;
	                }

	                var shortAbsenceTimeRest = Time.msToSec(state.config.shortAbsenceTime);
	                state.entity.filter(function (entity) {
	                  if (!state.personal.includes(entity.privateCode)) {
	                    return entity;
	                  }
	                }).map(function (entity) {
	                  return babelHelpers.objectSpread({}, entity, {
	                    time: Time.calculateInEntity(state, entity)
	                  });
	                }).sort(function (currentEntity, nextEntity) {
	                  return currentEntity.time - nextEntity.time;
	                }).forEach(function (entity) {
	                  if (state.strictlyWorking.includes(entity.privateCode) || entity.type !== timeman_const.EntityType.absence) {
	                    return;
	                  }

	                  if (entity.comment.trim() === '') {
	                    if (shortAbsenceTimeRest - entity.time >= 0) {
	                      shortAbsenceTimeRest -= entity.time;
	                      return;
	                    }

	                    state.personal.push(entity.privateCode);
	                  }
	                });
	              }

	              return time;
	            });
	            return entry;
	          });
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
	        clearStorage: function clearStorage(state) {
	          var getCodesToStore = function getCodesToStore(privateCode) {
	            var entity = state.entity.find(function (entity) {
	              return entity.privateCode === privateCode;
	            });

	            if (main_core.Type.isObject(entity) && entity.hasOwnProperty('type')) {
	              if (entity.type !== timeman_const.EntityType.absence) {
	                return true;
	              }
	            }

	            return false;
	          };

	          state.personal = state.personal.filter(getCodesToStore);
	          state.strictlyWorking = state.strictlyWorking.filter(getCodesToStore);
	          state.entity = [];
	          state.history = [];
	          state.sentQueue = [];
	          logger.log('Local storage cleared');
	          debug.space();
	          debug.log('Local storage cleared');
	          babelHelpers.get(babelHelpers.getPrototypeOf(MonitorModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setComment: function setComment(state, payload) {
	          payload.entity.comment = payload.comment;
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
	            var workingEntity = babelHelpers.objectSpread({}, entity, {
	              time: Time.calculateInEntity(state, entity)
	            });

	            if (workingEntity.type === timeman_const.EntityType.unknown) {
	              workingEntity.hint = timeman_const.EntityGroup.unknown.hint;
	            }

	            return workingEntity;
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

	            if (entity.comment.trim() === '') {
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
	              hint: timeman_const.EntityGroup.other.hint
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
	              hint: timeman_const.EntityGroup.absence.hint
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
	            return babelHelpers.objectSpread({}, entity, {
	              time: Time.calculateInEntity(state, entity)
	            });
	          }).sort(function (a, b) {
	            return b.time - a.time;
	          });
	        },
	        getSiteDetailByPrivateCode: function getSiteDetailByPrivateCode(state) {
	          return function (privateCode) {
	            var history = BX.util.objectClone(state.history);
	            var entries = history.filter(function (entry) {
	              return entry.privateCode === privateCode;
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

	          var history = BX.util.objectClone(state.history);
	          var minute = 60000; //collecting real intervals

	          history.forEach(function (entry) {
	            var type = state.personal.includes(entry.privateCode) ? timeman_const.EntityGroup.personal.value : timeman_const.EntityGroup.working.value;
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
	          }); //fill the voids with inactive intervals
	          //create the leftmost interval

	          var firstSegmentFrom = segments[0].start;

	          if (firstSegmentFrom.getHours() + firstSegmentFrom.getMinutes() > 0) {
	            segments.unshift({
	              start: new Date(firstSegmentFrom.getFullYear(), firstSegmentFrom.getMonth(), firstSegmentFrom.getDate(), 0, 0),
	              finish: firstSegmentFrom,
	              type: timeman_const.EntityGroup.inactive.value
	            });
	          } //create inactive intervals throughout the day


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
	          }); //create the rightmost interval

	          var lastSegmentTo = segments[segments.length - 1].finish;

	          if (lastSegmentTo.getHours() + lastSegmentTo.getMinutes() < 82) {
	            lastSegmentTo.setMinutes(lastSegmentTo.getMinutes() + 1);
	            segments.push({
	              start: lastSegmentTo,
	              finish: new Date(lastSegmentTo.getFullYear(), lastSegmentTo.getMonth(), lastSegmentTo.getDate(), 23, 59),
	              type: timeman_const.EntityGroup.inactive.value
	            });
	          } //collapse intervals shorter than a minute


	          segments = segments.filter(function (interval) {
	            return interval.finish - interval.start >= minute;
	          });
	          var chartData = [];
	          var lastSegmentType = null; //create data for the graph from intervals

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

	        if (main_core.Type.isString(entity.comment) || main_core.Type.isNumber(entity.comment)) {
	          result.comment = entity.comment.toString();
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
	      }

	      return result;
	    }
	  }, {
	    key: "getHistoryEntryByPrivateCode",
	    value: function getHistoryEntryByPrivateCode(store, privateCode) {
	      return store.state.history.find(function (entry) {
	        return entry.privateCode === privateCode;
	      });
	    }
	  }, {
	    key: "getHistoryEntryBySiteUrl",
	    value: function getHistoryEntryBySiteUrl(store, siteUrl) {
	      return store.state.history.find(function (entry) {
	        return entry.siteUrl === siteUrl;
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
	    key: "getDateLog",
	    value: function getDateLog() {
	      var date = new Date();

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
	        this.createUnknown();
	        break;

	      case timeman_const.EntityType.incognito:
	        this.createIncognito();
	        break;
	    }

	    logger.log('Caught:', this);
	    debug.log('Caught:', this);
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
	    value: function createUnknown() {
	      this.type = timeman_const.EntityType.unknown;
	      this.title = main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_UNKNOWN');
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
	      BX.desktop.addCustomEvent('BXUserApp', function (process, name, title, url) {
	        return _this.catch(process, name, title, url);
	      });
	      BX.desktop.addCustomEvent('BXExitApplication', this.catchAppClose);

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

	          if (result.data.state === monitor.getStateStop()) {
	            logger.warn('Stopped after server response');
	            debug.log('Stopped after server response');
	            monitor.setState(result.data.state);
	            monitor.stop();
	          }

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
	      }).catch(function () {
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

	      var currentDateLog = new Date(MonitorModel.prototype.getDateLog());
	      var reportDateLog = new Date(this.store.state.monitor.reportState.dateLog);
	      logger.warn('History sent');
	      debug.space();
	      debug.log('History sent');
	      monitor.isHistorySent = true;
	      BX.SidePanel.Instance.close();

	      if (currentDateLog > reportDateLog) {
	        logger.warn('The next day came. Clearing the history and changing the date of the report.');
	        debug.log('The next day came. Clearing the history and changing the date of the report.');
	        this.store.dispatch('monitor/clearStorage').then(function () {
	          _this2.store.dispatch('monitor/setDateLog', MonitorModel.prototype.getDateLog());

	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_STORAGE_CLEARED'),
	            autoHideDelay: 5000,
	            position: 'bottom-right'
	          });
	        });
	      } else {
	        logger.warn('History has been sent, report date has not changed.');
	        debug.log('History has been sent, report date has not changed.');
	        this.store.dispatch('monitor/clearSentQueue');
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SENT'),
	          autoHideDelay: 5000,
	          position: 'bottom-right'
	        });
	      }
	    }
	  }]);
	  return Sender;
	}();

	var sender = new Sender();

	var Control = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-control', {
	  data: function data() {
	    return {
	      status: timeman_const.DayState.unknown
	    };
	  },
	  computed: {
	    DayState: function DayState() {
	      return timeman_const.DayState;
	    }
	  },
	  mounted: function mounted() {
	    var _this = this;

	    this.getDayStatus();
	    pull_client.PULL.subscribe({
	      type: pull_client.PullClient.SubscriptionType.Server,
	      moduleId: 'timeman',
	      command: 'changeDayState',
	      callback: function callback(params, extra, command) {
	        _this.getDayStatus();
	      }
	    });
	  },
	  methods: {
	    getDayStatus: function getDayStatus() {
	      this.callRestMethod('timeman.status', {}, this.setStatusByResult);
	    },
	    closeDay: function closeDay() {
	      //tmp hack to close day in desktop app via old popup and link this to a component update.
	      var dayControl = BX('tm-component-pwt-day-control');

	      if (dayControl) {
	        dayControl.style.display = 'block';
	        dayControl.style.position = 'absolute';
	        dayControl.style.left = 'calc(100vw - 115px)';
	        dayControl.style.top = 0;
	      }

	      var callPopup = BX('bx_tm');

	      if (!dayControl && callPopup) {
	        callPopup.style.position = 'absolute';
	        callPopup.style.left = 'calc(100vw - 115px)';
	        callPopup.style.top = 0;
	      }

	      if (callPopup) {
	        callPopup.click();
	        var popup = BX('tm-popup');

	        if (!main_core.ZIndexManager.getComponent(popup)) {
	          main_core.ZIndexManager.register(popup);
	        }

	        main_core.ZIndexManager.bringToFront(popup);
	      } //this.callRestMethod('timeman.close', {}, this.setStatusByResult);

	    },
	    openDayAndSendHistory: function openDayAndSendHistory() {
	      BX.Timeman.Monitor.send();
	      this.callRestMethod('timeman.open', {}, this.setStatusByResult);
	    },
	    callRestMethod: function callRestMethod(method, params, callback) {
	      this.$Bitrix.RestClient.get().callMethod(method, params, callback);
	    },
	    setStatusByResult: function setStatusByResult(result) {
	      if (!result.error()) {
	        this.status = result.data().STATUS;
	      }
	    },
	    closeReport: function closeReport() {
	      BX.SidePanel.Instance.close();
	    },
	    isAllowedToStartDayAndSendHistory: function isAllowedToStartDayAndSendHistory() {
	      var currentDateLog = new Date(MonitorModel.prototype.getDateLog());
	      var reportDateLog = new Date(this.$store.state.monitor.reportState.dateLog);
	      var isHistorySent = BX.Timeman.Monitor.isHistorySent;

	      if (currentDateLog > reportDateLog && !isHistorySent) {
	        return true;
	      }

	      return false;
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-component-day-control-wrap\">\n\t\t\t<button\n\t\t\t\tv-if=\"this.status === DayState.unknown\"\n\t\t\t\tclass=\"ui-btn ui-btn-default ui-btn-wait ui-btn-disabled\"\n\t\t\t\tstyle=\"width: 130px\"\n\t\t\t/>\n\n\t\t\t<button\n\t\t\t\tv-if=\"\n\t\t\t\t\t\tthis.status === DayState.opened\n\t\t\t\t\t\t|| this.status === DayState.paused\n\t\t\t\t\t\t|| this.status === DayState.expired\n\t\t\t\t\t\"\n\t\t\t\t@click=\"closeDay\"\n\t\t\t\tclass=\"ui-btn ui-btn-danger ui-btn-icon-stop\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SEND_BUTTON') }}\n\t\t\t</button>\n\n\t\t\t<button\n\t\t\t\tv-if=\"\n\t\t\t\t\tthis.status === DayState.closed\n\t\t\t\t\t&& this.isAllowedToStartDayAndSendHistory()\n\t\t\t\t\"\n\t\t\t\t@click=\"openDayAndSendHistory\"\n\t\t\t\tclass=\"ui-btn ui-btn-success ui-btn-icon-start\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_OPEN_SEND_BUTTON') }}\n\t\t\t</button>\n\n\t\t\t<button\n\t\t\t\tclass=\"ui-btn ui-btn-light-border\"\n\t\t\t\t@click=\"closeReport\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t</button>\n\t\t</div>\n\t"
	});

	var AddIntervalPopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-addinterval', {
	  directives: {
	    'bx-focus': {
	      inserted: function inserted(element) {
	        element.focus();
	      }
	    }
	  },
	  props: {
	    minStart: Date,
	    maxFinish: Date
	  },
	  data: function data() {
	    return {
	      title: '',
	      start: this.getTime(this.minStart),
	      finish: this.getTime(this.maxFinish),
	      comment: ''
	    };
	  },
	  created: function created() {
	    this.minStart.setSeconds(0);
	    this.minStart.setMilliseconds(0);
	    this.maxFinish.setSeconds(0);
	    this.maxFinish.setMilliseconds(0);

	    if (this.createDateFromTimeString(this.finish) > this.saveMaxFinish) {
	      this.finish = this.getTime(this.saveMaxFinish);
	    }
	  },
	  computed: {
	    TimeFormatter: function TimeFormatter() {
	      return timeman_timeformatter.TimeFormatter;
	    },
	    Type: function Type() {
	      return main_core.Type;
	    },
	    saveMaxFinish: function saveMaxFinish() {
	      var safeMaxFinish = this.maxFinish;
	      var currentDateTime = new Date();
	      currentDateTime.setSeconds(0);
	      currentDateTime.setMilliseconds(0);

	      if (safeMaxFinish > currentDateTime) {
	        safeMaxFinish = currentDateTime;
	      }

	      return safeMaxFinish;
	    },
	    canAddInterval: function canAddInterval() {
	      if (this.title.trim() === '' || !this.start || !this.finish) {
	        return false;
	      }

	      var start = this.createDateFromTimeString(this.start);
	      var finish = this.createDateFromTimeString(this.finish);
	      var isStartError = start < this.minStart;
	      var isFinishError = finish > this.saveMaxFinish;
	      var isIntervalsConfusedError = start > finish;
	      return !(isStartError || isFinishError || isIntervalsConfusedError);
	    }
	  },
	  methods: {
	    addInterval: function addInterval() {
	      if (!this.canAddInterval) {
	        return;
	      }

	      var start = this.createDateFromTimeString(this.start);
	      var finish = this.createDateFromTimeString(this.finish);
	      this.$store.dispatch('monitor/addHistory', {
	        title: this.title,
	        type: timeman_const.EntityType.custom,
	        comment: this.comment,
	        time: [{
	          start: start,
	          preFinish: null,
	          finish: finish
	        }]
	      });
	      this.addIntervalPopupClose();
	    },
	    addIntervalPopupClose: function addIntervalPopupClose() {
	      this.$emit('addIntervalPopupClose');
	    },
	    addIntervalPopupHide: function addIntervalPopupHide() {
	      this.$emit('addIntervalPopupHide');
	    },
	    inputStart: function inputStart(value) {
	      var start = this.createDateFromTimeString(this.start);

	      if (start < this.minStart || value === '') {
	        this.start = this.getTime(this.minStart);
	        return;
	      }

	      if (start < this.minStart) {
	        this.start = this.getTime(this.minStart);
	        return;
	      }

	      if (this.finish) {
	        var finish = this.createDateFromTimeString(this.finish);

	        if (start >= finish || start >= this.getTime(this.saveMaxFinish)) {
	          start.setHours(this.saveMaxFinish.getHours());
	          start.setMinutes(this.saveMaxFinish.getMinutes() - 1);
	          this.start = this.getTime(start);
	          return;
	        }
	      }

	      this.start = value;
	    },
	    inputFinish: function inputFinish(value) {
	      var finish = this.createDateFromTimeString(this.finish);

	      if (finish > this.saveMaxFinish || value === '') {
	        this.finish = this.getTime(this.saveMaxFinish);
	        return;
	      }

	      if (this.start) {
	        var start = this.createDateFromTimeString(this.start);

	        if (finish <= start || finish <= this.getTime(this.minStart)) {
	          finish.setHours(start.getHours());
	          finish.setMinutes(start.getMinutes() + 1);
	          this.finish = this.getTime(finish);
	          return;
	        }
	      }

	      this.finish = value;
	    },
	    getTime: function getTime(date) {
	      if (!main_core.Type.isDate(date)) {
	        date = new Date(date);
	      }

	      var addZero = function addZero(num) {
	        return num >= 0 && num <= 9 ? '0' + num : num;
	      };

	      var hour = addZero(date.getHours());
	      var min = addZero(date.getMinutes());
	      return hour + ':' + min;
	    },
	    createDateFromTimeString: function createDateFromTimeString(time) {
	      var baseDate = this.minStart;
	      var year = baseDate.getFullYear();
	      var month = baseDate.getMonth();
	      var day = baseDate.getDate();
	      var hourMin = time.split(':');
	      return new Date(year, month, day, hourMin[0], hourMin[1], 0, 0);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-monitor-group-wrap\">\n\t\t\t<div class=\"bx-timeman-monitor-report-popup-wrap\">\n\t\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t\t<div class=\"popup-window-titlebar\">\n\t\t\t\t\t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\tpopup-window-content\n\t\t\t\t\t\t\tbx-timeman-monitor-popup-window-content\n\t\t\t\t\t\t\"\n\t\t\t\t\t\tstyle=\"\n\t\t\t\t\t\t\toverflow: auto; \n\t\t\t\t\t\t\tbackground: transparent;\n\t\t\t\t\t\t\twidth: 440px;\n\t\t\t\t\t\t\"\n\t\t\t\t\t>\n\t\t\t\t\t  \n\t\t\t\t\t\t<div class=\"ui-form\">\n\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n                                      {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_TITLE') }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\tv-model=\"title\"\n\t\t\t\t\t\t\t\t\t\t\tv-bx-focus\n\t\t\t\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-row-inline\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_START') }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ \n\t\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIN_START_HINT')\n\t\t\t\t\t\t\t\t\t\t  \t\t\t.replace('#TIME#', TimeFormatter.toShort(minStart))\n\t\t\t\t\t\t\t\t\t\t  \t}}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-time\">\n\t\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\t\tv-model=\"start\"\n\t\t\t\t\t\t\t\t\t\t\t\tv-on:blur=\"inputStart($event.target.value)\"\n\t\t\t\t\t\t\t\t\t\t\t\ttype=\"time\" \n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t\t\tstyle=\"padding-right: 4px !important;\"\n\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_FINISH') }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t\t{{\n\t\t\t\t\t\t\t\t\t\t\t\t$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MAX_FINISH_HINT')\n\t\t\t\t\t\t\t\t\t\t\t\t\t.replace('#TIME#', TimeFormatter.toShort(saveMaxFinish))\n\t\t\t\t\t\t\t\t\t\t\t}}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-time\">\n\t\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\t\tv-model=\"finish\"\n\t\t\t\t\t\t\t\t\t\t\t\tv-on:blur=\"inputFinish($event.target.value)\"\n\t\t\t\t\t\t\t\t\t\t\t\ttype=\"time\" \n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t\t\tstyle=\"padding-right: 4px !important;\"\n\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_COMMENT') }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textarea ui-ctl-no-resize\">\n\t\t\t\t\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\t\t\t\t\tv-model=\"comment\"\n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t</textarea>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t@click=\"addInterval\"\n\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\t'ui-btn',\n\t\t\t\t\t\t\t\t'ui-btn-md',\n\t\t\t\t\t\t\t\t'ui-btn-primary',\n\t\t\t\t\t\t\t\t!canAddInterval ? 'ui-btn-disabled' : ''\n\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ADD_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t@click=\"addIntervalPopupHide\" \n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t </button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var Interval = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup-interval', {
	  props: {
	    start: Date,
	    finish: Date
	  },
	  computed: {
	    TimeFormatter: function TimeFormatter() {
	      return timeman_timeformatter.TimeFormatter;
	    },
	    safeFinish: function safeFinish() {
	      var safeFinish = this.finish;
	      var currentDateTime = new Date();
	      currentDateTime.setSeconds(0);
	      currentDateTime.setMilliseconds(0);

	      if (safeFinish > currentDateTime) {
	        safeFinish = currentDateTime;
	      }

	      return safeFinish;
	    }
	  },
	  methods: {
	    intervalSelected: function intervalSelected() {
	      this.$emit('intervalSelected', {
	        start: this.start,
	        finish: this.safeFinish
	      });
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-selectintervalpopup-interval\">\n\t\t\t<div\n\t\t\t\t@click=\"intervalSelected\"\n                class=\"bx-timeman-monitor-report-popup-item\"\n\t\t\t>\n\t\t\t  <div class=\"bx-timeman-monitor-report-popup-title\">\n                {{ TimeFormatter.toShort(start) }} - {{ TimeFormatter.toShort(safeFinish) }}\n\t\t\t  </div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var SelectIntervalPopup = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup', {
	  components: {
	    Interval: Interval
	  },
	  computed: {
	    inactiveIntervals: function inactiveIntervals() {
	      return this.$store.getters['monitor/getChartData'].filter(function (interval) {
	        return interval.type === timeman_const.EntityGroup.inactive.value && interval.start < new Date();
	      });
	    }
	  },
	  methods: {
	    selectIntervalPopupCloseClick: function selectIntervalPopupCloseClick() {
	      this.$emit('selectIntervalPopupCloseClick');
	    },
	    onIntervalSelected: function onIntervalSelected(event) {
	      this.$emit('intervalSelected', event);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-monitor-report-popup-selectintervalpopup\">\n\t\t\t<div class=\"bx-timeman-monitor-report-popup-wrap\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-timeman-monitor-report-popup\n\t\t\t\t\t\tpopup-window \n\t\t\t\t\t\tpopup-window-with-titlebar \n\t\t\t\t\t\tui-message-box \n\t\t\t\t\t\tui-message-box-medium-buttons \n\t\t\t\t\t\tpopup-window-fixed-width \n\t\t\t\t\t\tpopup-window-fixed-height\n\t\t\t\t\t\" \n\t\t\t\t\tstyle=\"padding: 0\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"popup-window-titlebar\">\n\t\t\t\t\t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_SELECT_INTERVAL') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\tpopup-window-content\n\t\t\t\t\t\t\tbx-timeman-monitor-popup-window-content\n\t\t\t\t\t\t\"\n\t\t\t\t\t\tstyle=\"\n\t\t\t\t\t\t\toverflow: auto; \n\t\t\t\t\t\t\tbackground: transparent;\n\t\t\t\t\t\t\twidth: 440px;\n\t\t\t\t\t\t\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"bx-timeman-monitor-report-popup-items-container\">\n\t\t\t\t\t\t\t<Interval\n\t\t\t\t\t\t\t\tv-for=\"interval of inactiveIntervals\"\n\t\t\t\t\t\t\t\t:key=\"interval.start.toString()\"\n\t\t\t\t\t\t\t\t:start=\"interval.start\"\n\t\t\t\t\t\t\t\t:finish=\"interval.finish\"\n\t\t\t\t\t\t\t\t@intervalSelected=\"onIntervalSelected\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t@click=\"selectIntervalPopupCloseClick\" \n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var Time$1 = {
	  computed: {
	    fullTime: function fullTime() {
	      return this.workingTime + this.personalTime;
	    },
	    workingTime: function workingTime() {
	      return this.$store.getters['monitor/getWorkingEntities'].reduce(function (sum, entry) {
	        return sum + entry.time;
	      }, 0);
	    },
	    personalTime: function personalTime() {
	      return this.$store.getters['monitor/getPersonalEntities'].reduce(function (sum, entry) {
	        return sum + entry.time;
	      }, 0);
	    },
	    inactiveTime: function inactiveTime() {
	      return 86400 - (this.workingTime + this.personalTime);
	    }
	  },
	  methods: {
	    formatSeconds: function formatSeconds(seconds) {
	      if (seconds < 1) {
	        return 0 + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
	      } else if (seconds < 60) {
	        return this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_LESS_THAN_MINUTE');
	      }

	      var hours = Math.floor(seconds / 3600);
	      var minutes = Math.round(seconds / 60 % 60);

	      if (minutes === 60) {
	        hours += 1;
	        minutes = 0;
	      }

	      if (hours > 0) {
	        hours = hours + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_HOUR_SHORT');

	        if (minutes > 0) {
	          minutes = minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
	          return hours + ' ' + minutes;
	        }

	        return hours;
	      }

	      return minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
	    },
	    calculateEntryTime: function calculateEntryTime(entry) {
	      var time = entry.time.map(function (interval) {
	        var finish = interval.finish ? new Date(interval.finish) : new Date();
	        return finish - new Date(interval.start);
	      }).reduce(function (sum, interval) {
	        return sum + interval;
	      }, 0);
	      return Math.round(time / 1000);
	    },
	    getEntityByPrivateCode: function getEntityByPrivateCode(privateCode) {
	      return this.monitor.entity.find(function (entity) {
	        return entity.privateCode === privateCode;
	      });
	    }
	  }
	};

	var Item = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-group-item', {
	  mixins: [Time$1],
	  props: ['readOnly', 'group', 'privateCode', 'type', 'title', 'time', 'allowedTime', 'comment', 'hint'],
	  data: function data() {
	    return {
	      action: '',
	      hintOptions: {
	        targetContainer: document.body
	      }
	    };
	  },
	  computed: babelHelpers.objectSpread({}, ui_vuex.Vuex.mapGetters('monitor', ['getSiteDetailByPrivateCode']), ui_vuex.Vuex.mapState({
	    monitor: function monitor(state) {
	      return state.monitor;
	    }
	  }), {
	    EntityType: function EntityType() {
	      return timeman_const.EntityType;
	    },
	    EntityGroup: function EntityGroup() {
	      return timeman_const.EntityGroup;
	    }
	  }),
	  methods: {
	    addPersonal: function addPersonal(privateCode) {
	      this.$store.dispatch('monitor/addPersonal', privateCode);
	    },
	    removePersonal: function removePersonal(privateCode) {
	      var _this = this;

	      if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
	        this.action = function () {
	          return _this.$store.dispatch('monitor/removePersonal', _this.privateCode);
	        };

	        this.onCommentClick();
	        return;
	      }

	      this.$store.dispatch('monitor/removePersonal', privateCode);
	    },
	    addToStrictlyWorking: function addToStrictlyWorking(privateCode) {
	      var _this2 = this;

	      if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
	        this.action = function () {
	          return _this2.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
	        };

	        this.onCommentClick();
	        return;
	      }

	      this.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
	    },
	    removeFromStrictlyWorking: function removeFromStrictlyWorking(privateCode) {
	      this.$store.dispatch('monitor/removeFromStrictlyWorking', privateCode);
	    },
	    removeEntityByPrivateCode: function removeEntityByPrivateCode(privateCode) {
	      this.$store.dispatch('monitor/removeEntityByPrivateCode', privateCode);
	    },
	    onCommentClick: function onCommentClick(event) {
	      this.$emit('commentClick', {
	        event: event,
	        group: this.group,
	        content: {
	          privateCode: this.privateCode,
	          title: this.title,
	          time: this.time,
	          comment: this.comment,
	          type: this.type
	        },
	        onSaveComment: this.action
	      });
	    },
	    onDetailClick: function onDetailClick(event) {
	      this.$emit('detailClick', {
	        event: event,
	        group: this.group,
	        content: {
	          privateCode: this.privateCode,
	          title: this.title,
	          detail: this.getSiteDetailByPrivateCode(this.privateCode),
	          time: this.time
	        }
	      });
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-monitor-group-item-wrap\">\n\t\t\t<div class=\"bx-monitor-group-item\">\n\t\t\t\t<template v-if=\"type !== EntityType.group\">\n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-if=\"type === EntityType.absence\"\n\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t  'bx-monitor-group-item-title': comment, \n\t\t\t\t\t\t\t\t  'bx-monitor-group-item-title-small': !comment \n\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<template v-if=\"comment\">\n\t\t\t\t\t\t\t\t\t{{ comment }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-else class=\"bx-monitor-group-item-title\">\n\t\t\t\t\t\t\t\t<template v-if=\"type !== EntityType.site || readOnly\">\n\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<a \n\t\t\t\t\t\t\t\t\t\t@click=\"onDetailClick\" \n\t\t\t\t\t\t\t\t\t\thref=\"#\" \n\t\t\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-site-title\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-hint v-if=\"hint\" :text=\"hint\" :popupOptions=\"hintOptions\"/>\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"group === EntityGroup.working.value\" \n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-button-comment ui-icon ui-icon-xs\"\n\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t  'ui-icon-service-imessage': comment, \n\t\t\t\t\t\t\t\t  'ui-icon-service-light-imessage': !comment \n\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<i \n\t\t\t\t\t\t\t\t\t@click=\"onCommentClick\" \n\t\t\t\t\t\t\t\t\t:style=\"{\n\t\t\t\t\t\t\t\t\t\tbackgroundColor: comment ? EntityGroup.working.primaryColor : 'transparent'\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t{{ time }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"group === EntityGroup.personal.value && !readOnly\"\n\t\t\t\t\t\t@click=\"removePersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_WORKING') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"\n\t\t\t\t\t\t\tgroup === EntityGroup.working.value \n\t\t\t\t\t\t\t&& (type !== EntityType.unknown && type !== EntityType.custom) \n\t\t\t\t\t\t\t&& !readOnly\n\t\t\t\t\t\t\"\n\t\t\t\t\t\t@click=\"addPersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\" \t\t\t\t\t\t\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_PERSONAL') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"\n\t\t\t\t\t\t\ttype === EntityType.custom\n\t\t\t\t\t\t\t&& !readOnly\n\t\t\t\t\t\t\"\n\t\t\t\t\t\t@click=\"removeEntityByPrivateCode(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-danger-light ui-btn-round bx-monitor-group-btn-right\" \t\t\t\t\t\t\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_REMOVE') }}\n\t\t\t\t\t</button>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-full\">\n\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-hint v-if=\"hint\" :text=\"hint\" :popupOptions=\"hintOptions\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-menu\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t\t{{ time }} / {{ allowedTime }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var Group = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-group', {
	  components: {
	    Item: Item,
	    MountingPortal: ui_vue_portal.MountingPortal
	  },
	  directives: {
	    'bx-focus': {
	      inserted: function inserted(element) {
	        element.focus();
	      }
	    }
	  },
	  mixins: [Time$1],
	  props: ['group', 'readOnly'],
	  data: function data() {
	    return {
	      popupInstance: null,
	      popupIdSelector: !!this.readOnly ? '#bx-timeman-pwt-popup-preview' : '#bx-timeman-pwt-popup-editor',
	      popupContent: {
	        privateCode: '',
	        title: '',
	        time: '',
	        comment: '',
	        detail: '',
	        type: '',
	        onSaveComment: ''
	      },
	      comment: '',
	      isCommentPopup: false,
	      isDetailPopup: false
	    };
	  },
	  computed: babelHelpers.objectSpread({}, ui_vuex.Vuex.mapGetters('monitor', ['getWorkingEntities', 'getPersonalEntities']), ui_vuex.Vuex.mapState({
	    monitor: function monitor(state) {
	      return state.monitor;
	    }
	  }), {
	    EntityType: function EntityType() {
	      return timeman_const.EntityType;
	    },
	    EntityGroup: function EntityGroup() {
	      return timeman_const.EntityGroup;
	    },
	    displayedGroup: function displayedGroup() {
	      if (this.EntityGroup.getValues().includes(this.group)) {
	        return this.EntityGroup[this.group];
	      }
	    },
	    items: function items() {
	      switch (this.displayedGroup.value) {
	        case timeman_const.EntityGroup.working.value:
	          return this.getWorkingEntities;

	        case timeman_const.EntityGroup.personal.value:
	          return this.getPersonalEntities;
	      }
	    },
	    time: function time() {
	      switch (this.displayedGroup.value) {
	        case timeman_const.EntityGroup.working.value:
	          return this.workingTime;

	        case timeman_const.EntityGroup.personal.value:
	          return this.personalTime;
	      }
	    }
	  }),
	  methods: {
	    onCommentClick: function onCommentClick(event) {
	      var _this = this;

	      this.isCommentPopup = true;
	      this.popupContent.privateCode = event.content.privateCode;
	      this.popupContent.title = event.content.title;
	      this.popupContent.time = event.content.time;
	      this.popupContent.type = event.content.type;
	      this.popupContent.onSaveComment = event.onSaveComment;
	      this.comment = event.content.comment;

	      if (this.popupInstance !== null) {
	        this.popupInstance.destroy();
	        this.popupInstance = null;
	      }

	      var popup = main_popup.PopupManager.create({
	        id: "bx-timeman-pwt-external-data",
	        targetContainer: document.body,
	        autoHide: true,
	        closeByEsc: true,
	        bindOptions: {
	          position: "top"
	        },
	        events: {
	          onPopupDestroy: function onPopupDestroy() {
	            _this.isCommentPopup = false;
	            _this.popupInstance = null;
	          }
	        }
	      }); //little hack for correct open several popups in a row.

	      this.$nextTick(function () {
	        return _this.popupInstance = popup;
	      });
	    },
	    onDetailClick: function onDetailClick(event) {
	      var _this2 = this;

	      this.isDetailPopup = true;
	      this.popupContent.privateCode = event.content.privateCode;
	      this.popupContent.title = event.content.title;
	      this.popupContent.time = event.content.time;
	      this.popupContent.detail = event.content.detail;

	      if (this.popupInstance !== null) {
	        this.popupInstance.destroy();
	        this.popupInstance = null;
	      }

	      var popup = main_popup.PopupManager.create({
	        id: "bx-timeman-pwt-external-data",
	        targetContainer: document.body,
	        autoHide: true,
	        closeByEsc: true,
	        bindOptions: {
	          position: "top"
	        },
	        events: {
	          onPopupDestroy: function onPopupDestroy() {
	            _this2.isDetailPopup = false;
	            _this2.popupInstance = null;
	          }
	        }
	      }); //little hack for correct open several popups in a row.

	      this.$nextTick(function () {
	        return _this2.popupInstance = popup;
	      });
	    },
	    saveComment: function saveComment(privateCode) {
	      if (this.comment.trim() === '' && this.popupContent.type === timeman_const.EntityType.absence) {
	        return;
	      }

	      this.$store.dispatch('monitor/setComment', {
	        privateCode: privateCode,
	        comment: this.comment
	      });

	      if (typeof this.popupContent.onSaveComment === 'function') {
	        this.popupContent.onSaveComment();
	      }

	      this.popupInstance.destroy();
	    },
	    addNewLineToComment: function addNewLineToComment() {
	      this.comment += '\n';
	    },
	    selectIntervalClick: function selectIntervalClick(event) {
	      this.$emit('selectIntervalClick', event);
	    }
	  },
	  // language=Vue
	  template: "\t\t  \n\t\t<div class=\"bx-timeman-monitor-report-group-wrap\">\t\t\t\n\t\t\t<div class=\"bx-monitor-group\">\t\t\t\t  \n\t\t\t\t<div class=\"bx-monitor-group-header\" v-bind:style=\"{ background: displayedGroup.secondaryColor }\">\n\t\t\t\t\t<div class=\"bx-monitor-group-title-container\">\n                      \t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-title\">\n\t\t\t\t\t\t\t\t{{ displayedGroup.title }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t\t\t<div class=\"bx-monitor-group-subtitle\">\n\t\t\t\t\t\t\t\t  {{ formatSeconds(time) }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-if=\"(\n\t\t\t\t\t\t\t    this.displayedGroup.value === EntityGroup.working.value\n\t\t\t\t\t\t\t    && !readOnly\n\t\t\t\t\t\t\t)\"\n\t\t\t\t\t\t\t@click=\"selectIntervalClick\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light ui-btn-round bx-monitor-group-btn-add\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t{{ '+ ' + $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_ADD') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"!readOnly\" class=\"bx-monitor-group-subtitle-wrap\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-hint\">\n\t\t\t\t\t\t\t{{ displayedGroup.hint }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"bx-monitor-group-content\" v-bind:style=\"{ background: displayedGroup.lightColor }\">\n\t\t\t\t\t<transition-group name=\"bx-monitor-group-item\" class=\"bx-monitor-group-content-wrap\">\n\t\t\t\t\t\n\t\t\t\t\t\t<Item\n\t\t\t\t\t\t\tv-for=\"item of items\"\n\t\t\t\t\t\t\t:key=\"item.privateCode ? item.privateCode : item.title\"\n\t\t\t\t\t\t\t:group=\"displayedGroup.value\"\n\t\t\t\t\t\t\t:privateCode=\"item.privateCode\"\n\t\t\t\t\t\t\t:type=\"item.type\"\n\t\t\t\t\t\t\t:title=\"item.title\"\n\t\t\t\t\t\t\t:comment=\"item.comment\"\n\t\t\t\t\t\t\t:time=\"formatSeconds(item.time)\"\n\t\t\t\t\t\t\t:allowedTime=\"item.allowedTime ? formatSeconds(item.allowedTime) : null\"\n\t\t\t\t\t\t\t:readOnly=\"!!readOnly\"\n\t\t\t\t\t\t\t:hint=\"item.hint !== '' ? item.hint : null\"\n\t\t\t\t\t\t\t@commentClick=\"onCommentClick\"\n\t\t\t\t\t\t\t@detailClick=\"onDetailClick\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t  \n\t\t\t\t\t</transition-group>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<mounting-portal :mount-to=\"popupIdSelector\" append v-if=\"popupInstance\">\n\t\t\t\t<div class=\"bx-timeman-monitor-popup-wrap\">\t\t\t\t\t\n\t\t\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-title popup-window-titlebar\">\n\t\t\t\t\t\t\t<span class=\"bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text\">\n\t\t\t\t\t\t\t\t{{ popupContent.title }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span class=\"bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text\">\n\t\t\t\t\t\t\t\t{{ popupContent.time }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-content\" style=\"overflow: auto; background: transparent;\">\n\t\t\t\t\t\t\t<textarea \n\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-input\"\n\t\t\t\t\t\t\t\tid=\"bx-timeman-monitor-popup-input-comment\"\n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup\"\n\t\t\t\t\t\t\t\tv-model=\"comment\"\n\t\t\t\t\t\t\t\tv-bx-focus\n\t\t\t\t\t\t\t\t:placeholder=\"$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_ITEM_COMMENT')\"\n\t\t\t\t\t\t\t\t@keydown.enter.prevent.exact=\"saveComment(popupContent.privateCode)\"\n\t\t\t\t\t\t\t\t@keyup.shift.enter.exact=\"addNewLineToComment\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t<div v-if=\"isDetailPopup\" class=\"bx-timeman-monitor-popup-items-container\">\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\tv-for=\"detailItem in popupContent.detail\" \n\t\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t{{ detailItem.siteTitle }}\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t<a target=\"_blank\" :href=\"detailItem.siteUrl\" class=\"bx-timeman-monitor-popup-content-title\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{ detailItem.siteUrl }}\n\t\t\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-time\">\n\t\t\t\t\t\t\t\t\t\t{{ formatSeconds(detailItem.time) }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup\" \n\t\t\t\t\t\t\t\t@click=\"saveComment(popupContent.privateCode)\" \n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-primary\"\n\t\t\t\t\t\t\t\t:class=\"{'ui-btn-disabled': (comment.trim() === '' && popupContent.type === EntityType.absence)}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_OK') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t<button @click=\"popupInstance.destroy()\" class=\"ui-btn ui-btn-md ui-btn-light\">\n\t\t\t\t\t\t\t\t<span v-if=\"isCommentPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CANCEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span v-if=\"isDetailPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CLOSE') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</mounting-portal>\n\t\t</div>\n\t"
	});

	var Windows = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-consent-windows', {
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-monitor-report-consent-windows\">\n\t\t\t<div class=\"ui-form bx-timeman-monitor-report-consent-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t<input type=\"checkbox\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">Windows</div>\n\t\t\t\t\t</label>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var Mac = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-consent-mac', {
	  // language=Vue
	  template: "\n      <div class=\"bx-timeman-monitor-report-consent-mac\">\n\t\t  Mac\n      </div>\n\t"
	});

	var Consent = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-consent', {
	  components: {
	    Windows: Windows,
	    Mac: Mac
	  },
	  computed: {
	    isWindows: function isWindows() {
	      return navigator.userAgent.toLowerCase().includes('windows') || !this.isMac() && !this.isLinux();
	    },
	    isMac: function isMac() {
	      return navigator.userAgent.toLowerCase().includes('macintosh');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-monitor-report-consent\">\n\t\t\t<div class=\"pwt-report-header-container\">\n\t\t\t\t<div class=\"pwt-report-header-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t<div class=\"pwt-report-content\">\n                  \t<div class=\"\">\n\t\t\t\t\t<div class=\"bx-timeman-monitor-report-consent-logo-container\">\n\t\t\t\t\t\t<svg class=\"bx-timeman-monitor-report-consent-logo\"/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION') }}\n\t\t\t\t\t</div>\n\t\t\t\t\t<Windows v-if=\"isWindows\"/>\n\t\t\t\t\t<Mac v-else-if=\"isMac\"/>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width\" style=\"z-index: 0\">\n\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t<button\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\"\n\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n      </div>\n\t"
	});

	var Timeline = ui_vue.BitrixVue.localComponent('bx-timeman-monitor-report-timeline', {
	  props: {
	    readOnly: Boolean
	  },
	  mixins: [Time$1],
	  computed: {
	    EntityGroup: function EntityGroup() {
	      return timeman_const.EntityGroup;
	    },
	    Type: function Type() {
	      return main_core.Type;
	    },
	    chartData: function chartData() {
	      return this.$store.getters['monitor/getChartData'];
	    },
	    legendData: function legendData() {
	      return [{
	        id: 1,
	        type: timeman_const.EntityGroup.working.value,
	        title: timeman_const.EntityGroup.working.title + ': ' + this.formatSeconds(this.workingTime)
	      }, {
	        id: 2,
	        type: timeman_const.EntityGroup.personal.value,
	        title: timeman_const.EntityGroup.personal.title + ': ' + this.formatSeconds(this.personalTime)
	      }];
	    }
	  },
	  methods: {
	    onIntervalClick: function onIntervalClick(event) {
	      this.$emit('intervalClick', event);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-component-monitor-timeline\">\n\t\t\t<bx-timeman-component-timeline\n\t\t\t\tv-if=\"Type.isArrayFilled(chartData)\"\n\t\t\t\t:chart=\"chartData\"\n\t\t\t\t:legend=\"legendData\"\n\t\t\t\t:fixedSizeType=\"EntityGroup.inactive.value\"\n\t\t\t\t:readOnly=\"readOnly\"\n\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t/>\n\t\t</div>\n\t"
	});

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div id=\"pwt\">\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tclass=\"main-ui-loader main-ui-show\" \n\t\t\t\t\t\t\tstyle=\"width: 110px; height: 110px;\" \n\t\t\t\t\t\t\tdata-is-shown=\"true\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<svg class=\"main-ui-loader-svg\" viewBox=\"25 25 50 50\">\n\t\t\t\t\t\t\t\t<circle \n\t\t\t\t\t\t\t\t\tclass=\"main-ui-loader-svg-circle\" \n\t\t\t\t\t\t\t\t\tcx=\"50\" \n\t\t\t\t\t\t\t\t\tcy=\"50\" \n\t\t\t\t\t\t\t\t\tr=\"20\" \n\t\t\t\t\t\t\t\t\tfill=\"none\" \n\t\t\t\t\t\t\t\t\tstroke-miterlimit=\"10\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Report = /*#__PURE__*/function () {
	  function Report() {
	    babelHelpers.classCallCheck(this, Report);
	  }

	  babelHelpers.createClass(Report, [{
	    key: "loadComponents",
	    value: function loadComponents() {
	      return main_core.Runtime.loadExtension(['ui.pinner', 'ui.alerts', 'timeman.component.day-control']);
	    }
	  }, {
	    key: "open",
	    value: function open(store) {
	      var _this = this;

	      BX.SidePanel.Instance.open("timeman:pwt-report", {
	        contentCallback: function contentCallback() {
	          return _this.getAppPlaceholder();
	        },
	        animationDuration: 200,
	        width: 960,
	        closeByEsc: true,
	        title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
	        events: {
	          onOpen: function onOpen() {
	            if (main_core.Type.isFunction(BXIM.desktop.setPreventEsc)) {
	              BXIM.desktop.setPreventEsc(true);
	            }
	          },
	          onLoad: function onLoad() {
	            return _this.createEditor(store);
	          },
	          onClose: function onClose() {
	            if (main_core.Type.isFunction(BXIM.desktop.setPreventEsc)) {
	              BXIM.desktop.setPreventEsc(false);
	            }
	          },
	          onDestroy: function onDestroy() {
	            if (main_core.Type.isFunction(BXIM.desktop.setPreventEsc)) {
	              BXIM.desktop.setPreventEsc(false);
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "createEditor",
	    value: function createEditor(store) {
	      var _this2 = this;

	      this.loadComponents().then(function () {
	        return _this2.createEditorApp(store);
	      });
	    }
	  }, {
	    key: "openPreview",
	    value: function openPreview(store) {
	      var _this3 = this;

	      BX.SidePanel.Instance.open("timeman:pwt-report-preview", {
	        contentCallback: function contentCallback() {
	          return _this3.getAppPlaceholder();
	        },
	        animationDuration: 200,
	        width: 750,
	        closeByEsc: true,
	        title: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
	        label: {
	          text: main_core.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_LABEL')
	        },
	        events: {
	          onLoad: function onLoad() {
	            return _this3.createPreview(store);
	          }
	        }
	      });
	    }
	  }, {
	    key: "createPreview",
	    value: function createPreview(store) {
	      var _this4 = this;

	      this.loadComponents().then(function () {
	        return _this4.createPreviewApp(store);
	      });
	    }
	  }, {
	    key: "getAppPlaceholder",
	    value: function getAppPlaceholder() {
	      return main_core.Tag.render(_templateObject());
	    }
	  }, {
	    key: "createEditorApp",
	    value: function createEditorApp(store) {
	      ui_vue.BitrixVue.createApp({
	        components: {
	          Timeline: Timeline,
	          Group: Group,
	          AddIntervalPopup: AddIntervalPopup,
	          SelectIntervalPopup: SelectIntervalPopup,
	          Consent: Consent
	        },
	        store: store,
	        data: function data() {
	          return {
	            newInterval: null,
	            showSelectInternalPopup: false
	          };
	        },
	        computed: {
	          EntityGroup: function EntityGroup() {
	            return timeman_const.EntityGroup;
	          },
	          dateLog: function dateLog() {
	            var sentQueue = this.$store.state.monitor.sentQueue;
	            var sentQueueDateLog = main_core.Type.isArrayFilled(sentQueue) ? sentQueue[0].dateLog : '';
	            var history = this.$store.state.monitor.history;
	            var historyDateLog = main_core.Type.isArrayFilled(history) ? history[0].dateLog : '';

	            if (main_core.Type.isStringFilled(sentQueueDateLog)) {
	              return timeman_dateformatter.DateFormatter.toLong(sentQueueDateLog);
	            } else if (main_core.Type.isStringFilled(historyDateLog)) {
	              return timeman_dateformatter.DateFormatter.toLong(historyDateLog);
	            }

	            return timeman_dateformatter.DateFormatter.toLong(new Date());
	          },
	          isAllowedToStartDay: function isAllowedToStartDay() {
	            var currentDateLog = new Date(MonitorModel.prototype.getDateLog());
	            var reportDateLog = new Date(this.$store.state.monitor.reportState.dateLog);
	            var isHistorySent = BX.Timeman.Monitor.isHistorySent;

	            if (currentDateLog > reportDateLog && !isHistorySent) {
	              return false;
	            }

	            return true;
	          },
	          isPermissionGranted: function isPermissionGranted() {
	            return true;
	          }
	        },
	        methods: {
	          onIntervalClick: function onIntervalClick(event) {
	            this.newInterval = event;
	          },
	          onAddIntervalPopupHide: function onAddIntervalPopupHide() {
	            this.newInterval = null;
	          },
	          onAddIntervalPopupClose: function onAddIntervalPopupClose() {
	            this.newInterval = null;
	            this.showSelectInternalPopup = false;
	          },
	          onSelectIntervalClick: function onSelectIntervalClick() {
	            this.showSelectInternalPopup = true;
	          },
	          onSelectIntervalPopupCloseClick: function onSelectIntervalPopupCloseClick() {
	            this.showSelectInternalPopup = false;
	          }
	        },
	        // language=Vue
	        template: "\n\t\t\t\t<div class=\"pwt-report\">\n\t\t\t\t\t<Consent v-if=\"!isPermissionGranted\"/>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"pwt-report-header-container\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-header-title\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"!isAllowedToStartDay\"\n\t\t\t\t\t\t\tclass=\"pwt-report-alert ui-alert ui-alert-md ui-alert-danger ui-alert-icon-danger\">\n\t\t\t\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_NOT_SENT') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\">\n\t\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<Timeline\n\t\t\t\t\t\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-groups\">\n\t\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.working.value\"\n\t\t\t\t\t\t\t\t\t\t@selectIntervalClick=\"onSelectIntervalClick\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.personal.value\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tclass=\"\n\t\t\t\t\t\t\t\tpwt-report-button-panel-wrapper \n\t\t\t\t\t\t\t\tui-pinner \n\t\t\t\t\t\t\t\tui-pinner-bottom \n\t\t\t\t\t\t\t\tui-pinner-full-width\" \n\t\t\t\t\t\t\tstyle=\"z-index: 0\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t\t\t\t<bx-timeman-component-day-control\n\t\t\t\t\t\t\t\t\tv-if=\"isAllowedToStartDay\"\n\t\t\t\t\t\t\t\t\t:isButtonCloseHidden=\"true\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-success\" \n\t\t\t\t\t\t\t\t\tstyle=\"margin-left: 16px;\"\n\t\t\t\t\t\t\t\t\tonclick=\"BX.Timeman.Monitor.openReportPreview()\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_BUTTON') }}\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div id=\"bx-timeman-pwt-popup-editor\" class=\"bx-timeman-pwt-popup\">\n\t\t\t\t\t\t\t<SelectIntervalPopup\n\t\t\t\t\t\t\t\tv-if=\"showSelectInternalPopup\"\n\t\t\t\t\t\t\t\t@selectIntervalPopupCloseClick=\"onSelectIntervalPopupCloseClick\"\n\t\t\t\t\t\t\t\t@intervalSelected=\"onIntervalClick\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t<AddIntervalPopup\n\t\t\t\t\t\t\t\tv-if=\"newInterval\"\n\t\t\t\t\t\t\t\t:minStart=\"newInterval.start\"\n\t\t\t\t\t\t\t\t:maxFinish=\"newInterval.finish\"\n\t\t\t\t\t\t\t\t@addIntervalPopupClose=\"onAddIntervalPopupClose\"\n\t\t\t\t\t\t\t\t@addIntervalPopupHide=\"onAddIntervalPopupHide\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n                    </template>\n\t\t\t\t</div>\n\t\t\t"
	      }).mount('#pwt');
	    }
	  }, {
	    key: "createPreviewApp",
	    value: function createPreviewApp(store) {
	      ui_vue.BitrixVue.createApp({
	        components: {
	          Timeline: Timeline,
	          Group: Group,
	          Control: Control
	        },
	        store: store,
	        computed: {
	          EntityGroup: function EntityGroup() {
	            return timeman_const.EntityGroup;
	          },
	          dateLog: function dateLog() {
	            var sentQueue = this.$store.state.monitor.sentQueue;
	            var sentQueueDateLog = main_core.Type.isArrayFilled(sentQueue) ? sentQueue[0].dateLog : '';
	            var history = this.$store.state.monitor.history;
	            var historyDateLog = main_core.Type.isArrayFilled(history) ? history[0].dateLog : '';

	            if (main_core.Type.isStringFilled(sentQueueDateLog)) {
	              return timeman_dateformatter.DateFormatter.toLong(sentQueueDateLog);
	            } else if (main_core.Type.isStringFilled(historyDateLog)) {
	              return timeman_dateformatter.DateFormatter.toLong(historyDateLog);
	            }

	            return timeman_dateformatter.DateFormatter.toLong(new Date());
	          }
	        },
	        // language=Vue
	        template: "\n\t\t\t\t<div class=\"pwt-report\">\n\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t<div class=\"pwt-report-content-header\" style=\"margin-bottom: 0\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_TITLE') }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"pwt-report-content-container\">\n\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content-header\">\n\t\t\t\t\t\t\t\t<div class=\"pwt-report-content-header-title\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<Timeline\n\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"pwt-report-content\">\n\t\t\t\t\t\t\t<div class=\"pwt-report-content-groups\">\n\t\t\t\t\t\t\t\t<Group \n\t\t\t\t\t\t\t\t\t:group=\"EntityGroup.working.value\"\n\t\t\t\t\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width\" style=\"z-index: 0\">\n\t\t\t\t\t\t<div class=\"pwt-report-button-panel\">\n\t\t\t\t\t\t\t<Control/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div id=\"bx-timeman-pwt-popup-preview\" class=\"bx-timeman-pwt-popup\"/>\n\t\t\t\t</div>\n\t\t\t"
	      }).mount('#pwt');
	    }
	  }]);
	  return Report;
	}();

	var report = new Report();

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
	    key: "handleChangeDayState",
	    value: function handleChangeDayState(params) {
	      monitor.setState(params.state);

	      if (!monitor.isEnabled()) {
	        logger.warn('Ignore day state, monitor is disabled!');
	        debug.log('Ignore day state, monitor is disabled!');
	        return;
	      }

	      if (params.state === monitor.getStateStart()) {
	        monitor.start();
	      } else if (params.state === monitor.getStateStop()) {
	        monitor.stop();
	      }
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
	      this.state = options.state;
	      this.isHistorySent = options.isHistorySent;
	      this.isAway = false;
	      this.isAppInit = false;
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
	      logger.warn('History sent status: ', this.isHistorySent);
	      debug.log("History sent status: ".concat(this.isHistorySent));
	      this.removeDeprecatedStorage();
	      this.initApp();
	      pull_client.PULL.subscribe(new CommandHandler());
	    }
	  }, {
	    key: "initApp",
	    value: function initApp() {
	      var _this = this;

	      if (!this.isEnabled()) {
	        return;
	      }

	      return new Promise(function (resolve, reject) {
	        _this.initStorage().then(function (builder) {
	          _this.vuex.store = builder.store;
	          _this.vuex.models = builder.models;
	          _this.vuex.builder = builder.builder;

	          _this.vuex.store.dispatch('monitor/processUnfinishedEvents').then(function () {
	            _this.initTracker(_this.getStorage());

	            _this.isAppInit = true;
	            resolve();
	          });
	        }).catch(function () {
	          var errorMessage = "PWT: Storage initialization error";
	          logger.error(errorMessage);
	          debug.log(errorMessage);
	          reject();
	        });
	      });
	    }
	  }, {
	    key: "initStorage",
	    value: function initStorage() {
	      return new ui_vue_vuex.VuexBuilder().addModel(MonitorModel.create().setVariables(this.defaultStorageConfig).useDatabase(true)).setDatabaseConfig({
	        name: 'timeman-pwt',
	        type: ui_vue_vuex.VuexBuilder.DatabaseType.indexedDb,
	        siteId: main_core.Loc.getMessage('SITE_ID'),
	        userId: main_core.Loc.getMessage('USER_ID')
	      }).build();
	    }
	  }, {
	    key: "getStorage",
	    value: function getStorage() {
	      return this.vuex.hasOwnProperty('store') ? this.vuex.store : null;
	    }
	  }, {
	    key: "initTracker",
	    value: function initTracker(store) {
	      var _this2 = this;

	      timeman_dateformatter.DateFormatter.init(this.dateFormat);
	      timeman_timeformatter.TimeFormatter.init(this.timeFormat);
	      eventHandler.init(store);
	      sender.init(store);
	      BX.desktop.addCustomEvent('BXUserAway', function (away) {
	        return _this2.onAway(away);
	      });
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
	        this.afterTrackerInit();

	        if (this.isWorkingDayStarted()) {
	          this.start();
	        } else {
	          logger.warn('Monitor: Zzz...');
	        }
	      } else {
	        logger.warn('Monitor is disabled');
	      }
	    }
	  }, {
	    key: "openReport",
	    value: function openReport() {
	      if (!this.isEnabled()) {
	        return;
	      }

	      report.open(this.getStorage());
	    }
	  }, {
	    key: "openReportPreview",
	    value: function openReportPreview() {
	      if (!this.isEnabled()) {
	        return;
	      }

	      report.openPreview(this.getStorage());
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      var _this3 = this;

	      if (!this.isEnabled()) {
	        logger.warn("Can't start, monitor is disabled!");
	        debug.log("Can't start, monitor is disabled!");
	        return;
	      }

	      if (!this.isWorkingDayStarted()) {
	        logger.warn("Can't start monitor, working day is stopped!");
	        debug.log("Can't start monitor, working day is stopped!");
	        return;
	      }

	      if (!this.isAppInit) {
	        this.initApp().then(function () {
	          return _this3.startTracker();
	        });
	      } else {
	        this.startTracker();
	      }
	    }
	  }, {
	    key: "startTracker",
	    value: function startTracker() {
	      var _this4 = this;

	      if (!this.isAppInit) {
	        return;
	      }

	      debug.log('Monitor started');
	      debug.space();

	      if (this.isTrackerEventsApiAvailable()) {
	        logger.log('Events started');
	        BXDesktopSystem.TrackerStart();
	      }

	      this.afterTrackerInit();
	      BX.ajax.runAction('bitrix:timeman.api.monitor.setStatusWaitingData').then(function () {
	        _this4.isHistorySent = false;
	      });
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
	    key: "isTrackerEventsApiAvailable",
	    value: function isTrackerEventsApiAvailable() {
	      return BX.desktop.getApiVersion() >= 55;
	    }
	  }, {
	    key: "onAway",
	    value: function onAway(away) {
	      if (!this.isEnabled() || !this.isWorkingDayStarted()) {
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
	        this.start();
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

	      this.vuex.store.dispatch('monitor/createSentQueue').then(function () {
	        return sender.send();
	      });
	    }
	  }, {
	    key: "isWorkingDayStarted",
	    value: function isWorkingDayStarted() {
	      return this.getState() === this.getStateStart();
	    }
	  }, {
	    key: "setState",
	    value: function setState(state) {
	      this.state = state;
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return this.state;
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      return this.enabled === this.getStatusEnabled();
	    }
	  }, {
	    key: "isInactive",
	    value: function isInactive() {
	      return !(this.isEnabled() || this.getStorage() !== null || this.isAppInit);
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
	      this.isAppInit = false;
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
	    key: "getStateStart",
	    value: function getStateStart() {
	      return 'start';
	    }
	  }, {
	    key: "getStateStop",
	    value: function getStateStop() {
	      return 'stop';
	    }
	  }, {
	    key: "removeDeprecatedStorage",
	    value: function removeDeprecatedStorage() {
	      if (BX.desktop.getLocalConfig('bx_timeman_monitor_history')) {
	        BX.desktop.removeLocalConfig('bx_timeman_monitor_history');
	        logger.log("Deprecated storage has been cleared");
	        debug.log("Deprecated storage has been cleared");
	      }
	    }
	  }, {
	    key: "afterTrackerInit",
	    value: function afterTrackerInit() {
	      var _this5 = this;

	      var currentDateLog = new Date(MonitorModel.prototype.getDateLog());
	      var reportDateLog = new Date(this.vuex.store.state.monitor.reportState.dateLog);

	      if (currentDateLog > reportDateLog && this.isHistorySent) {
	        logger.warn('The next day came. Clearing the history and changing the date of the report.');
	        debug.space();
	        debug.log('The next day came. Clearing the history and changing the date of the report.');
	        this.vuex.store.dispatch('monitor/clearStorage').then(function () {
	          _this5.vuex.store.dispatch('monitor/setDateLog', MonitorModel.prototype.getDateLog());
	        });
	      }
	    }
	  }]);
	  return Monitor;
	}();

	var monitor = new Monitor();

	exports.Monitor = monitor;

}((this.BX.Timeman = this.BX.Timeman || {}),BX,BX,BX,BX,BX,BX.UI,BX.UI,BX,window,BX.Vue,BX.Main,BX.UI.Dialogs,BX,BX,BX.Timeman.Component,BX.Timeman.Const,BX,BX.Timeman,BX.Timeman,BX,BX));
//# sourceMappingURL=monitor.bundle.js.map

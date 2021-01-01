this.BX = this.BX || {};
(function (exports,main_core,pull_client) {
	'use strict';

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
	  }

	  babelHelpers.createClass(Debug, [{
	    key: "isEnabled",
	    value: function isEnabled() {
	      return monitor.isEnabled();
	    }
	  }, {
	    key: "log",
	    value: function log() {
	      if (!this.isEnabled()) {
	        return;
	      }

	      var text = this.getLogMessage.apply(this, arguments);
	      BX.desktop.log(BX.message('USER_ID') + '.monitor.log', text.substr(3));
	    }
	  }, {
	    key: "space",
	    value: function space() {
	      if (!this.isEnabled()) {
	        return;
	      }

	      BX.desktop.log(BX.message('USER_ID') + '.monitor.log', ' ');
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

	var Program = /*#__PURE__*/function () {
	  function Program(name, title, url) {
	    babelHelpers.classCallCheck(this, Program);
	    this.appName = name;
	    this.appCode = Program.createCode(this.appName);
	    this.desktopCode = Program.createCode(BXDesktopSystem.UserAccount(), BXDesktopSystem.UserOsMark());

	    if (url !== '') {
	      var host;

	      try {
	        host = new URL(url).host;
	      } catch (err) {
	        host = url;
	      }

	      this.host = host;
	      this.siteUrl = url;
	      this.siteTitle = title;
	      this.code = Program.createCode(this.appCode, this.host, this.desktopCode);
	      this.pageCode = Program.createCode(this.appCode, this.siteUrl, this.desktopCode);
	      this.siteCode = Program.createCode(this.host);
	      logger.log('New browser activity ' + url);
	      debug.log('Started NEW site', "Host: ".concat(this.host), "title: ".concat(this.siteTitle), "URL: ".concat(this.siteUrl));
	    } else {
	      this.code = Program.createCode(this.appCode, this.desktopCode);
	      logger.log('New program activity ' + this.appName);
	      debug.log('Started NEW app', "Name: ".concat(this.appName));
	    }

	    this.time = [{
	      start: new Date(),
	      finish: null
	    }];
	  }

	  babelHelpers.createClass(Program, null, [{
	    key: "createCode",
	    value: function createCode() {
	      for (var _len = arguments.length, params = new Array(_len), _key = 0; _key < _len; _key++) {
	        params[_key] = arguments[_key];
	      }

	      return BX.md5(params.join(''));
	    }
	  }]);
	  return Program;
	}();

	var ProgramManager = /*#__PURE__*/function () {
	  function ProgramManager() {
	    babelHelpers.classCallCheck(this, ProgramManager);
	  }

	  babelHelpers.createClass(ProgramManager, [{
	    key: "init",
	    value: function init(bounceTimeout) {
	      this.bounceTimeout = bounceTimeout;
	      this.history = this.loadHistory();
	      this.removeUnfinishedEvents();
	    }
	  }, {
	    key: "add",
	    value: function add(appName, siteTitle, siteUrl) {
	      var date = this.getDateForHistoryKey();

	      if (!this.history[date]) {
	        this.history[date] = [new Program(appName, siteTitle, siteUrl)];
	        logger.log('Created history for: ' + date);
	        return;
	      }

	      var site = this.findByAppNameAndSiteUrl(this.history[date], appName, siteUrl);
	      var app = this.findByAppName(this.history[date], appName);
	      this.finishLastInterval();

	      if (site !== undefined) {
	        this.startInterval(site);
	        this.saveHistory();
	        logger.log('Started interval for host: ' + site.siteUrl + ' | URL: ' + site.appName);
	        return;
	      } else if (app !== undefined && siteUrl === '') {
	        this.startInterval(app);
	        this.saveHistory();
	        logger.log('Started interval for app ' + app.appName);
	        return;
	      }

	      this.history[date].push(new Program(appName, siteTitle, siteUrl));
	      this.saveHistory();
	      logger.log(this.history);
	    }
	  }, {
	    key: "getDateForHistoryKey",
	    value: function getDateForHistoryKey() {
	      var date = new Date();

	      var addZero = function addZero(num) {
	        return num >= 0 && num <= 9 ? '0' + num : num;
	      };

	      return date.getFullYear() + '-' + addZero(date.getMonth() + 1) + '-' + addZero(date.getDate());
	    }
	  }, {
	    key: "findByAppName",
	    value: function findByAppName(programs, appName) {
	      return programs.find(function (program) {
	        return program.appName === appName;
	      });
	    }
	  }, {
	    key: "findByAppNameAndSiteUrl",
	    value: function findByAppNameAndSiteUrl(programs, appName, siteUrl) {
	      return programs.find(function (program) {
	        return program.appName === appName && program.siteUrl === siteUrl;
	      });
	    }
	  }, {
	    key: "startInterval",
	    value: function startInterval(program) {
	      program.time.push({
	        start: new Date(),
	        finish: null
	      });
	      logger.log(this.history);

	      if (program.siteTitle) {
	        debug.log('Started site', "Host: ".concat(program.host), "title: ".concat(program.siteTitle), "URL: ".concat(program.siteUrl));
	      } else {
	        debug.log('Started app', "Name: ".concat(program.appName));
	      }
	    }
	  }, {
	    key: "finishLastInterval",
	    value: function finishLastInterval() {
	      for (var day in this.history) {
	        this.history[day].forEach(function (program) {
	          program.time.forEach(function (time) {
	            if (time.finish === null) {
	              time.finish = new Date();

	              if (program.siteTitle) {
	                debug.log('Finished site', "Host: ".concat(program.host), "title: ".concat(program.siteTitle), "URL: ".concat(program.siteUrl));
	              } else {
	                debug.log('Finished app', "Name: ".concat(program.appName));
	              }
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "getGroupedHistory",
	    value: function getGroupedHistory() {
	      var history = BX.util.objectClone(this.history);
	      var bounceTime = Math.round(this.bounceTimeout / 1000);
	      var groupedHistory = {};

	      for (var day in history) {
	        groupedHistory[day] = history[day].map(this.calculateTimeInProgram).filter(function (program) {
	          return program.time > bounceTime;
	        });
	      }

	      return groupedHistory;
	    }
	  }, {
	    key: "calculateTimeInProgram",
	    value: function calculateTimeInProgram(program) {
	      program.time = program.time.map(function (interval) {
	        var finish = interval.finish ? new Date(interval.finish) : new Date();
	        return finish - new Date(interval.start);
	      }).reduce(function (sum, interval) {
	        return sum + interval;
	      }, 0);
	      program.time = Math.round(program.time / 1000);
	      return program;
	    }
	  }, {
	    key: "loadHistory",
	    value: function loadHistory() {
	      return BX.desktop.getLocalConfig('bx_timeman_monitor_history', '{}');
	    }
	  }, {
	    key: "saveHistory",
	    value: function saveHistory() {
	      BX.desktop.setLocalConfig('bx_timeman_monitor_history', this.history);
	    }
	  }, {
	    key: "removeHistoryBeforeDate",
	    value: function removeHistoryBeforeDate(actualDate) {
	      if (!actualDate) {
	        return;
	      }

	      var actualHistoryDate = new Date(actualDate + ' 00:00:00');

	      for (var date in this.history) {
	        var historyDate = new Date(date + ' 00:00:00');

	        if (historyDate < actualHistoryDate) {
	          delete this.history[date];
	          this.saveHistory();
	          logger.warn('History for the ' + date + ' has been deleted');
	        }
	      }
	    }
	  }, {
	    key: "removeUnfinishedEvents",
	    value: function removeUnfinishedEvents() {
	      for (var day in this.history) {
	        this.history[day] = this.history[day].map(function (program) {
	          program.time = program.time.filter(function (time) {
	            return time.finish !== null;
	          });
	          return program;
	        });
	      }

	      this.saveHistory();
	    }
	  }]);
	  return ProgramManager;
	}();

	var programManager = new ProgramManager();

	var EventHandler = /*#__PURE__*/function () {
	  function EventHandler() {
	    babelHelpers.classCallCheck(this, EventHandler);
	  }

	  babelHelpers.createClass(EventHandler, [{
	    key: "init",
	    value: function init() {
	      this.enabled = false;
	      this.lastApp = {
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

	      if (name === '') {
	        name = this.getNameByProcess(process);
	      }

	      if (!this.isNewEvent(name, url)) {
	        return;
	      }

	      programManager.add(name, title, url);
	      this.lastApp = {
	        name: name,
	        url: url
	      };
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
	        if (this.lastApp.name === name) {
	          return false;
	        }
	      } else {
	        if (this.lastApp.name === name && this.lastApp.url === url) {
	          return false;
	        }
	      }

	      return true;
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
	      BXDesktopSystem.ListScreenMedia(function (window) {
	        for (var index = 1; index < window.length; index++) {
	          if (window[index].id.includes('screen')) {
	            continue;
	          }

	          programManager.add(window[index].process, '', '');
	          return true;
	        }
	      });
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
	      programManager.finishLastInterval();
	      BX.desktop.setLocalConfig('bx_timeman_monitor_history', programManager.history);
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
	    value: function init() {
	      var sendTimeout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 5000;
	      var resendTimeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 5000;
	      this.enabled = false;
	      this.sendTimeout = sendTimeout;
	      this.resendTimeout = resendTimeout;
	      this.sendTimeoutId = null;
	      this.attempt = 0;
	    }
	  }, {
	    key: "send",
	    value: function send() {
	      var _this = this;

	      if (!this.enabled) {
	        return;
	      }

	      var request = this.immediatelySendHistoryOnce();
	      logger.warn('Trying to send history...');
	      request.then(function (result) {
	        if (result.status === 'success') {
	          var response = result.data;
	          logger.warn('SUCCESS!');

	          _this.saveLastSuccessfulSendDate();

	          programManager.removeHistoryBeforeDate(_this.getLastSuccessfulSendDate());
	          _this.attempt = 0;

	          _this.startSendingTimer();

	          if (response.state === monitor.getStateStop()) {
	            logger.warn('Stopped after server response');
	            debug.log('Stopped after server response');
	            monitor.setState(response.state);
	            monitor.stop();
	          }

	          if (response.enabled === monitor.getStatusDisabled()) {
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
	    key: "immediatelySendHistoryOnce",
	    value: function immediatelySendHistoryOnce() {
	      var history = JSON.stringify(programManager.getGroupedHistory());
	      debug.log('History sent');
	      return BX.ajax.runAction('bitrix:timeman.api.monitor.recordhistory', {
	        data: {
	          history: history
	        }
	      });
	    }
	  }, {
	    key: "startSendingTimer",
	    value: function startSendingTimer() {
	      this.sendTimeoutId = setTimeout(this.send.bind(this), this.getSendingDelay());
	      logger.log("Next send in ".concat(this.getSendingDelay() / 1000, " seconds..."));
	    }
	  }, {
	    key: "getSendingDelay",
	    value: function getSendingDelay() {
	      return this.attempt === 0 ? this.sendTimeout : this.resendTimeout;
	    }
	  }, {
	    key: "saveLastSuccessfulSendDate",
	    value: function saveLastSuccessfulSendDate() {
	      BX.desktop.setLocalConfig('bx_timeman_monitor_last_successful_send_date', programManager.getDateForHistoryKey());
	    }
	  }, {
	    key: "getLastSuccessfulSendDate",
	    value: function getLastSuccessfulSendDate() {
	      return BX.desktop.getLocalConfig('bx_timeman_monitor_last_successful_send_date');
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
	      this.startSendingTimer();
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
	      clearTimeout(this.sendTimeoutId);
	      this.immediatelySendHistoryOnce();
	      logger.log("Immediately send request sent");
	      logger.log("Sender stopped");
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
	        monitor.enable();

	        if (monitor.isWorkingDayStarted()) {
	          monitor.start();
	        }
	      } else {
	        monitor.stop();
	        monitor.disable();
	        logger.warn('Disabled via API');
	        debug.log('Disabled via API');
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
	      var _this = this;

	      this.enabled = options.enabled;
	      this.state = options.state;
	      this.bounceTimeout = options.bounceTimeout;
	      this.sendTimeout = options.sendTimeout;
	      this.resendTimeout = options.resendTimeout;
	      debug.space();
	      debug.log('Desktop launched!');

	      if (this.isEnabled() && logger.isEnabled()) {
	        BXDesktopSystem.LogInfo = function () {};

	        logger.start();
	      }

	      debug.log("Enabled: ".concat(this.enabled));
	      pull_client.PULL.subscribe(new CommandHandler());
	      programManager.init(this.bounceTimeout);
	      eventHandler.init();
	      sender.init(this.sendTimeout, this.resendTimeout);
	      BX.desktop.addCustomEvent('BXUserAway', function (away) {
	        return _this.onAway(away);
	      });

	      if (this.isEnabled()) {
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
	    key: "start",
	    value: function start() {
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

	      debug.log('Monitor started');
	      debug.space();
	      eventHandler.start();
	      sender.start();
	      logger.warn('Monitor started');
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      eventHandler.stop();
	      sender.stop();
	      logger.warn('Monitor stopped');
	      debug.log('Monitor stopped');
	    }
	  }, {
	    key: "onAway",
	    value: function onAway(away) {
	      if (!this.isEnabled()) {
	        return;
	      }

	      if (away) {
	        debug.space();
	        debug.log('User AWAY');
	        this.stop();
	      } else {
	        debug.space();

	        if (this.isWorkingDayStarted()) {
	          debug.log('User RETURNED, continue monitoring...');
	          this.start();
	        } else {
	          debug.log('User RETURNED, but working day is stopped');
	        }
	      }
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
	    key: "enable",
	    value: function enable() {
	      this.enabled = this.getStatusEnabled();
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      this.stop();
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
	  }]);
	  return Monitor;
	}();

	var monitor = new Monitor();

	exports.Monitor = monitor;

}((this.BX.Timeman = this.BX.Timeman || {}),BX,BX));
//# sourceMappingURL=monitor.bundle.js.map

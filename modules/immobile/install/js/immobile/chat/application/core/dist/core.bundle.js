/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,im_controller,mobile_pull_client,ui_vue_vuex) {
	'use strict';

	/**
	 * Bitrix Im mobile
	 * Application Launcher
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2020 Bitrix
	 */

	var ApplicationLauncher = function ApplicationLauncher(name) {
	  var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	  name = name.toString();
	  name = name.substr(0, 1).toUpperCase() + name.substr(1);
	  if (name === 'Launch' || name === 'Core' || name.endsWith('Application')) {
	    console.error('BX.Messenger.Application.Launch: specified name is forbidden.');
	    return new Promise(function (resolve, reject) {
	      return reject();
	    });
	  }
	  var launch = function launch() {
	    BX.Messenger.Application[name] = new BX.Messenger.Application[name + 'Application'](params);
	    return BX.Messenger.Application[name].ready();
	  };
	  if (typeof BX.Messenger.Application[name + 'Application'] === 'undefined') {
	    console.error('BX.Messenger.Application.Launch: application is not found.');
	    return new Promise(function (resolve, reject) {
	      return reject();
	    });
	  }
	  return launch();
	};

	/**
	 * Bitrix Im mobile
	 * Core application
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2020 Bitrix
	 */
	var CoreApplication = /*#__PURE__*/function () {
	  function CoreApplication() {
	    babelHelpers.classCallCheck(this, CoreApplication);
	    this.inited = false;
	    this.initPromise = new BX.Promise();
	    this.loadParams();
	  }
	  babelHelpers.createClass(CoreApplication, [{
	    key: "loadParams",
	    value: function loadParams() {
	      var _this = this;
	      if (typeof BX.componentParameters === 'undefined') {
	        setTimeout(this.loadParams.bind(this), 10);
	        return false;
	      }
	      BX.componentParameters.init().then(function (params) {
	        _this.controller = new im_controller.Controller({
	          host: currentDomain,
	          userId: params.USER_ID,
	          siteDir: params.SITE_DIR,
	          siteId: params.SITE_ID,
	          languageId: params.LANGUAGE_ID,
	          pull: {
	            instance: mobile_pull_client.PullClient,
	            client: mobile_pull_client.PULL
	          },
	          vuexBuilder: {
	            database: true,
	            databaseName: 'mobile/im',
	            databaseType: ui_vue_vuex.VuexBuilder.DatabaseType.jnSharedStorage
	          }
	        });
	        _this.controller.ready().then(function (core) {
	          _this.inited = true;
	          _this.initPromise.resolve(core);
	        });
	      });
	    }
	  }, {
	    key: "ready",
	    value: function ready() {
	      if (this.inited) {
	        var promise = new BX.Promise();
	        promise.resolve(this.controller);
	        return promise;
	      }
	      return this.initPromise;
	    }
	  }]);
	  return CoreApplication;
	}();
	var Core = new CoreApplication();

	exports.Core = Core;
	exports.Launch = ApplicationLauncher;

}((this.BX.Messenger.Application = this.BX.Messenger.Application || {}),BX.Messenger,BX,BX));
//# sourceMappingURL=core.bundle.js.map

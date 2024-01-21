/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Livefeed = this.BX.Crm.Livefeed || {};
(function (exports,main_core) {
	'use strict';

	var DisplayAlertsSupport = /*#__PURE__*/function () {
	  function DisplayAlertsSupport() {
	    babelHelpers.classCallCheck(this, DisplayAlertsSupport);
	    babelHelpers.defineProperty(this, "DisableAlert", null);
	    babelHelpers.defineProperty(this, "isShowAlert", null);
	    babelHelpers.defineProperty(this, "daysUntilDisable", null);
	    babelHelpers.defineProperty(this, "showAlertUserOption", null);
	    babelHelpers.defineProperty(this, "alertContainerSelector", null);
	    babelHelpers.defineProperty(this, "alertContainersStack", []);
	  }
	  babelHelpers.createClass(DisplayAlertsSupport, [{
	    key: "canShowAlerts",
	    value: function canShowAlerts() {
	      return this.isShowAlert === true && this.DisableAlert && main_core.Type.isString(this.alertContainerSelector) && main_core.Type.isString(this.showAlertUserOption) && main_core.Type.isInteger(this.daysUntilDisable) && this.daysUntilDisable > 0;
	    }
	  }, {
	    key: "renderAlerts",
	    value: function renderAlerts() {
	      var _this = this;
	      if (!this.canShowAlerts()) {
	        return;
	      }
	      this.alertContainersStack.forEach(function (alertContainer) {
	        _this.renderAlert(alertContainer);
	      });
	      this.alertContainersStack = [];
	    }
	  }, {
	    key: "renderAlert",
	    value: function renderAlert(container) {
	      var _this2 = this;
	      if (container.innerHTML !== '') {
	        return;
	      }
	      var closeBtnCallback = function closeBtnCallback() {
	        _this2.isShowAlert = false;
	        BX.userOptions.save('crm', _this2.showAlertUserOption, 'show', 'N');
	        var alertContainers = document.querySelectorAll(".".concat(_this2.alertContainerSelector));
	        alertContainers.forEach(function (alertContainer) {
	          alertContainer.remove();
	        });
	      };
	      main_core.Dom.style(container, {
	        background: 'white',
	        padding: '10px',
	        'margin-bottom': '-10px',
	        'border-radius': '10px 10px 0 0'
	      });
	      new this.DisableAlert({
	        alertContainer: container,
	        daysUntilDisable: this.daysUntilDisable,
	        closeBtnCallback: closeBtnCallback
	      }).render();
	    }
	  }]);
	  return DisplayAlertsSupport;
	}();
	var isCrm = window.location.pathname.includes('/crm/');
	if (!isCrm) {
	  var alertSupport = new DisplayAlertsSupport({});
	  main_core.Event.EventEmitter.subscribe('crm:disableLFAlertContainerRendered', function (event) {
	    var alertContainer = event.data.container;
	    if (alertSupport.canShowAlerts()) {
	      alertSupport.renderAlert(alertContainer);
	    } else {
	      alertSupport.alertContainersStack.push(alertContainer);
	    }
	  });
	  main_core.Runtime.loadExtension('crm.livefeed.disable-alert').then(function (exports) {
	    if (!exports.DisableAlert) {
	      alertSupport.isShowAlert = false;
	      return;
	    }
	    alertSupport.DisableAlert = exports.DisableAlert;
	    main_core.ajax.runAction('crm.controller.integration.socialnetwork.livefeed.getDisablingInfo').then(function (response) {
	      alertSupport.isShowAlert = response.data.isShowAlert;
	      alertSupport.daysUntilDisable = response.data.daysUntilDisable;
	      alertSupport.alertContainerSelector = response.data.alertContainerSelector;
	      alertSupport.showAlertUserOption = response.data.showAlertUserOption;
	      alertSupport.renderAlerts();
	    })["catch"](function (error) {
	      alertSupport.isShowAlert = false;
	    });
	  })["catch"](function (error) {
	    alertSupport.isShowAlert = false;
	  });
	}

}((this.BX.Crm.Livefeed['Disable-alert'] = this.BX.Crm.Livefeed['Disable-alert'] || {}),BX));
//# sourceMappingURL=render-helper.bundle.js.map

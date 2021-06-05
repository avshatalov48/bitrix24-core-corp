this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
(function (exports,ui_vue,timeman_const,pull_client) {
	'use strict';

	ui_vue.Vue.component('bx-timeman-component-day-control', {
	  props: ['isButtonCloseHidden'],
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
	    openDay: function openDay() {
	      this.callRestMethod('timeman.open', {}, this.setStatusByResult);
	    },
	    pauseDay: function pauseDay() {
	      this.callRestMethod('timeman.pause', {}, this.setStatusByResult);
	    },
	    closeDay: function closeDay() {
	      this.callRestMethod('timeman.close', {}, this.setStatusByResult);
	    },
	    callRestMethod: function callRestMethod(method, params, callback) {
	      this.$Bitrix.RestClient.get().callMethod(method, params, callback);
	    },
	    setStatusByResult: function setStatusByResult(result) {
	      if (!result.error()) {
	        this.status = result.data().STATUS;
	      }
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-timeman-component-day-control-wrap\">\n\t\t\t<button\n\t\t\t\tv-if=\"this.status === DayState.unknown\"\n\t\t\t\tclass=\"ui-btn ui-btn-default ui-btn-wait ui-btn-disabled\"\n\t\t\t\tstyle=\"width: 130px\"\n\t\t\t/>\n\t\t\t\n\t\t\t<button \n\t\t\t\tv-if=\"this.status === DayState.closed\" \n\t\t\t\t@click=\"openDay\"\n\t\t\t\tclass=\"ui-btn ui-btn-success ui-btn-icon-start\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_REOPEN') }}\n\t\t\t</button>\n\t\t\t\n\t\t\t<template v-if=\"this.status === DayState.opened\">\n\t\t\t\t<button\n\t\t\t\t\t@click=\"pauseDay\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-icon-pause tm-btn-pause\"\n\t\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_PAUSE') }}\n\t\t\t\t</button>\n\t\t\t\t<button\n\t\t\t\t\tv-if=\"!isButtonCloseHidden\"\n\t\t\t\t\t@click=\"closeDay\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-danger ui-btn-icon-stop\"\n\t\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}\n\t\t\t\t</button>\n\t\t\t</template>\n\t\t\t\n\t\t\t<template v-if=\"this.status === DayState.paused\">\n\t\t\t\t<button\n\t\t\t\t\t@click=\"openDay\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-icon-start tm-btn-start\"\n\t\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_RESUME') }}\n\t\t\t\t</button>\n\t\t\t\t<button\n\t\t\t\t\tv-if=\"!isButtonCloseHidden\"\n\t\t\t\t\t@click=\"closeDay\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-danger ui-btn-icon-stop\"\n\t\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}\n\t\t\t\t</button>\n\t\t\t</template>\n\t\t\t\n\t\t\t<button\n\t\t\t\tv-if=\"this.status === DayState.expired && !isButtonCloseHidden\"\n\t\t\t\t@click=\"closeDay\"\n\t\t\t\tclass=\"ui-btn ui-btn-danger ui-btn-icon-stop\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}\n\t\t\t</button>\n\t\t</div>\n\t"
	});

}((this.BX.Timeman.Component = this.BX.Timeman.Component || {}),BX,BX.Timeman.Const,BX));
//# sourceMappingURL=day-control.bundle.js.map

(function (exports,ui_vue,pull_client) {
	'use strict';

	/**
	 * Bitrix UI
	 * Pull connection status Vue component
	 *
	 * @package bitrix
	 * @subpackage pull
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-pull-component-status', {
	  /**
	   * @emits 'reconnect' {} - work only with props.canReconnect = true
	   */
	  props: {
	    canReconnect: {
	      default: false
	    }
	  },
	  data: function data() {
	    return {
	      status: pull_client.PullClient.PullStatus.Online,
	      showed: null
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.isMac = navigator.userAgent.toLowerCase().includes('macintosh');
	    this.setStatusTimeout = null;
	    this.hideTimeout = null;

	    this.pullUnSubscribe = function () {};

	    if (this.$Bitrix.PullClient.get()) {
	      this.subscribe();
	    }

	    this.$Bitrix.eventEmitter.subscribe(ui_vue.BitrixVue.events.pullClientChange, function () {
	      return _this.subscribe();
	    });
	    window.component = this;
	  },
	  beforeDestroy: function beforeDestroy() {
	    this.pullUnSubscribe();
	  },
	  methods: {
	    subscribe: function subscribe() {
	      var _this2 = this;

	      this.pullUnSubscribe();
	      this.pullUnSubscribe = this.$Bitrix.PullClient.get().subscribe({
	        type: pull_client.PullClient.SubscriptionType.Status,
	        callback: function callback(event) {
	          return _this2.statusChange(event.status);
	        }
	      });
	    },
	    reconnect: function reconnect() {
	      if (this.canReconnect) {
	        this.$emit('reconnect');
	      } else {
	        location.reload();
	      }
	    },
	    statusChange: function statusChange(status) {
	      var _this3 = this;

	      clearTimeout(this.setStatusTimeout);

	      if (this.status === status) {
	        return false;
	      }

	      var validStatus = [pull_client.PullClient.PullStatus.Online, pull_client.PullClient.PullStatus.Offline, pull_client.PullClient.PullStatus.Connecting];

	      if (validStatus.indexOf(status) < 0) {
	        return false;
	      }

	      var timeout = 500;

	      if (status === pull_client.PullClient.PullStatus.Connecting) {
	        timeout = 5000;
	      } else if (status === pull_client.PullClient.PullStatus.Offline) {
	        timeout = 2000;
	      }

	      this.setStatusTimeout = setTimeout(function () {
	        _this3.status = status;
	        _this3.showed = true;
	      }, timeout);
	      return true;
	    },
	    isMobile: function isMobile() {
	      return navigator.userAgent.toLowerCase().includes('android') || navigator.userAgent.toLowerCase().includes('webos') || navigator.userAgent.toLowerCase().includes('iphone') || navigator.userAgent.toLowerCase().includes('ipad') || navigator.userAgent.toLowerCase().includes('ipod') || navigator.userAgent.toLowerCase().includes('blackberry') || navigator.userAgent.toLowerCase().includes('windows phone');
	    }
	  },
	  watch: {
	    status: function status() {
	      var _this4 = this;

	      clearTimeout(this.hideTimeout);

	      if (this.status === pull_client.PullClient.PullStatus.Online) {
	        clearTimeout(this.hideTimeout);
	        this.hideTimeout = setTimeout(function () {
	          return _this4.showed = false;
	        }, 4000);
	      }
	    }
	  },
	  computed: {
	    connectionClass: function connectionClass() {
	      var result = '';

	      if (this.showed === true) {
	        result = "bx-pull-status-show";
	      } else if (this.showed === false) {
	        result = "bx-pull-status-hide";
	      }

	      if (this.status === pull_client.PullClient.PullStatus.Online) {
	        result += " bx-pull-status-online";
	      } else if (this.status === pull_client.PullClient.PullStatus.Offline) {
	        result += " bx-pull-status-offline";
	      } else if (this.status === pull_client.PullClient.PullStatus.Connecting) {
	        result += " bx-pull-status-connecting";
	      }

	      return result;
	    },
	    connectionText: function connectionText() {
	      var result = '';

	      if (this.status === pull_client.PullClient.PullStatus.Online) {
	        result = this.localize.BX_PULL_STATUS_ONLINE;
	      } else if (this.status === pull_client.PullClient.PullStatus.Offline) {
	        result = this.localize.BX_PULL_STATUS_OFFLINE;
	      } else if (this.status === pull_client.PullClient.PullStatus.Connecting) {
	        result = this.localize.BX_PULL_STATUS_CONNECTING;
	      }

	      return result;
	    },
	    button: function button() {
	      var hotkey = '';
	      var name = '';

	      if (this.canReconnect) {
	        name = this.localize.BX_PULL_STATUS_BUTTON_RECONNECT;
	      } else {
	        hotkey = this.isMac ? '&#8984;+R' : "Ctrl+R";
	        name = this.localize.BX_PULL_STATUS_BUTTON_RELOAD;
	      }

	      return {
	        title: name,
	        key: hotkey
	      };
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases('BX_PULL_STATUS_', this);
	    }
	  },
	  template: "\n\t\t<div v-if=\"!isMobile()\" :class=\"['bx-pull-status', connectionClass]\">\n\t\t\t<div class=\"bx-pull-status-wrap\">\n\t\t\t\t<span class=\"bx-pull-status-text\">{{connectionText}}</span>\n\t\t\t\t<span class=\"bx-pull-status-button\" @click=\"reconnect\">\n\t\t\t\t\t<span class=\"bx-pull-status-button-title\">{{button.title}}</span>\n\t\t\t\t\t<span class=\"bx-pull-status-button-key\" v-html=\"button.key\"></span>\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

}((this.window = this.window || {}),BX,BX));
//# sourceMappingURL=status.bundle.js.map

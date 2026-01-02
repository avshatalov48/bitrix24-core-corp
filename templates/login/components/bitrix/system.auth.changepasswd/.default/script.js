this.BX = this.BX || {};
(function (exports,ui_vue3,main_core) {
	'use strict';

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _application = new WeakMap();

	var _rootNode = new WeakMap();

	var _isFormVisible = new WeakMap();

	var _isCaptchaAvailable = new WeakMap();

	var _initVueApp = new WeakSet();

	var SystemAuthChangePassword = function SystemAuthChangePassword(params) {
	  babelHelpers.classCallCheck(this, SystemAuthChangePassword);

	  _initVueApp.add(this);

	  _application.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _rootNode.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _isFormVisible.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _isCaptchaAvailable.set(this, {
	    writable: true,
	    value: void 0
	  });

	  babelHelpers.classPrivateFieldSet(this, _rootNode, params.containerNode);
	  babelHelpers.classPrivateFieldSet(this, _isFormVisible, params.isFormVisible === 'Y');
	  babelHelpers.classPrivateFieldSet(this, _isCaptchaAvailable, params.isCaptchaAvailable === 'Y');

	  if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _rootNode))) {
	    return;
	  }

	  _classPrivateMethodGet(this, _initVueApp, _initVueApp2).call(this);
	};

	var _initVueApp2 = function _initVueApp2() {
	  var context = this;
	  babelHelpers.classPrivateFieldSet(this, _application, ui_vue3.BitrixVue.createApp({
	    name: 'SystemAuthChangePassword',
	    data: function data() {
	      return {
	        isWaiting: false,
	        isFormVisible: babelHelpers.classPrivateFieldGet(this.getApplication(), _isFormVisible),
	        isCaptchaAvailable: babelHelpers.classPrivateFieldGet(this.getApplication(), _isCaptchaAvailable),
	        isPasswordBlockVisible: true,
	        isCaptchaBlockVisible: false
	      };
	    },
	    beforeCreate: function beforeCreate() {
	      this.$bitrix.Application.set(context);
	    },
	    methods: {
	      onButtonClick: function onButtonClick(event) {
	        if (this.isCaptchaAvailable && !this.isCaptchaBlockVisible) {
	          event.preventDefault();
	          this.showCaptchaBlock();
	        } else {
	          this.isWaiting = true;
	        }
	      },
	      showCaptchaBlock: function showCaptchaBlock() {
	        this.isPasswordBlockVisible = false;
	        this.isCaptchaBlockVisible = true;
	      },
	      getApplication: function getApplication() {
	        return this.$bitrix.Application.get();
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _application).mount(babelHelpers.classPrivateFieldGet(this, _rootNode));
	};

	exports.SystemAuthChangePassword = SystemAuthChangePassword;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Vue3,BX));
//# sourceMappingURL=script.js.map

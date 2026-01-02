this.BX = this.BX || {};
(function (exports,main_core,ui_vue3) {
	'use strict';

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _application = new WeakMap();

	var _rootNode = new WeakMap();

	var _initVueApp = new WeakSet();

	var SystemAuthInitialize = function SystemAuthInitialize(params) {
	  babelHelpers.classCallCheck(this, SystemAuthInitialize);

	  _initVueApp.add(this);

	  _application.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _rootNode.set(this, {
	    writable: true,
	    value: void 0
	  });

	  babelHelpers.classPrivateFieldSet(this, _rootNode, params.containerNode);

	  if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _rootNode))) {
	    return;
	  }

	  _classPrivateMethodGet(this, _initVueApp, _initVueApp2).call(this);
	};

	var _initVueApp2 = function _initVueApp2() {
	  var context = this;
	  babelHelpers.classPrivateFieldSet(this, _application, ui_vue3.BitrixVue.createApp({
	    name: 'SystemAuthInitialize',
	    data: function data() {
	      return {
	        isWaiting: false
	      };
	    },
	    beforeCreate: function beforeCreate() {
	      this.$bitrix.Application.set(context);
	    },
	    mounted: function mounted() {
	      if (this.$refs && main_core.Type.isDomNode(this.$refs.modalInput)) {
	        this.$refs.modalInput.focus();
	      }
	    },
	    methods: {
	      onButtonClick: function onButtonClick() {
	        this.isWaiting = true;
	      },
	      getApplication: function getApplication() {
	        return this.$bitrix.Application.get();
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _application).mount(babelHelpers.classPrivateFieldGet(this, _rootNode));
	};

	exports.SystemAuthInitialize = SystemAuthInitialize;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Vue3));
//# sourceMappingURL=script.js.map

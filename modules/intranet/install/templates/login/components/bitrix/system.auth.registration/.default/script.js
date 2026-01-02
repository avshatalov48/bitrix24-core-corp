this.BX = this.BX || {};
(function (exports,main_core,ui_vue3) {
	'use strict';

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _application = new WeakMap();

	var _rootNode = new WeakMap();

	var _isEmailRequired = new WeakMap();

	var _isConfirmRegistrationBlockVisible = new WeakMap();

	var _isCaptchaAvailable = new WeakMap();

	var _userName = new WeakMap();

	var _userLastName = new WeakMap();

	var _userEmail = new WeakMap();

	var _userLogin = new WeakMap();

	var _initVueApp = new WeakSet();

	var SystemAuthRegistration = function SystemAuthRegistration(params) {
	  var _params$userName, _params$userLastName, _params$userEmail, _params$userLogin;

	  babelHelpers.classCallCheck(this, SystemAuthRegistration);

	  _initVueApp.add(this);

	  _application.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _rootNode.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _isEmailRequired.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _isConfirmRegistrationBlockVisible.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _isCaptchaAvailable.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _userName.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _userLastName.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _userEmail.set(this, {
	    writable: true,
	    value: void 0
	  });

	  _userLogin.set(this, {
	    writable: true,
	    value: void 0
	  });

	  babelHelpers.classPrivateFieldSet(this, _rootNode, params.containerNode);
	  babelHelpers.classPrivateFieldSet(this, _isEmailRequired, params.isEmailRequired === 'Y');
	  babelHelpers.classPrivateFieldSet(this, _isConfirmRegistrationBlockVisible, params.isConfirmRegistrationBlockVisible === 'Y');
	  babelHelpers.classPrivateFieldSet(this, _isCaptchaAvailable, params.isCaptchaAvailable === 'Y');
	  babelHelpers.classPrivateFieldSet(this, _userName, (_params$userName = params.userName) !== null && _params$userName !== void 0 ? _params$userName : '');
	  babelHelpers.classPrivateFieldSet(this, _userLastName, (_params$userLastName = params.userLastName) !== null && _params$userLastName !== void 0 ? _params$userLastName : '');
	  babelHelpers.classPrivateFieldSet(this, _userEmail, (_params$userEmail = params.userEmail) !== null && _params$userEmail !== void 0 ? _params$userEmail : '');
	  babelHelpers.classPrivateFieldSet(this, _userLogin, (_params$userLogin = params.userLogin) !== null && _params$userLogin !== void 0 ? _params$userLogin : '');

	  _classPrivateMethodGet(this, _initVueApp, _initVueApp2).call(this);

	  BX.UI.Hint.init(babelHelpers.classPrivateFieldGet(this, _rootNode));
	};

	var _initVueApp2 = function _initVueApp2() {
	  var context = this;
	  babelHelpers.classPrivateFieldSet(this, _application, ui_vue3.BitrixVue.createApp({
	    name: 'SystemAuthRegistration',
	    data: function data() {
	      return {
	        isNameBlockVisible: true,
	        isPasswordBlockVisible: false,
	        isCaptchaBlockVisible: false,
	        isBackButtonVisible: false,
	        userName: babelHelpers.classPrivateFieldGet(this.getApplication(), _userName),
	        userLastName: babelHelpers.classPrivateFieldGet(this.getApplication(), _userLastName),
	        userEmail: babelHelpers.classPrivateFieldGet(this.getApplication(), _userEmail),
	        userLogin: babelHelpers.classPrivateFieldGet(this.getApplication(), _userLogin),
	        isEmailRequired: babelHelpers.classPrivateFieldGet(this.getApplication(), _isEmailRequired),
	        isConfirmRegistrationBlockVisible: babelHelpers.classPrivateFieldGet(this.getApplication(), _isConfirmRegistrationBlockVisible),
	        isCaptchaAvailable: babelHelpers.classPrivateFieldGet(this.getApplication(), _isCaptchaAvailable),
	        isWaiting: false,
	        inputPasswordType: 'password',
	        inputConfirmPasswordType: 'password'
	      };
	    },
	    beforeCreate: function beforeCreate() {
	      this.$bitrix.Application.set(context);
	    },
	    computed: {
	      fullName: function fullName() {
	        return this.toFullName();
	      },
	      email: function email() {
	        return this.userEmail;
	      },
	      activeOrDisabledButtonClass: function activeOrDisabledButtonClass() {
	        return this.isEnteredValidLoginAndEmail() ? 'ui-btn-success' : 'ui-btn-disabled';
	      }
	    },
	    mounted: function mounted() {
	      if (this.$refs && main_core.Type.isDomNode(this.$refs.modalInput)) {
	        this.$refs.modalInput.focus();
	      }
	    },
	    methods: {
	      onButtonClick: function onButtonClick(event) {
	        if (this.isNameBlockVisible) {
	          event.preventDefault();

	          if (this.isEnteredValidLoginAndEmail()) {
	            this.showPasswordBlock();
	            this.isBackButtonVisible = true;
	          }
	        } else if (this.isPasswordBlockVisible && this.isCaptchaAvailable) {
	          event.preventDefault();
	          this.showCaptchaBlock();
	          this.isBackButtonVisible = true;
	        } else {
	          this.isWaiting = true;
	        }
	      },
	      onBackButtonClick: function onBackButtonClick() {
	        if (this.isPasswordBlockVisible) {
	          this.showNameBlock();
	          this.isBackButtonVisible = false;
	        } else if (this.isCaptchaAvailable && this.isCaptchaBlockVisible) {
	          this.showPasswordBlock();
	        }
	      },
	      showNameBlock: function showNameBlock() {
	        this.isNameBlockVisible = true;
	        this.isPasswordBlockVisible = false;
	        this.isCaptchaBlockVisible = false;
	      },
	      showPasswordBlock: function showPasswordBlock() {
	        this.isNameBlockVisible = false;
	        this.isPasswordBlockVisible = true;
	        this.isCaptchaBlockVisible = false;
	      },
	      showCaptchaBlock: function showCaptchaBlock() {
	        this.isNameBlockVisible = false;
	        this.isPasswordBlockVisible = false;
	        this.isCaptchaBlockVisible = true;
	      },
	      toFullName: function toFullName() {
	        var nameSplitter = this.userName !== '' && this.userLastName !== '' ? ' ' : '';
	        return "".concat(this.userName).concat(nameSplitter).concat(this.userLastName);
	      },
	      onEnterUserEmail: function onEnterUserEmail(value) {
	        this.userEmail = value;
	      },
	      isEmailEnteredCorrectly: function isEmailEnteredCorrectly() {
	        return main_core.Validation.isEmail(this.userEmail);
	      },
	      onEnterUserLogin: function onEnterUserLogin(value) {
	        this.userLogin = value;
	      },
	      isLoginEnteredCorrectly: function isLoginEnteredCorrectly() {
	        return this.userLogin.length >= 3;
	      },
	      onEnterUserName: function onEnterUserName(value) {
	        this.userName = value;
	      },
	      onEnterUserLastName: function onEnterUserLastName(value) {
	        this.userLastName = value;
	      },
	      isEnteredValidLoginAndEmail: function isEnteredValidLoginAndEmail() {
	        return this.isLoginEnteredCorrectly() && (!this.isEmailRequired || this.isEmailRequired && this.isEmailEnteredCorrectly());
	      },
	      onEyeMouseDown: function onEyeMouseDown(inputElementName) {
	        if (inputElementName === 'PASSWORD') {
	          this.inputPasswordType = 'text';
	        } else {
	          this.inputConfirmPasswordType = 'text';
	        }
	      },
	      onEyeMouseUp: function onEyeMouseUp(inputElementName) {
	        if (inputElementName === 'PASSWORD') {
	          this.inputPasswordType = 'password';
	        } else {
	          this.inputConfirmPasswordType = 'password';
	        }
	      },
	      getApplication: function getApplication() {
	        return this.$bitrix.Application.get();
	      }
	    }
	  }));
	  babelHelpers.classPrivateFieldGet(this, _application).mount(babelHelpers.classPrivateFieldGet(this, _rootNode));
	};

	exports.SystemAuthRegistration = SystemAuthRegistration;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Vue3));
//# sourceMappingURL=script.js.map

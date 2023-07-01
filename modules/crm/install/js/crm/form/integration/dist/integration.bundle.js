this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_sidepanelContent,main_core,main_core_events,crm_form_type,crm_form_fields_mapper,ui_alerts,ui_buttons,ui_dropdown,main_core_ajax,main_loader,seo_ads_login,ui_dialogs_messagebox) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var instances = [];

	/**
	 * Crm-From Integration
	 *
	 * @memberOf BX.Crm.Form
	 */
	var _container = /*#__PURE__*/new WeakMap();
	var _profileContainer = /*#__PURE__*/new WeakMap();
	var _pagesContainer = /*#__PURE__*/new WeakMap();
	var _formsContainer = /*#__PURE__*/new WeakMap();
	var _mapperContainer = /*#__PURE__*/new WeakMap();
	var _adForms = /*#__PURE__*/new WeakMap();
	var _adFormsErrors = /*#__PURE__*/new WeakMap();
	var _adAccounts = /*#__PURE__*/new WeakMap();
	var _seoEventHandler = /*#__PURE__*/new WeakMap();
	var _onClickChangeDirection = /*#__PURE__*/new WeakSet();
	var _onLogedIn = /*#__PURE__*/new WeakSet();
	var _loginProfile = /*#__PURE__*/new WeakSet();
	var _logoutProfile = /*#__PURE__*/new WeakSet();
	var _loginGroup = /*#__PURE__*/new WeakSet();
	var _logoutGroup = /*#__PURE__*/new WeakSet();
	var _renderProfileSelector = /*#__PURE__*/new WeakSet();
	var _setPageId = /*#__PURE__*/new WeakSet();
	var _renderPageSelector = /*#__PURE__*/new WeakSet();
	var _renderFormSelector = /*#__PURE__*/new WeakSet();
	var _renderLoader = /*#__PURE__*/new WeakSet();
	var _renderMapper = /*#__PURE__*/new WeakSet();
	var _checkNewProfile = /*#__PURE__*/new WeakSet();
	var _requestAuthUrl = /*#__PURE__*/new WeakSet();
	var Integration = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Integration, _EventEmitter);
	  function Integration(_options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Integration);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Integration).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _requestAuthUrl);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _checkNewProfile);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderMapper);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderLoader);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderFormSelector);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderPageSelector);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _setPageId);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderProfileSelector);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _logoutGroup);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _loginGroup);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _logoutProfile);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _loginProfile);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onLogedIn);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onClickChangeDirection);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _profileContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _pagesContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _formsContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _mapperContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _adForms, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _adFormsErrors, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _adAccounts, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _seoEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    _this.type = _options.type;
	    _this.form = _options.form;
	    _this.fields = _options.fields;
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _seoEventHandler, function (options) {
	      _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _onLogedIn, _onLogedIn2).call(babelHelpers.assertThisInitialized(_this), options);
	      options.reload = false;
	    });
	    _this.dictionary = _options.dictionary;
	    BX.addCustomEvent(window, 'seo-client-auth-result', babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _seoEventHandler));
	    instances.forEach(function (instance) {
	      return instance.destroy();
	    });
	    instances = [];
	    instances.push(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Integration, [{
	    key: "destroy",
	    value: function destroy() {
	      BX.removeCustomEvent(window, 'seo-client-auth-result', babelHelpers.classPrivateFieldGet(this, _seoEventHandler));
	    }
	  }, {
	    key: "getCase",
	    value: function getCase() {
	      var _this2 = this;
	      var item = this.form.integration.cases.filter(function (item) {
	        return item.providerCode === _this2.type;
	      })[0] || null;
	      if (!item) {
	        var profile = (this.getProvider() || {}).profile || {};
	        item = {
	          linkDirection: 1,
	          providerCode: this.type,
	          date: null,
	          account: {
	            id: profile.id || null,
	            name: profile.name || null
	          },
	          form: {
	            id: null,
	            name: null
	          },
	          fieldsMapping: []
	        };
	        this.form.integration.cases.push(item);
	      }
	      return item;
	    }
	  }, {
	    key: "getProvider",
	    value: function getProvider() {
	      var _this3 = this;
	      return this.dictionary.integration.providers.filter(function (item) {
	        return item.type === _this3.type;
	      })[0] || null;
	    }
	  }, {
	    key: "getTypeTitle",
	    value: function getTypeTitle() {
	      return main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PROVIDER_' + this.type.toUpperCase());
	    }
	  }, {
	    key: "getAdForm",
	    value: function getAdForm() {
	      var id = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      if (id === null) {
	        id = this.getCase().form.id + '';
	      }
	      return (babelHelpers.classPrivateFieldGet(this, _adForms) || []).filter(function (item) {
	        return item.id === id;
	      })[0] || null;
	    }
	  }, {
	    key: "getAdAccount",
	    value: function getAdAccount() {
	      var id = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      if (id === null) {
	        id = this.getCase().account.id;
	      }
	      return (babelHelpers.classPrivateFieldGet(this, _adAccounts) || []).filter(function (item) {
	        return item.id === id;
	      })[0] || null;
	    }
	  }, {
	    key: "getAdFormId",
	    value: function getAdFormId() {
	      var obj = this.getAdForm();
	      if (obj && babelHelpers.classPrivateFieldGet(this, _adForms).some(function (item) {
	        return item.id === obj.id;
	      })) {
	        return obj.id;
	      }
	      return null;
	    }
	  }, {
	    key: "getAdAccountId",
	    value: function getAdAccountId() {
	      var obj = this.getAdAccount();
	      if (obj && babelHelpers.classPrivateFieldGet(this, _adAccounts).some(function (item) {
	        return item.id === obj.id;
	      })) {
	        return obj.id;
	      }
	      return null;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this4 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      }
	      babelHelpers.classPrivateFieldGet(this, _container).innerHTML = '';
	      if (!this.dictionary.integration.canUse) {
	        return babelHelpers.classPrivateFieldGet(this, _container);
	      }
	      var currentCase = this.getCase();
	      if (currentCase && currentCase.linkDirection === 0) {
	        // show alert and button for changing direction
	        babelHelpers.classPrivateFieldGet(this, _container).appendChild(new ui_alerts.Alert({
	          color: ui_alerts.Alert.Color.WARNING,
	          text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_NEW_INTEGRATION', {
	            '%providerName%': this.getTypeTitle()
	          })
	        }).render());
	        babelHelpers.classPrivateFieldGet(this, _container).appendChild(new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_NEW_INTEGRATION_BTN'),
	          color: ui_buttons.ButtonColor.PRIMARY,
	          onclick: function onclick() {
	            return _classPrivateMethodGet(_this4, _onClickChangeDirection, _onClickChangeDirection2).call(_this4);
	          }
	        }).render());
	        return babelHelpers.classPrivateFieldGet(this, _container);
	      }
	      babelHelpers.classPrivateFieldGet(this, _container).appendChild(_classPrivateMethodGet(this, _renderProfileSelector, _renderProfileSelector2).call(this));
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "showBannerForOldProfile",
	    value: function showBannerForOldProfile() {
	      var _this5 = this;
	      var message = ui_dialogs_messagebox.MessageBox.create({
	        message: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_MESSAGE'),
	        title: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_TITLE'),
	        minWidth: 517,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_BTN_YES'),
	          color: ui_buttons.Button.Color.SUCCESS,
	          onclick: function onclick() {
	            return _classPrivateMethodGet(_this5, _loginProfile, _loginProfile2).call(_this5);
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_ALERT_POPUP_BTN_OK'),
	          color: ui_buttons.Button.Color.LIGHT_BORDER,
	          onclick: function onclick() {
	            message.close();
	          }
	        })]
	      });
	      message.show();
	    }
	  }, {
	    key: "showAvatar",
	    value: function showAvatar(provider) {
	      if (provider.profile.picture !== undefined) {
	        return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div>\n\t\t\t\t<div\n\t\t\t\t\tclass=\"crm-ads-conversion-social-avatar-icon\"\n\t\t\t\t\tstyle=\"background-image: url(", ")\"\n\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t</div>"])), main_core.Tag.safe(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["", ""])), provider.profile.picture));
	      }
	    }
	  }, {
	    key: "handleLoginCompletionError",
	    value: function handleLoginCompletionError(error) {
	      //show banner
	    }
	  }, {
	    key: "createLogoutProfileButton",
	    value: function createLogoutProfileButton() {
	      return new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGOUT_BTN'),
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        round: true,
	        size: ui_buttons.Button.Size.EXTRA_SMALL,
	        onclick: _classPrivateMethodGet(this, _logoutProfile, _logoutProfile2).bind(this)
	      });
	    }
	  }]);
	  return Integration;
	}(main_core_events.EventEmitter);
	function _onClickChangeDirection2() {
	  this.getCase().linkDirection = 1;
	  this.emit('change');
	  this.render();
	}
	function _onLogedIn2(options) {
	  var _this6 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	    return;
	  }
	  main_core_ajax.ajax.runAction('crm.api.form.getDict', {
	    json: {}
	  }).then(function (response) {
	    return response.data;
	  }).then(function (data) {
	    _this6.dictionary.integration = data.integration;
	    if (/.group/.test(options.engine || '')) {
	      _classPrivateMethodGet(_this6, _renderPageSelector, _renderPageSelector2).call(_this6);
	    } else {
	      _classPrivateMethodGet(_this6, _renderProfileSelector, _renderProfileSelector2).call(_this6);
	    }
	  });
	  main_core_ajax.ajax.runAction('crm.api.ads.leadads.account.loginCompletion', {
	    data: {
	      type: this.type
	    }
	  }).then(function (data) {}, function (error) {
	    _this6.handleLoginCompletionError(error);
	  });
	}
	function _loginProfile2() {
	  seo_ads_login.LoginFactory.getLoginObject({
	    TYPE: this.type,
	    ENGINE_CODE: this.getProvider().engineCode,
	    AUTH_URL: this.getProvider().authUrl
	  }).login();
	}
	function _logoutProfile2() {
	  var _this7 = this;
	  main_core_ajax.ajax.runAction('crm.api.ads.leadads.service.logout', {
	    data: {
	      type: this.type
	    }
	  }).then(function () {
	    _classPrivateMethodGet(_this7, _requestAuthUrl, _requestAuthUrl2).call(_this7);
	    babelHelpers.classPrivateFieldSet(_this7, _adAccounts, null);
	    _this7.getProvider().profile = null;
	    _classPrivateMethodGet(_this7, _renderProfileSelector, _renderProfileSelector2).call(_this7);
	    babelHelpers.classPrivateFieldSet(_this7, _adForms, null);
	  });
	}
	function _loginGroup2() {
	  var popup = BX.util.popup('', 800, 600);
	  main_core_ajax.ajax.runAction('crm.api.ads.leadads.service.registerGroup', {
	    data: {
	      type: this.type,
	      group: this.getAdAccountId()
	    }
	  }).then(function (response) {
	    popup.location = response.data.authUrl;
	  });
	}
	function _logoutGroup2() {
	  var _this8 = this;
	  var group = this.getProvider().group;
	  if (!group.groupId) {
	    return Promise.resolve();
	  }
	  return main_core_ajax.ajax.runAction('crm.api.ads.leadads.service.logoutGroup', {
	    data: {
	      type: this.type,
	      groupId: group.groupId
	    }
	  }).then(function (response) {
	    _this8.getProvider().group.hasAuth = false;
	    _classPrivateMethodGet(_this8, _renderPageSelector, _renderPageSelector2).call(_this8);
	  });
	}
	function _renderProfileSelector2() {
	  var _this9 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _profileContainer)) {
	    babelHelpers.classPrivateFieldSet(this, _profileContainer, main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	  }
	  babelHelpers.classPrivateFieldGet(this, _profileContainer).innerHTML = '';
	  var provider = this.getProvider();
	  if (!provider.profile) {
	    babelHelpers.classPrivateFieldGet(this, _profileContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.PRIMARY,
	      text: "\n\t\t\t\t\t<div class=\"ui-slider-heading-3\">\n\t\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_TITLE', {
	        '%providerName%': this.getTypeTitle()
	      }), "\n\t\t\t\t\t</div>\n\t\t\t\t\t<p class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_DESC', {
	        '%providerName%': this.getTypeTitle()
	      }), "\n\t\t\t\t\t</p>\n\t\t\t\t")
	    }).render());
	    babelHelpers.classPrivateFieldGet(this, _profileContainer).appendChild(new ui_buttons.Button({
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_LOGIN_BTN'),
	      color: ui_buttons.ButtonColor.PRIMARY,
	      onclick: function onclick() {
	        return _classPrivateMethodGet(_this9, _loginProfile, _loginProfile2).call(_this9);
	      }
	    }).render());
	    return babelHelpers.classPrivateFieldGet(this, _profileContainer);
	  }
	  babelHelpers.classPrivateFieldGet(this, _profileContainer).appendChild(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"crm-ads-conversion-block\">\n\t\t\t\t\t<div class=\"crm-ads-conversion-social crm-ads-conversion-social-facebook\"  style=\"padding-bottom: 15px; height: 58px;\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"crm-ads-conversion-social-user\">\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\ttarget=\"_top\"\n\t\t\t\t\t\t\t\tclass=\"crm-ads-conversion-social-user-link\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-ads-conversion-social-shutoff\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.showAvatar(provider), provider.profile.url ? 'href="' + main_core.Tag.safe(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["", ""])), provider.profile.url) + '"' : "", main_core.Tag.safe(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["", ""])), provider.profile.name), this.createLogoutProfileButton().render()));
	  if (this.type === 'vkontakte') {
	    babelHelpers.classPrivateFieldGet(this, _profileContainer).appendChild(_classPrivateMethodGet(this, _renderFormSelector, _renderFormSelector2).call(this));
	  } else {
	    babelHelpers.classPrivateFieldGet(this, _profileContainer).appendChild(_classPrivateMethodGet(this, _renderPageSelector, _renderPageSelector2).call(this));
	  }
	  if (this.type === 'vkontakte') {
	    _classPrivateMethodGet(this, _checkNewProfile, _checkNewProfile2).call(this);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _profileContainer);
	}
	function _setPageId2(id) {
	  this.getCase().account.id = id || '';
	  this.getCase().account.name = (this.getAdAccount(id) || {}).name;
	  this.emit('change');
	  babelHelpers.classPrivateFieldSet(this, _adForms, null);
	  _classPrivateMethodGet(this, _renderPageSelector, _renderPageSelector2).call(this);
	}
	function _renderPageSelector2() {
	  var _this10 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _pagesContainer)) {
	    babelHelpers.classPrivateFieldSet(this, _pagesContainer, main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	  }
	  babelHelpers.classPrivateFieldGet(this, _pagesContainer).innerHTML = '';
	  if (!babelHelpers.classPrivateFieldGet(this, _adAccounts)) {
	    babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(_classPrivateMethodGet(this, _renderLoader, _renderLoader2).call(this));
	    main_core_ajax.ajax.runAction('crm.api.ads.leadads.account.getAccounts', {
	      data: {
	        type: this.type,
	        proxyId: null
	      }
	    }).then(function (response) {
	      babelHelpers.classPrivateFieldSet(_this10, _adAccounts, response.data.accounts.map(function (item) {
	        return {
	          id: item.id + '',
	          name: item.name + ''
	        };
	      }));
	      _classPrivateMethodGet(_this10, _renderPageSelector, _renderPageSelector2).call(_this10);
	    });
	    return babelHelpers.classPrivateFieldGet(this, _pagesContainer);
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _adAccounts).length === 0) {
	    babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.PRIMARY,
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_EMPTY', {
	        '%providerName%': this.getTypeTitle()
	      })
	    }).render());
	    return babelHelpers.classPrivateFieldGet(this, _pagesContainer);
	  }
	  var id = this.getAdAccountId();
	  var pagesDropdown = new BX.Landing.UI.Field.Dropdown({
	    selector: 'page-list',
	    title: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_' + this.type.toUpperCase()),
	    content: id,
	    items: [{
	      name: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_NOT_SELECTED'),
	      value: ''
	    }].concat(babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(this, _adAccounts).map(function (item) {
	      return {
	        value: item.id,
	        name: item.name
	      };
	    })))
	  });
	  pagesDropdown.subscribe('onChange', function () {
	    _classPrivateMethodGet(_this10, _setPageId, _setPageId2).call(_this10, pagesDropdown.getValue());
	  });
	  var container = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-form-integration-page-container\"></div>"])));
	  var selectorContainer = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-form-integration-page-selector\"></div>"])));
	  selectorContainer.appendChild(pagesDropdown.getNode());
	  container.appendChild(selectorContainer);
	  babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(container);
	  var group = this.getProvider().group;
	  var hasAuthGroup = group.isAuthUsed && group.hasAuth && group.groupId;
	  if (hasAuthGroup && id && id !== group.groupId) {
	    babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.WARNING,
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_VKONTAKTE_RESTRICTED', {
	        '%groupName%': (this.getAdAccount(group.groupId) || {}).name
	      })
	    }).render());
	    var groupConnectBtn = new ui_buttons.Button({
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_DISCONNECT_BTN'),
	      color: ui_buttons.ButtonColor.PRIMARY,
	      className: '',
	      onclick: function onclick() {
	        groupConnectBtn.setWaiting(true);
	        _classPrivateMethodGet(_this10, _logoutGroup, _logoutGroup2).call(_this10).then(function () {
	          groupConnectBtn.setWaiting(false);
	        })["catch"](function () {
	          groupConnectBtn.setWaiting(false);
	        });
	      }
	    });
	    babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(groupConnectBtn.render());
	    return babelHelpers.classPrivateFieldGet(this, _pagesContainer);
	  }
	  if (id && group.isAuthUsed && !group.hasAuth) {
	    var _groupConnectBtn = new ui_buttons.Button({
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CONNECT_BTN'),
	      color: ui_buttons.ButtonColor.PRIMARY,
	      className: '',
	      onclick: function onclick() {
	        return _classPrivateMethodGet(_this10, _loginGroup, _loginGroup2).call(_this10);
	      }
	    });
	    container.appendChild(_groupConnectBtn.render());
	    babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.PRIMARY,
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CONNECT_INFO')
	    }).render());
	    return babelHelpers.classPrivateFieldGet(this, _pagesContainer);
	  }
	  babelHelpers.classPrivateFieldGet(this, _pagesContainer).appendChild(_classPrivateMethodGet(this, _renderFormSelector, _renderFormSelector2).call(this));
	  return babelHelpers.classPrivateFieldGet(this, _pagesContainer);
	}
	function _renderFormSelector2() {
	  var _this11 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _formsContainer)) {
	    babelHelpers.classPrivateFieldSet(this, _formsContainer, main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	  }
	  babelHelpers.classPrivateFieldGet(this, _formsContainer).innerHTML = '';

	  // hack for vk
	  if (!this.getCase().account.id && this.type === 'vkontakte') {
	    babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(_classPrivateMethodGet(this, _renderLoader, _renderLoader2).call(this));
	    main_core_ajax.ajax.runAction('crm.api.ads.leadads.account.getProfile', {
	      data: {
	        type: this.type,
	        proxyId: null
	      }
	    }).then(function (response) {
	      babelHelpers.classPrivateFieldSet(_this11, _adAccounts, [{
	        id: response.data.profile.id + '',
	        name: response.data.profile.name + ''
	      }]);
	      _this11.getCase().account.id = babelHelpers.classPrivateFieldGet(_this11, _adAccounts)[0].id;
	      _this11.getCase().account.name = babelHelpers.classPrivateFieldGet(_this11, _adAccounts)[0].name;
	      _classPrivateMethodGet(_this11, _renderFormSelector, _renderFormSelector2).call(_this11);
	    });
	    return babelHelpers.classPrivateFieldGet(this, _formsContainer);
	  }
	  if (this.getProvider().hasPages) {
	    var accountId = this.getAdAccountId();
	    if (!accountId) {
	      babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(new ui_alerts.Alert({
	        color: ui_alerts.Alert.Color.PRIMARY,
	        text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_PAGE_CHOOSE')
	      }).render());
	      return babelHelpers.classPrivateFieldGet(this, _formsContainer);
	    }
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _adForms)) {
	    babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(_classPrivateMethodGet(this, _renderLoader, _renderLoader2).call(this));
	    main_core_ajax.ajax.runAction('crm.api.ads.leadads.form.list', {
	      data: {
	        type: this.type,
	        accountId: this.getAdAccountId() || 0,
	        proxyId: null
	      }
	    }).then(function (response) {
	      babelHelpers.classPrivateFieldSet(_this11, _adForms, response.data.forms.map(function (item) {
	        item.id += '';
	        return item;
	      }));
	      _classPrivateMethodGet(_this11, _renderFormSelector, _renderFormSelector2).call(_this11);
	    })["catch"](function (response) {
	      babelHelpers.classPrivateFieldSet(_this11, _adFormsErrors, response.errors);
	      babelHelpers.classPrivateFieldSet(_this11, _adForms, []);
	      _classPrivateMethodGet(_this11, _renderFormSelector, _renderFormSelector2).call(_this11);
	    });
	    return babelHelpers.classPrivateFieldGet(this, _formsContainer);
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _adForms).length === 0) {
	    babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.PRIMARY,
	      text: babelHelpers.classPrivateFieldGet(this, _adFormsErrors).length > 0 ? babelHelpers.classPrivateFieldGet(this, _adFormsErrors)[0].message : main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_FORM_EMPTY', {
	        '%providerName%': this.getTypeTitle()
	      })
	    }).render());
	    return babelHelpers.classPrivateFieldGet(this, _formsContainer);
	  }
	  var formsDropdown = new BX.Landing.UI.Field.Dropdown({
	    selector: 'form-list',
	    title: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_FORM'),
	    content: this.getAdFormId(),
	    items: [{
	      name: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_NOT_SELECTED'),
	      value: ''
	    }].concat(babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(this, _adForms).map(function (item) {
	      return {
	        name: item.name,
	        value: item.id
	      };
	    })))
	  });
	  formsDropdown.subscribe('onChange', function () {
	    var formId = formsDropdown.getValue();
	    _this11.getCase().form.id = formId;
	    _this11.getCase().form.name = (_this11.getAdForm(formId) || {}).name;
	    _this11.getCase().fieldsMapping = [];
	    _this11.emit('change');
	    _classPrivateMethodGet(_this11, _renderMapper, _renderMapper2).call(_this11);
	  });
	  babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(formsDropdown.getNode());
	  babelHelpers.classPrivateFieldGet(this, _formsContainer).appendChild(_classPrivateMethodGet(this, _renderMapper, _renderMapper2).call(this));
	  return babelHelpers.classPrivateFieldGet(this, _formsContainer);
	}
	function _renderLoader2() {
	  var container = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<div style=\"position: relative; min-height: 100px;\"></div>"])));
	  new main_loader.Loader().show(container).then(function () {});
	  return container;
	}
	function _renderMapper2() {
	  var _this$getAdForm,
	    _this12 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _mapperContainer)) {
	    babelHelpers.classPrivateFieldSet(this, _mapperContainer, main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	  }
	  babelHelpers.classPrivateFieldGet(this, _mapperContainer).innerHTML = '';
	  if (!this.getAdForm()) {
	    babelHelpers.classPrivateFieldGet(this, _mapperContainer).appendChild(new ui_alerts.Alert({
	      color: ui_alerts.Alert.Color.PRIMARY,
	      text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_FORM_CHOOSE')
	    }).render());
	    return babelHelpers.classPrivateFieldGet(this, _mapperContainer);
	  }
	  var mappingMessageContainer = main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["<div style=\"margin-bottom: 29px\"></div>"])));
	  new ui_alerts.Alert({
	    color: ui_alerts.Alert.Color.PRIMARY,
	    text: main_core.Loc.getMessage('CRM_FORM_INTEGRATION_JS_FIELD_MAP')
	  }).renderTo(mappingMessageContainer);
	  babelHelpers.classPrivateFieldGet(this, _mapperContainer).appendChild(mappingMessageContainer);
	  if (((_this$getAdForm = this.getAdForm()) === null || _this$getAdForm === void 0 ? void 0 : _this$getAdForm.fields) === undefined) {
	    babelHelpers.classPrivateFieldGet(this, _mapperContainer).appendChild(_classPrivateMethodGet(this, _renderLoader, _renderLoader2).call(this));
	    main_core_ajax.ajax.runAction('crm.api.ads.leadads.form.get', {
	      data: {
	        type: this.type,
	        accountId: this.getAdAccountId() || 0,
	        proxyId: null,
	        formId: this.getAdForm().id
	      }
	    }).then(function (response) {
	      _this12.getAdForm().fields = response.data.form.fields;
	      _classPrivateMethodGet(_this12, _renderMapper, _renderMapper2).call(_this12);
	    })["catch"](function (response) {
	      babelHelpers.classPrivateFieldSet(_this12, _adFormsErrors, response.errors);
	      _classPrivateMethodGet(_this12, _renderMapper, _renderMapper2).call(_this12);
	    });
	    return babelHelpers.classPrivateFieldGet(this, _mapperContainer);
	  }
	  var mapper = new crm_form_fields_mapper.Mapper({
	    from: {
	      caption: this.getTypeTitle()
	    },
	    fields: this.fields,
	    map: this.getAdForm().fields.map(function (field) {
	      var outputCode = _this12.getCase().fieldsMapping.filter(function (item) {
	        return item.adsFieldKey === field.key;
	      })[0];
	      outputCode = (outputCode || {}).crmFieldKey || '';
	      if (!outputCode) {
	        outputCode = _this12.getProvider().defaultMapping.filter(function (item) {
	          return item.adsFieldType.toLowerCase() === (field.type || '').toLowerCase();
	        })[0];
	        outputCode = (outputCode || {}).crmFieldType || '';
	      }
	      return {
	        inputType: (field.type || '').toLowerCase(),
	        inputCode: field.key,
	        inputName: field.label,
	        outputCode: outputCode,
	        outputName: '',
	        data: {
	          items: field.options || []
	        }
	      };
	    })
	  });
	  var emitChangeEvent = function emitChangeEvent() {
	    var eventFields = [];
	    _this12.getCase().fieldsMapping = mapper.getMap().map(function (item) {
	      if (item.outputCode) {
	        eventFields.push({
	          name: item.outputCode
	        });
	      }
	      return {
	        crmFieldKey: item.outputCode,
	        adsFieldKey: item.inputCode,
	        items: item.data.items || []
	      };
	    }).filter(function (item) {
	      return item.crmFieldKey;
	    });
	    _this12.emit('change', {
	      fields: eventFields
	    });
	  };
	  emitChangeEvent();
	  mapper.subscribe('change', emitChangeEvent);
	  babelHelpers.classPrivateFieldGet(this, _mapperContainer).appendChild(mapper.render());
	  return babelHelpers.classPrivateFieldGet(this, _mapperContainer);
	}
	function _checkNewProfile2() {
	  var _this13 = this;
	  main_core_ajax.ajax.runAction('crm.api.ads.leadads.service.checkProfile', {
	    data: {
	      type: this.type
	    }
	  }).then(function (response) {}, function (error) {
	    _classPrivateMethodGet(_this13, _logoutProfile, _logoutProfile2).call(_this13);
	    _this13.showBannerForOldProfile();
	  });
	}
	function _requestAuthUrl2() {
	  var _this14 = this;
	  main_core_ajax.ajax.runAction('crm.api.ads.leadads.service.getAuthUrl', {
	    data: {
	      type: this.type
	    }
	  }).then(function (response) {
	    _this14.getProvider().authUrl = response.data.authUrl;
	  });
	}

	exports.Integration = Integration;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX,BX,BX.Event,BX.Crm.Form,BX.Crm.Form.Fields,BX.UI,BX.UI,BX,BX,BX,BX.Seo.Ads,BX.UI.Dialogs));
//# sourceMappingURL=integration.bundle.js.map

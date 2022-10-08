this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_sidepanel_layout,seo_ads_login,main_core) {
	'use strict';

	var Conversion = /*#__PURE__*/function () {
	  function Conversion() {
	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, Conversion);
	    this.width = width;
	  }

	  babelHelpers.createClass(Conversion, [{
	    key: "getSliderId",
	    value: function getSliderId() {
	      return '';
	    }
	  }, {
	    key: "show",
	    value: function show() {}
	  }]);
	  return Conversion;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13;
	var FacebookConversion = /*#__PURE__*/function (_Conversion) {
	  babelHelpers.inherits(FacebookConversion, _Conversion);

	  function FacebookConversion() {
	    var _this;

	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, FacebookConversion);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FacebookConversion).call(this, width));
	    _this.tag = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div/>"])));
	    BX.addCustomEvent(window, 'seo-client-auth-result', function (eventData) {
	      var _this$slider;

	      eventData.reload = false;
	      (_this$slider = _this.slider) === null || _this$slider === void 0 ? void 0 : _this$slider.reload();
	    });
	    return _this;
	  }

	  babelHelpers.createClass(FacebookConversion, [{
	    key: "getSliderId",
	    value: function getSliderId() {
	      return this.code + ':conversion';
	    }
	  }, {
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      switch (this.type) {
	        case 'facebook.form':
	          return main_core.Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_TITLE');

	        case 'facebook.lead':
	          return main_core.Loc.getMessage('CRM_ADS_CONVERSION_LEAD_SLIDER_TITLE');

	        case 'facebook.deal':
	          return main_core.Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_TITLE');

	        case 'facebook.payment':
	          return main_core.Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');

	        default:
	          return '';
	      }
	    }
	  }, {
	    key: "notify",
	    value: function notify(message) {
	      BX.UI.Notification.Center.notify({
	        content: message
	      });
	    }
	  }, {
	    key: "saveConfiguration",
	    value: function saveConfiguration(data) {
	      return BX.ajax.runAction('crm.ads.conversion.saveConfiguration', {
	        data: {
	          code: this.code,
	          configuration: data
	        }
	      });
	    }
	  }, {
	    key: "onItemEnable",
	    value: function onItemEnable(id, switcher) {
	      var _this2 = this;

	      this.data.configuration.items = main_core.Type.isArray(this.data.configuration.items) ? this.data.configuration.items : [];

	      if (!this.data.configuration.items.includes(id)) {
	        this.data.configuration.items.push(id);
	      }

	      this.saveConfiguration(this.data.configuration).then(function (response) {
	        if (!response.data.success) {
	          switcher.check(true, false);

	          _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	        }
	      })["catch"](function () {
	        switcher.check(true, false);

	        _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	      });
	    }
	  }, {
	    key: "onItemDisable",
	    value: function onItemDisable(id, switcher) {
	      var _this3 = this;

	      this.data.configuration.items = main_core.Type.isArray(this.data.configuration.items) ? this.data.configuration.items : [];

	      for (var i = 0; i < this.data.configuration.items.length; i++) {
	        if (id == this.data.configuration.items[i]) {
	          this.data.configuration.items.splice(i, 1);
	          break;
	        }
	      }

	      this.saveConfiguration(this.data.configuration).then(function (response) {
	        if (!response.data.success) {
	          switcher.check(true, false);

	          _this3.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	        }
	      })["catch"](function () {
	        switcher.check(true, false);

	        _this3.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	      });
	    }
	  }, {
	    key: "login",
	    value: function login() {
	      seo_ads_login.LoginFactory.getLoginObject({
	        'TYPE': 'facebook',
	        'ENGINE_CODE': 'business.facebook'
	      }).login();
	    }
	  }, {
	    key: "loadData",
	    value: function loadData() {
	      return BX.ajax.runAction('crm.ads.conversion.load', {
	        data: {
	          type: this.code
	        }
	      });
	    }
	  }, {
	    key: "logout",
	    value: function logout() {
	      var _this$slider2,
	          _this4 = this;

	      (_this$slider2 = this.slider) === null || _this$slider2 === void 0 ? void 0 : _this$slider2.showLoader();
	      return BX.ajax.runAction('crm.ads.conversion.logout', {
	        data: {},
	        analyticsLabel: {
	          connect: "FBE",
	          action: "disconnect",
	          type: "disconnect"
	        }
	      }).then(function () {
	        var _this4$slider;

	        (_this4$slider = _this4.slider) === null || _this4$slider === void 0 ? void 0 : _this4$slider.reload();
	      })["catch"](function () {
	        var _this4$slider2;

	        (_this4$slider2 = _this4.slider) === null || _this4$slider2 === void 0 ? void 0 : _this4$slider2.reload();
	      });
	    }
	  }, {
	    key: "getContentTitle",
	    value: function getContentTitle() {
	      return '';
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this5 = this;

	      this.tag.innerHTML = '';

	      if (this.data && this.data.available) {
	        this.tag.appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-slider-section crm-ads-conversion-title-section\">\n\t\t\t\t\t<span class=\"ui-icon ui-slider-icon ui-icon-service-fb\">\n\t\t\t\t\t\t<i></i>\n\t\t\t\t\t</span>\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-paragraph-2\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<ul class=\"ui-slider-list crm-ads-conversion-features\">\n\t\t\t\t\t\t\t<li class=\"ui-slider-list-item\">\n\t\t\t\t\t\t\t\t<span class=\"ui-slider-list-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"ui-slider-list-item\">\n\t\t\t\t\t\t\t\t<span class=\"ui-slider-list-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"ui-slider-list-item\">\n\t\t\t\t\t\t\t\t<span class=\"ui-slider-list-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"ui-slider-list-item\">\n\t\t\t\t\t\t\t\t<span class=\"ui-slider-list-text\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t\t<a \n\t\t\t\t\t\t\thref=\"https://www.facebook.com/business/help/1292598407460746?id=1205376682832142\" \n\t\t\t\t\t\t\tclass=\"ui-slider-link\" \n\t\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TITLE'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_1'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_2'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_3'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_4'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_LINK')));

	        if (this.data.auth && this.data.profile) {
	          this.tag.appendChild(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-slider-section crm-ads-conversion-account-section\">\n\t\t\t\t\t\t<div class=\"crm-ads-conversion-block\">\n\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-social crm-ads-conversion-social-facebook\">\n\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-social-avatar\">\n\t\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\t\tclass=\"crm-ads-conversion-social-avatar-icon\" \n\t\t\t\t\t\t\t\t\t\tstyle=\"background-image: url(", ")\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-social-user\">\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\ttarget=\"_top\" \n\t\t\t\t\t\t\t\t\t\tclass=\"crm-ads-conversion-social-user-link\" \n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-social-shutoff\">\n\t\t\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\t\t\tclass=\"crm-ads-conversion-social-shutoff-link\" \n\t\t\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Tag.safe(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["", ""])), this.data.profile.picture), this.data.profile.url ? 'href="' + main_core.Tag.safe(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["", ""])), this.data.profile.url) + '"' : "", main_core.Tag.safe(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["", ""])), this.data.profile.name), this.logout.bind(this), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGOUT')));

	          if (main_core.Type.isArray(this.data.items)) {
	            this.tag.appendChild(this.data.items.reduce(function (tag, value) {
	              var itemNode = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-settings-block\">\n\t\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-settings-name-block\">\n\t\t\t\t\t\t\t\t\t\t<span class=\"crm-ads-conversion-settings-name\">", "</span>\n\t\t\t\t\t\t\t\t\t\t<span class=\"crm-ads-conversion-settings-detail\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-settings-control\">\n\t\t\t\t\t\t\t\t\t\t<span data-switcher-node=\"\"/>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t"])), main_core.Tag.safe(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["", ""])), value.name), main_core.Loc.getMessage('CRM_ADS_CONVERSION_DETAIL'));
	              var switcher = new BX.UI.Switcher({
	                node: itemNode.querySelector('[data-switcher-node]'),
	                checked: value.enable
	              });
	              switcher.handlers = {
	                checked: _this5.onItemDisable.bind(_this5, value.id, switcher),
	                unchecked: _this5.onItemEnable.bind(_this5, value.id, switcher)
	              };
	              tag.appendChild(itemNode);
	              return tag;
	            }, main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"ui-slider-section crm-ads-conversion-settings\">\n\t\t\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t\t</div>"])), main_core.Tag.safe(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["", ""])), this.getScriptMessage()))));
	          }
	        } else {
	          this.tag.appendChild(main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-slider-section\">\n\t\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t\t<div class=\"ui-slider-heading-3\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-login-block\">\n\t\t\t\t\t\t\t\t<div class=\"crm-ads-conversion-login-wrapper\">\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\tclass=\"webform-small-button webform-small-button-transparent\"\n\t\t\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t\t<span class=\"crm-ads-conversion-settings-detail\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_CONNECT_TITLE'), this.login.bind(this), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN'), main_core.Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_5')));
	        }
	      } else {
	        this.tag.appendChild(main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-slider-no-access\">\n\t\t\t\t\t<div class=\"ui-slider-no-access-inner\">\n\t\t\t\t\t\t<div class=\"ui-slider-no-access-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-slider-no-access-subtitle\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-slider-no-access-img\">\n\t\t\t\t\t\t\t<div class=\"ui-slider-no-access-img-inner\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_TITLE_SERVICE_ERROR'), this.errors.length > 0 ? this.errors.reduce(function (unique, value) {
	          var item = main_core.Tag.safe(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["", ""])), _this5.getErrorMessage(value));

	          if (unique.includes(item)) {
	            unique.push(item);
	          }

	          return unique;
	        }, []).join('<br>') : main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_MESSAGE_SERVICE_ERROR')));
	      }
	    }
	  }, {
	    key: "getErrorMessage",
	    value: function getErrorMessage(value) {
	      if (main_core.Type.isObject(value) && value.code) {
	        if (value.code === "permissions") {
	          return main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_ACCESS_DENY');
	        }

	        if (value.code === "modules" && value.customData) {
	          switch (value.customData.module) {
	            case "seo":
	              return main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SEO_NOT_INSTALLED');

	            case "crm":
	              return main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_CRM_NOT_INSTALLED');

	            case "socialservices":
	              return main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SOCIAL_NOT_INSTALLED');
	          }
	        }
	      }

	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_UNKNOWN_ERROR');
	    }
	  }, {
	    key: "saveData",
	    value: function saveData(data) {
	      if (data) {
	        data.configuration = main_core.Type.isObject(data.configuration) ? data.configuration : {};
	        this.data = data;
	      }
	    }
	  }, {
	    key: "saveErrors",
	    value: function saveErrors(errors) {
	      this.errors = main_core.Type.isArray(errors) ? errors : [];
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this$slider3,
	          _this6 = this,
	          _ref,
	          _this$width,
	          _BX$SidePanel$Instanc;

	      this.slider = (_this$slider3 = this.slider) !== null && _this$slider3 !== void 0 ? _this$slider3 : BX.SidePanel.Instance.open(this.getSliderId(), {
	        contentCallback: function contentCallback() {
	          return main_core.Runtime.loadExtension('ui.sidepanel.layout').then(function () {
	            return BX.UI.SidePanel.Layout.createContent({
	              title: _this6.getContentTitle(),
	              extensions: ['crm.ads.conversion', 'ui.forms', 'ui.switcher', 'seo.ads.login'],
	              design: {
	                section: false,
	                margin: true
	              },
	              content: function content() {
	                return _this6.loadData().then(function (response) {
	                  _this6.saveData(response.data);

	                  _this6.saveErrors(response.errors);

	                  _this6.render();

	                  return _this6.tag;
	                })["catch"](function (response) {
	                  _this6.saveData(response.data);

	                  _this6.saveErrors(response.errors);

	                  _this6.render();

	                  if (((response.errors || [])[0] || {}).code === 100) {
	                    BX.UI.InfoHelper.show('crm_ad_conversion');
	                  }

	                  return _this6.tag;
	                });
	              }
	            });
	          });
	        },
	        title: this.getSliderTitle(),
	        width: (_ref = (_this$width = this.width) !== null && _this$width !== void 0 ? _this$width : (_BX$SidePanel$Instanc = BX.SidePanel.Instance.getTopSlider()) === null || _BX$SidePanel$Instanc === void 0 ? void 0 : _BX$SidePanel$Instanc.getWidth()) !== null && _ref !== void 0 ? _ref : 800
	      }) ? BX.SidePanel.Instance.getSlider(this.getSliderId()) : null;
	    }
	  }]);
	  return FacebookConversion;
	}(Conversion);

	var Deal = /*#__PURE__*/function (_FacebookConversion) {
	  babelHelpers.inherits(Deal, _FacebookConversion);

	  function Deal() {
	    var _this;

	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, Deal);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Deal).call(this, width));
	    _this.code = 'facebook.deal';
	    return _this;
	  }

	  babelHelpers.createClass(Deal, [{
	    key: "getContentTitle",
	    value: function getContentTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_DEAL_CONTENT_TITLE');
	    }
	  }, {
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_TITLE');
	    }
	  }, {
	    key: "getScriptMessage",
	    value: function getScriptMessage() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_ITEM_TITLE');
	    }
	  }]);
	  return Deal;
	}(FacebookConversion);

	var Form = /*#__PURE__*/function (_FacebookConversion) {
	  babelHelpers.inherits(Form, _FacebookConversion);

	  function Form() {
	    var _this;

	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, Form);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Form).call(this, width));
	    _this.code = 'facebook.webform';
	    return _this;
	  }

	  babelHelpers.createClass(Form, [{
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_TITLE');
	    }
	  }, {
	    key: "getScriptMessage",
	    value: function getScriptMessage() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_ITEM_TITLE');
	    }
	  }, {
	    key: "getContentTitle",
	    value: function getContentTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_FORM_CONTENT_TITLE');
	    }
	  }]);
	  return Form;
	}(FacebookConversion);

	var Payment = /*#__PURE__*/function (_FacebookConversion) {
	  babelHelpers.inherits(Payment, _FacebookConversion);

	  function Payment() {
	    var _this;

	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, Payment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Payment).call(this, width));
	    _this.code = 'facebook.payment';
	    return _this;
	  }

	  babelHelpers.createClass(Payment, [{
	    key: "getScriptMessage",
	    value: function getScriptMessage() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');
	    }
	  }, {
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');
	    }
	  }, {
	    key: "onItemEnable",
	    value: function onItemEnable(id, switcher) {
	      this.onOptionClick(switcher);
	    }
	  }, {
	    key: "onItemDisable",
	    value: function onItemDisable(id, switcher) {
	      this.onOptionClick(switcher);
	    }
	  }, {
	    key: "getContentTitle",
	    value: function getContentTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_CONTENT_TITLE');
	    }
	  }, {
	    key: "onOptionClick",
	    value: function onOptionClick(switcher) {
	      var _this2 = this;

	      this.data.configuration.enable = !(this.data.configuration.enable == 'true');
	      this.saveConfiguration(this.data.configuration).then(function (response) {
	        if (!response.data.success) {
	          switcher.check(!_this2.data.configuration.enabled, false);

	          _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	        }
	      })["catch"](function () {
	        switcher.check(!_this2.data.configuration.enabled, false);

	        _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	      });
	    }
	  }, {
	    key: "saveData",
	    value: function saveData(data) {
	      if (data) {
	        data.configuration = main_core.Type.isObject(data.configuration) ? data.configuration : {};
	        data.items = [{
	          id: null,
	          name: main_core.Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_OPTION'),
	          enable: data.configuration.enable == 'true'
	        }];
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Payment.prototype), "saveData", this).call(this, data);
	    }
	  }]);
	  return Payment;
	}(FacebookConversion);

	var Lead = /*#__PURE__*/function (_FacebookConversion) {
	  babelHelpers.inherits(Lead, _FacebookConversion);

	  function Lead() {
	    var _this;

	    var width = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 800;
	    babelHelpers.classCallCheck(this, Lead);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Lead).call(this, width));
	    _this.code = 'facebook.lead';
	    return _this;
	  }

	  babelHelpers.createClass(Lead, [{
	    key: "getScriptMessage",
	    value: function getScriptMessage() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_LEAD_SLIDER_TITLE');
	    }
	  }, {
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_LEAD_SLIDER_TITLE');
	    }
	  }, {
	    key: "onItemEnable",
	    value: function onItemEnable(id, switcher) {
	      this.onOptionClick(switcher);
	    }
	  }, {
	    key: "onItemDisable",
	    value: function onItemDisable(id, switcher) {
	      this.onOptionClick(switcher);
	    }
	  }, {
	    key: "getContentTitle",
	    value: function getContentTitle() {
	      return main_core.Loc.getMessage('CRM_ADS_CONVERSION_LEAD_CONTENT_TITLE');
	    }
	  }, {
	    key: "onOptionClick",
	    value: function onOptionClick(switcher) {
	      var _this2 = this;

	      this.data.configuration.enable = !(this.data.configuration.enable == 'true');
	      this.saveConfiguration(this.data.configuration).then(function (response) {
	        if (!response.data.success) {
	          switcher.check(!_this2.data.configuration.enabled, false);

	          _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	        }
	      })["catch"](function () {
	        switcher.check(!_this2.data.configuration.enabled, false);

	        _this2.notify(main_core.Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
	      });
	    }
	  }, {
	    key: "saveData",
	    value: function saveData(data) {
	      if (data) {
	        data.configuration = main_core.Type.isObject(data.configuration) ? data.configuration : {};
	        data.items = [{
	          id: null,
	          name: main_core.Loc.getMessage('CRM_ADS_CONVERSION_LEAD_OPTION'),
	          enable: data.configuration.enable == 'true'
	        }];
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Lead.prototype), "saveData", this).call(this, data);
	    }
	  }]);
	  return Lead;
	}(FacebookConversion);

	var Registry = /*#__PURE__*/function () {
	  function Registry() {
	    babelHelpers.classCallCheck(this, Registry);
	  }

	  babelHelpers.createClass(Registry, null, [{
	    key: "conversion",
	    value: function conversion(code) {
	      switch (code) {
	        case 'facebook_conversion_deal':
	          return new Deal();

	        case 'facebook_conversion_webform':
	          return new Form();

	        case 'facebook_conversion_payment':
	          return new Payment();

	        case 'facebook_conversion_lead':
	          return new Lead();
	      }
	    }
	  }]);
	  return Registry;
	}();

	exports.Registry = Registry;

}((this.BX.Crm.Ads = this.BX.Crm.Ads || {}),BX.UI.SidePanel,BX.Seo.Ads,BX));
//# sourceMappingURL=registry.bundle.js.map

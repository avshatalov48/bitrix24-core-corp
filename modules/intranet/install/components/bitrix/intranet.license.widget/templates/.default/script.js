this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core_events,ui_vue3,main_loader,main_core,ui_vue) {
	'use strict';

	var LoaderComponent = {
	  props: {
	    size: {
	      type: Number,
	      "default": 85
	    }
	  },
	  template: "\n\t\t<div></div>\n\t",
	  mounted: function mounted() {
	    this.loader = new main_loader.Loader({
	      target: this.$el,
	      size: this.size
	    });
	    this.loader.show();
	  },
	  beforeDestroy: function beforeDestroy() {
	    this.loader.destroy();
	  }
	};

	var ExpirationLevel = {
	  normal: 0,
	  soonExpired: 1,
	  almostExpired: 2,
	  expired: 4,
	  blocked: 8
	};
	function getExpirationLevel(daysLeft) {
	  if (daysLeft <= -14) {
	    return ExpirationLevel.soonExpired | ExpirationLevel.almostExpired | ExpirationLevel.expired | ExpirationLevel.blocked;
	  }

	  if (daysLeft <= 0) {
	    return ExpirationLevel.soonExpired | ExpirationLevel.almostExpired | ExpirationLevel.expired;
	  }

	  if (daysLeft <= 14) {
	    return ExpirationLevel.soonExpired | ExpirationLevel.almostExpired;
	  }

	  if (daysLeft <= 30) {
	    return ExpirationLevel.soonExpired;
	  }

	  return ExpirationLevel.normal;
	}

	var ContentComponent = {
	  props: ["license", "market", "telephony", "isAdmin", "partner"],
	  computed: {
	    ExpirationLevel: function ExpirationLevel$$1() {
	      return ExpirationLevel;
	    },
	    getTariffName: function getTariffName() {
	      return this.license.isDemo ? main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE_DEMO') : main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE');
	    },
	    getTariffButtonName: function getTariffButtonName() {
	      return this.license.isDemo ? main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_BUY') : main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_PROLONG');
	    },
	    getTariffIconCLass: function getTariffIconCLass() {
	      var expirationLevel = this.license.expirationLevel;

	      if (expirationLevel & expirationLevel.expired) {
	        return 'license-widget-item-icon--low';
	      }

	      if (expirationLevel & expirationLevel.soonExpired) {
	        return 'license-widget-item-icon--expired';
	      }

	      return 'license-widget-item-icon--start';
	    },
	    getMarketClass: function getMarketClass() {
	      if (this.market.isExpired || this.market.isAlmostExpired) {
	        return 'license-widget-item--expired';
	      } else if (this.market.isPaid || this.market.isDemo) {
	        return 'license-widget-item--active';
	      }

	      return '';
	    },
	    getMarketIconClass: function getMarketIconClass() {
	      if (this.market.isExpired) {
	        return 'license-widget-item-icon--expired';
	      }

	      if (this.market.isAlmostExpired) {
	        return 'license-widget-item-icon--low';
	      }

	      return 'license-widget-item-icon--mp';
	    }
	  },
	  methods: {
	    sendAnalytics: function sendAnalytics(code) {
	      main_core.ajax.runAction("intranet.license.analyticsLabel", {
	        data: {},
	        analyticsLabel: {
	          helperCode: code,
	          headerPopup: "Y"
	        }
	      });
	    },
	    showInfoHelper: function showInfoHelper(type) {
	      var articleCode = "";

	      if (type === "market") {
	        articleCode = "limit_benefit_market";
	      } else if (type === "whyPay") {
	        articleCode = "limit_why_pay_tariff";
	      } else if (type === "licenseDesc") {
	        articleCode = "limit_why_pay_tariff__".concat(this.license.type);
	      }

	      BX.UI.InfoHelper.show(articleCode);
	      this.sendAnalytics(articleCode);
	    },
	    showHelper: function showHelper() {
	      console.error('intranet.license.widget: needs a helper iformation.');

	      if (this.license.isDemo) {
	        var article = this.isCloud ? "limit_support_bitrix" : "limit_support_bitrix_box";
	        BX.UI.InfoHelper.show(article);
	      } else {
	        var _article = this.isCloud ? "12925062" : "12983582";

	        BX.Helper.show("redirect=detail&code=".concat(_article));
	      }
	    },
	    showPartner: function showPartner() {
	      if (this.partner.isPartnerOrder) {
	        var formLang = this.partner.formLang;
	        BX.UI.Feedback.Form.open({
	          id: 'intranet-license-partner',
	          portalUri: this.partner.formPortalUri,
	          forms: [{
	            zones: ['en', 'eu', 'in', 'uk'],
	            id: 1194,
	            lang: formLang,
	            sec: '6nivh3'
	          }, {
	            zones: ['de'],
	            id: 1195,
	            lang: formLang,
	            sec: 'q1rq2q'
	          }, {
	            zones: ['la', 'co', 'mx'],
	            id: 1196,
	            lang: formLang,
	            sec: 'dkdhid'
	          }, {
	            zones: ['com.br'],
	            id: 1197,
	            lang: formLang,
	            sec: 'nvobax'
	          }, {
	            zones: ['pl'],
	            id: 1198,
	            lang: formLang,
	            sec: 'h1013r'
	          }, {
	            zones: ['it'],
	            id: 1199,
	            lang: formLang,
	            sec: 'xsrbsh'
	          }, {
	            zones: ['fr'],
	            id: 1200,
	            lang: formLang,
	            sec: '3oupk4'
	          }, {
	            zones: ['tr'],
	            id: 1202,
	            lang: formLang,
	            sec: 'k3bnjz'
	          }, {
	            zones: ['vn'],
	            id: 1201,
	            lang: formLang,
	            sec: '9dxb9d'
	          }, {
	            zones: ['ua'],
	            id: 1204,
	            lang: formLang,
	            sec: '277p0u'
	          }, {
	            zones: ['by'],
	            id: 1205,
	            lang: formLang,
	            sec: '31inm5'
	          }, {
	            zones: ['kz'],
	            id: 1203,
	            lang: formLang,
	            sec: '6nkdb1'
	          }, {
	            zones: ['ru'],
	            id: 1192,
	            lang: formLang,
	            sec: 'b5mzdk'
	          }],
	          defaultForm: {
	            id: 1194,
	            lang: 'en',
	            sec: '6nivh3'
	          }
	        });
	      } else {
	        showPartnerForm(this.partner.connectPartnerJs);
	      }
	    },
	    showMarketDemoPopup: function showMarketDemoPopup(e) {
	      var _this = this;

	      BX.loadExt('marketplace').then(function () {
	        if (!!_this.market.landingBuyCode && _this.market.landingBuyCode !== '') {
	          BX.UI.InfoHelper.show(_this.market.landingBuyCode);
	        } else {
	          BX.rest.Marketplace.openDemoSubscription(function () {
	            window.location.href = '/settings/license.php?subscription_trial=Y&analyticsLabel[headerPopup]=Y';
	          });
	        }
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"license-widget\">\n\t\t\t<!-- region license -->\n\t\t\t<div\n\t\t\t\tclass=\"license-widget-item license-widget-item--main license-widget-item-margin-bottom-1x\"\n\t\t\t\t:class=\"{ 'license-widget-item--expired' : license.expirationLevel > ExpirationLevel.soonExpired }\">\n\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t<div class=\"license-widget-item-icon\" :class=\"getTariffIconCLass\" ></div>\n\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>{{ getTariffName }}</span>\n\t\t\t\t\t\t\t\t<!--<span data-hint=\"Hint\"></span>-->\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tv-if=\"license.isDemo\"\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t\t@click=\"showInfoHelper('whyPay')\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_DESCRIPTION_WHY') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t\t\t\t\t@click=\"showInfoHelper('licenseDesc')\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_DESCRIPTION_TARIFF') }}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-if=\"license.expirationLevel & ExpirationLevel.soonExpired\"\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-info license-widget-item-info--exp\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_DAYS_LEFT_SHORT', {'#NUM_DAYS#': license.daysLeft}) }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-info\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_TILL', {'#LICENSE_TILL#': license.expireDate}) }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<a :href=\"license.allPath\" target=\"_blank\" class=\"license-widget-item-btn\">\n\t\t\t\t\t\t{{ getTariffButtonName }}\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<!-- endregion -->\n\n\t\t\t<!-- region vox & demo -->\n\t\t\t<div class=\"license-widget-block license-widget-item-margin-bottom-1x\">\n\t\t\t\t<div\n\t\t\t\t\tclass=\"license-widget-item\"\n\t\t\t\t\t:class=\"{ 'license-widget-item--active' : telephony.isConnected }\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-icon license-widget-item-icon--tel\"></div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_TELEPHONY') }}</span>\n\t\t\t\t\t\t\t\t\t<!--<span data-hint=\"Hint\"></span>-->\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div v-if=\"telephony.isConnected\" class=\"license-widget-item-info\">\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\t:href=\"telephony.buyPath\"\n\t\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-info-text\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_TELEPHONY_CONNECTED') }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\tv-if=\"telephony.isConnected\"\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-text\"\n\t\t\t\t\t\t\t\t\tv-html=\"telephony.balanceFormatted\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\tv-if=\"!telephony.isConnected\"\n\t\t\t\t\t\t\t\t\t:href=\"telephony.buyPath\"\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-btn\"\n\t\t\t\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_CONNECT') }}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div \n\t\t\t\t\tclass=\"license-widget-item license-widget-item--active\"\n\t\t\t\t\t:class=\"{ 'license-widget-item--active' : license.isDemo }\"\n\t\t\t\t\t>\n\t\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-icon\"\n\t\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\t\t(license.isDemo && (license.expirationLevel & ExpirationLevel.soonExpired))\n\t\t\t\t\t\t\t\t\t? 'license-widget-item-icon--expdemo'\n\t\t\t\t\t\t\t\t\t: 'license-widget-item-icon--demo'\n\t\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_DEMO') }}</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t\t\t:href=\"license.demoPath\"\n\t\t\t\t\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_OPPORTUNITIES') }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\tv-if=\"!(license.expirationLevel & ExpirationLevel.soonExpired)\" \n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-info\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_DAYS_LEFT_SHORT', {'#NUM_DAYS#': license.daysLeft}) }}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-info license-widget-item-info--exp\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">\n\t\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_TILL', {'#LICENSE_TILL#': license.expireDate}) }}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<!-- endregion -->\n\n\t\t\t<!-- region market -->\n\t\t\t<div\n\t\t\t\tv-if=\"market.isMarketAvailable\"\n\t\t\t\tclass=\"license-widget-item license-widget-item--wide license-widget-item-margin-y-2x\"\n\t\t\t\t:class=\"getMarketClass\"\n\t\t\t>\n\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tclass=\"license-widget-item-icon\"\n\t\t\t\t\t\t\t:class=\"getMarketIconClass\"\n\t\t\t\t\t\t></div>\n\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_MARKET') }}</span>\n\t\t\t\t\t\t\t\t<!--<span data-hint=\"Hint\"></span>-->\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t\t<span class=\"license-widget-item-link-text\" @click=\"showInfoHelper('market')\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_MARKET_DESCRIPTION') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-if=\"market.isExpired || market.isAlmostExpired\"\n\t\t\t\t\t\t\t\tkey=\"marketTill\"\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-info\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">\n\t\t\t\t\t\t\t\t\t{{ market.daysLeftMessage }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-else-if=\"market.isPaid || market.isDemo\"\n\t\t\t\t\t\t\t\tkey=\"marketTill\"\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-info\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"license-widget-item-info-text\">\n\t\t\t\t\t\t\t\t\t{{ market.tillMessage }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<a\n\t\t\t\t\t\tv-if=\"!market.isPaid && !market.isDemo && !market.isDemoUsed && this.isAdmin\"\n\t\t\t\t\t\tkey=\"marketButton\"\n\t\t\t\t\t\tclass=\"license-widget-item-btn\"\n\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t\t@click=\"showMarketDemoPopup($event)\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_TRY') }}\n\t\t\t\t\t</a>\n\n\t\t\t\t\t<a\n\t\t\t\t\t\tv-else-if=\"market.canBuy && this.isAdmin\"\n\t\t\t\t\t\tkey=\"marketButton\"\n\t\t\t\t\t\t:href=\"market.buyPath\"\n\t\t\t\t\t\tclass=\"license-widget-item-btn\"\n\t\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{\n\t\t\t\t\t\t\tmarket.isPaid\n\t\t\t\t\t\t\t? $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_PROLONG')\n\t\t\t\t\t\t\t: $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_BUY')\n\t\t\t\t\t\t}}\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<!-- endregion -->\n\n\t\t\t<!-- region lists -->\n\t\t\t<div class=\"license-widget-option-list\">\n\t\t\t\t<a\n\t\t\t\t\tclass=\"license-widget-option\"\n\t\t\t\t\t@click=\"showPartner\"\n\t\t\t\t>\n\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"19\" height=\"19\" fill=\"#525C69\" opacity=\".5\">\n\t\t\t\t\t\t<path fill-rule=\"evenodd\" d=\"M8.033 14.294a5.26 5.26 0 002.283.008l3.072 3.07a9.214 9.214 0 01-8.42-.013zm5.481-2.942l3.716 3.715a10.027 10.027 0 01-2.162 2.163l-3.716-3.716a4.824 4.824 0 001.256-.907c.377-.378.68-.802.906-1.255zm-8.637.015c.226.447.526.867.9 1.24.373.374.793.674 1.24.9L3.303 17.22a10.022 10.022 0 01-2.14-2.14l3.714-3.713zm-3.866-6.37l3.07 3.069a5.26 5.26 0 00.008 2.285l-3.064 3.064a9.214 9.214 0 01-.014-8.419zm16.348-.028a9.214 9.214 0 01.014 8.418l-3.07-3.07a5.26 5.26 0 00-.007-2.285zM3.316 1.154l3.716 3.715a4.826 4.826 0 00-1.256.907c-.378.378-.68.803-.906 1.256L1.154 3.316a10.032 10.032 0 012.162-2.162zm11.765.01a10.038 10.038 0 012.14 2.139l-3.715 3.714a4.826 4.826 0 00-.898-1.241 4.828 4.828 0 00-1.241-.899l3.714-3.714zm-1.666-.14l-3.062 3.063a5.26 5.26 0 00-2.288-.008L4.996 1.011a9.214 9.214 0 018.42.014z\"/>\n\t\t\t\t\t</svg>\n\t\t\t\t\t<div class=\"license-widget-option-text\">\n\t\t\t\t\t\t{{\n\t\t\t\t\t\t\tpartner.isPartnerOrder\n\t\t\t\t\t\t\t? $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_PARTNER_ORDER')\n\t\t\t\t\t\t\t: $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_PARTNER_CONNECT')\n\t\t\t\t\t\t}}\n\t\t\t\t\t</div>\n\t\t\t\t</a>\n\t\t\t\t<a class=\"license-widget-option\" @click=\"showHelper\">\n\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"19\" height=\"18\" fill=\"#525C69\" opacity=\".5\">\n\t\t\t\t\t\t<path fill-rule=\"evenodd\" d=\"M16.996 4.652a1.6 1.6 0 011.593 1.455l.007.145v7.268a1.6 1.6 0 01-1.455 1.594l-.145.006H15.11l-.001 2.096a.3.3 0 01-.477.243l-.05-.048-1.963-2.292-4.046.001a1.6 1.6 0 01-1.593-1.454l-.007-.146v-1.382l6.43.001a2.1 2.1 0 002.096-1.95l.005-.15V4.652h1.492zM12.346 0a1.6 1.6 0 011.6 1.6v7.268a1.6 1.6 0 01-1.6 1.6l-5.373-.001-2.974 2.977a.3.3 0 01-.512-.212l-.001-2.765H1.6a1.6 1.6 0 01-1.6-1.6V1.6A1.6 1.6 0 011.6 0h10.747z\"/>\n\t\t\t\t\t</svg>\n\t\t\t\t\t<div class=\"license-widget-option-text\">{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_SUPPORT') }}</div>\n\t\t\t\t</a>\n\t\t\t\t<a class=\"license-widget-option\" :href=\"license.ordersPath\" target=\"_blank\">\n\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" fill=\"#525C69\" opacity=\".5\">\n\t\t\t\t\t\t<path fill-rule=\"evenodd\" d=\"M12.566 6.992a5.122 5.122 0 110 10.244 5.122 5.122 0 010-10.244zM9.383 0c.409 0 .798.179 1.064.49l2.251 2.626c.218.254.338.578.338.912v.911a7.365 7.365 0 00-2.005.176v-.843L9.126 2.006H2.006v11.03h3.413c.09.705.283 1.379.562 2.005H1.402A1.402 1.402 0 010 13.64V1.402C0 .628.628 0 1.402 0h7.98zm5.353 9.991l-2.75 2.75-1.147-1.147-.811.811 1.914 1.914.044.043 3.56-3.56-.81-.81zM6.67 8.022a7.12 7.12 0 00-.85 1.583h-1.81V8.023h2.66zm2.354-3.008l-.001.884c-.36.205-.7.439-1.019.7H4.011V5.014h5.014z\"/>\n\t\t\t\t\t</svg>\n\t\t\t\t\t<div class=\"license-widget-option-text\">{{ $Bitrix.Loc.getMessage('INTRANET_LICENSE_WIDGET_ORDERS') }}</div>\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t\t<!-- endregion -->\n\t\t</div>\n\t"
	};

	var PopupWrapperComponent = {
	  components: {
	    LoaderComponent: LoaderComponent,
	    ContentComponent: ContentComponent
	  },
	  props: ["licenseType"],
	  data: function data() {
	    return {
	      loaded: false,
	      loading: true,
	      license: [],
	      market: [],
	      isAdmin: "",
	      isCloud: ""
	    };
	  },
	  mounted: function mounted() {
	    var _this = this;

	    main_core.ajax.runAction('intranet.license.getLicenseData', {}).then(function (_ref) {
	      var data = _ref.data;
	      _this.license = data.license;
	      var daysLeft = _this.license.daysLeft;
	      _this.license.expirationLevel = getExpirationLevel(daysLeft);
	      _this.market = data.market;
	      _this.telephony = data.telephony;
	      _this.isAdmin = data.isAdmin;
	      _this.partner = data.partner;
	      _this.loaded = true;
	      _this.loading = false;
	    });
	  },
	  methods: {},
	  template: "\n\t\t<div>\n\t\t\t<LoaderComponent v-if=\"loading\" :size=\"100\" />\n\t\t\t<ContentComponent \n\t\t\t\tv-if=\"!loading && loaded\" \n\t\t\t\t:license=\"license\"\n\t\t\t\t:market=\"market\"\n\t\t\t\t:telephony=\"telephony\"\n\t\t\t\t:isAdmin=\"isAdmin\"\n\t\t\t\t:partner=\"partner\">\n\t\t\t</ContentComponent>\n\t\t</div>\n\t"
	};

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var namespace = main_core.Reflection.namespace('BX.Intranet');

	var _vue = /*#__PURE__*/new WeakMap();

	var LicenseWidget = /*#__PURE__*/function () {
	  function LicenseWidget(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, LicenseWidget);

	    _classPrivateFieldInitSpec(this, _vue, {
	      writable: true,
	      value: void 0
	    });

	    this.isDemo = params.isDemo === "Y";
	    this.expirationLevel = params.expirationLevel;
	    this.node = params.wrapper;
	    this.renderButton();
	  }

	  babelHelpers.createClass(LicenseWidget, [{
	    key: "renderButton",
	    value: function renderButton() {
	      var _this2 = this;

	      babelHelpers.classPrivateFieldSet(this, _vue, ui_vue3.BitrixVue.createApp({
	        name: 'LicenseWidget:Button',
	        data: function data() {
	          return {
	            isDemo: _this2.isDemo,
	            expirationLevel: _this2.expirationLevel
	          };
	        },
	        computed: {
	          buttonClass: function buttonClass() {
	            var classNames = [];

	            if (_this2.expirationLevel <= ExpirationLevel.soonExpired) {
	              classNames.push(_this2.isDemo ? 'ui-btn-icon-demo' : 'ui-btn-icon-tariff');
	              classNames.push(_this2.expirationLevel & ExpirationLevel.soonExpired ? 'license-btn-orange' : 'license-btn-blue-border');
	            } else {
	              classNames.push('license-btn-alert-border');
	              classNames.push(_this2.expirationLevel & ExpirationLevel.expired ? 'license-btn-animate license-btn-animate-forward' : 'ui-btn-icon-low-battery');
	            }

	            return classNames.join(' ');
	          },
	          buttonName: function buttonName() {
	            if (this.expirationLevel > 1) {
	              return this.isDemo ? main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_BUY') : main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_PROLONG');
	            }

	            return this.isDemo ? main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE_DEMO') : main_core.Loc.getMessage('INTRANET_LICENSE_WIDGET_TITLE');
	          }
	        },
	        methods: {
	          togglePopup: function togglePopup(e) {
	            if (_this2.popup && _this2.popup.isShown()) {
	              return _this2.closePopup();
	            }

	            _this2.initPopup(e.target);
	          }
	        },
	        template: "\n\t\t\t\t<button\n\t\t\t\t\tdata-bx-id=\"liswdgt\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-round ui-btn-themes license-btn\"\n\t\t\t\t\t:class=\"buttonClass\"\n\t\t\t\t\t@click=\"togglePopup\"\n\t\t\t\t>\n\t\t\t\t\t<span v-if=\"expirationLevel > 1\" class=\"license-btn-icon-battery\">\n\t\t\t\t\t\t<span class=\"license-btn-icon-battery-full\">\n\t\t\t\t\t\t\t<span class=\"license-btn-icon-battery-inner\">\n\t\t\t\t\t\t\t\t<span></span>\n\t\t\t\t\t\t\t\t<span></span>\n\t\t\t\t\t\t\t\t<span></span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<svg class=\"license-btn-icon-battery-cross\" xmlns=\"http://www.w3.org/2000/svg\" width=\"22\" height=\"18\">\n\t\t\t\t\t\t\t<path fill=\"#FFF\" fill-rule=\"evenodd\" d=\"M18.567.395c.42.42.42 1.1 0 1.52l-1.04 1.038.704.001a2 2 0 012 2v1.799a1.01 1.01 0 01.116-.007H21a1 1 0 011 1v2.495a1 1 0 01-1 1h-.653l-.116-.006v1.798a2 2 0 01-2 2L5.45 15.032l-2.045 2.045a1.075 1.075 0 11-1.52-1.52L17.047.395c.42-.42 1.1-.42 1.52 0zm-2.583 4.102l-8.991 8.99 10.836.002a1 1 0 00.994-.883l.006-.117v-6.99a1 1 0 00-1-1l-1.845-.002zm-5.031-1.543L9.409 4.498h-6.23a1 1 0 00-.993.884l-.006.116-.001 6.23-1.4 1.398v-.046L.777 4.954a2 2 0 012-2h8.175z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t</span>\n\t\t\t\t\t{{ buttonName }}\n\t\t\t\t</button>\n\t\t\t"
	      }));
	      babelHelpers.classPrivateFieldGet(this, _vue).mount(this.node);
	    }
	  }, {
	    key: "initPopup",
	    value: function initPopup(bindElement) {
	      var _this3 = this;

	      if (!this.popup) {
	        this.popup = new B24.PopupBlur({
	          autoHide: true,
	          closeByEsc: true,
	          contentPadding: 0,
	          padding: 0,
	          minWidth: 350,
	          minHeight: 260,
	          animation: {
	            showClassName: "popup-with-radius-show",
	            closeClassName: "popup-with-radius-close",
	            closeAnimationType: "animation"
	          },
	          offsetLeft: -20,
	          className: 'popup-with-radius',
	          // contentBackground: 'rgba(0,0,0,0)',
	          angle: {
	            position: 'top',
	            offset: 120
	          },
	          bindElement: bindElement,
	          content: this.renderPopupContent(),
	          cachable: false,
	          events: {
	            onFirstShow: function onFirstShow(event) {
	              main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onMenuShow', function () {
	                if (_this3.popup) {
	                  _this3.popup.close();
	                }
	              });
	            }
	          }
	        });
	      }

	      this.popup.show();
	    }
	  }, {
	    key: "renderPopupContent",
	    value: function renderPopupContent() {
	      var _this4 = this;

	      var node = document.createElement('div');
	      ui_vue3.BitrixVue.createApp({
	        name: 'LicenseWidget:PopupWrapper',
	        components: {
	          PopupWrapperComponent: PopupWrapperComponent
	        },
	        data: function data() {
	          return {
	            isDemo: _this4.isDemo,
	            expirationLevel: _this4.expirationLevel
	          };
	        },
	        template: "<PopupWrapperComponent/>"
	      }).mount(node);
	      return node;
	    }
	  }, {
	    key: "closePopup",
	    value: function closePopup() {
	      if (this.popup) {
	        this.popup.close();
	      }
	    }
	  }]);
	  return LicenseWidget;
	}();

	namespace.LicenseWidget = LicenseWidget;

}((this.BX.Intranet.LicenseWidget = this.BX.Intranet.LicenseWidget || {}),BX.Event,BX.Vue3,BX,BX,BX));
//# sourceMappingURL=script.js.map

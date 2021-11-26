this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_loader,main_popup,main_core,ui_vue) {
	'use strict';

	var LoaderComponent = {
	  props: {
	    size: {
	      type: Number,
	      default: 85
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

	var RightsComponent = {
	  props: ["isCrurrentUserAdmin"],
	  computed: {
	    localize: function localize(state) {
	      return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	    }
	  },
	  methods: {
	    showPopup: function showPopup(e) {
	      var _this = this;

	      main_core.Event.EventEmitter.emit('BX.Intranet.InvitationWidget:stopPopupMouseOut');
	      this.getInvitationRightSetting().then(function (type) {
	        var menuItems = [{
	          text: _this.localize.INTRANET_INVITATION_WIDGET_SETTING_ALL_INVITE,
	          className: type === 'all' ? 'menu-popup-item-accept' : '',
	          onclick: function onclick() {
	            _this.saveInvitationRightSetting('all');

	            _this.popupMenu.close();
	          }
	        }, {
	          text: _this.localize.INTRANET_INVITATION_WIDGET_SETTING_ADMIN_INVITE,
	          className: type === 'admin' ? 'menu-popup-item-accept' : '',
	          onclick: function onclick() {
	            _this.saveInvitationRightSetting('admin');

	            _this.popupMenu.close();
	          }
	        }];
	        _this.popupMenu = new main_popup.Menu({
	          bindElement: e.target,
	          items: menuItems,
	          offsetLeft: 10,
	          offsetTop: 0,
	          angle: true,
	          className: 'license-right-popup-men',
	          events: {
	            onPopupShow: function onPopupShow() {
	              main_core.Event.EventEmitter.emit('BX.Intranet.InvitationWidget:showPopupMenu');
	            },
	            onPopupClose: function onPopupClose() {
	              main_core.Event.EventEmitter.emit('BX.Intranet.InvitationWidget:closePopupMenu');
	            }
	          }
	        });

	        _this.popupMenu.show();
	      });
	    },
	    saveInvitationRightSetting: function saveInvitationRightSetting(type) {
	      BX.ajax.runAction("intranet.invitationwidget.saveInvitationRight", {
	        data: {
	          type: type
	        }
	      });
	    },
	    getInvitationRightSetting: function getInvitationRightSetting() {
	      return new Promise(function (resolve, reject) {
	        BX.ajax.runAction("intranet.invitationwidget.getInvitationRight", {
	          data: {}
	        }).then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"license-widget-item-menu\" @click=\"showPopup\"></div>\n\t"
	};

	var UserOnlineComponent = {
	  components: {
	    LoaderComponent: LoaderComponent
	  },
	  props: ["isCrurrentUserAdmin"],
	  computed: {
	    localize: function localize(state) {
	      return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	    }
	  },
	  methods: {
	    getComponentContent: function getComponentContent() {
	      var _this = this;

	      BX.ajax.runAction("intranet.invitationwidget.getUserOnlineComponent", {
	        data: {}
	      }).then(function (response) {
	        _this.showComponentData(response);
	      }, function (response) {});
	    },
	    showComponentData: function showComponentData(result) {
	      new Promise(function (resolve, reject) {
	        if (result.data.hasOwnProperty("assets") && result.data.assets['css'].length) {
	          BX.load(result.data.assets['css'], function () {
	            if (result.data.assets['js'].length) {
	              BX.load(result.data.assets['js'], function () {
	                if (result.data.assets['string'].length) {
	                  for (var i = 0; i < result.data.assets['string'].length; i++) {
	                    BX.html(null, result.data.assets['string'][i]);
	                  }
	                }

	                resolve();
	              });
	            }
	          });
	        }
	      }).then(function () {
	        var container = document.querySelector("[data-role='invitation-widget-ustat-online']");
	        var html = BX.prop.getString(result.data, "html", '');
	        BX.html(container, html);
	      });
	    }
	  },
	  template: "\n\t\t<div data-role=\"invitation-widget-ustat-online\" class=\"invitation-widget-ustat-online\">\n\t\t\t<LoaderComponent\n\t\t\t\t:size=\"40\"\n\t\t\t></LoaderComponent>\n\t\t\t{{getComponentContent()}}\n\t\t</div>\n\t"
	};

	var ContentComponent = {
	  components: {
	    RightsComponent: RightsComponent,
	    UserOnlineComponent: UserOnlineComponent
	  },
	  props: ["isCrurrentUserAdmin", "invitationLink", "structureLink", "users", "isInvitationAvailable", "isExtranetAvailable"],
	  computed: {
	    localize: function localize(state) {
	      return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	    }
	  },
	  methods: {
	    showInvitationSlider: function showInvitationSlider(e, type) {
	      if (this.isInvitationAvailable) {
	        var link = this.invitationLink;

	        if (type === 'extranet') {
	          link = "".concat(link, "&firstInvitationBlock=extranet");
	        }

	        BX.SidePanel.Instance.open(link, {
	          cacheable: false,
	          allowChangeHistory: false,
	          width: 1100
	        });
	        main_core.Event.EventEmitter.emit('BX.Intranet.InvitationWidget:showInvitationSlider');
	      } else {
	        this.showHintPopup(this.localize.INTRANET_INVITATION_WIDGET_DISABLED_TEXT, e.target);
	      }
	    },
	    showHintPopup: function showHintPopup(message, bindNode) {
	      if (!main_core.Type.isDomNode(bindNode) || !message) {
	        return;
	      }

	      new BX.PopupWindow('inviteHint' + main_core.Text.getRandom(8), bindNode, {
	        content: message,
	        zIndex: 15000,
	        angle: true,
	        offsetTop: 0,
	        offsetLeft: 50,
	        closeIcon: false,
	        autoHide: true,
	        darkMode: true,
	        overlay: false,
	        maxWidth: 400,
	        events: {
	          onAfterPopupShow: function onAfterPopupShow() {
	            setTimeout(function () {
	              this.close();
	            }.bind(this), 4000);
	          }
	        }
	      }).show();
	    },
	    sendAnalytics: function sendAnalytics(code) {
	      BX.ajax.runAction("intranet.invitationwidget.analyticsLabel", {
	        data: {},
	        analyticsLabel: {
	          helperCode: code,
	          headerPopup: "Y"
	        }
	      }).then(function (response) {}, function (response) {});
	    },
	    showInvitationHelper: function showInvitationHelper() {
	      var code = "limit_why_team_invites";
	      BX.UI.InfoHelper.show(code);
	      this.sendAnalytics(code);
	    },
	    showExtranetHelper: function showExtranetHelper() {
	      var article = "6770709";
	      BX.Helper.show("redirect=detail&code=".concat(article));
	      this.sendAnalytics(article);
	    }
	  },
	  template: "\n\t\t<div class=\"license-widget license-widget--invite\">\n\t\t\t<div class=\"license-widget-invite license-widget-item-margin-bottom-1x\">\n\t\t\t\t<div class=\"license-widget-invite-main\">\n\t\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-icon license-widget-item-icon--invite\"></div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE }}</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t\t\t<span class=\"license-widget-item-link-text\" @click=\"showInvitationHelper\">\n\t\t\t\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_DESC }}\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<a \n\t\t\t\t\t\t\tdata-role=\"invitationPopupButton\"\n\t\t\t\t\t\t\tclass=\"license-widget-item-btn license-widget-item-btn--invite\"\n\t\t\t\t\t\t\t@click=\"showInvitationSlider\" \n\t\t\t\t\t\t> \n\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_INVITE }} \n\t\t\t\t\t\t</a>\t\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<div class=\"license-widget-block license-widget-item-margin-bottom-2x\">\n\t\t\t\t<div class=\"license-widget-item license-widget-item--company license-widget-item--active\">\n\t\t\t\t\t<div class=\"license-widget-item-logo\"></div>\n\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_STRUCTURE }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<a :href=\"structureLink\" class=\"license-widget-item-btn\"> \n\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EDIT }} \n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\n\t\t\t\t<div \n\t\t\t\t\tclass=\"license-widget-item license-widget-item--emp\"\n\t\t\t\t\t:class=\"{ 'license-widget-item--emp-alert' : users.isLimit }\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-progress\"\n\t\t\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\t\t\tusers.isLimit \n\t\t\t\t\t\t\t\t\t\t? 'license-widget-item-progress--crit' \n\t\t\t\t\t\t\t\t\t\t: 'license-widget-item-progress--full'\n\t\t\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t\t\t<div class=\"license-widget-employees\">\n\t\t\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES }}</span>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"license-widget-item-num\">\n\t\t\t\t\t\t\t\t\t\t{{ users.currentUserCountMessage }}\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\t\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<!--<div class=\"license-widget-item-menu\"></div>-->\n\t\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"license-widget-item-detail\">\n\t\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\t\tv-if=\"users.maxUserCount == 0\" \n\t\t\t\t\t\t\t\t\tkey=\"employeeCount\"\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\t\tv-else-if=\"users.isLimit\"\n\t\t\t\t\t\t\t\t\tkey=\"employeeCount\" \n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\t\tv-else-if=\"!users.isLimit\" \n\t\t\t\t\t\t\t\t\tkey=\"employeeCount\"\n\t\t\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ users.leftCountMessage }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<RightsComponent\n\t\t\t\t\t\t\t\tv-if=\"isCrurrentUserAdmin\"\n\t\t\t\t\t\t\t></RightsComponent>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<div \n\t\t\t\tv-if=\"isExtranetAvailable\"\n\t\t\t\tkey=\"extranetBlock\"\n\t\t\t\tclass=\"license-widget-item license-widget-item--wide\"\n\t\t\t\t:class=\"{ 'license-widget-item--active' : users.currentExtranetUserCount > 0 }\"\n\t\t\t>\n\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t<div class=\"license-widget-content\">\n\t\t\t\t\t\t<div class=\"license-widget-item-icon license-widget-item-icon--ext\"></div>\n\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_EXTRANET }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t\t<a class=\"license-widget-item-link-text\" @click=\"showExtranetHelper\">\n\t\t\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EXTRANET_DESC }}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\tv-if=\"users.currentExtranetUserCount > 0\" \n\t\t\t\t\t\t\t\tkey=\"extranetEmployeeCount\"\n\t\t\t\t\t\t\t\tclass=\"license-widget-item-ext-users\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ users.currentExtranetUserCountMessage }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button \n\t\t\t\t\t\tclass=\"license-widget-item-btn\" \t\n\t\t\t\t\t\ttype=\"button\" \n\t\t\t\t\t\t@click=\"showInvitationSlider($event, 'extranet')\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_INVITE }}\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"license-widget-item license-widget-item--wide license-widget-item--no-padding\">\n\t\t\t\t<UserOnlineComponent></UserOnlineComponent>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var PopupWrapperComponent = {
	  components: {
	    LoaderComponent: LoaderComponent,
	    ContentComponent: ContentComponent
	  },
	  props: ["isCrurrentUserAdmin"],
	  data: function data() {
	    return {
	      loaded: false,
	      loading: true,
	      invitationLink: "",
	      structureLink: "",
	      isInvitationAvailable: true,
	      users: []
	    };
	  },
	  mounted: function mounted() {
	    this.getData();
	  },
	  methods: {
	    getData: function getData() {
	      var _this = this;
	      BX.ajax.runAction("intranet.invitationwidget.getData", {
	        data: {},
	        analyticsLabel: {
	          headerPopup: "Y"
	        }
	      }).then(function (response) {
	        _this.invitationLink = response.data.invitationLink;
	        _this.structureLink = response.data.structureLink;
	        _this.isInvitationAvailable = response.data.isInvitationAvailable;
	        _this.isExtranetAvailable = response.data.isExtranetAvailable;
	        _this.users = response.data.users;
	        _this.loaded = true;
	        _this.loading = false;
	      }, function (response) {});
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<LoaderComponent v-if=\"loading\" :size=\"100\" />\n\t\t\t<ContentComponent \n\t\t\t\tv-if=\"!loading && loaded\"\n\t\t\t\t:invitationLink=\"invitationLink\"\n\t\t\t\t:structureLink=\"structureLink\"\n\t\t\t\t:isInvitationAvailable=\"isInvitationAvailable\"\n\t\t\t\t:isExtranetAvailable=\"isExtranetAvailable\"\n\t\t\t\t:users=\"users\"\n\t\t\t\t:isCrurrentUserAdmin=\"isCrurrentUserAdmin\"\n\t\t\t>\n\t\t\t</ContentComponent>\n\t\t</div>\n\t"
	};

	var namespace = main_core.Reflection.namespace('BX.Intranet');

	var _vue = /*#__PURE__*/new WeakMap();

	var InvitationWidget = /*#__PURE__*/function () {
	  function InvitationWidget(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, InvitationWidget);

	    _vue.set(this, {
	      writable: true,
	      value: void 0
	    });

	    this.node = params.wrapper;
	    this.isCrurrentUserAdmin = params.isCrurrentUserAdmin === "Y";
	    this.enterTimeout = null;
	    this.leaveTimeout = null;
	    this.popupLeaveTimeout = null;
	    this.stopMouseLeave = false;
	    this.renderButton();
	    main_core.Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:showInvitationSlider', function (event) {
	      _this.closePopup();
	    });
	    main_core.Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:stopPopupMouseOut', function (event) {
	      _this.stopMouseLeave = true;
	    });
	    main_core.Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:showPopupMenu', function () {
	      _this.popup.setAutoHide(false);
	    });
	    main_core.Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:closePopupMenu', function () {
	      _this.popup.setAutoHide(true);

	      _this.stopMouseLeave = false;
	    });
	  }

	  babelHelpers.createClass(InvitationWidget, [{
	    key: "renderButton",
	    value: function renderButton() {
	      var InvitationWidgetInstance = this;
	      babelHelpers.classPrivateFieldSet(this, _vue, ui_vue.Vue.create({
	        el: this.node,
	        data: function data() {
	          return {};
	        },
	        computed: {
	          localize: function localize(state) {
	            return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	          }
	        },
	        methods: {
	          onMouseOver: function onMouseOver(e) {
	            clearTimeout(InvitationWidgetInstance.enterTimeout);
	            InvitationWidgetInstance.enterTimeout = setTimeout(function () {
	              InvitationWidgetInstance.enterTimeout = null;
	              InvitationWidgetInstance.initPopup(e.target);
	            }, 750);
	          },
	          onMouseOut: function onMouseOut() {
	            if (InvitationWidgetInstance.enterTimeout !== null) {
	              clearTimeout(InvitationWidgetInstance.enterTimeout);
	              InvitationWidgetInstance.enterTimeout = null;
	              return;
	            }

	            InvitationWidgetInstance.leaveTimeout = setTimeout(function () {
	              if (!InvitationWidgetInstance.stopMouseLeave) {
	                InvitationWidgetInstance.closePopup();
	              }
	            }, 500);
	          },
	          togglePopup: function togglePopup(e) {
	            if (InvitationWidgetInstance.popup) {
	              if (InvitationWidgetInstance.popup.isShown()) {
	                InvitationWidgetInstance.closePopup();
	              } else {
	                InvitationWidgetInstance.initPopup(e.target);
	              }
	            }
	          }
	        },
	        template: "\n\t\t\t\t<button \n\t\t\t\t\tclass=\"ui-btn ui-btn-round license-btn license-btn-primary\" \n\t\t\t\t\t@mouseover=\"onMouseOver\"\n\t\t\t\t\t@mouseout=\"onMouseOut\"\n\t\t\t\t\t@click=\"togglePopup\"\n\t\t\t\t>{{ localize.INTRANET_INVITATION_WIDGET_INVITE }}</button>\n\t\t\t"
	      }));
	    }
	  }, {
	    key: "initPopup",
	    value: function initPopup(bindElement) {
	      var _this2 = this;

	      if (this.popup) {
	        this.popup.destroy();
	      }

	      this.popup = new B24.PopupBlur({
	        autoHide: true,
	        closeByEsc: true,
	        contentPadding: 0,
	        padding: 0,
	        minWidth: 350,
	        minHeight: 220,
	        offsetLeft: -150,
	        animation: {
	          showClassName: "popup-with-radius-show",
	          closeClassName: "popup-with-radius-close",
	          closeAnimationType: "animation"
	        },
	        className: 'popup-with-radius',
	        // contentBackground: 'rgba(0,0,0,0)',
	        angle: {
	          position: 'top',
	          offset: 235
	        },
	        bindElement: bindElement,
	        content: this.renderPopupContent(),
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this2.popup.destroy();
	          }
	        }
	      });
	      this.initEvents();
	      this.popup.show();
	    }
	  }, {
	    key: "initEvents",
	    value: function initEvents() {
	      var _this3 = this;

	      this.popup.getPopupContainer().addEventListener('mouseenter', function () {
	        clearTimeout(_this3.enterTimeout);
	        clearTimeout(_this3.leaveTimeout);
	        clearTimeout(_this3.popupLeaveTimeout);
	      });
	      this.popup.getPopupContainer().addEventListener('mouseleave', function (event) {
	        _this3.popupLeaveTimeout = setTimeout(function () {
	          if (!_this3.stopMouseLeave) {
	            _this3.closePopup();
	          }
	        }, 500);
	      });
	    }
	  }, {
	    key: "renderPopupContent",
	    value: function renderPopupContent() {
	      var InvitationWidgetInstance = this;
	      var content = ui_vue.Vue.create({
	        el: document.createElement('div'),
	        components: {
	          PopupWrapperComponent: PopupWrapperComponent
	        },
	        data: function data() {
	          return {
	            isCrurrentUserAdmin: InvitationWidgetInstance.isCrurrentUserAdmin
	          };
	        },
	        computed: {
	          localize: function localize(state) {
	            return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	          }
	        },
	        template: "\n\t\t\t\t<PopupWrapperComponent\n\t\t\t\t\t:isCrurrentUserAdmin=\"isCrurrentUserAdmin\"\n\t\t\t\t/>"
	      });
	      return content.$el;
	    }
	  }, {
	    key: "closePopup",
	    value: function closePopup() {
	      if (this.popup) {
	        this.popup.close();
	      }
	    }
	  }]);
	  return InvitationWidget;
	}();

	namespace.InvitationWidget = InvitationWidget;

}((this.BX.Intranet.LicenseWidget = this.BX.Intranet.LicenseWidget || {}),BX,BX.Main,BX,BX));
//# sourceMappingURL=script.js.map

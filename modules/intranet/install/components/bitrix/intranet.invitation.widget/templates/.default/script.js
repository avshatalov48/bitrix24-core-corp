this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_popup,main_loader,ui_vue,main_core) {
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

	var ContentComponent = {
	  props: ["invitationLink", "structureLink", "users", "isInvitationAvailable"],
	  computed: {
	    localize: function localize(state) {
	      return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	    }
	  },
	  methods: {
	    showInvitationSlider: function showInvitationSlider() {
	      if (this.isInvitationAvailable) {
	        BX.SidePanel.Instance.open(this.invitationLink, {
	          cacheable: false,
	          allowChangeHistory: false,
	          width: 1100
	        });
	        main_core.Event.EventEmitter.emit('BX.Intranet.InvitationWidget:showInvitationSlider');
	      } else {
	        this.showHintPopup(BX.message("INTRANET_INVITATION_WIDGET_DISABLED_TEXT"), document.querySelector("[data-role='invitationPopupButton']"));
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
	    }
	  },
	  template: "\n\t\t<div class=\"license-widget license-widget--invite\">\n\t\t\t<div class=\"license-widget-item license-widget-item--main\">\n\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t<div class=\"license-widget-item-icon license-widget-item-icon--invite\"></div>\n\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"license-widget-item-link\">\n\t\t\t\t\t\t\t<span class=\"license-widget-item-link-text\" @click=\"showInvitationHelper\">\n\t\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_DESC }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<a \n\t\t\t\t\tdata-role=\"invitationPopupButton\"\n\t\t\t\t\tclass=\"license-widget-item-btn license-widget-item-btn--invite\"\n\t\t\t\t\t@click=\"showInvitationSlider\" \n\t\t\t\t> \n\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_INVITE }} \n\t\t\t\t</a>\t\n\t\t\t</div>\n\t\t\t\n\t\t\t<div class=\"license-widget-block\">\n\t\t\t\t<div class=\"license-widget-item license-widget-item--company license-widget-item--active\">\n\t\t\t\t\t<div class=\"license-widget-item-logo\"></div>\n\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_STRUCTURE }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<a :href=\"structureLink\" class=\"license-widget-item-btn\"> \n\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EDIT }} \n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\n\t\t\t\t<div \n\t\t\t\t\tclass=\"license-widget-item license-widget-item--emp\"\n\t\t\t\t\t:class=\"{ 'license-widget-item--emp-alert' : users.isLimit }\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"license-widget-inner\">\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tclass=\"license-widget-item-progress\"\n\t\t\t\t\t\t\t:class=\"[\n\t\t\t\t\t\t\t\tusers.isLimit \n\t\t\t\t\t\t\t\t? 'license-widget-item-progress--crit' \n\t\t\t\t\t\t\t\t: 'license-widget-item-progress--full'\n\t\t\t\t\t\t\t]\"\n\t\t\t\t\t\t></div>\n\t\t\t\t\t\t<div class=\"license-widget-item-content\">\n\t\t\t\t\t\t\t<div class=\"license-widget-item-name\">\n\t\t\t\t\t\t\t\t<span>{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"license-widget-item-num\">\n\t\t\t\t\t\t\t\t{{ users.currentUserCountMessage }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<!--<div class=\"license-widget-item-menu\"></div>-->\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"license-widget-item-detail\">\n\t\t\t\t\t\t<span \n\t\t\t\t\t\t\tv-if=\"users.maxUserCount == 0\" \n\t\t\t\t\t\t\tkey=\"employeeCount\"\n\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span \n\t\t\t\t\t\t\tv-else-if=\"users.isLimit\"\n\t\t\t\t\t\t\tkey=\"employeeCount\" \n\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span \n\t\t\t\t\t\t\tv-else-if=\"!users.isLimit\" \n\t\t\t\t\t\t\tkey=\"employeeCount\"\n\t\t\t\t\t\t\tclass=\"license-widget-item-link-text\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ users.leftCountMessage }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var PopupWrapperComponent = {
	  components: {
	    LoaderComponent: LoaderComponent,
	    ContentComponent: ContentComponent
	  },
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
	        _this.users = response.data.users;
	        _this.loaded = true;
	        _this.loading = false;
	      }, function (response) {});
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<LoaderComponent v-if=\"loading\" :size=\"100\" />\n\t\t\t<ContentComponent \n\t\t\t\tv-if=\"!loading && loaded\"\n\t\t\t\t:invitationLink=\"invitationLink\"\n\t\t\t\t:structureLink=\"structureLink\"\n\t\t\t\t:isInvitationAvailable=\"isInvitationAvailable\"\n\t\t\t\t:users=\"users\"\n\t\t\t>\n\t\t\t</ContentComponent>\n\t\t</div>\n\t"
	};

	var namespace = main_core.Reflection.namespace('BX.Intranet');

	var _vue = new WeakMap();

	var InvitationWidget = /*#__PURE__*/function () {
	  function InvitationWidget(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, InvitationWidget);

	    _vue.set(this, {
	      writable: true,
	      value: void 0
	    });

	    this.node = params.wrapper;
	    this.enterTimeout = null;
	    this.leaveTimeout = null;
	    this.popupLeaveTimeout = null;
	    this.renderButton();
	    main_core.Event.EventEmitter.subscribe('BX.Intranet.InvitationWidget:showInvitationSlider', function (event) {
	      _this.closePopup();
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
	            }, 500);
	          },
	          onMouseOut: function onMouseOut() {
	            if (InvitationWidgetInstance.enterTimeout !== null) {
	              clearTimeout(InvitationWidgetInstance.enterTimeout);
	              InvitationWidgetInstance.enterTimeout = null;
	              return;
	            }

	            InvitationWidgetInstance.leaveTimeout = setTimeout(function () {
	              InvitationWidgetInstance.closePopup();
	            }, 500);
	          },
	          togglePopup: function togglePopup() {
	            if (InvitationWidgetInstance.popup) {
	              if (InvitationWidgetInstance.popup.isShown()) {
	                InvitationWidgetInstance.closePopup();
	              } else {
	                InvitationWidgetInstance.popup.show();
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
	      if (!this.popup) {
	        this.popup = new main_popup.Popup({
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
	          contentBackground: 'rgba(0,0,0,0)',
	          angle: {
	            position: 'top',
	            offset: 235
	          },
	          bindElement: bindElement,
	          content: this.renderPopupContent()
	        });
	        this.initEvents();
	      }

	      this.popup.show();
	    }
	  }, {
	    key: "initEvents",
	    value: function initEvents() {
	      var _this2 = this;

	      this.popup.getPopupContainer().addEventListener('mouseenter', function () {
	        clearTimeout(_this2.enterTimeout);
	        clearTimeout(_this2.leaveTimeout);
	        clearTimeout(_this2.popupLeaveTimeout);
	      });
	      this.popup.getPopupContainer().addEventListener('mouseleave', function (event) {
	        _this2.popupLeaveTimeout = setTimeout(function () {
	          _this2.closePopup();
	        }, 500);
	      });
	    }
	  }, {
	    key: "renderPopupContent",
	    value: function renderPopupContent() {
	      var content = ui_vue.Vue.create({
	        el: document.createElement('div'),
	        components: {
	          PopupWrapperComponent: PopupWrapperComponent
	        },
	        computed: {
	          localize: function localize(state) {
	            return ui_vue.Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
	          }
	        },
	        template: "<PopupWrapperComponent/>"
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

}((this.BX.Intranet.LicenseWidget = this.BX.Intranet.LicenseWidget || {}),BX.Main,BX,BX,BX));
//# sourceMappingURL=script.js.map

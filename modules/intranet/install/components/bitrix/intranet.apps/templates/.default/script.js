this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_buttons) {
	'use strict';

	var AppsWidget = /*#__PURE__*/function () {
	  function AppsWidget(params) {
	    babelHelpers.classCallCheck(this, AppsWidget);
	    this.popup = null;
	    this.personalMobile = main_core.Type.isStringFilled(params.personalMobile) ? params.personalMobile : '';
	    this.initAppsInstall();
	  }

	  babelHelpers.createClass(AppsWidget, [{
	    key: "initAppsInstall",
	    value: function initAppsInstall() {
	      var _this = this;

	      var androidIcon = document.querySelector("[data-role='profile-android-app']");

	      if (main_core.Type.isDomNode(androidIcon)) {
	        androidIcon.addEventListener('click', function () {
	          _this.showSmsPopup(_this.personalMobile);
	        });
	      }

	      var iosIcon = document.querySelector("[data-role='profile-ios-app']");

	      if (main_core.Type.isDomNode(iosIcon)) {
	        iosIcon.addEventListener('click', function () {
	          _this.showSmsPopup(_this.personalMobile);
	        });
	      }
	    }
	  }, {
	    key: "showSmsPopup",
	    value: function showSmsPopup(personalMobile) {
	      this.popup = main_popup.PopupManager.create({
	        id: 'intranet-apps-widget-sms-popup',
	        className: 'intranet-apps-widget-popup',
	        titleBar: main_core.Loc.getMessage('INTRANET_APPS_WIDGET_INSTALL'),
	        cacheable: false,
	        maxWidth: 450,
	        contentColor: 'white',
	        content: main_core.Dom.create('div', {
	          children: [main_core.Dom.create('div', {
	            props: {
	              className: 'intranet-apps-widget-popup-title'
	            },
	            html: main_core.Loc.getMessage('INTRANET_APPS_WIDGET_PHONE')
	          }), main_core.Dom.create('div', {
	            props: {
	              className: 'ui-ctl ui-ctl-textbox ui-ctl-wa'
	            },
	            children: [main_core.Dom.create('input', {
	              props: {
	                value: personalMobile,
	                className: 'ui-ctl-element',
	                type: 'text'
	              },
	              events: {
	                input: function input(event) {
	                  personalMobile = event.target.value;
	                }
	              }
	            })]
	          }), main_core.Dom.create('div', {
	            props: {
	              className: 'intranet-apps-widget-popup-text'
	            },
	            html: main_core.Loc.getMessage('INTRANET_APPS_WIDGET_INSTALL_TEXT')
	          })]
	        }),
	        closeIcon: true,
	        contentPadding: 10,
	        buttons: [new ui_buttons.CreateButton({
	          text: main_core.Loc.getMessage('INTRANET_APPS_WIDGET_SEND'),
	          className: 'ui-btn-primary',
	          events: {
	            click: function click(button) {
	              button.setWaiting();
	              var popup = button.context;
	              main_core.ajax.runAction('intranet.controller.sms.sendsmsforapp', {
	                data: {
	                  phone: personalMobile
	                }
	              }).then(function () {
	                popup.close();
	              }, function () {
	                popup.close();
	              });
	            }
	          }
	        })]
	      });
	      this.popup.show();
	    }
	  }]);
	  return AppsWidget;
	}();

	exports.AppsWidget = AppsWidget;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Main,BX.UI));
//# sourceMappingURL=script.js.map

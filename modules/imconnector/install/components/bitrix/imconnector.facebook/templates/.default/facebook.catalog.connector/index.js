this.BX = this.BX || {};
(function (exports,main_core,main_popup,main_core_events) {
	'use strict';

	var _templateObject;

	var FacebookCatalogConnector = /*#__PURE__*/function () {
	  function FacebookCatalogConnector() {
	    babelHelpers.classCallCheck(this, FacebookCatalogConnector);
	    main_core_events.EventEmitter.subscribe('seo-client-auth-result', function (event) {
	      if (event.reload) {
	        BX.Dom.addClass(document.getElementById('catalog-login-button'), 'ui-btn-wait');
	      }
	    });
	    main_core.Event.bind(document.getElementById('catalog-logout-button'), 'click', this.confirmLogout.bind(this));
	  }

	  babelHelpers.createClass(FacebookCatalogConnector, [{
	    key: "confirmLogout",
	    value: function confirmLogout() {
	      var _this = this;

	      var confirmPopup = new main_popup.Popup({
	        content: this.getConfirmPopupHtml(),
	        autoHide: true,
	        cacheable: false,
	        closeIcon: true,
	        closeByEsc: true,
	        overlay: {
	          opacity: 20
	        },
	        buttons: [new BX.UI.Button({
	          text: main_core.Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DISCONNECT'),
	          color: BX.UI.Button.Color.DANGER,
	          onclick: function onclick() {
	            confirmPopup.close();

	            _this.logout();
	          }
	        }), new BX.UI.Button({
	          text: main_core.Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CANCEL'),
	          color: BX.UI.Button.Color.LINK,
	          onclick: function onclick() {
	            confirmPopup.close();
	          }
	        })]
	      });
	      confirmPopup.show();
	    }
	  }, {
	    key: "getConfirmPopupHtml",
	    value: function getConfirmPopupHtml() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"imconnector-facebook-catalog-popup\">\n\t\t\t<div class=\"imconnector-facebook-catalog-popup-text\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>"])), main_core.Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_REMOVE'));
	    }
	  }, {
	    key: "logout",
	    value: function logout() {
	      BX.ajax.runComponentAction('bitrix:imconnector.facebook', 'logout', {
	        'mode': 'ajax'
	      }).then(function () {
	        document.location.href = new main_core.Uri(document.location.href).setQueryParams({
	          MENU_TAB: 'catalog'
	        });
	      });
	    }
	  }]);
	  return FacebookCatalogConnector;
	}();

	main_core.Reflection.namespace('BX.ImConnector').FacebookCatalogConnector = FacebookCatalogConnector;

}((this.BX.ImConnector = this.BX.ImConnector || {}),BX,BX.Main,BX.Event));
//# sourceMappingURL=index.js.map

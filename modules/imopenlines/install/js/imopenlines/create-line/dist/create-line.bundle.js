this.BX = this.BX || {};
(function (exports,main_core,main_popup) {
	'use strict';

	var _templateObject;
	var CreateLine = /*#__PURE__*/function () {
	  function CreateLine(options) {
	    babelHelpers.classCallCheck(this, CreateLine);
	    babelHelpers.defineProperty(this, "isLocked", false);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    this.path = options.path;
	    this.sliderWidth = options.sliderWidth;
	    if (this.path) {
	      this.init();
	    }
	  }
	  babelHelpers.createClass(CreateLine, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      if (this.isLocked) {
	        return;
	      }
	      this.isLocked = true;
	      main_core.ajax({
	        url: '/bitrix/components/bitrix/imopenlines.lines/ajax.php',
	        method: 'POST',
	        data: {
	          'action': 'create',
	          'sessid': BX.bitrix_sessid()
	        },
	        timeout: 30,
	        dataType: 'json',
	        processData: true,
	        onsuccess: function onsuccess(data) {
	          data = data || {};
	          if (data.error) {
	            _this.onFail(data);
	          } else {
	            _this.onSuccess(data);
	          }
	        },
	        onfailure: function onfailure(data) {
	          return _this.onFail(data);
	        }
	      });
	    }
	  }, {
	    key: "onSuccess",
	    value: function onSuccess(data) {
	      BX.SidePanel.Instance.open(this.path.replace('#LINE#', data.config_id), {
	        width: this.sliderWidth,
	        cacheable: false
	      });
	    }
	  }, {
	    key: "onFail",
	    value: function onFail(responseData) {
	      responseData = responseData || {
	        'error': true,
	        'text': ''
	      };
	      this.isLocked = false;
	      if (responseData.limited)
	        //see \Bitrix\ImOpenLines\Config::canActivateLine()
	        {
	          if (!B24 || !B24['licenseInfoPopup']) {
	            return;
	          }
	          BX.UI.InfoHelper.show('limit_contact_center_ol_number');
	        } else {
	        responseData = responseData || {};
	        var errorMessage = responseData.text || main_core.Loc.getMessage('IMOPENLINES_CREATE_LINE_ERROR_ACTION');
	        this.showErrorPopup(errorMessage);
	      }
	    }
	  }, {
	    key: "showErrorPopup",
	    value: function showErrorPopup(errorMessage) {
	      var popup = main_popup.PopupManager.create({
	        id: 'crm_webform_list_error',
	        content: this.getPopupContent(errorMessage),
	        buttons: [new BX.UI.Button({
	          text: main_core.Loc.getMessage('IMOPENLINES_CREATE_LINE_CLOSE_BUTTON'),
	          onclick: function onclick() {
	            return popup.close();
	          }
	        })],
	        autoHide: true,
	        lightShadow: true,
	        closeByEsc: true,
	        overlay: {
	          backgroundColor: 'black',
	          opacity: 500
	        }
	      });
	      popup.show();
	    }
	  }, {
	    key: "getPopupContent",
	    value: function getPopupContent(message) {
	      return this.cache.remember('popupContent', function () {
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"crm-webform-edit-warning-popup-alert\">", "</span>\n\t\t\t"])), message);
	      });
	    }
	  }]);
	  return CreateLine;
	}();

	exports.CreateLine = CreateLine;

}((this.BX.Imopenlines = this.BX.Imopenlines || {}),BX,BX.Main));
//# sourceMappingURL=create-line.bundle.js.map

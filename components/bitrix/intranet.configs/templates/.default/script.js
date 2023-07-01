this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core) {
	'use strict';

	var Logo = /*#__PURE__*/function () {
	  function Logo(parent) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Logo);
	    this.ajaxPath = parent.ajaxPath;

	    if (BX("configLogoPostForm") && BX("configLogoPostForm").client_logo) {
	      BX.bind(BX("configLogoPostForm").client_logo, "change", function () {
	        _this.LogoChange();
	      });
	    }

	    if (BX("configDeleteLogo")) {
	      BX.bind(BX("configDeleteLogo"), "click", function () {
	        _this.LogoDelete(BX("configDeleteLogo"));
	      });
	    }

	    if (BX("configLogoRetinaPostForm") && BX("configLogoRetinaPostForm").client_logo_retina) {
	      BX.bind(BX("configLogoRetinaPostForm").client_logo_retina, "change", function () {
	        _this.LogoChange("retina");
	      });
	    }

	    if (BX("configDeleteLogoretina")) {
	      BX.bind(BX("configDeleteLogoretina"), "click", function () {
	        _this.LogoDelete(BX("configDeleteLogoretina"), "retina");
	      });
	    }
	  }

	  babelHelpers.createClass(Logo, [{
	    key: "LogoChange",
	    value: function LogoChange(mode) {
	      mode = mode == "retina" ? "retina" : "";
	      BX('configWaitLogo' + mode).style.display = 'inline-block';
	      BX.ajax.submit(BX(mode == "retina" ? 'configLogoRetinaPostForm' : 'configLogoPostForm'), function (reply) {
	        try {
	          var json = JSON.parse(reply);

	          if (json.error) {
	            BX('config_logo_error_block').style.display = 'block';
	            var error_block = BX.findChild(BX('config_logo_error_block'), {
	              class: 'content-edit-form-notice-text'
	            }, true, false);
	            error_block.innerHTML = '<span class=\'content-edit-form-notice-icon\'></span>' + json.error;
	          } else if (json.path) {
	            BX('config_logo_error_block').style.display = 'none';
	            BX('configImgLogo' + mode).src = json.path;
	            BX('configBlockLogo' + mode).style.display = 'inline-block';
	            BX('configDeleteLogo' + mode).style.display = 'inline-block';
	          }

	          BX('configWaitLogo' + mode).style.display = 'none';
	        } catch (e) {
	          BX('configWaitLogo' + mode).style.display = 'none';
	          return false;
	        }
	      });
	    }
	  }, {
	    key: "LogoDelete",
	    value: function LogoDelete(curLink, mode) {
	      mode = mode == "retina" ? "retina" : "";

	      if (confirm(BX.message("LogoDeleteConfirm"))) {
	        BX('configWaitLogo' + mode).style.display = 'inline-block';
	        BX.ajax.post(this.ajaxPath, {
	          client_delete_logo: 'Y',
	          sessid: BX.bitrix_sessid(),
	          mode: mode
	        }, function () {
	          BX('configBlockLogo' + mode).style.display = 'none';
	          curLink.style.display = 'none';
	          BX('config_error_block').style.display = 'none';
	          BX('configWaitLogo' + mode).style.display = 'none';
	        });
	      }
	    }
	  }]);
	  return Logo;
	}();

	var Culture = /*#__PURE__*/function () {
	  function Culture(parent) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Culture);
	    this.cultureList = parent.cultureList;
	    this.selectorNode = document.querySelector("[data-role='culture-selector']");
	    this.shortDateNode = document.querySelector("[data-role='culture-short-date-format']");
	    this.longDateNode = document.querySelector("[data-role='culture-long-date-format']");

	    if (main_core.Type.isDomNode(this.selectorNode)) {
	      main_core.Event.bind(this.selectorNode, 'change', function () {
	        _this.changeFormatExample(_this.selectorNode.value);
	      });
	    }
	  }

	  babelHelpers.createClass(Culture, [{
	    key: "changeFormatExample",
	    value: function changeFormatExample(cultureId) {
	      if (!main_core.Type.isDomNode(this.shortDateNode) || !main_core.Type.isDomNode(this.longDateNode)) {
	        return;
	      }

	      this.shortDateNode.textContent = this.cultureList[cultureId].SHORT_DATE_FORMAT;
	      this.longDateNode.textContent = this.cultureList[cultureId].LONG_DATE_FORMAT;
	    }
	  }]);
	  return Culture;
	}();

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<a \n\t\t\t\t\t\t\t\tclass=\"access-delete\" \n\t\t\t\t\t\t\t\ttitle=\"", "\" \n\t\t\t\t\t\t\t\thref=\"javascript:void(0);\"\n\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t></a>\t\t\n\t\t\t\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ip_access_rights_", "[]\" size=\"30\"\n\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t>\t\n\t\t\t\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var namespace = main_core.Reflection.namespace('BX.Intranet.Configs');

	var IpSettingsClass = /*#__PURE__*/function () {
	  function IpSettingsClass(arCurIpRights) {
	    babelHelpers.classCallCheck(this, IpSettingsClass);
	    this.arCurIpRights = arCurIpRights;
	    var deleteButtons = document.querySelectorAll("[data-role='ip-right-delete']");
	    deleteButtons.forEach(function (button) {
	      BX.bind(button, "click", function () {
	        this.DeleteIpAccessRow(button);
	      }.bind(this));
	    }.bind(this));
	  }

	  babelHelpers.createClass(IpSettingsClass, [{
	    key: "DeleteIpAccessRow",
	    value: function DeleteIpAccessRow(ob) {
	      var tdObj = ob.parentNode.parentNode;
	      BX.remove(ob.parentNode);
	      var allInputBlocks = BX.findChildren(tdObj, {
	        tagName: 'div'
	      }, true);

	      if (allInputBlocks.length <= 0) {
	        var deleteRight = tdObj.parentNode.getAttribute("data-bx-right");
	        var arCurIpRightsNew = [];

	        for (var i = 0; i < this.arCurIpRights.length; i++) {
	          if (this.arCurIpRights[i] != deleteRight) arCurIpRightsNew.push(this.arCurIpRights[i]);
	        }

	        this.arCurIpRights = arCurIpRightsNew;
	        BX.remove(tdObj.parentNode);
	      }
	    }
	  }, {
	    key: "ShowIpAccessPopup",
	    value: function ShowIpAccessPopup(val) {
	      var _this = this;

	      val = val || [];
	      BX.Access.Init({
	        other: {
	          disabled: false,
	          disabled_g2: true,
	          disabled_cr: true
	        },
	        groups: {
	          disabled: true
	        },
	        socnetgroups: {
	          disabled: true
	        }
	      });
	      var startValue = {};

	      for (var i = 0; i < val.length; i++) {
	        startValue[val[i]] = true;
	      }

	      BX.Access.SetSelected(startValue);
	      BX.Access.ShowForm({
	        callback: function callback(arRights) {
	          var pr = false;

	          for (var provider in arRights) {
	            pr = BX.Access.GetProviderName(provider);

	            var _loop = function _loop() {
	              var onInputClickHandler = function onInputClickHandler(event) {
	                _this.addInputForIp(childBlockInput);
	              };

	              var childBlockInput = main_core.Tag.render(_templateObject(), right, onInputClickHandler);

	              var onCloseClickHandler = function onCloseClickHandler(event) {
	                _this.DeleteIpAccessRow(childBlockClose);
	              };

	              var childBlockClose = main_core.Tag.render(_templateObject2(), BX.message('SLToAllDel'), onCloseClickHandler);
	              var insertBlock = BX.create('tr', {
	                attrs: {
	                  "data-bx-right": right
	                },
	                children: [BX.create('td', {
	                  html: (pr.length > 0 ? pr + ': ' : '') + BX.util.htmlspecialchars(arRights[provider][right].name) + '&nbsp;',
	                  props: {
	                    'className': 'content-edit-form-field-name'
	                  }
	                }), BX.create('td', {
	                  props: {
	                    'className': 'content-edit-form-field-input',
	                    'colspan': 2
	                  },
	                  children: [BX.create('div', {
	                    children: [childBlockInput, childBlockClose]
	                  })]
	                })]
	              });
	              BX('ip_add_right_button').parentNode.insertBefore(insertBlock, BX('ip_add_right_button'));

	              _this.arCurIpRights.push(right);
	            };

	            for (var right in arRights[provider]) {
	              _loop();
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "addInputForIp",
	    value: function addInputForIp(input) {
	      var _this2 = this;

	      var inputParent = input.parentNode;
	      if (BX.nextSibling(inputParent)) return;
	      var newInputBlock = BX.clone(inputParent);
	      var newInput = BX.firstChild(newInputBlock);
	      newInput.value = "";

	      newInput.onclick = function () {
	        _this2.addInputForIp(newInput);
	      };

	      var nextInput = BX.nextSibling(newInput);

	      nextInput.onclick = function () {
	        _this2.DeleteIpAccessRow(nextInput);
	      };

	      inputParent.parentNode.appendChild(newInputBlock);
	    }
	  }]);
	  return IpSettingsClass;
	}();

	namespace.IpSettingsClass = IpSettingsClass;

	var Functions = /*#__PURE__*/function () {
	  function Functions(params) {
	    var _this3 = this;

	    babelHelpers.classCallCheck(this, Functions);
	    this.ajaxPath = params.ajaxPath || '';
	    this.addressFormatList = params.addressFormatList || {};
	    this.cultureList = params.cultureList || {};
	    new Logo(this);
	    new Culture(this);
	    var toAllCheckBox = BX('allow_livefeed_toall');
	    var defaultCont = BX('DEFAULT_all');

	    if (toAllCheckBox && defaultCont) {
	      BX.bind(toAllCheckBox, 'click', BX.delegate(function (e) {
	        defaultCont.style.display = this.checked ? '' : 'none';
	      }, toAllCheckBox));
	    }

	    var rightsCont = BX('RIGHTS_all');

	    if (toAllCheckBox && rightsCont) {
	      BX.bind(toAllCheckBox, 'click', BX.delegate(function (e) {
	        rightsCont.style.display = this.checked ? '' : 'none';
	      }, toAllCheckBox));
	    } //im chat


	    var toChatAllCheckBox = BX('allow_general_chat_toall');
	    var chatRightsCont = BX('chat_rights_all');

	    if (toChatAllCheckBox && chatRightsCont) {
	      BX.bind(toChatAllCheckBox, 'click', function () {
	        chatRightsCont.style.display = this.checked ? '' : 'none';
	      });
	    }

	    var generalChatCanPostSelect = BX('general_chat_can_post_select');
		if (generalChatCanPostSelect) {
		  BX.bind(generalChatCanPostSelect, 'change', function () {
			  if (generalChatCanPostSelect.value == 'MANAGER') {
				  chatRightsCont.style.display = '';
			  }
			  else
			  {
				  chatRightsCont.style.display = 'none';
			  }
		  })
		}

	    var mpUserInstallChechBox = BX('mp_allow_user_install');
	    var mpUserInstallCont = BX('mp_user_install');

	    if (mpUserInstallChechBox && mpUserInstallCont) {
	      BX.bind(mpUserInstallChechBox, 'click', function () {
	        mpUserInstallCont.style.display = this.checked ? '' : 'none';
	      });
	    }

	    var addressFormatSelect = BX('location_address_format_select');

	    if (addressFormatSelect) {
	      BX.bind(addressFormatSelect, 'change', function () {
	        var addressFormatDescription = BX('location_address_format_description');
	        addressFormatDescription.innerHTML = _this3.addressFormatList[addressFormatSelect.value];
	      });
	    }

	    if (BX.type.isDomNode(BX('smtp_use_auth'))) {
	      BX.bind(BX('smtp_use_auth'), 'change', BX.proxy(function () {
	        this.showHideSmtpAuth();
	      }, this));
	    }
	  }

	  babelHelpers.createClass(Functions, [{
	    key: "submitForm",
	    value: function submitForm(button) {
	      BX.addClass(button, 'webform-button-wait webform-button-active');
	      BX.submit(BX('configPostForm'));
	    }
	  }, {
	    key: "otpSwitchOffInfo",
	    value: function otpSwitchOffInfo(elem) {
	      if (!elem.checked) {
	        BX.PopupWindowManager.create('otpSwitchOffInfo', elem, {
	          autoHide: true,
	          offsetLeft: -100,
	          offsetTop: 15,
	          overlay: false,
	          draggable: {
	            restrict: true
	          },
	          closeByEsc: true,
	          closeIcon: {
	            right: '12px',
	            top: '10px'
	          },
	          content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message('CONFIG_OTP_SECURITY_SWITCH_OFF_INFO') + '</div>'
	        }).show();
	      }
	    }
	  }, {
	    key: "onGdprChange",
	    value: function onGdprChange(element) {
	      var items = document.querySelectorAll("[data-role='gdpr-data']");

	      for (var i = 0; i < items.length; i++) {
	        items[i].style.visibility = element.checked ? 'visible' : 'collapse';
	      }
	    }
	  }, {
	    key: "adminOtpIsRequiredInfo",
	    value: function adminOtpIsRequiredInfo(elem) {
	      BX.PopupWindowManager.create('adminOtpIsRequiredInfo', elem, {
	        autoHide: true,
	        offsetLeft: -100,
	        offsetTop: 15,
	        overlay: false,
	        draggable: {
	          restrict: true
	        },
	        closeByEsc: true,
	        closeIcon: {
	          right: '12px',
	          top: '10px'
	        },
	        content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message('CONFIG_OTP_ADMIN_IS_REQUIRED_INFO') + '</div>'
	      }).show();
	    }
	  }, {
	    key: "showDiskExtendedFullTextInfo",
	    value: function showDiskExtendedFullTextInfo(event, elem) {
	      event.stopPropagation;
	      event.preventDefault();
	      BX.PopupWindowManager.create('diskExtendedFullTextInfo', elem, {
	        autoHide: true,
	        offsetLeft: -100,
	        offsetTop: 15,
	        overlay: false,
	        draggable: {
	          restrict: true
	        },
	        closeByEsc: true,
	        closeIcon: {
	          right: '12px',
	          top: '10px'
	        },
	        content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message('CONFIG_DISK_EXTENDED_FULLTEXT_INFO') + '</div>'
	      }).show();
	    }
	  }, {
	    key: "geoDataSwitch",
	    value: function geoDataSwitch(element) {
	      if (element.checked) {
	        element.checked = false;
	        BX.UI.Dialogs.MessageBox.show({
	          'modal': true,
	          'minWidth': BX.message('CONFIG_COLLECT_GEO_DATA_CONFIRM').length > 400 ? 640 : 480,
	          'title': BX.message('CONFIG_COLLECT_GEO_DATA'),
	          'message': BX.message('CONFIG_COLLECT_GEO_DATA_CONFIRM'),
	          'buttons': BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	          'okCaption': BX.message('CONFIG_COLLECT_GEO_DATA_OK'),
	          'onOk': function onOk() {
	            element.checked = true;
	            return true;
	          }
	        });
	      }
	    }
	  }]);
	  return Functions;
	}();

	namespace.Functions = Functions;

}((this.BX.Intranet.Configs = this.BX.Intranet.Configs || {}),BX));
//# sourceMappingURL=script.js.map

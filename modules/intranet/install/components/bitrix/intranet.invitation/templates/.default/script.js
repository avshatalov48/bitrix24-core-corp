/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_popup,ui_buttons,ui_switcher,ui_entitySelector,main_core_events,main_core,ui_analytics) {
	'use strict';

	var _templateObject;
	var Submit = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Submit, _EventEmitter);
	  function Submit(parent) {
	    var _this;
	    babelHelpers.classCallCheck(this, Submit);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Submit).call(this));
	    _this.waitingResponse = false;
	    _this.parent = parent;
	    _this.setEventNamespace("BX.Intranet.Invitation.Submit");
	    _this.parent.subscribe("onButtonClick", function (event) {});
	    return _this;
	  }
	  babelHelpers.createClass(Submit, [{
	    key: "parseEmailAndPhone",
	    value: function parseEmailAndPhone(form) {
	      var _this2 = this;
	      if (!main_core.Type.isDomNode(form)) {
	        return;
	      }
	      var errorInputData = [];
	      var items = [];
	      var phoneExp = /^[\d+][\d\(\)\ -]{4,22}\d$/;
	      var rows = Array.prototype.slice.call(form.querySelectorAll(".js-form-row"));
	      (rows || []).forEach(function (row) {
	        var emailInput = row.querySelector("input[name='EMAIL[]']");
	        var phoneInput = row.querySelector("input[name='PHONE[]']");
	        var nameInput = row.querySelector("input[name='NAME[]']");
	        var lastNameInput = row.querySelector("input[name='LAST_NAME[]']");
	        var emailValue = emailInput.value.trim();
	        if (_this2.parent.isInvitationBySmsAvailable && main_core.Type.isDomNode(phoneInput)) {
	          var phoneValue = phoneInput.value.trim();
	          if (phoneValue) {
	            if (!phoneExp.test(String(phoneValue).toLowerCase())) {
	              errorInputData.push(phoneValue);
	            } else {
	              var phoneCountryInput = row.querySelector("input[name='PHONE_COUNTRY[]']");
	              items.push({
	                "PHONE": phoneValue,
	                "PHONE_COUNTRY": phoneCountryInput.value.trim(),
	                "NAME": nameInput.value,
	                "LAST_NAME": lastNameInput.value
	              });
	            }
	          }
	        } else if (emailValue) {
	          if (main_core.Validation.isEmail(emailValue)) {
	            items.push({
	              "EMAIL": emailValue,
	              "NAME": nameInput.value,
	              "LAST_NAME": lastNameInput.value
	            });
	          } else {
	            errorInputData.push(emailValue);
	          }
	        }
	      });
	      return [items, errorInputData];
	    }
	  }, {
	    key: "getGroupAndDepartmentData",
	    value: function getGroupAndDepartmentData(requestData) {
	      var selector = this.parent.selector;
	      var selectorItems = selector.getItems();
	      if (selectorItems["departments"].length > 0) {
	        requestData["UF_DEPARTMENT"] = selectorItems["departments"];
	      }
	      if (selectorItems["projects"].length > 0) {
	        requestData["SONET_GROUPS_CODE"] = selectorItems["projects"];
	      }
	    }
	  }, {
	    key: "submitInvite",
	    value: function submitInvite() {
	      var inviteForm = this.parent.contentBlocks["invite"].querySelector("form");
	      var _ref = babelHelpers.toConsumableArray(this.parseEmailAndPhone(inviteForm)),
	        items = _ref[0],
	        errorInputData = _ref[1];
	      if (errorInputData.length > 0) {
	        var event = new main_core.Event.BaseEvent({
	          data: {
	            error: main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', ')
	          }
	        });
	        this.emit("onInputError", event);
	        return;
	      }
	      if (items.length <= 0) {
	        var _event = new main_core.Event.BaseEvent({
	          data: {
	            error: main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")
	          }
	        });
	        this.emit("onInputError", _event);
	        return;
	      }
	      var requestData = {
	        "ITEMS": items
	      };
	      var analyticsLabel = {
	        "INVITATION_TYPE": "invite",
	        "INVITATION_COUNT": items.length
	      };
	      this.sendAction("invite", requestData, analyticsLabel);
	    }
	  }, {
	    key: "submitInviteWithGroupDp",
	    value: function submitInviteWithGroupDp() {
	      var inviteWithGroupDpForm = this.parent.contentBlocks["invite-with-group-dp"].querySelector("form");
	      var _ref2 = babelHelpers.toConsumableArray(this.parseEmailAndPhone(inviteWithGroupDpForm)),
	        items = _ref2[0],
	        errorInputData = _ref2[1];
	      if (errorInputData.length > 0) {
	        this.parent.showErrorMessage(main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', '));
	        return;
	      }
	      if (items.length <= 0) {
	        var event = new main_core.Event.BaseEvent({
	          data: {
	            error: main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")
	          }
	        });
	        this.emit("onInputError", event);
	        return;
	      }
	      var requestData = {
	        "ITEMS": items
	      };
	      this.getGroupAndDepartmentData(requestData);
	      var analyticsLabel = {
	        "INVITATION_TYPE": "withGroupOrDepartment",
	        "INVITATION_COUNT": items.length
	      };
	      this.sendAction("inviteWithGroupDp", requestData, analyticsLabel);
	    }
	  }, {
	    key: "submitSelf",
	    value: function submitSelf() {
	      var selfForm = this.parent.contentBlocks["self"].querySelector("form");
	      var obRequestData = {
	        "allow_register": selfForm["allow_register"].value,
	        "allow_register_confirm": selfForm["allow_register_confirm"].checked ? "Y" : "N",
	        "allow_register_secret": selfForm["allow_register_secret"].value,
	        "allow_register_whitelist": selfForm["allow_register_whitelist"].value
	      };
	      this.sendAction("self", obRequestData);
	    }
	  }, {
	    key: "submitExtranet",
	    value: function submitExtranet() {
	      var extranetForm = this.parent.contentBlocks["extranet"].querySelector("form");
	      var _ref3 = babelHelpers.toConsumableArray(this.parseEmailAndPhone(extranetForm)),
	        items = _ref3[0],
	        errorInputData = _ref3[1];
	      if (errorInputData.length > 0) {
	        this.parent.showErrorMessage(main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', '));
	        return;
	      }
	      if (items.length <= 0) {
	        var event = new main_core.Event.BaseEvent({
	          data: {
	            error: main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")
	          }
	        });
	        this.emit("onInputError", event);
	        return;
	      }
	      var requestData = {
	        "ITEMS": items
	      };
	      this.getGroupAndDepartmentData(requestData);
	      var analyticsLabel = {
	        "INVITATION_TYPE": "extranet",
	        "INVITATION_COUNT": items.length
	      };
	      this.sendAction("extranet", requestData, analyticsLabel);
	    }
	  }, {
	    key: "submitIntegrator",
	    value: function submitIntegrator() {
	      this.getIntegratorConfirmPopup().show();
	    }
	  }, {
	    key: "getIntegratorConfirmPopup",
	    value: function getIntegratorConfirmPopup() {
	      var _this3 = this;
	      var integratorForm = this.parent.contentBlocks.integrator.querySelector('form');
	      var obRequestData = {
	        integrator_email: integratorForm.integrator_email.value
	      };
	      var analyticsLabel = {
	        INVITATION_TYPE: 'integrator'
	      };
	      var message = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"invite-integrator-confirm-message\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_DESCRIPTION'));
	      var moreLink = message.querySelector('span');
	      main_core.Event.bind(moreLink, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20682986');
	      });
	      var popup = new main_popup.Popup({
	        id: 'integrator-confirm-invitation-popup',
	        maxWidth: 500,
	        closeIcon: false,
	        overlay: true,
	        contentPadding: 10,
	        titleBar: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_TITLE'),
	        content: message,
	        offsetLeft: 100,
	        buttons: [new ui_buttons.CreateButton({
	          text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_YES'),
	          onclick: function onclick() {
	            popup.close();
	            _this3.sendAction('inviteIntegrator', obRequestData, analyticsLabel);
	          }
	        }), new ui_buttons.CancelButton({
	          text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_NO'),
	          onclick: function onclick() {
	            popup.close();
	          }
	        })],
	        events: {
	          onClose: function onClose() {
	            popup.destroy();
	          }
	        }
	      });
	      return popup;
	    }
	  }, {
	    key: "submitMassInvite",
	    value: function submitMassInvite() {
	      var massInviteForm = this.parent.contentBlocks["mass-invite"].querySelector("form");
	      var obRequestData = {
	        "ITEMS": massInviteForm["mass_invite_emails"].value
	      };
	      var analyticsLabel = {
	        "INVITATION_TYPE": "mass"
	      };
	      this.sendAction("massInvite", obRequestData, analyticsLabel);
	    }
	  }, {
	    key: "submitAdd",
	    value: function submitAdd() {
	      var addForm = this.parent.contentBlocks["add"].querySelector("form");
	      var requestData = {
	        "ADD_EMAIL": addForm["ADD_EMAIL"].value,
	        "ADD_NAME": addForm["ADD_NAME"].value,
	        "ADD_LAST_NAME": addForm["ADD_LAST_NAME"].value,
	        "ADD_POSITION": addForm["ADD_POSITION"].value,
	        "ADD_SEND_PASSWORD": addForm["ADD_SEND_PASSWORD"] && !!addForm["ADD_SEND_PASSWORD"].checked ? addForm["ADD_SEND_PASSWORD"].value : "N"
	      };
	      this.getGroupAndDepartmentData(requestData);
	      var analyticsLabel = {
	        "INVITATION_TYPE": "add"
	      };
	      this.sendAction("add", requestData, analyticsLabel);
	    }
	  }, {
	    key: "sendAction",
	    value: function sendAction(action, requestData, analyticsLabel) {
	      var _this4 = this;
	      this.disableSubmitButton(true);
	      requestData["userOptions"] = this.parent.userOptions;
	      requestData["analyticsData"] = this.parent.analyticsLabel;
	      BX.ajax.runComponentAction(this.parent.componentName, action, {
	        signedParameters: this.parent.signedParameters,
	        mode: "ajax",
	        data: requestData,
	        analyticsLabel: analyticsLabel
	      }).then(function (response) {
	        _this4.disableSubmitButton(false);
	        if (response.data) {
	          if (action === "self") {
	            _this4.parent.showSuccessMessage(response.data);
	          } else {
	            _this4.parent.changeContent("success");
	            _this4.sendSuccessEvent(response.data);
	          }
	        }
	        main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', function () {
	          BX.SidePanel.Instance.postMessageTop(window, 'BX.Bitrix24.EmailConfirmation:showPopup');
	        });
	      }, function (response) {
	        _this4.disableSubmitButton(false);
	        if (response.data == "user_limit") {
	          B24.licenseInfoPopup.show("featureID", BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TITLE"), BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT"));
	        } else {
	          _this4.parent.showErrorMessage(response.errors[0].message);
	        }
	      });
	    }
	  }, {
	    key: "disableSubmitButton",
	    value: function disableSubmitButton(isDisable) {
	      var button = this.parent.button;
	      if (!main_core.Type.isDomNode(button) || !main_core.Type.isBoolean(isDisable)) {
	        return;
	      }
	      if (isDisable) {
	        this.waitingResponse = true;
	        main_core.Dom.addClass(button, ["ui-btn-wait", "invite-cursor-auto"]);
	      } else {
	        this.waitingResponse = false;
	        main_core.Dom.removeClass(button, ["ui-btn-wait", "invite-cursor-auto"]);
	      }
	    }
	  }, {
	    key: "sendSuccessEvent",
	    value: function sendSuccessEvent(users) {
	      BX.SidePanel.Instance.postMessageAll(window, "BX.Intranet.Invitation:onAdd", {
	        users: users
	      });
	    }
	  }]);
	  return Submit;
	}(main_core_events.EventEmitter);

	var SelfRegister = /*#__PURE__*/function () {
	  function SelfRegister(parent) {
	    babelHelpers.classCallCheck(this, SelfRegister);
	    this.parent = parent;
	    if (main_core.Type.isDomNode(this.parent.contentBlocks["self"])) {
	      this.selfBlock = this.parent.contentBlocks["self"];
	      this.bindActions();
	      var switcherNode = this.selfBlock.querySelector('.invite-dialog-fast-reg-control-switcher');
	      this.switcher = new ui_switcher.Switcher({
	        inputName: 'allow_register',
	        id: 'allow_register',
	        checked: parent.isSelfRegisterEnabled,
	        node: switcherNode,
	        handlers: {
	          toggled: this.toggleSettings.bind(this)
	        }
	      });
	    }
	  }
	  babelHelpers.createClass(SelfRegister, [{
	    key: "bindActions",
	    value: function bindActions() {
	      var _this = this;
	      var regenerateButton = this.selfBlock.querySelector("[data-role='selfRegenerateSecretButton']");
	      if (main_core.Type.isDomNode(regenerateButton)) {
	        main_core.Event.bind(regenerateButton, 'click', function () {
	          _this.parent.activateButton();
	          _this.regenerateSecret(_this.parent.regenerateUrlBase);
	        });
	      }
	      var copyRegisterUrlButton = this.selfBlock.querySelector("[data-role='copyRegisterUrlButton']");
	      if (main_core.Type.isDomNode(copyRegisterUrlButton)) {
	        main_core.Event.bind(copyRegisterUrlButton, 'click', function () {
	          _this.copyRegisterUrl();
	        });
	      }
	      var allowRegisterConfirm = this.selfBlock.querySelector("[data-role='allowRegisterConfirm']");
	      if (main_core.Type.isDomNode(allowRegisterConfirm)) {
	        main_core.Event.bind(allowRegisterConfirm, 'change', function () {
	          _this.parent.activateButton();
	          _this.toggleWhiteList(allowRegisterConfirm);
	        });
	      }
	      var selfWhiteList = this.selfBlock.querySelector("[data-role='selfWhiteList']");
	      if (main_core.Type.isDomNode(selfWhiteList)) {
	        main_core.Event.bind(selfWhiteList, 'input', function () {
	          _this.parent.activateButton();
	        });
	      }
	    }
	  }, {
	    key: "regenerateSecret",
	    value: function regenerateSecret(registerUrl) {
	      var value = main_core.Text.getRandom(8);
	      var allowRegisterSecretNode = this.selfBlock.querySelector("[data-role='allowRegisterSecret']");
	      if (main_core.Type.isDomNode(allowRegisterSecretNode)) {
	        allowRegisterSecretNode.value = value || '';
	      }
	      var allowRegisterUrlNode = this.selfBlock.querySelector("[data-role='allowRegisterUrl']");
	      if (main_core.Type.isDomNode(allowRegisterUrlNode) && registerUrl) {
	        allowRegisterUrlNode.value = registerUrl + (value || 'yes');
	      }
	    }
	  }, {
	    key: "copyRegisterUrl",
	    value: function copyRegisterUrl() {
	      var allowRegisterUrlNode = this.selfBlock.querySelector("[data-role='allowRegisterUrl']");
	      if (main_core.Type.isDomNode(allowRegisterUrlNode)) {
	        BX.clipboard.copy(allowRegisterUrlNode.value);
	        this.showHintPopup(main_core.Loc.getMessage("BX24_INVITE_DIALOG_COPY_URL"), allowRegisterUrlNode);
	        BX.ajax.runAction('intranet.controller.invite.copyregisterurl', {
	          data: {}
	        }).then(function (response) {}, function (response) {});
	        this.parent.analytics.send();
	      }
	    }
	  }, {
	    key: "showHintPopup",
	    value: function showHintPopup(message, bindNode) {
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
	    }
	  }, {
	    key: "toggleSettings",
	    value: function toggleSettings() {
	      this.parent.activateButton();
	      var controlBlock = this.selfBlock.querySelector(".js-invite-dialog-fast-reg-control-container");
	      if (main_core.Type.isDomNode(controlBlock)) {
	        if (!main_core.Dom.hasClass(controlBlock, 'disallow-registration')) {
	          var switcher = controlBlock.querySelector("[data-role='self-switcher']");
	          this.showHintPopup(main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_SELF_OFF_HINT"), switcher);
	        }
	        main_core.Dom.toggleClass(controlBlock, 'disallow-registration');
	      }
	      var settingsBlock = this.selfBlock.querySelector("[data-role='selfSettingsBlock']");
	      if (main_core.Type.isDomNode(settingsBlock)) {
	        main_core.Dom.style(settingsBlock, 'display', this.switcher.checked ? 'block' : 'none');
	      }
	    }
	  }, {
	    key: "toggleWhiteList",
	    value: function toggleWhiteList(inputElement) {
	      var selfWhiteList = this.selfBlock.querySelector("[data-role='selfWhiteList']");
	      if (main_core.Type.isDomNode(selfWhiteList)) {
	        main_core.Dom.style(selfWhiteList, 'display', inputElement.checked ? 'block' : 'none');
	      }
	    }
	  }]);
	  return SelfRegister;
	}();

	var _templateObject$1;
	var Phone = /*#__PURE__*/function () {
	  function Phone(parent) {
	    babelHelpers.classCallCheck(this, Phone);
	    this.parent = parent;
	    this.count = 0;
	    this.index = 0;
	    this.maxCount = 5;
	    this.inputStack = [];
	  }
	  babelHelpers.createClass(Phone, [{
	    key: "renderPhoneRow",
	    value: function renderPhoneRow(inputNode) {
	      var _this = this;
	      if (this.count >= this.maxCount) {
	        return;
	      }
	      if (!main_core.Type.isDomNode(inputNode)) {
	        return;
	      }
	      var num = inputNode.getAttribute("data-num");
	      if (inputNode.parentNode.querySelector("#phone_number_" + num)) {
	        return;
	      }
	      var element = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span style=\"z-index: 3;\" class=\"ui-ctl-before\" data-role=\"phone-block\">\n\t\t\t\t<input type=\"hidden\" name=\"PHONE_COUNTRY[]\" id=\"phone_country_", "\" value=\"\">\n\t\t\t\t<input type=\"hidden\" name=\"PHONE[]\" id=\"phone_number_", "\" value=\"\">\n\t\t\t\t<div class=\"invite-dialog-phone-flag-block\" data-role=\"flag\">\n\t\t\t\t\t<span data-role=\"phone_flag_", "\" style=\"pointer-events: none;\"></span>\n\t\t\t\t</div>\n\t\t\t\t<input class=\"invite-dialog-phone-input\" type=\"hidden\" id=\"phone_input_", "\" value=\"\">&nbsp;\n\t\t\t</span>\n\t\t"])), num, num, num, num);
	      inputNode.style.paddingLeft = "57px";
	      main_core.Dom.append(element, inputNode.parentNode);
	      var flagNode = inputNode.parentNode.querySelector("[data-role='flag']");
	      if (main_core.Type.isDomNode(flagNode)) {
	        main_core.Event.bind(inputNode.parentNode.querySelector("[data-role='flag']"), 'click', function () {
	          _this.showCountrySelector(num);
	        });
	      }
	      var changeCallback = function changeCallback(i, inputNode) {
	        return function (e) {
	          if (main_core.Type.isDomNode(inputNode.parentNode)) {
	            inputNode.parentNode.querySelector('#phone_number_' + i).value = e.value;
	            inputNode.parentNode.querySelector('#phone_country_' + i).value = e.country;
	          }
	        };
	      };
	      this.inputStack[num] = new BX.PhoneNumber.Input({
	        node: inputNode,
	        flagNode: inputNode.parentNode.querySelector("[data-role='phone_flag_" + num + "']"),
	        flagSize: 16,
	        onChange: changeCallback(num, inputNode)
	      });

	      //for ctrl+v paste
	      var id = setInterval(function () {
	        var _inputNode$parentNode, _inputNode$parentNode2;
	        if (!((_inputNode$parentNode = inputNode.parentNode) !== null && _inputNode$parentNode !== void 0 && _inputNode$parentNode.querySelector('#phone_number_' + num).value) || !((_inputNode$parentNode2 = inputNode.parentNode) !== null && _inputNode$parentNode2 !== void 0 && _inputNode$parentNode2.querySelector('#phone_country_' + num).value)) {
	          changeCallback(num, inputNode)({
	            value: _this.inputStack[num].getValue(),
	            country: _this.inputStack[num].formatter ? _this.inputStack[num].getCountry() : null
	          });
	        } else {
	          clearInterval(id);
	        }
	      }, 1000);
	    }
	  }, {
	    key: "showCountrySelector",
	    value: function showCountrySelector(i) {
	      this.inputStack[i]._onFlagClick();
	    }
	  }]);
	  return Phone;
	}();

	var _templateObject$2, _templateObject2, _templateObject3, _templateObject4;
	var Row = /*#__PURE__*/function () {
	  function Row(parent, params) {
	    babelHelpers.classCallCheck(this, Row);
	    this.parent = parent;
	    this.contentBlock = params.contentBlock;
	    this.inputNum = 0;
	    if (main_core.Type.isDomNode(this.contentBlock)) {
	      this.rowsContainer = this.contentBlock.querySelector("[data-role='rows-container']");
	      this.bindActions();
	    }
	    if (this.parent.isInvitationBySmsAvailable) {
	      this.phoneObj = new Phone(this);
	    }
	  }
	  babelHelpers.createClass(Row, [{
	    key: "bindActions",
	    value: function bindActions() {
	      var _this = this;
	      var moreButton = this.contentBlock.querySelector("[data-role='invite-more']");
	      if (main_core.Type.isDomNode(moreButton)) {
	        main_core.Event.unbindAll(moreButton);
	        main_core.Event.bind(moreButton, 'click', function () {
	          _this.renderInputRow();
	        });
	      }
	      var massInviteButton = this.contentBlock.querySelector("[data-role='invite-mass']");
	      if (main_core.Type.isDomNode(massInviteButton)) {
	        main_core.Event.unbindAll(massInviteButton);
	        main_core.Event.bind(massInviteButton, 'click', function () {
	          var massMenuNode = document.querySelector("[data-role='menu-mass-invite']");
	          if (main_core.Type.isDomNode(massMenuNode)) {
	            BX.fireEvent(massMenuNode, 'click');
	          }
	        });
	      }
	    }
	  }, {
	    key: "checkPhoneInput",
	    value: function checkPhoneInput(element) {
	      var phoneExp = /^[\d+][\d\(\)\ -]{2,14}\d$/;
	      if (element.value && phoneExp.test(String(element.value).toLowerCase())) {
	        this.phoneObj.renderPhoneRow(element);
	      } else if (element.value.length === 0) {
	        var _this$renderDefaultRo;
	        (_this$renderDefaultRo = this.renderDefaultRow(element)) === null || _this$renderDefaultRo === void 0 ? void 0 : _this$renderDefaultRo.focus();
	      }
	    }
	  }, {
	    key: "bindPhoneChecker",
	    value: function bindPhoneChecker(element) {
	      var _this2 = this;
	      if (this.parent.isInvitationBySmsAvailable && main_core.Type.isDomNode(element)) {
	        var inputNodes = Array.prototype.slice.call(element.querySelectorAll(".js-email-phone-input"));
	        if (inputNodes) {
	          inputNodes.forEach(function (element) {
	            main_core.Event.bind(element, 'input', function () {
	              _this2.checkPhoneInput(element);
	            });
	          });
	        }
	      }
	    }
	  }, {
	    key: "renderDefaultRow",
	    value: function renderDefaultRow(node) {
	      var phoneFlagBlock = node.parentNode.querySelector('[data-role="phone-block"]');
	      if (main_core.Type.isDomNode(phoneFlagBlock)) {
	        main_core.Dom.remove(phoneFlagBlock);
	      }
	      var phoneBlock = node.parentNode.querySelector('input.ui-ctl-element');
	      if (main_core.Type.isDomNode(phoneBlock)) {
	        var newInput = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\t\tdata-num=\"", "\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element js-email-phone-input\"\n\t\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t\t/>"])), phoneBlock.name, node.getAttribute('data-num'), phoneBlock.placeholder);
	        main_core.Dom.replace(phoneBlock, newInput);
	        this.bindCloseIcons(newInput.parentNode);
	        this.bindPhoneChecker(newInput.parentNode);
	        main_core.Dom.remove(phoneBlock);
	        return newInput;
	      }
	      return phoneBlock;
	    }
	  }, {
	    key: "bindCloseIcons",
	    value: function bindCloseIcons(container) {
	      var _this3 = this;
	      var inputNodes = Array.prototype.slice.call(container.querySelectorAll("input"));
	      (inputNodes || []).forEach(function (node) {
	        var closeIcon = node.nextElementSibling;
	        main_core.Event.bind(node, 'input', function () {
	          main_core.Dom.style(closeIcon, 'display', node.value !== "" ? "block" : "none");
	        });
	        main_core.Event.bind(closeIcon, 'click', function (event) {
	          event.preventDefault();
	          if (main_core.Type.isDomNode(node.parentNode)) {
	            _this3.renderDefaultRow(node);
	          }
	          main_core.Dom.style(closeIcon, 'display', "none");
	        });
	      });
	    }
	  }, {
	    key: "renderInviteInputs",
	    value: function renderInviteInputs() {
	      var numRows = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 3;
	      main_core.Dom.clean(this.rowsContainer);
	      for (var i = 0; i < numRows; i++) {
	        this.renderInputRow(i === 0);
	      }
	    }
	  }, {
	    key: "renderInputRow",
	    value: function renderInputRow() {
	      var showTitles = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      var emailTitle, nameTitle, lastNameTitle;
	      if (showTitles) {
	        emailTitle = "\n\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT"), "\n\t\t\t\t</div>");
	        nameTitle = "\n\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE"), "\n\t\t\t\t</div>");
	        lastNameTitle = "\n\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE"), "\n\t\t\t\t</div>");
	      }
	      var element = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"invite-form-row js-form-row\">\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input \n\t\t\t\t\t\t\tname=\"EMAIL[]\" \n\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\tdata-num=\"", "\" \n\t\t\t\t\t\t\tclass=\"ui-ctl-element js-email-phone-input\" \n\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input name=\"NAME[]\" type=\"text\" class=\"ui-ctl-element js-email-name-input\" placeholder=\"", "\">\n\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input name=\"LAST_NAME[]\" type=\"text\" class=\"ui-ctl-element js-email-last-name-input\" placeholder=\"", "\">\n\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), showTitles ? emailTitle : '', this.inputNum++, main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT'), showTitles ? nameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE'), showTitles ? lastNameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE'));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	      this.bindPhoneChecker(element);
	    }
	  }, {
	    key: "renderRegisterInputs",
	    value: function renderRegisterInputs() {
	      main_core.Dom.clean(this.rowsContainer);
	      var element = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_NAME\" id=\"ADD_NAME\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_LAST_NAME\" id=\"ADD_LAST_NAME\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_EMAIL\" id=\"ADD_EMAIL\" class=\"ui-ctl-element\" maxlength=\"50\">\n\t\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_POSITION\" id=\"ADD_POSITION\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\t\t\t\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_EMAIL_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_POSITION_TITLE"));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	    }
	  }, {
	    key: "renderIntegratorInput",
	    value: function renderIntegratorInput() {
	      main_core.Dom.clean(this.rowsContainer);
	      var element = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input \n\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\tvalue=\"\" \n\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\tname=\"integrator_email\" \n\t\t\t\t\t\t\tid=\"integrator_email\" \n\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<button type=\"button\" class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL"), main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL"));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	    }
	  }]);
	  return Row;
	}();

	var Selector = /*#__PURE__*/function () {
	  function Selector(parent, params) {
	    babelHelpers.classCallCheck(this, Selector);
	    this.parent = parent;
	    this.contentBlock = params.contentBlock;
	    this.options = params.options;
	    this.entities = [];
	    this.prepareOptions();
	  }
	  babelHelpers.createClass(Selector, [{
	    key: "prepareOptions",
	    value: function prepareOptions() {
	      for (var type in this.options) {
	        if (!this.options.hasOwnProperty(type)) {
	          continue;
	        }
	        if (type === "department" && !!this.options[type]) {
	          this.entities.push({
	            id: "department",
	            options: {
	              selectMode: "departmentsOnly",
	              allowOnlyUserDepartments: !(!!this.options["isAdmin"] && this.options["isAdmin"] === true),
	              allowSelectRootDepartment: true
	            }
	          });
	        }
	        if (type === "project" && !!this.options[type]) {
	          var _this$options$showCre;
	          var options = {
	            fillRecentTab: true,
	            '!type': ['collab'],
	            createProjectLink: (_this$options$showCre = this.options.showCreateButton) !== null && _this$options$showCre !== void 0 ? _this$options$showCre : true
	          };
	          if (Object.prototype.hasOwnProperty.call(this.options, 'projectLimitExceeded') && Object.prototype.hasOwnProperty.call(this.options, 'projectLimitFeatureId')) {
	            options.lockProjectLink = this.options.projectLimitExceeded;
	            options.lockProjectLinkFeatureId = this.options.projectLimitFeatureId;
	          }
	          var optionValue = {
	            id: 'project',
	            options: options
	          };
	          if (this.options[type] === "extranet") {
	            optionValue["options"]["extranet"] = true;
	          }
	          this.entities.push(optionValue);
	        }
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var preselectedItems = [];
	      if (this.options.hasOwnProperty('projectId') && this.options.projectId > 0) {
	        preselectedItems.push(['project', this.options.projectId]);
	      }
	      if (this.options.hasOwnProperty('departmentsId') && Array.isArray(this.options.departmentsId)) {
	        this.options.departmentsId.forEach(function (departmentId) {
	          preselectedItems.push(['department', departmentId]);
	        });
	      }
	      this.tagSelector = new ui_entitySelector.TagSelector({
	        dialogOptions: {
	          preselectedItems: preselectedItems,
	          entities: this.entities,
	          context: 'INTRANET_INVITATION'
	        }
	      });
	      if (main_core.Type.isDomNode(this.contentBlock)) {
	        main_core.Dom.clean(this.contentBlock);
	        this.tagSelector.renderTo(this.contentBlock);
	      }
	    }
	  }, {
	    key: "getItems",
	    value: function getItems() {
	      var departments = [];
	      var projects = [];
	      var tagSelectorItems = this.tagSelector.getDialog().getSelectedItems();
	      tagSelectorItems.forEach(function (item) {
	        var id = parseInt(item.getId());
	        var type = item.getEntityId();
	        if (type === "department") {
	          departments.push(id);
	        } else if (type === "project") {
	          projects.push(id);
	        }
	      });
	      return {
	        departments: departments,
	        projects: projects
	      };
	    }
	  }]);
	  return Selector;
	}();

	var ActiveDirectory = /*#__PURE__*/function () {
	  function ActiveDirectory(parent) {
	    babelHelpers.classCallCheck(this, ActiveDirectory);
	    this.parent = parent;
	  }
	  babelHelpers.createClass(ActiveDirectory, [{
	    key: "showForm",
	    value: function showForm() {
	      BX.UI.Feedback.Form.open({
	        id: 'intranet-active-directory',
	        defaultForm: {
	          id: 309,
	          sec: 'fbc0n3'
	        }
	      });
	    }
	  }]);
	  return ActiveDirectory;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cSection = /*#__PURE__*/new WeakMap();
	var _isAdmin = /*#__PURE__*/new WeakMap();
	var _getAdminAllowMode = /*#__PURE__*/new WeakSet();
	var _getIsAdmin = /*#__PURE__*/new WeakSet();
	var _getCSection = /*#__PURE__*/new WeakSet();
	var Analytics = /*#__PURE__*/function () {
	  function Analytics(cSection, isAdmin) {
	    babelHelpers.classCallCheck(this, Analytics);
	    _classPrivateMethodInitSpec(this, _getCSection);
	    _classPrivateMethodInitSpec(this, _getIsAdmin);
	    _classPrivateMethodInitSpec(this, _getAdminAllowMode);
	    _classPrivateFieldInitSpec(this, _cSection, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _cSection, cSection);
	    babelHelpers.classPrivateFieldSet(this, _isAdmin, isAdmin);
	  }
	  babelHelpers.createClass(Analytics, [{
	    key: "send",
	    value: function send() {
	      ui_analytics.sendData({
	        tool: Analytics.TOOLS,
	        category: Analytics.CATEGORY_INVITATION,
	        event: Analytics.EVENT_COPY,
	        c_section: _classPrivateMethodGet(this, _getCSection, _getCSection2).call(this),
	        p1: _classPrivateMethodGet(this, _getIsAdmin, _getIsAdmin2).call(this),
	        p2: _classPrivateMethodGet(this, _getAdminAllowMode, _getAdminAllowMode2).call(this)
	      });
	    }
	  }, {
	    key: "sendTabData",
	    value: function sendTabData(section, subSection) {
	      if (!section) {
	        return;
	      }
	      ui_analytics.sendData({
	        tool: Analytics.TOOLS,
	        category: Analytics.CATEGORY_INVITATION,
	        event: Analytics.EVENT_TAB_VIEW,
	        c_section: section,
	        c_sub_section: subSection
	      });
	    }
	  }, {
	    key: "sendOpenSliderData",
	    value: function sendOpenSliderData(section) {
	      ui_analytics.sendData({
	        tool: Analytics.TOOLS,
	        category: Analytics.CATEGORY_INVITATION,
	        event: Analytics.EVENT_OPEN_SLIDER_INVITATION,
	        c_section: section
	      });
	    }
	  }]);
	  return Analytics;
	}();
	function _getAdminAllowMode2() {
	  return document.querySelector('#allow_register_confirm').checked ? Analytics.ADMIN_ALLOW_MODE_Y : Analytics.ADMIN_ALLOW_MODE_N;
	}
	function _getIsAdmin2() {
	  return babelHelpers.classPrivateFieldGet(this, _isAdmin) ? Analytics.IS_ADMIN_Y : Analytics.IS_ADMIN_N;
	}
	function _getCSection2() {
	  return babelHelpers.classPrivateFieldGet(this, _cSection).source;
	}
	babelHelpers.defineProperty(Analytics, "TOOLS", 'Invitation');
	babelHelpers.defineProperty(Analytics, "TOOLS_HEADER", 'headerPopup');
	babelHelpers.defineProperty(Analytics, "EVENT_OPEN_SLIDER_INVITATION", 'drawer_open');
	babelHelpers.defineProperty(Analytics, "CATEGORY_INVITATION", 'invitation');
	babelHelpers.defineProperty(Analytics, "EVENT_COPY", 'copy_invitation_link');
	babelHelpers.defineProperty(Analytics, "ADMIN_ALLOW_MODE_Y", 'askAdminToAllow_Y');
	babelHelpers.defineProperty(Analytics, "ADMIN_ALLOW_MODE_N", 'askAdminToAllow_N');
	babelHelpers.defineProperty(Analytics, "IS_ADMIN_Y", 'isAdmin_Y');
	babelHelpers.defineProperty(Analytics, "IS_ADMIN_N", 'isAdmin_N');
	babelHelpers.defineProperty(Analytics, "EVENT_TAB_VIEW", 'tab_view');
	babelHelpers.defineProperty(Analytics, "TAB_EMAIL", 'tab_by_email');
	babelHelpers.defineProperty(Analytics, "TAB_MASS", 'tab_mass');
	babelHelpers.defineProperty(Analytics, "TAB_DEPARTMENT", 'tab_department');
	babelHelpers.defineProperty(Analytics, "TAB_INTEGRATOR", 'tab_integrator');
	babelHelpers.defineProperty(Analytics, "TAB_LINK", 'by_link');
	babelHelpers.defineProperty(Analytics, "TAB_REGISTRATION", 'registration');
	babelHelpers.defineProperty(Analytics, "TAB_EXTRANET", 'extranet');
	babelHelpers.defineProperty(Analytics, "TAB_AD", 'AD');

	var Form = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Form, _EventEmitter);
	  function Form(formParams) {
	    var _this;
	    babelHelpers.classCallCheck(this, Form);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Form).call(this));
	    var params = main_core.Type.isPlainObject(formParams) ? formParams : {};
	    _this.signedParameters = params.signedParameters;
	    _this.componentName = params.componentName;
	    _this.menuContainer = params.menuContainerNode;
	    _this.contentContainer = params.contentContainerNode;
	    _this.contentBlocks = {};
	    _this.userOptions = params.userOptions;
	    _this.isExtranetInstalled = params.isExtranetInstalled === "Y";
	    _this.isCloud = params.isCloud === "Y";
	    _this.isAdmin = params.isAdmin === "Y";
	    _this.isInvitationBySmsAvailable = params.isInvitationBySmsAvailable === "Y";
	    _this.isCreatorEmailConfirmed = params.isCreatorEmailConfirmed === "Y";
	    _this.regenerateUrlBase = params.regenerateUrlBase;
	    _this.firstInvitationBlock = params.firstInvitationBlock;
	    _this.isSelfRegisterEnabled = params.isSelfRegisterEnabled;
	    _this.analyticsLabel = params.analyticsLabel;
	    _this.projectLimitExceeded = main_core.Type.isBoolean(params.projectLimitExceeded) ? params.projectLimitExceeded : true;
	    _this.projectLimitFeatureId = main_core.Type.isString(params.projectLimitFeatureId) ? params.projectLimitFeatureId : '';
	    _this.isCollabEnabled = params.isCollabEnabled === 'Y';
	    if (main_core.Type.isDomNode(_this.contentContainer)) {
	      var blocks = Array.prototype.slice.call(_this.contentContainer.querySelectorAll(".js-intranet-invitation-block"));
	      (blocks || []).forEach(function (block) {
	        var blockType = block.getAttribute("data-role");
	        blockType = blockType.replace("-block", "");
	        _this.contentBlocks[blockType] = block;
	      });
	      _this.errorMessageBlock = _this.contentContainer.querySelector("[data-role='error-message']");
	      _this.successMessageBlock = _this.contentContainer.querySelector("[data-role='success-message']");
	      BX.UI.Hint.init(_this.contentContainer);
	    }
	    _this.button = document.querySelector("#intranet-invitation-btn");
	    if (main_core.Type.isDomNode(_this.menuContainer)) {
	      _this.menuItems = Array.prototype.slice.call(_this.menuContainer.querySelectorAll("a"));
	      (_this.menuItems || []).forEach(function (item) {
	        main_core.Event.bind(item, 'click', function () {
	          _this.changeContent(item.getAttribute('data-action'));
	        });
	        if (item.getAttribute('data-action') === _this.firstInvitationBlock) {
	          BX.fireEvent(item, 'click');
	        }
	      });
	    }
	    _this.submit = new Submit(babelHelpers.assertThisInitialized(_this));
	    _this.submit.subscribe('onInputError', function (event) {
	      _this.showErrorMessage(event.data.error);
	    });
	    _this.analytics = new Analytics(_this.analyticsLabel, _this.isAdmin);
	    _this.analytics.sendOpenSliderData(_this.analyticsLabel.source);
	    if (_this.isCloud) {
	      _this.selfRegister = new SelfRegister(babelHelpers.assertThisInitialized(_this));
	    }
	    _this.arrowBox = document.querySelector('.invite-wrap-decal-arrow');
	    if (main_core.Type.isDomNode(_this.arrowBox)) {
	      _this.arrowRect = _this.arrowBox.getBoundingClientRect();
	      _this.arrowHeight = _this.arrowRect.height;
	      _this.setSetupArrow();
	    }
	    return _this;
	  }
	  babelHelpers.createClass(Form, [{
	    key: "renderSelector",
	    value: function renderSelector(params) {
	      this.selector = new Selector(this, params);
	      this.selector.render();
	    }
	  }, {
	    key: "getSubSection",
	    value: function getSubSection() {
	      var regex = /analyticsLabel\[source]=(\w*)&/gm;
	      var match = regex.exec(decodeURI(window.location));
	      if ((match === null || match === void 0 ? void 0 : match.length) > 1) {
	        return match[1];
	      }
	      return null;
	    }
	  }, {
	    key: "changeContent",
	    value: function changeContent(action) {
	      this.hideErrorMessage();
	      this.hideSuccessMessage();
	      var section = this.getSubSection();
	      var subSection = "";
	      if (action.length > 0) {
	        if (action === 'active-directory') {
	          if (!this.activeDirectory) {
	            this.activeDirectory = new ActiveDirectory(this);
	          }
	          this.activeDirectory.showForm();
	          this.analytics.sendTabData(section, Analytics.TAB_AD);
	          return;
	        }
	        var projectId = this.userOptions.hasOwnProperty('groupId') ? parseInt(this.userOptions.groupId, 10) : 0;
	        var departmentsId = this.userOptions.hasOwnProperty('departmentsId') && Array.isArray(this.userOptions.departmentsId) ? this.userOptions.departmentsId.map(function (id) {
	          return parseInt(id, 10);
	        }) : [];
	        for (var type in this.contentBlocks) {
	          var block = this.contentBlocks[type];
	          if (type === action) {
	            main_core.Dom.removeClass(block, 'invite-block-hidden');
	            main_core.Dom.addClass(block, 'invite-block-shown');
	            var params = {
	              contentBlock: this.contentBlocks[action]
	            };
	            var row = new Row(this, params);
	            if (action === 'invite') {
	              subSection = Analytics.TAB_EMAIL;
	              row.renderInviteInputs(5);
	            } else if (action === 'invite-with-group-dp') {
	              subSection = Analytics.TAB_DEPARTMENT;
	              row.renderInviteInputs(3);
	              var selectorParams = {
	                contentBlock: this.contentBlocks[action].querySelector("[data-role='entity-selector-container']"),
	                options: {
	                  department: true,
	                  project: true,
	                  projectId: projectId,
	                  departmentsId: departmentsId,
	                  isAdmin: this.isAdmin,
	                  projectLimitExceeded: this.projectLimitExceeded,
	                  projectLimitFeatureId: this.projectLimitFeatureId
	                }
	              };
	              this.renderSelector(selectorParams);
	            } else if (action === 'extranet') {
	              subSection = Analytics.TAB_EXTRANET;
	              row.renderInviteInputs(3);
	              var _selectorParams = {
	                contentBlock: this.contentBlocks[action].querySelector("[data-role='entity-selector-container']"),
	                options: {
	                  department: false,
	                  project: "extranet",
	                  projectId: projectId,
	                  isAdmin: this.isAdmin,
	                  projectLimitExceeded: this.projectLimitExceeded,
	                  projectLimitFeatureId: this.projectLimitFeatureId,
	                  showCreateButton: !this.isCollabEnabled
	                }
	              };
	              this.renderSelector(_selectorParams);
	            } else if (action === "add") {
	              subSection = Analytics.TAB_REGISTRATION;
	              row.renderRegisterInputs();
	              var _selectorParams2 = {
	                contentBlock: this.contentBlocks[action].querySelector("[data-role='entity-selector-container']"),
	                options: {
	                  department: true,
	                  project: true,
	                  projectId: projectId,
	                  isAdmin: this.isAdmin,
	                  projectLimitExceeded: this.projectLimitExceeded,
	                  projectLimitFeatureId: this.projectLimitFeatureId
	                }
	              };
	              this.renderSelector(_selectorParams2);
	            } else if (action === "integrator") {
	              subSection = Analytics.TAB_INTEGRATOR;
	              row.renderIntegratorInput();
	            } else if (action === "self") {
	              subSection = Analytics.TAB_LINK;
	            } else if (action === "mass-invite") {
	              subSection = Analytics.TAB_MASS;
	            }
	          } else {
	            main_core.Dom.removeClass(block, 'invite-block-shown');
	            main_core.Dom.addClass(block, 'invite-block-hidden');
	          }
	        }
	        if (this.analytics && section && subSection) {
	          this.analytics.sendTabData(section, subSection);
	        }
	        this.changeButton(action);
	      }
	    }
	  }, {
	    key: "changeButton",
	    value: function changeButton(action) {
	      var _this2 = this;
	      main_core.Event.unbindAll(this.button, 'click');
	      if (!this.isCreatorEmailConfirmed) {
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.showErrorMessage(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_CREATOR_EMAIL_ERROR'));
	        });
	        return;
	      }
	      this.activateButton();
	      if (action === "invite") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitInvite();
	          }
	        });
	      } else if (action === "mass-invite") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitMassInvite();
	          }
	        });
	      } else if (action === "invite-with-group-dp") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitInviteWithGroupDp();
	          }
	        });
	      } else if (action === "add") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitAdd();
	          }
	        });
	      } else if (action === "self") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');
	        this.disableButton();
	        main_core.Event.bind(this.button, 'click', function () {
	          if (_this2.isButtonActive()) {
	            _this2.submit.submitSelf();
	          }
	        });
	      } else if (action === "integrator") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitIntegrator();
	          }
	        });
	      } else if (action === "extranet") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          if (!_this2.submit.waitingResponse) {
	            _this2.submit.submitExtranet();
	          }
	        });
	      } else if (action === "success") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE_MORE');
	        main_core.Event.bind(this.button, 'click', function () {
	          BX.fireEvent(_this2.menuItems[0], 'click');
	        });
	      }
	    }
	  }, {
	    key: "disableButton",
	    value: function disableButton() {
	      main_core.Dom.addClass(this.button, "ui-btn-disabled");
	    }
	  }, {
	    key: "activateButton",
	    value: function activateButton() {
	      main_core.Dom.removeClass(this.button, "ui-btn-disabled");
	    }
	  }, {
	    key: "isButtonActive",
	    value: function isButtonActive() {
	      return !main_core.Dom.hasClass(this.button, "ui-btn-disabled");
	    }
	  }, {
	    key: "showSuccessMessage",
	    value: function showSuccessMessage(successText) {
	      this.hideErrorMessage();
	      if (main_core.Type.isDomNode(this.successMessageBlock)) {
	        this.successMessageBlock.style.display = "block";
	        var alert = this.successMessageBlock.querySelector(".ui-alert-message");
	        if (main_core.Type.isDomNode(alert)) {
	          alert.innerHTML = BX.util.htmlspecialchars(successText);
	        }
	      }
	    }
	  }, {
	    key: "hideSuccessMessage",
	    value: function hideSuccessMessage() {
	      if (main_core.Type.isDomNode(this.successMessageBlock)) {
	        this.successMessageBlock.style.display = "none";
	      }
	    }
	  }, {
	    key: "showErrorMessage",
	    value: function showErrorMessage(errorText) {
	      this.hideSuccessMessage();
	      if (main_core.Type.isDomNode(this.errorMessageBlock) && errorText) {
	        this.errorMessageBlock.style.display = "block";
	        var alert = this.errorMessageBlock.querySelector(".ui-alert-message");
	        if (main_core.Type.isDomNode(alert)) {
	          alert.innerHTML = BX.util.htmlspecialchars(errorText);
	        }
	      }
	    }
	  }, {
	    key: "hideErrorMessage",
	    value: function hideErrorMessage() {
	      if (main_core.Type.isDomNode(this.errorMessageBlock)) {
	        this.errorMessageBlock.style.display = "none";
	      }
	    }
	  }, {
	    key: "getSetupArrow",
	    value: function getSetupArrow() {
	      this.body = document.querySelector('.invite-body');
	      this.panelConfirmBtn = document.getElementById('intranet-invitation-btn');
	      this.sliderContent = document.querySelector('.ui-page-slider-workarea');
	      this.sliderHeader = document.querySelector('.ui-side-panel-wrap-title-wrap');
	      this.buttonPanel = document.querySelector('.ui-button-panel');
	      this.inviteButton = document.querySelector('.invite-form-buttons');
	      this.sliderHeaderHeight = this.sliderHeader.getBoundingClientRect().height;
	      this.buttonPanelRect = this.buttonPanel.getBoundingClientRect();
	      this.panelRect = this.panelConfirmBtn.getBoundingClientRect();
	      this.btnWidth = Math.ceil(this.panelRect.width);
	      this.arrowWidth = Math.ceil(this.arrowRect.width);
	      this.sliderContentRect = this.sliderContent.getBoundingClientRect();
	      this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
	      this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
	    }
	  }, {
	    key: "updateArrow",
	    value: function updateArrow() {
	      this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
	      this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
	      this.contentHeight > this.bodyHeight ? this.body.classList.add('js-intranet-invitation-arrow-hide') : this.body.classList.remove('js-intranet-invitation-arrow-hide');
	    }
	  }, {
	    key: "setSetupArrow",
	    value: function setSetupArrow() {
	      this.getSetupArrow();
	      var btnPadding = 40;
	      this.arrowBox.style.left = this.panelRect.left + this.btnWidth / 2 - this.arrowWidth / 2 - btnPadding + 'px';
	      this.contentHeight > this.bodyHeight ? this.body.classList.add('js-intranet-invitation-arrow-hide') : this.body.classList.remove('js-intranet-invitation-arrow-hide');
	      window.addEventListener('resize', function () {
	        if (window.innerWidth <= 1100) {
	          this.arrowBox.style.left = this.panelRect.left + this.btnWidth / 2 - this.arrowWidth / 2 - btnPadding + 'px';
	        }
	        this.getSetupArrow();
	        this.updateArrow();
	      }.bind(this));
	      this.inviteButton.addEventListener('click', function () {
	        this.getSetupArrow();
	        this.updateArrow();
	      }.bind(this));
	    }
	  }]);
	  return Form;
	}(main_core_events.EventEmitter);

	exports.Form = Form;

}((this.BX.Intranet.Invitation = this.BX.Intranet.Invitation || {}),BX.Main,BX.UI,BX.UI,BX.UI.EntitySelector,BX.Event,BX,BX.UI.Analytics));
//# sourceMappingURL=script.js.map

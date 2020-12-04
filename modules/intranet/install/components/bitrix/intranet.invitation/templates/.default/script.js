this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var Submit = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Submit, _EventEmitter);

	  function Submit(parent) {
	    var _this;

	    babelHelpers.classCallCheck(this, Submit);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Submit).call(this));
	    _this.parent = parent;

	    _this.setEventNamespace('BX.Intranet.Invitation.Submit');

	    _this.parent.subscribe('onButtonClick', function (event) {});

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
	      var rows = form.querySelectorAll(".js-form-row");
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
	    key: "prepareGroupAndDepartmentData",
	    value: function prepareGroupAndDepartmentData(inputs, form) {
	      var groups = [];
	      var departments = [];

	      function checkValue(element) {
	        var value = element.value;

	        if (value.match(/^SG(\d+)$/)) {
	          groups.push(value);
	        } else if (value.match(/^DR(\d+)$/)) {
	          departments.push(parseInt(value.replace('DR', '')));
	        } else if (value.match(/^D(\d+)$/)) {
	          departments.push(parseInt(value.replace('D', '')));
	        }
	      }

	      for (var i = 0, len = inputs.length; i < len; i++) {
	        if (main_core.Type.isArrayLike(inputs[i])) //check RadioNodeList
	          {
	            inputs[i].forEach(function (element) {
	              checkValue(element);
	            });
	          } else {
	          checkValue(inputs[i]);
	        }
	      }

	      return {
	        groups: groups,
	        departments: departments
	      };
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
	        this.emit('onInputError', event);
	        return;
	      }

	      if (items.length <= 0) {
	        var _event = new main_core.Event.BaseEvent({
	          data: {
	            error: main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")
	          }
	        });

	        this.emit('onInputError', _event);
	        return;
	      }

	      var requestData = {
	        "ITEMS": items
	      };
	      this.sendAction("invite", requestData);
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
	        this.emit('onInputError', event);
	        return;
	      }

	      var requestData = {
	        "ITEMS": items
	      };

	      if (!main_core.Type.isUndefined(inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"])) {
	        var arGroupsAndDepartmentInput;

	        if (typeof inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined') {
	          arGroupsAndDepartmentInput = inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"];
	        } else {
	          arGroupsAndDepartmentInput = [inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"]];
	        }

	        var groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsAndDepartmentInput, inviteWithGroupDpForm);

	        if (main_core.Type.isArray(groupsAndDepartmentId["groups"])) {
	          requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
	        }

	        if (main_core.Type.isArray(groupsAndDepartmentId["departments"])) {
	          requestData["UF_DEPARTMENT"] = groupsAndDepartmentId["departments"];
	        }
	      }

	      this.sendAction("inviteWithGroupDp", requestData);
	    }
	  }, {
	    key: "submitSelf",
	    value: function submitSelf() {
	      var selfForm = this.parent.contentBlocks["self"].querySelector("form");
	      var obRequestData = {
	        "allow_register": selfForm["allow_register"].checked ? "Y" : "N",
	        'allow_register_confirm': selfForm["allow_register_confirm"].checked ? "Y" : "N",
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
	        this.emit('onInputError', event);
	        return;
	      }

	      var requestData = {
	        "ITEMS": items
	      };

	      if (!main_core.Type.isUndefined(extranetForm["GROUP_AND_DEPARTMENT[]"])) {
	        var arGroupsInput;

	        if (typeof extranetForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined') {
	          arGroupsInput = extranetForm["GROUP_AND_DEPARTMENT[]"];
	        } else {
	          arGroupsInput = [extranetForm["GROUP_AND_DEPARTMENT[]"]];
	        }

	        var groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsInput, extranetForm);

	        if (main_core.Type.isArray(groupsAndDepartmentId["groups"])) {
	          requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
	        }
	      }

	      this.sendAction("extranet", requestData);
	    }
	  }, {
	    key: "submitIntegrator",
	    value: function submitIntegrator() {
	      var integratorForm = this.parent.contentBlocks["integrator"].querySelector("form");
	      var obRequestData = {
	        "integrator_email": integratorForm["integrator_email"].value
	      };
	      this.sendAction("inviteIntegrator", obRequestData);
	    }
	  }, {
	    key: "submitMassInvite",
	    value: function submitMassInvite() {
	      var massInviteForm = this.parent.contentBlocks["mass-invite"].querySelector("form");
	      var obRequestData = {
	        "ITEMS": massInviteForm["mass_invite_emails"].value
	      };
	      this.sendAction("massInvite", obRequestData);
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

	      if (!main_core.Type.isUndefined(addForm["GROUP_AND_DEPARTMENT[]"])) {
	        var arGroupsAndDepartmentInput;

	        if (typeof addForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined') {
	          arGroupsAndDepartmentInput = addForm["GROUP_AND_DEPARTMENT[]"];
	        } else {
	          arGroupsAndDepartmentInput = [addForm["GROUP_AND_DEPARTMENT[]"]];
	        }

	        var groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsAndDepartmentInput, addForm);

	        if (main_core.Type.isArray(groupsAndDepartmentId["groups"])) {
	          requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
	        }

	        if (main_core.Type.isArray(groupsAndDepartmentId["departments"])) {
	          requestData["DEPARTMENT_ID"] = groupsAndDepartmentId["departments"];
	        }
	      }

	      this.sendAction("add", requestData);
	    }
	  }, {
	    key: "sendAction",
	    value: function sendAction(action, requestData) {
	      this.disableSubmitButton(true);
	      requestData["userOptions"] = this.parent.userOptions;
	      BX.ajax.runComponentAction(this.parent.componentName, action, {
	        signedParameters: this.parent.signedParameters,
	        mode: 'ajax',
	        data: requestData
	      }).then(function (response) {
	        this.disableSubmitButton(false);

	        if (response.data) {
	          if (action === "self") {
	            this.parent.showSuccessMessage(response.data);
	          } else {
	            this.parent.changeContent("success");
	            this.sendSuccessEvent(response.data);
	          }
	        }
	      }.bind(this), function (response) {
	        this.disableSubmitButton(false);

	        if (response.data == "user_limit") {
	          B24.licenseInfoPopup.show('featureID', BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TITLE"), BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT"));
	        } else {
	          this.parent.showErrorMessage(response.errors[0].message);
	        }
	      }.bind(this));
	    }
	  }, {
	    key: "disableSubmitButton",
	    value: function disableSubmitButton(isDisable) {
	      var button = this.parent.button;

	      if (!main_core.Type.isDomNode(button) || !main_core.Type.isBoolean(isDisable)) {
	        return;
	      }

	      if (isDisable) {
	        main_core.Dom.addClass(button, "ui-btn-wait");
	        button.style.cursor = 'auto';
	      } else {
	        main_core.Dom.removeClass(button, "ui-btn-wait");
	        button.style.cursor = 'pointer';
	      }
	    }
	  }, {
	    key: "sendSuccessEvent",
	    value: function sendSuccessEvent(users) {
	      BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation:onAdd', {
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
	    }
	  }

	  babelHelpers.createClass(SelfRegister, [{
	    key: "bindActions",
	    value: function bindActions() {
	      var _this = this;

	      var regenerateButton = this.selfBlock.querySelector("[data-role='selfRegenerateSecretButton']");

	      if (main_core.Type.isDomNode(regenerateButton)) {
	        main_core.Event.bind(regenerateButton, 'click', BX.delegate(function () {
	          _this.regenerateSecret(_this.parent.regenerateUrlBase);
	        }, this));
	      }

	      var copyRegisterUrlButton = this.selfBlock.querySelector("[data-role='copyRegisterUrlButton']");

	      if (main_core.Type.isDomNode(copyRegisterUrlButton)) {
	        main_core.Event.bind(copyRegisterUrlButton, 'click', BX.delegate(function () {
	          _this.copyRegisterUrl();
	        }, this));
	      }

	      var selfToggleSettingsButton = this.selfBlock.querySelector("[data-role='selfToggleSettingsButton']");

	      if (main_core.Type.isDomNode(selfToggleSettingsButton)) {
	        main_core.Event.bind(selfToggleSettingsButton, 'change', function () {
	          _this.toggleSettings(selfToggleSettingsButton);
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
	    value: function toggleSettings(inputElement) {
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
	        main_core.Dom.style(settingsBlock, 'display', inputElement.checked ? 'block' : 'none');
	      }
	    }
	  }]);
	  return SelfRegister;
	}();

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span style=\"z-index: 3;\" class=\"ui-ctl-before\" data-role=\"phone-block\">\n\t\t\t\t<input type=\"hidden\" name=\"PHONE_COUNTRY[]\" id=\"phone_country_", "\" value=\"\">\n\t\t\t\t<input type=\"hidden\" name=\"PHONE[]\" id=\"phone_number_", "\" value=\"\">\n\t\t\t\t<div class=\"invite-dialog-phone-flag-block\" data-role=\"flag\">\n\t\t\t\t\t<span data-role=\"phone_flag_", "\" style=\"pointer-events: none;\"></span>\n\t\t\t\t</div>\n\t\t\t\t<input class=\"invite-dialog-phone-input\" type=\"hidden\" id=\"phone_input_", "\" value=\"\">&nbsp;\n\t\t\t</span>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
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

	      var element = main_core.Tag.render(_templateObject(), num, num, num, num);
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
	          inputNode.parentNode.querySelector('#phone_number_' + i).value = e.value;
	          inputNode.parentNode.querySelector('#phone_country_' + i).value = e.country;
	        };
	      };

	      this.inputStack[num] = new BX.PhoneNumber.Input({
	        node: inputNode,
	        flagNode: inputNode.parentNode.querySelector("[data-role='phone_flag_" + num + "']"),
	        flagSize: 16,
	        onChange: changeCallback(num, inputNode)
	      }); //for ctrl+v paste

	      setTimeout(function () {
	        if (!inputNode.parentNode.querySelector('#phone_number_' + num).value) {
	          changeCallback(num, inputNode)({
	            value: _this.inputStack[num].getValue(),
	            country: _this.inputStack[num].getCountry()
	          });
	        }
	      }, 100);
	    }
	  }, {
	    key: "showCountrySelector",
	    value: function showCountrySelector(i) {
	      this.inputStack[i]._onFlagClick();
	    }
	  }]);
	  return Phone;
	}();

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input \n\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\tvalue=\"\" \n\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\tname=\"integrator_email\" \n\t\t\t\t\t\t\tid=\"integrator_email\" \n\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_NAME\" id=\"ADD_NAME\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_LAST_NAME\" id=\"ADD_LAST_NAME\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_EMAIL\" id=\"ADD_EMAIL\" class=\"ui-ctl-element\" maxlength=\"50\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<input type=\"text\" name=\"ADD_POSITION\" id=\"ADD_POSITION\" class=\"ui-ctl-element\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t\t</div>\t\t\t\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"invite-form-row js-form-row\">\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input \n\t\t\t\t\t\t\tname=\"EMAIL[]\" \n\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\tdata-num=\"", "\" \n\t\t\t\t\t\t\tclass=\"ui-ctl-element js-email-phone-input\" \n\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input name=\"NAME[]\" type=\"text\" class=\"ui-ctl-element\" placeholder=\"", "\">\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon\">\n\t\t\t\t\t\t<input name=\"LAST_NAME[]\" type=\"text\" class=\"ui-ctl-element\" placeholder=\"", "\">\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" style=\"display: none\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\tname=\"EMAIL[]\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tmaxlength=\"50\"\n\t\t\t\t\t\t\t\tdata-num=\"", "\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element js-email-phone-input\"\n\t\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t\t/>"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
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
	      }
	    }
	  }, {
	    key: "bindPhoneChecker",
	    value: function bindPhoneChecker(element) {
	      var _this2 = this;

	      if (this.parent.isInvitationBySmsAvailable && main_core.Type.isDomNode(element)) {
	        var inputNodes = element.querySelectorAll(".js-email-phone-input");

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
	    key: "bindCloseIcons",
	    value: function bindCloseIcons(container) {
	      var _this3 = this;

	      var inputNodes = container.querySelectorAll("input");
	      (inputNodes || []).forEach(function (node) {
	        var closeIcon = node.nextElementSibling;
	        main_core.Event.bind(node, 'input', function () {
	          main_core.Dom.style(closeIcon, 'display', node.value !== "" ? "block" : "none");
	        });
	        main_core.Event.bind(closeIcon, 'click', function (event) {
	          event.preventDefault();
	          node.value = "";

	          if (main_core.Type.isDomNode(node.parentNode)) {
	            var phoneBlock = node.parentNode.querySelector("[data-role='phone-block']");

	            if (main_core.Type.isDomNode(phoneBlock)) {
	              var newInput = main_core.Tag.render(_templateObject$1(), node.getAttribute('data-num'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT'));
	              main_core.Dom.replace(node, newInput);

	              _this3.bindCloseIcons(newInput.parentNode);

	              _this3.bindPhoneChecker(newInput.parentNode);

	              main_core.Dom.remove(phoneBlock);
	            }
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

	      var element = main_core.Tag.render(_templateObject2(), showTitles ? emailTitle : '', this.inputNum++, main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT'), showTitles ? nameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE'), showTitles ? lastNameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE'));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	      this.bindPhoneChecker(element);
	    }
	  }, {
	    key: "renderRegisterInputs",
	    value: function renderRegisterInputs() {
	      main_core.Dom.clean(this.rowsContainer);
	      var element = main_core.Tag.render(_templateObject3(), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_EMAIL_TITLE"), main_core.Loc.getMessage("BX24_INVITE_DIALOG_ADD_POSITION_TITLE"));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	    }
	  }, {
	    key: "renderIntegratorInput",
	    value: function renderIntegratorInput() {
	      main_core.Dom.clean(this.rowsContainer);
	      var element = main_core.Tag.render(_templateObject4(), main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL"), main_core.Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL"));
	      main_core.Dom.append(element, this.rowsContainer);
	      this.bindCloseIcons(element);
	    }
	  }]);
	  return Row;
	}();

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
	    _this.isInvitationBySmsAvailable = params.isInvitationBySmsAvailable === "Y";
	    _this.isCreatorEmailConfirmed = params.isCreatorEmailConfirmed === "Y";
	    _this.regenerateUrlBase = params.regenerateUrlBase;

	    if (main_core.Type.isDomNode(_this.contentContainer)) {
	      var blocks = _this.contentContainer.querySelectorAll(".js-intranet-invitation-block");

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
	      _this.menuItems = _this.menuContainer.querySelectorAll("a");
	      (_this.menuItems || []).forEach(function (item) {
	        main_core.Event.bind(item, 'click', function () {
	          _this.changeContent(item.getAttribute('data-action'));
	        });
	      });

	      _this.changeContent(_this.menuItems[0].getAttribute('data-action'));
	    }

	    _this.submit = new Submit(babelHelpers.assertThisInitialized(_this));

	    _this.submit.subscribe('onInputError', function (event) {
	      _this.showErrorMessage(event.data.error);
	    });

	    if (_this.isCloud) {
	      _this.selfRegister = new SelfRegister(babelHelpers.assertThisInitialized(_this));
	    }

	    return _this;
	  }

	  babelHelpers.createClass(Form, [{
	    key: "changeContent",
	    value: function changeContent(action) {
	      this.hideErrorMessage();
	      this.hideSuccessMessage();

	      if (action.length > 0) {
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
	              row.renderInviteInputs(5);
	            } else if (action === 'invite-with-group-dp') {
	              row.renderInviteInputs(3);
	            } else if (action === 'extranet') {
	              row.renderInviteInputs(3);
	            } else if (action === "add") {
	              row.renderRegisterInputs();
	            } else if (action === "integrator") {
	              row.renderIntegratorInput();
	            }
	          } else {
	            main_core.Dom.removeClass(block, 'invite-block-shown');
	            main_core.Dom.addClass(block, 'invite-block-hidden');
	          }
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

	      if (action === "invite") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitInvite();
	        });
	      } else if (action === "mass-invite") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitMassInvite();
	        });
	      } else if (action === "invite-with-group-dp") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitInviteWithGroupDp();
	        });
	      } else if (action === "add") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitAdd();
	        });
	      } else if (action === "self") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitSelf();
	        });
	      } else if (action === "integrator") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitIntegrator();
	        });
	      } else if (action === "extranet") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	        main_core.Event.bind(this.button, 'click', function () {
	          _this2.submit.submitExtranet();
	        });
	      } else if (action === "success") {
	        this.button.innerText = main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE_MORE');
	        main_core.Event.bind(this.button, 'click', function () {
	          BX.fireEvent(_this2.menuItems[0], 'click');
	        });
	      }
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
	  }]);
	  return Form;
	}(main_core_events.EventEmitter);

	exports.Form = Form;

}((this.BX.Intranet.Invitation = this.BX.Intranet.Invitation || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map

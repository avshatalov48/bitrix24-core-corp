/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
this.BX.Intranet.Invitation = this.BX.Intranet.Invitation || {};
(function (exports,ui_buttons,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	var Row = /*#__PURE__*/function () {
	  function Row(rowOptions) {
	    babelHelpers.classCallCheck(this, Row);
	    babelHelpers.defineProperty(this, "email", null);
	    babelHelpers.defineProperty(this, "name", null);
	    babelHelpers.defineProperty(this, "lastName", null);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    var options = main_core.Type.isPlainObject(rowOptions) ? rowOptions : {};
	    if (main_core.Type.isStringFilled(options.email)) {
	      this.getEmailTextBox().value = options.email;
	    }
	    if (main_core.Type.isStringFilled(options.name)) {
	      this.getNameTextBox().value = options.name;
	    }
	    if (main_core.Type.isStringFilled(options.lastName)) {
	      this.getLastNameTextBox().value = options.lastName;
	    }
	  }
	  babelHelpers.createClass(Row, [{
	    key: "isEmpty",
	    value: function isEmpty() {
	      var email = this.getEmailTextBox().value.trim();
	      return !main_core.Type.isStringFilled(email);
	    }
	  }, {
	    key: "validate",
	    value: function validate() {
	      var email = this.getEmail();
	      var name = this.getName();
	      var lastName = this.getLastName();
	      if (main_core.Type.isStringFilled(email)) {
	        var atom = '=_0-9a-z+~\'!\$&*^`|\\#%/?{}-';
	        var regExp = new RegExp('^[' + atom + ']+(\\.[' + atom + ']+)*@(([-0-9a-z]+\\.)+)([a-z0-9-]{2,20})$', 'i');
	        if (!email.match(regExp)) {
	          main_core.Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	          return false;
	        }
	      } else if (main_core.Type.isStringFilled(name) || main_core.Type.isStringFilled(lastName)) {
	        main_core.Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	        return false;
	      }
	      main_core.Dom.removeClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	      return true;
	    }
	  }, {
	    key: "focus",
	    value: function focus() {
	      this.getEmailTextBox().focus();
	    }
	  }, {
	    key: "getEmail",
	    value: function getEmail() {
	      return this.getEmailTextBox().value.trim();
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.getNameTextBox().value.trim();
	    }
	  }, {
	    key: "getLastName",
	    value: function getLastName() {
	      return this.getLastNameTextBox().value.trim();
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;
	      return this.cache.remember('container', function () {
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"invite-form-row\">\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"invite-form-col\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_EMAIL'), _this.getEmailTextBox(), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_NAME'), _this.getNameTextBox(), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_LAST_NAME'), _this.getLastNameTextBox());
	      });
	    }
	  }, {
	    key: "getEmailTextBox",
	    value: function getEmailTextBox() {
	      return this.cache.remember('email', function () {
	        return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input \n\t\t\t\t\ttype=\"email\"\n\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_ENTER_EMAIL'));
	      });
	    }
	  }, {
	    key: "getNameTextBox",
	    value: function getNameTextBox() {
	      return this.cache.remember('name', function () {
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input \n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "getLastNameTextBox",
	    value: function getLastNameTextBox() {
	      return this.cache.remember('last-name', function () {
	        return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input \n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t>\n\t\t\t"])));
	      });
	    }
	  }]);
	  return Row;
	}();

	var _templateObject$1, _templateObject2$1, _templateObject3$1;
	var Form = /*#__PURE__*/function () {
	  function Form(formOptions) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Form);
	    babelHelpers.defineProperty(this, "targetNode", null);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "saveButton", null);
	    babelHelpers.defineProperty(this, "cancelButton", null);
	    babelHelpers.defineProperty(this, "rows", []);
	    babelHelpers.defineProperty(this, "error", null);
	    babelHelpers.defineProperty(this, "userOptions", {});
	    var options = main_core.Type.isPlainObject(formOptions) ? formOptions : {};
	    this.targetNode = options.targetNode;
	    this.userOptions = main_core.Type.isPlainObject(options.userOptions) ? options.userOptions : {};
	    main_core.Dom.append(this.getContainer(), this.targetNode);
	    if (main_core.Type.isElementNode(options.saveButtonNode)) {
	      this.saveButton = ui_buttons.ButtonManager.createFromNode(options.saveButtonNode);
	      this.saveButton.bindEvent('click', this.handleSaveButtonClick.bind(this));
	    }
	    if (main_core.Type.isElementNode(options.cancelButtonNode)) {
	      this.cancelButton = ui_buttons.ButtonManager.createFromNode(options.cancelButtonNode);
	      this.cancelButton.bindEvent('click', this.handleCancelButtonClick.bind(this));
	    }
	    if (main_core.Type.isArrayFilled(options.rows)) {
	      options.rows.forEach(function (row) {
	        _this.addRow(row);
	      });
	      this.addRows(Math.max(2, 5 - options.rows.length));
	      this.getRows()[0].focus();
	    } else {
	      this.addRows();
	    }
	    main_core.Runtime.loadExtension('ui.hint').then(function () {
	      var hint = BX.UI.Hint.createInstance();
	      var node = hint.createNode(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_HINT'));
	      var title = document.querySelector('#pagetitle') || _this.getTitleContainer();
	      main_core.Dom.append(node, title);
	    });
	  }
	  babelHelpers.createClass(Form, [{
	    key: "getRows",
	    value: function getRows() {
	      return this.rows;
	    }
	  }, {
	    key: "lock",
	    value: function lock() {
	      main_core.Dom.style(this.getContainer(), 'pointer-events', 'none');
	    }
	  }, {
	    key: "unlock",
	    value: function unlock() {
	      main_core.Dom.style(this.getContainer(), 'pointer-events', 'none');
	    }
	  }, {
	    key: "submit",
	    value: function submit() {
	      var _this2 = this;
	      var valid = true;
	      var guests = [];
	      var invalidRow = null;
	      this.getRows().forEach(function (row) {
	        if (!row.validate()) {
	          invalidRow = invalidRow || row;
	          valid = false;
	        }
	        if (valid && !row.isEmpty()) {
	          guests.push({
	            email: row.getEmail(),
	            name: row.getName(),
	            lastName: row.getLastName()
	          });
	        }
	      });
	      if (!valid) {
	        return Promise.reject(new main_core.BaseError(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_WRONG_DATA'), 'wrong_data', {
	          invalidRow: invalidRow
	        }));
	      } else if (!main_core.Type.isArrayFilled(guests)) {
	        return Promise.reject(new main_core.BaseError(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_EMPTY_DATA'), 'empty_data', {
	          invalidRow: this.getRows()[0]
	        }));
	      }
	      return new Promise(function (resolve, reject) {
	        return main_core.ajax.runComponentAction('bitrix:intranet.invitation.guest', 'addGuests', {
	          mode: 'class',
	          json: {
	            guests: guests,
	            userOptions: _this2.userOptions
	          }
	        }).then(function (response) {
	          resolve(response);
	        }, function (reason) {
	          var error = reason && main_core.Type.isArrayFilled(reason.errors) ? reason.errors.map(function (error) {
	            return main_core.Text.encode(error.message);
	          }).join('<br><br>') : 'Server Response Error';
	          reject(new main_core.BaseError(error, 'wrong_response'));
	        });
	      });
	    }
	  }, {
	    key: "getSaveButton",
	    value: function getSaveButton() {
	      return this.saveButton;
	    }
	  }, {
	    key: "getCancelButton",
	    value: function getCancelButton() {
	      return this.cancelButton;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this3 = this;
	      return this.cache.remember('container', function () {
	        return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"invite-wrap\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"invite-content-container\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"invite-form-buttons\">\n\t\t\t\t\t\t<button \n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round\"\n\t\t\t\t\t\t\tonclick=\"", "\">", "\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this3.getTitleContainer(), _this3.getRowsContainer(), _this3.handleAddMoreClick.bind(_this3), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_ADD_MORE'));
	      });
	    }
	  }, {
	    key: "getRowsContainer",
	    value: function getRowsContainer() {
	      return this.cache.remember('rows-container', function () {
	        return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"invite-form-container\"></div>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "getTitleContainer",
	    value: function getTitleContainer() {
	      return this.cache.remember('title-container', function () {
	        return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"invite-title-container\">\n\t\t\t\t\t<div class=\"invite-title-icon invite-title-icon-message\"></div>\n\t\t\t\t\t<div class=\"invite-title-text\">", "</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_TITLE'));
	      });
	    }
	  }, {
	    key: "addRow",
	    value: function addRow(rowOptions) {
	      var row = new Row(rowOptions);
	      this.rows.push(row);
	      main_core.Dom.append(row.getContainer(), this.getRowsContainer());
	      return row;
	    }
	  }, {
	    key: "addRows",
	    value: function addRows() {
	      var _this4 = this;
	      var numberOfRows = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 5;
	      Array(numberOfRows).fill().forEach(function (el, index) {
	        var row = _this4.addRow();
	        if (index === 0) {
	          row.focus();
	        }
	      });
	    }
	  }, {
	    key: "removeRows",
	    value: function removeRows() {
	      this.getRows().forEach(function (row) {
	        main_core.Dom.remove(row.getContainer());
	      });
	      this.rows = [];
	    }
	  }, {
	    key: "showError",
	    value: function showError(reason) {
	      var animate = this.error === null;
	      this.hideError();
	      this.error = new BX.UI.Alert({
	        color: BX.UI.Alert.Color.DANGER,
	        animated: animate,
	        text: reason
	      });
	      main_core.Dom.prepend(this.error.getContainer(), this.getContainer());
	    }
	  }, {
	    key: "hideError",
	    value: function hideError() {
	      if (this.error !== null) {
	        main_core.Dom.remove(this.error.container);
	        this.error = null;
	      }
	    }
	  }, {
	    key: "handleSaveButtonClick",
	    value: function handleSaveButtonClick() {
	      var _this5 = this;
	      if (this.getSaveButton().isWaiting()) {
	        return;
	      }
	      this.getSaveButton().setWaiting();
	      this.submit().then(function (response) {
	        _this5.getSaveButton().setWaiting(false);
	        _this5.hideError();
	        _this5.removeRows();
	        _this5.addRows();
	        BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation.Guest:onAdd', response.data);
	        BX.SidePanel.Instance.close();
	      })["catch"](function (error) {
	        _this5.getSaveButton().setWaiting(false);
	        _this5.showError(error.getMessage());
	        if (error.getCustomData() && error.getCustomData()['invalidRow']) {
	          error.getCustomData()['invalidRow'].focus();
	        }
	      });
	    }
	  }, {
	    key: "handleCancelButtonClick",
	    value: function handleCancelButtonClick() {
	      BX.SidePanel.Instance.close();
	    }
	  }, {
	    key: "handleAddMoreClick",
	    value: function handleAddMoreClick() {
	      var row = this.addRow();
	      row.focus();
	    }
	  }]);
	  return Form;
	}();

	exports.Form = Form;

}((this.BX.Intranet.Invitation.Guest = this.BX.Intranet.Invitation.Guest || {}),BX.UI,BX));
//# sourceMappingURL=script.js.map

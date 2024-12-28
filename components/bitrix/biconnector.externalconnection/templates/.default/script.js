/* eslint-disable */
(function (exports,main_core,main_core_events,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _node = /*#__PURE__*/new WeakMap();
	var _props = /*#__PURE__*/new WeakMap();
	var _checkConnectButton = /*#__PURE__*/new WeakMap();
	var _connectionStatusNode = /*#__PURE__*/new WeakMap();
	var _initForm = /*#__PURE__*/new WeakSet();
	var _initHint = /*#__PURE__*/new WeakSet();
	var _initFields = /*#__PURE__*/new WeakSet();
	var _onChangeType = /*#__PURE__*/new WeakSet();
	var _initCheckConnectButton = /*#__PURE__*/new WeakSet();
	var _initConnectionStatusBlock = /*#__PURE__*/new WeakSet();
	var _clearConnectionStatus = /*#__PURE__*/new WeakSet();
	var _updateConnectionStatus = /*#__PURE__*/new WeakSet();
	var _getConnectionValues = /*#__PURE__*/new WeakSet();
	var _onCheckConnectClick = /*#__PURE__*/new WeakSet();
	var ExternalConnectionForm = /*#__PURE__*/function () {
	  function ExternalConnectionForm(props) {
	    babelHelpers.classCallCheck(this, ExternalConnectionForm);
	    _classPrivateMethodInitSpec(this, _onCheckConnectClick);
	    _classPrivateMethodInitSpec(this, _getConnectionValues);
	    _classPrivateMethodInitSpec(this, _updateConnectionStatus);
	    _classPrivateMethodInitSpec(this, _clearConnectionStatus);
	    _classPrivateMethodInitSpec(this, _initConnectionStatusBlock);
	    _classPrivateMethodInitSpec(this, _initCheckConnectButton);
	    _classPrivateMethodInitSpec(this, _onChangeType);
	    _classPrivateMethodInitSpec(this, _initFields);
	    _classPrivateMethodInitSpec(this, _initHint);
	    _classPrivateMethodInitSpec(this, _initForm);
	    _classPrivateFieldInitSpec(this, _node, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _props, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _checkConnectButton, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _connectionStatusNode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _props, props);
	    _classPrivateMethodGet(this, _initForm, _initForm2).call(this);
	  }
	  babelHelpers.createClass(ExternalConnectionForm, [{
	    key: "onClickSave",
	    value: function onClickSave() {
	      var saveButton = ui_buttons.ButtonManager.createFromNode(document.querySelector('#connection-button-save'));
	      saveButton.setWaiting(true);
	      var connectionValues = _classPrivateMethodGet(this, _getConnectionValues, _getConnectionValues2).call(this);
	      if (babelHelpers.classPrivateFieldGet(this, _props).sourceFields.id) {
	        connectionValues.id = babelHelpers.classPrivateFieldGet(this, _props).sourceFields.id;
	      }
	      _classPrivateMethodGet(this, _onCheckConnectClick, _onCheckConnectClick2).call(this).then(function () {
	        return main_core.ajax.runAction('biconnector.externalsource.source.save', {
	          data: {
	            data: connectionValues
	          }
	        });
	      }).then(function (response) {
	        BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnection:onConnectionCreated', {
	          connection: response.data.connection
	        });
	        BX.SidePanel.Instance.getTopSlider().close();
	      })["catch"](function (response) {
	        var _response$errors;
	        saveButton.setWaiting(false);
	        if (((_response$errors = response.errors) === null || _response$errors === void 0 ? void 0 : _response$errors.length) > 0) {
	          BX.UI.Notification.Center.notify({
	            content: response.errors[0].message
	          });
	        } else {
	          console.error(response);
	        }
	        BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnection:onConnectionCreationError');
	      });
	    }
	  }]);
	  return ExternalConnectionForm;
	}();
	function _initForm2() {
	  babelHelpers.classPrivateFieldSet(this, _node, document.querySelector('#connection-form'));
	  _classPrivateMethodGet(this, _initHint, _initHint2).call(this);
	  var fieldsNode = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"fields-wrapper\"></div>\n\t\t"])));
	  main_core.Dom.append(fieldsNode, babelHelpers.classPrivateFieldGet(this, _node));
	  _classPrivateMethodGet(this, _initFields, _initFields2).call(this);
	  var buttonBlock = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"db-connection-button-block\">\n\t\t\t\t<div class=\"db-connection-button\"></div>\n\t\t\t\t<div class=\"db-connection-status\"></div>\n\t\t\t</div>\n\t\t"])));
	  main_core.Dom.append(buttonBlock, babelHelpers.classPrivateFieldGet(this, _node));
	  _classPrivateMethodGet(this, _initCheckConnectButton, _initCheckConnectButton2).call(this);
	  _classPrivateMethodGet(this, _initConnectionStatusBlock, _initConnectionStatusBlock2).call(this);
	}
	function _initHint2() {
	  var hint = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"db-connection-hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_HINT', {
	    '[link]': '<a class="ui-link" onclick="top.BX.Helper.show(`redirect=detail&code=23508958`)">',
	    '[/link]': '</a>'
	  }));
	  main_core.Dom.append(hint, babelHelpers.classPrivateFieldGet(this, _node));
	}
	function _initFields2() {
	  var _babelHelpers$classPr,
	    _sourceFields$title,
	    _sourceFields$type,
	    _this = this;
	  var fieldsNode = babelHelpers.classPrivateFieldGet(this, _node).querySelector('.fields-wrapper');
	  var sourceFields = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _props).sourceFields) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : {};
	  var fields = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"form-fields\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100\">\n\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t<select class=\"ui-ctl-element\" data-code=\"type\"></select>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\tplaceholder=\"", "\" \n\t\t\t\t\t\t\t\tdata-code=\"title\"\n\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_TYPE'), main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_NAME'), main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_NAME_PLACEHOLDER'), (_sourceFields$title = sourceFields.title) !== null && _sourceFields$title !== void 0 ? _sourceFields$title : '');
	  main_core.Dom.append(fields, fieldsNode);
	  var typeSelector = fieldsNode.querySelector('[data-code="type"]');
	  babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases.forEach(function (database) {
	    main_core.Dom.append(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<option \n\t\t\t\t\t\tvalue=\"", "\" \n\t\t\t\t\t\t", "\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</option>\n\t\t\t\t"])), database.code, sourceFields.type === database.code ? 'selected' : '', database.name), typeSelector);
	  });
	  main_core.Event.bind(typeSelector, 'input', _classPrivateMethodGet(this, _onChangeType, _onChangeType2).bind(this));
	  if (sourceFields.id) {
	    var fieldId = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input hidden value=\"", "\" data-code=\"id\">\n\t\t\t"])), sourceFields.id);
	    main_core.Dom.append(fieldId, fields);
	    main_core.Dom.attr(typeSelector, 'disabled', true);
	  }
	  var fieldConfig = babelHelpers.classPrivateFieldGet(this, _props).fieldsConfig;
	  var connectionType = (_sourceFields$type = sourceFields.type) !== null && _sourceFields$type !== void 0 ? _sourceFields$type : babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0].code;
	  fieldConfig[connectionType].forEach(function (field) {
	    var _sourceFields$field$c;
	    var fieldType = field.type;
	    if (field.code === 'password') {
	      fieldType = 'password';
	    }
	    var fieldNode = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\ttype=\"", "\" \n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\tdata-code=\"", "\"\n\t\t\t\t\t\t\t\tplaceholder=\"", "\" \n\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), field.name, fieldType, field.code, field.placeholder, (_sourceFields$field$c = sourceFields[field.code]) !== null && _sourceFields$field$c !== void 0 ? _sourceFields$field$c : '');
	    main_core.Dom.append(fieldNode, fields);
	    main_core.Event.bind(fieldNode, 'input', function () {
	      return _classPrivateMethodGet(_this, _clearConnectionStatus, _clearConnectionStatus2).call(_this);
	    });
	  });
	}
	function _onChangeType2(event) {
	  babelHelpers.classPrivateFieldGet(this, _props).sourceFields.type = event.target.value;
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _node).querySelector('.fields-wrapper'));
	  _classPrivateMethodGet(this, _initFields, _initFields2).call(this);
	  _classPrivateMethodGet(this, _clearConnectionStatus, _clearConnectionStatus2).call(this);
	}
	function _initCheckConnectButton2() {
	  var _this2 = this;
	  var connectButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('EXTERNAL_CONNECTION_CHECK_BUTTON'),
	    color: ui_buttons.ButtonColor.PRIMARY,
	    onclick: function onclick(button, event) {
	      event.preventDefault();
	      _classPrivateMethodGet(_this2, _onCheckConnectClick, _onCheckConnectClick2).call(_this2)["catch"](function () {});
	    },
	    noCaps: true
	  });
	  connectButton.renderTo(babelHelpers.classPrivateFieldGet(this, _node).querySelector('.db-connection-button'));
	  babelHelpers.classPrivateFieldSet(this, _checkConnectButton, connectButton);
	}
	function _initConnectionStatusBlock2() {
	  babelHelpers.classPrivateFieldSet(this, _connectionStatusNode, babelHelpers.classPrivateFieldGet(this, _node).querySelector('.db-connection-status'));
	}
	function _clearConnectionStatus2() {
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _connectionStatusNode));
	}
	function _updateConnectionStatus2(succedeed, errorMessage) {
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _connectionStatusNode));
	  var status = null;
	  if (succedeed) {
	    status = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"db-connection-success\">\n\t\t\t\t\t<div class=\"ui-icon-set --check\" style=\"--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: var(--ui-color-palette-green-50);\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_CHECK_SUCCESS'));
	  } else {
	    status = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"db-connection-error\">\n\t\t\t\t\t<div class=\"ui-icon-set --warning\" style=\"--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: var(--ui-color-palette-red-60);\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), errorMessage.replaceAll(/\s+/g, ' '));
	  }
	  main_core.Dom.append(status, babelHelpers.classPrivateFieldGet(this, _connectionStatusNode));
	}
	function _getConnectionValues2() {
	  var result = {};
	  babelHelpers.classPrivateFieldGet(this, _node).querySelectorAll('[data-code]').forEach(function (field) {
	    result[field.getAttribute('data-code')] = field.value;
	  });
	  return result;
	}
	function _onCheckConnectClick2() {
	  var _this3 = this;
	  babelHelpers.classPrivateFieldGet(this, _checkConnectButton).setState(ui_buttons.ButtonState.WAITING);
	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runComponentAction('bitrix:biconnector.externalconnection', 'checkConnection', {
	      mode: 'class',
	      signedParameters: babelHelpers.classPrivateFieldGet(_this3, _props).signedParameters,
	      data: {
	        data: _classPrivateMethodGet(_this3, _getConnectionValues, _getConnectionValues2).call(_this3)
	      }
	    }).then(function (response) {
	      _classPrivateMethodGet(_this3, _updateConnectionStatus, _updateConnectionStatus2).call(_this3, true);
	      babelHelpers.classPrivateFieldGet(_this3, _checkConnectButton).setState(null);
	      resolve(response);
	    })["catch"](function (response) {
	      _classPrivateMethodGet(_this3, _updateConnectionStatus, _updateConnectionStatus2).call(_this3, false, response.errors[0].message);
	      babelHelpers.classPrivateFieldGet(_this3, _checkConnectButton).setState(null);
	      reject();
	    });
	  });
	}
	main_core.Reflection.namespace('BX.BIConnector').ExternalConnectionForm = ExternalConnectionForm;

}((this.window = this.window || {}),BX,BX.Event,BX.UI));
//# sourceMappingURL=script.js.map

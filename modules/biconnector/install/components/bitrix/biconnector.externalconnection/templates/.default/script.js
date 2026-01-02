/* eslint-disable */
(function (exports,main_core,main_core_events,main_popup,ui_buttons,ui_entitySelector,biconnector_datasetImport) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11;
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
	var _showSaveSuccessPopup = /*#__PURE__*/new WeakSet();
	var _closeSlider = /*#__PURE__*/new WeakSet();
	var ExternalConnectionForm = /*#__PURE__*/function () {
	  function ExternalConnectionForm(props) {
	    babelHelpers.classCallCheck(this, ExternalConnectionForm);
	    _classPrivateMethodInitSpec(this, _closeSlider);
	    _classPrivateMethodInitSpec(this, _showSaveSuccessPopup);
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
	      var _this = this;
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
	        BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnection:onConnectionSave', {
	          connection: response.data.connection
	        });
	        if (babelHelpers.classPrivateFieldGet(_this, _props).closeAfterCreate) {
	          _classPrivateMethodGet(_this, _closeSlider, _closeSlider2).call(_this);
	        } else {
	          _classPrivateMethodGet(_this, _showSaveSuccessPopup, _showSaveSuccessPopup2).call(_this, response.data.connection, response.data.supersetIsReady);
	          saveButton.setWaiting(false);
	        }
	      })["catch"](function (response) {
	        var _response$errors;
	        saveButton.setWaiting(false);
	        if ((response === null || response === void 0 ? void 0 : (_response$errors = response.errors) === null || _response$errors === void 0 ? void 0 : _response$errors.length) > 0) {
	          BX.UI.Notification.Center.notify({
	            content: response.errors[0].message
	          });
	        }
	        BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnection:onConnectionCreationError');
	      });
	    }
	  }]);
	  return ExternalConnectionForm;
	}();
	function _initForm2() {
	  babelHelpers.classPrivateFieldSet(this, _node, document.querySelector('#connection-form'));
	  var hintNode = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"hint-wrapper\"></div>\n\t\t"])));
	  main_core.Dom.append(hintNode, babelHelpers.classPrivateFieldGet(this, _node));
	  _classPrivateMethodGet(this, _initHint, _initHint2).call(this);
	  var fieldsNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"fields-wrapper\"></div>\n\t\t"])));
	  main_core.Dom.append(fieldsNode, babelHelpers.classPrivateFieldGet(this, _node));
	  _classPrivateMethodGet(this, _initFields, _initFields2).call(this);
	  var buttonBlock = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"db-connection-button-block\">\n\t\t\t\t<div class=\"db-connection-button\"></div>\n\t\t\t\t<div class=\"db-connection-status\"></div>\n\t\t\t</div>\n\t\t"])));
	  main_core.Dom.append(buttonBlock, babelHelpers.classPrivateFieldGet(this, _node));
	  _classPrivateMethodGet(this, _initCheckConnectButton, _initCheckConnectButton2).call(this);
	  _classPrivateMethodGet(this, _initConnectionStatusBlock, _initConnectionStatusBlock2).call(this);
	}
	function _initHint2() {
	  var _articleCodes$babelHe, _babelHelpers$classPr;
	  var node = babelHelpers.classPrivateFieldGet(this, _node).querySelector('.hint-wrapper');
	  var articleCodes = {
	    '1c': '23508958',
	    rest: '24486426'
	  };
	  var articleCode = (_articleCodes$babelHe = articleCodes[(_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _props).sourceFields) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.type]) !== null && _articleCodes$babelHe !== void 0 ? _articleCodes$babelHe : articleCodes['1c'];
	  var link = "redirect=detail&code=".concat(articleCode);
	  var hint = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"db-connection-hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_HINT', {
	    '[link]': "<a class=\"ui-link\" onclick=\"top.BX.Helper.show(`".concat(link, "`)\">"),
	    '[/link]': '</a>'
	  }));
	  main_core.Dom.append(hint, node);
	}
	function _initFields2() {
	  var _babelHelpers$classPr2,
	    _babelHelpers$classPr3,
	    _babelHelpers$classPr4,
	    _babelHelpers$classPr5,
	    _babelHelpers$classPr6,
	    _sourceFields$title,
	    _this2 = this,
	    _sourceFields$code;
	  var fieldsNode = babelHelpers.classPrivateFieldGet(this, _node).querySelector('.fields-wrapper');
	  var sourceFields = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _props).sourceFields) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : {};
	  var fields = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"form-fields\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100\">\n\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t<div class=\"ui-ctl-element\"  id=\"connection-type-button\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<input \n\t\t\t\t\t\t\ttype=\"hidden\" \n\t\t\t\t\t\t\tid=\"connection-type-code\"\n\t\t\t\t\t\t\tdata-code=\"code\"\n\t\t\t\t\t\t\tvalue=\"", "\">\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\tplaceholder=\"", "\" \n\t\t\t\t\t\t\t\tdata-code=\"title\"\n\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_TYPE'), sourceFields.code ? main_core.Text.encode((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases.find(function (db) {
	    return db.code === sourceFields.code;
	  })) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.name) : main_core.Text.encode((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0]) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.name), sourceFields.code ? (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases.find(function (db) {
	    return db.code === sourceFields.code;
	  })) === null || _babelHelpers$classPr5 === void 0 ? void 0 : _babelHelpers$classPr5.code : (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0]) === null || _babelHelpers$classPr6 === void 0 ? void 0 : _babelHelpers$classPr6.code, main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_NAME'), main_core.Loc.getMessage('EXTERNAL_CONNECTION_FIELD_NAME_PLACEHOLDER'), (_sourceFields$title = sourceFields.title) !== null && _sourceFields$title !== void 0 ? _sourceFields$title : '');
	  main_core.Dom.append(fields, fieldsNode);
	  var button = document.getElementById('connection-type-button');
	  var dialog = new ui_entitySelector.Dialog({
	    targetNode: button,
	    width: 465,
	    height: 400,
	    autoHide: true,
	    multiple: false,
	    showAvatars: false,
	    compactView: true,
	    dropdownMode: true,
	    enableSearch: true,
	    items: babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases.map(function (database) {
	      return {
	        id: database.code,
	        entityId: 'biconnector-external-connection',
	        title: database.name,
	        tabs: 'connections'
	      };
	    }),
	    events: {
	      'Item:onSelect': function ItemOnSelect(event) {
	        var item = event.getData().item;
	        var selectedDatabaseCode = item.getId();
	        var selectedDatabaseName = item.getTitle();
	        button.textContent = main_core.Text.encode(selectedDatabaseName);
	        _classPrivateMethodGet(_this2, _onChangeType, _onChangeType2).call(_this2, {
	          target: {
	            value: selectedDatabaseCode
	          }
	        });
	      }
	    },
	    entities: [{
	      id: 'biconnector-external-connection'
	    }],
	    tabs: [{
	      id: 'connections',
	      showInList: true
	    }]
	  });
	  if (sourceFields.id) {
	    main_core.Dom.attr(button, 'disabled', true);
	  } else {
	    main_core.Event.bind(button, 'click', function () {
	      dialog.show();
	    });
	  }
	  var fieldConfig = babelHelpers.classPrivateFieldGet(this, _props).fieldsConfig;
	  var connectionType = (_sourceFields$code = sourceFields.code) !== null && _sourceFields$code !== void 0 ? _sourceFields$code : babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0].code;
	  fieldConfig[connectionType].forEach(function (field) {
	    var _sourceFields$field$c;
	    var fieldType = field.type;
	    if (field.code === 'password') {
	      fieldType = 'password';
	    }
	    var fieldNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\ttype=\"", "\" \n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\" \n\t\t\t\t\t\t\t\tdata-code=\"", "\"\n\t\t\t\t\t\t\t\tplaceholder=\"", "\" \n\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Text.encode(field.name), fieldType, field.code, field.placeholder, (_sourceFields$field$c = sourceFields[field.code]) !== null && _sourceFields$field$c !== void 0 ? _sourceFields$field$c : '');
	    main_core.Dom.append(fieldNode, fields);
	    main_core.Event.bind(fieldNode, 'input', function () {
	      return _classPrivateMethodGet(_this2, _clearConnectionStatus, _clearConnectionStatus2).call(_this2);
	    });
	  });
	}
	function _onChangeType2(event) {
	  var _connector$type;
	  var value = event.target.value;
	  var connector = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases.find(function (database) {
	    return database.code === value;
	  });
	  babelHelpers.classPrivateFieldGet(this, _props).sourceFields.code = value;
	  babelHelpers.classPrivateFieldGet(this, _props).sourceFields.type = (_connector$type = connector.type) !== null && _connector$type !== void 0 ? _connector$type : null;
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _node).querySelector('.hint-wrapper'));
	  _classPrivateMethodGet(this, _initHint, _initHint2).call(this);
	  main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _node).querySelector('.fields-wrapper'));
	  _classPrivateMethodGet(this, _initFields, _initFields2).call(this);
	  _classPrivateMethodGet(this, _clearConnectionStatus, _clearConnectionStatus2).call(this);
	}
	function _initCheckConnectButton2() {
	  var _this3 = this;
	  var connectButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('EXTERNAL_CONNECTION_CHECK_BUTTON'),
	    color: ui_buttons.ButtonColor.PRIMARY,
	    onclick: function onclick(button, event) {
	      event.preventDefault();
	      _classPrivateMethodGet(_this3, _onCheckConnectClick, _onCheckConnectClick2).call(_this3)["catch"](function () {});
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
	    status = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"db-connection-success\">\n\t\t\t\t\t<div class=\"ui-icon-set --check\" style=\"--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: var(--ui-color-palette-green-50);\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_CHECK_SUCCESS'));
	  } else {
	    status = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"db-connection-error\">\n\t\t\t\t\t<div class=\"ui-icon-set --warning\" style=\"--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: var(--ui-color-palette-red-60);\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), errorMessage.replaceAll(/\s+/g, ' '));
	  }
	  main_core.Dom.append(status, babelHelpers.classPrivateFieldGet(this, _connectionStatusNode));
	}
	function _getConnectionValues2() {
	  var _ref, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10;
	  var result = {};
	  babelHelpers.classPrivateFieldGet(this, _node).querySelectorAll('[data-code]').forEach(function (field) {
	    result[field.getAttribute('data-code')] = field.value;
	  });
	  var type = (_ref = (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldGet(this, _props)) === null || _babelHelpers$classPr8 === void 0 ? void 0 : (_babelHelpers$classPr9 = _babelHelpers$classPr8.sourceFields) === null || _babelHelpers$classPr9 === void 0 ? void 0 : _babelHelpers$classPr9.type) !== null && _babelHelpers$classPr7 !== void 0 ? _babelHelpers$classPr7 : (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0]) === null || _babelHelpers$classPr10 === void 0 ? void 0 : _babelHelpers$classPr10.type) !== null && _ref !== void 0 ? _ref : null;
	  if (type) {
	    result.type = type;
	  }
	  return result;
	}
	function _onCheckConnectClick2() {
	  var _this4 = this;
	  babelHelpers.classPrivateFieldGet(this, _checkConnectButton).setState(ui_buttons.ButtonState.WAITING);
	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runAction('biconnector.externalsource.source.checkConnectionByData', {
	      data: {
	        data: _classPrivateMethodGet(_this4, _getConnectionValues, _getConnectionValues2).call(_this4)
	      }
	    }).then(function (response) {
	      _classPrivateMethodGet(_this4, _updateConnectionStatus, _updateConnectionStatus2).call(_this4, true);
	      babelHelpers.classPrivateFieldGet(_this4, _checkConnectButton).setState(null);
	      resolve(response);
	    })["catch"](function (response) {
	      _classPrivateMethodGet(_this4, _updateConnectionStatus, _updateConnectionStatus2).call(_this4, false, response.errors[0].message);
	      babelHelpers.classPrivateFieldGet(_this4, _checkConnectButton).setState(null);
	      reject();
	    });
	  });
	}
	function _showSaveSuccessPopup2(connection, supersetIsReady) {
	  var _this5 = this,
	    _babelHelpers$classPr11;
	  var popup = null;

	  // show for (new or active sources) and ready superset only
	  var showCreateDatasetButton = supersetIsReady && (!Object.hasOwn(babelHelpers.classPrivateFieldGet(this, _props).sourceFields, 'id') || babelHelpers.classPrivateFieldGet(this, _props).sourceFields.active);
	  var createDatasetButton = showCreateDatasetButton ? main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-btn ui-btn-md ui-btn-primary\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_CREATE_DATASET')) : false;
	  var closeButton = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-btn ui-btn-md ui-btn-light-border\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('EXTERNAL_CONNECTION_CLOSE'));
	  var onPopupClose = function onPopupClose() {
	    _classPrivateMethodGet(_this5, _closeSlider, _closeSlider2).call(_this5);
	  };
	  var sourceType = (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldGet(this, _props).sourceFields.type) !== null && _babelHelpers$classPr11 !== void 0 ? _babelHelpers$classPr11 : babelHelpers.classPrivateFieldGet(this, _props).supportedDatabases[0].code;
	  main_core.Event.bind(createDatasetButton, 'click', function () {
	    onPopupClose();
	    biconnector_datasetImport.Slider.open(sourceType, 0, {
	      connectionId: connection.id,
	      connectionType: connection.type
	    });
	  });
	  main_core.Event.bind(closeButton, 'click', function () {
	    onPopupClose();
	  });
	  var isEditMode = Boolean(babelHelpers.classPrivateFieldGet(this, _props).sourceFields.id);
	  var popupMessageCode = isEditMode ? 'EXTERNAL_CONNECTION_EDIT_SUCCESS' : 'EXTERNAL_CONNECTION_SAVE_SUCCESS';
	  var popupText = main_core.Loc.getMessage(popupMessageCode, {
	    '#CONNECTION_TITLE#': main_core.Text.encode(_classPrivateMethodGet(this, _getConnectionValues, _getConnectionValues2).call(this).title)
	  });
	  var popupContent = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-popup--full-height\">\n\t\t\t\t<div class=\"biconnector-save-progress-popup\">\n\t\t\t\t\t<div class=\"biconnector-save-progress-popup__content\">\n\t\t\t\t\t\t<div class=\"biconnector-save-progress-popup__success-logo\"></div>\n\t\t\t\t\t\t<div class=\"biconnector-save-progress-popup__texts\">\n\t\t\t\t\t\t\t<h3 class=\"biconnector-save-progress-popup__header\">", "</h3>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"biconnector-save-progress-popup__buttons\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), popupText, createDatasetButton, closeButton);
	  popup = new main_popup.Popup({
	    content: popupContent,
	    autoHide: true,
	    events: {
	      onPopupClose: onPopupClose,
	      onPopupDestroy: onPopupClose
	    },
	    fixed: true,
	    width: 500,
	    minHeight: 299,
	    closeIcon: true,
	    noAllPaddings: true,
	    overlay: true
	  });
	  popup.show();
	}
	function _closeSlider2() {
	  BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnection:onConnectionSliderClose');
	  BX.SidePanel.Instance.getTopSlider().close();
	}
	main_core.Reflection.namespace('BX.BIConnector').ExternalConnectionForm = ExternalConnectionForm;

}((this.window = this.window || {}),BX,BX.Event,BX.Main,BX.UI,BX.UI.EntitySelector,BX.BIConnector.DatasetImport));
//# sourceMappingURL=script.js.map

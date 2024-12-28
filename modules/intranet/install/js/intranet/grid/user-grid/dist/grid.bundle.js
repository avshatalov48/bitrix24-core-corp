/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,ui_avatar,ui_label,ui_formElements_field,main_popup,ui_cnt,intranet_reinvite,ui_iconSet_main,ui_dialogs_messagebox,im_public,ui_entitySelector,main_core) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _fieldId = /*#__PURE__*/new WeakMap();
	var _gridId = /*#__PURE__*/new WeakMap();
	var BaseField = /*#__PURE__*/function () {
	  function BaseField(params) {
	    var _params$gridId;
	    babelHelpers.classCallCheck(this, BaseField);
	    _classPrivateFieldInitSpec(this, _fieldId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _gridId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _fieldId, params.fieldId);
	    babelHelpers.classPrivateFieldSet(this, _gridId, (_params$gridId = params.gridId) !== null && _params$gridId !== void 0 ? _params$gridId : null);
	  }
	  babelHelpers.createClass(BaseField, [{
	    key: "getGridId",
	    value: function getGridId() {
	      return babelHelpers.classPrivateFieldGet(this, _gridId);
	    }
	  }, {
	    key: "getFieldId",
	    value: function getFieldId() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldId);
	    }
	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      var _grid;
	      var grid = null;
	      if (babelHelpers.classPrivateFieldGet(this, _gridId)) {
	        grid = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldGet(this, _gridId));
	      }
	      return (_grid = grid) === null || _grid === void 0 ? void 0 : _grid.instance;
	    }
	  }, {
	    key: "getFieldNode",
	    value: function getFieldNode() {
	      return document.getElementById(this.getFieldId());
	    }
	  }, {
	    key: "appendToFieldNode",
	    value: function appendToFieldNode(element) {
	      main_core.Dom.append(element, this.getFieldNode());
	    }
	  }]);
	  return BaseField;
	}();

	var PhotoField = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(PhotoField, _BaseField);
	  function PhotoField() {
	    babelHelpers.classCallCheck(this, PhotoField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PhotoField).apply(this, arguments));
	  }
	  babelHelpers.createClass(PhotoField, [{
	    key: "render",
	    value: function render(params) {
	      var _avatar;
	      var avatarOptions = {
	        size: 40,
	        userpicPath: params.photoUrl ? params.photoUrl : null
	      };
	      var avatar = null;
	      if (params.role === 'collaber') {
	        avatar = new ui_avatar.AvatarRoundGuest(avatarOptions);
	      } else if (params.role === 'extranet') {
	        avatar = new ui_avatar.AvatarRoundExtranet(avatarOptions);
	      } else {
	        avatar = new ui_avatar.AvatarRound(avatarOptions);
	      }
	      (_avatar = avatar) === null || _avatar === void 0 ? void 0 : _avatar.renderTo(this.getFieldNode());
	      main_core.Dom.addClass(this.getFieldNode(), 'user-grid_user-photo');
	    }
	  }]);
	  return PhotoField;
	}(BaseField);

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getFullNameLink = /*#__PURE__*/new WeakSet();
	var _getInvitedLabelContainer = /*#__PURE__*/new WeakSet();
	var _getWaitingConfirmationLabelContainer = /*#__PURE__*/new WeakSet();
	var _getPositionLabelContainer = /*#__PURE__*/new WeakSet();
	var _getIntegratorBalloonContainer = /*#__PURE__*/new WeakSet();
	var _getAdminBalloonContainer = /*#__PURE__*/new WeakSet();
	var _getExtranetBalloonContainer = /*#__PURE__*/new WeakSet();
	var _getCollaberBalloonContainer = /*#__PURE__*/new WeakSet();
	var FullNameField = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(FullNameField, _BaseField);
	  function FullNameField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, FullNameField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(FullNameField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getCollaberBalloonContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getExtranetBalloonContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getAdminBalloonContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getIntegratorBalloonContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getPositionLabelContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getWaitingConfirmationLabelContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getInvitedLabelContainer);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _getFullNameLink);
	    return _this;
	  }
	  babelHelpers.createClass(FullNameField, [{
	    key: "render",
	    value: function render(params) {
	      var fullNameContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"user-grid_full-name-container\">", "</div>\n\t\t"])), _classPrivateMethodGet(this, _getFullNameLink, _getFullNameLink2).call(this, params.fullName, params.profileLink));
	      if (params.position) {
	        main_core.Dom.append(_classPrivateMethodGet(this, _getPositionLabelContainer, _getPositionLabelContainer2).call(this, main_core.Text.encode(params.position)), fullNameContainer);
	      }
	      switch (params.role) {
	        case 'integrator':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getIntegratorBalloonContainer, _getIntegratorBalloonContainer2).call(this), fullNameContainer);
	          break;
	        case 'admin':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getAdminBalloonContainer, _getAdminBalloonContainer2).call(this), fullNameContainer);
	          break;
	        case 'extranet':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getExtranetBalloonContainer, _getExtranetBalloonContainer2).call(this), fullNameContainer);
	          break;
	        case 'collaber':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getCollaberBalloonContainer, _getCollaberBalloonContainer2).call(this), fullNameContainer);
	          break;
	        default:
	          break;
	      }
	      switch (params.inviteStatus) {
	        case 'INVITE_AWAITING_APPROVE':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getWaitingConfirmationLabelContainer, _getWaitingConfirmationLabelContainer2).call(this), fullNameContainer);
	          break;
	        case 'INVITED':
	          main_core.Dom.append(_classPrivateMethodGet(this, _getInvitedLabelContainer, _getInvitedLabelContainer2).call(this), fullNameContainer);
	          break;
	        default:
	          break;
	      }
	      this.appendToFieldNode(fullNameContainer);
	    }
	  }]);
	  return FullNameField;
	}(BaseField);
	function _getFullNameLink2(fullName, profileLink) {
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"user-grid_full-name-label\" href=\"", "\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), profileLink, fullName);
	}
	function _getInvitedLabelContainer2() {
	  var label = new ui_label.Label({
	    text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INVITATION_NOT_ACCEPTED'),
	    color: ui_label.LabelColor.LIGHT_BLUE,
	    fill: true,
	    size: ui_label.Label.Size.MD,
	    customClass: 'user-grid_label'
	  });
	  return label.render();
	}
	function _getWaitingConfirmationLabelContainer2() {
	  var label = new ui_label.Label({
	    text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_NOT_CONFIRMED'),
	    color: ui_label.LabelColor.YELLOW,
	    fill: true,
	    size: ui_label.Label.Size.MD,
	    customClass: 'user-grid_label'
	  });
	  return label.render();
	}
	function _getPositionLabelContainer2(position) {
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"user-grid_position-label\">", "</div>"])), position);
	}
	function _getIntegratorBalloonContainer2() {
	  return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"user-grid_role-label --integrator\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INTEGRATOR'));
	}
	function _getAdminBalloonContainer2() {
	  return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"user-grid_role-label --admin\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADMIN'));
	}
	function _getExtranetBalloonContainer2() {
	  return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"user-grid_role-label --extranet\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_EXTRANET'));
	}
	function _getCollaberBalloonContainer2() {
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"user-grid_role-label --collaber\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_COLLABER'));
	}

	var _templateObject$1, _templateObject2$1;
	var EmployeeField = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(EmployeeField, _BaseField);
	  function EmployeeField() {
	    babelHelpers.classCallCheck(this, EmployeeField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EmployeeField).apply(this, arguments));
	  }
	  babelHelpers.createClass(EmployeeField, [{
	    key: "render",
	    value: function render(params) {
	      var photoFieldId = main_core.Text.getRandom(6);
	      var fullNameFieldId = main_core.Text.getRandom(6);
	      this.appendToFieldNode(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<span id=\"", "\"></span>"])), photoFieldId));
	      this.appendToFieldNode(main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"user-grid_full-name-wrapper\" id=\"", "\"></span>"])), fullNameFieldId));
	      new PhotoField({
	        fieldId: photoFieldId
	      }).render(params);
	      new FullNameField({
	        fieldId: fullNameFieldId
	      }).render(params);
	      main_core.Dom.addClass(this.getFieldNode(), 'user-grid_employee-card-container');
	    }
	  }]);
	  return EmployeeField;
	}(BaseField);

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _grid = /*#__PURE__*/new WeakMap();
	var GridManager = /*#__PURE__*/function () {
	  function GridManager(gridId) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, GridManager);
	    _classPrivateFieldInitSpec$1(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	  }
	  babelHelpers.createClass(GridManager, [{
	    key: "getGrid",
	    value: function getGrid() {
	      return babelHelpers.classPrivateFieldGet(this, _grid);
	    }
	  }, {
	    key: "confirmAction",
	    value: function confirmAction(params) {
	      var _this = this;
	      if (params.userId) {
	        this.confirmUser(params.isAccept ? 'confirm' : 'decline', function () {
	          var row = babelHelpers.classPrivateFieldGet(_this, _grid).getRows().getById(params.userId);
	          row === null || row === void 0 ? void 0 : row.stateLoad();
	          BX.ajax.runAction('intranet.controller.invite.confirmUserRequest', {
	            data: {
	              userId: params.userId,
	              isAccept: params.isAccept ? 'Y' : 'N'
	            }
	          }).then(function (response) {
	            row === null || row === void 0 ? void 0 : row.update();
	          })["catch"](function (err) {
	            row === null || row === void 0 ? void 0 : row.stateUnload();
	            if (!params.isAccept) {
	              _this.activityAction({
	                userId: params.userId,
	                action: 'deactivateInvited'
	              });
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "activityAction",
	    value: function activityAction(params) {
	      var _params$userId,
	        _params$action,
	        _this2 = this;
	      var userId = (_params$userId = params.userId) !== null && _params$userId !== void 0 ? _params$userId : null;
	      var action = (_params$action = params.action) !== null && _params$action !== void 0 ? _params$action : null;
	      if (userId) {
	        this.confirmUser(action, function () {
	          var row = babelHelpers.classPrivateFieldGet(_this2, _grid).getRows().getById(params.userId);
	          row === null || row === void 0 ? void 0 : row.stateLoad();
	          BX.ajax.runComponentAction('bitrix:intranet.user.list', 'setActivity', {
	            mode: 'class',
	            data: {
	              params: {
	                userId: userId,
	                action: action
	              }
	            }
	          }).then(function () {
	            row === null || row === void 0 ? void 0 : row.update();
	          })["catch"](function (response) {
	            row === null || row === void 0 ? void 0 : row.stateUnload();
	            if (BX.type.isNotEmptyObject(response) && BX.type.isArray(response.errors) && action === 'delete') {
	              return _this2.activityAction({
	                action: 'deactivateInvited',
	                userId: userId
	              });
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "confirmUser",
	    value: function confirmUser(action, callBack) {
	      var _this$getConfirmTitle, _this$getConfirmMessa;
	      ui_dialogs_messagebox.MessageBox.show({
	        title: (_this$getConfirmTitle = this.getConfirmTitle(action)) !== null && _this$getConfirmTitle !== void 0 ? _this$getConfirmTitle : '',
	        message: (_this$getConfirmMessa = this.getConfirmMessage(action)) !== null && _this$getConfirmMessa !== void 0 ? _this$getConfirmMessa : '',
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        yesCaption: this.getConfirmButtonText(action),
	        onYes: function onYes(messageBox) {
	          callBack();
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "getConfirmTitle",
	    value: function getConfirmTitle(action) {
	      switch (action) {
	        case 'restore':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_TITLE');
	        case 'confirm':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_TITLE');
	        case 'delete':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_TITLE');
	        case 'deactivate':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_TITLE');
	        case 'deactivateInvited':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_TITLE');
	        case 'decline':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_TITLE');
	        default:
	          return '';
	      }
	    }
	  }, {
	    key: "getConfirmMessage",
	    value: function getConfirmMessage(action) {
	      switch (action) {
	        case 'restore':
	        case 'confirm':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_MESSAGE');
	        case 'delete':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_MESSAGE');
	        case 'deactivate':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_MESSAGE');
	        case 'deactivateInvited':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_MESSAGE');
	        case 'decline':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_MESSAGE');
	        default:
	          return '';
	      }
	    }
	  }, {
	    key: "getConfirmButtonText",
	    value: function getConfirmButtonText(action) {
	      switch (action) {
	        case 'restore':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_BUTTON');
	        case 'confirm':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_BUTTON');
	        case 'delete':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_BUTTON');
	        case 'deactivate':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_BUTTON');
	        case 'deactivateInvited':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON');
	        case 'decline':
	          return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_BUTTON');
	        default:
	          return null;
	      }
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance(gridId) {
	      if (!this.instances[gridId]) {
	        this.instances[gridId] = new GridManager(gridId);
	      }
	      return this.instances[gridId];
	    }
	  }, {
	    key: "setSort",
	    value: function setSort(options) {
	      var _BX$Main$gridManager$2;
	      var grid = (_BX$Main$gridManager$2 = BX.Main.gridManager.getById(options.gridId)) === null || _BX$Main$gridManager$2 === void 0 ? void 0 : _BX$Main$gridManager$2.instance;
	      if (main_core.Type.isObject(grid)) {
	        grid.tableFade();
	        grid.getUserOptions().setSort(options.sortBy, options.order, function () {
	          grid.reload();
	        });
	      }
	    }
	  }, {
	    key: "setFilter",
	    value: function setFilter(options) {
	      var _BX$Main$gridManager$3;
	      var grid = (_BX$Main$gridManager$3 = BX.Main.gridManager.getById(options.gridId)) === null || _BX$Main$gridManager$3 === void 0 ? void 0 : _BX$Main$gridManager$3.instance;
	      var filter = BX.Main.filterManager.getById(options.gridId);
	      if (main_core.Type.isObject(grid) && main_core.Type.isObject(filter)) {
	        filter.getApi().extendFilter(options.filter);
	      }
	    }
	  }, {
	    key: "reinviteCloudAction",
	    value: function reinviteCloudAction(data) {
	      return BX.ajax.runAction('intranet.invite.reinviteWithChangeContact', {
	        data: data
	      }).then(function (response) {
	        if (response.data.result) {
	          var InviteAccessPopup = new BX.PopupWindow({
	            content: "<p>".concat(main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS'), "</p>"),
	            autoHide: true
	          });
	          InviteAccessPopup.show();
	        }
	        return response;
	      }, function (response) {
	        var errors = response.errors.map(function (error) {
	          return error.message;
	        });
	        ui_formElements_field.ErrorCollection.showSystemError(errors.join('<br>'));
	        return response;
	      });
	    }
	  }, {
	    key: "reinviteAction",
	    value: function reinviteAction(userId, isExtranetUser) {
	      return BX.ajax.runAction('intranet.controller.invite.reinvite', {
	        data: {
	          params: {
	            userId: userId,
	            extranet: isExtranetUser ? 'Y' : 'N'
	          }
	        }
	      }).then(function (response) {
	        if (response.data.result) {
	          var InviteAccessPopup = new BX.PopupWindow({
	            content: "<p>".concat(main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS'), "</p>"),
	            autoHide: true
	          });
	          InviteAccessPopup.show();
	        }
	        return response;
	      });
	    }
	  }]);
	  return GridManager;
	}();
	babelHelpers.defineProperty(GridManager, "instances", []);

	var _templateObject$2;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _updateData = /*#__PURE__*/new WeakSet();
	var _onClick = /*#__PURE__*/new WeakSet();
	var _actionFactory = /*#__PURE__*/new WeakSet();
	var _inviteAction = /*#__PURE__*/new WeakSet();
	var _acceptAction = /*#__PURE__*/new WeakSet();
	var ActivityField = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(ActivityField, _BaseField);
	  function ActivityField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, ActivityField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(ActivityField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _acceptAction);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _inviteAction);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _actionFactory);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onClick);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _updateData);
	    return _this;
	  }
	  babelHelpers.createClass(ActivityField, [{
	    key: "render",
	    value: function render(params) {
	      var _params$action,
	        _this2 = this;
	      var title = '';
	      var color = '';
	      switch ((_params$action = params.action) !== null && _params$action !== void 0 ? _params$action : 'invite') {
	        case 'accept':
	          title = main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_ACCEPT_ENTER');
	          color = BX.UI.Button.Color.PRIMARY;
	          break;
	        case 'invite':
	        default:
	          title = main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_INVITE_AGAIN');
	          color = BX.UI.Button.Color.LIGHT_BORDER;
	          break;
	      }
	      var counter = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-counter user-grid_invitation-counter\">\n\t\t\t\t<div class=\"ui-counter-inner\">1</div>\n\t\t\t</div>\n\t\t"])));
	      main_core.Dom.append(counter, this.getFieldNode());
	      var button = new BX.UI.Button({
	        text: title,
	        color: color,
	        noCaps: true,
	        size: BX.UI.Button.Size.EXTRA_SMALL,
	        tag: BX.UI.Button.Tag.INPUT,
	        round: true,
	        onclick: function onclick() {
	          _classPrivateMethodGet$1(_this2, _onClick, _onClick2).call(_this2, params, button);
	        }
	      });
	      button.renderTo(this.getFieldNode());
	    }
	  }]);
	  return ActivityField;
	}(BaseField);
	function _updateData2(data) {
	  var _GridManager$getInsta;
	  var row = (_GridManager$getInsta = GridManager.getInstance(this.gridId).getGrid()) === null || _GridManager$getInsta === void 0 ? void 0 : _GridManager$getInsta.getRows().getById(this.userId);
	  row === null || row === void 0 ? void 0 : row.stateLoad();
	  GridManager.reinviteCloudAction(data).then(function (response) {
	    row === null || row === void 0 ? void 0 : row.update();
	    row === null || row === void 0 ? void 0 : row.stateUnload();
	  });
	}
	function _onClick2(params, button) {
	  if (!params.enabled) {
	    var popup = BX.PopupWindowManager.create('intranet-user-grid-invitation-disabled', null, {
	      darkMode: true,
	      content: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_DISABLED'),
	      closeByEsc: true,
	      angle: true,
	      offsetLeft: 40,
	      maxWidth: 300,
	      overlay: false,
	      autoHide: true
	    });
	    popup.setBindElement(button.getContainer());
	    popup.show();
	  } else {
	    _classPrivateMethodGet$1(this, _actionFactory, _actionFactory2).call(this, params.action).call(this, params, button);
	  }
	}
	function _actionFactory2(action) {
	  switch (action) {
	    case 'accept':
	      return _classPrivateMethodGet$1(this, _acceptAction, _acceptAction2);
	      break;
	    case 'invite':
	      return _classPrivateMethodGet$1(this, _inviteAction, _inviteAction2);
	    default:
	      return _classPrivateMethodGet$1(this, _inviteAction, _inviteAction2);
	      break;
	  }
	}
	function _inviteAction2(params, button) {
	  if (params.isCloud === true) {
	    var _ref, _params$email;
	    var reinvitePopup = new intranet_reinvite.ReinvitePopup({
	      userId: params.userId,
	      transport: _classPrivateMethodGet$1(this, _updateData, _updateData2).bind(params),
	      //callback,
	      formType: params.email ? intranet_reinvite.FormType.EMAIL : intranet_reinvite.FormType.PHONE,
	      bindElement: button.getContainer(),
	      inputValue: (_ref = (_params$email = params.email) !== null && _params$email !== void 0 ? _params$email : params.phoneNumber) !== null && _ref !== void 0 ? _ref : ''
	    });
	    //This is a hack. When the row is updated, a new button is created.
	    reinvitePopup.getPopup().setBindElement(button.getContainer());
	    reinvitePopup.show();
	  } else {
	    button.setWaiting(true);
	    GridManager.reinviteAction(params.userId, params.isExtranet).then(function () {
	      button.setWaiting(false);
	    });
	  }
	}
	function _acceptAction2(params, button) {
	  GridManager.getInstance(params.gridId).confirmAction({
	    isAccept: true,
	    userId: params.userId
	  });
	}

	var _templateObject$3, _templateObject2$2, _templateObject3$1;
	var DepartmentField = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(DepartmentField, _BaseField);
	  function DepartmentField() {
	    babelHelpers.classCallCheck(this, DepartmentField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DepartmentField).apply(this, arguments));
	  }
	  babelHelpers.createClass(DepartmentField, [{
	    key: "render",
	    value: function render(params) {
	      var _this = this;
	      main_core.Dom.addClass(this.getFieldNode(), 'user-grid_department-container');
	      if (params.departments.length === 0 && params.canEdit) {
	        // TODO: add department button
	        return;
	        var onclick = function onclick() {
	          var dialog = new ui_entitySelector.Dialog({
	            width: 300,
	            height: 300,
	            targetNode: addButton,
	            compactView: true,
	            multiple: false,
	            entities: [{
	              id: 'department',
	              options: {
	                selectMode: 'departmentsOnly',
	                allowSelectRootDepartment: true
	              }
	            }]
	          });
	          dialog.show();
	        };
	        var addButton = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"user-grid_department-btn\" onclick=\"", "\">\n\t\t\t\t\t<div class=\"user-grid_department-icon-container\">\n\t\t\t\t\t\t<div class=\"ui-icon-set --plus-30\" style=\"--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: #2fc6f6;\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"user-grid_department-name-container\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), onclick, main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADD_DEPARTMENT'));
	        this.appendToFieldNode(addButton);
	      } else {
	        Object.values(params.departments).forEach(function (department) {
	          var isSelected = department.id === params.selectedDepartment;
	          var onclick = function onclick() {
	            GridManager.setFilter({
	              gridId: _this.getGridId(),
	              filter: {
	                DEPARTMENT: isSelected ? '' : department.id,
	                DEPARTMENT_label: isSelected ? '' : department.name
	              }
	            });
	          };
	          var button = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"user-grid_department-btn ", "\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"user-grid_department-name-container\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), isSelected ? '--selected' : '', onclick, department.name);
	          if (isSelected) {
	            main_core.Dom.append(main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"user-grid_department-btn-remove ui-icon-set --cross-60\"></div>\n\t\t\t\t\t"]))), button);
	          }
	          _this.appendToFieldNode(button);
	        });
	      }
	    }
	  }]);
	  return DepartmentField;
	}(BaseField);

	/**
	 * @abstract
	 */
	var BaseAction = /*#__PURE__*/function () {
	  babelHelpers.createClass(BaseAction, [{
	    key: "getAjaxMethod",
	    /**
	     * @abstract
	     */
	    value: function getAjaxMethod() {
	      throw new Error('not implemented');
	    }
	  }], [{
	    key: "getActionId",
	    /**
	     * @abstract
	     */
	    value: function getActionId() {
	      throw new Error('not implemented');
	    }
	  }]);
	  function BaseAction(params) {
	    var _params$showPopups;
	    babelHelpers.classCallCheck(this, BaseAction);
	    this.grid = params.grid;
	    this.userFilter = params.filter;
	    this.selectedUsers = params.selectedUsers;
	    this.showPopups = (_params$showPopups = params.showPopups) !== null && _params$showPopups !== void 0 ? _params$showPopups : true;
	    this.isCloud = params.isCloud;
	  }
	  babelHelpers.createClass(BaseAction, [{
	    key: "execute",
	    value: function execute() {
	      var _this = this;
	      var confirmationPopup = this.showPopups ? this.getConfirmationPopup() : null;
	      if (confirmationPopup) {
	        confirmationPopup.setOkCallback(function () {
	          _this.sendActionRequest();
	          confirmationPopup.close();
	        });
	        confirmationPopup.show();
	      } else {
	        this.sendActionRequest();
	      }
	    }
	  }, {
	    key: "getConfirmationPopup",
	    value: function getConfirmationPopup() {
	      return null;
	    }
	  }, {
	    key: "sendActionRequest",
	    value: function sendActionRequest() {
	      var _this$selectedUsers,
	        _this2 = this;
	      this.grid.tableFade();
	      var selectedRows = (_this$selectedUsers = this.selectedUsers) !== null && _this$selectedUsers !== void 0 ? _this$selectedUsers : this.grid.getRows().getSelectedIds();
	      var isSelectedAllRows = this.grid.getRows().isAllSelected() ? 'Y' : 'N';
	      BX.ajax.runAction(this.getAjaxMethod(), {
	        data: {
	          fields: {
	            userIds: selectedRows,
	            isSelectedAllRows: isSelectedAllRows,
	            filter: this.userFilter
	          }
	        }
	      }).then(function (result) {
	        return _this2.handleSuccess(result);
	      })["catch"](function (result) {
	        return _this2.handleError(result);
	      });
	    }
	  }, {
	    key: "handleSuccess",
	    value: function handleSuccess(result) {
	      this.grid.reload();
	      if (this.showPopups) {
	        var _result$data = result.data,
	          skippedActiveUsers = _result$data.skippedActiveUsers,
	          skippedFiredUsers = _result$data.skippedFiredUsers;
	        if (skippedActiveUsers && Object.keys(skippedActiveUsers).length > 0) {
	          this.showActiveUsersPopup(skippedActiveUsers);
	        } else if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0) {
	          this.showFiredUsersPopup(skippedFiredUsers);
	        }
	      }
	    }
	  }, {
	    key: "handleError",
	    value: function handleError(result) {
	      this.grid.tableUnfade();
	      this.unselectRows(this.grid);
	      console.error(result);
	      if (this.showPopups && result.errors && result.errors.length > 0) {
	        var errorMessage = result.errors.map(function (item) {
	          return item.message;
	        }).join(', ');
	        ui_dialogs_messagebox.MessageBox.show({
	          message: errorMessage,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	          yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	          onYes: function onYes(messageBox) {
	            messageBox.close();
	          }
	        });
	      }
	    }
	  }, {
	    key: "showActiveUsersPopup",
	    value: function showActiveUsersPopup(activeUsers) {
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage(this.getSkippedUsersTitleCode()),
	        message: this.getMessageWithProfileNames(this.getSkippedUsersMessageCode(), activeUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "showFiredUsersPopup",
	    value: function showFiredUsersPopup(firedUsers) {
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE', firedUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "getMessageWithProfileNames",
	    value: function getMessageWithProfileNames(messageCode, users) {
	      var maxDisplayCount = 5;
	      var userValues = Object.values(users);
	      var displayedNames = userValues.slice(0, maxDisplayCount).map(function (user) {
	        return user.fullName;
	      });
	      var remainingCount = userValues.length - maxDisplayCount;
	      var namesString = displayedNames.join(', ');
	      if (displayedNames.length < 2 && remainingCount < 1) {
	        return main_core.Loc.getMessage("".concat(messageCode, "_SINGLE"), {
	          '#USER#': namesString
	        });
	      }
	      if (remainingCount > 0) {
	        return main_core.Loc.getMessage("".concat(messageCode, "_REMAINING"), {
	          '#USER_LIST#': namesString,
	          '#USER_REMAINING#': remainingCount
	        });
	      }
	      return main_core.Loc.getMessage(messageCode, {
	        '#USER_LIST#': namesString
	      });
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return '';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return '';
	    }
	  }, {
	    key: "unselectRows",
	    value: function unselectRows(grid) {
	      grid.getRows().unselectAll();
	      grid.updateCounterDisplayed();
	      grid.updateCounterSelected();
	      grid.disableActionsPanel();
	      BX.onCustomEvent(window, 'Grid::allRowsUnselected', []);
	    }
	  }]);
	  return BaseAction;
	}();

	var FireAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(FireAction, _BaseAction);
	  function FireAction() {
	    babelHelpers.classCallCheck(this, FireAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FireAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(FireAction, [{
	    key: "getConfirmationPopup",
	    value: function getConfirmationPopup() {
	      return new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_TITLE'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_BUTTON')
	      });
	    }
	  }, {
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupFire';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE';
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'fire';
	    }
	  }]);
	  return FireAction;
	}(BaseAction);

	var DeleteAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(DeleteAction, _BaseAction);
	  function DeleteAction() {
	    babelHelpers.classCallCheck(this, DeleteAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DeleteAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(DeleteAction, [{
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupDelete';
	    }
	  }, {
	    key: "getConfirmationPopup",
	    value: function getConfirmationPopup() {
	      return new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_TITLE'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_BUTTON')
	      });
	    }
	  }, {
	    key: "showActiveUsersPopup",
	    value: function showActiveUsersPopup(activeUsers) {
	      var _this = this;
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE', activeUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	          new FireAction({
	            selectedUsers: Object.keys(activeUsers),
	            grid: _this.grid,
	            filter: _this.userFilter,
	            showPopups: false
	          }).execute();
	        },
	        onNo: function onNo() {
	          _this.grid.reload();
	        }
	      });
	    }
	  }, {
	    key: "showFiredUsersPopup",
	    value: function showFiredUsersPopup(firedUsers) {
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_MESSAGE', firedUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'delete';
	    }
	  }]);
	  return DeleteAction;
	}(BaseAction);

	var ConfirmAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(ConfirmAction, _BaseAction);
	  function ConfirmAction() {
	    babelHelpers.classCallCheck(this, ConfirmAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ConfirmAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(ConfirmAction, [{
	    key: "getConfirmationPopup",
	    value: function getConfirmationPopup() {
	      return new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_TITLE'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON')
	      });
	    }
	  }, {
	    key: "handleSuccess",
	    value: function handleSuccess(result) {
	      this.grid.reload();
	      if (this.showPopups) {
	        var skippedFiredUsers = result.data.skippedFiredUsers;
	        if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0) {
	          this.showFiredUsersPopup(skippedFiredUsers);
	        }
	      }
	    }
	  }, {
	    key: "showFiredUsersPopup",
	    value: function showFiredUsersPopup(firedUsers) {
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_MESSAGE', firedUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupConfirm';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'confirm';
	    }
	  }]);
	  return ConfirmAction;
	}(BaseAction);

	var DeclineAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(DeclineAction, _BaseAction);
	  function DeclineAction() {
	    babelHelpers.classCallCheck(this, DeclineAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DeclineAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(DeclineAction, [{
	    key: "getConfirmationPopup",
	    value: function getConfirmationPopup() {
	      return new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_TITLE'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_BUTTON')
	      });
	    }
	  }, {
	    key: "showActiveUsersPopup",
	    value: function showActiveUsersPopup(activeUsers) {
	      var _this = this;
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE', activeUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	          new FireAction({
	            selectedUsers: Object.keys(activeUsers),
	            grid: _this.grid,
	            filter: _this.userFilter,
	            showPopups: false
	          }).execute();
	        }
	      });
	    }
	  }, {
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupDecline';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'decline';
	    }
	  }]);
	  return DeclineAction;
	}(BaseAction);

	var ReinviteAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(ReinviteAction, _BaseAction);
	  function ReinviteAction() {
	    babelHelpers.classCallCheck(this, ReinviteAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ReinviteAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(ReinviteAction, [{
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupReInvite';
	    }
	  }, {
	    key: "handleSuccess",
	    value: function handleSuccess(result) {
	      this.grid.tableUnfade();
	      var _result$data = result.data,
	        skippedActiveUsers = _result$data.skippedActiveUsers,
	        skippedFiredUsers = _result$data.skippedFiredUsers,
	        skippedWaitingUsers = _result$data.skippedWaitingUsers;
	      if (skippedActiveUsers && Object.keys(skippedActiveUsers).length > 0) {
	        this.showActiveUsersPopup(skippedActiveUsers);
	      } else if (skippedWaitingUsers && Object.keys(skippedWaitingUsers).length > 0) {
	        this.showWaitingUsersPopup(skippedWaitingUsers);
	      } else if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0) {
	        this.showFiredUsersPopup(skippedFiredUsers);
	      } else {
	        var _BX$Bitrix, _BX$Bitrix$EmailConfi;
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_REINVITE_SUCCESS'),
	          autoHide: true,
	          position: 'bottom-right',
	          category: 'menu-self-item-popup',
	          autoHideDelay: 3000
	        });
	        (_BX$Bitrix = BX.Bitrix24) === null || _BX$Bitrix === void 0 ? void 0 : (_BX$Bitrix$EmailConfi = _BX$Bitrix.EmailConfirmation) === null || _BX$Bitrix$EmailConfi === void 0 ? void 0 : _BX$Bitrix$EmailConfi.showPopupDispatched();
	      }
	      this.unselectRows(this.grid);
	    }
	  }, {
	    key: "showWaitingUsersPopup",
	    value: function showWaitingUsersPopup(waitingUsers) {
	      var _this = this;
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_MESSAGE', waitingUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	          new ConfirmAction({
	            grid: _this.grid,
	            filter: _this.userFilter,
	            selectedUsers: Object.keys(waitingUsers),
	            showPopups: false
	          }).execute();
	        }
	      });
	    }
	  }, {
	    key: "showFiredUsersPopup",
	    value: function showFiredUsersPopup(firedUsers) {
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_TITLE'),
	        message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_MESSAGE', firedUsers),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: function onYes(messageBox) {
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'reInvite';
	    }
	  }]);
	  return ReinviteAction;
	}(BaseAction);

	var CreateChatAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(CreateChatAction, _BaseAction);
	  function CreateChatAction() {
	    babelHelpers.classCallCheck(this, CreateChatAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CreateChatAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(CreateChatAction, [{
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.createChat';
	    }
	  }, {
	    key: "handleSuccess",
	    value: function handleSuccess(result) {
	      this.grid.tableUnfade();
	      var chatId = result.data;
	      im_public.Messenger.openChat("chat".concat(chatId));
	      this.unselectRows(this.grid);
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'createChat';
	    }
	  }]);
	  return CreateChatAction;
	}(BaseAction);

	var _templateObject$4;
	var ChangeDepartmentAction = /*#__PURE__*/function (_BaseAction) {
	  babelHelpers.inherits(ChangeDepartmentAction, _BaseAction);
	  function ChangeDepartmentAction() {
	    babelHelpers.classCallCheck(this, ChangeDepartmentAction);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ChangeDepartmentAction).apply(this, arguments));
	  }
	  babelHelpers.createClass(ChangeDepartmentAction, [{
	    key: "getAjaxMethod",
	    value: function getAjaxMethod() {
	      return 'intranet.controller.user.userlist.groupChangeDepartment';
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var _this = this;
	      var saveButton = new BX.UI.SaveButton({
	        onclick: function onclick() {
	          var selectedIds = dialog.getSelectedItems().map(function (item) {
	            return item.id;
	          });
	          dialog.hide();
	          if (selectedIds.length > 0) {
	            _this.sendChangeDepartmentRequest(selectedIds);
	          } else {
	            _this.unselectRows(_this.grid);
	          }
	        },
	        size: BX.UI.Button.Size.SMALL
	      });
	      var cancelButton = new BX.UI.CancelButton({
	        onclick: function onclick() {
	          dialog.hide();
	        },
	        size: BX.UI.Button.Size.SMALL
	      });
	      var footer = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	      saveButton.renderTo(footer);
	      cancelButton.renderTo(footer);
	      var dialog = new ui_entitySelector.Dialog({
	        dropdownMode: true,
	        enableSearch: true,
	        compactView: true,
	        multiple: true,
	        footer: footer,
	        entities: [{
	          id: 'department',
	          options: {
	            selectMode: 'departmentsOnly',
	            allowSelectRootDepartment: true
	          }
	        }]
	      });
	      dialog.show();
	    }
	  }, {
	    key: "sendChangeDepartmentRequest",
	    value: function sendChangeDepartmentRequest(departmentIds) {
	      var _this$selectedUsers,
	        _this2 = this;
	      this.grid.tableFade();
	      var selectedRows = (_this$selectedUsers = this.selectedUsers) !== null && _this$selectedUsers !== void 0 ? _this$selectedUsers : this.grid.getRows().getSelectedIds();
	      var isSelectedAllRows = this.grid.getRows().isAllSelected() ? 'Y' : 'N';
	      BX.ajax.runAction(this.getAjaxMethod(), {
	        data: {
	          fields: {
	            userIds: selectedRows,
	            isSelectedAllRows: isSelectedAllRows,
	            filter: this.userFilter,
	            departmentIds: departmentIds
	          }
	        }
	      }).then(function (result) {
	        return _this2.handleSuccess(result);
	      })["catch"](function (result) {
	        return _this2.handleError(result);
	      });
	    }
	  }, {
	    key: "getSkippedUsersTitleCode",
	    value: function getSkippedUsersTitleCode() {
	      return 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_TITLE';
	    }
	  }, {
	    key: "getSkippedUsersMessageCode",
	    value: function getSkippedUsersMessageCode() {
	      return this.isCloud ? 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE_CLOUD' : 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE';
	    }
	  }], [{
	    key: "getActionId",
	    value: function getActionId() {
	      return 'changeDepartment';
	    }
	  }]);
	  return ChangeDepartmentAction;
	}(BaseAction);

	var ACTIONS = [DeleteAction, FireAction, ConfirmAction, DeclineAction, ReinviteAction, CreateChatAction, ChangeDepartmentAction];
	var ActionFactory = /*#__PURE__*/function () {
	  function ActionFactory() {
	    babelHelpers.classCallCheck(this, ActionFactory);
	  }
	  babelHelpers.createClass(ActionFactory, null, [{
	    key: "createAction",
	    value: function createAction(actionId, params) {
	      var ActionClass = ACTIONS.find(function (action) {
	        return action.getActionId() === actionId;
	      });
	      if (!ActionClass) {
	        throw new Error("Unknown actionId: ".concat(actionId));
	      }
	      return new ActionClass(params);
	    }
	  }]);
	  return ActionFactory;
	}();

	var Panel = /*#__PURE__*/function () {
	  function Panel() {
	    babelHelpers.classCallCheck(this, Panel);
	  }
	  babelHelpers.createClass(Panel, null, [{
	    key: "executeAction",
	    value: function executeAction(params) {
	      try {
	        var _BX$Main$gridManager$;
	        var action = ActionFactory.createAction(params.actionId, {
	          grid: (_BX$Main$gridManager$ = BX.Main.gridManager.getById(params.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance,
	          filter: params.filter,
	          isCloud: params.isCloud
	        });
	        action.execute();
	      } catch (error) {
	        console.error('Error executing action:', error);
	      }
	    }
	  }]);
	  return Panel;
	}();

	exports.BaseField = BaseField;
	exports.PhotoField = PhotoField;
	exports.FullNameField = FullNameField;
	exports.EmployeeField = EmployeeField;
	exports.ActivityField = ActivityField;
	exports.DepartmentField = DepartmentField;
	exports.GridManager = GridManager;
	exports.Panel = Panel;

}((this.BX.Intranet.UserList = this.BX.Intranet.UserList || {}),BX.UI,BX.UI,BX.UI.FormElements,BX.Main,BX.UI,BX.Intranet.Reinvite,BX,BX.UI.Dialogs,BX.Messenger.v2.Lib,BX.UI.EntitySelector,BX));
//# sourceMappingURL=grid.bundle.js.map

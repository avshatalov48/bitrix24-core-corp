/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,ui_avatar,ui_label,ui_formElements_field,ui_cnt,intranet_reinvite,ui_iconSet_main,ui_dialogs_messagebox,im_public,ui_entitySelector,main_core) {
	'use strict';

	var _fieldId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fieldId");
	var _gridId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridId");
	class BaseField {
	  constructor(params) {
	    var _params$gridId;
	    Object.defineProperty(this, _fieldId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _gridId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldId)[_fieldId] = params.fieldId;
	    babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId] = (_params$gridId = params.gridId) != null ? _params$gridId : null;
	  }
	  getGridId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId];
	  }
	  getFieldId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fieldId)[_fieldId];
	  }
	  getGrid() {
	    var _grid;
	    let grid = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId]) {
	      grid = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId]);
	    }
	    return (_grid = grid) == null ? void 0 : _grid.instance;
	  }
	  getFieldNode() {
	    return document.getElementById(this.getFieldId());
	  }
	  appendToFieldNode(element) {
	    main_core.Dom.append(element, this.getFieldNode());
	  }
	}

	class PhotoField extends BaseField {
	  render(params) {
	    var _avatar;
	    const avatarOptions = {
	      size: 40,
	      userpicPath: params.photoUrl ? params.photoUrl : null
	    };
	    let avatar = null;
	    if (params.role === 'collaber') {
	      avatar = new ui_avatar.AvatarRoundGuest(avatarOptions);
	    } else if (params.role === 'extranet') {
	      avatar = new ui_avatar.AvatarRoundExtranet(avatarOptions);
	    } else {
	      avatar = new ui_avatar.AvatarRound(avatarOptions);
	    }
	    (_avatar = avatar) == null ? void 0 : _avatar.renderTo(this.getFieldNode());
	    main_core.Dom.addClass(this.getFieldNode(), 'user-grid_user-photo');
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	var _getFullNameLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFullNameLink");
	var _getInvitedLabelContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInvitedLabelContainer");
	var _getWaitingConfirmationLabelContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWaitingConfirmationLabelContainer");
	var _getPositionLabelContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPositionLabelContainer");
	var _getIntegratorBalloonContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIntegratorBalloonContainer");
	var _getAdminBalloonContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAdminBalloonContainer");
	var _getExtranetBalloonContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getExtranetBalloonContainer");
	var _getCollaberBalloonContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCollaberBalloonContainer");
	class FullNameField extends BaseField {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getCollaberBalloonContainer, {
	      value: _getCollaberBalloonContainer2
	    });
	    Object.defineProperty(this, _getExtranetBalloonContainer, {
	      value: _getExtranetBalloonContainer2
	    });
	    Object.defineProperty(this, _getAdminBalloonContainer, {
	      value: _getAdminBalloonContainer2
	    });
	    Object.defineProperty(this, _getIntegratorBalloonContainer, {
	      value: _getIntegratorBalloonContainer2
	    });
	    Object.defineProperty(this, _getPositionLabelContainer, {
	      value: _getPositionLabelContainer2
	    });
	    Object.defineProperty(this, _getWaitingConfirmationLabelContainer, {
	      value: _getWaitingConfirmationLabelContainer2
	    });
	    Object.defineProperty(this, _getInvitedLabelContainer, {
	      value: _getInvitedLabelContainer2
	    });
	    Object.defineProperty(this, _getFullNameLink, {
	      value: _getFullNameLink2
	    });
	  }
	  render(params) {
	    const fullNameContainer = main_core.Tag.render(_t || (_t = _`
			<div class="user-grid_full-name-container">${0}</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getFullNameLink)[_getFullNameLink](params.fullName, params.profileLink));
	    if (params.position) {
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getPositionLabelContainer)[_getPositionLabelContainer](main_core.Text.encode(params.position)), fullNameContainer);
	    }
	    switch (params.role) {
	      case 'integrator':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getIntegratorBalloonContainer)[_getIntegratorBalloonContainer](), fullNameContainer);
	        break;
	      case 'admin':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getAdminBalloonContainer)[_getAdminBalloonContainer](), fullNameContainer);
	        break;
	      case 'extranet':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getExtranetBalloonContainer)[_getExtranetBalloonContainer](), fullNameContainer);
	        break;
	      case 'collaber':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getCollaberBalloonContainer)[_getCollaberBalloonContainer](), fullNameContainer);
	        break;
	      default:
	        break;
	    }
	    switch (params.inviteStatus) {
	      case 'INVITE_AWAITING_APPROVE':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getWaitingConfirmationLabelContainer)[_getWaitingConfirmationLabelContainer](), fullNameContainer);
	        break;
	      case 'INVITED':
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getInvitedLabelContainer)[_getInvitedLabelContainer](), fullNameContainer);
	        break;
	      default:
	        break;
	    }
	    this.appendToFieldNode(fullNameContainer);
	  }
	}
	function _getFullNameLink2(fullName, profileLink) {
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<a class="user-grid_full-name-label" href="${0}">
				${0}
			</a>
		`), profileLink, fullName);
	}
	function _getInvitedLabelContainer2() {
	  const label = new ui_label.Label({
	    text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INVITATION_NOT_ACCEPTED'),
	    color: ui_label.LabelColor.LIGHT_BLUE,
	    fill: true,
	    size: ui_label.Label.Size.MD,
	    customClass: 'user-grid_label'
	  });
	  return label.render();
	}
	function _getWaitingConfirmationLabelContainer2() {
	  const label = new ui_label.Label({
	    text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_NOT_CONFIRMED'),
	    color: ui_label.LabelColor.YELLOW,
	    fill: true,
	    size: ui_label.Label.Size.MD,
	    customClass: 'user-grid_label'
	  });
	  return label.render();
	}
	function _getPositionLabelContainer2(position) {
	  return main_core.Tag.render(_t3 || (_t3 = _`<div class="user-grid_position-label">${0}</div>`), position);
	}
	function _getIntegratorBalloonContainer2() {
	  return main_core.Tag.render(_t4 || (_t4 = _`
			<span class="user-grid_role-label --integrator">
				${0}
			</span>
		`), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INTEGRATOR'));
	}
	function _getAdminBalloonContainer2() {
	  return main_core.Tag.render(_t5 || (_t5 = _`
			<span class="user-grid_role-label --admin">
				${0}
			</span>
		`), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADMIN'));
	}
	function _getExtranetBalloonContainer2() {
	  return main_core.Tag.render(_t6 || (_t6 = _`
			<span class="user-grid_role-label --extranet">
				${0}
			</span>
		`), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_EXTRANET'));
	}
	function _getCollaberBalloonContainer2() {
	  return main_core.Tag.render(_t7 || (_t7 = _`
			<span class="user-grid_role-label --collaber">
				${0}
			</span>
		`), main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_COLLABER'));
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1;
	class EmployeeField extends BaseField {
	  render(params) {
	    const photoFieldId = main_core.Text.getRandom(6);
	    const fullNameFieldId = main_core.Text.getRandom(6);
	    this.appendToFieldNode(main_core.Tag.render(_t$1 || (_t$1 = _$1`<span id="${0}"></span>`), photoFieldId));
	    this.appendToFieldNode(main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<span class="user-grid_full-name-wrapper" id="${0}"></span>`), fullNameFieldId));
	    new PhotoField({
	      fieldId: photoFieldId
	    }).render(params);
	    new FullNameField({
	      fieldId: fullNameFieldId
	    }).render(params);
	    main_core.Dom.addClass(this.getFieldNode(), 'user-grid_employee-card-container');
	  }
	}

	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	class GridManager {
	  constructor(gridId) {
	    var _BX$Main$gridManager$;
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = (_BX$Main$gridManager$ = BX.Main.gridManager.getById(gridId)) == null ? void 0 : _BX$Main$gridManager$.instance;
	  }
	  static getInstance(gridId) {
	    if (!this.instances[gridId]) {
	      this.instances[gridId] = new GridManager(gridId);
	    }
	    return this.instances[gridId];
	  }
	  static setSort(options) {
	    var _BX$Main$gridManager$2;
	    const grid = (_BX$Main$gridManager$2 = BX.Main.gridManager.getById(options.gridId)) == null ? void 0 : _BX$Main$gridManager$2.instance;
	    if (main_core.Type.isObject(grid)) {
	      grid.tableFade();
	      grid.getUserOptions().setSort(options.sortBy, options.order, () => {
	        grid.reload();
	      });
	    }
	  }
	  static setFilter(options) {
	    var _BX$Main$gridManager$3;
	    const grid = (_BX$Main$gridManager$3 = BX.Main.gridManager.getById(options.gridId)) == null ? void 0 : _BX$Main$gridManager$3.instance;
	    const filter = BX.Main.filterManager.getById(options.gridId);
	    if (main_core.Type.isObject(grid) && main_core.Type.isObject(filter)) {
	      filter.getApi().extendFilter(options.filter);
	    }
	  }
	  static reinviteCloudAction(data) {
	    return main_core.ajax.runAction('intranet.invite.reinviteWithChangeContact', {
	      data
	    }).then(response => {
	      if (response.data.result) {
	        const InviteAccessPopup = new BX.PopupWindow({
	          content: `<p>${main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS')}</p>`,
	          autoHide: true
	        });
	        InviteAccessPopup.show();
	      }
	      return response;
	    }, response => {
	      const errors = response.errors.map(error => error.message);
	      ui_formElements_field.ErrorCollection.showSystemError(errors.join('<br>'));
	      return response;
	    });
	  }
	  static reinviteAction(userId, isExtranetUser) {
	    return main_core.ajax.runAction('intranet.controller.invite.reinvite', {
	      data: {
	        params: {
	          userId,
	          extranet: isExtranetUser ? 'Y' : 'N'
	        }
	      }
	    }).then(response => {
	      if (response.data.result) {
	        const InviteAccessPopup = new BX.PopupWindow({
	          content: `<p>${main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS')}</p>`,
	          autoHide: true
	        });
	        InviteAccessPopup.show();
	      }
	      return response;
	    });
	  }
	  getGrid() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid];
	  }
	  confirmAction(params) {
	    if (params.userId) {
	      this.confirmUser(params.isAccept ? 'confirm' : 'decline', () => {
	        const row = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getById(params.userId);
	        row == null ? void 0 : row.stateLoad();
	        main_core.ajax.runAction('intranet.controller.invite.confirmUserRequest', {
	          data: {
	            userId: params.userId,
	            isAccept: params.isAccept ? 'Y' : 'N'
	          }
	        }).then(response => {
	          if (response.data === true) {
	            row == null ? void 0 : row.update();
	          } else if (params.isAccept) {
	            row == null ? void 0 : row.stateUnload();
	          } else {
	            this.activityAction({
	              userId: params.userId,
	              action: 'deleteOrFire'
	            });
	          }
	        }).catch(() => {
	          if (params.isAccept) {
	            row == null ? void 0 : row.stateUnload();
	          } else {
	            this.activityAction({
	              userId: params.userId,
	              action: 'deleteOrFire'
	            });
	          }
	        });
	      });
	    }
	  }
	  activityAction(params) {
	    var _params$userId, _params$action;
	    const userId = (_params$userId = params.userId) != null ? _params$userId : null;
	    const action = (_params$action = params.action) != null ? _params$action : null;
	    if (userId) {
	      this.confirmUser(action, () => {
	        const row = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getById(params.userId);
	        row == null ? void 0 : row.stateLoad();
	        if (['fire', 'restore', 'deleteOrFire'].includes(action)) {
	          main_core.ajax.runAction(`intranet.v2.User.${action}`, {
	            data: {
	              userId
	            }
	          }).then(() => {
	            row == null ? void 0 : row.update();
	          }).catch(response => {
	            row == null ? void 0 : row.stateUnload();
	            const errors = response.errors.map(error => error.message);
	            ui_formElements_field.ErrorCollection.showSystemError(errors.join('<br>'));
	          });
	        }
	      });
	    }
	  }
	  confirmUser(action, callBack) {
	    var _this$getConfirmTitle, _this$getConfirmMessa;
	    ui_dialogs_messagebox.MessageBox.show({
	      title: (_this$getConfirmTitle = this.getConfirmTitle(action)) != null ? _this$getConfirmTitle : '',
	      message: (_this$getConfirmMessa = this.getConfirmMessage(action)) != null ? _this$getConfirmMessa : '',
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	      yesCaption: this.getConfirmButtonText(action),
	      onYes: messageBox => {
	        callBack();
	        messageBox.close();
	      }
	    });
	  }
	  getConfirmTitle(action) {
	    switch (action) {
	      case 'restore':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_TITLE');
	      case 'confirm':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_TITLE');
	      case 'delete':
	      case 'deleteOrFire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_TITLE');
	      case 'fire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_TITLE');
	      case 'deactivateInvited':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_TITLE');
	      case 'decline':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_TITLE');
	      default:
	        return '';
	    }
	  }
	  getConfirmMessage(action) {
	    switch (action) {
	      case 'restore':
	      case 'confirm':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_MESSAGE');
	      case 'delete':
	      case 'deleteOrFire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_MESSAGE');
	      case 'fire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_MESSAGE');
	      case 'deactivateInvited':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_MESSAGE');
	      case 'decline':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_MESSAGE');
	      default:
	        return '';
	    }
	  }
	  getConfirmButtonText(action) {
	    switch (action) {
	      case 'restore':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_BUTTON');
	      case 'confirm':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_BUTTON');
	      case 'delete':
	      case 'deleteOrFire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_BUTTON');
	      case 'fire':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_BUTTON');
	      case 'deactivateInvited':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON');
	      case 'decline':
	        return main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_BUTTON');
	      default:
	        return null;
	    }
	  }
	}
	GridManager.instances = [];

	let _$2 = t => t,
	  _t$2;
	var _updateData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateData");
	var _onClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClick");
	var _actionFactory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionFactory");
	var _inviteAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inviteAction");
	var _acceptAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("acceptAction");
	class ActivityField extends BaseField {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _acceptAction, {
	      value: _acceptAction2
	    });
	    Object.defineProperty(this, _inviteAction, {
	      value: _inviteAction2
	    });
	    Object.defineProperty(this, _actionFactory, {
	      value: _actionFactory2
	    });
	    Object.defineProperty(this, _onClick, {
	      value: _onClick2
	    });
	    Object.defineProperty(this, _updateData, {
	      value: _updateData2
	    });
	  }
	  render(params) {
	    var _params$action;
	    let title = '';
	    let color = '';
	    switch ((_params$action = params.action) != null ? _params$action : 'invite') {
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
	    const counter = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="ui-counter user-grid_invitation-counter">
				<div class="ui-counter-inner">1</div>
			</div>
		`));
	    main_core.Dom.append(counter, this.getFieldNode());
	    const button = new BX.UI.Button({
	      text: title,
	      color,
	      noCaps: true,
	      size: BX.UI.Button.Size.EXTRA_SMALL,
	      tag: BX.UI.Button.Tag.INPUT,
	      round: true,
	      onclick: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onClick)[_onClick](params, button);
	      }
	    });
	    button.renderTo(this.getFieldNode());
	  }
	}
	function _updateData2(data) {
	  var _GridManager$getInsta;
	  if (!main_core.Type.isStringFilled(data.get('newEmail')) && !main_core.Type.isBoolean(data.get('newPhone'))) {
	    top.console.error('Empty new email or phone');
	    return;
	  }
	  const row = (_GridManager$getInsta = GridManager.getInstance(this.gridId).getGrid()) == null ? void 0 : _GridManager$getInsta.getRows().getById(this.userId);
	  row == null ? void 0 : row.stateLoad();
	  GridManager.reinviteCloudAction(data).then(response => {
	    row == null ? void 0 : row.update();
	    row == null ? void 0 : row.stateUnload();
	  });
	}
	function _onClick2(params, button) {
	  if (!params.enabled) {
	    const popup = BX.PopupWindowManager.create('intranet-user-grid-invitation-disabled', null, {
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
	    babelHelpers.classPrivateFieldLooseBase(this, _actionFactory)[_actionFactory](params.action).call(this, params, button);
	  }
	}
	function _actionFactory2(action) {
	  switch (action) {
	    case 'accept':
	      return babelHelpers.classPrivateFieldLooseBase(this, _acceptAction)[_acceptAction];
	      break;
	    case 'invite':
	      return babelHelpers.classPrivateFieldLooseBase(this, _inviteAction)[_inviteAction];
	    default:
	      return babelHelpers.classPrivateFieldLooseBase(this, _inviteAction)[_inviteAction];
	      break;
	  }
	}
	function _inviteAction2(params, button) {
	  if (params.isCloud === true) {
	    var _ref, _params$email;
	    const reinvitePopup = new intranet_reinvite.ReinvitePopup({
	      userId: params.userId,
	      formType: params.email ? intranet_reinvite.FormType.EMAIL : intranet_reinvite.FormType.PHONE,
	      bindElement: button.getContainer(),
	      inputValue: (_ref = (_params$email = params.email) != null ? _params$email : params.phoneNumber) != null ? _ref : '',
	      transport: babelHelpers.classPrivateFieldLooseBase(this, _updateData)[_updateData].bind(params)
	      // transport: (data) => {
	      // 	if (!Type.isStringFilled(data.get('newEmail')) && !Type.isBoolean(data.get('newPhone'))) {
	      // 		top.console.error('Empty new email or phone');
	      // 		return;
	      // 	}
	      // 	if ((data.get('newEmail') === '' || data.get('newEmail') === null) && (data.get('newPhone') === '' || data.get('newPhone') === null)) {
	      // 		top.console.error('Empty new email or phone');
	      // 		return;
	      // 	}
	      // 	this.#updateData(data);
	      // },
	    });
	    //This is a hack. When the row is updated, a new button is created.
	    reinvitePopup.getPopup().setBindElement(button.getContainer());
	    reinvitePopup.show();
	  } else {
	    button.setWaiting(true);
	    GridManager.reinviteAction(params.userId, params.isExtranet).then(() => {
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

	let _$3 = t => t,
	  _t$3,
	  _t2$2,
	  _t3$1;
	class DepartmentField extends BaseField {
	  render(params) {
	    main_core.Dom.addClass(this.getFieldNode(), 'user-grid_department-container');
	    if (params.departments.length === 0 && params.canEdit) {
	      // TODO: add department button
	      return;
	      const onclick = () => {
	        const dialog = new ui_entitySelector.Dialog({
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
	      const addButton = main_core.Tag.render(_t$3 || (_t$3 = _$3`
				<div class="user-grid_department-btn" onclick="${0}">
					<div class="user-grid_department-icon-container">
						<div class="ui-icon-set --plus-30" style="--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: #2fc6f6;"></div>
					</div>
					<div class="user-grid_department-name-container">
						${0}
					</div>
				</div>
			`), onclick, main_core.Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADD_DEPARTMENT'));
	      this.appendToFieldNode(addButton);
	    } else {
	      Object.values(params.departments).forEach(department => {
	        const isSelected = department.id === params.selectedDepartment;
	        const onclick = () => {
	          GridManager.setFilter({
	            gridId: this.getGridId(),
	            filter: {
	              DEPARTMENT: isSelected ? '' : department.id,
	              DEPARTMENT_label: isSelected ? '' : department.name
	            }
	          });
	        };
	        const button = main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
					<div
						class="user-grid_department-btn ${0}"
						onclick="${0}"
						>
						<div class="user-grid_department-name-container">
							${0}
						</div>
					</div>
				`), isSelected ? '--selected' : '', onclick, department.name);
	        if (isSelected) {
	          main_core.Dom.append(main_core.Tag.render(_t3$1 || (_t3$1 = _$3`
						<div class="user-grid_department-btn-remove ui-icon-set --cross-60"></div>
					`)), button);
	        }
	        this.appendToFieldNode(button);
	      });
	    }
	  }
	}

	/**
	 * @abstract
	 */
	class BaseAction {
	  /**
	   * @abstract
	   */
	  static getActionId() {
	    throw new Error('not implemented');
	  }

	  /**
	   * @abstract
	   */
	  getAjaxMethod() {
	    throw new Error('not implemented');
	  }
	  constructor(params) {
	    var _params$showPopups;
	    this.grid = params.grid;
	    this.userFilter = params.filter;
	    this.selectedUsers = params.selectedUsers;
	    this.showPopups = (_params$showPopups = params.showPopups) != null ? _params$showPopups : true;
	    this.isCloud = params.isCloud;
	  }
	  execute() {
	    const confirmationPopup = this.showPopups ? this.getConfirmationPopup() : null;
	    if (confirmationPopup) {
	      confirmationPopup.setOkCallback(() => {
	        this.sendActionRequest();
	        confirmationPopup.close();
	      });
	      confirmationPopup.show();
	    } else {
	      this.sendActionRequest();
	    }
	  }
	  getConfirmationPopup() {
	    return null;
	  }
	  sendActionRequest() {
	    var _this$selectedUsers;
	    this.grid.tableFade();
	    const selectedRows = (_this$selectedUsers = this.selectedUsers) != null ? _this$selectedUsers : this.grid.getRows().getSelectedIds();
	    const isSelectedAllRows = this.grid.getRows().isAllSelected() ? 'Y' : 'N';
	    BX.ajax.runAction(this.getAjaxMethod(), {
	      data: {
	        fields: {
	          userIds: selectedRows,
	          isSelectedAllRows,
	          filter: this.userFilter
	        }
	      }
	    }).then(result => this.handleSuccess(result)).catch(result => this.handleError(result));
	  }
	  handleSuccess(result) {
	    this.grid.reload();
	    if (this.showPopups) {
	      const {
	        skippedActiveUsers,
	        skippedFiredUsers
	      } = result.data;
	      if (skippedActiveUsers && Object.keys(skippedActiveUsers).length > 0) {
	        this.showActiveUsersPopup(skippedActiveUsers);
	      } else if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0) {
	        this.showFiredUsersPopup(skippedFiredUsers);
	      }
	    }
	  }
	  handleError(result) {
	    this.grid.tableUnfade();
	    this.unselectRows(this.grid);
	    console.error(result);
	    if (this.showPopups && result.errors && result.errors.length > 0) {
	      const errorMessage = result.errors.map(item => {
	        return item.message;
	      }).join(', ');
	      ui_dialogs_messagebox.MessageBox.show({
	        message: errorMessage,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	        yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	        onYes: messageBox => {
	          messageBox.close();
	        }
	      });
	    }
	  }
	  showActiveUsersPopup(activeUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage(this.getSkippedUsersTitleCode()),
	      message: this.getMessageWithProfileNames(this.getSkippedUsersMessageCode(), activeUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	      }
	    });
	  }
	  showFiredUsersPopup(firedUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE', firedUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	      }
	    });
	  }
	  getMessageWithProfileNames(messageCode, users) {
	    const maxDisplayCount = 5;
	    const userValues = Object.values(users);
	    const displayedNames = userValues.slice(0, maxDisplayCount).map(user => user.fullName);
	    const remainingCount = userValues.length - maxDisplayCount;
	    const namesString = displayedNames.join(', ');
	    if (displayedNames.length < 2 && remainingCount < 1) {
	      return main_core.Loc.getMessage(`${messageCode}_SINGLE`, {
	        '#USER#': namesString
	      });
	    }
	    if (remainingCount > 0) {
	      return main_core.Loc.getMessage(`${messageCode}_REMAINING`, {
	        '#USER_LIST#': namesString,
	        '#USER_REMAINING#': remainingCount
	      });
	    }
	    return main_core.Loc.getMessage(messageCode, {
	      '#USER_LIST#': namesString
	    });
	  }
	  getSkippedUsersTitleCode() {
	    return '';
	  }
	  getSkippedUsersMessageCode() {
	    return '';
	  }
	  unselectRows(grid) {
	    grid.getRows().unselectAll();
	    grid.updateCounterDisplayed();
	    grid.updateCounterSelected();
	    grid.disableActionsPanel();
	    BX.onCustomEvent(window, 'Grid::allRowsUnselected', []);
	  }
	}

	class FireAction extends BaseAction {
	  static getActionId() {
	    return 'fire';
	  }
	  getConfirmationPopup() {
	    return new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE'),
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_TITLE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_BUTTON')
	    });
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupFire';
	  }
	  getSkippedUsersMessageCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE';
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE';
	  }
	}

	class DeleteAction extends BaseAction {
	  static getActionId() {
	    return 'delete';
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupDelete';
	  }
	  getConfirmationPopup() {
	    return new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE'),
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_TITLE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_BUTTON')
	    });
	  }
	  showActiveUsersPopup(activeUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE', activeUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	        new FireAction({
	          selectedUsers: Object.keys(activeUsers),
	          grid: this.grid,
	          filter: this.userFilter,
	          showPopups: false
	        }).execute();
	      },
	      onNo: () => {
	        this.grid.reload();
	      }
	    });
	  }
	  showFiredUsersPopup(firedUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_MESSAGE', firedUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	      }
	    });
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE';
	  }
	  getSkippedUsersMessageCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE';
	  }
	}

	class ConfirmAction extends BaseAction {
	  static getActionId() {
	    return 'confirm';
	  }
	  getConfirmationPopup() {
	    return new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE'),
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_TITLE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON')
	    });
	  }
	  handleSuccess(result) {
	    this.grid.reload();
	    if (this.showPopups) {
	      const {
	        skippedFiredUsers
	      } = result.data;
	      if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0) {
	        this.showFiredUsersPopup(skippedFiredUsers);
	      }
	    }
	  }
	  showFiredUsersPopup(firedUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_MESSAGE', firedUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	      }
	    });
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupConfirm';
	  }
	  getSkippedUsersMessageCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	  }
	}

	class DeclineAction extends BaseAction {
	  static getActionId() {
	    return 'decline';
	  }
	  getConfirmationPopup() {
	    return new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE'),
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_TITLE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_BUTTON')
	    });
	  }
	  showActiveUsersPopup(activeUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE', activeUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	        new FireAction({
	          selectedUsers: Object.keys(activeUsers),
	          grid: this.grid,
	          filter: this.userFilter,
	          showPopups: false
	        }).execute();
	      }
	    });
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupDecline';
	  }
	  getSkippedUsersMessageCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	  }
	}

	class ReinviteAction extends BaseAction {
	  static getActionId() {
	    return 'reInvite';
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupReInvite';
	  }
	  handleSuccess(result) {
	    this.grid.tableUnfade();
	    const {
	      skippedActiveUsers,
	      skippedFiredUsers,
	      skippedWaitingUsers
	    } = result.data;
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
	      (_BX$Bitrix = BX.Bitrix24) == null ? void 0 : (_BX$Bitrix$EmailConfi = _BX$Bitrix.EmailConfirmation) == null ? void 0 : _BX$Bitrix$EmailConfi.showPopupDispatched();
	    }
	    this.unselectRows(this.grid);
	  }
	  showWaitingUsersPopup(waitingUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_MESSAGE', waitingUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	        new ConfirmAction({
	          grid: this.grid,
	          filter: this.userFilter,
	          selectedUsers: Object.keys(waitingUsers),
	          showPopups: false
	        }).execute();
	      }
	    });
	  }
	  showFiredUsersPopup(firedUsers) {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_TITLE'),
	      message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_MESSAGE', firedUsers),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES,
	      yesCaption: main_core.Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
	      onYes: messageBox => {
	        messageBox.close();
	      }
	    });
	  }
	  getSkippedUsersMessageCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	  }
	}

	class CreateChatAction extends BaseAction {
	  static getActionId() {
	    return 'createChat';
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.createChat';
	  }
	  handleSuccess(result) {
	    this.grid.tableUnfade();
	    const chatId = result.data;
	    im_public.Messenger.openChat(`chat${chatId}`);
	    this.unselectRows(this.grid);
	  }
	}

	let _$4 = t => t,
	  _t$4;
	class ChangeDepartmentAction extends BaseAction {
	  static getActionId() {
	    return 'changeDepartment';
	  }
	  getAjaxMethod() {
	    return 'intranet.controller.user.userlist.groupChangeDepartment';
	  }
	  execute() {
	    const saveButton = new BX.UI.SaveButton({
	      onclick: () => {
	        const selectedIds = dialog.getSelectedItems().map(item => item.id);
	        dialog.hide();
	        if (selectedIds.length > 0) {
	          this.sendChangeDepartmentRequest(selectedIds);
	        } else {
	          this.unselectRows(this.grid);
	        }
	      },
	      size: BX.UI.Button.Size.SMALL
	    });
	    const cancelButton = new BX.UI.CancelButton({
	      onclick: () => {
	        dialog.hide();
	      },
	      size: BX.UI.Button.Size.SMALL
	    });
	    const footer = main_core.Tag.render(_t$4 || (_t$4 = _$4`<span></span>`));
	    saveButton.renderTo(footer);
	    cancelButton.renderTo(footer);
	    const dialog = new ui_entitySelector.Dialog({
	      dropdownMode: true,
	      enableSearch: true,
	      compactView: true,
	      multiple: true,
	      footer,
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
	  sendChangeDepartmentRequest(departmentIds) {
	    var _this$selectedUsers;
	    this.grid.tableFade();
	    const selectedRows = (_this$selectedUsers = this.selectedUsers) != null ? _this$selectedUsers : this.grid.getRows().getSelectedIds();
	    const isSelectedAllRows = this.grid.getRows().isAllSelected() ? 'Y' : 'N';
	    BX.ajax.runAction(this.getAjaxMethod(), {
	      data: {
	        fields: {
	          userIds: selectedRows,
	          isSelectedAllRows,
	          filter: this.userFilter,
	          departmentIds
	        }
	      }
	    }).then(result => this.handleSuccess(result)).catch(result => this.handleError(result));
	  }
	  getSkippedUsersTitleCode() {
	    return 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_TITLE';
	  }
	  getSkippedUsersMessageCode() {
	    return this.isCloud ? 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE_CLOUD' : 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE';
	  }
	}

	const ACTIONS = [DeleteAction, FireAction, ConfirmAction, DeclineAction, ReinviteAction, CreateChatAction, ChangeDepartmentAction];
	class ActionFactory {
	  static createAction(actionId, params) {
	    const ActionClass = ACTIONS.find(action => action.getActionId() === actionId);
	    if (!ActionClass) {
	      throw new Error(`Unknown actionId: ${actionId}`);
	    }
	    return new ActionClass(params);
	  }
	}

	class Panel {
	  static executeAction(params) {
	    try {
	      var _BX$Main$gridManager$;
	      const action = ActionFactory.createAction(params.actionId, {
	        grid: (_BX$Main$gridManager$ = BX.Main.gridManager.getById(params.gridId)) == null ? void 0 : _BX$Main$gridManager$.instance,
	        filter: params.filter,
	        isCloud: params.isCloud
	      });
	      action.execute();
	    } catch (error) {
	      console.error('Error executing action:', error);
	    }
	  }
	}

	exports.BaseField = BaseField;
	exports.PhotoField = PhotoField;
	exports.FullNameField = FullNameField;
	exports.EmployeeField = EmployeeField;
	exports.ActivityField = ActivityField;
	exports.DepartmentField = DepartmentField;
	exports.GridManager = GridManager;
	exports.Panel = Panel;

}((this.BX.Intranet.UserList = this.BX.Intranet.UserList || {}),BX.UI,BX.UI,BX.UI.FormElements,BX.UI,BX.Intranet.Reinvite,BX,BX.UI.Dialogs,BX.Messenger.v2.Lib,BX.UI.EntitySelector,BX));
//# sourceMappingURL=grid.bundle.js.map

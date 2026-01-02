/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_popup,main_core,ui_entitySelector,humanresources_companyStructure_utils,ui_notification,humanresources_companyStructure_chartStore,humanresources_companyStructure_api) {
	'use strict';

	const UserManagementDialogActions = {
	  getDepartmentName: nodeId => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const targetDepartment = departments.get(nodeId);
	    if (!targetDepartment) {
	      return '';
	    }
	    return targetDepartment.name;
	  }
	};

	const UserManagementDialogAPI = {
	  moveUsersToDepartment: (nodeId, userIds) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
	      nodeId,
	      userIds
	    });
	  },
	  addUsersToDepartment: (nodeId, userIds, role) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.addUserMember', {
	      nodeId,
	      userIds,
	      roleXmlId: role
	    });
	  }
	};

	const allowedDialogTypes = ['add', 'move'];

	let _ = t => t,
	  _t;
	const disabledButtonClass = 'ui-btn-disabled';
	var _handleOnTagAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagAdd");
	var _handleOnTagRemove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagRemove");
	var _onUserToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUserToggle");
	var _toggleAddButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleAddButton");
	var _setConfirmButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setConfirmButtonText");
	var _saveUsers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveUsers");
	class BaseUserManagementDialogFooter extends ui_entitySelector.BaseFooter {
	  constructor(tab, options) {
	    var _this$getOption, _this$getOption2, _this$getOption3;
	    super(tab, options);
	    Object.defineProperty(this, _saveUsers, {
	      value: _saveUsers2
	    });
	    Object.defineProperty(this, _setConfirmButtonText, {
	      value: _setConfirmButtonText2
	    });
	    Object.defineProperty(this, _toggleAddButton, {
	      value: _toggleAddButton2
	    });
	    Object.defineProperty(this, _onUserToggle, {
	      value: _onUserToggle2
	    });
	    Object.defineProperty(this, _handleOnTagRemove, {
	      value: _handleOnTagRemove2
	    });
	    Object.defineProperty(this, _handleOnTagAdd, {
	      value: _handleOnTagAdd2
	    });
	    this.nodeId = this.getOption('nodeId');
	    if (!main_core.Type.isInteger(this.nodeId)) {
	      throw new TypeError("Invalid argument 'nodeId'. An integer value was expected.");
	    }
	    this.entityType = (_this$getOption = this.getOption('entityType')) != null ? _this$getOption : humanresources_companyStructure_utils.EntityTypes.team;
	    this.memberRoles = humanresources_companyStructure_api.getMemberRoles(this.entityType);
	    this.role = (_this$getOption2 = this.getOption('role')) != null ? _this$getOption2 : this.memberRoles.employee;
	    const type = (_this$getOption3 = this.getOption('type')) != null ? _this$getOption3 : '';
	    if (main_core.Type.isString(type) && allowedDialogTypes.includes(type)) {
	      this.type = type;
	    } else {
	      throw new TypeError(`Invalid argument 'type'. Expected one of: ${allowedDialogTypes.join(', ')}`);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setConfirmButtonText)[_setConfirmButtonText]();
	    const selectedItems = this.getDialog().getSelectedItems();
	    this.userCount = selectedItems.length;
	    this.users = [];
	    selectedItems.forEach(item => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onUserToggle)[_onUserToggle](item);
	    });
	    this.getDialog().subscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagAdd)[_handleOnTagAdd].bind(this));
	    this.getDialog().subscribe('Item:onDeselect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagRemove)[_handleOnTagRemove].bind(this));
	  }
	  render() {
	    var _this$confirmButtonTe;
	    const {
	      footer,
	      footerAddButton,
	      footerCloseButton
	    } = main_core.Tag.render(_t || (_t = _`
			<div ref="footer" class="hr-user-management-dialog__footer">
				<button ref="footerAddButton" class="ui-btn ui-btn ui-btn-sm ui-btn-primary ${0} ui-btn-round hr-user-management-dialog__footer-btn-width">
					${0}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-user-management-dialog__footer-btn-width">
					${0}
				</button>
			</div>
		`), this.users.length === 0 ? disabledButtonClass : '', (_this$confirmButtonTe = this.confirmButtonText) != null ? _this$confirmButtonTe : '', main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_CANCEL_BUTTON'));
	    this.footerAddButton = footerAddButton;
	    main_core.Event.bind(footerCloseButton, 'click', event => {
	      this.dialog.hide();
	    });
	    main_core.Event.bind(footerAddButton, 'click', event => {
	      const users = this.dialog.getSelectedItems();
	      const userIds = users.map(item => item.getId());
	      if (userIds.length > 0) {
	        main_core.Dom.addClass(footerAddButton, 'ui-btn-wait');
	        this.action(userIds);
	      }
	    });
	    return footer;
	  }
	  destroyDialog() {
	    this.isInProcess = false;
	    this.getDialog().destroy();
	  }
	  async action(userIds) {
	    if (!this.userCount || this.isInProcess) {
	      return;
	    }
	    this.isInProcess = true;
	    const departmentUserIds = this.type === 'move' ? {
	      [this.memberRoles.employee]: userIds
	    } : userIds;
	    const data = await babelHelpers.classPrivateFieldLooseBase(this, _saveUsers)[_saveUsers](departmentUserIds).catch(() => {});
	    if (!data) {
	      this.destroyDialog();
	      return;
	    }
	    if (this.type === 'add') {
	      var _data$userCount, _this$role;
	      humanresources_companyStructure_chartStore.UserService.addUsersToEntity(this.nodeId, this.users, (_data$userCount = data.userCount) != null ? _data$userCount : 0, (_this$role = this.role) != null ? _this$role : this.memberRoles.employee);
	    }
	    if (this.type === 'move') {
	      var _data$userCount2, _data$updatedDepartme;
	      humanresources_companyStructure_chartStore.UserService.moveUsersToEntity(this.nodeId, this.users, (_data$userCount2 = data.userCount) != null ? _data$userCount2 : 0, (_data$updatedDepartme = data.updatedDepartmentIds) != null ? _data$updatedDepartme : []);
	    }
	    const notificationCode = this.getNotificationMessageCode();
	    if (notificationCode) {
	      this.showNotification(notificationCode);
	    }
	    this.destroyDialog();
	  }
	  showNotification(messageCode) {
	    const departmentName = UserManagementDialogActions.getDepartmentName(this.nodeId);
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(main_core.Loc.getMessage(messageCode, {
	        '#DEPARTMENT#': departmentName
	      })),
	      autoHideDelay: 2000
	    });
	  }

	  // eslint-disable-next-line sonarjs/cognitive-complexity
	  getNotificationMessageCode() {
	    if (this.type === 'add') {
	      if (this.users.length > 1) {
	        if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	          this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_USER_ADD_EMPLOYEES_MESSAGE');
	        } else {
	          this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_ADD_EMPLOYEES_MESSAGE');
	        }
	      }
	      if (this.users.length === 1) {
	        if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	          this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_USER_ADD_EMPLOYEE_MESSAGE');
	        } else {
	          this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_ADD_EMPLOYEE_MESSAGE');
	        }
	      }
	    }
	    if (this.type === 'move') {
	      if (this.users.length > 1) {
	        this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_MOVE_EMPLOYEES_MESSAGE');
	      }
	      if (this.users.length === 1) {
	        this.showNotification('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_MOVE_EMPLOYEE_MESSAGE');
	      }
	    }
	    return null;
	  }
	}
	function _handleOnTagAdd2(event) {
	  const {
	    item
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _onUserToggle)[_onUserToggle](item);
	}
	function _handleOnTagRemove2(event) {
	  const {
	    item
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _onUserToggle)[_onUserToggle](item, false);
	}
	function _onUserToggle2(item, isSelected = true) {
	  if (!isSelected) {
	    this.users = this.users.filter(user => user.id !== item.id);
	    this.userCount -= 1;
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleAddButton)[_toggleAddButton]();
	    return;
	  }
	  const userData = humanresources_companyStructure_utils.getUserDataBySelectorItem(item, this.role);
	  this.users = [...this.users, userData];
	  this.userCount += 1;
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleAddButton)[_toggleAddButton]();
	}
	function _toggleAddButton2() {
	  if (this.userCount === 0) {
	    main_core.Dom.addClass(this.footerAddButton, disabledButtonClass);
	    return;
	  }
	  main_core.Dom.removeClass(this.footerAddButton, disabledButtonClass);
	}
	function _setConfirmButtonText2() {
	  if (this.type === 'move') {
	    this.confirmButtonText = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_CONFIRM_BUTTON');
	    return;
	  }
	  if (this.type === 'add') {
	    this.confirmButtonText = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_USER_CONFIRM_BUTTON');
	    return;
	  }
	  this.confirmButtonText = '';
	}
	function _saveUsers2(departmentUserIds) {
	  if (this.type === 'move') {
	    return UserManagementDialogAPI.moveUsersToDepartment(this.nodeId, departmentUserIds);
	  }
	  return UserManagementDialogAPI.addUsersToDepartment(this.nodeId, departmentUserIds, this.role);
	}

	let _$1 = t => t,
	  _t$1,
	  _t2,
	  _t3,
	  _t4,
	  _t5;
	var _addRoleSwitcher = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addRoleSwitcher");
	var _toggleRoleSwitcherMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleRoleSwitcherMenu");
	var _changeRole = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeRole");
	var _getHeadRoleText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHeadRoleText");
	var _getDeputyRoleText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDeputyRoleText");
	class BaseUserManagementDialogHeader extends ui_entitySelector.BaseHeader {
	  constructor(context, options) {
	    var _this$getOption, _this$getOption2, _this$getOption3, _this$getOption4, _this$getOption5;
	    super(context, options);
	    Object.defineProperty(this, _getDeputyRoleText, {
	      value: _getDeputyRoleText2
	    });
	    Object.defineProperty(this, _getHeadRoleText, {
	      value: _getHeadRoleText2
	    });
	    Object.defineProperty(this, _changeRole, {
	      value: _changeRole2
	    });
	    Object.defineProperty(this, _toggleRoleSwitcherMenu, {
	      value: _toggleRoleSwitcherMenu2
	    });
	    Object.defineProperty(this, _addRoleSwitcher, {
	      value: _addRoleSwitcher2
	    });
	    this.tiltle = main_core.Text.encode((_this$getOption = this.getOption('title')) != null ? _this$getOption : '');
	    this.description = main_core.Text.encode((_this$getOption2 = this.getOption('description')) != null ? _this$getOption2 : '');
	    this.memberRoles = (_this$getOption3 = this.getOption('memberRoles')) != null ? _this$getOption3 : humanresources_companyStructure_api.memberRoles;
	    this.role = (_this$getOption4 = this.getOption('role')) != null ? _this$getOption4 : this.memberRoles.employee;
	    this.entityType = (_this$getOption5 = this.getOption('entityType')) != null ? _this$getOption5 : humanresources_companyStructure_utils.EntityTypes.department;
	  }
	  render() {
	    const {
	      header,
	      headerCloseButton
	    } = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div ref="header" class="hr-user-management-dialog__header">
				<div ref="headerCloseButton" class="hr-user-management-dialog__header-close_button"></div>
				<span class="hr-user-management-dialog__header-title">
					${0}
				</span>
			</div>
		`), this.tiltle);
	    main_core.Event.bind(headerCloseButton, 'click', () => {
	      this.getDialog().hide();
	    });
	    this.header = header;
	    if (this.role === this.memberRoles.employee) {
	      const employeeAddSubtitle = main_core.Tag.render(_t2 || (_t2 = _$1`
				<span class="hr-user-management-dialog__header-description">
					${0}
				</span>
			`), this.description);
	      main_core.Dom.append(employeeAddSubtitle, this.header);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _addRoleSwitcher)[_addRoleSwitcher]();
	    }
	    return header;
	  }
	}
	function _addRoleSwitcher2() {
	  const {
	    roleSwitcherContainer,
	    roleSwitcher
	  } = main_core.Tag.render(_t3 || (_t3 = _$1`
			<div ref="roleSwitcherContainer" class="hr-user-management-dialog__role_switcher-container">
				<span class="hr-user-management-dialog__role_switcher_title">
					${0}
					</span>
				<div ref="roleSwitcher" class="hr-user-management-dialog__role_switcher">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ROLE_PICKER_TEXT'), babelHelpers.classPrivateFieldLooseBase(this, _getHeadRoleText)[_getHeadRoleText]());
	  main_core.Dom.append(roleSwitcherContainer, this.header);
	  this.roleSwitcher = roleSwitcher;
	  main_core.Event.bind(this.roleSwitcher, 'click', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleRoleSwitcherMenu)[_toggleRoleSwitcherMenu]();
	  });
	}
	function _toggleRoleSwitcherMenu2() {
	  const roleSwitcherId = `${this.getDialog().id}-role-switcher`;
	  const oldRoleSwitcherMenu = main_popup.PopupManager.getPopupById(roleSwitcherId);
	  if (oldRoleSwitcherMenu) {
	    oldRoleSwitcherMenu.destroy();
	    return;
	  }
	  const roleSwitcherMenu = new main_popup.Menu({
	    id: roleSwitcherId,
	    bindElement: this.roleSwitcher,
	    autoHide: true,
	    closeByEsc: true,
	    maxWidth: 263,
	    events: {
	      onPopupDestroy: () => {
	        main_core.Dom.removeClass(this.roleSwitcher, '--focused');
	      }
	    }
	  });
	  const menuItems = [{
	    html: main_core.Tag.render(_t4 || (_t4 = _$1`
					<div 
						data-test-id="hr-company-structure_user-management-dialog__role-switcher-head"
					>
						${0}
					</div>
				`), babelHelpers.classPrivateFieldLooseBase(this, _getHeadRoleText)[_getHeadRoleText]()),
	    onclick: () => {
	      this.roleSwitcher.innerText = babelHelpers.classPrivateFieldLooseBase(this, _getHeadRoleText)[_getHeadRoleText]();
	      babelHelpers.classPrivateFieldLooseBase(this, _changeRole)[_changeRole](this.memberRoles.head);
	      roleSwitcherMenu.destroy();
	    }
	  }, {
	    html: main_core.Tag.render(_t5 || (_t5 = _$1`
					<div 
						data-test-id="hr-company-structure_user-management-dialog__role-switcher-deputy"
					>
						${0}
					</div>
				`), babelHelpers.classPrivateFieldLooseBase(this, _getDeputyRoleText)[_getDeputyRoleText]()),
	    onclick: () => {
	      this.roleSwitcher.innerText = babelHelpers.classPrivateFieldLooseBase(this, _getDeputyRoleText)[_getDeputyRoleText]();
	      babelHelpers.classPrivateFieldLooseBase(this, _changeRole)[_changeRole](this.memberRoles.deputyHead);
	      roleSwitcherMenu.destroy();
	    }
	  }];
	  menuItems.forEach(menuItem => roleSwitcherMenu.addMenuItem(menuItem));
	  if (roleSwitcherMenu.isShown) {
	    roleSwitcherMenu.destroy();
	    return;
	  }
	  roleSwitcherMenu.show();
	  main_core.Dom.addClass(this.roleSwitcher, '--focused');
	}
	function _changeRole2(role) {
	  const currentFooterOptions = this.getDialog().getFooter().getOptions();
	  currentFooterOptions.role = role;
	  this.getDialog().setFooter(BaseUserManagementDialogFooter, currentFooterOptions);
	}
	function _getHeadRoleText2() {
	  return this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_TEAM_HEAD_ROLE_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_HEAD_ROLE_TITLE');
	}
	function _getDeputyRoleText2() {
	  return this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_TEAM_DEPUTY_ROLE_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_DEPUTY_ROLE_TITLE');
	}

	const dialogId = 'hr-user-management-dialog';
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _nodeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nodeId");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _role = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("role");
	var _entityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _memberRoles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("memberRoles");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _getTitleByTypeAndRoleAndEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitleByTypeAndRoleAndEntity");
	var _getDescriptionByTypeRoleAndEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDescriptionByTypeRoleAndEntity");
	class UserManagementDialog {
	  constructor(options) {
	    Object.defineProperty(this, _getDescriptionByTypeRoleAndEntity, {
	      value: _getDescriptionByTypeRoleAndEntity2
	    });
	    Object.defineProperty(this, _getTitleByTypeAndRoleAndEntity, {
	      value: _getTitleByTypeAndRoleAndEntity2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _nodeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _role, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _memberRoles, {
	      writable: true,
	      value: void 0
	    });
	    if (main_core.Type.isInteger(options.nodeId)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _nodeId)[_nodeId] = options.nodeId;
	    } else {
	      throw new TypeError("Invalid argument 'nodeId'. An integer value was expected.");
	    }
	    if (main_core.Type.isString(options.type) && allowedDialogTypes.includes(options.type)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = options.type;
	    } else {
	      throw new TypeError(`Invalid argument 'type'. Expected one of: ${allowedDialogTypes.join(', ')}`);
	    }
	    if (main_core.Type.isString(options.entityType) && Object.values(humanresources_companyStructure_utils.EntityTypes).includes(options.entityType)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] = options.entityType;
	      if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === humanresources_companyStructure_utils.EntityTypes.team) {
	        babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = 'add';
	      }
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] = humanresources_companyStructure_utils.EntityTypes.department;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles] = humanresources_companyStructure_api.getMemberRoles(babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]);
	    if (Object.values(babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles]).includes(options.role)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _role)[_role] = options.role;
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _role)[_role] = babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee;
	    }
	    this.id = `${dialogId}-${babelHelpers.classPrivateFieldLooseBase(this, _type)[_type]}`;
	    this.title = babelHelpers.classPrivateFieldLooseBase(this, _getTitleByTypeAndRoleAndEntity)[_getTitleByTypeAndRoleAndEntity](babelHelpers.classPrivateFieldLooseBase(this, _type)[_type], babelHelpers.classPrivateFieldLooseBase(this, _role)[_role], babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]);
	    this.description = babelHelpers.classPrivateFieldLooseBase(this, _getDescriptionByTypeRoleAndEntity)[_getDescriptionByTypeRoleAndEntity](babelHelpers.classPrivateFieldLooseBase(this, _type)[_type], babelHelpers.classPrivateFieldLooseBase(this, _role)[_role], babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]);
	    babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	  }
	  static openDialog(options) {
	    const instance = new UserManagementDialog(options);
	    instance.show();
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].show();
	  }
	}
	function _createDialog2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = new ui_entitySelector.Dialog({
	    id: this.id,
	    width: 400,
	    height: 511,
	    multiple: true,
	    cacheable: false,
	    dropdownMode: true,
	    compactView: false,
	    enableSearch: true,
	    showAvatars: true,
	    autoHide: false,
	    header: BaseUserManagementDialogHeader,
	    headerOptions: {
	      title: this.title,
	      role: babelHelpers.classPrivateFieldLooseBase(this, _role)[_role],
	      description: this.description,
	      memberRoles: babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles],
	      entityType: babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]
	    },
	    footer: BaseUserManagementDialogFooter,
	    footerOptions: {
	      nodeId: babelHelpers.classPrivateFieldLooseBase(this, _nodeId)[_nodeId],
	      role: babelHelpers.classPrivateFieldLooseBase(this, _role)[_role],
	      type: babelHelpers.classPrivateFieldLooseBase(this, _type)[_type],
	      entityType: babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType]
	    },
	    popupOptions: {
	      overlay: {
	        opacity: 40
	      }
	    },
	    entities: [{
	      id: 'user',
	      options: {
	        intranetUsersOnly: true,
	        inviteEmployeeLink: false
	      }
	    }]
	  });
	}
	function _getTitleByTypeAndRoleAndEntity2(type, role, entityType) {
	  if (type === 'add' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee && entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_EMPLOYEE_TITLE');
	  }
	  if (type === 'move' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_TITLE');
	  }
	  if (type === 'add' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_TITLE');
	  }
	  if (type === 'add' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].head && entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_HEAD_TITLE');
	  }
	  if (type === 'add' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].head) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_HEAD_TITLE');
	  }
	  return '';
	}
	function _getDescriptionByTypeRoleAndEntity2(type, role, entityType) {
	  if (type === 'add' && entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_TEAM_EMPLOYEE_DESCRIPTION');
	  }
	  if (type === 'move' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_MOVE_USER_FROM_DESCRIPTION');
	  }
	  if (type === 'add' && role === babelHelpers.classPrivateFieldLooseBase(this, _memberRoles)[_memberRoles].employee) {
	    return main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_MANAGEMENT_DIALOG_ADD_EMPLOYEE_DESCRIPTION');
	  }
	  return '';
	}

	exports.UserManagementDialog = UserManagementDialog;
	exports.UserManagementDialogAPI = UserManagementDialogAPI;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Main,BX,BX.UI.EntitySelector,BX.Humanresources.CompanyStructure,BX,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=user-management-dialog.bundle.js.map

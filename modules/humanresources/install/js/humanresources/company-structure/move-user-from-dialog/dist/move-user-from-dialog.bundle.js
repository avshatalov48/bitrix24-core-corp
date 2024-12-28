/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_companyStructure_chartStore,humanresources_companyStructure_utils,main_core,humanresources_companyStructure_api,ui_entitySelector,ui_notification) {
	'use strict';

	let _ = t => t,
	  _t;
	class MoveFromDialogHeader extends ui_entitySelector.BaseHeader {
	  render() {
	    const {
	      header,
	      headerCloseButton
	    } = main_core.Tag.render(_t || (_t = _`
			<div ref="header" class="hr-move-user-from-dialog__header">
				<div ref="headerCloseButton" class="hr-move-user-from-dialog__header-close_button"></div>
				<div class="hr-move-user-from__header-text-container">
					<span class="hr-move-user-from-dialog__header-title">
						${0}
					</span>
					<span class="hr-move-user-from-dialog__header-description">
						${0}
					</span>
				</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_TITLE'), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_DESCRIPTION'));
	    main_core.Event.bind(headerCloseButton, 'click', event => {
	      this.getDialog().hide();
	    });
	    return header;
	  }
	}

	let _$1 = t => t,
	  _t$1;
	const disabledButtonClass = 'ui-btn-disabled';
	var _nodeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nodeId");
	var _moveEmployees = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveEmployees");
	var _destroyDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyDialog");
	var _handleOnTagAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagAdd");
	var _handleOnTagRemove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleOnTagRemove");
	var _onUserToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUserToggle");
	var _toggleAddButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleAddButton");
	class MoveFromDialogFooter extends ui_entitySelector.BaseFooter {
	  constructor(tab, options) {
	    super(tab, options);
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
	    Object.defineProperty(this, _destroyDialog, {
	      value: _destroyDialog2
	    });
	    Object.defineProperty(this, _moveEmployees, {
	      value: _moveEmployees2
	    });
	    Object.defineProperty(this, _nodeId, {
	      writable: true,
	      value: void 0
	    });
	    const selectedItems = this.getDialog().getSelectedItems();
	    this.userCount = selectedItems.length;
	    this.users = [];
	    selectedItems.forEach(item => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onUserToggle)[_onUserToggle](item);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _nodeId)[_nodeId] = this.getOption('nodeId');
	    this.getDialog().subscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagAdd)[_handleOnTagAdd].bind(this));
	    this.getDialog().subscribe('Item:onDeselect', babelHelpers.classPrivateFieldLooseBase(this, _handleOnTagRemove)[_handleOnTagRemove].bind(this));
	  }
	  render() {
	    const {
	      footer,
	      footerAddButton,
	      footerCloseButton
	    } = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div ref="footer" class="hr-move-user-from-dialog__footer">
				<button ref="footerAddButton" class="ui-btn ui-btn ui-btn-sm ui-btn-primary ${0} ui-btn-round hr-move-user-from-dialog-btn-width">
					${0}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-move-user-from-dialog-btn-width">
					${0}
				</button>
			</div>
		`), this.users.length === 0 ? disabledButtonClass : '', main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD'), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_REMOVE'));
	    this.footerAddButton = footerAddButton;
	    main_core.Event.bind(footerCloseButton, 'click', event => {
	      this.dialog.hide();
	    });
	    main_core.Event.bind(footerAddButton, 'click', event => {
	      const users = this.dialog.getSelectedItems();
	      const userIds = users.map(item => item.getId());
	      if (userIds.length > 0) {
	        main_core.Dom.addClass(footerAddButton, 'ui-btn-wait');
	        babelHelpers.classPrivateFieldLooseBase(this, _moveEmployees)[_moveEmployees](userIds);
	      }
	    });
	    return footer;
	  }
	}
	async function _moveEmployees2(userIds) {
	  var _nodeStorage$employee, _data$userCount;
	  if (!this.userCount) {
	    return;
	  }
	  if (this.isMoving) {
	    return;
	  }
	  this.isMoving = true;
	  const departmentUserIds = {
	    [humanresources_companyStructure_api.memberRoles.employee]: userIds
	  };
	  const {
	    data
	  } = await main_core.ajax.runAction('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
	    data: {
	      nodeId: babelHelpers.classPrivateFieldLooseBase(this, _nodeId)[_nodeId],
	      userIds: departmentUserIds
	    }
	  });
	  const store = humanresources_companyStructure_chartStore.useChartStore();
	  const nodeStorage = store.departments.get(babelHelpers.classPrivateFieldLooseBase(this, _nodeId)[_nodeId]);
	  if (!nodeStorage) {
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyDialog)[_destroyDialog]();
	    return;
	  }
	  const newMemberUserIds = new Set(this.users.map(user => user.id));
	  const employees = ((_nodeStorage$employee = nodeStorage.employees) != null ? _nodeStorage$employee : []).filter(user => !newMemberUserIds.has(user.id));
	  const headsUserIds = new Set(nodeStorage.heads.map(head => head.id));
	  this.users = this.users.filter(user => !headsUserIds.has(user.id));
	  employees.push(...this.users);
	  nodeStorage.employees = employees;
	  nodeStorage.userCount = (_data$userCount = data.userCount) != null ? _data$userCount : 0;
	  if (data.updatedDepartmentIds) {
	    void humanresources_companyStructure_utils.refreshDepartments(data.updatedDepartmentIds);
	  }
	  if (this.users.length > 1) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD_EMPLOYEES_MESSAGE', {
	        '#DEPARTMENT#': nodeStorage.name
	      })),
	      autoHideDelay: 2000
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyDialog)[_destroyDialog]();
	    return;
	  }
	  if (this.users.length === 1) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD_EMPLOYEE_MESSAGE', {
	        '#DEPARTMENT#': nodeStorage.name
	      })),
	      autoHideDelay: 2000
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _destroyDialog)[_destroyDialog]();
	}
	function _destroyDialog2() {
	  this.isAdding = false;
	  this.getDialog().destroy();
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
	  const userData = humanresources_companyStructure_utils.getUserStoreItemByDialogItem(item, this.role);
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

	const dialogId = 'hr-move-user-from-department-dialog';
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _nodeId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nodeId");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	class MoveUserFromDialog {
	  constructor(nodeId) {
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _nodeId$1, {
	      writable: true,
	      value: void 0
	    });
	    this.id = dialogId;
	    babelHelpers.classPrivateFieldLooseBase(this, _nodeId$1)[_nodeId$1] = nodeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	  }
	  static openDialog(nodeId) {
	    const instance = new MoveUserFromDialog(nodeId);
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
	    header: MoveFromDialogHeader,
	    footer: MoveFromDialogFooter,
	    footerOptions: {
	      nodeId: babelHelpers.classPrivateFieldLooseBase(this, _nodeId$1)[_nodeId$1]
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

	exports.MoveUserFromDialog = MoveUserFromDialog;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX,BX.Humanresources.CompanyStructure,BX.UI.EntitySelector,BX));
//# sourceMappingURL=move-user-from-dialog.bundle.js.map

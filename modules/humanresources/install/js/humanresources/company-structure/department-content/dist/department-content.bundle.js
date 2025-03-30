/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_core,ui_entitySelector,ui_notification,main_sidepanel,ui_tooltip,ui_iconSet_api_core,humanresources_companyStructure_utils,humanresources_companyStructure_structureComponents,ui_iconSet_crm,ui_iconSet_main,humanresources_companyStructure_api,humanresources_companyStructure_permissionChecker,humanresources_companyStructure_userManagementDialog,ui_buttons,humanresources_companyStructure_chartStore,ui_vue3_pinia) {
	'use strict';

	const DepartmentAPI = {
	  getPagedEmployees: (id, page, countPerPage) => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.Employee.list', {
	      nodeId: id,
	      page,
	      countPerPage
	    });
	  },
	  removeUserFromDepartment: (nodeId, userId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.deleteUser', {
	      nodeId,
	      userId
	    });
	  },
	  moveUserToDepartment: (nodeId, userId, targetNodeId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.moveUser', {
	      nodeId,
	      userId,
	      targetNodeId
	    });
	  },
	  isUserInMultipleDepartments: userId => {
	    return humanresources_companyStructure_api.getData('humanresources.api.User.isUserInMultipleDepartments', {
	      userId
	    });
	  },
	  getUserInfo: (nodeId, userId) => {
	    return humanresources_companyStructure_api.getData('humanresources.api.User.getInfoByUserMember', {
	      nodeId,
	      userId
	    });
	  },
	  findMemberByQuery: (nodeId, query) => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.find', {
	      nodeId,
	      query
	    });
	  }
	};

	const DepartmentContentActions = {
	  moveUserToDepartment: (departmentId, userId, targetDepartmentId, role) => {
	    var _department$employees;
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const department = store.departments.get(departmentId);
	    const targetDepartment = store.departments.get(targetDepartmentId);
	    if (!department || !targetDepartment) {
	      return;
	    }
	    const user = role === humanresources_companyStructure_api.memberRoles.employee ? (_department$employees = department.employees) == null ? void 0 : _department$employees.find(employee => employee.id === userId) : department.heads.find(head => head.id === userId);
	    if (!user) {
	      return;
	    }
	    department.userCount -= 1;
	    if (role === humanresources_companyStructure_api.memberRoles.employee) {
	      department.employees = department.employees.filter(employee => employee.id !== userId);
	    } else {
	      department.heads = department.heads.filter(head => head.id !== userId);
	    }
	    targetDepartment.userCount += 1;
	    if (userId === store.userId) {
	      store.changeCurrentDepartment(departmentId, targetDepartmentId);
	    }
	    user.role = humanresources_companyStructure_api.memberRoles.employee;
	    if (!targetDepartment.employees) {
	      return;
	    }
	    targetDepartment.employees.push(user);
	  },
	  removeUserFromDepartment: (departmentId, userId, role) => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const department = store.departments.get(departmentId);
	    if (!department) {
	      return;
	    }
	    if (userId === store.userId) {
	      store.changeCurrentDepartment(departmentId);
	    }
	    department.userCount -= 1;
	    if (role === humanresources_companyStructure_api.memberRoles.employee) {
	      department.employees = department.employees.filter(employee => employee.id !== userId);
	      return;
	    }
	    department.heads = department.heads.filter(head => head.id !== userId);
	  },
	  updateEmployees: (departmentId, employees) => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const department = departments.get(departmentId);
	    if (!department) {
	      return;
	    }
	    departments.set(departmentId, {
	      ...department,
	      employees
	    });
	  },
	  updateEmployeeListOptions: (departmentId, options) => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const department = departments.get(departmentId);
	    if (!department) {
	      return;
	    }
	    department.employeeListOptions = {
	      ...department.employeeListOptions,
	      ...options
	    };
	    departments.set(departmentId, department);
	  }
	};

	const MoveUserActionPopup = {
	  name: 'MoveUserActionPopup',
	  components: {
	    ConfirmationPopup: humanresources_companyStructure_structureComponents.ConfirmationPopup
	  },
	  emits: ['close', 'action'],
	  props: {
	    parentId: {
	      type: Number,
	      required: true
	    },
	    user: {
	      type: Object,
	      required: true
	    }
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (!this.permissionChecker) {
	      return;
	    }
	    this.action = humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	    this.selectedDepartmentId = 0;
	  },
	  data() {
	    return {
	      showMoveUserActionLoader: false,
	      lockMoveUserActionButton: false,
	      showUserAlreadyBelongsToDepartmentPopup: false,
	      accessDenied: false
	    };
	  },
	  mounted() {
	    const departmentContainer = this.$refs['department-selector'];
	    this.departmentSelector = this.getTagSelector(departmentContainer);
	    this.departmentSelector.renderTo(departmentContainer);
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'focusedNode']),
	    getMoveUserActionPhrase() {
	      var _this$departments$get, _this$user$name;
	      const departmentName = main_core.Text.encode((_this$departments$get = this.departments.get(this.focusedNode).name) != null ? _this$departments$get : '');
	      const userName = main_core.Text.encode((_this$user$name = this.user.name) != null ? _this$user$name : '');
	      return this.loc('HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_REMOVE_USER_DESCRIPTION', {
	        '#USER_NAME#': userName,
	        '#DEPARTMENT_NAME#': departmentName
	      }).replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`).replace('[/link]', '</a>');
	    },
	    getUserAlreadyBelongsToDepartmentPopupPhrase() {
	      var _this$departments$get2, _this$selectedParentD, _this$user$name2;
	      const departmentName = main_core.Text.encode((_this$departments$get2 = this.departments.get((_this$selectedParentD = this.selectedParentDepartment) != null ? _this$selectedParentD : 0).name) != null ? _this$departments$get2 : '');
	      const userName = main_core.Text.encode((_this$user$name2 = this.user.name) != null ? _this$user$name2 : '');
	      let phrase = this.loc('HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_DESCRIPTION', {
	        '#USER_NAME#': userName,
	        '#DEPARTMENT_NAME#': departmentName
	      });
	      phrase = phrase.replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`);
	      phrase = phrase.replace('[/link]', '</a>');
	      return phrase;
	    },
	    memberRoles() {
	      return humanresources_companyStructure_api.memberRoles;
	    }
	  },
	  methods: {
	    getTagSelector() {
	      return new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            this.accessDenied = false;
	            const {
	              tag
	            } = event.data;
	            this.selectedParentDepartment = tag.id;
	            if (humanresources_companyStructure_permissionChecker.PermissionChecker.hasPermission(this.action, tag.id)) {
	              this.lockMoveUserActionButton = false;
	              return;
	            }
	            this.accessDenied = true;
	            this.lockMoveUserActionButton = true;
	          },
	          onTagRemove: () => {
	            this.lockMoveUserActionButton = true;
	          }
	        },
	        multiple: false,
	        dialogOptions: {
	          width: 425,
	          height: 350,
	          dropdownMode: true,
	          hideOnDeselect: true,
	          entities: [{
	            id: 'structure-node',
	            options: {
	              selectMode: 'departmentsOnly'
	            }
	          }],
	          preselectedItems: [['structure-node', this.parentId]]
	        },
	        tagBgColor: '#ade7e4',
	        tagTextColor: '#207976',
	        tagFontWeight: '700',
	        tagAvatar: '/bitrix/js/humanresources/entity-selector/src/images/department.svg'
	      });
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    async confirmMoveUser() {
	      var _this$departments$get3, _this$departments$get4, _this$user$role;
	      this.showMoveUserActionLoader = true;
	      const departmentId = this.focusedNode;
	      const userId = this.user.id;
	      const targetNodeId = this.selectedParentDepartment;
	      try {
	        await DepartmentAPI.moveUserToDepartment(departmentId, userId, targetNodeId);
	      } catch (error) {
	        var _error$code;
	        this.showMoveUserActionLoader = false;
	        const code = (_error$code = error.code) != null ? _error$code : 0;
	        if (code === 'MEMBER_ALREADY_BELONGS_TO_NODE') {
	          this.showUserAlreadyBelongsToDepartmentPopup = true;
	        } else {
	          ui_notification.UI.Notification.Center.notify({
	            content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ERROR'),
	            autoHideDelay: 2000
	          });
	          this.$emit('close');
	        }
	        return;
	      }
	      const departmentName = main_core.Text.encode((_this$departments$get3 = (_this$departments$get4 = this.departments.get(targetNodeId)) == null ? void 0 : _this$departments$get4.name) != null ? _this$departments$get3 : '');
	      ui_notification.UI.Notification.Center.notify({
	        content: this.loc('HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUCCESS_MESSAGE', {
	          '#DEPARTMENT_NAME#': departmentName
	        }),
	        autoHideDelay: 2000
	      });
	      DepartmentContentActions.moveUserToDepartment(departmentId, userId, targetNodeId, (_this$user$role = this.user.role) != null ? _this$user$role : humanresources_companyStructure_api.memberRoles.employee);
	      this.$emit('action');
	      this.showMoveUserActionLoader = false;
	    },
	    closeAction() {
	      this.$emit('close');
	    },
	    closeUserAlreadyBelongsToDepartmentPopup() {
	      this.showUserAlreadyBelongsToDepartmentPopup = false;
	      this.closeAction();
	    }
	  },
	  template: `
		<ConfirmationPopup
			@action="confirmMoveUser"
			@close="closeAction"
			:showActionButtonLoader="showMoveUserActionLoader"
			:lockActionButton="lockMoveUserActionButton"
			:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_CONFIRM_TITLE')"
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div v-html="getMoveUserActionPhrase"/>
					<span>
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_ACTION_SELECT_DEPARTMENT_DESCRIPTION')}}
					</span>
				</div>
				<div class="hr-department-detail-content__move-user-department-selector" ref="department-selector"></div>
				<div
					class="hr-department-detail-content__move-user-department-selector"
					ref="department-selector"
					:class="{ 'ui-ctl-warning': accessDenied }"
				/>
				<div
					v-if="accessDenied"
					class="hr-department-detail-content__move-user-department_item-error"
				>
					<div class="ui-icon-set --warning"></div>
					<span
						class="hr-department-detail-content__move-user-department_item-error-message"
					>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_PERMISSION_ERROR')}}
					</span>
				</div>
			</template>
		</ConfirmationPopup>
		<ConfirmationPopup
			@action="closeUserAlreadyBelongsToDepartmentPopup"
			@close="closeUserAlreadyBelongsToDepartmentPopup"
			v-if="showUserAlreadyBelongsToDepartmentPopup"
			:withoutTitleBar = true
			:onlyConfirmButtonMode = true
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_CLOSE_BUTTON')"
			:width="300"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div 
						class="hr-department-detail-content__user-belongs-to-department-text-container"
						v-html="getUserAlreadyBelongsToDepartmentPopupPhrase"
					/>
				</div>
				<div class="hr-department-detail-content__move-user-department-selector" ref="department-selector"></div>
			</template>
		</ConfirmationPopup>
	`
	};

	const MenuOption = Object.freeze({
	  removeUserFromDepartment: 'removeUserFromDepartment',
	  moveUserToAnotherDepartment: 'moveUserToAnotherDepartment',
	  fireUserFromCompany: 'fireUserFromCompany'
	});
	const UserListItemActionButton = {
	  name: 'userList',
	  props: {
	    user: {
	      type: Object,
	      required: true
	    },
	    departmentId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu,
	    ConfirmationPopup: humanresources_companyStructure_structureComponents.ConfirmationPopup,
	    MoveUserActionPopup
	  },
	  data() {
	    return {
	      menuVisible: {},
	      showRemoveUserConfirmationPopup: false,
	      showRemoveUserConfirmationActionLoader: false,
	      showMoveUserPopup: false
	    };
	  },
	  methods: {
	    toggleMenu(userId) {
	      this.menuVisible[userId] = !this.menuVisible[userId];
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      if (actionId === MenuOption.removeUserFromDepartment) {
	        this.showRemoveUserConfirmationPopup = true;
	      }
	      if (actionId === MenuOption.moveUserToAnotherDepartment) {
	        this.showMoveUserPopup = true;
	      }
	    },
	    async removeUser() {
	      this.showRemoveUserConfirmationActionLoader = true;
	      const userId = this.user.id;
	      const isUserInMultipleDepartments = await DepartmentAPI.isUserInMultipleDepartments(userId);
	      const departmentId = this.focusedNode;
	      this.showRemoveUserConfirmationActionLoader = false;
	      this.showRemoveUserConfirmationPopup = false;
	      try {
	        await DepartmentAPI.removeUserFromDepartment(departmentId, userId);
	      } catch {
	        ui_notification.UI.Notification.Center.notify({
	          content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_ERROR'),
	          autoHideDelay: 2000
	        });
	        return;
	      }
	      const role = this.user.role;
	      if (isUserInMultipleDepartments) {
	        DepartmentContentActions.removeUserFromDepartment(departmentId, userId, role);
	        return;
	      }
	      const rootDepartment = [...this.departments.values()].find(department => department.parentId === 0);
	      if (!rootDepartment) {
	        return;
	      }
	      DepartmentContentActions.moveUserToDepartment(departmentId, userId, rootDepartment.id, role);
	    },
	    cancelRemoveUser() {
	      this.showRemoveUserConfirmationPopup = false;
	    },
	    handleMoveUserAction() {
	      this.showMoveUserPopup = false;
	    },
	    handleMoveUserClose() {
	      this.showMoveUserPopup = false;
	    },
	    getMemberKeyByValue(value) {
	      return Object.keys(humanresources_companyStructure_api.memberRoles).find(key => humanresources_companyStructure_api.memberRoles[key] === value) || '';
	    }
	  },
	  created() {
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    const menuItems = [{
	      id: MenuOption.moveUserToAnotherDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	      }
	    }, {
	      id: MenuOption.removeUserFromDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.TRASH_BIN,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeRemoveFromDepartment
	      }
	    }, {
	      id: MenuOption.fireUserFromCompany,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_FROM_COMPANY_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_FROM_COMPANY_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSONS_DENY,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeFire
	      }
	    }];
	    this.menuItems = menuItems.filter(item => {
	      if (!item.permission) {
	        return false;
	      }
	      return permissionChecker.hasPermission(item.permission.action, this.departmentId);
	    });
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments']),
	    memberRoles() {
	      return humanresources_companyStructure_api.memberRoles;
	    }
	  },
	  template: `
		<button 
			v-if="menuItems.length"
			class="ui-icon-set --more hr-department-detail-content__user-action-btn"
			:class="{ '--focused': menuVisible[user.id] }"
			@click.stop="toggleMenu(user.id)"
			ref="actionUserButton"
			:data-id="'hr-department-detail-content__'+ getMemberKeyByValue(user.role) + '-list_user-' + user.id + '-action-btn'"
		/>
		<RouteActionMenu
			v-if="menuVisible[user.id]"
			:id="'tree-node-department-menu-' + user.id"
			:items="menuItems"
			:width="302"
			:bindElement="$refs.actionUserButton"
			@action="onActionMenuItemClick"
			@close="menuVisible[user.id] = false"
		/>
		<ConfirmationPopup
			ref="removeUserConfirmationPopup"
			v-if="showRemoveUserConfirmationPopup"
			:showActionButtonLoader="showRemoveUserConfirmationActionLoader"
			:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_TITLE')"
			:description="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_DESCRIPTION')"
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			@action="removeUser"
			@close="cancelRemoveUser"
		/>
		<MoveUserActionPopup
			v-if="showMoveUserPopup"
			:parentId="focusedNode"
			:user="user"
			@action="handleMoveUserAction"
			@close="handleMoveUserClose"
		/>
	`
	};

	const UserListItem = {
	  name: 'userList',
	  props: {
	    user: {
	      type: Object,
	      required: true
	    },
	    selectedUserId: {
	      type: Number,
	      required: false,
	      default: null
	    }
	  },
	  components: {
	    UserListItemActionButton
	  },
	  computed: {
	    memberRoles() {
	      return humanresources_companyStructure_api.memberRoles;
	    },
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    handleUserClick(item) {
	      main_sidepanel.SidePanel.Instance.open(item.url, {
	        cacheable: false
	      });
	    }
	  },
	  template: `
		<div 
			:key="user.id"
			class="hr-department-detail-content__user-container"
			:class="{ '--searched': user.id === selectedUserId }"
			:data-id="'hr-department-detail-content__user-' + user.id + '-item'"
		>
			<div class="hr-department-detail-content__user-avatar-container" @click="handleUserClick(user)">
				<img 
					class="hr-department-detail-content__user-avatar-img"
					:src="user.avatar ? encodeURI(user.avatar) : defaultAvatar"
				/>
				<div v-if="user.role === memberRoles.head" class="hr-department-detail-content__user-avatar-overlay"></div>
			</div>
			<div class="hr-department-detail-content-user__text-container">
				<div class="hr-department-detail-content__user-title">
					<div 
						class="hr-department-detail-content__user-name" 
						@click="handleUserClick(user)"
						:bx-tooltip-user-id="user.id"
						:data-id="'hr-department-detail-content__user-' + user.id + '-item-name'"
					>
						{{ user.name }}
					</div>
					<div v-if="user.badgeText" class="hr-department-detail-content-user__name-badge">{{ user.badgeText }}</div>
				</div>
				<div 
					class="hr-department-detail-content__user-subtitle" 
					:class="{ '--without-work-position': !user.subtitle }"
				>
					{{ (user.subtitle?.length ?? 0) > 0 ? user.subtitle : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_DEFAULT_WORK_POSITION') }}
				</div>
				<div v-if="user.isInvited" class="hr-department-detail-content-user__item-badge">
					{{ this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_INVITED_BADGE_TEXT') }}
				</div>
			</div>
			<UserListItemActionButton
				:user="user"
				:departmentId="focusedNode"
			/>
		</div>
	`
	};

	const UserList = {
	  name: 'userList',
	  props: {
	    items: {
	      type: Array,
	      required: true
	    },
	    selectedUserId: {
	      type: Number,
	      required: false,
	      default: null
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu,
	    UserListItem
	  },
	  data() {
	    return {
	      draggedEmployee: null,
	      draggedIndex: null
	    };
	  },
	  methods: {
	    onDragStart(item, targetElement) {
	      this.$emit('dragstart', item, targetElement);
	    },
	    onDrop(item, targetIndex) {
	      this.$emit('drop', item, targetIndex);
	    },
	    updateEmployeeRole(employee, newRole) {
	      employee.role = newRole;
	    }
	  },
	  template: `
		<div 
			class="hr-department-detail-content__user-list-container"
		>
			<UserListItem
				v-for="user in items"
				:user="user"
				:selectedUserId="selectedUserId"
			/>
		</div>
	`
	};

	const MenuOption$1 = Object.freeze({
	  addToDepartment: 'addToDepartment',
	  editDepartmentUsers: 'editDepartmentUsers'
	});
	const UserListActionButton = {
	  name: 'userListActionButton',
	  emits: ['addToDepartment', 'editDepartmentUsers'],
	  props: {
	    role: {
	      type: String,
	      default: 'employee'
	    },
	    departmentId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.menuItems = this.getMenuItems();
	  },
	  watch: {
	    departmentId() {
	      this.menuItems = this.getMenuItems();
	    }
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode'])
	  },
	  data() {
	    return {
	      menuVisible: false
	    };
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      this.$emit(actionId);
	    },
	    getMenuItems() {
	      if (!this.permissionChecker) {
	        return [];
	      }
	      let menuItems = [{
	        id: MenuOption$1.addToDepartment,
	        bIcon: {
	          name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }, {
	        id: MenuOption$1.editDepartmentUsers,
	        bIcon: {
	          name: ui_iconSet_api_core.Main.EDIT_MENU,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }];
	      const menuItemText = {
	        [MenuOption$1.addToDepartment]: {
	          head: {
	            title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_TITLE'),
	            description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_SUBTITLE')
	          },
	          employee: {
	            title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_TITLE'),
	            description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_SUBTITLE')
	          }
	        },
	        [MenuOption$1.editDepartmentUsers]: {
	          head: {
	            title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_TITLE'),
	            description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_SUBTITLE')
	          },
	          employee: {
	            title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_TITLE'),
	            description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_SUBTITLE')
	          }
	        }
	      };
	      menuItems = menuItems.map(item => ({
	        ...item,
	        ...menuItemText[item.id][this.role]
	      }));
	      return menuItems.filter(item => {
	        if (!item.permission) {
	          return false;
	        }
	        return this.permissionChecker.hasPermission(item.permission.action, this.departmentId);
	      });
	    }
	  },
	  template: `
		<button
			v-if="menuItems.length"
			class="hr-department-detail-content__list-header-button"
			:class="{ '--focused': menuVisible }"
			:ref="'actionMenuButton' + role"
			@click.stop="menuVisible = true"
			:data-ld="'hr-department-detail-content__' + role + '_list-header-button'"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_ACTION_BUTTON_TITLE') }}
		</button>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'tree-node-department-menu-' + role + '-' + focusedNode"
			:items="menuItems"
			:width="302"
			:bindElement="$refs['actionMenuButton' + role]"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	const emptyStateTypes = {
	  NO_MEMBERS_WITH_ADD_PERMISSION: 'NO_MEMBERS_WITH_ADD_PERMISSION',
	  NO_MEMBERS_WITHOUT_ADD_PERMISSION: 'NO_MEMBERS_WITHOUT_ADD_PERMISSION',
	  NO_SEARCHED_USERS_RESULTS: 'NO_SEARCHED_USERS_RESULTS'
	};

	const MenuOption$2 = Object.freeze({
	  moveUser: 'moveUser',
	  userInvite: 'userInvite',
	  addToDepartment: 'addToDepartment'
	});
	const EmptyStateContainer = {
	  name: 'emptyStateContainer',
	  props: {
	    type: {
	      type: String,
	      required: true
	    },
	    departmentId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.menuItems = this.getMenuItems();
	  },
	  data() {
	    return {
	      menuVisible: false
	    };
	  },
	  computed: {
	    showAddButtons() {
	      return this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    emptyStateIconClass() {
	      if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS) {
	        return '--user-not-found';
	      }
	      if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION) {
	        return '--with-add-permission';
	      }
	      if (this.type === emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION) {
	        return '--without-add-permission';
	      }
	      return null;
	    },
	    emptyStateTitle() {
	      if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE');
	      }
	      if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_TITLE');
	      }
	      if (this.type === emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TITLE');
	      }
	      return null;
	    },
	    emptyStateDescription() {
	      if (this.type === emptyStateTypes.NO_SEARCHED_USERS_RESULTS) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE');
	      }
	      if (this.type === emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
	      }
	      return null;
	    },
	    onActionMenuItemClick(actionId) {
	      this.$emit(actionId, {
	        type: 'employee',
	        bindElement: this.$refs.actionMenuButton
	      });
	    },
	    getMenuItems() {
	      if (!this.permissionChecker) {
	        return [];
	      }
	      return [{
	        id: MenuOption$2.moveUser,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }, {
	        id: MenuOption$2.userInvite,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_INVITE_EMPLOYEE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_INVITE_EMPLOYEE_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.PERSON_LETTER,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.inviteToDepartment
	        }
	      }, {
	        id: MenuOption$2.addToDepartment,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_CONTENT_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }].filter(item => {
	        if (!item.permission) {
	          return false;
	        }
	        return this.permissionChecker.hasPermission(item.permission.action, this.focusedNode);
	      });
	    }
	  },
	  watch: {
	    departmentId() {
	      this.menuItems = this.getMenuItems();
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__tab-container --empty">
			<div :class="['hr-department-detail-content__tab-entity-icon', this.emptyStateIconClass()]"></div>
			<div class="hr-department-detail-content__tab-entity-content">
				<span class="hr-department-detail-content__empty-tab-entity-title">
					{{ this.emptyStateTitle() }}
				</span>
				<span class="hr-department-detail-content__empty-tab-entity-subtitle">
					{{ this.emptyStateDescription() }}
				</span>
				<div v-if="showAddButtons" class="hr-department-detail-content__empty-tab-entity-buttons-container">
					<button
						class="hr-add-employee-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
						ref="actionMenuButton"
						@click.stop="menuVisible = true"
						data-id="hr-department-detail-content__user-empty-tab_add-user-button"
					>
						<span class="hr-add-employee-empty-tab-entity-btn-text">{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_ADD_BUTTON')}}</span>
					</button>
					<RouteActionMenu
						v-if="menuVisible"
						:id="'empty-state-department-detail-add-menu-' + focusedNode"
						:items="menuItems"
						:width="302"
						:bindElement="$refs['actionMenuButton']"
						@action="onActionMenuItemClick"
						@close="menuVisible = false"
					/>
				</div>
			</div>
		</div>
	`
	};

	const UsersTab = {
	  name: 'usersTab',
	  emits: ['editDepartmentUsers', 'showDetailLoader', 'hideDetailLoader'],
	  components: {
	    UserList,
	    UserListActionButton,
	    EmptyStateContainer
	  },
	  data() {
	    return {
	      searchQuery: '',
	      selectedUserId: null,
	      needToScroll: false,
	      hasFocus: false
	    };
	  },
	  created() {
	    this.loadEmployeesAction();
	  },
	  mounted() {
	    this.tabContainer = this.$refs['tab-container'];
	  },
	  computed: {
	    heads() {
	      var _this$departments$get;
	      return (_this$departments$get = this.departments.get(this.focusedNode).heads) != null ? _this$departments$get : [];
	    },
	    headCount() {
	      var _this$heads$length;
	      return (_this$heads$length = this.heads.length) != null ? _this$heads$length : 0;
	    },
	    formattedHeads() {
	      return this.heads.map(head => ({
	        ...head,
	        subtitle: head.workPosition,
	        badgeText: this.getBadgeText(head.role)
	      })).sort((a, b) => {
	        const roleOrder = {
	          [humanresources_companyStructure_api.memberRoles.head]: 1,
	          [humanresources_companyStructure_api.memberRoles.deputyHead]: 2
	        };
	        const roleA = roleOrder[a.role] || 3;
	        const roleB = roleOrder[b.role] || 3;
	        return roleA - roleB;
	      });
	    },
	    filteredHeads() {
	      return this.formattedHeads.filter(head => head.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || head.workPosition.toLowerCase().includes(this.searchQuery.toLowerCase()));
	    },
	    employeeCount() {
	      var _this$departments$get2, _this$headCount;
	      const memberCount = (_this$departments$get2 = this.departments.get(this.focusedNode).userCount) != null ? _this$departments$get2 : 0;
	      return memberCount - ((_this$headCount = this.headCount) != null ? _this$headCount : 0);
	    },
	    formattedEmployees() {
	      return this.employees.map(employee => ({
	        ...employee,
	        subtitle: employee.workPosition
	      })).reverse();
	    },
	    filteredEmployees() {
	      return this.formattedEmployees.filter(employee => {
	        var _employee$workPositio;
	        return employee.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || ((_employee$workPositio = employee.workPosition) == null ? void 0 : _employee$workPositio.toLowerCase().includes(this.searchQuery.toLowerCase()));
	      });
	    },
	    memberCount() {
	      var _this$departments$get3;
	      return (_this$departments$get3 = this.departments.get(this.focusedNode).userCount) != null ? _this$departments$get3 : 0;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments', 'searchedUserId']),
	    ...ui_vue3_pinia.mapWritableState(humanresources_companyStructure_chartStore.useChartStore, ['searchedUserId']),
	    employees() {
	      var _this$departments$get4, _this$departments$get5;
	      return (_this$departments$get4 = (_this$departments$get5 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get5.employees) != null ? _this$departments$get4 : [];
	    },
	    showEmptyState() {
	      if (!this.memberCount) {
	        return true;
	      }
	      return this.filteredHeads.length === 0 && this.filteredEmployees.length === 0;
	    },
	    emptyStateType() {
	      if (!this.memberCount && this.canAddUsers) {
	        return emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION;
	      }
	      if (!this.memberCount) {
	        return emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION;
	      }
	      if (this.filteredHeads.length === 0 && this.filteredEmployees.length === 0) {
	        return emptyStateTypes.NO_SEARCHED_USERS_RESULTS;
	      }
	      return null;
	    },
	    showSearchBar() {
	      return this.memberCount > 0;
	    },
	    canAddUsers() {
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      if (!permissionChecker) {
	        return false;
	      }
	      const nodeId = this.focusedNode;
	      return permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment, nodeId);
	    },
	    headListEmptyStateTitle() {
	      if (this.canAddUsers) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE');
	      }
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
	    },
	    employeesListEmptyStateTitle() {
	      if (this.canAddUsers) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE');
	      }
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
	    },
	    shouldUpdateList() {
	      var _this$departments$get6, _this$departments$get7;
	      return (_this$departments$get6 = (_this$departments$get7 = this.departments.get(this.focusedNode).employeeListOptions) == null ? void 0 : _this$departments$get7.shouldUpdateList) != null ? _this$departments$get6 : true;
	    },
	    departmentUsersStatus() {
	      const department = this.departments.get(this.focusedNode);
	      if (department != null && department.heads && department.employees) {
	        return {
	          departmentId: this.focusedNode,
	          loaded: true
	        };
	      }
	      return {
	        departmentId: this.focusedNode,
	        loaded: false
	      };
	    }
	  },
	  methods: {
	    onDragStart(targetElement) {
	      if (!targetElement.id) {
	        return;
	      }
	      this.draggedEmployee = targetElement;
	    },
	    onDropToEmployee(targetIndex) {
	      // @todo send order or new member to backend
	      if (this.draggedEmployee) {
	        if (this.draggedEmployee.role) {
	          const movedEmployee = {
	            ...this.draggedEmployee
	          };
	          delete movedEmployee.role;
	          delete movedEmployee.badgeText;
	          const index = this.heads.findIndex(head => head.id === this.draggedEmployee.id);
	          this.heads.splice(index, 1);
	          this.employees.splice(targetIndex.id, 0, movedEmployee);
	        } else {
	          const index = this.employees.findIndex(employee => employee && employee.id === this.draggedEmployee.id);
	          const movedEmployee = this.employees.splice(index, 1)[0];
	          this.employees.splice(targetIndex.id, 0, movedEmployee);
	        }
	        this.draggedEmployee = null;
	        this.draggedIndex = null;
	      }
	    },
	    onDropToHead(targetElement) {
	      if (this.draggedEmployee) {
	        if (this.draggedEmployee.role === 'MEMBER_HEAD') {
	          const index = this.heads.findIndex(head => head.id === this.draggedEmployee.id);
	          const movedHead = this.heads.splice(index, 1)[0];
	          this.heads.splice(targetElement.id, 0, movedHead);
	        } else {
	          const movedHead = {
	            ...this.draggedEmployee,
	            role: 'MEMBER_HEAD'
	          };
	          const index = this.employees.findIndex(employee => employee && employee.id === this.draggedEmployee.id);
	          this.employees.splice(index, 1);
	          this.heads.splice(targetElement.id, 0, movedHead);
	        }
	        this.draggedEmployee = null;
	        this.draggedIndex = null;
	      }
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    getBadgeText(role) {
	      if (role === humanresources_companyStructure_api.memberRoles.head) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE');
	      }
	      if (role === humanresources_companyStructure_api.memberRoles.deputyHead) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_DEPUTY_HEAD_BADGE');
	      }
	      return null;
	    },
	    updateList(event) {
	      const employeesList = event.target;
	      const scrollPosition = employeesList.scrollTop + employeesList.clientHeight;
	      if (employeesList.scrollHeight - scrollPosition < 40) {
	        this.loadEmployeesAction();
	      }
	    },
	    addToDepartment(options = {}) {
	      const nodeId = this.focusedNode;
	      const role = options.type === 'head' ? humanresources_companyStructure_api.memberRoles.head : humanresources_companyStructure_api.memberRoles.employee;
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId,
	        type: 'add',
	        role
	      });
	    },
	    userInvite() {
	      const departmentToInvite = this.departments.get(this.focusedNode).accessCode.slice(1);
	      BX.SidePanel.Instance.open('/bitrix/services/main/ajax.php?action=getSliderContent' + '&c=bitrix%3Aintranet.invitation&mode=ajax' + `&departments[]=${departmentToInvite}&firstInvitationBlock=invite-with-group-dp`, {
	        cacheable: false,
	        allowChangeHistory: false,
	        width: 1100
	      });
	    },
	    moveUser() {
	      const nodeId = this.focusedNode;
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId,
	        type: 'move'
	      });
	    },
	    editDepartmentUsers() {
	      this.$emit('editDepartmentUsers');
	    },
	    async loadEmployeesAction() {
	      var _this$departments$get8, _employeeListOptions$, _employeeListOptions$2, _employeeListOptions$3, _this$departments$get9, _this$departments$get10;
	      const nodeId = this.focusedNode;
	      if (!this.departments.get(nodeId)) {
	        return;
	      }
	      const employeeListOptions = (_this$departments$get8 = this.departments.get(nodeId).employeeListOptions) != null ? _this$departments$get8 : {};
	      employeeListOptions.page = (_employeeListOptions$ = employeeListOptions.page) != null ? _employeeListOptions$ : 0;
	      employeeListOptions.shouldUpdateList = (_employeeListOptions$2 = employeeListOptions.shouldUpdateList) != null ? _employeeListOptions$2 : true;
	      employeeListOptions.isListUpdated = (_employeeListOptions$3 = employeeListOptions.isListUpdated) != null ? _employeeListOptions$3 : false;
	      DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
	      if (employeeListOptions.isListUpdated || !employeeListOptions.shouldUpdateList) {
	        return;
	      }
	      if (!employeeListOptions.isListUpdated && employeeListOptions.page === 0 && employeeListOptions.shouldUpdateList === true) {
	        this.$emit('showDetailLoader');
	      }
	      employeeListOptions.isListUpdated = true;
	      employeeListOptions.page += 1;
	      DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
	      let loadedEmployees = await DepartmentAPI.getPagedEmployees(nodeId, employeeListOptions.page, 25);
	      if (!loadedEmployees) {
	        employeeListOptions.shouldUpdateList = false;
	        employeeListOptions.isListUpdated = false;
	        DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
	        return;
	      }
	      loadedEmployees = loadedEmployees.map(user => {
	        return {
	          ...user,
	          role: humanresources_companyStructure_api.memberRoles.employee
	        };
	      });
	      const employees = (_this$departments$get9 = (_this$departments$get10 = this.departments.get(nodeId)) == null ? void 0 : _this$departments$get10.employees) != null ? _this$departments$get9 : [];
	      const employeeIds = new Set(employees.map(employee => employee.id));
	      const newEmployees = loadedEmployees.reverse().filter(employee => !employeeIds.has(employee.id));
	      employees.unshift(...newEmployees);
	      employeeListOptions.shouldUpdateList = newEmployees.length === 25;
	      employeeListOptions.isListUpdated = false;
	      DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
	      DepartmentContentActions.updateEmployees(nodeId, employees);
	      if (this.departmentUsersStatus.loaded) {
	        this.$emit('hideDetailLoader');
	      }
	      if (this.needToScroll) {
	        this.scrollToUser();
	      }
	    },
	    async scrollToUser() {
	      const userId = this.needToFocusUserId;
	      this.needToFocusUserId = null;
	      this.needToScroll = false;
	      const selectors = `.hr-department-detail-content__user-container[data-id="hr-department-detail-content__user-${userId}-item"]`;
	      let element = this.tabContainer.querySelector(selectors);
	      if (!element) {
	        let user = null;
	        try {
	          user = await DepartmentAPI.getUserInfo(this.focusedNode, userId);
	        } catch {/* empty */}
	        const department = this.departments.get(this.focusedNode);
	        if (!user || !department) {
	          return;
	        }
	        if (user.role === humanresources_companyStructure_api.memberRoles.head || user.role === humanresources_companyStructure_api.memberRoles.deputyHead) {
	          var _department$heads;
	          department.heads = (_department$heads = department.heads) != null ? _department$heads : [];
	          if (!department.heads.some(head => head.id === user.id)) {
	            return;
	          }
	        } else {
	          var _department$employees;
	          department.employees = (_department$employees = department.employees) != null ? _department$employees : [];
	          if (!department.employees.some(employee => employee.id === user.id)) {
	            department.employees.push(user);
	          }
	        }
	        await this.$nextTick(() => {
	          element = this.tabContainer.querySelector(selectors);
	        });
	      }
	      if (!element) {
	        return;
	      }
	      element.scrollIntoView({
	        behavior: 'smooth',
	        block: 'center'
	      });
	      setTimeout(() => {
	        this.selectedUserId = userId;
	      }, 750);
	      setTimeout(() => {
	        if (this.searchedUserId === userId) {
	          this.selectedUserId = null;
	          this.searchedUserId = null;
	        }
	      }, 4000);
	    },
	    async searchMembers(query) {
	      if (query.length === 0) {
	        return;
	      }
	      this.findQueryResult = this.findQueryResult || {};
	      this.findQueryResult[this.focusedNode] = this.findQueryResult[this.focusedNode] || {
	        success: [],
	        failure: []
	      };
	      const nodeResults = this.findQueryResult[this.focusedNode];
	      if (nodeResults.failure.some(failedQuery => query.startsWith(failedQuery))) {
	        return;
	      }
	      if (nodeResults.success.includes(query) || nodeResults.failure.includes(query)) {
	        return;
	      }
	      const founded = await DepartmentAPI.findMemberByQuery(this.focusedNode, query);
	      if (founded.length === 0) {
	        nodeResults.failure.push(query);
	        return;
	      }
	      const department = this.departments.get(this.focusedNode);
	      const newMembers = founded.filter(found => !department.heads.some(head => head.id === found.id) && !department.employees.some(employee => employee.id === found.id));
	      department.employees.push(...newMembers);
	      nodeResults.success.push(query);
	    },
	    onBlur() {
	      if (this.searchQuery.length === 0) {
	        this.hasFocus = false;
	      }
	    },
	    clearInput() {
	      this.searchQuery = '';
	      this.hasFocus = false;
	    }
	  },
	  watch: {
	    focusedNode(newId) {
	      const department = this.departments.get(newId) || {};
	      if (!department.employeeListOptions || Object.keys(department.employeeListOptions).length === 0) {
	        const employeeListOptions = {
	          page: 0,
	          shouldUpdateList: true,
	          isListUpdated: false
	        };
	        DepartmentContentActions.updateEmployeeListOptions(newId, employeeListOptions);
	        this.departments.set(newId, department);
	      }
	      if (department.employeeListOptions.page === 0 && department.employeeListOptions.isListUpdated === false && department.employeeListOptions.shouldUpdateList === true) {
	        this.loadEmployeesAction();
	      }
	      this.isDescriptionExpanded = false;
	      this.searchQuery = '';
	    },
	    searchedUserId: {
	      handler(userId) {
	        if (!userId) {
	          return;
	        }
	        this.needToFocusUserId = userId;
	        if (this.isListUpdated) {
	          this.needToScroll = true;
	        } else {
	          this.$nextTick(() => {
	            this.scrollToUser();
	          });
	        }
	      },
	      immediate: true
	    },
	    async searchQuery(newQuery) {
	      await this.searchMembers(newQuery);
	    },
	    departmentUsersStatus(usersStatus, prevUsersStatus) {
	      const {
	        departmentId,
	        loaded
	      } = usersStatus;
	      const {
	        departmentId: prevDepartmentId,
	        loaded: prevLoaded
	      } = prevUsersStatus;
	      if (departmentId === prevDepartmentId && loaded === prevLoaded) {
	        return;
	      }
	      if (loaded) {
	        this.$emit('hideDetailLoader');
	      } else {
	        this.$emit('showDetailLoader');
	        this.loadEmployeesAction();
	      }
	    }
	  },
	  template: `
		<div
			class="hr-department-detail-content__tab-container"
			ref="tab-container"
		>
			<div
				v-if="showSearchBar"
				class="hr-department-detail-content__content-search"
				:class="{'--focused': hasFocus}"
			>
				<div class="hr-department-detail-content__content-search-icon"/>
				<input
					v-model="searchQuery"
					class="hr-department-detail-content__content-search-input"
					:placeholder="!hasFocus ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_SEARCH_PLACEHOLDER') : ''"
					@focus="hasFocus = true"
					@blur="onBlur"
				>
				<div
					class="hr-department-detail-content__content-search-close-button ui-icon-set --cross-circle-50"
					:class="{'--hide': !hasFocus}"
					style="--ui-icon-set__icon-size: 24px; --ui-icon-set__icon-color: #2FC6F6;"
					@click="clearInput"
				/>
			</div>
			<EmptyStateContainer
				v-if="emptyStateType"
				:type="emptyStateType"
				:departmentId="focusedNode"
				@addToDepartment="addToDepartment"
				@userInvite="userInvite"
				@moveUser="moveUser"
			/>
			<div
				v-else
				v-on="shouldUpdateList ? { scroll: updateList } : {}"
				class="hr-department-detail-content__lists-container"
			>
				<div class="hr-department-detail-content__list --head">
					<div class="hr-department-detail-content__list-header-container">
						<div class="hr-department-detail-content__list-title">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_LIST_TITLE') }}
							<span
								class="hr-department-detail-content__list-count"
								data-id="hr-department-detail-content__head_list-count"
							>
								{{ headCount }}
							</span>
						</div>
						<UserListActionButton
							role="head"
							@addToDepartment="addToDepartment({ type: 'head' })"
							@editDepartmentUsers="editDepartmentUsers"
							:departmentId="focusedNode"
						/>
					</div>
					<div v-if="!headCount" :class="['hr-department-detail-content__empty-list-item', { '--with-add': canAddUsers }]">
						<div class="hr-department-detail-content__empty-user-list-item-image"/>
						<div class="hr-department-detail-content__empty-list-item-content">
							<div class="hr-department-detail-content__empty-list-item-title">
								{{ headListEmptyStateTitle }}
							</div>
						</div>
					</div>
					<UserList
						:items="filteredHeads"
						@dragstart="onDragStart"
						@drop="onDropToHead"
						:selectedUserId="selectedUserId"
					/>
				</div>
				<div class="hr-department-detail-content__list --employee">
					<div class="hr-department-detail-content__list-header-container">
						<div class="hr-department-detail-content__list-title">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_LIST_TITLE') }}
							<span
								class="hr-department-detail-content__list-count"
								data-id="hr-department-detail-content__employee_list-count"
							>
								{{ employeeCount }}
							</span>
						</div>
						<UserListActionButton
							role="employee"
							@addToDepartment="addToDepartment({ type: 'employee' })"
							@editDepartmentUsers="editDepartmentUsers"
							:departmentId="focusedNode"
						/>
					</div>
					<div v-if="!employeeCount" :class="['hr-department-detail-content__empty-list-item', { '--with-add': canAddUsers }]">
						<div class="hr-department-detail-content__empty-user-list-item-image"/>
						<div class="hr-department-detail-content__empty-list-item-content">
							<div class="hr-department-detail-content__empty-list-item-title">
								{{ employeesListEmptyStateTitle }}
							</div>
						</div>
					</div>
					<UserList
						:items="filteredEmployees"
						@dragstart="onDragStart"
						@drop="onDropToEmployee"
						:selectedUserId="selectedUserId"
					/>
				</div>
			</div>
		</div>
	`
	};

	const ChatTab = {
	  name: 'chatTab',
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  computed: {
	    unavailableChatList() {
	      return [{
	        text: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_LIST_ITEM_1')
	      }, {
	        text: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_LIST_ITEM_2')
	      }];
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__tab-container --empty">
			<div class="hr-department-detail-content__tab-entity-icon --chat --default"></div>
			<div class="hr-department-detail-content__tab-entity-content">
				<div class="hr-department-detail-content__empty-tab-entity-title">
					{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_TITLE') }}
				</div>
				<div class="hr-department-detail-content__empty-tab-entity-list">
					<div
						v-for="item in unavailableChatList"
						class="hr-department-detail-content__empty-tab-entity-item --check"
					>
						{{ item.text }}
					</div>
				</div>
			</div>
		</div>
	`
	};

	const DepartmentContent = {
	  name: 'departmentContent',
	  components: {
	    UsersTab,
	    ChatTab
	  },
	  emits: ['showDetailLoader', 'hideDetailLoader', 'editEmployee'],
	  data() {
	    return {
	      activeTab: 'usersTab',
	      tabs: [{
	        name: 'usersTab',
	        component: 'usersTab',
	        id: 'users-tab'
	      }, {
	        name: 'chatTab',
	        component: 'ChatTab',
	        id: 'chats-tab',
	        soon: true
	      }],
	      isDescriptionOverflowed: false,
	      isDescriptionExpanded: false
	    };
	  },
	  mounted() {
	    this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    selectTab(tabName) {
	      this.activeTab = tabName;
	    },
	    getTabLabel(name) {
	      if (name === 'usersTab') {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TITLE');
	      }
	      if (name === 'chatTab') {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE');
	      }
	      return '';
	    },
	    toggleDescriptionExpand() {
	      this.isDescriptionExpanded = !this.isDescriptionExpanded;
	    },
	    checkDescriptionOverflowed() {
	      var _this$$refs$descripti;
	      const descriptionContainer = (_this$$refs$descripti = this.$refs.descriptionContainer) != null ? _this$$refs$descripti : null;
	      if (descriptionContainer) {
	        return descriptionContainer.scrollWidth > descriptionContainer.clientWidth;
	      }
	      return false;
	    },
	    hideDetailLoader() {
	      this.$emit('hideDetailLoader');
	      this.$nextTick(() => {
	        this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	      });
	    }
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments']),
	    activeTabComponent() {
	      const activeTab = this.tabs.find(tab => tab.name === this.activeTab);
	      return activeTab ? activeTab.component : null;
	    },
	    count() {
	      var _this$departments$get, _this$departments$get2;
	      return (_this$departments$get = (_this$departments$get2 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get2.userCount) != null ? _this$departments$get : 0;
	    },
	    tabArray() {
	      return this.tabs.map(tab => {
	        if (tab.name === 'usersTab') {
	          return {
	            ...tab,
	            count: this.count
	          };
	        }
	        return tab;
	      });
	    },
	    description() {
	      const department = this.departments.get(this.focusedNode);
	      if (!department.description) {
	        return null;
	      }
	      return department.description;
	    }
	  },
	  watch: {
	    description() {
	      this.$nextTick(() => {
	        this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	      });
	    },
	    focusedNode() {
	      this.isDescriptionExpanded = false;
	      this.selectTab('usersTab');
	    }
	  },
	  template: `
		<div class="hr-department-detail-content hr-department-detail-content__scope">
			<div
				ref="descriptionContainer"
				v-show="description"
				:class="[
					'hr-department-detail-content-description',
					{ '--expanded': isDescriptionExpanded },
					{ '--overflowed': isDescriptionOverflowed},
				]"
				v-on="isDescriptionOverflowed ? { click: toggleDescriptionExpand } : {}"
			>
				{{ description }}
			</div>
			<div class="hr-department-detail-content__tab-list">
				<button
					v-for="tab in tabArray"
					:key="tab.name"
					class="hr-department-detail-content__tab-item"
					:class="[{'--active-tab' : activeTab === tab.name}, {'--soon' : tab.soon}]"
					:data-title="tab.soon ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_BADGE_SOON') : null"
					@click="selectTab(tab.name)"
					:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_button' : null"
				>
					{{ this.getTabLabel(tab.name) }}
					<span
						v-if="!tab.soon"
						class="hr-department-detail-content__tab-count"
						:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_counter' : null"
					>{{ tab.count }}
					</span>
				</button>
			</div>
			<component
				:is="activeTabComponent"
				@editDepartmentUsers="$emit('editEmployee')"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
		</div>
	`
	};

	exports.DepartmentContent = DepartmentContent;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX,BX.UI.EntitySelector,BX,BX,BX.UI,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX,BX,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI,BX.Humanresources.CompanyStructure,BX.Vue3.Pinia));
//# sourceMappingURL=department-content.bundle.js.map

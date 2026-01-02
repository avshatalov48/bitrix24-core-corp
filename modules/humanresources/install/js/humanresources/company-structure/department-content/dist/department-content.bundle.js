/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_companyStructure_userManagementDialog,ui_iconSet_crm,humanresources_companyStructure_api,ui_tooltip,ui_buttons,humanresources_companyStructure_permissionChecker,ui_iconSet_api_core,ui_iconSet_main,main_core_events,humanresources_companyStructure_orgChart,ui_notification,im_public_iframe,humanresources_companyStructure_utils,ui_avatar,main_core,humanresources_companyStructure_structureComponents,humanresources_companyStructure_chartStore,ui_vue3_pinia) {
	'use strict';

	const HeadListDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_users-tab__head-list-container',
	  listActionButtonDataTestId: 'hr-department-content_users-tab__head-list-action-button',
	  listActonMenuDataTestId: 'hr-department-content_users-tab__head-list-action-menu-container',
	  listCounterDataTestId: 'hr-department-content_users-tab__head-list-counter'
	});
	const EmployeeListDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_users-tab__employee-list-container',
	  listActionButtonDataTestId: 'hr-department-content_users-tab__employee-list-action-button',
	  listActonMenuDataTestId: 'hr-department-content_users-tab__employee-list-action-menu-container',
	  listCounterDataTestId: 'hr-department-content_users-tab__employee-list-counter'
	});

	const EmptyTabAddButton = {
	  name: 'emptyStateContainer',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  props: {
	    departmentId: {
	      type: Number,
	      required: true
	    },
	    entityType: {
	      type: String,
	      default: humanresources_companyStructure_utils.EntityTypes.department
	    }
	  },
	  data() {
	    return {
	      menuVisible: false
	    };
	  },
	  computed: {
	    menu() {
	      return new humanresources_companyStructure_orgChart.EmptyUsersTabActionMenu(this.departmentId, humanresources_companyStructure_api.AnalyticsSourceType.DETAIL, null, this.entityType);
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onClick() {
	      if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team && this.menu.items.length === 1) {
	        // for teams for now there can be only one item, so we invoke action right away
	        this.onActionMenuItemClick(this.menu.items[0].id);
	      } else {
	        this.menuVisible = true;
	      }
	    },
	    onActionMenuItemClick(actionId) {
	      this.menu.onActionMenuItemClick(actionId);
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__users-empty-tab-add_buttons-container">
			<button
				class="hr-add-employee-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
				ref="actionMenuButton"
				@click.stop="onClick"
				data-id="hr-department-detail-content__user-empty-tab_add-user-button"
			>
				<span class="hr-add-employee-empty-tab-entity-btn-text">{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_ADD_BUTTON')}}</span>
			</button>
			<RouteActionMenu
				v-if="menuVisible"
				:id="'empty-state-department-detail-add-menu-' + focusedNode"
				:items="menu.items"
				:width="302"
				:bindElement="$refs['actionMenuButton']"
				@action="onActionMenuItemClick"
				@close="menuVisible = false"
			/>
		</div>
	`
	};

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
	  moveUserToDepartment: (nodeId, userId, targetNodeId, role) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.moveUser', {
	      nodeId,
	      userId,
	      targetNodeId,
	      roleXmlId: role
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
	  fireUser: userId => {
	    return humanresources_companyStructure_api.postData('intranet.user.fire', {
	      userId
	    });
	  },
	  findMemberByQuery: (nodeId, query) => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.find', {
	      nodeId,
	      query
	    });
	  },
	  getChatsAndChannels: nodeId => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.Chat.getList', {
	      nodeId
	    });
	  },
	  saveChats: (nodeId, ids, removeIds, withChildren = 0) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveChatList', {
	      nodeId,
	      ids,
	      removeIds,
	      withChildren
	    });
	  },
	  saveChannel: (nodeId, ids, removeIds, withChildren = 0) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveChannelList', {
	      nodeId,
	      ids,
	      removeIds,
	      withChildren
	    });
	  },
	  saveCollab: (nodeId, ids, removeIds, withChildren = 0) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveCollabList', {
	      nodeId,
	      ids,
	      removeIds,
	      withChildren
	    });
	  }
	};

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
	    MoveUserPopup: humanresources_companyStructure_structureComponents.MoveUserPopup
	  },
	  data() {
	    return {
	      menuVisible: {},
	      showRemoveUserConfirmationPopup: false,
	      showRemoveUserConfirmationActionLoader: false,
	      showMoveUserPopup: false,
	      showMoveUserPopupOnlyMoveMode: false,
	      showFireUserPopup: false,
	      fireUserLoad: false
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
	      if (actionId === humanresources_companyStructure_orgChart.MenuActions.removeUserFromDepartment) {
	        if (this.isTeamEntity) {
	          this.showRemoveUserConfirmationPopup = true;
	        } else {
	          this.showMoveUserPopupOnlyMoveMode = false;
	          this.showMoveUserPopup = true;
	        }
	      }
	      if (actionId === humanresources_companyStructure_orgChart.MenuActions.moveUserToAnotherDepartment) {
	        this.showMoveUserPopupOnlyMoveMode = true;
	        this.showMoveUserPopup = true;
	      }
	      if (actionId === humanresources_companyStructure_orgChart.MenuActions.fireUserFromCompany) {
	        this.showFireUserPopup = true;
	      }
	    },
	    async removeUser() {
	      var _this$departments$get;
	      this.showRemoveUserConfirmationActionLoader = true;
	      const userId = this.user.id;
	      const isUserInMultipleDepartments = await DepartmentAPI.isUserInMultipleDepartments(userId);
	      const departmentId = this.focusedNode;
	      this.showRemoveUserConfirmationActionLoader = false;
	      this.showRemoveUserConfirmationPopup = false;
	      this.showMoveUserPopup = false;
	      try {
	        await DepartmentAPI.removeUserFromDepartment(departmentId, userId);
	      } catch {
	        const phraseCode = this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_ERROR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_ERROR');
	        ui_notification.UI.Notification.Center.notify({
	          content: phraseCode,
	          autoHideDelay: 2000
	        });
	        return;
	      }
	      const phraseCode = this.isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_SUCCESS' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUCCESS';
	      const departmentName = main_core.Text.encode((_this$departments$get = this.departments.get(this.focusedNode).name) != null ? _this$departments$get : '');
	      ui_notification.UI.Notification.Center.notify({
	        content: this.loc(phraseCode, {
	          '#DEPARTMENT_NAME#': departmentName
	        }),
	        autoHideDelay: 2000
	      });
	      const role = this.user.role;
	      if (isUserInMultipleDepartments || this.isTeamEntity) {
	        humanresources_companyStructure_chartStore.UserService.removeUserFromEntity(departmentId, userId, role);
	        return;
	      }
	      const rootDepartment = [...this.departments.values()].find(department => department.parentId === 0);
	      if (!rootDepartment) {
	        return;
	      }
	      humanresources_companyStructure_chartStore.UserService.moveUserToEntity(departmentId, userId, rootDepartment.id, role);
	    },
	    cancelRemoveUser() {
	      this.showRemoveUserConfirmationPopup = false;
	    },
	    async fireUser() {
	      this.fireUserLoad = true;
	      const userId = this.user.id;
	      try {
	        await DepartmentAPI.fireUser(userId);
	      } catch (error) {
	        if (error.code !== 'STRUCTURE_ACCESS_DENIED') {
	          ui_notification.UI.Notification.Center.notify({
	            content: this.user.isInvited ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_ERROR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_ERROR'),
	            autoHideDelay: 2000
	          });
	        }
	        this.showFireUserPopup = false;
	        this.fireUserLoad = false;
	        return;
	      }
	      humanresources_companyStructure_chartStore.UserService.removeUserFromEntity(this.focusedNode, userId, this.user.role);
	      await humanresources_companyStructure_chartStore.UserService.removeUserFromAllEntities(userId);
	      this.showFireUserPopup = false;
	      this.fireUserLoad = false;
	    },
	    cancelFireUser() {
	      this.showFireUserPopup = false;
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
	    this.menu = new humanresources_companyStructure_orgChart.UserListActionMenu(this.focusedNode, this.entityType, this.user.isInvited);
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments']),
	    memberRoles() {
	      return humanresources_companyStructure_api.memberRoles;
	    },
	    getFirePopupDescription() {
	      var _this$user$name, _this$user$url;
	      const userName = main_core.Text.encode((_this$user$name = this.user.name) != null ? _this$user$name : '');
	      const userUrl = main_core.Text.encode((_this$user$url = this.user.url) != null ? _this$user$url : '');
	      const genderSuffix = this.user.gender === 'F' ? '_F' : '_M';
	      const phrase = this.user.isInvited ? `HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_DESCRIPTION${genderSuffix}` : `HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_DESCRIPTION${genderSuffix}`;
	      const description = this.loc(phrase, {
	        '#USER_NAME#': userName
	      });
	      return description.replace('[link]', `<a class="hr-department-detail-content__fire-user-link" href="${userUrl}">`).replace('[/link]', '</a>');
	    },
	    getFirePopupTitle() {
	      return this.user.isInvited ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_TITLE');
	    },
	    getFirePopupButtonText() {
	      return this.user.isInvited ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_DELETE_POPUP_CONFIRM_BUTTON') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_FIRE_POPUP_CONFIRM_BUTTON');
	    },
	    getRemovePopupTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_POPUP_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_TITLE');
	    },
	    getRemovePopupDescription() {
	      var _this$departments$get2, _this$user$name2;
	      const departmentName = main_core.Text.encode((_this$departments$get2 = this.departments.get(this.focusedNode).name) != null ? _this$departments$get2 : '');
	      const userName = main_core.Text.encode((_this$user$name2 = this.user.name) != null ? _this$user$name2 : '');
	      if (this.isTeamEntity) {
	        const phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_TEAM_POPUP_DESCRIPTION';
	        const genderSuffix = this.user.gender === 'F' ? '_F' : '_M';
	        return this.loc(phraseCode + genderSuffix, {
	          '#USER_NAME#': userName,
	          '#DEPARTMENT_NAME#': departmentName
	        }).replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`).replace('[/link]', '</a>');
	      }
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_DESCRIPTION');
	    },
	    entityType() {
	      var _this$departments$get3;
	      return (_this$departments$get3 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get3.entityType;
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    }
	  },
	  template: `
		<button
			v-if="menu.items.length"
			class="ui-icon-set --more hr-department-detail-content__user-action-btn"
			:class="{ '--focused': menuVisible[user.id] }"
			@click.stop="toggleMenu(user.id)"
			ref="actionUserButton"
			:data-id="'hr-department-detail-content__'+ getMemberKeyByValue(user.role) + '-list_user-' + user.id + '-action-btn'"
		/>
		<RouteActionMenu
			v-if="menuVisible[user.id]"
			:id="'tree-node-department-menu-' + user.id"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.actionUserButton"
			@action="onActionMenuItemClick"
			@close="menuVisible[user.id] = false"
		/>
		<ConfirmationPopup
			ref="removeUserConfirmationPopup"
			v-if="showRemoveUserConfirmationPopup"
			:showActionButtonLoader="showRemoveUserConfirmationActionLoader"
			:title="getRemovePopupTitle"
			:confirmBtnText="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_REMOVE_FROM_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			:confirmButtonClass="isTeamEntity ? 'ui-btn-danger' : 'ui-btn-primary'"
			@action="removeUser"
			@close="cancelRemoveUser"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
				<div
					v-html="getRemovePopupDescription"
				/>
				</div>
			</template>
		</ConfirmationPopup>
		<ConfirmationPopup
			ref="fireUserConfirmationPopup"
			v-if="showFireUserPopup"
			:showActionButtonLoader="fireUserLoad"
			:title="getFirePopupTitle"
			:confirmBtnText="getFirePopupButtonText"
			:confirmButtonClass="'ui-btn-danger'"
			@action="fireUser"
			@close="cancelFireUser"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div
						v-html="getFirePopupDescription"
					/>
				</div>
			</template>
		</ConfirmationPopup>
		<MoveUserPopup
			v-if="showMoveUserPopup"
			:originalNodeId="focusedNode"
			:user="user"
			:entityType="entityType"
			:onlyMove="showMoveUserPopupOnlyMoveMode"
			@action="handleMoveUserAction"
			@close="handleMoveUserClose"
			@remove="removeUser"
		/>
	`
	};

	const UserListItem = {
	  name: 'userList',
	  emits: ['itemDragStart'],
	  props: {
	    user: {
	      type: Object,
	      required: true
	    },
	    selectedUserId: {
	      type: Number,
	      required: false,
	      default: null
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    listType: {
	      type: String,
	      required: true
	    },
	    canDrag: {
	      type: Boolean,
	      default: false
	    }
	  },
	  components: {
	    UserListItemActionButton
	  },
	  computed: {
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.entityType);
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
	      BX.SidePanel.Instance.open(item.url, {
	        cacheable: false
	      });
	    },
	    handleMouseDown(event) {
	      event.preventDefault();
	      this.$emit('itemDragStart', {
	        userId: this.user.id,
	        initialListType: this.listType,
	        event,
	        element: this.$el
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
			<span v-if="canDrag"
				  @mousedown="handleMouseDown"
				  class="hr-department-detail-content__dnd-icon ui-icon-set --more-points">
			</span>
			<div class="hr-department-detail-content__user-avatar-container" @click="handleUserClick(user)">
				<img 
					class="hr-department-detail-content__user-avatar-img"
					:src="user.avatar ? encodeURI(user.avatar) : defaultAvatar"
				    alt=""
				/>
				<div v-if="user.role === memberRoles.head" class="hr-department-detail-content__user-avatar-overlay"></div>
			</div>
			<div class="hr-department-detail-content-user__text-container">
				<div class="hr-department-detail-content__user-title">
					<div 
						class="hr-department-detail-content__user-name" 
						@click="handleUserClick(user)"
						:bx-tooltip-user-id="user.id"
						bx-tooltip-context="b24"
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

	const EmptyState = {
	  name: 'emptyState',
	  props: {
	    title: {
	      type: String,
	      required: false
	    },
	    imageClass: {
	      type: String,
	      required: false
	    },
	    description: {
	      type: String,
	      required: false
	    },
	    list: {
	      type: Array,
	      required: false,
	      default: []
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div class="hr-department-detail-content_tab__empty-state-container">
			<div v-if="imageClass" :class="['hr-department-detail-content_tab__empty-state-icon', imageClass]"/>
			<span v-if="title" class="hr-department-detail-content__empty-tab-entity-title">
				{{ title }}
			</span>
			<span v-if="description" class="hr-department-detail-content_tab__empty-state-description">
				{{ description }}
			</span>
			<div v-if="list.length > 0" class="hr-department-detail-content_tab__empty-state-list">
				<div class="hr-department-detail-content_tab__empty-state-list-item"  v-for="item in list">
					<div 
						class="ui-icon-set --circle-check hr-department-detail-content_tab__empty-state-list-item-check"
						style="--ui-icon-set__icon-size: 24px;"
					/>
					<div class="hr-department-detail-content_tab__empty-state-list--item-text">
						{{ item.text }}
					</div>
				</div>
			</div>
			<slot name="content"/>
		</div>
	`
	};

	const EmptyListItem = {
	  name: 'tabEmptyListItem',
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    imageClass: {
	      type: String,
	      required: true
	    },
	    withAddPermission: {
	      type: Boolean,
	      required: false,
	      default: true
	    }
	  },
	  template: `
		<div :class="['hr-department-detail-content__tab-empty-list_item-wrapper', { '--with-add': withAddPermission }]">
			<div class="hr-department-detail-content__tab-empty-list_item-image" :class="imageClass"/>
			<div class="hr-department-detail-content__tab-empty-list_item-content">
				<div class="hr-department-detail-content__tab-empty-list_item-title">
					{{ title }}
				</div>
			</div>
		</div>
	`
	};

	const ListActionButton = {
	  name: 'userListActionButton',
	  emits: ['tabListAction', 'close'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    menuItems: {
	      type: Array,
	      required: true
	    },
	    dataTestIds: {
	      type: Object,
	      required: false,
	      default: {}
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
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
	    onActionMenuItemClick(itemId) {
	      this.$emit('tabListAction', itemId);
	    }
	  },
	  template: `
		<button
			v-if="menuItems.length"
			class="hr-department-detail-content__tab-list_header-button"
			:class="{ '--focused': menuVisible }"
			:ref="id + '-route-action-menu'"
			@click.stop="menuVisible = true"
			:data-test-id="dataTestIds.buttonDataTestId"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_LIST_ACTION_BUTTON_TITLE') }}
		</button>
		<RouteActionMenu
			v-if="menuVisible"
			:id="id + '-route-action-menu'"
			:items="menuItems"
			:width="302"
			:bindElement="$refs[id + '-route-action-menu']"
			:containerDataTestId="dataTestIds.containerDataTestId"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	const TabList = {
	  name: 'tabList',
	  components: {
	    ListActionButton,
	    EmptyListItem
	  },
	  emits: ['tabListAction'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    count: {
	      type: Number,
	      required: false
	    },
	    menuItems: {
	      type: Array,
	      required: false,
	      default: []
	    },
	    listItems: {
	      type: Array,
	      required: true
	    },
	    emptyItemTitle: {
	      type: String,
	      required: false
	    },
	    emptyItemImageClass: {
	      type: String,
	      required: false
	    },
	    hideEmptyItem: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    withAddPermission: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    /** @var { TabListDataTestIds } dataTestIds */
	    dataTestIds: {
	      type: Object,
	      required: false,
	      default: {}
	    },
	    isDropTarget: {
	      type: Boolean,
	      default: false
	    },
	    listType: {
	      type: String
	    }
	  },
	  data() {
	    return {
	      placeholderIndex: null,
	      boundHandleDragOverList: null
	    };
	  },
	  computed: {
	    needToShowCount() {
	      return main_core.Type.isNumber(this.count);
	    }
	  },
	  watch: {
	    isDropTarget(newValue, oldValue) {
	      if (newValue === oldValue) {
	        return;
	      }
	      const itemsWrapper = this.$refs.itemsWrapper;
	      if (!itemsWrapper) {
	        return;
	      }
	      if (newValue) {
	        this.boundHandleDragOverList = main_core.Runtime.throttle(this.handleDragOverList.bind(this), 10);
	        main_core.Event.bind(itemsWrapper, 'mousemove', this.boundHandleDragOverList);
	        if (this.listItems.length === 0) {
	          this.placeholderIndex = 0;
	        }
	      } else {
	        main_core.Event.unbind(itemsWrapper, 'mousemove', this.boundHandleDragOverList);
	        this.boundHandleDragOverList = null;
	        this.placeholderIndex = null;
	      }
	    }
	  },
	  methods: {
	    onActionMenuItemClick(actionId) {
	      this.$emit('tabListAction', actionId);
	    },
	    handleDragOverList(event) {
	      if (!this.isDropTarget || !this.$refs.itemsWrapper) {
	        if (this.placeholderIndex !== null) {
	          this.placeholderIndex = null;
	        }
	        return;
	      }
	      const itemsWrapper = this.$refs.itemsWrapper;
	      const children = [...itemsWrapper.querySelectorAll('.hr-department-detail-content__user-container:not(.--dragging)')];
	      const mouseY = event.clientY;
	      if (children.length === 0) {
	        if (this.placeholderIndex !== 0) {
	          this.placeholderIndex = 0;
	        }
	        return;
	      }
	      let newIndex = children.length;
	      for (const [i, child] of children.entries()) {
	        const rect = child.getBoundingClientRect();
	        const childMidpointY = rect.top + rect.height / 2;
	        if (mouseY < childMidpointY) {
	          newIndex = i;
	          break;
	        }
	      }
	      if (newIndex !== this.placeholderIndex) {
	        this.placeholderIndex = newIndex;
	      }
	    }
	  },
	  template: `
		<div
			class="hr-department-detail-content__tab-list_container"
			 :data-test-id="dataTestIds.containerDataTestId"
		>
			<div class="hr-department-detail-content__tab-list_header-container">
				<div class="hr-department-detail-content__tab-list_list-title">
					{{ title }}
					<span
						v-if="needToShowCount"
						class="hr-department-detail-content__tab-list_header-count"
						:data-test-id="dataTestIds.listCounterDataTestId"
					>
					{{ count }}
				</span>
				</div>
				<ListActionButton
					:id="id"
					:menuItems="menuItems"
					@tabListAction="onActionMenuItemClick"
					:dataTestIds="{buttonDataTestId: dataTestIds.listActionButtonDataTestId, containerDataTestId: dataTestIds.listActonMenuDataTestId}"
				/>
			</div>
			<div class="hr-department-detail-content__tab_list-container" ref="itemsWrapper">
				<div
					v-if="placeholderIndex === 0 && isDropTarget"
					class="hr-department-detail-content__drop-placeholder"
				></div>
				<template v-for="(item, index) in listItems" :key="item.id">
					<slot :item="item"
					/>
					<div
						v-if="placeholderIndex === (index + 1) && isDropTarget"
						class="hr-department-detail-content__drop-placeholder"
					></div>
				</template>
				<slot name="footer" />
				<EmptyListItem v-if="emptyItemTitle && emptyItemImageClass && !listItems.length && !hideEmptyItem"
					:title="emptyItemTitle"
					:imageClass="emptyItemImageClass"
					:withAddPermission="withAddPermission"
				/>
			</div>
		</div>
	`
	};

	const SearchInput = {
	  name: 'searchInput',
	  props: {
	    value: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    placeholder: {
	      type: String,
	      required: true
	    },
	    dataTestId: {
	      type: String,
	      required: false
	    }
	  },
	  data() {
	    return {
	      hasFocus: false
	    };
	  },
	  methods: {
	    handleInput(event) {
	      this.$emit('inputChange', event.target.value);
	    },
	    handleBlur() {
	      if (this.value.length === 0) {
	        this.hasFocus = false;
	      }
	    },
	    clearInput() {
	      this.hasFocus = false;
	      this.$emit('inputChange', '');
	    }
	  },
	  template: `
		<div
			class="hr-department-detail-content__content-search"
			:class="{'--focused': hasFocus}"
		>
			<div class="hr-department-detail-content__content-search-icon ui-icon-set --search-1"/>
			<input
				class="hr-department-detail-content__content-search-input"
				type="text"
				:placeholder="!hasFocus ?  placeholder : ''"
				:data-test-id="dataTestId"
				:value="value"
				@input="handleInput"
				@focus="hasFocus = true"
				@blur="handleBlur"
			>
			<div
				class="hr-department-detail-content__content-search-close-button ui-icon-set --cross-circle-50"
				:class="{'--hide': !hasFocus}"
				@click="clearInput"
			/>
		</div>
	`
	};

	const DepartmentContentActions = {
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
	  updateHeads: (departmentId, heads) => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const department = departments.get(departmentId);
	    if (!department) {
	      return;
	    }
	    departments.set(departmentId, {
	      ...department,
	      heads
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
	  },
	  setChatsAndChannels: (nodeId, chats, channels, collabs, chatsNoAccess = 0, channelsNoAccess = 0, collabsNoAccess = 0) => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const department = store.departments.get(nodeId);
	    if (!department) {
	      return;
	    }
	    department.channelsDetailed = channels;
	    department.chatsDetailed = chats;
	    department.collabsDetailed = collabs;
	    department.channelsNoAccess = channelsNoAccess;
	    department.chatsNoAccess = chatsNoAccess;
	    department.collabsNoAccess = collabsNoAccess;
	    department.communicationsCount = chats.length + channels.length + collabs.length + chatsNoAccess + channelsNoAccess + collabsNoAccess;
	  },
	  unbindChatFromNode: (nodeId, chatId, type) => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const department = store.departments.get(nodeId);
	    if (!department) {
	      return;
	    }
	    if (type === humanresources_companyStructure_utils.ChatTypes.collab) {
	      department.collabsDetailed = department.collabsDetailed.filter(chat => chat.id !== chatId);
	    } else {
	      department.channelsDetailed = department.channelsDetailed.filter(chat => chat.id !== chatId);
	      department.chatsDetailed = department.chatsDetailed.filter(chat => chat.id !== chatId);
	    }
	    department.communicationsCount--;
	  }
	};

	const AUTOSCROLL_AREA_SIZE = 70;

	// @vue/component
	const UsersTab = {
	  name: 'usersTab',
	  components: {
	    SearchInput,
	    EmptyState,
	    TabList,
	    EmptyTabAddButton,
	    UserListItem,
	    MoveEmployeeConfirmationPopup: humanresources_companyStructure_structureComponents.MoveEmployeeConfirmationPopup
	  },
	  emits: ['showDetailLoader', 'hideDetailLoader'],
	  data() {
	    return {
	      searchQuery: '',
	      selectedUserId: null,
	      needToScroll: false,
	      dragState: this.getInitialDragState(),
	      showDndConfirmationPopup: false,
	      dndPreviousState: null,
	      dndPopupDescription: '',
	      boundHandleDragMove: null,
	      boundHandleDragEnd: null
	    };
	  },
	  computed: {
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.entityType);
	    },
	    heads() {
	      var _this$departments$get;
	      return (_this$departments$get = this.departments.get(this.focusedNode).heads) != null ? _this$departments$get : [];
	    },
	    headCount() {
	      var _this$heads$length;
	      return (_this$heads$length = this.heads.length) != null ? _this$heads$length : 0;
	    },
	    departmentId() {
	      return this.focusedNode;
	    },
	    formattedHeads() {
	      return this.heads.map(head => ({
	        ...head,
	        subtitle: head.workPosition,
	        badgeText: this.getBadgeText(head.role)
	      })).sort((a, b) => {
	        const roleOrder = {
	          [this.memberRoles.head]: 1,
	          [this.memberRoles.deputyHead]: 2
	        };
	        const roleA = roleOrder[a.role] || 3;
	        const roleB = roleOrder[b.role] || 3;
	        return roleA - roleB;
	      });
	    },
	    filteredHeads() {
	      return this.formattedHeads.filter(head => {
	        var _head$workPosition;
	        return head.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || ((_head$workPosition = head.workPosition) == null ? void 0 : _head$workPosition.toLowerCase().includes(this.searchQuery.toLowerCase()));
	      });
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
	    showSearchBar() {
	      return this.memberCount > 0;
	    },
	    showSearchEmptyState() {
	      return this.filteredHeads.length === 0 && this.filteredEmployees.length === 0;
	    },
	    canAddUsers() {
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      if (!permissionChecker) {
	        return false;
	      }
	      const nodeId = this.focusedNode;
	      const permission = this.isTeamEntity ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	      return permissionChecker.hasPermission(permission, nodeId);
	    },
	    headListEmptyStateTitle() {
	      if (this.canAddUsers) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_EMPTY_LIST_ITEM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
	    },
	    employeesListEmptyStateTitle() {
	      if (this.canAddUsers) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_EMPTY_LIST_ITEM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
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
	    },
	    headMenu() {
	      return new humanresources_companyStructure_orgChart.UsersTabActionMenu(this.focusedNode, humanresources_companyStructure_api.AnalyticsSourceType.DETAIL, 'head', this.entityType);
	    },
	    employeeMenu() {
	      return new humanresources_companyStructure_orgChart.UsersTabActionMenu(this.focusedNode, humanresources_companyStructure_api.AnalyticsSourceType.DETAIL, 'employee', this.entityType);
	    },
	    isEmployeeListOptionsSet() {
	      const department = this.departments.get(this.focusedNode) || {};
	      return department.employeeListOptions && Object.keys(department.employeeListOptions).length > 0;
	    },
	    employeeTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_LIST_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_LIST_TITLE');
	    },
	    entityType() {
	      var _this$departments$get8;
	      return (_this$departments$get8 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get8.entityType;
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    emptyStateTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TEAM_ADD_USER_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_TITLE');
	    },
	    emptyStateDescription() {
	      if (this.canAddUsers) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TEAM_ADD_USER_SUBTITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
	      }

	      // text is in progress
	      return this.isTeamEntity ? '' : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
	    },
	    isHeadListPotentialTarget() {
	      return this.dragState.isDragging && this.dragState.initialList === 'employees';
	    },
	    isEmployeeListPotentialTarget() {
	      return this.dragState.isDragging && (this.dragState.initialList === 'head' || this.dragState.initialList === 'deputyHead');
	    },
	    dndConfirmationPopupTitle() {
	      const toHead = this.dragState.targetList === 'head';
	      return toHead ? undefined : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_TITLE');
	    },
	    dndConfirmationPopupBtn() {
	      const toHead = this.dragState.targetList === 'head';
	      return toHead ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_BTN_TO_HEAD') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_BTN');
	    },
	    showDndRoleSelect() {
	      return this.dragState.targetList === 'head';
	    },
	    headListTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_LIST_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_LIST_TITLE');
	    }
	  },
	  watch: {
	    focusedNode(newId) {
	      const department = this.departments.get(newId) || {};
	      if (!this.isEmployeeListOptionsSet) {
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
	      if (this.searchedUserId) {
	        this.needToFocusUserId = this.searchedUserId;
	        this.$nextTick(() => {
	          this.scrollToUser();
	        });
	      }
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
	  created() {
	    this.loadEmployeesAction();
	    this.clearSearchTimeout = null;
	    this.autoscrollIntervalId = null;
	    this.boundHandleDragMove = this.handleDragMove.bind(this);
	    this.boundHandleDragEnd = this.handleDragEnd.bind(this);
	  },
	  mounted() {
	    this.tabContainer = this.$refs['tab-container'];
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    getBadgeText(role) {
	      if (role === this.memberRoles.head) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_TEAM_HEAD_BADGE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE');
	      }
	      if (role === this.memberRoles.deputyHead) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_TEAM_DEPUTY_HEAD_BADGE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_DEPUTY_HEAD_BADGE');
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
	    async loadEmployeesAction() {
	      var _this$departments$get9, _employeeListOptions$, _employeeListOptions$2, _employeeListOptions$3, _this$departments$get10, _this$departments$get11;
	      const nodeId = this.focusedNode;
	      if (!this.departments.get(nodeId)) {
	        return;
	      }
	      const employeeListOptions = (_this$departments$get9 = this.departments.get(nodeId).employeeListOptions) != null ? _this$departments$get9 : {};
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
	          role: this.memberRoles.employee
	        };
	      });
	      const employees = (_this$departments$get10 = (_this$departments$get11 = this.departments.get(nodeId)) == null ? void 0 : _this$departments$get11.employees) != null ? _this$departments$get10 : [];
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
	        if (user.role === this.memberRoles.head || user.role === this.memberRoles.deputyHead) {
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

	        // eslint-disable-next-line vue/valid-next-tick
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
	      if (this.clearSearchTimeout) {
	        clearTimeout(this.clearSearchTimeout);
	      }
	      this.clearSearchTimeout = setTimeout(() => {
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
	    searchUser(searchQuery) {
	      this.searchQuery = searchQuery;
	    },
	    onHeadListActionMenuItemClick(actionId) {
	      this.headMenu.onActionMenuItemClick(actionId);
	    },
	    onEmployeeListActionMenuItemClick(actionId) {
	      this.employeeMenu.onActionMenuItemClick(actionId);
	    },
	    getHeadListDataTestIds() {
	      return HeadListDataTestIds;
	    },
	    getEmployeeListDataTestIds() {
	      return EmployeeListDataTestIds;
	    },
	    handleItemDragStart(payload) {
	      if (this.dragState.isDragging) {
	        return;
	      }
	      const {
	        event,
	        element,
	        userId,
	        initialListType
	      } = payload;
	      event.preventDefault();
	      this.dragState.isDragging = true;
	      this.dragState.userId = userId;
	      this.dragState.initialList = initialListType;
	      this.dragState.draggedElement = element;
	      const rect = this.dragState.draggedElement.getBoundingClientRect();
	      this.dragState.offsetX = event.clientX - rect.left;
	      this.dragState.offsetY = event.clientY - rect.top;
	      const ghost = this.dragState.draggedElement.cloneNode(true);
	      main_core.Dom.addClass(ghost, '--ghost');
	      main_core.Dom.style(ghost, 'left', `${event.clientX - this.dragState.offsetX}px`);
	      main_core.Dom.style(ghost, 'top', `${event.clientY - this.dragState.offsetY}px`);
	      main_core.Dom.append(ghost, document.body);
	      this.dragState.ghostElement = ghost;
	      main_core.Dom.addClass(this.dragState.draggedElement, '--dragging');
	      main_core.Dom.addClass(document.body, '--user-dragging');
	      main_core.Event.bind(document, 'mousemove', this.boundHandleDragMove);
	      main_core.Event.bind(document, 'mouseup', this.boundHandleDragEnd);

	      // user to computed?
	      const user = this.formattedHeads.find(u => u.id === userId) || this.formattedEmployees.find(u => u.id === userId);
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_USER_DRAG_START, {
	        user
	      });
	    },
	    handleDragMove(event) {
	      var _this$$refs$headList, _this$$refs$employeeL;
	      if (!this.dragState.isDragging) {
	        return;
	      }
	      main_core.Dom.addClass(this.dragState.draggedElement, '--hidden');
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_USER_DRAG_MOVE, {
	        pageX: event.pageX,
	        pageY: event.pageY
	      });
	      if (this.dragState.ghostElement) {
	        main_core.Dom.style(this.dragState.ghostElement, 'left', `${event.clientX - this.dragState.offsetX}px`);
	        main_core.Dom.style(this.dragState.ghostElement, 'top', `${event.clientY - this.dragState.offsetY}px`);
	      }
	      let potentialTarget = null;
	      if (this.isHeadListPotentialTarget && this.isPointerOverElement(event, (_this$$refs$headList = this.$refs.headList) == null ? void 0 : _this$$refs$headList.$el)) {
	        potentialTarget = 'head';
	      } else if (this.isEmployeeListPotentialTarget && this.isPointerOverElement(event, (_this$$refs$employeeL = this.$refs.employeeList) == null ? void 0 : _this$$refs$employeeL.$el)) {
	        potentialTarget = 'employee';
	      }
	      this.dragState.targetList = potentialTarget;
	      this.handleAutoscrollOnDrag(event);
	    },
	    handleAutoscrollOnDrag(event) {
	      if (!this.isHeadListPotentialTarget) {
	        return;
	      }
	      const scrollableContainer = this.$refs.scrollableContainer;
	      if (!scrollableContainer) {
	        return;
	      }
	      const rect = scrollableContainer.getBoundingClientRect();
	      const mouseY = event.clientY;
	      const topTriggerZone = rect.top + AUTOSCROLL_AREA_SIZE;
	      if (mouseY < topTriggerZone) {
	        if (this.autoscrollIntervalId !== null) {
	          return;
	        }
	        const scrollSpeed = 10;
	        this.autoscrollIntervalId = setInterval(() => {
	          scrollableContainer.scrollTop -= scrollSpeed;
	        }, 16);
	      } else {
	        this.stopAutoscroll();
	      }
	    },
	    stopAutoscroll() {
	      if (this.autoscrollIntervalId !== null) {
	        clearInterval(this.autoscrollIntervalId);
	        this.autoscrollIntervalId = null;
	      }
	    },
	    isPointerOverElement(event, element) {
	      if (!element) {
	        return false;
	      }
	      const rect = element.getBoundingClientRect();
	      return event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
	    },
	    async handleDragEnd() {
	      if (!this.dragState.isDragging) {
	        return;
	      }
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_USER_DRAG_DROP);
	      this.stopAutoscroll();
	      const {
	        userId,
	        initialList,
	        targetList,
	        ghostElement,
	        draggedElement
	      } = this.dragState;
	      const isValidDrop = userId && targetList && targetList !== initialList;
	      let targetIndex = null;
	      if (isValidDrop) {
	        const listRef = targetList === 'head' ? this.$refs.headList : this.$refs.employeeList;
	        targetIndex = listRef == null ? void 0 : listRef.placeholderIndex;
	      }
	      main_core.Event.unbind(document, 'mousemove', this.boundHandleDragMove);
	      main_core.Event.unbind(document, 'mouseup', this.boundHandleDragEnd);
	      if (ghostElement) {
	        main_core.Dom.remove(ghostElement);
	      }
	      if (draggedElement) {
	        main_core.Dom.removeClass(draggedElement, '--dragging');
	        main_core.Dom.removeClass(draggedElement, '--hidden');
	      }
	      main_core.Dom.removeClass(document.body, '--user-dragging');
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_USER_DRAG_END);
	      if (!isValidDrop || targetIndex === null) {
	        this.dragState = this.getInitialDragState();
	        return;
	      }
	      this.dragState.isDragging = false;
	      this.dragState.ghostElement = null;
	      this.dragState.draggedElement = null;
	      this.dragState.context = {
	        targetIndex
	      };
	      this.dndPreviousState = this.moveDndUser();
	      if (!this.dndPreviousState) {
	        this.dragState = this.getInitialDragState();
	        return;
	      }
	      const {
	        context
	      } = this.dragState;
	      context.selectedRole = null;
	      context.selectedRoleLabel = '';
	      if (targetList === 'head') {
	        context.selectedRole = this.memberRoles.head;
	        context.selectedRoleLabel = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE');
	      }
	      await this.prepareDndPopupDescription();
	      this.showDndConfirmationPopup = true;
	    },
	    cancelDndUserMove() {
	      const {
	        heads,
	        employees
	      } = this.dndPreviousState;
	      DepartmentContentActions.updateHeads(this.focusedNode, heads);
	      DepartmentContentActions.updateEmployees(this.focusedNode, employees);
	      this.dndPreviousState = null;
	      this.dragState = this.getInitialDragState();
	      this.showDndConfirmationPopup = false;
	    },
	    async confirmDndUserMove(payload) {
	      const {
	        userId
	      } = this.dragState;
	      const targetRole = payload.role || this.memberRoles.employee;
	      const department = this.departments.get(this.focusedNode);
	      const user = department.heads.find(u => u.id === userId) || department.employees.find(u => u.id === userId);
	      if (!user) {
	        this.dragState = this.getInitialDragState();
	        return;
	      }
	      const previousRole = user.role;
	      user.role = targetRole;
	      try {
	        await humanresources_companyStructure_userManagementDialog.UserManagementDialogAPI.addUsersToDepartment(this.focusedNode, [userId], targetRole);
	        ui_notification.UI.Notification.Center.notify({
	          content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_SUCCEED'),
	          autoHideDelay: 3000
	        });
	      } catch (error) {
	        console.error(error);
	        user.role = previousRole;
	        this.cancelDndUserMove();
	      }
	      this.showDndConfirmationPopup = false;
	      this.dragState = this.getInitialDragState();
	    },
	    async prepareDndPopupDescription() {
	      var _department$name, _user$name, _user, _user$url, _user2;
	      const {
	        userId,
	        targetList
	      } = this.dragState;
	      const toHead = targetList === 'head';
	      const isTeam = this.isTeamEntity;
	      const department = this.departments.get(this.departmentId);
	      const departmentName = main_core.Text.encode((_department$name = department == null ? void 0 : department.name) != null ? _department$name : '');
	      let user = null;
	      try {
	        user = await DepartmentAPI.getUserInfo(this.departmentId, userId);
	      } catch {/* empty */}
	      const userName = main_core.Text.encode((_user$name = (_user = user) == null ? void 0 : _user.name) != null ? _user$name : '');
	      const userUrl = (_user$url = (_user2 = user) == null ? void 0 : _user2.url) != null ? _user$url : '#';
	      let phraseCode = '';
	      if (toHead) {
	        phraseCode = isTeam ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_TEAM_HEAD' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_HEAD';
	      } else {
	        var _user3;
	        const basePhrase = isTeam ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_TEAM' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC';
	        const genderSuffix = ((_user3 = user) == null ? void 0 : _user3.gender) === 'F' ? '_F' : '_M';
	        phraseCode = basePhrase + genderSuffix;
	      }
	      this.dndPopupDescription = this.loc(phraseCode, {
	        '#USER_NAME#': userName,
	        '#DEPARTMENT_NAME#': departmentName
	      }).replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${userUrl}">`).replace('[/link]', '</a>');
	    },
	    moveDndUser() {
	      const {
	        userId,
	        targetList,
	        context
	      } = this.dragState;
	      const {
	        targetIndex
	      } = context;
	      const department = this.departments.get(this.focusedNode);
	      if (!department) {
	        return null;
	      }
	      const previousState = {
	        heads: [...department.heads],
	        employees: [...department.employees]
	      };
	      const isMovingToHeads = targetList === 'head';
	      const sourceList = isMovingToHeads ? [...previousState.employees] : [...previousState.heads];
	      const targetListForMove = isMovingToHeads ? [...previousState.heads] : [...previousState.employees];
	      const userIndex = sourceList.findIndex(user => user.id === userId);
	      if (userIndex === -1) {
	        return null;
	      }
	      const [userToMove] = sourceList.splice(userIndex, 1);
	      targetListForMove.splice(targetIndex, 0, userToMove);
	      if (isMovingToHeads) {
	        DepartmentContentActions.updateHeads(this.focusedNode, targetListForMove);
	        DepartmentContentActions.updateEmployees(this.focusedNode, sourceList);
	      } else {
	        DepartmentContentActions.updateHeads(this.focusedNode, sourceList);
	        DepartmentContentActions.updateEmployees(this.focusedNode, targetListForMove);
	      }
	      return previousState;
	    },
	    getInitialDragState() {
	      return {
	        isDragging: false,
	        userId: null,
	        initialList: null,
	        targetList: null,
	        ghostElement: null,
	        draggedElement: null,
	        offsetX: 0,
	        offsetY: 0,
	        context: null
	      };
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__tab-container --users" ref="tab-container">
			<template v-if="memberCount > 0">
				<SearchInput
					v-if="showSearchBar"
					:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_SEARCH_PLACEHOLDER')"
					:value="searchQuery"
					@inputChange="searchUser"
					dataTestId="hr-department-detail-content_users-tab__users-search-input"
				/>
				<div
					v-if="!showSearchEmptyState"
					v-on="shouldUpdateList ? { scroll: updateList } : {}"
					class="hr-department-detail-content__lists-container"
					ref="scrollableContainer"
				>
					<TabList
						ref="headList"
						id='hr-department-detail-content_chats-tab__chat-list'
						:title="headListTitle"
						:count="headCount"
						:menuItems="headMenu.items"
						:listItems="filteredHeads"
						listType="head"
						:emptyItemTitle="headListEmptyStateTitle"
						emptyItemImageClass="hr-department-detail-content__empty-user-list-item-image"
						:hideEmptyItem="searchQuery.length > 0"
						:withAddPermission="canAddUsers"
						@tabListAction="onHeadListActionMenuItemClick"
						:dataTestIds="getHeadListDataTestIds()"
						:isDropTarget="isHeadListPotentialTarget && dragState.targetList === 'head'"
					>
						<template v-slot="{ item }">
							<UserListItem
								:user="item"
								listType="head"
								:selectedUserId="selectedUserId"
								:entityType="entityType"
								:canDrag="canAddUsers"
								@itemDragStart="handleItemDragStart"
							/>
						</template>
					</TabList>
					<TabList
						ref="employeeList"
						id='hr-department-detail-content_chats-tab__channel-list'
						:title="employeeTitle"
						:count="employeeCount"
						listType="employees"
						:menuItems="employeeMenu.items"
						:listItems="filteredEmployees"
						:emptyItemTitle="employeesListEmptyStateTitle"
						emptyItemImageClass="hr-department-detail-content__empty-user-list-item-image"
						:hideEmptyItem="searchQuery.length > 0"
						:withAddPermission="canAddUsers"
						@tabListAction="onEmployeeListActionMenuItemClick"
						:dataTestIds="getEmployeeListDataTestIds()"
						:isDropTarget="isEmployeeListPotentialTarget && dragState.targetList === 'employee'"
					>
						<template v-slot="{ item }">
							<UserListItem
								:user="item"
								listType="employees"
								:canDrag="canAddUsers"
								@itemDragStart="handleItemDragStart"
								:selectedUserId="selectedUserId"
								:entityType="entityType"
							/>
						</template>
					</TabList>
				</div>
				<EmptyState v-else
					imageClass="hr-department-detail-content__empty-tab-user-not-found-icon"
					:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE')"
					:description ="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE')"
				/>
			</template>
			<EmptyState v-else
				:imageClass="canAddUsers 
					? 'hr-department-detail-content__empty-tab-add-user-icon' 
					: 'hr-department-detail-content__empty-tab-cant-add-user-icon'"
				:title="emptyStateTitle"
				:description ="emptyStateDescription"
			>
				<template v-slot:content>
					<EmptyTabAddButton v-if="canAddUsers" :departmentId="departmentId" :entityType="entityType"/>
				</template>
			</EmptyState>
			<MoveEmployeeConfirmationPopup 
				v-if="showDndConfirmationPopup" 
				:title="dndConfirmationPopupTitle"
				:description="dndPopupDescription"
				:confirmButtonText="dndConfirmationPopupBtn"
				:showRoleSelect="showDndRoleSelect"
				:excludeEmployeeRole="true"
				:targetType="entityType"
				@confirm="confirmDndUserMove"
				@close="cancelDndUserMove"
			/>
		</div>
	`
	};

	const ChatsMenuOption = Object.freeze({
	  linkChat: 'linkChat',
	  linkChannel: 'linkChannel',
	  linkCollab: 'linkCollab'
	});
	const ChatsMenuLinkChat = Object.freeze({
	  id: ChatsMenuOption.linkChat,
	  title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LIST_LINK_BUTTON_TITLE'),
	  description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LIST_LINK_BUTTON_DESC'),
	  bIcon: {
	    name: ui_iconSet_api_core.Main.CHAT_MESSAGE,
	    size: 20,
	    colorTokenName: 'paletteBlue50'
	  },
	  dataTestId: 'hr-department-content_chats-tab__chat-list-action-link'
	});
	const ChatsMenuLinkChannel = Object.freeze({
	  id: ChatsMenuOption.linkChannel,
	  title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LIST_LINK_BUTTON_TITLE'),
	  description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LIST_LINK_BUTTON_DESC'),
	  bIcon: {
	    name: ui_iconSet_api_core.Main.SPEAKER_MOUTHPIECE,
	    size: 20,
	    colorTokenName: 'paletteBlue50'
	  },
	  dataTestId: 'hr-department-content_chats-tab__channel-list-action-link'
	});
	const ChatsMenuLinkCollab = Object.freeze({
	  id: ChatsMenuOption.linkCollab,
	  title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LIST_LINK_BUTTON_TITLE'),
	  description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LIST_LINK_BUTTON_DESC'),
	  bIcon: {
	    name: ui_iconSet_api_core.Main.COLLAB,
	    size: 20,
	    colorTokenName: 'paletteBlue50'
	  },
	  dataTestId: 'hr-department-content_chats-tab__collab-list-action-link'
	});
	const ChatListDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__chat-list-container',
	  listActionButtonDataTestId: 'hr-department-content_chats-tab__chat-list-action-button',
	  listActonMenuDataTestId: 'hr-department-content_chats-tab__chat-list-action-menu-container',
	  listCounterDataTestId: 'hr-department-content_chats-tab__chat-list-counter'
	});
	const ChannelListDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__channel-list-container',
	  listActionButtonDataTestId: 'hr-department-content_chats-tab__channel-list-action-button',
	  listActonMenuDataTestId: 'hr-department-content_chats-tab__channel-list-action-menu-container',
	  listCounterDataTestId: 'hr-department-content_chats-tab__channel-list-counter'
	});
	const CollabListDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__collab-list-container',
	  listActionButtonDataTestId: 'hr-department-content_chats-tab__collab-list-action-button',
	  listActonMenuDataTestId: 'hr-department-content_chats-tab__collab-list-action-menu-container',
	  listCounterDataTestId: 'hr-department-content_chats-tab__collab-list-counter'
	});
	const ChatLinkDialogDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__link-chat-container',
	  confirmButtonDataTestId: 'hr-department-content_chats-tab__link-chat-confirm-button',
	  cancelButtonDataTestId: 'hr-department-content_chats-tab__link-chat-cancel-button',
	  closeButtonDataTestId: 'hr-department-content_chats-tab__link-chat-close-button',
	  addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-chat-add-with-children'
	});
	const ChannelLinkDialogDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__link-channel-container',
	  confirmButtonDataTestId: 'hr-department-content_chats-tab__link-channel-confirm-button',
	  cancelButtonDataTestId: 'hr-department-content_chats-tab__link-channel-cancel-button',
	  closeButtonDataTestId: 'hr-department-content_chats-tab__link-channel-close-button',
	  addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-channel-add-with-children'
	});
	const CollabLinkDialogDataTestIds = Object.freeze({
	  containerDataTestId: 'hr-department-content_chats-tab__link-collab-container',
	  confirmButtonDataTestId: 'hr-department-content_chats-tab__link-collab-confirm-button',
	  cancelButtonDataTestId: 'hr-department-content_chats-tab__link-collab-cancel-button',
	  closeButtonDataTestId: 'hr-department-content_chats-tab__link-collab-close-button',
	  addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-collab-add-with-children'
	});

	// @vue/component
	const EmptyTabAddButtons = {
	  name: 'emptyStateButtons',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  props: {
	    canEditChat: {
	      type: Boolean,
	      required: true
	    },
	    canEditChannel: {
	      type: Boolean,
	      required: true
	    },
	    canEditCollab: {
	      type: Boolean,
	      required: true
	    },
	    isTeamEntity: {
	      type: Boolean,
	      required: true
	    }
	  },
	  emits: ['emptyStateAddAction'],
	  data() {
	    return {
	      menuVisible: false
	    };
	  },
	  computed: {
	    menu() {
	      const menu = [];
	      if (this.canEditCollab) {
	        menu.push(ChatsMenuLinkCollab);
	      }
	      if (this.canEditChannel) {
	        menu.push(ChatsMenuLinkChannel);
	      }
	      if (this.canEditChat) {
	        menu.push(ChatsMenuLinkChat);
	      }
	      return menu;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onClick() {
	      this.menuVisible = true;
	    },
	    onActionMenuItemClick(actionId) {
	      this.$emit('emptyStateAddAction', actionId);
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__users-empty-tab-add_buttons-container">
			<button
				class="hr-add-communications-empty-tab-entity-btn ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
				ref="actionMenuButton"
				@click.stop="onClick"
				data-id="hr-department-detail-content__user-empty-tab_add-user-button"
			>
				<span class="hr-add-communications-empty-tab-entity-btn-text">
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_ADD_BUTTON')}}
				</span>
			</button>
			<RouteActionMenu
				v-if="menuVisible"
				:id="'empty-state-department-detail-add-communications-menu-' + focusedNode"
				:items="menu"
				:delimiter="false"
				:width="302"
				:bindElement="$refs.actionMenuButton"
				:containerDataTestId="'empty-state-department-detail-add-communications-menu'"
				@action="onActionMenuItemClick"
				@close="menuVisible = false"
			/>
		</div>
	`
	};

	const ActionButtonDictionary = Object.freeze({
	  chat: {
	    team: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_TEAM_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_TEAM_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_TEAM_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_TEAM_SUCCESS'
	    },
	    department: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_DEPARTMENT_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_DEPARTMENT_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_DEPARTMENT_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHAT_FROM_DEPARTMENT_SUCCESS'
	    }
	  },
	  channel: {
	    team: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_TEAM_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_TEAM_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_TEAM_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_TEAM_SUCCESS'
	    },
	    department: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_DEPARTMENT_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_DEPARTMENT_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_DEPARTMENT_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CHANNEL_FROM_DEPARTMENT_SUCCESS'
	    }
	  },
	  collab: {
	    team: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_TEAM_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_TEAM_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_TEAM_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_TEAM_SUCCESS'
	    },
	    department: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_DEPARTMENT_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_DEPARTMENT_DESCRIPTION',
	      error: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_DEPARTMENT_ERROR',
	      success: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_COLLAB_FROM_DEPARTMENT_SUCCESS'
	    }
	  }
	});

	// @vue/component
	const CommunicationListItemActionButton = {
	  name: 'communicationListItemActionButton',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu,
	    ConfirmationPopup: humanresources_companyStructure_structureComponents.ConfirmationPopup
	  },
	  props: {
	    /** @type CommunicationDetailed */
	    communication: {
	      type: Object,
	      required: true
	    },
	    nodeId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isMenuVisible: false,
	      showUnbindConfirmationPopup: false,
	      unbindLoader: false,
	      dictionary: {}
	    };
	  },
	  computed: {
	    entityType() {
	      var _this$departments$get;
	      return (_this$departments$get = this.departments.get(this.nodeId)) == null ? void 0 : _this$departments$get.entityType;
	    },
	    isTeamEntity() {
	      var _this$departments$get2;
	      return ((_this$departments$get2 = this.departments.get(this.nodeId)) == null ? void 0 : _this$departments$get2.entityType) === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    menu() {
	      var _this$departments$get3;
	      const entityType = (_this$departments$get3 = this.departments.get(this.nodeId)) == null ? void 0 : _this$departments$get3.entityType;
	      return new humanresources_companyStructure_orgChart.ChatListActionMenu(entityType, this.communication, this.nodeId);
	    },
	    buttonDataId() {
	      const type = this.communication.type.toLowerCase();
	      return `hr-department-detail-content__${type}-list_chat-${this.communication.id}-action-btn`;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
	  },
	  created() {
	    this.dictionary = ActionButtonDictionary[this.communication.type.toLowerCase()] && ActionButtonDictionary[this.communication.type.toLowerCase()][this.entityType.toLowerCase()] || {};
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    toggleMenu() {
	      this.isMenuVisible = !this.isMenuVisible;
	    },
	    onActionMenuItemClick(actionId) {
	      if (actionId === humanresources_companyStructure_orgChart.MenuActions.openChat) {
	        im_public_iframe.Messenger.openChat(this.communication.dialogId);
	      } else if (actionId === humanresources_companyStructure_orgChart.MenuActions.unbindChat) {
	        this.showUnbindConfirmationPopup = true;
	      }
	    },
	    cancelUnbind() {
	      this.showUnbindConfirmationPopup = false;
	    },
	    async unbind() {
	      this.unbindLoader = true;
	      try {
	        switch (this.communication.type) {
	          case humanresources_companyStructure_utils.ChatTypes.chat:
	            await DepartmentAPI.saveChats(this.nodeId, [], [this.communication.id]);
	            break;
	          case humanresources_companyStructure_utils.ChatTypes.channel:
	            await DepartmentAPI.saveChannel(this.nodeId, [], [this.communication.id]);
	            break;
	          case humanresources_companyStructure_utils.ChatTypes.collab:
	            await DepartmentAPI.saveCollab(this.nodeId, [], [this.communication.id]);
	            break;
	          default:
	            break;
	        }
	      } catch (error) {
	        if (error.code !== 'STRUCTURE_ACCESS_DENIED') {
	          ui_notification.UI.Notification.Center.notify({
	            content: this.loc(this.dictionary.error),
	            autoHideDelay: 2000
	          });
	        }
	        return;
	      } finally {
	        this.unbindLoader = false;
	        this.showUnbindConfirmationPopup = false;
	      }
	      DepartmentContentActions.unbindChatFromNode(this.nodeId, this.communication.id, this.communication.type);
	      const isOwn = !this.communication.originalNodeId || this.communication.originalNodeId === this.nodeId;
	      if (isOwn) {
	        const store = humanresources_companyStructure_chartStore.useChartStore();
	        store.updateChatsInChildrenNodes(this.nodeId);
	      }
	      ui_notification.UI.Notification.Center.notify({
	        content: this.loc(this.dictionary.success),
	        autoHideDelay: 2000
	      });
	    }
	  },
	  template: `
		<button
			v-if="menu.items.length"
			class="ui-icon-set --more hr-department-detail-content__tab-list_item-action-btn --chat-item-action-btn ui-icon-set"
			:class="{ '--focused': isMenuVisible }"
			@click.stop="toggleMenu()"
			ref="actionCommunicationButton"
			:data-id="buttonDataId"
		/>
		<RouteActionMenu
			v-if="isMenuVisible"
			:id="'tree-node-department-menu-chat_' + this.nodeId + '_' + communication.id"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.actionCommunicationButton"
			@action="onActionMenuItemClick"
			@close="isMenuVisible = false"
		/>
		<ConfirmationPopup
			ref="unbindConfirmationPopup"
			v-if="showUnbindConfirmationPopup"
			:showActionButtonLoader="unbindLoader"
			:title="loc(dictionary.title)"
			:confirmBtnText="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CONFIRM_BUTTON')"
			confirmButtonClass="ui-btn-danger"
			@action="unbind"
			@close="cancelUnbind"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div
						v-html="loc(dictionary.description)"
					/>
				</div>
			</template>
		</ConfirmationPopup>
	`
	};

	const ItemDictionary = Object.freeze({
	  [humanresources_companyStructure_utils.ChatTypes.chat]: {
	    parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_CHAT_HINT',
	    subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_OF_TEAM_MSGVER_2',
	    parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHAT_OF_DEPARTMENT_HINT',
	    parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHAT_OF_TEAM_HINT',
	    subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_OF_DEPARTMENT_MSGVER_2',
	    getAvatar: avatarOptions => new ui_avatar.AvatarRound(avatarOptions),
	    dataTestIdPrefix: 'hr-department-content_chats-tab__list_chat-item-'
	  },
	  [humanresources_companyStructure_utils.ChatTypes.channel]: {
	    parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_CHANNEL_HINT',
	    subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_OF_TEAM_MSGVER_2',
	    parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHANNEL_OF_DEPARTMENT_HINT',
	    parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHANNEL_OF_TEAM_HINT',
	    subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_OF_DEPARTMENT_MSGVER_2',
	    getAvatar: avatarOptions => new ui_avatar.AvatarSquare(avatarOptions),
	    dataTestIdPrefix: 'hr-department-content_chats-tab__list_chat-item-'
	  },
	  [humanresources_companyStructure_utils.ChatTypes.collab]: {
	    parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_COLLAB_HINT',
	    subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_OF_TEAM_MSGVER_2',
	    parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_COLLAB_OF_DEPARTMENT_HINT',
	    parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_COLLAB_OF_TEAM_HINT',
	    subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_OF_DEPARTMENT_MSGVER_2',
	    getAvatar: avatarOptions => new ui_avatar.AvatarHexagonGuest(avatarOptions),
	    dataTestIdPrefix: 'hr-department-content_chats-tab__list_collab-item-'
	  }
	});

	// @vue/component
	const CommunicationListItem = {
	  name: 'communicationListItem',
	  components: {
	    ChatListItemActionButton: CommunicationListItemActionButton,
	    ResponsiveHint: humanresources_companyStructure_structureComponents.ResponsiveHint
	  },
	  directives: {
	    hint: humanresources_companyStructure_structureComponents.Hint
	  },
	  props: {
	    /** @type CommunicationDetailed */
	    communication: {
	      type: Object,
	      required: true
	    },
	    nodeId: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      avatar: null
	    };
	  },
	  computed: {
	    dictionary() {
	      return ItemDictionary[this.communication.type] || ItemDictionary[humanresources_companyStructure_utils.ChatTypes.channel];
	    },
	    originalNodeName() {
	      var _this$departments$get;
	      return (_this$departments$get = this.departments.get(this.communication.originalNodeId)) == null ? void 0 : _this$departments$get.name;
	    },
	    hiddenNodeName() {
	      var _this$structureMap$ge;
	      return ((_this$structureMap$ge = this.structureMap.get(this.communication.originalNodeId)) == null ? void 0 : _this$structureMap$ge.entityType) === humanresources_companyStructure_utils.EntityTypes.team ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_HIDDEN_TEAM') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_HIDDEN_DEPARTMENT');
	    },
	    /**
	     * Prepare subtitle with #NAME# placeholder where #NAME# can be a vue component
	     */
	    preparedIndirectSubtitle() {
	      var _this$structureMap$ge2;
	      let phrase = '';
	      if (((_this$structureMap$ge2 = this.structureMap.get(this.communication.originalNodeId)) == null ? void 0 : _this$structureMap$ge2.entityType) === humanresources_companyStructure_utils.EntityTypes.team) {
	        phrase = this.loc(this.dictionary.subtitleForTeam);
	      } else {
	        phrase = this.loc(this.dictionary.subtitleForDepartment);
	      }
	      const parts = phrase.split('#NAME#');
	      return {
	        beforeText: parts[0] || null,
	        afterText: parts[1] || null
	      };
	    },
	    lockHint() {
	      var _this$structureMap$ge3, _this$structureMap$ge4;
	      // parent node is a team so child node must be a team too
	      if (((_this$structureMap$ge3 = this.structureMap.get(this.communication.originalNodeId)) == null ? void 0 : _this$structureMap$ge3.entityType) === humanresources_companyStructure_utils.EntityTypes.team) {
	        return this.loc(this.dictionary.parentTeamHint);
	      }

	      // parent node is a department so we check if child node is a department too
	      if (((_this$structureMap$ge4 = this.structureMap.get(this.nodeId)) == null ? void 0 : _this$structureMap$ge4.entityType) === humanresources_companyStructure_utils.EntityTypes.department) {
	        return this.loc(this.dictionary.parentDepartmentOfDepartmentHint);
	      }

	      // parent node is a department but child node is a team
	      return this.loc(this.dictionary.parentDepartmentOfTeamHint);
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['structureMap', 'departments'])
	  },
	  watch: {
	    communication() {
	      this.prepareAvatar();
	    }
	  },
	  created() {
	    this.prepareAvatar();
	  },
	  methods: {
	    prepareAvatar() {
	      if (this.communication.avatar) {
	        this.communication.color = humanresources_companyStructure_utils.getColorCode('whiteBase');
	      }
	      const avatarOptions = {
	        size: 32,
	        userName: this.communication.title,
	        baseColor: this.isExtranet() && !this.communication.avatar ? humanresources_companyStructure_utils.getColorCode('extranetColor') : this.communication.color,
	        events: {
	          click: () => {
	            this.onChatItemClick();
	          }
	        }
	      };
	      if (this.communication.avatar) {
	        avatarOptions.userpicPath = this.communication.avatar;
	      }
	      this.avatar = this.dictionary.getAvatar(avatarOptions);
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onChatItemClick() {
	      if (this.communication.hasAccess) {
	        im_public_iframe.Messenger.openChat(this.communication.dialogId);
	      }
	    },
	    isExtranet() {
	      return this.communication.isExtranet;
	    },
	    locateToOriginalDepartment() {
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, {
	        nodeId: this.communication.originalNodeId
	      });
	    }
	  },
	  template: `
		<div
			:key="communication.id"
			class="hr-department-detail-content__tab-list_item-wrapper --chat"
			:class="{ '--isExtranet': isExtranet() }"
			:data-test-id="dictionary.dataTestIdPrefix + communication.id"
		>
			<div
				class="hr-department-detail-content__tab-list_item-avatar-container"
				:class="{ '--not-clickable': !communication.hasAccess }"
				v-html="this.avatar.getContainer().outerHTML"
				@click="onChatItemClick"
			/>
			<div class="hr-department-detail-content__tab-list_item-text-container">
				<div class="hr-department-detail-content__tab-list_item-title-container --chat-item">
					<span
						class="hr-department-detail-content__tab-list_item-title"
						:class="{ '--not-clickable': !communication.hasAccess }"
						:data-test-id="'hr-department-content_chats-tab__list_chat-item-' + communication.id + '-title'"
						@click="onChatItemClick"
					>{{ communication.title }}</span>
					<ResponsiveHint
						v-if="communication.originalNodeId"
						:content="lockHint"
						:extraClasses="{ 'hr-department-detail-content__tab-list_hint-container': true }"
					>
						<span class="ui-icon-set --lock hr-department-detail-content__tab-list_lock-icon"></span>
					</ResponsiveHint>
				</div>
				<div v-if="communication.originalNodeId && originalNodeName"
					 class="hr-department-detail-content__tab-list_item-subtitle --chat-item">
					<span class="hr-department-detail-content__tab-list_item-subtitle_before">
						{{ preparedIndirectSubtitle.beforeText }}
					</span>
					<ResponsiveHint
						:content="originalNodeName"
						defaultClass="hr-department-detail-content__tab-list_orig-node-name"
						:checkScrollWidth="true"
						:width="null"
						@click="locateToOriginalDepartment"
					>
						{{ originalNodeName }}
					</ResponsiveHint>
					<span class="hr-department-detail-content__tab-list_item-subtitle_after">
						{{ preparedIndirectSubtitle.afterText }}
					</span>
				</div>
				<div v-else-if="communication.originalNodeId"
					 class="hr-department-detail-content__tab-list_item-subtitle --chat-item">
					<span class="hr-department-detail-content__tab-list_item-subtitle_before">
						{{ preparedIndirectSubtitle.beforeText }}
					</span>
					<span class="hr-department-detail-content__tab-list_orig-node-hidden-name">
						{{ hiddenNodeName }}
					</span>
					<span class="hr-department-detail-content__tab-list_item-subtitle_after">
						{{ preparedIndirectSubtitle.afterText }}
					</span>
				</div>
				<div v-else class="hr-department-detail-content__tab-list_item-subtitle">
					{{ communication.subtitle }}
				</div>
			</div>
			<ChatListItemActionButton
				:communication="communication"
				:nodeId="nodeId"
			/>
		</div>
	`
	};

	const ListDictionary = Object.freeze({
	  chat: {
	    tabListId: 'hr-department-detail-content_chats-tab__chat-list',
	    footerDataTestId: 'hr-department-content_chats-tab__list_chat-hidden-text',
	    listTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LIST_TITLE',
	    emptyItemTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHAT_LIST_ITEM_TEXT_MSGVER_1',
	    noTeamText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHAT_LIST_ITEM_NO_TEAM_TEXT_MSGVER_1',
	    noDepartmentText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHAT_LIST_ITEM_NO_DEPARTMENT_TEXT_MSGVER_1',
	    moreHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHAT_LIST_ITEM_MORE_HIDDEN_TEXT',
	    emptyHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHAT_LIST_ITEM_EMPTY_HIDDEN_TEXT',
	    menuItems: [ChatsMenuLinkChat]
	  },
	  channel: {
	    tabListId: 'hr-department-detail-content_chats-tab__channel-list',
	    footerDataTestId: 'hr-department-content_chats-tab__list_channel-hidden-text',
	    listTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LIST_TITLE',
	    emptyItemTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHANNEL_LIST_ITEM_TEXT_MSGVER_1',
	    noTeamText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHANNEL_LIST_ITEM_NO_TEAM_TEXT_MSGVER_1',
	    noDepartmentText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHANNEL_LIST_ITEM_NO_DEPARTMENT_TEXT_MSGVER_1',
	    moreHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHANNEL_LIST_ITEM_MORE_HIDDEN_TEXT',
	    emptyHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_CHANNEL_LIST_ITEM_EMPTY_HIDDEN_TEXT',
	    menuItems: [ChatsMenuLinkChannel]
	  },
	  collab: {
	    tabListId: 'hr-department-detail-content_chats-tab__collab-list',
	    footerDataTestId: 'hr-department-content_chats-tab__list_collab-hidden-text',
	    listTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LIST_TITLE',
	    emptyItemTitle: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_COLLAB_LIST_ITEM_TEXT',
	    noTeamText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_COLLAB_LIST_ITEM_NO_TEAM_TEXT',
	    noDepartmentText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_COLLAB_LIST_ITEM_NO_DEPARTMENT_TEXT',
	    moreHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_COLLAB_LIST_ITEM_MORE_HIDDEN_TEXT',
	    emptyHiddenText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_COLLAB_LIST_ITEM_EMPTY_HIDDEN_TEXT',
	    menuItems: [ChatsMenuLinkCollab]
	  }
	});

	// @vue/component
	const CommunicationList = {
	  name: 'communicationList',
	  components: {
	    TabList,
	    CommunicationListItem
	  },
	  props: {
	    /** @type CommunicationDetailed[] */
	    communications: {
	      type: Array,
	      required: true
	    },
	    communicationsNoAccess: {
	      type: Number,
	      required: true
	    },
	    /** @type CommunicationDetailed[] */
	    filteredCommunications: {
	      type: Array,
	      required: true
	    },
	    isTeamEntity: {
	      type: Boolean,
	      required: true
	    },
	    canEdit: {
	      type: Boolean,
	      required: true
	    },
	    searchQuery: {
	      type: String,
	      required: true
	    },
	    focusedNode: {
	      type: [String, Number],
	      required: true
	    },
	    communicationType: {
	      type: String,
	      required: true,
	      validator: value => Object.values(humanresources_companyStructure_structureComponents.CommunicationsTypeDict).includes(value)
	    },
	    dataTestId: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      dictionary: {}
	    };
	  },
	  computed: {
	    communicationMenuItems() {
	      return this.canEdit ? this.dictionary.menuItems : [];
	    },
	    communicationsNoAccessText() {
	      const phrase = this.communications.length > 0 ? this.dictionary.moreHiddenText : this.dictionary.emptyHiddenText;
	      return this.locPlural(phrase, this.communicationsNoAccess);
	    },
	    emptyCommunicationTitle() {
	      if (this.canEdit) {
	        return this.loc(this.dictionary.emptyItemTitle);
	      }
	      return this.isTeamEntity ? this.loc(this.dictionary.noTeamText) : this.loc(this.dictionary.noDepartmentText);
	    },
	    hideEmptyCommunicationItem() {
	      return this.searchQuery.length > 0 || this.communicationsNoAccess > 0;
	    }
	  },
	  created() {
	    this.dictionary = ListDictionary[this.communicationType] || {};
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    locPlural(phraseCode, count) {
	      return main_core.Loc.getMessagePlural(phraseCode, count, {
	        '#COUNT#': count
	      });
	    },
	    onActionMenuItemClick(...args) {
	      this.$emit('tabListAction', ...args);
	    }
	  },
	  template: `
		<TabList
			:id='dictionary.tabListId'
			:title="loc(dictionary.listTitle)"
			:count="communications.length + communicationsNoAccess"
			:menuItems="communicationMenuItems"
			:listItems="filteredCommunications"
			:emptyItemTitle="emptyCommunicationTitle"
			emptyItemImageClass="hr-department-detail-content__chat-empty-tab-list-item_tab-icon"
			:hideEmptyItem="hideEmptyCommunicationItem"
			:withAddPermission="canEdit"
			@tabListAction="onActionMenuItemClick"
			:dataTestIds="dataTestId"
		>
			<template v-slot="{ item }">
				<CommunicationListItem :communication="item" :nodeId="focusedNode"/>
			</template>
			<template v-if="communicationsNoAccess > 0" v-slot:footer>
				<div
					class="hr-department-detail-content__tab-list_communications-hidden"
					:data-test-id="dictionary.footerDataTestId"
				>
					{{ communicationsNoAccessText }}
				</div>
			</template>
		</TabList>
	`
	};

	const MenuOption = Object.freeze({
	  withChildren: 'withChildren',
	  withoutChildren: 'withoutChildren'
	});

	// @vue/component
	const ChildrenModeSelector = {
	  name: 'childrenModeSelector',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  props: {
	    isTeamEntity: {
	      type: Boolean,
	      required: true
	    },
	    dataTestId: {
	      type: String,
	      required: true
	    },
	    hasPermission: {
	      type: Boolean,
	      required: true
	    },
	    text: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['saveChildrenMode'],
	  data() {
	    return {
	      menuVisible: false,
	      withChildren: false
	    };
	  },
	  computed: {
	    getControlButtonText() {
	      return this.getValueText(this.withChildren);
	    }
	  },
	  created() {
	    this.menuItems = this.getMenuItems();
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    showMenu() {
	      if (this.hasPermission) {
	        this.menuVisible = true;
	      }
	    },
	    onActionMenuItemClick(actionId) {
	      if (this.hasPermission) {
	        this.withChildren = actionId === MenuOption.withChildren;
	        this.$emit('saveChildrenMode', this.withChildren);
	      }
	    },
	    getMenuItems() {
	      return [{
	        id: MenuOption.withoutChildren,
	        title: this.getValueText(false),
	        itemClass: 'hr-department-detail-content__children-mode-selector_menu-item',
	        dataTestId: 'hr-chat-children-mode-selector_menu-item-without'
	      }, {
	        id: MenuOption.withChildren,
	        title: this.getValueText(true),
	        itemClass: 'hr-department-detail-content__children-mode-selector_menu-item',
	        dataTestId: 'hr-chat-children-mode-selector_menu-item-with'
	      }];
	    },
	    getValueText(value) {
	      if (this.isTeamEntity) {
	        return value ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_WITH_SUBTEAMS_LABEL') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_ONLY_TEAM_LABEL');
	      }
	      return value ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_WITH_SUBDEPARTMENTS_LABEL') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_ONLY_DEPARTMENT_LABEL');
	    }
	  },
	  template: `
		<div class="hr-department-detail-content__change-save-mode-control-container">
			<span>{{ text }}</span>
			<a
				class="hr-department-detail-content__change-save-mode-control-button"
				:class="{ '--focused': menuVisible, '--disabled': !hasPermission }"
				:dataTestId="dataTestId"
				ref='saveChildrenModeButton'
				@click="showMenu"
			>
				{{ getControlButtonText }}
			</a>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'hr-department-detail-children-mode-selector-menu'"
			:items="menuItems"
			:delimiter="false"
			:width="302"
			:bindElement="$refs.saveChildrenModeButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	const DialogDictionary = Object.freeze({
	  chat: {
	    dialogId: 'hr-department-detail-content-chats-tab-chat-link-dialog',
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_TITLE',
	    description: {
	      team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_TEAM_DESC',
	      default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_DESC'
	    },
	    childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_CHATS_TEXT',
	    dataTestIds: ChatLinkDialogDataTestIds,
	    getDialogEntity: () => humanresources_companyStructure_structureComponents.getChatDialogEntity(),
	    getDialogIdFromId: item => `chat${item.id}`,
	    getIdFromDialogId: item => {
	      var _item$id;
	      return Number((_item$id = item.id) == null ? void 0 : _item$id.replace('chat', ''));
	    }
	  },
	  channel: {
	    dialogId: 'hr-department-detail-content-chats-tab-channel-link-dialog',
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_TITLE',
	    description: {
	      team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_TEAM_DESC',
	      default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_DESC'
	    },
	    childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_CHANNELS_TEXT',
	    dataTestIds: ChannelLinkDialogDataTestIds,
	    getDialogEntity: () => humanresources_companyStructure_structureComponents.getChannelDialogEntity(),
	    getDialogIdFromId: item => `chat${item.id}`,
	    getIdFromDialogId: item => {
	      var _item$id2;
	      return Number((_item$id2 = item.id) == null ? void 0 : _item$id2.replace('chat', ''));
	    }
	  },
	  collab: {
	    dialogId: 'hr-department-detail-content-chats-tab-channel-link-dialog',
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_TITLE',
	    description: {
	      team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_TEAM_DESC',
	      default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_DESC'
	    },
	    childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_COLLABS_TEXT',
	    dataTestIds: CollabLinkDialogDataTestIds,
	    getDialogEntity: () => humanresources_companyStructure_structureComponents.getCollabDialogEntity(),
	    getDialogIdFromId: item => Number(item.id),
	    getIdFromDialogId: item => Number(item.id)
	  }
	});

	// @vue/component
	const LinkDialog = {
	  name: 'LinkDialog',
	  components: {
	    ManagementDialog: humanresources_companyStructure_structureComponents.ManagementDialog,
	    ChildrenModeSelector
	  },
	  props: {
	    /** @type CommunicationDetailed[] */
	    communications: {
	      type: Array,
	      required: true
	    },
	    communicationType: {
	      type: String,
	      required: true,
	      validator: value => Object.values(humanresources_companyStructure_structureComponents.CommunicationsTypeDict).includes(value)
	    },
	    focusedNode: {
	      type: [String, Number],
	      required: true
	    },
	    isTeamEntity: {
	      type: Boolean,
	      required: true
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    isVisible: {
	      type: Boolean,
	      required: true
	    },
	    canAddWithChildren: {
	      type: Boolean,
	      required: true
	    }
	  },
	  emits: ['close', 'reloadList'],
	  data() {
	    return {
	      isLinkActive: false,
	      addWithChildren: false,
	      dictionary: {}
	    };
	  },
	  computed: {
	    dialogEntities() {
	      const entity = this.dictionary.getDialogEntity();
	      entity.options.excludeIds = this.communications.map(item => item.id);
	      return [entity];
	    },
	    dialogRecentTabOptions() {
	      return humanresources_companyStructure_structureComponents.getCommunicationsRecentTabOptions(this.entityType, this.communicationType);
	    },
	    linkedIds() {
	      return this.communications.map(item => this.dictionary.getDialogIdFromId(item));
	    },
	    addDescription() {
	      return this.isTeamEntity ? this.loc(this.dictionary.description.team) : this.loc(this.dictionary.description.default);
	    }
	  },
	  created() {
	    this.dictionary = DialogDictionary[this.communicationType] || {};
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    async linkEntities(items) {
	      this.isLinkActive = true;
	      const mappedIds = items.map(item => this.dictionary.getIdFromDialogId(item));
	      try {
	        const nodeId = this.focusedNode;
	        const addWithChildren = Number(this.addWithChildren);
	        switch (this.communicationType) {
	          case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.chat:
	            await DepartmentAPI.saveChats(nodeId, mappedIds, [], addWithChildren);
	            break;
	          case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.channel:
	            await DepartmentAPI.saveChannel(nodeId, mappedIds, [], addWithChildren);
	            break;
	          case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.collab:
	            await DepartmentAPI.saveCollab(nodeId, mappedIds, [], addWithChildren);
	            break;
	          default:
	            break;
	        }
	        this.$emit('reloadList');
	        if (this.addWithChildren) {
	          const store = humanresources_companyStructure_chartStore.useChartStore();
	          store.updateChatsInChildrenNodes(nodeId);
	        }
	      } catch {/* empty */}
	      this.addWithChildren = false;
	      this.isLinkActive = false;
	      this.$emit('close');
	    }
	  },
	  template: `
		<ManagementDialog
			v-if="isVisible"
			:id="dictionary.dialogId"
			:entities="dialogEntities"
			:recentTabOptions="dialogRecentTabOptions"
			:hiddenItemsIds="linkedIds"
			:title="loc(dictionary.title)"
			:description="addDescription"
			:isActive="isLinkActive"
			@managementDialogAction="linkEntities"
			@close="$emit('close')"
			:dataTestIds="dictionary.dataTestIds"
		>
			<template v-slot:extra-subtitle>
				<ChildrenModeSelector
					:isTeamEntity="isTeamEntity"
					:dataTestId="dictionary.dataTestIds.addWithChildrenDataTestId"
					:hasPermission="canAddWithChildren"
					:text="loc(dictionary.childrenModeText)"
					@saveChildrenMode="addWithChildren = $event"
				/>
			</template>
		</ManagementDialog>
	`
	};

	const ChatsTab = {
	  name: 'chatsTab',
	  components: {
	    SearchInput,
	    EmptyState,
	    EmptyTabAddButtons,
	    CommunicationList,
	    LinkDialog
	  },
	  data() {
	    return {
	      chatLinkDialogVisible: false,
	      channelLinkDialogVisible: false,
	      collabLinkDialogVisible: false,
	      isLoading: false,
	      searchQuery: '',
	      permissionChecker: null
	    };
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    locPlural(phraseCode, count) {
	      return main_core.Loc.getMessagePlural(phraseCode, count, {
	        '#COUNT#': count
	      });
	    },
	    searchChatOrChannel(searchQuery) {
	      this.searchQuery = searchQuery;
	    },
	    async loadChatAction(force) {
	      var _loadedChatsAndChanne, _loadedChatsAndChanne2, _loadedChatsAndChanne3, _loadedChatsAndChanne4, _loadedChatsAndChanne5, _loadedChatsAndChanne6;
	      if (this.isLoading) {
	        return null;
	      }
	      const nodeId = this.focusedNode;
	      const department = this.departments.get(nodeId);
	      if (!department) {
	        return null;
	      }
	      if (!force && main_core.Type.isArray(department.chatsDetailed) && main_core.Type.isArray(department.channelsDetailed) && main_core.Type.isArray(department.collabsDetailed)) {
	        return {
	          chats: department.chatsDetailed,
	          channels: department.channelsDetailed,
	          collabs: department.collabsDetailed,
	          chatsNoAccess: department.chatsNoAccess,
	          channelsNoAccess: department.channelsNoAccess,
	          collabsNoAccess: department.collabsNoAccess
	        };
	      }
	      this.isLoading = true;
	      this.$emit('showDetailLoader');
	      const loadedChatsAndChannels = await DepartmentAPI.getChatsAndChannels(nodeId);
	      DepartmentContentActions.setChatsAndChannels(nodeId, (_loadedChatsAndChanne = loadedChatsAndChannels.chats) != null ? _loadedChatsAndChanne : [], (_loadedChatsAndChanne2 = loadedChatsAndChannels.channels) != null ? _loadedChatsAndChanne2 : [], (_loadedChatsAndChanne3 = loadedChatsAndChannels.collabs) != null ? _loadedChatsAndChanne3 : [], (_loadedChatsAndChanne4 = loadedChatsAndChannels.chatsNoAccess) != null ? _loadedChatsAndChanne4 : 0, (_loadedChatsAndChanne5 = loadedChatsAndChannels.channelsNoAccess) != null ? _loadedChatsAndChanne5 : 0, (_loadedChatsAndChanne6 = loadedChatsAndChannels.collabsNoAccess) != null ? _loadedChatsAndChanne6 : 0);
	      this.$emit('hideDetailLoader');
	      this.isLoading = false;
	      return loadedChatsAndChannels;
	    },
	    onActionMenuItemClick(actionId) {
	      switch (actionId) {
	        case ChatsMenuOption.linkChat:
	          this.chatLinkDialogVisible = true;
	          break;
	        case ChatsMenuOption.linkChannel:
	          this.channelLinkDialogVisible = true;
	          break;
	        case ChatsMenuOption.linkCollab:
	          this.collabLinkDialogVisible = true;
	          break;
	        default:
	          break;
	      }
	    },
	    getAddEmptyStateList() {
	      let stateArray = [];
	      if (this.isCollabsAvailable && this.isTeamEntity) {
	        stateArray = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_W_COLLABS_ITEM_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_W_COLLABS_ITEM_3')];
	      } else if (this.isCollabsAvailable) {
	        stateArray = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_3')];
	      } else if (this.isTeamEntity) {
	        stateArray = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_ITEM_1_MSGVER_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_ITEM_3')];
	      } else {
	        stateArray = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_1_MSGVER_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_3')];
	      }
	      return stateArray.map(item => ({
	        text: item
	      }));
	    }
	  },
	  computed: {
	    isCollabsAvailable() {
	      return humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance().isCollabsAvailable;
	    },
	    communicationTypes() {
	      return humanresources_companyStructure_structureComponents.CommunicationsTypeDict;
	    },
	    chats() {
	      var _this$departments$get, _this$departments$get2;
	      return (_this$departments$get = (_this$departments$get2 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get2.chatsDetailed) != null ? _this$departments$get : [];
	    },
	    channels() {
	      var _this$departments$get3, _this$departments$get4;
	      return (_this$departments$get3 = (_this$departments$get4 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get4.channelsDetailed) != null ? _this$departments$get3 : [];
	    },
	    collabs() {
	      var _this$departments$get5, _this$departments$get6;
	      return (_this$departments$get5 = (_this$departments$get6 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get6.collabsDetailed) != null ? _this$departments$get5 : [];
	    },
	    chatsNoAccess() {
	      var _this$departments$get7, _this$departments$get8;
	      return (_this$departments$get7 = (_this$departments$get8 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get8.chatsNoAccess) != null ? _this$departments$get7 : 0;
	    },
	    channelsNoAccess() {
	      var _this$departments$get9, _this$departments$get10;
	      return (_this$departments$get9 = (_this$departments$get10 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get10.channelsNoAccess) != null ? _this$departments$get9 : 0;
	    },
	    collabsNoAccess() {
	      var _this$departments$get11, _this$departments$get12;
	      return (_this$departments$get11 = (_this$departments$get12 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get12.collabsNoAccess) != null ? _this$departments$get11 : 0;
	    },
	    filteredChats() {
	      return this.chats.filter(chat => chat.title.toLowerCase().includes(this.searchQuery.toLowerCase()));
	    },
	    filteredChannels() {
	      return this.channels.filter(channel => channel.title.toLowerCase().includes(this.searchQuery.toLowerCase()));
	    },
	    filteredCollabs() {
	      return this.collabs.filter(collab => collab.title.toLowerCase().includes(this.searchQuery.toLowerCase()));
	    },
	    showAddEmptyState() {
	      return this.chats.length === 0 && this.channels.length === 0 && this.collabs.length === 0 && this.chatsNoAccess === 0 && this.channelsNoAccess === 0 && this.collabsNoAccess === 0;
	    },
	    showSearchEmptyState() {
	      return (this.chats.length > 0 || this.channels.length > 0 || this.collabs.length > 0) && this.filteredChats.length === 0 && this.filteredChannels.length === 0 && this.filteredCollabs.length === 0;
	    },
	    areChatsLoaded() {
	      const department = this.departments.get(this.focusedNode);
	      return Boolean(main_core.Type.isArray(department.chatsDetailed) && main_core.Type.isArray(department.channelsDetailed) && main_core.Type.isArray(department.collabsDetailed));
	    },
	    entityType() {
	      var _this$departments$get13;
	      return (_this$departments$get13 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get13.entityType;
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    getAddEmptyStateTitle() {
	      if (this.isCollabsAvailable) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_TITLE_W_COLLABS') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TITLE_W_COLLABS');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TITLE');
	    },
	    canEditChat() {
	      return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, this.focusedNode) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, this.focusedNode);
	    },
	    canEditChannel() {
	      return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, this.focusedNode) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, this.focusedNode);
	    },
	    canEditCollab() {
	      return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, this.focusedNode) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, this.focusedNode);
	    },
	    canAddChatWithChildren() {
	      if (this.isTeamEntity) {
	        return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, this.focusedNode, {
	          TEAM: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        }) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, this.focusedNode, {
	          DEPARTMENT: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        });
	      }
	      return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, this.focusedNode, humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub);
	    },
	    canAddChannelWithChildren() {
	      if (this.isTeamEntity) {
	        return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, this.focusedNode, {
	          TEAM: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        }) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, this.focusedNode, {
	          DEPARTMENT: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        });
	      }
	      return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, this.focusedNode, humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub);
	    },
	    canAddCollabWithChildren() {
	      if (this.isTeamEntity) {
	        return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, this.focusedNode, {
	          TEAM: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        }) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, this.focusedNode, {
	          DEPARTMENT: humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub
	        });
	      }
	      return this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, this.focusedNode, humanresources_companyStructure_permissionChecker.PermissionLevels.selfAndSub);
	    },
	    hideEmptyChatItem() {
	      return this.searchQuery.length > 0 || this.chatsNoAccess > 0;
	    },
	    hideEmptyChannelItem() {
	      return this.searchQuery.length > 0 || this.channelsNoAccess > 0;
	    },
	    getChatListDataTestIds() {
	      return ChatListDataTestIds;
	    },
	    getChannelListDataTestIds() {
	      return ChannelListDataTestIds;
	    },
	    getCollabListDataTestIds() {
	      return CollabListDataTestIds;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments'])
	  },
	  watch: {
	    areChatsLoaded(isChatsLoaded) {
	      if (isChatsLoaded === false) {
	        this.loadChatAction();
	      }
	    }
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.loadChatAction();
	  },
	  template: `
		<div class="hr-department-detail-content__tab-container --chat">
			<template v-if="!showAddEmptyState">
				<SearchInput
					:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_SEARCH_INPUT_PLACEHOLDER')"
					:value="searchQuery"
					@inputChange="searchChatOrChannel"
					dataTestId="hr-department-detail-content_chats-tab__chats-and-channels-search-input"
				/>
				<div
					v-if="!showSearchEmptyState"
					class="hr-department-detail-content__lists-container"
				>
					<CommunicationList
						v-if="isCollabsAvailable"
						:communications="collabs"
						:filteredCommunications="filteredCollabs"
						:communicationsNoAccess="collabsNoAccess"
						:canEdit="canEditCollab"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.collab"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getCollabListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
					<CommunicationList
						:communications="channels"
						:filteredCommunications="filteredChannels"
						:communicationsNoAccess="channelsNoAccess"
						:canEdit="canEditChannel"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.channel"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getChannelListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
					<CommunicationList
						:communications="chats"
						:filteredCommunications="filteredChats"
						:communicationsNoAccess="chatsNoAccess"
						:canEdit="canEditChat"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.chat"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getChatListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
				</div>
				<EmptyState 
					v-else
					imageClass="hr-department-detail-content__chat-empty-tab-search_tab-icon"
					:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE')"
					:description ="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE')"
				/>
			</template>
			<EmptyState 
				v-else
				imageClass="hr-department-detail-content__chat-empty-tab-add_tab-icon"
				:title="getAddEmptyStateTitle"
				:list="getAddEmptyStateList()"
			>
				<template v-slot:content>
					<EmptyTabAddButtons
						:isTeamEntity="isTeamEntity"
						:canEditChat="canEditChat"
						:canEditChannel="canEditChannel"
						:canEditCollab="canEditCollab"
						@emptyStateAddAction="onActionMenuItemClick"
					/>
				</template>
			</EmptyState>
			<LinkDialog
				v-if="isCollabsAvailable"
				:communications="collabs"
				:communicationType="communicationTypes.collab"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="collabLinkDialogVisible"
				:canAddWithChildren="canAddCollabWithChildren"
				@close="collabLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
			<LinkDialog
				:communications="channels"
				:communicationType="communicationTypes.channel"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="channelLinkDialogVisible"
				:canAddWithChildren="canAddChannelWithChildren"
				@close="channelLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
			<LinkDialog
				:communications="chats"
				:communicationType="communicationTypes.chat"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="chatLinkDialogVisible"
				:canAddWithChildren="canAddChatWithChildren"
				@close="chatLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
		</div>
	`
	};

	// @vue/component
	const DepartmentContent = {
	  name: 'departmentContent',
	  components: {
	    UsersTab,
	    ChatsTab
	  },
	  props: {
	    isCollapsed: Boolean
	  },
	  emits: ['showDetailLoader', 'hideDetailLoader', 'editEmployee'],
	  data() {
	    return {
	      activeTab: 'usersTab',
	      tabs: [{
	        name: 'usersTab',
	        component: 'UsersTab',
	        id: 'users-tab'
	      }, {
	        name: 'chatsTab',
	        component: 'ChatsTab',
	        id: 'chats-tab'
	      }],
	      isDescriptionOverflowed: false,
	      isDescriptionExpanded: false
	    };
	  },
	  computed: {
	    isCollabsAvailable() {
	      return humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance().isCollabsAvailable;
	    },
	    activeTabComponent() {
	      const activeTab = this.tabs.find(tab => tab.name === this.activeTab);
	      return activeTab ? activeTab.component : null;
	    },
	    usersCount() {
	      var _this$departments$get, _this$departments$get2;
	      return (_this$departments$get = (_this$departments$get2 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get2.userCount) != null ? _this$departments$get : 0;
	    },
	    communicationsCount() {
	      var _this$departments$get3, _this$departments$get4;
	      return (_this$departments$get3 = (_this$departments$get4 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get4.communicationsCount) != null ? _this$departments$get3 : null;
	    },
	    tabArray() {
	      return this.tabs.map(tab => {
	        if (tab.name === 'usersTab') {
	          return {
	            ...tab,
	            count: this.usersCount
	          };
	        }
	        if (tab.name === 'chatsTab') {
	          return {
	            ...tab,
	            count: this.communicationsCount
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
	    },
	    isTeamEntity() {
	      var _this$departments$get5;
	      return ((_this$departments$get5 = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get5.entityType) === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments'])
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
	    },
	    isCollapsed() {
	      this.$nextTick(() => {
	        this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	      });
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    selectTab(tabName) {
	      if (this.isTabDisabled(tabName)) {
	        return;
	      }
	      this.activeTab = tabName;
	    },
	    getTabLabel(name) {
	      if (name === 'usersTab') {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_TEAM_USERS_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TITLE');
	      }
	      if (name === 'chatsTab') {
	        return this.isCollabsAvailable ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE_W_COLLABS') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_TITLE');
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
	        return descriptionContainer.scrollHeight > descriptionContainer.clientHeight;
	      }
	      return false;
	    },
	    hideDetailLoader() {
	      this.$emit('hideDetailLoader');
	      this.$nextTick(() => {
	        this.isDescriptionOverflowed = this.checkDescriptionOverflowed();
	      });
	    },
	    isTabDisabled(tabName) {
	      if (tabName === 'chatsTab' && this.tabArray.find(tab => tab.name === tabName).count === 0) {
	        const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	        const canEditChats = this.isTeamEntity ? permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, this.focusedNode) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, this.focusedNode) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, this.focusedNode) : permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, this.focusedNode) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, this.focusedNode) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, this.focusedNode);
	        if (!canEditChats) {
	          return true;
	        }
	      }
	      return false;
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
					:class="[{'--active-tab' : activeTab === tab.name, '--disabled-tab': isTabDisabled(tab.name)}]"
					@click="selectTab(tab.name)"
					:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_button' : null"
				>
					{{ this.getTabLabel(tab.name) }}
					<span
						class="hr-department-detail-content__tab-count"
						:data-id="tab.id ? 'hr-department-detail-content__' + tab.id + '_counter' : null"
					>{{ tab.count }}
					</span>
				</button>
			</div>
			<UsersTab
				v-show="activeTab === 'usersTab'"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
			<ChatsTab
				v-show="activeTab === 'chatsTab'"
				:is="activeTabComponent"
				@showDetailLoader="$emit('showDetailLoader')"
				@hideDetailLoader="hideDetailLoader"
			/>
		</div>
	`
	};

	exports.DepartmentContent = DepartmentContent;
	exports.DepartmentContentActions = DepartmentContentActions;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure,BX,BX.Humanresources.CompanyStructure,BX.UI,BX.UI,BX.Humanresources.CompanyStructure,BX.UI.IconSet,BX,BX.Event,BX.Humanresources.CompanyStructure,BX,BX.Messenger.v2.Lib,BX.Humanresources.CompanyStructure,BX.UI,BX,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Vue3.Pinia));
//# sourceMappingURL=department-content.bundle.js.map

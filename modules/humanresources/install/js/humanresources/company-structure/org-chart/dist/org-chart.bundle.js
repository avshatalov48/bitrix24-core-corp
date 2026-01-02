/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3,ui_confetti,ui_canvas,humanresources_companyStructure_orgChart,ui_entitySelector,ui_dialogs_messagebox,ui_notification,humanresources_companyStructure_userManagementDialog,ui_vue3_pinia,humanresources_companyStructure_departmentContent,humanresources_companyStructure_api,humanresources_companyStructure_structureComponents,ui_iconSet_main,ui_iconSet_crm,ui_buttons,ui_forms,ui_iconSet_api_vue,humanresources_companyStructure_chartStore,humanresources_companyStructure_chartWizard,ui_analytics,ui_designTokens,humanresources_companyStructure_utils,ui_iconSet_api_core,humanresources_companyStructure_permissionChecker,main_core_events,main_core) {
	'use strict';

	const events = Object.freeze({
	  HR_DEPARTMENT_CONNECT: 'hr-department-connect',
	  HR_DEPARTMENT_DISCONNECT: 'hr-department-disconnect',
	  HR_DEPARTMENT_ADAPT_SIBLINGS: 'hr-department-adapt-siblings',
	  HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT: 'hr-department-adapt-connector-height',
	  HR_DEPARTMENT_FOCUS: 'hr-department-focus',
	  HR_DEPARTMENT_CONTROL: 'hr-department-control',
	  HR_DEPARTMENT_MENU_CLOSE: 'hr-department-menu-close',
	  HR_ORG_CHART_CLOSE_BY_ESC: 'SidePanel.Slider:onCloseByEsc',
	  HR_ORG_CHART_CLOSE: 'SidePanel.Slider:onClose',
	  HR_FIRST_POPUP_SHOW: 'HR.company-structure:first-popup-showed',
	  HR_DRAG_ENTITY: 'hr-drag-entity',
	  HR_DROP_ENTITY: 'hr-drop-entity',
	  HR_ORG_CHART_TRANSFORM_CANVAS: 'hr-org-chart-transform-canvas',
	  HR_DEPARTMENT_SLIDER_ON_MESSAGE: 'SidePanel.Slider:onMessage',
	  HR_ORG_CHART_LOCATE_TO_DEPARTMENT: 'hr-org-chart-locate-to-department',
	  HR_ENTITY_TOGGLE_ELEMENTS: 'hr-entity-toggle-elements',
	  HR_ENTITY_SHOW_WIZARD: 'hr-entity-show-wizard',
	  HR_ENTITY_REMOVE: 'hr-entity-remove',
	  HR_PUBLIC_FOCUS_NODE: 'HumanResources.CompanyStructure:focusNode',
	  INTRANET_USER_MINIPROFILE_CLOSE: 'Intranet.User.MiniProfile:close',
	  HR_USER_DRAG_START: 'HumanResources.User:dragStart',
	  HR_USER_DRAG_MOVE: 'HumanResources.User:dragMove',
	  HR_USER_DRAG_DROP: 'HumanResources.User:drop',
	  HR_USER_DRAG_END: 'HumanResources.User:dragEnd',
	  HR_USER_DRAG_ENTER_NODE: 'HumanResources.User:dragEnterNode',
	  HR_USER_DRAG_LEAVE_NODE: 'HumanResources.User:dragLeaveNode'
	});
	const detailPanelWidth = 364;

	const MenuOption = Object.freeze({
	  addDepartment: 'add-department',
	  addEmployee: 'add-employee'
	});

	// @vue/component
	const AddButton = {
	  name: 'AddButton',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  emits: ['addDepartment'],
	  data() {
	    return {
	      actionMenu: {
	        visible: false
	      }
	    };
	  },
	  computed: {
	    MenuOption() {
	      return MenuOption;
	    }
	  },
	  mounted() {
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (!permissionChecker) {
	      return;
	    }
	    this.dropdownItems = [{
	      id: MenuOption.addDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_DEPARTMENT_DESCR'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.CUBE_PLUS,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate
	      }
	    }, {
	      id: MenuOption.addEmployee,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_EMPLOYEE_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_EMPLOYEE_DESCR'),
	      bIcon: {
	        name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	      }
	    }];
	    this.dropdownItems = this.dropdownItems.filter(item => {
	      if (!item.permission) {
	        return false;
	      }
	      return permissionChecker.hasPermissionOfAction(item.permission.action);
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    addDepartment() {
	      this.$emit('addDepartment');
	    },
	    actionMenuItemClickHandler(actionId) {
	      if (actionId === MenuOption.addDepartment) {
	        this.$emit('addDepartment');
	      }
	    }
	  },
	  template: `
		<div
			class="ui-btn ui-btn-success ui-btn-round ui-btn-sm humanresources-title-panel__add-button"
			data-test-id="hr-org-chart_title-panel__add-button"
			@click="addDepartment"
		>
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON') }}
		</div>
	`
	};

	const createTreeDataStore = treeData => {
	  const dataMap = new Map();
	  treeData.forEach(item => {
	    var _dataMap$get, _dataMap$get2, _mapParentItem$childr;
	    const {
	      id,
	      parentId,
	      colorName,
	      entityType
	    } = item;
	    const mapItem = (_dataMap$get = dataMap.get(id)) != null ? _dataMap$get : {};
	    const teamColor = humanresources_companyStructure_utils.getNodeColorSettings(colorName, entityType);
	    dataMap.set(id, {
	      ...mapItem,
	      ...item,
	      teamColor
	    });
	    if (parentId === 0) {
	      return;
	    }
	    const mapParentItem = (_dataMap$get2 = dataMap.get(parentId)) != null ? _dataMap$get2 : {};
	    const children = (_mapParentItem$childr = mapParentItem.children) != null ? _mapParentItem$childr : [];
	    dataMap.set(parentId, {
	      ...mapParentItem,
	      children: [...children, id]
	    });
	  });
	  return dataMap;
	};
	const chartAPI = {
	  removeDepartment: id => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.delete', {
	      nodeId: id
	    });
	  },
	  getDepartmentsData: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.get', {}, {
	      tool: 'structure',
	      category: 'structure',
	      event: 'open_structure'
	    });
	  },
	  getCurrentDepartments: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.current');
	  },
	  getDictionary: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.dictionary');
	  },
	  getUserId: () => {
	    return humanresources_companyStructure_api.getData('humanresources.api.User.getCurrentId');
	  },
	  firstTimeOpened: () => {
	    return humanresources_companyStructure_api.postData('humanresources.api.User.firstTimeOpen');
	  },
	  updateDepartment: (nodeId, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.update', {
	      nodeId,
	      parentId,
	      name: null
	    });
	  },
	  changeOrder: (draggedId, direction, count) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.changeOrder', {
	      nodeId: draggedId,
	      direction,
	      count
	    });
	  },
	  createTreeDataStore
	};

	const OrgChartActions = {
	  applyData: (departments, currentDepartments, userId, structureMap, multipleUsers) => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.$patch({
	      departments,
	      currentDepartments,
	      userId,
	      searchedUserId: userId,
	      structureMap,
	      multipleUsers
	    });
	  },
	  focusDepartment: departmentId => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.focusedNode = departmentId;
	  },
	  searchUserInDepartment: userId => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.searchedUserId = userId;
	  },
	  moveSubordinatesToParent: removableDepartmentId => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const {
	      departments,
	      currentDepartments,
	      structureMap
	    } = store;
	    const removableDepartment = departments.get(removableDepartmentId);
	    const {
	      parentId,
	      entityType,
	      children: removableDeparmentChildren = [],
	      userCount: removableDepartmentUserCount,
	      heads: removableDeparmentHeads,
	      employees: removableDeparmentEmployees = []
	    } = removableDepartment;
	    const parentDepartment = departments.get(parentId);
	    removableDeparmentChildren.forEach(childId => {
	      const department = departments.get(childId);
	      const mapEntity = structureMap.get(childId);
	      mapEntity.parentId = parentId;
	      department.parentId = parentId;
	    });
	    if (removableDepartmentUserCount > 0 && entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	      var _parentDepartment$emp, _parentDepartment$emp2;
	      const parentDepartmentUsersIds = new Set([...parentDepartment.heads, ...((_parentDepartment$emp = parentDepartment.employees) != null ? _parentDepartment$emp : [])].map(user => user.id));
	      const removableDeparmentUsers = [...removableDeparmentHeads, ...removableDeparmentEmployees];
	      const movableUsers = removableDeparmentUsers.filter(user => {
	        return !parentDepartmentUsersIds.has(user.id);
	      });
	      for (const user of movableUsers) {
	        user.role = humanresources_companyStructure_api.memberRoles.employee;
	      }
	      parentDepartment.userCount += movableUsers.length;
	      parentDepartment.employees = [...((_parentDepartment$emp2 = parentDepartment.employees) != null ? _parentDepartment$emp2 : []), ...movableUsers];
	    }
	    parentDepartment.children = [...parentDepartment.children, ...removableDeparmentChildren];
	    if (currentDepartments.includes(removableDepartmentId) && entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	      store.changeCurrentDepartment(removableDepartmentId, parentId);
	    }
	  },
	  markDepartmentAsRemoved: removableDepartmentId => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const removableDepartment = departments.get(removableDepartmentId);
	    const {
	      parentId
	    } = removableDepartment;
	    const parentDepartment = departments.get(parentId);
	    parentDepartment.children = parentDepartment.children.filter(childId => {
	      return childId !== removableDepartmentId;
	    });
	    delete removableDepartment.parentId;
	    departments.set(removableDepartmentId, {
	      ...removableDepartment,
	      prevParentId: parentId
	    });
	  },
	  removeDepartment: departmentId => {
	    const {
	      departments,
	      structureMap
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    departments.delete(departmentId);
	    structureMap.delete(departmentId);
	  },
	  inviteUser: userData => {
	    const {
	      nodeId,
	      ...restData
	    } = userData;
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const department = departments.get(nodeId);
	    if (department.employees) {
	      departments.set(nodeId, {
	        ...department,
	        employees: [...department.employees, {
	          ...restData
	        }],
	        userCount: department.userCount + 1
	      });
	    }
	  },
	  orderDepartments: async (draggedId, targetId, direction, count) => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const department = departments.get(draggedId);
	    const {
	      parentId
	    } = department;
	    const parentDepartment = departments.get(parentId);
	    const {
	      children
	    } = parentDepartment;
	    const targetIndex = children.indexOf(targetId);
	    const newChildren = children.filter(childId => childId !== draggedId);
	    newChildren.splice(targetIndex, 0, draggedId);
	    departments.set(parentId, {
	      ...parentDepartment,
	      children: newChildren
	    });
	    try {
	      await chartAPI.changeOrder(draggedId, direction, count);
	      return true;
	    } catch {
	      departments.set(parentId, {
	        ...parentDepartment,
	        children
	      });
	      return false;
	    }
	  },
	  clearNodesChatLists: nodeIds => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    nodeIds.forEach(nodeId => {
	      const department = departments.get(nodeId);
	      departments.set(nodeId, {
	        ...department,
	        chats: null,
	        channels: null
	      });
	    });
	  }
	};

	// @vue/component
	const SearchBar = {
	  name: 'search-bar',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    focus: {
	      mounted(el) {
	        el.focus();
	      }
	    }
	  },
	  emits: ['locate'],
	  data() {
	    return {
	      canEditPermissions: false,
	      showSearchBar: false
	    };
	  },
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
	  },
	  watch: {
	    departments: {
	      handler() {
	        this.searchDialog.destroy();
	      },
	      deep: true
	    }
	  },
	  created() {
	    this.searchDialog = this.getSearchDialog();
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    showSearchbar() {
	      if (this.showSearchBar) {
	        this.showSearchBar = false;
	        return;
	      }
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'structure',
	        event: 'search'
	      });
	      this.showSearchBar = true;
	    },
	    hideSearchbar() {
	      this.showSearchBar = false;
	    },
	    getSearchDialog() {
	      const dialog = new ui_entitySelector.Dialog({
	        width: 425,
	        height: 320,
	        multiple: false,
	        entities: [{
	          id: 'user',
	          searchFields: [{
	            name: 'supertitle',
	            type: 'string',
	            system: true,
	            searchable: true
	          }, {
	            name: 'position',
	            type: 'string'
	          }],
	          options: {
	            intranetUsersOnly: true,
	            emailUsers: false,
	            inviteEmployeeLink: false
	          }
	        }, {
	          id: 'structure-node',
	          options: {
	            selectMode: 'departmentsOnly',
	            useMultipleTabs: true,
	            restricted: 'view',
	            flatMode: true,
	            includedNodeEntityTypes: ['department', 'team']
	          }
	        }],
	        recentTabOptions: {
	          id: 'recents',
	          visible: true
	        },
	        dropdownMode: true,
	        enableSearch: false,
	        hideOnDeselect: false,
	        context: 'HR_STRUCTURE',
	        events: {
	          'Item:onSelect': event => {
	            const item = event.getData().item;
	            if (item.entityType === 'employee') {
	              this.$emit('locate', item.customData.get('nodeId'));
	              OrgChartActions.searchUserInDepartment(item.id);
	              dialog.recentItemsToSave.push(item);
	              dialog.saveRecentItems();
	              return;
	            }
	            dialog.recentItemsToSave.push(item);
	            dialog.saveRecentItems();
	            this.$emit('locate', item.id);
	          },
	          onLoad: event => {
	            var _event$target$items$g;
	            (_event$target$items$g = event.target.items.get('user')) == null ? void 0 : _event$target$items$g.forEach(item => {
	              if (!item.getSubtitle()) {
	                item.setSubtitle(item.customData.get('position'));
	              }
	            });
	          },
	          'SearchTab:onLoad': event => {
	            var _event$target$items$g2;
	            (_event$target$items$g2 = event.target.items.get('user')) == null ? void 0 : _event$target$items$g2.forEach(item => {
	              if (!item.getSubtitle()) {
	                item.setSubtitle(item.customData.get('position'));
	              }
	            });
	          },
	          onDestroy: () => {
	            this.searchDialog = this.getSearchDialog();
	          }
	        }
	      });
	      const dialogContainer = dialog.getContainer();
	      main_core.Dom.attr(dialogContainer, 'data-test-id', 'hr-org-chart_title-panel__search-dialog-container');
	      return dialog;
	    },
	    onEnter() {
	      if (this.$refs.searchName) {
	        this.searchDialog.setTargetNode(this.$refs.searchName);
	        if (!this.searchDialog.isOpen()) {
	          this.searchDialog.show();
	        }
	        main_core.Event.bind(window, 'mousedown', this.handleClickOutside);
	      }
	    },
	    handleClickOutside(event) {
	      if (this.$refs.searchName && !this.$refs.searchName.parentElement.contains(event.target) && !this.searchDialog.isOpen()) {
	        this.clearSearch();
	        this.hideSearchbar();
	        main_core.Event.unbind(window, 'mousedown', this.handleClickOutside);
	      }
	    },
	    search(value) {
	      if (!this.searchDialog.isOpen()) {
	        this.searchDialog.show();
	      }
	      this.searchDialog.search(value);
	    },
	    clearSearch() {
	      this.searchDialog.getSearchTab().clearResults();
	      this.searchDialog.selectTab('recents');
	      if (this.$refs.searchName) {
	        this.$refs.searchName.value = '';
	        this.$refs.searchName.focus();
	      }
	    }
	  },
	  template: `
		<div
		    class="humanresources-title-panel-search-bar-container"
		    :class="{'--opened': showSearchBar}"
		>
		  <div
		      class="humanresources-title-panel-search-bar-block__search"
		      @click="showSearchbar"
				data-test-id="hr-org-chart_title-panel__search-bar-button"
		  >
		    <BIcon :name="set.SEARCH_2" :size="24" class="hr-title-search-icon"></BIcon>
		  </div>
		  <transition name="humanresources-title-panel-search-bar-fade" mode="in-out" @after-enter="onEnter">
		    <div v-if="showSearchBar"
		         class="humanresources-title-panel-search-bar-block__search-bar"
		    >
		      <input
		          ref="searchName"
		          key="searchInput"
		          type="text"
		          :placeholder="loc('HUMANRESOURCES_SEARCH_PLACEHOLDER_MSGVER_1')"
		          v-focus
		          class="humanresources-title-panel-search-bar-block__search-input"
		          @click="onEnter"
		          @input="search($event.target.value)"
					data-test-id="hr-org-chart_title-panel__search-bar-input"
		      >
		      <div
		          key="searchReset"
		          @click="clearSearch"
		          class="humanresources-title-panel-search-bar-block__search-reset"
		      >
		        <div class="humanresources-title-panel-search-bar-block__search-cursor"></div>
		        <BIcon
		            :name="set.CROSS_CIRCLE_50"
		            :size="24"
		            color="#2FC6F6"
		        ></BIcon>
		      </div>
		    </div>
		  </transition>
		</div>
	`
	};

	const MenuOption$1 = Object.freeze({
	  accessRights: 'access-rights',
	  teamAccessRights: 'team-access-rights'
	});

	// @vue/component
	const BurgerMenuButton = {
	  name: 'BurgerMenuButton',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  data() {
	    return {
	      actionMenu: {
	        visible: false
	      }
	    };
	  },
	  mounted() {
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    const id = permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.accessEdit) ? MenuOption$1.accessRights : MenuOption$1.teamAccessRights;
	    this.dropdownItems = [{
	      id,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION_DESCR_MSGVER_2'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSONS_2,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      dataTestId: 'hr-org-chart_title-panel__access-rights-button',
	      permission: permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.accessEdit) || permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.teamAccessEdit)
	    }];
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    actionMenuItemClickHandler(actionId) {
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'roles',
	        event: 'open_roles'
	      });
	      if (actionId === MenuOption$1.accessRights) {
	        BX.SidePanel.Instance.open('/hr/config/permission/', {
	          usePadding: true
	        });
	      }
	      if (actionId === MenuOption$1.teamAccessRights) {
	        BX.SidePanel.Instance.open('/hr/config/permission/?category=TEAM', {
	          usePadding: true
	        });
	      }
	    }
	  },
	  template: `
		<span
			ref="burgerMenuButton"
			@click="actionMenu.visible = true"
			class="humanresources-title-panel__burger-menu-button"
			data-test-id="hr-org-chart_title-panel__burger-menu-button"
		>
			<svg
				viewBox="0 0 24 24"
				fill="none"
				class="humanresources-title-panel__icon"
				:class="{'--selected': actionMenu.visible }"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M18.7067 15.5577C18.8172 15.5577 18.9067 15.6473 18.9067 15.7577L18.9067 17.2424C18.9067 17.3528 18.8172 17.4424 18.7067 17.4424H5.29375C5.1833 17.4424 5.09375 17.3528 5.09375 17.2424L5.09379 15.7577C5.09379 15.6473 5.18333 15.5577 5.29379 15.5577H18.7067ZM18.7067 11.5577C18.8172 11.5577 18.9067 11.6473 18.9067 11.7577L18.9067 13.2424C18.9067 13.3528 18.8172 13.4424 18.7067 13.4424H5.29375C5.1833 13.4424 5.09375 13.3528 5.09375 13.2424L5.09379 11.7577C5.09379 11.6473 5.18333 11.5577 5.29379 11.5577H18.7067ZM18.7067 7.55774C18.8172 7.55774 18.9067 7.64729 18.9067 7.75774L18.9067 9.24238C18.9067 9.35284 18.8172 9.44238 18.7067 9.44238H5.29375C5.1833 9.44238 5.09375 9.35283 5.09375 9.24237L5.09379 7.75773C5.09379 7.64728 5.18333 7.55774 5.29379 7.55774H18.7067Z" fill="#959ca4"/>
			</svg>
		 </span>
		<RouteActionMenu
			v-if="actionMenu.visible"
			id="title-panel-burger-menu"
			:items="dropdownItems.filter(item => item.permission)"
			:bindElement="this.$refs.burgerMenuButton"
			@action="actionMenuItemClickHandler($event)"
			@close="this.actionMenu.visible = false"
			containerDataTestId="hr-org-chart_title-panel__burger-menu-container"
		/>
	`
	};

	const TitlePanel = {
	  components: {
	    AddButton,
	    BurgerMenuButton,
	    SearchBar,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Set: ui_iconSet_api_vue.Set,
	    ResponsiveHint: humanresources_companyStructure_structureComponents.ResponsiveHint
	  },
	  data() {
	    return {
	      canEditPermissions: false,
	      canAddNode: false,
	      toolbarStarActive: false,
	      isHovered: false,
	      hintKey: 0
	    };
	  },
	  created() {
	    this.toolbarStarElement = document.getElementById('uiToolbarStar');
	  },
	  mounted() {
	    try {
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      this.canEditPermissions = permissionChecker && (permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.accessEdit) || permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.teamAccessEdit));
	      this.canAddNode = permissionChecker && (permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate) || permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate));
	    } catch (error) {
	      console.error('Failed to fetch data:', error);
	    }
	    const observer = new MutationObserver(() => {
	      const newState = main_core.Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
	      if (this.toolbarStarActive !== newState) {
	        this.toolbarStarActive = newState;
	        this.hintKey++;
	      }
	    });
	    observer.observe(this.toolbarStarElement, {
	      attributes: true,
	      attributeFilter: ['class']
	    });
	    this.toolbarStarActive = main_core.Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
	  },
	  name: 'title-panel',
	  emits: ['showWizard'],
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    toolbarStarIcon() {
	      if (this.isAirTemplate) {
	        return this.set.FAVORITE_0;
	      }
	      if (this.isHovered || this.toolbarStarActive) {
	        return this.set.FAVORITE_1;
	      }
	      return this.set.FAVORITE_0;
	    },
	    isAirTemplate() {
	      return BX.Reflection.getClass('top.BX.Intranet.Bitrix24.Template') !== null;
	    },
	    toolbarClassIcon() {
	      if (!this.isAirTemplate) {
	        return 'humanresources-title-panel__star';
	      }
	      return this.toolbarStarActive ? 'humanresources-title-panel__unpin' : 'humanresources-title-panel__pin';
	    },
	    starHintText() {
	      return this.toolbarStarActive ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_UN_SAVE_HINT') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_SAVE_HINT');
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    addDepartment() {
	      this.$emit('showWizard', {
	        source: humanresources_companyStructure_api.AnalyticsSourceType.HEADER
	      });
	    },
	    onLocate(nodeId) {
	      main_core_events.EventEmitter.emit(humanresources_companyStructure_orgChart.events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, {
	        nodeId
	      });
	    },
	    triggerFavoriteStar() {
	      this.toolbarStarElement.click();
	      ui_notification.UI.Notification.Center.notify({
	        content: this.toolbarStarActive ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_UN_SAVED') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_SAVED'),
	        autoHideDelay: 2000
	      });
	    }
	  },
	  template: `
		<div class="humanresources-title-panel">
			<p class="humanresources-title-panel__title">
				{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_TITLE') }}
			</p>
			<ResponsiveHint v-if="!isAirTemplate" :content="starHintText" :key="'hint-air-' + hintKey">
				<BIcon
					:name="toolbarStarIcon"
					:size="24"
					color="rgba(149, 156, 164, 1)"
					:class="toolbarClassIcon"
					@mouseover="isHovered = true"
					@mouseleave="isHovered = false" 
					@click="triggerFavoriteStar"
				></BIcon>
			</ResponsiveHint>
			<ResponsiveHint v-else :content="starHintText" :key="'hint-other-' + hintKey">
				<div 
					:class="['ui-icon-set', '--size-sm', toolbarClassIcon]" 
					@click="triggerFavoriteStar"
				></div>
			</ResponsiveHint>
			<AddButton
				v-if="canAddNode"
				@addDepartment="addDepartment"
			/>
			<div class="humanresources-title-panel__icon-buttons">
				<BurgerMenuButton v-if="canEditPermissions"/>
				<SearchBar @locate="onLocate"/>
			</div>
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

	const UrlParamNames = Object.freeze({
	  FocusNodeId: 'focusNodeId'
	});
	var _getParamsFromUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getParamsFromUrl");
	var _castAndValidate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("castAndValidate");
	class UrlProvidedParamsService {
	  static getParams() {
	    const paramMap = babelHelpers.classPrivateFieldLooseBase(this, _getParamsFromUrl)[_getParamsFromUrl]();
	    return {
	      focusNodeId: babelHelpers.classPrivateFieldLooseBase(this, _castAndValidate)[_castAndValidate](UrlParamNames.FocusNodeId, paramMap.get(UrlParamNames.FocusNodeId))
	    };
	  }
	}
	function _getParamsFromUrl2() {
	  const paramMap = new Map();
	  const urlSearchParams = new URLSearchParams(document.location.search);
	  Object.values(UrlParamNames).forEach(paramName => {
	    const paramValue = urlSearchParams.get(paramName);
	    if (!paramValue) {
	      return;
	    }
	    paramMap.set(paramName, paramValue);
	  });
	  return paramMap;
	}
	function _castAndValidate2(paramName, value) {
	  if (main_core.Type.isUndefined(value)) {
	    return null;
	  }
	  let castedValue = value;
	  if (paramName === UrlParamNames.FocusNodeId) {
	    castedValue = Number(value);
	    if (!main_core.Type.isInteger(castedValue)) {
	      return null;
	    }
	  }
	  return castedValue;
	}
	Object.defineProperty(UrlProvidedParamsService, _castAndValidate, {
	  value: _castAndValidate2
	});
	Object.defineProperty(UrlProvidedParamsService, _getParamsFromUrl, {
	  value: _getParamsFromUrl2
	});

	// @vue/component
	const HeadList = {
	  name: 'headList',
	  components: {
	    UserListActionMenu: humanresources_companyStructure_structureComponents.UserListActionMenu,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    hint: humanresources_companyStructure_structureComponents.Hint
	  },
	  props: {
	    entityId: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    items: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    collapsed: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    type: {
	      type: String,
	      required: false,
	      default: 'head'
	    },
	    isTeamEntity: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  data() {
	    return {
	      isCollapsed: false,
	      isUpdating: true,
	      headsVisible: false
	    };
	  },
	  computed: {
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    dropdownItems() {
	      return this.items.map(item => {
	        const workPosition = this.getPositionText(item);
	        return {
	          ...item,
	          workPosition
	        };
	      });
	    },
	    titleBar() {
	      return this.type === this.userTypes.deputy ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE');
	    },
	    subordinatesCount() {
	      return this.getSubtreeUserCount(this.entityId);
	    },
	    MainIcons: () => ui_iconSet_api_vue.Main,
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'multipleUsers'])
	  },
	  created() {
	    this.userTypes = {
	      head: 'head',
	      deputy: 'deputy'
	    };
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    handleUserClick(url) {
	      BX.SidePanel.Instance.open(url, {
	        cacheable: false
	      });
	    },
	    closeHeadList() {
	      if (this.headsVisible)
	        // to prevent double unsubscription
	        {
	          this.headsVisible = false;
	          main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
	          main_core_events.EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.closeHeadList);
	        }
	    },
	    openHeadList() {
	      if (!this.headsVisible)
	        // to prevent double subscription
	        {
	          this.headsVisible = true;
	          main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
	          main_core_events.EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.closeHeadList);
	        }
	    },
	    getPositionText(item) {
	      return item.workPosition || this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_HEAD_POSITION');
	    },
	    getSubtreeUserCount(rootId) {
	      if (!rootId) {
	        return 0;
	      }
	      let sum = 0;
	      const checkedEntities = new Set();
	      const countedEntities = new Set();
	      const stack = [rootId];
	      while (stack.length > 0) {
	        var _entity$children;
	        const id = stack.pop();
	        if (checkedEntities.has(id)) {
	          continue;
	        }
	        checkedEntities.add(id);
	        const entity = this.departments.get(id);
	        if (!entity) {
	          continue;
	        }
	        if (entity.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	          continue;
	        }
	        let count = Number(entity.userCount) || 0;

	        // if we are at the root, we should not count heads that are already displayed
	        if (id === rootId) {
	          const headsAtRoot = this.items.length;
	          count = Math.max(0, count - headsAtRoot);
	        }
	        if (count > 0) {
	          sum += count;
	          countedEntities.add(String(id));
	        }
	        if (((_entity$children = entity.children) == null ? void 0 : _entity$children.length) > 0) {
	          for (const childId of entity.children) {
	            if (!checkedEntities.has(childId)) {
	              stack.push(childId);
	            }
	          }
	        }
	      }
	      let duplicatesToSubtract = 0;
	      if (this.multipleUsers && countedEntities.size > 0) {
	        const headUserIds = new Set(this.items.map(item => String(item.id)));

	        // for each user that is in multiple departments
	        // check how many of those departments are in the countedEntities set
	        for (const [userId, entityIds] of Object.entries(this.multipleUsers)) {
	          if (!main_core.Type.isArray(entityIds) || entityIds.length === 0) {
	            continue;
	          }
	          let cnt = 0;
	          for (const entityId of entityIds) {
	            if (countedEntities.has(String(entityId))) {
	              cnt++;
	            }
	          }

	          // if user is in more than one counted entity, we need to subtract the duplicates
	          if (cnt > 1) {
	            let duplicatesCount = cnt - 1;
	            if (headUserIds.has(String(userId))) {
	              duplicatesCount = Math.max(0, duplicatesCount - 1);
	            }
	            duplicatesToSubtract += duplicatesCount;
	          }
	        }
	      }
	      return Math.max(0, sum - duplicatesToSubtract);
	    },
	    getEmployeesCountIconColor() {
	      return humanresources_companyStructure_utils.getColorCode('paletteGray70');
	    },
	    getEmployeesCountIconText() {
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_ALL_EMPLOYEES_COUNT_HINT_TEXT');
	    }
	  },
	  template: `
		<div v-if="items.length">
			<p v-if="title" class="humanresources-tree__node_employees-title">
				{{ title }}
			</p>
			<div
				class="humanresources-tree__node_head"
				:class="{ '--collapsed': collapsed }"
				v-for="(item, index) in items.slice(0, 2)"
			>
				<img
					:src="item.avatar ? encodeURI(item.avatar) : defaultAvatar"
					class="humanresources-tree__node_avatar --head"
					:class="{ '--collapsed': collapsed }"
					@click.stop="handleUserClick(item.url)"
				/>
				<div class="humanresources-tree__node_head-text">
					<div class="humanresources-tree__node_head-name-container">
					<span
						:bx-tooltip-user-id="item.id"
						bx-tooltip-context="b24"
						class="humanresources-tree__node_head-name"
						@click.stop="handleUserClick(item.url)"
					>
						{{ item.name }}
					</span>
						<div
							v-if="index === 0 && type === userTypes.head && !isTeamEntity"
							v-hint="getEmployeesCountIconText()"
							class="humanresources-tree__node_head-subordinates"
							:class="{ '--active': headsVisible }"
							:data-test-id="'hr-company-structure_org-chart-tree__node-subordinates'"
						>
							<BIcon
								:name="MainIcons.PERSONS_2"
								:size="14"
								class="hr-company-structure_org-chart-tree__node-subordinates-icon"
								:color="getEmployeesCountIconColor()"
							/>
							<span class="hr-company-structure_org-chart-tree__node-subordinates-text">{{ String(subordinatesCount) }}</span>
						</div>
					</div>
					<span v-if="!collapsed" class="humanresources-tree__node_head-position">
						{{ getPositionText(item) }}
					</span>
				</div>
				<span
					v-if="index === 1 && items.length > 2"
					class="humanresources-tree__node_head-rest"
					:class="{ '--active': headsVisible }"
					:data-test-id="'hr-company-structure_org-chart-tree__node-' + type + '-rest'"
					ref="showMoreHeadList"
					@click.stop="openHeadList"
				>
					{{ '+' + String(items.length - 2) }}
				</span>
			</div>
		</div>
		<UserListActionMenu
			v-if="headsVisible"
			:id="type === userTypes.head ? 'head-list-popup-head' : 'head-list-popup-deputy'"
			:items="dropdownItems"
			:width="228"
			:bindElement="$refs.showMoreHeadList[0]"
			@close="closeHeadList"
			:titleBar="titleBar"
		/>
	`
	};

	class AbstractActionMenu {
	  constructor(entityId) {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.entityId = entityId;
	  }
	  getItems() {
	    throw new Error('Must override this function');
	  }
	  getFilteredItems() {
	    if (!this.permissionChecker) {
	      return [];
	    }
	    const items = this.getItems();
	    return items.filter(item => {
	      if (main_core.Type.isFunction(item.hasPermission)) {
	        return item.hasPermission(this.permissionChecker, this.entityId);
	      }
	      return false;
	    });
	  }
	  onActionMenuItemClick(actionId) {
	    const targetItem = this.items.find(item => item.id === actionId);
	    targetItem == null ? void 0 : targetItem.invoke({
	      entityId: this.entityId,
	      analyticSource: this.analyticSource,
	      entityType: this.entityType
	    });
	  }
	}

	class AbstractMenuItem {
	  constructor({
	    id,
	    title,
	    description,
	    bIcon,
	    permissionAction,
	    dataTestId
	  }) {
	    this.id = id;
	    this.title = title;
	    this.description = description;
	    this.bIcon = bIcon;
	    this.permissionAction = permissionAction;
	    this.dataTestId = dataTestId;
	  }
	  invoke(options) {
	    throw new Error('Must override this function');
	  }
	  hasPermission(permissionChecker, entityId) {
	    return permissionChecker.hasPermission(this.permissionAction, entityId);
	  }
	}

	const MenuActions = Object.freeze({
	  editDepartment: 'editDepartment',
	  addDepartment: 'addDepartment',
	  addEmployee: 'addEmployee',
	  editEmployee: 'editEmployee',
	  removeDepartment: 'removeDepartment',
	  moveEmployee: 'moveEmployee',
	  userInvite: 'userInvite',
	  teamRights: 'teamRights',
	  moveUserToAnotherDepartment: 'moveUserToAnotherDepartment',
	  removeUserFromDepartment: 'removeUserFromDepartment',
	  fireUserFromCompany: 'fireUserFromCompany',
	  openChat: 'openChat',
	  unbindChat: 'unbindChat'
	});

	class AddDepartmentMenuItem extends AbstractMenuItem {
	  constructor(entityType) {
	    let title = '';
	    let description = '';

	    // temporary check for option
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (permissionChecker.isTeamsAvailable) {
	      title = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE_MSGVER_1');
	      description = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE_MSGVER_1');
	    } else {
	      title = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE_NO_TEAM');
	      description = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE_NO_TEAM');
	    }
	    super({
	      id: MenuActions.addDepartment,
	      title,
	      description,
	      bIcon: {
	        name: ui_iconSet_api_core.Main.CUBE_PLUS,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: null,
	      dataTestId: 'hr-company-structure_menu__add-department-item'
	    });
	    this.entityType = entityType;
	    this.permissionChecker = permissionChecker;
	    this.entityType = entityType;
	  }
	  invoke({
	    entityId,
	    analyticSource,
	    entityType
	  }) {
	    // for a team we can create only another team
	    if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      if (!this.permissionChecker.isTeamsAvailable) {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_TEAMS_DISABLED_ERROR_MSGVER_1'),
	          autoHideDelay: 2000
	        });
	        return;
	      }
	      main_core_events.EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
	        nodeId: entityId,
	        isEditMode: false,
	        showEntitySelector: false,
	        source: analyticSource,
	        entityType
	      });
	    }
	    // for a department we offer to chose entity
	    else {
	      main_core_events.EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
	        nodeId: entityId,
	        isEditMode: false,
	        showEntitySelector: true,
	        source: analyticSource,
	        entityType
	      });
	    }
	  }
	  hasPermission(permissionChecker, entityId) {
	    if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate, entityId);
	    }
	    return permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate, entityId);
	  }
	}

	class EditDepartmentMenuItem extends AbstractMenuItem {
	  constructor(entityType) {
	    const title = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_TITLE');
	    const description = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_SUBTITLE_MSGVER_1') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_SUBTITLE_MSGVER_1');
	    super({
	      id: MenuActions.editDepartment,
	      title,
	      description,
	      bIcon: {
	        name: ui_iconSet_api_core.Main.EDIT_PENCIL,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: null,
	      dataTestId: 'hr-company-structure_menu__edit-department-item'
	    });
	    this.entityType = entityType;
	  }
	  invoke({
	    entityId,
	    analyticSource,
	    refToFocus = 'title'
	  }) {
	    main_core_events.EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
	      nodeId: entityId,
	      isEditMode: true,
	      showEntitySelector: false,
	      type: 'department',
	      entityType: this.entityType,
	      source: analyticSource,
	      refToFocus
	    });
	  }
	  hasPermission(permissionChecker, entityId) {
	    if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamSettingsEdit, entityId);
	    }
	    return permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, entityId) || permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, entityId);
	  }
	}

	class RemoveDepartmentMenuItem extends AbstractMenuItem {
	  constructor(entityType) {
	    const title = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_TEAM_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE');
	    const description = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_TEAM_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE');
	    const permissionAction = entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamDelete : humanresources_companyStructure_permissionChecker.PermissionActions.departmentDelete;
	    super({
	      id: MenuActions.removeDepartment,
	      title,
	      description,
	      bIcon: {
	        name: ui_iconSet_api_core.Main.TRASH_BIN,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permissionAction,
	      dataTestId: 'hr-company-structure_menu__remove-department-item'
	    });
	  }
	  invoke({
	    entityId,
	    entityType
	  }) {
	    main_core_events.EventEmitter.emit(events.HR_ENTITY_REMOVE, {
	      nodeId: entityId,
	      entityType
	    });
	  }
	}

	class AddEmployeeMenuItem extends AbstractMenuItem {
	  constructor(entityType, role) {
	    const permissionAction = entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	    super({
	      id: MenuActions.addEmployee,
	      bIcon: {
	        name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction,
	      dataTestId: 'hr-company-structure_menu__add-employee-item'
	    });
	    this.localize(entityType, role);
	  }
	  localize(entityType, role) {
	    const i18nRole = ['head', 'employee'].includes(role) ? role : 'default';
	    const i18nType = entityType === humanresources_companyStructure_utils.EntityTypes.team ? 'team' : 'default';
	    const i18nMap = {
	      head: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_HEAD_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_HEAD_SUBTITLE'),
	          role: humanresources_companyStructure_api.teamMemberRoles.head
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_HEAD_SUBTITLE'),
	          role: humanresources_companyStructure_api.memberRoles.head
	        }
	      },
	      employee: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_TEAM_EMPLOYEE_SUBTITLE'),
	          role: humanresources_companyStructure_api.teamMemberRoles.employee
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_ADD_EMPLOYEE_SUBTITLE'),
	          role: humanresources_companyStructure_api.memberRoles.employee
	        }
	      },
	      default: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_TEAM_EMPLOYEE_SUBTITLE'),
	          role: null
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
	          role: null
	        }
	      }
	    };
	    this.title = i18nMap[i18nRole][i18nType].title;
	    this.description = i18nMap[i18nRole][i18nType].description;
	    this.role = i18nMap[i18nRole][i18nType].role;
	  }
	  invoke({
	    entityId,
	    entityType
	  }) {
	    humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	      nodeId: entityId,
	      type: 'add',
	      role: this.role,
	      entityType
	    });
	  }
	}

	class EditEmployeesMenuItem extends AbstractMenuItem {
	  constructor(entityType, role) {
	    const permissionAction = entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	    super({
	      id: MenuActions.editEmployee,
	      imageClass: '-hr-department-org-chart-menu-edit-list',
	      bIcon: {
	        name: ui_iconSet_api_core.Main.EDIT_MENU,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction,
	      dataTestId: 'hr-company-structure_menu__edit-employee-item'
	    });
	    this.localize(entityType, role);
	  }
	  localize(entityType, role) {
	    const i18nRole = ['head', 'employee'].includes(role) ? role : 'default';
	    const i18nType = entityType === humanresources_companyStructure_utils.EntityTypes.team ? 'team' : 'default';
	    const i18nMap = {
	      head: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_HEAD_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_HEAD_SUBTITLE')
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_HEAD_SUBTITLE')
	        }
	      },
	      employee: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_SUBTITLE')
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_EMPLOYEE_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_TAB_USERS_MEMBER_ACTION_MENU_EDIT_TEAM_EMPLOYEE_SUBTITLE')
	        }
	      },
	      default: {
	        team: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_EMPLOYEE_LIST_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_TEAM_EMPLOYEE_LIST_SUBTITLE')
	        },
	        default: {
	          title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
	          description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE')
	        }
	      }
	    };
	    this.title = i18nMap[i18nRole][i18nType].title;
	    this.description = i18nMap[i18nRole][i18nType].description;
	  }
	  invoke({
	    entityId,
	    analyticSource
	  }) {
	    main_core_events.EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
	      nodeId: entityId,
	      isEditMode: true,
	      showEntitySelector: false,
	      type: 'employees',
	      source: analyticSource
	    });
	  }
	}

	class MoveEmployeeMenuItem extends AbstractMenuItem {
	  constructor() {
	    super({
	      id: MenuActions.moveEmployee,
	      title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
	      description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment,
	      dataTestId: 'hr-company-structure_menu__move-employee-item'
	    });
	  }
	  invoke({
	    entityId,
	    analyticSource
	  }) {
	    humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	      nodeId: entityId,
	      type: 'move'
	    });
	  }
	  hasPermission(permissionChecker, entityId) {
	    return permissionChecker.currentUserPermissions.ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT && permissionChecker.hasPermission(this.permissionAction, entityId);
	  }
	}

	class UserInviteMenuItem extends AbstractMenuItem {
	  constructor() {
	    super({
	      id: MenuActions.userInvite,
	      title: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_TITLE'),
	      description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_LETTER,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.inviteToDepartment,
	      dataTestId: 'hr-company-structure_menu__user-invite-item'
	    });
	  }
	  invoke({
	    entityId
	  }) {
	    BX.SidePanel.Instance.open('/bitrix/services/main/ajax.php?action=getSliderContent' + '&c=bitrix%3Aintranet.invitation&mode=ajax' + `&departments[]=${Number(entityId)}&firstInvitationBlock=invite-with-group-dp`, {
	      cacheable: false,
	      allowChangeHistory: false,
	      width: 1100
	    });
	  }
	}

	class EntityActionMenu extends AbstractActionMenu {
	  constructor(entityId, entityType, analyticSource) {
	    super(entityId);
	    this.entityType = entityType;
	    this.analyticSource = analyticSource;
	    this.items = this.getFilteredItems();
	  }
	  getItems() {
	    if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return [new EditDepartmentMenuItem(this.entityType), new AddDepartmentMenuItem(this.entityType), new AddEmployeeMenuItem(this.entityType), new RemoveDepartmentMenuItem(this.entityType)];
	    }
	    return [new EditDepartmentMenuItem(this.entityType), new AddDepartmentMenuItem(this.entityType), new AddEmployeeMenuItem(this.entityType), new MoveEmployeeMenuItem(), new UserInviteMenuItem(), new RemoveDepartmentMenuItem(this.entityType)];
	  }
	}

	// @vue/component
	const DepartmentMenuButton = {
	  name: 'DepartmentMenuButton',
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  props: {
	    entityId: {
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
	      return new EntityActionMenu(this.entityId, this.entityType, humanresources_companyStructure_api.AnalyticsSourceType.CARD);
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      this.menu.onActionMenuItemClick(actionId);
	    },
	    closeMenu() {
	      if (this.menuVisible) {
	        this.menuVisible = false;
	        main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
	        main_core_events.EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.closeMenu);
	      }
	    },
	    openMenu() {
	      if (!this.menuVisible) {
	        this.menuVisible = true;
	        main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
	        main_core_events.EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.closeMenu);
	      }
	    }
	  },
	  template: `
		<div
			v-if="menu.items.length"
			class="ui-icon-set --more humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.menuVisible }"
			ref="departmentMenuButton"
			data-test-id="tree-node-more-button"
			@click.stop="openMenu"
		>
		</div>

		<RouteActionMenu
			v-if="menuVisible"
			:id="'tree-node-department-menu-' + entityId"
			:width="302"
			:items="menu.items"
			:bindElement="this.$refs.departmentMenuButton"
			@action="onActionMenuItemClick"
			@close="closeMenu"
		/>
	`
	};

	// @vue/component
	const DepartmentInfoIconButton = {
	  name: 'DepartmentInfoIconButton',
	  components: {
	    BasePopup: humanresources_companyStructure_structureComponents.BasePopup
	  },
	  props: {
	    entityId: {
	      type: Number,
	      required: true
	    },
	    canvasZoom: {
	      type: Number,
	      required: true
	    },
	    description: {
	      type: [String, null],
	      default: null
	    }
	  },
	  data() {
	    return {
	      showPopup: false
	    };
	  },
	  computed: {
	    popupConfig() {
	      const popupWidth = 340;
	      const buttonWidth = 22 * this.canvasZoom;
	      const initialPopupOffset = 41 - this.canvasZoom; // subtract 1px * zoom
	      const angleWidth = 33;
	      return {
	        width: popupWidth,
	        bindElement: this.$refs.departmentMenuButton,
	        bindOptions: {
	          position: 'top'
	        },
	        borderRadius: '12px',
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 16,
	        offsetTop: -2 * this.canvasZoom,
	        offsetLeft: buttonWidth / 2 - popupWidth / 2 + initialPopupOffset,
	        angleOffset: popupWidth / 2 - angleWidth / 2
	      };
	    }
	  },
	  created() {
	    this.menuItem = new EditDepartmentMenuItem(humanresources_companyStructure_utils.EntityTypes.team);
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.canEdit = this.menuItem.hasPermission(permissionChecker, this.entityId);
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onClose() {
	      if (this.showPopup) {
	        this.showPopup = false;
	        main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.onClose);
	        main_core_events.EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.onClose);
	      }
	    },
	    onOpen() {
	      if (!this.showPopup) {
	        this.showPopup = true;
	        main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.onClose);
	        main_core_events.EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.onClose);
	      }
	    },
	    addDescription() {
	      this.onClose();
	      this.menuItem.invoke({
	        entityId: this.entityId,
	        analyticSource: humanresources_companyStructure_api.AnalyticsSourceType.CARD,
	        refToFocus: 'description'
	      });
	    }
	  },
	  template: `
		<div
			v-if="canEdit || description"
			class="ui-icon-set --info-1 humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.showPopup }"
			ref="departmentMenuButton"
			@click.stop="onOpen"
			data-test-id="tree-node-info-button"
		>
		</div>
		<BasePopup
			v-if="showPopup"
			:config="popupConfig"
			:id="'humanresources-tree__node_info-popup' + entityId"
			@close="onClose"
		>
			<div class="humanresources-tree__node_info-popup-content_description" v-if="description">
				{{ description }}
			</div>
			<div v-else class="humanresources-tree__node_info-popup-content">
				<div class="humanresources-tree__node_info-popup-content_left"></div>
				<div class="humanresources-tree__node_info-popup-content_right">
					<div class="humanresources-tree__node_info-popup-content_right_title">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_TITLE') }}
					</div>
					<div class="humanresources-tree__node_info-popup-content_right_text">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_TEXT') }}
					</div>
					<div
						class="ui-btn ui-btn-primary ui-btn-round ui-btn-xs ui-btn-no-caps"
						data-test-id="tree-node-info-popup_add-button"
						@click="addDescription"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_BUTTON') }}
					</div>
				</div>
			</div>
		</BasePopup>
	`
	};

	// @vue/component
	const SubdivisionAddButton = {
	  name: 'SubdivisionAddButton',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    entityId: {
	      type: Number,
	      required: true
	    },
	    entityType: {
	      type: String,
	      default: humanresources_companyStructure_utils.EntityTypes.department
	    }
	  },
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  created() {
	    this.menuItem = new AddDepartmentMenuItem(this.entityType);
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.canShow = this.menuItem.hasPermission(permissionChecker, this.entityId);
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    addSubdivision() {
	      this.menuItem.invoke({
	        entityId: this.entityId,
	        analyticSource: humanresources_companyStructure_api.AnalyticsSourceType.PLUS,
	        entityType: this.entityType
	      });
	    }
	  },
	  template: `
		<div class="humanresources-tree__node_add-subdivision" v-if="canShow">
		  <button class="humanresources-tree__node_add-button" @click="addSubdivision">
		    <BIcon :name="set.PLUS_20" :size="32" class="humanresources-tree__node_add-icon"></BIcon>
		  </button>
		</div>
	`
	};

	// @vue/component
	const TreeNode = {
	  name: 'treeNode',
	  components: {
	    DepartmentMenuButton,
	    HeadList,
	    SubdivisionAddButton,
	    DepartmentInfoIconButton
	  },
	  directives: {
	    hint: humanresources_companyStructure_structureComponents.Hint
	  },
	  inject: ['getTreeBounds'],
	  props: {
	    nodeId: {
	      type: Number,
	      required: true
	    },
	    expandedNodes: {
	      type: Array,
	      required: true
	    },
	    canvasZoom: {
	      type: Number,
	      required: true
	    },
	    currentDepartments: {
	      type: Array,
	      required: true
	    },
	    isShown: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    userDropTargetNodeId: {
	      type: Number,
	      default: null
	    },
	    isUserDropAllowed: {
	      type: Boolean,
	      default: false
	    },
	    isDraggingUser: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['calculatePosition'],
	  data() {
	    return {
	      childrenOffset: 0,
	      childrenMounted: false,
	      showInfo: true,
	      showDnd: true
	    };
	  },
	  computed: {
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.nodeData.entityType);
	    },
	    nodeData() {
	      return this.departments.get(this.nodeId);
	    },
	    nodeClass() {
	      const isDragSource = this.isDraggingUser && this.nodeId === this.focusedNode;
	      return {
	        '--expanded': this.expandedNodes.includes(this.nodeId),
	        '--current-department': this.isCurrentDepartment,
	        '--focused': this.focusedNode === this.nodeId,
	        '--with-restricted-access-rights': !this.showInfo,
	        '--team': this.isTeamEntity,
	        '--drag-source': isDragSource
	      };
	    },
	    subdivisionsClass() {
	      return {
	        'humanresources-tree__node_arrow': this.hasChildren,
	        '--up': this.hasChildren && this.isExpanded,
	        '--down': this.hasChildren && !this.isExpanded,
	        '--transparent': !this.hasChildren
	      };
	    },
	    hasChildren() {
	      var _this$nodeData$childr;
	      return ((_this$nodeData$childr = this.nodeData.children) == null ? void 0 : _this$nodeData$childr.length) > 0;
	    },
	    isExpanded() {
	      return this.expandedNodes.includes(this.nodeId);
	    },
	    isCurrentDepartment() {
	      return this.currentDepartments.includes(this.nodeId);
	    },
	    head() {
	      var _this$nodeData$heads;
	      return (_this$nodeData$heads = this.nodeData.heads) == null ? void 0 : _this$nodeData$heads.filter(head => {
	        return head.role === this.memberRoles.head;
	      });
	    },
	    deputy() {
	      var _this$nodeData$heads2;
	      return (_this$nodeData$heads2 = this.nodeData.heads) == null ? void 0 : _this$nodeData$heads2.filter(head => {
	        return head.role === this.memberRoles.deputyHead;
	      });
	    },
	    employeeCount() {
	      return this.nodeData.userCount - this.nodeData.heads.length;
	    },
	    childNodeStyle() {
	      return {
	        left: `${this.childrenOffset}px`
	      };
	    },
	    showSubdivisionAddButton() {
	      return this.expandedNodes.includes(this.nodeId) || this.focusedNode === this.nodeId;
	    },
	    isTeamEntity() {
	      return this.nodeData.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    nodeDataTitle() {
	      if (!this.isCurrentDepartment) {
	        return null;
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_CURRENT_TEAM') : this.loc('HUMANRESOURCES_COMPANY_CURRENT_DEPARTMENT');
	    },
	    subdivisionsText() {
	      var _this$nodeData$childr3;
	      if (this.isTeamEntity) {
	        var _this$nodeData$childr2;
	        return (_this$nodeData$childr2 = this.nodeData.children) != null && _this$nodeData$childr2.length ? this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', this.nodeData.children.length) : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_NO_SUBDEPARTMENTS');
	      }
	      if (!((_this$nodeData$childr3 = this.nodeData.children) != null && _this$nodeData$childr3.length)) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS');
	      }
	      const childDepartmentsCount = [...this.departments.values()].filter(department => {
	        return department.entityType === humanresources_companyStructure_utils.EntityTypes.department && this.nodeData.children.includes(department.id);
	      }).length;
	      const childTeamsCount = [...this.departments.values()].filter(department => {
	        return department.entityType === humanresources_companyStructure_utils.EntityTypes.team && this.nodeData.children.includes(department.id);
	      }).length;
	      if (childTeamsCount > 0 && childDepartmentsCount > 0) {
	        return this.loc('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT_WITH_CONJUNCTION', {
	          '#DEPT_COUNT#': this.locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', childDepartmentsCount),
	          '#TEAM_COUNT#': this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', childTeamsCount)
	        });
	      }
	      if (childDepartmentsCount > 0) {
	        return this.locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', childDepartmentsCount);
	      }
	      if (childTeamsCount > 0) {
	        return this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', childTeamsCount);
	      }
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS');
	    },
	    deputyTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_DEPUTY_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_DEPUTY_TITLE');
	    },
	    dropOverlayText() {
	      const sourceNode = this.departments.get(this.focusedNode);
	      if (sourceNode.entityType === humanresources_companyStructure_utils.EntityTypes.team && this.nodeData.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_FORBIDDEN');
	      }
	      return this.isUserDropAllowed ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_APPROVED') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_NO_RIGHTS');
	    },
	    showOverlay() {
	      return this.nodeId === this.userDropTargetNodeId && this.nodeId !== this.focusedNode;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'focusedNode'])
	  },
	  watch: {
	    head() {
	      this.adaptHeight();
	    },
	    isShown() {
	      this.adaptHeight();
	    },
	    isExpanded: {
	      handler(isExpanded) {
	        if (isExpanded && !this.childrenMounted) {
	          this.childrenMounted = true;
	        }
	      },
	      immediate: true
	    }
	  },
	  created() {
	    this.width = 278;
	    this.gap = 24;
	    this.prevChildrenOffset = 0;
	    this.prevHeight = 0;
	  },
	  async mounted() {
	    let editAction = '';
	    let viewAction = '';
	    if (this.isTeamEntity) {
	      viewAction = humanresources_companyStructure_permissionChecker.PermissionActions.teamView;
	      editAction = humanresources_companyStructure_permissionChecker.PermissionActions.teamEdit;
	    } else {
	      viewAction = humanresources_companyStructure_permissionChecker.PermissionActions.structureView;
	      editAction = humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit;
	    }
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.showInfo = permissionChecker.hasPermission(viewAction, this.nodeId);
	    this.showDnd = permissionChecker.hasPermission(editAction, this.nodeId) && permissionChecker.hasPermission(editAction, this.nodeData.parentId);
	    this.$emit('calculatePosition', this.nodeId);
	    await this.$nextTick();
	    this.prevHeight = this.$el.offsetHeight;
	    main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_CONNECT, {
	      id: this.nodeId,
	      parentId: this.nodeData.parentId,
	      html: this.$el,
	      parentsPath: this.getParentsPath(this.nodeData.parentId),
	      ...this.calculateNodePoints()
	    });
	  },
	  unmounted() {
	    main_core.Dom.remove(this.$el);
	    const {
	      prevParentId
	    } = this.nodeData;
	    if (!prevParentId) {
	      return;
	    }
	    this.$emit('calculatePosition', this.nodeId);
	    main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_DISCONNECT, {
	      id: this.nodeId,
	      parentId: prevParentId
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onDepartmentClick(targetId) {
	      if (!this.showInfo) {
	        return;
	      }
	      main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_FOCUS, {
	        nodeId: this.nodeId,
	        showEmployees: targetId === 'employees',
	        subdivisionsSelected: targetId === 'subdivisions'
	      });
	    },
	    calculatePosition(nodeId) {
	      const node = this.departments.get(this.nodeId);
	      if (node.children.length === 0) {
	        this.childrenOffset = 0;
	      } else {
	        const gap = this.gap * (node.children.length - 1);
	        this.prevChildrenOffset = this.childrenOffset;
	        this.childrenOffset = (this.width - (this.width * node.children.length + gap)) / 2;
	      }
	      const offset = this.childrenOffset - this.prevChildrenOffset;
	      if (offset !== 0) {
	        main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_ADAPT_SIBLINGS, {
	          parentId: this.nodeId,
	          nodeId,
	          offset
	        });
	      }
	    },
	    locPlural(phraseCode, count) {
	      return main_core.Loc.getMessagePlural(phraseCode, count, {
	        '#COUNT#': count
	      });
	    },
	    calculateNodePoints() {
	      const {
	        left,
	        top,
	        width
	      } = this.$el.getBoundingClientRect();
	      const {
	        $el: parentNode
	      } = this.$parent.$parent;
	      const {
	        left: parentLeft,
	        top: parentTop,
	        width: parentWidth,
	        height: parentHeight
	      } = parentNode.getBoundingClientRect();
	      const {
	        x: chartX,
	        y: chartY
	      } = this.getTreeBounds();
	      return {
	        startPoint: {
	          x: (parentLeft - chartX + parentWidth / 2) / this.canvasZoom,
	          y: (parentTop - chartY + parentHeight) / this.canvasZoom
	        },
	        endPoint: {
	          x: (left - chartX + width / 2) / this.canvasZoom,
	          y: (top - chartY) / this.canvasZoom
	        }
	      };
	    },
	    getParentsPath(parentId) {
	      let topLevelId = parentId;
	      const parentsPath = [parentId];
	      while (topLevelId) {
	        const parentNode = this.departments.get(topLevelId);
	        topLevelId = parentNode.parentId;
	        if (topLevelId) {
	          parentsPath.push(topLevelId);
	        }
	      }
	      return parentsPath;
	    },
	    async adaptHeight() {
	      if (!this.isShown) {
	        return;
	      }
	      await this.$nextTick();
	      const shift = this.$el.offsetHeight - this.prevHeight;
	      if (shift !== 0) {
	        main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT, {
	          nodeId: this.nodeId,
	          shift
	        });
	        this.prevHeight = this.$el.offsetHeight;
	      }
	    },
	    onDragMouseEnter() {
	      main_core_events.EventEmitter.emit(events.HR_USER_DRAG_ENTER_NODE, {
	        nodeId: this.nodeId
	      });
	    },
	    onDragMouseLeave() {
	      main_core_events.EventEmitter.emit(events.HR_USER_DRAG_LEAVE_NODE, {
	        nodeId: this.nodeId
	      });
	    }
	  },
	  template: `
		<div
			:data-id="nodeId"
			:class="nodeClass"
			:data-title="nodeDataTitle"
			class="humanresources-tree__node"
			:style="{
				'--team-bubble-background': nodeData.teamColor?.bubbleBackground,
				'--team-focused-border-color': nodeData.teamColor?.focusedBorderColor,
				'--node-expanded-color': nodeData.teamColor?.expandedBorderColor,
			}"
		>
			<div v-if="showOverlay"
				class="humanresources-tree__node_drop-overlay"
			>
				<div class="ui-icon-set"
					 :class="{
					'--circle-plus': isUserDropAllowed,
					'--cross-circle-50': !isUserDropAllowed
				}"
				></div>
				<div class="humanresources-tree__node_drop-overlay-text">
					{{ dropOverlayText }}
				</div>
			</div>
			<div
				class="humanresources-tree__node_summary"
				@click.stop="onDepartmentClick('department')"
				@mouseenter="onDragMouseEnter"
				@mouseleave="onDragMouseLeave"
			>
				<template v-if="showInfo">
					<div
						class="humanresources-tree__node_department"
						:style="{ 'background-color': nodeData.teamColor?.treeHeadBackground}"
					>
						<div class="humanresources-tree__node_department-title">
							<div
								v-if="nodeData.parentId !== 0 && showDnd"
								class="humanresources-tree__node_dnd-icon ui-icon-set --more-points"
								:class="{ '--team': isTeamEntity }"
							>
							</div>
							<span
								v-hint
								class="humanresources-tree__node_department-title_text"
								:class="{ '--no-dnd': !showDnd }"
							>
								{{nodeData.name}}
							</span>
						</div>
						<div class="humanresources-tree__node_department-menu-icons">
							<DepartmentInfoIconButton
								v-if="isTeamEntity"
								:description="nodeData.description"
								:entityId="nodeId"
								:canvasZoom="canvasZoom"
							/>
							<DepartmentMenuButton
								v-if="head && deputy"
								:entityId="nodeId"
								:entityType="nodeData.entityType"
							></DepartmentMenuButton>
						</div>
					</div>
					<div class="humanresources-tree__node_description">
						<HeadList v-if="head"
								:entityId="nodeId"
								:items="head"
								:isTeamEntity="isTeamEntity"
						/>
						<div
							v-else
							class="humanresources-tree__node_load-skeleton --heads"
						></div>
						<div v-if="deputy" class="humanresources-tree__node_employees">
							<div>
								<p class="humanresources-tree__node_employees-title">
									{{
										isTeamEntity
											? loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_EMPLOYEES_TITLE')
											: loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_TITLE')
									}}
								</p>
								<span
									class="humanresources-tree__node_employees-count"
									@click.stop="onDepartmentClick('employees')"
								>
									{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_COUNT', employeeCount)}}
								</span>
							</div>
							<div v-if="!deputy.length"></div>
							<HeadList
								:items="deputy"
								:title="deputyTitle"
								:collapsed="true"
								:type="'deputy'"
							>
							</HeadList>
						</div>
						<div
							v-else
							class="humanresources-tree__node_load-skeleton --deputies"
						></div>
					</div>
					<div
						class="humanresources-tree__node_subdivisions"
						:class="subdivisionsClass"
						@click.stop="onDepartmentClick('subdivisions')"
					>
						<span>{{ subdivisionsText }}</span>
					</div>
				</template>
				<svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" class="humanresources-tree__node_lock">
					<path d="M12.0646 4.231C13.9529 4.231 15.4836 5.76968 15.4836 7.66775V10.5612H17.2905V7.66775C17.2905 4.76657 14.9507 2.41476 12.0645 2.41476C9.17827 2.41476 6.83847 4.76657 6.83847 7.66775V10.5612H8.64544V7.66775C8.64544 5.76968 10.1762 4.231 12.0646 4.231Z" fill="#D5D7DB"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M7.14721 10.3237C6.12854 10.3237 5.30273 11.1495 5.30273 12.1682V18.506C5.30273 19.5246 6.12854 20.3504 7.14722 20.3504H17.1029C18.1216 20.3504 18.9474 19.5246 18.9474 18.506V12.1682C18.9474 11.1495 18.1216 10.3237 17.1029 10.3237H7.14721ZM12.9216 15.5869C12.9216 15.5685 12.93 15.5512 12.944 15.5392C13.2142 15.3085 13.3859 14.9643 13.3859 14.5797C13.3859 13.8847 12.8259 13.3214 12.1353 13.3214C11.4446 13.3214 10.8846 13.8847 10.8846 14.5797C10.8846 14.9643 11.0563 15.3085 11.3266 15.5392C11.3406 15.5512 11.3489 15.5685 11.3489 15.5869V16.7572C11.3489 17.1915 11.701 17.5435 12.1353 17.5435C12.5696 17.5435 12.9216 17.1915 12.9216 16.7572V15.5869Z" fill="#D5D7DB"/>
				</svg>
				<SubdivisionAddButton
					v-if="showSubdivisionAddButton"
					:entityId="nodeId"
					:entityType="nodeData.entityType"
					@click.stop
				></SubdivisionAddButton>
			</div>
			<div
				v-if="nodeData.parentId === 0 && !hasChildren"
				class="humanresources-tree__node_empty-skeleton"
			></div>
			<div
				v-if="hasChildren"
				ref="childrenNode"
				class="humanresources-tree__node_children"
				:style="childNodeStyle"
			>
				<TransitionGroup>
					<treeNode
						v-for="id in nodeData.children"
						v-if="isExpanded || childrenMounted"
						v-show="isExpanded"
						:ref="'node-' + id"
						:key="id"
						:nodeId="id"
						:expandedNodes="expandedNodes"
						:canvasZoom="canvasZoom"
						:currentDepartments="currentDepartments"
						:isShown="isExpanded"
						@calculatePosition="calculatePosition"
						:userDropTargetNodeId="userDropTargetNodeId"
						:isUserDropAllowed="isUserDropAllowed"
						:isDraggingUser="isDraggingUser"
					/>
				</TransitionGroup>
			</div>
		</div>
	`
	};

	// @vue/component
	const Connectors = {
	  name: 'companyTreeConnectors',
	  expose: ['toggleConnectorsVisibility', 'toggleConnectorHighlighting', 'toggleAllConnectorsVisibility', 'adaptConnectorsAfterReorder'],
	  props: {
	    isLocatedDepartmentVisible: {
	      type: Boolean,
	      required: true
	    },
	    /** @type Map */
	    treeNodes: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      connectors: {}
	    };
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments'])
	  },
	  created() {
	    this.subscribeOnEvents();
	    this.prevWindowWidth = window.innerWidth;
	  },
	  beforeUnmount() {
	    this.unsubscribeFromEvents();
	  },
	  methods: {
	    onAddConnector({
	      data
	    }) {
	      var _this$connectors;
	      const {
	        id,
	        parentId
	      } = data;
	      if (!parentId) {
	        return;
	      }
	      const connector = (_this$connectors = this.connectors[`${parentId}-${id}`]) != null ? _this$connectors : {};
	      Object.assign(connector, data);
	      if (connector.highlighted) {
	        delete this.connectors[`${parentId}-${id}`];
	      }
	      this.connectors[`${parentId}-${id}`] = {
	        show: true,
	        highlighted: false,
	        ...connector
	      };
	    },
	    onRemoveConnector({
	      data
	    }) {
	      const {
	        parentId,
	        id
	      } = data;
	      delete this.connectors[`${parentId}-${id}`];
	    },
	    onAdaptSiblingConnectors({
	      data
	    }) {
	      const {
	        nodeId,
	        parentId,
	        offset
	      } = data;
	      const parentDepartment = this.departments.get(parentId);
	      const isMounted = parentDepartment.children.includes(nodeId);
	      const values = Object.values(this.connectors);
	      const parsedSiblingConnectors = values.reduce((acc, connector) => {
	        const {
	          endPoint: currentEndPoint,
	          id: currentId,
	          parentId: currentParentId
	        } = connector;
	        if (!currentId || currentParentId !== parentId || currentId === nodeId) {
	          return acc;
	        }
	        let sign = 0;
	        if (isMounted) {
	          sign = parentDepartment.children.indexOf(nodeId) > parentDepartment.children.indexOf(currentId) ? 1 : -1;
	        } else {
	          const {
	            endPoint
	          } = this.connectors[`${parentId}-${nodeId}`];
	          sign = endPoint.x > currentEndPoint.x ? 1 : -1;
	        }
	        return {
	          ...acc,
	          [currentId]: sign
	        };
	      }, {});
	      values.forEach(connector => {
	        const {
	          id: currentId,
	          parentId: currentParentId,
	          parentsPath,
	          endPoint: currentEndPoint,
	          startPoint: currentStartPoint
	        } = connector;
	        if (!currentId || currentId === nodeId) {
	          return;
	        }
	        if (currentParentId === parentId) {
	          const {
	            x
	          } = currentEndPoint;
	          const sign = parsedSiblingConnectors[currentId];
	          Object.assign(currentEndPoint, {
	            x: x + offset * sign
	          });
	          return;
	        }
	        const ancestorId = parentsPath == null ? void 0 : parentsPath.find(id => {
	          return Boolean(parsedSiblingConnectors[id]);
	        });
	        if (ancestorId) {
	          const ancestorSign = parsedSiblingConnectors[ancestorId];
	          Object.assign(currentStartPoint, {
	            x: currentStartPoint.x + offset * ancestorSign
	          });
	          Object.assign(currentEndPoint, {
	            x: currentEndPoint.x + offset * ancestorSign
	          });
	        }
	      });
	    },
	    adaptConnectorsAfterReorder(ids, shift, isRoot) {
	      ids.forEach(departmentId => {
	        const {
	          parentId,
	          children
	        } = this.departments.get(departmentId);
	        const connector = this.connectors[`${parentId}-${departmentId}`];
	        if (!connector) {
	          return;
	        }
	        Object.assign(connector.endPoint, {
	          x: connector.endPoint.x + shift
	        });
	        if (!isRoot) {
	          Object.assign(connector.startPoint, {
	            x: connector.startPoint.x + shift
	          });
	        }
	        if ((children == null ? void 0 : children.length) > 0) {
	          this.adaptConnectorsAfterReorder(children, shift, false);
	        }
	      });
	    },
	    onAdaptConnectorHeight({
	      data
	    }) {
	      const {
	        shift,
	        nodeId
	      } = data;
	      Object.values(this.connectors).forEach(connector => {
	        if (connector.parentId === nodeId) {
	          Object.assign(connector.startPoint, {
	            y: connector.startPoint.y + shift
	          });
	        }
	      });
	    },
	    toggleConnectorsVisibility(parentId, show) {
	      const {
	        children
	      } = this.departments.get(parentId);
	      children.forEach(childId => {
	        var _this$connectors2;
	        const connector = (_this$connectors2 = this.connectors[`${parentId}-${childId}`]) != null ? _this$connectors2 : {};
	        this.connectors = {
	          ...this.connectors,
	          [`${parentId}-${childId}`]: {
	            ...connector,
	            show
	          }
	        };
	      });
	    },
	    toggleAllConnectorsVisibility(shouldShow, expandedNodes) {
	      Object.keys(this.connectors).forEach(key => {
	        const connector = this.connectors[key];
	        if (!shouldShow || expandedNodes.includes(connector.parentId) && shouldShow) {
	          connector.show = shouldShow;
	        }
	      });
	    },
	    toggleConnectorHighlighting(nodeId, expanded) {
	      var _this$connectors3;
	      const {
	        parentId
	      } = this.departments.get(nodeId);
	      if (!parentId) {
	        return;
	      }
	      if (!expanded) {
	        this.connectors[`${parentId}-${nodeId}`] = {
	          ...this.connectors[`${parentId}-${nodeId}`],
	          highlighted: false
	        };
	        return;
	      }
	      const highlightedConnector = (_this$connectors3 = this.connectors[`${parentId}-${nodeId}`]) != null ? _this$connectors3 : {};
	      delete this.connectors[`${parentId}-${nodeId}`];
	      this.connectors = {
	        ...this.connectors,
	        [`${parentId}-${nodeId}`]: {
	          ...highlightedConnector,
	          highlighted: true
	        }
	      };
	    },
	    getPath(id) {
	      const connector = this.connectors[id];
	      const {
	        startPoint,
	        endPoint,
	        parentId
	      } = connector;
	      if (!startPoint || !endPoint) {
	        return '';
	      }
	      let verticalLineHeight = 115;
	      const parentNode = this.treeNodes.get(parentId);
	      const parentNodeStyle = getComputedStyle(parentNode);
	      const minDepartmentHeight = parseInt(parentNodeStyle.getPropertyValue('--min-height'), 10);
	      const diffHeight = parentNode.offsetHeight - minDepartmentHeight;
	      verticalLineHeight = diffHeight > 0 ? verticalLineHeight - diffHeight : verticalLineHeight;
	      const shiftY = 1;
	      const startY = startPoint.y - shiftY;
	      const shadowOffset = this.focusedNode === connector.id ? 12 : 0;
	      const rounded = {
	        start: '',
	        end: ''
	      };
	      let arcRadius = 0;
	      if (Math.round(startPoint.x) > Math.round(endPoint.x)) {
	        arcRadius = 15;
	        rounded.start = 'a15,15 0 0 1 -15,15';
	        rounded.end = 'a15,15 0 0 0 -15,15';
	      } else if (Math.round(startPoint.x) < Math.round(endPoint.x)) {
	        arcRadius = -15;
	        rounded.start = 'a15,15 0 0 0 15,15';
	        rounded.end = 'a15,15 0 0 1 15,15';
	      }
	      const adjustedEndY = endPoint.y - shadowOffset;
	      return [`M${startPoint.x} ${startY}`, `V${startY + verticalLineHeight}`, `${String(rounded.start)}`, `H${endPoint.x + arcRadius}`, `${String(rounded.end)}`, `V${adjustedEndY}`].join('');
	    },
	    subscribeOnEvents() {
	      this.events = {
	        [events.HR_DEPARTMENT_CONNECT]: this.onAddConnector,
	        [events.HR_DEPARTMENT_DISCONNECT]: this.onRemoveConnector,
	        [events.HR_DEPARTMENT_ADAPT_SIBLINGS]: this.onAdaptSiblingConnectors,
	        [events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT]: this.onAdaptConnectorHeight
	      };
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.subscribe(event, handle);
	      });
	      main_core.Event.bind(window, 'resize', this.onResizeWindow);
	    },
	    unsubscribeFromEvents() {
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.unsubscribe(event, handle);
	      });
	      main_core.Event.unbind(window, 'resize', this.onResizeWindow);
	    },
	    onResizeWindow() {
	      const offset = (window.innerWidth - this.prevWindowWidth) / 2;
	      this.prevWindowWidth = window.innerWidth;
	      if (offset === 0) {
	        return;
	      }
	      Object.keys(this.connectors).forEach(key => {
	        const connector = this.connectors[key];
	        if (connector.startPoint && connector.endPoint) {
	          const startPointX = connector.startPoint.x;
	          const endPointX = connector.endPoint.x;
	          Object.assign(connector.startPoint, {
	            x: startPointX + offset
	          });
	          Object.assign(connector.endPoint, {
	            x: endPointX + offset
	          });
	        }
	      });
	    }
	  },
	  template: `
		<svg class="humanresources-tree__connectors" fill="none">
			<marker
				id='arrow'
				markerUnits='userSpaceOnUse'
				markerWidth='20'
				markerHeight='12'
				refX='10'
				refY='10.5'
			>
				<path d="M1 1L10 10L19 1" class="--highlighted" />
			</marker>
			<path
				v-for="(connector, id) in connectors"
				v-show="connector.show"
				:ref="id"
				:marker-end="connector.highlighted ? 'url(#arrow)' : null"
				:class="{ '--highlighted': connector.highlighted }"
				:id="id"
				:d="getPath(id)"
			></path>
		</svg>
	`
	};

	let _ = t => t,
	  _t;
	const FULL_WIDTH = 302;
	const CANVAS_MOVE_SPEED = 15;
	var _prevAffectedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prevAffectedItems");
	var _targetData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetData");
	var _transform = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("transform");
	var _tree = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tree");
	var _draggedItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedItem");
	var _ghost = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ghost");
	var _positionPointer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("positionPointer");
	var _zoom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("zoom");
	var _canvas = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canvas");
	var _prevPageX = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prevPageX");
	var _prevPageY = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prevPageY");
	var _targetTeams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetTeams");
	var _permissionChecker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("permissionChecker");
	var _draggedEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedEntityType");
	var _mouseMoveHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mouseMoveHandler");
	var _mouseUpHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mouseUpHandler");
	var _mouseWheelHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mouseWheelHandler");
	var _onMouseDown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseDown");
	var _onMouseMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseMove");
	var _onMouseUp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseUp");
	var _onMouseWheel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseWheel");
	var _createGhost = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createGhost");
	var _createPositionPointer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPositionPointer");
	var _setTransform = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTransform");
	var _setPositionPointerTransform = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setPositionPointerTransform");
	var _getTargetData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTargetData");
	var _reorderEntity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reorderEntity");
	var _changeParentWithReorder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeParentWithReorder");
	var _checkForDraggedOverflow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkForDraggedOverflow");
	var _setStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setStyles");
	var _resetTeamsBlur = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetTeamsBlur");
	class TreeNodeDragController {
	  constructor(el) {
	    Object.defineProperty(this, _resetTeamsBlur, {
	      value: _resetTeamsBlur2
	    });
	    Object.defineProperty(this, _setStyles, {
	      value: _setStyles2
	    });
	    Object.defineProperty(this, _checkForDraggedOverflow, {
	      value: _checkForDraggedOverflow2
	    });
	    Object.defineProperty(this, _changeParentWithReorder, {
	      value: _changeParentWithReorder2
	    });
	    Object.defineProperty(this, _reorderEntity, {
	      value: _reorderEntity2
	    });
	    Object.defineProperty(this, _getTargetData, {
	      value: _getTargetData2
	    });
	    Object.defineProperty(this, _setPositionPointerTransform, {
	      value: _setPositionPointerTransform2
	    });
	    Object.defineProperty(this, _setTransform, {
	      value: _setTransform2
	    });
	    Object.defineProperty(this, _createPositionPointer, {
	      value: _createPositionPointer2
	    });
	    Object.defineProperty(this, _createGhost, {
	      value: _createGhost2
	    });
	    Object.defineProperty(this, _onMouseWheel, {
	      value: _onMouseWheel2
	    });
	    Object.defineProperty(this, _onMouseUp, {
	      value: _onMouseUp2
	    });
	    Object.defineProperty(this, _onMouseMove, {
	      value: _onMouseMove2
	    });
	    Object.defineProperty(this, _onMouseDown, {
	      value: _onMouseDown2
	    });
	    Object.defineProperty(this, _prevAffectedItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _targetData, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _transform, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tree, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _draggedItem, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _ghost, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _positionPointer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _zoom, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _canvas, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _prevPageX, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _prevPageY, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _targetTeams, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _permissionChecker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _draggedEntityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mouseMoveHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mouseUpHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mouseWheelHandler, {
	      writable: true,
	      value: void 0
	    });
	    main_core.Event.bind(el, 'mousedown', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onMouseDown)[_onMouseDown](event);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _mouseMoveHandler)[_mouseMoveHandler] = event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onMouseMove)[_onMouseMove](event);
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _mouseUpHandler)[_mouseUpHandler] = event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onMouseUp)[_onMouseUp](event);
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _mouseWheelHandler)[_mouseWheelHandler] = event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _onMouseWheel)[_onMouseWheel](event);
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _permissionChecker)[_permissionChecker] = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	  }
	}
	function _onMouseDown2({
	  target,
	  currentTarget: tree,
	  pageX,
	  pageY
	}) {
	  if (!main_core.Dom.hasClass(target, 'humanresources-tree__node_dnd-icon')) {
	    return;
	  }
	  event.stopPropagation();
	  babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree] = tree;
	  babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom] = Number(tree.dataset.zoom);
	  babelHelpers.classPrivateFieldLooseBase(this, _canvas)[_canvas] = {
	    x: Number(tree.dataset.x),
	    y: Number(tree.dataset.y)
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem] = target.closest('.humanresources-tree__node');
	  babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost] = babelHelpers.classPrivateFieldLooseBase(this, _createGhost)[_createGhost]();
	  babelHelpers.classPrivateFieldLooseBase(this, _prevPageX)[_prevPageX] = pageX;
	  babelHelpers.classPrivateFieldLooseBase(this, _prevPageY)[_prevPageY] = pageY;
	  babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer] = babelHelpers.classPrivateFieldLooseBase(this, _createPositionPointer)[_createPositionPointer]();
	  babelHelpers.classPrivateFieldLooseBase(this, _targetTeams)[_targetTeams] = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems] = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform] = {
	    x: 0,
	    y: 0,
	    prevX: 0,
	    prevY: 0
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _targetData)[_targetData] = {};
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedEntityType)[_draggedEntityType] = main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem], '--team') ? humanresources_companyStructure_utils.EntityTypes.team : humanresources_companyStructure_utils.EntityTypes.department;
	  main_core.Dom.addClass(tree, '--drag-progress');
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement.parentElement, '--blur');
	  babelHelpers.classPrivateFieldLooseBase(this, _setPositionPointerTransform)[_setPositionPointerTransform](babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]);
	  babelHelpers.classPrivateFieldLooseBase(this, _setStyles)[_setStyles]();
	  main_core.Event.bind(document, 'mousemove', babelHelpers.classPrivateFieldLooseBase(this, _mouseMoveHandler)[_mouseMoveHandler]);
	  main_core.Event.bind(document, 'mouseup', babelHelpers.classPrivateFieldLooseBase(this, _mouseUpHandler)[_mouseUpHandler]);
	  main_core.Event.bind(document, 'wheel', babelHelpers.classPrivateFieldLooseBase(this, _mouseWheelHandler)[_mouseWheelHandler]);
	  main_core_events.EventEmitter.emit(events.HR_ENTITY_TOGGLE_ELEMENTS, {
	    shouldShowElements: false
	  });
	  const draggedId = Number(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].dataset.id);
	  main_core_events.EventEmitter.emit(events.HR_DRAG_ENTITY, {
	    draggedId
	  });
	}
	function _onMouseMove2({
	  pageX,
	  pageY
	}) {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem], '--hidden');
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].parentElement, '--disabled-transition');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], ['--inside', '--no-permission']);
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], 'height', `${babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].offsetHeight}px`);
	  babelHelpers.classPrivateFieldLooseBase(this, _resetTeamsBlur)[_resetTeamsBlur]();
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevX = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x;
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevY = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y;
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x += (pageX - babelHelpers.classPrivateFieldLooseBase(this, _prevPageX)[_prevPageX]) / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y += (pageY - babelHelpers.classPrivateFieldLooseBase(this, _prevPageY)[_prevPageY]) / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  babelHelpers.classPrivateFieldLooseBase(this, _prevPageX)[_prevPageX] = pageX;
	  babelHelpers.classPrivateFieldLooseBase(this, _prevPageY)[_prevPageY] = pageY;
	  babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems].forEach(affectedItem => babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](affectedItem, 0, 0));
	  babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems] = [];
	  const {
	    directionX,
	    directionY
	  } = babelHelpers.classPrivateFieldLooseBase(this, _checkForDraggedOverflow)[_checkForDraggedOverflow]();
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x = directionX === 0 ? babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x : babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x + directionX * CANVAS_MOVE_SPEED / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y = directionY === 0 ? babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y : babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y + directionY * CANVAS_MOVE_SPEED / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x, babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y, 4);
	  babelHelpers.classPrivateFieldLooseBase(this, _setPositionPointerTransform)[_setPositionPointerTransform](babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]);
	  babelHelpers.classPrivateFieldLooseBase(this, _targetData)[_targetData] = babelHelpers.classPrivateFieldLooseBase(this, _getTargetData)[_getTargetData]();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _targetData)[_targetData].insertion) {
	    return;
	  }
	  const {
	    insertion,
	    targetItem,
	    hasPermission
	  } = babelHelpers.classPrivateFieldLooseBase(this, _targetData)[_targetData];
	  if (!hasPermission) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], '--no-permission');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setPositionPointerTransform)[_setPositionPointerTransform](targetItem);
	  switch (insertion) {
	    case 'reorder':
	      {
	        const affectedItems = babelHelpers.classPrivateFieldLooseBase(this, _reorderEntity)[_reorderEntity](targetItem);
	        babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems] = affectedItems;
	        break;
	      }
	    case 'sibling-left':
	    case 'sibling-right':
	      {
	        const affectedItems = babelHelpers.classPrivateFieldLooseBase(this, _changeParentWithReorder)[_changeParentWithReorder](targetItem, insertion);
	        babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems] = affectedItems;
	        break;
	      }
	    default:
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], '--inside');
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], 'height', `${targetItem.offsetHeight}px`);
	  }
	}
	function _onMouseUp2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _setStyles)[_setStyles](true);
	  main_core.Event.unbind(document, 'mousemove', babelHelpers.classPrivateFieldLooseBase(this, _mouseMoveHandler)[_mouseMoveHandler]);
	  main_core.Event.unbind(document, 'mouseup', babelHelpers.classPrivateFieldLooseBase(this, _mouseUpHandler)[_mouseUpHandler]);
	  main_core.Event.unbind(document, 'mouseup', babelHelpers.classPrivateFieldLooseBase(this, _mouseWheelHandler)[_mouseWheelHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems].forEach(item => babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](item, 0, 0));
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree], '--drag-progress');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement.parentElement, '--blur');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem], '--hidden');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].parentElement, '--disabled-transition');
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost]);
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer]);
	  babelHelpers.classPrivateFieldLooseBase(this, _resetTeamsBlur)[_resetTeamsBlur]();
	  main_core_events.EventEmitter.emit(events.HR_ENTITY_TOGGLE_ELEMENTS, {
	    shouldShowElements: true
	  });
	  const {
	    insertion,
	    targetItem,
	    hasPermission
	  } = babelHelpers.classPrivateFieldLooseBase(this, _targetData)[_targetData];
	  if (!insertion || !hasPermission) {
	    return;
	  }
	  const draggedIndex = [...babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement.children].indexOf(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]);
	  const targetIndex = [...targetItem.parentElement.children].indexOf(targetItem);
	  const draggedId = Number(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].dataset.id);
	  main_core_events.EventEmitter.emit(events.HR_DROP_ENTITY, {
	    draggedId,
	    targetId: Number(targetItem.dataset.id),
	    targetIndex,
	    affectedItems: babelHelpers.classPrivateFieldLooseBase(this, _prevAffectedItems)[_prevAffectedItems].map(item => Number(item.dataset.id)),
	    direction: draggedIndex < targetIndex ? 1 : -1,
	    insertion
	  });
	}
	function _onMouseWheel2({
	  shiftKey
	}) {
	  if (shiftKey) {
	    const currentCanvasX = Number(babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].dataset.x);
	    const movementX = currentCanvasX - babelHelpers.classPrivateFieldLooseBase(this, _canvas)[_canvas].x;
	    babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x -= movementX / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    babelHelpers.classPrivateFieldLooseBase(this, _canvas)[_canvas].x = currentCanvasX;
	  } else {
	    const currentCanvasY = Number(babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].dataset.y);
	    const movementY = currentCanvasY - babelHelpers.classPrivateFieldLooseBase(this, _canvas)[_canvas].y;
	    babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y -= movementY / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    babelHelpers.classPrivateFieldLooseBase(this, _canvas)[_canvas].y = currentCanvasY;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x, babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y, 4);
	}
	function _createGhost2() {
	  const ghost = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].cloneNode(true);
	  main_core.Dom.addClass(ghost, 'humanresources-tree__dnd-ghost');
	  main_core.Dom.removeClass(ghost, ['--expanded', '--focused']);
	  const {
	    x: treeX,
	    y: treeY
	  } = babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].getBoundingClientRect();
	  const {
	    x: draggedX,
	    y: draggedY
	  } = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].getBoundingClientRect();
	  const left = draggedX - treeX;
	  const top = draggedY - treeY;
	  main_core.Dom.style(ghost, 'left', `${left / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom]}px`);
	  main_core.Dom.style(ghost, 'top', `${top / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom]}px`);
	  main_core.Dom.append(ghost, babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree]);
	  return ghost;
	}
	function _createPositionPointer2() {
	  const {
	    offsetWidth,
	    offsetHeight
	  } = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem];
	  const text = main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem], '--team') ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DRAG_TEAM_LABEL') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DRAG_DEPARTMENT_LABEL');
	  const positionPointer = main_core.Tag.render(_t || (_t = _`
			<div
				class="humanresources-tree__position-pointer"
				style="width: ${0}px; height: ${0}px;);"
			>
				<div>
					<div class="ui-icon-set --circle-plus"></div>
					<span>${0}</span>
				</div>
				<div>
					<div class="ui-icon-set --cross-circle-50"></div>
					<span>
						${0}
					</span>
				</div>
			</div>
		`), offsetWidth, offsetHeight, text, main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NO_DRAG_PERMISSION'));
	  main_core.Dom.append(positionPointer, babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree]);
	  babelHelpers.classPrivateFieldLooseBase(this, _setPositionPointerTransform)[_setPositionPointerTransform](babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]);
	  return positionPointer;
	}
	function _setTransform2(element, x, y, rotation = 0) {
	  if (x === 0 && y === 0) {
	    main_core.Dom.style(element, 'transform', '');
	    return;
	  }
	  main_core.Dom.style(element, 'transform', `translate3d(${x}px, ${y}px, 0) rotate(${rotation}deg)`);
	}
	function _setPositionPointerTransform2(node) {
	  const {
	    x: treeX,
	    y: treeY
	  } = babelHelpers.classPrivateFieldLooseBase(this, _tree)[_tree].getBoundingClientRect();
	  const {
	    x: nodeX,
	    y: nodeY
	  } = node.getBoundingClientRect();
	  const x = (nodeX - treeX) / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  const y = (nodeY - treeY) / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](babelHelpers.classPrivateFieldLooseBase(this, _positionPointer)[_positionPointer], x, y);
	}
	function _getTargetData2() {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], '--disabled-events');
	  babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x, babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y);
	  const reorderThreshold = 0.7;
	  const changeParentWithReorderThreshold = 0.6;
	  const {
	    x,
	    y,
	    width: draggedWidth,
	    height: draggedHeight
	  } = babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost].getBoundingClientRect();
	  babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x, babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y, 4);
	  const points = [{
	    x,
	    y
	  }, {
	    x: x + draggedWidth,
	    y
	  }, {
	    x,
	    y: y + draggedHeight
	  }, {
	    x: x + draggedWidth,
	    y: y + draggedHeight
	  }, {
	    x,
	    y: y + draggedHeight / 2
	  }, {
	    x: x + draggedWidth,
	    y: y + draggedHeight / 2
	  }];
	  const targetData = points.reduce((acc, point, i) => {
	    if (acc.insertion) {
	      return acc;
	    }
	    const belowItem = document.elementFromPoint(point.x, point.y);
	    const targetItemSummary = belowItem == null ? void 0 : belowItem.closest('.humanresources-tree__node_summary');
	    const targetItem = targetItemSummary == null ? void 0 : targetItemSummary.parentElement;
	    if (!targetItem || targetItem === babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]) {
	      return acc;
	    }
	    const {
	      x: targetX,
	      y: targetY,
	      width: targetWidth,
	      height: targetHeight
	    } = targetItem.getBoundingClientRect();
	    const sameParent = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement === targetItem.parentElement;
	    const hasSortPermission = babelHelpers.classPrivateFieldLooseBase(this, _permissionChecker)[_permissionChecker].canSortEntitiesByParentId(Number(targetItem.parentElement.parentElement.dataset.id));
	    const allowSort = i === 0 && !main_core.Dom.hasClass(targetItem, '--root') && hasSortPermission;
	    acc.targetItem = targetItem;
	    if (sameParent && x + reorderThreshold * draggedWidth < targetX + targetWidth && allowSort) {
	      return {
	        ...acc,
	        insertion: 'reorder'
	      };
	    }
	    if (!sameParent && y + changeParentWithReorderThreshold * draggedHeight < targetY + targetHeight && allowSort) {
	      return {
	        ...acc,
	        insertion: x < targetX + targetWidth / 2 ? 'sibling-left' : 'sibling-right'
	      };
	    }
	    const isDepartmentInsertToTeam = main_core.Dom.hasClass(targetItem, '--team') && !main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem], '--team');
	    const isInsertToParent = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement.parentElement === targetItem;
	    if (isDepartmentInsertToTeam || isInsertToParent) {
	      if (isDepartmentInsertToTeam) {
	        main_core.Dom.addClass(targetItem, '--blur');
	        babelHelpers.classPrivateFieldLooseBase(this, _targetTeams)[_targetTeams].push(targetItem);
	      }
	      return acc;
	    }
	    return {
	      ...acc,
	      insertion: 'inside',
	      hasPermission: babelHelpers.classPrivateFieldLooseBase(this, _permissionChecker)[_permissionChecker].canBeParentForEntity(Number(targetItem.dataset.id), babelHelpers.classPrivateFieldLooseBase(this, _draggedEntityType)[_draggedEntityType])
	    };
	  }, {
	    hasPermission: true
	  });
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ghost)[_ghost], '--disabled-events');
	  return targetData;
	}
	function _reorderEntity2(targetItem) {
	  const children = [...babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].parentElement.children];
	  const draggedIndex = children.indexOf(babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem]);
	  const targetIndex = [...targetItem.parentElement.children].indexOf(targetItem);
	  const direction = draggedIndex < targetIndex ? 1 : -1;
	  const affectedItems = direction > 0 ? children.slice(draggedIndex + 1, targetIndex + 1) : children.slice(targetIndex, draggedIndex);
	  affectedItems.forEach(affectedItem => babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](affectedItem, -direction * FULL_WIDTH, 0));
	  return affectedItems;
	}
	function _changeParentWithReorder2(targetItem, insertion) {
	  const children = [...targetItem.parentElement.children];
	  const targetIndex = children.indexOf(targetItem);
	  let affectedItems = [];
	  if (insertion === 'sibling-right') {
	    affectedItems = children.slice(0, targetIndex + 1);
	    affectedItems.forEach(affectedItem => babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](affectedItem, -FULL_WIDTH, 0));
	  } else {
	    affectedItems = children.slice(targetIndex);
	    affectedItems.forEach(affectedItem => babelHelpers.classPrivateFieldLooseBase(this, _setTransform)[_setTransform](affectedItem, FULL_WIDTH, 0));
	  }
	  return affectedItems;
	}
	function _checkForDraggedOverflow2() {
	  let directionX = 0;
	  let directionY = 0;
	  const {
	    left,
	    top
	  } = babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].getBoundingClientRect();
	  const zoomedLeft = left / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  const zoomedTop = top / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	  if (zoomedLeft + babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x < 0) {
	    directionX = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x < babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevX ? -1 : 0;
	  }
	  if (zoomedLeft + babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x + babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].offsetWidth > document.body.offsetWidth / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom]) {
	    directionX = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].x > babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevX ? 1 : 0;
	  }
	  if (zoomedTop + babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y < 0) {
	    directionY = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y < babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevY ? -1 : 0;
	  }
	  if (zoomedTop + babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y + babelHelpers.classPrivateFieldLooseBase(this, _draggedItem)[_draggedItem].offsetHeight > document.body.offsetHeight / babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom]) {
	    directionY = babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].y > babelHelpers.classPrivateFieldLooseBase(this, _transform)[_transform].prevY ? 1 : 0;
	  }
	  if (directionX !== 0 || directionY !== 0) {
	    main_core_events.EventEmitter.emit(events.HR_ORG_CHART_TRANSFORM_CANVAS, {
	      directionX,
	      directionY,
	      speed: CANVAS_MOVE_SPEED
	    });
	  }
	  return {
	    directionX,
	    directionY
	  };
	}
	function _setStyles2(shouldReset) {
	  main_core.Dom.style(document.body, 'userSelect', 'none');
	  main_core.Dom.style(document.body, '-webkit-user-select', 'none');
	  main_core.Dom.style(document.body, 'cursor', 'grabbing');
	  if (shouldReset) {
	    main_core.Dom.style(document.body, 'userSelect', '');
	    main_core.Dom.style(document.body, '-webkit-user-select', '');
	    main_core.Dom.style(document.body, 'cursor', '');
	  }
	}
	function _resetTeamsBlur2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _targetTeams)[_targetTeams].forEach(team => main_core.Dom.removeClass(team, '--blur'));
	}

	const DragAndDrop = {
	  mounted(el) {
	    new TreeNodeDragController(el);
	  },
	  updated(el, {
	    value
	  }) {
	    const {
	      x,
	      y,
	      zoom
	    } = value;
	    el.setAttribute('data-zoom', zoom);
	    el.setAttribute('data-x', x);
	    el.setAttribute('data-y', y);
	  }
	};

	// @vue/component
	const Tree = {
	  name: 'companyTree',
	  components: {
	    TreeNode,
	    Connectors,
	    MoveEmployeeConfirmationPopup: humanresources_companyStructure_structureComponents.MoveEmployeeConfirmationPopup,
	    ConfirmationPopup: humanresources_companyStructure_structureComponents.ConfirmationPopup
	  },
	  directives: {
	    dnd: DragAndDrop
	  },
	  provide() {
	    return {
	      getTreeBounds: () => this.getTreeBounds()
	    };
	  },
	  props: {
	    canvasTransform: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['moveTo', 'showWizard', 'controlDetail'],
	  data() {
	    return {
	      expandedNodes: [],
	      isLocatedDepartmentVisible: false,
	      isLoaded: false,
	      userDropTargetNodeId: null,
	      isUserDropAllowed: false,
	      draggedUserData: null,
	      showDndConfirmationPopup: false,
	      showDndErrorPopup: false,
	      dndErrorPopupDescription: '',
	      dndUserMoveContext: null,
	      isCombineOnly: false
	    };
	  },
	  computed: {
	    dndTargetEntityType() {
	      return this.targetDepartment.entityType || '';
	    },
	    dndSourceEntityType() {
	      var _this$departments$get;
	      return ((_this$departments$get = this.departments.get(this.focusedNode)) == null ? void 0 : _this$departments$get.entityType) || '';
	    },
	    rootId() {
	      const {
	        id: rootId
	      } = [...this.departments.values()].find(department => {
	        return department.parentId === 0;
	      });
	      return rootId;
	    },
	    connectors() {
	      return this.$refs.connectors;
	    },
	    targetDepartment() {
	      var _this$dndUserMoveCont;
	      if (!((_this$dndUserMoveCont = this.dndUserMoveContext) != null && _this$dndUserMoveCont.targetDepartmentId)) {
	        return null;
	      }
	      return this.departments.get(this.dndUserMoveContext.targetDepartmentId);
	    },
	    isTeamTarget() {
	      var _this$targetDepartmen;
	      return ((_this$targetDepartmen = this.targetDepartment) == null ? void 0 : _this$targetDepartmen.entityType) === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    dndPopupDescription() {
	      const phrase = this.isTeamTarget ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_DESC_ROLE_TEAM' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_DESC_ROLE_DEPARTMENT';
	      return main_core.Loc.getMessage(phrase, {
	        '#USER_NAME#': main_core.Text.encode(this.dndUserMoveContext.user.name),
	        '#DEPARTMENT_NAME#': main_core.Text.encode(this.targetDepartment.name),
	        '[link]': `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.dndUserMoveContext.user.url}">`,
	        '[/link]': '</a>'
	      });
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['currentDepartments', 'userId', 'focusedNode', 'departments'])
	  },
	  created() {
	    this.treeNodes = new Map();
	    this.subscribeOnEvents();
	    this.loadHeads([this.rootId]);
	  },
	  mounted() {
	    const departmentToFocus = this.getDepartmentIdForInitialFocus();
	    this.currentDepartmentsLocated = [departmentToFocus];
	    if (departmentToFocus !== this.rootId) {
	      this.expandDepartmentParents(departmentToFocus);
	      this.focus(departmentToFocus, {
	        expandAfterFocus: true
	      });
	      return;
	    }
	    this.expandLowerDepartments();
	    this.focus(departmentToFocus);
	  },
	  beforeUnmount() {
	    this.unsubscribeFromEvents();
	  },
	  methods: {
	    getDepartmentIdForInitialFocus() {
	      const providedFocusNodeId = UrlProvidedParamsService.getParams().focusNodeId;
	      if (providedFocusNodeId) {
	        const node = this.departments.get(providedFocusNodeId);
	        if (node) {
	          return providedFocusNodeId;
	        }
	      }
	      for (const currentDepartmentId of this.currentDepartments) {
	        const node = this.departments.get(currentDepartmentId);
	        if (node.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	          return currentDepartmentId;
	        }
	      }
	      return this.rootId;
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    getTreeBounds() {
	      return this.$el.getBoundingClientRect();
	    },
	    onConnectDepartment({
	      data
	    }) {
	      const {
	        id,
	        html
	      } = data;
	      this.treeNodes.set(id, html);
	      const departmentIdToFocus = this.getDepartmentIdForInitialFocus();
	      if (id === departmentIdToFocus && !this.isLoaded)
	        // zoom to  department when loading
	        {
	          const movingDelay = 1800;
	          this.isLoaded = true;
	          main_core.Runtime.debounce(() => {
	            this.moveTo(departmentIdToFocus);
	          }, movingDelay)();
	        }
	    },
	    onDisconnectDepartment({
	      data
	    }) {
	      const {
	        id
	      } = data;
	      const department = this.departments.get(id);
	      delete department.prevParentId;
	      if (!department.parentId) {
	        OrgChartActions.removeDepartment(id);
	      }
	    },
	    onDragEntity({
	      data
	    }) {
	      const {
	        draggedId
	      } = data;
	      if (this.expandedNodes.includes(draggedId)) {
	        this.collapseRecursively(draggedId);
	      }
	    },
	    onToggleConnectors({
	      data
	    }) {
	      const {
	        shouldShowElements
	      } = data;
	      this.connectors.toggleAllConnectorsVisibility(shouldShowElements, this.expandedNodes);
	    },
	    async changeOrder(data) {
	      const {
	        draggedId,
	        targetId,
	        affectedItems,
	        direction
	      } = data;
	      const promise = OrgChartActions.orderDepartments(draggedId, targetId, direction, affectedItems.length);
	      const fullDepartmentWidth = 302;
	      const draggedShift = affectedItems.length * direction * fullDepartmentWidth;
	      this.connectors.adaptConnectorsAfterReorder([draggedId], draggedShift, true);
	      const affectedShift = -direction * fullDepartmentWidth;
	      this.connectors.adaptConnectorsAfterReorder(affectedItems, affectedShift, true);
	      const ordered = await promise;
	      if (!ordered) {
	        this.connectors.adaptConnectorsAfterReorder([draggedId], -draggedShift, true);
	        this.connectors.adaptConnectorsAfterReorder(affectedItems, -affectedShift, true);
	      }
	    },
	    async updateParent(data) {
	      const {
	        draggedId,
	        targetId,
	        targetIndex,
	        insertion
	      } = data;
	      const {
	        id,
	        parentId
	      } = this.departments.get(draggedId);
	      if (parentId === targetId && insertion === 'inside') {
	        return;
	      }
	      let updateParentPromise = null;
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      if (insertion === 'sibling-left' || insertion === 'sibling-right') {
	        const {
	          parentId: targetParentId
	        } = this.departments.get(targetId);
	        const position = insertion === 'sibling-left' ? targetIndex : targetIndex + 1;
	        store.updateDepartment({
	          id,
	          parentId: targetParentId
	        }, position);
	        updateParentPromise = chartAPI.updateDepartment(draggedId, targetParentId);
	      } else {
	        store.updateDepartment({
	          id,
	          parentId: targetId
	        });
	        updateParentPromise = chartAPI.updateDepartment(draggedId, targetId);
	      }
	      this.locateToDepartment(draggedId);
	      try {
	        await updateParentPromise;
	        if (insertion === 'sibling-left' || insertion === 'sibling-right') {
	          const {
	            parentId: targetParentId
	          } = this.departments.get(targetId);
	          const {
	            children
	          } = this.departments.get(targetParentId);
	          const shift = insertion === 'sibling-left' ? 1 : 2;
	          const affectedCount = children.length - targetIndex - shift;
	          await chartAPI.changeOrder(draggedId, -1, affectedCount);
	        }
	      } catch (err) {
	        console.error(err);
	      }
	    },
	    onDropEntity({
	      data
	    }) {
	      const {
	        insertion
	      } = data;
	      if (insertion === 'reorder') {
	        this.changeOrder(data);
	        return;
	      }
	      this.updateParent(data);
	    },
	    onPublicFocusNode({
	      data
	    }) {
	      const {
	        nodeId
	      } = data;
	      const node = this.departments.get(nodeId);
	      if (!node) {
	        return;
	      }
	      void this.locateToDepartment(nodeId);
	    },
	    collapse(nodeId) {
	      this.expandedNodes = this.expandedNodes.filter(expandedId => expandedId !== nodeId);
	      this.connectors.toggleConnectorsVisibility(nodeId, false);
	      this.connectors.toggleConnectorHighlighting(nodeId, false);
	    },
	    collapseRecursively(nodeId) {
	      const deepCollapse = id => {
	        var _node$children;
	        this.collapse(id);
	        const node = this.departments.get(id);
	        (_node$children = node.children) == null ? void 0 : _node$children.forEach(childId => {
	          if (this.expandedNodes.includes(childId)) {
	            deepCollapse(childId);
	          }
	        });
	      };
	      const {
	        parentId
	      } = this.departments.get(nodeId);
	      const expandedNode = this.expandedNodes.find(id => {
	        const node = this.departments.get(id);
	        return node.parentId === parentId;
	      });
	      if (expandedNode) {
	        deepCollapse(expandedNode);
	      }
	    },
	    expand(departmentId, options = {}) {
	      const {
	        isManual = false
	      } = options;
	      this.collapseRecursively(departmentId);
	      this.expandedNodes = [...this.expandedNodes, departmentId];
	      this.connectors.toggleConnectorsVisibility(departmentId, true);
	      this.connectors.toggleConnectorHighlighting(departmentId, true);
	      const department = this.departments.get(departmentId);
	      const childrenWithoutHeads = department.children.filter(childId => {
	        return !this.departments.get(childId).heads;
	      });
	      if (childrenWithoutHeads.length > 0) {
	        this.loadHeads(childrenWithoutHeads);
	      }
	      if (isManual) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: 'expand_department'
	        });
	      }
	    },
	    focus(nodeId, options = {}) {
	      var _this$departments$get2;
	      const {
	        expandAfterFocus = false,
	        showEmployees = false,
	        subdivisionsSelected = false
	      } = options;
	      const hasChildren = ((_this$departments$get2 = this.departments.get(nodeId).children) == null ? void 0 : _this$departments$get2.length) > 0;
	      let shouldExpand = expandAfterFocus || !this.expandedNodes.includes(nodeId);
	      if (showEmployees) {
	        shouldExpand = this.expandedNodes.includes(nodeId);
	      }
	      if (subdivisionsSelected || !hasChildren) {
	        this.collapseRecursively(nodeId);
	      }
	      if (hasChildren && shouldExpand) {
	        this.expand(nodeId, {
	          isManual: true
	        });
	      }
	      if (this.focusedNode && !this.expandedNodes.includes(this.focusedNode)) {
	        this.connectors.toggleConnectorHighlighting(this.focusedNode, false);
	      }
	      OrgChartActions.focusDepartment(nodeId);
	      this.connectors.toggleConnectorHighlighting(this.focusedNode, true);
	    },
	    onFocusDepartment({
	      data
	    }) {
	      const {
	        nodeId,
	        showEmployees,
	        subdivisionsSelected
	      } = data;
	      this.focus(nodeId, {
	        showEmployees,
	        subdivisionsSelected
	      });
	      this.$emit('controlDetail', {
	        showEmployees,
	        preventSwitch: subdivisionsSelected
	      });
	    },
	    tryRemoveDepartment(nodeId, entityType) {
	      const localizationMap = {
	        team: {
	          title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_TITLE'),
	          message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_MESSAGE'),
	          success: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_REMOVED'),
	          error: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_ERROR')
	        },
	        default: {
	          title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_TITLE'),
	          message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_MESSAGE'),
	          success: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_REMOVED'),
	          error: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_ERROR')
	        }
	      };
	      const mapIndex = entityType === humanresources_companyStructure_utils.EntityTypes.team ? 'team' : 'default';
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: localizationMap[mapIndex].title,
	        message: localizationMap[mapIndex].message,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        onOk: async dialog => {
	          try {
	            await this.removeDepartment(nodeId);
	            ui_notification.UI.Notification.Center.notify({
	              content: localizationMap[mapIndex].success,
	              autoHideDelay: 2000
	            });
	            dialog.close();
	          } catch {
	            ui_notification.UI.Notification.Center.notify({
	              content: localizationMap[mapIndex].error,
	              autoHideDelay: 2000
	            });
	          }
	        },
	        onCancel: dialog => dialog.close(),
	        minWidth: 250,
	        maxWidth: 320,
	        minHeight: 175,
	        okCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_OK_CAPTION'),
	        popupOptions: {
	          className: 'humanresources-tree__message-box',
	          overlay: {
	            opacity: 40
	          }
	        }
	      });
	      const okButton = messageBox.getOkButton();
	      const cancelButton = messageBox.getCancelButton();
	      okButton.setRound(true);
	      cancelButton.setRound(true);
	      okButton.setColor(ui_buttons.ButtonColor.DANGER);
	      cancelButton.setColor(ui_buttons.ButtonColor.LIGHT_BORDER);
	      messageBox.show();
	    },
	    async removeDepartment(nodeId) {
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      store.updateChatsInChildrenNodes(nodeId);
	      await chartAPI.removeDepartment(nodeId);
	      const removableDepartment = this.departments.get(nodeId);
	      const {
	        parentId,
	        children: removableDepartmentChildren = []
	      } = removableDepartment;
	      if (removableDepartmentChildren.length > 0) {
	        this.collapse(nodeId);
	      }
	      OrgChartActions.moveSubordinatesToParent(nodeId);
	      await this.$nextTick();
	      OrgChartActions.markDepartmentAsRemoved(nodeId);
	      this.focus(parentId, {
	        expandAfterFocus: true
	      });
	      this.moveTo(parentId);
	    },
	    expandDepartmentParents(nodeId) {
	      let {
	        parentId
	      } = this.departments.get(nodeId);
	      while (parentId) {
	        if (!this.expandedNodes.includes(parentId)) {
	          this.expand(parentId);
	        }
	        parentId = this.departments.get(parentId).parentId;
	      }
	    },
	    expandLowerDepartments() {
	      let expandLevel = 0;
	      const expandRecursively = departmentId => {
	        var _this$departments$get3;
	        const {
	          children = []
	        } = this.departments.get(departmentId);
	        if (expandLevel === 4 || children.length === 0) {
	          return;
	        }
	        this.expand(departmentId);
	        expandLevel += 1;
	        const middleBound = Math.trunc(children.length / 2);
	        const childId = children[middleBound];
	        if (((_this$departments$get3 = this.departments.get(childId).children) == null ? void 0 : _this$departments$get3.length) > 0) {
	          expandRecursively(childId);
	          return;
	        }
	        for (let i = middleBound - 1; i >= 0; i--) {
	          if (traverseSibling(children[i])) {
	            return;
	          }
	        }
	        for (let i = middleBound + 1; i < children.length; i++) {
	          if (traverseSibling(children[i])) {
	            return;
	          }
	        }
	      };
	      const traverseSibling = siblingId => {
	        const {
	          children: currentChildren = []
	        } = this.departments.get(siblingId);
	        if (currentChildren.length > 0) {
	          expandRecursively(siblingId);
	          return true;
	        }
	        return false;
	      };
	      expandRecursively(this.rootId);
	    },
	    async locateToCurrentDepartment() {
	      // currentDepartmentsLocated manipulation needed to cycle through current departments
	      if (this.currentDepartmentsLocated.length === this.currentDepartments.length) {
	        this.currentDepartmentsLocated = [];
	      }
	      const currentDepartment = this.currentDepartments.find(item => !this.currentDepartmentsLocated.includes(item));
	      if (!currentDepartment) {
	        return;
	      }
	      this.currentDepartmentsLocated.push(currentDepartment);
	      await this.locateToDepartment(currentDepartment);
	      OrgChartActions.searchUserInDepartment(this.userId);
	    },
	    async locateToDepartment(nodeId) {
	      this.isLocatedDepartmentVisible = false;
	      this.expandDepartmentParents(nodeId);
	      this.focus(nodeId, {
	        expandAfterFocus: true
	      });
	      // $nextTick makes sure that this.getTreeBounds() returns correct value when nodeId is not visible
	      await this.$nextTick();
	      this.isLocatedDepartmentVisible = true;
	      await this.moveTo(nodeId);
	    },
	    async moveTo(nodeId) {
	      await this.$nextTick();
	      const treeRect = this.getTreeBounds();
	      const centerX = treeRect.x + treeRect.width / 2;
	      const centerY = treeRect.y + treeRect.height / 2;
	      const treeNode = this.treeNodes.get(nodeId);
	      const treeNodeRect = treeNode.getBoundingClientRect();
	      this.$emit('moveTo', {
	        x: centerX - treeNodeRect.x - treeNodeRect.width / 2,
	        y: centerY - treeNodeRect.y - treeNodeRect.height / 2,
	        nodeId
	      });
	    },
	    loadHeads(departmentIds) {
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      store.loadHeads(departmentIds);
	    },
	    subscribeOnEvents() {
	      this.events = {
	        [events.HR_DEPARTMENT_CONNECT]: this.onConnectDepartment,
	        [events.HR_DEPARTMENT_DISCONNECT]: this.onDisconnectDepartment,
	        [events.HR_DEPARTMENT_FOCUS]: this.onFocusDepartment,
	        [events.HR_DRAG_ENTITY]: this.onDragEntity,
	        [events.HR_DROP_ENTITY]: this.onDropEntity,
	        [events.HR_ENTITY_TOGGLE_ELEMENTS]: this.onToggleConnectors,
	        [events.HR_PUBLIC_FOCUS_NODE]: this.onPublicFocusNode,
	        [events.HR_USER_DRAG_START]: this.onUserDragStart,
	        [events.HR_USER_DRAG_DROP]: this.onUserDragDrop,
	        [events.HR_USER_DRAG_END]: this.onUserDragEnd,
	        [events.HR_USER_DRAG_ENTER_NODE]: this.onUserDragEnterNode,
	        [events.HR_USER_DRAG_LEAVE_NODE]: this.onUserDragLeaveNode
	      };
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.subscribe(event, handle);
	      });
	    },
	    unsubscribeFromEvents() {
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.unsubscribe(event, handle);
	      });
	    },
	    onUserDragStart({
	      data
	    }) {
	      this.draggedUserData = data.user;
	    },
	    onUserDragEnterNode({
	      data
	    }) {
	      if (!this.draggedUserData) {
	        return;
	      }
	      const hoveredNodeId = data.nodeId;
	      const sourceDepartmentId = this.focusedNode;
	      if (hoveredNodeId === sourceDepartmentId) {
	        this.userDropTargetNodeId = hoveredNodeId;
	        this.isUserDropAllowed = false;
	        return;
	      }
	      this.userDropTargetNodeId = hoveredNodeId;
	      const movePermissions = this.canMoveUser(sourceDepartmentId, hoveredNodeId);
	      this.isUserDropAllowed = movePermissions.isAllowed;
	      this.isCombineOnly = movePermissions.combineOnly;
	    },
	    onUserDragLeaveNode({
	      data
	    }) {
	      if (!this.draggedUserData) {
	        return;
	      }
	      if (this.userDropTargetNodeId === data.nodeId) {
	        this.userDropTargetNodeId = null;
	        this.isUserDropAllowed = false;
	      }
	    },
	    async onUserDragDrop() {
	      if (!this.userDropTargetNodeId || !this.isUserDropAllowed) {
	        return;
	      }
	      this.dndUserMoveContext = {
	        user: this.draggedUserData,
	        sourceDepartmentId: this.focusedNode,
	        targetDepartmentId: this.userDropTargetNodeId,
	        sourceRole: this.draggedUserData.role
	      };
	      this.showDndConfirmationPopup = true;
	    },
	    async confirmUserMove(payload) {
	      var _this$targetDepartmen2, _this$targetDepartmen3, _this$targetDepartmen4, _this$targetDepartmen5;
	      this.showDndConfirmationPopup = false;
	      const {
	        user
	      } = this.dndUserMoveContext;
	      const userInTarget = ((_this$targetDepartmen2 = (_this$targetDepartmen3 = this.targetDepartment) == null ? void 0 : _this$targetDepartmen3.heads) != null ? _this$targetDepartmen2 : []).find(u => u.id === user.id) || ((_this$targetDepartmen4 = (_this$targetDepartmen5 = this.targetDepartment) == null ? void 0 : _this$targetDepartmen5.employees) != null ? _this$targetDepartmen4 : []).find(u => u.id === user.id);
	      const isAlreadyInTarget = Boolean(userInTarget);
	      const isRoleTheSame = isAlreadyInTarget && userInTarget.role === payload.role;
	      if (isAlreadyInTarget && isRoleTheSame) {
	        this.displayDndErrorPopup(user);
	        this.dndUserMoveContext = null;
	        return;
	      }
	      try {
	        var _this$targetDepartmen6;
	        if (payload.isCombineMode) {
	          await this.handleCombineUser(this.dndUserMoveContext, payload);
	        } else {
	          await this.handleMoveUser(this.dndUserMoveContext, payload, isAlreadyInTarget);
	        }
	        const phrase = this.isTeamTarget ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_SUCCESS_TEAM' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_SUCCESS_DEPARTMENT';
	        ui_notification.UI.Notification.Center.notify({
	          content: this.loc(phrase, {
	            '#USER_NAME#': main_core.Text.encode(user.name),
	            '#DEPARTMENT_NAME#': main_core.Text.encode((_this$targetDepartmen6 = this.targetDepartment) == null ? void 0 : _this$targetDepartmen6.name),
	            '#USER_ROLE#': main_core.Text.encode(payload.roleLabel)
	          })
	        });
	      } catch (error) {
	        console.error(error);
	      }
	      this.dndUserMoveContext = null;
	    },
	    async handleCombineUser(dndContext, payload) {
	      var _payload$badgeText;
	      const {
	        user,
	        targetDepartmentId
	      } = dndContext;
	      const data = await humanresources_companyStructure_userManagementDialog.UserManagementDialogAPI.addUsersToDepartment(targetDepartmentId, [user.id], payload.role);
	      humanresources_companyStructure_chartStore.UserService.addUsersToEntity(targetDepartmentId, [{
	        ...user,
	        role: payload.role,
	        badgeText: (_payload$badgeText = payload.badgeText) != null ? _payload$badgeText : null
	      }], data.userCount, payload.role);
	    },
	    async handleMoveUser(dndContext, payload, isAlreadyInTarget) {
	      var _payload$badgeText2;
	      const {
	        user,
	        sourceDepartmentId,
	        targetDepartmentId,
	        sourceRole
	      } = dndContext;
	      const targetDepartment = this.departments.get(targetDepartmentId);
	      if (isAlreadyInTarget) {
	        await humanresources_companyStructure_userManagementDialog.UserManagementDialogAPI.addUsersToDepartment(targetDepartmentId, [user.id], payload.role);
	        await DepartmentAPI.removeUserFromDepartment(sourceDepartmentId, user.id);
	      } else {
	        await DepartmentAPI.moveUserToDepartment(sourceDepartmentId, user.id, targetDepartmentId, payload.role);
	      }
	      const finalUserCount = targetDepartment.userCount + (isAlreadyInTarget ? 0 : 1);
	      humanresources_companyStructure_chartStore.UserService.removeUserFromEntity(sourceDepartmentId, user.id, sourceRole);
	      humanresources_companyStructure_chartStore.UserService.addUsersToEntity(targetDepartmentId, [{
	        ...user,
	        role: payload.role,
	        badgeText: (_payload$badgeText2 = payload.badgeText) != null ? _payload$badgeText2 : null
	      }], finalUserCount, payload.role);
	    },
	    displayDndErrorPopup(user) {
	      var _this$targetDepartmen7;
	      const phrase = this.isTeamTarget ? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_ERROR_POPUP_DESC_TEAM' : 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_ERROR_POPUP_DESC_DEPARTMENT';
	      this.dndErrorPopupDescription = main_core.Loc.getMessage(phrase, {
	        '#USER_NAME#': main_core.Text.encode(user.name),
	        '#DEPARTMENT_NAME#': main_core.Text.encode((_this$targetDepartmen7 = this.targetDepartment) == null ? void 0 : _this$targetDepartmen7.name),
	        '[link]': `<a class="hr-department-detail-content__move-user-department-user-link" href="${user.url}">`,
	        '[/link]': '</a>'
	      });
	      this.showDndErrorPopup = true;
	    },
	    cancelUserMove() {
	      if (!this.dndUserMoveContext) {
	        return;
	      }
	      this.showDndConfirmationPopup = false;
	      this.dndUserMoveContext = null;
	    },
	    closeDndErrorPopup() {
	      this.showDndErrorPopup = false;
	      this.dndErrorPopupDescription = '';
	      this.dndUserMoveContext = null;
	    },
	    onUserDragEnd() {
	      this.userDropTargetNodeId = null;
	      this.isUserDropAllowed = false;
	      this.draggedUserData = null;
	    },
	    canMoveUser(sourceDepartmentId, targetDepartmentId) {
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      const source = this.departments.get(sourceDepartmentId);
	      const target = this.departments.get(targetDepartmentId);
	      if (!source || !target) {
	        return {
	          isAllowed: false,
	          combineOnly: false
	        };
	      }
	      const isTeamToDept = source.entityType === humanresources_companyStructure_utils.EntityTypes.team && target.entityType === humanresources_companyStructure_utils.EntityTypes.department;
	      if (isTeamToDept) {
	        return {
	          isAllowed: false,
	          combineOnly: false
	        };
	      }
	      const sourcePermission = source.entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamRemoveMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeRemoveFromDepartment;
	      const canTakeFromSource = permissionChecker.hasPermission(sourcePermission, sourceDepartmentId);
	      const targetPermission = this.isTeamTarget ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	      const canPutToTarget = permissionChecker.hasPermission(targetPermission, targetDepartmentId);
	      if (!canPutToTarget) {
	        return {
	          isAllowed: false,
	          combineOnly: false
	        };
	      }
	      const isDeptToTeam = source.entityType === humanresources_companyStructure_utils.EntityTypes.department && target.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	      if (isDeptToTeam) {
	        return {
	          isAllowed: true,
	          combineOnly: true
	        };
	      }
	      return {
	        isAllowed: true,
	        combineOnly: !canTakeFromSource
	      };
	    }
	  },
	  template: `
		<div
			class="humanresources-tree"
			v-if="departments.size > 0"
			v-dnd="canvasTransform"
		>
			<TreeNode
				class="--root"
				:key="rootId"
				:nodeId="rootId"
				:expandedNodes="[...expandedNodes]"
				:canvasZoom="canvasTransform.zoom"
				:currentDepartments="currentDepartments"
				:userDropTargetNodeId="userDropTargetNodeId"
				:isUserDropAllowed="isUserDropAllowed"
				:isDraggingUser="!!draggedUserData"
			></TreeNode>
			<Connectors
				ref="connectors"
				:isLocatedDepartmentVisible="isLocatedDepartmentVisible"
				:treeNodes="treeNodes"
			></Connectors>
			<MoveEmployeeConfirmationPopup
				v-if="showDndConfirmationPopup"
				:description="dndPopupDescription"
				:showRoleSelect="true"
				:showCombineCheckbox="true"
				:isCombineOnly="isCombineOnly"
				:targetType="dndTargetEntityType"
				:sourceType="dndSourceEntityType"
				@confirm="confirmUserMove"
				@close="cancelUserMove"
			/>
			<ConfirmationPopup
				v-if="showDndErrorPopup"
				:withoutTitleBar = true
				:onlyConfirmButtonMode = true
				:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_CLOSE_BUTTON')"
				:width="300"
				@action="closeDndErrorPopup"
				@close="closeDndErrorPopup"
			>
				<template v-slot:content>
					<div class="hr-department-detail-content__user-action-text-container">
						<div
							class="hr-department-detail-content__user-belongs-to-department-text-container"
							v-html="dndErrorPopupDescription"
						/>
					</div>
				</template>
			</ConfirmationPopup>
		</div>
	`
	};

	// @vue/component
	const TransformPanel = {
	  name: 'transform-panel',
	  props: {
	    modelValue: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['update:modelValue'],
	  data() {
	    return {
	      selectedId: ''
	    };
	  },
	  computed: {
	    zoomInPercent() {
	      const percent = '<span class="humanresources-transform-panel__zoom_percent">%</span>';
	      return `${(this.modelValue.zoom * 100).toFixed(0)}${percent}`;
	    }
	  },
	  created() {
	    this.actions = Object.freeze({
	      zoomIn: 'zoomIn',
	      zoomOut: 'zoomOut',
	      locate: 'locate',
	      navigate: 'navigate'
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onZoom(zoomIn) {
	      const leftBound = 0.2;
	      const rightBound = 3;
	      let direction = -1;
	      if (zoomIn) {
	        direction = 1;
	        this.selectedId = this.actions.zoomIn;
	      } else {
	        this.selectedId = this.actions.zoomOut;
	      }
	      const zoom = Number((this.modelValue.zoom + leftBound * direction).toFixed(1));
	      if (zoom < leftBound || zoom > rightBound) {
	        return;
	      }

	      // calibrate x and y according to viewpoint center
	      const treeRect = this.$parent.$refs.tree.getTreeBounds();
	      const centerX = treeRect.width / 2 / this.modelValue.zoom - detailPanelWidth / 2;
	      const centerY = treeRect.height / 2 / this.modelValue.zoom;
	      const oldCenterX = (centerX - this.modelValue.x) / this.modelValue.zoom;
	      const oldCenterY = (centerY - this.modelValue.y) / this.modelValue.zoom;
	      const x = centerX - oldCenterX * zoom;
	      const y = centerY - oldCenterY * zoom;
	      this.$emit('update:modelValue', {
	        ...this.modelValue,
	        zoom,
	        x,
	        y
	      });
	    },
	    onLocate() {
	      main_core_events.EventEmitter.emit(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT);
	      this.selectedId = this.actions.locate;
	    },
	    onfocusout() {
	      this.selectedId = '';
	    }
	  },
	  template: `
		<div class="humanresources-transform-panel" @focusout="onfocusout" tabindex="-1">
			<div
				class="humanresources-transform-panel__locate"
				:class="{ '--selected': selectedId === actions.locate }"
				@click="onLocate"
			>
				{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_LOCATE')}}
			</div>
			<div class="humanresources-transform-panel__separator"></div>
			<div class="humanresources-transform-panel__zoom">
				<svg
					viewBox="0 0 16 16"
					fill="none"
					class="humanresources-transform-panel__icon --zoom-out"
					:class="{ '--selected': selectedId === actions.zoomOut }"
					@click="onZoom(false)"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8.66671V7.33337H7.33333H8.66667H12V8.66671H8.66667H7.33333H4Z" fill="#6A737F"/>
				</svg>
				<span v-html="zoomInPercent"></span>
				<svg
					viewBox="0 0 16 16"
					fill="none"
					class="humanresources-transform-panel__icon --zoom-in"
					:class="{ '--selected': selectedId === actions.zoomIn }"
					@click="onZoom(true)"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M7.83333 4H9.16667V7.33333H12.5V8.66667H9.16667V12H7.83333V8.66667H4.5V7.33333H7.83333V4Z" fill="#6A737F"/>
				</svg>
			</div>
		</div>
	`
	};

	// @vue/component
	const DetailPanelCollapsedTitle = {
	  name: 'detailPanelCollapsedTitle',
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    avatars: {
	      type: Array,
	      required: true
	    }
	  },
	  computed: {
	    maxVisibleAvatarsCount() {
	      return 2;
	    },
	    additionalCount() {
	      return this.avatars.length > this.maxVisibleAvatarsCount ? this.avatars.length - this.maxVisibleAvatarsCount : 0;
	    }
	  },
	  template: `
		<div class="humanresources-detail-panel__collapsed-title">
			<template v-for="(avatar, index) in avatars">
				<img
					v-if="index < this.maxVisibleAvatarsCount"
					:key="index"
					:src="encodeURI(avatar)"
					class="humanresources-detail-panel__collapsed-title-avatar"
				/>
			</template>
			<div
				v-if="avatars.length > maxVisibleAvatarsCount"
				class="humanresources-detail-panel__collapsed-title-avatar --additional"
			>
			 +{{ additionalCount }}
			</div>
			<div class="humanresources-detail-panel__title">{{ title }}</div>
		</div>
	`
	};

	/**
	 * Component for displaying icon that shows EntityActionMenu
	 */
	// @vue/component
	const DetailPanelEditButton = {
	  name: 'detailPanelEditButton',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  props: {
	    entityId: {
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
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    menu() {
	      return new EntityActionMenu(this.entityId, this.entityType, humanresources_companyStructure_api.AnalyticsSourceType.NODE_DETAIL);
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      this.menu.onActionMenuItemClick(actionId);
	    }
	  },
	  template: `
		<div
			v-if="menu.items.length"
			class="humanresources-detail-panel__edit-button"
			:class="{ '--focused': menuVisible }"
			:ref="'detailPanelEditButton'"
			data-id="hr-department-detail-panel__edit-menu-button"
			@click.stop="menuVisible = true"
		>
			<BIcon
				class="humanresources-detail-panel__edit-button-icon"
				:name="set.MORE"
				:size="20"
			/>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			id="department-detail-content-edit-menu"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.detailPanelEditButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	// @vue/component
	const DetailPanel = {
	  name: 'detailPanel',
	  components: {
	    DepartmentContent: humanresources_companyStructure_departmentContent.DepartmentContent,
	    DetailPanelCollapsedTitle,
	    DetailPanelEditButton
	  },
	  directives: {
	    hint: humanresources_companyStructure_structureComponents.Hint
	  },
	  props: {
	    preventPanelSwitch: Boolean,
	    modelValue: Boolean
	  },
	  emits: ['showWizard', 'removeDepartment', 'update:modelValue'],
	  data() {
	    return {
	      title: '',
	      isTeamEntity: false,
	      teamColor: null,
	      isCollapsed: true,
	      isHidden: false,
	      isLoading: true,
	      needToShowLoader: false
	    };
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode', 'departments']),
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    headAvatarsArray() {
	      var _this$departments$get, _heads$filter$map, _heads$filter;
	      const heads = (_this$departments$get = this.departments.get(this.focusedNode).heads) != null ? _this$departments$get : [];
	      return (_heads$filter$map = heads == null ? void 0 : (_heads$filter = heads.filter(employee => employee.role === this.memberRoles.head)) == null ? void 0 : _heads$filter.map(employee => employee.avatar || this.defaultAvatar)) != null ? _heads$filter$map : [];
	    },
	    entityType() {
	      return this.departments.get(this.focusedNode).entityType;
	    },
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.entityType);
	    },
	    isPanelCollapsed() {
	      return this.isCollapsed || this.isHidden;
	    }
	  },
	  watch: {
	    focusedNode(newId, oldId) {
	      this.updateDetailPageHandler(newId, oldId);
	    },
	    modelValue(collapsed) {
	      this.isCollapsed = collapsed;
	    },
	    departments: {
	      handler(newDepartments) {
	        const department = newDepartments.get(this.focusedNode);
	        if (department) {
	          var _department$name;
	          this.title = (_department$name = department.name) != null ? _department$name : '';
	        }
	      },
	      deep: true
	    }
	  },
	  created() {
	    this.subscribeOnEvents();
	  },
	  beforeUnmount() {
	    this.unsubscribeFromEvents();
	  },
	  methods: {
	    toggleCollapse() {
	      this.$emit('update:modelValue', !this.isCollapsed);
	    },
	    updateDetailPageHandler(nodeId, oldId) {
	      var _department$name2, _department$teamColor;
	      if (!this.preventPanelSwitch && oldId !== 0) {
	        this.$emit('update:modelValue', false);
	      }
	      this.isLoading = true;
	      const department = this.departments.get(nodeId);
	      this.isTeamEntity = department.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	      this.title = (_department$name2 = department.name) != null ? _department$name2 : '';
	      this.teamColor = (_department$teamColor = department.teamColor) != null ? _department$teamColor : null;
	      this.isLoading = false;
	    },
	    showLoader() {
	      this.needToShowLoader = true;
	    },
	    hideLoader() {
	      this.needToShowLoader = false;
	    },
	    subscribeOnEvents() {
	      this.events = {
	        [events.HR_ENTITY_TOGGLE_ELEMENTS]: this.onToggleDetailPanel
	      };
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.subscribe(event, handle);
	      });
	    },
	    unsubscribeFromEvents() {
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.unsubscribe(event, handle);
	      });
	    },
	    onToggleDetailPanel({
	      data
	    }) {
	      const {
	        shouldShowElements
	      } = data;
	      this.isHidden = !shouldShowElements;
	    }
	  },
	  template: `
		<div
			:class="['humanresources-detail-panel', { '--collapsed': isPanelCollapsed }]"
			v-on="isPanelCollapsed ? { click: toggleCollapse } : {}"
			data-id="hr-department-detail-panel__container"
		>
			<div
				v-if="!isLoading"
				class="humanresources-detail-panel-container"
				:class="{ '--hide': needToShowLoader && !isPanelCollapsed }"
			>
				<div class="humanresources-detail-panel__head" 
					 :class="{ '--team': isTeamEntity, '--collapsed': isPanelCollapsed }"
					 :style="{ '--team-head-background': teamColor?.treeHeadBackground }"
				>
					<span
						v-if="!isPanelCollapsed"
						v-hint
						class="humanresources-detail-panel__title"
					>
						{{ title }}
					</span>
					<DetailPanelCollapsedTitle
						v-else
						:title="title"
						:avatars="headAvatarsArray"
					>
					</DetailPanelCollapsedTitle>
					<div class="humanresources-detail-panel__header_buttons_container">
						<DetailPanelEditButton
							v-if="!isPanelCollapsed"
							:entityId="focusedNode"
							:entityType="entityType"
						/>
						<div
							class="humanresources-detail-panel__collapse_button --icon"
							@click="toggleCollapse"
							:class="{ '--collapsed': isPanelCollapsed }"
							data-id="hr-department-detail-panel__collapse-button"
						/>
					</div>
				</div>
				<div class="humanresources-detail-panel__content" v-show="!isPanelCollapsed">
					<DepartmentContent
						@showDetailLoader="showLoader"
						@hideDetailLoader="hideLoader"
						:isCollapsed="isPanelCollapsed"
					/>
				</div>
			</div>
			<div v-if="needToShowLoader && !isPanelCollapsed" class="humanresources-detail-panel-loader-container"/>
		</div>
	`
	};

	// @vue/component
	const FirstPopup = {
	  name: 'FirstPopup',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      show: false,
	      title: '',
	      description: '',
	      subDescription: '',
	      features: []
	    };
	  },
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  async mounted() {
	    this.title = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_TITLE');
	    this.description = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_DESCRIPTION');
	    this.subDescription = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_SUB_DESCRIPTION');
	    this.features = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_3'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_4'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_5'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_6'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_7')];
	    const {
	      firstTimeOpened
	    } = await chartAPI.getDictionary();
	    this.show = firstTimeOpened === 'N' && this.title.length > 0;
	  },
	  methods: {
	    closePopup() {
	      chartAPI.firstTimeOpened();
	      this.show = false;
	      top.BX.Event.EventEmitter.emit(events.HR_FIRST_POPUP_SHOW);
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div v-if="show" class="first-popup">
			<div class="first-popup-overlay" @click="closePopup"></div>
			<div class="first-popup-content">
				<div class="title">{{ title }}</div>
				<div class="first-popup-left">
					<p class="description">{{ description }}</p>
					<p class="sub-description">{{ subDescription }}</p>
					<div class="first-popup-list">
						<div class="first-popup-list-item" v-for="(feature, index) in features" :key="index">
							<div class="first-popup-list-item-point">•</div>
							<div class="first-popup-list-item-feature">{{ feature }}</div>
						</div>
					</div>
					<button class="ui-btn ui-btn-success first-popup-ui-btn" @click="closePopup">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_BUTTON_START') }}
					</button>
				</div>
				<div class="first-popup-right">
					<video
						src="/bitrix/js/humanresources/company-structure/org-chart/src/components/first-popup/images/preview.webm"
						autoplay
						loop
						muted
						playsinline
						class="first-popup-animation"
					></video>
				</div>
				<BIcon :name="set.CROSS_25" :size="24" class="first-popup-close" @click="closePopup"></BIcon>
			</div>
		</div>
	`
	};

	// @vue/component
	const Chart = {
	  components: {
	    TransformCanvas: ui_canvas.TransformCanvas,
	    Tree,
	    TransformPanel,
	    ChartWizard: humanresources_companyStructure_chartWizard.ChartWizard,
	    FirstPopup,
	    DetailPanel,
	    TitlePanel
	  },
	  data() {
	    return {
	      canvas: {
	        shown: false,
	        moved: false,
	        modelTransform: {
	          x: 0,
	          y: 0,
	          zoom: 0.3
	        }
	      },
	      wizard: {
	        shown: false,
	        isEditMode: false,
	        showEntitySelector: true,
	        entity: '',
	        nodeId: 0,
	        source: ''
	      },
	      detailPanel: {
	        collapsed: true,
	        preventSwitch: false
	      },
	      // so we block all controls until transition isn't completed
	      isTransitionCompleted: false,
	      analyticsQueue: [],
	      isAnalyticsBusy: false
	    };
	  },
	  computed: {
	    rootId() {
	      const {
	        id: rootId
	      } = [...this.departments.values()].find(department => {
	        return department.parentId === 0;
	      });
	      return rootId;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'currentDepartments'])
	  },
	  async created() {
	    var _departmentsData$mult;
	    const slider = BX.SidePanel.Instance.getTopSlider();
	    slider == null ? void 0 : slider.showLoader();
	    const [departmentsData, currentDepartments, userId] = await Promise.all([chartAPI.getDepartmentsData(), chartAPI.getCurrentDepartments(), chartAPI.getUserId()]);
	    slider == null ? void 0 : slider.closeLoader();
	    const departments = departmentsData.structure;
	    const structureMap = Object.entries(departmentsData.map).reduce((map, [id, value]) => {
	      map.set(Number(id), {
	        id: Number(id),
	        ...value
	      });
	      return map;
	    }, new Map());
	    const multipleUsers = (_departmentsData$mult = departmentsData.multipleMembers) != null ? _departmentsData$mult : [];
	    const parsedDepartments = chartAPI.createTreeDataStore(departments);
	    const availableDepartments = currentDepartments.filter(item => parsedDepartments.has(item));
	    OrgChartActions.applyData(parsedDepartments, availableDepartments, userId, structureMap, multipleUsers);
	    this.rootOffset = 100;
	    this.transformCanvas();
	    this.canvas.shown = true;
	    this.showConfetti = false;
	    main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
	    BX.PULL.subscribe({
	      type: BX.PullClient.SubscriptionType.Server,
	      moduleId: 'humanresources',
	      command: 'linkChatsToNodes',
	      callback: data => this.clearChatLists(data)
	    });
	    main_core_events.EventEmitter.subscribe(events.HR_ENTITY_SHOW_WIZARD, this.showWizardEventHandler);
	    main_core_events.EventEmitter.subscribe(events.HR_ENTITY_REMOVE, this.removeDepartmentEventHandler);
	    main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_TRANSFORM_CANVAS, this.onCanvasTransformWhenDragging);
	    main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, this.onLocate);
	    this.handleAnalyticsAjaxSuccess = this.handleAnalyticsAjaxSuccess.bind(this);
	    main_core.addCustomEvent('OnAjaxSuccess', this.handleAnalyticsAjaxSuccess);
	  },
	  unmounted() {
	    main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ENTITY_SHOW_WIZARD, this.showWizardEventHandler);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ENTITY_REMOVE, this.removeDepartmentEventHandler);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_TRANSFORM_CANVAS, this.onCanvasTransformWhenDragging);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, this.onLocate);
	    main_core.removeCustomEvent('OnAjaxSuccess', this.handleAnalyticsAjaxSuccess);
	  },
	  methods: {
	    onMoveTo({
	      x,
	      y,
	      nodeId
	    }) {
	      const {
	        x: prevX,
	        y: prevY,
	        zoom
	      } = this.canvas.modelTransform;
	      const detailPanelWidthZoomed = detailPanelWidth * zoom;
	      const newX = x - detailPanelWidthZoomed / 2;
	      const newY = nodeId === this.rootId ? this.rootOffset : y / zoom;
	      const samePoint = Math.round(newX) === Math.round(prevX) && Math.round(y) === Math.round(prevY);
	      this.detailPanel = {
	        ...this.detailPanel,
	        collapsed: false
	      };
	      if (samePoint) {
	        return;
	      }
	      this.canvas = {
	        ...this.canvas,
	        moved: true,
	        modelTransform: {
	          ...this.canvas.modelTransform,
	          x: newX / zoom,
	          y: newY,
	          zoom: 1
	        }
	      };
	      this.onUpdateTransform();
	    },
	    onLocate({
	      data
	    }) {
	      if (data.nodeId) {
	        this.$refs.tree.locateToDepartment(data.nodeId);
	        return;
	      }
	      this.$refs.tree.locateToCurrentDepartment();
	    },
	    showWizardEventHandler({
	      data
	    }) {
	      this.onShowWizard(data);
	    },
	    sendAnalyticsSequentially(data) {
	      this.analyticsQueue.push(data);
	      this.processAnalyticsQueue();
	    },
	    processAnalyticsQueue() {
	      if (this.isAnalyticsBusy || this.analyticsQueue.length === 0) {
	        return;
	      }
	      this.isAnalyticsBusy = true;
	      const dataToSend = this.analyticsQueue[0];
	      ui_analytics.sendData(dataToSend);
	    },
	    handleAnalyticsAjaxSuccess() {
	      if (!this.isAnalyticsBusy) {
	        return;
	      }
	      this.analyticsQueue.shift();
	      this.isAnalyticsBusy = false;
	      this.processAnalyticsQueue();
	    },
	    onShowWizard({
	      nodeId = 0,
	      isEditMode = false,
	      type,
	      showEntitySelector = true,
	      source = '',
	      entityType,
	      refToFocus
	    } = {}) {
	      let analyticsType = null;
	      if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	        analyticsType = 'team';
	      } else if (entityType === humanresources_companyStructure_utils.EntityTypes.department || entityType === humanresources_companyStructure_utils.EntityTypes.company) {
	        analyticsType = 'dept';
	      }
	      this.wizard = {
	        ...this.wizard,
	        shown: true,
	        isEditMode,
	        showEntitySelector,
	        entity: type,
	        nodeId,
	        source,
	        entityType,
	        refToFocus
	      };
	      if (!isEditMode && source !== humanresources_companyStructure_api.AnalyticsSourceType.HEADER) {
	        this.sendAnalyticsSequentially({
	          tool: 'structure',
	          category: 'structure',
	          event: 'create_wizard',
	          type: analyticsType,
	          c_element: source
	        });
	        if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	          this.sendAnalyticsSequentially({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_team_step1',
	            type: analyticsType,
	            c_element: source
	          });
	        }
	      }
	      if (isEditMode) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: `edit_${analyticsType}`,
	          c_element: source
	        });
	      }

	      // eslint-disable-next-line default-case
	      switch (type) {
	        case 'department':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: `create_${analyticsType}_step1`,
	            type: analyticsType,
	            c_element: source
	          });
	          break;
	        case 'employees':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: `create_${analyticsType}_step2`,
	            type: analyticsType,
	            c_element: source
	          });
	          break;
	        case 'bindChat':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: `create_${analyticsType}_step3`,
	            type: analyticsType,
	            c_element: source
	          });
	          break;
	        case 'teamRights':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: `create_${analyticsType}_step4`,
	            type: analyticsType,
	            c_element: source
	          });
	          break;
	      }
	    },
	    onModifyTree({
	      id,
	      showConfetti
	    }) {
	      this.showConfetti = showConfetti != null ? showConfetti : false;
	      const {
	        tree
	      } = this.$refs;
	      tree.locateToDepartment(id);
	    },
	    onWizardClose() {
	      this.wizard.shown = false;
	    },
	    removeDepartmentEventHandler({
	      data
	    }) {
	      this.onRemoveDepartment(data.nodeId, data.entityType);
	    },
	    onRemoveDepartment(nodeId, entityType) {
	      const {
	        tree
	      } = this.$refs;
	      tree.tryRemoveDepartment(nodeId, entityType);
	    },
	    async onTransitionEnd() {
	      if (this.canvas.moved) {
	        this.isTransitionCompleted = true;
	      }
	      this.canvas.moved = false;
	      if (!this.showConfetti) {
	        return;
	      }
	      this.isTransitionCompleted = false;
	      const promise = ui_confetti.Confetti.fire({
	        particleCount: 300,
	        startVelocity: 10,
	        spread: 400,
	        ticks: 100,
	        origin: {
	          y: 0.4,
	          x: 0.37
	        }
	      });
	      this.showConfetti = false;
	      await promise;
	      this.isTransitionCompleted = true;
	    },
	    onControlDetail({
	      showEmployees,
	      preventSwitch
	    }) {
	      this.detailPanel = {
	        ...this.detailPanel,
	        preventSwitch
	      };
	      if (!showEmployees) {
	        return;
	      }
	      this.detailPanel = {
	        ...this.detailPanel,
	        collapsed: false
	      };
	    },
	    transformCanvas() {
	      const {
	        zoom
	      } = this.canvas.modelTransform;
	      const {
	        offsetWidth,
	        offsetHeight
	      } = this.$el;
	      const [currentDepartment] = this.currentDepartments;
	      const y = currentDepartment === this.rootId ? this.rootOffset : offsetHeight / 2 - offsetHeight * zoom / 2;
	      this.canvas.modelTransform = {
	        ...this.canvas.modelTransform,
	        x: offsetWidth / 2 - offsetWidth * zoom / 2,
	        y
	      };
	    },
	    onUpdateTransform() {
	      main_core_events.EventEmitter.emit(events.INTRANET_USER_MINIPROFILE_CLOSE);
	      main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_MENU_CLOSE);
	    },
	    handleInviteSliderMessage(event) {
	      const [messageEvent] = event.getData();
	      const eventId = messageEvent.getEventId();
	      if (eventId !== 'BX.Intranet.Invitation:onAdd') {
	        return;
	      }
	      const {
	        users
	      } = messageEvent.getData();
	      users.forEach(user => {
	        const invitedUserData = humanresources_companyStructure_utils.getInvitedUserData(user);
	        OrgChartActions.inviteUser(invitedUserData);
	      });
	    },
	    clearChatLists(data) {
	      const nodeIds = Object.keys(data).map(key => Number(key));
	      OrgChartActions.clearNodesChatLists(nodeIds);
	    },
	    onKeyDown(event) {
	      if (!this.isTransitionCompleted) {
	        event.preventDefault();
	      }
	    },
	    onCanvasTransformWhenDragging({
	      data
	    }) {
	      const {
	        directionX,
	        directionY,
	        speed
	      } = data;
	      if (directionX !== 0) {
	        this.canvas.modelTransform.x += -directionX * speed;
	      }
	      if (directionY !== 0) {
	        this.canvas.modelTransform.y += -directionY * speed;
	      }
	    }
	  },
	  template: `
		<div
			class="humanresources-chart"
			:class="{ '--locked': !isTransitionCompleted }"
			@keydown="onKeyDown"
		>
			<TitlePanel @showWizard="onShowWizard"></TitlePanel>
			<TransformCanvas
				v-if="canvas.shown"
				v-slot="{transform}"
				v-model="canvas.modelTransform"
				@update:modelValue="onUpdateTransform"
				:class="{ '--moved': canvas.moved }"
				@transitionend="onTransitionEnd"
			>
				<Tree
					:canvasTransform="transform"
					ref="tree"
					@moveTo="onMoveTo"
					@showWizard="onShowWizard"
					@controlDetail="onControlDetail"
				></Tree>
			</TransformCanvas>
			<DetailPanel
				@showWizard="onShowWizard"
				@removeDepartment="onRemoveDepartment"
				v-model="detailPanel.collapsed"
				:preventPanelSwitch="detailPanel.preventSwitch"
			></DetailPanel>
			<TransformPanel
				v-model="canvas.modelTransform"
			></TransformPanel>
			<ChartWizard
				v-if="wizard.shown"
				:nodeId="wizard.nodeId"
				:isEditMode="wizard.isEditMode"
				:showEntitySelector="wizard.showEntitySelector"
				:entity="wizard.entity"
				:entityType="wizard.entityType"
				:source="wizard.source"
				:refToFocus="wizard.refToFocus"
				@modifyTree="onModifyTree"
				@close="onWizardClose"
			></ChartWizard>
			<FirstPopup></FirstPopup>
			<div class="humanresources-chart__back"></div>
		</div>
	`
	};

	class UsersTabActionMenu extends AbstractActionMenu {
	  constructor(entityId, analyticSource, role, entityType) {
	    super(entityId);
	    this.analyticSource = analyticSource;
	    this.role = role;
	    this.entityType = entityType;
	    this.items = this.getFilteredItems();
	  }
	  getItems() {
	    return [new AddEmployeeMenuItem(this.entityType, this.role), new EditEmployeesMenuItem(this.entityType, this.role)];
	  }
	}

	class EmptyUsersTabActionMenu extends AbstractActionMenu {
	  constructor(entityId, analyticSource, role, entityType) {
	    super(entityId);
	    this.entityType = entityType;
	    this.analyticSource = analyticSource;
	    this.role = role;
	    this.items = this.getFilteredItems();
	  }
	  getItems() {
	    if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return [new AddEmployeeMenuItem(this.entityType, this.role)];
	    }
	    return [new MoveEmployeeMenuItem(), new UserInviteMenuItem(), new AddEmployeeMenuItem(this.entityType, this.role)];
	  }
	}

	class FireUserFromCompanyMenuItem extends AbstractMenuItem {
	  constructor(isUserInvited) {
	    super({
	      id: MenuActions.fireUserFromCompany,
	      title: isUserInvited ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_DELETE_FROM_COMPANY_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_FIRE_FROM_COMPANY_TITLE'),
	      description: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_FIRE_FROM_COMPANY_SUBTITLE_MSGVER_1'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSONS_DENY,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permissionAction: isUserInvited ? '' : humanresources_companyStructure_permissionChecker.PermissionActions.employeeFire,
	      dataTestId: 'hr-department-detail-content__user-list_fire-user-from-department'
	    });
	  }
	  hasPermission(permissionChecker, entityId) {
	    return permissionChecker.hasPermissionOfAction(this.permissionAction);
	  }
	}

	class MoveFromDepartmentMenuItem extends AbstractMenuItem {
	  constructor(entityType) {
	    const title = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_TEAM_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_TITLE');
	    const description = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_TEAM_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUBTITLE');
	    super({
	      id: MenuActions.moveUserToAnotherDepartment,
	      title,
	      description,
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: null,
	      dataTestId: 'hr-department-detail-content__user-list_move-from-department'
	    });
	    this.entityType = entityType;
	  }
	  hasPermission(permissionChecker, entityId) {
	    const permissions = permissionChecker.currentUserPermissions;
	    const addMemberPermissionAction = this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	    const removeMemberPermissionAction = this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamRemoveMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeRemoveFromDepartment;
	    const addMemberPermissionValue = this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? permissions.ACTION_TEAM_MEMBER_ADD : permissions.ACTION_EMPLOYEE_ADD_TO_DEPARTMENT;
	    const removeMemberPermissionValue = this.entityType === humanresources_companyStructure_utils.EntityTypes.team ? permissions.ACTION_TEAM_MEMBER_REMOVE : permissions.ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT;
	    const moveUserPermission = addMemberPermissionValue < removeMemberPermissionValue ? addMemberPermissionAction : removeMemberPermissionAction;
	    return permissionChecker.hasPermission(moveUserPermission, entityId);
	  }
	}

	class RemoveFromDepartmentMenuItem extends AbstractMenuItem {
	  constructor(entityType) {
	    const title = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_TEAM_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_DEPARTMENT_TITLE');
	    const description = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_TEAM_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_USER_LIST_ACTION_MENU_REMOVE_FROM_DEPARTMENT_SUBTITLE');
	    const color = entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_utils.getColorCode('paletteRed40') : humanresources_companyStructure_utils.getColorCode('paletteBlue50');
	    const permissionAction = entityType === humanresources_companyStructure_utils.EntityTypes.team ? humanresources_companyStructure_permissionChecker.PermissionActions.teamRemoveMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeRemoveFromDepartment;
	    super({
	      id: MenuActions.removeUserFromDepartment,
	      title,
	      description,
	      bIcon: {
	        name: ui_iconSet_api_core.Main.TRASH_BIN,
	        size: 20,
	        color
	      },
	      permissionAction,
	      dataTestId: 'hr-department-detail-content__user-list_remove-from-department'
	    });
	  }
	}

	class UserListActionMenu extends AbstractActionMenu {
	  constructor(entityId, entityType, isUserInvited) {
	    super(entityId);
	    this.isUserInvited = isUserInvited;
	    this.entityType = entityType;
	    this.items = this.getFilteredItems();
	  }
	  getItems() {
	    if (this.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	      return [new MoveFromDepartmentMenuItem(this.entityType), new RemoveFromDepartmentMenuItem(this.entityType)];
	    }
	    return [new MoveFromDepartmentMenuItem(this.entityType), new RemoveFromDepartmentMenuItem(this.entityType), new FireUserFromCompanyMenuItem(this.isUserInvited)];
	  }
	}

	/**
	 * Menu item for opening a chat or channel.
	 * Displays appropriate title, and description based on chat type.
	 * Checks permissions using hasAccess property of the chat.
	 */
	class OpenChatMenuItem extends AbstractMenuItem {
	  constructor(entityType, chat) {
	    const {
	      title,
	      description
	    } = OpenChatMenuItem.Dictionary[chat.type] || {};
	    super({
	      id: MenuActions.openChat,
	      title: main_core.Loc.getMessage(title),
	      description: main_core.Loc.getMessage(description),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.EDIT_PENCIL,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permissionAction: null,
	      dataTestId: 'hr-department-detail-content__chat-list_open-chat'
	    });
	    this.entityType = entityType;
	    this.chat = chat;
	  }
	  hasPermission(permissionChecker, entityId) {
	    return this.chat.hasAccess;
	  }
	}
	OpenChatMenuItem.Dictionary = Object.freeze({
	  [humanresources_companyStructure_utils.ChatTypes.chat]: {
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHAT_TITLE',
	    description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHAT_DESCRIPTION'
	  },
	  [humanresources_companyStructure_utils.ChatTypes.channel]: {
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHANNEL_TITLE',
	    description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHANNEL_DESCRIPTION'
	  },
	  [humanresources_companyStructure_utils.ChatTypes.collab]: {
	    title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_COLLAB_TITLE',
	    description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_COLLAB_DESCRIPTION'
	  }
	});

	/**
	 * Menu item for unbinding a chat or channel from a team or department.
	 * Displays appropriate title, description, and permission requirements
	 * based on the entity type and chat type.
	 */
	class UnbindChatMenuItem extends AbstractMenuItem {
	  constructor(entityType, chat) {
	    const {
	      title,
	      description,
	      permissionAction
	    } = UnbindChatMenuItem.Dictionary[entityType][chat.type] || {};
	    super({
	      id: MenuActions.unbindChat,
	      title: main_core.Loc.getMessage(title),
	      description: main_core.Loc.getMessage(description),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.TRASH_BIN,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permissionAction,
	      dataTestId: 'hr-department-detail-content__chat-list_unbind-chat'
	    });
	    this.chat = chat;
	  }
	  hasPermission(permissionChecker, entityId) {
	    return !this.chat.originalNodeId && this.chat.hasAccess && permissionChecker.hasPermission(this.permissionAction, entityId);
	  }
	}
	UnbindChatMenuItem.Dictionary = Object.freeze({
	  [humanresources_companyStructure_utils.EntityTypes.team]: {
	    [humanresources_companyStructure_utils.ChatTypes.chat]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHAT_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHAT_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit
	    },
	    [humanresources_companyStructure_utils.ChatTypes.channel]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHANNEL_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHANNEL_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit
	    },
	    [humanresources_companyStructure_utils.ChatTypes.collab]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_COLLAB_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_COLLAB_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit
	    }
	  },
	  [humanresources_companyStructure_utils.EntityTypes.department]: {
	    [humanresources_companyStructure_utils.ChatTypes.chat]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHAT_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHAT_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit
	    },
	    [humanresources_companyStructure_utils.ChatTypes.channel]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHANNEL_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHANNEL_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit
	    },
	    [humanresources_companyStructure_utils.ChatTypes.collab]: {
	      title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_COLLAB_TITLE',
	      description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_COLLAB_DESCRIPTION',
	      permissionAction: humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit
	    }
	  }
	});

	/**
	 * Action menu for chat or channel items in the company structure.
	 * Provides menu items to open or unbind a chat/channel.
	 */
	class ChatListActionMenu extends AbstractActionMenu {
	  constructor(entityType, chat, entityId) {
	    super(entityId);
	    this.chat = chat;
	    this.entityType = entityType;
	    this.items = this.getFilteredItems();
	  }
	  getItems() {
	    return [new OpenChatMenuItem(this.entityType, this.chat), new UnbindChatMenuItem(this.entityType, this.chat)];
	  }
	}

	let _$1 = t => t,
	  _t$1;
	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	var _getNotConvertedStateScreen = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNotConvertedStateScreen");
	class NotConvertedState {
	  static async mount(containerId) {
	    const container = document.getElementById(containerId);
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getNotConvertedStateScreen)[_getNotConvertedStateScreen](), container);
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEvents)[_subscribeOnEvents]();
	    const updateTimeMs = 30000;
	    this.updateConvertedStatusInterval = setInterval(async () => {
	      try {
	        await chartAPI.getDictionary();
	      } catch (e) {
	        if (e.code === 'STRUCTURE_IS_NOT_CONVERTED') {
	          return;
	        }
	        if (e.code === 'STRUCTURE_ACCESS_DENIED') {
	          clearInterval(this.updateConvertedStatusInterval);
	          window.location.reload();
	        }
	        throw e;
	      }
	      clearInterval(this.updateConvertedStatusInterval);
	      window.location.reload();
	    }, updateTimeMs);
	  }
	}
	function _subscribeOnEvents2() {
	  const onCloseByEsc = event => {
	    const [sidePanelEvent] = event.data;
	    sidePanelEvent.denyAction();
	  };
	  const onClose = () => {
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE, onClose);
	    clearInterval(this.updateConvertedStatusInterval);
	  };
	  main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
	  main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE, onClose);
	}
	function _getNotConvertedStateScreen2() {
	  return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="humanresources-not-converted-state-screen">
				<div class="humanresources-not-converted-state-screen__icon"></div>
				<div class="humanresources-not-converted-state-screen__title">
					${0}
				</div>
				<div class="humanresources-not-converted-state-screen__description">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NOT_CONVERTED_SCREEN_LOADING_TITLE'), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NOT_CONVERTED_SCREEN_LOADING_DESCRIPTION'));
	}
	Object.defineProperty(NotConvertedState, _getNotConvertedStateScreen, {
	  value: _getNotConvertedStateScreen2
	});
	Object.defineProperty(NotConvertedState, _subscribeOnEvents, {
	  value: _subscribeOnEvents2
	});

	var _subscribeOnEvents$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	class App {
	  static async mount(containerId) {
	    const container = document.getElementById(containerId);
	    const app = ui_vue3.BitrixVue.createApp(Chart);
	    const store = ui_vue3_pinia.createPinia();
	    app.use(store);
	    babelHelpers.classPrivateFieldLooseBase(App, _subscribeOnEvents$1)[_subscribeOnEvents$1](app);
	    const slider = BX.SidePanel.Instance.getTopSlider();
	    if (slider) {
	      slider.showLoader();
	    }
	    main_core.Dom.addClass(container, 'humanresources-chart__back');
	    await humanresources_companyStructure_permissionChecker.PermissionChecker.init();
	    if (slider) {
	      slider.closeLoader();
	    }
	    main_core.Dom.removeClass(container, 'humanresources-chart__back');
	    app.mount(container);
	  }
	}
	function _subscribeOnEvents2$1(app) {
	  const onCloseByEsc = event => {
	    const [sidePanelEvent] = event.data;
	    sidePanelEvent.denyAction();
	  };
	  const onClose = () => {
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
	    main_core_events.EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE, onClose);
	    app.unmount();
	  };
	  main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
	  main_core_events.EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE, onClose);
	}
	Object.defineProperty(App, _subscribeOnEvents$1, {
	  value: _subscribeOnEvents2$1
	});

	exports.App = App;
	exports.UsersTabActionMenu = UsersTabActionMenu;
	exports.EmptyUsersTabActionMenu = EmptyUsersTabActionMenu;
	exports.UserListActionMenu = UserListActionMenu;
	exports.ChatListActionMenu = ChatListActionMenu;
	exports.MenuActions = MenuActions;
	exports.NotConvertedState = NotConvertedState;
	exports.events = events;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3,BX.UI,BX.UI,BX.Humanresources.CompanyStructure,BX.UI.EntitySelector,BX.UI.Dialogs,BX,BX.Humanresources.CompanyStructure,BX.Vue3.Pinia,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX,BX,BX.UI,BX,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.Analytics,BX,BX.Humanresources.CompanyStructure,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX.Event,BX));
//# sourceMappingURL=org-chart.bundle.js.map

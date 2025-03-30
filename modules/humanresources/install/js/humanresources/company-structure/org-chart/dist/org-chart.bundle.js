/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3,ui_confetti,humanresources_companyStructure_canvas,ui_entitySelector,ui_dialogs_messagebox,ui_notification,main_core,main_sidepanel,main_core_events,humanresources_companyStructure_api,humanresources_companyStructure_departmentContent,humanresources_companyStructure_userManagementDialog,humanresources_companyStructure_structureComponents,ui_iconSet_api_core,ui_vue3_pinia,ui_iconSet_main,ui_iconSet_crm,ui_buttons,ui_forms,ui_iconSet_api_vue,humanresources_companyStructure_chartStore,humanresources_companyStructure_chartWizard,humanresources_companyStructure_utils,ui_analytics,ui_designTokens,humanresources_companyStructure_permissionChecker) {
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
	  HR_DEPARTMENT_SLIDER_ON_MESSAGE: 'SidePanel.Slider:onMessage'
	});

	const MenuOption = Object.freeze({
	  addDepartment: 'add-department',
	  addEmployee: 'add-employee'
	});
	const AddButton = {
	  name: 'AddButton',
	  emits: ['addDepartment'],
	  data() {
	    return {
	      actionMenu: {
	        visible: false
	      }
	    };
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
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
	  computed: {
	    MenuOption() {
	      return MenuOption;
	    }
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
		<div class="ui-btn ui-btn-success ui-btn-round ui-btn-sm" @click="addDepartment">
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON') }}
		</div>
	`
	};

	const OrgChartActions = {
	  applyData: (departments, currentDepartments, userId) => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.$patch({
	      departments,
	      currentDepartments,
	      userId,
	      searchedUserId: userId
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
	      currentDepartments
	    } = store;
	    const removableDepartment = departments.get(removableDepartmentId);
	    const {
	      parentId,
	      children: removableDeparmentChildren = [],
	      userCount: removableDepartmentUserCount,
	      heads: removableDeparmentHeads,
	      employees: removableDeparmentEmployees = []
	    } = removableDepartment;
	    removableDeparmentChildren.forEach(childId => {
	      const department = departments.get(childId);
	      department.parentId = parentId;
	    });
	    const parentDepartment = departments.get(parentId);
	    if (removableDepartmentUserCount > 0) {
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
	    if (currentDepartments.includes(removableDepartmentId)) {
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
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    departments.delete(departmentId);
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
	  }
	};

	const SearchBar = {
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
	  data() {
	    return {
	      canEditPermissions: false,
	      showSearchBar: false
	    };
	  },
	  created() {
	    this.searchDialog = this.getSearchDialog();
	  },
	  name: 'search-bar',
	  emits: ['locate'],
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
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
	            selectMode: 'onlyDepartments',
	            flatMode: true,
	            fillRecentTab: true
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
	            event.target.items.get('user').forEach(item => {
	              if (!item.getSubtitle()) {
	                item.setSubtitle(item.customData.get('position'));
	              }
	            });
	          },
	          'SearchTab:onLoad': event => {
	            event.target.items.get('user').forEach(item => {
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
	  watch: {
	    departments: {
	      handler() {
	        this.searchDialog.destroy();
	      },
	      deep: true
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
		          :placeholder="loc('HUMANRESOURCES_SEARCH_PLACEHOLDER')"
		          v-focus
		          class="humanresources-title-panel-search-bar-block__search-input"
		          @click="onEnter"
		          @input="search($event.target.value)"
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
	  accessRights: 'access-rights'
	});
	const BurgerMenuButton = {
	  name: 'BurgerMenuButton',
	  data() {
	    return {
	      actionMenu: {
	        visible: false
	      }
	    };
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  mounted() {
	    this.dropdownItems = [{
	      id: MenuOption$1.accessRights,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIG_PERMISSION_DESCR'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.SHIELD,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      }
	    }];
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    actionMenuItemClickHandler(actionId) {
	      if (actionId === MenuOption$1.accessRights) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: 'open_roles'
	        });
	        BX.SidePanel.Instance.open('/hr/config/permission/', {
	          usePadding: true
	        });
	      }
	    }
	  },
	  template: `
		<span
			ref="burgerMenuButton"
			@click="actionMenu.visible = true"
		>
			<svg
				viewBox="0 0 24 24"
				fill="none"
				class="humanresources-title-panel__icon"
				:class="{'--selected': actionMenu.visible }"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M18.7067 15.5577C18.8172 15.5577 18.9067 15.6473 18.9067 15.7577L18.9067 17.2424C18.9067 17.3528 18.8172 17.4424 18.7067 17.4424H5.29375C5.1833 17.4424 5.09375 17.3528 5.09375 17.2424L5.09379 15.7577C5.09379 15.6473 5.18333 15.5577 5.29379 15.5577H18.7067ZM18.7067 11.5577C18.8172 11.5577 18.9067 11.6473 18.9067 11.7577L18.9067 13.2424C18.9067 13.3528 18.8172 13.4424 18.7067 13.4424H5.29375C5.1833 13.4424 5.09375 13.3528 5.09375 13.2424L5.09379 11.7577C5.09379 11.6473 5.18333 11.5577 5.29379 11.5577H18.7067ZM18.7067 7.55774C18.8172 7.55774 18.9067 7.64729 18.9067 7.75774L18.9067 9.24238C18.9067 9.35284 18.8172 9.44238 18.7067 9.44238H5.29375C5.1833 9.44238 5.09375 9.35283 5.09375 9.24237L5.09379 7.75773C5.09379 7.64728 5.18333 7.55774 5.29379 7.55774H18.7067Z" fill="#525C69"/>
			</svg>
		 </span>
		<RouteActionMenu
			v-if="actionMenu.visible"
			id="title-panel-burger-menu"
			:items="dropdownItems"
			:bindElement="this.$refs.burgerMenuButton"
			@action="actionMenuItemClickHandler($event)"
			@close="this.actionMenu.visible = false"
		/>
	`
	};

	const TitlePanel = {
	  components: {
	    AddButton,
	    BurgerMenuButton,
	    SearchBar,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Set: ui_iconSet_api_vue.Set
	  },
	  data() {
	    return {
	      canEditPermissions: false,
	      canAddNode: false,
	      toolbarStarActive: false,
	      isHovered: false
	    };
	  },
	  created() {
	    this.toolbarStarElement = document.getElementById('uiToolbarStar');
	  },
	  mounted() {
	    try {
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      this.canEditPermissions = permissionChecker && permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.accessEdit);
	      this.canAddNode = permissionChecker && permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate);
	    } catch (error) {
	      console.error('Failed to fetch data:', error);
	    }
	    const observer = new MutationObserver(() => {
	      this.toolbarStarActive = main_core.Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
	    });
	    observer.observe(this.toolbarStarElement, {
	      attributes: true,
	      attributeFilter: ['class']
	    });
	    this.toolbarStarActive = main_core.Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
	  },
	  name: 'title-panel',
	  emits: ['showWizard', 'locate'],
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    toolbarStarIcon() {
	      return this.toolbarStarActive ? this.set.FAVORITE_1 : this.set.FAVORITE_0;
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
	      this.$emit('locate', nodeId);
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
		  <BIcon :name="isHovered ? set.FAVORITE_1 : toolbarStarIcon" :size="24" class="humanresources-title-panel__star"
		               @mouseover="isHovered = true"
		               @mouseleave="isHovered = false" @click="triggerFavoriteStar"
		  ></BIcon>
		  <div class="humanresources-title-panel__separator"></div>
		  <AddButton
			  v-if="canAddNode"
		      @addDepartment="addDepartment"
		  />
		  <div class="humanresources-title-panel__separator" v-if="canAddNode"></div>
		  <BurgerMenuButton v-if="canEditPermissions"/>
		  <div class="humanresources-title-panel__separator" v-if="canEditPermissions"></div>
		  <SearchBar @locate="onLocate"/>
		</div>
	`
	};

	const HeadList = {
	  name: 'headList',
	  components: {
	    UserListActionMenu: humanresources_companyStructure_structureComponents.UserListActionMenu
	  },
	  props: {
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
	    }
	  },
	  data() {
	    return {
	      isCollapsed: false,
	      isUpdating: true,
	      headsVisible: false
	    };
	  },
	  created() {
	    this.userTypes = {
	      head: 'head',
	      deputy: 'deputy'
	    };
	  },
	  computed: {
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    dropdownItems() {
	      return this.items.map(item => {
	        const workPosition = item.workPosition || this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_HEAD_POSITION');
	        return {
	          ...item,
	          workPosition
	        };
	      });
	    },
	    titleBar() {
	      return this.type === this.userTypes.deputy ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE');
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    handleUserClick(url) {
	      main_sidepanel.SidePanel.Instance.open(url, {
	        cacheable: false
	      });
	    },
	    closeHeadList() {
	      this.headsVisible = false;
	      main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
	    },
	    openHeadList() {
	      this.headsVisible = true;
	      main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeHeadList);
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
					<span
						:bx-tooltip-user-id="item.id"
						class="humanresources-tree__node_head-name"
						@click.stop="handleUserClick(item.url)"
					>
						{{ item.name }}
					</span>
					<span v-if="!collapsed" class="humanresources-tree__node_head-position">
						{{ item.workPosition || loc('HUMANRESOURCES_COMPANY_STRUCTURE_HEAD_POSITION') }}
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

	const MenuActions = Object.freeze({
	  editDepartment: 'editDepartment',
	  addDepartment: 'addDepartment',
	  editEmployee: 'editEmployee',
	  moveEmployee: 'moveEmployee',
	  addEmployee: 'addEmployee',
	  userInvite: 'userInvite',
	  removeDepartment: 'removeDepartment'
	});
	const DepartmentMenuButton = {
	  name: 'DepartmentMenuButton',
	  emits: ['addDepartment', 'editDepartment', 'moveEmployee', 'addEmployee', 'removeDepartment', 'editEmployee', 'userInvite'],
	  props: {
	    departmentId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  created() {
	    this.menuItems = [];
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (!this.permissionChecker) {
	      return;
	    }
	    this.menuItems = [{
	      id: MenuActions.editDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.EDIT_PENCIL,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit
	      }
	    }, {
	      id: MenuActions.addDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.CUBE_PLUS,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate
	      }
	    }, {
	      id: MenuActions.editEmployee,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE'),
	      imageClass: '-hr-department-org-chart-menu-edit-list',
	      bIcon: {
	        name: ui_iconSet_api_core.Main.EDIT_MENU,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	      }
	    }, {
	      id: MenuActions.moveEmployee,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	      }
	    }, {
	      id: MenuActions.userInvite,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.PERSON_LETTER,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.inviteToDepartment
	      }
	    }, {
	      id: MenuActions.addEmployee,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	      }
	    }, {
	      id: MenuActions.removeDepartment,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE'),
	      bIcon: {
	        name: ui_iconSet_api_core.Main.TRASH_BIN,
	        size: 20,
	        color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	      },
	      permission: {
	        action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentDelete
	      }
	    }];
	    this.menuItems = this.menuItems.filter(item => {
	      if (!item.permission) {
	        return false;
	      }
	      return this.permissionChecker.hasPermission(item.permission.action, this.departmentId);
	    });
	  },
	  data() {
	    return {
	      menu: {
	        visible: false
	      }
	    };
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      this.$emit(actionId, actionId);
	    },
	    closeMenu() {
	      this.menu.visible = false;
	      main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
	    },
	    openMenu() {
	      this.menu.visible = true;
	      main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.closeMenu);
	    }
	  },
	  template: `
		<div
			v-if="menuItems.length"
			class="ui-icon-set --more humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.menu.visible }"
			ref="departmentMenuButton"
			@click.stop="openMenu"
		>
		</div>

		<RouteActionMenu
			v-if="menu.visible"
			:id="'tree-node-department-menu-' + departmentId"
			:width="302"
			:items="menuItems"
			:bindElement="this.$refs.departmentMenuButton"
			@action="onActionMenuItemClick"
			@close="closeMenu"
		/>
	`
	};

	const SubdivisionAddButton = {
	  name: 'SubdivisionAddButton',
	  emits: ['addDepartment'],
	  props: {
	    departmentId: {
	      type: Number,
	      required: true
	    }
	  },
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  created() {
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.canShow = permissionChecker && permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, this.departmentId);
	  },
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    addSubdivision(event) {
	      this.$emit('addDepartment');
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

	const TreeNode = {
	  name: 'treeNode',
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
	    zoom: {
	      type: Number,
	      required: true
	    },
	    currentDepartment: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['calculatePosition'],
	  components: {
	    DepartmentMenuButton,
	    HeadList,
	    SubdivisionAddButton
	  },
	  directives: {
	    hint: humanresources_companyStructure_structureComponents.Hint
	  },
	  data() {
	    return {
	      childrenOffset: 0,
	      childrenMounted: false,
	      showInfo: true
	    };
	  },
	  created() {
	    this.width = 278;
	    this.gap = 20;
	    this.prevChildrenOffset = 0;
	    this.prevHeight = 0;
	  },
	  watch: {
	    async head() {
	      await this.$nextTick();
	      main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT, {
	        nodeId: this.nodeId,
	        shift: this.$el.offsetHeight - this.prevHeight
	      });
	      this.prevHeight = this.$el.offsetHeight;
	    }
	  },
	  async mounted() {
	    this.showInfo = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance().hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.structureView, this.nodeId);
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
	  computed: {
	    nodeData() {
	      return this.departments.get(this.nodeId);
	    },
	    nodeClass() {
	      return {
	        '--expanded': this.expandedNodes.includes(this.nodeId),
	        '--current-department': this.isCurrentDepartment,
	        '--focused': this.focusedNode === this.nodeId,
	        '--with-restricted-access-rights': !this.showInfo
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
	      const isExpanded = this.expandedNodes.includes(this.nodeId);
	      if (isExpanded) {
	        this.childrenMounted = true;
	      }
	      return isExpanded;
	    },
	    isCurrentDepartment() {
	      return this.currentDepartment === this.nodeId;
	    },
	    head() {
	      var _this$nodeData$heads;
	      return (_this$nodeData$heads = this.nodeData.heads) == null ? void 0 : _this$nodeData$heads.filter(head => {
	        return head.role === humanresources_companyStructure_api.memberRoles.head;
	      });
	    },
	    deputy() {
	      var _this$nodeData$heads2;
	      return (_this$nodeData$heads2 = this.nodeData.heads) == null ? void 0 : _this$nodeData$heads2.filter(head => {
	        return head.role === humanresources_companyStructure_api.memberRoles.deputyHead;
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
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'focusedNode'])
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
	    controlDepartment(action, source = humanresources_companyStructure_api.AnalyticsSourceType.CARD) {
	      main_core_events.EventEmitter.emit(events.HR_DEPARTMENT_CONTROL, {
	        nodeId: this.nodeId,
	        action,
	        source
	      });
	    },
	    addEmployee() {
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId: this.nodeId,
	        type: 'add'
	      });
	    },
	    userInvite() {
	      const departmentToInvite = this.departments.get(this.nodeId).accessCode.slice(1);
	      BX.SidePanel.Instance.open('/bitrix/services/main/ajax.php?action=getSliderContent' + '&c=bitrix%3Aintranet.invitation&mode=ajax' + `&departments[]=${departmentToInvite}&firstInvitationBlock=invite-with-group-dp`, {
	        cacheable: false,
	        allowChangeHistory: false,
	        width: 1100
	      });
	    },
	    moveEmployee() {
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId: this.nodeId,
	        type: 'move'
	      });
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
	          x: (parentLeft - chartX + parentWidth / 2) / this.zoom,
	          y: (parentTop - chartY + parentHeight) / this.zoom
	        },
	        endPoint: {
	          x: (left - chartX + width / 2) / this.zoom,
	          y: (top - chartY) / this.zoom
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
	    }
	  },
	  template: `
		<div
			:data-id="nodeId"
			:class="nodeClass"
			:data-title="isCurrentDepartment ? loc('HUMANRESOURCES_COMPANY_CURRENT_DEPARTMENT') : null"
			class="humanresources-tree__node"
		>
			<div
				class="humanresources-tree__node_summary"
				@click.stop="onDepartmentClick('department')"
			>
				<div class="humanresources-tree__node_description">
					<div class="humanresources-tree__node_department">
						<span class="humanresources-tree__node_department-title">
							<span
								v-hint
								class="humanresources-tree__node_department-title_text"
							>
								{{nodeData.name}}
							</span>
						</span>
						<DepartmentMenuButton
							v-if="showInfo && head && deputy"
							:department-id="nodeId"
							@addDepartment="controlDepartment"
							@editDepartment="controlDepartment"
							@editEmployee="controlDepartment"
							@removeDepartment="controlDepartment"
							@addEmployee="addEmployee"
							@userInvite="userInvite"
							@moveEmployee="moveEmployee"
						></DepartmentMenuButton>
					</div>
				  	<HeadList v-if="head && showInfo" :items="head"></HeadList>
					<div
						v-else-if="showInfo"
						class="humanresources-tree__node_load-skeleton --heads"
					></div>
					<div v-if="deputy && showInfo" class="humanresources-tree__node_employees">
						<div>
							<p class="humanresources-tree__node_employees-title">
								{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_TITLE')}}
							</p>
							<span
								class="humanresources-tree__node_employees-count"
								@click.stop="onDepartmentClick('employees')"
							>
								{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_COUNT', employeeCount)}}
							</span>
						</div>
						<div v-if="!deputy.length"></div>
						<HeadList :items="deputy"
								  :title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_DEPUTY_TITLE')" 
								  :collapsed="true"
								  :type="'deputy'">
						</HeadList>
					</div>
					<div
						v-else-if="showInfo"
						class="humanresources-tree__node_load-skeleton --deputies"
					></div>
				</div>
				<div
					class="humanresources-tree__node_subdivisions"
					:class="subdivisionsClass"
					v-if="showInfo"
					@click.stop="onDepartmentClick('subdivisions')"
				>
					<span>
						{{
							nodeData.children?.length ?
								locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', nodeData.children.length) :
								loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS')
						}}
					</span>
				</div>
			  	<SubdivisionAddButton
					v-if="showSubdivisionAddButton"
					@addDepartment="controlDepartment('addDepartment', 'plus')"
					:department-id="nodeId"
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
						:zoom="zoom"
						:currentDepartment="currentDepartment"
						@calculatePosition="calculatePosition"
					/>
				</TransitionGroup>
			</div>
		</div>
	`
	};

	const createTreeDataStore = treeData => {
	  const dataMap = new Map();
	  treeData.forEach(item => {
	    var _dataMap$get, _dataMap$get2, _mapParentItem$childr;
	    const {
	      id,
	      parentId
	    } = item;
	    const mapItem = (_dataMap$get = dataMap.get(id)) != null ? _dataMap$get : {};
	    dataMap.set(id, {
	      ...mapItem,
	      ...item
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
	  createTreeDataStore
	};

	const Tree = {
	  name: 'tree',
	  components: {
	    TreeNode
	  },
	  props: {
	    zoom: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['moveTo', 'showWizard', 'controlDetail'],
	  data() {
	    return {
	      connectors: {},
	      expandedNodes: []
	    };
	  },
	  created() {
	    this.treeNodes = new Map();
	    this.subscribeOnEvents();
	    this.loadHeads([this.rootId]);
	    this.prevWindowWidth = window.innerWidth;
	    const [currentDepartment] = this.currentDepartments;
	    if (!currentDepartment) {
	      return;
	    }
	    if (currentDepartment !== this.rootId) {
	      this.expandDepartmentParents(currentDepartment);
	      this.focus(currentDepartment, {
	        expandAfterFocus: true
	      });
	      return;
	    }
	    this.expandLowerDepartments();
	    this.focus(currentDepartment);
	  },
	  beforeUnmount() {
	    this.unsubscribeOnEvents();
	  },
	  provide() {
	    return {
	      getTreeBounds: () => this.getTreeBounds()
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
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['currentDepartments', 'userId', 'focusedNode', 'departments'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    getTreeBounds() {
	      return this.$el.getBoundingClientRect();
	    },
	    getPath(id) {
	      const connector = this.connectors[id];
	      const {
	        startPoint,
	        endPoint
	      } = connector;
	      if (!startPoint || !endPoint) {
	        return '';
	      }
	      const lineLength = 90;
	      const shiftY = 1;
	      const startY = startPoint.y - shiftY;
	      const shadowOffset = this.focusedNode === connector.id ? 9 : 0;
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
	      return [`M${startPoint.x} ${startY}`, `V${startY + lineLength}`, `${String(rounded.start)}`, `H${endPoint.x + arcRadius}`, `${String(rounded.end)}`, `V${adjustedEndY}`].join('');
	    },
	    onConnectDepartment({
	      data
	    }) {
	      var _this$connectors;
	      const {
	        id,
	        parentId,
	        html
	      } = data;
	      this.treeNodes.set(id, html);
	      const [currentDepartment] = this.currentDepartments;
	      if (id === currentDepartment) {
	        setTimeout(() => {
	          this.moveTo(currentDepartment);
	        }, 1800);
	      }
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
	    onDisconnectDepartment({
	      data
	    }) {
	      const {
	        id,
	        parentId
	      } = data;
	      delete this.connectors[`${parentId}-${id}`];
	      const department = this.departments.get(id);
	      delete department.prevParentId;
	      if (!department.parentId) {
	        OrgChartActions.removeDepartment(id);
	      }
	    },
	    onAdaptSiblings({
	      data
	    }) {
	      const {
	        nodeId,
	        parentId,
	        offset
	      } = data;
	      const parentDepartment = this.departments.get(parentId);
	      if (parentDepartment.children.includes(nodeId)) {
	        this.adaptConnectorsAfterMount(parentId, nodeId, offset);
	        return;
	      }
	      this.adaptConnectorsAfterUnmount(parentId, nodeId, offset);
	    },
	    adaptConnectorsAfterMount(parentId, nodeId, offset) {
	      Object.entries(this.connectors).forEach(([key, connector]) => {
	        if (!connector.id) {
	          return;
	        }
	        if (connector.parentId === parentId) {
	          const {
	            x
	          } = connector.endPoint;
	          Object.assign(connector.endPoint, {
	            x: x + offset
	          });
	          return;
	        }
	        if (connector.parentsPath.includes(parentId)) {
	          const {
	            startPoint: currentStartPoint,
	            endPoint
	          } = connector;
	          Object.assign(currentStartPoint, {
	            x: currentStartPoint.x + offset
	          });
	          Object.assign(endPoint, {
	            x: endPoint.x + offset
	          });
	        }
	      });
	    },
	    adaptConnectorsAfterUnmount(parentId, nodeId, offset) {
	      const entries = Object.entries(this.connectors);
	      const {
	        endPoint
	      } = this.connectors[`${parentId}-${nodeId}`];
	      const parsedSiblingConnectors = entries.reduce((acc, [key, connector]) => {
	        const {
	          endPoint: currentEndPoint,
	          id,
	          parentId: currentParentId
	        } = connector;
	        if (currentParentId !== parentId || id === nodeId) {
	          return acc;
	        }
	        const sign = endPoint.x > currentEndPoint.x ? 1 : -1;
	        return {
	          ...acc,
	          [id]: sign
	        };
	      }, {});
	      entries.forEach(([key, connector]) => {
	        const {
	          id: currentId,
	          parentId: currentParentId,
	          parentsPath,
	          endPoint: currentEndPoint,
	          startPoint: currentStartPoint
	        } = connector;
	        if (currentId === nodeId) {
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
	    onAdaptConnectorHeight({
	      data
	    }) {
	      const {
	        shift,
	        nodeId
	      } = data;
	      Object.entries(this.connectors).forEach(([id, connector]) => {
	        if (connector.parentId === nodeId) {
	          Object.assign(connector.startPoint, {
	            y: connector.startPoint.y + shift
	          });
	        }
	      });
	    },
	    collapse(nodeId) {
	      this.expandedNodes = this.expandedNodes.filter(expandedId => expandedId !== nodeId);
	      this.toggleConnectorsVisibility(nodeId, false);
	      this.toggleConnectorHighlighting(nodeId, false);
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
	    expand(departmentId) {
	      this.collapseRecursively(departmentId);
	      this.expandedNodes = [...this.expandedNodes, departmentId];
	      this.toggleConnectorsVisibility(departmentId, true);
	      this.toggleConnectorHighlighting(departmentId, true);
	      const department = this.departments.get(departmentId);
	      const childrenWithoutHeads = department.children.filter(childId => {
	        return !this.departments.get(childId).heads;
	      });
	      if (childrenWithoutHeads.length > 0) {
	        this.loadHeads(childrenWithoutHeads);
	      }
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'structure',
	        event: 'expand_department'
	      });
	    },
	    focus(nodeId, options = {}) {
	      var _this$departments$get;
	      const {
	        expandAfterFocus = false,
	        showEmployees = false,
	        subdivisionsSelected = false
	      } = options;
	      const hasChildren = ((_this$departments$get = this.departments.get(nodeId).children) == null ? void 0 : _this$departments$get.length) > 0;
	      let shouldExpand = expandAfterFocus || !this.expandedNodes.includes(nodeId);
	      if (showEmployees) {
	        shouldExpand = this.expandedNodes.includes(nodeId);
	      }
	      if (subdivisionsSelected || !hasChildren) {
	        this.collapseRecursively(nodeId);
	      }
	      if (hasChildren && shouldExpand) {
	        this.expand(nodeId);
	      }
	      if (this.focusedNode && !this.expandedNodes.includes(this.focusedNode)) {
	        this.toggleConnectorHighlighting(this.focusedNode, false);
	      }
	      OrgChartActions.focusDepartment(nodeId);
	      this.toggleConnectorHighlighting(this.focusedNode, true);
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
	    onControlDepartment({
	      data
	    }) {
	      const {
	        action,
	        nodeId,
	        source
	      } = data;
	      const isEditMode = action === MenuActions.editDepartment || action === MenuActions.editEmployee;
	      if (isEditMode) {
	        const type = action === MenuActions.editDepartment ? 'department' : 'employees';
	        this.$emit('showWizard', {
	          nodeId,
	          isEditMode: true,
	          type,
	          source
	        });
	        return;
	      }
	      if (action === MenuActions.addDepartment) {
	        this.$emit('showWizard', {
	          nodeId,
	          isEditMode: false,
	          showEntitySelector: false,
	          source
	        });
	        return;
	      }
	      this.tryRemoveDepartment(nodeId);
	    },
	    tryRemoveDepartment(nodeId) {
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_TITLE'),
	        message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_MESSAGE'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	        onOk: async dialog => {
	          try {
	            await this.removeDepartment(nodeId);
	            ui_notification.UI.Notification.Center.notify({
	              content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_REMOVED'),
	              autoHideDelay: 2000
	            });
	            dialog.close();
	          } catch {
	            ui_notification.UI.Notification.Center.notify({
	              content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_ERROR'),
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
	      await chartAPI.removeDepartment(nodeId);
	      const removableDepartment = this.departments.get(nodeId);
	      const {
	        parentId,
	        children: removableDeparmentChildren = []
	      } = removableDepartment;
	      if (removableDeparmentChildren.length > 0) {
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
	        if (this.expandedNodes.includes(childId)) {
	          this.toggleConnectorsVisibility(childId, show);
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
	        var _this$departments$get2;
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
	        if (((_this$departments$get2 = this.departments.get(childId).children) == null ? void 0 : _this$departments$get2.length) > 0) {
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
	    locateToCurrentDepartment() {
	      const [currentDepartment] = this.currentDepartments;
	      if (!currentDepartment) {
	        return;
	      }
	      this.expandDepartmentParents(currentDepartment);
	      this.focus(currentDepartment, {
	        expandAfterFocus: true
	      });
	      this.moveTo(currentDepartment);
	      OrgChartActions.searchUserInDepartment(this.userId);
	    },
	    async locateToDepartment(nodeId) {
	      await this.expandDepartmentParents(nodeId);
	      await this.focus(nodeId, {
	        expandAfterFocus: true
	      });
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
	        [events.HR_DEPARTMENT_CONTROL]: this.onControlDepartment,
	        [events.HR_DEPARTMENT_ADAPT_SIBLINGS]: this.onAdaptSiblings,
	        [events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT]: this.onAdaptConnectorHeight
	      };
	      Object.entries(this.events).forEach(([event, handle]) => {
	        main_core_events.EventEmitter.subscribe(event, handle);
	      });
	      main_core.Event.bind(window, 'resize', this.onResizeWindow);
	    },
	    unsubscribeOnEvents() {
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
		<div
			class="humanresources-tree"
			v-if="departments.size > 0"
		>
			<TreeNode
				class="--root"
				:key="rootId"
				:nodeId="rootId"
				:expandedNodes="[...expandedNodes]"
				:zoom="zoom"
				:currentDepartment="currentDepartments[0]"
			/>
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
		</div>
	`
	};

	const TransformPanel = {
	  name: 'transform-panel',
	  props: {
	    modelValue: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['locate', 'update:modelValue'],
	  data() {
	    return {
	      selectedId: ''
	    };
	  },
	  created() {
	    this.actions = Object.freeze({
	      zoomIn: 'zoomIn',
	      zoomOut: 'zoomOut',
	      locate: 'locate',
	      navigate: 'navigate'
	    });
	  },
	  computed: {
	    zoomInPercent() {
	      const percent = '<span class="humanresources-transform-panel__zoom_percent">%</span>';
	      return `${(this.modelValue.zoom * 100).toFixed(0)}${percent}`;
	    }
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
	      this.$emit('update:modelValue', {
	        ...this.modelValue,
	        zoom
	      });
	    },
	    onLocate() {
	      const {
	        locate
	      } = this.actions;
	      this.$emit(locate);
	      this.selectedId = locate;
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

	const MenuOption$2 = Object.freeze({
	  editDepartment: 'editDepartment',
	  addDepartment: 'addDepartment',
	  editEmployee: 'editEmployee',
	  moveEmployee: 'moveEmployee',
	  userInvite: 'userInvite',
	  addEmployee: 'addEmployee',
	  removeDepartment: 'removeDepartment'
	});
	const DetailPanelEditButton = {
	  name: 'detailPanelEditButton',
	  emits: ['editDepartment', 'addDepartment', 'editEmployee', 'addEmployee', 'removeDepartment', 'moveEmployee', 'userInvite'],
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
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
	      this.$emit(actionId, {
	        role: this.role,
	        bindElement: this.$refs.detailPanelEditButton
	      });
	    }
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['focusedNode']),
	    set() {
	      return ui_iconSet_api_vue.Set;
	    },
	    menuItems() {
	      if (!this.permissionChecker) {
	        return [];
	      }
	      return [{
	        id: MenuOption$2.editDepartment,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_DEPARTMENT_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.EDIT_PENCIL,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit
	        }
	      }, {
	        id: MenuOption$2.addDepartment,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_DEPARTMENT_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.CUBE_PLUS,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate
	        }
	      }, {
	        id: MenuOption$2.editEmployee,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_EDIT_EMPLOYEE_LIST_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.EDIT_MENU,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }, {
	        id: MenuOption$2.moveEmployee,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_MOVE_EMPLOYEE_SUBTITLE'),
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
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.PERSON_LETTER,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.inviteToDepartment
	        }
	      }, {
	        id: MenuOption$2.addEmployee,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_ADD_EMPLOYEE_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment
	        }
	      }, {
	        id: MenuOption$2.removeDepartment,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_REMOVE_DEPARTMENT_SUBTITLE'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.TRASH_BIN,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteRed40')
	        },
	        permission: {
	          action: humanresources_companyStructure_permissionChecker.PermissionActions.departmentDelete
	        }
	      }].filter(item => {
	        if (!item.permission) {
	          return false;
	        }
	        return this.permissionChecker.hasPermission(item.permission.action, this.focusedNode);
	      });
	    }
	  },
	  template: `
		<div
			v-if="menuItems.length"
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
			:items="menuItems"
			:width="302"
			:bindElement="$refs.detailPanelEditButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	const DetailPanel = {
	  name: 'detailPanel',
	  emits: ['showWizard', 'removeDepartment', 'update:modelValue'],
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
	  data() {
	    return {
	      title: '',
	      isCollapsed: true,
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
	      return (_heads$filter$map = heads == null ? void 0 : (_heads$filter = heads.filter(employee => employee.role === humanresources_companyStructure_api.memberRoles.head)) == null ? void 0 : _heads$filter.map(employee => employee.avatar || this.defaultAvatar)) != null ? _heads$filter$map : [];
	    }
	  },
	  methods: {
	    toggleCollapse() {
	      this.$emit('update:modelValue', !this.isCollapsed);
	    },
	    updateDetailPageHandler(nodeId, oldId) {
	      var _department$name;
	      if (!this.preventPanelSwitch && oldId !== 0) {
	        this.$emit('update:modelValue', false);
	      }
	      this.isLoading = true;
	      const department = this.departments.get(nodeId);
	      this.title = (_department$name = department.name) != null ? _department$name : '';
	      this.isLoading = false;
	    },
	    addEmployee() {
	      const nodeId = this.focusedNode;
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId,
	        type: 'add'
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
	    moveEmployee() {
	      const nodeId = this.focusedNode;
	      humanresources_companyStructure_userManagementDialog.UserManagementDialog.openDialog({
	        nodeId,
	        type: 'move'
	      });
	    },
	    editEmployee() {
	      this.$emit('showWizard', {
	        nodeId: this.focusedNode,
	        isEditMode: true,
	        type: 'employees',
	        source: humanresources_companyStructure_api.AnalyticsSourceType.DETAIL
	      });
	    },
	    editDepartment() {
	      this.$emit('showWizard', {
	        nodeId: this.focusedNode,
	        isEditMode: true,
	        type: 'department',
	        source: humanresources_companyStructure_api.AnalyticsSourceType.DETAIL
	      });
	    },
	    addDepartment() {
	      this.$emit('showWizard', {
	        nodeId: this.focusedNode,
	        isEditMode: false,
	        showEntitySelector: false,
	        source: humanresources_companyStructure_api.AnalyticsSourceType.DETAIL
	      });
	    },
	    removeDepartment() {
	      this.$emit('removeDepartment', this.focusedNode);
	    },
	    showLoader() {
	      this.needToShowLoader = true;
	    },
	    hideLoader() {
	      this.needToShowLoader = false;
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
	          var _department$name2;
	          this.title = (_department$name2 = department.name) != null ? _department$name2 : '';
	        }
	      },
	      deep: true
	    }
	  },
	  template: `
		<div
			:class="['humanresources-detail-panel', { '--collapsed': isCollapsed }]"
			v-on="isCollapsed ? { click: toggleCollapse } : {}"
			data-id="hr-department-detail-panel__container"
		>
			<div
				v-if="!isLoading"
				class="humanresources-detail-panel-container"
				:class="{ '--hide': needToShowLoader && !isCollapsed }"
			>
				<div class="humanresources-detail-panel__head">
					<span
						v-if="!isCollapsed"
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
							v-if="!isCollapsed"
							@addEmployee="addEmployee"
							@editEmployee="editEmployee"
							@editDepartment="editDepartment"
							@addDepartment="addDepartment"
							@moveEmployee="moveEmployee"
							@removeDepartment="removeDepartment"
							@userInvite="userInvite"
						/>
						<div
							class="humanresources-detail-panel__collapse_button --icon"
							@click="toggleCollapse"
							:class="{ '--collapsed': isCollapsed }"
							data-id="hr-department-detail-panel__collapse-button"
						/>
					</div>
				</div>
				<div class="humanresources-detail-panel__content" v-show="!isCollapsed">
					<DepartmentContent
						@editEmployee="editEmployee"
						@showDetailLoader="showLoader"
						@hideDetailLoader="hideLoader"
					/>
				</div>
			</div>
			<div v-if="needToShowLoader && !isCollapsed" class="humanresources-detail-panel-loader-container"/>
		</div>
	`
	};

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
	  computed: {
	    set() {
	      return ui_iconSet_api_vue.Set;
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

	const Chart = {
	  components: {
	    TransformCanvas: humanresources_companyStructure_canvas.TransformCanvas,
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
	        moving: false,
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
	      }
	    };
	  },
	  async created() {
	    const slider = BX.SidePanel.Instance.getTopSlider();
	    slider == null ? void 0 : slider.showLoader();
	    const [departments, currentDepartments, userId] = await Promise.all([chartAPI.getDepartmentsData(), chartAPI.getCurrentDepartments(), chartAPI.getUserId()]);
	    slider == null ? void 0 : slider.closeLoader();
	    const parsedDepartments = chartAPI.createTreeDataStore(departments);
	    OrgChartActions.applyData(parsedDepartments, currentDepartments, userId);
	    this.rootOffset = 100;
	    this.transformCanvas();
	    this.canvas.shown = true;
	    this.showConfetti = false;
	    main_core_events.EventEmitter.subscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
	  },
	  unmounted() {
	    main_core_events.EventEmitter.unsubscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
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
	      const detailPanelWidth = 364 * zoom;
	      const newX = x - detailPanelWidth / 2;
	      const newY = nodeId === this.rootId ? this.rootOffset : y / zoom;
	      const notSamePoint = Math.round(newX) !== Math.round(prevX) || Math.round(y) !== Math.round(prevY);
	      const shouldMove = notSamePoint && !this.canvas.moving;
	      this.detailPanel = {
	        ...this.detailPanel,
	        collapsed: false
	      };
	      if (!shouldMove) {
	        return;
	      }
	      this.canvas = {
	        ...this.canvas,
	        moving: true,
	        modelTransform: {
	          ...this.canvas.modelTransform,
	          x: newX / zoom,
	          y: newY,
	          zoom: 1
	        }
	      };
	    },
	    onLocate(nodeId) {
	      if (nodeId) {
	        this.$refs.tree.locateToDepartment(nodeId);
	        return;
	      }
	      this.$refs.tree.locateToCurrentDepartment();
	    },
	    onShowWizard({
	      nodeId = 0,
	      isEditMode = false,
	      type,
	      showEntitySelector = true,
	      source = ''
	    } = {}) {
	      this.wizard = {
	        ...this.wizard,
	        shown: true,
	        isEditMode,
	        showEntitySelector,
	        entity: type,
	        nodeId,
	        source
	      };
	      if (!isEditMode && source !== humanresources_companyStructure_api.AnalyticsSourceType.HEADER) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: 'create_dept_step1',
	          c_element: source
	        });
	      }

	      // eslint-disable-next-line default-case
	      switch (type) {
	        case 'department':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step1',
	            c_element: source
	          });
	          break;
	        case 'employees':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step2',
	            c_element: source
	          });
	          break;
	        case 'bindChat':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step3',
	            c_element: source
	          });
	          break;
	      }
	    },
	    async onModifyTree({
	      id,
	      parentId,
	      showConfetti
	    }) {
	      this.showConfetti = showConfetti != null ? showConfetti : false;
	      const {
	        tree
	      } = this.$refs;
	      tree.expandDepartmentParents(id);
	      tree.focus(id, {
	        expandAfterFocus: true
	      });
	      await this.$nextTick();
	      tree.moveTo(id);
	    },
	    onWizardClose() {
	      this.wizard.shown = false;
	    },
	    onRemoveDepartment(nodeId) {
	      const {
	        tree
	      } = this.$refs;
	      tree.tryRemoveDepartment(nodeId);
	    },
	    onTransitionEnd() {
	      this.canvas.moving = false;
	      if (this.showConfetti) {
	        ui_confetti.Confetti.fire({
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
	      }
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
	    }
	  },
	  template: `
		<div class="humanresources-chart">
			<TitlePanel @showWizard="onShowWizard" @locate="onLocate"></TitlePanel>
			<TransformCanvas
				v-if="canvas.shown"
				v-slot="{transform}"
				v-model="canvas.modelTransform"
				@update:modelValue="onUpdateTransform"
				:class="{ '--moving': canvas.moving }"
				@transitionend="onTransitionEnd"
			>
				<Tree
					:zoom="transform.zoom"
					ref="tree"
					@moveTo="onMoveTo"
					@showWizard="onShowWizard"
					@controlDetail="onControlDetail"
				/>
			</TransformCanvas>
			<DetailPanel
				@showWizard="onShowWizard"
				@removeDepartment="onRemoveDepartment"
				v-model="detailPanel.collapsed"
				:preventPanelSwitch="detailPanel.preventSwitch"
			></DetailPanel>
			<TransformPanel
				v-model="canvas.modelTransform"
				@locate="onLocate"
			></TransformPanel>
			<ChartWizard
				v-if="wizard.shown"
				:nodeId="wizard.nodeId"
				:isEditMode="wizard.isEditMode"
				:showEntitySelector="wizard.showEntitySelector"
				:entity="wizard.entity"
				:source="wizard.source"
				@modifyTree="onModifyTree"
				@close="onWizardClose"
			></ChartWizard>
			<FirstPopup/>
			<div class="humanresources-chart__back"></div>
		</div>
	`
	};

	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	class App {
	  static async mount(containerId) {
	    const container = document.getElementById(containerId);
	    const app = ui_vue3.BitrixVue.createApp(Chart);
	    const store = ui_vue3_pinia.createPinia();
	    app.use(store);
	    babelHelpers.classPrivateFieldLooseBase(App, _subscribeOnEvents)[_subscribeOnEvents](app);
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
	function _subscribeOnEvents2(app) {
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
	Object.defineProperty(App, _subscribeOnEvents, {
	  value: _subscribeOnEvents2
	});

	exports.App = App;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3,BX.UI,BX.Humanresources.CompanyStructure,BX.UI.EntitySelector,BX.UI.Dialogs,BX,BX,BX,BX.Event,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.IconSet,BX.Vue3.Pinia,BX,BX,BX.UI,BX,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.Analytics,BX,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=org-chart.bundle.js.map

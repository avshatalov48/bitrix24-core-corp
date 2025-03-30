/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3_pinia,main_loader,main_core,humanresources_companyStructure_permissionChecker,humanresources_companyStructure_structureComponents,ui_iconSet_api_core,ui_iconSet_crm,humanresources_companyStructure_utils,ui_entitySelector,humanresources_companyStructure_api,humanresources_companyStructure_chartStore,ui_analytics) {
	'use strict';

	const HeadUsers = {
	  name: 'headUsers',
	  components: {
	    UserListActionMenu: humanresources_companyStructure_structureComponents.UserListActionMenu
	  },
	  props: {
	    users: {
	      type: Array,
	      required: true
	    },
	    showPlaceholder: {
	      type: Boolean,
	      default: true
	    },
	    userType: String
	  },
	  data() {
	    return {
	      headsVisible: false
	    };
	  },
	  created() {
	    this.headItemsCount = 2;
	    this.userTypes = {
	      head: 'head',
	      deputy: 'deputy'
	    };
	  },
	  computed: {
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    placeholderAvatar() {
	      return '/bitrix/js/humanresources/company-structure/chart-wizard/src/components/tree-preview/images/placeholder-avatar.svg';
	    },
	    dropdownItems() {
	      return this.users.map(user => {
	        const workPosition = user.workPosition || this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION');
	        return {
	          ...user,
	          workPosition
	        };
	      });
	    },
	    titleBar() {
	      return this.userType === this.userTypes.deputy ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE');
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div
			class="chart-wizard-tree-preview__node_head"
			v-for="(user, index) in users.slice(0, headItemsCount)"
		>
			<img
				:src="user.avatar || defaultAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{ '--deputy': userType === userTypes.deputy }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text">
				<span class="chart-wizard-tree-preview__node_head-name --crop">
					{{user.name}}
				</span>
				<span v-if="userType !== userTypes.deputy" class="chart-wizard-tree-preview__node_head-position --crop">
					{{user.workPosition || loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION')}}
				</span>
			</div>
			<span
				v-if="index === 1 && users.length > 2"
				class="chart-wizard-tree-preview__node_head-rest"
				:class="{ '--active': headsVisible }"
				ref="showMoreHeadUserWizardList"
				:data-test-id="'hr-company-structure_chart-wizard-tree__preview-' + type + '-rest'"
				@click.stop="headsVisible = true"
			>
					{{'+' + String(users.length - 2)}}
			</span>
		</div>
		<div
			v-if="users.length === 0 && showPlaceholder"
			class="chart-wizard-tree-preview__node_head"
		>
			<img
				:src="placeholderAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{'--deputy': userType === userTypes.deputy }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text">
				<span class="chart-wizard-tree-preview__placeholder_name"></span>
				<span
					v-if="userType !== userTypes.deputy"
					class="chart-wizard-tree-preview__node_head-position --crop"
				>
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION')}}
				</span>
			</div>
		</div>

		<UserListActionMenu
			v-if="headsVisible"
			:id="userType === userTypes.deputy ? 'wizard-head-list-popup-deputy' : 'wizard-head-list-popup-head' "
			:items="dropdownItems"
			:width="228"
			:bindElement="$refs.showMoreHeadUserWizardList[0]"
			@close="headsVisible = false"
			:titleBar="titleBar"
		/>
	`
	};

	const TreeNode = {
	  name: 'treeNode',
	  components: {
	    HeadUsers
	  },
	  props: {
	    name: String,
	    heads: Array,
	    userCount: Number,
	    nodeId: Number
	  },
	  data() {
	    return {
	      isShowLoader: false
	    };
	  },
	  watch: {
	    isShowLoader(newValue) {
	      if (!newValue) {
	        return;
	      }
	      this.$nextTick(() => {
	        const {
	          loaderContainer
	        } = this.$refs;
	        const loader = new main_loader.Loader({
	          size: 30
	        });
	        loader.show(loaderContainer);
	      });
	    }
	  },
	  computed: {
	    departmentData() {
	      if (this.isExistingDepartment) {
	        if (!this.isHeadsLoaded) {
	          this.loadHeads([this.nodeId]);
	        }
	        return this.departments.get(this.nodeId);
	      }
	      return {
	        name: this.name,
	        heads: this.heads,
	        userCount: this.userCount
	      };
	    },
	    isExistingDepartment() {
	      return Boolean(this.nodeId);
	    },
	    employeesCount() {
	      var _this$heads;
	      return (this.userCount || 0) - (((_this$heads = this.heads) == null ? void 0 : _this$heads.length) || 0);
	    },
	    headUsers() {
	      var _this$departmentData$;
	      return (_this$departmentData$ = this.departmentData.heads) == null ? void 0 : _this$departmentData$.filter(head => {
	        return head.role === humanresources_companyStructure_api.memberRoles.head;
	      });
	    },
	    deputyUsers() {
	      var _this$departmentData$2;
	      return (_this$departmentData$2 = this.departmentData.heads) == null ? void 0 : _this$departmentData$2.filter(head => {
	        return head.role === humanresources_companyStructure_api.memberRoles.deputyHead;
	      });
	    },
	    showInfo() {
	      return this.nodeId ? humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance().hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.structureView, this.nodeId) : true;
	    },
	    isHeadsLoaded(departmentId) {
	      const {
	        heads
	      } = this.departments.get(this.nodeId);
	      return Boolean(heads);
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
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
	    async loadHeads(departmentIds) {
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      try {
	        this.isShowLoader = true;
	        await store.loadHeads(departmentIds);
	      } finally {
	        this.isShowLoader = false;
	      }
	    }
	  },
	  template: `
		<div
			class="chart-wizard-tree-preview__node"
			:class="{ '--new': !isExistingDepartment }"
		>
			<div class="chart-wizard-tree-preview__node_summary">
				<p class="chart-wizard-tree-preview__node_name --crop">
					{{departmentData.name}}
				</p>
				<HeadUsers
					v-if="showInfo && headUsers"
					:users="headUsers"
					:showPlaceholder="!isExistingDepartment"
				/>
				<div v-if="isShowLoader" ref="loaderContainer"></div>
				<div
					v-if="showInfo && !isExistingDepartment"
					class="chart-wizard-tree-preview__node_employees"
				>
					<div>
						<p class="chart-wizard-tree-preview__node_employees-title">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_TITLE')}}
						</p>
						<span class="chart-wizard-tree-preview__node_employees_count">
							{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_COUNT', employeesCount)}}
						</span>
					</div>
					<div class="chart-wizard-tree-preview__node_deputies">
						<p class="chart-wizard-tree-preview__node_employees-title">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPUTIES_TITLE')}}
						</p>
						<HeadUsers
							:users="deputyUsers"
							userType="deputy"
						/>
					</div>
				</div>
			</div>
			<slot v-if="isExistingDepartment"></slot>
		</div>
	`
	};

	const TreePreview = {
	  name: 'treePreview',
	  components: {
	    TreeNode
	  },
	  props: {
	    parentId: {
	      type: [Number, null],
	      required: true
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    heads: {
	      type: Array,
	      required: true
	    },
	    userCount: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    rootId() {
	      const parentNode = this.departments.get(this.parentId);
	      if (parentNode) {
	        var _parentNode$parentId;
	        return (_parentNode$parentId = parentNode.parentId) != null ? _parentNode$parentId : 0;
	      }
	      return 0;
	    },
	    companyName() {
	      const {
	        name
	      } = [...this.departments.values()].find(department => {
	        return department.parentId === 0;
	      });
	      return name;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div class="chart-wizard-tree-preview">
			<div class="chart-wizard-tree-preview__header">
				<span class="chart-wizard-tree-preview__header_text">
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPARTMENT_TITLE')}}
				</span>
				<span class="chart-wizard-tree-preview__header_name">
					{{companyName}}
				</span>
			</div>
			<TreeNode
				v-if="rootId"
				:nodeId="rootId"
			>
				<TreeNode :nodeId="parentId">
					<TreeNode
						:name="name"
						:heads="heads"
						:userCount="userCount"
					></TreeNode>
				</TreeNode>
			</TreeNode>
			<TreeNode
				v-else-if="parentId"
				:nodeId="parentId"
			>
				<TreeNode
					:name="name"
					:heads="heads"
					:userCount="userCount"
				></TreeNode>
			</TreeNode>
			<TreeNode
				v-else
				:name="name"
				:heads="heads"
				:userCount="userCount"
			></TreeNode>
		</div>
	`
	};

	const Department = {
	  name: 'department',
	  emits: ['applyData'],
	  props: {
	    parentId: {
	      type: [Number, null],
	      required: true
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    shouldErrorHighlight: {
	      type: Boolean,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean
	    }
	  },
	  data() {
	    return {
	      deniedError: false
	    };
	  },
	  created() {
	    this.selectedParentDepartment = this.parentId;
	    this.departmentName = this.name;
	    this.departmentDescription = this.description;
	    this.departmentsSelector = this.getTagSelector();
	  },
	  mounted() {
	    this.departmentsSelector.renderTo(this.$refs['tag-selector']);
	  },
	  activated() {
	    this.applyData();
	    this.$refs.title.focus();
	  },
	  methods: {
	    getTagSelector() {
	      return new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.data;
	            this.selectedParentDepartment = tag.id;
	          },
	          onTagRemove: () => {
	            this.selectedParentDepartment = null;
	            this.applyData();
	          }
	        },
	        multiple: false,
	        locked: this.parentId === 0,
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
	          preselectedItems: this.parentId ? [['structure-node', this.parentId]] : [],
	          events: {
	            onLoad: event => {
	              var _target$selectedItems, _target$selectedItems2, _target$selectedItems3;
	              if (this.isEditMode) {
	                return;
	              }
	              const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	              if (!permissionChecker) {
	                return;
	              }
	              const target = event.target;
	              const selectedItem = (_target$selectedItems = target.selectedItems) == null ? void 0 : (_target$selectedItems2 = _target$selectedItems.values()) == null ? void 0 : (_target$selectedItems3 = _target$selectedItems2.next()) == null ? void 0 : _target$selectedItems3.value;
	              const nodes = target.items.get('structure-node');
	              for (const [, node] of nodes) {
	                if (permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, node.id) && !permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, selectedItem == null ? void 0 : selectedItem.id)) {
	                  node.select();
	                  break;
	                }
	              }
	            },
	            onLoadError: () => {
	              this.selectedParentDepartment = null;
	              this.applyData();
	            },
	            'Item:onSelect': event => {
	              var _target$selectedItems4, _target$selectedItems5, _target$selectedItems6;
	              this.deniedError = false;
	              const target = event.target;
	              const selectedItem = (_target$selectedItems4 = target.selectedItems) == null ? void 0 : (_target$selectedItems5 = _target$selectedItems4.values()) == null ? void 0 : (_target$selectedItems6 = _target$selectedItems5.next()) == null ? void 0 : _target$selectedItems6.value;
	              const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	              if (!permissionChecker) {
	                return;
	              }
	              if (!permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, selectedItem.id)) {
	                this.deniedError = true;
	              }
	              this.applyData();
	            }
	          }
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
	    applyData() {
	      this.$emit('applyData', {
	        name: this.departmentName,
	        description: this.departmentDescription,
	        parentId: this.selectedParentDepartment,
	        isValid: this.departmentName !== '' && this.selectedParentDepartment !== null && !this.deniedError
	      });
	    }
	  },
	  template: `
		<div class="chart-wizard__department">
			<div class="chart-wizard__form">
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_LABEL')}}
					</span>
					<div
						:class="{ 'ui-ctl-warning': deniedError || (selectedParentDepartment === null && shouldErrorHighlight) }"
						ref="tag-selector"></div>
					<div
						v-if="deniedError || (selectedParentDepartment === null && shouldErrorHighlight)"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span
							v-if="deniedError"
							class="chart-wizard__department_item-error-message"
						>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ADD_TO_DEPARTMENT_DENIED_MSG_VER_1')}}
						</span>
						<span
							v-else
							class="chart-wizard__department_item-error-message"
						>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_PARENT_ERROR')}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_LABEL')}}
					</span>
					<div
						class="ui-ctl ui-ctl-textbox"
						:class="{ 'ui-ctl-warning': shouldErrorHighlight && departmentName === '' }"
					>
						<input
							v-model="departmentName"
							type="text"
							maxlength="255"
							class="ui-ctl-element"
							ref="title"
							:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_PLACEHOLDER')"
							@input="applyData()"
						/>
					</div>
					<div
						v-if="shouldErrorHighlight && departmentName === ''"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__department_item-error-message">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_ERROR')}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_LABEL')}}
					</span>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize">
						<textarea
							v-model="departmentDescription"
							maxlength="255"
							class="ui-ctl-element"
							:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_PLACEHOLDER')"
							@change="applyData()"
						>
						</textarea>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const MenuOption = Object.freeze({
	  moveUsers: 'moveUsers',
	  addUsers: 'addUsers'
	});
	const ChangeSaveModeControl = {
	  name: 'changeSaveModeControl',
	  emits: ['saveModeChanged'],
	  components: {
	    RouteActionMenu: humanresources_companyStructure_structureComponents.RouteActionMenu
	  },
	  created() {
	    this.menuItems = this.getMenuItems();
	  },
	  data() {
	    return {
	      menuVisible: false,
	      actionId: MenuOption.moveUsers
	    };
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    onActionMenuItemClick(actionId) {
	      this.actionId = actionId;
	      this.$emit('saveModeChanged', actionId);
	    },
	    getMenuItems() {
	      return [{
	        id: MenuOption.moveUsers,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_DESCRIPTION'),
	        bIcon: {
	          name: ui_iconSet_api_core.Main.PERSON_ARROW_LEFT_1,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        }
	      }, {
	        id: MenuOption.addUsers,
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_TITLE'),
	        description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_DESCRIPTION'),
	        bIcon: {
	          name: ui_iconSet_api_core.CRM.PERSON_PLUS_2,
	          size: 20,
	          color: humanresources_companyStructure_utils.getColorCode('paletteBlue50')
	        }
	      }];
	    }
	  },
	  computed: {
	    getControlButtonText() {
	      const phraseCode = this.actionId === MenuOption.moveUsers ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_MOVE_USERS_TITLE' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_ADD_USERS_TITLE';
	      return this.loc(phraseCode);
	    }
	  },
	  template: `
		<div
			class="chart-wizard__change-save-mode-control-container"
		>
			<span>{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_SAVE_MODE_CONTROL_TEXT') }}</span>
			<a
				class="chart-wizard__change-save-mode-control-button"
				:class="{ '--focused': menuVisible }"
				ref='changeSaveModeButton'
				@click="menuVisible = true"
			>
				{{ getControlButtonText }}
			</a>
		</div>
		<RouteActionMenu
			v-if="menuVisible"
			:id="'hr-wizard-save-mode-menu'"
			:items="menuItems"
			:width="302"
			:bindElement="$refs.changeSaveModeButton"
			@action="onActionMenuItemClick"
			@close="menuVisible = false"
		/>
	`
	};

	const Employees = {
	  name: 'employees',
	  components: {
	    ChangeSaveModeControl
	  },
	  emits: ['applyData', 'saveModeChanged'],
	  props: {
	    heads: {
	      type: Array,
	      required: true
	    },
	    employeesIds: {
	      type: Array,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    }
	  },
	  created() {
	    this.selectedUsers = new Set();
	    this.departmentHeads = [];
	    this.departmentEmployees = [];
	    this.removedUsers = [];
	    this.headSelector = this.getUserSelector(humanresources_companyStructure_api.memberRoles.head);
	    this.deputySelector = this.getUserSelector(humanresources_companyStructure_api.memberRoles.deputyHead);
	    this.employeesSelector = this.getUserSelector(humanresources_companyStructure_api.memberRoles.employee);
	    this.userCount = 0;
	  },
	  mounted() {
	    this.headSelector.renderTo(this.$refs['head-selector']);
	    this.deputySelector.renderTo(this.$refs['deputy-selector']);
	    this.employeesSelector.renderTo(this.$refs['employees-selector']);
	  },
	  watch: {
	    employeesIds: {
	      handler(payload) {
	        const preselectedEmployees = payload.map(employeeId => ['user', employeeId]);
	        const {
	          dialog
	        } = this.employeesSelector;
	        dialog.setPreselectedItems(preselectedEmployees);
	        dialog.load();
	      }
	    }
	  },
	  methods: {
	    getPreselectedItems(role) {
	      if (humanresources_companyStructure_api.memberRoles.employee === role) {
	        return this.employeesIds.map(employeeId => ['user', employeeId]);
	      }
	      return this.heads.filter(head => head.role === role).map(head => {
	        return ['user', head.id];
	      });
	    },
	    getUserSelector(role) {
	      const selector = new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            this.selectedUsers.add(tag.id);
	            this.onSelectorToggle(tag, role);
	            this.applyData();
	          },
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            this.selectedUsers.delete(tag.id);
	            this.onSelectorToggle(tag, role);
	            this.applyData();
	          }
	        },
	        multiple: true,
	        dialogOptions: {
	          preselectedItems: this.getPreselectedItems(role),
	          popupOptions: {
	            events: {
	              onBeforeShow: () => {
	                dialog.setHeight(250);
	                if (dialog.isLoaded()) {
	                  this.toggleUsers(dialog);
	                }
	              }
	            }
	          },
	          events: {
	            onShow: () => {
	              const {
	                dialog
	              } = selector;
	              const container = dialog.getContainer();
	              const {
	                top
	              } = container.getBoundingClientRect();
	              const offset = top + container.offsetHeight - document.body.offsetHeight;
	              if (offset > 0) {
	                const margin = 5;
	                dialog.setHeight(container.offsetHeight - offset - margin);
	              }
	            },
	            onLoad: event => {
	              this.toggleUsers(dialog);
	              const users = event.target.items.get('user');
	              users.forEach(user => {
	                user.setLink('');
	              });
	            },
	            'SearchTab:onLoad': () => {
	              this.toggleUsers(dialog);
	            }
	          },
	          height: 250,
	          width: 380,
	          entities: [{
	            id: 'user',
	            options: {
	              intranetUsersOnly: true,
	              inviteEmployeeLink: true
	            }
	          }],
	          dropdownMode: true,
	          hideOnDeselect: false
	        }
	      });
	      const dialog = selector.getDialog();
	      return selector;
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    toggleUsers(dialog) {
	      const items = dialog.getItems();
	      items.forEach(item => {
	        const hidden = this.selectedUsers.has(item.id) && !dialog.selectedItems.has(item);
	        item.setHidden(hidden);
	      });
	    },
	    onSelectorToggle(tag, role) {
	      const item = tag.selector.dialog.getItem(['user', tag.id]);
	      const userData = humanresources_companyStructure_utils.getUserDataBySelectorItem(item, role);
	      const isEmployee = role === humanresources_companyStructure_api.memberRoles.employee;
	      if (!tag.rendered) {
	        this.removedUsers = this.removedUsers.filter(user => user.id !== userData.id);
	        if (isEmployee) {
	          this.departmentEmployees = [...this.departmentEmployees, {
	            ...userData
	          }];
	        } else {
	          this.departmentHeads = [...this.departmentHeads, {
	            ...userData
	          }];
	        }
	        this.userCount += 1;
	        return;
	      }
	      const {
	        preselectedItems = []
	      } = tag.selector.dialog;
	      const parsedPreselected = preselectedItems.flat().filter(item => item !== 'user');
	      if (parsedPreselected.includes(userData.id)) {
	        this.removedUsers = [...this.removedUsers, {
	          ...userData,
	          role
	        }];
	      }
	      if (isEmployee) {
	        this.departmentEmployees = this.departmentEmployees.filter(employee => employee.id !== tag.id);
	      } else {
	        this.departmentHeads = this.departmentHeads.filter(head => head.id !== tag.id);
	      }
	      this.userCount -= 1;
	    },
	    applyData() {
	      this.$emit('applyData', {
	        heads: this.departmentHeads,
	        employees: this.departmentEmployees,
	        removedUsers: this.removedUsers,
	        userCount: this.userCount
	      });
	    },
	    handleSaveModeChangedChanged(actionId) {
	      this.$emit('saveModeChanged', actionId);
	    }
	  },
	  template: `
		<div class="chart-wizard__employee">
			<div class="chart-wizard__form">
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="head-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-head-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_DESCR')}}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="deputy-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-deputy-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_DESCR')}}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_EMPLOYEES_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="employees-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-employee-selector"
					/>
				</div>
				<div class="chart-wizard__employee_item --change-save-mode-control">
					<ChangeSaveModeControl
						v-if="!isEditMode"
						@saveModeChanged="handleSaveModeChangedChanged"
					></ChangeSaveModeControl>
					<div class="chart-wizard__change-save-mode-control-container" v-else>
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_EDIT_WIZARD_EMPLOYEE_SAVE_MODE_TEXT')}}
					</div>
				</div>
			</div>
		</div>
	`
	};

	const BindChat = {
	  name: 'bindChat',
	  created() {
	    this.hints = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2')];
	    this.chatSelector = this.getChatSelector();
	  },
	  mounted() {
	    this.chatSelector.renderTo(this.$refs['chat-selector']);
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    getChatSelector() {
	      const selector = new ui_entitySelector.TagSelector({
	        events: {},
	        multiple: true,
	        locked: true,
	        dialogOptions: {
	          height: 250,
	          width: 380,
	          dropdownMode: true,
	          hideOnDeselect: true
	        }
	      });
	      selector.getDialog().freeze();
	      return selector;
	    }
	  },
	  template: `
		<div class="chart-wizard__bind-chat">
			<div class="chart-wizard__bind-chat__item">
				<div class="chart-wizard__bind-chat__item-hint">
					<div class="chart-wizard__bind-chat__item-hint__logo"></div>
					<div class="chart-wizard__bind-chat__item-hint__text">
						<div v-for="hint in hints"
							 class="chart-wizard__bind-chat__item-hint__text-item"
						>
							<div class="chart-wizard__bind-chat__item-hint__text-item__icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content__title">
						<div class="chart-wizard__bind-chat__item-options__item-content__title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT') }}
						</div>
						<span class="chart-wizard__bind-chat__item-options__item-content__title-not-available">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_OPTION_NOT_AVAILABLE') }}
						</span>
					</div>
					<div class="chart-wizard__chat_selector" ref="chat-selector" disabled="disabled"></div>
					<span class="chart-wizard__employee_item-description">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHAT_DESCRIPTION') }}
					</span>
				</div>
			</div>
		</div>
	`
	};

	const Entities = {
	  data() {
	    return {
	      selectedId: 'department'
	    };
	  },
	  emits: ['applyData'],
	  created() {
	    this.entities = [{
	      id: 'department',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_DESCR')
	    }, {
	      id: 'group',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_GROUP_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_GROUP_DESCR')
	    }, {
	      id: 'company',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_COMPANY_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_COMPANY_DESCR')
	    }];
	  },
	  activated() {
	    this.applyData();
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    applyData() {
	      this.$emit('applyData', {
	        isValid: true
	      });
	    }
	  },
	  template: `
		<div
			v-for="entity in entities"
			class="chart-wizard__entity"
			:class="{ ['--' + entity.id]: true, '--selected': entity.id === selectedId }"
		>
			<div class="chart-wizard__entity_summary" @click="applyData">
				<h2
					class="chart-wizard__entity_title"
					:data-title="entity.id !== 'department' ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE') : null"
					:class="{ '--disabled': entity.id !== 'department' }"
				>
					{{entity.title}}
				</h2>
				<p class="chart-wizard__entity_description" :class="{ '--disabled': entity.id !== 'department'}">
					{{entity.description}}
				</p>
			</div>
		</div>
	`
	};

	const WizardAPI = {
	  addDepartment: (name, parentId, description) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.add', {
	      name,
	      parentId,
	      description
	    });
	  },
	  getEmployees: nodeId => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Employee.getIds', {
	      nodeId
	    });
	  },
	  updateDepartment: (nodeId, parentId, name, description) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.update', {
	      nodeId,
	      name,
	      parentId,
	      description
	    });
	  },
	  saveUsers: (nodeId, userIds, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.saveUserList', {
	      nodeId,
	      userIds,
	      parentId
	    });
	  },
	  moveUsers: (nodeId, userIds, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
	      nodeId,
	      userIds,
	      parentId
	    });
	  }
	};

	const chartWizardActions = {
	  createDepartment: departmentData => {
	    var _parent$children;
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const {
	      id: departmentId,
	      parentId
	    } = departmentData;
	    const parent = departments.get(parentId);
	    parent.children = [...((_parent$children = parent.children) != null ? _parent$children : []), departmentId];
	    departments.set(departmentId, {
	      ...departmentData,
	      id: departmentId
	    });
	  },
	  editDepartment: departmentData => {
	    const {
	      id,
	      parentId
	    } = departmentData;
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    departments.set(id, {
	      ...departmentData
	    });
	    const prevParent = [...departments.values()].find(department => {
	      var _department$children;
	      return (_department$children = department.children) == null ? void 0 : _department$children.includes(id);
	    });
	    if (parentId !== 0 && prevParent.id !== parentId) {
	      var _newParent$children;
	      prevParent.children = prevParent.children.filter(childId => childId !== id);
	      const newParent = departments.get(parentId);
	      newParent.children = [...((_newParent$children = newParent.children) != null ? _newParent$children : []), id];
	      departments.set(id, {
	        ...departmentData,
	        prevParentId: prevParent.id
	      });
	    }
	  },
	  moveUsersToRootDepartment: (removedUsers, userMovedToRootIds) => {
	    const {
	      departments
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const rootEmployees = removedUsers.filter(user => userMovedToRootIds.includes(user.id));
	    const rootNode = [...departments.values()].find(department => department.parentId === 0);
	    departments.set(rootNode.id, {
	      ...rootNode,
	      employees: [...(rootNode.employees || []), ...rootEmployees],
	      userCount: rootNode.userCount + rootEmployees.length
	    });
	  },
	  refreshDepartments: ids => {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    store.refreshDepartments(ids);
	  },
	  tryToAddCurrentDepartment(departmentData, departmentId) {
	    const store = humanresources_companyStructure_chartStore.useChartStore();
	    const {
	      heads,
	      employees
	    } = departmentData;
	    const isCurrentUserAdd = [...heads, ...employees].some(user => {
	      return user.id === store.userId;
	    });
	    if (isCurrentUserAdd) {
	      store.changeCurrentDepartment(0, departmentId);
	    }
	  }
	};

	const SaveMode = Object.freeze({
	  moveUsers: 'moveUsers',
	  addUsers: 'addUsers'
	});
	const ChartWizard = {
	  name: 'chartWizard',
	  emits: ['modifyTree', 'close'],
	  components: {
	    Department,
	    Employees,
	    BindChat,
	    TreePreview,
	    Entities
	  },
	  props: {
	    nodeId: {
	      type: Number,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    },
	    showEntitySelector: {
	      type: Boolean,
	      required: false
	    },
	    entity: {
	      type: String
	    },
	    source: {
	      type: String
	    }
	  },
	  data() {
	    return {
	      stepIndex: 0,
	      waiting: false,
	      isValidStep: false,
	      departmentData: {
	        id: 0,
	        parentId: 0,
	        name: '',
	        description: '',
	        heads: [],
	        employees: [],
	        userCount: 0
	      },
	      removedUsers: [],
	      employeesIds: [],
	      shouldErrorHighlight: false,
	      visibleSteps: [],
	      saveMode: SaveMode.moveUsers
	    };
	  },
	  created() {
	    this.steps = [{
	      id: 'entities',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SELECT_ENTITY_TITLE')
	    }, {
	      id: 'department',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_TITLE')
	    }, {
	      id: 'employees',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEES_TITLE')
	    }, {
	      id: 'bindChat',
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE')
	    }];
	    this.init();
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(window, 'beforeunload', this.handleBeforeUnload);
	  },
	  computed: {
	    stepTitle() {
	      if (this.isFirstStep && !this.isEditMode) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE');
	      }
	      const currentStep = this.visibleSteps[0] === 'entities' ? this.stepIndex : this.stepIndex + 1;
	      return this.isEditMode ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_STEP_PROGRESS', {
	        '#CURRENT_STEP#': currentStep,
	        '#MAX_STEP#': this.steps.length - 1
	      });
	    },
	    currentStep() {
	      const id = this.visibleSteps[this.stepIndex];
	      return this.steps.find(step => id === step.id);
	    },
	    componentInfo() {
	      const {
	        parentId,
	        name,
	        description,
	        heads
	      } = this.departmentData;
	      const components = {
	        department: {
	          name: 'Department',
	          params: {
	            parentId,
	            name,
	            description,
	            shouldErrorHighlight: this.shouldErrorHighlight,
	            isEditMode: this.isEditMode
	          },
	          hasData: true
	        },
	        employees: {
	          name: 'Employees',
	          params: {
	            heads,
	            employeesIds: this.employeesIds,
	            isEditMode: this.isEditMode
	          },
	          hasData: true
	        },
	        bindChat: {
	          name: 'BindChat'
	        },
	        entities: {
	          name: 'Entities',
	          hasData: true
	        }
	      };
	      const {
	        id: stepId
	      } = this.currentStep;
	      return components[stepId];
	    },
	    isFirstStep() {
	      return this.currentStep.id === 'entities';
	    },
	    filteredSteps() {
	      return this.visibleSteps.filter(step => step !== 'entities');
	    },
	    rootId() {
	      const {
	        id
	      } = [...this.departments.values()].find(department => {
	        return department.parentId === 0;
	      });
	      return id;
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'userId', 'currentDepartments'])
	  },
	  methods: {
	    handleBeforeUnload(event) {
	      event.preventDefault();
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    async init() {
	      main_core.Event.bind(window, 'beforeunload', this.handleBeforeUnload);
	      this.createVisibleSteps();
	      if (this.isEditMode) {
	        const {
	          id,
	          name,
	          description,
	          parentId,
	          heads,
	          userCount,
	          children,
	          employees = []
	        } = this.departments.get(this.nodeId);
	        this.departmentData = {
	          ...this.departmentData,
	          id,
	          parentId,
	          name,
	          description,
	          heads,
	          userCount,
	          children,
	          employees
	        };
	        this.employeesIds = await WizardAPI.getEmployees(this.nodeId);
	        return;
	      }
	      if (this.nodeId) {
	        this.departmentData.parentId = this.nodeId;
	        return;
	      }
	      this.departmentData.parentId = this.rootId;
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'structure',
	        event: 'create_wizard',
	        c_element: this.source
	      });
	    },
	    createVisibleSteps() {
	      switch (this.entity) {
	        case 'department':
	          this.visibleSteps = ['department'];
	          break;
	        case 'employees':
	          this.visibleSteps = ['employees'];
	          break;
	        default:
	          this.visibleSteps = this.showEntitySelector ? this.steps.map(step => step.id) : this.steps.filter(step => step.id !== 'entities').map(step => step.id);
	          break;
	      }
	    },
	    move(buttonId = 'next') {
	      if (buttonId === 'next' && !this.isValidStep) {
	        this.shouldErrorHighlight = true;
	        return;
	      }
	      this.stepIndex = buttonId === 'back' ? this.stepIndex - 1 : this.stepIndex + 1;
	      this.pickStepsAnalitics();
	    },
	    close(sendEvent = false) {
	      this.$emit('close');
	      if (sendEvent) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: 'cancel_wizard',
	          c_element: this.source
	        });
	      }
	    },
	    onApplyData(data) {
	      const {
	        isValid = true,
	        removedUsers = [],
	        ...departmentData
	      } = data;
	      this.isValidStep = isValid;
	      if (departmentData) {
	        this.departmentData = {
	          ...this.departmentData,
	          ...departmentData
	        };
	      }
	      this.removedUsers = removedUsers;
	      if (isValid) {
	        this.shouldErrorHighlight = false;
	      }
	    },
	    getUsersPromise(departmentId) {
	      const ids = this.calculateEmployeeIds();
	      const {
	        headsIds,
	        deputiesIds,
	        employeesIds
	      } = ids;
	      const departmentUserIds = {
	        [humanresources_companyStructure_api.memberRoles.head]: headsIds,
	        [humanresources_companyStructure_api.memberRoles.deputyHead]: deputiesIds,
	        [humanresources_companyStructure_api.memberRoles.employee]: employeesIds
	      };
	      return this.getUserMemberPromise(departmentId, departmentUserIds);
	    },
	    calculateEmployeeIds() {
	      const {
	        heads,
	        employees = []
	      } = this.departmentData;
	      return [...heads, ...employees].reduce((acc, user) => {
	        const {
	          headsIds,
	          deputiesIds,
	          employeesIds
	        } = acc;
	        if (user.role === humanresources_companyStructure_api.memberRoles.head) {
	          headsIds.push(user.id);
	        } else if (user.role === humanresources_companyStructure_api.memberRoles.deputyHead) {
	          deputiesIds.push(user.id);
	        } else {
	          employeesIds.push(user.id);
	        }
	        return acc;
	      }, {
	        headsIds: [],
	        deputiesIds: [],
	        employeesIds: []
	      });
	    },
	    getUserMemberPromise(departmentId, ids, role) {
	      var _this$departmentData$;
	      if (this.isEditMode) {
	        return WizardAPI.saveUsers(departmentId, ids);
	      }
	      const hasUsers = Object.values(ids).some(userIds => userIds.length > 0);
	      if (!hasUsers) {
	        return Promise.resolve();
	      }
	      const parentId = (_this$departmentData$ = this.departmentData.parentId) != null ? _this$departmentData$ : null;
	      if (this.saveMode === SaveMode.moveUsers) {
	        return WizardAPI.moveUsers(departmentId, ids, parentId);
	      }
	      return WizardAPI.saveUsers(departmentId, ids, parentId);
	    },
	    async create() {
	      const {
	        name,
	        parentId,
	        description
	      } = this.departmentData;
	      let departmentId = 0;
	      let accessCode = '';
	      this.waiting = true;
	      try {
	        const [newDepartment] = await WizardAPI.addDepartment(name, parentId, description);
	        departmentId = newDepartment.id;
	        accessCode = newDepartment.accessCode;
	        const data = await this.getUsersPromise(departmentId);
	        if (data != null && data.updatedDepartmentIds) {
	          chartWizardActions.refreshDepartments(data.updatedDepartmentIds);
	        } else {
	          chartWizardActions.tryToAddCurrentDepartment(this.departmentData, departmentId);
	        }
	      } finally {
	        this.waiting = false;
	      }
	      chartWizardActions.createDepartment({
	        ...this.departmentData,
	        id: departmentId,
	        accessCode
	      });
	      this.$emit('modifyTree', {
	        id: departmentId,
	        parentId,
	        showConfetti: true
	      });
	      const {
	        headsIds,
	        deputiesIds,
	        employeesIds
	      } = this.calculateEmployeeIds();
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'structure',
	        event: 'create_dept',
	        c_element: this.source,
	        p2: `headAmount_${headsIds.length}`,
	        p3: `secondHeadAmount_${deputiesIds.length}`,
	        p4: `employeeAmount_${employeesIds.length}`
	      });
	      this.close();
	    },
	    async save() {
	      if (!this.isValidStep) {
	        this.shouldErrorHighlight = true;
	        return;
	      }
	      const {
	        id,
	        parentId,
	        name,
	        description
	      } = this.departmentData;
	      const currentNode = this.departments.get(id);
	      const targetNodeId = (currentNode == null ? void 0 : currentNode.parentId) === parentId ? null : parentId;
	      this.waiting = true;
	      const usersPromise = this.entity === 'employees' ? this.getUsersPromise(id) : Promise.resolve();
	      const departmentPromise = this.entity === 'department' ? WizardAPI.updateDepartment(id, targetNodeId, name, description) : Promise.resolve();
	      this.pickEditAnalitics(id, parentId);
	      try {
	        const [usersResponse] = await Promise.all([usersPromise, departmentPromise]);
	        let userMovedToRootIds = [];
	        if (this.removedUsers.length > 0) {
	          var _usersResponse$userMo;
	          userMovedToRootIds = (_usersResponse$userMo = usersResponse == null ? void 0 : usersResponse.userMovedToRootIds) != null ? _usersResponse$userMo : [];
	          if (userMovedToRootIds.length > 0) {
	            chartWizardActions.moveUsersToRootDepartment(this.removedUsers, userMovedToRootIds);
	          }
	        }
	        const store = humanresources_companyStructure_chartStore.useChartStore();
	        if (userMovedToRootIds.includes(this.userId)) {
	          store.changeCurrentDepartment(id, this.rootId);
	        } else if (this.removedUsers.some(user => user.id === this.userId)) {
	          store.changeCurrentDepartment(id);
	        } else {
	          chartWizardActions.tryToAddCurrentDepartment(this.departmentData, id);
	        }
	        chartWizardActions.editDepartment(this.departmentData);
	      } catch (e) {
	        console.error(e);
	        return;
	      } finally {
	        this.waiting = false;
	      }
	      this.$emit('modifyTree', {
	        id,
	        parentId
	      });
	      this.close();
	    },
	    handleSaveModeChanged(actionId) {
	      this.saveMode = actionId;
	    },
	    pickEditAnalitics(departmentId, parentId) {
	      const currentNode = this.departments.get(departmentId);
	      switch (this.entity) {
	        case 'department':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'edit_dept_name',
	            c_element: this.source,
	            p1: (currentNode == null ? void 0 : currentNode.parentId) === parentId ? 'editHead_N' : 'editHeadDept_Y',
	            p2: (currentNode == null ? void 0 : currentNode.name) === name ? 'editName_N' : 'editName_Y'
	          });
	          break;
	        case 'employees':
	          {
	            const {
	              headsIds,
	              deputiesIds,
	              employeesIds
	            } = this.calculateEmployeeIds();
	            ui_analytics.sendData({
	              tool: 'structure',
	              category: 'structure',
	              event: 'edit_dept_employee',
	              c_element: this.source,
	              p2: `headAmount_${headsIds.length}`,
	              p3: `secondHeadAmount_${deputiesIds.length}`,
	              p4: `employeeAmount_${employeesIds.length}`
	            });
	            break;
	          }
	        default:
	          break;
	      }
	    },
	    pickStepsAnalitics() {
	      switch (this.currentStep.id) {
	        case 'department':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step1',
	            c_element: this.source
	          });
	          break;
	        case 'employees':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step2',
	            c_element: this.source
	          });
	          break;
	        case 'bindChat':
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'create_dept_step3',
	            c_element: this.source
	          });
	          break;
	        default:
	          break;
	      }
	    }
	  },
	  template: `
		<div class="chart-wizard">
			<div class="chart-wizard__dialog" :style="{ 'max-width': !isEditMode && isFirstStep ? '643px' : '883px' }">
				<div class="chart-wizard__head">
					<div class="chart-wizard__head_close" @click="close(true)"></div>
					<p class="chart-wizard__head_title">{{ stepTitle }}</p>
					<p class="chart-wizard__head_descr">{{ currentStep.title }}</p>
					<div class="chart-wizard__head_stages" v-if="!isFirstStep && !isEditMode">
						<div
							v-for="n in filteredSteps.length"
							class="chart-wizard__head_stage"
							:class="{ '--completed': stepIndex >= (this.showEntitySelector ? n : n - 1) }"
						></div>
					</div>
				</div>
				<div class="chart-wizard__content" :style="{ display: !isEditMode && isFirstStep ? 'block' : 'flex' }">
					<KeepAlive>
						<component
							:is="componentInfo.name"
							v-bind="componentInfo.params"
							v-on="{
								applyData: componentInfo.hasData ? onApplyData : undefined,
								saveModeChanged: componentInfo.name === 'Employees' ? handleSaveModeChanged : undefined
							}"
						>
						</component>
					</KeepAlive>
					<TreePreview
						v-if="isEditMode || !isFirstStep"
						:parentId="departmentData.parentId"
						:name="departmentData.name"
						:heads="departmentData.heads"
						:userCount="departmentData.userCount"
					/>
				</div>
				<div class="chart-wizard__footer">
					<button
						v-if="stepIndex > 0"
						class="ui-btn ui-btn-light --back"
						@click="move('back')"
					>
						<div class="ui-icon-set --chevron-left"></div>
						<span>{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BACK_BTN') }}</span>
					</button>
					<button
						v-show="stepIndex < visibleSteps.length - 1 && !isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --next"
						:class="{ 'ui-btn-disabled': !isValidStep, 'ui-btn-light-border': isEditMode }"
						@click="move()"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_NEXT_BTN') }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --next"
						:class="{ 'ui-btn-light-border': isEditMode }"
						@click="close(true)"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DISCARD_BTN') }}
					</button>
					<button
						v-show="!isEditMode && stepIndex === visibleSteps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round"
						:class="{ 'ui-btn-wait': waiting }"
						@click="create"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_BTN') }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --save"
						:class="{ 'ui-btn-wait': waiting, 'ui-btn-disabled': !isValidStep, }"
						@click="save"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SAVE_BTN') }}
					</button>
				</div>
			</div>
			<div class="chart-wizard__overlay"></div>
		</div>
	`
	};

	exports.ChartWizard = ChartWizard;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Vue3.Pinia,BX,BX,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.IconSet,BX,BX.Humanresources.CompanyStructure,BX.UI.EntitySelector,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.Analytics));
//# sourceMappingURL=chart-wizard.bundle.js.map

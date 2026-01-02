/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_notification,ui_dialogs_messagebox,humanresources_companyStructure_departmentContent,main_loader,main_core_cache,ui_iconSet_crm,ui_vue3_pinia,ui_iconSet_api_core,ui_iconSet_api_vue,humanresources_companyStructure_structureComponents,main_core,ui_entitySelector,humanresources_companyStructure_api,humanresources_companyStructure_chartStore,ui_analytics,humanresources_companyStructure_permissionChecker,humanresources_companyStructure_utils) {
	'use strict';

	const SaveMode = Object.freeze({
	  moveUsers: 'moveUsers',
	  addUsers: 'addUsers'
	});
	const StepIds = Object.freeze({
	  entities: 'entities',
	  department: 'department',
	  employees: 'employees',
	  bindChat: 'bindChat',
	  settings: 'settings'
	});
	const AuthorityTypes = {
	  departmentHead: 'HEAD',
	  departmentDeputy: 'DEPUTY_HEAD',
	  teamHead: 'TEAM_HEAD',
	  teamDeputy: 'TEAM_DEPUTY'
	};

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
	    isTeamEntity: {
	      type: Boolean,
	      default: false
	    },
	    isExistingDepartment: {
	      type: Boolean,
	      default: true
	    },
	    userType: {
	      type: String,
	      default: 'head'
	    },
	    teamColor: {
	      type: [Object, null],
	      default: null
	    }
	  },
	  data() {
	    return {
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
	    headItemsCount() {
	      return this.isExistingDepartment ? 1 : 2;
	    },
	    defaultAvatar() {
	      return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
	    },
	    placeholderAvatar() {
	      if (!this.isTeamEntity || !this.teamColor) {
	        return '/bitrix/js/humanresources/company-structure/chart-wizard/src/components/tree-preview/images/placeholder-avatar.svg';
	      }
	      return `/bitrix/js/humanresources/company-structure/chart-wizard/src/components/tree-preview/images/${this.teamColor.avatarImage}`;
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
	    },
	    isDeputy() {
	      return this.userType === this.userTypes.deputy;
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
			:class="{ '--deputy': isDeputy }"
			v-for="(user, index) in users.slice(0, headItemsCount)"
		>
			<img
				:src="user.avatar || defaultAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{ '--deputy': isDeputy, '--old': isExistingDepartment }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text" :class="{'--deputy': isDeputy }">
				<span
					class="chart-wizard-tree-preview__node_head-name --crop"
					:class="{'--old': isExistingDepartment }"
				>
					{{ user.name }}
				</span>
				<span
					v-if="!isDeputy"
					class="chart-wizard-tree-preview__node_head-position --crop"
					:class="{'--old': isExistingDepartment }"
				>
					{{ user.workPosition || loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION') }}
				</span>
			</div>
			<span
				v-if="index === headItemsCount - 1 && users.length > headItemsCount"
				class="chart-wizard-tree-preview__node_head-rest"
				:class="{ '--active': headsVisible }"
				ref="showMoreHeadUserWizardList"
				:data-test-id="'hr-company-structure_chart-wizard-tree__preview-' + userType + '-rest'"
				@click.stop="headsVisible = true"
			>
					{{ '+' + String(users.length - headItemsCount) }}
			</span>
		</div>
		<div
			v-if="users.length === 0 && showPlaceholder"
			class="chart-wizard-tree-preview__node_head"
			:class="{ '--deputy': isDeputy }"
		>
			<img
				:src="placeholderAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{'--deputy': isDeputy, '--old': isExistingDepartment  }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text">
				<span
					class="chart-wizard-tree-preview__placeholder_name"
					:class="{'--deputy': isDeputy, '--team': isTeamEntity }"
					:style="{ 'background-color': teamColor ? teamColor.namePlaceholder : false }"
				></span>
				<span
					v-if="!isDeputy"
					class="chart-wizard-tree-preview__node_head-position --crop"
				>
					{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION') }}
				</span>
			</div>
		</div>

		<UserListActionMenu
			v-if="headsVisible"
			:id="isDeputy ? 'wizard-head-list-popup-deputy' : 'wizard-head-list-popup-head' "
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
	    nodeId: Number,
	    entityType: {
	      type: String,
	      default: humanresources_companyStructure_utils.EntityTypes.department
	    },
	    /** @type NodeColorSettingsType | null */
	    teamColor: {
	      type: [Object, null],
	      default: null
	    }
	  },
	  data() {
	    return {
	      isShowLoader: false
	    };
	  },
	  created() {
	    this.memberRoles = humanresources_companyStructure_api.getMemberRoles(this.departmentData.entityType);
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
	        userCount: this.userCount,
	        entityType: this.entityType
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
	        return head.role === this.memberRoles.head;
	      });
	    },
	    deputyUsers() {
	      var _this$departmentData$2;
	      return (_this$departmentData$2 = this.departmentData.heads) == null ? void 0 : _this$departmentData$2.filter(head => {
	        return head.role === this.memberRoles.deputyHead;
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
	    isTeamEntity() {
	      return this.departmentData.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    dataTestIdSuffix() {
	      return this.isExistingDepartment ? '' : '_new';
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
	    },
	    formatDataTestId(prefix) {
	      return prefix + this.dataTestIdSuffix;
	    }
	  },
	  template: `
		<div
			class="chart-wizard-tree-preview__node"
			:class="{ '--new': !isExistingDepartment, '--old': isExistingDepartment,'--team': isTeamEntity }"
		>
			<div 
				class="chart-wizard-tree-preview__node_summary" 
				:class="{ '--old': isExistingDepartment, '--team': isTeamEntity }"
				:style="{ '--team-border-color': teamColor?.previewBorder }"
			>
				<div 
					class="chart-wizard-tree-preview__node_name --crop"
					:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_name')"
					:style="{ 'background-color': teamColor ? teamColor.headBackground : false }"
				>
					<span
						v-if="!departmentData.name" 
						class="chart-wizard-tree-preview__placeholder_node_name"
						:class="{ '--team': isTeamEntity }"
						:style="{ 'background-color': teamColor ? teamColor.namePlaceholder : false }"
					></span>
					{{departmentData.name}}
				</div>
				<div 
					class="chart-wizard-tree-preview__node_content"
					:data-test-id="formatDataTestId('chart-wizard-tree-preview__head_list')"
				>
					<div
						class="chart-wizard-tree-preview__head_list"
						:data-test-id="formatDataTestId('chart-wizard-tree-preview__head_list')"
					>
						<HeadUsers
							v-if="showInfo && headUsers"
							:users="headUsers"
							:showPlaceholder="!isExistingDepartment"
							:isTeamEntity="isTeamEntity"
							:isExistingDepartment="isExistingDepartment"
							:teamColor="teamColor"
						/> 
					</div>
					<div v-if="isShowLoader" ref="loaderContainer"></div>
					<div
						v-if="showInfo && !isExistingDepartment"
						class="chart-wizard-tree-preview__node_employees"
					>
						<div
							class="chart-wizard-tree-preview__node_employees-list"
							:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_employees-list')"
						>
							<p class="chart-wizard-tree-preview__node_employees-title">
								{{
									isTeamEntity 
										? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_TEAM_EMPLOYEES_TITLE') 
										: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_TITLE')
								}}
							</p>
							<span class="chart-wizard-tree-preview__node_employees_count">
								{{ locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_EMPLOYEES_COUNT', employeesCount) }}
							</span>
						</div>
						<div 
							class="chart-wizard-tree-preview__node_deputies"
							:data-test-id="formatDataTestId('chart-wizard-tree-preview__node_deputies-list')"
						>
							<p class="chart-wizard-tree-preview__node_employees-title">
								{{
									isTeamEntity
										? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_TEAM_DEPUTIES_TITLE') 
										: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPUTIES_TITLE') 
								}}
							</p>
							<HeadUsers
								:users="deputyUsers"
								:isTeamEntity="isTeamEntity"
								:isExistingDepartment="isExistingDepartment"
								userType="deputy"
								:teamColor="teamColor"
							/>
						</div>
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
	    },
	    entityType: {
	      type: String,
	      default: humanresources_companyStructure_utils.EntityTypes.department
	    },
	    /** @type NodeColorSettingsType | null */
	    teamColor: {
	      type: [Object, null],
	      default: null
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
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
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
					{{
						isTeamEntity
							? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_TEAM_TITLE')
							: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPARTMENT_TITLE')
					}}
				</span>
				<span class="chart-wizard-tree-preview__header_name">
					{{ companyName }}
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
						:entityType="entityType"
						:teamColor="teamColor"
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
					:entityType="entityType"
					:teamColor="teamColor"
				></TreeNode>
			</TreeNode>
			<TreeNode
				v-else
				:name="name"
				:heads="heads"
				:userCount="userCount"
				:entityType="entityType"
				:teamColor="teamColor"
			></TreeNode>
		</div>
	`
	};

	// @vue/component
	const TeamColorPicker = {
	  name: 'TeamColorPicker',
	  components: {
	    BasePopup: humanresources_companyStructure_structureComponents.BasePopup
	  },
	  props: {
	    /** @type NodeColorSettingsType */
	    modelValue: {
	      type: Object,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    }
	  },
	  emits: ['action', 'close', 'update:modelValue'],
	  computed: {
	    popupConfig() {
	      const popupWidth = 164;
	      const pickerWidth = 41;
	      const initialPopupOffset = 39;
	      const angleWidth = 33;
	      return {
	        width: popupWidth,
	        height: 116,
	        bindElement: this.bindElement,
	        borderRadius: 12,
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 0,
	        offsetTop: 4,
	        offsetLeft: pickerWidth / 2 - popupWidth / 2 + initialPopupOffset,
	        angleOffset: popupWidth / 2 - angleWidth / 2
	      };
	    },
	    nodeColorsSettingsDict() {
	      return humanresources_companyStructure_utils.NodeColorsSettingsDict;
	    }
	  },
	  methods: {
	    close() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<BasePopup
			:config="popupConfig"
			:id="'chart_wizard__department__color-picker_popup'"
			@close="close"
		>
			<div
				class="chart_wizard__department__color-picker_popup-container"
				:data-test-id="'wizard-department-color-picker-popup'"
			>
				<div v-for="item in nodeColorsSettingsDict" 
					 class="chart_wizard__department__color-picker_color-item"
					 :class=" {'--active': item.name === modelValue.name }"
					 @click="$emit('update:modelValue', item)"
					 :data-test-id="'wizard-department-color-picker-item-'+item.name"
				>
					<div class="chart_wizard__department__color-picker_color-item_inner"
						 :style="{ 'background-color': item.pickerColor }"
					></div>
				</div>
			</div>
		</BasePopup>
	`
	};

	// @vue/component
	const Department = {
	  name: 'department',
	  components: {
	    TeamColorPicker
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
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    teamColor: {
	      type: [Object, null],
	      default: null
	    },
	    refToFocus: {
	      type: String,
	      default: null
	    }
	  },
	  emits: ['applyData'],
	  data() {
	    return {
	      deniedError: false,
	      showColorPicker: false,
	      teamColorValue: this.teamColor,
	      locked: false
	    };
	  },
	  watch: {
	    isTeamEntity() {
	      // tag selector is not valid now
	      this.recreateTagSelector();
	    }
	  },
	  created() {
	    this.selectedParentDepartment = this.parentId;
	    this.departmentName = this.name;
	    this.departmentDescription = this.description;
	    this.departmentSelectorCashe = new main_core_cache.MemoryCache();
	    this.departmentsSelector = this.getOrCreateTagSelector();
	  },
	  mounted() {
	    this.departmentsSelector.renderTo(this.$refs['tag-selector']);
	  },
	  activated() {
	    var _this$departmentName;
	    this.teamColorValue = this.teamColor;
	    this.$emit('applyData', {
	      isDepartmentDataChanged: false,
	      isValid: this.departmentName && ((_this$departmentName = this.departmentName) == null ? void 0 : _this$departmentName.trim()) !== '' && this.selectedParentDepartment !== null && !this.deniedError
	    });
	    if (this.refToFocus && this.$refs[this.refToFocus]) {
	      this.$refs[this.refToFocus].focus();
	    } else {
	      this.$refs.title.focus();
	    }
	  },
	  methods: {
	    recreateTagSelector() {
	      this.$refs['tag-selector'].innerHTML = '';
	      this.departmentsSelector = this.getOrCreateTagSelector();
	      this.departmentsSelector.renderTo(this.$refs['tag-selector']);
	    },
	    createTagSelector() {
	      this.locked = this.isTagSelectorLocked();
	      let preselectedItems = this.parentId ? [['structure-node', this.parentId]] : [];
	      if (!this.isEditMode) {
	        const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	        const permissionAction = this.isTeamEntity ? humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate : humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate;
	        if (permissionChecker && !permissionChecker.hasPermission(permissionAction, this.parentId)) {
	          preselectedItems = [['structure-node', 0]];
	        }
	      }
	      const isTabEmpty = tab => tab.getRootNode().getChildren().count() === 0;
	      const selector = new ui_entitySelector.TagSelector({
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
	        locked: this.locked,
	        dialogOptions: {
	          width: 425,
	          height: 350,
	          dropdownMode: true,
	          hideOnDeselect: true,
	          entities: [{
	            id: 'structure-node',
	            options: {
	              selectMode: 'departmentsOnly',
	              restricted: this.isEditMode ? 'update' : 'create',
	              includedNodeEntityTypes: this.includedNodeEntityTypesInDialog,
	              useMultipleTabs: true
	            }
	          }],
	          preselectedItems,
	          events: {
	            onLoad: event => {
	              var _target$selectedItems, _target$selectedItems2, _target$selectedItems3;
	              const dialog = selector.getDialog();
	              dialog.getTabs().filter(tab => isTabEmpty(tab)).forEach(tab => tab.setVisible(false));
	              const target = event.target;
	              const selectedItem = (_target$selectedItems = target.selectedItems) == null ? void 0 : (_target$selectedItems2 = _target$selectedItems.values()) == null ? void 0 : (_target$selectedItems3 = _target$selectedItems2.next()) == null ? void 0 : _target$selectedItems3.value;
	              if (this.isEditMode) {
	                if ((selectedItem == null ? void 0 : selectedItem.id) === this.parentId && this.locked) {
	                  selector.lock();
	                }
	                return;
	              }
	              const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	              if (!permissionChecker) {
	                return;
	              }
	              const nodes = target.items.get('structure-node');
	              const permissionAction = this.isTeamEntity ? humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate : humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate;
	              for (const [, node] of nodes) {
	                var _selectedItem$id;
	                if (permissionChecker.hasPermission(permissionAction, node.id) && !permissionChecker.hasPermission(permissionAction, (_selectedItem$id = selectedItem == null ? void 0 : selectedItem.id) != null ? _selectedItem$id : 0)) {
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
	              const item = this.departments.get(selectedItem.id);
	              if (!item) {
	                return;
	              }
	              const isTeamItem = (item == null ? void 0 : item.entityType) === humanresources_companyStructure_utils.EntityTypes.team;
	              const permissionCreateAction = this.isTeamEntity ? humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate : humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate;
	              const permissionEditAction = isTeamItem ? humanresources_companyStructure_permissionChecker.PermissionActions.teamEdit : humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit;
	              const permissionAction = this.isEditMode ? permissionEditAction : permissionCreateAction;
	              if (!permissionChecker.hasPermission(permissionAction, selectedItem.id)) {
	                this.deniedError = true;
	              }
	              this.applyData();
	            }
	          }
	        }
	      });
	      return selector;
	    },
	    getOrCreateTagSelector() {
	      const key = String(this.isTeamEntity);
	      return this.departmentSelectorCashe.remember(key, () => this.createTagSelector());
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    applyData() {
	      var _this$departmentName2;
	      this.$emit('applyData', {
	        apiEntityChanged: humanresources_companyStructure_utils.WizardApiEntityChangedDict.department,
	        name: this.departmentName,
	        description: this.departmentDescription,
	        parentId: this.selectedParentDepartment,
	        teamColor: this.teamColorValue,
	        isDepartmentDataChanged: true,
	        isValid: this.departmentName && ((_this$departmentName2 = this.departmentName) == null ? void 0 : _this$departmentName2.trim()) !== '' && this.selectedParentDepartment !== null && !this.deniedError
	      });
	    },
	    isTagSelectorLocked() {
	      if (!this.isEditMode) {
	        return false;
	      }
	      if (this.parentId === 0) {
	        return true;
	      }
	      const parent = this.departments.get(this.parentId);
	      if (!parent) {
	        return false;
	      }
	      const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	      if (!permissionChecker) {
	        return true;
	      }
	      if (parent.entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	        return !permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit, parent.id);
	      }
	      if (parent.entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	        return !permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamEdit, parent.id);
	      }
	      return false;
	    }
	  },
	  computed: {
	    includedNodeEntityTypesInDialog() {
	      return this.isTeamEntity ? ['department', 'team'] : ['department'];
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    namePlaceholder() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_NAME_PLACEHOLDER_MSGVER_1') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_PLACEHOLDER');
	    },
	    descriptionPlaceholder() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_DESCR_PLACEHOLDER') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_PLACEHOLDER');
	    },
	    higherLevelDepartmentContainer() {
	      // for team entity department tag selector should be placed after name and description
	      // to presumably encourage user to fill in the description field
	      return this.isTeamEntity ? '.chart-wizard__department_higher_bottom' : '.chart-wizard__department_higher_top';
	    },
	    showLockedSelectorErrorText() {
	      return this.locked && this.parentId !== 0;
	    },
	    selectorErrorText() {
	      if (this.deniedError) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ADD_TO_DEPARTMENT_DENIED_MSG_VER_1');
	      }
	      if (this.locked && this.parentId !== 0) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_PARENT_TEAM_ERROR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_PARENT_DEPARTMENT_ERROR');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_PARENT_ERROR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_PARENT_ERROR');
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments'])
	  },
	  template: `
		<div class="chart-wizard__department">
			<div class="chart-wizard__form">
				<Teleport defer :to="higherLevelDepartmentContainer">
					<span class="chart-wizard__department_item-label">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_WITH_TEAM_LABEL')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_LABEL')
						}}
					</span>
					<div
						:class="{ 'ui-ctl-warning': deniedError || (selectedParentDepartment === null && shouldErrorHighlight) }"
						ref="tag-selector"
					></div>
					<div
						v-if="deniedError || (selectedParentDepartment === null && shouldErrorHighlight) || showLockedSelectorErrorText"
						class="chart-wizard__department_item-error"
						:class="{'--wizard-warning-item':  locked && parentId !== 0}"
					>
						<div
							class="ui-icon-set --warning"
							:style="locked && !deniedError ? { '--ui-icon-set__icon-size': '17px', '--ui-icon-set__icon-color': '#FFA900' } : {}"
						/>
						<span class="chart-wizard__department_item-error-message">
							{{ selectorErrorText }}
						</span>
					</div>
				</Teleport>
				<div
					v-show="!isTeamEntity"
					class="chart-wizard__department_item chart-wizard__department_higher_top">
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_TEAM_NAME_LABEL')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_LABEL') 
						}}
					</span>
					<div class="chart-wizard__department_control-wrapper">
						<div
							class="ui-ctl ui-ctl-textbox"
							:class="{ 'ui-ctl-warning': shouldErrorHighlight && departmentName?.trim() === '' }"
						>
							<input
								v-model="departmentName"
								type="text"
								maxlength="255"
								class="ui-ctl-element"
								ref="title"
								:placeholder="namePlaceholder"
								@input="applyData()"
							/>
						</div>
						<div v-if="isTeamEntity" 
							 class="chart-wizard__department__color-picker" 
							 @click="showColorPicker = true"
							 ref="TeamColorPicker"
							 :data-test-id="'wizard-department-color-picker'"
							 :class="{ '--active': showColorPicker }"
						>
							<div class="chart-wizard__department__color-picker_inner"
								 :style="{ 'background-color': teamColorValue?.pickerColor }"
							></div>
						</div>
					</div>
					<div
						v-if="shouldErrorHighlight && departmentName?.trim() === ''"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__department_item-error-message">
							{{
								isTeamEntity
									? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_NAME_ERROR')
									: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_ERROR')
							}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_LABEL') }}
					</span>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize">
						<textarea
							v-model="departmentDescription"
							maxlength="255"
							class="ui-ctl-element"
							ref="description"
							:placeholder="descriptionPlaceholder"
							@change="applyData()"
						>
						</textarea>
					</div>
				</div>
				<div
					v-show="isTeamEntity"
					class="chart-wizard__department_item chart-wizard__department_higher_bottom"
				></div>
			</div>
			<TeamColorPicker
				v-if="showColorPicker"
				:bindElement="$refs.TeamColorPicker"
				v-model="teamColorValue"
				@update:model-value="applyData()"
				@close="showColorPicker = false"
			/>
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
	    ChangeSaveModeControl,
	    MoveUserPopup: humanresources_companyStructure_structureComponents.MoveUserPopup
	  },
	  emits: ['applyData', 'saveModeChanged'],
	  props: {
	    entityId: {
	      type: Number,
	      required: true
	    },
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
	    },
	    entityType: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {
	      showMoveUserPopup: false,
	      movedUserData: null,
	      movedUserRole: null
	    };
	  },
	  created() {
	    this.memberRoles = humanresources_companyStructure_api.getMemberRoles(this.entityType);
	    this.selectedUsers = new Set();
	    this.departmentHeads = [];
	    this.departmentEmployees = [];
	    this.removedUsers = [];
	    this.headSelector = this.getUserSelector(humanresources_companyStructure_api.memberRolesKeys.head);
	    this.deputySelector = this.getUserSelector(humanresources_companyStructure_api.memberRolesKeys.deputyHead);
	    this.employeesSelector = this.getUserSelector(humanresources_companyStructure_api.memberRolesKeys.employee);
	    this.moveUsersMap = {};
	    this.userCount = 0;

	    // store initial users to control applyData method in tagSelector
	    this.initialUsers = this.heads.reduce((set, item) => set.add(item.id), new Set());
	    this.employeesIds.forEach(item => this.initialUsers.add(item));
	  },
	  mounted() {
	    this.headSelector.renderTo(this.$refs['head-selector']);
	    this.deputySelector.renderTo(this.$refs['deputy-selector']);
	    this.employeesSelector.renderTo(this.$refs['employees-selector']);
	  },
	  activated() {
	    this.$emit('applyData', {
	      isDepartmentDataChanged: false,
	      isValid: true
	    });
	  },
	  watch: {
	    entityType(entityType) {
	      const prevMemberRoles = this.memberRoles;
	      const rolesKeys = Object.keys(prevMemberRoles);
	      this.memberRoles = humanresources_companyStructure_api.getMemberRoles(entityType);
	      this.departmentHeads = this.departmentHeads.map(item => {
	        const roleKey = rolesKeys.find(key => prevMemberRoles[key] === item.role);
	        return {
	          ...item,
	          role: this.memberRoles[roleKey]
	        };
	      });
	      this.departmentEmployees = this.departmentEmployees.map(item => ({
	        ...item,
	        role: this.memberRoles.employee
	      }));
	    },
	    employeesIds: {
	      handler(payload) {
	        this.employeesIds.forEach(item => this.initialUsers.add(item));
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
	      if (this.memberRoles.employee === role) {
	        return this.employeesIds.map(employeeId => ['user', employeeId]);
	      }
	      return this.heads.filter(head => head.role === role).map(head => {
	        return ['user', head.id];
	      });
	    },
	    getUserSelector(roleKey) {
	      const selector = new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            this.selectedUsers.add(tag.id);
	            this.onSelectorToggle(tag, this.memberRoles[roleKey]);
	            if (this.initialUsers.has(tag.id)) {
	              this.initialUsers.delete(tag.id);
	            } else {
	              this.applyData();
	            }
	          },
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            this.selectedUsers.delete(tag.id);
	            this.onSelectorToggle(tag, this.memberRoles[roleKey]);
	            this.applyData();
	          }
	        },
	        multiple: true,
	        dialogOptions: {
	          preselectedItems: this.getPreselectedItems(this.memberRoles[roleKey]),
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
	      const isEmployee = role === this.memberRoles.employee;
	      if (!tag.rendered) {
	        var _this$movedUserData;
	        if (((_this$movedUserData = this.movedUserData) == null ? void 0 : _this$movedUserData.id) === userData.id) {
	          return;
	        }
	        this.removedUsers = this.removedUsers.filter(user => user.id !== userData.id);
	        Object.keys(this.moveUsersMap).forEach(nodeId => {
	          this.moveUsersMap[nodeId] = this.moveUsersMap[nodeId].filter(user => user.id !== userData.id);
	        });
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
	      this.movedUserData = userData;
	      this.movedUserRole = role;
	      if (!this.isTeamEntity && this.isUserPreselected(tag.selector, userData.id)) {
	        this.showMoveUserPopup = true;
	      } else {
	        this.handleMoveUserAction(null);
	      }
	    },
	    handleMoveUserAction(newNodeId) {
	      // return user to selector if we try to move user to the same department/team
	      if (newNodeId === this.entityId) {
	        this.handleAbortMove();
	        return;
	      }
	      const selector = this.getSelectorByRole(this.movedUserRole);
	      if (this.isUserPreselected(selector, this.movedUserData.id)) {
	        this.removedUsers = [...this.removedUsers, {
	          ...this.movedUserData,
	          role: this.movedUserRole
	        }];
	        if (newNodeId) {
	          this.moveUsersMap[newNodeId] = this.moveUsersMap[newNodeId] ? [...this.moveUsersMap[newNodeId], this.movedUserData] : [this.movedUserData];
	        }
	      }
	      const isEmployee = this.movedUserRole === this.memberRoles.employee;
	      if (isEmployee) {
	        this.departmentEmployees = this.departmentEmployees.filter(employee => employee.id !== this.movedUserData.id);
	      } else {
	        this.departmentHeads = this.departmentHeads.filter(head => head.id !== this.movedUserData.id);
	      }
	      this.userCount -= 1;
	      this.showMoveUserPopup = false;
	      this.movedUserData = null;
	      this.movedUserRole = null;
	      this.applyData();
	    },
	    handleAbortMove() {
	      const selector = this.getSelectorByRole(this.movedUserRole);
	      const tag = selector.getDialog().getItem({
	        id: this.movedUserData.id,
	        entityId: 'user'
	      });
	      tag.select();
	      this.showMoveUserPopup = false;
	      this.movedUserData = null;
	      this.movedUserRole = null;
	    },
	    getSelectorByRole(role) {
	      switch (role) {
	        case this.memberRoles.head:
	          return this.headSelector;
	        case this.memberRoles.deputyHead:
	          return this.deputySelector;
	        default:
	          return this.employeesSelector;
	      }
	    },
	    applyData() {
	      this.$emit('applyData', {
	        apiEntityChanged: humanresources_companyStructure_utils.WizardApiEntityChangedDict.employees,
	        heads: this.departmentHeads,
	        employees: this.departmentEmployees,
	        removedUsers: this.removedUsers,
	        moveUsersMap: this.moveUsersMap,
	        userCount: this.userCount,
	        isDepartmentDataChanged: true
	      });
	    },
	    handleSaveModeChangedChanged(actionId) {
	      this.$emit('saveModeChanged', actionId);
	    },
	    isUserPreselected(selector, userId) {
	      const {
	        preselectedItems = []
	      } = selector.dialog;
	      const parsedPreselected = preselectedItems.flat().filter(preselectedItem => preselectedItem !== 'user');
	      return parsedPreselected.includes(userId);
	    }
	  },
	  computed: {
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    headTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_TEAM_HEAD_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE');
	    },
	    headDescription() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_TEAM_HEAD_DESCR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_DESCR');
	    },
	    deputyTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_TEAM_DEPUTY_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE');
	    },
	    deputyDescription() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_TEAM_DEPUTY_DESCR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_DESCR');
	    },
	    employeeTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_TEAM_EMPLOYEES_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_EMPLOYEES_TITLE');
	    }
	  },
	  template: `
		<div class="chart-wizard__employee">
			<div class="chart-wizard__form">
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{ headTitle }}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="head-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-head-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{ headDescription }}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{ deputyTitle }}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="deputy-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-deputy-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{ deputyDescription }}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{ employeeTitle }}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="employees-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-employee-selector"
					/>
				</div>
				<div v-if="!isTeamEntity" class="chart-wizard__employee_item --change-save-mode-control">
					<ChangeSaveModeControl
						v-if="!isEditMode"
						@saveModeChanged="handleSaveModeChangedChanged"
					></ChangeSaveModeControl>
					<div class="chart-wizard__change-save-mode-control-container" v-else>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_EDIT_WIZARD_EMPLOYEE_SAVE_MODE_TEXT') }}
					</div>
				</div>
			</div>
		</div>
		<MoveUserPopup
			v-if="showMoveUserPopup"
			:originalNodeId="entityId"
			:user="movedUserData"
			:entityType="entityType"
			:executeAction="false"
			:onlyMove="false"
			@action="handleMoveUserAction"
			@close="handleAbortMove"
			@remove="handleMoveUserAction"
		/>
	`
	};

	class AbstractSelectorDictionary {
	  constructor() {
	    this.phrases = {};
	    this.permissionAction = {
	      team: '',
	      department: ''
	    };
	  }
	  getPhrase(key, isTeamEntity) {
	    const phraseSet = this.phrases[key];
	    if (!phraseSet) {
	      throw new Error(`Phrase set for key "${key}" is not defined in ${this.constructor.name} selector dictionary.`);
	    }
	    return isTeamEntity ? phraseSet.team : phraseSet.default;
	  }
	  getEntityName() {
	    return 'im-chat-only';
	  }
	  getTagId(chat) {
	    return `chat${chat.id}`;
	  }
	  getItemId(tag) {
	    return Number(tag.id.replace('chat', ''));
	  }
	  getEntity() {}
	  getDialogEvents(entityId, isTeamEntity, isEditMode) {
	    return {
	      onLoad: event => {
	        const dialog = event.getTarget();
	        if (!this.canEdit(entityId, isTeamEntity, isEditMode)) {
	          dialog.getTagSelector().lock();
	        }
	      }
	    };
	  }
	  getTestId(blueprint) {}
	  canEdit(entityId, isTeamEntity, isEditMode) {
	    if (!isEditMode) {
	      return true;
	    }
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (!permissionChecker) {
	      return false;
	    }
	    const permissionAction = isTeamEntity ? this.permissionAction.team : this.permissionAction.department;
	    return permissionChecker.hasPermission(permissionAction, entityId);
	  }
	  getRemovePhrase(hasCurrentUser, isTeamEntity) {
	    throw new Error('getRemovePhrase must be implemented in inheritor');
	  }
	}

	class ChannelSelectorDictionary extends AbstractSelectorDictionary {
	  constructor(...args) {
	    super(...args);
	    this.phrases = {
	      hintText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_HINT',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_HINT_MSGVER_1'
	      },
	      createText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_LABEL_MSGVER_1',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_LABEL_MSGVER_2'
	      },
	      warningText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_WARNING',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_WARNING'
	      }
	    };
	    this.permissionAction = {
	      department: humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit,
	      team: humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit
	    };
	  }
	  getEntity() {
	    return humanresources_companyStructure_structureComponents.getChannelDialogEntity();
	  }
	  getTestId(blueprint) {
	    return blueprint.replace('chat', 'channel');
	  }
	  getRemovePhrase(hasCurrentUser, isTeamEntity) {
	    if (hasCurrentUser) {
	      return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_MSGVER_1' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_MSGVER_1';
	    }
	    return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE';
	  }
	}

	class ChatSelectorDictionary extends AbstractSelectorDictionary {
	  constructor(...args) {
	    super(...args);
	    this.phrases = {
	      hintText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_ADD_CHECKBOX_HINT',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_ADD_CHECKBOX_HINT_MSGVER_1'
	      },
	      createText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_ADD_CHECKBOX_LABEL_MSGVER_1',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_ADD_CHECKBOX_LABEL_MSGVER_2'
	      },
	      warningText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_ADD_CHECKBOX_WARNING',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_ADD_CHECKBOX_WARNING'
	      }
	    };
	    this.permissionAction = {
	      department: humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit,
	      team: humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit
	    };
	  }
	  getEntity() {
	    return humanresources_companyStructure_structureComponents.getChatDialogEntity();
	  }
	  getTestId(blueprint) {
	    return blueprint;
	  }
	  getRemovePhrase(hasCurrentUser, isTeamEntity) {
	    if (hasCurrentUser) {
	      return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_REMOVE_CHECKBOX_LABEL_MSGVER_1' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_REMOVE_CHECKBOX_LABEL_MSGVER_1';
	    }
	    return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE';
	  }
	}

	class CollabSelectorDictionary extends AbstractSelectorDictionary {
	  constructor(...args) {
	    super(...args);
	    this.phrases = {
	      hintText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_HINT',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_HINT'
	      },
	      createText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_LABEL',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_LABEL'
	      },
	      warningText: {
	        team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_WARNING',
	        default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_WARNING'
	      }
	    };
	    this.permissionAction = {
	      department: humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit,
	      team: humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit
	    };
	  }
	  getEntityName() {
	    return 'project';
	  }
	  getEntity() {
	    return humanresources_companyStructure_structureComponents.getCollabDialogEntity();
	  }
	  getDialogEvents(entityId, isTeamEntity, isEditMode) {
	    return {
	      onLoad: event => {
	        const dialog = event.getTarget();
	        dialog.removeTab('projects');
	        if (!this.canEdit(entityId, isTeamEntity, isEditMode)) {
	          dialog.getTagSelector().lock();
	        }
	      }
	    };
	  }
	  getTagId(chat) {
	    return Number(chat.id);
	  }
	  getItemId(tag) {
	    return tag.id;
	  }
	  getTestId(blueprint) {
	    return blueprint.replace('chat', 'collab');
	  }
	  getRemovePhrase(hasCurrentUser, isTeamEntity) {
	    if (hasCurrentUser) {
	      return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL';
	    }
	    return isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL_NOT_INSIDE' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL_NOT_INSIDE';
	  }
	}

	function createSelectorDictionary(type) {
	  switch (type) {
	    case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.chat:
	      return new ChatSelectorDictionary();
	    case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.channel:
	      return new ChannelSelectorDictionary();
	    case humanresources_companyStructure_structureComponents.CommunicationsTypeDict.collab:
	      return new CollabSelectorDictionary();
	    default:
	      throw new Error('Unknown selector type');
	  }
	}

	const DEFAULT_TAG_ID = 'default';

	// Component for selecting chats or channels in the wizard. Consists of selector, default button, hint and warning
	// @vue/component
	const CommunicationSelector = {
	  name: 'communicationSelector',
	  components: {
	    DefaultHint: humanresources_companyStructure_structureComponents.DefaultHint,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    entityId: {
	      type: Number,
	      required: true
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    headsCreated: {
	      type: Boolean,
	      required: true
	    },
	    hasCurrentUser: {
	      type: Boolean,
	      required: true
	    },
	    isTeamEntity: {
	      type: Boolean,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    },
	    /** @type CommunicationDetailed[] */
	    initCommunications: {
	      type: Array,
	      required: true
	    },
	    /** @type CommunicationsTypeDict.chat | ChatTypeDict.channel | ChatTypeDict.collab */
	    type: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['applyData'],
	  data() {
	    return {
	      createDefault: false,
	      permissionChecker: null
	    };
	  },
	  computed: {
	    selectorDictionary() {
	      return createSelectorDictionary(this.type);
	    },
	    isChannel() {
	      return this.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.channel;
	    },
	    set() {
	      return ui_iconSet_api_core.Set;
	    },
	    hintText() {
	      return this.loc(this.selectorDictionary.getPhrase('hintText', this.isTeamEntity));
	    },
	    createText() {
	      return this.loc(this.selectorDictionary.getPhrase('createText', this.isTeamEntity));
	    },
	    removeText() {
	      return this.loc(this.selectorDictionary.getRemovePhrase(this.hasCurrentUser, this.isTeamEntity));
	    },
	    warningText() {
	      return this.loc(this.selectorDictionary.getPhrase('warningText', this.isTeamEntity));
	    },
	    canBeEdit() {
	      if (!this.isEditMode) {
	        return true;
	      }
	      if (this.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.chat) {
	        return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, this.entityId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, this.entityId);
	      }
	      if (this.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.channel) {
	        return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, this.entityId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, this.entityId);
	      }
	      if (this.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.collab) {
	        return this.isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, this.entityId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, this.entityId);
	      }
	      return false;
	    }
	  },
	  watch: {
	    createDefault(value) {
	      if (value) {
	        this.selector.getDialog().getItem({
	          id: DEFAULT_TAG_ID,
	          entityId: DEFAULT_TAG_ID
	        }).select();
	      } else {
	        this.selector.getDialog().getItem({
	          id: DEFAULT_TAG_ID,
	          entityId: DEFAULT_TAG_ID
	        }).deselect();
	      }
	    },
	    name(value) {
	      const defaultItem = this.selector.getDialog().getItem({
	        id: DEFAULT_TAG_ID,
	        entityId: DEFAULT_TAG_ID
	      });
	      defaultItem.setTitle(value);
	      if (this.createDefault) {
	        defaultItem.deselect();
	        defaultItem.select();
	      }
	    },
	    headsCreated(value) {
	      if (!value) {
	        this.createDefault = false;
	      }
	    },
	    initCommunications: {
	      handler(payload) {
	        payload.forEach(item => this.initialItemsSet.add(this.selectorDictionary.getTagId(item)));
	        const preselectedItems = payload.map(item => [this.selectorDictionary.getEntityName(), this.selectorDictionary.getTagId(item)]);
	        const {
	          dialog
	        } = this.selector;
	        dialog.setPreselectedItems(preselectedItems);
	        dialog.load();
	      }
	    }
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.communications = [];
	    // store initial values to control applyData method in tagSelector
	    this.initialItemsSet = this.initCommunications.reduce((set, item) => set.add(this.selectorDictionary.getTagId(item)), new Set());
	    this.selector = this.getSelector();
	  },
	  mounted() {
	    this.selector.renderTo(this.$refs['communications-selector']);
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    addTag(tag) {
	      if (!tag.searchable) {
	        const existingItem = this.initCommunications.find(item => this.selectorDictionary.getTagId(item) === tag.id);
	        if (existingItem != null && existingItem.title) {
	          tag.setTitle(existingItem.title);
	          if (existingItem.hasAccess) {
	            tag.setDeselectable(true);
	          }
	        }
	      }
	      this.communications.push(this.selectorDictionary.getItemId(tag));
	    },
	    getSelector() {
	      var _entity$tagOptions, _entity$itemOptions;
	      const entity = this.selectorDictionary.getEntity();
	      const options = {
	        multiple: true,
	        events: {
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            if (tag.id === DEFAULT_TAG_ID) {
	              this.createDefault = true;
	            } else {
	              this.addTag(tag);
	            }
	            if (this.initialItemsSet.has(tag.id)) {
	              this.initialItemsSet.delete(tag.id);
	            } else {
	              this.applyData();
	            }
	          },
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            const intId = this.selectorDictionary.getItemId(tag);
	            if (tag.id === DEFAULT_TAG_ID) {
	              this.createDefault = false;
	            } else {
	              this.communications = this.communications.filter(item => item !== intId);
	            }
	            this.applyData();
	          }
	        },
	        locked: !this.canBeEdit,
	        dialogOptions: {
	          enableSearch: true,
	          height: 250,
	          width: 380,
	          dropdownMode: true,
	          events: this.selectorDictionary.getDialogEvents(this.entityId, this.isTeamEntity, this.isEditMode),
	          recentTabOptions: humanresources_companyStructure_structureComponents.getCommunicationsRecentTabOptions(this.entityType, this.type),
	          items: [this.getDefaultItem((_entity$tagOptions = entity.tagOptions) == null ? void 0 : _entity$tagOptions.default, (_entity$itemOptions = entity.itemOptions) == null ? void 0 : _entity$itemOptions.default)],
	          preselectedItems: this.initCommunications.map(item => [this.selectorDictionary.getEntityName(), this.selectorDictionary.getTagId(item)]),
	          entities: [entity]
	        }
	      };
	      return new ui_entitySelector.TagSelector(options);
	    },
	    getDefaultItem(tagOptions = {}, itemOptions = {}) {
	      return {
	        id: DEFAULT_TAG_ID,
	        entityId: DEFAULT_TAG_ID,
	        title: this.name,
	        searchable: false,
	        tagOptions,
	        ...itemOptions
	      };
	    },
	    applyData() {
	      this.$emit('applyData', {
	        type: this.type,
	        communications: this.communications,
	        createDefault: this.createDefault
	      });
	    }
	  },
	  template: `
		<div
			class="chart-wizard__chat_selector"
			ref="communications-selector"
			:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__chat-selector')"
		/>
		<div
			v-if="!createDefault"
			class="chart-wizard__bind-chat__item-checkbox_container"
		>
			<div
				@click="createDefault = headsCreated && canBeEdit"
				class="chart-wizard__bind-chat__item-create"
				:class="{ '--disabled': !headsCreated || !canBeEdit }"
				:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__make-default-chat-create')"
				v-html="createText"
			>
			</div>
			<DefaultHint :content="hintText" />
		</div>
		<div v-else class="chart-wizard__bind-chat__item-checkbox_container">
			<div class="ui-icon-set --o-circle-check"></div>
			<div
				class="chart-wizard__bind-chat__item-remove"
				:class="{ '--disabled': !headsCreated || !canBeEdit }"
				:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__make-default-chat-remove')"
			>
				{{ removeText }}
			</div>
		</div>
		<div v-if="!headsCreated" class="chart-wizard__bind-chat__item-checkbox_warning">
			<BIcon
				:name="set.WARNING"
				color="#FFA900"
				:size="16"
			></BIcon>
			<span>
				{{ warningText }}
			</span>
		</div>
	`
	};

	// @vue/component
	const BindChat = {
	  name: 'bindChat',
	  components: {
	    CommunicationSelector
	  },
	  props: {
	    entityId: {
	      type: Number,
	      required: true
	    },
	    heads: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    employees: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    employeesIds: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    },
	    /** @type CommunicationDetailed[] */
	    initChats: {
	      type: Array,
	      required: true
	    },
	    /** @type CommunicationDetailed[] */
	    initChannels: {
	      type: Array,
	      required: true
	    },
	    /** @type CommunicationDetailed[] */
	    initCollabs: {
	      type: Array,
	      required: true
	    }
	  },
	  emits: ['applyData'],
	  computed: {
	    isCollabsAvailable() {
	      return humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance().isCollabsAvailable;
	    },
	    headsCreated() {
	      const memberRoles = humanresources_companyStructure_api.getMemberRoles(this.entityType);
	      return this.heads.some(item => item.role === memberRoles.head);
	    },
	    hasCurrentUser() {
	      return this.heads.some(item => item.id === this.userId) || this.employeesIds.includes(this.userId) || this.employees.some(item => item.id === this.userId);
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    hints() {
	      if (this.isCollabsAvailable) {
	        if (this.isTeamEntity) {
	          return [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_1_W_COLLABS'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_2_MSGVER_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_3_W_COLLABS')];
	        }
	        return [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1_W_COLLABS'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2_MSGVER_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_3_W_COLLABS')];
	      }
	      if (this.isTeamEntity) {
	        return [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_2_MSGVER_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_3')];
	      }
	      return [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1_MSGVER_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2_MSGVER_2'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_3')];
	    },
	    ChatTypeDict() {
	      return humanresources_companyStructure_structureComponents.CommunicationsTypeDict;
	    },
	    hintTitle() {
	      if (this.isCollabsAvailable) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_TITLE_W_COLLABS') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_TITLE_W_COLLABS');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_TITLE');
	    },
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['userId'])
	  },
	  watch: {
	    initChats(value) {
	      this.chats = value.map(item => item.id);
	    },
	    initChannels(value) {
	      this.channels = value.map(item => item.id);
	    },
	    initCollabs(value) {
	      this.collabs = value.map(item => item.id);
	    }
	  },
	  created() {
	    this.chats = this.initChats.map(item => item.id);
	    this.channels = this.initChannels.map(item => item.id);
	    this.collabs = this.initCollabs.map(item => Number(item.id));
	    this.createDefaultChat = false;
	    this.createDefaultChannel = false;
	    this.createDefaultCollab = false;
	  },
	  activated() {
	    this.$emit('applyData', {
	      isDepartmentDataChanged: false,
	      isValid: true
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    applyData() {
	      this.$emit('applyData', {
	        apiEntityChanged: humanresources_companyStructure_utils.WizardApiEntityChangedDict.bindChats,
	        chats: this.chats,
	        channels: this.channels,
	        collabs: this.collabs,
	        createDefaultChat: this.createDefaultChat,
	        createDefaultChannel: this.createDefaultChannel,
	        createDefaultCollab: this.createDefaultCollab,
	        isDepartmentDataChanged: true
	      });
	    },
	    onCommunicationSelectorChanged(data) {
	      if (data.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.chat) {
	        this.chats = data.communications;
	        this.createDefaultChat = data.createDefault;
	      } else if (data.type === humanresources_companyStructure_structureComponents.CommunicationsTypeDict.channel) {
	        this.channels = data.communications;
	        this.createDefaultChannel = data.createDefault;
	      } else {
	        this.collabs = data.communications;
	        this.createDefaultCollab = data.createDefault;
	      }
	      this.applyData();
	    }
	  },
	  template: `
		<div class="chart-wizard__bind-chat">
			<div class="chart-wizard__bind-chat__item" :class="{ '--team': isTeamEntity }">
				<div v-if="!isEditMode" class="chart-wizard__bind-chat__item-hint">
					<div class="chart-wizard__bind-chat__item-hint_logo"></div>
					<div class="chart-wizard__bind-chat__item-hint_text">
						<div
							class="chart-wizard__bind-chat__item-hint_title"
							v-html="hintTitle"
						>
						</div>
						<div v-for="hint in hints"
							 class="chart-wizard__bind-chat__item-hint_text-item"
						>
							<div class="chart-wizard__bind-chat__item-hint_text-item_icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__bind-chat__item-options" v-if="isCollabsAvailable">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initCollabs"
						:type="ChatTypeDict.collab"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc(
									'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initChannels"
						:type="ChatTypeDict.channel"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initChats"
						:type="ChatTypeDict.chat"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
			</div>
		</div>
	`
	};

	const Entities = {
	  props: {
	    parentId: {
	      type: [Number, null],
	      required: true
	    }
	  },
	  components: {
	    ResponsiveHint: humanresources_companyStructure_structureComponents.ResponsiveHint
	  },
	  data() {
	    return {
	      selectedEntityType: humanresources_companyStructure_utils.EntityTypes.department
	    };
	  },
	  emits: ['applyData'],
	  created() {
	    this.hint = null;
	    this.hintHideTimeout = null;
	    const permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    const hasTeamCreatePermission = this.parentId === 0 ? permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate) : permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCreate, this.parentId);
	    const hasDepartmentCreatePermission = this.parentId === 0 ? permissionChecker.hasPermissionOfAction(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate) : permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCreate, this.parentId);
	    this.entities = [{
	      type: humanresources_companyStructure_utils.EntityTypes.department,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_TITLE'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_DESCR_MSGVER_1'),
	      isEnabled: hasDepartmentCreatePermission,
	      hint: !hasDepartmentCreatePermission && this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_NO_ACCESS_HINT'),
	      isSoon: false
	    }, {
	      type: humanresources_companyStructure_utils.EntityTypes.team,
	      title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_TITLE_MSGVER_1'),
	      description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_DESCR_MSGVER_1'),
	      isEnabled: humanresources_companyStructure_permissionChecker.PermissionChecker.isTeamsAvailable && hasTeamCreatePermission,
	      hint: humanresources_companyStructure_permissionChecker.PermissionChecker.isTeamsAvailable && !hasTeamCreatePermission && this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_NO_ACCESS_HINT_MSGVER_1'),
	      isSoon: !permissionChecker.isTeamsAvailable
	    }];
	    for (const entity of this.entities) {
	      if (entity.isEnabled) {
	        this.selectedEntityType = entity.type;
	        break;
	      }
	    }
	    this.applyData(this.selectedEntityType);
	  },
	  activated() {
	    this.applyData(this.selectedEntityType);
	  },
	  deactivated() {
	    var _this$hint;
	    (_this$hint = this.hint) == null ? void 0 : _this$hint.hide();
	    clearTimeout(this.hintHideTimeout);
	    this.hintHideTimeout = null;
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    applyData(entityId) {
	      this.selectedEntityType = entityId;
	      this.$emit('applyData', {
	        isValid: true,
	        isDepartmentDataChanged: false,
	        entityType: this.selectedEntityType
	      });
	    },
	    showHint(text, $event) {
	      if (this.hintHideTimeout) {
	        return;
	      }
	      const width = 300;
	      this.hint = main_core.Reflection.getClass('BX.UI.Hint').createInstance({
	        popupParameters: {
	          width,
	          bindOptions: {
	            position: 'top'
	          },
	          offsetLeft: 225,
	          offsetTop: -23,
	          angle: {
	            offset: width / 2 - 33 / 2
	          }
	        }
	      });
	      this.hint.show($event.target, text);
	      this.hintHideTimeout = setTimeout(() => {
	        var _this$hint2;
	        (_this$hint2 = this.hint) == null ? void 0 : _this$hint2.hide();
	        this.hintHideTimeout = null;
	      }, 2000);
	    }
	  },
	  template: `
		<div
			v-for="entity in entities"
			class="chart-wizard__entity-wrapper"
		>
			<span v-if="entity.hint" @click="showHint(entity.hint, $event)" class="ui-hint chart-wizard__entity_hint-layout"></span>
			<div class="chart-wizard__entity"
				 :class="{ 
					['--' + entity.type.toLowerCase()]: true, 
					'--selected': entity.type === selectedEntityType, 
					'--enabled': entity.isEnabled 
				}"
				@click="entity.isEnabled && applyData(entity.type)"
			>
				<div class="chart-wizard__entity_summary">
					<h2
						class="chart-wizard__entity_title"
						:data-title="entity.isSoon ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE') : null"
						:class="{ '--disabled': !entity.isEnabled, '--soon': entity.isSoon }"
					>
						{{entity.title}}
					</h2>
					<p class="chart-wizard__entity_description" :class="{ '--disabled': !entity.isEnabled}">
						{{entity.description}}
					</p>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const Settings = {
	  name: 'NodeSettings',
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    /** @type {Record<string, Set>} */
	    settings: {
	      type: Object,
	      required: false,
	      default: () => {}
	    },
	    /** @type {Record<string, boolean>} */
	    features: {
	      type: Object,
	      required: false,
	      default: () => {}
	    },
	    shouldErrorHighlight: {
	      type: Boolean,
	      required: true
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    /** @type UserData[] */
	    heads: {
	      type: Array,
	      required: false,
	      default: () => []
	    }
	  },
	  emits: ['applyData'],
	  computed: {
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    isBusinessProcHeadNotSelected() {
	      if (!this.isTeamEntity) {
	        return false;
	      }
	      const bpSettings = this.settings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority];
	      return !bpSettings || bpSettings.size === 0;
	    },
	    businessDescription() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_DESCRIPTION', {
	        '#DEPARTMENT_NAME#': main_core.Text.encode(this.name)
	      }) : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_DESCRIPTION');
	    },
	    reportsDescriptions() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_REPORTS_DESCRIPTION', {
	        '#DEPARTMENT_NAME#': main_core.Text.encode(this.name)
	      }) : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_REPORTS_DESCRIPTION');
	    },
	    hintTitle() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_HINT_TITLE');
	    },
	    businessProcWarningText() {
	      if (this.isTeamEntity) {
	        return null;
	      }
	      const memberRoles = humanresources_companyStructure_api.getMemberRoles(this.entityType);
	      const hasHead = this.heads.some(item => item.role === memberRoles.head);
	      const hasDeputy = this.heads.some(item => item.role === memberRoles.deputyHead);
	      const headSelected = this.settings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority].has(AuthorityTypes.departmentHead);
	      const deputySelected = this.settings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority].has(AuthorityTypes.departmentDeputy);
	      if (headSelected && hasHead && deputySelected && !hasDeputy) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_HAS_HEAD_NO_DEPUTY');
	      }
	      if (headSelected && !hasHead && deputySelected && hasDeputy) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD_HAS_DEPUTY');
	      }
	      if (headSelected && !hasHead && deputySelected && !hasDeputy) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD_NO_DEPUTY');
	      }
	      if (headSelected && !hasHead && !deputySelected) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD');
	      }
	      if (!headSelected && deputySelected && !hasDeputy) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_DEPUTY');
	      }
	      if (!headSelected && !deputySelected) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_EMPTY');
	      }
	      return null;
	    }
	  },
	  watch: {
	    settings: {
	      handler(payload) {
	        if (payload[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]) {
	          const businessProcPreselected = this.getTagItems(false).filter(item => payload[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority].has(item.id));
	          businessProcPreselected.forEach(businessProcPreselectedItem => {
	            const item = this.businessProcSelector.dialog.getItem(['head-type', businessProcPreselectedItem.id]);
	            if (item) {
	              this.initBpValues.add(businessProcPreselectedItem.id);
	              item.select();
	            }
	          });
	        }
	      }
	    }
	  },
	  created() {
	    this.hints = [this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_1'), this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_2')];
	    this.initBpValues = new Set();
	    this.businessProcSelector = this.getTagSelector(humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority, false);
	    this.reportsSelector = this.getTagSelector(humanresources_companyStructure_utils.NodeSettingsTypes.reportsAuthority, true);
	  },
	  mounted() {
	    this.businessProcSelector.renderTo(this.$refs['business-proc-selector']);
	    this.reportsSelector.renderTo(this.$refs['reports-selector']);
	  },
	  activated() {
	    this.$emit('applyData', {
	      isDepartmentDataChanged: false,
	      isValid: !this.isBusinessProcHeadNotSelected
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    applyData() {
	      this.$emit('applyData', {
	        apiEntityChanged: humanresources_companyStructure_utils.WizardApiEntityChangedDict.settings,
	        settings: this.settings,
	        isDepartmentDataChanged: true,
	        isValid: !this.isBusinessProcHeadNotSelected
	      });
	    },
	    getTagSelector(settingType, locked) {
	      return new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            this.settings[settingType].add(tag.id);
	            if (this.initBpValues.has(tag.id)) {
	              this.initBpValues.delete(tag.id);
	            } else {
	              this.applyData();
	            }
	          },
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            this.settings[settingType].delete(tag.id);
	            this.applyData();
	          }
	        },
	        multiple: true,
	        id: 'head-type-selector',
	        locked,
	        tagFontWeight: '700',
	        showAddButton: !locked,
	        dialogOptions: {
	          id: 'head-type-selector',
	          events: {
	            'Item:onBeforeSelect': event => {
	              var _item$getCustomData;
	              const {
	                item
	              } = event.getData();
	              if (!((_item$getCustomData = item.getCustomData()) != null && _item$getCustomData.get('selectable'))) {
	                event.preventDefault();
	              }
	            }
	          },
	          width: 400,
	          height: 200,
	          tagMaxWidth: 400,
	          dropdownMode: true,
	          showAvatars: false,
	          selectedItems: this.getTagItems(locked).filter(item => this.settings[settingType].has(item.id)),
	          items: this.getTagItems(locked),
	          undeselectedItems: this.features.isDeputyApprovesBPAvailable || !this.isTeamEntity ? [] : [['head-type', AuthorityTypes.departmentHead]]
	        }
	      });
	    },
	    getTagItems(locked) {
	      const lockedTagOptions = {
	        bgColor: '#BDC1C6',
	        textColor: '#525C69'
	      };
	      const departmentTagOptions = {
	        bgColor: '#ADE7E4',
	        textColor: '#207976',
	        maxWidth: 400
	      };
	      const teamTagOptions = {
	        bgColor: '#CCE3FF',
	        textColor: '#3592FF',
	        maxWidth: 400
	      };
	      const soonItemOptions = {
	        customData: {
	          selectable: false
	        },
	        textColor: '#C9CCD0',
	        badges: [{
	          title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_SOON_ITEM_BADGE'),
	          textColor: '#FFFFFF',
	          bgColor: '#2FC6F6'
	        }],
	        badgesOptions: {
	          justifyContent: 'right'
	        }
	      };
	      const deputyOptions = this.features.isDeputyApprovesBPAvailable || !this.isTeamEntity ? {
	        customData: {
	          selectable: true
	        }
	      } : soonItemOptions;
	      const departmentHead = {
	        id: AuthorityTypes.departmentHead,
	        entityId: 'head-type',
	        tabs: 'recents',
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_DEPARTMENT_HEAD_ITEM'),
	        tagOptions: locked ? lockedTagOptions : departmentTagOptions,
	        customData: {
	          selectable: true
	        }
	      };
	      const teamHead = {
	        id: AuthorityTypes.teamHead,
	        entityId: 'head-type',
	        tabs: 'recents',
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_TEAM_HEAD_ITEM'),
	        tagOptions: locked ? lockedTagOptions : teamTagOptions,
	        customData: {
	          selectable: true
	        }
	      };
	      const departmentDeputy = {
	        id: AuthorityTypes.departmentDeputy,
	        entityId: 'head-type',
	        tabs: 'recents',
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_DEPARTMENT_DEPUTY_ITEM'),
	        tagOptions: locked ? lockedTagOptions : departmentTagOptions,
	        ...deputyOptions
	      };
	      const teamDeputy = {
	        id: AuthorityTypes.teamDeputy,
	        entityId: 'head-type',
	        tabs: 'recents',
	        title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_TEAM_DEPUTY_ITEM'),
	        tagOptions: locked ? lockedTagOptions : teamTagOptions,
	        ...deputyOptions
	      };
	      if (this.isTeamEntity) {
	        return this.features.isDeputyApprovesBPAvailable ? [departmentHead, departmentDeputy, teamHead, teamDeputy] : [departmentHead, teamHead, departmentDeputy, teamDeputy];
	      }
	      return [departmentHead, departmentDeputy];
	    },
	    goToBPHelp(event) {
	      if (top.BX.Helper) {
	        event.preventDefault();
	        top.BX.Helper.show('redirect=detail&code=25455744');
	      }
	    }
	  },
	  template: `
		<div class="chart-wizard__settings">
			<div class="chart-wizard__settings__item" :class="{ '--team': isTeamEntity }">
				<div class="chart-wizard__settings__item-hint">
					<div class="chart-wizard__settings__item-hint_logo"></div>
					<div class="chart-wizard__settings__item-hint_text">
						<div class="chart-wizard__settings__item-hint_title">
							{{ hintTitle }}
						</div>
						<div v-for="hint in hints"
							class="chart-wizard__settings__item-hint_text-item"
						>
							<div class="chart-wizard__settings__item-hint_text-item_icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__settings__item-options">
					<div class="chart-wizard__settings__item-options__item-content_title"
						 :data-title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE')"
					>
						<div class="chart-wizard__settings__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_TITLE') }}
						</div>
						<span v-if="isTeamEntity" class="ui-hint" @click="goToBPHelp">
							<span class="ui-hint-icon"/>
						</span>
					</div>
					<div class="chart-wizard__settings__item-description-container">
						<span class="chart-wizard__settings__item-description-text" v-html="businessDescription">
						</span>
					</div>
					<div
						class="chart-wizard__settings__business-proc-selector"
						:class="{ 'ui-ctl-warning': (isBusinessProcHeadNotSelected) }"
						ref="business-proc-selector"
						data-test-id="hr-company-structure__settings__business-proc-selector"
					/>
					<div
						v-if="isBusinessProcHeadNotSelected"
						class="chart-wizard__settings__item-options-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__settings__item-options-error-message">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_HEAD_ERROR') }}
						</span>
					</div>
					<div
						v-else-if="businessProcWarningText"
						class="chart-wizard__settings__item-options-warning"
					>
						<div class="ui-icon-set --warning"></div>
						<span>
							{{ businessProcWarningText }}
						</span>
					</div>
				</div>
				<div class="chart-wizard__settings__item-options">
					<div class="chart-wizard__settings__item-options__item-content_title --soon"
						 :data-title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE')"
					>
						<div class="chart-wizard__settings__item-options__item-content_title-text --soon">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_REPORTS_TITLE') }}
						</div>
						<span v-if="isTeamEntity" class="chart-wizard__settings_ui-hint-disabled">
							<span class="ui-hint-icon"/>
						</span>
					</div>
					<div class="chart-wizard__settings__item-description-container">
						<span class="chart-wizard__settings__item-description-text --soon" v-html="reportsDescriptions">
						</span>
					</div>
					<div
						class="chart-wizard__settings__reports-selector"
						ref="reports-selector"
						data-test-id="hr-company-structure__settings__reports-selector"
					/>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const AccessDenied = {
	  name: 'AccessDenied',
	  mounted() {
	    // enable 'next' button
	    this.$emit('applyData', {
	      isDepartmentDataChanged: false,
	      isValid: true
	    });
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div class="chart-wizard__access-denied">
			<div class="chart-wizard__access-denied_icon"></div>
			<div class="chart-wizard__access-denied_title">
				{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ACCESS_DENIED_TITLE') }}
			</div>
			<div class="chart-wizard__access-denied_description">
				{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ACCESS_DENIED_DESCRIPTION') }}
			</div>
		</div>
	`
	};

	const WizardAPI = {
	  createDepartment: (name, parentId, description, userIds, moveUsersToDepartment, createChat, bindingChatIds, createChannel, bindingChannelIds, createCollab, bindingCollabIds, settings) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Department.create', {
	      name,
	      parentId,
	      description,
	      userIds,
	      moveUsersToDepartment,
	      createChat,
	      bindingChatIds,
	      createChannel,
	      bindingChannelIds,
	      createCollab,
	      bindingCollabIds,
	      settings
	    });
	  },
	  createTeam: (name, parentId, description, colorName, userIds, createChat, bindingChatIds, createChannel, bindingChannelIds, createCollab, bindingCollabIds, settings) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Team.create', {
	      name,
	      parentId,
	      description,
	      colorName,
	      userIds,
	      createChat,
	      bindingChatIds,
	      createChannel,
	      bindingChannelIds,
	      createCollab,
	      bindingCollabIds,
	      settings
	    });
	  },
	  addDepartment: (name, parentId, description, entityType, colorName) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.add', {
	      name,
	      parentId,
	      description,
	      entityType,
	      colorName
	    });
	  },
	  getEmployees: nodeId => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Employee.getIds', {
	      nodeId
	    });
	  },
	  updateDepartment: (nodeId, parentId, name, description, colorName) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.update', {
	      nodeId,
	      name,
	      parentId,
	      description,
	      colorName
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
	  },
	  saveChats: (nodeId, ids, createDefault, removeIds) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveChatList', {
	      nodeId,
	      createDefault,
	      ids,
	      removeIds
	    });
	  },
	  saveChannels: (nodeId, ids, createDefault, removeIds) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveChannelList', {
	      nodeId,
	      createDefault,
	      ids,
	      removeIds
	    });
	  },
	  saveCollabs: (nodeId, ids, createDefault, removeIds) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.Chat.saveCollabList', {
	      nodeId,
	      createDefault,
	      ids,
	      removeIds
	    });
	  },
	  createSettings: (nodeId, settings, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.NodeSettings.create', {
	      settings,
	      nodeId,
	      parentId
	    });
	  },
	  updateSettings: (nodeId, settings, parentId) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.NodeSettings.update', {
	      settings,
	      nodeId,
	      parentId
	    });
	  },
	  getSettings: nodeId => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.NodeSettings.get', {
	      nodeId
	    });
	  },
	  getChatsAndChannels: nodeId => {
	    return humanresources_companyStructure_api.getData('humanresources.api.Structure.Node.Member.Chat.getList', {
	      nodeId
	    });
	  }
	};

	const chartWizardActions = {
	  createDepartment: async departmentData => {
	    var _parent$children;
	    const {
	      departments,
	      structureMap
	    } = humanresources_companyStructure_chartStore.useChartStore();
	    const {
	      id: departmentId,
	      parentId,
	      entityType
	    } = departmentData;
	    const parent = departments.get(parentId);
	    parent.children = [...((_parent$children = parent.children) != null ? _parent$children : []), departmentId];
	    structureMap.set(departmentId, {
	      id: departmentId,
	      parentId,
	      entityType
	    });
	    departments.set(departmentId, {
	      ...departmentData,
	      id: departmentId,
	      chats: null,
	      channels: null
	    });
	    await humanresources_companyStructure_chartStore.UserService.refreshMultipleUsers();
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
	    humanresources_companyStructure_chartStore.UserService.refreshMultipleUsers();
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

	const SaveMode$1 = Object.freeze({
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
	    Entities,
	    Settings,
	    AccessDenied
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
	    /** @type StepIdType */
	    entity: {
	      type: String
	    },
	    entityType: {
	      type: String,
	      default: humanresources_companyStructure_utils.EntityTypes.department
	    },
	    source: {
	      type: String
	    },
	    refToFocus: {
	      type: String,
	      default: null
	    }
	  },
	  data() {
	    return {
	      stepIndex: 0,
	      waiting: false,
	      isValidStep: false,
	      isDepartmentDataChanged: false,
	      departmentData: {
	        id: 0,
	        parentId: 0,
	        name: '',
	        description: '',
	        heads: [],
	        employees: [],
	        chats: [],
	        channels: [],
	        collabs: [],
	        userCount: 0,
	        createDefaultChat: false,
	        createDefaultChannel: false,
	        teamColor: humanresources_companyStructure_utils.NodeColorsSettingsDict.blue,
	        entityType: humanresources_companyStructure_utils.EntityTypes.department,
	        settings: {
	          [humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]: new Set([AuthorityTypes.departmentHead]),
	          [humanresources_companyStructure_utils.NodeSettingsTypes.reportsAuthority]: new Set([AuthorityTypes.departmentHead])
	        }
	      },
	      removedUsers: [],
	      moveUsersMap: [],
	      employeesIds: [],
	      departmentSettings: {
	        [humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]: new Set([AuthorityTypes.departmentHead]),
	        [humanresources_companyStructure_utils.NodeSettingsTypes.reportsAuthority]: new Set([AuthorityTypes.departmentHead])
	      },
	      initChats: [],
	      initChannels: [],
	      initCollabs: [],
	      shouldErrorHighlight: false,
	      steps: [],
	      saveMode: SaveMode$1.moveUsers,
	      permissionChecker: null
	    };
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    this.init();
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(window, 'beforeunload', this.handleBeforeUnload);
	  },
	  computed: {
	    breadcrumbsTitle() {
	      if (this.isEditMode) {
	        return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TEAM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TITLE');
	      }
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TEAM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TITLE');
	    },
	    closeConfirmTitle() {
	      var _this$departmentData2;
	      if (this.isEditMode) {
	        var _this$departmentData;
	        return ((_this$departmentData = this.departmentData) == null ? void 0 : _this$departmentData.entityType) === humanresources_companyStructure_utils.EntityTypes.team ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_EDIT_WIZARD_TEAM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_EDIT_WIZARD_DEPARTMENT_TITLE');
	      }
	      return ((_this$departmentData2 = this.departmentData) == null ? void 0 : _this$departmentData2.entityType) === humanresources_companyStructure_utils.EntityTypes.team ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_CREATE_WIZARD_TEAM_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_CREATE_WIZARD_DEPARTMENT_TITLE');
	    },
	    currentStep() {
	      return this.steps[this.stepIndex];
	    },
	    isDeputyApprovesBPAvailable() {
	      if (!this.permissionChecker) {
	        return false;
	      }
	      return this.permissionChecker.checkDeputyApprovalBPAvailable();
	    },
	    isDepartmentSettingsAvailable() {
	      if (!this.permissionChecker) {
	        return false;
	      }
	      return this.permissionChecker.checkDepartmentSettingsAvailable();
	    },
	    componentInfo() {
	      if (!this.currentStep.isPermitted) {
	        return {
	          name: 'AccessDenied'
	        };
	      }
	      const {
	        parentId,
	        name,
	        description,
	        heads,
	        entityType,
	        teamColor,
	        employees
	      } = this.departmentData;
	      const components = {
	        [StepIds.department]: {
	          name: 'Department',
	          params: {
	            parentId,
	            name,
	            description,
	            entityType,
	            teamColor,
	            refToFocus: this.refToFocus,
	            shouldErrorHighlight: this.shouldErrorHighlight,
	            isEditMode: this.isEditMode
	          }
	        },
	        [StepIds.employees]: {
	          name: 'Employees',
	          params: {
	            entityId: this.nodeId,
	            heads,
	            entityType,
	            employeesIds: this.employeesIds,
	            isEditMode: this.isEditMode
	          }
	        },
	        [StepIds.bindChat]: {
	          name: 'BindChat',
	          params: {
	            entityId: this.nodeId,
	            heads,
	            employees,
	            employeesIds: this.employeesIds,
	            name,
	            entityType,
	            isEditMode: this.isEditMode,
	            initChats: this.initChats,
	            initChannels: this.initChannels,
	            initCollabs: this.initCollabs
	          }
	        },
	        [StepIds.settings]: {
	          name: 'Settings',
	          params: {
	            name,
	            heads,
	            settings: this.departmentSettings,
	            entityType,
	            features: {
	              isDeputyApprovesBPAvailable: this.isDeputyApprovesBPAvailable
	            },
	            shouldErrorHighlight: this.shouldErrorHighlight
	          }
	        },
	        [StepIds.entities]: {
	          name: 'Entities',
	          params: {
	            parentId
	          }
	        }
	      };
	      return components[this.currentStep.id];
	    },
	    isFirstStep() {
	      return this.currentStep.id === StepIds.entities;
	    },
	    breadcrumbsSteps() {
	      return this.steps.filter(step => step.hasBreadcrumbs);
	    },
	    rootId() {
	      const {
	        id
	      } = [...this.departments.values()].find(department => {
	        return department.parentId === 0;
	      });
	      return id;
	    },
	    isTeamEntity() {
	      var _this$departmentData3;
	      return ((_this$departmentData3 = this.departmentData) == null ? void 0 : _this$departmentData3.entityType) === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    entityAnalyticsCategory() {
	      return this.isTeamEntity ? 'team' : 'dept';
	    },
	    createButtonText() {
	      return this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TEAM_BTN') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_BTN');
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
	    mapRawSettings(rawSettings) {
	      return rawSettings.reduce((acc, {
	        settingsType,
	        settingsValue
	      }) => {
	        if (!Object.hasOwn(acc, settingsType)) {
	          acc[settingsType] = new Set();
	        }
	        acc[settingsType].add(settingsValue);
	        return acc;
	      }, {});
	    },
	    async init() {
	      this.apiEntityChanged = new Set();

	      // Important: we need to call createSteps as soon as we get entityType or determine a lack of it

	      main_core.Event.bind(window, 'beforeunload', this.handleBeforeUnload);
	      if (this.isEditMode) {
	        const {
	          id,
	          name,
	          description,
	          parentId,
	          heads,
	          userCount,
	          children,
	          entityType,
	          teamColor,
	          employees = []
	        } = this.departments.get(this.nodeId);
	        this.getMemberRoles(entityType);
	        this.createSteps(entityType, id);
	        this.stepIndex = this.steps.indexOf(this.getStepById(this.entity));
	        this.createDefaultSaveMode(entityType);
	        this.departmentData = {
	          ...this.departmentData,
	          id,
	          parentId,
	          name,
	          description,
	          heads,
	          userCount,
	          children,
	          employees,
	          entityType,
	          teamColor,
	          createDefaultChat: false,
	          createDefaultChannel: false
	        };
	        const rawSettings = await WizardAPI.getSettings(this.nodeId);
	        const newSettings = this.mapRawSettings(rawSettings);
	        this.departmentSettings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority] = new Set();
	        this.departmentSettings = {
	          ...this.departmentSettings,
	          ...newSettings
	        };

	        // store directly bound chats and channels
	        const rawChatsAndChannels = await WizardAPI.getChatsAndChannels(this.nodeId);
	        this.initChats = rawChatsAndChannels.chats.filter(item => !item.originalNodeId);
	        this.initChannels = rawChatsAndChannels.channels.filter(item => !item.originalNodeId);
	        this.initCollabs = rawChatsAndChannels.collabs.filter(item => !item.originalNodeId);
	        this.employeesIds = await WizardAPI.getEmployees(this.nodeId);
	        return;
	      }
	      if (this.entityType) {
	        this.departmentData.entityType = this.entityType;
	      }
	      this.getMemberRoles(this.entityType);
	      this.createSteps(this.entityType, this.departmentData.id);
	      this.createDefaultSaveMode(this.entityType);
	      if (this.nodeId) {
	        this.departmentData.parentId = this.nodeId;
	        return;
	      }
	      this.departmentData.parentId = 0;
	      ui_analytics.sendData({
	        tool: 'structure',
	        category: 'structure',
	        event: 'create_wizard',
	        c_element: this.source
	      });
	    },
	    getMemberRoles(entityType) {
	      this.memberRoles = humanresources_companyStructure_api.getMemberRoles(entityType);
	    },
	    move(buttonId = 'next') {
	      if (!this.isValidStep) {
	        this.shouldErrorHighlight = true;
	        return;
	      }
	      this.stepIndex = buttonId === 'back' ? this.stepIndex - 1 : this.stepIndex + 1;
	      this.pickStepsAnalytics();
	    },
	    moveToStep(stepId) {
	      if (!this.isValidStep) {
	        this.shouldErrorHighlight = true;
	        return;
	      }
	      this.stepIndex = this.steps.indexOf(this.getStepById(stepId, true));
	      this.pickStepsAnalytics();
	    },
	    getStepById(stepId, force) {
	      const stepIndex = this.steps.findIndex(step => stepId === step.id);
	      if (stepIndex > -1 && (this.steps[stepIndex].isEditPermitted || force)) {
	        return this.steps[stepIndex];
	      }
	      return this.steps.find((step, index) => step.isEditPermitted && index > stepIndex) || this.steps.find(step => StepIds.department === step.id);
	    },
	    close(sendEvent = false) {
	      this.$emit('close');
	      if (sendEvent) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event: 'cancel_wizard',
	          type: this.entityAnalyticsCategory,
	          c_element: this.source
	        });
	      }
	    },
	    closeWithConfirm() {
	      if (!this.isDepartmentDataChanged) {
	        this.close(true);
	        return;
	      }
	      const confirmPopup = new ui_dialogs_messagebox.MessageBox({
	        title: this.closeConfirmTitle,
	        message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_MESSAGE'),
	        yesCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_OK_CAPTION'),
	        noCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_NO_CAPTION'),
	        onYes: () => {
	          this.close(true);
	          return true;
	        },
	        minHeight: 155,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	        popupOptions: {
	          closeIcon: true,
	          className: 'humanresources-chart-wizard__close-confirm-popup'
	        }
	      });
	      confirmPopup.getYesButton().setRound(true);
	      confirmPopup.getNoButton().setRound(true);
	      confirmPopup.show();
	    },
	    onApplyData(data) {
	      var _this$departmentData$;
	      const prevEntityType = this.departmentData.entityType;
	      const {
	        isValid = true,
	        removedUsers = [],
	        moveUsersMap = {},
	        isDepartmentDataChanged = true,
	        apiEntityChanged = null,
	        ...departmentData
	      } = data;
	      this.isValidStep = isValid;
	      this.isDepartmentDataChanged = this.isDepartmentDataChanged || isDepartmentDataChanged;
	      if (apiEntityChanged) {
	        this.apiEntityChanged.add(apiEntityChanged);
	      }
	      if (departmentData) {
	        this.departmentData = {
	          ...this.departmentData,
	          ...departmentData
	        };
	      }
	      this.removedUsers = removedUsers;
	      this.moveUsersMap = moveUsersMap;
	      if (isValid) {
	        this.shouldErrorHighlight = false;
	      }

	      // change steps and roles according to entityType
	      if (prevEntityType !== this.departmentData.entityType) {
	        this.getMemberRoles(this.departmentData.entityType);
	        this.createSteps(this.departmentData.entityType, this.departmentData.id);
	        this.createDefaultSaveMode(this.departmentData.entityType);
	        const prevMemberRoles = humanresources_companyStructure_api.getMemberRoles(prevEntityType);
	        const rolesKeys = Object.keys(prevMemberRoles);
	        this.departmentData.heads = this.departmentData.heads.map(item => {
	          const roleKey = rolesKeys.find(key => prevMemberRoles[key] === item.role);
	          return {
	            ...item,
	            role: this.memberRoles[roleKey]
	          };
	        });
	        this.departmentData.employees = this.departmentData.employees.map(item => ({
	          ...item,
	          role: this.memberRoles.employee
	        }));
	      }

	      // change NodeColorsSettingsDict according to entityType
	      this.departmentData.teamColor = this.isTeamEntity ? (_this$departmentData$ = this.departmentData.teamColor) != null ? _this$departmentData$ : humanresources_companyStructure_utils.NodeColorsSettingsDict.blue : null;
	    },
	    getAllSteps(entityType, departmentId = null) {
	      const isTeamEntity = entityType === humanresources_companyStructure_utils.EntityTypes.team;
	      return {
	        entity: {
	          id: StepIds.entities,
	          title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SELECT_ENTITY_TITLE'),
	          hasBreadcrumbs: false,
	          hasTreePreview: false,
	          dataTestIdPart: 'entities'
	        },
	        department: {
	          id: StepIds.department,
	          breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_BREADCRUMBS',
	          breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_TITLE_BREADCRUMBS',
	          hasBreadcrumbs: true,
	          hasTreePreview: true,
	          isEditPermitted: isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamEdit, departmentId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentEdit, departmentId),
	          dataTestIdPart: 'department'
	        },
	        employees: {
	          id: StepIds.employees,
	          breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEES_BREADCRUMBS',
	          breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_EMPLOYEES_BREADCRUMBS',
	          hasBreadcrumbs: true,
	          hasTreePreview: true,
	          isEditPermitted: isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember, departmentId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment, departmentId),
	          dataTestIdPart: 'employees'
	        },
	        bindChat: {
	          id: StepIds.bindChat,
	          breadcrumbsTitleDepartment: this.permissionChecker.isCollabsAvailable ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE_BREADCRUMBS_W_COLLABS' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE_BREADCRUMBS',
	          breadcrumbsTitleTeam: this.permissionChecker.isCollabsAvailable ? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_TITLE_BREADCRUMBS_W_COLLABS' : 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_TITLE_BREADCRUMBS',
	          hasBreadcrumbs: true,
	          hasTreePreview: false,
	          isEditPermitted: this.checkCommunicationEditPermission(departmentId, isTeamEntity),
	          dataTestIdPart: 'bind-chat'
	        },
	        settings: {
	          id: StepIds.settings,
	          breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BREADCRUMBS',
	          breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BREADCRUMBS',
	          hasBreadcrumbs: true,
	          hasTreePreview: false,
	          isEditPermitted: isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamSettingsEdit, departmentId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentSettingsEdit, departmentId),
	          dataTestIdPart: 'team-rights'
	        }
	      };
	    },
	    createSteps(entityType = 'DEPARTMENT', departmentId = null) {
	      const {
	        entity,
	        department,
	        employees,
	        bindChat,
	        settings
	      } = this.getAllSteps(entityType, departmentId);
	      const steps = this.showEntitySelector ? [entity] : [];
	      steps.push(department, employees, bindChat);
	      if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	        steps.push(settings);
	        steps.forEach(step => {
	          Object.assign(step, {
	            breadcrumbsTitle: step.breadcrumbsTitleTeam ? this.loc(step.breadcrumbsTitleTeam) : ''
	          });
	        });
	      } else {
	        if (this.isDepartmentSettingsAvailable) {
	          steps.push(settings);
	        }
	        steps.forEach(step => {
	          Object.assign(step, {
	            breadcrumbsTitle: step.breadcrumbsTitleDepartment ? this.loc(step.breadcrumbsTitleDepartment) : ''
	          });
	        });
	      }
	      if (this.isEditMode) {
	        steps.forEach(step => {
	          Object.assign(step, {
	            isPermitted: step.isEditPermitted,
	            hasTreePreview: step.hasTreePreview && step.isEditPermitted
	          });
	        });
	      } else {
	        steps.forEach(step => {
	          Object.assign(step, {
	            isPermitted: true
	          });
	        });
	      }
	      this.steps = steps;
	    },
	    getUsersPromise(departmentId) {
	      const ids = this.calculateEmployeeIds();
	      const {
	        headsIds,
	        deputiesIds,
	        employeesIds
	      } = ids;
	      const departmentUserIds = {
	        [this.memberRoles.head]: headsIds,
	        [this.memberRoles.deputyHead]: deputiesIds,
	        [this.memberRoles.employee]: employeesIds
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
	        if (user.role === this.memberRoles.head) {
	          headsIds.push(user.id);
	        } else if (user.role === this.memberRoles.deputyHead) {
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
	      var _this$departmentData$2;
	      if (this.isEditMode) {
	        return WizardAPI.saveUsers(departmentId, ids);
	      }
	      const hasUsers = Object.values(ids).some(userIds => userIds.length > 0);
	      if (!hasUsers) {
	        return Promise.resolve();
	      }
	      const parentId = (_this$departmentData$2 = this.departmentData.parentId) != null ? _this$departmentData$2 : null;
	      if (this.saveMode === SaveMode$1.moveUsers) {
	        return WizardAPI.moveUsers(departmentId, ids, parentId);
	      }
	      return WizardAPI.saveUsers(departmentId, ids, parentId);
	    },
	    async create() {
	      const {
	        name,
	        parentId,
	        description,
	        chats,
	        channels,
	        collabs,
	        createDefaultChat,
	        createDefaultChannel,
	        createDefaultCollab,
	        entityType,
	        teamColor,
	        settings
	      } = this.departmentData;
	      let departmentId = 0;
	      let accessCode = '';
	      this.waiting = true;
	      try {
	        const userIds = this.calculateEmployeeIds();
	        const {
	          headsIds,
	          deputiesIds,
	          employeesIds
	        } = userIds;
	        const departmentUserIds = {
	          [this.memberRoles.head]: headsIds,
	          [this.memberRoles.deputyHead]: deputiesIds,
	          [this.memberRoles.employee]: employeesIds
	        };
	        const nodeSettings = {
	          [humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]: {
	            values: [...settings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]],
	            replace: true
	          }
	        };
	        let newDepartment = {};
	        let updatedDepartmentIds = false;
	        let userMovedToRootIds = false;
	        if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	          ({
	            node: newDepartment,
	            updatedDepartmentIds = false,
	            userMovedToRootIds = false
	          } = await WizardAPI.createTeam(name, parentId, description, teamColor.name, departmentUserIds, Number(createDefaultChat), chats, Number(createDefaultChannel), channels, Number(createDefaultCollab), collabs, nodeSettings));
	        } else if (entityType === humanresources_companyStructure_utils.EntityTypes.department) {
	          ({
	            node: newDepartment,
	            updatedDepartmentIds = false,
	            userMovedToRootIds = false
	          } = await WizardAPI.createDepartment(name, parentId, description, departmentUserIds, this.saveMode === SaveMode$1.moveUsers ? 1 : 0, Number(createDefaultChat), chats, Number(createDefaultChannel), channels, Number(createDefaultCollab), collabs, nodeSettings));
	        }
	        departmentId = newDepartment.id;
	        accessCode = newDepartment.accessCode;
	        if (updatedDepartmentIds) {
	          chartWizardActions.refreshDepartments(updatedDepartmentIds);
	        } else if (userMovedToRootIds) {
	          chartWizardActions.tryToAddCurrentDepartment(this.departmentData, departmentId);
	        }
	      } catch (error) {
	        this.reportError(error);
	        return;
	      } finally {
	        this.waiting = false;
	      }
	      await chartWizardActions.createDepartment({
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
	        event: `create_${this.entityAnalyticsCategory}`,
	        c_element: this.source,
	        ...(!this.isTeamEntity && {
	          p1: `deptChat_${createDefaultChat ? 'Y' : 'N'}`,
	          p2: `headAmount_${headsIds.length}`,
	          p3: `secondHeadAmount_${deputiesIds.length}`,
	          p4: `employeeAmount_${employeesIds.length}`,
	          p5: `deptChannel_${createDefaultChannel ? 'Y' : 'N'}`
	        })
	      });
	      this.close();
	    },
	    async save() {
	      var _moveUsersResult$resu;
	      if (!this.isValidStep) {
	        this.shouldErrorHighlight = true;
	        return;
	      }
	      const {
	        id,
	        parentId,
	        name,
	        description,
	        teamColor,
	        settings,
	        chats,
	        createDefaultChat,
	        channels,
	        createDefaultChannel,
	        collabs,
	        createDefaultCollab
	      } = this.departmentData;
	      const currentNode = this.departments.get(id);
	      const targetNodeId = (currentNode == null ? void 0 : currentNode.parentId) === parentId ? null : parentId;
	      this.waiting = true;
	      const moveUsersResult = await this.moveUsers();
	      let allUpdatedDepartmentIds = [];
	      // Process move users results to update UserService
	      if (((_moveUsersResult$resu = moveUsersResult.results) == null ? void 0 : _moveUsersResult$resu.length) > 0) {
	        for (const result of moveUsersResult.results) {
	          var _result$response, _result$users;
	          // Collect all updatedDepartmentIds
	          if ((_result$response = result.response) != null && _result$response.updatedDepartmentIds && Array.isArray(result.response.updatedDepartmentIds)) {
	            allUpdatedDepartmentIds = [...allUpdatedDepartmentIds, ...result.response.updatedDepartmentIds];
	          }
	          if (((_result$users = result.users) == null ? void 0 : _result$users.length) > 0) {
	            var _result$response2;
	            // Call UserService.moveUsersToEntity for this batch
	            humanresources_companyStructure_chartStore.UserService.moveUsersToEntity(Number(result.departmentId), result.users, (_result$response2 = result.response) == null ? void 0 : _result$response2.userCount, []);
	          }
	        }
	        allUpdatedDepartmentIds = [...new Set(allUpdatedDepartmentIds)].filter(nodeId => nodeId !== id);
	      }
	      const usersPromise = this.apiEntityChanged.has(humanresources_companyStructure_utils.WizardApiEntityChangedDict.employees) ? this.getUsersPromise(id) : Promise.resolve();
	      const departmentPromise = this.apiEntityChanged.has(humanresources_companyStructure_utils.WizardApiEntityChangedDict.department) ? WizardAPI.updateDepartment(id, targetNodeId, name, description, teamColor == null ? void 0 : teamColor.name) : Promise.resolve();
	      const settingsPromise = this.apiEntityChanged.has(humanresources_companyStructure_utils.WizardApiEntityChangedDict.settings) ? WizardAPI.updateSettings(id, {
	        [humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]: {
	          values: [...settings[humanresources_companyStructure_utils.NodeSettingsTypes.businessProcAuthority]],
	          replace: true
	        }
	      }, parentId) : Promise.resolve();
	      this.pickEditAnalytics(id, parentId);
	      let usersResponse = null;
	      try {
	        [usersResponse] = await Promise.all([usersPromise, departmentPromise, settingsPromise]);
	      } catch (e) {
	        this.reportError(e);
	        this.waiting = false;
	        return;
	      }
	      if (this.apiEntityChanged.has(humanresources_companyStructure_utils.WizardApiEntityChangedDict.bindChats)) {
	        const chatsToAdd = chats.filter(chatId => !this.initChats.some(chat => chat.id === chatId));
	        const chatsToRemove = this.initChats.filter(chat => !chats.includes(chat.id)).map(chat => chat.id);
	        if (chatsToAdd.length > 0 || chatsToRemove.length > 0 || Number(createDefaultChat) !== 0) {
	          try {
	            await WizardAPI.saveChats(id, chatsToAdd, Number(createDefaultChat), chatsToRemove);
	          } catch (e) {
	            this.reportError(e);
	          }
	        }
	        const channelsToAdd = channels.filter(channelId => !this.initChannels.some(channel => channel.id === channelId));
	        const channelsToRemove = this.initChannels.filter(channel => !channels.includes(channel.id)).map(channel => channel.id);
	        if (channelsToAdd.length > 0 || channelsToRemove.length > 0 || Number(createDefaultChannel) !== 0) {
	          try {
	            await WizardAPI.saveChannels(id, channelsToAdd, Number(createDefaultChannel), channelsToRemove);
	          } catch (e) {
	            this.reportError(e);
	          }
	        }
	        const collabsToAdd = collabs.filter(collabId => !this.initCollabs.some(collab => Number(collab.id) === collabId));
	        const collabsToRemove = this.initCollabs.filter(collab => !collabs.includes(Number(collab.id))).map(collab => collab.id);
	        if (collabsToAdd.length > 0 || collabsToRemove.length > 0 || Number(createDefaultCollab) !== 0) {
	          try {
	            await WizardAPI.saveCollabs(id, collabsToAdd, Number(createDefaultCollab), collabsToRemove);
	          } catch (e) {
	            this.reportError(e);
	          }
	        }
	        this.departmentData.chatsDetailed = null;
	        this.departmentData.channelsDetailed = null;
	        this.departmentData.collabssDetailed = null;
	        if (this.isEditMode && id && humanresources_companyStructure_departmentContent.DepartmentContentActions != null && humanresources_companyStructure_departmentContent.DepartmentContentActions.updateChatsInChildrenNodes) {
	          humanresources_companyStructure_departmentContent.DepartmentContentActions.updateChatsInChildrenNodes(this.nodeId);
	        }
	      }
	      let userMovedToRootIds = [];
	      if (this.removedUsers.length > 0) {
	        var _usersResponse$userMo, _usersResponse;
	        userMovedToRootIds = (_usersResponse$userMo = (_usersResponse = usersResponse) == null ? void 0 : _usersResponse.userMovedToRootIds) != null ? _usersResponse$userMo : [];
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
	      store.updateDepartment(this.departmentData);
	      store.refreshDepartments(allUpdatedDepartmentIds);
	      this.waiting = false;
	      this.$emit('modifyTree', {
	        id,
	        parentId
	      });
	      this.close();
	    },
	    reportError(error, delay = 4000) {
	      if (!humanresources_companyStructure_api.reportedErrorTypes.has(error.code)) {
	        ui_notification.UI.Notification.Center.notify({
	          content: error.message,
	          autoHideDelay: delay
	        });
	      }
	    },
	    handleSaveModeChanged(actionId) {
	      this.saveMode = actionId;
	    },
	    createDefaultSaveMode(entityType = 'DEPARTMENT') {
	      if (entityType === humanresources_companyStructure_utils.EntityTypes.team) {
	        this.saveMode = SaveMode$1.addUsers;
	      } else {
	        this.saveMode = SaveMode$1.moveUsers;
	      }
	    },
	    pickEditAnalytics(departmentId, parentId) {
	      const currentNode = this.departments.get(departmentId);
	      switch (this.entity) {
	        case StepIds.department:
	          ui_analytics.sendData({
	            tool: 'structure',
	            category: 'structure',
	            event: 'edit_dept_name',
	            c_element: this.source,
	            p1: (currentNode == null ? void 0 : currentNode.parentId) === parentId ? 'editHead_N' : 'editHeadDept_Y',
	            p2: (currentNode == null ? void 0 : currentNode.name) === name ? 'editName_N' : 'editName_Y'
	          });
	          break;
	        case StepIds.employees:
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
	    pickStepsAnalytics() {
	      let event = null;
	      switch (this.currentStep.id) {
	        case StepIds.department:
	          event = `create_${this.entityAnalyticsCategory}_step1`;
	          break;
	        case StepIds.employees:
	          event = `create_${this.entityAnalyticsCategory}_step2`;
	          break;
	        case StepIds.bindChat:
	          event = `create_${this.entityAnalyticsCategory}_step3`;
	          break;
	        case StepIds.settings:
	          event = `create_${this.entityAnalyticsCategory}_step4`;
	          break;
	        default:
	          break;
	      }
	      if (event) {
	        ui_analytics.sendData({
	          tool: 'structure',
	          category: 'structure',
	          event,
	          type: this.entityAnalyticsCategory,
	          c_element: this.source
	        });
	      }
	    },
	    checkCommunicationEditPermission(entityId, isTeamEntity) {
	      return isTeamEntity ? this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChatEdit, entityId) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamChannelEdit, entityId) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.teamCollabEdit, entityId) : this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChatEdit, entityId) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentChannelEdit, entityId) || this.permissionChecker.hasPermission(humanresources_companyStructure_permissionChecker.PermissionActions.departmentCollabEdit, entityId);
	    },
	    async moveUsers() {
	      if (!this.isEditMode || !this.moveUsersMap || Object.keys(this.moveUsersMap).length === 0) {
	        return {
	          success: true,
	          results: []
	        };
	      }
	      const results = [];
	      const errors = [];

	      // Execute promises for each department
	      for (const [departmentId, users] of Object.entries(this.moveUsersMap)) {
	        const userIds = users.map(user => user.id);
	        try {
	          // Create the department user IDs structure expected by the API
	          const departmentUserIds = {
	            [this.memberRoles.employee]: userIds,
	            [this.memberRoles.head]: [],
	            [this.memberRoles.deputyHead]: []
	          };

	          // Call the API to move users to this department and await the result
	          // eslint-disable-next-line no-await-in-loop
	          const response = await WizardAPI.moveUsers(Number(departmentId), departmentUserIds);
	          results.push({
	            departmentId: Number(departmentId),
	            users,
	            response
	          });
	        } catch (error) {
	          errors.push({
	            departmentId: Number(departmentId),
	            users,
	            error: error.message || 'Unknown error moving users'
	          });
	          this.reportError(error);
	        }
	      }
	      return {
	        success: errors.length === 0,
	        results,
	        errors: errors.length > 0 ? errors : null
	      };
	    }
	  },
	  template: `
		<div class="chart-wizard">
			<div class="chart-wizard__dialog" :class="{ '--first-step': isFirstStep }">
				<div v-if="currentStep.hasBreadcrumbs" class="chart-wizard__breadcrumbs-head">
					<div class="chart-wizard__head_close --breadcrumbs" @click="closeWithConfirm(true)"></div>
					<div class="chart-wizard__breadcrumbs-head_descr">
						{{ breadcrumbsTitle }}
					</div>
					<div class="chart-wizard__breadcrumbs-head_breadcrumbs">
						<span 
							v-for="(step, index) in breadcrumbsSteps" 
							:key="index"
							class="chart-wizard__breadcrumbs-head_breadcrumbs-item"
						>
							<span v-if="index > 0" class="ui-icon-set --chevron-right"></span>
							<span class="chart-wizard__breadcrumbs-head_breadcrumbs-item-text"
								  :class="{ '--active': step.id === currentStep.id }"
								  :data-test-id="'hr-company-structure_chart-wizard__breadcrumbs_' + step.dataTestIdPart"
								  @click="moveToStep(step.id)"
							>
								{{ step.breadcrumbsTitle }}
							</span>
						</span>
					</div>
				</div>
				<div v-else class="chart-wizard__head">
					<div class="chart-wizard__head_close" @click="close(true)"></div>
					<p class="chart-wizard__head_title">{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE') }}</p>
					<p class="chart-wizard__head_descr" :class="{ '--first-step': isFirstStep }">
						{{ currentStep.title }}
					</p>
				</div>
				<div class="chart-wizard__content" :style="{ display: !isEditMode && isFirstStep ? 'block' : 'flex' }">
					<KeepAlive>
						<component
							:is="componentInfo.name"
							v-bind="componentInfo.params"
							v-on="{
								applyData: onApplyData,
								saveModeChanged: componentInfo.name === 'Employees' ? handleSaveModeChanged : undefined
							}"
						>
						</component>
					</KeepAlive>
					<div v-if="currentStep.hasTreePreview" class="chart-wizard__tree_container">
						<TreePreview
							:parentId="departmentData.parentId"
							:name="departmentData.name"
							:heads="departmentData.heads"
							:userCount="departmentData.userCount"
							:entityType="departmentData.entityType"
							:teamColor="departmentData.teamColor"
						/>
					</div>
				</div>
				<div class="chart-wizard__footer">
					<button
						v-if="stepIndex > 0"
						class="ui-btn ui-btn-light chart-wizard__button --back"
						@click="move('back')"
						data-test-id="hr-company-structure_chart-wizard__back-button"
					>
						<span class="chart-wizard__back-button-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BACK_BTN') }}
						</span>
					</button>
					<button
						v-show="stepIndex < steps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button --next"
						:class="{ 
							'ui-btn-disabled': !isValidStep,
							'ui-btn-light-border': isEditMode,
						 }"
						@click="move()"
						data-test-id="hr-company-structure_chart-wizard__next-button"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_NEXT_BTN') }}
					</button>
					<button
						v-show="!isEditMode && stepIndex === steps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button"
						:class="{ 'ui-btn-wait': waiting }"
						@click="create"
						data-test-id="hr-company-structure_chart-wizard__create-button"
					>
						{{ createButtonText }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button --save"
						:class="{ 'ui-btn-wait': waiting, 'ui-btn-disabled': !isValidStep, }"
						@click="save"
						data-test-id="hr-company-structure_chart-wizard__save-button"
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

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX,BX.UI.Dialogs,BX.Humanresources.CompanyStructure,BX,BX.Cache,BX,BX.Vue3.Pinia,BX.UI.IconSet,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX,BX.UI.EntitySelector,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.UI.Analytics,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=chart-wizard.bundle.js.map

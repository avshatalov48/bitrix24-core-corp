/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_popup,ui_iconSet_actions,ui_hint,main_core,ui_entitySelector,ui_notification,ui_vue3_pinia,ui_iconSet_api_core,ui_iconSet_api_vue,humanresources_companyStructure_chartStore,humanresources_companyStructure_permissionChecker,humanresources_companyStructure_utils,humanresources_companyStructure_api) {
	'use strict';

	const POPUP_CONTAINER_PREFIX = '#popup-window-content-';
	const BasePopup = {
	  name: 'BasePopup',
	  emits: ['close'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    config: {
	      type: Object,
	      required: false,
	      default: {}
	    }
	  },
	  computed: {
	    popupContainer() {
	      return `${POPUP_CONTAINER_PREFIX}${this.id}`;
	    }
	  },
	  created() {
	    this.instance = this.getPopupInstance();
	    this.instance.show();
	  },
	  mounted() {
	    this.instance.adjustPosition({
	      forceBindPosition: true,
	      position: this.getPopupConfig().bindOptions.position
	    });
	  },
	  beforeUnmount() {
	    if (!this.instance) {
	      return;
	    }
	    this.closePopup();
	  },
	  methods: {
	    getPopupInstance() {
	      if (!this.instance) {
	        var _PopupManager$getPopu;
	        (_PopupManager$getPopu = main_popup.PopupManager.getPopupById(this.id)) == null ? void 0 : _PopupManager$getPopu.destroy();
	        const config = this.getPopupConfig();
	        this.instance = new main_popup.Popup(config);
	        if (this.config.angleOffset) {
	          this.instance.setAngle({
	            offset: this.config.angleOffset
	          });
	        }
	      }
	      return this.instance;
	    },
	    getDefaultConfig() {
	      return {
	        id: this.id,
	        className: 'hr-structure-components-base-popup',
	        autoHide: true,
	        animation: 'fading-slide',
	        bindOptions: {
	          position: 'bottom'
	        },
	        cacheable: false,
	        events: {
	          onPopupClose: () => this.closePopup(),
	          onPopupShow: async () => {
	            const container = this.instance.getPopupContainer();
	            await Promise.resolve();
	            const {
	              top
	            } = container.getBoundingClientRect();
	            const offset = top + container.offsetHeight - document.body.offsetHeight;
	            if (offset > 0) {
	              const margin = 5;
	              this.instance.setMaxHeight(container.offsetHeight - offset - margin);
	            }
	          }
	        }
	      };
	    },
	    getPopupConfig() {
	      var _this$config$offsetTo, _this$config$bindOpti;
	      const defaultConfig = this.getDefaultConfig();
	      const modifiedOptions = {};
	      const defaultClassName = defaultConfig.className;
	      if (this.config.className) {
	        modifiedOptions.className = `${defaultClassName} ${this.config.className}`;
	      }
	      const offsetTop = (_this$config$offsetTo = this.config.offsetTop) != null ? _this$config$offsetTo : defaultConfig.offsetTop;
	      if (((_this$config$bindOpti = this.config.bindOptions) == null ? void 0 : _this$config$bindOpti.position) === 'top' && main_core.Type.isNumber(this.config.offsetTop)) {
	        modifiedOptions.offsetTop = offsetTop - 10;
	      }
	      return {
	        ...defaultConfig,
	        ...this.config,
	        ...modifiedOptions
	      };
	    },
	    closePopup() {
	      this.$emit('close');
	      this.instance.destroy();
	      this.instance = null;
	    },
	    enableAutoHide() {
	      this.getPopupInstance().setAutoHide(true);
	    },
	    disableAutoHide() {
	      this.getPopupInstance().setAutoHide(false);
	    },
	    adjustPosition() {
	      this.getPopupInstance().adjustPosition({
	        forceBindPosition: true,
	        position: this.getPopupConfig().bindOptions.position
	      });
	    }
	  },
	  template: `
		<Teleport :to="popupContainer">
			<slot
				:adjustPosition="adjustPosition"
				:enableAutoHide="enableAutoHide"
				:disableAutoHide="disableAutoHide"
				:closePopup="closePopup"
			></slot>
		</Teleport>
	`
	};

	const BaseActionMenuPropsMixin = {
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    items: {
	      type: Array,
	      required: true,
	      default: []
	    },
	    titleBar: {
	      type: String,
	      required: false
	    },
	    containerDataTestId: {
	      type: String,
	      required: false
	    }
	  }
	};
	const BaseActionMenu = {
	  name: 'BaseActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  props: {
	    width: {
	      type: Number,
	      required: false,
	      default: 260
	    },
	    delimiter: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    angleOffset: {
	      type: Number,
	      required: false,
	      default: 0
	    },
	    titleBar: {
	      type: String,
	      required: false
	    },
	    className: {
	      type: String,
	      required: false
	    }
	  },
	  emits: ['action', 'close'],
	  components: {
	    BasePopup
	  },
	  computed: {
	    popupConfig() {
	      const options = {
	        width: this.width,
	        bindElement: this.bindElement,
	        borderRadius: 12,
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 0,
	        offsetTop: 4
	      };
	      if (this.angleOffset >= 0) {
	        options.angleOffset = this.angleOffset;
	      }
	      if (this.titleBar) {
	        options.titleBar = this.titleBar;
	      }
	      if (this.className) {
	        options.className = this.className;
	      }
	      return options;
	    }
	  },
	  methods: {
	    onItemClick(event, item, closePopup) {
	      var _item$disabled;
	      event.stopPropagation();
	      if ((_item$disabled = item.disabled) != null ? _item$disabled : false) {
	        return;
	      }
	      this.$emit('action', item.id);
	      closePopup();
	    },
	    close() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<BasePopup
			:config="popupConfig"
			v-slot="{closePopup}"
			:id="id"
			@close="close"
		>
			<div
				class="hr-structure-components-action-menu-container"
				:data-test-id="containerDataTestId"
			>
			<template v-for="(item, index) in items">
				<div
					class="hr-structure-components-action-menu-item-wrapper"
					:class="{ '--disabled': item.disabled ?? false }"
					@click="onItemClick($event, item, closePopup)"
				>
					<slot :item="item"></slot>
				</div>
				<span v-if="delimiter && index < items.length - 1"
					class="hr-structure-action-popup-menu-item-delimiter"
				></span>
			</template>
			</div>
		</BasePopup>
	`
	};

	const RouteActionMenuItem = {
	  name: 'RouteActionMenuItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    imageClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    itemClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    bIcon: {
	      type: Object,
	      required: false,
	      default: null
	    },
	    dataTestId: {
	      type: String,
	      required: false
	    }
	  },
	  methods: {
	    getColor(bIcon) {
	      var _bIcon$color;
	      if (bIcon.colorTokenName) {
	        return humanresources_companyStructure_utils.getColorCode(bIcon.colorTokenName);
	      }
	      return (_bIcon$color = bIcon.color) != null ? _bIcon$color : 'black';
	    }
	  },
	  template: `
		<div
			class="hr-structure-route-action-popup-menu-item"
			:class="itemClass"
			:data-test-id="dataTestId"
		>
			<div class="hr-structure-route-action-popup-menu-item__content">
				<BIcon
					v-if="bIcon"
					:name="bIcon.name"
					:size="bIcon.size || 20"
					:color="getColor(bIcon)"
				/>
				<div
					v-if="!bIcon && imageClass"
					class="hr-structure-route-action-popup-menu-item__content-icon-container"

				>
					<div
						class="hr-structure-route-action-popup-menu-item__content-icon"
						:class="imageClass"
					/>
				</div>
				<div class="hr-structure-route-action-popup-menu-item__content-text-container">
					<div
						class="hr-structure-route-action-popup-menu-item__content-title"
					>
						{{ this.title }}
					</div>
					<div class="hr-structure-route-action-popup-menu-item__content-description">{{ this.description }}</div>
				</div>
			</div>
		</div>
	`
	};

	const RouteActionMenu = {
	  name: 'RouteActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  components: {
	    BaseActionMenu,
	    RouteActionMenuItem
	  },
	  template: `
		<BaseActionMenu
			:id="id"
			:items="items"
			:bindElement="bindElement"
			:containerDataTestId="containerDataTestId"
			:width="260"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<RouteActionMenuItem
				:id="item.id"
				:title="item.title"
				:description="item.description"
				:itemClass="item.itemClass"
				:imageClass="item.imageClass"
				:dataTestId="item.dataTestId"
				:bIcon="item.bIcon"
			/>
		</BaseActionMenu>
	`
	};

	const SupportedColors = new Set(['red']);
	const ActionMenuItem = {
	  name: 'ActionMenuItem',
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    imageClass: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    dataTestId: {
	      type: String,
	      required: false
	    }
	  },
	  computed: {
	    colorClass() {
	      if (SupportedColors.has(this.color)) {
	        return `--${this.color}`;
	      }
	      return '';
	    }
	  },
	  template: `
		<div
			class="hr-structure-action-popup-menu-item"
			:data-test-id="dataTestId"
		>
			<div class="hr-structure-action-popup-menu-item__content">
				<div
					class="hr-structure-action-popup-menu-item__content-title"
					:class="[imageClass, colorClass]"
				>
					{{ title }}
				</div>
			</div>
		</div>
	`
	};

	const ActionMenu = {
	  name: 'ActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  components: {
	    BaseActionMenu,
	    ActionMenuItem
	  },
	  template: `
		<BaseActionMenu
			:id="id"
			:items="items"
			:bindElement="bindElement"
			:containerDataTestId="containerDataTestId"
			:width="260"
			:delimiter="false"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<ActionMenuItem
				:id="item.id"
				:title="item.title"
				:imageClass="item.imageClass"
				:color="item.color"
				:dataTestId="item.dataTestId"
				@click="this.$emit('action', item.id)"
			/>
		</BaseActionMenu>
	`
	};

	const UserActionMenuItem = {
	  name: 'UserActionMenuItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    id: {
	      type: Number,
	      required: true
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    avatar: {
	      type: String,
	      required: false,
	      default: null
	    },
	    workPosition: {
	      type: String,
	      required: false,
	      default: null
	    },
	    dataTestId: {
	      type: String,
	      required: false
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div
			class="hr-structure-route-action-popup-menu-item"
			:data-test-id="dataTestId"
		>
			<div class="hr-structure-route-action-popup-menu-item__content">
				<img
					:src="!this.avatar ? '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg' : encodeURI(this.avatar)"
					class="humanresources-tree__node_avatar --head"
				 	alt=""
				/>
				<div class="hr-structure-route-action-popup-menu-item__content-text-container">
					<span
						class="humanresources-tree__node_head-name"
						:title="this.name"
					>
						{{ this.name }}
					</span>
					<span class="humanresources-tree__node_head-position">{{ this.workPosition }}</span>
				</div>
			</div>
		</div>
	`
	};

	const UserListActionMenu = {
	  name: 'UserListActionMenu',
	  mixins: [BaseActionMenuPropsMixin],
	  components: {
	    BaseActionMenu,
	    UserActionMenuItem
	  },
	  methods: {
	    openUserUrl(url) {
	      if (!url) {
	        return;
	      }
	      BX.SidePanel.Instance.open(url, {
	        cacheable: false
	      });
	    }
	  },
	  template: `
		<BaseActionMenu 
			:id="id"
			className="hr-user-list-action-menu"
			:items="items" 
			:bindElement="bindElement"
			:width="260"
			:delimiter="false"
			:titleBar="titleBar"
			:containerDataTestId="containerDataTestId"
			:angleOffset="35"
			v-slot="{item}"
			@close="this.$emit('close')"
		>
			<UserActionMenuItem
				:id="item.id" 
				:name="item.name"
				:avatar="item.avatar"
				:workPosition="item.workPosition"
				:color="item.color"
				:dataTestId="item.dataTestId"
				@click="this.openUserUrl(item.url)"
			/>
		</BaseActionMenu>
	`
	};

	const DefaultPopupLayout = {
	  name: 'DefaultPopupLayout',
	  template: `
		<div
			v-if="$slots.content"
			class="hr-default-popup-layout__content"
		>
			<slot name="content"></slot>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const ConfirmationPopup = {
	  name: 'ConfirmationPopup',
	  emits: ['close', 'action'],
	  components: {
	    BasePopup,
	    DefaultLayoutPopup: DefaultPopupLayout
	  },
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: null
	    },
	    withoutTitleBar: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    description: {
	      type: String,
	      required: false
	    },
	    onlyConfirmButtonMode: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    confirmBtnText: {
	      type: String,
	      required: false,
	      default: null
	    },
	    showActionButtonLoader: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    lockActionButton: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    cancelBtnText: {
	      type: String,
	      required: false,
	      default: null
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: false,
	      default: null
	    },
	    width: {
	      type: Number,
	      required: false,
	      default: 300
	    },
	    confirmButtonClass: {
	      type: String,
	      required: false,
	      default: 'ui-btn-primary'
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    closeAction() {
	      if (this.showActionButtonLoader) {
	        return;
	      }
	      this.$emit('close');
	    },
	    performAction() {
	      if (this.lockActionButton || this.showActionButtonLoader) {
	        return;
	      }
	      this.$emit('action');
	    },
	    getTitleBar() {
	      var _this$title;
	      const {
	        root,
	        closeButton
	      } = main_core.Tag.render(_t || (_t = _`
				<div class="hr-confirmation-popup__title-bar">
					<span class="hr-confirmation-popup__title-bar-text">
						${0}
					</span>
					<div
						class="ui-icon-set --cross-25 hr-confirmation-popup__title-bar-close-button"
						ref="closeButton"
					>
					</div>
				</div>
			`), (_this$title = this.title) != null ? _this$title : '');
	      main_core.Event.bind(closeButton, 'click', () => {
	        this.closeAction();
	      });
	      return {
	        content: root
	      };
	    }
	  },
	  computed: {
	    popupConfig() {
	      return {
	        width: this.width,
	        bindElement: this.bindElement,
	        borderRadius: 12,
	        overlay: this.bindElement === null ? {
	          opacity: 40
	        } : false,
	        contentNoPaddings: true,
	        contentPadding: 0,
	        padding: 0,
	        className: 'hr_structure_confirmation_popup',
	        autoHide: false,
	        draggable: true,
	        titleBar: this.withoutTitleBar ? null : this.getTitleBar()
	      };
	    }
	  },
	  template: `
		<BasePopup
			:id="'id'"
			:config="popupConfig"
		>
			<template v-slot="{ closePopup }">
				<DefaultLayoutPopup>
					<template v-slot:content>
						<div
							class="hr-confirmation-popup__content-container"
							:class="{ '--without-title-bar': withoutTitleBar }"
						>
							<div v-if="$slots.content">
								<slot name="content"></slot>
							</div>
							<div v-else class="hr-confirmation-popup__content-text">
								{{ description }}
							</div>
						</div>
						<div class="hr-confirmation-popup__buttons-container">
							<button
								class="ui-btn ui-btn-round"
								:class="{ 
									'ui-btn-wait': showActionButtonLoader, 
									'ui-btn-disabled': lockActionButton, 
									[confirmButtonClass]: true
								}"
								@click="performAction"
							>
								{{ confirmBtnText ?? '' }}
							</button>
							<button
								v-if="!onlyConfirmButtonMode"
								class="ui-btn ui-btn-light-border ui-btn-round"
								@click="closeAction"
							>
								{{ cancelBtnText ?? loc('HUMANRESOURCES_COMPANY_STRUCTURE_STRUCTURE_COMPONENTS_POPUP_CONFIRMATION_POPUP_CANCEL_BUTTON') }}
							</button>
						</div>
					</template>
				</DefaultLayoutPopup>
			</template>
		</BasePopup>
	`
	};

	// @vue/component
	const MoveEmployeeConfirmationPopup = {
	  name: 'MoveEmployeeConfirmationPopup',
	  components: {
	    ConfirmationPopup
	  },
	  props: {
	    title: {
	      type: String,
	      default: ''
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    confirmButtonText: {
	      type: String,
	      default: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CONFIRM_BTN')
	    },
	    targetType: {
	      type: String,
	      default: 'department'
	    },
	    sourceType: {
	      type: String,
	      default: 'department'
	    },
	    showRoleSelect: {
	      type: Boolean,
	      default: false
	    },
	    showCombineCheckbox: {
	      type: Boolean,
	      default: false
	    },
	    isCombineOnly: {
	      type: Boolean,
	      default: false
	    },
	    excludeEmployeeRole: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['confirm', 'close'],
	  data() {
	    return {
	      selectedRole: null,
	      combinePosition: false
	    };
	  },
	  computed: {
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.targetType);
	    },
	    selectedRoleLabel() {
	      switch (this.selectedRole) {
	        case this.memberRoles.head:
	          return this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_HEAD') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_HEAD');
	        case this.memberRoles.deputyHead:
	          return this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_DEPUTY') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_DEPUTY');
	        case this.memberRoles.employee:
	          return this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_MEMBER') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_EMPLOYEE');
	        default:
	          return '';
	      }
	    },
	    isCombineCheckboxEnabled() {
	      return this.isCombineOnly || this.sourceType === humanresources_companyStructure_utils.EntityTypes.team && this.isTeamTarget;
	    },
	    isTeamTarget() {
	      return this.targetType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    popupTitle() {
	      if (this.title) {
	        return this.title;
	      }
	      return this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_TITLE_TEAM') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_TITLE_DEPT');
	    },
	    popupCheckboxText() {
	      return this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CHECKBOX_TEAM') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CHECKBOX_DEPT');
	    },
	    showCombineNotice() {
	      const isDeptToTeam = this.sourceType === humanresources_companyStructure_utils.EntityTypes.department && this.isTeamTarget;
	      return this.isCombineOnly && isDeptToTeam;
	    }
	  },
	  created() {
	    if (this.showRoleSelect) {
	      this.selectedRole = this.excludeEmployeeRole ? this.memberRoles.head : this.memberRoles.employee;
	    }
	    this.combinePosition = this.isCombineCheckboxEnabled;
	  },
	  methods: {
	    loc(phrase) {
	      return main_core.Loc.getMessage(phrase);
	    },
	    handleConfirm() {
	      const payload = {
	        role: this.selectedRole,
	        roleLabel: this.selectedRoleLabel,
	        isCombineMode: this.combinePosition
	      };
	      if (this.selectedRole !== this.memberRoles.employee) {
	        payload.badgeText = this.selectedRoleLabel;
	      }
	      this.$emit('confirm', payload);
	    },
	    toggleRoleMenu() {
	      const menuId = 'dnd-confirmation-role-menu';
	      const bindElement = this.$refs.roleSelect;
	      if (main_popup.PopupManager.getPopupById(menuId)) {
	        main_popup.PopupManager.getPopupById(menuId).destroy();
	        return;
	      }
	      const menu = new main_popup.Menu({
	        id: menuId,
	        bindElement,
	        width: 334,
	        items: this.roleMenuItems(),
	        events: {
	          onPopupClose: () => {
	            menu.destroy();
	          }
	        }
	      });
	      menu.show();
	    },
	    roleMenuItems() {
	      const items = [{
	        id: this.memberRoles.head,
	        text: this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_HEAD') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_HEAD')
	      }, {
	        id: this.memberRoles.deputyHead,
	        text: this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_DEPUTY') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_DEPUTY')
	      }];
	      if (!this.excludeEmployeeRole) {
	        const employeeRoleText = this.isTeamTarget ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_MEMBER') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_EMPLOYEE');
	        items.push({
	          id: this.memberRoles.employee,
	          text: employeeRoleText
	        });
	      }
	      return items.map(item => ({
	        ...item,
	        onclick: (event, menuItem) => {
	          this.selectedRole = menuItem.id;
	          menuItem.getMenuWindow().close();
	        }
	      }));
	    }
	  },
	  template: `
		<ConfirmationPopup
			:title="popupTitle"
			:width="364"
			:confirmBtnText="confirmButtonText"
			@action="handleConfirm"
			@close="$emit('close')"
		>
			<template v-slot:content>
				<div class="hr-dnd-confirmation_block">
					<div v-html="description" class="hr-dnd-confirmation_description"></div>
					<div v-if="showRoleSelect"
						 class="ui-ctl ui-ctl-w100 ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown hr-dnd-confirmation_select"
						 @click="toggleRoleMenu"
						 ref="roleSelect"
					>
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<div class="ui-ctl-element">{{ selectedRoleLabel }}</div>
					</div>
					<div v-if="showCombineCheckbox">
						<div
							v-if="showCombineNotice"
							class="hr-dnd-confirmation_notice"
						>
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_NOTICE') }}
						</div>
						<div 
							v-else
							class="ui-ctl ui-ctl-checkbox hr-dnd-confirmation_checkbox"
						>
							<input
								type="checkbox"
								class="ui-ctl-element"
								v-model="combinePosition"
								:disabled="isCombineOnly"
								id="dnd-confirmation-combine-checkbox"
							>
							<label for="dnd-confirmation-combine-checkbox" class="ui-ctl-label-text">
								{{ popupCheckboxText }}
							</label>
						</div>
					</div>
				</div>
			</template>
		</ConfirmationPopup>
	`
	};

	const Hint = {
	  mounted(el, binding) {
	    const value = main_core.Type.isString(binding == null ? void 0 : binding.value) ? binding.value.trim() : '';
	    let hint = null;
	    const shouldShow = () => value ? true : el.scrollWidth !== el.offsetWidth;
	    const getText = () => main_core.Text.encode(value || el.textContent);
	    const onMouseEnter = () => {
	      if (!shouldShow()) {
	        return;
	      }
	      hint = main_core.Reflection.getClass('BX.UI.Hint').createInstance({
	        popupParameters: {
	          cacheable: false,
	          angle: {
	            offset: 0
	          },
	          offsetLeft: el.getBoundingClientRect().width / 2
	        }
	      });
	      hint.show(el, getText());
	    };
	    const onMouseLeave = () => {
	      var _hint;
	      (_hint = hint) == null ? void 0 : _hint.hide();
	    };
	    main_core.Event.bind(el, 'mouseenter', onMouseEnter);
	    main_core.Event.bind(el, 'mouseleave', onMouseLeave);
	  }
	};

	let _$1 = t => t,
	  _t$1;
	class BaseManagementDialogHeader extends ui_entitySelector.BaseHeader {
	  render() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1``));
	  }
	}

	let _$2 = t => t,
	  _t$2;
	class BaseManagementDialogFooter extends ui_entitySelector.BaseFooter {
	  render() {
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2``));
	  }
	}

	// eslint-disable-next-line no-unused-vars
	const ManagementDialog = {
	  name: 'ManagementDialog',
	  emits: ['managementDialogAction', 'close'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: false
	    },
	    description: {
	      type: String,
	      required: false
	    },
	    entities: {
	      type: Array,
	      required: true
	    },
	    isActive: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    hiddenItemsIds: {
	      type: Array,
	      required: false,
	      default: []
	    },
	    confirmButtonText: {
	      type: String,
	      required: false
	    },
	    /** @var { ManagementDialogDataTestIds } dataTestIds */
	    dataTestIds: {
	      type: Object,
	      required: false,
	      default: {}
	    },
	    /** @var TabOptions */
	    recentTabOptions: {
	      type: Object,
	      required: false,
	      default: {}
	    }
	  },
	  data() {
	    return {
	      headerContainer: HTMLElement | null,
	      footerContainer: HTMLElement | null,
	      selectedItemsCount: 0
	    };
	  },
	  created() {
	    this.instance = this.getDialogInstance();
	    this.instance.show();
	  },
	  beforeUnmount() {
	    if (!this.instance || !this.instance.isOpen()) {
	      return;
	    }
	    this.instance.destroy();
	  },
	  methods: {
	    getDialogInstance() {
	      var _Dialog$getById;
	      if (this.instance) {
	        return this.instance;
	      }
	      (_Dialog$getById = ui_entitySelector.Dialog.getById(this.id)) == null ? void 0 : _Dialog$getById.destroy();
	      const config = this.getDialogConfig();
	      this.instance = new ui_entitySelector.Dialog(config);
	      this.headerContainer = this.instance.getHeader().getContainer();
	      this.footerContainer = this.instance.getFooter().getContainer();
	      if (this.dataTestIds.containerDataTestId) {
	        main_core.Dom.attr(this.instance.getContainer(), 'data-test-id', this.dataTestIds.containerDataTestId);
	      }
	      return this.instance;
	    },
	    getDialogConfig() {
	      return {
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
	        popupOptions: {
	          overlay: {
	            opacity: 40
	          }
	        },
	        header: BaseManagementDialogHeader,
	        footer: BaseManagementDialogFooter,
	        recentTabOptions: this.recentTabOptions,
	        entities: this.entities,
	        events: {
	          'Item:onSelect': () => {
	            this.selectedItemsCount++;
	          },
	          'Item:onDeselect': () => {
	            this.selectedItemsCount--;
	          },
	          onLoad: event => {
	            const dialog = event.getTarget();
	            this.toggleItems(dialog);
	            const tabs = dialog.getTabs();
	            for (const tab of tabs) {
	              if (tab.id === 'recents') {
	                tab.select();
	              }
	              if (!['recents', 'search'].includes(tab.id)) {
	                tab.deselect();
	                tab.setVisible(false);
	              }
	            }
	          },
	          'SearchTab:onLoad': event => {
	            const dialog = event.getTarget();
	            this.toggleItems(dialog);
	          },
	          onDestroy: () => {
	            this.instance = null;
	            this.$emit('close');
	          },
	          onHide: () => {
	            this.$emit('close');
	          }
	        }
	      };
	    },
	    onActionItemClick() {
	      var _this$instance$getSel;
	      if (this.isActive || !this.selectedItemsCount) {
	        return;
	      }
	      const selectedItems = (_this$instance$getSel = this.instance.getSelectedItems()) != null ? _this$instance$getSel : [];
	      this.$emit('managementDialogAction', selectedItems);
	    },
	    closeDialog() {
	      this.$emit('close');
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    toggleItems(dialog) {
	      if (this.hiddenItemsIds.length === 0) {
	        return;
	      }
	      const items = dialog.getItems();
	      items.forEach(item => {
	        const hidden = this.hiddenItemsIds.includes(item.id);
	        item.setHidden(hidden);
	      });
	    }
	  },
	  template: `
		<div>
			<teleport :to="headerContainer">
				<div class="hr-management-dialog__header_container">
					<div class="hr-management-dialog__header_content-container">
						<div class="hr-management-dialog__header_title">{{title}}</div>
						<div v-if="description" class="hr-management-dialog__header_subtitle">{{description}}</div>
						<slot name="extra-subtitle" />
					</div>
					<div
						class="ui-icon-set --cross-40 hr-management-dialog__header_close-button"
						@click="closeDialog"
						:data-test-id="dataTestIds.closeButtonDataTestId"
					/>
				</div>
			</teleport>
			<teleport :to="footerContainer">
				<div class="hr-management-dialog__footer_container">
					<button
						class="ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
						:class="{ 'ui-btn-wait': isActive, 'ui-btn-disabled': !selectedItemsCount, }"
						@click="onActionItemClick"
						:data-test-id="dataTestIds.confirmButtonDataTestId"
					>
						{{ confirmButtonText ?? loc('HUMANRESOURCES_STRUCTURE_COMPONENTS_MANAGEMENT_DIALOG_CONFIRM_BUTTON') }}
					</button>
					<button
						class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
						@click="closeDialog"
						:data-test-id="dataTestIds.cancelButtonDataTestId"
					>
						{{ loc('HUMANRESOURCES_STRUCTURE_COMPONENTS_MANAGEMENT_DIALOG_CANCEL_BUTTON') }}
					</button>
				</div>
			</teleport>
		</div>
	`
	};

	const getChatDialogEntity = function () {
	  return {
	    id: 'im-chat-only',
	    dynamicLoad: true,
	    dynamicSearch: true,
	    filters: [{
	      id: 'im.chatOnlyDataFilter',
	      options: {
	        includeSubtitle: true
	      }
	    }],
	    tagOptions: {
	      default: {
	        textColor: '#11A9D9',
	        bgColor: '#D3F4FF',
	        avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-chat-tag.svg'
	      }
	    },
	    itemOptions: {
	      default: {
	        avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-chat-item.svg'
	      }
	    },
	    options: {
	      searchChatTypes: ['O', 'C']
	    }
	  };
	};
	const getChannelDialogEntity = function () {
	  return {
	    id: 'im-chat-only',
	    filters: [{
	      id: 'im.chatOnlyDataFilter',
	      options: {
	        includeSubtitle: true
	      }
	    }],
	    dynamicLoad: true,
	    dynamicSearch: true,
	    tagOptions: {
	      default: {
	        textColor: '#8DBB00',
	        bgColor: '#EAF6C3',
	        avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-channel-tag.svg',
	        avatarOptions: {
	          borderRadius: '50%'
	        }
	      }
	    },
	    itemOptions: {
	      default: {
	        avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-channel-item.svg',
	        avatarOptions: {
	          borderRadius: '6px'
	        }
	      }
	    },
	    options: {
	      searchChatTypes: ['N', 'J']
	    }
	  };
	};
	const getCollabDialogEntity = function () {
	  return {
	    id: 'project',
	    dynamicLoad: true,
	    dynamicSearch: true,
	    options: {
	      type: ['collab'],
	      createProjectLink: false,
	      checkCollabInviteOption: true
	    },
	    itemOptions: {
	      collab: {
	        supertitle: null,
	        subtitle: main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_SUPERTITLE'),
	        textColor: '#535c69'
	      }
	    },
	    tagOptions: {
	      default: {
	        textColor: '#207976',
	        bgColor: '#ade7e4',
	        avatar: '/bitrix/js/socialnetwork/entity-selector/src/images/collab-project.svg'
	      }
	    }
	  };
	};
	const CommunicationsTypeDict = Object.freeze({
	  chat: 'chat',
	  channel: 'channel',
	  collab: 'collab'
	});
	const getCommunicationsRecentTabOptions = function (entityType, chatType) {
	  let title = '';
	  let subtitle = '';
	  if (chatType === CommunicationsTypeDict.chat) {
	    title = main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_STUB_TITLE');
	    subtitle = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_TEAM_STUB_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_DEPARTMENT_STUB_SUBTITLE');
	  } else if (chatType === CommunicationsTypeDict.channel) {
	    title = main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_STUB_TITLE');
	    subtitle = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_TEAM_STUB_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_DEPARTMENT_STUB_SUBTITLE');
	  } else {
	    title = main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_STUB_TITLE');
	    subtitle = entityType === humanresources_companyStructure_utils.EntityTypes.team ? main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_TEAM_STUB_SUBTITLE') : main_core.Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_DEPARTMENT_STUB_SUBTITLE');
	  }
	  return {
	    visible: false,
	    stub: true,
	    stubOptions: {
	      title,
	      subtitle
	    }
	  };
	};

	/**
	 * ui.hint with reactive content
	 */
	const ResponsiveHint = {
	  name: 'ResponsiveHint',
	  props: {
	    content: {
	      type: String,
	      required: true
	    },
	    width: {
	      type: [Number, null],
	      default: 300
	    },
	    extraClasses: {
	      type: Object
	    },
	    defaultClass: {
	      type: String,
	      default: 'ui-hint'
	    },
	    top: {
	      type: Boolean,
	      default: false
	    },
	    alignCenter: {
	      type: Boolean,
	      default: false
	    },
	    checkScrollWidth: {
	      type: Boolean,
	      default: false
	    }
	  },
	  created() {
	    this.hint = null;
	  },
	  mounted() {
	    const container = this.$refs['hint-container'];
	    const parameters = {
	      width: this.width
	    };
	    if (this.top) {
	      parameters.bindOptions = {
	        position: 'top'
	      };
	    }
	    if (this.alignCenter) {
	      parameters.offsetLeft = container.offsetWidth / 2 - this.width / 2 + 39;
	      parameters.angle = {
	        offset: this.width / 2 - 33 / 2
	      };
	    }
	    main_core.Event.bind(this.$refs['hint-container'], 'mouseenter', () => {
	      if (this.checkScrollWidth && this.$refs['hint-container'].scrollWidth === this.$refs['hint-container'].offsetWidth) {
	        return;
	      }
	      this.hint = main_core.Reflection.getClass('BX.UI.Hint').createInstance({
	        popupParameters: {
	          ...parameters
	        } // destruct parameters to recreate hint
	      });

	      this.hint.show(this.$refs['hint-container'], this.content);
	    });
	    main_core.Event.bind(this.$refs['hint-container'], 'mouseleave', () => {
	      var _this$hint;
	      (_this$hint = this.hint) == null ? void 0 : _this$hint.hide(); // hide() function also destroys popup
	    });
	  },

	  unmounted() {
	    var _this$hint2;
	    (_this$hint2 = this.hint) == null ? void 0 : _this$hint2.hide();
	  },
	  template: `
		<span :class="[defaultClass, extraClasses]" ref="hint-container">
			<slot/>
		</span>
	`
	};

	// @vue/component
	const DefaultHint = {
	  name: 'DefaultHint',
	  components: {
	    ResponsiveHint
	  },
	  props: {
	    content: {
	      type: String,
	      required: true
	    },
	    width: {
	      type: Number,
	      default: 300
	    }
	  },
	  template: `
		<ResponsiveHint :content=content>
			<span class="ui-hint-icon"/>
		</ResponsiveHint>
	`
	};

	const MoveAPI = {
	  moveUserToDepartment: (nodeId, userId, targetNodeId, role) => {
	    return humanresources_companyStructure_api.postData('humanresources.api.Structure.Node.Member.moveUser', {
	      nodeId,
	      userId,
	      targetNodeId,
	      roleXmlId: role
	    });
	  }
	};

	// @vue/component
	const MoveUserPopup = {
	  name: 'MoveUserPopup',
	  components: {
	    ConfirmationPopup,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    originalNodeId: {
	      type: Number,
	      required: true
	    },
	    user: {
	      type: Object,
	      required: true
	    },
	    entityType: {
	      type: String,
	      required: true
	    },
	    executeAction: {
	      type: Boolean,
	      default: true
	    },
	    onlyMove: {
	      type: Boolean,
	      default: true
	    }
	  },
	  emits: ['close', 'action', 'remove'],
	  data() {
	    return {
	      showMoveUserActionLoader: false,
	      hasPermission: true,
	      showUserAlreadyBelongsToDepartmentPopup: false,
	      accessDenied: false,
	      selectedParentDepartment: null
	    };
	  },
	  computed: {
	    ...ui_vue3_pinia.mapState(humanresources_companyStructure_chartStore.useChartStore, ['departments', 'focusedNode']),
	    includedNodeEntityTypesInDialog() {
	      return this.isTeamEntity ? ['team'] : ['department'];
	    },
	    getMoveUserActionPhrase() {
	      let phraseCode = '';
	      if (this.isTeamEntity) {
	        phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_REMOVE_USER_DESCRIPTION';
	      } else if (this.onlyMove) {
	        phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_DESCRIPTION_ONLY_MOVE';
	      } else {
	        return null;
	      }
	      phraseCode += this.user.gender === 'F' ? '_F' : '_M';
	      return this.getStandardPhrase(phraseCode, this.originalNodeId);
	    },
	    getMoveUserActionPhraseWarning() {
	      var _this$departments$get, _this$selectedParentD, _this$user$name;
	      if (this.isTeamEntity) {
	        return null;
	      }
	      const departmentName = main_core.Text.encode((_this$departments$get = this.departments.get((_this$selectedParentD = this.selectedParentDepartment) != null ? _this$selectedParentD : 0).name) != null ? _this$departments$get : '');
	      const userName = main_core.Text.encode((_this$user$name = this.user.name) != null ? _this$user$name : '');
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO', {
	        '#USER_NAME#': userName,
	        '#DEPARTMENT_NAME#': departmentName
	      }).replace('[link]', '').replace('[/link]', '');
	    },
	    getUserAlreadyBelongsToDepartmentPopupPhrase() {
	      let phraseCode = '';
	      if (this.isTeamEntity) {
	        phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_TEAM_DESCRIPTION';
	        phraseCode += this.user.gender === 'F' ? '_F' : '_M';
	      } else {
	        phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_DESCRIPTION';
	      }
	      return this.getStandardPhrase(phraseCode, this.selectedParentDepartment);
	    },
	    memberRoles() {
	      return humanresources_companyStructure_api.getMemberRoles(this.entityType);
	    },
	    isTeamEntity() {
	      return this.entityType === humanresources_companyStructure_utils.EntityTypes.team;
	    },
	    confirmTitle() {
	      if (this.isTeamEntity) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_POPUP_CONFIRM_TITLE');
	      }
	      if (this.onlyMove) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_TITLE_ONLY_MOVE');
	      }
	      return this.userHasOtherDepartments ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MULTIROLE_TITLE') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_TITLE');
	    },
	    confirmDescription() {
	      if (this.isTeamEntity) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_POPUP_ACTION_SELECT_TEAM_DESCRIPTION');
	      }
	      if (this.onlyMove) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_SELECT_DEPARTMENT_DESCRIPTION');
	      }
	      return this.userHasOtherDepartments ? this.getStandardPhrase('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_MULTIROLE_DESCRIPTION', this.originalNodeId) : this.getStandardPhrase('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_DESCRIPTION', this.originalNodeId);
	    },
	    isInSelectedNode() {
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      const nodeIds = store.multipleUsers[this.user.id];
	      return this.selectedParentDepartment && main_core.Type.isArray(nodeIds) && nodeIds.includes(this.selectedParentDepartment);
	    },
	    parentNodeId() {
	      const originalNode = this.departments.get(this.originalNodeId);
	      const parentId = originalNode.parentId;
	      if (!parentId) {
	        return this.originalNodeId;
	      }
	      const parentNode = this.departments.get(parentId);
	      return parentNode && parentNode.entityType === originalNode.entityType ? parentId : this.originalNodeId;
	    },
	    iconSet() {
	      return ui_iconSet_api_core.Set;
	    },
	    userHasOtherDepartments() {
	      const store = humanresources_companyStructure_chartStore.useChartStore();
	      const nodeIds = store.multipleUsers[this.user.id];
	      return main_core.Type.isArray(nodeIds) && nodeIds.length > 1;
	    },
	    confirmButtonText() {
	      if (this.onlyMove) {
	        return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MOVE_BUTTON_ONLY_MOVE');
	      }
	      return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MOVE_BUTTON');
	    },
	    lockMoveUserActionButton() {
	      return !this.hasPermission || !this.userHasOtherDepartments && !this.selectedParentDepartment || this.onlyMove && !this.selectedParentDepartment;
	    },
	    isWarningVisible() {
	      return this.isInSelectedNode && this.getMoveUserActionPhraseWarning;
	    }
	  },
	  created() {
	    this.permissionChecker = humanresources_companyStructure_permissionChecker.PermissionChecker.getInstance();
	    if (!this.permissionChecker) {
	      return;
	    }
	    this.action = this.isTeamEntity ? humanresources_companyStructure_permissionChecker.PermissionActions.teamAddMember : humanresources_companyStructure_permissionChecker.PermissionActions.employeeAddToDepartment;
	    this.selectedDepartmentId = 0;
	  },
	  mounted() {
	    const departmentContainer = this.$refs['department-selector'];
	    this.departmentSelector = this.createTagSelector();
	    this.departmentSelector.renderTo(departmentContainer);
	  },
	  methods: {
	    createTagSelector() {
	      return new ui_entitySelector.TagSelector({
	        events: {
	          onTagAdd: event => {
	            this.accessDenied = false;
	            const {
	              tag
	            } = event.data;
	            this.selectedParentDepartment = tag.id;
	            if (humanresources_companyStructure_permissionChecker.PermissionChecker.hasPermission(this.action, tag.id)) {
	              this.hasPermission = true;
	              return;
	            }
	            this.accessDenied = true;
	            this.hasPermission = false;
	          },
	          onTagRemove: () => {
	            this.selectedParentDepartment = null;
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
	              selectMode: 'departmentsOnly',
	              restricted: 'addMember',
	              includedNodeEntityTypes: this.includedNodeEntityTypesInDialog,
	              useMultipleTabs: true
	            }
	          }]
	        }
	      });
	    },
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    },
	    async confirmMoveUser() {
	      var _this$departments$get2, _this$departments$get3, _this$user$role;
	      const departmentId = this.focusedNode;
	      const userId = this.user.id;
	      const targetNodeId = this.selectedParentDepartment;
	      if (!targetNodeId) {
	        this.$emit('remove');
	        return;
	      }
	      if (!this.executeAction) {
	        this.$emit('action', targetNodeId);
	        return;
	      }
	      this.showMoveUserActionLoader = true;
	      try {
	        await MoveAPI.moveUserToDepartment(departmentId, userId, targetNodeId);
	      } catch (error) {
	        var _error$code;
	        this.showMoveUserActionLoader = false;
	        const code = (_error$code = error.code) != null ? _error$code : 0;
	        if (code === 'MEMBER_ALREADY_BELONGS_TO_NODE') {
	          this.showUserAlreadyBelongsToDepartmentPopup = true;
	        } else {
	          const phraseCode = this.isTeamEntity ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_ERROR') : this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ERROR');
	          ui_notification.UI.Notification.Center.notify({
	            content: phraseCode,
	            autoHideDelay: 2000
	          });
	          this.$emit('close');
	        }
	        return;
	      }
	      const departmentName = main_core.Text.encode((_this$departments$get2 = (_this$departments$get3 = this.departments.get(targetNodeId)) == null ? void 0 : _this$departments$get3.name) != null ? _this$departments$get2 : '');
	      const phraseCode = this.isTeamEntity ? 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_SUCCESS_MESSAGE' : 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_SUCCESS_MESSAGE';
	      ui_notification.UI.Notification.Center.notify({
	        content: this.loc(phraseCode, {
	          '#DEPARTMENT_NAME#': departmentName
	        }),
	        autoHideDelay: 2000
	      });
	      humanresources_companyStructure_chartStore.UserService.moveUserToEntity(departmentId, userId, targetNodeId, (_this$user$role = this.user.role) != null ? _this$user$role : this.memberRoles.employee);
	      this.$emit('action', targetNodeId);
	      this.showMoveUserActionLoader = false;
	    },
	    closeAction() {
	      this.$emit('close');
	    },
	    closeUserAlreadyBelongsToDepartmentPopup() {
	      this.showUserAlreadyBelongsToDepartmentPopup = false;
	      this.closeAction();
	    },
	    getStandardPhrase(phrase, departmentId) {
	      var _this$departments$get4, _this$departments$get5, _this$user$name2;
	      const departmentName = main_core.Text.encode((_this$departments$get4 = (_this$departments$get5 = this.departments.get(departmentId != null ? departmentId : 0)) == null ? void 0 : _this$departments$get5.name) != null ? _this$departments$get4 : '');
	      const userName = main_core.Text.encode((_this$user$name2 = this.user.name) != null ? _this$user$name2 : '');
	      return this.loc(phrase, {
	        '#USER_NAME#': userName,
	        '#DEPARTMENT_NAME#': departmentName
	      }).replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`).replace('[/link]', '</a>');
	    }
	  },
	  template: `
		<ConfirmationPopup
			@action="confirmMoveUser"
			@close="closeAction"
			:showActionButtonLoader="showMoveUserActionLoader"
			:lockActionButton="lockMoveUserActionButton"
			:title="confirmTitle"
			:confirmBtnText = "confirmButtonText"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div v-if="getMoveUserActionPhrase" v-html="getMoveUserActionPhrase"/>
					<span v-html="confirmDescription"/>
				</div>
				<div
					class="hr-department-detail-content__move-user-department-selector"
					ref="department-selector"
					:class="{ 'ui-ctl-warning': accessDenied }"
				/>
				<div v-if="isWarningVisible" class="hr-department-detail-content__move-user-department_item-warning">
					<BIcon
						:name="iconSet.WARNING"
						color="#FFA900"
						:size="20"
					></BIcon>
					<span v-html="getMoveUserActionPhraseWarning"/>
				</div>
				<div
					v-if="accessDenied"
					class="hr-department-detail-content__move-user-department_item-error"
				>
					<div class="ui-icon-set --warning"></div>
					<span
						class="hr-department-detail-content__move-user-department_item-error-message"
					>
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_PERMISSION_ERROR') }}
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
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_CLOSE_BUTTON')"
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

	exports.BasePopup = BasePopup;
	exports.BaseActionMenu = BaseActionMenu;
	exports.RouteActionMenu = RouteActionMenu;
	exports.ActionMenu = ActionMenu;
	exports.UserListActionMenu = UserListActionMenu;
	exports.ConfirmationPopup = ConfirmationPopup;
	exports.MoveEmployeeConfirmationPopup = MoveEmployeeConfirmationPopup;
	exports.Hint = Hint;
	exports.ManagementDialog = ManagementDialog;
	exports.getChatDialogEntity = getChatDialogEntity;
	exports.getChannelDialogEntity = getChannelDialogEntity;
	exports.getCommunicationsRecentTabOptions = getCommunicationsRecentTabOptions;
	exports.getCollabDialogEntity = getCollabDialogEntity;
	exports.CommunicationsTypeDict = CommunicationsTypeDict;
	exports.ResponsiveHint = ResponsiveHint;
	exports.DefaultHint = DefaultHint;
	exports.MoveUserPopup = MoveUserPopup;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Main,BX,BX,BX,BX.UI.EntitySelector,BX,BX.Vue3.Pinia,BX.UI.IconSet,BX.UI.IconSet,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure));
//# sourceMappingURL=structure-components.bundle.js.map

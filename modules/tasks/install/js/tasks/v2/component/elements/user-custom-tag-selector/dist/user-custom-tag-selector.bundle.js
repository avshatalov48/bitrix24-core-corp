/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,ui_entitySelector,ui_vue3_components_menu,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,ui_tooltip,tasks_v2_component_elements_userAvatar,tasks_v2_lib_entitySelectorDialog,tasks_v2_provider_service_userService,ui_loader) {
	'use strict';

	// @vue/component
	const Loader = {
	  name: 'UiUserCustomTagSelectorLoader',
	  props: {
	    options: {
	      type: Object,
	      default: null
	    }
	  },
	  mounted() {
	    this.loader = new ui_loader.Loader(this.getOptions());
	    this.loader.render();
	    this.loader.show();
	  },
	  beforeUnmount() {
	    var _this$loader;
	    (_this$loader = this.loader) == null ? void 0 : _this$loader.hide == null ? void 0 : _this$loader.hide();
	    this.loader = null;
	  },
	  methods: {
	    getOptions() {
	      return {
	        ...this.getDefaultOptions(),
	        ...this.options
	      };
	    },
	    getDefaultOptions() {
	      return {
	        target: this.$refs.loader,
	        type: 'BULLET',
	        size: 'xs'
	      };
	    }
	  },
	  template: `
		<div class="b24-loader-container" ref="loader"></div>
	`
	};

	// @vue/component
	const UserCustomTagSelector = {
	  name: 'UiUserCustomTagSelector',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UserAvatar: tasks_v2_component_elements_userAvatar.UserAvatar,
	    Loader,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  props: {
	    /** @type DialogOptions */
	    dialogOptions: {
	      type: Object,
	      required: true
	    },
	    items: {
	      type: Array,
	      default: () => []
	    },
	    /** @type UserModel */
	    userInfo: {
	      type: Object,
	      default: null
	    },
	    withActionMenu: {
	      type: Boolean,
	      default: false
	    },
	    clickHandler: {
	      type: Function,
	      default: null
	    },
	    readonly: {
	      type: Boolean,
	      default: false
	    },
	    avatarOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['freeze', 'unfreeze', 'select'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      localContext: `${this.dialogOptions.context}-${this.$options.name}`,
	      dialogId: `${this.dialogOptions.context}-${this.$options.name}-${main_core.Text.getRandom()}`,
	      /** @type Item */
	      user: null,
	      previousUser: null,
	      loading: true,
	      isMenuShown: false
	    };
	  },
	  computed: {
	    selected() {
	      return Boolean(this.user);
	    },
	    userId() {
	      var _this$user$getId, _this$user;
	      return parseInt((_this$user$getId = (_this$user = this.user) == null ? void 0 : _this$user.getId()) != null ? _this$user$getId : 0, 10);
	    },
	    userName() {
	      return this.user.getTitle();
	    },
	    userAvatar() {
	      return this.user.getAvatar();
	    },
	    userType() {
	      return this.user.getEntityType();
	    },
	    userProfileLink() {
	      return tasks_v2_provider_service_userService.userService.getUrl(this.userId);
	    },
	    testId() {
	      return `${this.localContext}-${main_core.Text.getRandom()}`;
	    },
	    menuOptions() {
	      return {
	        id: `${this.localContext}-${main_core.Text.getRandom()}-${this.userId}`,
	        bindElement: this.$refs.user,
	        offsetTop: 8,
	        items: this.menuItems,
	        targetContainer: document.body
	      };
	    },
	    menuItems() {
	      return [{
	        title: this.loc('UCTS_MENU_PROFILE'),
	        icon: ui_iconSet_api_core.Outline.PERSON,
	        dataset: {
	          id: `MenuProfile-${this.testId}`
	        },
	        onClick: () => BX.SidePanel.Instance.open(this.userProfileLink)
	      }, {
	        title: this.loc('UCTS_MENU_EDIT'),
	        icon: ui_iconSet_api_core.Outline.EDIT_L,
	        dataset: {
	          id: `MenuEdit-${this.testId}`
	        },
	        onClick: () => this.showDialog()
	      }];
	    }
	  },
	  watch: {
	    userInfo() {
	      if (this.userInfo) {
	        this.user = this.mapUserInfoToItem(this.userInfo);
	      }
	    },
	    items() {
	      var _this$dialog;
	      if ((_this$dialog = this.dialog) != null && _this$dialog.isLoaded()) {
	        this.user = this.dialog.getItemsByIds(this.items)[0];
	      }
	    }
	  },
	  created() {
	    if (this.userInfo) {
	      this.user = this.mapUserInfoToItem(this.userInfo);
	      this.$emit('select', this.mapItemToUserInfo(this.user));
	      this.loading = false;
	    } else {
	      var _this$dialog2;
	      (_this$dialog2 = this.dialog) != null ? _this$dialog2 : this.dialog = this.createDialog();
	    }
	  },
	  unmounted() {
	    var _this$dialog3;
	    (_this$dialog3 = this.dialog) == null ? void 0 : _this$dialog3.destroy();
	  },
	  methods: {
	    freeze() {
	      this.$emit('freeze');
	    },
	    unfreeze() {
	      var _this$dialog4;
	      if ((_this$dialog4 = this.dialog) != null && _this$dialog4.isOpen()) {
	        return;
	      }
	      this.$emit('unfreeze');
	    },
	    select() {
	      var _this$dialog5;
	      this.previousUser = this.user;
	      const selectedUser = (_this$dialog5 = this.dialog) == null ? void 0 : _this$dialog5.getSelectedItems()[0];
	      this.user = selectedUser || this.previousUser;
	      this.$emit('select', this.mapItemToUserInfo(this.user));
	      this.loading = false;
	    },
	    handleClick() {
	      if (this.readonly) {
	        BX.SidePanel.Instance.open(this.userProfileLink);
	        return;
	      }
	      if (this.withActionMenu) {
	        this.showMenu();
	      } else {
	        void this.showDialog();
	      }
	    },
	    async showDialog() {
	      var _this$dialog6;
	      if (this.clickHandler && (await this.clickHandler()) === false) {
	        return;
	      }
	      (_this$dialog6 = this.dialog) != null ? _this$dialog6 : this.dialog = this.createDialog();
	      this.dialog.selectItemsByIds(this.items);
	      this.dialog.showTo(this.$refs.container);
	    },
	    createDialog() {
	      return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	        id: this.dialogId,
	        preselectedItems: this.items,
	        events: {
	          'Item:onSelect': this.select,
	          'Item:onDeselect': this.select,
	          onLoad: this.select,
	          onShow: this.freeze,
	          onHide: this.unfreeze
	        },
	        ...this.dialogOptions
	      });
	    },
	    showMenu() {
	      this.isMenuShown = true;
	    },
	    mapUserInfoToItem(userInfo) {
	      var _userInfo$image, _userInfo$type;
	      return new ui_entitySelector.Item({
	        id: userInfo.id,
	        entityId: 'user',
	        title: userInfo.name,
	        avatar: (_userInfo$image = userInfo == null ? void 0 : userInfo.image) != null ? _userInfo$image : '',
	        entityType: (_userInfo$type = userInfo == null ? void 0 : userInfo.type) != null ? _userInfo$type : 'employee'
	      });
	    },
	    mapItemToUserInfo(user) {
	      return {
	        id: user.getId(),
	        name: user.getTitle(),
	        image: user.getAvatar(),
	        type: user.getEntityType()
	      };
	    }
	  },
	  template: `
		<div class="b24-user-selector" ref="container">
			<Loader v-if="loading"/>
			<template v-else>
				<div
					v-if="selected"
					class="b24-user-selector-user"
					:class="'--' + userType"
					@mouseover="showAction = true"
					@mouseleave="showAction = false"
				>
					<div
						ref="user"
						class="b24-user-selector-user-label"
						:data-id="'Label-' + testId"
						:bx-tooltip-user-id="userId"
						bx-tooltip-context="b24"
						@click="handleClick"
					>
						<div class="b24-user-selector-user-image">
							<UserAvatar :src="userAvatar" :type="userType"/>
						</div>
						<div v-if="!avatarOnly" class="b24-user-selector-user-name">
							{{ userName }}
						</div>
					</div>
				</div>
				<div
					v-else
					class="b24-user-selector-user-placeholder"
					:data-id="'Add-' + testId"
					@click="showDialog"
				>
					<UserAvatar/>
					<template v-if="!avatarOnly">
						{{ loc('UCTS_ADD_BTN') }}
					</template>
				</div>
			</template>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`
	};

	exports.UserCustomTagSelector = UserCustomTagSelector;
	exports.Loader = Loader;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX,BX.UI.EntitySelector,BX.UI.Vue3.Components,BX.UI.IconSet,BX.UI.IconSet,BX,BX.UI,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.UI));
//# sourceMappingURL=user-custom-tag-selector.bundle.js.map

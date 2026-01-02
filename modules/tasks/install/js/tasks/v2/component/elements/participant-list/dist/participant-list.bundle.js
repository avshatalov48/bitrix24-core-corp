/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,ui_entitySelector,ui_vue3_components_menu,ui_vue3_components_popup,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_component_elements_addBackground,tasks_v2_component_elements_fieldAdd,tasks_v2_lib_userSelectorDialog,tasks_v2_lib_heightTransition,tasks_v2_lib_hrefClick,tasks_v2_provider_service_userService,tasks_v2_component_elements_userAvatarList,tasks_v2_core,tasks_v2_const) {
	'use strict';

	const participantMeta = Object.freeze({
	  dialogOptions: (taskId, idSalt) => {
	    const limits = tasks_v2_core.Core.getParams().limits;
	    return {
	      id: `tasks-user-selector-dialog-${idSalt}-${taskId}`,
	      context: 'tasks-card',
	      multiple: true,
	      enableSearch: true,
	      entities: [{
	        id: tasks_v2_const.EntitySelectorEntity.User,
	        options: {
	          emailUsers: true,
	          inviteGuestLink: true,
	          analyticsSource: 'tasks',
	          lockGuestLink: !limits.mailUserIntegration,
	          lockGuestLinkFeatureId: limits.mailUserIntegrationFeatureId
	        }
	      }, {
	        id: 'department'
	      }]
	    };
	  }
	});

	const maxUsers = 4;

	// @vue/component
	const ParticipantList = {
	  name: 'TaskParticipantList',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UserAvatarListUsers: tasks_v2_component_elements_userAvatarList.UserAvatarListUsers,
	    AddBackground: tasks_v2_component_elements_addBackground.AddBackground,
	    FieldAdd: tasks_v2_component_elements_fieldAdd.FieldAdd,
	    BMenu: ui_vue3_components_menu.BMenu,
	    Popup: ui_vue3_components_popup.Popup
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    dataset: {
	      type: Object,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    },
	    users: {
	      type: Array,
	      required: true
	    }
	  },
	  emits: ['update'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  data() {
	    return {
	      menuUserId: null,
	      isDialogShown: false,
	      isPopupShown: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    withActionMenu() {
	      return main_core.Type.isNumber(this.taskId) && this.taskId > 0;
	    },
	    testId() {
	      return `${this.$options.name}-${this.context}-${this.taskId}`;
	    },
	    popupOptions() {
	      return () => ({
	        id: 'tasks-participant-list-more-popup',
	        bindElement: this.$refs.more,
	        padding: 18,
	        maxWidth: 300,
	        maxHeight: window.innerHeight / 2 - 40,
	        offsetTop: 8,
	        offsetLeft: -18,
	        targetContainer: document.body
	      });
	    },
	    menuOptions() {
	      var _this$$refs$userList$;
	      const userId = this.menuUserId;
	      return {
	        id: 'tasks-participant-list-menu-user',
	        bindElement: (_this$$refs$userList$ = this.$refs.userList.getNode(userId)) != null ? _this$$refs$userList$ : this.$refs.popupUserList.getNode(userId),
	        items: this.getMenuItems(userId),
	        offsetTop: 8,
	        targetContainer: document.body
	      };
	    },
	    moreFormatted() {
	      return this.loc('TASKS_V2_PARTICIPANT_LIST_MORE_COUNT', {
	        '#COUNT#': this.popupUsers.length
	      });
	    },
	    displayedUsers() {
	      return this.users.slice(0, maxUsers - (this.usersLength > maxUsers));
	    },
	    popupUsers() {
	      return this.users.slice(maxUsers - (this.usersLength > maxUsers));
	    },
	    usersLength() {
	      return this.users.length;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  watch: {
	    usersLength() {
	      if (this.popupUsers.length === 0) {
	        this.isPopupShown = false;
	      }
	    }
	  },
	  mounted() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$el);

	    // Load preselected items
	    this.createDialog();
	  },
	  updated() {
	    tasks_v2_lib_heightTransition.heightTransition.animate(this.$el);
	  },
	  beforeUnmount() {
	    var _this$selector;
	    (_this$selector = this.selector) == null ? void 0 : _this$selector.destroy();
	  },
	  methods: {
	    handleClickUser(userId) {
	      if (this.readonly) {
	        tasks_v2_lib_hrefClick.hrefClick(tasks_v2_provider_service_userService.userService.getUrl(userId));
	        return;
	      }
	      if (this.withActionMenu) {
	        this.showMenu(userId);
	      } else {
	        this.showDialog();
	      }
	    },
	    showMenu(userId) {
	      if (this.menuUserId !== userId) {
	        setTimeout(() => {
	          this.menuUserId = userId;
	        });
	      }
	    },
	    showDialog() {
	      var _this$selector2, _this$selector3;
	      if ((_this$selector2 = this.selector) != null && _this$selector2.getDialog().isOpen()) {
	        return;
	      }
	      (_this$selector3 = this.selector) != null ? _this$selector3 : this.selector = this.createDialog();
	      this.selector.selectItemsByIds(this.getPreselected(this.users));
	      this.selector.show(window.innerHeight < 700 ? this.$refs.anchor : this.$refs.users);
	    },
	    createDialog() {
	      const dialog = new tasks_v2_lib_userSelectorDialog.UserSelectorDialog({
	        taskId: this.taskId,
	        preselected: this.getPreselected(this.users),
	        dialogOptions: {
	          ...participantMeta.dialogOptions(this.taskId, this.context),
	          height: window.innerHeight / 2 - 80
	        }
	      });
	      dialog.getDialog().getPopup().subscribeFromOptions({
	        onShow: () => {
	          this.isDialogShown = true;
	        },
	        onClose: () => {
	          this.isDialogShown = false;
	          if (dialog.getDialog().isLoaded()) {
	            this.updateUsers(this.getSelectedUsers());
	          }
	        }
	      });
	      dialog.subscribe('selectedUsersLoaded', event => {
	        const users = event.getData();
	        this.updateUsers(users);
	      });
	      return dialog;
	    },
	    getSelectedUsers() {
	      return this.selector.getDialog().getSelectedItems().map(item => ({
	        id: item.getId(),
	        name: item.getTitle(),
	        image: item.getAvatar()
	      }));
	    },
	    getMenuItems(userId) {
	      return [{
	        title: this.loc('TASKS_V2_PARTICIPANT_LIST_USER_MENU_PROFILE'),
	        icon: ui_iconSet_api_core.Outline.PERSON,
	        dataset: {
	          id: `UserMenuProfile-${this.testId}`
	        },
	        onClick: () => tasks_v2_lib_hrefClick.hrefClick(tasks_v2_provider_service_userService.userService.getUrl(userId))
	      }, {
	        design: ui_vue3_components_menu.MenuItemDesign.Alert,
	        title: this.loc('TASKS_V2_PARTICIPANT_LIST_USER_MENU_REMOVE'),
	        icon: ui_iconSet_api_core.Outline.TRASHCAN,
	        dataset: {
	          id: `UserMenuRemove-${this.testId}`
	        },
	        onClick: () => this.removeUser(userId)
	      }];
	    },
	    removeUser(userId) {
	      var _this$selector4;
	      const isSelectorShown = (_this$selector4 = this.selector) == null ? void 0 : _this$selector4.getDialog().isOpen();
	      const users = isSelectorShown ? this.getSelectedUsers() : this.users;
	      const usersWithoutRemoved = users.filter(({
	        id
	      }) => id !== userId);
	      if (isSelectorShown) {
	        this.selector.selectItemsByIds(this.getPreselected(usersWithoutRemoved));
	      }
	      this.updateUsers(usersWithoutRemoved);
	    },
	    updateUsers(users) {
	      this.$emit('update', users);
	    },
	    getPreselected(users) {
	      return users.map(({
	        id
	      }) => [tasks_v2_const.EntitySelectorEntity.User, id]);
	    }
	  },
	  template: `
		<div v-bind="dataset" ref="users">
			<div v-if="usersLength > 0">
				<div class="tasks-field-users-container">
					<AddBackground v-if="!readonly" :isActive="isDialogShown" @click="showDialog"/>
					<div class="tasks-field-users">
						<UserAvatarListUsers
							:users="displayedUsers"
							:withCross="!withActionMenu"
							:readonly
							ref="userList"
							@onClick="showDialog"
							@onUserClick="(userId) => handleClickUser(userId)"
							@onUserCrossClick="(userId) => removeUser(userId)"
						/>
					</div>
				</div>
				<div
					v-if="popupUsers.length > 0"
					class="tasks-field-participant-list-more"
					ref="more"
					@click="isPopupShown = true"
				>
					{{ moreFormatted }}
				</div>
			</div>
			<FieldAdd v-else :icon="Outline.PERSON" @click="showDialog"/>
			<div ref="anchor"></div>
		</div>
		<BMenu v-if="menuUserId" :options="menuOptions" @close="menuUserId = null"/>
		<Popup
			v-if="isPopupShown"
			:options="popupOptions()"
			@close="isPopupShown = false"
		>
			<div class="tasks-field-users --popup">
				<UserAvatarListUsers
					:users="popupUsers"
					:withCross="!withActionMenu"
					:isDialogShown
					ref="popupUserList"
					@onClick="showDialog"
					@onUserClick="(userId) => handleClickUser(userId)"
					@onUserCrossClick="(userId) => removeUser(userId)"
				/>
			</div>
		</Popup>
	`
	};

	exports.ParticipantList = ParticipantList;
	exports.participantMeta = participantMeta;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX,BX.UI.EntitySelector,BX.UI.Vue3.Components,BX.UI.Vue3.Components,BX.UI.IconSet,BX.UI.IconSet,BX,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Component.Elements,BX.Tasks.V2,BX.Tasks.V2.Const));
//# sourceMappingURL=participant-list.bundle.js.map

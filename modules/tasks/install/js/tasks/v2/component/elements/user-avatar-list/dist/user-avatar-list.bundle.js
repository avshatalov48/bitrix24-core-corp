/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_components_popup,tasks_v2_lib_hrefClick,tasks_v2_provider_service_userService,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,ui_tooltip,tasks_v2_component_elements_hoverPill,tasks_v2_component_elements_userAvatar) {
	'use strict';

	// @vue/component
	const UserAvatarListUsers = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    UserAvatar: tasks_v2_component_elements_userAvatar.UserAvatar
	  },
	  props: {
	    users: {
	      type: Array,
	      required: true
	    },
	    withCross: {
	      type: Boolean,
	      required: true
	    },
	    readonly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['onUserClick', 'onUserCrossClick'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  methods: {
	    getNode(userId) {
	      var _this$$refs;
	      return (_this$$refs = this.$refs[`user_${userId}`]) == null ? void 0 : _this$$refs[0];
	    }
	  },
	  template: `
		<template v-for="(user, index) of users" :key="user.id">
			<div class="b24-user-avatar-list-user-container">
				<HoverPill
					class="b24-user-avatar-list-user"
					:class="'--' + user.type"
					:withClear="!readonly"
					@click.stop="$emit('onUserClick', user.id)"
					@clear="$emit('onUserCrossClick', user.id)"
				>
					<div
						class="b24-user-avatar-list-user-inner"
						:ref="'user_' + user.id"
						:bx-tooltip-user-id="user.id"
						bx-tooltip-context="b24"
					>
							<span class="b24-user-avatar-list-user-image">
								<UserAvatar :src="user.image" :type="user.type"/>
							</span>
						<span class="b24-user-avatar-list-user-title">{{ user.name }}</span>
					</div>
				</HoverPill>
			</div>
		</template>
	`
	};

	// @vue/component
	const UserAvatarList = {
	  name: 'UiUserAvatarList',
	  components: {
	    Popup: ui_vue3_components_popup.Popup,
	    UserAvatar: tasks_v2_component_elements_userAvatar.UserAvatar,
	    UserAvatarListUsers
	  },
	  props: {
	    users: {
	      type: Array,
	      required: true
	    },
	    visibleAmount: {
	      type: Number,
	      default: 3
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  computed: {
	    visibleUsers() {
	      return this.users.slice(0, this.visibleAmount);
	    },
	    popupUsers() {
	      return this.users.slice(this.visibleAmount);
	    },
	    count() {
	      return this.users.length;
	    },
	    invisibleCount() {
	      return this.count - this.visibleAmount;
	    },
	    popupOptions() {
	      return () => ({
	        id: 'ui-user-avatar-list-more-popup',
	        bindElement: this.$refs.more,
	        padding: 18,
	        maxWidth: 300,
	        maxHeight: window.innerHeight / 2 - 40,
	        offsetTop: 8,
	        offsetLeft: -18,
	        targetContainer: document.body
	      });
	    }
	  },
	  methods: {
	    showListUsers() {
	      this.isPopupShown = true;
	    },
	    handleClickFromListUsers(userId) {
	      tasks_v2_lib_hrefClick.hrefClick(tasks_v2_provider_service_userService.userService.getUrl(userId));
	    }
	  },
	  template: `
		<div
			ref="container"
			class="b24-user-avatar-list"
		>
			<UserAvatar
				v-for="user in visibleUsers"
				:key="user.id"
				:id="user.id"
				:src="user.image"
				:type="user.type"
				:bx-tooltip-user-id="user.id"
				bx-tooltip-context="b24"
				class="b24-user-avatar-list-item"
			/>
			<div
				v-if="count > visibleAmount"
				ref="more"
				class="b24-user-avatar-list-more"
				@click="showListUsers"
			>
				+{{ invisibleCount }}
			</div>
			<Popup
				v-if="isPopupShown"
				:options="popupOptions()"
				@close="isPopupShown = false"
			>
				<div class="b24-user-avatar-list-users --popup">
					<UserAvatarListUsers
						:users="popupUsers"
						:withCross="false"
						ref="popupUserList"
						@onClick="(userId) => handleClickFromListUsers(userId)"
					/>
				</div>
			</Popup>
		</div>
	`
	};

	exports.UserAvatarList = UserAvatarList;
	exports.UserAvatarListUsers = UserAvatarListUsers;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI.Vue3.Components,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.UI.IconSet,BX.UI.IconSet,BX,BX.UI,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements));
//# sourceMappingURL=user-avatar-list.bundle.js.map

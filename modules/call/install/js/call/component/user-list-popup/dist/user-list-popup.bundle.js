/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,call_component_elements) {
	'use strict';

	// @vue/component
	const UserListPopup = {
	  name: 'UserListPopup',
	  components: {
	    CallPopupContainer: call_component_elements.CallPopupContainer,
	    CallLoader: call_component_elements.CallLoader
	  },
	  emits: ['close'],
	  props: {
	    bindElement: {
	      type: Object,
	      required: true
	    },
	    usersData: {
	      type: Array,
	      required: true
	    },
	    loading: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  data() {
	    return {
	      hasError: false,
	      isLoadingUsers: false
	    };
	  },
	  computed: {
	    getId() {
	      return 'call-start-ai-call-promo-popup';
	    },
	    config() {
	      const popupWidth = 240;
	      return {
	        width: popupWidth,
	        padding: 0,
	        overlay: false,
	        autoHide: true,
	        closeByEsc: true,
	        angle: false,
	        closeIcon: false,
	        bindElement: this.bindElement,
	        offsetTop: 15,
	        offsetLeft: -((popupWidth - this.bindElement.offsetWidth) / 2)
	      };
	    },
	    isLoading() {
	      return this.loading || this.isLoadingUsers;
	    }
	  },
	  methods: {
	    backgroundStyle(user) {
	      return {
	        backgroundColor: user.color
	      };
	    }
	  },
	  template: `
		<CallPopupContainer
			:config="config"
			:id="getId"
			@close="$emit('close')"
		>
			<div class="bx-call-users-popup">
				<template v-if="!isLoading && !hasError">
					<div v-for="user in this.usersData" :key="user.id" class="bx-call-users-popup__user-item">
						<img
							v-if="user.avatar"
							:src="encodeURI(user.avatar)"
							:alt="user.name"
							class="bx-call-users-popup__user-avatar"
							draggable="false"
						/>
						<div v-else class="bx-call-users-popup__user-avatar --icon" :style="backgroundStyle(user)"></div>
						<div class="bx-call-users-popup__user-name">{{ user.name }}</div>
					</div>
				</template>
				<CallLoader v-else-if="isLoading" />
			</div>
		</CallPopupContainer>
	`
	};

	exports.UserListPopup = UserListPopup;

}((this.BX.Call.Component = this.BX.Call.Component || {}),BX.Call.Component.Elements));
//# sourceMappingURL=user-list-popup.bundle.js.map

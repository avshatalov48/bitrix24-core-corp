/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,tasks_v2_component_elements_userAvatar) {
	'use strict';

	// @vue/component
	const UserCheckbox = {
	  name: 'UiUserCheckbox',
	  components: {
	    UserAvatar: tasks_v2_component_elements_userAvatar.UserAvatar
	  },
	  props: {
	    /** @type UserModel */
	    initUser: {
	      type: Object,
	      required: true
	    },
	    number: {
	      type: Number,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['update:checked'],
	  setup() {
	    return {
	      UserAvatarSize: tasks_v2_component_elements_userAvatar.UserAvatarSize
	    };
	  },
	  computed: {
	    user() {
	      return this.initUser;
	    },
	    containerClasses() {
	      return {
	        'b24-user-checkbox-container': true,
	        '--checked': this.checked
	      };
	    }
	  },
	  methods: {
	    toggleCheck() {
	      this.$emit('update:checked', !this.checked);
	    }
	  },
	  template: `
		<div :class="containerClasses" @click="toggleCheck">
			<div class="b24-user-checkbox-avatar">
				<UserAvatar
					:key="user.id"
					:id="user.id"
					:src="user.image"
					:type="user.type"
					:size="UserAvatarSize.XS"
					class="b24-user-checkbox-avatar-item"
				/>
			</div>
			<div class="b24-user-checkbox-number">
				{{ number }}
			</div>
		</div>
	`
	};

	exports.UserCheckbox = UserCheckbox;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.Tasks.V2.Component.Elements));
//# sourceMappingURL=user-checkbox.bundle.js.map

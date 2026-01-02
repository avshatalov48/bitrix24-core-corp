/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_avatar) {
	'use strict';

	const UserAvatarSize = Object.freeze({
	  XXS: 'XXS',
	  XS: 'XS',
	  S: 'S',
	  M: 'M',
	  L: 'L',
	  XL: 'XL',
	  XXL: 'XXL',
	  XXXL: 'XXXL'
	});
	const UserAvatarSizeMap = Object.freeze({
	  [UserAvatarSize.XXXL]: 94,
	  [UserAvatarSize.XXL]: 60,
	  [UserAvatarSize.XL]: 48,
	  [UserAvatarSize.L]: 42,
	  [UserAvatarSize.M]: 32,
	  [UserAvatarSize.S]: 22,
	  [UserAvatarSize.XS]: 18,
	  [UserAvatarSize.XXS]: 14
	});

	// @vue/component
	const UserAvatar = {
	  name: 'UiUserAvatar',
	  props: {
	    src: {
	      type: String,
	      default: ''
	    },
	    type: {
	      type: String,
	      default: 'employee'
	    },
	    size: {
	      type: String,
	      default: UserAvatarSize.S
	    }
	  },
	  watch: {
	    src() {
	      this.render();
	    }
	  },
	  mounted() {
	    this.render();
	  },
	  methods: {
	    render() {
	      var _this$avatar, _this$avatar$getConta;
	      (_this$avatar = this.avatar) == null ? void 0 : (_this$avatar$getConta = _this$avatar.getContainer()) == null ? void 0 : _this$avatar$getConta.remove();
	      const AvatarClass = this.type === 'collaber' ? ui_avatar.AvatarRoundGuest : ui_avatar.AvatarBase;
	      this.avatar = new AvatarClass({
	        size: UserAvatarSizeMap[this.size],
	        picPath: this.src,
	        baseColor: '#858D95'
	      });
	      this.avatar.renderTo(this.$refs.container);
	    }
	  },
	  template: `
		<div
			ref="container"
			class="b24-user-avatar"
		></div>
	`
	};

	exports.UserAvatar = UserAvatar;
	exports.UserAvatarSize = UserAvatarSize;
	exports.UserAvatarSizeMap = UserAvatarSizeMap;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI));
//# sourceMappingURL=user-avatar.bundle.js.map

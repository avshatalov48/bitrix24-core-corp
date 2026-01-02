/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_iconSet_api_vue) {
	'use strict';

	// @vue/component
	const AddBackground = {
	  name: 'AddBackground',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    isActive: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  data() {
	    return {
	      isHovered: false
	    };
	  },
	  template: `
		<div>
			<div
				class="b24-add-background"
				@mouseenter="isHovered = true"
				@mouseleave="isHovered = false"
			></div>
			<div
				class="b24-add-background-button"
				:class="{ '--active': isHovered || isActive }"
			>
				<BIcon :name="Outline.PLUS_L"/>
			</div>
		</div>
	`
	};

	exports.AddBackground = AddBackground;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI.IconSet));
//# sourceMappingURL=add-background.bundle.js.map

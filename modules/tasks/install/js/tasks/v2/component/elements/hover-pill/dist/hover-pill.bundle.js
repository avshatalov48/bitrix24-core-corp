/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_iconSet_api_vue) {
	'use strict';

	// @vue/component
	const HoverPill = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    withClear: {
	      type: Boolean,
	      default: false
	    },
	    readonly: {
	      type: Boolean,
	      default: false
	    },
	    transparentHover: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['clear'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  template: `
		<div
			class="b24-hover-pill"
			:class="{ '--readonly': readonly, '--transparent': transparentHover }"
			tabindex="0"
		>
			<div class="b24-hover-pill-content">
				<slot/>
			</div>
			<div v-if="withClear" class="b24-hover-pill-remover" @click.capture.stop="$emit('clear')">
				<BIcon :name="Outline.CROSS_L"/>
			</div>
		</div>
	`
	};

	exports.HoverPill = HoverPill;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI.IconSet));
//# sourceMappingURL=hover-pill.bundle.js.map

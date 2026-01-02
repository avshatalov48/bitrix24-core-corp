/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_components_richLoc) {
	'use strict';

	// @vue/component
	const BaseRichLoc = {
	  name: 'BaseRichLoc',
	  components: {
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    message: {
	      type: String,
	      required: true
	    },
	    enabledRules: {
	      type: Array,
	      default: () => []
	    }
	  },
	  computed: {
	    activePlaceholders() {
	      return this.enabledRules.flatMap(rule => `[${rule.name}]`);
	    },
	    rulePropsMap() {
	      const result = {};
	      this.enabledRules.forEach(rule => {
	        const ruleComponent = rule.component;
	        result[rule.name] = {};
	        if (ruleComponent.props) {
	          Object.keys(ruleComponent.props).forEach(propName => {
	            if (propName in this.$attrs) {
	              result[rule.name][propName] = this.$attrs[propName];
	            }
	          });
	        }
	      });
	      return result;
	    }
	  },
	  template: `
		<RichLoc :text="message" :placeholder="activePlaceholders">
			<template v-for="rule in enabledRules" #[rule.name]="{ text }">
				<component
					:is="rule.component"
					:text="text"
					v-bind="rulePropsMap[rule.name]"
				/>
			</template>
		</RichLoc>
	`
	};

	// @vue/component
	const HelpDeskRule = {
	  name: 'HelpDeskRule',
	  props: {
	    // default property for every rule
	    text: String,
	    // current rule properties
	    code: {
	      type: String,
	      required: true
	    },
	    anchor: {
	      type: String,
	      default: null
	    },
	    redirect: {
	      type: String,
	      default: 'detail'
	    },
	    linkClass: {
	      type: [String, Object, Array],
	      default: 'booking--help-desk-link'
	    }
	  },
	  methods: {
	    showHelpDesk() {
	      if (top.BX.Helper) {
	        const anchor = this.anchor;
	        const params = {
	          redirect: this.redirect,
	          code: this.code,
	          ...(anchor !== null && {
	            anchor
	          })
	        };
	        const queryString = Object.entries(params).map(([key, value]) => `${key}=${value}`).join('&');
	        top.BX.Helper.show(queryString);
	      }
	    }
	  },
	  template: `
		<span
			:class="linkClass"
			role="button"
			tabindex="0"
			@click="showHelpDesk"
		>
		  {{ text }}
		</span>
	`
	};

	// @vue/component
	const NoWrapRule = {
	  name: 'NoWrapRule',
	  props: {
	    // default property for every rule
	    text: String
	  },
	  template: `
		{{ text }}
	`
	};

	const ruleRegistry = {
	  helpdesk: {
	    name: 'helpdesk',
	    component: HelpDeskRule
	  },
	  nowrap: {
	    name: 'nowrap',
	    component: NoWrapRule
	  }
	};

	// @vue/component
	const EmptyRichLoc = {
	  name: 'EmptyRichLoc',
	  components: {
	    BaseRichLoc
	  },
	  props: {
	    message: {
	      type: String,
	      required: true
	    },
	    rules: {
	      type: Array,
	      default: () => []
	    }
	  },
	  computed: {
	    enabledRules() {
	      return [...this.rules].map(ruleName => ruleRegistry[ruleName]).filter(Boolean);
	    }
	  },
	  template: `
		<BaseRichLoc
			:message="message"
			:enabled-rules="enabledRules"
			v-bind="$attrs"
		/>
	`
	};

	const DEFAULT_RULES = ['helpdesk'];

	// @vue/component
	const HelpDeskLoc = {
	  name: 'HelpDeskLoc',
	  components: {
	    EmptyRichLoc
	  },
	  props: {
	    message: {
	      type: String,
	      required: true
	    },
	    rules: {
	      type: Array,
	      default: () => []
	    }
	  },
	  computed: {
	    enabledRules() {
	      return [...DEFAULT_RULES, ...this.rules];
	    }
	  },
	  template: `
		<EmptyRichLoc
			:message="message"
			:rules="enabledRules"
			v-bind="$attrs"
		/>
	`
	};

	exports.EmptyRichLoc = EmptyRichLoc;
	exports.HelpDeskLoc = HelpDeskLoc;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI.Vue3.Components));
//# sourceMappingURL=help-desk-loc.bundle.js.map

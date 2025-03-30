/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_components_richLoc) {
	'use strict';

	const HelpDeskLoc = {
	  name: 'HelpDeskLoc',
	  props: {
	    message: {
	      type: String,
	      required: true
	    },
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
	          redirect: 'detail',
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
	  components: {
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  template: `
		<RichLoc :text="message" placeholder="[helpdesk]">
			<template #helpdesk="{ text }">
				<slot name="helpdesk">
					<span
						:class="linkClass"
						role="button"
						tabindex="0"
						@click="showHelpDesk"
					>
						{{ text }}
					</span>
				</slot>
			</template>
		</RichLoc>
	`
	};

	exports.HelpDeskLoc = HelpDeskLoc;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI.Vue3.Components));
//# sourceMappingURL=help-desk-loc.bundle.js.map

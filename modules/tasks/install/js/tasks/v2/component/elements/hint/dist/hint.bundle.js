/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,ui_vue3_components_popup) {
	'use strict';

	// @vue/component
	const Hint = {
	  components: {
	    Popup: ui_vue3_components_popup.Popup
	  },
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    options: {
	      /** @type PopupOptions */
	      type: Object,
	      default: () => ({})
	    }
	  },
	  emits: ['close'],
	  computed: {
	    popupId() {
	      return `tasks-hint-${main_core.Text.getRandom(10)}`;
	    },
	    popupOptions() {
	      return {
	        bindElement: this.bindElement,
	        maxWidth: 320,
	        offsetLeft: 40,
	        background: 'var(--ui-color-bg-content-inapp)',
	        padding: 13,
	        angle: true,
	        targetContainer: document.body,
	        ...this.options
	      };
	    }
	  },
	  template: `
		<Popup
			:id="popupId"
			:options="popupOptions"
			@close="$emit('close')"
		>
			<div class="tasks-hint">
				<slot></slot>
			</div>
		</Popup>
	`
	};
	const tooltip = params => ({
	  timeout: 500,
	  ...params,
	  popupOptions: {
	    className: 'tasks-hint',
	    darkMode: false,
	    offsetTop: 2,
	    background: 'var(--ui-color-bg-content-inapp)',
	    padding: 6,
	    angle: true,
	    targetContainer: document.body,
	    ...params.popupOptions
	  }
	});

	exports.Hint = Hint;
	exports.tooltip = tooltip;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX,BX.UI.Vue3.Components));
//# sourceMappingURL=hint.bundle.js.map

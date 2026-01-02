/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_components_popup) {
	'use strict';

	// @vue/component
	const BottomSheet = {
	  components: {
	    Popup: ui_vue3_components_popup.Popup
	  },
	  props: {
	    isExpanded: {
	      type: Boolean,
	      default: false
	    },
	    padding: {
	      type: Number,
	      default: 24
	    },
	    getBindElement: {
	      type: Function,
	      default: null
	    },
	    getTargetContainer: {
	      type: Function,
	      default: null
	    }
	  },
	  computed: {
	    popupOptions() {
	      var _this$getBindElement, _this$getTargetContai;
	      return {
	        id: 'b24-bottom-sheet',
	        bindElement: (_this$getBindElement = this.getBindElement) == null ? void 0 : _this$getBindElement.call(this),
	        targetContainer: (_this$getTargetContai = this.getTargetContainer) == null ? void 0 : _this$getTargetContai.call(this),
	        className: `b24-bottom-sheet ${this.isExpanded ? '--expanded' : ''}`,
	        borderRadius: '18px 18px 0 0',
	        padding: this.padding,
	        animation: {
	          showClassName: '--show',
	          closeClassName: '--close',
	          closeAnimationType: 'animation'
	        },
	        autoHide: false
	      };
	    }
	  },
	  methods: {
	    adjustPosition() {},
	    freeze() {},
	    unfreeze() {}
	  },
	  template: `
		<Popup :options="popupOptions">
			<div class="b24-bottom-sheet-content" :style="{ '--padding': padding + 'px' }">
				<slot></slot>
			</div>
		</Popup>
	`
	};

	exports.BottomSheet = BottomSheet;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI.Vue3.Components));
//# sourceMappingURL=bottom-sheet.bundle.js.map

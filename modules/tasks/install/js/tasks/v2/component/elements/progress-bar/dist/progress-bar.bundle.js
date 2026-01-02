/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports) {
	'use strict';

	// @vue/component
	const ProgressBar = {
	  name: 'UiProgressBar',
	  props: {
	    totalValue: {
	      type: Number,
	      required: true
	    },
	    completedValue: {
	      type: Number,
	      required: true
	    },
	    width: {
	      type: Number,
	      default: 100
	    },
	    height: {
	      type: Number,
	      default: 10
	    },
	    color: {
	      type: String,
	      default: '#000000'
	    },
	    bgColor: {
	      type: String,
	      default: '#ffffff'
	    },
	    borderRadius: {
	      type: Number,
	      default: 5
	    }
	  },
	  computed: {
	    progressPercentage() {
	      if (this.totalValue === 0) {
	        return 0;
	      }
	      const percentage = this.completedValue / this.totalValue * 100;
	      return Math.min(Math.max(percentage, 0), 100);
	    },
	    containerStyle() {
	      return {
	        width: `${this.width}px`,
	        height: `${this.height}px`,
	        backgroundColor: this.bgColor,
	        borderRadius: `${this.borderRadius}px`
	      };
	    },
	    progressStyle() {
	      return {
	        width: `${this.progressPercentage}%`,
	        backgroundColor: this.color
	      };
	    }
	  },
	  template: `
		<div class="progress-bar-container" :style="containerStyle">
			<div class="progress-bar" :style="progressStyle"></div>
		</div>
	`
	};

	exports.ProgressBar = ProgressBar;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {})));
//# sourceMappingURL=progress-bar.bundle.js.map

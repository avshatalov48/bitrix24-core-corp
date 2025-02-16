/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_cnt) {
	'use strict';

	const Counter = {
	  name: 'UiCounter',
	  props: {
	    value: {
	      type: [String, Number],
	      default: ''
	    },
	    border: {
	      type: Boolean,
	      default: false
	    },
	    size: {
	      type: String,
	      default: ''
	    },
	    color: {
	      type: String,
	      default: ''
	    },
	    maxValue: {
	      type: [Number, Boolean],
	      default: 99
	    },
	    counterClass: {
	      type: String,
	      default: ''
	    }
	  },
	  computed: {
	    counterValue() {
	      if (this.value < this.maxValue) {
	        return this.value;
	      }
	      return `${Number(this.maxValue)}+`;
	    }
	  },
	  template: `
		<div :class="['ui-counter', counterClass, size, color, { 'ui-counter-border': border }]">
			<div class="ui-counter-inner">{{ counterValue }}</div>
		</div>
	`
	};

	exports.CounterSize = ui_cnt.CounterSize;
	exports.CounterColor = ui_cnt.CounterColor;
	exports.Counter = Counter;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI));
//# sourceMappingURL=counter.bundle.js.map

this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports) {
	'use strict';

	var StatusTypes = {
	  current: 'current',
	  disabled: 'disabled',
	  complete: 'complete'
	};

	var Hint = {
	  methods: {
	    onHint: function onHint(e) {
	      this.$emit('on-hint', e);
	    }
	  },
	  template: "\n\t\t<div @click=\"onHint\" class=\"salescenter-app-payment-by-sms-item-title-info\">\n\t\t\t<slot></slot>\n\t\t</div>\n\t"
	};

	var Title = {
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-title\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-title-text\">\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t\t<slot name=\"item-hint\"></slot>\n\t\t</div>\n\t"
	};

	var Counter = {
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\">\n\t\t\t\t<slot name=\"block-counter-number\"/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var BlockCounter = {
	  components: {
	    'block-counter': Counter
	  },
	  template: "\n\t\t<div>\n\t\t\t<block-counter/>\n\t\t\t<slot name=\"block-container\"></slot>\n\t\t</div>\n\t"
	};

	var CounterNumber = {
	  props: {
	    value: {
	      type: String,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'block-counter': Counter
	  },
	  computed: {
	    counterClass: function counterClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-counter-number-checker': this.checked
	      };
	    }
	  },
	  template: "\n\t\t<block-counter>\n\t\t\t<template v-slot:block-counter-number>\n\t\t\t\t<div :class=\"counterClass\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number-text\">{{value}}</div>\n\t\t\t</template>\n\t\t</block-counter>\n\t"
	};

	var BlockNumberTitle = {
	  props: {
	    counter: {
	      type: String,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'block-title': Title,
	    'block-counter-number': CounterNumber
	  },
	  template: "\n\t\t<div>\n\t\t\t<block-counter-number :value=\"counter\" :checked=\"checked\" />\n\t\t\t<block-title>\n\t\t\t\t<template v-slot:default>\n\t\t\t\t\t<slot name=\"block-title-title\"></slot>\n\t\t\t\t</template>\n\t\t\t</block-title>\n\t\t\t<slot name=\"block-container\"></slot>\n\t\t</div>\n\t"
	};

	var BlockNumberTitleHint = {
	  props: {
	    counter: {
	      type: String,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'block-hint': Hint,
	    'block-title': Title,
	    'block-counter-number': CounterNumber
	  },
	  methods: {
	    onHint: function onHint(e) {
	      this.$emit('on-item-hint', e);
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<block-counter-number :value=\"counter\" :checked=\"checked\"/>\n\t\t\t<block-title>\n\t\t\t\t<template v-slot:default>\n\t\t\t\t\t<slot name=\"block-title-title\"></slot>\n\t\t\t\t</template>\n\t\t\t\t<template v-slot:item-hint>\n\t\t\t\t\t<block-hint v-on:on-hint=\"onHint\">\n\t\t\t\t\t\t<template v-slot:default>\n\t\t\t\t\t\t\t<slot name=\"block-hint-title\"></slot>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</block-hint>\n\t\t\t\t</template>\n\t\t\t</block-title>\n\t\t\t<slot name=\"block-container\"></slot>\n\t\t</div>\n\t"
	};

	exports.StatusTypes = StatusTypes;
	exports.Hint = Hint;
	exports.Title = Title;
	exports.Counter = Counter;
	exports.CounterNumber = CounterNumber;
	exports.BlockCounter = BlockCounter;
	exports.BlockNumberTitle = BlockNumberTitle;
	exports.BlockNumberTitleHint = BlockNumberTitleHint;

}((this.BX.Salescenter.Component = this.BX.Salescenter.Component || {})));
//# sourceMappingURL=stage-block.bundle.js.map

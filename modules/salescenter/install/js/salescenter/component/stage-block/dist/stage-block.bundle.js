/* eslint-disable */
this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,salescenter_marketplace) {
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
	  computed: {
	    hasContentSlot: function hasContentSlot() {
	      try {
	        return this.$slots['default'][0].text !== '';
	      } catch (err) {
	        return false;
	      }
	    }
	  },
	  template: "\n\t\t<div v-if=\"hasContentSlot\" @click=\"onHint\" class=\"salescenter-app-payment-by-sms-item-title-info\">\n\t\t\t<slot></slot>\n\t\t</div>\n\t"
	};

	var Title = {
	  props: {
	    collapsible: {
	      type: Boolean,
	      "default": false
	    }
	  },
	  methods: {
	    onTitleClicked: function onTitleClicked() {
	      this.$emit('on-title-clicked');
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-title\" :class=\"{ 'salescenter-app-payment-by-sms-item-title-collapsible': collapsible }\" v-on:click=\"onTitleClicked\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-title-text\">\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t\t<slot name=\"item-hint\"></slot>\n\t\t\t<slot name=\"title-items\"></slot>\n\t\t\t<slot name=\"title-name\"></slot>\n\t\t</div>\n\t"
	};

	var Counter = {
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\">\n\t\t\t\t<slot name=\"block-counter-number\"/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var CounterNumber = {
	  props: {
	    value: {
	      type: Number,
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

	var TitleItem = {
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  methods: {
	    onTitleItem: function onTitleItem(item) {
	      this.$emit('on-title-item', item);
	    }
	  },
	  template: "\n\t\t<span class=\"salescenter-app-payment-by-sms-item-container-payment-title-item-text\"\n\t\t\tv-on:click.stop.prevent=\"onTitleItem(item)\"\n\t\t>{{ item.name }}</span>\n\t"
	};

	var TitleName = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    }
	  },
	  template: "\n\t\t<span class=\"salescenter-app-payment-by-sms-item-container-payment-title-item-text\">{{ name }}</span>\n\t"
	};

	var Block = {
	  props: {
	    config: {
	      type: Object,
	      required: true
	    }
	  },
	  components: {
	    'block-counter': Counter,
	    'block-title': Title,
	    'block-title-item': TitleItem,
	    'block-title-name': TitleName,
	    'block-counter-number': CounterNumber,
	    'block-hint': Hint
	  },
	  data: function data() {
	    return {
	      containerHeight: null,
	      collapse: false,
	      blockContainer: null
	    };
	  },
	  computed: {
	    titleItems: function titleItems() {
	      return this.config.items.slice(0, this.TITLE_ITEMS_LIMIT);
	    },
	    displayClass: function displayClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-hide': this.collapse,
	        'salescenter-app-payment-by-sms-item-show': !this.collapse
	      };
	    },
	    hintClassModifier: function hintClassModifier() {
	      return this.config.hintClassModifier || '';
	    },
	    bodyStyle: function bodyStyle() {
	      return {
	        maxHeight: this.containerHeight ? this.containerHeight + 'px' : null
	      };
	    },
	    hasTitleSlot: function hasTitleSlot() {
	      return !!this.$slots['block-title-title'];
	    }
	  },
	  methods: {
	    onHint: function onHint(e) {
	      this.$emit('on-item-hint', e);
	    },
	    onTitleClicked: function onTitleClicked() {
	      this.$emit('on-title-clicked');
	      if (this.config.collapsible) {
	        this.adjustCollapsed();
	      }
	    },
	    adjustCollapsed: function adjustCollapsed() {
	      if (this.collapse) {
	        this.collapse = false;
	      } else {
	        this.collapse = true;
	      }
	      var collapseOption = this.collapse ? 'Y' : 'N';
	      this.$emit('on-adjust-collapsed', collapseOption);
	    },
	    openSliderForTitleItem: function openSliderForTitleItem(titleItem) {
	      var _this = this;
	      var slider = new salescenter_marketplace.AppSlider();
	      var link = titleItem.link;
	      slider.openAppLocalLink(link);
	      slider.subscribe(salescenter_marketplace.EventTypes.AppSliderSliderClose, function (e) {
	        return _this.$emit('on-tile-slider-close', {
	          data: e.data
	        });
	      });
	    }
	  },
	  mounted: function mounted() {
	    this.collapse = this.config.initialCollapseState;
	  },
	  template: "\n\t\t<div :class=\"displayClass\">\n\t\t\t<block-counter-number :value=\"config.counter\" :checked=\"config.checked\" v-if=\"config.counter\" />\n\t\t\t<block-counter v-else />\n\t\t\t<block-title @on-title-clicked=\"onTitleClicked\" :collapsible=\"config.collapsible\" v-if=\"hasTitleSlot\">\n\t\t\t\t<template v-slot:default>\n\t\t\t\t\t<slot name=\"block-title-title\"></slot>\n\t\t\t\t</template>\n\t\t\t\t<template v-slot:item-hint v-if=\"config.showHint\">\n\t\t\t\t\t<block-hint v-on:on-hint.stop.prevent=\"onHint\" :class=\"hintClassModifier\">\n\t\t\t\t\t\t<template v-slot:default>\n\t\t\t\t\t\t\t<slot name=\"block-hint-title\"></slot>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</block-hint>\n\t\t\t\t</template>\n\t\t\t\t<template v-slot:title-items v-if=\"collapse\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-title-item-wrapper\">\n\t\t\t\t\t\t<block-title-item\n\t\t\t\t\t\t\tv-for=\"(item, index) in config.titleItems\"\n\t\t\t\t\t\t\tv-bind:key=\"index\"\n\t\t\t\t\t\t\tv-on:on-title-item=\"openSliderForTitleItem(item)\"\n\t\t\t\t\t\t\t:item=\"item\">\n\t\t\t\t\t\t</block-title-item>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-slot:title-name v-if=\"collapse && config.titleName\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-title-item-wrapper\">\n\t\t\t\t\t\t<block-title-name\n\t\t\t\t\t\t\t:name=\"config.titleName\">\n\t\t\t\t\t\t</block-title-name>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</block-title>\n\t\t\t<div :class=\"{'salescenter-app-payment-collapsible-block': config.collapsible, 'salescenter-app-payment-collapsible-block-collapsed': collapse}\" v-bind:style=\"[config.collapsible ? bodyStyle : null]\" ref=\"containerWrapper\">\n\t\t\t\t<slot name=\"block-container\"></slot>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	exports.StatusTypes = StatusTypes;
	exports.Hint = Hint;
	exports.Title = Title;
	exports.Counter = Counter;
	exports.CounterNumber = CounterNumber;
	exports.Block = Block;

}((this.BX.Salescenter.Component = this.BX.Salescenter.Component || {}),BX.Salescenter));
//# sourceMappingURL=stage-block.bundle.js.map

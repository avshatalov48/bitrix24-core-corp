this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
(function (exports) {
	'use strict';

	var Hint = {
	  methods: {
	    onMouseenter: function onMouseenter(e) {
	      this.$emit('tile-hint-on-mouseenter', {
	        data: {
	          event: e
	        }
	      });
	    },
	    onMouseleave: function onMouseleave() {
	      this.$emit('tile-hint-on-mouseleave');
	    }
	  },
	  computed: {
	    classes: function classes() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-info': true
	      };
	    }
	  },
	  template: "\n\t\t<div :class=\"classes\" \n\t\t\tv-on:mouseenter=\"onMouseenter($event)\" \n\t\t\tv-on:mouseleave=\"onMouseleave\"\n\t\t\t/>\n"
	};

	var Image = {
	  props: {
	    src: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    classes: function classes() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-img': true
	      };
	    }
	  },
	  template: "\n\t\t\t<img :class=\"classes\" :src=\"src\">\n"
	};

	var Label = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    }
	  },
	  template: "\n\t\t\t<span>{{name}}</span>\n"
	};

	var TileLabel = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-label-block': Label
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-label-on-click');
	    }
	  },
	  template: "<div @click=\"onClick()\">\n\t\t\t\t\t<tile-label-block :name=\"name\"/>\n\t\t\t\t</div>"
	};

	var Background = {
	  props: {
	    src: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    classes: function classes() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-img-del': true
	      };
	    }
	  },
	  methods: {
	    getUrl: function getUrl() {
	      return encodeURI(this.src);
	    }
	  },
	  template: "\n\t\t\t<div :class=\"classes\">\n\t\t\t\t<img :src=\"getUrl()\">\n\t\t\t</div>\n"
	};

	var TileHintImg = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    src: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-hint-block': Hint,
	    'tile-img-block': Image
	  },
	  computed: {
	    classConteiner: function classConteiner() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item': true
	      };
	    },
	    classContent: function classContent() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-contet': true
	      };
	    }
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-hint-img-on-click');
	    },
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(e) {
	      this.$emit('tile-label-img-hint-on-mouseenter', e);
	    },
	    hidePopupHint: function hidePopupHint() {
	      this.$emit('tile-label-img-hint-on-mouseleave');
	    }
	  },
	  template: "\n\t\t<div @click=\"onClick()\" :class=\"classConteiner\">\n\t\t\t<div :class=\"classContent\">\n\t\t\t\t<tile-hint-block\n\t\t\t\t\tv-on:tile-hint-on-mouseenter=\"showSmsMessagePopupHint\"\n\t\t\t\t\tv-on:tile-hint-on-mouseleave=\"hidePopupHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-img-block :src=\"src\"/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TileLabelPlus = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-label-block': Label
	  },
	  computed: {
	    classConteiner: function classConteiner() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-added': true,
	        'salescenter-app-payment-by-sms-item-container-payment-item': true
	      };
	    },
	    classContent: function classContent() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-contet': true
	      };
	    },
	    classLabel: function classLabel() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-added-text': true
	      };
	    }
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-label-plus-on-click');
	    }
	  },
	  template: "\n\t\t<div @click=\"onClick()\" :class=\"classConteiner\">\n\t\t\t<div :class=\"classContent\"> \n\t\t\t\t<tile-label-block \n\t\t\t\t\t:class=\"classLabel\" \n\t\t\t\t\t:name=\"name\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TileHintImgCaption = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    src: {
	      type: String,
	      required: true
	    },
	    caption: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-hint-block': Hint,
	    'tile-img-block': Image,
	    'tile-label-block': Label
	  },
	  computed: {
	    hasImg: function hasImg() {
	      return this.src.length > 0;
	    },
	    classConteiner: function classConteiner() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item': true
	      };
	    },
	    classContent: function classContent() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-contet': true
	      };
	    },
	    classTileText: function classTileText() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-title-text': true
	      };
	    }
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-hint-img-label-on-click');
	    },
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(e) {
	      this.$emit('tile-hint-img-label-on-mouseenter', e);
	    },
	    hidePopupHint: function hidePopupHint() {
	      this.$emit('tile-hint-img-label-on-mouseleave');
	    }
	  },
	  template: "\n\t\t<div @click=\"onClick()\" :class=\"classConteiner\">\n\t\t\t<div :class=\"classContent\">\n\t\t\t\t<tile-hint-block\n\t\t\t\t\tv-on:tile-hint-on-mouseenter=\"showSmsMessagePopupHint\"\n\t\t\t\t\tv-on:tile-hint-on-mouseleave=\"hidePopupHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-img-block :src=\"src\"/>\n\t\t\t\t<tile-label-block :name=\"caption\" :class=\"classTileText\"/> \n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TileHintBackground = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    src: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-hint-block': Hint,
	    'tile-background-block': Background
	  },
	  computed: {
	    classConteiner: function classConteiner() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item': true
	      };
	    },
	    classContent: function classContent() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-contet': true
	      };
	    }
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-hint-bg-on-click');
	    },
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(e) {
	      this.$emit('tile-label-bg-hint-on-mouseenter', e);
	    },
	    hidePopupHint: function hidePopupHint() {
	      this.$emit('tile-label-bg-hint-on-mouseleave');
	    }
	  },
	  template: "\n\t\t<div @click=\"onClick()\" :class=\"classConteiner\">\n\t\t\t<div :class=\"classContent\">\n\t\t\t\t<tile-hint-block\n\t\t\t\t\tv-on:tile-hint-on-mouseenter=\"showSmsMessagePopupHint\"\n\t\t\t\t\tv-on:tile-hint-on-mouseleave=\"hidePopupHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-background-block :src=\"src\"/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TileHintBackgroundCaption = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    src: {
	      type: String,
	      required: true
	    },
	    caption: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    'tile-hint-block': Hint,
	    'tile-label-block': Label,
	    'tile-background-block': Background
	  },
	  computed: {
	    hasImg: function hasImg() {
	      return this.src.length > 0;
	    },
	    classConteiner: function classConteiner() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item': true
	      };
	    },
	    classContent: function classContent() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-contet': true
	      };
	    },
	    classTileText: function classTileText() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-payment-item-title-text': true
	      };
	    }
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('tile-hint-bg-label-on-click');
	    },
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(e) {
	      this.$emit('tile-hint-bg-label-on-mouseenter', e);
	    },
	    hidePopupHint: function hidePopupHint() {
	      this.$emit('tile-hint-bg-label-on-mouseleave');
	    }
	  },
	  template: "\n\t\t<div @click=\"onClick()\" :class=\"classConteiner\">\n\t\t\t<div :class=\"classContent\">\n\t\t\t\t<tile-hint-block\n\t\t\t\t\tv-on:tile-hint-on-mouseenter=\"showSmsMessagePopupHint\"\n\t\t\t\t\tv-on:tile-hint-on-mouseleave=\"hidePopupHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-background-block :src=\"src\" class=\"salescenter-app-payment-by-sms-item-container-payment-item-img-del-title\"/>\n\t\t\t\t<tile-label-block :name=\"caption\" :class=\"classTileText\"/> \n\t\t\t</div>\n\t\t</div>\n\t"
	};

	exports.Hint = Hint;
	exports.Image = Image;
	exports.Label = Label;
	exports.TileLabel = TileLabel;
	exports.Background = Background;
	exports.TileHintImg = TileHintImg;
	exports.TileLabelPlus = TileLabelPlus;
	exports.TileHintImgCaption = TileHintImgCaption;
	exports.TileHintBackground = TileHintBackground;
	exports.TileHintBackgroundCaption = TileHintBackgroundCaption;

}((this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {})));
//# sourceMappingURL=tile.bundle.js.map

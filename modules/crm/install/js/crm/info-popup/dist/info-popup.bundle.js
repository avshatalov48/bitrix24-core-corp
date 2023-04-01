this.BX = this.BX || {};
(function (exports,currency_currencyCore,main_core,ui_vue3,main_popup) {
	'use strict';

	const InfoPopupIcons = Object.freeze({
	  DELIVERY: 'delivery'
	});

	const InfoPopupHeader = {
	  props: {
	    icon: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    hint: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    subtitle: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    iconClassname() {
	      return ['crm__info-popup_icon', this.getIconModifier()];
	    }

	  },
	  methods: {
	    getIconModifier() {
	      if (!this.isIconExist(this.icon)) {
	        return '--empty';
	      }

	      return `--${InfoPopupIcons[this.icon] || InfoPopupIcons[this.icon.toUpperCase()]}`;
	    },

	    isIconExist() {
	      if (!main_core.Type.isString(this.icon)) {
	        return false;
	      }

	      return !!(InfoPopupIcons[this.icon] || InfoPopupIcons[this.icon.toUpperCase()]);
	    }

	  },
	  template: `
		<header class="crm__info-popup_header">
				<div
					v-if="icon"
					:class="iconClassname"
				/>
				<div class="crm__info-popup_header-right">
					<div class="crm__info-popup_title-container">
						<h2 class="crm__info-popup_title ui-typography-heading-h2">
							{{ title }}
						</h2>
						<div v-if="hint" class="crm__info-popup_hint"></div>
					</div>
					<div v-if="subtitle" class="crm__info-popup_subtitle">
						{{ subtitle }}
					</div>
				</div>
			</header>
	`
	};

	const InfoPopupContentBlock = {
	  props: {
	    type: Object,
	    content: String,
	    attributes: Object
	  }
	};

	const InfoPopupContentBlockText = {
	  extends: InfoPopupContentBlock,
	  template: `
		<span>{{ content }}</span>
	`
	};

	const InfoPopupContentBlockMoney = {
	  extends: InfoPopupContentBlock,
	  computed: {
	    opportunity() {
	      var _this$attributes;

	      return (_this$attributes = this.attributes) == null ? void 0 : _this$attributes.opportunity;
	    },

	    currencyId() {
	      var _this$attributes2;

	      return (_this$attributes2 = this.attributes) == null ? void 0 : _this$attributes2.currencyId;
	    },

	    encodedText() {
	      if (!main_core.Type.isNumber(this.opportunity) || !main_core.Type.isStringFilled(this.currencyId)) {
	        return null;
	      }

	      return currency_currencyCore.CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
	    }

	  },
	  template: `
		<span
			v-if="encodedText"
			v-html="encodedText"
		></span>
	`
	};

	const InfoPopupContentBlockLink = {
	  extends: InfoPopupContentBlock,
	  computed: {
	    href() {
	      var _this$attributes;

	      return (_this$attributes = this.attributes) == null ? void 0 : _this$attributes.href;
	    }

	  },
	  template: `
		<a :href="href">{{ content }}</a>
	`
	};

	const InfoPopupContentBlockType = Object.freeze({
	  LINK: 'link',
	  TEXT: 'text',
	  MONEY: 'money',
	  PHONE: 'phone',
	  MIXED: 'mixed'
	});

	const InfoPopupContentBlockPhone = {
	  extends: InfoPopupContentBlock,
	  computed: {
	    phoneNumber() {
	      return this.attributes.phone || '';
	    },

	    canPerformCalls() {
	      return !!this.attributes.canPerformCalls;
	    }

	  },
	  methods: {
	    makeCall() {
	      if (typeof window.top['BXIM'] !== 'undefined' && this.canPerformCalls) {
	        window.top['BXIM'].phoneTo(this.phoneNumber);
	      }
	    }

	  },
	  template: `
		<span
			class="crm__info-popup_content-table-field-link --internal"
			@click="makeCall"
		>
			{{ content }}
		</span>
	`
	};

	const InfoPopupContentTableField = {
	  components: {
	    InfoPopupContentBlockLink,
	    InfoPopupContentBlockText,
	    InfoPopupContentBlockMoney
	  },
	  props: {
	    title: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    contentBlock: {
	      type: Object,
	      required: true,
	      default: () => ({})
	    }
	  },
	  computed: {
	    type() {
	      var _this$contentBlock;

	      return (_this$contentBlock = this.contentBlock) == null ? void 0 : _this$contentBlock.type;
	    },

	    contentBlockComponent() {
	      switch (this.type) {
	        case InfoPopupContentBlockType.LINK:
	          return InfoPopupContentBlockLink;

	        case InfoPopupContentBlockType.TEXT:
	          return InfoPopupContentBlockText;

	        case InfoPopupContentBlockType.MONEY:
	          return InfoPopupContentBlockMoney;

	        case InfoPopupContentBlockType.PHONE:
	          return InfoPopupContentBlockPhone;

	        default:
	          return InfoPopupContentBlockText;
	      }
	    }

	  },
	  template: `
		<li class="crm__info-popup_content-table-field">
			<div class="crm__info-popup_content-table-field-title">
				{{ title }}
			</div>
			<div class="crm__info-popup_content-table-field-value">
				<component
					:is="contentBlockComponent"
					:content="contentBlock.content"
					:attributes="contentBlock.attributes"
				/>
			</div>
		</li>
	`
	};

	const InfoPopupContentTable = {
	  components: {
	    InfoPopupContentTableField
	  },
	  props: {
	    fields: Object
	  },
	  template: `
		<div class="crm__info-popup_content-table">
			<ul class="crm__info-popup_content-table-fields">
				<info-popup-content-table-field
					v-for="(field, index) in fields"
					:key="index"
					:title="field.title"
					:content-block="field.contentBlock"
				/>
			</ul>
		</div>
	`
	};

	const InfoPopup = {
	  name: 'InfoPopup',
	  components: {
	    InfoPopupHeader,
	    InfoPopupContentTable
	  },
	  props: {
	    header: {
	      type: Object,
	      required: false,
	      default: () => ({
	        title: '',
	        subtitle: '',
	        hint: ''
	      })
	    },
	    fields: {
	      type: Object
	    }
	  },
	  template: `
		<div class="crm__info-popup">
			<info-popup-header
				:title="header.title"
				:subtitle="header.subtitle"
				:hint="header.hint"
				:icon="header.icon"
				
			/>
			<body class="crm__info-popup_body">
				<info-popup-content-table
					:fields="fields"
				/>
			</body>
		</div>`
	};

	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");

	class InfoPopup$1 {
	  constructor(options = {
	    name: 'InfoPopup'
	  }) {
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    this.id = options.id;
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	    this.header = options.content.header;
	    this.contentFields = options.content.fields;
	  }

	  show() {
	    const content = main_core.Dom.create('div');
	    ui_vue3.BitrixVue.createApp(InfoPopup, {
	      header: this.header,
	      fields: this.contentFields
	    }).mount(content);
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	      className: 'crm__info-popup-window',
	      content: content,
	      width: 532,
	      noAllPaddings: true,
	      closeByEsc: true,
	      closeIcon: true,
	      autoHide: true,
	      borderRadius: 10,
	      animation: 'fading-slide'
	    });

	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }

	  hide() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].destroy();
	    }
	  }

	  getPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }

	}

	exports.InfoPopup = InfoPopup$1;

}((this.BX.Crm = this.BX.Crm || {}),BX.Currency,BX,BX.Vue3,BX.Main));
//# sourceMappingURL=info-popup.bundle.js.map

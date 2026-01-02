/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_date,ui_datePicker,sign_v2_b2e_signDropdown,main_core,ui_vue3_components_richMenu,ui_iconSet_api_vue) {
	'use strict';

	const LocMixin = {
	  methods: {
	    loc(phraseCode, replacements) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  }
	};

	// @vue/component
	const RoundedSmallSelectedItemView = {
	  name: 'RoundedSmallSelectedItemView',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    set: () => ui_iconSet_api_vue.Set
	  },
	  template: `
		<div class="sign-v2-b2e-vue-util-sign-selector__rounded-small-selected-item-view">
			<div class="sign-b2e-vue-util-sign-selector__rounded-small-selected-item-view__text">
				{{ title }}
			</div>
			<BIcon
				style="margin-left: 6px; margin-right: 5px;"
				color="#525C69"
				:name="set.CHEVRON_DOWN"
				:size="14"
			/>
		</div>
	`
	};

	// @vue/component
	const DateSelector = {
	  name: 'DateSelector',
	  components: {
	    RoundedSmallSelectedItemView
	  },
	  props: {
	    value: {
	      type: Date,
	      required: true
	    },
	    showTime: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['onSelect'],
	  computed: {
	    formattedDate() {
	      const datePart = this.formatDate(this.value, 'DAY_MONTH_FORMAT');
	      const timePart = this.formatDate(this.value, 'SHORT_TIME_FORMAT');
	      return this.showTime ? `${datePart}, ${timePart}` : datePart;
	    }
	  },
	  created() {
	    this.datePicker = new ui_datePicker.DatePicker({
	      selectionMode: 'single',
	      selectedDates: [this.value],
	      ...(this.showTime && {
	        enableTime: true
	      })
	    });
	    this.datePicker.subscribe(ui_datePicker.DatePickerEvent.SELECT, ({
	      data
	    }) => {
	      this.selectDate(data.date);
	    });
	  },
	  mounted() {
	    this.datePicker.getPopup().setBindElement(this.$refs.target);
	  },
	  unmounted() {
	    this.datePicker.hide();
	    this.datePicker.destroy();
	  },
	  methods: {
	    openPicker() {
	      this.datePicker.show();
	    },
	    selectDate(date) {
	      this.$emit('onSelect', date);
	    },
	    formatDate(date, formatType) {
	      const template = main_date.DateTimeFormat.getFormat(formatType);
	      return main_date.DateTimeFormat.format(template, date);
	    }
	  },
	  template: `
		<div
			class="sign-b2e-vue-util-date-selector"
			ref="target"
			@click="openPicker"
		>
			<RoundedSmallSelectedItemView
				:title="formattedDate"
			/>
		</div>
	`
	};

	// @vue/component
	const Notice = {
	  // eslint-disable-next-line vue/multi-word-component-names
	  name: 'Notice',
	  template: `
		<div class="sign-b2e-vue-util-notice">
			<div class="sign-b2e-vue-util-notice__content">
				<slot></slot>
			</div>
		</div>
	`
	};

	// @vue/component
	const SignDropdownComponent = {
	  name: 'SignDropdownComponent',
	  props: {
	    tabs: {
	      type: Array,
	      required: true,
	      default: () => []
	    },
	    entities: {
	      type: Array,
	      required: true,
	      default: () => []
	    },
	    items: {
	      type: Array,
	      required: true,
	      default: () => []
	    },
	    isEnableSearch: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    isWithCaption: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    className: {
	      type: String,
	      required: false,
	      default: 'sign-b2e-document-setup__type-selector'
	    },
	    // eslint-disable-next-line vue/require-prop-types
	    selectedId: {
	      required: false,
	      default: null
	    }
	  },
	  emits: ['onSelected'],
	  watch: {
	    items(value) {
	      this.setItems(value);
	    },
	    selectedId(value) {
	      if (this.dropdown.getSelectedId() !== value) {
	        this.dropdown.selectItem(value);
	      }
	    }
	  },
	  created() {
	    this.dropdown = new sign_v2_b2e_signDropdown.SignDropdown({
	      tabs: this.tabs,
	      entities: this.entities,
	      className: this.className,
	      withCaption: this.isWithCaption,
	      isEnableSearch: this.isEnableSearch
	    });
	    this.setItems(this.items);
	  },
	  mounted() {
	    this.dropdown.subscribe('onSelect', event => this.onSelected(event.data.item));
	    const dropdownContainer = this.$refs.wrapper;
	    if (dropdownContainer) {
	      main_core.Dom.append(this.dropdown.getLayout(), dropdownContainer);
	    }
	  },
	  methods: {
	    setItems(items) {
	      const dropdown = this.dropdown;
	      dropdown.removeItems();
	      const filledItems = items.filter(item => {
	        return main_core.Type.isPlainObject(item) && main_core.Type.isStringFilled(String(item.id)) && main_core.Type.isStringFilled(item.title);
	      });
	      filledItems.forEach(item => {
	        const {
	          id,
	          title,
	          caption,
	          entityId,
	          tabId
	        } = item;
	        dropdown.addItem({
	          id,
	          title,
	          caption,
	          entityId,
	          tabs: tabId,
	          deselectable: false
	        });
	      });
	      if (this.selectedId) {
	        dropdown.selectItem(this.selectedId);
	      }
	    },
	    onSelected(item) {
	      this.$emit('onSelected', item);
	    }
	  },
	  template: `
		<div class="sign-b2e-sign-dropdown-wrapper" ref="wrapper"></div>
	`
	};

	// @vue/component
	const BaseSelectedItemView = {
	  name: 'BaseSelectedItemView',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    set: () => ui_iconSet_api_vue.Set
	  },
	  template: `
		<div class="sign-v2-b2e-vue-util-sign-selector__selected-item-view">
			<div class="sign-b2e-vue-util-sign-selector__selected-item-view__text">
				{{ title }}
			</div>
			<BIcon
				style="margin-left: auto; margin-right: 10px;"
				:name="set.CHEVRON_DOWN"
				:size="20"
			/>
		</div>
	`
	};

	// @vue/component
	const BaseMenuItem = {
	  name: 'BaseMenuItem',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    selected: {
	      type: Boolean,
	      required: false
	    }
	  },
	  computed: {
	    set: () => ui_iconSet_api_vue.Set
	  },
	  template: `
		<div class="sign-b2e-vue-util-sign-selector__menu-item">
			<div class="sign-b2e-vue-util-sign-selector__menu-item__title">
				{{ title }}
			</div>
			<BIcon v-if="selected"
				style="margin-left: auto; margin-right: 16px"
				:name="set.CHECK"
				:size="18"
				color="#2066B0"
			/>
		</div>
	`
	};

	// @vue/component
	const SignSelector = {
	  name: 'SignSelector',
	  components: {
	    BaseSelectedItemView,
	    RichMenuPopup: ui_vue3_components_richMenu.RichMenuPopup,
	    BaseMenuItem
	  },
	  props: {
	    items: {
	      /* @type Array<{ id: string | number, title: string }> */
	      type: Array,
	      required: true
	    },
	    // eslint-disable-next-line vue/require-prop-types
	    selectedId: {
	      required: true
	    }
	  },
	  emits: ['onSelect'],
	  data() {
	    return {
	      isMenuShow: false
	    };
	  },
	  computed: {
	    selectedItemTitle() {
	      var _this$items$find$titl, _this$items$find;
	      return (_this$items$find$titl = (_this$items$find = this.items.find(item => item.id === this.selectedId)) == null ? void 0 : _this$items$find.title) != null ? _this$items$find$titl : '';
	    },
	    popupOptions() {
	      return {
	        bindElement: this.$refs.selector,
	        maxWidth: 400,
	        width: 290
	      };
	    }
	  },
	  methods: {
	    select(id) {
	      this.$emit('onSelect', id);
	      this.isMenuShow = false;
	    }
	  },
	  template: `
		<div class="sign-b2e-vue-util-sign-selector"
			 ref="selector"
			 @click="isMenuShow = true"
		>
			<slot :title="selectedItemTitle">
				<BaseSelectedItemView
					:title="selectedItemTitle"
				/>
			</slot>
			<RichMenuPopup
				v-if="isMenuShow"
				class="sign-b2e-vue-util-sign-selector-container"
				:popupOptions="popupOptions"
				@close="isMenuShow = false"
			>
				<template v-for="item in items">
					<BaseMenuItem
						:title="item.title"
						:selected="selectedId === item.id"
						@click="select(item.id)"
					/>
				</template>
			</RichMenuPopup>
		</div>
	`
	};

	exports.LocMixin = LocMixin;
	exports.DateSelector = DateSelector;
	exports.Notice = Notice;
	exports.SignDropdownComponent = SignDropdownComponent;
	exports.SignSelector = SignSelector;
	exports.RoundedSmallSelectedItemView = RoundedSmallSelectedItemView;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Main,BX.UI.DatePicker,BX.Sign.V2.B2e,BX,BX.UI.Vue3.Components,BX.UI.IconSet));
//# sourceMappingURL=vue-util.bundle.js.map

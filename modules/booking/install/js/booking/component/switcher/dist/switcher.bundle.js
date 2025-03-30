/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,ui_switcher) {
	'use strict';

	const Switcher = {
	  name: 'UiSwitcher',
	  emits: ['update:model-value', 'toggle', 'checked', 'unchecked', 'lock', 'unlock'],
	  props: {
	    modelValue: {
	      type: Boolean,
	      required: true
	    },
	    id: {
	      type: String,
	      default: undefined
	    },
	    inputName: {
	      type: String,
	      default: ''
	    },
	    size: {
	      type: String,
	      default: ui_switcher.SwitcherSize.small,
	      validator(val) {
	        return Object.values(ui_switcher.SwitcherSize).includes(val);
	      }
	    },
	    color: {
	      type: String,
	      default: ui_switcher.SwitcherColor.primary,
	      validator(val) {
	        return Object.values(ui_switcher.SwitcherColor).includes(val);
	      }
	    },
	    disabled: Boolean,
	    loading: Boolean,
	    hiddenText: Boolean
	  },
	  beforeCreate() {
	    this.switcher = new ui_switcher.Switcher({
	      id: this.id,
	      inputName: this.inputName,
	      checked: this.modelValue,
	      size: this.size,
	      color: this.color,
	      disabled: this.disabled,
	      loading: this.loading,
	      handlers: {
	        toggled: this.toggle,
	        checked: this.checked,
	        unchecked: this.unchecked,
	        lock: this.lock,
	        unlock: this.unlock
	      }
	    });
	  },
	  mounted() {
	    this.switcher.renderTo(this.$refs.switcherWrapper);
	  },
	  methods: {
	    checked() {
	      this.$emit('checked');
	    },
	    unchecked() {
	      this.$emit('unchecked');
	    },
	    lock() {
	      this.$emit('lock');
	    },
	    unlock() {
	      this.$emit('unlock');
	    },
	    toggle() {
	      const checked = this.switcher.isChecked();
	      this.$emit('update:model-value', checked);
	      this.$emit('toggle', checked);
	    },
	    toggleTextVisibility(hidden) {
	      const node = this.switcher.getNode();
	      const elOn = node.querySelector('.ui-switcher-enabled');
	      const elOff = node.querySelector('.ui-switcher-disabled');
	      if (hidden) {
	        main_core.Dom.addClass(elOn, 'switcher-transparent-text');
	        main_core.Dom.addClass(elOff, 'switcher-transparent-text');
	      } else {
	        main_core.Dom.removeClass(elOn, 'switcher-transparent-text');
	        main_core.Dom.removeClass(elOff, 'switcher-transparent-text');
	      }
	    }
	  },
	  watch: {
	    disabled: {
	      handler(disabled) {
	        if (disabled !== this.switcher.isDisabled()) {
	          this.switcher.disable(disabled);
	        }
	      }
	    },
	    loading: {
	      handler(loading) {
	        if (loading !== this.switcher.isLoading()) {
	          this.switcher.setLoading(loading);
	        }
	      }
	    },
	    modelValue: {
	      handler(checked) {
	        if (checked !== this.switcher.checked) {
	          this.switcher.check(checked);
	        }
	      }
	    },
	    hiddenText: {
	      handler(hidden) {
	        this.toggleTextVisibility(hidden);
	      },
	      immediate: true
	    }
	  },
	  template: `
		<div ref="switcherWrapper"></div>
	`
	};

	exports.SwitcherColor = ui_switcher.SwitcherColor;
	exports.SwitcherSize = ui_switcher.SwitcherSize;
	exports.Switcher = Switcher;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX,BX.UI));
//# sourceMappingURL=switcher.bundle.js.map

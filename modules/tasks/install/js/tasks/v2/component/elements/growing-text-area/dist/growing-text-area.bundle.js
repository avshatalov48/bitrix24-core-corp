/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,ui_vue3_directives_hint) {
	'use strict';

	// @vue/component
	const GrowingTextArea = {
	  name: 'GrowingTextArea',
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    modelValue: {
	      type: String,
	      default: ''
	    },
	    placeholder: {
	      type: String,
	      default: ''
	    },
	    fontColor: {
	      type: String,
	      default: 'var(--ui-color-base-1)'
	    },
	    fontSize: {
	      type: Number,
	      default: 21
	    },
	    fontWeight: {
	      type: [Number, String],
	      default: 'inherit'
	    },
	    lineHeight: {
	      type: Number,
	      default: 29
	    },
	    readonly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['update:modelValue', 'input', 'focus', 'blur', 'emptyFocus', 'emptyBlur', 'enterBlur'],
	  data() {
	    return {
	      focus: false,
	      isOverflowing: false
	    };
	  },
	  computed: {
	    isEmpty() {
	      return this.modelValue.trim() === '';
	    },
	    isDisplay() {
	      return this.isOverflowing && !this.isEmpty && !this.focus;
	    },
	    tooltip() {
	      return () => ({
	        text: this.modelValue,
	        interactivity: true,
	        popupOptions: {
	          className: 'b24-growing-text-area-popup',
	          bindElement: this.$el,
	          offsetLeft: 40,
	          maxWidth: 440,
	          maxHeight: 360,
	          angle: {
	            offset: 40
	          },
	          darkMode: false,
	          targetContainer: document.body
	        }
	      });
	    }
	  },
	  mounted() {
	    requestAnimationFrame(() => {
	      if (this.isEmpty) {
	        this.focusToEnd();
	      }
	      void this.adjustTextareaHeight();
	    });
	  },
	  methods: {
	    async adjustTextareaHeight() {
	      const textarea = this.$refs.textarea;
	      if (!textarea) {
	        return;
	      }
	      main_core.Dom.style(textarea, 'height', 'auto');
	      const maxHeight = this.lineHeight * 3;
	      const height = Math.min(textarea.scrollHeight, maxHeight);
	      this.isOverflowing = textarea.scrollHeight > maxHeight;
	      main_core.Dom.style(textarea, 'height', `${height}px`);
	      main_core.Dom.style(textarea, 'maxHeight', `${maxHeight}px`);
	    },
	    focusToEnd() {
	      if (this.readonly) {
	        return;
	      }
	      const textarea = this.$refs.textarea;
	      textarea.focus({
	        preventScroll: true
	      });
	      textarea.setSelectionRange(textarea.value.length, textarea.value.length);
	      this.scrollToBeginning();
	      this.scrollToEnd();
	    },
	    focusTextarea() {
	      if (this.readonly) {
	        return;
	      }
	      if (this.focus) {
	        void this.handleFocus();
	      }
	      this.focus = true;
	      void this.$nextTick(() => {
	        this.$refs.textarea.focus();
	      });
	    },
	    scrollToBeginning() {
	      if (!this.$refs.textarea) {
	        return;
	      }
	      this.$refs.textarea.scrollTop = 0;
	    },
	    scrollToEnd() {
	      const textarea = this.$refs.textarea;
	      textarea.scrollTo({
	        top: textarea.scrollHeight,
	        behavior: 'smooth'
	      });
	    },
	    handleInput(event) {
	      this.$emit('input', event.target.value);
	      void this.adjustTextareaHeight();
	    },
	    handleKeyDown(event) {
	      if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
	        event.stopPropagation();
	        return;
	      }
	      if (event.key === 'Enter') {
	        this.$emit('enterBlur', this.modelValue === '');
	        event.target.blur();
	        event.preventDefault();
	      }
	      if (event.key === 'Escape') {
	        event.target.blur();
	        event.stopPropagation();
	      }
	    },
	    async handleFocus(event) {
	      this.focus = true;
	      await this.adjustTextareaHeight();
	      this.focusToEnd();
	      if (this.modelValue === '') {
	        this.$emit('emptyFocus');
	      }
	      this.$emit('focus', event);
	    },
	    async handleBlur(event) {
	      this.focus = false;
	      if (!this.$refs.textarea) {
	        return;
	      }
	      if (!this.isOverflowing) {
	        await this.adjustTextareaHeight();
	        this.scrollToBeginning();
	      }
	      const value = this.$refs.textarea.value.trim();
	      if (value !== this.modelValue) {
	        this.$emit('update:modelValue', value);
	      }
	      this.$refs.textarea.value = value;
	      if (value === '') {
	        this.$emit('emptyBlur');
	      }
	      this.$emit('blur', event);
	    }
	  },
	  template: `
		<div class="b24-growing-text-area" :class="{ '--readonly': readonly }">
			<div
				v-if="isDisplay"
				v-hint="tooltip"
				class="b24-growing-text-area-display"
				:style="{
					lineHeight: lineHeight + 'px',
					color: fontColor,
					fontSize: fontSize + 'px',
					fontWeight: fontWeight,
				}"
				@click="focusTextarea"
			>
				{{ modelValue }}
			</div>
			<textarea
				v-else
				class="b24-growing-text-area-edit"
				rows="1"
				:value="modelValue"
				:placeholder="placeholder"
				:style="{
					lineHeight: lineHeight + 'px',
					color: fontColor,
					fontSize: fontSize + 'px',
					fontWeight: fontWeight,
				}"
				:readonly="readonly"
				ref="textarea"
				@input="handleInput"
				@keydown="handleKeyDown"
				@focus="handleFocus"
				@blur="handleBlur"
			></textarea>
		</div>
	`
	};

	exports.GrowingTextArea = GrowingTextArea;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX,BX.Vue3.Directives));
//# sourceMappingURL=growing-text-area.bundle.js.map

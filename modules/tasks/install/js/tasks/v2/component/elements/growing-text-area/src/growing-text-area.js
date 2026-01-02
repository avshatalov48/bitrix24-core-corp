import { Dom } from 'main.core';
import { hint, type HintParams } from 'ui.vue3.directives.hint';

import './growing-text-area.css';

// @vue/component
export const GrowingTextArea = {
	name: 'GrowingTextArea',
	directives: { hint },
	props: {
		modelValue: {
			type: String,
			default: '',
		},
		placeholder: {
			type: String,
			default: '',
		},
		fontColor: {
			type: String,
			default: 'var(--ui-color-base-1)',
		},
		fontSize: {
			type: Number,
			default: 21,
		},
		fontWeight: {
			type: [Number, String],
			default: 'inherit',
		},
		lineHeight: {
			type: Number,
			default: 29,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'update:modelValue',
		'input',
		'focus',
		'blur',
		'emptyFocus',
		'emptyBlur',
		'enterBlur',
	],
	data(): Object
	{
		return {
			focus: false,
			isOverflowing: false,
		};
	},
	computed: {
		isEmpty(): boolean
		{
			return this.modelValue.trim() === '';
		},
		isDisplay(): boolean
		{
			return this.isOverflowing && !this.isEmpty && !this.focus;
		},
		tooltip(): Function
		{
			return (): HintParams => ({
				text: this.modelValue,
				interactivity: true,
				popupOptions: {
					className: 'b24-growing-text-area-popup',
					bindElement: this.$el,
					offsetLeft: 40,
					maxWidth: 440,
					maxHeight: 360,
					angle: {
						offset: 40,
					},
					darkMode: false,
					targetContainer: document.body,
				},
			});
		},
	},
	mounted(): void
	{
		requestAnimationFrame(() => {
			if (this.isEmpty)
			{
				this.focusToEnd();
			}

			void this.adjustTextareaHeight();
		});
	},
	methods: {
		async adjustTextareaHeight(): Promise<void>
		{
			const textarea = this.$refs.textarea;
			if (!textarea)
			{
				return;
			}

			Dom.style(textarea, 'height', 'auto');

			const maxHeight = this.lineHeight * 3;
			const height = Math.min(textarea.scrollHeight, maxHeight);

			this.isOverflowing = textarea.scrollHeight > maxHeight;

			Dom.style(textarea, 'height', `${height}px`);
			Dom.style(textarea, 'maxHeight', `${maxHeight}px`);
		},
		focusToEnd(): void
		{
			if (this.readonly)
			{
				return;
			}

			const textarea = this.$refs.textarea;
			textarea.focus({ preventScroll: true });
			textarea.setSelectionRange(textarea.value.length, textarea.value.length);
			this.scrollToBeginning();
			this.scrollToEnd();
		},
		focusTextarea(): void
		{
			if (this.readonly)
			{
				return;
			}

			if (this.focus)
			{
				void this.handleFocus();
			}

			this.focus = true;

			void this.$nextTick(() => {
				this.$refs.textarea.focus();
			});
		},
		scrollToBeginning(): void
		{
			if (!this.$refs.textarea)
			{
				return;
			}

			this.$refs.textarea.scrollTop = 0;
		},
		scrollToEnd(): void
		{
			const textarea = this.$refs.textarea;
			textarea.scrollTo({
				top: textarea.scrollHeight,
				behavior: 'smooth',
			});
		},
		handleInput(event): void
		{
			this.$emit('input', event.target.value);

			void this.adjustTextareaHeight();
		},
		handleKeyDown(event: KeyboardEvent): void
		{
			if (event.key === 'Enter' && (event.ctrlKey || event.metaKey))
			{
				event.stopPropagation();

				return;
			}

			if (event.key === 'Enter')
			{
				this.$emit('enterBlur', this.modelValue === '');

				event.target.blur();
				event.preventDefault();
			}

			if (event.key === 'Escape')
			{
				event.target.blur();
				event.stopPropagation();
			}
		},
		async handleFocus(event?: FocusEvent): Promise<void>
		{
			this.focus = true;

			await this.adjustTextareaHeight();
			this.focusToEnd();

			if (this.modelValue === '')
			{
				this.$emit('emptyFocus');
			}

			this.$emit('focus', event);
		},
		async handleBlur(event: FocusEvent): Promise<void>
		{
			this.focus = false;
			if (!this.$refs.textarea)
			{
				return;
			}

			if (!this.isOverflowing)
			{
				await this.adjustTextareaHeight();
				this.scrollToBeginning();
			}

			const value = this.$refs.textarea.value.trim();
			if (value !== this.modelValue)
			{
				this.$emit('update:modelValue', value);
			}

			this.$refs.textarea.value = value;

			if (value === '')
			{
				this.$emit('emptyBlur');
			}

			this.$emit('blur', event);
		},
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
	`,
};

import { Loc, Runtime } from 'main.core';

const DELTA = 10;

export const Pill = {
	emits: [
		'change',
	],

	props: {
		value: {
			type: Number,
			required: true,
		},
		additionalClass: {
			type: String,
		},
	},

	data(): Object
	{
		return {
			currentValue: this.value,
		};
	},

	methods: {
		onInlineValueKeyDown(event: KeyboardEvent): boolean
		{
			const { key, code, ctrlKey, metaKey } = event;

			if (metaKey || ctrlKey)
			{
				const allowedKeysWithCtrlAndMeta = ['KeyA', 'KeyZ', 'KeyC', 'KeyV', 'KeyR', 'KeyX'];
				if (!allowedKeysWithCtrlAndMeta.includes(code))
				{
					event.preventDefault();

					return false;
				}

				return true;
			}

			const allowedKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Backspace', 'Delete'];
			if (allowedKeys.includes(code))
			{
				return true;
			}

			if (key === ' ' || Number.isNaN(Number(key)))
			{
				event.preventDefault();

				return false;
			}

			return true;
		},
		onInlineValueChange(event: InputEvent): void
		{
			const value = Number(this.$refs.input.innerText);
			this.executeValueChange(value);

			this.$nextTick(() => {
				const range = document.createRange();
				const selection = window.getSelection();

				range.selectNodeContents(this.$refs.input);
				range.collapse(false);

				selection.removeAllRanges();
				selection.addRange(range);
				this.$refs.input.focus();
			});
		},
		onInlineValueBlur(): void
		{
			const onlyNums = this.$refs.input.innerText.replaceAll(/\D/g, '');
			if (onlyNums === '')
			{
				this.executeValueChange(0);
			}
		},
		changeValue(action: string): void
		{
			let value = (action === 'plus' ? this.value + DELTA : this.value - DELTA);

			if (value % DELTA > 0)
			{
				value = Math.ceil(value / DELTA) * 10;
			}

			this.executeValueChange(value);
		},
		executeValueChange(value: number, action: ?string = null): void
		{
			this.currentValue = value;

			this.$refs.input.innerText = (
				value === 0
					? 0
					: this.$refs.input.innerText.replace(/^0+/, '')
			);

			// @todo animation will be redone
			// if (action)
			// {
			// 	const target = (action === 'plus' ? this.$refs.plus : this.$refs.minus);
			// 	const timeoutIdName = (action === 'plus' ? 'timeoutIdPlus' : 'timeoutIdMinus');
			//
			// 	Dom.removeClass(target, '--click');
			// 	setTimeout(() => {
			// 		if (Type.isNumber(this[timeoutIdName]))
			// 		{
			// 			clearTimeout(this[timeoutIdName]);
			// 		}
			// 		Dom.addClass(target, '--click');
			// 		this[timeoutIdName] = setTimeout(() => {
			// 			Dom.removeClass(target, '--click');
			// 		}, 600);
			// 	}, 10);
			// }

			Runtime.debounce(
				() => {
					this.$emit('change', this.currentValue);
				},
				300,
				this,
			)();
		},
	},

	computed: {
		title(): string {
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PILL_TITLE');
		},
		classList(): Array<string>
		{
			return [
				'crm-copilot__call-assessment-pill',
				this.additionalClass,
			];
		},
		minusButtonClassList(): Array
		{
			return [
				'crm-copilot__call-assessment-pill-minus',
				{ '--disabled': this.currentValue <= 0 },
			];
		},
		plusButtonClassList(): Array
		{
			return [
				'crm-copilot__call-assessment-pill-plus',
				{ '--disabled': this.currentValue >= 100 },
			];
		},
	},

	watch: {
		currentValue(value: number): void
		{
			if (value <= 0)
			{
				this.currentValue = 0;
			}

			if (value >= 100)
			{
				this.currentValue = 100;
			}

			if (Number.isNaN(value))
			{
				this.currentValue = 0;
			}

			this.$refs.input.innerText = this.currentValue;
		},
	},

	template: `
		<div :class="classList">
			<div class="crm-copilot__call-assessment-pill-title" v-html="title">
			</div>
			<div class="crm-copilot__call-assessment-pill-control">
				<div
					:class="minusButtonClassList"
					@click="changeValue('minus')"
					ref="minus"
				>
				</div>
				<div class="crm-copilot__call-assessment-pill-value-container">
					<div 
						class="crm-copilot__call-assessment-pill-value"
						contenteditable="true"
						type="text"
						@keydown="onInlineValueKeyDown"
						@input="onInlineValueChange"
						@blur="onInlineValueBlur"
						ref="input"
					>
						{{ currentValue }}
					</div>
					<div class="crm-copilot__call-assessment-pill-percent">
					</div>
				</div>
				<div
					:class="plusButtonClassList"
					@click="changeValue('plus')"
					ref="plus"
				>
				</div>
			</div>
		</div>
	`,
};

import * as Mixins from "../base/components/mixins";

let MixinString = {
	props: ['field', 'itemIndex', 'item', 'readonly', 'buttonClear'],
	mixins: [Mixins.MixinField],
	computed: {
		label ()
		{
			return this.item.label
				? this.item.label
				: this.field.label + (this.itemIndex > 0
					? ' (' + this.itemIndex + ')'
					: ''
			);
		},
		value: {
			get ()
			{
				return this.item.value;
			},
			set (newValue)
			{
				this.item.value = newValue;
				this.item.selected = !!this.item.value;
			}
		},
		inputClasses ()
		{
			let list = [];
			if (this.item.value)
			{
				list.push('b24-form-control-not-empty');
			}
			return list;
		}
	},
	methods: {
		deleteItem ()
		{
			this.field.items.splice(this.itemIndex, 1);
		},
		clearItem ()
		{
			this.value = '';
		},
	},
	watch: {

	}
};

const FieldString = {
	mixins: [MixinString],
	template: `
		<div class="b24-form-control-container b24-form-control-icon-after">
			<input class="b24-form-control"
				:type="field.getInputType()"
				:name="field.getInputName()"
				:class="inputClasses"
				:readonly="readonly || field.isReadonly()"
				:autocomplete="field.getInputAutocomplete()"
				v-model="value"
				@blur="$emit('input-blur', $event)"
				@focus="$emit('input-focus', $event)"
				@click="$emit('input-click', $event)"
				@input="onInput"
				@keydown="$emit('input-key-down', $event)"
			>
			<div class="b24-form-control-label">
				{{ label }} 
				<span class="b24-form-control-required"
					v-show="field.required"
				>*</span>				
			</div>
			<div class="b24-form-icon-after b24-form-icon-remove"
				:title="field.messages.get('fieldRemove')"
				v-if="itemIndex > 0"
				@click="deleteItem"
			></div>
			<div class="b24-form-icon-after b24-form-icon-remove"
				:title="buttonClear"
				v-if="buttonClear && itemIndex === 0 && value"
				@click="clearItem"
			></div>
			<field-item-alert
				v-bind:field="field"
				v-bind:item="item"
			></field-item-alert>
		</div>
	`,
	methods: {
		onInput()
		{
			let value = this.field.normalize(this.value);
			value = this.field.format(value);
			if (this.value !== value)
			{
				this.value = value;
			}
		},
	}
};

export {
	MixinString,
	FieldString,
}
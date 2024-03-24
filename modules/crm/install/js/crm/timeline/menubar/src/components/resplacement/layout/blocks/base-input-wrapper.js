import EventType from '../../enums/event-type';
import { ITEM_ACTION_EVENT } from '../../layout';
import Input from './inputs/input-text';
import Select from './inputs/select';
import Textarea from './inputs/textarea';

export default {
	inheritAttrs: false,
	components: {
		Input,
		Select,
		Textarea,
	},
	props: {
		id: String,
		title: String,
		errorText: String,
		value: String,
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data(): Object
	{
		return {
			currentValue: this.getInitialValue(),
		};
	},
	computed: {
		className(): Array
		{
			return [
				'ui-ctl-container',
				'ui-ctl-w100',
				this.hasError ? 'ui-ctl-warning' : '',
			];
		},
		hasTitle(): boolean
		{
			return Boolean(this.title);
		},
		hasError(): boolean
		{
			return Boolean(this.errorText);
		},
		componentName(): String
		{
			throw new Error('Must be overridden');
		},
		componentProps(): Object
		{
			throw new Error('Must be overridden');
		},
	},
	watch: {
		value(newValue): void
		{
			this.currentValue = newValue;
		},
	},
	methods: {
		getInitialValue(): string
		{
			return this.value;
		},
		onChange(newValue): void
		{
			this.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
				event: EventType.VALUE_CHANGED_EVENT,
				value: {
					id: this.id,
					value: newValue,
				},
			});
		},
	},
	template: `
		<div :class="className">
			<div class="ui-ctl-top" v-if="hasTitle">
				<div class="ui-ctl-title">{{ title }}</div>
			</div>
			<component :is="componentName" v-bind="componentProps" :disabled="disabled" v-model="currentValue" @update:modelValue="onChange"></component>
			<div v-if="hasError" class="ui-ctl-bottom">{{ errorText }}</div>
		</div>
	`,
};

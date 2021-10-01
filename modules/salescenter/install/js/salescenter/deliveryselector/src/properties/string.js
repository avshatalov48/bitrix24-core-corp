export default {
	props: {
		name: {type: String, required: true},
		initValue: {required: false},
		settings: {required: false},
		options: {required: false},
	},
	created()
	{
		this.value = this.initValue;
	},
	data()
	{
		return {
			value: null,
		}
	},
	methods: {
		onInput(event)
		{
			this.value = event.target.value;

			this.$emit('change', this.value);
		}
	},
	computed: {
		isMultiline()
		{
			return (this.settings && this.settings.MULTILINE === 'Y');
		}
	},
	template: `
		<div class="ui-ctl ui-ctl-w100">
			<textarea v-if="isMultiline" @input="onInput" :name="name" class="ui-ctl-element salescenter-delivery-comment-textarea" rows="1">{{value}}</textarea>
			<input v-else @input="onInput" type="text" :name="name" :value="value" class="ui-ctl-element ui-ctl-textbox" />
		</div>					
	`
};

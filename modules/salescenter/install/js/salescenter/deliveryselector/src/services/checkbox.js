export default {
	props: {
		name: {required: false},
		initValue: {required: false},
	},
	created()
	{
		this.value = this.initValue;
	},
	data()
	{
		return {
			value: null
		}
	},
	methods: {
		onChange(event)
		{
			this.value =  event.target.checked ? 'Y' : '';
			this.$emit('change', this.value);
		}
	},
	template: `
		<label class="salescenter-delivery-selector salescenter-delivery-selector--hover salescenter-delivery-selector--checkbox">
			<input @change="onChange" :checked="value == 'Y' ? true : false" type="checkbox" value="Y" />
			<span class="salescenter-delivery-selector-text">{{name}}</span>
		</label>
	`
};

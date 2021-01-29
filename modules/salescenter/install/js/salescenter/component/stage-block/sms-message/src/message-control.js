const MessageControl = {
	props: {
		editable: {
			type: Boolean,
			required: true
		}
	},
	computed:
	{
		classObject()
		{
			return {
				'salescenter-app-payment-by-sms-item-container-sms-content-edit': true,
				'salescenter-app-payment-by-sms-item-container-sms-content-save': this.editable
			}
		}
	},
	methods:
	{
		onSave(e)
		{
			this.$emit('control-on-save', e);
		},
	},
	template: `
		<div :class="classObject" @click="onSave($event)"></div>
	`
};

export {
	MessageControl
}
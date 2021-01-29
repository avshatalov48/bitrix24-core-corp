const SelectItem = {
	props: {
		name: {
			type: String,
			required: true
		}
	},
	template: `
			<div class="salescenter-app-payment-by-sms-item-container-select-item" id="stageOnOrderPaid">{{name}}</div>
`

};
export {
	SelectItem
}
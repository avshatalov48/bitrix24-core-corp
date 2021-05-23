const TitleName = {
	props: {
		name: {
			type: String,
			required: true,
		}
	},
	template: `
		<span class="salescenter-app-payment-by-sms-item-container-payment-title-item-text">{{ name }}</span>
	`
};

export {
	TitleName
}
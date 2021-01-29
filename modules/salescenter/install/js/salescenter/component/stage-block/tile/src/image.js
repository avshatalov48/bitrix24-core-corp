const Image = {
	props: {
		src: {
			type: String,
			required: true
		}
	},
	computed:
		{
			classes()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-img':true
				}
			}
		},
	template: `
			<img :class="classes" :src="src">
`

};
export {
	Image
}
const Background  ={
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
					'salescenter-app-payment-by-sms-item-container-payment-item-img-del':true
				}
			}
		},
	methods:
		{
			getUrl()
			{
				return encodeURI(this.src)
			}
		},
	template: `
			<div :class="classes">
				<img :src="getUrl()">
			</div>
`
};

export {
	Background
}
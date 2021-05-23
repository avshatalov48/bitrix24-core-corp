const TitleIcon = {
	props: {
		icon: {
			type: String,
			required: true,
		}
	},
	template: `
		<div class="salescenter-delivery-method-image">
			<img :src="icon">
		</div>
	`
};

export {
	TitleIcon
}
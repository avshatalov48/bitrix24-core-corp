const Title = {
	props: {
		collapsible: {
			type: Boolean,
			default: false,
		}
	},
	methods: {
		onTitleClicked()
		{
			this.$emit('on-title-clicked');
		},
	},
	template: `
		<div class="salescenter-app-payment-by-sms-item-title" :class="{ 'salescenter-app-payment-by-sms-item-title-collapsible': collapsible }" v-on:click="onTitleClicked">
			<div class="salescenter-app-payment-by-sms-item-title-text">
				<slot></slot>
			</div>
			<slot name="item-hint"></slot>
			<slot name="title-items"></slot>
			<slot name="title-name"></slot>
		</div>
	`
};

export {
	Title
}
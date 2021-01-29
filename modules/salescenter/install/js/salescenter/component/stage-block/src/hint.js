const Hint = {
	methods:
	{
		onHint(e)
		{
			this.$emit('on-hint', e);
		}

	},
	template: `
		<div @click="onHint" class="salescenter-app-payment-by-sms-item-title-info">
			<slot></slot>
		</div>
	`
};

export {
	Hint
}
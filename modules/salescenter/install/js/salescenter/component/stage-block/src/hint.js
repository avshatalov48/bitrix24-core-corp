const Hint = {
	methods:
	{
		onHint(e)
		{
			this.$emit('on-hint', e);
		}

	},
	computed: {
		hasContentSlot() {
			try
			{
				return (this.$slots['default'][0].text !== '');
			}
			catch (err)
			{
				return false;
			}
		}
	},
	template: `
		<div v-if="hasContentSlot" @click="onHint" class="salescenter-app-payment-by-sms-item-title-info">
			<slot></slot>
		</div>
	`
};

export {
	Hint
}
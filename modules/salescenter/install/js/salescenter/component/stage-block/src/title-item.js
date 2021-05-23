const TitleItem = {
	props: {
		item: {
			type: Object,
			required: true,
		}
	},
	methods:
	{
		onTitleItem(item)
		{
			this.$emit('on-title-item', item);
		}

	},
	template: `
		<span class="salescenter-app-payment-by-sms-item-container-payment-title-item-text"
			v-on:click.stop.prevent="onTitleItem(item)"
		>{{ item.name }}</span>
	`
};

export {
	TitleItem
}
const Hint = {
	methods:{
		onMouseenter(e)
		{
			this.$emit('tile-hint-on-mouseenter', {data: {event:e}});
		},
		onMouseleave()
		{
			this.$emit('tile-hint-on-mouseleave');
		},
	},
	computed:
		{
			classes()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-info':true
				}
			}
		},
	template: `
		<div :class="classes" 
			v-on:mouseenter="onMouseenter($event)" 
			v-on:mouseleave="onMouseleave"
			/>
`

};
export {
	Hint
}
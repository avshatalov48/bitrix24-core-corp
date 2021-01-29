import {Label} from "./label";

const TileLabelPlus = {
	props: {
		name: {
			type: String,
			required: true
		}
	},
	components:
		{
			'tile-label-block'	:	Label
		},
	computed:
		{
			classConteiner()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-added': true,
					'salescenter-app-payment-by-sms-item-container-payment-item': true
				}
			},
			classContent()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-contet':true
				}
			},
			classLabel()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-added-text':true
				}
			}
		},
	methods:
		{
			onClick()
			{
				this.$emit('tile-label-plus-on-click');
			},
		},
	template: `
		<div @click="onClick()" :class="classConteiner">
			<div :class="classContent"> 
				<tile-label-block 
					:class="classLabel" 
					:name="name"
				/>
			</div>
		</div>
	`
};
export {
	TileLabelPlus
}
import {Hint} from "./hint";
import {Background} from "./background";

const TileHintBackground = {
	props: {
		name: {
			type: String,
			required: true
		},
		src: {
			type: String,
			required: true
		}
	},
	components:
		{
			'tile-hint-block'		:	Hint,
			'tile-background-block'	:	Background
		},
	computed:
		{
			classConteiner()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item': true
				}
			},
			classContent()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-contet':true
				}
			}
		},
	methods:
		{
			onClick()
			{
				this.$emit('tile-hint-bg-on-click');
			},

			showSmsMessagePopupHint(e)
			{
				this.$emit('tile-label-bg-hint-on-mouseenter', e);
			},
			hidePopupHint()
			{
				this.$emit('tile-label-bg-hint-on-mouseleave');
			},
		},
	template: `
		<div @click="onClick()" :class="classConteiner">
			<div :class="classContent">
				<tile-hint-block
					v-on:tile-hint-on-mouseenter="showSmsMessagePopupHint"
					v-on:tile-hint-on-mouseleave="hidePopupHint"
				/>
				<tile-background-block :src="src"/>
			</div>
		</div>
	`
};
export {
	TileHintBackground
}
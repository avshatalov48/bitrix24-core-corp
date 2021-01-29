import {Hint} from "./hint";
import {Image} from "./image";

const TileHintImg = {
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
			'tile-hint-block'	:	Hint,
			'tile-img-block'	:	Image
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
				this.$emit('tile-hint-img-on-click');
			},

			showSmsMessagePopupHint(e)
			{
				this.$emit('tile-label-img-hint-on-mouseenter', e);
			},
			hidePopupHint()
			{
				this.$emit('tile-label-img-hint-on-mouseleave');
			},
		},
	template: `
		<div @click="onClick()" :class="classConteiner">
			<div :class="classContent">
				<tile-hint-block
					v-on:tile-hint-on-mouseenter="showSmsMessagePopupHint"
					v-on:tile-hint-on-mouseleave="hidePopupHint"
				/>
				<tile-img-block :src="src"/>
			</div>
		</div>
	`
};
export {
	TileHintImg
}
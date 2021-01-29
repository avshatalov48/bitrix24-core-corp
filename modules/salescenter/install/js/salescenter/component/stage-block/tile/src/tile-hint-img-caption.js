import {Hint} from "./hint";
import {Image} from "./image";
import {Label} from "./label";

const TileHintImgCaption = {
	props: {
		name: {
			type: String,
			required: true
		},
		src: {
			type: String,
			required: true
		},
		caption: {
			type: String,
			required: true
		}
	},
	components:
		{
			'tile-hint-block'	:	Hint,
			'tile-img-block'	:	Image,
			'tile-label-block'	:	Label
		},
	computed:
		{
			hasImg()
			{
				return  this.src.length > 0;
			},
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
			},
			classTileText()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-payment-item-title-text':true
				}
			}
		},
	methods:
		{
			onClick()
			{
				this.$emit('tile-hint-img-label-on-click');
			},

			showSmsMessagePopupHint(e)
			{
				this.$emit('tile-hint-img-label-on-mouseenter', e);
			},
			hidePopupHint()
			{
				this.$emit('tile-hint-img-label-on-mouseleave');
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
				<tile-label-block :name="caption" :class="classTileText"/> 
			</div>
		</div>
	`
};
export {
	TileHintImgCaption
}
import {Hint} from "./hint";
import {Label} from "./label";
import {Background} from "./background";

const TileHintBackgroundCaption = {
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
			'tile-hint-block'		:	Hint,
			'tile-label-block'		:	Label,
			'tile-background-block'	:	Background
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
				this.$emit('tile-hint-bg-label-on-click');
			},

			showSmsMessagePopupHint(e)
			{
				this.$emit('tile-hint-bg-label-on-mouseenter', e);
			},
			hidePopupHint()
			{
				this.$emit('tile-hint-bg-label-on-mouseleave');
			},
		},
	template: `
		<div @click="onClick()" :class="classConteiner">
			<div :class="classContent">
				<tile-hint-block
					v-on:tile-hint-on-mouseenter="showSmsMessagePopupHint"
					v-on:tile-hint-on-mouseleave="hidePopupHint"
				/>
				<tile-background-block :src="src" class="salescenter-app-payment-by-sms-item-container-payment-item-img-del-title"/>
				<tile-label-block :name="caption" :class="classTileText"/> 
			</div>
		</div>
	`
};
export {
	TileHintBackgroundCaption
}
import {Hint} from "./hint";
import {Image} from "./image";
import {Label} from "./label";

const TileLabelImgHint = {
	props: {
		name: {
			type: String,
			required: true
		},
		title: {
			type: String
		},
		src: {
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
					'salescenter-app-payment-by-sms-item-container-payment-item-added-text':true
				}
			}
		},
	methods:
		{
			onClick()
			{
				this.$emit('tile-label-img-hint-on-click');
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
				<template v-if="hasImg">
					<tile-hint-block
						v-on:tile-hint-on-mouseenter="showSmsMessagePopupHint"
						v-on:tile-hint-on-mouseleave="hidePopupHint"
					/>
					<tile-img-block :src="src"/>
				</template>
				<template v-else>
					<tile-label-block :name="name" :class="classTileText"/> 
				</template>
			</div>
		</div>
	`

};
export {
	TileLabelImgHint
}
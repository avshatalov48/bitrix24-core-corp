import {Loc} 								from 'main.core';
import {BlockNumberTitleHint as Block} 		from 'salescenter.component.stage-block';
import {Uninstalled as TileUnInstalled} 	from "./tile-collection/uninstalled";
import {Installed as TileInstalled} 		from "./tile-collection/installed";
import {StageMixin} 						from "./stage-mixin";

const PaySystem = {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: String,
			required: true
		},
		tiles: {
			type: Array,
			required: true
		},
		installed: {
			type: Boolean,
			required: true
		}
	},
	mixins:[StageMixin],
	components:
	{
		'stage-block-item'					:	Block,
		'tile-collection-installed-block'	:	TileInstalled,
		'tile-collection-uninstalled-block'	:	TileUnInstalled
	},
	methods:
	{
		onItemHint(e)
		{
			BX.Salescenter.Manager.openHowToConfigPaySystem(e);
		},
	},
	computed:
	{
		statusClass()
		{
			return {
				'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false
			}
		},
		title()
		{
			return this.installed === true
				? Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE')
				: Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE');
		}
	},

	template: `
		<stage-block-item
			:counter="counter"
			:class="[statusClassMixin, statusClass]"			
			:checked="counterCheckedMixin"
			v-on:on-item-hint="onItemHint"
		>
			<template v-slot:block-title-title>{{title}}</template>
			<template v-slot:block-hint-title>${Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<tile-collection-installed-block 	:tiles="tiles" v-on:on-tile-slider-close="onSliderClose" v-if="installed"/>
					<tile-collection-uninstalled-block 	:tiles="tiles" v-on:on-tile-slider-close="onSliderClose" v-else />
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	PaySystem
}
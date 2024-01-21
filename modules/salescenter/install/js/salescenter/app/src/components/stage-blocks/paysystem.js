import {Loc} 								from 'main.core';
import {Block} 			from 'salescenter.component.stage-block';
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
			type: Number,
			required: true
		},
		tiles: {
			type: Array,
			required: true
		},
		installed: {
			type: Boolean,
			required: true
		},
		titleItems: {
			type: Array
		},
		initialCollapseState: {
			type: Boolean,
			required: true
		},
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
			BX.Salescenter.Manager.openHowToConfigDefaultPaySystem(e);
		},
		saveCollapsedOption(option)
		{
			this.$emit('on-save-collapsed-option', 'pay_system', option);
		},

	},
	computed:
	{
		configForBlock()
		{
			return {
				counter: this.counter,
				titleItems: this.installed ? this.titleItems : [],
				installed: this.installed,
				collapsible: true,
				checked: this.counterCheckedMixin,
				showHint: !this.installed,
				initialCollapseState: this.initialCollapseState,
			}
		},
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
		},
	},

	template: `
		<stage-block-item
			:class="[statusClassMixin, statusClass]"
			:config="configForBlock"
			@on-item-hint.stop.prevent="onItemHint"
			@on-tile-slider-close="onSliderClose"
			@on-adjust-collapsed="saveCollapsedOption"
		>
			<template v-slot:block-title-title>{{title}}</template>
			<template v-slot:block-hint-title>
				${Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE')}
			</template>
			<template v-slot:block-container>
				<div
					v-if="!installed"
					class="salescenter-app-explanation"
				>
					<div class="salescenter-app-explanation-img"></div>
					<div class="salescenter-app-explanation-area">
						<div class="salescenter-app-explanation-text">
							${Loc.getMessage('SALESCENTER_TERMINAL_PAYMENT_SYSTEM_SETUP_HINT')}
						</div>
					</div>
				</div>
				<div :class="containerClassMixin">
					<tile-collection-uninstalled-block 	:tiles="tiles" v-if="!installed" v-on:on-tile-slider-close="onSliderClose"/>
					<tile-collection-installed-block :tiles="tiles" v-on:on-tile-slider-close="onSliderClose" v-else />
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	PaySystem
}

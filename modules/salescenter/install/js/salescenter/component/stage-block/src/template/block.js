import {Hint} from "../hint";
import {Title} from "../title";
import {TitleItem} from "../title-item";
import {TitleName} from "../title-name";
import {CounterNumber} from "../counter-number";
import {Counter} from "../counter";
import {AppSlider, EventTypes} from "salescenter.marketplace";

const Block = {
	props: {
		config: {
			type: Object,
			required: true,
		}
	},
	components: {
		'block-counter': Counter,
		'block-title': Title,
		'block-title-item': TitleItem,
		'block-title-name': TitleName,
		'block-counter-number': CounterNumber,
		'block-hint': Hint,
	},
	data: function ()
	{
		return {
			containerHeight: null,
			collapse: false,
			blockContainer: null,
		}
	},
	computed: {
		titleItems()
		{
			return this.config.items.slice(0, this.TITLE_ITEMS_LIMIT);
		},
		displayClass()
		{
			return {
				'salescenter-app-payment-by-sms-item-hide': this.collapse,
				'salescenter-app-payment-by-sms-item-show': !this.collapse,
			}
		},
		hintClassModifier()
		{
			return this.config.hintClassModifier || '';
		},
		bodyStyle()
		{
			return {
				maxHeight: this.containerHeight ? this.containerHeight + 'px' : null,
			}
		},
		hasTitleSlot() {
			return !!this.$slots['block-title-title']
		}
	},
	methods: {
		onHint(e)
		{
			this.$emit('on-item-hint', e);
		},
		onTitleClicked()
		{
			this.$emit('on-title-clicked');
			if (this.config.collapsible)
			{
				this.adjustCollapsed();
			}
		},
		adjustCollapsed()
		{
			if(this.collapse)
			{
				this.collapse = false;
			}
			else
			{
				this.collapse = true;
			}
			let collapseOption = this.collapse ? 'Y' : 'N';
			this.$emit('on-adjust-collapsed', collapseOption);
		},
		openSliderForTitleItem(titleItem)
		{
			let slider = new AppSlider();
			let link = titleItem.link;

			slider.openAppLocalLink(link);
			slider.subscribe(EventTypes.AppSliderSliderClose,
				(e) => this.$emit('on-tile-slider-close', {data: e.data})
			);
		}
	},
	mounted: function() {
		this.collapse = this.config.initialCollapseState;
	},
	template: `
		<div :class="displayClass">
			<block-counter-number :value="config.counter" :checked="config.checked" v-if="config.counter" />
			<block-counter v-else />
			<block-title @on-title-clicked="onTitleClicked" :collapsible="config.collapsible" v-if="hasTitleSlot">
				<template v-slot:default>
					<slot name="block-title-title"></slot>
				</template>
				<template v-slot:item-hint v-if="config.showHint">
					<block-hint v-on:on-hint.stop.prevent="onHint" :class="hintClassModifier">
						<template v-slot:default>
							<slot name="block-hint-title"></slot>
						</template>
					</block-hint>
				</template>
				<template v-slot:title-items v-if="collapse">
					<div class="salescenter-app-payment-by-sms-item-container-payment-title-item-wrapper">
						<block-title-item
							v-for="(item, index) in config.titleItems"
							v-on:on-title-item="openSliderForTitleItem(item)"
							:item="item">
						</block-title-item>
					</div>
				</template>
				<template v-slot:title-name v-if="collapse && config.titleName">
					<div class="salescenter-app-payment-by-sms-item-container-payment-title-item-wrapper">
						<block-title-name
							:name="config.titleName">
						</block-title-name>
					</div>
				</template>
			</block-title>
			<div :class="{'salescenter-app-payment-collapsible-block': config.collapsible, 'salescenter-app-payment-collapsible-block-collapsed': collapse}" v-bind:style="[config.collapsible ? bodyStyle : null]" ref="containerWrapper">
				<slot name="block-container"></slot>
			</div>
		</div>
	`,
}

export {
	Block
}
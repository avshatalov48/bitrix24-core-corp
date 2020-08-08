import {config} from './config';
import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import {Factory} from './timeline/factory';
import {Cash}  from './timeline/cash'
import {Sent} from "./timeline/sent";
import {CheckSent} from "./timeline/check-sent";

import './bx-salescenter-app-add-payment-by-sms-item';
import {TimeLineListBlock} from "./components/timeline-list";
import {MixinTemplatesType} from "./components/templates-type-mixin";

Vue.component(config.templateAddPaymentBySms,
{
	/**
	 * @emits 'send' {e: object}
	 */

	props: ['isAllowedSubmitButton'],

	mixins:[MixinTemplatesType],

	components: {
		'timeline-list-block': TimeLineListBlock,
	},

	data()
	{
		let steps = [
			{
				sort: 100,
				type: 'BEE_SEND_SMS',
				title: this.$root.$app.options.contactBlock.title,
				stage: 'complete',
				itemData: this.$root.$app.options.contactBlock,
			},
			{
				sort: 200,
				type: 'SELECT_PRODUCTS',
				title: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE'),
				stage: (
					this.$root.$app.options.basket
					&& this.$root.$app.options.basket.length > 0
				)
					? 'complete'
					: 'current'
			},
			{
				sort: 300,
				type: 'PAY_SYSTEM',
				title: this.$root.$app.options.paySystemList.isSet
					? Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE')
					: Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE'),
				stage: this.$root.$app.options.paySystemList.isSet ? 'complete' :'disabled',
				set: this.$root.$app.options.paySystemList.isSet,
				itemData: this.$root.$app.options.paySystemList,
			}
		];

		if (this.$root.$app.options.isAutomationAvailable)
		{
			steps.push(
				{
					sort: 500,
					type: 'AUTOMATION',
					title: Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE'),
					stage: 'complete',
					itemData: this.$root.$app.options.dealStageList
				}
			);
		}

		if (this.$root.$app.options.cashboxList.hasOwnProperty('items'))
		{
			steps.push(
				{
					sort: 400,
					type: 'CASHBOX',
					title: this.$root.$app.options.cashboxList.isSet
						? Loc.getMessage('SALESCENTER_CASHBOX_SET_BLOCK_TITLE')
						: Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_TITLE'),
					stage: this.$root.$app.options.cashboxList.isSet ? 'complete' :'disabled',
					set: this.$root.$app.options.cashboxList.isSet,
					itemData: this.$root.$app.options.cashboxList,
				}
			);
		}

		if (this.$root.$app.options.deliveryList.hasOwnProperty('items'))
		{
			steps.push(
				{
					sort: 600,
					type: 'DELIVERY',
					title: Loc.getMessage('SALESCENTER_DELIVERY_BLOCK_TITLE'),
					stage: 'disabled',
					itemData: this.$root.$app.options.deliveryList
				}
			);
		}

		steps.sort((a, b) => a.sort - b.sort);

		return {
			title: Loc.getMessage('SALESCENTER_LEFT_CREATE_LINK_AND_SEND'),
			data: this.$root.$app,
			steps: steps,
			timeline:{
				items:[{
					sum: '',
					url: '',
					type: '',
					title: '',
					content: '',
					currency: '',
					disabled: '',
				}]
			}
		}
	},

	computed:
	{
		config: () => config,
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_TIMELINE_');
		},

		iMessageAvailable()
		{
			return this.$root.$app.isApplePayAvailable && this.$root.$app.connector === 'imessage';
		},
	},

	created()
	{
		this.timelineItemsInit()
	},

	methods:
	{
		timelineItemsInit()
		{
			if (BX.type.isObject(this.$root.$app.options.timeline) && Object.values(this.$root.$app.options.timeline).length > 0)
			{
				this.timeline.items = [];
				Object.values(this.$root.$app.options.timeline).forEach(
					options => this.timeline.items.push(Factory.create(options)));
			}
		},
		send(e)
		{
			this.$emit('send', e)
		},
		initTileGrid()
		{
			console.log("initTileGrid");
		}
	},

	template: `
	<div class="salescenter-app-payment-by-sms">
		<div class="salescenter-app-payment-by-sms-title">{{ title }}</div>
		<component 
			:is="config.templateAddPaymentBySmsItem"
			v-for="(step, index) in steps"
			:index="index + 1"
			:data="step"
			:complete="step.complete"
			:current="step.current"></component>
			<div :class="{
				'salescenter-app-payment-by-sms-item-disabled': !this.isAllowedSubmitButton
				}" class="salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send salescenter-app-payment-by-sms-item">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-rounder"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-number"></div>
				</div>
				<div class="salescenter-app-payment-by-sms-item-container">
					<div class="salescenter-app-payment-by-sms-item-container-payment">
						<div class="salescenter-app-payment-by-sms-item-container-payment-inline">
							<div class="ui-btn ui-btn-lg ui-btn-success ui-btn-round" v-on:click="send($event)" v-if="editable">${Loc.getMessage('SALESCENTER_SEND')}</div>
							<div class="ui-btn ui-btn-lg ui-btn-success ui-btn-round" v-on:click="send($event)" v-else >${Loc.getMessage('SALESCENTER_RESEND')}</div>
							<div v-on:click="BX.Salescenter.Manager.openWhatClientSee(event)" class="salescenter-app-add-item-link">${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE')}</div>
						</div>
					</div>
				</div>
			</div>
			<div v-if="iMessageAvailable" class="salescenter-app-payment-container">
				<label class="ui-ctl ui-ctl-checkbox">
					<input type="checkbox" class="ui-ctl-element" @change="handleIMessagePayment($event)">
					<div class="ui-ctl-label-text">{{localize.SALESCENTER_IMESSAGE_PAYMENT}}</div>
				</label>
			</div>
		<component :is="'timeline-list-block'" :items="timeline.items"/>
	</div>
	`
});

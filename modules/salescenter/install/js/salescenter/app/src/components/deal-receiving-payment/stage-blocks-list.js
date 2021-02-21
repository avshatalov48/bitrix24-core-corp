import {
	StatusTypes as Status
} 						from 'salescenter.component.stage-block';
import * as TimeLineItem
						from 'salescenter.timeline';
import {MixinTemplatesType}
						from "./templates-type-mixin";

import * as Tile 		from 'salescenter.tile';
import {Send} 			from "./stage-blocks/send";
import {Cashbox} 		from "./stage-blocks/cashbox";
import {Product} 		from "./stage-blocks/product";
import {DeliveryVuex} 	from "./stage-blocks/delivery-vuex";
import {PaySystem} 		from "./stage-blocks/paysystem";
import {SmsMessage} 	from "./stage-blocks/sms-message";
import {Automation} 	from "./stage-blocks/automation";
import {StageMixin} 	from "./stage-blocks/stage-mixin";
import {TimeLine} 		from "./stage-blocks/timeline";


const StageBlocksList = {
	props:{
		sendAllowed: {
			type: Boolean,
			required: true
		},
	},
	data()
	{
		let stages =  {
			message:{
				status: 			Status.complete,
				items: 				this.$root.$app.options.contactBlock.smsSenders,
				manager:			this.$root.$app.options.contactBlock.manager,
				phone:				this.$root.$app.options.contactPhone,
				senderSettingsUrl:	this.$root.$app.urlSettingsSmsSenders,
				editorTemplate:		this.$root.$app.sendingMethodDesc.text,
				editorUrl:			this.$root.$app.orderPublicUrl
			},
			product:{
				status:				this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0
									?	Status.complete
									:	Status.current
			},
			paysystem:{
				status: 			this.$root.$app.options.paySystemList.isSet
									?	Status.complete
									:	Status.disabled,
				tiles:			this.getTileCollection(
									this.$root.$app.options.paySystemList.items),
				installed:			this.$root.$app.options.paySystemList.isSet,
			},
			cashbox:{},
			delivery:{
				status: 			this.$root.$app.options.deliveryList.isInstalled
									?	Status.complete
									:	Status.disabled,
				tiles:			this.getTileCollection(
									this.$root.$app.options.deliveryList.items),
				installed:			this.$root.$app.options.deliveryList.isInstalled,
			}
		};

		if (this.$root.$app.options.cashboxList.hasOwnProperty('items'))
		{
			stages.cashbox = {
				status: 			this.$root.$app.options.cashboxList.isSet
									?	Status.complete
									:	Status.disabled,
				tiles:			this.getTileCollection(
									this.$root.$app.options.cashboxList.items),
				installed:			this.$root.$app.options.cashboxList.isSet,
			};
		}

		if (this.$root.$app.options.isAutomationAvailable)
		{
			stages.automation = {
				status:				Status.complete,
				items:				this.$root.$app.options.dealStageList
			};
		}

		if (BX.type.isObject(this.$root.$app.options.timeline)
			&& Object.values(this.$root.$app.options.timeline).length > 0)
		{
			stages.timeline = {
				items:			this.getTimelineCollection(
									this.$root.$app.options.timeline)
			}
		}

		return {
			stages: stages,
		}
	},
	components: {
		'send-block'			: Send,
		'cashbox-block'			: Cashbox,
		'product-block'			: Product,
		'delivery-block'		: DeliveryVuex,
		'paysystem-block'		: PaySystem,
		'automation-block'		: Automation,
		'sms-message-block'		: SmsMessage,
		'timeline-block'		: TimeLine
	},
	mixins:[StageMixin, MixinTemplatesType],
	computed:
		{
			hasStageTimeLine()
			{
				return this.stages.timeline.hasOwnProperty('items');
			},

			hasStageAutomation()
			{
				return this.stages.automation.hasOwnProperty('items');
			},
			hasStageCashBox()
			{
				return this.stages.cashbox.hasOwnProperty('tiles');
			},
			editableMixin()
			{
				return this.editable === false;
			}
		},
	methods:
		{
			initCounter()
			{
				this.counter = 1;
			},

			getTimelineCollection(items)
			{
				let list = [];

				Object.values(items).forEach(
					options => list.push(TimeLineItem.Factory.create(options)));

				return list;
			},

			getTileCollection(items)
			{
				let tiles = [];
				Object.values(items).forEach(
					options => tiles.push(Tile.Factory.create(options)));

				return tiles;
			},

			stageRefresh(e, type)
			{
				BX.ajax.runComponentAction(
					"bitrix:salescenter.app",
					"getAjaxData",
					{
						mode: "class",
						data: {
							type: type,
						}
					}
				).then(function(response) {
					if (response.data)
					{
						this.refreshTilesByType(response.data, type);
					}
				}.bind(this));
			},

			refreshTilesByType(data, type)
			{
				if(type === 'PAY_SYSTEM')
				{
					this.stages.paysystem.status = 		data.isSet
														?	Status.complete
														:	Status.disabled;
					this.stages.paysystem.tiles = 		this.getTileCollection(data.items);
					this.stages.paysystem.installed = 	data.isSet;
				}
				else if(type === 'CASHBOX')
				{
					this.stages.cashbox.status = 		data.isSet
														?	Status.complete
														:	Status.disabled;
					this.stages.cashbox.tiles = 		this.getTileCollection(data.items);
					this.stages.cashbox.installed = 	data.isSet;
				}
				else if(type === 'DELIVERY')
				{
					this.stages.delivery.status = 		data.isSet
														?	Status.complete
														:	Status.disabled;
					this.stages.delivery.tiles = 		this.getTileCollection(data.items);
					this.stages.delivery.installed = 	data.isInstalled;
				}
			},

			onSend(event)
			{
				this.$emit('stage-block-send-on-send', event)
			},

			changeProvider(value)
			{
				this.$root.$app.sendingMethodDesc.provider = value;
			}
		},
	created()
	{
		this.initCounter();
	},
	beforeUpdate()
	{
		this.initCounter();
	},
	template: `
		<div>
			<sms-message-block 						v-on:stage-block-sms-send-on-change-provider="changeProvider"
				:counter=			"counter++"
				:status=			"stages.message.status"
				:items=				"stages.message.items"
				:manager=			"stages.message.manager"
				:phone=				"stages.message.phone"
				:senderSettingsUrl=	"stages.message.senderSettingsUrl"
				:editorTemplate=	"stages.message.editorTemplate"
				:editorUrl=			"stages.message.editorUrl"
			/>
				
			<product-block 
				:counter=	"counter++"
				:status= 	"stages.product.status"				
			/>
			
			<paysystem-block						v-on:on-stage-tile-collection-slider-close="stageRefresh($event, 'PAY_SYSTEM')"
				:counter=	"counter++"
				:status=  	"stages.paysystem.status"
				:tiles=  	"stages.paysystem.tiles"
				:installed=	"stages.paysystem.installed"	
			/>
				
			<cashbox-block 	v-if="hasStageCashBox"	v-on:on-stage-tile-collection-slider-close="stageRefresh($event, 'CASHBOX')"
				:counter=	"counter++"
				:status=	"stages.cashbox.status"
				:tiles=		"stages.cashbox.tiles"
				:installed=	"stages.cashbox.installed"				
			/>	
			
			<automation-block v-if="hasStageAutomation"
				:counter=	"counter++"
				:status=	"stages.automation.status"
				:items=		"stages.automation.items"
			/>
			
			<delivery-block							v-on:on-stage-tile-collection-slider-close="stageRefresh($event, 'DELIVERY')"
				:counter=	"counter++"
				:status=  	"stages.delivery.status"
				:tiles=  	"stages.delivery.tiles"
				:installed=	"stages.delivery.installed"
			/>
			
			<send-block								v-on:stage-block-send-on-send="onSend"
				:allowed=	"sendAllowed" 
				:resend=	"editableMixin"
			/>
			
			<timeline-block  v-if="hasStageTimeLine"
				:timelineItems= "stages.timeline.items"
			/>
		</div>
	`
};

export {
	StageBlocksList
}
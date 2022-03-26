import {Loc} from 'main.core';
import {StatusTypes as Status} from 'salescenter.component.stage-block';
import {UI} from 'ui.notification';
import {MixinTemplatesType} from '../templates-type-mixin';
import * as Tile from 'salescenter.tile';
import Send from './stage-blocks/send';
import Product from '../stage-blocks/product';
import {DeliveryVuex} from '../stage-blocks/delivery-vuex';
import {Automation} from '../stage-blocks/automation';
import {StageMixin} from '../stage-blocks/stage-mixin';

export default {
	components: {
		'send-block'			: Send,
		'product-block'			: Product,
		'delivery-block'		: DeliveryVuex,
		'automation-block'		: Automation,
	},
	props:{
		sendAllowed: {
			type: Boolean,
			required: true
		},
	},
	data()
	{
		let stages =  {
			product:{
				status:				this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0
					?	Status.complete
					:	Status.current,
				title: this.$root.$app.options.templateMode === 'view'
					? Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHIPMENT_VIEW')
					: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT_SHIPMENT'),
			},
			delivery:{
				status: 			this.$root.$app.options.deliveryList.isInstalled
					?	Status.complete
					:	Status.disabled,
				tiles:			this.getTileCollection(
					this.$root.$app.options.deliveryList.items),
				installed:			this.$root.$app.options.deliveryList.isInstalled,
				initialCollapseState: this.$root.$app.options.isDeliveryCollapsed ? this.$root.$app.options.isDeliveryCollapsed === 'Y' : this.$root.$app.options.deliveryList.isInstalled,
			},
			automation:{}
		};

		if (this.$root.$app.options.isAutomationAvailable)
		{
			stages.automation = {
				status:						Status.complete,
				stageOnDeliveryFinished:	this.$root.$app.options.stageOnDeliveryFinished,
				items:						this.$root.$app.options.entityStageList,
				initialCollapseState: 		this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false,
			};
		}

		return {
			stages: stages,
		}
	},
	mixins:[StageMixin, MixinTemplatesType],
	computed:
		{
			hasStageAutomation()
			{
				return this.stages.automation.hasOwnProperty('items');
			},
			editableMixin()
			{
				return this.editable === false;
			},
			isViewTemplateMode()
			{
				return this.$root.$app.options.templateMode === 'view';
			},
		},
	methods:
		{
			initCounter()
			{
				this.counter = 1;
			},

			getTileCollection(items)
			{
				let tiles = [];
				Object.values(items).forEach(
					options => tiles.push(Tile.Factory.create(options)));

				return tiles;
			},

			getTitleItems(items)
			{
				let result = [];
				items.forEach((item) => {
					if (![Tile.More.type(), Tile.Offer.type()].includes(item.type))
					{
						result.push(item);
					}
				});

				return result;
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
				}.bind(this),
				function() {
					UI.Notification.Center.notify({
						content: Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR'),
					});
				});
			},

			refreshTilesByType(data, type)
			{
				if(type === 'DELIVERY')
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

			saveCollapsedOption(type, value)
			{
				BX.userOptions.save('salescenter', 'add_shipment_collapse_options', type, value);
			},
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
			<product-block 
				:counter=	"counter++"
				:status= 	"stages.product.status"
				:title=		"stages.product.title"		
				:hintTitle=		"''"
			/>
			
			<delivery-block							v-on:on-stage-tile-collection-slider-close="stageRefresh($event, 'DELIVERY')"
				:counter=	"counter++"
				:status=  	"stages.delivery.status"
				:tiles=  	"stages.delivery.tiles"
				:installed=	"stages.delivery.installed"
				:isCollapsible="false"
				:initialCollapseState = "stages.delivery.initialCollapseState"
				@on-save-collapsed-option="saveCollapsedOption"
			/>
									
			<automation-block v-if="editable && hasStageAutomation"
				:counter=	"counter++"
				:status=	"stages.automation.status"
				:stageOnDeliveryFinished=	"stages.automation.stageOnDeliveryFinished"
				:items=		"stages.automation.items"
				:initialCollapseState = "stages.automation.initialCollapseState"
				@on-save-collapsed-option="saveCollapsedOption"
			/>
			
			<send-block
				v-if="!isViewTemplateMode"
				@on-submit="onSend"
				:buttonEnabled="sendAllowed"
			/>
		</div>
	`
};

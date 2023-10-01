import {Loc} from 'main.core';
import {StatusTypes as Status} from 'salescenter.component.stage-block';
import * as TimeLineItem from 'salescenter.timeline';
import {UI} from 'ui.notification';
import {MixinTemplatesType} from '../templates-type-mixin';
import * as Tile from 'salescenter.tile';
import Send from '../deal-receiving-payment/stage-blocks/send';
import {Cashbox} from '../stage-blocks/cashbox';
import Product from '../stage-blocks/product';
import {PaySystem} from '../stage-blocks/paysystem';
import {SmsMessage} from '../stage-blocks/sms-message';
import {Automation} from '../stage-blocks/automation';
import {StageMixin} from '../stage-blocks/stage-mixin';
import {TimeLine} from '../stage-blocks/timeline';
import {DocumentSelector} from '../stage-blocks/document-selector';

export default {
	components: {
		'send-block': Send,
		'cashbox-block': Cashbox,
		'product-block': Product,
		'paysystem-block': PaySystem,
		'automation-block': Automation,
		'sms-message-block': SmsMessage,
		'timeline-block': TimeLine,
		'document-selector-block': DocumentSelector,
	},
	props: {
		sendAllowed: {
			type: Boolean,
			required: true
		},
	},
	data()
	{
		let stages =  {
			message: {
				initSenders: this.$root.$app.options.senders,
				initCurrentSenderCode: this.$root.$app.options.currentSenderCode,
				initPushedToUseBitrix24Notifications: this.$root.$app.options.pushedToUseBitrix24Notifications,
				status: Status.complete,
				selectedSmsSender: this.$root.$app.sendingMethodDesc.provider,
				manager: this.$root.$app.options.entityResponsible,
				phone: this.$root.$app.options.contactPhone,
				ownerId: this.$root.$app.options.ownerId,
				ownerTypeId: this.$root.$app.options.ownerTypeId,
				contactEditorUrl: this.$root.$app.options.contactEditorUrl,
				titleTemplate: this.$root.$app.sendingMethodDesc.sent
					? Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2_PAST_TIME')
					: Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2'),
				showHint: this.$root.$app.options.templateMode !== 'view',
				editorTemplate: this.$root.$app.sendingMethodDesc.text,
				editorUrl: this.$root.$app.orderPublicUrl,
				selectedMode: 'payment',
			},
			product: {
				status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0
					? Status.complete
					: Status.current,
				title: this.$root.$app.options.templateMode === 'view'
					? Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW')
					: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
				hintTitle: this.$root.$app.options.templateMode === 'view'
					? ''
					: Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT'),
			},
			paysystem: {
				status: this.$root.$app.options.paySystemList.isSet
					? Status.complete
					: Status.disabled,
				tiles: this.getTileCollection(this.$root.$app.options.paySystemList.items),
				installed: this.$root.$app.options.paySystemList.isSet,
				titleItems: this.getTitleItems(this.$root.$app.options.paySystemList.items),
				initialCollapseState: this.$root.$app.options.isPaySystemCollapsed ? this.$root.$app.options.isPaySystemCollapsed === 'Y' : this.$root.$app.options.paySystemList.isSet,
			},
			cashbox: {},
			automation: {},
			documentSelector: {
				status: Status.complete,
			},
		};

		if (this.$root.$app.options.cashboxList.hasOwnProperty('items'))
		{
			stages.cashbox = {
				status: this.$root.$app.options.cashboxList.isSet
					? Status.complete
					: Status.disabled,
				tiles: this.getTileCollection(this.$root.$app.options.cashboxList.items),
				installed: this.$root.$app.options.cashboxList.isSet,
				titleItems: this.getTitleItems(this.$root.$app.options.cashboxList.items),
				initialCollapseState: this.$root.$app.options.isCashboxCollapsed ? this.$root.$app.options.isCashboxCollapsed === 'Y' : this.$root.$app.options.cashboxList.isSet,
			};
		}

		if (this.$root.$app.options.isAutomationAvailable)
		{
			stages.automation = {
				status: Status.complete,
				stageOnOrderPaid: this.$root.$app.options.stageOnOrderPaid,
				items: this.$root.$app.options.entityStageList,
				initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false,
			};
		}

		if (this.$root.$app.options.hasOwnProperty('timeline'))
		{
			stages.timeline = {
				items: this.getTimelineCollection(this.$root.$app.options.timeline)
			}
		}

		if (this.$root.$app.hasOwnProperty('documentSelector'))
		{
			if (this.$root.$app.documentSelector.templateAddUrl)
			{
				stages.documentSelector.templateAddUrl = this.$root.$app.documentSelector.templateAddUrl;
			}
		}

		return {
			stages: stages,
		}
	},
	mixins: [
		StageMixin,
		MixinTemplatesType
	],
	computed: {
		hasStageTimeLine()
		{
			return this.stages.timeline.hasOwnProperty('items') && this.stages.timeline.items.length > 0;
		},
		hasStageAutomation()
		{
			return this.stages.automation.hasOwnProperty('items');
		},
		hasStageCashBox()
		{
			return this.stages.cashbox.hasOwnProperty('tiles');
		},
		submitButtonLabel()
		{
			return this.editable ? Loc.getMessage('SALESCENTER_SEND') : Loc.getMessage('SALESCENTER_RESEND');
		},
		isShowDocumentSelector()
		{
			return this.$root.$app.hasOwnProperty('documentSelector');
		},
	},
	methods: {
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
			if(type === 'PAY_SYSTEM')
			{
				this.stages.paysystem.status = data.isSet
					? Status.complete
					: Status.disabled;
				this.stages.paysystem.tiles = this.getTileCollection(data.items);
				this.stages.paysystem.installed = data.isSet;
			}
			else if(type === 'CASHBOX')
			{
				this.stages.cashbox.status = data.isSet
					? Status.complete
					: Status.disabled;
				this.stages.cashbox.tiles = this.getTileCollection(data.items);
				this.stages.cashbox.installed = data.isSet;
				this.stages.cashbox.titleItems = this.getTitleItems(data.items);
			}
		},
		onSend(event)
		{
			this.$emit('stage-block-send-on-send', event)
		},
		changeProvider(value)
		{
			this.$root.$app.sendingMethodDesc.provider = value;
			BX.userOptions.save('salescenter', 'payment_sms_provider_options', 'latest_selected_provider', value);
		},
		saveCollapsedOption(type, value)
		{
			BX.userOptions.save('salescenter', 'add_payment_collapse_options', type, value);
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
			<sms-message-block
				@stage-block-sms-send-on-change-provider="changeProvider"
				:counter="counter++"
				:status="stages.message.status"
				:initSenders="stages.message.initSenders"
				:initCurrentSenderCode="stages.message.initCurrentSenderCode"
				:initPushedToUseBitrix24Notifications="stages.message.initPushedToUseBitrix24Notifications"
				:selectedSmsSender="stages.message.selectedSmsSender"
				:manager="stages.message.manager"
				:phone="stages.message.phone"
				:contactEditorUrl="stages.message.contactEditorUrl"
				:ownerId="stages.message.ownerId"
				:ownerTypeId="stages.message.ownerTypeId"
				:titleTemplate="stages.message.titleTemplate"
				:showHint="stages.message.showHint"
				:editorTemplate="stages.message.editorTemplate"
				:editorUrl="stages.message.editorUrl"
				:selectedMode="stages.message.selectedMode"
			/>
			<product-block
				:counter="counter++"
				:status="stages.product.status"
				:title="stages.product.title"
				:hintTitle="stages.product.hintTitle"
			/>
			<paysystem-block
				v-if="editable"
				@on-stage-tile-collection-slider-close="stageRefresh($event, 'PAY_SYSTEM')"
				:counter="counter++"
				:status="stages.paysystem.status"
				:tiles="stages.paysystem.tiles"
				:installed="stages.paysystem.installed"
				:titleItems="stages.paysystem.titleItems"
				:initialCollapseState="stages.paysystem.initialCollapseState"
				@on-save-collapsed-option="saveCollapsedOption"
			/>
			<cashbox-block
				v-if="editable && hasStageCashBox"
				@on-stage-tile-collection-slider-close="stageRefresh($event, 'CASHBOX')"
				:counter="counter++"
				:status="stages.cashbox.status"
				:tiles="stages.cashbox.tiles"
				:installed="stages.cashbox.installed"
				:titleItems="stages.cashbox.titleItems"
				:initialCollapseState="stages.cashbox.initialCollapseState"
				@on-save-collapsed-option="saveCollapsedOption"
			/>
			<document-selector-block
				v-if="isShowDocumentSelector"
				:counter="counter++"
				:templateAddUrl="stages.documentSelector.templateAddUrl"
			/>
			<automation-block
				v-if="editable && hasStageAutomation"
				:counter="counter++"
				:status="stages.automation.status"
				:stageOnOrderPaid="stages.automation.stageOnOrderPaid"
				:items="stages.automation.items"
				:initialCollapseState="stages.automation.initialCollapseState"
				@on-save-collapsed-option="saveCollapsedOption"
			/>
			<send-block
				@on-submit="onSend"
				:buttonEnabled="sendAllowed"
				:showWhatClientSeesControl="!editable"
				:buttonLabel="submitButtonLabel"
			/>
			<timeline-block
				v-if="hasStageTimeLine"
				:timelineItems="stages.timeline.items"
			/>
		</div>
	`
};

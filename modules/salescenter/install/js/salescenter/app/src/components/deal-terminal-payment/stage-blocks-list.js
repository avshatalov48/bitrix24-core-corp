import { ajax as Ajax, Loc, Runtime, userOptions as UserOptions } from 'main.core';
import { StatusTypes as Status } from 'salescenter.component.stage-block';
import * as TimeLineItem from 'salescenter.timeline';
import { UI } from 'ui.notification';
import { MixinTemplatesType } from '../templates-type-mixin';
import * as Tile from 'salescenter.tile';
import { ResponsibleSelector } from '../stage-blocks/responsible-selector';
import { Cashbox } from '../stage-blocks/cashbox';
import Product from '../stage-blocks/product';
import { PaySystem } from '../stage-blocks/paysystem';
import { Automation } from '../stage-blocks/automation';
import { StageMixin } from '../stage-blocks/stage-mixin';
import { TimeLine } from '../stage-blocks/timeline';
import Send from './stage-blocks/send';
import Amount from './stage-blocks/amount';

export default {
	components: {
		'responsible-block': ResponsibleSelector,
		'cashbox-block': Cashbox,
		'product-block': Product,
		'paysystem-block': PaySystem,
		'automation-block': Automation,
		'timeline-block': TimeLine,
		'send-block': Send,
		'amount-block': Amount,
	},
	props: {
		sendAllowed: {
			type: Boolean,
			required: true,
		},
	},
	data()
	{
		const stages = {
			responsible: {
				status: Status.complete,
				selectedUser: parseInt(this.$root.$app.options.paymentResponsible ?? 0),
				responsible: this.$root.$app.options.entityResponsible,
				isMobileInstalledForResponsible: this.$root.$app.options.isMobileInstalledForResponsible,
				contact: {
					name: this.$root.$app.options.contactName,
					phone: this.$root.$app.options.contactPhone,
				},
				editable: this.$root.$app.options.templateMode === 'create' || this.$root.$app.options.payment?.PAID === 'N',
				hintTitle: this.$root.$app.options.templateMode === 'view'
					? ''
					: Loc.getMessage('SALESCENTER_HOW_TERMINAL_WORKS'),
			},
			product: {
				status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0
					? Status.complete
					: Status.current,
				title: this.$root.$app.options.templateMode === 'view'
					? Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW')
					: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
				hintTitle: '',
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
				items: this.getTimelineCollection(this.$root.$app.options.timeline),
			};
		}

		if (this.$root.$app.options.paySystemList.groups)
		{
			stages.paysystem.groups = this.getTileGroupsCollection(
				this.$root.$app.options.paySystemList.groups,
				stages.paysystem.tiles,
			);
		}

		if (this.$root.$app.options.templateMode === 'view' && this.$root.$app.options.payment?.PAID === 'Y')
		{
			stages.responsible.title = Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_TITLE_PAID_VIEW');
		}
		else
		{
			stages.responsible.title = Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_TITLE');
		}

		return {
			stages,
		};
	},
	mixins: [
		StageMixin,
		MixinTemplatesType,
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
			return this.editable ? Loc.getMessage('SALESCENTER_CREATE_TERMINAL_PAYMENT') : Loc.getMessage('SALESCENTER_SAVE');
		},
		hasProducts()
		{
			return !this.$root.$app.options.isPaymentByAmount;
		},
	},
	methods: {
		initCounter()
		{
			this.counter = 1;
		},
		getTimelineCollection(items)
		{
			const list = [];

			Object.values(items).forEach(
				(options) => list.push(TimeLineItem.Factory.create(options)),
			);

			return list;
		},
		getTileCollection(items)
		{
			const tiles = [];
			Object.values(items).forEach(
				(options) => tiles.push(Tile.Factory.create(options)),
			);

			return tiles;
		},
		getTileGroupsCollection(groups, tiles)
		{
			const ret = [];

			if (Array.isArray(groups))
			{
				Object.values(groups).forEach((item) => {
					const group = new Tile.Group(item);
					group.fillTiles(tiles);
					ret.push(group);
				});
			}

			return ret;
		},
		getTitleItems(items)
		{
			const result = [];
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
			Ajax.runComponentAction(
				'bitrix:salescenter.app',
				'getAjaxData',
				{
					mode: 'class',
					data: {
						type,
					},
				},
			).then(
				(response) => {
					if (response.data)
					{
						this.refreshTilesByType(response.data, type);
					}
				},
				() => {
					UI.Notification.Center.notify({
						content: Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR'),
					});
				},
			);
		},
		refreshTilesByType(data, type)
		{
			switch (type)
			{
				case 'PAY_SYSTEM': {
					this.stages.paysystem.status = data.isSet
						? Status.complete
						: Status.disabled;
					this.stages.paysystem.tiles = this.getTileCollection(data.items);
					this.stages.paysystem.groups = this.getTileGroupsCollection(data.groups, this.stages.paysystem.tiles);
					this.stages.paysystem.installed = data.isSet;
					this.stages.paysystem.titleItems = this.getTitleItems(data.items);

					break;
				}

				case 'CASHBOX': {
					this.stages.cashbox.status = data.isSet
						? Status.complete
						: Status.disabled;
					this.stages.cashbox.tiles = this.getTileCollection(data.items);
					this.stages.cashbox.installed = data.isSet;
					this.stages.cashbox.titleItems = this.getTitleItems(data.items);

					break;
				}
			// No default
			}
		},
		onSend(event)
		{
			this.$emit('stage-block-send-on-send', event);
		},
		onResponsibleChanged(event)
		{
			this.$emit('on-responsible-changed', event);
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
	// language=Vue
	template: `
		<div class="salescenter-app-terminal-wrapper">
			<responsible-block
				:counter="counter++"
				:status="stages.responsible.status"
				:title="stages.responsible.title"
				:selectedUser="stages.responsible.selectedUser"
				:responsible="stages.responsible.responsible"
				:isMobileInstalledForResponsible="stages.responsible.isMobileInstalledForResponsible"
				:contact="stages.responsible.contact"
				:editable="stages.responsible.editable"
				:hintTitle="stages.responsible.hintTitle"
				@on-responsible-changed="onResponsibleChanged"
			/>
			<product-block
				v-if="hasProducts"
				:counter="counter++"
				:status="stages.product.status"
				:title="stages.product.title"
				:hintTitle="stages.product.hintTitle"
				:additionalContainerClasses="{ 'salescenter-app-teminal-products-item': true }"
			/>
			<amount-block
				v-else
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
				v-if="editable"
				@on-submit="onSend"
				:buttonEnabled="sendAllowed"
				:buttonLabel="submitButtonLabel"
			/>
			<timeline-block
				v-if="hasStageTimeLine"
				:timelineItems="stages.timeline.items"
			/>
		</div>
	`,
};

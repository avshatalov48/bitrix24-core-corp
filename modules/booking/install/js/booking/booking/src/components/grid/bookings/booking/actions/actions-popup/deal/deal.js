import { mapGetters } from 'ui.vue3.vuex';
import { Event, Runtime, Uri } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { SidePanel } from 'main.sidepanel';
import { Menu, MenuManager } from 'main.popup';
import type { MenuItemOptions } from 'main.popup';

import { Dialog } from 'ui.entity-selector';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import { limit } from 'booking.lib.limit';
import { CrmEntity, EntitySelectorEntity, HelpDesk, Model, Module } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel, DealData } from 'booking.model.bookings';
import type { ClientData } from 'booking.model.clients';

import './deal.css';

export const Deal = {
	name: 'BookingActionsPopupDeal',
	emits: ['freeze', 'unfreeze'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Button,
		Icon,
		Loader,
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: false,
			saveDealDebounce: Runtime.debounce(this.saveDeal, 10, this),
		};
	},
	mounted(): void
	{
		this.dialog = new Dialog({
			context: 'BOOKING',
			multiple: false,
			targetNode: this.getDialogButton(),
			width: 340,
			height: 340,
			enableSearch: true,
			dropdownMode: true,
			preselectedItems: this.deal ? [[EntitySelectorEntity.Deal, this.deal.value]] : [],
			entities: [
				{
					id: EntitySelectorEntity.Deal,
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
			events: {
				onShow: this.freeze,
				onHide: this.unfreeze,
				'Item:onSelect': this.itemChange,
				'Item:onDeselect': this.itemChange,
			},
		});

		Event.bind(document, 'scroll', this.adjustPosition, true);
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, true);
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		menuId(): string
		{
			return 'booking-actions-popup-deal-menu';
		},
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		deal(): DealData | null
		{
			return this.booking.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
		},
		dateFormatted(): string
		{
			if (!this.deal.data.createdTimestamp)
			{
				return '';
			}

			const format = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');

			return DateTimeFormat.format(format, this.deal.data.createdTimestamp);
		},
	},
	methods: {
		freeze(): void
		{
			this.$emit('freeze');
		},
		unfreeze(): void
		{
			if (this.dialog?.isOpen() || this.getMenu()?.getPopupWindow().isShown())
			{
				return;
			}

			this.$emit('unfreeze');
		},
		createDeal(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			const bookingIdParamName = 'bookingId';

			const createDealUrl = new Uri('/crm/deal/details/0/');
			createDealUrl.setQueryParam(bookingIdParamName, this.bookingId);

			(this.booking.clients ?? []).forEach((client: ClientData) => {
				const paramName = {
					[CrmEntity.Contact]: 'contact_id',
					[CrmEntity.Company]: 'company_id',
				}[client.type.code];

				createDealUrl.setQueryParam(paramName, client.id);
			});

			SidePanel.Instance.open(createDealUrl.toString(), {
				events: {
					onLoad: ({ slider }) => {
						slider.getWindow().BX.Event.EventEmitter.subscribe('onCrmEntityCreate', (event) => {
							const [data] = event.getData();

							const isDeal = data.entityTypeName === CrmEntity.Deal;
							const bookingId = Number(new Uri(data.sliderUrl).getQueryParam(bookingIdParamName));
							if (!isDeal || bookingId !== this.bookingId)
							{
								return;
							}

							const dealData = this.mapEntityInfoToDeal(data.entityInfo);

							this.saveDealDebounce(dealData);
						});
					},
					onClose: () => {
						if (this.deal?.value)
						{
							this.saveDealDebounce(this.deal);
						}
					},
				},
			});
		},
		showMenu(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			const bindElement = this.$refs.moreButton.$el;
			MenuManager.destroy(this.menuId);
			MenuManager.show({
				id: this.menuId,
				bindElement,
				items: this.getMenuItems(),
				offsetLeft: bindElement.offsetWidth / 2,
				angle: true,
				events: {
					onShow: this.freeze,
					onAfterClose: this.unfreeze,
					onDestroy: this.unfreeze,
				},
			});
		},
		getMenuItems(): MenuItemOptions[]
		{
			return [
				{
					text: this.loc('BB_ACTIONS_POPUP_DEAL_CHANGE'),
					onclick: () => {
						this.showDealDialog();
						this.getMenu().close();
					},
				},
				{
					text: this.loc('BB_ACTIONS_POPUP_DEAL_CLEAR'),
					onclick: () => {
						this.dialog?.deselectAll();
						this.saveDealDebounce(null);
						this.getMenu().close();
					},
				},
			];
		},
		showDealDialog(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			this.dialog.setTargetNode(this.getDialogButton());
			this.dialog.show();
		},
		adjustPosition(): void
		{
			this.dialog.setTargetNode(this.getDialogButton());
			this.dialog.adjustPosition();
			this.getMenu()?.getPopupWindow().adjustPosition();
		},
		getMenu(): Menu | null
		{
			return MenuManager.getMenuById(this.menuId);
		},
		openDeal(): void
		{
			SidePanel.Instance.open(`/crm/deal/details/${this.deal.value}/`, {
				events: {
					onClose: () => {
						if (this.deal?.value)
						{
							void bookingService.getById(this.bookingId);
						}
					},
				},
			});
		},
		itemChange(): void
		{
			const dealData = this.getDealData();

			this.saveDealDebounce(dealData);

			this.dialog.hide();
		},
		getDealData(): DealData | null
		{
			const item = this.dialog.getSelectedItems()[0];
			if (!item)
			{
				return null;
			}

			return this.mapEntityInfoToDeal(item.getCustomData().get('entityInfo'));
		},
		mapEntityInfoToDeal(info: Object): DealData
		{
			return {
				moduleId: Module.Crm,
				entityTypeId: info.typeName,
				value: info.id,
				data: [],
			};
		},
		saveDeal(dealData: DealData | null): void
		{
			const externalData = dealData ? [dealData] : [];

			void bookingService.update({
				id: this.booking.id,
				externalData,
			});
		},
		getDialogButton(): HTMLElement
		{
			return this.deal ? this.$refs.moreButton.$el : this.$refs.addButton.$el;
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.BookingActionsDeal.code,
				HelpDesk.BookingActionsDeal.anchorCode,
			);
		},
	},
	template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-deal-content"
			:class="{ '--active': deal }"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-deal-loader" />
			<template v-else>
				<div class="booking-actions-popup__item-deal">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="IconSet.DEAL"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DEAL_LABEL') }}</span>
							<Icon :name="IconSet.HELP" @click="showHelpDesk" />
						</div>
						<template v-if="deal">
							<div
								class="booking-actions-popup__item-deal-profit"
								data-element="booking-menu-deal-profit"
								:data-profit="deal.data.opportunity"
								:data-booking-id="bookingId"
								v-html="deal.data.formattedOpportunity"
							></div>
							<div
								class="booking-actions-popup-item-subtitle"
								data-element="booking-menu-deal-ts"
								:data-ts="deal.data.createdTimestamp * 1000"
								:data-booking-id="bookingId"
							>
								{{ dateFormatted }}
							</div>
						</template>
						<template v-else>
							<div class="booking-actions-popup-item-subtitle">
								{{ loc('BB_ACTIONS_POPUP_DEAL_ADD_LABEL') }}
							</div>
						</template>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<template v-if="deal">
						<Button
							data-element="booking-menu-deal-open-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_OPEN')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="openDeal"
						/>
						<Button
							data-element="booking-menu-deal-more-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="moreButton"
							@click="showMenu"
						>
							<Icon :name="IconSet.MORE"/>
						</Button>
					</template>
					<template v-else>
						<Button
							data-element="booking-menu-deal-create-button"
							:data-booking-id="bookingId"
							class="booking-actions-popup-plus-button"
							:class="{'--lock': !isFeatureEnabled}"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="createDeal"
						>
							<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_30"/>
							<Icon v-else :name="IconSet.LOCK"/>
						</Button>
						<Button
							class="booking-menu-deal-add-button"
							:class="{'--lock': !isFeatureEnabled}"
							data-element="booking-menu-deal-add-button"
							:data-booking-id="bookingId"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_BTN_LABEL')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="addButton"
							@click="showDealDialog"
						>
							<Icon v-if="!isFeatureEnabled" :name="IconSet.LOCK"/>
						</Button>
					</template>
				</div>
			</template>
		</div>
	`,
};

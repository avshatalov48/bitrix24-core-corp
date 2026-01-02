import { Dialog, Item, type ItemOptions } from 'ui.entity-selector';
import { mapGetters } from 'ui.vue3.vuex';

import { EntitySelectorEntity, EntitySelectorTab, Model } from 'booking.const';
import { resourceDialogService } from 'booking.provider.service.resource-dialog-service';
import type { BookingModel } from 'booking.model.bookings';
import type { ResourceModel } from 'booking.model.resources';

// @vue/component
export const ExtraResourcesDialog = {
	name: 'ExtraResourcesDialog',
	props: {
		/**
		 * @type {BookingModel}
		 */
		booking: {
			type: Object,
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
	},
	emits: ['save'],
	computed: {
		...mapGetters({
			resources: `${Model.Resources}/get`,
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			getByInterval: `${Model.Bookings}/getByInterval`,
		}),
		extraResourcesIds(): number[]
		{
			return this.booking.resourcesIds.filter((resourceId) => resourceId !== this.resourceId);
		},
		bookings(): BookingModel[]
		{
			return this.getByInterval(this.booking.dateFromTs, this.booking.dateToTs);
		},
		resourceBookingsMap(): Map<number, Array<number | string>>
		{
			const map: Map<number, Array<number | string>> = new Map();
			const bookingId = this.booking.id;

			this.bookings
				.filter((booking) => booking.id !== bookingId)
				.forEach((booking) => {
					booking.resourcesIds.forEach((resourceId) => {
						const resourceBookingIds = map.get(resourceId) || [];
						resourceBookingIds.push(booking.id);
						map.set(resourceId, resourceBookingIds);
					});
				});

			return map;
		},
		excludedResourceIds(): Set<number>
		{
			return new Set([this.resourceId]);
		},
	},
	watch: {
		resources(resources): void
		{
			this.addItems(resources);
		},
	},
	created(): void
	{
		void this.loadResources();
	},
	mounted(): void
	{
		this.dialog = new Dialog({
			id: `booking-booking-extra-resources-selector-${this.booking.id}`,
			targetNode: this.$refs.dialog,
			preselectedItems: this.extraResourcesIds.map((id: number) => [EntitySelectorEntity.Resource, id]),
			width: 340,
			height: Math.min(window.innerHeight - 380, 400),
			dropdownMode: true,
			entities: [
				{
					id: EntitySelectorEntity.Resource,
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
			popupOptions: {
				id: `booking-actions-popup-extra-resources-dialog-${this.booking.id}-${this.resourceId}`,
				className: 'booking--booking--actions-popup--extra-resources-info--extra-resources-dialog',
			},
			enableSearch: true,
			searchOptions: {
				allowCreateItem: false,
			},
			events: {
				onLoad: (): void => {
					this.addItems(this.resources);
				},
				onHide: (): void => {
					const selectedItemIds = this.dialog.getSelectedItems().map((item) => item.id);
					selectedItemIds.push(this.resourceId);

					this.$emit('save', selectedItemIds);
				},
				'Item:onBeforeSelect': (event): void => {
					const item = event.data.item;
					if ((this.resourceBookingsMap.get(item.id) || []).length > 1)
					{
						// eslint-disable-next-line no-param-reassign
						event.defaultPrevented = true;
					}
				},
			},
		});

		this.dialog.show();
	},
	unmounted()
	{
		this.dialog?.destroy?.();
	},
	methods: {
		async loadResources(): Promise<void>
		{
			await resourceDialogService.fillDialog(this.selectedDateTs / 1000);
		},
		getItemOptions(resource: ResourceModel): ItemOptions
		{
			return {
				id: resource.id,
				entityId: EntitySelectorEntity.Resource,
				title: resource.name,
				subtitle: '',
				avatar: '/bitrix/js/booking/component/actions-popup/images/extra-resource-icon.svg',
				avatarOptions: {
					bgImage: '/bitrix/js/booking/component/actions-popup/images/extra-resource-icon.svg',
					bgColor: 'rgba(230, 244, 255, 1)',
					borderRadius: '50%',
				},
				tabs: EntitySelectorTab.Recent,
				selected: this.isItemSelected(resource.id),
				deselectable: !this.excludedResourceIds.has(resource.id),
				nodeAttributes: {
					'data-id': `${this.booking.id}-${resource.id}`,
					'data-element': 'booking-extra-resources-dialog-item',
				},
				badges: [this.getItemBadge(resource)],
				badgesOptions: {
					justifyContent: 'right',
				},
			};
		},
		isItemSelected(id: number): boolean
		{
			return this.extraResourcesIds.includes(id);
		},
		getItemBadge(resource: ResourceModel): Object
		{
			const resourceBookingsCount = (this.resourceBookingsMap.get(resource.id) || []).length;

			if (resourceBookingsCount >= 2)
			{
				return {
					title: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_RESOURCE_SELECTOR_BADGE_BUSY'),
					textColor: 'rgba(255, 255, 255, 1)',
					bgColor: '#e92f2a',
				};
			}

			if (resourceBookingsCount > 0)
			{
				return {
					title: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_RESOURCE_SELECTOR_BADGE_OVERBOOKING'),
					textColor: 'rgba(255, 255, 255, 1)',
					bgColor: 'rgba(250, 167, 44, 1)',
				};
			}

			return {};
		},
		addItems(resources: ResourceModel[]): void
		{
			const itemsOptions: { [id: number]: ItemOptions } = resources
				.filter(({ id }) => !this.excludedResourceIds.has(id))
				.reduce((acc, resource: ResourceModel) => ({
					...acc,
					[resource.id]: this.getItemOptions(resource),
				}), {});

			Object.values(itemsOptions).forEach((itemOptions: ItemOptions) => this.dialog.addItem(itemOptions));

			const itemsIds = this.dialog.getItems()
				.map((item: Item) => item.getId())
				.filter((id: number) => itemsOptions[id]);

			this.dialog.removeItems();
			itemsIds.forEach((id: number) => this.dialog.addItem(itemsOptions[id]));

			const tab = this.dialog.getActiveTab();
			if (tab)
			{
				tab.getContainer().append(tab.getRootNode().getChildrenContainer());
				tab.render();
			}
		},
	},
	template: `
		<div ref="dialog"></div>
	`,
};

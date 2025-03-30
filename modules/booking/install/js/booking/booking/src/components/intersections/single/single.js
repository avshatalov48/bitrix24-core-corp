import { Event } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { Item, TagSelector } from 'ui.entity-selector';

import { AhaMoment, EntitySelectorEntity, EntitySelectorTab, HelpDesk, Model } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { limit } from 'booking.lib.limit';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import './single.css';

export const Single = {
	emits: ['change'],
	created(): void
	{
		this.selector = this.createSelector();

		if (!this.isFeatureEnabled)
		{
			this.selector.lock();
		}
	},
	mounted(): void
	{
		this.mountSelector();

		Event.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
		Event.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	},
	beforeUnmount(): void
	{
		this.destroySelector();
	},
	computed: {
		...mapGetters({
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			intersections: `${Model.Interface}/intersections`,
			isLoaded: `${Model.Interface}/isLoaded`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			resources: `${Model.Resources}/get`,
		}),
		resourcesIds(): number[]
		{
			return this.resources.map(({ id }) => id);
		},
	},
	methods: {
		createSelector(): TagSelector
		{
			return new TagSelector({
				multiple: true,
				addButtonCaption: this.loc('BOOKING_BOOKING_ADD_INTERSECTION'),
				showCreateButton: false,
				maxHeight: 50,
				dialogOptions: {
					header: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_DIALOG_HEADER'),
					context: 'bookingResourceIntersection',
					width: 290,
					height: 340,
					dropdownMode: true,
					enableSearch: true,
					cacheable: true,
					showAvatars: false,
					entities: [
						{
							id: EntitySelectorEntity.Resource,
							dynamicLoad: true,
							dynamicSearch: true,
						},
					],
					searchOptions: {
						allowCreateItem: false,
						footerOptions: {
							label: this.loc('BOOKING_BOOKING_ADD_INTERSECTION_DIALOG_SEARCH_FOOTER'),
						},
					},
				},
				events: {
					onAfterTagAdd: this.onSelectorChange,
					onAfterTagRemove: this.onSelectorChange,
				},
			});
		},
		onSelectorChange(): void
		{
			const selectedIds = this.selector.getDialog().getSelectedItems().map(({ id }) => id);

			this.$emit('change', selectedIds);
		},
		mountSelector(): void
		{
			this.selector.renderTo(this.$refs.intersectionField);
		},
		destroySelector(): void
		{
			this.selector.getDialog().destroy();
			this.selector = null;
			this.$refs.intersectionField.innerHTML = '';
		},
		getResource(id: number): ResourceModel
		{
			return this.$store.getters['resources/getById'](id);
		},
		getResourceType(id: number): ResourceTypeModel
		{
			return this.$store.getters['resourceTypes/getById'](id);
		},
		tryShowAhaMoment(): void
		{
			if (
				ahaMoments.shouldShow(AhaMoment.ResourceIntersection)
				&& this.selector
			)
			{
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
				void this.showAhaMoment();
			}
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.show({
				id: 'booking-resource-intersection',
				title: this.loc('BOOKING_AHA_RESOURCE_INTERSECTION_TITLE'),
				text: this.loc('BOOKING_AHA_RESOURCE_INTERSECTION_TEXT'),
				article: HelpDesk.AhaResourceIntersection,
				target: this.selector.getAddButton(),
			});

			ahaMoments.setShown(AhaMoment.ResourceIntersection);
		},
		click(): void
		{
			if (!this.isFeatureEnabled)
			{
				void limit.show();
			}
		},
	},
	watch: {
		intersections(intersections: Object): void
		{
			if (!this.isEditingBookingMode)
			{
				return;
			}

			const resourcesIds = intersections[0] ?? [];

			resourcesIds.forEach((id: number): void => {
				const resource = this.getResource(id);

				this.selector.getDialog().addItem({
					id: resource.id,
					entityId: EntitySelectorEntity.Resource,
					title: resource.name,
					subtitle: this.getResourceType(resource.typeId).name,
					selected: true,
				});
			});
		},
		isLoaded(): void
		{
			this.tryShowAhaMoment();
		},
		resourcesIds(resourcesIds: number[], previousResourcesIds: number[]): void
		{
			if (resourcesIds.join(',') === previousResourcesIds.join(','))
			{
				return;
			}

			const deletedIds = previousResourcesIds.filter((id: number) => !resourcesIds.includes(id));
			const newIds = resourcesIds.filter((id: number) => !previousResourcesIds.includes(id));

			deletedIds.forEach((id: number) => {
				const item: Item = this.selector.getDialog().getItem([EntitySelectorEntity.Resource, id]);
				this.selector.getDialog().removeItem(item);

				const tag = this.selector.getTags().find((it) => it.getId() === id);
				tag?.remove();
			});

			newIds.forEach((id: number) => {
				const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](id);
				const resourceType: ResourceTypeModel = this.$store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

				this.selector.getDialog().addItem({
					id,
					entityId: EntitySelectorEntity.Resource,
					title: resource.name,
					subtitle: resourceType.name,
					tabs: EntitySelectorTab.Recent,
				});
			});

			this.onSelectorChange();

			this.tryShowAhaMoment();
		},
		resources: {
			handler(): void
			{
				this.selector.getDialog().getItems().forEach((item: Item) => {
					const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](item.getId());
					if (!resource)
					{
						return;
					}

					const resourceType: ResourceTypeModel = this.$store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

					item.setTitle(resource.name);
					item.setSubtitle(resourceType.name);
				});

				this.selector.getTags().forEach((tag) => {
					const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](tag.getId());
					if (!resource)
					{
						return;
					}

					tag.setTitle(resource.name);
					tag.render();
				});
			},
			deep: true,
		},
	},
	template: `
		<div
			ref="intersectionField"
			class="booking-booking-intersections-line"
			data-id="booking-booking-intersections-line"
			@click="click"
		></div>
	`,
};

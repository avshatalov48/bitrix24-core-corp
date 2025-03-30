import { mapGetters } from 'ui.vue3.vuex';
import { Dialog, Item } from 'ui.entity-selector';

import { EntitySelectorEntity, EntitySelectorTab, Model } from 'booking.const';
import { limit } from 'booking.lib.limit';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import type { ResourceModel } from 'booking.model.resources';
import './multiple.css';

export const Multiple = {
	emits: [
		'change',
	],
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
	},
	data(): Object
	{
		return {
			isSelected: false,
			selectedItems: [],
		};
	},
	mounted(): void
	{
		this.selector = this.createSelector();
	},
	unmounted(): void
	{
		this.selector.destroy();
		this.selector = null;
	},
	methods: {
		createSelector(): Dialog
		{
			const selectedIds = this.intersections[this.resourceId] ?? [];

			return new Dialog({
				id: `booking-intersection-selector-resource-${this.resourceId}`,
				targetNode: this.$refs.intersectionField,
				preselectedItems: selectedIds.map((id: number) => [EntitySelectorEntity.Resource, id]),
				width: 400,
				enableSearch: true,
				dropdownMode: true,
				context: 'bookingResourceIntersection',
				multiple: true,
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
				events: {
					onHide: this.changeSelected.bind(this),
					onLoad: this.changeSelected.bind(this),
				},
			});
		},
		showSelector(): void
		{
			if (this.isFeatureEnabled)
			{
				this.selector.show();
			}
			else
			{
				void limit.show();
			}
		},
		changeSelected(): void
		{
			this.selectedItems = this.selector.getSelectedItems();

			this.isSelected = this.selectedItems.length > 0;

			const selectedIds = this.selectedItems.map((item) => item.id);

			this.$emit('change', selectedIds, this.resourceId);
		},
		handleRemove(itemId: number): void
		{
			this.selector.getItem([EntitySelectorEntity.Resource, itemId]).deselect();

			this.changeSelected();
		},
	},
	computed: {
		...mapGetters({
			intersections: `${Model.Interface}/intersections`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			resources: `${Model.Resources}/get`,
		}),
		resourcesIds(): number[]
		{
			return this.resources.map(({ id }) => id);
		},
		firstItemTitle(): string
		{
			return this.selectedItems.length > 0 ? this.selectedItems[0].title : '';
		},
		remainingItemsCount(): number
		{
			return this.selectedItems.length > 1 ? this.selectedItems.length - 1 : 0;
		},
	},
	watch: {
		resourcesIds(resourcesIds: number[], previousResourcesIds: number[]): void
		{
			if (resourcesIds.join(',') === previousResourcesIds.join(','))
			{
				return;
			}

			const deletedIds = previousResourcesIds.filter((id: number) => !resourcesIds.includes(id));
			const newIds = resourcesIds.filter((id: number) => !previousResourcesIds.includes(id));

			deletedIds.forEach((id: number) => {
				const item: Item = this.selector.getItem([EntitySelectorEntity.Resource, id]);
				this.selector.removeItem(item);
			});

			newIds.forEach((id: number) => {
				const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](id);
				const resourceType: ResourceTypeModel = this.$store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

				this.selector.addItem({
					id,
					entityId: EntitySelectorEntity.Resource,
					title: resource.name,
					subtitle: resourceType.name,
					tabs: EntitySelectorTab.Recent,
				});
			});

			this.changeSelected();
		},
		resources: {
			handler(): void
			{
				this.selector.getItems().forEach((item: Item) => {
					const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](item.getId());
					if (!resource)
					{
						return;
					}

					const resourceType: ResourceTypeModel = this.$store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

					item.setTitle(resource.name);
					item.setSubtitle(resourceType.name);
				});

				this.selector.getTagSelector().getTags().forEach((tag) => {
					const resource: ResourceModel = this.$store.getters[`${Model.Resources}/getById`](tag.getId());
					if (!resource)
					{
						return;
					}

					tag.setTitle(resource.name);
					tag.render();
				});

				this.selectedItems = this.selector.getSelectedItems();
			},
			deep: true,
		},
	},
	template: `
		<div
			ref="intersectionField"
			class="booking-booking-intersections-resource"
			:data-id="'booking-booking-intersections-resource-' + resourceId"
		>
			<template v-if="isSelected">
				<div
					ref="selectorItemContainer"
					class="booking-booking-intersections-resource-container"
				>
					<div
						v-if="selectedItems.length > 0"
						class="bbi-resource-selector-item bbi-resource-selector-tag"
					>
						<div class="bbi-resource-selector-tag-content" :title="firstItemTitle">
							<div class="bbi-resource-selector-tag-title">{{ firstItemTitle }}</div>
						</div>
						<div 
							class="bbi-resource-selector-tag-remove"
							@click="handleRemove(selectedItems[0].id)"
							:data-id="'bbi-resource-selector-tag-remove-' + resourceId"
						></div>
					</div>
					<div
						v-if="remainingItemsCount > 0"
						class="bbi-resource-selector-item bbi-resource-selector-tag --count"
						@click="showSelector"
						:data-id="'bbi-resource-selector-tag-count-' + resourceId"
					>
						<div class="bbi-resource-selector-tag-content">
							<div class="bbi-resource-selector-tag-title --count">+{{ remainingItemsCount }}</div>
						</div>
					</div>
					<div>
						<span
							class="bbi-resource-selector-item bbi-resource-selector-add-button"
							@click="showSelector"
							:data-id="'bbi-resource-selector-add-button' + resourceId"
						>
							<span class="bbi-resource-selector-add-button-caption">
								{{ loc('BOOKING_BOOKING_INTERSECTION_BUTTON_MORE') }}
							</span>
						</span>
					</div>
				</div>
			</template>
			<template v-else>
				<span
					ref="selectorButton"
					class="bbi-resource-selector-item bbi-resource-selector-add-button"
					@click="showSelector"
					:data-id="'bbi-resource-selector-add-button' + resourceId"
				>
					<span class="bbi-resource-selector-add-button-caption">
						{{ loc('BOOKING_BOOKING_INTERSECTION_BUTTON') }}
					</span>
				</span>
			</template>
		</div>
	`,
};

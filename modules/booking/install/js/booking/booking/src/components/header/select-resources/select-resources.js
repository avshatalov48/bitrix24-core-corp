import { ahaMoments } from 'booking.lib.aha-moments';
import { Dom, Event, Runtime, Type } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { Dialog, Item, type ItemOptions } from 'ui.entity-selector';

import { AhaMoment, EntitySelectorEntity, EntitySelectorTab, HelpDesk, Limit, Model } from 'booking.const';
import { resourceDialogService } from 'booking.provider.service.resource-dialog-service';
import { hideResources } from 'booking.lib.resources';
import { resourcesDateCache } from 'booking.lib.resources-date-cache';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceTypeModel } from 'booking.model.resource-types';

import { ContentHeader } from './dialog-header/content-header';
import { DialogHeader } from './dialog-header/dialog-header';
import { ContentFooter } from './dialog-footer/content-footer';
import { DialogFooter } from './dialog-footer/dialog-footer';
import { ResourceWorkload } from '../resource/resource-workload/resource-workload';
import './select-resources.css';

export const SelectResources = {
	data(): Object
	{
		return {
			dialogFilled: false,
			query: '',
			saveItemsDebounce: Runtime.debounce(this.saveItems, 10, this),
			workloadRefs: {},
			selectedTypes: {},
		};
	},
	mounted(): void
	{
		this.dialog = new Dialog({
			context: 'BOOKING',
			targetNode: this.$refs.button,
			width: 340,
			height: Math.min(window.innerHeight - 280, 600),
			offsetLeft: 4,
			dropdownMode: true,
			preselectedItems: this.favoritesIds.map((resourceId) => [EntitySelectorEntity.Resource, resourceId]),
			items: this.resources.map((resource) => this.getItemOptions(resource)),
			entities: [
				{
					id: EntitySelectorEntity.Resource,
				},
			],
			events: {
				onShow: this.onShow,
				'Item:onSelect': this.saveItemsDebounce,
				'Item:onDeselect': this.saveItemsDebounce,
			},
			header: ContentHeader,
			headerOptions: {
				content: this.$refs.dialogHeader.$el,
			},
			footer: ContentFooter,
			footerOptions: {
				content: this.$refs.dialogFooter.$el,
			},
		});

		Event.bind(this.dialog.getRecentTab().getContainer(), 'scroll', this.loadOnScroll);
		Event.EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
		Event.EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	},
	computed: {
		...mapGetters({
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			favoritesIds: `${Model.Favorites}/get`,
			resources: `${Model.Resources}/get`,
			isFilterMode: `${Model.Interface}/isFilterMode`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			isLoaded: `${Model.Interface}/isLoaded`,
			mainResources: `${Model.MainResources}/resources`,
		}),
		mainResourceIds(): Set<number>
		{
			return new Set(this.mainResources);
		},
		isDefaultState(): boolean
		{
			return this.mainResourceIds.size === this.favoritesIds.length
				&& this.favoritesIds.every((id: number) => this.mainResourceIds.has(id));
		},
	},
	methods: {
		showDialog(): void
		{
			this.updateItems();
			void this.loadMainResources();

			this.dialog.show();
		},
		async onShow(): Promise<void>
		{
			if (this.dialogFilled)
			{
				void this.loadOnScroll();

				return;
			}

			this.dialogFilled = true;

			await resourceDialogService.fillDialog(this.selectedDateTs / 1000);
		},
		async loadOnScroll(): Promise<void>
		{
			const container = this.dialog.getRecentTab().getContainer();
			const scrollTop = container.scrollTop;
			const maxScroll = container.scrollHeight - container.offsetHeight;

			if (scrollTop + 10 >= maxScroll)
			{
				const loadedResourcesIds = resourcesDateCache.getIdsByDateTs(this.selectedDateTs / 1000);
				const resourcesIds = this.resources.map((resource: ResourceModel) => resource.id);
				const idsToLoad = resourcesIds
					.filter((id: number) => !loadedResourcesIds.includes(id))
					.slice(0, Limit.ResourcesDialog)
				;

				await resourceDialogService.loadByIds(idsToLoad, this.selectedDateTs / 1000);

				this.updateItems();
			}
		},
		async loadMainResources(): Promise<void>
		{
			await resourceDialogService.getMainResources();
		},
		updateItems(): void
		{
			this.dialog.getItems().forEach((item: Item) => {
				const id = item.getId();
				const workload = this.workloadRefs[id];
				const isHidden = this.isItemHidden(id);
				const isSelected = this.isItemSelected(id);

				item.getNodes().forEach((node) => {
					const avatarContainer = node.getAvatarContainer();
					Dom.style(avatarContainer, 'width', 'max-content');
					Dom.style(avatarContainer, 'height', 'max-content');
					Dom.append(workload, avatarContainer);
				});

				item.setHidden(isHidden);

				if (!item.isSelected() && isSelected)
				{
					item.select();
				}

				if (item.isSelected() && !isSelected)
				{
					item.deselect();
				}
			});
		},
		saveItems(): void
		{
			void hideResources(this.dialog.getSelectedItems().map((item) => item.id));
		},
		addItems(resources: ResourceModel[]): void
		{
			const itemsOptions: { [id: number]: ItemOptions } = resources.reduce((acc, resource: ResourceModel) => ({
				...acc,
				[resource.id]: this.getItemOptions(resource),
			}), {});

			Object.values(itemsOptions).forEach((itemOptions: ItemOptions) => this.dialog.addItem(itemOptions));

			const itemsIds = this.dialog.getItems().map((item: Item) => item.getId())
				.filter((id: number) => itemsOptions[id])
			;

			this.dialog.removeItems();
			itemsIds.forEach((id: number) => this.dialog.addItem(itemsOptions[id]));

			// I don't know why, but tab is being removed after this.dialog.removeItems();
			const tab = this.dialog.getActiveTab();
			if (tab)
			{
				tab.getContainer().append(tab.getRootNode().getChildrenContainer());
				tab.render();
			}

			this.updateItems();

			if (Type.isStringFilled(this.query))
			{
				void this.search(this.query);
			}
		},
		getItemOptions(resource: ResourceModel): ItemOptions
		{
			return {
				id: resource.id,
				entityId: EntitySelectorEntity.Resource,
				title: resource.name,
				subtitle: this.getResourceType(resource.typeId).name,
				avatarOptions: {
					bgImage: 'none',
					borderRadius: '0',
				},
				tabs: EntitySelectorTab.Recent,
				selected: this.isItemSelected(resource.id),
				hidden: this.isItemHidden(resource.id),
				nodeAttributes: {
					'data-id': resource.id,
					'data-element': 'booking-select-resources-dialog-item',
				},
			};
		},
		isItemSelected(id: number): boolean
		{
			return this.favoritesIds.includes(id);
		},
		isItemHidden(id: number): boolean
		{
			const loadedResourcesIds = resourcesDateCache.getIdsByDateTs(this.selectedDateTs / 1000);
			const resource = this.getResource(id);
			const visible = loadedResourcesIds.includes(id) && resource && this.selectedTypes[resource.typeId];

			return !visible;
		},
		getResource(id: number): ResourceModel
		{
			return this.$store.getters['resources/getById'](id);
		},
		getResourceType(id: number): ResourceTypeModel
		{
			return this.$store.getters['resourceTypes/getById'](id);
		},
		async search(query: string): Promise<void>
		{
			this.query = query;

			this.dialog.search(this.query);
			this.updateItems();
			this.dialog.getSearchTab().getStub().hide();
			this.dialog.getSearchTab().getSearchLoader().show();

			await resourceDialogService.doSearch(this.query, this.selectedDateTs / 1000);

			this.dialog.search(this.query);
			this.updateItems();
			this.dialog.getSearchTab().getSearchLoader().hide();
			if (this.dialog.getSearchTab().isEmptyResult())
			{
				this.dialog.getSearchTab().getStub().show();
			}
		},
		startResize(): void
		{
			this.dialog.freeze();
		},
		endResize(): void
		{
			setTimeout(() => this.dialog.unfreeze());
		},
		selectAll(): void
		{
			this.dialog.getItems().forEach((item: Item) => {
				if (!item.isHidden())
				{
					item.select();
				}
			});
		},
		deselectAll(): void
		{
			this.dialog.getItems().forEach((item: Item) => {
				if (!item.isHidden())
				{
					item.deselect();
				}
			});
		},
		setWorkloadRef(element: HTMLElement, id: number): void
		{
			this.workloadRefs[id] = element;
		},
		tryShowAhaMoment(): void
		{
			if (ahaMoments.shouldShow(AhaMoment.SelectResources))
			{
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
				void this.showAhaMoment();
			}
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.show({
				id: 'booking-select-resources',
				title: this.loc('BOOKING_AHA_SELECT_RESOURCES_TITLE'),
				text: this.loc('BOOKING_AHA_SELECT_RESOURCES_TEXT'),
				article: HelpDesk.AhaSelectResources,
				target: this.$refs.button,
			});

			ahaMoments.setShown(AhaMoment.SelectResources);
		},
		reset(): void
		{
			const mainResourceIds: Set<number> = this.mainResourceIds;

			this.dialog.getItems().forEach((item: Item) => {
				if (mainResourceIds.has(item.id))
				{
					item.select();
				}
				else
				{
					item.deselect();
				}
			});
		},
	},
	watch: {
		favoritesIds(): void
		{
			this.updateItems();
		},
		resources: {
			handler(resources: ResourceModel[]): void
			{
				setTimeout(() => this.addItems(resources));
				resourceDialogService.clearMainResourcesCache();
			},
			deep: true,
		},
		selectedTypes: {
			handler(): void
			{
				this.updateItems();
			},
			deep: true,
		},
		isLoaded(): void
		{
			this.tryShowAhaMoment();
		},
	},
	components: {
		DialogHeader,
		DialogFooter,
		ResourceWorkload,
	},
	template: `
		<div
			class="booking-booking-select-resources"
			:class="{'--disabled': isFilterMode}"
			data-element="booking-select-resources"
			ref="button"
			@click="showDialog"
		>
			<div class="ui-icon-set --funnel"></div>
		</div>
		<DialogHeader
			ref="dialogHeader"
			v-model="selectedTypes"
			@search="search"
			@startResize="startResize"
			@endResize="endResize"
			@selectAll="selectAll"
			@deselectAll="deselectAll"
		/>
		<DialogFooter
			v-show="dialogFilled && !isDefaultState"
			ref="dialogFooter"
			@reset="reset"
		/>
		<div class="booking-booking-select-resources-workload-container">
			<template v-for="resource of resources">
				<span class="booking-booking-select-resources-workload" :ref="(el) => setWorkloadRef(el, resource.id)">
					<ResourceWorkload :resourceId="resource.id"/>
				</span>
			</template>
		</div>
	`,
};

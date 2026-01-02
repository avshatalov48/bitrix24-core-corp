import { Loader } from 'main.loader';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { EmptyFilterResultsPopup } from 'booking.component.empty-filter-results-popup';
import { mainPageService } from 'booking.provider.service.main-page-service';
import { dictionaryService } from 'booking.provider.service.dictionary-service';
import { bookingService } from 'booking.provider.service.booking-service';
import { calendarService } from 'booking.provider.service.calendar-service';
import { resourceDialogService } from 'booking.provider.service.resource-dialog-service';
import { SectionAnalytics } from 'booking.lib.analytics';
import { mousePosition } from 'booking.lib.mouse-position';
import {
	filterResultNavigator,
	deletingResourceFilterResultCountActualizer,
} from 'booking.lib.filter-result-navigator';
import type { BookingModel } from 'booking.model.bookings';
import type { ResourceModel } from 'booking.model.resources';
import type { BookingUIFilter } from 'booking.lib.booking-filter';
import type { FilterFields } from 'booking.model.filter';

import { expandOffHours } from '../lib/expand-off-hours/expand-off-hours';
import { Filter as BookingFilter, RequireAttention } from './filter/filter';
import { CountersPanel, CounterItem } from './counters-panel/counters-panel';
import { AfterTitle } from './after-title/after-title';
import { BaseComponent } from './base-component/base-component';
import { MultiBooking } from './multi-booking/multi-booking';
import { Banner } from './banner/banner';
import { Trial } from './trial/trial';
import { CrmFormsButton } from './crm-forms-button/crm-forms-button';

// @vue/component
export const App = {
	name: 'BookingApp',
	components: {
		BaseComponent,
		AfterTitle,
		BookingFilter,
		CountersPanel,
		MultiBooking,
		Banner,
		Trial,
		CrmFormsButton,
		EmptyFilterResultsPopup,
	},
	props: {
		afterTitleContainer: HTMLElement,
		counterPanelContainer: HTMLElement,
		settingsButtonContainer: HTMLElement,
		filterId: {
			type: String,
			required: true,
		},
	},
	data(): Object
	{
		return {
			loadingFilter: false,
			loader: new Loader(),
		};
	},
	computed: {
		...mapGetters({
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			viewDateTs: `${Model.Interface}/viewDateTs`,
			isFilterMode: `${Model.Filter}/isFilterMode`,
			isDeletingResourceFilterMode: `${Model.Filter}/isDeletingResourceFilterMode`,
			deletingResource: `${Model.Filter}/deletingResource`,
			filteredBookingsIds: `${Model.Filter}/filteredBookingsIds`,
			selectedCells: `${Model.Interface}/selectedCells`,
			resourcesIds: `${Model.Favorites}/get`,
			extraResourcesIds: `${Model.Interface}/extraResourcesIds`,
			bookings: `${Model.Bookings}/get`,
			getBookingsByIds: `${Model.Bookings}/getByIds`,
			getFutureBookingsByResourceId: `${Model.Bookings}/getFutureByResourceId`,
			intersections: `${Model.Interface}/intersections`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			fetchingNextDate: `${Model.Filter}/fetchingNextDate`,
			datesCount: `${Model.Filter}/datesCount`,
			requestFields: `${Model.Filter}/requestFields`,
		}),
		hasSelectedCells(): boolean
		{
			return Object.keys(this.selectedCells).length > 0;
		},
		editingBooking(): BookingModel | null
		{
			return this.$store.getters['bookings/getById'](this.editingBookingId) ?? null;
		},
	},
	watch: {
		selectedDateTs(): void
		{
			if (this.isFilterMode)
			{
				void this.applyFilter();
			}
			else if (this.isDeletingResourceFilterMode)
			{
				void this.applyDeletingResourceFilter(this.deletingResource);
			}
			else
			{
				void this.fetchPage(this.selectedDateTs / 1000);
			}
		},
		filteredBookingsIds(): void
		{
			if (this.isFilterMode)
			{
				this.showResourcesWithBookings();
			}
		},
		isFilterMode(isFilterMode: boolean): void
		{
			if (!isFilterMode)
			{
				void this.fetchPage(this.selectedDateTs / 1000);
			}
		},
		isDeletingResourceFilterMode(isDeletingResourceFilterMode: boolean): void
		{
			if (isDeletingResourceFilterMode)
			{
				deletingResourceFilterResultCountActualizer.subscribe();
			}
			else
			{
				deletingResourceFilterResultCountActualizer.unsubscribe();
				void this.clearDeletingResourceFilter();
				void this.fetchPage(this.selectedDateTs / 1000);
			}
		},
		viewDateTs(): void
		{
			void this.updateMarks();
		},
		resourcesIds(resourcesIds: number[]): void
		{
			if (this.isDeletingResourceFilterMode)
			{
				if (resourcesIds.includes(this.deletingResource.id))
				{
					this.$store.dispatch(`${Model.Interface}/setPinnedResourceIds`, [...resourcesIds]);
				}
				else
				{
					void this.clearDeletingResourceFilter();
				}
			}

			void this.updateMarks();
		},
		intersections(): void
		{
			void this.updateMarks();
		},
		editingBooking(booking: BookingModel | null): void
		{
			const additionalResourcesIds = booking?.resourcesIds?.slice(1) ?? [];
			if (additionalResourcesIds.length > 0)
			{
				void this.$store.dispatch(`${Model.Interface}/setIntersections`, {
					0: additionalResourcesIds,
				});
			}
		},
		fetchingNextDate(fetching: boolean): void
		{
			if (fetching)
			{
				this.showLoader();
			}
			else
			{
				this.hideLoader();
			}
		},
		deletingResource(resource: ResourceModel | null): void
		{
			if (resource !== null)
			{
				void this.applyDeletingResourceFilter(resource, true);
			}
		},
	},
	beforeMount(): void
	{
		mousePosition.init();
	},
	async mounted(): Promise<void>
	{
		this.showLoader();
		expandOffHours.setExpanded(true);
		this.addAfterTitle();

		SectionAnalytics.sendOpenSection();

		await Promise.all([
			dictionaryService.fetchData(),
			this.fetchPage(this.editingBookingId > 0 ? 0 : this.selectedDateTs / 1000),
		]);

		void this.$store.dispatch(`${Model.Interface}/setIsLoaded`, true);
	},
	beforeUnmount(): void
	{
		mousePosition.destroy();
	},
	methods: {
		async fetchPage(dateTs: number = 0): Promise<void>
		{
			this.showLoader();

			await mainPageService.fetchData(dateTs);

			if (this.extraResourcesIds.length > 0)
			{
				await resourceDialogService.loadByIds(
					this.extraResourcesIds,
					this.selectedDateTs / 1000,
				);
			}

			this.hideLoader();
		},
		onActiveItem(counterItem: string | null): void
		{
			if (this.ignoreConterPanel)
			{
				return;
			}

			const fields = this.getFilterFieldsByCounterItem(counterItem);
			this.$refs.filter.setFields(fields);
		},
		getFilterFields(): BookingUIFilter
		{
			const fields = this.$refs.filter.getFields();

			if (this.isDeletingResourceFilterMode && 'RESOURCE' in fields && Object.keys(fields).length === 1)
			{
				return {
					RESOURCE: this.$store.getters[`${Model.Interface}/resourcesIds`],
				};
			}

			return this.$refs.filter.getFields();
		},
		async applyFilter({ fromFilter = false }: ApplyFilterParams = {}): Promise<void>
		{
			const fields = this.$refs.filter.getFields();

			this.setCounterItem(this.getCounterItemByFilterFields(fields));
			this.showLoader();

			await this.$store.dispatch(`${Model.Filter}/setFilterFields`, this.$refs.filter.getFields());
			await Promise.all([
				this.$store.dispatch(`${Model.Filter}/setFilterMode`, true),
				this.updateMarks(),
				// eslint-disable-next-line unicorn/no-array-callback-reference
				bookingService.filter(this.requestFields),
			]);

			if (fromFilter && (this.filteredBookingsIds.length === 0 || Date.now() > this.selectedDateTs))
			{
				await this.tryNavigateToOptimalFilterResult();
			}

			this.hideLoader();
		},
		async applyDeletingResourceFilter(resource: ResourceModel, force = false): Promise<void>
		{
			if (
				!force
				&& this.isDeletingResourceFilterMode
				&& !this.isDeletionResourceFilterFields(this.$refs.filter.getFields())
			)
			{
				await this.$store.dispatch(`${Model.Filter}/setFilterMode`, true);
				await this.clearDeletingResourceFilter(true);
				await this.applyFilter({ fromFilter: true });

				return;
			}

			const fields: BookingUIFilter = {
				RESOURCE: [resource.id.toString()],
				RESOURCE_label: [resource.name],
			};
			if (force)
			{
				this.$refs.filter.setFields(fields);
			}

			const requestFields = this.getFilterFields();

			this.showLoader();

			await Promise.all([
				this.$store.dispatch(`${Model.Filter}/setFilterFields`, fields),
				this.$store.dispatch(`${Model.Filter}/setDeletionResourceFilterFields`, requestFields),
				this.$store.dispatch(`${Model.Interface}/setPinnedResourceIds`, requestFields.RESOURCE),
			]);
			await Promise.all([
				this.updateMarks(),
				// eslint-disable-next-line unicorn/no-array-callback-reference
				bookingService.filter(this.requestFields),
			]);

			if (force && this.getFutureBookingsByResourceId(resource.id).length === 0)
			{
				await this.tryNavigateToOptimalFilterResult(true);
			}

			this.hideLoader();
		},
		isDeletionResourceFilterFields(fields: FilterFields): boolean
		{
			return (
				Object.keys(fields).length === 1
				&& 'RESOURCE' in fields
				&& fields.RESOURCE.length === 1
				&& fields.RESOURCE[0] === this.deletingResource.id.toString()
			);
		},
		setCounterItem(item: string | null): void
		{
			this.ignoreConterPanel = true;
			setTimeout(() => {
				this.ignoreConterPanel = false;
			}, 0);

			this.$refs.countersPanel.setItem(item);
		},
		getCounterItemByFilterFields(filterFields: BookingUIFilter): string | null
		{
			return {
				[RequireAttention.AwaitConfirmation]: CounterItem.AwaitConfirmation,
				[RequireAttention.Delayed]: CounterItem.Delayed,
			}[filterFields.REQUIRE_ATTENTION];
		},
		async tryNavigateToOptimalFilterResult(inFuture = false): Promise<void>
		{
			const dateTs = await filterResultNavigator.getOptimalFilterDateTs(inFuture);

			if (dateTs && dateTs !== this.selectedDateTs)
			{
				await this.$store.dispatch(`${Model.Interface}/setSelectedDateTs`, dateTs);
			}
		},
		getFilterFieldsByCounterItem(counterItem: string | null): BookingUIFilter
		{
			const fields = this.$refs.filter.getFields();

			fields.REQUIRE_ATTENTION = {
				[CounterItem.AwaitConfirmation]: RequireAttention.AwaitConfirmation,
				[CounterItem.Delayed]: RequireAttention.Delayed,
			}[counterItem];

			return fields;
		},
		async clearFilter(): Promise<void>
		{
			this.setCounterItem(null);

			calendarService.clearFilterCache();
			bookingService.clearFilterCache();

			await Promise.all([
				this.$store.dispatch(`${Model.Interface}/setResourcesIds`, this.resourcesIds),
				this.$store.dispatch(`${Model.Filter}/setFilterMode`, false),
				this.$store.dispatch(`${Model.Filter}/setFilteredBookingsIds`, []),
				this.$store.dispatch(`${Model.Filter}/setFilteredMarks`, []),
				this.$store.dispatch(`${Model.Filter}/clearFilter`, {}),
				this.$store.dispatch(`${Model.Interface}/setPinnedResourceIds`, []),
			]);

			this.hideLoader();
		},
		async clearDeletingResourceFilter(modeOnly = false): Promise<void>
		{
			await Promise.all([
				this.$store.dispatch(`${Model.Filter}/setDeletingResourceFilter`, null),
				this.$store.dispatch(`${Model.Interface}/setPinnedResourceIds`, []),
			]);

			if (this.isFilterMode || modeOnly)
			{
				return;
			}

			this.$refs.filter.setFields({
				RESOURCE: [],
				RESOURCE_label: [],
			});
			await this.clearFilter();
		},
		clearDeletingResourceFilterFields(): void
		{
			if (this.isDeletionResourceFilterFields(this.$refs.filter.getFields()))
			{
				this.$refs.filter.setFields({
					RESOURCE: [],
					RESOURCE_label: [],
				});
			}
		},
		addAfterTitle(): void
		{
			this.afterTitleContainer.append(this.$refs.afterTitle.$el);
		},
		showResourcesWithBookings(): void
		{
			const resourcesIds = this.$store.getters[`${Model.Bookings}/getByDateAndIds`](
				this.selectedDateTs,
				this.filteredBookingsIds,
			)
				.map((booking: BookingModel) => booking.resourcesIds[0])
				.filter((value, index, array) => array.indexOf(value) === index);

			void this.$store.dispatch(`${Model.Interface}/setResourcesIds`, resourcesIds);
		},
		async updateMarks(): Promise<void>
		{
			if (this.isFilterMode || this.isDeletingResourceFilterMode)
			{
				await Promise.all([
					this.updateFilterBookingsCount(),
					this.updateFilterMarks(),
				]);
			}
			else
			{
				await Promise.all([
					this.updateFreeMarks(),
					this.updateCounterMarks(),
				]);
			}
		},
		async updateFreeMarks(): Promise<void>
		{
			const resources = this.resourcesIds.map((id: number) => [
				id,
				...(this.intersections[0] ?? []),
				...(this.intersections[id] ?? []),
			]);

			await this.$store.dispatch(`${Model.Interface}/setFreeMarks`, []);

			await calendarService.loadMarks(this.viewDateTs, resources);
		},
		async updateFilterMarks(): Promise<void>
		{
			const fields = this.$refs.filter.getFields();

			await this.$store.dispatch(`${Model.Filter}/setFilteredMarks`, []);

			await calendarService.loadFilterMarks(fields, this.isDeletingResourceFilterMode);
		},
		async updateCounterMarks(): Promise<void>
		{
			await calendarService.loadCounterMarks(this.viewDateTs);
		},
		async updateFilterBookingsCount(): Promise<void>
		{
			const fields = this.$refs.filter.getFields();

			await calendarService.loadBookingsDateCount(fields, this.isDeletingResourceFilterMode);
		},
		showLoader(): void
		{
			this.loadingFilter = true;
			void this.loader.show(this.$refs.baseComponent.$el);
		},
		hideLoader(): void
		{
			this.loadingFilter = false;
			void this.loader.hide();
		},
	},
	template: `
		<div>
			<MultiBooking v-if="hasSelectedCells"/>
			<AfterTitle ref="afterTitle"/>
			<CrmFormsButton :container="settingsButtonContainer"/>
			<BookingFilter
				:filterId="filterId"
				ref="filter"
				@apply="isDeletingResourceFilterMode ? applyDeletingResourceFilter(deletingResource) : applyFilter({ fromFilter: true })"
				@clear="clearFilter"
			/>
			<EmptyFilterResultsPopup
				v-if="isFilterMode && !isDeletingResourceFilterMode && !loadingFilter && !fetchingNextDate && datesCount.count === 0"
			/>
			<CountersPanel
				:target="counterPanelContainer"
				ref="countersPanel"
				@activeItem="onActiveItem"
			/>
			<BaseComponent ref="baseComponent"/>
			<Banner/>
			<Trial/>
		</div>
	`,
};

type ApplyFilterParams = {
	fromFilter?: boolean,
};

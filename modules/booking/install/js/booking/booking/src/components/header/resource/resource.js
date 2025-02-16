import { mapGetters } from 'ui.vue3.vuex';
import { CrmEntity, Model } from 'booking.const';
import { currencyFormat } from 'booking.lib.currency-format';
import type { BookingModel, DealData } from 'booking.model.bookings';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceTypeModel } from 'booking.model.resource-types';

import { ResourceWorkload } from './resource-workload/resource-workload';
import { ResourceMenu } from './resource-menu/resource-menu';
import './resource.css';

export const Resource = {
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
	},
	data(): Object
	{
		return {
			visible: true,
		};
	},
	mounted(): void
	{
		this.updateVisibility();
		this.updateVisibilityDuringTransition();
	},
	computed: {
		...mapGetters({
			resourcesIds: `${Model.Interface}/resourcesIds`,
			zoom: `${Model.Interface}/zoom`,
			scroll: `${Model.Interface}/scroll`,
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
		}),
		resource(): ResourceModel
		{
			return this.$store.getters[`${Model.Resources}/getById`](this.resourceId);
		},
		resourceType(): ResourceTypeModel
		{
			return this.$store.getters[`${Model.ResourceTypes}/getById`](this.resource.typeId);
		},
		profit(): string
		{
			const currencyId = currencyFormat.getBaseCurrencyId();
			const deals = this.bookings
				.map(({ externalData }) => externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null)
				.filter((deal: DealData | null) => deal?.data?.currencyId === currencyId)
			;

			if (deals.length === 0)
			{
				return '';
			}

			const uniqueDeals: DealData[] = [...new Map(deals.map((it) => [it.value, it])).values()];

			const profit = uniqueDeals.reduce((sum: number, deal: DealData) => sum + deal.data.opportunity, 0);

			return currencyFormat.format(currencyId, profit);
		},
		bookings(): BookingModel[]
		{
			return this.$store.getters[`${Model.Bookings}/getByDateAndResources`](this.selectedDateTs, [this.resourceId]);
		},
	},
	methods: {
		updateVisibilityDuringTransition(): void
		{
			this.animation?.stop();
			this.animation = new BX.easing({
				duration: 200,
				start: {},
				finish: {},
				step: this.updateVisibility,
			});
			this.animation.animate();
		},
		updateVisibility(): void
		{
			if (!this.$refs.container)
			{
				return;
			}

			const rect = this.$refs.container.getBoundingClientRect();
			this.visible = rect.right > 0 && rect.left < window.innerWidth;
		},
	},
	watch: {
		scroll(): void
		{
			this.updateVisibility();
		},
		zoom(): void
		{
			this.updateVisibility();
		},
		resourcesIds(): void
		{
			this.updateVisibilityDuringTransition();
		},
	},
	components: {
		ResourceMenu,
		ResourceWorkload,
	},
	template: `
		<div
			class="booking-booking-header-resource"
			data-element="booking-resource"
			:data-id="resourceId"
			ref="container"
		>
			<template v-if="visible">
				<ResourceWorkload
					:resourceId="resourceId"
					:scale="zoom"
					:isGrid="true"
				/>
				<div class="booking-booking-header-resource-title">
					<div class="booking-booking-header-resource-name" :title="resource.name">
						{{ resource.name }}
					</div>
					<div class="booking-booking-header-resource-type" :title="resourceType.name">
						{{ resourceType.name }}
					</div>
				</div>
				<div class="booking-booking-header-resource-profit" v-html="profit"></div>
				<div class="booking-booking-header-resource-actions">
					<ResourceMenu :resource-id/>
				</div>
			</template>
		</div>
	`,
};

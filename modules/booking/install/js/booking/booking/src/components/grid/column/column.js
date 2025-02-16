import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { range } from 'booking.lib.range';
import { Model } from 'booking.const';
import type { ResourceModel } from 'booking.model.resources';
import { Cell } from './cell/cell';
import { OffHours } from './off-hours/off-hours';
import './column.css';

const { mapGetters: mapInterfaceGetters } = createNamespacedHelpers(Model.Interface);

export const Column = {
	props: {
		resourceId: Number,
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
		...mapInterfaceGetters({
			resourcesIds: 'resourcesIds',
			zoom: 'zoom',
			scroll: 'scroll',
			selectedDateTs: 'selectedDateTs',
			offHoursHover: 'offHoursHover',
			offHoursExpanded: 'offHoursExpanded',
			fromHour: 'fromHour',
			toHour: 'toHour',
			offset: 'offset',
		}),
		resource(): ResourceModel
		{
			return this.$store.getters['resources/getById'](this.resourceId);
		},
		fromMinutes(): number
		{
			return this.fromHour * 60;
		},
		toMinutes(): number
		{
			return this.toHour * 60;
		},
		slotSize(): number
		{
			return this.resource.slotRanges[0]?.slotSize ?? 60;
		},
		offHoursTopCells(): CellDto[]
		{
			return this.cells.filter((it: CellDto) => it.minutes < this.fromMinutes);
		},
		workTimeCells(): CellDto[]
		{
			return this.cells.filter((it: CellDto) => it.minutes >= this.fromMinutes && it.minutes < this.toMinutes);
		},
		offHoursBottomCells(): CellDto[]
		{
			return this.cells.filter((it: CellDto) => it.minutes >= this.toMinutes);
		},
		cells(): CellDto[]
		{
			const hour = 3600 * 1000;
			const from = this.selectedDateTs;
			const to = new Date(from).setDate(new Date(from).getDate() + 1);

			return range(from, to - hour, hour).map((fromTs) => {
				const toTs = fromTs + this.slotSize * 60 * 1000;

				return {
					id: `${this.resource.id}-${fromTs}-${toTs}`,
					fromTs,
					toTs,
					minutes: new Date(fromTs + this.offset).getHours() * 60,
					resourceId: this.resource.id,
				};
			});
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
			const rect = this.$el.getBoundingClientRect();
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
		Cell,
		OffHours,
	},
	template: `
		<div
			class="booking-booking-grid-column"
			data-element="booking-grid-column"
			:data-id="resourceId"
		>
			<template v-if="visible">
				<OffHours/>
				<div class="booking-booking-grid-off-hours-cells">
					<Cell v-for="cell of offHoursTopCells" :key="cell.id" :cell="cell"/>
				</div>
				<Cell v-for="cell of workTimeCells" :key="cell.id" :cell="cell"/>
				<div class="booking-booking-grid-off-hours-cells --bottom">
					<Cell v-for="cell of offHoursBottomCells" :key="cell.id" :cell="cell"/>
				</div>
				<OffHours :bottom="true"/>
			</template>
		</div>
	`,
};

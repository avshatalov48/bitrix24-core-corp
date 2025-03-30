import { Dom, Event } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

import { Core } from 'booking.core';
import { AhaMoment, HelpDesk } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { Maximize } from '../../../lib/maximize/maximize';
import './scale-panel.css';

export const ScalePanel = {
	props: {
		getColumnsContainer: Function,
	},
	data(): Object
	{
		return {
			isSlider: Core.getParams().isSlider,
			maximize: new Maximize({
				onOverlayClick: () => this.collapse(),
			}),
			desiredZoom: this.$store.getters['interface/zoom'],
			minZoom: 0.5,
			maxZoom: 1,
		};
	},
	mounted(): void
	{
		if (location.hash === '#maximize')
		{
			void this.maximize.maximize();
		}
	},
	computed: {
		...mapGetters({
			zoom: 'interface/zoom',
			expanded: 'interface/expanded',
		}),
		zoomFormatted(): string
		{
			return this.loc('BOOKING_BOOKING_ZOOM_PERCENT', {
				'#PERCENT#': Math.round(this.zoom * 100),
			});
		},
	},
	methods: {
		expand(event: MouseEvent): void
		{
			if (location.hash === '#maximize' || this.isAnyModifierKeyPressed(event))
			{
				void this.maximize.maximize();
			}
			else
			{
				window.open(`${location.href}#maximize`, '_blank').focus();
			}
		},
		isAnyModifierKeyPressed(event: MouseEvent): boolean
		{
			return event.altKey || event.shiftKey || event.ctrlKey || event.metaKey;
		},
		collapse(): void
		{
			void this.maximize.minimize();
		},
		fitToScreen(): void
		{
			const sidebarPadding = 260;
			const view = this.getColumnsContainer();
			const zoomCoefficient = (view.offsetWidth - sidebarPadding) / (view.scrollWidth - sidebarPadding);
			const newZoom = Math.floor(this.zoom * zoomCoefficient * 10) / 10;

			this.zoomInto(newZoom);
		},
		zoomInto(zoomInto: number): void
		{
			if (Number.isNaN(zoomInto))
			{
				return;
			}

			const noTransitionClass = '--booking-booking-no-transition';
			const container = Core.getParams().container;
			const maxAnimationDuration = 400;

			this.desiredZoom = Math.max(this.minZoom, Math.min(this.maxZoom, zoomInto));

			if (this.zoom === this.desiredZoom)
			{
				return;
			}

			this.animation?.stop();
			Dom.addClass(container, noTransitionClass);
			this.animation = new BX.easing({
				duration: Math.abs(this.zoom - this.desiredZoom) / this.minZoom * maxAnimationDuration,
				start: { zoom: this.zoom * 100 },
				finish: { zoom: this.desiredZoom * 100 },
				step: ({ zoom }) => this.$store.dispatch('interface/setZoom', zoom / 100),
				complete: () => Dom.removeClass(container, noTransitionClass),
			});
			this.animation.animate();
		},
		async onMouseDown(direction: 1 | -1): Promise<void>
		{
			Event.unbind(window, 'mouseup', this.onMouseUp);
			Event.bind(window, 'mouseup', this.onMouseUp);
			this.mouseDown = true;
			await new Promise((resolve) => setTimeout(resolve, 50));
			if (this.mouseDown)
			{
				clearInterval(this.zoomInterval);
				this.zoomInterval = setInterval(() => this.zoomInto(this.desiredZoom + direction * 0.1), 40);
			}
		},
		onMouseUp(): void
		{
			this.mouseDown = false;
			if (this.desiredZoom > this.zoom)
			{
				this.desiredZoom = Math.ceil(this.zoom * 10) / 10;
			}

			if (this.desiredZoom < this.zoom)
			{
				this.desiredZoom = Math.floor(this.zoom * 10) / 10;
			}

			this.zoomInto(this.desiredZoom);
			clearInterval(this.zoomInterval);
			Event.unbind(window, 'mouseup', this.onMouseUp);
		},
		async showAhaMoment(): Promise<void>
		{
			ahaMoments.setPopupShown(AhaMoment.ExpandGrid);

			await ahaMoments.show({
				id: 'booking-expand-grid',
				title: this.loc('BOOKING_AHA_EXPAND_GRID_TITLE'),
				text: this.loc('BOOKING_AHA_EXPAND_GRID_TEXT'),
				article: HelpDesk.AhaExpandGrid,
				target: this.$refs.expand,
				top: true,
			});

			ahaMoments.setShown(AhaMoment.ExpandGrid);
		},
	},
	template: `
		<div class="booking-booking-grid-scale-panel">
			<div v-if="!isSlider" class="booking-booking-grid-scale-panel-full-screen" ref="expand">
				<div v-if="expanded" class="ui-icon-set --collapse-diagonal" @click="collapse"></div>
				<div v-else class="ui-icon-set --expand-diagonal" @click="expand"></div>
			</div>
			<div class="booking-booking-grid-scale-panel-fit-to-screen">
				<div class="booking-booking-grid-scale-panel-fit-to-screen-text" @click="fitToScreen">
					{{ loc('BOOKING_BOOKING_SHOW_ALL') }}
				</div>
			</div>
			<div class="booking-booking-grid-scale-panel-change">
				<div
					class="ui-icon-set --minus-30"
					:class="{'--disabled': zoom <= minZoom}"
					@click="zoomInto(desiredZoom - 0.1)"
					@mousedown="onMouseDown(-1)"
				></div>
				<div v-html="zoomFormatted" class="booking-booking-grid-scale-panel-zoom"></div>
				<div
					class="ui-icon-set --plus-30"
					:class="{'--disabled': zoom >= maxZoom}"
					@click="zoomInto(desiredZoom + 0.1)"
					@mousedown="onMouseDown(1)"
				></div>
			</div>
		</div>
	`,
};

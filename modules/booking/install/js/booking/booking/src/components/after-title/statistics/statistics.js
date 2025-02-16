import { Event } from 'main.core';
import { StatisticsPopup } from 'booking.component.statistics-popup';

export const Statistics = {
	props: {
		value: {
			type: Number,
			required: true,
		},
		valueFormatted: {
			type: String,
			required: true,
		},
		increasedValue: {
			type: Number,
			required: true,
		},
		increasedValueFormatted: {
			type: String,
			required: true,
		},
		popupId: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		rows: {
			type: Array,
			required: true,
		},
		button: {
			type: Object,
			required: false,
		},
	},
	data(): Object
	{
		return {
			isPopupShown: false,
		};
	},
	methods: {
		onMouseEnter(): void
		{
			this.clearTimeouts();
			this.showTimeout = setTimeout(() => this.showPopup(), 100);
		},
		onMouseLeave(): void
		{
			Event.unbind(document, 'mouseover', this.updateHoverElement);
			Event.bind(document, 'mouseover', this.updateHoverElement);

			this.clearTimeouts();

			if (!this.button)
			{
				this.closePopup();

				return;
			}

			this.closeTimeout = setTimeout(() => {
				this.popupContainer = document.getElementById(this.popupId);
				if (!this.popupContainer?.contains(this.hoverElement) && !this.$refs.container.contains(this.hoverElement))
				{
					this.closePopup();
				}

				if (this.popupContainer)
				{
					Event.unbind(this.popupContainer, 'mouseleave', this.onMouseLeave);
					Event.bind(this.popupContainer, 'mouseleave', this.onMouseLeave);
				}
			}, 100);
		},
		updateHoverElement(event: MouseEvent): void
		{
			this.hoverElement = event.target;
		},
		showPopup(): void
		{
			this.clearTimeouts();
			this.isPopupShown = true;
		},
		closePopup(): void
		{
			this.clearTimeouts();
			this.isPopupShown = false;
			Event.unbind(this.popupContainer, 'mouseleave', this.onMouseLeave);
			Event.unbind(document, 'mouseover', this.updateHoverElement);
		},
		clearTimeouts(): void
		{
			clearTimeout(this.closeTimeout);
			clearTimeout(this.showTimeout);
		},
		close()
		{
			this.closePopup();
			this.$emit('close');
		},
	},
	watch: {
		value(): void
		{
			this.$refs.animation?.replaceWith(this.$refs.animation);
		},
	},
	components: {
		StatisticsPopup,
	},
	template: `
		<div class="booking-toolbar-after-title-info-profit-container" ref="container">
			<div
				v-html="valueFormatted"
				class="booking-toolbar-after-title-info-profit"
				@click="showPopup"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
			></div>
			<div
				v-if="increasedValue > 0"
				v-html="increasedValueFormatted"
				class="booking-toolbar-after-title-profit-increased"
				ref="animation"
			></div>
		</div>
		<StatisticsPopup
			v-if="isPopupShown"
			:popupId="popupId"
			:bindElement="$refs.container"
			:title="title"
			:rows="rows"
			:button="button"
			@close="close"
		/>
	`,
};

import { Dom } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import './now-line.css';

export const NowLine = {
	data(): Object
	{
		return {
			visible: true,
		};
	},
	mounted(): void
	{
		this.updateNowLine();
		setInterval(() => this.updateNowLine(), 1000);
	},
	computed: mapGetters({
		zoom: `${Model.Interface}/zoom`,
		selectedDateTs: `${Model.Interface}/selectedDateTs`,
		offHoursExpanded: `${Model.Interface}/offHoursExpanded`,
		fromHour: `${Model.Interface}/fromHour`,
		toHour: `${Model.Interface}/toHour`,
		offset: `${Model.Interface}/offset`,
	}),
	methods: {
		setVisible(visible: boolean): void
		{
			this.visible = visible;
			this.updateNowLine();
		},
		updateNowLine(): void
		{
			const now = new Date(Date.now() + this.offset);

			const hourHeight = 50 * this.zoom;
			const fromMinutes = this.fromHour * 60;
			const nowMinutes = now.getHours() * 60 + now.getMinutes();
			const toHour = this.offHoursExpanded ? 24 : this.toHour;
			const toMinutes = Math.min(toHour * 60 + 21, nowMinutes);
			const top = (toMinutes - fromMinutes) * (hourHeight / 60);
			Dom.style(this.$refs.nowLine, 'top', `${top}px`);

			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
			const timeFormatted = DateTimeFormat.format(timeFormat, now.getTime() / 1000);
			if (timeFormatted !== this.$refs.nowText.innerText)
			{
				this.$refs.nowText.innerText = timeFormatted;
			}

			const date = new Date(this.selectedDateTs + this.offset);

			const visible = this.visible
				&& Date.UTC(date.getFullYear(), date.getMonth(), date.getDate())
				=== Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())
			;
			Dom.style(this.$refs.nowLine, 'display', visible ? '' : 'none');
		},
	},
	watch: {
		selectedDateTs(): void
		{
			this.updateNowLine();
		},
		zoom(): void
		{
			this.updateNowLine();
		},
		offHoursExpanded(offHoursExpanded: boolean): void
		{
			const now = new Date();
			const nowMinutes = now.getHours() * 60 + now.getMinutes();
			if (nowMinutes < this.toHour * 60)
			{
				return;
			}

			this.setVisible(!offHoursExpanded);
			setTimeout(() => this.setVisible(true), 200);
		},
	},
	template: `
		<div class="booking-booking-grid-now-line" ref="nowLine">
			<div class="booking-booking-grid-now-line-text" ref="nowText"></div>
		</div>
	`,
};

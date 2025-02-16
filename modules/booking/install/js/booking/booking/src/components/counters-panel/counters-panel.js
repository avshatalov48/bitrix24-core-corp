import { EventEmitter } from 'main.core.events';
import { mapGetters } from 'ui.vue3.vuex';
import { CounterPanel } from 'ui.counterpanel';
import { CounterColor } from 'ui.cnt';
import './counters-panel.css';

export const CounterItem = Object.freeze({
	NotConfirmed: 'not-confirmed',
	Delayed: 'delayed',
});

export const CountersPanel = {
	emits: ['activeItem'],
	props: {
		target: HTMLElement,
	},
	mounted(): void
	{
		this.addCounterPanel();

		EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', this.onActiveItem);
		EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', this.onActiveItem);
	},
	computed: mapGetters({
		counters: 'counters/get',
	}),
	methods: {
		setItem(itemId: string | null): void
		{
			if (this.getActiveItem() === itemId)
			{
				return;
			}

			Object.values(CounterItem).forEach((id) => this.counterPanel.getItemById(id).deactivate());

			const item = this.counterPanel.getItemById(itemId);
			item?.activate();
		},
		addCounterPanel(): void
		{
			this.counterPanel = new CounterPanel({
				target: this.target,
				items: [
					{
						id: CounterItem.NotConfirmed,
						title: this.loc('BOOKING_BOOKING_COUNTER_PANEL_NOT_CONFIRMED'),
						value: this.counters.unConfirmed,
						color: getFieldName(CounterColor, CounterColor.THEME),
					},
					{
						id: CounterItem.Delayed,
						title: this.loc('BOOKING_BOOKING_COUNTER_PANEL_DELAYED'),
						value: this.counters.delayed,
					},
				],
			});

			this.counterPanel.init();
		},
		onActiveItem(): void
		{
			this.$emit('activeItem', this.getActiveItem());
		},
		getActiveItem(): string | null
		{
			return this.counterPanel.getItems().find(({ isActive }) => isActive)?.id ?? null;
		},
	},
	watch: {
		counters(counters): void
		{
			this.counterPanel.getItems().forEach((item) => {
				if (item.id === CounterItem.NotConfirmed)
				{
					item.updateColor(getFieldName(CounterColor, CounterColor.DANGER));
					item.updateValue(counters.unConfirmed);
				}

				if (item.id === CounterItem.Delayed)
				{
					item.updateColor(getFieldName(CounterColor, CounterColor.DANGER));
					item.updateValue(counters.delayed);
				}
			});
		},
	},
	template: `
		<div></div>
	`,
};

const getFieldName = (obj, field) => Object.entries(obj).find(([, value]) => value === field)[0];

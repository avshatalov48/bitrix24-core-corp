import { CounterSize, CounterStyle } from 'ui.cnt';
import { Counter } from 'ui.vue3.components.counter';
import { BIcon as UiIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import './entity-list.css';

// @vue/component
export const EntitiesList = {
	name: 'EntitiesList',
	components: {
		UiCounter: Counter,
		UiIcon,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		iconName: {
			type: String,
			required: true,
		},
		entities: {
			type: Array,
			default: () => [],
		},
		hasNoAccess: {
			type: Boolean,
			default: false,
		},
	},
	// eslint-disable-next-line flowtype/require-return-type
	setup()
	{
		return {
			Outline,
			CounterSize,
			CounterStyle,
		};
	},
	data(): { limit: number }
	{
		return {
			limit: 90,
		};
	},
	computed: {
		entitiesCount(): number
		{
			return this.entities.length;
		},
		hasMore(): boolean
		{
			return !this.hasNoAccess && this.entities.length > this.names.length;
		},
		more(): string
		{
			return this.loc('BOOKING_EVENT_POPUP_ENTITIES_MORE', {
				'#COUNT#': (this.entities.length - this.names.length) || '',
			});
		},
		count(): number
		{
			return this.entitiesCount ?? this.entities.length;
		},
		names(): string[]
		{
			if (this.hasNoAccess)
			{
				return [this.loc('BOOKING_EVENT_POPUP_NO_ACCESS')];
			}

			if (this.limit === Infinity)
			{
				return this.entities.map(({ name }) => name);
			}

			let textLength: number = 0;
			const names: string[] = [];

			if (this.entities.length === 1)
			{
				return this.entities.map(({ name }) => name);
			}

			for (const entity of this.entities)
			{
				if (textLength + entity.name.length > this.limit)
				{
					break;
				}

				textLength += entity.name.length;
				names.push(entity.name);
			}

			return names;
		},
	},
	template: `
		<div class="booking-event-popup__resources-item">
			<div class="booking-event-popup__resources-item_icon">
				<UiIcon :name="iconName" :size="20"/>
			</div>
			<div class="booking-event-popup__resources-item_info">
				<div class="booking-event-popup__resources-item_title">
					{{ title }}
					<UiCounter
						v-if="!hasNoAccess"
						:value="count"
						:maxValue="999"
						:size="CounterSize.SMALL"
						:style="CounterStyle.FILLED"
					/>
					<template v-if="hasNoAccess">
						<UiIcon :name="Outline.LOCK_S" :size="20" color="rgb(var(--ui-color-palette-gray-50-rgb))"/>
					</template>
				</div>
				<div
					:class="[
						'booking-event-popup__resources-item_text',
						{ '--no-access': hasNoAccess }
					]"
				>
					{{ names.join(', ') }}{{ hasMore ? ', ' : '' }}
					<span
						v-if="hasMore"
						class="booking-event-popup__resources-item_text-more"
						@click="limit = Infinity"
					>
						{{ more }}
					</span>
				</div>
			</div>
		</div>
	`,
};

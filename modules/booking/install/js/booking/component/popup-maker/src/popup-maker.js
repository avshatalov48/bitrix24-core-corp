import './popup-maker.css';

export type PopupMakerItem = {
	id: string,
	props: Object,
	component: Object,
};

// @vue/component
export const PopupMaker = {
	props: {
		contentStructure: {
			type: Array,
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	template: `
		<div class="booking-popup-maker__content">
			<template v-for="section in contentStructure" :key="section.id">
				<div class="booking-popup-maker__content-section">
					<template v-for="item in [section].flat()" :key="item.id">
						<div class="booking-popup-maker__content-section_item" :class="item.class">
							<component
								v-bind="item.props"
								:is="item.component"
								@freeze="$emit('freeze')"
								@unfreeze="$emit('unfreeze')"
							/>
						</div>
					</template>
				</div>
			</template>
		</div>
	`,
};

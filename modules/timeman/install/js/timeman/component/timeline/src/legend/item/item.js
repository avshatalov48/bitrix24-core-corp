import {BitrixVue} from "ui.vue";
import "./item.css";

export const Item = BitrixVue.localComponent('bx-timeman-component-timeline-legend-item',{
	props: [
		'type',
		'title',
	],
	// language=Vue
	template: `
		<div class="bx-timeman-component-timeline-legend-item">
			<div 
				:class="[ 
					'bx-timeman-component-timeline-legend-item-marker',
					type ? 'bx-timeman-component-timeline-legend-item-marker-' + type : '',
				]"
			/>
			<div class="bx-timeman-component-timeline-legend-item-title">
				{{ title }}
			</div>
		</div>
	`
});
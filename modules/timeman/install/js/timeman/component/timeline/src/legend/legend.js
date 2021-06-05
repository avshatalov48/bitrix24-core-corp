import {BitrixVue} from "ui.vue";
import "./legend.css";
import {Item} from "./item/item";

export const Legend = BitrixVue.localComponent('bx-timeman-component-timeline-legend',{
	components:
	{
		Item
	},
	props: ['items'],
	// language=Vue
	template: `
		<div class="bx-timeman-component-timeline-legend">
			<transition-group 
				name="bx-timeman-component-timeline-legend"
				class="bx-timeman-component-timeline-legend-container"
			>

				<Item
					v-for="item of items"
					:key="item.id"
					:type="item.type"
					:title="item.title"
				/>

			</transition-group>
		</div>
	`
});
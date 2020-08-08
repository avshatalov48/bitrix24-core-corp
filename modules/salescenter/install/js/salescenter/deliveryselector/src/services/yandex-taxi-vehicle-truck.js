import {Vue} from 'ui.vue';
import {Loc} from 'main.core';

export default {
	template: `
		<div class="salescenter-delivery-car-container">
			<div class="salescenter-delivery-car-image salescenter-delivery-car-image--truck"></div>
			<div class="salescenter-delivery-car-param">
				<table>
					<tr>
						<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH}}</td>
						<td>3 400 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>
					</tr>
					<tr>
						<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WIDTH}}</td>
						<td>1 950 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>
					</tr>
					<tr>
						<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_HEIGHT}}</td>
						<td>1 600 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>
					</tr>
					<tr>
						<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT}}</td>
						<td>2 200 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT_UNIT}}</td>
					</tr>
				</table>
			</div>
		</div>
	`,
	computed: {
		localize() {
			return Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
		}
	}
}

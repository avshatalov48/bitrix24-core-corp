import Car from './yandex-taxi-vehicle-car';
import Truck from './yandex-taxi-vehicle-truck';

export default {
	props: {
		options: {type: Array, required: true},
		name: {type: String, required: true},
		initValue: {type: String, required: false},
		editable: {required: true, type: Boolean},
	},
	data()
	{
		return {
			value: null
		};
	},
	methods: {
		onItemClick(value)
		{
			if (!this.editable)
			{
				return;
			}

			this.value = value;
			this.$emit('change', this.value);
		}
	},
	components: {
		'express': Car,
		'cargo': Truck,
	},
	created()
	{
		this.value = this.initValue;
	},
	template: `
		<div class="salescenter-delivery-car">
			<div v-for="option in options" @click="onItemClick(option.id)" :class="{'salescenter-delivery-car-item': true, 'salescenter-delivery-car-item--selected': option.id == value}" >
				<div class="salescenter-delivery-car-title">{{option.title}}</div>
				<component
					:is="option.code"
					:key="option.code"
				>
				</component>
			</div>
		</div>
	`
};

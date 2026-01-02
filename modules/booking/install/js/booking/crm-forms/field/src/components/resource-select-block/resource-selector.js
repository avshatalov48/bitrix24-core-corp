import './resource-selector.css';

// @vue/component
export const ResourceSelector = {
	name: 'ResourceSelector',
	props: {
		resources: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['select'],
	template: `
		<div class="booking--crm-forms--resource-selector">
			<div
				v-for="(resource) in resources"
				:key="resource.id"
				class="b24-form-control-list-selector-item booking--crm-forms--resource-selector-resource"
				@click="$emit('select', resource)"
			>
				<div>
					<div class="booking--crm-forms--time-selector-block-resource-name">
						{{ resource.name }}
					</div>
					<div class="booking--crm-forms--time-selector-block-resource-type-name">
						{{ resource.typeName }}
					</div>
				</div>
			</div>
		</div>
	`,
};

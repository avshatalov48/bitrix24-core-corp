import { ResourceSelector } from './resource-selector';
// eslint-disable-next-line no-unused-vars
import type { Resource } from '../../types';

// @vue/component
export const ResourceSelectBlock = {
	name: 'ResourceSelectBlock',
	components: {
		ResourceSelector,
	},
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
		/**
		 * @type {Resource[]}
		 */
		resources: {
			type: Array,
			required: true,
		},
		settingsData: {
			type: Object,
			required: true,
		},
		fetching: {
			type: Boolean,
			default: false,
		},
		errorMessage: {
			type: String,
			default: '',
		},
		hasErrors: {
			type: Boolean,
			default: false,
		},
		dependencies: {
			type: Object,
			required: true,
		},
	},
	emits: ['update:resourceId'],
	data(): { dropdownOpened: false }
	{
		return {
			dropdownOpened: false,
		};
	},
	computed: {
		label(): string
		{
			return this.settingsData?.label || '';
		},
		placeholder(): string
		{
			return `${this.settingsData?.textHeader || ''} *`;
		},
		resource(): Object
		{
			return this.resources.find((resource) => resource.id === this.resourceId);
		},
		resourceName(): string
		{
			return this.resource?.name || '';
		},
		hint(): { visible: boolean, text: string }
		{
			return {
				text: this.settingsData?.hint || '',
				visible: Boolean(this.settingsData?.isVisibleHint),
			};
		},
		fieldItemDropdownComponent(): Object
		{
			return this.dependencies.mixinDropdown.components['field-item-dropdown'];
		},
	},
	methods: {
		toggleDropdown()
		{
			if (this.dropdownOpened)
			{
				this.closeDropdown();

				return;
			}

			if (this.fetching)
			{
				return;
			}

			this.dropdownOpened = true;
		},
		closeDropdown()
		{
			setTimeout(() => {
				this.dropdownOpened = false;
			}, 0);
		},
		setResource(resource): void
		{
			this.$emit('update:resourceId', resource.id);

			if (this.dropdownOpened)
			{
				this.closeDropdown();
			}
		},
	},
	template: `
		<div
			class="booking-crm-forms-field"
			:class="{
				'--error': hasErrors,
			}"
		>
			<div class="b24-form-field-layout-section booking-crm-forms-field-title">
				{{ label }}
			</div>
			<div
				ref="tagSelector"
				class="booking-crm-forms-field-tag-selector b24-form-control-string"
				:class="{
					'--disabled': fetching,
				}"
			>
				<div
					class="b24-form-control-container b24-form-control-icon-after"
					@click="toggleDropdown"
				>
					<input
						name="resourceName"
						type="text"
						readonly
						:placeholder="placeholder"
						:value="resourceName"
						class="b24-form-control booking--crm-forms--field-tag-selector-input"
						@click.capture.stop.prevent="toggleDropdown"
					/>
					<div class="booking--crm-forms--field-tag-selector-input-icon"></div>
				</div>
			</div>
			<div class="b24-form-control-alert-message" style="top: 75px">{{ errorMessage }}</div>
			<component
				v-if="dropdownOpened"
				:is="fieldItemDropdownComponent"
				:marginTop="0"
				:visible="dropdownOpened"
				:title="label"
				@close="closeDropdown()"
			>
				<ResourceSelector :resources="resources" @select="setResource"/>
			</component>
			<div v-if="hint.visible" class="booking--crm-forms--field-hint">
				<div class="booking--crm-forms--field-hint-text">{{ hint.text }}</div>
			</div>
		</div>
	`,
};

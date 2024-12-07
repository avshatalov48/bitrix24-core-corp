import '../css/permissioncontrol.css';
import { Event, Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

export const PermissionControl = {
	name: 'PermissionControl',
	props: {
		value: {
			type: String,
			required: true,
		},
		valuesMap: {
			type: Object,
			required: true,
		},
		permissionIdentifier: {
			type: Object,
			required: true,
		},
	},
	emits: ['valueChanged'],
	data() {
		return {
			label: {
				inherit: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_INHERIT'),
			},
			isEditMode: false,
		};
	},
	computed: {
		...mapGetters(['getAssignedAttribute']),
		currentPermissionName()
		{
			if (
				this.permissionIdentifier.permissionCode === 'AUTOMATION'
				&& this.isReadOnlyMode
			)
			{
				const forcePerm = 'X';

				return this.valuesMap[forcePerm];
			}

			if (this.isStageControl && this.model === '-')
			{
				return this.getParentName;
			}

			return this.valuesMap[this.value];
		},
		isInherited() {
			return this.isStageControl && this.model === '-';
		},
		model: {
			get() {
				return this.value;
			},
			set(value) {
				this.$emit('valueChanged', { identifier: this.permissionIdentifier, value });
			},
		},
		isStageControl() {
			return Boolean(this.permissionIdentifier.stageField);
		},
		getParentName() {
			const parentChose = this.getAssignedAttribute({
				permissionCode: this.permissionIdentifier.permissionCode,
				entityCode: this.permissionIdentifier.entityCode,
			}) || '';

			return this.valuesMap[parentChose];
		},
		isReadOnlyMode(): boolean {
			if (this.permissionIdentifier.permissionCode === 'AUTOMATION')
			{
				return this.getAssignedAttribute({ permissionCode: 'WRITE', entityCode: 'CONFIG' }) === 'X';
			}

			return false;
		},
	},
	methods: {
		toEditMode(e) {
			e.stopPropagation();
			if (this.isReadOnlyMode)
			{
				return;
			}
			this.isEditMode = true;
			Event.bind(window, 'click', this.windowClickListener);
		},
		windowClickListener(event) {
			if (
				event.target !== this.$refs.componentRef
				&& !event.composedPath().includes(this.$refs.componentRef)
			)
			{
				this.isEditMode = false;
				Event.unbind(window, 'click', this.windowClickListener);
			}
		},
	},
	template: `
		<div 
			class="bx-crm-perms-edit-permission_control"
			ref="componentRef"
		>
			<div 
				class="bx-crm-perms-edit-permission_control-text"
				:class="{'--readonly': isReadOnlyMode, '--inherited': isInherited}"
				v-if="!isEditMode"
				@click="toEditMode"
			>{{ currentPermissionName }}</div>
			<select 
				v-model="model" 
				v-if="isEditMode" 
				class="bx-crm-perms-edit-permission_control-select"
			>
				<option 
					class="bx-crm-perms-edit-permission_control-select-option__grey" 
					v-if="isStageControl" 
					value="-"
				>{{ label.inherit }}</option>
				<option v-for="val in Object.keys(valuesMap)" :value="val">
					{{ valuesMap[val] }}
				</option>
			</select>
		</div>
	`,
};

import { Loc } from 'main.core';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { SliderManager } from '../slider/slider-manager';
import { MAX_SORT_ORDER_ON_THE_DESK, PermissionEntityIdentifier, Permissions } from '../store';
import { PermissionControl } from './permissioncontrol';
import { ExpandControl } from './expandcontrol';

export default {
	name: 'Row',
	props: {
		entity: Object,
	},
	data() {
		return {
			labels: {
				moreName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_MORE'),
			},
			maxOrder: 0,
		};
	},
	components: {
		PermissionControl,
		ExpandControl,
	},
	computed: {
		...mapGetters([
			'availablePermissions',
			'isStageEntitiesVisible',
			'getAssignedAttribute',
			'permissionsOnMainView',
			'groupEntityPermission',
			'getAvailablePermissionsOrders',
		]),
		isExpandedStages() {
			return this.isStageEntitiesVisible(this.entity);
		},
		isShowMore(): boolean {
			return this.maxOrder > MAX_SORT_ORDER_ON_THE_DESK;
		},
	},
	mounted()
	{
		let maxOrder = 0;
		for (const permCode of Object.keys(this.entity.permissions))
		{
			maxOrder = Math.max(maxOrder, this.getAvailablePermissionsOrders[permCode] || 0);
		}

		this.maxOrder = maxOrder;
	},
	methods: {
		...mapMutations(['toggleStagesVisibility', 'assignPermissionAttribute', 'setEditMode']),
		expandChanged() {
			this.toggleStagesVisibility(this.entity);
		},
		openSlider() {

			SliderManager.create(this.entity.entityCode, this.$store).open();
		},
		attributeValue(permissionCode) {
			const identifier = this.getIdentifier(permissionCode);

			return this.getAssignedAttribute(identifier) || '';
		},
		onAttributeValueChanged(payload) {
			this.assignPermissionAttribute({ identifier: payload.identifier, value: payload.value });
		},
		getIdentifier(permissionCode): PermissionEntityIdentifier {
			return {
				permissionCode,
				entityCode: this.entity.entityCode,
				stageField: this.entity.stageField,
				stageCode: this.entity.stageCode,
			};
		},
		isShowControl(perm: Permissions): boolean {
			if (!this.entity.permissions[perm.code])
			{
				return false;
			}

			if (this.entity.stageField && perm.canAssignPermissionToStages === false)
			{
				return false;
			}

			return true;
		},
	},
	template: `
		<tr 
			class="bx-crm-perms-desk-row bx-crm-perms-edit-desk__body"
		>
			<td class="bx-crm-perms-desk-row-item">
				<ExpandControl 
					v-if="entity.hasStages" 
					:is-expanded="isExpandedStages" 
					@toggle="expandChanged" 
				/> 
				<span :class="{'stage-row': !!entity.stageField}">{{ entity.name }}</span>
			</td>
			<td
				v-for="perm of permissionsOnMainView"
				class="bx-crm-perms-desk-row-item"
				:data-permission-code="perm.code"
				:data-permission-entity="entity.entityCode"
				:data-permission-attr-field="entity.stageField"
				:data-permission-attr-value="entity.stageCode"
			>
				<PermissionControl
					v-if="isShowControl(perm)"
					:value="attributeValue(perm.code)"
					:values-map="entity.permissions[perm.code]"
					:permission-identifier="getIdentifier(perm.code)"
					@value-changed="onAttributeValueChanged"
				></PermissionControl>
			</td>
			<td 
				v-if="!entity.stageField"
				class="bx-crm-perms-desk-row-item"
				data-permission-code="MORE"
				:data-permission-entity="entity.entityCode"
			>
				<span
					v-if="isShowMore"
					class="bx-crm-perms-edit-permission_control-text bx-crm-perms-desk-row-more"
					@click="openSlider"
				>
					{{ labels.moreName }}
				</span>
			</td>
		</tr>
	`,
};

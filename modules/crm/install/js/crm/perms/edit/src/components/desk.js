import 'ui.design-tokens';
import '../css/desk.css';
import { Loc } from 'main.core';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { MAX_SORT_ORDER_ON_THE_DESK } from './../store';
import { PermissionControl } from './permissioncontrol';
import Row from './row';

export const Desk = {
	name: 'Desk',
	components: {
		PermissionControl,
		Row,
	},
	data() {
		return {
			labels: {
				allowToChangeConfigName: Loc.getMessage('CRM_PERMS_EDIT_ALLOW_TO_CHANGE_CONFIG'),
				saveName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_SAVE'),
				applyName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_APPLY'),
				roleName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_ROLE'),
				notAllowedByTariff: Loc.getMessage('CRM_PERMS_EDIT_RESTRICTION'),
				roleDelete: Loc.getMessage('CRM_PERMS_EDIT_ROLE_DELETE'),
				additionalName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_ADDITIONAL'),
			},
		};
	},
	computed: {
		...mapGetters([
			'permissionEntitiesExpanded',
			'permissionsOnMainView',
			'getAssignedAttribute',
		]),
		configWrite: {
			get() {
				return this.getAssignedAttribute({ permissionCode: 'WRITE', entityCode: 'CONFIG' }) === 'X';
			},
			set(value) {
				this.assignPermissionAttribute({
					value: value ? 'X' : '',
					identifier: { permissionCode: 'WRITE', entityCode: 'CONFIG' },
				});
			},
		},
		columnsCount() {
			return MAX_SORT_ORDER_ON_THE_DESK + 2;
		},

	},
	methods: {
		...mapMutations(['assignPermissionAttribute']),
		getStages(entity) {
			return entity.fields.STAGE_ID || {};
		},
	},
	mounted() {},
	template: `
		<table class="bx-crm-perms-edit-desk">
			<tr class="bx-crm-perms-desk-row bx-crm-perms-edit-desk__head">
				<th class="bx-crm-perms-desk-row-item"></th>
				<th
					v-for="perm in permissionsOnMainView"
					:key="perm.code"
					class="bx-crm-perms-desk-row-item"
				>
					{{ perm.name }}
				</th>
				<th class="bx-crm-perms-desk-row-item">
					{{ labels.additionalName }}
				</th>
			</tr>

			<Row v-for="permissionEntity of permissionEntitiesExpanded" :entity="permissionEntity" />
			
			<tr class="bx-crm-perms-desk-row bx-crm-perms-edit-desk__footer">
				<td class="bx-crm-perms-desk-row-item" :colspan="columnsCount">
					<label>
						<input name="WRITE" type="checkbox" v-model="configWrite">
						{{ labels.allowToChangeConfigName }}
					</label>
				</td>
			</tr>
		</table>
	`,
};

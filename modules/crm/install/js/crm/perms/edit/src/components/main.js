import './../css/main.css';
import { Loc } from 'main.core';
import { mapActions, mapGetters, mapMutations } from 'ui.vue3.vuex';
import { Desk } from './desk';
import { Loader } from './loader';

export const Main = {
	name: 'Main',
	components: {
		Desk,
		Loader,
	},
	data() {
		return {
			labels: {
				saveName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_SAVE'),
				applyName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_APPLY'),
				roleName: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_ROLE'),
				notAllowedByTariff: Loc.getMessage('CRM_PERMS_EDIT_RESTRICTION'),
				roleDelete: Loc.getMessage('CRM_PERMS_EDIT_ROLE_DELETE'),
			},
		};
	},
	computed: {
		...mapGetters([
			'hasTariffPermission',
			'getRoleName',
			'getRestrictionScript',
			'getLastErrorMessage',
			'getRoleId',
			'isSaveInProgress',
		]),
		roleName: {
			get() {
				return this.getRoleName;
			},
			set(val) {
				this.setRoleName(val);
			},
		},
		isShowDeleteBtn(): boolean {
			return this.getRoleId > 0;
		},
	},
	methods: {
		...mapMutations(['setRoleName']),
		...mapActions(['saveRolePermission', 'deleteRole']),
		async onPressSave() {
			await this.performSave('save');
		},
		async onPressApply() {
			await this.performSave('apply');
		},
		async onPressDeleteRole() {
			if (!this.hasTariffPermission)
			{
				this.executeTariffRestrictionScript();
			}

			await this.deleteRole();
		},
		async performSave(action: string) {
			if (!this.hasTariffPermission)
			{
				this.executeTariffRestrictionScript();
			}

			await this.saveRolePermission(action);
		},
		executeTariffRestrictionScript()
		{
			const script = this.getRestrictionScript;

			if (script)
			{
				// eslint-disable-next-line no-eval
				eval(script);
			}
		},
	},
	template: `
		<div class="bx-crm-perms-main">
			<Loader v-if="isSaveInProgress"/>
			<p class="bx-crm-perms-desk-error" v-if="getLastErrorMessage">
				{{ getLastErrorMessage }}
			</p>
	
			<label>
				{{ labels.roleName }}:
				<input name="NAME" v-model="roleName" class="bx-crm-perms-desk-input-role_name">
			</label>
	
			<Desk/>

			<div class="bx-crm-perms-desk-footer">
				<div class="bx-crm-perms-desk-footer-colleft">
					<button @click="onPressSave" class="bx-crm-perms-desk-btn" name="save">{{ labels.saveName }}</button>
					<button @click="onPressApply" class="bx-crm-perms-desk-btn" name="apply">{{ labels.applyName }}</button>
				</div>
				<div class="bx-crm-perms-desk-footer-colright">
					<span 
						v-if="isShowDeleteBtn" 
						class="bx-crm-perms-desk-delete"
						@click="onPressDeleteRole"
					>{{ labels.roleDelete }}</span>
				</div>
			</div>

			<div
				v-if="!hasTariffPermission"
				class="ui-alert ui-alert-warning"
				style="margin: 15px 0 0 0;"
			>
				<span class="ui-alert-message" v-html="labels.notAllowedByTariff"></span>
			</div>

		</div>
	`,
};

import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { Reflection } from 'main.core';
import { ResponsiveHint } from 'humanresources.company-structure.structure-components';

export const Entities = {
	props: {
		parentId: {
			type: [Number, null],
			required: true,
		},
	},

	components: { ResponsiveHint },

	data(): { selectedEntityType: string; }
	{
		return {
			selectedEntityType: EntityTypes.department,
		};
	},

	emits: ['applyData'],

	created(): void
	{
		this.hint = null;
		this.hintHideTimeout = null;

		const permissionChecker = PermissionChecker.getInstance();

		const hasTeamCreatePermission = this.parentId === 0
			? permissionChecker.hasPermissionOfAction(PermissionActions.teamCreate)
			: permissionChecker.hasPermission(PermissionActions.teamCreate, this.parentId)
		;

		const hasDepartmentCreatePermission = this.parentId === 0
			? permissionChecker.hasPermissionOfAction(PermissionActions.departmentCreate)
			: permissionChecker.hasPermission(PermissionActions.departmentCreate, this.parentId)
		;

		this.entities = [
			{
				type: EntityTypes.department,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_DESCR_MSGVER_1'),
				isEnabled: hasDepartmentCreatePermission,
				hint: !hasDepartmentCreatePermission
					&& this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_NO_ACCESS_HINT'),
				isSoon: false,
			},
			{
				type: EntityTypes.team,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_TITLE_MSGVER_1'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_DESCR_MSGVER_1'),
				isEnabled: PermissionChecker.isTeamsAvailable && hasTeamCreatePermission,
				hint: PermissionChecker.isTeamsAvailable
					&& !hasTeamCreatePermission
					&& this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_TEAM_NO_ACCESS_HINT_MSGVER_1'),
				isSoon: !permissionChecker.isTeamsAvailable,
			},
		];

		for (const entity of this.entities)
		{
			if (entity.isEnabled)
			{
				this.selectedEntityType = entity.type;
				break;
			}
		}
		this.applyData(this.selectedEntityType);
	},

	activated(): void
	{
		this.applyData(this.selectedEntityType);
	},

	deactivated(): void
	{
		this.hint?.hide();
		clearTimeout(this.hintHideTimeout);
		this.hintHideTimeout = null;
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		applyData(entityId: string): void
		{
			this.selectedEntityType = entityId;
			this.$emit('applyData', {
				isValid: true,
				isDepartmentDataChanged: false,
				entityType: this.selectedEntityType,
			});
		},
		showHint(text: string, $event: PointerEvent): void
		{
			if (this.hintHideTimeout)
			{
				return;
			}

			const width = 300;
			this.hint = Reflection.getClass('BX.UI.Hint').createInstance({
				popupParameters: {
					width,
					bindOptions: { position: 'top' },
					offsetLeft: 225,
					offsetTop: -23,
					angle: { offset: width / 2 - 33 / 2 },
				},
			});
			this.hint.show($event.target, text);

			this.hintHideTimeout = setTimeout(() => {
				this.hint?.hide();
				this.hintHideTimeout = null;
			}, 2000);
		},
	},

	template: `
		<div
			v-for="entity in entities"
			class="chart-wizard__entity-wrapper"
		>
			<span v-if="entity.hint" @click="showHint(entity.hint, $event)" class="ui-hint chart-wizard__entity_hint-layout"></span>
			<div class="chart-wizard__entity"
				 :class="{ 
					['--' + entity.type.toLowerCase()]: true, 
					'--selected': entity.type === selectedEntityType, 
					'--enabled': entity.isEnabled 
				}"
				@click="entity.isEnabled && applyData(entity.type)"
			>
				<div class="chart-wizard__entity_summary">
					<h2
						class="chart-wizard__entity_title"
						:data-title="entity.isSoon ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE') : null"
						:class="{ '--disabled': !entity.isEnabled, '--soon': entity.isSoon }"
					>
						{{entity.title}}
					</h2>
					<p class="chart-wizard__entity_description" :class="{ '--disabled': !entity.isEnabled}">
						{{entity.description}}
					</p>
				</div>
			</div>
		</div>
	`,
};

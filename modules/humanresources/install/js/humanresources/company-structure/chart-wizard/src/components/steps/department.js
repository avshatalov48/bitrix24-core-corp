import { mapState } from 'ui.vue3.pinia';
import { TeamColorPicker } from '../team-color-picker/team-color-picker';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, WizardApiEntityChangedDict, type NodeColorSettingsType } from 'humanresources.company-structure.utils';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { type BaseEvent } from 'main.core.events';
import { TagSelector, type Item } from 'ui.entity-selector';
import { MemoryCache } from 'main.core.cache';

type DepartmentStepDataType = {
	deniedError: boolean,
	showColorPicker: boolean,
	teamColorValue: NodeColorSettingsType | null,
}

// @vue/component
export const Department = {
	name: 'department',

	components: { TeamColorPicker },

	props: {
		parentId: {
			type: [Number, null],
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
		shouldErrorHighlight: {
			type: Boolean,
			required: true,
		},
		isEditMode: {
			type: Boolean,
		},
		entityType: {
			type: String,
			required: true,
		},
		teamColor: {
			type: [Object, null],
			default: null,
		},
		refToFocus: {
			type: String,
			default: null,
		},
	},

	emits: ['applyData'],

	data(): DepartmentStepDataType
	{
		return {
			deniedError: false,
			showColorPicker: false,
			teamColorValue: this.teamColor,
			locked: false,
		};
	},

	watch:
	{
		isTeamEntity(): void
		{
			// tag selector is not valid now
			this.recreateTagSelector();
		},
	},

	created(): void
	{
		this.selectedParentDepartment = this.parentId;
		this.departmentName = this.name;
		this.departmentDescription = this.description;
		this.departmentSelectorCashe = new MemoryCache();
		this.departmentsSelector = this.getOrCreateTagSelector();
	},

	mounted(): void
	{
		this.departmentsSelector.renderTo(this.$refs['tag-selector']);
	},

	activated(): void
	{
		this.teamColorValue = this.teamColor;
		this.$emit('applyData', {
			isDepartmentDataChanged: false,
			isValid:
				this.departmentName
				&& this.departmentName?.trim() !== ''
				&& this.selectedParentDepartment !== null
				&& !this.deniedError
			,
		});

		if (this.refToFocus && this.$refs[this.refToFocus])
		{
			this.$refs[this.refToFocus].focus();
		}
		else
		{
			this.$refs.title.focus();
		}
	},

	methods:
	{
		recreateTagSelector(): void
		{
			this.$refs['tag-selector'].innerHTML = '';
			this.departmentsSelector = this.getOrCreateTagSelector();
			this.departmentsSelector.renderTo(this.$refs['tag-selector']);
		},
		createTagSelector(): TagSelector
		{
			this.locked = this.isTagSelectorLocked();
			let preselectedItems = this.parentId ? [['structure-node', this.parentId]] : [];
			if (!this.isEditMode)
			{
				const permissionChecker = PermissionChecker.getInstance();
				const permissionAction = this.isTeamEntity ? PermissionActions.teamCreate : PermissionActions.departmentCreate;
				if (
					permissionChecker
					&& !permissionChecker.hasPermission(permissionAction, this.parentId)
				)
				{
					preselectedItems = [['structure-node', 0]];
				}
			}

			const isTabEmpty = (tab) => tab.getRootNode().getChildren().count() === 0;

			const selector = new TagSelector({
				events: {
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.data;
						this.selectedParentDepartment = tag.id;
					},
					onTagRemove: () => {
						this.selectedParentDepartment = null;
						this.applyData();
					},
				},
				multiple: false,
				locked: this.locked,
				dialogOptions: {
					width: 425,
					height: 350,
					dropdownMode: true,
					hideOnDeselect: true,
					entities: [
						{
							id: 'structure-node',
							options: {
								selectMode: 'departmentsOnly',
								restricted: this.isEditMode ? 'update' : 'create',
								includedNodeEntityTypes: this.includedNodeEntityTypesInDialog,
								useMultipleTabs: true,
							},
						},
					],
					preselectedItems,
					events: {
						onLoad: (event) => {
							const dialog = selector.getDialog();

							dialog.getTabs()
								.filter((tab) => isTabEmpty(tab))
								.forEach((tab) => tab.setVisible(false))
							;

							const target = event.target;
							const selectedItem = target.selectedItems?.values()?.next()?.value;
							if (this.isEditMode)
							{
								if (selectedItem?.id === this.parentId && this.locked)
								{
									selector.lock();
								}

								return;
							}

							const permissionChecker = PermissionChecker.getInstance();
							if (!permissionChecker)
							{
								return;
							}

							const nodes = target.items.get('structure-node');

							const permissionAction = this.isTeamEntity
								? PermissionActions.teamCreate
								: PermissionActions.departmentCreate
							;

							for (const [, node] of nodes)
							{
								if 	(
									permissionChecker.hasPermission(permissionAction, node.id)
									&& !permissionChecker.hasPermission(permissionAction, selectedItem?.id ?? 0)
								)
								{
									node.select();

									break;
								}
							}
						},
						onLoadError: () => {
							this.selectedParentDepartment = null;
							this.applyData();
						},
						'Item:onSelect': (event: BaseEvent<{ item: Item }>) => {
							this.deniedError = false;

							const target = event.target;
							const selectedItem = target.selectedItems?.values()?.next()?.value;

							const permissionChecker = PermissionChecker.getInstance();
							if (!permissionChecker)
							{
								return;
							}

							const item = this.departments.get(selectedItem.id);
							if (!item)
							{
								return;
							}

							const isTeamItem = item?.entityType === EntityTypes.team;

							const permissionCreateAction = this.isTeamEntity
								? PermissionActions.teamCreate
								: PermissionActions.departmentCreate
							;

							const permissionEditAction = isTeamItem
								? PermissionActions.teamEdit
								: PermissionActions.departmentEdit
							;

							const permissionAction = this.isEditMode
								? permissionEditAction
								: permissionCreateAction
							;

							if (!permissionChecker.hasPermission(permissionAction, selectedItem.id))
							{
								this.deniedError = true;
							}

							this.applyData();
						},
					},
				},
			});

			return selector;
		},
		getOrCreateTagSelector(): TagSelector
		{
			const key = String(this.isTeamEntity);

			return this.departmentSelectorCashe.remember(key, () => this.createTagSelector());
		},
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		applyData(): void
		{
			this.$emit('applyData', {
				apiEntityChanged: WizardApiEntityChangedDict.department,
				name: this.departmentName,
				description: this.departmentDescription,
				parentId: this.selectedParentDepartment,
				teamColor: this.teamColorValue,
				isDepartmentDataChanged: true,
				isValid:
					this.departmentName
					&& this.departmentName?.trim() !== ''
					&& this.selectedParentDepartment !== null
					&& !this.deniedError
				,
			});
		},
		isTagSelectorLocked(): boolean
		{
			if (!this.isEditMode)
			{
				return false;
			}

			if (this.parentId === 0)
			{
				return true;
			}

			const parent = this.departments.get(this.parentId);
			if (!parent)
			{
				return false;
			}

			const permissionChecker = PermissionChecker.getInstance();
			if (!permissionChecker)
			{
				return true;
			}

			if (parent.entityType === EntityTypes.department)
			{
				return !permissionChecker.hasPermission(PermissionActions.departmentEdit, parent.id);
			}

			if (parent.entityType === EntityTypes.team)
			{
				return !permissionChecker.hasPermission(PermissionActions.teamEdit, parent.id);
			}

			return false;
		},
	},

	computed:
	{
		includedNodeEntityTypesInDialog(): string[]
		{
			return this.isTeamEntity ? ['department', 'team'] : ['department'];
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		namePlaceholder(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_NAME_PLACEHOLDER_MSGVER_1')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_PLACEHOLDER');
		},
		descriptionPlaceholder(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_DESCR_PLACEHOLDER')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_PLACEHOLDER');
		},
		higherLevelDepartmentContainer(): string
		{
			// for team entity department tag selector should be placed after name and description
			// to presumably encourage user to fill in the description field
			return this.isTeamEntity ? '.chart-wizard__department_higher_bottom' : '.chart-wizard__department_higher_top';
		},
		showLockedSelectorErrorText(): boolean
		{
			return this.locked && this.parentId !== 0;
		},
		selectorErrorText(): string
		{
			if (this.deniedError)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ADD_TO_DEPARTMENT_DENIED_MSG_VER_1');
			}

			if (this.locked && this.parentId !== 0)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_PARENT_TEAM_ERROR')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_PARENT_DEPARTMENT_ERROR')
				;
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_PARENT_ERROR')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_PARENT_ERROR')
			;
		},
		...mapState(useChartStore, ['departments']),
	},

	template: `
		<div class="chart-wizard__department">
			<div class="chart-wizard__form">
				<Teleport defer :to="higherLevelDepartmentContainer">
					<span class="chart-wizard__department_item-label">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_WITH_TEAM_LABEL')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_LABEL')
						}}
					</span>
					<div
						:class="{ 'ui-ctl-warning': deniedError || (selectedParentDepartment === null && shouldErrorHighlight) }"
						ref="tag-selector"
					></div>
					<div
						v-if="deniedError || (selectedParentDepartment === null && shouldErrorHighlight) || showLockedSelectorErrorText"
						class="chart-wizard__department_item-error"
						:class="{'--wizard-warning-item':  locked && parentId !== 0}"
					>
						<div
							class="ui-icon-set --warning"
							:style="locked && !deniedError ? { '--ui-icon-set__icon-size': '17px', '--ui-icon-set__icon-color': '#FFA900' } : {}"
						/>
						<span class="chart-wizard__department_item-error-message">
							{{ selectorErrorText }}
						</span>
					</div>
				</Teleport>
				<div
					v-show="!isTeamEntity"
					class="chart-wizard__department_item chart-wizard__department_higher_top">
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_TEAM_NAME_LABEL')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_LABEL') 
						}}
					</span>
					<div class="chart-wizard__department_control-wrapper">
						<div
							class="ui-ctl ui-ctl-textbox"
							:class="{ 'ui-ctl-warning': shouldErrorHighlight && departmentName?.trim() === '' }"
						>
							<input
								v-model="departmentName"
								type="text"
								maxlength="255"
								class="ui-ctl-element"
								ref="title"
								:placeholder="namePlaceholder"
								@input="applyData()"
							/>
						</div>
						<div v-if="isTeamEntity" 
							 class="chart-wizard__department__color-picker" 
							 @click="showColorPicker = true"
							 ref="TeamColorPicker"
							 :data-test-id="'wizard-department-color-picker'"
							 :class="{ '--active': showColorPicker }"
						>
							<div class="chart-wizard__department__color-picker_inner"
								 :style="{ 'background-color': teamColorValue?.pickerColor }"
							></div>
						</div>
					</div>
					<div
						v-if="shouldErrorHighlight && departmentName?.trim() === ''"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__department_item-error-message">
							{{
								isTeamEntity
									? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_NAME_ERROR')
									: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_ERROR')
							}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_LABEL') }}
					</span>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize">
						<textarea
							v-model="departmentDescription"
							maxlength="255"
							class="ui-ctl-element"
							ref="description"
							:placeholder="descriptionPlaceholder"
							@change="applyData()"
						>
						</textarea>
					</div>
				</div>
				<div
					v-show="isTeamEntity"
					class="chart-wizard__department_item chart-wizard__department_higher_bottom"
				></div>
			</div>
			<TeamColorPicker
				v-if="showColorPicker"
				:bindElement="$refs.TeamColorPicker"
				v-model="teamColorValue"
				@update:model-value="applyData()"
				@close="showColorPicker = false"
			/>
		</div>
	`,
};

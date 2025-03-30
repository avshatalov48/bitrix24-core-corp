import { TagSelector } from 'ui.entity-selector';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

export const Department = {
	name: 'department',

	emits: ['applyData'],

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
	},

	data(): { deniedError: boolean; }
	{
		return {
			deniedError: false,
		};
	},

	created(): void
	{
		this.selectedParentDepartment = this.parentId;
		this.departmentName = this.name;
		this.departmentDescription = this.description;
		this.departmentsSelector = this.getTagSelector();
	},

	mounted(): void
	{
		this.departmentsSelector.renderTo(this.$refs['tag-selector']);
	},

	activated(): void
	{
		this.applyData();
		this.$refs.title.focus();
	},

	methods:
	{
		getTagSelector(): TagSelector
		{
			return new TagSelector({
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
				locked: this.parentId === 0,
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
							},
						},
					],
					preselectedItems: this.parentId ? [['structure-node', this.parentId]] : [],
					events: {
						onLoad: (event) => {
							if (this.isEditMode)
							{
								return;
							}

							const permissionChecker = PermissionChecker.getInstance();
							if (!permissionChecker)
							{
								return;
							}

							const target = event.target;
							const selectedItem = target.selectedItems?.values()?.next()?.value;
							const nodes = target.items.get('structure-node');

							for (const [, node] of nodes)
							{
								if (permissionChecker.hasPermission(PermissionActions.departmentCreate, node.id)
									&& !permissionChecker.hasPermission(PermissionActions.departmentCreate, selectedItem?.id))
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
						'Item:onSelect': (event) => {
							this.deniedError = false;

							const target = event.target;
							const selectedItem = target.selectedItems?.values()?.next()?.value;

							const permissionChecker = PermissionChecker.getInstance();
							if (!permissionChecker)
							{
								return;
							}

							if (!permissionChecker.hasPermission(PermissionActions.departmentCreate, selectedItem.id))
							{
								this.deniedError = true;
							}
							this.applyData();
						},
					},
				},
				tagBgColor: '#ade7e4',
				tagTextColor: '#207976',
				tagFontWeight: '700',
				tagAvatar: '/bitrix/js/humanresources/entity-selector/src/images/department.svg',
			});
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		applyData(): void
		{
			this.$emit('applyData', {
				name: this.departmentName,
				description: this.departmentDescription,
				parentId: this.selectedParentDepartment,
				isValid:
					this.departmentName !== ''
					&& this.selectedParentDepartment !== null
					&& !this.deniedError
				,
			});
		},
	},

	template: `
		<div class="chart-wizard__department">
			<div class="chart-wizard__form">
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_HIGHER_LABEL')}}
					</span>
					<div
						:class="{ 'ui-ctl-warning': deniedError || (selectedParentDepartment === null && shouldErrorHighlight) }"
						ref="tag-selector"></div>
					<div
						v-if="deniedError || (selectedParentDepartment === null && shouldErrorHighlight)"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span
							v-if="deniedError"
							class="chart-wizard__department_item-error-message"
						>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ADD_TO_DEPARTMENT_DENIED_MSG_VER_1')}}
						</span>
						<span
							v-else
							class="chart-wizard__department_item-error-message"
						>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_PARENT_ERROR')}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_LABEL')}}
					</span>
					<div
						class="ui-ctl ui-ctl-textbox"
						:class="{ 'ui-ctl-warning': shouldErrorHighlight && departmentName === '' }"
					>
						<input
							v-model="departmentName"
							type="text"
							maxlength="255"
							class="ui-ctl-element"
							ref="title"
							:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_PLACEHOLDER')"
							@input="applyData()"
						/>
					</div>
					<div
						v-if="shouldErrorHighlight && departmentName === ''"
						class="chart-wizard__department_item-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__department_item-error-message">
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_NAME_ERROR')}}
						</span>
					</div>
				</div>
				<div class="chart-wizard__department_item">
					<span class="chart-wizard__department_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_LABEL')}}
					</span>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize">
						<textarea
							v-model="departmentDescription"
							maxlength="255"
							class="ui-ctl-element"
							:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_DESCR_PLACEHOLDER')"
							@change="applyData()"
						>
						</textarea>
					</div>
				</div>
			</div>
		</div>
	`,
};

import { getMemberRoles } from 'humanresources.company-structure.api';
import { Text } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import { AuthorityTypes } from '../../consts';
import { WizardApiEntityChangedDict, NodeSettingsTypes, EntityTypes, type UserData } from 'humanresources.company-structure.utils';

// @vue/component
export const Settings = {
	name: 'NodeSettings',

	props: {
		name: {
			type: String,
			required: true,
		},
		/** @type {Record<string, Set>} */
		settings: {
			type: Object,
			required: false,
			default: () => {},
		},
		/** @type {Record<string, boolean>} */
		features: {
			type: Object,
			required: false,
			default: () => {},
		},
		shouldErrorHighlight: {
			type: Boolean,
			required: true,
		},
		entityType: {
			type: String,
			required: true,
		},
		/** @type UserData[] */
		heads: {
			type: Array,
			required: false,
			default: () => [],
		},
	},

	emits: ['applyData'],

	computed:
	{
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		isBusinessProcHeadNotSelected(): boolean
		{
			if (!this.isTeamEntity)
			{
				return false;
			}

			const bpSettings = this.settings[NodeSettingsTypes.businessProcAuthority];

			return !bpSettings || bpSettings.size === 0;
		},
		businessDescription(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_DESCRIPTION', {
					'#DEPARTMENT_NAME#': Text.encode(this.name),
				})
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_DESCRIPTION')
			;
		},
		reportsDescriptions(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_REPORTS_DESCRIPTION', {
					'#DEPARTMENT_NAME#': Text.encode(this.name),
				})
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_REPORTS_DESCRIPTION')
			;
		},
		hintTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_HINT_TITLE')
			;
		},
		businessProcWarningText(): string | null
		{
			if (this.isTeamEntity)
			{
				return null;
			}

			const memberRoles = getMemberRoles(this.entityType);
			const hasHead = this.heads.some((item: UserData) => item.role === memberRoles.head);
			const hasDeputy = this.heads.some((item: UserData) => item.role === memberRoles.deputyHead);
			const headSelected = this.settings[NodeSettingsTypes.businessProcAuthority].has(AuthorityTypes.departmentHead);
			const deputySelected = this.settings[NodeSettingsTypes.businessProcAuthority]
				.has(AuthorityTypes.departmentDeputy)
			;

			if (headSelected && hasHead && deputySelected && !hasDeputy)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_HAS_HEAD_NO_DEPUTY');
			}

			if (headSelected && !hasHead && deputySelected && hasDeputy)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD_HAS_DEPUTY');
			}

			if (headSelected && !hasHead && deputySelected && !hasDeputy)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD_NO_DEPUTY');
			}

			if (headSelected && !hasHead && !deputySelected)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_HEAD');
			}

			if (!headSelected && deputySelected && !hasDeputy)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_NO_DEPUTY');
			}

			if (!headSelected && !deputySelected)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BUSINESS_PROC_EMPTY');
			}

			return null;
		},
	},

	watch:
	{
		settings:
		{
			handler(payload: Record<string, Set>): void
			{
				if (payload[NodeSettingsTypes.businessProcAuthority])
				{
					const businessProcPreselected = this.getTagItems(false)
						.filter((item) => payload[NodeSettingsTypes.businessProcAuthority].has(item.id))
					;

					businessProcPreselected.forEach((businessProcPreselectedItem) => {
						const item = this.businessProcSelector.dialog.getItem(['head-type', businessProcPreselectedItem.id]);

						if (item)
						{
							this.initBpValues.add(businessProcPreselectedItem.id);
							item.select();
						}
					});
				}
			},
		},
	},

	created(): void
	{
		this.hints = [
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_1'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_HINT_2'),
		];

		this.initBpValues = new Set();
		this.businessProcSelector = this.getTagSelector(NodeSettingsTypes.businessProcAuthority, false);
		this.reportsSelector = this.getTagSelector(NodeSettingsTypes.reportsAuthority, true);
	},

	mounted(): void
	{
		this.businessProcSelector.renderTo(this.$refs['business-proc-selector']);
		this.reportsSelector.renderTo(this.$refs['reports-selector']);
	},

	activated(): void
	{
		this.$emit('applyData', {
			isDepartmentDataChanged: false,
			isValid: !this.isBusinessProcHeadNotSelected,
		});
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		applyData(): void
		{
			this.$emit('applyData', {
				apiEntityChanged: WizardApiEntityChangedDict.settings,
				settings: this.settings,
				isDepartmentDataChanged: true,
				isValid: !this.isBusinessProcHeadNotSelected,
			});
		},
		getTagSelector(settingType: string, locked: boolean): TagSelector
		{
			return new TagSelector({
				events: {
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.settings[settingType].add(tag.id);

						if (this.initBpValues.has(tag.id))
						{
							this.initBpValues.delete(tag.id);
						}
						else
						{
							this.applyData();
						}
					},
					onTagRemove: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.settings[settingType].delete(tag.id);
						this.applyData();
					},
				},
				multiple: true,
				id: 'head-type-selector',
				locked,
				tagFontWeight: '700',
				showAddButton: !locked,
				dialogOptions: {
					id: 'head-type-selector',
					events: {
						'Item:onBeforeSelect': (event: BaseEvent) => {
							const { item } = event.getData();
							if (!item.getCustomData()?.get('selectable'))
							{
								event.preventDefault();
							}
						},
					},
					width: 400,
					height: 200,
					tagMaxWidth: 400,
					dropdownMode: true,
					showAvatars: false,
					selectedItems: this.getTagItems(locked).filter(((item) => this.settings[settingType].has(item.id))),
					items: this.getTagItems(locked),
					undeselectedItems: this.features.isDeputyApprovesBPAvailable || !this.isTeamEntity
						? []
						: [['head-type', AuthorityTypes.departmentHead]],
				},
			});
		},
		getTagItems(locked: boolean): Array
		{
			const lockedTagOptions = {
				bgColor: '#BDC1C6',
				textColor: '#525C69',
			};
			const departmentTagOptions = {
				bgColor: '#ADE7E4',
				textColor: '#207976',
				maxWidth: 400,
			};
			const teamTagOptions = {
				bgColor: '#CCE3FF',
				textColor: '#3592FF',
				maxWidth: 400,
			};

			const soonItemOptions = {
				customData: { selectable: false },
				textColor: '#C9CCD0',
				badges: [
					{
						title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_SOON_ITEM_BADGE'),
						textColor: '#FFFFFF',
						bgColor: '#2FC6F6',
					},
				],
				badgesOptions: {
					justifyContent: 'right',
				},
			};

			const deputyOptions = this.features.isDeputyApprovesBPAvailable || !this.isTeamEntity
				? { customData: { selectable: true } }
				: soonItemOptions
			;

			const departmentHead = {
				id: AuthorityTypes.departmentHead,
				entityId: 'head-type',
				tabs: 'recents',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_DEPARTMENT_HEAD_ITEM'),
				tagOptions: locked ? lockedTagOptions : departmentTagOptions,
				customData: { selectable: true },
			};

			const teamHead = {
				id: AuthorityTypes.teamHead,
				entityId: 'head-type',
				tabs: 'recents',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_TEAM_HEAD_ITEM'),
				tagOptions: locked ? lockedTagOptions : teamTagOptions,
				customData: { selectable: true },
			};

			const departmentDeputy = {
				id: AuthorityTypes.departmentDeputy,
				entityId: 'head-type',
				tabs: 'recents',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_DEPARTMENT_DEPUTY_ITEM'),
				tagOptions: locked ? lockedTagOptions : departmentTagOptions,
				...deputyOptions,
			};

			const teamDeputy = {
				id: AuthorityTypes.teamDeputy,
				entityId: 'head-type',
				tabs: 'recents',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_TEAM_DEPUTY_ITEM'),
				tagOptions: locked ? lockedTagOptions : teamTagOptions,
				...deputyOptions,
			};

			if (this.isTeamEntity)
			{
				return this.features.isDeputyApprovesBPAvailable
					? [departmentHead, departmentDeputy, teamHead, teamDeputy]
					: [departmentHead, teamHead, departmentDeputy, teamDeputy]
				;
			}

			return [departmentHead, departmentDeputy];
		},
		goToBPHelp(event): void
		{
			if (top.BX.Helper)
			{
				event.preventDefault();
				top.BX.Helper.show('redirect=detail&code=25455744');
			}
		},
	},

	template: `
		<div class="chart-wizard__settings">
			<div class="chart-wizard__settings__item" :class="{ '--team': isTeamEntity }">
				<div class="chart-wizard__settings__item-hint">
					<div class="chart-wizard__settings__item-hint_logo"></div>
					<div class="chart-wizard__settings__item-hint_text">
						<div class="chart-wizard__settings__item-hint_title">
							{{ hintTitle }}
						</div>
						<div v-for="hint in hints"
							class="chart-wizard__settings__item-hint_text-item"
						>
							<div class="chart-wizard__settings__item-hint_text-item_icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__settings__item-options">
					<div class="chart-wizard__settings__item-options__item-content_title"
						 :data-title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE')"
					>
						<div class="chart-wizard__settings__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_TITLE') }}
						</div>
						<span v-if="isTeamEntity" class="ui-hint" @click="goToBPHelp">
							<span class="ui-hint-icon"/>
						</span>
					</div>
					<div class="chart-wizard__settings__item-description-container">
						<span class="chart-wizard__settings__item-description-text" v-html="businessDescription">
						</span>
					</div>
					<div
						class="chart-wizard__settings__business-proc-selector"
						:class="{ 'ui-ctl-warning': (isBusinessProcHeadNotSelected) }"
						ref="business-proc-selector"
						data-test-id="hr-company-structure__settings__business-proc-selector"
					/>
					<div
						v-if="isBusinessProcHeadNotSelected"
						class="chart-wizard__settings__item-options-error"
					>
						<div class="ui-icon-set --warning"></div>
						<span class="chart-wizard__settings__item-options-error-message">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BUSINESS_PROC_HEAD_ERROR') }}
						</span>
					</div>
					<div
						v-else-if="businessProcWarningText"
						class="chart-wizard__settings__item-options-warning"
					>
						<div class="ui-icon-set --warning"></div>
						<span>
							{{ businessProcWarningText }}
						</span>
					</div>
				</div>
				<div class="chart-wizard__settings__item-options">
					<div class="chart-wizard__settings__item-options__item-content_title --soon"
						 :data-title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE')"
					>
						<div class="chart-wizard__settings__item-options__item-content_title-text --soon">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_REPORTS_TITLE') }}
						</div>
						<span v-if="isTeamEntity" class="chart-wizard__settings_ui-hint-disabled">
							<span class="ui-hint-icon"/>
						</span>
					</div>
					<div class="chart-wizard__settings__item-description-container">
						<span class="chart-wizard__settings__item-description-text --soon" v-html="reportsDescriptions">
						</span>
					</div>
					<div
						class="chart-wizard__settings__reports-selector"
						ref="reports-selector"
						data-test-id="hr-company-structure__settings__reports-selector"
					/>
				</div>
			</div>
		</div>
	`,
};

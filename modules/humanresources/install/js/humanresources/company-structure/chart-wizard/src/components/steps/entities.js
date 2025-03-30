export const Entities = {
	data(): { selectedId: string; }
	{
		return {
			selectedId: 'department',
		};
	},

	emits: ['applyData'],

	created(): void
	{
		this.entities = [
			{
				id: 'department',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_DEPARTMENT_DESCR'),
			},
			{
				id: 'group',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_GROUP_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_FUNCTIONAL_GROUP_DESCR'),
			},
			{
				id: 'company',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_COMPANY_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_COMPANY_DESCR'),
			},
		];
	},

	activated(): void
	{
		this.applyData();
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
				isValid: true,
			});
		},
	},

	template: `
		<div
			v-for="entity in entities"
			class="chart-wizard__entity"
			:class="{ ['--' + entity.id]: true, '--selected': entity.id === selectedId }"
		>
			<div class="chart-wizard__entity_summary" @click="applyData">
				<h2
					class="chart-wizard__entity_title"
					:data-title="entity.id !== 'department' ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ENTITY_ACCESS_TITLE') : null"
					:class="{ '--disabled': entity.id !== 'department' }"
				>
					{{entity.title}}
				</h2>
				<p class="chart-wizard__entity_description" :class="{ '--disabled': entity.id !== 'department'}">
					{{entity.description}}
				</p>
			</div>
		</div>
	`,
};

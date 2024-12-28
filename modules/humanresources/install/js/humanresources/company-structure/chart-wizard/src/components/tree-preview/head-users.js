export const HeadUsers = {
	name: 'headUsers',

	props: {
		users: {
			type: Array,
			required: true,
		},
		showPlaceholder: {
			type: Boolean,
			default: true,
		},
		userType: String,
	},

	created(): void
	{
		this.headItemsCount = 2;
		this.userTypes = {
			head: 'head',
			deputy: 'deputy',
		};
	},

	computed:
	{
		defaultAvatar(): string
		{
			return '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg';
		},
		placeholderAvatar(): string
		{
			return '/bitrix/js/humanresources/company-structure/chart-wizard/src/components/tree-preview/images/placeholder-avatar.svg';
		},
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},

	template: `
		<div
			class="chart-wizard-tree-preview__node_head"
			v-for="(user, index) in users.slice(0, headItemsCount)"
		>
			<img
				:src="user.avatar || defaultAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{ '--deputy': userType === userTypes.deputy }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text">
				<span class="chart-wizard-tree-preview__node_head-name --crop">
					{{user.name}}
				</span>
				<span v-if="userType !== userTypes.deputy" class="chart-wizard-tree-preview__node_head-position --crop">
					{{user.workPosition || loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION')}}
				</span>
			</div>
			<span
				v-if="index === 1 && users.length > 2"
				class="chart-wizard-tree-preview__node_head-rest"
				>
					{{'+' + String(users.length - 2)}}
			</span>
		</div>
		<div
			v-if="users.length === 0 && showPlaceholder"
			class="chart-wizard-tree-preview__node_head"
		>
			<img
				:src="placeholderAvatar"
				class="chart-wizard-tree-preview__node_head-avatar --placeholder"
				:class="{'--deputy': userType === userTypes.deputy }"
			/>
			<div class="chart-wizard-tree-preview__node_head-text">
				<span class="chart-wizard-tree-preview__placeholder_name"></span>
				<span
					v-if="userType !== userTypes.deputy"
					class="chart-wizard-tree-preview__node_head-position --crop"
				>
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION')}}
				</span>
			</div>
		</div>
	`,
};

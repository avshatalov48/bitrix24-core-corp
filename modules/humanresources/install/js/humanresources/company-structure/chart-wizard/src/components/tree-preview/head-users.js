import { UserListActionMenu } from 'humanresources.company-structure.structure-components';
import type { UserData } from 'humanresources.company-structure.utils';

export const HeadUsers = {
	name: 'headUsers',

	components: {
		UserListActionMenu,
	},

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

	data(): { headsVisible: boolean; }
	{
		return {
			headsVisible: false,
		};
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
		dropdownItems(): Array<UserData>
		{
			return this.users.map((user: UserData): UserData => {
				const workPosition = user.workPosition || this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_HEAD_POSITION');

				return { ...user, workPosition };
			});
		},
		titleBar(): string
		{
			return this.userType === this.userTypes.deputy
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE')
			;
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
				:class="{ '--active': headsVisible }"
				ref="showMoreHeadUserWizardList"
				:data-test-id="'hr-company-structure_chart-wizard-tree__preview-' + type + '-rest'"
				@click.stop="headsVisible = true"
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

		<UserListActionMenu
			v-if="headsVisible"
			:id="userType === userTypes.deputy ? 'wizard-head-list-popup-deputy' : 'wizard-head-list-popup-head' "
			:items="dropdownItems"
			:width="228"
			:bindElement="$refs.showMoreHeadUserWizardList[0]"
			@close="headsVisible = false"
			:titleBar="titleBar"
		/>
	`,
};

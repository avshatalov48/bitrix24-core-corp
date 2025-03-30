import { mapState } from 'ui.vue3.pinia';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { TreeNode } from './tree-node';
import './style.css';

export const TreePreview = {
	name: 'treePreview',

	components: { TreeNode },

	props: {
		parentId: {
			type: [Number, null],
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		heads: {
			type: Array,
			required: true,
		},
		userCount: {
			type: Number,
			required: true,
		},
	},

	computed:
	{
		rootId(): number
		{
			const parentNode = this.departments.get(this.parentId);
			if (parentNode)
			{
				return parentNode.parentId ?? 0;
			}

			return 0;
		},
		companyName(): string
		{
			const { name } = [...this.departments.values()].find((department) => {
				return department.parentId === 0;
			});

			return name;
		},
		...mapState(useChartStore, ['departments']),
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},

	template: `
		<div class="chart-wizard-tree-preview">
			<div class="chart-wizard-tree-preview__header">
				<span class="chart-wizard-tree-preview__header_text">
					{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TREE_PREVIEW_DEPARTMENT_TITLE')}}
				</span>
				<span class="chart-wizard-tree-preview__header_name">
					{{companyName}}
				</span>
			</div>
			<TreeNode
				v-if="rootId"
				:nodeId="rootId"
			>
				<TreeNode :nodeId="parentId">
					<TreeNode
						:name="name"
						:heads="heads"
						:userCount="userCount"
					></TreeNode>
				</TreeNode>
			</TreeNode>
			<TreeNode
				v-else-if="parentId"
				:nodeId="parentId"
			>
				<TreeNode
					:name="name"
					:heads="heads"
					:userCount="userCount"
				></TreeNode>
			</TreeNode>
			<TreeNode
				v-else
				:name="name"
				:heads="heads"
				:userCount="userCount"
			></TreeNode>
		</div>
	`,
};

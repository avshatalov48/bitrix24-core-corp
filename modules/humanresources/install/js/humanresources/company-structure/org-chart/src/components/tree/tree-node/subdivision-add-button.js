import { BIcon, Set } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

export const SubdivisionAddButton = {
	name: 'SubdivisionAddButton',
	emits: ['addDepartment'],

	props: {
		departmentId: {
			type: Number,
			required: true,
		},
	},

	components: {
		BIcon,
	},

	created(): void
	{
		const permissionChecker = PermissionChecker.getInstance();

		this.canShow = permissionChecker
			&& permissionChecker.hasPermission(PermissionActions.departmentCreate, this.departmentId);
	},

	computed: {
		set(): Set
		{
			return Set;
		},
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		addSubdivision(event): void
		{
			this.$emit('addDepartment');
		},
	},

	template: `
		<div class="humanresources-tree__node_add-subdivision" v-if="canShow">
		  <button class="humanresources-tree__node_add-button" @click="addSubdivision">
		    <BIcon :name="set.PLUS_20" :size="32" class="humanresources-tree__node_add-icon"></BIcon>
		  </button>
		</div>
	`,
};

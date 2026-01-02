import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { AddDepartmentMenuItem } from '../../menu/items/department/add-department-menu-item';

// @vue/component
export const SubdivisionAddButton = {
	name: 'SubdivisionAddButton',

	components: {
		BIcon,
	},

	props: {
		entityId: {
			type: Number,
			required: true,
		},
		entityType: {
			type: String,
			default: EntityTypes.department,
		},
	},

	computed:
	{
		set(): Set
		{
			return Set;
		},
	},

	created(): void
	{
		this.menuItem = new AddDepartmentMenuItem(this.entityType);
		const permissionChecker = PermissionChecker.getInstance();

		this.canShow = this.menuItem.hasPermission(permissionChecker, this.entityId);
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		addSubdivision(): void
		{
			this.menuItem.invoke({
				entityId: this.entityId,
				analyticSource: AnalyticsSourceType.PLUS,
				entityType: this.entityType,
			});
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

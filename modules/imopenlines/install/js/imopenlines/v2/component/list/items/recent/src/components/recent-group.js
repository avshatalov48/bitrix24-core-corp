import { RecentItem } from './recent-item/recent-item';

// @vue/component
export const RecentGroup = {
	name: 'RecentGroup',
	components: { RecentItem },
	props:
	{
		groupItems: {
			type: Array,
			required: true,
		},
		groupName: {
			type: String,
			required: true,
		},
	},
	emits: ['recentClick'],
	computed:
	{
		groupTitle(): string
		{
			return this.loc(`IMOL_LIST_STATUS_MESSAGE_${this.groupName.toUpperCase()}`);
		},
	},
	methods:
	{
		onRecentClick(dialogId: string)
		{
			this.$emit('recentClick', dialogId);
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-list-recent__group-item_container" v-if="groupItems.length !== 0">
			<span 
				class="bx-imol-list-recent__group_name" 
				:class="'bx-imol-list-recent__group_name_' + groupName.toLowerCase()"
			>
				{{ groupTitle }}
			</span>
			<RecentItem
				v-for="item in groupItems"
				:item="item"
				:key="item.dialogId"
				@click="onRecentClick(item.dialogId)"
			/>
		</div>
	`,
};

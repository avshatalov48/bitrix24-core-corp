import ListItem from './list-item';

export default {
	props: {
		listItems: {
			type: Array,
			required: true,
			default: [],
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		showMoreEnabled: {
			type: Boolean,
			required: true,
		},
		showMoreCnt: {
			type: Number,
			required: false,
		},
		showMoreText: {
			type: String,
			required: false,
		},
	},

	data()
	{
		return {
			isShortList: this.showMoreEnabled,
			shortListItemsCnt: this.showMoreCnt,
		};
	},
	components: {
		ListItem,
	},
	methods: {
		showMore()
		{
			this.isShortList = false;
		},
		isItemVisible(index)
		{
			return !this.isShortList || index < this.showMoreCnt;
		},
	},
	computed: {
		isShowMoreVisible()
		{
			return this.isShortList && this.listItems.length > this.shortListItemsCnt;
		},
	},
	// language=Vue
	template: `
		<div>
			<div v-if="title" class="crm-entity-stream-advice-title">
				{{title}}
			</div>
			<transition-group class="crm-entity-stream-advice-list" name="list" tag="ul">
				<ListItem
					v-for="(item, index) in listItems"
					v-show="isItemVisible(index)"
					:key="item.id"
					v-bind="item.properties"
				></ListItem>
			</transition-group>
			<a
				v-if="isShowMoreVisible"
				@click.prevent="showMore"
				class="crm-entity-stream-advice-link"
				href="#"
			>
				{{showMoreText}}
			</a>
		</div>
	`
}

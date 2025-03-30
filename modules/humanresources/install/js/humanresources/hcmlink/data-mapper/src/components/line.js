import { Separator } from './separator';
import { PersonItem } from './person-item';
import { UserItem } from './user-item';
import { MoreOptions } from './more-option';
import '../styles/line.css';

export const Line = {
	name: 'Line',

	props: {
		item: {
			type: Object,
			required: true,
		},
		mappedUserIds: {
			type: Array,
			required: true,
		},
		config: {
			required: true,
			type: {
				companyId: Number,
				mode: String,
				isHideInfoAlert: Boolean,
			},
		},
	},

	data(): Object
	{
		return {
			hasLink: false,
		};
	},

	emits:
	[
		'createLink',
		'removeLink',
	],

	components: {
		PersonItem,
		UserItem,
		Separator,
		MoreOptions,
	},

	methods: {
		onAddEntity(options): void
		{
			if (this.config.mode === 'direct')
			{
				this.$emit('createLink', { userId: this.item.id, personId: options.id });
				this.hasLink = true;
			}
			else
			{
				this.$emit('createLink', { userId: options.id, personId: this.item.id });
				this.hasLink = true;
			}
		},
		onRemoveEntity(options): void
		{
			const userId = this.config.mode === 'direct' ? this.item.id : options.id;

			this.$emit('removeLink', { userId });
			this.hasLink = false;
		},
	},

	template: `
		<div class="hr-hcmlink-sync__line-container">
			<div class="hr-hcmlink-sync__line-left-container">
				<UserItem
					:item=item
				    :mode="config.mode"
				></UserItem>
				<Separator
					:hasLink=hasLink
					:mode="config.mode"
				></Separator>
			</div>
			<div class="hr-hcmlink-sync__line-right-container" :class="this.config.mode === 'direct' ? '--person' : '--user'">
				<PersonItem
					:config=config
					:suggestId="item.suggestId"
					:mappedUserIds=mappedUserIds
					@addEntity="onAddEntity"
					@removeEntity="onRemoveEntity"
				></PersonItem>
			</div>
		</div>
	`,
};

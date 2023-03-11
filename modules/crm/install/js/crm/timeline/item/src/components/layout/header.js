import {ChangeStreamButton} from './header/change-stream-button'
import {Title} from './header/title'
import {Tag} from './header/tag'
import {User} from './header/user'
import {FormatDate} from './header/format-date';
import {Runtime, Type} from 'main.core';
import {Hint} from './header/hint';

export const Header = {
	components: {
		ChangeStreamButton,
		Title,
		Tag,
		User,
		FormatDate,
		Hint,
	},
	props: {
		title: String,
		titleAction: Object,
		date: Number,
		datePlaceholder: String,
		useShortTimeFormat: Boolean,
		changeStreamButton: Object|null,
		tags: Object,
		user: Object,
		infoHelper: Object,
	},
	inject: [
		'isReadOnly',
		'isLogMessage',
	],
	computed: {
		visibleTags(): Array
		{
			if (!Type.isPlainObject(this.tags))
			{
				return [];
			}

			return this.tags
				? Object.values(this.tags).filter(this.isVisibleTagFilter)
				: []
			;
		},

		visibleAndAscSortedTags(): Array
		{
			const tagsCopy = Runtime.clone(this.visibleTags);
			return tagsCopy.sort(this.tagsAscSorter);
		},

		isShowDate(): boolean
		{
			return this.date || this.datePlaceholder;
		},

		className(): Object {
			return [
				'crm-timeline__card-top', {
				'--log-message': this.isReadOnly || this.isLogMessage,
				}
			]
		}
	},
	methods: {
		isVisibleTagFilter(tag): Boolean {
			return tag.state !== 'hidden' && tag.scope !== 'mobile' && (!this.isReadOnly || !tag.hideIfReadonly);
		},

		tagsAscSorter(tagA, tagB): Number {
			return tagA.sort - tagB.sort;
		},

		getChangeStreamButton(): ?Object
		{
			return this.$refs.changeStreamButton;
		}
	},
	template: `
		<div :class="className">
			<div class="crm-timeline__card-top_info">
				<div class="crm-timeline__card-top_info_left">
					<ChangeStreamButton v-if="changeStreamButton" v-bind="changeStreamButton" ref="changeStreamButton"></ChangeStreamButton>
					<Title :title="title" :action="titleAction"></Title>
					<Hint v-if="infoHelper" v-bind="infoHelper"></Hint>
				</div>
				<div ref="tags" class="crm-timeline__card-top_info_right">
					<Tag
						v-for="(tag, index) in visibleAndAscSortedTags"
						:key="index"
						v-bind="tag"
					/>
					<FormatDate
						v-if="isShowDate"
						:timestamp="date"
						:use-short-time-format="useShortTimeFormat"
						:date-placeholder="datePlaceholder"
						class="crm-timeline__card-time"
					/>
				</div>
			</div>
			<div class="crm-timeline__card-top_user">
				<User v-bind="user"></User>
			</div>
		</div>
	`
};

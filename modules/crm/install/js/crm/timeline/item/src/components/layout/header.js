import {ChangeStreamButton} from './header/change-stream-button'
import {Title} from './header/title'
import {Tag} from './header/tag'
import {User} from './header/user'
import {FormatDate} from './header/format-date';
import {Runtime} from 'main.core';

export const Header = {
	components: {
		ChangeStreamButton,
		Title,
		Tag,
		User,
		FormatDate,
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
	},
	inject: [
		'isReadOnly',
	],
	computed: {
		visibleTags(): Array
		{
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
	},
	methods: {
		isVisibleTagFilter(tag): Boolean {
			return tag.state !== 'hidden' && tag.scope !== 'mobile' && (!this.isReadOnly || !tag.hideIfReadonly);
		},

		tagsAscSorter(tagA, tagB): Number {
			return tagA.sort - tagB.sort;
		},
	},
	template: `
		<div class="crm-timeline__card-top">
			<div class="crm-timeline__card-top_info">
				<div class="crm-timeline__card-top_info_left">
					<ChangeStreamButton v-if="changeStreamButton" v-bind="changeStreamButton"></ChangeStreamButton>
					<Title :title="title" :action="titleAction"></Title>
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

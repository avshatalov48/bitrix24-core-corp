import { Runtime, Type } from 'main.core';
import { ChangeStreamButton } from './header/change-stream-button';
import { ColorSelector } from './header/color-selector';
import { FormatDate } from './header/format-date';
import { Hint } from './header/hint';
import { Tag } from './header/tag';
import { Title } from './header/title';
import { User } from './header/user';

export const Header = {
	components: {
		ColorSelector,
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
		changeStreamButton: Object | null,
		tags: Object,
		user: Object,
		infoHelper: Object,
		colorSettings: {
			type: Object,
			required: false,
			default: null,
		},
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
				? Object.values(this.tags).filter((element) => this.isVisibleTagFilter(element))
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
				'crm-timeline__card-top',
				{
					'--log-message': this.isReadOnly || this.isLogMessage,
				},
			];
		},
	},
	methods: {
		isVisibleTagFilter(tag): Boolean
		{
			return (
				tag.state !== 'hidden'
				&& tag.scope !== 'mobile'
				&& (!this.isReadOnly || !tag.hideIfReadonly)
			);
		},

		tagsAscSorter(tagA, tagB): Number
		{
			return tagA.sort - tagB.sort;
		},

		getChangeStreamButton(): ?Object
		{
			return this.$refs.changeStreamButton;
		},
	},
	created()
	{
		this.$watch(
			'colorSettings',
			(newColorSettings) => {
				this.$refs.colorSelector.setValue(newColorSettings.selectedValueId);
			},
			{
				deep: true,
			},
		);
	},
	template: `
		<div :class="className">
			<div class="crm-timeline__card-top_info">
				<div class="crm-timeline__card-top_info_left">
					<ChangeStreamButton 
						v-if="changeStreamButton" 
						v-bind="changeStreamButton" 
						ref="changeStreamButton"
					/>
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
			<div class="crm-timeline__card-top_components-container">
				<ColorSelector
					v-if="colorSettings"
					ref="colorSelector"
					:valuesList="colorSettings.valuesList"
					:selectedValueId="colorSettings.selectedValueId"
					:readOnlyMode="colorSettings.readOnlyMode"
				/>
				<User v-bind="user"></User>
			</div>
		</div>
	`,
};

import { Alert } from 'ui.alerts';
import { Line } from './line';
import { ColumnTitle } from './column-title';
import { Counter } from './counter';
import { Event, Loc, Tag } from 'main.core';
import { StateScreen } from './state-screen';
import { SearchBar } from './search-bar';

import '../styles/page.css';

const HELPDESK_CODE = '23343056';

export const Page = {
	name: 'Page',

	props: {
		collection: {
			required: true,
			type: Array,
		},
		mappedUserIds: {
			required: true,
			type: Array,
		},
		config: {
			required: true,
			type: {
				companyId: Number,
				isHideInfoAlert: Boolean,
				mode: 'direct' | 'reverse',
			},
		},
		searchActive: {
			required: true,
			type: Boolean,
		},
		dataLoading: {
			required: true,
			type: Boolean,
		},
	},

	components: {
		Line,
		ColumnTitle,
		Counter,
		StateScreen,
		SearchBar,
	},

	emits:
	[
		'createLink',
		'removeLink',
		'closeAlert',
		'search',
	],

	mounted(): void
	{
		if (!this.config.isHideInfoAlert)
		{
			this.createAlert();
		}
	},

	computed:
	{
		isSearchResultEmpty(): boolean
		{
			return this.collection.length === 0 && this.searchActive;
		},
		searchPlaceholder(): string
		{
			return this.config.mode === 'direct'
				? Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_DIRECT')
				: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_REVERSE');
		},
	},

	methods: {
		createAlert(): void
		{
			const moreButton = Tag.render`<span class="hr-hcmlink-mapping-alert-container__more-button">${Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SHOW_MORE_BUTTON')}</span>`;
			const alert = new Alert({
				text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_ALERT_INFO'),
				color: Alert.Color.PRIMARY,
				size: Alert.Size.MD,
				closeBtn: true,
				animated: true,
				customClass: 'hr-hcmlink-mapping-alert-container',
				afterMessageHtml: moreButton,
			});

			alert.renderTo(this.$refs.alertContainer);

			Event.bind(alert.getCloseBtn(), 'click', this.onCloseAlertButton);
			Event.bind(moreButton, 'click', this.showDocumentation);
		},
		showDocumentation(event): void
		{
			if (top.BX.Helper)
			{
				event.preventDefault();
				top.BX.Helper.show(`redirect=detail&code=${HELPDESK_CODE}`);
			}
		},
		onCloseAlertButton(): void
		{
			this.$emit('closeAlert');
		},
		onCreateLink(options): void
		{
			this.$emit('createLink', options);
		},
		onRemoveLink(options): void
		{
			this.$emit('removeLink', options);
		},
		onSearchPersonName(query): void
		{
			this.$emit('search', query);
		},
	},

	template: `
		<div 
			class="hr-hcmlink-sync__page-subtitle-box"
			:class="{'--alert-hidden': config.isHideInfoAlert}"
		>
			<div class="hr-hcmlink-sync__page-subtitle">
				{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_PAGE_TITLE') }}
			</div>
			<div class="hr-hcmlink-sync__search-container">
				<SearchBar
					:placeholder="searchPlaceholder"
					:debounceWait="500"
					@search="onSearchPersonName"
				/>
			</div>
		</div>
		<div
			ref="alertContainer"
			class="hr-hcmlink-mapping-alert"
			:class="{'--hide': config.isHideInfoAlert}"
		></div>
		<div  v-if="isSearchResultEmpty" class="hr-hcmlink-mapping-page-state-container">
			<StateScreen
				status="searchNotFound"
				:isBlock="true"
				:mode=config.mode
			></StateScreen>
		</div>
		<div v-if="!isSearchResultEmpty && !dataLoading" class="hr-hcmlink-mapping-page-container" ref="container">
			<div class="hr-hcmlink-mapping-page-container__wrapper">
				<ColumnTitle
					:mode=config.mode
				></ColumnTitle>
				<div
					v-for="item in collection"
					:key="item.id"
				>
					<Line
						:item=item
						:config=config
						:mappedUserIds=mappedUserIds
						@createLink="onCreateLink"
						@removeLink="onRemoveLink"
					></Line>
				</div>
			</div>
			<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_right"
				 ref="person_wrapper" :class="[this.config.mode === 'direct' ? '--person' : '--user']"></div>
			<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_left"
				 ref="person_wrapper" :class="[this.config.mode === 'direct' ? '--user' : '--person']"></div>
		</div>
	`,
};

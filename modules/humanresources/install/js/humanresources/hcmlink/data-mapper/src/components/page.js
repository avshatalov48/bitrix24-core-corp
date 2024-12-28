import { Alert } from 'ui.alerts';
import { Line } from './line';
import { ColumnTitle } from './column-title';
import { Counter } from './counter';
import { Event, Loc, Tag } from 'main.core';

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
				mode: String,
				isHideInfoAlert: Boolean,
			},
		},
	},

	components: {
		Line,
		ColumnTitle,
		Counter,
	},

	emits:
	[
		'createLink',
		'removeLink',
		'closeAlert',
	],

	mounted()
	{
		if (!this.config.isHideInfoAlert)
		{
			this.showAlert();
		}
	},

	methods: {
		showAlert()
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
		showDocumentation(event)
		{
			if (top.BX.Helper)
			{
				event.preventDefault();
				top.BX.Helper.show(`redirect=detail&code=${HELPDESK_CODE}`);
			}
		},
		onCloseAlertButton()
		{
			this.$emit('closeAlert');
		},
		onCreateLink(options)
		{
			this.$emit('createLink', options);
		},
		onRemoveLink(options)
		{
			this.$emit('removeLink', options);
		},
	},

	template: `
		<div>
			<div ref="alertContainer" v-if="!config.isHideInfoAlert"></div>
			<div class="hr-hcmlink-mapping-page-container" ref="container">
				<div style="z-index: 100">
					<ColumnTitle
						:mode = config.mode
					></ColumnTitle>
					<div
						v-for="item in collection"
						:key="item.id"
					>
						<Line
							:item = item
							:config = config
							:mappedUserIds=mappedUserIds
							@createLink="onCreateLink"
							@removeLink="onRemoveLink"
						></Line>
					</div>
				</div>
				<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_right" ref="person_wrapper" v-if="config.mode == 'direct'"></div>
				<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_left" ref="person_wrapper" v-if="config.mode == 'reverse'"></div>
			</div>
		</div>
	`,
};

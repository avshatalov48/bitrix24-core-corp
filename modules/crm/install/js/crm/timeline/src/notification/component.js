import {Vue} from 'ui.vue';
import HistoryItemMixin from '../mixins/history-item';
import Author from '../components/author';
import {ajax, Loc} from 'main.core';
import {PULL} from 'pull.client';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	components: {
		'author': Author,
	},
	data()
	{
		return {
			entityData: null,
			messageId: null,
			text: null,
			title: null,
			status: {
				name: null,
				semantics: null,
				description: null,
			},
			provider: null,
			isRefreshing: false,
		};
	},
	created()
	{
		this.entityData = this.self.getAssociatedEntityData();

		if (this.entityData['MESSAGE_INFO'])
		{
			this.setMessageInfo(this.entityData['MESSAGE_INFO']);
		}

		PULL.subscribe({
			moduleId: 'notifications',
			command: 'message_update',
			callback: (params) => {
				if (params.message.ID == this.messageId)
				{
					this.refresh();
				}
			}
		});
		if (this.entityData['PULL_TAG_NAME'])
		{
			PULL.extendWatch(this.entityData['PULL_TAG_NAME']);
		}
	},
	methods: {
		setMessageInfo(messageInfo)
		{
			this.messageId = messageInfo['MESSAGE']['ID'];

			if (
				messageInfo['HISTORY_ITEMS']
				&& Array.isArray(messageInfo['HISTORY_ITEMS'])
				&& messageInfo['HISTORY_ITEMS'].length > 0
				&& messageInfo['HISTORY_ITEMS'][0]
				&& messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']
				&& messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION']
			)
			{
				this.provider = messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION'];
				this.title = this.provider + ' ' + Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE');
			}
			else
			{
				this.title = this.capitalizeFirstLetter(Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE'));
			}

			if (
				messageInfo['HISTORY_ITEMS']
				&& Array.isArray(messageInfo['HISTORY_ITEMS'])
				&& messageInfo['HISTORY_ITEMS'].length > 0
				&& messageInfo['HISTORY_ITEMS'][0]
				&& messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']
				&& messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION']
			)
			{
				this.status.name = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION'];
				this.status.semantics = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['SEMANTICS'];
				this.status.description = messageInfo['HISTORY_ITEMS'][0]['ERROR_MESSAGE'];
			}

			this.text = messageInfo['MESSAGE']['TEXT']
				? messageInfo['MESSAGE']['TEXT']
				: Loc.getMessage('CRM_TIMELINE_NOTIFICATION_NO_MESSAGE_TEXT_2');
		},
		refresh()
		{
			if (this.isRefreshing)
			{
				return;
			}

			this.isRefreshing = true;

			ajax.runAction(
				'crm.timeline.notification.getmessageinfo',
				{
					data: {
						messageId: this.messageId
					}
				}
			).then((result) => {
				this.setMessageInfo(result.data);
				this.isRefreshing = false;
			}).catch((result) => {
				this.isRefreshing = false;
			});
		},
		viewActivity()
		{
			this.self.view();
		},
		capitalizeFirstLetter(str, locale= navigator.language)
		{
			return str.replace(/^\p{CWU}/u, char => char.toLocaleUpperCase(locale));
		}
	},
	computed: {
		communication()
		{
			return this.entityData['COMMUNICATION'] ? this.entityData['COMMUNICATION'] : null;
		},
		statusClass()
		{
			return {
				'crm-entity-stream-content-event-process': this.status.semantics === 'process',
				'crm-entity-stream-content-event-successful': this.status.semantics === 'success',
				'crm-entity-stream-content-event-missing': this.status.semantics === 'failure',
				'crm-entity-stream-content-event-error-tip': this.isStatusError,
			};
		},
		isStatusError()
		{
			return (this.status.semantics === 'failure' && !!this.status.description);
		},
		statusErrorDescription()
		{
			return this.isStatusError ? this.status.description : '';
		},
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-sms">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-sms"></div>
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event">
					<div class="crm-entity-stream-content-header">
						<a
							@click.prevent="viewActivity"
							href="#"
							class="crm-entity-stream-content-event-title"
						>
							{{title}}
						</a>
						<span
							v-if="status"
							:class="statusClass"
							:title="statusErrorDescription"
						>
							{{status.name}}
						</span>
						<span class="crm-entity-stream-content-event-time">{{createdAt}}</span>
					</div>
					<div class="crm-entity-stream-content-detail">
						<div class="crm-entity-stream-content-detail-sms">
							<div class="crm-entity-stream-content-detail-sms-status">
								${Loc.getMessage('CRM_TIMELINE_NOTIFICATION_VIA')} 
								<strong>
									${Loc.getMessage('CRM_TIMELINE_NOTIFICATION_BITRIX24')}
								</strong>
							</div>
							<div class="crm-entity-stream-content-detail-sms-fragment">
								<span>{{text}}</span>
							</div>
						</div>
						<div
							v-if="communication"
							class="crm-entity-stream-content-detail-contact-info"
						>
							{{BX.message('CRM_TIMELINE_SMS_TO')}}
							<a v-if="communication.SHOW_URL" :href="communication.SHOW_URL">
								{{communication.TITLE}}
							</a>
							<template v-else>
								{{communication.TITLE}}
							</template>
							<span v-if="communication.VALUE">{{communication.VALUE}}</span>
							<template v-if="provider">
								${Loc.getMessage('CRM_TIMELINE_NOTIFICATION_IN_MESSENGER')} {{provider}}
							</template>
						</div>
					</div>
					<author v-if="author" :author="author"></author>
				</div>
			</div>
		</div>	
	`
});

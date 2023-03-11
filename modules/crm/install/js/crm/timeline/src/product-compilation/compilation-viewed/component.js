import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import HistoryItemMixin from '../../mixins/history-item';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	computed: {
		legend()
		{
			var compilationCreationDate = BX.prop.getString(this.data, 'COMPILATION_CREATION_DATE', '');
			var legendTextTemplate = Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_VIEWED_LEGEND');

			return legendTextTemplate.replace('#DATE_CREATE#', compilationCreationDate);
		},
		authorFormattedName()
		{
			return this?.data?.AUTHOR?.FORMATTED_NAME;
		},
		authorHref()
		{
			return this?.data?.AUTHOR?.SHOW_URL;
		},
		authorImageUrl()
		{
			return this?.data?.AUTHOR?.IMAGE_URL;
		},
		authorImageStyle()
		{
			if (this.authorImageUrl)
			{
				return {
					backgroundImage: "url('" + encodeURI(this.authorImageUrl) + "')",
					backgroundSize: '21px',
				}
			}

			return {};
		}
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-history">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-info"></div>
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event">
					<div class="crm-entity-stream-content-header">
						<div class="crm-entity-stream-content-event-title">
							${Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_TITLE')}
						</div>
						<span class="crm-entity-stream-content-event-done">
							${Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_VIEWED')}
						</span>
						<span class="crm-entity-stream-content-event-time">{{createdAt}}</span>
					</div>
					<div class="crm-entity-stream-content-detail">
						<div class="crm-entity-stream-content-detail-description">
							${Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_VIEWED_DESCRIPTION')}
						</div>
					</div>
						<a
							class="ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee" 
							v-bind:title="authorFormattedName"
							target="_blank"
							v-bind:href="authorHref"
						>
							<i :style="authorImageStyle"></i>
						</a>
					</div>
			</div>
		</div>
	`
});

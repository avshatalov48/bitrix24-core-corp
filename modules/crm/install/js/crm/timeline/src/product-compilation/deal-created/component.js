import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import HistoryItemMixin from '../../mixins/history-item';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	computed: {
		message()
		{
			const dealTitle = BX.util.htmlspecialchars(this?.data?.NEW_DEAL_DATA?.TITLE);
			const dealLegend = this?.data?.NEW_DEAL_DATA?.LEGEND;
			const dealUrl = this?.data?.NEW_DEAL_DATA?.SHOW_URL;
			const dealTitleLink = `<a href="${dealUrl}">${dealTitle}</a>`;
			let message = Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_DEAL_CREATED');
			message = message.replace('#DEAL_TITLE#', dealTitleLink);
			message = message.replace('#DEAL_LEGEND#', dealLegend);

			return message;
		},
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-advice"></div>
			<div class="crm-entity-stream-advice-content">
				<div class="crm-entity-stream-advice-info" v-html="message">
				</div>
			</div>
		</div>
	`
});

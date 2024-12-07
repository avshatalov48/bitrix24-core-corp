import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import HistoryItemMixin from '../../mixins/history-item';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-advice"></div>
			<div class="crm-entity-stream-advice-content">
				<div class="crm-entity-stream-advice-info">
					${Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_ORDER_EXISTS_NOTICE')}
				</div>
			</div>
		</div>
	`
});

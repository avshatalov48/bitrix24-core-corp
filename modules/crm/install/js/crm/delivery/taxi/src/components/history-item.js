import {Vue} from 'ui.vue';
import AuthorComponent from "../components/author";

export default {
	components: {
		'author': AuthorComponent,
	},
	props: {
		author: {
			required: false,
			type: Object
		},
		createdAt: {
			required: false,
			type: String
		}
	},
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-new">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi"></div>
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event">
					<div class="crm-entity-stream-content-header">
						<span class="crm-entity-stream-content-event-title">
							<slot name="title"></slot>
						</span>
						<slot name="status"></slot>
						<span class="crm-entity-stream-content-event-time">
							<span v-html="createdAt"></span>
						</span>
					</div>
					<div class="crm-entity-stream-content-detail">
						<slot></slot>
					</div>
					<author v-if="author" :author="author"></author>
				</div>
			</div>
		</div>
	`
};

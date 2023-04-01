import {InfoPopupContentTableField} from "./InfoPopupContentTableField";

export const InfoPopupContentTable = {
	components: {
		InfoPopupContentTableField,
	},
	props: {
		fields: Object,
	},
	template: `
		<div class="crm__info-popup_content-table">
			<ul class="crm__info-popup_content-table-fields">
				<info-popup-content-table-field
					v-for="(field, index) in fields"
					:key="index"
					:title="field.title"
					:content-block="field.contentBlock"
				/>
			</ul>
		</div>
	`
}
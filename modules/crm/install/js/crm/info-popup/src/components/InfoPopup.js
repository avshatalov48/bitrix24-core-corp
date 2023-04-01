import {InfoPopupHeader} from "./InfoPopupHeader";
import {InfoPopupContentTable} from "./InfoPopupContentTable";

export const InfoPopup = {
	name: 'InfoPopup',
	components: {
		InfoPopupHeader,
		InfoPopupContentTable,
	},
	props: {
		header: {
			type: Object,
			required: false,
			default: () => ({
				title: '',
				subtitle: '',
				hint: '',
			}),
		},

		fields: {
			type: Object,
		},
	},
	template: `
		<div class="crm__info-popup">
			<info-popup-header
				:title="header.title"
				:subtitle="header.subtitle"
				:hint="header.hint"
				:icon="header.icon"
				
			/>
			<body class="crm__info-popup_body">
				<info-popup-content-table
					:fields="fields"
				/>
			</body>
		</div>`
}
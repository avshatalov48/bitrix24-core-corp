import UseLocalize from '../mixins/uselocalize';
import LogoComponent from '../components/logo';
import InfoComponent from '../components/info';

export default {
	props: {
		logo: {required: false},
		serviceName: {required: false},
		methodName: {required: false},
	},
	components: {
		'logo': LogoComponent,
		'info': InfoComponent,
	},
	mixins: [UseLocalize],
	template: `
		<div class="crm-entity-stream-content-delivery-row">
			<div class="crm-entity-stream-content-delivery-title">
				<div class="crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car"></div>
				<div class="crm-entity-stream-content-delivery-title-contnet">
					<logo v-if="logo" :logo="logo"></logo>
					<info
						v-if="serviceName || methodName"
						:name="serviceName"
						:method="methodName"
					></info>
				</div>
			</div>
			<slot name="bottom"></slot>
		</div>
	`
};

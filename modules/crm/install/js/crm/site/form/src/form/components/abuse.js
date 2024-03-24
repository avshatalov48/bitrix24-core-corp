import { Factory } from "../../field/factory";
import * as Window from "../../window/registry";

const AbuseBlock = {
	props: ['messages', 'abuseLink'],
	components: Object.assign(
		Window.Components.Definition,
		{
			field: Factory.getComponent(),
		}
	),
	data: function () {
		return {
			popupShown: false,
		};
	},
	template: `
		<div>
			<span class="b24-form-sign-abuse">
				<!--noindex--><a :href="abuseLink" target="_blank" rel="nofollow" class="b24-form-sign-abuse-link">
					{{ messages.get('abuseLink') }}
				</a><!--/noindex-->
				<span class="b24-form-sign-abuse-help"
					:title="messages.get('abuseInfoHint')"
					@click.capture="showPopup"
				></span>
			</span>

			<b24-popup
				:show="popupShown"
				:title="this.messages.get('abuseLink')"
				:scrollDown="false"
				:hideOnOverlayClick="true"
				@hide="hidePopup"
			>
				<div class="b24-form-sign-abuse-popup">
					{{this.messages.get('abuseInfoHint')}}
				</div>
			</b24-popup>
		</div>
	`,
	methods: {
		showPopup()
		{
			this.popupShown = true;
		},
		hidePopup()
		{
			this.popupShown = false;
		},
	}
};

export {
	AbuseBlock
};

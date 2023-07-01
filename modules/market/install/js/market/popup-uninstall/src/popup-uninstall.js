import {marketUninstallState} from "market.uninstall-store";
import { mapActions } from 'ui.vue3.pinia';

export const PopupUninstall = {
	props: [
		'appCode', 'appName',
	],
	mounted() {
		this.setNodesInfo(this.appCode, this.$refs['uninstall-confirm-content']);
	},
	methods: {
		...mapActions(marketUninstallState, ['setNodesInfo']),
	},
	template: `
		<div ref="uninstall-confirm-content" style="display: none">
			<div class="market_delete_confirm">
				<div class="market_delete_confirm_text">
					{{ $Bitrix.Loc.getMessage('MARKET_APPLICATION_JS_DELETE_CONFIRM', {'#APP_NAME#': appName ?? appCode}) }}
				</div>
				<div class="market_delete_confirm_cb">
					<input type="checkbox" name="delete-data">&nbsp;
					<label for="delete_data">
						{{ $Bitrix.Loc.getMessage('MARKET_APPLICATION_JS_DELETE_CONFIRM_CLEAN') }}
					</label>
				</div>
			</div>
		</div>
	`,
}
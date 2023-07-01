this.BX = this.BX || {};
(function (exports,market_uninstallStore,ui_vue3_pinia) {
	'use strict';

	const PopupUninstall = {
	  props: ['appCode', 'appName'],
	  mounted() {
	    this.setNodesInfo(this.appCode, this.$refs['uninstall-confirm-content']);
	  },
	  methods: {
	    ...ui_vue3_pinia.mapActions(market_uninstallStore.marketUninstallState, ['setNodesInfo'])
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
	`
	};

	exports.PopupUninstall = PopupUninstall;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Vue3.Pinia));

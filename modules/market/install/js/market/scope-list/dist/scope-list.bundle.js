this.BX = this.BX || {};
(function (exports) {
	'use strict';

	const ScopeList = {
	  props: ['appInfo'],
	  mounted() {
	    top.BX.loadCSS(['/bitrix/components/bitrix/market.install/templates/scope_list/style.css']);
	  },
	  template: `
		<div class="market-app__scope-list_wrapper">
			<div class="market-app__scope-list_header">
				<div class="market-app__scope-list_title">
					<div class="market-app__scope-list_title-icon">
						<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g clip-path="url(#clip0_5502_492414)">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M25.489 18.891C24.178 20.202 22.0525 20.202 20.7415 18.891C19.4305 17.58 19.4305 15.4545 20.7415 14.1435C22.0525 12.8325 24.178 12.8325 25.489 14.1435C26.8 15.4545 26.8 17.58 25.489 18.891ZM19.7237 22.5357C22.3566 24.0213 25.7567 23.643 27.9989 21.4009C30.696 18.7037 30.696 14.3308 27.9989 11.6336C25.3017 8.93647 20.9288 8.93647 18.2316 11.6336C15.9909 13.8743 15.6117 17.2716 17.094 19.9037L8.95929 28.0384C8.74219 28.2555 8.74219 28.6075 8.95929 28.8246L10.8039 30.6692C11.021 30.8863 11.373 30.8863 11.5901 30.6692L12.8904 29.369L14.3419 30.8204C14.5422 31.0208 14.8672 31.0208 15.0676 30.8204L16.882 29.0061C17.0824 28.8057 17.0823 28.4807 16.882 28.2803L15.4305 26.8289L19.7237 22.5357Z" fill="#559BE6"/>
							</g>
							<defs>
								<clipPath id="clip0_5502_492414">
									<rect width="30" height="30" fill="white" transform="translate(5 5.5)"/>
								</clipPath>
							</defs>
						</svg>
					</div>
					<div class="market-app__scope-list_title-text">
						{{ $Bitrix.Loc.getMessage('MARKET_SCOPE_JS_DATA_SECURITY_TITLE') }}
					</div>
				</div>
				<button class="ui-btn ui-btn-light-border"
						onclick="BX.Helper.show('redirect=detail&code=17227276')"
				>{{ $Bitrix.Loc.getMessage('MARKET_SCOPE_JS_BTN_MORE') }}</button>
			</div>
			
			<div class="market-app__scope-list_content">
				<div class="market-app__scope-list_content-top">
					<div class="market-app__scope-list_logo">
						<img width="72" alt="icon" class="market-app__scope-list_logo-img"
							 :src="appInfo.ICON"
						>
					</div>
					<div class="market-app__scope-list-name"
						 :title="appInfo.NAME"
					>{{ appInfo.NAME }}</div>
				</div>
				<div class="market-app__scope-list_content-middle">
					{{ $Bitrix.Loc.getMessage('MARKET_SCOPE_JS_APP_REQUESTS_ACCESS') }}
				</div>
				<div class="market-app__scope-list_rights-list">
					<div class="market-app__scope-list_rights-item"
						 v-for="scope in appInfo.SCOPES"
					>
						<div class="market-app__scope-list_rights-item-header">
							<img class="market-app__scope-list_rights-item-icon" alt=""
								 :src="scope.ICON"
							>
							<div class="market-app__scope-list_rights-item-title">{{ scope.TITLE }}</div>
						</div>
						<div class="market-app__scope-list_rights-item-description">{{ scope.DESCRIPTION }}</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	exports.ScopeList = ScopeList;

}((this.BX.Market = this.BX.Market || {})));

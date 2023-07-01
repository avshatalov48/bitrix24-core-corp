import {marketInstallState} from "market.install-store";
import { mapState, mapActions } from 'ui.vue3.pinia';

import "./popup-install.css";

export const PopupInstall = {
	props: [
		'appInfo', 'licenseInfo'
	],
	computed: {
		showMoreScopes: function () {
			return this.appInfo.SCOPES_MORE_BUTTON === 'Y';
		},
		isInstall: function () {
			return this.appInfo.BUTTONS.hasOwnProperty('INSTALL') && this.appInfo.BUTTONS.INSTALL === 'Y'
		},
		isUpdate: function () {
			return this.appInfo.BUTTONS.hasOwnProperty('UPDATE') && this.appInfo.BUTTONS.UPDATE === 'Y'
		},
		...mapState(marketInstallState, [
			'installStep', 'licenseError', 'openAppAfterInstall', 'timer', 'slider', 'versionSlider',
		]),
	},
	mounted() {
		this.setPopupNode(this.appInfo.CODE, this.$refs['market-popup-install']);
	},
	methods: {
		...mapActions(marketInstallState, [
			'cleanLicenseError', 'isRestOnlyApp', 'openSliderWithContent', 'reloadSlider', 'isSubscriptionApp',
			'isHiddenBuy', 'prevVersion', 'nextVersion', 'setPopupNode', 'openApplication',
		]),
	},
	template: `
		<div class="market-popup" ref="market-popup-install">
			<template v-if="installStep === 1">
				<div class="market-popup__container"
					 v-if="isInstall"
				>
					<div class="market-popup__header">
						<div class="market-popup__header-logo">
							<img class="market-popup__header-logo_img" :src="appInfo.ICON" alt="">
						</div>
						<div class="market-popup__header-info">
							<div class="market-popup__header-title"
								 v-html="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_INSTALL_TITLE', {'#APP_NAME#' : appInfo.NAME})"
							></div>
							<div class="market-popup__header-additional">
								<div class="market-popup__header-label --version">
									{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_VERSION', {'#APP_VERSION#': appInfo.VER}) }}
								</div>
								<div class="market-popup__header-label">
									<template v-if="isSubscriptionApp()">
										<svg class="market-popup__header-label_svg" width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="7.5" cy="7.5" r="7.5" fill="#8DBB00"/>
											<path d="M4.41361 6.64596L7.94914 10.1815L6.53493 11.5957L2.9994 8.06017L4.41361 6.64596Z" fill="white"/>
											<path d="M12.1918 5.93885L6.53493 11.5957L5.12072 10.1815L10.7776 4.52464L12.1918 5.93885Z" fill="white"/>
										</svg>
										{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_AVAILABLE_IN_SUBSCRIPTION') }}
									</template>
									<template v-else>
										{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IS_FREE') }}
									</template>
								</div>
								<div class="market-popup__header_about-buying"
									 v-if="isHiddenBuy()"
								>
									{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IN_APP_PURCHASES') }}
								</div>
							</div>
						</div>
					</div>

					<div class="market-popup__body">
						<div class="market-popup__row">
							<div class="market-popup__title">
								<svg class="market-popup__title_svg" width="24" height="24" viewBox="0 0 24 24"
									 fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd"
										  d="M8.9478 8.62038C8.9478 6.9345 10.3145 5.56782 12.0004 5.56782C13.6862 5.56782 15.0529 6.9345 15.0529 8.62038V11.0187H8.11328C7.00871 11.0187 6.11328 11.9142 6.11328 13.0187V17.7403C6.11328 18.8449 7.00871 19.7403 8.11328 19.7403H15.8874C16.992 19.7403 17.8874 18.8449 17.8874 17.7403V13.0187C17.8874 12.0774 17.2371 11.2879 16.3612 11.0752V8.62038C16.3612 6.21198 14.4088 4.25958 12.0004 4.25958C9.59196 4.25958 7.63956 6.21198 7.63956 8.62038H8.9478ZM12.4626 15.518C12.7254 15.3145 12.8946 14.996 12.8946 14.6378C12.8946 14.0234 12.3965 13.5253 11.7821 13.5253C11.1676 13.5253 10.6695 14.0234 10.6695 14.6378C10.6695 14.9959 10.8387 15.3144 11.1014 15.5179V17.1268H12.4626V15.518Z"
										  fill="#828B95"/>
								</svg>
								<span class="market-popup__title_text">{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_ACCESS') }}</span>
							</div>
							<span class="market-popup__link-more"
								  v-if="!showMoreScopes"
								  @click="openSliderWithContent(slider.scope, 491)"
							>
							{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_MORE') }}								
							</span>
						</div>
						<div class="market-popup__content">
							<div class="market-popup__content-description">
								{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_APP_REQUESTS_PERMISSIONS_TO_WORK') }}
							</div>
							<div class="market-popup__modules">
								<template v-for="(scope, index) in appInfo.SCOPES">
									<div class="market-popup__modules-item" v-if="index < appInfo.SCOPES_TO_SHOW">
										<img class="market-popup__modules-item_img"
											 :src="scope.ICON" alt="">
										<span class="market-popup__modules-item_text" 
											  :title="scope.TITLE">
											{{ scope.TITLE }}</span>
									</div>
								</template>
								<div class="market-popup__modules_more-block">
									<span class="market-popup__modules-item_link-more"
										  v-if="showMoreScopes"
										  @click="openSliderWithContent(slider.scope, 491)"
									>
										{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_SHOW_ALL_ACCESSES') }} ({{ appInfo.SCOPES.length }})
									</span>
								</div>
							</div>
							<div class="market-popup__agreement">
								<div id="market-license-error" style="color: red; margin-bottom: 10px; font-size: 12px;"
									 v-if="licenseError.length > 0"
								>{{ licenseError }}</div>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.TERMS_OF_SERVICE_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-tos-license"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.TERMS_OF_SERVICE_TEXT"
									></div>
								</label>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.EULA_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-install-license"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.EULA_TEXT"
									></div>
								</label>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.PRIVACY_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-install-confidentiality"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.PRIVACY_TEXT"
									></div>
								</label>
							</div>
						</div>
					</div>
				</div>
				<div class="market-popup__container"
					 v-if="isUpdate"
				>
					<div class="market-popup__header">
						<div class="market-popup__header-logo">
							<img class="market-popup__header-logo_img" :src="appInfo.ICON" alt="">
						</div>
						<div class="market-popup__header-info">
							<div class="market-popup__header-title"
								 v-html="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_UPDATE_TITLE', {'#APP_NAME#' : appInfo.NAME})"
							></div>
							<div class="market-popup__header-additional">
								<div class="market-popup__header-label">
									<template v-if="isSubscriptionApp()">
										<svg class="market-popup__header-label_svg" width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="7.5" cy="7.5" r="7.5" fill="#8DBB00"/>
											<path d="M4.41361 6.64596L7.94914 10.1815L6.53493 11.5957L2.9994 8.06017L4.41361 6.64596Z" fill="white"/>
											<path d="M12.1918 5.93885L6.53493 11.5957L5.12072 10.1815L10.7776 4.52464L12.1918 5.93885Z" fill="white"/>
										</svg>
										{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_AVAILABLE_IN_SUBSCRIPTION') }}
									</template>
									<template v-else>
										{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IS_FREE') }}
									</template>
								</div>
								<div class="market-popup__header-label">
									<svg class="market-popup__header-label_svg" width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
										<circle opacity="0.75" cx="7.5" cy="7.5" r="7.5" fill="#82888F"/>
										<path d="M4 7.5C4 9.433 5.567 11 7.5 11C9.433 11 11 9.433 11 7.5C11 5.567 9.433 4 7.5 4" stroke="white" stroke-width="2"/>
										<path d="M8.5 0.964844V7.96484L5 4.46484L8.5 0.964844Z" fill="white"/>
									</svg>
									{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_THERE_ARE_UPDATES') }}
								</div>
								<div class="market-popup__header_about-buying"
									 v-if="isHiddenBuy()"
								>
									{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IN_APP_PURCHASES') }}
								</div>
							</div>
						</div>
					</div>

					<div class="market-popup__body">
						<div class="market-popup__row">
							<div class="market-popup__title">
								<svg class="market-popup__title_svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M9 8.27273C9 7.56982 9.63207 7 10.4118 7H15.8855C16.2806 7 16.6575 7.14919 16.9248 7.41135L20.6275 11.0422C20.8671 11.2771 21 11.5845 21 11.9036V19.7273C21 20.4302 20.3679 21 19.5882 21H10.4118C9.63207 21 9 20.4302 9 19.7273V8.27273ZM15.8855 8.27273H10.4118V19.7273H19.5882V11.9036L15.8855 8.27273Z" fill="#828B95"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M4 5.27273C4 4.56982 4.58186 4 5.29961 4H10.3385C10.7022 4 11.0492 4.14919 11.2953 4.41135L13 6.22727H11.2346L10.3385 5.27273H5.29961V16.7273H8.51711V18H5.29961C4.58185 18 4 17.4302 4 16.7273V5.27273Z" fill="#828B95"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M15.0008 16.7281C16.3064 16.7281 17.3649 15.6697 17.3649 14.3641C17.3649 13.0584 16.3064 12 15.0008 12C13.6951 12 12.6367 13.0584 12.6367 14.3641C12.6367 15.6697 13.6951 16.7281 15.0008 16.7281ZM14.6967 15.6551L15.1425 15.2093L16.4798 13.872L16.0341 13.4262L14.6967 14.7635L14.0281 14.0949L13.5823 14.5406L14.251 15.2093L14.6967 15.6551Z" fill="#828B95"/>
								</svg>
								<span class="market-popup__title_text">{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_VERSIONS') }}</span>
							</div>
							<div class="market-popup__warning">
								<svg class="market-popup__warning-icon" width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M6.48909 7.62939e-05C10.0728 7.62939e-05 12.978 2.90507 12.978 6.48914C12.978 10.0727 10.0728 12.9779 6.48909 12.9779C2.90525 12.9779 0 10.0727 0 6.48914C0 2.90507 2.90525 7.62939e-05 6.48909 7.62939e-05ZM7.37517 2.94937C7.37517 3.48628 6.9357 3.92152 6.39359 3.92152C5.85148 3.92152 5.41201 3.48628 5.41201 2.94937C5.41201 2.41247 5.85148 1.97722 6.39359 1.97722C6.9357 1.97722 7.37517 2.41247 7.37517 2.94937ZM4.44394 4.82645H6.73796V4.82809H7.37122V9.14414H8.35V10.0049H4.44394V9.14414H5.59095V5.70089H4.44394V4.82645Z" fill="#D5D7DB"/>
								</svg>
								<div class="market-popup__warning-text">
									{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_YOU_HAVE_A_PREVIOUS_VERSION') }}
								</div>
							</div>
						</div>
						<div class="market-popup__content --slider">
							<div class="market-popup__versions-slider" data-role="market-popup__versions-slider">
								<div class="market-popup__versions-nav">
									<div class="market-popup__versions-nav_item"
										 v-for="version in appInfo.VERSIONS_FORMAT"
										 :class="{'--active': versionSlider.currentItem === parseInt(version.INDEX, 10)}"
									>
										<template v-if="parseInt(version.INDEX, 10) === (appInfo.VERSIONS_FORMAT.length - 1)">
											{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_NEW_VERSION') }}
										</template>
										<template v-else>
											{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_PREVIOUS_VERSION') }}
										</template>
										<span class="market-popup__versions-nav_item-selected">
											{{ version.VERSION }}
										</span>
									</div>
								</div>
								<div class="market-popup__versions-content-wrapper">
									<div class="market-popup__versions-content">
										<div class="market-popup__versions-content_item"
											 v-for="version in appInfo.VERSIONS_FORMAT"
											 :class="{'--active': versionSlider.currentItem === parseInt(version.INDEX, 10)}"
											 v-html="version.TEXT"
										></div>
									</div>
								</div>
								<div class="market-popup__versions-nav_arrows">
									<div class="market-popup__versions-nav_arrow --left-arrow"
										 @click="prevVersion"
									>
										<svg width="9" height="16" viewBox="0 0 9 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.85944 13.6572L4.33239 9.13014L3.19838 7.99997L4.33239 6.87073L8.85944 2.34368L7.26197 0.746216L0.0078125 8.00037L7.26197 15.2545L8.85944 13.6572Z" fill="#559BE6"></path></svg>
									</div>
									<div class="market-popup__versions-nav_arrow --right-arrow"
										 @click="nextVersion"
									>
										<svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.160156 2.34355L4.68721 6.8706L5.85979 7.99997L4.68721 9.13002L0.160156 13.6571L1.75762 15.2545L9.01178 8.00037L1.75762 0.746216L0.160156 2.34355Z" fill="#559BE6"></path></svg>
									</div>
								</div>
							</div>
							<div class="market-popup__agreement">
								<div id="market-license-error" style="color: red; margin-bottom: 10px; font-size: 12px;"
									 v-if="licenseError.length > 0"
								>{{ licenseError }}</div>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.TERMS_OF_SERVICE_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-tos-license"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.TERMS_OF_SERVICE_TEXT"
									></div>
								</label>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.EULA_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-install-license"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.EULA_TEXT"
									></div>
								</label>
								<label class="ui-ctl ui-ctl-checkbox ui-ctl-wa"
									   v-if="licenseInfo.PRIVACY_TEXT"
								>
									<input type="checkbox" class="ui-ctl-element" data-role="market-install-confidentiality"
										   @click="cleanLicenseError"
									>
									<div class="market-popup__agreement_label-text"
										 v-html="licenseInfo.PRIVACY_TEXT"
									></div>
								</label>
							</div>
						</div>
					</div>
				</div>
			</template>
			<div class="market-popup__container"
				 v-if="installStep === 2"
			>
				<div class="market-popup__body">
					<span>{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_INSTALLING_THE_APP_ON_YOUR_BITRIX') }}</span>
					<div class="market-install-loader"></div>
				</div>
			</div>
			<div class="market-popup__container"
				 v-if="installStep === 3"
			>
				<div class="market-popup__body">
					<ul class="market-popup__points">
						<li class="market-popup__points-item"
							v-html="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_APPLICATION', {'#APP_NAME#' : '<span>' + appInfo.NAME + '</span>'})"
						></li>
						<li class="market-popup__points-item --light"
							v-if="isRestOnlyApp()"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_APP_WORKS_AUTOMATICALLY') }}
						</li>
						<li class="market-popup__points-item --light"
							v-else
						>
							{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_INSTALLED_APP_LOCATED_APP_TAB') }}
						</li>
					</ul>

					<template
						v-if="isRestOnlyApp()"
					>
						<img class="market-popup__points-img"
							 :src="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IMAGE_ESTABLISHED')"
							 alt="img"
							 
						>
						<div class="market-popup__success-button">
							<button class="ui-btn ui-btn-success"
									@click="reloadSlider"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_READY') }}
							</button>
						</div>
					</template>
					<template
						v-else
					>
						<img class="market-popup__points-img"
							 :src="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_IMAGE_ESTABLISHED_INTERFACE')"
							 alt="img"
						>
						<div class="market-popup__info-app-launch" v-html="$Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_APP_WILL_OPEN_AUTO_AFTER', {'#COUNTER#': openAppAfterInstall})"></div>
						<div class="market-popup__success-button">
							<button class="ui-btn ui-btn-success"
									@click="openApplication"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_POPUP_INSTALL_JS_OPEN_APP') }}
							</button>
						</div>
					</template>
				</div>
			</div>
		</div>
	`,
}
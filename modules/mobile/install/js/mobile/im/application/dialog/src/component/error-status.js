import {Vuex} from "ui.vue.vuex";

export const ErrorStatus = {
	computed:
	{
		...Vuex.mapState({
			application: state => state.application
		}),
	},
	template: `
		<div class="bx-mobilechat-body" key="error-body">
			<div class="bx-mobilechat-warning-window">
				<div class="bx-mobilechat-warning-icon"></div>
				<template v-if="application.error.description"> 
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg" v-html="application.error.description"></div>
				</template> 
				<template v-else>
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-warning-msg">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_TITLE')}}</div>
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_DESC')}}</div>
				</template> 
			</div>
		</div>
	`
};

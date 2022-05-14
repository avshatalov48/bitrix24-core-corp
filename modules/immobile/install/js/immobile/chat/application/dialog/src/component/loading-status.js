export const LoadingStatus = {
	template: `
		<div class="bx-mobilechat-loading-window">
			<svg class="bx-mobilechat-loading-circular" viewBox="25 25 50 50">
				<circle class="bx-mobilechat-loading-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				<circle class="bx-mobilechat-loading-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
			<h3 class="bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_LOADING')}}</h3>
		</div>
	`
};
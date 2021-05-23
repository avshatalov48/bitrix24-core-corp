import {Vue} from 'ui.vue';
import {Text, Type, Event} from "main.core";

export const ContentComponent = {
	props: [
		"invitationLink",
		"structureLink",
		"users",
		"isInvitationAvailable",
	],
	computed: {
		localize(state)
		{
			return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
		}
	},
	methods: {
		showInvitationSlider()
		{
			if (this.isInvitationAvailable)
			{
				BX.SidePanel.Instance.open(this.invitationLink, {cacheable: false, allowChangeHistory: false, width: 1100});
				Event.EventEmitter.emit('BX.Intranet.InvitationWidget:showInvitationSlider');
			}
			else
			{
				this.showHintPopup(
					BX.message("INTRANET_INVITATION_WIDGET_DISABLED_TEXT"),
					document.querySelector("[data-role='invitationPopupButton']")
				);
			}
		},
		showHintPopup(message, bindNode)
		{
			if (!Type.isDomNode(bindNode) || !message)
			{
				return;
			}

			new BX.PopupWindow('inviteHint' + Text.getRandom(8), bindNode, {
				content: message,
				zIndex: 15000,
				angle: true,
				offsetTop: 0,
				offsetLeft: 50,
				closeIcon: false,
				autoHide: true,
				darkMode: true,
				overlay: false,
				maxWidth: 400,
				events: {
					onAfterPopupShow: function () {
						setTimeout(function () {
							this.close();
						}.bind(this), 4000);
					}
				}
			}).show();
		},
		sendAnalytics(code)
		{
			BX.ajax.runAction("intranet.invitationwidget.analyticsLabel", {
				data: {},
				analyticsLabel: {
					helperCode: code,
					headerPopup: "Y"
				}
			}).then((response) => {}, (response) => {});
		},
		showInvitationHelper()
		{
			const code = "limit_why_team_invites";

			BX.UI.InfoHelper.show(code);
			this.sendAnalytics(code);
		},
	},
	template: `
		<div class="license-widget license-widget--invite">
			<div class="license-widget-item license-widget-item--main">
				<div class="license-widget-inner">
					<div class="license-widget-item-icon license-widget-item-icon--invite"></div>
					<div class="license-widget-item-content">
						<div class="license-widget-item-name">
							<span>{{ localize.INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE }}</span>
						</div>
						<div class="license-widget-item-link">
							<span class="license-widget-item-link-text" @click="showInvitationHelper">
								{{ localize.INTRANET_INVITATION_WIDGET_DESC }}
							</span>
						</div>
					</div>
				</div>
				<a 
					data-role="invitationPopupButton"
					class="license-widget-item-btn license-widget-item-btn--invite"
					@click="showInvitationSlider" 
				> 
					{{ localize.INTRANET_INVITATION_WIDGET_INVITE }} 
				</a>	
			</div>
			
			<div class="license-widget-block">
				<div class="license-widget-item license-widget-item--company license-widget-item--active">
					<div class="license-widget-item-logo"></div>
					<div class="license-widget-item-content">
						<div class="license-widget-item-name">
							<span>{{ localize.INTRANET_INVITATION_WIDGET_STRUCTURE }}</span>
						</div>
						<a :href="structureLink" class="license-widget-item-btn"> 
							{{ localize.INTRANET_INVITATION_WIDGET_EDIT }} 
						</a>
					</div>
				</div>
			
				<div 
					class="license-widget-item license-widget-item--emp"
					:class="{ 'license-widget-item--emp-alert' : users.isLimit }"
				>
					<div class="license-widget-inner">
						<div 
							class="license-widget-item-progress"
							:class="[
								users.isLimit 
								? 'license-widget-item-progress--crit' 
								: 'license-widget-item-progress--full'
							]"
						></div>
						<div class="license-widget-item-content">
							<div class="license-widget-item-name">
								<span>{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES }}</span>
							</div>
							<div class="license-widget-item-num">
								{{ users.currentUserCountMessage }}
							</div>
						</div>
						<!--<div class="license-widget-item-menu"></div>-->
					</div>
					<div class="license-widget-item-detail">
						<span 
							v-if="users.maxUserCount == 0" 
							key="employeeCount"
							class="license-widget-item-link-text"
						>
							{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT }}
						</span>
						<span 
							v-else-if="users.isLimit"
							key="employeeCount" 
							class="license-widget-item-link-text"
						>
							{{ localize.INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT }}
						</span>
						<span 
							v-else-if="!users.isLimit" 
							key="employeeCount"
							class="license-widget-item-link-text"
						>
							{{ users.leftCountMessage }}
						</span>
					</div>
				</div>
			</div>
		</div>
	`,
};

import { Text } from 'main.core';
import { AvatarRoundGuest } from 'ui.avatar';

import { Action } from '../../../action';

export const CallScoring = {
	props: {
		userName: String,
		userAvatarUrl: String,
		scoringData: Object | null,
		action: Object | null,
	},

	inject: ['isReadOnly'],

	computed: {
		className(): Object
		{
			const assessment = Text.toInteger(this.scoringData?.ASSESSMENT);
			const highBorder = Text.toInteger(this.scoringData?.HIGH_BORDER);
			const lowBorder = Text.toInteger(this.scoringData?.LOW_BORDER);

			return {
				'crm-timeline__call-scoring': true,
				'--success': assessment >= highBorder,
				'--failed': assessment <= lowBorder,
			};
		},

		assessmentScriptClassName(): []
		{
			return [
				'crm-timeline__call-scoring-assessment-script',
				{
					'--readonly': this.isContentReadonly,
				},
			];
		},

		assessmentPillClassName(): []
		{
			return [
				'crm-timeline__call-scoring-assessment-pill',
				{
					'--readonly': this.isContentReadonly,
				},
			];
		},

		isContentReadonly(): boolean
		{
			return this.isReadOnly || !this.action;
		},

		renderUserAvatarElement(): string
		{
			return new AvatarRoundGuest({
				size: 26,
				userName: this.userName,
				userpicPath: this.userAvatarUrl,
				baseColor: '#7fdefc',
				borderColor: '#9dcf00',
			}).getContainer().outerHTML;
		},
	},

	methods: {
		executeAction(): void
		{
			if (this.isContentReadonly)
			{
				return;
			}

			const action = new Action(this.action);

			void action.execute(this);
		},
	},

	template: `
		<div :class='className'>
			<div class='crm-timeline__call-scoring-wrapper'>
				<div class='crm-timeline__call-scoring-responsible'>
					<div class='crm-timeline__call-scoring-title'>
						{{ this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_CALL_SCORING_RESPONSIBLE_TITLE') }}
					</div>
					<div class='crm-timeline__call-scoring-responsible-content'>
						<div class='responsible-user-avatar' v-html="renderUserAvatarElement"></div>
						<div class='responsible-user-name'>{{ this.userName }}</div>
					</div>
				</div>
				<div class='crm-timeline__line-div'></div>
				<div class='crm-timeline__call-scoring-assessment'>
					<div class='crm-timeline__call-scoring-assessment-wrapper'>
						<!--
						<img 
							class='copilot-avatar' 
							src='/bitrix/js/crm/timeline/item/src/images/crm-timelime__copilot-avatar.svg' 
							alt='copilot-avatar'
						>
						-->
						<div
							:class='assessmentPillClassName'
							@click='executeAction'
						>
							<span class="value">{{ this.scoringData?.ASSESSMENT }}</span>
							<div class="percent"></div>
						</div>
						<div class='script-layout'>
							<div class='crm-timeline__call-scoring-title'>
								{{ this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_CALL_SCORING_SCRIPT_TITLE') }}
							</div>
							<div 
								:class='assessmentScriptClassName'
								@click='executeAction'
							>
								{{ this.scoringData?.TITLE }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};

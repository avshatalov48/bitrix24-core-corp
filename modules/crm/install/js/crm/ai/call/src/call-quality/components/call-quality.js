import { AudioPlayerComponent } from 'crm.audio-player';
import { DatetimeConverter } from 'crm.timeline.tools';
import { ajax as Ajax, Loc, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Pull } from '../pull';
import { ViewMode, ViewModeType } from './common/view-mode';
import { Compliance as ComplianceComponent } from './compliance';
import { AssessmentSettingsPendingBlock } from './explanation/assessment-settings-pending-block';
import { EmptyScriptListBlock } from './explanation/empty-script-list-block';
import { ErrorBlock } from './explanation/error-block';
import { NotAssessmentScriptBlock } from './explanation/not-assessment-script-block';
import { OtherScriptBlock } from './explanation/other-script-block';
import { PendingBlock } from './explanation/pending-block';
import { RecommendationBlock } from './explanation/recommendation-block';
import { ScriptSelector as ScriptSelectorComponent } from './script-selector';

type Quality = {
	id: number;
	createdAt: number;
	assessmentSettingsId: number;
	assessmentSettingsStatus: ?string;
	assessment: number;
	assessmentAvg: number;
	prevAssessmentAvg: number;
	isPromptChanged: boolean;
	useInRating: boolean;
	prompt: string;
	actualPrompt: string;
	promptUpdatedAt: string;
	title: string;
	recommendations: string;
	summary: string;
	lowBorder: number;
	highBorder: number;
}

type PullData = {
	assessmentSettingsId: ?number;
	activityId: number;
	jobId: ?number;
	ratedUserId: ?number;
	status?: string;
}

export const CallQuality = {
	components: {
		AudioPlayerComponent,
		ScriptSelectorComponent,
		RecommendationBlock,
		OtherScriptBlock,
		NotAssessmentScriptBlock,
		AssessmentSettingsPendingBlock,
		PendingBlock,
		ErrorBlock,
		EmptyScriptListBlock,
		ComplianceComponent,
	},

	props: {
		client: {
			type: Object,
			required: true,
		},
		data: {
			type: Object,
			required: true,
		},
		audioProps: {
			type: Object,
			required: true,
		},
		context: {
			type: Object,
			required: true,
		},
	},

	data(): Object
	{
		const quality = this.getPreparedQualityProps(this.data);
		let prompt = quality.prompt;

		const currentQualityAssessmentId = quality.id ?? null;
		let viewMode: ?ViewModeType = null;
		if (this.data.viewMode === ViewMode.usedNotAssessmentScript)
		{
			viewMode = ViewMode.usedNotAssessmentScript;
		}
		else if (this.data.viewMode === ViewMode.pending)
		{
			viewMode = ViewMode.pending;
		}
		else if (this.data.viewMode === ViewMode.emptyScriptList)
		{
			viewMode = ViewMode.emptyScriptList;
		}
		else if (quality.id)
		{
			viewMode = this.data.viewMode ?? ViewMode.usedCurrentVersionOfScript;
		}
		else
		{
			viewMode = ViewMode.error;
		}

		return {
			quality,
			currentQualityAssessmentId,
			viewMode,
			prompt,
			isShowAudioPlayer: false,
			direction: this.data.callDirection,
		};
	},

	mounted()
	{
		top.BX.Event.EventEmitter.subscribe(
			'crm:copilot:callAssessment:beforeSave',
			this.onBeforeAssessmentSettingsChange,
		);

		top.BX.Event.EventEmitter.subscribe(
			'crm:copilot:callAssessment:save',
			this.onAssessmentSettingsChange,
		);

		this.pull = new Pull(
			this.onPullChangeScript,
			this.onPullChangeAssessment,
		);
		this.pull.init();
	},

	methods: {
		onBeforeAssessmentSettingsChange(event: BaseEvent): void
		{
			const { data } = event.getData();
			if (!this.isPromptChanged(data.prompt))
			{
				return;
			}

			this.quality.assessmentSettingsStatus = 'PENDING';
		},
		onAssessmentSettingsChange(event: BaseEvent): void
		{
			const { id, data } = event.getData();
			if (!this.isPromptChanged(data.prompt))
			{
				return;
			}

			this.onChangeScript(id);
		},
		isPromptChanged(newPrompt: string): boolean
		{
			return this.quality.actualPrompt !== newPrompt;
		},
		showAudioPlayer(): void
		{
			this.isShowAudioPlayer = true;
		},
		onShowActualPrompt(): void
		{
			this.viewMode = ViewMode.usedOtherVersionOfScript;
			this.prompt = this.quality.actualPrompt;
		},
		onShowCurrentAssessment(): void
		{
			this.viewMode = ViewMode.usedCurrentVersionOfScript;
			this.prompt = this.quality.prompt;
		},
		onDoAssessment(): void
		{
			this.viewMode = ViewMode.pending;
			this.$refs.scriptSelector?.disable();

			const config = {
				data: {
					...this.context,
					assessmentSettingsId: this.quality.assessmentSettingsId,
				},
			};

			Ajax
				.runAction('crm.copilot.callqualityassessment.doAssessment', config)
				.then((response) => {
					const { status, data } = response;
					this.$refs.scriptSelector?.enable();

					if (status !== 'success')
					{
						this.showError(response);

						return;
					}

					EventEmitter.emit('crm.ai.callQuality:doAssessment', { data });
				})
				.catch((response) => {
					this.showError(response);
					this.$refs.scriptSelector?.enable();
				})
			;
		},
		onChangeScript(assessmentSettingsId: number): void
		{
			const config = {
				data: {
					...this.context,
					assessmentSettingsId,
				},
			};

			Ajax
				.runAction('crm.copilot.callqualityassessment.get', config)
				.then((response) => {
					this.$refs.scriptSelector?.enable();

					const { status, data } = response;

					if (status !== 'success')
					{
						this.showError(response);

						return;
					}

					if (Type.isObject(data))
					{
						this.quality = this.getPreparedQualityProps(data);

						if (!(
							this.quality.isPromptChanged
							&& data.viewMode === ViewMode.assessmentSettingsPending
							//&& this.viewMode === ViewMode.usedCurrentVersionOfScript
						))
						{
							this.viewMode = data.viewMode;
						}
					}
				})
				.catch((response) => {
					this.$refs.scriptSelector?.enable();
					top.BX.UI.Notification.Center.notify({
						content: response.errors[0].message,
						autoHideDelay: 5000,
					});
				})
			;
		},
		showError(response)
		{
			this.viewMode = ViewMode.error;
			this.$nextTick(() => {
				this.$refs.errorBlock?.setErrorMessage(response.errors[0]?.message);
			});
		},
		getPreparedQualityProps({ callQuality: quality }): Quality
		{
			if (!Type.isPlainObject(quality))
			{
				// eslint-disable-next-line no-param-reassign
				quality = {};
			}

			return {
				id: Number(quality.ID ?? 0),
				createdAt: quality.CREATED_AT ?? null,
				assessmentSettingsId: Number(quality.ASSESSMENT_SETTING_ID ?? 0),
				assessmentSettingsStatus: quality.ASSESSMENT_SETTINGS_STATUS ?? null,
				assessment: Number(quality.ASSESSMENT ?? 0),
				assessmentAvg: Number(quality.ASSESSMENT_AVG ?? 0),
				prevAssessmentAvg: Number(quality.PREV_ASSESSMENT_AVG ?? 0),
				isPromptChanged: Boolean(quality.IS_PROMPT_CHANGED ?? false),
				useInRating: Boolean(quality.USE_IN_RATING ?? false),
				prompt: quality.PROMPT ?? '',
				actualPrompt: quality.ACTUAL_PROMPT ?? '',
				promptUpdatedAt: quality.PROMPT_UPDATED_AT ?? '',
				title: quality.TITLE ?? '',
				recommendations: quality.RECOMMENDATIONS ?? '',
				summary: quality.SUMMARY ?? '',
				lowBorder: Number(quality.LOW_BORDER ?? 30),
				highBorder: Number(quality.HIGH_BORDER ?? 70),
			};
		},
		close(): void
		{
			this.$refs.scriptSelector?.close();
			this.pull.unsubscribe();
			top.BX.Event.EventEmitter.unsubscribe(
				'crm:copilot:callAssessment:beforeSave',
				this.onBeforeAssessmentSettingsChange,
			);
			top.BX.Event.EventEmitter.unsubscribe(
				'crm:copilot:callAssessment:save',
				this.onAssessmentSettingsChange,
			);
		},
		onPullChangeScript(params: PullData): void
		{
			if (this.context.activityId !== params.activityId)
			{
				return;
			}

			if (params.status === 'error' || !Type.isNumber(params.assessmentSettingsId))
			{
				this.viewMode = ViewMode.error;
			}
			else
			{
				this.onChangeScript(params.assessmentSettingsId);
			}
		},
		onPullChangeAssessment(params: PullData): void
		{
			const assessmentSettingsId = params.assessmentSettingsId ?? null;
			const currentAssessmentSettingsId = this.quality.assessmentSettingsId;

			if (assessmentSettingsId !== currentAssessmentSettingsId)
			{
				return;
			}

			this.onChangeScript(assessmentSettingsId);
		},
	},

	watch: {
		quality: {
			handler(quality) {
				this.prompt = quality.prompt;
			},
			deep: true,
		},
	},

	computed: {
		clientNameClassList(): Object
		{
			return {
				'call-quality__call-client-name': true,
				'--incoming': Number(this.direction) === 1,
				'--outgoing': Number(this.direction) === 2,
			}
		},
		clientName(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_AI_CALL_TITLE', {
				'[clientname]': `<a href="${this.client.detailUrl}">`,
				'[/clientname]': '</a>',
				'#CLIENT_NAME#': Text.encode(this.client.fullName),
			});
		},
		formattedDate(): string
		{
			const datetimeConverter = DatetimeConverter.createFromServerTimestamp(this.client.activityCreated);

			return datetimeConverter.toDatetimeString({
				withDayOfWeek: false,
				delimiter: ', ',
			});
		},
		isUsedCurrentVersionOfScriptViewMode(): boolean
		{
			return this.viewMode === ViewMode.usedCurrentVersionOfScript;
		},
		isUsedOtherVersionOfScriptViewMode(): boolean
		{
			return this.viewMode === ViewMode.usedOtherVersionOfScript;
		},
		isUsedNotAssessmentScriptViewMode(): boolean
		{
			return this.viewMode === ViewMode.usedNotAssessmentScript;
		},
		isAssessmentSettingsPendingViewMode(): boolean
		{
			return this.viewMode === ViewMode.assessmentSettingsPending;
		},
		isPendingViewMode(): boolean
		{
			return this.viewMode === ViewMode.pending;
		},
		isErrorViewMode(): boolean
		{
			return this.viewMode === ViewMode.error;
		},
		isEmptyScriptListViewMode(): boolean
		{
			return this.viewMode === ViewMode.emptyScriptList;
		},
	},

	template: `
		<div class="call-quality__column --info">
			<div>
				<div class="call-quality__header">
					<div class="call-quality__header-row --flex">
						<div :class="clientNameClassList" v-html="clientName">
						</div>
						<div class="call-quality__call-date">
							{{ formattedDate }}
						</div>
					</div>
					<div class="call-quality__header-row">
						<div id="crm-textbox-audio-player" ref="audioPlayer">
							<AudioPlayerComponent v-if="isShowAudioPlayer" v-bind="audioProps" />
						</div>
					</div>
				</div>
				<ComplianceComponent 
					:assessment="quality.assessment"
					:title="quality.title"
					:viewMode="viewMode"
					:lowBorder="quality.lowBorder"
					:highBorder="quality.highBorder"
				/>
				<RecommendationBlock
					v-if="isUsedCurrentVersionOfScriptViewMode"
					:recommendations="quality.recommendations"
					:summary="quality.summary"
					:use-in-rating="quality.useInRating"
				/>
				<OtherScriptBlock
					v-if="isUsedOtherVersionOfScriptViewMode"
					@showAssessment="onShowCurrentAssessment"
					@doAssessment="onDoAssessment"
				/>
				<NotAssessmentScriptBlock
					v-if="isUsedNotAssessmentScriptViewMode"
					@doAssessment="onDoAssessment"
				/>
				<AssessmentSettingsPendingBlock v-if="isAssessmentSettingsPendingViewMode"/>
				<PendingBlock v-if="isPendingViewMode"/>
				<ErrorBlock v-if="isErrorViewMode" ref="errorBlock"/>
				<EmptyScriptListBlock v-if="isEmptyScriptListViewMode"/>
			</div>
		</div>
		<div class="call-quality__column --prompt">
			<ScriptSelectorComponent
				ref="scriptSelector"
				:assessmentSettingsId="quality.assessmentSettingsId"
				:assessmentSettingsStatus="quality.assessmentSettingsStatus"
				:assessmentSettingsTitle="quality.title"
				:isPromptChanged="quality.isPromptChanged"
				:promptUpdatedAt="quality.promptUpdatedAt"
				:prompt="prompt"
				:viewMode="viewMode"
				@onBeforeSelect="onChangeScript"
				@onShowActualPrompt="onShowActualPrompt"
				@doAssessment="onDoAssessment"
			/>
		</div>
	`,
};

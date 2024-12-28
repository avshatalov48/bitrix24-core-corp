const ARTICLE_CODE = '23240682';

export const RecommendationBlock = {
	props: {
		recommendations: {
			type: String,
			default: null,
		},
		summary: {
			type: String,
			default: null,
		},
		useInRating: {
			type: Boolean,
			default: false,
		},
	},

	methods: {
		showArticle(): void
		{
			window.top.BX?.Helper?.show(`redirect=detail&code=${ARTICLE_CODE}`);
		},
	},

	template: `
		<div class="call-quality__explanation --copilot-content">
			<div class="call-quality__explanation__container ">
				<div class="call-quality__explanation-title">
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EXPLANATION_TITLE') }}
				</div>
				<div class="call-quality__explanation-text">
					<div 
						v-if="!useInRating"
						class="call-quality__explanation-badge"
					>
						<div>
							{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EXPLANATION_NOT_IN_RATING') }}
							<div
								class="call-quality__explanation-badge-article ui-icon-set --help"
								@click="showArticle"
							></div>
						</div>
					</div>
					<p>
						{{ summary }}
					</p>
					<p>
						{{ recommendations }}
					</p>
				</div>
			</div>
		</div>
	`,
};

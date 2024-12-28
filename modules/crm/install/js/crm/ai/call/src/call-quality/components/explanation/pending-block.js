import { Lottie } from 'ui.lottie';

export const PendingBlock = {
	mounted()
	{
		this.renderLottieAnimation();
	},

	methods: {
		renderLottieAnimation(): HTMLElement
		{
			const mainAnimation = Lottie.loadAnimation({
				path: this.getAnimationPath(),
				container: this.$refs.lottie,
				renderer: 'svg',
				loop: true,
				autoplay: true,
			});

			mainAnimation.setSpeed(0.75);

			return this.$refs.lottie.root;
		},
		getAnimationPath(): string
		{
			return '/bitrix/js/crm/ai/call/src/call-quality/lottie/loader.json';
		},
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container ">
				<div class="call-quality__explanation-title">
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TITLE') }}
				</div>
				<div class="call-quality__explanation-text">
					<div class="call-quality__explanation-loader__container">
						<div ref="lottie" class="call-quality__explanation-loader__lottie"></div>
						<div class="call-quality__explanation-loader__lottie-text">{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TEXT') }}</div>
					</div>
				</div>
			</div>
		</div>
	`,
};

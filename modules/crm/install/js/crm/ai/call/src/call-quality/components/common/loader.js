import { Lottie } from 'ui.lottie';

export const Loader = {
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
		<div ref="lottie" class="call-quality__explanation-loader__lottie"></div>
	`,
};

const ProgressBar = {
	props: ['uploadingProgress', 'timeLeft', 'messages'],
	data()
	{
		return {
			displayProgress: 0,
			interval: false,
		}
	},
	mounted()
	{

	},
	watch: {
		uploadingProgress() {
			clearInterval(this.interval);

			const normalizedProgress = this.uploadingProgress * 100;

			if (normalizedProgress === Math.ceil(this.displayProgress))
			{
				return;
			}

			this.interval = window.setInterval(() => {
				if (this.displayProgress !== Math.ceil(normalizedProgress))
				{
					let delta = (normalizedProgress - this.displayProgress) / 10;
					delta = delta >= 0 ? Math.ceil(delta) : Math.floor(delta);
					this.displayProgress = this.displayProgress + delta;
				}
			}, 20)
		}
	},
	template: `
		<div class="b24-form-loader-progress-wrapper">
			<div class="b24-form-loader-progress-time-left"
				 v-show="this.timeLeft"
			>
				<span class="b24-form-loader-progress-time-left-value">{{ normalizedTime }}</span>
			</div>
			<div class="b24-form-loader-progress">
				<span class="b24-form-loader-progress-value">{{ normalizedProgress }}</span>
			</div>
		</div>
	`,
	computed: {
		normalizedProgress()
		{
			return Math.round(this.displayProgress);
		},
		normalizedTime()
		{
			let message = '';
			if (this.timeLeft >= 60)
			{
				message = this.messages?.get('filesLoadingTimeLeftFewMinutes');
			}
			else if (this.timeLeft > 30)
			{
				message = this.messages?.get('filesLoadingTimeLeftLessThanMinute');
			}
			else
			{
				message = this.messages?.get('filesLoadingTimeLeftFewSeconds');
			}

			return message;
		}
	},
	methods: {

	}
};

export {
	ProgressBar
}

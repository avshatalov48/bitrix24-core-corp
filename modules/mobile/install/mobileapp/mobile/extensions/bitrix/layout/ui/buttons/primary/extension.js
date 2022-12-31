(() => {
	/**
	 * @class PrimaryButton
	 */
	class PrimaryButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: '#00a2e8',
						backgroundColor: '#00a2e8',
					},
					icon: {},
					text: {
						color: '#ffffff',
					},
				};
			}

			return {
				button: {},
				icon: {},
				text: {
					fontWeight: '500',
					fontSize: 18,
					color: '#0b66c3',
				},
			};
		}
	}

	this.PrimaryButton = PrimaryButton;
})();

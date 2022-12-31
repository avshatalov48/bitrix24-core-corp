(() => {
	/**
	 * @class SuccessButton
	 */
	class SuccessButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: '#9dcf00',
						backgroundColor: '#9dcf00',
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
					color: '#9dcf00',
				},
			};
		}
	}

	this.SuccessButton = SuccessButton;
})();
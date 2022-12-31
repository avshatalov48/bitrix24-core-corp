(() => {
	/**
	 * @class CancelButton
	 */
	class CancelButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: '#828b95',
						backgroundColor: '#ffffff',
					},
					icon: {},
					text: {
						color: '#525c69',
					},
				};
			}

			return {
				button: {},
				icon: {},
				text: {
					fontWeight: '500',
					fontSize: 18,
					color: '#525c69',
				},
			};
		}
	}

	this.CancelButton = CancelButton;
})();

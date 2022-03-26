(() => {
	class SuccessButton extends BaseButton
	{
		getStyle()
		{
			return {
				button: {
					borderColor: '#9DCF00',
					backgroundColor: '#9DCF00',
				},
				icon: {},
				text: {
					color: '#FFFFFF',
				},
			};
		}
	}

	this.SuccessButton = SuccessButton;
})();
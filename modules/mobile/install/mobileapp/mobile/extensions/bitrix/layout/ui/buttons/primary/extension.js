(() => {
	class PrimaryButton extends BaseButton
	{
		getStyle()
		{
			return {
				button: {
					borderColor: '#00A2E8',
					backgroundColor: '#00A2E8',
				},
				icon: {},
				text: {
					color: '#FFFFFF',
				},
			};
		}
	}

	this.PrimaryButton = PrimaryButton;
})();

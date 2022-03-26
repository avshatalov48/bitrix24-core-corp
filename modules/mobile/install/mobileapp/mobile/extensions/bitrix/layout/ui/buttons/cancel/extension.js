(() => {
	class CancelButton extends BaseButton
	{
		getStyle()
		{
			return {
				button: {
					borderColor: '#828B95',
					backgroundColor: '#FFFFFF',
				},
				icon: {},
				text: {
					color: '#525C69',
				},
			};
		}
	}

	this.CancelButton = CancelButton;
})();

(() => {
	class ActionButton extends BaseButton
	{
		getStyle()
		{
			return {
				button: {
					borderColor: '#00A2E8',
					backgroundColor: '#FFFFFF',
				},
				icon: {},
				text: {
					color: '#525C69',
				},
			};
		}
	}

	this.ActionButton = ActionButton;
})();

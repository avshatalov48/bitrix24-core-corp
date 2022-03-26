(() => {

	/**
	 * @function MoneyView
	 * @param {Object} props
	 * @param {Money} props.money
	 * @param {Function} props.renderAmount
	 * @param {Function} props.renderCurrency
	 * @param {Function} props.renderContainer
	 * @param {Object} props.options
	 * @returns {View}
	 */
	function MoneyView({money, renderAmount, renderCurrency, renderContainer, ...options})
	{
		options = options || {};
		const template = money.template || {};
		const parts = template.PARTS || ['#'];
		const valueIndex = template.VALUE_INDEX || 0;

		const nodes = parts.map((part, index) => {
			if (index === valueIndex)
			{
				return renderAmount(money.formattedAmount);
			}
			part = options.trim && part.trim ? part.trim() : part;
			return renderCurrency(jnComponent.convertHtmlEntities(part));
		});

		if (renderContainer)
		{
			return renderContainer(nodes);
		}

		const style = options.containerStyle || {
			flexDirection: 'row',
		};

		return View(
			{style},
			...nodes
		);
	}

	jnexport(MoneyView);

})();
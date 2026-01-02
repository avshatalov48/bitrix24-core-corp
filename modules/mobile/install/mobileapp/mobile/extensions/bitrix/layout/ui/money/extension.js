/**
 * @module layout/ui/money
 */
jn.define('layout/ui/money', (require, exports, module) => {
	// just re-export it for handy usage.
	const { Money } = require('money');

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
	function MoneyView({ money, renderAmount, renderCurrency, renderContainer, ...options })
	{
		const template = money.template || {};
		const parts = template.PARTS || ['#'];
		const valueIndex = template.VALUE_INDEX || 0;

		const nodes = parts.map((part, index) => {
			if (index === valueIndex)
			{
				return renderAmount(money.formattedAmount);
			}
			const text = (options?.trim && part.trim) ? part.trim() : part;

			return renderCurrency(jnComponent.convertHtmlEntities(text));
		});

		if (renderContainer)
		{
			return renderContainer(nodes);
		}

		const style = options?.containerStyle || {
			flexDirection: 'row',
		};

		return View(
			{ style },
			...nodes,
		);
	}

	module.exports = {
		MoneyView,
		Money,
	};
});

// todo remove after all global usages in other modules will be cleaned
(function() {
	const require = (ext) => jn.require(ext);
	const { MoneyView } = require('layout/ui/money');

	jnexport(MoneyView);
})();

(() => {

	/**
	 * @function StoreProductListSummary
	 */
	function StoreProductListSummary(props)
	{
		return Container(
			ItemsCount(props.count),
			Sum(props.sum)
		);
	}

	function Container(...children)
	{
		return View(
			{
				style: {
					backgroundColor: '#F0F2F5',
				}
			},
			View(
				{
					style: {
						backgroundColor: '#ffffff',
						borderRadius: 12,
						padding: 12,
						marginBottom: 90,
						flexDirection: 'row',
						justifyContent: 'space-between',
					}
				},
				...children
			)
		);
	}

	function ItemsCount(count)
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-start',
					alignItems: 'center',
				}
			},
			Text({
				text: BX.message('CSPL_ITEMS_COUNT').replace('#NUM#', count),
				style: {
					fontSize: 18,
					color: '#525C69',
					opacity: 0.4,
				}
			})
		);
	}

	function Sum({amount, currency})
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-end',
					alignItems: 'center',
				}
			},
			View(
				{},
				Text({
					text: BX.message('CSPL_TOTAL'),
					style: {
						color: '#525C69',
						fontSize: 18,
						fontWeight: 'bold',
						marginRight: 6,
					}
				})
			),
			MoneyView({
				money: Money.create({amount, currency}),
				renderAmount: (formattedAmount) => Text({
					text: formattedAmount,
					style: {
						fontSize: 20,
						color: '#333333',
						fontWeight: 'bold',
					}
				}),
				renderCurrency: (formattedCurrency) => Text({
					text: formattedCurrency,
					style: {
						fontSize: 20,
						color: '#828B95',
						fontWeight: 'bold',
					}
				}),
			}),
		);
	}

	jnexport(StoreProductListSummary);

})();
/**
 * @module crm/timeline/item/ui/header/vertical-separator
 */
jn.define('crm/timeline/item/ui/header/vertical-separator', (require, exports, module) => {
	function VerticalSeparator()
	{
		return View(
			{
				style: {
					width: 1,
					paddingTop: 10,
					paddingBottom: 10,
				},
			},
			View(
				{
					style: {
						backgroundColor: '#dfe0e3',
						flex: 1,
					},
				},
			),
		);
	}

	module.exports = { VerticalSeparator };
});

jn.define('layout/ui/product-grid/components/separator', (require, exports, module) => {

	class Separator extends LayoutComponent
	{
		render()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: '#eef2f4',
					marginTop: 4,
					marginBottom: 6,
				}
			});
		}
	}

	module.exports = { Separator };

});
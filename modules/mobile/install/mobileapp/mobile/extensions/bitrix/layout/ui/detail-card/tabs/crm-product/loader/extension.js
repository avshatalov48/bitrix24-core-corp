/**
 * @module layout/ui/detail-card/tabs/crm-product/loader
 */
jn.define('layout/ui/detail-card/tabs/crm-product/loader', (require, exports, module) => {

	/**
	 * @function CrmProductTabLoader
	 */
	const CrmProductTabLoader = ({ productCount, onRef } = {}) => {
		if (!productCount)
		{
			return null;
		}

		productCount = Math.min(productCount, 4);

		return View(
			{
				style: {
					marginTop: 12,
				},
				ref: onRef,
			},
			...Array(productCount).fill(0).map((value, index) => renderProduct(index % 2 === 0)),
			renderSummary(),
		);
	};

	const renderProduct = (even) => View(
		{
			style: {
				backgroundColor: '#ffffff',
				borderRadius: 12,
				padding: 16,
				marginTop: 0,
				marginBottom: 12,
				flexDirection: 'row',
			},
		},
		renderImage(),
		renderContent(even),
		renderDeleteButton(),
	);

	const renderImage = () => View(
		{
			style: {
				width: 52,
				height: 52,
				marginTop: 4,
				marginLeft: 5,
				marginRight: 18,
				justifyContent: 'center',
				alignItems: 'center',
				borderRadius: 4,
				backgroundColor: '#dfe0e3',
			},
		},
	);

	const renderContent = (even) => View(
		{
			style: {
				flexGrow: 1,
				flexShrink: 1,
				width: 0,
			},
		},
		renderName(even),
		renderContextMenu(),
		renderInnerContent(even),
	);

	const renderName = (even) => View(
		{
			style: {
				paddingRight: 40,
				marginBottom: 14,
			},
		},
		renderLine(even ? 182 : 129, 6, 8, 6),
	);

	const renderContextMenu = () => View(
		{
			style: {
				position: 'absolute',
				right: -8,
				top: 0,
				width: 40,
				height: 40,
				alignItems: 'flex-end',
				justifyContent: 'center',
			},
		},
		renderCircle(19),
	);

	const renderInnerContent = (even) => View(
		{},
		renderSkuTree(even),
		renderProductPricing(),
		renderProductTotals(),
	);

	const renderSkuTree = (even) => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				marginBottom: 13,
			},
		},
		renderLine(even ? 122 : 77, 4, 9, 18),
		renderLine(53, 4, 7, 18),
	);

	const renderProductPricing = () => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				marginBottom: 10,
			},
		},
		renderInput(0, 13, '#eef2f4'),
		View(
			{
				style: {
					flex: 1,
					flexDirection: 'row',
				},
			},
			renderCircle(26, 4, 0, 5),
			renderInput(),
			renderCircle(26, 4, 5, 0),
		),
	);

	const renderProductTotals = () => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
			},
		},
		renderInput(0, 8, '#eef2f4'),
		renderInput(8, 0, '#eef2f4'),
	);

	const renderInput = (marginLeft = 0, marginRight = 0, backgroundColor = '#dfe0e3') => {
		const style = {
			flex: 1,
			height: 34,
			marginLeft,
			marginRight,
			backgroundColor,
			borderRadius: 4,
		};

		return View({ style });
	};

	const renderDeleteButton = () => View(
		{
			style: {
				position: 'absolute',
				left: 7,
				bottom: 0,
				paddingHorizontal: 10,
			},
		},
		renderCircle(15),
	);

	const renderSummary = () => View(
		{
			style: {
				backgroundColor: '#ffffff',
				borderRadius: 12,
				padding: 12,
			},
		},
		View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
				},
				renderLine(100, 6, 10, 6),
			),
			View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'flex-end',
						alignItems: 'flex-end',
					},
				},
				renderLine(133, 6, 10, 6),
				renderLine(72, 3, 10, 6),
			),
		),
	);

	const renderCircle = (size = 18, marginTop = 0, marginLeft = 0, marginRight = 10) => View(
		{
			style: {
				marginRight,
				marginVertical: 18,
				marginTop,
				marginLeft,
			},
		},
		View({
			style: {
				height: size,
				width: size,
				borderRadius: size / 2,
				backgroundColor: '#dfe0e3',
				position: 'relative',
				left: 0,
				top: 0,
			},
		}),
	);

	const renderLine = (width, height, marginTop = 0, marginBottom = 0) => {
		const style = {
			width,
			height,
			borderRadius: height / 2,
			backgroundColor: '#dfe0e3',
		};
		if (marginTop)
		{
			style.marginTop = marginTop;
		}
		if (marginBottom)
		{
			style.marginBottom = marginBottom;
		}

		return View({
			style,
		});
	};

	module.exports = { CrmProductTabLoader };
});

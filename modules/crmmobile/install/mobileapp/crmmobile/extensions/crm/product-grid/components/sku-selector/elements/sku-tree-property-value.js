/**
 * @module crm/product-grid/components/sku-selector/elements/sku-tree-property-value
 */
jn.define('crm/product-grid/components/sku-selector/elements/sku-tree-property-value', (require, exports, module) => {
	const skip = () => {};

	function SkuTreePropertyValue(props)
	{
		return View(
			{
				style: {
					paddingTop: 3,
					paddingRight: 4,
					marginRight: 6,
					marginBottom: 7,
				},
				onClick: () => (props.onClick ? props.onClick() : skip()),
			},
			View(
				{
					style: {
						borderColor: props.selected ? '#2FC6F6' : '#d5d7db',
						borderWidth: 1,
						borderRadius: 4,
						padding: 10,
						flexDirection: 'row',
					},
				},
				props.picture && Picture(props.picture),
				Text({
					text: props.name,
					style: {
						color: '#828B95',
						fontSize: 15,
					},
				}),
			),
			props.selected && SelectedIcon(),
		);
	}

	function Picture(src)
	{
		src = src.startsWith('/') ? currentDomain + src : src;

		return View(
			{},
			Image({
				style: {
					width: 20,
					height: 20,
					borderRadius: 2,
					marginRight: 6,
					borderWidth: 1,
					borderColor: '#e6e7e9',
				},
				resizeMode: 'cover',
				uri: encodeURI(src),
			}),
		);
	}

	function SelectedIcon()
	{
		return View(
			{
				style: {
					position: 'absolute',
					top: -2,
					right: -3,
				},
			},
			Image({
				style: {
					width: 16,
					height: 17,
				},
				svg: {
					content: '<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_7715_74318)"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.7582 7.54558C12.7582 10.1485 10.648 12.2586 8.04509 12.2586C5.44214 12.2586 3.33203 10.1485 3.33203 7.54558C3.33203 4.94263 5.44214 2.83252 8.04509 2.83252C10.648 2.83252 12.7582 4.94263 12.7582 7.54558ZM7.30152 8.06036L6.21176 6.93168L5.35552 8.02144L7.30152 9.96744L10.9811 6.28789L10.0259 5.29704L7.30152 8.06036Z" fill="#2FC6F6"/><path d="M6.94182 8.40766L7.29778 8.77633L7.65758 8.4114L10.0219 6.0133L10.2804 6.28147L7.30152 9.26034L6.02274 7.98155L6.25051 7.69166L6.94182 8.40766ZM8.04509 12.7586C10.9242 12.7586 13.2582 10.4247 13.2582 7.54558C13.2582 4.66649 10.9242 2.33252 8.04509 2.33252C5.166 2.33252 2.83203 4.66649 2.83203 7.54558C2.83203 10.4247 5.166 12.7586 8.04509 12.7586Z" stroke="white"/></g><defs><filter id="filter0_d_7715_74318" x="0.332031" y="0.83252" width="15.4258" height="15.4263" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_7715_74318"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_7715_74318" result="shape"/></filter></defs></svg>',
				},
			}),
		);
	}

	module.exports = { SkuTreePropertyValue };
});

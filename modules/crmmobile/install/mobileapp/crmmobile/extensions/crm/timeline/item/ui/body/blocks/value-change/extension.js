/**
 * @module crm/timeline/item/ui/body/blocks/value-change
 */
jn.define('crm/timeline/item/ui/body/blocks/value-change', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { transparent } = require('utils/color');
	const { SnackbarText } = require('layout/ui/snackbar-text');

	/**
	 * @class TimelineItemBodyValueChangeBlock
	 */
	class TimelineItemBodyValueChangeBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				...this.renderValue(this.props.from),
				this.renderArrow(),
				...this.renderValue(this.props.to),
			);
		}

		renderValue(item)
		{
			const elements = [];

			if (item.properties.iconCode || item.properties.text)
			{
				elements.push(View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							minWidth: 76,
							justifyContent: 'center',
							flexShrink: 1,
						},
					},
					item.properties.iconCode && Image({
						style: {
							width: 12,
							height: 12,
							marginRight: 4,
						},
						svg: {
							content: Icons[item.properties.iconCode],
						},
					}),
					item.properties.text && View(
						{
							style: {
								marginRight: 6,
								flexShrink: 1,
							},
						},
						SnackbarText({
							text: item.properties.text,
							numberOfLines: 1,
							ellipsize: 'end',
							style: {
								fontSize: 13,
								color: '#525C69',
							},
						}),
					),
				));
			}

			if (item.properties.pillText)
			{
				elements.push(View(
					{
						style: {
							minWidth: 60,
							borderRadius: 40,
							backgroundColor: transparent('#000000', 0.05),
							paddingTop: 4,
							paddingBottom: 4,
							paddingLeft: 8,
							paddingRight: 8,
							flexShrink: 1,
						},
					},
					SnackbarText({
						text: item.properties.pillText,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							fontSize: 12,
							fontWeight: '400',
							color: '#525C69',
							textAlign: 'center',
						},
					}),
				));
			}

			return elements;
		}

		renderArrow()
		{
			return View(
				{
					style: {
						marginLeft: 8,
						marginRight: 8,
					},
				},
				Image({
					svg: {
						content: '<svg width="9" height="8" viewBox="0 0 9 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.5V0.5L9 4L5.5 7.5V4.5H0V3.5H5.5Z" fill="#a8adb4"/></svg>',
					},
					style: {
						width: 9,
						height: 8,
					},
				}),
			);
		}
	}

	const Icons = {
		pipeline: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.99149 2.99127H10.0087C10.2156 2.99127 10.3833 3.15148 10.3833 3.34911C10.3833 3.38917 10.3763 3.42894 10.3625 3.46677L10.1203 4.13133C10.0678 4.27514 9.92588 4.3715 9.76646 4.3715H2.23177C2.072 4.3715 1.92982 4.27473 1.87766 4.13048L1.63738 3.46592C1.56984 3.27912 1.67363 3.07539 1.8692 3.01087C1.90854 2.9979 1.94987 2.99127 1.99149 2.99127ZM3.31197 5.54264H8.68822C8.89512 5.54264 9.06285 5.70285 9.06285 5.90048C9.06285 5.94338 9.05477 5.98593 9.039 6.02611L8.77813 6.69067C8.7233 6.83034 8.58352 6.92287 8.42735 6.92287H3.55897C3.40038 6.92287 3.25898 6.8275 3.20593 6.68475L2.95893 6.02019C2.88971 5.83395 2.99166 5.62937 3.18664 5.56326C3.22688 5.54961 3.26927 5.54264 3.31197 5.54264ZM4.9811 8.10201H7.01909C7.22599 8.10201 7.39372 8.26221 7.39372 8.45984C7.39372 8.49466 7.3884 8.52929 7.37793 8.56263L7.16928 9.22719C7.12176 9.37857 6.97589 9.48224 6.81044 9.48224H5.21236C5.05099 9.48224 4.90776 9.38354 4.85686 9.23728L4.62559 8.57272C4.56033 8.38518 4.66659 8.18261 4.86293 8.12027C4.90104 8.10817 4.94094 8.10201 4.9811 8.10201Z" fill="#828B95"/></svg>',
	};

	module.exports = { TimelineItemBodyValueChangeBlock };
});

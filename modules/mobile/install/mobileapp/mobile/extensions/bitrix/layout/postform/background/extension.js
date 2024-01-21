(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	this.BackgroundSelectorComponent = class BackgroundSelectorComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				selected: false,
			};

			this.onSelectBackground = props.onSelectBackground;
			this.backgroundImagesData = props.backgroundImagesData ? props.backgroundImagesData : {};
			this.heightRatio = props.heightRatio;
		}

		render()
		{
			const { selected } = this.state;

			const onSelectBackground = this.onSelectBackground;
			let backgroundImageUrlsList = [];

			for (const [key, item] of Object.entries(this.backgroundImagesData.images))
			{
				backgroundImageUrlsList.push({
					code: key,
					imageData: item,
				});
			}

			backgroundImageUrlsList = backgroundImageUrlsList.sort((a, b) => {
				if (a.code < b.code)
				{
					return -1;
				}

				if (a.code > b.code)
				{
					return 1;
				}

				return 0;
			});

			const backgroundImagesList = [];
			let counter = 1;

			backgroundImageUrlsList.forEach((item) => {
				backgroundImagesList.push(
					View(
						{
							style: {
								width: '50%',
							},
						},
						View(
							{
								testId: `backgroundSelectorItem_${item.code}`,
								style: {
									marginTop: parseInt(10 * this.heightRatio, 10),
									marginLeft: (counter % 2 ? 10 : 5),
									marginRight: (counter % 2 ? 5 : 10),
									height: parseInt(59 * this.heightRatio, 10),
									borderRadius: parseInt(10 * this.heightRatio, 10),
									backgroundImage: currentDomain + item.imageData.resizedUrl,
									backgroundResizeMode: 'cover',
								},
								onClick: () => {
									onSelectBackground(item.code);
								},
							},
						),
					),
				);
				counter++;
			});

			let imagesViewArgs = [
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
			];

			imagesViewArgs = [...imagesViewArgs, ...backgroundImagesList];

			return ScrollView(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{},
					View.apply(null, imagesViewArgs),
					View(
						{
							style: {
								width: '100%',
								alignItems: 'center',
								marginTop: parseInt(20 * this.heightRatio, 10),
							},
						},
						Button({
							style: {
								align: 'center',
								justifyContent: 'center',
								width: '80%',
								height: parseInt(46 * this.heightRatio, 10),
								fontSize: parseInt(17 * this.heightRatio, 10),
								color: AppTheme.colors.base0,
								borderWidth: 1,
								borderColor: AppTheme.colors.base2,
								borderRadius: parseInt(6 * this.heightRatio, 10),
							},
							text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_BACKGROUND_EMPTY'),
							onClick: () => {
								onSelectBackground(null);
							},
						}),
					),
				),
			);
		}
	};
})();

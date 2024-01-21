(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	AppTheme.extend('medalSelector', {
		BgOpacity: [1, 0.05],
		MedalOpacity: [1, 0.7],
	});

	this.MedalSelectorComponent = class MedalSelectorComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				selected: false,
			};

			this.onSelectMedal = props.onSelectMedal;
			this.medalsList = props.medalsList || {};
		}

		render()
		{
			const { medal } = this.state;

			const onSelectMedal = this.onSelectMedal;
			const medalsList = this.medalsList;

			let sorterMedalsList = [];
			const medalImagesList = [];

			for (const [key, item] of Object.entries(medalsList))
			{
				sorterMedalsList.push({
					key,
					sort: item.sort,
					name: item.name,
					medalSelectorUrl: item.medalSelectorUrl,
					medalUrl: item.medalUrl,
				});
			}

			sorterMedalsList = sorterMedalsList.sort((a, b) => {
				if (a.sort < b.sort)
				{
					return -1;
				}

				if (a.sort > b.sort)
				{
					return 1;
				}

				return 0;
			});

			let counter = 1;

			sorterMedalsList.forEach((item) => {
				medalImagesList.push(View(
					{
						style: {
							width: '50%',
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					View(
						{
							testId: `medalSelectorItem_${item.key}`,
							style: {
								marginTop: 10,
								marginLeft: (counter % 2 ? 10 : 5),
								marginRight: (counter % 2 ? 5 : 10),
								height: 59,
								borderRadius: 4,
								backgroundColor: AppTheme.colors.accentSoftBlue2,
							},
							onClick: () => {
								onSelectMedal(item.key);
							},
						},
						View({
							style: {
								position: 'absolute',
								backgroundResizeMode: 'cover',
								opacity: AppTheme.colors.medalSelectorMedalOpacity,
								backgroundImageSvgUrl: currentDomain + item.medalUrl,
								height: 60,
								width: 60,
								top: 10,
								left: 8,
							},
						}),
						View({
							style: {
								position: 'absolute',
								opacity: AppTheme.colors.medalSelectorBgOpacity,
								backgroundPosition: 'top-left',
								backgroundResizeMode: 'cover',
								backgroundImageSvgUrl: currentDomain + item.medalSelectorUrl,
								height: '100%',
								width: '100%',
							},
						}),
						Text({
							style: {
								position: 'absolute',
								top: 20,
								left: 82,
								fontSize: 15,
								fontWeight: 'bold',
								color: AppTheme.colors.base1,
							},
							text: item.name,
						}),
					),
				));
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

			imagesViewArgs = [...imagesViewArgs, ...medalImagesList];

			return ScrollView(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{},
					View.apply(null, imagesViewArgs),
				),
			);
		}
	};
})();

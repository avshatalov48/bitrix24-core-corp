(() =>
{
	this.BackgroundSelectorComponent = class BackgroundSelectorComponent extends LayoutComponent {

		constructor(props) {
			super(props);

			this.state = {
				selected: false
			};

			this.onSelectBackground = props.onSelectBackground;
			this.backgroundImagesData = props.backgroundImagesData ? props.backgroundImagesData : {};
			this.heightRatio = props.heightRatio;
		}

		render() {
			const { selected } = this.state;

			const onSelectBackground = this.onSelectBackground;
			let backgroundImageUrlsList = [];

			for (let [key, item] of Object.entries(this.backgroundImagesData['images']))
			{
				backgroundImageUrlsList.push({
					code: key,
					imageData: item
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

			let backgroundImagesList = [];
			let counter = 1;

			backgroundImageUrlsList.forEach(item => {
				backgroundImagesList.push(
					View(
						{
							style: {
								width: '50%'
							}
						},
						View(
							{
								testId: `backgroundSelectorItem_${item.code}`,
								style: {
									marginTop: parseInt(10 * this.heightRatio),
									marginLeft: (counter % 2 ? 10 : 5),
									marginRight: (counter % 2 ? 5 : 10),
									height: parseInt(59 * this.heightRatio),
									borderRadius: parseInt(10 * this.heightRatio),
									backgroundImage: currentDomain + item.imageData.resizedUrl,
									backgroundResizeMode: 'cover'
								},
								onClick: () => {
									onSelectBackground(item.code)
								}
							}
						)
					)
				);
				counter++;
			});

			let imagesViewArgs = [
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap'
					}
				}
			];

			imagesViewArgs = imagesViewArgs.concat(backgroundImagesList);

			return ScrollView(
				{
					style: {
						backgroundColor: '#ffffff'
					}
				},
				View({
					},
					View.apply(null, imagesViewArgs),
					View(
						{
							style: {
								width: '100%',
								alignItems: 'center',
								marginTop: parseInt(20 * this.heightRatio)
							}
						},
						Button({
							style: {
								align: 'center',
								justifyContent: 'center',
								width: '80%',
								height: parseInt(46 * this.heightRatio),
								fontSize: parseInt(17 * this.heightRatio),
								color: '#000000',
								borderWidth: 1,
								borderColor: '#525C69',
								borderRadius: parseInt(6 * this.heightRatio)
							},
							text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_BACKGROUND_EMPTY'),
							onClick: () => {
								onSelectBackground(null)
							}
						})
					)
				)
			);
		}
	}
})();
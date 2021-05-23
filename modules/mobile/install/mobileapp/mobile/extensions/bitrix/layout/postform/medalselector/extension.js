(() =>
{
	this.MedalSelectorComponent = class MedalSelectorComponent extends LayoutComponent {

		constructor(props) {
			super(props);

			this.state = {
				selected: false
			};

			this.onSelectMedal = props.onSelectMedal;
			this.medalsList = props.medalsList ? props.medalsList : {}
		};

		render() {
			const { medal } = this.state;

			const onSelectMedal = this.onSelectMedal;
			const medalsList = this.medalsList;

			let sorterMedalsList = [];
			let medalImagesList = [];

			for (let [key, item] of Object.entries(medalsList))
			{
				sorterMedalsList.push({
					key: key,
					sort: item.sort,
					name: item.name,
					medalSelectorUrl: item.medalSelectorUrl
				})
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

			sorterMedalsList.forEach(item => {
				medalImagesList.push(View(
					{
						style: {
							width: '50%'
						}
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
								backgroundPosition: 'top-left',
								backgroundResizeMode: 'cover',
								backgroundImageSvgUrl: currentDomain + item.medalSelectorUrl,
							},
							onClick: () => {
								onSelectMedal(item.key)
							}
						},
						Text({
								style: {
									position: 'absolute',
									top: 20,
									left: 82,
									fontSize: 15,
									fontWeight: 'bold',
									color: '#333333',
								},
								text: item.name
							}
						)
					)
				));
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

			imagesViewArgs = imagesViewArgs.concat(medalImagesList);

			return ScrollView(
				{
					style: {
						backgroundColor: '#ffffff'
					}
				},
				View({
					},
					View.apply(null, imagesViewArgs)
				)
			);
		};
	};
})();
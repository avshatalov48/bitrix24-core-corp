(function(){

	this.Separator = ({ clickCallback }) => (
		View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center'
				}
			},
			View({
				style: {
					flex: 1,
					height: 1,
					backgroundColor: '#33525C69'
				}
			}),
			View(
				{
					style: {
						width: 34,
						height: 34,
						borderRadius: 17,
						borderWidth: 1,
						borderColor: '#4C525C69',
						alignItems: 'center',
						justifyContent: 'center',
						marginLeft: 8,
						marginRight: 16
					},
					onClick: clickCallback
				},
				Image({
					named: 'icon_threedots',
					style: {
						width: 28,
						height: 28
					}
				})
			)
		)
	)

})();
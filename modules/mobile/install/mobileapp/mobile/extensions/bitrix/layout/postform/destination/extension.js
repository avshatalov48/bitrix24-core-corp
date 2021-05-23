(() => {

	this.renderDestinationList = ({
		recipients,
		useContainer,
		limit,
	}) => {

		let result = [];

		for (let [type, items] of Object.entries(recipients)) {
			items.forEach(element => {
				if (
					type === 'users'
					&& element.id === 'A'
				)
				{
					result.unshift(`#ALL_BEGIN#${element.title}#ALL_END#`);
				}
				else
				{
					result.push(element.title);
				}
			})
		}

		let value = '';
		const listCount = result.length;

		if (result.length <= 0)
		{
			value = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_DESTINATION_VALUE_PLACEHOLDER');
		}
		else
		{
			limit = (limit ? limit : 3);

			value = (
				listCount > limit
					? BX.message('MOBILE_EXT_LAYOUT_POSTFORM_DESTINATION_VALUE_MORE').replace('#LIST#', result.splice(0, limit).join(', ')).replace('#MORE#', (listCount - limit))
					: result.join(', ')
			);
		}

		return (
			useContainer
			&& listCount <= 1
				? BX.message('MOBILE_EXT_LAYOUT_POSTFORM_DESTINATION_CONTAINER2').replace('#DESTINATIONS#', value)
				: value
		);
	}

})();
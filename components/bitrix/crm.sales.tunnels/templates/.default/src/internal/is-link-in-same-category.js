import {Event} from 'main.core';

export default function isLinkInSameCategory(event: Event.BaseEvent)
{
	const columnFrom = event.data.from.data.column;
	const columnTo = event.data.to.data.column;
	const dataFrom = columnFrom.getData();
	const dataTo = columnTo.getData();

	return String(dataFrom.category.id) === String(dataTo.category.id);
}
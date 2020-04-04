import {Event} from 'main.core';
import Marker from '../marker/marker';

export default function isCycleLink(event: Event.BaseEvent)
{
	const columnFrom = event.data.from.data.column;
	const columnTo = event.data.to.data.column;

	return [...Marker.getAllLinks()].some((item) => {
		return item.from === columnTo.marker && item.to === columnFrom.marker;
	});
}
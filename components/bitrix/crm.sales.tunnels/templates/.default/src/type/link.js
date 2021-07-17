import * as d3 from 'main.d3js';
import type Marker from '../marker/marker';

type Link = {
	from: Marker,
	to: Marker,
	robotAction: string,
	path: Array<[number, number]>,
	arrow: d3.Selection,
	node: d3.Selection,
};

export default Link;
const invalidRects = [
	{left: NaN, top: 10, width: 10, height: 10},
	{left: -10, top: -NaN, width: 10, height: 10},
	{left: -10, top: 10, width: NaN, height: 10},
	{left: -10, top: 10, width: 10, height: NaN},
	{left: -10, top: 10, width: 10, height: NaN},
	{left: -10, top: 10},
	{width: 10, height: 10},
	{top: 10, left: 10},
];

export default invalidRects;
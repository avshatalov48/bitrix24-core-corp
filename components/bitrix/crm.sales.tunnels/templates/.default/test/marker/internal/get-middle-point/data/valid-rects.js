const validRects = [
	{
		rect: {left: 10, top: 10, width: 10, height: 10},
		result: {middleX: 15, middleY: 15},
	},
	{
		rect: {left: -10, top: -10, width: 10, height: 10},
		result: {middleX: -5, middleY: -5},
	},
];

export default validRects;
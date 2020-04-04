export default function createStageStubs(count: 0)
{
	return Array.from({length: count}).map((item, index) => {
		return {
			STATUS_ID: `stub_${index}`,
			COLOR: 'F1F5F7',
			NAME: 'category stub',
		};
	});
}
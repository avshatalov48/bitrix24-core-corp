/**
 * @module testing/tests/redux/optimistic-ui/playground-slice-users/src/data
 */
jn.define('testing/tests/redux/optimistic-ui/playground-slice-users/src/data', (require, exports, module) => {
	const data = [
		{
			id: 1,
			name: 'John',
			lastName: 'Doe',
			secondName: 'A',
			personalMobile: '123-456-7890',
			personalPhone: '098-765-4321',
		},
		{
			id: 2,
			name: 'Jane',
			lastName: 'Smith',
			secondName: 'B',
			personalMobile: '234-567-8901',
			personalPhone: '987-654-3210',
		},
		{
			id: 3,
			name: 'Alice',
			lastName: 'Johnson',
			secondName: 'C',
			personalMobile: '345-678-9012',
			personalPhone: '876-543-2109',
		},
		{
			id: 4,
			name: 'Bob',
			lastName: 'Brown',
			secondName: 'D',
			personalMobile: '456-789-0123',
			personalPhone: '765-432-1098',
		},
		{
			id: 5,
			name: 'Charlie',
			lastName: 'Davis',
			secondName: 'E',
			personalMobile: '567-890-1234',
			personalPhone: '654-321-0987',
		},
	];

	module.exports = {
		data,
	};
});

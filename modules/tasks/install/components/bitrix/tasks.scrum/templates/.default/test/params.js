export default function getInputParams(entityName: string, seed: number): Object
{
	switch (entityName)
	{
		case 'CreateItem':
			return {
				name: '::name::',
			};
	}
}
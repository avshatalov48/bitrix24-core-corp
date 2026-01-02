type InitialParamType = 'right-side-expand';

export const InitialParamDict: Record<string, InitialParamType> = {
	RightSideExpand: 'right-side-expand',
};

const prefix = 'intranet-user-mini-profile';

export class InitialParamService
{
	// eslint-disable-next-line flowtype/require-return-type
	static getValue(type: InitialParamType): string
	{
		const key = this.#getKeyByType(type);

		return localStorage.getItem(key);
	}

	static save(type: InitialParamType, value: string): void
	{
		const key = this.#getKeyByType(type);
		if (!Object.values(InitialParamDict).includes(type))
		{
			return;
		}

		localStorage.setItem(key, value);
	}

	static #getKeyByType(type: InitialParamType): string
	{
		return prefix + type;
	}
}

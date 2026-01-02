import { Runtime, Reflection } from 'main.core';

export const showHelper = async (): Promise<void> => {
	await Runtime.loadExtension('helper');
	const Helper = Reflection.getClass('BX.Helper');

	Helper.show('redirect=detail&code=20267044');
};

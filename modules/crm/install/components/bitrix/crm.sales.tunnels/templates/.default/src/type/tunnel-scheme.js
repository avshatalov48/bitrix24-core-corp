export type Tunnel = {
	srcCategory: string,
	srcStage: string,
	dstCategory: string,
	dstStage: string,
	robotAction: string,
	robot: any,
}

export type TunnelSchemeStage = {
	categoryId: number,
	stageId: string,
	locked: boolean,
	tunnels: Array<Tunnel>,
};

type TunnelScheme = {
	available: boolean,
	stages: Array<TunnelSchemeStage>,
};

export default TunnelScheme;
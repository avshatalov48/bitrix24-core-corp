type RouteOptions = {
	pattern: string;
	params?: Record<string, string>;
}

type RouteProps = {
	handler?: (params: Record<string, string>) => void;
	routes?: RouteOptions[];
} & RouteOptions;

export { RouteProps };

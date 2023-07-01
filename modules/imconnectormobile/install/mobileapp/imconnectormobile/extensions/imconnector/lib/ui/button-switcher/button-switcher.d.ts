type ButtonSwitcherProps<TStates, TCurrentState extends keyof TStates> = {
	states: TStates,
	startingState: TCurrentState
}
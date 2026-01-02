import { SidePanel, Manager } from 'main.sidepanel';

const sp = SidePanel ?? BX.SidePanel;
const ManagerInst = Manager ?? BX.SidePanel.Manager;

export const SidePanelInstance = window === top ? sp.Instance : new ManagerInst();

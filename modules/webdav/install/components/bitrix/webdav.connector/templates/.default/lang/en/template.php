<?
$MESS["WD_WEBFOLDER_TITLE"] = "Connect as a Web Folder";
$MESS["WD_USEADDRESS"] = "Use the following address for connection:";
$MESS["WD_CONNECT"] = "Connect";
$MESS["WD_SHAREDDRIVE_TITLE"] = "Show instructions for connecting as a network drive";
$MESS["WD_REGISTERPATCH"] = "The current security preferences require that you <a href=\"#LINK#\">make some Registry changes</a> in order to connect a network drive.";
$MESS["WD_NOTINSTALLED"] = "This component is not installed in your operating system by default. You can <a href=\"#LINK#\">download it here</a>.";
$MESS["WD_WIN7HTTPSCMD"] = "To connect the library as a network drive via HTTPS/SSL protocol, execute the command: <b>Start > Execute > cmd</b>.";
$MESS["WD_CONNECTION_MANUAL"] = "<a href=\"#LINK#\">Connection instructions</a>.";
$MESS["WD_TIP_FOR_2008"] = "Please read the <a href=\"#LINK#\">notice</a> if you are using Microsoft Windows Server 2008.";
$MESS["WD_USECOMMANDLINE"] = "To connect the library as a network drive using HTTPS/SSL, use <b>Start > Run > cmd</b>. Type the following commands in the command line:";
$MESS["WD_EMPTY_PATH"] = "The network path is not specified.";
$MESS["WD_CONNECTION_TITLE"] = "Map document library as network drive";
$MESS["WD_MACOS_TITLE"] = "Map document library in Mac OS X";
$MESS["WD_CONNECTOR_HELP_MAPDRIVE"] = "<h3>Mapping the Library as a Network Drive</h3>
<p>To connect a library as a network disk using <b>file manager</b>:
<ul>
<li>Run Windows Explorer</b>;</li>
<li>Select <i>Tools > Map Network Drive</i>. The network disc wizard will open:
<br /><br /><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/network_storage.png',629,459,'Map Network Drive');\">
<img width=\"250\" height=\"183\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/en/network_storage_sm.png\" style=\"cursor: pointer;\" alt=\"Click to Enlarge\" /></a></li>
<li>In the <b>Drive</b> field, specify a letter to map the folder to;</li>
<li>In the <b>Folder</b> field, enter the path to the library: <i>http://your_server/docs/shared/</i>. If you want this folder to be available when the system starts, check the <b>Reconnect at logon</b> option;</li>
<li>Click <b>Ready</b>. If prompted for a User name and Password, enter your login and password, and then click <b>OK</b>.</li>
</ul>
</p>
<p>Later, you can open the folder in Windows Explorer where the folder will be shown as a drive under My Computer, or in any file manager.</p>";
$MESS["WD_CONNECTOR_HELP_OSX"] = "<h3>Connecting The Library in Mac OS and Mac OS X</h3>

<ul>
<li>Select <i>Finder Go->Connect to Server command</i>;</li>
<li>Type in the library address in <b>Server Address</b>:</p>
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/macos.png',465,550,'Mac OS X');\">
<img width=\"235\" height=\"278\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/en/macos_sm.png\" style=\"cursor: pointer;\" alt=\"Click to Enlarge\" /></a></li>
</ul>";
$MESS["WD_CONNECTOR_HELP_WEBFOLDERS"] = "<h3>Connecting Using Web Folders</h3>
<p>Ensure that you have made proper <a href=\"#URL_HELP##oswindowsreg\">modification to the system registry</a> and the <a href=\"#URL_HELP##oswindowswebclient\">Webclient service is running</a>.</p>
<p>A special web folder connection component is required to connect to the document library. Follow the instructions at the <a href=\"http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64\" target=\"_blank\">Microsoft website</a> ). </p>
<ul>
<li>Run Windows Explorer;</li>
<li>Select <b>Map Network Drive</b>;</li>
<li>Click the link <b>Connect to a Web site that you can use to store your documents and pictures</b>:</p>
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/network_add_1.png',630,461,'Map Network Drive');\">
<img width=\"250\" height=\"183\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/en/network_add_1_sm.png\" style=\"cursor: pointer;\" alt=\"Click to Enlarge\" /></a> <br />This will run the <b>Add Network Location</b>.</li>
<li>In the wizard window, click <b>Next</b>. The next wizard window will appear;</li>
<li>In this window, click <b>Choose a custom network location</b> and then click <b>Next</b>:
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/network_add_4.png',610,499,'Add Network Location');\">
<img width=\"250\" height=\"205\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/en/network_add_4_sm.png\" style=\"cursor: pointer;\" alt=\"Click to Enlarge\" /></a></li>
<li>Here, in the <b>Internet or network address</b> field, type the URL of the mapping folder in the format: <i>http://your_server/docs/shared/</i>;</li>
<li>Click <b>Next</b>. If prompted for a <b>User name</b> and <b>Password</b>, enter your login and password, and then click <b>OK</b>.</li>
</ul>

<p>From now on, you can access the folder by clicking <b>Run > Network Neighborhood > Folder Name</b>.</p>";
?>
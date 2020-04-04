<?
$MESS['WD_HELP_BPHELP_TEXT'] = "<p><b>Note</b>: detailed information about business processes can be found on the <a href=\"#LINK#\" target=\"_blank\">Business Processes</a> page.</p>";
$MESS['WD_HELP_FULL_TEXT'] = "The document library offers two approaches to handle documents: by using a web browser (Internet Explorer, Opera, Fire Fox etc.), or via the WebDAV client (web folders and remote drives in Windows). <br><br>
<ul>
	<li><b><a href=\"#iewebfolder\">Using Web Browsers to Manage Documents</a></b></li>
	<li><b><a href=\"#ostable\">WebDAV Application Comparison Table</a></b></li>
	<li><b><a href=\"#oswindows\">Connecting the Document Library in Windows</a></b></li>
	<ul>
		<li><a href=\"#oswindowsnoties\">Limitations in Windows</a></li>
		<li><a href=\"#oswindowsreg\">Enabling non-HTTPS Authorization</a></li>
		<li><a href=\"#oswindowswebclient\">Running Web Client Service</a></li>
		<li><a href=\"#oswindowsfolders\">Connecting and Using Web Folders</a></li>
		<li><a href=\"#oswindowsmapdrive\">Mapping the Library as a Network Drive</a></li>

	</ul>
	<li><b><a href=\"#osmacos\">Connecting a Library in Mac OS and Mac OS X</a></b></li>
	<li><b><a href=\"#maxfilesize\">Increasing the Maximum Size of Uploaded Files</a></b></li>
</ul>


<h2><a name=\"browser\"></a>Using Web Browsers to Manage Documents</h2>
<h4><a name=\"upload\"></a>Uploading Documents</h4>
<p>Before you start uploading, open the folder to which the documents need to be uploaded. Then, click <b>Upload</b>, on the context toolbar:</p>
<p><img src=\"#TEMPLATEFOLDER#/images/en/upload_contex_panel.png\" width=\"679\" height=\"65\"  border=\"0\"/></p>
<p>A file upload form will show up. This form has the following three view modes:</p>
<ul>
<li><b>standard</b>: uploads specified documents from one or multiple folders (by clicking <b>Add Files</b>) or all documents from one or multiple folders (by clicking <b>Add Folder</b>);</li>
<li><b>classic</b>: uploads specified documents from only one folder;</li>
<li><b>simple</b>: uploads only one specified document.</li>
</ul>

<p>Select the view mode you feel comfortable with and select files or folders to upload.</p>
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/load_form.png',737,638,'Document uploading form');\">
<img src=\"#TEMPLATEFOLDER#/images/en/load_form_sm.png\" style=\"CURSOR: pointer\" width=\"300\" height=\"260\" alt=\"Click to Enlarge\"  border=\"0\"/></a></p>

<p>Click <b>Upload</b>.</p>

<h4><a name=\"bizproc\"></a>Running a Business Process</h4>

<p>In certain cases, a document requires one or more operations to be performed on it. For example, a document may need to be approved or negotiated. This is where business processes come into play.</p>

<p>To create a business process, click on the <b>Action</b> <img src=\"#TEMPLATEFOLDER#/images/en/action_button.png\" width=\"30\" height=\"26\" border=\"0\"/> button in the row with the relevant document and choose <b>New Business Process</b>:</p>
<p><img src=\"#TEMPLATEFOLDER#/images/en/new_bizproc.png\" width=\"442\" height=\"263\" border=\"0\"/></p>
<p>The <b>Run Business Process</b> page opens, where you can fill in the parameters of the business process which you have selected.  </p>
#BPHELP#
<p>To manage business process templates, click on the <b>Business Process</b> button, in the context panel:</p>
<p><img src=\"#TEMPLATEFOLDER#/images/en/bizproc_contex_panel.png\" width=\"734\" height=\"67\" border=\"0\"/></p>

<h4><a name=\"delete\"></a>Editing and Deleting Documents</h4>
<p>The document modification commands are available in the context menu:
<p><img src=\"#TEMPLATEFOLDER#/images/en/delete_file.png\" width=\"388\" height=\"227\" border=\"0\"/></p> Alternatively, you can use the group action panel to apply a required action to multiple documents.
<br/><br/>
<h4><a name=\"office\"></a>Editing Documents Using Microsoft Office 2003 and Later Versions</h4>
<p><b>Attention!</b> This function is available only when editing documents in <b>Internet Explorer</b>.</p>

<p>Click on the pencil icon, edit the document, save it and close the application. All the changes will be saved on the server side.</p>
<i><div class=\"hint\"><b>Note</b>: the document has an icon indicating the lock status. The yellow icon <img src=\"#TEMPLATEFOLDER#/images/yellow_status.png\" width=\"14\" height=\"14\" border=\"0\"/> shows that the document is being edited by you; the red icon <img src=\"#TEMPLATEFOLDER#/images/red_status.png\" width=\"14\" height=\"14\" border=\"0\"/> means the file is locked by someone else. Use the action button menu to unlock the document.
</div></i>

<br>
<h2><a name=\"ostable\"></a>WebDAV Application Comparison Table</h2>

<p><b>Note! </b> <i>Certain limitations exist when using WebDAV clients to manage the library in workflow or business process mode: <br>
	<ul> <li>a business process cannot be run on a document; </li>
	<li>documents cannot be uploaded or edited if there are autorun business processes with mandatory parameters without default values;</li>
	<li>document versions are not tracked. </i></li></ul>
</p>

<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" class=\"wd-main data-table\">
	<thead>
		<tr class=\"wd-row\">
			<th class=\"wd-cell\">WebDAV client</th>
			<th class=\"wd-cell\">Basic<br />authorization</th>
			<th class=\"wd-cell\">Windows<br />authorization (IWA)</th>
			<th class=\"wd-cell\">SSL</th>
			<th class=\"wd-cell\">Port</th>
			<th class=\"wd-cell\">Present<br />in OS</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><a href=\"#oswindowsfolders\"><u>Web folder</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>-</td>
			<td>all</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsfolders\"><u>Web folder</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsfolders\"><u>Web folder</u></a>, Windows XP</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsfolders\"><u>Web folder</u></a>, Windows 2003/2000</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsfolders\"><u>Web folder</u></a>, Windows Server 2008</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsmapdrive\"><u>Network drive</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsmapdrive\"><u>Network drive</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsmapdrive\"><u>Network drive</u></a>, Windows XP</td>
			<td>-</td>
			<td>+</td>
			<td>-</td>
			<td>80</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href=\"#oswindowsmapdrive\"><u>Network drive</u></a>, Windows 2003/2000</td>
			<td>-</td>
			<td>+</td>
			<td>-</td>
			<td>80</td>
			<td>+</td>
		</tr>
		<tr>
			<td>MS Office 2007/2003/XP</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>-</td>
		</tr>
		<tr>
			<td>MS Office 2010</td>
			<td>+</td>
			<td>+</td>
			<td>the only option</td>
			<td>all</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href=\"#osmacos\"><u>MAC OS X</u></a></td>
			<td>+</td>
			<td>-</td>
			<td>+</td>
			<td>all</td>
			<td>+</td>
		</tr>
	</tbody>
</table>
<br>
<h2><a name=\"oswindows\"></a>Connecting the Document Library in Windows</h2>
<h4><a name=\"oswindowsnoties\"></a>Limitations in Windows</h4>
<div style=\"border:1px solid #ffc34f; background: #fffdbe;padding:1em;\">
	<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr>
			<td style=\"border-right:1px solid #FFDD9D; padding-right:1em;\">
				<img src=\"/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png\" width=\"20\" height=\"18\" border=\"0\"/>
			</td>
			<td style=\"padding-left:1em;\">
				<p><b>Windows 7</b> prohibits basic authorization by default; you have to edit the system registry to enable it (see <a href=\"#oswindowsreg\">details</a>). The web folder component does not support secure protocol. You will have to use http to access the library. </p>

				<p><b>Windows Vista</b> prohibits basic authorization by default; you have to edit the system registry to enable it (see <a href=\"#oswindowsreg\">details</a>.)</p>
				<p><b>Windows XP </b> requires an explicit port number in an URL even if using the standard port 80 (e.g. http://servername:80/).</p>
				<p><b>Windows 2008 Server </b>does not install the WebClient service by default. You have to install it manually:
					<ul>
						<li><i>Start -> Administrative Tools -> Server Manager -> Features</i></li>
						<li>Click <b>Add Features</b></li>
						<li>Select Desktop Experience and install it</li>
					</ul>
					Then, edit the system registry (see <a href=\"#oswindowsreg\">details</a>).
				</p>

				<p><b>You have to ensure the WebClient service is running before you connect to the library.</b></p>
			</td>
		</tr>
	</table>
</div>

<h4><a name=\"oswindowsreg\">Enabling non-HTTPS Authorization</h4>
<p>Change the value of the <b>Basic authentication</b> parameter in the system registry. Download: </p>
<ul>
  <li><a href=\"/bitrix/webdav/xp.reg\">.reg file</a> for <b>Windows XP, Windows 2003 Server</b>;</li>
  <li><a href=\"/bitrix/webdav/vista.reg\">.reg file</a> for <b>Windows 7, Vista, Windows 2008 Server</b>.</li>
</ul>
<p>Click <b>Run</b> in the file download dialog; then, click <b>Yes</b> in the <b>Registry Editor</b> dialog:</p>
<p><img src=\"#TEMPLATEFOLDER#/images/en/editor_warning.png\" width=\"574\" height=\"201\" border=\"0\"/></p>
<p>If you use browsers other than Internet Explorer, the file will be downloaded, but the Registry Editor will not start automatically. You will have to run the downloaded file manually</b>.</p>
<p><b>Using Registry Editor to Edit the Parameter</b></p>
<p>Click <b>Start &gt; Run</b>.</p>

<p><img src=\"#TEMPLATEFOLDER#/images/en/regedit.png\" width=\"427\" height=\"235\" border=\"0\"/></a></p>

<p>In the <b>Open</b> field, type <b>regedit</b> and click <b>OK</b>.</p>
<p>For <b>Windows XP, Windows 2003 Server</b> change the parameter to:</p>
<p></p>
  <table cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
    <tbody>
      <tr><td width=\"638\" valign=\"top\">
          <p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters] &quot;UseBasicAuth&quot;=dword:00000001</p>
         </td></tr>
     </tbody>
   </table>
<p></p>
<p>For <b>Windows 7, Vista, Windows 2008 Server</b> change the parameter or create the registry entry:</p>
	<table cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
		<tbody>
			<tr><td width=\"638\" valign=\"top\">
				<p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters]
				<br />
				&quot;BasicAuthLevel&quot;=dword:00000002</p>
			</td></tr>
		</tbody>
	</table>

<p>Restart the <a href=\"#oswindowswebclient\"><b>Webclient</b></a> service.</p>
<h4><a name=\"oswindowswebclient\"></a><b>Running the Web Client Service</b></h4>
<p>Click <b>Start &gt; Control Panel &gt; System and Security &gt; Administrative Tools &gt; Services</b> to open the <b>Services</b> window:
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/webclient.png',820,599,'Services');\">
<img src=\"#TEMPLATEFOLDER#/images/en/webclient_sm.png\" style=\"CURSOR: pointer\" width=\"250\" height=\"183\" alt=\"Click to Enlarge\"  border=\"0\"/></a></p>
<p>Find the <b>Web Client</b> service in the list and run or restart it. To have the service run at system start-up, change the <b>Startup</b> parameter to <b>Automatic</b>:
<p><img src=\"#TEMPLATEFOLDER#/images/en/properties.png\"  width=\"418\" height=\"474\" alt=\"Click to Enlarge\"  border=\"0\"/></p></li>
<p>You can now map the folder.</p>

<h4><a name=\"oswindowsfolders\">Connecting Using Web Folders</h4>
<div style=\"border:1px solid #ffc34f; background: #fffdbe;padding:1em;\">
	<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr>
			<td style=\"border-right:1px solid #FFDD9D; padding-right:1em;\">
				<img src=\"/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png\" width=\"20\" height=\"18\" border=\"0\"/>
			</td>
			<td style=\"padding-left:1em;\">
			    <b>Windows 7</b> does not support HTTPS/SSL secure protocol.<br>
				The web folders component is not installed in <b>Windows 2003 Server</b>. You will have to install it manually ( <a href=\"http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64\" target=\"_blank\">instructions at Microsoft Corporation website</a> ).
			</td>
		</tr>
	</table>
</div>
<p>Ensure that you have made proper <a href=\"#oswindowsreg\">modification to the system registry</a> and the <a href=\"#oswindowswebclient\">Webclient service is running</a>.</p>
<p>A special web folder connection component is required to connect to the document library. Follow the instructions at the <a href=\"http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64\" target=\"_blank\">Microsoft website</a> ). </p>
<p>If you are using <b>Internet Explorer</b>, click <b>Network Drive</b> on the toolbar.</p>
<p><img src=\"#TEMPLATEFOLDER#/images/en/network_storage_contex_panel.png\" width=\"735\" height=\"70\"  border=\"0\" /></p>

<p>When using other browsers, or if the library was not open as a network drive:</p>
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

<p>From now on, you can access the folder by clicking <b>Run > Network Neighborhood > Folder Name</b>.</p>


<h4><a name=\"oswindowsmapdrive\"></a>Mapping the Library as a Network Drive</h4>
<div style=\"border:1px solid #ffc34f; background: #fffdbe;padding:1em;\">
	<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr>
			<td style=\"border-right:1px solid #FFDD9D; padding-right:1em;\">
				<img src=\"/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png\" width=\"20\" height=\"18\" border=\"0\"/>
			</td>
			<td style=\"padding-left:1em;\">
				<b>Attention! Windows XP and Windows Server 2003 </b>do not support HTTPS/SSL secure protocol.
			</td>
		</tr>
	</table>
</div>
<p>To connect a library as a network disk in <b>Windows 7</b> using the <b>HTTPS/SSL</b> secure protocol:  execute the command <b>Start &gt; Run &gt; cmd</b>. In the command line, enter:<br>
<table cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
	<tbody>
		<tr><td width=\"638\" valign=\"top\">
			<p>net use z: https://&lt;your_server&gt;/docs/shared/ /user:&lt;userlogin&gt; *</p>
		</td></tr>
	</tbody>
</table>
<br>
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
<p>Later, you can open the folder in Windows Explorer where the folder will be shown as a drive under My Computer, or in any file manager.</p>

<h2><a name=\"osmacos\"></a>Connecting The Library in Mac OS and Mac OS X</h2>

<ul>
<li>Select <i>Finder Go->Connect to Server command</i>;</li>
<li>Type in the library address in <b>Server Address</b>:</p>
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/en/macos.png',465,550,'Mac OS X');\">
<img width=\"235\" height=\"278\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/en/macos_sm.png\" style=\"cursor: pointer;\" alt=\"Click to Enlarge\" /></a></li>
</ul>
<br />

<h2><a name=\"maxfilesize\"></a>Increasing the Maximum Size of Uploaded Files</h2>

<p>Essentially, the maximum size of uploaded files is the value of (<b>upload_max_filesize</b> or <b>post_max_size</b>) PHP variables and the component parameters.</p>
<p>To increase the file size quota, edit the following values in <b>php.ini</b>:</p>

<table cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
  <tbody>
      <tr><td width=\"638\" valign=\"top\">
	  <p>upload_max_filesize = required value;
	  <br/>post_max_size = more than upload_max_filesize;</p>
      </td></tr>
  </tbody>
</table>

<p>If using virtual hosting, edit <b>.htaccess</b> as well:</p>

<table cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
  <tbody>
      <tr><td width=\"638\" valign=\"top\">
	  <p>php_value upload_max_filesize required value<br/>
	  php_value post_max_size more than _upload_max_filesize</p>
      </td></tr>
  </tbody>
</table>

<p>It is likely that you will have to contact your hosting administrator in order to increase the values of PHP variables (<b>upload_max_filesize</b> and <b>post_max_size</b>).</p>
<p>After the PHP quotas have been increased, edit the parameters of your components accordingly.</p>";
?>
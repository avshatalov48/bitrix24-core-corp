<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<h3>Connecting Using Web Folders</h3>
<p>Ensure that you have made proper <a href="<?=$arResult["URL"]["HELP"]?>#oswindowsreg">modification to the system registry</a> and the <a href="<?=$arResult["URL"]["HELP"]?>#oswindowswebclient">Webclient service is running</a>.</p>
<p>A special web folder connection component is required to connect to the document library. Follow the instructions at the <a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">Microsoft website</a> ). </p>
<ul>
<li>Run Windows Explorer;</li>
<li>Select <b>Map Network Drive</b>;</li>
<li>Click the link <b>Connect to a Web site that you can use to store your documents and pictures</b>:</p> 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/en/network_add_1.png\',630,461,\'Map Network Drive\');'?>">
<img width="250" height="183" border="0" src="<?=$templateFolder.'/images/en/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Click to Enlarge" /></a> <br />This will run the <b>Add Network Location</b>.</li>
<li>In the wizard window, click <b>Next</b>. The next wizard window will appear;</li>
<li>In this window, click <b>Choose a custom network location</b> and then click <b>Next</b>:
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/en/network_add_4.png\',610,499,\'Add Network Location\');'?>">
<img width="250" height="205" border="0" src="<?=$templateFolder.'/images/en/network_add_4_sm.png'?>" style="cursor: pointer;" alt="Click to Enlarge" /></a></li>
<li>Here, in the <b>Internet or network address</b> field, type the URL of the mapping folder in the format: <i>http://your_server/docs/shared/</i>;</li>
<li>Click <b>Next</b>. If prompted for a <b>User name</b> and <b>Password</b>, enter your login and password, and then click <b>OK</b>.</li>
</ul>

<p>From now on, you can access the folder by clicking <b>Run > Network Neighborhood > Folder Name</b>.</p>

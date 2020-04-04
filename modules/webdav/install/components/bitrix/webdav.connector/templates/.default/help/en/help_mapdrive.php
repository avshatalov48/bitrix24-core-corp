<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<h3>Mapping the Library as a Network Drive</h3>
<p>To connect a library as a network disk using <b>file manager</b>:
<ul>
<li>Run Windows Explorer</b>;</li> 
<li>Select <i>Tools > Map Network Drive</i>. The network disc wizard will open: 
<br /><br /><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/en/network_storage.png\',629,459,\'Map Network Drive\');'?>">
<img width="250" height="183" border="0" src="<?=$templateFolder.'/images/en/network_storage_sm.png'?>" style="cursor: pointer;" alt="Click to Enlarge" /></a></li>
<li>In the <b>Drive</b> field, specify a letter to map the folder to;</li>
<li>In the <b>Folder</b> field, enter the path to the library: <i>http://your_server/docs/shared/</i>. If you want this folder to be available when the system starts, check the <b>Reconnect at logon</b> option;</li>
<li>Click <b>Ready</b>. If prompted for a User name and Password, enter your login and password, and then click <b>OK</b>.</li>
</ul>
</p>
<p>Later, you can open the folder in Windows Explorer where the folder will be shown as a drive under My Computer, or in any file manager.</p>

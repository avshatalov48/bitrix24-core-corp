<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript" src="/bitrix/js/webdav/imgshw.js"></script>

Es gibt zwei Möglichkeiten mit der Dokumentenbibliothek zu arbeiten: über den Browser (Internet Explorer, Opera, Fire Fox usw.) oder über das WebDAV-Protokoll (bei Windows: Netzwerklaufwerk, Laufwerk-Verbindung).<br><br>
<ul>
	<li><b><a href="#iewebfolder">Arbeit mit der Dokumentenbibliothek über den Browser</a></b></li>
	<li><b><a href="#ostable">Vergleichstabelle der WebDAV-Anwendungen von Kunden</a></b></li>
	<li><b><a href="#oswindows">Einbindung der Dokumentenbibliothek in  Windows</a></b></li>
	<ul>
		<li><a href="#oswindowsnoties">Einschränkungen von Windows</a></li>
		<li><a href="#oswindowsreg">Autorisierungsmöglichkeit ohne https</a></li>
		<li><a href="#oswindowswebclient">Neustart des Services Web-Client</a></li>
		<li><a href="#oswindowsfolders">Einbindung über die Netzwerklaufwerk-Komponente</a></li>
		<li><a href="#oswindowsmapdrive">Verbindung des Netzwerklaufwerks</a></li>

	</ul>
	<li><b><a href="#osmacos">Bibliothek-Einbindung in Mac OS, Mac OS X</a></b></li>
	<li><b><a href="#maxfilesize">Erhöhen vom maximal zulässigen Größenlimit für Dateien, die hochgeladen werden</a></b></li>
</ul>


<h2><a name="browser"></a>Arbeit mit der Dokumentenbibliothek über den Browser</h2>
<h4><a name="upload"></a>Hochladen von Dokumenten</h4>
<p>Um  Dokumente hochzuladen, wechseln Sie in den Ordner, in welchen die Dokumente hochgeladen werden sollen. Drücken Sie die Schaltfläche <b>Hochladen</b> auf dem Kontextpanel.</p>
<p><img src="<?=$templateFolder.'/images/de/upload_contex_panel.png'?>" width="691" height="67"  border="0"/></p>
<p>Es öffnet sich ein neues Fenster zum Hochladen von Dateien. Dieses Fenster kann drei Ansichten haben:</p>
<ul>
<li>Standard Ansicht: Dokumente können als Dateien aus verschiedenen Verzeichnissen (Schaltfläche <b>Neue Dateien</b>) oder als ganzer Ordner (Schaltfläche <b>Neuer Ordner</b>) hochgeladen werden;</li><li>Klassische Ansicht: Dokumente können aus einem bestimmten Verzeichnis hochgeladen werden;</li>
<li>Einfache Ansicht: jedes Dokument wird einzeln hochgeladen.</li>
</ul>

<p>Wählen Sie die gewünschte Ansicht aus und wählen Sie Dokumente aus, die hochgeladen werden sollen.</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/load_form.png\',771,557,\'Hochladen von Dokumenten\');'?>">
<img src="<?=$templateFolder.'/images/de/load_form_sm.png'?>" style="CURSOR: pointer" width="300" height="217" alt="Bild vergrößern"  border="0"/></a></p>

<p>Dann drücken Sie die Schaltfläche <b>Hochladen</b>.</p>

<br/>
<h4><a name="bizproc"></a>Initiieren von Geschäftsprozessen</h4>

<p>In manchen Fällen müssen hochgeladene Dokumente zusätzlich an bestimmte Arbeitsgänge gekoppelt werden, z.B. muss ein Dokument vor Veröffentlichung genehmigt oder eben über die Publikation eines Dokuments abgestimmt werden. Dazu werden Geschäftsprozesse verwendet. </p>

<p>Um ein Geschäftsprozess zu initiieren, klicken Sie auf den Punkt <b>Neuer Geschäftsprozess</b>:</p>
<p><img src="<?=$templateFolder.'/images/de/new_bizproc.png'?>" width="531" height="262" border="0"/></p>
<p>Es öffnet sich ein weiteres Kontextmenü, in welchem eine Geschäftsprozessvorlage ausgewählt und der Geschäftsprozess initiiert werden kann.</p>
<? if (!empty($arResult['BPHELP'])) { ?>
<p><b>Anmerkung</b>: detaillierte Informationen zu den Geschäftsprozessen gibt es auf der Hilfeseite <a href="<?=$arResult['BPHELP']?>" target="_blank">Geschäftsprozesse</a>.</p>
<? } ?>
<p>Zum Verwalten von Vorlagen der Geschäftsprozesse drücken Sie auf die Schaltfläche <b>Geschäftsprozess</b>, die sich auf dem Kontextpanel befindet:</p> 
<p><img src="<?=$templateFolder.'/images/de/bizproc_contex_panel.png'?>" width="718" height="65" border="0"/></p>

<br/>
<h4><a name="delete"></a>Bearbeiten und Löschen von Dokumenten</h4>
<p>Die Dokumentenverwaltung erfolgt entweder über das Kontextmenü 
<p><img src="<?=$templateFolder.'/images/de/delete_file.png'?>" width="378" height="224" border="0"/></p> oder über das Gruppenauswahlpanel, das bestimmte Aktionen enthält und sich unter der Dokumentenliste befindet.
<br/><br/>
<h4><a name="office"></a>Dokumentenbearbeitung mit Microsoft Office ab Version 2003</h4>
<p>Beachten Sie, dass die Dokumentenbearbeitung über WebDAV nur aus dem Internet Explorer möglich ist.</p>

<p>Klicken Sie auf das Stift-Symbol, welches sich hinter dem Dateinamen befindet, dann bearbeiten Sie das Dokument, speichern Sie es und schließen Sie den Anhang. Die Änderungen werden auf dem Server in der Bibliothek gespeichert.</p>
<i><div class="hint"><b>Anmerkung</b>: Wird das Dokument bearbeitet, wird in der Zeile mit dem Dokumentennamen ein Symbol erscheinen. Gelb <img src="<?=$templateFolder?>/images/yellow_status.png" width="14" height="14" border="0"/> bedeutet dabei, dass das Dokument durch Sie gesperrt ist. Rot <img src="<?=$templateFolder
?>/images/red_status.png" width="14" height="14" border="0"/> - bedeutet, dass ein anderer Mitarbeiter es zur Zeit bearbeitet. Um das Dokument zu entsperren, benutzen Sie das Kontextmenü und klicken Sie auf <b>Freigeben</b>.
</div></i>

<br>
<h2><a name="ostable"></a>Vergleichstabelle der WebDAV-Anwendungen</h2>

<p><b>Anmerkung</b>. <i>Wenn WebDAV zur Verwaltung der Bibliothek beim Dokumentenmanagement oder bei den Geschäftsprozessen verwendet wird, müssen einige Einschränkungen berücksichtigt werden:<br>
	<ul> <li>Der Geschäftsprozess für ein Dokument kann nicht initiiert werden; </li>
	<li>Dokumente können nicht hochgeladen oder bearbeitet werden, wenn Geschäftsprozesse zum Autostart angemeldet sind, die obligatorische Parameter des Autostartes ohne Standard-Bedeutungen haben;</li>
	<li>Die Änderungshistorie kann nicht nachverfolgt werden.</i></li></ul>
</p>


<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main data-table">
	<thead>
		<tr class="wd-row">
			<th class="wd-cell">WebDAV</th>
			<th class="wd-cell">Autorisierung<br />Basis (Basic)</th>
			<th class="wd-cell">Autorisierung<br />Windows (IWA)</th>
			<th class="wd-cell">SSL</th>
			<th class="wd-cell">Port</th>
			<th class="wd-cell">Installiert<br />im OS</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><a href="#oswindowsfolders"><u>Netzwerklaufwerk</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>-</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Netzwerklaufwerk</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Netzwerklaufwerk</u></a>, Windows XP</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Netzwerklaufwerk</u></a>, Windows 2003/2000</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>Alle</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Netzwerklaufwerk</u></a>, Windows Server 2008</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>all</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Netzlaufwerk</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Netzlaufwerk</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Netzlaufwerk</u></a>, Windows XP</td>
			<td>-</td>
			<td>+</td>
			<td>-</td>
			<td>80</td>
			<td>+</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Netzlaufwerk</u></a>, Windows 2003/2000</td>
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
			<td>Alle</td>
			<td>-</td>
		</tr>
		<tr>
			<td>MS Office 2010</td>
			<td>+</td>
			<td>+</td>
			<td>nur</td>
			<td>Alle</td>
			<td>-</td>
		</tr>
		<tr>
			<td><a href="#osmacos"><u>MAC OS X</u></a></td>
			<td>+</td>
			<td>-</td>
			<td>+</td>
			<td>Alle</td>
			<td>+</td>
		</tr>
	</tbody>
</table>
<br>
<h2><a name="oswindows"></a>Einbinden der Dokumentenbibliothek in Windows</h2>
<h4><a name="oswindowsnoties"></a>Einschränkungen von Windows</h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				<p>In <b>Windows 7</b> ist die Basisautorisierung (Basic Authorization) nicht möglich, im Registrierungs-Editor müssen Änderungen vorgenommen werden. <a href="#oswindowsreg">Details</a>. Die Netzwerklaufwerk-Komponente funktioniert <b>nicht</b> mit dem SSL-Protokoll. Bei der Arbeit mit der Bibliothek in Windows 7 muss das http-Protokoll benutzt werden. </p>
				<p>In <b>Windows Vista</b> Vista ist die Basisautorisierung (Basic Authorization) nicht möglich, im Registrierungs-Editor müssen Änderungen vorgenommen werden. <a href="#oswindowsreg">Details</a>.</p>
				<p>In <b>Windows XP </b> muss die Portnummer immer angegeben werden, selbst wenn Port 80 verwendet wird (http://servername:80/).</p>
				<p>Im <b>Windows 2008 Server </b>ist der Service Web-Client nicht installiert. Features müssen wie folgt installiert werden:  
					<ul>
						<li><i>Start -> Administrative Tools -> Server Manager -> Features</i></li>
						<li>Oben rechts auf <b>Add Features</b> klicken</li>
						<li>Desktop Experience auswählen und installieren</li>
					</ul>
					Außerdem müssen im Registrierungs-Editor Änderungen vorgenommen werden. <a href="#oswindowsreg">Details</a>.
				</p>
				
				<p><b>Vor der Einbindung der Dokumentenbibliothek vergewissern Sie sich, dass der Service WebClient gestartet ist.</b></p>
			</td>
		</tr>
	</table>
</div>

<h4><a name="oswindowsreg">Autorisierung ohne https</h4>
<p><b>Zuerst</b> muss der Parameter Basisauthentifizierung (<b>Basic authentication</b>) im Registrierungs-Editor von Windows geändert werden: </p>
<ul>
  <li><a href="/bitrix/webdav/xp.reg">Registrierungs-Editor</a> ändern für <b>Windows XP, Windows 2003 Server</b>;</li> 
  <li><a href="/bitrix/webdav/vista.reg">Registrierungs-Editor</a> ändern für <b>Windows 7, Vista, Windows 2008 Server</b>.</li> 
</ul>
<p>Im Fenster zum Dateihochladen drücken Sie die Schaltfläche <b>Starten</b>. Im Dialogfenster des <b>Registrierungs-Editors</b>, wo die Meldung über eventuelle Unglaubwürdigkeit der Quelle erscheint, klicken Sie auf <b>Ja</b>. </p> 
<p><img src="<?=$templateFolder.'/images/de/vista_reg.png'?>" width="572" height="214" alt="Bild vergrößern"  border="0"/></p>
<p>Wenn Sie einen Browser verwenden, der die .reg-Dateien nicht zulässt, müssen Sie die Datei hochladen und mit Hilfe des Registereditors manuell starten oder im Register bearbeiten.</p>
<p><b>Parameteränderung mit Hilfe des Registrierungseditors</b></p>
<p>Führen Sie den Befehl <b>Start &gt; Ausführen</b> aus.</p>

<p><img src="<?=$templateFolder.'/images/de/regedit.png'?>" width="415" height="212" border="0"/></a></p>

<p>Im Feld <b>Öffnen</b> geben Sie <b>regedit</b> ein und klicken Sie auf <b>OK</b>.</p>
<p>Für <b>Windows XP, Windows 2003 Server</b> muss der Parameterwert geändert werden auf:</p>
<p></p>
  <table cellspacing="0" cellpadding="0" border="1"> 
    <tbody> 
      <tr><td width="638" valign="top"> 
          <p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters] &quot;UseBasicAuth&quot;=dword:00000001</p>
         </td></tr>
     </tbody>
   </table>
<p></p>
<p>Für <b>Windows 7, Vista, Windows 2008 Server</b> muss der Parameterwert wie folgt geändert oder ein Eintrag im Registrierungs-Editor gemacht werden. </p>
	<table cellspacing="0" cellpadding="0" border="1"> 
		<tbody> 
			<tr><td width="638" valign="top"> 
				<p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters] 
				<br />
				&quot;BasicAuthLevel&quot;=dword:00000002</p>
			</td></tr>
		</tbody>
	</table>

<p><b>Dann</b> muss der Service <a href="#oswindowswebclient"><b>Webclient</b></a> neu gestartet werden.</p>
<h4><a name="oswindowswebclient"></a><b>Den Service WebClient neu starten</b></h4>
<p>Zum Neustart: <b>Start &gt; Systemsteuerung &gt; System und Sicherheit &gt; Verwaltung &gt; Dienste</b>. Es öffnet sich das Dialogfenster des <b>Dienste</b>: 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/web_client.png\',792,568,\'Dienste\');'?>">
<img src="<?=$templateFolder.'/images/de/web_client_sm.png'?>" style="CURSOR: pointer" width="250" height="180" alt="Bild vergrößern"  border="0"/></a></p>  
<p>Finden Sie in der Gesamtliste der Services die Zeile <b>WebClient</b> und starten Sie ihn neu. Damit dieser Service in Zukunft beim Systemstarten automatisch gestartet wird, muss in den Serviceeigenschaften der Parameterwert <b>Starttyp</b> auf <b>Automatisch</b> eingestellt werden:</b>:
<p>
<img src="<?=$templateFolder.'/images/de/properties.png'?>" width="420" height="476" alt="Eigenschaften von WebClient"  border="0"/></p></li>
<p>Jetzt kann der Ordner eingebunden werden.</p>

<h4><a name="oswindowsfolders">Einbindung über die Netzwerklaufwerk-Komponente (web-folders)</h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				In <b>Windows 7</b> funktioniert die Einbindung mit den HTTPS/SSL-Protokollen nicht.<br>
				Im <b>Windows 2003 Server</b> ist die Netzwerklaufwerk-Komponente nicht installiert. Diese Komponente muss installiert werden (<a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">auf die Website von Mikrosoft wechseln</a> ).
			</td>
		</tr>
	</table>
</div>
<p>Bevor Sie die Dokumentenbibliothek einbinden, vergewissern Sie sich, dass <a href="#oswindowsreg">Änderungen im Registrierungs-Editor vorgenommen wurden</a> und <a href="#oswindowswebclient">der Service WebClient gestartet</a> ist.</p>
<p>Um die Dokumentenbibliothek auf diese Weise einzubinden, ist die Netzwerklaufwerk-Komponente erforderlich. Wünschenswert ist die Installation der neuesten Software für die Netzwerklaufwerk auf dem Kunden-PC <a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">auf die Website von Mikrosoft wechseln</a> ). </p>
<p>Benutzen Sie den Browser <b>Internet Explorer</b> drücken Sie auf die Schaltfläche <b>Netzwerklaufwerk</b>, die sich in der Toolbar befindet. </p>
<p><img width="690" height="65"  border="0" src="/images/de/network_storage_contex_panel.png"/></p>
<p>Wenn Sie andere Browser benutzen oder wenn die Bibliothek nicht als Netzwerklaufwerk geöffnet wurde:</p>
<ul>
<li>Starten Sie den Datei-Manager (Explorer);</li>
<li>Wählen Sie im Menü den Punkt <b>Service &gt; Netzlaufwerk verbinden </b>aus;</li>
<li>Mit Hilfe des Links <b>Verbindung mit einer Website herstellen, auf der Sie Dokumente und Bilder speichern können</b> starten Sie den Assistenten zum <b>Hinzufügen eines Netzwerkes</b>:</p> 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_1.png\',630,458,\'Netzlaufwerk verbinden\');'?>">
<img width="250" height="182" border="0" src="<?=$templateFolder.'/images/de/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></b>.</li>
<li>Drücken Sie auf die Schaltfläche <b>Weiter</b>, es öffnet sich das zweite Fenster des <b>Assistenten</b>;</li>
<li>Aktivieren Sie in diesem Fenster die Position <b>Eine benutzerdefinierte Netzwerkadresse auswählen</b>, drücken Sie auf die Schaltfläche <b>Weiter</b>. Es öffnet sich der nächste Schritt des <b>Assistenten</b>:
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_4.png\',612,498,\'Eine Netzwekadresse hinzufügen\');'?>">
<img width="250" height="204" border="0" src="<?=$templateFolder.'/images/de/network_add_4_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></li>
<li>Im Feld <b>Internet- oder Netzwerkadresse</b> geben Sie URL des Ordners, mit dem Verbindung hergestellt werden soll, wie folgt ein: http://&lt;Ihr_Server&gt;/docs/shared/</i>;</li>
<li>Drücken Sie auf die Schaltfläche <b>Next</b>. Wenn sich das Fenster zur Autorisierung öffnet, geben Sie hier die Autorisierungsdaten für den Server ein.</li>
</ul>

<p>Um dann den Ordner öffnen zu können, führen Sie den Befehl aus: <b>Start > Netzwerk > Ordnername</b>.</p>
 

<h4><a name="oswindowsmapdrive"></a>Netzlaufwerk-Verbindung</h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				<b>Achtung!</b> In <b>Windows XP und im Windows Server 2003</b> funktioniert die Verbindung mit HTTPS/SSL-Protokollen nicht. 
			</td>
		</tr>
	</table>
</div>
<p>Um die Dokumentenbibliothek  in <b>Windows 7</b> als Netzlaufwerk über das sichere Protokoll <b>HTTPS/SSL</b>: einzubinden: Drücken Sie <b>Start</b> und geben in das Suchfeld "cmd" ein. In der Eingabeaufforderung dann folgendes eingeben:<br>
<table cellspacing="0" cellpadding="0" border="1"> 
	<tbody> 
		<tr><td width="638" valign="top"> 
			<p>net use z: https://&lt;Ihr_Server&gt;/docs/shared/ /user:&lt;userlogin&gt; *</p>
		</td></tr>
	</tbody>
</table>
<br>
<ul>
<p>Um die Dokumentenbibliothek als Netzlaufwerk über den Dateimanager (Windows Explorer) einzubinden:</p> 
<li>Starten Sie den Datei-Manager (Explorer);
<br><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/network_add_4.png\',612,498,\'Eine Netzweradresse hinzufügen\');'?>">
<img width="250" height="185" border="0" src="<?=$templateFolder.'/images/de/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></li>
<li>Wählen Sie im Menü den Punkt <b>Service >Netzlaufwerk verbinden</b> aus. Es öffnet sich das Dialogfenster zur Verbindung des Netzlaufwerks:</li>
<li>Im Feld <b>Laufwerk</b> geben Sie einen Buchstaben für den  Ordner an, mit dem Verbindung hergestellt werden soll;</li>
<li>Im Feld <b>Ordner</b> geben Sie Pfad zur Bibliothek ein: http://&lt;Ihr_Server&gt;/docs/shared/. Wenn der Ordner bei jedem Systemstart zur Vorschau angeschaltet werden soll, markieren Sie <b>Beim Systemstart wiederherstellen</b>;</li>
<li>Drücken Sie auf Fertigstellen. Wenn sich das Dialogfenster des Operationssystems zur Autorisierung öffnet, geben Sie die Autorisierungsdaten für den Server ein. </li>
</ul>
</p>
<p>Später kann der Ordner entweder mit dem Windows Explorer, wo der Ordner als einzelnes Laufwerk dargestellt wird, oder mit einem beliebigen Dateimanager geöffnet werden.</p>

<h2><a name="osmacos"></a>Bibliothek-Einbindung in Mac OS, Mac OS X</h2>
<p>Um die Bibliothek einzubinden:</p>
<ul>
<li>Öffnen Sie <i>Finder Go->Connect to Server command</i>;</li>
<li>Im Feld <b>Server Address</b> geben Sie die Adresse der Bibliothek ein:</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/de/macos.png\',465,550,\'Mac OS X\');'?>">
<img width="235" height="278" border="0" src="<?=$templateFolder.'/images/de/macos_sm.png'?>" style="cursor: pointer;" alt="Bild vergrößern" /></a></li>
</ul>
<br />

<h2><a name="maxfilesize"></a>Erhöhen vom maximal zulässigen Größenlimit für Dateien, die hochgeladen werden</h2>

<p>Ein maximal zulässiges Größenlimit der hochzuladenden Datei bedeutet minimale Werte der Variablen PHP (<b>upload_max_filesize</b> und <b>post_max_size</b>) und Einstellungsparameter der Komponenten.</p>
<p>Möchten Sie die Quote erhöhen, die empfohlene Werte übertrifft, müssen Sie folgende Änderungen <b>php.ini</b> eintragen:</p>

<table cellspacing="0" cellpadding="0" border="1"> 
  <tbody> 
      <tr><td width="638" valign="top">
	  <p>upload_max_filesize = gewünschter_Wert;
	  <br/>post_max_size = übertrifft_Größe_upload_max_filesize;</p>
      </td></tr>
  </tbody>
</table>

<p>Wenn Sie einen Internet-Platz mieten (Virtual-Hosting), müssen Sie die Änderungen in der Datei <b>.htaccess</b> vornehmen:</p>

<table cellspacing="0" cellpadding="0" border="1"> 
  <tbody> 
      <tr><td width="638" valign="top">
	  <p>php_value upload_max_filesize gewünschter_Wert<br/>
	 php_value post_max_size übertrifft_Größe_upload_max_filesize</p>
      </td></tr>
  </tbody>
</table>

<p>Eventuell werden Sie sich an den Hosting-Anbieter mit der Bitte wenden müssen, minimale Werte der Variablen PHP zu erhöhen (<b>upload_max_filesize</b> und <b>post_max_size</b>).</p>
<p>Nachdem die PHP-Quoten erhöht werden, müssen die Komponenteneinstellungen geändert werden.</p>

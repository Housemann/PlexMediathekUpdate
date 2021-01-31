<?php
    // Klassendefinition
    class PlexMediathekUpdate extends IPSModule {
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

            // Variables
            $this->RegisterVariableString ("HtmlMediathekBox", "Mediathek Box", "~HTMLBox", 0);
            $this->RegisterTimer ("Update", 0, 'PLEX_FillHtmlBox($_IPS[\'TARGET\']);');

            // Properties
            $this->RegisterPropertyInteger ("UpdateIntervall", 60);
            $this->RegisterPropertyString ("IPAddress", "2.2.2.2");
            $this->RegisterPropertyString ("Port", "32400");
            $this->RegisterPropertyString ("UpdateButtonColor", "bc_green");
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateIntervall") * 60 * 1000);

            // check IP && Port
            $ip   = $this->ReadPropertyString("IPAddress");
            $port = $this->ReadPropertyString("Port");

            $rc_ip    = $this->CheckIP($ip);
            $rc_port  = $this->CheckPort($port);

            
            if($rc_ip>0 && $rc_port>0) {
              $rc = $this->PingHost($ip);
              if($rc==1) {
                // HTML Box füllen
                $this->FillHtmlBox();
                $this->LogMessage($this->ReturnMessage($rc), KL_NOTIFY);
              } else {
                // Return code
                $this->LogMessage($this->ReturnMessage($rc), KL_ERROR);
              }
            } elseif($rc_ip>0 && $rc_port<0) {
              // Return code 
              $this->LogMessage($this->ReturnMessage($rc_port), KL_ERROR);
            } elseif($rc_ip<0 && $rc_port>0) {
              // Return code
              $this->LogMessage($this->ReturnMessage($rc_ip), KL_ERROR);
            } elseif($rc_ip<0 && $rc_port<0) {
              // Return code 
              $this->LogMessage($this->ReturnMessage($rc_ip)." & ".$this->ReturnMessage($rc_port), KL_ERROR);
            }
        }

        // Port Check 
        public function CheckPort(string $port) {
          $rc = 1;
          // Check Port is Numeric
          if(is_numeric($port)<>true) {
            $rc=-12;
          }
          // Check Port is Max LEN 5
          if($rc==1 && strlen($port)>5) {
            $rc=-14;
          }
          // Check Port is lower then 65535 
          if($rc==1 && intval($port)>65535) {
            $rc=-16;
          }
          return $rc;
        }
        
        // Check IP is valid
        public function CheckIP(string $ip) {
          if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $rc=1;
          } else {
            $rc=-22;
          }
          return $rc;
        }

        // Check Host available
        public function PingHost(string $ip) {
          $rc=1;
          if(Sys_Ping($ip, 5000)==0) {
            $rc=-32;
          }
          return $rc;
        }

        // Statuscodes and Return Message
        public function ReturnMessage(int $rc) {
          switch($rc) {
            case 1:
                return $this->Translate("Update successed");
                break;
            case -12:
                return $this->Translate("Port is not numeric");
                break;
            case -14:
                return $this->Translate("Port is larger then five digits");
                break;
            case -16:
                return $this->Translate("Port is lager den maximal port number");
                break;
            case -22:
                return $this->Translate("No IPV4 Ip-Adress");
                break;
            case -32:
                return $this->Translate("Host not aviable");
                break;
            case -42:
                return $this->Translate("URL is not reachable");
                break;
          }
        }

        // Chekc URL is valid
        public function url_check(string $url) {
          $hdrs = @get_headers($url);
          return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]) : false;
        }

        public function FillHtmlBox() {
          $ip   = $this->ReadPropertyString("IPAddress");
          $port = $this->ReadPropertyString("Port");
               
          $url  = 'http://'.$ip.':'.$port.'/library/sections';
          
          if($this->url_check($url)) {

            // Plex XML Metadaten zu Bibliotheken auslesen und in Array konvertieren	
            $homepage  = simplexml_load_file($url);	
            $array_xml = json_decode(json_encode($homepage),true);

            if(is_countable($array_xml)) {
              $count_xmlData = count($array_xml['Directory']);
              $array_librarys = array();

              // Neues Array mit benötigten Daten erstellen
              for ($i = 0; $i < $count_xmlData; $i++) {
                $array_librarys[] = array(
                  'title' => $array_xml['Directory'][$i]['@attributes']['title'],
                  'key' => $array_xml['Directory'][$i]['@attributes']['key'], 
                  'type' => $array_xml['Directory'][$i]['@attributes']['type'],
                  'scannedAt' => date("d.m.Y H:i:s", $array_xml['Directory'][$i]['@attributes']['scannedAt'])
                );
              }  
              
              // HTML Tabelle füllen
              $count_array_librarys = count($array_librarys);
              $font_size_header = "";
              $font_size_table = "";

              // Etwas CSS und HTML
              $style = "";
              $style = $style.'<style type="text/css">';
              $style = $style.'table.test { width: 100%; border-collapse: true;}';
              $style = $style.'Test { border: 2px solid #444455; }';
              $style = $style.'td.lst { width: 42px; text-align:center; padding: 2px;  border-right: 0px solid rgba(255, 255, 255, 0.2); border-top: 0px solid rgba(255, 255, 255, 0.1); }';
              $style = $style.'.bc_blue { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 0, 255); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
              $style = $style.'.bc_red { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 0, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
              $style = $style.'.bc_green { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 255, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
              $style = $style.'.bc_yellow { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 255, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
              $style = $style.'.bc_orange { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 160, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
              $style = $style.'</style>';

              $s = '';	
              $s = $s . $style;

              //Tabelle Erstellen
              $s = $s . '<table class=\'test\'>'; 

              $s = $s . '<tr>'; 
              $s = $s . "<tr>"; 
              $s = $s . "<td style='background: #121212;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek")."</td>";
              $s = $s . "<td style='background: #121212;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek Type")."</td>";
              $s = $s . "<td style='background: #121212;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek ID")."</td>"; 
              $s = $s . "<td style='background: #121212;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Last Update")."</td>";
              $s = $s . "<td style='background: #121212;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Update Mediathek")."</td>";
              $s = $s . "</tr>"; 
              $s = $s . "<tr>"; 

              for($i = 0; $i < $count_array_librarys; $i++) {
                $newArray = $array_librarys[$i];	

                $title  = $newArray['title'];
                $type   = $newArray['type'];
                $key    = $newArray['key'];
                $upd    = $newArray['scannedAt'];

                $class  = $this->ReadPropertyString("UpdateButtonColor");
                $toggle = $this->Translate('Update');

                $s = $s . "<tr>"; 
                $s = $s . "<td style='text-align:left;font-size:$font_size_table;' colspan='2'>$title</td>";
                $s = $s . "<td style='text-align:left;font-size:$font_size_table;' colspan='2'>$type</td>";
                $s = $s . "<td style='text-align:left;font-size:$font_size_table;' colspan='2'>$key</td>";
                $s = $s . "<td style='text-align:left;font-size:$font_size_table;' colspan='2'>$upd</td>";
                $s = $s . '<td style=\'border-bottom:0.0px outset;border-top:0.0px outset\' class=\'lst\'><div class =\''.$class.'\' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'http://'.$ip.':'.$port.'/library/sections/'.$key.'/refresh?force=1\' });">'.$toggle.'</div></td>';	
                $s = $s . "</tr>"; 
                $s = $s . "<tr>"; 	
              }          
              $this->SetValue("HtmlMediathekBox", $s);
            }
          } else {
            $rc_url = -42;
            $this->LogMessage($this->ReturnMessage($rc_url), KL_ERROR);
          }
        }
    }


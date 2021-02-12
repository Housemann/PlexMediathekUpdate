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
            
            $this->RegisterPropertyInteger ("UpdateButtonColor", -1);
            $this->RegisterPropertyInteger ("UpdateButtonFontColor", -1);

            $this->RegisterPropertyInteger ("ColorHeader", -1);
            $this->RegisterPropertyInteger ("FontColorHeader", -1);
            $this->RegisterPropertyInteger ("FontSizeHeader", 16);
            $this->RegisterPropertyInteger ("ColorTable", -1);
            $this->RegisterPropertyInteger ("FontSizeTable", 14);
            $this->RegisterPropertyInteger ("FontColorTable", -1);

            $this->RegisterPropertyInteger ("BoarderColor", -1);
            $this->RegisterPropertyString ("BorderStyle", "outset");
            $this->RegisterPropertyInteger ("BorderWidth", 1);
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

          // Propertys lesen und Farbe umwandeln in HEX
          $color_header       = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("ColorHeader")));
          $font_size_header 	= $this->ReadPropertyInteger ("FontSizeHeader")."px";
          $font_color_header  = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("FontColorHeader")));

          $color_table        = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("ColorTable")));
          $font_size_table 	= $this->ReadPropertyInteger ("FontSizeTable")."px";
          $font_color_table   = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("FontColorTable")));

          $border_style       = $this->ReadPropertyString ("BorderStyle"); // dotted,dashed,solid,double,groove,ridge,inset,outset,none,hidden
          $border_width       = $this->ReadPropertyInteger ("BorderWidth")."px";
          $boarder_color      = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("BoarderColor")));

          $button_color       = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("UpdateButtonColor")));
          $button_font_color  = str_replace("0x","#",$this->IntToHex($this->ReadPropertyInteger ("UpdateButtonFontColor")));



               
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

              // Etwas CSS und HTML
              $style = "";
              $style = $style.'<style type="text/css">';
              $style = $style.'table.test { width: 100%; border-collapse: true;}';
              $style = $style.'Test { border: 2px solid #444455; }';
              $style = $style.'td.lst { width: 43px; text-align:center; padding: 5px;'." border-color: $boarder_color; border-width: $border_width; border-style: $border_style;".'}';
              $style = $style.".bc_button { padding: 5px; color: $button_font_color; background-color: $button_color; background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }";
              $style = $style.'</style>';

              $s = '';	
              $s = $s . $style;

              //Tabelle Erstellen
              $s = $s . '<table class=\'test\'>'; 

              $s = $s . '<tr>'; 
              $s = $s . "<tr>"; 

              $s = $s . "<th style='border-color: $boarder_color; color: $font_color_header; border-width: $border_width; border-style: $border_style; background: $color_header;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek")."</td>";
              $s = $s . "<th style='border-color: $boarder_color; color: $font_color_header; border-width: $border_width; border-style: $border_style; background: $color_header;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek Type")."</td>";
              $s = $s . "<th style='border-color: $boarder_color; color: $font_color_header; border-width: $border_width; border-style: $border_style; background: $color_header;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Bibliothek ID")."</td>"; 
              $s = $s . "<th style='border-color: $boarder_color; color: $font_color_header; border-width: $border_width; border-style: $border_style; background: $color_header;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Last Update")."</td>";
              $s = $s . "<th style='border-color: $boarder_color; color: $font_color_header; border-width: $border_width; border-style: $border_style; background: $color_header;font-size:$font_size_header;' colspan='2'><B>".$this->Translate("Update Mediathek")."</td>";

              $s = $s . "</tr>"; 
              $s = $s . "<tr>"; 

              for($i = 0; $i < $count_array_librarys; $i++) {
                $newArray = $array_librarys[$i];	

                $title  = $newArray['title'];
                $type   = $newArray['type'];
                $key    = $newArray['key'];
                $upd    = $newArray['scannedAt'];

                #$class  = $this->ReadPropertyString("UpdateButtonColor");
                $toggle = $this->Translate('Update');

                $s = $s . "<tr>"; 
                $s = $s . "<td style='text-align:left;border-color: $boarder_color; color: $font_color_table; border-width: $border_width; border-style: $border_style;background: $color_table; font-size:$font_size_table;' colspan='2'>$title</td>";
                $s = $s . "<td style='text-align:left;border-color: $boarder_color; color: $font_color_table; border-width: $border_width; border-style: $border_style;background: $color_table; font-size:$font_size_table;' colspan='2'>$type</td>";
                $s = $s . "<td style='text-align:left;border-color: $boarder_color; color: $font_color_table; border-width: $border_width; border-style: $border_style;background: $color_table; font-size:$font_size_table;' colspan='2'>$key</td>";
                $s = $s . "<td style='text-align:left;border-color: $boarder_color; color: $font_color_table; border-width: $border_width; border-style: $border_style;background: $color_table; font-size:$font_size_table;' colspan='2'>$upd</td>";
                $s = $s . '<td style='."border-color: $boarder_color; color: $font_color_table; border-width: $border_width;border-style: $border_style;".' outset\' class=\'lst\'><div class =\'bc_button \' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'http://'.$ip.':'.$port.'/library/sections/'.$key.'/refresh?force=1\' });">'.$toggle.'</div></td>';	
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

        private function IntToHex (int $value) {
          $HEX = sprintf('0x%06X',$value);
          return $HEX;
        }
    }


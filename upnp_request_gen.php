<?php
/*UPnP Request Generator - Parses description XML files from UPnP daemons and generates requests for all actions
Daniel Crowley
Copyright (C) 2013 Trustwave Holdings, Inc.
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.*/

function get_xml_file($url){
   $context = stream_context_create(array('http' => array('timeout' => 15)));

   $data = file_get_contents($url, false, $context);

   if(!$data){
      trigger_error('Can\'t retrieve descriptor XML file: ' . $url, E_USER_NOTICE);
      return false;
   }

   return simplexml_load_string($data);
}

if(!isset($_SERVER['argv'][1])){
	die("UPnP request generator, v0.1\n".
		"Usage: php upnp_request_gen.php <URL_TO_DESCRIPTION_XML_FILE>\n".
		"\n".
		"This tool will generate a series of directories and files corresponding\n".
		"to devices, services, and actions exposed by a UPnP daemon. This tool \n".
		"does not perform discovery of UPnP daemons. The author recommends the \n".
		"nmap NSE scripts 'broadcast-upnp-info' and 'upnp-info' for UPnP daemon\n".
		"discovery.\n".
		"\n".
		"Requests, as generated, have each variable pre-filled with the type of\n".
		"variable value expected by the UPnP endpoint. Modify generated request\n".
		"files before use, or load requests into a tool such as Burp Repeater in\n".
		"order to modify variables to useful values before exercising control\n".
		"over UPnP daemons.\n");
}

echo "Attempting to retrieve descriptor XML file...\n";
$desc_xml = get_xml_file($_SERVER['argv'][1]);

//register namespace so xpath works
$device_ns = implode($desc_xml->getDocNamespaces());
$desc_xml->registerXPathNamespace('upnp',$device_ns);

$host = explode("/",$_SERVER['argv'][1]);
$host_name = $host[2];
mkdir($host_name);

foreach($desc_xml->xpath('//upnp:device') as $device){
   $device_name = $device->deviceType;
   $device->registerXPathNamespace('upnp',$device_ns);
   echo "Starting work on UPnP device $device_name\n";
   mkdir("$host_name/$device_name");
   
   foreach($device->serviceList->service as $service){
      $service_id = $service->serviceType;
      $service_ctrl_url = ltrim($service->controlURL,"/");
      echo "Attempting to retrieve service description for $service_id\n";
      if(substr($service->SCPDURL,0,4)=='http'){
         $service_desc = get_xml_file($service->SCPDURL);
      }elseif(substr($service->SCPDURL,0,1)=='/'){
         $service_desc = get_xml_file("http://" . $host_name . $service->SCPDURL);
      }else{
         $service_desc = get_xml_file(implode('/', array_slice($host, 0,-1)) . "/" . ltrim($service->SCPDURL,"/"));
      }
      if(!$service_desc){
         echo "Couldn't retrieve description xml file for $service_id.\n";
         continue;
      }
      $service_ns = implode($service_desc->getDocNamespaces());
      $service_desc->registerXPathNamespace('upnp',$service_ns);
      mkdir("$host_name/$device_name/$service_id");
      
      echo "Generating actions for service $service_id\n";
      foreach($service_desc->xpath('//upnp:action') as $action){
         $action_name = $action->name;
         echo "$action_name\n";
         $action_body = 
            "<?xml version=\"1.0\"?>\n".
            "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" ".
            "s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">\n".
            "   <s:Body>\n".
            "      <u:$action_name xmlns:u=\"$service_id\">\n";
         foreach($action->argumentList->argument as $argument){
            $service_desc->registerXPathNamespace('upnp',$service_ns);
            if ($argument->direction == "in"){
               $action_body .= "         <".$argument->name.">";
               $state_var = $service_desc->xpath("//upnp:stateVariable[upnp:name='$argument->relatedStateVariable']");
               $action_body .= $state_var[0]->dataType;
               $action_body .= "</".$argument->name.">\n";
            }
         }
         $action_body .=
            "      </u:$action_name>\n".
            "   </s:Body>\n".
            "</s:Envelope>";
         $fh = fopen("$host_name/$device_name/$service_id/$action_name","w");
         fwrite($fh,
            "POST /$service_ctrl_url HTTP/1.1\n".
            "Host: $host_name\n".
            "SOAPAction: \"$service_id#$action_name\"\n".
            "Content-Type: text/xml; charset=\"utf-8\"\n".
            "Content-Length: " . strlen($action_body) ."\n".
            "\n".
            $action_body);
         fclose($fh);
      }
   }
}

?>

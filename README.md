UPnP Request Generator
Released at Black Hat USA 2013
Daniel Crowley <dcrowley@trustwave.com>
http://www.trustwave.com

INTRODUCTION
============

UPnP Request Generator is a tool for parsing remote XML description
files for UPnP daemons and generating requests to invoke each UPnP
action exposed by the UPnP daemon.

This tool does not perform discovery, so use your favorite tool to
discover UPnP-enabled hosts. If you do not have a favorite tool, try
the nmap NSE scripts 'broadcast-upnp-info' and 'upnp-info'. This is
an intentional decision, as some hosts expose control endpoints to
interfaces which are not reachable by multicast, which is how UPnP
discovery traditionally happens.

This tool does not make the UPnP control requests, it only generates
requests in the right format. This is intentional, since it is often
desirable to create malformed requests or to send requests quickly
in order to produce unexpected behavior from the UPnP endpoint. The
variable values set in the requests are probably not valid values as
generated, they are filled with the type of value expected for that
variable. Manually edit the generated requests before use. This tool
is designed to generate requests destined for Burp Repeater or Burp
Intruder.

Be aware that UPnP daemons are generally poorly built and may not
have properly formatted XML files (the tool is built to be very fault
tolerant but despite attempts to be foolproof they keep building
better fools). If you discover that this tool fails to parse a
particular descriptor XML file, please submit a bug and include the
relevant XML file.
 
REQUIREMENTS
============

PHP command line utility

USAGE
=====

php upnp_request_gen.php <URL_OF_DESCRIPTION_XML>

COPYRIGHT
=========

UPnP Request Generator - Parses description XML files from UPnP daemons and generates requests for all actions
Daniel Crowley
Copyright (C) 2013 Trustwave Holdings, Inc.
 
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>

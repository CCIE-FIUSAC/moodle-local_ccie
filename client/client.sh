#!/bin/bash
protocol='http'
domain='elearning.ingenieria.usac.edu.gt' # and port
token='c489b0789dc043f4de7d60ea5b637342'
ws_function_enrol='local_ccie_hello_world'
rest_format='json'
ws_url="${protocol}://${domain}/campus/webservice/xmlrpc/server.php?wstoken=${token}&wsfunction=${ws_function_enrol}&moodlewsrestformat=${rest_format}"

content='
welcomemessage= Hola, '
# echo Making post request with endpoint:
# echo $ws_url
# echo $'\n'And content
# echo $content $'\n'
curl -i "$ws_url" \
  -X POST \
  -H 'Content-Type:text/plain' \
  -d "$content"
echo

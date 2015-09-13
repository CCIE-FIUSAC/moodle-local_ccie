#!/bin/bash
protocol='http'
domain='elearning.ingenieria.usac.edu.gt' # and port
token='1aff5972b673566478eecd5a3fcd95c0'
ws_function_enrol='local_ccie_hello_world'
rest_format='json'
ws_url="${protocol}://${domain}/campus/webservice/rest/server.php?wstoken=${token}&wsfunction=${ws_function_enrol}&moodlewsrestformat=${rest_format}"

content="welcomemessage=Hola"
# echo Making post request with endpoint:
# echo $ws_url
# echo $'\n'And content
# echo $content $'\n'
curl -i "$ws_url" \
  -X POST \
  -d "$content"
echo

#!/bin/bash
protocol='http'
domain='elearning.ingenieria.usac.edu.gt' # and port
token='7b1e0ddaeeb967091ffaf49c36712d22'
ws_function_enrol='local_ccie_matricular'
rest_format='json'
ws_url="${protocol}://${domain}/campus/webservice/rest/server.php?wstoken=${token}&wsfunction=${ws_function_enrol}&moodlewsrestformat=${rest_format}"

#content='username=hola1&firstname=hola2&lastname=hola3&email=hola4%40gmail.com&roleid=5'
content='username=hola1&firstname=hola2&lastname=hola3&email=hola4%40gmail.com&roleid=5&enrolments[0][idnumber]=007A&enrolments[0][timestart]=500&enrolments[0][timeend]=600&enrolments[1][idnumber]=007B&enrolments[1][timestart]=500&enrolments[1][timeend]=600'
# echo Making post request with endpoint:
# echo $ws_url
# echo $'\n'And content
# echo $content $'\n'
curl -i "$ws_url" \
  -X POST \
  -d "$content"
echo

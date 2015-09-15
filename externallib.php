<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External Web Service for CCIE
 *
 * @package    localccie
 * @copyright  2015 Unidad de educación a distancia (https://elearning.ingenieria.usac.edu.gt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_ccie_external extends external_api {
    public static function log_action($action, $message = "", $logfile = "/var/www/html/campus/debug.log") {
        $new = file_exists($logfile) ? false : true;
        $handle = fopen($logfile, 'a');
        if ($handle) { // append
            $timestamp = strftime("%Y-%m-%d %H:%M:%S", time());
            $content = "{$timestamp} | {$action}: {$message}\n";
            fwrite($handle, $content);
            fclose($handle);
            if ($new) {
                chmod($logfile, 0755);
            }
        }
    }
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function matricular_parameters() {
        return new external_function_parameters(
                array(
                  'username' => new external_value(PARAM_TEXT, 'Carné universitario'),
                  'firstname' => new external_value(PARAM_TEXT, 'Primer y/o segundo nombre del usuario'),
                  'lastname' => new external_value(PARAM_TEXT, 'Apellido del usuario'),
                  'email' => new external_value(PARAM_TEXT, 'Correo electrónico del usuario'),
                  'roleid' => new external_value(PARAM_INT, 'Role que tiene el usuario con el curso. Valores: 3 (editingteacher), 4 (teacher), 5 (student)', VALUE_REQUIRED, VALUE_DEFAULT, 5),
                  'enrolments' => new external_multiple_structure(
                          new external_single_structure(
                                  array(
                                      'idnumber' => new external_value(PARAM_TEXT, 'Número ID de un curso en moodle'),
                                      'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                      'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL)
                                  )
                          ), 'Option names:
                                  * groupid (integer) return only users in this group id. Requires \'moodle/site:accessallgroups\' .
                                  * onlyactive (integer) only users with active enrolments. Requires \'moodle/course:enrolreview\' .
                                  * userfields (\'string, string, ...\') return only the values of these user fields.
                                  * limitfrom (integer) sql limit from.
                                  * limitnumber (integer) max number of users per course and capability.', VALUE_DEFAULT, array()

                  )
                )
        );
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function desmatricular_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }
    /**
     * Matricula el usuario a los cursos indicados, usando el rol indicado
     *
     * Function throw an exception at the first error encountered.
     * @param String $username Carne
     * @param String $firstname Primer y/o segundo nombre
     * @param String $lastname Apellidos
     * @param String $email Correo electronico @ingenieria.usac.edu.gt
     * @param Int $roleid 3 (editingteacher), 4 (teacher), 5 (student)
     * @param array $enrolments Un array de cursos a matricular. El esquema es:
     *  String $idnumber Número ID del curso
     *  Int $timestart Timestamp when the enrolment start
     *  Int $timeend Timestamp when the enrolment end
     * @since Moodle 2.8
     */
    public static function matricular($username, $firstname, $lastname, $email, $roleid = 5, $enrolments) {
      global $DB, $CFG;

      require_once($CFG->libdir . '/enrollib.php');
      // require_once($CFG->dirroot . "/user/lib.php");

      $params = self::validate_parameters(self::matricular_parameters(),
              array('username' => $username,
              'firstname' => $firstname,
              'lastname' => $lastname,
              'email' => $email,
              'roleid' => $roleid,
              'enrolments' => $enrolments
            ));

      $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
                                                           // (except if the DB doesn't support it).
      // Get the user.
      $user = $DB->get_record('user',
                    array('email' => $email, 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id));

      // Create user account
      if (empty($user)){
        
      } else {

      }
      return array("username"=>$params['username'], 'enrolments'=>array(array("courseid"=>$params['enrolments'][0]['idnumber'], "status"=>1), array("courseid"=>$params['enrolments'][1]['idnumber'], "status"=>2)));
    }
    public static function desmatricular($welcomemessage = 'Hello world, ') {
      return "hola";
    }
    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello world, ') {
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::hello_world_parameters(),
                array('welcomemessage' => $welcomemessage));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
        static::log_action("welcomemessage",$params['welcomemessage']);
        return $params['welcomemessage'] . $USER->firstname ;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function matricular_returns() {
      return new external_single_structure(
              array(
                  'username' => new external_value(PARAM_TEXT, 'Carné universitario del estudiante'),
                  'enrolments' => new external_multiple_structure(
                      new external_single_structure(
                          array(
                            'courseid' => new external_value(PARAM_TEXT, 'Número ID de un curso en moodle'),
                            'status' => new external_value(PARAM_INT, 'Estado del estudiante dentro del curso: 0 (activo) ó 1 (suspendido)')
                          )
                      )
                  )
              )
          );
      return new external_multiple_structure(
          new external_single_structure(
              array(
                  'username' => new external_value(PARAM_TEXT, 'Carné universitario del estudiante'),
                  'courseid' => new external_value(PARAM_INT, 'Número ID de un curso en moodle'),
                  'status' => new external_value(PARAM_INT, 'Estado del estudiante dentro del curso: 0 (activo) ó 1 (suspendido)'),
              )
          )
      );
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function desmatricular_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }



}

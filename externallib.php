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

    protected static function get_enrolperiod(){
      $today = time();
      $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
      if ($today<=strtotime('June 15')){
        // 1 semestre -  31 julio
        // 1 vacaciones - 31 julio
        $timeend=strtotime("July 31");
      } else if ($today>=strtotime('June 16') and $today<=strtotime('December 15')){
        // 2 semestre - 31 Enero próximo año
        // 2 vacaciones - 31 enero próximo año
        $january31=strtotime("January 31");
        $timeend=strtotime("+1 year", $january31);
      } else {
        // December 16 and December 31
        // 1 semestre -  31 julio próximo año
        // 1 vacaciones - 31 julio próximo año
        $july31=strtotime("July 31");
        $timeend=strtotime("+1 year", $july31);
      }
      return array('timestart'=>$today, 'timeend'=>$timeend);
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
                  'idnumbers' => new external_multiple_structure(
                          new external_value(PARAM_RAW, 'Número ID del curso', VALUE_REQUIRED)
                  ),
                  'password' => new external_value(PARAM_TEXT, 'Contraseña del usuario', VALUE_DEFAULT, ''),
                )
        );
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function desmatricular_parameters() {
        return new external_function_parameters(
                array('username' => new external_value(PARAM_TEXT, 'Carné universitario'),
                  'idnumbers' => new external_multiple_structure(
                          new external_value(PARAM_RAW, 'Número ID del curso')
                  , 'Array donde cada elemento es un ID Number que representa el curso en moodle', VALUE_OPTIONAL)
                )
        );
    }
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_cursos_parameters() {
        return new external_function_parameters(
                array(
                  'username' => new external_value(PARAM_TEXT, 'Carné universitario', VALUE_OPTIONAL)
                )
        );
    }
    /**
     * Returns SSO url for external login
     *
     * @return external_function_parameters
     * @since Moodle 2.8
     */
    public static function get_authurl_parameters() {
        return new external_function_parameters(
                array()
        );
    }
    /**
     * Returns SSO url for external login
     *
     * @return external_function_parameters
     * @since Moodle 2.8
     */
    public static function set_password_parameters() {
        return new external_function_parameters(
                array(
                  'username' => new external_value(PARAM_USERNAME, 'Carné universitario'),
                  'password' => new external_value(PARAM_RAW, 'Contraseña del usuario'),
                )
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
     * @param array $idnumbers Un array de número ID del curso, de cursos a matricular.
     * @param String $password Contraseña del usuario
     * @since Moodle 2.8
     */
    public static function matricular($username, $firstname, $lastname, $email, $roleid = 5, $idnumbers, $password) {
      global $DB, $CFG;

      require_once($CFG->libdir . '/enrollib.php');
      require_once($CFG->dirroot . '/user/lib.php');

      $params = self::validate_parameters(self::matricular_parameters(),
              array('username' => $username,
              'firstname' => $firstname,
              'lastname' => $lastname,
              'email' => $email,
              'roleid' => $roleid,
              'idnumbers' => $idnumbers,
              'password' => $password
            ));

      // Ensure the current user is allowed to run this function.
      $context = context_system::instance();
      self::validate_context($context);
      require_capability('moodle/user:create', $context);
      $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
                                                           // (except if the DB doesn't support it).
      // Get the user.
      $user = $DB->get_record('user',
                    array('username' => $params['username'], 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id), 'id');

      if (empty($user)){
        // Make sure that the username doesn't already exist.
        if ($DB->record_exists('user', array('email' => $params['email'], 'mnethostid' => $CFG->mnet_localhost_id))) {
            return array('statusCode'=>500, 'message'=>"EMAIL ${params['email']} YA EXISTE", 'username'=>$params['username'], 'enrolments'=>array());
        }
        // Create user account
        $newuser = new stdClass();
        $newuser->username = $params['username'];
        $newuser->email = $params['email'];
        $newuser->firstname = $params['firstname'];
        $newuser->lastname = $params['lastname'];
        $emptyPassword = empty($params['password']);
        // Es permitido la asignación de una contraseña vacía ''
        $newuser->password = $params['password'];
        // Si tiene contraseña vacía, asignarle 'googleoauth2' como medio de autentitación
        $newuser->auth = $emptyPassword?'googleoauth2':'manual';
        $newuser->confirmed = true;
        $newuser->mnethostid = $CFG->mnet_localhost_id;
        // 2nd param is false, when password is ''
        $newuser->id = user_create_user($newuser, !$emptyPassword, true);
        $user = $newuser;
      }
      $username = $params['username'];

      // Retrieve the manual enrolment plugin.
      $enrol = enrol_get_plugin('manual');
      if (empty($enrol)) {
        return array('statusCode'=>500, 'message'=>'MOODLE NO PUEDE MATRICULAR POR FALTA DE PLUGIN "manual"', 'username'=>$username, 'enrolments'=>array());
      }

      $roleid = $params['roleid'];
      $times = static::get_enrolperiod();
      $enrolments = array();
      $statusCode = 200;
      foreach ($params['idnumbers'] as $idnumber) {
        // Ensure the current user is allowed to run this function in the enrolment context.
        try{
          $course = $DB->get_record('course', array('idnumber'=>$idnumber), 'id, fullname', MUST_EXIST);
        } catch (dml_exception $e) {
          $enrolments[] = array('statusCode' => 600, 'message'=>"NO EXISTE EL CURSO ${idnumber}", 'courseid'=>$idnumber);
          $statusCode = 600;
          continue;
        }

        $coursefullname = $course->fullname;
        $courseid = $course->id;
        $context = context_course::instance($courseid, IGNORE_MISSING);
        self::validate_context($context);

        // Check that the user has the permission to manual enrol.
        require_capability('enrol/manual:enrol', $context);

        // Throw an exception if user is not able to assign the role.
        $roles = get_assignable_roles($context);

        if (!array_key_exists($roleid, $roles)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE MATRICULARSE EN EL CURSO ${coursefullname} CON ROLE ${roleid}", 'courseid'=>$idnumber);
            $statusCode = 600;
            continue;
        }

        // Check manual enrolment plugin instance is enabled/exist.
        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, true);
        foreach ($enrolinstances as $courseenrolinstance) {
          if ($courseenrolinstance->enrol == "manual") {
              $instance = $courseenrolinstance;
              break;
          }
        }
        if (empty($instance)) {
          $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE MATRICULARSE EN EL CURSO ${coursefullname} POR ESTAR DESHABILITADO.", 'courseid'=>$idnumber);
          $statusCode = 600;
          continue;
        }

        // Check that the plugin accept enrolment
        if (!$enrol->allow_enrol($instance)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE MATRICULARSE EN EL CURSO ${coursefullname} CON ROLE ${roleid}. PLUGIN NO PERMITE MATRICULACIÓN", 'courseid'=>$idnumber);
            $statusCode = 600;
            continue;
        }

        // check if user is participating in the course
        $enrolid = $instance->id;
        $user_enrolments = $DB->get_record('user_enrolments', array('userid'=>$user->id, 'enrolid'=>$enrolid ), 'id, status');
        if (!empty($user_enrolments)){
          if ($user_enrolments->status == ENROL_USER_ACTIVE){
            $enrolments[] = array('statusCode' => 200, 'message'=>"USUARIO ${username} ACTIVO EN ${idnumber} CON EXITO", 'courseid'=>$idnumber);
          } else if ($user_enrolments->status == ENROL_USER_SUSPENDED){
            $enrol->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE);
            $enrolments[] = array('statusCode' => 200, 'message'=>"USUARIO ${username} ACTIVO EN ${idnumber} CON EXITO", 'courseid'=>$idnumber);
          }
          continue;
        }
        // Finally proceed the enrolment.
        $enrol->enrol_user($instance, $user->id, $roleid,
                $times['timestart'], $times['timeend'], ENROL_USER_ACTIVE);

        $enrolments[] = array('statusCode' => 200, 'message'=>"USUARIO ${username} MATRICULADO EN ${idnumber} CON EXITO", 'courseid'=>$idnumber);
      }
      if ($statusCode == 200){
        $transaction->allow_commit();
        return array('statusCode'=>$statusCode, 'message'=>'MATRICULACION EXITOSA', 'username'=>$username, 'enrolments'=>$enrolments);
      }
      return array('statusCode'=>$statusCode, 'message'=>'MATRICULACION SIN EXITO', 'username'=>$username, 'enrolments'=>$enrolments);
    }
    public static function desmatricular($username, $idnumbers = array()) {
      global $DB, $CFG;

      $params = self::validate_parameters(self::desmatricular_parameters(),
              array('username' => $username, 'idnumbers' => $idnumbers));
      $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
                                                           // (except if the DB doesn't support it).
      // Get the user.
      $user = $DB->get_record('user',
                    array('username' => $params['username'], 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id), 'id');

      if (empty($user)){
        return array('statusCode'=>500, 'message'=>"USUARIO ${params['username']} NO EXISTE", 'username'=>$params['username']);
      }
      $username = $params['username'];
      // Retrieve the manual enrolment plugin.
      $enrol = enrol_get_plugin('manual');
      if (empty($enrol)) {
        return array('statusCode'=>500, 'message'=>'MOODLE NO PUEDE MATRICULAR POR FALTA DE PLUGIN "manual"', 'username'=>$username);
      }
      $enrolments = array();
      $statusCode = 200;
      if (empty($params['idnumbers'])){
        $user_enrolments = $DB->get_recordset('user_enrolments',
                      array('userid' => $user->id, 'status'=>ENROL_USER_ACTIVE),'', 'enrolid');

        foreach ($user_enrolments as $user_enrolment){
          // find courseid using enrolid
          $course = $DB->get_record('enrol', array('id'=>$user_enrolment->enrolid), 'courseid', MUST_EXIST);
          $courseid = $course->courseid;
          $course = $DB->get_record('course', array('id'=>$courseid), 'fullname, idnumber', MUST_EXIST);
          $coursefullname = $course->fullname;
          $courseidnumber = $course->idnumber;
          // Check manual enrolment plugin instance is enabled/exist.
          $instance = null;
          $enrolinstances = enrol_get_instances($courseid, true);
          foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
              $instance = $courseenrolinstance;
              break;
            }
          }
          if (empty($instance)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE DESMATRICULARSE EN EL CURSO ${coursefullname} POR ESTAR DESHABILITADO.", 'courseid'=>$courseidnumber);
            $statusCode = 600;
            continue;
          }
          // Check that the plugin accept enrolment
          if (!$enrol->allow_enrol($instance)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE DESMATRICULARSE EN EL CURSO ${coursefullname} POR ESTAR DESHABILITADO", 'courseid'=>$courseidnumber);
            $statusCode = 600;
            continue;
          }

          $enrol->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
          $enrolments[] = array('statusCode' => 200, 'message'=>"USUARIO ${username} SUSPENDIDO EN ${courseidnumber} CON EXITO", 'courseid'=>$courseidnumber);
        }
      } else {
        $idnumbers = $params['idnumbers'];
        foreach($idnumbers as $idnumber){
          try{
            $course = $DB->get_record('course', array('idnumber'=>$idnumber), 'id, fullname', MUST_EXIST);
          } catch (dml_exception $e) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"NO EXISTE EL CURSO ${idnumber}", 'courseid'=>$idnumber);
            $statusCode = 600;
            continue;
          }
          $coursefullname = $course->fullname;
          // Check manual enrolment plugin instance is enabled/exist.
          $instance = null;
          $enrolinstances = enrol_get_instances($course->id, true);
          foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
              $instance = $courseenrolinstance;
              break;
            }
          }
          if (empty($instance)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE DESMATRICULARSE EN EL CURSO ${coursefullname} POR ESTAR DESHABILITADO.", 'courseid'=>$idnumber);
            $statusCode = 600;
            continue;
          }
          // Check that the plugin accept enrolment
          if (!$enrol->allow_enrol($instance)) {
            $enrolments[] = array('statusCode' => 600, 'message'=>"EL USUARIO ${username} NO PUEDE DESMATRICULARSE EN EL CURSO ${coursefullname}", 'courseid'=>$idnumber);
            $statusCode = 600;
            continue;
          }
          $enrol->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
          $enrolments[] = array('statusCode' => 200, 'message'=>"USUARIO ${username} SUSPENDIDO EN ${idnumber} CON EXITO", 'courseid'=>$idnumber);
        }
      }
      if ($statusCode == 200){
        $transaction->allow_commit();
        return array('statusCode'=>$statusCode, 'message'=>'DESMATRICULACION EXITOSA', 'username'=>$username, 'enrolments'=>$enrolments);
      }
      return array('statusCode'=>$statusCode, 'message'=>'DESMATRICULACION SIN EXITO', 'username'=>$username, 'enrolments'=>$enrolments);
    }
    public static function get_cursos($username){
      global $CFG, $DB;
      require_once($CFG->dirroot . '/course/lib.php');
      require_once($CFG->libdir . '/enrollib.php');

      $params = self::validate_parameters(self::get_cursos_parameters(),
              array('username' => $username));

      if (empty($params['username'])){
        $userid = null;
      } else {
        // buscar el usuario con username
        $user = $DB->get_record('user',
                      array('username' => $params['username'], 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id), 'id');

        if (empty($user)){
          // usuario no registrado
          $userid = null;
        } else {
          // usuario encontrado
          $userid = $user->id;
        }
      }

      $courses = $DB->get_recordset_select('course',
                    'visible=? and id>?', array(1,1),'fullname ASC', 'id, fullname, shortname, idnumber, format');
      //create return value
      $coursesinfo = array();
      foreach ($courses as $course) {

          // now security checks
          $context = context_course::instance($course->id, IGNORE_MISSING);
          $courseformatoptions = course_get_format($course)->get_format_options();
          try {
              self::validate_context($context);
          } catch (Exception $e) {
              $exceptionparam = new stdClass();
              $exceptionparam->message = $e->getMessage();
              $exceptionparam->courseid = $course->id;
              throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
          }
          require_capability('moodle/course:view', $context);

          $courseinfo = array();
          $courseinfo['fullname'] = $course->fullname;
          $courseinfo['shortname'] = $course->shortname;

          //some field should be returned only if the user has update permission
          $courseadmin = has_capability('moodle/course:update', $context);
          if ($courseadmin) {
              $courseinfo['idnumber'] = $course->idnumber;
          }
          if (empty($userid)){
            $courseinfo['matriculado'] = ENROL_USER_SUSPENDED;
          } else {
            // Buscar el id de la modalidad de matriculacion del curso $course->id
            $enrol = $DB->get_record('enrol',
                          array('status' => ENROL_INSTANCE_ENABLED, 'courseid' => $course->id, 'enrol'=>'manual'), 'id');
            // Buscar el usuario matriculado con status ACTIVE
            $matriculado = $DB->record_exists('user_enrolments',
                          array('userid' => $userid, 'enrolid'=>$enrol->id, 'status'=>ENROL_USER_ACTIVE));
            if ($matriculado){
              // Usuario no esta matriculado, porque no esta en la tabla user_enrolments o tiene status SUSPENDED
              $courseinfo['matriculado'] = ENROL_USER_ACTIVE;
            } else {
              $courseinfo['matriculado'] = ENROL_USER_SUSPENDED;
            }
          }

          if ($courseadmin or $course->visible
                  or has_capability('moodle/course:viewhiddencourses', $context)) {
              $coursesinfo[] = $courseinfo;
          }
      }

      return array('cursos'=>$coursesinfo);
    }
    /**
     * Returns SSO url for external login
     * 'auth_googleoauth2' plugin with OpenAM support must be installed and configured!
     * @return string SSO url
     */
    public static function get_authurl(){
      global $CFG;
      require_once($CFG->dirroot . '/auth/googleoauth2/classes/provider/openam.php');
      $provider = new provideroauth2openam();
      // prepare state hash
      $today = strtotime('00:00:00');
      $authurl = $provider->getAuthorizationUrl(array(
        'state'=>md5($today.$provider->statesalt)
        )
      );
      return array('authurl' => $authurl);
    }
    /**
     * Cambia la contraseña de un estudiante.
     *
     * Function throw an exception at the first error encountered.
     * @param String $username Carne
     * @param String $password Contraseña del usuario
     * @since Moodle 2.9
     */
    public static function set_password($username, $password) {
      global $DB, $CFG;

      require_once($CFG->dirroot . '/user/lib.php');

      $params = self::validate_parameters(self::set_password_parameters(),
              array('username' => $username,
              'password' => $password
            ));

      // Ensure the current user is allowed to run this function.
      $context = context_system::instance();
      self::validate_context($context);
      require_capability('moodle/user:update', $context);
      $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
                                                           // (except if the DB doesn't support it).
      // Get the user.
      $user = $DB->get_record('user',
                    array('username' => $params['username'], 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id), 'id');

      if (empty($user)){
        return array('statusCode'=>500, 'message'=>"No existe el usuario ${params['username']}");
      }
      // continuar flujo normal
      $user->password = $params['password'];
      user_update_user($user);
      $transaction->allow_commit();
      return array('statusCode'=>200, 'message'=>"Se ha modificado la contraseña del usuario ${params['username']}");
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
        return $params['welcomemessage'] . $USER->firstname ;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function matricular_returns() {
      return new external_single_structure(
              array(
                  'statusCode' => new external_value(PARAM_TEXT, '200 (Exito) o 500 (fracaso)'),
                  'message' => new external_value(PARAM_TEXT, 'Breve descripción del resultado'),
                  'username' => new external_value(PARAM_TEXT, 'Carné universitario del estudiante'),
                  'enrolments' => new external_multiple_structure(
                      new external_single_structure(
                          array(
                            'statusCode' => new external_value(PARAM_INT, 'Estado de la matriculación: 200 (exito), 600 (error, revisar message)'),
                            'message' => new external_value(PARAM_TEXT, 'Descripción del resultado de matriculación'),
                            'courseid' => new external_value(PARAM_TEXT, 'Número ID de un curso en moodle')
                          )
                      )
                  )
              )
          );
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function desmatricular_returns() {
        return new external_single_structure(
                array(
                    'statusCode' => new external_value(PARAM_TEXT, '200 (Exito) o 500 (fracaso)'),
                    'message' => new external_value(PARAM_TEXT, 'Breve descripción del resultado'),
                    'username' => new external_value(PARAM_TEXT, 'Carné universitario del estudiante'),
                    'enrolments' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                              'statusCode' => new external_value(PARAM_INT, 'Estado de la desmatriculación: 200 (exito), 600 (error, revisar message)'),
                              'message' => new external_value(PARAM_TEXT, 'Descripción del resultado de desmatriculación'),
                              'courseid' => new external_value(PARAM_TEXT, 'Número ID de un curso en moodle')
                            )
                        )
                    )
                )
            );
    }
    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_cursos_returns() {
      return new external_single_structure(
              array(
                  'cursos' => new external_multiple_structure(
                    new external_single_structure(
                            array(
                                'fullname' => new external_value(PARAM_TEXT, 'full name'),
                                'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                                'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                                'matriculado' => new external_value(PARAM_INT, 'Si se ha especificado el usuario, el valor es 0 (si esta matriculado), 1 (si no participa, no existe el usuario, o no enviaste el username)', VALUE_OPTIONAL),
                            ), 'curso'
                    )
                  )
              )
          );
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_authurl_returns() {
      return new external_single_structure(array('authurl' => new external_value(PARAM_TEXT, 'SSO URL for external login')));
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function set_password_returns() {
       return new external_single_structure(
               array(
                   'statusCode' => new external_value(PARAM_TEXT, '200 (Exito) o 500 (fracaso)'),
                   'message' => new external_value(PARAM_TEXT, 'Breve descripción del resultado'),
               )
           );
     }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }



}

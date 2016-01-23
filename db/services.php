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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localccie
 * @copyright  2015 David Yzaguirre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_ccie_hello_world' => array(
                'classname'   => 'local_ccie_external',
                'methodname'  => 'hello_world',
                'classpath'   => 'local/ccie/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read'
        ),
        'local_ccie_matricular' => array(
                'classname'   => 'local_ccie_external',
                'methodname'  => 'matricular',
                'classpath'   => 'local/ccie/externallib.php',
                'description' => 'CCIE matricular usuarios',
                'capabilities'=> 'moodle/user:create, enrol/manual:enrol',
                'type'        => 'write'
        ),
        'local_ccie_desmatricular' => array(
                'classname'   => 'local_ccie_external',
                'methodname'  => 'desmatricular',
                'classpath'   => 'local/ccie/externallib.php',
                'description' => 'CCIE desmatricular usuarios',
                'type'        => 'write'
        ),
        'local_ccie_get_cursos' => array(
            'classname'   => 'local_ccie_external',
            'methodname'  => 'get_cursos',
            'classpath'   => 'local/ccie/externallib.php',
            'description' => 'Return course details',
            'type'        => 'read',
            'capabilities'=> 'moodle/course:view,moodle/course:update,moodle/course:viewhiddencourses'
        ),
        'local_ccie_get_authurl' => array(
            'classname'   => 'local_ccie_external',
            'methodname'  => 'get_authurl',
            'classpath'   => 'local/ccie/externallib.php',
            'description' => 'Return SSO url for external login',
            'type'        => 'read'
        ),
        'local_ccie_set_password' => array(
            'classname'   => 'local_ccie_external',
            'methodname'  => 'set_password',
            'classpath'   => 'local/ccie/externallib.php',
            'description' => 'Change users password',
            'capabilities'=> 'moodle/user:update',
            'type'        => 'write'
        ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Web Service CCIE' => array(
                'functions' => array ('local_ccie_hello_world', 'local_ccie_matricular', 'local_ccie_desmatricular', 'local_ccie_get_cursos', 'local_ccie_get_authurl', 'local_ccie_set_password'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);

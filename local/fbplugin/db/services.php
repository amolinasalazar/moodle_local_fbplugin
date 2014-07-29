<?php

/**
 * Web service feedback local plugin for external functions and service definitions.
 *
 * @copyright  2014 Alejandro Molina (amolinasalazar@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_fbplugin_get_feedback_questions' => array(
                'classname'   => 'local_fbplugin_external',
                'methodname'  => 'get_feedback_questions',
                'classpath'   => 'local/fbplugin/externallib.php',
                'description' => 'Return all the questions of the feedback.',
                'type'        => 'read'
        ),
		
		'local_fbplugin_get_feedbacks_by_courses' => array(
                'classname'   => 'local_fbplugin_external',
                'methodname'  => 'get_feedbacks_by_courses',
                'classpath'   => 'local/fbplugin/externallib.php',
                'description' => 'Return all the feedbacks of the courses.',
                'type'        => 'read'
        ),
		
		'local_fbplugin_complete_feedback' => array(
                'classname'   => 'local_fbplugin_external',
                'methodname'  => 'complete_feedback',
                'classpath'   => 'local/fbplugin/externallib.php',
                'description' => 'Complete a feedback using the item values.',
                'type'        => 'write'
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'My service 2' => array(
                'functions' => array ('local_fbplugin_get_feedback_questions','local_fbplugin_get_feedbacks_by_courses','local_fbplugin_complete_feedback'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);

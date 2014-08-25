<?php

/**
 * Web service feedback local plugin for external functions and service definitions.
 *
 * @copyright  Universidad de Granada. Granada – 2014 
 * @author     Alejandro Molina (amolinasalazar@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Web Services functions definitions.
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

// The pre-build services to install.
$services = array(
        'Service for fbplugin' => array(
                'functions' => array ('local_fbplugin_get_feedback_questions','local_fbplugin_get_feedbacks_by_courses','local_fbplugin_complete_feedback', 'core_webservice_get_site_info', 'core_enrol_get_users_courses'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);

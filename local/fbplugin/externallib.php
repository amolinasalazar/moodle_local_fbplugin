<?php

/**
 *
 * @copyright  2014 Alejandro Molina (amolinasalazar@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_fbplugin_external extends external_api {

	/**
     * Describes the parameters for get_feedbacks_by_courses.
     * @return external_function_parameters
     */
    public static function get_feedbacks_by_courses_parameters() {
		return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course ID',
                        '', VALUE_REQUIRED, '', NULL_NOT_ALLOWED), 'Array of Course IDs', VALUE_DEFAULT, array()),
            )
        );
    }
	
	/**
     * Returns a list of feedbacks in a provided list of courses,
     * if no list is provided all feedbacks that the user can view
     * will be returned.
     *
     * @param array $courseids the course ids
     * @return array the feedback details
     */
	 public static function get_feedbacks_by_courses($courseids = array()) {

        global $DB, $CFG;
		
		require_once($CFG->dirroot . "/mod/feedback/lib.php");

        //Parameter validation
        $params = self::validate_parameters(self::get_feedbacks_by_courses_parameters(), array('courseids' => $courseids));
		
		if (empty($params['courseids'])) {
            // Get all the courses the user can view.
            $courseids = array_keys(enrol_get_my_courses());
        } else {
            $courseids = $params['courseids'];
        }
		
		// Array to store the feedbacks to return.
        $arrfeedback = array();
		
		// Ensure there are courseids to loop through.
        if (!empty($courseids)) {
            // Go through the courseids and return the forums.
            foreach ($courseids as $cid) {
                // Get the course context.
                $context = context_course::instance($cid);
                // Check the user can function in this context.
                self::validate_context($context);
                // Get the forums in this course.
                if ($feedbacks = $DB->get_records('feedback', array('course' => $cid))) {
                    // Get the modinfo for the course.
                    $modinfo = get_fast_modinfo($cid);
                    // Get the feedback instances.
                    $feedbackinstances = $modinfo->get_instances_of('feedback');
                    // Loop through the feedbacks returned by modinfo.
                    foreach ($feedbackinstances as $feedbackid => $cm) {
                        // If it is not visible or present in the feedbacks get_records call, continue.
                        if (!$cm->uservisible || !isset($feedbacks[$feedbackid])) {
                            continue;
                        }
                        // Set the feedback object.
                        $feedback = $feedbacks[$feedbackid];
                        // Get the module context.
                        $context = context_module::instance($cm->id);
                        // Check they have the view feedback capability.
                        require_capability('mod/feedback:view', $context);
                        // Format the intro before being returning using the format setting.
                        list($feedback->intro, $feedback->introformat) = external_format_text($feedback->intro, $feedback->introformat, $context->id, 'mod_feedback', 'intro', 0);
                        // Add the course module id to the object, this information is useful.
                        $feedback->cmid = $cm->id;
                        // Add the forum to the array to return.
                        $arrfeedbacks[$feedback->id] = (array) $feedback;
                    }
                }
            }
        }		
		return $arrfeedbacks;
    }

	/**
     * Describes the get_feedback return value.
     *
     * @return external_single_structure
     */
     public static function get_feedbacks_by_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Feedback id'),
                    'course' => new external_value(PARAM_TEXT, 'Course id'),
					'name' => new external_value(PARAM_TEXT, 'Feedback name'),
                    'intro' => new external_value(PARAM_RAW, 'The feedback intro'),
                    'introformat' => new external_format_value('intro'),
                    'anonymous' => new external_value(PARAM_INT, 'Anonymous submit'),
					'email_notification' => new external_value(PARAM_INT, 'Email notification status'),
					'multiple_submit' => new external_value(PARAM_INT, 'Multiple submit status'),
					'site_after_submit' => new external_value(PARAM_TEXT, 'Site after submit'),
					'page_after_submit' => new external_value(PARAM_TEXT, 'Page after submit'),
					'page_after_submitformat' => new external_value(PARAM_INT, 'Page after submit format'),
					'publish_stats' => new external_value(PARAM_INT, 'Publish stats'),
					'timeopen' => new external_value(PARAM_INT, 'Time to open'),
					'timeclose' => new external_value(PARAM_INT, 'Time to close'),
					'timemodified' => new external_value(PARAM_INT, 'Time open'),
					'completionsubmit' => new external_value(PARAM_INT, 'Completion submit'),
                    'cmid' => new external_value(PARAM_INT, 'Course module id')
                ), 'feedback'
            )
        );
    }
	
    /**
     * Describes the parameters for get_feedback_questions.
     * @return external_function_parameters
     */
    public static function get_feedback_questions_parameters() {
        return new external_function_parameters(
                array('feedbackid' => new external_value(PARAM_INT, 'feedback ID'))
        );
    }

    /**
     * Returns a list of all the questions of a feedback
	 * @param int $feedbackid feedback id
     * @return array list of questions
     */
    public static function get_feedback_questions($feedbackid) {

        global $DB, $USER, $CFG;
		
		require_once($CFG->dirroot . "/mod/feedback/lib.php");

        //Parameter validation
        $params = self::validate_parameters(self::get_feedback_questions_parameters(), array('feedbackid' => $feedbackid));
			
		// Retrieve the feedback
		$feedback = $DB->get_record('feedback', array('id' => $feedbackid), '*', MUST_EXIST);
		
		//Course context validation
		$coursecontext = context_course::instance($feedback->course, IGNORE_MISSING);

		try {
			self::validate_context($coursecontext);
		} catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
		}
		
		// Create return value 
        $modinfo = get_fast_modinfo($feedback->course);
		$cm = $modinfo->get_instances_of('feedback'); //Course module
		
		// Check if this feedback does not exist in the modinfo array, should always be false unless DB is borked.
        if (empty($cm[$feedback->id])) {
			throw new moodle_exception('invalidmodule', 'error');
        }

        // If the feedback is not visible throw an exception.
        if (!$cm[$feedback->id]->uservisible) {
            throw new moodle_exception('nopermissiontoshow', 'error');
        }
		
		// Get the module context.
        $modcontext = context_module::instance($cm[$feedback->id]->id);
		
		// Check they have the view feedback capability.
        require_capability('mod/feedback:view', $modcontext);
		
		// Check if the feedback is open (timeopen, timeclose)
		$checktime = time();
		$feedback_is_closed = ($feedback->timeopen > $checktime) OR
                      ($feedback->timeclose < $checktime AND
                            $feedback->timeclose > 0);
		if($feedback_is_closed){
            throw new moodle_exception('feedback_is_not_open', 'feedback');
        }
		
		// Check if the feedback is already submitted and is not possible to submit it again.
		if ($feedback->multiple_submit == 0 ) {
			if (feedback_is_already_submitted($feedback->id, $courseid)) { // Testear: al eliminar este if, la funcion devuelve error cuando el usuario nunca ha completado la encuesta.
				$select = 'feedback = ? AND userid = ?';
				$params_select = array($feedback->id, $USER->id);
				$completedid = $DB->get_records_select('feedback_completed', $select, $params_select, 'id', MUST_EXIST);
				if(empty($completedid)){
					continue;
				}
				else{				
					throw new moodle_exception('this_feedback_is_already_submitted', 'feedback');
				}
			}
		}
		
		// Array to store the feedback questions to return.
        $arritems = array();
		
		if($items = $DB->get_records('feedback_item', array('feedback' => $params['feedbackid']))){
			foreach($items as $item){
			
				// Create object to return.
				$return = new stdClass();
				$return->id = (int) $item->id;
				$return->template = $item->template;
				$return->name = $item->name;
				$return->label = $item->label;
				$return->presentation = $item->presentation;
				$return->typ = $item->typ;
				$return->hasvalue = $item->hasvalue;
				$return->position = $item->position;
				$return->required = $item->required;
				$return->dependitem = $item->dependitem;
				$return->dependvalue = $item->dependvalue;
				$return->options = $item->options;
				
				// Add the question statistics to the array to return.
                $arritems[$return->position] = (array) $return;
			}
		}
		return $arritems;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_feedback_questions_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Feedback id'),
                    'template' => new external_value(PARAM_INT, 'Template id'),
					'name' => new external_value(PARAM_TEXT, 'Name'),
                    'label' => new external_value(PARAM_TEXT, 'Label'),
                    'presentation' => new external_value(PARAM_RAW, 'Presentation'),
                    'typ' => new external_value(PARAM_TEXT, 'Type'),
                    'hasvalue' => new external_value(PARAM_INT, 'Has value'),
                    'position' => new external_value(PARAM_INT, 'Position'),
                    'required' => new external_value(PARAM_INT, 'Required'),
                    'dependitem' => new external_value(PARAM_INT, 'Depend item'),
                    'dependvalue' => new external_value(PARAM_TEXT, 'Depend value'),
                    'options' => new external_value(PARAM_TEXT, 'Options'), 
					'items'
                )
            )
        );
    }
	
	// El formato de entrada esta pensado para recibir mas de un feedback, pero de momento solo contemplaremos uno
	/**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function complete_feedback_parameters() {
		return new external_function_parameters(
            array(
                'feedbackid' => new external_value(PARAM_INT, 'feedback ID'),
                'itemvalues' => new external_multiple_structure(
					new external_single_structure(
						array(
							'itemid' => new external_value(PARAM_INT, 'item ID'),
							'value' => new external_value(PARAM_TEXT, 'item value'),
							'typ' => new external_value(PARAM_TEXT, 'item type')),
						'values of the feedback items', VALUE_OPTIONAL)
					)
				
			)
		);
    }

	
	// Hacemos uso de 3 funciones internas del modulo (lib.php): feedback_create_values, feedback_save_values, feedback_update_values
	// repetimos el codigo, cambiando el hecho de recoger los valores en vez desde la web, desde los parametros de la funcion

	/**
     * Complete a feedback with a sequence of item values
	 * @param array $feedback_values all the values of the items
     * @return array
     */
    public static function complete_feedback($feedbackid, $itemvalues) {

        global $DB, $USER, $CFG;
		$usrid = $USER->id;
		
		require_once($CFG->dirroot . "/mod/feedback/lib.php");

        //Parameter validation
		$params = self::validate_parameters(self::complete_feedback_parameters(), array('feedbackid' => $feedbackid, 'itemvalues' => $itemvalues));
        // $params[feedbackid];
	
		//$params = self::validate_parameters(self::get_feedback_questions_parameters(), array('feedbackid' => $feedback_values['feedback_values']['feedbackid']));
			
		// Retrieve the feedback
		$feedback = $DB->get_record('feedback', array('id' => $params['feedbackid']), '*', MUST_EXIST);
		
		//Course context validation
		$coursecontext = context_course::instance($feedback->course, IGNORE_MISSING);

		try {
			self::validate_context($coursecontext);
		} catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
		}
		
		// Create return value 
        $modinfo = get_fast_modinfo($feedback->course);
		$cm = $modinfo->get_instances_of('feedback'); //Course module
		
		// Check if this feedback does not exist in the modinfo array, should always be false unless DB is borked.
        if (empty($cm[$feedback->id])) {
			throw new moodle_exception('invalidmodule', 'error');
        }

        // If the feedback is not visible throw an exception.
        if (!$cm[$feedback->id]->uservisible) {
            throw new moodle_exception('nopermissiontoshow', 'error');
        }
		
		// Get the module context.
        $modcontext = context_module::instance($cm[$feedback->id]->id);
		
		// Check they have the complete feedback capability.
        require_capability('mod/feedback:complete', $modcontext);
		
		// Check if the feedback is open (timeopen, timeclose)
		$checktime = time();
		$feedback_is_closed = ($feedback->timeopen > $checktime) OR
                      ($feedback->timeclose < $checktime AND
                            $feedback->timeclose > 0);
		if($feedback_is_closed){
            throw new moodle_exception('feedback_is_not_open', 'feedback');
        }
		/*
		// Check if the feedback is already submitted and is not possible to submit it again.
		if ($feedback->multiple_submit == 0 ) {
			if (feedback_is_already_submitted($feedback->id, $courseid)) {
				throw new moodle_exception('this_feedback_is_already_submitted', 'feedback');
			}
		}*/
		
		// construir el objeto para pasarselo a la funcion
		
		//$completedfeedback = new stdClass();
		
		//$completedfeedback = feedback_get_current_completed($feedback->id, false, $feedback->course);
		
		//feedback_set_tmp_values($completedfeedback);
		
		//feedback_save_tmp_values(objetocompletedfeedback,false,userid);
		
		/*if($DB->get_record('feedback_complete', array('id' => $feedback->id), '*', MUST_EXIST);){
			
		}
		*/
		// En el caso de que YA se hubiera completado el feedback, buscaremos el ID del record completado..
		$select = 'feedback = ? AND userid = ?';
		$params_select = array($feedback->id, $usrid);
		$completedid = $DB->get_records_select('feedback_completed', $select, $params_select, 'id', MUST_EXIST);
		if(empty($completedid)){
			$completedid=0;
		}
		else{ // Si ya se ha completado, comprobaremos si podemos volver a completarlo
			if ($feedback->multiple_submit == 0 ) {
				throw new moodle_exception('this_feedback_is_already_submitted', 'feedback');
			}
		}
		
		$transaction = $DB->start_delegated_transaction();
/*
		// Crea una instancia en la tabla feedback_completed y feedback_item_values
		// tmp es false, ya que guardamos directamente los datos en item_values
		$newcompletedid = WSfeedback_save_values($USER->id, $feedback, $feedback_values, $completeid, , false);
		if(!$newcompletedid){
			throw new moodle_exception('invalidmodule', 'error');
		}
*/		
		

		// FUNCTION feedback_save_values
		
		// Parametros
		$tmp = false;
		$tmpstr = $tmp ? 'tmp' : '';

		// Tiempo modificacion encuesta
		$time = time();
		$timemodified = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
		
		
		// Comprueba si esta completado, para crear valores desde cero o actualizarlos
		$completed = $DB->get_record('feedback_completed'.$tmpstr, array('id'=>$completedid));
		
		
		if (!$completed) {
			// Creamos valores desde cero: feedback_create_values
			//return WSfeedback_create_values($usrid, $feedback, $feedback_values, $timemodified, $tmp);
			

			// Parametros
			$guestid = false;

			//$feedbackid = $feedback->id;
			$anonymous_response = $feedback->anonymous;
			$courseid = $feedback->course;

			//first we create a new completed record
			$completed = new stdClass();
			$completed->feedback           = $feedbackid;
			$completed->userid             = $usrid;
			$completed->guestid            = $guestid;
			$completed->timemodified       = $timemodified;
			$completed->anonymous_response = $anonymous_response;
			
			// Introducimos el record en la base de datos
			$completedid = $DB->insert_record('feedback_completed'.$tmpstr, $completed);

			// y acto seguido lo reobtenemos de la base de datos
			$completed = $DB->get_record('feedback_completed'.$tmpstr, array('id'=>$completedid));

			//the keys are in the form like abc_xxx
			//with explode we make an array with(abc, xxx) and (abc=typ und xxx=itemnr)

			//get the items of the feedback
			//if (!$allitems = $DB->get_records('feedback_item', array('feedback'=>$completed->feedback))) {
				//return false;
			//}
			
			//foreach($feedback_values['feedback_values']['item_values'] as $values){
				//$itemvalue = $values;
			//}
			
			//$itemvalue = $feedback_values['feedback_values']['item_values']['value'];
			
			foreach ($params['itemvalues'] as $itemvalue) {
				
				//if (!$item->hasvalue) {
					//continue;
				//}
				
				//get the class of item-typ
				//$itemobj = feedback_get_item_class($item->typ);

				//$keyname = $item->typ.'_'.$item->id;

				// Recogida de valores CAMBIAR
				//if ($itemobj->value_is_array()) {
					//$itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
				//} else {
					//$itemvalue = optional_param($keyname, null, $itemobj->value_type());
				//}
				
				//get the class of item-typ
				$itemobj = feedback_get_item_class($itemvalue['typ']);
				
				if (is_null($itemvalue['value'])) {
					continue;
				}

				$value = new stdClass();
				$value->item = $itemvalue['itemid'];
				$value->completed = $completed->id;
				$value->course_id = $courseid;

				//the kind of values can be absolutely different
				//so we run create_value directly by the item-class
				$value->value = $itemobj->create_value($itemvalue['value']);
				$DB->insert_record('feedback_value'.$tmpstr, $value);
			}
			
			$newcompletedid = $completed->id;
	
			
			
			
		} else { 
			// Actualizamos los valores: feedback_update_values
			$completed->timemodified = $timemodified;
			

			$courseid = $feedback->course;

			$DB->update_record('feedback_completed'.$tmpstr, $completed);
			//get the values of this completed
			$values = $DB->get_records('feedback_value'.$tmpstr, array('completed'=>$completed->id));

			//get the items of the feedback
			//if (!$allitems = $DB->get_records('feedback_item', array('feedback'=>$completed->feedback))) {
				//return false;
			//}
			// OK
			foreach ($params['itemvalues'] as $itemvalue) {
				//if (!$item->hasvalue) {
					//continue;
				//}
				//get the class of item-typ
				$itemobj = feedback_get_item_class($itemvalue['typ']);

				//$keyname = $item->typ.'_'.$item->id;

				//if ($itemobj->value_is_array()) {
					//$itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
				//} else {
					//$itemvalue = optional_param($keyname, null, $itemobj->value_type());
				//}

				//is the itemvalue set (could be a subset of items because pagebreak)?
				if (is_null($itemvalue['value'])) {
					continue;
				}

				$newvalue = new stdClass();
				$newvalue->item = $itemvalue['itemid'];
				$newvalue->completed = $completed->id;
				$newvalue->course_id = $courseid;

				//the kind of values can be absolutely different
				//so we run create_value directly by the item-class
				$newvalue->value = $itemobj->create_value($itemvalue['value']);

				//check, if we have to create or update the value
				$exist = false;
				foreach ($values as $value) {
					if ($value->item == $newvalue->item) {
						$newvalue->id = $value->id;
						$exist = true;
						break;
					}
				}
				if ($exist) {
					$DB->update_record('feedback_value'.$tmpstr, $newvalue);
				} else {
					$DB->insert_record('feedback_value'.$tmpstr, $newvalue);
				}					
			}

			$newcompletedid = $completed->id;
		}
		

		$transaction->allow_commit();
		
		if(!$newcompletedid){
			throw new moodle_exception('invalidmodule', 'error');
		}
		
		// Return completed ID
		return $newcompletedid;
	}
	
	/**
     * Returns description of method result value
     * @return external_description
     */
    public static function complete_feedback_returns() {
        return null;
    }
}

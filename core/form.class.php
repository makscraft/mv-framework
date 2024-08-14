<?php
/**
 * This class is a forms constructor and processor.
 * Creates form from model or from given array of fields.
 * Collects and shows validation errors.
 * Can create and use CSRF security tokens.
 */
class Form
{
	/**
	 * Class of model if form is created from model.
	 * @var string
	 */
	private $model_class;
	
	/**
	 * Record id of model to load values from it into the form.
	 * @var int
	 */
	private $record_id;

	/**
	 * Fields of current form (objects of datatypes).
	 * @var array
	 */
	public $fields;
	
	/**
	 * Errors of form after validation.
	 * @var array
	 */ 
	private $errors = [];
	
	/**
	 * Mode when errors of form are displayed near inputs.
	 * @var bool
	 */
	private $display_with_errors = false;
	
	/**
	 * Keeps regular CSRF token for form validation (if applied).
	 * @var string
	 */
	private $csrf_token;

	/**
	 * Keeps ajax CSRF token for form validation (if applied).
	 * @var string
	 */
	private static $ajax_csrf_token;
	
	/**
	 * Keeps special javascript token for form validation  (if applied).
	 * @var string
	 */ 
	private static $jquery_token;

	/**
	 * List of applied security tokens to validate in current form processing.
	 * @var array
	 */ 
	private $used_tokens = [
		'regular' => false, 
		'ajax' => false, 
		'jquery' => false
	];
	
	/**
	 * Localization object.
	 * @var object I18n
	 */
	public $i18n;
	
	/**
	 * App settings manager.
	 * @var object Registry
	 */
	public $registry;

	/**
	 * Current state of the form (validation, errors, success, ...)
	 * @var array
	 */
	private $state = [
		'submitted' => false,
		'valid' => false,
		'uploaded' => false,
		'errors' => [],
		'extra' => []
	];

	/**
	 * Settings storage if the form uses catcha.
	 * @var array
	 */
	private $captcha;
	
	/**
	 * Creates form object from model class name or array of fields.
	 */
	public function __construct(mixed $form_source, int $record_id = null)
	{
		$this -> i18n = I18n :: instance();
		$this -> registry = Registry :: instance();

		//Form from model class
		if(!is_array($form_source) && Registry :: checkModel($form_source))
		{			
			$model = new $form_source();
			
			if(get_parent_class($model) === "ModelSimple")
				Debug :: displayError("It's forbidden to create forms from simple models.");
			
			//Runs model
			$model -> loadRelatedData();
			
			//To load record values into form later
			$this -> record_id = $record_id;
			
			//Pass model's fields objects into form
			$this -> fields = $model -> getElements();
						
			foreach($this -> fields as $name => $field)
				if($field -> getType() == "many_to_one" || $field -> getType() == "group")
					unset($this -> fields[$name]);
			
			$this -> model_class = $form_source;
		}
		else if(is_array($form_source) && count($form_source)) //Form from array of fields
		{
			foreach($form_source as $field_data)
				$this -> addField($field_data);
		}
		else
		{
			$message = "The form fields source has not being passed. You need to pass the array of fields";
			$message .= " or class name of existed model from folder '~models/'.";
			Debug :: displayError($message);
		}
	}
	
	public function __call($method, $arguments)
	{
	    if($method == "displayFormErrors")
	        return $this -> displayErrors();
		else if($method == "getAllValues")
	        return $this -> all($arguments[0] ?? null);
		else if($method == "displayVertical")
			return $this -> display($arguments[0] ?? [], 'vertical');
		else if($method == "displayTokenCSFR")
	        return $this -> displayTokenCSRF();
	    else if($method == "checkTokenCSFR")
	        return $this -> checkTokenCSRF();
	    else
		{
			$trace = debug_backtrace();
			$message = "Call to undefiend method '".$method."' of Form class object";
			$message .= ', in line '.$trace[0]['line'].' of file ~'.Service :: removeDocumentRoot($trace[0]['file']);

			Debug :: displayError($message, $trace[0]['file'], $trace[0]['line']);
		}
	}

	/**
	 * Return current form state as array.
	 * @return array
	 */
	public function getState()
	{
		return $this -> state;
	}

	/**
	 * Adds value to form state by key.
	 * @return self
	 */
	public function addStateValue(string $key, mixed $value)
	{
		$this -> state['extra'][$key] = $value;

		return $this;
	}

	/**
	 * Removes value from form state by key.
	 * @return self
	 */
	public function removeStateValue(string $key)
	{
		if(array_key_exists($key, $this -> state['extra']))
			unset($this -> state['extra'][$key]);

		return $this;
	}
	
	/**
	 * Empties vluea and errors of all form fields.
	 * @return self
	 */
	public function dropValues()
	{
		foreach($this -> fields as $object)
		{
			$object -> setValue(''); //Drops values and errors of all fields
			$object -> setError('');
		}
		
		$this -> errors = [];

		$this -> state = [
			'submitted' => false,
			'valid' => false,
			'uploaded' => false,
			'errors' => [],
			'extra' => []
		];
		
		return $this;
	}
	
	/**
	 * Returns form fields values as array.
	 * @param array $fiedls optional, names of needed fields
	 * @return array 
	 */
	public function all(?array $fields = null)
	{
		$values = [];
		
		foreach($this -> fields as $name => $object)
			if($fields === null || in_array($name, $fields))
			{
				if($object -> getType() == "file" && $object -> getProperty("multiple"))
					$values[$name] = $object -> getMultipleFilesPaths(); //Multiple files data
				else
					$values[$name] = $object -> getValue(); //Collects all form values	
			}
			
		return $values;
	}

	/**
	 * Checks the request type and collects data from POST.
	 * @return self
	 */
	public function submit()
	{
		if(Http :: isPostRequest())
		{
			$this -> getDataFromPost();
			$this -> state['submitted'] = true;
		}

		return $this;
	}

	
	/**
	 * Loads values into form fields from array.
	 * @return self
	 */
	public function getDataFromArray(array $source)
	{
		$this -> dropValues(); //Removes all old values from form fields

		foreach($source as $name => $value)
			if(isset($this -> fields[$name]))
			{				
				$type = $this -> fields[$name] -> getType(); //Type of current field

				if($type == "file" || $type == "image")
				{
					if($this -> record_id)
						$value = Service :: addFileRoot($value);
						
					$this -> fields[$name] -> setRealValue($value, basename($value));
				}	
				else
					$this -> fields[$name] -> setValue($value);
			}
				
		return $this;
	}
	
	/**
	 * Loads values into form fields from POST global array.
	 * @return self
	 */
	public function getDataFromPost()
	{
		$this -> getDataFromArray($_POST); //Simple field data collection
		
		foreach($this -> fields as $name => $object) //Complex fields data processing
		{
			$type = $object -> getType();
			
			if($type == 'image' || $type == 'file')
			{
				//Tmp files cleanup
				Filemanager :: deleteOldFiles($this -> registry -> getSetting("FilesPath")."tmp/");
				Filemanager :: deleteOldFiles($this -> registry -> getSetting("FilesPath")."tmp/filemanager/");

				if($type == "file" && $object -> getProperty("multiple")) //Multiple files processing
				{
					$this -> processMultipleFilesData($object);
					continue;
				}

				if(isset($_FILES[$name]) && !isset($_POST['value-'.$name]))
					$object -> setValue($_FILES[$name]);
				else if(isset($_POST['value-'.$name]) && $_POST['value-'.$name])
				{
					$data = $object -> unpackUploadedFileData($_POST['value-'.$name]);
					
					if(is_array($data))
						$object -> setRealValue($data['path'], $data['name']);
				}
			}
			else if($type == 'multi_images')
			{
				$old_value = '';

				if(isset($_POST['value-'.$name]) && $_POST['value-'.$name])
					$old_value = Service :: decodeBase64($_POST['value-'.$name]);

				$images = $this -> getMultipleFilesData($name);
				$object -> processMultipleImagesInForm($images, $old_value);
			}
			else if(($type == 'date' || $type == 'date_time') && $object -> getDisplaySelects())
			{
				foreach(array('dd', 'mm', 'yyyy', 'hh', 'mi') as $period)
					if(isset($_POST['select-'.$period.'-'.$name]) && is_numeric($_POST['select-'.$period.'-'.$name]))
						$object -> setSelectValue($period, $_POST['select-'.$period.'-'.$name]);
			}
			else if($type == 'many_to_many' && $object -> getProperty('display_table'))
				$object -> setValuesFromCheckboxes();
			else if($type == 'enum' && $object -> getProperty('multiple_choice'))
				$object -> setValuesFromCheckboxes();
		}
		
		return $this;
	}
	
	/**
	 * Returns value of one field by name.
	 * @return mixed
	 */
	public function getValue(string $field)
	{
		if(isset($this -> fields[$field]))
			return $this -> fields[$field] -> getValue();
	}

	/**
	 * Sets the value of one field by name.
	 * @return self
	 */
	public function setValue(string $field, mixed $value)
	{
		if(isset($this -> fields[$field]))
		{
			if($this -> fields[$field] -> getType() == "file" || $this -> fields[$field] -> getType() == "image")
				$this -> fields[$field] -> setRealValue($value, basename($value));
			else
				$this -> fields[$field] -> setValue($value);
		}
		
		return $this;
	}
	
	/**
	 * Getter to return fields values.
	 * @return mixed
	 */
	public function __get(string $key)
	{
		return $this -> getValue($key);
	}
	
	/**
	 * Setter to pass value into form field.
	 */
	public function __set(string $key, mixed $value)
	{
		return $this -> setValue($key, $value);	
	}
	
	/**
	 * Returns form field object.
	 * @return object|null
	 */
	public function getField(string $field)
	{
		if(isset($this -> fields[$field]))
			return $this -> fields[$field];
	}
	
	/**
	 * Adds one new field into form, by creating model element object.
	 * @return self
	 */
	public function addField(array $field_data)
	{
		Model :: checkElement($field_data);

		if(isset($field_data[3]['captcha'], $field_data[3]['session_key']))
		{
			$this -> captcha = [
				'field' => $field_data[2],
				'session_key' => $field_data[3]['session_key']
			];

			unset($field_data[3]['session_key']);
		}

		$element = Model :: elementsFactory($field_data, 'Form');
		$type = $element -> getType();
		$forbidden_types = ["parent", "many_to_one", "group", "many_to_many"];		

		if(($type == "file" || $type == "image" || $type == "multi_images") && !$this -> model_class)
		{
			if(!$element -> getProperty('files_folder'))
			{
				$message = "You must specify the folder for uploaded files for field '".$element -> getName()."' for the current form. ";
				$message .= "Put the name of folder in extra parameter like 'files_folder' => 'uploads'. ";
				$message .= "This folder will be created in folder 'userfiles' automatically.";
				Debug :: displayError($message);
			}
		}
		else if(!$this -> model_class && in_array($type, $forbidden_types))
		{
			$message = "It's forbidden to use the field type '".$type."' in form which is created ";
			$message .= "without model.";
			Debug :: displayError($message);
		}

		if($type == "enum")
			$element -> defineValuesList();

		$this -> fields[$element -> getName()] = $element;
		
		return $this;
	}
	
	/**
	 * Removes one field from form.
	 * @return self
	 */
	public function removeField(string $field)
	{
		if(isset($this -> fields[$field]))
			unset($this -> fields[$field]);
			
		return $this;
	}
	
	//Validation
	
	/**
	 * Adds the validation rule into form.
	 * @return self
	 */
	public function addRule(mixed $field, string $rule, mixed $value, string $error = '')
	{
		$fields = is_array($field) ? $field : ($field == "*" ? array_keys($this -> fields) : array($field));
		$set_new_rule_value = true;
		
		if(!is_array($value) && strval($value) == "->")
			$set_new_rule_value = false;
			
		foreach($fields as $field_)
			if(isset($this -> fields[$field_]) && $this -> fields[$field_] -> hasProperty($rule))
			{
				if($rule == "format" && ($this -> fields[$field_] -> getType() == "date" || 
					$this -> fields[$field_] -> getType() == "date_time"))
					$value = true; //We can not change date format from here
					
				if($set_new_rule_value)
					$this -> fields[$field_] -> setProperty($rule, $value);
				
				if($error !== '')
				{
					$custom_errors = $this -> fields[$field_] -> getProperty("custom_errors");
					$custom_errors[$rule] = $error;
					$this -> fields[$field_] -> setProperty("custom_errors", $custom_errors);
				}			
			}
		
		return $this;
	}
	
	/**
	 * Removes the validation rule from form.
	 * @return self
	 */
	public function removeRule(mixed $field, mixed $rule)
	{
		$fields = is_array($field) ? $field : ($field == "*" ? array_keys($this -> fields) : [$field]);
		
		foreach($fields as $field_)
			if(isset($this -> fields[$field_]) && $this -> fields[$field_] -> hasProperty($rule))
			{
				$this -> fields[$field_] -> setProperty($rule, false);
				
				$custom_errors = $this -> fields[$field_] -> getProperty("custom_errors");
				unset($custom_errors[$rule]);
				$this -> fields[$field_] -> setProperty("custom_errors", $custom_errors);
			}
		
		return $this;
	}
	
	/**
	 * Validates form fields, according to rules.
	 * @param mixed $fields optional, array of fields to validate
	 * @return bool if valid or not
	 */
	public function validate(?array $fields = null)
	{
		if(Registry :: getInitialVersion() >= 3.0 && !$this -> state['submitted'])
			return false;

		foreach($this -> fields as $name => $object)
			if($fields === null || in_array($name, $fields)) //If we should validate this field
			{
				if($object -> validate($this -> model_class, $this -> record_id) -> getError())
					$this -> errors[] = array($object -> getCaption(), $object -> getError(), $name);
				
				if($object -> getProperty("must_match") && !$object -> getError())
				{
					$match_field = $this -> fields[$object -> getProperty("must_match")];
					
					if($object -> getValue() != $match_field -> getValue())
					{
						$error = $object -> chooseError("must_match", "{error-must-match}");
						$this -> errors[] = array($object -> getCaption(), $error, $name, $match_field -> getCaption());		            		
						
						if(!$object -> getError() && !$match_field -> getError())
							$object -> setError($error);
					}
				}
			}

		if(is_array($this -> captcha))
		{
			$value = $this -> getValue($this -> captcha['field']);
			
			if($value != '')
				if(!isset($_SESSION[$this -> captcha['session_key']]) || 
					$_SESSION[$this -> captcha['session_key']] != $value)
						$this -> addError(I18n :: locale('wrong-captcha').'.', $this -> captcha['field']);
		}

		if($this -> used_tokens["regular"] && !$this -> checkTokenCSRF())
			$this -> errors[] = ["", "{error-wrong-token}", "csrf_individual_token"];
    	else if($this -> used_tokens["ajax"] && !$this -> checkAjaxTokenCSRF())
		  	$this -> errors[] = ["", "{error-wrong-ajax-token}", "csrf_individual_token"];
    	else if($this -> used_tokens["jquery"] && !$this -> checkJqueryToken())
			$this -> errors[] = ["", "{error-wrong-ajax-token}", "csrf_individual_token"];

		if(!count($this -> errors))
			$this -> state['valid'] = true;
		else
		{
			$this -> state['valid'] = false;
			$this -> state['errors'] = $this -> moveErrorsToState();
		}

      	return $this -> state['valid'];
	}

	/**
	 * Creates form state based on current errors list.
	 */
	protected function moveErrorsToState()
	{
		$result = ['fields' => [], 'list' => [], 'all' => ''];

		foreach($this -> errors as $error)
		{
			if(is_array($error) && isset($error[2]))
			{
				$result['fields'][] = $error[2];
				$result['list'][$error[2]] = $this -> displayOneError($error);
			}
		}

		$result['all'] = $this -> displayErrors();

		return $result;
	}
	
	/**
	 * Creates one form error.
	 */
	public function displayOneError(mixed $error)
	{
		$object = isset($error[2], $this -> fields[$error[2]]) ? $this -> fields[$error[2]] : null;
		  
		return Model :: processErrorText($error, $object);
	}
	
	/**
	 * Generates form errors list.
	 * @return string errors html
	 */
	public function displayErrors()
	{
		if($this -> model_class)
		{
			$model_object = new $this -> model_class();
			
			foreach($this -> fields as $name => $object)
				if(!$model_object -> getElement($name))
					$model_object -> passElement($object);
			
			foreach($this -> errors as $error)
				$model_object -> addError($error);
				
			return $model_object -> displayFormErrors();
		}
		else
		{
			if(!count($this -> errors))
				return '';
				
			$html = "<div class=\"form-errors\">\n";
			
			foreach($this -> errors as $error)
				$html .= "<p>".$this -> displayOneError($error)."</p>\n";
			
			return $html."</div>\n";
		}					
	}
	
	/**
	 * Adds one error into form errors list.
	 * @return self
	 */
	public function addError(string $error, string $field = '')
	{
		if($field !== '' && isset($this -> fields[$field]))
		{
			$this -> fields[$field] -> setError($error);
			$this -> errors[] = [$this -> fields[$field] -> getCaption(), $error, $field];
		}
		else
			$this -> errors[] = $error;

		$this -> state['valid'] = false;
		$this -> state['errors'] = $this -> moveErrorsToState();		
		
		return $this;
	}

	/**
	 * Returns form errors quantity.
	 * @return int
	 */
	public function hasErrors()
	{
		return count($this -> errors);
	}
	
	/**
	 * Returns form validation status (if the form has any errors).
	 * @return bool
	 */
	public function isValid()
	{
		return $this -> state['valid'];
	}

	/**
	 * Returns form submit status.
	 * @return bool
	 */
	public function isSubmitted()
	{
		return $this -> state['submitted'];
	}
	
	/**
	 * Returns form error as array.
	 * @return array
	 */
	public function getErrors()
	{
		return $this -> errors;
	}

	/**
	 * Generates form fields html code.
	 * @param mixed $fields form fileds to display, example: 'name' or ['name', 'email', 'phone']
	 * @param string $format optional, output format, options: 'table', 'divs', 'inputs'
	 * @return string html, containing form titles and inputs (or inputs only)
	 */
	public function display(mixed $fields = [], string $format = '')
	{
		if(is_string($fields))
			$fields = $fields === '*' ? [] : [$fields];

		if(Registry :: getInitialVersion() < 3.0 && $format === '')
			$format = 'table';
		else if($format === 'vertical')
			$format = 'old';
		else if($format === '')
			$format = 'divs';

		if(!in_array($format, ['old', 'table', 'divs', 'inputs']))
			return '';
		
		return $this -> composeHtml($fields, $format);
	}
	
	/**
	 * Generates form fields html.
	 * @return string html with form titles and inputs
	 */
	public function composeHtml(array $fields = [], string $format = '')
	{
		$as_divs = in_array($format, ['', 'old', 'divs']);
		$allowed_fields = count($fields) ? $fields : array_keys($this -> fields);

		$html = '';
		
		foreach($this -> fields as $key => $object)
		{
			if(!in_array($key, $allowed_fields))
				continue;

			$caption = $object -> getCaption();
			$type = $object -> getType();
			
			if($type == 'many_to_one')
				continue;
			
			if($object -> getProperty('required'))
				$caption .= "&nbsp;<span class=\"required\">*</span>";
				
			$error_class = $object -> getError() ? " error-field" : "";
				
			if($as_divs)
			{
				if($format === 'divs')
					$html .= "<div class=\"field-wrapper\">\n";

				$html .= "<div class=\"field-name\">";
								
				if($type == "bool")
				{
					if($format == 'divs')
						$html .= "<label>".$object -> displayHtml()." ".$caption."</label></div>\n";
					else
					{
						$bool_html = $object -> displayHtml();
						$bool_id = "form-bool-".$object -> getName();
						$bool_html = str_replace("/>", "id=\"".$bool_id."\" />", $bool_html);
						
						$html .= $bool_html." <label for=\"".$bool_id."\">".$caption."</label>\n";
					}

					if($this -> display_with_errors && $object -> getError())
					{				
						$error = [$object -> getCaption(), $object -> getError(), $object -> getName()];
						$html .= "<p class=\"field-error\">".$this -> displayOneError($error)."</p>\n";
					}					

					$html .= "</div>\n";

					continue;
				} 
				
				$html .= $caption."</div>\n";
				$html .= "<div class=\"field-input".$error_class."\">\n";
			}
			else if($format === 'table')
			{
				$html .= "<tr>\n<td class=\"field-name\">".$caption."</td>\n";
				$html .= "<td class=\"field-input".$error_class."\">\n";			
			}
			else if($format === 'inputs')
			{
				if($type == "bool")
				{
					$html .= "<label>".$object -> displayHtml()." ".$caption."</label>\n";
					continue;
				}
			}
			
			if($type == "char" && $object -> getProperty('captcha'))
			{
				$src = $this -> registry -> getSetting("MainPath").$object -> getProperty('captcha');
				$html .= "<img src=\"".$src."\" />\n";
			}

			if($type == "enum" && $object -> getProperty('multiple_choice'))
				$html .= $object -> displayAsCheckboxes();
			else if($type == "file" && $object -> getProperty('multiple'))
				$html .= $object -> displayMultipleHtml();
			else if($type == "many_to_many" && !$object -> getProperty('display_table'))
				$html .= $object -> displayAsSelect();
			else
				$html .= $object -> displayHtml('frontend');
				
			if($this -> display_with_errors && $object -> getError())
			{				
				$error = [$object -> getCaption(), $object -> getError(), $object -> getName()];
				
				if($object -> getError() == "{error-must-match}")
					$error[3] = $this -> fields[$object -> getProperty("must_match")] -> getCaption();
				
				$error = $this -> displayOneError($error);
				$error = preg_replace("/\s+\./", ".", $error);				
				$html .= "<p class=\"field-error\">".$error."</p>\n";
			}
			
			if($as_divs)
				$html .= "</div>\n";
			else if($format === 'table')
				$html .= "</td>\n</tr>\n";

			if($format === 'divs')
				$html .= "</div>\n";
		}
		
		return $html;
	}
	
	/**
	 * Generates html data for one form field.
	 * @return string html
	 */
	public function displayFieldHtml(string $name)
	{				
		if(isset($this -> fields[$name]))
		{
			$html = $this -> fields[$name] -> displayHtml("frontend");
			$type = $this -> fields[$name] -> getType();
			
			if($type == "char" && $this -> fields[$name] -> getProperty('captcha'))
			{
				$src = $this -> registry -> getSetting("MainPath").$this -> fields[$name] -> getProperty('captcha');
				$html = "<img src=\"".$src."\" alt=\"\" />\n".$html;
			}
			else if($type == "file" && $this -> fields[$name] -> getProperty('multiple'))
				$html = $this -> fields[$name] -> displayMultipleHtml();
			
			return $html;
		}

		return '';
	}
	
	/**
	 * Sets required validation condition for form fields.
	 * @param mixed $fields, '*' - all fields, or array like ['name', 'phone']
	 * @return self
	 */
	public function setRequiredFields(mixed $fields)
	{
		if($fields === '' || $fields === [])
			return $this;
		
		if($fields === '*')
			$fields = array_keys($this -> fields);
		
		if(is_array($fields))
			foreach($this -> fields as $name => $object)
				$object -> setRequired(in_array($name, $fields));
				
		return $this;		
	}
	
	/**
	 * Sets caption (title) for the form field.
	 * @return self
	 */
	public function setCaption(string $field, string $caption)
	{
		if(isset($this -> fields[$field]))
			$this -> fields[$field] -> setCaption($caption);

		return $this;
	}
	
	/**
	 * Sets required property to true for the single form field.
	 * @return self
	 */
	public function setRequired(string $field)
	{
		if(isset($this -> fields[$field]))
			$this -> fields[$field] -> setRequired(true);

		return $this;
	}	
	
	/**
	 * Sets help_text property for the form field.
	 * @return self
	 */
	public function setHelpText(string $field, string $value)
	{
		if(isset($this -> fields[$field]))
			$this -> fields[$field] -> setHelpText($value);

		return $this;
	}
	
	/**
	 * Sets html parameters property for the form field.
	 * @return self
	 */
	public function setHtmlParams(mixed $field, string $value)
	{
		if(!is_array($field))
			$field = [$field];
			
		foreach($field as $key)
			if(isset($this -> fields[$key]))
				$this -> fields[$key] -> setHtmlParams($value);

		return $this;
	}

	/**
	 * Sets display_with_errors property for the form.
	 * @return self
	 */
	public function setDisplayWithErrors(bool $value = true)
	{		
		$this -> display_with_errors = $value;
		
		return $this;
	}
	
	/**
	 * Sets enum empty title property for the form field.
	 * @return self
	 */
	public function setEnumEmptyValueTitle(string $field, string $title)
   	{
   		if(isset($this -> fields[$field]) && 
		   ($this -> fields[$field] -> getType() == 'enum' || $this -> fields[$field] -> getType() == 'parent'))
			$this -> fields[$field] -> setEmptyValueTitle($title);
				
		return $this;
   	}

	/**
	 * Moves files and images from tmp folder to the proper locations.
	 * @return self
	 */
	public function moveUploadedFiles()
	{
		if($this -> state['uploaded'] || !$this -> state['valid'])
			return $this;

		foreach($this -> fields as $name => $object)
		{
			$type = $object -> getType();
			$value = $this -> fields[$name] -> getValue();
			$multiple_file = $type === 'file' && $object -> getProperty('multiple');
			
			if($type === 'image' || ($type === 'file' && !$multiple_file))
			{
				$object -> copyFile($this -> model_class ?? '');
				$object -> removeFileRoot();
			}
			else if($type === 'file' && $multiple_file && is_numeric($value))
				$this -> copyMultipleFilesToTargetFolder($name);
			else if($type === 'multi_images')
				$object -> copyImages($this -> model_class ?? '');
		}

		$this -> state['uploaded'] = true;

		return $this;
	}

	/**
	 * Generates html message for email, based on form data.
	 * @return string html ul list
	 */
	public function composeMessage(array $allowed_fields = [])
	{
		//Fields of form which will go into message
		$allowed_fields = count($allowed_fields) ? $allowed_fields : array_keys($this -> fields);
		$files_fields = ['file', 'image', 'multi_images'];
		$this -> moveUploadedFiles();

		if(!$this -> state['valid'])
			return '';

		$message = "<ul>\n";
		
		foreach($allowed_fields as $name)
			if(isset($this -> fields[$name]))
			{
				$type =  $this -> fields[$name] -> getType();
				$caption = $this -> fields[$name] -> getCaption().": ";
				
				if($type == "bool")
				{
					$key = $this -> fields[$name] -> getValue() ? "yes" : "no";
					$message .= "<li>".$caption.I18n :: locale($key)."</li>\n";
				}
				else if($type == "many_to_many" && $this -> fields[$name] -> getValue())
				{
					$values = $this -> fields[$name] -> getDataForMessage($this -> fields[$name] -> getValue());
					$message .= "<li>".$caption.$values."</li>\n";
				}
				else if($type == "enum")
				{
					$values = $this -> fields[$name] -> getValuesList();
					
					if($this -> fields[$name] -> getValue())
						if($this -> fields[$name] -> getProperty("multiple_choice"))
						{
							$selected_titles = [];
							
							foreach(explode(",", $this -> fields[$name] -> getValue()) as $key)
								if(isset($values[$key]))
									$selected_titles[] = $values[$key];
										
							$message .= "<li>".$caption.implode(", ", $selected_titles)."</li>\n";
						}
						else if($this -> fields[$name] -> getProperty("long_list"))
							$message .= "<li>".$caption.$this -> getEnumTitle($name)."</li>\n";
						else if(isset($values[$this -> fields[$name] -> getValue()]))
							$message .= "<li>".$caption.$values[$this -> fields[$name] -> getValue()]."</li>\n";
				}
				else if(in_array($type, $files_fields))
				{
					$value = $this -> fields[$name] -> getValue();

					if(!$value || $value === '[]')
						continue;

					if($type == 'multi_images')
					{
						foreach(MultiImagesModelElement :: unpackValue($value) as $image)
						{
							$link = Service :: setFullHttpPath($image['image']);
							$message .= "<li>".$caption."<a href=\"".$link."\" target=\"_blank\">".$link."</a></li>\n";
						}
					}
					else if($type == 'file' && $this -> fields[$name] -> getProperty("multiple"))
					{
						$files = $this -> fields[$name] -> getMultipleFilesPaths();

						foreach($files as $file)
						{
							$link = Service :: setFullHttpPath($file);
							$message .= "<li>".$caption."<a href=\"".$link."\" target=\"_blank\">".$link."</a></li>\n";
						}						
					}
					else
					{
						$link = Service :: setFullHttpPath($value);
						$message .= "<li>".$caption."<a href=\"".$link."\" target=\"_blank\">".$link."</a></li>\n";
					}
				}				
				else if($this -> fields[$name] -> getValue())
					$message .= "<li>".$caption.$this -> fields[$name] -> getValue()."</li>\n";
			}
				
		return $message."</ul>\n";
	}
	
	/**
	 * Returns title of enum field of the form.
	 * @return string
	 */
	public function getEnumTitle(string $field)
	{
		if(!isset($this -> fields[$field]) || $this -> fields[$field] -> getType() != "enum")
			return '';
			
		return $this -> fields[$field] -> getValueName($this -> fields[$field] -> getValue());
	}
	
	/**
	 * Transfers values from array into form fields.
	 * @return self
	 */
	public function loadRecord(array $fields = [])
	{
		if(!$this -> record_id)
			return $this;
		
		$model_object = new $this -> model_class();
		$record = $model_object -> findRecordById($this -> record_id);
		
		if(!$record) 
			return $this; //If record is not exists
		
		$source = $record -> getValues(); //Values of record from database
		
		 //If we passed fields array which will be loaded into form
		if(count($fields))
			foreach($source as $field => $value)
				if(!in_array($field, $fields))
					unset($source[$field]);
					
		foreach($source as $field => $value)
		{
			$object = $model_object -> getElement($field);
			
			if($object)
				if($object -> getType() == "password")
					unset($source[$field]);
				else if($object -> getType() == "date" || $object -> getType() == "date_time")
					$source[$field] = I18n :: formatDate($value);
		}

		$this -> getDataFromArray($source); //Passes data into form fields
		
		foreach($this -> fields as $object)
			$object -> cleanValue();
		
		return $this;
	}
	
	/**
	 * Filters enum and many_to_many dataypes values lists.
	 * @param string $field
	 * @param array $params like ['active' => 1]
	 * @return self
	 */
	public function filterValuesList(string $field, array $params)
	{
		if(isset($this -> fields[$field]) && count($params))
		{
			$type = $this -> fields[$field] -> getType();
			
			if($type == "enum" || $type == "many_to_many")
				$this -> fields[$field] -> filterValuesList($params);
		}			
		
		return $this;
	}
	
	
	//Fields settings
	
	/**
	 * Says to display date and date_time fields as select tags.
	 * @return self
	 */
	public function setDisplaySelects(string $field)
	{
		if(isset($this -> fields[$field]))
		{
			$type = $this -> fields[$field] -> getType();
		
			if($type == "date" || $type == "date_time")
				$this -> fields[$field] -> setDisplaySelects(true);
		}
			
		return $this;
	}
	
	/**
	 * Says to display enum field as radio inputs, wrapped in table cells.
	 * @return self
	 */	
	public function setDisplayRadio(string $field, int $columns)
	{
		if(isset($this -> fields[$field]) && $this -> fields[$field] -> getType() == "enum" && $columns)
			$this -> fields[$field] -> setDisplayRadio(intval($columns));
		
		return $this;
	}
	
	/**
	 * Says to display enum or many_to_many fields as a table with checkboxes.
	 * @return self
	 */
	public function setDisplayTable(string $field, int $columns)
	{		
		if(isset($this -> fields[$field]) && $columns)
			if($this -> fields[$field] -> getType() == 'many_to_many')
				$this -> fields[$field] -> setDisplayTable($columns);
			else if($this -> fields[$field] -> getType() == 'enum')
				$this -> fields[$field] -> setProperty('multiple_choice', $columns);
			
		return $this;
	}
	
	/**
	 * Returns property of form field (datatype object) by field name and property name.
	 * @return mixed
	 */
	public function getFieldProperty(string $field, string $property)
	{
		if(isset($this -> fields[$field]))
			if($this -> fields[$field] -> hasProperty($property))
				return $this -> fields[$field] -> getProperty($property);
	}

	/**
	 * Sets the property of form field (datatype object) by field name and property name.
	 * @return self
	 */
	public function setFieldProperty(string $field, string $property, mixed $value)
	{
		if(isset($this -> fields[$field]))
			if($this -> fields[$field] -> hasProperty($property))
				$this -> fields[$field] -> setProperty($property, $value);
			
		return $this;
	}

	/**
	 * Sets placeholder property for one form field.
	 * @return self
	 */
	public function setPlaceholder(string $field, string $placeholder)
	{
		if(isset($this -> fields[$field]))
			if($this -> fields[$field] -> hasProperty('placeholder'))
				$this -> setFieldProperty($field, 'placeholder', $placeholder);

		return $this;
	}

	/**
	 * Sets form fields captions as a placeholders for inputs or textareas.
	 * @param bool $show_required_mark if true symbol '*' will be added to the placeholder if the field is required
	 * @return mixed
	 */
	public function setNamesAsPlaceholders(bool $show_required_mark = true)
	{
		foreach($this -> fields as $field => $object)
		{
			$star = '';

			if($show_required_mark && $object -> getProperty('required'))
				$star = ' *';

			if($object -> hasProperty('placeholder'))
				$this -> setFieldProperty($field, 'placeholder', $object -> getCaption().$star);
			else if($object -> getType() === 'enum' || $object -> getType() === 'parent')
				$this -> setEnumEmptyValueTitle($field, $object -> getCaption().$star);
			else if($object -> getType() === 'many_to_many')
				$this -> setFieldProperty($field, 'empty_value', $object -> getCaption().$star);
		}
		
		return $this;
	}

	
	//Security tokens

	/**
	 * Ganerates special name for cookie to keep form ajax csrf token part.
	 * @return string key name 
	 */
	static public function generateFormCookieKey()
	{
		$code = Debug :: browser().substr(Registry :: get('SecretCode'), 10, 10);
		$code .= Registry :: get('DomainName');

		return 'form_'.substr(md5($code), 0, 8);
	}

	/**
	 * Creates base security token and stores it into cookie.
	 * @return string token value 
	 */
	static public function getCreateCookieKeyToken()
	{
		$key = self :: generateFormCookieKey();

		if(isset($_COOKIE[$key]) && $_COOKIE[$key])
			return trim($_COOKIE[$key]);
		else
		{
			$token = Service :: strongRandomString(50);
			$time = time() + 3600 * 24 * 30;
			
			Http :: setCookie($key, $token, ['expires' => $time]);
			$_COOKIE[$key] = $token;

			return $token;
		}
	}


	//Ajax CSRF token
	
	/**
	 * Sets form option to use ajax csrf token.
	 */
	public function useAjaxTokenCSRF()
	{
		self :: createAjaxTokenCSRF();
		$this -> used_tokens["ajax"] = true;
		
		return $this;
	}

	/**
	 * Creates ajax csrf token for form.
	 * @return string
	 */
	static public function createAjaxTokenCSRF()
	{
		if(!self :: $ajax_csrf_token)
		{
			$code = Registry :: get("SecretCode");
			$key = self :: getCreateCookieKeyToken();

			self :: $ajax_csrf_token =  Service :: createHash($code.Debug :: browser().$key, "sha224");
		}
				
		return self :: $ajax_csrf_token;
	}

	/**
	 * Creates and returns ajax csrf token input (legacy method).
	 * @return string html input
	 */
	static public function createAndDisplayAjaxTokenCSRF()
	{
		return self :: displayAjaxTokenCSRF();
	}

	/**
	 * Creates and returns ajax csrf token html input.
	 * @return string html input
	 */
	static public function displayAjaxTokenCSRF()
	{
		self :: createAjaxTokenCSRF();

		return "<input type=\"hidden\" name=\"csrf_ajax_token\" value=\"".self :: $ajax_csrf_token."\" />\n";
	}

	/**
	 * Returns ajax csrf token value.
	 * @return string
	 */
	public function getAjaxTokenCSRF()
	{
	    return self :: $ajax_csrf_token;
	}

	/**
	 * Checks (validates) ajax csrf token value.
	 * @return bool
	 */
	public function checkAjaxTokenCSRF()
	{
	    return (isset($_POST["csrf_ajax_token"]) && $_POST["csrf_ajax_token"] == self :: $ajax_csrf_token);
	}


	//Regular CSRF token

	/**
	 * Sets form option to use regular csrf token.
	 */
	public function useTokenCSRF()
	{
		$token = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
		$token .= $_SERVER["REMOTE_ADDR"].$this -> model_class;
		
		if(Service :: sessionIsStarted())
		{
			if(!isset($_SESSION["csrf-individual-token"]))
				$_SESSION["csrf-individual-token"] = Service :: strongRandomString(50);
			
			$token .= $_SESSION["csrf-individual-token"];
		}

		$this -> csrf_token = Service :: createHash($token.$this -> registry -> getSetting("SecretCode"));
		$this -> used_tokens["regular"] = true;
		
		return $this;
	}
	
	/**
	 * Returns csrf token html input.
	 * @return string html input
	 */
	public function displayTokenCSRF()
	{
		$html = "<input type=\"hidden\" name=\"csrf_individual_token\" value=\"".$this -> csrf_token."\" />\n";
		
		if($this -> display_with_errors)
			foreach($this -> errors as $error)
				if($error[2] == "csrf_individual_token")
				{
					$error[1] = str_replace(array("{", "}"), "", $error[1]);
					$html .= "<p class=\"field-error\">".I18n :: locale($error[1])."</p>\n";
				}
		
		return $html;
	}

	/**
	 * Returns regular csrf token value.
	 * @return string
	 */
	public function getTokenCSRF()
	{
		return $this -> csrf_token;
	}
	
	/**
	 * Checks (validates) regular csrf token value.
	 * @return bool
	 */
	public function checkTokenCSRF()
	{
	    return (isset($_POST["csrf_individual_token"]) && $_POST["csrf_individual_token"] == $this -> csrf_token);
	}


	//jQuery token

	/**
	 * Sets form option to use jquery token.
	 */
	public function useJqueryToken()
	{
		self :: createJqueryToken();
		$this -> used_tokens["jquery"] = true;
	    
	    return $this;
	}
	/**
	 * Creates jquery token for form.
	 * @return string
	 */
	static public function createJqueryToken()
	{
		if(!self :: $jquery_token)
		{
			$key = self :: getCreateCookieKeyToken();
			$token = Service :: createHash($key.Registry :: get("SecretCode").Debug :: browser(), "sha224");
			
			self :: $jquery_token = preg_replace('/\D/', '', $token);
		}

	    return self :: $jquery_token;
	}
	
	/**
	 * Creates and returns jquery token html string.
	 * @return string script html link
	 */
	static public function displayJqueryToken()
	{
		self :: createJqueryToken();

	    $html = "\n<script type=\"text/javascript\"> $(document).ready(function(){";
	    $html .= "$(\"form[method='post']\").append(\"<input type='hidden' name='jquery_check_code' ";
	    $html .= "value='".self :: $jquery_token."' />\")";
	    $html .= "}); </script>";
	    
	    return $html;
	}

	/**
	 * Creates and returns jquery token html string (legacy method).
	 * @return string script html link
	 */
	static public function createAndDisplayJqueryToken()
	{
		return self :: displayJqueryToken();
	}

	/**
	 * Returns jquery token value.
	 * @return string
	 */
	public function getJqueryToken()
	{
	    return self :: $jquery_token;
	}

	/**
	 * Checks (validates) jquery token value.
	 * @return bool
	 */
	public function checkJqueryToken()
	{
	    return (isset($_POST["jquery_check_code"]) && $_POST["jquery_check_code"] == self :: $jquery_token);
	}


	/* Multiple files input processing */

	public function getMultipleFilesValue($field)
	{
		if(isset($this -> fields[$field]) && $this -> fields[$field] -> getType() == "file")
			if($this -> fields[$field] -> getProperty("multiple"))
				return $this -> fields[$field] -> getMultipleFilesPaths();
	}

	static public function getMultipleFilesData(string $field)
	{
		$files = [];
		
		if(isset($_FILES[$field]))
			foreach($_FILES[$field] as $section => $data)
				foreach($data as $key => $value)
					$files[$key][$section] = $value;

		foreach($files as $key => $data)
			if(!$data["name"] || !$data["type"] || !$data["size"])
				unset($files[$key]);

		return $files;
	}

	public function processMultipleFilesData(object $object)
	{
		$field = $object -> getName();
		$limit = (int) $object -> getProperty("multiple");
		$new_files = self :: getMultipleFilesData($field);
		$old_files = [];
		$salt = Registry :: get('SecretCode');

		foreach($_POST as $key => $value)
			if(preg_match("/^multiple-".$field."-\w+$/", $key))
			{
				$parts = explode("-", $key);
				$data = Service :: unserializeArray($value);

				if(md5($data["file"].$salt) == $parts[2] && is_file($data["file"]))
					$old_files[] = $data;
			}
		
		if(count($old_files) <= $limit)
			$object -> setMultipleFiles($old_files);
		
		if(!count($new_files))
			return;

		$error = I18n :: locale("maximum-files-one-time", ["number" => $object -> getProperty("multiple")]).'.';
		$max = $limit - count($old_files);

		if($max <= 0)
		{
			$object -> setError($error);
			return;
		}

		foreach($new_files as $file)
		{
			$object -> setValue($file);

			if(!$object -> getError())
				$object -> addMultipleFile($object);

			if(-- $max == 0)
				break;
		}

		if(count($new_files) + count($old_files) > $limit)
			$object -> setError($error);
	}

	public function copyMultipleFilesToTargetFolder(string $field)
	{
		$type = $this -> fields[$field] -> getType();
		$data = $this -> fields[$field] -> getValue();

		if($type != "file" || !$this -> fields[$field] -> getProperty("multiple") || !is_numeric($data))
			return $this;

		$files = $this -> fields[$field] -> getMultipleFilesPaths();
		$values = [];

		foreach($files as $file)
		{
			$this -> fields[$field] -> setRealValue($file, basename($file));
			
			if($path = $this -> fields[$field] -> copyFile())
				$values[] = ["name" => basename($path), "file" => Service :: removeFileRoot($path)];
		}

		$this -> fields[$field] -> setMultipleFiles($values);

		return $this;
	}
}

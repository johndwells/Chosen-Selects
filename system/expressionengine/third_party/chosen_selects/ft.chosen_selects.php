<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD . 'chosen_selects/config.php');
require_once(APPPATH . 'fieldtypes/multi_select/ft.multi_select.php');

/**
 * Chosen Selects - A fieldtype for sexy multiselects
 *
 * @package   Chosen Selects
 * @author    John D Wells
 */
class Chosen_selects_ft extends Multi_select_ft {

/*
	FYI these are inherited from EE_fieldtype:
	var $EE;					// EE instance
	var $field_id;				// CHOSEN_SELECTS_KEY
	var $field_name;			// name given by admin
	var $cell_name;				// if in a matrix cell
	var $settings = array(		// field (or cell?) settings
		'field_type' => '',
		'field_name' => ''
		);
*/

	public $info = array(
		'name'     => CHOSEN_SELECTS_NAME,
		'version'  => CHOSEN_SELECTS_VER
	);

	public $has_array_data = TRUE;
	
	protected $cache;

	// --------------------------------------------------------------------
	

    /**
     * PHP5 construct
     */
	function __construct()
	{
		parent::__construct();

		if ( ! isset($this->EE->session->cache[CHOSEN_SELECTS_KEY]))
		{
			$this->EE->session->cache[CHOSEN_SELECTS_KEY] = array(
				'head' => FALSE
			);

			$this->EE->lang->loadfile(CHOSEN_SELECTS_KEY);
		}
		
		$this->cache =& $this->EE->session->cache[CHOSEN_SELECTS_KEY];
	}
	// --------------------------------------------------------------------


    /**
     * Display Field on Publish/Edit screen
     */
	function display_field($data)
	{
		$this->_add_to_head();
		
		$this->EE->load->helper('custom_field');
		
		$values = decode_multi_field($data);
		$field_options = $this->_get_field_options($data);
		
		$multiple = ($this->settings['allow_multiple'] == 'y') ? 'multiple' : '';
		
		return form_dropdown($this->field_name.'[]', $field_options, $values, $multiple . ' class="chzn-select" id="'.$this->field_id.'"');
	}
	// --------------------------------------------------------------------


    /**
     * Display Settings on Create a New Channel Field
	 *
	 * @param array field settings
	 * @return	void
	 *
     */
	function display_settings($data)
	{
		// set up some defaults
		$data['allow_multiple'] = (isset($data['allow_multiple']) && $data['allow_multiple'] == 'n') ? 'n' : 'y'; // default is 'y'
		$data['ui_width'] = (int) (isset($data['ui_width'])) ? $data['ui_width'] : 350; // default is 350
		$data['allow_inserts'] = (isset($data['allow_inserts']) && $data['allow_inserts'] == 'y') ? 'y' : 'n'; // default is 'n'
		$data['select_msg'] = (isset($data['select_msg'])) ? $data['select_msg'] : 'Select...';

		// add our rows to the options table
		$this->_yes_no_row($data, 'allow_multiple', 'allow_multiple');
		$this->_yes_no_row($data, 'allow_inserts', 'allow_inserts');
		$this->_input_row($data, 'ui_width', 'ui_width');
		$this->_input_row($data, 'select_msg', 'select_msg');
		$this->multi_item_row($data, CHOSEN_SELECTS_KEY);
	}
	// --------------------------------------------------------------------
	
	function _input_row($data, $lang, $data_key, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';
		
		$val = isset($data[$data_key]) ? $data[$data_key] : '';

		$this->EE->table->add_row(
			'<strong>'.lang($lang).'</strong>',
			form_input($prefix.$data_key, $val)
		);
	}


    /**
     * Save field values
	 *
	 * @return	string pipe-delimited values
	 *
     */
	function save($data)
	{
		return implode('|', $data);
	}
	// --------------------------------------------------------------------


	/**
	 * Save Settings
	 *
	 * @return	array settings
	 *
	 */
	function save_settings($data)
	{
		return array(
			'allow_multiple'	=> preg_match('/y|n/i', $this->EE->input->post('allow_multiple')) ? $this->EE->input->post('allow_multiple') : 'y'
		);
	}
	// --------------------------------------------------------------------


	/**
	 * Internal function, places required css & js into document
	 *
	 * @return	Void
	 *
	 */
	function _add_to_head()
	{
		if ( $this->cache['head'] === FALSE )
		{
			$theme_url = $this->EE->config->item('theme_folder_url') . 'third_party/chosen_selects/chosen.css';
	
			// Are we working on SSL?
			if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
			{
				$theme_url = str_replace('http://', 'https://', $theme_url);
			}

			$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $theme_url . '" type="text/css" media="screen" />');
			$this->EE->cp->load_package_js('chosen');

			$this->cache['head'] = TRUE;
		}
	}
	// --------------------------------------------------------------------

}

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
	 * FYI these are inherited from EE_fieldtype:
	 * var $EE;						// EE instance
	 * var $field_id;				// CHOSEN_SELECTS_KEY
	 * var $field_name;				// name given by admin
	 * var $settings = array();		// field (or cell?) settings
	 * var $cell_name;				// ? if in a matrix cell
	*/

	public $info = array(
		'name'     => CHOSEN_SELECTS_NAME,
		'version'  => CHOSEN_SELECTS_VER );
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
	 *
	 * @param array field data
	 * @return	string the dom element of fieldtype
	 *
     */
	function display_field($data)
	{
		//css & js 
		$this->_add_css_js();

		// take our settings prop and normalise it		
		$settings = $this->_normalise_settings($this->settings);

		// build our extras content for dropdown
		$extras = array(
			'data-placeholder="' . $settings['select_msg'] . '"',
			($settings['allow_multiple'] == 'y') ? 'multiple' : '',
			(0 < $settings['min_width']) ? 'style="min-width:' . $settings['min_width'] . 'px;"' : '',
			'class="chzn-select"',
			'id="' . $this->field_name . '"'
		);

		// needed for decode_multi_field functionality
		$this->EE->load->helper('custom_field');

		// decode & set up our field value & options
		$values = decode_multi_field($data);
		
		// if allowing multiple, due to the UI difference in Chosen, we need to add an empty option up top
		// In the future this could possibly be removed if the field is configured as required
		$field_options = ($settings['allow_multiple'] == 'y') ? $this->_get_field_options($data) : array_merge(array('' => $settings['select_msg']), $this->_get_field_options($data));
		
		// garbage cleanup
		unset($settings);

		return form_dropdown($this->field_name.'[]', $field_options, $values, implode(' ', $extras));
		
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
		$settings = $this->_normalise_settings($data);

		$this->_yes_no_row($settings, 'allow_multiple', 'allow_multiple');

		// not yet ready
		// $this->_yes_no_row($settings, 'allow_inserts', 'allow_inserts');

		$this->_input_row($settings, 'min_width', 'min_width');
		$this->_input_row($settings, 'select_msg', 'select_msg');
		$this->multi_item_row($data, CHOSEN_SELECTS_KEY);
		
		// garbage cleanup
		unset($settings);
	}
	// --------------------------------------------------------------------


    /**
     * Save field values
	 *
	 * @param	array
	 * @return	string pipe-delimited values
     */
	function save($data)
	{
		return implode('|', $data);
	}
	// --------------------------------------------------------------------


	/**
	 * Save Settings
	 *
	 * @param	array
	 * @return	array settings
	 */
	function save_settings($data)
	{
		return $this->_normalise_settings($_POST, TRUE);
	}
	// --------------------------------------------------------------------




	/**
	 * Internal function, places required css & js into document
	 *
	 * @return	Void
	 *
	 */
	function _add_css_js()
	{
		if ( $this->cache['head'] === FALSE )
		{
			$theme_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_url, -1) != '/') $theme_url .= '/';
			$theme_url = $theme_url . 'third_party/chosen_selects/';
	
			// Are we working on SSL?
			if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
			{
				$theme_url = str_replace('http://', 'https://', $theme_url);
			}

			$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $theme_url . 'chosen.css" type="text/css" media="screen" /><style type="text/css">.publish_field .chzn-container a { text-decoration: none; }</style>');
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'chosen.jquery.min.js"></script><script type="text/javascript">jQuery(function($) {$("select.chzn-select").chosen();});</script>');

			$this->cache['head'] = TRUE;
			
			unset($theme_url);
		}
	}
	// --------------------------------------------------------------------


	/**
	 * Fetch from array
	 *
	 * This is a helper function to retrieve values from an arrays
	 * It has been borrowed, verbatim, from EE->input
	 *
	 * @access	private
	 * @param	array
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function _fetch_from_array(&$array, $index = '', $xss_clean = FALSE)
	{
		if ( ! isset($array[$index]))
		{
			return FALSE;
		}

		if ($xss_clean === TRUE)
		{
			return $this->EE->security->xss_clean($array[$index]);
		}

		return $array[$index];
	}
	// --------------------------------------------------------------------


	/**
	 * Fetch from array
	 *
	 * This is a helper function to add a row to EE->table
	 *
	 * @access	private
	 * @param	array
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function _input_row($data, $lang, $data_key, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';
		
		$val = isset($data[$data_key]) ? $data[$data_key] : '';

		$this->EE->table->add_row(
			'<strong>'.lang($lang).'</strong>',
			form_input($prefix.$data_key, $val)
		);

		// garbage cleanup		
		unset($val);
	}
	// --------------------------------------------------------------------


	/**
	 * Normalise Settings
	 * Ensures all setting values are acceptable formats/ranges
	 *
	 * @param	array
	 * @param	bool
	 * @return	array settings
	 */
	function _normalise_settings(&$array, $xss_clean = FALSE)
	{
		return array(
			'allow_multiple'	=> preg_match('/y|n/i', $this->_fetch_from_array($array, 'allow_multiple', $xss_clean)) ? $this->_fetch_from_array($array, 'allow_multiple', $xss_clean) : 'y',
			'allow_inserts'		=> preg_match('/y|n/i', $this->_fetch_from_array($array, 'allow_inserts', $xss_clean)) ? $this->_fetch_from_array($array, 'allow_inserts', $xss_clean) : 'n',
			'min_width'			=> (0 < (int) $this->_fetch_from_array($array, 'min_width', $xss_clean)) ? (int) $this->_fetch_from_array($array, 'min_width', $xss_clean) : 0,
			'select_msg'		=> ($this->_fetch_from_array($array, 'select_msg', $xss_clean)) ? $this->_fetch_from_array($array, 'select_msg', $xss_clean) : 'Select...'
		);
	}
	// --------------------------------------------------------------------

}
// END Chosen_selects_ft class

/* End of file ft.chosen_selects.php */
/* Location: ./system/expressionengine/third_party/chosen_selects/ft.chosen_selects.php */

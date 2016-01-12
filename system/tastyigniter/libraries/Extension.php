<?php
/**
 * TastyIgniter
 *
 * An open source online ordering, reservation and management system for restaurants.
 *
 * @package   TastyIgniter
 * @author    SamPoyigi
 * @copyright TastyIgniter
 * @link      http://tastyigniter.com
 * @license   http://opensource.org/licenses/GPL-3.0 The GNU GENERAL PUBLIC LICENSE
 * @since     File available since Release 1.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extension Class
 *
 * @category       Libraries
 * @package        TastyIgniter\Libraries\Extension.php
 * @link           http://docs.tastyigniter.com
 */
class Extension {

    private $extensions = array();

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function getInstalledExtensions($type = NULL) {
        $this->CI->load->model('Extensions_model');
        return $this->CI->Extensions_model->getInstalledExtensions($type);
    }

    public function getExtensions($type = NULL) {
        !empty($this->extensions) OR $this->extensions = $this->getInstalledExtensions();

        if (!empty($type)) {
            $results = array();

            foreach ($this->extensions as $name => $extenion) {
                if ($extenion['type'] === $type) {
                    $results[$name] = $extenion;
                }
            }

            return $results;
        }

        return $this->extensions;
    }

    public function getModules() {
        return $this->getInstalledExtensions('module');
    }

    public function getModule($name) {
        $modules = $this->getModules();

        if (!empty($modules[$name]) AND is_array($modules[$name])) {
            return $modules[$name];
        }
    }

    public function getPayments() {
        return $this->getInstalledExtensions('payment');
    }

    public function getPayment($name) {
        $payments = $this->getPayments();

        if (!empty($payments[$name]) AND is_array($payments[$name])) {
            return $payments[$name];
        }
    }

    public function getAvailablePayments($load_payment = TRUE) {
        $payments = array();
        $this->CI->load->library('location');

        foreach ($this->getPayments() as $payment) {
            if (!empty($payment['ext_data'])) {
                if ($payment['ext_data']['status'] === '1') {

                    $payments[$payment['name']] = array(
                        'name'		=> $payment['title'],
                        'code'		=> $payment['name'],
                        'priority'	=> $payment['ext_data']['priority'],
                        'status'	=> $payment['ext_data']['status'],
                        'data'      => ($load_payment) ? Modules::run($payment['name'] . '/' . $payment['name'] . '/index') : array()
                    );
                }
            }
        }

        if (!empty($payments)) {
            $sort_order = array();
            foreach ($payments as $key => $value) {
                $sort_order[$key] = $value['priority'];
            }
            array_multisort($sort_order, SORT_ASC, $payments);
        }

        return $payments;
    }

    public function loadConfig($module, $fail_gracefully = FALSE, $non_persistent = FALSE) {
        if (!is_string($module)) return FALSE;

        // and retrieve the configuration items
        if ($non_persistent === TRUE) {
            $path = ROOTPATH.EXTPATH."{$module}/config/";
            $config = is_file($path."{$module}.php") ? Modules::load_file($module, $path, 'config') : NULL;
        } else {
            $this->CI->config->load($module . '/' . $module, TRUE);
            $config = $this->CI->config->item($module);
        }

        if ($error = $this->checkConfig($module, $config)) {
            return ($fail_gracefully === FALSE) ? $error : show_error($error);
        }

        return $config;
    }

    public function getConfig($module = '', $item = '') {
        if (!is_string($module)) return NULL;

        $config = $this->CI->config->item($module);

        if ($item == '') {
            return isset($config) ? $config : NULL;
        }

        return isset($config, $config[$item]) ? $config[$item] : NULL;
    }

    public function getMeta($module = '', $config = array()) {
        !empty($config) OR $config = $this->getConfig($module);

        if (isset($config['extension_meta']) AND is_array($config['extension_meta'])) {
            return $config['extension_meta'];
        } else {
            $metadata['type'] = (isset($config['ext_type'])) ? $config['ext_type'] : '';
            $metadata['settings'] = (isset($config['admin_options'])) ? $config['admin_options'] : '';

            return $metadata;
        }
    }

    private function checkConfig($module, $config = array()) {
        $error = FALSE;

        // Check if the module configuration items are correctly set
        $mtypes = array('module', 'payment', 'widget');

        $metadata = (isset($config['extension_meta'])) ? $config['extension_meta'] : array();

        if (!isset($config['ext_type']) OR !in_array($config['ext_type'], $mtypes)) {
            if (!is_array($metadata) OR ! isset($metadata['name'], $metadata['type'])
                OR !in_array($metadata['type'], $mtypes)) {

                $error = 'Check that the extension [' . $module . '] configuration type key is correctly set';
            }
        }

        if (class_exists('Admin_' . $module, FALSE)) {
            $this->CI->load->library('user');

            if (!isset($metadata['settings']) OR !is_bool($metadata['settings']) OR !class_exists('Admin_Controller', FALSE)) {
                $error = 'Check that the extension [' . $module . '] configuration admin_options key is correctly set';
            }
        }

        return $error;
    }
}

// END Extension Class

/* End of file Extension.php */
/* Location: ./system/tastyigniter/libraries/Extension.php */
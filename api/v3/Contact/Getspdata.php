<?php

/**
 * API Method Parameters
 * @return array
 */
function _civicrm_api3_contact_getspdata_params() {
  return [
    'contact_id'            => ['api.required' => 0, 'name' => 'contact_id',
                                'title' => 'Contact ID (int)', 'type' => CRM_Utils_Type::T_INT],
    'group'              => ['api.required' => 0, 'name' => 'group',
                                'title' => 'Group', 'type' => CRM_Utils_Type::T_INT,
                                'pseudoconstant' => array(
                                    'table' => 'civicrm_group',
                                    'keyColumn' => 'id',
                                    'labelColumn' => 'title'
                                )],
    'city'                  => ['api.required' => 0, 'name' => 'city',
                                'title' => 'Woonplaats (string or array of strings)', 'type' => CRM_Utils_Type::T_STRING],
    'gemeente'                  => ['api.required' => 0, 'name' => 'gemeente',
                                'title' => 'Gemeente (string or array of strings)', 'type' => CRM_Utils_Type::T_STRING],
    'geo_code_1'            => ['api.required' => 0, 'name' => 'geo_code_1',
                                'title' => 'Latitude (between, array of two floats)', 'type' => CRM_Utils_Type::T_TEXT],
    'geo_code_2'            => ['api.required' => 0, 'name' => 'geo_code_2',
                                'title' => 'Longitude (between, array of two floats)', 'type' => CRM_Utils_Type::T_TEXT],
    'include_spspecial'     => ['api.required' => 0, 'name' => 'include_spspecial',
                                'title' => 'Include SP staff who aren\'t members', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'include_memberships'   => ['api.required' => 0, 'name' => 'include_memberships',
                                'title' => 'Include SP membership data', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'include_relationships' => ['api.required' => 0, 'name' => 'include_relationships',
                                'title' => 'Include SP relationship data', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'include_non_menmbers' => ['api.required' => 0, 'name' => 'include_non_menmbers',
                                 'title' => 'Also include non members', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'include_non_members' => ['api.required' => 0, 'name' => 'include_non_members',
                                 'title' => 'Also include non members', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'order_by_group_contact_id' => ['api.required' => 0, 'name' => 'order_by_group_contact_id',
                                 'title' => 'Sort contacts by order in group', 'type' => CRM_Utils_Type::T_BOOLEAN],
    'sequential'            => ['api.required' => 0, 'name' => 'sequential',
                                'title' => 'Sequential', 'type' => CRM_Utils_Type::T_BOOLEAN],
  ];
}

/**
 * Contact.GetSPData API specification
 * This is used for documentation and validation.
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 * @param array $params description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_contact_getspdata_spec(&$params) {
  $myparams = _civicrm_api3_contact_getspdata_params();
  $params = array_merge($params, $myparams);
}

/**
 * Contact.GetSPData API
 * Returns detailed information about contacts that currently are a member of SP and/or ROOD
 * and that are accessible to the user using the API,
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @throws API_Exception
 */
function civicrm_api3_contact_getspdata($params) {

  // Parse booleans and options
  $params['include_spspecial'] = ($params['include_spspecial'] == 1);
  $params['include_memberships'] = ($params['include_memberships'] == 1);
  $params['include_relationships'] = ($params['include_relationships'] == 1);
  $params['include_non_menmbers'] = ($params['include_non_menmbers'] == 1);
  $params['include_non_members'] = ($params['include_non_members'] == 1);
  $params['order_by_group_contact_id'] = ($params['order_by_group_contact_id'] == 1);
  $params['options']['limit'] = !empty($params['options']['limit']) ? (int) $params['options']['limit'] : 25;
  $params['options']['offset'] = !empty($params['options']['offset']) ? (int) $params['options']['offset'] : 0;
  $params['sequential'] = ($params['sequential'] == 1) ? TRUE : FALSE;

  // Validatie voor Bob!
  $validSystemParams = ['options','version', 'check_permissions', 'prettyprint', 'access_token'];
  $methodParams = array_merge(array_keys(_civicrm_api3_contact_getspdata_params()), $validSystemParams);
  $invalidParams = [];
  foreach ($params as $k => $p) {
    if (!in_array($k, $methodParams)) {
      $invalidParams[] = $k;
    }
  }
  if (count($invalidParams) > 0) {
    return civicrm_api3_create_error('Invalid parameter(s): ' . implode(', ', $invalidParams) . '.');
  }

  if(isset($params['city'])) {
    if(isset($params['city']['IN'])) {
      $params['city'] = $params['city']['IN'];
    }
  }

  if(isset($params['gemeente'])) {
    if(isset($params['gemeente']['IN'])) {
      $params['gemeente'] = $params['gemeente']['IN'];
    }
  }

  if(isset($params['contact_id'])) {
    if(is_array($params['contact_id']) && !is_numeric($params['contact_id'])) {
      return civicrm_api3_create_error('Invalid parameter: contact_id is not a number.');
    }
    $params['contact_id'] = (int)$params['contact_id'];
  }

  if(isset($params['geo_code_1'])) {
    if (!is_array($params['geo_code_1'])) {
      return civicrm_api3_create_error('Invalid coordinates: geo_code_1 is not an array.');
    }
    if(isset($params['geo_code_1']['BETWEEN'])) {
      $params['geo_code_1'] = $params['geo_code_1']['BETWEEN'];
    }
    if(count($params['geo_code_1']) != 2) {
      return civicrm_api3_create_error('Invalid coordinates: geo_code_1 does not contain an array of two floats.');
    }
  }

  if(isset($params['geo_code_2'])) {
    if (!is_array($params['geo_code_2'])) {
      return civicrm_api3_create_error('Invalid coordinates: geo_code_2 is not an array.');
    }
    if(isset($params['geo_code_2']['BETWEEN'])) {
      $params['geo_code_2'] = $params['geo_code_2']['BETWEEN'];
    }
    if(count($params['geo_code_2']) != 2) {
      return civicrm_api3_create_error('Invalid coordinates: geo_code_2 does not contain an array of two floats.');
    }
  }

  // Get and return data
  // (Exceptions should be automatically caught by the API handler)
  $result = CRM_SPCustomApi_Contact::getSPData($params);
  return civicrm_api3_create_success($result, $params, 'Contact', 'getspdata');

}

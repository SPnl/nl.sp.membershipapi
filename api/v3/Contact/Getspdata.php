<?php

/**
 * Contact.GetSPData API specification
 * This is used for documentation and validation.
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 * @param array $params description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_contact_getspdata_spec(&$params) {
  $params['contact_id']['api.required'] = 0;
  $params['include_spspecial']['api.required'] = 0;
  $params['include_memberships']['api.required'] = 0;
  $params['include_relationships']['api.required'] = 0;
  $params['options']['limit']['api.required'] = 0;
  $params['options']['offset']['api.required'] = 0;
  $params['sequential'] = 0;
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

  // Set default parameters
  $params['contact_id'] = $params['contact_id'] ? (int) $params['contact_id'] : NULL;
  $params['include_spspecial'] = ($params['include_spspecial'] == 1);
  $params['include_memberships'] = ($params['include_memberships'] == 1);
  $params['include_relationships'] = ($params['include_relationships'] == 1);
  $params['limit'] = $params['options']['limit'] ? (int) $params['options']['limit'] : 25;
  $params['offset'] = $params['options']['offset'] ? (int) $params['options']['offset'] : 0;
  $params['sequential'] = ($params['sequential'] == 1) ? TRUE : FALSE;

  // Get and return data
  // (Exceptions should be automatically caught by the API handler)
  $result = CRM_SPCustomApi_Contact::getSPData($params);
  return civicrm_api3_create_success($result);

}

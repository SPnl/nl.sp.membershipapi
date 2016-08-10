<?php

/**
 * Contribution.LinkToMembership API specification
 * This is used for documentation and validation.
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 * @param array $params description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_contribution_linktomembership_spec(&$params) {
  $params['source']['api.required'] = 1;
  $params['membership_type_ids']['api.required'] = 1;
  $params['limit']['api.required'] = 0;
  $params['sequential'] = 0;
}

/**
 * Contribution.LinkToMembership API
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @throws API_Exception
 */
function civicrm_api3_contribution_linktomembership($params) {
  if (empty($params['source'])) {
    return civicrm_api3_create_error('source is required');
  }
  if (empty($params['membership_type_ids'])) {
    return civicrm_api3_create_error('membership_type_ids is required and it should contain a comma seperated list of memberships type ids');
  }
  if (is_array($params['membership_type_ids'])) {
    $membership_type_ids = $params['membership_type_ids'];
  } else {
    $membership_type_ids = explode(",", $params['membership_type_ids']);
  }

  $limit = 200;
  if (!empty($params['limit'])) {
    $limit = $params['limit'];
  }
  $return = CRM_SPCustomApi_Contribution_LinkToMembership::LinkToMembership($params['source'], $membership_type_ids, $limit);

  return civicrm_api3_create_success($return,$params);
}
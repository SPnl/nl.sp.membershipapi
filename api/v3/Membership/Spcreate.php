<?php

/**
 * Membership.Spcreate API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_membership_spcreate_spec(&$spec) {
  $params['contact_id']['api.required'] = 1;
  $params['membership_type_id']['api.required'] = 1;
  $params['new_mandaat']['api.default'] = 0;
  $params['new_mandaat']['title'] = 'If you want to create a SEPA mandaat set this parameter to 1 and pass the SEPA mandaat data';
  
  $params['total_amount']['api.required'] = 1;
  $params['financial_type_id']['api.required'] = 1;
}

/**
 * Membership.Spcreate API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_membership_spcreate($params) {
  if (isset($params['id'])) {
    return civicrm_api3_create_error('Invalid parameter ID');
  }
  if (!empty($params['new_mandaat'])) {
    //validate mandaat fields
    if (empty($params['mandaat_status']) ||
        empty($params['iban']) ||
        empty($params['mandaat_datum']) ||
        empty($params['mandaat_plaats'])
    ) {
      return civicrm_api3_create_error('Mandaat parameters invalid');
    }
  }
  
  $iban = false;
  $bic = false;
  
  $membershipParams = _spmembership_api_filter_membership_parameters($params);
  $contributionParams = _spmembership_api_filter_contribution_parameters($params);
  
  //add the sepa mandaat to the contact, if needed
  if (!empty($params['new_mandaat'])) {
    $mandaat_config = CRM_Sepamandaat_Config_SepaMandaat::singleton();
    $membership_mandaat_config = CRM_Sepamandaat_Config_MembershipSepaMandaat::singleton();
    $contribution_mandaat_config = CRM_Sepamandaat_Config_ContributionSepaMandaat::singleton();
    $sepa_params = _spmembership_api_filter_sepamandaat_parameters($params);
    civicrm_api3('Contact', 'create', $sepa_params);
    $customParams = array();
    $customParams['return.custom_'.$mandaat_config->getCustomField('mandaat_nr', 'id')] = 1;
    $customParams['entity_id'] = $params['contact_id'];
    $sepa = civicrm_api3('CustomValue', 'getsingle', $customParams);
    $membershipParams['custom_'.$membership_mandaat_config->getCustomField('mandaat_id', 'id')] = $sepa['latest'];
    $contributionParams['custom_'.$contribution_mandaat_config->getCustomField('mandaat_id', 'id')] = $sepa['latest'];
  } else {
    $iban_config = CRM_Ibanaccounts_Config::singleton();
    if (!empty($params['iban'])) {
      $iban = $params['iban'];
      $contributionParams['custom_'.$iban_config->getIbanContributionCustomFieldValue('id')] = $params['iban'];
    }
    if (!empty($params['bic'])) {
      $bic = $params['bic'];
      $contributionParams['custom_'.$iban_config->getBicContributionCustomFieldValue('id')] = $params['bic'];
    }
  }

  $membership = civicrm_api3('Membership', 'create', $membershipParams);
  $contribution = civicrm_api3('Contribution', 'create', $contributionParams);
  
  if ($iban || $bic) {
    CRM_Ibanaccounts_Ibanaccounts::saveIbanForMembership($membership['id'], $params['contact_id'], $iban, $bic);
  }
  
  $membershipPayment['contribution_id'] = $contribution['id'];
  $membershipPayment['membership_id'] = $membership['id'];
  civicrm_api3('MembershipPayment', 'create', $membershipPayment);
  
  return civicrm_api3_create_success($membership['values'][$membership['id']]);
}

function _spmembership_api_filter_contribution_parameters($params) {
  $contribution_parameters = array();
  
  $contribution_parameters['contact_id'] = $params['contact_id'];
  $contribution_parameters['total_amount'] = $params['total_amount'];
  $contribution_parameters['financial_type_id'] = $params['financial_type_id'];
  
  foreach($params as $key => $value) {
    if (stripos($key, "custom_")===0) {
      //this is a custom field copy it to the membership 
      $contribution_parameters[$key] = $params[$key];
    }
  }
  
  return $contribution_parameters;
}

function _spmembership_api_filter_membership_parameters($params) {
  $membership_params['contact_id'] = $params['contact_id'];
  $membership_params['membership_type_id'] = $params['membership_type_id'];
  $membership_params['num_terms'] = !empty($params['num_terms']) ? $params['num_terms'] : 1;
  if (isset($params['join_date'])) {
    $membership_params['join_date'] = $params['join_date'];
  }
  if (isset($params['membership_start_date'])) {
    $membership_params['membership_start_date'] = $params['membership_start_date'];
  }
  if (isset($params['membership_end_date'])) {
    $membership_params['membership_end_date'] = $params['membership_end_date'];
  }
  foreach($params as $key => $value) {
    if (stripos($key, "custom_")===0) {
      //this is a custom field copy it to the membership 
      $membership_params[$key] = $params[$key];
    }
  }
  return $membership_params;
}

function _spmembership_api_filter_sepamandaat_parameters($params) {
  $config = CRM_Sepamandaat_Config_SepaMandaat::singleton();
  
  $sepa_params['id'] = $params['contact_id'];
  $sepa_params['custom_'.$config->getCustomField('status', 'id')] = $params['mandaat_status'];
  $sepa_params['custom_'.$config->getCustomField('IBAN', 'id')] = $params['iban'];
  $sepa_params['custom_'.$config->getCustomField('mandaat_datum', 'id')] = $params['mandaat_datum'];
  $sepa_params['custom_'.$config->getCustomField('plaats', 'id')] = $params['mandaat_plaats'];
  if (isset($params['bic'])) {
    $sepa_params['custom_'.$config->getCustomField('BIC', 'id')] = $params['bic'];
  }
  if (isset($params['mandaat_omschrijving'])) {
    $sepa_params['custom_'.$config->getCustomField('subject', 'id')] = $params['mandaat_omschrijving'];
  }
  return $sepa_params;
}


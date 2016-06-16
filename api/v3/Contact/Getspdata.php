<?php

/**
 * Contact.GetSPData API specification
 * This is used for documentation and validation.
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_contact_getspdata_spec(&$params) {
  $params['contact_id']['api.required'] = 0;
  $params['options']['limit']['api.required'] = 0;
  $params['options']['offset']['api.required'] = 0;
}

/**
 * Contact.GetSPData API
 * Returns detailed information about contacts that currently are a member of SP and/or ROOD
 * and that are accessible to the user using the API,
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contact_getspdata($params) {

  $params['contact_id'] = $params['contact_id'] ? (int)$params['contact_id'] : null;
  $params['limit'] = $params['limit'] ? (int)$params['limit'] : 25;
  $params['offset'] = $params['offset'] ? (int)$params['offset'] : 0;

  // TODO IETS DOEN!

  return civicrm_api3_create_success([
    'status' => 'TODO Return API result.',
    'params' => $params,
  ]);
}


/* Dit is de query van LegacyExport.Generate, met ingevulde parameters.
   Maar hier speelt dan een issue met opzeggers die weer lid worden, volgens mij.
   En belangrijker: er moet een sluitende ACL-clause overheen.

 SELECT DISTINCT c.id AS contact_id, c.first_name, c.middle_name, c.last_name, cmc.voorletters_1 AS voorletters, c.gender_id, c.birth_date, ca.street_name, ca.street_number, ca.street_unit, ca.city, ca.postal_code, ca.country_id, cc.name AS country_name, cc.iso_code AS country_code, ca.state_province_id, ce.email, cp.phone, cpm.phone AS mobile, cca.id AS afdeling_id, cca.display_name AS afdeling, cva.gemeente_24 AS gemeente, ccr.id AS regio_id, ccr.display_name AS regio, ccp.id AS provincie_id, ccp.display_name AS provincie, c.do_not_mail, c.do_not_phone, cm.membership_type_id AS membership_type, cm.start_date AS sp_start_date, cm.end_date AS sp_end_date, cm.status_id, cm.source, cml.reden_6 AS opzegreden, cmw.cadeau_8 AS cadeau, cmw.datum_14 AS cadeaudatum
	FROM civicrm_contact c
	LEFT JOIN civicrm_membership cm ON (c.id = cm.contact_id AND cm.membership_type_id IN (1,2,3) AND (cm.status_id IN (1,2) OR (cm.end_date >= '2016-01-01' AND cm.status_id IN (3,4,6,7))))
	LEFT JOIN civicrm_relationship cr ON (c.id = cr.contact_id_a AND cr.relationship_type_id IN (126,127,128,111) AND (cr.end_date IS NULL OR cr.end_date > '2016-01-01'))
	LEFT JOIN civicrm_value_migratie_1 cmc ON cmc.entity_id = c.id
	LEFT JOIN civicrm_value_migratie_lidmaatschappen_2 cml ON cml.entity_id = cm.id
	LEFT JOIN civicrm_value_welkomstcadeau_sp_3 cmw ON cmw.entity_id = cm.id
	LEFT JOIN civicrm_address ca ON c.id = ca.contact_id AND ca.is_primary = 1
	LEFT JOIN civicrm_value_adresgegevens_12 cva ON ca.id = cva.entity_id
	LEFT JOIN civicrm_country cc ON ca.country_id = cc.id
	LEFT JOIN civicrm_email ce ON c.id = ce.contact_id AND ce.is_primary = 1
	LEFT JOIN civicrm_phone cp ON c.id = cp.contact_id AND cp.phone_type_id = 1
	LEFT JOIN civicrm_phone cpm ON c.id = cpm.contact_id AND cpm.phone_type_id = 2
	LEFT JOIN civicrm_value_geostelsel cvg ON c.id = cvg.entity_id
	LEFT JOIN civicrm_contact cca ON cvg.afdeling = cca.id
	LEFT JOIN civicrm_contact ccr ON cvg.regio = ccr.id
	LEFT JOIN civicrm_contact ccp ON cvg.provincie = ccp.id
	WHERE c.is_deleted = 0 AND (cm.status_id IN (1,2) OR cm.end_date >= '2016-01-01' OR (cr.relationship_type_id IS NOT NULL AND (cr.end_date IS NULL OR cr.end_date >= '2016-01-01')))
	GROUP BY c.id
 */
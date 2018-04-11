<?php

/**
 * Class CRM_SPCustomApi_Contact
 * Contains custom Contact API methods.
 */
class CRM_SPCustomApi_Contact {

  /**
   * GetSPData
   * @param array $params API call parameters
   * @return array Array of contacts
   * @throws Exception Throws exceptions on error
   */
  public static function getSPData(&$params) {

    // Check user ID
    $session = \CRM_Core_Session::singleton();
    $contactId = $session->get('userID');
    if (!isset($contactId) && !empty($params['check_permissions'])) {
      throw new \CRM_SPCustomApi_Exception('User not logged in or session->userID not set.', 400);
    }

    $membership_type = \CRM_Geostelsel_Config_MembershipTypes::singleton();
    if (empty($params['include_non_menmbers']) && empty($params['include_non_members'])) {
      if (!isset($whereClause) || $whereClause == '1') {
        // Filter voor landelijke gebruikers of voor legacy-export wordt hier alsnog even handmatig ingesteld, om in ieder geval alleen recente leden mee te geven
        $whereClause = 'membership_access.membership_type_id IN (' . implode(", ", $membership_type->getMembershipTypeIds()) . ') 
          AND (membership_access.status_id IN (' . implode(", ", $membership_type->getStatusIds()) . ') 
          OR (membership_access.status_id = \'' . $membership_type->getDeceasedStatusId() . '\' 
          AND (membership_access.end_date >= NOW() - INTERVAL 3 MONTH)))';
      }
    } else {
      // Include everyone except those who are deleted or those who are deceased
      $whereClause = 'contact_a.is_deleted = "0" AND contact_a.is_deceased = "0"';
    }

    $isMemberSelect = "(
      SELECT count(membership_count.id) as membership_count 
		  FROM civicrm_membership membership_count 
      WHERE membership_count.contact_id = contact_a.id
      AND membership_count.membership_type_id IN (" . implode(", ", $membership_type->getMembershipTypeIds()).")
      AND membership_count.status_id IN (". implode(", ", $membership_type->getStatusIds()) . ")
	  ) as is_member";

    // Add contact id to where clause if defined
    if (!empty($params['contact_id'])) {
      $whereClause = " contact_a.id = " . (int)$params['contact_id'] . " AND " . $whereClause;
    }

    // Add city/cities to where clause if defined
    if (!empty($params['city'])) {
      if(is_array($params['city'])) {
        foreach($params['city'] as &$c) {
          $c = CRM_Core_DAO::escapeString($c);
        }
        $cities = '"' . implode('", "', $params['city']) . '"';
        $whereClause = " caddr.city IN (" . $cities . ") AND " . $whereClause;
      } else {
        $whereClause = " caddr.city LIKE '" . CRM_Core_DAO::escapeString($params['city']) . "' AND " . $whereClause;
      }
    }

    // Add gemeente/gemeenten to where clause if defined
    if (!empty($params['gemeente'])) {
      if(is_array($params['gemeente'])) {
        foreach($params['gemeente'] as &$c) {
          $c = CRM_Core_DAO::escapeString($c);
        }
        $cities = '"' . implode('", "', $params['gemeente']) . '"';
        $whereClause = " caddrx.gemeente_24 IN (" . $cities . ") AND " . $whereClause;
      } else {
        $whereClause = " caddrx.gemeente_24 LIKE '" . CRM_Core_DAO::escapeString($params['gemeente']) . "' AND " . $whereClause;
      }
    }

    // Add coordinates to where clause if defined
    if (!empty($params['geo_code_1'])) {
      $whereClause = " caddr.geo_code_1 BETWEEN " . (float)$params['geo_code_1'][0] . " AND " . (float)$params['geo_code_1'][1] . " AND " . $whereClause;
    }
    if (!empty($params['geo_code_2'])) {
      $whereClause = " caddr.geo_code_2 BETWEEN " . (float)$params['geo_code_2'][0] . " AND " . (float)$params['geo_code_2'][1] . " AND " . $whereClause;
    }

    // If include_spspecial is set, add contacts who have a staff relationship type
    // (used by LegacyExport.Generate, not meant to be used by regular API users)
    $relationshipJoin = '';
    if ($params['include_spspecial'] && empty($params['check_permissions'])) {
      $rtypes = static::getSPSpecialRelationshipTypes();
      $relationshipJoin = "LEFT JOIN civicrm_relationship sprel ON contact_a.id = sprel.contact_id_a AND sprel.relationship_type_id IN (" . implode(',', $rtypes) . ") AND sprel.is_active = 1";
      $whereClause = " sprel.id IS NOT NULL OR {$whereClause}";
    }

    $groupJoin = '';
    if (!empty($params['group'])) {
      $groupIds = $params['group'];
      if (!is_array($groupIds)) {
        $groupIds = array($groupIds);
      }
      $groupJoin = "INNER JOIN civicrm_group_contact ON contact_a.id = civicrm_group_contact.contact_id AND civicrm_group_contact.status = 'Added' AND civicrm_group_contact.group_id IN (".implode(", ", $groupIds).") ";
    }

    // Other data used to enrich this export
    $genderCodes = \CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    $spGeoNames = static::getSPGeostelselNames();

    // Execute contact query (civicrm_contact and all data that is tied directly to a single civicrm_contact record))
    $query = <<<SQL
SELECT contact_a.id AS contact_id, first_name, middle_name, last_name, cmigr.voorletters_1 AS initials, display_name, gender_id, birth_date, do_not_mail, do_not_phone, do_not_email, do_not_sms, is_opt_out, contact_a.source,
  caddr.street_address, caddr.street_name, caddr.street_number, caddr.street_unit, caddr.city, caddr.postal_code, caddr.state_province_id, caddr.country_id, caddr.geo_code_1, caddr.geo_code_2,
  cphone.phone AS phone, cmobile.phone AS mobile, cemail.email AS email,
  country.name AS country_name, country.iso_code AS country_code,
  caddrx.gemeente_24 AS gemeente, caddrx.buurt_25 AS cbs_buurt, caddrx.buurtcode_26 AS cbs_buurtcode, caddrx.wijkcode_27 AS cbs_wijkcode,
  geostelsel.afdeling AS afdeling_code, geostelsel.regio AS regio_code, geostelsel.provincie AS provincie_code,
  {$isMemberSelect}
  FROM civicrm_contact contact_a
  LEFT JOIN civicrm_group_contact `civicrm_group_contact-ACL` ON contact_a.id = `civicrm_group_contact-ACL`.contact_id
  LEFT JOIN civicrm_membership membership_access ON contact_a.id = membership_access.contact_id
  LEFT JOIN civicrm_value_geostelsel geostelsel ON contact_a.id = geostelsel.entity_id
  LEFT JOIN civicrm_value_migratie_1 cmigr ON contact_a.id = cmigr.entity_id
  LEFT JOIN civicrm_address caddr ON contact_a.id = caddr.contact_id AND caddr.is_primary = 1
  LEFT JOIN civicrm_country country ON caddr.country_id = country.id
  LEFT JOIN civicrm_value_adresgegevens_12 caddrx ON caddr.id = caddrx.entity_id
  LEFT JOIN civicrm_phone cphone ON contact_a.id = cphone.contact_id AND cphone.phone_type_id = 1
  LEFT JOIN civicrm_phone cmobile ON contact_a.id = cmobile.contact_id AND cmobile.phone_type_id = 2
  LEFT JOIN civicrm_email cemail ON contact_a.id = cemail.contact_id AND cemail.is_primary = 1
  {$relationshipJoin}
  {$groupJoin}
  WHERE {$whereClause}
  GROUP BY contact_a.id
  ORDER BY contact_a.id ASC LIMIT {$params['options']['offset']},{$params['options']['limit']}
SQL;

    // return civicrm_api3_create_error(['query' => $query]);
    $cres = \CRM_Core_DAO::executeQuery($query);

    // Store contacts, and get all contact ids for the next query
    $contacts = [];
    $cids = [];

    /** @var \DB_DataObject $cres */
    while ($cres->fetch()) {

      // Get contact array
      $contact = static::daoToArray($cres);

      // Enrich contact data
      $contact['afdeling'] = $spGeoNames[$contact['afdeling_code']];
      $contact['regio'] = $spGeoNames[$contact['regio_code']];
      $contact['provincie'] = $spGeoNames[$contact['provincie_code']];
      $contact['gender'] = $genderCodes[$contact['gender_id']];

      // Add to contacts and cids array
      $contacts[$cres->contact_id] = $contact;
      $cids[] = $cres->contact_id;
    }

    $cidlist = implode(',', $cids);
    if ($cres instanceof \CRM_Core_DAO) {
      $cres->free();
    }

    // Fetch *current* SP and/or ROOD memberships for these contacts, if include_memberships is set
    if ($params['include_memberships'] && !empty($cidlist)) {
      $mTypes = "'" . implode("','", ['Lid SP', 'Lid ROOD', 'Lid SP en ROOD']) . "'";
      $mStatuses = "'" . implode("','", ['New', 'Current', 'Grace', 'Deceased']) . "'";

      $mquery = <<<SQL
SELECT cmember.contact_id, cmember.id, cmember.membership_type_id AS type_id, cmtype.name AS type_name, cmember.status_id, cmstatus.name AS status_name, cmember.join_date, cmember.start_date, cmember.end_date, cmigr.bron_4 AS source, cmigr.reden_6 AS opzegreden, cwelk.cadeau_8 AS cadeau, cwelk.datum_14 AS cadeau_datum 
  FROM civicrm_membership cmember
  LEFT JOIN civicrm_membership_type cmtype ON cmember.membership_type_id = cmtype.id
  LEFT JOIN civicrm_membership_status cmstatus ON cmember.status_id = cmstatus.id
	LEFT JOIN civicrm_value_migratie_lidmaatschappen_2 cmigr ON cmigr.entity_id = cmember.id
	LEFT JOIN civicrm_value_welkomstcadeau_sp_3 cwelk ON cwelk.entity_id = cmember.id
  WHERE cmember.contact_id IN ({$cidlist})
  AND cmtype.name IN ({$mTypes}) AND (cmstatus.name IN ({$mStatuses}) OR cmember.end_date >= NOW())
SQL;
      $mres = \CRM_Core_DAO::executeQuery($mquery);

      /** @var \DB_DataObject $mres */
      while ($mres->fetch()) {

        // Get membership and filter properties
        $membership = static::daoToArray($mres);

        // Find contact and add membership data
        $contact = &$contacts[$membership['contact_id']];
        if (!isset($contact['memberships'])) {
          $contact['memberships'] = [];
        }
        $contact['memberships'][] = $membership;

        // Add some basic data to contact object
        if (in_array($membership['type_name'], ['Lid SP', 'Lid SP en ROOD'])
            && in_array($membership['status_name'], ['New', 'Current'])
        ) {
          $contact['member_sp'] = 1;
        } elseif (!isset($contact['member_sp'])) {
          $contact['member_sp'] = 0;
        }

        if (in_array($membership['type_name'], ['Lid ROOD', 'Lid SP en ROOD'])
            && in_array($membership['status_name'], ['New', 'Current'])
        ) {
          $contact['member_rood'] = 1;
        } elseif (!isset($contact['member_rood'])) {
          $contact['member_rood'] = 0;
        }
      }
    }
    if ($mres instanceof \CRM_Core_DAO) {
      $mres->free();
    }

    // Fetch *current* relationships for contacts, if include_relationships is set
    if ($params['include_relationships'] && !empty($cidlist)) {

      $rquery = <<<SQL
SELECT DISTINCT crel.id, crel.contact_id_a, crel.contact_id_b, creltype.name_a_b, creltype.label_a_b,
  contact_b.display_name AS contact_name_b, crel.start_date, crel.end_date, crel.is_active
  FROM civicrm_relationship crel
  LEFT JOIN civicrm_relationship_type creltype ON crel.relationship_type_id = creltype.id
  LEFT JOIN civicrm_contact contact_b ON crel.contact_id_b = contact_b.id
  WHERE crel.contact_id_a IN ({$cidlist}) AND crel.is_active = 1
SQL;
      $rres = \CRM_Core_DAO::executeQuery($rquery);

      /** @var \DB_DataObject $mres */
      while ($rres->fetch()) {

        // Get relationship array
        $rel = static::daoToArray($rres);

        // Find contact and add relationship data
        $contact = &$contacts[$rel['contact_id_a']];
        if (!isset($contact['relationships'])) {
          $contact['relationships'] = [];
        }
        $contact['relationships'][] = $rel;
      }
    }
    if ($rres instanceof \CRM_Core_DAO) {
      $rres->free();
    }

    // Remove array keys is params.sequential = 1
    if ($params['sequential'] == 1) {
      $contacts = array_values($contacts);
    }

    // Return contacts
    return $contacts;
  }

  /**
   * Set custom permissions per API method here (called from spcustomapi.php)
   * @param array $permissions API permissions array
   */
  public static function alterAPIPermissions($entity, $action, &$params, &$permissions = []) {
  	if (strtolower($entity) == 'contact' && strtolower($action) == 'getspdata') {
  		if (empty($params['include_non_menmbers']) && empty($params['include_non_members'])) {
  			$permissions['contact']['getspdata'] = ['access CiviCRM'];
  		} else {
				$permissions['contact']['getspdata'] = ['access to contact.getspdata api'];	
  		}
		}
    
  }
	
	/** 
	 * Adds another permission to access the contact.getspdata api
	 */
  public static function getExtraPermissions(&$permissions) {
    $permissions['access to contact.getspdata api'] = ts('CiviCRM') . ': ' . ts('Access to Contact.Getspdata API');
  }

  /**
   * Get SP staff relationship type ids
   * @return array Array of relationship type ids
   */
  private static function getSPSpecialRelationshipTypes() {

    $relationshipTypes = \CRM_Core_PseudoConstant::relationshipType('name');
    $ret = [];

    foreach ($relationshipTypes as $rtype) {
      if (in_array($rtype['name_a_b'], ['sprel_personeelslid_amersfoort_landelijk', 'sprel_personeelslid_denhaag_landelijk', 'sprel_personeelslid_brussel_landelijk', 'sprel_bestelpersoon_landelijk'])) {
        $ret[] = $rtype['id'];
      }
    }

    return $ret;
  }

  /**
   * Get an array of names of SP afdelingen / regio's / provincies
   * @return array Array of SP geostelsel names
   */
  private static function getSPGeostelselNames() {

    $res = \CRM_Core_DAO::executeQuery("SELECT id, display_name FROM civicrm_contact WHERE contact_sub_type IN ('SP_Landelijk','SP_Provincie','SP_Regio','SP_Afdeling','SP_Werkgroep')");
    $ret = [];

    while ($res->fetch()) {
      $ret[$res->id] = str_ireplace(['SP-afdeling ', 'SP-werkgroep ', 'SP-regio ', 'SP-provincie '], '', $res->display_name);
    }

    return $ret;
  }

  /**
   * Convert DAO object to an array, removing private properties
   * (is there a better way to get all properties without having to specify them individually?)
   * @param \DB_DataObject $object Data object
   * @return array Data array
   */
  private static function daoToArray($object) {
    $ret = (array) $object;
    foreach ($ret as $k => $v) {
      if (substr($k, 0, 1) == '_' || $k == 'N' || !isset($v) || $v === "") {
        unset($ret[$k]);
      }
    }
    return $ret;
  }
}

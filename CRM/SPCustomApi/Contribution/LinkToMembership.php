<?php

class CRM_SPCustomApi_Contribution_LinkToMembership {

  const NO_ACTIVE_MEMBERSHIP_FOUND = 'No active membership found';
  const CORRECTED_CONTRIBUTION = 'Corrected contribution';

  public static function LinkToMembership($source, $membership_type_ids, $limit=250) {
    $contributions = CRM_Core_DAO::executeQuery("
      SELECT c.id as id, c.contact_id as contact_id
      FROM `civicrm_contribution` c
      LEFT JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
      WHERE mp.id IS NULL and c.source = %1 LIMIT 0, %2
      ", array(
        1=>array($source, 'String'),
        2=>array($limit, 'Integer'),
    ));

    $return = array();
    while($contributions->fetch()) {
      $status = static::linkContribution($contributions->id, $contributions->contact_id, $membership_type_ids);
      $return[$contributions->id] = array(
        'contact_id' => $contributions->contact_id,
        'id' => $contributions->id,
        'status' => $status,
      );
    }
    return $return;
  }

  protected static function linkContribution($contribution_id, $contact_id, $membership_type_ids) {
    $membership_id = static::findActiveMembershipId($contact_id, $membership_type_ids);
    if (empty($membership_id)) {
      return static::NO_ACTIVE_MEMBERSHIP_FOUND;
    }

    $sql = "INSERT INTO `civicrm_membership_payment` (`membership_id`, `contribution_id`) VALUES(%1, %2)";
    $params = array();
    $params[1] = array($membership_id, 'Integer');
    $params[2] = array($contribution_id, 'Integer');
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "UPDATE `civicrm_contribution` SET `source` = '' WHERE `id` = %1";
    $params = array();
    $params[1] = array($contribution_id, 'Integer');
    CRM_Core_DAO::executeQuery($sql, $params);

    return static::CORRECTED_CONTRIBUTION;
  }

  protected static function findActiveMembershipId($contact_id, $membership_type_ids) {
    foreach($membership_type_ids as $membership_type_id) {
      $membership = static::getContactMembership($contact_id, $membership_type_id);
      if ($membership) {
        return $membership['id'];
      }
    }
    return false;
  }

  protected static function getContactMembership($contact_id, $membership_type_id) {
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $contact_id;
    $dao->membership_type_id = $membership_type_id;
    $dao->whereAdd('is_test IS NULL OR is_test = 0');

    $dao->whereAdd("status_id IN (".implode(", ",CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent()).")");

    // order by start date to find most recent membership first, CRM-4545
    $dao->orderBy('start_date DESC');

    if ($dao->find(TRUE)) {
      $membership = array();
      CRM_Core_DAO::storeValues($dao, $membership);
      $membership['is_current_member'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
        $membership['status_id'],
        'is_current_member', 'id'
      );
      return $membership;
    }
    return false;
  }

}
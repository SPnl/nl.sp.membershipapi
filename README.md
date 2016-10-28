nl.sp.spcustomapi
=================

This extension contains custom CiviCRM API methods specific for the SP.  
(Formerly known as nl.sp.membershipapi.) 

Membership API
--------------

Adds an action to the membership API: **Membership.SPCreate**.  
This action creates a new SP and/or ROOD membership, and automatically adds an IBAN account and a SEPA mandate. This method is used by the member signup forms on the website.

| Parameter  | Required  | Default value | Description |
|---|---|---|---|
| contact_id   | y  |   |   |
| membership_type_id   | y  |   |   |
| membership_start_date  | n  |   |   |
| membership_end_date   | n  |   |   |
| join_date   | n  |   |   |
| status_id   | n  |   |   |
| is_override  | n  |   |   |
| num_terms   | n  |   |   |
| new_mandaat | n | 0 | 0 = don't create a new SEPA mandaat, 1 = create new SEPA mandaat |
| mandaat_status | yes when new_mandaat is set |   | FRST, RCUR, OOFF, NEW |
| mandaat_datum | yes when new_mandaat is set |   |   |
| mandaat_plaats | yes when new_mandaat is set |   |   |
| mandaat_omschrijving | n |   |   |
| iban | yes when new_mandaat is set |   | you can provide iban details when you don't create a SEPA mandaat. The iban details will be printed on the acceptgiro.  |
| bic | n |   |   |
| total_amount | y |   | the total amount for the membership payment |
| financial_type_id | y |   |   |
| contribution_status_id   | n  |   |   |
| payment_instrument_id | n  |   |   |


Contact API
-----------

Adds an action to the contact API: **Contact.GetSPData**.  
This action returns the result of a query that contains all contact information including current membership information. The query is very similar to the one used in LegacyExport.Generate.
This method currently supports a few specifically defined filters. If no filters are set, this method will return *all* SP and ROOD *members* that are accessible to the user using the API.  
The API will return SP staff contacts that aren't SP members if 'include_spspecial' is set to 1. It will return detailed membership data for all active memberships if 'include_memberships' is set to 1, and active relationships if 'include_relationships' is set to 1.  
The options 'limit', 'offset' and 'sequential' are also supported.

| Parameter | Required | Default value | Description |
|---|---|---|---|
| contact_id | n | | Contact ID (integer) |
| group | n | | The ID of the group to which the contact should belong (or an array of group ids)
| city | n | | City (woonplaats - string or array of strings) |
| gemeente | n | | Gemeente (string or array of strings) |
| geo_code_1 | n | | Latitude (between - array of two floats) |
| geo_code_2 | n | | Longitude (between - array of two floats) |
| include_spspecial | n | 0 | Include SP staff who aren't members |
| include_memberships | n | 0 | Include SP membership data |
| include_relationships | n | 0 | Include SP relationship data |
| include_non_menmbers | n | 0 | Include all contacts even if they are not a member |
| options.limit | n | 25 | Limit |
| options.offset | n | 0 | Offset | 
| sequential | n | 0 | Sequential |



Contribution API
----------------

Adds an action to the contribution API: **Contribution.LinkToMembership**.  
This API function searches for all contributions based on the _source_ field and links them to an active membership. The source field is emptied afterwards. If a contribution has already been linked to a membership, it will be skipped.

| Parameter | Required | Default value | Description |
|---|---|---|---|
| source | y | | Source field for the contributions we're looking for |
| membership_type_id | n | 0 | A comma separated list of membership type ids |
| limit | n | 200 | Optional limit |

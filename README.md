nl.sp.membershipapi
===================

Custom membership API for the SP

This extension adds an action to the membership API: **spcreate**

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


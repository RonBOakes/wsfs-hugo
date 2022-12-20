SELECT `registration_data`.`registrations`.`REG_ID`    AS `member_number`,
       `registration_data`.`registrations`.`ATT_fname` AS `first_name`,
       `registration_data`.`registrations`.`ATT_lname` AS `last_name`,
       `registration_data`.`registrations`.`ATT_email` AS `email`

FROM `registration_data`.`registrations` 
WHERE 1;

SELECT `registration_data`.`registrations`.`TXN_ID`    AS `member_number`,
       `registration_data`.`registrations`.`ATT_fname` AS `first_name`,
       `registration_data`.`registrations`.`ATT_lname` AS `last_name`,
       `registration_data`.`registrations`.`ATT_email` AS `email`,
       `registration_data`.`registrations`.`TKT_name`  AS `member_type`
FROM `registration_data`.`registrations` 
WHERE 1;

SELECT `registration_data`.`registrations`.`TXN_ID`    AS `member_number`,
       `registration_data`.`registrations`.`ATT_fname` AS `first_name`,
       `registration_data`.`registrations`.`ATT_lname` AS `last_name`,
       `registration_data`.`registrations`.`ATT_email` AS `email`,
       TRIM(LEFT(`registration_data`.`registrations`.`TKT_name`, 
                 (LOCATE('(',`registration_data`.`registrations`.`TKT_name`) - 1))) 
                                                       AS `member_type`
FROM `registration_data`.`registrations` 
WHERE LEFT(`registration_data`.`registrations`.`TKT_name`, 
            (LOCATE('(',`registration_data`.`registrations`.`TKT_name`) - 1))
               IN ('Spporting','Attending','Young Adult','Staff Attending');


SELECT `member_number`,
       `first_name`,
       `last_name`,
       `email`,
       `member_type`,
       `transaction_timestamp`
FROM `registration_data`.`clean_member_data_with_timestamp` 
WHERE  `member_number` > 5010
  AND  `member_number` NOT IN (SELECT `member_number`FROM `hugo_nom_members`.`membership` WHERE 1)
  AND  `transaction_timestamp` > (SELECT `last_transfer_time` FROM `hugo_nom_members`.`last_transfer_time` ORDER BY `last_transfer_time` DESC LIMIT 1)


SELECT ((`registrations`.`TXN_ID` * 10) + (`registrations`.`REG_count`))    AS `member_number`,
       `registration_data`.`registrations`.`ATT_fname` AS `first_name`,
       `registration_data`.`registrations`.`ATT_lname` AS `last_name`,
       `registration_data`.`registrations`.`ATT_email` AS `email`,
       `registration_data`.`registrations`.`TKT_name`  AS `member_type`
FROM `registration_data`.`registrations`
WHERE  STR_TO_DATE(`registrations`.`TXN_timestamp`,'%Y-%m-%d %H:%i:%S') > '2016-05-23 02:30:00'
  AND  `registrations`.`STS_Code` LIE 'APPROVED';


SELECT ((`registrations`.`TXN_ID` * 10) + (`registrations`.`REG_count`)) AS `member_number`,
       `registrations`.`ATT_fname` AS `first_name`,
       `registrations`.`ATT_lname` AS `last_name`,
       `registrations`.`ATT_email` AS `email`,
       `registrations`.`TKT_name` AS `member_type`,
       STR_TO_DATE(`registrations`.`TXN_timestamp`,'%Y-%m-%d %H:%i:%S') AS `transaction_timestamp` 
FROM   `registrations`
WHERE  STR_TO_DATE(`registrations`.`TXN_timestamp`,'%Y-%m-%d %H:%i:%S') > '2016-05-23 02:30:00'
  AND  `registrations`.`STS_Code` LIKE 'APPROVED';

SELECT DISTINCT `member_id` 
FROM `hugo`.`hugo_ballot_entry`

SELECT DISTINCT `member_id` 
FROM `retro_hugo`.`hugo_ballot_entry`

SELECT `pin`,`member_number`,`firstname`,`lastname`,`email`
FROM `hugo_nom_members`.`membership` 
WHERE `hugo_nom_members`.`membership`.`pin` IN (SELECT DISTINCT `member_id` FROM `hugo`.`hugo_ballot_entry`)
   OR `hugo_nom_members`.`membership`.`pin` IN (SELECT DISTINCT `member_id` FROM `retro_hugo`.`hugo_ballot_entry`)
ORDER BY `member_number` ASC


SELECT DISTINCT `member_id`
FROM   `hugo`.`hugo_ballot_entry`;

SELECT `pin`,
       `member_number`,
       `firstname`,
       `lastname`,
       `email`
FROM `hugo_nom_members`.`membership` 
WHERE `pin` IN (SELECT DISTINCT `member_id`
                FROM   `hugo`.`hugo_ballot_entry`)

SELECT `pin`,
       `member_number`,
       `firstname`,
       `lastname`,
       `email`
FROM `hugo_nom_members`.`membership` 
WHERE `pin` IN (SELECT DISTINCT `member_id`
                FROM   `retro_hugo`.`hugo_ballot_entry`)

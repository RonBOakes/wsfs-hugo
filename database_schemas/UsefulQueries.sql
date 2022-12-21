SELECT `memberkey`,`pin`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `pin` IN (SELECT `pin` FROM (SELECT `pin`,COUNT(`memberkey`) AS `pin_count` FROM `membership` GROUP BY `pin`
ORDER BY `pin_count`  DESC) AS `pin_counts` WHERE `pin_count` > 1) ORDER BY `pin`, `member_number`;

SELECT `memberkey`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `member_number` IN (SELECT `member_number` FROM (SELECT `member_number`,COUNT(`memberkey`) AS `member_count` FROM (SELECT * FROM `membership` WHERE `source` = 'MidAmericon2') AS `Mac2Members` GROUP BY `member_number`
ORDER BY `member_count`  DESC) AS `member_counts` WHERE `member_count` > 1) AND `source` = 'MidAmericon2' ORDER BY `member_number`

-- Saved queries from 2016 that were useful.  These may or may not apply
-- Copyright (C) 2016,2024 Ronald B. Oakes
--
-- This program is free software: you can redistribute it and/or modify it
-- under the terms of the GNU General Public License as published by the Free
-- Software Foundation, either version 3 of the License, or (at your option)
-- any later version.
--
-- This program is distributed in the hope that it will be useful, but WITHOUT
-- ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
-- FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
-- more details.
--
-- You should have received a copy of the GNU General Public License along with
-- this program.  If not, see <http://www.gnu.org/licenses/>.

SELECT `memberkey`,`pin`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `pin` IN (SELECT `pin` FROM (SELECT `pin`,COUNT(`memberkey`) AS `pin_count` FROM `membership` GROUP BY `pin`
ORDER BY `pin_count`  DESC) AS `pin_counts` WHERE `pin_count` > 1) ORDER BY `pin`, `member_number`;

SELECT `memberkey`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `member_number` IN (SELECT `member_number` FROM (SELECT `member_number`,COUNT(`memberkey`) AS `member_count` FROM (SELECT * FROM `membership` WHERE `source` = 'MidAmericon2') AS `Mac2Members` GROUP BY `member_number`
ORDER BY `member_count`  DESC) AS `member_counts` WHERE `member_count` > 1) AND `source` = 'MidAmericon2' ORDER BY `member_number`

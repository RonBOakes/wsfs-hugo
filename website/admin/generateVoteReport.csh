#! /bin/bash

rm /home3/swocorg/public_html/sasquan/hugo/admin/voteReport.html
rm /home3/swocorg/public_html/sasquan/hugo/admin/voteReport_errors.txt
php -f /home3/swocorg/public_html/sasquan/hugo/admin/voteReport.php > /home3/swocorg/public_html/sasquan/hugo/admin/voteReport.html 2> /home3/swocorg/public_html/sasquan/hugo/admin/voteReport_errors.txt


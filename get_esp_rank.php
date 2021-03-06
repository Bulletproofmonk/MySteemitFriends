<?php

// connect to SteemSQL database
include 'steemSQLconnect2.php';		

// retrieve global values for calculating Steem Power
$my_file = fopen("global.txt",'r');
$total_vesting_fund_steem=fgets($my_file);
$total_vesting_fund_steem = preg_replace('/[^0-9.]+/', '', $total_vesting_fund_steem);
$total_vesting_shares=fgets($my_file);
$total_vesting_shares = preg_replace('/[^0-9.]+/', '', $total_vesting_shares);
fclose($my_file);

// sanitize input for Steemit UserName
$SteemitUser = $_GET["SteemitUser"];
$SteemitUser = filter_var($SteemitUser, FILTER_SANITIZE_STRING);

// SQL for finding rank of Steemit User for Effective SP
$sql = "
;With cte as
(
select effective_vests, RANK() over (order by effective_vests DESC) AS RankByESP, name
from
(
SELECT TOP 100 PERCENT a.name AS name, convert(float, a.vesting_shares)-convert(float,a.delegated_vesting_shares)+convert(float,a.received_vesting_shares) AS effective_vests
FROM
(SELECT name, Substring(vesting_shares,0,PATINDEX('%VESTS%',vesting_shares)) AS vesting_shares, Substring(delegated_vesting_shares,0,PATINDEX('%VESTS%',delegated_vesting_shares)) AS delegated_vesting_shares, Substring(received_vesting_shares,0,PATINDEX('%VESTS%',received_vesting_shares)) AS received_vesting_shares
FROM Accounts (NOLOCK)) a
Order by effective_vests DESC
) b
)
select effective_vests, RankByESP
from cte
where name=:name;

";

    
// Prepare the SQL query to prevent SQL injection. Bind values to parameters after statement is prepared.

    $sth = $conn->prepare($sql);

    $sth -> bindValue(':name', $SteemitUser, PDO::PARAM_STR);

    $sth->execute();


    // print the results. If successful, magicmonk will be printed on page.

    while ($row = $sth->fetch(PDO::FETCH_NUM)) {

           $vests=$row[0];
			$sp = $total_vesting_fund_steem * $vests / $total_vesting_shares;
           $rank=$row[1];

        }

	


echo "<p>".$SteemitUser." has ".number_format(round($sp))." effective SP and is ranked at  ".$rank.".</p>";



$page = ceil($rank/50);

echo "<p><a href=effectiveSP.php?page=".$page."&highlight=".$SteemitUser.">".$SteemitUser." is on page ".$page.". Click to see this page</a>.</p>";



// terminate connectiion

unset($conn); unset($sth);

  

?>  


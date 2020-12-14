<?php
##Not using anywhere delete after done testing
##Nitin Jadhav
##SpotPricing BSE Gold
include_once dirname(dirname(__FILE__)) .'/includes/common.php';
include_once BASE_PATH.'/includes/simple_html_dom.php';
include_once BASE_PATH.'/includes/inc.common.nseit.php';
include_once BASE_PATH.'/includes/MysqliDb.php';


$mysqli = new Mysqlidb (DB_HOST_INT, DB_USER_INT, DB_PASSWORD_INT, DB_NAME_INT);

$response = $harvest->curlRequest("https://www.bseindia.com/markets/Commodity/PolledSpotPrice.aspx");


//
//Note Above part i am using in server // u can use ur code upto this in local
//



$html= new simple_html_dom();
$html->load($response);

//following part is what i have changes made it dynamic for every value from tabel // so no need to take each td separately
$list = $html->find('table[class="mGrid"]',0);
$dataArray = array();
foreach($list->find('tr') as $element)
{
	$subDataArray = array();
	$cnt = 1;
	foreach ($element->find('td')  as $value) {
		$subDataArray[$cnt] = trim($value->plaintext);
		$cnt++;
	 }
	 $cnt = 1;

	 if(count($subDataArray) > 0)
	 	$dataArray[] = $subDataArray;

}
// here u will get all rows data in array
//print_r($dataArray);

//following code will filter what we want for gold ot silver
// we could do this filteration part  above only but requirements can be change so better to take all data and do filteraton on it.
$outputArray = array();
foreach ($dataArray as $key => $value) {
	if($value[2] == "GOLD" || $value[2] == "SILVER"){
		//$sessionValue = (($key == 0) ? "one" : "two");
		//$inputArray[$value[2]][$sessionValue]= $value;
		$outputArray[$value[2]][]= $value;
	}
}

echo "final Array";
print_r($outputArray);

// insert data in database will come now  by using foreach to $outputArray // 0 key means session 1 and key 1 means session 2

foreach ($outputArray as $key => $bseArray) {

		foreach ($bseArray as $bsekey => $bsevalue) {
				print_r($bsevalue);
				$session = (($bsekey == 0) ? 1 : 2);
				echo strtotime($bsevalue[1]);

				//check for same date with session value exists or not , update if yes or insert if not.
				$mysqli->where("post_date",strtotime($bsevalue[1]));
				$mysqli->where("source","bse");
				$mysqli->where("commodity",$key);
				$mysqli->where("session",$session);
				$bse_exists = $mysqli->getOne("confab_intext_nseit.nse_spot_prices");
				echo $mysqli->getLastQuery();
				echo "entry";
				print_r($bse_exists);

				if(isset($bse_exists) && count($bse_exists) > 1){
					$bse_update_data = array(
						'unit' => $bsevalue[3],
						'price' => $bsevalue[4],
						'updated_time' => $bsevalue[5],
						'post_time' => strtotime($bsevalue[1]." ".$bsevalue[5]),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->where("id",$bse_exists['id']);
					$mysqli->update("confab_intext_nseit.nse_spot_prices", $bse_update_data);
					echo $mysqli->getLastQuery();
					echo " Updated Succsessfully for $key with date $bsevalue[1]. ";
				}else{
					$bse_data = array(
						'source' => 'bse',
						'commodity' => $key,
						'unit' => $bsevalue[3],
						'price' => $bsevalue[4],
						'session' => (($bsekey == 0) ? 1 : 2),
						'raw_date' => $bsevalue[1],
						'updated_time' => $bsevalue[5],
						'post_date' => strtotime($bsevalue[1]),
						'post_time' => strtotime($bsevalue[1]." ".$bsevalue[5]),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->setQueryOption("IGNORE");
					$int_bse_id = $mysqli->insert("confab_intext_nseit.nse_spot_prices",$bse_data,1);
					echo "<pre>";
					print_r($bse_data);
				}





			//exit;
		}
}

echo "Done";


?>
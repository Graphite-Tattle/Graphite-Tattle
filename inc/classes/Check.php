<?
class Check extends fActiveRecord
{
    protected function configure()
    {
    }
	/**
	 * Returns all checks on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll($sort_column = 'name', $sort_dir = 'desc')
	{
		if (!in_array($sort_column, array('name', 'owner','target','visibility'))) {
			$sort_column = 'name';
		} 
		
		if (!in_array($sort_dir, array('asc', 'desc'))) {
			$sort_dir = 'desc';
		}
		
       return fRecordSet::build(
          __CLASS__,
          array('enabled=' => true),
          array($sort_column => $sort_dir)
          );
	}    

	/**
	 * Returns all active checks on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findActive($sort = "",$sort_by = "")
	{
       return fRecordSet::buildFromSQL(
          __CLASS__,
          array("SELECT checks.* FROM checks JOIN subscriptions ON checks.check_id = subscriptions.check_id WHERE enabled = 1;")
          );
	}    

	static function findUsersActive()
	{
       return fRecordSet::buildFromSQL(
          __CLASS__,
          array("SELECT checks.* FROM checks WHERE enabled = 1 AND (user_id = " . fSession::get('user_id') . ' OR visibility = 0);')
          );
	}    

        /**
	 * Creates all Check related URLs for the site
	 * 
	 * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
	 * @param  Check $obj   The Check object for the edit and delete URL types
	 * @return string  The URL requested
	 */
	static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'check.php';
			case 'add':
				return 'check.php?action=add';
			case 'edit':
				return 'check.php?action=edit&check_id=' . $obj->prepareCheckId();
			case 'delete':
				return 'check.php?action=delete&check_id=' . $obj->prepareCheckId();
		}	
	}   
    
        static public function deleteRelated($obj=NULL)
	{
	  if (!is_null($obj)) {
	    $subscriptions = Subscription::getAll($obj->getCheckId());
	    foreach ($subscriptions as $subscription) {
	      $subscription->delete();
	    } 
 
	    $check_results = CheckResult::getAll($obj->getCheckId());
	    foreach ($check_results as $check_result) {
	      $check_results->delete();
	    }
	  }
        } 

        static public function acknowledgeCheck($check=NULL,$result=NULL,$ackAll=false)
	{
	  if (!is_null($check)) {
            if ($ackAll === true) {
    	      $check_results = CheckResult::findAll($check->getCheckId());
            } elseif (!is_null($result)) {
              $check_results = CheckResult::build($result->getResultId());
            }
	    foreach ($check_results as $check_result) {
            fCore::expose($check_result);
             $check_result->setAcknowledged(1);
             $check_result->store();
	    }
	  }
        }       
        /**
	 * Requests Graphite Data for check
	 * 
	 * @param  Check $obj   The Check object to get the graphite data for
	 * @return array either a Graphite json_data array or an empty one  
	 */
	static public function getData($obj=NULL)
	{
	
	  if ( $GLOBALS['SOURCE_ENGINE'] == "GANGLIA" ) {
	      $parts = explode("_|_", $obj->prepareTarget());
	      $check_url = $GLOBALS['GANGLIA_URL'] . "/graph.php?json=1&cs=". $obj->prepareSample() . "&ce=now&c=" . 
		$parts[0] . "&h=" . $parts[1] . "&m=" . $parts[2];	      
	    } else {
              $check_url = $GLOBALS['GRAPHITE_URL'] . '/render/?target=' . $obj->prepareTarget() . '&from='. $obj->prepareSample() . '&format=json';		
	    }
          $json_data = @file_get_contents($check_url);
          if ($json_data) {
            $data = json_decode($json_data);
          } else {
            $data = array();
          }
      return $data;
	}   
    
        /**
	 * Creates all Check related URLs for the site
	 * 
	 * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
	 * @param  Meetup $obj   The Check object for the edit and delete URL types
	 * @return string  The URL requested
	 */
	static public function getResultValue($data,$obj=NULL)
	{
          $value = false;
          //print_r($data[0]->datapoints);
          if ($obj->getBaseline() == 'average') {
            //print_r($data[0]->datapoints);
            $value = subarray_average($data[0]->datapoints);
          } elseif ($obj->getBaseline() == 'median') {
            //print_r($data[0]->datapoints);
            $value = subarray_median($data[0]->datapoints);
          } 
         
     return $value;
        }        
  
        /**
	 * Creates all Check related URLs for the site
	 * 
	 * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
	 * @param  Meetup $obj   The Check object for the edit and delete URL types
	 * @return string  The URL requested
	 */
	static public function setResultsLevel($value,$obj=NULL)
	{
          if ($obj->getOverUnder() == 0) {
            if ($value >= $obj->getError()) { 
              $state = 1;
            } elseif ($value >= $obj->getWarn()) { 
              $state = 2;
            } else { 
              //echo 'all good ' . " $value <br />";
              $state = 0;
            }
            return $state;
         } 
         
          if ($obj->getOverUnder() == 1) {
            if ($value >= $obj->getWarn()) { 
              $state = 0;
            } elseif ($value >= $obj->getError()) { 
              $state = 1;
            } else { 
               fCore::debug('error state' . " $value compared to " . $obj->getError() . "<br />",FALSE); 
              $state = 2;
            }
            return $state;
         }      
        }
           
        /**
	 * Creates all Check related URLs for the site
	 * 
	 * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
	 * @param  Meetup $obj   The Check object for the edit and delete URL types
	 * @return string  The URL requested
	 */
	static public function showGraph($obj=NULL,$img=true,$sample=false,$width=false,$hideLegend=false) 
        {
          if ($img) {  
            $link = '<img src="';
          } else {
            $link = '<a href="';
          }

	  if ( $GLOBALS['SOURCE_ENGINE'] == "GANGLIA" ) {

	    $parts = explode("_|_", $obj->prepareTarget());
	    $link  .= $GLOBALS['GANGLIA_URL'] . "/graph.php?json=1&ce=now&c=" . 
	      $parts[0] . "&h=" . $parts[1] . "&m=" . $parts[2];

	    if ($sample !== False) {
	      $link .= '&cs=' . $sample;
	    } else {
	      $link .= '&cs=' . $obj->prepareSample();
	    }

	  } else {

	    $link .=  $GLOBALS['GRAPHITE_URL'] . '/render/?';
	    $link .= 'target=legendValue(alias(' . $obj->prepareTarget() . '%2C%22Check : ' . $obj->prepareName() .'%22),%22last%22)';
	    //$link .= 'target=legendValue(' . $obj->prepareTarget() .',%22last%22)'; 
	    if ($sample !== False) {
	      $link .= '&from=' . $sample;
	    } else {
	      $link .= '&from=' . $obj->prepareSample();
	    }
	    if ($width !== false) {
	      $link .= '&width=' .$width;
	    } else {
	      $link .= '&width=' .GRAPH_WIDTH;
	    }
	    $link .= '&height=' .GRAPH_HEIGHT;
	    $link .= '&target=color(alias(threshold('. $obj->getError() . ')%2C%22Error%20('. $obj->getError() . ')%22)%2C%22' . ERROR_COLOR . '%22)';
	    $link .= '&target=color(alias(threshold('. $obj->getWarn() . ')%2C%22Warning%20('. $obj->getWarn() . ')%22)%2C%22' . WARN_COLOR . '%22)';
	    if ($hideLegend !== false) { 
	      $link .= '&hideLegend=true';
	    } 
	  } // end of if ( $GLOBALS['SOURCE_ENGINE'] == "GANGLIA" ) {


	  if ($img) {  
	    $link .= '" title="' . $obj->prepareName() . '" alt="' . $obj->prepareName();
	    $link .= '" />';
	  } else {
	    $link .= '"> ' . $obj->prepareTarget() .'</a>';
	  }

	  return $link;
	}
     
}

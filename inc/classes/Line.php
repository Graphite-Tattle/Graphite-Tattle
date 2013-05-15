<?
class Line extends fActiveRecord
{
    protected function configure()
    {
    }
    
  /**
	 * Returns all meetups on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll($graph_id=NULL)
	{
       return fRecordSet::build(
          __CLASS__,
          array('graph_id=' =>$graph_id),
          array()
          );
	}   
    
    static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'lines.php';
			case 'add':
				return 'lines.php?action=add&graph_id=' . $obj->getGraphId();
			case 'edit':
				$id = $obj->prepareLineId();
				return 'lines.php?action=edit&line_id=' . (empty($id)?'':(new fNumber($id))->__toString());
			case 'delete':
				$id = $obj->prepareLineId();
				return 'lines.php?action=delete&line_id=' . (empty($id)?'':(new fNumber($id))->__toString());
			case 'list':
				$id = $obj->prepareLineId();
				return 'lines.php?action=list&line_id=' . (empty($id)?'':(new fNumber($id))->__toString());
                
		}	
	}
    
}
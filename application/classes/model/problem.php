<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * problems handle for hust online judge
 *
 * @author freefcw
 */

class Model_Problem extends Model_Database {

    public function get_problem($pid)
    {
        $key = 'problem-'. $pid;
        $cache = Cache::instance();
        $data = $cache->get($key);
        if($data != null) return $data;
        
        //fetch data
        $query = DB::select()
            ->from('problem')
            ->where('problem_id', '=', $pid);
        
        $result = $query->as_object()->execute();

        $ret = $result->current();
        $cache->set($key, $ret, array('problem'));

        return $ret;
    }

    public function get_page($page_id, $per_page)
    {
        $key = 'problem-page-'. $page_id;
        $cache = Cache::instance();
        $data = $cache->get($key);
        if($data != null) return $data;

        //fetch data
        /*$sql = 'SELECT title from problem limit 100';
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);
        foreach($result as $r) {
            echo $r->title, '<br />';
        }*/

        $query = DB::select('problem_id', 'title', 'accepted', 'submit')
            ->from('problem')
            ->offset(($page_id - 1) * $per_page)
            ->limit($per_page)
            ->order_by('problem_id');

        $result = $query->as_object()->execute();

        $ret = array();
        foreach($result as $r){
            $ret[] = $r;
        }
        $cache->set($key, $ret, array('problem', 'page'));
        return $ret;
    }
    /**
    * return total problems
    *
    *  @author freefcw
    *  @return int
    */
    public function get_total()
    {
        $key    = 'problem-total';
        $cache  = Cache::instance();
        $data   = $cache->get($key);
        if ($data != null) return $data;
        
        $sql = 'SELECT count(*) AS total FROM problem';
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);

        $ret = $result->current()->total;

        $cache->set($key, $ret, array('problem','total'));
        return $ret;
    }

    /**
     * get recent problem information for index page
     *
     * @return array
     */
    public function get_recent()
    {
        $key    = 'problem-index';
        $cache  = Cache::instance();
        $data   = $cache->get($key);
        if ($data != null) return $data;

        //fetch data
        $query = DB::select('problem_id', 'title', 'in_date')
            ->from('problem')
            ->limit(5)
            ->order_by('in_date', 'DESC');

        //TODO: fixit
        $result = $query->as_object()->execute();
        $cache->set($key, $result, array('problem', 'page'));
        return $result;
    }

    public function get_status($page_id = 1, $problem_id = '', $user_id = '', $language = -1, $result = -1)
    {
        // fixme: add more set
        $query = DB::select('solution_id', 'problem_id', 'user_id', 'time', 'memory', 'language', 'result', 'code_length', 'in_date')
                ->from('solution')
                ->offset(($page_id - 1) * 20)
                ->limit(20)
                ->order_by('solution_id', 'DESC');
                
        if (!(($problem_id == '') AND ($user_id == '') AND ($language == -1) AND ($result == -1)))
	    {
	        $query->where_open();
	        if ($problem_id != '')
		    {
		    	$query->where('problem_id', '=', $problem_id);
		    }
		    if ($user_id != '')
		    {
		    	$query->where('user_id', '=', $user_id);
		    }
		    if ($language != -1)
	        {
	        	$query->where('language', '=', $language);
	        }
	        if ($result != -1)
	        {
	        	$query->where('result', '=', $result);
	        }
	        $query->where_close();
    	}

        $result = $query->as_object()->execute();

        $ret = array();
        foreach($result as $r){
            $ret[] = $r;
        }

        return $ret;
    }

    public function get_summary($pid)
    {
        # TODO: add content
        $key    = "summary-{$pid}";
        $cache  = Cache::instance();
        $data   = $cache->get($key);
        if ($data != null){
            return $data;
        }

        $data = array();
        // get total solutions
        $sql = "SELECT count(*) AS total FROM solution WHERE problem_id='{$pid}'";
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);
        $data['total'] = $result->current()->total;

        // get total user has submited
        $sql = "SELECT count(DISTINCT user_id) AS total FROM solution WHERE problem_id='{$pid}'";
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);
        $data['submit_user'] = $result->current()->total;

        // get total user has ac
        $sql = "SELECT count(DISTINCT user_id) AS total FROM solution WHERE problem_id='{$pid}'  AND result='4'";
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);
        $data['ac_user'] = $result->current()->total;

        // get all status
        $sql = "SELECT result, count(*) as total FROM solution WHERE problem_id='{$pid}' AND result>=4 GROUP BY result ORDER BY result";
        $result = $this->_db->query(Database::SELECT, $sql, TRUE);
        $data['more'] = array();
        foreach($result as $r)
        {
            $data['more'][$r->result]= $r->total;
        }

        $cache->set($key, $data, array('problem', 'summary', $pid));
        return $data;
    }

    public function get_best_solution($pid)
    {
        # TODO: add content
    }
    
    public function find_problem($text, $area)
	{
		// TODO: add permission
        $key    = "search-$text-$area";
        $cache  = Cache::instance();
        $data   = $cache->get($key);
        if ($data != null){
            return $data;
        }
		$query = DB::select('problem_id', 'title', 'submit', 'accepted', 'source')
				->from('problem')
				->where($area, 'like', "%{$text}%")
				->order_by('problem_id');

        $result = $query->as_object()->execute();

        $ret = array();
        foreach($result as $r){
            $ret[] = $r;
        }
        
        $cache->set($key, $ret, array('search', $text, $area));
        return $ret;
	}
	
	public function get_status_count($problem_id = '', $user_id = '', $language = -1, $result = -1)
	{   
        $sql = 'SELECT count(*) AS total FROM solution';
        
		$append = '';
        if (!(($problem_id == '') AND ($user_id == '') AND ($language == -1) AND ($result == -1)))
	    {
			$append = ' WHERE (';
			$flag = FALSE;
	        if ($problem_id != '')
		    {
		    	$append = $append."`problem_id` = '{$problem_id}'";
		    	$flag = TRUE;
		    }
		    if ($user_id != '')
		    {
		    	if ($flag) $append = $append. ' AND ';
		    	$append = $append."`user_id` = '{$user_id}'";
		    	$flag = TRUE;
		    }
		    if ($language != -1)
	        {
	        	if ($flag) $append = $append. ' AND ';
	        	$append = $append."`language` = '{$language}'";
	        	$flag = TRUE;
	        }
	        if ($result != -1)
	        {
	        	if ($flag) $append = $append. ' AND ';
	        	$append = $append."`result` = '{$result}'";
	        	$flag = TRUE;
	        }
	        $append = $append. ')';
    	}
    	$sql = $sql. $append;

    	$result = $this->_db->query(Database::SELECT, $sql, TRUE);
    	$ret = $result->current()->total;
    	
    	return $ret;
	}
}
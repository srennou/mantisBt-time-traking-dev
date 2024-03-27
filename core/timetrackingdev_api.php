<?php

/**
 * convert hours to a time format [h]h:mm
 * @param string $p_hhmm Time (hh:mm)
 * @return integer integer of minutes
 * @access public
 */
function plugin_TimeTrackingDev_hours_to_hhmm($p_hours)
{
	$t_min = round($p_hours * 60);
	return sprintf('%02d:%02d', $t_min / 60, $t_min % 60);
}

/**
 * Returns an array of time tracking stats
 * @param int $p_project_id project id
 * @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @return array array of bugnote stats
 * @access public
 */
function plugin_TimeTrackingDev_stats_get_project_array($p_project_id, $p_from, $p_to, $user_id)
{
	$t_project_id = db_prepare_int($p_project_id);
	$t_to = date("Y-m-d", strtotime("$p_to") + SECONDS_PER_DAY - 1);
	$t_from = $p_from; //strtotime( $p_from ) 
	if ($t_to === false || $t_from === false) {
		error_parameters(array($p_from, $p_to));
		trigger_error(ERROR_GENERIC, ERROR);
	}
	$t_timereport_table = db_get_table('mantis_TimeTracking_data');
	$t_bug_table = db_get_table('bug');
	$t_user_table = db_get_table('user');
	$t_project_table = db_get_table('project');
	$t_categorie_table = db_get_table('category');

	$t_query = 'SELECT u.username,u.id as user_id,p.view_state,p.id as project_id, p.name as project_name, bug_id, expenditure_date, hours, timestamp, category, cat.name as bug_cat, info 
	FROM ' . $t_timereport_table . ' tr
	LEFT JOIN ' . $t_bug_table . ' b ON tr.bug_id=b.id
	LEFT JOIN ' . $t_user_table . ' u ON tr.user=u.id
	LEFT JOIN ' . $t_project_table . ' p ON p.id = b.project_id
	LEFT JOIN ' . $t_categorie_table . ' cat ON cat.id=b.category_id
	WHERE 1=1 ';

	db_param_push();
	$t_query_parameters = array();

	if (!is_blank($t_from)) {
		$t_query .= " AND (expenditure_date >= " . db_param() . " OR expenditure_date IS NULL)";
		$t_query_parameters[] = $t_from;
	}
	if (!is_blank($t_to)) {
		$t_query .= " AND (expenditure_date <= " . db_param() . " OR expenditure_date IS NULL)";
		$t_query_parameters[] = $t_to;
	}
	if (ALL_PROJECTS != $t_project_id) {
		//$t_query .= " AND b.project_id = " . db_param();
		$t_query .= " AND (b.project_id in 
		(select id from {project} where id in
		( select child_id from {project_hierarchy} where parent_id =" . db_param() . ") ) or b.project_id=  " . db_param() . " )";
		$t_query_parameters[] = $t_project_id;
		$t_query_parameters[] = $t_project_id;
	}
	$t_query .= " AND u.id = " . db_param();
	$t_query_parameters[] = $user_id;

	$t_query .= ' ORDER BY user, expenditure_date, bug_id';
	$t_results = array();
	//$t_project_where $t_from_where $t_to_where $t_user_where
	$t_dbresult = db_query($t_query, $t_query_parameters);
	while ($row = db_fetch_array($t_dbresult)) {
		if ($row['view_state'] == 50) {
			$t_queryfilter = 'SELECT access_level FROM mantis_project_user_list_mantis tr
				where user_id=' . $user_id . ' and project_id =' . $row['project_id'];
			$t_dbresultfilter = db_query($t_queryfilter);
			$userhasaccess = false;
			while ($rowfilter = db_fetch_array($t_dbresultfilter)) {

				$userhasaccess = true;
			}
			if (access_has_global_level(plugin_config_get('admin_threshold'))) {
				$userhasaccess = true;
			}
			if ($userhasaccess || strval($user_id) == $row['user_id']) {
				$t_results[] = $row;
			}
		} else {
			$t_results[] = $row;
		}
	}

	return $t_results;
}
function plugin_TimeTrackingDev_stats_get_project_infos($f_project_id, $user_id, $t_from = null, $t_to = null)
{
	$t_timereport_table = db_get_table('mantis_TimeTracking_data');
	$t_tma_table = db_get_table('project_tma');
	$t_bug_table = db_get_table('bug');
	$t_project_table = db_get_table('project');

	$t_query = 'SELECT tma.*, sum(ta.hours) as sum_time, p.name , p.id as id_project
	FROM ' . $t_project_table . ' p 
	LEFT JOIN ' . $t_bug_table . ' bug on p.id = bug.project_id
	LEFT JOIN ' . $t_timereport_table . ' ta on bug.id = ta.bug_id 
	LEFT JOIN ' . $t_tma_table . ' tma on tma.project_id = bug.project_id
	WHERE 1=1 ';
	if (!is_null($t_from) && !is_null($t_to)) {
		$t_query .= 'AND  (DATE(expenditure_date) >= DATE("' . $t_from . '") AND DATE(expenditure_date) <= DATE("' . $t_to . '") OR expenditure_date IS NULL) ';
	}

	if (ALL_PROJECTS != $f_project_id) {
		//$t_query .= " AND b.project_id = " . db_param();
		$t_query .= " AND (p.id in 
		(select id from {project} where id in
		( select child_id from {project_hierarchy} where parent_id =" . db_param() . ") ) or p.id=  " . db_param() . " )";
		$t_query_parameters[] = $f_project_id;
		$t_query_parameters[] = $f_project_id;
	}
	$t_query .= " AND user = " . db_param();
	$t_query_parameters[] = $user_id;
	$t_query .= 'group by p.id,tma.id';
	$t_results = array();
	$t_dbresult = db_query($t_query, $t_query_parameters);
	while ($row = db_fetch_array($t_dbresult)) {
		if ($row['view_state'] == 50) {
			$t_queryfilter = 'SELECT access_level FROM mantis_project_user_list_mantis tr
				where user_id=' . $user_id . ' and project_id =' . $row['id_project'];
			$t_dbresultfilter = db_query($t_queryfilter);
			$userhasaccess = false;
			while ($rowfilter = db_fetch_array($t_dbresultfilter)) {

				$userhasaccess = true;
			}
			if (access_has_global_level(plugin_config_get('admin_threshold'))) {
				$userhasaccess = true;
			}
			if ($userhasaccess || strval($user_id) == $row['user_id']) {
				$t_results[] = $row;
			}
		} else {
			if (isset($t_results[$row['name']])) {
				$t_results[$row['name']][] = $row;
			} else {
				$t_results[$row['name']] = $row;
			}
		}
	}

	return $t_results;
}


/**
 * Function that displays pie charts
 *
 * @param array $p_metrics       Graph Data.
 * @param bool $p_mantis_colors  True to use colors defined in Mantis config
 *                               {@see $g_status_colors}. By default use
 *                               standard color scheme
 *
 * @return void
 */
function graph_user_projects_pie($f_project_id, $t_from, $t_to, $user_id)
{
	static $s_id = 0;
	$t_timereport_table = db_get_table('mantis_TimeTracking_data');
	$t_bug_table = db_get_table('bug');
	$t_project_table = db_get_table('project');
	$p_mantis_colors = false;
	db_param_push();
	$t_query_parameters = array();

	$t_query = 'SELECT  sum(ta.hours) as sum_time, p.name , p.id as id_project
	FROM ' . $t_project_table . ' p 
	LEFT JOIN ' . $t_bug_table . ' bug on p.id = bug.project_id
	LEFT JOIN ' . $t_timereport_table . ' ta on bug.id = ta.bug_id 
	WHERE 1=1 ';

	if (!is_null($t_from) && !is_null($t_to)) {
		$t_query .= 'AND  (DATE(expenditure_date) >= DATE("' . $t_from . '") AND DATE(expenditure_date) <= DATE("' . $t_to . '") OR expenditure_date IS NULL) ';
	}

	if (ALL_PROJECTS != $f_project_id) {
		$t_query .= " AND (p.id in 
		(select id from {project} where id in
		( select child_id from {project_hierarchy} where parent_id =" . db_param() . ") ) or p.id=  " . db_param() . " )";
		$t_query_parameters[] = $f_project_id;
		$t_query_parameters[] = $f_project_id;
	}
	$t_query .= " AND user = " . db_param();
	$t_query_parameters[] = $user_id;
	$t_query .= ' group by p.id';
	$t_dbresult = db_query($t_query, $t_query_parameters);
	$p_metrics = [];
	while ($row = db_fetch_array($t_dbresult)) {
		$p_metrics[$row['name'] . ' ' . plugin_TimeTracking_hours_to_hhmm($row['sum_time']) . ' (' . plugin_lang_get('hours') . ') '] = $row['sum_time'];
	}
	
	$s_id++;
	
	$t_json_labels = array_keys($p_metrics);
	$t_json_labels = json_encode(array_keys($p_metrics));

	$t_json_values =  array_values($p_metrics);
	
	$t_json_values = json_encode(array_values($p_metrics));

?>
	<canvas id="piechart<?php echo $s_id ?>" width="500" height="400" data-labels="<?php echo htmlspecialchars($t_json_labels, ENT_QUOTES) ?>" data-values="<?php echo htmlspecialchars($t_json_values, ENT_QUOTES) ?>" <?php
																																																						if ($p_mantis_colors) {
																																																							$t_colors = graph_colors_to_rgbas(graph_status_colors_to_colors($p_metrics), 1.0);
																																																						?> data-colors="[<?php echo htmlspecialchars($t_colors, ENT_QUOTES) ?>]" <?php } ?>>
	</canvas>
<?php
}


/**
 * Cumulative line graph
 *
 * @param array   $p_metrics      Graph Data.
 * @param integer $p_wfactor      Width factor for graph chart. Eg: 2 to make it double wide
 * @return void
 */
function graph_cumulative_user_bydate($f_project_id, $t_from, $t_to, $user_id) {
	static $s_id = 0;

	$s_id++;
	$p_wfactor = 2;
	$t_timereport_table = db_get_table('mantis_TimeTracking_data');
	$t_bug_table = db_get_table('bug');
	$t_project_table = db_get_table('project');
	db_param_push();
	$t_query_parameters = array();

	$t_query = 'SELECT  sum(ta.hours) as sum_time, expenditure_date
	FROM ' . $t_project_table . ' p 
	LEFT JOIN ' . $t_bug_table . ' bug on p.id = bug.project_id
	LEFT JOIN ' . $t_timereport_table . ' ta on bug.id = ta.bug_id 
	WHERE 1=1 ';

	if (!is_null($t_from) && !is_null($t_to)) {
		$t_query .= 'AND  (DATE(expenditure_date) >= DATE("' . $t_from . '") AND DATE(expenditure_date) <= DATE("' . $t_to . '") OR expenditure_date IS NULL) ';
	}

	if (ALL_PROJECTS != $f_project_id) {
		$t_query .= " AND (p.id in 
		(select id from {project} where id in
		( select child_id from {project_hierarchy} where parent_id =" . db_param() . ") ) or p.id=  " . db_param() . " )";
		$t_query_parameters[] = $f_project_id;
		$t_query_parameters[] = $f_project_id;
	}
	$t_query .= " AND user = " . db_param();
	$t_query_parameters[] = $user_id;
	$t_query .= ' group by expenditure_date';
	$t_dbresult = db_query($t_query, $t_query_parameters);
	$p_metrics = [];
	$p_metrics = generateDateRange($t_from, $t_to);
	while ($row = db_fetch_array($t_dbresult)) {
		$p_metrics[strtotime(date('Y-m-d', strtotime($row['expenditure_date'])))] = $row['sum_time'];
	}

	$t_labels = array_keys( $p_metrics );
	$t_formatted_labels = array_map( function($label) { return date( 'Y/m/d', $label ); }, $t_labels );
	
	$t_json_labels = json_encode( $t_formatted_labels );
	
	$t_opened_values = json_encode( array_values( $p_metrics ) );
	$t_legend_opened = plugin_lang_get( 'hours' );
	$t_width = 500 * $p_wfactor;
	$t_height = 400;
?>
	<canvas id="linebydate<?php echo $s_id ?>"
		width="<?php echo $t_width ?>" height="<?php echo $t_height ?>"
		data-labels="<?php echo htmlspecialchars( $t_json_labels, ENT_QUOTES ) ?>"
		data-opened-label="<?php echo $t_legend_opened ?>"
		data-opened-values="<?php echo htmlspecialchars( $t_opened_values, ENT_QUOTES ) ?>"
>
	</canvas>
<?php

}
function generateDateRange($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D'); // 1 day interval
    $date_range = [];

    // Iterate over each date and add it to the array
    $current = clone $start;
    while ($current <= $end) {
        $date_range[strtotime($current->format('Y-m-d'))] = 0;
        $current->add($interval);
    }

    return $date_range;
}


/**
 * Cumulative line graph
 *
 * @param array   $p_metrics      Graph Data.
 * @param integer $p_wfactor      Width factor for graph chart. Eg: 2 to make it double wide
 * @return void
 */
function graph_user_projects_nb_ticket_pie($f_project_id, $t_from, $t_to, $user_id) {
	static $s_id = 0;
	$t_timereport_table = db_get_table('mantis_TimeTracking_data');
	$t_bug_table = db_get_table('bug');
	$t_project_table = db_get_table('project');
	$p_mantis_colors = false;
	db_param_push();
	$t_query_parameters = array();

	$t_query = 'SELECT  count(ta.id) as count_number, p.name , p.id as id_project
	FROM ' . $t_project_table . ' p 
	LEFT JOIN ' . $t_bug_table . ' bug on p.id = bug.project_id
	LEFT JOIN ' . $t_timereport_table . ' ta on bug.id = ta.bug_id 
	WHERE 1=1 ';

	if (!is_null($t_from) && !is_null($t_to)) {
		$t_query .= 'AND  (DATE(expenditure_date) >= DATE("' . $t_from . '") AND DATE(expenditure_date) <= DATE("' . $t_to . '") OR expenditure_date IS NULL) ';
	}

	if (ALL_PROJECTS != $f_project_id) {
		$t_query .= " AND (p.id in 
		(select id from {project} where id in
		( select child_id from {project_hierarchy} where parent_id =" . db_param() . ") ) or p.id=  " . db_param() . " )";
		$t_query_parameters[] = $f_project_id;
		$t_query_parameters[] = $f_project_id;
	}
	$t_query .= " AND user = " . db_param();
	$t_query_parameters[] = $user_id;
	$t_query .= ' group by p.id';
	$t_dbresult = db_query($t_query, $t_query_parameters);
	$p_metrics = [];
	while ($row = db_fetch_array($t_dbresult)) {
		$p_metrics[$row['name'] . ' ' . $row['count_number'] . ' (' . plugin_lang_get('ticket') . ') '] = $row['count_number'];
	}
	
	$s_id++;
	
	$t_json_labels = array_keys($p_metrics);
	$t_json_labels = json_encode(array_keys($p_metrics));

	$t_json_values =  array_values($p_metrics);
	
	$t_json_values = json_encode(array_values($p_metrics));

?>
	<canvas id="piechart<?php echo $s_id ?>" width="500" height="400" data-labels="<?php echo htmlspecialchars($t_json_labels, ENT_QUOTES) ?>" data-values="<?php echo htmlspecialchars($t_json_values, ENT_QUOTES) ?>" <?php
																																																						if ($p_mantis_colors) {
																																																							$t_colors = graph_colors_to_rgbas(graph_status_colors_to_colors($p_metrics), 1.0);
																																																						?> data-colors="[<?php echo htmlspecialchars($t_colors, ENT_QUOTES) ?>]" <?php } ?>>
	</canvas>
<?php
}



function project_get_all_user_rows_orderd( $p_project_id = ALL_PROJECTS, $p_access_level = ANYBODY, $p_include_global_users = true ) {
	$c_project_id = (int)$p_project_id;

	# Optimization when access_level is NOBODY
	if( NOBODY == $p_access_level ) {
		return array();
	}

	$t_on = ON;
	$t_users = array();

	$t_global_access_level = $p_access_level;
	if( $c_project_id != ALL_PROJECTS && $p_include_global_users ) {

		# looking for specific project
		if( VS_PRIVATE == project_get_field( $p_project_id, 'view_state' ) ) {
			# @todo (thraxisp) this is probably more complex than it needs to be
			# When a new project is created, those who meet 'private_project_threshold' are added
			# automatically, but don't have an entry in project_user_list_table.
			#  if they did, you would not have to add global levels.
			$t_private_project_threshold = config_get( 'private_project_threshold' );
			if( is_array( $t_private_project_threshold ) ) {
				if( is_array( $p_access_level ) ) {
					# both private threshold and request are arrays, use intersection
					$t_global_access_level = array_intersect( $p_access_level, $t_private_project_threshold );
				} else {
					# private threshold is an array, but request is a number, use values in threshold higher than request
					$t_global_access_level = array();
					foreach( $t_private_project_threshold as $t_threshold ) {
						if( $p_access_level <= $t_threshold ) {
							$t_global_access_level[] = $t_threshold;
						}
					}
				}
			} else {
				if( is_array( $p_access_level ) ) {
					# private threshold is a number, but request is an array, use values in request higher than threshold
					$t_global_access_level = array();
					foreach( $p_access_level as $t_threshold ) {
						if( $t_threshold >= $t_private_project_threshold ) {
							$t_global_access_level[] = $t_threshold;
						}
					}
				} else {
					# both private threshold and request are numbers, use maximum
					$t_global_access_level = max( $p_access_level, $t_private_project_threshold );
				}
			}
		}
	}

	if( $p_include_global_users ) {
		$t_query = new DbQuery();
		$t_query->sql( 'SELECT id, username, realname, access_level
			FROM {user}
			WHERE enabled = ' . $t_query->param( $t_on ) . ' 
				AND '
		);
		if( is_array( $t_global_access_level ) ) {
			if( empty( $t_global_access_level ) ) {
				$t_query->append_sql( 'access_level >= ' . $t_query->param( NOBODY ) );
			} else {
				$t_query->append_sql( $t_query->sql_in( 'access_level', $t_global_access_level ) );
			}
		} else {
			$t_query->append_sql( 'access_level >= ' . $t_query->param( $t_global_access_level ) );
		}
		$t_query->append_sql( ' ORDER by username ASC');

		$t_query->execute();

		while( $t_row = $t_query->fetch() ) {
			$t_users[(int)$t_row['id']] = $t_row;
		}
	}

	if( $c_project_id != ALL_PROJECTS ) {
		# Get the project overrides
		$t_query = new DbQuery();
		$t_query->sql( 'SELECT u.id, u.username, u.realname, l.access_level
			FROM {project_user_list} l, {user} u
			WHERE l.user_id = u.id
			AND u.enabled = ' . $t_query->param( $t_on ) . '
			AND l.project_id = ' . $t_query->param( $c_project_id ).' Order by username ASC'
		);
		$t_query->execute();

		while( $t_row = $t_query->fetch() ) {
			if( is_array( $p_access_level ) ) {
				$t_keep = in_array( $t_row['access_level'], $p_access_level );
			} else {
				$t_keep = $t_row['access_level'] >= $p_access_level;
			}

			if( $t_keep ) {
				$t_users[(int)$t_row['id']] = $t_row;
			} else {
				# If user's overridden level is lower than required, so remove
				#  them from the list if they were previously there
				unset( $t_users[(int)$t_row['id']] );
			}
		}
	}

	return $t_users;
}
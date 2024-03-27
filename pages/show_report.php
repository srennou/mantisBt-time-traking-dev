<?php
require_once('core.php');
require_once('core/bug_api.php');
require_once('timetrackingdev_api.php');
require_once('timetracking_api.php');
layout_page_header(plugin_lang_get('title'));
layout_page_begin(plugin_page('show_report'));

$t_today = date("d:m:Y");
$t_date_submitted = isset($t_bug) ? date("d:m:Y", $t_bug->date_submitted) : '01:' . date("m:Y");
$t_plugin_TimeTrackingDev_stats_from_def = $t_date_submitted;
$t_plugin_TimeTrackingDev_stats_from_def_ar = explode(":", $t_plugin_TimeTrackingDev_stats_from_def);
$t_plugin_TimeTrackingDev_stats_from_def_d = $t_plugin_TimeTrackingDev_stats_from_def_ar[0];
$t_plugin_TimeTrackingDev_stats_from_def_m = $t_plugin_TimeTrackingDev_stats_from_def_ar[1];
$t_plugin_TimeTrackingDev_stats_from_def_y = $t_plugin_TimeTrackingDev_stats_from_def_ar[2];
$t_plugin_TimeTrackingDev_stats_from_d = gpc_get_int('start_day', $t_plugin_TimeTrackingDev_stats_from_def_d);
$t_plugin_TimeTrackingDev_stats_from_m = gpc_get_int('start_month', $t_plugin_TimeTrackingDev_stats_from_def_m);
$t_plugin_TimeTrackingDev_stats_from_y = gpc_get_int('start_year', $t_plugin_TimeTrackingDev_stats_from_def_y);
$t_plugin_TimeTrackingDev_stats_to_def = $t_today;
$t_plugin_TimeTrackingDev_stats_to_def_ar = explode(":", $t_plugin_TimeTrackingDev_stats_to_def);
$t_plugin_TimeTrackingDev_stats_to_def_d = $t_plugin_TimeTrackingDev_stats_to_def_ar[0];
$t_plugin_TimeTrackingDev_stats_to_def_m = $t_plugin_TimeTrackingDev_stats_to_def_ar[1];
$t_plugin_TimeTrackingDev_stats_to_def_y = $t_plugin_TimeTrackingDev_stats_to_def_ar[2];
$t_plugin_TimeTrackingDev_stats_to_d = gpc_get_int('end_day', $t_plugin_TimeTrackingDev_stats_to_def_d);
$t_plugin_TimeTrackingDev_stats_to_m = gpc_get_int('end_month', $t_plugin_TimeTrackingDev_stats_to_def_m);
$t_plugin_TimeTrackingDev_stats_to_y = gpc_get_int('end_year', $t_plugin_TimeTrackingDev_stats_to_def_y);

$f_plugin_TimeTrackingDev_stats_button = gpc_get_string('plugin_TimeTrackingDev_stats_button', '');
$f_project_id = helper_get_current_project();
$t_switch_tma_id = gpc_get_int('switch_tma_id', '');
$t_switch_tma_id = gpc_get_int('switch_tma_id', '');

$is_loading_from_filter = gpc_get_string('is_loading_from_filter', '');
$user_id = gpc_get_string('user_id', '');

$t_collapse_block = is_collapsed('timefilter');
$t_block_css = $t_collapse_block ? 'collapsed' : '';
$t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

?>

<div class="col-md-12 col-xs-12 noprint">
	<div id="filter" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-filter"></i>
				<?php echo lang_get('filters') ?>
			</h4>
			<div class="widget-toolbar">
				<a id="filter-toggle" data-action="collapse" href="#">
					<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
				</a>
			</div>
		</div>

		<div class="widget-body">
			<form method="post" action="<?php echo plugin_page('show_report') ?>">
				<div class="widget-main no-padding">

					<div class="table-responsive">

						<table class="width100" cellspacing="1">
							<tr class="row-category">
								<td class="category" style="padding-left:1em;padding-top:1em;padding-bottom:1em;background-color:white !important;">
									<?php
									$t_filter = array();
									$t_filter['do_filter_by_date'] = 'on';
									$t_filter['start_day'] = $t_plugin_TimeTrackingDev_stats_from_d;
									$t_filter['start_month'] = $t_plugin_TimeTrackingDev_stats_from_m;
									$t_filter['start_year'] = $t_plugin_TimeTrackingDev_stats_from_y;
									$t_filter['end_day'] = $t_plugin_TimeTrackingDev_stats_to_d;
									$t_filter['end_month'] = $t_plugin_TimeTrackingDev_stats_to_m;
									$t_filter['end_year'] = $t_plugin_TimeTrackingDev_stats_to_y;
									filter_init($t_filter);
									print_filter_do_filter_by_date(true);
									?>
								</td>
								<td style="padding-left:2em;"> <?= plugin_lang_get('users') ?> </td>
								<td class="users" style="padding-left:1em;padding-top:1em;padding-bottom:1em;background-color:white !important;">
									<?php $t_all_users = project_get_all_user_rows_orderd(); ?>
									<select id="project-users-mail" name="user_id" class="input-sm" required>
										<?php

										foreach ($t_all_users as $t_user_key => $t_user_value) {
											echo '<option ' . ((isset($user_id) && !is_null($user_id) && $user_id == $t_user_value['id']) ? 'selected' : '') . ' value="' . $t_user_value['id'] . '">' . $t_user_value['username'] . '</option>';
										}
										?>
									</select>
								</td>
							</tr>
						</table>

					</div>
				</div>
				<div class="widget-toolbox padding-8 clearfix">
					<input type="hidden" name="is_loading_from_filter" value="true">
					<input type="submit" class="btn btn-primary btn-white btn-round" name="plugin_TimeTrackingDev_stats_button" value="<?php echo plugin_lang_get('get_info') ?>" />
				</div>
			</form>
		</div>
	</div>

	<div class="space-10"></div>
	<?php if (!is_blank($f_plugin_TimeTrackingDev_stats_button)) { ?>
	<?php
		$t_from = "$t_plugin_TimeTrackingDev_stats_from_y-$t_plugin_TimeTrackingDev_stats_from_m-$t_plugin_TimeTrackingDev_stats_from_d";
		$t_to = "$t_plugin_TimeTrackingDev_stats_to_y-$t_plugin_TimeTrackingDev_stats_to_m-$t_plugin_TimeTrackingDev_stats_to_d";
		$t_plugin_TimeTrackingDev_stats = plugin_TimeTrackingDev_stats_get_project_array($f_project_id, $t_from, $t_to, $user_id);
		$t_project_summary = array();
		$t_bug_summary = array();
		# Initialize the user summary array

		foreach ($t_plugin_TimeTrackingDev_stats as $t_item) {
			$t_user_summary[$t_item['username']] = 0;
			$t_project_summary[$t_item['project_name']] = ['time' => 0, 'project_id' => 0];
			$t_bug_summary[$t_item['bug_id']] = 0;
		}
		foreach ($t_plugin_TimeTrackingDev_stats as $t_key => $t_item) {
			$t_sum_in_hours += $t_item['hours'];
			$t_user_summary[$t_item['username']] += $t_item['hours'];
			$t_project_summary[$t_item['project_name']]['time'] += $t_item['hours'];
			$t_project_summary[$t_item['project_name']]['project_id'] = $t_item['project_id'];
			$t_bug_summary[$t_item['bug_id']] += $t_item['hours'];
		}
	}
	$t_plugin_TimeTrackingDev_stats_infos = plugin_TimeTrackingDev_stats_get_project_infos($f_project_id, $user_id, $t_from, $t_to);

	?>
	<div id="result-project" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-clock-o"></i>
				<?php echo plugin_lang_get('title'), ' - ', lang_get('project_name') ?>
			</h4>
			<div class="widget-toolbar">
				<a id="result-project-toggle" data-action="collapse" href="#">
					<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
				</a>
			</div>
		</div>

		<div class="widget-body">
			<div class="table-responsive">
				<table class="table table-bordered table-condensed table-hover table-striped">
					<thead>
						<tr>
							<td class="small-caption">
								<?php echo lang_get('project_name') ?>
							</td>
							<td class="small-caption">
								<?php echo plugin_lang_get('hours') ?>
							</td>
						</tr>
					</thead>

					<tbody>
						<?php foreach ($t_plugin_TimeTrackingDev_stats_infos as $t_project_key => $t_project_value) { ?>
							<tr <?php echo helper_alternate_class() ?>>
								<td class="small-caption">
									<?php echo $t_project_key; ?>
								</td>
								<td class="small-caption">
									<?php echo number_format($t_project_value['sum_time'], 2, '.', ','); ?> (<?php echo plugin_TimeTrackingDev_hours_to_hhmm($t_project_value['time']); ?>)
								</td>

							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="space-10"></div>

	<?php
	if (!is_blank($f_plugin_TimeTrackingDev_stats_button) && $user_id != '' && $user_id != 0) { ?>
		<div id="result-user" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
			<?php
			$t_from = "$t_plugin_TimeTrackingDev_stats_from_y-$t_plugin_TimeTrackingDev_stats_from_m-$t_plugin_TimeTrackingDev_stats_from_d";
			$t_to = "$t_plugin_TimeTrackingDev_stats_to_y-$t_plugin_TimeTrackingDev_stats_to_m-$t_plugin_TimeTrackingDev_stats_to_d";
			$t_plugin_TimeTrackingDev_stats = plugin_TimeTrackingDev_stats_get_project_array($f_project_id, $t_from, $t_to, $user_id);
			$totaletime = 0;
			$t_tma_garantie = 0;
			foreach ($t_plugin_TimeTrackingDev_stats as $t_key => $t_item) {
				if ($t_item['category'] != 'Garantie') {
					$totaletime += $t_item['hours'];
				} else {
					$t_tma_garantie += $t_item['hours'];
				}
			}
			?>
			<div class="widget-header widget-header-small">

				<div class="widget-toolbar">
					<a id="result-user-toggle" data-action="collapse" href="#">
						<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
					</a>
				</div>
			</div>
			<div class="widget-body">
				<div class="table-responsive">
					<?php
					?>


					<div class="col-md-3 col-xs-12" style="text-align:center;">
						<h3 style="width:100%"><?= plugin_lang_get('traking_project_hours') ?></h3>
						<?php graph_user_projects_pie($f_project_id, $t_from, $t_to, $user_id); ?>
					</div>
					<div class="col-md-6 col-xs-12" style="text-align:center;">
						<h3 style="width:100%"><?= plugin_lang_get('activity') ?></h3>
						<?php graph_cumulative_user_bydate($f_project_id, $t_from, $t_to, $user_id); ?>
					</div>
					<div class="col-md-3 col-xs-12" style="text-align:center;">
						<h3 style="width:100%"><?= plugin_lang_get('traking_project_nb_ticket') ?></h3>
						<?php graph_user_projects_nb_ticket_pie($f_project_id, $t_from, $t_to, $user_id); ?>
					</div>
				</div>
				<div class="space-10"></div>
			</div>
		</div>

		<div class="space-10"></div>
	<?php } ?>
	<?php

	if (!is_blank($f_plugin_TimeTrackingDev_stats_button)) {
	?>
		<div id="result" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<i class="ace-icon fa fa-clock-o"></i>
					<?php echo plugin_lang_get('title') ?>
				</h4>
				<div class="widget-toolbar">
					<a id="result-toggle" data-action="collapse" href="#">
						<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
					</a>
				</div>
			</div>

			<div class="widget-body">
				<div class="table-responsive">
					<table class="table table-bordered table-condensed table-hover table-striped">
						<thead>
							<tr>
								<td class="small-caption">
									<?php echo plugin_lang_get('user') ?>
								</td>
								<td class="small-caption">
									<?= lang_get('project_name') ?>
								</td>
								<td class="small-caption">
									<?php echo plugin_lang_get('expenditure_date') ?>
								</td>
								<td class="small-caption">
									<?php echo lang_get('issue_id') ?>
								</td>
								<td class="small-caption">
									<?php echo plugin_lang_get('category') ?>
								</td>
								<td class="small-caption">
									<?php echo plugin_lang_get('hours') ?>
								</td>
								<td class="small-caption">
									<?php echo plugin_lang_get('information') ?>
								</td>
							</tr>
						</thead>
						<tbody>
							<?php

							foreach ($t_plugin_TimeTrackingDev_stats as $t_key => $t_item) {
							?>
								<tr>
									<td class="small-caption">
										<?php echo $t_item['username'] ?>
									</td>
									<td class="small-caption">
										<?= $t_item['project_name'] ?>
									</td>
									<td class="small-caption">
										<?php echo date(config_get("short_date_format"), strtotime($t_item['expenditure_date'])) ?>
									</td>
									<td class="small-caption">
										<?php echo bug_format_summary($t_item['bug_id'], SUMMARY_LINK) ?>
									</td>
									<td class="small-caption">
										<?php echo $t_item['category'] ?>
									</td>
									<td class="small-caption">
										<?php echo number_format($t_item['hours'], 2, '.', ',') ?> (<?php echo plugin_TimeTrackingDev_hours_to_hhmm($t_item['hours']); ?>)
									</td>
									<td class="small-caption">
										<?php echo $t_item['info'] ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
						<tfoot>
							<tr>
								<td class="small-caption">
									<?php echo lang_get('total_time'); ?>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td class="small-caption">
									<?php echo number_format($t_sum_in_hours, 2, '.', ','); ?> (<?php echo plugin_TimeTrackingDev_hours_to_hhmm($t_sum_in_hours); ?>)
								</td>
								<td>&nbsp;</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div class="space-10"></div>

		<div id="result-issue" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<i class="ace-icon fa fa-clock-o"></i>
					<?php echo plugin_lang_get('title'), ' - ', lang_get('issue_id') ?>
				</h4>
				<div class="widget-toolbar">
					<a id="esult-issue-toggle" data-action="collapse" href="#">
						<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
					</a>
				</div>
			</div>

			<div class="widget-body">
				<div class="table-responsive">
					<table class="table table-bordered table-condensed table-hover table-striped">
						<thead>
							<tr>
								<td class="small-caption">
									<?php echo lang_get('issue_id') ?>
								</td>
								<td class="small-caption">
									<?php echo plugin_lang_get('hours') ?>
								</td>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($t_bug_summary as $t_bug_key => $t_bug_value) { ?>
								<tr>
									<td class="small-caption">
										<?php echo bug_format_id($t_bug_key); ?>
									</td>
									<td class="small-caption">
										<?php echo number_format($t_bug_value, 2, '.', ','); ?> (<?php echo plugin_TimeTrackingDev_hours_to_hhmm($t_bug_value); ?>)
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	<?php } ?>
</div>
<?php
layout_page_end();
?>
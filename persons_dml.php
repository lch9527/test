<?php

// Data functions (insert, update, delete, form) for table persons

// This script and data application were generated by AppGini 5.95
// Download AppGini for free from https://bigprof.com/appgini/download/

function persons_insert(&$error_message = '') {
	global $Translation;

	// mm: can member insert record?
	$arrPerm = getTablePermissions('persons');
	if(!$arrPerm['insert']) return false;

	$data = [
		'personal_id' => Request::val('personal_id', ''),
		'homeworld' => Request::val('homeworld', ''),
		'blaster_ownership' => Request::val('blaster_ownership', ''),
		'landcruiser_ownership' => Request::val('landcruiser_ownership', ''),
	];

	if($data['personal_id'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Personal id': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}

	// hook: persons_before_insert
	if(function_exists('persons_before_insert')) {
		$args = [];
		if(!persons_before_insert($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$error = '';
	// set empty fields to NULL
	$data = array_map(function($v) { return ($v === '' ? NULL : $v); }, $data);
	insert('persons', backtick_keys_once($data), $error);
	if($error)
		die("{$error}<br><a href=\"#\" onclick=\"history.go(-1);\">{$Translation['< back']}</a>");

	$recID = $data['personal_id'];

	update_calc_fields('persons', $recID, calculated_fields()['persons']);

	// hook: persons_after_insert
	if(function_exists('persons_after_insert')) {
		$res = sql("SELECT * FROM `persons` WHERE `personal_id`='" . makeSafe($recID, false) . "' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) {
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = makeSafe($recID, false);
		$args=[];
		if(!persons_after_insert($data, getMemberInfo(), $args)) { return $recID; }
	}

	// mm: save ownership data
	set_record_owner('persons', $recID, getLoggedMemberID());

	// if this record is a copy of another record, copy children if applicable
	if(!empty($_REQUEST['SelectedID'])) persons_copy_children($recID, $_REQUEST['SelectedID']);

	return $recID;
}

function persons_copy_children($destination_id, $source_id) {
	global $Translation;
	$requests = []; // array of curl handlers for launching insert requests
	$eo = ['silentErrors' => true];
	$safe_sid = makeSafe($source_id);

	// launch requests, asynchronously
	curl_batch($requests);
}

function persons_delete($selected_id, $AllowDeleteOfParents = false, $skipChecks = false) {
	// insure referential integrity ...
	global $Translation;
	$selected_id = makeSafe($selected_id);

	// mm: can member delete record?
	if(!check_record_permission('persons', $selected_id, 'delete')) {
		return $Translation['You don\'t have enough permissions to delete this record'];
	}

	// hook: persons_before_delete
	if(function_exists('persons_before_delete')) {
		$args = [];
		if(!persons_before_delete($selected_id, $skipChecks, getMemberInfo(), $args))
			return $Translation['Couldn\'t delete this record'] . (
				!empty($args['error_message']) ?
					'<div class="text-bold">' . strip_tags($args['error_message']) . '</div>'
					: '' 
			);
	}

	sql("DELETE FROM `persons` WHERE `personal_id`='{$selected_id}'", $eo);

	// hook: persons_after_delete
	if(function_exists('persons_after_delete')) {
		$args = [];
		persons_after_delete($selected_id, getMemberInfo(), $args);
	}

	// mm: delete ownership data
	sql("DELETE FROM `membership_userrecords` WHERE `tableName`='persons' AND `pkValue`='{$selected_id}'", $eo);
}

function persons_update(&$selected_id, &$error_message = '') {
	global $Translation;

	// mm: can member edit record?
	if(!check_record_permission('persons', $selected_id, 'edit')) return false;

	$data = [
		'personal_id' => Request::val('personal_id', ''),
		'homeworld' => Request::val('homeworld', ''),
		'blaster_ownership' => Request::val('blaster_ownership', ''),
		'landcruiser_ownership' => Request::val('landcruiser_ownership', ''),
	];

	if($data['personal_id'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Personal id': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}
	// get existing values
	$old_data = getRecord('persons', $selected_id);
	if(is_array($old_data)) {
		$old_data = array_map('makeSafe', $old_data);
		$old_data['selectedID'] = makeSafe($selected_id);
	}

	$data['selectedID'] = makeSafe($selected_id);

	// hook: persons_before_update
	if(function_exists('persons_before_update')) {
		$args = ['old_data' => $old_data];
		if(!persons_before_update($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$set = $data; unset($set['selectedID']);
	foreach ($set as $field => $value) {
		$set[$field] = ($value !== '' && $value !== NULL) ? $value : NULL;
	}

	if(!update(
		'persons', 
		backtick_keys_once($set), 
		['`personal_id`' => $selected_id], 
		$error_message
	)) {
		echo $error_message;
		echo '<a href="persons_view.php?SelectedID=' . urlencode($selected_id) . "\">{$Translation['< back']}</a>";
		exit;
	}

	$data['selectedID'] = $data['personal_id'];
	$newID = $data['personal_id'];

	$eo = ['silentErrors' => true];

	update_calc_fields('persons', $data['selectedID'], calculated_fields()['persons']);

	// hook: persons_after_update
	if(function_exists('persons_after_update')) {
		$res = sql("SELECT * FROM `persons` WHERE `personal_id`='{$data['selectedID']}' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) $data = array_map('makeSafe', $row);

		$data['selectedID'] = $data['personal_id'];
		$args = ['old_data' => $old_data];
		if(!persons_after_update($data, getMemberInfo(), $args)) return;
	}

	// mm: update ownership data
	sql("UPDATE `membership_userrecords` SET `dateUpdated`='" . time() . "', `pkValue`='{$data['personal_id']}' WHERE `tableName`='persons' AND `pkValue`='" . makeSafe($selected_id) . "'", $eo);

	// if PK value changed, update $selected_id
	$selected_id = $newID;
}

function persons_form($selected_id = '', $AllowUpdate = 1, $AllowInsert = 1, $AllowDelete = 1, $ShowCancel = 0, $TemplateDV = '', $TemplateDVP = '') {
	// function to return an editable form for a table records
	// and fill it with data of record whose ID is $selected_id. If $selected_id
	// is empty, an empty form is shown, with only an 'Add New'
	// button displayed.

	global $Translation;

	// mm: get table permissions
	$arrPerm = getTablePermissions('persons');
	if(!$arrPerm['insert'] && $selected_id=='') { return ''; }
	$AllowInsert = ($arrPerm['insert'] ? true : false);
	// print preview?
	$dvprint = false;
	if($selected_id && $_REQUEST['dvprint_x'] != '') {
		$dvprint = true;
	}


	// populate filterers, starting from children to grand-parents

	// unique random identifier
	$rnd1 = ($dvprint ? rand(1000000, 9999999) : '');

	if($selected_id) {
		// mm: check member permissions
		if(!$arrPerm['view']) return '';

		// mm: who is the owner?
		$ownerGroupID = sqlValue("SELECT `groupID` FROM `membership_userrecords` WHERE `tableName`='persons' AND `pkValue`='" . makeSafe($selected_id) . "'");
		$ownerMemberID = sqlValue("SELECT LCASE(`memberID`) FROM `membership_userrecords` WHERE `tableName`='persons' AND `pkValue`='" . makeSafe($selected_id) . "'");

		if($arrPerm['view'] == 1 && getLoggedMemberID() != $ownerMemberID) return '';
		if($arrPerm['view'] == 2 && getLoggedGroupID() != $ownerGroupID) return '';

		// can edit?
		$AllowUpdate = 0;
		if(($arrPerm['edit'] == 1 && $ownerMemberID == getLoggedMemberID()) || ($arrPerm['edit'] == 2 && $ownerGroupID == getLoggedGroupID()) || $arrPerm['edit'] == 3) {
			$AllowUpdate = 1;
		}

		$res = sql("SELECT * FROM `persons` WHERE `personal_id`='" . makeSafe($selected_id) . "'", $eo);
		if(!($row = db_fetch_array($res))) {
			return error_message($Translation['No records found'], 'persons_view.php', false);
		}
		$urow = $row; /* unsanitized data */
		$row = array_map('safe_html', $row);
	} else {
	}

	// code for template based detail view forms

	// open the detail view template
	if($dvprint) {
		$template_file = is_file("./{$TemplateDVP}") ? "./{$TemplateDVP}" : './templates/persons_templateDVP.html';
		$templateCode = @file_get_contents($template_file);
	} else {
		$template_file = is_file("./{$TemplateDV}") ? "./{$TemplateDV}" : './templates/persons_templateDV.html';
		$templateCode = @file_get_contents($template_file);
	}

	// process form title
	$templateCode = str_replace('<%%DETAIL_VIEW_TITLE%%>', 'Person details', $templateCode);
	$templateCode = str_replace('<%%RND1%%>', $rnd1, $templateCode);
	$templateCode = str_replace('<%%EMBEDDED%%>', ($_REQUEST['Embedded'] ? 'Embedded=1' : ''), $templateCode);
	// process buttons
	if($AllowInsert) {
		if(!$selected_id) $templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-success" id="insert" name="insert_x" value="1" onclick="return persons_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save New'] . '</button>', $templateCode);
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="insert" name="insert_x" value="1" onclick="return persons_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save As Copy'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '', $templateCode);
	}

	// 'Back' button action
	if($_REQUEST['Embedded']) {
		$backAction = 'AppGini.closeParentModal(); return false;';
	} else {
		$backAction = '$j(\'form\').eq(0).attr(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;';
	}

	if($selected_id) {
		if(!$_REQUEST['Embedded']) $templateCode = str_replace('<%%DVPRINT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="dvprint" name="dvprint_x" value="1" onclick="$j(\'form\').eq(0).prop(\'novalidate\', true); document.myform.reset(); return true;" title="' . html_attr($Translation['Print Preview']) . '"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>', $templateCode);
		if($AllowUpdate) {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '<button type="submit" class="btn btn-success btn-lg" id="update" name="update_x" value="1" onclick="return persons_validateData();" title="' . html_attr($Translation['Save Changes']) . '"><i class="glyphicon glyphicon-ok"></i> ' . $Translation['Save Changes'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		}
		if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3) { // allow delete?
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '<button type="submit" class="btn btn-danger" id="delete" name="delete_x" value="1" onclick="return confirm(\'' . $Translation['are you sure?'] . '\');" title="' . html_attr($Translation['Delete']) . '"><i class="glyphicon glyphicon-trash"></i> ' . $Translation['Delete'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		}
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', ($ShowCancel ? '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>' : ''), $templateCode);
	}

	// set records to read only if user can't insert new records and can't edit current record
	if(($selected_id && !$AllowUpdate && !$AllowInsert) || (!$selected_id && !$AllowInsert)) {
		$jsReadOnly .= "\tjQuery('#personal_id').replaceWith('<div class=\"form-control-static\" id=\"personal_id\">' + (jQuery('#personal_id').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#homeworld').replaceWith('<div class=\"form-control-static\" id=\"homeworld\">' + (jQuery('#homeworld').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#blaster_ownership').replaceWith('<div class=\"form-control-static\" id=\"blaster_ownership\">' + (jQuery('#blaster_ownership').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#landcruiser_ownership').replaceWith('<div class=\"form-control-static\" id=\"landcruiser_ownership\">' + (jQuery('#landcruiser_ownership').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('.select2-container').hide();\n";

		$noUploads = true;
	} elseif($AllowInsert) {
		$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', true);"; // temporarily disable form change handler
			$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', false);"; // re-enable form change handler
	}

	// process combos

	/* lookup fields array: 'lookup field name' => array('parent table name', 'lookup field caption') */
	$lookup_fields = array();
	foreach($lookup_fields as $luf => $ptfc) {
		$pt_perm = getTablePermissions($ptfc[0]);

		// process foreign key links
		if($pt_perm['view'] || $pt_perm['edit']) {
			$templateCode = str_replace("<%%PLINK({$luf})%%>", '<button type="button" class="btn btn-default view_parent hspacer-md" id="' . $ptfc[0] . '_view_parent" title="' . html_attr($Translation['View'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-eye-open"></i></button>', $templateCode);
		}

		// if user has insert permission to parent table of a lookup field, put an add new button
		if($pt_perm['insert'] /* && !$_REQUEST['Embedded']*/) {
			$templateCode = str_replace("<%%ADDNEW({$ptfc[0]})%%>", '<button type="button" class="btn btn-success add_new_parent hspacer-md" id="' . $ptfc[0] . '_add_new" title="' . html_attr($Translation['Add New'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-plus-sign"></i></button>', $templateCode);
		}
	}

	// process images
	$templateCode = str_replace('<%%UPLOADFILE(personal_id)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(homeworld)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(blaster_ownership)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(landcruiser_ownership)%%>', '', $templateCode);

	// process values
	if($selected_id) {
		if( $dvprint) $templateCode = str_replace('<%%VALUE(personal_id)%%>', safe_html($urow['personal_id']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(personal_id)%%>', html_attr($row['personal_id']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(personal_id)%%>', urlencode($urow['personal_id']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(homeworld)%%>', safe_html($urow['homeworld']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(homeworld)%%>', html_attr($row['homeworld']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(homeworld)%%>', urlencode($urow['homeworld']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(blaster_ownership)%%>', safe_html($urow['blaster_ownership']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(blaster_ownership)%%>', html_attr($row['blaster_ownership']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(blaster_ownership)%%>', urlencode($urow['blaster_ownership']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(landcruiser_ownership)%%>', safe_html($urow['landcruiser_ownership']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(landcruiser_ownership)%%>', html_attr($row['landcruiser_ownership']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(landcruiser_ownership)%%>', urlencode($urow['landcruiser_ownership']), $templateCode);
	} else {
		$templateCode = str_replace('<%%VALUE(personal_id)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(personal_id)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(homeworld)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(homeworld)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(blaster_ownership)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(blaster_ownership)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(landcruiser_ownership)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(landcruiser_ownership)%%>', urlencode(''), $templateCode);
	}

	// process translations
	$templateCode = parseTemplate($templateCode);

	// clear scrap
	$templateCode = str_replace('<%%', '<!-- ', $templateCode);
	$templateCode = str_replace('%%>', ' -->', $templateCode);

	// hide links to inaccessible tables
	if($_REQUEST['dvprint_x'] == '') {
		$templateCode .= "\n\n<script>\$j(function() {\n";
		$arrTables = getTableList();
		foreach($arrTables as $name => $caption) {
			$templateCode .= "\t\$j('#{$name}_link').removeClass('hidden');\n";
			$templateCode .= "\t\$j('#xs_{$name}_link').removeClass('hidden');\n";
		}

		$templateCode .= $jsReadOnly;
		$templateCode .= $jsEditable;

		if(!$selected_id) {
		}

		$templateCode.="\n});</script>\n";
	}

	// ajaxed auto-fill fields
	$templateCode .= '<script>';
	$templateCode .= '$j(function() {';


	$templateCode.="});";
	$templateCode.="</script>";
	$templateCode .= $lookups;

	// handle enforced parent values for read-only lookup fields

	// don't include blank images in lightbox gallery
	$templateCode = preg_replace('/blank.gif" data-lightbox=".*?"/', 'blank.gif"', $templateCode);

	// don't display empty email links
	$templateCode=preg_replace('/<a .*?href="mailto:".*?<\/a>/', '', $templateCode);

	/* default field values */
	$rdata = $jdata = get_defaults('persons');
	if($selected_id) {
		$jdata = get_joined_record('persons', $selected_id);
		if($jdata === false) $jdata = get_defaults('persons');
		$rdata = $row;
	}
	$templateCode .= loadView('persons-ajax-cache', array('rdata' => $rdata, 'jdata' => $jdata));

	// hook: persons_dv
	if(function_exists('persons_dv')) {
		$args=[];
		persons_dv(($selected_id ? $selected_id : FALSE), getMemberInfo(), $templateCode, $args);
	}

	return $templateCode;
}
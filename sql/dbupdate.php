<#1>
<?php
$fields = array(
	'obj_id'          => array(
		'type'   => 'integer',
		'length' => '4'
	),
	'active'       => array(
		'notnull' => '1',
		'type'    => 'integer',
		'length'  => '1'
	)
);
if(!$ilDB->tableExists('pl_scas_test_config'))
{
	$ilDB->createTable('pl_scas_test_config', $fields);
	$ilDB->addPrimaryKey('pl_scas_test_config', array('obj_id'));
}
?>
<#2>
<?php
$fields = array(
	'tst_id'      => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'count_documents' => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'matriculation_code' => array(
		'type'    => 'integer',
		'length'  => '1'
	),
	'matriculation_style' => array(
		'type'    => 'integer',
		'length'  => '1'
	),
	'download_style' => array(
		'type'    => 'integer',
		'length'  => '1'
	),
	'personalised' => array(
		'type'    => 'integer',
		'length'  => '1'
	),
	'documents_generated' => array(
		'notnull' => false,
		'type'    => 'int'
	),
);
if(!$ilDB->tableExists('pl_scas_user_packages'))
{
	$ilDB->createTable('pl_scas_user_packages', $fields);
	$ilDB->addPrimaryKey('pl_scas_user_packages', array('tst_id'));
}
?>
<#3>
<?php
if(!$ilDB->tableColumnExists('pl_scas_user_packages', 'no_name_field'))
{
	$ilDB->addTableColumn('pl_scas_user_packages', 'no_name_field',
		array(
			'type'    => 'integer',
			'length'  => '1'
		)
	);
}
?>
<#4>
<?php
if(!$ilDB->tableColumnExists('pl_scas_user_packages', 'assessment_date'))
{
	$ilDB->addTableColumn('pl_scas_user_packages', 'assessment_date',
		array(
			'type'    => 'integer',
			'length'  => '4'
		)
	);
}
?>

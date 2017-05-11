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
		'type'    => 'integer',
		'length'  => '4'
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
<#5>
<?php
$fields = array(
	'pdf_id'      => array(
		'notnull' => '1',
		'type'    => 'integer',
		'length'  => '4'
	),
	'obj_id'     => array(
		'notnull'=> '1',
		'type'   => 'integer',
		'length' => '4'
	),
	'usr_id'	 => array(
		'type'   => 'integer',
		'length' => '4'
	),
	'qst_data' => array(
		'type' => 'text',
		'length' => '255'
	)
);
if(!$ilDB->tableExists('pl_scas_pdf_data'))
{
	$ilDB->createTable('pl_scas_pdf_data', $fields);
	$ilDB->addPrimaryKey('pl_scas_pdf_data', array('pdf_id'));
	$ilDB->createSequence('pl_scas_pdf_data');
}
?>
<#6>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'personalised'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'personalised',
		array(
			'type'    => 'integer',
			'length'  => '1'
		)
	);
}
if($ilDB->tableColumnExists('pl_scas_pdf_data', 'qst_data'))
{
	$ilDB->dropTableColumn('pl_scas_pdf_data','qst_data');
	$ilDB->addTableColumn('pl_scas_pdf_data', 'qst_data',
		array(
			'type'    => 'text',
			'length'  => '4000',
			'notnull' => false,
			'default' => null
		)
	);
}
?>
<#7>
<?php
if($ilDB->tableColumnExists('pl_scas_pdf_data', 'qst_data'))
{
	$ilDB->dropTableColumn('pl_scas_pdf_data','qst_data');
	$ilDB->addTableColumn('pl_scas_pdf_data', 'qst_data',
		array(
			'type'    => 'clob',
			'notnull' => false,
			'default' => null
		)
	);
}
?>
<#8>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'page'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'page',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '4'
		)
	);
}
?>
<#9>
<?php
if($ilDB->tableColumnExists('pl_scas_pdf_data', 'page'))
{
	$ilDB->dropTableColumn('pl_scas_pdf_data','page');
}
if($ilDB->tableColumnExists('pl_scas_pdf_data', 'qst_data'))
{
	$ilDB->dropTableColumn('pl_scas_pdf_data','qst_data');
}
?>
<#10>
<?php
$fields = array(
	'pdf_id'      => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'page'      => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'qpl_data' => array(
		'type'    => 'clob',
		'notnull' => false,
		'default' => null
	)
);
if(!$ilDB->tableExists('pl_scas_pdf_data_qpl'))
{
	$ilDB->createTable('pl_scas_pdf_data_qpl', $fields);
	$ilDB->addPrimaryKey('pl_scas_pdf_data_qpl', array('pdf_id', 'page'));
}
?>
<#11>
<?php
$fields = array(
	'answer_id'   => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'pdf_id'      => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'page'      => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'test_id' => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'qid' => array(
		'notnull' => true,
		'type'    => 'integer',
		'length'  => '4'
	),
	'value1' => array(
		'type'    => 'text',
		'default' => null,
		'length'  => '4000'
	),
	'value2' => array(
		'type'    => 'text',
		'default' => null,
		'length'  => '4000'
	)
);
if(!$ilDB->tableExists('pl_scas_scan_data'))
{
	$ilDB->createTable('pl_scas_scan_data', $fields);
	$ilDB->addPrimaryKey('pl_scas_scan_data', array('answer_id'));
	$ilDB->createSequence('pl_scas_scan_data');
}
?>
<#12>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'revision_done'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'revision_done',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '1',
			'default' => 0
		)
	);
}
?>
<#13>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'usr_id'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'usr_id',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '4',
			'default' => 0
		)
	);
}
?>
<#14>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'results_exported'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'results_exported',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '1',
			'default' => 0
		)
	);
}
?>
<#15>
<?php
if(!$ilDB->tableColumnExists('pl_scas_scan_data', 'correctness'))
{
	$ilDB->addTableColumn('pl_scas_scan_data', 'correctness',
		array(
			'notnull'=> false,
			'type'   => 'text',
			'length' => '25',
			'default' => 0
		)
	);
}
?>
<#16>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'matriculation_matrix'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'matriculation_matrix',
		array(
			'type'    => 'clob',
			'notnull' => false,
			'default' => null
		)
	);
}
?>
<#17>
<?php
if(!$ilDB->tableColumnExists('pl_scas_test_config', 'shuffle'))
{
	$ilDB->addTableColumn('pl_scas_test_config', 'shuffle',
		array('notnull' => '1',
			  'type'    => 'integer',
			  'length'  => '1'
		)
	);
}
?>
<#18>
<?php
if(!$ilDB->tableColumnExists('pl_scas_test_config', 'pdf_mode'))
{
	$ilDB->addTableColumn('pl_scas_test_config', 'pdf_mode',
		array('notnull' => '1',
			  'type'    => 'integer',
			  'length'  => '1'
		)
	);
}
?>
<#19>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data_qpl', 'has_checkboxes'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data_qpl', 'has_checkboxes',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '1',
			'default' => 0
		)
	);
}
?>
<#20>
<?php

?>
<#21>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'matriculation_number'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'matriculation_number',
		array(
			'notnull'=> false,
			'type'   => 'text',
			'length' => '400',
			'default' => 0
		)
	);
}
?>
<#22>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'header_height'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'header_height',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '4',
			'default' => 0
		)
	);
}
?>
<#23>
<?php
if(!$ilDB->tableColumnExists('pl_scas_pdf_data', 'header_page'))
{
	$ilDB->addTableColumn('pl_scas_pdf_data', 'header_page',
		array(
			'notnull'=> false,
			'type'   => 'integer',
			'length' => '4',
			'default' => 0
		)
	);
}
?>
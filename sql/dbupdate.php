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

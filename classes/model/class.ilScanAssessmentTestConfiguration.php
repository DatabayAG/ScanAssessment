<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/ActiveRecord/class.ActiveRecord.php';
ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentModel.php');

/**
 * Class ilScanAssessmentTestConfiguration
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentTestConfiguration extends ActiveRecord implements ilScanAssessmentModel
{
	/**
	 * @var int
	 *
	 * @con_is_primary  true
	 * @con_is_unique   true
	 * @con_has_field   true
	 * @con_fieldtype   integer
	 * @con_length      4
	 */
	protected $obj_id = 0;

	/**
	 * @var int
	 *
	 * @con_is_primary  false
	 * @con_is_unique   false
	 * @con_has_field   true
	 * @con_fieldtype   integer
	 * @con_length      1
	 */
	protected $active = 0;

	/**
	 * @var int
	 *
	 * @con_is_primary  false
	 * @con_is_unique   false
	 * @con_has_field   true
	 * @con_fieldtype   integer
	 * @con_length      1
	 */
	protected $shuffle;

	/**
	 * @var int
	 *
	 * @con_is_primary  false
	 * @con_is_unique   false
	 * @con_has_field   true
	 * @con_fieldtype   integer
	 * @con_length      1
	 */
	protected $pdf_mode;

	/**
	 * @var string
	 * @con_is_primary  false
	 * @con_is_unique   false
	 * @con_has_field   true
	 * @con_fieldtype   text
	 * @con_length      100
	 */
	protected $department;

	/**
	 * @return int
	 */
	public function getShuffle()
	{
		return $this->shuffle;
	}

	/**
	 * @param $shuffle
	 */
	public function setShuffle($shuffle)
	{
		$this->shuffle = $shuffle;
	}

	/**
	 * @return mixed
	 */
	public function getPdfMode()
	{
		return $this->pdf_mode;
	}

	/**
	 * @param mixed $pdf_mode
	 */
	public function setPdfMode($pdf_mode)
	{
		$this->pdf_mode = $pdf_mode;
	}

	/**
	 * @return string
	 */
	public function getDepartment()
	{
		return $this->department;
	}

	/**
	 * @param string $department
	 */
	public function setDepartment($department)
	{
		$this->department = $department;
	}

	/**
	 * @return int
	 */
	public function getActive()
	{
		return $this->active;
	}

	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName()
	{
		return 'pl_scas_test_config';
	}

	/**
	 * {@inheritdoc}
	 */
	public function __construct($primary_key, arConnector $connector = NULL)
	{
		if($primary_key !== 0 && $primary_key !== NULL && $primary_key !== false)
		{
			$this->setObjId($primary_key);
		}

		$this->ar_safe_read = false;
		parent::__construct($primary_key, $connector);
	}

	/**
	 * {@inheritdoc}
	 */
	public function store()
	{
		if($this->is_new)
		{
			$this->create();
		}
		else
		{
			$this->update();
		}
	}

	/**
	 * @var ilScanAssessmentPreconditionBase[]
	 */
	protected $preconditions = array();

	/**
	 * @var ilScanAssessmentStepsBase[]
	 */
	protected $steps = array();

	/**
	 * @var bool
	 */
	protected $init_complete = false;

	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @param $a_id integer
	 * @throws ilException
	 */
	public function setObjId($a_id)
	{
		if(!is_numeric($a_id) || $a_id < 1)
		{
			throw new ilException(sprintf("Only natural numbers accepted as obj_id, %s given", var_export($a_id, 1)));
		}

		if($this->obj_id != $a_id)
		{
			$this->test = ilObjectFactory::getInstanceByObjId($a_id);
			$this->initPreconditions();
			$this->initSteps();
		}

		$this->obj_id = $a_id;
	}

	/**
	 * 
	 */
	public function initPreconditions()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('preconditions/class.ilScanAssessmentIsFixedTestPrecondition.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('preconditions/class.ilScanAssessmentHasValidQuestionsPrecondition.php');

		$this->preconditions[] = new ilScanAssessmentIsFixedTestPrecondition(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->preconditions[] = new ilScanAssessmentHasValidQuestionsPrecondition(ilScanAssessmentPlugin::getInstance(), $this->test);
	}

	/**
	 * @return ilScanAssessmentPreconditionBase[]
	 */
	public function getPreconditions()
	{
		return $this->preconditions;
	}

	/**
	 *
	 */
	public function initSteps()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentLayoutStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentUserPackagesExportedStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentScanStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentReturnResultsStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentRevisionStep.php');
		ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentUserMappingStep.php');

		$this->steps[] = new ilScanAssessmentIsActivatedStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentLayoutStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentUserPackagesExportedStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentScanStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentRevisionStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentUserMappingStep(ilScanAssessmentPlugin::getInstance(), $this->test);
		$this->steps[] = new ilScanAssessmentReturnResultsStep(ilScanAssessmentPlugin::getInstance(), $this->test);
	}

	/**
	 * @return ilScanAssessmentStepsBase[]
	 */
	public function getSteps()
	{
		return $this->steps;
	}

	/**
	 * @param ilScanAssessmentPreconditionBase $precondition
	 */
	public function addPrecondition(ilScanAssessmentPreconditionBase $precondition)
	{
		$this->preconditions[] = $precondition;
	}

	/**
	 * @param array $preconditions
	 * @throws ilException
	 */
	public function setPreconditions(array $preconditions)
	{
		foreach($preconditions as $precondition)
		{
			if(!($precondition instanceof ilScanAssessmentPreconditionBase))
			{
				throw new ilException(sprintf("Precondition must be of type %s, %s given.", 'ilScanAssessmentPreconditionBase', get_class('ilScanAssessmentPreconditionBase')));
			}
		}

		$this->preconditions = $preconditions;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function bindForm(ilPropertyFormGUI $form)
	{
		$this->setActive((int)$form->getInput('active'));
		$this->setShuffle((int)$form->getInput('shuffle'));
		$this->setPdfMode((int)$form->getInput('pdf_mode'));
		$this->setDepartment(ilUtil::stripSlashes($form->getInput('department')));
	}

	/**
	 * {@inheritdoc}
	 */
	public function toArray()
	{
		return $this->__asArray();
	}

	/**
	 * @return bool
	 */
	public function arePreconditionsFulfilled()
	{
		foreach($this->getPreconditions() as $precondition)
		{
			if($precondition->isRequired() &&  !$precondition->isFulfilled())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @throws ilException
	 */
	private function validate()
	{
		if($this->getActive())
		{
			if(!$this->arePreconditionsFulfilled())
			{
				throw new ilException('scas_cant_save_pc');
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function create()
	{
		$this->validate();
		parent::create();
	}

	/**
	 * {@inheritdoc}
	 */
	public function update()
	{
		$this->validate();
		parent::update();
	}

	/**
	 * @return ilObjTest
	 */
	public function getTest()
	{
		return $this->test;
	}
}
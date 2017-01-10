<?php

require_once 'Services/Xml/classes/class.ilXmlWriter.php';

/**
 * Class ilScanAssessmentXMLResultCreator
 */
class ilScanAssessmentXMLResultCreator extends ilXmlWriter
{
	private $test_id = 0;
	private $anonymized = false;
	private $active_ids;
	private $test;

	/**
	 * ilScanAssessmentXMLResultCreator constructor.
	 * @param ilObjTest $test
	 * @param bool $anonymized
	 */
	function __construct($test, $anonymized = false)
	{
		parent::__construct();
		$this->test = $test;
		$this->test_id = $test->getId();
		$this->anonymized = $anonymized;
	}

	/**
	 * @return array
	 */
	protected function exportActiveIDs()
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT * FROM pl_scas_pdf_data WHERE obj_id = %s AND results_exported = 0',
			array('integer'), array($this->test_id));
		$pdf_ids = array();
		$user_ids = array();
		$assessmentSetting = new ilSetting("assessment");
		$user_criteria = $assessmentSetting->get("user_criteria");
		if (strlen($user_criteria) == 0) $user_criteria = 'usr_id';

		$this->xmlStartTag("tst_active", NULL);
		while ($row = $ilDB->fetchAssoc($res))
		{
			$attrs = array(
				'active_id' => $row['pdf_id'],
				'user_fi' => $row['usr_id'],
				'test_fi' => $this->test->getTestId(),
				'lastindex' => 0,
				'anonymous_id' => '',
				'last_finished_pass' => 0,
				'tries' => 1,
				'submitted' => 1,
				'submittimestamp' => date('Y-m-d h:i:s a', time()),
				'tstamp' => time(),
				'user_criteria' => 'usr_id',
				'usr_id' => $row['usr_id']
			);
			$pdf_ids[$row['pdf_id']] = $row['pdf_id'];
			$user_ids[$row['usr_id']] = $row['usr_id'];
			$name = ilObjUser::_lookupName($row['usr_id']);
			$attrs['fullname'] = trim($name["lastname"] . ", " . $name["firstname"] . " " .  $name["title"]);

			array_push($this->active_ids, $row['pdf_id']);
			$attrs['user_criteria'] = $user_criteria;
			$attrs[$user_criteria] = $row['usr_id'];
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("tst_active");

		$this->exportPassResult($pdf_ids);
		$this->exportTestSequence($pdf_ids);

		return $user_ids;
	}

	/**
	 * @param $pdf_ids
	 */
	protected function exportPassResult($pdf_ids)
	{
		$this->xmlStartTag("tst_pass_result", NULL);
		$count = sizeof($this->test->getQuestions());
		if(is_array($pdf_ids) && sizeof($pdf_ids) > 0)
		{
			foreach($pdf_ids as $pdf_id)
			{
				$attrs = array(
					'active_fi' => $pdf_id,
					'pass' => 0,
					'points' => 0,
					'maxpoints' => 0,
					'questioncount' => $count,
					'answeredquestions' => $count,
					'workingtime' => 0,
					'tstamp' => time()
				);
				$this->xmlElement("row", $attrs);
			}
		}
		$this->xmlEndTag("tst_pass_result");
	}

	/**
	 * @param $pdf_ids
	 */
	protected function exportTestSequence($pdf_ids)
	{
		$count = sizeof($this->test->getQuestions());
		$newsequence = array();
		if ($count > 0)
		{
			for ($i = 1; $i <= $count; $i++)
			{
				array_push($newsequence, $i);
			}
		}
		$this->xmlStartTag("tst_sequence");
		if(is_array($pdf_ids) && sizeof($pdf_ids) > 0)
		{
			foreach($pdf_ids as $pdf_id)
			{
				$attrs = array(
					'active_fi' => $pdf_id,
					'pass'      => 0,
					'sequence'  => serialize($newsequence),
					'postponed' => '',
					'hidden'    => '',
					'tstamp'    => time()
				);
				$this->xmlElement("row", $attrs);
			}
		}
		$this->xmlEndTag("tst_sequence");
	}

	protected function exportTestQuestions()
	{
		global $ilDB;

		$result = $ilDB->queryF("SELECT * FROM tst_test_question WHERE test_fi = %s",
			array('integer'),
			array($this->test->getTestId())
		);
		$this->xmlStartTag("tst_test_question", NULL);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$attrs = array(
				'test_question_id' => $row['test_question_id'],
				'test_fi' => $row['test_fi'],
				'question_fi' => $row['question_fi'],
				'sequence' => $row['sequence'],
				'tstamp' => $row['tstamp']
			);
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("tst_test_question");
	}

	protected function exportTestSolutions()
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT * FROM pl_scas_scan_data WHERE test_id = %s',
			array('integer'), array($this->test_id));
		$test_results = array();
		$this->xmlStartTag("tst_solutions", NULL);
		while ($row = $ilDB->fetchAssoc($res))
		{
			$attrs = array(
				'solution_id' => $row['answer_id'],
				'active_fi' => $row['pdf_id'],
				'question_fi' => $row['qid'],
				'points' => 0,
				'pass' => 0,
				'value1' => $row['value1'],
				'value2' => $row['value2'],
				'tstamp' => 0
			);
			$test_results[] = array('qfi' =>  $row['qid'], 'aid' => $row['pdf_id']);
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("tst_solutions");
		$this->exportTestResults($test_results);
	}

	/**
	 * @param $results
	 */
	protected function exportTestResults($results)
	{
		$this->xmlStartTag("tst_test_result", NULL);
		foreach($results as $key => $value)
		{
			$attrs = array(
				'test_result_id' => 0,
				'active_fi' => $value['aid'],
				'question_fi' => $value['qfi'],
				'points' => 0,
				'pass' => 0,
				'manual' => 0,
				'tstamp' => time()
			);
			$this->xmlElement("row", $attrs);
		}

		$this->xmlEndTag("tst_test_result");
	}

	/**
	 * @return array
	 */
	function getXML()
	{
		$this->active_ids = array();
		$this->xmlHeader();
		$attrs = array("version" => "4.1.0");
		$this->xmlStartTag("results", $attrs);
		$val = $this->exportActiveIDs();
		$this->exportTestQuestions();
		$this->exportTestSolutions();
		$this->xmlEndTag("results");
		return $val;
	}

	/**
	 * @param bool $format
	 * @return string
	 */
	function xmlDumpMem($format = TRUE)
	{
		$this->getXML();
		return parent::xmlDumpMem($format);
	}

	function xmlDumpFile($file, $format = TRUE)
	{
		$val = $this->getXML();
		parent::xmlDumpFile($file, $format);
		return $val;
	}

}
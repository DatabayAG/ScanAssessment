<?php

require_once 'Services/Xml/classes/class.ilXmlWriter.php';

/**
 * Class ilScanAssessmentDebugExport
 */
class ilScanAssessmentDebugExport extends ilXmlWriter
{
	private $test_id = 0;
	private $anonymized = false;
	private $active_ids;
	private $test;
	
	private $db;
	
	private $pdf_ids = array();

	/**
	 * ilScanAssessmentDebugExport constructor.
	 * @param ilObjTest $test
	 * @param bool $anonymized
	 */
	function __construct($test, $anonymized = false)
	{
		global $ilDB;
		parent::__construct();
		$this->test = $test;
		$this->test_id = $test->getId();
		$this->anonymized = $anonymized;
		$this->db = $ilDB;
	}

	/**
	 */
	protected function exportConfig()
	{
		$found_row = false;
		$result = $this->db->queryF("SELECT * FROM pl_scas_test_config WHERE obj_id = %s",
			array('integer'),
			array($this->test->getId())
		);
		$this->xmlStartTag("sacs_config", NULL);

		while ($row = $this->db->fetchAssoc($result))
		{
			$attrs = array(
				'obj_id'	=> $row['obj_id'],
				'active'	=> $row['active'],
				'shuffle'	=> $row['shuffle'],
				'pdf_mode'	=> $row['pdf_mode']
			);
			$found_row = true;
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("sacs_config");
		return $found_row;
	}

	/**
	 */
	protected function exportScasScanData()
	{
		$result = $this->db->queryF("SELECT * FROM pl_scas_scan_data WHERE test_id = %s",
			array('integer'),
			array($this->test->getId())
		);
		$this->xmlStartTag("sacs_scan_data", NULL);

		while ($row = $this->db->fetchAssoc($result))
		{
			$attrs = array(
				'answer_id'		=> $row['obj_id'],
				'pdf_id'		=> $row['active'],
				'page'			=> $row['shuffle'],
				'test_id'		=> $row['test_id'],
				'qid'			=> $row['qid'],
				'value1'		=> $row['value1'],
				'value2'		=> $row['value2'],
				'correctness'	=> $row['correctness']
			);
			$this->xmlElement("row", $attrs);
		}

		$this->xmlEndTag("sacs_scan_data");
	}

	protected function exportScasPdfData()
	{
		$result = $this->db->queryF("SELECT * FROM pl_scas_pdf_data WHERE obj_id = %s",
			array('integer'),
			array($this->test->getId())
		);
		
		$this->xmlStartTag("sacs_pdf_data", NULL);

		while ($row = $this->db->fetchAssoc($result))
		{
			$attrs = array(
				'pdf_id'				=> $row['pdf_id'],
				'obj_id'				=> $row['obj_id'],
				'usr_id'				=> $row['usr_id'],
				'personalised'			=> $row['personalised'],
				'revision_done'			=> $row['revision_done'],
				'results_exported'		=> $row['results_exported'],
				'matriculation_matrix'	=> base64_encode($row['matriculation_matrix'])
			);
			$this->xmlElement("row", $attrs);
			$this->pdf_ids[] = $row['pdf_id'];
		}

		$this->xmlEndTag("sacs_pdf_data");
	}

	protected function exportScasQplData()
	{
		$result = $this->db->query("SELECT * FROM pl_scas_pdf_data_qpl WHERE " . $this->db->in('pdf_id', $this->pdf_ids, false, array('integer')));

		$this->xmlStartTag("sacs_pdf_data_qpl", NULL);
		while ($row = $this->db->fetchAssoc($result))
		{
			$attrs = array(
				'pdf_id'			=> $row['pdf_id'],
				'page'				=> $row['page'],
				'qpl_data'			=> base64_encode($row['qpl_data']),
				'has_checkboxes'	=> $row['has_checkboxes']

			);
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("sacs_pdf_data_qpl");
	}

	protected function exportScasUserPackages()
	{
		$result = $this->db->queryF("SELECT * FROM pl_scas_user_packages WHERE tst_id = %s",
			array('integer'),
			array($this->test->getId())
		);
		$this->xmlStartTag("sacs_user_packages", NULL);
		while ($row = $this->db->fetchAssoc($result))
		{
			$attrs = array(
				'tst_id'				=> $row['tst_id'],
				'count_documents'		=> $row['count_documents'],
				'matriculation_code'	=> $row['matriculation_code'],
				'matriculation_style'	=> $row['matriculation_style'],
				'download_style'		=> $row['download_style'],
				'personalised'			=> $row['personalised'],
				'documents_generated'	=> $row['documents_generated'],
				'no_name_field'			=> $row['no_name_field'],
				'assessment_date'		=> $row['assessment_date']

			);
			$this->xmlElement("row", $attrs);
		}
		$this->xmlEndTag("sacs_user_packages");
	}

	/**
	 * @return array
	 */
	function getXML()
	{
		$this->active_ids = array();
		$this->xmlHeader();
		$attrs = array("version" => "4.1.0");
		$this->xmlStartTag("scanAssessment", $attrs);
		$val = $this->exportConfig();
		$this->exportScasScanData();
		$this->exportScasPdfData();
		$this->exportScasQplData();
		$this->exportScasUserPackages();
		$this->xmlEndTag("scanAssessment");
		return $val;
	}

	/**
	 * @param bool $format
	 * @return string
	 */
	function xmlDumpMem($format = TRUE)
	{
		$this->getXML();
		parent::xmlDumpMem($format);
	}

	function xmlDumpFile($file, $format = TRUE)
	{
		$val = $this->getXML();
		if($val)
		{
			parent::xmlDumpFile($file, $format);
			return $val;
		}
	}

}